<?php
/**
 * Generic single template for operator-defined CPT Engine content types
 * (lwp_team, lwp_event, lwp_music_academy_teacher, …).
 *
 * Renders any engine-managed CPT with the same chrome as the Vendor profile
 * (reuses the styled `.lwp-person-*` markup) but populates everything
 * GENERICALLY from the type's field schema — so a custom Team / Maker /
 * Teacher directory gets a styled profile page out of the box instead of the
 * bare blog fallback. Routed here by inc/engine-cpt-templates.php; a
 * type-specific template (e.g. single-lwp_vendor.php) still wins.
 *
 * Schema.org JSON-LD is emitted automatically by the core CPT Engine → Schema
 * Registry bridge (3.11.0) when the type has a schema_mapping @type — don't
 * duplicate it here.
 *
 * @package luwipress-onyx
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$lwp_pt     = get_post_type();
$lwp_def    = ( class_exists( 'LuwiPress_CPT_Engine' ) )
	? LuwiPress_CPT_Engine::get_instance()->get_type_by_post_type( $lwp_pt )
	: null;
$lwp_pt_obj = get_post_type_object( $lwp_pt );

$lwp_singular = ( $lwp_def && ! empty( $lwp_def['labels']['singular'] ) )
	? (string) $lwp_def['labels']['singular']
	: ( $lwp_pt_obj ? (string) $lwp_pt_obj->labels->singular_name : __( 'Item', 'luwipress-onyx' ) );
$lwp_plural   = ( $lwp_def && ! empty( $lwp_def['labels']['plural'] ) )
	? (string) $lwp_def['labels']['plural']
	: ( $lwp_pt_obj ? (string) $lwp_pt_obj->labels->name : __( 'Items', 'luwipress-onyx' ) );
$lwp_fields   = ( $lwp_def && ! empty( $lwp_def['field_schema'] ) ) ? $lwp_def['field_schema'] : array();

while ( have_posts() ) : the_post();
	$post_id   = get_the_ID();
	$name      = get_the_title( $post_id );
	$image_url = get_the_post_thumbnail_url( $post_id, 'large' );
	$excerpt   = get_the_excerpt();
	$content   = apply_filters( 'the_content', get_post_field( 'post_content', $post_id ) );

	// Categorize fields from the engine schema: url / sameAs → social chips;
	// short scalars → meta-strip stats; image / textarea → skipped (the featured
	// image is the portrait and long prose lives in the body).
	$socials   = array();
	$meta_rows = array();
	foreach ( $lwp_fields as $f ) {
		$key = isset( $f['key'] ) ? (string) $f['key'] : '';
		if ( '' === $key ) { continue; }
		$val = get_post_meta( $post_id, $key, true );
		if ( '' === $val || null === $val || ( is_array( $val ) && empty( $val ) ) ) { continue; }
		$type  = isset( $f['type'] ) ? (string) $f['type'] : 'text';
		$sprop = isset( $f['schema_prop'] ) ? (string) $f['schema_prop'] : '';
		$label = ( isset( $f['label'] ) && '' !== $f['label'] )
			? (string) $f['label']
			: ucwords( str_replace( array( '_', '-' ), ' ', preg_replace( '/^_?lwp_[a-z0-9]+_/', '', $key ) ) );

		if ( 'url' === $type || 'sameAs' === $sprop ) {
			if ( preg_match( '#^https?://#i', trim( (string) $val ) ) ) {
				$socials[] = array(
					'label' => $label,
					'url'   => (string) $val,
					'icon'  => function_exists( 'lwp_onyx_cpt_social_icon' ) ? lwp_onyx_cpt_social_icon( $label ) : 'eicon-globe',
				);
			}
		} elseif ( 'image' !== $type && 'textarea' !== $type ) {
			$meta_rows[] = array( 'label' => $label, 'value' => (string) $val );
		}
	}

	// "Made by" products grid — only when this CPT attributes to products
	// (woocommerce.attribution_meta in its engine definition) and WC is active.
	$work_query = null;
	$attr_meta  = ( $lwp_def && isset( $lwp_def['woocommerce']['attribution_meta'] ) )
		? (string) $lwp_def['woocommerce']['attribution_meta'] : '';
	if ( '' !== $attr_meta && class_exists( 'WooCommerce' ) ) {
		$regex      = '(\\[|,)[[:space:]]*"?' . preg_quote( (string) $post_id, '/' ) . '"?[[:space:]]*(,|\\])';
		$work_query = new WP_Query( array(
			'post_type'      => 'product',
			'posts_per_page' => 8,
			'meta_query'     => array(
				array( 'key' => $attr_meta, 'value' => $regex, 'compare' => 'REGEXP' ),
			),
		) );
	}
	?>

	<main class="lwp-person-page" id="primary">
		<article class="lwp-person">

			<header class="lwp-person__hero">
				<?php if ( $image_url ) : ?>
					<div class="lwp-person__portrait">
						<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $name ); ?>" />
					</div>
				<?php endif; ?>

				<div class="lwp-person__intro">
					<p class="lwp-person__eyebrow">
						<a href="<?php echo esc_url( get_post_type_archive_link( $lwp_pt ) ); ?>">
							<?php echo esc_html( $lwp_plural ); ?>
						</a>
					</p>

					<h1 class="lwp-person__name"><?php echo esc_html( $name ); ?></h1>

					<?php if ( $excerpt ) : ?>
						<p class="lwp-person__lead"><?php echo esc_html( $excerpt ); ?></p>
					<?php endif; ?>

					<?php if ( ! empty( $meta_rows ) ) : ?>
						<div class="lwp-person__meta-strip">
							<?php foreach ( $meta_rows as $r ) : ?>
								<div class="lwp-person__stat">
									<span class="lwp-person__stat-num"><?php echo esc_html( $r['value'] ); ?></span>
									<span class="lwp-person__stat-label"><?php echo esc_html( $r['label'] ); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $socials ) ) : ?>
						<ul class="lwp-person__socials">
							<?php foreach ( $socials as $s ) : ?>
								<li>
									<a href="<?php echo esc_url( $s['url'] ); ?>" target="_blank" rel="noopener me" aria-label="<?php echo esc_attr( $s['label'] ); ?>">
										<i class="<?php echo esc_attr( $s['icon'] ); ?>" aria-hidden="true"></i>
										<span class="lwp-person__social-label"><?php echo esc_html( $s['label'] ); ?></span>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			</header>

			<?php if ( $content ) : ?>
				<section class="lwp-person__bio">
					<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content filter chain. ?>
				</section>
			<?php endif; ?>

			<?php if ( $work_query && $work_query->have_posts() ) : ?>
				<section class="lwp-person__work">
					<h2 class="lwp-person__work-heading">
						<?php
						echo esc_html( sprintf(
							/* translators: %s: singular label, e.g. "Team member" */
							__( 'Related to this %s', 'luwipress-onyx' ),
							strtolower( $lwp_singular )
						) );
						?>
					</h2>
					<ul class="lwp-person__work-grid products columns-4">
						<?php while ( $work_query->have_posts() ) : $work_query->the_post(); ?>
							<?php wc_get_template_part( 'content', 'product' ); ?>
						<?php endwhile; ?>
					</ul>
				</section>
				<?php wp_reset_postdata(); ?>
			<?php endif; ?>

		</article>
	</main>

<?php endwhile;

get_footer();
