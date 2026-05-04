<?php
/**
 * Smart Mapper — turns a detector snapshot into a concrete plan that the
 * Importer will execute.
 *
 * Three paths supported:
 *   - use_existing → adapt theme to detected content (recommended)
 *   - tapadum_demo → import bundled demo data (Elementor Kit + sample products)
 *   - empty        → tokens only, no content imports
 *
 * The plan is a structured array — Importer consumes it without re-deciding.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LuwiPress_Gold_Mapper {

	public function plan( array $snapshot, $path = 'use_existing' ) {
		$path = in_array( $path, [ 'use_existing', 'tapadum_demo', 'empty' ], true ) ? $path : 'use_existing';

		$plan = [
			'path'     => $path,
			'snapshot' => $snapshot,
			'actions'  => [],   // ordered list of operations the importer runs
			'pages'    => [],   // page slug → template / content mapping
			'menus'    => [],   // nav menu fixes / suggestions
			'options'  => [],   // wp_options writes (very limited, never destructive)
			'warnings' => [],
		];

		switch ( $path ) {
			case 'use_existing':
				$this->plan_use_existing( $plan );
				break;
			case 'tapadum_demo':
				$this->plan_tapadum_demo( $plan );
				break;
			case 'empty':
				$this->plan_empty( $plan );
				break;
		}

		return $plan;
	}

	/**
	 * Path A — adapt the theme to whatever is already in the DB.
	 * Never overwrites existing content; only adds missing pages/templates.
	 */
	private function plan_use_existing( &$plan ) {
		$snap = $plan['snapshot'];

		// 1. Theme Builder header / footer always go in (entire site).
		$plan['actions'][] = [
			'op'     => 'import_elementor_template',
			'kit'    => '01-header.json',
			'name'   => 'LuwiPress Gold — Header',
			'type'   => 'header',
			'condition_type'  => 'general',
			'condition_value' => 'include/general',
		];
		$plan['actions'][] = [
			'op'     => 'import_elementor_template',
			'kit'    => '02-footer.json',
			'name'   => 'LuwiPress Gold — Footer',
			'type'   => 'footer',
			'condition_type'  => 'general',
			'condition_value' => 'include/general',
		];

		// 2. Animation layer (loader + scroll reveal) always goes in.
		$plan['actions'][] = [
			'op'   => 'import_elementor_template',
			'kit'  => '00-animations.json',
			'name' => 'LuwiPress Gold — Animations',
			'type' => 'page',
		];

		// 3. Single Product template — assign to all products if WC present.
		if ( ! empty( $snap['wc']['active'] ) ) {
			$plan['actions'][] = [
				'op'     => 'import_elementor_template',
				'kit'    => '05-single-product.json',
				'name'   => 'LuwiPress Gold — Single Product',
				'type'   => 'single-product',
				'condition_type'  => 'product',
				'condition_value' => 'include/all',
			];

			// Pre-fill featured products array from top sellers.
			if ( ! empty( $snap['top_sellers'] ) ) {
				$plan['options']['luwipress_gold_featured_products'] = wp_list_pluck( $snap['top_sellers'], 'id' );
			}

			// Pre-fill megabar from top sub-categories.
			if ( ! empty( $snap['top_terms'] ) ) {
				$plan['options']['luwipress_gold_megabar_terms'] = wp_list_pluck( $snap['top_terms'], 'slug' );
			}
		}

		// 4. Homepage — only create/import if no front page is set.
		$has_home = ! empty( $snap['content']['front_page']['page_on_front'] );
		if ( ! $has_home ) {
			$plan['pages']['home'] = [
				'slug'        => 'home',
				'title'       => __( 'Home', 'luwipress-gold' ),
				'kit'         => '03-homepage.json',
				'set_as_home' => true,
				'reason'      => 'no_front_page_set',
			];
		} else {
			// Front page already exists — only import the kit if user opts in (mark as suggested).
			$plan['warnings'][] = [
				'kind'    => 'front_page_exists',
				'page_id' => $snap['content']['front_page']['page_on_front'],
				'title'   => $snap['content']['front_page']['home_title'],
				'message' => sprintf(
					/* translators: %s: existing home page title */
					__( 'Existing front page detected: "%s". The wizard will leave it alone unless you tick "Replace homepage with the Gold layout" below.', 'luwipress-gold' ),
					$snap['content']['front_page']['home_title']
				),
				'suggestion' => 'optional_replace_home',
			];
		}

		// 5. Static pages — create only if missing.
		foreach ( $this->static_page_definitions() as $slug => $def ) {
			$existing = $this->find_page_by_slug( $slug ) ?: $this->find_page_by_title( $def['title'] );
			if ( $existing ) {
				$plan['pages'][ $slug ] = [
					'existing_id' => $existing->ID,
					'title'       => $existing->post_title,
					'kit'         => $def['kit'],
					'reason'      => 'page_exists',
					'action'      => 'apply_template_only',
				];
			} else {
				$plan['pages'][ $slug ] = [
					'title'  => $def['title'],
					'kit'    => $def['kit'],
					'reason' => 'page_missing',
					'action' => 'create_and_import',
				];
			}
		}

		// 6. Master Profile template — assign to existing master luthier pages
		//    OR if pa_luthier taxonomy exists, plan to use the term archive template.
		if ( ! empty( $snap['masters'] ) ) {
			$plan['actions'][] = [
				'op'     => 'import_elementor_template',
				'kit'    => '07-master-profile.json',
				'name'   => 'LuwiPress Gold — Master Profile',
				'type'   => 'page',
				'note'   => sprintf(
					'Use as base for %d existing master luthier(s)',
					count( $snap['masters'] )
				),
			];
			foreach ( $snap['masters'] as $m ) {
				$plan['actions'][] = [
					'op'    => 'mark_master',
					'slug'  => $m['slug'],
					'name'  => $m['name'],
					'note'  => 'Surface in homepage maker grid',
				];
			}
		}

		// 7. WPML / Polylang — never auto-create translations.
		if ( ! empty( $snap['i18n']['is_multi'] ) ) {
			$plan['warnings'][] = [
				'kind'    => 'multilingual',
				'plugin'  => $snap['i18n']['plugin'],
				'count'   => count( $snap['i18n']['active'] ),
				'message' => sprintf(
					/* translators: %s: WPML or Polylang */
					__( '%s detected. New pages will be created in the default language only — duplicate them via your translation manager when ready.', 'luwipress-gold' ),
					strtoupper( $snap['i18n']['plugin'] )
				),
			];
		}

		// 8. Theme switching from Hello Elementor → reuse existing menu locations.
		if ( ! empty( $snap['theme_state']['switching_from_hello'] ) ) {
			$plan['warnings'][] = [
				'kind'    => 'switching_from_hello',
				'message' => __( 'Switching from Hello Elementor — your menus and Elementor pages will be preserved. Header / footer locations will be re-assigned to the LuwiPress Gold templates.', 'luwipress-gold' ),
			];
			$plan['menus']['preserve_assignments'] = true;
		}

		// 9. If there are existing menus, suggest the largest as `primary`.
		if ( ! empty( $snap['menus']['menus'] ) ) {
			$best = null;
			foreach ( $snap['menus']['menus'] as $m ) {
				if ( ! $best || $m['count'] > $best['count'] ) $best = $m;
			}
			if ( $best ) {
				$plan['menus']['primary_suggestion'] = [
					'menu_id' => $best['id'],
					'name'    => $best['name'],
					'reason'  => 'largest_existing_menu',
				];
			}
		}
	}

	/**
	 * Path B — bundled Tapadum-style demo content.
	 * Adds sample products + sample posts + full kit. Existing data is preserved
	 * (we tag everything with `_lwp_demo_data` for easy cleanup later).
	 */
	private function plan_tapadum_demo( &$plan ) {
		// Header / footer / animations — same as path A.
		$plan['actions'][] = [
			'op'   => 'import_elementor_template',
			'kit'  => '01-header.json',
			'name' => 'LuwiPress Gold — Header',
			'type' => 'header',
			'condition_type'  => 'general',
			'condition_value' => 'include/general',
		];
		$plan['actions'][] = [
			'op'   => 'import_elementor_template',
			'kit'  => '02-footer.json',
			'name' => 'LuwiPress Gold — Footer',
			'type' => 'footer',
			'condition_type'  => 'general',
			'condition_value' => 'include/general',
		];
		$plan['actions'][] = [
			'op'   => 'import_elementor_template',
			'kit'  => '00-animations.json',
			'name' => 'LuwiPress Gold — Animations',
			'type' => 'page',
		];

		// Demo content imports.
		$plan['actions'][] = [
			'op'   => 'import_demo_xml',
			'file' => 'demo-content/tapadum-products.xml',
			'note' => 'Sample products + categories + attributes',
		];
		$plan['actions'][] = [
			'op'   => 'import_demo_xml',
			'file' => 'demo-content/tapadum-posts.xml',
			'note' => 'Sample journal posts',
		];
		$plan['actions'][] = [
			'op'   => 'import_demo_xml',
			'file' => 'demo-content/tapadum-pages.xml',
			'note' => 'Demo pages (About, Masters, Contact, etc.)',
		];

		// All static pages, all imported.
		foreach ( $this->static_page_definitions() as $slug => $def ) {
			$plan['pages'][ $slug ] = [
				'title'  => $def['title'],
				'kit'    => $def['kit'],
				'reason' => 'tapadum_demo',
				'action' => 'create_and_import',
			];
		}

		$plan['pages']['home'] = [
			'slug'        => 'home',
			'title'       => __( 'Home', 'luwipress-gold' ),
			'kit'         => '03-homepage.json',
			'set_as_home' => true,
			'reason'      => 'tapadum_demo',
		];

		// Single Product template assigned to all products.
		$plan['actions'][] = [
			'op'     => 'import_elementor_template',
			'kit'    => '05-single-product.json',
			'name'   => 'LuwiPress Gold — Single Product',
			'type'   => 'single-product',
			'condition_type'  => 'product',
			'condition_value' => 'include/all',
		];

		$plan['warnings'][] = [
			'kind'    => 'demo_data_warning',
			'message' => __( 'Demo content will be tagged with _lwp_demo_data so you can remove it later via Tools → LuwiPress Gold → Clean demo data.', 'luwipress-gold' ),
		];
	}

	/**
	 * Path C — minimal: tokens + global colors only, no pages/products/menus touched.
	 */
	private function plan_empty( &$plan ) {
		$plan['actions'][] = [
			'op'   => 'import_elementor_kit',
			'file' => 'kit.json',
			'note' => 'Global colors + fonts + button styles only',
		];
		$plan['warnings'][] = [
			'kind'    => 'empty_path',
			'message' => __( 'No pages or products will be imported. You can re-run this wizard later or manually import templates from Elementor → Tools → Kit Library.', 'luwipress-gold' ),
		];
	}

	/* ------------------------------------------------------------------
	 * Helpers
	 * ---------------------------------------------------------------- */

	/**
	 * Static-page slug → kit JSON mapping.
	 */
	private function static_page_definitions() {
		return [
			'about'    => [ 'title' => __( 'About', 'luwipress-gold' ),    'kit' => '06-about.json' ],
			'masters'  => [ 'title' => __( 'Masters', 'luwipress-gold' ),  'kit' => '07-master-profile.json' ],
			'journal'  => [ 'title' => __( 'Journal', 'luwipress-gold' ),  'kit' => '08-journal.json' ],
			'contact'  => [ 'title' => __( 'Contact', 'luwipress-gold' ),  'kit' => '09-contact.json' ],
		];
	}

	private function find_page_by_slug( $slug ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
		return $page;
	}

	private function find_page_by_title( $title ) {
		// get_page_by_title() is deprecated — use a query instead.
		$pages = get_posts( [
			'post_type'      => 'page',
			'title'          => $title,
			'posts_per_page' => 1,
			'post_status'    => 'any',
		] );
		return ! empty( $pages ) ? $pages[0] : null;
	}
}
