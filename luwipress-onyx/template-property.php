<?php
/**
 * Template Name: Onyx — Single Property
 *
 * A product-grade single-residence showcase: gallery + lightbox, spec bar,
 * amenities, floor plan, payment-plan timeline, interactive mortgage
 * calculator, sticky CTA bar, similar residences + Residence JSON-LD.
 * Demo content (properties stay as normal content for now). Yields to
 * Elementor when the page is built with it.
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

$onyx_contact  = onyx_page_url( 'contact' );
$onyx_listings = onyx_page_url( 'listings' );

$onyx_gal = array(
	array( 'interior', 'Living · dusk' ),
	array( 'interior', 'Reception gallery' ),
	array( 'interior', 'Principal suite' ),
	array( 'tower', 'Terrace' ),
	array( 'interior', 'Kitchen' ),
);
$onyx_specs = array(
	array( 'bed', 'Bedrooms', '4' ),
	array( 'ruler', 'Internal area', '412 m²' ),
	array( 'floor', 'Floor', '58—60' ),
	array( 'car', 'Parking', '3' ),
	array( 'pin', 'Location', 'Downtown' ),
);
$onyx_amen = array(
	array( 'Infinity pool', 'Temperature-controlled, on the 60th floor' ),
	array( 'Private spa', 'Hammam, sauna and treatment suite' ),
	array( '24/7 concierge', 'Lobby and in-residence service' ),
	array( 'Sky lounge', 'Residents-only, framing the Burj' ),
	array( 'Smart home', 'Lighting, climate and access, controlled' ),
	array( 'Private lift', 'Direct, key-secured arrival' ),
);
$onyx_plan = array(
	array( '10%', 'On booking', 'Reservation & SPA signing' ),
	array( '40%', 'During build', 'Across construction milestones' ),
	array( '40%', 'On handover', 'Keys & registration' ),
	array( '10%', 'Post-handover', '12 months after handover' ),
);

$onyx_residence_schema = array(
	'@context'      => 'https://schema.org',
	'@type'         => 'Residence',
	'name'          => 'The Onyx Penthouse',
	'description'   => 'A triplex penthouse at the summit of Burj Vista, Downtown Dubai.',
	'address'       => array( '@type' => 'PostalAddress', 'addressLocality' => 'Downtown Dubai', 'addressCountry' => 'AE' ),
	'numberOfRooms' => 4,
	'floorSize'     => array( '@type' => 'QuantitativeValue', 'value' => 412, 'unitCode' => 'MTK' ),
	'offers'        => array( '@type' => 'Offer', 'price' => 14800000, 'priceCurrency' => 'AED' ),
);
?>
<script type="application/ld+json"><?php echo wp_json_encode( $onyx_residence_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?></script>

<main>
	<!-- sticky CTA -->
	<div class="scta">
		<div class="wrap scta-in">
			<div class="scta-info">
				<span class="scta-name"><?php esc_html_e( 'The Onyx Penthouse', 'luwipress-onyx' ); ?></span>
				<span class="scta-price"><small><?php esc_html_e( 'From', 'luwipress-onyx' ); ?></small>AED 14.8M</span>
			</div>
			<div class="scta-btns">
				<a class="btn btn-ghost" href="tel:0567761946"><?php esc_html_e( 'Call advisor', 'luwipress-onyx' ); ?></a>
				<a class="btn btn-gold" href="<?php echo esc_url( $onyx_contact ); ?>"><?php esc_html_e( 'Book a viewing', 'luwipress-onyx' ); ?> <span class="arr"><?php echo onyx_icon( 'arrow', 16 ); // phpcs:ignore ?></span></a>
			</div>
		</div>
	</div>

	<section class="phead">
		<div class="wrap">
			<div class="crumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'luwipress-onyx' ); ?></a><span class="sep">/</span>
				<a href="<?php echo esc_url( $onyx_listings ); ?>"><?php esc_html_e( 'Listings', 'luwipress-onyx' ); ?></a><span class="sep">/</span>
				<span><?php esc_html_e( 'The Onyx Penthouse', 'luwipress-onyx' ); ?></span>
			</div>
			<div class="phead-row">
				<div>
					<span class="smallcaps" style="color:var(--gold)"><?php esc_html_e( 'Penthouse · Downtown Dubai', 'luwipress-onyx' ); ?></span>
					<h1 class="display-lg" style="margin-top:14px"><?php esc_html_e( 'The Onyx Penthouse', 'luwipress-onyx' ); ?></h1>
					<p class="phead-sub" style="display:flex;align-items:center;gap:10px"><?php echo onyx_icon( 'pin', 15 ); // phpcs:ignore ?> <?php esc_html_e( 'Burj Vista, Downtown Dubai — facing the fountain', 'luwipress-onyx' ); ?></p>
				</div>
				<div class="price-block">
					<span class="smallcaps"><?php esc_html_e( 'Price from', 'luwipress-onyx' ); ?></span>
					<div class="pv">AED 14.8M</div>
				</div>
			</div>
		</div>
	</section>

	<section class="section" style="padding-top:clamp(28px,4vw,48px)">
		<div class="wrap">
			<!-- gallery + lightbox -->
			<div data-onyx-gallery-lb>
				<div class="gal">
					<div class="gal-main">
						<span class="gal-tag"><?php echo esc_html( $onyx_gal[0][1] ); ?></span>
						<button class="gal-zoom" type="button" aria-label="<?php esc_attr_e( 'Expand', 'luwipress-onyx' ); ?>"><?php echo onyx_icon( 'search', 16 ); // phpcs:ignore ?></button>
						<?php echo onyx_ph( array( 'glyph' => $onyx_gal[0][0] ) ); // phpcs:ignore ?>
					</div>
					<div class="gal-side">
						<?php for ( $i = 0; $i < 3; $i++ ) : ?>
							<div class="gal-thumb<?php echo 0 === $i ? ' on' : ''; ?>">
								<?php echo onyx_ph( array( 'glyph' => $onyx_gal[ $i ][0] ) ); // phpcs:ignore ?>
								<?php if ( 2 === $i ) : ?><div class="gal-more">+<?php echo (int) ( count( $onyx_gal ) - 2 ); ?></div><?php endif; ?>
							</div>
						<?php endfor; ?>
					</div>
				</div>
				<!-- source list for the lightbox (placeholders) -->
				<div style="display:none" aria-hidden="true">
					<?php foreach ( $onyx_gal as $g ) : ?>
						<span data-gal-item="<?php echo esc_attr( $g[0] ); ?>" data-gal-tag="<?php echo esc_attr( $g[1] ); ?>"></span>
					<?php endforeach; ?>
				</div>
				<div class="lightbox">
					<button class="lb-close" type="button" aria-label="<?php esc_attr_e( 'Close', 'luwipress-onyx' ); ?>"><?php echo onyx_icon( 'close', 22 ); // phpcs:ignore ?></button>
					<button class="lb-nav lb-prev" type="button" aria-label="<?php esc_attr_e( 'Previous', 'luwipress-onyx' ); ?>" style="transform:translateY(-50%) rotate(180deg)"><?php echo onyx_icon( 'arrow', 20 ); // phpcs:ignore ?></button>
					<div class="lb-stage"></div>
					<button class="lb-nav lb-next" type="button" aria-label="<?php esc_attr_e( 'Next', 'luwipress-onyx' ); ?>"><?php echo onyx_icon( 'arrow', 20 ); // phpcs:ignore ?></button>
					<div class="lb-count">01 / <?php echo esc_html( str_pad( (string) count( $onyx_gal ), 2, '0', STR_PAD_LEFT ) ); ?></div>
				</div>
			</div>

			<div class="specbar">
				<?php foreach ( $onyx_specs as $s ) : ?>
					<div class="sb">
						<span class="smallcaps"><span class="ic"><?php echo onyx_icon( $s[0], 15 ); // phpcs:ignore ?></span><?php echo esc_html( $s[1] ); ?></span>
						<div class="v"><?php echo esc_html( $s[2] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="prop-body" style="margin-top:clamp(50px,6vw,90px)">
				<div class="prop-desc reveal">
					<?php echo onyx_eyebrow( __( 'The Residence', 'luwipress-onyx' ) ); // phpcs:ignore ?>
					<h2 class="display-md" style="margin-top:18px"><?php esc_html_e( 'A whole floor, given to the light.', 'luwipress-onyx' ); ?></h2>
					<p><?php esc_html_e( 'The Onyx Penthouse occupies three connected levels at the summit of Burj Vista. Full-height glass wraps the principal rooms, so the Burj Khalifa and the fountain become part of the interior rather than a view to be framed.', 'luwipress-onyx' ); ?></p>
					<p><?php esc_html_e( 'Interiors are finished in honed travertine, smoked oak and brushed brass — a restrained palette chosen to age quietly. A private lift opens directly into the reception gallery; staff quarters, a wine room and a media lounge sit discreetly behind.', 'luwipress-onyx' ); ?></p>

					<h2 class="display-md" style="margin:52px 0 0"><?php esc_html_e( 'Amenities', 'luwipress-onyx' ); ?></h2>
					<div class="amen-grid">
						<?php foreach ( $onyx_amen as $a ) : ?>
							<div class="amen">
								<span class="ic"><?php echo onyx_icon( 'check', 18 ); // phpcs:ignore ?></span>
								<div><div class="at"><?php echo esc_html( $a[0] ); ?></div><div class="ad"><?php echo esc_html( $a[1] ); ?></div></div>
							</div>
						<?php endforeach; ?>
					</div>

					<h2 class="display-md" style="margin:52px 0 24px"><?php esc_html_e( 'Floor plan', 'luwipress-onyx' ); ?></h2>
					<?php echo onyx_ph( array( 'glyph' => 'plan', 'tag' => __( 'Level 58 — principal floor', 'luwipress-onyx' ), 'style' => 'aspect-ratio:16/9' ) ); // phpcs:ignore ?>
				</div>

				<aside class="prop-aside reveal">
					<h4><?php esc_html_e( 'At a glance', 'luwipress-onyx' ); ?></h4>
					<?php
					$onyx_facts = array(
						array( 'Type', 'Triplex Penthouse' ),
						array( 'Bedrooms', '4 + Staff' ),
						array( 'Bathrooms', '5' ),
						array( 'Internal', '412 m²' ),
						array( 'Terrace', '88 m²' ),
						array( 'Handover', 'Ready' ),
						array( 'Reference', 'AH-0581' ),
					);
					foreach ( $onyx_facts as $f ) : ?>
						<div class="fact"><span><?php echo esc_html( $f[0] ); ?></span><span><?php echo esc_html( $f[1] ); ?></span></div>
					<?php endforeach; ?>
					<a class="btn btn-gold" href="<?php echo esc_url( $onyx_contact ); ?>"><?php esc_html_e( 'Book a private viewing', 'luwipress-onyx' ); ?> <span class="arr"><?php echo onyx_icon( 'arrow', 16 ); // phpcs:ignore ?></span></a>
				</aside>
			</div>
		</div>
	</section>

	<section class="section" style="background:var(--onyx-850);border-top:1px solid var(--hair-soft);border-bottom:1px solid var(--hair-soft)">
		<div class="wrap">
			<div class="sec-head" style="margin-bottom:clamp(24px,3vw,40px)"><div class="sh-title">
				<span class="reveal" style="display:block"><?php echo onyx_eyebrow( __( 'Payment Plan', 'luwipress-onyx' ) ); // phpcs:ignore ?></span>
				<h2 class="display-md reveal"><?php esc_html_e( 'A clear, staged path', 'luwipress-onyx' ); ?></h2>
			</div></div>
			<div class="plan-tl reveal">
				<?php foreach ( $onyx_plan as $k => $s ) : ?>
					<div class="plan-step<?php echo $k < 1 ? ' fill' : ''; ?>">
						<div class="node"></div>
						<div class="pp"><?php echo esc_html( $s[0] ); ?></div>
						<div class="pl"><?php echo esc_html( $s[1] ); ?></div>
						<div class="pd"><?php echo esc_html( $s[2] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="sec-head" style="margin:clamp(56px,7vw,90px) 0 clamp(24px,3vw,40px)"><div class="sh-title">
				<span class="reveal" style="display:block"><?php echo onyx_eyebrow( __( 'Mortgage Estimate', 'luwipress-onyx' ) ); // phpcs:ignore ?></span>
				<h2 class="display-md reveal"><?php esc_html_e( 'What it could cost monthly', 'luwipress-onyx' ); ?></h2>
			</div></div>
			<div class="calc reveal" data-onyx-calc>
				<div class="calc-controls">
					<div class="cc">
						<div class="cc-top"><span class="cl"><?php esc_html_e( 'Property price', 'luwipress-onyx' ); ?></span><span class="cv" data-calc="lbl-price">AED 14,800,000</span></div>
						<input class="cslider" type="range" min="1000000" max="35000000" step="100000" value="14800000" data-calc="price" aria-label="<?php esc_attr_e( 'Property price', 'luwipress-onyx' ); ?>">
					</div>
					<div class="cc">
						<div class="cc-top"><span class="cl"><?php esc_html_e( 'Down payment', 'luwipress-onyx' ); ?></span><span class="cv" data-calc="lbl-dp">25%</span></div>
						<input class="cslider" type="range" min="20" max="60" step="1" value="25" data-calc="dp" aria-label="<?php esc_attr_e( 'Down payment', 'luwipress-onyx' ); ?>">
					</div>
					<div class="cc">
						<div class="cc-top"><span class="cl"><?php esc_html_e( 'Term', 'luwipress-onyx' ); ?></span><span class="cv" data-calc="lbl-years">20 years</span></div>
						<input class="cslider" type="range" min="5" max="25" step="1" value="20" data-calc="years" aria-label="<?php esc_attr_e( 'Term', 'luwipress-onyx' ); ?>">
					</div>
					<div class="cc">
						<div class="cc-top"><span class="cl"><?php esc_html_e( 'Interest rate', 'luwipress-onyx' ); ?></span><span class="cv" data-calc="lbl-rate">4.0%</span></div>
						<input class="cslider" type="range" min="2.5" max="6" step="0.1" value="4" data-calc="rate" aria-label="<?php esc_attr_e( 'Interest rate', 'luwipress-onyx' ); ?>">
					</div>
				</div>
				<div class="calc-result">
					<span class="smallcaps"><?php esc_html_e( 'Estimated monthly', 'luwipress-onyx' ); ?></span>
					<div class="cr-big" data-calc="monthly">AED 0</div>
					<div class="cr-sub" data-calc="sub"></div>
					<div class="cr-note"><?php esc_html_e( 'Indicative only. Actual terms depend on lender, residency and eligibility. We introduce clients to private mortgage advisors.', 'luwipress-onyx' ); ?></div>
				</div>
			</div>
		</div>
	</section>

	<section class="section">
		<div class="wrap">
			<div class="sec-head">
				<div class="sh-title">
					<span class="reveal" style="display:block"><?php echo onyx_eyebrow( __( 'Also Available', 'luwipress-onyx' ) ); // phpcs:ignore ?></span>
					<h2 class="display-lg reveal"><?php esc_html_e( 'Similar residences', 'luwipress-onyx' ); ?></h2>
				</div>
				<a class="sh-link reveal" href="<?php echo esc_url( $onyx_listings ); ?>"><?php esc_html_e( 'View all', 'luwipress-onyx' ); ?> <?php echo onyx_icon( 'arrow', 15 ); // phpcs:ignore ?></a>
			</div>
			<div class="pgrid reveal">
				<?php
				$onyx_similar = array(
					array( 'Penthouse', 'The Onyx Penthouse', 'Downtown', '4 Bed', '412 m²', 'AED 14.8M', 'interior' ),
					array( 'Duplex', 'Marsa Duplex 41', 'Business Bay', '3 Bed', '268 m²', 'AED 6.9M', 'tower' ),
					array( 'Studio', 'Atelier Studio', 'DIFC', 'Studio', '54 m²', 'AED 1.2M', 'exterior' ),
				);
				foreach ( $onyx_similar as $p ) : ?>
					<a class="pcard" href="<?php echo esc_url( $onyx_listings ); ?>">
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
