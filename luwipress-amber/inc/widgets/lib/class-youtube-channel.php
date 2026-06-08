<?php
/**
 * Widget: YouTube Channel.
 *
 * Editorial grid of YouTube videos that open in the in-theme lightbox
 * modal (handled by `[data-lwp-yt]` in assets/js/frontend.js — no
 * navigation off-site, no extra modal script).
 *
 * Operator workflow:
 *   1. Drop the widget into a section.
 *   2. Eyebrow + heading + optional CTA copy.
 *   3. Add video items as a repeater — each row needs only the YouTube
 *      URL/ID (title, byline, duration are optional overrides).
 *   4. Channel CTA URL defaults to the Customizer "YouTube URL" setting
 *      under LuwiPress Amber → Footer → Social, so the user enters it
 *      once and every YouTube link across the site stays consistent.
 *
 * Thumbnails are pulled from `i.ytimg.com/vi/<ID>/hqdefault.jpg` so no
 * YouTube Data API key is required.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

class LuwiPress_Amber_Widget_YouTube_Channel extends Widget_Base {

	public function get_name()        { return 'lwp-youtube-channel'; }
	public function get_title()       { return __( 'YouTube Channel', 'luwipress-amber' ); }
	public function get_icon()        { return 'eicon-youtube'; }
	public function get_categories()  { return [ 'luwipress-amber' ]; }
	public function get_keywords()    { return [ 'youtube', 'video', 'channel', 'lightbox', 'gallery', 'atelier' ]; }
	public function get_style_depends() { return [ 'luwipress-amber-widgets' ]; }

	protected function register_controls() {

		/* Section: Heading */
		$this->start_controls_section( 'section_head', [ 'label' => __( 'Heading', 'luwipress-amber' ) ] );

		$this->add_control(
			'eyebrow',
			[
				'label'   => __( 'Eyebrow', 'luwipress-amber' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'From our channel', 'luwipress-amber' ),
			]
		);
		$this->add_control(
			'heading',
			[
				'label'       => __( 'Heading', 'luwipress-amber' ),
				'description' => __( 'Use a line break (Enter) for a 2-line title.', 'luwipress-amber' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => __( "Featured videos\nfrom our YouTube channel.", 'luwipress-amber' ),
			]
		);

		$this->add_control(
			'cta_label',
			[
				'label'   => __( 'CTA label', 'luwipress-amber' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'YouTube channel →', 'luwipress-amber' ),
			]
		);
		$this->add_control(
			'cta_url',
			[
				'label'       => __( 'CTA URL', 'luwipress-amber' ),
				'description' => __( 'Leave empty to pull the channel URL from Customize → LuwiPress Amber → Footer → YouTube URL.', 'luwipress-amber' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://www.youtube.com/c/YourChannel',
				'default'     => [ 'url' => '' ],
			]
		);

		$this->add_control(
			'show_subscribe',
			[
				'label'        => __( 'Show Subscribe button', 'luwipress-amber' ),
				'description'  => __( 'Only renders if a channel URL is set (here or in Customize → Footer → YouTube URL).', 'luwipress-amber' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'luwipress-amber' ),
				'label_off'    => __( 'No', 'luwipress-amber' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);
		$this->add_control(
			'subscribe_label',
			[
				'label'     => __( 'Subscribe label', 'luwipress-amber' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Subscribe', 'luwipress-amber' ),
				'condition' => [ 'show_subscribe' => 'yes' ],
			]
		);

		$this->end_controls_section();

		/* Section: Videos (repeater) */
		$this->start_controls_section( 'section_videos', [ 'label' => __( 'Videos', 'luwipress-amber' ) ] );

		$repeater = new Repeater();
		$repeater->add_control(
			'video',
			[
				'label'       => __( 'YouTube URL or ID', 'luwipress-amber' ),
				'description' => __( 'Paste the full URL (youtu.be/… or youtube.com/watch?v=…) or just the 11-char ID.', 'luwipress-amber' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'https://youtu.be/dQw4w9WgXcQ',
				'default'     => '',
			]
		);
		$repeater->add_control(
			'title',
			[
				'label'   => __( 'Title', 'luwipress-amber' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			]
		);
		$repeater->add_control(
			'byline',
			[
				'label'       => __( 'Byline', 'luwipress-amber' ),
				'description' => __( 'e.g. "Channel name · 14k views"', 'luwipress-amber' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
			]
		);
		$repeater->add_control(
			'duration',
			[
				'label'       => __( 'Duration badge', 'luwipress-amber' ),
				'description' => __( 'Optional. Shown as a small badge on the thumbnail (e.g. "3:42"). Leave empty to hide.', 'luwipress-amber' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
			]
		);
		$repeater->add_control(
			'thumb_quality',
			[
				'label'   => __( 'Thumbnail quality', 'luwipress-amber' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'hq',
				'options' => [
					'hq'      => __( 'HQ (480×360)', 'luwipress-amber' ),
					'mq'      => __( 'Standard (320×180)', 'luwipress-amber' ),
					'maxres'  => __( 'Maxres (1280×720, may 404 on old videos)', 'luwipress-amber' ),
				],
			]
		);

		$this->add_control(
			'videos',
			[
				'label'       => __( 'Videos', 'luwipress-amber' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title || video || "Video" }}}',
				'default'     => [
					[ 'video' => '', 'title' => __( 'Featured video 1', 'luwipress-amber' ), 'byline' => '', 'duration' => '', 'thumb_quality' => 'hq' ],
					[ 'video' => '', 'title' => __( 'Featured video 2', 'luwipress-amber' ), 'byline' => '', 'duration' => '', 'thumb_quality' => 'hq' ],
					[ 'video' => '', 'title' => __( 'Featured video 3', 'luwipress-amber' ), 'byline' => '', 'duration' => '', 'thumb_quality' => 'hq' ],
					[ 'video' => '', 'title' => __( 'Featured video 4', 'luwipress-amber' ), 'byline' => '', 'duration' => '', 'thumb_quality' => 'hq' ],
				],
			]
		);

		$this->add_control(
			'columns',
			[
				'label'   => __( 'Columns (desktop)', 'luwipress-amber' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '4',
				'options' => [ '2' => '2', '3' => '3', '4' => '4' ],
			]
		);

		$this->end_controls_section();

		/* Section: Style */
		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-amber' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
		$this->add_control(
			'card_bg',
			[
				'label'     => __( 'Card background', 'luwipress-amber' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [ '{{WRAPPER}} .lwp-ytc-card' => 'background: {{VALUE}};' ],
			]
		);
		$this->add_control(
			'card_border',
			[
				'label'     => __( 'Card border', 'luwipress-amber' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [ '{{WRAPPER}} .lwp-ytc-card' => 'border-color: {{VALUE}};' ],
			]
		);
		$this->add_control(
			'title_color',
			[
				'label'     => __( 'Title color', 'luwipress-amber' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [ '{{WRAPPER}} .lwp-ytc-card h4' => 'color: {{VALUE}};' ],
			]
		);
		$this->end_controls_section();
	}

	/**
	 * Extract a YouTube video ID from a raw URL or 11-char ID.
	 * Returns '' on miss.
	 */
	protected function extract_id( $raw ) {
		$raw = trim( (string) $raw );
		if ( $raw === '' ) {
			return '';
		}
		// Plain ID.
		if ( preg_match( '/^[A-Za-z0-9_-]{6,15}$/', $raw ) ) {
			return $raw;
		}
		// URL.
		$parts = wp_parse_url( $raw );
		if ( empty( $parts['host'] ) ) {
			return '';
		}
		$host = strtolower( $parts['host'] );
		$path = $parts['path'] ?? '';
		if ( strpos( $host, 'youtu.be' ) !== false ) {
			$id = ltrim( $path, '/' );
			$id = explode( '/', $id )[0];
			return preg_match( '/^[A-Za-z0-9_-]{6,15}$/', $id ) ? $id : '';
		}
		if ( strpos( $host, 'youtube' ) !== false ) {
			if ( strpos( $path, '/embed/' ) === 0 ) {
				$id = substr( $path, 7 );
				$id = explode( '/', $id )[0];
				return preg_match( '/^[A-Za-z0-9_-]{6,15}$/', $id ) ? $id : '';
			}
			if ( strpos( $path, '/shorts/' ) === 0 ) {
				$id = substr( $path, 8 );
				$id = explode( '/', $id )[0];
				return preg_match( '/^[A-Za-z0-9_-]{6,15}$/', $id ) ? $id : '';
			}
			if ( ! empty( $parts['query'] ) ) {
				parse_str( $parts['query'], $q );
				if ( ! empty( $q['v'] ) && preg_match( '/^[A-Za-z0-9_-]{6,15}$/', $q['v'] ) ) {
					return $q['v'];
				}
			}
		}
		return '';
	}

	/**
	 * Resolve the channel CTA URL — widget setting wins, else Customizer
	 * social YouTube URL, else empty (hide the CTA).
	 */
	protected function resolve_channel_url( $widget_url ) {
		$widget_url = trim( (string) $widget_url );
		if ( $widget_url !== '' ) {
			return $widget_url;
		}
		$mod = get_theme_mod( 'luwipress_amber_social_youtube', '' );
		return trim( (string) $mod );
	}

	protected function render() {
		$s = $this->get_settings_for_display();

		$videos = is_array( $s['videos'] ?? null ) ? $s['videos'] : [];

		// Filter to repeater rows that resolve to a real YouTube ID.
		$resolved = array();
		foreach ( $videos as $item ) {
			$id = $this->extract_id( $item['video'] ?? '' );
			if ( $id ) {
				$item['_id'] = $id;
				$resolved[]  = $item;
			}
		}

		// In the Elementor editor or for admins on the live site, always
		// render the section with a configure-prompt when no videos
		// resolved. On the public frontend, hide the section entirely.
		$is_edit_mode = class_exists( '\\Elementor\\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode();
		$is_preview   = function_exists( 'is_user_logged_in' ) && is_user_logged_in() && current_user_can( 'edit_theme_options' );
		if ( empty( $resolved ) && ! $is_edit_mode && ! $is_preview ) {
			return;
		}

		$eyebrow   = trim( (string) ( $s['eyebrow'] ?? '' ) );
		$heading   = trim( (string) ( $s['heading'] ?? '' ) );
		$cta_label = trim( (string) ( $s['cta_label'] ?? '' ) );
		$cta_url   = $this->resolve_channel_url( $s['cta_url']['url'] ?? '' );
		$cta_external = ! empty( $s['cta_url']['is_external'] );
		$columns   = in_array( $s['columns'] ?? '4', [ '2', '3', '4' ], true ) ? $s['columns'] : '4';

		$show_subscribe  = ( $s['show_subscribe'] ?? 'yes' ) === 'yes' && $cta_url !== '';
		$subscribe_label = trim( (string) ( $s['subscribe_label'] ?? '' ) ) ?: __( 'Subscribe', 'luwipress-amber' );
		// YouTube one-click subscribe-confirm URL — appends ?sub_confirmation=1.
		$subscribe_url = $cta_url ? add_query_arg( 'sub_confirmation', '1', $cta_url ) : '';
		?>
		<section class="lwp-ytc" data-columns="<?php echo esc_attr( $columns ); ?>">
			<?php if ( $eyebrow || $heading || ( $cta_label && $cta_url ) || $show_subscribe ) : ?>
				<header class="lwp-ytc-head">
					<div class="lwp-ytc-head__copy">
						<?php if ( $eyebrow ) : ?>
							<span class="lwp-ytc-eyebrow">— <?php echo esc_html( $eyebrow ); ?></span>
						<?php endif; ?>
						<?php if ( $heading ) : ?>
							<h2 class="lwp-ytc-title"><?php echo nl2br( esc_html( $heading ) ); ?></h2>
						<?php endif; ?>
					</div>
					<div class="lwp-ytc-head__actions">
						<?php if ( $show_subscribe ) : ?>
							<a class="lwp-ytc-sub"
								href="<?php echo esc_url( $subscribe_url ); ?>"
								target="_blank" rel="noopener"
								aria-label="<?php echo esc_attr( sprintf( __( '%s on YouTube', 'luwipress-amber' ), $subscribe_label ) ); ?>">
								<span class="lwp-ytc-sub__icon" aria-hidden="true">
									<svg viewBox="0 0 28 20" width="22" height="16" focusable="false">
										<path d="M27.4 3.1c-.3-1.2-1.3-2.2-2.5-2.5C22.7 0 14 0 14 0S5.3 0 3.1.6C1.9.9.9 1.9.6 3.1 0 5.3 0 10 0 10s0 4.7.6 6.9c.3 1.2 1.3 2.2 2.5 2.5C5.3 20 14 20 14 20s8.7 0 10.9-.6c1.2-.3 2.2-1.3 2.5-2.5.6-2.2.6-6.9.6-6.9s0-4.7-.6-6.9z" fill="currentColor"/>
										<path d="M11.2 14.3 18.4 10l-7.2-4.3v8.6z" fill="#fff"/>
									</svg>
								</span>
								<span class="lwp-ytc-sub__label"><?php echo esc_html( $subscribe_label ); ?></span>
								<span class="lwp-ytc-sub__pulse" aria-hidden="true"></span>
							</a>
						<?php endif; ?>
						<?php if ( $cta_label && $cta_url ) : ?>
							<a class="lwp-ytc-cta" href="<?php echo esc_url( $cta_url ); ?>"
								<?php echo $cta_external ? ' target="_blank" rel="noopener"' : ''; ?>>
								<?php echo esc_html( $cta_label ); ?>
							</a>
						<?php endif; ?>
					</div>
				</header>
			<?php endif; ?>

			<?php if ( empty( $resolved ) ) : ?>
				<div class="lwp-ytc-placeholder" role="note">
					<strong><?php esc_html_e( 'No YouTube videos configured yet.', 'luwipress-amber' ); ?></strong>
					<span><?php esc_html_e( 'Open this widget and paste a YouTube URL or video ID into each Videos row. This notice is only visible to logged-in editors.', 'luwipress-amber' ); ?></span>
				</div>
			<?php else : ?>
			<div class="lwp-ytc-grid">
				<?php foreach ( $resolved as $item ) :
					$id = $item['_id'];
					$title    = trim( (string) ( $item['title'] ?? '' ) );
					$byline   = trim( (string) ( $item['byline'] ?? '' ) );
					$duration = trim( (string) ( $item['duration'] ?? '' ) );
					$quality  = in_array( $item['thumb_quality'] ?? 'hq', [ 'hq', 'mq', 'maxres' ], true ) ? $item['thumb_quality'] : 'hq';
					$thumb    = sprintf(
						'https://i.ytimg.com/vi/%s/%s.jpg',
						rawurlencode( $id ),
						$quality === 'maxres' ? 'maxresdefault' : ( $quality === 'mq' ? 'mqdefault' : 'hqdefault' )
					);
					$watch    = 'https://www.youtube.com/watch?v=' . rawurlencode( $id );
					$aria     = $title !== '' ? sprintf( __( 'Play video — %s', 'luwipress-amber' ), $title ) : __( 'Play video', 'luwipress-amber' );
					?>
					<a class="lwp-ytc-card"
						href="<?php echo esc_url( $watch ); ?>"
						data-lwp-yt="<?php echo esc_attr( $id ); ?>"
						aria-label="<?php echo esc_attr( $aria ); ?>">
						<span class="lwp-ytc-thumb">
							<img loading="lazy" decoding="async"
								src="<?php echo esc_url( $thumb ); ?>"
								alt=""
								width="480" height="360" />
							<span class="lwp-ytc-play" aria-hidden="true">▶</span>
							<?php if ( $duration !== '' ) : ?>
								<span class="lwp-ytc-time"><?php echo esc_html( $duration ); ?></span>
							<?php endif; ?>
						</span>
						<?php if ( $title || $byline ) : ?>
							<span class="lwp-ytc-meta">
								<?php if ( $title ) : ?>
									<h4><?php echo esc_html( $title ); ?></h4>
								<?php endif; ?>
								<?php if ( $byline ) : ?>
									<span class="lwp-ytc-byline"><?php echo esc_html( $byline ); ?></span>
								<?php endif; ?>
							</span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</section>
		<?php
	}
}
