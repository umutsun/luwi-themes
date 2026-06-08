<?php
/**
 * Widget: Master Profile.
 *
 * Renders a luthier / craftsperson card — large 3:4 portrait, name,
 * location, specialty, instrument count, dual CTA. Used in the homepage
 * "Twenty-eight masters" grid and on the dedicated /masters/{slug}/ page.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class LuwiPress_Onyx_Widget_Master_Profile extends Widget_Base {

	public function get_name()        { return 'lwp-master-profile'; }
	public function get_title()       { return __( 'Master Profile', 'luwipress-onyx' ); }
	public function get_icon()        { return 'eicon-person'; }
	public function get_categories()  { return [ 'luwipress-onyx' ]; }
	public function get_keywords()    { return [ 'master', 'luthier', 'profile', 'artisan', 'craftsperson' ]; }
	public function get_style_depends() { return [ 'luwipress-onyx-widgets' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Content', 'luwipress-onyx' ) ] );

		$this->add_control(
			'portrait',
			[
				'label'   => __( 'Portrait', 'luwipress-onyx' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => [ 'url' => '' ],
			]
		);
		$this->add_control(
			'fallback_initial',
			[
				'label'       => __( 'Fallback initial', 'luwipress-onyx' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'Shown when no portrait is set. Auto-derived from name if empty.', 'luwipress-onyx' ),
			]
		);
		$this->add_control(
			'name',
			[
				'label'   => __( 'Name', 'luwipress-onyx' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Profile name', 'luwipress-onyx' ),
			]
		);
		$this->add_control(
			'location',
			[
				'label'   => __( 'Location', 'luwipress-onyx' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			]
		);
		$this->add_control(
			'specialty',
			[
				'label'   => __( 'Specialty', 'luwipress-onyx' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			]
		);
		$this->add_control(
			'count',
			[
				'label'   => __( 'Count', 'luwipress-onyx' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 0,
				'min'     => 0,
			]
		);
		$this->add_control(
			'show_count',
			[
				'label'   => __( 'Show instrument count', 'luwipress-onyx' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);
		$this->add_control(
			'profile_url',
			[
				'label'   => __( 'Profile URL', 'luwipress-onyx' ),
				'type'    => Controls_Manager::URL,
				'default' => [ 'url' => '' ],
				'placeholders' => [ 'url' => 'https://example.com/masters/feramis-aktas/' ],
			]
		);
		$this->add_control(
			'layout',
			[
				'label'   => __( 'Layout', 'luwipress-onyx' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'card',
				'options' => [
					'card'     => __( 'Compact card (homepage grid)', 'luwipress-onyx' ),
					'feature'  => __( 'Featured profile (hero portrait + bio)', 'luwipress-onyx' ),
				],
			]
		);

		$this->end_controls_section();

		/* ─── Style ─── */
		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-onyx' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
		$this->add_control(
			'gradient_a',
			[
				'label'   => __( 'Fallback gradient — start', 'luwipress-onyx' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '#9a7b3a',
			]
		);
		$this->add_control(
			'gradient_b',
			[
				'label'   => __( 'Fallback gradient — end', 'luwipress-onyx' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '#d4af37',
			]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'name_typography',
				'selector' => '{{WRAPPER}} .lwp-mp-name',
			]
		);
		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();

		$portrait_url = ! empty( $s['portrait']['url'] ) ? $s['portrait']['url'] : '';
		$initial      = ! empty( $s['fallback_initial'] )
			? $s['fallback_initial']
			: mb_substr( $s['name'] ?? '', 0, 1 );
		$gradient = sprintf(
			'linear-gradient(135deg, %s, %s)',
			$s['gradient_a'] ?? '#9a7b3a',
			$s['gradient_b'] ?? '#d4af37'
		);
		$bg_style = $portrait_url
			? 'background:#000;background-image:url(' . esc_url( $portrait_url ) . ');background-size:cover;background-position:center'
			: 'background:' . $gradient;

		$href = ! empty( $s['profile_url']['url'] ) ? esc_url( $s['profile_url']['url'] ) : '';
		$tag  = $href ? 'a' : 'div';
		$attr = $href ? ' href="' . $href . '"' : '';
		if ( ! empty( $s['profile_url']['is_external'] ) && $href ) {
			$attr .= ' target="_blank" rel="noopener"';
		}

		$layout = $s['layout'] ?? 'card';

		if ( $layout === 'feature' ) {
			?>
			<div class="lwp-mp lwp-mp--feature">
				<div class="lwp-mp-portrait" style="<?php echo esc_attr( $bg_style ); ?>">
					<?php echo $portrait_url ? '' : esc_html( $initial ); ?>
				</div>
				<div class="lwp-mp-body">
					<?php if ( ! empty( $s['location'] ) ) : ?>
						<span class="lwp-mp-eyebrow">— <?php
							/* translators: %s: location string like 'Istanbul · Turkey' */
							printf( esc_html__( 'Master luthier · %s', 'luwipress-onyx' ), esc_html( $s['location'] ) );
						?></span>
					<?php endif; ?>
					<h1 class="lwp-mp-name"><?php echo esc_html( $s['name'] ); ?></h1>
					<?php if ( ! empty( $s['specialty'] ) ) : ?>
						<p class="lwp-mp-spec"><?php echo esc_html( $s['specialty'] ); ?></p>
					<?php endif; ?>
					<?php if ( $s['show_count'] === 'yes' ) : ?>
						<div class="lwp-mp-stats">
							<div><span class="n"><?php echo (int) $s['count']; ?></span><span class="l"><?php esc_html_e( 'Instruments at the atelier', 'luwipress-onyx' ); ?></span></div>
						</div>
					<?php endif; ?>
				</div>
			</div>
			<?php
			return;
		}

		// Compact card layout.
		echo '<' . $tag . ' class="lwp-mp lwp-mp--card"' . $attr . '>';
		echo '<div class="lwp-mp-portrait" style="' . esc_attr( $bg_style ) . '">';
		echo $portrait_url ? '' : esc_html( $initial );
		echo '</div>';
		echo '<div class="lwp-mp-meta">';
		echo '<h4 class="lwp-mp-name">' . esc_html( $s['name'] ) . '</h4>';
		if ( ! empty( $s['location'] ) ) {
			echo '<span class="lwp-mp-loc">' . esc_html( $s['location'] ) . '</span>';
		}
		if ( ! empty( $s['specialty'] ) ) {
			echo '<span class="lwp-mp-spec">' . esc_html( $s['specialty'] ) . '</span>';
		}
		if ( $s['show_count'] === 'yes' && ! empty( $s['count'] ) ) {
			echo '<span class="lwp-mp-count">' . sprintf(
				/* translators: %d: instrument count */
				esc_html__( '%d instruments', 'luwipress-onyx' ),
				(int) $s['count']
			) . '</span>';
		}
		echo '</div></' . $tag . '>';
	}
}
