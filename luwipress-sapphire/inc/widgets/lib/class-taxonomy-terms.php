<?php
/**
 * Widget: Taxonomy Terms (generic).
 *
 * One reusable widget that lists the terms of ANY public taxonomy as a tile
 * grid — product categories, product brands, vendor groups (lwp_vendor_group),
 * post categories/tags, or any custom taxonomy. Replaces having a bespoke
 * widget per taxonomy: the operator picks the taxonomy + scope, the widget
 * queries `get_terms()` and renders the same `.lwp-cgrid` tile chrome used by
 * the Category Grid (so styling is inherited, no new CSS).
 *
 * Term images come from the `thumbnail_id` term meta (WooCommerce product_cat
 * / product_brand set this); taxonomies without an image fall back to the
 * brand-gold gradient palette. Each tile links to the term archive.
 *
 * @since 1.10.5
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class LuwiPress_Sapphire_Widget_Taxonomy_Terms extends Widget_Base {

	public function get_name()        { return 'lwp-taxonomy-terms'; }
	public function get_title()       { return __( 'Taxonomy Terms', 'luwipress-sapphire' ); }
	public function get_icon()        { return 'eicon-folder-o'; }
	public function get_categories()  { return [ 'luwipress-sapphire' ]; }
	public function get_keywords()    { return [ 'taxonomy', 'terms', 'category', 'brand', 'vendor', 'group', 'tag', 'grid', 'browse' ]; }
	public function get_style_depends() { return [ 'luwipress-sapphire-widgets' ]; }

	/**
	 * Public taxonomies the operator can pick — excludes WP-internal ones that
	 * have no useful front-end archive.
	 *
	 * @return array slug => "Label (slug)"
	 */
	private function taxonomy_options() {
		$skip = [ 'nav_menu', 'link_category', 'post_format', 'wp_pattern_category', 'wp_theme', 'wp_template_part_area' ];
		$out  = [];
		if ( function_exists( 'get_taxonomies' ) ) {
			$taxes = get_taxonomies( [ 'public' => true ], 'objects' );
			foreach ( $taxes as $tx ) {
				if ( in_array( $tx->name, $skip, true ) ) { continue; }
				$label = isset( $tx->labels->name ) ? $tx->labels->name : $tx->label;
				$out[ $tx->name ] = sprintf( '%s (%s)', $label, $tx->name );
			}
		}
		if ( empty( $out ) ) {
			$out['product_cat'] = 'Product categories (product_cat)';
			$out['category']    = 'Categories (category)';
		}
		return $out;
	}

	protected function register_controls() {

		/* ───────────── Source ───────────── */
		$this->start_controls_section( 'section_source', [ 'label' => __( 'Source', 'luwipress-sapphire' ) ] );

		$tax_opts = $this->taxonomy_options();
		$this->add_control( 'taxonomy', [
			'label'   => __( 'Taxonomy', 'luwipress-sapphire' ),
			'type'    => Controls_Manager::SELECT,
			'default' => array_key_exists( 'product_cat', $tax_opts ) ? 'product_cat' : (string) array_key_first( $tax_opts ),
			'options' => $tax_opts,
		] );

		$this->add_control( 'scope', [
			'label'   => __( 'Which terms', 'luwipress-sapphire' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'top_level',
			'options' => [
				'top_level'   => __( 'Top-level only (no children)', 'luwipress-sapphire' ),
				'all'         => __( 'All terms (flat)', 'luwipress-sapphire' ),
				'children_of' => __( 'Children of a specific term', 'luwipress-sapphire' ),
			],
		] );

		$this->add_control( 'parent_term', [
			'label'       => __( 'Parent term (ID or slug)', 'luwipress-sapphire' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => '',
			'placeholder' => 'e.g. 25  or  string-instruments',
			'condition'   => [ 'scope' => 'children_of' ],
		] );

		$this->add_control( 'hide_empty', [
			'label'        => __( 'Hide empty terms', 'luwipress-sapphire' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$this->add_control( 'orderby', [
			'label'   => __( 'Order by', 'luwipress-sapphire' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'count',
			'options' => [
				'count'      => __( 'Product / post count', 'luwipress-sapphire' ),
				'name'       => __( 'Name (A→Z)', 'luwipress-sapphire' ),
				'slug'       => __( 'Slug', 'luwipress-sapphire' ),
				'term_order' => __( 'Manual term order', 'luwipress-sapphire' ),
				'menu_order' => __( 'Menu order (WC)', 'luwipress-sapphire' ),
			],
		] );

		$this->add_control( 'order', [
			'label'   => __( 'Order direction', 'luwipress-sapphire' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'DESC',
			'options' => [ 'DESC' => 'DESC', 'ASC' => 'ASC' ],
		] );

		$this->add_control( 'limit', [
			'label'   => __( 'Max terms', 'luwipress-sapphire' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 12,
			'min'     => 1,
			'max'     => 100,
		] );

		$this->add_control( 'include_slugs', [
			'label'       => __( 'Only these slugs (optional)', 'luwipress-sapphire' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => '',
			'placeholder' => 'oud, darbuka, qanun',
			'description' => __( 'Comma-separated. Overrides scope/order — shows exactly these, in this order.', 'luwipress-sapphire' ),
		] );

		$this->add_control( 'exclude_slugs', [
			'label'       => __( 'Exclude these slugs (optional)', 'luwipress-sapphire' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => 'uncategorized',
			'placeholder' => 'uncategorized',
		] );

		$this->end_controls_section();

		/* ───────────── Display ───────────── */
		$this->start_controls_section( 'section_display', [ 'label' => __( 'Display', 'luwipress-sapphire' ) ] );

		$this->add_control( 'columns', [
			'label'   => __( 'Columns', 'luwipress-sapphire' ),
			'type'    => Controls_Manager::SELECT,
			'default' => '4',
			'options' => [ '2' => '2', '3' => '3', '4' => '4' ],
		] );

		$this->add_control( 'count_label', [
			'label'       => __( 'Eyebrow label after the count', 'luwipress-sapphire' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => __( 'items', 'luwipress-sapphire' ),
			'description' => __( 'e.g. "items" → "11 items". Leave empty to hide the count line.', 'luwipress-sapphire' ),
		] );

		$this->add_control( 'show_image', [
			'label'        => __( 'Show term image', 'luwipress-sapphire' ),
			'description'  => __( 'Uses the term thumbnail (WooCommerce category/brand images). Falls back to a gold gradient.', 'luwipress-sapphire' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$this->add_control( 'show_description', [
			'label'        => __( 'Show description sub-line', 'luwipress-sapphire' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
		] );

		$this->end_controls_section();
	}

	/**
	 * @return \WP_Term[] resolved + filtered term list (never WP_Error).
	 */
	private function query_terms( $s ) {
		$tax = sanitize_key( $s['taxonomy'] ?? 'product_cat' );
		if ( ! $tax || ! taxonomy_exists( $tax ) ) {
			return [];
		}

		$hide_empty = ( ( $s['hide_empty'] ?? 'yes' ) === 'yes' );
		$limit      = max( 1, min( 100, (int) ( $s['limit'] ?? 12 ) ) );

		$include = array_values( array_filter( array_map( 'trim', explode( ',', (string) ( $s['include_slugs'] ?? '' ) ) ) ) );

		$args = [
			'taxonomy'   => $tax,
			'hide_empty' => $hide_empty,
		];

		if ( ! empty( $include ) ) {
			// Explicit curation — exact slugs, preserve the operator's order below.
			$args['slug']    = $include;
			$args['number']  = 0;
			$args['orderby'] = 'name';
		} else {
			$args['number']  = $limit;
			$args['orderby'] = sanitize_key( $s['orderby'] ?? 'count' );
			$args['order']   = strtoupper( $s['order'] ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC';

			$scope = $s['scope'] ?? 'top_level';
			if ( 'top_level' === $scope ) {
				$args['parent'] = 0;
			} elseif ( 'children_of' === $scope ) {
				$pid = $this->resolve_parent( trim( (string) ( $s['parent_term'] ?? '' ) ), $tax );
				if ( $pid ) { $args['parent'] = $pid; }
			}
		}

		$terms = get_terms( $args );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return [];
		}

		// Preserve include-slug order when curated.
		if ( ! empty( $include ) ) {
			$by_slug = [];
			foreach ( $terms as $t ) { $by_slug[ $t->slug ] = $t; }
			$ordered = [];
			foreach ( $include as $slug ) {
				if ( isset( $by_slug[ $slug ] ) ) { $ordered[] = $by_slug[ $slug ]; }
			}
			$terms = $ordered;
		}

		// Exclude slugs (post-filter — works regardless of scope/include).
		$exclude = array_values( array_filter( array_map( 'trim', explode( ',', (string) ( $s['exclude_slugs'] ?? '' ) ) ) ) );
		if ( ! empty( $exclude ) ) {
			$terms = array_values( array_filter( $terms, function ( $t ) use ( $exclude ) {
				return ! in_array( $t->slug, $exclude, true );
			} ) );
		}

		return array_slice( $terms, 0, empty( $include ) ? $limit : 100 );
	}

	private function resolve_parent( $val, $tax ) {
		if ( $val === '' ) { return 0; }
		if ( ctype_digit( $val ) ) { return (int) $val; }
		$t = get_term_by( 'slug', $val, $tax );
		return ( $t instanceof \WP_Term ) ? (int) $t->term_id : 0;
	}

	protected function render() {
		$s = $this->get_settings_for_display();

		$terms = $this->query_terms( $s );
		if ( empty( $terms ) ) {
			if ( class_exists( '\\Elementor\\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="lwp-cgrid lwp-cgrid--empty"><em>' . esc_html__( '[Taxonomy Terms] No terms match the current settings.', 'luwipress-sapphire' ) . '</em></div>';
			}
			return;
		}

		$columns     = in_array( $s['columns'] ?? '4', [ '2', '3', '4' ], true ) ? $s['columns'] : '4';
		$count_label = trim( (string) ( $s['count_label'] ?? '' ) );
		$show_image  = ( ( $s['show_image'] ?? 'yes' ) === 'yes' );
		$show_desc   = ( ( $s['show_description'] ?? '' ) === 'yes' );

		$palette = [
			[ '#d4b97a', '#7a5a2c' ], [ '#3d2f1f', '#7a5a2c' ], [ '#5a3a2a', '#c89a5a' ],
			[ '#2a3d3a', '#7aa494' ], [ '#7a5a3a', '#b89a6a' ], [ '#3a342c', '#9a7b3a' ],
		];
		?>
		<div class="lwp-cgrid" data-columns="<?php echo esc_attr( $columns ); ?>">
			<?php
			$i = 0;
			foreach ( $terms as $t ) :
				if ( ! ( $t instanceof \WP_Term ) ) { continue; }
				$link = get_term_link( $t );
				if ( is_wp_error( $link ) ) { continue; }

				$img = '';
				if ( $show_image && function_exists( 'get_term_meta' ) ) {
					$thumb = get_term_meta( $t->term_id, 'thumbnail_id', true );
					if ( $thumb ) {
						$img = wp_get_attachment_image_url( (int) $thumb, 'large' );
					}
				}
				$pal = $palette[ $i % count( $palette ) ];
				$bg  = $img
					? 'background-image: linear-gradient(rgba(0,0,0,0.15), rgba(0,0,0,0.45)), url(' . esc_url( $img ) . '); background-size: cover; background-position: center;'
					: 'background: linear-gradient(135deg, ' . esc_attr( $pal[0] ) . ', ' . esc_attr( $pal[1] ) . ');';
				$i++;
				?>
				<a class="lwp-cgrid__tile" href="<?php echo esc_url( $link ); ?>" style="<?php echo $bg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- values escaped above. ?>">
					<?php if ( $count_label !== '' && (int) $t->count > 0 ) : ?>
						<span class="lwp-cgrid__eyebrow"><?php echo esc_html( sprintf( '%d %s', (int) $t->count, $count_label ) ); ?></span>
					<?php endif; ?>
					<h3 class="lwp-cgrid__title"><?php echo esc_html( $t->name ); ?></h3>
					<?php if ( $show_desc && $t->description ) : ?>
						<span class="lwp-cgrid__sub"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $t->description ), 14 ) ); ?></span>
					<?php endif; ?>
					<span class="lwp-cgrid__arrow" aria-hidden="true">&rarr;</span>
				</a>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
