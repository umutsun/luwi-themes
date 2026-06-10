<?php
/**
 * Generic archive — categories, tags, authors, dates. Sapphire journal-style
 * grid of `.ncard` posts.
 *
 * @package luwipress-sapphire
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$sapphire_glyphs = array( 'tower', 'interior', 'exterior' );
?>
<main>
	<section class="phead">
		<div class="wrap">
			<div class="crumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'luwipress-sapphire' ); ?></a><span class="sep">/</span><span><?php echo esc_html( wp_strip_all_tags( get_the_archive_title() ) ); ?></span></div>
			<span class="smallcaps" style="color:var(--accent)"><?php esc_html_e( 'The Journal', 'luwipress-sapphire' ); ?></span>
			<h1 class="display-xl" style="margin-top:14px;font-size:clamp(40px,5.5vw,76px)"><?php echo esc_html( wp_strip_all_tags( get_the_archive_title() ) ); ?></h1>
			<?php $sapphire_desc = get_the_archive_description(); if ( $sapphire_desc ) : ?>
				<div class="phead-sub"><?php echo wp_kses_post( $sapphire_desc ); ?></div>
			<?php endif; ?>
		</div>
	</section>

	<section class="section" style="padding-top:clamp(40px,5vw,64px)">
		<div class="wrap">
			<?php if ( have_posts() ) : ?>
				<div class="news-grid">
					<?php
					$sapphire_i = 0;
					while ( have_posts() ) : the_post();
						$cats  = get_the_category();
						$cat   = ! empty( $cats ) ? $cats[0]->name : __( 'Journal', 'luwipress-sapphire' );
						$thumb = get_the_post_thumbnail_url( get_the_ID(), 'large' );
						?>
						<article class="ncard reveal">
							<a href="<?php the_permalink(); ?>" style="display:block;color:inherit">
								<div class="ncard-media"><?php echo sapphire_ph( array( 'glyph' => $sapphire_glyphs[ $sapphire_i % 3 ], 'img' => $thumb ) ); // phpcs:ignore ?><div class="ncard-date"><div class="d tnum"><?php echo esc_html( get_the_date( 'd' ) ); ?></div><div class="m"><?php echo esc_html( get_the_date( 'M' ) ); ?></div></div></div>
								<div class="ncard-cat"><?php echo esc_html( $cat ); ?></div>
								<h3><?php the_title(); ?></h3>
								<span class="ncard-more"><?php esc_html_e( 'Read more', 'luwipress-sapphire' ); ?> <?php echo sapphire_icon( 'arrow', 14 ); // phpcs:ignore ?></span>
							</a>
						</article>
						<?php $sapphire_i++;
					endwhile; ?>
				</div>
				<?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => '‹', 'next_text' => '›' ) ); ?>
			<?php else : ?>
				<div class="empty">
					<span class="ic"><?php echo sapphire_icon( 'search', 34 ); // phpcs:ignore ?></span>
					<h3><?php esc_html_e( 'Nothing here yet', 'luwipress-sapphire' ); ?></h3>
					<p><?php esc_html_e( 'No articles in this archive.', 'luwipress-sapphire' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php
get_footer();
