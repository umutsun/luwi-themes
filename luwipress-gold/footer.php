<?php
/**
 * Minimum footer. Real layout comes from the Elementor footer template
 * (`02-footer.json`). Falls back to a single line + sitemap link if Elementor
 * Theme Builder hasn't supplied one yet.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$elementor_footer_active =
	did_action( 'elementor/loaded' ) &&
	function_exists( 'elementor_theme_do_location' ) &&
	elementor_theme_do_location( 'footer' );

if ( ! $elementor_footer_active ) :
?>
<footer class="lwp-fallback-footer" role="contentinfo">
	<div class="lwp-fallback-footer__inner">
		<span>&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></span>
		<?php if ( has_nav_menu( 'footer' ) ) : ?>
			<nav class="lwp-fallback-footer__nav" aria-label="<?php esc_attr_e( 'Footer', 'luwipress-gold' ); ?>">
				<?php
				wp_nav_menu( [
					'theme_location' => 'footer',
					'container'      => false,
					'depth'          => 1,
					'items_wrap'     => '<ul>%3$s</ul>',
				] );
				?>
			</nav>
		<?php endif; ?>
	</div>
</footer>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
