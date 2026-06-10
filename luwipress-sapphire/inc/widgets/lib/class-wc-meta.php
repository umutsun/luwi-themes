<?php
/**
 * Widget: WooCommerce Product Meta (dynamic).
 *
 * Pro-free replacement for Elementor Pro's `woocommerce-product-meta` widget.
 * Renders SKU + product categories + product tags as a small key-value table.
 * Each row toggleable; matches WC's native meta block convention.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }
if ( ! class_exists( 'WooCommerce' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class LuwiPress_Sapphire_Widget_WC_Meta extends Widget_Base {

	public function get_name()        { return 'lwp-wc-meta'; }
	public function get_title()       { return __( 'WC Product Meta', 'luwipress-sapphire' ); }
	public function get_icon()        { return 'eicon-product-meta'; }
	public function get_categories()  { return [ 'luwipress-sapphire' ]; }
	public function get_keywords()    { return [ 'woocommerce', 'product', 'meta', 'sku', 'categories', 'tags' ]; }
	public function get_style_depends() { return [ 'luwipress-sapphire-widgets' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Rows', 'luwipress-sapphire' ) ] );

		$this->add_control( 'show_sku', [
			'label' => __( 'Show SKU', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes',
		] );
		$this->add_control( 'sku_label', [
			'label' => __( 'SKU label', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::TEXT, 'default' => __( 'SKU', 'luwipress-sapphire' ),
			'condition' => [ 'show_sku' => 'yes' ],
		] );

		$this->add_control( 'show_categories', [
			'label' => __( 'Show categories', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes',
		] );
		$this->add_control( 'categories_label', [
			'label' => __( 'Categories label', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::TEXT, 'default' => __( 'Categories', 'luwipress-sapphire' ),
			'condition' => [ 'show_categories' => 'yes' ],
		] );

		$this->add_control( 'show_tags', [
			'label' => __( 'Show tags', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '',
		] );
		$this->add_control( 'tags_label', [
			'label' => __( 'Tags label', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::TEXT, 'default' => __( 'Tags', 'luwipress-sapphire' ),
			'condition' => [ 'show_tags' => 'yes' ],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_style', [ 'label' => __( 'Style', 'luwipress-sapphire' ), 'tab' => Controls_Manager::TAB_STYLE ] );

		$this->add_control( 'label_color', [
			'label' => __( 'Label color', 'luwipress-sapphire' ), 'type' => Controls_Manager::COLOR, 'default' => '#8b7f6a',
			'selectors' => [ '{{WRAPPER}} .lwp-wc-meta__label' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'value_color', [
			'label' => __( 'Value color', 'luwipress-sapphire' ), 'type' => Controls_Manager::COLOR, 'default' => '#1A1612',
			'selectors' => [ '{{WRAPPER}} .lwp-wc-meta__value, {{WRAPPER}} .lwp-wc-meta__value a' => 'color: {{VALUE}};' ],
		] );

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name' => 'typography', 'selector' => '{{WRAPPER}} .lwp-wc-meta',
		] );

		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$product = $this->get_current_product();
		if ( ! $product ) {
			echo '<dl class="lwp-wc-meta"><dt class="lwp-wc-meta__label">SKU</dt><dd class="lwp-wc-meta__value">—</dd></dl>';
			return;
		}

		$rows = [];

		if ( ( $s['show_sku'] ?? '' ) === 'yes' && $product->get_sku() ) {
			$rows[] = [
				'label' => $s['sku_label'] ?? __( 'SKU', 'luwipress-sapphire' ),
				'value' => esc_html( $product->get_sku() ),
			];
		}

		if ( ( $s['show_categories'] ?? '' ) === 'yes' ) {
			$cats = wc_get_product_category_list( $product->get_id(), ', ' );
			if ( $cats ) {
				$rows[] = [
					'label' => $s['categories_label'] ?? __( 'Categories', 'luwipress-sapphire' ),
					'value' => $cats, // pre-escaped by wc_get_product_category_list
				];
			}
		}

		if ( ( $s['show_tags'] ?? '' ) === 'yes' ) {
			$tags = wc_get_product_tag_list( $product->get_id(), ', ' );
			if ( $tags ) {
				$rows[] = [
					'label' => $s['tags_label'] ?? __( 'Tags', 'luwipress-sapphire' ),
					'value' => $tags,
				];
			}
		}

		if ( empty( $rows ) ) { return; }

		echo '<dl class="lwp-wc-meta">';
		foreach ( $rows as $row ) {
			echo '<dt class="lwp-wc-meta__label">' . esc_html( $row['label'] ) . '</dt>';
			echo '<dd class="lwp-wc-meta__value">' . $row['value'] . '</dd>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped.
		}
		echo '</dl>';
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
