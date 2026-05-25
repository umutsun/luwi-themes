<?php
/**
 * Widget: Shop Filters.
 *
 * Sidebar facets for WC shop / category archives. Each block has a heading
 * and a list of checkbox items with counts. Supports:
 *   - auto product_cat block (built from WC term query)
 *   - auto product_tag block
 *   - auto price-range slider (reads WC products min/max)
 *   - auto stock status block
 *   - manual blocks (repeater) for custom facets (master / origin / etc.)
 *
 * Form posts to current shop URL with ?filter[<slug>]=... query — operator
 * can wire this to WC AJAX filters or Beaver Faceting plugin via a hook.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

class LuwiPress_Gold_Widget_Shop_Filters extends Widget_Base {

	public function get_name()        { return 'lwp-shop-filters'; }
	public function get_title()       { return __( 'Shop Filters', 'luwipress-gold' ); }
	public function get_icon()        { return 'eicon-filter'; }
	public function get_categories()  { return [ 'luwipress-gold' ]; }
	public function get_keywords()    { return [ 'shop', 'filter', 'sidebar', 'facet', 'wc' ]; }
	public function get_style_depends() { return [ 'luwipress-gold-widgets' ]; }

	protected function register_controls() {

		/* Auto blocks */
		$this->start_controls_section( 'section_auto', [ 'label' => __( 'Auto blocks (WC)', 'luwipress-gold' ) ] );

		$this->add_control( 'show_category', [
			'label'        => __( 'Show category facet', 'luwipress-gold' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );
		$this->add_control( 'label_category', [
			'label'   => __( 'Category facet label', 'luwipress-gold' ),
			'type'    => Controls_Manager::TEXT,
			'default' => __( 'Family', 'luwipress-gold' ),
			'condition' => [ 'show_category' => 'yes' ],
		] );

		$this->add_control( 'show_price', [
			'label'        => __( 'Show price range', 'luwipress-gold' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );
		$this->add_control( 'label_price', [
			'label'   => __( 'Price facet label', 'luwipress-gold' ),
			'type'    => Controls_Manager::TEXT,
			'default' => __( 'Price', 'luwipress-gold' ),
			'condition' => [ 'show_price' => 'yes' ],
		] );

		$this->add_control( 'show_stock', [
			'label'        => __( 'Show availability', 'luwipress-gold' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );
		$this->add_control( 'label_stock', [
			'label'   => __( 'Availability facet label', 'luwipress-gold' ),
			'type'    => Controls_Manager::TEXT,
			'default' => __( 'Availability', 'luwipress-gold' ),
			'condition' => [ 'show_stock' => 'yes' ],
		] );

		$this->end_controls_section();

		/* Manual blocks */
		$this->start_controls_section( 'section_manual', [ 'label' => __( 'Custom blocks', 'luwipress-gold' ) ] );

		$item_rep = new Repeater();
		$item_rep->add_control( 'label', [ 'label' => __( 'Label', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT, 'default' => 'Option' ] );
		$item_rep->add_control( 'count', [ 'label' => __( 'Count', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT, 'default' => '' ] );
		$item_rep->add_control( 'value', [ 'label' => __( 'Value (URL param)', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT, 'default' => '' ] );

		$block_rep = new Repeater();
		$block_rep->add_control( 'heading', [ 'label' => __( 'Block heading', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT, 'default' => 'Master luthier' ] );
		$block_rep->add_control( 'param',   [ 'label' => __( 'Query parameter', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT, 'default' => 'master' ] );
		$block_rep->add_control(
			'items',
			[
				'label' => __( 'Items', 'luwipress-gold' ),
				'type'  => Controls_Manager::REPEATER,
				'fields'=> $item_rep->get_controls(),
				'title_field' => '{{{ label || "Item" }}}',
				'default' => [],
			]
		);

		$this->add_control(
			'custom_blocks',
			[
				'label'       => __( 'Custom filter blocks', 'luwipress-gold' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $block_rep->get_controls(),
				'title_field' => '{{{ heading || "Block" }}}',
				'default'     => [],
			]
		);

		$this->add_control(
			'apply_label',
			[ 'label' => __( 'Apply button label', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Apply filters', 'luwipress-gold' ) ]
		);
		$this->add_control(
			'clear_label',
			[ 'label' => __( 'Clear button label', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Clear all', 'luwipress-gold' ) ]
		);

		$this->end_controls_section();

		/* Style */
		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-gold' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
		$this->add_control( 'heading_color', [ 'label' => __( 'Block heading color', 'luwipress-gold' ), 'type' => Controls_Manager::COLOR, 'default' => '#1A1612',
			'selectors' => [ '{{WRAPPER}} .lwp-sf__heading' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'item_color', [ 'label' => __( 'Item color', 'luwipress-gold' ), 'type' => Controls_Manager::COLOR, 'default' => '#3a342c',
			'selectors' => [ '{{WRAPPER}} .lwp-sf__item' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'count_color', [ 'label' => __( 'Count color', 'luwipress-gold' ), 'type' => Controls_Manager::COLOR, 'default' => '#8b7f6a',
			'selectors' => [ '{{WRAPPER}} .lwp-sf__count' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'accent_color', [ 'label' => __( 'Accent', 'luwipress-gold' ), 'type' => Controls_Manager::COLOR, 'default' => '#1A1612',
			'selectors' => [
				'{{WRAPPER}} .lwp-sf__apply' => 'background: {{VALUE}}; border-color: {{VALUE}};',
				'{{WRAPPER}} .lwp-sf input[type=checkbox]:checked' => 'accent-color: {{VALUE}};',
			] ] );
		$this->end_controls_section();
	}

	private function wc_active() {
		return function_exists( 'WC' ) && class_exists( 'WooCommerce' );
	}

	private function category_terms() {
		if ( ! $this->wc_active() || ! function_exists( 'get_terms' ) ) return [];
		$terms = get_terms( [
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'parent'     => 0,
			'number'     => 12,
		] );
		if ( is_wp_error( $terms ) || empty( $terms ) ) return [];
		return $terms;
	}

	private function price_range() {
		global $wpdb;
		if ( ! $this->wc_active() ) return [ 0, 1000 ];
		$cached = wp_cache_get( 'lwp_sf_price_range', 'luwipress' );
		if ( false !== $cached && is_array( $cached ) ) return $cached;
		$range = $wpdb->get_row(
			"SELECT MIN(CAST(meta_value AS DECIMAL(10,2))) AS min_p,
			        MAX(CAST(meta_value AS DECIMAL(10,2))) AS max_p
			 FROM {$wpdb->postmeta}
			 WHERE meta_key = '_price'",
			ARRAY_A
		);
		$out = [ (int) ( $range['min_p'] ?? 0 ), (int) ceil( $range['max_p'] ?? 1000 ) ];
		wp_cache_set( 'lwp_sf_price_range', $out, 'luwipress', HOUR_IN_SECONDS );
		return $out;
	}

	private function get_active_params() {
		$active = [];
		if ( isset( $_GET['filter'] ) && is_array( $_GET['filter'] ) ) {
			foreach ( $_GET['filter'] as $k => $v ) {
				$active[ sanitize_key( $k ) ] = is_array( $v ) ? array_map( 'sanitize_text_field', $v ) : [ sanitize_text_field( $v ) ];
			}
		}
		return $active;
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$show_cat   = ( $s['show_category'] ?? 'yes' ) === 'yes' && $this->wc_active();
		$show_price = ( $s['show_price']    ?? 'yes' ) === 'yes' && $this->wc_active();
		$show_stock = ( $s['show_stock']    ?? 'yes' ) === 'yes' && $this->wc_active();
		$custom     = is_array( $s['custom_blocks'] ?? null ) ? $s['custom_blocks'] : [];
		$apply_lbl  = trim( (string) ( $s['apply_label'] ?? 'Apply' ) );
		$clear_lbl  = trim( (string) ( $s['clear_label'] ?? 'Clear' ) );

		$active = $this->get_active_params();

		// Build form action — current shop URL
		$action = remove_query_arg( [ 'filter' ] );
		?>
		<aside class="lwp-sf">
			<form class="lwp-sf__form" method="get" action="<?php echo esc_url( $action ); ?>">
				<?php
				// Preserve non-filter GET params as hidden inputs
				foreach ( $_GET as $k => $v ) {
					if ( $k === 'filter' ) continue;
					if ( is_array( $v ) ) continue;
					printf( '<input type="hidden" name="%s" value="%s">', esc_attr( $k ), esc_attr( $v ) );
				}
				?>

				<?php /* CATEGORY BLOCK */
				if ( $show_cat ) :
					$terms = $this->category_terms();
					if ( $terms ) :
						$active_cats = $active['product_cat'] ?? [];
						?>
						<div class="lwp-sf__block">
							<h5 class="lwp-sf__heading"><?php echo esc_html( $s['label_category'] ); ?></h5>
							<?php foreach ( $terms as $t ) :
								$checked = in_array( $t->slug, $active_cats, true );
								?>
								<label class="lwp-sf__item">
									<input type="checkbox" name="filter[product_cat][]" value="<?php echo esc_attr( $t->slug ); ?>"<?php echo $checked ? ' checked' : ''; ?>>
									<span><?php echo esc_html( $t->name ); ?></span>
									<span class="lwp-sf__count"><?php echo esc_html( $t->count ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
				<?php endif; endif; ?>

				<?php /* CUSTOM BLOCKS */
				foreach ( $custom as $b ) :
					$heading = trim( (string) ( $b['heading'] ?? '' ) );
					$param   = sanitize_key( $b['param'] ?? '' );
					$items   = is_array( $b['items'] ?? null ) ? $b['items'] : [];
					if ( $heading === '' || $param === '' || empty( $items ) ) continue;
					$active_param = $active[ $param ] ?? [];
					?>
					<div class="lwp-sf__block">
						<h5 class="lwp-sf__heading"><?php echo esc_html( $heading ); ?></h5>
						<?php foreach ( $items as $it ) :
							$lbl   = trim( (string) ( $it['label'] ?? '' ) );
							$cnt   = trim( (string) ( $it['count'] ?? '' ) );
							$val   = trim( (string) ( $it['value'] ?? sanitize_title( $lbl ) ) );
							if ( $lbl === '' ) continue;
							$checked = in_array( $val, $active_param, true );
							?>
							<label class="lwp-sf__item">
								<input type="checkbox" name="filter[<?php echo esc_attr( $param ); ?>][]" value="<?php echo esc_attr( $val ); ?>"<?php echo $checked ? ' checked' : ''; ?>>
								<span><?php echo esc_html( $lbl ); ?></span>
								<?php if ( $cnt ) : ?><span class="lwp-sf__count"><?php echo esc_html( $cnt ); ?></span><?php endif; ?>
							</label>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>

				<?php /* PRICE BLOCK */
				if ( $show_price ) :
					[ $min_p, $max_p ] = $this->price_range();
					$cur_max = isset( $active['price_max'][0] ) ? (int) $active['price_max'][0] : $max_p;
					$symbol  = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$';
					?>
					<div class="lwp-sf__block">
						<h5 class="lwp-sf__heading"><?php echo esc_html( $s['label_price'] ); ?></h5>
						<div class="lwp-sf__price-range">
							<span class="lwp-sf__price-min"><?php echo esc_html( $symbol . $min_p ); ?></span>
							<input type="range" name="filter[price_max][]" min="<?php echo esc_attr( $min_p ); ?>" max="<?php echo esc_attr( $max_p ); ?>" value="<?php echo esc_attr( $cur_max ); ?>" oninput="this.nextElementSibling.textContent='<?php echo esc_attr( $symbol ); ?>' + this.value">
							<span class="lwp-sf__price-max"><?php echo esc_html( $symbol . $cur_max ); ?></span>
						</div>
					</div>
				<?php endif; ?>

				<?php /* STOCK BLOCK */
				if ( $show_stock ) :
					$active_stock = $active['stock'] ?? [];
					$opts = [
						'instock'    => __( 'In stock', 'luwipress-gold' ),
						'onbackorder'=> __( 'Pre-order', 'luwipress-gold' ),
						'outofstock' => __( 'Made to order', 'luwipress-gold' ),
					];
					?>
					<div class="lwp-sf__block">
						<h5 class="lwp-sf__heading"><?php echo esc_html( $s['label_stock'] ); ?></h5>
						<?php foreach ( $opts as $key => $label ) :
							$checked = in_array( $key, $active_stock, true );
							?>
							<label class="lwp-sf__item">
								<input type="checkbox" name="filter[stock][]" value="<?php echo esc_attr( $key ); ?>"<?php echo $checked ? ' checked' : ''; ?>>
								<span><?php echo esc_html( $label ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<div class="lwp-sf__actions">
					<button type="submit" class="lwp-sf__apply"><?php echo esc_html( $apply_lbl ); ?></button>
					<a class="lwp-sf__clear" href="<?php echo esc_url( $action ); ?>"><?php echo esc_html( $clear_lbl ); ?></a>
				</div>
			</form>
		</aside>
		<?php
	}
}
