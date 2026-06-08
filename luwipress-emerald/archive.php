<?php
/**
 * LuwiPress Emerald — archive.php
 *
 * Category / tag / author / date archive landing. Shares the page-head
 * primitive with index.php but pulls the archive title + description
 * through the_archive_* helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<section class="emerald-page-head emerald-reveal">
	<span class="emerald-page-eyebrow"><?php esc_html_e( 'Archive', 'luwipress-emerald' ); ?></span>
	<h1 class="emerald-page-title"><?php echo wp_kses_post( get_the_archive_title() ); ?></h1>
	<?php $desc = get_the_archive_description();
	if ( $desc ) : ?>
		<div class="emerald-page-sub"><?php echo wp_kses_post( $desc ); ?></div>
	<?php endif; ?>
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
			<h2 class="emerald-h2"><?php esc_html_e( 'Nothing in this archive yet.', 'luwipress-emerald' ); ?></h2>
		</div>
	<?php endif; ?>
</section>

<?php
get_footer();
