<?php
/**
 * Additional audit-tier tools (ecosystem hygiene).
 *
 *   - LuwiPress_Emerald_Broken_Internal_Links_Tool   HEAD-check internal hrefs in post_content
 *   - LuwiPress_Emerald_Empty_Term_Archives_Tool     product_cat with 0 published products
 *   - LuwiPress_Emerald_WPML_Strings_Tool            WPML String Translation pending entries
 *   - LuwiPress_Emerald_Page_Speed_Signals_Tool      autoload size + transient cruft + object cache
 *
 * Read-only by design; surface findings, do not mutate. Fixes go through
 * dedicated UIs (Edit screens, WPML String Translation, Tools → Site Health).
 *
 * @package luwipress-emerald
 * @since   1.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// Broken Internal Links — HEAD-check every internal href appearing in
// published post_content. Skips assets (uploads, fonts), self-anchors, and
// external domains. Honours a 25-link limit per scan to avoid timing out.
// ─────────────────────────────────────────────────────────────────────────────

class LuwiPress_Emerald_Broken_Internal_Links_Tool {

	const MAX_LINKS_PER_SCAN = 60;

	public static function scan( $args = array(), $tool = array() ) {
		$home = home_url( '/' );
		$home_host = wp_parse_url( $home, PHP_URL_HOST );
		if ( ! $home_host ) {
			return array( 'candidates' => array(), 'count' => 0 );
		}

		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT ID, post_title, post_content
			   FROM {$wpdb->posts}
			  WHERE post_status = 'publish'
			    AND post_type IN ('post','page','product')
			    AND post_content LIKE '%href=%'
			  ORDER BY post_modified DESC
			  LIMIT 200"
		);

		$seen = array();
		$candidates = array();
		$checked = 0;

		foreach ( (array) $rows as $row ) {
			if ( $checked >= self::MAX_LINKS_PER_SCAN ) {
				break;
			}
			if ( ! preg_match_all( '/href=["\']([^"\']+)["\']/i', $row->post_content, $m ) ) {
				continue;
			}
			foreach ( array_unique( $m[1] ) as $href ) {
				if ( $checked >= self::MAX_LINKS_PER_SCAN ) {
					break 2;
				}
				$href = trim( $href );
				if ( $href === '' || $href[0] === '#' ) {
					continue;
				}
				if ( strpos( $href, 'mailto:' ) === 0 || strpos( $href, 'tel:' ) === 0 || strpos( $href, 'javascript:' ) === 0 ) {
					continue;
				}
				$abs = self::absolute_url( $href, $home );
				if ( ! $abs ) {
					continue;
				}
				$host = wp_parse_url( $abs, PHP_URL_HOST );
				if ( $host !== $home_host ) {
					continue; // external — out of scope
				}
				// Skip uploads + static assets.
				$path = wp_parse_url( $abs, PHP_URL_PATH );
				if ( ! $path || preg_match( '#/wp-content/uploads/|\\.(jpg|jpeg|png|gif|webp|svg|css|js|pdf|zip)$#i', $path ) ) {
					continue;
				}
				if ( isset( $seen[ $abs ] ) ) {
					continue;
				}
				$seen[ $abs ] = true;

				$status = self::head_check( $abs );
				$checked++;

				if ( $status === 200 || $status === 301 || $status === 302 ) {
					continue;
				}
				$candidates[] = array(
					'id'    => (int) $row->ID,
					'title' => $row->post_title ?: ( '#' . $row->ID ),
					'meta'  => sprintf( '%d %s', $status, $abs ),
				);
			}
		}

		return array(
			'candidates' => $candidates,
			'count'      => count( $candidates ),
			'meta'       => array(
				'scanned'        => $checked,
				'limit_per_scan' => self::MAX_LINKS_PER_SCAN,
				'note'           => 'Read-only HEAD-check of internal hrefs. Re-run periodically — slugs change, especially after WC re-imports. Findings show "post_id · status · url" in the meta column.',
			),
		);
	}

	private static function absolute_url( $href, $base ) {
		// Already absolute.
		if ( preg_match( '#^https?://#i', $href ) ) {
			return $href;
		}
		// Protocol-relative.
		if ( strpos( $href, '//' ) === 0 ) {
			return ( wp_parse_url( $base, PHP_URL_SCHEME ) ?: 'https' ) . ':' . $href;
		}
		// Root-relative.
		if ( $href[0] === '/' ) {
			$base_host = wp_parse_url( $base, PHP_URL_SCHEME ) . '://' . wp_parse_url( $base, PHP_URL_HOST );
			return $base_host . $href;
		}
		// Other relative — drop, too noisy.
		return '';
	}

	private static function head_check( $url ) {
		$res = wp_remote_head( $url, array(
			'timeout'     => 4,
			'redirection' => 0,
			'sslverify'   => false,
			'user-agent'  => 'LuwiPressGold-LinkCheck/1.0',
		) );
		if ( is_wp_error( $res ) ) {
			return 0;
		}
		return (int) wp_remote_retrieve_response_code( $res );
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// Empty Term Archives — product_cat terms with zero published products that
// nonetheless appear in a navigation menu. Operator should either populate
// or remove the menu entry; the empty archive is dead weight for SEO + UX.
// ─────────────────────────────────────────────────────────────────────────────

class LuwiPress_Emerald_Empty_Term_Archives_Tool {

	public static function scan( $args = array(), $tool = array() ) {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return array( 'candidates' => array(), 'count' => 0, 'meta' => array( 'wc_active' => false ) );
		}

		$terms = get_terms( array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
		) );
		if ( is_wp_error( $terms ) ) {
			return array( 'candidates' => array(), 'count' => 0 );
		}

		// Collect every term_id that's referenced by a nav menu item.
		$nav_term_ids = array();
		foreach ( wp_get_nav_menus() as $menu ) {
			$items = wp_get_nav_menu_items( $menu->term_id );
			if ( ! is_array( $items ) ) {
				continue;
			}
			foreach ( $items as $i ) {
				if ( $i->object === 'product_cat' ) {
					$nav_term_ids[] = (int) $i->object_id;
				}
			}
		}
		$nav_term_ids = array_unique( $nav_term_ids );

		$candidates = array();
		foreach ( $terms as $t ) {
			if ( $t->count > 0 ) {
				continue;
			}
			$in_nav = in_array( (int) $t->term_id, $nav_term_ids, true );
			if ( ! $in_nav ) {
				continue; // empty AND not in nav = not user-visible, leave alone
			}
			$candidates[] = array(
				'id'    => (int) $t->term_id,
				'title' => $t->name,
				'meta'  => sprintf( 'slug=%s · 0 products · in nav', $t->slug ),
				'href'  => get_term_link( $t ),
			);
		}

		return array(
			'candidates' => $candidates,
			'count'      => count( $candidates ),
			'meta'       => array(
				'note'              => 'Empty product_cat terms reachable via nav menu. Either add products or drop the menu link.',
				'nav_term_count'    => count( $nav_term_ids ),
				'total_term_count'  => count( $terms ),
			),
		);
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// WPML String Translation Pending — entries in `icl_strings` whose status
// isn't 10 (translated). Catches UI strings that themes/plugins register
// via __() but the operator never translated. WPML statuses:
//   0 = not translated, 1 = needs update, 2 = duplicate, 3..9 = various,
//   10 = translated.
// ─────────────────────────────────────────────────────────────────────────────

class LuwiPress_Emerald_WPML_Strings_Tool {

	public static function scan( $args = array(), $tool = array() ) {
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
			return array( 'candidates' => array(), 'count' => 0, 'meta' => array( 'wpml_active' => false ) );
		}

		global $wpdb;
		$strings_table = $wpdb->prefix . 'icl_string_translations';
		$has_table     = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $strings_table ) );
		if ( ! $has_table ) {
			return array( 'candidates' => array(), 'count' => 0, 'meta' => array( 'wpml_strings_table' => false ) );
		}

		$rows = $wpdb->get_results(
			"SELECT s.id, s.string_id, s.language, s.status, src.name, src.value, src.context
			   FROM {$wpdb->prefix}icl_string_translations s
			   JOIN {$wpdb->prefix}icl_strings src ON src.id = s.string_id
			  WHERE s.status <> 10
			  ORDER BY s.id DESC
			  LIMIT 100"
		);

		$candidates = array();
		foreach ( (array) $rows as $row ) {
			$preview = mb_substr( wp_strip_all_tags( (string) $row->value ), 0, 80 );
			$candidates[] = array(
				'id'    => (int) $row->id,
				'title' => $row->name ?: ( '#' . $row->id ),
				'meta'  => sprintf( '%s · %s · status=%d · "%s"', $row->context, $row->language, (int) $row->status, $preview ),
			);
		}

		return array(
			'candidates' => $candidates,
			'count'      => count( $candidates ),
			'meta'       => array(
				'note' => 'Resolve in WPML → String Translation. Status codes: 0=untranslated, 1=needs-update, 2=duplicate, 10=done.',
			),
		);
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// Page Speed Signals — wp_options autoload size, expired transient cruft,
// and a quick object-cache health probe. Catches the silent-truncation bug
// (412 KB option) before it bites + warns when the autoload payload bloats.
// ─────────────────────────────────────────────────────────────────────────────

class LuwiPress_Emerald_Page_Speed_Signals_Tool {

	const AUTOLOAD_WARN_BYTES = 800 * 1024;   // bootstrap concern starts here
	const TRANSIENT_WARN_ROWS = 200;          // expired cruft above this

	public static function scan( $args = array(), $tool = array() ) {
		global $wpdb;

		$autoload_total = (int) $wpdb->get_var(
			"SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload = 'yes'"
		);

		// Top 5 autoload hogs — surface so the operator can see what's heavy.
		$top = $wpdb->get_results(
			"SELECT option_name, LENGTH(option_value) AS bytes
			   FROM {$wpdb->options}
			  WHERE autoload = 'yes'
			  ORDER BY bytes DESC
			  LIMIT 5"
		);

		// Expired transients that haven't been GC'd. Each row is a
		// `_transient_timeout_*` whose ts < now (the matching value row may
		// or may not still exist; both should be cleaned).
		$now = time();
		$expired = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->options}
			  WHERE option_name LIKE '\\_transient\\_timeout\\_%' AND CAST(option_value AS UNSIGNED) < %d",
			$now
		) );

		// Object cache probe — write+read a value, time the round-trip.
		$cache_class = wp_using_ext_object_cache() ? get_class( $GLOBALS['wp_object_cache'] ?? new stdClass() ) : 'WP default (DB-backed)';
		$probe_key   = 'lwp_emerald_perf_probe_' . wp_generate_password( 6, false );
		$start       = microtime( true );
		wp_cache_set( $probe_key, '1', 'lwp', 30 );
		$got = wp_cache_get( $probe_key, 'lwp' );
		$elapsed_ms = round( ( microtime( true ) - $start ) * 1000, 2 );
		$cache_works = $got === '1';
		wp_cache_delete( $probe_key, 'lwp' );

		$candidates = array();

		if ( $autoload_total > self::AUTOLOAD_WARN_BYTES ) {
			$candidates[] = array(
				'id'    => 'autoload_size',
				'title' => __( 'Autoload payload above safe threshold', 'luwipress-emerald' ),
				'meta'  => sprintf( '%.1f KB autoloaded · top 5: %s', $autoload_total / 1024, implode( ', ', array_map( function ( $r ) { return $r->option_name . ' (' . round( $r->bytes / 1024 ) . ' KB)'; }, $top ) ) ),
			);
		}
		if ( $expired > self::TRANSIENT_WARN_ROWS ) {
			$candidates[] = array(
				'id'    => 'transient_cruft',
				'title' => __( 'Expired transients not garbage-collected', 'luwipress-emerald' ),
				'meta'  => sprintf( '%d expired transient rows', $expired ),
			);
		}
		if ( ! $cache_works ) {
			$candidates[] = array(
				'id'    => 'object_cache_broken',
				'title' => __( 'Object cache write/read failed', 'luwipress-emerald' ),
				'meta'  => sprintf( 'class=%s · probe round-trip %s ms', $cache_class, $elapsed_ms ),
			);
		}

		return array(
			'candidates' => $candidates,
			'count'      => count( $candidates ),
			'meta'       => array(
				'autoload_bytes'       => $autoload_total,
				'autoload_warn_bytes'  => self::AUTOLOAD_WARN_BYTES,
				'expired_transients'   => $expired,
				'object_cache_class'   => $cache_class,
				'object_cache_works'   => $cache_works,
				'object_cache_ms'      => $elapsed_ms,
				'top_autoload_options' => $top,
				'note'                 => 'Read-only signals. Fix autoload bloat by toggling autoload=no on hot options; clean transients via WP-CLI `transient delete --expired`; install Redis/Memcached if object_cache is DB-backed.',
			),
		);
	}
}
