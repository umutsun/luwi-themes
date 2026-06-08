<?php
/**
 * Widget: Spec List.
 *
 * Definition-list-style spec table for product detail pages.
 * Each row = label + value (e.g. "Bowl: 23-rib walnut").
 *
 * Supports auto-pull from WC product attributes when "source = attributes".
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

class LuwiPress_Amber_Widget_Spec_List extends Widget_Base {

	public function get_name()        { return 'lwp-spec-list'; }
	public function get_title()       { return __( 'Spec List', 'luwipress-amber' ); }
	public function get_icon()        { return 'eicon-table-of-contents'; }
	public function get_categories()  { return [ 'luwipress-amber' ]; }
	public function get_keywords()    { return [ 'spec', 'list', 'attributes', 'product', 'details' ]; }
	public function get_style_depends() { return [ 'luwipress-amber-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_content', [ 'label' => __( 'Specs', 'luwipress-amber' ) ] );

		$this->add_control(
			'source',
			[
				'label'   => __( 'Source', 'luwipress-amber' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'manual',
				'options' => [
					'manual'     => __( 'Manual list', 'luwipress-amber' ),
					'attributes' => __( 'Auto: current product attributes (WC)', 'luwipress-amber' ),
				],
			]
		);

		$rep = new Repeater();
		$rep->add_control( 'label', [ 'label' => __( 'Label', 'luwipress-amber' ), 'type' => Controls_Manager::TEXT, 'default' => 'Material' ] );
		$rep->add_control( 'value', [ 'label' => __( 'Value', 'luwipress-amber' ), 'type' => Controls_Manager::TEXT, 'default' => 'Walnut' ] );

		$this->add_control(
			'rows',
			[
				'label'       => __( 'Rows', 'luwipress-amber' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $rep->get_controls(),
				'title_field' => '{{{ label || "Row" }}}',
				'default'     => [
					[ 'label' => __( 'Material', 'luwipress-amber' ), 'value' => 'Walnut' ],
					[ 'label' => __( 'Weight', 'luwipress-amber' ),   'value' => '1.4 kg' ],
				],
				'condition' => [ 'source' => 'manual' ],
			]
		);

		$this->add_control(
			'columns',
			[
				'label'   => __( 'Columns', 'luwipress-amber' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '1',
				'options' => [ '1' => __( 'Single column', 'luwipress-amber' ), '2' => __( 'Two columns', 'luwipress-amber' ) ],
			]
		);

		$this->end_controls_section();

		/* Style */
		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-amber' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
		$this->add_control( 'label_color', [ 'label' => __( 'Label color', 'luwipress-amber' ), 'type' => Controls_Manager::COLOR, 'default' => '#8b7f6a',
			'selectors' => [ '{{WRAPPER}} .lwp-spec dt' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'value_color', [ 'label' => __( 'Value color', 'luwipress-amber' ), 'type' => Controls_Manager::COLOR, 'default' => '#1A1612',
			'selectors' => [ '{{WRAPPER}} .lwp-spec dd' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'border_color', [ 'label' => __( 'Row border', 'luwipress-amber' ), 'type' => Controls_Manager::COLOR, 'default' => '#eae3d4',
			'selectors' => [ '{{WRAPPER}} .lwp-spec__row' => 'border-bottom-color: {{VALUE}};' ] ] );
		$this->end_controls_section();
	}

	private function product_attributes() {
		if ( ! function_exists( 'wc_get_product' ) || ! is_singular( 'product' ) ) return [];
		$product = wc_get_product( get_the_ID() );
		if ( ! $product || ! method_exists( $product, 'get_attributes' ) ) return [];
		$out = [];
		foreach ( $product->get_attributes() as $attr ) {
			$label = method_exists( $attr, 'get_name' ) ? wc_attribute_label( $attr->get_name() ) : '';
			$values = [];
			if ( method_exists( $attr, 'get_terms' ) ) {
				$terms = $attr->get_terms();
				if ( is_array( $terms ) ) {
					foreach ( $terms as $t ) { $values[] = $t->name; }
				}
			}
			if ( empty( $values ) && method_exists( $attr, 'get_options' ) ) {
				$values = (array) $attr->get_options();
			}
			if ( empty( $values ) || $label === '' ) continue;
			$out[] = [ 'label' => $label, 'value' => implode( ', ', $values ) ];
		}
		return $out;
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$source = $s['source'] ?? 'manual';
		$cols   = ( $s['columns'] ?? '1' ) === '2' ? 2 : 1;

		$rows = $source === 'attributes' ? $this->product_attributes() : ( is_array( $s['rows'] ?? null ) ? $s['rows'] : [] );
		if ( empty( $rows ) ) return;
		?>
		<dl class="lwp-spec" data-cols="<?php echo esc_attr( $cols ); ?>">
			<?php foreach ( $rows as $r ) :
				$lbl = trim( (string) ( $r['label'] ?? '' ) );
				$val = trim( (string) ( $r['value'] ?? '' ) );
				if ( $lbl === '' && $val === '' ) continue;
				?>
				<div class="lwp-spec__row">
					<dt><?php echo esc_html( $lbl ); ?></dt>
					<dd><?php echo esc_html( $val ); ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>
		<?php
	}
}
