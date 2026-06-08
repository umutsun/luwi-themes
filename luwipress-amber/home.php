<?php
/**
 * Journal listing — used when a static page is set as Posts page
 * (Settings → Reading → "Posts page"). Mirrors the reference
 * `Tapadum-Journal.html`: 1 big featured first post + 3-col grid for
 * the rest. Pulls eyebrow text from the post's primary category and
 * computes reading time from word count.
 *
 * Falls through to default WP loop semantics — pagination, sticky
 * posts, and category filters all keep working.
 *
 * @package luwipress-amber
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$blog_title = get_the_title( (int) get_option( 'page_for_posts' ) );
if ( ! $blog_title ) {
	$blog_title = __( 'Journal', 'luwipress-amber' );
}
$blog_subtitle = (string) get_theme_mod(
	'luwipress_amber_journal_subtitle',
	__( 'Field notes, workshop visits and long reads from the atelier.', 'luwipress-amber' )
);
?>

<main class="lwp-page lwp-journal" id="primary">
	<div class="lwp-page-container">

		<nav class="lwp-crumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'luwipress-amber' ); ?>">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'luwipress-amber' ); ?></a>
			<span class="sep">›</span>
			<span class="current"><?php echo esc_html( $blog_title ); ?></span>
		</nav>

		<header class="lwp-journal-header">
			<span class="lwp-eyebrow">— <?php esc_html_e( 'Long reads from the atelier', 'luwipress-amber' ); ?></span>
			<h1 class="lwp-page-title"><?php
				$first_word = explode( ' ', $blog_title )[0] ?? $blog_title;
				$rest       = trim( str_replace( $first_word, '', $blog_title ) );
				if ( $rest ) {
					echo esc_html( $first_word ) . ' <em>' . esc_html( $rest ) . '</em>';
				} else {
					echo esc_html( $blog_title );
				}
			?>.</h1>
			<?php if ( $blog_subtitle ) : ?>
				<p class="lwp-page-lead"><?php echo esc_html( $blog_subtitle ); ?></p>
			<?php endif; ?>
		</header>

		<?php if ( have_posts() ) : ?>

			<div class="lwp-jnl-grid">
				<?php
				$index = 0;
				while ( have_posts() ) :
					the_post();
					$index++;
					get_template_part( 'template-parts/journal-card', null, [
						'index'    => $index,
						'is_paged' => is_paged(),
					] );
				endwhile;
				?>
			</div>

			<?php
			// Pagination — Elementor's stylesheet often hides the default
			// .nav-links output, so we wrap with our own pill class.
			$prev = get_previous_posts_link( '← ' . __( 'Newer', 'luwipress-amber' ) );
			$next = get_next_posts_link( __( 'Older', 'luwipress-amber' ) . ' →' );
			if ( $prev || $next ) : ?>
				<nav class="lwp-jnl-pagination" aria-label="<?php esc_attr_e( 'Posts pagination', 'luwipress-amber' ); ?>">
					<span class="lwp-jnl-pagination__prev"><?php echo $prev ?: '&nbsp;'; // phpcs:ignore ?></span>
					<span class="lwp-jnl-pagination__pages">
						<?php
						global $wp_query;
						$total   = (int) $wp_query->max_num_pages;
						$current = max( 1, get_query_var( 'paged' ) );
						/* translators: 1: current page 2: total pages */
						printf( esc_html__( 'Page %1$d of %2$d', 'luwipress-amber' ), $current, $total );
						?>
					</span>
					<span class="lwp-jnl-pagination__next"><?php echo $next ?: '&nbsp;'; // phpcs:ignore ?></span>
				</nav>
			<?php endif; ?>

		<?php else : ?>

			<div class="lwp-jnl-empty">
				<p><?php esc_html_e( 'No posts yet — the atelier is still drafting.', 'luwipress-amber' ); ?></p>
			</div>

		<?php endif; ?>

	</div>
</main>

<?php
get_footer();
