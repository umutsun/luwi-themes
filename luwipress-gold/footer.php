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
	$byline    = get_theme_mod( 'luwipress_gold_footer_byline',
		__( 'Hand-tuned in our atelier · Shipped worldwide', 'luwipress-gold' )
	);

	$social = [
		'instagram' => [ 'icon' => '◎', 'label' => 'Instagram' ],
		'youtube'   => [ 'icon' => '▶', 'label' => 'YouTube' ],
		'facebook'  => [ 'icon' => 'f', 'label' => 'Facebook' ],
		'whatsapp'  => [ 'icon' => 'w', 'label' => 'WhatsApp' ],
	];

	// Customer care + explore menus from registered nav menus, or auto-build.
	$has_care    = has_nav_menu( 'footer' );
	$has_explore = has_nav_menu( 'footer-explore' );
?>

<footer class="lwp-site-footer" role="contentinfo">
	<div class="lwp-site-footer-inner">

		<div class="lwp-site-footer-col lwp-site-footer-brand">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="lwp-site-footer-logo" rel="home">
				<?php echo esc_html( $site_name ); ?>
			</a>
			<?php if ( $blurb ) : ?>
				<p class="lwp-site-footer-blurb"><?php echo esc_html( $blurb ); ?></p>
			<?php endif; ?>
			<div class="lwp-site-footer-social">
				<?php foreach ( $social as $key => $cfg ) :
					$url = get_theme_mod( 'luwipress_gold_social_' . $key, '' );
					if ( ! $url ) continue;
				?>
					<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $cfg['label'] ); ?>">
						<?php echo esc_html( $cfg['icon'] ); ?>
					</a>
				<?php endforeach; ?>
			</div>
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

		<div class="lwp-site-footer-col">
			<h4><?php esc_html_e( 'Atelier', 'luwipress-gold' ); ?></h4>
			<?php
			$loc   = get_theme_mod( 'luwipress_gold_topbar_location', '' );
			$phone = get_theme_mod( 'luwipress_gold_topbar_phone', '' );
			$email = get_theme_mod( 'luwipress_gold_topbar_email', get_option( 'admin_email' ) );
			?>
			<ul>
				<?php if ( $loc !== '' ) : ?><li><?php echo esc_html( $loc ); ?></li><?php endif; ?>
				<?php if ( $phone !== '' ) : ?><li><a href="<?php echo esc_url( 'tel:' . preg_replace( '/\s+/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></li><?php endif; ?>
				<?php if ( $email !== '' ) : ?><li><a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a></li><?php endif; ?>
			</ul>
		</div>

	</div>

	<div class="lwp-site-footer-bottom">
		<span>
			&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php echo esc_html( $site_name ); ?>
			<?php if ( $legal ) echo ' · ' . esc_html( $legal ); ?>
		</span>
		<?php if ( $byline ) : ?>
			<span><?php echo esc_html( $byline ); ?></span>
		<?php endif; ?>
	</div>
</footer>

<?php endif; // !elementor footer ?>

<?php wp_footer(); ?>
</body>
</html>
