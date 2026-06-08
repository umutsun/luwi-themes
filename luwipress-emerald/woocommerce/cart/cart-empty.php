<?php
/**
 * Empty-cart layout — Tapadum Gold.
 *
 * Renders when WC reports the cart has zero items. Without this file
 * (or the parent cart.php's `is_empty()` branch), `cart.php` would
 * loop over an empty array and emit a blank middle between header and
 * footer — visitors see what looks like a broken page.
 *
 * 1.7.6: simplified to bare-bones markup. The previous version ran an
 * inline `WP_Query` for best-sellers; under Elementor Pro's `woocommerce-cart`
 * widget render context that nested query was fataling silently and the
 * widget's output buffer captured the resulting blank string — leaving
 * the cart page rendering an empty `.elementor-widget-container` in the
 * DOM. Discovery rail moved out to a separate `luwipress_emerald_after_cart_empty`
 * hook so it can be re-added by a sister plugin or later iteration without
 * gambling on Elementor render context.
 *
 * @package luwipress-emerald
 */

defined( 'ABSPATH' ) || exit;

$shop_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : '';
$has_account = is_user_logged_in() && $account_url;
?>

<section class="lwp-cart-empty" role="region" aria-label="<?php esc_attr_e( 'Empty cart', 'luwipress-emerald' ); ?>">
	<div class="lwp-cart-empty__inner">

		<div class="lwp-cart-empty__icon" aria-hidden="true">
			<svg viewBox="0 0 64 64" width="56" height="56" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
				<path d="M10 14h6l4 30h32l4-22H22"/>
				<circle cx="26" cy="52" r="3"/>
				<circle cx="46" cy="52" r="3"/>
			</svg>
		</div>

		<h1 class="lwp-cart-empty__title"><?php
			echo wp_kses_post( apply_filters(
				'luwipress_emerald_cart_empty_title',
				__( 'Your cart is empty', 'luwipress-emerald' )
			) );
		?></h1>

		<p class="lwp-cart-empty__blurb"><?php
			echo esc_html( apply_filters(
				'luwipress_emerald_cart_empty_blurb',
				__( 'Hand-picked instruments are waiting in the atelier. Browse the collection or pick up where you left off.', 'luwipress-emerald' )
			) );
		?></p>

		<div class="lwp-cart-empty__actions">
			<a href="<?php echo esc_url( $shop_url ); ?>" class="lwp-cart-empty__cta lwp-cart-empty__cta--primary"><?php esc_html_e( 'Browse the collection', 'luwipress-emerald' ); ?></a>
			<?php if ( $has_account ) : ?>
				<a href="<?php echo esc_url( $account_url ); ?>" class="lwp-cart-empty__cta lwp-cart-empty__cta--ghost"><?php esc_html_e( 'My orders', 'luwipress-emerald' ); ?></a>
			<?php endif; ?>
		</div>

	</div>
</section>

<?php
/* Hook so the operator / sister plugins can append below the empty
 * state (e.g. a "promotion code" widget, best-sellers rail, or recently-
 * viewed items) without forking this template. Render handler is responsible
 * for any nested WP_Query — keep this template's scope narrow to dodge
 * Elementor Pro widget render-context conflicts.
 */
do_action( 'luwipress_emerald_after_cart_empty' );
