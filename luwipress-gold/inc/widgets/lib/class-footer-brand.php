<?php
/**
 * Widget: Footer Brand.
 *
 * Logo + tagline + contact info + social icons row.
 * Used in the footer's leftmost column.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

class LuwiPress_Gold_Widget_Footer_Brand extends Widget_Base {

	public function get_name()        { return 'lwp-footer-brand'; }
	public function get_title()       { return __( 'Footer Brand', 'luwipress-gold' ); }
	public function get_icon()        { return 'eicon-site-identity'; }
	public function get_categories()  { return [ 'luwipress-gold' ]; }
	public function get_keywords()    { return [ 'footer', 'brand', 'logo', 'social', 'contact' ]; }
	public function get_style_depends() { return [ 'luwipress-gold-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_logo', [ 'label' => __( 'Logo', 'luwipress-gold' ) ] );

		$this->add_control(
			'logo_image',
			[
				'label'   => __( 'Logo image (optional)', 'luwipress-gold' ),
				'description' => __( 'If set, replaces the text logo.', 'luwipress-gold' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => [ 'url' => '' ],
			]
		);
		$this->add_control(
			'logo_text',
			[
				'label'   => __( 'Logo text', 'luwipress-gold' ),
				'type'    => Controls_Manager::TEXT,
				'default' => 'tapadum',
			]
		);
		$this->add_control(
			'accent_index',
			[
				'label'   => __( 'Accent character position (0-indexed)', 'luwipress-gold' ),
				'description' => __( 'Which character to italicise + accent-tint. -1 = none.', 'luwipress-gold' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 5,
				'min'     => -1,
			]
		);
		$this->add_control(
			'logo_url',
			[
				'label'   => __( 'Logo link', 'luwipress-gold' ),
				'type'    => Controls_Manager::URL,
				'default' => [ 'url' => home_url( '/' ) ],
			]
		);

		$this->end_controls_section();

		/* Tagline */
		$this->start_controls_section( 'section_tag', [ 'label' => __( 'Tagline', 'luwipress-gold' ) ] );
		$this->add_control(
			'tagline',
			[
				'label'   => __( 'Tagline / description', 'luwipress-gold' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 3,
				'default' => __( 'A short brand line that explains what your store does and where you ship from.', 'luwipress-gold' ),
			]
		);
		$this->end_controls_section();

		/* Contact */
		$this->start_controls_section( 'section_contact', [ 'label' => __( 'Contact', 'luwipress-gold' ) ] );
		$this->add_control( 'phone',   [ 'label' => __( 'Phone', 'luwipress-gold' ),   'type' => Controls_Manager::TEXT, 'default' => '' ] );
		$this->add_control( 'email',   [ 'label' => __( 'Email', 'luwipress-gold' ),   'type' => Controls_Manager::TEXT, 'default' => '' ] );
		$this->add_control( 'address', [ 'label' => __( 'Address line', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT, 'default' => '' ] );
		$this->end_controls_section();

		/* Social */
		$this->start_controls_section( 'section_social', [ 'label' => __( 'Social icons', 'luwipress-gold' ) ] );

		$rep = new Repeater();
		$rep->add_control(
			'network',
			[
				'label'   => __( 'Network', 'luwipress-gold' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'instagram',
				'options' => [
					'instagram' => 'Instagram',
					'youtube'   => 'YouTube',
					'spotify'   => 'Spotify',
					'whatsapp'  => 'WhatsApp',
					'facebook'  => 'Facebook',
					'tiktok'    => 'TikTok',
					'x'         => 'X / Twitter',
					'pinterest' => 'Pinterest',
				],
			]
		);
		$rep->add_control(
			'url',
			[ 'label' => __( 'URL', 'luwipress-gold' ), 'type' => Controls_Manager::URL, 'default' => [ 'url' => '#' ] ]
		);

		$this->add_control(
			'socials',
			[
				'label'       => __( 'Social links', 'luwipress-gold' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $rep->get_controls(),
				'title_field' => '{{{ network || "social" }}}',
				'default'     => [
					[ 'network' => 'instagram', 'url' => [ 'url' => '#' ] ],
					[ 'network' => 'youtube',   'url' => [ 'url' => '#' ] ],
				],
			]
		);
		$this->end_controls_section();

		/* Style */
		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-gold' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
		$this->add_control(
			'logo_color',
			[
				'label'   => __( 'Logo color', 'luwipress-gold' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '#ffffff',
				'selectors' => [ '{{WRAPPER}} .lwp-fb__logo' => 'color: {{VALUE}};' ],
			]
		);
		$this->add_control(
			'accent_color',
			[
				'label'   => __( 'Accent color', 'luwipress-gold' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '#D4AF37',
				'selectors' => [ '{{WRAPPER}} .lwp-fb__logo-accent' => 'color: {{VALUE}};' ],
			]
		);
		$this->add_control(
			'text_color',
			[
				'label'   => __( 'Text color', 'luwipress-gold' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '#a89d85',
				'selectors' => [ '{{WRAPPER}} .lwp-fb__tagline, {{WRAPPER}} .lwp-fb__contact' => 'color: {{VALUE}};' ],
			]
		);
		$this->end_controls_section();
	}

	private function render_logo( $text, $accent_idx ) {
		$len = mb_strlen( $text );
		if ( $accent_idx < 0 || $accent_idx >= $len ) {
			return esc_html( $text );
		}
		$before = mb_substr( $text, 0, $accent_idx );
		$char   = mb_substr( $text, $accent_idx, 1 );
		$after  = mb_substr( $text, $accent_idx + 1 );
		return esc_html( $before ) . '<span class="lwp-fb__logo-accent">' . esc_html( $char ) . '</span>' . esc_html( $after );
	}

	private function social_svg( $network ) {
		// Inline SVG icons (no external icon font deps).
		$icons = [
			'instagram' => '<rect width="20" height="20" x="2" y="2" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5h.01"/>',
			'youtube'   => '<path d="M2.5 17a24 24 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49 49 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24 24 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49 49 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/><path d="m10 15 5-3-5-3z" fill="currentColor"/>',
			'spotify'   => '<circle cx="12" cy="12" r="10"/><path d="M8 11.5c4-1 8-1 11.5 1"/><path d="M8 14.5c3-1 6-1 9 1"/><path d="M8 8.5c5-1 10-1 13.5 2"/>',
			'whatsapp'  => '<path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9 8.5 8.5 0 0 1 8.5 8.5z"/>',
			'facebook'  => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
			'tiktok'    => '<path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/>',
			'x'         => '<path d="M18 6 6 18M6 6l12 12"/>',
			'pinterest' => '<circle cx="12" cy="12" r="10"/><path d="M8 20s2-3 3-6 3-3 5-1 1 7-2 7-3-3-3-3"/>',
		];
		$path = $icons[ $network ] ?? '';
		return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
	}

	protected function render() {
		$s        = $this->get_settings_for_display();
		$image    = $s['logo_image']['url'] ?? '';
		$text     = trim( (string) ( $s['logo_text'] ?? '' ) );
		$idx      = isset( $s['accent_index'] ) ? (int) $s['accent_index'] : -1;
		$url      = $s['logo_url']['url'] ?? '/';
		$ext      = ! empty( $s['logo_url']['is_external'] );
		$tagline  = trim( (string) ( $s['tagline'] ?? '' ) );
		$phone    = trim( (string) ( $s['phone'] ?? '' ) );
		$email    = trim( (string) ( $s['email'] ?? '' ) );
		$address  = trim( (string) ( $s['address'] ?? '' ) );
		$socials  = is_array( $s['socials'] ?? null ) ? $s['socials'] : [];
		?>
		<div class="lwp-fb">
			<a class="lwp-fb__logo" href="<?php echo esc_url( $url ); ?>"<?php echo $ext ? ' target="_blank" rel="noopener"' : ''; ?>>
				<?php if ( $image ) : ?>
					<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $text ?: get_bloginfo( 'name' ) ); ?>" />
				<?php else : ?>
					<?php echo $this->render_logo( $text, $idx ); ?>
				<?php endif; ?>
			</a>
			<?php if ( $tagline ) : ?>
				<p class="lwp-fb__tagline"><?php echo esc_html( $tagline ); ?></p>
			<?php endif; ?>
			<?php if ( $phone || $email || $address ) : ?>
				<div class="lwp-fb__contact">
					<?php if ( $phone ) : ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a><?php endif; ?>
					<?php if ( $email ) : ?><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a><?php endif; ?>
					<?php if ( $address ) : ?><span><?php echo esc_html( $address ); ?></span><?php endif; ?>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $socials ) ) : ?>
				<div class="lwp-fb__social">
					<?php foreach ( $socials as $soc ) :
						$net = sanitize_text_field( $soc['network'] ?? 'instagram' );
						$surl = $soc['url']['url'] ?? '#';
						$sext = ! empty( $soc['url']['is_external'] );
						?>
						<a href="<?php echo esc_url( $surl ); ?>"
							aria-label="<?php echo esc_attr( ucfirst( $net ) ); ?>"
							<?php echo $sext ? ' target="_blank" rel="noopener"' : ''; ?>>
							<?php echo $this->social_svg( $net ); ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
