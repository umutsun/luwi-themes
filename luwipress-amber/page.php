<?php
/**
 * Page template — branches on whether the page is built with Elementor.
 *
 * Elementor "Default" page template tells Elementor to NOT take over
 * rendering — the theme is expected to call `the_content()` itself, and
 * Elementor's widget HTML flows through the content filter. If the
 * theme then wraps that output in a width-constrained reading column
 * (e.g. `max-width: 72ch`), every Elementor section gets squeezed into
 * a ~720 px gutter regardless of what the operator built. The fix is
 * to detect Elementor-built posts and emit content full-width with no
 * chrome — Elementor handles its own hero, title, and section padding.
 *
 * Non-Elementor pages still get the editorial fallback chrome
 * (centered title + reading-width body) for readability.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$post_id        = get_queried_object_id();
$is_el_built    = $post_id && (bool) get_post_meta( $post_id, '_elementor_edit_mode', true );
$has_el_data    = $post_id && (bool) get_post_meta( $post_id, '_elementor_data', true );
$elementor_page = $is_el_built && $has_el_data;
?>

<?php if ( $elementor_page ) : ?>

	<main class="lwp-elementor-page" id="primary">
		<?php while ( have_posts() ) : the_post(); ?>
			<?php the_content(); ?>
			<?php wp_link_pages(); ?>
		<?php endwhile; ?>
	</main>

<?php else : ?>

	<main class="lwp-fallback-main" id="primary">
		<?php while ( have_posts() ) : the_post(); ?>
			<article class="lwp-fallback-page">
				<header class="lwp-fallback-page__head">
					<h1 class="lwp-fallback-page__title"><?php the_title(); ?></h1>
				</header>
				<div class="lwp-fallback-page__content">
					<?php
					the_content();
					wp_link_pages();
					?>
				</div>
			</article>
		<?php endwhile; ?>
	</main>

<?php endif; ?>

<?php
get_footer();
