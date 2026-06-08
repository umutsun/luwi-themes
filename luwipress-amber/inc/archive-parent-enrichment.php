<?php
/**
 * Archive parent-term enrichment.
 *
 * `woocommerce/archive-product.php` already exposes four action hooks for
 * the Gold archive layout:
 *   - luwipress_amber_archive_intro_before
 *   - luwipress_amber_archive_intro_after
 *   - luwipress_amber_archive_subcat_tiles_before
 *   - luwipress_amber_archive_subcat_tiles_after
 *
 * This module hangs two enrichments off those hooks, ALL gated to
 * parent-only product_cat archives (terms with `parent === 0` and at least
 * one child term):
 *
 *  1. Atelier note callout — a short editorial paragraph rendered above
 *     the sub-category tiles. Sourced from the term meta
 *     `_lwp_amber_archive_note`, which the operator fills via the term-edit
 *     screen. Empty meta = no callout (graceful no-op).
 *
 *  2. Featured product band — a single best-selling product from anywhere
 *     in the term tree, rendered between the sub-category tiles and the
 *     product grid. Reuses the same "best-seller across descendants" query
 *     pattern as the mega-menu featured-slot fallback so the surface stays
 *     coherent. Operator can override per-term via the
 *     `_lwp_amber_archive_featured` term meta.
 *
 * Leaf archives (terms with `parent !== 0`) skip every enrichment — the
 * default sub-category layout is already complete.
 *
 * @package luwipress-amber
 * @since   1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'luwipress_amber_is_parent_product_cat' ) ) {

	/**
	 * @param mixed $term Term passed by the action hook (may be null on shop).
	 * @return bool
	 */
	function luwipress_amber_is_parent_product_cat( $term ) {
		if ( ! ( $term instanceof WP_Term ) ) {
			return false;
		}
		if ( $term->taxonomy !== 'product_cat' ) {
			return false;
		}
		if ( (int) $term->parent !== 0 ) {
			return false;
		}
		// Must actually have at least one child to qualify as a hub.
		$kids = get_term_children( $term->term_id, 'product_cat' );
		return is_array( $kids ) && ! empty( $kids );
	}
}

if ( ! function_exists( 'luwipress_amber_render_archive_master_note' ) ) {

	/**
	 * Render the operator-curated editorial note above the sub-cat tiles.
	 * Hooked to `luwipress_amber_archive_subcat_tiles_before`.
	 *
	 * @param array        $subcats     Children — unused here, but matches signature.
	 * @param WP_Term|null $current_obj Queried term.
	 */
	function luwipress_amber_render_archive_master_note( $subcats, $current_obj ) {
		unset( $subcats );
		if ( ! luwipress_amber_is_parent_product_cat( $current_obj ) ) {
			return;
		}
		$note = (string) get_term_meta( $current_obj->term_id, '_lwp_amber_archive_note', true );
		$note = trim( $note );
		if ( $note === '' ) {
			return;
		}
		?>
		<aside class="lwp-archive-note" role="note" aria-label="<?php esc_attr_e( 'Curator note', 'luwipress-amber' ); ?>">
			<span class="lwp-archive-note__eyebrow"><?php esc_html_e( 'Atelier note', 'luwipress-amber' ); ?></span>
			<p class="lwp-archive-note__body"><?php echo wp_kses_post( $note ); ?></p>
		</aside>
		<?php
	}
	add_action( 'luwipress_amber_archive_subcat_tiles_before', 'luwipress_amber_render_archive_master_note', 10, 2 );
}

