<?php
/**
 * LuwiPress Amber — Tour Booking module bootstrap.
 *
 * A lightweight WooCommerce booking layer for travel agencies: a tour is a
 * WooCommerce product, the PDP shows a date + pax + add-ons booking box, the
 * booking flows through the cart into order-item meta, checkout collects the
 * trip/pickup details, an admin dispatch box assigns driver + vehicle, and a
 * print-ready voucher (+ .ics) is rendered on the account page, in the order
 * email and on a print endpoint. TouristTrip JSON-LD is emitted via the core
 * LuwiPress Schema Registry.
 *
 * All persisted order/booking data uses theme-agnostic `_fbd_*` meta keys so
 * the records survive a theme switch (and so the module could be promoted to a
 * companion plugin later as a file move, not a rewrite).
 *
 * Loaded only when WooCommerce is active (see functions.php).
 *
 * @package luwipress-amber
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'LWP_AMBER_BOOKING_DIR', __DIR__ );

/**
 * Declare WooCommerce HPOS (custom order tables) compatibility. All order I/O
 * in this module goes through WC_Order CRUD ($order->get_meta()/update_meta_data()),
 * never get_post_meta() on an order id — so HPOS is fully supported.
 */
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			get_stylesheet(),
			true
		);
	}
} );

require_once LWP_AMBER_BOOKING_DIR . '/helpers.php';            // pure helpers (load first)
require_once LWP_AMBER_BOOKING_DIR . '/class-tour-product.php'; // product "Tour / Booking" data tab + meta
require_once LWP_AMBER_BOOKING_DIR . '/cart.php';               // PDP box → cart item data → line item
require_once LWP_AMBER_BOOKING_DIR . '/checkout.php';           // Trip & pickup details → order meta
require_once LWP_AMBER_BOOKING_DIR . '/class-dispatch-metabox.php'; // admin driver/vehicle → order meta
require_once LWP_AMBER_BOOKING_DIR . '/voucher.php';            // voucher render (account + email + print)
require_once LWP_AMBER_BOOKING_DIR . '/ics.php';                // .ics calendar download
require_once LWP_AMBER_BOOKING_DIR . '/schema-trip.php';        // TouristTrip JSON-LD via Schema Registry
