<?php
/**
 * Generic page — Onyx editorial reading column. Elementor-built pages
 * render their own content full-width.
 *
 * @package luwipress-onyx
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$onyx_pid       = get_queried_object_id();
$onyx_elementor = $onyx_pid && get_post_meta( $onyx_pid, '_elementor_edit_mode', true ) && get_post_meta( $onyx_pid, '_elementor_data', true );

if ( $onyx_elementor ) {
	while ( have_posts() ) { the_post(); the_content(); }
	get_footer();
	return;
}

while ( have_posts() ) :
	the_post();
	?>
	<main>
		<section class="phead">
			<div class="wrap" style="max-width:820px">
				<div class="crumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'luwipress-onyx' ); ?></a><span class="sep">/</span><span><?php the_title(); ?></span></div>
				<h1 class="display-lg" style="margin-top:14px"><?php the_title(); ?></h1>
			</div>
		</section>
		<section class="section" style="padding-top:clamp(24px,3vw,44px)">
			<div class="wrap">
				<article class="article">
					<?php
					if ( has_post_thumbnail() ) {
						echo onyx_ph( array( 'img' => get_the_post_thumbnail_url( get_the_ID(), 'full' ), 'alt' => get_the_title(), 'class' => 'a-hero' ) ); // phpcs:ignore
					}
					the_content();
					wp_link_pages( array( 'before' => '<div class="j-meta" style="margin-top:24px">', 'after' => '</div>' ) );
					?>
				</article>
				<?php
				if ( comments_open() || get_comments_number() ) {
					echo '<div style="max-width:760px;margin:60px auto 0">';
					comments_template();
					echo '</div>';
				}
				?>
			</div>
		</section>
	</main>
	<?php
endwhile;

get_footer();
