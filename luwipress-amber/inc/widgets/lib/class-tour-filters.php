<?php
/**
 * Elementor widget — Tour Filters sidebar.
 *
 * Renders the `.filters` aside (category checkboxes from product_cat, duration
 * buckets, price range slider) that assets/js/tours.js binds to a Tour Grid of
 * the same data-tours-group.
 *
 * @package luwipress-amber
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }
if ( ! class_exists( 'WooCommerce' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class LuwiPress_Amber_Widget_Tour_Filters extends Widget_Base {

	public function get_name() { return 'lwp-tour-filters'; }
	public function get_title() { return __( 'Tour Filters', 'luwipress-amber' ); }
	public function get_icon() { return 'eicon-filter'; }
	public function get_categories() { return [ 'luwipress-amber' ]; }
	public function get_keywords() { return [ 'tour', 'filter', 'facet', 'sidebar', 'travel' ]; }
	public function get_style_depends() { return [ 'luwipress-amber-widgets' ]; }
	public function get_script_depends() { return [ 'luwipress-amber-tours' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'content', [ 'label' => __( 'Filters', 'luwipress-amber' ) ] );

		$this->add_control( 'show_category', [ 'label' => __( 'Category filter', 'luwipress-amber' ), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->add_control( 'label_category', [ 'label' => __( 'Category label', 'luwipress-amber' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Category', 'luwipress-amber' ) ] );

		$this->add_control( 'show_duration', [ 'label' => __( 'Duration filter', 'luwipress-amber' ), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->add_control( 'label_duration', [ 'label' => __( 'Duration label', 'luwipress-amber' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Duration', 'luwipress-amber' ) ] );

		$this->add_control( 'show_price', [ 'label' => __( 'Price filter', 'luwipress-amber' ), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->add_control( 'label_price', [ 'label' => __( 'Price label', 'luwipress-amber' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Max price', 'luwipress-amber' ) ] );

		$this->add_control( 'reset_label', [ 'label' => __( 'Reset label', 'luwipress-amber' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Reset all', 'luwipress-amber' ) ] );
		$this->add_control( 'group', [
			'label'       => __( 'Filter group', 'luwipress-amber' ),
			'description' => __( 'Must match the Tour Grid + Toolbar.', 'luwipress-amber' ),
			'type'        => Controls_Manager::TEXT,
		] );
		$this->end_controls_section();
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$group = isset( $s['group'] ) ? sanitize_title( $s['group'] ) : '';
		$sym   = function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol() ) : '';

		printf( '<aside class="filters" data-lwp-filters data-tours-group="%s">', esc_attr( $group ) );
		printf(
			'<div class="f-title"><h3>%s</h3><button type="button" class="reset">%s</button></div>',
			esc_html__( 'Filters', 'luwipress-amber' ),
			esc_html( $s['reset_label'] ?: __( 'Reset all', 'luwipress-amber' ) )
		);

		if ( 'yes' === ( $s['show_category'] ?? '' ) ) {
			$terms = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0 ] );
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				echo '<div class="filter-group"><div class="filter-h">' . esc_html( $s['label_category'] ) . '</div>';
				foreach ( $terms as $t ) {
					printf(
						'<label class="filter-opt"><input type="checkbox" class="f-cat" value="%s"> %s <span class="count">%d</span></label>',
						esc_attr( $t->slug ),
						esc_html( $t->name ),
						(int) $t->count
					);
				}
				echo '</div>';
			}
		}

		if ( 'yes' === ( $s['show_duration'] ?? '' ) ) {
			$buckets = [
				'short' => __( 'Up to 3 hours', 'luwipress-amber' ),
				'half'  => __( 'Half day (3–6h)', 'luwipress-amber' ),
				'full'  => __( 'Full day', 'luwipress-amber' ),
				'multi' => __( 'Multi-day', 'luwipress-amber' ),
			];
			echo '<div class="filter-group"><div class="filter-h">' . esc_html( $s['label_duration'] ) . '</div>';
			foreach ( $buckets as $val => $label ) {
				printf(
					'<label class="filter-opt"><input type="checkbox" class="f-dur" value="%s"> %s</label>',
					esc_attr( $val ),
					esc_html( $label )
				);
			}
			echo '</div>';
		}

		if ( 'yes' === ( $s['show_price'] ?? '' ) ) {
			$max = $this->max_tour_price();
			$max = $max > 0 ? (int) ceil( $max / 50 ) * 50 : 5000;
			echo '<div class="filter-group"><div class="filter-h">' . esc_html( $s['label_price'] ) . '</div>';
			echo '<div class="price-range"><div class="price-vals"><span>' . esc_html( $sym . '0' ) . '</span><span class="price-out">' . esc_html( $sym . number_format_i18n( $max ) ) . '</span></div>';
			printf(
				'<input type="range" class="price-range-input" min="0" max="%1$d" step="50" value="%1$d"></div></div>',
				(int) $max
			);
		}

		echo '</aside>';
	}

	private function max_tour_price() {
		$products = wc_get_products( [
			'limit'        => 1,
			'status'       => 'publish',
			'orderby'      => 'price',
			'order'        => 'DESC',
			'meta_key'     => '_fbd_is_tour',
			'meta_value'   => 'yes',
			'meta_compare' => '=',
		] );
		if ( empty( $products ) ) {
			$products = wc_get_products( [ 'limit' => 1, 'status' => 'publish', 'orderby' => 'price', 'order' => 'DESC' ] );
		}
		return ! empty( $products ) ? (float) $products[0]->get_price() : 0;
	}
}
