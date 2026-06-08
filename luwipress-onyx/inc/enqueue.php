<?php
/**
 * Asset enqueue — fonts, Onyx design system, interactions.
 *
 * The Onyx design system ships as a set of stylesheets, loaded in
 * cascade order:
 *   onyx.css                  → tokens (dark :root + [data-theme=light]) + base + buttons
 *   onyx-sections.css         → home chrome / hero / trust / overview / plans
 *   onyx-sections2.css        → grid / areas / testimonials / lifestyle / news / contact / footer / FAQ
 *   onyx-pages.css            → sub-page styles (gallery / property / about / journal)
 *   onyx-product.css          → advanced listings + rich property detail
 *   onyx-page-contact-search.css → contact + search pages
 *   tokens.css                → thin brand-knob bridge (LAST, so Customizer overrides win)
 *
 * onyx.js drives the theme toggle, mobile drawer, mega menu, scroll
 * reveal, FAQ + testimonial carousels, the client-rendered Listings
 * filter, the gallery filter, the property lightbox, mortgage calc and
 * sticky CTA. Null-guarded — safe on every page.
 *
 * @package luwipress-onyx
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', function () {
	$base_ver = LUWIPRESS_ONYX_VERSION;
	$dir      = LUWIPRESS_ONYX_DIR;
	$uri      = LUWIPRESS_ONYX_URI;

	$asset_ver = function ( $rel ) use ( $base_ver, $dir ) {
		$abs = $dir . $rel;
		return $base_ver . '.' . ( file_exists( $abs ) ? filemtime( $abs ) : '0' );
	};

	// Google Fonts — Bodoni Moda (display serif, headings/prices, incl. italic)
	// + Nunito (body / UI). display=swap to avoid layout shift.
	wp_enqueue_style(
		'luwipress-onyx-fonts',
		'https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Nunito:wght@300;400;500;600;700&display=swap',
		array(),
		null
	);

	// Shared interactions — theme toggle, drawer, mega, reveal, listings,
	// lightbox, mortgage calc, sticky CTA. Buster `?cb=` survives LiteSpeed's
	// "Remove Query Strings" (which strips ?ver=).
	wp_enqueue_script(
		'luwipress-onyx-app',
		$uri . '/assets/js/onyx.js?cb=' . $asset_ver( '/assets/js/onyx.js' ),
		array(),
		null,
		true
	);

	// Keep onyx.js out of LiteSpeed Defer/Delay so the theme toggle + reveal
	// fire on first paint rather than first interaction.
	add_filter( 'script_loader_tag', function ( $tag, $handle ) {
		if ( 'luwipress-onyx-app' === $handle ) {
			$tag = str_replace( ' src=', ' data-no-optimize="1" data-no-defer="true" data-cfasync="false" src=', $tag );
		}
		return $tag;
	}, 10, 2 );

	// Shop archive surfaces — price slider + optional load-more (inherited).
	if ( function_exists( 'is_woocommerce' ) && ( is_shop() || is_product_taxonomy() || is_product_category() || is_product_tag() ) ) {
		wp_enqueue_script( 'wc-price-slider' );

		if ( get_theme_mod( 'luwipress_onyx_shop_loadmore', true ) ) {
			wp_enqueue_script(
				'luwipress-onyx-loadmore',
				$uri . '/assets/js/loadmore.js',
				array(),
				$asset_ver( '/assets/js/loadmore.js' ),
				true
			);
			wp_localize_script( 'luwipress-onyx-loadmore', 'LWP_ONYX_LM', array(
				'i18n' => array(
					'load_more' => __( 'Load more', 'luwipress-onyx' ),
					'loading'   => __( 'Loading…', 'luwipress-onyx' ),
					'no_more'   => __( 'You\'ve reached the end.', 'luwipress-onyx' ),
					'error'     => __( 'Couldn\'t load more — try again.', 'luwipress-onyx' ),
				),
				'mode' => (string) get_theme_mod( 'luwipress_onyx_shop_loadmore_mode', 'infinite' ),
			) );
		}
	}
}, 20 );

/**
 * LiteSpeed JS-defer/delay exclusion for onyx.js.
 */
