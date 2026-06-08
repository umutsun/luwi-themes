<?php
/**
 * Widget: LuwiPress Emerald Product Card.
 *
 * Renders a single WooCommerce product in the Gold style — sale badge,
 * favourite heart, italic gold price ladder, quick-add pill on hover.
 *
 * Operator picks the product manually via the panel autocomplete; for batch
 * grids use Elementor Pro's Loop Grid widget with this card as the item.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class LuwiPress_Emerald_Widget_Product_Card extends Widget_Base {

	public function get_name()        { return 'lwp-product-card'; }
	public function get_title()       { return __( 'Product Card', 'luwipress-emerald' ); }
	public function get_icon()        { return 'eicon-product-categories'; }
	public function get_categories()  { return [ 'luwipress-emerald' ]; }
	public function get_keywords()    { return [ 'woocommerce', 'product', 'card', 'shop', 'gold' ]; }
	public function get_style_depends() { return [ 'luwipress-emerald-widgets' ]; }

	protected function register_controls() {

		/* ─── Source ─── */
		$this->start_controls_section(
			'section_source',
			[ 'label' => __( 'Source', 'luwipress-emerald' ) ]
		);

		$this->add_control(
			'product_id',
			[
				'label'       => __( 'Product', 'luwipress-emerald' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => $this->get_product_options(),
				'description' => __( 'Pick the product to render.', 'luwipress-emerald' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'show_badge',
			[
				'label'        => __( 'Show sale badge', 'luwipress-emerald' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_favourite',
			[
				'label'   => __( 'Show favourite heart', 'luwipress-emerald' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'show_quick_add',
			[
				'label'   => __( 'Show quick-add on hover', 'luwipress-emerald' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'show_maker',
			[
				'label'       => __( 'Show maker name', 'luwipress-emerald' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'description' => __( 'Reads the pa_luthier attribute if available.', 'luwipress-emerald' ),
			]
		);

		$this->end_controls_section();

		/* ─── Style ─── */
		$this->start_controls_section(
			'section_style',
			[
				'label' => __( 'Style', 'luwipress-emerald' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'card_radius',
			[
				'label'   => __( 'Card corner radius', 'luwipress-emerald' ),
				'type'    => Controls_Manager::SLIDER,
				'range'   => [ 'px' => [ 'min' => 0, 'max' => 24 ] ],
				'default' => [ 'unit' => 'px', 'size' => 8 ],
				'selectors' => [
					'{{WRAPPER}} .lwp-pc' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'card_bg',
			[
				'label'     => __( 'Card background', 'luwipress-emerald' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .lwp-pc' => 'background: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'badge_bg',
			[
				'label'     => __( 'Sale badge color', 'luwipress-emerald' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#a33b3e',
				'selectors' => [
					'{{WRAPPER}} .lwp-pc-badge' => 'background: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'price_color',
			[
				'label'     => __( 'Price color', 'luwipress-emerald' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#735c00',
				'selectors' => [
					'{{WRAPPER}} .lwp-pc-price ins, {{WRAPPER}} .lwp-pc-price .price' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .lwp-pc-title',
			]
		);

		$this->end_controls_section();
	}

	private function get_product_options() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return [ '' => __( 'Install WooCommerce to pick products', 'luwipress-emerald' ) ];
		}
		$products = get_posts( [
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );
		$out = [ '' => __( '— Select a product —', 'luwipress-emerald' ) ];
		foreach ( $products as $p ) {
			$out[ $p->ID ] = $p->post_title;
		}
		return $out;
	}

	protected function render() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->render_placeholder( __( 'WooCommerce required.', 'luwipress-emerald' ) );
			return;
		}
		$settings = $this->get_settings_for_display();
		$pid      = (int) ( $settings['product_id'] ?? 0 );
		if ( ! $pid ) {
			$this->render_placeholder( __( 'Pick a product in the panel →', 'luwipress-emerald' ) );
			return;
		}
		$product = wc_get_product( $pid );
		if ( ! $product ) {
			$this->render_placeholder( __( 'Product not found.', 'luwipress-emerald' ) );
			return;
		}

		$cats   = wp_get_post_terms( $pid, 'product_cat', [ 'fields' => 'names' ] );
		$cat    = ! is_wp_error( $cats ) && ! empty( $cats ) ? $cats[0] : '';
		$maker  = $product->get_attribute( 'pa_luthier' );
		$is_sale = $product->is_on_sale();
		$badge_text = '';
		if ( $is_sale ) {
			$reg = (float) $product->get_regular_price();
			$sal = (float) $product->get_sale_price();
			$badge_text = ( $reg > 0 && $sal > 0 )
				? '−' . round( ( ( $reg - $sal ) / $reg ) * 100 ) . '%'
				: __( 'Sale', 'luwipress-emerald' );
		}
		?>
		<a class="lwp-pc product-card" href="<?php echo esc_url( get_permalink( $pid ) ); ?>">
			<div class="lwp-pc-img">
				<?php if ( $is_sale && $settings['show_badge'] === 'yes' ) : ?>
					<span class="lwp-pc-badge"><?php echo esc_html( $badge_text ); ?></span>
				<?php endif; ?>
				<?php if ( $settings['show_favourite'] === 'yes' ) : ?>
					<button type="button" class="lwp-pc-fav" aria-label="<?php esc_attr_e( 'Save', 'luwipress-emerald' ); ?>" onclick="event.preventDefault();event.stopPropagation();">♡</button>
				<?php endif; ?>
				<?php echo $product->get_image( 'woocommerce_thumbnail' ); ?>
				<?php if ( $settings['show_quick_add'] === 'yes' ) : ?>
					<span class="lwp-pc-quick-add quick-add" data-product-id="<?php echo esc_attr( $pid ); ?>"><?php esc_html_e( 'Quick add', 'luwipress-emerald' ); ?></span>
				<?php endif; ?>
			</div>
			<div class="lwp-pc-meta">
				<?php if ( $cat ) : ?>
					<span class="lwp-pc-cat"><?php echo esc_html( $cat ); ?></span>
				<?php endif; ?>
				<h3 class="lwp-pc-title"><?php echo esc_html( $product->get_name() ); ?></h3>
				<?php if ( $maker && $settings['show_maker'] === 'yes' ) : ?>
					<span class="lwp-pc-maker"><?php
						/* translators: %s: maker name */
						printf( esc_html__( 'by %s', 'luwipress-emerald' ), esc_html( $maker ) );
					?></span>
				<?php endif; ?>
				<div class="lwp-pc-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
			</div>
		</a>
		<?php
	}

	private function render_placeholder( $message ) {
		echo '<div class="lwp-pc lwp-pc--placeholder">' . esc_html( $message ) . '</div>';
	}
}
