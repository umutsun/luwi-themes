<?php
/**
 * Widget: Featured Product showcase.
 *
 * Hero-style single-product spotlight: large image + spec list + price +
 * primary CTA. Three source modes:
 *
 *   (a) Manual pick — operator picks a specific product via select control
 *   (b) Featured registry — auto-renders the most-recently-featured product
 *       (flipped via the product meta box or admin bar). When the registry
 *       has no items, optionally falls back to mode (a) or (c).
 *   (c) Custom (no WC) — operator types title / image / price / CTA URL
 *       manually. Lets the widget render on non-WC sites or with curated
 *       editorial picks.
 *
 * Pairs with the Featured registry helpers in inc/featured-products.php.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class LuwiPress_Amber_Widget_Featured_Product extends Widget_Base {

	public function get_name()        { return 'lwp-featured-product'; }
	public function get_title()       { return __( 'Featured Product', 'luwipress-amber' ); }
	public function get_icon()        { return 'eicon-product-info'; }
	public function get_categories()  { return [ 'luwipress-amber' ]; }
	public function get_keywords()    { return [ 'product', 'featured', 'spotlight', 'showcase', 'hero' ]; }
	public function get_style_depends() { return [ 'luwipress-amber-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_source', [ 'label' => __( 'Source', 'luwipress-amber' ) ] );

		$this->add_control( 'source', [
			'label'   => __( 'Source', 'luwipress-amber' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'registry',
			'options' => [
				'registry' => __( 'Featured registry (latest flagged product)', 'luwipress-amber' ),
				'manual'   => __( 'Manual pick — specific product', 'luwipress-amber' ),
				'custom'   => __( 'Custom (title + image + CTA only)', 'luwipress-amber' ),
			],
		] );

		$this->add_control( 'product_id', [
			'label'       => __( 'Pick product', 'luwipress-amber' ),
			'type'        => Controls_Manager::SELECT2,
			'options'     => $this->get_product_options(),
			'condition'   => [ 'source' => 'manual' ],
		] );

		$this->add_control( 'eyebrow', [
			'label' => __( 'Eyebrow', 'luwipress-amber' ),
			'type'  => Controls_Manager::TEXT,
			'default' => __( 'Featured', 'luwipress-amber' ),
		] );

		$this->end_controls_section();

		/* Custom mode fields */
		$this->start_controls_section( 'section_custom', [
			'label'     => __( 'Custom content', 'luwipress-amber' ),
			'condition' => [ 'source' => 'custom' ],
		] );

		$this->add_control( 'c_image', [ 'label' => __( 'Image', 'luwipress-amber' ), 'type' => Controls_Manager::MEDIA, 'default' => [ 'url' => '' ] ] );
		$this->add_control( 'c_title', [ 'label' => __( 'Title', 'luwipress-amber' ), 'type' => Controls_Manager::TEXT, 'default' => '' ] );
		$this->add_control( 'c_excerpt', [ 'label' => __( 'Short description', 'luwipress-amber' ), 'type' => Controls_Manager::TEXTAREA, 'rows' => 3, 'default' => '' ] );
		$this->add_control( 'c_price', [ 'label' => __( 'Price (display string)', 'luwipress-amber' ), 'type' => Controls_Manager::TEXT, 'default' => '' ] );
		$this->add_control( 'c_url', [ 'label' => __( 'Product URL', 'luwipress-amber' ), 'type' => Controls_Manager::URL, 'default' => [ 'url' => '' ] ] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_cta', [ 'label' => __( 'CTA', 'luwipress-amber' ) ] );
		$this->add_control( 'cta_label', [
			'label'   => __( 'CTA label', 'luwipress-amber' ),
			'type'    => Controls_Manager::TEXT,
			'default' => __( 'View product →', 'luwipress-amber' ),
		] );
		$this->end_controls_section();

		$this->start_controls_section( 'section_style', [ 'label' => __( 'Style', 'luwipress-amber' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'image_side', [
			'label'   => __( 'Image side', 'luwipress-amber' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'left',
			'options' => [ 'left' => __( 'Left', 'luwipress-amber' ), 'right' => __( 'Right', 'luwipress-amber' ) ],
		] );
		$this->add_control( 'variant', [
			'label'   => __( 'Variant', 'luwipress-amber' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'light',
			'options' => [ 'light' => __( 'Light', 'luwipress-amber' ), 'dark' => __( 'Dark', 'luwipress-amber' ) ],
		] );
		$this->end_controls_section();
	}

	protected function get_product_options() {
		if ( ! function_exists( 'wc_get_product' ) ) { return [ '' => __( 'WooCommerce inactive', 'luwipress-amber' ) ]; }
		$opts = [ '' => __( '— pick a product —', 'luwipress-amber' ) ];
		$q = new WP_Query( [
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'fields'         => 'ids',
			'no_found_rows'  => true,
		] );
		foreach ( $q->posts as $pid ) {
			$opts[ (string) $pid ] = get_the_title( $pid );
		}
		return $opts;
	}

	/**
	 * Resolve which product to render — returns either a normalised data
	 * array { title, excerpt, image, price, url, note } or null.
	 */
	protected function resolve_product( $s ) {
		$source = $s['source'] ?? 'registry';

		if ( $source === 'custom' ) {
			$title = trim( (string) ( $s['c_title'] ?? '' ) );
			$image = $s['c_image']['url'] ?? '';
			$url   = $s['c_url']['url'] ?? '';
			if ( $title === '' && $image === '' ) { return null; }
			return [
				'title'   => $title,
				'excerpt' => (string) ( $s['c_excerpt'] ?? '' ),
				'image'   => $image,
				'price'   => (string) ( $s['c_price'] ?? '' ),
				'url'     => $url,
				'note'    => '',
			];
		}

		$pid = 0;
		if ( $source === 'manual' ) {
			$pid = (int) ( $s['product_id'] ?? 0 );
		} else {
			if ( function_exists( 'lwp_amber_get_featured_ids' ) ) {
				$ids = lwp_amber_get_featured_ids( [ 'number' => 1 ] );
				$pid = $ids[0] ?? 0;
			}
		}
		if ( ! $pid ) { return null; }
		return $this->product_to_data( $pid );
	}

	protected function product_to_data( $pid ) {
		$pid = (int) $pid;
		if ( ! $pid ) { return null; }
		$p = get_post( $pid );
		if ( ! $p || $p->post_status !== 'publish' ) { return null; }

		$image = get_the_post_thumbnail_url( $pid, 'large' ) ?: '';
		$url   = get_permalink( $pid );
		$note  = function_exists( 'get_post_meta' ) ? (string) get_post_meta( $pid, '_luwipress_amber_featured_note', true ) : '';

		$price = '';
		$excerpt = wp_strip_all_tags( $p->post_excerpt ?: wp_trim_words( $p->post_content, 30 ) );
		if ( function_exists( 'wc_get_product' ) ) {
			$wc = wc_get_product( $pid );
			if ( $wc ) {
				$price = $wc->get_price_html();
				if ( $excerpt === '' ) {
					$excerpt = wp_strip_all_tags( $wc->get_short_description() );
				}
			}
		}

		return [
			'title'   => get_the_title( $pid ),
			'excerpt' => $excerpt,
			'image'   => $image,
			'price'   => $price,
			'url'     => $url,
			'note'    => $note,
		];
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$d = $this->resolve_product( $s );
		if ( ! $d ) { return; }

		$eyebrow   = trim( (string) ( $s['eyebrow'] ?? '' ) );
		$cta_label = trim( (string) ( $s['cta_label'] ?? '' ) );
		$image_side = ( $s['image_side'] ?? 'left' ) === 'right' ? 'right' : 'left';
		$variant    = ( $s['variant'] ?? 'light' ) === 'dark' ? 'dark' : 'light';
		?>
		<div class="lwp-fp lwp-fp--<?php echo esc_attr( $variant ); ?>" data-image-side="<?php echo esc_attr( $image_side ); ?>">
			<div class="lwp-fp__image">
				<?php if ( $d['image'] ) : ?>
					<img loading="lazy" decoding="async" src="<?php echo esc_url( $d['image'] ); ?>" alt="<?php echo esc_attr( $d['title'] ); ?>" />
				<?php endif; ?>
				<?php if ( $d['note'] ) : ?>
					<span class="lwp-fp__chip"><?php echo esc_html( $d['note'] ); ?></span>
				<?php endif; ?>
			</div>
			<div class="lwp-fp__copy">
				<?php if ( $eyebrow ) : ?><span class="lwp-fp__eyebrow">— <?php echo esc_html( $eyebrow ); ?></span><?php endif; ?>
				<?php if ( $d['title'] ) : ?>
					<h2 class="lwp-fp__title">
						<?php if ( $d['url'] ) : ?>
							<a href="<?php echo esc_url( $d['url'] ); ?>"><?php echo esc_html( $d['title'] ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $d['title'] ); ?>
						<?php endif; ?>
					</h2>
				<?php endif; ?>
				<?php if ( $d['excerpt'] ) : ?>
					<p class="lwp-fp__excerpt"><?php echo esc_html( $d['excerpt'] ); ?></p>
				<?php endif; ?>
				<?php if ( $d['price'] ) : ?>
					<div class="lwp-fp__price"><?php echo wp_kses_post( $d['price'] ); ?></div>
				<?php endif; ?>
				<?php if ( $cta_label && $d['url'] ) : ?>
					<a class="lwp-fp__cta" href="<?php echo esc_url( $d['url'] ); ?>">
						<?php echo esc_html( $cta_label ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
