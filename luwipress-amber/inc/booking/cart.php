<?php
/**
 * LuwiPress Amber — Tour booking ↔ cart pipeline.
 *
 * Renders the `.book-box` (shared by the native PDP and the Elementor widget),
 * captures the booking on add-to-cart, recomputes the line price server-side
 * (per_person * pax + add-ons), shows the booking in the cart/checkout, and
 * persists it to order-item meta for the voucher.
 *
 * Quantity model: WC quantity stays 1; pax is baked into the unit price (a
 * booking is one transaction; the voucher shows "6 Pax" as a field).
 *
 * @package luwipress-amber
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'WooCommerce' ) ) { return; }

/**
 * Render the `.book-box` markup for a tour product. Used by:
 *   - the native PDP (woocommerce_before_add_to_cart_button, $args['form']=false)
 *   - the Tour Booking Box Elementor widget ($args['form']=true)
 *
 * @param int|WC_Product $product
 * @param array          $args  form(bool), reserve_label, show_whatsapp, whatsapp_url
 * @return string
 */
function lwp_amber_render_booking_box( $product, $args = [] ) {
	$pid = is_object( $product ) ? $product->get_id() : (int) $product;
	$cfg = lwp_amber_tour_config( $pid );
	if ( empty( $cfg ) ) { return ''; }

	$args = wp_parse_args( $args, [
		'form'          => false,
		'show_reserve'  => true,
		'reserve_label' => __( 'Reserve Your Spot', 'luwipress-amber' ),
		'show_whatsapp' => true,
		'whatsapp_url'  => '',
		'from_label'    => __( 'From', 'luwipress-amber' ),
		'per_label'     => __( '/ person', 'luwipress-amber' ),
	] );

	$sym   = $cfg['currency_symbol'];
	$pp    = $cfg['per_person'];
	$total = $pp * $cfg['pax_default'];
	$uid   = 'fbd' . wp_rand( 1000, 9999 );

	$arrow = '<svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';

	ob_start();
	if ( $args['form'] ) {
		printf(
			'<form class="bb-form" action="%s" method="post">',
			esc_url( get_permalink( $pid ) )
		);
		printf( '<input type="hidden" name="add-to-cart" value="%d">', (int) $pid );
		echo '<input type="hidden" name="quantity" value="1">';
	}
	?>
	<div class="book-box" data-per-person="<?php echo esc_attr( $pp ); ?>" data-min="<?php echo esc_attr( $cfg['pax_min'] ); ?>" data-max="<?php echo esc_attr( $cfg['pax_max'] ); ?>" data-currency="<?php echo esc_attr( $sym ); ?>">
		<div class="bb-price">
			<span class="from"><?php echo esc_html( $args['from_label'] ); ?></span>
			<span class="amt"><?php echo esc_html( $sym . lwp_amber_money_plain( $pp ) ); ?></span>
			<span class="per"><?php echo esc_html( $args['per_label'] ); ?></span>
		</div>

		<div class="bb-field">
			<label><?php esc_html_e( 'Date', 'luwipress-amber' ); ?></label>
			<div class="datepick" id="<?php echo esc_attr( $uid ); ?>">
				<button type="button" class="dp-trigger">
					<span class="dp-val placeholder"><?php esc_html_e( 'Select a date', 'luwipress-amber' ); ?></span>
					<span class="cal-ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M3 9h18M8 2.5v4M16 2.5v4"/></svg></span>
				</button>
				<div class="dp-pop">
					<div class="dp-head">
						<button type="button" class="dp-nav dp-prev" aria-label="<?php esc_attr_e( 'Previous month', 'luwipress-amber' ); ?>">‹</button>
						<span class="dp-title"></span>
						<button type="button" class="dp-nav dp-next" aria-label="<?php esc_attr_e( 'Next month', 'luwipress-amber' ); ?>">›</button>
					</div>
					<div class="dp-dow"><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span><span>Su</span></div>
					<div class="dp-days"></div>
					<div class="dp-foot">
						<button type="button" class="dp-today"><?php esc_html_e( 'Today', 'luwipress-amber' ); ?></button>
						<button type="button" class="dp-clear"><?php esc_html_e( 'Clear', 'luwipress-amber' ); ?></button>
					</div>
				</div>
				<input type="hidden" class="bb-date" name="fbd_date" value="" required>
			</div>
		</div>

		<div class="bb-field">
			<label><?php esc_html_e( 'Guests', 'luwipress-amber' ); ?></label>
			<div class="bb-stepper">
				<button type="button" class="qbtn qminus" aria-label="<?php esc_attr_e( 'Fewer guests', 'luwipress-amber' ); ?>">−</button>
				<span class="qval"><?php echo (int) $cfg['pax_default']; ?></span>
				<button type="button" class="qbtn qplus" aria-label="<?php esc_attr_e( 'More guests', 'luwipress-amber' ); ?>">+</button>
				<input type="hidden" class="bb-pax" name="fbd_pax" value="<?php echo (int) $cfg['pax_default']; ?>">
			</div>
		</div>

		<?php if ( ! empty( $cfg['time_slots'] ) ) : ?>
			<div class="bb-field">
				<label><?php esc_html_e( 'Time slot', 'luwipress-amber' ); ?></label>
				<select class="bb-slot" name="fbd_time_slot">
					<?php foreach ( $cfg['time_slots'] as $slot ) : ?>
						<option value="<?php echo esc_attr( $slot ); ?>"><?php echo esc_html( $slot ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $cfg['addons'] ) ) : ?>
			<div class="bb-field">
				<label><?php esc_html_e( 'Add-ons', 'luwipress-amber' ); ?></label>
				<?php foreach ( $cfg['addons'] as $i => $addon ) :
					$a_price = (float) ( $addon['price'] ?? 0 ); ?>
					<label class="bb-addon">
						<span class="bb-ck"><input type="checkbox" name="fbd_addons[]" value="<?php echo (int) $i; ?>" data-cost="<?php echo esc_attr( $a_price ); ?>"><span class="box"></span></span>
						<span class="bb-addon-t"><?php echo esc_html( $addon['label'] ?? '' ); ?></span>
						<span class="bb-addon-p">+<?php echo esc_html( $sym . lwp_amber_money_plain( $a_price ) ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="bb-total">
			<span><?php esc_html_e( 'Total', 'luwipress-amber' ); ?></span>
			<span class="t-amt"><?php echo esc_html( $sym . lwp_amber_money_plain( $total ) ); ?></span>
		</div>

		<?php if ( $args['show_reserve'] ) : ?>
			<button type="submit" class="btn btn--coral bb-reserve"><?php echo esc_html( $args['reserve_label'] ); ?> <?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
		<?php endif; ?>

		<?php
		$wa = $args['whatsapp_url'];
		if ( $args['show_whatsapp'] && $wa ) : ?>
			<a class="bb-wa" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.5 15.2L2 22l4.9-1.3A10 10 0 1 0 12 2Z"/></svg> <?php esc_html_e( 'Ask on WhatsApp', 'luwipress-amber' ); ?></a>
		<?php endif; ?>

		<?php if ( $cfg['cancellation'] ) : ?>
			<div class="bb-note"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M9 12l2 2 4-4"/></svg> <?php echo esc_html( $cfg['cancellation'] ); ?></div>
		<?php endif; ?>
	</div>
	<?php
	if ( $args['form'] ) {
		echo '</form>';
	}
	return ob_get_clean();
}

