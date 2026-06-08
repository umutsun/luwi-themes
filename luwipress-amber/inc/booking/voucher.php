<?php
/**
 * LuwiPress Amber — tour voucher rendering.
 *
 * Builds the print-ready `.voucher` card (markup + CSS already ship in
 * assets/css/page-styles.css) from order + line-item + dispatch meta, and
 * surfaces it on the My Account order view, in the customer order email, and
 * on a standalone print endpoint. One card per tour line item.
 *
 * @package luwipress-amber
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'WooCommerce' ) ) { return; }

/**
 * Render the voucher card(s) for an order (one per tour line item).
 *
 * @param WC_Order $order
 * @param array    $args  with_links(bool)
 * @return string
 */
function lwp_amber_render_voucher( $order, $args = [] ) {
	if ( ! $order || ! is_object( $order ) ) { return ''; }
	$args = wp_parse_args( $args, [ 'with_links' => true ] );

	$site   = get_bloginfo( 'name' );
	$ready  = 'yes' === $order->get_meta( '_fbd_voucher_ready' );
	$cust   = trim( $order->get_formatted_billing_full_name() );
	$phone  = $order->get_billing_phone();
	$from   = (string) $order->get_meta( '_fbd_pickup_from' );
	$flight = (string) $order->get_meta( '_fbd_flight_no' );
	$pickup = (string) $order->get_meta( '_fbd_pickup_time' );

	$driver = (string) $order->get_meta( '_fbd_driver_name' );
	$d_mob  = (string) $order->get_meta( '_fbd_driver_mobile' );
	$vehic  = (string) $order->get_meta( '_fbd_vehicle' );
	$plate  = (string) $order->get_meta( '_fbd_plate' );

	$wa_phone = preg_replace( '/[^0-9]/', '', (string) get_theme_mod( 'luwipress_amber_topbar_phone', $phone ) );
	$wa_url   = $wa_phone ? 'https://wa.me/' . $wa_phone : '';

	$logo_id  = (int) get_theme_mod( 'custom_logo' );
	$logo_src = $logo_id ? wp_get_attachment_image_url( $logo_id, 'thumbnail' ) : '';

	$out = '';
	foreach ( $order->get_items() as $item_id => $item ) {
		$date = (string) $item->get_meta( '_fbd_tour_date' );
		if ( ! $date ) { continue; } // not a tour line

		$pax  = (int) $item->get_meta( '_fbd_pax' );
		$slot = (string) $item->get_meta( '_fbd_time_slot' );
		$tour = $item->get_name();

		ob_start();
		?>
		<div class="voucher">
			<div class="vou-top">
				<span class="brand">
					<?php if ( $logo_src ) : ?><img class="mark" src="<?php echo esc_url( $logo_src ); ?>" alt="<?php echo esc_attr( $site ); ?>"><?php endif; ?>
					<span class="word"><?php echo esc_html( $site ); ?></span>
				</span>
				<?php if ( $ready ) : ?><div class="vou-badge"><?php esc_html_e( 'Confirmed', 'luwipress-amber' ); ?></div><?php endif; ?>
			</div>
			<div class="vou-title"><?php echo esc_html( $tour ); ?></div>
			<div class="vou-grid">
				<div class="vou-f"><span class="vk"><?php esc_html_e( 'Date', 'luwipress-amber' ); ?></span><span class="vv"><?php echo esc_html( lwp_amber_format_voucher_date( $date ) ); ?></span></div>
				<div class="vou-f"><span class="vk"><?php esc_html_e( 'Pick-up time', 'luwipress-amber' ); ?></span><span class="vv"><?php echo esc_html( $pickup ?: ( $slot ?: '—' ) ); ?></span></div>
				<div class="vou-f"><span class="vk"><?php esc_html_e( 'Flight', 'luwipress-amber' ); ?></span><span class="vv"><?php echo esc_html( $flight ?: '—' ); ?></span></div>
				<div class="vou-f"><span class="vk"><?php esc_html_e( 'Pax', 'luwipress-amber' ); ?></span><span class="vv"><?php echo esc_html( (string) $pax ); ?></span></div>
				<div class="vou-f wide"><span class="vk"><?php esc_html_e( 'From', 'luwipress-amber' ); ?></span><span class="vv"><?php echo esc_html( $from ?: '—' ); ?></span></div>
				<div class="vou-f wide"><span class="vk"><?php esc_html_e( 'To', 'luwipress-amber' ); ?></span><span class="vv"><?php echo esc_html( $tour ); ?></span></div>
				<div class="vou-f"><span class="vk"><?php esc_html_e( 'Customer', 'luwipress-amber' ); ?></span><span class="vv"><?php echo esc_html( $cust ?: '—' ); ?></span></div>
				<div class="vou-f"><span class="vk"><?php esc_html_e( 'Contact', 'luwipress-amber' ); ?></span><span class="vv"><?php echo esc_html( $phone ?: '—' ); ?></span></div>
			</div>
			<?php if ( $driver ) : ?>
				<div class="vou-driver">
					<div class="vd-h"><?php esc_html_e( 'Your driver & vehicle', 'luwipress-amber' ); ?></div>
					<div class="vou-grid">
						<div class="vou-f"><span class="vk"><?php esc_html_e( 'Driver', 'luwipress-amber' ); ?></span><span class="vv"><?php echo esc_html( $driver ); ?></span></div>
						<div class="vou-f"><span class="vk"><?php esc_html_e( 'Mobile', 'luwipress-amber' ); ?></span><span class="vv"><?php echo esc_html( $d_mob ?: '—' ); ?></span></div>
						<div class="vou-f"><span class="vk"><?php esc_html_e( 'Vehicle', 'luwipress-amber' ); ?></span><span class="vv"><?php echo esc_html( $vehic ?: '—' ); ?></span></div>
						<div class="vou-f"><span class="vk"><?php esc_html_e( 'Plate number', 'luwipress-amber' ); ?></span><span class="vv"><?php echo esc_html( $plate ?: '—' ); ?></span></div>
					</div>
				</div>
			<?php endif; ?>
			<div class="vou-foot">
				<span><?php esc_html_e( 'Show this voucher to your driver · 24/7 concierge on WhatsApp', 'luwipress-amber' ); ?></span>
				<?php if ( $wa_url ) : ?><a class="btn btn--amber" href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Contact concierge', 'luwipress-amber' ); ?> →</a><?php endif; ?>
			</div>
			<?php if ( $args['with_links'] ) : ?>
				<div class="vou-links" style="display:flex;gap:14px;margin-top:14px;font-size:14px">
					<a href="<?php echo esc_url( lwp_amber_voucher_url( $order, $item_id, 'ics' ) ); ?>"><?php esc_html_e( 'Add to calendar (.ics)', 'luwipress-amber' ); ?></a>
					<a href="<?php echo esc_url( lwp_amber_voucher_url( $order, $item_id, 'print' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Print voucher', 'luwipress-amber' ); ?></a>
				</div>
			<?php endif; ?>
		</div>
		<?php
		$out .= ob_get_clean();
	}
	return $out;
}

