<?php
/**
 * Fix-tier maintenance tools — auto-repair the most common WPML/WC/Elementor
 * triangle gaps surfaced by the audit tools.
 *
 *   - LuwiPress_Sapphire_WPML_Term_Repair_Tool          register missing translated terms
 *   - LuwiPress_Sapphire_Menu_Propagate_Tool            sync new menu items to sibling menus
 *   - LuwiPress_Sapphire_Product_Translation_Tool       find products missing in some languages
 *   - LuwiPress_Sapphire_Template_Assignment_Tool       audit + apply forced PDP/archive template ids
 *
 * Each tool that mutates registers backup payloads via the bridge so restore
 * is one click away.
 *
 * @package luwipress-sapphire
 * @since   1.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// WPML Term Repair — register missing product_cat translations so the
// translated archive URL stops 404'ing. Fixes the screenshot bug where the
// drawer shows /it/.../kemence-classico/ but the URL is unresolved.
// ─────────────────────────────────────────────────────────────────────────────

class LuwiPress_Sapphire_WPML_Term_Repair_Tool {

	public static function scan( $args = array(), $tool = array() ) {
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) || ! taxonomy_exists( 'product_cat' ) ) {
			return array(
				'candidates' => array(),
				'count'      => 0,
				'meta'       => array( 'wpml_active' => defined( 'ICL_SITEPRESS_VERSION' ), 'wc_active' => taxonomy_exists( 'product_cat' ) ),
			);
		}

		$active = apply_filters( 'wpml_active_languages', null );
		if ( ! is_array( $active ) ) {
			return array( 'candidates' => array(), 'count' => 0 );
		}
		$default = apply_filters( 'wpml_default_language', null ) ?: 'en';

		$source_terms = get_terms( array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'fields'     => 'ids',
		) );
		if ( is_wp_error( $source_terms ) ) {
			return array( 'candidates' => array(), 'count' => 0 );
		}

		$candidates = array();
		foreach ( $source_terms as $tid ) {
			$tid = (int) $tid;
			$src_lang = apply_filters( 'wpml_element_language_code', null, array( 'element_id' => $tid, 'element_type' => 'product_cat' ) );
			if ( $src_lang !== $default ) {
				continue; // only scan default-language terms as repair source
			}
			$src = get_term( $tid, 'product_cat' );
			if ( ! $src || is_wp_error( $src ) ) {
				continue;
			}
			$missing = array();
			foreach ( array_keys( $active ) as $lang ) {
				if ( $lang === $default ) {
					continue;
				}
				$translated = apply_filters( 'wpml_object_id', $tid, 'product_cat', false, $lang );
				if ( ! $translated ) {
					$missing[] = $lang;
				}
			}
			if ( $missing ) {
				$candidates[] = array(
					'id'    => $tid,
					'title' => $src->name,
					'meta'  => sprintf( 'parent=%d · slug=%s · missing translations: %s', $src->parent, $src->slug, implode( ',', $missing ) ),
					'_data' => array(
						'name'    => $src->name,
						'slug'    => $src->slug,
						'parent'  => (int) $src->parent,
						'missing' => $missing,
					),
				);
			}
		}

		return array(
			'candidates' => $candidates,
			'count'      => count( $candidates ),
			'meta'       => array(
				'default_lang' => $default,
				'languages'    => array_keys( $active ),
				'note'         => 'Execute creates a placeholder translated term (same slug + language code suffix) and registers it via wpml_admin_make_post_duplicates_action / wpml_set_element_language_details so the translated archive URL resolves. Operator can rename the translated term afterwards in WPML → Taxonomy Translation.',
			),
		);
	}

	public static function execute( $args = array(), $tool = array() ) {
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
			return new WP_Error( 'wpml_required', 'WPML required for this tool.', array( 'status' => 412 ) );
		}
		$ids = array_map( 'intval', (array) ( $args['post_ids'] ?? array() ) );
		$ids = array_filter( $ids );
		if ( ! $ids ) {
			return new WP_Error( 'no_ids', 'Provide source product_cat term IDs.', array( 'status' => 400 ) );
		}

		$active  = apply_filters( 'wpml_active_languages', null );
		$default = apply_filters( 'wpml_default_language', null ) ?: 'en';
		$mutated = 0;
		$created = array();
		$backup  = array();

		foreach ( $ids as $tid ) {
			$src = get_term( $tid, 'product_cat' );
			if ( ! $src || is_wp_error( $src ) ) {
				continue;
			}
			$src_trid = apply_filters( 'wpml_element_trid', null, $tid, 'tax_product_cat' );

			foreach ( array_keys( (array) $active ) as $lang ) {
				if ( $lang === $default ) {
					continue;
				}
				$translated = apply_filters( 'wpml_object_id', $tid, 'product_cat', false, $lang );
				if ( $translated ) {
					continue;
				}
				$new_slug = $src->slug . '-' . $lang;
				$new_term = wp_insert_term( $src->name, 'product_cat', array(
					'slug'   => $new_slug,
					'parent' => self::translated_parent( $src->parent, $lang ),
				) );
				if ( is_wp_error( $new_term ) ) {
					continue;
				}
				do_action( 'wpml_set_element_language_details', array(
					'element_id'           => (int) $new_term['term_taxonomy_id'],
					'element_type'         => 'tax_product_cat',
					'trid'                 => $src_trid,
					'language_code'        => $lang,
					'source_language_code' => $default,
				) );
				$created[] = array(
					'source_term_id' => $tid,
					'new_term_id'    => (int) $new_term['term_id'],
					'lang'           => $lang,
					'slug'           => $new_slug,
				);
				$backup[ (int) $new_term['term_id'] ] = array( 'lang' => $lang, 'source' => $tid );
				$mutated++;
			}
		}

		return array(
			'mutated'         => $mutated,
			'created'         => $created,
			'_backup_payload' => $backup,
		);
	}

	public static function restore( $args = array(), $tool = array() ) {
		$bridge    = LuwiPress_Theme_Bridge::get_instance();
		$entry     = $bridge->load_backup( sanitize_text_field( $args['backup_id'] ?? '' ) );
		if ( ! $entry || $entry['tool_id'] !== 'wpml_term_repair' ) {
			return new WP_Error( 'backup_not_found', 'Backup not found.', array( 'status' => 404 ) );
		}
		$payload  = is_array( $entry['payload'] ) ? $entry['payload'] : array();
		$restored = 0;
		foreach ( $payload as $new_term_id => $info ) {
			$term = get_term( (int) $new_term_id, 'product_cat' );
			if ( ! $term || is_wp_error( $term ) ) {
				continue;
			}
			$res = wp_delete_term( (int) $new_term_id, 'product_cat' );
			if ( ! is_wp_error( $res ) ) {
				$restored++;
			}
		}
		return array( 'restored' => $restored );
	}

	private static function translated_parent( $parent_id, $lang ) {
		if ( ! $parent_id ) {
			return 0;
		}
		$resolved = apply_filters( 'wpml_object_id', (int) $parent_id, 'product_cat', false, $lang );
		return $resolved ? (int) $resolved : 0;
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// Menu Translation Propagate — for each source-language menu, walk to its
// translation siblings and append items that exist on source but not on the
// sibling. Conservative: appends at depth 0 only (deeper hierarchy needs the
// operator's eye). Backed up so restore drops the propagated items.
// ─────────────────────────────────────────────────────────────────────────────

class LuwiPress_Sapphire_Menu_Propagate_Tool {

	public static function scan( $args = array(), $tool = array() ) {
		$languages = self::active_languages();
		if ( count( $languages ) <= 1 ) {
			return array( 'candidates' => array(), 'count' => 0, 'meta' => array( 'multilingual' => false ) );
		}
		$default = self::default_language( $languages );
		$primary_menus = self::menus_for_language( $default );

		$candidates = array();
		foreach ( $primary_menus as $term_id => $name ) {
			$source_items = (array) wp_get_nav_menu_items( $term_id );
			$source_titles = array();
			foreach ( $source_items as $i ) {
				$source_titles[ $i->ID ] = trim( wp_strip_all_tags( $i->title ) );
			}
			foreach ( $languages as $lang ) {
				if ( $lang === $default ) {
					continue;
				}
				$sib_id = self::translate_menu_term( $term_id, $lang );
				if ( ! $sib_id ) {
					continue;
				}
				$sib_items = (array) wp_get_nav_menu_items( $sib_id );
				$sib_count = count( $sib_items );
				$src_count = count( $source_items );
				if ( $sib_count >= $src_count ) {
					continue;
				}
				$candidates[] = array(
					'id'    => $term_id . ':' . $lang,
					'title' => sprintf( '%s → %s', $name, strtoupper( $lang ) ),
					'meta'  => sprintf( 'source %d items · target %d items · gap %d', $src_count, $sib_count, $src_count - $sib_count ),
					'_data' => array(
						'source_menu_id' => $term_id,
						'target_menu_id' => $sib_id,
						'lang'           => $lang,
						'gap'            => $src_count - $sib_count,
					),
				);
			}
		}

		return array(
			'candidates' => $candidates,
			'count'      => count( $candidates ),
			'meta'       => array(
				'languages'      => $languages,
				'default_lang'   => $default,
				'note'           => 'Execute appends top-level items present in source but missing in the target menu. Use args[item_titles]=array to limit which to propagate; otherwise all missing items are appended.',
			),
		);
	}

	public static function execute( $args = array(), $tool = array() ) {
		// Args expected:
		//   post_ids: array of "source_menu_id:lang" strings (the candidate ids)
		// We accept both raw `id` strings and integers; normalise.
		$raw_ids = (array) ( $args['post_ids'] ?? array() );
		if ( empty( $raw_ids ) ) {
			return new WP_Error( 'no_ids', 'Provide candidate ids in form "<menu_id>:<lang>".', array( 'status' => 400 ) );
		}

		$mutated = 0;
		$backup  = array();

		foreach ( $raw_ids as $candidate_id ) {
			if ( ! preg_match( '/^(\d+):([a-z]{2,5})$/', (string) $candidate_id, $m ) ) {
				continue;
			}
			$source_menu_id = (int) $m[1];
			$lang           = $m[2];
			$target_menu_id = self::translate_menu_term( $source_menu_id, $lang );
			if ( ! $target_menu_id ) {
				continue;
			}
			$source_items = (array) wp_get_nav_menu_items( $source_menu_id );
			$target_items = (array) wp_get_nav_menu_items( $target_menu_id );

			// Build a lookup of target titles (case-insensitive).
			$target_titles = array();
			foreach ( $target_items as $ti ) {
				$target_titles[ mb_strtolower( wp_strip_all_tags( $ti->title ) ) ] = true;
			}

			$created_ids = array();
			foreach ( $source_items as $si ) {
				if ( (int) $si->menu_item_parent !== 0 ) {
					continue; // depth 0 only
				}
				$key = mb_strtolower( wp_strip_all_tags( $si->title ) );
				if ( isset( $target_titles[ $key ] ) ) {
					continue;
				}
				$new_id = wp_update_nav_menu_item( $target_menu_id, 0, array(
					'menu-item-title'  => $si->title,
					'menu-item-url'    => self::translate_url_for_lang( $si->url, $lang ),
					'menu-item-status' => 'publish',
					'menu-item-type'   => 'custom',
				) );
				if ( ! is_wp_error( $new_id ) ) {
					$created_ids[] = (int) $new_id;
					$mutated++;
				}
			}
			if ( $created_ids ) {
				$backup[ $target_menu_id ] = array_merge( $backup[ $target_menu_id ] ?? array(), $created_ids );
			}
		}

		return array(
			'mutated'         => $mutated,
			'_backup_payload' => $backup,
		);
	}

	public static function restore( $args = array(), $tool = array() ) {
		$bridge = LuwiPress_Theme_Bridge::get_instance();
		$entry  = $bridge->load_backup( sanitize_text_field( $args['backup_id'] ?? '' ) );
		if ( ! $entry || $entry['tool_id'] !== 'menu_translation_propagate' ) {
			return new WP_Error( 'backup_not_found', 'Backup not found.', array( 'status' => 404 ) );
		}
		$payload  = is_array( $entry['payload'] ) ? $entry['payload'] : array();
		$restored = 0;
		foreach ( $payload as $menu_id => $item_ids ) {
			foreach ( (array) $item_ids as $iid ) {
				if ( wp_delete_post( (int) $iid, true ) ) {
					$restored++;
				}
			}
		}
		return array( 'restored' => $restored );
	}

	private static function active_languages() {
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$langs = apply_filters( 'wpml_active_languages', null );
			return is_array( $langs ) ? array_keys( $langs ) : array();
		}
		if ( function_exists( 'pll_languages_list' ) ) {
			return (array) pll_languages_list();
		}
		return array();
	}

	private static function default_language( $langs ) {
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$d = apply_filters( 'wpml_default_language', null );
			if ( $d ) { return $d; }
		}
		if ( function_exists( 'pll_default_language' ) ) {
			$d = pll_default_language();
			if ( $d ) { return $d; }
		}
		return reset( $langs );
	}

	private static function translate_menu_term( $term_id, $language ) {
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			return (int) apply_filters( 'wpml_object_id', (int) $term_id, 'nav_menu', false, $language );
		}
		if ( function_exists( 'pll_get_term' ) ) {
			return (int) pll_get_term( (int) $term_id, $language );
		}
		return 0;
	}

	private static function menus_for_language( $language ) {
		$out = array();
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			global $wpdb;
			$rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT t.element_id, terms.name
				   FROM {$wpdb->prefix}icl_translations t
				   JOIN {$wpdb->terms} terms ON terms.term_id = t.element_id
				  WHERE t.element_type = 'tax_nav_menu' AND t.language_code = %s",
				$language
			) );
			foreach ( (array) $rows as $r ) {
				$out[ (int) $r->element_id ] = $r->name;
			}
			return $out;
		}
		// Polylang or none.
		foreach ( wp_get_nav_menus() as $menu ) {
			$lang = function_exists( 'pll_get_term_language' ) ? pll_get_term_language( $menu->term_id ) : $language;
			if ( $lang === $language ) {
				$out[ $menu->term_id ] = $menu->name;
			}
		}
		return $out;
	}

	/**
	 * Best-effort URL translation. Custom URLs are kept as-is; for our own
	 * /product-category/ paths we prefix the language code (WPML handles
	 * actual rewriting at request time).
	 */
	private static function translate_url_for_lang( $url, $lang ) {
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$converted = apply_filters( 'wpml_permalink', $url, $lang );
			return is_string( $converted ) && $converted ? $converted : $url;
		}
		return $url;
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// Product Translation Completeness — read-only audit listing every product
// missing in one or more languages. Prepares the input for batch translation.
// ─────────────────────────────────────────────────────────────────────────────

