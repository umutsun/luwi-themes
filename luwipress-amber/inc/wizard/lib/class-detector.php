<?php
/**
 * Site Detector — scans the existing WordPress install for everything the
 * wizard needs to make smart decisions about what to import / map.
 *
 * Read-only: never writes to the DB, never modifies any post or option.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LuwiPress_Amber_Detector {

	/**
	 * Full snapshot — used by the wizard UI + handed off to the mapper.
	 *
	 * @return array Heavily-nested associative array; structure documented inline.
	 */
	public function snapshot() {
		return [
			'site'           => $this->detect_site(),
			'wc'             => $this->detect_woocommerce(),
			'content'        => $this->detect_content(),
			'i18n'           => $this->detect_languages(),
			'menus'          => $this->detect_menus(),
			'theme_state'    => $this->detect_theme_state(),
			'plugins'        => $this->detect_plugins(),
			'seo'            => $this->detect_seo(),
			'top_sellers'    => $this->detect_top_sellers(),
			'top_terms'      => $this->detect_top_terms(),
			'masters'        => $this->detect_master_luthiers(),
			'slug_conflicts' => $this->detect_slug_conflicts(),
		];
	}

	/**
	 * Find page slugs that collide with non-empty product_cat term slugs.
	 *
	 * Returns one row per conflict: page details + matching term details +
	 * the proposed redirect URL. Empty array when:
	 *   - WooCommerce is inactive
	 *   - No published page shares a slug with a product category
	 *
	 * Used by the wizard "Detect" panel to show the operator a transparent
	 * preview of what will be redirected if they opt into auto-resolution
	 * on the Apply step.
	 *
	 * Read-only — only SELECTs, no writes.
	 *
	 * @return array<int,array{slug:string,page_id:int,page_title:string,page_url:string,term_id:int,term_name:string,term_count:int,target_url:string}>
	 */
	public function detect_slug_conflicts() {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return [];
		}
		global $wpdb;
		// One row per (page, matching term). LEFT-fielding the page first
		// so we get a proper INNER join only when the term exists.
		$rows = $wpdb->get_results(
			"SELECT p.ID AS page_id, p.post_title AS page_title, p.post_name AS slug,
			        t.term_id AS term_id, t.name AS term_name, tt.count AS term_count
			   FROM {$wpdb->posts} p
			   INNER JOIN {$wpdb->terms} t ON p.post_name = t.slug
			   INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
			  WHERE p.post_type = 'page'
			    AND p.post_status = 'publish'
			    AND tt.taxonomy = 'product_cat'
			    AND tt.count > 0
			  ORDER BY tt.count DESC, p.post_title ASC",
			ARRAY_A
		);
		if ( ! is_array( $rows ) || empty( $rows ) ) {
			return [];
		}
		$out = [];
		foreach ( $rows as $r ) {
			$slug = (string) $r['slug'];
			if ( $slug === '' ) {
				continue;
			}
			$out[] = [
				'slug'       => $slug,
				'page_id'    => (int) $r['page_id'],
				'page_title' => (string) $r['page_title'],
				'page_url'   => home_url( '/' . $slug . '/' ),
				'term_id'    => (int) $r['term_id'],
				'term_name'  => (string) $r['term_name'],
				'term_count' => (int) $r['term_count'],
				'target_url' => home_url( '/product-category/' . $slug . '/' ),
			];
		}
		return $out;
	}

	/* --------------------------------------------------------------------
	 * Atomic detectors
	 * ------------------------------------------------------------------ */

	private function detect_site() {
		return [
			'name'        => get_bloginfo( 'name' ),
			'description' => get_bloginfo( 'description' ),
			'admin_email' => get_option( 'admin_email' ),
			'home_url'    => home_url( '/' ),
			'has_logo'    => has_custom_logo(),
			'logo_url'    => has_custom_logo() ? wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' ) : null,
			'language'    => get_locale(),
			'timezone'    => wp_timezone_string(),
		];
	}

	private function detect_woocommerce() {
		$active = class_exists( 'WooCommerce' );
		if ( ! $active ) {
			return [ 'active' => false ];
		}

		$product_count = wp_count_posts( 'product' );
		$published     = isset( $product_count->publish ) ? (int) $product_count->publish : 0;

		// Top-level product categories.
		$top_cats = [];
		$terms    = get_terms( [
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => false,
			'orderby'    => 'count',
			'order'      => 'DESC',
		] );
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $t ) {
				$top_cats[] = [
					'id'    => $t->term_id,
					'slug'  => $t->slug,
					'name'  => $t->name,
					'count' => (int) $t->count,
				];
			}
		}

		// Sub-categories — flat list with their parent.
		$sub_cats = [];
		$child_terms = get_terms( [
			'taxonomy'   => 'product_cat',
			'parent__not_in' => [ 0 ],
			'hide_empty' => false,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => 50,
		] );
		if ( ! is_wp_error( $child_terms ) && ! empty( $child_terms ) ) {
			foreach ( $child_terms as $t ) {
				if ( $t->parent === 0 ) continue;
				$sub_cats[] = [
					'id'        => $t->term_id,
					'slug'      => $t->slug,
					'name'      => $t->name,
					'parent_id' => $t->parent,
					'count'     => (int) $t->count,
				];
			}
		}

		// Product attributes (`pa_*` taxonomies).
		$attributes = [];
		if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
			foreach ( wc_get_attribute_taxonomies() as $att ) {
				$tax_name = 'pa_' . $att->attribute_name;
				$att_terms = get_terms( [ 'taxonomy' => $tax_name, 'hide_empty' => false, 'number' => 200 ] );
				if ( is_wp_error( $att_terms ) ) continue;
				$attributes[] = [
					'name'      => $att->attribute_name,
					'label'     => $att->attribute_label,
					'taxonomy'  => $tax_name,
					'term_count' => count( $att_terms ),
					'terms'     => array_values( array_map( function ( $t ) {
						return [ 'slug' => $t->slug, 'name' => $t->name, 'count' => (int) $t->count ];
					}, $att_terms ) ),
				];
			}
		}

		// Currency + tax info.
		$currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '';
		$on_sale_count = function_exists( 'wc_get_product_ids_on_sale' ) ? count( wc_get_product_ids_on_sale() ) : 0;

		return [
			'active'        => true,
			'version'       => defined( 'WC_VERSION' ) ? WC_VERSION : '',
			'product_count' => $published,
			'on_sale_count' => $on_sale_count,
			'currency'      => $currency,
			'top_cats'      => $top_cats,
			'sub_cats'      => array_slice( $sub_cats, 0, 30 ),
			'attributes'    => $attributes,
			'has_luthier'   => taxonomy_exists( 'pa_luthier' ),
			'has_origin'    => taxonomy_exists( 'pa_origin' ),
		];
	}

	private function detect_content() {
		$post_count = wp_count_posts( 'post' );
		$published_posts = isset( $post_count->publish ) ? (int) $post_count->publish : 0;

		$page_count = wp_count_posts( 'page' );
		$published_pages = isset( $page_count->publish ) ? (int) $page_count->publish : 0;

		// Post categories with counts.
		$post_cats = [];
		$cats = get_categories( [ 'hide_empty' => false, 'orderby' => 'count', 'order' => 'DESC', 'number' => 20 ] );
		foreach ( $cats as $c ) {
			$post_cats[] = [ 'slug' => $c->slug, 'name' => $c->name, 'count' => (int) $c->count ];
		}

		// Detect known custom post types (informational only).
		$cpts = [];
		foreach ( get_post_types( [ '_builtin' => false, 'public' => true ], 'objects' ) as $cpt ) {
			if ( in_array( $cpt->name, [ 'product', 'shop_order', 'shop_coupon', 'product_variation' ], true ) ) continue;
			$cnt = wp_count_posts( $cpt->name );
			$cpts[] = [
				'name'  => $cpt->name,
				'label' => $cpt->labels->name ?? $cpt->name,
				'count' => isset( $cnt->publish ) ? (int) $cnt->publish : 0,
			];
		}

		// Front-page setup.
		$show_on_front = get_option( 'show_on_front' );
		$page_on_front = (int) get_option( 'page_on_front' );
		$page_for_posts = (int) get_option( 'page_for_posts' );

		return [
			'posts'         => $published_posts,
			'pages'         => $published_pages,
			'post_cats'     => $post_cats,
			'custom_post_types' => $cpts,
			'front_page' => [
				'mode'           => $show_on_front,
				'page_on_front'  => $page_on_front,
				'page_for_posts' => $page_for_posts,
				'home_title'     => $page_on_front ? get_the_title( $page_on_front ) : '',
				'blog_title'     => $page_for_posts ? get_the_title( $page_for_posts ) : '',
			],
		];
	}

	private function detect_languages() {
		$out = [
			'plugin'   => null,    // wpml | polylang | translatepress | null
			'default'  => null,
			'active'   => [],
			'is_multi' => false,
		];

		// WPML.
		if ( defined( 'ICL_LANGUAGE_CODE' ) && function_exists( 'icl_get_languages' ) ) {
			$out['plugin']  = 'wpml';
			$out['default'] = apply_filters( 'wpml_default_language', null );
			$langs = icl_get_languages( 'skip_missing=0' );
			if ( is_array( $langs ) ) {
				foreach ( $langs as $code => $info ) {
					$out['active'][] = [
						'code'   => $info['language_code'],
						'name'   => $info['translated_name'] ?? $info['native_name'] ?? $info['language_code'],
						'native' => $info['native_name'] ?? '',
						'active' => ! empty( $info['active'] ),
					];
				}
			}
			$out['is_multi'] = count( $out['active'] ) > 1;
			return $out;
		}

		// Polylang.
		if ( function_exists( 'pll_languages_list' ) && function_exists( 'pll_default_language' ) ) {
			$out['plugin']  = 'polylang';
			$out['default'] = pll_default_language();
			$codes  = pll_languages_list();
			$current = function_exists( 'pll_current_language' ) ? pll_current_language() : null;
			if ( is_array( $codes ) ) {
				foreach ( $codes as $code ) {
					$out['active'][] = [
						'code'   => $code,
						'name'   => $code,
						'native' => $code,
						'active' => $code === $current,
					];
				}
			}
			$out['is_multi'] = count( $out['active'] ) > 1;
			return $out;
		}

		// TranslatePress.
		if ( class_exists( 'TRP_Translate_Press' ) ) {
			$out['plugin'] = 'translatepress';
			return $out;
		}

		return $out;
	}

	private function detect_menus() {
		$registered_locations = get_nav_menu_locations();
		$detected = [];

		foreach ( wp_get_nav_menus() as $menu ) {
			$items = wp_get_nav_menu_items( $menu->term_id );
			$assigned_locations = array_keys( $registered_locations, $menu->term_id, true );
			$detected[] = [
				'id'        => $menu->term_id,
				'name'      => $menu->name,
				'slug'      => $menu->slug,
				'count'     => is_array( $items ) ? count( $items ) : 0,
				'locations' => $assigned_locations,
			];
		}

		return [
			'menus' => $detected,
			'locations_registered' => array_keys( get_registered_nav_menus() ),
		];
	}

	private function detect_theme_state() {
		$current = wp_get_theme();
		return [
			'name'    => $current->get( 'Name' ),
			'slug'    => get_stylesheet(),
			'version' => $current->get( 'Version' ),
			'parent'  => $current->parent() ? $current->parent()->get( 'Name' ) : null,
			'switching_from_hello' => stripos( $current->get( 'Name' ), 'hello elementor' ) !== false,
			'is_luwipress_amber' => stripos( $current->get( 'Name' ), 'luwipress gold' ) !== false,
		];
	}

	private function detect_plugins() {
		$present = [
			'elementor'        => did_action( 'elementor/loaded' ),
			'elementor_pro'    => defined( 'ELEMENTOR_PRO_VERSION' ),
			'elementskit_lite' => defined( 'ELEMENTSKIT_LITE_VERSION' ) || class_exists( '\Elementskit_Lite\Elementskit_Lite' ),
			'woocommerce'      => class_exists( 'WooCommerce' ),
			'wpml'             => defined( 'ICL_SITEPRESS_VERSION' ),
			'polylang'         => function_exists( 'pll_languages_list' ),
			'yoast'            => defined( 'WPSEO_VERSION' ),
			'rank_math'        => defined( 'RANK_MATH_VERSION' ),
			'wp_rocket'        => defined( 'WP_ROCKET_VERSION' ),
			'litespeed'        => defined( 'LSCWP_V' ),
			'mc4wp'            => class_exists( 'MC4WP_Plugin' ),
			'mailpoet'         => class_exists( 'MailPoet\\Config\\Initializer' ),
			'cf7'              => defined( 'WPCF7_VERSION' ),
			'wpforms'          => defined( 'WPFORMS_VERSION' ),
		];
		return $present;
	}

	private function detect_seo() {
		$plugin = null;
		if ( defined( 'WPSEO_VERSION' ) ) $plugin = 'yoast';
		elseif ( defined( 'RANK_MATH_VERSION' ) ) $plugin = 'rank_math';
		elseif ( defined( 'AIOSEO_VERSION' ) ) $plugin = 'aioseo';
		elseif ( defined( 'SEOPRESS_VERSION' ) ) $plugin = 'seopress';
		return [ 'plugin' => $plugin ];
	}

	/**
	 * Top-N best sellers — used to populate the homepage "Featured products" grid.
	 */
	private function detect_top_sellers( $limit = 8 ) {
		if ( ! class_exists( 'WooCommerce' ) ) return [];

		// Try `total_sales` meta.
		$args = [
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'meta_key'       => 'total_sales',
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		];
		$query = new WP_Query( $args );

		// Fallback to recently-modified if no sales data.
		if ( ! $query->have_posts() ) {
			$query = new WP_Query( [
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			] );
		}

		$out = [];
		foreach ( $query->posts as $p ) {
			$product = wc_get_product( $p->ID );
			if ( ! $product ) continue;
			$out[] = [
				'id'    => $p->ID,
				'title' => $product->get_name(),
				'url'   => get_permalink( $p->ID ),
				'price' => wp_strip_all_tags( $product->get_price_html() ),
				'image' => wp_get_attachment_image_url( $product->get_image_id(), 'large' ),
				'sales' => (int) get_post_meta( $p->ID, 'total_sales', true ),
				'sku'   => $product->get_sku(),
			];
		}
		wp_reset_postdata();
		return $out;
	}

	/**
	 * Most-used sub-category terms — populate the megabar (sub-cat strip).
	 */
	private function detect_top_terms( $limit = 12 ) {
		if ( ! class_exists( 'WooCommerce' ) ) return [];

		$terms = get_terms( [
			'taxonomy'   => 'product_cat',
			'parent__not_in' => [ 0 ],
			'orderby'    => 'count',
			'order'      => 'DESC',
			'hide_empty' => true,
			'number'     => $limit,
		] );
		if ( is_wp_error( $terms ) ) return [];

		$out = [];
		foreach ( $terms as $t ) {
			$out[] = [
				'name'  => $t->name,
				'slug'  => $t->slug,
				'url'   => get_term_link( $t ),
				'count' => (int) $t->count,
			];
		}
		return $out;
	}

	/**
	 * If pa_luthier exists, surface up to 8 luthiers with their product counts.
	 * Otherwise return [] — wizard falls back to story-section maker tiles.
	 */
	private function detect_master_luthiers( $limit = 8 ) {
		if ( ! taxonomy_exists( 'pa_luthier' ) ) return [];

		$terms = get_terms( [
			'taxonomy'   => 'pa_luthier',
			'orderby'    => 'count',
			'order'      => 'DESC',
			'hide_empty' => false,
			'number'     => $limit,
		] );
		if ( is_wp_error( $terms ) ) return [];

		$out = [];
		foreach ( $terms as $t ) {
			$out[] = [
				'name'  => $t->name,
				'slug'  => $t->slug,
				'count' => (int) $t->count,
				'init'  => mb_substr( $t->name, 0, 1 ),
				'url'   => get_term_link( $t ),
			];
		}
		return $out;
	}

	/**
	 * Human-readable one-liner for the activation notice.
	 *
	 * Examples:
	 *   "We found 128 products across 5 categories, 57 posts, and 5 active languages."
	 *   "Empty install — no WooCommerce yet."
	 */
	public function summary_phrase( $snap ) {
		$bits = [];

		if ( ! empty( $snap['wc']['active'] ) ) {
			$bits[] = sprintf(
				/* translators: %1$d products, %2$d categories */
				_n(
					'%1$d product across %2$d categories',
					'%1$d products across %2$d categories',
					$snap['wc']['product_count'],
					'luwipress-amber'
				),
				$snap['wc']['product_count'],
				count( $snap['wc']['top_cats'] )
			);
		} else {
			$bits[] = __( 'no WooCommerce yet', 'luwipress-amber' );
		}

		if ( ! empty( $snap['content']['posts'] ) ) {
			$bits[] = sprintf(
				/* translators: %d posts */
				_n( '%d post', '%d posts', $snap['content']['posts'], 'luwipress-amber' ),
				$snap['content']['posts']
			);
		}

		if ( ! empty( $snap['i18n']['is_multi'] ) ) {
			$bits[] = sprintf(
				/* translators: %d active languages */
				_n( '%d active language', '%d active languages', count( $snap['i18n']['active'] ), 'luwipress-amber' ),
				count( $snap['i18n']['active'] )
			);
		}

		if ( ! empty( $snap['masters'] ) ) {
			$bits[] = sprintf(
				/* translators: %d master luthiers */
				_n( '%d master luthier', '%d master luthiers', count( $snap['masters'] ), 'luwipress-amber' ),
				count( $snap['masters'] )
			);
		}

		if ( empty( $bits ) ) {
			return __( 'Fresh install — perfect for the demo content path.', 'luwipress-amber' );
		}

		return sprintf(
			/* translators: comma-joined list of detected things */
			__( 'We detected: %s.', 'luwipress-amber' ),
			implode( ', ', $bits )
		);
	}
}
