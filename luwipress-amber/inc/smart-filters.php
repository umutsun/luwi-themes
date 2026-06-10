<?php
/**
 * Smart filter sidebar.
 *
 * Renders a contextual filter sidebar for product archives that adapts to
 * the catalog without operator configuration. Replaces the legacy
 * "Categories" list (which was redundant with the mega-menu) with widgets
 * that actually narrow the result set: price, attributes (any pa_*
 * taxonomy with terms in the archive), tags, on-sale, in-stock.
 *
 * Generic — works on any WooCommerce store regardless of attribute
 * configuration. Stores with zero registered attributes still get price +
 * on-sale + in-stock + active-filter chip; stores with rich attributes
 * (master luthier, size, region, finish…) light up the full filter panel
 * automatically.
 *
 * Used by `woocommerce/archive-product.php` as the sidebar fallback when
 * the operator hasn't configured the `shop-sidebar` widget area. Operators
 * who want a different layout can:
 *   - Drop widgets into the shop-sidebar area (their widgets win)
 *   - Hook `luwipress_amber_smart_filters_blocks` to add / remove blocks
 *   - Use the `luwipress_amber_smart_filter_quick_toggles` filter to
 *     extend or override the on-sale / in-stock toggles
 *
 * URL conventions follow WooCommerce native query strings:
 *   ?min_price=…&max_price=…           → price range
 *   ?filter_<attribute>=term1,term2    → attribute filter
 *   ?onsale=1                           → on-sale only (custom)
 *   ?instock=1                          → in-stock only (custom)
 *
 * @package luwipress-amber
 * @since   1.6.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'luwipress_amber_render_smart_filters' ) ) {

	/**
	 * Top-level entry point — invoke from the archive sidebar slot.
	 *
	 * @param WP_Term|null $current_term Queried term, or null on shop.
	 */
	function luwipress_amber_render_smart_filters( $current_term = null ) {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}

		$widget_args = [
			'before_widget' => '<div class="lwp-filter-block">',
			'after_widget'  => '</div>',
			'before_title'  => '<h5 class="lwp-filter-block__title">',
			'after_title'   => '</h5>',
		];

		// Composable block list. Filter to add / remove / reorder.
		$blocks = apply_filters( 'luwipress_amber_smart_filters_blocks', [
			'active_filters' => true,
			'price'          => true,
			'attributes'     => true,
			'tags'           => true,
			'quick_toggles'  => true,
		], $current_term );

		echo '<div class="lwp-smart-filters" role="group" aria-label="' . esc_attr__( 'Filter results', 'luwipress-amber' ) . '">';

		if ( ! empty( $blocks['active_filters'] ) ) {
			luwipress_amber_render_active_filters_chip( $widget_args );
		}
		if ( ! empty( $blocks['price'] ) ) {
			luwipress_amber_render_price_filter( $widget_args );
		}
		if ( ! empty( $blocks['attributes'] ) ) {
			luwipress_amber_render_attribute_filters( $widget_args, $current_term );
		}
		if ( ! empty( $blocks['tags'] ) ) {
			luwipress_amber_render_archive_tag_cloud( $widget_args, $current_term );
		}
		if ( ! empty( $blocks['quick_toggles'] ) ) {
			luwipress_amber_render_quick_toggles( $widget_args );
		}

		echo '</div>';
	}
}

if ( ! function_exists( 'luwipress_amber_render_active_filters_chip' ) ) {

	function luwipress_amber_render_active_filters_chip( $widget_args ) {
		// Native WC widget — only renders when at least one filter is active.
		if ( ! class_exists( 'WC_Widget_Layered_Nav_Filters' ) ) {
			return;
		}
		the_widget( 'WC_Widget_Layered_Nav_Filters', [
			'title' => __( 'Active filters', 'luwipress-amber' ),
		], $widget_args );
	}
}

if ( ! function_exists( 'luwipress_amber_render_price_filter' ) ) {

	function luwipress_amber_render_price_filter( $widget_args ) {
		if ( ! class_exists( 'WC_Widget_Price_Filter' ) ) {
			return;
		}
		the_widget( 'WC_Widget_Price_Filter', [
			'title' => __( 'Price', 'luwipress-amber' ),
		], $widget_args );
	}
}