if ( ! function_exists( 'luwipress_onyx_js_ls_excludes' ) ) {
	function luwipress_onyx_js_ls_excludes( $excludes ) {
		if ( ! is_array( $excludes ) ) {
			$excludes = array();
		}
		$excludes[] = 'luwipress-onyx-elementor/assets/js/onyx.js';
		$excludes[] = 'luwipress-onyx-app';
		return $excludes;
	}
	add_filter( 'litespeed_optm_js_defer_exc', 'luwipress_onyx_js_ls_excludes' );
	add_filter( 'litespeed_optm_js_excludes',  'luwipress_onyx_js_ls_excludes' );
	add_filter( 'litespeed_optm_js_delay_inc', 'luwipress_onyx_js_ls_excludes' );
}

/**
 * Onyx design system CSS at priority 9999 — lands AFTER every plugin's CSS
 * (WooCommerce, Elementor, Yoast, …) and after any LiteSpeed CSS combine, so
 * "last rule wins" goes our way. tokens.css ships LAST so the brand-knob :root
 * + the inline Customizer overrides (output-css.php) win the accent tokens.
 */
add_action( 'wp_enqueue_scripts', function () {
	$base_ver = LUWIPRESS_ONYX_VERSION;
	$dir      = LUWIPRESS_ONYX_DIR;
	$uri      = LUWIPRESS_ONYX_URI;

	$asset_ver = function ( $rel ) use ( $base_ver, $dir ) {
		$abs = $dir . $rel;
		return $base_ver . '.' . ( file_exists( $abs ) ? filemtime( $abs ) : '0' );
	};

	$sheets = array(
		'luwipress-onyx-design'         => array( '/assets/css/onyx.css',                     array( 'luwipress-onyx-fonts' ) ),
		'luwipress-onyx-sections'       => array( '/assets/css/onyx-sections.css',            array( 'luwipress-onyx-design' ) ),
		'luwipress-onyx-sections2'      => array( '/assets/css/onyx-sections2.css',           array( 'luwipress-onyx-sections' ) ),
		'luwipress-onyx-pages'          => array( '/assets/css/onyx-pages.css',               array( 'luwipress-onyx-sections2' ) ),
		'luwipress-onyx-product'        => array( '/assets/css/onyx-product.css',             array( 'luwipress-onyx-pages' ) ),
		'luwipress-onyx-contact-search' => array( '/assets/css/onyx-page-contact-search.css', array( 'luwipress-onyx-product' ) ),
		'luwipress-onyx-tokens'         => array( '/assets/css/tokens.css',                   array( 'luwipress-onyx-contact-search' ) ),
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
		'luwipress-onyx-design', 'luwipress-onyx-sections', 'luwipress-onyx-sections2',
		'luwipress-onyx-pages', 'luwipress-onyx-product', 'luwipress-onyx-contact-search', 'luwipress-onyx-tokens',
	);
	if ( in_array( $handle, $skip, true ) ) {
		$tag = str_replace( ' href=', ' data-no-optimize="1" data-no-defer="1" data-cfasync="false" href=', $tag );
	}
	return $tag;
}, 10, 2 );

add_filter( 'litespeed_optm_css_excludes', 'luwipress_onyx_css_ls_excludes' );
add_filter( 'litespeed_optm_ucss_file_exc_inline_gen', 'luwipress_onyx_css_ls_excludes' );
if ( ! function_exists( 'luwipress_onyx_css_ls_excludes' ) ) {
	function luwipress_onyx_css_ls_excludes( $excludes ) {
		if ( ! is_array( $excludes ) ) {
			$excludes = array();
		}
		foreach ( array( 'onyx.css', 'onyx-sections.css', 'onyx-sections2.css', 'onyx-pages.css', 'onyx-product.css', 'onyx-page-contact-search.css', 'tokens.css' ) as $f ) {
			$excludes[] = 'luwipress-onyx-elementor/assets/css/' . $f;
		}
		return $excludes;
	}
}

/**
 * Editor styles — surface the Onyx design + tokens inside the block editor.
 */
add_action( 'enqueue_block_editor_assets', function () {
	wp_enqueue_style( 'luwipress-onyx-editor-design', LUWIPRESS_ONYX_URI . '/assets/css/onyx.css', array(), LUWIPRESS_ONYX_VERSION );
	wp_enqueue_style( 'luwipress-onyx-editor-tokens', LUWIPRESS_ONYX_URI . '/assets/css/tokens.css', array( 'luwipress-onyx-editor-design' ), LUWIPRESS_ONYX_VERSION );
} );

/**
 * Preconnect Google Fonts before the stylesheet starts downloading.
 */
add_action( 'wp_head', function () {
	echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
}, 0 );
