<?php
/**
 * Template Name: Onyx — Contact
 *
 * Contact page (onyx-page-contact.jsx): form (real wp_mail handler, or the
 * operator's Contact Form 7 / WPForms shortcode when present) + contact
 * panel + embedded map. Yields to Elementor when the page is built with it.
 *
 * @package luwipress-onyx
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$onyx_pid = get_queried_object_id();
if ( $onyx_pid && get_post_meta( $onyx_pid, '_elementor_edit_mode', true ) && get_post_meta( $onyx_pid, '_elementor_data', true ) ) {
	get_header();
	while ( have_posts() ) { the_post(); the_content(); }
	get_footer();
	return;
}

get_header();

$onyx_status  = isset( $_GET['onyx_contact'] ) ? sanitize_key( wp_unslash( $_GET['onyx_contact'] ) ) : '';
$onyx_phone   = trim( (string) get_theme_mod( 'luwipress_onyx_topbar_phone', '056 776 1946' ) );
$onyx_email   = trim( (string) get_theme_mod( 'luwipress_onyx_topbar_email', 'sales@arshahomes.com' ) );
$onyx_address = trim( (string) get_theme_mod( 'luwipress_onyx_topbar_location', 'Blue Bay Tower, Office 608 — Business Bay, Dubai' ) );
$onyx_map_q   = trim( (string) get_theme_mod( 'luwipress_onyx_map_query', 'Business Bay Dubai' ) );

// If the operator dropped a form-plugin shortcode into the page content,
// render that (styled by .cform) instead of the built-in handler.
$onyx_content   = $onyx_pid ? (string) get_post_field( 'post_content', $onyx_pid ) : '';
$onyx_has_form  = has_shortcode( $onyx_content, 'contact-form-7' ) || has_shortcode( $onyx_content, 'wpforms' ) || has_shortcode( $onyx_content, 'gravityform' );

$onyx_interests = array(
	__( 'A specific residence', 'luwipress-onyx' ),
	__( 'Buying — general enquiry', 'luwipress-onyx' ),
	__( 'Selling my property', 'luwipress-onyx' ),
	__( 'Off-plan investment', 'luwipress-onyx' ),
	__( 'Private viewing', 'luwipress-onyx' ),
	__( 'Something else', 'luwipress-onyx' ),
);

$onyx_contacts = array(
	array( 'pin', __( 'Visit', 'luwipress-onyx' ), esc_html( $onyx_address ), __( 'Business Bay, Dubai, UAE', 'luwipress-onyx' ) ),
	array( 'phone', __( 'Call', 'luwipress-onyx' ), '<a href="' . esc_url( 'tel:' . preg_replace( '/\s+/', '', $onyx_phone ) ) . '">' . esc_html( $onyx_phone ) . '</a>', __( 'Sun–Thu, 9:00 — 19:00 GST', 'luwipress-onyx' ) ),
	array( 'mail', __( 'Email', 'luwipress-onyx' ), '<a href="' . esc_url( 'mailto:' . $onyx_email ) . '">' . esc_html( $onyx_email ) . '</a>', __( 'We reply within one business day', 'luwipress-onyx' ) ),
	array( 'clock', __( 'Hours', 'luwipress-onyx' ), esc_html__( 'Sunday — Thursday', 'luwipress-onyx' ), __( '9:00 — 19:00 · Fri/Sat by appointment', 'luwipress-onyx' ) ),
);
?>

<main>
	<section class="phead">
		<div class="wrap">
			<div class="crumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'luwipress-onyx' ); ?></a><span class="sep">/</span><span><?php esc_html_e( 'Contact', 'luwipress-onyx' ); ?></span></div>
			<span class="smallcaps" style="color:var(--gold)"><?php esc_html_e( "Let's Talk Quietly", 'luwipress-onyx' ); ?></span>
			<h1 class="display-xl" style="margin-top:14px;font-size:clamp(44px,6vw,84px)"><?php esc_html_e( 'Get in touch', 'luwipress-onyx' ); ?></h1>
			<p class="phead-sub"><?php esc_html_e( "One conversation, one advisor, no call-centre. Tell us what you're looking for — or simply that you'd like to look — and we'll take it from there.", 'luwipress-onyx' ); ?></p>
		</div>
	</section>

	<section class="section" style="padding-top:clamp(44px,5vw,72px)">
		<div class="wrap">
			<div class="contact-grid">
				<div class="reveal">
					<?php if ( 'sent' === $onyx_status ) : ?>
						<div class="cform-done">
							<span class="ic"><?php echo onyx_icon( 'check', 40 ); // phpcs:ignore ?></span>
							<h3><?php esc_html_e( 'Thank you — message received.', 'luwipress-onyx' ); ?></h3>
							<p><?php esc_html_e( 'A senior advisor will reply within one business day, discreetly and without a sales script.', 'luwipress-onyx' ); ?></p>
						</div>
					<?php elseif ( $onyx_has_form ) : ?>
						<div class="cform"><?php
							while ( have_posts() ) { the_post(); the_content(); }
						?></div>
					<?php else : ?>
						<form class="cform" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="onyx_contact">
							<?php wp_nonce_field( 'onyx_contact', 'onyx_contact_nonce' ); ?>
							<div style="position:absolute;left:-9999px" aria-hidden="true"><label>Website<input type="text" name="onyx_website" tabindex="-1" autocomplete="off"></label></div>
							<?php if ( 'error' === $onyx_status ) : ?>
								<p class="cform-note" style="color:var(--gold)"><?php esc_html_e( 'Please check your name and a valid email, then try again.', 'luwipress-onyx' ); ?></p>
							<?php endif; ?>
							<div class="row2">
								<div class="field"><label><?php esc_html_e( 'Full name', 'luwipress-onyx' ); ?></label><input type="text" name="onyx_name" required placeholder="<?php esc_attr_e( 'Your name', 'luwipress-onyx' ); ?>"></div>
								<div class="field"><label><?php esc_html_e( 'Phone', 'luwipress-onyx' ); ?></label><input type="tel" name="onyx_phone" placeholder="+971 …"></div>
							</div>
							<div class="field"><label><?php esc_html_e( 'Email', 'luwipress-onyx' ); ?></label><input type="email" name="onyx_email" required placeholder="you@email.com"></div>
							<div class="field"><label><?php esc_html_e( "I'm interested in", 'luwipress-onyx' ); ?></label>
								<select name="onyx_interest">
									<option value=""><?php esc_html_e( 'Select one…', 'luwipress-onyx' ); ?></option>
									<?php foreach ( $onyx_interests as $opt ) : ?>
										<option value="<?php echo esc_attr( $opt ); ?>"><?php echo esc_html( $opt ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="field"><label><?php esc_html_e( 'Message', 'luwipress-onyx' ); ?></label><?php $onyx_prefill = ''; if ( ! empty( $_GET['project'] ) ) { $onyx_proj = sanitize_text_field( html_entity_decode( wp_unslash( $_GET['project'] ), ENT_QUOTES, 'UTF-8' ) ); if ( '' !== $onyx_proj ) { /* translators: %s: project name */ $onyx_prefill = sprintf( __( "I'm interested in %s. Please send me the price, payment plan and availability.", 'luwipress-onyx' ), $onyx_proj ); } } ?><textarea name="onyx_message" placeholder="<?php esc_attr_e( "Tell us what you're looking for — budget, area, timing. The more you share, the better we can help.", 'luwipress-onyx' ); ?>"><?php echo esc_textarea( $onyx_prefill ); ?></textarea></div>
							<button type="submit" class="btn btn-gold"><?php esc_html_e( 'Send message', 'luwipress-onyx' ); ?> <span class="arr"><?php echo onyx_icon( 'arrow', 16 ); // phpcs:ignore ?></span></button>
							<p class="cform-note"><?php esc_html_e( 'By sending this, you agree to be contacted by ArshaHomes Real Estate L.L.C. We never share your details. Most enquiries are answered the same day.', 'luwipress-onyx' ); ?></p>
						</form>
					<?php endif; ?>
				</div>
				<div class="reveal">
					<div class="cinfo">
						<?php foreach ( $onyx_contacts as $c ) : ?>
							<div class="ci">
								<span class="ci-ic"><?php echo onyx_icon( $c[0], 20 ); // phpcs:ignore ?></span>
								<div>
									<div class="ci-l"><?php echo esc_html( $c[1] ); ?></div>
									<div class="ci-v"><?php echo wp_kses_post( $c[2] ); ?></div>
									<div class="ci-sub"><?php echo esc_html( $c[3] ); ?></div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<div class="cmap reveal">
				<div class="cmap-pin"><span class="ic"><?php echo onyx_icon( 'pin', 16 ); // phpcs:ignore ?></span><span><?php esc_html_e( 'Blue Bay Tower · Business Bay', 'luwipress-onyx' ); ?></span></div>
				<iframe title="<?php esc_attr_e( 'ArshaHomes location', 'luwipress-onyx' ); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="<?php echo esc_url( 'https://www.google.com/maps?q=' . rawurlencode( $onyx_map_q ) . '&output=embed' ); ?>"></iframe>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
