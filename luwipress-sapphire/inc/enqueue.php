<?php
/**
 * Asset enqueue — fonts, Sapphire design system, interactions.
 *
 * The Sapphire design system ships as a set of stylesheets, loaded in
 * cascade order:
 *   sapphire.css                  → tokens (dark :root + [data-theme=light]) + base + buttons
 *   sapphire-sections.css         → home chrome / hero / trust / overview / plans
 *   sapphire-sections2.css        → grid / areas / testimonials / lifestyle / news / contact / footer / FAQ
 *   sapphire-pages.css            → sub-page styles (integrations / plan / about / blog)
 *   sapphire-product.css          → pricing matrix + rich plan detail
 *   sapphire-page-contact-search.css → contact + search pages
 *   tokens.css                → thin brand-knob bridge (LAST, so Customizer overrides win)
 *
 * sapphire.js drives the theme toggle, mobile drawer, mega menu, scroll
 * reveal, FAQ + testimonial carousels, the client-rendered Listings
 * filter, the integrations filter, sticky CTA and
 * sticky CTA. Null-guarded — safe on every page.
 *
 * @package luwipress-sapphire
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', function () {
	$base_ver = LUWIPRESS_SAPPHIRE_VERSION;
	$dir      = LUWIPRESS_SAPPHIRE_DIR;
	$uri      = LUWIPRESS_SAPPHIRE_URI;

	$asset_ver = function ( $rel ) use ( $base_ver, $dir ) {
		$abs = $dir . $rel;
		return $base_ver . '.' . ( file_exists( $abs ) ? filemtime( $abs ) : '0' );
	};

	// Google Fonts — Space Grotesk (display, techy geometric headlines)
	// + Inter (body / UI) + JetBrains Mono (eyebrows / code / numerics).
	// display=swap to avoid layout shift.
	wp_enqueue_style(
		'luwipress-sapphire-fonts',
		'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap',
		array(),
		null
	);

	// Shared interactions — theme toggle, drawer, mega, reveal, listings,
	// sticky CTA, scroll reveal. Buster `?cb=` survives LiteSpeed's
	// "Remove Query Strings" (which strips ?ver=).
	wp_enqueue_script(
		'luwipress-sapphire-app',
		$uri . '/assets/js/sapphire.js?cb=' . $asset_ver( '/assets/js/sapphire.js' ),
		array(),
		null,
		true
	);

	// Keep sapphire.js out of LiteSpeed Defer/Delay so the theme toggle + reveal
	// fire on first paint rather than first interaction.
	add_filter( 'script_loader_tag', function ( $tag, $handle ) {
		if ( 'luwipress-sapphire-app' === $handle ) {
			$tag = str_replace( ' src=', ' data-no-optimize="1" data-no-defer="true" data-cfasync="false" src=', $tag );
		}
		return $tag;
	}, 10, 2 );

	// Ported Gold fork-tree interaction layer — drives the .lwp-* Elementor
	// widget suite (testimonials carousel, countdown, stat count-up, search
	// overlay, mini-cart drawer, PDP variations, tap-pill shop filters) and the
	// WooCommerce pages. Distinct scope from sapphire.js (theme chrome).
	wp_enqueue_script(
		'luwipress-sapphire-frontend',
		$uri . '/assets/js/frontend.js?cb=' . $asset_ver( '/assets/js/frontend.js' ),
		array(),
		null,
		true
	);

	// Shop archive surfaces — price slider + optional load-more (inherited).
	if ( function_exists( 'is_woocommerce' ) && ( is_shop() || is_product_taxonomy() || is_product_category() || is_product_tag() ) ) {
		wp_enqueue_script( 'wc-price-slider' );

		if ( get_theme_mod( 'luwipress_sapphire_shop_loadmore', true ) ) {
			wp_enqueue_script(
				'luwipress-sapphire-loadmore',
				$uri . '/assets/js/loadmore.js',
				array(),
				$asset_ver( '/assets/js/loadmore.js' ),
				true
			);
			wp_localize_script( 'luwipress-sapphire-loadmore', 'LWP_SAPPHIRE_LM', array(
				'i18n' => array(
					'load_more' => __( 'Load more', 'luwipress-sapphire' ),
					'loading'   => __( 'Loading…', 'luwipress-sapphire' ),
					'no_more'   => __( 'You\'ve reached the end.', 'luwipress-sapphire' ),
					'error'     => __( 'Couldn\'t load more — try again.', 'luwipress-sapphire' ),
				),
				'mode' => (string) get_theme_mod( 'luwipress_sapphire_shop_loadmore_mode', 'infinite' ),
			) );
		}
	}
}, 20 );

/**
 * LiteSpeed JS-defer/delay exclusion for sapphire.js.
 */
if ( ! function_exists( 'luwipress_sapphire_js_ls_excludes' ) ) {
	function luwipress_sapphire_js_ls_excludes( $excludes ) {
		if ( ! is_array( $excludes ) ) {
			$excludes = array();
		}
		$excludes[] = 'luwipress-sapphire-elementor/assets/js/sapphire.js';
		$excludes[] = 'luwipress-sapphire-app';
		$excludes[] = 'luwipress-sapphire-elementor/assets/js/frontend.js';
		$excludes[] = 'luwipress-sapphire-frontend';
		return $excludes;
	}
	add_filter( 'litespeed_optm_js_defer_exc', 'luwipress_sapphire_js_ls_excludes' );
	add_filter( 'litespeed_optm_js_excludes',  'luwipress_sapphire_js_ls_excludes' );
	add_filter( 'litespeed_optm_js_delay_inc', 'luwipress_sapphire_js_ls_excludes' );
}

