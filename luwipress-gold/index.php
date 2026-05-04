<?php
/**
 * Generic fallback (blog index, archives, search results, plain post lists).
 * Operators using the Elementor Kit will replace these with the
 * `08-journal.json` template (single post) and a Posts widget for the index.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();
?>
<main class="lwp-fallback-main" id="primary">
	<header class="lwp-fallback-archive__head">
		<h1>
			<?php
			if ( is_search() ) {
				/* translators: %s: search query */
				printf( esc_html__( 'Search results for: %s', 'luwipress-gold' ), '<em>' . esc_html( get_search_query() ) . '</em>' );
			} elseif ( is_archive() ) {
				the_archive_title();
			} else {
				bloginfo( 'name' );
			}
			?>
		</h1>
		<?php if ( is_archive() && get_the_archive_description() ) : ?>
			<div class="lwp-fallback-archive__desc"><?php echo wp_kses_post( get_the_archive_description() ); ?></div>
		<?php endif; ?>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="lwp-fallback-list">
			<?php while ( have_posts() ) : the_post(); ?>
				<article class="lwp-fallback-card">
					<?php if ( has_post_thumbnail() ) : ?>
						<a class="lwp-fallback-card__thumb" href="<?php the_permalink(); ?>">
							<?php the_post_thumbnail( 'luwipress-gold-editorial' ); ?>
						</a>
					<?php endif; ?>
					<div class="lwp-fallback-card__body">
						<h2 class="lwp-fallback-card__title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h2>
						<p class="lwp-fallback-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 28 ) ); ?></p>
						<span class="lwp-fallback-card__meta"><?php echo esc_html( get_the_date() ); ?></span>
					</div>
				</article>
			<?php endwhile; ?>
		</div>

		<?php
		the_posts_pagination( [
			'mid_size'  => 1,
			'prev_text' => __( '← Previous', 'luwipress-gold' ),
			'next_text' => __( 'Next →', 'luwipress-gold' ),
		] );
		?>
	<?php else : ?>
		<p><?php esc_html_e( 'Nothing here yet.', 'luwipress-gold' ); ?></p>
	<?php endif; ?>
</main>
<?php
get_footer();
