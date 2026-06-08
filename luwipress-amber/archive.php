<?php
/**
 * Generic post archive — categories, tags, authors, dates.
 * Reuses the journal card markup so any post-archive view picks up the
 * same editorial grid styling as `home.php`. WooCommerce product
 * archives are routed to `woocommerce/archive-product.php` upstream
 * and never reach this template.
 *
 * @package luwipress-amber
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$archive_title = get_the_archive_title();
$archive_desc  = get_the_archive_description();
?>

<main class="lwp-page lwp-journal" id="primary">
	<div class="lwp-page-container">

		<nav class="lwp-crumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'luwipress-amber' ); ?>">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'luwipress-amber' ); ?></a>
			<?php
			$posts_page_id = (int) get_option( 'page_for_posts' );
			if ( $posts_page_id > 0 ) :
				$posts_page_title = get_the_title( $posts_page_id );
				if ( $posts_page_title ) : ?>
					<span class="sep">›</span>
					<a href="<?php echo esc_url( get_permalink( $posts_page_id ) ); ?>"><?php echo esc_html( $posts_page_title ); ?></a>
				<?php endif;
			endif;
			?>
			<span class="sep">›</span>
			<span class="current"><?php echo wp_kses_post( $archive_title ); ?></span>
		</nav>

		<header class="lwp-journal-header">
			<span class="lwp-eyebrow">— <?php
			if ( is_category() ) {
				esc_html_e( 'Category', 'luwipress-amber' );
			} elseif ( is_tag() ) {
				esc_html_e( 'Tag', 'luwipress-amber' );
			} elseif ( is_author() ) {
				esc_html_e( 'Author', 'luwipress-amber' );
			} elseif ( is_date() ) {
				esc_html_e( 'Archive', 'luwipress-amber' );
			} else {
				esc_html_e( 'From the atelier', 'luwipress-amber' );
			}
			?></span>
			<h1 class="lwp-page-title"><?php echo wp_kses_post( $archive_title ); ?></h1>
			<?php if ( $archive_desc ) : ?>
				<div class="lwp-page-lead"><?php echo wp_kses_post( $archive_desc ); ?></div>
			<?php endif; ?>
		</header>

		<?php
		/**
		 * Category chip strip — opt-in (default OFF since 1.6.2).
		 *
		 * Most stores already expose blog categories in the mega menu
		 * (the theme's mega menu auto-injects them under the BLOG top
		 * item), so rendering them a second time above every archive
		 * grid is redundant noise. Sites that prefer the chip rail can
		 * flip the filter to true:
		 *
		 *   add_filter( 'luwipress_amber_show_archive_category_chips', '__return_true' );
		 */
		$show_chips = (bool) apply_filters(
			'luwipress_amber_show_archive_category_chips',
			false,
			is_archive() ? get_queried_object() : null
		);
		if ( $show_chips && ( is_home() || is_category() || is_tag() || is_post_type_archive( 'post' ) ) ) :
			$chip_terms = get_terms( [
				'taxonomy'   => 'category',
				'hide_empty' => true,
				'number'     => 12,
				'orderby'    => 'count',
				'order'      => 'DESC',
			] );
			if ( ! is_wp_error( $chip_terms ) && $chip_terms ) :
				$current_cat_id = is_category() ? get_queried_object_id() : 0;
				$total_posts    = (int) wp_count_posts( 'post' )->publish;
		?>
		<nav class="lwp-cat-chips" aria-label="<?php esc_attr_e( 'Filter by category', 'luwipress-amber' ); ?>">
			<a href="<?php echo esc_url( get_post_type_archive_link( 'post' ) ?: home_url( '/' ) ); ?>" class="<?php echo ( ! $current_cat_id ) ? 'is-active' : ''; ?>"><?php
				/* translators: %d: total post count */
				printf( esc_html__( 'All · %d', 'luwipress-amber' ), $total_posts );
			?></a>
			<?php foreach ( $chip_terms as $term ) : ?>
				<a href="<?php echo esc_url( get_term_link( $term ) ); ?>" class="<?php echo ( (int) $term->term_id === $current_cat_id ) ? 'is-active' : ''; ?>"><?php
					echo esc_html( $term->name . ' · ' . $term->count );
				?></a>
			<?php endforeach; ?>
		</nav>
		<?php endif; endif; ?>

		<?php if ( have_posts() ) : ?>

			<div class="lwp-jnl-grid">
				<?php
				$index = 0;
				while ( have_posts() ) :
					the_post();
					$index++;
					get_template_part( 'template-parts/journal-card', null, [
						'index'    => $index,
						'is_paged' => is_paged(),
					] );
				endwhile;
				?>
			</div>

			<?php
			$prev = get_previous_posts_link( '← ' . __( 'Newer', 'luwipress-amber' ) );
			$next = get_next_posts_link( __( 'Older', 'luwipress-amber' ) . ' →' );
			if ( $prev || $next ) : ?>
				<nav class="lwp-jnl-pagination" aria-label="<?php esc_attr_e( 'Posts pagination', 'luwipress-amber' ); ?>">
					<span class="lwp-jnl-pagination__prev"><?php echo $prev ?: '&nbsp;'; // phpcs:ignore ?></span>
					<span class="lwp-jnl-pagination__pages">
						<?php
						global $wp_query;
						$total   = (int) $wp_query->max_num_pages;
						$current = max( 1, get_query_var( 'paged' ) );
						/* translators: 1: current page 2: total pages */
						printf( esc_html__( 'Page %1$d of %2$d', 'luwipress-amber' ), $current, $total );
						?>
					</span>
					<span class="lwp-jnl-pagination__next"><?php echo $next ?: '&nbsp;'; // phpcs:ignore ?></span>
				</nav>
			<?php endif; ?>

			<?php
			// Infinite scroll / load-more over the crawlable pagination above.
			// Covers Event CPT + blog/category/tag/author/date archives.
			if ( function_exists( 'lwp_amber_loadmore_render' ) ) {
				lwp_amber_loadmore_render();
			}
			?>

		<?php else : ?>

			<div class="lwp-jnl-empty">
				<p><?php esc_html_e( 'Nothing here yet — try another category or come back soon.', 'luwipress-amber' ); ?></p>
			</div>

		<?php endif; ?>

	</div>
</main>

<?php
get_footer();
