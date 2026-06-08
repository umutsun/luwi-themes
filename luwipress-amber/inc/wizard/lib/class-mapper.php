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

class LuwiPress_Amber_Mapper {

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
			'name'   => 'LuwiPress Amber — Header',
			'type'   => 'header',
			'condition_type'  => 'general',
			'condition_value' => 'include/general',
		];
		$plan['actions'][] = [
			'op'     => 'import_elementor_template',
			'kit'    => '02-footer.json',
			'name'   => 'LuwiPress Amber — Footer',
			'type'   => 'footer',
			'condition_type'  => 'general',
			'condition_value' => 'include/general',
		];

		// 2. Animation layer (loader + scroll reveal) always goes in.
		$plan['actions'][] = [
			'op'   => 'import_elementor_template',
			'kit'  => '00-animations.json',
			'name' => 'LuwiPress Amber — Animations',
			'type' => 'page',
		];

		// 3. Single Product template — assign to all products if WC present.
		if ( ! empty( $snap['wc']['active'] ) ) {
			$plan['actions'][] = [
				'op'     => 'import_elementor_template',
				'kit'    => '05-single-product.json',
				'name'   => 'LuwiPress Amber — Single Product',
				'type'   => 'single-product',
				'condition_type'  => 'product',
				'condition_value' => 'include/all',
			];

			// Pre-fill featured products array from top sellers.
			if ( ! empty( $snap['top_sellers'] ) ) {
				$plan['options']['luwipress_amber_featured_products'] = wp_list_pluck( $snap['top_sellers'], 'id' );
			}

			// Pre-fill megabar from top sub-categories.
			if ( ! empty( $snap['top_terms'] ) ) {
				$plan['options']['luwipress_amber_megabar_terms'] = wp_list_pluck( $snap['top_terms'], 'slug' );
			}
		}

		// 4. Homepage handling — non-destructive policy.
		//    - If front page exists → UPDATE its content with the compiled
		//      Gold homepage (operator can revert via post revisions).
		//    - If no front page → create a new "Home" and set it as front.
		$has_home = ! empty( $snap['content']['front_page']['page_on_front'] );
		if ( $has_home ) {
			$plan['pages']['home'] = [
				'existing_id' => (int) $snap['content']['front_page']['page_on_front'],
				'title'       => $snap['content']['front_page']['home_title'] ?: __( 'Home', 'luwipress-amber' ),
				'kit'         => '03-homepage.json',
				'reason'      => 'updating_front_page',
				'action'      => 'apply_template_only',
				'preserve_revision' => true,
			];
		} else {
			$plan['pages']['home'] = [
				'slug'        => 'home',
				'title'       => __( 'Home', 'luwipress-amber' ),
				'kit'         => '03-homepage.json',
				'set_as_home' => true,
				'reason'      => 'no_front_page_set',
				'action'      => 'create_and_import',
			];
		}

		// 5. Static pages — NON-DESTRUCTIVE. If a page with the canonical slug
		//    already exists we leave it untouched; create a parallel page with
		//    a `-gold` suffix so the operator can compare side by side.
		foreach ( $this->static_page_definitions() as $slug => $def ) {
			$existing = $this->find_page_by_slug( $slug ) ?: $this->find_page_by_title( $def['title'] );
			if ( $existing ) {
				$plan['pages'][ $slug ] = [
					'preserved_existing_id' => $existing->ID,
					'preserved_title'       => $existing->post_title,
					'slug'   => $slug . '-gold',
					'title'  => $def['title'] . ' (Gold)',
					'kit'    => $def['kit'],
					'reason' => 'page_exists_creating_parallel',
					'action' => 'create_and_import',
					'parallel_to' => $existing->ID,
				];
				$plan['warnings'][] = [
					'kind'    => 'parallel_page_created',
					'message' => sprintf(
						/* translators: 1: existing page title 2: new gold page slug */
						__( 'Existing "%1$s" left untouched. A parallel "%2$s" page was created so you can compare layouts side by side.', 'luwipress-amber' ),
						$existing->post_title,
						$slug . '-gold'
					),
				];
			} else {
				$plan['pages'][ $slug ] = [
					'slug'   => $slug,
					'title'  => $def['title'],
					'kit'    => $def['kit'],
					'reason' => 'page_missing',
					'action' => 'create_and_import',
				];
			}
		}

		// 6. Master Profile pages — auto-generate one page per pa_luthier term,
		//    each populated with that master's name + portrait + product count.
		//    Pages live under /masters/{slug}/. If a page already exists at
		//    that slug, we skip (non-destructive).
		if ( ! empty( $snap['masters'] ) ) {
			// Save the template once for Theme Builder Single Page condition (Pro only).
			$plan['actions'][] = [
				'op'     => 'import_elementor_template',
				'kit'    => '07-master-profile.json',
				'name'   => 'LuwiPress Amber — Master Profile',
				'type'   => 'page',
				'note'   => sprintf(
					/* translators: %d: master count */
					_n( 'Used as the base layout for %d luthier page', 'Used as the base layout for %d luthier pages', count( $snap['masters'] ), 'luwipress-amber' ),
					count( $snap['masters'] )
				),
			];

			// Generate one page per master. Compiler resolves placeholders against
			// the matching pa_luthier term — name, portrait, count, etc.
			foreach ( $snap['masters'] as $m ) {
				$page_slug = 'masters/' . sanitize_title( $m['slug'] );
				$existing  = $this->find_page_by_slug( 'masters-' . sanitize_title( $m['slug'] ) );
				if ( $existing ) {
					$plan['actions'][] = [
						'op'    => 'mark_master',
						'slug'  => $m['slug'],
						'name'  => $m['name'],
						'note'  => 'Page already exists — surface in homepage maker grid',
					];
					continue;
				}
				$plan['pages'][ 'master-' . sanitize_title( $m['slug'] ) ] = [
					'slug'   => 'masters-' . sanitize_title( $m['slug'] ),
					'title'  => $m['name'],
					'kit'    => '07-master-profile.json',
					'reason' => 'master_profile_auto_generated',
					'action' => 'create_and_import',
					'compiler_context' => [
						'master_slug' => $m['slug'],
						'master_name' => $m['name'],
						'master_init' => $m['init'],
						'master_count' => $m['count'],
					],
				];
				$plan['actions'][] = [
					'op'    => 'mark_master',
					'slug'  => $m['slug'],
					'name'  => $m['name'],
					'note'  => 'Surface in homepage maker grid + auto-generated profile page',
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
					__( '%s detected. New pages will be created in the default language only — duplicate them via your translation manager when ready.', 'luwipress-amber' ),
					strtoupper( $snap['i18n']['plugin'] )
				),
			];
		}

		// 8. Theme switching from Hello Elementor → reuse existing menu locations.
		if ( ! empty( $snap['theme_state']['switching_from_hello'] ) ) {
			$plan['warnings'][] = [
				'kind'    => 'switching_from_hello',
				'message' => __( 'Switching from Hello Elementor — your menus and Elementor pages will be preserved. Header / footer locations will be re-assigned to the LuwiPress Amber templates.', 'luwipress-amber' ),
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
			'name' => 'LuwiPress Amber — Header',
			'type' => 'header',
			'condition_type'  => 'general',
			'condition_value' => 'include/general',
		];
		$plan['actions'][] = [
			'op'   => 'import_elementor_template',
			'kit'  => '02-footer.json',
			'name' => 'LuwiPress Amber — Footer',
			'type' => 'footer',
			'condition_type'  => 'general',
			'condition_value' => 'include/general',
		];
		$plan['actions'][] = [
			'op'   => 'import_elementor_template',
			'kit'  => '00-animations.json',
			'name' => 'LuwiPress Amber — Animations',
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
			'title'       => __( 'Home', 'luwipress-amber' ),
			'kit'         => '03-homepage.json',
			'set_as_home' => true,
			'reason'      => 'tapadum_demo',
		];

		// Single Product template assigned to all products.
		$plan['actions'][] = [
			'op'     => 'import_elementor_template',
			'kit'    => '05-single-product.json',
			'name'   => 'LuwiPress Amber — Single Product',
			'type'   => 'single-product',
			'condition_type'  => 'product',
			'condition_value' => 'include/all',
		];

		$plan['warnings'][] = [
			'kind'    => 'demo_data_warning',
			'message' => __( 'Demo content will be tagged with _lwp_demo_data so you can remove it later via Tools → LuwiPress Amber → Clean demo data.', 'luwipress-amber' ),
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
			'message' => __( 'No pages or products will be imported. You can re-run this wizard later or manually import templates from Elementor → Tools → Kit Library.', 'luwipress-amber' ),
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
			'about'    => [ 'title' => __( 'About', 'luwipress-amber' ),    'kit' => '06-about.json' ],
			'masters'  => [ 'title' => __( 'Masters', 'luwipress-amber' ),  'kit' => '07-master-profile.json' ],
			'journal'  => [ 'title' => __( 'Journal', 'luwipress-amber' ),  'kit' => '08-journal.json' ],
			'contact'  => [ 'title' => __( 'Contact', 'luwipress-amber' ),  'kit' => '09-contact.json' ],
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
