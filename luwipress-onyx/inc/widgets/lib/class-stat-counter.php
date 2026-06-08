<?php
/**
 * Widget: Stat Counter.
 *
 * Big numbers that count up when they scroll into view. Each stat has
 * a number (with optional suffix like "+" or "k"), a label, and an
 * optional small description. Uses IntersectionObserver via theme JS.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

class LuwiPress_Onyx_Widget_Stat_Counter extends Widget_Base {

	public function get_name()        { return 'lwp-stat-counter'; }
	public function get_title()       { return __( 'Stat Counter', 'luwipress-onyx' ); }
	public function get_icon()        { return 'eicon-counter'; }
	public function get_categories()  { return [ 'luwipress-onyx' ]; }
	public function get_keywords()    { return [ 'stats', 'counter', 'numbers', 'metrics', 'count-up' ]; }
	public function get_style_depends() { return [ 'luwipress-onyx-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_items', [ 'label' => __( 'Stats', 'luwipress-onyx' ) ] );

		$rep = new Repeater();
		$rep->add_control( 'number', [ 'label' => __( 'Number', 'luwipress-onyx' ), 'type' => Controls_Manager::NUMBER, 'default' => 240 ] );
		$rep->add_control( 'prefix', [ 'label' => __( 'Prefix (e.g. €)', 'luwipress-onyx' ), 'type' => Controls_Manager::TEXT, 'default' => '' ] );
		$rep->add_control( 'suffix', [ 'label' => __( 'Suffix (e.g. + or k)', 'luwipress-onyx' ), 'type' => Controls_Manager::TEXT, 'default' => '+' ] );
		$rep->add_control( 'label',  [ 'label' => __( 'Label', 'luwipress-onyx' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Products in catalogue', 'luwipress-onyx' ) ] );
		$rep->add_control( 'sub',    [ 'label' => __( 'Sub line (optional)', 'luwipress-onyx' ), 'type' => Controls_Manager::TEXT, 'default' => '' ] );
		$rep->add_control( 'icon',   [ 'label' => __( 'Icon (optional)', 'luwipress-onyx' ), 'type' => Controls_Manager::ICONS, 'default' => [ 'value' => '', 'library' => '' ] ] );

		$this->add_control( 'items', [
			'label'       => __( 'Items', 'luwipress-onyx' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $rep->get_controls(),
			'title_field' => '{{{ label || "Stat" }}}',
			'default'     => [
				[ 'number' => 100, 'suffix' => '+',   'label' => __( 'Products', 'luwipress-onyx' ) ],
				[ 'number' => 10,  'suffix' => '',    'label' => __( 'Countries shipped', 'luwipress-onyx' ) ],
				[ 'number' => 5,   'suffix' => '',    'label' => __( 'Team members', 'luwipress-onyx' ) ],
				[ 'number' => 3,   'suffix' => ' yrs', 'label' => __( 'In business', 'luwipress-onyx' ) ],
			],
		] );

		$this->add_control( 'columns', [
			'label'   => __( 'Columns (desktop)', 'luwipress-onyx' ),
			'type'    => Controls_Manager::SELECT,
			'default' => '4',
			'options' => [ '2' => '2', '3' => '3', '4' => '4', '6' => '6' ],
		] );

		$this->add_control( 'duration', [
			'label'   => __( 'Count-up duration (ms)', 'luwipress-onyx' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 1800,
			'min'     => 300,
			'max'     => 6000,
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_style', [ 'label' => __( 'Style', 'luwipress-onyx' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'variant', [
			'label'   => __( 'Variant', 'luwipress-onyx' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'light',
			'options' => [ 'light' => __( 'Light', 'luwipress-onyx' ), 'dark' => __( 'Dark', 'luwipress-onyx' ) ],
		] );
		$this->add_control( 'show_dividers', [
			'label'        => __( 'Vertical dividers', 'luwipress-onyx' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );
		$this->end_controls_section();
	}

	protected function render() {
		$s        = $this->get_settings_for_display();
		$items    = is_array( $s['items'] ?? null ) ? $s['items'] : [];
		if ( empty( $items ) ) { return; }
		$columns  = in_array( $s['columns'] ?? '4', [ '2', '3', '4', '6' ], true ) ? $s['columns'] : '4';
		$duration = max( 300, min( 6000, (int) ( $s['duration'] ?? 1800 ) ) );
		$variant  = ( $s['variant'] ?? 'light' ) === 'dark' ? 'dark' : 'light';
		$div      = ( $s['show_dividers'] ?? 'yes' ) === 'yes';
		?>
		<div class="lwp-sc lwp-sc--<?php echo esc_attr( $variant ); ?> <?php echo $div ? 'lwp-sc--dividers' : ''; ?>"
			data-columns="<?php echo esc_attr( $columns ); ?>"
			data-duration="<?php echo esc_attr( $duration ); ?>">
			<?php foreach ( $items as $it ) :
				$num    = (int) ( $it['number'] ?? 0 );
				$prefix = (string) ( $it['prefix'] ?? '' );
				$suffix = (string) ( $it['suffix'] ?? '' );
				$label  = trim( (string) ( $it['label'] ?? '' ) );
				$sub    = trim( (string) ( $it['sub'] ?? '' ) );
				$icon   = $it['icon'] ?? [];
				?>
				<div class="lwp-sc__item">
					<?php if ( ! empty( $icon['value'] ) ) : ?>
						<div class="lwp-sc__icon" aria-hidden="true"><?php \Elementor\Icons_Manager::render_icon( $icon, [ 'aria-hidden' => 'true' ] ); ?></div>
					<?php endif; ?>
					<div class="lwp-sc__num" data-target="<?php echo esc_attr( $num ); ?>">
						<?php if ( $prefix ) : ?><span class="lwp-sc__prefix"><?php echo esc_html( $prefix ); ?></span><?php endif; ?>
						<span class="lwp-sc__val">0</span>
						<?php if ( $suffix ) : ?><span class="lwp-sc__suffix"><?php echo esc_html( $suffix ); ?></span><?php endif; ?>
					</div>
					<?php if ( $label ) : ?><span class="lwp-sc__lbl"><?php echo esc_html( $label ); ?></span><?php endif; ?>
					<?php if ( $sub ) : ?><span class="lwp-sc__sub"><?php echo esc_html( $sub ); ?></span><?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
