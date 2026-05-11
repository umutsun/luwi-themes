<?php
/**
 * The Template for displaying product archives — shop page, product
 * categories, sub-categories, tags. Wraps the WC loop in our Gold page
 * chrome (breadcrumb + 2-col page header with category image + featured
 * sub-cat tiles) instead of the default WC markup, then defers to native
 * WC actions for the loop itself.
 *
 * @package luwipress-gold
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

// Title + intro defaults — overridden if a category description is set.
$title       = woocommerce_page_title( false );
$lead        = '';
$current_obj = get_queried_object();
$is_term     = $current_obj instanceof WP_Term;
if ( $is_term ) {
	$desc = term_description( $current_obj->term_id, $current_obj->taxonomy );
	if ( $desc ) {
		$lead = wp_strip_all_tags( $desc );
	}
}

// Category image — native WC product_cat thumbnail when available, else
// fall back to the first product's featured image. Shop page (non-term)
// has no contextual image; the right column simply collapses on desktop.
$header_image_html = '';
if ( $is_term ) {
	$thumb_id = (int) get_term_meta( $current_obj->term_id, 'thumbnail_id', true );
	if ( $thumb_id > 0 ) {
		$header_image_html = wp_get_attachment_image( $thumb_id, 'large', false, [
			'class'   => 'lwp-archive-header__img',
			'loading' => 'eager',
			'alt'     => $current_obj->name,
		] );
	}
}

// Sub-category tiles — only on a parent category that actually has children.
$subcats = [];
if ( $is_term && 'product_cat' === $current_obj->taxonomy ) {
	$subcats = get_terms( [
		'taxonomy'   => 'product_cat',
		'parent'     => $current_obj->term_id,
		'hide_empty' => true,
		'number'     => 12,
	] );
	if ( is_wp_error( $subcats ) ) {
		$subcats = [];
	}
}
?>

<main class="lwp-page lwp-shop-archive" id="primary">
	<div class="lwp-page-container">

		<nav class="lwp-crumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'luwipress-gold' ); ?>">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'luwipress-gold' ); ?></a>
			<?php if ( function_exists( 'wc_get_page_id' ) ) :
				$shop_id = wc_get_page_id( 'shop' );
				if ( $shop_id > 0 && ! is_shop() ) : ?>
					<span class="sep">›</span>
					<a href="<?php echo esc_url( get_permalink( $shop_id ) ); ?>"><?php esc_html_e( 'Shop', 'luwipress-gold' ); ?></a>
				<?php endif;
			endif; ?>
			<?php
			// Walk parent terms (if any) for sub-category context.
			if ( $is_term && $current_obj->parent ) {
				$ancestors = array_reverse( get_ancestors( $current_obj->term_id, $current_obj->taxonomy ) );
				foreach ( $ancestors as $aid ) {
					$at = get_term( $aid, $current_obj->taxonomy );
					if ( $at && ! is_wp_error( $at ) ) {
						echo '<span class="sep">›</span><a href="' . esc_url( get_term_link( $at ) ) . '">' . esc_html( $at->name ) . '</a>';
					}
				}
			}
			?>
			<span class="sep">›</span>
			<span class="current"><?php echo esc_html( $title ); ?></span>
		</nav>

		<?php
		/**
		 * Fires before the archive page header renders.
		 *
		 * @param WP_Term|null $current_obj Queried term, or null on shop archive.
		 */
		do_action( 'luwipress_gold_archive_intro_before', $is_term ? $current_obj : null );
		?>

		<header class="lwp-archive-header<?php echo $header_image_html ? ' lwp-archive-header--split' : ''; ?>">
			<div class="lwp-archive-header__copy">
				<?php if ( $is_term ) : ?>
					<span class="lwp-eyebrow">— <?php
					echo esc_html(
						$current_obj->parent
							? __( 'Sub-category', 'luwipress-gold' )
							: __( 'Collection', 'luwipress-gold' )
					);
					?></span>
				<?php endif; ?>
				<h1 class="lwp-page-title"><?php echo wp_kses_post( $title ); ?></h1>
				<?php if ( $lead ) : ?>
					<p class="lwp-page-lead"><?php echo esc_html( $lead ); ?></p>
				<?php endif; ?>
				<?php
				/**
				 * Fires inside the page header copy column, after the title/lead.
				 * Plugins can append term meta intros (e.g. master luthier note,
				 * region map, brand voice intro).
				 *
				 * @param WP_Term|null $current_obj
				 */
				do_action( 'luwipress_gold_archive_intro_after', $is_term ? $current_obj : null );
				?>
			</div>
			<?php if ( $header_image_html ) : ?>
				<div class="lwp-archive-header__media">
					<?php echo $header_image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>
		</header>

		<?php if ( ! empty( $subcats ) ) : ?>
			<?php
			/**
			 * Fires before the featured sub-category tiles render.
			 *
			 * @param WP_Term[] $subcats
			 * @param WP_Term   $current_obj
			 */
			do_action( 'luwipress_gold_archive_subcat_tiles_before', $subcats, $current_obj );
			?>
			<section class="lwp-archive-subcat-grid" aria-label="<?php esc_attr_e( 'Sub-categories', 'luwipress-gold' ); ?>">
				<?php foreach ( $subcats as $sc ) :
					$sc_thumb_id = (int) get_term_meta( $sc->term_id, 'thumbnail_id', true );
					$sc_img      = $sc_thumb_id > 0
						? wp_get_attachment_image( $sc_thumb_id, 'medium_large', false, [
							'class' => 'lwp-archive-subcat__img',
							'alt'   => $sc->name,
						] )
						: '<div class="lwp-archive-subcat__img lwp-skel"></div>';
					?>
					<a class="lwp-archive-subcat" href="<?php echo esc_url( get_term_link( $sc ) ); ?>">
						<div class="lwp-archive-subcat__img-wrap">
							<?php echo $sc_img; // phpcs:ignore ?>
						</div>
						<div class="lwp-archive-subcat__meta">
							<h3 class="lwp-archive-subcat__name"><?php echo esc_html( $sc->name ); ?></h3>
							<span class="lwp-archive-subcat__count">
								<?php
								/* translators: %d: product count */
								printf( esc_html( _n( '%d piece', '%d pieces', (int) $sc->count, 'luwipress-gold' ) ), (int) $sc->count );
								?>
							</span>
						</div>
					</a>
				<?php endforeach; ?>
			</section>
			<?php
			do_action( 'luwipress_gold_archive_subcat_tiles_after', $subcats, $current_obj );
			?>
		<?php endif; ?>

		<div class="lwp-shop-grid-wrap">

			<aside class="lwp-shop-sidebar" aria-label="<?php esc_attr_e( 'Filters', 'luwipress-gold' ); ?>">
				<?php
				// Operator-configured sidebar wins. Otherwise fall back to
				// the generic smart filter sidebar (price + attributes +
				// tags + on-sale/in-stock toggles). The legacy "Categories"
				// list was removed in 1.6.1 — it duplicated the mega menu
				// and added no real filtering value.
				if ( is_active_sidebar( 'shop-sidebar' ) ) {
					dynamic_sidebar( 'shop-sidebar' );
				} elseif ( function_exists( 'luwipress_gold_render_smart_filters' ) ) {
					luwipress_gold_render_smart_filters( $is_term ? $current_obj : null );
				}
				?>
			</aside>

			<section class="lwp-shop-results">

				<div class="lwp-shop-toolbar">
					<div>
						<?php woocommerce_result_count(); ?>
					</div>
					<?php woocommerce_catalog_ordering(); ?>
				</div>

				<?php
				if ( woocommerce_product_loop() ) {
					woocommerce_product_loop_start();

					if ( wc_get_loop_prop( 'total' ) ) {
						while ( have_posts() ) {
							the_post();
							/**
							 * Hook: woocommerce_shop_loop.
							 */
							do_action( 'woocommerce_shop_loop' );
							wc_get_template_part( 'content', 'product' );
						}
					}

					woocommerce_product_loop_end();
					woocommerce_pagination();

					/**
					 * Load-more sentinel + button (1.7.0+).
					 *
					 * Always rendered when the toggle is on AND the query has
					 * more than one page. The JS handler (loadmore.js) hides
					 * the woocommerce_pagination output via the
					 * `lwp-loadmore-active` class on the shop wrapper.
					 */
					if ( get_theme_mod( 'luwipress_gold_shop_loadmore', true ) ) {
						global $wp_query;
						$current_page = max( 1, (int) get_query_var( 'paged', 1 ) );
						$max_pages    = isset( $wp_query->max_num_pages ) ? (int) $wp_query->max_num_pages : 1;
						if ( $max_pages > 1 ) {
							/* Loading indicator is purely symbolic — a rotating ring
							   for the active state, "X / Y" numeric pagination for the
							   resting state, "✓" for end-of-list, "⚠" for error. No text
							   labels means zero i18n work for FR/IT/ES/DE/etc. The button
							   keeps a textual label since it's only visible in button-mode
							   (operator opt-in); infinite mode hides it via JS. */
							printf(
								'<div class="lwp-loadmore-wrap" data-current="%d" data-max="%d">'
									. '<button type="button" class="lwp-loadmore-btn" data-action="loadmore">%s</button>'
									. '<div class="lwp-loadmore-spinner" role="status" aria-label="%s" hidden>'
										. '<span class="lwp-loadmore-spinner__ring"></span>'
									. '</div>'
									. '<span class="lwp-loadmore-status" aria-live="polite"></span>'
									. '</div>',
								$current_page,
								$max_pages,
								esc_html__( 'Load more products', 'luwipress-gold' ),
								esc_attr__( 'Loading', 'luwipress-gold' )
							);
						}
					}
				} else {
					/**
					 * Hook: woocommerce_no_products_found.
					 */
					do_action( 'woocommerce_no_products_found' );
				}
				?>

			</section>

		</div>
	</div>
</main>

<?php
get_footer( 'shop' );
