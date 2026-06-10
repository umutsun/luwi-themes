<?php
/**
 * Widget: WooCommerce Products Grid (curated, sortable).
 *
 * Pro-free replacement for Elementor Pro's `wc-products` widget and a clean
 * substitute for the legacy `[products]` shortcode. Queries via wc_get_products()
 * and renders the same .lwp-pcard card chrome used on archives + related grids,
 * so the visual output is consistent across the entire site.
 *
 * Designed to drop in where a homepage `[products limit=8 columns=4 orderby=popularity]`
 * shortcode used to live — the operator gets native Elementor controls (number
 * inputs, dropdowns, repeater for manual product picks) instead of a string of
 * shortcode params, AND we preserve the `tap-products` wrapper class hook so
 * customer-side CSS overrides continue to apply.
 *
 * @since 1.10.2
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }
if ( ! class_exists( 'WooCommerce' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class LuwiPress_Sapphire_Widget_Product_Grid extends Widget_Base {

	public function get_name()        { return 'lwp-product-grid'; }
	public function get_title()       { return __( 'Product Grid', 'luwipress-sapphire' ); }
	public function get_icon()        { return 'eicon-products'; }
	public function get_categories()  { return [ 'luwipress-sapphire' ]; }
	public function get_keywords()    { return [ 'woocommerce', 'product', 'grid', 'shop', 'popular', 'best sellers', 'featured' ]; }
	public function get_style_depends() { return [ 'luwipress-sapphire-widgets' ]; }

	protected function register_controls() {

		/* ────────────── Content / Query ────────────── */
		$this->start_controls_section( 'section_query', [ 'label' => __( 'Query', 'luwipress-sapphire' ) ] );

		$this->add_control( 'heading', [
			'label'       => __( 'Heading (optional)', 'luwipress-sapphire' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => '',
			'placeholder' => __( 'e.g. Most popular', 'luwipress-sapphire' ),
		] );

		$this->add_control( 'source', [
			'label'   => __( 'Source', 'luwipress-sapphire' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'query',
			'options' => [
				'query'    => __( 'Auto query (by orderby)', 'luwipress-sapphire' ),
				'featured' => __( 'Featured products only', 'luwipress-sapphire' ),
				'manual'   => __( 'Manual ID list', 'luwipress-sapphire' ),
			],
		] );

		$this->add_control( 'manual_ids', [
			'label'       => __( 'Product IDs (comma or space separated)', 'luwipress-sapphire' ),
			'type'        => Controls_Manager::TEXTAREA,
			'default'     => '',
			'placeholder' => '123, 456, 789',
			'condition'   => [ 'source' => 'manual' ],
			'description' => __( 'Order in this list is preserved. Limit + Orderby ignored.', 'luwipress-sapphire' ),
		] );

		$this->add_control( 'limit', [
			'label'     => __( 'Number of products', 'luwipress-sapphire' ),
			'type'      => Controls_Manager::NUMBER,
			'default'   => 8,
			'min'       => 1,
			'max'       => 24,
			'condition' => [ 'source!' => 'manual' ],
		] );

		$this->add_control( 'orderby', [
			'label'     => __( 'Order by', 'luwipress-sapphire' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'popularity',
			'options'   => [
				'popularity' => __( 'Popularity (sales count)', 'luwipress-sapphire' ),
				'rating'     => __( 'Average rating', 'luwipress-sapphire' ),
				'date'       => __( 'Newest first', 'luwipress-sapphire' ),
				'price'      => __( 'Price (low → high)', 'luwipress-sapphire' ),
				'price-desc' => __( 'Price (high → low)', 'luwipress-sapphire' ),
				'title'      => __( 'Title A → Z', 'luwipress-sapphire' ),
				'menu_order' => __( 'Menu order', 'luwipress-sapphire' ),
				'rand'       => __( 'Random', 'luwipress-sapphire' ),
			],
			'condition' => [ 'source' => 'query' ],
		] );

		$this->add_control( 'category', [
			'label'       => __( 'Limit to category (slug, optional)', 'luwipress-sapphire' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => '',
			'placeholder' => 'darbuka',
			'condition'   => [ 'source!' => 'manual' ],
			'description' => __( 'Single product_cat slug. Leave empty for all categories.', 'luwipress-sapphire' ),
		] );

		$this->add_control( 'exclude_out_of_stock', [
			'label'        => __( 'Hide out-of-stock', 'luwipress-sapphire' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
		] );

		$this->end_controls_section();

		/* ────────────── Layout ────────────── */
		$this->start_controls_section( 'section_layout', [ 'label' => __( 'Layout', 'luwipress-sapphire' ) ] );

		$this->add_responsive_control( 'columns', [
			'label'           => __( 'Columns', 'luwipress-sapphire' ),
			'type'            => Controls_Manager::NUMBER,
			'default'         => 4,
			'tablet_default'  => 3,
			'mobile_default'  => 2,
			'min'             => 1,
			'max'             => 6,
			'selectors'       => [
				'{{WRAPPER}} .lwp-product-grid__list' => 'grid-template-columns: repeat({{VALUE}}, minmax(0,1fr));',
			],
		] );

		$this->add_responsive_control( 'gap', [
			'label'      => __( 'Grid gap', 'luwipress-sapphire' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 64 ] ],
			'default'    => [ 'unit' => 'px', 'size' => 24 ],
			'selectors'  => [ '{{WRAPPER}} .lwp-product-grid__list' => 'gap: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_control( 'extra_class', [
			'label'       => __( 'Extra wrapper class', 'luwipress-sapphire' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => '',
			'placeholder' => 'tap-products',
			'description' => __( 'Preserves legacy CSS hooks when migrating from a [products] shortcode (e.g. "tap-products"). Optional.', 'luwipress-sapphire' ),
		] );

		$this->end_controls_section();

		/* ────────────── Style ────────────── */
		$this->start_controls_section( 'section_style', [ 'label' => __( 'Style', 'luwipress-sapphire' ), 'tab' => Controls_Manager::TAB_STYLE ] );

		$this->add_control( 'heading_color', [
			'label'     => __( 'Heading color', 'luwipress-sapphire' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#1A1612',
			'selectors' => [ '{{WRAPPER}} .lwp-product-grid__heading' => 'color: {{VALUE}};' ],
		] );

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'heading_typography',
			'selector' => '{{WRAPPER}} .lwp-product-grid__heading',
		] );

		$this->end_controls_section();
	}

	protected function render() {
		$s          = $this->get_settings_for_display();
		$source     = $s['source'] ?? 'query';
		$limit      = max( 1, min( 24, (int) ( $s['limit'] ?? 8 ) ) );
		$extra      = sanitize_html_class( $s['extra_class'] ?? '', '' );
		// Column count is applied through the Elementor "Columns" control (it
		// targets `.lwp-product-grid__list` via {{WRAPPER}}), NOT a class. We
		// deliberately DON'T emit WooCommerce's `columns-N` class: WC core's
		// `ul.products.columns-N` rules fight the theme's own CSS grid on mobile,
		// forcing a fixed width + negative break-out margins that pushed cards
		// past the left/right viewport edges. Dropping the class lets the
		// responsive `__list` grid (4→3→2 cols) and gutters behave correctly.
		$wrap_class = trim( 'lwp-product-grid products' . ( $extra ? ' ' . $extra : '' ) );

		$products = $this->query_products( $source, $s, $limit );
		if ( empty( $products ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="lwp-product-grid lwp-product-grid--empty"><em>' . esc_html__( '[Product Grid] No products match the current query.', 'luwipress-sapphire' ) . '</em></div>';
			}
			return;
		}

		echo '<div class="lwp-product-grid">';
		if ( ! empty( $s['heading'] ) ) {
			echo '<h2 class="lwp-product-grid__heading">' . esc_html( $s['heading'] ) . '</h2>';
		}
		echo '<ul class="' . esc_attr( $wrap_class ) . ' lwp-product-grid__list">';
		foreach ( $products as $p ) {
			$this->render_product_card( $p );
		}
		echo '</ul>';
		echo '</div>';
	}

	private function query_products( $source, $s, $limit ) {
		$exclude_oos = ( ( $s['exclude_out_of_stock'] ?? '' ) === 'yes' );

		if ( 'manual' === $source ) {
			$ids = array_values( array_filter( array_map( 'absint', preg_split( '/[\s,]+/', (string) ( $s['manual_ids'] ?? '' ) ) ) ) );
			if ( empty( $ids ) ) { return []; }
			$args = [
				'limit'        => count( $ids ),
				'include'      => $ids,
				'orderby'      => 'post__in',
				'status'       => 'publish',
				'stock_status' => $exclude_oos ? 'instock' : '',
			];
			if ( ! $exclude_oos ) { unset( $args['stock_status'] ); }
			return wc_get_products( $args );
		}

		// orderby normalization for wc_get_products (some keys differ from WC shortcode names)
		$orderby = $s['orderby'] ?? 'popularity';
		$order   = 'DESC';
		switch ( $orderby ) {
			case 'price':       $orderby = 'price';     $order = 'ASC';  break;
			case 'price-desc':  $orderby = 'price';     $order = 'DESC'; break;
			case 'title':       $orderby = 'title';     $order = 'ASC';  break;
			case 'date':        $orderby = 'date';      $order = 'DESC'; break;
			case 'popularity':  $orderby = 'popularity';$order = 'DESC'; break;
			case 'rating':      $orderby = 'rating';    $order = 'DESC'; break;
			case 'menu_order':  $orderby = 'menu_order';$order = 'ASC';  break;
			case 'rand':        $orderby = 'rand';      $order = 'DESC'; break;
		}

		$args = [
			'limit'   => $limit,
			'status'  => 'publish',
			'orderby' => $orderby,
			'order'   => $order,
		];

		if ( 'featured' === $source ) {
			$args['featured'] = true;
		}

		if ( ! empty( $s['category'] ) ) {
			$args['category'] = [ sanitize_title( $s['category'] ) ];
		}

		if ( $exclude_oos ) {
			$args['stock_status'] = 'instock';
		}

		return wc_get_products( $args );
	}

	private function render_product_card( \WC_Product $p ) {
		$link   = $p->get_permalink();
		$name   = $p->get_name();
		$price  = $p->get_price_html();
		$img    = $p->get_image( 'woocommerce_thumbnail' );
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
			<?php
			/**
			 * Loop add-to-cart — rendered OUTSIDE the card anchor so we don't
			 * nest interactive elements (invalid HTML + a11y trap). WC's
			 * woocommerce_template_loop_add_to_cart() reads the GLOBAL $product,
			 * so we set it around the call and restore it after (the widget runs
			 * outside the WC loop, where the global isn't populated for us).
			 *
			 * The emitted `<a class="button add_to_cart_button …">` is a sibling
			 * of `.lwp-pcard-link` inside `li.product.lwp-pcard`; the body-agnostic
			 * CTA tier in widgets.css (1.7.34+) styles it as the bold pill and
			 * bottom-aligns it. Out-of-stock / non-purchasable items get WC's
			 * "Read more" link via the same hook — no special-casing needed.
			 */
			if ( function_exists( 'woocommerce_template_loop_add_to_cart' ) ) {
				$prev_global_product = isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null;
				$GLOBALS['product']  = $p;
				woocommerce_template_loop_add_to_cart();
				$GLOBALS['product']  = $prev_global_product;
			}
			?>
		</li>
		<?php
	}
}
