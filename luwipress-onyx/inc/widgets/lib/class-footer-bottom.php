<?php
/**
 * Widget: Footer Bottom.
 *
 * Copyright line + legal links + payment-method badges row.
 * Sits at the very bottom of the footer above the page edge.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

class LuwiPress_Onyx_Widget_Footer_Bottom extends Widget_Base {

	public function get_name()        { return 'lwp-footer-bottom'; }
	public function get_title()       { return __( 'Footer Bottom', 'luwipress-onyx' ); }
	public function get_icon()        { return 'eicon-footer'; }
	public function get_categories()  { return [ 'luwipress-onyx' ]; }
	public function get_keywords()    { return [ 'footer', 'copyright', 'legal', 'payment' ]; }
	public function get_style_depends() { return [ 'luwipress-onyx-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_left', [ 'label' => __( 'Copyright + Legal', 'luwipress-onyx' ) ] );

		$this->add_control(
			'copyright',
			[
				'label'   => __( 'Copyright line', 'luwipress-onyx' ),
				'description' => __( 'Use %YEAR% to insert the current year automatically.', 'luwipress-onyx' ),
				'type'    => Controls_Manager::TEXT,
				'default' => sprintf( '© %s %s', '%YEAR%', get_bloginfo( 'name' ) ),
			]
		);

		$rep = new Repeater();
		$rep->add_control( 'label', [ 'label' => __( 'Label', 'luwipress-onyx' ), 'type' => Controls_Manager::TEXT, 'default' => 'Privacy' ] );
		$rep->add_control( 'url',   [ 'label' => __( 'URL', 'luwipress-onyx' ),   'type' => Controls_Manager::URL,  'default' => [ 'url' => '#' ] ] );

		$this->add_control(
			'legal_links',
			[
				'label'       => __( 'Legal links', 'luwipress-onyx' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $rep->get_controls(),
				'title_field' => '{{{ label || "Link" }}}',
				'default'     => [
					[ 'label' => __( 'Privacy', 'luwipress-onyx' ), 'url' => [ 'url' => '/privacy' ] ],
					[ 'label' => __( 'Terms', 'luwipress-onyx' ),   'url' => [ 'url' => '/terms' ] ],
					[ 'label' => __( 'Cookies', 'luwipress-onyx' ), 'url' => [ 'url' => '/cookies' ] ],
				],
			]
		);

		$this->end_controls_section();

		/* Payment methods */
		$this->start_controls_section( 'section_pay', [ 'label' => __( 'Payment methods', 'luwipress-onyx' ) ] );

		$this->add_control(
			'pay_label',
			[
				'label'   => __( 'Payment label', 'luwipress-onyx' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Secure payments', 'luwipress-onyx' ),
			]
		);

		$rep_pay = new Repeater();
		$rep_pay->add_control( 'label', [ 'label' => __( 'Method', 'luwipress-onyx' ), 'type' => Controls_Manager::TEXT, 'default' => 'VISA' ] );

		$this->add_control(
			'pay_methods',
			[
				'label'       => __( 'Methods', 'luwipress-onyx' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $rep_pay->get_controls(),
				'title_field' => '{{{ label || "Method" }}}',
				'default'     => [
					[ 'label' => 'VISA' ],
					[ 'label' => 'MC' ],
					[ 'label' => 'AMEX' ],
					[ 'label' => 'PayPal' ],
					[ 'label' => 'SEPA' ],
				],
			]
		);

		$this->end_controls_section();

		/* Style */
		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-onyx' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
		$this->add_control(
			'text_color',
			[
				'label'   => __( 'Text color', 'luwipress-onyx' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '#6b6151',
				'selectors' => [ '{{WRAPPER}} .lwp-fbot' => 'color: {{VALUE}};' ],
			]
		);
		$this->add_control(
			'link_hover_color',
			[
				'label'   => __( 'Link hover color', 'luwipress-onyx' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '#D4AF37',
				'selectors' => [ '{{WRAPPER}} .lwp-fbot a:hover' => 'color: {{VALUE}};' ],
			]
		);
		$this->add_control(
			'pay_bg',
			[
				'label'   => __( 'Pay badge bg', 'luwipress-onyx' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '#1A1612',
				'selectors' => [ '{{WRAPPER}} .lwp-fbot__pay' => 'background: {{VALUE}};' ],
			]
		);
		$this->add_control(
			'pay_border',
			[
				'label'   => __( 'Pay badge border', 'luwipress-onyx' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '#3a342c',
				'selectors' => [ '{{WRAPPER}} .lwp-fbot__pay' => 'border-color: {{VALUE}};' ],
			]
		);
		$this->end_controls_section();
	}

	protected function render() {
		$s          = $this->get_settings_for_display();
		$copyright  = trim( (string) ( $s['copyright'] ?? '' ) );
		$copyright  = str_replace( '%YEAR%', (string) gmdate( 'Y' ), $copyright );
		$legals     = is_array( $s['legal_links'] ?? null ) ? $s['legal_links'] : [];
		$pay_label  = trim( (string) ( $s['pay_label'] ?? '' ) );
		$pay        = is_array( $s['pay_methods'] ?? null ) ? $s['pay_methods'] : [];
		?>
		<div class="lwp-fbot">
			<div class="lwp-fbot__left">
				<?php if ( $copyright ) : ?>
					<span class="lwp-fbot__copy"><?php echo esc_html( $copyright ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $legals ) ) : ?>
					<?php foreach ( $legals as $lnk ) :
						$lbl = trim( (string) ( $lnk['label'] ?? '' ) );
						if ( $lbl === '' ) { continue; }
						$url = $lnk['url']['url'] ?? '#';
						$ext = ! empty( $lnk['url']['is_external'] );
						?>
						<a href="<?php echo esc_url( $url ); ?>"<?php echo $ext ? ' target="_blank" rel="noopener"' : ''; ?>>
							<?php echo esc_html( $lbl ); ?>
						</a>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
			<?php if ( $pay_label || ! empty( $pay ) ) : ?>
				<div class="lwp-fbot__right">
					<?php if ( $pay_label ) : ?>
						<span class="lwp-fbot__pay-label"><?php echo esc_html( $pay_label ); ?></span>
					<?php endif; ?>
					<?php foreach ( $pay as $p ) :
						$lbl = trim( (string) ( $p['label'] ?? '' ) );
						if ( $lbl === '' ) { continue; }
						?>
						<span class="lwp-fbot__pay"><?php echo esc_html( $lbl ); ?></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
