<?php
/**
 * Widget: Megabar — sub-category strip rendered under the main header.
 *
 * Auto-fills with the top-N most populated WooCommerce sub-categories,
 * or accepts a manual list (slug-per-line). Mobile collapses behind a
 * "Browse categories →" toggle.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class LuwiPress_Sapphire_Widget_Megabar extends Widget_Base {

	public function get_name()        { return 'lwp-megabar'; }
	public function get_title()       { return __( 'Megabar (sub-cat strip)', 'luwipress-sapphire' ); }
	public function get_icon()        { return 'eicon-bullet-list'; }
	public function get_categories()  { return [ 'luwipress-sapphire' ]; }
	public function get_keywords()    { return [ 'megabar', 'subcategory', 'strip', 'shortcuts' ]; }
	public function get_style_depends() { return [ 'luwipress-sapphire-widgets' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'section_source', [ 'label' => __( 'Source', 'luwipress-sapphire' ) ] );

		$this->add_control(
			'mode',
			[
				'label'   => __( 'Mode', 'luwipress-sapphire' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => [
					'auto'   => __( 'Auto (top sub-categories by product count)', 'luwipress-sapphire' ),
					'manual' => __( 'Manual (specific slugs)', 'luwipress-sapphire' ),
				],
			]
		);

		$this->add_control(
			'auto_count',
			[
				'label'     => __( 'How many', 'luwipress-sapphire' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 12,
				'min'       => 4,
				'max'       => 24,
				'condition' => [ 'mode' => 'auto' ],
			]
		);

		$this->add_control(
			'manual_slugs',
			[
				'label'       => __( 'Slugs', 'luwipress-sapphire' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 6,
				'description' => __( 'One slug per line. Order is preserved.', 'luwipress-sapphire' ),
				'condition'   => [ 'mode' => 'manual' ],
			]
		);

		$this->add_control(
			'separator',
			[
				'label'   => __( 'Separator', 'luwipress-sapphire' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'middot',
				'options' => [
					'middot' => '·',
					'slash'  => '/',
					'pipe'   => '|',
					'none'   => __( 'None', 'luwipress-sapphire' ),
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-sapphire' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
		$this->add_control(
			'bar_bg',
			[
				'label'     => __( 'Background', 'luwipress-sapphire' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [ '{{WRAPPER}} .lwp-megabar' => 'background: {{VALUE}};' ],
			]
		);
		$this->add_control(
			'link_color',
			[
				'label'     => __( 'Link color', 'luwipress-sapphire' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#4d4635',
				'selectors' => [ '{{WRAPPER}} .lwp-megabar a' => 'color: {{VALUE}};' ],
			]
		);
		$this->add_control(
			'hover_color',
			[
				'label'     => __( 'Hover color', 'luwipress-sapphire' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#735c00',
				'selectors' => [ '{{WRAPPER}} .lwp-megabar a:hover' => 'color: {{VALUE}};' ],
			]
		);
		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$mode = $s['mode'] ?? 'auto';
		$terms = [];

		if ( $mode === 'manual' ) {
			$slugs = array_filter( array_map( 'trim', explode( "\n", $s['manual_slugs'] ?? '' ) ) );
			foreach ( $slugs as $slug ) {
				$t = get_term_by( 'slug', $slug, 'product_cat' );
				if ( ! $t ) $t = get_term_by( 'slug', $slug, 'category' );
				if ( $t && ! is_wp_error( $t ) ) {
					$terms[] = $t;
				}
			}
		} else {
			$count = max( 4, (int) ( $s['auto_count'] ?? 12 ) );
			$wc_terms = taxonomy_exists( 'product_cat' )
				? get_terms( [
					'taxonomy'       => 'product_cat',
					'parent__not_in' => [ 0 ],
					'orderby'        => 'count',
					'order'          => 'DESC',
					'hide_empty'     => true,
					'number'         => $count,
				] )
				: [];
			if ( ! is_wp_error( $wc_terms ) ) $terms = $wc_terms;
		}

		if ( empty( $terms ) ) {
			echo '<div class="lwp-megabar lwp-megabar--empty">' . esc_html__( 'No sub-categories yet.', 'luwipress-sapphire' ) . '</div>';
			return;
		}

		$sep_chars = [ 'middot' => '·', 'slash' => '/', 'pipe' => '|', 'none' => '' ];
		$sep = $sep_chars[ $s['separator'] ?? 'middot' ] ?? '·';

		echo '<div class="lwp-megabar">';
		echo '<div class="lwp-megabar-inner">';
		$last = count( $terms ) - 1;
		foreach ( $terms as $i => $t ) {
			printf(
				'<a href="%s">%s</a>',
				esc_url( get_term_link( $t ) ),
				esc_html( $t->name )
			);
			if ( $i < $last && $sep !== '' ) {
				echo '<span class="lwp-megabar-sep">' . esc_html( $sep ) . '</span>';
			}
		}
		echo '</div></div>';
	}
}
