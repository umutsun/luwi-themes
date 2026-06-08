<?php
/**
 * Theme support, image sizes, nav menu locations.
 *
 * Kept deliberately small — Elementor Theme Builder (or ElementsKit Lite)
 * handles the actual layout, so the theme only declares what WordPress core
 * and WooCommerce expect.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme-string translation that works regardless of WPML's "theme localization"
 * mode. WPML hooks `override_load_textdomain` and — when set to translate the
 * theme via WPML String Translation rather than .mo files — swallows
 * `load_theme_textdomain()`, so the .mo we ship never loads and theme chrome
 * (mega menu "View all" / "Open", footer blurb, widget labels) renders in English
 * on /fr/ /it/ /es/. We bypass that entirely: read the shipped .mo ourselves,
 * keyed to the request language (WPML/Polylang aware), and filter `gettext`
 * directly. The filter fires per __()/_e()/esc_html__() call at render time, when
 * the request language is already resolved, so timing is a non-issue too.
 */
function luwipress_gold_i18n_locale() {
	$code = '';
	if ( defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE ) {
		$code = ICL_LANGUAGE_CODE;
	} elseif ( function_exists( 'pll_current_language' ) ) {
		$code = (string) pll_current_language( 'slug' );
	}
	if ( $code ) {
		$langs = apply_filters( 'wpml_active_languages', null );
		if ( is_array( $langs ) && ! empty( $langs[ $code ]['default_locale'] ) ) {
			return $langs[ $code ]['default_locale'];
		}
		$map = array( 'fr' => 'fr_FR', 'it' => 'it_IT', 'es' => 'es_ES', 'de' => 'de_DE', 'en' => 'en_US' );
		if ( isset( $map[ $code ] ) ) {
			return $map[ $code ];
		}
	}
	return function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
}

function luwipress_gold_i18n_map() {
	static $cache = array();
	$locale = luwipress_gold_i18n_locale();
	if ( array_key_exists( $locale, $cache ) ) {
		return $cache[ $locale ];
	}
	$map  = array();
	$file = LUWIPRESS_GOLD_DIR . '/languages/luwipress-gold-' . $locale . '.mo';
	if ( $locale && is_readable( $file ) ) {
		if ( ! class_exists( 'MO' ) ) {
			require_once ABSPATH . 'wp-includes/pomo/mo.php';
		}
		$mo = new MO();
		if ( $mo->import_from_file( $file ) ) {
			foreach ( $mo->entries as $entry ) {
				if ( isset( $entry->singular ) && ! empty( $entry->translations[0] ) ) {
					$map[ $entry->singular ] = $entry->translations[0];
				}
			}
		}
	}
	$cache[ $locale ] = $map;
	return $map;
}

add_filter( 'gettext', function ( $translation, $text, $domain ) {
	if ( 'luwipress-gold' !== $domain || $translation !== $text ) {
		return $translation;
	}
	$map = luwipress_gold_i18n_map();
	return isset( $map[ $text ] ) ? $map[ $text ] : $translation;
}, 999, 3 );

add_filter( 'gettext_with_context', function ( $translation, $text, $context, $domain ) {
	if ( 'luwipress-gold' !== $domain || $translation !== $text ) {
		return $translation;
	}
	$map = luwipress_gold_i18n_map();
	return isset( $map[ $text ] ) ? $map[ $text ] : $translation;
}, 999, 4 );

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'luwipress-gold', LUWIPRESS_GOLD_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', [
		'height'      => 60,
		'width'       => 200,
		'flex-height' => true,
		'flex-width'  => true,
	] );
	add_theme_support( 'html5', [
		'search-form', 'comment-form', 'comment-list',
		'gallery', 'caption', 'style', 'script',
	] );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );

	// WooCommerce — declared so WC delegates the layout to its templates;
	// we don't override any WC template here. Elementor / ElementsKit Lite
	// renders cart, checkout, single product, archive.
	add_theme_support( 'woocommerce', [
		'thumbnail_image_width' => 600,
		'single_image_width'    => 1200,
	] );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	// Nav menu locations are minimal — the Elementor Kit's header / footer
	// templates use these for fallback when the operator hasn't populated
	// custom Elementor menu widgets yet.
	register_nav_menus( [
		'primary' => __( 'Primary navigation (header fallback)', 'luwipress-gold' ),
		'mobile'  => __( 'Mobile drawer (header fallback)', 'luwipress-gold' ),
		'footer'  => __( 'Footer fallback', 'luwipress-gold' ),
	] );

	// Image sizes used inside Elementor Kit JSONs.
	add_image_size( 'luwipress-gold-card', 600, 600, true );
	add_image_size( 'luwipress-gold-cat',  600, 660, true );
	add_image_size( 'luwipress-gold-hero', 1100, 1100, false );
	add_image_size( 'luwipress-gold-editorial',      900, 720, true );
	add_image_size( 'luwipress-gold-editorial-feat', 1100, 880, true );
}, 5 );

/**
 * Content width — used by oEmbed and the WP editor to constrain media.
 */
add_action( 'after_setup_theme', function () {
	if ( ! isset( $GLOBALS['content_width'] ) ) {
		$GLOBALS['content_width'] = 1372;
	}
} );

/**
 * Tag every Elementor-built singular post with `lwp-elementor-built` so
 * theme CSS can opt-out of fallback width caps without inspecting per-
 * template branches. Mirrors the page.php / single.php branch logic so
 * the marker is reliable in either path.
 */
add_filter( 'body_class', function ( $classes ) {
	if ( ! is_singular() ) {
		return $classes;
	}
	$post_id = get_queried_object_id();
	if ( ! $post_id ) {
		return $classes;
	}
	$is_el_built = (bool) get_post_meta( $post_id, '_elementor_edit_mode', true );
	$has_el_data = (bool) get_post_meta( $post_id, '_elementor_data', true );
	if ( $is_el_built && $has_el_data ) {
		$classes[] = 'lwp-elementor-built';
	}
	return $classes;
} );
