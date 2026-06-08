<?php
/**
 * Widget: WooCommerce Add to Cart (dynamic).
 *
 * Pro-free replacement for Elementor Pro's `woocommerce-product-add-to-cart`
 * widget. Delegates the ATC form rendering to WC core via wc_get_template()
 * — `simple.php`, `variable.php`, `grouped.php`, `external.php` are picked
 * automatically by product type. This way variation JS, quantity validation,
 * AJAX add, and any plugin extension hooked into `woocommerce_*_add_to_cart`
 * actions all light up without us re-implementing them.
 *
 * Style controls customize the qty input + button look only; the form DOM
 * stays WC-standard so theme overrides in woocommerce/single-product/add-to-cart/
 * continue to work for advanced customers.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }
if ( ! class_exists( 'WooCommerce' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class LuwiPress_Amber_Widget_WC_Add_To_Cart extends Widget_Base {

	public function get_name()        { return 'lwp-wc-add-to-cart'; }
	public function get_title()       { return __( 'WC Add to Cart', 'luwipress-amber' ); }
	public function get_icon()        { return 'eicon-product-add-to-cart'; }
	public function get_categories()  { return [ 'luwipress-amber' ]; }
	public function get_keywords()    { return [ 'woocommerce', 'product', 'add', 'cart', 'buy', 'purchase' ]; }
	public function get_style_depends() { return [ 'luwipress-amber-widgets' ]; }

	public function get_script_depends() {
		$scripts = [];
		if ( wp_script_is( 'wc-add-to-cart-variation', 'registered' ) ) { $scripts[] = 'wc-add-to-cart-variation'; }
		if ( wp_script_is( 'wc-single-product', 'registered' ) )       { $scripts[] = 'wc-single-product'; }
		return $scripts;
	}

	protected function register_controls() {
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Add to Cart', 'luwipress-amber' ) ] );

		$this->add_control( 'note', [
			'type' => Controls_Manager::RAW_HTML,
			'raw'  => '<em style="color:#8b7f6a;">' . esc_html__( 'Form type is auto-detected from the product (simple / variable / grouped / external). Variation JS + AJAX add inherit from WooCommerce core.', 'luwipress-amber' ) . '</em>',
		] );

		$this->add_control( 'show_quantity_label', [
			'label' => __( 'Show quantity label', 'luwipress-amber' ),
			'type'  => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes',
		] );

		$this->add_control( 'quantity_label', [
			'label' => __( 'Quantity label', 'luwipress-amber' ),
			'type'  => Controls_Manager::TEXT, 'default' => __( 'Quantity', 'luwipress-amber' ),
			'condition' => [ 'show_quantity_label' => 'yes' ],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_button_style', [ 'label' => __( 'Button', 'luwipress-amber' ), 'tab' => Controls_Manager::TAB_STYLE ] );

		$this->add_control( 'btn_bg', [
			'label' => __( 'Background', 'luwipress-amber' ), 'type' => Controls_Manager::COLOR, 'default' => '#1A1612',
			'selectors' => [ '{{WRAPPER}} .lwp-wc-atc .single_add_to_cart_button, {{WRAPPER}} .lwp-wc-atc button.button' => 'background-color: {{VALUE}}; border-color: {{VALUE}};' ],
		] );
		$this->add_control( 'btn_bg_hover', [
			'label' => __( 'Background (hover)', 'luwipress-amber' ), 'type' => Controls_Manager::COLOR, 'default' => '#9A7B3A',
			'selectors' => [ '{{WRAPPER}} .lwp-wc-atc .single_add_to_cart_button:hover, {{WRAPPER}} .lwp-wc-atc button.button:hover' => 'background-color: {{VALUE}}; border-color: {{VALUE}};' ],
		] );
		$this->add_control( 'btn_color', [
			'label' => __( 'Text color', 'luwipress-amber' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff',
			'selectors' => [ '{{WRAPPER}} .lwp-wc-atc .single_add_to_cart_button, {{WRAPPER}} .lwp-wc-atc button.button' => 'color: {{VALUE}};' ],
		] );

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name' => 'btn_typography', 'selector' => '{{WRAPPER}} .lwp-wc-atc .single_add_to_cart_button, {{WRAPPER}} .lwp-wc-atc button.button',
		] );

		$this->add_control( 'btn_radius', [
			'label' => __( 'Radius', 'luwipress-amber' ), 'type' => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px' ],
			'selectors' => [ '{{WRAPPER}} .lwp-wc-atc .single_add_to_cart_button, {{WRAPPER}} .lwp-wc-atc button.button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->add_responsive_control( 'btn_padding', [
			'label' => __( 'Padding', 'luwipress-amber' ), 'type' => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'selectors' => [ '{{WRAPPER}} .lwp-wc-atc .single_add_to_cart_button, {{WRAPPER}} .lwp-wc-atc button.button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->add_control( 'btn_full_width', [
			'label' => __( 'Full width', 'luwipress-amber' ),
			'type'  => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '',
			'selectors_dictionary' => [ 'yes' => 'width: 100%;', '' => 'width: auto;' ],
			'selectors' => [ '{{WRAPPER}} .lwp-wc-atc .single_add_to_cart_button' => '{{VALUE}}' ],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_qty_style', [ 'label' => __( 'Quantity', 'luwipress-amber' ), 'tab' => Controls_Manager::TAB_STYLE ] );

		$this->add_control( 'qty_label_color', [
			'label' => __( 'Label color', 'luwipress-amber' ), 'type' => Controls_Manager::COLOR, 'default' => '#8b7f6a',
			'selectors' => [ '{{WRAPPER}} .lwp-wc-atc__qty-label' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'qty_border', [
			'label' => __( 'Input border color', 'luwipress-amber' ), 'type' => Controls_Manager::COLOR, 'default' => '#E8DFC8',
			'selectors' => [ '{{WRAPPER}} .lwp-wc-atc .quantity input.qty' => 'border-color: {{VALUE}};' ],
		] );

		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$product = $this->get_current_product();
		if ( ! $product ) {
			echo '<div class="lwp-wc-atc lwp-wc-atc--placeholder"><button class="button single_add_to_cart_button" disabled>' . esc_html__( 'Add to Cart', 'luwipress-amber' ) . '</button></div>';
			return;
		}

		// Set up $product global so WC's add-to-cart templates resolve correctly even
		// outside the canonical loop (e.g. on a custom Single Product Theme Builder page).
		$GLOBALS['product'] = $product;
		$show_qty_label   = ( $s['show_quantity_label'] ?? 'yes' ) === 'yes';
		$quantity_label   = (string) ( $s['quantity_label'] ?? __( 'Quantity', 'luwipress-amber' ) );

		echo '<div class="lwp-wc-atc lwp-wc-atc--type-' . esc_attr( $product->get_type() ) . '">';

		if ( $show_qty_label && $product->is_type( 'simple' ) ) {
			// Inject a small label above WC's quantity input via a filter.
			$label_html = '<span class="lwp-wc-atc__qty-label">' . esc_html( $quantity_label ) . '</span>';
			add_action( 'woocommerce_before_add_to_cart_quantity', function () use ( $label_html ) {
				echo $label_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped.
			}, 5 );
		}

		// Delegate to WC's standard add-to-cart template via the canonical action chain.
		// This handles simple, variable, grouped, external — and any custom product types
		// that hook into `woocommerce_template_single_add_to_cart`.
		do_action( 'woocommerce_template_single_add_to_cart' );

		echo '</div>';
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