/**
 * Plain (no-symbol) money string for inline display next to a JS-mirrored symbol.
 */
function lwp_amber_money_plain( $amount ) {
	$decimals = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2;
	// Tours are usually whole-number prices; trim trailing zeros for "AED 240".
	$amount = (float) $amount;
	if ( floor( $amount ) === $amount ) {
		return number_format_i18n( $amount, 0 );
	}
	return number_format_i18n( $amount, $decimals );
}

/* ── Native PDP fallback (inside the WC add-to-cart form) ──────────────── */
add_action( 'woocommerce_before_add_to_cart_button', function () {
	global $product;
	if ( ! lwp_amber_is_tour( $product ) ) { return; }
	// Native fallback: fields ride inside WC's own add-to-cart <form>, and WC's
	// "Add to cart" button submits them — so no second reserve button here.
	echo lwp_amber_render_booking_box( $product, [ 'form' => false, 'show_reserve' => false ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within renderer.
}, 15 );

/* ── Tours are sold individually (qty locked to 1; pax drives the price) ─ */
add_filter( 'woocommerce_is_sold_individually', function ( $individually, $product ) {
	return lwp_amber_is_tour( $product ) ? true : $individually;
}, 10, 2 );

/* ── Require a tour date before add-to-cart (graceful abort, no throw) ─── */
add_filter( 'woocommerce_add_to_cart_validation', function ( $passed, $product_id ) {
	if ( ! lwp_amber_is_tour( $product_id ) ) {
		return $passed;
	}
	$date = isset( $_POST['fbd_date'] ) ? sanitize_text_field( wp_unslash( $_POST['fbd_date'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC add-to-cart is nonce-free by design.
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		wc_add_notice( __( 'Please choose a tour date before reserving.', 'luwipress-amber' ), 'error' );
		return false;
	}
	return $passed;
}, 10, 2 );

/* ── Capture booking on add-to-cart ───────────────────────────────────── */
add_filter( 'woocommerce_add_cart_item_data', function ( $cart_item_data, $product_id ) {
	if ( ! lwp_amber_is_tour( $product_id ) ) {
		return $cart_item_data;
	}
	$cfg = lwp_amber_tour_config( $product_id );

	$date = isset( $_POST['fbd_date'] ) ? sanitize_text_field( wp_unslash( $_POST['fbd_date'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC add-to-cart is nonce-free by design.
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		// The validation filter above already aborted; bail defensively.
		return $cart_item_data;
	}

	$pax = isset( $_POST['fbd_pax'] ) ? absint( $_POST['fbd_pax'] ) : $cfg['pax_default']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$pax = max( $cfg['pax_min'], min( $cfg['pax_max'], $pax ) );

	$slot = isset( $_POST['fbd_time_slot'] ) ? sanitize_text_field( wp_unslash( $_POST['fbd_time_slot'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( $slot && ! in_array( $slot, $cfg['time_slots'], true ) ) { $slot = ''; }

	// Resolve checked add-ons against the product config (never trust posted prices).
	$chosen = [];
	if ( isset( $_POST['fbd_addons'] ) && is_array( $_POST['fbd_addons'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		foreach ( wp_unslash( $_POST['fbd_addons'] ) as $idx ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$idx = (int) $idx;
			if ( isset( $cfg['addons'][ $idx ] ) ) {
				$chosen[] = [
					'label' => (string) $cfg['addons'][ $idx ]['label'],
					'price' => (float) $cfg['addons'][ $idx ]['price'],
				];
			}
		}
	}

	$cart_item_data['fbd'] = [
		'date'       => $date,
		'pax'        => $pax,
		'time_slot'  => $slot,
		'addons'     => $chosen,
		'per_person' => (float) $cfg['per_person'],
	];
	return $cart_item_data;
}, 10, 2 );

/* ── Restore from session ─────────────────────────────────────────────── */
add_filter( 'woocommerce_get_cart_item_from_session', function ( $cart_item, $values ) {
	if ( isset( $values['fbd'] ) ) {
		$cart_item['fbd'] = $values['fbd'];
	}
	return $cart_item;
}, 10, 2 );

/* ── Recompute line price from the stored snapshot (idempotent) ────────── */
add_action( 'woocommerce_before_calculate_totals', function ( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) { return; }
	if ( ! $cart || ! method_exists( $cart, 'get_cart' ) ) { return; }
	foreach ( $cart->get_cart() as $cart_item ) {
		if ( empty( $cart_item['fbd'] ) || empty( $cart_item['data'] ) ) { continue; }
		$fbd  = $cart_item['fbd'];
		$pax  = max( 1, (int) ( $fbd['pax'] ?? 1 ) );
		$unit = (float) ( $fbd['per_person'] ?? 0 ) * $pax;
		if ( ! empty( $fbd['addons'] ) && is_array( $fbd['addons'] ) ) {
			foreach ( $fbd['addons'] as $a ) {
				$unit += (float) ( $a['price'] ?? 0 ) * $pax;
			}
		}
		$cart_item['data']->set_price( $unit );
	}
}, 20 );

/* ── Show the booking in cart + checkout review ───────────────────────── */
add_filter( 'woocommerce_get_item_data', function ( $item_data, $cart_item ) {
	if ( empty( $cart_item['fbd'] ) ) { return $item_data; }
	$fbd = $cart_item['fbd'];
	$item_data[] = [ 'key' => __( 'Date', 'luwipress-amber' ), 'value' => lwp_amber_format_voucher_date( $fbd['date'] ?? '' ) ];
	$item_data[] = [ 'key' => __( 'Guests', 'luwipress-amber' ), 'value' => (string) ( $fbd['pax'] ?? '' ) ];
	if ( ! empty( $fbd['time_slot'] ) ) {
		$item_data[] = [ 'key' => __( 'Time slot', 'luwipress-amber' ), 'value' => $fbd['time_slot'] ];
	}
	if ( ! empty( $fbd['addons'] ) ) {
		$labels = array_map( function ( $a ) { return $a['label']; }, $fbd['addons'] );
		$item_data[] = [ 'key' => __( 'Add-ons', 'luwipress-amber' ), 'value' => implode( ', ', $labels ) ];
	}
	return $item_data;
}, 10, 2 );

/* ── Persist booking to order-item meta ───────────────────────────────── */
add_action( 'woocommerce_checkout_create_order_line_item', function ( $item, $cart_item_key, $values, $order ) {
	if ( empty( $values['fbd'] ) ) { return; }
	$fbd = $values['fbd'];
	$item->update_meta_data( '_fbd_tour_date', (string) ( $fbd['date'] ?? '' ) );
	$item->update_meta_data( '_fbd_pax', (int) ( $fbd['pax'] ?? 0 ) );
	if ( ! empty( $fbd['time_slot'] ) ) {
		$item->update_meta_data( '_fbd_time_slot', (string) $fbd['time_slot'] );
	}
	$item->update_meta_data( '_fbd_per_person', (float) ( $fbd['per_person'] ?? 0 ) );
	if ( ! empty( $fbd['addons'] ) ) {
		$item->update_meta_data( '_fbd_addons_json', wp_json_encode( $fbd['addons'] ) );
		// Readable line for the order/email.
		foreach ( $fbd['addons'] as $a ) {
			$item->add_meta_data( __( 'Add-on', 'luwipress-amber' ), $a['label'], false );
		}
	}
}, 10, 4 );
