<?php
/**
 * Single article (onyx-page-article.jsx): article hero + reading column +
 * author byline + "more from the Journal" related grid. Elementor-built
 * posts render their own content full-width.
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

$onyx_journal = onyx_page_url( 'journal' );
$onyx_contact = onyx_page_url( 'contact' );

while ( have_posts() ) :
	the_post();
	$cats   = get_the_category();
	$cat    = ! empty( $cats ) ? $cats[0]->name : __( 'Journal', 'luwipress-onyx' );
	$thumb  = get_the_post_thumbnail_url( get_the_ID(), 'full' );
	$author = get_the_author();
	$ico    = strtoupper( mb_substr( $author, 0, 1 ) . ( preg_match( '/\s(\S)/u', $author, $m ) ? $m[1] : '' ) );
	?>

	<main>
		<section class="phead">
			<div class="wrap" style="max-width:820px">
				<div class="crumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'luwipress-onyx' ); ?></a><span class="sep">/</span><a href="<?php echo esc_url( $onyx_journal ); ?>"><?php esc_html_e( 'Journal', 'luwipress-onyx' ); ?></a><span class="sep">/</span><span><?php echo esc_html( $cat ); ?></span></div>
				<span class="smallcaps" style="color:var(--gold)"><?php echo esc_html( $cat ); ?></span>
				<h1 class="display-lg" style="margin-top:14px"><?php the_title(); ?></h1>
				<div class="j-meta" style="margin-top:24px">
					<span><?php printf( esc_html__( 'By %s', 'luwipress-onyx' ), esc_html( $author ) ); ?></span><span>·</span><span><?php echo esc_html( get_the_date() ); ?></span><span>·</span><span><?php echo esc_html( sprintf( _n( '%d min read', '%d min read', onyx_reading_time(), 'luwipress-onyx' ), onyx_reading_time() ) ); ?></span>
				</div>
			</div>
		</section>

		<section class="section" style="padding-top:clamp(20px,3vw,40px)">
			<div class="wrap">
				<article class="article">
					<?php if ( $thumb ) : ?>
						<div class="a-hero"><?php echo onyx_ph( array( 'img' => $thumb, 'alt' => get_the_title() ) ); // phpcs:ignore ?></div>
					<?php else : ?>
						<?php echo onyx_ph( array( 'glyph' => 'tower', 'class' => 'a-hero' ) ); // phpcs:ignore ?>
					<?php endif; ?>

					<?php the_content(); ?>
					<?php wp_link_pages( array( 'before' => '<div class="j-meta" style="margin-top:24px">', 'after' => '</div>' ) ); ?>

					<div class="hair-gold" style="margin:48px 0;width:60px"></div>
					<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px">
						<div style="display:flex;align-items:center;gap:16px">
							<span class="tst-av" style="display:grid;place-items:center;font-family:var(--display);font-size:20px;color:var(--gold);width:54px;height:54px;border-radius:50%;border:1px solid var(--hair)"><?php echo esc_html( $ico ); ?></span>
							<div><div style="font-family:var(--display);font-size:20px;color:var(--ink)"><?php echo esc_html( $author ); ?></div><div class="tst-role"><?php echo esc_html( get_the_author_meta( 'description' ) ? wp_trim_words( get_the_author_meta( 'description' ), 6 ) : __( 'ArshaHomes Advisor', 'luwipress-onyx' ) ); ?></div></div>
						</div>
						<a class="btn btn-gold" href="<?php echo esc_url( $onyx_contact ); ?>"><?php esc_html_e( 'Speak with an advisor', 'luwipress-onyx' ); ?> <span class="arr"><?php echo onyx_icon( 'arrow', 16 ); // phpcs:ignore ?></span></a>
					</div>
				</article>
			</div>
		</section>

		<?php
		// Related — same category, exclude current.
		$onyx_related = new WP_Query( array(
			'posts_per_page'      => 3,
			'post__not_in'        => array( get_the_ID() ),
			'category__in'        => wp_list_pluck( $cats, 'term_id' ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		) );
		if ( ! $onyx_related->have_posts() ) {
			$onyx_related = new WP_Query( array( 'posts_per_page' => 3, 'post__not_in' => array( get_the_ID() ), 'ignore_sticky_posts' => true, 'no_found_rows' => true ) );
		}
		if ( $onyx_related->have_posts() ) : ?>
			<section class="section" style="background:var(--onyx-850);border-top:1px solid var(--hair-soft)">
				<div class="wrap">
					<div class="sec-head"><div class="sh-title">
						<span class="reveal" style="display:block"><?php echo onyx_eyebrow( __( 'Keep Reading', 'luwipress-onyx' ) ); // phpcs:ignore ?></span>
						<h2 class="display-lg reveal"><?php esc_html_e( 'More from the Journal', 'luwipress-onyx' ); ?></h2>
					</div>
					<a class="sh-link reveal" href="<?php echo esc_url( $onyx_journal ); ?>"><?php esc_html_e( 'All articles', 'luwipress-onyx' ); ?> <?php echo onyx_icon( 'arrow', 15 ); // phpcs:ignore ?></a>
					</div>
					<div class="news-grid">
						<?php
						$rg = array( 'interior', 'exterior', 'tower' );
						$ri = 0;
						while ( $onyx_related->have_posts() ) : $onyx_related->the_post();
							$rcat   = get_the_category();
							$rcatn  = ! empty( $rcat ) ? $rcat[0]->name : __( 'Journal', 'luwipress-onyx' );
							$rthumb = get_the_post_thumbnail_url( get_the_ID(), 'large' );
							?>
							<article class="ncard reveal">
								<a href="<?php the_permalink(); ?>" style="display:block;color:inherit">
									<div class="ncard-media"><?php echo onyx_ph( array( 'glyph' => $rg[ $ri % 3 ], 'img' => $rthumb ) ); // phpcs:ignore ?><div class="ncard-date"><div class="d tnum"><?php echo esc_html( get_the_date( 'd' ) ); ?></div><div class="m"><?php echo esc_html( get_the_date( 'M' ) ); ?></div></div></div>
									<div class="ncard-cat"><?php echo esc_html( $rcatn ); ?></div>
									<h3><?php the_title(); ?></h3>
									<span class="ncard-more"><?php esc_html_e( 'Read more', 'luwipress-onyx' ); ?> <?php echo onyx_icon( 'arrow', 14 ); // phpcs:ignore ?></span>
								</a>
							</article>
							<?php $ri++;
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php
		if ( comments_open() || get_comments_number() ) :
			?>
			<section class="section"><div class="wrap" style="max-width:820px"><?php comments_template(); ?></div></section>
			<?php
		endif;
		?>
	</main>

<?php
endwhile;

get_footer();
