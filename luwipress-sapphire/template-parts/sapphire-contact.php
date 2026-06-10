<?php
/**
 * Shared contact band — form (real wp_mail handler) + advisor card.
 * Used by the home front page; the Contact page builds its own richer layout.
 *
 * @package luwipress-sapphire
 * @var array $args eyebrow, title
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$sapphire_eyebrow = isset( $args['eyebrow'] ) ? $args['eyebrow'] : __( 'Get in touch', 'luwipress-sapphire' );
$sapphire_title   = isset( $args['title'] ) ? $args['title'] : __( 'Talk to our team', 'luwipress-sapphire' );

$sapphire_status  = isset( $_GET['sapphire_contact'] ) ? sanitize_key( wp_unslash( $_GET['sapphire_contact'] ) ) : '';
$sapphire_phone   = trim( (string) get_theme_mod( 'luwipress_sapphire_topbar_phone', '' ) );
$sapphire_email   = trim( (string) get_theme_mod( 'luwipress_sapphire_topbar_email', 'hello@sapphire.dev' ) );
$sapphire_address = trim( (string) get_theme_mod( 'luwipress_sapphire_topbar_location', 'Remote-first — teams in 40+ countries' ) );
$sapphire_advisor = trim( (string) get_theme_mod( 'luwipress_sapphire_advisor_name', 'The Sapphire Team' ) );
$sapphire_advisor_role = trim( (string) get_theme_mod( 'luwipress_sapphire_advisor_role', 'Sales & support · we usually reply in minutes' ) );
?>
<section class="section" id="contact">
	<div class="wrap">
		<div class="sec-head">
			<div class="sh-title">
				<span class="reveal" style="display:block"><?php echo sapphire_eyebrow( $sapphire_eyebrow ); // phpcs:ignore ?></span>
				<h2 class="display-lg reveal"><?php echo esc_html( $sapphire_title ); ?></h2>
			</div>
		</div>
		<div class="contact-grid">
			<div class="reveal">
				<?php if ( 'sent' === $sapphire_status ) : ?>
					<div class="cform-done">
						<span class="ic"><?php echo sapphire_icon( 'check', 40 ); // phpcs:ignore ?></span>
						<h3><?php esc_html_e( 'Thanks — message received.', 'luwipress-sapphire' ); ?></h3>
						<p><?php esc_html_e( 'Our team will get back to you within one business day. Need an answer faster? Start a chat.', 'luwipress-sapphire' ); ?></p>
					</div>
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
						<div class="field"><label><?php esc_html_e( 'How can we help?', 'luwipress-sapphire' ); ?></label><textarea name="sapphire_message" placeholder="<?php esc_attr_e( 'Tell us about your team and what you are building.', 'luwipress-sapphire' ); ?>"></textarea></div>
						<button type="submit" class="btn btn-gold"><?php esc_html_e( 'Talk to sales', 'luwipress-sapphire' ); ?> <span class="arr"><?php echo sapphire_icon( 'arrow', 16 ); // phpcs:ignore ?></span></button>
					</form>
				<?php endif; ?>
			</div>
			<div class="agent reveal">
				<div class="ag-photo" style="display:grid;place-items:center;background:linear-gradient(160deg,var(--surface),var(--sapphire-850));color:var(--accent-soft)"><?php echo sapphire_icon( 'chat', 56 ); // phpcs:ignore ?></div>
				<span class="smallcaps"><?php esc_html_e( "We're here to help", 'luwipress-sapphire' ); ?></span>
				<h3><?php echo esc_html( $sapphire_advisor ); ?></h3>
				<div class="ag-role"><?php echo esc_html( $sapphire_advisor_role ); ?></div>
				<div class="ag-rows">
					<?php if ( $sapphire_phone !== '' ) : ?>
						<div class="ag-row"><span class="ic"><?php echo sapphire_icon( 'phone', 16 ); // phpcs:ignore ?></span><a href="<?php echo esc_url( 'tel:' . preg_replace( '/\s+/', '', $sapphire_phone ) ); ?>"><?php echo esc_html( $sapphire_phone ); ?></a></div>
					<?php endif; ?>
					<?php if ( $sapphire_email !== '' ) : ?>
						<div class="ag-row"><span class="ic"><?php echo sapphire_icon( 'mail', 16 ); // phpcs:ignore ?></span><a href="<?php echo esc_url( 'mailto:' . $sapphire_email ); ?>"><?php echo esc_html( $sapphire_email ); ?></a></div>
					<?php endif; ?>
					<?php if ( $sapphire_address !== '' ) : ?>
						<div class="ag-row"><span class="ic"><?php echo sapphire_icon( 'pin', 16 ); // phpcs:ignore ?></span><?php echo esc_html( $sapphire_address ); ?></div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</section>
