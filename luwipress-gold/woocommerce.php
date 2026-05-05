<?php
/**
 * Master WooCommerce template — wraps every WC archive / single page
 * with the LuwiPress Gold container so cards aren't squashed into a
 * 30 % column.
 *
 * The default WC fallback sits the `woocommerce_content()` output inside
 * whatever the active theme's main column is. On a Hello-Elementor-DB-clone
 * site that came through as a narrow `.entry-content` (~30 % wide), which
 * made every product card a single visible thumbnail. This wrapper
 * forces the 1372 px content-width and 4-col grid that the rest of the
 * theme tokens promise.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();
?>
<main class="lwp-shop-main" id="primary">
	<div class="lwp-shop-container">
		<?php woocommerce_content(); ?>
	</div>
</main>
<?php
get_footer();
