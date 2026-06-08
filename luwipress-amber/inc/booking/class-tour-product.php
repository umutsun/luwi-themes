<?php
/**
 * LuwiPress Amber — Tour product config (admin "Tour / Booking" data tab).
 *
 * Adds a product-data tab where an operator marks a product as a bookable tour
 * and sets its booking attributes (duration, pax range, pickup, deposit, time
 * slots, add-ons). The per-person price is the product's own WooCommerce price
 * — never duplicated. Saved via WC CRUD so it's HPOS/variation-safe; mirrored
 * to register_post_meta for REST + WPML translation visibility.
 *
 * @package luwipress-amber
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'WooCommerce' ) ) { return; }

/* ── Tab ───────────────────────────────────────────────────────────────── */
add_filter( 'woocommerce_product_data_tabs', function ( $tabs ) {
	$tabs['lwp_amber_tour'] = [
		'label'    => __( 'Tour / Booking', 'luwipress-amber' ),
		'target'   => 'lwp_amber_tour_data',
		'class'    => [ 'show_if_simple', 'show_if_variable' ],
		'priority' => 21,
	];
	return $tabs;
} );

/* ── Panel ─────────────────────────────────────────────────────────────── */
add_action( 'woocommerce_product_data_panels', function () {
	global $post;
	$pid = $post ? (int) $post->ID : 0;

	$time_slots = get_post_meta( $pid, '_fbd_time_slots', true );
	$time_slots = is_array( $time_slots ) ? implode( "\n", $time_slots ) : '';

	$addons = get_post_meta( $pid, '_fbd_addons', true );
	$addons_text = '';
	if ( is_array( $addons ) ) {
		foreach ( $addons as $a ) {
			if ( ! is_array( $a ) || empty( $a['label'] ) ) { continue; }
			$addons_text .= $a['label'] . ' | ' . ( isset( $a['price'] ) ? $a['price'] : '0' ) . "\n";
		}
	}
	?>
	<div id="lwp_amber_tour_data" class="panel woocommerce_options_panel hidden">
		<div class="options_group">
			<?php
			woocommerce_wp_checkbox( [
				'id'          => '_fbd_is_tour',
				'label'       => __( 'Bookable tour', 'luwipress-amber' ),
				'description' => __( 'Enable the date + guests booking box and trip/voucher fields for this product.', 'luwipress-amber' ),
			] );
			?>
		</div>
		<div class="options_group">
			<?php
			woocommerce_wp_text_input( [
				'id'          => '_fbd_duration',
				'label'       => __( 'Duration (label)', 'luwipress-amber' ),
				'placeholder' => __( 'e.g. 6 hours', 'luwipress-amber' ),
				'desc_tip'    => true,
				'description' => __( 'Shown on the tour card and Quick Facts.', 'luwipress-amber' ),
			] );
			woocommerce_wp_select( [
				'id'      => '_fbd_duration_bucket',
				'label'   => __( 'Duration bucket', 'luwipress-amber' ),
				'options' => [
					''      => __( '— (auto)', 'luwipress-amber' ),
					'short' => __( 'Up to 3 hours', 'luwipress-amber' ),
					'half'  => __( 'Half day (3–6h)', 'luwipress-amber' ),
					'full'  => __( 'Full day', 'luwipress-amber' ),
					'multi' => __( 'Multi-day', 'luwipress-amber' ),
				],
				'desc_tip'    => true,
				'description' => __( 'Used by the Tour Packages duration filter.', 'luwipress-amber' ),
			] );
			woocommerce_wp_text_input( [
				'id'                => '_fbd_pax_min',
				'label'             => __( 'Minimum guests', 'luwipress-amber' ),
				'type'              => 'number',
				'custom_attributes' => [ 'min' => '1', 'step' => '1' ],
			] );
			woocommerce_wp_text_input( [
				'id'                => '_fbd_pax_max',
				'label'             => __( 'Maximum guests', 'luwipress-amber' ),
				'type'              => 'number',
				'custom_attributes' => [ 'min' => '1', 'step' => '1' ],
			] );
			woocommerce_wp_text_input( [
				'id'                => '_fbd_pax_default',
				'label'             => __( 'Default guests', 'luwipress-amber' ),
				'type'              => 'number',
				'custom_attributes' => [ 'min' => '1', 'step' => '1' ],
			] );
			?>
		</div>
		<div class="options_group">
			<?php
			woocommerce_wp_checkbox( [
				'id'    => '_fbd_pickup_included',
				'label' => __( 'Hotel pickup included', 'luwipress-amber' ),
			] );
			woocommerce_wp_text_input( [
				'id'          => '_fbd_cancellation',
				'label'       => __( 'Cancellation note', 'luwipress-amber' ),
				'placeholder' => __( 'Free cancellation up to 24h before', 'luwipress-amber' ),
			] );
			woocommerce_wp_text_input( [
				'id'                => '_fbd_deposit_pct',
				'label'             => __( 'Deposit %', 'luwipress-amber' ),
				'type'              => 'number',
				'custom_attributes' => [ 'min' => '0', 'max' => '100', 'step' => '1' ],
				'desc_tip'          => true,
				'description'       => __( '0 = pay in full. Deposit checkout option is a Phase 2 feature.', 'luwipress-amber' ),
			] );
			?>
		</div>
		<div class="options_group">
			<p class="form-field">
				<label for="_fbd_time_slots"><?php esc_html_e( 'Time slots', 'luwipress-amber' ); ?></label>
				<textarea id="_fbd_time_slots" name="_fbd_time_slots" rows="3" style="width:60%" placeholder="<?php esc_attr_e( 'One per line, e.g. Morning · Sunset · Evening', 'luwipress-amber' ); ?>"><?php echo esc_textarea( $time_slots ); ?></textarea>
				<span class="description"><?php esc_html_e( 'One slot per line. Leave empty for no time selection.', 'luwipress-amber' ); ?></span>
			</p>
			<p class="form-field">
				<label for="_fbd_addons"><?php esc_html_e( 'Add-ons', 'luwipress-amber' ); ?></label>
				<textarea id="_fbd_addons" name="_fbd_addons" rows="4" style="width:60%" placeholder="<?php esc_attr_e( 'One per line: Label | Price  (e.g. Quad bike | 120)', 'luwipress-amber' ); ?>"><?php echo esc_textarea( $addons_text ); ?></textarea>
				<span class="description"><?php esc_html_e( 'Format: Label | Price — one per line. Price is per person, in the store currency.', 'luwipress-amber' ); ?></span>
			</p>
		</div>
	</div>
	<?php
} );

