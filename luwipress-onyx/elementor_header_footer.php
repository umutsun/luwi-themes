<?php
/**
 * Template Name: Elementor — Header + Footer
 * Template Post Type: post, page, product
 *
 * Hello-Elementor-style "canvas with theme chrome" template. Posts and
 * pages built entirely in Elementor — where the operator wants the
 * theme's header + footer kept but Elementor controls every other
 * pixel — assign this template via the Page/Post sidebar's "Template"
 * dropdown.
 *
 * Without this file, posts authored under a previous theme that
 * used `_wp_page_template = elementor_header_footer` would lose their
 * canvas branch and partially fall back to single.php. The fallback
 * inconsistently rendered (no `<main>`, no breadcrumb), giving the
 * "some posts open, some don't" symptom. Shipping this file makes the
 * template resolve cleanly so every Elementor-built post renders the
 * same way regardless of when it was first authored.
 *
 * @package luwipress-onyx
 * @since   1.6.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main class="lwp-elementor-page lwp-elementor-page--canvas" id="primary">
	<?php while ( have_posts() ) : the_post(); ?>
		<?php the_content(); ?>
		<?php wp_link_pages(); ?>
		<?php if ( comments_open() || get_comments_number() ) : ?>
			<div class="lwp-page-container" style="padding-top:64px;padding-bottom:64px;">
				<?php comments_template(); ?>
			</div>
		<?php endif; ?>
	<?php endwhile; ?>
</main>

<?php
get_footer();
