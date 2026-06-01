<?php
/**
 * Widget: WooCommerce Product Gallery (dynamic).
 *
 * Pro-free replacement for Elementor Pro's `woocommerce-product-images` widget.
 * Renders the WC standard product gallery DOM (`.woocommerce-product-gallery`)
 * so WC's own JS (PhotoSwipe lightbox, FlexSlider, variation image swap) lights
 * up automatically — we don't reinvent the interaction layer, just provide the
 * container + style controls.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }
if ( ! class_exists( 'WooCommerce' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class LuwiPress_Gold_Widget_WC_Gallery extends Widget_Base {

	public function get_name()        { return 'lwp-wc-gallery'; }
	public function get_title()       { return __( 'WC Product Gallery', 'luwipress-gold' ); }
	public function get_icon()        { return 'eicon-product-images'; }
	public function get_categories()  { return [ 'luwipress-gold' ]; }
	public function get_keywords()    { return [ 'woocommerce', 'product', 'gallery', 'images', 'photos' ]; }
	public function get_style_depends() { return [ 'luwipress-gold-widgets' ]; }

	public function get_script_depends() {
		// WC's gallery interactions need these — let Elementor enqueue them for the editor preview.
		$scripts = [];
		if ( wp_script_is( 'wc-single-product', 'registered' ) ) { $scripts[] = 'wc-single-product'; }
		if ( wp_script_is( 'photoswipe-ui-default', 'registered' ) ) { $scripts[] = 'photoswipe-ui-default'; }
		return $scripts;
	}

	protected function register_controls() {
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Gallery', 'luwipress-gold' ) ] );

		$this->add_control( 'enable_zoom', [
			'label' => __( 'Enable hover zoom', 'luwipress-gold' ),
			'type'  => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes',
		] );
		$this->add_control( 'enable_lightbox', [
			'label' => __( 'Enable click-to-lightbox', 'luwipress-gold' ),
			'type'  => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes',
		] );
		$this->add_control( 'image_size', [
			'label'   => __( 'Main image size', 'luwipress-gold' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'woocommerce_single',
			'options' => [
				'woocommerce_single'    => __( 'WC Single (default)', 'luwipress-gold' ),
				'large'                 => __( 'Large', 'luwipress-gold' ),
				'full'                  => __( 'Full', 'luwipress-gold' ),
			],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_style', [ 'label' => __( 'Style', 'luwipress-gold' ), 'tab' => Controls_Manager::TAB_STYLE ] );

		$this->add_responsive_control( 'main_max_height', [
			'label' => __( 'Main image max height', 'luwipress-gold' ),
			'type'  => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'vh' ],
			'range' => [ 'px' => [ 'min' => 200, 'max' => 1200 ], 'vh' => [ 'min' => 20, 'max' => 100 ] ],
			'selectors' => [ '{{WRAPPER}} .woocommerce-product-gallery__image img' => 'max-height: {{SIZE}}{{UNIT}}; object-fit: contain;' ],
		] );

		$this->add_control( 'main_border_radius', [
			'label' => __( 'Main image radius', 'luwipress-gold' ),
			'type'  => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px' ],
			'selectors' => [ '{{WRAPPER}} .woocommerce-product-gallery__image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->add_responsive_control( 'thumb_size', [
			'label' => __( 'Thumb size', 'luwipress-gold' ),
			'type'  => Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range' => [ 'px' => [ 'min' => 40, 'max' => 160 ] ],
			'selectors' => [ '{{WRAPPER}} .flex-control-thumbs li img, {{WRAPPER}} .lwp-wc-gallery__thumb img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; object-fit: cover;' ],
		] );

		$this->add_control( 'thumb_radius', [
			'label' => __( 'Thumb radius', 'luwipress-gold' ),
			'type'  => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px' ],
			'selectors' => [ '{{WRAPPER}} .flex-control-thumbs li img, {{WRAPPER}} .lwp-wc-gallery__thumb img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$product = $this->get_current_product();
		if ( ! $product ) {
			echo '<div class="lwp-wc-gallery lwp-wc-gallery--placeholder"><div class="lwp-wc-gallery__ph">' . esc_html__( '[Product gallery]', 'luwipress-gold' ) . '</div></div>';
			return;
		}

		$columns          = (int) apply_filters( 'woocommerce_product_thumbnails_columns', 4 );
		$post_thumbnail   = (int) $product->get_image_id();
		$attachment_ids   = $product->get_gallery_image_ids();
		$enable_zoom      = ( $s['enable_zoom'] ?? 'yes' ) === 'yes';
		$enable_lightbox  = ( $s['enable_lightbox'] ?? 'yes' ) === 'yes';
		$image_size       = $s['image_size'] ?? 'woocommerce_single';

		$wrapper_classes = [
			'woocommerce-product-gallery',
			'woocommerce-product-gallery--with-images',
			'woocommerce-product-gallery--columns-' . absint( $columns ),
			'images',
			'lwp-wc-gallery',
		];
		if ( $enable_zoom ) { $wrapper_classes[] = 'has-zoom-support'; }

		// Set up the gallery using WC's standard DOM — its JS (wc-single-product.js)
		// finds the `.woocommerce-product-gallery` selector and lights up FlexSlider
		// + PhotoSwipe automatically.
		?>
		<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>" data-columns="<?php echo esc_attr( $columns ); ?>" style="opacity: 1; transition: opacity .25s ease-in-out;">
			<figure class="woocommerce-product-gallery__wrapper">
				<?php
				if ( $post_thumbnail ) {
					echo wc_get_gallery_image_html( $post_thumbnail, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC core escaped.
				} else {
					$placeholder = wc_placeholder_img_src( $image_size );
					echo '<div class="woocommerce-product-gallery__image woocommerce-product-gallery__image--placeholder"><a href="' . esc_url( $placeholder ) . '"><img src="' . esc_url( $placeholder ) . '" alt="' . esc_attr__( 'Placeholder', 'luwipress-gold' ) . '" /></a></div>';
				}
				foreach ( $attachment_ids as $aid ) {
					echo wc_get_gallery_image_html( (int) $aid ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC core escaped.
				}
				?>
			</figure>
		</div>
		<?php

		// Hint to WC's gallery JS — it reads these data attrs from `body.single-product`
		// but in Theme Builder context body might be `body.page`, so we stamp them
		// onto the wrapper too via inline script (idempotent, micro-payload).
		?>
		<script data-no-optimize="1">
			(function(){
				var w = document.currentScript && document.currentScript.previousElementSibling;
				if (w && w.classList && w.classList.contains('woocommerce-product-gallery')) {
					w.setAttribute('data-zoom_enabled', <?php echo $enable_zoom ? '1' : '0'; ?>);
					w.setAttribute('data-lightbox', <?php echo $enable_lightbox ? '1' : '0'; ?>);
				}
			})();
		</script>
		<?php
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
