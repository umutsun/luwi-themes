<?php
/**
 * Template Name: Onyx — Listings / Search
 *
 * Advanced property listings: filter sidebar (price dual-range, type,
 * bedrooms, location, status), sort, grid/list toggle, favourites,
 * pagination + mobile filter drawer. Cards are rendered client-side by
 * onyx.js from a JSON data island (demo residences — properties stay as
 * normal content for now; a CPT can back this later). Yields to Elementor
 * when the page is built with it.
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

/**
 * Demo residence index. Filterable so an operator can replace it with real
 * data (e.g. from a CPT) via `luwipress_onyx_listings_data`.
 */
$onyx_data = apply_filters( 'luwipress_onyx_listings_data', array(
	array( 'cat' => 'Penthouse', 'name' => 'The Onyx Penthouse', 'loc' => 'Downtown', 'beds' => 4, 'area' => 412, 'price' => 14800000, 'status' => 'Ready', 'glyph' => 'interior', 'desc' => 'A triplex residence at the summit of Burj Vista, facing the fountain.' ),
	array( 'cat' => 'Duplex', 'name' => 'Marsa Duplex 41', 'loc' => 'Business Bay', 'beds' => 3, 'area' => 268, 'price' => 6900000, 'status' => 'Ready', 'glyph' => 'tower', 'desc' => 'Double-height living over the canal, with a private roof terrace.' ),
	array( 'cat' => 'Studio', 'name' => 'Atelier Studio', 'loc' => 'DIFC', 'beds' => 0, 'area' => 54, 'price' => 1200000, 'status' => 'Ready', 'glyph' => 'exterior', 'desc' => 'A precise, light-filled studio in the financial district.' ),
	array( 'cat' => 'Business', 'name' => 'Bay Office Loft', 'loc' => 'Business Bay', 'beds' => 1, 'area' => 120, 'price' => 3100000, 'status' => 'Off-plan', 'glyph' => 'interior', 'desc' => 'A live-work loft for founders who want a five-minute commute.' ),
	array( 'cat' => 'Apartment', 'name' => 'Skyframe Two-Bed', 'loc' => 'Dubai Marina', 'beds' => 2, 'area' => 146, 'price' => 2400000, 'status' => 'Ready', 'glyph' => 'tower', 'desc' => 'Marina-facing two-bedroom with floor-to-ceiling glass.' ),
	array( 'cat' => 'Villa', 'name' => 'Palm Shore Villa', 'loc' => 'Palm Jumeirah', 'beds' => 5, 'area' => 640, 'price' => 28000000, 'status' => 'Ready', 'glyph' => 'exterior', 'desc' => 'A beachfront villa with private pool and direct sand access.' ),
	array( 'cat' => 'Penthouse', 'name' => 'Creek Sky Penthouse', 'loc' => 'Dubai Creek', 'beds' => 4, 'area' => 388, 'price' => 12200000, 'status' => 'Off-plan', 'glyph' => 'tower', 'desc' => 'A corner penthouse framing the creek and the new skyline.' ),
	array( 'cat' => 'Apartment', 'name' => 'Garden Residence 12', 'loc' => 'City Walk', 'beds' => 2, 'area' => 132, 'price' => 2900000, 'status' => 'Ready', 'glyph' => 'interior', 'desc' => 'A quiet two-bedroom over the City Walk gardens.' ),
	array( 'cat' => 'Studio', 'name' => 'Pier Studio', 'loc' => 'Dubai Marina', 'beds' => 0, 'area' => 48, 'price' => 1050000, 'status' => 'Off-plan', 'glyph' => 'exterior', 'desc' => 'An efficient studio steps from the marina promenade.' ),
	array( 'cat' => 'Duplex', 'name' => 'Boulevard Duplex', 'loc' => 'Downtown', 'beds' => 3, 'area' => 244, 'price' => 7400000, 'status' => 'Ready', 'glyph' => 'interior', 'desc' => 'A duplex on the Boulevard, moments from the Opera.' ),
	array( 'cat' => 'Villa', 'name' => 'Hills Estate 04', 'loc' => 'Dubai Hills', 'beds' => 6, 'area' => 820, 'price' => 34000000, 'status' => 'Off-plan', 'glyph' => 'exterior', 'desc' => 'A standalone estate on the golf course, fully landscaped.' ),
	array( 'cat' => 'Apartment', 'name' => 'Frame One-Bed', 'loc' => 'Business Bay', 'beds' => 1, 'area' => 78, 'price' => 1600000, 'status' => 'Ready', 'glyph' => 'tower', 'desc' => 'A well-proportioned one-bedroom with canal views.' ),
	array( 'cat' => 'Penthouse', 'name' => 'Marina Crown PH', 'loc' => 'Dubai Marina', 'beds' => 5, 'area' => 520, 'price' => 19500000, 'status' => 'Ready', 'glyph' => 'interior', 'desc' => 'A full-floor penthouse crowning the marina.' ),
	array( 'cat' => 'Apartment', 'name' => 'Walk Three-Bed', 'loc' => 'City Walk', 'beds' => 3, 'area' => 188, 'price' => 4200000, 'status' => 'Off-plan', 'glyph' => 'tower', 'desc' => 'A family three-bedroom with a wrap-around balcony.' ),
) );

