<?php
/**
 * Widget: Header Actions.
 *
 * Icon row for the main header — search trigger / account / cart / CTA / burger.
 * WC-aware: cart shows live count via WC()->cart, account auto-routes to my-account.
 * Triggers lwp-search-overlay via data-lwp-trigger="search" attribute.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class LuwiPress_Sapphire_Widget_Header_Actions extends Widget_Base {

	public function get_name()        { return 'lwp-header-actions'; }
	public function get_title()       { return __( 'Header Actions', 'luwipress-sapphire' ); }
	public function get_icon()        { return 'eicon-cart'; }
	public function get_categories()  { return [ 'luwipress-sapphire' ]; }
	public function get_keywords()    { return [ 'header', 'cart', 'search', 'account', 'icons' ]; }
	public function get_style_depends() { return [ 'luwipress-sapphire-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_content', [ 'label' => __( 'Actions', 'luwipress-sapphire' ) ] );

		$this->add_control( 'show_search',  [ 'label' => __( 'Show search', 'luwipress-sapphire' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ] );

		$this->add_control( 'show_account', [ 'label' => __( 'Show account', 'luwipress-sapphire' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ] );
		$this->add_control(
			'account_url',
			[
				'label'     => __( 'Account URL', 'luwipress-sapphire' ),
				'type'      => Controls_Manager::URL,
				'default'   => [ 'url' => '/my-account' ],
				'condition' => [ 'show_account' => 'yes' ],
			]
		);

		$this->add_control( 'show_cart',    [ 'label' => __( 'Show cart', 'luwipress-sapphire' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ] );
		$this->add_control(
			'cart_url',
			[
				'label'     => __( 'Cart URL', 'luwipress-sapphire' ),
				'type'      => Controls_Manager::URL,
				'default'   => [ 'url' => '/cart' ],
				'condition' => [ 'show_cart' => 'yes' ],
			]
		);

		$this->add_control(
			'cta_label',
			[
				'label'   => __( 'CTA label (optional)', 'luwipress-sapphire' ),
				'description' => __( 'Empty = no CTA button.', 'luwipress-sapphire' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			]
		);
		$this->add_control(
			'cta_url',
			[
				'label'     => __( 'CTA URL', 'luwipress-sapphire' ),
				'type'      => Controls_Manager::URL,
				'default'   => [ 'url' => '' ],
			]
		);

		$this->add_control( 'show_burger',  [ 'label' => __( 'Show mobile burger', 'luwipress-sapphire' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ] );

		$this->end_controls_section();

		/* Style */
		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-sapphire' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
		$this->add_control(
			'icon_color',
			[ 'label' => __( 'Icon color', 'luwipress-sapphire' ), 'type' => Controls_Manager::COLOR, 'default' => '#1A1612',
				'selectors' => [ '{{WRAPPER}} .lwp-ha__icon' => 'color: {{VALUE}};' ] ]
		);
		$this->add_control(
			'icon_border',
			[ 'label' => __( 'Icon border', 'luwipress-sapphire' ), 'type' => Controls_Manager::COLOR, 'default' => '#E8DFC8',
				'selectors' => [ '{{WRAPPER}} .lwp-ha__icon' => 'border-color: {{VALUE}};' ] ]
		);
		$this->add_control(
			'accent_color',
			[ 'label' => __( 'Hover accent', 'luwipress-sapphire' ), 'type' => Controls_Manager::COLOR, 'default' => '#9A7B3A',
				'selectors' => [ '{{WRAPPER}} .lwp-ha__icon:hover' => 'color: {{VALUE}}; border-color: {{VALUE}};' ] ]
		);
		$this->add_control(
			'cta_bg',
			[ 'label' => __( 'CTA background', 'luwipress-sapphire' ), 'type' => Controls_Manager::COLOR, 'default' => '#1A1612',
				'selectors' => [ '{{WRAPPER}} .lwp-ha__cta' => 'background: {{VALUE}}; border-color: {{VALUE}};' ] ]
		);
		$this->add_control(
			'cta_text_color',
			[ 'label' => __( 'CTA text color', 'luwipress-sapphire' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff',
				'selectors' => [ '{{WRAPPER}} .lwp-ha__cta' => 'color: {{VALUE}};' ] ]
		);
		$this->add_control(
			'badge_bg',
			[ 'label' => __( 'Cart badge bg', 'luwipress-sapphire' ), 'type' => Controls_Manager::COLOR, 'default' => '#D4AF37',
				'selectors' => [ '{{WRAPPER}} .lwp-ha__badge' => 'background: {{VALUE}};' ] ]
		);
		$this->end_controls_section();
	}

	private function wc_active() {
		return function_exists( 'WC' ) && class_exists( 'WooCommerce' );
	}

	private function cart_count() {
		if ( ! $this->wc_active() ) { return 0; }
		$wc = function_exists( 'WC' ) ? WC() : null;
		if ( $wc && isset( $wc->cart ) && method_exists( $wc->cart, 'get_cart_contents_count' ) ) {
			return (int) $wc->cart->get_cart_contents_count();
		}
		return 0;
	}

	private function svg_icon( $name ) {
		$icons = [
			'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
			'user'   => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-7 8-7s8 3 8 7"/>',
			'cart'   => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
		];
		$path = $icons[ $name ] ?? '';
		return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
	}

	protected function render() {
		$s             = $this->get_settings_for_display();
		$show_search   = ( $s['show_search']  ?? 'yes' ) === 'yes';
		$show_account  = ( $s['show_account'] ?? 'yes' ) === 'yes';
		$show_cart     = ( $s['show_cart']    ?? 'yes' ) === 'yes';
		$show_burger   = ( $s['show_burger']  ?? 'yes' ) === 'yes';
		$account_url   = $s['account_url']['url'] ?? '/my-account';
		$cart_url      = $s['cart_url']['url']    ?? '/cart';
		$cta_label     = trim( (string) ( $s['cta_label'] ?? '' ) );
		$cta_url       = $s['cta_url']['url']     ?? '';
		$cta_ext       = ! empty( $s['cta_url']['is_external'] );
		$count         = $this->cart_count();
		?>
		<div class="lwp-ha">
			<?php if ( $show_search ) : ?>
				<button class="lwp-ha__icon" type="button" aria-label="<?php esc_attr_e( 'Search', 'luwipress-sapphire' ); ?>"
					data-lwp-trigger="search">
					<?php echo $this->svg_icon( 'search' ); ?>
				</button>
			<?php endif; ?>
			<?php if ( $show_account ) : ?>
				<a class="lwp-ha__icon" href="<?php echo esc_url( $account_url ); ?>" aria-label="<?php esc_attr_e( 'Account', 'luwipress-sapphire' ); ?>">
					<?php echo $this->svg_icon( 'user' ); ?>
				</a>
			<?php endif; ?>
			<?php if ( $show_cart && $this->wc_active() ) : ?>
				<a class="lwp-ha__icon lwp-ha__cart" href="<?php echo esc_url( $cart_url ); ?>" aria-label="<?php esc_attr_e( 'Cart', 'luwipress-sapphire' ); ?>">
					<?php echo $this->svg_icon( 'cart' ); ?>
					<?php if ( $count > 0 ) : ?>
						<span class="lwp-ha__badge"><?php echo esc_html( $count ); ?></span>
					<?php endif; ?>
				</a>
			<?php endif; ?>
			<?php if ( $cta_label && $cta_url ) : ?>
				<a class="lwp-ha__cta" href="<?php echo esc_url( $cta_url ); ?>"<?php echo $cta_ext ? ' target="_blank" rel="noopener"' : ''; ?>>
					<?php echo esc_html( $cta_label ); ?>
				</a>
			<?php endif; ?>
			<?php if ( $show_burger ) : ?>
				<button class="lwp-ha__burger" type="button" aria-label="<?php esc_attr_e( 'Menu', 'luwipress-sapphire' ); ?>" data-lwp-trigger="mobile-nav">
					<span></span><span></span><span></span>
				</button>
			<?php endif; ?>
		</div>
		<?php
	}
}
