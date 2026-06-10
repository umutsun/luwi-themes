<?php
/**
 * Template Name: Sapphire — Single Plan
 *
 * A single-plan detail page: plan summary aside, product app-mock, a
 * what's-included checklist, an "Everything in Pro" feature grid, a sticky
 * "Start free trial" CTA and SoftwareApplication JSON-LD. Yields to Elementor
 * when the page is built with it.
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

$sapphire_included = array(
	array( 'Unlimited projects', 'No caps — spin up as many workspaces as your team needs' ),
	array( 'All 100+ integrations', 'Slack, GitHub, Stripe, Linear and the rest of your stack' ),
	array( 'Advanced analytics', 'Dashboards, funnels and CSV export, no data team required' ),
	array( 'API & webhooks', 'A fully typed API with SDKs in every language you ship in' ),
	array( 'Realtime collaboration', 'Live cursors, comments and presence across the workspace' ),
	array( 'Priority support', 'Human help in minutes, not days — plus a private Slack channel' ),
);
$sapphire_app_schema = array(
	'@context'            => 'https://schema.org',
	'@type'               => 'SoftwareApplication',
	'name'                => 'Sapphire Pro',
	'description'         => 'The Pro plan of Sapphire — one platform for product, docs, billing and analytics, built for teams that ship.',
	'applicationCategory' => 'BusinessApplication',
	'operatingSystem'     => 'Web, iOS, Android',
	'offers'              => array( '@type' => 'Offer', 'price' => '24', 'priceCurrency' => 'USD' ),
	'aggregateRating'     => array( '@type' => 'AggregateRating', 'ratingValue' => '4.9', 'ratingCount' => '1280' ),
);
?>
<script type="application/ld+json"><?php echo wp_json_encode( $sapphire_app_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?></script>

<main>
	<!-- sticky CTA -->
	<div class="scta">
		<div class="wrap scta-in">
			<div class="scta-info">
				<span class="scta-name"><?php esc_html_e( 'Sapphire Pro', 'luwipress-sapphire' ); ?></span>
				<span class="scta-price"><small><?php esc_html_e( 'From', 'luwipress-sapphire' ); ?></small>$24 <?php esc_html_e( '/seat/mo', 'luwipress-sapphire' ); ?></span>
			</div>
			<div class="scta-btns">
				<a class="btn btn-ghost" href="<?php echo esc_url( $sapphire_pricing ); ?>"><?php esc_html_e( 'Compare plans', 'luwipress-sapphire' ); ?></a>
				<a class="btn btn-gold" href="<?php echo esc_url( $sapphire_contact ); ?>"><?php esc_html_e( 'Start free trial', 'luwipress-sapphire' ); ?> <span class="arr"><?php echo sapphire_icon( 'arrow', 16 ); // phpcs:ignore ?></span></a>
			</div>
		</div>
	</div>

	<section class="phead">
		<div class="wrap">
			<div class="crumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'luwipress-sapphire' ); ?></a><span class="sep">/</span>
				<a href="<?php echo esc_url( $sapphire_pricing ); ?>"><?php esc_html_e( 'Pricing', 'luwipress-sapphire' ); ?></a><span class="sep">/</span>
				<span><?php esc_html_e( 'Sapphire Pro', 'luwipress-sapphire' ); ?></span>
			</div>
			<div class="phead-row">
				<div>
					<span class="smallcaps" style="color:var(--accent)"><?php esc_html_e( 'Plan · Most popular', 'luwipress-sapphire' ); ?></span>
					<h1 class="display-lg" style="margin-top:14px"><?php esc_html_e( 'Sapphire Pro', 'luwipress-sapphire' ); ?></h1>
					<p class="phead-sub" style="display:flex;align-items:center;gap:10px"><?php echo sapphire_icon( 'sparkle', 15 ); // phpcs:ignore ?> <?php esc_html_e( 'Everything a growing team needs to ship weekly — with a 14-day free trial.', 'luwipress-sapphire' ); ?></p>
				</div>
				<div class="price-block">
					<span class="smallcaps"><?php esc_html_e( 'From', 'luwipress-sapphire' ); ?></span>
					<div class="pv">$24<span style="font-size:16px;color:var(--faint)"> <?php esc_html_e( '/seat/mo', 'luwipress-sapphire' ); ?></span></div>
				</div>
			</div>
		</div>
	</section>

	<section class="section" style="padding-top:clamp(28px,4vw,48px)">
		<div class="wrap">
			<div class="prop-body">
				<div class="prop-desc reveal">
					<div class="app-mock" style="max-width:100%;aspect-ratio:16/8;margin-bottom:clamp(32px,4vw,52px)" aria-hidden="true">
						<div class="app-bar"><span class="app-dot"></span><span class="app-dot"></span><span class="app-dot"></span><span class="app-url">app.sapphire.dev</span></div>
						<div class="app-body">
							<div class="app-side"><span class="app-nav on"></span><span class="app-nav"></span><span class="app-nav"></span><span class="app-nav"></span></div>
							<div class="app-main">
								<div class="app-row"><span class="app-kpi"><b>99.99%</b><i>uptime</i></span><span class="app-kpi"><b>1.2M</b><i>events/day</i></span><span class="app-kpi"><b>+18%</b><i>this week</i></span></div>
								<div class="app-chart"><i style="height:42%"></i><i style="height:60%"></i><i style="height:50%"></i><i style="height:78%"></i><i style="height:66%"></i><i style="height:92%"></i><i style="height:74%"></i></div>
							</div>
						</div>
					</div>
					<?php echo sapphire_eyebrow( __( 'The plan', 'luwipress-sapphire' ) ); // phpcs:ignore ?>
					<h2 class="display-md" style="margin-top:18px"><?php esc_html_e( 'Built for teams that ship weekly', 'luwipress-sapphire' ); ?></h2>
					<p><?php esc_html_e( 'Pro unlocks the full Sapphire platform — unlimited projects, every integration, advanced analytics and a typed API — so your team works from one source of truth instead of stitching six tools together.', 'luwipress-sapphire' ); ?></p>
					<p><?php esc_html_e( 'Start with a 14-day trial, no credit card. Upgrade, downgrade or cancel anytime; you only pay for active seats.', 'luwipress-sapphire' ); ?></p>

					<h2 class="display-md" style="margin:52px 0 0"><?php esc_html_e( "What's included", 'luwipress-sapphire' ); ?></h2>
					<div class="amen-grid">
						<?php foreach ( $sapphire_included as $a ) : ?>
							<div class="amen">
								<span class="ic"><?php echo sapphire_icon( 'check', 18 ); // phpcs:ignore ?></span>
								<div><div class="at"><?php echo esc_html( $a[0] ); ?></div><div class="ad"><?php echo esc_html( $a[1] ); ?></div></div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<aside class="prop-aside reveal">
					<h4><?php esc_html_e( 'Plan summary', 'luwipress-sapphire' ); ?></h4>
					<?php
					$sapphire_facts = array(
						array( __( 'Plan', 'luwipress-sapphire' ), 'Pro' ),
						array( __( 'Price', 'luwipress-sapphire' ), '$24 /seat/mo' ),
						array( __( 'Billing', 'luwipress-sapphire' ), __( 'Monthly or annual', 'luwipress-sapphire' ) ),
						array( __( 'Seats', 'luwipress-sapphire' ), __( 'Up to 50', 'luwipress-sapphire' ) ),
						array( __( 'Projects', 'luwipress-sapphire' ), __( 'Unlimited', 'luwipress-sapphire' ) ),
						array( __( 'Trial', 'luwipress-sapphire' ), __( '14 days free', 'luwipress-sapphire' ) ),
						array( __( 'Support', 'luwipress-sapphire' ), __( 'Priority', 'luwipress-sapphire' ) ),
					);
					foreach ( $sapphire_facts as $f ) : ?>
						<div class="fact"><span><?php echo esc_html( $f[0] ); ?></span><span><?php echo esc_html( $f[1] ); ?></span></div>
					<?php endforeach; ?>
					<a class="btn btn-gold" href="<?php echo esc_url( $sapphire_contact ); ?>"><?php esc_html_e( 'Start free trial', 'luwipress-sapphire' ); ?> <span class="arr"><?php echo sapphire_icon( 'arrow', 16 ); // phpcs:ignore ?></span></a>
				</aside>
			</div>
		</div>
	</section>

	<section class="section" style="background:var(--sapphire-850);border-top:1px solid var(--hair-soft);border-bottom:1px solid var(--hair-soft)">
		<div class="wrap">
			<div class="sec-head" style="flex-direction:column;align-items:flex-start;gap:14px;margin-bottom:clamp(24px,3vw,40px)">
				<span class="reveal" style="display:block"><?php echo sapphire_eyebrow( __( 'Why Pro', 'luwipress-sapphire' ) ); // phpcs:ignore ?></span>
				<h2 class="display-lg reveal"><?php esc_html_e( 'Everything in Pro', 'luwipress-sapphire' ); ?></h2>
			</div>
			<?php
			$sapphire_pro_features = array(
				array( 'zap',    __( 'Realtime by default', 'luwipress-sapphire' ),  __( 'Presence, live cursors and instant sync across every workspace.', 'luwipress-sapphire' ) ),
				array( 'plug',   __( 'All 100+ integrations', 'luwipress-sapphire' ), __( 'Connect your whole stack in two clicks — no glue code.', 'luwipress-sapphire' ) ),
				array( 'chart',  __( 'Advanced analytics', 'luwipress-sapphire' ),   __( 'Dashboards, funnels and CSV export, ready when you are.', 'luwipress-sapphire' ) ),
				array( 'code',   __( 'API & webhooks', 'luwipress-sapphire' ),       __( 'A typed REST surface and SDKs for every language.', 'luwipress-sapphire' ) ),
				array( 'shield', __( 'Roles & permissions', 'luwipress-sapphire' ),  __( 'Granular access control so the right people see the right thing.', 'luwipress-sapphire' ) ),
				array( 'star',   __( 'Priority support', 'luwipress-sapphire' ),     __( 'Human help in minutes, plus a private Slack channel.', 'luwipress-sapphire' ) ),
			);
			?>
			<div class="fgrid">
				<?php foreach ( $sapphire_pro_features as $f ) : ?>
					<div class="fcard reveal">
						<span class="fcard-ic"><?php echo sapphire_icon( $f[0], 22 ); // phpcs:ignore ?></span>
						<h3><?php echo esc_html( $f[1] ); ?></h3>
						<p><?php echo esc_html( $f[2] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="cta-band">
		<div class="grain"></div>
		<div class="wrap">
			<div class="reveal" style="display:flex;justify-content:center;margin-bottom:22px"><?php echo sapphire_eyebrow( __( 'Ready when you are', 'luwipress-sapphire' ), true ); // phpcs:ignore ?></div>
			<h2 class="display-lg reveal"><?php esc_html_e( 'Start your 14-day Pro trial', 'luwipress-sapphire' ); ?></h2>
			<p class="reveal cta-sub"><?php esc_html_e( 'No credit card required. Compare every plan, or talk to our team about volume pricing.', 'luwipress-sapphire' ); ?></p>
			<div class="hero-cta reveal" style="justify-content:center;margin-top:30px">
				<a class="btn btn-gold" href="<?php echo esc_url( $sapphire_contact ); ?>"><?php esc_html_e( 'Start free trial', 'luwipress-sapphire' ); ?> <span class="arr"><?php echo sapphire_icon( 'rocket', 16 ); // phpcs:ignore ?></span></a>
				<a class="btn btn-ghost" href="<?php echo esc_url( $sapphire_pricing ); ?>"><?php esc_html_e( 'Compare plans', 'luwipress-sapphire' ); ?></a>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
