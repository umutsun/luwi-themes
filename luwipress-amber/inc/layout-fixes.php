<?php
/**
 * Cross-template layout safety net.
 *
 * Small CSS rules that need to load on every page regardless of the
 * Elementor Kit, Customizer choices, or active sidebar configuration —
 * fixes that prevent visual regressions when page content is shorter or
 * narrower than the viewport.
 *
 * Currently includes:
 *
 *  1. Footer-color leak fix — on tall viewports, a short page ends with
 *     a strip of empty body background below the footer (cream / off-white
 *     by default). Visitors perceive that strip as a layout bug. Pinning
 *     the html element to the footer's background color makes any leak
 *     below the footer match the footer instead of the page body — so the
 *     footer visually extends to the bottom of the viewport even when DOM
 *     positioning leaves room below it. No layout changes, no flex / grid
 *     reflow, no sticky-positioning side effects.
 *
 *  2. Min-page-height enforcement — `body { min-height: 100vh }` keeps
 *     short pages from collapsing to header+footer height; combined with
 *     the html background fix above, this guarantees the leak strip is
 *     always footer-colored rather than ever showing the html default
 *     (browser white).
 *
 * Footer color is read from Customizer (`luwipress_amber_footer_bg` theme
 * mod). When the operator hasn't customized it, falls back to the same
 * `#1b1c1c` ink token that ships with the theme — matching the default
 * footer skin shipped in the Elementor Kit.
 *
 * Print is inline so the rule lands in the very first wp_head and is
 * applied before the body even paints. ~140 bytes.
 *
 * @package luwipress-amber
 * @since   1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'luwipress_amber_resolve_footer_bg_token' ) ) {

	/**
	 * Resolve the footer background color, in priority order:
	 *   1. Customizer theme_mod `luwipress_amber_footer_bg`
	 *   2. The ink/text token `luwipress_amber_ink` (default footer skin
	 *      uses ink color for bg in the shipped Kit)
	 *   3. Hard-coded `#1b1c1c` fallback
	 *
	 * Result is always a valid CSS color string. Sanitized to a small
	 * allowlist so a corrupted theme_mod can't inject CSS.
	 *
	 * @return string
	 */
	function luwipress_amber_resolve_footer_bg_token() {
		$candidates = [
			get_theme_mod( 'luwipress_amber_footer_bg' ),
			get_theme_mod( 'luwipress_amber_ink' ),
			'#1b1c1c',
		];
		foreach ( $candidates as $c ) {
			if ( ! is_string( $c ) || $c === '' ) {
				continue;
			}
			$c = trim( $c );
			// Accept #rgb, #rrggbb, #rrggbbaa, rgb(), rgba(), hsl(), hsla(), or
			// CSS named colors (alpha allowed). Strict regex keeps it tight.
			if ( preg_match( '/^#(?:[0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $c ) ) {
				return $c;
			}
			if ( preg_match( '/^(?:rgb|rgba|hsl|hsla)\([^)]+\)$/i', $c ) ) {
				return $c;
			}
			if ( preg_match( '/^[a-z]+$/i', $c ) ) {
				return $c;
			}
		}
		return '#1b1c1c';
	}
}

if ( ! function_exists( 'luwipress_amber_print_layout_fixes' ) ) {

	function luwipress_amber_print_layout_fixes() {
		if ( is_admin() ) {
			return;
		}
		$footer_bg = luwipress_amber_resolve_footer_bg_token();
		?>
<style id="lwp-amber-layout-fixes">
html{background-color:<?php echo esc_html( $footer_bg ); ?>}
body{min-height:100vh}
</style>
		<?php
	}
	// Priority 1 so it lands ahead of any plugin-injected style.
	add_action( 'wp_head', 'luwipress_amber_print_layout_fixes', 1 );
}