class LuwiPress_Sapphire_Product_Translation_Tool {

	public static function scan( $args = array(), $tool = array() ) {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return array( 'candidates' => array(), 'count' => 0, 'meta' => array( 'wc_active' => false ) );
		}
		$languages = self::active_languages();
		if ( count( $languages ) <= 1 ) {
			return array( 'candidates' => array(), 'count' => 0, 'meta' => array( 'multilingual' => false ) );
		}
		$default = self::default_language( $languages );

		$limit = isset( $args['limit'] ) ? max( 1, min( 500, (int) $args['limit'] ) ) : 200;

		$ids = get_posts( array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $limit * 3,
			'fields'         => 'ids',
			'lang'           => $default,
		) );

		$candidates = array();
		foreach ( $ids as $pid ) {
			$missing = array();
			foreach ( $languages as $lang ) {
				if ( $lang === $default ) {
					continue;
				}
				$translated = self::resolve_post_in_language( (int) $pid, 'product', $lang );
				if ( ! $translated ) {
					$missing[] = $lang;
				}
			}
			if ( $missing ) {
				$candidates[] = array(
					'id'    => (int) $pid,
					'title' => get_the_title( (int) $pid ),
					'meta'  => sprintf( 'missing in %s', implode( ',', $missing ) ),
				);
			}
			if ( count( $candidates ) >= $limit ) {
				break;
			}
		}

		return array(
			'candidates' => $candidates,
			'count'      => count( $candidates ),
			'meta'       => array(
				'languages'    => $languages,
				'default_lang' => $default,
				'note'         => 'Pass these IDs to LuwiPress\'s /translation/batch endpoint to queue AI translation.',
			),
		);
	}

	private static function active_languages() {
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$langs = apply_filters( 'wpml_active_languages', null );
			return is_array( $langs ) ? array_keys( $langs ) : array();
		}
		if ( function_exists( 'pll_languages_list' ) ) {
			return (array) pll_languages_list();
		}
		return array();
	}

	private static function default_language( $langs ) {
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$d = apply_filters( 'wpml_default_language', null );
			if ( $d ) { return $d; }
		}
		if ( function_exists( 'pll_default_language' ) ) {
			$d = pll_default_language();
			if ( $d ) { return $d; }
		}
		return reset( $langs );
	}

	private static function resolve_post_in_language( $post_id, $type, $language ) {
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			return (int) apply_filters( 'wpml_object_id', $post_id, $type, false, $language );
		}
		if ( function_exists( 'pll_get_post' ) ) {
			return (int) pll_get_post( $post_id, $language );
		}
		return 0;
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// Elementor → Default Editor — for blog posts whose Elementor build hijacks
// our atelier single-post layout (sidebar, breadcrumb, reading-progress,
// related rail). The fix mirrors the WP admin "Use Default Editor" toggle:
// strip ONLY `_elementor_edit_mode`. `_elementor_data` is preserved so the
// switch is fully reversible — re-adding edit_mode='builder' restores the
// Elementor render. WPML siblings get their own post IDs (separate scans).
//
// Distinct from `elementor_shell_cleanup`: that tool targets EMPTY skeletons
// hiding Gutenberg content (Persian Tar pattern) — strips all Elementor
// metas + any legacy template meta. This tool targets POPULATED Elementor
// builds where the operator wants atelier rendering, not removal of the
// Elementor data itself.
// ─────────────────────────────────────────────────────────────────────────────

