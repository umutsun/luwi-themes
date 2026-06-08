<?php
/**
 * Widget: Instagram Channel.
 *
 * Editorial grid of Instagram posts that link out to the original post.
 * Sister widget to `lwp-youtube-channel` — mirrors the operator flow:
 *
 *   1. Drop the widget into a section.
 *   2. Eyebrow + heading + optional CTA copy.
 *   3. Add post items as a repeater — each row needs a post URL, a
 *      square thumbnail (uploaded to the Media library), and an
 *      optional caption + meta line.
 *   4. CTA + Follow URL default to the Customizer "Instagram URL"
 *      setting under LuwiPress Onyx → Footer → Social (also exposed in
 *      LuwiPress admin → Theme → Settings → Social).
 *
 * No Instagram API call is made — the widget is purely a curated grid
 * that links out to instagram.com. Operators who want auto-fetched
 * latest posts can swap in a third-party feed plugin later; this widget
 * gives them control + zero API friction by default.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

class LuwiPress_Onyx_Widget_Instagram_Channel extends Widget_Base {

	public function get_name()        { return 'lwp-instagram-channel'; }
	public function get_title()       { return __( 'Instagram Channel', 'luwipress-onyx' ); }
	public function get_icon()        { return 'eicon-instagram'; }
	public function get_categories()  { return [ 'luwipress-onyx' ]; }
	public function get_keywords()    { return [ 'instagram', 'social', 'gram', 'photos', 'feed', 'ig' ]; }
	public function get_style_depends() { return [ 'luwipress-onyx-widgets' ]; }

	protected function register_controls() {

		/* Section: Heading */
		$this->start_controls_section( 'section_head', [ 'label' => __( 'Heading', 'luwipress-onyx' ) ] );

		$this->add_control(
			'eyebrow',
			[
				'label'   => __( 'Eyebrow', 'luwipress-onyx' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Follow us', 'luwipress-onyx' ),
			]
		);
		$this->add_control(
			'heading',
			[
				'label'   => __( 'Heading', 'luwipress-onyx' ),
				'description' => __( 'Use Enter for a 2-line title.', 'luwipress-onyx' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 2,
				'default' => __( "Latest posts\non Instagram.", 'luwipress-onyx' ),
			]
		);

		$this->add_control(
			'cta_label',
			[
				'label'   => __( 'CTA label', 'luwipress-onyx' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Instagram profile →', 'luwipress-onyx' ),
			]
		);
		$this->add_control(
			'cta_url',
			[
				'label'       => __( 'CTA URL', 'luwipress-onyx' ),
				'description' => __( 'Leave empty to pull the profile URL from Customize → LuwiPress Onyx → Footer → Instagram URL (or LuwiPress admin → Theme → Settings → Social).', 'luwipress-onyx' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://www.instagram.com/yourhandle/',
				'default'     => [ 'url' => '' ],
			]
		);

		$this->add_control(
			'show_follow',
			[
				'label'        => __( 'Show Follow button', 'luwipress-onyx' ),
				'description'  => __( 'Only renders if a profile URL is set (here or in Customize → Footer → Instagram URL).', 'luwipress-onyx' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'luwipress-onyx' ),
				'label_off'    => __( 'No', 'luwipress-onyx' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);
		$this->add_control(
			'follow_label',
			[
				'label'     => __( 'Follow label', 'luwipress-onyx' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Follow', 'luwipress-onyx' ),
				'condition' => [ 'show_follow' => 'yes' ],
			]
		);

		$this->end_controls_section();

		/* Section: Posts (repeater) */
		$this->start_controls_section( 'section_posts', [ 'label' => __( 'Posts', 'luwipress-onyx' ) ] );

		$repeater = new Repeater();
		$repeater->add_control(
			'thumbnail',
			[
				'label'   => __( 'Thumbnail (square)', 'luwipress-onyx' ),
				'description' => __( 'Upload a square crop (recommended 600×600 or 1080×1080). Falls back to a solid tile if blank.', 'luwipress-onyx' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => [ 'url' => '' ],
			]
		);
		$repeater->add_control(
			'post_url',
			[
				'label'       => __( 'Instagram post URL', 'luwipress-onyx' ),
				'description' => __( 'Full post URL, e.g. https://www.instagram.com/p/AbC123XyZ/', 'luwipress-onyx' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'https://www.instagram.com/p/AbC123XyZ/',
				'default'     => '',
			]
		);
		$repeater->add_control(
			'caption',
			[
				'label'       => __( 'Caption', 'luwipress-onyx' ),
				'description' => __( 'Short overlay text shown on hover.', 'luwipress-onyx' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
			]
		);
		$repeater->add_control(
			'meta',
			[
				'label'       => __( 'Meta line', 'luwipress-onyx' ),
				'description' => __( 'Optional small badge (e.g. "Reel · 12k likes" or "📷 Photo").', 'luwipress-onyx' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
			]
		);
		$repeater->add_control(
			'kind',
			[
				'label'   => __( 'Post type icon', 'luwipress-onyx' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'post',
				'options' => [
					'post'     => __( 'Photo', 'luwipress-onyx' ),
					'reel'     => __( 'Reel', 'luwipress-onyx' ),
					'carousel' => __( 'Carousel', 'luwipress-onyx' ),
				],
			]
		);

		$this->add_control(
			'posts',
			[
				'label'       => __( 'Posts', 'luwipress-onyx' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ caption || post_url || "Post" }}}',
				'default'     => [
					[ 'post_url' => '', 'caption' => '', 'meta' => __( 'Photo', 'luwipress-onyx' ),    'kind' => 'post',     'thumbnail' => [ 'url' => '' ] ],
					[ 'post_url' => '', 'caption' => '', 'meta' => __( 'Reel', 'luwipress-onyx' ),     'kind' => 'reel',     'thumbnail' => [ 'url' => '' ] ],
					[ 'post_url' => '', 'caption' => '', 'meta' => __( 'Carousel', 'luwipress-onyx' ), 'kind' => 'carousel', 'thumbnail' => [ 'url' => '' ] ],
					[ 'post_url' => '', 'caption' => '', 'meta' => __( 'Photo', 'luwipress-onyx' ),    'kind' => 'post',     'thumbnail' => [ 'url' => '' ] ],
				],
			]
		);

		$this->add_control(
			'columns',
			[
				'label'   => __( 'Columns (desktop)', 'luwipress-onyx' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '4',
				'options' => [ '2' => '2', '3' => '3', '4' => '4', '6' => '6' ],
			]
		);

		$this->end_controls_section();

		/* Section: Style */
		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-onyx' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
		$this->add_control(
			'title_color',
			[
				'label'     => __( 'Title color', 'luwipress-onyx' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [ '{{WRAPPER}} .lwp-igc-title' => 'color: {{VALUE}};' ],
			]
		);
		$this->end_controls_section();
	}

	/**
	 * Resolve the profile/CTA URL — widget setting wins, else Customizer
	 * social Instagram URL, else empty (hide the CTA + Follow button).
	 */
	protected function resolve_profile_url( $widget_url ) {
		$widget_url = trim( (string) $widget_url );
		if ( $widget_url !== '' ) {
			return $widget_url;
		}
		$mod = get_theme_mod( 'luwipress_onyx_social_instagram', '' );
		return trim( (string) $mod );
	}

	/**
	 * Strip the @ + trailing slash from an instagram URL or handle string.
	 * Used to display the @handle on the Follow button.
	 */
	protected function handle_from_url( $url ) {
		$url = trim( (string) $url );
		if ( $url === '' ) {
			return '';
		}
		$parts = wp_parse_url( $url );
		if ( empty( $parts['path'] ) ) {
			return '';
		}
		$first = explode( '/', trim( $parts['path'], '/' ) )[0] ?? '';
		return $first !== '' ? '@' . ltrim( $first, '@' ) : '';
	}

	protected function render() {
		$s = $this->get_settings_for_display();

		$posts = is_array( $s['posts'] ?? null ) ? $s['posts'] : [];

		$resolved = array();
		foreach ( $posts as $item ) {
			$url = esc_url_raw( $item['post_url'] ?? '' );
			$thumb = esc_url_raw( $item['thumbnail']['url'] ?? '' );
			if ( $url !== '' || $thumb !== '' ) {
				$resolved[] = $item;
			}
		}

		$is_edit_mode = class_exists( '\\Elementor\\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode();
		$is_preview   = function_exists( 'is_user_logged_in' ) && is_user_logged_in() && current_user_can( 'edit_theme_options' );
		if ( empty( $resolved ) && ! $is_edit_mode && ! $is_preview ) {
			return;
		}

		$eyebrow      = trim( (string) ( $s['eyebrow'] ?? '' ) );
		$heading      = trim( (string) ( $s['heading'] ?? '' ) );
		$cta_label    = trim( (string) ( $s['cta_label'] ?? '' ) );
		$cta_url      = $this->resolve_profile_url( $s['cta_url']['url'] ?? '' );
		$cta_external = ! empty( $s['cta_url']['is_external'] );
		$columns      = in_array( $s['columns'] ?? '4', [ '2', '3', '4', '6' ], true ) ? $s['columns'] : '4';

		$show_follow  = ( $s['show_follow'] ?? 'yes' ) === 'yes' && $cta_url !== '';
		$follow_label = trim( (string) ( $s['follow_label'] ?? '' ) ) ?: __( 'Follow', 'luwipress-onyx' );
		$handle       = $this->handle_from_url( $cta_url );

		$kind_icons = array(
			'post'     => '◻',
			'reel'     => '▶',
			'carousel' => '▤',
		);
		?>
		<section class="lwp-igc" data-columns="<?php echo esc_attr( $columns ); ?>">
			<?php if ( $eyebrow || $heading || ( $cta_label && $cta_url ) || $show_follow ) : ?>
				<header class="lwp-igc-head">
					<div class="lwp-igc-head__copy">
						<?php if ( $eyebrow ) : ?>
							<span class="lwp-igc-eyebrow">— <?php echo esc_html( $eyebrow ); ?></span>
						<?php endif; ?>
						<?php if ( $heading ) : ?>
							<h2 class="lwp-igc-title"><?php echo nl2br( esc_html( $heading ) ); ?></h2>
						<?php endif; ?>
					</div>
					<div class="lwp-igc-head__actions">
						<?php if ( $show_follow ) : ?>
							<a class="lwp-igc-follow"
								href="<?php echo esc_url( $cta_url ); ?>"
								target="_blank" rel="noopener"
								aria-label="<?php echo esc_attr( sprintf( __( 'Follow %s on Instagram', 'luwipress-onyx' ), $handle ?: '' ) ); ?>">
								<span class="lwp-igc-follow__icon" aria-hidden="true">
									<svg viewBox="0 0 24 24" width="18" height="18" focusable="false">
										<rect x="2.5" y="2.5" width="19" height="19" rx="5" ry="5" fill="none" stroke="currentColor" stroke-width="2"/>
										<circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/>
										<circle cx="17.5" cy="6.5" r="1.3" fill="currentColor"/>
									</svg>
								</span>
								<span class="lwp-igc-follow__label">
									<?php echo esc_html( $follow_label ); ?>
									<?php if ( $handle ) : ?>
										<em class="lwp-igc-follow__handle"><?php echo esc_html( $handle ); ?></em>
									<?php endif; ?>
								</span>
							</a>
						<?php endif; ?>
						<?php if ( $cta_label && $cta_url ) : ?>
							<a class="lwp-igc-cta" href="<?php echo esc_url( $cta_url ); ?>"
								<?php echo $cta_external ? ' target="_blank" rel="noopener"' : ''; ?>>
								<?php echo esc_html( $cta_label ); ?>
							</a>
						<?php endif; ?>
					</div>
				</header>
			<?php endif; ?>

			<?php if ( empty( $resolved ) ) : ?>
				<div class="lwp-ytc-placeholder" role="note">
					<strong><?php esc_html_e( 'No Instagram posts configured yet.', 'luwipress-onyx' ); ?></strong>
					<span><?php esc_html_e( 'Open this widget and upload a square thumbnail + paste the Instagram post URL for each Posts row. This notice is only visible to logged-in editors.', 'luwipress-onyx' ); ?></span>
				</div>
			<?php else : ?>
			<div class="lwp-igc-grid">
				<?php foreach ( $resolved as $item ) :
					$url     = esc_url_raw( $item['post_url'] ?? '' );
					$thumb   = esc_url_raw( $item['thumbnail']['url'] ?? '' );
					$caption = trim( (string) ( $item['caption'] ?? '' ) );
					$meta    = trim( (string) ( $item['meta'] ?? '' ) );
					$kind    = in_array( $item['kind'] ?? 'post', [ 'post', 'reel', 'carousel' ], true ) ? $item['kind'] : 'post';
					$href    = $url !== '' ? $url : '#';
					$aria    = $caption !== '' ? sprintf( __( 'Open Instagram post — %s', 'luwipress-onyx' ), $caption ) : __( 'Open Instagram post', 'luwipress-onyx' );
					?>
					<a class="lwp-igc-tile" data-kind="<?php echo esc_attr( $kind ); ?>"
						href="<?php echo esc_url( $href ); ?>"
						<?php echo $url !== '' ? ' target="_blank" rel="noopener"' : ' aria-disabled="true"'; ?>
						aria-label="<?php echo esc_attr( $aria ); ?>">
						<span class="lwp-igc-thumb">
							<?php if ( $thumb !== '' ) : ?>
								<img loading="lazy" decoding="async"
									src="<?php echo esc_url( $thumb ); ?>"
									alt=""
									width="600" height="600" />
							<?php else : ?>
								<span class="lwp-igc-thumb__empty" aria-hidden="true">
									<?php echo esc_html( $kind_icons[ $kind ] ?? $kind_icons['post'] ); ?>
								</span>
							<?php endif; ?>
							<span class="lwp-igc-tile__overlay">
								<span class="lwp-igc-tile__kind" aria-hidden="true"><?php echo esc_html( $kind_icons[ $kind ] ?? '◻' ); ?></span>
								<?php if ( $caption ) : ?>
									<span class="lwp-igc-tile__caption"><?php echo esc_html( $caption ); ?></span>
								<?php endif; ?>
							</span>
							<?php if ( $meta ) : ?>
								<span class="lwp-igc-tile__meta"><?php echo esc_html( $meta ); ?></span>
							<?php endif; ?>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</section>
		<?php
	}
}
