<?php
/**
 * My Account Dashboard endpoint.
 *
 * Replaces WC's default "Hello, {name} (not {name}? Log out)" prose with
 * a Tapadum Gold dashboard: greeting + four stat cards (orders, saved
 * instruments, addresses, lifetime spend) + recent orders table + the
 * customer's default shipping address. All data pulled from native WC
 * APIs so the layout works on any LuwiPress-Gold install with WC.
 *
 * @package luwipress-amber
 */

defined( 'ABSPATH' ) || exit;

$current_user = wp_get_current_user();
$user_id      = $current_user ? $current_user->ID : 0;

if ( ! $user_id ) {
	echo '<p>' . esc_html__( 'You must be logged in to view your account.', 'luwipress-amber' ) . '</p>';
	return;
}

// Stat: total completed orders.
$customer_orders = wc_get_orders( [
	'customer'    => $user_id,
	'status'      => [ 'wc-completed', 'wc-processing', 'wc-on-hold', 'wc-pending' ],
	'limit'       => -1,
	'return'      => 'ids',
] );
$total_orders = is_array( $customer_orders ) ? count( $customer_orders ) : 0;

// Stat: lifetime spend (sum of completed-order totals).
$lifetime_spend = 0;
$completed = wc_get_orders( [
	'customer' => $user_id,
	'status'   => [ 'wc-completed' ],
	'limit'    => -1,
] );
if ( ! is_wp_error( $completed ) ) {
	foreach ( $completed as $o ) {
		$lifetime_spend += (float) $o->get_total();
	}
}

// Stat: saved address count (default + shipping if distinct).
$customer = new WC_Customer( $user_id );
$active_addresses = 0;
if ( trim( $customer->get_billing_address_1() ) !== '' )  $active_addresses++;
if ( trim( $customer->get_shipping_address_1() ) !== '' && $customer->get_shipping_address_1() !== $customer->get_billing_address_1() ) {
	$active_addresses++;
}

// Stat: saved instruments (wishlist) — best-effort. TI WooCommerce
// Wishlist + YITH are the most common. Fall back to 0 if neither is
// active.
$saved_count = 0;
if ( function_exists( 'tinv_get_default_lists' ) && function_exists( 'tinv_wishlist_get_count_data' ) ) {
	$ids = tinv_wishlist_get_count_data( null, false );
	$saved_count = is_numeric( $ids ) ? (int) $ids : 0;
} elseif ( function_exists( 'YITH_WCWL' ) ) {
	$wl = YITH_WCWL()->count_products();
	$saved_count = is_numeric( $wl ) ? (int) $wl : 0;
}

// Recent orders (latest 5).
$recent_orders = wc_get_orders( [
	'customer' => $user_id,
	'limit'    => 5,
	'orderby'  => 'date',
	'order'    => 'DESC',
] );
?>

<header class="lwp-account-greeting">
	<span class="eyebrow" style="font-family:var(--mono);font-size:9.5px;letter-spacing:.22em;text-transform:uppercase;color:var(--muted)"><?php esc_html_e( 'Account', 'luwipress-amber' ); ?></span>
	<h1><?php
		/* translators: %s: customer first name (wrapped <em> for italic accent) */
		printf(
			wp_kses( __( 'Welcome back, <em>%s</em>.', 'luwipress-amber' ), [ 'em' => [] ] ),
			esc_html( $current_user->first_name ?: $current_user->display_name )
		);
	?></h1>
	<p class="meta lwp-acct-sub"><?php
		$member_year = $current_user->user_registered ? gmdate( 'Y', strtotime( $current_user->user_registered ) ) : '';
		$bits = [];
		if ( $member_year ) {
			/* translators: %s: registration year */
			$bits[] = sprintf( esc_html__( 'Member since %s', 'luwipress-amber' ), esc_html( $member_year ) );
		}
		/* translators: %d: total orders */
		$bits[] = sprintf( esc_html( _n( '%d order', '%d orders', $total_orders, 'luwipress-amber' ) ), $total_orders );
		/* translators: %d: saved instruments count */
		$bits[] = sprintf( esc_html( _n( '%d favorite', '%d favorites', $saved_count, 'luwipress-amber' ) ), $saved_count );
		echo esc_html( implode( ' · ', $bits ) );
	?></p>
</header>

<div class="lwp-acct-stats">
	<div class="lwp-acct-stat">
		<div class="n"><?php echo esc_html( $total_orders ); ?></div>
		<div class="l"><?php esc_html_e( 'Total orders', 'luwipress-amber' ); ?></div>
	</div>
	<div class="lwp-acct-stat">
		<div class="n"><?php echo esc_html( $saved_count ); ?></div>
		<div class="l"><?php esc_html_e( 'Saved instruments', 'luwipress-amber' ); ?></div>
	</div>
	<div class="lwp-acct-stat">
		<div class="n"><?php echo esc_html( $active_addresses ); ?></div>
		<div class="l"><?php esc_html_e( 'Active addresses', 'luwipress-amber' ); ?></div>
	</div>
	<div class="lwp-acct-stat">
		<div class="n"><?php echo wp_kses_post( wc_price( $lifetime_spend ) ); ?></div>
		<div class="l"><?php esc_html_e( 'Lifetime spend', 'luwipress-amber' ); ?></div>
	</div>