if ( ! function_exists( 'luwipress_amber_render_attribute_filters' ) ) {

	/**
	 * Render one Layered Nav widget per registered pa_* attribute that has
	 * at least one term with non-zero product count. Skips attributes that
	 * exist in the registry but have no usable terms — keeps the sidebar
	 * tidy on stores that registered an attribute but never populated it.
	 *
	 * @param array        $widget_args
	 * @param WP_Term|null $current_term
	 */
	function luwipress_amber_render_attribute_filters( $widget_args, $current_term = null ) {
		if ( ! class_exists( 'WC_Widget_Layered_Nav' ) ) {
			return;
		}
		if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
			return;
		}

		$attribute_taxonomies = wc_get_attribute_taxonomies();
		if ( empty( $attribute_taxonomies ) ) {
			return;
		}

		foreach ( $attribute_taxonomies as $tax ) {
			$slug     = (string) $tax->attribute_name;     // e.g. 'size'
			$tax_full = wc_attribute_taxonomy_name( $slug ); // e.g. 'pa_size'
			if ( ! taxonomy_exists( $tax_full ) ) {
				continue;
			}

			// Skip attributes with zero used terms — would render an empty
			// widget header otherwise.
			$used = get_terms( [
				'taxonomy'   => $tax_full,
				'hide_empty' => true,
				'fields'     => 'ids',
				'number'     => 1,
			] );
			if ( is_wp_error( $used ) || empty( $used ) ) {
				continue;
			}

			$label = $tax->attribute_label !== '' ? $tax->attribute_label : ucfirst( $slug );

			the_widget( 'WC_Widget_Layered_Nav', [
				'title'        => $label,
				'attribute'    => $slug,
				'display_type' => 'list',
				'query_type'   => 'and',
			], $widget_args );
		}
	}
}

if ( ! function_exists( 'luwipress_amber_render_archive_tag_cloud' ) ) {

	/**
	 * Render product tags as a compact tag cloud, scoped to tags that
	 * actually appear on products in the current archive context. Caps to
	 * 8 most-popular tags so we don't overflow the sidebar.
	 *
	 * @param array        $widget_args
	 * @param WP_Term|null $current_term
	 */
	function luwipress_amber_render_archive_tag_cloud( $widget_args, $current_term = null ) {
		if ( ! taxonomy_exists( 'product_tag' ) ) {
			return;
		}

		$args = [
			'taxonomy'   => 'product_tag',
			'hide_empty' => true,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => 8,
		];

		// When we're on a category archive, narrow tags to the products
		// inside that category branch so we don't show generic site-wide
		// tags that don't exist among the current results.
		if ( $current_term instanceof WP_Term && $current_term->taxonomy === 'product_cat' ) {
			$descendants = get_term_children( $current_term->term_id, 'product_cat' );
			$cat_ids     = array_merge( [ (int) $current_term->term_id ], is_array( $descendants ) ? $descendants : [] );
			$product_ids = get_posts( [
				'post_type'      => 'product',
				'posts_per_page' => 200,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'tax_query'      => [
					[
						'taxonomy' => 'product_cat',
						'field'    => 'term_id',
						'terms'    => $cat_ids,
					],
				],
			] );
			if ( ! empty( $product_ids ) ) {
				$args['object_ids'] = $product_ids;
			} else {
				return; // No products → no useful tag cloud.
			}
		}

		$tags = get_terms( $args );
		if ( is_wp_error( $tags ) || empty( $tags ) ) {
			return;
		}

		echo $widget_args['before_widget']; // phpcs:ignore
		echo $widget_args['before_title']; // phpcs:ignore
		echo esc_html__( 'Popular tags', 'luwipress-amber' );
		echo $widget_args['after_title']; // phpcs:ignore
		echo '<ul class="lwp-tag-cloud">';
		foreach ( $tags as $t ) {
			printf(
				'<li><a href="%s" rel="tag">%s <span>%d</span></a></li>',
				esc_url( get_term_link( $t ) ),
				esc_html( $t->name ),
				(int) $t->count
			);
		}
		echo '</ul>';
		echo $widget_args['after_widget']; // phpcs:ignore
	}
}

