<?php
/**
 * Front page — the designed Onyx home ("quiet luxury, after dark").
 *
 * Reproduces the home composition from the design references
 * (onyx-app.jsx + onyx-sec-a..e.jsx): editorial split hero → trust strip
 * → building overview → apartment plans (tabbed) → property grid →
 * neighborhoods → testimonials → lifestyle band → journal teasers → FAQ
 * (with FAQPage JSON-LD) → contact band.
 *
 * Yields to Elementor when the front page is built with it, so operators
 * can take over layout in the editor.
 *
 * @package luwipress-onyx
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$onyx_front_id = (int) get_option( 'page_on_front' );
$onyx_el_built = $onyx_front_id && (bool) get_post_meta( $onyx_front_id, '_elementor_edit_mode', true ) && (bool) get_post_meta( $onyx_front_id, '_elementor_data', true );

get_header();

if ( $onyx_el_built ) {
	while ( have_posts() ) {
		the_post();
		the_content();
	}
	get_footer();
	return;
}

$onyx_listings = onyx_page_url( 'listings' );
$onyx_gallery  = onyx_page_url( 'gallery' );
$onyx_contact  = onyx_page_url( 'contact' );
$onyx_journal  = onyx_page_url( 'journal' );
?>

<main>

	<!-- ===== HERO (editorial split) ===== -->
	<section class="hero hero--b" id="top">
		<div class="hb-text">
			<span class="hero-eyebrow"><?php echo onyx_eyebrow( __( 'The Collection · 2026', 'luwipress-onyx' ) ); // phpcs:ignore ?></span>
			<h1 class="display-lg"><?php esc_html_e( 'Quiet luxury,', 'luwipress-onyx' ); ?><br><?php esc_html_e( 'after dark.', 'luwipress-onyx' ); ?></h1>
			<p class="lede hero-sub"><?php esc_html_e( 'Premium properties across the UAE, sold the way wealth is kept — privately, patiently, without noise. Begin with a single residence.', 'luwipress-onyx' ); ?></p>
			<div class="hero-cta">
				<a class="btn btn-gold" href="<?php echo esc_url( $onyx_gallery ); ?>"><?php esc_html_e( 'Find Your Home', 'luwipress-onyx' ); ?> <span class="arr"><?php echo onyx_icon( 'arrow', 16 ); // phpcs:ignore ?></span></a>
				<a class="btn btn-ghost" href="#contact"><?php esc_html_e( 'Book a Viewing', 'luwipress-onyx' ); ?></a>
			</div>
			<div class="hero-meta" style="margin-top:56px">
				<div><div class="smallcaps"><?php esc_html_e( 'From', 'luwipress-onyx' ); ?></div><div class="tnum" style="font-family:var(--display);font-size:30px;color:var(--gold)">AED 2.4M</div></div>
				<div class="hair-gold" style="width:1px;height:44px"></div>
				<div><div class="smallcaps"><?php esc_html_e( 'Active listings', 'luwipress-onyx' ); ?></div><div class="tnum" style="font-family:var(--display);font-size:30px;color:var(--ink)">366</div></div>
			</div>
		</div>
		<div class="hb-photo">
			<?php echo onyx_ph( array( 'glyph' => 'tower', 'style' => 'position:absolute;inset:0' ) ); // phpcs:ignore ?>
			<div class="grain"></div>
			<div class="hb-num"><span class="smallcaps" style="color:var(--gold-soft)"><?php esc_html_e( 'Featured', 'luwipress-onyx' ); ?></span><div class="tnum">01</div></div>
			<div class="hb-cap"><span class="smallcaps"><?php esc_html_e( 'Now showing', 'luwipress-onyx' ); ?></span><div style="font-family:var(--display);font-size:26px;margin-top:4px"><?php esc_html_e( 'The Onyx Penthouse', 'luwipress-onyx' ); ?></div></div>
		</div>
	</section>

	<!-- ===== TRUST STRIP ===== -->
	<section class="section">
		<div class="wrap">
			<div class="trust-in">
				<div class="trust-badge reveal">
					<span class="tnum" data-countup="10">10</span>
					<span class="t-lbl"><?php esc_html_e( 'Years of experience', 'luwipress-onyx' ); ?></span>
					<span class="smallcaps"><?php esc_html_e( 'Est. 2016 · Dubai', 'luwipress-onyx' ); ?></span>
				</div>
				<?php echo onyx_ph( array( 'glyph' => 'interior', 'class' => 'trust-img reveal' ) ); // phpcs:ignore ?>
				<?php echo onyx_ph( array( 'glyph' => 'tower', 'tag' => 'Penthouse — Downtown', 'class' => 'trust-img reveal' ) ); // phpcs:ignore ?>
				<div class="trust-tag reveal">
					<p class="display-md"><?php esc_html_e( 'Beautiful spaces in the best places.', 'luwipress-onyx' ); ?></p>
				</div>
			</div>
		</div>
	</section>

	<!-- ===== BUILDING OVERVIEW ===== -->
	<section class="section" id="about">
		<div class="wrap">
			<div class="ov-grid">
				<div class="ov-copy">
					<span class="reveal" style="display:block"><?php echo onyx_eyebrow( __( 'The Building Overview', 'luwipress-onyx' ) ); // phpcs:ignore ?></span>
					<h2 class="display-lg reveal" style="margin:22px 0 26px"><?php esc_html_e( 'Modern & premium apartments', 'luwipress-onyx' ); ?></h2>
					<p class="lede reveal"><?php esc_html_e( 'Every Arsha residence is built around restraint — full-height glass, honest stone, and rooms proportioned for stillness. The result is architecture that recedes so the city, the light and the life inside it can take the lead.', 'luwipress-onyx' ); ?></p>
					<ul class="ov-checks reveal">
						<li><span class="ic"><?php echo onyx_icon( 'check', 18 ); // phpcs:ignore ?></span><div><b style="font-weight:500"><?php esc_html_e( 'Hand-finished interiors', 'luwipress-onyx' ); ?></b> <span><?php esc_html_e( '— marble, oak and brushed brass, specified to order.', 'luwipress-onyx' ); ?></span></div></li>
						<li><span class="ic"><?php echo onyx_icon( 'check', 18 ); // phpcs:ignore ?></span><div><b style="font-weight:500"><?php esc_html_e( 'Private amenities', 'luwipress-onyx' ); ?></b> <span><?php esc_html_e( "— infinity pool, spa, residents' lounge and concierge.", 'luwipress-onyx' ); ?></span></div></li>
						<li><span class="ic"><?php echo onyx_icon( 'check', 18 ); // phpcs:ignore ?></span><div><b style="font-weight:500"><?php esc_html_e( 'Five-minute city', 'luwipress-onyx' ); ?></b> <span><?php esc_html_e( '— Business Bay, DIFC and Downtown within reach.', 'luwipress-onyx' ); ?></span></div></li>
					</ul>
				</div>
				<div class="ov-media reveal">
					<?php echo onyx_ph( array( 'glyph' => 'tower', 'tag' => 'Tower elevation', 'style' => 'height:100%' ) ); // phpcs:ignore ?>
					<?php echo onyx_ph( array( 'glyph' => 'interior', 'class' => 'ov-float' ) ); // phpcs:ignore ?>
				</div>
			</div>

			<div class="stats-row">
				<?php
				// DLD market scale via arsha-connect — CACHED (transient) so the homepage
				// never makes a live maps-API call per request (graceful fallback to demo figures).
				$onyx_ov = function_exists( 'onyx_arsha_cached' ) ? onyx_arsha_cached( 'overview' ) : null;
				if ( is_array( $onyx_ov ) && ! empty( $onyx_ov['projects_total'] ) ) {
					$onyx_ov_stats = array(
						array( (int) $onyx_ov['projects_total'],   '',  __( 'Projects tracked', 'luwipress-onyx' ) ),
						array( (int) $onyx_ov['developers_total'], '',  __( 'Developers', 'luwipress-onyx' ) ),
						array( (int) $onyx_ov['areas_total'],      '',  __( 'Communities', 'luwipress-onyx' ) ),
						array( (int) round( (float) ( $onyx_ov['ready_vs_offplan']['offplan_pct'] ?? 0 ) ), '%', __( 'Off-plan share', 'luwipress-onyx' ) ),
					);
				} else {
					$onyx_ov_stats = array(
						array( 8800, '', __( 'Square metres', 'luwipress-onyx' ) ),
						array( 680, '', __( 'Car parkings', 'luwipress-onyx' ) ),
						array( 366, '', __( 'Apartments', 'luwipress-onyx' ) ),
						array( 286, '', __( 'Bedrooms', 'luwipress-onyx' ) ),
					);
				}
				foreach ( $onyx_ov_stats as $st ) : ?>
					<div class="stat">
						<div class="tnum stat-num"><span data-countup="<?php echo (int) $st[0]; ?>"><?php echo esc_html( number_format_i18n( $st[0] ) ); ?></span><span class="stat-suf"><?php echo esc_html( $st[1] ); ?></span></div>
						<div class="smallcaps stat-lbl"><?php echo esc_html( $st[2] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<!-- ===== APARTMENT PLANS (tabbed) ===== -->
	<section class="section" style="background:var(--onyx-850);border-top:1px solid var(--hair-soft);border-bottom:1px solid var(--hair-soft)">
		<div class="wrap">
			<?php
			$onyx_plans = array(
				'Penthouse'     => array( 'area' => '412 m²', 'floor' => '58—60', 'rooms' => '4 + Maid', 'parking' => '3', 'price' => 'AED 14.8M' ),
				'Studio'        => array( 'area' => '54 m²',  'floor' => '12—28', 'rooms' => 'Open plan', 'parking' => '1', 'price' => 'AED 1.2M' ),
				'Duplex'        => array( 'area' => '268 m²', 'floor' => '40—41', 'rooms' => '3 + Study', 'parking' => '2', 'price' => 'AED 6.9M' ),
				'Apartment'     => array( 'area' => '146 m²', 'floor' => '18—36', 'rooms' => '2 Bed', 'parking' => '1', 'price' => 'AED 2.4M' ),
				'Double Height' => array( 'area' => '224 m²', 'floor' => '44—45', 'rooms' => '3 Bed', 'parking' => '2', 'price' => 'AED 5.6M' ),
			);
			$onyx_plan_keys = array_keys( $onyx_plans );
			?>
			<div class="plans-head">
				<div>
					<span class="reveal" style="display:block"><?php echo onyx_eyebrow( __( 'Villa Plans', 'luwipress-onyx' ) ); // phpcs:ignore ?></span>
					<h2 class="display-lg reveal" style="margin-top:18px"><?php esc_html_e( 'Floor plans, by typology', 'luwipress-onyx' ); ?></h2>
				</div>
				<div class="plans-tabs reveal">
					<?php foreach ( $onyx_plan_keys as $i => $key ) : ?>
						<button type="button" class="<?php echo 0 === $i ? 'on' : ''; ?>" data-plan="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $key ); ?></button>
					<?php endforeach; ?>
				</div>
			</div>

			<?php foreach ( $onyx_plan_keys as $i => $key ) :
				$p = $onyx_plans[ $key ];
				$specs = array(
					array( 'ruler', __( 'Total area', 'luwipress-onyx' ), $p['area'] ),
					array( 'floor', __( 'Floor no.', 'luwipress-onyx' ), $p['floor'] ),
					array( 'bed', __( 'Rooms', 'luwipress-onyx' ), $p['rooms'] ),
					array( 'car', __( 'Parking', 'luwipress-onyx' ), $p['parking'] ),
				); ?>
				<div class="plans-body plans-panel" data-plan="<?php echo esc_attr( $key ); ?>"<?php echo 0 === $i ? '' : ' hidden'; ?>>
					<div>
						<div class="plans-specs">
							<?php foreach ( $specs as $s ) : ?>
								<div class="spec">
									<span class="smallcaps"><span class="ic"><?php echo onyx_icon( $s[0], 15 ); // phpcs:ignore ?></span><?php echo esc_html( $s[1] ); ?></span>
									<div class="spec-v"><?php echo esc_html( $s[2] ); ?></div>
								</div>
							<?php endforeach; ?>
						</div>
						<div class="plans-price">
							<div><span class="smallcaps"><?php esc_html_e( 'Price from', 'luwipress-onyx' ); ?></span><div class="pv"><?php echo esc_html( $p['price'] ); ?></div></div>
						</div>
						<a class="btn btn-ghost" href="#contact" style="margin-top:34px"><?php esc_html_e( 'Request full plan', 'luwipress-onyx' ); ?> <span class="arr"><?php echo onyx_icon( 'arrow', 16 ); // phpcs:ignore ?></span></a>
					</div>
					<div class="plans-floor">
						<?php echo onyx_ph( array( 'glyph' => 'plan', 'tag' => $key . ' — ' . __( 'floor plan', 'luwipress-onyx' ) ) ); // phpcs:ignore ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</section>

	<!-- ===== PROPERTY GRID ===== -->
	<section class="section" id="gallery">
		<div class="wrap">
			<div class="sec-head">
				<div class="sh-title">
					<span class="reveal" style="display:block"><?php echo onyx_eyebrow( __( 'Apartments & Complex', 'luwipress-onyx' ) ); // phpcs:ignore ?></span>
					<h2 class="display-lg reveal"><?php esc_html_e( 'Choose your apartment', 'luwipress-onyx' ); ?></h2>
				</div>
				<a class="sh-link reveal" href="<?php echo esc_url( $onyx_gallery ); ?>"><?php esc_html_e( 'View all residences', 'luwipress-onyx' ); ?> <?php echo onyx_icon( 'arrow', 15 ); // phpcs:ignore ?></a>
			</div>
			<div class="pgrid reveal">
				<?php
				// Featured projects — operator-picked arsha_project posts first (via the
				// featured registry), topped up with the most recent; each links to itself.
				$onyx_res_ids = function_exists( 'lwp_onyx_get_featured_ids' )
					? lwp_onyx_get_featured_ids( array( 'post_type' => 'arsha_project', 'number' => 6 ) )
					: array();
				if ( count( $onyx_res_ids ) < 6 ) {
					$onyx_res_fill = get_posts( array(
						'post_type'        => 'arsha_project',
						'posts_per_page'   => 6 - count( $onyx_res_ids ),
						'post_status'      => 'publish',
						'fields'           => 'ids',
						'post__not_in'     => $onyx_res_ids ? $onyx_res_ids : array( 0 ),
						'orderby'          => 'date',
						'order'            => 'DESC',
						'no_found_rows'    => true,
						'suppress_filters' => false,
					) );
					$onyx_res_ids = array_merge( $onyx_res_ids, $onyx_res_fill );
				}
				$onyx_res = array_filter( array_map( 'get_post', $onyx_res_ids ) );
				if ( $onyx_res ) :
					foreach ( $onyx_res as $rp ) :
						$r_id    = $rp->ID;
						$r_img   = get_the_post_thumbnail_url( $r_id, 'large' );
						$r_areas = wp_get_post_terms( $r_id, 'arsha_area', array( 'fields' => 'names' ) );
						$r_stats = wp_get_post_terms( $r_id, 'arsha_project_status', array( 'fields' => 'names' ) );
						$r_area  = ( ! empty( $r_areas ) && ! is_wp_error( $r_areas ) ) ? $r_areas[0] : '';
						$r_stat  = ( ! empty( $r_stats ) && ! is_wp_error( $r_stats ) ) ? $r_stats[0] : '';
						$r_dev   = (string) get_post_meta( $r_id, '_arsha_developer', true );
						$r_price = (int) get_post_meta( $r_id, '_arsha_price', true );
						?>
						<a class="pcard" href="<?php echo esc_url( get_permalink( $r_id ) ); ?>">
							<div class="pcard-media"<?php echo $r_img ? ' style="background-image:url(\'' . esc_url( $r_img ) . '\');background-size:cover;background-position:center"' : ''; ?>><span class="pcard-cat"><?php echo esc_html( $r_stat ); ?></span></div>
							<div class="pcard-body">
								<div class="pcard-loc"><span class="ic"><?php echo onyx_icon( 'pin', 13 ); // phpcs:ignore ?></span><?php echo esc_html( $r_area ); ?></div>
								<h3><?php echo esc_html( get_the_title( $r_id ) ); ?></h3>
								<?php if ( $r_dev ) : ?><div class="pcard-meta"><span><?php echo esc_html( $r_dev ); ?></span></div><?php endif; ?>
								<div class="pcard-foot">
									<div class="pcard-price"><small><?php esc_html_e( 'Price from', 'luwipress-onyx' ); ?></small><?php echo $r_price ? esc_html( 'AED ' . number_format_i18n( $r_price ) ) : esc_html( $r_stat ); ?></div>
									<span class="pcard-arrow"><?php echo onyx_icon( 'arrowUR', 16 ); // phpcs:ignore ?></span>
								</div>
							</div>
						</a>
					<?php endforeach;
				else :
					$onyx_props = array(
						array( 'Penthouse', 'The Onyx Penthouse', 'Downtown', '4 Bed', '412 m²', 'AED 14.8M', 'interior' ),
						array( 'Apartment', 'Skyframe Two-Bed', 'Dubai Marina', '2 Bed', '146 m²', 'AED 2.4M', 'tower' ),
						array( 'Villa', 'Palm Shore Villa', 'Palm Jumeirah', '5 Bed', '640 m²', 'AED 28M', 'exterior' ),
					);
					foreach ( $onyx_props as $p ) : ?>
						<a class="pcard" href="<?php echo esc_url( $onyx_listings ); ?>">
							<div class="pcard-media"><span class="pcard-cat"><?php echo esc_html( $p[0] ); ?></span><?php echo onyx_ph( array( 'glyph' => $p[6] ) ); // phpcs:ignore ?></div>
							<div class="pcard-body">
								<div class="pcard-loc"><span class="ic"><?php echo onyx_icon( 'pin', 13 ); // phpcs:ignore ?></span><?php echo esc_html( $p[2] ); ?></div>
								<h3><?php echo esc_html( $p[1] ); ?></h3>
								<div class="pcard-foot"><div class="pcard-price"><small><?php esc_html_e( 'Price from', 'luwipress-onyx' ); ?></small><?php echo esc_html( $p[5] ); ?></div><span class="pcard-arrow"><?php echo onyx_icon( 'arrowUR', 16 ); // phpcs:ignore ?></span></div>
							</div>
						</a>
					<?php endforeach;
				endif; ?>
			</div>
		</div>
	</section>

	<!-- ===== DEVELOPER PARTNERS (dynamic — lwp_vendor) ===== -->
	<?php
	$onyx_devs = get_posts( array(
		'post_type'        => 'lwp_vendor',
		'posts_per_page'   => 12,
		'orderby'          => 'ID',
		'order'            => 'ASC',
		'post_status'      => 'publish',
		'suppress_filters' => false,
	) );
	if ( $onyx_devs ) : ?>
	<section class="section" id="developers" style="background:var(--onyx-850);border-top:1px solid var(--hair-soft);border-bottom:1px solid var(--hair-soft)">
		<div class="wrap">
			<div class="sec-head">
				<div class="sh-title">
					<span class="reveal" style="display:block"><?php echo onyx_eyebrow( __( 'Developer Partners', 'luwipress-onyx' ) ); // phpcs:ignore ?></span>
					<h2 class="display-lg reveal"><?php esc_html_e( 'The names that build Dubai', 'luwipress-onyx' ); ?></h2>
				</div>
				<a class="sh-link reveal" href="<?php echo esc_url( get_post_type_archive_link( 'lwp_vendor' ) ); ?>"><?php esc_html_e( 'All developers', 'luwipress-onyx' ); ?> <?php echo onyx_icon( 'arrow', 14 ); // phpcs:ignore ?></a>
			</div>
			<div class="dev-showcase">
				<?php foreach ( $onyx_devs as $onyx_dev ) :
					$onyx_dev_lead = get_the_excerpt( $onyx_dev ); ?>
					<a class="dev-card reveal" href="<?php echo esc_url( get_permalink( $onyx_dev ) ); ?>">
						<span class="dev-mono"><?php echo esc_html( mb_strtoupper( mb_substr( get_the_title( $onyx_dev ), 0, 1 ) ) ); ?></span>
						<span class="dev-name"><?php echo esc_html( get_the_title( $onyx_dev ) ); ?></span>
						<?php if ( $onyx_dev_lead ) : ?><span class="dev-lead"><?php echo esc_html( $onyx_dev_lead ); ?></span><?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<!-- ===== NEIGHBORHOODS ===== -->
	<section class="section" id="areas">
		<div class="wrap">
			<div class="sec-head">
				<div class="sh-title">
					<span class="reveal" style="display:block"><?php echo onyx_eyebrow( __( 'Dubai Communities', 'luwipress-onyx' ) ); // phpcs:ignore ?></span>
					<h2 class="display-lg reveal"><?php esc_html_e( 'Where Dubai lives', 'luwipress-onyx' ); ?></h2>
				</div>
				<a class="sh-link reveal" href="<?php echo esc_url( home_url( '/market-insights/' ) ); ?>"><?php esc_html_e( 'Market insights', 'luwipress-onyx' ); ?> <?php echo onyx_icon( 'arrow', 14 ); // phpcs:ignore ?></a>
			</div>
			<?php
			// Top Dubai communities via arsha-connect — CACHED (transient), no live
			// maps-API call per request (graceful if unavailable).
			$onyx_aidx      = function_exists( 'onyx_arsha_cached' ) ? onyx_arsha_cached( 'area-index' ) : null;
			$onyx_top_areas = ( is_array( $onyx_aidx ) && ! empty( $onyx_aidx['top_areas'] ) ) ? $onyx_aidx['top_areas'] : array();
			if ( $onyx_top_areas ) : ?>
			<div class="area-grid">
				<?php foreach ( $onyx_top_areas as $k => $ar ) :
					$ar_name = (string) ( $ar['area'] ?? '' );
					if ( '' === $ar_name ) { continue; }
					$ar_proj = (int) ( $ar['project_count'] ?? 0 );
					$ar_avg  = (float) ( $ar['avg_price_aed'] ?? 0 );
					$ar_url  = add_query_arg( 'residence_area', sanitize_title( $ar_name ), get_post_type_archive_link( 'lwp_residence' ) );
					?>
					<a class="area-card reveal" href="<?php echo esc_url( $ar_url ); ?>">
						<span class="area-idx"><?php echo esc_html( str_pad( (string) ( $k + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
						<span class="area-name"><?php echo esc_html( $ar_name ); ?></span>
						<span class="area-meta">
							<span><?php echo esc_html( sprintf( _n( '%d project', '%d projects', $ar_proj, 'luwipress-onyx' ), $ar_proj ) ); ?></span>
							<?php if ( $ar_avg > 0 && function_exists( 'arsha_connect_aed' ) ) : ?>
								<span class="area-avg"><?php echo esc_html( sprintf( __( 'avg %s', 'luwipress-onyx' ), arsha_connect_aed( $ar_avg ) ) ); ?></span>
							<?php endif; ?>
						</span>
						<span class="area-go" aria-hidden="true"><?php echo onyx_icon( 'arrowUR', 14 ); // phpcs:ignore ?></span>
					</a>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
	</section>

	<!-- ===== TESTIMONIALS ===== -->
	<section class="section" style="background:var(--onyx-850);border-top:1px solid var(--hair-soft)">
		<div class="wrap">
			<div class="sec-head" style="justify-content:center;text-align:center;margin-bottom:60px">
				<div class="sh-title" style="margin:0 auto">
					<span class="reveal" style="display:flex;justify-content:center"><?php echo onyx_eyebrow( __( 'About Company', 'luwipress-onyx' ), true ); // phpcs:ignore ?></span>
					<h2 class="display-lg reveal" style="margin-top:18px"><?php esc_html_e( "What they're saying", 'luwipress-onyx' ); ?></h2>
				</div>
			</div>
			<?php
			$onyx_quotes = array(
				array( 'They never once used the word "opportunity". They showed me a home, let the silence do the work, and waited. That is how it should be done.', 'Lina Haddad', 'Private Collector', 'LH' ),
				array( 'Arsha handled an eight-figure purchase the way a private bank handles a portfolio — discreet, precise, and entirely without theatre.', 'Marcus Reed', 'Family Office, London', 'MR' ),
				array( 'Every detail was already considered before I asked. I signed for the calm as much as the address.', 'Aisha Al-Faraj', 'Founder, Dubai', 'AF' ),
			);
			$onyx_partners = array( 'EMAAR', 'DAMAC', 'SOBHA', 'MERAAS', 'NAKHEEL', 'OMNIYAT' );
			?>
			<div class="tst">
				<span class="tst-mark">&ldquo;</span>
				<div class="tst-stage">
					<?php foreach ( $onyx_quotes as $k => $q ) : ?>
						<div class="tst-slide<?php echo 0 === $k ? ' on' : ''; ?>">
							<p class="tst-quote"><?php echo esc_html( $q[0] ); ?></p>
							<div class="tst-who">
								<span class="tst-av" style="display:grid;place-items:center;font-family:var(--display);font-size:20px;color:var(--gold)"><?php echo esc_html( $q[3] ); ?></span>
								<div><div class="tst-name"><?php echo esc_html( $q[1] ); ?></div><div class="tst-role"><?php echo esc_html( $q[2] ); ?></div></div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="tst-dots">
					<?php foreach ( $onyx_quotes as $k => $q ) : ?>
						<button type="button" class="<?php echo 0 === $k ? 'on' : ''; ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Quote %d', 'luwipress-onyx' ), $k + 1 ) ); ?>"></button>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="logos">
				<?php foreach ( $onyx_partners as $partner ) : ?>
					<span class="lg"><?php echo esc_html( $partner ); ?></span>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<!-- ===== LIFESTYLE / FILM CTA ===== -->
	<section class="life">
		<div class="life-bg"><?php echo onyx_ph( array( 'glyph' => 'tower', 'style' => 'width:100%;height:100%' ) ); // phpcs:ignore ?></div>
		<div class="life-veil"></div>
		<div class="grain"></div>
		<div class="wrap">
			<span class="reveal" style="display:inline-block"><button class="life-play" type="button" aria-label="<?php esc_attr_e( 'Play film', 'luwipress-onyx' ); ?>"><?php echo onyx_icon( 'play', 26 ); // phpcs:ignore ?></button></span>
			<div class="reveal" style="display:flex;justify-content:center;margin-bottom:22px"><?php echo onyx_eyebrow( __( 'The Film', 'luwipress-onyx' ), true ); // phpcs:ignore ?></div>
			<h2 class="display-lg reveal"><?php esc_html_e( 'Modern & luxury living complexes', 'luwipress-onyx' ); ?></h2>
			<div class="life-stats">
				<?php
				$onyx_life_stats = array(
					array( 360, '+', __( 'Apartments', 'luwipress-onyx' ) ),
					array( 680, '+', __( 'Features', 'luwipress-onyx' ) ),
					array( 12, 'k', __( 'Luxury rooms', 'luwipress-onyx' ) ),
					array( 98, '%', __( 'Success rate', 'luwipress-onyx' ) ),
				);
				foreach ( $onyx_life_stats as $st ) : ?>
					<div class="stat">
						<div class="tnum stat-num"><span data-countup="<?php echo (int) $st[0]; ?>"><?php echo (int) $st[0]; ?></span><span class="stat-suf"><?php echo esc_html( $st[1] ); ?></span></div>
						<div class="smallcaps stat-lbl"><?php echo esc_html( $st[2] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<!-- ===== JOURNAL TEASERS ===== -->
	<section class="section" id="news">
		<div class="wrap">
			<div class="sec-head">
				<div class="sh-title">
					<span class="reveal" style="display:block"><?php echo onyx_eyebrow( __( "What's Happening", 'luwipress-onyx' ) ); // phpcs:ignore ?></span>
					<h2 class="display-lg reveal"><?php esc_html_e( 'Latest from Arsha', 'luwipress-onyx' ); ?></h2>
				</div>
				<a class="sh-link reveal" href="<?php echo esc_url( $onyx_journal ); ?>"><?php esc_html_e( 'All articles', 'luwipress-onyx' ); ?> <?php echo onyx_icon( 'arrow', 15 ); // phpcs:ignore ?></a>
			</div>
			<div class="news-grid">
				<?php
				$onyx_news = new WP_Query( array( 'posts_per_page' => 3, 'post_status' => 'publish', 'ignore_sticky_posts' => true ) );
				if ( $onyx_news->have_posts() ) :
					$glyphs = array( 'tower', 'interior', 'exterior' );
					$gi = 0;
					while ( $onyx_news->have_posts() ) : $onyx_news->the_post();
						$cats = get_the_category();
						$cat  = ! empty( $cats ) ? $cats[0]->name : __( 'Journal', 'luwipress-onyx' );
						$thumb = get_the_post_thumbnail_url( get_the_ID(), 'large' );
						?>
						<article class="ncard reveal">
							<a href="<?php the_permalink(); ?>" style="display:block;color:inherit">
								<div class="ncard-media">
									<?php echo onyx_ph( array( 'glyph' => $glyphs[ $gi % 3 ], 'img' => $thumb ) ); // phpcs:ignore ?>
									<div class="ncard-date"><div class="d tnum"><?php echo esc_html( get_the_date( 'd' ) ); ?></div><div class="m"><?php echo esc_html( get_the_date( 'M' ) ); ?></div></div>
								</div>
								<div class="ncard-cat"><?php echo esc_html( $cat ); ?></div>
								<h3><?php the_title(); ?></h3>
								<span class="ncard-more"><?php esc_html_e( 'Read more', 'luwipress-onyx' ); ?> <?php echo onyx_icon( 'arrow', 14 ); // phpcs:ignore ?></span>
							</a>
						</article>
						<?php $gi++;
					endwhile;
					wp_reset_postdata();
				else :
					$onyx_demo_news = array(
						array( '18', 'May', 'Market', 'Why Business Bay is the quietest bet in Dubai', 'tower' ),
						array( '04', 'Apr', 'Design', 'Inside the Onyx material palette: stone, oak, brass', 'interior' ),
						array( '21', 'Mar', 'Living', "A buyer's guide to off-plan, without the noise", 'exterior' ),
					);
					foreach ( $onyx_demo_news as $n ) : ?>
						<article class="ncard reveal">
							<a href="<?php echo esc_url( $onyx_journal ); ?>" style="display:block;color:inherit">
								<div class="ncard-media"><?php echo onyx_ph( array( 'glyph' => $n[4] ) ); // phpcs:ignore ?><div class="ncard-date"><div class="d tnum"><?php echo esc_html( $n[0] ); ?></div><div class="m"><?php echo esc_html( $n[1] ); ?></div></div></div>
								<div class="ncard-cat"><?php echo esc_html( $n[2] ); ?></div>
								<h3><?php echo esc_html( $n[3] ); ?></h3>
								<span class="ncard-more"><?php esc_html_e( 'Read more', 'luwipress-onyx' ); ?> <?php echo onyx_icon( 'arrow', 14 ); // phpcs:ignore ?></span>
							</a>
						</article>
					<?php endforeach;
				endif; ?>
			</div>
		</div>
	</section>

	<?php
	// ===== FAQ (FAQPage JSON-LD) =====
	$onyx_faqs = array(
		array( 'Can foreign nationals buy property in Dubai?', "Yes. Overseas buyers can own freehold property outright in Dubai's designated areas — including Business Bay, Downtown, Dubai Marina and Palm Jumeirah. No residency or local partner is required to purchase." ),
		array( 'What is the minimum required to get started?', 'Our portfolio begins at around AED 1.2M for a studio. Many off-plan residences offer staged payment plans, so the initial commitment can be a fraction of the total price.' ),
		array( 'Does buying property qualify me for UAE residency?', 'A property investment of AED 2M or more can qualify you for the UAE Golden Visa — a renewable 10-year residency for you and your family. We coordinate the application end to end.' ),
		array( 'What costs should I budget beyond the price?', 'Typically a 4% Dubai Land Department transfer fee, a registration fee, and our advisory fee. We provide a full, itemised breakdown before you commit — with no surprises.' ),
		array( 'Do you handle both off-plan and ready properties?', 'Both. We advise on ready, key-in-hand residences as well as select off-plan releases from Emaar, Sobha, Omniyat and Meraas — many of them off-market before public listing.' ),
		array( 'Can you work with international buyers remotely?', 'Yes. We complete purchases entirely remotely through power of attorney and secure video viewings, and we handle conveyancing, snagging and handover on your behalf.' ),
	);
	$onyx_faq_schema = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => array_map( function ( $f ) {
			return array(
				'@type'          => 'Question',
				'name'           => $f[0],
				'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $f[1] ),
			);
		}, $onyx_faqs ),
	);
	?>
	<script type="application/ld+json"><?php echo wp_json_encode( $onyx_faq_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?></script>
	<section class="section faq" id="faq">
		<div class="wrap">
			<div class="faq-grid">
				<div class="faq-aside reveal">
					<?php echo onyx_eyebrow( __( 'Good to Know', 'luwipress-onyx' ) ); // phpcs:ignore ?>
					<h2 class="display-lg"><?php esc_html_e( 'Questions,', 'luwipress-onyx' ); ?><br><?php esc_html_e( 'answered.', 'luwipress-onyx' ); ?></h2>
					<p><?php esc_html_e( 'The essentials of buying in Dubai, without the noise. Anything else — ask your advisor directly.', 'luwipress-onyx' ); ?></p>
					<a class="faq-call" href="tel:0567761946"><span class="ic"><?php echo onyx_icon( 'phone', 16 ); // phpcs:ignore ?></span>056 776 1946</a>
				</div>
				<div class="faq-list reveal">
					<?php foreach ( $onyx_faqs as $k => $f ) : ?>
						<div class="faq-item<?php echo 0 === $k ? ' open' : ''; ?>">
							<button class="faq-q" type="button" aria-expanded="<?php echo 0 === $k ? 'true' : 'false'; ?>"><?php echo esc_html( $f[0] ); ?><span class="faq-sign" aria-hidden="true"></span></button>
							<div class="faq-a"><div class="faq-a-inner"><?php echo esc_html( $f[1] ); ?></div></div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</section>

	<!-- ===== CONTACT ===== -->
	<?php get_template_part( 'template-parts/onyx', 'contact', array( 'eyebrow' => __( 'Free Quote', 'luwipress-onyx' ), 'title' => __( 'Begin a private conversation', 'luwipress-onyx' ) ) ); ?>

</main>

<?php
get_footer();
