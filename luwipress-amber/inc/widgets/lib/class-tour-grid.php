<?php
/**
 * Elementor widget — Tour Grid.
 *
 * Renders WooCommerce tour products as `.tour-card`s with the data-* attributes
 * (cat / dur / price / rating / pop) that assets/js/tours.js filters and sorts.
 * Pair with the Tour Filters + Tour Toolbar widgets (same data-tours-group).
 *
 * @package luwipress-amber
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }
if ( ! class_exists( 'WooCommerce' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class LuwiPress_Amber_Widget_Tour_Grid extends Widget_Base {

	public function get_name() { return 'lwp-tour-grid'; }
	public function get_title() { return __( 'Tour Grid', 'luwipress-amber' ); }
	public function get_icon() { return 'eicon-products-archive'; }
	public function get_categories() { return [ 'luwipress-amber' ]; }
	public function get_keywords() { return [ 'tour', 'tours', 'packages', 'grid', 'travel', 'products' ]; }
	public function get_style_depends() { return [ 'luwipress-amber-widgets' ]; }
	public function get_script_depends() { return [ 'luwipress-amber-tours' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'content', [ 'label' => __( 'Tours', 'luwipress-amber' ) ] );

		$this->add_control( 'source', [
			'label'   => __( 'Source', 'luwipress-amber' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'tours',
			'options' => [
				'tours'    => __( 'All bookable tours', 'luwipress-amber' ),
				'category' => __( 'Product category', 'luwipress-amber' ),
				'featured' => __( 'Featured products', 'luwipress-amber' ),
				'manual'   => __( 'Manual IDs', 'luwipress-amber' ),
			],
		] );
		$this->add_control( 'category', [
			'label'     => __( 'Category slug', 'luwipress-amber' ),
			'type'      => Controls_Manager::TEXT,
			'condition' => [ 'source' => 'category' ],
		] );
		$this->add_control( 'manual_ids', [
			'label'       => __( 'Product IDs (comma separated)', 'luwipress-amber' ),
			'type'        => Controls_Manager::TEXT,
			'condition'   => [ 'source' => 'manual' ],
		] );
		$this->add_control( 'limit', [
			'label'   => __( 'Number of tours', 'luwipress-amber' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 12,
			'min'     => 1,
			'max'     => 60,
		] );
		$this->add_control( 'orderby', [
			'label'   => __( 'Order by', 'luwipress-amber' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'popularity',
			'options' => [
				'popularity' => __( 'Popularity', 'luwipress-amber' ),
				'date'       => __( 'Newest', 'luwipress-amber' ),
				'price'      => __( 'Price (low→high)', 'luwipress-amber' ),
				'price-desc' => __( 'Price (high→low)', 'luwipress-amber' ),
				'rating'     => __( 'Rating', 'luwipress-amber' ),
				'title'      => __( 'Title', 'luwipress-amber' ),
			],
		] );
		$this->add_control( 'group', [
			'label'       => __( 'Filter group', 'luwipress-amber' ),
			'description' => __( 'Match this on the Tour Filters + Toolbar widgets to wire them together. Leave blank if there is only one set on the page.', 'luwipress-amber' ),
			'type'        => Controls_Manager::TEXT,
		] );
		$this->end_controls_section();

		$this->start_controls_section( 'style', [ 'label' => __( 'Layout', 'luwipress-amber' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_responsive_control( 'columns', [
			'label'          => __( 'Columns', 'luwipress-amber' ),
			'type'           => Controls_Manager::SELECT,
			'default'        => '3',
			'tablet_default' => '2',
			'mobile_default' => '1',
			'options'        => [ '1' => '1', '2' => '2', '3' => '3', '4' => '4' ],
			'selectors'      => [ '{{WRAPPER}} .tours-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);' ],
		] );
		$this->end_controls_section();
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$tours = $this->query( $s );

		if ( empty( $tours ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="tours-grid"><em>' . esc_html__( '[Tour Grid] No tours match — mark products as “Bookable tour” or adjust the query.', 'luwipress-amber' ) . '</em></div>';
			}
			return;
		}

		$group = isset( $s['group'] ) ? sanitize_title( $s['group'] ) : '';
		$sym   = function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol() ) : '';

		printf(
			'<div class="tours-grid" data-lwp-tours data-tours-group="%s" data-currency="%s">',
			esc_attr( $group ),
			esc_attr( $sym )
		);
		foreach ( $tours as $p ) {
			$this->card( $p, $sym );
		}
		echo '<div class="no-results" hidden><h3>' . esc_html__( 'No experiences match your filters.', 'luwipress-amber' ) . '</h3></div>';
		echo '</div>';
	}

	private function query( $s ) {
		$limit = max( 1, (int) ( $s['limit'] ?? 12 ) );
		$source = $s['source'] ?? 'tours';

		if ( 'manual' === $source ) {
			$ids = array_values( array_filter( array_map( 'absint', preg_split( '/[\s,]+/', (string) ( $s['manual_ids'] ?? '' ) ) ) ) );
			if ( empty( $ids ) ) { return []; }
			return wc_get_products( [ 'include' => $ids, 'orderby' => 'post__in', 'limit' => count( $ids ), 'status' => 'publish' ] );
		}

		$orderby = $s['orderby'] ?? 'popularity';
		$order   = 'DESC';
		switch ( $orderby ) {
			case 'price':      $orderby = 'price'; $order = 'ASC'; break;
			case 'price-desc': $orderby = 'price'; $order = 'DESC'; break;
			case 'title':      $orderby = 'title'; $order = 'ASC'; break;
			case 'date':       $orderby = 'date';  $order = 'DESC'; break;
			case 'rating':     $orderby = 'rating'; $order = 'DESC'; break;
			default:           $orderby = 'popularity'; $order = 'DESC';
		}

		$args = [ 'limit' => $limit, 'status' => 'publish', 'orderby' => $orderby, 'order' => $order ];
		if ( 'featured' === $source ) {
			$args['featured'] = true;
		}
		if ( 'category' === $source && ! empty( $s['category'] ) ) {
			$args['category'] = [ sanitize_title( $s['category'] ) ];
		}
		if ( 'tours' === $source ) {
			$args['meta_key']     = '_fbd_is_tour';
			$args['meta_value']   = 'yes';
			$args['meta_compare'] = '=';
		}
		return wc_get_products( $args );
	}

	private function card( $p, $sym ) {
		$cfg     = function_exists( 'lwp_amber_tour_config' ) ? lwp_amber_tour_config( $p->get_id() ) : [];
		$cats    = wp_get_post_terms( $p->get_id(), 'product_cat', [ 'fields' => 'all' ] );
		$cat_slug = ( ! is_wp_error( $cats ) && ! empty( $cats ) ) ? $cats[0]->slug : '';
		$cat_name = ( ! is_wp_error( $cats ) && ! empty( $cats ) ) ? $cats[0]->name : '';
		$bucket  = ! empty( $cfg['duration_bucket'] ) ? $cfg['duration_bucket'] : $this->bucket_from_text( $cfg['duration'] ?? '' );
		$rating  = (float) $p->get_average_rating();
		$price   = (float) $p->get_price();
		$pax_max = ! empty( $cfg['pax_max'] ) ? (int) $cfg['pax_max'] : 0;
		$dur_lbl = $cfg['duration'] ?? '';
		?>
		<article class="tour-card" data-cat="<?php echo esc_attr( $cat_slug ); ?>" data-dur="<?php echo esc_attr( $bucket ); ?>" data-price="<?php echo esc_attr( $price ); ?>" data-rating="<?php echo esc_attr( $rating ); ?>" data-pop="<?php echo esc_attr( (int) $p->get_total_sales() ); ?>">
			<div class="tc-photo">
				<?php echo $p->get_image( 'large', [ 'class' => 'scene-img', 'loading' => 'lazy' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC escaped. ?>
				<div class="tc-scrim"></div>
				<div class="tc-top">
					<?php if ( $cat_name ) : ?><span class="tc-cat"><?php echo esc_html( $cat_name ); ?></span><?php endif; ?>
					<button type="button" class="tc-fav" aria-label="<?php esc_attr_e( 'Save', 'luwipress-amber' ); ?>"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-5.6-7-11a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.4-7 11-7 11Z"/></svg></button>
				</div>
				<?php if ( $rating > 0 ) : ?><span class="tc-rating-pin"><span class="star">★</span> <?php echo esc_html( number_format_i18n( $rating, 1 ) ); ?></span><?php endif; ?>
			</div>
			<div class="tc-body">
				<div class="tc-meta">
					<?php if ( $dur_lbl ) : ?><span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg> <?php echo esc_html( $dur_lbl ); ?></span><?php endif; ?>
					<?php if ( $pax_max ) : ?><span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><path d="M16 14c2.2 0 5 1.8 5 6"/></svg> <?php /* translators: %d: max guests */ printf( esc_html__( 'Up to %d', 'luwipress-amber' ), $pax_max ); ?></span><?php endif; ?>
				</div>
				<h3><?php echo esc_html( $p->get_name() ); ?></h3>
				<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $p->get_short_description() ?: $p->get_description() ), 18 ) ); ?></p>
				<div class="tc-foot">
					<div class="tc-price">
						<div class="from"><?php esc_html_e( 'From', 'luwipress-amber' ); ?></div>
						<div class="amt"><?php echo esc_html( $sym . ( function_exists( 'lwp_amber_money_plain' ) ? lwp_amber_money_plain( $price ) : $price ) ); ?><span>/<?php esc_html_e( 'person', 'luwipress-amber' ); ?></span></div>
					</div>
					<a class="tc-btn" href="<?php echo esc_url( $p->get_permalink() ); ?>"><?php esc_html_e( 'View', 'luwipress-amber' ); ?> <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
				</div>
			</div>
		</article>
		<?php
	}

	private function bucket_from_text( $text ) {
		if ( preg_match( '/(\d+)\s*day/i', $text, $m ) ) {
			return ( (int) $m[1] >= 2 ) ? 'multi' : 'full';
		}
		if ( preg_match( '/(\d+)\s*(hour|hr)/i', $text, $m ) ) {
			$h = (int) $m[1];
			if ( $h <= 3 ) { return 'short'; }
			if ( $h <= 6 ) { return 'half'; }
			return 'full';
		}
		return '';
	}
}
