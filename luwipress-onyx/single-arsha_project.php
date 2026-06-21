<?php
/**
 * Single Project — `arsha_project` detail (Phase 2b, tabbed/modern).
 *
 * Investor-facing project page: cinematic hero + key stats, then a tabbed body
 * (Overview · Market & valuation · Calculators · Location). Rich DLD data
 * (gallery, rental yield, escrow, transactions, property-type mix, developer
 * rating) is pulled live (cached 6h) via lwp_onyx_project_detail(); CPT meta is
 * the fallback. Calculators + tabs + gallery lightbox are driven by onyx.js.
 *
 * Yields to Elementor when the post is built with it.
 *
 * @package luwipress-onyx
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

while ( have_posts() ) :
	the_post();
	$pid = get_the_ID();

	if ( get_post_meta( $pid, '_elementor_edit_mode', true ) && get_post_meta( $pid, '_elementor_data', true ) ) {
		the_content();
		continue;
	}

	$dld_id = (int) get_post_meta( $pid, LWP_ONYX_PROJECT_META['dld_id'], true );
	$d      = lwp_onyx_project_detail( $dld_id );

	$g = function ( $key, $default = '' ) use ( $d ) {
		return ( is_array( $d ) && isset( $d[ $key ] ) && $d[ $key ] !== null && $d[ $key ] !== '' ) ? $d[ $key ] : $default;
	};
	$m = function ( $key ) use ( $pid ) {
		return get_post_meta( $pid, LWP_ONYX_PROJECT_META[ $key ], true );
	};

	$name     = get_the_title();
	$area     = $g( 'area', lwp_onyx_project_first_term( $pid, 'arsha_area' ) );
	$status   = $g( 'status', (string) $m( 'status' ) );
	$dev      = $g( 'developer', (string) $m( 'developer' ) );
	$rating   = (float) $g( 'developer_rating', 0 );
	$units    = (int) $g( 'units_count', (int) $m( 'units' ) );
	$progress = (float) $g( 'progress', (float) $m( 'progress' ) );
	$handover = $g( 'handover', (string) $m( 'handover' ) );
	$start    = $g( 'start_date', '' );
	$reg      = $g( 'registration_date', '' );
	$lat      = (float) $g( 'lat', (float) $m( 'lat' ) );
	$lng      = (float) $g( 'lng', (float) $m( 'lng' ) );
	$hero     = $g( 'hero_image_url', (string) $m( 'hero' ) );
	$escrow   = (array) $g( 'escrow', array() );
	$gallery  = (array) $g( 'gallery', array() );
	$ptmix    = (array) $g( 'property_type_mix', array() );
	$txn      = (array) $g( 'transactions_summary', array() );
	$area_avg = (int) $g( 'area_avg_price_aed', (int) $m( 'price' ) );
	$rental   = (array) $g( 'rental', array() );
	$yield    = (float) ( $rental['gross_yield_pct'] ?? 0 );
	$avg_rent = (int) ( $rental['avg_rent_yearly_aed'] ?? 0 );
	$rsource  = (string) ( $rental['source'] ?? '' );
	// Phase 2c: render these the moment arsha-connect's detail-sync provides them.
	$amenities = array_filter( array_map( 'strval', (array) $g( 'amenities', array() ) ) );
	$payplan   = $g( 'payment_plan', array() ); // array of milestones [{label,pct,amount}] or a string

	$calc_price = 0;
	foreach ( $ptmix as $row ) {
		if ( ! empty( $row['avg_price_aed'] ) ) { $calc_price = (int) $row['avg_price_aed']; break; }
	}
	if ( ! $calc_price ) { $calc_price = $area_avg ?: 2000000; }

	// Build the gallery image list (gallery → hero fallback).
	$imgs = array();
	foreach ( $gallery as $it ) {
		$u = (string) ( $it['url'] ?? '' );
		if ( $u ) { $imgs[] = array( 'url' => $u, 'thumb' => (string) ( $it['thumbnail'] ?? $u ) ); }
	}
	if ( ! $imgs && $hero ) { $imgs[] = array( 'url' => $hero, 'thumb' => $hero ); }
	$hero_bg = $imgs ? $imgs[0]['url'] : ( get_the_post_thumbnail_url( $pid, 'full' ) ?: '' );

	$aed = function ( $n ) { return 'AED ' . number_format_i18n( (float) $n ); };
	$name_q      = html_entity_decode( wp_strip_all_tags( $name ), ENT_QUOTES, 'UTF-8' );
	$contact_url = add_query_arg( 'project', rawurlencode( $name_q ), onyx_page_url( 'contact' ) );
	$archive_url = get_post_type_archive_link( LWP_ONYX_PROJECT_PT );
	?>

	<main class="pdp" data-onyx-proj data-price="<?php echo esc_attr( $calc_price ); ?>" data-yield="<?php echo esc_attr( $yield ?: 6 ); ?>">

		<!-- ============ CINEMATIC HERO ============ -->
		<section class="pdp-hero<?php echo $hero_bg ? ' has-img' : ''; ?>"<?php echo $hero_bg ? ' style="background-image:url(\'' . esc_url( $hero_bg ) . '\')"' : ''; ?>>
			<div class="pdp-hero-scrim"></div>
			<div class="wrap pdp-hero-in">
				<div class="crumb pdp-crumb">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'luwipress-onyx' ); ?></a><span class="sep">/</span>
					<a href="<?php echo esc_url( $archive_url ); ?>"><?php esc_html_e( 'Projects', 'luwipress-onyx' ); ?></a><span class="sep">/</span>
					<span><?php echo esc_html( $name ); ?></span>
				</div>
				<h1 class="pdp-hero-title"><?php echo esc_html( $name ); ?></h1>
				<div class="pdp-hero-chips">
					<?php if ( $area ) : ?><span class="pdp-chip"><?php echo onyx_icon( 'pin', 14 ); // phpcs:ignore ?> <?php echo esc_html( $area ); ?></span><?php endif; ?>
					<?php if ( $dev ) : ?><span class="pdp-chip"><?php echo esc_html( $dev ); ?><?php if ( $rating > 0 ) : ?> · ★ <?php echo esc_html( number_format( $rating, 1 ) ); ?><?php endif; ?></span><?php endif; ?>
					<?php if ( $status ) : ?><span class="pdp-chip pdp-chip--gold"><?php echo esc_html( $status ); ?></span><?php endif; ?>
				</div>
				<div class="pdp-hero-stats">
					<?php if ( $calc_price > 0 ) : ?><div class="pdp-stat"><span class="pdp-stat-v"><?php echo esc_html( $aed( $calc_price ) ); ?></span><span class="pdp-stat-l"><?php esc_html_e( 'Area avg price', 'luwipress-onyx' ); ?></span></div><?php endif; ?>
					<?php if ( $yield > 0 ) : ?><div class="pdp-stat"><span class="pdp-stat-v"><?php echo esc_html( number_format( $yield, 1 ) ); ?>%</span><span class="pdp-stat-l"><?php esc_html_e( 'Gross yield', 'luwipress-onyx' ); ?></span></div><?php endif; ?>
					<?php if ( $units > 0 ) : ?><div class="pdp-stat"><span class="pdp-stat-v"><?php echo esc_html( number_format_i18n( $units ) ); ?></span><span class="pdp-stat-l"><?php esc_html_e( 'Units', 'luwipress-onyx' ); ?></span></div><?php endif; ?>
					<?php if ( $handover ) : ?><div class="pdp-stat"><span class="pdp-stat-v"><?php echo esc_html( $handover ); ?></span><span class="pdp-stat-l"><?php esc_html_e( 'Handover', 'luwipress-onyx' ); ?></span></div><?php endif; ?>
					<?php if ( $progress > 0 ) : ?><div class="pdp-stat"><span class="pdp-stat-v"><?php echo esc_html( round( $progress ) ); ?>%</span><span class="pdp-stat-l"><?php esc_html_e( 'Built', 'luwipress-onyx' ); ?></span></div><?php endif; ?>
				</div>
				<div class="pdp-hero-cta">
					<a class="btn btn-gold" href="<?php echo esc_url( $contact_url ); ?>"><?php esc_html_e( 'Register interest', 'luwipress-onyx' ); ?> <span class="arr"><?php echo onyx_icon( 'arrow', 16 ); // phpcs:ignore ?></span></a>
					<a class="btn btn-ghost" href="<?php echo esc_url( $archive_url ); ?>"><?php esc_html_e( 'All projects', 'luwipress-onyx' ); ?></a>
				</div>
			</div>
		</section>

		<!-- ============ TABS ============ -->
		<section class="section pdp-tabsec">
			<div class="wrap">
				<nav class="pdp-tabs" data-onyx-tabs role="tablist">
					<button class="pdp-tab on" data-tab="overview" role="tab"><?php esc_html_e( 'Overview', 'luwipress-onyx' ); ?></button>
					<button class="pdp-tab" data-tab="market" role="tab"><?php esc_html_e( 'Market & valuation', 'luwipress-onyx' ); ?></button>
					<button class="pdp-tab" data-tab="calc" role="tab"><?php esc_html_e( 'Calculators', 'luwipress-onyx' ); ?></button>
					<?php if ( $lat && $lng ) : ?><button class="pdp-tab" data-tab="location" role="tab"><?php esc_html_e( 'Location', 'luwipress-onyx' ); ?></button><?php endif; ?>
				</nav>

				<!-- OVERVIEW -->
				<div class="pdp-panel on" data-panel="overview">
					<div class="pdp-facts">
						<?php
						$facts = array();
						if ( $status ) { $facts[] = array( __( 'Status', 'luwipress-onyx' ), $status ); }
						if ( $progress > 0 ) { $facts[] = array( __( 'Construction', 'luwipress-onyx' ), round( $progress ) . '%' ); }
						if ( $units > 0 ) { $facts[] = array( __( 'Total units', 'luwipress-onyx' ), number_format_i18n( $units ) ); }
						if ( $handover ) { $facts[] = array( __( 'Handover', 'luwipress-onyx' ), $handover ); }
						if ( $start ) { $facts[] = array( __( 'Started', 'luwipress-onyx' ), $start ); }
						if ( $reg ) { $facts[] = array( __( 'Registered', 'luwipress-onyx' ), $reg ); }
						if ( ! empty( $escrow['account'] ) ) { $facts[] = array( __( 'Escrow a/c', 'luwipress-onyx' ), $escrow['account'] ); }
						if ( ! empty( $escrow['agent'] ) ) { $facts[] = array( __( 'Escrow bank', 'luwipress-onyx' ), $escrow['agent'] ); }
						foreach ( $facts as $f ) : ?>
							<div class="pdp-fact"><span class="pdp-fact-l"><?php echo esc_html( $f[0] ); ?></span><span class="pdp-fact-v"><?php echo esc_html( $f[1] ); ?></span></div>
						<?php endforeach; ?>
					</div>

					<?php $bio = get_the_content(); if ( trim( wp_strip_all_tags( $bio ) ) !== '' ) : ?>
						<div class="pdp-bio"><?php the_content(); ?></div>
					<?php endif; ?>

					<?php if ( $amenities ) : ?>
					<h3 class="pdp-h3"><?php esc_html_e( 'Amenities', 'luwipress-onyx' ); ?></h3>
					<div class="pdp-amenities">
						<?php foreach ( $amenities as $am ) : ?>
							<span class="pdp-amenity"><?php echo esc_html( $am ); ?></span>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>

					<?php if ( $imgs ) : ?>
					<h3 class="pdp-h3"><?php esc_html_e( 'Gallery', 'luwipress-onyx' ); ?></h3>
					<div class="pdp-gallery" data-onyx-pgallery>
						<?php foreach ( $imgs as $k => $im ) : ?>
							<a class="pdp-gal-cell<?php echo 0 === $k ? ' pdp-gal-cell--lead' : ''; ?>" href="<?php echo esc_url( $im['url'] ); ?>" data-pg-item style="background-image:url('<?php echo esc_url( $im['thumb'] ); ?>')"><?php if ( 0 === $k ) : ?><span class="pdp-gal-count"><?php echo onyx_icon( 'search', 16 ); // phpcs:ignore ?> <?php echo (int) count( $imgs ); ?></span><?php endif; ?></a>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>
				</div>

				<!-- MARKET & VALUATION -->
				<div class="pdp-panel" data-panel="market" hidden>
					<div class="pdp-cards">
						<?php if ( $area_avg > 0 ) : ?>
						<div class="pdp-card"><span class="pdp-card-l"><?php esc_html_e( 'Area average price', 'luwipress-onyx' ); ?></span><span class="pdp-card-v"><?php echo esc_html( $aed( $area_avg ) ); ?></span><span class="pdp-card-s"><?php echo esc_html( $area ); ?></span></div>
						<?php endif; ?>
						<?php if ( ! empty( $txn['count'] ) ) : ?>
						<div class="pdp-card"><span class="pdp-card-l"><?php esc_html_e( 'Recorded transactions', 'luwipress-onyx' ); ?></span><span class="pdp-card-v"><?php echo esc_html( number_format_i18n( (int) $txn['count'] ) ); ?></span><span class="pdp-card-s"><?php echo esc_html( __( 'avg', 'luwipress-onyx' ) . ' ' . $aed( $txn['avg_value_aed'] ?? 0 ) ); ?></span></div>
						<?php endif; ?>
						<?php if ( $yield > 0 ) : ?>
						<div class="pdp-card pdp-card--gold"><span class="pdp-card-l"><?php esc_html_e( 'Gross rental yield', 'luwipress-onyx' ); ?></span><span class="pdp-card-v"><?php echo esc_html( number_format( $yield, 1 ) ); ?>%</span><span class="pdp-card-s"><?php echo $avg_rent ? esc_html( $aed( $avg_rent ) . '/' . __( 'yr', 'luwipress-onyx' ) ) : ''; echo ( 'official_portal' !== $rsource ) ? ' · ' . esc_html__( 'indicative', 'luwipress-onyx' ) : ''; ?></span></div>
						<?php endif; ?>
					</div>

					<?php if ( $ptmix ) : ?>
					<h3 class="pdp-h3"><?php esc_html_e( 'Unit mix', 'luwipress-onyx' ); ?></h3>
					<table class="pdp-table">
						<thead><tr><th><?php esc_html_e( 'Type', 'luwipress-onyx' ); ?></th><th><?php esc_html_e( 'Count', 'luwipress-onyx' ); ?></th><th><?php esc_html_e( 'Avg price', 'luwipress-onyx' ); ?></th></tr></thead>
						<tbody>
						<?php foreach ( $ptmix as $row ) : ?>
							<tr><td><?php echo esc_html( $row['type'] ?? '—' ); ?></td><td><?php echo esc_html( number_format_i18n( (int) ( $row['count'] ?? 0 ) ) ); ?></td><td><?php echo ! empty( $row['avg_price_aed'] ) ? esc_html( $aed( $row['avg_price_aed'] ) ) : '—'; ?></td></tr>
						<?php endforeach; ?>
						</tbody>
					</table>
					<?php endif; ?>
					<p class="pdp-note"><?php esc_html_e( 'Prices are DLD transaction averages (indicative), not developer list prices.', 'luwipress-onyx' ); ?></p>
				</div>

				<!-- CALCULATORS -->
				<div class="pdp-panel" data-panel="calc" hidden>
					<?php if ( ! empty( $payplan ) ) : ?>
					<div class="pdp-payplan">
						<h3 class="pdp-h3"><?php esc_html_e( 'Developer payment plan', 'luwipress-onyx' ); ?></h3>
						<?php if ( is_array( $payplan ) ) : ?>
							<table class="pdp-table">
								<tbody>
								<?php foreach ( $payplan as $row ) :
									if ( is_array( $row ) ) {
										$lbl = (string) ( $row['label'] ?? $row['milestone'] ?? '' );
										$val = isset( $row['pct'] ) ? ( $row['pct'] . '%' ) : ( isset( $row['amount'] ) ? $aed( $row['amount'] ) : '' );
									} else {
										$lbl = (string) $row; $val = '';
									}
									?>
									<tr><td><?php echo esc_html( $lbl ); ?></td><td style="text-align:right"><?php echo esc_html( $val ); ?></td></tr>
								<?php endforeach; ?>
								</tbody>
							</table>
						<?php else : ?>
							<p class="pdp-bio"><?php echo esc_html( (string) $payplan ); ?></p>
						<?php endif; ?>
					</div>
					<?php endif; ?>
					<div class="pdp-calc-wrap">
						<div class="pdp-calc" data-onyx-roi>
							<h3 class="pdp-h3"><?php esc_html_e( 'Investment / ROI', 'luwipress-onyx' ); ?></h3>
							<div class="pdp-calc-inputs">
								<label class="pdp-field"><span><?php esc_html_e( 'Purchase price (AED)', 'luwipress-onyx' ); ?></span><input type="number" data-roi="price" value="<?php echo esc_attr( $calc_price ); ?>" min="100000" step="50000"></label>
								<label class="pdp-field"><span><?php esc_html_e( 'Gross yield (%)', 'luwipress-onyx' ); ?></span><input type="number" data-roi="yield" value="<?php echo esc_attr( $yield ?: 6 ); ?>" min="0" max="20" step="0.1"></label>
								<label class="pdp-field"><span><?php esc_html_e( 'Occupancy (%)', 'luwipress-onyx' ); ?></span><input type="number" data-roi="occ" value="95" min="0" max="100" step="1"></label>
								<label class="pdp-field"><span><?php esc_html_e( 'Annual appreciation (%)', 'luwipress-onyx' ); ?></span><input type="number" data-roi="apx" value="5" min="0" max="20" step="0.5"></label>
							</div>
							<div class="pdp-calc-out">
								<div class="pdp-out"><span class="pdp-out-l"><?php esc_html_e( 'Net annual rent', 'luwipress-onyx' ); ?></span><span class="pdp-out-v" data-roi="rent">—</span></div>
								<div class="pdp-out"><span class="pdp-out-l"><?php esc_html_e( 'Net yield', 'luwipress-onyx' ); ?></span><span class="pdp-out-v" data-roi="netyield">—</span></div>
								<div class="pdp-out"><span class="pdp-out-l"><?php esc_html_e( 'Payback', 'luwipress-onyx' ); ?></span><span class="pdp-out-v" data-roi="payback">—</span></div>
								<div class="pdp-out pdp-out--big"><span class="pdp-out-l"><?php esc_html_e( '5-yr total return', 'luwipress-onyx' ); ?></span><span class="pdp-out-v" data-roi="total5">—</span></div>
							</div>
						</div>

						<div class="pdp-calc" data-onyx-plan>
							<h3 class="pdp-h3"><?php esc_html_e( 'Off-plan payment plan', 'luwipress-onyx' ); ?></h3>
							<div class="pdp-calc-inputs">
								<label class="pdp-field"><span><?php esc_html_e( 'Price (AED)', 'luwipress-onyx' ); ?></span><input type="number" data-plan="price" value="<?php echo esc_attr( $calc_price ); ?>" min="100000" step="50000"></label>
								<label class="pdp-field"><span><?php esc_html_e( 'Down payment (%)', 'luwipress-onyx' ); ?></span><input type="number" data-plan="down" value="20" min="0" max="100" step="5"></label>
								<label class="pdp-field"><span><?php esc_html_e( 'During construction (%)', 'luwipress-onyx' ); ?></span><input type="number" data-plan="during" value="50" min="0" max="100" step="5"></label>
								<label class="pdp-field"><span><?php esc_html_e( 'On handover (%)', 'luwipress-onyx' ); ?></span><input type="number" data-plan="handover" value="30" min="0" max="100" step="5"></label>
							</div>
							<div class="pdp-calc-out">
								<div class="pdp-out"><span class="pdp-out-l"><?php esc_html_e( 'Down payment', 'luwipress-onyx' ); ?></span><span class="pdp-out-v" data-plan="downv">—</span></div>
								<div class="pdp-out"><span class="pdp-out-l"><?php esc_html_e( 'During construction', 'luwipress-onyx' ); ?></span><span class="pdp-out-v" data-plan="duringv">—</span></div>
								<div class="pdp-out"><span class="pdp-out-l"><?php esc_html_e( 'On handover', 'luwipress-onyx' ); ?></span><span class="pdp-out-v" data-plan="handoverv">—</span></div>
								<div class="pdp-out"><span class="pdp-out-l" data-plan="sumlbl"><?php esc_html_e( 'Total', 'luwipress-onyx' ); ?></span><span class="pdp-out-v" data-plan="sumv">—</span></div>
							</div>
						</div>
					</div>
				</div>

				<!-- LOCATION -->
				<?php if ( $lat && $lng ) : ?>
				<div class="pdp-panel" data-panel="location" hidden>
					<div class="pdp-map">
						<iframe loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="<?php echo esc_attr( $name ); ?>" src="https://www.google.com/maps?q=<?php echo esc_attr( $lat . ',' . $lng ); ?>&z=14&output=embed"></iframe>
					</div>
				</div>
				<?php endif; ?>
			</div>
		</section>

		<!-- LIGHTBOX -->
		<div class="pdp-lightbox" data-pg-lightbox hidden>
			<button class="pdp-lb-close" data-pg-close aria-label="<?php esc_attr_e( 'Close', 'luwipress-onyx' ); ?>"><?php echo onyx_icon( 'close', 22 ); // phpcs:ignore ?></button>
			<button class="pdp-lb-nav pdp-lb-prev" data-pg-prev aria-label="<?php esc_attr_e( 'Previous', 'luwipress-onyx' ); ?>">‹</button>
			<img class="pdp-lb-img" data-pg-img src="" alt="">
			<button class="pdp-lb-nav pdp-lb-next" data-pg-next aria-label="<?php esc_attr_e( 'Next', 'luwipress-onyx' ); ?>">›</button>
		</div>
	</main>

	<?php
endwhile;

get_footer();
