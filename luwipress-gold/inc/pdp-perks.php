<?php
/**
 * Product-page perks bar — the trust strip shown under each single product
 * ("Hand-tuned · DHL Express · 14-day returns · 2-year warranty").
 *
 * The bar is rendered by woocommerce/single-product.php, which feeds the
 * canonical defaults below into the `luwipress_gold_pdp_perks` filter. This
 * module adds a no-code editor under  Customize → LuwiPress Gold → Product
 * Page  and hooks the filter to apply the operator's overrides.
 *
 * Multilingual: the defaults stay wrapped in __() so the active translation
 * plugin (WPML String Translation on Tapadum) keeps translating them. An
 * EMPTY override field falls back to that row's already-translated default,
 * so untouched rows keep their per-language text. Overridden values are
 * registered as WPML admin-texts via wpml-config.xml so they can be
 * translated too. Type two dashes (--) to hide a built-in row.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Canonical default perks — SINGLE SOURCE OF TRUTH.
 *
 * woocommerce/single-product.php passes this into the filter, and the
 * Customizer descriptions reference it. Keep the strings wrapped in __()
 * with the 'luwipress-gold' text domain so existing translations (keyed by
 * the source string) continue to apply unchanged.
 *
 * @return array<int,array{icon:string,text:string}>
 */
function luwipress_gold_pdp_perk_defaults() {
	return array(
		array( 'icon' => '✓', 'text' => __( 'Hand-tuned in our atelier before dispatch', 'luwipress-gold' ) ),
		array( 'icon' => '✈', 'text' => __( 'DHL Express worldwide · 3–7 working days', 'luwipress-gold' ) ),
		array( 'icon' => '↺', 'text' => __( '14-day no-questions-asked return policy', 'luwipress-gold' ) ),
		array( 'icon' => '★', 'text' => __( '2-year free service & tuning warranty', 'luwipress-gold' ) ),
	);
}

/**
 * Number of editable rows exposed in the Customizer (built-in slots + spares).
 * Filterable so a site can extend the editor without touching this file.
 *
 * @return int
 */
function luwipress_gold_pdp_perk_slots() {
	return (int) apply_filters( 'luwipress_gold_pdp_perk_slots', 6 );
}

/* ──────────────────────────────────────────────────────────────────
 * Customizer — LuwiPress Gold → Product Page
 * The panel itself is registered by inc/customizer/bootstrap.php; this
 * file is required AFTER it (see functions.php) so the panel exists.
 * ──────────────────────────────────────────────────────────────── */
add_action( 'customize_register', function ( $wp_customize ) {

	$wp_customize->add_section( 'luwipress_gold_pdp', array(
		'title'       => __( 'Product Page', 'luwipress-gold' ),
		'description' => __( 'The trust bar shown under each product. Leave a row empty to keep its built-in, translated default. Type two dashes (--) as the text to hide a built-in row.', 'luwipress-gold' ),
		'panel'       => 'luwipress_gold',
		'priority'    => 35,
	) );

	$defaults = luwipress_gold_pdp_perk_defaults();
	$slots    = luwipress_gold_pdp_perk_slots();

	for ( $i = 1; $i <= $slots; $i++ ) {
		$d = isset( $defaults[ $i - 1 ] ) ? $defaults[ $i - 1 ] : null;

		/* Icon */
		$icon_id = "luwipress_gold_pdp_perk{$i}_icon";
		$wp_customize->add_setting( $icon_id, array(
			'default'           => '',
			'transport'         => 'refresh',
			'sanitize_callback' => 'sanitize_text_field',
		) );
		$wp_customize->add_control( $icon_id, array(
			'label'       => sprintf( /* translators: %d: row number */ __( 'Row %d — icon', 'luwipress-gold' ), $i ),
			'description' => $d
				? sprintf( /* translators: %s: default glyph */ __( 'Default: %s', 'luwipress-gold' ), $d['icon'] )
				: __( 'Glyph or emoji, e.g. ✓ ✈ ↺ ★', 'luwipress-gold' ),
			'section'     => 'luwipress_gold_pdp',
			'type'        => 'text',
		) );

		/* Text */
		$text_id = "luwipress_gold_pdp_perk{$i}_text";
		$wp_customize->add_setting( $text_id, array(
			'default'           => '',
			'transport'         => 'refresh',
			'sanitize_callback' => 'sanitize_text_field',
		) );
		$wp_customize->add_control( $text_id, array(
			'label'       => sprintf( /* translators: %d: row number */ __( 'Row %d — text', 'luwipress-gold' ), $i ),
			'description' => $d
				? sprintf( /* translators: %s: default text */ __( 'Default: %s', 'luwipress-gold' ), $d['text'] )
				: __( 'Extra perk line — leave empty to skip.', 'luwipress-gold' ),
			'section'     => 'luwipress_gold_pdp',
			'type'        => 'text',
		) );
	}
} );

/* ──────────────────────────────────────────────────────────────────
 * Apply operator overrides to the perks list.
 *
 * $perks arrives as the default set (already translated at runtime). For
 * each row we overlay the Customizer value when set; an empty value keeps
 * that row's translated default, so partial edits never wipe the other
 * languages. "--" hides a built-in row; spare slots render only when the
 * operator types text.
 * ──────────────────────────────────────────────────────────────── */
add_filter( 'luwipress_gold_pdp_perks', function ( $perks, $product = null ) {
	$defaults = is_array( $perks ) ? array_values( $perks ) : array();
	$slots    = luwipress_gold_pdp_perk_slots();
	$out      = array();

	for ( $i = 1; $i <= $slots; $i++ ) {
		$text = trim( (string) get_theme_mod( "luwipress_gold_pdp_perk{$i}_text", '' ) );
		$icon = trim( (string) get_theme_mod( "luwipress_gold_pdp_perk{$i}_icon", '' ) );
		$d    = isset( $defaults[ $i - 1 ] ) ? $defaults[ $i - 1 ] : null;

		if ( '--' === $text ) {
			continue; // operator hid this built-in row.
		}

		if ( $d ) {
			$final_text = '' !== $text ? $text : ( isset( $d['text'] ) ? $d['text'] : '' );
			$final_icon = '' !== $icon ? $icon : ( isset( $d['icon'] ) ? $d['icon'] : '' );
		} else {
			if ( '' === $text ) {
				continue; // empty spare slot.
			}
			$final_text = $text;
			$final_icon = '' !== $icon ? $icon : '•';
		}

		if ( '' !== $final_text ) {
			$out[] = array( 'icon' => $final_icon, 'text' => $final_text );
		}
	}

	return $out;
}, 20, 2 );
