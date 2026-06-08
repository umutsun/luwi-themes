<?php
/**
 * Widget: Perks List.
 *
 * Icon + strong-tag + body bullet list. Common on PDP / checkout pages for
 * "Why buy from us" perks: hand-tuned, 30-day returns, 2-year warranty, etc.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

class LuwiPress_Onyx_Widget_Perks_List extends Widget_Base {

	public function get_name()        { return 'lwp-perks-list'; }
	public function get_title()       { return __( 'Perks List', 'luwipress-onyx' ); }
	public function get_icon()        { return 'eicon-bullet-list'; }
	public function get_categories()  { return [ 'luwipress-onyx' ]; }
	public function get_keywords()    { return [ 'perks', 'benefits', 'list', 'usp', 'trust' ]; }
	public function get_style_depends() { return [ 'luwipress-onyx-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_content', [ 'label' => __( 'Perks', 'luwipress-onyx' ) ] );

		$rep = new Repeater();
		$rep->add_control(
			'icon_type',
			[
				'label'   => __( 'Icon style', 'luwipress-onyx' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'check',
				'options' => [
					'check'    => __( 'Check', 'luwipress-onyx' ),
					'truck'    => __( 'Truck', 'luwipress-onyx' ),
					'shield'   => __( 'Shield', 'luwipress-onyx' ),
					'tool'     => __( 'Tool', 'luwipress-onyx' ),
					'star'     => __( 'Star', 'luwipress-onyx' ),
					'heart'    => __( 'Heart', 'luwipress-onyx' ),
					'leaf'     => __( 'Leaf', 'luwipress-onyx' ),
					'gift'     => __( 'Gift', 'luwipress-onyx' ),
					'phone'    => __( 'Phone', 'luwipress-onyx' ),
				],
			]
		);
		$rep->add_control( 'strong', [ 'label' => __( 'Bold lead', 'luwipress-onyx' ), 'type' => Controls_Manager::TEXT, 'default' => 'Hand-tuned' ] );
		$rep->add_control( 'body',   [ 'label' => __( 'Body', 'luwipress-onyx' ),     'type' => Controls_Manager::TEXT, 'default' => 'in our atelier before shipping' ] );

		$this->add_control(
			'perks',
			[
				'label'       => __( 'Perks', 'luwipress-onyx' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $rep->get_controls(),
				'title_field' => '{{{ strong || "Perk" }}}',
				'default'     => [
					[ 'icon_type' => 'check',  'strong' => __( 'Hand-tuned', 'luwipress-onyx' ),     'body' => __( 'in our atelier before shipping', 'luwipress-onyx' ) ],
					[ 'icon_type' => 'truck',  'strong' => __( 'Free DHL shipping', 'luwipress-onyx' ), 'body' => __( 'on orders over €450', 'luwipress-onyx' ) ],
					[ 'icon_type' => 'shield', 'strong' => __( '2-year warranty', 'luwipress-onyx' ), 'body' => __( '+ lifetime tuning support', 'luwipress-onyx' ) ],
				],
			]
		);

		$this->end_controls_section();

		/* Style */
		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-onyx' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
		$this->add_control( 'icon_color',   [ 'label' => __( 'Icon color', 'luwipress-onyx' ),   'type' => Controls_Manager::COLOR, 'default' => '#9A7B3A',
			'selectors' => [ '{{WRAPPER}} .lwp-perks__icon' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'strong_color', [ 'label' => __( 'Bold color', 'luwipress-onyx' ),   'type' => Controls_Manager::COLOR, 'default' => '#1A1612',
			'selectors' => [ '{{WRAPPER}} .lwp-perks__strong' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'body_color',   [ 'label' => __( 'Body color', 'luwipress-onyx' ),   'type' => Controls_Manager::COLOR, 'default' => '#3a342c',
			'selectors' => [ '{{WRAPPER}} .lwp-perks__body' => 'color: {{VALUE}};' ] ] );
		$this->end_controls_section();
	}

	private function svg( $type ) {
		$paths = [
			'check'  => '<path d="M5 12l5 5 9-11"/>',
			'truck'  => '<path d="M3 7h11v8H3z"/><path d="M14 10h4l3 4v1h-7z"/><circle cx="6" cy="17" r="2"/><circle cx="17" cy="17" r="2"/>',
			'shield' => '<path d="M12 2 4 5v6c0 5 3.5 9 8 11 4.5-2 8-6 8-11V5z"/>',
			'tool'   => '<path d="M14.7 6.3a4 4 0 0 0-5.7 5.7L3 18l3 3 6-6a4 4 0 0 0 5.7-5.7l-3 3-3-3z"/>',
			'star'   => '<polygon points="12 2 15 9 22 9 17 14 19 22 12 18 5 22 7 14 2 9 9 9"/>',
			'heart'  => '<path d="M20 8a5 5 0 0 0-9-3 5 5 0 0 0-9 3c0 7 9 12 9 12s9-5 9-12z"/>',
			'leaf'   => '<path d="M11 20A7 7 0 0 1 4 13c0-5 5-9 13-10-1 8-5 13-10 13a4 4 0 0 1-4-4"/>',
			'gift'   => '<rect x="3" y="8" width="18" height="13" rx="1"/><path d="M3 13h18M12 8v13"/><path d="M12 8s-2-5-5-3 5 3 5 3M12 8s2-5 5-3-5 3-5 3"/>',
			'phone'  => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2 4.2 2 2 0 0 1 4 2h3a2 2 0 0 1 2 1.7c.1 1 .3 2 .6 2.9a2 2 0 0 1-.4 2L8 9.8a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2-.5c.9.3 1.9.5 2.9.6A2 2 0 0 1 22 16.9z"/>',
		];
		$d = $paths[ $type ] ?? $paths['check'];
		return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $d . '</svg>';
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$perks = is_array( $s['perks'] ?? null ) ? $s['perks'] : [];
		if ( empty( $perks ) ) return;
		?>
		<ul class="lwp-perks">
			<?php foreach ( $perks as $p ) :
				$icon   = $p['icon_type'] ?? 'check';
				$strong = trim( (string) ( $p['strong'] ?? '' ) );
				$body   = trim( (string) ( $p['body'] ?? '' ) );
				if ( $strong === '' && $body === '' ) continue;
				?>
				<li class="lwp-perks__item">
					<span class="lwp-perks__icon"><?php echo $this->svg( $icon ); ?></span>
					<span>
						<?php if ( $strong ) : ?><strong class="lwp-perks__strong"><?php echo esc_html( $strong ); ?></strong><?php endif; ?>
						<?php if ( $body ) : ?>
							<span class="lwp-perks__body"> <?php echo esc_html( $body ); ?></span>
						<?php endif; ?>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}
}
