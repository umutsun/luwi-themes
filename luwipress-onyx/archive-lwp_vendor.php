<?php
/**
 * Archive Vendor template (CPT `lwp_vendor`).
 *
 * The "Our Luthiers / Chefs / Artists / Team" index page. Grid of all
 * published Vendor profiles; each tile links to /<archive>/<slug>/ single
 * profile.
 *
 * Labels come from LuwiPress Vendors settings — singular_label / plural_label.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$singular = class_exists( 'LuwiPress_Vendors' )
	? (string) LuwiPress_Vendors::get_setting( 'singular_label' )
	: __( 'Person', 'luwipress-onyx' );
$plural = class_exists( 'LuwiPress_Vendors' )
	? (string) LuwiPress_Vendors::get_setting( 'plural_label' )
	: __( 'People', 'luwipress-onyx' );
?>

<main class="lwp-people-archive" id="primary">

	<header class="lwp-people-archive__header">
		<p class="lwp-people-archive__eyebrow">
			<?php
			/* translators: Eyebrow on the archive — "the hands behind the work" */
			esc_html_e( '— The hands behind the work', 'luwipress-onyx' );
			?>
		</p>
		<h1 class="lwp-people-archive__heading">
			<?php echo esc_html( $plural ); ?>
		</h1>
		<?php
		$archive_description = get_the_archive_description();
		if ( $archive_description ) :
			?>
			<div class="lwp-people-archive__lead">
				<?php echo wp_kses_post( $archive_description ); ?>
			</div>
		<?php endif; ?>
	</header>

	<?php if ( have_posts() ) : ?>
		<ul class="lwp-people-archive__grid">
			<?php while ( have_posts() ) : the_post();
				$vendor_id  = get_the_ID();
				$image_url  = get_the_post_thumbnail_url( $vendor_id, 'large' );
				$location   = (string) get_post_meta( $vendor_id, '_lwp_vendor_location',   true );
				$specialty  = (string) get_post_meta( $vendor_id, '_lwp_vendor_specialty',  true );
				$years      = (int)    get_post_meta( $vendor_id, '_lwp_vendor_years_active', true );
				$permalink  = get_permalink( $vendor_id );
				?>
				<li class="lwp-people-card" itemscope itemtype="https://schema.org/Person">
					<a href="<?php echo esc_url( $permalink ); ?>" class="lwp-people-card__link">
						<?php if ( $image_url ) : ?>
							<div class="lwp-people-card__portrait">
								<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php the_title_attribute(); ?>" itemprop="image" />
							</div>
						<?php else : ?>
							<div class="lwp-people-card__portrait lwp-people-card__portrait--initials">
								<span><?php echo esc_html( mb_substr( get_the_title( $vendor_id ), 0, 1 ) ); ?></span>
							</div>
						<?php endif; ?>

						<div class="lwp-people-card__body">
							<h2 class="lwp-people-card__name" itemprop="name">
								<?php the_title(); ?>
							</h2>
							<?php if ( $location ) : ?>
								<span class="lwp-people-card__location" itemprop="homeLocation">
									<?php echo esc_html( $location ); ?>
								</span>
							<?php endif; ?>
							<?php if ( $specialty ) : ?>
								<span class="lwp-people-card__specialty" itemprop="knowsAbout">
									<?php echo esc_html( $specialty ); ?>
								</span>
							<?php endif; ?>
							<?php if ( $years > 0 ) : ?>
								<span class="lwp-people-card__years">
									<?php
									echo esc_html( sprintf(
										/* translators: %d: number of years active */
										_n( '%d year', '%d years', $years, 'luwipress-onyx' ),
										$years
									) );
									?>
								</span>
							<?php endif; ?>
						</div>
					</a>
				</li>
			<?php endwhile; ?>
		</ul>

		<?php
		// Native WP pagination — kept in the DOM (and crawlable) for SEO. When
		// load-more is active it is hidden via CSS and driven by loadmore.js.
		the_posts_pagination( array(
			'mid_size'  => 1,
			'prev_text' => __( '← Previous', 'luwipress-onyx' ),
			'next_text' => __( 'Next →', 'luwipress-onyx' ),
		) );

		// Infinite scroll / load-more over the real paginated links above.
		if ( function_exists( 'lwp_onyx_loadmore_render' ) ) {
			lwp_onyx_loadmore_render( array(
				'grid'       => '.lwp-people-archive__grid',
				'pagination' => '.pagination',
				'next'       => '.pagination a.next',
			) );
		}
		?>
	<?php else : ?>
		<p class="lwp-people-archive__empty">
			<?php
			echo esc_html( sprintf(
				/* translators: %s: plural label */
				__( 'No %s yet.', 'luwipress-onyx' ),
				strtolower( $plural )
			) );
			?>
		</p>
	<?php endif; ?>

</main>

<?php
get_footer();
