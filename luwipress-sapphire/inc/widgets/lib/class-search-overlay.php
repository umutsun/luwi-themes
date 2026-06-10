<?php
/**
 * Widget: Search Overlay.
 *
 * Full-screen search modal triggered by [data-lwp-trigger="search"] elements
 * (used by lwp-header-actions). Renders search input + grouped suggestion
 * chips (Trending / Categories / Masters / Customs).
 *
 * Also outputs a mobile navigation drawer triggered by [data-lwp-trigger="mobile-nav"].
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

class LuwiPress_Sapphire_Widget_Search_Overlay extends Widget_Base {

	public function get_name()        { return 'lwp-search-overlay'; }
	public function get_title()       { return __( 'Search Overlay', 'luwipress-sapphire' ); }
	public function get_icon()        { return 'eicon-search'; }
	public function get_categories()  { return [ 'luwipress-sapphire' ]; }
	public function get_keywords()    { return [ 'search', 'overlay', 'modal', 'mobile', 'menu' ]; }
	public function get_style_depends() { return [ 'luwipress-sapphire-widgets' ]; }
	public function get_script_depends() { return [ 'luwipress-sapphire-frontend' ]; }

	protected function register_controls() {

		/* Search panel */
		$this->start_controls_section( 'section_search', [ 'label' => __( 'Search panel', 'luwipress-sapphire' ) ] );

		$this->add_control( 'eyebrow',     [ 'label' => __( 'Eyebrow', 'luwipress-sapphire' ),     'type' => Controls_Manager::TEXT, 'default' => __( 'Search', 'luwipress-sapphire' ) ] );
		$this->add_control( 'placeholder', [ 'label' => __( 'Placeholder', 'luwipress-sapphire' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Try a product, master, or category…', 'luwipress-sapphire' ) ] );
		$this->add_control(
			'search_action_url',
			[
				'label'   => __( 'Form action URL', 'luwipress-sapphire' ),
				'description' => __( 'Where the form submits. Default is the site root with ?s=…', 'luwipress-sapphire' ),
				'type'    => Controls_Manager::URL,
				'default' => [ 'url' => home_url( '/' ) ],
			]
		);
		$this->add_control(
			'search_param',
			[
				'label'   => __( 'Search query param', 'luwipress-sapphire' ),
				'description' => __( 'WP default = "s". WooCommerce-specific can use "post_type=product&s".', 'luwipress-sapphire' ),
				'type'    => Controls_Manager::TEXT,
				'default' => 's',
			]
		);

		$rep_block = new Repeater();
		$rep_block->add_control( 'label', [ 'label' => __( 'Block label', 'luwipress-sapphire' ), 'type' => Controls_Manager::TEXT, 'default' => 'Trending' ] );
		$rep_block->add_control(
			'chips',
			[
				'label' => __( 'Chips (label|url, one per line)', 'luwipress-sapphire' ),
				'description' => __( 'Each line: label|url. Example: "Turkish Oud|/?s=turkish+oud"', 'luwipress-sapphire' ),
				'type'  => Controls_Manager::TEXTAREA,
				'rows'  => 5,
				'default' => "Turkish Oud|/?s=turkish+oud\nHandpan|/?s=handpan",
			]
		);

		$this->add_control(
			'suggest_blocks',
			[
				'label'       => __( 'Suggestion blocks', 'luwipress-sapphire' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $rep_block->get_controls(),
				'title_field' => '{{{ label || "Block" }}}',
				'default'     => [
					[ 'label' => __( 'Trending', 'luwipress-sapphire' ), 'chips' => "Turkish Oud|/?s=turkish+oud\nHandpan|/?s=handpan\nNey|/?s=ney" ],
					[ 'label' => __( 'Categories', 'luwipress-sapphire' ), 'chips' => "String|/category/string\nPercussions|/category/percussions" ],
				],
			]
		);

		$this->end_controls_section();

		/* Mobile drawer */
		$this->start_controls_section( 'section_mobile', [ 'label' => __( 'Mobile drawer', 'luwipress-sapphire' ) ] );

		$this->add_control( 'show_mobile_drawer', [ 'label' => __( 'Show mobile drawer', 'luwipress-sapphire' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ] );

		// Menu select
		$menus    = [];
		if ( function_exists( 'wp_get_nav_menus' ) ) {
			foreach ( wp_get_nav_menus() as $m ) {
				$menus[ (string) $m->term_id ] = $m->name;
			}
		}
		$menus    = $menus ?: [ '' => __( '— No menus found —', 'luwipress-sapphire' ) ];
		$this->add_control(
			'mobile_menu_id',
			[
				'label'     => __( 'Mobile menu', 'luwipress-sapphire' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => array_merge( [ '' => __( '— Choose —', 'luwipress-sapphire' ) ], $menus ),
				'condition' => [ 'show_mobile_drawer' => 'yes' ],
			]
		);
		$this->add_control(
			'mobile_drawer_title',
			[
				'label' => __( 'Drawer header label', 'luwipress-sapphire' ),
				'type'  => Controls_Manager::TEXT,
				'default' => 'Menu',
				'condition' => [ 'show_mobile_drawer' => 'yes' ],
			]
		);

		$this->end_controls_section();

		/* Style */
		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-sapphire' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
		$this->add_control(
			'overlay_bg',
			[ 'label' => __( 'Overlay background', 'luwipress-sapphire' ), 'type' => Controls_Manager::COLOR, 'default' => 'rgba(12,12,12,0.92)',
				'selectors' => [ '{{WRAPPER}} .lwp-overlay' => 'background: {{VALUE}};' ] ]
		);
		$this->add_control(
			'panel_bg',
			[ 'label' => __( 'Panel background', 'luwipress-sapphire' ), 'type' => Controls_Manager::COLOR, 'default' => '#FAF6EE',
				'selectors' => [ '{{WRAPPER}} .lwp-search__panel' => 'background: {{VALUE}};' ] ]
		);
		$this->add_control(
			'chip_bg',
			[ 'label' => __( 'Chip background', 'luwipress-sapphire' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff',
				'selectors' => [ '{{WRAPPER}} .lwp-search__chip' => 'background: {{VALUE}};' ] ]
		);
		$this->add_control(
			'accent',
			[ 'label' => __( 'Accent', 'luwipress-sapphire' ), 'type' => Controls_Manager::COLOR, 'default' => '#9A7B3A',
				'selectors' => [
					'{{WRAPPER}} .lwp-search__input-wrap:focus-within' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .lwp-search__chip:hover' => 'border-color: {{VALUE}}; color: {{VALUE}};',
				] ]
		);
		$this->end_controls_section();
	}

	private function parse_chips( $raw ) {
		$out = [];
		foreach ( preg_split( "/\r?\n/", (string) $raw ) as $line ) {
			$line = trim( $line );
			if ( $line === '' ) { continue; }
			$parts = array_map( 'trim', explode( '|', $line, 2 ) );
			$lbl   = $parts[0] ?? '';
			$url   = $parts[1] ?? '';
			if ( $lbl === '' ) { continue; }
			if ( $url === '' ) {
				$url = '/?s=' . rawurlencode( $lbl );
			}
			$out[] = [ 'label' => $lbl, 'url' => $url ];
		}
		return $out;
	}

	protected function render() {
		$s          = $this->get_settings_for_display();
		$eyebrow    = trim( (string) ( $s['eyebrow']     ?? '' ) );
		$placeh     = trim( (string) ( $s['placeholder'] ?? '' ) );
		$action     = $s['search_action_url']['url'] ?? home_url( '/' );
		$param      = sanitize_key( $s['search_param'] ?? 's' ) ?: 's';
		$blocks     = is_array( $s['suggest_blocks'] ?? null ) ? $s['suggest_blocks'] : [];
		$show_drawer= ( $s['show_mobile_drawer'] ?? 'yes' ) === 'yes';
		$menu_id    = (int) ( $s['mobile_menu_id'] ?? 0 );
		$drawer_t   = trim( (string) ( $s['mobile_drawer_title'] ?? 'Menu' ) );
		?>
		<div class="lwp-overlay lwp-overlay--search" data-lwp-overlay="search" hidden>
			<div class="lwp-search__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Search', 'luwipress-sapphire' ); ?>">
				<button class="lwp-overlay__close" type="button" data-lwp-overlay-close aria-label="<?php esc_attr_e( 'Close', 'luwipress-sapphire' ); ?>">×</button>
				<?php if ( $eyebrow ) : ?>
					<div class="lwp-search__eyebrow"><?php echo esc_html( $eyebrow ); ?></div>
				<?php endif; ?>
				<form class="lwp-search__form" role="search" method="get" action="<?php echo esc_url( $action ); ?>">
					<div class="lwp-search__input-wrap">
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#8b7f6a" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
						<input type="search" name="<?php echo esc_attr( $param ); ?>"
							placeholder="<?php echo esc_attr( $placeh ); ?>"
							autocomplete="off" data-lwp-search-input />
					</div>
				</form>
				<?php if ( ! empty( $blocks ) ) : ?>
					<div class="lwp-search__suggest">
						<?php foreach ( $blocks as $b ) :
							$lbl   = trim( (string) ( $b['label'] ?? '' ) );
							$chips = $this->parse_chips( $b['chips'] ?? '' );
							if ( $lbl === '' && empty( $chips ) ) { continue; }
							?>
							<div class="lwp-search__sugg-block">
								<?php if ( $lbl ) : ?><div class="lwp-search__sugg-label"><?php echo esc_html( $lbl ); ?></div><?php endif; ?>
								<div class="lwp-search__chips">
									<?php foreach ( $chips as $c ) : ?>
										<a class="lwp-search__chip" href="<?php echo esc_url( $c['url'] ); ?>"><?php echo esc_html( $c['label'] ); ?></a>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( $show_drawer ) : ?>
			<div class="lwp-overlay lwp-overlay--drawer" data-lwp-overlay="mobile-nav" hidden>
				<aside class="lwp-drawer">
					<header class="lwp-drawer__head">
						<span class="lwp-drawer__title"><?php echo esc_html( $drawer_t ); ?></span>
						<button class="lwp-overlay__close" type="button" data-lwp-overlay-close aria-label="<?php esc_attr_e( 'Close', 'luwipress-sapphire' ); ?>">×</button>
					</header>
					<div class="lwp-drawer__body">
						<?php
						if ( $menu_id ) {
							wp_nav_menu( [
								'menu'        => $menu_id,
								'container'   => false,
								'menu_class'  => 'lwp-drawer__menu',
								'fallback_cb' => false,
								'depth'       => 2,
							] );
						} else {
							echo '<p class="lwp-drawer__empty">' . esc_html__( 'Pick a menu in the widget settings.', 'luwipress-sapphire' ) . '</p>';
						}
						?>
					</div>
				</aside>
			</div>
		<?php endif; ?>
		<?php
	}
}
