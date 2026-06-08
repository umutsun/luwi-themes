<?php
/**
 * LuwiPress Emerald — footer.php
 *
 * Closes the <main>, renders the 4-column footer + bottom strip,
 * mobile nav drawer, optional WooCommerce cart drawer, and wp_footer().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lwp_emerald_brand_desc = (string) get_theme_mod(
	'luwipress_emerald_footer_desc',
	get_bloginfo( 'description', 'display' )
);

$lwp_emerald_news_on = (bool) get_theme_mod( 'luwipress_emerald_newsletter_on', true );
$lwp_emerald_socials = array(
	'linkedin'  => (string) get_theme_mod( 'luwipress_emerald_social_linkedin', '' ),
	'twitter'   => (string) get_theme_mod( 'luwipress_emerald_social_twitter', '' ),
	'instagram' => (string) get_theme_mod( 'luwipress_emerald_social_instagram', '' ),
	'github'    => (string) get_theme_mod( 'luwipress_emerald_social_github', '' ),
	'youtube'   => (string) get_theme_mod( 'luwipress_emerald_social_youtube', '' ),
	'facebook'  => (string) get_theme_mod( 'luwipress_emerald_social_facebook', '' ),
	'rss'       => get_bloginfo( 'rss2_url' ),
);
$lwp_emerald_socials = array_filter( $lwp_emerald_socials );

$lwp_emerald_social_icons = array(
	'linkedin'  => '<svg class="emerald-i" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 10v7M8 7v.01M12 17v-4a2 2 0 0 1 4 0v4M12 13v-3"/></svg>',
	'twitter'   => '<svg class="emerald-i" viewBox="0 0 24 24" aria-hidden="true"><path d="m4 4 16 16M20 4 4 20"/></svg>',
	'instagram' => '<svg class="emerald-i" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1"/></svg>',
	'github'    => '<svg class="emerald-i" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 19c-4 1-4-2-6-2m12 4v-3a3 3 0 0 0-1-2c3 0 6-2 6-5a4 4 0 0 0-1-3 4 4 0 0 0 0-3s-1 0-3 1a10 10 0 0 0-6 0c-2-1-3-1-3-1a4 4 0 0 0 0 3 4 4 0 0 0-1 3c0 3 3 5 6 5a3 3 0 0 0-1 2v3"/></svg>',
	'youtube'   => '<svg class="emerald-i" viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="6" width="20" height="12" rx="3"/><path d="m10 9 5 3-5 3z"/></svg>',
	'facebook'  => '<svg class="emerald-i" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 22v-9h3l1-4h-4V7c0-1 .5-2 2-2h2V2h-3a4 4 0 0 0-4 4v3H8v4h3v9z"/></svg>',
	'rss'       => '<svg class="emerald-i" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 11a9 9 0 0 1 9 9M4 4a16 16 0 0 1 16 16"/><circle cx="5" cy="19" r="1.5"/></svg>',
);
?>

</main><!-- .emerald-main -->

<footer class="emerald-footer" data-screen-label="Footer">
	<div class="emerald-footer-inner">
		<div class="emerald-footer-cols">
			<div class="emerald-footer-col emerald-footer-brand">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="emerald-logo" rel="home">
					<?php
					$site_name = get_bloginfo( 'name' );
					$mark      = $site_name ? mb_strtoupper( mb_substr( $site_name, 0, 1 ) ) : 'L';
					?>
					<span class="emerald-logo-mark"><?php echo esc_html( $mark ); ?></span>
					<span class="emerald-logo-word"><?php echo esc_html( $site_name ?: 'LuwiPress' ); ?></span>
				</a>
				<?php if ( $lwp_emerald_brand_desc ) : ?>
					<p class="emerald-footer-desc"><?php echo esc_html( $lwp_emerald_brand_desc ); ?></p>
				<?php endif; ?>
				<?php if ( $lwp_emerald_socials ) : ?>
					<div class="emerald-footer-social">
						<?php foreach ( $lwp_emerald_socials as $key => $url ) :
							if ( ! isset( $lwp_emerald_social_icons[ $key ] ) ) {
								continue;
							}
							?>
							<a href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( ucfirst( $key ) ); ?>"<?php echo ( 'rss' === $key ) ? '' : ' target="_blank" rel="noopener noreferrer"'; ?>><?php echo $lwp_emerald_social_icons[ $key ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="emerald-footer-col">
				<h4><?php esc_html_e( 'Solutions', 'luwipress-emerald' ); ?></h4>
				<?php
				if ( has_nav_menu( 'footer-solutions' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'footer-solutions',
							'container'      => false,
							'menu_class'     => 'emerald-footer-links',
							'depth'          => 1,
							'fallback_cb'    => false,
						)
					);
				} else {
					echo '<ul class="emerald-footer-links">';
					echo '<li><a href="' . esc_url( home_url( '/solutions/' ) ) . '">' . esc_html__( 'Strategy Advisory', 'luwipress-emerald' ) . '</a></li>';
					echo '<li><a href="' . esc_url( home_url( '/solutions/' ) ) . '">' . esc_html__( 'Operations Design', 'luwipress-emerald' ) . '</a></li>';
					echo '<li><a href="' . esc_url( home_url( '/solutions/' ) ) . '">' . esc_html__( 'Digital Transformation', 'luwipress-emerald' ) . '</a></li>';
					echo '</ul>';
				}
				?>
			</div>

			<div class="emerald-footer-col">
				<h4><?php esc_html_e( 'Resources', 'luwipress-emerald' ); ?></h4>
				<?php
				if ( has_nav_menu( 'footer-resources' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'footer-resources',
							'container'      => false,
							'menu_class'     => 'emerald-footer-links',
							'depth'          => 1,
							'fallback_cb'    => false,
						)
					);
				} else {
					echo '<ul class="emerald-footer-links">';
					echo '<li><a href="' . esc_url( home_url( '/blog/' ) ) . '">' . esc_html__( 'Insights', 'luwipress-emerald' ) . '</a></li>';
					echo '<li><a href="' . esc_url( home_url( '/about/' ) ) . '">' . esc_html__( 'About', 'luwipress-emerald' ) . '</a></li>';
					echo '<li><a href="' . esc_url( home_url( '/contact/' ) ) . '">' . esc_html__( 'Contact', 'luwipress-emerald' ) . '</a></li>';
					echo '</ul>';
				}
				?>
			</div>

			<?php if ( $lwp_emerald_news_on ) : ?>
				<div class="emerald-footer-col">
					<h4><?php esc_html_e( 'Stay in the loop', 'luwipress-emerald' ); ?></h4>
					<p class="emerald-footer-desc" style="margin-bottom:var(--sp-3);"><?php esc_html_e( 'One letter a month. Field notes from live engagements, never marketing.', 'luwipress-emerald' ); ?></p>
					<?php
					/**
					 * Newsletter form. Filterable so a CRM plugin (FluentCRM,
					 * Mailchimp for WC) can swap in its native form markup
					 * without touching the theme.
					 */
					$lwp_emerald_news_html = apply_filters( 'luwipress_emerald_footer_newsletter', '' );
					if ( $lwp_emerald_news_html ) {
						echo $lwp_emerald_news_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					} else {
						?>
						<form class="emerald-news-form" action="#" method="post" onsubmit="event.preventDefault();this.querySelector('input').value='';this.querySelector('input').placeholder='<?php esc_attr_e( 'Thanks — see you next month', 'luwipress-emerald' ); ?>';">
							<input type="email" name="email" placeholder="you@company.com" aria-label="<?php esc_attr_e( 'Email address', 'luwipress-emerald' ); ?>" required>
							<button class="emerald-btn emerald-btn--primary" type="submit"><?php esc_html_e( 'Subscribe', 'luwipress-emerald' ); ?></button>
						</form>
						<?php
					}
					?>
				</div>
			<?php endif; ?>
		</div>

		<div class="emerald-footer-bottom">
			<span class="emerald-footer-stamp">
				&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?>
				<?php if ( defined( 'LUWIPRESS_EMERALD_VERSION' ) ) : ?>
					&middot; v<?php echo esc_html( LUWIPRESS_EMERALD_VERSION ); ?>
				<?php endif; ?>
			</span>
			<?php
			if ( has_nav_menu( 'footer-legal' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'footer-legal',
						'container'      => false,
						'menu_class'     => 'emerald-footer-bottom-links',
						'depth'          => 1,
						'fallback_cb'    => false,
					)
				);
			} else {
				echo '<ul class="emerald-footer-bottom-links">';
				echo '<li><a href="' . esc_url( get_privacy_policy_url() ?: '#' ) . '">' . esc_html__( 'Privacy', 'luwipress-emerald' ) . '</a></li>';
				echo '<li><a href="#">' . esc_html__( 'Terms', 'luwipress-emerald' ) . '</a></li>';
				echo '<li><a href="#">' . esc_html__( 'Cookies', 'luwipress-emerald' ) . '</a></li>';
				echo '</ul>';
			}
			?>
		</div>
	</div>
