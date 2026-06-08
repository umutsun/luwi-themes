<?php
/**
 * Generic fallback (blog index when no home.php applies, misc lists).
 * Mirrors archive.php's Onyx journal grid.
 *
 * @package luwipress-onyx
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$onyx_glyphs = array( 'tower', 'interior', 'exterior' );
?>
<main>
	<section class="phead">
		<div class="wrap">
			<span class="smallcaps" style="color:var(--gold)"><?php esc_html_e( 'The Journal', 'luwipress-onyx' ); ?></span>
			<h1 class="display-xl" style="margin-top:14px;font-size:clamp(40px,5.5vw,76px)"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h1>
			<p class="phead-sub"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
		</div>
	</section>

	<section class="section" style="padding-top:clamp(40px,5vw,64px)">
		<div class="wrap">
			<?php if ( have_posts() ) : ?>
				<div class="news-grid">
					<?php
					$onyx_i = 0;
					while ( have_posts() ) : the_post();
						$cats  = get_the_category();
						$cat   = ! empty( $cats ) ? $cats[0]->name : __( 'Journal', 'luwipress-onyx' );
						$thumb = get_the_post_thumbnail_url( get_the_ID(), 'large' );
						?>
						<article class="ncard reveal">
							<a href="<?php the_permalink(); ?>" style="display:block;color:inherit">
								<div class="ncard-media"><?php echo onyx_ph( array( 'glyph' => $onyx_glyphs[ $onyx_i % 3 ], 'img' => $thumb ) ); // phpcs:ignore ?><div class="ncard-date"><div class="d tnum"><?php echo esc_html( get_the_date( 'd' ) ); ?></div><div class="m"><?php echo esc_html( get_the_date( 'M' ) ); ?></div></div></div>
								<div class="ncard-cat"><?php echo esc_html( $cat ); ?></div>
								<h3><?php the_title(); ?></h3>
								<span class="ncard-more"><?php esc_html_e( 'Read more', 'luwipress-onyx' ); ?> <?php echo onyx_icon( 'arrow', 14 ); // phpcs:ignore ?></span>
							</a>
						</article>
						<?php $onyx_i++;
					endwhile; ?>
				</div>
				<?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => '‹', 'next_text' => '›' ) ); ?>
			<?php else : ?>
				<div class="empty">
					<span class="ic"><?php echo onyx_icon( 'search', 34 ); // phpcs:ignore ?></span>
					<h3><?php esc_html_e( 'No posts yet', 'luwipress-onyx' ); ?></h3>
					<p><?php esc_html_e( 'Check back soon.', 'luwipress-onyx' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php
get_footer();
