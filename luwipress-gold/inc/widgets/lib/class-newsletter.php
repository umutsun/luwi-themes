<?php
/**
 * Widget: Newsletter Signup.
 *
 * Email capture form. Detects FluentCRM / Mailchimp for WC / Klaviyo via
 * the LuwiPress Plugin Detector and posts to the right endpoint;
 * otherwise stores the lead via a generic `luwipress_subscribe` REST
 * call that the core plugin's customer-chat / CRM bridge handles.
 *
 * Submission flow is AJAX so the page never redirects. GDPR checkbox
 * is opt-in but recommended for EU traffic.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class LuwiPress_Gold_Widget_Newsletter extends Widget_Base {

	public function get_name()        { return 'lwp-newsletter'; }
	public function get_title()       { return __( 'Newsletter Signup', 'luwipress-gold' ); }
	public function get_icon()        { return 'eicon-email-field'; }
	public function get_categories()  { return [ 'luwipress-gold' ]; }
	public function get_keywords()    { return [ 'newsletter', 'email', 'signup', 'subscribe', 'crm', 'mailchimp' ]; }
	public function get_style_depends() { return [ 'luwipress-gold-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_copy', [ 'label' => __( 'Copy', 'luwipress-gold' ) ] );

		$this->add_control( 'eyebrow', [ 'label' => __( 'Eyebrow', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Stay in the loop', 'luwipress-gold' ) ] );
		$this->add_control( 'heading', [ 'label' => __( 'Heading', 'luwipress-gold' ), 'type' => Controls_Manager::TEXTAREA, 'rows' => 2, 'default' => __( "New arrivals + offers,\ndelivered to your inbox.", 'luwipress-gold' ) ] );
		$this->add_control( 'lead',    [ 'label' => __( 'Lead', 'luwipress-gold' ),    'type' => Controls_Manager::TEXTAREA, 'rows' => 3, 'default' => __( 'A short letter, once a month. No spam, no resellers.', 'luwipress-gold' ) ] );

		$this->add_control( 'placeholder', [ 'label' => __( 'Email placeholder', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'your@email.com', 'luwipress-gold' ) ] );
		$this->add_control( 'btn_label',   [ 'label' => __( 'Button label', 'luwipress-gold' ),     'type' => Controls_Manager::TEXT, 'default' => __( 'Subscribe', 'luwipress-gold' ) ] );

		$this->add_control( 'gdpr_show', [
			'label'        => __( 'Show GDPR consent checkbox', 'luwipress-gold' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );
		$this->add_control( 'gdpr_text', [
			'label'   => __( 'GDPR consent text', 'luwipress-gold' ),
			'type'    => Controls_Manager::TEXTAREA,
			'rows'    => 2,
			'default' => __( 'I agree to receive your newsletter. I can unsubscribe at any time.', 'luwipress-gold' ),
			'condition' => [ 'gdpr_show' => 'yes' ],
		] );

		$this->add_control( 'success_msg', [
			'label'   => __( 'Success message', 'luwipress-gold' ),
			'type'    => Controls_Manager::TEXTAREA,
			'rows'    => 2,
			'default' => __( 'Thanks for subscribing. Check your inbox to confirm.', 'luwipress-gold' ),
		] );
		$this->add_control( 'error_msg', [
			'label'   => __( 'Error message', 'luwipress-gold' ),
			'type'    => Controls_Manager::TEXT,
			'default' => __( "Couldn't subscribe. Please try again.", 'luwipress-gold' ),
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_style', [ 'label' => __( 'Style', 'luwipress-gold' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'variant', [
			'label'   => __( 'Variant', 'luwipress-gold' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'light',
			'options' => [ 'light' => __( 'Light', 'luwipress-gold' ), 'dark' => __( 'Dark', 'luwipress-gold' ) ],
		] );
		$this->end_controls_section();
	}

	/**
	 * Surface the active CRM detector result so the form can hint to JS
	 * which endpoint to use. Defaults to LuwiPress core subscribe route.
	 */
	protected function detect_crm() {
		if ( function_exists( 'lwp_gold_lp_detector' ) ) {
			$d = lwp_gold_lp_detector();
			if ( $d && method_exists( $d, 'detect_crm' ) ) {
				$out = $d->detect_crm();
				return is_array( $out ) ? ( $out['active'] ?? 'core' ) : 'core';
			}
		}
		return 'core';
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$eyebrow = trim( (string) ( $s['eyebrow'] ?? '' ) );
		$heading = trim( (string) ( $s['heading'] ?? '' ) );
		$lead    = trim( (string) ( $s['lead']    ?? '' ) );
		$ph      = (string) ( $s['placeholder'] ?? '' );
		$btn     = (string) ( $s['btn_label']  ?? '' );
		$gdpr    = ( $s['gdpr_show'] ?? 'yes' ) === 'yes';
		$gdpr_t  = (string) ( $s['gdpr_text']   ?? '' );
		$ok      = (string) ( $s['success_msg'] ?? '' );
		$err     = (string) ( $s['error_msg']   ?? '' );
		$variant = ( $s['variant'] ?? 'light' ) === 'dark' ? 'dark' : 'light';
		$crm     = $this->detect_crm();
		$nonce   = wp_create_nonce( 'wp_rest' );
		// Theme-side endpoint always exists (registered in luwipress-bridge.php).
		// Plays nicely with CRM detection — fires `luwipress_gold_newsletter_subscribed`
		// so a future CRM bridge can hook in.
		$rest    = esc_url_raw( rest_url( 'luwipress-gold/v1/subscribe' ) );
		?>
		<div class="lwp-nl lwp-nl--<?php echo esc_attr( $variant ); ?>">
			<div class="lwp-nl__copy">
				<?php if ( $eyebrow ) : ?><span class="lwp-nl__eyebrow">— <?php echo esc_html( $eyebrow ); ?></span><?php endif; ?>
				<?php if ( $heading ) : ?><h2 class="lwp-nl__title"><?php echo nl2br( esc_html( $heading ) ); ?></h2><?php endif; ?>
				<?php if ( $lead ) : ?><p class="lwp-nl__lead"><?php echo esc_html( $lead ); ?></p><?php endif; ?>
			</div>
			<form class="lwp-nl__form"
				data-lwp-newsletter
				data-rest="<?php echo esc_attr( $rest ); ?>"
				data-nonce="<?php echo esc_attr( $nonce ); ?>"
				data-crm="<?php echo esc_attr( $crm ); ?>"
				data-success="<?php echo esc_attr( $ok ); ?>"
				data-error="<?php echo esc_attr( $err ); ?>"
				novalidate>
				<label class="lwp-nl__field">
					<span class="screen-reader-text"><?php echo esc_html( $ph ); ?></span>
					<input type="email" name="email" required autocomplete="email"
						placeholder="<?php echo esc_attr( $ph ); ?>" />
					<button type="submit"><?php echo esc_html( $btn ); ?></button>
				</label>
				<?php if ( $gdpr && $gdpr_t ) : ?>
					<label class="lwp-nl__gdpr">
						<input type="checkbox" name="consent" required />
						<span><?php echo esc_html( $gdpr_t ); ?></span>
					</label>
				<?php endif; ?>
				<div class="lwp-nl__msg" role="status" aria-live="polite"></div>
				<input type="hidden" name="source" value="<?php echo esc_attr( 'newsletter:' . $crm ); ?>" />
			</form>
		</div>
		<?php
	}
}
