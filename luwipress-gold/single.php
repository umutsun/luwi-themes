<?php
/**
 * Single post template — branches on whether the post is built with
 * Elementor. See page.php for the full reasoning; same pattern.
 *
 * Elementor-built posts (e.g. journal articles laid out in Elementor)
 * render full-width with their own internal chrome. Plain posts get
 * our editorial fallback (centered title + reading column).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$post_id        = get_queried_object_id();
$is_el_built    = $post_id && (bool) get_post_meta( $post_id, '_elementor_edit_mode', true );
$has_el_data    = $post_id && (bool) get_post_meta( $post_id, '_elementor_data', true );
$elementor_post = $is_el_built && $has_el_data;
?>

<?php if ( $elementor_post ) : ?>

	<main class="lwp-elementor-page" id="primary">
		<?php while ( have_posts() ) : the_post(); ?>
			<?php the_content(); ?>
			<?php wp_link_pages(); ?>
			<?php if ( comments_open() || get_comments_number() ) : ?>
				<div class="lwp-page-container" style="padding-top:64px;padding-bottom:64px;"><?php comments_template(); ?></div>
			<?php endif; ?>
		<?php endwhile; ?>
	</main>

<?php else : ?>

	<main class="lwp-page lwp-journal-single" id="primary">
		<?php while ( have_posts() ) : the_post();
			$cats = get_the_category();
			$primary = ! empty( $cats ) ? $cats[0] : null;
			$author_id = get_the_author_meta( 'ID' );
			$reading_time = max( 1, (int) round( str_word_count( wp_strip_all_tags( get_the_content() ) ) / 220 ) );
			?>

			<article <?php post_class( 'lwp-journal-article' ); ?>>

				<div class="lwp-page-container">
					<nav class="lwp-crumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'luwipress-gold' ); ?>">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'luwipress-gold' ); ?></a>
						<?php $blog_id = (int) get_option( 'page_for_posts' );
						if ( $blog_id ) : ?>
							<span class="sep">›</span>
							<a href="<?php echo esc_url( get_permalink( $blog_id ) ); ?>"><?php echo esc_html( get_the_title( $blog_id ) ?: __( 'Journal', 'luwipress-gold' ) ); ?></a>
						<?php endif; ?>
						<span class="sep">›</span>
						<span class="current"><?php the_title(); ?></span>
					</nav>

					<header class="lwp-journal-head">
						<?php if ( $primary ) : ?>
							<span class="lwp-eyebrow">— <?php echo esc_html( $primary->name ); ?></span>
						<?php endif; ?>
						<h1 class="lwp-page-title"><?php the_title(); ?></h1>
						<div class="lwp-journal-meta">
							<span class="lwp-journal-meta__author"><?php
								/* translators: %s: author display name */
								printf( esc_html__( 'By %s', 'luwipress-gold' ), esc_html( get_the_author_meta( 'display_name', $author_id ) ) );
							?></span>
							<span class="sep">·</span>
							<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
							<span class="sep">·</span>
							<span><?php
								/* translators: %d: reading time in minutes */
								printf( esc_html( _n( '%d min read', '%d min read', $reading_time, 'luwipress-gold' ) ), $reading_time );
							?></span>
						</div>
					</header>
				</div>

				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="lwp-journal-cover">
						<?php the_post_thumbnail( 'full', [ 'class' => 'lwp-journal-cover__img' ] ); ?>
						<?php $caption = get_the_post_thumbnail_caption(); if ( $caption ) : ?>
							<figcaption><?php echo esc_html( $caption ); ?></figcaption>
						<?php endif; ?>
					</figure>
				<?php endif; ?>

				<div class="lwp-page-container">
					<div class="lwp-journal-body">
						<?php the_content(); ?>
						<?php wp_link_pages(); ?>
					</div>

					<?php $tags = get_the_tags();
					if ( $tags && ! is_wp_error( $tags ) ) : ?>
						<div class="lwp-journal-tags">
							<span class="lwp-journal-tags__label"><?php esc_html_e( 'Tagged', 'luwipress-gold' ); ?></span>
							<?php foreach ( $tags as $tag ) : ?>
								<a href="<?php echo esc_url( get_tag_link( $tag ) ); ?>" class="lwp-journal-tag">#<?php echo esc_html( $tag->name ); ?></a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<footer class="lwp-journal-foot">
						<?php
						$prev = get_previous_post();
						$next = get_next_post();
						?>
						<?php if ( $prev ) : ?>
							<a class="lwp-journal-foot__prev" href="<?php echo esc_url( get_permalink( $prev ) ); ?>">
								<small><?php esc_html_e( '← Previous', 'luwipress-gold' ); ?></small>
								<strong><?php echo esc_html( $prev->post_title ); ?></strong>
							</a>
						<?php endif; ?>
						<?php if ( $next ) : ?>
							<a class="lwp-journal-foot__next" href="<?php echo esc_url( get_permalink( $next ) ); ?>">
								<small><?php esc_html_e( 'Next →', 'luwipress-gold' ); ?></small>
								<strong><?php echo esc_html( $next->post_title ); ?></strong>
							</a>
						<?php endif; ?>
					</footer>

					<?php if ( comments_open() || get_comments_number() ) : ?>
						<div class="lwp-journal-comments"><?php comments_template(); ?></div>
					<?php endif; ?>
				</div>

			</article>
		<?php endwhile; ?>
	</main>

<?php endif; ?>

<?php
get_footer();
