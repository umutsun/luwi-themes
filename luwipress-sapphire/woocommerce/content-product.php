<?php
/**
 * The template used to display product cards within product loops
 * (shop, category archives, related products, "[products]" shortcode).
 *
 * Mirrors the Claude Design Tapadum Gold reference card:
 *   eyebrow (category name) → Playfair name → italic master/maker →
 *   gold serif price ladder. Sale percentage badge top-left. AJAX
 *   add-to-cart button rendered AFTER the main card anchor so the
 *   button isn't nested inside another interactive element (invalid
 *   HTML + a11y trap). Operator feedback 2026-05-12: storefront cards
 *   need a direct ATC, picking variations on the PDP is a separate flow.
 *
 * Falls back to the WC default behaviour for shape (li.product) so
 * grid + sale class hooks + 3rd-party loop filters keep working.
 *
 * @see woocommerce/templates/content-product.php (parent template)
 *
 * @package luwipress-sapphire
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}

$product_id = $product->get_id();

// Primary category — first assigned category, used as eyebrow.
$primary_cat_name = '';
$cat_ids          = $product->get_category_ids();
if ( ! empty( $cat_ids ) ) {
	$cat = get_term( (int) $cat_ids[0], 'product_cat' );
	if ( $cat && ! is_wp_error( $cat ) ) {
		$primary_cat_name = $cat->name;
	}
}

// Maker / brand — pulled from `pa_master` or `pa_maker` attribute when
// present, otherwise falls back to `_lwp_sapphire_maker` post-meta. Stores
// without this taxonomy/meta simply hide the line.
$maker = '';
if ( taxonomy_exists( 'pa_master' ) ) {
	$terms = wp_get_post_terms( $product_id, 'pa_master', [ 'fields' => 'names' ] );
	if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
		$maker = $terms[0];
	}
}
if ( ! $maker && taxonomy_exists( 'pa_maker' ) ) {
	$terms = wp_get_post_terms( $product_id, 'pa_maker', [ 'fields' => 'names' ] );
	if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
		$maker = $terms[0];
	}
}
if ( ! $maker ) {
	$maker = (string) get_post_meta( $product_id, '_lwp_sapphire_maker', true );
}

// Sale percentage for the badge — calculated from regular vs sale price
// when both are set; falls back to a generic "SALE" label otherwise.
$sale_pct = '';
if ( $product->is_on_sale() ) {
	$reg = (float) $product->get_regular_price();
	$sal = (float) $product->get_sale_price();
	if ( $reg > 0 && $sal > 0 && $sal < $reg ) {
		$sale_pct = round( ( ( $reg - $sal ) / $reg ) * 100 );
	}
}
?>

<li <?php wc_product_class( 'lwp-pcard', $product ); ?>>
	<a href="<?php the_permalink(); ?>" class="lwp-pcard-link" aria-label="<?php echo esc_attr( wp_strip_all_tags( $product->get_name() ) ); ?>">

		<div class="lwp-pcard-img">
			<?php if ( $product->is_on_sale() ) : ?>
				<span class="lwp-pcard-badge"><?php echo $sale_pct ? '−' . esc_html( $sale_pct ) . '%' : esc_html__( 'Sale', 'luwipress-sapphire' ); ?></span>
			<?php endif; ?>
			<?php
			// Featured image (or WC placeholder when missing).
			if ( has_post_thumbnail( $product_id ) ) {
				echo get_the_post_thumbnail( $product_id, 'woocommerce_thumbnail', [ 'class' => 'lwp-pcard-thumb' ] );
			} else {
				echo wc_placeholder_img( 'woocommerce_thumbnail', [ 'class' => 'lwp-pcard-thumb' ] );
			}
			?>
		</div>

		<div class="lwp-pcard-meta">
			<?php if ( $primary_cat_name ) : ?>
				<span class="lwp-pcard-cat"><?php echo esc_html( $primary_cat_name ); ?></span>
			<?php endif; ?>
			<h4 class="lwp-pcard-title"><?php echo esc_html( $product->get_name() ); ?></h4>
			<?php if ( $maker ) : ?>
				<span class="lwp-pcard-maker"><?php
					/* translators: %s: master / maker name */
					printf( esc_html__( 'by %s', 'luwipress-sapphire' ), esc_html( $maker ) );
				?></span>
			<?php endif; ?>
			<div class="lwp-pcard-price">
				<?php echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — WC sanitises ?>
			</div>
		</div>

	</a>

	<?php
	/**
	 * Add-to-cart action — rendered OUTSIDE the main `<a>` so we don't
	 * nest interactive elements. WC's default loop ATC outputs an
	 * `<a class="button add_to_cart_button ajax_add_to_cart">` which
	 * is fine as a sibling anchor; AJAX handling is auto-bound by
	 * `wc-add-to-cart.js` (still enqueued — we only dequeue gallery
	 * scripts on PDP). Hidden for out-of-stock / unpurchasable items
	 * via WC's own guards.
	 */
	woocommerce_template_loop_add_to_cart();
	?>
</li>
