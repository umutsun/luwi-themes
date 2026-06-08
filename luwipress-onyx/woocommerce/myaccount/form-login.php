<?php
/**
 * Login + Register form on /my-account/ when no user is signed in.
 *
 * Two-card auth-grid (matches reference Tapadum-Login.html). The
 * register card hides itself when WC's "Allow customers to create an
 * account on the My Account page" setting is disabled.
 *
 * @package luwipress-onyx
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_customer_login_form' );

$registration_enabled = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );
?>

<div class="lwp-auth-grid">

	<section class="lwp-auth-card">
		<h2><?php esc_html_e( 'Welcome back', 'luwipress-onyx' ); ?></h2>
		<p class="sub"><?php esc_html_e( 'Sign in to view orders, saved instruments and addresses.', 'luwipress-onyx' ); ?></p>

		<form class="woocommerce-form woocommerce-form-login login" method="post">
			<?php do_action( 'woocommerce_login_form_start' ); ?>

			<label class="lwp-form-label">
				<?php esc_html_e( 'Email or username', 'luwipress-onyx' ); ?>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; // phpcs:ignore ?>" required />
			</label>

			<label class="lwp-form-label">
				<?php esc_html_e( 'Password', 'luwipress-onyx' ); ?>
				<input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" required />
			</label>

			<?php do_action( 'woocommerce_login_form' ); ?>

			<div class="lwp-form-actions">
				<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
				<button type="submit" class="lwp-btn-primary woocommerce-button button woocommerce-form-login__submit" name="login" value="<?php esc_attr_e( 'Log in', 'luwipress-onyx' ); ?>"><?php esc_html_e( 'Log in', 'luwipress-onyx' ); ?></button>
				<label class="lwp-remember">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" />
					<?php esc_html_e( 'Remember me', 'luwipress-onyx' ); ?>
				</label>
			</div>

			<p class="alt"><a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Forgot your password?', 'luwipress-onyx' ); ?></a></p>

			<?php do_action( 'woocommerce_login_form_end' ); ?>
		</form>
	</section>

	<?php if ( $registration_enabled ) : ?>
		<section class="lwp-auth-card">
			<h2><?php
				/* translators: %s: site name */
				printf( esc_html__( 'New to %s', 'luwipress-onyx' ), esc_html( get_bloginfo( 'name' ) ) );
			?></h2>
			<p class="sub"><?php esc_html_e( 'Create an account to track orders, save instruments to your wishlist, and check out faster next time.', 'luwipress-onyx' ); ?></p>

			<form method="post" class="woocommerce-form woocommerce-form-register register">
				<?php do_action( 'woocommerce_register_form_start' ); ?>

				<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
					<label class="lwp-form-label">
						<?php esc_html_e( 'Username', 'luwipress-onyx' ); ?>
						<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; // phpcs:ignore ?>" />
					</label>
				<?php endif; ?>

				<label class="lwp-form-label">
					<?php esc_html_e( 'Email', 'luwipress-onyx' ); ?>
					<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; // phpcs:ignore ?>" required />
				</label>

				<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
					<label class="lwp-form-label">
						<?php esc_html_e( 'Password', 'luwipress-onyx' ); ?>
						<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" required />
					</label>
				<?php endif; ?>

				<?php do_action( 'woocommerce_register_form' ); ?>

				<div class="lwp-form-actions">
					<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
					<button type="submit" class="lwp-btn-primary woocommerce-Button woocommerce-button button woocommerce-form-register__submit" name="register" value="<?php esc_attr_e( 'Create account', 'luwipress-onyx' ); ?>"><?php esc_html_e( 'Create account', 'luwipress-onyx' ); ?></button>
				</div>

				<?php do_action( 'woocommerce_register_form_end' ); ?>
			</form>
		</section>
	<?php else :
		// Registration disabled — fill the right column with brand content
		// (atelier story brief + perks list + featured product image) so the
		// auth grid doesn't render as one form floating in empty space. The
		// CSS auto-hides this card when the customizer flag is off.
		$brand_blurb = get_theme_mod(
			'luwipress_onyx_login_blurb',
			__( 'Hand-crafted instruments shipped from our atelier in Brisighella. Sign in to keep track of orders, save the pieces you\'re watching, and check out faster.', 'luwipress-onyx' )
		);
		?>
		<section class="lwp-auth-card lwp-auth-card--brand" aria-label="<?php esc_attr_e( 'About', 'luwipress-onyx' ); ?>">
			<span class="lwp-eyebrow"><?php esc_html_e( '— Why sign in', 'luwipress-onyx' ); ?></span>
			<h2 class="lwp-auth-card__brand-title"><?php
				/* translators: %s: site name */
				printf( esc_html__( '%s · members.', 'luwipress-onyx' ), esc_html( get_bloginfo( 'name' ) ) );
			?></h2>
			<p class="lwp-auth-card__brand-blurb"><?php echo esc_html( $brand_blurb ); ?></p>
			<ul class="lwp-auth-card__perks">
				<li><?php esc_html_e( '✓  Track every order from atelier to door', 'luwipress-onyx' ); ?></li>
				<li><?php esc_html_e( '✓  Save instruments to your wishlist for later', 'luwipress-onyx' ); ?></li>
				<li><?php esc_html_e( '✓  One-click checkout with stored addresses', 'luwipress-onyx' ); ?></li>
				<li><?php esc_html_e( '✓  Early access to new arrivals', 'luwipress-onyx' ); ?></li>
			</ul>
			<?php
			// Featured product image — operator pin one product as
			// `_lwp_onyx_login_feature` (post meta) or fall back to the
			// most recent in-stock featured product. Empty graceful: card
			// just shows perks without imagery.
			$feat_id = (int) get_option( 'luwipress_onyx_login_feature_id', 0 );
			if ( ! $feat_id && function_exists( 'wc_get_featured_product_ids' ) ) {
				$ids = wc_get_featured_product_ids();
				if ( ! empty( $ids ) ) $feat_id = (int) $ids[0];
			}
			if ( $feat_id ) {
				$feat = get_post( $feat_id );
				if ( $feat && $feat->post_status === 'publish' ) {
					$thumb = get_the_post_thumbnail( $feat_id, 'medium', [ 'class' => 'lwp-auth-card__feat-img' ] );
					if ( $thumb ) : ?>
						<a href="<?php echo esc_url( get_permalink( $feat_id ) ); ?>" class="lwp-auth-card__feat">
							<?php echo $thumb; // phpcs:ignore ?>
							<span class="lwp-auth-card__feat-meta">
								<small><?php esc_html_e( 'Featured', 'luwipress-onyx' ); ?></small>
								<strong><?php echo esc_html( get_the_title( $feat_id ) ); ?></strong>
							</span>
						</a>
					<?php endif;
				}
			}
			?>
		</section>
	<?php endif; ?>

</div>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
