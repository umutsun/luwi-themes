<?php
/**
 * Widget: Timeline.
 *
 * Year + headline + body rows for the dark-band history strip on About pages.
 * Repeater control — operator adds as many rows as the brand needs.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

class LuwiPress_Gold_Widget_Timeline extends Widget_Base {

	public function get_name()        { return 'lwp-timeline'; }
	public function get_title()       { return __( 'Timeline', 'luwipress-gold' ); }
	public function get_icon()        { return 'eicon-time-line'; }
	public function get_categories()  { return [ 'luwipress-gold' ]; }
	public function get_keywords()    { return [ 'timeline', 'history', 'years', 'milestones', 'about' ]; }
	public function get_style_depends() { return [ 'luwipress-gold-widgets' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Rows', 'luwipress-gold' ) ] );

		$this->add_control(
			'eyebrow',
			[
				'label'   => __( 'Eyebrow', 'luwipress-gold' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'A short history', 'luwipress-gold' ),
			]
		);
		$this->add_control(
			'heading',
			[
				'label'   => __( 'Heading', 'luwipress-gold' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Eleven years, four chapters.', 'luwipress-gold' ),
			]
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'year',
			[
				'label'   => __( 'Year', 'luwipress-gold' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '2014',
			]
		);
		$repeater->add_control(
			'title',
			[
				'label'   => __( 'Title', 'luwipress-gold' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Milestone title', 'luwipress-gold' ),
			]
		);
		$repeater->add_control(
			'body',
			[
				'label'   => __( 'Body', 'luwipress-gold' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 3,
				'default' => __( 'A short paragraph describing this milestone — replace from the widget editor.', 'luwipress-gold' ),
			]
		);

		$this->add_control(
			'rows',
			[
				'label'       => __( 'Timeline rows', 'luwipress-gold' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ year }}} — {{{ title }}}',
				'default'     => [
					[ 'year' => '2020', 'title' => __( 'The beginning',     'luwipress-gold' ), 'body' => __( 'Describe how the story started — replace this copy.', 'luwipress-gold' ) ],
					[ 'year' => '2022', 'title' => __( 'First milestone',   'luwipress-gold' ), 'body' => __( 'A second milestone — replace this copy.', 'luwipress-gold' ) ],
					[ 'year' => '2024', 'title' => __( 'Scaling up',         'luwipress-gold' ), 'body' => __( 'A third milestone — replace this copy.', 'luwipress-gold' ) ],
					[ 'year' => '2026', 'title' => __( 'Where we are today', 'luwipress-gold' ), 'body' => __( 'Final milestone — replace this copy.', 'luwipress-gold' ) ],
				],
			]
		);

		$this->end_controls_section();

		/* Style */
		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-gold' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
		$this->add_control(
			'background',
			[
				'label'     => __( 'Background', 'luwipress-gold' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#0c0c0c',
				'selectors' => [ '{{WRAPPER}} .lwp-tl' => 'background: {{VALUE}};' ],
			]
		);
		$this->add_control(
			'year_color',
			[
				'label'     => __( 'Year color', 'luwipress-gold' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#D4AF37',
				'selectors' => [ '{{WRAPPER}} .lwp-tl-row .yr' => 'color: {{VALUE}};' ],
			]
		);
		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		?>
		<section class="lwp-tl">
			<div class="lwp-tl-inner">
				<?php if ( ! empty( $s['eyebrow'] ) ) : ?>
					<span class="lwp-tl-eyebrow">— <?php echo esc_html( $s['eyebrow'] ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $s['heading'] ) ) : ?>
					<h2 class="lwp-tl-heading"><?php echo esc_html( $s['heading'] ); ?></h2>
				<?php endif; ?>
				<?php if ( ! empty( $s['rows'] ) ) : ?>
					<div class="lwp-tl-list">
						<?php foreach ( $s['rows'] as $row ) : ?>
							<div class="lwp-tl-row">
								<span class="yr"><?php echo esc_html( $row['year'] ); ?></span>
								<div>
									<h4><?php echo esc_html( $row['title'] ); ?></h4>
									<?php if ( ! empty( $row['body'] ) ) : ?>
										<p><?php echo esc_html( $row['body'] ); ?></p>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}
