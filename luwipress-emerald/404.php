<?php
/**
 * LuwiPress Emerald — 404.php
 *
 * Editorial 404 — mono ledger-style "404" code, headline + sub, two
 * CTAs back to home / contact. Centered, no chrome distractions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<section class="emerald-404 emerald-reveal">
	<p class="emerald-404-code">404</p>
	<h1 class="emerald-404-title"><?php esc_html_e( 'Off the map.', 'luwipress-emerald' ); ?></h1>
	<p class="emerald-404-sub"><?php esc_html_e( 'The page you were looking for isn\'t here. It may have moved, retired, or never existed.', 'luwipress-emerald' ); ?></p>
	<div class="emerald-404-cta">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="emerald-btn emerald-btn--primary">
			<?php esc_html_e( 'Back to home', 'luwipress-emerald' ); ?>
			<svg class="emerald-i emerald-i--sm" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
		</a>
		<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="emerald-btn emerald-btn--secondary">
			<?php esc_html_e( 'Contact us', 'luwipress-emerald' ); ?>
		</a>
	</div>
	<div style="margin-top:var(--sp-8);width:100%;max-width:520px;">
		<?php get_search_form(); ?>
	</div>
</section>

<?php
get_footer();
