<?php
/**
 * Elementor widget — Tour Toolbar.
 *
 * The `.listing-top` strip above a Tour Grid: live result count + sort select
 * (+ a mobile "Filters" toggle). Bound by assets/js/tours.js to the grid +
 * filters of the same data-tours-group.
 *
 * @package luwipress-amber
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class LuwiPress_Amber_Widget_Tour_Toolbar extends Widget_Base {

	public function get_name() { return 'lwp-tour-toolbar'; }
	public function get_title() { return __( 'Tour Toolbar', 'luwipress-amber' ); }
	public function get_icon() { return 'eicon-toggle'; }
	public function get_categories() { return [ 'luwipress-amber' ]; }
	public function get_keywords() { return [ 'tour', 'sort', 'toolbar', 'count', 'travel' ]; }
	public function get_style_depends() { return [ 'luwipress-amber-widgets' ]; }
	public function get_script_depends() { return [ 'luwipress-amber-tours' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'content', [ 'label' => __( 'Toolbar', 'luwipress-amber' ) ] );
		$this->add_control( 'count_suffix', [ 'label' => __( 'Count suffix', 'luwipress-amber' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'experiences found', 'luwipress-amber' ) ] );
		$this->add_control( 'sort_label', [ 'label' => __( 'Sort label', 'luwipress-amber' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Sort by', 'luwipress-amber' ) ] );
		$this->add_control( 'show_mobile_toggle', [ 'label' => __( 'Mobile filter toggle', 'luwipress-amber' ), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->add_control( 'group', [
			'label'       => __( 'Filter group', 'luwipress-amber' ),
			'description' => __( 'Must match the Tour Grid + Filters.', 'luwipress-amber' ),
			'type'        => Controls_Manager::TEXT,
		] );
		$this->end_controls_section();
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$group = isset( $s['group'] ) ? sanitize_title( $s['group'] ) : '';
		?>
		<div class="listing-top" data-tours-group="<?php echo esc_attr( $group ); ?>">
			<?php if ( 'yes' === ( $s['show_mobile_toggle'] ?? '' ) ) : ?>
				<button type="button" class="filter-toggle"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M6 12h12M10 18h4"/></svg> <?php esc_html_e( 'Filters', 'luwipress-amber' ); ?></button>
			<?php endif; ?>
			<div class="result-count"><b class="res-count">0</b> <?php echo esc_html( $s['count_suffix'] ); ?></div>
			<div class="sort-wrap">
				<label><?php echo esc_html( $s['sort_label'] ); ?></label>
				<select class="sort-select">
					<option value="pop"><?php esc_html_e( 'Most popular', 'luwipress-amber' ); ?></option>
					<option value="price-asc"><?php esc_html_e( 'Price: low to high', 'luwipress-amber' ); ?></option>
					<option value="price-desc"><?php esc_html_e( 'Price: high to low', 'luwipress-amber' ); ?></option>
					<option value="rating"><?php esc_html_e( 'Top rated', 'luwipress-amber' ); ?></option>
				</select>
			</div>
		</div>
		<?php
	}
}