if ( ! function_exists( 'luwipress_amber_render_quick_toggles' ) ) {

	/**
	 * Tiny UX block for "On sale" + "In stock" toggles. URLs are crafted
	 * by appending / removing query strings, preserving every other active
	 * filter so toggles stack with the rest of the panel.
	 *
	 * @param array $widget_args
	 */
	function luwipress_amber_render_quick_toggles( $widget_args ) {
		// Operators may extend / replace the toggles via filter.
		$toggles = apply_filters( 'luwipress_amber_smart_filter_quick_toggles', [
			[
				'param' => 'onsale',
				'label' => __( 'On sale only', 'luwipress-amber' ),
			],
			[
				'param' => 'instock',
				'label' => __( 'In stock only', 'luwipress-amber' ),
			],
		] );
		if ( empty( $toggles ) ) {
			return;
		}

		// Build current URL params. Use $_GET for read-only (we don't act).
		$current_params = [];
		foreach ( $_GET as $k => $v ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( is_string( $v ) ) {
				$current_params[ sanitize_key( $k ) ] = sanitize_text_field( wp_unslash( $v ) );
			}
		}

		echo $widget_args['before_widget']; // phpcs:ignore
		echo $widget_args['before_title']; // phpcs:ignore
		echo esc_html__( 'Quick filters', 'luwipress-amber' );
		echo $widget_args['after_title']; // phpcs:ignore
		echo '<ul class="lwp-quick-toggles">';
		foreach ( $toggles as $t ) {
			$param  = isset( $t['param'] ) ? sanitize_key( $t['param'] ) : '';
			$label  = isset( $t['label'] ) ? (string) $t['label'] : '';
			if ( $param === '' || $label === '' ) {
				continue;
			}
			$is_on  = ! empty( $current_params[ $param ] );
			$params = $current_params;
			if ( $is_on ) {
				unset( $params[ $param ] );
			} else {
				$params[ $param ] = '1';
			}
			$url = add_query_arg( $params, get_pagenum_link( 1, false ) );
			printf(
				'<li><a href="%s" class="lwp-quick-toggle %s">%s%s</a></li>',
				esc_url( $url ),
				$is_on ? 'is-active' : '',
				$is_on ? '<span class="lwp-quick-toggle__check" aria-hidden="true">✓ </span>' : '',
				esc_html( $label )
			);
		}
		echo '</ul>';
		echo $widget_args['after_widget']; // phpcs:ignore
	}
}

if ( ! function_exists( 'luwipress_amber_smart_filters_apply_query' ) ) {

	/**
	 * Apply ?onsale=1 / ?instock=1 to the WooCommerce product query.
	 * Hooks `woocommerce_product_query` so it composes with WC's own
	 * filtering (price, attributes, sort).
	 *
	 * @param WP_Query $q
	 */
	function luwipress_amber_smart_filters_apply_query( $q ) {
		if ( is_admin() || ! is_main_query() ) {
			return;
		}

		// On-sale filter — intersect with WC's running tax_query.
		if ( ! empty( $_GET['onsale'] ) && function_exists( 'wc_get_product_ids_on_sale' ) ) { // phpcs:ignore
			$sale_ids = wc_get_product_ids_on_sale();
			if ( empty( $sale_ids ) ) {
				$sale_ids = [ 0 ]; // force empty result rather than "no filter"
			}
			$existing = (array) $q->get( 'post__in' );
			$q->set( 'post__in', empty( $existing ) ? $sale_ids : array_intersect( $existing, $sale_ids ) );
		}

		// In-stock filter — exclude outofstock visibility term.
		if ( ! empty( $_GET['instock'] ) ) { // phpcs:ignore
			$tax_query = (array) $q->get( 'tax_query' );
			$tax_query[] = [
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => [ 'outofstock' ],
				'operator' => 'NOT IN',
			];
			$q->set( 'tax_query', $tax_query );
		}
	}
	add_action( 'woocommerce_product_query', 'luwipress_amber_smart_filters_apply_query' );
}

