<?php
/**
 * Template Name: About / Atelier (Tapadum Gold)
 *
 * Editorial about page — Mobile Spec Preview §04.
 * Composed of: cover (3:4 with eyebrow + display title) → mission +
 * pull-quote → timeline → team grid → atelier h-scroll gallery → CTA.
 *
 * If the operator builds the page in Elementor we exit early and let
 * Elementor render — same pattern as page.php.
 *
 * Optional ACF (or filterable) data:
 *   - about_cover_image (image url)
 *   - about_eyebrow (string)
 *   - about_quote + about_quote_author (string + string)
 *   - about_timeline (array of [year, label])
 *   - about_team (array of [photo_url, name, role])
 *   - about_gallery (array of image urls)
 *
 * Each block can also be filtered via `luwipress_emerald_about_*`.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$post_id        = get_queried_object_id();
$is_el_built    = $post_id && (bool) get_post_meta( $post_id, '_elementor_edit_mode', true );
$has_el_data    = $post_id && (bool) get_post_meta( $post_id, '_elementor_data', true );
$elementor_page = $is_el_built && $has_el_data;

if ( $elementor_page ) {
	?>
	<main class="lwp-elementor-page" id="primary">
		<?php while ( have_posts() ) : the_post(); the_content(); endwhile; ?>
	</main>
	<?php
	get_footer();
	return;
}

// Optional ACF / fallback data
$cover_image = function_exists( 'get_field' ) ? get_field( 'about_cover_image', $post_id ) : '';
if ( ! $cover_image ) {
	$cover_image = get_the_post_thumbnail_url( $post_id, 'full' );
}
$eyebrow_text = function_exists( 'get_field' ) ? get_field( 'about_eyebrow', $post_id ) : '';
if ( ! $eyebrow_text ) {
	$eyebrow_text = apply_filters( 'luwipress_emerald_about_eyebrow', __( 'Brisighella · Italy', 'luwipress-emerald' ) );
}
$quote_text   = function_exists( 'get_field' ) ? get_field( 'about_quote', $post_id ) : '';
$quote_author = function_exists( 'get_field' ) ? get_field( 'about_quote_author', $post_id ) : '';

$timeline = function_exists( 'get_field' ) ? (array) get_field( 'about_timeline', $post_id ) : [];
$timeline = apply_filters( 'luwipress_emerald_about_timeline', $timeline );

$team = function_exists( 'get_field' ) ? (array) get_field( 'about_team', $post_id ) : [];
$team = apply_filters( 'luwipress_emerald_about_team', $team );

$gallery = function_exists( 'get_field' ) ? (array) get_field( 'about_gallery', $post_id ) : [];
$gallery = apply_filters( 'luwipress_emerald_about_gallery', $gallery );
?>

<main class="lwp-page page-about" id="primary">
	<?php while ( have_posts() ) : the_post(); ?>

	<div class="lwp-cover" <?php if ( $cover_image ) : ?>style="background-image:linear-gradient(135deg,rgba(31,26,14,.4),rgba(90,69,32,.6)),url('<?php echo esc_url( $cover_image ); ?>');background-size:cover;background-position:center"<?php endif; ?>>
		<div style="display:flex;flex-direction:column;gap:10px">
			<span class="eyebrow"><?php echo esc_html( $eyebrow_text ); ?></span>
			<h1><?php
				/* Wrap the *last word* of the title in <em> for italic accent. */
				$title = get_the_title();
				$parts = preg_split( '/\s+/', $title );
				if ( count( $parts ) > 1 ) {
					$last = array_pop( $parts );
					echo esc_html( implode( ' ', $parts ) ) . ' <em>' . esc_html( $last ) . '</em>';
				} else {
					echo esc_html( $title );
				}
			?></h1>
		</div>
	</div>

	<section style="padding:24px 20px;display:flex;flex-direction:column;gap:14px">
		<span class="eyebrow" style="font-family:var(--mono);font-size:9.5px;letter-spacing:.22em;text-transform:uppercase;color:var(--muted)"><?php esc_html_e( '01 · The mission', 'luwipress-emerald' ); ?></span>
		<div class="lwp-about-mission" style="font-size:14.5px;line-height:1.6;color:var(--ink-soft)">
			<?php the_content(); ?>
		</div>
		<?php if ( $quote_text ) : ?>
			<div class="divider" role="presentation" aria-hidden="true"></div>
			<p class="lwp-pullquote">"<?php echo esc_html( $quote_text ); ?>"</p>
			<?php if ( $quote_author ) : ?>
				<span class="lwp-pullquote-author">— <?php echo esc_html( $quote_author ); ?></span>
			<?php endif; ?>
		<?php endif; ?>
	</section>

	<?php if ( $timeline ) : ?>
	<section style="background:var(--bg-alt);padding:24px 20px;display:flex;flex-direction:column;gap:18px">
		<span class="eyebrow" style="font-family:var(--mono);font-size:9.5px;letter-spacing:.22em;text-transform:uppercase;color:var(--muted)"><?php esc_html_e( '02 · Timeline', 'luwipress-emerald' ); ?></span>
		<h2 style="font-family:var(--serif);font-size:22px;font-weight:500;line-height:1.25;letter-spacing:-.005em;margin:0"><?php
			echo wp_kses(
				__( 'Years on the bench, <em>line by line</em>', 'luwipress-emerald' ),
				[ 'em' => [] ]
			);
		?></h2>
		<ul class="lwp-timeline">
			<?php foreach ( $timeline as $row ) :
				$yr  = $row['year']  ?? ( $row[0] ?? '' );
				$lbl = $row['title'] ?? ( $row[1] ?? '' );
				if ( ! $yr || ! $lbl ) continue;
			?>
				<li><span class="yr"><?php echo esc_html( $yr ); ?></span><span class="lbl"><?php echo esc_html( $lbl ); ?></span></li>
			<?php endforeach; ?>
		</ul>
	</section>
	<?php endif; ?>

	<?php if ( $team ) : ?>
	<section style="padding:24px 20px;display:flex;flex-direction:column;gap:14px">
		<span class="eyebrow" style="font-family:var(--mono);font-size:9.5px;letter-spacing:.22em;text-transform:uppercase;color:var(--muted)"><?php esc_html_e( '03 · The team', 'luwipress-emerald' ); ?></span>
		<h2 style="font-family:var(--serif);font-size:22px;font-weight:500;margin:0"><?php
			/* translators: editorial team grid heading */
			echo wp_kses(
				__( 'A few hands in <em>one room</em>', 'luwipress-emerald' ),
				[ 'em' => [] ]
			);
		?></h2>
		<div class="lwp-team-grid">
			<?php foreach ( $team as $member ) :
				$photo = $member['photo']   ?? '';
				$name  = $member['name']    ?? '';
				$role  = $member['role']    ?? '';
				if ( ! $name ) continue;
			?>
				<div>
					<?php if ( $photo ) : ?>
						<img src="<?php echo esc_url( $photo ); ?>" alt="<?php echo esc_attr( $name ); ?>" loading="lazy" decoding="async" />
					<?php else : ?>
						<div class="ph" style="background:linear-gradient(135deg,#3d2f1f,#7a5a2c)"></div>
					<?php endif; ?>
					<div class="nm"><?php echo esc_html( $name ); ?></div>
					<?php if ( $role ) : ?><div class="role"><?php echo esc_html( $role ); ?></div><?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
	<?php endif; ?>

	<?php if ( $gallery ) : ?>
	<section style="background:#15110b;color:#cfc7b3;padding:24px 20px;display:flex;flex-direction:column;gap:14px">
		<span class="eyebrow" style="font-family:var(--mono);font-size:9.5px;letter-spacing:.22em;text-transform:uppercase;color:#a39c8e"><?php esc_html_e( '04 · Inside the atelier', 'luwipress-emerald' ); ?></span>
		<h2 style="font-family:var(--serif);font-size:22px;font-weight:500;color:#fff;margin:0"><?php
			echo wp_kses(
				__( 'From <em>raw wood</em> to first pluck', 'luwipress-emerald' ),
				[ 'em' => [] ]
			);
		?></h2>
		<div class="lwp-h-scroll">
			<?php foreach ( $gallery as $img ) :
				$src = is_array( $img ) ? ( $img['url'] ?? '' ) : $img;
				if ( ! $src ) continue;
			?>
				<img src="<?php echo esc_url( $src ); ?>" alt="" loading="lazy" decoding="async" />
			<?php endforeach; ?>
		</div>
	</section>
	<?php endif; ?>

	<?php endwhile; ?>
</main>

<?php
get_footer();
