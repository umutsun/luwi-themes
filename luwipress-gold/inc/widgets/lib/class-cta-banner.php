<?php
/**
 * Widget: CTA Banner.
 *
 * Full-bleed call-to-action strip — bg image (or gradient) + headline +
 * 1-2 buttons. Simpler than Hero Split — designed for repeat use in
 * between sections.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class LuwiPress_Gold_Widget_CTA_Banner extends Widget_Base {

	public function get_name()        { return 'lwp-cta-banner'; }
	public function get_title()       { return __( 'CTA Banner', 'luwipress-gold' ); }
	public function get_icon()        { return 'eicon-call-to-action'; }
	public function get_categories()  { return [ 'luwipress-gold' ]; }
	public function get_keywords()    { return [ 'cta', 'banner', 'call', 'action', 'promo' ]; }
	public function get_style_depends() { return [ 'luwipress-gold-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_copy', [ 'label' => __( 'Copy', 'luwipress-gold' ) ] );

		$this->add_control( 'eyebrow', [ 'label' => __( 'Eyebrow', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT, 'default' => '' ] );
		$this->add_control( 'heading', [
			'label'       => __( 'Headline', 'luwipress-gold' ),
			'description' => __( 'Use Enter for line breaks. Wrap with [em]…[/em] for italic gold accent.', 'luwipress-gold' ),
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 3,
			'default'     => __( 'Build the instrument you have always wanted.', 'luwipress-gold' ),
		] );
		$this->add_control( 'lead', [ 'label' => __( 'Lead', 'luwipress-gold' ), 'type' => Controls_Manager::TEXTAREA, 'rows' => 3, 'default' => '' ] );

		$this->add_control( 'cta1_label', [ 'label' => __( 'Primary CTA — label', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Start a custom order →', 'luwipress-gold' ) ] );
		$this->add_control( 'cta1_url',   [ 'label' => __( 'Primary CTA — URL', 'luwipress-gold' ),   'type' => Controls_Manager::URL,  'default' => [ 'url' => '' ] ] );
		$this->add_control( 'cta2_label', [ 'label' => __( 'Secondary CTA — label', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT, 'default' => '' ] );
		$this->add_control( 'cta2_url',   [ 'label' => __( 'Secondary CTA — URL', 'luwipress-gold' ),   'type' => Controls_Manager::URL,  'default' => [ 'url' => '' ] ] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_bg', [ 'label' => __( 'Background', 'luwipress-gold' ) ] );

		$this->add_control( 'bg_image', [
			'label' => __( 'Background image (optional)', 'luwipress-gold' ),
			'type'  => Controls_Manager::MEDIA,
			'default' => [ 'url' => '' ],
		] );
		$this->add_control( 'bg_grad_from', [ 'label' => __( 'Gradient start', 'luwipress-gold' ), 'type' => Controls_Manager::COLOR, 'default' => '#1A1612' ] );
		$this->add_control( 'bg_grad_to',   [ 'label' => __( 'Gradient end', 'luwipress-gold' ),   'type' => Controls_Manager::COLOR, 'default' => '#3d2f1f' ] );

		$this->add_control( 'overlay', [
			'label' => __( 'Image overlay strength', 'luwipress-gold' ),
			'description' => __( 'Only applies when a background image is set.', 'luwipress-gold' ),
			'type'  => Controls_Manager::SELECT,
			'default' => 'medium',
			'options' => [
				'none'    => __( 'None', 'luwipress-gold' ),
				'light'   => __( 'Light', 'luwipress-gold' ),
				'medium'  => __( 'Medium', 'luwipress-gold' ),
				'strong'  => __( 'Strong', 'luwipress-gold' ),
			],
		] );

		$this->add_control( 'align', [
			'label' => __( 'Content alignment', 'luwipress-gold' ),
			'type'  => Controls_Manager::SELECT,
			'default' => 'center',
			'options' => [
				'left'   => __( 'Left', 'luwipress-gold' ),
				'center' => __( 'Center', 'luwipress-gold' ),
				'right'  => __( 'Right', 'luwipress-gold' ),
			],
		] );

		$this->end_controls_section();
	}

	protected function render_heading( $raw ) {
		$out = esc_html( $raw );
		$out = preg_replace_callback( '/\[em\](.*?)\[\/em\]/u', static function ( $m ) { return '<em>' . $m[1] . '</em>'; }, $out );
		return nl2br( $out );
	}

	protected function render() {
		$s         = $this->get_settings_for_display();
		$eyebrow   = trim( (string) ( $s['eyebrow'] ?? '' ) );
		$heading   = trim( (string) ( $s['heading'] ?? '' ) );
		$lead      = trim( (string) ( $s['lead']    ?? '' ) );
		$bg_img    = $s['bg_image']['url']    ?? '';
		$gf        = $s['bg_grad_from']       ?? '#1A1612';
		$gt        = $s['bg_grad_to']         ?? '#3d2f1f';
		$align     = in_array( $s['align'] ?? 'center', [ 'left', 'center', 'right' ], true ) ? $s['align'] : 'center';
		$overlay   = in_array( $s['overlay'] ?? 'medium', [ 'none', 'light', 'medium', 'strong' ], true ) ? $s['overlay'] : 'medium';
		$over_map  = [ 'none' => 0, 'light' => 0.25, 'medium' => 0.45, 'strong' => 0.65 ];
		$over_val  = $over_map[ $overlay ];

		if ( $bg_img && $over_val > 0 ) {
			$style = sprintf(
				'background-image: linear-gradient(rgba(0,0,0,%1$s), rgba(0,0,0,%1$s)), url(%2$s); background-size: cover; background-position: center;',
				$over_val,
				esc_url( $bg_img )
			);
		} elseif ( $bg_img ) {
			$style = sprintf( 'background-image: url(%s); background-size: cover; background-position: center;', esc_url( $bg_img ) );
		} else {
			$style = sprintf( 'background: linear-gradient(135deg, %s, %s);', esc_attr( $gf ), esc_attr( $gt ) );
		}

		$cta1_lbl = trim( (string) ( $s['cta1_label'] ?? '' ) );
		$cta1_url = $s['cta1_url']['url'] ?? '';
		$cta1_ext = ! empty( $s['cta1_url']['is_external'] );
		$cta2_lbl = trim( (string) ( $s['cta2_label'] ?? '' ) );
		$cta2_url = $s['cta2_url']['url'] ?? '';
		$cta2_ext = ! empty( $s['cta2_url']['is_external'] );
		?>
		<div class="lwp-ctab" data-align="<?php echo esc_attr( $align ); ?>" style="<?php echo $style; ?>">
			<div class="lwp-ctab__inner">
				<?php if ( $eyebrow ) : ?><span class="lwp-ctab__eyebrow">— <?php echo esc_html( $eyebrow ); ?></span><?php endif; ?>
				<?php if ( $heading ) : ?><h2 class="lwp-ctab__title"><?php echo $this->render_heading( $heading ); ?></h2><?php endif; ?>
				<?php if ( $lead ) : ?><p class="lwp-ctab__lead"><?php echo esc_html( $lead ); ?></p><?php endif; ?>
				<?php if ( ( $cta1_lbl && $cta1_url ) || ( $cta2_lbl && $cta2_url ) ) : ?>
					<div class="lwp-ctab__btns">
						<?php if ( $cta1_lbl && $cta1_url ) : ?>
							<a class="lwp-ctab__btn lwp-ctab__btn--primary" href="<?php echo esc_url( $cta1_url ); ?>"<?php echo $cta1_ext ? ' target="_blank" rel="noopener"' : ''; ?>>
								<?php echo esc_html( $cta1_lbl ); ?>
							</a>
						<?php endif; ?>
						<?php if ( $cta2_lbl && $cta2_url ) : ?>
							<a class="lwp-ctab__btn lwp-ctab__btn--ghost" href="<?php echo esc_url( $cta2_url ); ?>"<?php echo $cta2_ext ? ' target="_blank" rel="noopener"' : ''; ?>>
								<?php echo esc_html( $cta2_lbl ); ?>
							</a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