if ( ! function_exists( 'luwipress_amber_resolve_archive_featured_product' ) ) {

	/**
	 * Pick a featured product for a parent term — best-seller across the
	 * term and every descendant. Mirrors the mega-menu auto-featured logic
	 * so the visual surface is consistent. Operator can override per-term
	 * with the meta `_lwp_amber_archive_featured`.
	 *
	 * @param WP_Term $term
	 * @return int Product ID, or 0 if none found.
	 */
	function luwipress_amber_resolve_archive_featured_product( $term ) {
		if ( ! ( $term instanceof WP_Term ) ) {
			return 0;
		}
		$override = (int) get_term_meta( $term->term_id, '_lwp_amber_archive_featured', true );
		if ( $override > 0 ) {
			$post = get_post( $override );
			if ( $post && $post->post_type === 'product' && $post->post_status === 'publish' ) {
				return $override;
			}
		}
		if ( ! function_exists( 'wc_get_product' ) ) {
			return 0;
		}
		$descendants = get_term_children( $term->term_id, 'product_cat' );
		$term_ids    = array_merge( [ (int) $term->term_id ], is_array( $descendants ) ? $descendants : [] );

		$query = new WP_Query( [
			'post_type'      => 'product',
			'posts_per_page' => 1,
			'post_status'    => 'publish',
			'no_found_rows'  => true,
			'tax_query'      => [
				[
					'taxonomy'         => 'product_cat',
					'field'            => 'term_id',
					'terms'            => $term_ids,
					'include_children' => false,
				],
			],
			'meta_key'       => 'total_sales',
			'orderby'        => [ 'meta_value_num' => 'DESC', 'date' => 'DESC' ],
		] );
		$id = 0;
		if ( $query->have_posts() ) {
			$id = (int) $query->posts[0]->ID;
		}
		wp_reset_postdata();
		return $id;
	}
}

if ( ! function_exists( 'luwipress_amber_render_archive_featured_band' ) ) {

	/**
	 * Render the parent-term featured-product band, slotting between the
	 * sub-cat tiles and the product grid. Hooked to
	 * `luwipress_amber_archive_subcat_tiles_after`.
	 *
	 * @param array        $subcats
	 * @param WP_Term|null $current_obj
	 */
	function luwipress_amber_render_archive_featured_band( $subcats, $current_obj ) {
		unset( $subcats );
		if ( ! luwipress_amber_is_parent_product_cat( $current_obj ) ) {
			return;
		}
		$pid = luwipress_amber_resolve_archive_featured_product( $current_obj );
		if ( $pid <= 0 ) {
			return;
		}
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $pid ) : null;
		if ( ! $product ) {
			return;
		}
		$image = get_the_post_thumbnail_url( $pid, 'large' );
		$href  = get_permalink( $pid );
		$price = $product->get_price_html();
		$short = $product->get_short_description();
		$short = $short ? wp_strip_all_tags( $short ) : '';
		if ( $short !== '' && function_exists( 'wp_html_excerpt' ) ) {
			$short = wp_html_excerpt( $short, 160, '…' );
		}
		?>
		<section class="lwp-archive-featured" aria-label="<?php esc_attr_e( 'Featured piece', 'luwipress-amber' ); ?>">
			<a class="lwp-archive-featured__inner" href="<?php echo esc_url( $href ); ?>">
				<div class="lwp-archive-featured__media"<?php echo $image ? ' style="background-image:url(' . esc_url( $image ) . ');background-size:cover;background-position:center"' : ''; ?>>
					<span class="lwp-archive-featured__eyebrow"><?php esc_html_e( 'Atelier pick', 'luwipress-amber' ); ?></span>
				</div>
				<div class="lwp-archive-featured__copy">
					<h3 class="lwp-archive-featured__title"><?php echo esc_html( get_the_title( $pid ) ); ?></h3>
					<?php if ( $short ) : ?>
						<p class="lwp-archive-featured__lead"><?php echo esc_html( $short ); ?></p>
					<?php endif; ?>
					<?php if ( $price ) : ?>
						<span class="lwp-archive-featured__price"><?php echo wp_kses_post( $price ); ?></span>
					<?php endif; ?>
					<span class="lwp-archive-featured__cta"><?php esc_html_e( 'Open piece →', 'luwipress-amber' ); ?></span>
				</div>
			</a>
		</section>
		<?php
	}
	add_action( 'luwipress_amber_archive_subcat_tiles_after', 'luwipress_amber_render_archive_featured_band', 10, 2 );
}

