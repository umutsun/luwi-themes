<?php
/**
 * Single Master template — Mobile Spec Preview §05.
 *
 * Activates only if a `master` CPT exists. Layout: portrait hero (3:4)
 * → meta strip (years / pieces / joined) → bio → signature pieces →
 * audio sample → ink CTA.
 *
 * Operators that don't ship the `master` CPT can safely ignore — WP
 * never reaches this file unless `is_singular( 'master' )`.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

while ( have_posts() ) : the_post();
	$post_id = get_the_ID();

	$portrait = function_exists( 'get_field' ) ? get_field( 'master_portrait', $post_id ) : '';
	if ( ! $portrait ) {
		$portrait = get_the_post_thumbnail_url( $post_id, 'full' );
	}

	$years_active = function_exists( 'get_field' ) ? get_field( 'master_years', $post_id ) : '';
	$pieces       = function_exists( 'get_field' ) ? get_field( 'master_pieces_count', $post_id ) : '';
	$joined_year  = function_exists( 'get_field' ) ? get_field( 'master_joined_year', $post_id ) : '';
	$specialty    = function_exists( 'get_field' ) ? get_field( 'master_specialty', $post_id ) : '';
	$location     = function_exists( 'get_field' ) ? get_field( 'master_location', $post_id ) : '';

	$audio_url    = function_exists( 'get_field' ) ? get_field( 'master_audio', $post_id ) : '';
	$audio_label  = function_exists( 'get_field' ) ? get_field( 'master_audio_label', $post_id ) : __( 'Hicaz', 'luwipress-amber' );

	$quote        = function_exists( 'get_field' ) ? get_field( 'master_quote', $post_id ) : '';

	// Pieces dynamically — products tagged with this master via ACF post-object back-ref.
	$signature_query = new WP_Query( [
		'post_type'      => 'product',
		'posts_per_page' => 6,
		'meta_query'     => [
			[
				'key'     => 'product_master',
				'value'   => '"' . $post_id . '"',
				'compare' => 'LIKE',
			],
		],
	] );
	if ( ! $pieces ) {
		$pieces = $signature_query->found_posts;
	}

	$specialty_line = trim( implode( ' · ', array_filter( [ $location, $specialty ] ) ) );
?>

<main class="lwp-page lwp-master-single" id="primary">

	<div class="master-portrait" <?php if ( $portrait ) : ?>style="background-image:linear-gradient(135deg,rgba(61,47,31,.4),rgba(122,90,44,.6)),url('<?php echo esc_url( $portrait ); ?>');background-size:cover;background-position:center"<?php endif; ?>>
		<div style="display:flex;flex-direction:column;gap:6px">
			<span class="eyebrow" style="font-family:var(--mono);font-size:9.5px;letter-spacing:.22em;text-transform:uppercase;color:var(--primary-light, #D4AF37)">
				<?php esc_html_e( 'Master luthier', 'luwipress-amber' ); ?>
			</span>
			<h1><?php
				$title = get_the_title();
				$parts = preg_split( '/\s+/', $title );
				if ( count( $parts ) > 1 ) {
					$last = array_pop( $parts );
					echo esc_html( implode( ' ', $parts ) ) . ' <em>' . esc_html( $last ) . '</em>';
				} else {
					echo esc_html( $title );
				}
			?></h1>
			<?php if ( $specialty_line ) : ?>
				<span class="specialty"><?php echo esc_html( $specialty_line ); ?></span>
			<?php endif; ?>
		</div>
	</div>

	<div class="master-meta">
		<?php if ( $years_active ) : ?>
		<div class="cell">
			<span class="n"><?php echo esc_html( $years_active ); ?></span>
			<span class="l"><?php esc_html_e( 'Years', 'luwipress-amber' ); ?></span>
		</div>
		<?php endif; ?>
		<?php if ( $pieces ) : ?>
		<div class="cell">
			<span class="n"><?php echo esc_html( $pieces ); ?></span>
			<span class="l"><?php esc_html_e( 'Pieces', 'luwipress-amber' ); ?></span>
		</div>
		<?php endif; ?>
		<?php if ( $joined_year ) : ?>
		<div class="cell">
			<span class="n"><?php echo esc_html( $joined_year ); ?></span>
			<span class="l"><?php esc_html_e( 'Joined', 'luwipress-amber' ); ?></span>
		</div>
		<?php endif; ?>
	</div>

	<section style="padding:24px 20px;display:flex;flex-direction:column;gap:12px">
		<span class="eyebrow" style="font-family:var(--mono);font-size:9.5px;letter-spacing:.22em;text-transform:uppercase;color:var(--muted)"><?php esc_html_e( 'Bio', 'luwipress-amber' ); ?></span>
		<div class="lwp-master-bio" style="font-size:15px;line-height:1.65;color:var(--ink-soft)">
			<?php the_content(); ?>
		</div>
	</section>

	<?php if ( $signature_query->have_posts() ) : ?>
	<section style="background:var(--bg-alt);padding:24px 20px;display:flex;flex-direction:column;gap:14px">
		<span class="eyebrow" style="font-family:var(--mono);font-size:9.5px;letter-spacing:.22em;text-transform:uppercase;color:var(--muted)"><?php esc_html_e( 'Signature instruments', 'luwipress-amber' ); ?></span>
		<h2 style="font-family:var(--serif);font-size:22px;font-weight:500;margin:0"><?php
			/* translators: %d signature piece count */
			printf( esc_html( _n( '%d piece in circulation', '%d pieces in circulation', (int) $pieces, 'luwipress-amber' ) ), (int) $pieces );
		?></h2>
		<ul class="products columns-2" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;list-style:none;padding:0;margin:0">
			<?php while ( $signature_query->have_posts() ) : $signature_query->the_post();
				if ( ! function_exists( 'wc_get_product' ) ) continue;
				$prod = wc_get_product( get_the_ID() );
				if ( ! $prod ) continue;
			?>
				<li class="product">
					<a href="<?php the_permalink(); ?>" style="display:flex;flex-direction:column;gap:6px;text-decoration:none;color:inherit">
						<?php
						if ( has_post_thumbnail() ) {
							the_post_thumbnail( 'medium', [ 'style' => 'width:100%;aspect-ratio:4/5;object-fit:cover;border-radius:8px' ] );
						} else {
							echo '<div style="width:100%;aspect-ratio:4/5;border-radius:8px;background:linear-gradient(135deg,#3d2f1f,#7a5a2c)"></div>';
						}
						?>
						<h4 class="woocommerce-loop-product__title" style="font-family:var(--serif);font-size:15px;font-weight:500;line-height:1.3;margin:4px 0 0"><?php the_title(); ?></h4>
						<span class="price" style="font-family:var(--serif);font-size:15px;color:var(--primary)"><?php echo wp_kses_post( $prod->get_price_html() ); ?></span>
					</a>
				</li>
			<?php endwhile; ?>
		</ul>
		<a href="<?php echo esc_url( add_query_arg( [ 'master' => $post_id ], wc_get_page_permalink( 'shop' ) ) ); ?>" class="cta-link" style="font-family:var(--mono);font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:var(--primary);align-self:flex-start"><?php
			/* translators: %s: master display name */
			printf( esc_html__( 'Browse all from %s →', 'luwipress-amber' ), esc_html( get_the_title( $post_id ) ) );
		?></a>
	</section>
	<?php wp_reset_postdata(); endif; ?>

	<?php if ( $audio_url ) : ?>
	<section style="padding:24px 20px;display:flex;flex-direction:column;gap:12px">
		<span class="eyebrow" style="font-family:var(--mono);font-size:9.5px;letter-spacing:.22em;text-transform:uppercase;color:var(--muted)"><?php esc_html_e( 'Listen', 'luwipress-amber' ); ?></span>
		<h2 style="font-family:var(--serif);font-size:22px;font-weight:500;margin:0"><?php
			/* translators: %s: makam name (e.g. Hicaz) */
			printf( esc_html__( 'Tuning sample · %s', 'luwipress-amber' ), '<em>' . esc_html( $audio_label ) . '</em>' );
		?></h2>
		<div class="lwp-audio-card" data-audio-src="<?php echo esc_url( $audio_url ); ?>">
			<button class="play" aria-label="<?php esc_attr_e( 'Play audio sample', 'luwipress-amber' ); ?>">▶</button>
			<div class="progress">
				<div class="bar"><i style="width:0%"></i></div>
				<div class="time"><span class="cur">0:00</span><span class="dur">--:--</span></div>
			</div>
			<audio preload="none" src="<?php echo esc_url( $audio_url ); ?>"></audio>
		</div>
	</section>
	<?php endif; ?>

	<?php if ( $quote ) : ?>
	<section style="background:#15110b;color:#f6f3f2;padding:24px 20px;text-align:center;display:flex;flex-direction:column;gap:12px">
		<p style="font-family:var(--serif);font-style:italic;font-size:18px;line-height:1.4;margin:0">"<?php echo esc_html( $quote ); ?>"</p>
		<a href="#" class="cta-pill" style="align-self:center;padding:12px 22px;background:var(--primary);color:#fff;border-radius:999px;font-family:var(--mono);font-size:11px;letter-spacing:.14em;text-transform:uppercase;text-decoration:none">
			<?php
			/* translators: %s: master first name */
			printf( esc_html__( "Visit %s's atelier →", 'luwipress-amber' ), esc_html( get_the_title( $post_id ) ) );
			?>
		</a>
	</section>
	<?php endif; ?>

</main>

<?php endwhile; ?>

<?php
get_footer();