/**
 * Sapphire design system CSS at priority 9999 — lands AFTER every plugin's CSS
 * (WooCommerce, Elementor, Yoast, …) and after any LiteSpeed CSS combine, so
 * "last rule wins" goes our way. tokens.css ships LAST so the brand-knob :root
 * + the inline Customizer overrides (output-css.php) win the accent tokens.
 */
add_action( 'wp_enqueue_scripts', function () {
	$base_ver = LUWIPRESS_SAPPHIRE_VERSION;
	$dir      = LUWIPRESS_SAPPHIRE_DIR;
	$uri      = LUWIPRESS_SAPPHIRE_URI;

	$asset_ver = function ( $rel ) use ( $base_ver, $dir ) {
		$abs = $dir . $rel;
		return $base_ver . '.' . ( file_exists( $abs ) ? filemtime( $abs ) : '0' );
	};

	$sheets = array(
		'luwipress-sapphire-design'         => array( '/assets/css/sapphire.css',                     array( 'luwipress-sapphire-fonts' ) ),
		'luwipress-sapphire-sections'       => array( '/assets/css/sapphire-sections.css',            array( 'luwipress-sapphire-design' ) ),
		'luwipress-sapphire-sections2'      => array( '/assets/css/sapphire-sections2.css',           array( 'luwipress-sapphire-sections' ) ),
		'luwipress-sapphire-pages'          => array( '/assets/css/sapphire-pages.css',               array( 'luwipress-sapphire-sections2' ) ),
		'luwipress-sapphire-product'        => array( '/assets/css/sapphire-product.css',             array( 'luwipress-sapphire-pages' ) ),
		'luwipress-sapphire-contact-search' => array( '/assets/css/sapphire-page-contact-search.css', array( 'luwipress-sapphire-product' ) ),
		// Ported Gold fork-tree suite — Elementor widget styles + WooCommerce chrome.
		'luwipress-sapphire-widgets'        => array( '/assets/css/widgets.css',                  array( 'luwipress-sapphire-contact-search' ) ),
		'luwipress-sapphire-woo-overrides'  => array( '/assets/css/woo-overrides.css',            array( 'luwipress-sapphire-widgets' ) ),
		'luwipress-sapphire-wc'             => array( '/assets/css/sapphire-wc.css',              array( 'luwipress-sapphire-woo-overrides' ) ),
		'luwipress-sapphire-tokens'         => array( '/assets/css/tokens.css',                   array( 'luwipress-sapphire-wc' ) ),
	);
	foreach ( $sheets as $handle => $cfg ) {
		wp_enqueue_style( $handle, $uri . $cfg[0], $cfg[1], $asset_ver( $cfg[0] ) );
	}
}, 9999 );

/**
 * Keep the theme's stylesheets OUT of LiteSpeed CSS optimization (UCSS +
 * combine), which drops selectors for widgets inserted after the crawl.
 */
add_filter( 'style_loader_tag', function ( $tag, $handle ) {
	$skip = array(
		'luwipress-sapphire-design', 'luwipress-sapphire-sections', 'luwipress-sapphire-sections2',
		'luwipress-sapphire-pages', 'luwipress-sapphire-product', 'luwipress-sapphire-contact-search',
		'luwipress-sapphire-widgets', 'luwipress-sapphire-woo-overrides', 'luwipress-sapphire-wc', 'luwipress-sapphire-tokens',
	);
	if ( in_array( $handle, $skip, true ) ) {
		$tag = str_replace( ' href=', ' data-no-optimize="1" data-no-defer="1" data-cfasync="false" href=', $tag );
	}
	return $tag;
}, 10, 2 );

add_filter( 'litespeed_optm_css_excludes', 'luwipress_sapphire_css_ls_excludes' );
add_filter( 'litespeed_optm_ucss_file_exc_inline_gen', 'luwipress_sapphire_css_ls_excludes' );
if ( ! function_exists( 'luwipress_sapphire_css_ls_excludes' ) ) {
	function luwipress_sapphire_css_ls_excludes( $excludes ) {
		if ( ! is_array( $excludes ) ) {
			$excludes = array();
		}
		foreach ( array( 'sapphire.css', 'sapphire-sections.css', 'sapphire-sections2.css', 'sapphire-pages.css', 'sapphire-product.css', 'sapphire-page-contact-search.css', 'widgets.css', 'woo-overrides.css', 'sapphire-wc.css', 'tokens.css' ) as $f ) {
			$excludes[] = 'luwipress-sapphire-elementor/assets/css/' . $f;
		}
		return $excludes;
	}
}

/**
 * Editor styles — surface the Sapphire design + tokens inside the block editor.
 */
add_action( 'enqueue_block_editor_assets', function () {
	wp_enqueue_style( 'luwipress-sapphire-editor-design', LUWIPRESS_SAPPHIRE_URI . '/assets/css/sapphire.css', array(), LUWIPRESS_SAPPHIRE_VERSION );
	wp_enqueue_style( 'luwipress-sapphire-editor-tokens', LUWIPRESS_SAPPHIRE_URI . '/assets/css/tokens.css', array( 'luwipress-sapphire-editor-design' ), LUWIPRESS_SAPPHIRE_VERSION );
} );

/**
 * Preconnect Google Fonts before the stylesheet starts downloading.
 */
add_action( 'wp_head', function () {
	echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
}, 0 );
