<?php
/**
 * Widget: AI Search Bar.
 *
 * Prominent landing-page search bar that triggers the theme's existing
 * AI search overlay (`[data-lwp-search-toggle]`). Renders as a large
 * pseudo-input — clicking anywhere on it opens the overlay, where the
 * actual typing happens.
 *
 * When LuwiPress is active, the overlay surfaces AI-ranked product /
 * page suggestions via `/search/ai` REST. When LuwiPress is absent, it
 * falls back to the native WP search and surfaces an "Install LuwiPress
 * for AI search" CTA inside the overlay (theme handles this).
 *
 * The widget itself only knows how to render the trigger — the overlay
 * is the central source of truth.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

class LuwiPress_Onyx_Widget_AI_Search extends Widget_Base {

	public function get_name()        { return 'lwp-ai-search'; }
	public function get_title()       { return __( 'AI Search Bar', 'luwipress-onyx' ); }
	public function get_icon()        { return 'eicon-search-bold'; }
	public function get_categories()  { return [ 'luwipress-onyx' ]; }
	public function get_keywords()    { return [ 'search', 'ai', 'finder', 'discovery', 'overlay' ]; }
	public function get_style_depends() { return [ 'luwipress-onyx-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_copy', [ 'label' => __( 'Copy', 'luwipress-onyx' ) ] );

		$this->add_control( 'placeholder', [
			'label'   => __( 'Placeholder text', 'luwipress-onyx' ),
			'type'    => Controls_Manager::TEXT,
			'default' => __( 'Search products, categories, posts…', 'luwipress-onyx' ),
		] );

		$this->add_control( 'badge', [
			'label'       => __( 'Badge label', 'luwipress-onyx' ),
			'description' => __( 'Small pill on the right side of the bar. Defaults to "AI" — only renders when LuwiPress AI is active.', 'luwipress-onyx' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => 'AI',
		] );

		$this->add_control( 'show_chips', [
			'label'        => __( 'Show suggestion chips', 'luwipress-onyx' ),
			'description'  => __( 'Quick-tap suggested searches shown under the bar.', 'luwipress-onyx' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$rep = new Repeater();
		$rep->add_control( 'label', [ 'label' => __( 'Chip label', 'luwipress-onyx' ), 'type' => Controls_Manager::TEXT, 'default' => '' ] );
		$rep->add_control( 'q',     [ 'label' => __( 'Search query', 'luwipress-onyx' ), 'type' => Controls_Manager::TEXT, 'default' => '' ] );

		$this->add_control( 'chips', [
			'label'       => __( 'Chips', 'luwipress-onyx' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $rep->get_controls(),
			'title_field' => '{{{ label || "Chip" }}}',
			'default'     => [
				[ 'label' => __( 'Best sellers', 'luwipress-onyx' ), 'q' => '' ],
				[ 'label' => __( 'New arrivals', 'luwipress-onyx' ), 'q' => '' ],
				[ 'label' => __( 'On sale',      'luwipress-onyx' ), 'q' => '' ],
				[ 'label' => __( 'Gift ideas',   'luwipress-onyx' ), 'q' => '' ],
			],
			'condition'   => [ 'show_chips' => 'yes' ],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_style', [ 'label' => __( 'Style', 'luwipress-onyx' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'variant', [
			'label'   => __( 'Variant', 'luwipress-onyx' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'light',
			'options' => [ 'light' => __( 'Light', 'luwipress-onyx' ), 'dark' => __( 'Dark', 'luwipress-onyx' ) ],
		] );
		$this->add_control( 'size', [
			'label'   => __( 'Size', 'luwipress-onyx' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'large',
			'options' => [
				'medium' => __( 'Medium', 'luwipress-onyx' ),
				'large'  => __( 'Large (hero-style)', 'luwipress-onyx' ),
			],
		] );
		$this->end_controls_section();
	}

	/**
	 * Is the LuwiPress AI engine active (or even just the chat module)?
	 * Used to optionally hide the AI badge when LuwiPress is missing.
	 */
	protected function lp_ai_active() {
		return function_exists( 'lwp_onyx_lp_active' ) && lwp_onyx_lp_active();
	}

	protected function render() {
		$s        = $this->get_settings_for_display();
		$ph       = (string) ( $s['placeholder'] ?? '' );
		$badge    = trim( (string) ( $s['badge'] ?? '' ) );
		$variant  = ( $s['variant'] ?? 'light' ) === 'dark' ? 'dark' : 'light';
		$size     = ( $s['size'] ?? 'large' ) === 'medium' ? 'medium' : 'large';
		$chips_on = ( $s['show_chips'] ?? 'yes' ) === 'yes';
		$chips    = $chips_on && is_array( $s['chips'] ?? null ) ? $s['chips'] : [];
		$ai_on    = $this->lp_ai_active();
		?>
		<div class="lwp-ais lwp-ais--<?php echo esc_attr( $variant ); ?>" data-size="<?php echo esc_attr( $size ); ?>">
			<button type="button" class="lwp-ais__bar"
				data-lwp-search-toggle
				aria-label="<?php echo esc_attr( $ph ); ?>">
				<svg class="lwp-ais__icon" viewBox="0 0 24 24" width="22" height="22" focusable="false" aria-hidden="true">
					<circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/>
					<line x1="16.5" y1="16.5" x2="21" y2="21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
				</svg>
				<span class="lwp-ais__placeholder"><?php echo esc_html( $ph ); ?></span>
				<?php if ( $badge && $ai_on ) : ?>
					<span class="lwp-ais__badge" aria-hidden="true"><?php echo esc_html( $badge ); ?></span>
				<?php endif; ?>
			</button>
			<?php if ( ! empty( $chips ) ) : ?>
				<div class="lwp-ais__chips">
					<?php foreach ( $chips as $c ) :
						$lbl = trim( (string) ( $c['label'] ?? '' ) );
						$q   = trim( (string) ( $c['q'] ?? '' ) );
						if ( $lbl === '' ) { continue; }
						?>
						<button type="button" class="lwp-ais__chip"
							data-lwp-search-toggle
							data-lwp-search-q="<?php echo esc_attr( $q ); ?>">
							<?php echo esc_html( $lbl ); ?>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
