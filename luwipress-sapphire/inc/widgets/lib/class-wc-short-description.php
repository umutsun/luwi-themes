<?php
/**
 * Widget: WooCommerce Product Short Description (dynamic).
 *
 * Pro-free replacement for Elementor Pro's `woocommerce-product-short-description`
 * widget. Renders the product short description through the standard WC filter
 * (woocommerce_short_description) so shortcodes/oEmbed work.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }
if ( ! class_exists( 'WooCommerce' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class LuwiPress_Sapphire_Widget_WC_Short_Description extends Widget_Base {

	public function get_name()        { return 'lwp-wc-short-desc'; }
	public function get_title()       { return __( 'WC Product Short Description', 'luwipress-sapphire' ); }
	public function get_icon()        { return 'eicon-product-description'; }
	public function get_categories()  { return [ 'luwipress-sapphire' ]; }
	public function get_keywords()    { return [ 'woocommerce', 'product', 'short', 'description', 'excerpt' ]; }
	public function get_style_depends() { return [ 'luwipress-sapphire-widgets' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Description', 'luwipress-sapphire' ) ] );

		$this->add_control( 'note', [
			'type' => Controls_Manager::RAW_HTML,
			'raw'  => '<em style="color:#8b7f6a;">' . esc_html__( 'Renders the product short description (excerpt). Edit on the product page itself.', 'luwipress-sapphire' ) . '</em>',
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_style', [ 'label' => __( 'Style', 'luwipress-sapphire' ), 'tab' => Controls_Manager::TAB_STYLE ] );

		$this->add_control( 'color', [
			'label' => __( 'Color', 'luwipress-sapphire' ), 'type' => Controls_Manager::COLOR, 'default' => '#3a342c',
			'selectors' => [ '{{WRAPPER}} .lwp-wc-short-desc' => 'color: {{VALUE}};' ],
		] );

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name' => 'typography', 'selector' => '{{WRAPPER}} .lwp-wc-short-desc',
		] );

		$this->add_responsive_control( 'max_width', [
			'label' => __( 'Max width', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::SLIDER,
			'size_units' => [ 'px', '%' ],
			'range' => [ 'px' => [ 'min' => 200, 'max' => 1000 ] ],
			'selectors' => [ '{{WRAPPER}} .lwp-wc-short-desc' => 'max-width: {{SIZE}}{{UNIT}};' ],
		] );

		$this->end_controls_section();
	}

	protected function render() {
		$product = $this->get_current_product();
		if ( ! $product ) {
			echo '<div class="lwp-wc-short-desc"><p>' . esc_html__( '[Product short description goes here.]', 'luwipress-sapphire' ) . '</p></div>';
			return;
		}

		$short = $product->get_short_description();
		if ( ! $short ) { return; }

		$html = apply_filters( 'woocommerce_short_description', $short );

		echo '<div class="lwp-wc-short-desc">' . $html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- through WC filter chain.
	}

	private function get_current_product() {
		global $product;
		if ( $product instanceof \WC_Product ) { return $product; }
		if ( function_exists( 'wc_get_product' ) ) {
			$p = wc_get_product( get_post() );
			if ( $p instanceof \WC_Product ) { return $p; }
		}
		return null;
	}
}
