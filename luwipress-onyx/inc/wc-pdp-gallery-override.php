<?php
/**
 * PDP gallery override — replaces WooCommerce's Flexslider-based gallery
 * with a vanilla-JS gallery that doesn't depend on jQuery, Flexslider,
 * Zoom, or PhotoSwipe. WC's default ships `<div ... style="opacity: 0">`
 * and relies on JS to flip it back to opacity 1 once Flexslider initialises.
 * Under aggressive LiteSpeed JS Defer/Delay (or any script-blocking
 * scenario) the gallery stays invisible and thumbnails never render.
 *
 * Our replacement renders fully server-side: first slide is `is-active`
 * via CSS, thumb strip is in the DOM at page load, click-to-swap is a
 * 40-line vanilla JS at DOMContentLoaded. Works with or without JS
 * (graceful degrade: each slide is wrapped in <a href="full-size"> so
 * a no-JS click opens the full image natively).
 *
 * @package luwipress-onyx
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Remove WC default gallery (Flexslider) and replace with vanilla gallery.
 * The vanilla render is wrapped in a `.lwp-pdp-sticky-col` div which is
 * what carries the grid-area: gallery placement (spans rows 1-2). That
 * wrapper is the sticky containing block — when row 2 (tabs) ends, the
 * wrapper ends, sticky releases naturally, related products start fresh
 * full-width on row 3.
 *
 * Priority order on `woocommerce_before_single_product_summary`:
 *   5   → open `<div class="lwp-pdp-sticky-col">`
 *   20  → render vanilla gallery (our function)
 *   25  → close `</div>`
 */
add_action( 'init', function () {
	remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
	add_action( 'woocommerce_before_single_product_summary', 'luwipress_onyx_open_sticky_col', 5 );
	add_action( 'woocommerce_before_single_product_summary', 'luwipress_onyx_render_pdp_gallery', 20 );
	add_action( 'woocommerce_before_single_product_summary', 'luwipress_onyx_close_sticky_col', 25 );
} );

function luwipress_onyx_open_sticky_col() {
	if ( ! is_product() ) { return; }
	echo '<div class="lwp-pdp-sticky-col">';
}
function luwipress_onyx_close_sticky_col() {
	if ( ! is_product() ) { return; }
	echo '</div>';
}

/**
 * Render the vanilla PDP gallery — main viewer + thumb strip + lightbox-free
 * fallback. Uses WP attachment URLs at `full` size for the main image and
 * `thumbnail` size for the strip; no inline opacity, no JS dependency.
 */
