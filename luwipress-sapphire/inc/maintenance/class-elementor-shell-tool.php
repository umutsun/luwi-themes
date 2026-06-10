<?php
/**
 * Elementor Shell Cleanup Tool
 *
 * Detects posts whose `_elementor_data` is an empty/near-empty skeleton AND
 * whose underlying `post_content` contains substantive Gutenberg/classic
 * content. The Elementor shell hijacks the page-template router (because
 * `_wp_page_template = elementor_header_footer.php` or the theme defaults
 * to Elementor canvas), masking the real content under a blank-middle layout.
 *
 * The fix is to strip the four hijacking meta keys:
 *   - `_elementor_edit_mode`   (so Elementor stops claiming the post)
 *   - `_elementor_data`        (the empty skeleton itself)
 *   - `_elementor_template_type`
 *   - `_elementor_version`
 *
 * Backups capture every stripped meta value so restore() can replay them
 * exactly. WPML siblings are auto-expanded by the Theme Bridge — we receive
 * them in `$args['_expanded_post_ids']`.
 *
 * Replays the work the operator did manually via MCP `meta_delete` on
 * 4 posts (`/the-enchanting-persian-tar/` + 3 siblings) on 2026-05-08.
 *
 * @package luwipress-sapphire
 * @since   1.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LuwiPress_Sapphire_Elementor_Shell_Tool {

	const SHELL_META_KEYS = array(
		'_elementor_data',
		'_elementor_edit_mode',
		'_elementor_template_type',
		'_elementor_version',
	);

	/**
	 * `_elementor_data` payloads at or below this byte length are considered
	 * empty skeletons. Real Elementor pages are 2 KB+ minimum (one section +
	 * column + widget already runs ~600-800 bytes; plus settings JSON).
	 *
	 * 200 catches: `[]`, `null`, `[{"id":"...","elType":"section","settings":[],"elements":[],"isInner":false}]`.
	 */
	const SHELL_DATA_MAX_BYTES = 256;

	/**
	 * Minimum body length of `post_content` (after stripping shortcodes/tags)
	 * for a candidate to qualify as "real Gutenberg content underneath".
	 */
	const MIN_REAL_CONTENT_BYTES = 400;

	public static function scan( $args = array(), $tool = array() ) {
		$post_types = isset( $args['post_types'] ) && is_array( $args['post_types'] )
			? array_map( 'sanitize_key', $args['post_types'] )
			: array( 'post', 'page', 'product' );

		$limit = isset( $args['limit'] ) ? max( 1, min( 200, (int) $args['limit'] ) ) : 100;

		global $wpdb;

		// Subquery: posts that have an `_elementor_data` meta entry. We do NOT
		// filter by length in SQL because the data is JSON and may be slashed —
		// PHP-side check is more reliable.
		$placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );
		$ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT p.ID FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_elementor_data'
			 WHERE p.post_status = 'publish'
			   AND p.post_type IN ($placeholders)
			 ORDER BY p.ID DESC
			 LIMIT %d",
			array_merge( $post_types, array( $limit * 5 ) )
		) );

		$candidates = array();
		foreach ( $ids as $pid ) {
			$pid     = (int) $pid;
			$post    = get_post( $pid );
			if ( ! $post ) {
				continue;
			}
			$ele_data = get_post_meta( $pid, '_elementor_data', true );
			$raw      = is_string( $ele_data ) ? $ele_data : wp_json_encode( $ele_data );
			$raw_len  = $raw ? strlen( $raw ) : 0;

			if ( $raw_len > self::SHELL_DATA_MAX_BYTES ) {
				continue;
			}

			$decoded = json_decode( wp_unslash( $raw ), true );
			if ( is_array( $decoded ) && self::has_meaningful_widget( $decoded ) ) {
				continue; // real Elementor content despite small size
			}

			$plain = trim( wp_strip_all_tags( strip_shortcodes( (string) $post->post_content ) ) );
			$plain_len = strlen( $plain );
			if ( $plain_len < self::MIN_REAL_CONTENT_BYTES ) {
				continue; // no real content underneath — leave it alone
			}

			$candidates[] = array(
				'id'         => $pid,
				'title'      => get_the_title( $pid ) ?: $post->post_name,
				'post_type'  => $post->post_type,
				'shell_size' => $raw_len,
				'real_size'  => $plain_len,
				'meta'       => sprintf( 'shell %dB · real %.1fKB', $raw_len, $plain_len / 1024 ),
				'edit_link'  => get_edit_post_link( $pid, '' ),
			);

			if ( count( $candidates ) >= $limit ) {
				break;
			}
		}

		return array(
			'candidates' => $candidates,
			'count'      => count( $candidates ),
			'criteria'   => array(
				'shell_max_bytes' => self::SHELL_DATA_MAX_BYTES,
				'min_real_bytes'  => self::MIN_REAL_CONTENT_BYTES,
				'post_types'      => $post_types,
				'limit'           => $limit,
			),
		);
	}

	public static function execute( $args = array(), $tool = array() ) {
		// Bridge auto-expanded WPML/Polylang siblings into _expanded_post_ids.
		$ids = ! empty( $args['_expanded_post_ids'] )
			? array_map( 'intval', (array) $args['_expanded_post_ids'] )
			: array_map( 'intval', (array) ( $args['post_ids'] ?? array() ) );
		$ids = array_values( array_unique( array_filter( $ids ) ) );

		if ( empty( $ids ) ) {
			return new WP_Error( 'no_post_ids', 'Provide at least one post_id.', array( 'status' => 400 ) );
		}

		$backup_payload = array();
		$mutated        = 0;
		$skipped        = array();

		foreach ( $ids as $pid ) {
			$post = get_post( $pid );
			if ( ! $post ) {
				$skipped[] = array( 'id' => $pid, 'reason' => 'not_found' );
				continue;
			}

			$captured = array();
			$any      = false;
			foreach ( self::SHELL_META_KEYS as $key ) {
				$val = get_post_meta( $pid, $key, true );
				if ( $val !== '' && $val !== null ) {
					$captured[ $key ] = $val;
					$any = true;
				}
			}

			if ( ! $any ) {
				$skipped[] = array( 'id' => $pid, 'reason' => 'no_shell_meta' );
				continue;
			}

			$captured['_wp_page_template'] = get_post_meta( $pid, '_wp_page_template', true );

			$backup_payload[ $pid ] = $captured;

			foreach ( self::SHELL_META_KEYS as $key ) {
				delete_post_meta( $pid, $key );
			}
			// Also reset _wp_page_template if it's pointing at the Elementor
			// header-footer template, which is the migration symptom (Hello
			// Elementor → custom theme leaves this stuck).
			$tpl = get_post_meta( $pid, '_wp_page_template', true );
			if ( $tpl === 'elementor_header_footer.php' || $tpl === 'elementor_canvas.php' ) {
				delete_post_meta( $pid, '_wp_page_template' );
			}

			$mutated++;
		}

		// Best-effort cache flush — Elementor caches per-post CSS files.
		if ( class_exists( '\\Elementor\\Plugin' ) ) {
			try {
				$ele = \Elementor\Plugin::$instance;
				if ( $ele && isset( $ele->files_manager ) && method_exists( $ele->files_manager, 'clear_cache' ) ) {
					$ele->files_manager->clear_cache();
				}
			} catch ( \Throwable $e ) {
				// ignore — page cache layers (LiteSpeed) handle the visitor side
			}
		}

		return array(
			'mutated'         => $mutated,
			'skipped'         => $skipped,
			'post_ids'        => $ids,
			'_backup_payload' => $backup_payload,
		);
	}

	public static function restore( $args = array(), $tool = array() ) {
		$bridge = LuwiPress_Theme_Bridge::get_instance();
		$backup_id = sanitize_text_field( $args['backup_id'] ?? '' );
		if ( '' === $backup_id ) {
			return new WP_Error( 'no_backup_id', 'Missing backup_id.', array( 'status' => 400 ) );
		}
		$entry = $bridge->load_backup( $backup_id );
		if ( ! $entry || $entry['tool_id'] !== 'elementor_shell_cleanup' ) {
			return new WP_Error( 'backup_not_found', 'Backup not found.', array( 'status' => 404 ) );
		}

		$payload  = is_array( $entry['payload'] ) ? $entry['payload'] : array();
		$restored = 0;
		foreach ( $payload as $pid => $meta_set ) {
			$pid = (int) $pid;
			if ( ! get_post( $pid ) || ! is_array( $meta_set ) ) {
				continue;
			}
			foreach ( $meta_set as $key => $val ) {
				if ( $val === '' || $val === null ) {
					continue;
				}
				update_post_meta( $pid, $key, wp_slash( $val ) );
			}
			$restored++;
		}

		if ( class_exists( '\\Elementor\\Plugin' ) ) {
			try {
				$ele = \Elementor\Plugin::$instance;
				if ( $ele && isset( $ele->files_manager ) && method_exists( $ele->files_manager, 'clear_cache' ) ) {
					$ele->files_manager->clear_cache();
				}
			} catch ( \Throwable $e ) {}
		}

		return array(
			'restored' => $restored,
			'backup_id'=> $backup_id,
			'post_ids' => array_map( 'intval', array_keys( $payload ) ),
		);
	}

	/**
	 * Walks an Elementor data array looking for ANY widget that's not a
	 * placeholder. Returns true on first hit. A "real" widget has a non-empty
	 * `widgetType` AND a non-empty `settings` map OR holds nested elements.
	 */
	private static function has_meaningful_widget( $nodes ) {
		foreach ( (array) $nodes as $node ) {
			if ( ! is_array( $node ) ) {
				continue;
			}
			if ( ! empty( $node['widgetType'] ) ) {
				if ( ! empty( $node['settings'] ) && is_array( $node['settings'] ) && count( $node['settings'] ) > 0 ) {
					return true;
				}
			}
			if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
				if ( self::has_meaningful_widget( $node['elements'] ) ) {
					return true;
				}
			}
		}
		return false;
	}
}
