<?php
/**
 * LuwiPress Amber — Tour Booking helpers.
 *
 * Pure functions shared across the booking module: tour-product config,
 * sanitizers, money formatting, floating (no-timezone-shift) date parsing,
 * and order-booking accessors. No hooks here.
 *
 * Meta key map (all theme-agnostic `_fbd_*`):
 *   Product (a tour):
 *     _fbd_is_tour          'yes'|'no'  — marks a product as a bookable tour
 *     _fbd_duration         string      — human duration ("6 hours")
 *     _fbd_duration_bucket  short|half|full|multi — for grid filtering
 *     _fbd_pax_min/_max/_default int
 *     _fbd_pickup_included  'yes'|'no'
 *     _fbd_deposit_pct      int 0..100
 *     _fbd_cancellation     string      — free-cancellation note
 *     _fbd_time_slots       array<string>
 *     _fbd_addons           array<{label,price}>
 *   Order line item (per tour booked):
 *     _fbd_tour_date, _fbd_pax, _fbd_time_slot, _fbd_per_person, _fbd_addons_json
 *   Order (per booking):
 *     _fbd_pickup_time, _fbd_flight_no, _fbd_pickup_from, _fbd_dropoff, _fbd_notes
 *   Order (admin dispatch):
 *     _fbd_driver_name, _fbd_driver_mobile, _fbd_vehicle, _fbd_plate, _fbd_voucher_ready
 *
 * @package luwipress-amber
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Is this product a bookable tour?
 *
 * @param int|WC_Product $product
 * @return bool
 */
function lwp_amber_is_tour( $product ) {
	if ( is_numeric( $product ) ) {
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( (int) $product ) : null;
	}
	if ( ! $product || ! is_object( $product ) || ! method_exists( $product, 'get_meta' ) ) {
		return false;
	}
	return 'yes' === $product->get_meta( '_fbd_is_tour' );
}

/**
 * Normalized tour booking config with safe defaults. Single source of truth
 * consumed by the PDP widget, the cart math, the voucher and the schema.
 *
 * @param int $product_id
 * @return array
 */
function lwp_amber_tour_config( $product_id ) {
	$product = function_exists( 'wc_get_product' ) ? wc_get_product( (int) $product_id ) : null;
	if ( ! $product ) {
		return [];
	}

	$pax_min = max( 1, (int) ( $product->get_meta( '_fbd_pax_min' ) ?: 1 ) );
	$pax_max = (int) ( $product->get_meta( '_fbd_pax_max' ) ?: 12 );
	if ( $pax_max < $pax_min ) { $pax_max = $pax_min; }
	$pax_default = (int) ( $product->get_meta( '_fbd_pax_default' ) ?: 2 );
	$pax_default = min( $pax_max, max( $pax_min, $pax_default ) );

	$addons = $product->get_meta( '_fbd_addons' );
	$addons = is_array( $addons ) ? array_values( $addons ) : [];

	$slots = $product->get_meta( '_fbd_time_slots' );
	$slots = is_array( $slots ) ? array_values( array_filter( array_map( 'strval', $slots ) ) ) : [];

	$price = $product->get_price();
	$price = ( '' === $price || null === $price ) ? 0.0 : (float) $price;

	return [
		'product_id'       => (int) $product_id,
		'name'             => $product->get_name(),
		'per_person'       => $price,
		'currency'         => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD',
		'currency_symbol'  => function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol() ) : '$',
		'duration'         => (string) $product->get_meta( '_fbd_duration' ),
		'duration_bucket'  => (string) $product->get_meta( '_fbd_duration_bucket' ),
		'pax_min'          => $pax_min,
		'pax_max'          => $pax_max,
		'pax_default'      => $pax_default,
		'pickup_included'  => 'yes' === $product->get_meta( '_fbd_pickup_included' ),
		'deposit_pct'      => max( 0, min( 100, (int) $product->get_meta( '_fbd_deposit_pct' ) ) ),
		'cancellation'     => (string) $product->get_meta( '_fbd_cancellation' ),
		'time_slots'       => $slots,
		'addons'           => $addons,
	];
}

/**
 * Sanitize an add-ons array: [{label,price}, ...]. Drops empty-label rows.
 *
 * @param mixed $value
 * @return array
 */
function lwp_amber_sanitize_addons( $value ) {
	if ( ! is_array( $value ) ) { return []; }
	$out = [];
	foreach ( $value as $row ) {
		if ( ! is_array( $row ) ) { continue; }
		$label = isset( $row['label'] ) ? sanitize_text_field( $row['label'] ) : '';
		if ( '' === $label ) { continue; }
		$price = isset( $row['price'] ) ? (float) wc_format_decimal( $row['price'] ) : 0.0;
		$out[] = [ 'label' => $label, 'price' => $price ];
	}
	return $out;
}

/**
 * Sanitize a flat list of strings (time slots).
 *
 * @param mixed $value
 * @return array
 */
function lwp_amber_sanitize_string_list( $value ) {
	if ( is_string( $value ) ) {
		$value = preg_split( '/\r\n|\r|\n/', $value );
	}
	if ( ! is_array( $value ) ) { return []; }
	return array_values( array_filter( array_map( 'sanitize_text_field', $value ), function ( $v ) {
		return '' !== trim( $v );
	} ) );
}

/**
 * Format a money amount using WC's formatter when available.
 *
 * @param float  $amount
 * @param string $currency
 * @return string
 */
function lwp_amber_money( $amount, $currency = '' ) {
	if ( function_exists( 'wc_price' ) ) {
		$args = $currency ? [ 'currency' => $currency ] : [];
		return wp_strip_all_tags( wc_price( (float) $amount, $args ) );
	}
	return number_format_i18n( (float) $amount, 2 );
}

/**
 * Parse a "YYYY-MM-DD" (optionally + "HH:MM") string into discrete integer
 * parts WITHOUT any timezone conversion — Dubai-local wall-clock times must
 * never shift. Mirrors the core Events module's ICS datetime handling.
 *
 * @param string $date  e.g. "2026-06-15"
 * @param string $time  e.g. "09:45" (24h) — optional
 * @return array|null  [Y,M,D,H,I] or null when unparseable
 */
function lwp_amber_parse_floating( $date, $time = '' ) {
	if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})/', (string) $date, $d ) ) {
		return null;
	}
	$h = 0; $i = 0;
	if ( preg_match( '/(\d{1,2}):(\d{2})/', (string) $time, $t ) ) {
		$h = max( 0, min( 23, (int) $t[1] ) );
		$i = max( 0, min( 59, (int) $t[2] ) );
	}
	return [ (int) $d[1], (int) $d[2], (int) $d[3], $h, $i ];
}

/**
 * Human-format an ISO date as "dd.mm.yyyy" (the voucher format), no TZ shift.
 *
 * @param string $iso
 * @return string
 */
function lwp_amber_format_voucher_date( $iso ) {
	$p = lwp_amber_parse_floating( $iso );
	if ( ! $p ) { return (string) $iso; }
	return sprintf( '%02d.%02d.%04d', $p[2], $p[1], $p[0] );
}

/**
 * Does this order contain at least one tour line item (carries _fbd_tour_date)?
 *
 * @param WC_Order $order
 * @return bool
 */
function lwp_amber_order_has_tour( $order ) {
	if ( ! $order || ! is_object( $order ) || ! method_exists( $order, 'get_items' ) ) {
		return false;
	}
	foreach ( $order->get_items() as $item ) {
		if ( $item->get_meta( '_fbd_tour_date' ) ) {
			return true;
		}
	}
	return false;
}
