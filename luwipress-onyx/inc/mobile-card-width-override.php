<?php
/**
 * Mobile card width override — guaranteed-90% layer at `wp_head` p999.
 *
 * Problem this solves:
 *   LuwiPress core emits the Elementor Kit CSS inline at `wp_head` p99.
 *   When the operator-curated Kit CSS contains pre-V32 baseline rules
 *   that set `ul.products { padding-left: 20px !important }` (and similar
 *   high-priority WC selectors), every later attempt to override them
 *   from within Kit CSS itself ran into a wall: same media query, same
 *   `!important` weight, same source — cascade order didn't help because
 *   the override layer landed inside the same `<style>` block, not after.
 *
 *   Stylesheet enqueue (`widgets.css`) runs at `wp_head` p10 — that's
 *   BEFORE the kit at p99, so a widgets.css patch loses the cascade race.
 *
 *   This module hooks at `wp_head` priority 999 and emits a dedicated
 *   `<style id="lwp-onyx-mobile-90">` block AFTER the kit. Same selector,
 *   later in the document → cascade winner regardless of specificity.
 *
 * What we apply (mobile only, max-width:767px):
 *   - Strip outer padding from common WC container chains
 *     (.lwp-page-container, ul.products[class*="columns-"], elementor
 *     containers) so the products grid spans the viewport.
 *   - Force 1-column grid in the products list (operator preference —
 *     2-col mobile creates 50vw cards that feel cramped on small phones).
 *   - Set product/category/blog post cards to a uniform `width: 90%`
 *     with auto margins so every card surface (product loop, blog list,
 *     single post body, archive list) reads as the same width to the eye.
 *   - Restore the product card flex column + button bottom-anchor in
 *     case any other layer turned them off — defensive.
 *
 * Cheap by design: a single `wp_head` action that echoes ~1.5 KB of
 * inline CSS. No DB hit, no enqueue overhead, no priority renegotiation
 * with sister plugins.
 *
 * Operator toggle: filter `luwipress_onyx_mobile_90_enabled` returning
 * `false` disables the entire module. Useful when a child theme provides
 * its own mobile width strategy.
 *
 * @package luwipress-onyx
 * @since   1.7.28
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'luwipress_onyx_emit_mobile_card_width_override' ) ) {

	/**
	 * Echo the mobile-90% override CSS at `wp_head` priority 999, AFTER
	 * the LuwiPress Kit CSS (p99) so the cascade resolves in our favour
	 * without escalating specificity wars.
	 */
	function luwipress_onyx_emit_mobile_card_width_override() {
		if ( is_admin() ) {
			return;
		}
		/**
		 * Allow operators / child themes to suppress this layer entirely.
		 *
		 * @param bool $enabled Default true.
		 */
		if ( ! (bool) apply_filters( 'luwipress_onyx_mobile_90_enabled', true ) ) {
			return;
		}
		echo "\n<style id=\"lwp-onyx-mobile-90\">\n";
		echo "@media (max-width:767px){";
		// Outer Elementor + theme container padding zero — let content surfaces breathe.
		echo "html body main :is(.e-con-boxed,.e-con-full,.e-con,.elementor-container,.elementor-widget-wrap,.elementor-column){padding:0!important}";
		// Theme containers (lwp-page-container et al) — padding zero, full bleed.
		echo "html body main .lwp-page-container,html body main.lwp-page,html body main.lwp-shop-archive,html body main.lwp-blog-archive,html body main.lwp-blog-single,html body section.lwp-shop-results,html body .lwp-shop-grid-wrap{padding:0!important;margin:0!important;width:100%!important;max-width:100%!important}";
		// Products UL — full-viewport grid with vertical stack on mobile.
		// We DON'T set ul width to 90vw because then cards-as-100%-of-ul
		// would inherit parent padding constraints from upstream WC core
		// rules (`.woocommerce ul.products { padding-left: 20px }`).
		// Instead UL stays full-bleed and grid-justifies its single-
		// column children to center — see card rule below.
		// 1.7.35 fix — classic break-out negative-margin pattern. Without
		// it, an Elementor section with `padding-left: 32px` pinned ul
		// at x=32 while width grew to 100vw, so ul bled 32px past the
		// viewport's right edge and cards drifted right. `margin: 0
		// calc(50% - 50vw)` shifts ul left by the parent's offset-from-
		// viewport-centre — so a 100vw element renders cleanly at
		// x=0→100vw regardless of how deeply nested the container chain
		// pads its content.
		echo "html body main ul.products,html body main ul.products[class*=\"columns-\"]{width:100vw!important;max-width:100vw!important;padding:0!important;margin-top:0!important;margin-bottom:0!important;margin-left:calc(50% - 50vw)!important;margin-right:calc(50% - 50vw)!important;grid-template-columns:1fr!important;justify-items:center!important;gap:14px!important}";
		// Card surface — uniform 90vw width across product loop cards,
		// product-category cards, blog post cards (list + archive +
		// single body), AND single editorial pages (body.page profile
		// pages like /kabak-kemane/ that used to run edge-to-edge).
		// Using `vw` (viewport units) instead of `%` keeps the width
		// independent of every parent's padding decisions — visitor sees
		// the SAME 90% width regardless of which surface they're on.
		echo "html body ul.products li.product,html body ul.products li.product-category,html body.woocommerce ul.products li.product,html body.woocommerce-page ul.products li.product{width:90vw!important;max-width:90vw!important;margin:0!important;box-sizing:border-box!important}";
		echo "html body :is(.blog main article.post,.archive main article.post,.single-post main article.post,.single main>article.post,main.lwp-blog-archive article){width:90vw!important;max-width:90vw!important;margin-left:auto!important;margin-right:auto!important;box-sizing:border-box!important}";
		echo "html body :is(body.page main > article,body.page main > .lwp-page-container > article,body.page main .lwp-content > *:not(.lwp-hero):not(.lwp-fullbleed),body.page-template-default main > article,body.single-page main > article){width:90vw!important;max-width:90vw!important;margin-left:auto!important;margin-right:auto!important;box-sizing:border-box!important}";
		// Archive page headers (product_cat archive term description /
		// blog archive intro). DOM has `.lwp-archive-header` directly
		// inside `.lwp-page-container` — not an `article.post` — so the
		// generic article rule above misses it. Same 90vw treatment.
		echo "html body :is(.archive main .lwp-archive-header,.archive main .lwp-archive-header__copy,.tax-product_cat main .lwp-archive-header,.archive main .term-description,.archive main > .lwp-page-container > header){width:90vw!important;max-width:90vw!important;margin-left:auto!important;margin-right:auto!important;box-sizing:border-box!important}";
		// Card flex column + button bottom-anchor — defensive so loop
		// cards keep CTA pinned to the bottom even when Kit CSS gets
		// reset to baseline. CTA matches every WC button variant
		// (descendant selector covers wrapped + class-variant buttons).
		echo ".woocommerce ul.products li.product,.woocommerce-page ul.products li.product{display:flex!important;flex-direction:column!important;height:100%!important;align-self:stretch!important}";
		echo ".woocommerce ul.products li.product .button,.woocommerce ul.products li.product a.button,.woocommerce ul.products li.product .add_to_cart_button,.woocommerce ul.products li.product [class*=\"product_type_\"],.woocommerce ul.products li.product .woocommerce-loop-product__buttons{margin-top:auto!important;align-self:stretch!important;box-sizing:border-box!important}";
		// 1.7.35 — Subcategory tile grid on category archives. The grid
		// sits directly inside `main.lwp-shop-archive` (no `lwp-page-
		// container` wrapper) and the container-padding-stripping rule
		// above leaves it flush to the viewport edges. Add a small
		// outer gutter on mobile so tiles breathe without changing the
		// desktop grid (covered by widgets.css `(max-width: 720px)`).
		echo "html body main.lwp-shop-archive .lwp-archive-subcat-grid{padding-left:12px!important;padding-right:12px!important;box-sizing:border-box!important}";
		echo "}\n</style>\n";
	}
	add_action( 'wp_head', 'luwipress_onyx_emit_mobile_card_width_override', 999 );
}
