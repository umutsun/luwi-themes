<?php
/**
 * Widget: Contact CTA.
 *
 * Auto-pulls WhatsApp + Telegram + email + escalation channel from the
 * LuwiPress Customer Chat settings (single source of truth). Operator
 * updates the WhatsApp number once in LuwiPress → Customer Chat → it
 * propagates to the chat launcher button AND this widget automatically.
 *
 * Renders a dark ink card with stacked CTA links: WA pill (brand green) +
 * Telegram + Email + optional "Browse catalog" button + atelier address.
 * Operator can override per-widget heading / lead copy / button label.
 *
 * @since 1.10.3
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class LuwiPress_Sapphire_Widget_Contact_CTA extends Widget_Base {

	public function get_name()        { return 'lwp-contact-cta'; }
	public function get_title()       { return __( 'Contact CTA Card', 'luwipress-sapphire' ); }
	public function get_icon()        { return 'eicon-call-to-action'; }
	public function get_categories()  { return [ 'luwipress-sapphire' ]; }
	public function get_keywords()    { return [ 'contact', 'whatsapp', 'telegram', 'email', 'cta', 'chat' ]; }
	public function get_style_depends() { return [ 'luwipress-sapphire-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_content', [ 'label' => __( 'Copy', 'luwipress-sapphire' ) ] );

		$this->add_control(
			'info',
			[
				'type' => Controls_Manager::RAW_HTML,
				'raw'  => '<em style="color:#8b7f6a;">' . esc_html__( 'WhatsApp number + Telegram username come from LuwiPress → Customer Chat. Update once there; this widget and the chat launcher both refresh.', 'luwipress-sapphire' ) . '</em>',
			]
		);

		$this->add_control( 'heading', [
			'label' => __( 'Heading', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::TEXT,
			'default' => __( 'Send us a note.', 'luwipress-sapphire' ),
		] );

		$this->add_control( 'lead', [
			'label' => __( 'Lead', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::TEXTAREA,
			'default' => __( 'We reply within 1 working day · usually faster. Same-day on WhatsApp.', 'luwipress-sapphire' ),
		] );

		$this->add_control( 'email', [
			'label' => __( 'Email address', 'luwipress-sapphire' ),
			'description' => __( 'Used in the email row. Leave empty to hide.', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::TEXT,
			'default' => '',
			'placeholder' => 'info@example.com',
		] );

		$this->add_control( 'address_line', [
			'label' => __( 'Address footnote (optional)', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::TEXT,
			'default' => '',
			'placeholder' => 'Via della Rocca 14 · 48013 Brisighella RA · Italy',
		] );

		$this->add_control( 'cta_label', [
			'label' => __( 'CTA button label', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::TEXT,
			'default' => __( 'Browse the catalog →', 'luwipress-sapphire' ),
		] );

		$this->add_control( 'cta_url', [
			'label' => __( 'CTA button URL', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::URL,
			'default' => [ 'url' => '/shop/', 'is_external' => '', 'nofollow' => '' ],
		] );

		$this->add_control( 'show_whatsapp', [
			'label' => __( 'Show WhatsApp', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes',
		] );
		$this->add_control( 'show_telegram', [
			'label' => __( 'Show Telegram', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes',
		] );
		$this->add_control( 'show_email', [
			'label' => __( 'Show Email', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes',
		] );
		$this->add_control( 'show_cta', [
			'label' => __( 'Show CTA button', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes',
		] );

		$this->end_controls_section();

		/* Style */
		$this->start_controls_section( 'section_style', [ 'label' => __( 'Style', 'luwipress-sapphire' ), 'tab' => Controls_Manager::TAB_STYLE ] );

		$this->add_control( 'card_bg', [
			'label' => __( 'Card background', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::COLOR,
			'default' => '#1A1612',
			'selectors' => [ '{{WRAPPER}} .lwp-cta-card' => 'background: {{VALUE}};' ],
		] );

		$this->add_control( 'heading_color', [
			'label' => __( 'Heading color', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::COLOR,
			'default' => '#ffffff',
			'selectors' => [ '{{WRAPPER}} .lwp-cta-card__heading' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'lead_color', [
			'label' => __( 'Lead color', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::COLOR,
			'default' => 'rgba(255,255,255,0.7)',
			'selectors' => [ '{{WRAPPER}} .lwp-cta-card__lead' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'cta_bg', [
			'label' => __( 'CTA button bg', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::COLOR,
			'default' => '#9A7B3A',
			'selectors' => [ '{{WRAPPER}} .lwp-cta-card__cta' => 'background: {{VALUE}};' ],
		] );

		$this->add_control( 'cta_color', [
			'label' => __( 'CTA button text', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::COLOR,
			'default' => '#1A1612',
			'selectors' => [ '{{WRAPPER}} .lwp-cta-card__cta' => 'color: {{VALUE}};' ],
		] );

		$this->add_responsive_control( 'card_padding', [
			'label' => __( 'Card padding', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px' ],
			'selectors' => [ '{{WRAPPER}} .lwp-cta-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->add_control( 'card_radius', [
			'label' => __( 'Border radius', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px' ],
			'selectors' => [ '{{WRAPPER}} .lwp-cta-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name' => 'heading_typography',
			'selector' => '{{WRAPPER}} .lwp-cta-card__heading',
		] );

		$this->end_controls_section();
	}

	/**
	 * Pull WhatsApp + Telegram from LuwiPress Customer Chat options. These
	 * are the same source the chat launcher button reads from, so a single
	 * settings change in LuwiPress → Customer Chat propagates everywhere.
	 */
	private function get_chat_contact() {
		return array(
			'whatsapp' => trim( (string) get_option( 'luwipress_chat_whatsapp_number', '' ) ),
			'telegram' => trim( (string) get_option( 'luwipress_chat_telegram_username', '' ) ),
			'channel'  => (string) get_option( 'luwipress_chat_escalation_channel', 'whatsapp' ),
		);
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$contact = $this->get_chat_contact();

		$wa = $contact['whatsapp'];
		$tg = $contact['telegram'];
		$em = trim( (string) ( $s['email'] ?? '' ) );

		$show_wa  = ( $s['show_whatsapp'] ?? 'yes' ) === 'yes' && $wa !== '';
		$show_tg  = ( $s['show_telegram'] ?? 'yes' ) === 'yes' && $tg !== '';
		$show_em  = ( $s['show_email']    ?? 'yes' ) === 'yes' && $em !== '';
		$show_cta = ( $s['show_cta']      ?? 'yes' ) === 'yes';

		$cta_url = $s['cta_url']['url'] ?? '';
		$cta_ext = ! empty( $s['cta_url']['is_external'] );
		?>
		<div class="lwp-cta-card">
			<?php if ( ! empty( $s['heading'] ) ) : ?>
				<h3 class="lwp-cta-card__heading"><?php echo esc_html( $s['heading'] ); ?></h3>
			<?php endif; ?>
			<?php if ( ! empty( $s['lead'] ) ) : ?>
				<p class="lwp-cta-card__lead"><?php echo esc_html( $s['lead'] ); ?></p>
			<?php endif; ?>

			<div class="lwp-cta-card__rows">
				<?php if ( $show_wa ) : ?>
					<a class="lwp-cta-card__row lwp-cta-card__row--whatsapp" href="<?php echo esc_url( 'https://wa.me/' . rawurlencode( ltrim( $wa, '+' ) ) ); ?>" target="_blank" rel="noopener">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.5 14.4l-2.2-.5c-.3-.1-.6 0-.8.2l-1 1c-1.6-.8-2.9-2.1-3.7-3.7l1-1c.2-.2.3-.5.2-.8l-.5-2.2c-.1-.4-.5-.7-.9-.6L7.7 7c-.4.1-.7.5-.7.9.3 3.6 3.5 6.8 7.1 7.1.4 0 .8-.3.9-.7l.5-1.9c0-.4-.2-.8-.6-.9z"/></svg>
						<span><strong>WhatsApp</strong> · +<?php echo esc_html( $wa ); ?></span>
					</a>
				<?php endif; ?>
				<?php if ( $show_tg ) : ?>
					<a class="lwp-cta-card__row lwp-cta-card__row--telegram" href="<?php echo esc_url( 'https://t.me/' . rawurlencode( ltrim( $tg, '@' ) ) ); ?>" target="_blank" rel="noopener">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9.5 14.5l-.4 3c.4 0 .6-.2.8-.4l2-1.9 4.1 3c.7.4 1.3.2 1.5-.7l2.8-13c.2-.9-.3-1.3-1.1-1L3 9c-.9.3-.9.8-.1 1L7 11.3l9.4-5.9c.4-.3.8-.1.5.2"/></svg>
						<span><strong>Telegram</strong> · @<?php echo esc_html( ltrim( $tg, '@' ) ); ?></span>
					</a>
				<?php endif; ?>
				<?php if ( $show_em ) : ?>
					<a class="lwp-cta-card__row lwp-cta-card__row--email" href="<?php echo esc_url( 'mailto:' . sanitize_email( $em ) ); ?>">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M3 8l9 6 9-6M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/></svg>
						<span><strong>Email</strong> · <?php echo esc_html( $em ); ?></span>
					</a>
				<?php endif; ?>
				<?php if ( $show_cta && $cta_url ) : ?>
					<a class="lwp-cta-card__cta" href="<?php echo esc_url( $cta_url ); ?>" <?php if ( $cta_ext ) : ?>target="_blank" rel="noopener"<?php endif; ?>>
						<?php echo esc_html( $s['cta_label'] ?? '' ); ?>
					</a>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $s['address_line'] ) ) : ?>
				<p class="lwp-cta-card__address"><?php echo esc_html( $s['address_line'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}
}
