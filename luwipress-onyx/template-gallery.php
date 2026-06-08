<?php
/**
 * Template Name: Onyx — Gallery
 *
 * Category-filtered collection grid (demo residences). The chip filter is
 * progressive-enhancement: every card is server-rendered, onyx.js just
 * shows/hides on chip click. Yields to Elementor when built with it.
 *
 * @package luwipress-onyx
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$onyx_pid = get_queried_object_id();
if ( $onyx_pid && get_post_meta( $onyx_pid, '_elementor_edit_mode', true ) && get_post_meta( $onyx_pid, '_elementor_data', true ) ) {
	get_header();
	while ( have_posts() ) { the_post(); the_content(); }
	get_footer();
	return;
}

get_header();

$onyx_listings = onyx_page_url( 'listings' );
$onyx_prop_url = onyx_page_url( 'property' );

$onyx_listings_data = apply_filters( 'luwipress_onyx_gallery_data', array(
	array( 'Penthouse', 'The Onyx Penthouse', 'Downtown', '4 Bed', '412 m²', 'AED 14.8M', 'interior' ),
	array( 'Duplex', 'Marsa Duplex 41', 'Business Bay', '3 Bed', '268 m²', 'AED 6.9M', 'tower' ),
	array( 'Studio', 'Atelier Studio', 'DIFC', 'Studio', '54 m²', 'AED 1.2M', 'exterior' ),
	array( 'Business', 'Bay Office Loft', 'Business Bay', 'Loft', '120 m²', 'AED 3.1M', 'interior' ),
	array( 'Apartment', 'Skyframe Two-Bed', 'Dubai Marina', '2 Bed', '146 m²', 'AED 2.4M', 'tower' ),
	array( 'Villa', 'Palm Shore Villa', 'Palm Jumeirah', '5 Bed', '640 m²', 'AED 28M', 'exterior' ),
	array( 'Penthouse', 'Creek Sky Penthouse', 'Dubai Creek', '4 Bed', '388 m²', 'AED 12.2M', 'tower' ),
	array( 'Apartment', 'Garden Residence 12', 'City Walk', '2 Bed', '132 m²', 'AED 2.9M', 'interior' ),
	array( 'Studio', 'Pier Studio', 'Dubai Marina', 'Studio', '48 m²', 'AED 1.05M', 'exterior' ),
	array( 'Duplex', 'Boulevard Duplex', 'Downtown', '3 Bed', '244 m²', 'AED 7.4M', 'interior' ),
	array( 'Villa', 'Hills Estate 04', 'Dubai Hills', '6 Bed', '820 m²', 'AED 34M', 'exterior' ),
	array( 'Apartment', 'Frame One-Bed', 'Business Bay', '1 Bed', '78 m²', 'AED 1.6M', 'tower' ),
) );
$onyx_cats = array( 'All', 'Penthouse', 'Duplex', 'Apartment', 'Studio', 'Villa', 'Business' );
?>

<main>
	<section class="phead">
		<div class="wrap">
			<div class="crumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'luwipress-onyx' ); ?></a><span class="sep">/</span><span><?php esc_html_e( 'Gallery', 'luwipress-onyx' ); ?></span></div>
			<span class="smallcaps" style="color:var(--gold)"><?php esc_html_e( 'Apartments & Complex', 'luwipress-onyx' ); ?></span>
			<h1 class="display-xl" style="margin-top:14px;font-size:clamp(44px,6vw,84px)"><?php esc_html_e( 'The Collection', 'luwipress-onyx' ); ?></h1>
			<p class="phead-sub"><?php esc_html_e( "A curated portfolio across Dubai's most considered addresses. Filter by typology, or speak with an advisor for the residences we never list.", 'luwipress-onyx' ); ?></p>
		</div>
	</section>

	<section class="section" style="padding-top:clamp(40px,5vw,64px)">
		<div class="wrap" data-onyx-gallery>
			<div class="filters">
				<?php foreach ( $onyx_cats as $i => $c ) : ?>
					<button type="button" class="<?php echo 0 === $i ? 'on' : ''; ?>" data-gal-cat="<?php echo esc_attr( $c ); ?>"><?php echo esc_html( $c ); ?></button>
				<?php endforeach; ?>
				<span class="count" data-gal-count><?php echo count( $onyx_listings_data ); ?> <?php esc_html_e( 'residences', 'luwipress-onyx' ); ?></span>
			</div>
			<div class="listing-grid">
				<?php foreach ( $onyx_listings_data as $p ) : ?>
					<a class="pcard reveal" href="<?php echo esc_url( $onyx_prop_url ); ?>" data-card-cat="<?php echo esc_attr( $p[0] ); ?>">
						<div class="pcard-media"><span class="pcard-cat"><?php echo esc_html( $p[0] ); ?></span><?php echo onyx_ph( array( 'glyph' => $p[6] ) ); // phpcs:ignore ?></div>
						<div class="pcard-body">
							<div class="pcard-loc"><span class="ic"><?php echo onyx_icon( 'pin', 13 ); // phpcs:ignore ?></span><?php echo esc_html( $p[2] ); ?></div>
							<h3><?php echo esc_html( $p[1] ); ?></h3>
							<div class="pcard-meta">
								<span><span class="ic"><?php echo onyx_icon( 'bed', 15 ); // phpcs:ignore ?></span><?php echo esc_html( $p[3] ); ?></span>
								<span><span class="ic"><?php echo onyx_icon( 'ruler', 15 ); // phpcs:ignore ?></span><?php echo esc_html( $p[4] ); ?></span>
							</div>
							<div class="pcard-foot">
								<div class="pcard-price"><small><?php esc_html_e( 'Price from', 'luwipress-onyx' ); ?></small><?php echo esc_html( $p[5] ); ?></div>
								<span class="pcard-arrow"><?php echo onyx_icon( 'arrowUR', 16 ); // phpcs:ignore ?></span>
							</div>
						</div>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
