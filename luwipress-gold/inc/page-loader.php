<?php
/**
 * Server-side page loader.
 *
 * Renders the loader overlay into the DOM as the very first child of
 * <body>, before any theme content paints. Eliminates the
 * "flash-of-content-before-loader" caused by JS-injecting the overlay
 * only after DOMContentLoaded — by that point the page has already
 * been visible to the visitor for ~150-400ms (longer on slow product
 * pages with hero images + Elementor sections).
 *
 * The overlay is positioned `fixed; inset: 0` with an opaque background
 * so content underneath is occluded. JS removes the `lwp-booting`
 * class on <html> when window.load fires (or after a 4s hard cap),
 * which transitions the overlay out via CSS.
 *
 * Customizer toggle: LuwiPress Gold → Performance → Page loader overlay.
 *
 * @package luwipress-gold
 * @since   1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'luwipress_gold_loader_should_render' ) ) {

	/**
	 * Decide whether the loader overlay should be emitted on the
	 * current request. Skipped in admin / AJAX / REST / Customizer
	 * preview / Elementor editor contexts. Operator can suppress
	 * globally via Customizer or via the `luwipress_gold_loader_render`
	 * filter (return false).
	 *
	 * @return bool
	 */
	function luwipress_gold_loader_should_render() {
		if ( is_admin() ) {
			return false;
		}
		if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return false;
		}
		if ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) {
			return false;
		}
		// Elementor editor / preview iframes — the overlay would
		// interfere with editing UX.
		if ( isset( $_GET['elementor-preview'] ) || ( isset( $_GET['action'] ) && $_GET['action'] === 'elementor' ) ) {
			return false;
		}
		$on = (bool) get_theme_mod( 'luwipress_gold_loader_enabled', true );
		return (bool) apply_filters( 'luwipress_gold_loader_render', $on );
	}
}

if ( ! function_exists( 'luwipress_gold_loader_print_head' ) ) {

	/**
	 * Print the boot-class marker + minimal blocking CSS into <head>
	 * at priority 1, so the overlay's "occlude content" rules are
	 * applied before the first paint. The full visual styles for
	 * the loader live in widgets.css (already enqueued); this style
	 * block exists to handle the boot-state ordering only.
	 */
	function luwipress_gold_loader_print_head() {
		if ( ! luwipress_gold_loader_should_render() ) {
			return;
		}
		?>
<style id="lwp-gold-loader-boot">
html.lwp-booting body { overflow: hidden; }
.lwp-loader { will-change: opacity; }
html:not(.lwp-booting) .lwp-loader {
	opacity: 0; visibility: hidden; pointer-events: none;
}
@media (prefers-reduced-motion: reduce) {
	.lwp-loader { transition: opacity .12s ease, visibility .12s ease !important; }
	.lwp-loader-mark, .lwp-loader-arc, .lwp-loader-bar > span { animation: none !important; }
}
</style>
<script id="lwp-gold-loader-boot-mark" data-no-optimize="1" data-no-defer="true" data-cfasync="false">
/* Add the boot class as early as possible so the server-rendered
 * loader overlay covers the page on first paint.
 *
 * 1.7.35 — `data-no-optimize`, `data-no-defer`, `data-cfasync` attributes
 * tell LiteSpeed Cache (and Cloudflare Rocket Loader, when present) to
 * skip JS-defer/combine on this specific script. Without them, LS rewrote
 * `type="text/javascript"` to `type="litespeed/javascript"`, which the
 * browser ignores until LS's combined deferred bundle re-executes after
 * DOMContentLoaded — moving `lwp-booting` arrival to ~900ms post-nav and
 * producing the visible "page first, loader after" flash. The attributes
 * keep the boot mark inline and synchronous; LS still defers the rest of
 * the page's JS. Pairs with the `litespeed_optm_html_filter` callback
 * registered below as belt-and-braces (some older LS builds prefer
 * content-pattern excludes over attribute-based ones). */
document.documentElement.classList.add('lwp-booting');

/* CRITICAL safety timer — runs INDEPENDENTLY of frontend.js. If the
 * theme JS never loads, errors out, or window.load never fires (some
 * resource hangs), this guarantees the loader auto-dismisses after
 * 5 seconds so visitors are never stuck on a blocking overlay. Also
 * force-reveals every [data-lwp-reveal] element in the DOM at the
 * same instant — without this, the JS-driven IntersectionObserver
 * never gets a chance to run and below-fold sections stay opacity:0
 * forever, leaving the visitor with a page that scrolls to blank.
 *
 * Pairs with frontend.js buildLoader().hide() which does the same
 * force-reveal earlier (250ms after window.load) when JS works
 * normally. */
window.setTimeout(function () {
	document.documentElement.classList.remove('lwp-booting');
	var revealEls = document.querySelectorAll('[data-lwp-reveal]:not(.in), [data-lwp-stagger]:not(.in)');
	for (var i = 0; i < revealEls.length; i++) revealEls[i].classList.add('in');
	var l = document.getElementById('lwp-loader');
	if (l) {
		l.classList.add('lwp-loaded');
		window.setTimeout(function () {
			if (l.parentNode) l.parentNode.removeChild(l);
		}, 700);
	}
}, 5000);
</script>
		<?php
	}
	add_action( 'wp_head', 'luwipress_gold_loader_print_head', 1 );
}

