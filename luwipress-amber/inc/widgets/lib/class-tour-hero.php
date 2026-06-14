<?php
/**
 * Widget: Tour Hero (lwp-tour-hero).
 *
 * The cinematic travel hero — video/scene background, eyebrow, accented headline,
 * lead, two CTAs and an optional "tour search" bar (Experience + Date + Guests →
 * the tour page with date/guests prefilled). Replaces the hand-pasted Elementor
 * HTML widget so every text field is a real, translatable control (works with
 * LuwiPress /elementor/translate) and the hero is reusable across travel themes.
 *
 * Markup mirrors the original `.hero` block, so it inherits amber.css `.hero` /
 * `.bookbar` styling and the hero-search.js enhancer (which targets
 * `.hero .bookbar`) with zero extra CSS.
 *
 * @package LuwiPress_Amber
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class LuwiPress_Amber_Widget_Tour_Hero extends Widget_Base {

	public function get_name()          { return 'lwp-tour-hero'; }
	public function get_title()         { return __( 'Tour Hero', 'luwipress-amber' ); }
	public function get_icon()          { return 'eicon-banner'; }
	public function get_categories()    { return [ 'luwipress-amber' ]; }
	public function get_keywords()      { return [ 'hero', 'banner', 'tour', 'travel', 'search', 'video' ]; }
	public function get_style_depends() { return [ 'luwipress-amber-widgets' ]; }
	public function get_script_depends() { return [ 'luwipress-amber-hero-search' ]; }

	protected function register_controls() {

		/* ── Content ── */
		$this->start_controls_section( 'sec_content', [ 'label' => __( 'Content', 'luwipress-amber' ) ] );
		$this->add_control( 'eyebrow', [
			'label'   => __( 'Eyebrow', 'luwipress-amber' ),
			'type'    => Controls_Manager::TEXT,
			'default' => __( 'Welcome to Fly By Deniz', 'luwipress-amber' ),
		] );
		$this->add_control( 'heading', [
			'label'       => __( 'Headline (HTML allowed)', 'luwipress-amber' ),
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 3,
			'default'     => __( 'Unforgettable Dubai, <em>effortlessly</em> yours.', 'luwipress-amber' ),
			'description' => __( 'Wrap a word in <em>…</em> for the gold italic accent.', 'luwipress-amber' ),
		] );
		$this->add_control( 'sub', [
			'label'   => __( 'Sub text', 'luwipress-amber' ),
			'type'    => Controls_Manager::TEXTAREA,
			'rows'    => 3,
			'default' => __( 'Curated adventures and seamless travel — visas, transfers, private tours and the moments in between, handled end to end by your Dubai concierge.', 'luwipress-amber' ),
		] );
		$this->end_controls_section();

		/* ── Buttons ── */
		$this->start_controls_section( 'sec_cta', [ 'label' => __( 'Buttons', 'luwipress-amber' ) ] );
		$this->add_control( 'cta1_label', [
			'label'   => __( 'Primary button — label', 'luwipress-amber' ),
			'type'    => Controls_Manager::TEXT,
			'default' => __( 'Plan My Trip', 'luwipress-amber' ),
		] );
		$this->add_control( 'cta1_url', [
			'label'   => __( 'Primary button — link', 'luwipress-amber' ),
			'type'    => Controls_Manager::URL,
			'default' => [ 'url' => '#contact' ],
		] );
		$this->add_control( 'cta2_label', [
			'label'   => __( 'Secondary button — label', 'luwipress-amber' ),
			'type'    => Controls_Manager::TEXT,
			'default' => __( 'Explore Experiences', 'luwipress-amber' ),
		] );
		$this->add_control( 'cta2_url', [
			'label'   => __( 'Secondary button — link', 'luwipress-amber' ),
			'type'    => Controls_Manager::URL,
			'default' => [ 'url' => '#activities' ],
		] );
		$this->end_controls_section();

		/* ── Background ── */
		$this->start_controls_section( 'sec_bg', [ 'label' => __( 'Background', 'luwipress-amber' ) ] );
		$this->add_control( 'video_url', [
			'label'       => __( 'Background video URL (.mp4)', 'luwipress-amber' ),
			'type'        => Controls_Manager::URL,
			'options'     => false,
			'default'     => [ 'url' => '' ],
			'description' => __( 'Optional. Autoplays muted + looped behind the scrim.', 'luwipress-amber' ),
		] );
		$this->add_control( 'poster', [
			'label' => __( 'Video poster / fallback image', 'luwipress-amber' ),
			'type'  => Controls_Manager::MEDIA,
		] );
		$this->end_controls_section();

		/* ── Tour search bar ── */
		$this->start_controls_section( 'sec_search', [ 'label' => __( 'Tour search bar', 'luwipress-amber' ) ] );
		$this->add_control( 'show_search', [
			'label'   => __( 'Show tour search bar', 'luwipress-amber' ),
			'type'    => Controls_Manager::SWITCHER,
			'default' => 'yes',
		] );
		$this->add_control( 'label_experience', [
			'label' => __( 'Label — Experience', 'luwipress-amber' ),
			'type'  => Controls_Manager::TEXT, 'default' => __( 'Experience', 'luwipress-amber' ),
			'condition' => [ 'show_search' => 'yes' ],
		] );
		$this->add_control( 'label_date', [
			'label' => __( 'Label — Date', 'luwipress-amber' ),
			'type'  => Controls_Manager::TEXT, 'default' => __( 'Date', 'luwipress-amber' ),
			'condition' => [ 'show_search' => 'yes' ],
		] );
		$this->add_control( 'label_guests', [
			'label' => __( 'Label — Guests', 'luwipress-amber' ),
			'type'  => Controls_Manager::TEXT, 'default' => __( 'Guests', 'luwipress-amber' ),
			'condition' => [ 'show_search' => 'yes' ],
		] );
		$this->end_controls_section();
	}

	private function cta_attrs( $url ) {
		if ( empty( $url['url'] ) ) { return ''; }
		$a = ' href="' . esc_url( $url['url'] ) . '"';
		if ( ! empty( $url['is_external'] ) ) { $a .= ' target="_blank" rel="noopener"'; }
		if ( ! empty( $url['nofollow'] ) )    { $a .= ' rel="nofollow"'; }
		return $a;
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$video = $s['video_url']['url'] ?? '';
		$poster = $s['poster']['url'] ?? '';
		$arrow = '<svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
		?>
		<section class="hero">
			<div class="scene" style="position:absolute;inset:0;">
				<div class="scene-sky"></div>
				<div class="scene-sun"></div>
				<div class="scene-dunes" style="position:absolute;inset:0;"></div>
				<?php if ( $video ) : ?>
					<video class="scene-video" autoplay muted loop playsinline preload="auto"<?php echo $poster ? ' poster="' . esc_url( $poster ) . '"' : ''; ?>>
						<source src="<?php echo esc_url( $video ); ?>" type="video/mp4">
					</video>
				<?php elseif ( $poster ) : ?>
					<div class="scene-dunes" style="position:absolute;inset:0;background:url(<?php echo esc_url( $poster ); ?>) center/cover;"></div>
				<?php endif; ?>
				<div class="scene-scrim"></div>
			</div>
			<div class="wrap">
				<?php if ( ! empty( $s['eyebrow'] ) ) : ?>
					<span class="eyebrow"><?php echo esc_html( $s['eyebrow'] ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $s['heading'] ) ) : ?>
					<h1><?php echo wp_kses_post( $s['heading'] ); ?></h1>
				<?php endif; ?>
				<?php if ( ! empty( $s['sub'] ) ) : ?>
					<p class="sub"><?php echo esc_html( $s['sub'] ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $s['cta1_label'] ) || ! empty( $s['cta2_label'] ) ) : ?>
					<div class="cta-row">
						<?php if ( ! empty( $s['cta1_label'] ) ) : ?>
							<a<?php echo $this->cta_attrs( $s['cta1_url'] ?? [] ); ?> class="btn btn--amber"><?php echo esc_html( $s['cta1_label'] ); ?> <?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
						<?php endif; ?>
						<?php if ( ! empty( $s['cta2_label'] ) ) : ?>
							<a<?php echo $this->cta_attrs( $s['cta2_url'] ?? [] ); ?> class="btn btn--ghost"><?php echo esc_html( $s['cta2_label'] ); ?></a>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( 'yes' === ( $s['show_search'] ?? 'yes' ) ) : ?>
					<!-- tour search — hero-search.js upgrades .val nodes into select / date / guests -->
					<div class="bookbar">
						<div class="field">
							<div class="lbl"><?php echo esc_html( $s['label_experience'] ?: __( 'Experience', 'luwipress-amber' ) ); ?></div>
							<div class="val"><?php esc_html_e( 'Choose a tour', 'luwipress-amber' ); ?></div>
						</div>
						<div class="field">
							<div class="lbl"><?php echo esc_html( $s['label_date'] ?: __( 'Date', 'luwipress-amber' ) ); ?></div>
							<div class="val"><?php esc_html_e( 'Add dates', 'luwipress-amber' ); ?></div>
						</div>
						<div class="field">
							<div class="lbl"><?php echo esc_html( $s['label_guests'] ?: __( 'Guests', 'luwipress-amber' ) ); ?></div>
							<div class="val"><?php esc_html_e( '2 travellers', 'luwipress-amber' ); ?></div>
						</div>
						<button class="go" type="button" aria-label="<?php esc_attr_e( 'Search', 'luwipress-amber' ); ?>">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
						</button>
					</div>
				<?php endif; ?>
			</div>
			<div class="scroll-hint"><span><?php esc_html_e( 'Scroll', 'luwipress-amber' ); ?></span><span class="line"></span></div>
		</section>
		<?php
	}
}
