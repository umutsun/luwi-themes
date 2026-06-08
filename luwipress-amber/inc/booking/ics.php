<?php
/**
 * LuwiPress Amber — tour voucher .ics download.
 *
 * Serves a single-event iCalendar file for a booked tour line item at
 * ?fbd_ics=ORDER&item=LINE&fbd_key=KEY. Datetimes are FLOATING wall-clock
 * (no Z, no TZID) so a Dubai-local pick-up time is never shifted by the
 * importer's timezone — mirrors the core LuwiPress Events ICS handling.
 *
 * @package luwipress-amber
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'WooCommerce' ) ) { return; }

/**
 * Escape a value for an ICS text field.
 */
function lwp_amber_ics_escape( $text ) {
	$text = (string) $text;
	$text = str_replace( [ '\\', ';', ',', "\r\n", "\n" ], [ '\\\\', '\\;', '\\,', '\\n', '\\n' ], $text );
	return $text;
}

/**
 * Fold an ICS line to 75 octets per RFC 5545.
 */
function lwp_amber_ics_fold( $line ) {
	if ( strlen( $line ) <= 75 ) { return $line; }
	$out = '';
	while ( strlen( $line ) > 75 ) {
		$out  .= substr( $line, 0, 75 ) . "\r\n ";
		$line  = substr( $line, 75 );
	}
	return $out . $line;
}

add_action( 'template_redirect', function () {
	if ( empty( $_GET['fbd_ics'] ) ) { return; } // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$order = wc_get_order( absint( $_GET['fbd_ics'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! $order || ! function_exists( 'lwp_amber_voucher_can_view' ) || ! lwp_amber_voucher_can_view( $order ) ) {
		wp_die( esc_html__( 'Voucher not found.', 'luwipress-amber' ), '', [ 'response' => 404 ] );
	}

	$item_id = isset( $_GET['item'] ) ? absint( $_GET['item'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$item    = $item_id ? $order->get_item( $item_id ) : null;
	if ( ! $item ) {
		// Fall back to the first tour line.
		foreach ( $order->get_items() as $id => $it ) {
			if ( $it->get_meta( '_fbd_tour_date' ) ) { $item = $it; $item_id = $id; break; }
		}
	}
	if ( ! $item || ! $item->get_meta( '_fbd_tour_date' ) ) {
		wp_die( esc_html__( 'No tour on this voucher.', 'luwipress-amber' ), '', [ 'response' => 404 ] );
	}

	$date   = (string) $item->get_meta( '_fbd_tour_date' );
	$pickup = (string) $order->get_meta( '_fbd_pickup_time' );
	$slot   = (string) $item->get_meta( '_fbd_time_slot' );
	$parts  = lwp_amber_parse_floating( $date, $pickup );
	if ( ! $parts ) {
		wp_die( esc_html__( 'Invalid tour date.', 'luwipress-amber' ), '', [ 'response' => 400 ] );
	}

	$has_time = (bool) preg_match( '/\d{1,2}:\d{2}/', $pickup );
	if ( $has_time ) {
		$dtstart = sprintf( 'DTSTART:%04d%02d%02dT%02d%02d00', $parts[0], $parts[1], $parts[2], $parts[3], $parts[4] );
		// +3h default duration.
		$end_h   = min( 23, $parts[3] + 3 );
		$dtend   = sprintf( 'DTEND:%04d%02d%02dT%02d%02d00', $parts[0], $parts[1], $parts[2], $end_h, $parts[4] );
	} else {
		$dtstart = sprintf( 'DTSTART;VALUE=DATE:%04d%02d%02d', $parts[0], $parts[1], $parts[2] );
		$dtend   = '';
	}

	$summary  = $item->get_name();
	$location = (string) $order->get_meta( '_fbd_pickup_from' );
	$pax      = (int) $item->get_meta( '_fbd_pax' );
	$driver   = (string) $order->get_meta( '_fbd_driver_name' );
	$desc_bits = [];
	if ( $pax ) { $desc_bits[] = sprintf( /* translators: %d: number of guests */ __( '%d guests', 'luwipress-amber' ), $pax ); }
	if ( $slot ) { $desc_bits[] = $slot; }
	if ( $driver ) { $desc_bits[] = sprintf( /* translators: %s: driver name */ __( 'Driver: %s', 'luwipress-amber' ), $driver ); }
	$desc = implode( ' · ', $desc_bits );

	$host  = wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'localhost';
	$uid   = sprintf( 'voucher-%d-%d@%s', $order->get_id(), $item_id, $host );
	$stamp = gmdate( 'Ymd\THis\Z' );

	$lines = [
		'BEGIN:VCALENDAR',
		'VERSION:2.0',
		'PRODID:-//LuwiPress Amber//Tour Voucher//EN',
		'CALSCALE:GREGORIAN',
		'METHOD:PUBLISH',
		'BEGIN:VEVENT',
		'UID:' . $uid,
		'DTSTAMP:' . $stamp,
		$dtstart,
	];
	if ( $dtend ) { $lines[] = $dtend; }
	$lines[] = 'SUMMARY:' . lwp_amber_ics_escape( $summary );
	if ( $location ) { $lines[] = 'LOCATION:' . lwp_amber_ics_escape( $location ); }
	if ( $desc ) { $lines[] = 'DESCRIPTION:' . lwp_amber_ics_escape( $desc ); }
	$lines[] = 'END:VEVENT';
	$lines[] = 'END:VCALENDAR';

	$folded = array_map( 'lwp_amber_ics_fold', $lines );
	$body   = implode( "\r\n", $folded ) . "\r\n";

	nocache_headers();
	header( 'Content-Type: text/calendar; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="tour-' . $order->get_id() . '.ics"' );
	echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS body, escaped per-field.
	exit;
}, 1 );
