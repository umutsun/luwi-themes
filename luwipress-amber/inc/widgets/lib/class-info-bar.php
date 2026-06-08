<?php
/**
 * Widget: Info Bar.
 *
 * V32-style 4-column trust strip — black band, red icon circles, title + desc.
 * Operator can change the icon (any Elementor icon library) and copy.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

class LuwiPress_Amber_Widget_Info_Bar extends Widget_Base {

	public function get_name()        { return 'lwp-info-bar'; }
	public function get_title()       { return __( 'Info Bar', 'luwipress-amber' ); }
	public function get_icon()        { return 'eicon-bullet-list'; }
	public function get_categories()  { return [ 'luwipress-amber' ]; }
	public function get_keywords()    { return [ 'info', 'bar', 'trust', 'shipping', 'features', 'icons' ]; }
	public function get_style_depends() { return [ 'luwipress-amber-widgets' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Items', 'luwipress-amber' ) ] );

		$repeater = new Repeater();
		$repeater->add_control(
			'icon',
			[
				'label'   => __( 'Icon', 'luwipress-amber' ),
				'type'    => Controls_Manager::ICONS,
				'default' => [ 'value' => 'fas fa-shipping-fast', 'library' => 'fa-solid' ],
			]
		);
		$repeater->add_control(
			'title',
			[
				'label'   => __( 'Title', 'luwipress-amber' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Free shipping', 'luwipress-amber' ),
			]
		);
		$repeater->add_control(
			'desc',
			[
				'label'   => __( 'Description', 'luwipress-amber' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Worldwide · DHL Express', 'luwipress-amber' ),
			]
		);

		$this->add_control(
			'items',
			[
				'label'       => __( 'Columns', 'luwipress-amber' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => [
					[ 'title' => __( 'Free shipping', 'luwipress-amber' ),    'desc' => __( 'Worldwide · DHL Express', 'luwipress-amber' ),    'icon' => [ 'value' => 'fas fa-shipping-fast', 'library' => 'fa-solid' ] ],
					[ 'title' => __( '15-day warranty', 'luwipress-amber' ), 'desc' => __( 'Easy returns, no questions', 'luwipress-amber' ), 'icon' => [ 'value' => 'fas fa-undo-alt',     'library' => 'fa-solid' ] ],
					[ 'title' => __( 'Hand-tuned', 'luwipress-amber' ),       'desc' => __( 'Before every shipment', 'luwipress-amber' ),     'icon' => [ 'value' => 'fas fa-music',        'library' => 'fa-solid' ] ],
					[ 'title' => __( '100% secure', 'luwipress-amber' ),     'desc' => __( 'Trusted checkout', 'luwipress-amber' ),           'icon' => [ 'value' => 'fas fa-lock',         'library' => 'fa-solid' ] ],
				],
			]
		);

		$this->end_controls_section();

		/* Style */
		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-amber' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
		$this->add_control(
			'bar_bg',
			[
				'label'     => __( 'Bar background', 'luwipress-amber' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#0c0c0c',
				'selectors' => [ '{{WRAPPER}} .lwp-ib' => 'background: {{VALUE}};' ],
			]
		);
		$this->add_control(
			'icon_bg',
			[
				'label'       => __( 'Icon circle background', 'luwipress-amber' ),
				'description' => __( 'Pick a flat color. Leave empty to keep the theme default (gradient gold).', 'luwipress-amber' ),
				'type'        => Controls_Manager::COLOR,
				'default'     => '',
				'selectors'   => [ '{{WRAPPER}} .lwp-ib-icon' => 'background: {{VALUE}};' ],
			]
		);
		$this->add_control(
			'title_color',
			[
				'label'     => __( 'Title color', 'luwipress-amber' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [ '{{WRAPPER}} .lwp-ib-title' => 'color: {{VALUE}};' ],
			]
		);
		$this->add_control(
			'desc_color',
			[
				'label'     => __( 'Description color', 'luwipress-amber' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#9b9285',
				'selectors' => [ '{{WRAPPER}} .lwp-ib-desc' => 'color: {{VALUE}};' ],
			]
		);
		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		if ( empty( $s['items'] ) ) return;
		?>
		<div class="lwp-ib">
			<div class="lwp-ib-inner">
				<?php foreach ( $s['items'] as $item ) : ?>
					<div class="lwp-ib-col">
						<div class="lwp-ib-icon">
							<?php
							if ( ! empty( $item['icon']['value'] ) && class_exists( '\\Elementor\\Icons_Manager' ) ) {
								// render_icon() handles both SVG library + Font Awesome class output,
								// AND enqueues the icon library's CSS bundle as a side effect.
								\Elementor\Icons_Manager::render_icon( $item['icon'], [ 'aria-hidden' => 'true' ] );
							} elseif ( ! empty( $item['icon']['value'] ) && is_string( $item['icon']['value'] ) ) {
								echo '<i class="' . esc_attr( $item['icon']['value'] ) . '" aria-hidden="true"></i>';
							}
							?>
						</div>
						<div class="lwp-ib-text">
							<span class="lwp-ib-title"><?php echo esc_html( $item['title'] ); ?></span>
							<?php if ( ! empty( $item['desc'] ) ) : ?>
								<span class="lwp-ib-desc"><?php echo esc_html( $item['desc'] ); ?></span>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}
