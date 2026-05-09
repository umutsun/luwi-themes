<?php
/**
 * Force-template filter for Elementor Pro Theme Builder.
 *
 * When the operator sets `luwipress_gold_pdp_template_id` or
 * `luwipress_gold_archive_template_id` via the LuwiPress → Theme settings
 * tab, the chosen template wins over Elementor Pro's own conditions
 * resolution at request time. Solves the WPML duplicate-template problem
 * where two PDP templates (one EN, one IT) both claim a product and the
 * wrong one renders.
 *
 * Set ID = 0 to defer to Elementor's own conditions (default behaviour).
 *
 * @package luwipress-gold
 * @since   1.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Force `single.php` for blog posts that carry a legacy `_wp_page_template`
 * meta from Hello Elementor / page-builder migrations.
 *
 * Symptom this fixes: post saved as `post_type=post` with
 * `_wp_page_template = elementor_header_footer.php` (or `elementor_canvas.php`)
 * renders through the Elementor template instead of our cream/atelier
 * single-post layout (sidebar, breadcrumb, reading-progress, related rail).
 *
 * Pages are intentionally NOT touched — page builders rely on those
 * templates for Elementor canvas authoring. Only `post_type=post` is
 * promoted back to single.php.
 *
 * Tapadum case (2026-05-09): 10/12 Buying Guide posts had this meta from
 * the WP migration; saving in the WP block editor restored content but
 * the legacy meta survived. This filter neutralises the meta at request
 * time without requiring a DB migration.
 */
add_filter( 'template_include', function ( $template ) {
	if ( is_admin() ) {
		return $template;
	}
	if ( ! is_singular( 'post' ) ) {
		return $template;
	}
	$queried = get_queried_object_id();
	if ( ! $queried ) {
		return $template;
	}
	$assigned = (string) get_post_meta( $queried, '_wp_page_template', true );
	if ( $assigned === '' || $assigned === 'default' ) {
		return $template;
	}
	$legacy = array(
		'elementor_header_footer.php',
		'elementor_header_footer',
		'elementor_canvas.php',
		'elementor_canvas',
	);
	if ( ! in_array( $assigned, $legacy, true ) ) {
		return $template;
	}
	$single = locate_template( array( 'single.php' ) );
	return $single ? $single : $template;
}, 99 );

add_filter( 'elementor/theme/get_location_templates/template_id', function ( $template_id, $location ) {
	// Single Product (PDP).
	if ( in_array( $location, array( 'single', 'product' ), true ) || ( is_string( $location ) && false !== strpos( $location, 'single-product' ) ) ) {
		$forced = (int) get_theme_mod( 'luwipress_gold_pdp_template_id', 0 );
		if ( $forced > 0 && get_post( $forced ) ) {
			return $forced;
		}
	}
	// Products Archive.
	if ( in_array( $location, array( 'archive', 'product-archive', 'products-archive' ), true ) || ( is_string( $location ) && false !== strpos( $location, 'product-archive' ) ) ) {
		$forced = (int) get_theme_mod( 'luwipress_gold_archive_template_id', 0 );
		if ( $forced > 0 && get_post( $forced ) ) {
			return $forced;
		}
	}
	return $template_id;
}, 10, 2 );

/**
 * Strict 404 banner — when a translated product_cat archive 404s and the
 * `luwipress_gold_wpml_subcat_strict_404` setting is on, render an explicit
 * "Translation pending" notice instead of WP's generic 404. Helps QA spot
 * WPML term-translation gaps quickly.
 */
add_action( 'template_redirect', function () {
	if ( ! is_404() ) {
		return;
	}
	if ( ! get_theme_mod( 'luwipress_gold_wpml_subcat_strict_404', false ) ) {
		return;
	}
	$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	if ( ! $path || false === strpos( $path, '/product-category/' ) ) {
		return;
	}
	status_header( 404 );
	nocache_headers();
	echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Translation pending</title>';
	echo '<style>body{font-family:Inter,system-ui,sans-serif;background:#fff7ed;color:#9a3412;margin:0;padding:40px 20px;text-align:center}h1{font-family:Playfair Display,Georgia,serif;font-size:32px;margin:24px 0 12px}.b{max-width:540px;margin:60px auto;background:#fff;border:1px solid #fed7aa;border-radius:12px;padding:32px;box-shadow:0 6px 24px rgba(154,52,18,.08)}a{color:#9a3412;font-weight:600}</style>';
	echo '</head><body><div class="b"><h1>Translation pending</h1>';
	echo '<p>This subcategory archive has not been translated yet. The luwipress-gold theme is configured to surface this gap rather than fall through to a generic 404 so it can be fixed in WPML &rarr; Taxonomy Translation.</p>';
	echo '<p><a href="' . esc_url( home_url( '/' ) ) . '">&larr; Back to homepage</a></p>';
	echo '</div></body></html>';
	exit;
} );

/**
 * Block orphan landing pages on the front-end when the operator has flagged
 * them via the Unwanted Landing Pages tool but hasn't trashed them yet.
 *
 * The block list is a transient written by the tool's scan; if absent we no-op.
 */
add_action( 'template_redirect', function () {
	if ( ! get_theme_mod( 'luwipress_gold_block_orphan_landings', false ) ) {
		return;
	}
	if ( ! is_singular( array( 'page', 'post' ) ) ) {
		return;
	}
	$blocked = get_transient( 'luwipress_gold_orphan_landing_ids' );
	if ( ! is_array( $blocked ) || ! in_array( get_queried_object_id(), array_map( 'intval', $blocked ), true ) ) {
		return;
	}
	status_header( 410 );
	nocache_headers();
	wp_die(
		esc_html__( 'This page has been retired.', 'luwipress-gold' ),
		'410 Gone',
		array( 'response' => 410 )
	);
} );
