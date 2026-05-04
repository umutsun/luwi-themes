<?php
/**
 * Widget: Editorial Grid.
 *
 * 1-large + 2-small blog post grid for the homepage Journal section.
 * Pulls from any post type the operator picks (default: posts).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class LuwiPress_Gold_Widget_Editorial_Grid extends Widget_Base {

	public function get_name()        { return 'lwp-editorial-grid'; }
	public function get_title()       { return __( 'Editorial Grid', 'luwipress-gold' ); }
	public function get_icon()        { return 'eicon-posts-grid'; }
	public function get_categories()  { return [ 'luwipress-gold' ]; }
	public function get_keywords()    { return [ 'journal', 'blog', 'editorial', 'posts', 'grid', 'featured' ]; }
	public function get_style_depends() { return [ 'luwipress-gold-widgets' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'section_query', [ 'label' => __( 'Query', 'luwipress-gold' ) ] );

		$this->add_control(
			'post_type',
			[
				'label'   => __( 'Post type', 'luwipress-gold' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'post',
				'options' => $this->get_public_post_types(),
			]
		);
		$this->add_control(
			'count',
			[
				'label'   => __( 'Number of posts', 'luwipress-gold' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 3,
				'min'     => 1,
				'max'     => 12,
			]
		);
		$this->add_control(
			'orderby',
			[
				'label'   => __( 'Order by', 'luwipress-gold' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'date',
				'options' => [
					'date'     => __( 'Newest first', 'luwipress-gold' ),
					'modified' => __( 'Recently updated', 'luwipress-gold' ),
					'rand'     => __( 'Random', 'luwipress-gold' ),
					'comment_count' => __( 'Most discussed', 'luwipress-gold' ),
				],
			]
		);
		$this->add_control(
			'category',
			[
				'label'       => __( 'Filter by category', 'luwipress-gold' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'options'     => $this->get_category_options(),
				'description' => __( 'Leave empty for all.', 'luwipress-gold' ),
			]
		);

		$this->end_controls_section();

		/* Heading */
		$this->start_controls_section( 'section_head', [ 'label' => __( 'Section heading', 'luwipress-gold' ) ] );
		$this->add_control(
			'eyebrow',
			[
				'label'   => __( 'Eyebrow', 'luwipress-gold' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Journal', 'luwipress-gold' ),
			]
		);
		$this->add_control(
			'heading',
			[
				'label'   => __( 'Heading', 'luwipress-gold' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 2,
				'default' => __( 'Notes from the workshop.', 'luwipress-gold' ),
			]
		);
		$this->add_control(
			'all_link',
			[
				'label'   => __( 'View all link', 'luwipress-gold' ),
				'type'    => Controls_Manager::URL,
				'default' => [ 'url' => '' ],
			]
		);
		$this->add_control(
			'all_label',
			[
				'label'   => __( 'View all label', 'luwipress-gold' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'All articles →', 'luwipress-gold' ),
			]
		);
		$this->end_controls_section();
	}

	private function get_public_post_types() {
		$out = [];
		foreach ( get_post_types( [ 'public' => true ], 'objects' ) as $pt ) {
			if ( $pt->name === 'attachment' ) continue;
			$out[ $pt->name ] = $pt->labels->name ?? $pt->name;
		}
		return $out;
	}

	private function get_category_options() {
		$cats = get_categories( [ 'hide_empty' => false, 'number' => 100 ] );
		$out = [];
		foreach ( $cats as $c ) {
			$out[ $c->term_id ] = $c->name;
		}
		return $out;
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$args = [
			'post_type'      => $s['post_type'] ?: 'post',
			'posts_per_page' => max( 1, (int) $s['count'] ),
			'orderby'        => $s['orderby'] ?: 'date',
			'order'          => 'DESC',
			'post_status'    => 'publish',
			'no_found_rows'  => true,
		];
		if ( ! empty( $s['category'] ) ) {
			$args['category__in'] = array_map( 'intval', (array) $s['category'] );
		}
		$query = new WP_Query( $args );
		if ( ! $query->have_posts() ) {
			echo '<div class="lwp-eg lwp-eg--empty">' . esc_html__( 'No posts found.', 'luwipress-gold' ) . '</div>';
			return;
		}
		?>
		<section class="lwp-eg-section">
			<header class="lwp-eg-head">
				<div>
					<?php if ( $s['eyebrow'] ) : ?><span class="lwp-eg-eyebrow">— <?php echo esc_html( $s['eyebrow'] ); ?></span><?php endif; ?>
					<?php if ( $s['heading'] ) : ?><h2 class="lwp-eg-heading"><?php echo esc_html( $s['heading'] ); ?></h2><?php endif; ?>
				</div>
				<?php if ( ! empty( $s['all_link']['url'] ) ) : ?>
					<a href="<?php echo esc_url( $s['all_link']['url'] ); ?>" class="lwp-eg-all"<?php echo ! empty( $s['all_link']['is_external'] ) ? ' target="_blank" rel="noopener"' : ''; ?>>
						<?php echo esc_html( $s['all_label'] ); ?>
					</a>
				<?php endif; ?>
			</header>

			<div class="lwp-eg-grid">
			<?php
			$i = 0;
			while ( $query->have_posts() ) :
				$query->the_post();
				$i++;
				$cls   = $i === 1 ? 'lwp-eg-card lwp-eg-card--feat' : 'lwp-eg-card';
				$cats  = get_the_category();
				$cat   = $cats ? $cats[0]->name : '';
				$image = get_the_post_thumbnail_url( null, 'large' );
				?>
				<a class="<?php echo esc_attr( $cls ); ?>" href="<?php the_permalink(); ?>">
					<div class="lwp-eg-img" <?php echo $image ? 'style="background-image:url(' . esc_url( $image ) . ')"' : ''; ?>></div>
					<div class="lwp-eg-meta">
						<?php if ( $cat ) : ?><span class="lwp-eg-cat"><?php echo esc_html( $cat ); ?></span><?php endif; ?>
						<h3 class="lwp-eg-title"><?php the_title(); ?></h3>
						<?php if ( $i === 1 ) : ?>
							<p class="lwp-eg-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 28 ) ); ?></p>
						<?php endif; ?>
						<span class="lwp-eg-byline"><?php echo esc_html( get_the_date() ); ?></span>
					</div>
				</a>
			<?php endwhile; ?>
			</div>
		</section>
		<?php
		wp_reset_postdata();
	}
}
