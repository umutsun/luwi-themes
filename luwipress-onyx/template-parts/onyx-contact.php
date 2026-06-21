<?php
/**
 * Shared contact band — form (real wp_mail handler) + advisor card.
 * Used by the home front page; the Contact page builds its own richer layout.
 *
 * @package luwipress-onyx
 * @var array $args eyebrow, title
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$onyx_eyebrow = isset( $args['eyebrow'] ) ? $args['eyebrow'] : __( 'Free Quote', 'luwipress-onyx' );
$onyx_title   = isset( $args['title'] ) ? $args['title'] : __( 'Begin a private conversation', 'luwipress-onyx' );

$onyx_status  = isset( $_GET['onyx_contact'] ) ? sanitize_key( wp_unslash( $_GET['onyx_contact'] ) ) : '';
$onyx_phone   = trim( (string) get_theme_mod( 'luwipress_onyx_topbar_phone', '056 776 1946' ) );
$onyx_email   = trim( (string) get_theme_mod( 'luwipress_onyx_topbar_email', 'sales@arshahomes.com' ) );
$onyx_address = trim( (string) get_theme_mod( 'luwipress_onyx_topbar_location', 'Blue Bay Tower, Office 608 — Business Bay, Dubai' ) );
$onyx_advisor = trim( (string) get_theme_mod( 'luwipress_onyx_advisor_name', 'Ayhan Sahin' ) );
$onyx_advisor_role = trim( (string) get_theme_mod( 'luwipress_onyx_advisor_role', 'Serves in Dubai · Speaks EN / TR / AR' ) );
$onyx_ayhan   = get_template_directory_uri() . '/assets/ayhan.jpg';
?>
<section class="section" id="contact">
	<div class="wrap">
		<div class="sec-head">
			<div class="sh-title">
				<span class="reveal" style="display:block"><?php echo onyx_eyebrow( $onyx_eyebrow ); // phpcs:ignore ?></span>
				<h2 class="display-lg reveal"><?php echo esc_html( $onyx_title ); ?></h2>
			</div>
		</div>
		<div class="contact-grid">
			<div class="reveal">
				<?php if ( 'sent' === $onyx_status ) : ?>
					<div class="cform-done">
						<span class="ic"><?php echo onyx_icon( 'check', 40 ); // phpcs:ignore ?></span>
						<h3><?php esc_html_e( 'Thank you — message received.', 'luwipress-onyx' ); ?></h3>
						<p><?php esc_html_e( 'A senior advisor will reply within one business day, discreetly and without a sales script.', 'luwipress-onyx' ); ?></p>
					</div>
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
						<?php
						// Pre-fill the message when arriving from a project "Register interest" CTA.
						$onyx_prefill = '';
						if ( ! empty( $_GET['project'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
							$onyx_proj = sanitize_text_field( html_entity_decode( wp_unslash( $_GET['project'] ), ENT_QUOTES, 'UTF-8' ) ); // phpcs:ignore
							if ( '' !== $onyx_proj ) {
								/* translators: %s: project name */
								$onyx_prefill = sprintf( __( "I'm interested in %s. Please send me the price, payment plan and availability.", 'luwipress-onyx' ), $onyx_proj );
							}
						}
						?>
						<div class="field"><label><?php esc_html_e( 'How can we help?', 'luwipress-onyx' ); ?></label><textarea name="onyx_message" placeholder="<?php esc_attr_e( "Tell us what you're looking for — budget, area, timing.", 'luwipress-onyx' ); ?>"><?php echo esc_textarea( $onyx_prefill ); ?></textarea></div>
						<button type="submit" class="btn btn-gold"><?php esc_html_e( 'Request a callback', 'luwipress-onyx' ); ?> <span class="arr"><?php echo onyx_icon( 'arrow', 16 ); // phpcs:ignore ?></span></button>
					</form>
				<?php endif; ?>
			</div>
			<div class="agent reveal">
				<?php echo onyx_ph( array( 'glyph' => 'portrait', 'class' => 'ag-photo', 'img' => $onyx_ayhan, 'alt' => $onyx_advisor ) ); // phpcs:ignore ?>
				<span class="smallcaps"><?php esc_html_e( 'Your advisor', 'luwipress-onyx' ); ?></span>
				<h3><?php echo esc_html( $onyx_advisor ); ?></h3>
				<div class="ag-role"><?php echo esc_html( $onyx_advisor_role ); ?></div>
				<div class="ag-rows">
					<?php if ( $onyx_phone !== '' ) : ?>
						<div class="ag-row"><span class="ic"><?php echo onyx_icon( 'phone', 16 ); // phpcs:ignore ?></span><a href="<?php echo esc_url( 'tel:' . preg_replace( '/\s+/', '', $onyx_phone ) ); ?>"><?php echo esc_html( $onyx_phone ); ?></a></div>
					<?php endif; ?>
					<?php if ( $onyx_email !== '' ) : ?>
						<div class="ag-row"><span class="ic"><?php echo onyx_icon( 'mail', 16 ); // phpcs:ignore ?></span><a href="<?php echo esc_url( 'mailto:' . $onyx_email ); ?>"><?php echo esc_html( $onyx_email ); ?></a></div>
					<?php endif; ?>
					<?php if ( $onyx_address !== '' ) : ?>
						<div class="ag-row"><span class="ic"><?php echo onyx_icon( 'pin', 16 ); // phpcs:ignore ?></span><?php echo esc_html( $onyx_address ); ?></div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</section>
