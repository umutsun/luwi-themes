<?php
/**
 * Asset enqueue — fonts, design tokens, WooCommerce loop overrides, animation layer.
 *
 * Intentionally minimal: most page CSS lives inline inside the Elementor Kit
 * JSONs (each section ships its own <style> block). The theme only enqueues
 * the cross-page primitives that Elementor templates rely on.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', function () {
	$ver = LUWIPRESS_GOLD_VERSION;

	// Google Fonts — preconnect + display=swap to avoid layout shift.
	wp_enqueue_style(
		'luwipress-gold-fonts',
		'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap',
		[],
		null
	);

	// Animation layer — page loader, scroll reveal, cart bump.
	// Scripts only; respects prefers-reduced-motion at the JS layer.
	wp_enqueue_script(
		'luwipress-gold-frontend',
		LUWIPRESS_GOLD_URI . '/assets/js/frontend.js',
		[],
		$ver,
		true
	);
}, 20 );

/**
 * THEME CSS at priority 9999 — ensures our stylesheets land AFTER every
 * plugin's CSS (WooCommerce, WCML, ElementsKit Lite, Yoast, etc.) and
 * AFTER any LiteSpeed CSS combine ordering. Specificity wars are won by
 * "last rule wins" when both rules are 0,1,0 — without this Tapadum's
 * plugin stack consistently overrode .lwp-mm li, .lwp-site-header etc.
 */
add_action( 'wp_enqueue_scripts', function () {
	$ver = LUWIPRESS_GOLD_VERSION;

	wp_enqueue_style(
		'luwipress-gold-tokens',
		LUWIPRESS_GOLD_URI . '/assets/css/tokens.css',
		[ 'luwipress-gold-fonts' ],
		$ver
	);

	wp_enqueue_style(
		'luwipress-gold-widgets',
		LUWIPRESS_GOLD_URI . '/assets/css/widgets.css',
		[ 'luwipress-gold-tokens' ],
		$ver
	);

	if ( class_exists( 'WooCommerce' ) ) {
		wp_enqueue_style(
			'luwipress-gold-woo',
			LUWIPRESS_GOLD_URI . '/assets/css/woo-overrides.css',
			[ 'luwipress-gold-widgets' ],
			$ver
		);
	}
}, 9999 );

/**
 * Critical inline stylesheet — written directly into <head> just before
 * </head>. Wins every specificity fight regardless of plugin enqueue
 * order or LiteSpeed combine. Strictly limited to the chrome reset
 * rules that we observed losing on Tapadum's stack:
 *   - <ul> in mega menu shows browser bullets → reset list-style + padding
 *   - <a> in topbar/header/footer shows underlined link → reset
 *   - sticky header z-index conflicts with WCML / chat widget → bump
 */
add_action( 'wp_head', function () {
	?>
<style id="luwipress-gold-critical-reset">
	.lwp-topbar ul,.lwp-topbar ol,.lwp-site-header ul,.lwp-site-header ol,
	.lwp-mm ul,.lwp-mm ol,.lwp-mm-dropdown,.lwp-mm-panel ul,
	.lwp-megabar ul,.lwp-megabar ol,
	.lwp-site-footer ul,.lwp-site-footer ol{
		list-style:none !important;margin:0 !important;padding:0 !important;
	}
	.lwp-topbar a,.lwp-site-header a,.lwp-mm a,.lwp-megabar a,.lwp-site-footer a,
	.lwp-icon-btn{
		text-decoration:none !important;box-shadow:none !important;
	}
	.lwp-site-header{z-index:9999 !important;}
	.lwp-mm-panel,.lwp-mm-dropdown{z-index:10000 !important;}
	body.luwipress-gold .lwp-mm li{
		list-style:none !important;border:0 !important;background:transparent !important;
	}
</style>
	<?php
}, 99999 );

/**
 * Editor styles — show the Gold tokens inside the block editor too.
 */
add_action( 'enqueue_block_editor_assets', function () {
	wp_enqueue_style(
		'luwipress-gold-editor-tokens',
		LUWIPRESS_GOLD_URI . '/assets/css/tokens.css',
		[],
		LUWIPRESS_GOLD_VERSION
	);
} );

/**
 * Preconnect Google Fonts before stylesheet starts downloading. Saves
 * 100–200 ms on first paint.
 */
add_action( 'wp_head', function () {
	echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
}, 0 );
