<?php
/**
 * Widget: Trust Badges.
 *
 * Horizontal strip of trust signals — payment methods, security seals,
 * certifications, "Featured in" press logos. Each row is an icon
 * (Elementor icon library) or uploaded image + optional caption.
 *
 * Useful right above the footer or inside the checkout funnel.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

class LuwiPress_Sapphire_Widget_Trust_Badges extends Widget_Base {

	public function get_name()        { return 'lwp-trust-badges'; }
	public function get_title()       { return __( 'Trust Badges', 'luwipress-sapphire' ); }
	public function get_icon()        { return 'eicon-lock-user'; }
	public function get_categories()  { return [ 'luwipress-sapphire' ]; }
	public function get_keywords()    { return [ 'trust', 'badges', 'payment', 'security', 'certifications', 'press' ]; }
	public function get_style_depends() { return [ 'luwipress-sapphire-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_items', [ 'label' => __( 'Badges', 'luwipress-sapphire' ) ] );

		$rep = new Repeater();
		$rep->add_control( 'image', [ 'label' => __( 'Image (preferred)', 'luwipress-sapphire' ), 'type' => Controls_Manager::MEDIA, 'default' => [ 'url' => '' ] ] );
		$rep->add_control( 'icon',  [ 'label' => __( 'Icon (fallback)', 'luwipress-sapphire' ),  'type' => Controls_Manager::ICONS, 'default' => [ 'value' => '', 'library' => '' ] ] );
		$rep->add_control( 'label', [ 'label' => __( 'Label (optional)', 'luwipress-sapphire' ), 'type' => Controls_Manager::TEXT, 'default' => '' ] );
		$rep->add_control( 'url',   [ 'label' => __( 'Link (optional)', 'luwipress-sapphire' ),  'type' => Controls_Manager::URL,  'default' => [ 'url' => '' ] ] );

		$this->add_control( 'items', [
			'label'       => __( 'Badges', 'luwipress-sapphire' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $rep->get_controls(),
			'title_field' => '{{{ label || "Badge" }}}',
			'default'     => [
				[ 'label' => 'Visa',         'icon' => [ 'value' => 'fab fa-cc-visa',       'library' => 'fa-brands' ] ],
				[ 'label' => 'Mastercard',   'icon' => [ 'value' => 'fab fa-cc-mastercard', 'library' => 'fa-brands' ] ],
				[ 'label' => 'PayPal',       'icon' => [ 'value' => 'fab fa-cc-paypal',     'library' => 'fa-brands' ] ],
				[ 'label' => 'DHL Express',  'icon' => [ 'value' => 'fas fa-shipping-fast', 'library' => 'fa-solid' ] ],
				[ 'label' => 'SSL Secured',  'icon' => [ 'value' => 'fas fa-lock',          'library' => 'fa-solid' ] ],
			],
		] );

		$this->add_control( 'show_labels', [
			'label'        => __( 'Show labels under icons', 'luwipress-sapphire' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
		] );
		$this->add_control( 'align', [
			'label'   => __( 'Alignment', 'luwipress-sapphire' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'center',
			'options' => [ 'left' => __( 'Left', 'luwipress-sapphire' ), 'center' => __( 'Center', 'luwipress-sapphire' ), 'right' => __( 'Right', 'luwipress-sapphire' ) ],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_style', [ 'label' => __( 'Style', 'luwipress-sapphire' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'icon_color', [
			'label' => __( 'Icon color', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::COLOR,
			'default' => '',
			'selectors' => [ '{{WRAPPER}} .lwp-tb__item' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'icon_size', [
			'label' => __( 'Icon size (px)', 'luwipress-sapphire' ),
			'type'  => Controls_Manager::NUMBER,
			'default' => 32,
			'min' => 16, 'max' => 96,
			'selectors' => [ '{{WRAPPER}} .lwp-tb__item' => 'font-size: {{VALUE}}px;' ],
		] );
		$this->end_controls_section();
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$items = is_array( $s['items'] ?? null ) ? $s['items'] : [];
		if ( empty( $items ) ) { return; }
		$show_labels = ( $s['show_labels'] ?? '' ) === 'yes';
		$align       = in_array( $s['align'] ?? 'center', [ 'left', 'center', 'right' ], true ) ? $s['align'] : 'center';
		?>
		<div class="lwp-tb" data-align="<?php echo esc_attr( $align ); ?>">
			<?php foreach ( $items as $it ) :
				$image = $it['image']['url'] ?? '';
				$icon  = $it['icon'] ?? [];
				$label = trim( (string) ( $it['label'] ?? '' ) );
				$url   = $it['url']['url'] ?? '';
				$ext   = ! empty( $it['url']['is_external'] );
				$tag   = $url ? 'a' : 'span';
				$attrs = $url ? ' href="' . esc_url( $url ) . '"' . ( $ext ? ' target="_blank" rel="noopener"' : '' ) : '';
				?>
				<<?php echo $tag . $attrs; ?> class="lwp-tb__item" <?php echo $label ? 'aria-label="' . esc_attr( $label ) . '"' : ''; ?>>
					<?php if ( $image ) : ?>
						<img loading="lazy" decoding="async" src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $label ); ?>" />
					<?php elseif ( ! empty( $icon['value'] ) ) : ?>
						<?php \Elementor\Icons_Manager::render_icon( $icon, [ 'aria-hidden' => 'true' ] ); ?>
					<?php endif; ?>
					<?php if ( $show_labels && $label ) : ?>
						<span class="lwp-tb__lbl"><?php echo esc_html( $label ); ?></span>
					<?php endif; ?>
				</<?php echo $tag; ?>>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
