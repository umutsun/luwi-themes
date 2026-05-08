<?php
/**
 * Template Name: Contact (Tapadum Gold)
 *
 * Editorial contact page — Mobile Spec Preview §08.
 * Composed of: header (eyebrow + h-display) → reason chips → form
 * (Fluent / WPForms / native) → contact list-rows → static map → hours.
 *
 * Operators using Elementor on the Contact page will land in the
 * Elementor branch (page.php pattern) — this template is the markup
 * fallback for a non-Elementor page or a page-template assignment.
 *
 * Reason chips, contact rows, hours rows are all filterable so a child
 * theme or LuwiPress companion can override them without touching this file.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$post_id        = get_queried_object_id();
$is_el_built    = $post_id && (bool) get_post_meta( $post_id, '_elementor_edit_mode', true );
$has_el_data    = $post_id && (bool) get_post_meta( $post_id, '_elementor_data', true );
$elementor_page = $is_el_built && $has_el_data;

if ( $elementor_page ) {
	// Elementor page is canonical — render its content full-width and exit.
	?>
	<main class="lwp-elementor-page" id="primary">
		<?php while ( have_posts() ) : the_post(); the_content(); endwhile; ?>
	</main>
	<?php
	get_footer();
	return;
}

/**
 * Reason chips — filterable. Each entry: [ 'label' => string, 'value' => slug ].
 * Default chip set comes from Mobile Spec Preview §08.
 *
 * @since 1.5.4
 */
$reasons = apply_filters( 'luwipress_gold_contact_reasons', [
	[ 'label' => __( 'An instrument', 'luwipress-gold' ), 'value' => 'instrument' ],
	[ 'label' => __( 'A custom build', 'luwipress-gold' ), 'value' => 'custom' ],
	[ 'label' => __( 'An order', 'luwipress-gold' ), 'value' => 'order' ],
	[ 'label' => __( 'Lessons', 'luwipress-gold' ), 'value' => 'lessons' ],
	[ 'label' => __( 'Press', 'luwipress-gold' ), 'value' => 'press' ],
] );

$contact_rows = apply_filters( 'luwipress_gold_contact_rows', [
	[ 'ic' => '☎', 'label' => get_option( 'luwipress_gold_contact_phone', '' ), 'meta' => __( 'Mon–Sat', 'luwipress-gold' ), 'href' => 'tel:' ],
	[ 'ic' => '✉', 'label' => get_option( 'admin_email', '' ), 'meta' => __( '24h reply', 'luwipress-gold' ), 'href' => 'mailto:' ],
] );

$hours = apply_filters( 'luwipress_gold_contact_hours', [
	[ 'day' => __( 'Tue – Fri', 'luwipress-gold' ), 'val' => '10 – 18' ],
	[ 'day' => __( 'Saturday', 'luwipress-gold' ),   'val' => '10 – 13' ],
	[ 'day' => __( 'Sun & Mon', 'luwipress-gold' ),  'val' => __( 'Closed', 'luwipress-gold' ), 'closed' => true ],
] );
?>

