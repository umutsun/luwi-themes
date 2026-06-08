<?php
/**
 * Checkout Form — Tapadum Gold layout.
 *
 * Hooks faithfully into WC's checkout pipeline (woocommerce_checkout_*
 * actions retain their standard semantics, including AJAX field
 * validation and update_order_review). The visual chrome is restyled
 * to match the reference: customer-details on the left as numbered
 * `co-step` cards (Contact → Shipping → Payment), with a sticky
 * `co-summary` order review on the right.
 *
 * @package luwipress-emerald
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_checkout_form', $checkout );

// Logged-out users → optional login prompt above the form.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'luwipress-emerald' ) ) );
	return;
}
?>

<div class="lwp-checkout-steps" role="presentation" aria-hidden="true">
	<span class="done"></span>
	<span class="current"></span>
	<span></span>
</div>

<details class="lwp-order-summary-collapsible" open>
	<summary>
		<span class="lbl"><?php
			$cart_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
			/* translators: %d: cart item count */
			printf( esc_html( _n( 'Order summary · %d item', 'Order summary · %d items', $cart_count, 'luwipress-emerald' ) ), $cart_count );
		?></span>
		<span class="v"><?php echo wp_kses_post( WC()->cart ? WC()->cart->get_total() : '' ); ?></span>
	</summary>
</details>


