<?php
/**
 * Widget: Featured Post.
 *
 * Large image-tile + meta-block "hero card" for blog/journal archives.
 * Three modes:
 *   - auto-sticky:   first sticky post WP-wide
 *   - auto-latest:   latest post in current archive context (or sitewide)
 *   - manual:        operator picks a specific post by ID
 *
 * Layout: image-left | meta-right (or stacked on mobile). Meta block has
 * category eyebrow + read-time + h2 title + excerpt + author + "Read on" CTA.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class LuwiPress_Onyx_Widget_Featured_Post extends Widget_Base {

	public function get_name()        { return 'lwp-featured-post'; }
	public function get_title()       { return __( 'Featured Post', 'luwipress-onyx' ); }
	public function get_icon()        { return 'eicon-post-content'; }
	public function get_categories()  { return [ 'luwipress-onyx' ]; }
	public function get_keywords()    { return [ 'featured', 'post', 'sticky', 'archive', 'journal' ]; }
	public function get_style_depends() { return [ 'luwipress-onyx-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_content', [ 'label' => __( 'Source', 'luwipress-onyx' ) ] );

		$this->add_control(
			'source',
			[
				'label'   => __( 'Source', 'luwipress-onyx' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto-sticky',
				'options' => [
					'auto-sticky' => __( 'Auto: first sticky post', 'luwipress-onyx' ),
					'auto-latest' => __( 'Auto: latest post', 'luwipress-onyx' ),
					'manual'      => __( 'Manual: pick post by ID', 'luwipress-onyx' ),
				],
			]
		);
		$this->add_control(
			'manual_post_id',
			[
				'label'     => __( 'Post ID', 'luwipress-onyx' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 0,
				'min'       => 0,
				'condition' => [ 'source' => 'manual' ],
			]
		);
		$this->add_control(
			'post_type',
			[
				'label'   => __( 'Post type', 'luwipress-onyx' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'post',
				'options' => [ 'post' => 'Post', 'page' => 'Page', 'product' => 'Product' ],
				'condition' => [ 'source!' => 'manual' ],
			]
		);

		$this->add_control(
			'image_side',
			[
				'label'   => __( 'Image side', 'luwipress-onyx' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'left',
				'options' => [ 'left' => __( 'Image left', 'luwipress-onyx' ), 'right' => __( 'Image right', 'luwipress-onyx' ) ],
			]
		);
		$this->add_control(
			'show_excerpt',
			[ 'label' => __( 'Show excerpt', 'luwipress-onyx' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ]
		);
		$this->add_control(
			'show_meta',
			[ 'label' => __( 'Show meta (category · read time)', 'luwipress-onyx' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ]
		);
		$this->add_control(
			'cta_label',
			[ 'label' => __( 'CTA label', 'luwipress-onyx' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Read on →', 'luwipress-onyx' ) ]
		);

		$this->end_controls_section();

		/* Style */
		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-onyx' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
		$this->add_control( 'title_color', [ 'label' => __( 'Title color', 'luwipress-onyx' ), 'type' => Controls_Manager::COLOR, 'default' => '#1A1612',
			'selectors' => [ '{{WRAPPER}} .lwp-fp__title' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'eyebrow_color', [ 'label' => __( 'Eyebrow color', 'luwipress-onyx' ), 'type' => Controls_Manager::COLOR, 'default' => '#9A7B3A',
			'selectors' => [ '{{WRAPPER}} .lwp-fp__eyebrow' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'cta_color', [ 'label' => __( 'CTA color', 'luwipress-onyx' ), 'type' => Controls_Manager::COLOR, 'default' => '#1A1612',
			'selectors' => [ '{{WRAPPER}} .lwp-fp__cta' => 'color: {{VALUE}}; border-color: {{VALUE}};' ] ] );
		$this->end_controls_section();
	}

	private function find_post( $source, $manual_id, $post_type ) {
		if ( $source === 'manual' ) {
			$p = get_post( (int) $manual_id );
			return ( $p && $p->post_status === 'publish' ) ? $p : null;
		}
		if ( $source === 'auto-sticky' ) {
			$stickies = get_option( 'sticky_posts', [] );
			if ( ! empty( $stickies ) ) {
				$p = get_post( (int) $stickies[0] );
				if ( $p && $p->post_status === 'publish' ) return $p;
			}
			// Fall through to latest if no sticky
		}
		// auto-latest (or sticky-fallback)
		$args = [
			'post_type'      => $post_type,
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		];
		$posts = get_posts( $args );
		return $posts ? $posts[0] : null;
	}

	private function estimate_read_time( $content ) {
		$words = str_word_count( wp_strip_all_tags( (string) $content ) );
		$min   = (int) ceil( $words / 220 );
		return max( 1, $min );
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$src   = $s['source'] ?? 'auto-sticky';
		$mid   = (int) ( $s['manual_post_id'] ?? 0 );
		$ptype = $s['post_type'] ?? 'post';

		$post = $this->find_post( $src, $mid, $ptype );
		if ( ! $post ) {
			echo '<div class="lwp-fp lwp-fp--empty"><p>' . esc_html__( 'No featured post found.', 'luwipress-onyx' ) . '</p></div>';
			return;
		}

		$image_side = ( $s['image_side'] ?? 'left' ) === 'right' ? 'right' : 'left';
		$show_excerpt = ( $s['show_excerpt'] ?? 'yes' ) === 'yes';
		$show_meta    = ( $s['show_meta']    ?? 'yes' ) === 'yes';
		$cta_label    = trim( (string) ( $s['cta_label'] ?? '' ) );

		$thumb_url = get_the_post_thumbnail_url( $post, 'large' );
		$cats      = get_the_category( $post->ID );
		$cat_label = $cats ? $cats[0]->name : '';
		$read_min  = $this->estimate_read_time( $post->post_content );
		$excerpt   = has_excerpt( $post ) ? wp_strip_all_tags( $post->post_excerpt ) : wp_trim_words( wp_strip_all_tags( $post->post_content ), 30 );
		?>
		<a class="lwp-fp lwp-fp--<?php echo esc_attr( $image_side ); ?>" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
			<div class="lwp-fp__img" <?php if ( $thumb_url ) : ?>style="background-image: url(<?php echo esc_url( $thumb_url ); ?>);"<?php endif; ?>></div>
			<div class="lwp-fp__body">
				<?php if ( $show_meta && ( $cat_label || $read_min ) ) : ?>
					<span class="lwp-fp__eyebrow">
						<?php if ( $cat_label ) : ?><?php echo esc_html( $cat_label ); ?><?php endif; ?>
						<?php if ( $cat_label && $read_min ) : ?> · <?php endif; ?>
						<?php if ( $read_min ) : ?><?php printf( esc_html( _n( '%d min read', '%d min read', $read_min, 'luwipress-onyx' ) ), $read_min ); ?><?php endif; ?>
					</span>
				<?php endif; ?>
				<h2 class="lwp-fp__title"><?php echo esc_html( get_the_title( $post ) ); ?></h2>
				<?php if ( $show_excerpt && $excerpt ) : ?>
					<p class="lwp-fp__excerpt"><?php echo esc_html( $excerpt ); ?></p>
				<?php endif; ?>
				<?php if ( $cta_label ) : ?>
					<span class="lwp-fp__cta"><?php echo esc_html( $cta_label ); ?></span>
				<?php endif; ?>
			</div>
		</a>
		<?php
	}
}
