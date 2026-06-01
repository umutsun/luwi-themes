<?php
/**
 * Widget: WooCommerce Product Tabs (dynamic).
 *
 * Pro-free replacement for Elementor Pro's `woocommerce-product-data-tabs`
 * widget. Reads the `woocommerce_product_tabs` filter — which accumulates
 * Description / Additional Info / Reviews + any plugin-added tabs — and
 * renders them in a tabs OR accordion layout with hash routing.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }
if ( ! class_exists( 'WooCommerce' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class LuwiPress_Gold_Widget_WC_Tabs extends Widget_Base {

	public function get_name()        { return 'lwp-wc-tabs'; }
	public function get_title()       { return __( 'WC Product Tabs', 'luwipress-gold' ); }
	public function get_icon()        { return 'eicon-product-tabs'; }
	public function get_categories()  { return [ 'luwipress-gold' ]; }
	public function get_keywords()    { return [ 'woocommerce', 'product', 'tabs', 'description', 'reviews' ]; }
	public function get_style_depends() { return [ 'luwipress-gold-widgets' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Tabs', 'luwipress-gold' ) ] );

		$this->add_control( 'layout', [
			'label'   => __( 'Layout', 'luwipress-gold' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'tabs',
			'options' => [
				'tabs'      => __( 'Horizontal tabs', 'luwipress-gold' ),
				'accordion' => __( 'Accordion', 'luwipress-gold' ),
			],
		] );

		$this->add_control( 'hide_description', [
			'label' => __( 'Hide Description tab', 'luwipress-gold' ),
			'type'  => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '',
		] );
		$this->add_control( 'hide_additional', [
			'label' => __( 'Hide Additional Info tab', 'luwipress-gold' ),
			'type'  => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '',
		] );
		$this->add_control( 'hide_reviews', [
			'label' => __( 'Hide Reviews tab', 'luwipress-gold' ),
			'type'  => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '',
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_tabs_style', [ 'label' => __( 'Tabs', 'luwipress-gold' ), 'tab' => Controls_Manager::TAB_STYLE ] );

		$this->add_control( 'tab_color', [
			'label' => __( 'Inactive tab color', 'luwipress-gold' ), 'type' => Controls_Manager::COLOR, 'default' => '#8b7f6a',
			'selectors' => [ '{{WRAPPER}} .lwp-wc-tabs__tab' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'tab_active_color', [
			'label' => __( 'Active tab color', 'luwipress-gold' ), 'type' => Controls_Manager::COLOR, 'default' => '#1A1612',
			'selectors' => [ '{{WRAPPER}} .lwp-wc-tabs__tab[aria-selected="true"], {{WRAPPER}} .lwp-wc-tabs__tab.is-active' => 'color: {{VALUE}}; border-bottom-color: {{VALUE}};' ],
		] );

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name' => 'tab_typography', 'selector' => '{{WRAPPER}} .lwp-wc-tabs__tab',
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_panel_style', [ 'label' => __( 'Panel', 'luwipress-gold' ), 'tab' => Controls_Manager::TAB_STYLE ] );

		$this->add_control( 'panel_color', [
			'label' => __( 'Text color', 'luwipress-gold' ), 'type' => Controls_Manager::COLOR, 'default' => '#3a342c',
			'selectors' => [ '{{WRAPPER}} .lwp-wc-tabs__panel' => 'color: {{VALUE}};' ],
		] );

		$this->add_responsive_control( 'panel_padding', [
			'label' => __( 'Padding', 'luwipress-gold' ), 'type' => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'selectors' => [ '{{WRAPPER}} .lwp-wc-tabs__panel' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$product = $this->get_current_product();
		if ( ! $product ) {
			echo '<div class="lwp-wc-tabs lwp-wc-tabs--placeholder"><div class="lwp-wc-tabs__ph">' . esc_html__( '[Product tabs]', 'luwipress-gold' ) . '</div></div>';
			return;
		}

		$GLOBALS['product'] = $product;

		$tabs = apply_filters( 'woocommerce_product_tabs', [] );

		// Strip hidden tabs per settings.
		if ( ( $s['hide_description'] ?? '' ) === 'yes' ) { unset( $tabs['description'] ); }
		if ( ( $s['hide_additional']  ?? '' ) === 'yes' ) { unset( $tabs['additional_information'] ); }
		if ( ( $s['hide_reviews']     ?? '' ) === 'yes' ) { unset( $tabs['reviews'] ); }

		if ( empty( $tabs ) ) { return; }

		$layout = $s['layout'] ?? 'tabs';
		$uid    = 'lwp-wc-tabs-' . $this->get_id();

		echo '<div class="lwp-wc-tabs lwp-wc-tabs--' . esc_attr( $layout ) . '" id="' . esc_attr( $uid ) . '">';

		if ( $layout === 'accordion' ) {
			$this->render_accordion( $tabs, $uid );
		} else {
			$this->render_tabs( $tabs, $uid );
		}

		echo '</div>';

		$this->render_inline_script( $uid, $layout );
	}

	private function render_tabs( array $tabs, $uid ) {
		echo '<ul class="lwp-wc-tabs__tablist" role="tablist">';
		$i = 0;
		foreach ( $tabs as $key => $tab ) {
			$selected = $i === 0 ? 'true' : 'false';
			$class    = 'lwp-wc-tabs__tab' . ( $i === 0 ? ' is-active' : '' );
			echo '<li role="presentation"><button type="button" class="' . esc_attr( $class ) . '" role="tab" aria-selected="' . esc_attr( $selected ) . '" aria-controls="' . esc_attr( $uid . '-panel-' . $key ) . '" id="' . esc_attr( $uid . '-tab-' . $key ) . '" data-tab="' . esc_attr( $key ) . '">' . esc_html( $tab['title'] ?? $key ) . '</button></li>';
			$i++;
		}
		echo '</ul>';

		$i = 0;
		foreach ( $tabs as $key => $tab ) {
			$hidden = $i === 0 ? '' : ' hidden';
			echo '<div class="lwp-wc-tabs__panel' . esc_attr( $hidden ? ' is-hidden' : '' ) . '" id="' . esc_attr( $uid . '-panel-' . $key ) . '" role="tabpanel" aria-labelledby="' . esc_attr( $uid . '-tab-' . $key ) . '"' . $hidden . '>';
			$this->call_tab_callback( $tab, $key );
			echo '</div>';
			$i++;
		}
	}

	private function render_accordion( array $tabs, $uid ) {
		$i = 0;
		foreach ( $tabs as $key => $tab ) {
			$open = $i === 0 ? ' open' : '';
			echo '<details class="lwp-wc-tabs__acc"' . $open . '>';
			echo '<summary class="lwp-wc-tabs__tab" data-tab="' . esc_attr( $key ) . '">' . esc_html( $tab['title'] ?? $key ) . '</summary>';
			echo '<div class="lwp-wc-tabs__panel">';
			$this->call_tab_callback( $tab, $key );
			echo '</div>';
			echo '</details>';
			$i++;
		}
	}

	private function call_tab_callback( $tab, $key ) {
		if ( isset( $tab['callback'] ) && is_callable( $tab['callback'] ) ) {
			call_user_func( $tab['callback'], $key, $tab );
		} else {
			echo '<p>' . esc_html__( 'No content.', 'luwipress-gold' ) . '</p>';
		}
	}

	private function render_inline_script( $uid, $layout ) {
		if ( $layout === 'accordion' ) { return; } // <details> handles itself.
		?>
		<script data-no-optimize="1">
		(function(){
			var root = document.getElementById('<?php echo esc_js( $uid ); ?>');
			if (!root) return;
			var tabs = root.querySelectorAll('.lwp-wc-tabs__tab');
			var panels = root.querySelectorAll('.lwp-wc-tabs__panel');
			tabs.forEach(function(tab) {
				tab.addEventListener('click', function() {
					tabs.forEach(function(t){ t.setAttribute('aria-selected','false'); t.classList.remove('is-active'); });
					panels.forEach(function(p){ p.hidden = true; p.classList.add('is-hidden'); });
					tab.setAttribute('aria-selected','true');
					tab.classList.add('is-active');
					var target = document.getElementById(tab.getAttribute('aria-controls'));
					if (target) { target.hidden = false; target.classList.remove('is-hidden'); }
				});
			});
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
