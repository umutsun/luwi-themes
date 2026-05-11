<?php
/**
 * Widget: KG Trending.
 *
 * Surfaces the top-scored items from the LuwiPress Knowledge Graph —
 * either trending product categories (default) or trending products.
 * Score comes from KG's weighted-edge ranker (popularity + recency +
 * completeness signals).
 *
 * Same upsell pattern as lwp-kg-stats when LuwiPress is inactive: the
 * widget renders an install-LuwiPress card. The hook is intentional —
 * this widget is one of the strongest reasons to install the plugin.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class LuwiPress_Gold_Widget_KG_Trending extends Widget_Base {

	public function get_name()        { return 'lwp-kg-trending'; }
	public function get_title()       { return __( 'Knowledge Graph · Trending', 'luwipress-gold' ); }
	public function get_icon()        { return 'eicon-trending-up'; }
	public function get_categories()  { return [ 'luwipress-gold' ]; }
	public function get_keywords()    { return [ 'kg', 'trending', 'popular', 'live', 'knowledge graph' ]; }
	public function get_style_depends() { return [ 'luwipress-gold-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_layout', [ 'label' => __( 'Layout', 'luwipress-gold' ) ] );

		$this->add_control( 'mode', [
			'label'   => __( 'Trending', 'luwipress-gold' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'categories',
			'options' => [
				'categories' => __( 'Product categories', 'luwipress-gold' ),
				'products'   => __( 'Products', 'luwipress-gold' ),
			],
		] );
		$this->add_control( 'count', [
			'label'   => __( 'How many', 'luwipress-gold' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 6,
			'min'     => 3,
			'max'     => 18,
		] );
		$this->add_control( 'show_score', [
			'label'        => __( 'Show KG score', 'luwipress-gold' ),
			'description'  => __( 'Small pill on each card showing the KG ranking score. Useful in editorial contexts to signal "data-driven".', 'luwipress-gold' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
		] );

		$this->add_control( 'eyebrow', [ 'label' => __( 'Eyebrow', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Picking up momentum', 'luwipress-gold' ) ] );
		$this->add_control( 'heading', [ 'label' => __( 'Heading', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Trending in the catalogue.', 'luwipress-gold' ) ] );

		$this->end_controls_section();
	}

	/**
	 * Fetch trending items from KG. Returns null if KG class missing.
	 *
	 * @return array|null  list of [ 'id', 'name', 'url', 'image', 'score', 'count' ]
	 */
	protected function fetch_trending( $mode, $count ) {
		if ( ! function_exists( 'lwp_gold_lp_active' ) || ! lwp_gold_lp_active() ) { return null; }
		if ( ! class_exists( 'LuwiPress_Knowledge_Graph' ) ) { return null; }

		$cache_key = 'lwp_gold_kg_trending_' . $mode . '_' . $count;
		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) ) { return $cached; }

		$items = [];
		try {
			$kg = LuwiPress_Knowledge_Graph::get_instance();
			if ( method_exists( $kg, 'get_trending' ) ) {
				$items = (array) $kg->get_trending( [ 'mode' => $mode, 'limit' => $count ] );
			}
		} catch ( \Throwable $e ) {
			$items = [];
		}

		if ( empty( $items ) ) {
			$items = $this->fallback_trending( $mode, $count );
		}

		if ( ! empty( $items ) ) {
			set_transient( $cache_key, $items, 10 * MINUTE_IN_SECONDS );
		}
		return $items;
	}

	/**
	 * When KG lacks `get_trending()`, fall back to a simple
	 * popularity heuristic so the widget still renders something.
	 */
	protected function fallback_trending( $mode, $count ) {
		$out = [];
		if ( $mode === 'categories' && taxonomy_exists( 'product_cat' ) ) {
			$terms = get_terms( [
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => $count,
				'parent'     => 0,
			] );
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $t ) {
					$thumb_id = function_exists( 'get_term_meta' ) ? get_term_meta( $t->term_id, 'thumbnail_id', true ) : 0;
					$out[] = [
						'id'    => $t->term_id,
						'name'  => $t->name,
						'url'   => get_term_link( $t ),
						'image' => $thumb_id ? wp_get_attachment_image_url( (int) $thumb_id, 'medium_large' ) : '',
						'score' => (int) $t->count,
						'count' => (int) $t->count,
					];
				}
			}
		} elseif ( $mode === 'products' && function_exists( 'wc_get_product' ) ) {
			$q = new WP_Query( [
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => $count,
				'orderby'        => 'meta_value_num',
				'meta_key'       => 'total_sales',
				'order'          => 'DESC',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			] );
			foreach ( $q->posts as $pid ) {
				$out[] = [
					'id'    => (int) $pid,
					'name'  => get_the_title( $pid ),
					'url'   => get_permalink( $pid ),
					'image' => get_the_post_thumbnail_url( (int) $pid, 'medium_large' ),
					'score' => (int) get_post_meta( $pid, 'total_sales', true ),
					'count' => (int) get_post_meta( $pid, 'total_sales', true ),
				];
			}
		}
		return $out;
	}

	protected function render_upsell() {
		?>
		<div class="lwp-kgstats lwp-kgstats--upsell" role="region">
			<div class="lwp-kgstats__upsell-inner">
				<span class="lwp-kgstats__upsell-eb"><?php esc_html_e( '— LuwiPress required', 'luwipress-gold' ); ?></span>
				<h3 class="lwp-kgstats__upsell-title">
					<?php esc_html_e( 'See what is trending — powered by the Knowledge Graph.', 'luwipress-gold' ); ?>
				</h3>
				<p class="lwp-kgstats__upsell-lead">
					<?php esc_html_e( 'LuwiPress reads your store + reviews + traffic signals and ranks categories and products on a live KG score. Install the free plugin to surface it on this widget.', 'luwipress-gold' ); ?>
				</p>
				<a class="lwp-kgstats__upsell-btn" href="<?php echo esc_url( admin_url( 'plugin-install.php?s=luwipress&tab=search&type=term' ) ); ?>">
					<?php esc_html_e( 'Install LuwiPress (free) →', 'luwipress-gold' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$mode  = ( $s['mode'] ?? 'categories' ) === 'products' ? 'products' : 'categories';
		$count = max( 3, min( 18, (int) ( $s['count'] ?? 6 ) ) );
		$show_score = ( $s['show_score'] ?? '' ) === 'yes';

		$items = $this->fetch_trending( $mode, $count );
		if ( $items === null ) { $this->render_upsell(); return; }
		if ( empty( $items ) ) { $this->render_upsell(); return; }

		$eyebrow = trim( (string) ( $s['eyebrow'] ?? '' ) );
		$heading = trim( (string) ( $s['heading'] ?? '' ) );
		?>
		<section class="lwp-kgt" data-mode="<?php echo esc_attr( $mode ); ?>">
			<?php if ( $eyebrow || $heading ) : ?>
				<header class="lwp-kgt__head">
					<div>
						<?php if ( $eyebrow ) : ?><span class="lwp-kgt__eyebrow">— <?php echo esc_html( $eyebrow ); ?></span><?php endif; ?>
						<?php if ( $heading ) : ?><h2 class="lwp-kgt__heading"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
					</div>
					<span class="lwp-kgt__badge"><?php esc_html_e( 'Live · LuwiPress KG', 'luwipress-gold' ); ?></span>
				</header>
			<?php endif; ?>
			<div class="lwp-kgt__grid">
				<?php foreach ( $items as $it ) :
					$name = trim( (string) ( $it['name'] ?? '' ) );
					if ( $name === '' ) { continue; }
					$url   = (string) ( $it['url'] ?? '' );
					$image = (string) ( $it['image'] ?? '' );
					$score = (int) ( $it['score'] ?? 0 );
					$cnt   = (int) ( $it['count'] ?? 0 );
					?>
					<a class="lwp-kgt__card" href="<?php echo esc_url( $url ); ?>">
						<span class="lwp-kgt__img"<?php if ( $image ) : ?> style="background-image: url(<?php echo esc_url( $image ); ?>);"<?php endif; ?>></span>
						<span class="lwp-kgt__meta">
							<span class="lwp-kgt__name"><?php echo esc_html( $name ); ?></span>
							<?php if ( $cnt ) : ?>
								<span class="lwp-kgt__count"><?php
									echo esc_html( $mode === 'categories'
										? sprintf( _n( '%d product', '%d products', $cnt, 'luwipress-gold' ), $cnt )
										: sprintf( _n( '%d sale', '%d sales', $cnt, 'luwipress-gold' ), $cnt )
									);
								?></span>
							<?php endif; ?>
						</span>
						<?php if ( $show_score && $score > 0 ) : ?>
							<span class="lwp-kgt__score"><?php echo esc_html( $score ); ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}
}
