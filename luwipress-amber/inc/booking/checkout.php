<?php
/**
 * LuwiPress Amber — Checkout "Trip & pickup details".
 *
 * Per-booking fields collected once at checkout and saved to ORDER meta
 * (the per-tour date/pax/add-ons already ride on the line item from the cart).
 * Only rendered when the cart contains a tour. Customer name + contact reuse
 * the standard WooCommerce billing fields — not re-asked here.
 *
 * @package luwipress-amber
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'WooCommerce' ) ) { return; }

/**
 * Does the current cart contain at least one tour?
 */
function lwp_amber_cart_has_tour() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) { return false; }
	foreach ( WC()->cart->get_cart() as $cart_item ) {
		if ( ! empty( $cart_item['fbd'] ) ) { return true; }
	}
	return false;
}

/* ── Render the block ─────────────────────────────────────────────────── */
add_action( 'woocommerce_after_order_notes', function ( $checkout ) {
	if ( ! lwp_amber_cart_has_tour() ) { return; }
	echo '<div class="lwp-co-step lwp-trip-details" id="lwp-trip-details">';
	echo '<h3>' . esc_html__( 'Trip & pickup details', 'luwipress-amber' ) . '</h3>';

	woocommerce_form_field( 'fbd_pickup_from', [
		'type'        => 'text',
		'class'       => [ 'form-row-wide' ],
		'required'    => true,
		'label'       => __( 'Pick-up from (hotel / address)', 'luwipress-amber' ),
		'placeholder' => __( 'e.g. Rixos Premium, JBR', 'luwipress-amber' ),
	], $checkout->get_value( 'fbd_pickup_from' ) );

	woocommerce_form_field( 'fbd_pickup_time', [
		'type'  => 'time',
		'class' => [ 'form-row-first' ],
		'label' => __( 'Preferred pick-up time', 'luwipress-amber' ),
	], $checkout->get_value( 'fbd_pickup_time' ) );

	woocommerce_form_field( 'fbd_flight_no', [
		'type'        => 'text',
		'class'       => [ 'form-row-last' ],
		'label'       => __( 'Flight number (arrival day only)', 'luwipress-amber' ),
		'placeholder' => __( 'e.g. EK124', 'luwipress-amber' ),
	], $checkout->get_value( 'fbd_flight_no' ) );

	woocommerce_form_field( 'fbd_dropoff', [
		'type'  => 'text',
		'class' => [ 'form-row-wide' ],
		'label' => __( 'Drop-off (if different)', 'luwipress-amber' ),
	], $checkout->get_value( 'fbd_dropoff' ) );

	woocommerce_form_field( 'fbd_notes', [
		'type'        => 'textarea',
		'class'       => [ 'form-row-wide' ],
		'label'       => __( 'Notes (child seats, accessibility, occasions…)', 'luwipress-amber' ),
	], $checkout->get_value( 'fbd_notes' ) );

	echo '</div>';
} );

/* ── Validate ─────────────────────────────────────────────────────────── */
add_action( 'woocommerce_checkout_process', function () {
	if ( ! lwp_amber_cart_has_tour() ) { return; }
	if ( empty( $_POST['fbd_pickup_from'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC verifies the checkout nonce.
		wc_add_notice( __( 'Please tell us where to pick you up.', 'luwipress-amber' ), 'error' );
	}
} );

/* ── Save to order meta (HPOS-safe CRUD) ──────────────────────────────── */
add_action( 'woocommerce_checkout_create_order', function ( $order ) {
	$fields = [
		'fbd_pickup_from' => 'text',
		'fbd_pickup_time' => 'text',
		'fbd_flight_no'   => 'text',
		'fbd_dropoff'     => 'text',
		'fbd_notes'       => 'textarea',
	];
	foreach ( $fields as $field => $type ) {
		if ( ! isset( $_POST[ $field ] ) ) { continue; } // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$raw = wp_unslash( $_POST[ $field ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$val = ( 'textarea' === $type ) ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
		if ( 'fbd_flight_no' === $field ) { $val = strtoupper( $val ); }
		$order->update_meta_data( '_' . $field, $val );
	}
}, 10, 1 );
