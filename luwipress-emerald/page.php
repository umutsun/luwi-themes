<?php
/**
 * LuwiPress Emerald — page.php
 *
 * Default page template. Elementor-built pages render via the canvas
 * template (page-elementor-canvas.php) the operator selects per page;
 * this file handles plain pages (privacy, terms, etc.) with a clean
 * editorial column.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<?php while ( have_posts() ) : the_post(); ?>
	<section class="emerald-page-head emerald-reveal">
		<?php
		$ancestors = get_post_ancestors( get_the_ID() );
		if ( $ancestors ) {
			$root = end( $ancestors );
			echo '<span class="emerald-page-eyebrow">' . esc_html( get_the_title( $root ) ) . '</span>';
		} else {
			echo '<span class="emerald-page-eyebrow">' . esc_html__( 'Page', 'luwipress-emerald' ) . '</span>';
		}
		?>
		<h1 class="emerald-page-title"><?php the_title(); ?></h1>
		<?php $excerpt = get_the_excerpt();
		if ( $excerpt && ! has_excerpt() === false ) : ?>
			<p class="emerald-page-sub"><?php echo esc_html( $excerpt ); ?></p>
		<?php endif; ?>
	</section>

	<div class="emerald-content emerald-reveal">
		<?php the_content(); ?>
		<?php
		wp_link_pages( array(
			'before'      => '<div class="emerald-page-links">',
			'after'       => '</div>',
			'link_before' => '<span>',
			'link_after'  => '</span>',
		) );
		?>
	</div>
<?php endwhile; ?>

<?php
get_footer();
