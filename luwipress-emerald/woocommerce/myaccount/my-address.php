<?php
/**
 * Account → Saved addresses (billing + shipping).
 *
 * Reference: Tapadum-Account-Addresses.html. 2-card grid; each card =
 * uppercase mono label + Playfair name + multi-line address + edit link.
 *
 * @package luwipress-emerald
 */

defined( 'ABSPATH' ) || exit;

$customer_id           = get_current_user_id();
$get_addresses         = apply_filters( 'woocommerce_my_account_get_addresses', [
	'billing'  => __( 'Billing', 'luwipress-emerald' ),
	'shipping' => __( 'Shipping', 'luwipress-emerald' ),
], $customer_id );

$ship_to_billing_only  = wc_ship_to_billing_address_only();
if ( $ship_to_billing_only ) {
	unset( $get_addresses['shipping'] );
}
?>

<h2><?php esc_html_e( 'Saved addresses', 'luwipress-emerald' ); ?></h2>
<p class="lwp-acct-sub"><?php esc_html_e( 'These addresses are used by default at checkout. You can update them at any time, or add a new address from the cart.', 'luwipress-emerald' ); ?></p>

<?php if ( ! $ship_to_billing_only ) : ?>
	<p class="lwp-acct-sub" style="margin-top:-16px;font-size:13px;">
		<?php esc_html_e( 'The following addresses will be used on the checkout page by default.', 'luwipress-emerald' ); ?>
	</p>
<?php endif; ?>

<div class="lwp-addr-grid">
	<?php foreach ( $get_addresses as $name => $title ) :
		$address = wc_get_account_formatted_address( $name );
		$is_default = ( $name === 'billing' );
		?>
		<div class="lwp-addr-card">
			<span class="lwp-addr-card__lbl">
				<?php
				if ( $is_default ) {
					/* translators: %s: address type */
					printf( esc_html__( 'Default · %s', 'luwipress-emerald' ), esc_html( strtolower( $title ) ) );
				} else {
					echo esc_html( $title );
				}
				?>
			</span>
			<?php if ( $address ) : ?>
				<address><?php echo wp_kses_post( $address ); ?></address>
			<?php else : ?>
				<p class="lwp-addr-card__empty"><?php esc_html_e( 'You have not set up this type of address yet.', 'luwipress-emerald' ); ?></p>
			<?php endif; ?>
			<a href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address', $name ) ); ?>" class="lwp-addr-card__edit">
				<?php echo $address ? esc_html__( 'Edit address →', 'luwipress-emerald' ) : esc_html__( 'Add address →', 'luwipress-emerald' ); ?>
			</a>
		</div>
	<?php endforeach; ?>
</div>
