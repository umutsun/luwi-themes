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

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'luwipress-onyx', LUWIPRESS_ONYX_DIR . '/languages' );

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
		'primary'           => __( 'Primary navigation (header + drawer)', 'luwipress-onyx' ),
		'mobile'            => __( 'Mobile drawer (header fallback)', 'luwipress-onyx' ),
		'footer'            => __( 'Footer — Quick Links column', 'luwipress-onyx' ),
		'footer-activities' => __( 'Footer — Explore column', 'luwipress-onyx' ),
		'footer-legal'      => __( 'Footer — bottom-bar legal links', 'luwipress-onyx' ),
	] );

	// Image sizes used inside Elementor Kit JSONs.
	add_image_size( 'luwipress-onyx-card', 600, 600, true );
	add_image_size( 'luwipress-onyx-cat',  600, 660, true );
	add_image_size( 'luwipress-onyx-hero', 1100, 1100, false );
	add_image_size( 'luwipress-onyx-editorial',      900, 720, true );
	add_image_size( 'luwipress-onyx-editorial-feat', 1100, 880, true );
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
