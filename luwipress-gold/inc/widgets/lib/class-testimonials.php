<?php
/**
 * Widget: Testimonials Carousel.
 *
 * Customer review cards with star rating + photo + name + location.
 * Renders Schema.org Review markup so SEO plugins (Rank Math, Yoast,
 * AIOSEO) pick them up for rich-snippet stars in SERP.
 *
 * Carousel layer: simple CSS scroll-snap + JS dots (no library).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

class LuwiPress_Gold_Widget_Testimonials extends Widget_Base {

	public function get_name()        { return 'lwp-testimonials'; }
	public function get_title()       { return __( 'Testimonials', 'luwipress-gold' ); }
	public function get_icon()        { return 'eicon-testimonial-carousel'; }
	public function get_categories()  { return [ 'luwipress-gold' ]; }
	public function get_keywords()    { return [ 'testimonials', 'reviews', 'customer', 'quotes', 'carousel', 'stars' ]; }
	public function get_style_depends() { return [ 'luwipress-gold-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_items', [ 'label' => __( 'Reviews', 'luwipress-gold' ) ] );

		$rep = new Repeater();
		$rep->add_control( 'quote',    [ 'label' => __( 'Quote', 'luwipress-gold' ),    'type' => Controls_Manager::TEXTAREA, 'rows' => 4, 'default' => '' ] );
		$rep->add_control( 'name',     [ 'label' => __( 'Author name', 'luwipress-gold' ),  'type' => Controls_Manager::TEXT, 'default' => '' ] );
		$rep->add_control( 'location', [ 'label' => __( 'Location', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT, 'default' => '' ] );
		$rep->add_control( 'avatar',   [ 'label' => __( 'Avatar (optional)', 'luwipress-gold' ), 'type' => Controls_Manager::MEDIA, 'default' => [ 'url' => '' ] ] );
		$rep->add_control( 'product',  [ 'label' => __( 'Product purchased (optional)', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT, 'default' => '' ] );
		$rep->add_control( 'rating',   [
			'label' => __( 'Star rating', 'luwipress-gold' ),
			'type'  => Controls_Manager::SELECT,
			'default' => '5',
			'options' => [ '5' => '★★★★★ (5)', '4' => '★★★★☆ (4)', '3' => '★★★☆☆ (3)', '2' => '★★☆☆☆ (2)', '1' => '★☆☆☆☆ (1)' ],
		] );
		$rep->add_control( 'date', [ 'label' => __( 'Review date (YYYY-MM-DD, optional)', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT, 'default' => '' ] );

		$this->add_control( 'reviews', [
			'label'       => __( 'Reviews', 'luwipress-gold' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $rep->get_controls(),
			'title_field' => '{{{ name || "Review" }}}',
			'default'     => [
				[ 'quote' => 'The oud arrived already in tune and the action was perfect. Communication from the atelier was exceptional throughout.', 'name' => 'Marco S.',  'location' => 'Milan · IT',     'product' => 'Sultan Turkish Oud',  'rating' => '5' ],
				[ 'quote' => 'I have bought instruments from many luthiers over thirty years. This was the most personal experience I have had — and the saz sings.', 'name' => 'David K.',  'location' => 'Berlin · DE',   'product' => 'Baglama Saz',          'rating' => '5' ],
				[ 'quote' => 'The packaging alone was a work of art. The setar inside? Even better. Tapadum has my trust forever.', 'name' => 'Leila R.',  'location' => 'Paris · FR',   'product' => 'Persian Setar',         'rating' => '5' ],
			],
		] );

		$this->add_control( 'layout', [
			'label'   => __( 'Layout', 'luwipress-gold' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'carousel',
			'options' => [
				'carousel' => __( 'Carousel (scroll-snap)', 'luwipress-gold' ),
				'grid'     => __( 'Grid (3-up desktop)', 'luwipress-gold' ),
			],
		] );

		$this->add_control( 'show_schema', [
			'label'        => __( 'Output Review schema', 'luwipress-gold' ),
			'description'  => __( 'Outputs Schema.org Review JSON-LD so Google + Rank Math can show stars in SERP. Disable if your SEO plugin handles it separately.', 'luwipress-gold' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$this->end_controls_section();
	}

	protected function render_stars( $rating ) {
		$r = max( 1, min( 5, (int) $rating ) );
		$out = '<span class="lwp-tst__stars" aria-label="' . esc_attr( sprintf( __( '%d out of 5 stars', 'luwipress-gold' ), $r ) ) . '">';
		for ( $i = 1; $i <= 5; $i++ ) {
			$out .= $i <= $r ? '<span class="is-on">★</span>' : '<span>★</span>';
		}
		return $out . '</span>';
	}

	protected function render() {
		$s       = $this->get_settings_for_display();
		$items   = is_array( $s['reviews'] ?? null ) ? $s['reviews'] : [];
		if ( empty( $items ) ) { return; }
		$layout  = ( $s['layout'] ?? 'carousel' ) === 'grid' ? 'grid' : 'carousel';
		$schema  = ( $s['show_schema'] ?? 'yes' ) === 'yes';
		?>
		<div class="lwp-tst" data-layout="<?php echo esc_attr( $layout ); ?>">
			<div class="lwp-tst__track">
				<?php foreach ( $items as $r ) :
					$q = trim( (string) ( $r['quote'] ?? '' ) );
					if ( $q === '' ) { continue; }
					$name     = trim( (string) ( $r['name'] ?? '' ) );
					$location = trim( (string) ( $r['location'] ?? '' ) );
					$product  = trim( (string) ( $r['product'] ?? '' ) );
					$rating   = $r['rating'] ?? '5';
					$avatar   = $r['avatar']['url'] ?? '';
					$date     = trim( (string) ( $r['date'] ?? '' ) );
					?>
					<article class="lwp-tst__card" <?php echo $schema ? 'itemscope itemtype="https://schema.org/Review"' : ''; ?>>
						<?php echo $this->render_stars( $rating ); ?>
						<?php if ( $schema ) : ?>
							<meta itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating" content="<?php echo esc_attr( $rating ); ?>" />
						<?php endif; ?>
						<blockquote class="lwp-tst__quote" <?php echo $schema ? 'itemprop="reviewBody"' : ''; ?>>
							<?php echo esc_html( $q ); ?>
						</blockquote>
						<footer class="lwp-tst__foot">
							<?php if ( $avatar ) : ?>
								<div class="lwp-tst__avatar"><img loading="lazy" decoding="async" src="<?php echo esc_url( $avatar ); ?>" alt="" width="44" height="44" /></div>
							<?php elseif ( $name ) : ?>
								<div class="lwp-tst__avatar lwp-tst__avatar--initial"><span aria-hidden="true"><?php echo esc_html( mb_substr( $name, 0, 1 ) ); ?></span></div>
							<?php endif; ?>
							<div class="lwp-tst__who">
								<?php if ( $name ) : ?>
									<span class="lwp-tst__name" <?php echo $schema ? 'itemprop="author" itemscope itemtype="https://schema.org/Person"' : ''; ?>>
										<?php if ( $schema ) : ?><span itemprop="name"><?php echo esc_html( $name ); ?></span><?php else : ?><?php echo esc_html( $name ); ?><?php endif; ?>
									</span>
								<?php endif; ?>
								<?php if ( $location || $product ) : ?>
									<span class="lwp-tst__meta">
										<?php echo esc_html( trim( implode( ' · ', array_filter( [ $location, $product ] ) ) ) ); ?>
									</span>
								<?php endif; ?>
							</div>
							<?php if ( $date && $schema ) : ?><meta itemprop="datePublished" content="<?php echo esc_attr( $date ); ?>" /><?php endif; ?>
						</footer>
					</article>
				<?php endforeach; ?>
			</div>
			<?php if ( $layout === 'carousel' ) : ?>
				<div class="lwp-tst__dots" role="tablist" aria-label="<?php esc_attr_e( 'Testimonials navigation', 'luwipress-gold' ); ?>"></div>
			<?php endif; ?>
		</div>
		<?php
	}
}
