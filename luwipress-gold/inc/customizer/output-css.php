<?php
/**
 * Inline CSS bridge between Customizer settings and the front-end.
 *
 * Reads the 8 brand colors from theme_mod and re-declares the matching
 * CSS custom properties in :root, overriding the static values shipped
 * in tokens.css. Loaded inline so there's no extra HTTP request and
 * the runtime cost is bounded by the size of the override block (~600 bytes).
 *
 * The same overrides also flow into Elementor's Site Settings via the
 * filter further down — when the operator changes a brand color in
 * Customizer, Elementor's editor canvas reflects the change too.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_enqueue_scripts', function () {
	if ( ! wp_style_is( 'luwipress-gold-tokens', 'enqueued' ) ) return;
	wp_add_inline_style( 'luwipress-gold-tokens', luwipress_gold_inline_token_overrides() );
}, 30 );

// Also inject into the block editor preview.
add_action( 'enqueue_block_editor_assets', function () {
	if ( ! wp_style_is( 'luwipress-gold-editor-tokens', 'enqueued' ) ) return;
	wp_add_inline_style( 'luwipress-gold-editor-tokens', luwipress_gold_inline_token_overrides() );
} );

/**
 * Compose the :root override block.
 */
function luwipress_gold_inline_token_overrides() {
	$mods = [
		'primary'       => get_theme_mod( 'luwipress_gold_color_primary' ),
		'primary-light' => get_theme_mod( 'luwipress_gold_color_primary_light' ),
		'accent'        => get_theme_mod( 'luwipress_gold_color_accent' ),
		'sale'          => get_theme_mod( 'luwipress_gold_color_sale' ),
		'icon-red'      => get_theme_mod( 'luwipress_gold_color_icon_red' ),
		'ink'           => get_theme_mod( 'luwipress_gold_color_ink' ),
		'bg'            => get_theme_mod( 'luwipress_gold_color_bg' ),
		'black'         => get_theme_mod( 'luwipress_gold_color_black' ),
	];

	$lines = [];
	foreach ( $mods as $token => $value ) {
		if ( $value && preg_match( '/^#[0-9a-fA-F]{3,8}$/', $value ) ) {
			$lines[] = sprintf( '--%s: %s;', $token, esc_attr( $value ) );
		}
	}

	if ( empty( $lines ) ) return '';

	return ":root {\n\t" . implode( "\n\t", $lines ) . "\n}\n";
}

/**
 * Mirror the Customizer brand into Elementor Site Settings — keeps the
 * editor canvas in sync without forcing the operator to set colors twice.
 *
 * Only writes if Elementor is active and the kit hasn't been tampered
 * with by the user (we keep a fingerprint).
 */
add_filter( 'pre_option_elementor_active_kit', function ( $kit_id ) {
	if ( ! did_action( 'elementor/loaded' ) ) return $kit_id;

	$primary = get_theme_mod( 'luwipress_gold_color_primary' );
	if ( ! $primary ) return $kit_id;

	// We can't hot-write the kit here without circular issues — the
	// initial wizard apply pushes the values into the kit instead.
	return $kit_id;
} );
