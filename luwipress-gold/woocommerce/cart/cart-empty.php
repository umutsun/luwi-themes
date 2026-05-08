<?php
/**
 * Empty-cart layout — Tapadum Gold.
 *
 * Renders when WC reports the cart has zero items. Without this file
 * (or the parent cart.php's `is_empty()` branch), `cart.php` would
 * loop over an empty array and emit a blank middle between header and
 * footer — visitors see what looks like a broken page.
 *
 * Editorial empty-state: large headline + supportive blurb + two CTAs
 * (Shop / Continue with last viewed) + a quiet "what's in your cart"
 * help line. Auto-pulls 3 best-selling products as a discovery rail
 * when WC product data exists, so the page never feels like a dead end.
 *
 * @package luwipress-gold
 */

defined( 'ABSPATH' ) || exit;

$shop_url      = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$account_url   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : '';
$has_account   = is_user_logged_in() && $account_url;

// Best-sellers rail: 3 most-bought products. Skipped if no products
// have ever been sold (e.g. fresh install).
$best_sellers = [];
if ( function_exists( 'wc_get_product' ) ) {
	$q = new WP_Query( [
		'post_type'      => 'product',
		'posts_per_page' => 3,
		'post_status'    => 'publish',
		'meta_key'       => 'total_sales',
		'orderby'        => [ 'meta_value_num' => 'DESC', 'date' => 'DESC' ],
		'no_found_rows'  => true,
	] );
	if ( $q->have_posts() ) {
		while ( $q->have_posts() ) {
			$q->the_post();
			$pid = get_the_ID();
			$p   = wc_get_product( $pid );
			if ( $p && $p->is_visible() ) {
				$best_sellers[] = [
					'id'    => $pid,
					'name'  => $p->get_name(),
					'price' => $p->get_price_html(),
					'thumb' => get_the_post_thumbnail_url( $pid, 'woocommerce_thumbnail' ) ?: '',
					'url'   => get_permalink( $pid ),
				];
			}
		}
		wp_reset_postdata();
	}
}
?>

<section class="lwp-cart-empty" role="region" aria-label="<?php esc_attr_e( 'Empty cart', 'luwipress-gold' ); ?>">
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
				'luwipress_gold_cart_empty_title',
				__( 'Your cart is empty', 'luwipress-gold' )
			) );
		?></h1>

		<p class="lwp-cart-empty__blurb"><?php
			echo esc_html( apply_filters(
				'luwipress_gold_cart_empty_blurb',
				__( 'Hand-picked instruments are waiting in the atelier. Browse the collection or pick up where you left off.', 'luwipress-gold' )
			) );
		?></p>

		<div class="lwp-cart-empty__actions">
			<a href="<?php echo esc_url( $shop_url ); ?>" class="lwp-cart-empty__cta lwp-cart-empty__cta--primary"><?php esc_html_e( 'Browse the collection', 'luwipress-gold' ); ?></a>
			<?php if ( $has_account ) : ?>
				<a href="<?php echo esc_url( $account_url ); ?>" class="lwp-cart-empty__cta lwp-cart-empty__cta--ghost"><?php esc_html_e( 'My orders', 'luwipress-gold' ); ?></a>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $best_sellers ) ) : ?>
			<div class="lwp-cart-empty__rail">
				<h2 class="lwp-cart-empty__rail-title"><?php esc_html_e( 'Most loved this season', 'luwipress-gold' ); ?></h2>
				<ul class="lwp-cart-empty__rail-list">
					<?php foreach ( $best_sellers as $item ) : ?>
						<li>
							<a href="<?php echo esc_url( $item['url'] ); ?>" class="lwp-cart-empty__rail-card">
								<?php if ( $item['thumb'] ) : ?>
									<span class="lwp-cart-empty__rail-thumb" style="background-image:url(<?php echo esc_url( $item['thumb'] ); ?>)" aria-hidden="true"></span>
								<?php endif; ?>
								<span class="lwp-cart-empty__rail-name"><?php echo esc_html( $item['name'] ); ?></span>
								<span class="lwp-cart-empty__rail-price"><?php echo wp_kses_post( $item['price'] ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

	</div>
</section>

<?php
/* Hook so the operator / sister plugins can append below the empty
 * state (e.g. a "promotion code" widget) without forking this template. */
do_action( 'luwipress_gold_after_cart_empty' );
