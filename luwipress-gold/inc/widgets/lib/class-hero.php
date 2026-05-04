<?php
/**
 * Widget: Hero.
 *
 * Three layout variants:
 *   - split       → 60/40 text + visual side-by-side (Tapadum default)
 *   - centered    → centered headline + image below (editorial)
 *   - full-bleed  → full-cover image with text overlay (impact intro)
 *
 * Includes: eyebrow, headline (with italic emphasis), lead, primary + secondary CTA.
 * Optional: 3 stat cards under the lead.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;

class LuwiPress_Gold_Widget_Hero extends Widget_Base {

	public function get_name()        { return 'lwp-hero'; }
	public function get_title()       { return __( 'Hero', 'luwipress-gold' ); }
	public function get_icon()        { return 'eicon-banner'; }
	public function get_categories()  { return [ 'luwipress-gold' ]; }
	public function get_keywords()    { return [ 'hero', 'banner', 'header', 'intro', 'splash' ]; }
	public function get_style_depends() { return [ 'luwipress-gold-widgets' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'section_layout', [ 'label' => __( 'Layout', 'luwipress-gold' ) ] );
		$this->add_control(
			'variant',
			[
				'label'   => __( 'Variant', 'luwipress-gold' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'split',
				'options' => [
					'split'      => __( 'Split (text + visual side-by-side)', 'luwipress-gold' ),
					'centered'   => __( 'Centered (headline above image)', 'luwipress-gold' ),
					'full-bleed' => __( 'Full-bleed (image cover + text overlay)', 'luwipress-gold' ),
				],
			]
		);
		$this->end_controls_section();

		/* Content */
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Content', 'luwipress-gold' ) ] );
		$this->add_control(
			'eyebrow',
			[
				'label'   => __( 'Eyebrow', 'luwipress-gold' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Est. Brisighella · Worldwide shipping', 'luwipress-gold' ),
			]
		);
		$this->add_control(
			'headline',
			[
				'label'       => __( 'Headline (HTML allowed)', 'luwipress-gold' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'default'     => __( 'Hand-crafted instruments<br>from <em>Anatolia</em>, <span class="underline">Persia</span> &amp; the Mediterranean.', 'luwipress-gold' ),
				'description' => __( 'Use <em>…</em> for italic emphasis (gold) and <span class="underline">…</span> for the underline accent.', 'luwipress-gold' ),
			]
		);
		$this->add_control(
			'lead',
			[
				'label'   => __( 'Lead paragraph', 'luwipress-gold' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 3,
				'default' => __( 'Tapadum brings together master luthiers from Istanbul, Tehran and Cairo — then carries their work to musicians in 47 countries.', 'luwipress-gold' ),
			]
		);

		$this->add_control(
			'cta_primary_label',
			[
				'label'   => __( 'Primary CTA — label', 'luwipress-gold' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Explore the collection', 'luwipress-gold' ),
			]
		);
		$this->add_control(
			'cta_primary_url',
			[
				'label'   => __( 'Primary CTA — URL', 'luwipress-gold' ),
				'type'    => Controls_Manager::URL,
				'default' => [ 'url' => '/shop' ],
			]
		);

		$this->add_control(
			'cta_secondary_label',
			[
				'label'   => __( 'Secondary CTA — label', 'luwipress-gold' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Watch atelier film', 'luwipress-gold' ),
			]
		);
		$this->add_control(
			'cta_secondary_url',
			[
				'label'   => __( 'Secondary CTA — URL', 'luwipress-gold' ),
				'type'    => Controls_Manager::URL,
				'default' => [ 'url' => '' ],
			]
		);

		$this->add_control(
			'image',
			[
				'label'       => __( 'Visual', 'luwipress-gold' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => [ 'url' => '' ],
				'description' => __( 'Used in split + centered + full-bleed variants. Falls back to a gradient.', 'luwipress-gold' ),
			]
		);
		$this->end_controls_section();

		/* Stats (split + centered only) */
		$this->start_controls_section( 'section_stats', [
			'label' => __( 'Stats', 'luwipress-gold' ),
			'condition' => [ 'variant' => [ 'split', 'centered' ] ],
		] );
		$this->add_control(
			'show_stats',
			[
				'label'   => __( 'Show stats row', 'luwipress-gold' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'value',
			[ 'label' => __( 'Value', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT, 'default' => '1,200+' ]
		);
		$repeater->add_control(
			'label',
			[ 'label' => __( 'Label', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT, 'default' => 'Instruments' ]
		);
		$this->add_control(
			'stats',
			[
				'label'       => __( 'Stat cards', 'luwipress-gold' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ value }}} — {{{ label }}}',
				'default'     => [
					[ 'value' => '1,200+', 'label' => __( 'Instruments', 'luwipress-gold' ) ],
					[ 'value' => '28',     'label' => __( 'Master makers', 'luwipress-gold' ) ],
					[ 'value' => '47',     'label' => __( 'Countries shipped', 'luwipress-gold' ) ],
				],
				'condition'   => [ 'show_stats' => 'yes' ],
			]
		);
		$this->end_controls_section();

		/* Style */
		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-gold' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
		$this->add_control(
			'overlay_color',
			[
				'label'     => __( 'Image overlay (full-bleed only)', 'luwipress-gold' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(28,22,12,0.45)',
				'selectors' => [
					'{{WRAPPER}} .lwp-hero--full-bleed::after' => 'background: {{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'headline_typography',
				'selector' => '{{WRAPPER}} .lwp-hero-h1',
			]
		);
		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$variant = $s['variant'] ?? 'split';
		$image_url = $s['image']['url'] ?? '';

		$primary_attrs = '';
		if ( ! empty( $s['cta_primary_url']['url'] ) ) {
			$primary_attrs = ' href="' . esc_url( $s['cta_primary_url']['url'] ) . '"';
			if ( ! empty( $s['cta_primary_url']['is_external'] ) ) $primary_attrs .= ' target="_blank" rel="noopener"';
		}
		$secondary_attrs = '';
		if ( ! empty( $s['cta_secondary_url']['url'] ) ) {
			$secondary_attrs = ' href="' . esc_url( $s['cta_secondary_url']['url'] ) . '"';
			if ( ! empty( $s['cta_secondary_url']['is_external'] ) ) $secondary_attrs .= ' target="_blank" rel="noopener"';
		}

		$class = 'lwp-hero lwp-hero--' . esc_attr( $variant );
		$style = '';
		if ( $variant === 'full-bleed' && $image_url ) {
			$style = 'style="background-image:url(' . esc_url( $image_url ) . ')"';
		}
		?>
		<section class="<?php echo $class; ?>" <?php echo $style; ?>>
			<div class="lwp-hero-inner">
				<div class="lwp-hero-text">
					<?php if ( ! empty( $s['eyebrow'] ) ) : ?>
						<span class="lwp-hero-eyebrow"><?php echo esc_html( $s['eyebrow'] ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $s['headline'] ) ) : ?>
						<h1 class="lwp-hero-h1"><?php echo wp_kses_post( $s['headline'] ); ?></h1>
					<?php endif; ?>
					<?php if ( ! empty( $s['lead'] ) ) : ?>
						<p class="lwp-hero-lead"><?php echo esc_html( $s['lead'] ); ?></p>
					<?php endif; ?>

					<?php if ( ! empty( $s['cta_primary_label'] ) || ! empty( $s['cta_secondary_label'] ) ) : ?>
						<div class="lwp-hero-cta">
							<?php if ( ! empty( $s['cta_primary_label'] ) ) : ?>
								<a class="lwp-hero-btn lwp-hero-btn--primary"<?php echo $primary_attrs; ?>>
									<?php echo esc_html( $s['cta_primary_label'] ); ?>
									<span class="lwp-hero-btn-arrow">→</span>
								</a>
							<?php endif; ?>
							<?php if ( ! empty( $s['cta_secondary_label'] ) ) : ?>
								<a class="lwp-hero-btn lwp-hero-btn--secondary"<?php echo $secondary_attrs; ?>>
									▶ <?php echo esc_html( $s['cta_secondary_label'] ); ?>
								</a>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php if ( in_array( $variant, [ 'split', 'centered' ], true ) && $s['show_stats'] === 'yes' && ! empty( $s['stats'] ) ) : ?>
						<div class="lwp-hero-stats">
							<?php foreach ( $s['stats'] as $stat ) : ?>
								<div class="lwp-hero-stat">
									<span class="lwp-hero-stat-num"><?php echo esc_html( $stat['value'] ); ?></span>
									<span class="lwp-hero-stat-lbl"><?php echo esc_html( $stat['label'] ); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>

				<?php if ( $variant !== 'full-bleed' && ( $image_url || true ) ) : ?>
					<div class="lwp-hero-visual" <?php echo $image_url ? 'style="background-image:url(' . esc_url( $image_url ) . ')"' : ''; ?>></div>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}
