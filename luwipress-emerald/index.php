<?php
/**
 * LuwiPress Emerald — index.php
 *
 * Catch-all archive / blog landing. Renders the page head with the
 * archive title + a 3-column Insights grid using the .emerald-insight
 * card primitive.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<section class="emerald-page-head">
	<span class="emerald-page-eyebrow">
		<svg class="emerald-i emerald-i--sm" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h10M4 18h16"/></svg>
		<?php
		if ( is_home() ) {
			$blog_id = (int) get_option( 'page_for_posts' );
			echo esc_html( $blog_id ? get_the_title( $blog_id ) : __( 'Journal', 'luwipress-emerald' ) );
		} else {
			single_post_title( '', true );
		}
		?>
	</span>
	<h1 class="emerald-page-title">
		<?php
		if ( is_home() ) {
			$blog_id = (int) get_option( 'page_for_posts' );
			echo esc_html( $blog_id ? get_the_title( $blog_id ) : __( 'Field notes & essays.', 'luwipress-emerald' ) );
		} else {
			the_archive_title();
		}
		?>
	</h1>
	<?php
	$desc = get_the_archive_description();
	if ( $desc ) {
		echo '<div class="emerald-page-sub">' . wp_kses_post( $desc ) . '</div>';
	}
	?>
</section>

<section class="emerald-section">
	<?php if ( have_posts() ) : ?>
		<div class="emerald-insights-grid emerald-stagger emerald-reveal">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/insight-card' );
			endwhile;
			?>
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
			<h2 class="emerald-h2"><?php esc_html_e( 'Nothing here yet.', 'luwipress-emerald' ); ?></h2>
			<p class="emerald-404-sub"><?php esc_html_e( 'No posts match your filter. Try a search instead.', 'luwipress-emerald' ); ?></p>
			<?php get_search_form(); ?>
		</div>
	<?php endif; ?>
</section>

<?php
get_footer();
