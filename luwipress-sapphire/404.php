<?php
/**
 * 404 — Sapphire editorial "lost address" page.
 *
 * @package luwipress-sapphire
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();
?>
<main>
	<section class="phead" style="border-bottom:0">
		<div class="wrap" style="text-align:center;padding-top:clamp(60px,10vh,140px);padding-bottom:clamp(60px,10vh,140px)">
			<span class="smallcaps" style="color:var(--accent)"><?php esc_html_e( 'Error 404', 'luwipress-sapphire' ); ?></span>
			<h1 class="display-xl" style="margin:18px 0 0;font-size:clamp(56px,10vw,140px)">404</h1>
			<p class="lede" style="max-width:42ch;margin:22px auto 0"><?php esc_html_e( 'This address is off the market. The page you were looking for has moved, sold, or never existed.', 'luwipress-sapphire' ); ?></p>
			<div class="hero-cta" style="justify-content:center;margin-top:42px">
				<a class="btn btn-gold" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back home', 'luwipress-sapphire' ); ?> <span class="arr"><?php echo sapphire_icon( 'arrow', 16 ); // phpcs:ignore ?></span></a>
				<a class="btn btn-ghost" href="<?php echo esc_url( sapphire_page_url( 'gallery' ) ); ?>"><?php esc_html_e( 'Browse the collection', 'luwipress-sapphire' ); ?></a>
			</div>
			<form class="search-box" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" role="search" style="margin:46px auto 0">
				<span class="ic"><?php echo sapphire_icon( 'search', 22 ); // phpcs:ignore ?></span>
				<input type="search" name="s" placeholder="<?php esc_attr_e( 'Search the site…', 'luwipress-sapphire' ); ?>" aria-label="<?php esc_attr_e( 'Search', 'luwipress-sapphire' ); ?>">
				<button class="btn btn-gold" type="submit"><?php esc_html_e( 'Search', 'luwipress-sapphire' ); ?></button>
			</form>
		</div>
	</section>
</main>
<?php
get_footer();
