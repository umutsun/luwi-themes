<?php
/**
 * WooCommerce page render fallback — keeps cart / checkout / my-account
 * usable when the page was built with Elementor Pro's WooCommerce widgets
 * but the Pro plugin is not installed. Free Elementor leaves only an empty
 * widget skeleton, so the page renders as a blank middle between header
 * and footer (the same "broken theme" symptom cart-empty.php was meant
 * to guard against, except here cart.php never even gets a chance to run
 * because Elementor takes over `the_content`).
 *
 * Pattern check: if Elementor's `the_content` output contains a Pro-only
 * WC widget (`elementor-widget-woocommerce-{cart,checkout,my-account}`),
 * AND Elementor Pro is NOT active, swap the content for the matching WC
 * shortcode. WooCommerce then loads its standard template chain — which
 * means the theme's branded `woocommerce/cart/cart.php` + `cart-empty.php`
 * override picks up cleanly. Install Pro later and this filter becomes
 * a no-op because the Pro guard short-circuits it.
 *
 * Generic by design — no per-page targeting, no hardcoded post IDs. Any
 * cart/checkout/account page that ships only Pro widgets gets recovered.
 *
 * @package luwipress-gold
 * @since   1.7.7
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'luwipress_gold_wc_page_fallback' ) ) {

	/**
	 * Detect Pro-only WC widget markup in Elementor's rendered content and
	 * replace with the matching WC shortcode output.
	 *
	 * Runs at priority 999 so it fires AFTER Elementor's own `the_content`
	 * filter (priority 9) has produced its skeleton output.
	 *
	 * @param string $content the_content as filtered so far.
	 * @return string
	 */
	function luwipress_gold_wc_page_fallback( $content ) {
		if ( is_admin() || ! function_exists( 'is_cart' ) ) {
			return $content;
		}
		// Pro present? Trust the Pro widgets and bail.
		if ( did_action( 'elementor_pro/init' ) || defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			return $content;
		}
		// Only on the three canonical WC commerce pages, and not WC endpoint sub-URLs
		// (e.g. /my-account/orders/) which WC routes through their own templates.
		$is_cart     = is_cart()         && ! is_wc_endpoint_url();
		$is_checkout = is_checkout()     && ! is_wc_endpoint_url();
		$is_account  = is_account_page() && ! is_wc_endpoint_url();

		if ( ! ( $is_cart || $is_checkout || $is_account ) ) {
			return $content;
		}

		$needles = array(
			'cart'       => 'elementor-widget-woocommerce-cart',
			'checkout'   => 'elementor-widget-woocommerce-checkout',
			'my-account' => 'elementor-widget-woocommerce-my-account',
		);

		$shortcode = '';
		if ( $is_cart     && false !== strpos( $content, $needles['cart'] ) )       $shortcode = '[woocommerce_cart]';
		if ( $is_checkout && false !== strpos( $content, $needles['checkout'] ) )   $shortcode = '[woocommerce_checkout]';
		if ( $is_account  && false !== strpos( $content, $needles['my-account'] ) ) $shortcode = '[woocommerce_my_account]';

		// Also catch the trivially-empty case — Elementor rendered an empty wrapper
		// because the only widget was Pro-gated. Treat that as a fallback trigger
		// on the commerce pages so the storefront stays usable even if the widget
		// class name changes in a future Pro release.
		if ( empty( $shortcode ) ) {
			$stripped = trim( wp_strip_all_tags( $content ) );
			if ( '' === $stripped ) {
				if ( $is_cart )     $shortcode = '[woocommerce_cart]';
				if ( $is_checkout ) $shortcode = '[woocommerce_checkout]';
				if ( $is_account )  $shortcode = '[woocommerce_my_account]';
			}
		}

		if ( empty( $shortcode ) ) {
			return $content;
		}

		// Render the shortcode directly so it picks up the theme's WC template
		// overrides (cart.php / cart-empty.php / form-checkout.php / etc.).
		return do_shortcode( $shortcode );
	}

	add_filter( 'the_content', 'luwipress_gold_wc_page_fallback', 999 );
}