<form name="checkout" method="post" class="checkout woocommerce-checkout lwp-co-form" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

	<div class="lwp-co-grid">

		<div class="lwp-co-main">
			<?php if ( $checkout->get_checkout_fields() ) : ?>

				<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

				<section class="lwp-co-step">
					<h3><span class="lwp-co-stnum">1</span> <?php esc_html_e( 'Contact', 'luwipress-emerald' ); ?></h3>
					<p class="lwp-co-stsub">
						<?php esc_html_e( "We'll send the order confirmation here.", 'luwipress-emerald' ); ?>
						<?php if ( ! is_user_logged_in() ) : ?>
							<?php
							/* translators: %s sign-in link */
							printf( esc_html__( 'Already a customer? %s', 'luwipress-emerald' ), '<a href="' . esc_url( wp_login_url( wc_get_checkout_url() ) ) . '" style="color:var(--primary);text-decoration:underline">' . esc_html__( 'Sign in', 'luwipress-emerald' ) . '</a>' );
							?>
						<?php endif; ?>
					</p>
					<?php
					// Render the email field on its own (then the rest of the
					// billing fields go in step 2). WC ships email inside the
					// billing fieldset; pull just that field here.
					$fields = $checkout->get_checkout_fields( 'billing' );
					if ( isset( $fields['billing_email'] ) ) {
						woocommerce_form_field( 'billing_email', $fields['billing_email'], $checkout->get_value( 'billing_email' ) );
					}
					?>
				</section>

				<section class="lwp-co-step">
					<h3><span class="lwp-co-stnum">2</span> <?php esc_html_e( 'Shipping address', 'luwipress-emerald' ); ?></h3>
					<p class="lwp-co-stsub"><?php esc_html_e( 'DHL Express · arrives in 3–7 working days worldwide.', 'luwipress-emerald' ); ?></p>
					<div class="woocommerce-billing-fields lwp-co-fields">
						<?php do_action( 'woocommerce_checkout_billing' ); ?>
					</div>
					<div class="woocommerce-shipping-fields lwp-co-fields">
						<?php do_action( 'woocommerce_checkout_shipping' ); ?>
					</div>
				</section>

				<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

			<?php endif; ?>

			<section class="lwp-co-step">
				<h3><span class="lwp-co-stnum">3</span> <?php esc_html_e( 'Payment', 'luwipress-emerald' ); ?></h3>
				<p class="lwp-co-stsub"><?php esc_html_e( 'All transactions are encrypted. We never store your card.', 'luwipress-emerald' ); ?></p>
				<div id="payment_wrapper">
					<?php
					// `woocommerce_checkout_payment` outputs payment methods +
					// terms checkbox + place-order button inside `<div id="payment">`.
					// We rely on it for AJAX rebinding, so don't tear it apart.
					do_action( 'woocommerce_checkout_before_order_review_heading' );
					do_action( 'woocommerce_checkout_before_order_review' );
					?>
					<div id="order_review" class="woocommerce-checkout-review-order">
						<?php do_action( 'woocommerce_checkout_order_review' ); ?>
					</div>
					<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
				</div>
			</section>
		</div>

		<aside class="lwp-co-summary" aria-label="<?php esc_attr_e( 'Order summary', 'luwipress-emerald' ); ?>">
			<h3><?php esc_html_e( 'Your order', 'luwipress-emerald' ); ?></h3>
			<?php
			// Compact item list — pulled directly from the cart, not the
			// review-order template (which is rendered inside step 3 above
			// for AJAX field reactivity).
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
				if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 ) continue;
				$thumb = $_product->get_image( 'thumbnail', [ 'class' => 'lwp-co-item-img' ] );
				?>
				<div class="lwp-co-item">
					<div class="lwp-co-item-thumb">
						<?php echo $thumb; // phpcs:ignore ?>
						<span class="lwp-co-item-qty"><?php echo (int) $cart_item['quantity']; ?></span>
					</div>
					<div class="lwp-co-item-name">
						<?php echo esc_html( $_product->get_name() ); ?>
						<?php
						$meta = wc_get_formatted_cart_item_data( $cart_item );
						if ( $meta ) echo '<small>' . wp_kses_post( $meta ) . '</small>';
						?>
					</div>
					<div class="lwp-co-item-px">
						<?php echo wp_kses_post( WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ) ); ?>
					</div>
				</div>
				<?php
			}
			?>
			<div class="lwp-co-totals">
				<div class="line"><span><?php esc_html_e( 'Subtotal', 'luwipress-emerald' ); ?></span><span><?php wc_cart_totals_subtotal_html(); ?></span></div>
				<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
					<div class="line"><span><?php wc_cart_totals_coupon_label( $coupon ); ?></span><span><?php wc_cart_totals_coupon_html( $coupon ); ?></span></div>
				<?php endforeach; ?>
				<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
					<div class="line"><span><?php esc_html_e( 'Shipping', 'luwipress-emerald' ); ?></span><span><?php wc_cart_totals_shipping_html(); ?></span></div>
				<?php endif; ?>
				<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
					<div class="line"><span><?php echo esc_html( $fee->name ); ?></span><span><?php wc_cart_totals_fee_html( $fee ); ?></span></div>
				<?php endforeach; ?>
				<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : ?>
					<div class="line"><span><?php echo esc_html( $tax->label ); ?></span><span><?php echo wp_kses_post( $tax->formatted_amount ); ?></span></div>
				<?php endforeach; endif; ?>
				<div class="line tot"><span><?php esc_html_e( 'Total', 'luwipress-emerald' ); ?></span><strong><?php wc_cart_totals_order_total_html(); ?></strong></div>
			</div>
		</aside>

	</div>

</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>

<?php
// Sticky pay-total bar — Mobile Spec §10. Mirrors Woo's #place_order via JS click.
?>
<div class="lwp-sticky-cart-bar" role="region" aria-label="<?php esc_attr_e( 'Pay total and place order', 'luwipress-emerald' ); ?>">
	<div class="px">
		<span class="lbl"><?php esc_html_e( 'Pay total', 'luwipress-emerald' ); ?></span>
		<span class="v"><?php echo wp_kses_post( WC()->cart ? WC()->cart->get_total() : '' ); ?></span>
	</div>
	<button type="button" class="button" onclick="var b=document.getElementById('place_order');if(b){b.click();}"><?php esc_html_e( 'Place order →', 'luwipress-emerald' ); ?></button>
</div>
<script>document.body.classList.add('has-lwp-sticky-bar');</script>
