<?php
/**
 * Theme footer. Same three-tier rendering as header.php:
 *   1. Elementor Pro Theme Builder Footer template active → yield.
 *   2. ElementsKit Lite Footer Builder active → yield.
 *   3. Otherwise: full Gold footer inline (4-column, deep-black band,
 *      brand blurb + customer-care links + explore links + atelier address,
 *      bottom strip with copyright + byline).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$elementor_footer_active =
	did_action( 'elementor/loaded' ) &&
	function_exists( 'elementor_theme_do_location' ) &&
	elementor_theme_do_location( 'footer' );

if ( ! $elementor_footer_active ) :
	$site_name = get_bloginfo( 'name' );
	$blurb     = get_theme_mod( 'luwipress_gold_footer_blurb',
		__( 'Hand-crafted instruments from the masters of Anatolia, Persia and the Mediterranean.', 'luwipress-gold' )
	);
	$legal     = get_theme_mod( 'luwipress_gold_footer_legal', '' );
	// Byline default empty since 1.7.5 — was redundant with the brand
	// blurb above and crowded the bottom strip. Operator can re-enable
	// any text via Customizer → LuwiPress Gold → Footer → Footer byline.
	$byline    = get_theme_mod( 'luwipress_gold_footer_byline', '' );

	// Social-icon rendering moved to inc/footer-enhancements.php where the
	// full SVG icon set + Customizer wiring live. Footer.php just calls
	// the helper. The helper renders nothing when no social URL is set.

	// Customer care + explore menus from registered nav menus, or auto-build.
	$has_care    = has_nav_menu( 'footer' );
	$has_explore = has_nav_menu( 'footer-explore' );
?>

<footer class="lwp-site-footer" role="contentinfo">
	<div class="lwp-site-footer-inner">

		<div class="lwp-site-footer-col lwp-site-footer-brand">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="lwp-site-footer-logo" rel="home">
				<?php
				// Prefer the uploaded site logo (Customizer → Site Identity).
				// Wrap in a span so CSS can apply the white-on-dark filter
				// (footer band is deep-black; coloured logos invert via
				// `filter: brightness(0) invert(1)`). Fallback to site
				// name text only when no logo has been uploaded.
				$logo_id = (int) get_theme_mod( 'custom_logo', 0 );
				if ( $logo_id ) {
					echo wp_get_attachment_image(
						$logo_id,
						'medium',
						false,
						array(
							'class'   => 'lwp-site-footer-logo__img',
							'alt'     => esc_attr( $site_name ),
							'loading' => 'lazy',
						)
					);
				} else {
					echo '<span class="lwp-site-footer-logo__text">' . esc_html( $site_name ) . '</span>';
				}
				?>
			</a>
			<?php if ( $blurb ) : ?>
				<p class="lwp-site-footer-blurb"><?php echo esc_html( $blurb ); ?></p>
			<?php endif; ?>
			<?php
			// Social icons under the logo + tagline (operator UI judgment
			// call 2026-05-12 — "tapadum logosunun altında tagline altına
			// sen nasıl uygun görürsen"). Top-down reading: brand → claim
			// → community. Renders nothing when no platform URLs are set
			// in Customizer, so the column stays clean for fresh installs.
			if ( get_theme_mod( 'luwipress_gold_footer_show_socials', true ) &&
				 function_exists( 'luwipress_gold_footer_render_social_icons' ) ) {
				luwipress_gold_footer_render_social_icons();
			}
			?>
		</div>

		<div class="lwp-site-footer-col">
			<h4><?php esc_html_e( 'Customer care', 'luwipress-gold' ); ?></h4>
			<?php if ( $has_care ) {
				wp_nav_menu( [
					'theme_location' => 'footer',
					'container'      => false,
					'depth'          => 1,
					'items_wrap'     => '<ul>%3$s</ul>',
				] );
			} else {
				echo '<ul>';
				echo '<li><a href="' . esc_url( home_url( '/contact/' ) ) . '">' . esc_html__( 'Contact us', 'luwipress-gold' ) . '</a></li>';
				if ( class_exists( 'WooCommerce' ) ) {
					$account = wc_get_page_permalink( 'myaccount' );
					$cart    = wc_get_cart_url();
					if ( $account ) echo '<li><a href="' . esc_url( $account ) . '">' . esc_html__( 'My account', 'luwipress-gold' ) . '</a></li>';
					if ( $cart )    echo '<li><a href="' . esc_url( $cart ) . '">' . esc_html__( 'Cart', 'luwipress-gold' ) . '</a></li>';
				}
				echo '<li><a href="' . esc_url( get_privacy_policy_url() ) . '">' . esc_html__( 'Privacy policy', 'luwipress-gold' ) . '</a></li>';
				echo '</ul>';
			} ?>
		</div>

		<div class="lwp-site-footer-col">
			<h4><?php esc_html_e( 'Explore', 'luwipress-gold' ); ?></h4>
			<?php if ( $has_explore ) {
				wp_nav_menu( [
					'theme_location' => 'footer-explore',
					'container'      => false,
					'depth'          => 1,
					'items_wrap'     => '<ul>%3$s</ul>',
				] );
			} else {
				echo '<ul>';
				if ( class_exists( 'WooCommerce' ) && wc_get_page_permalink( 'shop' ) ) {
					echo '<li><a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">' . esc_html__( 'Shop', 'luwipress-gold' ) . '</a></li>';
				}
				$blog_id = (int) get_option( 'page_for_posts' );
				if ( $blog_id ) {
					echo '<li><a href="' . esc_url( get_permalink( $blog_id ) ) . '">' . esc_html__( 'Journal', 'luwipress-gold' ) . '</a></li>';
				}
				echo '<li><a href="' . esc_url( home_url( '/about/' ) ) . '">' . esc_html__( 'About', 'luwipress-gold' ) . '</a></li>';
				echo '</ul>';
			} ?>
		</div>

		<?php
		// Shop column — auto-generated WC product category list. Renders
		// nothing when WC is inactive or operator opted out via
		// Customizer, keeping the layout unchanged for content sites.
		if ( function_exists( 'luwipress_gold_footer_render_shop_categories' ) ) {
			luwipress_gold_footer_render_shop_categories();
		}
		?>

		<?php /* Atelier column removed in 1.7.10 — used to render only the
		 * topbar email as a single mailto link, which felt redundant given
		 * email lives in the chat widget + drawer + (footer-newsletter on
		 * larger plans). Footer is now 4-col: logo+blurb / Customer Care /
		 * Explore / Shop. Operator: "bu gereksiz info kaldıralım." */ ?>

	</div>

	<?php
	if ( function_exists( 'luwipress_gold_footer_render_newsletter' ) ) {
		luwipress_gold_footer_render_newsletter();
	}
	// Trust strip ("Secure checkout · Worldwide shipping · 30-day returns")
	// removed 2026-05-12 — operator feedback: the three short claims read as
	// generic reassurance copy and the dark band visually fragmented the
	// footer between the column grid and the copyright bottom strip.
	// `luwipress_gold_footer_render_trust_strip()` is still defined for
	// operators who want it back; uncomment to restore.
	?>

	<div class="lwp-site-footer-bottom">
		<span class="lwp-site-footer-bottom__copy">
			&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php echo esc_html( $site_name ); ?>
			<?php if ( $legal ) echo ' · ' . esc_html( $legal ); ?>
		</span>
		<?php
		// Optional byline (default empty since 1.7.5 — was redundant with
		// the brand blurb in the column above).
		if ( $byline !== '' ) : ?>
			<span class="lwp-site-footer-bottom__byline"><?php echo esc_html( $byline ); ?></span>
		<?php endif; ?>
		<?php
		// Social icons moved to brand column (under logo) since 1.7.10
		// per operator preference. The bottom-strip render is removed
		// so icons appear in one place only.
		// Payment row default OFF since 1.7.5 — the gateway labels are
		// long ("MyBank · a través de PayPal"), language-dependent, and
		// already shown at checkout where they actually matter. Operator
		// can flip back on in Customizer → LuwiPress Gold → Footer.
		if ( function_exists( 'luwipress_gold_footer_render_payment_row' ) ) {
			luwipress_gold_footer_render_payment_row();
		}
		?>
	</div>
</footer>

<?php endif; // !elementor footer ?>

<?php wp_footer(); ?>
</body>
</html>