if ( ! function_exists( 'luwipress_amber_print_archive_hero_fixes' ) ) {

	/**
	 * Archive hero image fit override (product_cat archives only).
	 *
	 * The default split-hero in `woocommerce/archive-product.php` puts the
	 * term thumbnail into a `.lwp-archive-header__media` container with
	 * `aspect-ratio: 4 / 3` and `object-fit: cover` on the image. That works
	 * fine when the term thumbnail is a full-bleed lifestyle photo — but
	 * most operators upload **product-style PNGs** (a single instrument or
	 * object centered on a transparent / white background, with significant
	 * built-in padding around the subject). On those:
	 *
	 *   - `cover` zooms but the subject still appears small because the
	 *     useful pixels live in the center of the image
	 *   - `contain` letterboxes the subject inside the 4:3 box, leaving
	 *     visible empty bands top + bottom
	 *
	 * Fix: drop the fixed aspect-ratio so the container hugs the image's
	 * natural height, switch to `object-fit: contain`, and cap with a
	 * sensible `max-height` so a tall portrait image (like a baglama) does
	 * not stretch the whole hero band. Container becomes a centered
	 * flex shell so the subject sits visually balanced even when its
	 * intrinsic aspect ratio differs from its sibling text column.
	 *
	 * Scoped to WooCommerce category archives — does not affect leaf
	 * single-product layouts or Elementor pages with their own image hero.
	 */
	function luwipress_amber_print_archive_hero_fixes() {
		if ( is_admin() ) {
			return;
		}
		// Only on a product_cat term archive — the only place
		// .lwp-archive-header is rendered.
		if ( ! function_exists( 'is_tax' ) || ! is_tax( 'product_cat' ) ) {
			return;
		}
		?>
<style id="lwp-amber-archive-hero-fix">
.lwp-archive-header--split{align-items:flex-start}
.lwp-archive-header__media{
	aspect-ratio:auto !important;
	background:transparent !important;
	display:flex;
	align-items:center;
	justify-content:center;
	padding:0;
	min-height:0;
	border-radius:var(--radius-card,8px);
	overflow:hidden;
}
.lwp-archive-header__img{
	width:100% !important;
	height:auto !important;
	max-height:520px;
	object-fit:contain !important;
	display:block;
}
@media (max-width:900px){
	.lwp-archive-header__media{aspect-ratio:auto !important}
	.lwp-archive-header__img{max-height:360px}
}
</style>
		<?php
	}
	add_action( 'wp_head', 'luwipress_amber_print_archive_hero_fixes', 99 );
}

if ( ! function_exists( 'luwipress_amber_print_pdp_spacing_fix' ) ) {

	/**
	 * Single-product PDP spacing fix.
	 *
	 * On WooCommerce single-product pages the visual flow is:
	 *
	 *   .product .images  +  .product .summary    ← 2-col row
	 *   .woocommerce-tabs                         ← Description / Additional Info
	 *   .related.products                         ← WC default related
	 *
	 * When the product summary column is short (just a few lines of
	 * short_description + price + add-to-cart), the gallery column
	 * keeps its full thumbnail-strip height, leaving a visible chasm
	 * (~300-450px) between the bottom of the gallery and the start of
	 * the tabs. The fix tightens the trailing margin on the row
	 * wrappers and guarantees the tabs section starts no more than
	 * 32-48px below whichever column ended last.
	 *
	 * Generic — applies to any WC store using the default 2-col
	 * single-product layout. Operator can disable via the
	 * `luwipress_amber_pdp_spacing_fix` filter returning false.
	 */
	function luwipress_amber_print_pdp_spacing_fix() {
		if ( is_admin() ) {
			return;
		}
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		if ( ! apply_filters( 'luwipress_amber_pdp_spacing_fix', true ) ) {
			return;
		}
		?>
<style id="lwp-amber-pdp-spacing-fix">
.woocommerce div.product{margin-bottom:0}
.woocommerce div.product .images,
.woocommerce div.product div.images,
.woocommerce div.product .summary,
.woocommerce div.product div.summary{margin-bottom:24px !important}
.woocommerce div.product .woocommerce-tabs,
.woocommerce-tabs.wc-tabs-wrapper{
	margin-top:32px !important;clear:both;
}
.woocommerce div.product .related.products,
.woocommerce div.product .upsells.products{
	margin-top:48px !important;clear:both;
}
.woocommerce-tabs .wc-tab,
.woocommerce-tabs .panel{padding-top:24px !important}
@media (max-width:900px){
	.woocommerce div.product .images,
	.woocommerce div.product .summary{margin-bottom:16px !important}
	.woocommerce div.product .woocommerce-tabs{margin-top:24px !important}
}
</style>
		<?php
	}
	add_action( 'wp_head', 'luwipress_amber_print_pdp_spacing_fix', 99 );
}