/**
 * Programmatic LiteSpeed Cache exclusion for the loader boot mark.
 *
 * Belt-and-braces complement to the inline `data-no-optimize` attribute.
 * Some LiteSpeed Cache builds honour the attribute reliably; others apply
 * the JS-defer/combine pass BEFORE checking attributes. Adding the script
 * to LSCWP's `optm_excludes_js` list via filter guarantees exclusion
 * across every LS version that exposes the filter.
 *
 * The list accepts either inline content snippets or script IDs; we add
 * the unique substring `lwp-gold-loader-boot-mark` (matches both the ID
 * and any inline content), which LS uses as a presence-check against
 * each script tag candidate during its optimization pass.
 *
 * @since 1.7.35 — Vendor / shipping prep: no-cache-config required on
 * customer installs. The fix has to land in plugin code, not in the
 * LiteSpeed admin's "JS Excludes" textarea (which a non-technical store
 * owner would never discover or maintain).
 */
if ( ! function_exists( 'luwipress_gold_loader_litespeed_exclude' ) ) {
	function luwipress_gold_loader_litespeed_exclude( $excludes ) {
		if ( ! is_array( $excludes ) ) {
			$excludes = array();
		}
		$excludes[] = 'lwp-gold-loader-boot-mark';
		return $excludes;
	}
	add_filter( 'litespeed_optm_js_defer_exc', 'luwipress_gold_loader_litespeed_exclude' );
	add_filter( 'litespeed_optm_js_excludes', 'luwipress_gold_loader_litespeed_exclude' );
}

if ( ! function_exists( 'luwipress_gold_loader_render' ) ) {

	/**
	 * Output the loader markup as the first body child. Runs at
	 * `wp_body_open` priority 1 so it precedes any other body-open
	 * insertions (analytics tags, Elementor overlays, etc).
	 */
	function luwipress_gold_loader_render() {
		if ( ! luwipress_gold_loader_should_render() ) {
			return;
		}
		?>
<div class="lwp-loader" id="lwp-loader" aria-hidden="true">
	<div class="lwp-loader-inner">
		<svg class="lwp-loader-mark" viewBox="0 0 60 60" width="60" height="60" aria-hidden="true">
			<circle cx="30" cy="30" r="26" fill="none" stroke="currentColor" stroke-width="2" opacity=".25"/>
			<circle class="lwp-loader-arc" cx="30" cy="30" r="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="40 200"/>
		</svg>
		<span class="lwp-loader-text"><?php esc_html_e( 'Loading', 'luwipress-gold' ); ?></span>
		<span class="lwp-loader-bar"><span></span></span>
	</div>
</div>
		<?php
	}
	add_action( 'wp_body_open', 'luwipress_gold_loader_render', 1 );
}

if ( ! function_exists( 'luwipress_gold_loader_customizer' ) ) {

	/**
	 * Register the on/off toggle in Customize → LuwiPress Gold →
	 * Performance. Re-uses the Performance section if it has already
	 * been registered by another module (e.g. template-redirects.php).
	 */
	function luwipress_gold_loader_customizer( $wp_customize ) {
		$panel_id = 'luwipress_gold';
		if ( ! $wp_customize->get_panel( $panel_id ) ) {
			$wp_customize->add_panel( $panel_id, [
				'title'    => __( 'LuwiPress Gold', 'luwipress-gold' ),
				'priority' => 10,
			] );
		}
		$section_id = 'luwipress_gold_performance';
		if ( ! $wp_customize->get_section( $section_id ) ) {
			$wp_customize->add_section( $section_id, [
				'title'    => __( 'Performance', 'luwipress-gold' ),
				'panel'    => $panel_id,
				'priority' => 60,
			] );
		}
		$wp_customize->add_setting( 'luwipress_gold_loader_enabled', [
			'type'              => 'theme_mod',
			'default'           => true,
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => function ( $v ) { return ! empty( $v ); },
			'transport'         => 'refresh',
		] );
		$wp_customize->add_control( 'luwipress_gold_loader_enabled', [
			'label'       => __( 'Page loader overlay', 'luwipress-gold' ),
			'description' => __( 'Show a brand-aligned loader overlay until the page finishes painting. Server-rendered, so it appears on the first frame instead of after the page has flashed.', 'luwipress-gold' ),
			'section'     => $section_id,
			'type'        => 'checkbox',
			'priority'    => 10,
		] );
	}
	add_action( 'customize_register', 'luwipress_gold_loader_customizer', 30 );
}
