<?php
/**
 * My Account page wrapper. Drops WC's default `<div class="woocommerce">`
 * box layout in favour of the Gold account chrome — sticky 260px nav
 * sidebar + main content card. The actual endpoint content is still
 * rendered by WC's native templates (dashboard/orders/addresses/etc.).
 *
 * @package luwipress-onyx
 */

defined( 'ABSPATH' ) || exit;

$user = wp_get_current_user();
?>

<main class="lwp-page lwp-acct-page" id="primary">
	<div class="lwp-page-container">

		<nav class="lwp-crumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'luwipress-onyx' ); ?>">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'luwipress-onyx' ); ?></a>
			<span class="sep">›</span>
			<span class="current"><?php esc_html_e( 'My Account', 'luwipress-onyx' ); ?></span>
		</nav>

		<h1 class="lwp-page-title"><?php esc_html_e( 'My account', 'luwipress-onyx' ); ?>
			<?php if ( $user && $user->ID ) : ?><em>· <?php echo esc_html( $user->display_name ); ?></em><?php endif; ?>
		</h1>

		<div class="woocommerce">
			<?php
			/**
			 * My Account navigation.
			 *
			 * @hooked woocommerce_account_navigation - 10
			 */
			?>
			<div class="lwp-acct-grid">
				<aside class="lwp-acct-side">
					<?php if ( $user && $user->ID ) : ?>
						<div class="lwp-acct-who">
							<span class="nm"><?php echo esc_html( $user->display_name ); ?></span>
							<span class="em"><?php echo esc_html( $user->user_email ); ?></span>
						</div>
					<?php endif; ?>
					<?php do_action( 'woocommerce_account_navigation' ); ?>
				</aside>

				<div class="lwp-acct-main woocommerce-MyAccount-content">
					<?php
					/**
					 * My Account content.
					 *
					 * @hooked woocommerce_account_content - 10
					 */
					do_action( 'woocommerce_account_content' );
					?>
				</div>
			</div>
		</div>

	</div>
</main>
