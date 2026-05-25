<?php
/**
 * Widget: Logo.
 *
 * Logo display with two modes: image OR text-with-accent-character.
 * Used in headers, footers, mobile drawers.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class LuwiPress_Gold_Widget_Logo extends Widget_Base {

	public function get_name()        { return 'lwp-logo'; }
	public function get_title()       { return __( 'Logo', 'luwipress-gold' ); }
	public function get_icon()        { return 'eicon-site-logo'; }
	public function get_categories()  { return [ 'luwipress-gold' ]; }
	public function get_keywords()    { return [ 'logo', 'brand', 'wordmark', 'site' ]; }
	public function get_style_depends() { return [ 'luwipress-gold-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_content', [ 'label' => __( 'Logo', 'luwipress-gold' ) ] );

		$this->add_control(
			'image',
			[
				'label'   => __( 'Image (optional)', 'luwipress-gold' ),
				'description' => __( 'When set, replaces the text wordmark.', 'luwipress-gold' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => [ 'url' => '' ],
			]
		);
		$this->add_control(
			'image_max_height',
			[
				'label'   => __( 'Image max height', 'luwipress-gold' ),
				'type'    => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'   => [ 'px' => [ 'min' => 16, 'max' => 120, 'step' => 1 ] ],
				'default' => [ 'unit' => 'px', 'size' => 40 ],
				'selectors' => [ '{{WRAPPER}} .lwp-logo img' => 'max-height: {{SIZE}}{{UNIT}}; width: auto;' ],
				'condition' => [ 'image[url]!' => '' ],
			]
		);
		$this->add_control(
			'text',
			[
				'label'   => __( 'Text wordmark', 'luwipress-gold' ),
				'type'    => Controls_Manager::TEXT,
				'default' => 'tapadum',
				'condition' => [ 'image[url]' => '' ],
			]
		);
		$this->add_control(
			'accent_index',
			[
				'label'   => __( 'Accent character index (0-based, -1 = none)', 'luwipress-gold' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 5,
				'min'     => -1,
				'condition' => [ 'image[url]' => '' ],
			]
		);
		$this->add_control(
			'link_url',
			[
				'label'   => __( 'Link URL', 'luwipress-gold' ),
				'type'    => Controls_Manager::URL,
				'default' => [ 'url' => home_url( '/' ) ],
			]
		);

		$this->end_controls_section();

		/* Style */
		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-gold' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
		$this->add_control(
			'color',
			[
				'label'   => __( 'Text color', 'luwipress-gold' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '#1A1612',
				'selectors' => [ '{{WRAPPER}} .lwp-logo' => 'color: {{VALUE}};' ],
			]
		);
		$this->add_control(
			'accent_color',
			[
				'label'   => __( 'Accent color', 'luwipress-gold' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '#9A7B3A',
				'selectors' => [ '{{WRAPPER}} .lwp-logo__accent' => 'color: {{VALUE}};' ],
			]
		);
		$this->add_control(
			'accent_hover_color',
			[
				'label'   => __( 'Accent hover color', 'luwipress-gold' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '#D4AF37',
				'selectors' => [ '{{WRAPPER}} .lwp-logo:hover .lwp-logo__accent' => 'color: {{VALUE}};' ],
			]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'typography',
				'selector' => '{{WRAPPER}} .lwp-logo',
			]
		);
		$this->end_controls_section();
	}

	private function build_text( $text, $accent_idx ) {
		$len = mb_strlen( $text );
		if ( $accent_idx < 0 || $accent_idx >= $len ) {
			return esc_html( $text );
		}
		$before = mb_substr( $text, 0, $accent_idx );
		$char   = mb_substr( $text, $accent_idx, 1 );
		$after  = mb_substr( $text, $accent_idx + 1 );
		return esc_html( $before ) . '<span class="lwp-logo__accent">' . esc_html( $char ) . '</span>' . esc_html( $after );
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$image = $s['image']['url'] ?? '';
		$text  = trim( (string) ( $s['text'] ?? '' ) );
		$idx   = isset( $s['accent_index'] ) ? (int) $s['accent_index'] : -1;
		$url   = $s['link_url']['url'] ?? home_url( '/' );
		$ext   = ! empty( $s['link_url']['is_external'] );
		?>
		<a class="lwp-logo" href="<?php echo esc_url( $url ); ?>"<?php echo $ext ? ' target="_blank" rel="noopener"' : ''; ?>>
			<?php if ( $image ) : ?>
				<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $text ?: get_bloginfo( 'name' ) ); ?>" />
			<?php else : ?>
				<?php echo $this->build_text( $text, $idx ); ?>
			<?php endif; ?>
		</a>
		<?php
	}
}
