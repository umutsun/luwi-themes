<?php
/**
 * Widget: WooCommerce Product Price (dynamic).
 *
 * Pro-free replacement for Elementor Pro's `woocommerce-product-price` widget.
 * Renders the product price via $product->get_price_html() which handles
 * regular/sale/variation/grouped price formatting in one call.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }
if ( ! class_exists( 'WooCommerce' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class LuwiPress_Sapphire_Widget_WC_Price extends Widget_Base {

	public function get_name()        { return 'lwp-wc-price'; }
	public function get_title()       { return __( 'WC Product Price', 'luwipress-sapphire' ); }
	public function get_icon()        { return 'eicon-product-price'; }
	public function get_categories()  { return [ 'luwipress-sapphire' ]; }
	public function get_keywords()    { return [ 'woocommerce', 'product', 'price', 'sale' ]; }
	public function get_style_depends() { return [ 'luwipress-sapphire-widgets' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Price', 'luwipress-sapphire' ) ] );

		$this->add_responsive_control( 'align', [
			'label'   => __( 'Alignment', 'luwipress-sapphire' ),
			'type'    => Controls_Manager::CHOOSE,
			'options' => [
				'left'   => [ 'title' => 'Left',   'icon' => 'eicon-text-align-left' ],
				'center' => [ 'title' => 'Center', 'icon' => 'eicon-text-align-center' ],
				'right'  => [ 'title' => 'Right',  'icon' => 'eicon-text-align-right' ],
			],
			'selectors' => [ '{{WRAPPER}} .lwp-wc-price' => 'text-align: {{VALUE}};' ],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_style', [ 'label' => __( 'Style', 'luwipress-sapphire' ), 'tab' => Controls_Manager::TAB_STYLE ] );

		$this->add_control( 'price_color', [
			'label' => __( 'Price color', 'luwipress-sapphire' ), 'type' => Controls_Manager::COLOR, 'default' => '#1A1612',
			'selectors' => [ '{{WRAPPER}} .lwp-wc-price' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'sale_color', [
			'label' => __( 'Sale price color', 'luwipress-sapphire' ), 'type' => Controls_Manager::COLOR, 'default' => '#d83131',
			'selectors' => [ '{{WRAPPER}} .lwp-wc-price ins .amount, {{WRAPPER}} .lwp-wc-price ins' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'strike_color', [
			'label' => __( 'Strikethrough color', 'luwipress-sapphire' ), 'type' => Controls_Manager::COLOR, 'default' => '#8b7f6a',
			'selectors' => [ '{{WRAPPER}} .lwp-wc-price del .amount, {{WRAPPER}} .lwp-wc-price del' => 'color: {{VALUE}};' ],
		] );

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name' => 'typography', 'selector' => '{{WRAPPER}} .lwp-wc-price',
		] );

		$this->end_controls_section();
	}

	protected function render() {
		$product = $this->get_current_product();
		if ( ! $product ) {
			echo '<div class="lwp-wc-price"><span class="amount">$0.00</span></div>';
			return;
		}

		$html = $product->get_price_html();
		if ( ! $html ) { return; }

		echo '<div class="lwp-wc-price">' . $html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC core escapes via wc_price().
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
