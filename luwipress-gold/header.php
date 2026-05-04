<?php
/**
 * Minimum header for the theme. The actual sticky header / megabar / mega menu
 * lives inside the Elementor Theme Builder template (`01-header.json`).
 *
 * When Elementor's Theme Builder header is active, it takes over `wp_body_open()`.
 * This file just opens <html> + <head> and a body element so WP can hand off
 * cleanly. If no Elementor header is set, a small fallback wordmark + nav is
 * rendered so the site stays usable during installation.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="profile" href="https://gmpg.org/xfn/11" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php
/**
 * If Elementor Theme Builder hasn't supplied a header, render a tiny fallback
 * so the site is browseable before the kit is imported.
 */
$elementor_header_active =
	did_action( 'elementor/loaded' ) &&
	function_exists( 'elementor_theme_do_location' ) &&
	elementor_theme_do_location( 'header' );

if ( ! $elementor_header_active ) :
?>
<header class="lwp-fallback-header" role="banner">
	<div class="lwp-fallback-header__inner">
		<a class="lwp-fallback-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php
			if ( has_custom_logo() ) {
				the_custom_logo();
			} else {
				echo esc_html( get_bloginfo( 'name' ) );
			}
			?>
		</a>
		<?php if ( has_nav_menu( 'primary' ) ) : ?>
			<nav class="lwp-fallback-header__nav" aria-label="<?php esc_attr_e( 'Primary', 'luwipress-gold' ); ?>">
				<?php
				wp_nav_menu( [
					'theme_location' => 'primary',
					'container'      => false,
					'depth'          => 1,
					'items_wrap'     => '<ul>%3$s</ul>',
				] );
				?>
			</nav>
		<?php endif; ?>
	</div>
</header>
<?php endif; ?>
