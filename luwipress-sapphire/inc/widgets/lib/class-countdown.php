<?php
/**
 * Widget: Countdown Timer.
 *
 * Sale / launch countdown with day / hour / minute / second cells.
 * Server emits the target timestamp in UTC; client-side JS does the
 * tick + locale-aware label rendering. When expired, swaps to a
 * configurable post-expiry message.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class LuwiPress_Sapphire_Widget_Countdown extends Widget_Base {

	public function get_name()        { return 'lwp-countdown'; }
	public function get_title()       { return __( 'Countdown Timer', 'luwipress-sapphire' ); }
	public function get_icon()        { return 'eicon-countdown'; }
	public function get_categories()  { return [ 'luwipress-sapphire' ]; }
	public function get_keywords()    { return [ 'countdown', 'timer', 'sale', 'launch', 'flash' ]; }
	public function get_style_depends() { return [ 'luwipress-sapphire-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_target', [ 'label' => __( 'Target', 'luwipress-sapphire' ) ] );

		$this->add_control( 'target_date', [
			'label'       => __( 'Target date & time', 'luwipress-sapphire' ),
			'description' => __( 'Picked in your site timezone (WP general settings) and rendered live in the visitor\'s browser.', 'luwipress-sapphire' ),
			'type'        => Controls_Manager::DATE_TIME,
			'default'     => gmdate( 'Y-m-d H:i:s', strtotime( '+7 days' ) ),
		] );

		$this->add_control( 'show_units', [
			'label'       => __( 'Units to show', 'luwipress-sapphire' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'dhms',
			'options'     => [
				'dhms' => __( 'Days · Hours · Minutes · Seconds', 'luwipress-sapphire' ),
				'dhm'  => __( 'Days · Hours · Minutes', 'luwipress-sapphire' ),
				'hms'  => __( 'Hours · Minutes · Seconds', 'luwipress-sapphire' ),
			],
		] );

		$this->add_control( 'lbl_days',    [ 'label' => __( 'Label — days', 'luwipress-sapphire' ),    'type' => Controls_Manager::TEXT, 'default' => __( 'Days', 'luwipress-sapphire' ) ] );
		$this->add_control( 'lbl_hours',   [ 'label' => __( 'Label — hours', 'luwipress-sapphire' ),   'type' => Controls_Manager::TEXT, 'default' => __( 'Hours', 'luwipress-sapphire' ) ] );
		$this->add_control( 'lbl_minutes', [ 'label' => __( 'Label — minutes', 'luwipress-sapphire' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Minutes', 'luwipress-sapphire' ) ] );
		$this->add_control( 'lbl_seconds', [ 'label' => __( 'Label — seconds', 'luwipress-sapphire' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Seconds', 'luwipress-sapphire' ) ] );

		$this->add_control( 'expired_msg', [
			'label'   => __( 'Post-expiry message', 'luwipress-sapphire' ),
			'type'    => Controls_Manager::TEXTAREA,
			'rows'    => 2,
			'default' => __( "It's live. Browse the sale →", 'luwipress-sapphire' ),
		] );
		$this->add_control( 'expired_url', [
			'label'   => __( 'Post-expiry button URL (optional)', 'luwipress-sapphire' ),
			'type'    => Controls_Manager::URL,
			'default' => [ 'url' => '' ],
		] );
		$this->add_control( 'expired_btn', [
			'label'   => __( 'Post-expiry button label (optional)', 'luwipress-sapphire' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '',
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_style', [ 'label' => __( 'Style', 'luwipress-sapphire' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'variant', [
			'label'   => __( 'Variant', 'luwipress-sapphire' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'light',
			'options' => [ 'light' => __( 'Light', 'luwipress-sapphire' ), 'dark' => __( 'Dark', 'luwipress-sapphire' ) ],
		] );
		$this->add_control( 'align', [
			'label'   => __( 'Alignment', 'luwipress-sapphire' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'center',
			'options' => [ 'left' => __( 'Left', 'luwipress-sapphire' ), 'center' => __( 'Center', 'luwipress-sapphire' ), 'right' => __( 'Right', 'luwipress-sapphire' ) ],
		] );
		$this->end_controls_section();
	}

	protected function render() {
		$s         = $this->get_settings_for_display();
		$target    = trim( (string) ( $s['target_date'] ?? '' ) );
		if ( $target === '' ) { return; }
		// Elementor returns "Y-m-d H:i:s" in site timezone.
		try {
			$dt = new \DateTime( $target, wp_timezone() );
			$utc = $dt->getTimestamp();
		} catch ( \Exception $e ) {
			return;
		}

		$units   = in_array( $s['show_units'] ?? 'dhms', [ 'dhms', 'dhm', 'hms' ], true ) ? $s['show_units'] : 'dhms';
		$variant = ( $s['variant'] ?? 'light' ) === 'dark' ? 'dark' : 'light';
		$align   = in_array( $s['align'] ?? 'center', [ 'left', 'center', 'right' ], true ) ? $s['align'] : 'center';

		$labels = [
			'd' => (string) ( $s['lbl_days']    ?? 'Days' ),
			'h' => (string) ( $s['lbl_hours']   ?? 'Hours' ),
			'm' => (string) ( $s['lbl_minutes'] ?? 'Minutes' ),
			's' => (string) ( $s['lbl_seconds'] ?? 'Seconds' ),
		];
		$expired_msg = (string) ( $s['expired_msg'] ?? '' );
		$expired_url = $s['expired_url']['url'] ?? '';
		$expired_btn = trim( (string) ( $s['expired_btn'] ?? '' ) );
		$expired_ext = ! empty( $s['expired_url']['is_external'] );
		?>
		<div class="lwp-cd lwp-cd--<?php echo esc_attr( $variant ); ?>" data-align="<?php echo esc_attr( $align ); ?>"
			data-target="<?php echo esc_attr( $utc ); ?>"
			data-units="<?php echo esc_attr( $units ); ?>">
			<div class="lwp-cd__cells">
				<?php
				$show = [
					'd' => strpos( $units, 'd' ) !== false,
					'h' => true,
					'm' => true,
					's' => strpos( $units, 's' ) !== false,
				];
				foreach ( $show as $unit => $on ) {
					if ( ! $on ) { continue; }
					?>
					<div class="lwp-cd__cell" data-unit="<?php echo esc_attr( $unit ); ?>">
						<span class="lwp-cd__num">--</span>
						<span class="lwp-cd__lbl"><?php echo esc_html( $labels[ $unit ] ); ?></span>
					</div>
					<?php
				}
				?>
			</div>
			<div class="lwp-cd__expired" hidden>
				<?php if ( $expired_msg ) : ?>
					<p><?php echo esc_html( $expired_msg ); ?></p>
				<?php endif; ?>
				<?php if ( $expired_btn && $expired_url ) : ?>
					<a class="lwp-cd__expired-btn" href="<?php echo esc_url( $expired_url ); ?>"
						<?php echo $expired_ext ? ' target="_blank" rel="noopener"' : ''; ?>>
						<?php echo esc_html( $expired_btn ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
