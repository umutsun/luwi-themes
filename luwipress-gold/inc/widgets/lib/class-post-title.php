<?php
/**
 * Widget: Post Title (dynamic).
 *
 * Pro-free replacement for Elementor Pro's `theme-post-title` Theme Builder
 * widget. Reads the current post via get_post() and emits the title in the
 * chosen heading tag, optionally wrapped in a link to the post.
 *
 * Designed to work inside any single-post Elementor template OR inside a
 * standard Loop context. When no post is in scope (e.g. raw widget preview
 * outside a single template) it renders a placeholder so the editor doesn't
 * collapse to zero height.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class LuwiPress_Gold_Widget_Post_Title extends Widget_Base {

	public function get_name()        { return 'lwp-post-title'; }
	public function get_title()       { return __( 'Post Title', 'luwipress-gold' ); }
	public function get_icon()        { return 'eicon-post-title'; }
	public function get_categories()  { return [ 'luwipress-gold' ]; }
	public function get_keywords()    { return [ 'post', 'title', 'dynamic', 'single', 'theme builder' ]; }
	public function get_style_depends() { return [ 'luwipress-gold-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_content', [ 'label' => __( 'Title', 'luwipress-gold' ) ] );

		$this->add_control(
			'tag',
			[
				'label'   => __( 'HTML tag', 'luwipress-gold' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h1',
				'options' => [
					'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3',
					'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6',
					'div' => 'div', 'span' => 'span', 'p' => 'p',
				],
			]
		);

		$this->add_control(
			'link_to',
			[
				'label'   => __( 'Link', 'luwipress-gold' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'none',
				'options' => [
					'none' => __( 'None', 'luwipress-gold' ),
					'post' => __( 'Post URL', 'luwipress-gold' ),
				],
			]
		);

		$this->add_responsive_control(
			'align',
			[
				'label'   => __( 'Alignment', 'luwipress-gold' ),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'left'    => [ 'title' => __( 'Left', 'luwipress-gold' ),   'icon' => 'eicon-text-align-left' ],
					'center'  => [ 'title' => __( 'Center', 'luwipress-gold' ), 'icon' => 'eicon-text-align-center' ],
					'right'   => [ 'title' => __( 'Right', 'luwipress-gold' ),  'icon' => 'eicon-text-align-right' ],
					'justify' => [ 'title' => __( 'Justify', 'luwipress-gold' ),'icon' => 'eicon-text-align-justify' ],
				],
				'selectors' => [ '{{WRAPPER}} .lwp-post-title' => 'text-align: {{VALUE}};' ],
			]
		);

		$this->end_controls_section();

		/* Style */
		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-gold' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control(
			'color',
			[
				'label'     => __( 'Color', 'luwipress-gold' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1A1612',
				'selectors' => [ '{{WRAPPER}} .lwp-post-title, {{WRAPPER}} .lwp-post-title a' => 'color: {{VALUE}};' ],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'typography',
				'selector' => '{{WRAPPER}} .lwp-post-title',
			]
		);

		$this->add_responsive_control(
			'margin',
			[
				'label'      => __( 'Margin', 'luwipress-gold' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [ '{{WRAPPER}} .lwp-post-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$s   = $this->get_settings_for_display();
		$tag = $s['tag'] ?? 'h1';
		$allowed = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p' ];
		if ( ! in_array( $tag, $allowed, true ) ) { $tag = 'h1'; }
		$link_to = $s['link_to'] ?? 'none';

		$post = get_post();
		if ( ! $post ) {
			// Editor preview placeholder.
			echo '<' . esc_attr( $tag ) . ' class="lwp-post-title">' . esc_html__( 'Post Title', 'luwipress-gold' ) . '</' . esc_attr( $tag ) . '>';
			return;
		}

		$title = get_the_title( $post );
		$url   = ( $link_to === 'post' ) ? get_permalink( $post ) : '';

		echo '<' . esc_attr( $tag ) . ' class="lwp-post-title">';
		if ( $url ) {
			echo '<a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a>';
		} else {
			echo esc_html( $title );
		}
		echo '</' . esc_attr( $tag ) . '>';
	}
}
