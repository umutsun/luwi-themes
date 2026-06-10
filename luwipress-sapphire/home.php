<?php
/**
 * Journal (blog) index — the WordPress posts page. Reproduces
 * sapphire-page-journal.jsx: a big featured first post + a 3-up grid for the
 * rest, in the Sapphire `.ncard` / `.j-feature` style.
 *
 * @package luwipress-sapphire
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$sapphire_blog_id  = (int) get_option( 'page_for_posts' );
$sapphire_title    = $sapphire_blog_id ? get_the_title( $sapphire_blog_id ) : __( 'The Journal', 'luwipress-sapphire' );
$sapphire_subtitle = (string) get_theme_mod( 'luwipress_sapphire_journal_subtitle', __( 'Product updates, engineering deep-dives, and lessons from teams shipping with Sapphire.', 'luwipress-sapphire' ) );
$sapphire_glyphs   = array( 'tower', 'interior', 'exterior' );
?>

<main>
	<section class="phead">
		<div class="wrap">
			<div class="crumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'luwipress-sapphire' ); ?></a><span class="sep">/</span><span><?php echo esc_html( $sapphire_title ); ?></span></div>
			<span class="smallcaps" style="color:var(--accent)"><?php esc_html_e( "What's Happening", 'luwipress-sapphire' ); ?></span>
			<h1 class="display-xl" style="margin-top:14px;font-size:clamp(44px,6vw,84px)"><?php echo esc_html( $sapphire_title ); ?></h1>
			<p class="phead-sub"><?php echo esc_html( $sapphire_subtitle ); ?></p>
		</div>
	</section>

	<section class="section" style="padding-top:clamp(40px,5vw,64px)">
		<div class="wrap">
			<?php if ( have_posts() ) : ?>
				<?php
				$sapphire_i = 0;
				while ( have_posts() ) : the_post();
					$cats  = get_the_category();
					$cat   = ! empty( $cats ) ? $cats[0]->name : __( 'Journal', 'luwipress-sapphire' );
					$thumb = get_the_post_thumbnail_url( get_the_ID(), 'large' );
					$glyph = $sapphire_glyphs[ $sapphire_i % 3 ];

					if ( 0 === $sapphire_i && ! is_paged() ) :
						// Featured first post.
						?>
						<div class="j-feature reveal">
							<a href="<?php the_permalink(); ?>" style="display:block"><?php echo sapphire_ph( array( 'glyph' => $glyph, 'img' => $thumb, 'tag' => __( 'Featured', 'luwipress-sapphire' ) ) ); // phpcs:ignore ?></a>
							<div>
								<span class="j-cat"><?php echo esc_html( $cat ); ?></span>
								<h2 class="display-lg"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
								<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 36 ) ); ?></p>
								<div class="j-meta"><span><?php echo esc_html( get_the_date() ); ?></span><span>·</span><span><?php echo esc_html( sprintf( _n( '%d min read', '%d min read', sapphire_reading_time(), 'luwipress-sapphire' ), sapphire_reading_time() ) ); ?></span></div>
								<a class="btn btn-ghost" href="<?php the_permalink(); ?>" style="margin-top:28px"><?php esc_html_e( 'Read article', 'luwipress-sapphire' ); ?> <span class="arr"><?php echo sapphire_icon( 'arrow', 16 ); // phpcs:ignore ?></span></a>
							</div>
						</div>
						<div class="news-grid">
						<?php
					else :
						if ( 0 === $sapphire_i ) {
							echo '<div class="news-grid">';
						}
						?>
						<article class="ncard reveal">
							<a href="<?php the_permalink(); ?>" style="display:block;color:inherit">
								<div class="ncard-media">
									<?php echo sapphire_ph( array( 'glyph' => $glyph, 'img' => $thumb ) ); // phpcs:ignore ?>
									<div class="ncard-date"><div class="d tnum"><?php echo esc_html( get_the_date( 'd' ) ); ?></div><div class="m"><?php echo esc_html( get_the_date( 'M' ) ); ?></div></div>
								</div>
								<div class="ncard-cat"><?php echo esc_html( $cat ); ?></div>
								<h3><?php the_title(); ?></h3>
								<span class="ncard-more"><?php esc_html_e( 'Read more', 'luwipress-sapphire' ); ?> <?php echo sapphire_icon( 'arrow', 14 ); // phpcs:ignore ?></span>
							</a>
						</article>
						<?php
					endif;
					$sapphire_i++;
				endwhile;
				?>
				</div><!-- /.news-grid -->

				<?php
				the_posts_pagination( array(
					'mid_size'  => 1,
					'prev_text' => '‹',
					'next_text' => '›',
					'class'     => 'pager',
				) );
				?>
			<?php else : ?>
				<div class="empty">
					<span class="ic"><?php echo sapphire_icon( 'search', 34 ); // phpcs:ignore ?></span>
					<h3><?php esc_html_e( 'No articles yet', 'luwipress-sapphire' ); ?></h3>
					<p><?php esc_html_e( 'The Journal is being written. Check back soon.', 'luwipress-sapphire' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>

<?php
get_footer();