class LuwiPress_Sapphire_Elementor_To_Default_Editor_Tool {

	public static function scan( $args = array(), $tool = array() ) {
		$post_types = isset( $args['post_types'] ) && is_array( $args['post_types'] )
			? array_map( 'sanitize_key', $args['post_types'] )
			: array( 'post' );

		$limit = isset( $args['limit'] ) ? max( 1, min( 200, (int) $args['limit'] ) ) : 100;

		global $wpdb;
		$placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );

		// Posts with BOTH meta keys present — the body_class condition our
		// theme uses to flip into Elementor render mode. Single SQL JOIN is
		// the cheapest discovery path; meta_value not inspected (any non-
		// empty edit_mode is a builder flag).
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT p.ID, p.post_title, p.post_type, m1.meta_value AS edit_mode, LENGTH(m2.meta_value) AS data_size
			   FROM {$wpdb->posts} p
			   INNER JOIN {$wpdb->postmeta} m1 ON m1.post_id = p.ID AND m1.meta_key = '_elementor_edit_mode'
			   INNER JOIN {$wpdb->postmeta} m2 ON m2.post_id = p.ID AND m2.meta_key = '_elementor_data'
			  WHERE p.post_status = 'publish'
			    AND p.post_type IN ($placeholders)
			    AND m1.meta_value <> ''
			  ORDER BY p.ID DESC
			  LIMIT %d",
			array_merge( $post_types, array( $limit ) )
		) );

		$candidates = array();
		foreach ( (array) $rows as $row ) {
			$candidates[] = array(
				'id'        => (int) $row->ID,
				'title'     => $row->post_title ?: ( '#' . $row->ID ),
				'post_type' => $row->post_type,
				'meta'      => sprintf( 'edit_mode=%s · _elementor_data=%s · execute strips edit_mode only (data preserved)',
					$row->edit_mode,
					size_format( (int) $row->data_size, 1 )
				),
			);
		}

		return array(
			'candidates' => $candidates,
			'count'      => count( $candidates ),
			'meta'       => array(
				'post_types' => $post_types,
				'note'       => 'Reversible. Backup captures the edit_mode value (typically "builder"); restore re-adds it and Elementor takes back over rendering. _elementor_data is never modified.',
			),
		);
	}

	public static function execute( $args = array(), $tool = array() ) {
		$ids = array_map( 'intval', (array) ( $args['post_ids'] ?? array() ) );
		$ids = array_values( array_unique( array_filter( $ids ) ) );
		if ( empty( $ids ) ) {
			return new WP_Error( 'no_post_ids', 'Provide at least one post_id.', array( 'status' => 400 ) );
		}

		$mutated = 0;
		$skipped = array();
		$backup  = array();

		foreach ( $ids as $pid ) {
			$post = get_post( $pid );
			if ( ! $post ) {
				$skipped[] = array( 'id' => $pid, 'reason' => 'not_found' );
				continue;
			}
			$edit_mode = get_post_meta( $pid, '_elementor_edit_mode', true );
			if ( $edit_mode === '' || $edit_mode === null ) {
				$skipped[] = array( 'id' => $pid, 'reason' => 'no_edit_mode_meta' );
				continue;
			}
			$backup[ $pid ] = array( '_elementor_edit_mode' => $edit_mode );
			delete_post_meta( $pid, '_elementor_edit_mode' );
			$mutated++;
		}

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
		if ( ! $entry || $entry['tool_id'] !== 'elementor_to_default_editor' ) {
			return new WP_Error( 'backup_not_found', 'Backup not found.', array( 'status' => 404 ) );
		}
		$payload  = is_array( $entry['payload'] ) ? $entry['payload'] : array();
		$restored = 0;
		foreach ( $payload as $pid => $meta_set ) {
			$pid = (int) $pid;
			if ( ! get_post( $pid ) || ! is_array( $meta_set ) ) { continue; }
			foreach ( $meta_set as $key => $val ) {
				if ( $val === '' || $val === null ) { continue; }
				update_post_meta( $pid, $key, $val );
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
// Template Assignment — read-only; outputs current Elementor Pro PDP/archive
// template binding per language. Real enforcement happens via the
// `pdp_template_id` / `archive_template_id` settings + filter wired in
// inc/template-redirects.php (or a future inc/elementor-template-force.php).
// ─────────────────────────────────────────────────────────────────────────────

class LuwiPress_Sapphire_Template_Assignment_Tool {

	public static function scan( $args = array(), $tool = array() ) {
		$forced_pdp     = (int) get_theme_mod( 'luwipress_sapphire_pdp_template_id', 0 );
		$forced_archive = (int) get_theme_mod( 'luwipress_sapphire_archive_template_id', 0 );

		$candidates = array();
		$candidates[] = array(
			'id'    => 'pdp',
			'title' => 'Single Product (PDP)',
			'meta'  => $forced_pdp
				? sprintf( 'Forced to template #%d via theme_mod', $forced_pdp )
				: 'Deferred to Elementor Pro conditions',
		);
		$candidates[] = array(
			'id'    => 'archive',
			'title' => 'Products Archive',
			'meta'  => $forced_archive
				? sprintf( 'Forced to template #%d via theme_mod', $forced_archive )
				: 'Deferred to Elementor Pro conditions',
		);

		// Discover candidate template IDs for the operator to pick from.
		$pdp_choices     = self::find_templates_for( 'product' );
		$archive_choices = self::find_templates_for( 'product-archive' );

		return array(
			'candidates' => $candidates,
			'count'      => count( $candidates ),
			'meta'       => array(
				'pdp_force_id'         => $forced_pdp,
				'archive_force_id'     => $forced_archive,
				'pdp_template_choices' => $pdp_choices,
				'archive_template_choices' => $archive_choices,
				'note'                 => 'Apply via theme_settings_set with id="pdp_template_id" or "archive_template_id". Setting it to a non-zero value activates the force-template filter at request time.',
			),
		);
	}

	private static function find_templates_for( $location_keyword ) {
		if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
			return array();
		}
		$posts = get_posts( array(
			'post_type'      => 'elementor_library',
			'posts_per_page' => 50,
			'meta_query'     => array(
				array(
					'key'     => '_elementor_conditions',
					'value'   => $location_keyword,
					'compare' => 'LIKE',
				),
			),
		) );
		$out = array();
		foreach ( $posts as $p ) {
			$out[] = array(
				'id'    => (int) $p->ID,
				'title' => $p->post_title,
				'lang'  => defined( 'ICL_SITEPRESS_VERSION' )
					? apply_filters( 'wpml_element_language_code', null, array( 'element_id' => (int) $p->ID, 'element_type' => 'post_elementor_library' ) )
					: '',
			);
		}
		return $out;
	}
}
