<?php
/**
 * Generic post archive — categories, tags, authors, dates.
 * Reuses the journal card markup so any post-archive view picks up the
 * same editorial grid styling as `home.php`. WooCommerce product
 * archives are routed to `woocommerce/archive-product.php` upstream
 * and never reach this template.
 *
 * @package luwipress-gold
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$archive_title = get_the_archive_title();
$archive_desc  = get_the_archive_description();
?>

<main class="lwp-page lwp-journal" id="primary">
	<div class="lwp-page-container">

		<nav class="lwp-crumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'luwipress-gold' ); ?>">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'luwipress-gold' ); ?></a>
			<?php
			$posts_page_id = (int) get_option( 'page_for_posts' );
			if ( $posts_page_id > 0 ) :
				$posts_page_title = get_the_title( $posts_page_id );
				if ( $posts_page_title ) : ?>
					<span class="sep">›</span>
					<a href="<?php echo esc_url( get_permalink( $posts_page_id ) ); ?>"><?php echo esc_html( $posts_page_title ); ?></a>
				<?php endif;
			endif;
			?>
			<span class="sep">›</span>
			<span class="current"><?php echo wp_kses_post( $archive_title ); ?></span>
		</nav>

		<header class="lwp-journal-header">
			<span class="lwp-eyebrow">— <?php
			if ( is_category() ) {
				esc_html_e( 'Category', 'luwipress-gold' );
			} elseif ( is_tag() ) {
				esc_html_e( 'Tag', 'luwipress-gold' );
			} elseif ( is_author() ) {
				esc_html_e( 'Author', 'luwipress-gold' );
			} elseif ( is_date() ) {
				esc_html_e( 'Archive', 'luwipress-gold' );
			} else {
				esc_html_e( 'From the atelier', 'luwipress-gold' );
			}
			?></span>
			<h1 class="lwp-page-title"><?php echo wp_kses_post( $archive_title ); ?></h1>
			<?php if ( $archive_desc ) : ?>
				<div class="lwp-page-lead"><?php echo wp_kses_post( $archive_desc ); ?></div>
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
			$prev = get_previous_posts_link( '← ' . __( 'Newer', 'luwipress-gold' ) );
			$next = get_next_posts_link( __( 'Older', 'luwipress-gold' ) . ' →' );
			if ( $prev || $next ) : ?>
				<nav class="lwp-jnl-pagination" aria-label="<?php esc_attr_e( 'Posts pagination', 'luwipress-gold' ); ?>">
					<span class="lwp-jnl-pagination__prev"><?php echo $prev ?: '&nbsp;'; // phpcs:ignore ?></span>
					<span class="lwp-jnl-pagination__pages">
						<?php
						global $wp_query;
						$total   = (int) $wp_query->max_num_pages;
						$current = max( 1, get_query_var( 'paged' ) );
						/* translators: 1: current page 2: total pages */
						printf( esc_html__( 'Page %1$d of %2$d', 'luwipress-gold' ), $current, $total );
						?>
					</span>
					<span class="lwp-jnl-pagination__next"><?php echo $next ?: '&nbsp;'; // phpcs:ignore ?></span>
				</nav>
			<?php endif; ?>

		<?php else : ?>

			<div class="lwp-jnl-empty">
				<p><?php esc_html_e( 'Nothing here yet — try another category or come back soon.', 'luwipress-gold' ); ?></p>
			</div>

		<?php endif; ?>

	</div>
</main>

<?php
get_footer();
