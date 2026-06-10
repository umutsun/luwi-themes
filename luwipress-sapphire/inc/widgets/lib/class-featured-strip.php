<?php
/**
 * Widget: Featured Strip.
 *
 * Horizontal scrolling strip of every product currently flipped to
 * "featured" via the Featured Products registry (or a manual count).
 * No carousel library — CSS scroll-snap + drag-to-scroll micro-script.
 *
 * Empty state: when there are no featured products, optionally falls
 * back to the WC bestseller / on-sale list (server-rendered, cached).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class LuwiPress_Sapphire_Widget_Featured_Strip extends Widget_Base {

	public function get_name()        { return 'lwp-featured-strip'; }
	public function get_title()       { return __( 'Featured Strip', 'luwipress-sapphire' ); }
	public function get_icon()        { return 'eicon-posts-carousel'; }
	public function get_categories()  { return [ 'luwipress-sapphire' ]; }
	public function get_keywords()    { return [ 'featured', 'strip', 'carousel', 'products', 'highlight' ]; }
	public function get_style_depends() { return [ 'luwipress-sapphire-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_source', [ 'label' => __( 'Source', 'luwipress-sapphire' ) ] );

		$this->add_control( 'count', [
			'label'   => __( 'Max items', 'luwipress-sapphire' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 8,
			'min'     => 2,
			'max'     => 30,
		] );

		$this->add_control( 'fallback', [
			'label'       => __( 'Fallback when registry is empty', 'luwipress-sapphire' ),
			'description' => __( 'What to show when no products are flipped to featured. Falls back automatically — the operator does not see the strip disappear during onboarding.', 'luwipress-sapphire' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'bestseller',
			'options'     => [
				'bestseller' => __( 'Best-selling products', 'luwipress-sapphire' ),
				'onsale'     => __( 'On-sale products', 'luwipress-sapphire' ),
				'recent'     => __( 'Recently added', 'luwipress-sapphire' ),
				'hide'       => __( 'Hide the strip', 'luwipress-sapphire' ),
			],
		] );

		$this->add_control( 'show_note', [
			'label'        => __( 'Show operator note chips', 'luwipress-sapphire' ),
			'description'  => __( 'Renders the operator note from the meta box ("Boxing Week pick", "New arrival") as a small chip on each card.', 'luwipress-sapphire' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_head', [ 'label' => __( 'Heading (optional)', 'luwipress-sapphire' ) ] );
		$this->add_control( 'eyebrow', [ 'label' => __( 'Eyebrow', 'luwipress-sapphire' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Hand-picked', 'luwipress-sapphire' ) ] );
		$this->add_control( 'heading', [ 'label' => __( 'Heading', 'luwipress-sapphire' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Featured products.', 'luwipress-sapphire' ) ] );
		$this->end_controls_section();
	}

	protected function get_ids( $s ) {
		$count = max( 2, min( 30, (int) ( $s['count'] ?? 8 ) ) );
		$ids   = [];
		if ( function_exists( 'lwp_sapphire_get_featured_ids' ) ) {
			$ids = lwp_sapphire_get_featured_ids( [ 'number' => $count ] );
		}
		if ( ! empty( $ids ) ) { return $ids; }

		$fallback = $s['fallback'] ?? 'bestseller';
		if ( $fallback === 'hide' || ! function_exists( 'wc_get_product' ) ) { return []; }

		$args = [
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $count,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		];
		if ( $fallback === 'bestseller' ) {
			$args['orderby']  = 'meta_value_num';
			$args['meta_key'] = 'total_sales';
			$args['order']    = 'DESC';
		} elseif ( $fallback === 'onsale' ) {
			$args['post__in'] = wc_get_product_ids_on_sale() ?: [ 0 ];
			$args['orderby']  = 'rand';
		} else {
			$args['orderby']  = 'date';
			$args['order']    = 'DESC';
		}
		$q = new WP_Query( $args );
		return array_map( 'intval', $q->posts );
	}

	protected function render() {
		$s   = $this->get_settings_for_display();
		$ids = $this->get_ids( $s );
		if ( empty( $ids ) ) { return; }

		$show_note = ( $s['show_note'] ?? 'yes' ) === 'yes';
		$eyebrow   = trim( (string) ( $s['eyebrow'] ?? '' ) );
		$heading   = trim( (string) ( $s['heading'] ?? '' ) );
		?>
		<section class="lwp-fs">
			<?php if ( $eyebrow || $heading ) : ?>
				<header class="lwp-fs__head">
					<?php if ( $eyebrow ) : ?><span class="lwp-fs__eyebrow">— <?php echo esc_html( $eyebrow ); ?></span><?php endif; ?>
					<?php if ( $heading ) : ?><h2 class="lwp-fs__heading"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
				</header>
			<?php endif; ?>
			<div class="lwp-fs__track">
				<?php foreach ( $ids as $pid ) :
					$pid = (int) $pid;
					if ( ! $pid ) { continue; }
					$title = get_the_title( $pid );
					$url   = get_permalink( $pid );
					$image = get_the_post_thumbnail_url( $pid, 'medium_large' );
					$price = '';
					if ( function_exists( 'wc_get_product' ) ) {
						$wc = wc_get_product( $pid );
						if ( $wc ) { $price = $wc->get_price_html(); }
					}
					$note = $show_note ? (string) get_post_meta( $pid, '_luwipress_sapphire_featured_note', true ) : '';
					?>
					<a class="lwp-fs__card" href="<?php echo esc_url( $url ); ?>">
						<span class="lwp-fs__img">
							<?php if ( $image ) : ?>
								<img loading="lazy" decoding="async" src="<?php echo esc_url( $image ); ?>" alt="" />
							<?php endif; ?>
							<?php if ( $note ) : ?><span class="lwp-fs__chip"><?php echo esc_html( $note ); ?></span><?php endif; ?>
						</span>
						<span class="lwp-fs__meta">
							<span class="lwp-fs__title"><?php echo esc_html( $title ); ?></span>
							<?php if ( $price ) : ?><span class="lwp-fs__price"><?php echo wp_kses_post( $price ); ?></span><?php endif; ?>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}
}
