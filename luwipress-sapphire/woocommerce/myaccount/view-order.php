<?php
/**
 * Account → View order detail.
 *
 * Reference: Tapadum-Account-Order-Detail.html. Three acct-card blocks
 * stacked: Tracking timeline → Items table → Summary totals. Plus a
 * CTA row with download-invoice + contact-support links.
 *
 * @package luwipress-sapphire
 */

defined( 'ABSPATH' ) || exit;

/**
 * @var int $order_id
 */
$order = wc_get_order( $order_id );
if ( ! $order ) {
	echo '<p>' . esc_html__( 'Invalid order.', 'luwipress-sapphire' ) . '</p>';
	return;
}

$status = $order->get_status();
$notes  = $order->get_customer_order_notes();

// Tracking-step computation, mirrors thankyou.php.
$track_steps = [
	[ 'lbl' => __( 'Confirmed', 'luwipress-sapphire' ),  'state' => 'done' ],
	[ 'lbl' => __( 'Processing', 'luwipress-sapphire' ), 'state' => in_array( $status, [ 'processing', 'on-hold', 'completed' ], true ) ? 'done' : ( $status === 'pending' ? 'cur' : '' ) ],
	[ 'lbl' => __( 'Dispatched', 'luwipress-sapphire' ), 'state' => $status === 'completed' ? 'done' : ( in_array( $status, [ 'processing' ], true ) ? 'cur' : '' ) ],
	[ 'lbl' => __( 'Delivered', 'luwipress-sapphire' ),  'state' => $status === 'completed' ? 'cur' : '' ],
];
?>

<h2>
	<?php
	/* translators: %s: order number */
	printf( esc_html__( 'Order #%s', 'luwipress-sapphire' ), esc_html( $order->get_order_number() ) );
	?>
</h2>
<p class="lwp-acct-sub">
	<?php
	/* translators: 1: date 2: shipping method or status name */
	printf(
		esc_html__( 'Placed on %1$s · %2$s', 'luwipress-sapphire' ),
		esc_html( wc_format_datetime( $order->get_date_created() ) ),
		esc_html( $order->get_shipping_method() ?: wc_get_order_status_name( $status ) )
	);
	?>
</p>

<?php if ( ! in_array( $status, [ 'cancelled', 'refunded', 'failed' ], true ) ) : ?>
	<div class="lwp-acct-card">
		<h3><?php esc_html_e( 'Tracking', 'luwipress-sapphire' ); ?></h3>
		<div class="lwp-track">
			<?php foreach ( $track_steps as $step ) : ?>
				<div class="lwp-track-step <?php echo esc_attr( $step['state'] ); ?>">
					<div class="dot"><?php echo $step['state'] === 'done' ? '✓' : ( $step['state'] === 'cur' ? '●' : '·' ); ?></div>
					<div class="lbl"><?php echo esc_html( $step['lbl'] ); ?></div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
<?php endif; ?>