<main class="lwp-page page-contact" id="primary">

	<?php while ( have_posts() ) : the_post(); ?>

	<header style="padding:24px 20px 14px;display:flex;flex-direction:column;gap:10px">
		<span class="eyebrow" style="font-family:var(--mono);font-size:9.5px;letter-spacing:.22em;text-transform:uppercase;color:var(--muted)"><?php esc_html_e( 'Get in touch', 'luwipress-gold' ); ?></span>
		<h1 class="lwp-page-title" style="font-family:var(--serif);font-size:30px;font-weight:500;line-height:1.15;letter-spacing:-.012em;margin:0"><?php the_title(); ?></h1>
		<?php
		$excerpt = get_the_excerpt();
		if ( $excerpt ) :
		?>
			<p class="lwp-page-lead" style="font-size:15px;color:var(--ink-soft);line-height:1.55"><?php echo esc_html( $excerpt ); ?></p>
		<?php endif; ?>
	</header>

	<?php if ( ! empty( $reasons ) ) : ?>
	<section style="padding:0 20px 4px">
		<span class="eyebrow" style="display:block;margin-bottom:8px;font-family:var(--mono);font-size:9.5px;letter-spacing:.22em;text-transform:uppercase;color:var(--muted)"><?php esc_html_e( "I'm writing about", 'luwipress-gold' ); ?></span>
		<div class="lwp-reason-chips" role="radiogroup" aria-label="<?php esc_attr_e( 'Reason for contact', 'luwipress-gold' ); ?>">
			<?php foreach ( $reasons as $i => $row ) : ?>
				<button type="button" data-reason="<?php echo esc_attr( $row['value'] ?? '' ); ?>" role="radio" aria-checked="<?php echo $i === 0 ? 'true' : 'false'; ?>" class="<?php echo $i === 0 ? 'is-active' : ''; ?>"><?php echo esc_html( $row['label'] ); ?></button>
			<?php endforeach; ?>
		</div>
	</section>
	<?php endif; ?>

	<section class="lwp-contact-form" style="padding:18px 20px;display:flex;flex-direction:column;gap:12px">
		<?php
		$form_html = apply_filters( 'luwipress_gold_contact_form_html', '' );
		if ( $form_html ) {
			echo $form_html; // phpcs:ignore — opt-in filter for trusted form output
		} else {
			the_content();
		}
		?>
	</section>

	<?php endwhile; ?>

	<div class="divider" role="presentation" aria-hidden="true"></div>

	<?php
	$rows_to_render = array_filter( $contact_rows, function ( $r ) {
		return ! empty( $r['label'] );
	} );
	if ( $rows_to_render ) : ?>
	<section class="lwp-contact-list">
		<?php foreach ( $rows_to_render as $i => $row ) :
			$last = ( $i === array_key_last( $rows_to_render ) );
			$href = '#';
			if ( ! empty( $row['href'] ) && $row['href'] === 'tel:' ) {
				$href = 'tel:' . preg_replace( '/[^\d+]/', '', $row['label'] );
			} elseif ( ! empty( $row['href'] ) && $row['href'] === 'mailto:' ) {
				$href = 'mailto:' . $row['label'];
			} elseif ( ! empty( $row['href'] ) ) {
				$href = $row['href'];
			}
		?>
			<a class="lwp-list-row" href="<?php echo esc_url( $href ); ?>"<?php echo $last ? ' style="border-bottom:0"' : ''; ?>>
				<span class="ic" aria-hidden="true"><?php echo esc_html( $row['ic'] ?? '' ); ?></span>
				<span class="nm"><?php echo esc_html( $row['label'] ); ?></span>
				<span class="val"><?php echo esc_html( $row['meta'] ?? '' ); ?></span>
			</a>
		<?php endforeach; ?>
	</section>
	<?php endif; ?>

	<?php
	$map_embed = apply_filters( 'luwipress_gold_contact_map_embed', '' );
	$map_lat   = apply_filters( 'luwipress_gold_contact_map_lat', '' );
	if ( $map_embed || $map_lat ) :
	?>
	<section style="padding:8px 20px 20px">
		<?php if ( $map_embed ) : ?>
			<?php echo $map_embed; // phpcs:ignore — opt-in filter ?>
		<?php else : ?>
			<div class="lwp-static-map">
				<span class="pin" aria-hidden="true"></span>
			</div>
		<?php endif; ?>
	</section>
	<?php endif; ?>

	<?php if ( $hours ) : ?>
	<section class="lwp-hours" style="background:var(--bg-alt);padding:18px 20px;display:flex;flex-direction:column;gap:8px">
		<span class="eyebrow" style="font-family:var(--mono);font-size:9.5px;letter-spacing:.22em;text-transform:uppercase;color:var(--muted)"><?php esc_html_e( 'Opening hours', 'luwipress-gold' ); ?></span>
		<?php foreach ( $hours as $i => $row ) :
			$last = ( $i === count( $hours ) - 1 );
		?>
			<div class="lwp-list-row" style="border-bottom:<?php echo $last ? '0' : '1px solid var(--line)'; ?>;padding-left:0;padding-right:0">
				<span class="nm" style="font-family:var(--sans);font-size:14px"><?php echo esc_html( $row['day'] ); ?></span>
				<span class="val<?php echo ! empty( $row['closed'] ) ? ' closed' : ''; ?>"><?php echo esc_html( $row['val'] ); ?></span>
			</div>
		<?php endforeach; ?>
	</section>
	<?php endif; ?>

</main>

<?php
get_footer();
