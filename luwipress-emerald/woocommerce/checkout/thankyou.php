<?php
/**
 * Thank-you / Order received page.
 *
 * Reference: Tapadum-Order-Received.html — centred hero (green check
 * icon + eyebrow + title + lead) + 4-cell meta grid (order# / date /
 * total / payment) + acct-card with timeline + CTA row.
 *
 * @package luwipress-emerald
 */

defined( 'ABSPATH' ) || exit;

/**
 * @var WC_Order $order
 */
?>

<div class="woocommerce-order">
	<?php if ( $order ) :
		do_action( 'woocommerce_before_thankyou', $order->get_id() );

		if ( $order->has_status( 'failed' ) ) : ?>

			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed">
				<?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'luwipress-emerald' ); ?>
			</p>

			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions">
				<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="lwp-btn-primary"><?php esc_html_e( 'Pay', 'luwipress-emerald' ); ?></a>
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="lwp-btn-secondary"><?php esc_html_e( 'My account', 'luwipress-emerald' ); ?></a>
				<?php endif; ?>
			</p>

		<?php else :
			$first_name = $order->get_billing_first_name() ?: $order->get_formatted_billing_full_name();
			$payment    = $order->get_payment_method_title() ?: __( 'On account', 'luwipress-emerald' );
		?>
			<div class="lwp-ty-hero">
				<div class="lwp-ty-check" aria-hidden="true">✓</div>
				<span class="lwp-eyebrow"><?php esc_html_e( '— Order received', 'luwipress-emerald' ); ?></span>
				<h1 class="lwp-page-title">
					<?php
					/* translators: %s: customer first name */
					printf( esc_html__( 'Thank you, %s.', 'luwipress-emerald' ), esc_html( $first_name ) );
					?>
					<br><em><?php esc_html_e( 'Your instrument is in good hands.', 'luwipress-emerald' ); ?></em>
				</h1>
				<p class="lwp-page-lead">
					<?php
					/* translators: %s: customer email */
					printf(
						wp_kses( __( 'A confirmation has been sent to <strong>%s</strong>. Our atelier prepares each instrument by hand before dispatching with the carrier of your choice.', 'luwipress-emerald' ), [ 'strong' => [] ] ),
						esc_html( $order->get_billing_email() )
					);
					?>
				</p>
			</div>

			<div class="lwp-ty-meta">
				<div>
					<div class="lbl"><?php esc_html_e( 'Order number', 'luwipress-emerald' ); ?></div>
					<div class="val">#<?php echo esc_html( $order->get_order_number() ); ?></div>
				</div>
				<div>
					<div class="lbl"><?php esc_html_e( 'Date', 'luwipress-emerald' ); ?></div>
					<div class="val"><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></div>
				</div>
				<div>
					<div class="lbl"><?php esc_html_e( 'Total paid', 'luwipress-emerald' ); ?></div>
					<div class="val"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></div>
				</div>
				<div>
					<div class="lbl"><?php esc_html_e( 'Payment', 'luwipress-emerald' ); ?></div>
					<div class="val"><?php echo esc_html( $payment ); ?></div>
				</div>
			</div>

			<?php
			// Build a 4-step tracking timeline from the order status. WC
			// statuses: pending → processing/on-hold → completed (we treat
			// completed as "delivered"). Failed/cancelled are not surfaced
			// here — they hit the failed branch above.
			$status = $order->get_status();
			$steps  = [
				[ 'lbl' => __( 'Confirmed', 'luwipress-emerald' ),  'state' => 'done' ],
				[ 'lbl' => __( 'Processing', 'luwipress-emerald' ), 'state' => in_array( $status, [ 'processing', 'on-hold', 'completed' ], true ) ? 'done' : ( $status === 'pending' ? 'cur' : '' ) ],
				[ 'lbl' => __( 'Dispatched', 'luwipress-emerald' ), 'state' => $status === 'completed' ? 'done' : ( in_array( $status, [ 'processing' ], true ) ? 'cur' : '' ) ],
				[ 'lbl' => __( 'Delivered', 'luwipress-emerald' ),  'state' => $status === 'completed' ? 'cur' : '' ],
			];
			?>
			<div class="lwp-acct-card">
				<h3><?php esc_html_e( 'What happens next', 'luwipress-emerald' ); ?></h3>
				<div class="lwp-track">
					<?php foreach ( $steps as $step ) : ?>
						<div class="lwp-track-step <?php echo esc_attr( $step['state'] ); ?>">
							<div class="dot"><?php echo $step['state'] === 'done' ? '✓' : ( $step['state'] === 'cur' ? '●' : '·' ); ?></div>
							<div class="lbl"><?php echo esc_html( $step['lbl'] ); ?></div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="lwp-cta-row">
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( $order->get_view_order_url() ); ?>" class="lwp-btn-primary"><?php esc_html_e( 'Track this order →', 'luwipress-emerald' ); ?></a>
				<?php endif; ?>
				<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="lwp-btn-link"><?php esc_html_e( 'Continue browsing ↗', 'luwipress-emerald' ); ?></a>
			</div>

			<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
			<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>
		<?php endif; ?>

	<?php else : ?>

		<p class="woocommerce-notice woocommerce-notice--info woocommerce-thankyou-order-received"><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Thank you. Your order has been received.', 'luwipress-emerald' ), null ); // phpcs:ignore ?></p>

	<?php endif; ?>
</div>
