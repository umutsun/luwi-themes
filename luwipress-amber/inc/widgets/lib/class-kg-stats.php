<?php
/**
 * Widget: KG Store Stats.
 *
 * Surfaces live store-intelligence numbers from the LuwiPress Knowledge
 * Graph endpoint (`luwipress/v1/knowledge-graph`). Renders four big
 * counters — products, categories, masters/authors, countries — pulled
 * fresh from the KG summary cache (5-min TTL).
 *
 * When LuwiPress is INACTIVE: renders an upsell card explaining that
 * installing LuwiPress unlocks live KG data. The theme is the hook;
 * this widget is the most explicit "go install LuwiPress" surface.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class LuwiPress_Amber_Widget_KG_Stats extends Widget_Base {

	public function get_name()        { return 'lwp-kg-stats'; }
	public function get_title()       { return __( 'Knowledge Graph · Stats', 'luwipress-amber' ); }
	public function get_icon()        { return 'eicon-number-field'; }
	public function get_categories()  { return [ 'luwipress-amber' ]; }
	public function get_keywords()    { return [ 'knowledge graph', 'kg', 'stats', 'live', 'luwipress' ]; }
	public function get_style_depends() { return [ 'luwipress-amber-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_layout', [ 'label' => __( 'Layout', 'luwipress-amber' ) ] );

		$this->add_control( 'eyebrow', [ 'label' => __( 'Eyebrow', 'luwipress-amber' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Store at a glance', 'luwipress-amber' ) ] );
		$this->add_control( 'heading', [ 'label' => __( 'Heading', 'luwipress-amber' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Live store stats.', 'luwipress-amber' ) ] );

		$this->add_control( 'show_products',  [ 'label' => __( 'Show Products counter', 'luwipress-amber' ),  'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ] );
		$this->add_control( 'show_categories',[ 'label' => __( 'Show Categories counter', 'luwipress-amber' ),'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ] );
		$this->add_control( 'show_authors',   [ 'label' => __( 'Show Authors/Team counter', 'luwipress-amber' ),   'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ] );
		$this->add_control( 'show_countries', [ 'label' => __( 'Show Countries-shipped counter', 'luwipress-amber' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ] );

		$this->add_control( 'lbl_products',   [ 'label' => __( 'Products label', 'luwipress-amber' ),   'type' => Controls_Manager::TEXT, 'default' => __( 'Products in catalogue', 'luwipress-amber' ) ] );
		$this->add_control( 'lbl_categories', [ 'label' => __( 'Categories label', 'luwipress-amber' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Categories', 'luwipress-amber' ) ] );
		$this->add_control( 'lbl_authors',    [ 'label' => __( 'Authors label', 'luwipress-amber' ),    'type' => Controls_Manager::TEXT, 'default' => __( 'Team members', 'luwipress-amber' ) ] );
		$this->add_control( 'lbl_countries',  [ 'label' => __( 'Countries label', 'luwipress-amber' ),  'type' => Controls_Manager::TEXT, 'default' => __( 'Countries shipped to', 'luwipress-amber' ) ] );

		$this->end_controls_section();
	}

	/**
	 * Fetch KG summary. Cached per request + 5-min transient.
	 *
	 * @return array|null  null when LuwiPress KG class is missing.
	 */
	protected function fetch_kg() {
		if ( ! function_exists( 'lwp_amber_lp_active' ) || ! lwp_amber_lp_active() ) { return null; }
		if ( ! class_exists( 'LuwiPress_Knowledge_Graph' ) ) { return null; }

		$cached = get_transient( 'lwp_amber_kg_summary' );
		if ( is_array( $cached ) ) { return $cached; }

		// Call KG class directly — faster + auth-free vs internal REST hop.
		try {
			$kg = LuwiPress_Knowledge_Graph::get_instance();
			$data = method_exists( $kg, 'get_summary' ) ? $kg->get_summary() : null;
		} catch ( \Throwable $e ) {
			$data = null;
		}

		if ( ! is_array( $data ) ) {
			// Fallback: compute from native taxonomy + post counts.
			$data = $this->compute_summary_fallback();
		}

		if ( is_array( $data ) ) {
			set_transient( 'lwp_amber_kg_summary', $data, 5 * MINUTE_IN_SECONDS );
		}
		return $data;
	}

	protected function compute_summary_fallback() {
		$out = [];
		if ( function_exists( 'wp_count_posts' ) ) {
			$pc = wp_count_posts( 'product' );
			$out['products'] = isset( $pc->publish ) ? (int) $pc->publish : 0;
		}
		if ( taxonomy_exists( 'product_cat' ) ) {
			$terms = wp_count_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => true ] );
			$out['categories'] = is_wp_error( $terms ) ? 0 : (int) $terms;
		}
		$users = count_users();
		$out['authors']   = isset( $users['avail_roles']['shop_manager'] ) ? (int) $users['avail_roles']['shop_manager'] : 0;
		$out['countries'] = 0;
		return $out;
	}

	protected function render_upsell() {
		?>
		<div class="lwp-kgstats lwp-kgstats--upsell" role="region" aria-label="<?php esc_attr_e( 'Install LuwiPress to enable live store stats', 'luwipress-amber' ); ?>">
			<div class="lwp-kgstats__upsell-inner">
				<span class="lwp-kgstats__upsell-eb"><?php esc_html_e( '— LuwiPress required', 'luwipress-amber' ); ?></span>
				<h3 class="lwp-kgstats__upsell-title">
					<?php esc_html_e( 'Unlock live Knowledge Graph data.', 'luwipress-amber' ); ?>
				</h3>
				<p class="lwp-kgstats__upsell-lead">
					<?php esc_html_e( 'Install the free LuwiPress plugin to surface live product counts, category coverage, master roster and countries-shipped on this widget — pulled directly from your store data.', 'luwipress-amber' ); ?>
				</p>
				<a class="lwp-kgstats__upsell-btn" href="<?php echo esc_url( admin_url( 'plugin-install.php?s=luwipress&tab=search&type=term' ) ); ?>">
					<?php esc_html_e( 'Install LuwiPress (free) →', 'luwipress-amber' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	protected function render() {
		$s = $this->get_settings_for_display();

		if ( ! function_exists( 'lwp_amber_lp_active' ) || ! lwp_amber_lp_active() ) {
			$this->render_upsell();
			return;
		}

		$data = $this->fetch_kg();
		if ( ! $data ) {
			$this->render_upsell();
			return;
		}

		$eyebrow = trim( (string) ( $s['eyebrow'] ?? '' ) );
		$heading = trim( (string) ( $s['heading'] ?? '' ) );

		$cells = [];
		if ( ( $s['show_products']   ?? 'yes' ) === 'yes' ) { $cells[] = [ 'num' => (int) ( $data['products']   ?? 0 ), 'lbl' => (string) ( $s['lbl_products']   ?? '' ) ]; }
		if ( ( $s['show_categories'] ?? 'yes' ) === 'yes' ) { $cells[] = [ 'num' => (int) ( $data['categories'] ?? 0 ), 'lbl' => (string) ( $s['lbl_categories'] ?? '' ) ]; }
		if ( ( $s['show_authors']    ?? 'yes' ) === 'yes' ) { $cells[] = [ 'num' => (int) ( $data['authors']    ?? 0 ), 'lbl' => (string) ( $s['lbl_authors']    ?? '' ) ]; }
		if ( ( $s['show_countries']  ?? 'yes' ) === 'yes' ) { $cells[] = [ 'num' => (int) ( $data['countries']  ?? 0 ), 'lbl' => (string) ( $s['lbl_countries']  ?? '' ) ]; }
		?>
		<div class="lwp-kgstats" data-live="1">
			<?php if ( $eyebrow || $heading ) : ?>
				<header class="lwp-kgstats__head">
					<?php if ( $eyebrow ) : ?><span class="lwp-kgstats__eyebrow">— <?php echo esc_html( $eyebrow ); ?></span><?php endif; ?>
					<?php if ( $heading ) : ?><h2 class="lwp-kgstats__title"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
					<span class="lwp-kgstats__badge" title="<?php esc_attr_e( 'Powered by LuwiPress Knowledge Graph', 'luwipress-amber' ); ?>">
						<?php esc_html_e( 'Live · LuwiPress KG', 'luwipress-amber' ); ?>
					</span>
				</header>
			<?php endif; ?>
			<div class="lwp-kgstats__grid" data-count="<?php echo esc_attr( count( $cells ) ); ?>">
				<?php foreach ( $cells as $c ) : ?>
					<div class="lwp-kgstats__cell">
						<span class="lwp-kgstats__num" data-target="<?php echo esc_attr( $c['num'] ); ?>"><?php echo esc_html( number_format_i18n( $c['num'] ) ); ?></span>
						<?php if ( $c['lbl'] ) : ?><span class="lwp-kgstats__lbl"><?php echo esc_html( $c['lbl'] ); ?></span><?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}