/**
 * Build a signed voucher URL (print or ics). Auth = owning user OR order_key,
 * matching WC's order-pay/received link convention.
 *
 * @param WC_Order $order
 * @param int      $item_id
 * @param string   $what  'print'|'ics'
 * @return string
 */
function lwp_amber_voucher_url( $order, $item_id, $what ) {
	$key = ( 'ics' === $what ) ? 'fbd_ics' : 'fbd_voucher';
	return add_query_arg( [
		$key       => $order->get_id(),
		'item'     => (int) $item_id,
		'fbd_key'  => $order->get_order_key(),
	], home_url( '/' ) );
}

/**
 * Authorize a public voucher request: owning logged-in user OR matching order_key.
 *
 * @param WC_Order $order
 * @return bool
 */
function lwp_amber_voucher_can_view( $order ) {
	if ( ! $order ) { return false; }
	if ( is_user_logged_in() && (int) $order->get_user_id() === get_current_user_id() && $order->get_user_id() ) {
		return true;
	}
	$key = isset( $_GET['fbd_key'] ) ? sanitize_text_field( wp_unslash( $_GET['fbd_key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- order_key IS the secret.
	return $key && hash_equals( (string) $order->get_order_key(), $key );
}

/* ── My Account order view ────────────────────────────────────────────── */
add_action( 'woocommerce_view_order', function ( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order || ! lwp_amber_order_has_tour( $order ) ) { return; }
	echo '<section class="lwp-acct-voucher" style="margin-top:32px">';
	echo '<h2 class="h2" style="margin-bottom:18px">' . esc_html__( 'Your voucher', 'luwipress-amber' ) . '</h2>';
	echo lwp_amber_render_voucher( $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within renderer.
	echo '</section>';
}, 20 );

/* ── Customer order email ─────────────────────────────────────────────── */
add_action( 'woocommerce_email_after_order_table', function ( $order, $sent_to_admin, $plain_text ) {
	if ( $sent_to_admin || $plain_text ) { return; }
	if ( ! lwp_amber_order_has_tour( $order ) ) { return; }
	// Email clients strip <style> + CSS vars — the shipped .voucher CSS won't
	// apply, but the markup degrades to a readable labelled block. A fully
	// inline-styled variant is a Phase 2 refinement.
	echo '<h2 style="color:#C9572F;font-family:Georgia,serif">' . esc_html__( 'Your tour voucher', 'luwipress-amber' ) . '</h2>';
	echo lwp_amber_render_voucher( $order, [ 'with_links' => false ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 20, 3 );

/* ── Print endpoint ───────────────────────────────────────────────────── */
add_action( 'template_redirect', function () {
	if ( empty( $_GET['fbd_voucher'] ) ) { return; } // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$order = wc_get_order( absint( $_GET['fbd_voucher'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! $order || ! lwp_amber_voucher_can_view( $order ) ) {
		wp_die( esc_html__( 'Voucher not found.', 'luwipress-amber' ), '', [ 'response' => 404 ] );
	}
	$page_styles = LUWIPRESS_AMBER_URI . '/assets/css/page-styles.css';
	$amber_css   = LUWIPRESS_AMBER_URI . '/assets/css/amber.css';
	$tokens_css  = LUWIPRESS_AMBER_URI . '/assets/css/tokens.css';
	header( 'Content-Type: text/html; charset=utf-8' );
	?><!doctype html><html><head><meta charset="utf-8"><title><?php echo esc_html__( 'Tour voucher', 'luwipress-amber' ); ?></title>
	<link rel="stylesheet" href="<?php echo esc_url( $amber_css ); ?>">
	<link rel="stylesheet" href="<?php echo esc_url( $page_styles ); ?>">
	<link rel="stylesheet" href="<?php echo esc_url( $tokens_css ); ?>">
	<style>body{background:var(--bg-1,#211710);padding:40px}@media print{body{background:#fff}}.voucher{max-width:640px;margin:0 auto}</style>
	</head><body>
	<?php echo lwp_amber_render_voucher( $order, [ 'with_links' => false ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<script>window.addEventListener('load',function(){setTimeout(function(){window.print();},400);});</script>
	</body></html><?php
	exit;
}, 1 );
