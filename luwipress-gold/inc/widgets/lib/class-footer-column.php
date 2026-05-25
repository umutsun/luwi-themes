<?php
/**
 * Widget: Footer Column.
 *
 * Heading + link list repeater. Replaces inline footer column HTML
 * (Shop / Atelier / Help patterns). Supports "hot" flag for promo links.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

class LuwiPress_Gold_Widget_Footer_Column extends Widget_Base {

	public function get_name()        { return 'lwp-footer-column'; }
	public function get_title()       { return __( 'Footer Column', 'luwipress-gold' ); }
	public function get_icon()        { return 'eicon-bullet-list'; }
	public function get_categories()  { return [ 'luwipress-gold' ]; }
	public function get_keywords()    { return [ 'footer', 'column', 'links', 'menu', 'list' ]; }
	public function get_style_depends() { return [ 'luwipress-gold-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_content', [ 'label' => __( 'Column', 'luwipress-gold' ) ] );

		$this->add_control(
			'heading',
			[
				'label'   => __( 'Heading', 'luwipress-gold' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Shop', 'luwipress-gold' ),
			]
		);

		$rep = new Repeater();
		$rep->add_control( 'label', [ 'label' => __( 'Label', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT, 'default' => 'Link' ] );
		$rep->add_control( 'url',   [ 'label' => __( 'URL', 'luwipress-gold' ),   'type' => Controls_Manager::URL,  'default' => [ 'url' => '#' ] ] );
		$rep->add_control(
			'hot',
			[
				'label'        => __( 'Highlight (sale / promo)', 'luwipress-gold' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'links',
			[
				'label'       => __( 'Links', 'luwipress-gold' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $rep->get_controls(),
				'title_field' => '{{{ label || "Link" }}}',
				'default'     => [
					[ 'label' => __( 'Link 1', 'luwipress-gold' ), 'url' => [ 'url' => '#' ] ],
					[ 'label' => __( 'Link 2', 'luwipress-gold' ), 'url' => [ 'url' => '#' ] ],
					[ 'label' => __( 'Link 3', 'luwipress-gold' ), 'url' => [ 'url' => '#' ] ],
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
			'heading_color',
			[
				'label'     => __( 'Heading color', 'luwipress-gold' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [ '{{WRAPPER}} .lwp-fc__heading' => 'color: {{VALUE}};' ],
			]
		);
		$this->add_control(
			'link_color',
			[
				'label'     => __( 'Link color', 'luwipress-gold' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#a89d85',
				'selectors' => [ '{{WRAPPER}} .lwp-fc a' => 'color: {{VALUE}};' ],
			]
		);
		$this->add_control(
			'link_hover_color',
			[
				'label'     => __( 'Link hover color', 'luwipress-gold' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [ '{{WRAPPER}} .lwp-fc a:hover' => 'color: {{VALUE}};' ],
			]
		);
		$this->add_control(
			'hot_color',
			[
				'label'     => __( 'Hot link color', 'luwipress-gold' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#D4AF37',
				'selectors' => [ '{{WRAPPER}} .lwp-fc a.is-hot' => 'color: {{VALUE}};' ],
			]
		);
		$this->end_controls_section();
	}

	protected function render() {
		$s        = $this->get_settings_for_display();
		$heading  = trim( (string) ( $s['heading'] ?? '' ) );
		$links    = is_array( $s['links'] ?? null ) ? $s['links'] : [];
		if ( $heading === '' && empty( $links ) ) { return; }
		?>
		<div class="lwp-fc">
			<?php if ( $heading ) : ?>
				<h6 class="lwp-fc__heading"><?php echo esc_html( $heading ); ?></h6>
			<?php endif; ?>
			<?php if ( ! empty( $links ) ) : ?>
				<ul class="lwp-fc__list">
					<?php foreach ( $links as $lnk ) :
						$lbl = trim( (string) ( $lnk['label'] ?? '' ) );
						if ( $lbl === '' ) { continue; }
						$url = $lnk['url']['url'] ?? '#';
						$ext = ! empty( $lnk['url']['is_external'] );
						$hot = ( $lnk['hot'] ?? '' ) === 'yes';
						?>
						<li>
							<a class="<?php echo $hot ? 'is-hot' : ''; ?>"
								href="<?php echo esc_url( $url ); ?>"
								<?php echo $ext ? ' target="_blank" rel="noopener"' : ''; ?>>
								<?php echo esc_html( $lbl ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}
}
