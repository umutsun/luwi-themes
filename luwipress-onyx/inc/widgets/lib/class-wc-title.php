<?php
/**
 * Widget: WooCommerce Product Title (dynamic).
 *
 * Pro-free replacement for Elementor Pro's `woocommerce-product-title` widget.
 * Reads the current product via wc_get_product() + global $product fallback,
 * emits the product name in the chosen heading tag.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }
if ( ! class_exists( 'WooCommerce' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class LuwiPress_Onyx_Widget_WC_Title extends Widget_Base {

	public function get_name()        { return 'lwp-wc-title'; }
	public function get_title()       { return __( 'WC Product Title', 'luwipress-onyx' ); }
	public function get_icon()        { return 'eicon-product-title'; }
	public function get_categories()  { return [ 'luwipress-onyx' ]; }
	public function get_keywords()    { return [ 'woocommerce', 'product', 'title', 'name', 'single' ]; }
	public function get_style_depends() { return [ 'luwipress-onyx-widgets' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Title', 'luwipress-onyx' ) ] );

		$this->add_control( 'tag', [
			'label'   => __( 'HTML tag', 'luwipress-onyx' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'h1',
			'options' => [ 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'div' => 'div' ],
		] );

		$this->add_control( 'link_to', [
			'label'   => __( 'Link to product page', 'luwipress-onyx' ),
			'type'    => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default' => '',
		] );

		$this->add_responsive_control( 'align', [
			'label'   => __( 'Alignment', 'luwipress-onyx' ),
			'type'    => Controls_Manager::CHOOSE,
			'options' => [
				'left'   => [ 'title' => 'Left',   'icon' => 'eicon-text-align-left' ],
				'center' => [ 'title' => 'Center', 'icon' => 'eicon-text-align-center' ],
				'right'  => [ 'title' => 'Right',  'icon' => 'eicon-text-align-right' ],
			],
			'selectors' => [ '{{WRAPPER}} .lwp-wc-title' => 'text-align: {{VALUE}};' ],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_style', [ 'label' => __( 'Style', 'luwipress-onyx' ), 'tab' => Controls_Manager::TAB_STYLE ] );

		$this->add_control( 'color', [
			'label' => __( 'Color', 'luwipress-onyx' ), 'type' => Controls_Manager::COLOR, 'default' => '#1A1612',
			'selectors' => [ '{{WRAPPER}} .lwp-wc-title, {{WRAPPER}} .lwp-wc-title a' => 'color: {{VALUE}};' ],
		] );

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name' => 'typography', 'selector' => '{{WRAPPER}} .lwp-wc-title',
		] );

		$this->end_controls_section();
	}

	protected function render() {
		$s   = $this->get_settings_for_display();
		$tag = $s['tag'] ?? 'h1';
		$allowed = [ 'h1', 'h2', 'h3', 'h4', 'div' ];
		if ( ! in_array( $tag, $allowed, true ) ) { $tag = 'h1'; }

		$product = $this->get_current_product();
		if ( ! $product ) {
			echo '<' . esc_attr( $tag ) . ' class="lwp-wc-title">' . esc_html__( 'Product Title', 'luwipress-onyx' ) . '</' . esc_attr( $tag ) . '>';
			return;
		}

		$name = $product->get_name();
		$link = ( ( $s['link_to'] ?? '' ) === 'yes' ) ? $product->get_permalink() : '';

		echo '<' . esc_attr( $tag ) . ' class="lwp-wc-title">';
		if ( $link ) {
			echo '<a href="' . esc_url( $link ) . '">' . esc_html( $name ) . '</a>';
		} else {
			echo esc_html( $name );
		}
		echo '</' . esc_attr( $tag ) . '>';
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
