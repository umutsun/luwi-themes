<?php
/**
 * Single-product page polish — hooks that adjust the WooCommerce
 * `summary` block to match the Tapadum Gold reference. Pure WC hooks
 * so they coexist with variations / 3rd-party PDP plugins.
 *
 * @package luwipress-gold
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Eyebrow ABOVE the product title — primary category + master/maker.
 * Hooks at priority 3, before WC's `woocommerce_template_single_title`
 * (priority 5). Renders inside the `.summary` block so the buy column
 * gets the eyebrow → title → price stack from the reference.
 */
add_action( 'woocommerce_single_product_summary', function () {
	if ( ! function_exists( 'wc_get_product' ) ) return;
	$product = wc_get_product( get_the_ID() );
	if ( ! $product ) return;

	$parts = [];
	$cat_ids = $product->get_category_ids();
	if ( ! empty( $cat_ids ) ) {
		$cat = get_term( (int) $cat_ids[0], 'product_cat' );
		if ( $cat && ! is_wp_error( $cat ) ) $parts[] = $cat->name;
	}
	$maker = '';
	if ( taxonomy_exists( 'pa_master' ) ) {
		$mt = wp_get_post_terms( $product->get_id(), 'pa_master', [ 'fields' => 'names' ] );
		if ( ! is_wp_error( $mt ) && ! empty( $mt ) ) $maker = $mt[0];
	}
	if ( ! $maker ) {
		$m_meta = (string) get_post_meta( $product->get_id(), '_lwp_gold_maker', true );
		if ( $m_meta ) $maker = $m_meta;
	}
	if ( $maker ) {
		/* translators: %s: master / maker name */
		$parts[] = sprintf( __( 'by %s', 'luwipress-gold' ), $maker );
	}
	if ( empty( $parts ) ) return;

	echo '<span class="lwp-pdp-eyebrow lwp-eyebrow">— ' . esc_html( implode( ' · ', $parts ) ) . '</span>';
}, 3 );

/**
 * Save €X badge appended to the price HTML on single product pages
 * when the product is on sale. Calculation uses regular vs. sale price;
 * variable products use the get_variation_sale_price min/max range.
 *
 * Filtered only on `is_product()` so loop cards keep the cleaner price
 * format (no badge — reference loop card doesn't show "save" amount).
 */
add_filter( 'woocommerce_get_price_html', function ( $html, $product ) {
	if ( ! is_product() ) return $html;
	if ( ! is_main_query() ) return $html;
	if ( ! $product->is_on_sale() ) return $html;

	$reg = (float) $product->get_regular_price();
	$sal = (float) $product->get_sale_price();

	// Variable products — cheapest sale to its regular range.
	if ( $product->is_type( 'variable' ) ) {
		$prices    = $product->get_variation_prices( true );
		$reg_arr   = $prices['regular_price'];
		$sale_arr  = $prices['price'];
		if ( ! empty( $reg_arr ) && ! empty( $sale_arr ) ) {
			$reg = max( array_map( 'floatval', $reg_arr ) );
			$sal = min( array_map( 'floatval', $sale_arr ) );
		}
	}

	if ( $reg <= 0 || $sal <= 0 || $sal >= $reg ) return $html;

	$saved = $reg - $sal;
	$badge = sprintf(
		/* translators: %s: amount saved (formatted price) */
		'<span class="lwp-pdp-save">' . esc_html__( 'Save %s', 'luwipress-gold' ) . '</span>',
		wc_price( $saved )
	);
	return $html . ' ' . $badge;
}, 20, 2 );

/**
 * Move the WC short-description into a dedicated wrapper class so we
 * can style it as the "lead paragraph" beneath the price in the buy
 * column. WC outputs it at priority 20 inside summary; we don't move
 * it, just decorate it via JS-free body class.
 */
add_action( 'wp_body_open', function () {
	if ( is_product() ) {
		echo '<style>.summary .woocommerce-product-details__short-description{font-size:15.5px;line-height:1.7;color:var(--ink-soft,#4d4635);margin:0 0 28px;max-width:54ch}</style>';
	}
} );

/**
 * Stock indicator → branded pill. WC ships a plain `<p class="stock
 * out-of-stock">Out of stock</p>`. Wrap with our pill style.
 */
add_filter( 'woocommerce_get_availability', function ( $availability, $product ) {
	if ( ! is_product() ) return $availability;
	if ( empty( $availability['availability'] ) || empty( $availability['class'] ) ) return $availability;

	$class = $availability['class']; // in-stock | out-of-stock | available-on-backorder
	$label = $availability['availability'];

	$pill_class = 'lwp-pdp-stock';
	switch ( $class ) {
		case 'in-stock':                $pill_class .= ' lwp-pdp-stock--in';      break;
		case 'out-of-stock':            $pill_class .= ' lwp-pdp-stock--out';     break;
		case 'available-on-backorder':  $pill_class .= ' lwp-pdp-stock--backord'; break;
	}

	$availability['availability'] = '<span class="' . esc_attr( $pill_class ) . '"><span class="lwp-pdp-stock__dot" aria-hidden="true"></span>' . esc_html( $label ) . '</span>';
	return $availability;
}, 10, 2 );
