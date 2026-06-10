<?php
/**
 * Widget: Pullquote.
 *
 * Large editorial blockquote with decorative quote mark.
 * Optional attribution + citation link.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class LuwiPress_Sapphire_Widget_Pullquote extends Widget_Base {

	public function get_name()        { return 'lwp-pullquote'; }
	public function get_title()       { return __( 'Pullquote', 'luwipress-sapphire' ); }
	public function get_icon()        { return 'eicon-blockquote'; }
	public function get_categories()  { return [ 'luwipress-sapphire' ]; }
	public function get_keywords()    { return [ 'quote', 'pullquote', 'blockquote', 'editorial' ]; }
	public function get_style_depends() { return [ 'luwipress-sapphire-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_content', [ 'label' => __( 'Quote', 'luwipress-sapphire' ) ] );

		$this->add_control(
			'quote',
			[
				'label'   => __( 'Quote text', 'luwipress-sapphire' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 4,
				'default' => __( 'A short, punchy quote that captures the essence of the piece in a single line.', 'luwipress-sapphire' ),
			]
		);
		$this->add_control(
			'attribution',
			[
				'label'   => __( 'Attribution (optional)', 'luwipress-sapphire' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			]
		);
		$this->add_control(
			'citation_url',
			[
				'label'   => __( 'Citation URL (optional)', 'luwipress-sapphire' ),
				'type'    => Controls_Manager::URL,
				'default' => [ 'url' => '' ],
			]
		);
		$this->add_control(
			'show_mark',
			[
				'label'        => __( 'Show decorative quote mark', 'luwipress-sapphire' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);
		$this->add_control(
			'alignment',
			[
				'label'   => __( 'Alignment', 'luwipress-sapphire' ),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'left'   => [ 'title' => __( 'Left', 'luwipress-sapphire' ),   'icon' => 'eicon-text-align-left' ],
					'center' => [ 'title' => __( 'Center', 'luwipress-sapphire' ), 'icon' => 'eicon-text-align-center' ],
				],
				'default' => 'left',
			]
		);

		$this->end_controls_section();

		/* Style */
		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-sapphire' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
		$this->add_control( 'quote_color', [ 'label' => __( 'Quote color', 'luwipress-sapphire' ), 'type' => Controls_Manager::COLOR, 'default' => '#735c00',
			'selectors' => [ '{{WRAPPER}} .lwp-pq__text' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'mark_color', [ 'label' => __( 'Mark color', 'luwipress-sapphire' ), 'type' => Controls_Manager::COLOR, 'default' => 'rgba(155,123,58,0.2)',
			'selectors' => [ '{{WRAPPER}} .lwp-pq__mark' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'attr_color', [ 'label' => __( 'Attribution color', 'luwipress-sapphire' ), 'type' => Controls_Manager::COLOR, 'default' => '#8b7f6a',
			'selectors' => [ '{{WRAPPER}} .lwp-pq__cite' => 'color: {{VALUE}};' ] ] );
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'quote_typography',
				'selector' => '{{WRAPPER}} .lwp-pq__text',
			]
		);
		$this->end_controls_section();
	}

	protected function render() {
		$s        = $this->get_settings_for_display();
		$quote    = trim( (string) ( $s['quote'] ?? '' ) );
		$attr     = trim( (string) ( $s['attribution'] ?? '' ) );
		$cite_url = $s['citation_url']['url'] ?? '';
		$cite_ext = ! empty( $s['citation_url']['is_external'] );
		$show_mark= ( $s['show_mark'] ?? 'yes' ) === 'yes';
		$align    = ( $s['alignment'] ?? 'left' ) === 'center' ? 'center' : 'left';
		if ( $quote === '' ) return;
		?>
		<blockquote class="lwp-pq lwp-pq--<?php echo esc_attr( $align ); ?>"<?php echo $cite_url ? ' cite="' . esc_url( $cite_url ) . '"' : ''; ?>>
			<?php if ( $show_mark ) : ?>
				<span class="lwp-pq__mark" aria-hidden="true">“</span>
			<?php endif; ?>
			<p class="lwp-pq__text"><?php echo esc_html( $quote ); ?></p>
			<?php if ( $attr ) : ?>
				<footer class="lwp-pq__cite">
					— <?php if ( $cite_url ) : ?><a href="<?php echo esc_url( $cite_url ); ?>"<?php echo $cite_ext ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html( $attr ); ?></a><?php else : ?><?php echo esc_html( $attr ); ?><?php endif; ?>
				</footer>
			<?php endif; ?>
		</blockquote>
		<?php
	}
}
