<?php
/**
 * Elementor widget — Tour Booking Box.
 *
 * Renders the sticky `.book-box` (date picker + pax stepper + add-ons + live
 * total + Reserve) for the current tour product, posting the booking into the
 * WC cart via the inc/booking/ pipeline. Place on a Single Product Elementor
 * template. Markup + behaviour come from inc/booking/cart.php + assets/js/booking.js.
 *
 * @package luwipress-amber
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }
if ( ! class_exists( 'WooCommerce' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class LuwiPress_Amber_Widget_Tour_Booking_Box extends Widget_Base {

	public function get_name() { return 'lwp-tour-booking-box'; }
	public function get_title() { return __( 'Tour Booking Box', 'luwipress-amber' ); }
	public function get_icon() { return 'eicon-cart-medium'; }
	public function get_categories() { return [ 'luwipress-amber' ]; }
	public function get_keywords() { return [ 'tour', 'booking', 'date', 'calendar', 'reserve', 'travel' ]; }
	public function get_style_depends() { return [ 'luwipress-amber-widgets' ]; }
	public function get_script_depends() { return [ 'luwipress-amber-booking' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'content', [ 'label' => __( 'Booking box', 'luwipress-amber' ) ] );

		$this->add_control( 'product_id', [
			'label'       => __( 'Tour product', 'luwipress-amber' ),
			'description' => __( 'Leave empty to use the current product (on a Single Product template).', 'luwipress-amber' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
		] );

		$this->add_control( 'reserve_label', [
			'label'   => __( 'Reserve button label', 'luwipress-amber' ),
			'type'    => Controls_Manager::TEXT,
			'default' => __( 'Reserve Your Spot', 'luwipress-amber' ),
		] );

		$this->add_control( 'show_whatsapp', [
			'label'        => __( 'Show "Ask on WhatsApp"', 'luwipress-amber' ),
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
		] );

		$this->add_control( 'whatsapp_url', [
			'label'       => __( 'WhatsApp URL', 'luwipress-amber' ),
			'type'        => Controls_Manager::URL,
			'placeholder' => 'https://wa.me/9715XXXXXXXX',
			'condition'   => [ 'show_whatsapp' => 'yes' ],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'style', [ 'label' => __( 'Style', 'luwipress-amber' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'cta_color', [
			'label'     => __( 'Reserve button color', 'luwipress-amber' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .book-box .btn--coral' => 'background: {{VALUE}};' ],
		] );
		$this->add_control( 'total_color', [
			'label'     => __( 'Total amount color', 'luwipress-amber' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .book-box .bb-total .t-amt' => 'color: {{VALUE}};' ],
		] );
		$this->end_controls_section();
	}

	protected function render() {
		$s   = $this->get_settings_for_display();
		$pid = ! empty( $s['product_id'] ) ? (int) $s['product_id'] : 0;

		if ( ! $pid ) {
			$post = get_post();
			if ( $post && 'product' === $post->post_type ) {
				$pid = (int) $post->ID;
			}
		}

		if ( ! $pid || ! function_exists( 'lwp_amber_is_tour' ) || ! lwp_amber_is_tour( $pid ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="book-box book-box--placeholder"><em>' . esc_html__( '[Tour Booking Box] Place this on a Single Product template for a product marked “Bookable tour”, or set a Tour product above.', 'luwipress-amber' ) . '</em></div>';
			}
			return;
		}

		$wa = '';
		if ( 'yes' === ( $s['show_whatsapp'] ?? '' ) && ! empty( $s['whatsapp_url']['url'] ) ) {
			$wa = $s['whatsapp_url']['url'];
		}

		echo lwp_amber_render_booking_box( $pid, [ // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within renderer.
			'form'          => true,
			'show_reserve'  => true,
			'reserve_label' => $s['reserve_label'] ?: __( 'Reserve Your Spot', 'luwipress-amber' ),
			'show_whatsapp' => 'yes' === ( $s['show_whatsapp'] ?? '' ),
			'whatsapp_url'  => $wa,
		] );
	}
}
