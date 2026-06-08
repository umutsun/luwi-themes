<?php
/**
 * Account → Edit account details.
 *
 * Reference: Tapadum-Account-Details.html — three stacked acct-card
 * sections (Personal info, Password, Newsletter & preferences). WC's
 * default form bundles personal-info + password into one form; we keep
 * that single-form shape (otherwise WC's nonce + save handler breaks)
 * but visually split it with internal headings.
 *
 * @package luwipress-emerald
 */

defined( 'ABSPATH' ) || exit;

/**
 * @var WP_User $user
 */
$user = wp_get_current_user();
?>

<h2><?php esc_html_e( 'Account details', 'luwipress-emerald' ); ?></h2>
<p class="lwp-acct-sub"><?php esc_html_e( 'Update your name, email and password. Marketing preferences are stored separately under the newsletter section below.', 'luwipress-emerald' ); ?></p>

<form class="woocommerce-EditAccountForm edit-account lwp-edit-account-form" action="" method="post" <?php do_action( 'woocommerce_edit_account_form_tag' ); ?>>

	<?php do_action( 'woocommerce_edit_account_form_start' ); ?>

	<section class="lwp-acct-card">
		<h3><?php esc_html_e( 'Personal info', 'luwipress-emerald' ); ?></h3>
		<div class="lwp-form-grid">
			<label class="lwp-form-label">
				<?php esc_html_e( 'First name', 'luwipress-emerald' ); ?> <span class="required">*</span>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_first_name" id="account_first_name" autocomplete="given-name" value="<?php echo esc_attr( $user->first_name ); ?>" />
			</label>
			<label class="lwp-form-label">
				<?php esc_html_e( 'Last name', 'luwipress-emerald' ); ?> <span class="required">*</span>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_last_name" id="account_last_name" autocomplete="family-name" value="<?php echo esc_attr( $user->last_name ); ?>" />
			</label>
			<label class="lwp-form-label lwp-form-full">
				<?php esc_html_e( 'Display name', 'luwipress-emerald' ); ?> <span class="required">*</span>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_display_name" id="account_display_name" value="<?php echo esc_attr( $user->display_name ); ?>" />
				<small><?php esc_html_e( 'How your name appears on the account section and reviews.', 'luwipress-emerald' ); ?></small>
			</label>
			<label class="lwp-form-label lwp-form-full">
				<?php esc_html_e( 'Email address', 'luwipress-emerald' ); ?> <span class="required">*</span>
				<input type="email" class="woocommerce-Input woocommerce-Input--email input-text" name="account_email" id="account_email" autocomplete="email" value="<?php echo esc_attr( $user->user_email ); ?>" />
			</label>
		</div>
	</section>

	<?php do_action( 'woocommerce_edit_account_form' ); ?>

	<section class="lwp-acct-card">
		<h3><?php esc_html_e( 'Password change', 'luwipress-emerald' ); ?></h3>
		<p class="lwp-acct-sub" style="font-size:13px;margin-bottom:18px;"><?php esc_html_e( 'Leave blank to keep the current password.', 'luwipress-emerald' ); ?></p>
		<div class="lwp-form-grid">
			<label class="lwp-form-label lwp-form-full">
				<?php esc_html_e( 'Current password', 'luwipress-emerald' ); ?>
				<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_current" id="password_current" autocomplete="current-password" />
			</label>
			<label class="lwp-form-label">
				<?php esc_html_e( 'New password', 'luwipress-emerald' ); ?>
				<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_1" id="password_1" autocomplete="new-password" />
			</label>
			<label class="lwp-form-label">
				<?php esc_html_e( 'Confirm new password', 'luwipress-emerald' ); ?>
				<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_2" id="password_2" autocomplete="new-password" />
			</label>
		</div>
	</section>

	<div class="lwp-form-actions">
		<?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>
		<button type="submit" class="lwp-btn-primary woocommerce-Button button" name="save_account_details" value="<?php esc_attr_e( 'Save changes', 'luwipress-emerald' ); ?>"><?php esc_html_e( 'Save changes →', 'luwipress-emerald' ); ?></button>
		<input type="hidden" name="action" value="save_account_details" />
	</div>

	<?php do_action( 'woocommerce_edit_account_form_end' ); ?>
</form>
