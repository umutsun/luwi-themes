<?php
/**
 * Single Vendor template (CPT `lwp_vendor`).
 *
 * Pro-free renderer for LuwiPress Vendor profiles — E-E-A-T-grade author /
 * maker pages with verified social links, occupation chips, and a "Their
 * work" grid that joins to WooCommerce products via the `_lwp_vendor_ids`
 * product meta (LIKE-encoded JSON array).
 *
 * The labels (Vendor / Luthier / Chef / Artist / Team) come from the
 * LuwiPress Vendors settings — `singular_label` and `plural_label` — so
 * this template doesn't hard-code any vertical-specific vocabulary.
 *
 * Schema.org Person / Organization / LocalBusiness JSON-LD is emitted
 * automatically by LuwiPress_Vendors::build_vendor_schema() via the Schema
 * Registry on wp_head priority 6. Don't duplicate it here.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$singular_label = function_exists( 'LuwiPress_Vendors::get_setting' ) || class_exists( 'LuwiPress_Vendors' )
	? (string) LuwiPress_Vendors::get_setting( 'singular_label' )
	: __( 'Person', 'luwipress-gold' );
$plural_label = class_exists( 'LuwiPress_Vendors' )
	? (string) LuwiPress_Vendors::get_setting( 'plural_label' )
	: __( 'People', 'luwipress-gold' );
$archive_slug = class_exists( 'LuwiPress_Vendors' )
	? (string) LuwiPress_Vendors::get_setting( 'archive_slug' )
	: 'people';

while ( have_posts() ) : the_post();
	$post_id = get_the_ID();

	$name        = get_the_title( $post_id );
	$image_url   = get_the_post_thumbnail_url( $post_id, 'large' );
	$excerpt     = get_the_excerpt();
	$content     = apply_filters( 'the_content', get_post_field( 'post_content', $post_id ) );

	$location    = (string) get_post_meta( $post_id, '_lwp_vendor_location', true );
	$occupation  = (string) get_post_meta( $post_id, '_lwp_vendor_occupation', true );
	$specialty   = (string) get_post_meta( $post_id, '_lwp_vendor_specialty', true );
	$years       = (int)    get_post_meta( $post_id, '_lwp_vendor_years_active', true );
	$quote       = (string) get_post_meta( $post_id, '_lwp_vendor_quote', true );

	$socials = array(
		'website'    => array( 'icon' => 'eicon-globe',           'label' => __( 'Website', 'luwipress-gold' ),    'url' => get_post_meta( $post_id, '_lwp_vendor_website',    true ) ),
		'facebook'   => array( 'icon' => 'eicon-social-icon',     'label' => 'Facebook',                            'url' => get_post_meta( $post_id, '_lwp_vendor_facebook',   true ) ),
		'instagram'  => array( 'icon' => 'eicon-instagram',       'label' => 'Instagram',                           'url' => get_post_meta( $post_id, '_lwp_vendor_instagram',  true ) ),
		'youtube'    => array( 'icon' => 'eicon-youtube',         'label' => 'YouTube',                             'url' => get_post_meta( $post_id, '_lwp_vendor_youtube',    true ) ),
		'soundcloud' => array( 'icon' => 'eicon-soundcloud',      'label' => 'SoundCloud',                          'url' => get_post_meta( $post_id, '_lwp_vendor_soundcloud', true ) ),
		'linkedin'   => array( 'icon' => 'eicon-linkedin',        'label' => 'LinkedIn',                            'url' => get_post_meta( $post_id, '_lwp_vendor_linkedin',   true ) ),
		'x'          => array( 'icon' => 'eicon-twitter',         'label' => 'X (Twitter)',                         'url' => get_post_meta( $post_id, '_lwp_vendor_x',          true ) ),
		'behance'    => array( 'icon' => 'eicon-behance',         'label' => 'Behance',                             'url' => get_post_meta( $post_id, '_lwp_vendor_behance',    true ) ),
	);
	$active_socials = array_filter( $socials, function ( $s ) { return ! empty( $s['url'] ); } );

	// Their work — query WC products that reference this vendor via meta.
	// Standard meta key contract: `_lwp_vendor_ids` on the product, JSON array of vendor IDs.
	//
	// Tolerates BOTH historical formats: quoted (`["36633"]` — what the
	// LuwiPress_Vendors save handler emits) and unquoted (`[36633]` — what
	// some third-party seed scripts and direct meta writes produce). Uses a
	// JSON-array-element-aware REGEXP so `36633` doesn't false-match against
	// `366331`.
	$work_query = null;
	if ( class_exists( 'WooCommerce' ) ) {
		$vendor_regex = '(\\[|,)[[:space:]]*"?' . preg_quote( (string) $post_id, '/' ) . '"?[[:space:]]*(,|\\])';
		$work_query = new WP_Query( array(
			'post_type'      => 'product',
			'posts_per_page' => 8,
			'meta_query'     => array(
				array(
					'key'     => '_lwp_vendor_ids',
					'value'   => $vendor_regex,
					'compare' => 'REGEXP',
				),
			),
			'no_found_rows' => false,
		) );
	}
	?>

	<main class="lwp-person-page" id="primary">
		<article class="lwp-person" itemscope itemtype="https://schema.org/Person">

			<header class="lwp-person__hero">
				<?php if ( $image_url ) : ?>
					<div class="lwp-person__portrait">
						<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $name ); ?>" itemprop="image" />
					</div>
				<?php endif; ?>

				<div class="lwp-person__intro">
					<p class="lwp-person__eyebrow">
						<a href="<?php echo esc_url( get_post_type_archive_link( 'lwp_vendor' ) ); ?>">
							<?php echo esc_html( $plural_label ); ?>
						</a>
						<?php if ( $location ) : ?>
							<span class="lwp-person__sep">·</span>
							<span itemprop="homeLocation"><?php echo esc_html( $location ); ?></span>
						<?php endif; ?>
					</p>

					<h1 class="lwp-person__name" itemprop="name"><?php echo esc_html( $name ); ?></h1>

					<?php if ( $occupation || $specialty ) : ?>
						<p class="lwp-person__occupation" itemprop="jobTitle">
							<?php echo esc_html( $occupation ?: $specialty ); ?>
						</p>
					<?php endif; ?>

					<?php if ( $excerpt ) : ?>
						<p class="lwp-person__lead"><?php echo esc_html( $excerpt ); ?></p>
					<?php endif; ?>

					<div class="lwp-person__meta-strip">
						<?php if ( $years > 0 ) : ?>
							<div class="lwp-person__stat">
								<span class="lwp-person__stat-num"><?php echo esc_html( $years ); ?></span>
								<span class="lwp-person__stat-label"><?php esc_html_e( 'years active', 'luwipress-gold' ); ?></span>
							</div>
						<?php endif; ?>
						<?php if ( $specialty ) : ?>
							<div class="lwp-person__stat">
								<span class="lwp-person__stat-num"><?php echo esc_html( $specialty ); ?></span>
								<span class="lwp-person__stat-label"><?php esc_html_e( 'specialty', 'luwipress-gold' ); ?></span>
							</div>
						<?php endif; ?>
						<?php if ( $work_query && $work_query->found_posts > 0 ) : ?>
							<div class="lwp-person__stat">
								<span class="lwp-person__stat-num"><?php echo esc_html( $work_query->found_posts ); ?></span>
								<span class="lwp-person__stat-label"><?php esc_html_e( 'works', 'luwipress-gold' ); ?></span>
							</div>
						<?php endif; ?>
					</div>

					<?php if ( ! empty( $active_socials ) ) : ?>
						<ul class="lwp-person__socials">
							<?php foreach ( $active_socials as $key => $s ) : ?>
								<li>
									<a href="<?php echo esc_url( $s['url'] ); ?>" target="_blank" rel="noopener me" itemprop="sameAs" aria-label="<?php echo esc_attr( $s['label'] ); ?>">
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
				<section class="lwp-person__bio" itemprop="description">
					<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content filter chain. ?>
				</section>
			<?php endif; ?>

			<?php if ( $quote ) : ?>
				<aside class="lwp-person__quote">
					<blockquote>
						<?php echo wp_kses_post( $quote ); ?>
					</blockquote>
					<cite>— <?php echo esc_html( $name ); ?></cite>
				</aside>
			<?php endif; ?>

			<?php if ( $work_query && $work_query->have_posts() ) : ?>
				<section class="lwp-person__work">
					<h2 class="lwp-person__work-heading">
						<?php
						echo esc_html( sprintf(
							/* translators: %s: singular label, e.g. "Luthier" */
							__( 'Made by this %s', 'luwipress-gold' ),
							strtolower( $singular_label )
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
