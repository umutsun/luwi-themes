<?php
/**
 * Widget: LuwiPress Onyx Mega Menu.
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
 * (custom field `_lwp_onyx_mega_featured` saved per top item).
 *
 * No mouse-out timer: pure CSS hover via `<details>` polyfill on mobile,
 * and `:hover` + a 150ms cancel timeout on desktop (handled in app JS).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class LuwiPress_Onyx_Widget_Mega_Menu extends Widget_Base {

	public function get_name()        { return 'lwp-mega-menu'; }
	public function get_title()       { return __( 'Mega Menu', 'luwipress-onyx' ); }
	public function get_icon()        { return 'eicon-nav-menu'; }
	public function get_categories()  { return [ 'luwipress-onyx' ]; }
	public function get_keywords()    { return [ 'menu', 'mega', 'nav', 'navigation', 'header', 'dropdown' ]; }
	public function get_style_depends() { return [ 'luwipress-onyx-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_source', [ 'label' => __( 'Source', 'luwipress-onyx' ) ] );

		$this->add_control(
			'menu_id',
			[
				'label'       => __( 'Menu', 'luwipress-onyx' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => $this->get_menu_options(),
				'description' => __( 'Build the menu in Appearance → Menus, then pick it here. The widget auto-detects which top-level items deserve a mega panel.', 'luwipress-onyx' ),
			]
		);

		$this->add_control(
			'mega_threshold',
			[
				'label'       => __( 'Mega panel threshold', 'luwipress-onyx' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 4,
				'min'         => 2,
				'max'         => 12,
				'description' => __( 'A top-level item becomes a mega panel when it has at least this many children, or when any child has its own children.', 'luwipress-onyx' ),
			]
		);

		$this->add_control(
			'mega_columns',
			[
				'label'   => __( 'Mega panel columns', 'luwipress-onyx' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => [
					'auto' => __( 'Auto (2 or 3 by item count)', 'luwipress-onyx' ),
					'2'    => __( '2 columns', 'luwipress-onyx' ),
					'3'    => __( '3 columns', 'luwipress-onyx' ),
					'4'    => __( '4 columns', 'luwipress-onyx' ),
				],
			]
		);

		$this->add_control(
			'show_counts',
			[
				'label'   => __( 'Show item counts', 'luwipress-onyx' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
				'description' => __( 'When a menu item points at a product category, show that category\'s product count next to the link.', 'luwipress-onyx' ),
			]
		);

		$this->add_control(
			'mobile_collapse',
			[
				'label'   => __( 'Mobile mode', 'luwipress-onyx' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'drawer',
				'options' => [
					'drawer'    => __( 'Off-canvas drawer (hamburger)', 'luwipress-onyx' ),
					'accordion' => __( 'In-place accordion', 'luwipress-onyx' ),
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-onyx' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control(
			'top_color',
			[
				'label'     => __( 'Top item color', 'luwipress-onyx' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1b1c1c',
				'selectors' => [ '{{WRAPPER}} .lwp-mm > ul > li > a' => 'color: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'top_hover_color',
			[
				'label'     => __( 'Top item hover color', 'luwipress-onyx' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#735c00',
				'selectors' => [ '{{WRAPPER}} .lwp-mm > ul > li > a:hover' => 'color: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'panel_bg',
			[
				'label'     => __( 'Panel background', 'luwipress-onyx' ),
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
		$out = [ '' => __( '— Pick a menu —', 'luwipress-onyx' ) ];
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

		// Fall back to Customizer (Görünüm → Customize → LuwiPress Onyx →
		// Mega menu) when the widget instance has no explicit selection.
		// Lets the operator manage the global mega-menu config in one place
		// without re-opening every header template.
		$menu_id = (int) ( $s['menu_id'] ?? 0 );
		if ( ! $menu_id ) {
			$menu_id = (int) get_theme_mod( 'luwipress_onyx_mega_menu_id', 0 );
		}
		if ( ! $menu_id ) {
			$this->placeholder( __( 'Pick a menu in the panel → (or set the global default in Customize → LuwiPress Onyx → Mega menu)', 'luwipress-onyx' ) );
			return;
		}

		$items = wp_get_nav_menu_items( $menu_id );
		if ( empty( $items ) ) {
			$this->placeholder( __( 'This menu has no items yet.', 'luwipress-onyx' ) );
			return;
		}

		$threshold = isset( $s['mega_threshold'] ) && (int) $s['mega_threshold'] > 0
			? max( 2, (int) $s['mega_threshold'] )
			: max( 2, (int) get_theme_mod( 'luwipress_onyx_mega_threshold', 4 ) );

		$cols_pref = ! empty( $s['mega_columns'] )
			? $s['mega_columns']
			: (string) get_theme_mod( 'luwipress_onyx_mega_columns', 'auto' );

		$show_counts = isset( $s['show_counts'] )
			? ( $s['show_counts'] === 'yes' )
			: (bool) get_theme_mod( 'luwipress_onyx_mega_show_counts', true );

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

		// Auto-inject post-category children into a top-level "Blog" /
		// "Journal" / "News" menu item that the operator left flat. Lets
		// the mega menu surface blog categories without forcing the
		// operator to maintain a parallel hierarchy in Appearance →
		// Menus. Skipped when the operator already added children — we
		// respect manual configuration. Operator can disable globally
		// from Customize → LuwiPress Onyx → Mega menu.
		if ( (bool) get_theme_mod( 'luwipress_onyx_mega_auto_inject_blog', true ) ) {
			foreach ( $tree as &$top_node ) {
				if ( empty( $top_node['children'] ) ) {
					$injected = self::maybe_inject_blog_categories( $top_node );
					if ( ! empty( $injected ) ) {
						$top_node['children'] = $injected;
					}
				}
			}
			unset( $top_node );
		}

		echo '<nav class="lwp-mm" aria-label="' . esc_attr__( 'Primary', 'luwipress-onyx' ) . '">';
		echo '<ul class="lwp-mm-top">';
		foreach ( $tree as $top ) {
			self::render_top_item( $top, $threshold, $cols_pref, $show_counts );
		}
		echo '</ul>';
		echo '</nav>';
	}

	/**
	 * If a top-level menu item points at the site's blog (Posts page) or
	 * a slug in the blog-name allowlist, return synthetic children
	 * representing every non-empty `category` taxonomy term — excluding
	 * the default "Uncategorized" term. Returns empty array when the
	 * node isn't a blog hub or no useful categories exist.
	 *
	 * Children use synthetic negative IDs (-1, -2…) so they never clash
	 * with real menu_item IDs in any downstream lookup, and `object` is
	 * set to 'category' so resolve_item_count() can read the count.
	 *
	 * @param array $node Top-level tree node.
	 * @return array
	 */
	public static function maybe_inject_blog_categories( $node ) {
		if ( ! taxonomy_exists( 'category' ) ) {
			return [];
		}

		$is_blog_hub = false;

		// 1) Direct match: URL equals the WP "Posts page" permalink.
		$blog_page_id = (int) get_option( 'page_for_posts' );
		if ( $blog_page_id > 0 ) {
			$blog_url = get_permalink( $blog_page_id );
			if ( $blog_url && ! empty( $node['url'] ) ) {
				$node_url_norm = untrailingslashit( wp_parse_url( $node['url'], PHP_URL_PATH ) ?: '' );
				$blog_url_norm = untrailingslashit( wp_parse_url( $blog_url, PHP_URL_PATH ) ?: '' );
				if ( $node_url_norm !== '' && $node_url_norm === $blog_url_norm ) {
					$is_blog_hub = true;
				}
			}
		}

		// 2) Slug match against the blog-name allowlist.
		if ( ! $is_blog_hub && ! empty( $node['url'] ) ) {
			$path     = (string) wp_parse_url( $node['url'], PHP_URL_PATH );
			$segments = array_values( array_filter( explode( '/', trim( $path, '/' ) ) ) );
			$last     = end( $segments );
			if ( $last !== false && $last !== '' ) {
				$slugs = function_exists( 'luwipress_onyx_get_blog_page_slugs' )
					? luwipress_onyx_get_blog_page_slugs()
					: [ 'blog', 'journal', 'news', 'magazine' ];
				$slugs = array_map( 'strtolower', $slugs );
				if ( in_array( strtolower( $last ), $slugs, true ) ) {
					$is_blog_hub = true;
				}
			}
		}

		if ( ! $is_blog_hub ) {
			return [];
		}

		// Discover categories — exclude the WP default "Uncategorized".
		$default_cat = (int) get_option( 'default_category' );
		$cats = get_terms( [
			'taxonomy'   => 'category',
			'hide_empty' => true,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'exclude'    => $default_cat > 0 ? [ $default_cat ] : [],
			'number'     => 12,
		] );

		// Operators / sister plugins may extend the exclude list (for
		// taxonomies localised by WPML / Polylang there can be multiple
		// "Uncategorized" terms — slug `uncategorized-en`, `senza-categoria`…).
		$cats = (array) apply_filters( 'luwipress_onyx_blog_menu_categories', $cats, $node );

		if ( empty( $cats ) || is_wp_error( $cats ) ) {
			return [];
		}

		$children = [];
		$i = 0;
		foreach ( $cats as $cat ) {
			if ( ! ( $cat instanceof WP_Term ) ) {
				continue;
			}
			$slug = strtolower( (string) $cat->slug );
			// Belt-and-braces filter for localised "Uncategorized" variants.
			if ( strpos( $slug, 'uncategorized' ) === 0
				|| strpos( $slug, 'senza-categoria' ) === 0
				|| strpos( $slug, 'sin-categoria' ) === 0
				|| strpos( $slug, 'non-classe' ) === 0 ) {
				continue;
			}
			$i++;
			$children[] = [
				'id'        => -1 * $i, // synthetic, won't collide with real menu_item IDs
				'parent'    => (int) $node['id'],
				'order'     => $i,
				'label'     => $cat->name,
				'url'       => get_term_link( $cat ),
				'object'    => 'category',
				'object_id' => (int) $cat->term_id,
				'children'  => [],
			];
		}
		return $children;
	}

	public static function render_top_item( $node, $threshold, $cols_pref, $show_counts ) {
		$has_children = ! empty( $node['children'] );
		$is_mega = self::is_mega_candidate( $node, $threshold );

		$cls = 'lwp-mm-item';
		if ( $has_children ) $cls .= ' has-children';
		if ( $is_mega ) $cls .= ' is-mega';

		// Resolve the top-level node to a product_cat term when its URL or
		// object type matches one — lets us rewrite the href to the live
		// archive (instead of any legacy page sitting at the same slug) AND
		// surface the term's product count inside the mega panel header.
		$top_term  = self::resolve_to_product_cat( $node );
		$top_url   = self::resolve_target_url( $node );

		$top_count = '';
		if ( $show_counts ) {
			if ( $top_term ) {
				$top_count = (string) $top_term->count;
			} elseif ( $has_children ) {
				$sum = 0;
				foreach ( $node['children'] as $child ) {
					$cn = self::resolve_item_count( $child );
					if ( '' !== $cn ) $sum += (int) $cn;
				}
				$top_count = $sum > 0 ? (string) $sum : '';
			}
		}

		echo '<li class="' . esc_attr( $cls ) . '" data-item-id="' . esc_attr( $node['id'] ) . '">';
		printf(
			'<a href="%s"%s>%s%s</a>',
			esc_url( $top_url ),
			$has_children ? ' aria-haspopup="true" aria-expanded="false"' : '',
			esc_html( $node['label'] ),
			$has_children ? '<span class="lwp-mm-arrow" aria-hidden="true">›</span>' : ''
		);

		if ( $has_children ) {
			if ( $is_mega ) {
				self::render_mega_panel( $node, $cols_pref, $show_counts, [
					'top_url'   => $top_url,
					'top_label' => $node['label'],
					'top_count' => $top_count,
				] );
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
				esc_url( self::resolve_target_url( $c ) ),
				esc_html( $c['label'] ),
				$count !== '' ? '<span class="lwp-mm-count">' . esc_html( $count ) . '</span>' : ''
			);
		}
		echo '</ul>';
	}

	public static function render_mega_panel( $node, $cols_pref, $show_counts, $head_meta = [] ) {
		$cols = self::pick_columns( $node['children'], $cols_pref );
		echo '<div class="lwp-mm-panel" role="menu">';

		// Panel header — moves the parent-category count out of the top-level
		// menu link (where it crowded the typography) into the dropdown
		// itself. Only renders when we have a count or a "view all" link.
		$top_url   = (string) ( $head_meta['top_url']   ?? ( $node['url'] ?? '' ) );
		$top_label = (string) ( $head_meta['top_label'] ?? ( $node['label'] ?? '' ) );
		$top_count = (string) ( $head_meta['top_count'] ?? '' );
		if ( $top_url !== '' && ( $top_count !== '' || $top_label !== '' ) ) {
			echo '<div class="lwp-mm-panel-head">';
			if ( $top_count !== '' ) {
				printf(
					'<span class="lwp-mm-panel-head-count" aria-label="%s">%s</span>',
					esc_attr( sprintf( /* translators: %s: count */ _n( '%s item in this collection', '%s items in this collection', (int) $top_count, 'luwipress-onyx' ), $top_count ) ),
					esc_html( $top_count )
				);
			}
			printf(
				'<a class="lwp-mm-panel-head-link" href="%s"><span class="lwp-mm-panel-head-title">%s</span><em>%s</em></a>',
				esc_url( $top_url ),
				esc_html( $top_label ),
				esc_html__( 'View all →', 'luwipress-onyx' )
			);
			echo '</div>';
		}

		echo '<div class="lwp-mm-panel-cols" style="grid-template-columns:repeat(' . count( $cols ) . ',1fr) auto">';

		foreach ( $cols as $col ) {
			echo '<div class="lwp-mm-col">';
			foreach ( $col as $entry ) {
				$entry_url   = self::resolve_target_url( $entry );
				$entry_count = $show_counts ? self::resolve_item_count( $entry ) : '';
				printf(
					'<h5 class="lwp-mm-col-head"><a href="%s">%s%s</a></h5>',
					esc_url( $entry_url ),
					esc_html( $entry['label'] ),
					$entry_count !== '' ? '<span class="lwp-mm-col-head-count">' . esc_html( $entry_count ) . '</span>' : ''
				);
				/* Third-level (grand-children) suppressed by design — every column
				 * renders the same: header + count pill only. Operator-set sub-menus
				 * on items like Oud would otherwise stand out as the only column
				 * with a `<ul>`, breaking visual rhythm across the mega panel. */
			}
			echo '</div>';
		}

		// Featured slot — resolution chain (first hit wins):
		//   1. Per-menu-item manual override (`_lwp_onyx_mega_featured`)
		//   2. Central Featured Registry — products flipped via product
		//      meta box / admin bar. Prefer ones whose product_cat
		//      overlaps this menu item's category tree; fall back to
		//      the global most-recently-featured product.
		//   3. Auto: most-popular product matching the top-level slug.
		$featured_id = (int) get_post_meta( $node['id'], '_lwp_onyx_mega_featured', true );
		if ( ! $featured_id ) {
			$featured_id = self::registry_featured_for_top( $node );
		}
		if ( ! $featured_id ) {
			$featured_id = self::auto_featured_for_top( $node );
		}
		if ( $featured_id ) {
			self::render_featured_slot( $featured_id );
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
		$eyebrow    = $is_product ? __( 'Featured', 'luwipress-onyx' ) : __( 'Read', 'luwipress-onyx' );
		?>
		<a class="lwp-mm-feat" href="<?php echo esc_url( get_permalink( $product_id ) ); ?>">
			<div class="lwp-mm-feat-img" style="<?php echo esc_attr( $image_style ); ?>">
				<span><?php echo esc_html( $eyebrow ); ?></span>
			</div>
			<div class="lwp-mm-feat-meta">
				<span class="lwp-mm-feat-title"><?php echo esc_html( get_the_title( $product_id ) ); ?></span>
				<span class="lwp-mm-feat-link"><?php esc_html_e( 'Open →', 'luwipress-onyx' ); ?></span>
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
	 *
	 * Backed by the same product_cat resolver used to rewrite menu URLs,
	 * so any item that resolves to a category for navigation also picks
	 * up its count for free. Items that don't resolve return '' (we never
	 * render "0" — empty pills look broken).
	 */
	/**
	 * Bridge to the slug-collision map.
	 *
	 * LuwiPress core 3.1.56+ ships the canonical `LuwiPress_Slug_Resolver`
	 * with the same six-pass discovery this theme used to do in
	 * `inc/template-redirects.php`. When that class is loaded the theme's
	 * legacy function (`luwipress_onyx_get_hub_redirects`) early-returns
	 * to avoid double registration — leaving previously theme-coupled
	 * call-sites here without a source of truth.
	 *
	 * This helper centralises the lookup: prefer the core resolver when
	 * present, fall through to the legacy theme function for any install
	 * still on core ≤ 3.1.55. Per-request memoised so a 50-item menu
	 * walks the resolver exactly once.
	 *
	 * @return array<string,int|bool|string> slug => term_id | true | URL
	 */
	private static function get_slug_resolver_map() {
		static $cache = null;
		if ( $cache !== null ) {
			return $cache;
		}
		if ( class_exists( '\\LuwiPress_Slug_Resolver' ) ) {
			$resolver = \LuwiPress_Slug_Resolver::get_instance();
			$cache = $resolver->get_redirects();
		} elseif ( function_exists( 'luwipress_onyx_get_hub_redirects' ) ) {
			$cache = (array) luwipress_onyx_get_hub_redirects();
		} else {
			$cache = array();
		}
		return $cache;
	}

	public static function resolve_item_count( $node ) {
		// Product taxonomy resolution — covers object='product_cat', exact
		// URL slug, and plural variants (darbuka → darbukas).
		$term = self::resolve_to_product_cat( $node );
		if ( $term ) {
			return (string) $term->count;
		}

		// Native post category / tag — direct term-id path.
		if ( ! empty( $node['object'] ) && ! empty( $node['object_id'] ) ) {
			switch ( $node['object'] ) {
				case 'category':
				case 'post_tag':
					$t = get_term( (int) $node['object_id'] );
					if ( $t instanceof \WP_Term && $t->count > 0 ) {
						return (string) $t->count;
					}
					break;
				case 'page':
					$kids = get_pages( [ 'parent' => (int) $node['object_id'], 'number' => 0 ] );
					if ( $kids ) return (string) count( $kids );
					break;
			}
		}
		return '';
	}

	/**
	 * Resolve a menu node to a non-empty `product_cat` term when one
	 * matches. Used by both the URL-rewriter (so `/string-instruments/`
	 * page-slug menu items go to `/product-category/string-instruments/`
	 * instead of the legacy page) and `resolve_item_count` (so plural-form
	 * menu items still get a count badge).
	 *
	 * Resolution order:
	 *   1. Direct: `object === 'product_cat'` with non-empty term.
	 *   2. URL last-segment slug match.
	 *   3. Plural variants: slug + 's', slug + 'es'.
	 *
	 * Returns null on failure — callers must NOT render a fallback URL or
	 * "0" badge from a null. Per-request memoised so a 50-item menu only
	 * does one lookup per unique slug.
	 *
	 * @param array $node Menu tree node.
	 * @return \WP_Term|null
	 */
	public static function resolve_to_product_cat( $node ) {
		static $cache = [];
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return null;
		}

		// Direct fast path — operator picked the category in Appearance → Menus.
		if ( ! empty( $node['object'] ) && $node['object'] === 'product_cat' && ! empty( $node['object_id'] ) ) {
			$cache_key = 'id:' . (int) $node['object_id'];
			if ( array_key_exists( $cache_key, $cache ) ) {
				return $cache[ $cache_key ];
			}
			$t = get_term( (int) $node['object_id'] );
			$resolved = ( $t instanceof \WP_Term && $t->count > 0 ) ? $t : null;
			$cache[ $cache_key ] = $resolved;
			if ( $resolved ) return $resolved;
		}

		if ( empty( $node['url'] ) ) {
			return null;
		}

		$path = (string) wp_parse_url( $node['url'], PHP_URL_PATH );
		$segments = array_values( array_filter( explode( '/', trim( $path, '/' ) ) ) );
		$slug = is_array( $segments ) && ! empty( $segments ) ? (string) end( $segments ) : '';
		if ( $slug === '' ) {
			return null;
		}

		$cache_key = 'slug:' . $slug;
		if ( array_key_exists( $cache_key, $cache ) ) {
			return $cache[ $cache_key ];
		}

		// Exact slug, then plural variants. Stop on the first non-empty term.
		foreach ( [ $slug, $slug . 's', $slug . 'es' ] as $candidate ) {
			$t = get_term_by( 'slug', $candidate, 'product_cat' );
			if ( $t instanceof \WP_Term && $t->count > 0 ) {
				$cache[ $cache_key ] = $t;
				return $t;
			}
		}

		// WPML / Polylang cross-language fallback. The slug-conflict map
		// (built by LuwiPress_Slug_Resolver in core 3.1.56+, or by the
		// legacy theme function on older installs) records every page
		// slug — including translated ones (`percusiones` ES, `vents` FR)
		// AND empty-term ancestor fallbacks (`duduk` → `winds`) —
		// against the canonical product_cat term. Plural-fuzzy match
		// can't catch these because no in-language term has the
		// translated slug.
		$redirects = self::get_slug_resolver_map();
		if ( ! empty( $redirects ) && isset( $redirects[ $slug ] ) ) {
			$rule = $redirects[ $slug ];
			if ( is_int( $rule ) || ( is_string( $rule ) && ctype_digit( $rule ) ) ) {
				$t = get_term( (int) $rule );
				if ( $t instanceof \WP_Term ) {
					$cache[ $cache_key ] = $t;
					return $t;
				}
			}
		}

		$cache[ $cache_key ] = null;
		return null;
	}

	/**
	 * Pick the URL the visitor should actually land on when clicking a
	 * menu entry. When the entry resolves to a non-empty product_cat
	 * term we use the term's permalink — bypasses any legacy page that
	 * happens to share the same slug, removing one hop from the journey
	 * and keeping the visitor inside the live commerce flow.
	 *
	 * Resolution order:
	 *   1. Direct + plural slug match (in-language fast path).
	 *   2. WPML / Polylang cross-language slug-conflict map. Catches
	 *      menus where the page slug is translated (`/es/percusiones/`,
	 *      `/fr/vents/`) but the term slug stayed in English. Without
	 *      this fallback the rewrite would silently no-op on multi-
	 *      lingual stores. The map is built once per request by
	 *      `template-redirects.php` and cached in a transient.
	 *
	 * @param array $node Menu tree node.
	 * @return string Always returns a non-empty URL (falls back to the
	 *                operator-set URL when no term matches).
	 */
	public static function resolve_target_url( $node ) {
		$term = self::resolve_to_product_cat( $node );
		if ( $term ) {
			$link = get_term_link( $term );
			if ( ! is_wp_error( $link ) && $link ) {
				return (string) $link;
			}
		}

		// Cross-language fallback via the slug-conflict map.
		if ( ! empty( $node['url'] ) ) {
			$slug = self::extract_last_slug( (string) $node['url'] );
			if ( $slug !== '' ) {
				$redirects = self::get_slug_resolver_map();
				if ( ! empty( $redirects ) && isset( $redirects[ $slug ] ) ) {
					$rule = $redirects[ $slug ];
					if ( is_int( $rule ) || ( is_string( $rule ) && ctype_digit( $rule ) ) ) {
						$tlink = get_term_link( (int) $rule );
						if ( ! is_wp_error( $tlink ) && $tlink ) {
							return (string) $tlink;
						}
					} elseif ( is_string( $rule ) && strpos( $rule, 'http' ) === 0 ) {
						return $rule;
					}
				}
			}
		}

		return (string) ( $node['url'] ?? '' );
	}

	/**
	 * Pull the last URL path segment (the slug). WPML/Polylang language
	 * directory prefixes (`/it/`, `/fr/`) are absorbed by `end()` since
	 * the slug we want is always the trailing segment, not the prefix.
	 *
	 * @param string $url
	 * @return string Last slug, or '' when the URL has no path.
	 */
	private static function extract_last_slug( $url ) {
		if ( $url === '' ) return '';
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		$segments = array_values( array_filter( explode( '/', trim( $path, '/' ) ) ) );
		if ( empty( $segments ) ) return '';
		return (string) end( $segments );
	}

	/**
	 * Layer 2 of the featured-slot fallback chain: pick a product from
	 * the Featured Products registry, preferring one whose product_cat
	 * overlaps this menu item's category tree. Lets operators flip a
	 * "Featured" flag on any product and have it bubble up into the
	 * relevant mega panel automatically.
	 *
	 * Returns 0 when registry helpers are missing or when no featured
	 * product matches.
	 */
	private static function registry_featured_for_top( $node ) {
		if ( ! function_exists( 'lwp_onyx_get_featured_ids' ) ) {
			return 0;
		}
		$ids = lwp_onyx_get_featured_ids( [ 'number' => 30 ] );
		if ( empty( $ids ) ) {
			return 0;
		}

		$term_id = 0;
		if ( in_array( $node['object'] ?? '', [ 'product_cat' ], true ) && ! empty( $node['object_id'] ) ) {
			$term_id = (int) $node['object_id'];
		} elseif ( ! empty( $node['url'] ) ) {
			$path = wp_parse_url( $node['url'], PHP_URL_PATH );
			if ( $path ) {
				$segments = array_values( array_filter( explode( '/', trim( $path, '/' ) ) ) );
				$slug     = end( $segments );
				if ( $slug && taxonomy_exists( 'product_cat' ) ) {
					$t = get_term_by( 'slug', $slug, 'product_cat' );
					if ( $t && ! is_wp_error( $t ) ) {
						$term_id = (int) $t->term_id;
					}
				}
			}
		}

		if ( $term_id && taxonomy_exists( 'product_cat' ) ) {
			$descendants = get_term_children( $term_id, 'product_cat' );
			$tree_ids    = is_wp_error( $descendants ) ? [ $term_id ] : array_merge( [ $term_id ], (array) $descendants );

			foreach ( $ids as $pid ) {
				$prod_terms = wp_get_post_terms( (int) $pid, 'product_cat', [ 'fields' => 'ids' ] );
				if ( is_wp_error( $prod_terms ) || empty( $prod_terms ) ) { continue; }
				if ( array_intersect( $prod_terms, $tree_ids ) ) {
					return (int) $pid;
				}
			}
		}

		// No category-matched featured product — but if the menu item has
		// no category context (e.g. "Sale" custom link), fall back to the
		// globally most-recently-featured product.
		if ( ! $term_id && ! empty( $ids ) ) {
			return (int) $ids[0];
		}
		return 0;
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
