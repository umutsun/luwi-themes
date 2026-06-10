<?php
/**
 * LuwiPress Amber — theme footer (Fly By Deniz "Amber" chrome).
 *
 * Yields to an Elementor Pro / ElementsKit Footer template when one is
 * built; otherwise renders the Amber 4-column footer + bottom bar and the
 * floating WhatsApp launcher. Columns are wired to nav menus (Quick Links =
 * "footer" location, Explore = "footer-activities") with graceful
 * fallbacks so a fresh install isn't blank.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$elementor_footer_active =
	did_action( 'elementor/loaded' ) &&
	function_exists( 'elementor_theme_do_location' ) &&
	elementor_theme_do_location( 'footer' );

if ( ! $elementor_footer_active ) :

	$site_name = get_bloginfo( 'name' );
	$blurb     = get_theme_mod( 'luwipress_amber_footer_blurb', '' );
	if ( $blurb === '' ) { $blurb = get_bloginfo( 'description' ); }
	$legal   = trim( (string) get_theme_mod( 'luwipress_amber_footer_legal', '' ) );

	$f_phone    = trim( (string) get_theme_mod( 'luwipress_amber_topbar_phone', '' ) );
	$f_email    = trim( (string) get_theme_mod( 'luwipress_amber_topbar_email', get_option( 'admin_email' ) ) );
	$f_location = trim( (string) get_theme_mod( 'luwipress_amber_topbar_location', '' ) );

	$f_arrow = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';

	$f_socials = [
		'facebook'  => [ get_theme_mod( 'luwipress_amber_social_facebook', '' ),  '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M14 9h3V6h-3c-1.7 0-3 1.3-3 3v2H9v3h2v6h3v-6h2.5l.5-3H14V9.5c0-.3.2-.5.5-.5Z"/></svg>' ],
		'twitter'   => [ get_theme_mod( 'luwipress_amber_social_twitter', '' ),   '<svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M17 3h3l-7 8 8 10h-6l-5-6-5 6H2l8-9L3 3h6l4 5 4-5Z"/></svg>' ],
		'instagram' => [ get_theme_mod( 'luwipress_amber_social_instagram', '' ), '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>' ],
		'tiktok'    => [ get_theme_mod( 'luwipress_amber_social_tiktok', '' ),    '<svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M16 3c.3 2.2 1.7 3.9 3.9 4.2v3.1c-1.4 0-2.7-.4-3.9-1.1v6.1A6.2 6.2 0 1 1 9.8 9v3.2a3 3 0 1 0 3.1 3V3H16Z"/></svg>' ],
		'youtube'   => [ get_theme_mod( 'luwipress_amber_social_youtube', '' ),   '<svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M22 8.2a3 3 0 0 0-2.1-2.1C18 5.5 12 5.5 12 5.5s-6 0-7.9.6A3 3 0 0 0 2 8.2 31 31 0 0 0 1.6 12 31 31 0 0 0 2 15.8a3 3 0 0 0 2.1 2.1c1.9.6 7.9.6 7.9.6s6 0 7.9-.6a3 3 0 0 0 2.1-2.1A31 31 0 0 0 22.4 12 31 31 0 0 0 22 8.2ZM10 15V9l5.2 3L10 15Z"/></svg>' ],
	];
?>

<footer class="footer" id="contact" role="contentinfo">
	<div class="wrap">
		<div class="foot-top">
			<div class="foot-brand">
				<span class="brand">
					<?php
					$f_logo_id  = (int) get_theme_mod( 'custom_logo' );
					$f_logo_src = $f_logo_id ? wp_get_attachment_image_url( $f_logo_id, 'full' ) : get_template_directory_uri() . '/logos/fbd-emblem.png';
					if ( $f_logo_src ) : ?>
						<img class="mark" src="<?php echo esc_url( $f_logo_src ); ?>" alt="<?php echo esc_attr( $site_name ); ?>">
					<?php endif; ?>
					<?php if ( apply_filters( 'luwipress_amber_show_wordmark', true ) ) : ?><span class="word"><?php echo esc_html( $site_name ); ?></span><?php endif; ?>
				</span>
				<?php if ( $blurb !== '' ) : ?><p><?php echo esc_html( $blurb ); ?></p><?php endif; ?>
				<div class="foot-social">
					<?php foreach ( $f_socials as $platform => $cfg ) :
						if ( $cfg[0] === '' ) continue; ?>
						<a href="<?php echo esc_url( $cfg[0] ); ?>" aria-label="<?php echo esc_attr( ucfirst( $platform ) ); ?>" target="_blank" rel="noopener"><?php echo $cfg[1]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="foot-col">
				<h4><?php esc_html_e( 'Quick Links', 'luwipress-amber' ); ?></h4>
				<?php
				if ( has_nav_menu( 'footer' ) ) {
					wp_nav_menu( [
						'theme_location' => 'footer',
						'container'      => false,
						'items_wrap'     => '%3$s',
						'depth'          => 1,
						'fallback_cb'    => false,
					] );
				} else {
					// Curated fallback — never dump Cart / Checkout / My-Account into
					// the footer (the old wp_list_pages() alphabetical dump did).
					$f_quick = array(
						home_url( '/' )         => __( 'Home', 'luwipress-amber' ),
						home_url( '/about/' )   => __( 'About Us', 'luwipress-amber' ),
						( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) ) => __( 'Tours', 'luwipress-amber' ),
						home_url( '/contact/' ) => __( 'Contact', 'luwipress-amber' ),
					);
					foreach ( $f_quick as $f_href => $f_label ) {
						printf( '<a href="%s">%s</a>', esc_url( $f_href ), esc_html( $f_label ) );
					}
				}
				?>
			</div>

			<div class="foot-col">
				<h4><?php esc_html_e( 'Explore', 'luwipress-amber' ); ?></h4>
				<?php
				if ( has_nav_menu( 'footer-activities' ) ) {
					wp_nav_menu( [
						'theme_location' => 'footer-activities',
						'container'      => false,
						'items_wrap'     => '%3$s',
						'depth'          => 1,
						'fallback_cb'    => false,
					] );
				} elseif ( taxonomy_exists( 'product_cat' ) ) {
					$cats = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0, 'number' => 6 ] );
					if ( ! is_wp_error( $cats ) ) {
						foreach ( $cats as $cat ) {
							printf( '<a href="%s">%s</a>', esc_url( get_term_link( $cat ) ), esc_html( $cat->name ) );
						}
					}
				}
				?>
			</div>

			<div class="foot-col foot-news">
				<h4><?php esc_html_e( 'Stay in the loop', 'luwipress-amber' ); ?></h4>
				<p><?php esc_html_e( 'Seasonal experiences and quiet-season deals, a few times a year.', 'luwipress-amber' ); ?></p>
				<form class="news-input" data-demo method="post" action="">
					<input type="email" name="email" placeholder="<?php esc_attr_e( 'Your email', 'luwipress-amber' ); ?>" aria-label="<?php esc_attr_e( 'Your email', 'luwipress-amber' ); ?>">
					<button type="submit" aria-label="<?php esc_attr_e( 'Subscribe', 'luwipress-amber' ); ?>"><?php echo $f_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
					<span class="form-note" data-form-note style="display:none"><?php esc_html_e( 'Thanks — you\'re on the list.', 'luwipress-amber' ); ?></span>
				</form>
				<div class="d-meta" style="color:var(--muted);font-size:14px;margin-top:22px;display:grid;gap:9px;">
					<?php if ( $f_phone !== '' ) : ?><a class="li" href="<?php echo esc_url( 'tel:' . preg_replace( '/\s+/', '', $f_phone ) ); ?>"><?php echo esc_html( $f_phone ); ?></a><?php endif; ?>
					<?php if ( $f_email !== '' ) : ?><a class="li" href="<?php echo esc_url( 'mailto:' . $f_email ); ?>"><?php echo esc_html( $f_email ); ?></a><?php endif; ?>
					<?php if ( $f_location !== '' ) : ?><span class="li"><?php echo esc_html( $f_location ); ?></span><?php endif; ?>
				</div>
			</div>
		</div>

		<div class="foot-bottom">
			<span>&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php echo esc_html( $site_name ); ?><?php echo $legal !== '' ? ' · ' . esc_html( $legal ) : ''; ?>. <?php esc_html_e( 'All Rights Reserved.', 'luwipress-amber' ); ?></span>
			<?php if ( has_nav_menu( 'footer-legal' ) ) : ?>
				<div class="links">
					<?php
					wp_nav_menu( [
						'theme_location' => 'footer-legal',
						'container'      => false,
						'items_wrap'     => '%3$s',
						'depth'          => 1,
						'fallback_cb'    => false,
					] );
					?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</footer>

<?php
// Floating WhatsApp launcher — only when a WhatsApp URL or a phone number
// is set. Deferred to LuwiPress: when core LuwiPress is active it provides the
// Customer Chat launcher in the same bottom-right slot, so the theme's own
// launcher would double up. Default off when LuwiPress is present; operators
// can force it back with the filter.
$wa_url = trim( (string) get_theme_mod( 'luwipress_amber_social_whatsapp', '' ) );
if ( $wa_url === '' && $f_phone !== '' ) {
	$wa_url = 'https://wa.me/' . preg_replace( '/[^0-9]/', '', $f_phone );
}
$wa_show = ( $wa_url !== '' ) && apply_filters( 'luwipress_amber_show_wa_launcher', ! class_exists( 'LuwiPress' ) );
if ( $wa_show ) : ?>
<div class="wa-launcher">
	<a href="<?php echo esc_url( $wa_url ); ?>" class="wa-btn" aria-label="<?php esc_attr_e( 'Chat on WhatsApp', 'luwipress-amber' ); ?>" target="_blank" rel="noopener">
		<svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.5 15.2L2 22l4.9-1.3A10 10 0 1 0 12 2Zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .1-1.7-.1-.4-.1-.9-.3-1.6-.6-2.8-1.2-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.8 0-1.3.7-2 .9-2.2.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 1.9c.1.2.1.4 0 .5l-.4.5c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.4 2.4 1.5.2.1.4.1.6-.1l.7-.9c.2-.2.4-.2.6-.1l1.8.9c.3.1.4.2.5.3.1.2.1.7-.1 1.2Z"/></svg>
	</a>
</div>
<?php endif; ?>

<?php endif; // ! $elementor_footer_active ?>

<?php wp_footer(); ?>
</body>
</html>
