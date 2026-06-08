<?php
/**
 * Journal card — the per-post unit shared by home.php and archive.php.
 * Caller is responsible for the surrounding `<div class="lwp-jnl-grid">`
 * and for advancing the loop ($index, the_post()).
 *
 * Expected $args:
 *   - index (int)   1-based post counter; first post on page 1 becomes the big featured card
 *   - is_paged (bool)
 *
 * @package luwipress-emerald
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$index     = isset( $args['index'] ) ? (int) $args['index'] : 1;
$is_paged  = ! empty( $args['is_paged'] );
$is_big    = ( 1 === $index && ! $is_paged );

$cats         = get_the_category();
$primary_cat  = ! empty( $cats ) ? $cats[0]->name : '';
$reading_time = max( 1, (int) round( str_word_count( wp_strip_all_tags( get_the_content() ) ) / 220 ) );
$thumb_size   = $is_big ? 'large' : 'medium_large';
$thumb_html   = has_post_thumbnail()
	? get_the_post_thumbnail( get_the_ID(), $thumb_size, [ 'class' => 'lwp-jnl-img' ] )
	: '<div class="lwp-jnl-img lwp-skel"></div>';
?>
<a class="lwp-jnl<?php echo $is_big ? ' lwp-jnl--big' : ''; ?>" href="<?php the_permalink(); ?>">
	<div class="lwp-jnl-img-wrap">
		<?php echo $thumb_html; // phpcs:ignore ?>
	</div>
	<div class="lwp-jnl-meta">
		<span class="lwp-eyebrow">
			<?php
			if ( $primary_cat ) {
				echo '— ' . esc_html( $primary_cat ) . ' · ';
			} else {
				echo '— ';
			}
			/* translators: %d: reading time in minutes */
			printf( esc_html( _n( '%d min', '%d min', $reading_time, 'luwipress-emerald' ) ), $reading_time );
			?>
		</span>
		<h3 class="lwp-jnl-title"><?php the_title(); ?></h3>
		<?php if ( $is_big && has_excerpt() ) : ?>
			<p class="lwp-jnl-excerpt"><?php echo wp_kses_post( get_the_excerpt() ); ?></p>
		<?php endif; ?>
		<time class="lwp-jnl-date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
	</div>
</a>
