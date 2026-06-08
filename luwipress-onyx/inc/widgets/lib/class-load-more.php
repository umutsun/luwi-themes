<?php
/**
 * Widget: Load More.
 *
 * "Load 9 more posts" anchor button. Two modes:
 *   - link:  plain anchor to next paged URL (no JS, SEO-friendly)
 *   - ajax:  hijacks click, fetches next page, appends to target container
 *            (target selector configurable, default WC/WP loops)
 *
 * Auto-hides on the last page.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class LuwiPress_Onyx_Widget_Load_More extends Widget_Base {

	public function get_name()        { return 'lwp-load-more'; }
	public function get_title()       { return __( 'Load More', 'luwipress-onyx' ); }
	public function get_icon()        { return 'eicon-loop-builder'; }
	public function get_categories()  { return [ 'luwipress-onyx' ]; }
	public function get_keywords()    { return [ 'load', 'more', 'pagination', 'infinite', 'ajax' ]; }
	public function get_style_depends() { return [ 'luwipress-onyx-widgets' ]; }
	public function get_script_depends() { return [ 'luwipress-onyx-frontend' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_content', [ 'label' => __( 'Button', 'luwipress-onyx' ) ] );

		$this->add_control(
			'mode',
			[
				'label'   => __( 'Mode', 'luwipress-onyx' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'link',
				'options' => [
					'link' => __( 'Plain link (SEO-friendly)', 'luwipress-onyx' ),
					'ajax' => __( 'AJAX (in-place append)', 'luwipress-onyx' ),
				],
			]
		);
		$this->add_control(
			'label_template',
			[
				'label'   => __( 'Label', 'luwipress-onyx' ),
				'description' => __( 'Use %d for remaining count placeholder.', 'luwipress-onyx' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Load %d more posts', 'luwipress-onyx' ),
			]
		);
		$this->add_control(
			'target_selector',
			[
				'label'   => __( 'Target container (AJAX mode)', 'luwipress-onyx' ),
				'description' => __( 'Where to append new posts. Default catches WC / WP loops.', 'luwipress-onyx' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '.elementor-posts-container, ul.products, .lwp-egrid',
				'condition' => [ 'mode' => 'ajax' ],
			]
		);
		$this->add_control(
			'hide_when_done',
			[
				'label'        => __( 'Hide when no more pages', 'luwipress-onyx' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();

		/* Style */
		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-onyx' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
		$this->add_control( 'text_color', [ 'label' => __( 'Text color', 'luwipress-onyx' ), 'type' => Controls_Manager::COLOR, 'default' => '#1A1612',
			'selectors' => [ '{{WRAPPER}} .lwp-lm__btn' => 'color: {{VALUE}}; border-color: {{VALUE}};' ] ] );
		$this->add_control( 'bg_hover', [ 'label' => __( 'Hover background', 'luwipress-onyx' ), 'type' => Controls_Manager::COLOR, 'default' => '#1A1612',
			'selectors' => [ '{{WRAPPER}} .lwp-lm__btn:hover' => 'background: {{VALUE}}; color: #fff;' ] ] );
		$this->end_controls_section();
	}

	private function next_page_url_and_remaining() {
		global $wp_query;
		if ( ! ( $wp_query instanceof \WP_Query ) ) {
			return [ '', 0 ];
		}
		$paged  = max( 1, (int) $wp_query->get( 'paged' ) ?: 1 );
		$max    = (int) $wp_query->max_num_pages;
		if ( $paged >= $max ) {
			return [ '', 0 ];
		}
		// Compute remaining items not yet shown
		$ppp        = (int) $wp_query->get( 'posts_per_page' ) ?: get_option( 'posts_per_page', 10 );
		$found      = (int) $wp_query->found_posts;
		$shown      = $paged * $ppp;
		$remaining  = max( 0, $found - $shown );
		$next_paged = $paged + 1;
		$next_url   = get_pagenum_link( $next_paged );
		return [ $next_url, $remaining ];
	}

	protected function render() {
		$s          = $this->get_settings_for_display();
		$mode       = $s['mode'] ?? 'link';
		$tmpl       = trim( (string) ( $s['label_template'] ?? 'Load more' ) );
		$target     = trim( (string) ( $s['target_selector'] ?? '' ) );
		$hide_done  = ( $s['hide_when_done'] ?? 'yes' ) === 'yes';

		[ $next_url, $remaining ] = $this->next_page_url_and_remaining();
		if ( ! $next_url ) {
			if ( $hide_done ) return;
			$next_url = '#';
			$remaining = 0;
		}
		$label = sprintf( $tmpl, max( 0, (int) $remaining ) );
		?>
		<div class="lwp-lm">
			<a class="lwp-lm__btn"
				href="<?php echo esc_url( $next_url ); ?>"
				<?php if ( $mode === 'ajax' ) : ?>
					data-lwp-loadmore="ajax"
					data-lwp-target="<?php echo esc_attr( $target ); ?>"
				<?php endif; ?>>
				<?php echo esc_html( $label ); ?>
			</a>
		</div>
		<?php
	}
}
