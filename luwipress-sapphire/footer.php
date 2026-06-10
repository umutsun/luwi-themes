<?php
/**
 * LuwiPress Sapphire — theme footer.
 *
 * Yields to an Elementor Pro / theme-builder Footer template when one is
 * built; otherwise renders the Sapphire 4-column footer + bottom bar and the
 * floating chat launcher. The launcher defers to LuwiPress core: when core
 * is active it owns the bottom-right Customer Chat slot, so the theme's own
 * launcher hides to avoid a double launcher (operator-overridable).
 *
 * @package luwipress-sapphire
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$sapphire_footer_active =
	did_action( 'elementor/loaded' ) &&
	function_exists( 'elementor_theme_do_location' ) &&
	elementor_theme_do_location( 'footer' );

if ( ! $sapphire_footer_active ) :

	$f_name    = get_bloginfo( 'name' );
	$f_logo    = sapphire_logo_url();
	$f_blurb   = trim( (string) get_theme_mod( 'luwipress_sapphire_footer_blurb', '' ) );
	if ( '' === $f_blurb ) {
		$f_blurb = __( 'One platform for product, docs, billing and analytics. Built for teams that ship — from a weekend project to thousands of seats.', 'luwipress-sapphire' );
	}
	$f_email   = trim( (string) get_theme_mod( 'luwipress_sapphire_topbar_email', 'hello@sapphire.dev' ) );
	$f_phone   = trim( (string) get_theme_mod( 'luwipress_sapphire_topbar_phone', '' ) );
	$f_legal   = trim( (string) get_theme_mod( 'luwipress_sapphire_footer_legal', 'Sapphire, Inc.' ) );

	$f_socials = array(
		'Instagram' => get_theme_mod( 'luwipress_sapphire_social_instagram', '' ),
		'LinkedIn'  => get_theme_mod( 'luwipress_sapphire_social_linkedin', '' ),
		'Facebook'  => get_theme_mod( 'luwipress_sapphire_social_facebook', '' ),
		'WhatsApp'  => get_theme_mod( 'luwipress_sapphire_social_whatsapp', '' ),
	);
	?>

	<footer class="foot" id="footer" role="contentinfo">
		<div class="wrap">
			<div class="foot-grid">
				<div class="f-brand">
					<a class="logo<?php echo $f_logo ? '' : ' logo--text'; ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>" data-small="true"><?php if ( $f_logo ) : ?><img src="<?php echo esc_url( $f_logo ); ?>" alt="<?php echo esc_attr( $f_name ); ?>"><?php else : ?><span class="logo-text"><?php echo esc_html( $f_name ? $f_name : 'Sapphire' ); ?></span><?php endif; ?></a>
					<p><?php echo esc_html( $f_blurb ); ?></p>
				</div>
				<div>
					<h4><?php esc_html_e( 'Product', 'luwipress-sapphire' ); ?></h4>
					<ul>
						<li><a href="<?php echo esc_url( home_url( '/#features' ) ); ?>"><?php esc_html_e( 'Features', 'luwipress-sapphire' ); ?></a></li>
						<li><a href="<?php echo esc_url( sapphire_page_url( 'pricing' ) ); ?>"><?php esc_html_e( 'Pricing', 'luwipress-sapphire' ); ?></a></li>
						<li><a href="<?php echo esc_url( home_url( '/#integrations' ) ); ?>"><?php esc_html_e( 'Integrations', 'luwipress-sapphire' ); ?></a></li>
						<li><a href="<?php echo esc_url( home_url( '/#changelog' ) ); ?>"><?php esc_html_e( 'Changelog', 'luwipress-sapphire' ); ?></a></li>
					</ul>
				</div>
				<div>
					<h4><?php esc_html_e( 'Company', 'luwipress-sapphire' ); ?></h4>
					<?php if ( has_nav_menu( 'footer' ) ) : ?>
						<ul><?php
							wp_nav_menu( array(
								'theme_location' => 'footer',
								'container'      => false,
								'items_wrap'     => '%3$s',
								'depth'          => 1,
								'fallback_cb'    => false,
							) );
						?></ul>
					<?php else : ?>
						<ul>
							<li><a href="<?php echo esc_url( sapphire_page_url( 'about' ) ); ?>"><?php esc_html_e( 'About', 'luwipress-sapphire' ); ?></a></li>
							<li><a href="<?php echo esc_url( sapphire_page_url( 'journal' ) ); ?>"><?php esc_html_e( 'Blog', 'luwipress-sapphire' ); ?></a></li>
							<li><a href="<?php echo esc_url( sapphire_page_url( 'contact' ) ); ?>"><?php esc_html_e( 'Contact', 'luwipress-sapphire' ); ?></a></li>
							<li><a href="<?php echo esc_url( sapphire_page_url( 'search' ) ); ?>"><?php esc_html_e( 'Search', 'luwipress-sapphire' ); ?></a></li>
						</ul>
					<?php endif; ?>
				</div>
				<div class="f-news">
					<h4><?php esc_html_e( 'Ship notes', 'luwipress-sapphire' ); ?></h4>
					<p><?php esc_html_e( 'Product updates and release notes — no spam, unsubscribe anytime.', 'luwipress-sapphire' ); ?></p>
					<form class="f-input" data-demo method="post" action="">
						<input type="email" name="email" placeholder="<?php esc_attr_e( 'Your work email', 'luwipress-sapphire' ); ?>" aria-label="<?php esc_attr_e( 'Email', 'luwipress-sapphire' ); ?>">
						<button type="submit" aria-label="<?php esc_attr_e( 'Subscribe', 'luwipress-sapphire' ); ?>"><?php echo sapphire_icon( 'arrow', 16 ); // phpcs:ignore ?></button>
						<span data-form-note style="display:none;align-self:center;padding:0 14px;color:var(--accent);font-size:12px"><?php esc_html_e( 'Thank you.', 'luwipress-sapphire' ); ?></span>
					</form>
					<?php if ( $f_email !== '' || $f_phone !== '' ) : ?>
						<div style="margin-top:22px;display:grid;gap:8px;color:var(--muted);font-size:14px">
							<?php if ( $f_phone !== '' ) : ?><a href="<?php echo esc_url( 'tel:' . preg_replace( '/\s+/', '', $f_phone ) ); ?>"><?php echo esc_html( $f_phone ); ?></a><?php endif; ?>
							<?php if ( $f_email !== '' ) : ?><a href="<?php echo esc_url( 'mailto:' . $f_email ); ?>"><?php echo esc_html( $f_email ); ?></a><?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
			<div class="foot-bar">
				<span class="smallcaps">&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php echo esc_html( $f_legal ); ?> · <span style="color:var(--accent)">LuwiPress Sapphire</span> <?php esc_html_e( 'Theme', 'luwipress-sapphire' ); ?></span>
				<div class="f-social">
					<?php foreach ( $f_socials as $label => $url ) :
						if ( $url === '' ) { continue; } ?>
						<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $label ); ?></a>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</footer>

	<?php
	// Floating chat launcher — only when a WhatsApp/phone target is set, and
	// deferred to LuwiPress (default off when core is active so it doesn't
	// double up with the Customer Chat launcher).
	$sapphire_wa = trim( (string) get_theme_mod( 'luwipress_sapphire_social_whatsapp', '' ) );
	if ( '' === $sapphire_wa && $f_phone !== '' ) {
		$sapphire_wa = 'https://wa.me/' . preg_replace( '/[^0-9]/', '', $f_phone );
	}
	$sapphire_show_fab = ( '' !== $sapphire_wa ) && apply_filters( 'luwipress_sapphire_show_chat_fab', ! class_exists( 'LuwiPress' ) );
	if ( $sapphire_show_fab ) : ?>
		<a class="chat-fab" href="<?php echo esc_url( $sapphire_wa ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Chat with us', 'luwipress-sapphire' ); ?>">
			<span class="chat-label"><?php esc_html_e( 'Chat with us', 'luwipress-sapphire' ); ?></span>
			<?php echo sapphire_icon( 'chat', 28 ); // phpcs:ignore ?>
		</a>
	<?php endif; ?>

<?php endif; // ! $sapphire_footer_active ?>

<?php wp_footer(); ?>
</body>
</html>
