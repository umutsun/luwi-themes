<?php
/**
 * LuwiPress Emerald — single.php
 *
 * Default single post layout: page-head with category eyebrow + post
 * title + meta, narrow editorial column for the body, tag pills at
 * the foot.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<?php while ( have_posts() ) : the_post();
	$cats     = get_the_category();
	$cat_name = $cats ? $cats[0]->name : '';
	$cat_link = $cats ? get_category_link( $cats[0]->term_id ) : '';
	$words    = str_word_count( wp_strip_all_tags( get_the_content() ) );
	$mins     = max( 1, (int) ceil( $words / 220 ) );
	?>
	<section class="emerald-page-head emerald-reveal">
		<?php if ( $cat_name ) : ?>
			<a href="<?php echo esc_url( $cat_link ); ?>" class="emerald-page-eyebrow" style="text-decoration:none;">
				<svg class="emerald-i emerald-i--sm" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 5h6l2 2h10v11a2 2 0 0 1-2 2H3z"/></svg>
				<?php echo esc_html( $cat_name ); ?>
			</a>
		<?php endif; ?>
		<h1 class="emerald-page-title"><?php the_title(); ?></h1>
		<div class="emerald-post-meta">
			<span><?php echo esc_html( get_the_date( 'M j, Y' ) ); ?></span>
			<span><?php
				/* translators: 1: author display name */
				printf( esc_html__( 'by %s', 'luwipress-emerald' ), esc_html( get_the_author() ) ); ?></span>
			<span><?php
				/* translators: %d: minutes to read */
				printf( esc_html__( '%d min read', 'luwipress-emerald' ), $mins ); ?></span>
		</div>
	</section>

	<?php if ( has_post_thumbnail() ) : ?>
		<figure class="emerald-section emerald-reveal" style="padding-top:0;padding-bottom:0;max-width:var(--container);">
			<?php the_post_thumbnail( 'emerald-hero', array( 'style' => 'width:100%;border-radius:var(--radius-lg);border:1px solid var(--line);' ) ); ?>
		</figure>
	<?php endif; ?>

	<article <?php post_class( 'emerald-content emerald-reveal' ); ?>>
		<?php the_content(); ?>
		<?php
		wp_link_pages( array(
			'before'      => '<div class="emerald-page-links">',
			'after'       => '</div>',
			'link_before' => '<span>',
			'link_after'  => '</span>',
		) );
		?>
		<?php $tags = get_the_tags();
		if ( $tags ) : ?>
			<div class="emerald-post-tags">
				<?php foreach ( $tags as $tag ) : ?>
					<a class="emerald-post-tag" href="<?php echo esc_url( get_tag_link( $tag->term_id ) ); ?>">#<?php echo esc_html( $tag->name ); ?></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</article>

	<?php
	if ( comments_open() || get_comments_number() ) {
		echo '<div class="emerald-content" style="padding-top:0;">';
		comments_template();
		echo '</div>';
	}
	?>
<?php endwhile; ?>

<?php
get_footer();
