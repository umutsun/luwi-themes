<?php
/**
 * LuwiPress Onyx — theme footer.
 *
 * Yields to an Elementor Pro / theme-builder Footer template when one is
 * built; otherwise renders the Onyx 4-column footer + bottom bar and the
 * floating chat launcher. The launcher defers to LuwiPress core: when core
 * is active it owns the bottom-right Customer Chat slot, so the theme's own
 * launcher hides to avoid a double launcher (operator-overridable).
 *
 * @package luwipress-onyx
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$onyx_footer_active =
	did_action( 'elementor/loaded' ) &&
	function_exists( 'elementor_theme_do_location' ) &&
	elementor_theme_do_location( 'footer' );

if ( ! $onyx_footer_active ) :

	$f_name    = get_bloginfo( 'name' );
	$f_logo    = onyx_logo_url();
	$f_blurb   = trim( (string) get_theme_mod( 'luwipress_onyx_footer_blurb', '' ) );
	if ( '' === $f_blurb ) {
		$f_blurb = __( 'A residence, not a listing. Premium properties across the United Arab Emirates, handled quietly.', 'luwipress-onyx' );
	}
	$f_address = trim( (string) get_theme_mod( 'luwipress_onyx_topbar_location', 'Blue Bay Tower, Office 608 — Business Bay, Dubai' ) );
	$f_email   = trim( (string) get_theme_mod( 'luwipress_onyx_topbar_email', 'sales@arshahomes.com' ) );
	$f_phone   = trim( (string) get_theme_mod( 'luwipress_onyx_topbar_phone', '056 776 1946' ) );
	$f_legal   = trim( (string) get_theme_mod( 'luwipress_onyx_footer_legal', 'ArshaHomes Real Estate L.L.C' ) );

	$f_address_lines = array_filter( array_map( 'trim', preg_split( '/[—,]/', $f_address ) ) );

	$f_socials = array(
		'Instagram' => get_theme_mod( 'luwipress_onyx_social_instagram', '' ),
		'LinkedIn'  => get_theme_mod( 'luwipress_onyx_social_linkedin', '' ),
		'Facebook'  => get_theme_mod( 'luwipress_onyx_social_facebook', '' ),
		'WhatsApp'  => get_theme_mod( 'luwipress_onyx_social_whatsapp', '' ),
	);
	?>

	<footer class="foot" id="footer" role="contentinfo">
		<div class="wrap">
			<div class="foot-grid">
				<div class="f-brand">
					<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" data-small="true"><img src="<?php echo esc_url( $f_logo ); ?>" alt="<?php echo esc_attr( $f_name ); ?>"></a>
					<p><?php echo esc_html( $f_blurb ); ?></p>
				</div>
				<div>
					<h4><?php esc_html_e( 'Address', 'luwipress-onyx' ); ?></h4>
					<ul>
						<?php foreach ( $f_address_lines as $line ) : ?>
							<li><?php echo esc_html( $line ); ?></li>
						<?php endforeach; ?>
						<?php if ( empty( $f_address_lines ) ) : ?>
							<li><?php esc_html_e( 'United Arab Emirates', 'luwipress-onyx' ); ?></li>
						<?php endif; ?>
					</ul>
				</div>
				<div>
					<h4><?php esc_html_e( 'Explore', 'luwipress-onyx' ); ?></h4>
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
							<li><a href="<?php echo esc_url( onyx_page_url( 'gallery' ) ); ?>"><?php esc_html_e( 'Gallery', 'luwipress-onyx' ); ?></a></li>
							<li><a href="<?php echo esc_url( onyx_page_url( 'about' ) ); ?>"><?php esc_html_e( 'About', 'luwipress-onyx' ); ?></a></li>
							<li><a href="<?php echo esc_url( onyx_page_url( 'journal' ) ); ?>"><?php esc_html_e( 'Journal', 'luwipress-onyx' ); ?></a></li>
							<li><a href="<?php echo esc_url( onyx_page_url( 'contact' ) ); ?>"><?php esc_html_e( 'Contact', 'luwipress-onyx' ); ?></a></li>
						</ul>
					<?php endif; ?>
				</div>
				<div class="f-news">
					<h4><?php esc_html_e( 'The Quiet List', 'luwipress-onyx' ); ?></h4>
					<p><?php esc_html_e( 'Off-market residences, before they are listed. No noise.', 'luwipress-onyx' ); ?></p>
					<form class="f-input" data-demo method="post" action="">
						<input type="email" name="email" placeholder="<?php esc_attr_e( 'Your email', 'luwipress-onyx' ); ?>" aria-label="<?php esc_attr_e( 'Email', 'luwipress-onyx' ); ?>">
						<button type="submit" aria-label="<?php esc_attr_e( 'Subscribe', 'luwipress-onyx' ); ?>"><?php echo onyx_icon( 'arrow', 16 ); // phpcs:ignore ?></button>
						<span data-form-note style="display:none;align-self:center;padding:0 14px;color:var(--gold);font-size:12px"><?php esc_html_e( 'Thank you.', 'luwipress-onyx' ); ?></span>
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
				<span class="smallcaps">&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php echo esc_html( $f_legal ); ?> · <span style="color:var(--gold)">LuwiPress Onyx</span> <?php esc_html_e( 'Theme', 'luwipress-onyx' ); ?></span>
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
	$onyx_wa = trim( (string) get_theme_mod( 'luwipress_onyx_social_whatsapp', '' ) );
	if ( '' === $onyx_wa && $f_phone !== '' ) {
		$onyx_wa = 'https://wa.me/' . preg_replace( '/[^0-9]/', '', $f_phone );
	}
	$onyx_show_fab = ( '' !== $onyx_wa ) && apply_filters( 'luwipress_onyx_show_chat_fab', ! class_exists( 'LuwiPress' ) );
	if ( $onyx_show_fab ) : ?>
		<a class="chat-fab" href="<?php echo esc_url( $onyx_wa ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Chat with us', 'luwipress-onyx' ); ?>">
			<span class="chat-label"><?php esc_html_e( 'Chat with us', 'luwipress-onyx' ); ?></span>
			<?php echo onyx_icon( 'chat', 28 ); // phpcs:ignore ?>
		</a>
	<?php endif; ?>

<?php endif; // ! $onyx_footer_active ?>

<?php wp_footer(); ?>
</body>
</html>
