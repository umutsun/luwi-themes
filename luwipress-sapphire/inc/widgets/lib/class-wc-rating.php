<?php
/**
 * Widget: WooCommerce Product Rating (dynamic).
 *
 * Pro-free replacement for Elementor Pro's `woocommerce-product-rating` widget.
 * Renders star rating + review count link via wc_get_rating_html() with
 * fallback to "no reviews yet" placeholder when product has 0 reviews.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }
if ( ! class_exists( 'WooCommerce' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class LuwiPress_Sapphire_Widget_WC_Rating extends Widget_Base {

	public function get_name()        { return 'lwp-wc-rating'; }
	public function get_title()       { return __( 'WC Product Rating', 'luwipress-sapphire' ); }
	public function get_icon()        { return 'eicon-rating'; }
	public function get_categories()  { return [ 'luwipress-sapphire' ]; }
	public function get_keywords()    { return [ 'woocommerce', 'product', 'rating', 'stars', 'reviews' ]; }
	public function get_style_depends() { return [ 'luwipress-sapphire-widgets' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Rating', 'luwipress-sapphire' ) ] );

		$this->add_control( 'show_count', [
			'label' => __( 'Show review count', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes',
		] );

		$this->add_control( 'hide_when_empty', [
			'label' => __( 'Hide when no reviews', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '',
		] );

		$this->add_responsive_control( 'align', [
			'label'   => __( 'Alignment', 'luwipress-sapphire' ),
			'type'    => Controls_Manager::CHOOSE,
			'options' => [
				'flex-start' => [ 'title' => 'Left',   'icon' => 'eicon-text-align-left' ],
				'center'     => [ 'title' => 'Center', 'icon' => 'eicon-text-align-center' ],
				'flex-end'   => [ 'title' => 'Right',  'icon' => 'eicon-text-align-right' ],
			],
			'selectors' => [ '{{WRAPPER}} .lwp-wc-rating' => 'justify-content: {{VALUE}};' ],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_style', [ 'label' => __( 'Style', 'luwipress-sapphire' ), 'tab' => Controls_Manager::TAB_STYLE ] );

		$this->add_control( 'star_color', [
			'label' => __( 'Star color', 'luwipress-sapphire' ), 'type' => Controls_Manager::COLOR, 'default' => '#9A7B3A',
			'selectors' => [ '{{WRAPPER}} .lwp-wc-rating .star-rating::before, {{WRAPPER}} .lwp-wc-rating .star-rating span::before' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'count_color', [
			'label' => __( 'Count color', 'luwipress-sapphire' ), 'type' => Controls_Manager::COLOR, 'default' => '#8b7f6a',
			'selectors' => [ '{{WRAPPER}} .lwp-wc-rating__count, {{WRAPPER}} .lwp-wc-rating__count a' => 'color: {{VALUE}};' ],
		] );

		$this->add_responsive_control( 'star_size', [
			'label' => __( 'Star size', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em' ],
			'range' => [ 'px' => [ 'min' => 8, 'max' => 32 ] ],
			'selectors' => [ '{{WRAPPER}} .lwp-wc-rating .star-rating' => 'font-size: {{SIZE}}{{UNIT}};' ],
		] );

		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$product = $this->get_current_product();
		if ( ! $product ) {
			echo '<div class="lwp-wc-rating"><span class="star-rating"><span style="width:80%"></span></span><span class="lwp-wc-rating__count">(0)</span></div>';
			return;
		}

		$rating = (float) $product->get_average_rating();
		$count  = (int) $product->get_review_count();

		if ( $count === 0 && ( $s['hide_when_empty'] ?? '' ) === 'yes' ) { return; }

		echo '<div class="lwp-wc-rating">';
		if ( $rating > 0 || $count > 0 ) {
			echo wc_get_rating_html( $rating, $count ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC core escaped.
		} else {
			echo '<span class="star-rating"><span style="width:0%"></span></span>';
		}
		if ( ( $s['show_count'] ?? 'yes' ) === 'yes' ) {
			$count_label = $count === 0
				? esc_html__( 'No reviews yet', 'luwipress-sapphire' )
				: sprintf( _n( '%d review', '%d reviews', $count, 'luwipress-sapphire' ), $count );
			$reviews_url = get_permalink( $product->get_id() ) . '#reviews';
			echo '<span class="lwp-wc-rating__count"><a href="' . esc_url( $reviews_url ) . '">' . esc_html( $count_label ) . '</a></span>';
		}
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
