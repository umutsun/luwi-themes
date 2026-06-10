<?php
/**
 * Front page — the designed Sapphire home (Midnight Sapphire SaaS system).
 *
 * SaaS section order: product-shot split hero → logo/trust strip → feature
 * grid → product overview + stats → integrations wall → 3-tier pricing →
 * testimonials → changelog/roadmap timeline → FAQ (FAQPage JSON-LD) →
 * CTA band → contact.
 *
 * Demo copy is self-titled ("Sapphire") so an operator can find-and-replace
 * one word. Yields to Elementor when the front page is built with it.
 *
 * @package luwipress-sapphire
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$sapphire_front_id = (int) get_option( 'page_on_front' );
$sapphire_el_built = $sapphire_front_id && (bool) get_post_meta( $sapphire_front_id, '_elementor_edit_mode', true ) && (bool) get_post_meta( $sapphire_front_id, '_elementor_data', true );

get_header();

if ( $sapphire_el_built ) {
	while ( have_posts() ) {
		the_post();
		the_content();
	}
	get_footer();
	return;
}

$sapphire_contact = sapphire_page_url( 'contact' );
$sapphire_journal = sapphire_page_url( 'journal' );
$sapphire_pricing = home_url( '/#pricing' );
?>

<main>

	<!-- ===== HERO (product split) ===== -->
	<section class="hero hero--b" id="top">
		<div class="hb-text">
			<span class="hero-eyebrow"><?php echo sapphire_eyebrow( __( 'SaaS Platform · v2.4', 'luwipress-sapphire' ) ); // phpcs:ignore ?></span>
			<h1 class="display-lg"><?php esc_html_e( 'Ship faster.', 'luwipress-sapphire' ); ?><br><?php esc_html_e( 'Sleep better.', 'luwipress-sapphire' ); ?></h1>
			<p class="lede hero-sub"><?php esc_html_e( 'Sapphire brings your product, docs, billing and analytics into one workspace — so your team ships in days, not quarters.', 'luwipress-sapphire' ); ?></p>
			<div class="hero-cta">
				<a class="btn btn-gold" href="<?php echo esc_url( $sapphire_pricing ); ?>"><?php esc_html_e( 'Start free', 'luwipress-sapphire' ); ?> <span class="arr"><?php echo sapphire_icon( 'arrow', 16 ); // phpcs:ignore ?></span></a>
				<a class="btn btn-ghost" href="#demo"><?php esc_html_e( 'Live demo', 'luwipress-sapphire' ); ?></a>
			</div>
			<div class="hero-meta" style="margin-top:48px">
				<div><div class="smallcaps"><?php esc_html_e( 'Free trial', 'luwipress-sapphire' ); ?></div><div class="tnum" style="font-family:var(--display);font-size:26px;color:var(--ink)"><?php esc_html_e( '14 days', 'luwipress-sapphire' ); ?></div></div>
				<div class="hair-gold" style="width:1px;height:40px"></div>
				<div><div class="smallcaps"><?php esc_html_e( 'No card', 'luwipress-sapphire' ); ?></div><div class="tnum" style="font-family:var(--display);font-size:26px;color:var(--accent)"><?php esc_html_e( 'Required', 'luwipress-sapphire' ); ?></div></div>
				<div class="hair-gold" style="width:1px;height:40px"></div>
				<div><div class="smallcaps"><?php esc_html_e( 'Teams', 'luwipress-sapphire' ); ?></div><div class="tnum" style="font-family:var(--display);font-size:26px;color:var(--ink)">12k+</div></div>
			</div>
		</div>
		<div class="hb-photo" id="demo">
			<div class="app-mock" aria-hidden="true">
				<div class="app-bar"><span class="app-dot"></span><span class="app-dot"></span><span class="app-dot"></span><span class="app-url"><?php echo esc_html( 'app.sapphire.dev' ); ?></span></div>
				<div class="app-body">
					<div class="app-side">
						<span class="app-nav on"></span><span class="app-nav"></span><span class="app-nav"></span><span class="app-nav"></span><span class="app-nav"></span>
					</div>
					<div class="app-main">
						<div class="app-row"><span class="app-kpi"><b>99.99%</b><i><?php esc_html_e( 'uptime', 'luwipress-sapphire' ); ?></i></span><span class="app-kpi"><b>1.2M</b><i><?php esc_html_e( 'events/day', 'luwipress-sapphire' ); ?></i></span><span class="app-kpi"><b>+18%</b><i><?php esc_html_e( 'this week', 'luwipress-sapphire' ); ?></i></span></div>
						<div class="app-chart"><i style="height:38%"></i><i style="height:55%"></i><i style="height:44%"></i><i style="height:72%"></i><i style="height:60%"></i><i style="height:86%"></i><i style="height:70%"></i><i style="height:94%"></i></div>
						<div class="app-list"><span></span><span></span><span></span></div>
					</div>
				</div>
			</div>
			<div class="hb-cap"><span class="smallcaps" style="color:var(--accent-soft)"><?php esc_html_e( 'Live preview', 'luwipress-sapphire' ); ?></span><div style="font-family:var(--display);font-size:22px;margin-top:4px"><?php esc_html_e( 'The Sapphire dashboard', 'luwipress-sapphire' ); ?></div></div>
		</div>
	</section>

	<!-- ===== LOGO / TRUST STRIP ===== -->
	<section class="section logos-strip">
		<div class="wrap">
			<p class="smallcaps logos-cap reveal"><?php esc_html_e( 'Trusted by fast-moving teams worldwide', 'luwipress-sapphire' ); ?></p>
			<div class="logos">
				<?php foreach ( array( 'NORTHWIND', 'LUMEN', 'APEX', 'STRATUS', 'ORBIT', 'HELM' ) as $sapphire_logo ) : ?>
					<span class="lg"><?php echo esc_html( $sapphire_logo ); ?></span>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<!-- ===== FEATURE GRID ===== -->
	<section class="section" id="features">
		<div class="wrap">
			<div class="sec-head" style="flex-direction:column;align-items:flex-start;gap:18px">
				<span class="reveal" style="display:block"><?php echo sapphire_eyebrow( __( 'Everything in one place', 'luwipress-sapphire' ) ); // phpcs:ignore ?></span>
				<h2 class="display-lg reveal"><?php esc_html_e( 'Built for teams that ship', 'luwipress-sapphire' ); ?></h2>
			</div>
			<?php
			$sapphire_features = array(
				array( 'zap',     __( 'Realtime by default', 'luwipress-sapphire' ),    __( 'Presence, live cursors and instant sync — your team is always on the same page.', 'luwipress-sapphire' ) ),
				array( 'code',    __( 'Developer-first API', 'luwipress-sapphire' ),     __( 'A fully typed REST + webhooks surface, with SDKs for every language you ship in.', 'luwipress-sapphire' ) ),
				array( 'plug',    __( '100+ integrations', 'luwipress-sapphire' ),       __( 'Connect Slack, GitHub, Stripe, Linear and the rest of your stack in two clicks.', 'luwipress-sapphire' ) ),
				array( 'chart',   __( 'Insightful analytics', 'luwipress-sapphire' ),    __( 'Dashboards, funnels and CSV export — answers without waiting on the data team.', 'luwipress-sapphire' ) ),
				array( 'shield',  __( 'Secure by design', 'luwipress-sapphire' ),        __( 'SOC 2 Type II, SSO/SAML, encryption at rest and granular role-based access.', 'luwipress-sapphire' ) ),
				array( 'layers',  __( 'Scales with you', 'luwipress-sapphire' ),         __( 'From a two-person startup to thousands of seats — 99.99% uptime, no rewrites.', 'luwipress-sapphire' ) ),
			);
			?>
			<div class="fgrid">
				<?php foreach ( $sapphire_features as $sapphire_f ) : ?>
					<div class="fcard reveal">
						<span class="fcard-ic"><?php echo sapphire_icon( $sapphire_f[0], 22 ); // phpcs:ignore ?></span>
						<h3><?php echo esc_html( $sapphire_f[1] ); ?></h3>
						<p><?php echo esc_html( $sapphire_f[2] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<!-- ===== PRODUCT OVERVIEW + STATS ===== -->
	<section class="section" id="product" style="background:var(--sapphire-850);border-top:1px solid var(--hair-soft);border-bottom:1px solid var(--hair-soft)">
		<div class="wrap">
			<div class="ov-grid">
				<div class="ov-copy">
					<span class="reveal" style="display:block"><?php echo sapphire_eyebrow( __( 'Why Sapphire', 'luwipress-sapphire' ) ); // phpcs:ignore ?></span>
					<h2 class="display-lg reveal" style="margin:22px 0 26px"><?php esc_html_e( 'One platform, every workflow', 'luwipress-sapphire' ); ?></h2>
					<p class="lede reveal"><?php esc_html_e( 'Stop stitching together six tools. Sapphire unifies the product surface, the docs, the billing and the analytics — so the whole team works from one source of truth.', 'luwipress-sapphire' ); ?></p>
					<ul class="ov-checks reveal">
						<li><span class="ic"><?php echo sapphire_icon( 'check', 18 ); // phpcs:ignore ?></span><div><b style="font-weight:600"><?php esc_html_e( 'One workspace', 'luwipress-sapphire' ); ?></b> <span><?php esc_html_e( '— product, docs, billing and analytics in a single place.', 'luwipress-sapphire' ); ?></span></div></li>
						<li><span class="ic"><?php echo sapphire_icon( 'check', 18 ); // phpcs:ignore ?></span><div><b style="font-weight:600"><?php esc_html_e( 'Built for developers', 'luwipress-sapphire' ); ?></b> <span><?php esc_html_e( '— typed API, webhooks and SDKs in every language.', 'luwipress-sapphire' ); ?></span></div></li>
						<li><span class="ic"><?php echo sapphire_icon( 'check', 18 ); // phpcs:ignore ?></span><div><b style="font-weight:600"><?php esc_html_e( 'Enterprise-ready', 'luwipress-sapphire' ); ?></b> <span><?php esc_html_e( '— SSO, audit logs, SOC 2 and 99.99% uptime.', 'luwipress-sapphire' ); ?></span></div></li>
					</ul>
				</div>
				<div class="ov-media reveal">
					<div class="app-mock app-mock--alt" aria-hidden="true">
						<div class="app-bar"><span class="app-dot"></span><span class="app-dot"></span><span class="app-dot"></span></div>
						<div class="app-body">
							<div class="app-main" style="width:100%">
								<div class="app-row"><span class="app-kpi"><b>4.9/5</b><i><?php esc_html_e( 'rating', 'luwipress-sapphire' ); ?></i></span><span class="app-kpi"><b>100+</b><i><?php esc_html_e( 'integrations', 'luwipress-sapphire' ); ?></i></span></div>
								<div class="app-chart"><i style="height:48%"></i><i style="height:66%"></i><i style="height:52%"></i><i style="height:80%"></i><i style="height:64%"></i><i style="height:92%"></i></div>
								<div class="app-list"><span></span><span></span><span></span><span></span></div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="stats-row">
				<?php
				$sapphire_kpis = array(
					array( '99.99%', __( 'Uptime SLA', 'luwipress-sapphire' ), null ),
					array( '100+',   __( 'Integrations', 'luwipress-sapphire' ), 100 ),
					array( '4.9/5',  __( 'Average rating', 'luwipress-sapphire' ), null ),
					array( '12k+',   __( 'Teams onboard', 'luwipress-sapphire' ), 12 ),
				);
				foreach ( $sapphire_kpis as $sapphire_k ) : ?>
					<div class="stat">
						<div class="tnum stat-num"><?php if ( null !== $sapphire_k[2] ) : ?><span data-countup="<?php echo (int) $sapphire_k[2]; ?>"><?php echo esc_html( $sapphire_k[0] ); ?></span><?php else : ?><?php echo esc_html( $sapphire_k[0] ); ?><?php endif; ?></div>
						<div class="smallcaps stat-lbl"><?php echo esc_html( $sapphire_k[1] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<!-- ===== INTEGRATIONS WALL ===== -->
	<section class="section" id="integrations">
		<div class="wrap">
			<div class="sec-head" style="flex-direction:column;align-items:flex-start;gap:14px">
				<span class="reveal" style="display:block"><?php echo sapphire_eyebrow( __( 'Integrations', 'luwipress-sapphire' ) ); // phpcs:ignore ?></span>
				<h2 class="display-lg reveal"><?php esc_html_e( 'Works with your stack', 'luwipress-sapphire' ); ?></h2>
			</div>
			<div class="intg reveal">
				<?php
				$sapphire_intg = array( 'Slack', 'GitHub', 'Stripe', 'Figma', 'Linear', 'Notion', 'Zapier', 'Vercel', 'Sentry', 'Datadog', 'Segment', 'Intercom' );
				foreach ( $sapphire_intg as $sapphire_i ) : ?>
					<div class="intg-tile">
						<span class="intg-mark"><?php echo esc_html( substr( $sapphire_i, 0, 1 ) ); ?></span>
						<span class="intg-name"><?php echo esc_html( $sapphire_i ); ?></span>
					</div>
				<?php endforeach; ?>
				<a class="intg-tile intg-more" href="<?php echo esc_url( $sapphire_contact ); ?>">
					<span class="intg-name"><?php esc_html_e( 'and 100+ more', 'luwipress-sapphire' ); ?> <?php echo sapphire_icon( 'arrow', 15 ); // phpcs:ignore ?></span>
				</a>
			</div>
		</div>
	</section>

	<!-- ===== PRICING ===== -->
	<section class="section" id="pricing" style="background:var(--sapphire-850);border-top:1px solid var(--hair-soft);border-bottom:1px solid var(--hair-soft)">
		<div class="wrap">
			<div class="sec-head" style="justify-content:center;text-align:center;margin-bottom:54px">
				<div class="sh-title" style="margin:0 auto">
					<span class="reveal" style="display:flex;justify-content:center"><?php echo sapphire_eyebrow( __( 'Pricing', 'luwipress-sapphire' ), true ); // phpcs:ignore ?></span>
					<h2 class="display-lg reveal" style="margin-top:18px"><?php esc_html_e( 'Simple, scales with you', 'luwipress-sapphire' ); ?></h2>
					<p class="reveal" style="color:var(--muted);margin-top:14px"><?php esc_html_e( 'Start free. Upgrade when your team grows. Cancel anytime.', 'luwipress-sapphire' ); ?></p>
				</div>
			</div>
			<?php
			$sapphire_tiers = array(
				array(
					'name'    => __( 'Starter', 'luwipress-sapphire' ),
					'price'   => '$0',
					'per'     => __( 'free forever', 'luwipress-sapphire' ),
					'blurb'   => __( 'For individuals and small projects.', 'luwipress-sapphire' ),
					'popular' => false,
					'cta'     => __( 'Start free', 'luwipress-sapphire' ),
					'feats'   => array( __( '1 workspace', 'luwipress-sapphire' ), __( 'Up to 3 projects', 'luwipress-sapphire' ), __( 'Community support', 'luwipress-sapphire' ), __( 'Core integrations', 'luwipress-sapphire' ) ),
				),
				array(
					'name'    => __( 'Pro', 'luwipress-sapphire' ),
					'price'   => '$24',
					'per'     => __( 'per seat / month', 'luwipress-sapphire' ),
					'blurb'   => __( 'For growing teams that ship weekly.', 'luwipress-sapphire' ),
					'popular' => true,
					'cta'     => __( 'Start 14-day trial', 'luwipress-sapphire' ),
					'feats'   => array( __( 'Unlimited projects', 'luwipress-sapphire' ), __( 'All 100+ integrations', 'luwipress-sapphire' ), __( 'Advanced analytics', 'luwipress-sapphire' ), __( 'Priority support', 'luwipress-sapphire' ), __( 'API & webhooks', 'luwipress-sapphire' ) ),
				),
				array(
					'name'    => __( 'Studio', 'luwipress-sapphire' ),
					'price'   => '$79',
					'per'     => __( 'per seat / month', 'luwipress-sapphire' ),
					'blurb'   => __( 'For organisations that need control.', 'luwipress-sapphire' ),
					'popular' => false,
					'cta'     => __( 'Talk to sales', 'luwipress-sapphire' ),
					'feats'   => array( __( 'Everything in Pro', 'luwipress-sapphire' ), __( 'SSO / SAML + SCIM', 'luwipress-sapphire' ), __( 'Audit log & SOC 2', 'luwipress-sapphire' ), __( '99.99% uptime SLA', 'luwipress-sapphire' ), __( 'Dedicated CSM', 'luwipress-sapphire' ) ),
				),
			);
			?>
			<div class="price-grid">
				<?php foreach ( $sapphire_tiers as $sapphire_t ) : ?>
					<div class="price-card reveal<?php echo $sapphire_t['popular'] ? ' is-popular' : ''; ?>">
						<?php if ( $sapphire_t['popular'] ) : ?><span class="price-badge"><?php esc_html_e( 'Most popular', 'luwipress-sapphire' ); ?></span><?php endif; ?>
						<div class="price-name"><?php echo esc_html( $sapphire_t['name'] ); ?></div>
						<div class="price-fig"><span class="price-amt"><?php echo esc_html( $sapphire_t['price'] ); ?></span><span class="price-per"><?php echo esc_html( $sapphire_t['per'] ); ?></span></div>
						<p class="price-blurb"><?php echo esc_html( $sapphire_t['blurb'] ); ?></p>
						<a class="btn <?php echo $sapphire_t['popular'] ? 'btn-gold' : 'btn-ghost'; ?> price-cta" href="<?php echo esc_url( $sapphire_contact ); ?>"><?php echo esc_html( $sapphire_t['cta'] ); ?></a>
						<ul class="price-feats">
							<?php foreach ( $sapphire_t['feats'] as $sapphire_ft ) : ?>
								<li><span class="ic"><?php echo sapphire_icon( 'check', 16 ); // phpcs:ignore ?></span><?php echo esc_html( $sapphire_ft ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<!-- ===== TESTIMONIALS ===== -->
	<section class="section">
		<div class="wrap">
			<div class="sec-head" style="justify-content:center;text-align:center;margin-bottom:60px">
				<div class="sh-title" style="margin:0 auto">
					<span class="reveal" style="display:flex;justify-content:center"><?php echo sapphire_eyebrow( __( 'Loved by builders', 'luwipress-sapphire' ), true ); // phpcs:ignore ?></span>
					<h2 class="display-lg reveal" style="margin-top:18px"><?php esc_html_e( "What teams are saying", 'luwipress-sapphire' ); ?></h2>
				</div>
			</div>
			<?php
			$sapphire_quotes = array(
				array( 'We replaced four tools with Sapphire in a weekend. Our release cycle went from monthly to daily, and nobody misses the old stack.', 'Dana Whitfield', 'VP Engineering, Northwind', 'DW' ),
				array( 'The API is the cleanest I have integrated against in years. We were live in an afternoon, webhooks and all.', 'Marco Bellini', 'Staff Engineer, Lumen', 'MB' ),
				array( 'Onboarding 400 seats with SSO and audit logs took one call. Security signed off the same week.', 'Priya Nair', 'Head of IT, Apex', 'PN' ),
			);
			?>
			<div class="tst">
				<span class="tst-mark">&ldquo;</span>
				<div class="tst-stage">
					<?php foreach ( $sapphire_quotes as $sapphire_qk => $sapphire_q ) : ?>
						<div class="tst-slide<?php echo 0 === $sapphire_qk ? ' on' : ''; ?>">
							<p class="tst-quote"><?php echo esc_html( $sapphire_q[0] ); ?></p>
							<div class="tst-who">
								<span class="tst-av" style="display:grid;place-items:center;font-family:var(--display);font-size:18px;color:var(--accent)"><?php echo esc_html( $sapphire_q[3] ); ?></span>
								<div><div class="tst-name"><?php echo esc_html( $sapphire_q[1] ); ?></div><div class="tst-role"><?php echo esc_html( $sapphire_q[2] ); ?></div></div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="tst-dots">
					<?php foreach ( $sapphire_quotes as $sapphire_qk => $sapphire_q ) : ?>
						<button type="button" class="<?php echo 0 === $sapphire_qk ? 'on' : ''; ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Quote %d', 'luwipress-sapphire' ), $sapphire_qk + 1 ) ); ?>"></button>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</section>

	<!-- ===== CHANGELOG / ROADMAP ===== -->
	<section class="section" id="changelog">
		<div class="wrap">
			<div class="sec-head" style="flex-direction:column;align-items:flex-start;gap:14px">
				<span class="reveal" style="display:block"><?php echo sapphire_eyebrow( __( 'Always shipping', 'luwipress-sapphire' ) ); // phpcs:ignore ?></span>
				<h2 class="display-lg reveal"><?php esc_html_e( 'Changelog & roadmap', 'luwipress-sapphire' ); ?></h2>
			</div>
			<?php
			$sapphire_log = array(
				array( 'v2.4', 'shipped',  __( 'Realtime collaboration & presence', 'luwipress-sapphire' ), __( 'Live cursors, comments and instant sync across every workspace.', 'luwipress-sapphire' ) ),
				array( 'v2.3', 'shipped',  __( 'Analytics dashboards + CSV export', 'luwipress-sapphire' ), __( 'Build funnels, save views and export anything to CSV in one click.', 'luwipress-sapphire' ) ),
				array( 'v2.5', 'progress', __( 'Native mobile apps (iOS / Android)', 'luwipress-sapphire' ), __( 'Full parity on the go, with push notifications and offline drafts.', 'luwipress-sapphire' ) ),
				array( 'next', 'planned',  __( 'AI assistant for automations', 'luwipress-sapphire' ), __( 'Describe a workflow in plain language and ship it as an automation.', 'luwipress-sapphire' ) ),
			);
			$sapphire_status_lbl = array(
				'shipped'  => __( 'Shipped', 'luwipress-sapphire' ),
				'progress' => __( 'In progress', 'luwipress-sapphire' ),
				'planned'  => __( 'Planned', 'luwipress-sapphire' ),
			);
			?>
			<ol class="chlog reveal">
				<?php foreach ( $sapphire_log as $sapphire_l ) : ?>
					<li class="chlog-item">
						<span class="chlog-tag"><?php echo esc_html( $sapphire_l[0] ); ?></span>
						<div class="chlog-body">
							<div class="chlog-top"><h3><?php echo esc_html( $sapphire_l[2] ); ?></h3><span class="chlog-pill chlog-pill--<?php echo esc_attr( $sapphire_l[1] ); ?>"><?php echo esc_html( $sapphire_status_lbl[ $sapphire_l[1] ] ); ?></span></div>
							<p><?php echo esc_html( $sapphire_l[3] ); ?></p>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>

	<?php
	// ===== FAQ (FAQPage JSON-LD) =====
	$sapphire_faqs = array(
		array( __( 'Is there a free plan?', 'luwipress-sapphire' ), __( 'Yes. The Starter plan is free forever — one workspace, up to three projects and the core integrations, with no credit card required.', 'luwipress-sapphire' ) ),
		array( __( 'How does the 14-day trial work?', 'luwipress-sapphire' ), __( 'Every paid plan starts with a full-featured 14-day trial. No card up front; if you do nothing at the end, you simply drop back to the free Starter plan.', 'luwipress-sapphire' ) ),
		array( __( 'Can I self-host or stay in my region?', 'luwipress-sapphire' ), __( 'Sapphire runs in the EU and US regions, and Studio customers can pin data residency to a single region. On-prem is available on request.', 'luwipress-sapphire' ) ),
		array( __( 'Do you support SSO and SCIM?', 'luwipress-sapphire' ), __( 'Yes — SAML SSO, SCIM provisioning, audit logs and role-based access are included on the Studio plan and configurable in minutes.', 'luwipress-sapphire' ) ),
		array( __( 'How are you priced — per seat or usage?', 'luwipress-sapphire' ), __( 'Plans are priced per active seat, billed monthly or annually. There are no usage surprises; integrations and API calls are included.', 'luwipress-sapphire' ) ),
		array( __( 'Is my data secure?', 'luwipress-sapphire' ), __( 'We are SOC 2 Type II certified, encrypt data in transit and at rest, and undergo regular third-party penetration testing.', 'luwipress-sapphire' ) ),
	);
	$sapphire_faq_schema = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => array_map( function ( $f ) {
			return array(
				'@type'          => 'Question',
				'name'           => $f[0],
				'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $f[1] ),
			);
		}, $sapphire_faqs ),
	);
	?>
	<script type="application/ld+json"><?php echo wp_json_encode( $sapphire_faq_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?></script>
	<section class="section faq" id="faq" style="background:var(--sapphire-850);border-top:1px solid var(--hair-soft)">
		<div class="wrap">
			<div class="faq-grid">
				<div class="faq-aside reveal">
					<?php echo sapphire_eyebrow( __( 'Good to know', 'luwipress-sapphire' ) ); // phpcs:ignore ?>
					<h2 class="display-lg"><?php esc_html_e( 'Questions,', 'luwipress-sapphire' ); ?><br><?php esc_html_e( 'answered.', 'luwipress-sapphire' ); ?></h2>
					<p><?php esc_html_e( 'Everything you need before you start your trial. Still stuck? Our team replies in minutes.', 'luwipress-sapphire' ); ?></p>
					<a class="faq-call" href="<?php echo esc_url( $sapphire_contact ); ?>"><span class="ic"><?php echo sapphire_icon( 'chat', 16 ); // phpcs:ignore ?></span><?php esc_html_e( 'Talk to us', 'luwipress-sapphire' ); ?></a>
				</div>
				<div class="faq-list reveal">
					<?php foreach ( $sapphire_faqs as $sapphire_fk => $sapphire_f ) : ?>
						<div class="faq-item<?php echo 0 === $sapphire_fk ? ' open' : ''; ?>">
							<button class="faq-q" type="button" aria-expanded="<?php echo 0 === $sapphire_fk ? 'true' : 'false'; ?>"><?php echo esc_html( $sapphire_f[0] ); ?><span class="faq-sign" aria-hidden="true"></span></button>
							<div class="faq-a"><div class="faq-a-inner"><?php echo esc_html( $sapphire_f[1] ); ?></div></div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</section>

	<!-- ===== CTA BAND ===== -->
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

	<!-- ===== CONTACT ===== -->
	<?php get_template_part( 'template-parts/sapphire', 'contact', array( 'eyebrow' => __( 'Get in touch', 'luwipress-sapphire' ), 'title' => __( "Let's get you shipping", 'luwipress-sapphire' ) ) ); ?>

</main>

<?php
get_footer();