</div>

<?php if ( ! empty( $recent_orders ) ) : ?>
	<div class="lwp-acct-card">
		<h3>
			<?php esc_html_e( 'Recent orders', 'luwipress-amber' ); ?>
			<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>"><?php esc_html_e( 'View all →', 'luwipress-amber' ); ?></a>
		</h3>
		<table class="lwp-acct-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Order', 'luwipress-amber' ); ?></th>
					<th><?php esc_html_e( 'Date', 'luwipress-amber' ); ?></th>
					<th><?php esc_html_e( 'Item', 'luwipress-amber' ); ?></th>
					<th><?php esc_html_e( 'Total', 'luwipress-amber' ); ?></th>
					<th><?php esc_html_e( 'Status', 'luwipress-amber' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $recent_orders as $order ) :
					$items   = $order->get_items();
					$first   = $items ? reset( $items ) : null;
					$item_nm = $first ? $first->get_name() : '';
					$status  = $order->get_status();
					$pill_class = 'lwp-pill';
					switch ( $status ) {
						case 'completed':  $pill_class .= ' lwp-pill--delivered'; break;
						case 'processing':
						case 'pending':    $pill_class .= ' lwp-pill--processing'; break;
						case 'on-hold':    $pill_class .= ' lwp-pill--shipped'; break;
						case 'cancelled':
						case 'failed':
						case 'refunded':   $pill_class .= ' lwp-pill--cancelled'; break;
					}
					?>
					<tr>
						<td class="lwp-ord-id">#<?php echo esc_html( $order->get_order_number() ); ?></td>
						<td><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></td>
						<td class="lwp-ord-prod"><?php echo esc_html( $item_nm ); ?>
							<?php if ( count( $items ) > 1 ) : ?>
								<small><?php
									/* translators: %d: extra item count */
									printf( esc_html( _n( '+ %d more item', '+ %d more items', count( $items ) - 1, 'luwipress-amber' ) ), count( $items ) - 1 );
								?></small>
							<?php endif; ?>
						</td>
						<td><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
						<td><span class="<?php echo esc_attr( $pill_class ); ?>"><?php echo esc_html( wc_get_order_status_name( $status ) ); ?></span></td>
						<td><a href="<?php echo esc_url( $order->get_view_order_url() ); ?>" class="lwp-row-act"><?php esc_html_e( 'View', 'luwipress-amber' ); ?></a></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php else : ?>
	<div class="lwp-acct-card">
		<h3><?php esc_html_e( 'Recent orders', 'luwipress-amber' ); ?></h3>
		<p><?php esc_html_e( "You haven't placed any orders yet. Browse the catalogue to get started.", 'luwipress-amber' ); ?>
			<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="lwp-row-act" style="margin-left:8px;"><?php esc_html_e( 'Open shop →', 'luwipress-amber' ); ?></a>
		</p>
	</div>
<?php endif; ?>

<?php
$ship_addr = $customer->get_shipping_address_1() ? $customer : null;
$bill_addr = $customer->get_billing_address_1() ? $customer : null;
$default   = $ship_addr ?: $bill_addr;
if ( $default ) :
	$lines = [];
	if ( $default->get_shipping_address_1() ) {
		$lines[] = trim( $default->get_shipping_first_name() . ' ' . $default->get_shipping_last_name() );
		$lines[] = $default->get_shipping_address_1();
		if ( $default->get_shipping_address_2() ) { $lines[] = $default->get_shipping_address_2(); }
		$lines[] = trim( $default->get_shipping_postcode() . ' ' . $default->get_shipping_city() );
		$lines[] = $default->get_shipping_country();
	} else {
		$lines[] = trim( $default->get_billing_first_name() . ' ' . $default->get_billing_last_name() );
		$lines[] = $default->get_billing_address_1();
		if ( $default->get_billing_address_2() ) { $lines[] = $default->get_billing_address_2(); }
		$lines[] = trim( $default->get_billing_postcode() . ' ' . $default->get_billing_city() );
		$lines[] = $default->get_billing_country();
	}
	$lines = array_values( array_filter( $lines ) );
	?>
	<div class="lwp-acct-card">
		<h3>
			<?php esc_html_e( 'Default address', 'luwipress-amber' ); ?>
			<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-address' ) ); ?>"><?php esc_html_e( 'Edit →', 'luwipress-amber' ); ?></a>
		</h3>
		<p class="lwp-addr-display">
			<?php
			$first = array_shift( $lines );
			if ( $first ) {
				echo '<strong>' . esc_html( $first ) . '</strong><br>';
			}
			echo nl2br( esc_html( implode( "\n", $lines ) ) );
			?>
		</p>
	</div>
<?php endif; ?>
