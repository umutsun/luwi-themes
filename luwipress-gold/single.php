<?php
/**
 * Single post fallback. Replaced by Elementor Theme Builder Single template
 * when the operator imports `08-journal.json` and assigns it as the Post
 * single template.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();
?>
<main class="lwp-fallback-main" id="primary">
	<?php while ( have_posts() ) : the_post(); ?>
		<article <?php post_class( 'lwp-fallback-single' ); ?>>
			<header class="lwp-fallback-single__head">
				<h1><?php the_title(); ?></h1>
				<p class="lwp-fallback-single__meta"><?php echo esc_html( get_the_date() ); ?></p>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="lwp-fallback-single__thumb"><?php the_post_thumbnail( 'large' ); ?></div>
			<?php endif; ?>

			<div class="lwp-fallback-single__body">
				<?php the_content(); ?>
				<?php wp_link_pages(); ?>
			</div>

			<?php if ( comments_open() || get_comments_number() ) : ?>
				<div class="lwp-fallback-single__comments"><?php comments_template(); ?></div>
			<?php endif; ?>
		</article>
	<?php endwhile; ?>
</main>
<?php
get_footer();