<div class="lwp-acct-card lwp-acct-card--flush">
	<h3 class="lwp-acct-card__head"><?php esc_html_e( 'Items', 'luwipress-sapphire' ); ?></h3>
	<table class="lwp-acct-table lwp-order-items">
		<tbody>
			<?php foreach ( $order->get_items() as $item_id => $item ) :
				$_product = $item->get_product();
				$thumb    = $_product ? $_product->get_image( [ 64, 64 ], [ 'class' => 'lwp-order-item-thumb' ] ) : '';
				$name     = $item->get_name();
				$qty      = $item->get_quantity();
				$line_t   = $order->get_formatted_line_subtotal( $item );

				$maker = '';
				if ( $_product && taxonomy_exists( 'pa_master' ) ) {
					$mt = wp_get_post_terms( $_product->get_id(), 'pa_master', [ 'fields' => 'names' ] );
					if ( ! is_wp_error( $mt ) && ! empty( $mt ) ) $maker = $mt[0];
				}
				?>
				<tr>
					<td class="lwp-order-item__thumb"><?php echo $thumb; // phpcs:ignore ?></td>
					<td class="lwp-ord-prod">
						<?php
						if ( $_product && $_product->is_visible() ) {
							printf( '<a href="%s" style="color:inherit;text-decoration:none">%s</a>', esc_url( $_product->get_permalink() ), esc_html( $name ) );
						} else {
							echo esc_html( $name );
						}
						?>
						<?php if ( $maker ) : ?>
							<small><?php
								/* translators: %s: maker name */
								printf( esc_html__( 'by %s', 'luwipress-sapphire' ), esc_html( $maker ) );
							?></small>
						<?php endif; ?>
						<?php
						$meta_html = wc_display_item_meta( $item, [ 'echo' => false ] );
						if ( $meta_html ) {
							echo '<small>' . wp_kses_post( $meta_html ) . '</small>';
						}
						?>
					</td>
					<td class="lwp-order-item__qty">× <?php echo esc_html( $qty ); ?></td>
					<td class="lwp-order-item__total"><?php echo wp_kses_post( $line_t ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<div class="lwp-acct-card">
	<h3><?php esc_html_e( 'Summary', 'luwipress-sapphire' ); ?></h3>
	<div class="lwp-co-totals lwp-order-totals">
		<?php foreach ( $order->get_order_item_totals() as $total ) : ?>
			<div class="line<?php echo strpos( strtolower( $total['label'] ), 'total' ) !== false ? ' tot' : ''; ?>">
				<span><?php echo esc_html( $total['label'] ); ?></span>
				<?php if ( strpos( strtolower( $total['label'] ), 'total' ) !== false ) : ?>
					<strong><?php echo wp_kses_post( $total['value'] ); ?></strong>
				<?php else : ?>
					<span><?php echo wp_kses_post( $total['value'] ); ?></span>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
	<?php if ( $order->get_customer_note() ) : ?>
		<p class="lwp-order-note" style="margin-top:18px;padding-top:18px;border-top:1px solid var(--line);font-size:13.5px;color:var(--ink-soft);">
			<strong style="font-family:var(--mono,monospace);font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);display:block;margin-bottom:6px;"><?php esc_html_e( 'Note', 'luwipress-sapphire' ); ?></strong>
			<?php echo wp_kses_post( wpautop( wptexturize( $order->get_customer_note() ) ) ); ?>
		</p>
	<?php endif; ?>
</div>

<?php
$show_shipping = ! wc_ship_to_billing_address_only() && $order->needs_shipping_address();
$show_billing  = ! empty( $order->get_billing_address_1() );

if ( $show_billing || $show_shipping ) : ?>
	<div class="lwp-acct-card">
		<h3><?php esc_html_e( 'Addresses', 'luwipress-sapphire' ); ?></h3>
		<div class="lwp-addr-grid">
			<?php if ( $show_billing ) : ?>
				<div class="lwp-addr-card">
					<span class="lwp-addr-card__lbl"><?php esc_html_e( 'Billing', 'luwipress-sapphire' ); ?></span>
					<address><?php echo wp_kses_post( $order->get_formatted_billing_address() ?: '—' ); ?></address>
					<?php if ( $order->get_billing_phone() ) : ?>
						<p class="woocommerce-customer-details--phone"><?php echo esc_html( $order->get_billing_phone() ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<?php if ( $show_shipping ) : ?>
				<div class="lwp-addr-card">
					<span class="lwp-addr-card__lbl"><?php esc_html_e( 'Shipping', 'luwipress-sapphire' ); ?></span>
					<address><?php echo wp_kses_post( $order->get_formatted_shipping_address() ?: '—' ); ?></address>
				</div>
			<?php endif; ?>
		</div>
	</div>
<?php endif; ?>

<div class="lwp-cta-row">
	<?php
	$actions = wc_get_account_orders_actions( $order );
	if ( ! empty( $actions ) ) {
		foreach ( $actions as $key => $action ) {
			$cls = ( $key === 'pay' || $key === 'view' ) ? 'lwp-btn-primary' : 'lwp-btn-secondary';
			printf( '<a href="%s" class="%s woocommerce-button button %s">%s</a>', esc_url( $action['url'] ), esc_attr( $cls ), esc_attr( $key ), esc_html( $action['name'] ) );
		}
	}
	?>
	<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="lwp-btn-link"><?php esc_html_e( 'Continue browsing ↗', 'luwipress-sapphire' ); ?></a>
</div>