$onyx_types = array( 'Penthouse', 'Duplex', 'Apartment', 'Studio', 'Villa', 'Business' );
$onyx_locs  = array( 'Downtown', 'Business Bay', 'Dubai Marina', 'Palm Jumeirah', 'DIFC', 'City Walk', 'Dubai Creek', 'Dubai Hills' );
$onyx_type_counts = array();
foreach ( $onyx_types as $tt ) {
	$onyx_type_counts[ $tt ] = count( array_filter( $onyx_data, function ( $p ) use ( $tt ) { return $p['cat'] === $tt; } ) );
}
$onyx_prop_url = onyx_page_url( 'property' );
?>

<main>
	<section class="phead">
		<div class="wrap">
			<div class="crumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'luwipress-onyx' ); ?></a><span class="sep">/</span><span><?php esc_html_e( 'Listings', 'luwipress-onyx' ); ?></span></div>
			<span class="smallcaps" style="color:var(--gold)"><?php esc_html_e( 'Search the Portfolio', 'luwipress-onyx' ); ?></span>
			<h1 class="display-xl" style="margin-top:14px;font-size:clamp(44px,6vw,84px)"><?php esc_html_e( 'Find your residence', 'luwipress-onyx' ); ?></h1>
			<p class="phead-sub"><?php esc_html_e( "Filter by type, price, bedrooms and address. The most discreet listings are released privately — tell us what you're looking for.", 'luwipress-onyx' ); ?></p>
		</div>
	</section>

	<section class="section" style="padding-top:clamp(36px,4vw,56px)">
		<div class="wrap">
			<div class="lst-wrap" data-onyx-listings data-property-url="<?php echo esc_url( $onyx_prop_url ); ?>">
				<div class="lst-side-scrim" data-listings-side-scrim></div>
				<aside class="lst-side" data-listings-side>
					<div class="lst-side-top">
						<h4><?php esc_html_e( 'Filter', 'luwipress-onyx' ); ?></h4>
						<button type="button" data-listings-reset-all><?php esc_html_e( 'Reset all', 'luwipress-onyx' ); ?></button>
					</div>
					<div class="fgroup">
						<span class="fl"><?php esc_html_e( 'Price range', 'luwipress-onyx' ); ?></span>
						<div class="range">
							<div class="range-vals">
								<span><span class="smallcaps"><?php esc_html_e( 'From', 'luwipress-onyx' ); ?></span><span data-range-vmin>1M</span></span>
								<span style="text-align:right"><span class="smallcaps"><?php esc_html_e( 'To', 'luwipress-onyx' ); ?></span><span data-range-vmax>35M+</span></span>
							</div>
							<div class="range-track">
								<div class="range-fill" data-range-fill></div>
								<input type="range" min="1000000" max="35000000" step="100000" value="1000000" data-range-min aria-label="<?php esc_attr_e( 'Minimum price', 'luwipress-onyx' ); ?>">
								<input type="range" min="1000000" max="35000000" step="100000" value="35000000" data-range-max aria-label="<?php esc_attr_e( 'Maximum price', 'luwipress-onyx' ); ?>">
							</div>
						</div>
					</div>
					<div class="fgroup">
						<span class="fl"><?php esc_html_e( 'Property type', 'luwipress-onyx' ); ?></span>
						<div class="checks">
							<?php foreach ( $onyx_types as $tt ) : ?>
								<div class="chk" data-f-type="<?php echo esc_attr( $tt ); ?>"><span class="box"><?php echo onyx_icon( 'check', 13 ); // phpcs:ignore ?></span><?php echo esc_html( $tt ); ?><span class="cnt"><?php echo (int) $onyx_type_counts[ $tt ]; ?></span></div>
							<?php endforeach; ?>
						</div>
					</div>
					<div class="fgroup">
						<span class="fl"><?php esc_html_e( 'Bedrooms', 'luwipress-onyx' ); ?></span>
						<div class="pills">
							<?php
							$onyx_beds = array( 'Any' => __( 'Any', 'luwipress-onyx' ), '0' => __( 'Studio', 'luwipress-onyx' ), '1' => '1', '2' => '2', '3' => '3', '4' => '4+' );
							foreach ( $onyx_beds as $val => $lbl ) : ?>
								<button type="button" class="pill<?php echo 'Any' === $val ? ' on' : ''; ?>" data-f-beds="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $lbl ); ?></button>
							<?php endforeach; ?>
						</div>
					</div>
					<div class="fgroup">
						<span class="fl"><?php esc_html_e( 'Location', 'luwipress-onyx' ); ?></span>
						<div class="checks">
							<?php foreach ( $onyx_locs as $ll ) : ?>
								<div class="chk" data-f-loc="<?php echo esc_attr( $ll ); ?>"><span class="box"><?php echo onyx_icon( 'check', 13 ); // phpcs:ignore ?></span><?php echo esc_html( $ll ); ?></div>
							<?php endforeach; ?>
						</div>
					</div>
					<div class="fgroup">
						<span class="fl"><?php esc_html_e( 'Status', 'luwipress-onyx' ); ?></span>
						<div class="seg">
							<?php foreach ( array( 'Any', 'Ready', 'Off-plan' ) as $i => $stt ) : ?>
								<button type="button" class="<?php echo 0 === $i ? 'on' : ''; ?>" data-f-status="<?php echo esc_attr( $stt ); ?>"><?php echo esc_html( $stt ); ?></button>
							<?php endforeach; ?>
						</div>
					</div>
				</aside>

				<div>
					<div class="lst-bar">
						<div class="lst-count" data-listings-count><b><?php echo count( $onyx_data ); ?></b> <?php esc_html_e( 'residences', 'luwipress-onyx' ); ?></div>
						<div class="lst-ctrls">
							<button class="filt-toggle" type="button" data-listings-filter-toggle><span class="ic"><?php echo onyx_icon( 'menu', 16 ); // phpcs:ignore ?></span><?php esc_html_e( 'Filters', 'luwipress-onyx' ); ?></button>
							<div class="sortsel">
								<select data-listings-sort aria-label="<?php esc_attr_e( 'Sort', 'luwipress-onyx' ); ?>">
									<option value="featured"><?php esc_html_e( 'Sort · Featured', 'luwipress-onyx' ); ?></option>
									<option value="price-asc"><?php esc_html_e( 'Price · Low to high', 'luwipress-onyx' ); ?></option>
									<option value="price-desc"><?php esc_html_e( 'Price · High to low', 'luwipress-onyx' ); ?></option>
									<option value="area-desc"><?php esc_html_e( 'Largest area', 'luwipress-onyx' ); ?></option>
								</select>
							</div>
							<div class="viewtoggle">
								<button type="button" class="on" data-view="grid" aria-label="<?php esc_attr_e( 'Grid view', 'luwipress-onyx' ); ?>"><?php echo onyx_icon( 'grid', 17 ); // phpcs:ignore ?></button>
								<button type="button" data-view="list" aria-label="<?php esc_attr_e( 'List view', 'luwipress-onyx' ); ?>"><?php echo onyx_icon( 'rows', 17 ); // phpcs:ignore ?></button>
							</div>
						</div>
					</div>

					<div class="results grid" data-listings-results>
						<noscript><p style="color:var(--muted)"><?php esc_html_e( 'Enable JavaScript to browse and filter the full portfolio, or contact an advisor for the private list.', 'luwipress-onyx' ); ?></p></noscript>
					</div>
					<div class="pager" data-listings-pager></div>
				</div>
			</div>
		</div>
	</section>
</main>

<script type="application/json" id="onyx-listings-data"><?php echo wp_json_encode( array_values( $onyx_data ) ); ?></script>

<?php
get_footer();
