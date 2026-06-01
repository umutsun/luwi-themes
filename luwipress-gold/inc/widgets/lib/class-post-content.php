<?php
/**
 * Widget: Post Content (dynamic).
 *
 * Pro-free replacement for Elementor Pro's `theme-post-content` Theme Builder
 * widget. Emits the_content() for the current post, with the standard WP
 * filter chain (oEmbed, shortcodes, Gutenberg blocks).
 *
 * Includes a guard against infinite recursion when this widget appears inside
 * a template that is itself displaying the post — Elementor's editor preview
 * sometimes triggers that loop.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class LuwiPress_Gold_Widget_Post_Content extends Widget_Base {

	private static $rendering = false;

	public function get_name()        { return 'lwp-post-content'; }
	public function get_title()       { return __( 'Post Content', 'luwipress-gold' ); }
	public function get_icon()        { return 'eicon-post-content'; }
	public function get_categories()  { return [ 'luwipress-gold' ]; }
	public function get_keywords()    { return [ 'post', 'content', 'dynamic', 'single', 'body', 'theme builder' ]; }
	public function get_style_depends() { return [ 'luwipress-gold-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_content', [ 'label' => __( 'Content', 'luwipress-gold' ) ] );

		$this->add_control(
			'note',
			[
				'type' => Controls_Manager::RAW_HTML,
				'raw'  => '<em style="color:#8b7f6a;">' . esc_html__( 'Renders the current post body (the_content). No content controls here — edit the post itself.', 'luwipress-gold' ) . '</em>',
			]
		);

		$this->add_responsive_control(
			'max_width',
			[
				'label'      => __( 'Max width', 'luwipress-gold' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em' ],
				'range'      => [
					'px' => [ 'min' => 320, 'max' => 1200, 'step' => 8 ],
					'%'  => [ 'min' => 30,  'max' => 100,  'step' => 1 ],
				],
				'selectors'  => [ '{{WRAPPER}} .lwp-post-content' => 'max-width: {{SIZE}}{{UNIT}};' ],
			]
		);

		$this->end_controls_section();

		/* Style */
		$this->start_controls_section( 'section_style', [
			'label' => __( 'Body', 'luwipress-gold' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'body_color', [
			'label'     => __( 'Text color', 'luwipress-gold' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#3a3128',
			'selectors' => [ '{{WRAPPER}} .lwp-post-content' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'link_color', [
			'label'     => __( 'Link color', 'luwipress-gold' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#9A7B3A',
			'selectors' => [ '{{WRAPPER}} .lwp-post-content a' => 'color: {{VALUE}};' ],
		] );

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'body_typography',
				'selector' => '{{WRAPPER}} .lwp-post-content',
			]
		);

		$this->add_responsive_control( 'paragraph_spacing', [
			'label'      => __( 'Paragraph spacing', 'luwipress-gold' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 80 ] ],
			'selectors'  => [ '{{WRAPPER}} .lwp-post-content p' => 'margin-bottom: {{SIZE}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_headings_style', [
			'label' => __( 'Headings', 'luwipress-gold' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'heading_color', [
			'label'     => __( 'Heading color', 'luwipress-gold' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#1A1612',
			'selectors' => [ '{{WRAPPER}} .lwp-post-content h1, {{WRAPPER}} .lwp-post-content h2, {{WRAPPER}} .lwp-post-content h3, {{WRAPPER}} .lwp-post-content h4, {{WRAPPER}} .lwp-post-content h5, {{WRAPPER}} .lwp-post-content h6' => 'color: {{VALUE}};' ],
		] );

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'heading_typography',
				'selector' => '{{WRAPPER}} .lwp-post-content h2, {{WRAPPER}} .lwp-post-content h3',
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		if ( self::$rendering ) {
			// Recursion guard — widget should never render itself inside its own output.
			return;
		}
		$post = get_post();
		if ( ! $post ) {
			echo '<div class="lwp-post-content"><p>' . esc_html__( '[Post content goes here.]', 'luwipress-gold' ) . '</p></div>';
			return;
		}

		self::$rendering = true;
		$content = get_post_field( 'post_content', $post );
		// Apply standard the_content filter chain so blocks/shortcodes/oembeds run.
		$rendered = apply_filters( 'the_content', $content );
		$rendered = str_replace( ']]>', ']]&gt;', $rendered );
		self::$rendering = false;

		echo '<div class="lwp-post-content entry-content">' . $rendered . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses'd through the_content filter chain.
	}
}
