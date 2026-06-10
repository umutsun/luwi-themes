<?php
/**
 * Widget: WooCommerce Related Products (dynamic).
 *
 * Pro-free replacement for Elementor Pro's `woocommerce-product-related` widget.
 * Queries related products via wc_get_related_products() + renders a card grid
 * using the existing lwp-pcard styling layer (consistent with archive cards).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }
if ( ! class_exists( 'WooCommerce' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class LuwiPress_Sapphire_Widget_WC_Related extends Widget_Base {

	public function get_name()        { return 'lwp-wc-related'; }
	public function get_title()       { return __( 'WC Related Products', 'luwipress-sapphire' ); }
	public function get_icon()        { return 'eicon-products'; }
	public function get_categories()  { return [ 'luwipress-sapphire' ]; }
	public function get_keywords()    { return [ 'woocommerce', 'product', 'related', 'upsell', 'cross-sell' ]; }
	public function get_style_depends() { return [ 'luwipress-sapphire-widgets' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Related', 'luwipress-sapphire' ) ] );

		$this->add_control( 'heading', [
			'label' => __( 'Heading', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::TEXT, 'default' => __( 'You may also enjoy', 'luwipress-sapphire' ),
		] );

		$this->add_control( 'limit', [
			'label' => __( 'Number of products', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::NUMBER, 'default' => 4, 'min' => 1, 'max' => 12,
		] );

		$this->add_responsive_control( 'columns', [
			'label' => __( 'Columns', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::NUMBER, 'default' => 4, 'min' => 1, 'max' => 6,
			'selectors' => [ '{{WRAPPER}} .lwp-wc-related__grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);' ],
		] );

		$this->add_control( 'order_by', [
			'label'   => __( 'Order by', 'luwipress-sapphire' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'rand',
			'options' => [
				'rand'       => __( 'Random', 'luwipress-sapphire' ),
				'date'       => __( 'Date', 'luwipress-sapphire' ),
				'price'      => __( 'Price', 'luwipress-sapphire' ),
				'popularity' => __( 'Popularity', 'luwipress-sapphire' ),
				'rating'     => __( 'Rating', 'luwipress-sapphire' ),
			],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_style', [ 'label' => __( 'Style', 'luwipress-sapphire' ), 'tab' => Controls_Manager::TAB_STYLE ] );

		$this->add_control( 'heading_color', [
			'label' => __( 'Heading color', 'luwipress-sapphire' ), 'type' => Controls_Manager::COLOR, 'default' => '#1A1612',
			'selectors' => [ '{{WRAPPER}} .lwp-wc-related__heading' => 'color: {{VALUE}};' ],
		] );

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name' => 'heading_typography', 'selector' => '{{WRAPPER}} .lwp-wc-related__heading',
		] );

		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$product = $this->get_current_product();
		if ( ! $product ) {
			echo '<div class="lwp-wc-related"><h2 class="lwp-wc-related__heading">' . esc_html( $s['heading'] ?? 'You may also enjoy' ) . '</h2><div class="lwp-wc-related__ph">' . esc_html__( '[Related products will render here on a product page.]', 'luwipress-sapphire' ) . '</div></div>';
			return;
		}

		$limit    = max( 1, min( 12, (int) ( $s['limit'] ?? 4 ) ) );
		$order_by = $s['order_by'] ?? 'rand';

		$related_ids = wc_get_related_products( $product->get_id(), $limit * 2 );
		if ( empty( $related_ids ) ) { return; }

		// Apply ordering (wc_get_related_products returns rand by default; reorder if requested).
		if ( $order_by !== 'rand' ) {
			$args = [
				'limit'    => $limit,
				'include'  => $related_ids,
				'orderby'  => $order_by,
				'return'   => 'ids',
				'status'   => 'publish',
			];
			$ordered = wc_get_products( $args );
			if ( ! empty( $ordered ) ) { $related_ids = $ordered; }
		}

		$related_ids = array_slice( $related_ids, 0, $limit );

		echo '<div class="lwp-wc-related">';
		if ( ! empty( $s['heading'] ) ) {
			echo '<h2 class="lwp-wc-related__heading">' . esc_html( $s['heading'] ) . '</h2>';
		}
		echo '<ul class="lwp-wc-related__grid products columns-' . absint( $s['columns'] ?? 4 ) . '">';
		foreach ( $related_ids as $pid ) {
			$rp = wc_get_product( (int) $pid );
			if ( ! $rp ) { continue; }
			$this->render_product_card( $rp );
		}
		echo '</ul>';
		echo '</div>';
	}

	private function render_product_card( \WC_Product $p ) {
		$link  = $p->get_permalink();
		$name  = $p->get_name();
		$price = $p->get_price_html();
		$img   = $p->get_image( 'woocommerce_thumbnail' );
		$onsale = $p->is_on_sale();
		?>
		<li class="product lwp-pcard">
			<a href="<?php echo esc_url( $link ); ?>" class="lwp-pcard-link">
				<div class="lwp-pcard-media">
					<?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC escaped. ?>
					<?php if ( $onsale ) : ?>
						<span class="lwp-pcard-badge"><?php esc_html_e( 'Sale', 'luwipress-sapphire' ); ?></span>
					<?php endif; ?>
				</div>
				<h3 class="lwp-pcard-title"><?php echo esc_html( $name ); ?></h3>
				<?php if ( $price ) : ?>
					<div class="lwp-pcard-price"><?php echo $price; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC escaped. ?></div>
				<?php endif; ?>
			</a>
		</li>
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
