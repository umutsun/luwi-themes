<?php
/**
 * Widget: 404 Hero.
 *
 * Giant "404" number + eyebrow + h1 + lead paragraph + inline search + dual CTA.
 * Used on the 404.php template (or Elementor Theme Builder 404 template).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class LuwiPress_Onyx_Widget_404_Hero extends Widget_Base {

	public function get_name()        { return 'lwp-404-hero'; }
	public function get_title()       { return __( '404 Hero', 'luwipress-onyx' ); }
	public function get_icon()        { return 'eicon-warning'; }
	public function get_categories()  { return [ 'luwipress-onyx' ]; }
	public function get_keywords()    { return [ '404', 'error', 'not-found', 'hero' ]; }
	public function get_style_depends() { return [ 'luwipress-onyx-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_content', [ 'label' => __( 'Copy', 'luwipress-onyx' ) ] );

		$this->add_control( 'big_number', [ 'label' => __( 'Big number', 'luwipress-onyx' ), 'type' => Controls_Manager::TEXT, 'default' => '404' ] );
		$this->add_control( 'eyebrow',    [ 'label' => __( 'Eyebrow', 'luwipress-onyx' ),    'type' => Controls_Manager::TEXT, 'default' => __( 'Page not found', 'luwipress-onyx' ) ] );
		$this->add_control(
			'heading',
			[
				'label'   => __( 'Heading', 'luwipress-onyx' ),
				'description' => __( 'Use [em]…[/em] for italic-gold accent.', 'luwipress-onyx' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 2,
				'default' => __( 'This [em]tune[/em] is not in our songbook.', 'luwipress-onyx' ),
			]
		);
		$this->add_control(
			'lead',
			[
				'label'   => __( 'Lead', 'luwipress-onyx' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 3,
				'default' => __( "The link you followed may be broken — but the catalogue, the journal and the masters page are all just one click away.", 'luwipress-onyx' ),
			]
		);
		$this->add_control( 'show_search', [ 'label' => __( 'Show search input', 'luwipress-onyx' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ] );
		$this->add_control(
			'search_placeholder',
			[ 'label' => __( 'Search placeholder', 'luwipress-onyx' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Search the site…', 'luwipress-onyx' ),
				'condition' => [ 'show_search' => 'yes' ] ]
		);

		$this->add_control( 'cta1_label', [ 'label' => __( 'CTA 1 label', 'luwipress-onyx' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Browse the catalogue →', 'luwipress-onyx' ) ] );
		$this->add_control( 'cta1_url',   [ 'label' => __( 'CTA 1 URL', 'luwipress-onyx' ),   'type' => Controls_Manager::URL,  'default' => [ 'url' => '/shop' ] ] );
		$this->add_control( 'cta2_label', [ 'label' => __( 'CTA 2 label', 'luwipress-onyx' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Back home', 'luwipress-onyx' ) ] );
		$this->add_control( 'cta2_url',   [ 'label' => __( 'CTA 2 URL', 'luwipress-onyx' ),   'type' => Controls_Manager::URL,  'default' => [ 'url' => '/' ] ] );

		$this->end_controls_section();

		/* Style */
		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-onyx' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
		$this->add_control( 'number_color', [ 'label' => __( 'Big number color', 'luwipress-onyx' ), 'type' => Controls_Manager::COLOR, 'default' => '#9A7B3A',
			'selectors' => [ '{{WRAPPER}} .lwp-404__num' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'heading_color', [ 'label' => __( 'Heading color', 'luwipress-onyx' ), 'type' => Controls_Manager::COLOR, 'default' => '#1A1612',
			'selectors' => [ '{{WRAPPER}} .lwp-404__title' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'em_color', [ 'label' => __( 'Italic accent color', 'luwipress-onyx' ), 'type' => Controls_Manager::COLOR, 'default' => '#9A7B3A',
			'selectors' => [ '{{WRAPPER}} .lwp-404__title em' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'lead_color', [ 'label' => __( 'Lead color', 'luwipress-onyx' ), 'type' => Controls_Manager::COLOR, 'default' => '#3a342c',
			'selectors' => [ '{{WRAPPER}} .lwp-404__lead' => 'color: {{VALUE}};' ] ] );
		$this->end_controls_section();
	}

	private function render_heading( $raw ) {
		$out = esc_html( $raw );
		$out = preg_replace_callback(
			'/\[em\](.*?)\[\/em\]/u',
			static function ( $m ) { return '<em>' . $m[1] . '</em>'; },
			$out
		);
		return nl2br( $out );
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$num   = trim( (string) ( $s['big_number'] ?? '404' ) );
		$eb    = trim( (string) ( $s['eyebrow'] ?? '' ) );
		$h1    = trim( (string) ( $s['heading'] ?? '' ) );
		$lead  = trim( (string) ( $s['lead'] ?? '' ) );
		$showS = ( $s['show_search'] ?? 'yes' ) === 'yes';
		$sph   = trim( (string) ( $s['search_placeholder'] ?? 'Search' ) );
		$c1l   = trim( (string) ( $s['cta1_label'] ?? '' ) );
		$c1u   = $s['cta1_url']['url'] ?? '';
		$c1e   = ! empty( $s['cta1_url']['is_external'] );
		$c2l   = trim( (string) ( $s['cta2_label'] ?? '' ) );
		$c2u   = $s['cta2_url']['url'] ?? '';
		$c2e   = ! empty( $s['cta2_url']['is_external'] );
		?>
		<div class="lwp-404">
			<?php if ( $num ) : ?><div class="lwp-404__num" aria-hidden="true"><?php echo esc_html( $num ); ?></div><?php endif; ?>
			<?php if ( $eb ) : ?><span class="lwp-404__eyebrow">— <?php echo esc_html( $eb ); ?></span><?php endif; ?>
			<?php if ( $h1 ) : ?><h1 class="lwp-404__title"><?php echo $this->render_heading( $h1 ); ?></h1><?php endif; ?>
			<?php if ( $lead ) : ?><p class="lwp-404__lead"><?php echo esc_html( $lead ); ?></p><?php endif; ?>
			<?php if ( $showS ) : ?>
				<form class="lwp-404__search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" role="search">
					<input type="search" name="s" placeholder="<?php echo esc_attr( $sph ); ?>">
					<button type="submit" aria-label="<?php esc_attr_e( 'Search', 'luwipress-onyx' ); ?>">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
					</button>
				</form>
			<?php endif; ?>
			<?php if ( ( $c1l && $c1u ) || ( $c2l && $c2u ) ) : ?>
				<div class="lwp-404__cta">
					<?php if ( $c1l && $c1u ) : ?>
						<a class="lwp-404__cta-primary" href="<?php echo esc_url( $c1u ); ?>"<?php echo $c1e ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html( $c1l ); ?></a>
					<?php endif; ?>
					<?php if ( $c2l && $c2u ) : ?>
						<a class="lwp-404__cta-link" href="<?php echo esc_url( $c2u ); ?>"<?php echo $c2e ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html( $c2l ); ?></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
