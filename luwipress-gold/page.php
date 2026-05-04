<?php
/**
 * Page fallback. When Elementor edits a page, Elementor takes over rendering
 * and this template is bypassed. The fallback below covers the case where the
 * operator hasn't yet built the page in Elementor — a stripped-down container
 * with the post content rendered straight, no extra chrome.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();
?>
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
<?php
get_footer();