if ( ! function_exists( 'luwipress_amber_smart_filters_print_styles' ) ) {

	/**
	 * Sidebar styling — inline-printed only on product archives so it
	 * doesn't pollute other pages. ~1.5 KB.
	 */
	function luwipress_amber_smart_filters_print_styles() {
		if ( is_admin() ) {
			return;
		}
		if ( ! function_exists( 'is_shop' ) ) {
			return;
		}
		if ( ! ( is_shop() || is_product_taxonomy() ) ) {
			return;
		}
		?>
<style id="lwp-amber-smart-filters">
.lwp-smart-filters .lwp-filter-block{
	margin:0;padding:18px 0;background:transparent;
	border:0;border-top:1px solid var(--line,rgba(231,200,150,.16));
}
.lwp-smart-filters .lwp-filter-block__title,
.lwp-smart-filters .widget-title{
	margin:0 0 10px;font-size:11.5px;letter-spacing:.16em;text-transform:uppercase;
	color:var(--ink,#1b1c1c);font-weight:700;
}
.lwp-smart-filters ul{margin:0;padding:0;list-style:none;}
.lwp-smart-filters .woocommerce-widget-layered-nav-list li,
.lwp-smart-filters ul li{margin:0 0 6px;font-size:13px;line-height:1.4;}
.lwp-smart-filters .woocommerce-widget-layered-nav-list a,
.lwp-smart-filters ul li a{color:var(--ink,#1b1c1c);text-decoration:none;
	display:flex;justify-content:space-between;align-items:center;padding:4px 0;
	border-bottom:1px solid transparent;transition:border-color .15s ease;
}
.lwp-smart-filters ul li a:hover{border-bottom-color:var(--amber,#E5A23D);color:var(--amber,#E5A23D);}
.lwp-smart-filters ul li a span,
.lwp-smart-filters .woocommerce-widget-layered-nav-list a .count{
	color:#a39f96;font-size:12px;
}
.lwp-smart-filters .chosen{font-weight:600;}
.lwp-smart-filters .price_slider_amount{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-top:10px;}
.lwp-smart-filters .price_slider_amount .price_label{font-size:12.5px;color:#5b5247;flex:1;}
.lwp-smart-filters .price_slider_amount .button{
	background:var(--amber,#E5A23D);color:#fff;border:0;padding:6px 14px;border-radius:999px;
	font-size:11.5px;letter-spacing:.06em;text-transform:uppercase;cursor:pointer;
}
.lwp-smart-filters .price_slider_wrapper{padding:6px 4px 0}
.lwp-tag-cloud{display:flex;flex-wrap:wrap;gap:6px;}
.lwp-tag-cloud li{margin:0;}
.lwp-tag-cloud li a{
	display:inline-flex;align-items:center;gap:6px;padding:5px 10px;
	background:rgba(231,200,150,.06);border:1px solid var(--line,rgba(231,200,150,.16));border-radius:999px;font-size:12px;border-bottom:0!important;
}
.lwp-tag-cloud li a span{font-size:11px;color:#a39f96;}
.lwp-tag-cloud li a:hover{background:var(--amber,#E5A23D);color:#fff;}
.lwp-tag-cloud li a:hover span{color:rgba(255,255,255,.7);}
.lwp-quick-toggles li{margin-bottom:4px;}
.lwp-quick-toggle{
	display:inline-flex;align-items:center;gap:6px;padding:6px 12px;
	background:rgba(231,200,150,.06);border:1px solid var(--line,rgba(231,200,150,.16));border-radius:999px;font-size:12.5px;
	color:var(--ink,#1b1c1c);text-decoration:none;
	border:1px solid transparent;transition:all .15s ease;
}
.lwp-quick-toggle.is-active{
	background:var(--amber,#E5A23D);color:#fff;border-color:var(--amber,#E5A23D);
}
.lwp-quick-toggle__check{font-weight:700;}
.lwp-quick-toggle:not(.is-active):hover{border-color:var(--amber,#E5A23D);color:var(--amber,#E5A23D);}
.lwp-smart-filters .woocommerce-widget-layered-nav-dropdown__submit{
	margin-top:8px;background:var(--amber,#E5A23D);color:#fff;border:0;
	padding:8px 14px;border-radius:6px;font-size:12px;cursor:pointer;
}
@media (max-width:900px){
	.lwp-smart-filters{display:grid;grid-template-columns:1fr;gap:0;}
	.lwp-smart-filters .lwp-filter-block{padding:12px 14px;margin-bottom:12px;}
}
</style>
		<?php
	}
	add_action( 'wp_head', 'luwipress_amber_smart_filters_print_styles', 99 );
}
