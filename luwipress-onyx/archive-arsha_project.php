<?php
/**
 * Projects catalog — `arsha_project` archive (/projects/), Phase 2 final.
 *
 * Sticky viewport-fit filter rail (status / type / developer typeahead / area /
 * project-search autocomplete), infinite-scroll results (page 1 server-rendered
 * for SEO + no-JS, further pages appended via the REST list endpoint). Filtering
 * is live (no reload) when JS is on; the GET form is the no-JS fallback. Cards
 * use the shared lwp_onyx_render_project_card(). Driven by onyx.js.
 *
 * @package luwipress-onyx
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$params = lwp_onyx_projects_filter_params( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$paged  = max( 1, (int) ( $_GET['fp'] ?? 1 ) );     // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$q      = new WP_Query( lwp_onyx_projects_query_args( $params, $paged, 12 ) );
$base   = get_post_type_archive_link( LWP_ONYX_PROJECT_PT );

$areas = get_terms( array( 'taxonomy' => 'arsha_area', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC' ) );
$types = get_terms( array( 'taxonomy' => 'arsha_unit_type', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC' ) );
$stats = get_terms( array( 'taxonomy' => 'arsha_project_status', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC' ) );
$devs  = get_terms( array( 'taxonomy' => 'arsha_developer', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 2000 ) );

$dev_island = array();
$dev_name   = '';
if ( ! is_wp_error( $devs ) ) {
	foreach ( $devs as $t ) {
		$dev_island[] = array( 's' => $t->slug, 'n' => $t->name, 'c' => (int) $t->count );
		if ( $t->slug === $params['dev'] ) { $dev_name = $t->name; }
	}
}
?>

<main class="pdp-cat" data-onyx-catalog
	data-rest="<?php echo esc_url( rest_url( 'luwipress-onyx/v1/projects/list' ) ); ?>"
	data-suggest="<?php echo esc_url( rest_url( 'luwipress-onyx/v1/projects/suggest' ) ); ?>">

	<section class="phead">
		<div class="wrap">
			<div class="crumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'luwipress-onyx' ); ?></a><span class="sep">/</span><span><?php esc_html_e( 'Projects', 'luwipress-onyx' ); ?></span></div>
			<span class="smallcaps" style="color:var(--gold)"><?php esc_html_e( 'Dubai Developments', 'luwipress-onyx' ); ?></span>
			<h1 class="display-xl" style="margin-top:10px;font-size:clamp(34px,4.2vw,58px)"><?php esc_html_e( 'Browse projects', 'luwipress-onyx' ); ?></h1>
		</div>
	</section>

	<section class="section" style="padding-top:clamp(28px,3vw,44px)">
		<div class="wrap">
			<div class="cat-wrap">
				<div class="cat-side-scrim" data-cf-scrim></div>
				<aside class="cat-side" data-cf-side>
					<form method="get" action="<?php echo esc_url( $base ); ?>" data-cf-form>
						<div class="cat-side-top">
							<h4><?php esc_html_e( 'Filter', 'luwipress-onyx' ); ?></h4>
							<a href="<?php echo esc_url( $base ); ?>" class="lst-reset-link" data-cf-reset><?php esc_html_e( 'Reset', 'luwipress-onyx' ); ?></a>
						</div>
						<div class="cat-side-scroll">
							<div class="fgroup cat-ac">
								<span class="fl"><?php esc_html_e( 'Search project', 'luwipress-onyx' ); ?></span>
								<input type="search" name="q" value="<?php echo esc_attr( $params['q'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. The Vogue', 'luwipress-onyx' ); ?>" class="proj-input" data-cf-q autocomplete="off">
								<div class="cat-ac-menu" data-cf-q-menu hidden></div>
							</div>

							<?php if ( ! is_wp_error( $stats ) && $stats ) : ?>
							<div class="fgroup">
								<span class="fl"><?php esc_html_e( 'Status', 'luwipress-onyx' ); ?></span>
								<div class="seg seg--wrap">
									<label class="seg-opt<?php echo '' === $params['status'] ? ' on' : ''; ?>"><input type="radio" name="status" value="" <?php checked( '', $params['status'] ); ?>><?php esc_html_e( 'Any', 'luwipress-onyx' ); ?></label>
									<?php foreach ( $stats as $t ) : ?>
										<label class="seg-opt<?php echo $params['status'] === $t->slug ? ' on' : ''; ?>"><input type="radio" name="status" value="<?php echo esc_attr( $t->slug ); ?>" <?php checked( $params['status'], $t->slug ); ?>><?php echo esc_html( $t->name ); ?> <span class="cnt"><?php echo (int) $t->count; ?></span></label>
									<?php endforeach; ?>
								</div>
							</div>
							<?php endif; ?>

							<?php if ( ! is_wp_error( $types ) && $types ) : ?>
							<div class="fgroup">
								<span class="fl"><?php esc_html_e( 'Type', 'luwipress-onyx' ); ?></span>
								<div class="checks">
									<?php foreach ( $types as $t ) : ?>
										<label class="chk<?php echo in_array( $t->slug, $params['type'], true ) ? ' on' : ''; ?>"><input type="checkbox" name="type[]" value="<?php echo esc_attr( $t->slug ); ?>" <?php checked( in_array( $t->slug, $params['type'], true ) ); ?>><?php echo esc_html( $t->name ); ?><span class="cnt"><?php echo (int) $t->count; ?></span></label>
									<?php endforeach; ?>
								</div>
							</div>
							<?php endif; ?>

							<div class="fgroup cat-ac">
								<span class="fl"><?php esc_html_e( 'Developer', 'luwipress-onyx' ); ?></span>
								<input type="text" class="proj-input" data-cf-devsearch placeholder="<?php esc_attr_e( 'Type a developer…', 'luwipress-onyx' ); ?>" value="<?php echo esc_attr( $dev_name ); ?>" autocomplete="off">
								<input type="hidden" name="dev" value="<?php echo esc_attr( $params['dev'] ); ?>" data-cf-dev>
								<div class="cat-ac-menu" data-cf-dev-menu hidden></div>
							</div>

							<?php if ( ! is_wp_error( $areas ) && $areas ) : ?>
							<div class="fgroup">
								<span class="fl"><?php esc_html_e( 'Area', 'luwipress-onyx' ); ?></span>
								<div class="checks checks--scroll">
									<?php foreach ( $areas as $t ) : ?>
										<label class="chk<?php echo in_array( $t->slug, $params['area'], true ) ? ' on' : ''; ?>"><input type="checkbox" name="area[]" value="<?php echo esc_attr( $t->slug ); ?>" <?php checked( in_array( $t->slug, $params['area'], true ) ); ?>><?php echo esc_html( $t->name ); ?><span class="cnt"><?php echo (int) $t->count; ?></span></label>
									<?php endforeach; ?>
								</div>
							</div>
							<?php endif; ?>
						</div>
						<noscript><button type="submit" class="btn btn-gold" style="width:100%;justify-content:center;margin-top:10px"><?php esc_html_e( 'Apply', 'luwipress-onyx' ); ?></button></noscript>
					</form>
				</aside>

				<div class="cat-main">
					<div class="lst-bar">
						<div class="lst-count" data-cf-count><b><?php echo (int) $q->found_posts; ?></b> <?php esc_html_e( 'projects', 'luwipress-onyx' ); ?></div>
						<div class="lst-ctrls">
							<button class="filt-toggle" type="button" data-cf-toggle><span class="ic"><?php echo onyx_icon( 'menu', 16 ); // phpcs:ignore ?></span><?php esc_html_e( 'Filters', 'luwipress-onyx' ); ?></button>
							<div class="sortsel">
								<select data-cf-sort aria-label="<?php esc_attr_e( 'Sort', 'luwipress-onyx' ); ?>">
									<option value="recent" <?php selected( $params['sort'], 'recent' ); ?>><?php esc_html_e( 'Sort · Recent', 'luwipress-onyx' ); ?></option>
									<option value="name" <?php selected( $params['sort'], 'name' ); ?>><?php esc_html_e( 'Name (A–Z)', 'luwipress-onyx' ); ?></option>
									<option value="progress" <?php selected( $params['sort'], 'progress' ); ?>><?php esc_html_e( 'Construction progress', 'luwipress-onyx' ); ?></option>
									<option value="units" <?php selected( $params['sort'], 'units' ); ?>><?php esc_html_e( 'Most units', 'luwipress-onyx' ); ?></option>
								</select>
							</div>
						</div>
					</div>

					<div class="results grid" data-cf-results>
						<?php
						$i = ( $paged - 1 ) * 12;
						if ( $q->have_posts() ) {
							while ( $q->have_posts() ) {
								$q->the_post();
								echo lwp_onyx_render_project_card( get_the_ID(), $i ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								$i++;
							}
							wp_reset_postdata();
						} else {
							echo '<p class="cat-empty" style="color:var(--muted);padding:40px 0">' . esc_html__( 'No projects match these filters.', 'luwipress-onyx' ) . '</p>';
						}
						?>
					</div>
					<div class="cat-loader" data-cf-loader hidden><span class="cat-spin"></span></div>
					<div data-cf-sentinel></div>
				</div>
			</div>
		</div>
	</section>

	<script type="application/json" data-cf-devs><?php echo wp_json_encode( $dev_island ); // phpcs:ignore ?></script>
	<script type="application/json" data-cf-state><?php echo wp_json_encode( array( 'pages' => (int) $q->max_num_pages, 'page' => $paged ) ); // phpcs:ignore ?></script>
</main>

<?php
get_footer();
