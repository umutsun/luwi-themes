<?php
/**
 * Widget: LuwiPress Gold Mega Menu.
 *
 * Renders a sticky-header navigation strip whose top-level items expand
 * into multi-column dropdown panels on hover. Pulls items + hierarchy
 * from any registered WP nav menu (`wp_get_nav_menu_items`), so the
 * operator builds the menu in Appearance → Menus and the widget adapts
 * to whatever's there — no separate "mega menu builder" required.
 *
 * Auto-classification rule:
 *   - Top-level item with NO children   → flat link
 *   - Top-level item with 1-3 children  → simple dropdown
 *   - Top-level item with 4+ children
 *     OR ANY child that has its own children → MEGA panel
 *       Children are split into 2-3 columns by even count;
 *       deepest leaves render as link list under each column header.
 *
 * The widget also reserves room for a "featured" card in mega panels —
 * the operator can attach a product/post via menu item meta
 * (custom field `_lwp_gold_mega_featured` saved per top item).
 *
 * No mouse-out timer: pure CSS hover via `<details>` polyfill on mobile,
 * and `:hover` + a 150ms cancel timeout on desktop (handled in app JS).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class LuwiPress_Gold_Widget_Mega_Menu extends Widget_Base {

	public function get_name()        { return 'lwp-mega-menu'; }
	public function get_title()       { return __( 'Mega Menu', 'luwipress-gold' ); }
	public function get_icon()        { return 'eicon-nav-menu'; }
	public function get_categories()  { return [ 'luwipress-gold' ]; }
	public function get_keywords()    { return [ 'menu', 'mega', 'nav', 'navigation', 'header', 'dropdown' ]; }
	public function get_style_depends() { return [ 'luwipress-gold-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_source', [ 'label' => __( 'Source', 'luwipress-gold' ) ] );

		$this->add_control(
			'menu_id',
			[
				'label'       => __( 'Menu', 'luwipress-gold' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => $this->get_menu_options(),
				'description' => __( 'Build the menu in Appearance → Menus, then pick it here. The widget auto-detects which top-level items deserve a mega panel.', 'luwipress-gold' ),
			]
		);

		$this->add_control(
			'mega_threshold',
			[
				'label'       => __( 'Mega panel threshold', 'luwipress-gold' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 4,
				'min'         => 2,
				'max'         => 12,
				'description' => __( 'A top-level item becomes a mega panel when it has at least this many children, or when any child has its own children.', 'luwipress-gold' ),
			]
		);

		$this->add_control(
			'mega_columns',
			[
				'label'   => __( 'Mega panel columns', 'luwipress-gold' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => [
					'auto' => __( 'Auto (2 or 3 by item count)', 'luwipress-gold' ),
					'2'    => __( '2 columns', 'luwipress-gold' ),
					'3'    => __( '3 columns', 'luwipress-gold' ),
					'4'    => __( '4 columns', 'luwipress-gold' ),
				],
			]
		);

		$this->add_control(
			'show_counts',
			[
				'label'   => __( 'Show item counts', 'luwipress-gold' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
				'description' => __( 'When a menu item points at a product category, show that category\'s product count next to the link.', 'luwipress-gold' ),
			]
		);

		$this->add_control(
			'mobile_collapse',
			[
				'label'   => __( 'Mobile mode', 'luwipress-gold' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'drawer',
				'options' => [
					'drawer'    => __( 'Off-canvas drawer (hamburger)', 'luwipress-gold' ),
					'accordion' => __( 'In-place accordion', 'luwipress-gold' ),
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-gold' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control(
			'top_color',
			[
				'label'     => __( 'Top item color', 'luwipress-gold' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1b1c1c',
				'selectors' => [ '{{WRAPPER}} .lwp-mm > ul > li > a' => 'color: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'top_hover_color',
			[
				'label'     => __( 'Top item hover color', 'luwipress-gold' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#735c00',
				'selectors' => [ '{{WRAPPER}} .lwp-mm > ul > li > a:hover' => 'color: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'panel_bg',
			[
				'label'     => __( 'Panel background', 'luwipress-gold' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [ '{{WRAPPER}} .lwp-mm-panel' => 'background: {{VALUE}};' ],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'top_typography',
				'selector' => '{{WRAPPER}} .lwp-mm > ul > li > a',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * @return array  menu_id => name
	 */
	private function get_menu_options() {
		$out = [ '' => __( '— Pick a menu —', 'luwipress-gold' ) ];
		$menus = wp_get_nav_menus();
		foreach ( $menus as $m ) {
			$count = wp_get_nav_menu_items( $m->term_id );
			$count = is_array( $count ) ? count( $count ) : 0;
			$out[ $m->term_id ] = sprintf( '%s (%d items)', $m->name, $count );
		}
		return $out;
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$menu_id = (int) ( $s['menu_id'] ?? 0 );
		if ( ! $menu_id ) {
			$this->placeholder( __( 'Pick a menu in the panel →', 'luwipress-gold' ) );
			return;
		}

		$items = wp_get_nav_menu_items( $menu_id );
		if ( empty( $items ) ) {
			$this->placeholder( __( 'This menu has no items yet.', 'luwipress-gold' ) );
			return;
		}

		$tree = self::build_tree( $items );
		$threshold = max( 2, (int) ( $s['mega_threshold'] ?? 4 ) );
		$cols_pref = $s['mega_columns'] ?? 'auto';
		$show_counts = ( $s['show_counts'] ?? 'yes' ) === 'yes';

		self::render_navigation_html( $menu_id, [
			'threshold'   => $threshold,
			'cols_pref'   => $cols_pref,
			'show_counts' => $show_counts,
		] );
	}

	/**
	 * Public static facade so the theme header (header.php) can render the
	 * complete mega menu — including featured-product slot, count badges,
	 * and dropdown variants — without duplicating logic.
	 *
	 * @param int   $menu_id WP nav menu term ID.
	 * @param array $opts    threshold, cols_pref, show_counts.
	 */
	public static function render_navigation_html( $menu_id, $opts = [] ) {
		$items = wp_get_nav_menu_items( $menu_id );
		if ( empty( $items ) ) return;

		// Strip WPML / Polylang language switcher items — Gold renders the
		// language pill in the topbar (and in the mobile drawer foot), so
		// having "Español" pop up as a fake top-level menu entry is double
		// signalling and looks like a category to the visitor.
		$items = array_values( array_filter( $items, function ( $it ) {
			$obj  = isset( $it->object ) ? (string) $it->object : '';
			$type = isset( $it->type ) ? (string) $it->type : '';
			$cls  = isset( $it->classes ) && is_array( $it->classes ) ? $it->classes : [];
			if ( in_array( $obj, [ 'wpml_ls_menu_item', 'pll_ls_menu_item', 'language_switcher' ], true ) ) return false;
			if ( $type === 'wpml_ls_menu_item' || $type === 'pll_ls_menu_item' ) return false;
			foreach ( $cls as $c ) {
				if ( $c === 'wpml-ls-item' || $c === 'wpml-ls-menu-item' || $c === 'pll-parent-menu-item' || $c === 'lang-item' ) return false;
			}
			return true;
		} ) );

		if ( empty( $items ) ) return;

		$tree        = self::build_tree( $items );
		$threshold   = max( 2, (int) ( $opts['threshold'] ?? 4 ) );
		$cols_pref   = $opts['cols_pref'] ?? 'auto';
		$show_counts = ! isset( $opts['show_counts'] ) || $opts['show_counts'] === true || $opts['show_counts'] === 'yes';

		echo '<nav class="lwp-mm" aria-label="' . esc_attr__( 'Primary', 'luwipress-gold' ) . '">';
		echo '<ul class="lwp-mm-top">';
		foreach ( $tree as $top ) {
			self::render_top_item( $top, $threshold, $cols_pref, $show_counts );
		}
		echo '</ul>';
		echo '</nav>';
	}

	public static function render_top_item( $node, $threshold, $cols_pref, $show_counts ) {
		$has_children = ! empty( $node['children'] );
		$is_mega = self::is_mega_candidate( $node, $threshold );

		$cls = 'lwp-mm-item';
		if ( $has_children ) $cls .= ' has-children';
		if ( $is_mega ) $cls .= ' is-mega';

		// Top-level count — direct term count first, fall back to summing
		// children when the top-level node points at a parent term (mega panel).
		$top_count = '';
		if ( $show_counts ) {
			$top_count = self::resolve_item_count( $node );
			if ( ( '' === $top_count || '0' === $top_count ) && $has_children ) {
				$sum = 0;
				foreach ( $node['children'] as $child ) {
					$cn = self::resolve_item_count( $child );
					if ( '' !== $cn ) $sum += (int) $cn;
				}
				$top_count = $sum > 0 ? (string) $sum : '';
			}
		}

		$count_html = '';
		if ( '' !== $top_count ) {
			$count_html = '<span class="lwp-mm-top-count">' . esc_html( $top_count ) . '</span>';
		}

		echo '<li class="' . esc_attr( $cls ) . '" data-item-id="' . esc_attr( $node['id'] ) . '">';
		printf(
			'<a href="%s"%s>%s%s%s</a>',
			esc_url( $node['url'] ),
			$has_children ? ' aria-haspopup="true" aria-expanded="false"' : '',
			esc_html( $node['label'] ),
			$count_html,
			$has_children ? '<span class="lwp-mm-arrow" aria-hidden="true">›</span>' : ''
		);

		if ( $has_children ) {
			if ( $is_mega ) {
				self::render_mega_panel( $node, $cols_pref, $show_counts );
			} else {
				self::render_simple_dropdown( $node['children'], $show_counts );
			}
		}
		echo '</li>';
	}

	public static function render_simple_dropdown( $children, $show_counts ) {
		echo '<ul class="lwp-mm-dropdown">';
		foreach ( $children as $c ) {
			$count = $show_counts ? self::resolve_item_count( $c ) : '';
			printf(
				'<li><a href="%s">%s%s</a></li>',
				esc_url( $c['url'] ),
				esc_html( $c['label'] ),
				$count !== '' ? '<span class="lwp-mm-count">' . esc_html( $count ) . '</span>' : ''
			);
		}
		echo '</ul>';
	}

	public static function render_mega_panel( $node, $cols_pref, $show_counts ) {
		$cols = self::pick_columns( $node['children'], $cols_pref );
		echo '<div class="lwp-mm-panel" role="menu">';
		echo '<div class="lwp-mm-panel-cols" style="grid-template-columns:repeat(' . count( $cols ) . ',1fr) auto">';

		foreach ( $cols as $col ) {
			echo '<div class="lwp-mm-col">';
			foreach ( $col as $entry ) {
				printf(
					'<h5 class="lwp-mm-col-head"><a href="%s">%s</a></h5>',
					esc_url( $entry['url'] ),
					esc_html( $entry['label'] )
				);
				if ( ! empty( $entry['children'] ) ) {
					echo '<ul>';
					foreach ( $entry['children'] as $c ) {
						$count = $show_counts ? self::resolve_item_count( $c ) : '';
						printf(
							'<li><a href="%s">%s%s</a></li>',
							esc_url( $c['url'] ),
							esc_html( $c['label'] ),
							$count !== '' ? '<span>' . esc_html( $count ) . '</span>' : ''
						);
					}
					echo '</ul>';
				}
			}
			echo '</div>';
		}

		// Featured slot — content set via menu item meta `_lwp_gold_mega_featured`.
		$featured_id = (int) get_post_meta( $node['id'], '_lwp_gold_mega_featured', true );
		if ( $featured_id ) {
			self::render_featured_slot( $featured_id );
		} else {
			// Auto-fallback: pick most-popular product matching the top-level URL slug.
			$auto_id = self::auto_featured_for_top( $node );
			if ( $auto_id ) {
				self::render_featured_slot( $auto_id );
			}
		}

		echo '</div>'; // panel-cols
		echo '</div>'; // panel
	}

	public static function render_featured_slot( $product_id ) {
		$post = get_post( $product_id );
		if ( ! $post ) return;
		$is_product = $post->post_type === 'product' && function_exists( 'wc_get_product' );
		$image      = get_the_post_thumbnail_url( $product_id, 'medium' );
		$image_style= $image ? 'background-image:url(' . esc_url( $image ) . ');background-size:cover;background-position:center' : '';
		$eyebrow    = $is_product ? __( 'Featured', 'luwipress-gold' ) : __( 'Read', 'luwipress-gold' );
		?>
		<a class="lwp-mm-feat" href="<?php echo esc_url( get_permalink( $product_id ) ); ?>">
			<div class="lwp-mm-feat-img" style="<?php echo esc_attr( $image_style ); ?>">
				<span><?php echo esc_html( $eyebrow ); ?></span>
			</div>
			<div class="lwp-mm-feat-meta">
				<span class="lwp-mm-feat-title"><?php echo esc_html( get_the_title( $product_id ) ); ?></span>
				<span class="lwp-mm-feat-link"><?php esc_html_e( 'Open →', 'luwipress-gold' ); ?></span>
			</div>
		</a>
		<?php
	}

	private function placeholder( $msg ) {
		echo '<div class="lwp-mm lwp-mm--placeholder">' . esc_html( $msg ) . '</div>';
	}

	/* ----------------------------------------------------------------
	 * Static helpers — also used by the mega menu compiler placeholder.
	 * -------------------------------------------------------------- */

	/**
	 * Build a tree from a flat menu_items array (parent_id-keyed).
	 *
	 * @param array $items Result of wp_get_nav_menu_items().
	 * @return array Top-level nodes, each with `children` recursively.
	 */
	public static function build_tree( $items ) {
		$by_id = [];
		foreach ( $items as $i ) {
			$by_id[ (int) $i->ID ] = [
				'id'       => (int) $i->ID,
				'parent'   => (int) $i->menu_item_parent,
				'order'    => (int) $i->menu_order,
				'label'    => wp_strip_all_tags( $i->title ),
				'url'      => $i->url,
				'object'   => $i->object,        // 'category', 'page', 'post', 'product_cat', etc.
				'object_id'=> (int) $i->object_id,
				'children' => [],
			];
		}
		// Order by menu_order so children stay in operator-set sequence.
		uasort( $by_id, function ( $a, $b ) { return $a['order'] <=> $b['order']; } );

		$roots = [];
		foreach ( $by_id as $id => &$node ) {
			$pid = $node['parent'];
			if ( $pid && isset( $by_id[ $pid ] ) ) {
				$by_id[ $pid ]['children'][] = &$node;
			} else {
				$roots[] = &$node;
			}
		}
		unset( $node );
		return $roots;
	}

	/**
	 * @return bool true if this top-level item should render as a mega panel.
	 */
	public static function is_mega_candidate( $node, $threshold ) {
		$kids = $node['children'] ?? [];
		if ( count( $kids ) >= $threshold ) return true;
		foreach ( $kids as $c ) {
			if ( ! empty( $c['children'] ) ) return true;
		}
		return false;
	}

	/**
	 * Distribute children across N columns, keeping each "deep" branch
	 * (a child that itself has children) intact within one column.
	 *
	 * @param array  $children
	 * @param string $cols_pref 'auto' | '2' | '3' | '4'
	 * @return array  array of columns; each column is array of nodes
	 */
	public static function pick_columns( $children, $cols_pref = 'auto' ) {
		$count = count( $children );
		if ( $cols_pref !== 'auto' ) {
			$cols = max( 1, (int) $cols_pref );
		} else {
			$cols = ( $count >= 9 ) ? 3 : ( $count >= 4 ? 2 : 1 );
		}
		$buckets = array_fill( 0, $cols, [] );
		// Round-robin into columns by item count, but try to balance "deep"
		// branches: each branch with children counts as more weight.
		$weights = array_fill( 0, $cols, 0 );
		foreach ( $children as $c ) {
			$w = 1 + ( count( $c['children'] ?? [] ) * 0.5 );
			$lightest = array_keys( $weights, min( $weights ) )[0];
			$buckets[ $lightest ][] = $c;
			$weights[ $lightest ] += $w;
		}
		return $buckets;
	}

	/**
	 * Resolve the count to show next to a menu link — works for product
	 * categories, regular categories, and pages-listing-sub-pages.
	 */
	public static function resolve_item_count( $node ) {
		// Term-typed menu items — fast path.
		if ( ! empty( $node['object'] ) && ! empty( $node['object_id'] ) ) {
			switch ( $node['object'] ) {
				case 'product_cat':
				case 'category':
				case 'post_tag':
					$term = get_term( (int) $node['object_id'] );
					if ( $term && ! is_wp_error( $term ) ) {
						return (string) $term->count;
					}
					break;
				case 'page':
					$kids = get_pages( [ 'parent' => (int) $node['object_id'], 'number' => 0 ] );
					if ( $kids ) return (string) count( $kids );
					break;
			}
		}
		// Fallback: many operators add custom-URL menu items pointing at
		// product category permalinks (e.g. "/arabic-oud/"). Look the slug
		// up against `product_cat` and return its count if found.
		if ( ! empty( $node['url'] ) && taxonomy_exists( 'product_cat' ) ) {
			$path = wp_parse_url( $node['url'], PHP_URL_PATH );
			if ( $path ) {
				$path = trim( $path, '/' );
				// Permalinks may include the WC base ("product-category/").
				$segments = array_values( array_filter( explode( '/', $path ) ) );
				$slug     = end( $segments );
				if ( $slug ) {
					$term = get_term_by( 'slug', $slug, 'product_cat' );
					if ( $term && ! is_wp_error( $term ) ) {
						return (string) $term->count;
					}
				}
			}
		}
		return '';
	}

	/**
	 * If no manual featured product was set on this top-level menu item,
	 * try to derive one — best-selling product whose category matches
	 * the menu item's URL or any descendant category. Tapadum-style menus
	 * use parent categories like "string-instruments" that often have zero
	 * direct products — all the products live in sub-categories. Walking
	 * the descendant tree ensures the featured slot fills.
	 */
	private static function auto_featured_for_top( $node ) {
		if ( ! function_exists( 'wc_get_product' ) ) return 0;
		if ( ! taxonomy_exists( 'product_cat' ) ) return 0;

		$term_id = 0;
		if ( in_array( $node['object'] ?? '', [ 'product_cat' ], true ) && ! empty( $node['object_id'] ) ) {
			$term_id = (int) $node['object_id'];
		} elseif ( ! empty( $node['url'] ) ) {
			// Custom-URL menu items: resolve from URL slug.
			$path = wp_parse_url( $node['url'], PHP_URL_PATH );
			if ( $path ) {
				$segments = array_values( array_filter( explode( '/', trim( $path, '/' ) ) ) );
				$slug     = end( $segments );
				if ( $slug ) {
					$term = get_term_by( 'slug', $slug, 'product_cat' );
					if ( $term && ! is_wp_error( $term ) ) {
						$term_id = (int) $term->term_id;
					}
				}
			}
		}
		if ( ! $term_id ) return 0;

		// Pull the term + every descendant — best-selling within that set.
		$descendants = get_term_children( $term_id, 'product_cat' );
		$term_ids    = array_merge( [ $term_id ], is_array( $descendants ) ? $descendants : [] );

		$query = new WP_Query( [
			'post_type'      => 'product',
			'posts_per_page' => 1,
			'post_status'    => 'publish',
			'tax_query'      => [
				[
					'taxonomy'         => 'product_cat',
					'field'            => 'term_id',
					'terms'            => $term_ids,
					'include_children' => false,
				],
			],
			'meta_key'       => 'total_sales',
			'orderby'        => [ 'meta_value_num' => 'DESC', 'date' => 'DESC' ],
			'no_found_rows'  => true,
		] );
		$id = 0;
		if ( $query->have_posts() ) {
			$id = (int) $query->posts[0]->ID;
		}
		wp_reset_postdata();
		return $id;
	}
}