if ( ! function_exists( 'luwipress_amber_register_archive_term_meta_ui' ) ) {

	/**
	 * Add the two extra fields (`_lwp_amber_archive_note`,
	 * `_lwp_amber_archive_featured`) to the product_cat add/edit screens.
	 * Operator never has to touch code to set them.
	 */
	function luwipress_amber_register_archive_term_meta_ui() {
		// Only render on the term-edit screen.
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return;
		}
		add_action( 'product_cat_edit_form_fields', 'luwipress_amber_render_term_meta_fields_edit', 30, 2 );
		add_action( 'product_cat_add_form_fields',  'luwipress_amber_render_term_meta_fields_add',  30, 1 );
		add_action( 'edited_product_cat',           'luwipress_amber_save_term_meta_fields',        20, 2 );
		add_action( 'created_product_cat',          'luwipress_amber_save_term_meta_fields',        20, 2 );
	}
	add_action( 'admin_init', 'luwipress_amber_register_archive_term_meta_ui' );
}

if ( ! function_exists( 'luwipress_amber_render_term_meta_fields_edit' ) ) {

	function luwipress_amber_render_term_meta_fields_edit( $term, $taxonomy ) {
		unset( $taxonomy );
		$note     = (string) get_term_meta( $term->term_id, '_lwp_amber_archive_note', true );
		$featured = (int) get_term_meta( $term->term_id, '_lwp_amber_archive_featured', true );
		?>
		<tr class="form-field">
			<th scope="row"><label for="lwp_amber_archive_note"><?php esc_html_e( 'Atelier note', 'luwipress-amber' ); ?></label></th>
			<td>
				<textarea name="lwp_amber_archive_note" id="lwp_amber_archive_note" rows="3" cols="50" style="max-width:100%"><?php echo esc_textarea( $note ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Short editorial paragraph rendered above sub-category tiles on parent category archives. HTML allowed (light tags). Leave empty to hide.', 'luwipress-amber' ); ?></p>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="lwp_amber_archive_featured"><?php esc_html_e( 'Featured product (override)', 'luwipress-amber' ); ?></label></th>
			<td>
				<input type="number" name="lwp_amber_archive_featured" id="lwp_amber_archive_featured" value="<?php echo $featured > 0 ? (int) $featured : ''; ?>" min="0" style="width:160px">
				<p class="description"><?php esc_html_e( 'Product ID to feature in the parent-archive band. Leave empty to auto-pick the best-selling product across this category and its descendants.', 'luwipress-amber' ); ?></p>
			</td>
		</tr>
		<?php
	}
}

if ( ! function_exists( 'luwipress_amber_render_term_meta_fields_add' ) ) {

	function luwipress_amber_render_term_meta_fields_add( $taxonomy ) {
		unset( $taxonomy );
		?>
		<div class="form-field">
			<label for="lwp_amber_archive_note"><?php esc_html_e( 'Atelier note', 'luwipress-amber' ); ?></label>
			<textarea name="lwp_amber_archive_note" id="lwp_amber_archive_note" rows="3" cols="50"></textarea>
			<p><?php esc_html_e( 'Optional editorial paragraph for parent-category archives.', 'luwipress-amber' ); ?></p>
		</div>
		<div class="form-field">
			<label for="lwp_amber_archive_featured"><?php esc_html_e( 'Featured product (override)', 'luwipress-amber' ); ?></label>
			<input type="number" name="lwp_amber_archive_featured" id="lwp_amber_archive_featured" min="0" />
			<p><?php esc_html_e( 'Optional product ID. Leave empty for auto-best-seller.', 'luwipress-amber' ); ?></p>
		</div>
		<?php
	}
}

if ( ! function_exists( 'luwipress_amber_save_term_meta_fields' ) ) {

	function luwipress_amber_save_term_meta_fields( $term_id, $tt_id = 0 ) {
		unset( $tt_id );
		if ( ! current_user_can( 'manage_product_terms' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( isset( $_POST['lwp_amber_archive_note'] ) ) {
			$note = wp_kses_post( wp_unslash( (string) $_POST['lwp_amber_archive_note'] ) );
			if ( $note === '' ) {
				delete_term_meta( $term_id, '_lwp_amber_archive_note' );
			} else {
				update_term_meta( $term_id, '_lwp_amber_archive_note', $note );
			}
		}
		if ( isset( $_POST['lwp_amber_archive_featured'] ) ) {
			$pid = (int) $_POST['lwp_amber_archive_featured'];
			if ( $pid <= 0 ) {
				delete_term_meta( $term_id, '_lwp_amber_archive_featured' );
			} else {
				update_term_meta( $term_id, '_lwp_amber_archive_featured', $pid );
			}
		}
	}
}

/* ------------------------------------------------------------------
 * Minimal CSS — kept inline-on-load to avoid bloating widgets.css
 * (paralel mobile-responsive session is editing widgets.css, so we
 * inline-print to dodge the merge conflict). Cheap: ~1.2 KB once.
 * ------------------------------------------------------------------ */
if ( ! function_exists( 'luwipress_amber_archive_enrichment_styles' ) ) {

	function luwipress_amber_archive_enrichment_styles() {
		// Only on product_cat archives — and only when current term is parent.
		if ( ! is_tax( 'product_cat' ) ) {
			return;
		}
		$term = get_queried_object();
		if ( ! luwipress_amber_is_parent_product_cat( $term ) ) {
			return;
		}
		?>
<style id="lwp-amber-archive-enrichment">
.lwp-archive-note{
	margin:24px 0 16px;padding:18px 22px;border-left:3px solid var(--lwp-amber-accent,#735c00);
	background:var(--lwp-amber-bg-alt,#fafaf6);border-radius:0 8px 8px 0;
}
.lwp-archive-note__eyebrow{
	display:block;font-size:11px;letter-spacing:.14em;text-transform:uppercase;
	color:var(--lwp-amber-accent,#735c00);font-weight:600;margin-bottom:6px;
}
.lwp-archive-note__body{
	margin:0;font-family:var(--lwp-amber-serif,"Playfair Display",serif);
	font-style:italic;font-size:17px;line-height:1.55;color:var(--lwp-amber-ink,#1b1c1c);
}
.lwp-archive-featured{
	margin:24px 0;border:1px solid var(--lwp-amber-line,#e7e3d6);border-radius:12px;
	overflow:hidden;background:#fff;
}
.lwp-archive-featured__inner{
	display:grid;grid-template-columns:minmax(220px,38%) 1fr;gap:0;
	color:inherit;text-decoration:none;align-items:stretch;
}
.lwp-archive-featured__media{
	background:#f3efe5;min-height:240px;position:relative;
}
.lwp-archive-featured__eyebrow{
	position:absolute;top:14px;left:14px;background:#1b1c1c;color:#fff;
	font-size:11px;letter-spacing:.14em;text-transform:uppercase;
	padding:5px 10px;border-radius:999px;
}
.lwp-archive-featured__copy{
	padding:24px 28px;display:flex;flex-direction:column;gap:10px;justify-content:center;
}
.lwp-archive-featured__title{
	margin:0;font-family:var(--lwp-amber-serif,"Playfair Display",serif);
	font-size:26px;font-weight:600;line-height:1.2;color:var(--lwp-amber-ink,#1b1c1c);
}
.lwp-archive-featured__lead{
	margin:0;font-size:14.5px;line-height:1.55;color:#555;
}
.lwp-archive-featured__price{
	font-size:18px;font-weight:600;color:var(--lwp-amber-accent,#735c00);
}
.lwp-archive-featured__cta{
	margin-top:6px;font-size:13px;letter-spacing:.04em;color:var(--lwp-amber-accent,#735c00);
	font-weight:600;text-transform:uppercase;
}
@media (max-width:720px){
	.lwp-archive-featured__inner{grid-template-columns:1fr}
	.lwp-archive-featured__media{min-height:200px;aspect-ratio:4/3}
	.lwp-archive-featured__copy{padding:18px 20px}
	.lwp-archive-featured__title{font-size:22px}
	.lwp-archive-note{padding:14px 16px;margin:18px 0 10px}
	.lwp-archive-note__body{font-size:15.5px}
}
</style>
		<?php
	}
	add_action( 'wp_head', 'luwipress_amber_archive_enrichment_styles', 99 );
}
