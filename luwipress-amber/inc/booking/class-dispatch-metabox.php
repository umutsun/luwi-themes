<?php
/**
 * LuwiPress Amber — order Dispatch / Voucher meta box.
 *
 * Admin-side panel on the order edit screen (HPOS + legacy) where dispatch
 * assigns the driver + vehicle after confirming a tour, and flips a
 * "voucher ready" flag. Saved to ORDER meta; consumed by the voucher render.
 * Only shown on orders that contain a tour line item.
 *
 * @package luwipress-amber
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'WooCommerce' ) ) { return; }

add_action( 'add_meta_boxes', function () {
	// HPOS screen id + legacy post type — register on whichever is active.
	$screens = [ 'shop_order' ];
	if ( class_exists( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )
		&& function_exists( 'wc_get_container' ) ) {
		$hpos_screen = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : '';
		if ( $hpos_screen ) { $screens[] = $hpos_screen; }
	}
	foreach ( array_unique( $screens ) as $screen ) {
		add_meta_box(
			'lwp_amber_dispatch',
			__( 'Dispatch / Voucher', 'luwipress-amber' ),
			'lwp_amber_render_dispatch_metabox',
			$screen,
			'side',
			'high'
		);
	}
} );

/**
 * Render the dispatch fields. $post_or_order is a WP_Post (legacy) or WC_Order (HPOS).
 *
 * @param WP_Post|WC_Order $post_or_order
 */
function lwp_amber_render_dispatch_metabox( $post_or_order ) {
	$order = ( $post_or_order instanceof WC_Order )
		? $post_or_order
		: wc_get_order( is_object( $post_or_order ) ? $post_or_order->ID : (int) $post_or_order );
	if ( ! $order ) { return; }

	if ( ! lwp_amber_order_has_tour( $order ) ) {
		echo '<p class="description">' . esc_html__( 'No tour line item on this order.', 'luwipress-amber' ) . '</p>';
		return;
	}

	wp_nonce_field( 'lwp_amber_dispatch_save', 'lwp_amber_dispatch_nonce' );

	$fields = [
		'_fbd_driver_name'   => __( 'Driver name', 'luwipress-amber' ),
		'_fbd_driver_mobile' => __( 'Driver mobile', 'luwipress-amber' ),
		'_fbd_vehicle'       => __( 'Vehicle', 'luwipress-amber' ),
		'_fbd_plate'         => __( 'Plate number', 'luwipress-amber' ),
	];
	echo '<div class="lwp-amber-dispatch">';
	foreach ( $fields as $key => $label ) {
		printf(
			'<p style="margin:0 0 10px"><label for="%1$s" style="display:block;font-weight:600;margin-bottom:3px">%2$s</label><input type="text" id="%1$s" name="%1$s" value="%3$s" style="width:100%%"></p>',
			esc_attr( $key ),
			esc_html( $label ),
			esc_attr( (string) $order->get_meta( $key ) )
		);
	}
	printf(
		'<p style="margin:10px 0 0"><label><input type="checkbox" name="_fbd_voucher_ready" value="yes" %s> %s</label></p>',
		checked( 'yes', $order->get_meta( '_fbd_voucher_ready' ), false ),
		esc_html__( 'Voucher confirmed (show "Confirmed" badge + driver block)', 'luwipress-amber' )
	);
	echo '</div>';
}

/**
 * Save dispatch fields. `woocommerce_process_shop_order_meta` fires on the
 * order edit save in both legacy and HPOS.
 *
 * @param int $order_id
 */
add_action( 'woocommerce_process_shop_order_meta', function ( $order_id ) {
	if ( ! isset( $_POST['lwp_amber_dispatch_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lwp_amber_dispatch_nonce'] ) ), 'lwp_amber_dispatch_save' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_shop_orders' ) ) { return; }

	$order = wc_get_order( $order_id );
	if ( ! $order ) { return; }

	$was_ready = 'yes' === $order->get_meta( '_fbd_voucher_ready' );

	foreach ( [ '_fbd_driver_name', '_fbd_driver_mobile', '_fbd_vehicle', '_fbd_plate' ] as $key ) {
		$val = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
		$order->update_meta_data( $key, $val );
	}
	$now_ready = isset( $_POST['_fbd_voucher_ready'] ) && 'yes' === $_POST['_fbd_voucher_ready'];
	$order->update_meta_data( '_fbd_voucher_ready', $now_ready ? 'yes' : 'no' );
	$order->save();

	if ( $now_ready && ! $was_ready ) {
		/**
		 * Fires when an operator first confirms a tour voucher. Consumers can
		 * resend the voucher email / WhatsApp (Phase 3).
		 */
		do_action( 'lwp_amber_voucher_confirmed', $order_id );
	}
}, 50 );
