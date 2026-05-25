<?php
/**
 * Widget: Topbar.
 *
 * Thin top utility strip above the main header. Left column for contact
 * info (location / phone / email), right column for language switcher
 * + utility link (track order, etc).
 *
 * Auto-detects WPML/Polylang when show_lang_switcher = yes; falls back
 * to manually-configured langs repeater.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

class LuwiPress_Gold_Widget_Topbar extends Widget_Base {

	public function get_name()        { return 'lwp-topbar'; }
	public function get_title()       { return __( 'Topbar', 'luwipress-gold' ); }
	public function get_icon()        { return 'eicon-header'; }
	public function get_categories()  { return [ 'luwipress-gold' ]; }
	public function get_keywords()    { return [ 'topbar', 'top', 'utility', 'language', 'header' ]; }
	public function get_style_depends() { return [ 'luwipress-gold-widgets' ]; }

	protected function register_controls() {

		/* Left items */
		$this->start_controls_section( 'section_left', [ 'label' => __( 'Left items', 'luwipress-gold' ) ] );

		$rep_l = new Repeater();
		$rep_l->add_control( 'text', [ 'label' => __( 'Text', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT, 'default' => '' ] );
		$rep_l->add_control( 'url',  [ 'label' => __( 'URL (optional)', 'luwipress-gold' ), 'type' => Controls_Manager::URL, 'default' => [ 'url' => '' ] ] );

		$this->add_control(
			'left_items',
			[
				'label'       => __( 'Left items', 'luwipress-gold' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $rep_l->get_controls(),
				'title_field' => '{{{ text || "Item" }}}',
				'default'     => [
					[ 'text' => __( '— Atelier', 'luwipress-gold' ), 'url' => [ 'url' => '' ] ],
					[ 'text' => '+1 555 123 4567', 'url' => [ 'url' => 'tel:+15551234567' ] ],
					[ 'text' => 'hello@store.com', 'url' => [ 'url' => 'mailto:hello@store.com' ] ],
				],
			]
		);
		$this->end_controls_section();

		/* Language switcher */
		$this->start_controls_section( 'section_lang', [ 'label' => __( 'Language switcher', 'luwipress-gold' ) ] );

		$this->add_control(
			'show_lang_switcher',
			[
				'label'        => __( 'Show language switcher', 'luwipress-gold' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);
		$this->add_control(
			'lang_mode',
			[
				'label'   => __( 'Switcher source', 'luwipress-gold' ),
				'description' => __( 'Auto = read from WPML/Polylang if active. Manual = use the langs repeater below.', 'luwipress-gold' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => [ 'auto' => __( 'Auto (WPML/Polylang)', 'luwipress-gold' ), 'manual' => __( 'Manual', 'luwipress-gold' ) ],
				'condition' => [ 'show_lang_switcher' => 'yes' ],
			]
		);

		$rep_lang = new Repeater();
		$rep_lang->add_control( 'code',  [ 'label' => __( 'Code', 'luwipress-gold' ),  'type' => Controls_Manager::TEXT, 'default' => 'EN' ] );
		$rep_lang->add_control( 'url',   [ 'label' => __( 'URL', 'luwipress-gold' ),   'type' => Controls_Manager::URL,  'default' => [ 'url' => '/' ] ] );
		$rep_lang->add_control( 'active',[ 'label' => __( 'Active', 'luwipress-gold' ),'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '' ] );

		$this->add_control(
			'langs',
			[
				'label'       => __( 'Manual languages', 'luwipress-gold' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $rep_lang->get_controls(),
				'title_field' => '{{{ code || "??" }}}',
				'default'     => [
					[ 'code' => 'EN', 'active' => 'yes', 'url' => [ 'url' => '/' ] ],
					[ 'code' => 'IT', 'url' => [ 'url' => '/it/' ] ],
					[ 'code' => 'FR', 'url' => [ 'url' => '/fr/' ] ],
					[ 'code' => 'ES', 'url' => [ 'url' => '/es/' ] ],
				],
				'condition' => [ 'lang_mode' => 'manual', 'show_lang_switcher' => 'yes' ],
			]
		);
		$this->end_controls_section();

		/* Right utility links */
		$this->start_controls_section( 'section_right', [ 'label' => __( 'Right utility links', 'luwipress-gold' ) ] );

		$rep_r = new Repeater();
		$rep_r->add_control( 'label', [ 'label' => __( 'Label', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT, 'default' => 'Track order →' ] );
		$rep_r->add_control( 'url',   [ 'label' => __( 'URL', 'luwipress-gold' ),   'type' => Controls_Manager::URL,  'default' => [ 'url' => '/track-order' ] ] );

		$this->add_control(
			'right_links',
			[
				'label'       => __( 'Right links', 'luwipress-gold' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $rep_r->get_controls(),
				'title_field' => '{{{ label || "Link" }}}',
				'default'     => [ [ 'label' => __( 'Track order →', 'luwipress-gold' ), 'url' => [ 'url' => '/track-order' ] ] ],
			]
		);
		$this->end_controls_section();

		/* Style */
		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-gold' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
		$this->add_control(
			'bg_color',
			[ 'label' => __( 'Background', 'luwipress-gold' ), 'type' => Controls_Manager::COLOR, 'default' => '#1A1612',
				'selectors' => [ '{{WRAPPER}} .lwp-topbar' => 'background: {{VALUE}};' ] ]
		);
		$this->add_control(
			'text_color',
			[ 'label' => __( 'Text color', 'luwipress-gold' ), 'type' => Controls_Manager::COLOR, 'default' => '#bbb',
				'selectors' => [ '{{WRAPPER}} .lwp-topbar' => 'color: {{VALUE}};' ] ]
		);
		$this->add_control(
			'accent_color',
			[ 'label' => __( 'Accent (active / hover)', 'luwipress-gold' ), 'type' => Controls_Manager::COLOR, 'default' => '#D4AF37',
				'selectors' => [
					'{{WRAPPER}} .lwp-topbar a:hover'       => 'color: {{VALUE}};',
					'{{WRAPPER}} .lwp-topbar__lang a.on'    => 'color: {{VALUE}};',
				] ]
		);
		$this->end_controls_section();
	}

	/** @return array<int, array{code:string,url:string,active:bool}> */
	private function autodetect_languages() {
		$out = [];

		// WPML
		if ( defined( 'ICL_LANGUAGE_CODE' ) && function_exists( 'icl_get_languages' ) ) {
			$langs = icl_get_languages( 'skip_missing=0&orderby=code' );
			if ( is_array( $langs ) ) {
				foreach ( $langs as $code => $row ) {
					$out[] = [
						'code'   => strtoupper( $row['language_code'] ?? $code ),
						'url'    => $row['url'] ?? '#',
						'active' => ! empty( $row['active'] ),
					];
				}
				return $out;
			}
		}

		// Polylang
		if ( function_exists( 'pll_the_languages' ) ) {
			$langs = pll_the_languages( [ 'raw' => 1, 'hide_if_empty' => 0 ] );
			if ( is_array( $langs ) ) {
				foreach ( $langs as $row ) {
					$out[] = [
						'code'   => strtoupper( $row['slug'] ?? '' ),
						'url'    => $row['url'] ?? '#',
						'active' => ! empty( $row['current_lang'] ),
					];
				}
				return $out;
			}
		}

		return $out;
	}

	protected function render() {
		$s        = $this->get_settings_for_display();
		$left     = is_array( $s['left_items'] ?? null ) ? $s['left_items'] : [];
		$show_lang= ( $s['show_lang_switcher'] ?? 'yes' ) === 'yes';
		$lang_mode= $s['lang_mode'] ?? 'auto';
		$manual_l = is_array( $s['langs'] ?? null ) ? $s['langs'] : [];
		$right    = is_array( $s['right_links'] ?? null ) ? $s['right_links'] : [];

		$langs = [];
		if ( $show_lang ) {
			if ( $lang_mode === 'auto' ) {
				$langs = $this->autodetect_languages();
			}
			if ( empty( $langs ) ) {
				foreach ( $manual_l as $l ) {
					$langs[] = [
						'code'   => sanitize_text_field( $l['code'] ?? '' ),
						'url'    => $l['url']['url'] ?? '#',
						'active' => ( $l['active'] ?? '' ) === 'yes',
					];
				}
			}
		}
		?>
		<div class="lwp-topbar">
			<div class="lwp-topbar__inner">
				<?php if ( ! empty( $left ) ) : ?>
					<div class="lwp-topbar__left">
						<?php foreach ( $left as $it ) :
							$text = trim( (string) ( $it['text'] ?? '' ) );
							if ( $text === '' ) { continue; }
							$url  = $it['url']['url'] ?? '';
							$ext  = ! empty( $it['url']['is_external'] );
							if ( $url ) {
								echo '<a href="' . esc_url( $url ) . '"' . ( $ext ? ' target="_blank" rel="noopener"' : '' ) . '>' . esc_html( $text ) . '</a>';
							} else {
								echo '<span>' . esc_html( $text ) . '</span>';
							}
						endforeach; ?>
					</div>
				<?php endif; ?>
				<div class="lwp-topbar__right">
					<?php if ( ! empty( $langs ) ) : ?>
						<span class="lwp-topbar__lang">
							<?php foreach ( $langs as $l ) :
								if ( empty( $l['code'] ) ) { continue; }
								?>
								<a href="<?php echo esc_url( $l['url'] ); ?>" class="<?php echo $l['active'] ? 'on' : ''; ?>"><?php echo esc_html( $l['code'] ); ?></a>
							<?php endforeach; ?>
						</span>
						<?php if ( ! empty( $right ) ) : ?><span class="lwp-topbar__sep">|</span><?php endif; ?>
					<?php endif; ?>
					<?php foreach ( $right as $r ) :
						$lbl = trim( (string) ( $r['label'] ?? '' ) );
						if ( $lbl === '' ) { continue; }
						$url = $r['url']['url'] ?? '#';
						$ext = ! empty( $r['url']['is_external'] );
						?>
						<a class="lwp-topbar__util" href="<?php echo esc_url( $url ); ?>"<?php echo $ext ? ' target="_blank" rel="noopener"' : ''; ?>>
							<?php echo esc_html( $lbl ); ?>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
	}
}