</footer>

<?php
/**
 * Mobile nav drawer — slides from the left at <=900px. The mobile
 * hamburger in header.php toggles it via JS.
 */
?>
<aside class="emerald-navdrawer" id="navDrawer" aria-hidden="true" aria-label="<?php esc_attr_e( 'Main menu', 'luwipress-emerald' ); ?>">
	<div class="emerald-navdrawer-scrim" data-nav-close></div>
	<div class="emerald-navdrawer-panel" role="dialog" aria-modal="true">
		<div class="emerald-navdrawer-head">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="emerald-logo" rel="home">
				<?php
				$site_name = get_bloginfo( 'name' );
				$mark      = $site_name ? mb_strtoupper( mb_substr( $site_name, 0, 1 ) ) : 'L';
				?>
				<span class="emerald-logo-mark"><?php echo esc_html( $mark ); ?></span>
				<span class="emerald-logo-word"><?php echo esc_html( $site_name ?: 'LuwiPress' ); ?></span>
			</a>
			<button class="emerald-icon-btn" data-nav-close aria-label="<?php esc_attr_e( 'Close menu', 'luwipress-emerald' ); ?>">
				<svg class="emerald-i" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
			</button>
		</div>
		<div class="emerald-navdrawer-body">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'emerald-navdrawer-menu',
						'depth'          => 2,
						'fallback_cb'    => false,
					)
				);
			} else {
				echo '<ul class="emerald-navdrawer-menu">';
				echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'luwipress-emerald' ) . '</a></li>';
				echo '<li><a href="' . esc_url( home_url( '/blog/' ) ) . '">' . esc_html__( 'Insights', 'luwipress-emerald' ) . '</a></li>';
				echo '<li><a href="' . esc_url( home_url( '/about/' ) ) . '">' . esc_html__( 'About', 'luwipress-emerald' ) . '</a></li>';
				echo '</ul>';
			}
			?>
		</div>
		<?php $cta_label = (string) get_theme_mod( 'luwipress_emerald_header_cta_label', __( 'Book a call', 'luwipress-emerald' ) );
		$cta_url = (string) get_theme_mod( 'luwipress_emerald_header_cta_url', '' );
		if ( $cta_label && $cta_url ) : ?>
			<div class="emerald-navdrawer-foot">
				<a href="<?php echo esc_url( $cta_url ); ?>" class="emerald-btn emerald-btn--primary emerald-btn--block"><?php echo esc_html( $cta_label ); ?></a>
			</div>
		<?php endif; ?>
	</div>
