<?php
/**
 * Widget: Section Head.
 *
 * The recurring section-heading pattern used across the homepage and
 * other landing pages: eyebrow line · multi-line heading · optional
 * pill-tab row · optional CTA-link on the right.
 *
 * Replaces every `<div class="tap-section-head">…</div>` HTML snippet
 * that was previously inlined inside Elementor HTML widgets.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

class LuwiPress_Emerald_Widget_Section_Head extends Widget_Base {

	public function get_name()        { return 'lwp-section-head'; }
	public function get_title()       { return __( 'Section Head', 'luwipress-emerald' ); }
	public function get_icon()        { return 'eicon-heading'; }
	public function get_categories()  { return [ 'luwipress-emerald' ]; }
	public function get_keywords()    { return [ 'section', 'heading', 'title', 'eyebrow', 'header', 'pills' ]; }
	public function get_style_depends() { return [ 'luwipress-emerald-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_copy', [ 'label' => __( 'Copy', 'luwipress-emerald' ) ] );

		$this->add_control(
			'eyebrow',
			[
				'label'   => __( 'Eyebrow', 'luwipress-emerald' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			]
		);
		$this->add_control(
			'heading',
			[
				'label'   => __( 'Heading', 'luwipress-emerald' ),
				'description' => __( 'Use Enter for a multi-line title. Wrap a phrase with [em]…[/em] to italicise + gold-tint it.', 'luwipress-emerald' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 3,
				'default' => '',
			]
		);
		$this->add_control(
			'heading_tag',
			[
				'label'   => __( 'Heading tag', 'luwipress-emerald' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h2',
				'options' => [ 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4' ],
			]
		);

		$this->add_control(
			'cta_label',
			[
				'label'   => __( 'CTA label', 'luwipress-emerald' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			]
		);
		$this->add_control(
			'cta_url',
			[
				'label'   => __( 'CTA URL', 'luwipress-emerald' ),
				'type'    => Controls_Manager::URL,
				'default' => [ 'url' => '' ],
			]
		);

		$this->end_controls_section();

		/* Optional pill-tab row */
		$this->start_controls_section( 'section_pills', [ 'label' => __( 'Pill tabs (optional)', 'luwipress-emerald' ) ] );

		$rep = new Repeater();
		$rep->add_control(
			'label',
			[ 'label' => __( 'Label', 'luwipress-emerald' ), 'type' => Controls_Manager::TEXT, 'default' => '' ]
		);
		$rep->add_control(
			'url',
			[ 'label' => __( 'URL', 'luwipress-emerald' ), 'type' => Controls_Manager::URL, 'default' => [ 'url' => '#' ] ]
		);
		$rep->add_control(
			'active',
			[
				'label'        => __( 'Mark as active', 'luwipress-emerald' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'luwipress-emerald' ),
				'label_off'    => __( 'No', 'luwipress-emerald' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'pills',
			[
				'label'       => __( 'Pills', 'luwipress-emerald' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $rep->get_controls(),
				'title_field' => '{{{ label || "Pill" }}}',
				'default'     => [],
			]
		);

		$this->end_controls_section();

		/* Style */
		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-emerald' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
		$this->add_control(
			'variant',
			[
				'label'   => __( 'Variant', 'luwipress-emerald' ),
				'description' => __( 'Light = dark text on light bg. Dark = light text on dark bg (e.g. for the masters section).', 'luwipress-emerald' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'light',
				'options' => [
					'light' => __( 'Light', 'luwipress-emerald' ),
					'dark'  => __( 'Dark', 'luwipress-emerald' ),
				],
			]
		);
		$this->end_controls_section();
	}

	/**
	 * Render heading, replacing [em]...[/em] with <em>...</em>.
	 * Newlines become <br>.
	 */
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
		$tag       = in_array( $s['heading_tag'] ?? 'h2', [ 'h1', 'h2', 'h3', 'h4' ], true ) ? $s['heading_tag'] : 'h2';
		$cta_label = trim( (string) ( $s['cta_label'] ?? '' ) );
		$cta_url   = $s['cta_url']['url'] ?? '';
		$cta_ext   = ! empty( $s['cta_url']['is_external'] );
		$variant   = ( $s['variant'] ?? 'light' ) === 'dark' ? 'dark' : 'light';
		$pills     = is_array( $s['pills'] ?? null ) ? $s['pills'] : [];

		if ( $eyebrow === '' && $heading === '' && $cta_label === '' && empty( $pills ) ) {
			return;
		}
		?>
		<div class="lwp-sh lwp-sh--<?php echo esc_attr( $variant ); ?>">
			<div class="lwp-sh__copy">
				<?php if ( $eyebrow ) : ?>
					<span class="lwp-sh__eyebrow">— <?php echo esc_html( $eyebrow ); ?></span>
				<?php endif; ?>
				<?php if ( $heading ) : ?>
					<<?php echo $tag; ?> class="lwp-sh__title"><?php echo $this->render_heading( $heading ); ?></<?php echo $tag; ?>>
				<?php endif; ?>
			</div>
			<?php if ( ! empty( $pills ) || ( $cta_label && $cta_url ) ) : ?>
				<div class="lwp-sh__right">
					<?php if ( ! empty( $pills ) ) : ?>
						<div class="lwp-sh__pills">
							<?php foreach ( $pills as $p ) :
								$lbl = trim( (string) ( $p['label'] ?? '' ) );
								if ( $lbl === '' ) { continue; }
								$url = $p['url']['url'] ?? '#';
								$on  = ( $p['active'] ?? '' ) === 'yes';
								$ext = ! empty( $p['url']['is_external'] );
								?>
								<a href="<?php echo esc_url( $url ); ?>"
									class="lwp-sh__pill<?php echo $on ? ' is-on' : ''; ?>"
									<?php echo $ext ? ' target="_blank" rel="noopener"' : ''; ?>>
									<?php echo esc_html( $lbl ); ?>
								</a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<?php if ( $cta_label && $cta_url ) : ?>
						<a class="lwp-sh__cta" href="<?php echo esc_url( $cta_url ); ?>"
							<?php echo $cta_ext ? ' target="_blank" rel="noopener"' : ''; ?>>
							<?php echo esc_html( $cta_label ); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
