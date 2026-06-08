<?php
/**
 * Account → Orders list.
 *
 * Reference: Tapadum-Account-Orders.html. Single acct-card wrapping a
 * full-width table; each row → status pill + row-act link → view-order.
 *
 * @package luwipress-onyx
 */

defined( 'ABSPATH' ) || exit;

/**
 * @var WC_Order[]               $customer_orders Customer orders.
 * @var bool                     $has_orders      True when there are orders.
 */
?>

<?php do_action( 'woocommerce_before_account_orders', $has_orders ); ?>

<?php if ( $has_orders ) : ?>

	<h2><?php esc_html_e( 'All orders', 'luwipress-onyx' ); ?></h2>
	<p class="lwp-acct-sub"><?php esc_html_e( "Every order you've placed. Click any row to see line items, tracking and the invoice.", 'luwipress-onyx' ); ?></p>

	<div class="lwp-acct-card lwp-acct-card--flush">
		<table class="lwp-acct-table woocommerce-orders-table">
			<thead>
				<tr>
					<?php foreach ( wc_get_account_orders_columns() as $column_id => $column_name ) : ?>
						<th class="woocommerce-orders-table__header woocommerce-orders-table__header-<?php echo esc_attr( $column_id ); ?>"><?php echo esc_html( $column_name ); ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>

			<tbody>
				<?php foreach ( $customer_orders->orders as $customer_order ) :
					$order      = wc_get_order( $customer_order );
					$item_count = $order->get_item_count() - $order->get_item_count_refunded();
					$status     = $order->get_status();
					$pill       = 'lwp-pill';
					switch ( $status ) {
						case 'completed':  $pill .= ' lwp-pill--delivered'; break;
						case 'processing':
						case 'pending':    $pill .= ' lwp-pill--processing'; break;
						case 'on-hold':    $pill .= ' lwp-pill--shipped'; break;
						case 'cancelled':
						case 'failed':
						case 'refunded':   $pill .= ' lwp-pill--cancelled'; break;
					}
					$items   = $order->get_items();
					$first   = $items ? reset( $items ) : null;
					$first_n = $first ? $first->get_name() : '';
					$extra_c = max( 0, count( $items ) - 1 );
					?>
					<tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-<?php echo esc_attr( $status ); ?> order">
						<?php foreach ( wc_get_account_orders_columns() as $column_id => $column_name ) : ?>
							<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-<?php echo esc_attr( $column_id ); ?>" data-title="<?php echo esc_attr( $column_name ); ?>">
								<?php if ( has_action( 'woocommerce_my_account_my_orders_column_' . $column_id ) ) : ?>
									<?php do_action( 'woocommerce_my_account_my_orders_column_' . $column_id, $order ); ?>

								<?php elseif ( 'order-number' === $column_id ) : ?>
									<a href="<?php echo esc_url( $order->get_view_order_url() ); ?>" class="lwp-ord-id">
										#<?php echo esc_html( $order->get_order_number() ); ?>
									</a>

								<?php elseif ( 'order-date' === $column_id ) : ?>
									<time datetime="<?php echo esc_attr( $order->get_date_created()->date( 'c' ) ); ?>">
										<?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?>
									</time>

								<?php elseif ( 'order-status' === $column_id ) : ?>
									<span class="<?php echo esc_attr( $pill ); ?>"><?php echo esc_html( wc_get_order_status_name( $status ) ); ?></span>

								<?php elseif ( 'order-total' === $column_id ) : ?>
									<?php
										echo wp_kses_post(
											sprintf(
												/* translators: 1: order total 2: order item count */
												_n( '%1$s for %2$s item', '%1$s for %2$s items', $item_count, 'luwipress-onyx' ),
												$order->get_formatted_order_total(),
												$item_count
											)
										);
									?>

								<?php elseif ( 'order-actions' === $column_id ) : ?>
									<a href="<?php echo esc_url( $order->get_view_order_url() ); ?>" class="lwp-row-act"><?php echo $status === 'processing' || $status === 'on-hold' ? esc_html__( 'Track', 'luwipress-onyx' ) : esc_html__( 'View', 'luwipress-onyx' ); ?></a>
								<?php endif; ?>
							</td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<?php do_action( 'woocommerce_before_account_orders_pagination' ); ?>

	<?php if ( 1 < $customer_orders->max_num_pages ) : ?>
		<div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination">
			<?php if ( 1 !== $current_page ) : ?>
				<a class="lwp-btn-secondary" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page - 1 ) ); ?>"><?php esc_html_e( '← Previous', 'luwipress-onyx' ); ?></a>
			<?php endif; ?>
			<?php if ( intval( $customer_orders->max_num_pages ) !== $current_page ) : ?>
				<a class="lwp-btn-secondary" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page + 1 ) ); ?>"><?php esc_html_e( 'Next →', 'luwipress-onyx' ); ?></a>
			<?php endif; ?>
		</div>
	<?php endif; ?>

<?php else : ?>

	<div class="woocommerce-message woocommerce-message--info woocommerce-Message woocommerce-Message--info woocommerce-info">
		<a class="lwp-btn-primary" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
			<?php esc_html_e( 'Browse instruments', 'luwipress-onyx' ); ?>
		</a>
		<?php esc_html_e( 'No order has been made yet.', 'luwipress-onyx' ); ?>
	</div>

<?php endif; ?>

<?php do_action( 'woocommerce_after_account_orders', $has_orders ); ?>
