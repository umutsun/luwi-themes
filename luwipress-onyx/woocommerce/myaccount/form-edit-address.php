<?php
/**
 * Account → Edit address form.
 *
 * Reference: form-grid 2-col with `.full` modifier for full-width fields
 * (address, country). Submit + cancel actions in `form-actions` flex row.
 *
 * Defers field definitions to WC's `WC_Countries->get_address_fields()`
 * so plugin filters (Polylang locale, custom fields, etc.) keep working.
 *
 * @package luwipress-onyx
 */

defined( 'ABSPATH' ) || exit;

/**
 * @var string $load_address  'billing' | 'shipping'
 * @var array  $address       Field definitions for the chosen type.
 */

$page_title = ( 'billing' === $load_address ) ? __( 'Billing address', 'luwipress-onyx' ) : __( 'Shipping address', 'luwipress-onyx' );
?>

<h2><?php echo esc_html( $page_title ); ?></h2>
<p class="lwp-acct-sub">
	<?php esc_html_e( 'Update the details we use to ship and bill your orders. Required fields are marked with an asterisk.', 'luwipress-onyx' ); ?>
</p>

<form method="post" class="lwp-acct-card lwp-edit-address-form woocommerce-EditAccountForm edit-account">

	<?php do_action( "woocommerce_before_edit_address_form_{$load_address}" ); ?>

	<div class="lwp-form-grid">
		<?php foreach ( $address as $key => $field ) :
			// Force the country + address1 fields to span both columns.
			$full_keys = [ 'billing_country', 'shipping_country', 'billing_address_1', 'shipping_address_1', 'billing_address_2', 'shipping_address_2', 'billing_company', 'shipping_company' ];
			$class     = $field['class'] ?? [];
			if ( in_array( $key, $full_keys, true ) ) {
				$class[] = 'lwp-form-full';
			}
			$field['class']        = $class;
			$field['return']       = false;

			woocommerce_form_field( $key, $field, wc_get_post_data_by_key( $key, $field['value'] ?? '' ) );
		endforeach; ?>
	</div>

	<?php do_action( "woocommerce_after_edit_address_form_{$load_address}" ); ?>

	<div class="lwp-form-actions">
		<?php wp_nonce_field( 'woocommerce-edit_address', 'woocommerce-edit-address-nonce' ); ?>
		<button type="submit" class="lwp-btn-primary woocommerce-Button button" name="save_address" value="<?php esc_attr_e( 'Save address', 'luwipress-onyx' ); ?>"><?php esc_html_e( 'Save address →', 'luwipress-onyx' ); ?></button>
		<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-address' ) ); ?>" class="lwp-btn-link"><?php esc_html_e( '← Cancel', 'luwipress-onyx' ); ?></a>
		<input type="hidden" name="action" value="edit_address" />
	</div>

</form>
