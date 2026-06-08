<?php
/**
 * Widget: Post Featured Image (dynamic).
 *
 * Pro-free replacement for Elementor Pro's `theme-post-featured-image` Theme
 * Builder widget. Emits get_the_post_thumbnail() for the current post with
 * configurable size, link target, and caption.
 *
 * Falls back to a 16:9 placeholder block when the post has no thumbnail —
 * keeps editor preview rectangle visible instead of collapsing to nothing.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Image_Size;

class LuwiPress_Amber_Widget_Post_Featured_Image extends Widget_Base {

	public function get_name()        { return 'lwp-post-featured-image'; }
	public function get_title()       { return __( 'Post Featured Image', 'luwipress-amber' ); }
	public function get_icon()        { return 'eicon-featured-image'; }
	public function get_categories()  { return [ 'luwipress-amber' ]; }
	public function get_keywords()    { return [ 'post', 'featured', 'image', 'thumbnail', 'dynamic', 'theme builder' ]; }
	public function get_style_depends() { return [ 'luwipress-amber-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_content', [ 'label' => __( 'Image', 'luwipress-amber' ) ] );

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'    => 'image_size',
				'default' => 'large',
			]
		);

		$this->add_control(
			'link_to',
			[
				'label'   => __( 'Link', 'luwipress-amber' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'none',
				'options' => [
					'none'  => __( 'None', 'luwipress-amber' ),
					'post'  => __( 'Post URL', 'luwipress-amber' ),
					'file'  => __( 'Image file', 'luwipress-amber' ),
				],
			]
		);

		$this->add_control(
			'caption',
			[
				'label'   => __( 'Caption', 'luwipress-amber' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'none',
				'options' => [
					'none'       => __( 'None', 'luwipress-amber' ),
					'attachment' => __( 'Attachment caption', 'luwipress-amber' ),
				],
			]
		);

		$this->end_controls_section();

		/* Style */
		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-amber' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_responsive_control( 'width', [
			'label'      => __( 'Width', 'luwipress-amber' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', '%' ],
			'range'      => [
				'px' => [ 'min' => 100, 'max' => 1600, 'step' => 8 ],
				'%'  => [ 'min' => 10,  'max' => 100 ],
			],
			'default'   => [ 'unit' => '%', 'size' => 100 ],
			'selectors' => [ '{{WRAPPER}} .lwp-post-featured-image img' => 'width: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_responsive_control( 'max_height', [
			'label'      => __( 'Max height', 'luwipress-amber' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'vh' ],
			'range'      => [
				'px' => [ 'min' => 100, 'max' => 1200 ],
				'vh' => [ 'min' => 10, 'max' => 100 ],
			],
			'selectors'  => [ '{{WRAPPER}} .lwp-post-featured-image img' => 'max-height: {{SIZE}}{{UNIT}}; object-fit: cover;' ],
		] );

		$this->add_control( 'object_fit', [
			'label'   => __( 'Object fit', 'luwipress-amber' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'cover',
			'options' => [
				'cover'   => __( 'Cover', 'luwipress-amber' ),
				'contain' => __( 'Contain', 'luwipress-amber' ),
				'fill'    => __( 'Fill', 'luwipress-amber' ),
				'none'    => __( 'None', 'luwipress-amber' ),
			],
			'selectors' => [ '{{WRAPPER}} .lwp-post-featured-image img' => 'object-fit: {{VALUE}};' ],
		] );

		$this->add_responsive_control( 'border_radius', [
			'label'      => __( 'Border radius', 'luwipress-amber' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%' ],
			'selectors'  => [ '{{WRAPPER}} .lwp-post-featured-image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->add_responsive_control( 'align', [
			'label'   => __( 'Alignment', 'luwipress-amber' ),
			'type'    => Controls_Manager::CHOOSE,
			'options' => [
				'left'   => [ 'title' => __( 'Left', 'luwipress-amber' ),   'icon' => 'eicon-text-align-left' ],
				'center' => [ 'title' => __( 'Center', 'luwipress-amber' ), 'icon' => 'eicon-text-align-center' ],
				'right'  => [ 'title' => __( 'Right', 'luwipress-amber' ),  'icon' => 'eicon-text-align-right' ],
			],
			'default'   => 'center',
			'selectors' => [ '{{WRAPPER}} .lwp-post-featured-image' => 'text-align: {{VALUE}};' ],
		] );

		$this->end_controls_section();
	}

	protected function render() {
		$s        = $this->get_settings_for_display();
		$link_to  = $s['link_to'] ?? 'none';
		$caption  = $s['caption'] ?? 'none';

		$post = get_post();
		if ( ! $post ) {
			echo '<div class="lwp-post-featured-image lwp-post-featured-image--placeholder"><div class="lwp-fpi-ph">' . esc_html__( '[Featured image]', 'luwipress-amber' ) . '</div></div>';
			return;
		}

		$thumb_id = (int) get_post_thumbnail_id( $post );
		if ( ! $thumb_id ) {
			echo '<div class="lwp-post-featured-image lwp-post-featured-image--placeholder"><div class="lwp-fpi-ph">' . esc_html__( '[No featured image]', 'luwipress-amber' ) . '</div></div>';
			return;
		}

		$size = $s['image_size_size'] ?? 'large';
		// Render image via Elementor's standard helper if available, else core.
		if ( class_exists( '\\Elementor\\Group_Control_Image_Size' ) && method_exists( '\\Elementor\\Group_Control_Image_Size', 'get_attachment_image_html' ) ) {
			$img_html = \Elementor\Group_Control_Image_Size::get_attachment_image_html(
				array_merge( $s, [ 'image' => [ 'id' => $thumb_id, 'url' => wp_get_attachment_image_url( $thumb_id, $size ) ] ] ),
				'image_size',
				'image'
			);
		} else {
			$img_html = wp_get_attachment_image( $thumb_id, $size, false, [ 'class' => 'lwp-fpi-img' ] );
		}

		if ( ! $img_html ) {
			$img_html = wp_get_attachment_image( $thumb_id, $size );
		}

		$url = '';
		if ( $link_to === 'post' ) {
			$url = get_permalink( $post );
		} elseif ( $link_to === 'file' ) {
			$url = wp_get_attachment_url( $thumb_id );
		}

		echo '<figure class="lwp-post-featured-image">';
		if ( $url ) {
			echo '<a href="' . esc_url( $url ) . '">' . $img_html . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attachment image HTML.
		} else {
			echo $img_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attachment image HTML.
		}

		if ( $caption === 'attachment' ) {
			$cap = wp_get_attachment_caption( $thumb_id );
			if ( $cap ) {
				echo '<figcaption class="lwp-post-featured-image__caption">' . esc_html( $cap ) . '</figcaption>';
			}
		}
		echo '</figure>';
	}
}
