<?php
/**
 * Fly by Deniz flight-results i18n bridge.
 *
 * The fbd-connect search widget renders its results in vanilla JS with hard-coded
 * English UI labels (Non-stop, Cabin bag, Book this flight, the value-score panel,
 * status messages…). Those strings live inside the plugin's own search.js, so any
 * plugin update overwrites an in-place translation of that file.
 *
 * This theme-side shim keeps the single source of truth in the gettext catalog at
 * wp-content/languages/plugins/fbd-connect-<locale>.mo (which survives plugin
 * updates): it reads each label through __() under the page's WPML locale, hands
 * the map to a tiny front-end script, and that script translates the result DOM in
 * place via a MutationObserver. The plugin itself is never touched.
 *
 * @package LuwiPress_Amber
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_enqueue_scripts',
	function () {
		// Only meaningful when the flight plugin is active.
		if ( ! defined( 'FBD_CONNECT_VERSION' ) ) {
			return;
		}

		$rel = '/assets/js/fbd-i18n.js';
		$abs = LUWIPRESS_AMBER_DIR . $rel;
		if ( ! file_exists( $abs ) ) {
			return;
		}
		$cb = LUWIPRESS_AMBER_VERSION . '.' . filemtime( $abs );

		wp_enqueue_script(
			'luwipress-amber-fbd-i18n',
			LUWIPRESS_AMBER_URI . $rel . '?cb=' . $cb,
			array(),
			null,
			true
		);

		// Whole-string labels: English source => translation for the current WPML
		// locale (pulled just-in-time from the .mo catalog).
		$labels = array(
			// Chips + detail-row keys.
			'Cabin bag',
			'Checked bag',
			'Refundable',
			'Changeable',
			// Detail-row values.
			'Included',
			'Not included',
			'Yes',
			'No',
			// Section headers + value-score bar labels.
			'Baggage',
			'Flexibility',
			'Journey',
			'Why this value score',
			'Times',
			'Duration',
			'Price',
			'Stops',
			// Buttons / toggles / score caption.
			'Flight details',
			'Book this flight',
			'score',
			// Status messages.
			'Please choose an origin, destination and date.',
			'Searching best-value flights…',
			'No flights found for this route and date.',
			'Sorry, search is unavailable right now. Please try again.',
			'Booking page is not configured.',
			// Chip tooltips.
			'Hand/cabin baggage included',
			'Refundable before departure',
			'Date/flight change allowed before departure',
			// Booking page — passenger form.
			'Title',
			'Mr',
			'Ms',
			'Mrs',
			'Given name',
			'Family name',
			'Date of birth',
			'Gender',
			'Male',
			'Female',
			'Contact',
			'Email',
			'Phone',
			// Booking page — notices / status / requote.
			'Please choose a flight first.',
			'Creating your booking…',
			'That fare just expired. Please pick a fresh option below.',
			'Sorry, we could not create the booking. Please try again.',
			'Network error. Please try again.',
			'No fresh fares right now — please search again.',
			'Choose',
			// Confirmation page — states.
			"You're booked!",
			'A confirmation has been sent to your email.',
			"We're finalising your ticket",
			'Your payment is safe. Our team is completing the issuance and will email you shortly.',
			'Payment received',
			'Issuing your ticket…',
			'Awaiting payment',
			'Complete the payment to confirm your flight.',
			'No booking reference found.',
			'Booking not found yet…',
			'Booking reference:',
		);

		$dict = array();
		foreach ( $labels as $s ) {
			$t = __( $s, 'fbd-connect' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- catalog shared with fbd-connect plugin.
			if ( $t !== $s ) {
				$dict[ $s ] = $t;
			}
		}

		wp_localize_script(
			'luwipress-amber-fbd-i18n',
			'FBD_I18N',
			array(
				'dict'    => $dict,
				// Stop tokens are interpolated with a count in JS, so they travel
				// separately from the whole-string dictionary.
				'nonstop' => __( 'Non-stop', 'fbd-connect' ),
				'stop'    => __( 'stop', 'fbd-connect' ),
				'stops'   => __( 'stops', 'fbd-connect' ),
				// "%d flights, ranked by value" — JS emits the live count inline.
				'results'   => __( '%d flights, ranked by value', 'fbd-connect' ),
				// Format strings with a runtime value (count / money / id).
				'passenger' => __( 'Passenger %d', 'fbd-connect' ),
				'pay'       => __( 'Pay %s', 'fbd-connect' ),
				'reference' => __( 'Reference: %s', 'fbd-connect' ),
			)
		);
	},
	20
);
