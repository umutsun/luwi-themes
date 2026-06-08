<?php
/**
 * Maintenance tool classes (5 read-mostly utilities).
 *
 *   - LuwiPress_Amber_Slug_Conflict_Audit_Tool   read-only redirect map view
 *   - LuwiPress_Amber_WPML_Drift_Tool            outdated translation detection
 *   - LuwiPress_Amber_Legacy_Canvas_Tool         find Hello Elementor leftovers
 *   - LuwiPress_Amber_Kit_CSS_Health_Tool        Kit CSS size + headroom audit
 *   - LuwiPress_Amber_Orphan_Media_Tool          unreferenced uploads (read-only)
 *
 * @package luwipress-amber
 * @since   1.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// Slug Conflict Audit — read-only view of the current redirect map
// ─────────────────────────────────────────────────────────────────────────────

class LuwiPress_Amber_Slug_Conflict_Audit_Tool {

	public static function scan( $args = array(), $tool = array() ) {
		$transient_key = defined( 'LUWIPRESS_AMBER_SLUG_CONFLICT_TRANSIENT' )
			? LUWIPRESS_AMBER_SLUG_CONFLICT_TRANSIENT
			: 'luwipress_amber_slug_conflicts_v1';
		$map = get_transient( $transient_key );
		if ( ! is_array( $map ) ) {
			$map = array();
		}

		$candidates = array();
		foreach ( $map as $slug => $term_id ) {
			$term = get_term( (int) $term_id, 'product_cat' );
			$link = $term && ! is_wp_error( $term ) ? get_term_link( $term ) : '';
			$candidates[] = array(
				'id'    => $slug,
				'title' => '/' . $slug . '/',
				'meta'  => $term && ! is_wp_error( $term )
					? sprintf( '→ %s (term #%d)', $term->name, (int) $term_id )
					: sprintf( '→ term #%d (missing)', (int) $term_id ),
				'href'  => is_string( $link ) ? $link : '',
			);
		}

		$enabled = (bool) get_theme_mod( 'luwipress_amber_resolve_slug_conflicts', false );
		return array(
			'candidates' => $candidates,
			'count'      => count( $candidates ),
			'meta'       => array(
				'redirect_enabled' => $enabled,
				'transient_key'    => $transient_key,
				'transient_age'    => self::transient_age( $transient_key ),
			),
		);
	}

	private static function transient_age( $key ) {
		$timeout = get_option( '_transient_timeout_' . $key );
		if ( ! $timeout ) {
			return null;
		}
		return max( 0, HOUR_IN_SECONDS - ( (int) $timeout - time() ) );
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// WPML Drift — find translations whose source has changed since last sync
// ─────────────────────────────────────────────────────────────────────────────

class LuwiPress_Amber_WPML_Drift_Tool {

	public static function scan( $args = array(), $tool = array() ) {
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
			return array(
				'candidates' => array(),
				'count'      => 0,
				'meta'       => array( 'wpml_active' => false ),
			);
		}

		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT p.ID, p.post_title, p.post_modified, t.language_code,
			        m.meta_value AS synced_at
			   FROM {$wpdb->posts} p
			   JOIN {$wpdb->prefix}icl_translations t ON p.ID = t.element_id
			   LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_luwipress_synced_source_modified'
			  WHERE p.post_status = 'publish'
			    AND t.element_type LIKE 'post_%'
			    AND t.source_language_code IS NOT NULL
			    AND m.meta_value IS NOT NULL
			    AND m.meta_value <> ''
			  ORDER BY p.post_modified DESC
			  LIMIT 200"
		);

		$candidates = array();
		foreach ( (array) $rows as $row ) {
			// `_luwipress_synced_source_modified` stores the source's
			// post_modified at the time of the last successful sync. If the
			// source has since been edited, drift exists.
			$source_id = apply_filters( 'wpml_master_post_from_duplicate', null, (int) $row->ID );
			if ( ! $source_id || (int) $source_id === (int) $row->ID ) {
				continue;
			}
			$source_modified = get_post_field( 'post_modified', (int) $source_id );
			if ( ! $source_modified ) {
				continue;
			}
			if ( strtotime( $source_modified ) <= strtotime( $row->synced_at ) ) {
				continue;
			}
			$candidates[] = array(
				'id'      => (int) $row->ID,
				'title'   => $row->post_title,
				'meta'    => sprintf(
					'%s · synced %s · source updated %s',
					$row->language_code,
					mysql2date( 'Y-m-d', $row->synced_at ),
					mysql2date( 'Y-m-d', $source_modified )
				),
			);
		}

		return array(
			'candidates' => $candidates,
			'count'      => count( $candidates ),
			'meta'       => array( 'wpml_active' => true ),
		);
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// Legacy Canvas Migration — Hello Elementor leftovers
// ─────────────────────────────────────────────────────────────────────────────

class LuwiPress_Amber_Legacy_Canvas_Tool {

	const LEGACY_TEMPLATES = array(
		'elementor_canvas.php',
		'elementor_canvas',
		'elementor_header_footer.php',
		'elementor_header_footer',
	);

	public static function scan( $args = array(), $tool = array() ) {
		global $wpdb;
		$placeholders = implode( ',', array_fill( 0, count( self::LEGACY_TEMPLATES ), '%s' ) );
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT p.ID, p.post_title, p.post_type, m.meta_value AS template
			   FROM {$wpdb->posts} p
			   JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_wp_page_template'
			  WHERE p.post_status = 'publish'
			    AND m.meta_value IN ($placeholders)
			  ORDER BY p.ID DESC
			  LIMIT 200",
			self::LEGACY_TEMPLATES
		) );

		$candidates = array();
		foreach ( (array) $rows as $row ) {
			$resolved = locate_template( array( $row->template ) );
			$ours     = $resolved && strpos( $resolved, get_stylesheet_directory() ) === 0;
			// Posts route to single.php at request time via the
			// `template_include` filter in elementor-template-force.php (1.7.0+);
			// flag pages separately since pages legitimately use canvas builds.
			$is_post = $row->post_type === 'post';
			$candidates[] = array(
				'id'    => (int) $row->ID,
				'title' => $row->post_title,
				'meta'  => sprintf(
					'%s · %s · %s%s',
					$row->post_type,
					$row->template,
					$ours ? 'theme overrides ✓' : 'theme missing ✗',
					$is_post ? ' · execute will delete meta (single.php takes over)' : ''
				),
			);
		}

		return array(
			'candidates' => $candidates,
			'count'      => count( $candidates ),
		);
	}

	/**
	 * Strip the legacy `_wp_page_template` meta from selected posts. This is
	 * the DB-level companion to the request-time `template_include` filter:
	 * the filter neutralises the symptom invisibly per-render, this tool
	 * removes the root cause so the meta no longer pollutes WP exports +
	 * any tooling that reads it directly.
	 *
	 * Only `post_type=post` is mutated by default — pages may legitimately
	 * use Elementor canvas. Pass `args[allow_page]=true` to opt pages in.
	 */
	public static function execute( $args = array(), $tool = array() ) {
		$ids = array_map( 'intval', (array) ( $args['post_ids'] ?? array() ) );
		$ids = array_values( array_unique( array_filter( $ids ) ) );
		if ( empty( $ids ) ) {
			return new WP_Error( 'no_post_ids', 'Provide at least one post_id.', array( 'status' => 400 ) );
		}
		$allow_page = ! empty( $args['allow_page'] );

		$mutated = 0;
		$skipped = array();
		$backup  = array();

		foreach ( $ids as $pid ) {
			$post = get_post( $pid );
			if ( ! $post ) {
				$skipped[] = array( 'id' => $pid, 'reason' => 'not_found' );
				continue;
			}
			if ( $post->post_type !== 'post' && ! $allow_page ) {
				$skipped[] = array( 'id' => $pid, 'reason' => 'not_post_type=post (pass allow_page=true to override)' );
				continue;
			}
			$tpl = get_post_meta( $pid, '_wp_page_template', true );
			if ( ! in_array( (string) $tpl, self::LEGACY_TEMPLATES, true ) ) {
				$skipped[] = array( 'id' => $pid, 'reason' => 'meta not legacy (' . (string) $tpl . ')' );
				continue;
			}
			$backup[ $pid ] = array( '_wp_page_template' => $tpl );
			delete_post_meta( $pid, '_wp_page_template' );
			$mutated++;
		}

		// Bust Elementor per-post CSS cache for affected posts.
		if ( $mutated > 0 && class_exists( '\\Elementor\\Plugin' ) ) {
			try {
				$ele = \Elementor\Plugin::$instance;
				if ( $ele && isset( $ele->files_manager ) && method_exists( $ele->files_manager, 'clear_cache' ) ) {
					$ele->files_manager->clear_cache();
				}
			} catch ( \Throwable $e ) {}
		}

		return array(
			'mutated'         => $mutated,
			'skipped'         => $skipped,
			'post_ids'        => $ids,
			'_backup_payload' => $backup,
		);
	}

	public static function restore( $args = array(), $tool = array() ) {
		$bridge    = LuwiPress_Theme_Bridge::get_instance();
		$entry     = $bridge->load_backup( sanitize_text_field( $args['backup_id'] ?? '' ) );
		if ( ! $entry || $entry['tool_id'] !== 'legacy_canvas_migration' ) {
			return new WP_Error( 'backup_not_found', 'Backup not found.', array( 'status' => 404 ) );
		}
		$payload  = is_array( $entry['payload'] ) ? $entry['payload'] : array();
		$restored = 0;
		foreach ( $payload as $pid => $meta_set ) {
			$pid = (int) $pid;
			if ( ! get_post( $pid ) || ! is_array( $meta_set ) ) { continue; }
			foreach ( $meta_set as $key => $val ) {
				if ( $val === '' || $val === null ) { continue; }
				update_post_meta( $pid, $key, wp_slash( $val ) );
			}
			$restored++;
		}
		return array(
			'restored' => $restored,
			'backup_id'=> sanitize_text_field( $args['backup_id'] ?? '' ),
		);
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// Kit CSS Health — size + headroom + layer markers
// ─────────────────────────────────────────────────────────────────────────────

class LuwiPress_Amber_Kit_CSS_Health_Tool {

	const SOFT_LIMIT_BYTES = 412 * 1024; // observed silent-truncation threshold

	public static function scan( $args = array(), $tool = array() ) {
		$css = (string) get_option( 'luwipress_kit_css', '' );
		$len = strlen( $css );

		preg_match_all( '/\\/\\*\\s*(V\\d+(?:\\.\\d+)?)\\s+BEGIN[^\\*]*\\*\\//i', $css, $begins );
		preg_match_all( '/\\/\\*\\s*end\\s+(V\\d+(?:\\.\\d+)?)\\s*\\*\\//i', $css, $ends );

		$begin_set = array_unique( $begins[1] );
		$end_set   = array_unique( $ends[1] );
		$mismatch  = array_values( array_diff( $begin_set, $end_set ) );

		$candidates = array();
		foreach ( $begin_set as $marker ) {
			$candidates[] = array(
				'id'    => $marker,
				'title' => 'Layer ' . $marker,
				'meta'  => in_array( $marker, $mismatch, true ) ? 'BEGIN without matching end' : 'paired ✓',
			);
		}

		$pct = $len > 0 ? round( ( $len / self::SOFT_LIMIT_BYTES ) * 100, 1 ) : 0;
		return array(
			'candidates' => $candidates,
			'count'      => count( $candidates ),
			'meta'       => array(
				'size_bytes'      => $len,
				'soft_limit'      => self::SOFT_LIMIT_BYTES,
				'pct_of_limit'    => $pct,
				'headroom_bytes'  => max( 0, self::SOFT_LIMIT_BYTES - $len ),
				'layers_total'    => count( $begin_set ),
				'layers_unpaired' => $mismatch,
			),
		);
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// Orphan Media — uploads not referenced anywhere (read-only)
// ─────────────────────────────────────────────────────────────────────────────

class LuwiPress_Amber_Orphan_Media_Tool {

	public static function scan( $args = array(), $tool = array() ) {
		$limit = isset( $args['limit'] ) ? max( 1, min( 200, (int) $args['limit'] ) ) : 50;

		global $wpdb;
		// Attachments without a parent — first-pass orphan signal.
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT ID, post_title, post_mime_type, post_date
			   FROM {$wpdb->posts}
			  WHERE post_type = 'attachment'
			    AND ( post_parent = 0 OR post_parent IS NULL )
			  ORDER BY post_date DESC
			  LIMIT %d",
			$limit * 4
		) );

		$candidates = array();
		foreach ( (array) $rows as $row ) {
			if ( count( $candidates ) >= $limit ) {
				break;
			}
			$id   = (int) $row->ID;
			$file = wp_get_attachment_url( $id );
			if ( ! $file ) {
				continue;
			}
			// Look for any post_content that mentions the file's basename.
			$basename = wp_basename( wp_parse_url( $file, PHP_URL_PATH ) ?: $file );
			$found    = $wpdb->get_var( $wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts}
				  WHERE post_status IN ('publish','draft','pending','private')
				    AND post_type NOT IN ('attachment','revision')
				    AND post_content LIKE %s
				  LIMIT 1",
				'%' . $wpdb->esc_like( $basename ) . '%'
			) );
			if ( $found ) {
				continue;
			}
			$candidates[] = array(
				'id'    => $id,
				'title' => $row->post_title ?: $basename,
				'meta'  => sprintf( '%s · %s', $row->post_mime_type, mysql2date( 'Y-m-d', $row->post_date ) ),
			);
		}

		return array(
			'candidates' => $candidates,
			'count'      => count( $candidates ),
			'meta'       => array(
				'note' => 'Read-only scan — does not delete. Cross-check before manually deleting from Media library.',
			),
		);
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// WPML / Polylang Structural Sync — audit cross-language drift across menus,
// menu-location bindings, and theme_mod values that legitimately differ per
// language. Read-only by default; surfaces drift the operator can resolve in
// the appropriate UI (WPML String Translation, Appearance → Menus, Customizer).
// ─────────────────────────────────────────────────────────────────────────────

class LuwiPress_Amber_WPML_Structure_Sync_Tool {

	/**
	 * theme_mods that, IF set per-language, are most likely to drift and cause
	 * visible bugs (e.g. mega-menu ID pointing at the EN menu in IT context).
	 * Generic — discovered live from the bridge's settings registry.
	 */
	private static function tracked_mod_keys() {
		$keys = array();
		if ( class_exists( 'LuwiPress_Theme_Bridge' ) ) {
			foreach ( LuwiPress_Theme_Bridge::get_instance()->get_settings() as $s ) {
				if ( ! empty( $s['theme_mod'] ) ) {
					$keys[] = $s['theme_mod'];
				}
			}
		}
		// Always include the menu-location map; that's the most common bug surface.
		$keys[] = 'nav_menu_locations';
		return array_values( array_unique( $keys ) );
	}

	public static function scan( $args = array(), $tool = array() ) {
		$languages = self::active_languages();
		if ( count( $languages ) <= 1 ) {
			return array(
				'candidates' => array(),
				'count'      => 0,
				'meta'       => array(
					'multilingual' => false,
					'note'         => 'No multilingual plugin (WPML/Polylang) detected, or only one language active. Structural sync is a no-op.',
				),
			);
		}

		$candidates = array();

		// 1) Menu inventory + location parity ────────────────────────────────
		$menus = self::menus_per_language( $languages );
		$locations_drift = self::location_drift( $languages );
		foreach ( $locations_drift as $row ) {
			$candidates[] = array(
				'id'    => 'loc:' . $row['location'],
				'title' => sprintf( 'Menu location "%s"', $row['location'] ),
				'meta'  => $row['summary'],
			);
		}

		// 2) Menu structural drift (item count, depth, item titles) ──────────
		$menu_drift = self::menu_structure_drift( $menus, $languages );
		foreach ( $menu_drift as $row ) {
			$candidates[] = array(
				'id'    => 'menu:' . $row['source_term_id'],
				'title' => sprintf( 'Menu "%s"', $row['source_name'] ),
				'meta'  => $row['summary'],
			);
		}

		// 3) Translatable theme_mod drift (only when WPML String Translation
		//    is configured to translate theme_mods, or Polylang per-language
		//    overrides exist) ─────────────────────────────────────────────────
		$mod_drift = self::theme_mod_drift( $languages );
		foreach ( $mod_drift as $row ) {
			$candidates[] = array(
				'id'    => 'mod:' . $row['key'],
				'title' => sprintf( 'theme_mod "%s"', $row['key'] ),
				'meta'  => $row['summary'],
			);
		}

		return array(
			'candidates' => $candidates,
			'count'      => count( $candidates ),
			'meta'       => array(
				'multilingual'      => true,
				'languages'         => $languages,
				'menus_per_lang'    => array_map( function ( $m ) { return count( $m ); }, $menus ),
				'locations_checked' => array_keys( get_registered_nav_menus() ),
				'mods_checked'      => self::tracked_mod_keys(),
				'note'              => 'Read-only audit. Menu drift = fix in Appearance → Menus per language. Location drift = fix in WPML → Languages → Menus / Polylang Settings. Mod drift = WPML String Translation or Customizer per language.',
			),
		);
	}

	/* ── helpers ─────────────────────────────────────────────────────────── */

	private static function active_languages() {
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$langs = apply_filters( 'wpml_active_languages', null );
			if ( is_array( $langs ) ) {
				return array_keys( $langs );
			}
		}
		if ( function_exists( 'pll_languages_list' ) ) {
			$langs = pll_languages_list();
			if ( is_array( $langs ) ) {
				return $langs;
			}
		}
		return array();
	}

	/**
	 * Return menus visible in each language as [ lang => [ term_id => name ] ].
	 * Polylang ties menus to languages via term meta; WPML stores them via
	 * `icl_translations` element_type='nav_menu'.
	 */
	private static function menus_per_language( $languages ) {
		$out = array();
		foreach ( $languages as $lang ) {
			$out[ $lang ] = array();
		}

		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			global $wpdb;
			$rows = $wpdb->get_results(
				"SELECT t.element_id, t.language_code, terms.name
				   FROM {$wpdb->prefix}icl_translations t
				   JOIN {$wpdb->terms} terms ON terms.term_id = t.element_id
				  WHERE t.element_type = 'tax_nav_menu'"
			);
			foreach ( (array) $rows as $r ) {
				if ( isset( $out[ $r->language_code ] ) ) {
					$out[ $r->language_code ][ (int) $r->element_id ] = $r->name;
				}
			}
		} elseif ( function_exists( 'pll_languages_list' ) ) {
			$menus = wp_get_nav_menus();
			foreach ( (array) $menus as $menu ) {
				$lang = function_exists( 'pll_get_term_language' ) ? pll_get_term_language( $menu->term_id ) : '';
				if ( $lang && isset( $out[ $lang ] ) ) {
					$out[ $lang ][ $menu->term_id ] = $menu->name;
				}
			}
		} else {
			// No multilingual plugin — single language gets everything.
			$first = reset( $languages );
			$menus = wp_get_nav_menus();
			foreach ( (array) $menus as $menu ) {
				$out[ $first ][ $menu->term_id ] = $menu->name;
			}
		}

		return $out;
	}

	/**
	 * Compare nav_menu_locations across languages — does each registered
	 * location have a menu assigned in every active language?
	 */
	private static function location_drift( $languages ) {
		$registered = array_keys( get_registered_nav_menus() );
		$drift = array();
		if ( empty( $registered ) ) {
			return $drift;
		}

		// For WPML the nav_menu_locations map is shared but assigned menu IDs
		// translate via icl_object_id — so we re-resolve each location per lang.
		foreach ( $registered as $loc ) {
			$per_lang = array();
			foreach ( $languages as $lang ) {
				$resolved = self::resolve_location_in_language( $loc, $lang );
				$per_lang[ $lang ] = $resolved;
			}
			$assigned = array_filter( $per_lang );
			if ( count( $assigned ) === count( $languages ) ) {
				continue; // every language has a menu — no drift
			}
			$missing = array_diff_key( $per_lang, $assigned );
			$drift[] = array(
				'location' => $loc,
				'summary'  => sprintf(
					'Missing menu in: %s · Assigned in: %s',
					implode( ',', array_keys( $missing ) ),
					implode( ',', array_keys( $assigned ) )
				),
			);
		}
		return $drift;
	}

	private static function resolve_location_in_language( $location, $language ) {
		$locations = get_nav_menu_locations();
		$menu_id   = $locations[ $location ] ?? 0;
		if ( ! $menu_id ) {
			return 0;
		}
		// WPML translates the menu term to the target language.
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$translated = apply_filters( 'wpml_object_id', (int) $menu_id, 'nav_menu', false, $language );
			if ( $translated ) {
				return (int) $translated;
			}
			return 0;
		}
		// Polylang stores per-language nav_menu_locations under `polylang` option.
		if ( function_exists( 'pll_languages_list' ) ) {
			$pll_options = get_option( 'polylang', array() );
			$nav_locs    = $pll_options['nav_menus'] ?? array();
			$by_theme    = $nav_locs[ get_stylesheet() ] ?? array();
			$by_loc      = $by_theme[ $location ] ?? array();
			return isset( $by_loc[ $language ] ) ? (int) $by_loc[ $language ] : 0;
		}
		return (int) $menu_id;
	}

	/**
	 * For each "primary" language menu, walk its translations and report when
	 * sibling menus differ in item count or structural depth.
	 */
	private static function menu_structure_drift( $menus_per_lang, $languages ) {
		$primary = self::default_language( $languages );
		if ( empty( $menus_per_lang[ $primary ] ) ) {
			return array();
		}

		$drift = array();
		foreach ( $menus_per_lang[ $primary ] as $term_id => $name ) {
			$source_items = (array) wp_get_nav_menu_items( $term_id );
			$source_count = count( $source_items );
			$source_depth = self::menu_max_depth( $source_items );

			$gaps = array();
			foreach ( $languages as $lang ) {
				if ( $lang === $primary ) {
					continue;
				}
				$sib_id = self::translate_menu_term( $term_id, $lang );
				if ( ! $sib_id ) {
					$gaps[] = sprintf( '%s: no translation', $lang );
					continue;
				}
				$sib_items = (array) wp_get_nav_menu_items( $sib_id );
				$sib_count = count( $sib_items );
				$sib_depth = self::menu_max_depth( $sib_items );
				if ( $sib_count !== $source_count || $sib_depth !== $source_depth ) {
					$gaps[] = sprintf( '%s: %d items / depth %d', $lang, $sib_count, $sib_depth );
				}
			}
			if ( $gaps ) {
				$drift[] = array(
					'source_term_id' => $term_id,
					'source_name'    => $name,
					'summary'        => sprintf(
						'%s reference: %d items / depth %d → %s',
						strtoupper( $primary ),
						$source_count,
						$source_depth,
						implode( ' · ', $gaps )
					),
				);
			}
		}
		return $drift;
	}

	private static function menu_max_depth( $items ) {
		$by_id = array();
		foreach ( (array) $items as $i ) {
			$by_id[ (int) $i->ID ] = (int) ( $i->menu_item_parent ?? 0 );
		}
		$max = 0;
		foreach ( $by_id as $id => $parent ) {
			$d = 0;
			$cur = $parent;
			while ( $cur && isset( $by_id[ $cur ] ) && $d < 10 ) {
				$d++;
				$cur = $by_id[ $cur ];
			}
			$max = max( $max, $d );
		}
		return $max;
	}

	private static function translate_menu_term( $term_id, $language ) {
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$resolved = apply_filters( 'wpml_object_id', (int) $term_id, 'nav_menu', false, $language );
			return (int) $resolved;
		}
		if ( function_exists( 'pll_get_term' ) ) {
			$resolved = pll_get_term( (int) $term_id, $language );
			return (int) $resolved;
		}
		return 0;
	}

	private static function default_language( $languages ) {
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$default = apply_filters( 'wpml_default_language', null );
			if ( $default ) {
				return $default;
			}
		}
		if ( function_exists( 'pll_default_language' ) ) {
			$default = pll_default_language();
			if ( $default ) {
				return $default;
			}
		}
		return reset( $languages );
	}

	/**
	 * Drift in tracked theme_mods is rare (theme_mods are global) but possible
	 * via WPML String Translation or Polylang per-language overrides. Surface
	 * any value that resolves differently per language so the operator can
	 * verify it's intentional.
	 */
	private static function theme_mod_drift( $languages ) {
		$keys  = self::tracked_mod_keys();
		$drift = array();
		foreach ( $keys as $key ) {
			$values = array();
			foreach ( $languages as $lang ) {
				$values[ $lang ] = self::resolve_mod_in_language( $key, $lang );
			}
			$unique = array_unique( array_map( 'wp_json_encode', $values ) );
			if ( count( $unique ) <= 1 ) {
				continue; // single value across languages — no drift
			}
			$drift[] = array(
				'key'     => $key,
				'summary' => sprintf( '%d distinct values across languages: %s', count( $unique ), wp_json_encode( $values ) ),
			);
		}
		return $drift;
	}

	private static function resolve_mod_in_language( $key, $language ) {
		// WPML lets sites translate theme_mods via String Translation; the
		// canonical filter is `wpml_translate_single_string` for strings.
		// For non-string mods we fall through to the global value.
		$value = get_theme_mod( $key );
		if ( is_string( $value ) && defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$translated = apply_filters( 'wpml_translate_single_string', $value, 'theme ' . get_stylesheet(), $key, $language );
			if ( is_string( $translated ) && $translated !== '' ) {
				return $translated;
			}
		}
		return $value;
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// Unwanted Landing Pages — long keyword-stuffed slugs (legacy SEO leftovers)
// that render near-empty AND aren't part of the navigation. Common after
// migrations from Hello Elementor sites that imported old marketing pages
// without their visual builder data. Mutating: trash the page (with backup)
// or delete the Elementor shell only — operator's choice via args.
// ─────────────────────────────────────────────────────────────────────────────

class LuwiPress_Amber_Unwanted_Landing_Pages_Tool {

	const SLUG_LEN_THRESHOLD     = 35;   // typical nav slugs are <= 25 chars
	const SLUG_TOKEN_THRESHOLD   = 5;    // 5+ hyphenated tokens looks like SEO
	const MIN_KW_HITS            = 2;    // commercial/SEO keyword hits
	const NEAR_EMPTY_BYTES       = 800;  // post_content + Elementor combined

	private static function commercial_keywords() {
		return apply_filters( 'luwipress_amber_unwanted_landing_keywords', array(
			// EN
			'buy', 'purchase', 'order', 'shop', 'cheap', 'best', 'discount', 'sale', 'store',
			'guide', 'tips', 'choose', 'beginner', 'professional',
			// IT
			'acquista', 'compra', 'acquisto', 'sconto', 'negozio', 'comprare', 'migliore',
			// FR
			'acheter', 'achat', 'meilleur', 'promo', 'magasin', 'choisir',
			// ES
			'comprar', 'compra', 'mejor', 'tienda', 'rebaja', 'descuento',
		) );
	}

	public static function scan( $args = array(), $tool = array() ) {
		$min_slug_tokens = isset( $args['min_slug_tokens'] ) ? max( 3, (int) $args['min_slug_tokens'] ) : self::SLUG_TOKEN_THRESHOLD;
		$max_body        = isset( $args['max_body_bytes'] ) ? max( 100, (int) $args['max_body_bytes'] ) : self::NEAR_EMPTY_BYTES;

		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT ID, post_title, post_name, post_content, post_status, post_type
			   FROM {$wpdb->posts}
			  WHERE post_status IN ('publish','draft')
			    AND post_type IN ('page','post')
			  ORDER BY ID DESC
			  LIMIT 500"
		);

		$nav_post_ids = self::collect_nav_post_ids();
		$keywords     = self::commercial_keywords();

		$candidates = array();
		foreach ( (array) $rows as $row ) {
			$slug = (string) $row->post_name;
			if ( $slug === '' ) {
				continue;
			}
			$tokens = preg_split( '/-+/', $slug );
			if ( count( $tokens ) < $min_slug_tokens ) {
				continue;
			}
			if ( strlen( $slug ) < self::SLUG_LEN_THRESHOLD ) {
				continue;
			}

			// Commercial keyword density — at least N hits in the slug.
			$hits = 0;
			foreach ( $tokens as $tk ) {
				if ( in_array( strtolower( $tk ), $keywords, true ) ) {
					$hits++;
				}
			}
			if ( $hits < self::MIN_KW_HITS ) {
				continue;
			}

			// Body is near-empty — combined post_content + _elementor_data.
			$body_len = strlen( trim( wp_strip_all_tags( strip_shortcodes( (string) $row->post_content ) ) ) );
			$ele      = get_post_meta( $row->ID, '_elementor_data', true );
			$ele_len  = is_string( $ele ) ? strlen( $ele ) : 0;
			if ( $body_len + $ele_len > $max_body ) {
				continue;
			}

			$in_nav = in_array( (int) $row->ID, $nav_post_ids, true );
			if ( $in_nav ) {
				continue; // it's in nav, treat as legitimate even if thin
			}

			$candidates[] = array(
				'id'         => (int) $row->ID,
				'title'      => $row->post_title,
				'slug'       => $slug,
				'token_count'=> count( $tokens ),
				'kw_hits'    => $hits,
				'body_size'  => $body_len + $ele_len,
				'in_nav'     => false,
				'meta'       => sprintf(
					'%d tokens · %d kw · %dB body · NOT in nav',
					count( $tokens ),
					$hits,
					$body_len + $ele_len
				),
				'edit_link'  => get_edit_post_link( (int) $row->ID, '' ),
				'permalink'  => get_permalink( (int) $row->ID ),
			);
		}

		return array(
			'candidates' => $candidates,
			'count'      => count( $candidates ),
			'criteria'   => array(
				'min_slug_tokens'    => $min_slug_tokens,
				'min_slug_length'    => self::SLUG_LEN_THRESHOLD,
				'min_keyword_hits'   => self::MIN_KW_HITS,
				'max_body_bytes'     => $max_body,
				'must_be_orphan'     => 'not in any nav menu',
			),
		);
	}

	public static function execute( $args = array(), $tool = array() ) {
		$ids = ! empty( $args['_expanded_post_ids'] )
			? array_map( 'intval', (array) $args['_expanded_post_ids'] )
			: array_map( 'intval', (array) ( $args['post_ids'] ?? array() ) );
		$ids = array_values( array_unique( array_filter( $ids ) ) );
		if ( empty( $ids ) ) {
			return new WP_Error( 'no_post_ids', 'Provide at least one post_id.', array( 'status' => 400 ) );
		}

		// Default mode = trash (recoverable for 30 days). Mode=delete still
		// goes through wp_delete_post which is reversible only via restore().
		$mode = isset( $args['mode'] ) && $args['mode'] === 'delete' ? 'delete' : 'trash';

		$backup_payload = array();
		$mutated        = 0;

		foreach ( $ids as $pid ) {
			$post = get_post( $pid );
			if ( ! $post ) {
				continue;
			}
			// Capture full post object + every meta entry — verbose but cheap.
			$backup_payload[ $pid ] = array(
				'post'     => (array) $post,
				'postmeta' => get_post_meta( $pid ),
				'mode'     => $mode,
			);
			if ( $mode === 'delete' ) {
				wp_delete_post( $pid, true );
			} else {
				wp_trash_post( $pid );
			}
			$mutated++;
		}

		return array(
			'mutated'         => $mutated,
			'mode'            => $mode,
			'post_ids'        => $ids,
			'_backup_payload' => $backup_payload,
		);
	}

	public static function restore( $args = array(), $tool = array() ) {
		$bridge    = LuwiPress_Theme_Bridge::get_instance();
		$backup_id = sanitize_text_field( $args['backup_id'] ?? '' );
		$entry     = $bridge->load_backup( $backup_id );
		if ( ! $entry || $entry['tool_id'] !== 'unwanted_landing_pages' ) {
			return new WP_Error( 'backup_not_found', 'Backup not found.', array( 'status' => 404 ) );
		}
		$payload = is_array( $entry['payload'] ) ? $entry['payload'] : array();
		$restored = 0;

		foreach ( $payload as $pid => $snap ) {
			$pid = (int) $pid;
			// Trash mode = wp_untrash; delete mode = recreate from snapshot.
			if ( ( $snap['mode'] ?? 'trash' ) === 'trash' ) {
				if ( get_post( $pid ) ) {
					wp_untrash_post( $pid );
					$restored++;
				}
				continue;
			}
			if ( ! is_array( $snap['post'] ?? null ) ) {
				continue;
			}
			// Hard-delete recovery — re-insert with original ID via $wpdb to
			// preserve permalinks. wp_insert_post would mint a new ID.
			global $wpdb;
			$post_data = $snap['post'];
			$wpdb->insert( $wpdb->posts, $post_data );
			if ( ! empty( $snap['postmeta'] ) && is_array( $snap['postmeta'] ) ) {
				foreach ( $snap['postmeta'] as $mkey => $mvals ) {
					foreach ( (array) $mvals as $mv ) {
						update_post_meta( $pid, $mkey, maybe_unserialize( $mv ) );
					}
				}
			}
			$restored++;
		}

		return array(
			'restored' => $restored,
			'backup_id'=> $backup_id,
		);
	}

	private static function collect_nav_post_ids() {
		$out = array();
		$menus = wp_get_nav_menus();
		foreach ( (array) $menus as $menu ) {
			$items = wp_get_nav_menu_items( $menu->term_id );
			if ( ! is_array( $items ) ) {
				continue;
			}
			foreach ( $items as $item ) {
				if ( $item->object === 'page' || $item->object === 'post' ) {
					$out[] = (int) $item->object_id;
				}
			}
		}
		return array_values( array_unique( $out ) );
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// Subcategory Template Parity — every product_cat term, in every active
// language, must resolve to a real archive URL that returns 200 (not 404).
// Common failure: WPML translates the term but the slug or parent linkage is
// broken, so the visitor lands on a 404 — exactly the screenshot the operator
// sent (drawer shows "Kemence Classico" but /it/.../kemence-classico/ → 404).
// ─────────────────────────────────────────────────────────────────────────────

class LuwiPress_Amber_Subcategory_Template_Parity_Tool {

	public static function scan( $args = array(), $tool = array() ) {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return array(
				'candidates' => array(),
				'count'      => 0,
				'meta'       => array( 'wc_active' => false ),
			);
		}

		$languages = self::active_languages();
		if ( empty( $languages ) ) {
			$languages = array( 'default' );
		}

		$terms = get_terms( array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'fields'     => 'ids',
		) );
		if ( is_wp_error( $terms ) ) {
			return new WP_Error( 'term_query_failed', $terms->get_error_message() );
		}

		$candidates = array();
		foreach ( $terms as $tid ) {
			$tid = (int) $tid;
			$src_term = get_term( $tid, 'product_cat' );
			if ( ! $src_term || is_wp_error( $src_term ) ) {
				continue;
			}

			$gaps = array();
			foreach ( $languages as $lang ) {
				$resolved = self::resolve_term_in_language( $tid, $lang );
				if ( ! $resolved ) {
					$gaps[] = $lang . ': no translation';
					continue;
				}
				$link = get_term_link( (int) $resolved, 'product_cat' );
				if ( is_wp_error( $link ) || ! $link ) {
					$gaps[] = $lang . ': no link';
					continue;
				}
				$status = self::head_check( $link );
				if ( $status === 404 ) {
					$gaps[] = sprintf( '%s: 404 %s', $lang, $link );
				} elseif ( $status >= 500 ) {
					$gaps[] = sprintf( '%s: %d %s', $lang, $status, $link );
				}
			}

			if ( $gaps ) {
				$candidates[] = array(
					'id'    => $tid,
					'title' => $src_term->name,
					'meta'  => sprintf( 'parent=%d · slug=%s · gaps: %s', $src_term->parent, $src_term->slug, implode( ' · ', $gaps ) ),
				);
			}
		}

		return array(
			'candidates' => $candidates,
			'count'      => count( $candidates ),
			'meta'       => array(
				'languages'   => $languages,
				'term_count'  => count( $terms ),
				'note'        => 'Read-only HEAD-check audit. Each non-200 needs WPML term re-translation (Settings → WPML → Taxonomy Translation) or term re-creation in the target language.',
			),
		);
	}

	private static function active_languages() {
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$langs = apply_filters( 'wpml_active_languages', null );
			if ( is_array( $langs ) ) {
				return array_keys( $langs );
			}
		}
		if ( function_exists( 'pll_languages_list' ) ) {
			return (array) pll_languages_list();
		}
		return array();
	}

	private static function resolve_term_in_language( $term_id, $language ) {
		if ( $language === 'default' ) {
			return $term_id;
		}
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$translated = apply_filters( 'wpml_object_id', (int) $term_id, 'product_cat', false, $language );
			return $translated ? (int) $translated : 0;
		}
		if ( function_exists( 'pll_get_term' ) ) {
			$translated = pll_get_term( (int) $term_id, $language );
			return $translated ? (int) $translated : 0;
		}
		return $term_id;
	}

	private static function head_check( $url ) {
		$res = wp_remote_head( $url, array(
			'timeout'     => 5,
			'redirection' => 1,
			'sslverify'   => false,
			'user-agent'  => 'LuwiPressGold-SubcatParity/1.0',
		) );
		if ( is_wp_error( $res ) ) {
			return 0;
		}
		return (int) wp_remote_retrieve_response_code( $res );
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// Elementor + WC + WPML Triangle Health — consolidated audit ensuring the
// three pillars play well together. Read-only; surfaces every config gap that
// makes the storefront render incorrectly across languages.
// ─────────────────────────────────────────────────────────────────────────────

class LuwiPress_Amber_Triangle_Health_Tool {

	public static function scan( $args = array(), $tool = array() ) {
		$findings = array();

		// — Foundation checks ────────────────────────────────────────────────
		$wc       = class_exists( 'WooCommerce' );
		$elem     = did_action( 'elementor/loaded' ) > 0;
		$wpml     = defined( 'ICL_SITEPRESS_VERSION' );
		$polylang = function_exists( 'pll_languages_list' );

		if ( ! $wc ) {
			$findings[] = self::row( 'wc_missing', 'WooCommerce not active', 'critical', 'Triangle audit requires WooCommerce.' );
		}
		if ( ! $elem ) {
			$findings[] = self::row( 'elem_missing', 'Elementor not active', 'critical', 'Triangle audit requires Elementor.' );
		}
		if ( ! $wpml && ! $polylang ) {
			$findings[] = self::row( 'i18n_missing', 'No multilingual plugin (WPML/Polylang)', 'info', 'Triangle audit reduces to single-language WC+Elementor checks.' );
		}

		// — WC core pages translated? ────────────────────────────────────────
		if ( $wc ) {
			$wc_page_keys = array( 'shop', 'cart', 'checkout', 'myaccount', 'terms' );
			foreach ( $wc_page_keys as $key ) {
				$pid = wc_get_page_id( $key );
				if ( $pid <= 0 ) {
					$findings[] = self::row( 'wc_page_missing_' . $key, sprintf( 'WC %s page not configured', $key ), 'warning', 'Set under WooCommerce → Settings → Advanced → Page setup.' );
					continue;
				}
				if ( $wpml || $polylang ) {
					$missing = self::missing_translations_for_post( $pid );
					if ( $missing ) {
						$findings[] = self::row(
							'wc_page_untranslated_' . $key,
							sprintf( 'WC %s page missing translations', $key ),
							'warning',
							sprintf( 'Source post #%d not translated into: %s', $pid, implode( ',', $missing ) )
						);
					}
				}
			}
		}

		// — Elementor PDP / Archive templates registered? ─────────────────────
		if ( $elem && $wc ) {
			$pdp_template     = self::find_theme_builder_template( 'product' );
			$archive_template = self::find_theme_builder_template( 'product-archive' );
			if ( ! $pdp_template ) {
				$findings[] = self::row( 'pdp_template_missing', 'Elementor Single Product template not assigned', 'warning', 'Elementor → Templates → Theme Builder → Single Product. Without it WooCommerce falls back to the theme single-product.php.' );
			}
			if ( ! $archive_template ) {
				$findings[] = self::row( 'archive_template_missing', 'Elementor Product Archive template not assigned', 'warning', 'Elementor → Templates → Theme Builder → Products Archive.' );
			}
		}

		// — Cross-language menu coverage (delegate to existing audit) ────────
		if ( ( $wpml || $polylang ) && class_exists( 'LuwiPress_Amber_WPML_Structure_Sync_Tool' ) ) {
			$wpml_audit = LuwiPress_Amber_WPML_Structure_Sync_Tool::scan( array(), array() );
			if ( ! empty( $wpml_audit['count'] ) ) {
				$findings[] = self::row(
					'menu_drift_present',
					sprintf( '%d menu/location/mod drift entries', (int) $wpml_audit['count'] ),
					'warning',
					'Run the "WPML Structural Sync Audit" tool for the full breakdown.'
				);
			}
		}

		// — Subcategory parity (delegate) ────────────────────────────────────
		if ( $wc && ( $wpml || $polylang ) && class_exists( 'LuwiPress_Amber_Subcategory_Template_Parity_Tool' ) ) {
			$subcat = LuwiPress_Amber_Subcategory_Template_Parity_Tool::scan( array(), array() );
			if ( ! empty( $subcat['count'] ) ) {
				$findings[] = self::row(
					'subcategory_404',
					sprintf( '%d product_cat terms have non-200 archives in some languages', (int) $subcat['count'] ),
					'critical',
					'Run the "Subcategory Template Parity" tool — typically WPML term-translation gap.'
				);
			}
		}

		// — Orphan SEO landings ─────────────────────────────────────────────
		if ( class_exists( 'LuwiPress_Amber_Unwanted_Landing_Pages_Tool' ) ) {
			$orphan = LuwiPress_Amber_Unwanted_Landing_Pages_Tool::scan( array(), array() );
			if ( ! empty( $orphan['count'] ) ) {
				$findings[] = self::row(
					'orphan_landings',
					sprintf( '%d orphan SEO landing pages with empty bodies', (int) $orphan['count'] ),
					'warning',
					'Run "Unwanted Landing Pages" tool — review then trash.'
				);
			}
		}

		return array(
			'candidates' => $findings,
			'count'      => count( $findings ),
			'meta'       => array(
				'foundation' => array(
					'wc'       => $wc,
					'elementor'=> $elem,
					'wpml'     => $wpml,
					'polylang' => $polylang,
				),
				'note'       => 'Consolidated triangle audit. Each finding links to the dedicated tool that resolves it.',
			),
		);
	}

	private static function row( $id, $title, $severity, $detail ) {
		return array(
			'id'    => $id,
			'title' => $title,
			'meta'  => '[' . strtoupper( $severity ) . '] ' . $detail,
		);
	}

	private static function missing_translations_for_post( $pid ) {
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$active = apply_filters( 'wpml_active_languages', null );
			if ( ! is_array( $active ) ) {
				return array();
			}
			$missing = array();
			foreach ( array_keys( $active ) as $lang ) {
				$translated = apply_filters( 'wpml_object_id', (int) $pid, get_post_type( $pid ), false, $lang );
				if ( ! $translated ) {
					$missing[] = $lang;
				}
			}
			return $missing;
		}
		if ( function_exists( 'pll_get_post_translations' ) && function_exists( 'pll_languages_list' ) ) {
			$tr      = pll_get_post_translations( (int) $pid );
			$active  = pll_languages_list();
			$missing = array();
			foreach ( (array) $active as $lang ) {
				if ( empty( $tr[ $lang ] ) ) {
					$missing[] = $lang;
				}
			}
			return $missing;
		}
		return array();
	}

	private static function find_theme_builder_template( $type ) {
		if ( ! class_exists( '\\Elementor\\Plugin' ) || ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			return 0;
		}
		// Elementor Pro stores conditions in `_elementor_conditions` post meta.
		// Fast path: query for posts of CPT `elementor_library` whose conditions
		// reference the requested type.
		$posts = get_posts( array(
			'post_type'      => 'elementor_library',
			'posts_per_page' => 20,
			'meta_query'     => array(
				array(
					'key'     => '_elementor_conditions',
					'value'   => $type,
					'compare' => 'LIKE',
				),
			),
		) );
		return $posts ? (int) $posts[0]->ID : 0;
	}
}


