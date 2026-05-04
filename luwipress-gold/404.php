<?php
/**
 * 404 fallback. Replaced by `10-404.json` when imported as the Theme Builder
 * "Single 404" template.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();
?>
<main class="lwp-fallback-main lwp-fallback-404" id="primary">
	<h1><?php esc_html_e( 'This page wandered off.', 'luwipress-gold' ); ?></h1>
	<p><?php esc_html_e( 'The link you followed may be broken, or the page may have been moved.', 'luwipress-gold' ); ?></p>
	<p>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="lwp-fallback-link">
			<?php esc_html_e( 'Back to home →', 'luwipress-gold' ); ?>
		</a>
	</p>
</main>
<?php
get_footer();
