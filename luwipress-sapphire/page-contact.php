<?php
/**
 * Template Name: Sapphire — Contact
 *
 * Contact page (sapphire-page-contact.jsx): form (real wp_mail handler, or the
 * operator's Contact Form 7 / WPForms shortcode when present) + contact
 * panel + embedded map. Yields to Elementor when the page is built with it.
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

$sapphire_status  = isset( $_GET['sapphire_contact'] ) ? sanitize_key( wp_unslash( $_GET['sapphire_contact'] ) ) : '';
$sapphire_phone   = trim( (string) get_theme_mod( 'luwipress_sapphire_topbar_phone', '' ) );
$sapphire_email   = trim( (string) get_theme_mod( 'luwipress_sapphire_topbar_email', 'hello@sapphire.dev' ) );
$sapphire_address = trim( (string) get_theme_mod( 'luwipress_sapphire_topbar_location', 'Remote-first — teams in 40+ countries' ) );
$sapphire_map_q   = trim( (string) get_theme_mod( 'luwipress_sapphire_map_query', 'San Francisco, CA' ) );

// If the operator dropped a form-plugin shortcode into the page content,
// render that (styled by .cform) instead of the built-in handler.
$sapphire_content   = $sapphire_pid ? (string) get_post_field( 'post_content', $sapphire_pid ) : '';
$sapphire_has_form  = has_shortcode( $sapphire_content, 'contact-form-7' ) || has_shortcode( $sapphire_content, 'wpforms' ) || has_shortcode( $sapphire_content, 'gravityform' );

$sapphire_interests = array(
	__( 'Sales enquiry', 'luwipress-sapphire' ),
	__( 'Technical support', 'luwipress-sapphire' ),
	__( 'Billing & account', 'luwipress-sapphire' ),
	__( 'Partnerships', 'luwipress-sapphire' ),
	__( 'Press & media', 'luwipress-sapphire' ),
	__( 'Something else', 'luwipress-sapphire' ),
);

$sapphire_contacts = array(
	array( 'chat', __( 'Live chat', 'luwipress-sapphire' ), esc_html__( 'Start a chat in-app', 'luwipress-sapphire' ), __( 'The fastest way to reach us', 'luwipress-sapphire' ) ),
	array( 'mail', __( 'Email', 'luwipress-sapphire' ), '<a href="' . esc_url( 'mailto:' . $sapphire_email ) . '">' . esc_html( $sapphire_email ) . '</a>', __( 'We reply within one business day', 'luwipress-sapphire' ) ),
	array( 'globe', __( 'Where', 'luwipress-sapphire' ), esc_html( $sapphire_address ), __( 'Sapphire, Inc.', 'luwipress-sapphire' ) ),
	array( 'clock', __( 'Hours', 'luwipress-sapphire' ), esc_html__( 'Monday — Friday', 'luwipress-sapphire' ), __( '9:00 — 18:00 · all time zones covered', 'luwipress-sapphire' ) ),
);
?>

<main>
	<section class="phead">
		<div class="wrap">
			<div class="crumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'luwipress-sapphire' ); ?></a><span class="sep">/</span><span><?php esc_html_e( 'Contact', 'luwipress-sapphire' ); ?></span></div>
			<span class="smallcaps" style="color:var(--accent)"><?php esc_html_e( "We'd love to hear from you", 'luwipress-sapphire' ); ?></span>
			<h1 class="display-xl" style="margin-top:14px;font-size:clamp(44px,6vw,84px)"><?php esc_html_e( 'Get in touch', 'luwipress-sapphire' ); ?></h1>
			<p class="phead-sub"><?php esc_html_e( 'Questions about plans, a demo, security review or a custom rollout — tell us what you need and the right person on our team will reply.', 'luwipress-sapphire' ); ?></p>
		</div>
	</section>

	<section class="section" style="padding-top:clamp(44px,5vw,72px)">
		<div class="wrap">
			<div class="contact-grid">
				<div class="reveal">
					<?php if ( 'sent' === $sapphire_status ) : ?>
						<div class="cform-done">
							<span class="ic"><?php echo sapphire_icon( 'check', 40 ); // phpcs:ignore ?></span>
							<h3><?php esc_html_e( 'Thanks — message received.', 'luwipress-sapphire' ); ?></h3>
							<p><?php esc_html_e( 'Our team will get back to you within one business day. Need an answer faster? Start a chat.', 'luwipress-sapphire' ); ?></p>
						</div>
					<?php elseif ( $sapphire_has_form ) : ?>
						<div class="cform"><?php
							while ( have_posts() ) { the_post(); the_content(); }
						?></div>
					<?php else : ?>
						<form class="cform" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="sapphire_contact">
							<?php wp_nonce_field( 'sapphire_contact', 'sapphire_contact_nonce' ); ?>
							<div style="position:absolute;left:-9999px" aria-hidden="true"><label>Website<input type="text" name="sapphire_website" tabindex="-1" autocomplete="off"></label></div>
							<?php if ( 'error' === $sapphire_status ) : ?>
								<p class="cform-note" style="color:var(--accent)"><?php esc_html_e( 'Please check your name and a valid email, then try again.', 'luwipress-sapphire' ); ?></p>
							<?php endif; ?>
							<div class="row2">
								<div class="field"><label><?php esc_html_e( 'Full name', 'luwipress-sapphire' ); ?></label><input type="text" name="sapphire_name" required placeholder="<?php esc_attr_e( 'Your name', 'luwipress-sapphire' ); ?>"></div>
								<div class="field"><label><?php esc_html_e( 'Company', 'luwipress-sapphire' ); ?></label><input type="text" name="sapphire_company" placeholder="<?php esc_attr_e( 'Your company', 'luwipress-sapphire' ); ?>"></div>
							</div>
							<div class="field"><label><?php esc_html_e( 'Work email', 'luwipress-sapphire' ); ?></label><input type="email" name="sapphire_email" required placeholder="you@company.com"></div>
							<div class="field"><label><?php esc_html_e( "I'm interested in", 'luwipress-sapphire' ); ?></label>
								<select name="sapphire_interest">
									<option value=""><?php esc_html_e( 'Select one…', 'luwipress-sapphire' ); ?></option>
									<?php foreach ( $sapphire_interests as $opt ) : ?>
										<option value="<?php echo esc_attr( $opt ); ?>"><?php echo esc_html( $opt ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="field"><label><?php esc_html_e( 'Message', 'luwipress-sapphire' ); ?></label><textarea name="sapphire_message" placeholder="<?php esc_attr_e( 'Tell us about your team, your stack and what you want to ship. The more you share, the better we can help.', 'luwipress-sapphire' ); ?>"></textarea></div>
							<button type="submit" class="btn btn-gold"><?php esc_html_e( 'Send message', 'luwipress-sapphire' ); ?> <span class="arr"><?php echo sapphire_icon( 'arrow', 16 ); // phpcs:ignore ?></span></button>
							<p class="cform-note"><?php esc_html_e( 'By sending this, you agree to be contacted by Sapphire, Inc. We never share your details, and most enquiries are answered the same day.', 'luwipress-sapphire' ); ?></p>
						</form>
					<?php endif; ?>
				</div>
				<div class="reveal">
					<div class="cinfo">
						<?php foreach ( $sapphire_contacts as $c ) : ?>
							<div class="ci">
								<span class="ci-ic"><?php echo sapphire_icon( $c[0], 20 ); // phpcs:ignore ?></span>
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
				<div class="cmap-pin"><span class="ic"><?php echo sapphire_icon( 'pin', 16 ); // phpcs:ignore ?></span><span><?php esc_html_e( 'Sapphire HQ · San Francisco', 'luwipress-sapphire' ); ?></span></div>
				<iframe title="<?php esc_attr_e( 'Sapphire HQ location', 'luwipress-sapphire' ); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="<?php echo esc_url( 'https://www.google.com/maps?q=' . rawurlencode( $sapphire_map_q ) . '&output=embed' ); ?>"></iframe>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
