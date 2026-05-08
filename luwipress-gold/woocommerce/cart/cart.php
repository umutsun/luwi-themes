<?php
/**
 * Cart Page — Tapadum Gold layout.
 *
 * Two-column grid: line items list (left) + sticky order summary (right).
 * Defers cart actions to the native WC `woocommerce_cart_actions` hook so
 * coupon / update-cart / remove-item buttons keep working; only the
 * visual chrome is custom.
 *
 * @package luwipress-gold
 */

defined( 'ABSPATH' ) || exit;

// Empty-cart branch — without it the foreach below iterates nothing and
// the page renders as a blank middle between header + footer, which
// looks like the theme is broken. Defers to our branded cart-empty.php
// when present; falls through to WC core's default otherwise.
if ( WC()->cart->is_empty() ) {
	wc_get_template( 'cart/cart-empty.php' );
	return;
}

do_action( 'woocommerce_before_cart' ); ?>

<form class="woocommerce-cart-form lwp-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
	<?php do_action( 'woocommerce_before_cart_table' ); ?>

	<div class="lwp-cart-grid">
		<div class="lwp-cart-list" role="region" aria-label="<?php esc_attr_e( 'Cart items', 'luwipress-gold' ); ?>">

			<?php do_action( 'woocommerce_before_cart_contents' ); ?>

			<?php
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
				$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

				if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
					continue;
				}

				$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
				$thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'thumbnail', [ 'class' => 'lwp-cart-thumb' ] ), $cart_item, $cart_item_key );
				$line_total        = apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key );

				$maker = '';
				if ( taxonomy_exists( 'pa_master' ) ) {
					$mt = wp_get_post_terms( $product_id, 'pa_master', [ 'fields' => 'names' ] );
					if ( ! is_wp_error( $mt ) && ! empty( $mt ) ) {
						$maker = $mt[0];
					}
				}
				?>
				<div class="lwp-cart-row <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
					<div class="lwp-cart-row__thumb">
						<?php
						echo $product_permalink
							? sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $thumbnail ) // phpcs:ignore
							: $thumbnail; // phpcs:ignore
						?>
					</div>

					<div class="lwp-cart-row__name">
						<?php
						if ( $product_permalink ) {
							echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_name() ), $cart_item, $cart_item_key ) );
						} else {
							echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name() . '&nbsp;', $cart_item, $cart_item_key ) );
						}
						if ( $maker ) {
							echo '<small>' . sprintf( /* translators: maker name */ esc_html__( 'by %s', 'luwipress-gold' ), esc_html( $maker ) ) . '</small>';
						}
						echo wc_get_formatted_cart_item_data( $cart_item );
						?>
					</div>

					<div class="lwp-cart-row__price">
						<?php echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); // phpcs:ignore ?>
					</div>

					<div class="lwp-cart-row__qty">
						<?php
						if ( $_product->is_sold_individually() ) {
							$product_quantity = sprintf( '1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key );
						} else {
							$product_quantity = woocommerce_quantity_input(
								[
									'input_name'   => "cart[{$cart_item_key}][qty]",
									'input_value'  => $cart_item['quantity'],
									'max_value'    => apply_filters( 'woocommerce_cart_item_max_quantity', $_product->get_max_purchase_quantity(), $_product, $cart_item, $cart_item_key ),
									'min_value'    => '0',
									'product_name' => $_product->get_name(),
								],
								$_product,
								false
							);
						}
						echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item ); // phpcs:ignore
						?>
					</div>

					<div class="lwp-cart-row__total">
						<?php echo $line_total; // phpcs:ignore ?>
					</div>

					<div class="lwp-cart-row__remove">
						<?php
						echo apply_filters( // phpcs:ignore
							'woocommerce_cart_item_remove_link',
							sprintf(
								'<a href="%s" class="lwp-cart-rm" aria-label="%s" data-product_id="%s" data-product_sku="%s">×</a>',
								esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
								esc_attr( sprintf( /* translators: %s: product name */ __( 'Remove %s from cart', 'luwipress-gold' ), wp_strip_all_tags( $_product->get_name() ) ) ),
								esc_attr( $product_id ),
								esc_attr( $_product->get_sku() )
							),
							$cart_item_key
						);
						?>
					</div>
				</div>
				<?php
			}
			?>

			<?php do_action( 'woocommerce_cart_contents' ); ?>

			<div class="lwp-cart-actions">
				<?php if ( wc_coupons_enabled() ) : ?>
					<div class="lwp-cart-coupon">
						<input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'luwipress-gold' ); ?>" />
						<button type="submit" class="lwp-btn-secondary" name="apply_coupon" value="<?php esc_attr_e( 'Apply', 'luwipress-gold' ); ?>"><?php esc_html_e( 'Apply', 'luwipress-gold' ); ?></button>
						<?php do_action( 'woocommerce_cart_coupon' ); ?>
					</div>
				<?php endif; ?>
				<button type="submit" class="lwp-btn-secondary" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'luwipress-gold' ); ?>"><?php esc_html_e( 'Update cart', 'luwipress-gold' ); ?></button>
				<?php do_action( 'woocommerce_cart_actions' ); ?>
				<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
			</div>

			<?php do_action( 'woocommerce_after_cart_contents' ); ?>
		</div>

		<aside class="lwp-cart-summary" aria-label="<?php esc_attr_e( 'Order summary', 'luwipress-gold' ); ?>">
			<?php do_action( 'woocommerce_before_cart_collaterals' ); ?>
			<div class="cart-collaterals">
				<?php
					/**
					 * Cart collaterals — totals + shipping.
					 *
					 * @hooked woocommerce_cross_sell_display
					 * @hooked woocommerce_cart_totals - 10
					 */
					do_action( 'woocommerce_cart_collaterals' );
				?>
			</div>
		</aside>

	</div>

	<?php do_action( 'woocommerce_after_cart_table' ); ?>
</form>

<?php do_action( 'woocommerce_after_cart' ); ?>

<?php
/**
 * Mobile sticky checkout bar — Mobile Spec Preview §09.
 * Rendered outside the main form so the iOS keyboard doesn't shift it.
 * CSS is gated to ≤767px in woo-overrides.css.
 */
$cart_total    = WC()->cart ? WC()->cart->get_total() : '';
$cart_count    = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
$checkout_url  = wc_get_checkout_url();
?>
<div class="lwp-sticky-cart-bar" role="region" aria-label="<?php esc_attr_e( 'Cart total and checkout', 'luwipress-gold' ); ?>">
	<div class="px">
		<span class="lbl"><?php
			/* translators: %d: cart item count */
			printf( esc_html( _n( 'Total · %d item', 'Total · %d items', $cart_count, 'luwipress-gold' ) ), $cart_count );
		?></span>
		<span class="v"><?php echo wp_kses_post( $cart_total ); ?></span>
	</div>
	<a href="<?php echo esc_url( $checkout_url ); ?>" class="button"><?php esc_html_e( 'Checkout →', 'luwipress-gold' ); ?></a>
</div>
<script>document.body.classList.add('has-lwp-sticky-bar');</script>
