<?php
/**
 * Template Name: Sapphire — Integrations
 *
 * Category-filtered integrations directory. The chip filter is progressive
 * enhancement: every tile is server-rendered, sapphire.js just shows/hides
 * on chip click. Yields to Elementor when built with it.
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

$sapphire_integrations = apply_filters( 'luwipress_sapphire_integrations_data', array(
	array( 'Dev tools',     'GitHub',   'Sync issues, PRs and deploys', 'G' ),
	array( 'Dev tools',     'Vercel',   'Ship a preview on every push', 'V' ),
	array( 'Dev tools',     'Sentry',   'Track errors and releases live', 'S' ),
	array( 'Communication', 'Slack',    'Alerts, approvals and digests', 'S' ),
	array( 'Communication', 'Intercom', 'Sync conversations and users', 'I' ),
	array( 'Analytics',     'Datadog',  'Stream metrics, traces and logs', 'D' ),
	array( 'Analytics',     'Segment',  'Pipe product events anywhere', 'S' ),
	array( 'Payments',      'Stripe',   'Billing, invoices and tax', 'S' ),
	array( 'Productivity',  'Notion',   'Embed docs and wikis', 'N' ),
	array( 'Productivity',  'Linear',   'Two-way issue sync', 'L' ),
	array( 'Productivity',  'Figma',    'Embed designs and specs', 'F' ),
	array( 'Automation',    'Zapier',   'Connect 5,000+ apps, no code', 'Z' ),
) );
$sapphire_cats = array( 'All', 'Dev tools', 'Communication', 'Analytics', 'Payments', 'Productivity', 'Automation' );
?>

<main>
	<section class="phead">
		<div class="wrap">
			<div class="crumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'luwipress-sapphire' ); ?></a><span class="sep">/</span><span><?php esc_html_e( 'Integrations', 'luwipress-sapphire' ); ?></span></div>
			<span class="smallcaps" style="color:var(--accent)"><?php esc_html_e( 'Integrations', 'luwipress-sapphire' ); ?></span>
			<h1 class="display-xl" style="margin-top:14px;font-size:clamp(44px,6vw,84px)"><?php esc_html_e( 'Works with your stack', 'luwipress-sapphire' ); ?></h1>
			<p class="phead-sub"><?php esc_html_e( 'Connect Sapphire to the tools your team already uses — two clicks, no glue code. Filter by category; 100+ more land every month.', 'luwipress-sapphire' ); ?></p>
		</div>
	</section>

	<section class="section" style="padding-top:clamp(40px,5vw,64px)">
		<div class="wrap" data-sapphire-gallery>
			<div class="filters">
				<?php foreach ( $sapphire_cats as $i => $c ) : ?>
					<button type="button" class="<?php echo 0 === $i ? 'on' : ''; ?>" data-gal-cat="<?php echo esc_attr( $c ); ?>"><?php echo esc_html( $c ); ?></button>
				<?php endforeach; ?>
				<span class="count" data-gal-count><?php echo count( $sapphire_integrations ); ?> <?php esc_html_e( 'integrations', 'luwipress-sapphire' ); ?></span>
			</div>
			<div class="intg" style="margin-top:32px">
				<?php foreach ( $sapphire_integrations as $ig ) : ?>
					<a class="intg-tile reveal" href="<?php echo esc_url( $sapphire_contact ); ?>" data-card-cat="<?php echo esc_attr( $ig[0] ); ?>" style="flex-direction:column;align-items:flex-start;gap:14px">
						<span class="intg-mark"><?php echo esc_html( $ig[3] ); ?></span>
						<div>
							<div class="smallcaps" style="color:var(--faint);margin-bottom:6px"><?php echo esc_html( $ig[0] ); ?></div>
							<div class="intg-name" style="font-family:var(--display);font-size:18px;font-weight:600;color:var(--ink)"><?php echo esc_html( $ig[1] ); ?></div>
							<p style="color:var(--muted);font-size:14px;margin:8px 0 0;line-height:1.6"><?php echo esc_html( $ig[2] ); ?></p>
						</div>
						<span class="sh-link" style="margin-top:auto;color:var(--accent-soft);display:inline-flex;align-items:center;gap:7px"><?php esc_html_e( 'Connect', 'luwipress-sapphire' ); ?> <?php echo sapphire_icon( 'arrow', 14 ); // phpcs:ignore ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
