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

	// Design tokens (CSS custom properties) — every Elementor section reads from these.
	wp_enqueue_style(
		'luwipress-gold-tokens',
		LUWIPRESS_GOLD_URI . '/assets/css/tokens.css',
		[ 'luwipress-gold-fonts' ],
		$ver
	);

	// WooCommerce loop card overrides — sale badge, italic gold price ladder,
	// add-to-cart pill. Loaded only when WooCommerce is active.
	if ( class_exists( 'WooCommerce' ) ) {
		wp_enqueue_style(
			'luwipress-gold-woo',
			LUWIPRESS_GOLD_URI . '/assets/css/woo-overrides.css',
			[ 'luwipress-gold-tokens' ],
			$ver
		);
	}

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