/* ── Save ──────────────────────────────────────────────────────────────── */
add_action( 'woocommerce_admin_process_product_object', function ( $product ) {
	// Checkboxes.
	$product->update_meta_data( '_fbd_is_tour', isset( $_POST['_fbd_is_tour'] ) ? 'yes' : 'no' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC verifies the product save nonce upstream.
	$product->update_meta_data( '_fbd_pickup_included', isset( $_POST['_fbd_pickup_included'] ) ? 'yes' : 'no' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

	// Scalars.
	$product->update_meta_data( '_fbd_duration', isset( $_POST['_fbd_duration'] ) ? sanitize_text_field( wp_unslash( $_POST['_fbd_duration'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$bucket = isset( $_POST['_fbd_duration_bucket'] ) ? sanitize_text_field( wp_unslash( $_POST['_fbd_duration_bucket'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$product->update_meta_data( '_fbd_duration_bucket', in_array( $bucket, [ 'short', 'half', 'full', 'multi' ], true ) ? $bucket : '' );
	$product->update_meta_data( '_fbd_cancellation', isset( $_POST['_fbd_cancellation'] ) ? sanitize_text_field( wp_unslash( $_POST['_fbd_cancellation'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

	$product->update_meta_data( '_fbd_pax_min', isset( $_POST['_fbd_pax_min'] ) ? max( 1, absint( $_POST['_fbd_pax_min'] ) ) : 1 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$product->update_meta_data( '_fbd_pax_max', isset( $_POST['_fbd_pax_max'] ) ? max( 1, absint( $_POST['_fbd_pax_max'] ) ) : 12 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$product->update_meta_data( '_fbd_pax_default', isset( $_POST['_fbd_pax_default'] ) ? max( 1, absint( $_POST['_fbd_pax_default'] ) ) : 2 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$product->update_meta_data( '_fbd_deposit_pct', isset( $_POST['_fbd_deposit_pct'] ) ? max( 0, min( 100, absint( $_POST['_fbd_deposit_pct'] ) ) ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

	// Time slots (one per line).
	$slots_raw = isset( $_POST['_fbd_time_slots'] ) ? wp_unslash( $_POST['_fbd_time_slots'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in helper.
	$product->update_meta_data( '_fbd_time_slots', lwp_amber_sanitize_string_list( $slots_raw ) );

	// Add-ons ("Label | Price" per line).
	$addons_raw = isset( $_POST['_fbd_addons'] ) ? wp_unslash( $_POST['_fbd_addons'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$addons = [];
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $addons_raw ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) { continue; }
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		$label = $parts[0];
		if ( '' === $label ) { continue; }
		$price = isset( $parts[1] ) ? (float) wc_format_decimal( $parts[1] ) : 0.0;
		$addons[] = [ 'label' => $label, 'price' => $price ];
	}
	$product->update_meta_data( '_fbd_addons', $addons );
} );

/* ── REST + translation visibility ─────────────────────────────────────── */
add_action( 'init', function () {
	$scalar = [
		'_fbd_is_tour'         => 'string',
		'_fbd_duration'        => 'string',
		'_fbd_duration_bucket' => 'string',
		'_fbd_pax_min'         => 'integer',
		'_fbd_pax_max'         => 'integer',
		'_fbd_pax_default'     => 'integer',
		'_fbd_pickup_included' => 'string',
		'_fbd_cancellation'    => 'string',
		'_fbd_deposit_pct'     => 'integer',
	];
	foreach ( $scalar as $key => $type ) {
		register_post_meta( 'product', $key, [
			'type'          => $type,
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => function () { return current_user_can( 'edit_products' ); },
		] );
	}
}, 20 );
