<?php
/**
 * Template Name: Sapphire — Pricing
 *
 * Pricing page: three plan tiers (Starter / Pro / Studio, sold as WooCommerce
 * products) plus a full feature-comparison matrix and a closing trial CTA.
 * Tiers + matrix are filterable (luwipress_sapphire_pricing_tiers). Yields to
 * Elementor when the page is built with it.
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

// Pricing tiers (mirrors the homepage pricing section; filterable).
$sapphire_tiers = apply_filters( 'luwipress_sapphire_pricing_tiers', array(
	array(
		'name' => __( 'Starter', 'luwipress-sapphire' ), 'price' => '$0', 'per' => __( 'free forever', 'luwipress-sapphire' ),
		'blurb' => __( 'For individuals and small projects.', 'luwipress-sapphire' ), 'popular' => false,
		'cta' => __( 'Start free', 'luwipress-sapphire' ),
		'feats' => array( __( '1 workspace', 'luwipress-sapphire' ), __( 'Up to 3 projects', 'luwipress-sapphire' ), __( 'Community support', 'luwipress-sapphire' ), __( 'Core integrations', 'luwipress-sapphire' ) ),
	),
	array(
		'name' => __( 'Pro', 'luwipress-sapphire' ), 'price' => '$24', 'per' => __( 'per seat / month', 'luwipress-sapphire' ),
		'blurb' => __( 'For growing teams that ship weekly.', 'luwipress-sapphire' ), 'popular' => true,
		'cta' => __( 'Start 14-day trial', 'luwipress-sapphire' ),
		'feats' => array( __( 'Unlimited projects', 'luwipress-sapphire' ), __( 'All 100+ integrations', 'luwipress-sapphire' ), __( 'Advanced analytics', 'luwipress-sapphire' ), __( 'Priority support', 'luwipress-sapphire' ), __( 'API & webhooks', 'luwipress-sapphire' ) ),
	),
	array(
		'name' => __( 'Studio', 'luwipress-sapphire' ), 'price' => '$79', 'per' => __( 'per seat / month', 'luwipress-sapphire' ),
		'blurb' => __( 'For organisations that need control.', 'luwipress-sapphire' ), 'popular' => false,
		'cta' => __( 'Talk to sales', 'luwipress-sapphire' ),
		'feats' => array( __( 'Everything in Pro', 'luwipress-sapphire' ), __( 'SSO / SAML + SCIM', 'luwipress-sapphire' ), __( 'Audit log & SOC 2', 'luwipress-sapphire' ), __( '99.99% uptime SLA', 'luwipress-sapphire' ), __( 'Dedicated CSM', 'luwipress-sapphire' ) ),
	),
) );

// Feature comparison matrix — each row: [ label, [starter, pro, studio] ].
// A cell is true (✓), false (—) or a string value.
$sapphire_matrix = array(
	array( __( 'Workspaces', 'luwipress-sapphire' ),       array( '1', __( 'Unlimited', 'luwipress-sapphire' ), __( 'Unlimited', 'luwipress-sapphire' ) ) ),
	array( __( 'Projects', 'luwipress-sapphire' ),         array( '3', __( 'Unlimited', 'luwipress-sapphire' ), __( 'Unlimited', 'luwipress-sapphire' ) ) ),
	array( __( 'Team seats', 'luwipress-sapphire' ),       array( '1', __( 'Up to 50', 'luwipress-sapphire' ), __( 'Unlimited', 'luwipress-sapphire' ) ) ),
	array( __( 'Integrations', 'luwipress-sapphire' ),     array( __( 'Core', 'luwipress-sapphire' ), __( 'All 100+', 'luwipress-sapphire' ), __( 'All 100+', 'luwipress-sapphire' ) ) ),
	array( __( 'Advanced analytics', 'luwipress-sapphire' ), array( false, true, true ) ),
	array( __( 'API & webhooks', 'luwipress-sapphire' ),   array( false, true, true ) ),
	array( __( 'Audit log', 'luwipress-sapphire' ),        array( false, false, true ) ),
	array( __( 'SSO / SAML + SCIM', 'luwipress-sapphire' ),array( false, false, true ) ),
	array( __( 'Uptime SLA', 'luwipress-sapphire' ),       array( false, false, '99.99%' ) ),
	array( __( 'Support', 'luwipress-sapphire' ),          array( __( 'Community', 'luwipress-sapphire' ), __( 'Priority', 'luwipress-sapphire' ), __( 'Dedicated CSM', 'luwipress-sapphire' ) ) ),
);
$sapphire_cell = function ( $v ) {
	if ( true === $v )  { return '<span class="pm-yes">' . sapphire_icon( 'check', 16 ) . '</span>'; }
	if ( false === $v ) { return '<span class="pm-no">—</span>'; }
	return '<span class="pm-val">' . esc_html( $v ) . '</span>';
};
?>

<main>
	<section class="phead">
		<div class="wrap">
			<div class="crumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'luwipress-sapphire' ); ?></a><span class="sep">/</span><span><?php esc_html_e( 'Pricing', 'luwipress-sapphire' ); ?></span></div>
			<span class="smallcaps" style="color:var(--accent)"><?php esc_html_e( 'Pricing', 'luwipress-sapphire' ); ?></span>
			<h1 class="display-xl" style="margin-top:14px;font-size:clamp(44px,6vw,84px)"><?php esc_html_e( 'Simple, scales with you', 'luwipress-sapphire' ); ?></h1>
			<p class="phead-sub"><?php esc_html_e( 'Start free, upgrade when your team grows, and cancel anytime. Every paid plan begins with a 14-day trial — no credit card required.', 'luwipress-sapphire' ); ?></p>
		</div>
	</section>

	<section class="section" style="padding-top:clamp(36px,4vw,56px)">
		<div class="wrap">
			<div class="price-grid">
				<?php foreach ( $sapphire_tiers as $t ) : ?>
					<div class="price-card reveal<?php echo $t['popular'] ? ' is-popular' : ''; ?>">
						<?php if ( $t['popular'] ) : ?><span class="price-badge"><?php esc_html_e( 'Most popular', 'luwipress-sapphire' ); ?></span><?php endif; ?>
						<div class="price-name"><?php echo esc_html( $t['name'] ); ?></div>
						<div class="price-fig"><span class="price-amt"><?php echo esc_html( $t['price'] ); ?></span><span class="price-per"><?php echo esc_html( $t['per'] ); ?></span></div>
						<p class="price-blurb"><?php echo esc_html( $t['blurb'] ); ?></p>
						<a class="btn <?php echo $t['popular'] ? 'btn-gold' : 'btn-ghost'; ?> price-cta" href="<?php echo esc_url( $sapphire_contact ); ?>"><?php echo esc_html( $t['cta'] ); ?></a>
						<ul class="price-feats">
							<?php foreach ( $t['feats'] as $ft ) : ?>
								<li><span class="ic"><?php echo sapphire_icon( 'check', 16 ); // phpcs:ignore ?></span><?php echo esc_html( $ft ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="section" style="background:var(--sapphire-850);border-top:1px solid var(--hair-soft);border-bottom:1px solid var(--hair-soft)">
		<div class="wrap">
			<div class="sec-head" style="flex-direction:column;align-items:flex-start;gap:14px">
				<span class="reveal" style="display:block"><?php echo sapphire_eyebrow( __( 'Compare plans', 'luwipress-sapphire' ) ); // phpcs:ignore ?></span>
				<h2 class="display-lg reveal"><?php esc_html_e( 'Every feature, side by side', 'luwipress-sapphire' ); ?></h2>
			</div>
			<div class="price-matrix-wrap reveal">
				<table class="price-matrix">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Feature', 'luwipress-sapphire' ); ?></th>
							<th><?php esc_html_e( 'Starter', 'luwipress-sapphire' ); ?></th>
							<th class="pm-pop"><?php esc_html_e( 'Pro', 'luwipress-sapphire' ); ?></th>
							<th><?php esc_html_e( 'Studio', 'luwipress-sapphire' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $sapphire_matrix as $row ) : ?>
							<tr>
								<td class="pm-feat"><?php echo esc_html( $row[0] ); ?></td>
								<td><?php echo $sapphire_cell( $row[1][0] ); // phpcs:ignore ?></td>
								<td class="pm-pop"><?php echo $sapphire_cell( $row[1][1] ); // phpcs:ignore ?></td>
								<td><?php echo $sapphire_cell( $row[1][2] ); // phpcs:ignore ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</section>

	<section class="cta-band">
		<div class="grain"></div>
		<div class="wrap">
			<div class="reveal" style="display:flex;justify-content:center;margin-bottom:22px"><?php echo sapphire_eyebrow( __( 'Still deciding?', 'luwipress-sapphire' ), true ); // phpcs:ignore ?></div>
			<h2 class="display-lg reveal"><?php esc_html_e( 'Try every Pro feature free for 14 days', 'luwipress-sapphire' ); ?></h2>
			<p class="reveal cta-sub"><?php esc_html_e( 'No credit card. Cancel anytime. Need a custom plan or volume pricing? Talk to our team.', 'luwipress-sapphire' ); ?></p>
			<div class="hero-cta reveal" style="justify-content:center;margin-top:30px">
				<a class="btn btn-gold" href="<?php echo esc_url( $sapphire_contact ); ?>"><?php esc_html_e( 'Start free', 'luwipress-sapphire' ); ?> <span class="arr"><?php echo sapphire_icon( 'rocket', 16 ); // phpcs:ignore ?></span></a>
				<a class="btn btn-ghost" href="<?php echo esc_url( $sapphire_contact ); ?>"><?php esc_html_e( 'Talk to sales', 'luwipress-sapphire' ); ?></a>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
