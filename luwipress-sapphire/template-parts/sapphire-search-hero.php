<?php
/**
 * Shared search hero — crumb, title, real GET search form, popular chips.
 *
 * @package luwipress-sapphire
 * @var array $args q (current query string)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$sapphire_q = isset( $args['q'] ) ? (string) $args['q'] : '';
$sapphire_suggest = apply_filters( 'luwipress_sapphire_search_suggest', array( 'Pricing', 'API', 'Integrations', 'Security', 'SSO', 'Changelog' ) );
?>
<section class="search-hero">
	<div class="wrap">
		<div class="crumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'luwipress-sapphire' ); ?></a><span class="sep">/</span><span><?php esc_html_e( 'Search', 'luwipress-sapphire' ); ?></span></div>
		<span class="smallcaps" style="color:var(--accent)"><?php esc_html_e( 'Search', 'luwipress-sapphire' ); ?></span>
		<h1 class="display-xl" style="margin-top:14px;font-size:clamp(40px,5.5vw,76px)"><?php esc_html_e( 'What are you looking for?', 'luwipress-sapphire' ); ?></h1>
		<form class="search-box" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" role="search">
			<span class="ic"><?php echo sapphire_icon( 'search', 22 ); // phpcs:ignore ?></span>
			<input type="search" name="s" value="<?php echo esc_attr( $sapphire_q ); ?>" placeholder="<?php esc_attr_e( 'Try “pricing”, “the API”, “integrations”…', 'luwipress-sapphire' ); ?>" aria-label="<?php esc_attr_e( 'Search', 'luwipress-sapphire' ); ?>">
			<button class="btn btn-gold" type="submit"><?php esc_html_e( 'Search', 'luwipress-sapphire' ); ?></button>
		</form>
		<div class="search-chips">
			<span class="sc-l"><?php esc_html_e( 'Popular', 'luwipress-sapphire' ); ?></span>
			<?php foreach ( $sapphire_suggest as $s ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 's', rawurlencode( $s ), home_url( '/' ) ) ); ?>"><?php echo esc_html( $s ); ?></a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php
/**
 * Render WP results (or recent content) as Sapphire `.sresult` rows.
 *
 * @param WP_Query $query   The query to render.
 * @param string   $qstring The current search string (for the meta line).
 */
if ( ! function_exists( 'sapphire_render_search_results' ) ) {
	function sapphire_render_search_results( $query, $qstring = '' ) {
		$glyphs = array( 'interior', 'tower', 'exterior' );
		?>
		<section class="section" style="padding-top:clamp(34px,4vw,56px)">
			<div class="wrap">
				<div class="search-meta">
					<?php if ( $qstring !== '' ) : ?>
						<b><?php echo (int) $query->found_posts; ?></b> <?php echo esc_html( _n( 'result', 'results', (int) $query->found_posts, 'luwipress-sapphire' ) ); ?> <?php esc_html_e( 'for', 'luwipress-sapphire' ); ?> &ldquo;<?php echo esc_html( $qstring ); ?>&rdquo;
					<?php else : ?>
						<?php esc_html_e( 'Showing', 'luwipress-sapphire' ); ?> <b><?php echo (int) $query->post_count; ?></b> <?php esc_html_e( 'results · everything on the site', 'luwipress-sapphire' ); ?>
					<?php endif; ?>
				</div>
				<?php if ( ! $query->have_posts() ) : ?>
					<div class="empty">
						<span class="ic"><?php echo sapphire_icon( 'search', 34 ); // phpcs:ignore ?></span>
						<h3><?php printf( esc_html__( 'Nothing matched “%s”', 'luwipress-sapphire' ), esc_html( $qstring ) ); ?></h3>
						<p><?php esc_html_e( 'Try a broader term, or browse the full collection.', 'luwipress-sapphire' ); ?></p>
						<a class="btn btn-ghost" href="<?php echo esc_url( sapphire_page_url( 'gallery' ) ); ?>" style="margin-top:24px"><?php esc_html_e( 'Open the gallery', 'luwipress-sapphire' ); ?></a>
					</div>
				<?php else : ?>
					<div class="sresults">
						<?php
						$i = 0;
						while ( $query->have_posts() ) : $query->the_post();
							$kind  = ucfirst( get_post_type() );
							$thumb = get_the_post_thumbnail_url( get_the_ID(), 'medium' );
							?>
							<a class="sresult reveal" href="<?php the_permalink(); ?>">
								<div class="sr-media"><?php echo sapphire_ph( array( 'glyph' => $glyphs[ $i % 3 ], 'img' => $thumb ) ); // phpcs:ignore ?></div>
								<div>
									<div class="sr-kind"><?php echo esc_html( $kind ); ?></div>
									<h3><?php the_title(); ?></h3>
									<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 26 ) ); ?></p>
								</div>
								<span class="sr-go"><?php echo sapphire_icon( 'arrowUR', 16 ); // phpcs:ignore ?></span>
							</a>
							<?php $i++;
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}