if ( ! function_exists( 'luwipress_amber_print_singular_top_spacing' ) ) {

	/**
	 * Mobile / sub-mobile breathing room above the breadcrumb on
	 * singular post + page templates. The sticky header sits flush
	 * against the .lwp-page container with no margin on phones, which
	 * makes the first line of the breadcrumb crash into the header
	 * underline. Adding 24-32px top padding only on viewports under
	 * 900px restores breathing room without changing desktop spacing.
	 *
	 * Also enforces the tag-pill rendering on single posts — the
	 * theme's widgets.css ships .lwp-journal-tag with border-radius:999px
	 * already, but some hosting LiteSpeed minify configs strip lone
	 * border-radius from CSS classes that look unused. Re-asserting it
	 * inline guarantees the pill shape ships even when the merged CSS
	 * has been mangled.
	 */
	function luwipress_amber_print_singular_top_spacing() {
		if ( is_admin() ) {
			return;
		}
		if ( ! ( is_singular( 'post' ) || is_singular( 'page' ) ) ) {
			return;
		}
		?>
<style id="lwp-amber-singular-top-spacing">
/* 1.6.7: bumped from 24px → 40px on tablet, 32px → 56px on phone, plus
 * added explicit margin-top on .lwp-crumb at every nesting depth.
 * Operator screenshot showed breadcrumb visually touching the bottom
 * of the sticky header on ~620px viewport — the prior selector chain
 * (`.lwp-page > .lwp-page-container > .lwp-crumb`) didn't match the
 * single.php structure where breadcrumb is nested INSIDE `<article>`,
 * so the rule silently no-op'd. New selector matches breadcrumb at
 * any depth under `main.lwp-page` / `main.lwp-journal-single` /
 * `main.lwp-elementor-page`. !important guards against LiteSpeed
 * minify reordering and Elementor responsive controls overriding. */
@media (max-width:900px){
	main.lwp-page,
	main.lwp-journal-single,
	main.lwp-elementor-page,
	.lwp-page,
	.lwp-elementor-page{padding-top:40px !important}
	main.lwp-page .lwp-crumb,
	main.lwp-journal-single .lwp-crumb,
	main.lwp-elementor-page .lwp-crumb,
	.lwp-page .lwp-crumb,
	.lwp-elementor-page .lwp-crumb{margin-top:12px !important;margin-bottom:20px}
}
@media (max-width:600px){
	main.lwp-page,
	main.lwp-journal-single,
	main.lwp-elementor-page,
	.lwp-page,
	.lwp-elementor-page{padding-top:56px !important}
	main.lwp-page .lwp-crumb,
	main.lwp-journal-single .lwp-crumb,
	main.lwp-elementor-page .lwp-crumb,
	.lwp-page .lwp-crumb,
	.lwp-elementor-page .lwp-crumb{margin-top:16px !important}
}
.lwp-journal-tag{
	display:inline-block !important;
	padding:5px 12px !important;
	border-radius:999px !important;
	background:var(--bg-alt,#f6f3f2) !important;
	color:var(--ink-soft,#4d4635) !important;
	text-decoration:none !important;
	font-size:12.5px !important;
	transition:background .15s,color .15s;
}
.lwp-journal-tag:hover{
	background:var(--primary,#735c00) !important;
	color:#fff !important;
}
.lwp-journal-tags{
	max-width:720px;margin:48px auto 0;padding:24px 0 0;
	border-top:1px solid var(--line,#e8e2d3);
	display:flex;gap:10px;align-items:center;flex-wrap:wrap;
}
.lwp-journal-tags__label{
	font-family:var(--mono,monospace);font-size:11px;letter-spacing:.14em;
	text-transform:uppercase;color:var(--muted,#7f7663);margin-right:4px;
}
</style>
		<?php
	}
	add_action( 'wp_head', 'luwipress_amber_print_singular_top_spacing', 99 );
}
