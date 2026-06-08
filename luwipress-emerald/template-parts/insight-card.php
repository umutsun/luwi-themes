<?php
/**
 * LuwiPress Emerald — Insight (blog post) card.
 *
 * Used by index.php, archive.php, search.php, and any custom loop
 * that wants the Acme/Emerald journal-card primitive.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_categories = get_the_category();
$primary_cat     = $post_categories ? $post_categories[0]->name : '';
$reading_min     = (int) ceil( str_word_count( wp_strip_all_tags( get_the_content() ) ) / 220 );
$reading_min     = max( 1, $reading_min );
?>
<article <?php post_class( 'emerald-insight' ); ?>>
	<div class="emerald-insight-meta">
		<?php if ( $primary_cat ) : ?>
			<span><?php echo esc_html( $primary_cat ); ?></span>
		<?php endif; ?>
		<span><?php echo esc_html( get_the_date( 'M j, Y' ) ); ?></span>
		<span><?php
			/* translators: %d: estimated minutes to read */
			printf( esc_html__( '%d min read', 'luwipress-emerald' ), $reading_min ); ?></span>
	</div>
	<h2 class="emerald-insight-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
	<p class="emerald-insight-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 28, '…' ) ); ?></p>
	<a href="<?php the_permalink(); ?>" class="emerald-insight-arrow">
		<?php esc_html_e( 'Read', 'luwipress-emerald' ); ?>
		<svg class="emerald-i emerald-i--sm" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
	</a>
</article>
