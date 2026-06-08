<?php
/**
 * Widget: Hero Split.
 *
 * The homepage hero pattern — 2-column editorial hero:
 *   • Left:  eyebrow · big H1 (with [em]…[/em] italic gold accent) ·
 *            lead paragraph · primary + secondary CTA · 3-up stats row.
 *   • Right: large image (or gradient fallback) · live-status pill ·
 *            featured-tag chip · floating master quote card.
 *
 * Replaces `tap_hero_l_html` + `tap_hero_r_html` in the homepage kit.
 * Renders as a single widget that lays out both columns internally.
 *
 * Note on dynamic copy: the old HTML used `{{LWP:…}}` placeholders for
 * AI-populated values (category_phrase, product_count, etc.). This
 * widget exposes them as plain text controls so the operator can wire
 * them up to ACF / CPT / API data later; LuwiPress Open Claw can fill
 * them via REST on next sync.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

class LuwiPress_Emerald_Widget_Hero_Split extends Widget_Base {

	public function get_name()        { return 'lwp-hero-split'; }
	public function get_title()       { return __( 'Hero Split', 'luwipress-emerald' ); }
	public function get_icon()        { return 'eicon-banner'; }
	public function get_categories()  { return [ 'luwipress-emerald' ]; }
	public function get_keywords()    { return [ 'hero', 'banner', 'landing', 'h1', 'stats' ]; }
	public function get_style_depends() { return [ 'luwipress-emerald-widgets' ]; }

	protected function register_controls() {

		/* Left: copy */
		$this->start_controls_section( 'section_copy', [ 'label' => __( 'Copy (left column)', 'luwipress-emerald' ) ] );

		$this->add_control( 'eyebrow', [
			'label' => __( 'Eyebrow', 'luwipress-emerald' ),
			'type'  => Controls_Manager::TEXT,
			'default' => __( 'Welcome', 'luwipress-emerald' ),
		] );
		$this->add_control( 'heading', [
			'label'       => __( 'Heading (H1)', 'luwipress-emerald' ),
			'description' => __( 'Use Enter for line breaks. Wrap with [em]…[/em] for italic gold accent.', 'luwipress-emerald' ),
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 3,
			'default'     => __( "Your headline goes here\nin [em]two lines[/em].", 'luwipress-emerald' ),
		] );
		$this->add_control( 'lead', [
			'label' => __( 'Lead paragraph', 'luwipress-emerald' ),
			'type'  => Controls_Manager::TEXTAREA,
			'rows'  => 4,
			'default' => '',
		] );

		$this->add_control( 'cta1_label', [
			'label' => __( 'Primary CTA — label', 'luwipress-emerald' ),
			'type'  => Controls_Manager::TEXT,
			'default' => __( 'Shop now →', 'luwipress-emerald' ),
		] );
		$this->add_control( 'cta1_url', [
			'label' => __( 'Primary CTA — URL', 'luwipress-emerald' ),
			'type'  => Controls_Manager::URL,
			'default' => [ 'url' => '/shop' ],
		] );
		$this->add_control( 'cta2_label', [
			'label' => __( 'Secondary CTA — label', 'luwipress-emerald' ),
			'type'  => Controls_Manager::TEXT,
			'default' => __( 'Learn more →', 'luwipress-emerald' ),
		] );
		$this->add_control( 'cta2_url', [
			'label' => __( 'Secondary CTA — URL', 'luwipress-emerald' ),
			'type'  => Controls_Manager::URL,
			'default' => [ 'url' => '/about' ],
		] );

		$this->end_controls_section();

		/* Stats row */
		$this->start_controls_section( 'section_stats', [ 'label' => __( 'Stats row', 'luwipress-emerald' ) ] );

		$rep = new Repeater();
		$rep->add_control( 'num',   [ 'label' => __( 'Number', 'luwipress-emerald' ), 'type' => Controls_Manager::TEXT, 'default' => '' ] );
		$rep->add_control( 'label', [ 'label' => __( 'Label', 'luwipress-emerald' ),  'type' => Controls_Manager::TEXTAREA, 'rows' => 2, 'default' => '' ] );

		$this->add_control( 'stats', [
			'label'       => __( 'Stats', 'luwipress-emerald' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $rep->get_controls(),
			'title_field' => '{{{ num || "Stat" }}}',
			'default'     => [
				[ 'num' => '100+', 'label' => "Products\nin catalogue" ],
				[ 'num' => '10',   'label' => "Countries\nshipped to" ],
				[ 'num' => '5',    'label' => "Years in\nbusiness" ],
			],
		] );

		$this->end_controls_section();

		/* Right: image side */
		$this->start_controls_section( 'section_image', [ 'label' => __( 'Image (right column)', 'luwipress-emerald' ) ] );

		$this->add_control( 'image', [
			'label' => __( 'Image', 'luwipress-emerald' ),
			'type'  => Controls_Manager::MEDIA,
			'default' => [ 'url' => '' ],
		] );
		$this->add_control( 'image_grad_from', [
			'label' => __( 'Gradient start (fallback)', 'luwipress-emerald' ),
			'type'  => Controls_Manager::COLOR,
			'default' => '#3d2f1f',
		] );
		$this->add_control( 'image_grad_to', [
			'label' => __( 'Gradient end (fallback)', 'luwipress-emerald' ),
			'type'  => Controls_Manager::COLOR,
			'default' => '#7a5a2c',
		] );

		$this->add_control( 'pill_text', [
			'label'       => __( 'Live status pill text', 'luwipress-emerald' ),
			'description' => __( 'Small pulsing-dot pill above the image. Leave empty to hide.', 'luwipress-emerald' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => '',
		] );

		$this->add_control( 'tag_eyebrow', [
			'label' => __( 'Featured tag — eyebrow', 'luwipress-emerald' ),
			'type'  => Controls_Manager::TEXT,
			'default' => __( 'Featured', 'luwipress-emerald' ),
		] );
		$this->add_control( 'tag_title', [
			'label' => __( 'Featured tag — title', 'luwipress-emerald' ),
			'type'  => Controls_Manager::TEXT,
			'default' => '',
		] );

		$this->end_controls_section();

		/* Master quote card */
		$this->start_controls_section( 'section_quote', [ 'label' => __( 'Master quote card', 'luwipress-emerald' ) ] );

		$this->add_control( 'quote_avatar', [
			'label' => __( 'Avatar image (optional)', 'luwipress-emerald' ),
			'type'  => Controls_Manager::MEDIA,
			'default' => [ 'url' => '' ],
		] );
		$this->add_control( 'quote_initial', [
			'label' => __( 'Avatar initial (fallback)', 'luwipress-emerald' ),
			'type'  => Controls_Manager::TEXT,
			'default' => '',
		] );
		$this->add_control( 'quote_name', [
			'label' => __( 'Name', 'luwipress-emerald' ),
			'type'  => Controls_Manager::TEXT,
			'default' => '',
		] );
		$this->add_control( 'quote_role', [
			'label' => __( 'Role', 'luwipress-emerald' ),
			'type'  => Controls_Manager::TEXT,
			'default' => '',
		] );
		$this->add_control( 'quote_text', [
			'label' => __( 'Quote', 'luwipress-emerald' ),
			'type'  => Controls_Manager::TEXTAREA,
			'rows'  => 2,
			'default' => '',
		] );

		$this->end_controls_section();
	}

	protected function render_heading( $raw ) {
		$out = esc_html( $raw );
		$out = preg_replace_callback(
			'/\[em\](.*?)\[\/em\]/u',
			static function ( $m ) { return '<em>' . $m[1] . '</em>'; },
			$out
		);
		$out = nl2br( $out );
		return $out;
	}

	protected function render() {
		$s = $this->get_settings_for_display();

		$eyebrow   = trim( (string) ( $s['eyebrow'] ?? '' ) );
		$heading   = trim( (string) ( $s['heading'] ?? '' ) );
		$lead      = trim( (string) ( $s['lead']    ?? '' ) );
		$cta1_lbl  = trim( (string) ( $s['cta1_label'] ?? '' ) );
		$cta1_url  = $s['cta1_url']['url'] ?? '';
		$cta1_ext  = ! empty( $s['cta1_url']['is_external'] );
		$cta2_lbl  = trim( (string) ( $s['cta2_label'] ?? '' ) );
		$cta2_url  = $s['cta2_url']['url'] ?? '';
		$cta2_ext  = ! empty( $s['cta2_url']['is_external'] );

		$stats     = is_array( $s['stats'] ?? null ) ? $s['stats'] : [];
		$image     = $s['image']['url'] ?? '';
		$gf        = $s['image_grad_from'] ?? '#3d2f1f';
		$gt        = $s['image_grad_to']   ?? '#7a5a2c';
		$pill      = trim( (string) ( $s['pill_text'] ?? '' ) );
		$tag_eb    = trim( (string) ( $s['tag_eyebrow'] ?? '' ) );
		$tag_tt    = trim( (string) ( $s['tag_title']   ?? '' ) );

		$q_avatar  = $s['quote_avatar']['url'] ?? '';
		$q_initial = trim( (string) ( $s['quote_initial'] ?? '' ) );
		$q_name    = trim( (string) ( $s['quote_name']    ?? '' ) );
		$q_role    = trim( (string) ( $s['quote_role']    ?? '' ) );
		$q_text    = trim( (string) ( $s['quote_text']    ?? '' ) );
		?>
		<div class="lwp-hero">
			<div class="lwp-hero__copy">
				<?php if ( $eyebrow ) : ?>
					<span class="lwp-hero__eyebrow">— <?php echo esc_html( $eyebrow ); ?></span>
				<?php endif; ?>
				<?php if ( $heading ) : ?>
					<h1 class="lwp-hero__h1"><?php echo $this->render_heading( $heading ); ?></h1>
				<?php endif; ?>
				<?php if ( $lead ) : ?>
					<p class="lwp-hero__lead"><?php echo esc_html( $lead ); ?></p>
				<?php endif; ?>
				<?php if ( ( $cta1_lbl && $cta1_url ) || ( $cta2_lbl && $cta2_url ) ) : ?>
					<div class="lwp-hero__cta">
						<?php if ( $cta1_lbl && $cta1_url ) : ?>
							<a class="lwp-hero__cta-primary" href="<?php echo esc_url( $cta1_url ); ?>"
								<?php echo $cta1_ext ? ' target="_blank" rel="noopener"' : ''; ?>>
								<?php echo esc_html( $cta1_lbl ); ?>
							</a>
						<?php endif; ?>
						<?php if ( $cta2_lbl && $cta2_url ) : ?>
							<a class="lwp-hero__cta-link" href="<?php echo esc_url( $cta2_url ); ?>"
								<?php echo $cta2_ext ? ' target="_blank" rel="noopener"' : ''; ?>>
								<?php echo esc_html( $cta2_lbl ); ?>
							</a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<?php if ( ! empty( $stats ) ) : ?>
					<div class="lwp-hero__stats">
						<?php foreach ( $stats as $st ) :
							$n = trim( (string) ( $st['num'] ?? '' ) );
							$l = trim( (string) ( $st['label'] ?? '' ) );
							if ( $n === '' && $l === '' ) { continue; }
							?>
							<div class="lwp-hero__stat">
								<?php if ( $n ) : ?><span class="lwp-hero__stat-num"><?php echo esc_html( $n ); ?></span><?php endif; ?>
								<?php if ( $l ) : ?><span class="lwp-hero__stat-lbl"><?php echo nl2br( esc_html( $l ) ); ?></span><?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="lwp-hero__image-side">
				<?php if ( $pill ) : ?>
					<div class="lwp-hero__pill"><?php echo esc_html( $pill ); ?></div>
				<?php endif; ?>
				<div class="lwp-hero__image"
					<?php if ( $image ) : ?>
						style="background-image: url(<?php echo esc_url( $image ); ?>); background-size: cover; background-position: center;"
					<?php else : ?>
						style="background: linear-gradient(135deg, <?php echo esc_attr( $gf ); ?>, <?php echo esc_attr( $gt ); ?>);"
					<?php endif; ?>>
					<?php if ( $tag_eb || $tag_tt ) : ?>
						<div class="lwp-hero__tag">
							<?php if ( $tag_eb ) : ?><span><?php echo esc_html( $tag_eb ); ?></span><?php endif; ?>
							<?php if ( $tag_tt ) : ?><strong><?php echo esc_html( $tag_tt ); ?></strong><?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
				<?php if ( $q_name || $q_text ) : ?>
					<div class="lwp-hero__quote">
						<div class="lwp-hero__quote-avatar">
							<?php if ( $q_avatar ) : ?>
								<img loading="lazy" decoding="async" src="<?php echo esc_url( $q_avatar ); ?>" alt="" width="48" height="48" />
							<?php else : ?>
								<span aria-hidden="true"><?php echo esc_html( $q_initial ?: '·' ); ?></span>
							<?php endif; ?>
						</div>
						<div class="lwp-hero__quote-text">
							<?php if ( $q_name ) : ?><span class="lwp-hero__quote-name"><?php echo esc_html( $q_name ); ?></span><?php endif; ?>
							<?php if ( $q_role ) : ?><span class="lwp-hero__quote-role"><?php echo esc_html( $q_role ); ?></span><?php endif; ?>
							<?php if ( $q_text ) : ?><span class="lwp-hero__quote-q"><?php echo esc_html( $q_text ); ?></span><?php endif; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