</aside>

<?php if ( function_exists( 'WC' ) ) : ?>
	<?php
	/**
	 * Cart drawer — rendered when WooCommerce is active. Pure shell;
	 * cart line items are filled by `woocommerce_mini_cart` on AJAX
	 * refresh. We render a static read of the cart server-side on
	 * first load so the drawer is meaningful even before WC's JS
	 * boots.
	 */
	$cart_count = WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
	$cart_total = WC()->cart ? wp_strip_all_tags( WC()->cart->get_cart_subtotal() ) : '';
	?>
	<aside class="emerald-drawer" id="cartDrawer" aria-hidden="true" aria-label="<?php esc_attr_e( 'Shopping cart', 'luwipress-emerald' ); ?>">
		<div class="emerald-drawer-scrim" data-cart-close></div>
		<div class="emerald-drawer-panel" role="dialog" aria-modal="true">
			<div class="emerald-drawer-head">
				<h3><?php esc_html_e( 'Cart', 'luwipress-emerald' ); ?> <span style="color:var(--muted);font-weight:400;">(<span data-cart-count><?php echo esc_html( (string) $cart_count ); ?></span>)</span></h3>
				<button class="emerald-icon-btn" data-cart-close aria-label="<?php esc_attr_e( 'Close cart', 'luwipress-emerald' ); ?>">
					<svg class="emerald-i" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
				</button>
			</div>
			<div class="emerald-drawer-body widget_shopping_cart_content">
				<?php
				if ( $cart_count > 0 ) {
					woocommerce_mini_cart();
				} else {
					echo '<p class="emerald-drawer-empty">' . esc_html__( 'Your cart is empty.', 'luwipress-emerald' ) . '</p>';
				}
				?>
			</div>
			<div class="emerald-drawer-foot">
				<?php if ( $cart_count > 0 ) : ?>
					<div class="emerald-drawer-subtotal">
						<span><?php esc_html_e( 'Subtotal', 'luwipress-emerald' ); ?></span>
						<span class="emerald-drawer-subtotal-val" data-cart-subtotal><?php echo esc_html( $cart_total ); ?></span>
					</div>
				<?php endif; ?>
				<div class="emerald-drawer-actions">
					<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="emerald-btn emerald-btn--secondary emerald-btn--block"><?php esc_html_e( 'View cart', 'luwipress-emerald' ); ?></a>
					<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="emerald-btn emerald-btn--primary emerald-btn--block"><?php esc_html_e( 'Checkout', 'luwipress-emerald' ); ?></a>
				</div>
				<p class="emerald-drawer-microcopy"><?php esc_html_e( 'Secure handoff · Fixed scope · 30-day satisfaction', 'luwipress-emerald' ); ?></p>
			</div>
		</div>
	</aside>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
