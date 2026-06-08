<?php
/**
 * Widget: Shop Toolbar.
 *
 * Result count + active-filters chip + view toggle (grid/list) + sort dropdown.
 * Auto-reads WC archive query for live result count + sort options. Renders
 * a deterministic sort form that posts to the same archive URL with ?orderby=.
 *
 * View-toggle JS swaps the `.products` container's `lwp-shop--view-grid` /
 * `--view-list` classes (persisted in localStorage).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class LuwiPress_Onyx_Widget_Shop_Toolbar extends Widget_Base {

	public function get_name()        { return 'lwp-shop-toolbar'; }
	public function get_title()       { return __( 'Shop Toolbar', 'luwipress-onyx' ); }
	public function get_icon()        { return 'eicon-tabs-horizontal'; }
	public function get_categories()  { return [ 'luwipress-onyx' ]; }
	public function get_keywords()    { return [ 'shop', 'toolbar', 'sort', 'view', 'wc' ]; }
	public function get_style_depends() { return [ 'luwipress-onyx-widgets' ]; }
	public function get_script_depends() { return [ 'luwipress-onyx-frontend' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_content', [ 'label' => __( 'Toolbar', 'luwipress-onyx' ) ] );

		$this->add_control( 'show_count',  [ 'label' => __( 'Show result count', 'luwipress-onyx' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ] );
		$this->add_control(
			'count_template',
			[
				'label'   => __( 'Count template', 'luwipress-onyx' ),
				'description' => __( 'Use %d for number, %s for filter text.', 'luwipress-onyx' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( '%d results', 'luwipress-onyx' ),
				'condition' => [ 'show_count' => 'yes' ],
			]
		);

		$this->add_control( 'show_view_toggle', [ 'label' => __( 'Show grid/list toggle', 'luwipress-onyx' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ] );
		$this->add_control( 'show_sort',         [ 'label' => __( 'Show sort dropdown', 'luwipress-onyx' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ] );

		$this->end_controls_section();

		/* Style */
		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-onyx' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
		$this->add_control( 'text_color', [ 'label' => __( 'Text color', 'luwipress-onyx' ), 'type' => Controls_Manager::COLOR, 'default' => '#3a342c',
			'selectors' => [ '{{WRAPPER}} .lwp-stb' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'count_strong', [ 'label' => __( 'Count strong color', 'luwipress-onyx' ), 'type' => Controls_Manager::COLOR, 'default' => '#1A1612',
			'selectors' => [ '{{WRAPPER}} .lwp-stb__count strong' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'active_bg', [ 'label' => __( 'Toggle active bg', 'luwipress-onyx' ), 'type' => Controls_Manager::COLOR, 'default' => '#1A1612',
			'selectors' => [ '{{WRAPPER}} .lwp-stb__view-btn.is-on' => 'background: {{VALUE}}; color: #fff;' ] ] );
		$this->end_controls_section();
	}

	private function get_wc_sort_options() {
		// Mirror WC's standard catalog ordering options
		$options = [
			'menu_order' => __( 'Default sorting', 'luwipress-onyx' ),
			'popularity' => __( 'Sort by popularity', 'luwipress-onyx' ),
			'rating'     => __( 'Sort by average rating', 'luwipress-onyx' ),
			'date'       => __( 'Sort by latest', 'luwipress-onyx' ),
			'price'      => __( 'Sort by price: low to high', 'luwipress-onyx' ),
			'price-desc' => __( 'Sort by price: high to low', 'luwipress-onyx' ),
		];
		if ( function_exists( 'apply_filters' ) ) {
			return apply_filters( 'woocommerce_catalog_orderby', $options );
		}
		return $options;
	}

	private function current_query_count() {
		global $wp_query;
		// On shop / category archives this is product count
		if ( $wp_query && isset( $wp_query->found_posts ) ) {
			return (int) $wp_query->found_posts;
		}
		return 0;
	}

	private function active_filters_summary() {
		if ( empty( $_GET['filter'] ) || ! is_array( $_GET['filter'] ) ) return '';
		$parts = [];
		foreach ( $_GET['filter'] as $param => $values ) {
			if ( ! is_array( $values ) ) $values = [ $values ];
			$values = array_filter( array_map( 'sanitize_text_field', $values ) );
			if ( empty( $values ) ) continue;
			$parts[] = ucfirst( str_replace( [ 'product_cat', 'product_tag', '_' ], [ 'category', 'tag', ' ' ], $param ) ) . ': ' . implode( ', ', $values );
		}
		return $parts ? implode( ' · ', $parts ) : '';
	}

	protected function render() {
		$s          = $this->get_settings_for_display();
		$show_count = ( $s['show_count'] ?? 'yes' ) === 'yes';
		$show_view  = ( $s['show_view_toggle'] ?? 'yes' ) === 'yes';
		$show_sort  = ( $s['show_sort'] ?? 'yes' ) === 'yes';
		$count_tpl  = $s['count_template'] ?? '%d results';

		$count   = $this->current_query_count();
		$filter  = $this->active_filters_summary();
		$current = sanitize_text_field( $_GET['orderby'] ?? 'menu_order' );
		$opts    = $this->get_wc_sort_options();
		?>
		<div class="lwp-stb">
			<?php if ( $show_count ) : ?>
				<div class="lwp-stb__count">
					<strong><?php echo esc_html( sprintf( $count_tpl, $count ) ); ?></strong>
					<?php if ( $filter ) : ?><span class="lwp-stb__filter">· <?php echo esc_html( $filter ); ?></span><?php endif; ?>
				</div>
			<?php endif; ?>
			<div class="lwp-stb__tools">
				<?php if ( $show_view ) : ?>
					<div class="lwp-stb__view" role="group" aria-label="<?php esc_attr_e( 'View mode', 'luwipress-onyx' ); ?>">
						<button type="button" class="lwp-stb__view-btn is-on" data-lwp-view="grid" aria-label="<?php esc_attr_e( 'Grid view', 'luwipress-onyx' ); ?>">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="7" x="3" y="3"/><rect width="7" height="7" x="14" y="3"/><rect width="7" height="7" x="3" y="14"/><rect width="7" height="7" x="14" y="14"/></svg>
						</button>
						<button type="button" class="lwp-stb__view-btn" data-lwp-view="list" aria-label="<?php esc_attr_e( 'List view', 'luwipress-onyx' ); ?>">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
						</button>
					</div>
				<?php endif; ?>
				<?php if ( $show_sort ) : ?>
					<form class="lwp-stb__sort-form" method="get">
						<?php
						foreach ( $_GET as $k => $v ) {
							if ( $k === 'orderby' || $k === 'paged' ) continue;
							if ( is_array( $v ) ) {
								foreach ( $v as $kk => $vv ) {
									if ( is_array( $vv ) ) {
										foreach ( $vv as $vvv ) {
											printf( '<input type="hidden" name="%s[%s][]" value="%s">', esc_attr( $k ), esc_attr( $kk ), esc_attr( $vvv ) );
										}
									} else {
										printf( '<input type="hidden" name="%s[%s]" value="%s">', esc_attr( $k ), esc_attr( $kk ), esc_attr( $vv ) );
									}
								}
							} else {
								printf( '<input type="hidden" name="%s" value="%s">', esc_attr( $k ), esc_attr( $v ) );
							}
						}
						?>
						<select class="lwp-stb__sort" name="orderby" onchange="this.form.submit()">
							<?php foreach ( $opts as $val => $label ) : ?>
								<option value="<?php echo esc_attr( $val ); ?>"<?php selected( $val, $current ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</form>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
