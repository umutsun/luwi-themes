<?php
/**
 * LuwiPress Emerald — search.php
 *
 * Search-results landing: page-head with the query echoed back, then
 * the insight-card grid (re-used). When the query returns nothing we
 * show the 404 primitive with the search form embedded.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<section class="emerald-page-head emerald-reveal">
	<span class="emerald-page-eyebrow">
		<svg class="emerald-i emerald-i--sm" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
		<?php esc_html_e( 'Search', 'luwipress-emerald' ); ?>
	</span>
	<h1 class="emerald-page-title">
		<?php
		/* translators: %s: search query */
		printf( esc_html__( 'Results for &ldquo;%s&rdquo;', 'luwipress-emerald' ), esc_html( get_search_query() ) );
		?>
	</h1>
	<p class="emerald-page-sub">
		<?php
		global $wp_query;
		$total = (int) $wp_query->found_posts;
		/* translators: %d: number of results */
		printf( esc_html( _n( '%d result', '%d results', $total, 'luwipress-emerald' ) ), $total );
		?>
	</p>
</section>

<section class="emerald-section">
	<?php if ( have_posts() ) : ?>
		<div class="emerald-insights-grid emerald-stagger emerald-reveal">
			<?php while ( have_posts() ) : the_post();
				get_template_part( 'template-parts/insight-card' );
			endwhile; ?>
		</div>

		<?php
		the_posts_pagination(
			array(
				'mid_size'  => 2,
				'prev_text' => __( '&larr; Older', 'luwipress-emerald' ),
				'next_text' => __( 'Newer &rarr;', 'luwipress-emerald' ),
				'class'     => 'emerald-pagination',
			)
		);
		?>
	<?php else : ?>
		<div class="emerald-404" style="padding:var(--sp-16) 0;">
			<h2 class="emerald-h2"><?php esc_html_e( 'No results.', 'luwipress-emerald' ); ?></h2>
			<p class="emerald-404-sub"><?php esc_html_e( 'Nothing matched your search. Try a different phrase.', 'luwipress-emerald' ); ?></p>
			<?php get_search_form(); ?>
		</div>
	<?php endif; ?>
</section>

<?php
get_footer();
