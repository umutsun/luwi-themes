<?php
/**
 * Widget: Reading Progress.
 *
 * Sticky-top progress bar that tracks scroll-through of an article. By
 * default tracks the entire page; can target a specific element selector
 * (e.g. ".lwp-journal-article") for finer accuracy on Theme Builder
 * Single templates with chrome above/below the content.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class LuwiPress_Amber_Widget_Reading_Progress extends Widget_Base {

	public function get_name()        { return 'lwp-reading-progress'; }
	public function get_title()       { return __( 'Reading Progress', 'luwipress-amber' ); }
	public function get_icon()        { return 'eicon-progress-tracker'; }
	public function get_categories()  { return [ 'luwipress-amber' ]; }
	public function get_keywords()    { return [ 'reading', 'progress', 'scroll', 'bar' ]; }
	public function get_style_depends() { return [ 'luwipress-amber-widgets' ]; }
	public function get_script_depends() { return [ 'luwipress-amber-frontend' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_content', [ 'label' => __( 'Behavior', 'luwipress-amber' ) ] );

		$this->add_control(
			'target_selector',
			[
				'label'   => __( 'Track selector (optional)', 'luwipress-amber' ),
				'description' => __( 'CSS selector of the content area to track. Empty = whole page.', 'luwipress-amber' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '.lwp-journal-article, .entry-content, article',
			]
		);
		$this->add_control(
			'position',
			[
				'label'   => __( 'Position', 'luwipress-amber' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'top',
				'options' => [
					'top'    => __( 'Sticky top', 'luwipress-amber' ),
					'bottom' => __( 'Sticky bottom', 'luwipress-amber' ),
					'inline' => __( 'Inline (no stick)', 'luwipress-amber' ),
				],
			]
		);
		$this->add_control(
			'height',
			[
				'label'   => __( 'Bar height (px)', 'luwipress-amber' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 3,
				'min'     => 1,
				'max'     => 12,
			]
		);

		$this->end_controls_section();

		/* Style */
		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-amber' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
		$this->add_control( 'track_color', [ 'label' => __( 'Track color', 'luwipress-amber' ), 'type' => Controls_Manager::COLOR, 'default' => '#eae3d4',
			'selectors' => [ '{{WRAPPER}} .lwp-rp' => 'background: {{VALUE}};' ] ] );
		$this->add_control( 'bar_color', [ 'label' => __( 'Bar color', 'luwipress-amber' ), 'type' => Controls_Manager::COLOR, 'default' => '#9A7B3A',
			'selectors' => [ '{{WRAPPER}} .lwp-rp__bar' => 'background: {{VALUE}};' ] ] );
		$this->end_controls_section();
	}

	protected function render() {
		$s        = $this->get_settings_for_display();
		$target   = trim( (string) ( $s['target_selector'] ?? '' ) );
		$position = $s['position'] ?? 'top';
		$height   = max( 1, (int) ( $s['height'] ?? 3 ) );
		?>
		<div class="lwp-rp lwp-rp--<?php echo esc_attr( $position ); ?>"
			data-lwp-reading-progress
			data-target="<?php echo esc_attr( $target ); ?>"
			style="height: <?php echo (int) $height; ?>px;">
			<span class="lwp-rp__bar" style="height: <?php echo (int) $height; ?>px;"></span>
		</div>
		<?php
	}
}
