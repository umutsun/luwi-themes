<?php
/**
 * Template Name: Sapphire — About
 *
 * Editorial About page (sapphire-page-about.jsx): story + lead, stats, three
 * principles, team grid, and a closing
 * lifestyle CTA. Yields to Elementor when the page is built with it.
 *
 * @package luwipress-sapphire
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$sapphire_pid = get_queried_object_id();
if ( $sapphire_pid && get_post_meta( $sapphire_pid, '_elementor_edit_mode', true ) && get_post_meta( $sapphire_pid, '_elementor_data', true ) ) {
	get_header();
	while ( have_posts() ) { the_post(); the_content(); }
	get_footer();
	return;
}

get_header();

$sapphire_contact = sapphire_page_url( 'contact' );
$sapphire_pricing = sapphire_page_url( 'pricing' );
?>

<main>
	<section class="phead">
		<div class="wrap">
			<div class="crumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'luwipress-sapphire' ); ?></a><span class="sep">/</span><span><?php esc_html_e( 'About', 'luwipress-sapphire' ); ?></span></div>
			<span class="smallcaps" style="color:var(--accent)"><?php esc_html_e( 'About Sapphire', 'luwipress-sapphire' ); ?></span>
			<h1 class="display-xl" style="margin-top:14px;font-size:clamp(44px,6vw,84px)"><?php esc_html_e( 'Built for teams that ship.', 'luwipress-sapphire' ); ?></h1>
			<p class="phead-sub"><?php esc_html_e( 'Sapphire started as an internal tool and grew into the platform thousands of teams now rely on to ship — every single day.', 'luwipress-sapphire' ); ?></p>
		</div>
	</section>

	<section class="section">
		<div class="wrap">
			<div class="about-hero">
				<div class="reveal">
					<p class="about-lead"><?php esc_html_e( 'We believe shipping should feel effortless — so we put product, docs, billing and analytics in one place.', 'luwipress-sapphire' ); ?></p>
					<p class="lede" style="margin-top:28px"><?php esc_html_e( 'Founded by engineers tired of stitching six tools together, Sapphire is built developer-first, secure by default, and designed to scale from a weekend project to thousands of seats — without rewrites.', 'luwipress-sapphire' ); ?></p>
				</div>
				<div class="reveal">
					<div class="app-mock app-mock--alt" aria-hidden="true">
						<div class="app-bar"><span class="app-dot"></span><span class="app-dot"></span><span class="app-dot"></span></div>
						<div class="app-body"><div class="app-main" style="width:100%">
							<div class="app-row"><span class="app-kpi"><b>99.99%</b><i><?php esc_html_e( 'uptime', 'luwipress-sapphire' ); ?></i></span><span class="app-kpi"><b>12k+</b><i><?php esc_html_e( 'teams', 'luwipress-sapphire' ); ?></i></span></div>
							<div class="app-chart"><i style="height:50%"></i><i style="height:68%"></i><i style="height:56%"></i><i style="height:82%"></i><i style="height:70%"></i><i style="height:95%"></i></div>
							<div class="app-list"><span></span><span></span><span></span></div>
						</div></div>
					</div>
				</div>
			</div>

			<div class="stats-row">
				<?php
				$sapphire_about_stats = array(
					array( 12, 'k+', __( 'Teams onboard', 'luwipress-sapphire' ) ),
					array( 100, '+', __( 'Integrations', 'luwipress-sapphire' ) ),
					array( 40, '+', __( 'Countries', 'luwipress-sapphire' ) ),
					array( 99, '%', __( 'Would recommend', 'luwipress-sapphire' ) ),
				);
				foreach ( $sapphire_about_stats as $st ) : ?>
					<div class="stat">
						<div class="tnum stat-num"><span data-countup="<?php echo (int) $st[0]; ?>"><?php echo (int) $st[0]; ?></span><span class="stat-suf"><?php echo esc_html( $st[1] ); ?></span></div>
						<div class="smallcaps stat-lbl"><?php echo esc_html( $st[2] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="section" style="background:var(--sapphire-850);border-top:1px solid var(--hair-soft);border-bottom:1px solid var(--hair-soft)">
		<div class="wrap">
			<div class="sec-head"><div class="sh-title">
				<span class="reveal" style="display:block"><?php echo sapphire_eyebrow( __( 'What we hold to', 'luwipress-sapphire' ) ); // phpcs:ignore ?></span>
				<h2 class="display-lg reveal"><?php esc_html_e( 'Three principles', 'luwipress-sapphire' ); ?></h2>
			</div></div>
			<div class="values">
				<?php
				$sapphire_values = array(
					array( '01', __( 'Developer-first', 'luwipress-sapphire' ), __( 'A typed API, webhooks and SDKs in every language. If your team can code it, Sapphire can automate it — no lock-in, no black boxes.', 'luwipress-sapphire' ) ),
					array( '02', __( 'Secure by default', 'luwipress-sapphire' ), __( 'SOC 2 Type II, SSO/SAML, encryption at rest and granular access control — ready for security review on day one, not as an afterthought.', 'luwipress-sapphire' ) ),
					array( '03', __( 'Built to scale', 'luwipress-sapphire' ), __( 'From a two-person startup to thousands of seats: 99.99% uptime, predictable pricing and an architecture that grows without rewrites.', 'luwipress-sapphire' ) ),
				);
				foreach ( $sapphire_values as $v ) : ?>
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
					<span class="reveal" style="display:block"><?php echo sapphire_eyebrow( __( 'The team', 'luwipress-sapphire' ) ); // phpcs:ignore ?></span>
					<h2 class="display-lg reveal"><?php esc_html_e( 'Meet the builders', 'luwipress-sapphire' ); ?></h2>
				</div>
				<a class="sh-link reveal" href="<?php echo esc_url( $sapphire_contact ); ?>"><?php esc_html_e( 'Join the team', 'luwipress-sapphire' ); ?> <?php echo sapphire_icon( 'arrow', 15 ); // phpcs:ignore ?></a>
			</div>
			<div class="team-grid">
				<?php
				$sapphire_team = array(
					array( 'Dana Whitfield', __( 'Founder & CEO', 'luwipress-sapphire' ) ),
					array( 'Marco Bellini', __( 'CTO', 'luwipress-sapphire' ) ),
					array( 'Priya Nair', __( 'Head of Product', 'luwipress-sapphire' ) ),
					array( 'Sam Okafor', __( 'Head of Customer', 'luwipress-sapphire' ) ),
				);
				foreach ( $sapphire_team as $m ) : ?>
					<div class="member reveal">
						<?php echo sapphire_ph( array( 'glyph' => 'plan', 'alt' => $m[0] ) ); // phpcs:ignore ?>
						<h4><?php echo esc_html( $m[0] ); ?></h4>
						<div class="role"><?php echo esc_html( $m[1] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="cta-band">
		<div class="grain"></div>
		<div class="wrap">
			<div class="reveal" style="display:flex;justify-content:center;margin-bottom:22px"><?php echo sapphire_eyebrow( __( 'Start today', 'luwipress-sapphire' ), true ); // phpcs:ignore ?></div>
			<h2 class="display-lg reveal"><?php esc_html_e( 'Ship your next release with Sapphire', 'luwipress-sapphire' ); ?></h2>
			<p class="reveal cta-sub"><?php esc_html_e( 'Free for individuals. A 14-day trial on every paid plan. No credit card required.', 'luwipress-sapphire' ); ?></p>
			<div class="hero-cta reveal" style="justify-content:center;margin-top:30px">
				<a class="btn btn-gold" href="<?php echo esc_url( $sapphire_pricing ); ?>"><?php esc_html_e( 'Start free', 'luwipress-sapphire' ); ?> <span class="arr"><?php echo sapphire_icon( 'rocket', 16 ); // phpcs:ignore ?></span></a>
				<a class="btn btn-ghost" href="<?php echo esc_url( $sapphire_contact ); ?>"><?php esc_html_e( 'Talk to sales', 'luwipress-sapphire' ); ?></a>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