function luwipress_onyx_render_pdp_gallery() {
	if ( ! is_product() ) { return; }
	global $product;
	if ( ! ( $product instanceof WC_Product ) ) {
		$product = wc_get_product( get_the_ID() );
	}
	if ( ! $product ) { return; }

	$main_id     = (int) $product->get_image_id();
	$gallery_ids = (array) $product->get_gallery_image_ids();

	// Merge featured + gallery, drop empties, preserve order, dedupe.
	$all_ids = array_values( array_filter( array_unique( array_merge( [ $main_id ], $gallery_ids ) ) ) );

	if ( empty( $all_ids ) ) {
		// Placeholder when product has no images set.
		echo '<div class="lwp-gallery lwp-gallery--empty" data-lwp-gallery>';
		echo '<div class="lwp-gallery__main">';
		echo '<div class="lwp-gallery__slide is-active">';
		echo wc_placeholder_img( 'woocommerce_single' );
		echo '</div>';
		echo '</div>';
		echo '</div>';
		return;
	}

	$product_name = $product->get_name();
	$count        = count( $all_ids );
	?>
	<div class="lwp-gallery <?php echo $count > 1 ? 'lwp-gallery--multi' : 'lwp-gallery--single'; ?>" data-lwp-gallery tabindex="0" role="region" aria-label="<?php echo esc_attr( sprintf( __( '%s product images', 'luwipress-onyx' ), $product_name ) ); ?>">
		<div class="lwp-gallery__main">
			<?php foreach ( $all_ids as $i => $aid ) :
				$full = wp_get_attachment_image_url( (int) $aid, 'full' );
				if ( ! $full ) { continue; }
				$alt = (string) get_post_meta( (int) $aid, '_wp_attachment_image_alt', true );
				if ( $alt === '' ) { $alt = $product_name; }
				$is_first = ( $i === 0 );
				?>
				<a class="lwp-gallery__slide<?php echo $is_first ? ' is-active' : ''; ?>"
				   href="<?php echo esc_url( $full ); ?>"
				   data-idx="<?php echo (int) $i; ?>"
				   target="_blank"
				   rel="noopener">
					<img src="<?php echo esc_url( $full ); ?>"
					     alt="<?php echo esc_attr( $alt ); ?>"
					     <?php echo $is_first ? 'fetchpriority="high"' : 'loading="lazy"'; ?>
					     decoding="async">
				</a>
			<?php endforeach; ?>
		</div>

		<?php if ( $count > 1 ) : ?>
			<ul class="lwp-gallery__thumbs" role="tablist">
				<?php foreach ( $all_ids as $i => $aid ) :
					$thumb_url = wp_get_attachment_image_url( (int) $aid, 'thumbnail' );
					if ( ! $thumb_url ) {
						$thumb_url = wp_get_attachment_image_url( (int) $aid, 'full' );
					}
					if ( ! $thumb_url ) { continue; }
					$is_first = ( $i === 0 );
					?>
					<li class="lwp-gallery__thumb<?php echo $is_first ? ' is-active' : ''; ?>" role="presentation">
						<button type="button"
						        data-idx="<?php echo (int) $i; ?>"
						        role="tab"
						        aria-selected="<?php echo $is_first ? 'true' : 'false'; ?>"
						        aria-label="<?php echo esc_attr( sprintf( __( 'View image %d', 'luwipress-onyx' ), $i + 1 ) ); ?>">
							<img src="<?php echo esc_url( $thumb_url ); ?>" alt="" loading="lazy" decoding="async">
						</button>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Strip WooCommerce's gallery JS dependencies on single product pages —
 * we render gallery ourselves, no need for Flexslider/Zoom/PhotoSwipe.
 * This also removes the body-class `woocommerce-no-js` toggle script.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_product() ) { return; }
	wp_dequeue_script( 'flexslider' );
	wp_dequeue_script( 'zoom' );
	wp_dequeue_script( 'photoswipe' );
	wp_dequeue_script( 'photoswipe-ui-default' );
	wp_dequeue_script( 'wc-single-product' );
	wp_dequeue_style( 'photoswipe' );
	wp_dequeue_style( 'photoswipe-default-skin' );
}, 99 );

/**
 * Remove the WC `wc-product-gallery-*` body classes — they trigger CSS
 * that targets the default gallery DOM (`.woocommerce-product-gallery`).
 * Our markup uses `.lwp-gallery` and doesn't need them.
 */
add_filter( 'body_class', function ( $classes ) {
	if ( ! is_product() ) { return $classes; }
	return array_values( array_filter( $classes, function ( $c ) {
		return strpos( $c, 'wc-product-gallery-' ) !== 0;
	} ) );
}, 99 );

/**
 * Enqueue the vanilla gallery JS. Marked with `data-no-defer` +
 * `data-no-optimize` so LiteSpeed's JS Defer/Delay can't push it past
 * DOMContentLoaded. File is ~40 lines, no dependencies; cost is negligible.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_product() ) { return; }

	$rel  = '/assets/js/pdp-gallery.js';
	$abs  = LUWIPRESS_ONYX_DIR . $rel;
	$ver  = LUWIPRESS_ONYX_VERSION . '.' . ( file_exists( $abs ) ? filemtime( $abs ) : '0' );

	wp_enqueue_script(
		'luwipress-onyx-pdp-gallery',
		LUWIPRESS_ONYX_URI . $rel,
		[],
		$ver,
		true
	);
}, 20 );

/**
 * Bypass LiteSpeed JS Defer/Delay/Combine for our PDP gallery script.
 * Without these attributes LiteSpeed rewrites the <script> tag to
 * `type="litespeed/javascript"` which keeps it from executing until a
 * user interaction — defeating the purpose of having a JS-free init.
 */
add_filter( 'script_loader_tag', function ( $tag, $handle ) {
	if ( $handle === 'luwipress-onyx-pdp-gallery' ) {
		return str_replace(
			'<script ',
			'<script data-no-defer="1" data-no-optimize="1" data-no-minify="1" data-cfasync="false" ',
			$tag
		);
	}
	return $tag;
}, 10, 2 );
