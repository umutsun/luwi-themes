<?php
/**
 * Template Name: Onyx — About
 *
 * Editorial About page (onyx-page-about.jsx): story + lead, stats, three
 * principles, advisor team grid (founder Ayhan Sahin), and a closing
 * lifestyle CTA. Yields to Elementor when the page is built with it.
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

$onyx_contact = onyx_page_url( 'contact' );
$onyx_ayhan   = get_template_directory_uri() . '/assets/ayhan.jpg';
?>

<main>
	<section class="phead">
		<div class="wrap">
			<div class="crumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'luwipress-onyx' ); ?></a><span class="sep">/</span><span><?php esc_html_e( 'About', 'luwipress-onyx' ); ?></span></div>
			<span class="smallcaps" style="color:var(--gold)"><?php esc_html_e( 'About Company', 'luwipress-onyx' ); ?></span>
			<h1 class="display-xl" style="margin-top:14px;font-size:clamp(44px,6vw,84px)"><?php esc_html_e( 'Quiet by design.', 'luwipress-onyx' ); ?></h1>
			<p class="phead-sub"><?php esc_html_e( 'Since 2016, ArshaHomes has placed premium residences across the UAE for clients who value privacy as much as the property itself.', 'luwipress-onyx' ); ?></p>
		</div>
	</section>

	<section class="section">
		<div class="wrap">
			<div class="about-hero">
				<div class="reveal">
					<p class="about-lead"><?php esc_html_e( "We don't sell listings. We place residences — one client, one home, one conversation at a time.", 'luwipress-onyx' ); ?></p>
					<p class="lede" style="margin-top:28px"><?php esc_html_e( 'Founded in Business Bay, ArshaHomes grew by referral rather than advertising. Our work is to understand how a client wants to live, then to find — or quietly secure — the address that fits. No volume, no theatre, no noise.', 'luwipress-onyx' ); ?></p>
				</div>
				<div class="reveal"><?php echo onyx_ph( array( 'glyph' => 'interior' ) ); // phpcs:ignore ?></div>
			</div>

			<div class="stats-row">
				<?php
				$onyx_about_stats = array(
					array( 4, 'B', __( 'AED placed', 'luwipress-onyx' ) ),
					array( 366, '', __( 'Residences sold', 'luwipress-onyx' ) ),
					array( 10, '', __( 'Years in Dubai', 'luwipress-onyx' ) ),
					array( 98, '%', __( 'Client retention', 'luwipress-onyx' ) ),
				);
				foreach ( $onyx_about_stats as $st ) : ?>
					<div class="stat">
						<div class="tnum stat-num"><span data-countup="<?php echo (int) $st[0]; ?>"><?php echo (int) $st[0]; ?></span><span class="stat-suf"><?php echo esc_html( $st[1] ); ?></span></div>
						<div class="smallcaps stat-lbl"><?php echo esc_html( $st[2] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="section" style="background:var(--onyx-850);border-top:1px solid var(--hair-soft);border-bottom:1px solid var(--hair-soft)">
		<div class="wrap">
			<div class="sec-head"><div class="sh-title">
				<span class="reveal" style="display:block"><?php echo onyx_eyebrow( __( 'What We Hold To', 'luwipress-onyx' ) ); // phpcs:ignore ?></span>
				<h2 class="display-lg reveal"><?php esc_html_e( 'Three principles', 'luwipress-onyx' ); ?></h2>
			</div></div>
			<div class="values">
				<?php
				$onyx_values = array(
					array( '01', __( 'Discretion', 'luwipress-onyx' ), __( 'We sell the way private banks advise — quietly, off the record, and never with pressure. Most of our finest residences are never publicly listed.', 'luwipress-onyx' ) ),
					array( '02', __( 'Precision', 'luwipress-onyx' ), __( 'Every detail is considered before you ask. Floor plans, fees, handover timelines and paperwork arrive complete, accurate and on time.', 'luwipress-onyx' ) ),
					array( '03', __( 'Permanence', 'luwipress-onyx' ), __( 'We advise on homes meant to be kept, not flipped. Architecture, materials and location chosen to hold their value and their calm.', 'luwipress-onyx' ) ),
				);
				foreach ( $onyx_values as $v ) : ?>
					<div class="value reveal">
						<div class="hair-gold"></div>
						<div class="vnum"><?php echo esc_html( $v[0] ); ?></div>
						<h3><?php echo esc_html( $v[1] ); ?></h3>
						<p><?php echo esc_html( $v[2] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="section">
		<div class="wrap">
			<div class="sec-head">
				<div class="sh-title">
					<span class="reveal" style="display:block"><?php echo onyx_eyebrow( __( 'The People', 'luwipress-onyx' ) ); // phpcs:ignore ?></span>
					<h2 class="display-lg reveal"><?php esc_html_e( 'Your advisors', 'luwipress-onyx' ); ?></h2>
				</div>
				<a class="sh-link reveal" href="<?php echo esc_url( $onyx_contact ); ?>"><?php esc_html_e( 'Work with us', 'luwipress-onyx' ); ?> <?php echo onyx_icon( 'arrow', 15 ); // phpcs:ignore ?></a>
			</div>
			<div class="team-grid">
				<?php
				$onyx_team = array(
					array( 'Ayhan Sahin', __( 'Founder & Principal', 'luwipress-onyx' ), $onyx_ayhan ),
					array( 'Layla Mansour', __( 'Head of Acquisitions', 'luwipress-onyx' ), '' ),
					array( 'Daniel Park', __( 'Private Client Advisor', 'luwipress-onyx' ), '' ),
					array( 'Noor Rahman', __( 'Client Relations', 'luwipress-onyx' ), '' ),
				);
				$tg = array( 'portrait', 'interior', 'tower', 'exterior' );
				foreach ( $onyx_team as $i => $m ) : ?>
					<div class="member reveal">
						<?php echo onyx_ph( array( 'glyph' => 'portrait', 'img' => $m[2], 'alt' => $m[0] ) ); // phpcs:ignore ?>
						<h4><?php echo esc_html( $m[0] ); ?></h4>
						<div class="role"><?php echo esc_html( $m[1] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="life" style="padding:clamp(80px,11vh,140px) 0">
		<div class="life-bg"><?php echo onyx_ph( array( 'glyph' => 'tower', 'style' => 'width:100%;height:100%' ) ); // phpcs:ignore ?></div>
		<div class="life-veil"></div>
		<div class="grain"></div>
		<div class="wrap" style="text-align:center">
			<div class="reveal" style="display:flex;justify-content:center;margin-bottom:22px"><?php echo onyx_eyebrow( __( 'Begin', 'luwipress-onyx' ), true ); // phpcs:ignore ?></div>
			<h2 class="display-lg reveal"><?php esc_html_e( 'A residence, not a listing.', 'luwipress-onyx' ); ?></h2>
			<div class="reveal" style="display:flex;justify-content:center;margin-top:38px">
				<a class="btn btn-gold" href="<?php echo esc_url( $onyx_contact ); ?>"><?php esc_html_e( 'Start a private conversation', 'luwipress-onyx' ); ?> <span class="arr"><?php echo onyx_icon( 'arrow', 16 ); // phpcs:ignore ?></span></a>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
