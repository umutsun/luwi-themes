<?php
/**
 * The Template for displaying a single product. Wraps WC's content-single
 * template in our Gold PDP chrome (breadcrumb + 2-col grid) but defers
 * gallery + summary + tabs rendering to the native WC actions so plugin
 * compatibility (variations, reviews, related, hooks) stays intact.
 *
 * @package luwipress-amber
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );
?>

<main class="lwp-pdp" id="primary">
	<div class="lwp-pdp-container">

		<?php while ( have_posts() ) : the_post();

			$primary_cat = null;
			if ( function_exists( 'wc_get_product' ) ) {
				$product = wc_get_product( get_the_ID() );
				if ( $product ) {
					$cat_ids = $product->get_category_ids();
					if ( ! empty( $cat_ids ) ) {
						$primary_cat = get_term( (int) $cat_ids[0], 'product_cat' );
					}
				}
			}
			?>

			<nav class="lwp-crumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'luwipress-amber' ); ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'luwipress-amber' ); ?></a>
				<?php
				$shop_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : 0;
				if ( $shop_id > 0 ) : ?>
					<span class="sep">›</span>
					<a href="<?php echo esc_url( get_permalink( $shop_id ) ); ?>"><?php esc_html_e( 'Shop', 'luwipress-amber' ); ?></a>
				<?php endif; ?>
				<?php if ( $primary_cat instanceof WP_Term ) : ?>
					<span class="sep">›</span>
					<a href="<?php echo esc_url( get_term_link( $primary_cat ) ); ?>"><?php echo esc_html( $primary_cat->name ); ?></a>
				<?php endif; ?>
				<span class="sep">›</span>
				<span class="current"><?php the_title(); ?></span>
			</nav>

			<?php
			// Eyebrow / Save badge / Stock pill all wired via hooks in
			// inc/wc-pdp-hooks.php — they render INSIDE the WC summary
			// block (above title) so the buy column matches reference
			// without us reaching into WC's template hierarchy.
			?>

			<div class="lwp-pdp-grid">
				<?php
				// Use WC's standard product wrapper but inject our grid as the layout.
				wc_get_template_part( 'content', 'single-product' );
				?>
			</div>

			<?php
			// Perks rail under the buy column — speaks the brand promise
			// (hand-tuned, free shipping, 14-day returns, warranty). The
			// list is filterable so operator can swap items per language
			// or per product category.
			// Travel / experiences brand promise. Generic enough for any
			// Amber store; operators override per language or per product
			// category via the `luwipress_amber_pdp_perks` filter.
			$perks = apply_filters( 'luwipress_amber_pdp_perks', [
				[ 'icon' => '✓', 'text' => __( 'Instant confirmation — book with confidence', 'luwipress-amber' ) ],
				[ 'icon' => '★', 'text' => __( 'Hand-picked experiences, vetted by our team', 'luwipress-amber' ) ],
				[ 'icon' => '↺', 'text' => __( 'Free cancellation up to 24 hours before', 'luwipress-amber' ) ],
				[ 'icon' => '☎', 'text' => __( '24/7 concierge support before & during your trip', 'luwipress-amber' ) ],
			], isset( $product ) ? $product : null );

			if ( ! empty( $perks ) ) : ?>
				<ul class="lwp-pdp-perks">
					<?php foreach ( $perks as $perk ) : ?>
						<li><span class="lwp-pdp-perks__icon" aria-hidden="true"><?php echo esc_html( $perk['icon'] ); ?></span><?php echo esc_html( $perk['text'] ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php // PDP sticky add-to-cart bar removed 2026-05-12 — operator
				// feedback: bar was covering functional content (chat launcher,
				// cart drawer trigger, payment buttons) and wasn't useful since
				// the main `.single_add_to_cart_button` stays in viewport on
				// reasonably-sized screens. CSS rules in widgets.css + the
				// `setupStickyPdp` / `initFloatBarFooterHandoff` JS handlers are
				// now no-ops (no `.lwp-pdp-sticky` element to observe). ?>

		<?php endwhile; ?>

	</div>
</main>

<?php
get_footer( 'shop' );
