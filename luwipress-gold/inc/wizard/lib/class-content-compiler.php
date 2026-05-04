<?php
/**
 * Content Compiler — turns Elementor JSON template stubs into fully-populated
 * pages by walking the JSON tree and replacing `{{LWP:tag[:arg]}}` placeholders
 * with values harvested from the live site (products, posts, taxonomies, KG).
 *
 * Goals
 * -----
 * 1. The operator should not see any "lorem" or template-defaults.
 * 2. The bundled JSON kit ships with **two kinds of placeholders**:
 *    - Inline scalars:  {{LWP:product_count}}, {{LWP:site_name}}, etc.
 *    - Loop blocks:     {{LWP:featured_loop:8|<li>{{LWP:product.name}} {{LWP:product.price}}</li>}}
 * 3. Markers that fail to resolve fall back to the original literal in the
 *    template (so a fresh install with no products still ships pretty copy).
 * 4. Compiler is fully self-contained — it only needs the snapshot from
 *    Detector and the raw JSON; all WP/WC calls are read-only.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LuwiPress_Gold_Content_Compiler {

	/**
	 * @var array Snapshot from LuwiPress_Gold_Detector::snapshot().
	 */
	private $snap;

	/**
	 * @var array Pre-resolved scalar map. Built lazily on first compile.
	 */
	private $scalars;

	public function __construct( array $snapshot ) {
		$this->snap = $snapshot;
	}

	/**
	 * Compile a JSON-decoded Elementor template.
	 *
	 * Walks `content` (an array of sections) recursively, and for every
	 * widget setting it sees, replaces placeholders.
	 *
	 * @param array $template Decoded JSON (as produced by json_decode($string,true)).
	 * @return array Compiled template — same shape, placeholders resolved.
	 */
	public function compile( array $template ) {
		if ( ! isset( $template['content'] ) || ! is_array( $template['content'] ) ) {
			return $template;
		}
		$template['content'] = $this->walk( $template['content'] );
		return $template;
	}

	/**
	 * Walk every node, looking for widget settings to compile.
	 */
	private function walk( $nodes ) {
		if ( ! is_array( $nodes ) ) return $nodes;
		foreach ( $nodes as $key => $node ) {
			if ( ! is_array( $node ) ) continue;
			if ( isset( $node['settings'] ) && is_array( $node['settings'] ) ) {
				$node['settings'] = $this->compile_settings( $node['settings'] );
			}
			if ( isset( $node['elements'] ) && is_array( $node['elements'] ) ) {
				$node['elements'] = $this->walk( $node['elements'] );
			}
			$nodes[ $key ] = $node;
		}
		return $nodes;
	}

	/**
	 * Compile a single widget's settings array — only replaces strings that
	 * actually contain `{{LWP:` so we don't waste time on numerical settings.
	 */
	private function compile_settings( array $settings ) {
		foreach ( $settings as $key => $value ) {
			if ( is_string( $value ) && strpos( $value, '{{LWP:' ) !== false ) {
				$settings[ $key ] = $this->resolve_string( $value );
			} elseif ( is_array( $value ) ) {
				// Repeater rows (e.g. icon-list items) — recurse one more level.
				$settings[ $key ] = $this->compile_settings_deep( $value );
			}
		}
		return $settings;
	}

	private function compile_settings_deep( array $arr ) {
		foreach ( $arr as $k => $v ) {
			if ( is_string( $v ) && strpos( $v, '{{LWP:' ) !== false ) {
				$arr[ $k ] = $this->resolve_string( $v );
			} elseif ( is_array( $v ) ) {
				$arr[ $k ] = $this->compile_settings_deep( $v );
			}
		}
		return $arr;
	}

	/**
	 * Replace every `{{LWP:tag[:arg][|template]}}` in the input string.
	 *
	 * Two forms supported:
	 *   - Scalar:  {{LWP:product_count}}  →  "128"
	 *   - Loop:    {{LWP:featured_loop:8|<article>{{LWP:product.name}} – {{LWP:product.price}}</article>}}
	 *              → 8 articles concatenated, each with the inner template applied
	 */
	private function resolve_string( $str ) {
		// First pass: AI slots — `{{LWP:ai:slot[|tone=editorial|max=120]}}`.
		// Run before loops so that an `ai:hero_lead` inside a loop body
		// is resolved per-iteration with the correct context.
		$str = preg_replace_callback(
			'/\{\{LWP:ai:([a-z_]+)(?:\|([^}]*))?\}\}/i',
			[ $this, 'resolve_ai' ],
			$str
		);

		// Second pass: loops (they contain | and ::scalar markers that the
		// scalar pass would mis-handle if we did scalars first).
		$str = preg_replace_callback(
			'/\{\{LWP:([a-z_]+_loop)(?::(\d+))?\|(.*?)\}\}/s',
			[ $this, 'resolve_loop' ],
			$str
		);

		// Third pass: scalars.
		$str = preg_replace_callback(
			'/\{\{LWP:([a-z_][a-z0-9_.]*)(?::([^}|]*))?\}\}/i',
			[ $this, 'resolve_scalar' ],
			$str
		);

		return $str;
	}

	/**
	 * AI slot resolver — bridges into LuwiPress_Gold_AI_Content.
	 * Falls back to the slot's static default text when AI is disabled
	 * or LuwiPress core isn't installed.
	 */
	private function resolve_ai( $matches ) {
		$slot = $matches[1];
		$args_str = $matches[2] ?? '';
		$context = [];
		// Optional pipe-separated args: tone=editorial|max=120|name=Feramis
		if ( $args_str !== '' ) {
			foreach ( explode( '|', $args_str ) as $pair ) {
				if ( strpos( $pair, '=' ) !== false ) {
					[ $k, $v ] = explode( '=', $pair, 2 );
					$context[ trim( $k ) ] = trim( $v );
				}
			}
		}
		// Lazy-load AI module to avoid require-on-every-call cost.
		if ( ! class_exists( 'LuwiPress_Gold_AI_Content' ) ) {
			$ai_path = LUWIPRESS_GOLD_DIR . '/inc/wizard/lib/class-ai-content.php';
			if ( file_exists( $ai_path ) ) {
				require_once $ai_path;
			} else {
				return $matches[0];
			}
		}
		$text = LuwiPress_Gold_AI_Content::resolve( $slot, $context );
		// Auto-escape newlines into <br> when the surrounding context is
		// HTML (cheap heuristic — most Elementor html widgets carry HTML).
		return $text;
	}

	/* -------------------------------------------------------------------
	 * Loop resolvers
	 * ----------------------------------------------------------------- */

	private function resolve_loop( $matches ) {
		$loop_name    = $matches[1];        // e.g. featured_loop
		$limit        = isset( $matches[2] ) && $matches[2] !== '' ? (int) $matches[2] : 8;
		$inner_tmpl   = $matches[3];

		$rows = $this->collect_loop_rows( $loop_name, $limit );
		if ( empty( $rows ) ) return $matches[0]; // keep template default

		$out = '';
		foreach ( $rows as $row ) {
			$out .= $this->apply_row_template( $inner_tmpl, $row );
		}
		return $out;
	}

	/**
	 * Walks the inner template, replacing {{LWP:product.name}} etc. against
	 * the current row's data dictionary.
	 */
	private function apply_row_template( $tmpl, $row ) {
		return preg_replace_callback(
			'/\{\{LWP:([a-z_]+)\.([a-z_]+)\}\}/i',
			function ( $m ) use ( $row ) {
				$key = $m[2];
				if ( isset( $row[ $key ] ) ) {
					return $this->safe( $row[ $key ] );
				}
				// Allow nested keys like image_url, badge — fall back to ''.
				return '';
			},
			$tmpl
		);
	}

	private function collect_loop_rows( $name, $limit ) {
		switch ( $name ) {
			case 'featured_loop':
				return $this->loop_featured( $limit );
			case 'categories_loop':
				return $this->loop_categories( $limit );
			case 'subcategories_loop':
				return $this->loop_subcategories( $limit );
			case 'masters_loop':
				return $this->loop_masters( $limit );
			case 'posts_loop':
				return $this->loop_posts( $limit );
			default:
				return [];
		}
	}

	private function loop_featured( $limit ) {
		$rows = [];
		$src  = $this->snap['top_sellers'] ?? [];
		foreach ( array_slice( $src, 0, $limit ) as $p ) {
			$pid = (int) ( $p['id'] ?? 0 );
			if ( ! $pid ) continue;
			$product = function_exists( 'wc_get_product' ) ? wc_get_product( $pid ) : null;
			$cats    = wp_get_post_terms( $pid, 'product_cat', [ 'fields' => 'names' ] );
			$cat     = ! is_wp_error( $cats ) && ! empty( $cats ) ? $cats[0] : '';
			$maker   = $product ? $product->get_attribute( 'pa_luthier' ) : '';
			$image   = $p['image'] ?? '';
			$on_sale = $product ? $product->is_on_sale() : false;
			$badge   = '';
			if ( $on_sale && $product ) {
				$reg = (float) $product->get_regular_price();
				$sal = (float) $product->get_sale_price();
				if ( $reg > 0 && $sal > 0 ) {
					$badge = '−' . round( ( ( $reg - $sal ) / $reg ) * 100 ) . '%';
				}
			}
			$rows[] = [
				'id'        => $pid,
				'name'      => $p['title'] ?? '',
				'url'       => $p['url'] ?? '',
				'price'     => $p['price'] ?? '',
				'price_html'=> $product ? wp_strip_all_tags( $product->get_price_html() ) : ( $p['price'] ?? '' ),
				'image'     => $image,
				'image_style' => $image ? 'background-image:url(' . esc_url( $image ) . ');background-size:cover;background-position:center' : '',
				'category'  => $cat,
				'maker'     => $maker,
				'badge'     => $badge,
				'on_sale'   => $on_sale ? '1' : '',
				'sku'       => $p['sku'] ?? '',
			];
		}
		return $rows;
	}

	private function loop_categories( $limit ) {
		$rows = [];
		$src  = $this->snap['wc']['top_cats'] ?? [];
		foreach ( array_slice( $src, 0, $limit ) as $c ) {
			$thumb_id = get_term_meta( $c['id'], 'thumbnail_id', true );
			$image    = $thumb_id ? wp_get_attachment_image_url( (int) $thumb_id, 'large' ) : '';
			$rows[] = [
				'id'         => (int) $c['id'],
				'name'       => $c['name'],
				'slug'       => $c['slug'],
				'count'      => (int) $c['count'],
				'count_label'=> sprintf(
					/* translators: %d: items */
					_n( '%d item', '%d items', $c['count'], 'luwipress-gold' ),
					$c['count']
				),
				'url'        => get_term_link( (int) $c['id'], 'product_cat' ),
				'image'      => $image,
				'image_style'=> $image ? 'background-image:url(' . esc_url( $image ) . ');background-size:cover;background-position:center' : '',
			];
		}
		return $rows;
	}

	private function loop_subcategories( $limit ) {
		$rows = [];
		$src  = $this->snap['top_terms'] ?? [];
		foreach ( array_slice( $src, 0, $limit ) as $t ) {
			$rows[] = [
				'name'  => $t['name'],
				'slug'  => $t['slug'],
				'count' => (int) $t['count'],
				'url'   => $t['url'],
			];
		}
		return $rows;
	}

	private function loop_masters( $limit ) {
		$rows = [];
		$src  = $this->snap['masters'] ?? [];
		foreach ( array_slice( $src, 0, $limit ) as $m ) {
			$rows[] = [
				'name'    => $m['name'],
				'slug'    => $m['slug'],
				'count'   => (int) $m['count'],
				'init'    => $m['init'],
				'url'     => $m['url'] ?? '',
				'craft'   => '', // pa_luthier doesn't carry craft per term — left for future
			];
		}
		return $rows;
	}

	private function loop_posts( $limit ) {
		$args = [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => max( 1, $limit ),
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		];
		$query = new WP_Query( $args );
		$rows = [];
		while ( $query->have_posts() ) {
			$query->the_post();
			$cats = get_the_category();
			$cat  = $cats ? $cats[0]->name : __( 'Journal', 'luwipress-gold' );
			$image = get_the_post_thumbnail_url( null, 'large' );
			$rows[] = [
				'id'         => get_the_ID(),
				'title'      => get_the_title(),
				'excerpt'    => wp_trim_words( get_the_excerpt(), 28 ),
				'url'        => get_permalink(),
				'date'       => get_the_date(),
				'date_iso'   => get_the_date( 'c' ),
				'category'   => $cat,
				'image'      => $image,
				'image_style'=> $image ? 'background-image:url(' . esc_url( $image ) . ');background-size:cover;background-position:center' : '',
				'minutes'    => max( 1, (int) ceil( str_word_count( get_the_content() ) / 200 ) ),
			];
		}
		wp_reset_postdata();
		return $rows;
	}

	/* -------------------------------------------------------------------
	 * Scalar resolver
	 * ----------------------------------------------------------------- */

	private function resolve_scalar( $matches ) {
		$key = $matches[1];
		$arg = isset( $matches[2] ) ? $matches[2] : '';

		$map = $this->scalars();
		if ( array_key_exists( $key, $map ) ) {
			$val = $map[ $key ];
			return $this->safe( $val );
		}

		// Argument-bearing scalars (e.g. counts with multipliers).
		switch ( $key ) {
			case 'site_name':       return $this->safe( get_bloginfo( 'name' ) );
			case 'site_description': return $this->safe( get_bloginfo( 'description' ) );
			case 'admin_email':      return $this->safe( get_option( 'admin_email' ) );
			case 'home_url':         return esc_url( home_url( '/' ) );
			case 'shop_url':         return function_exists( 'wc_get_page_permalink' ) ? esc_url( wc_get_page_permalink( 'shop' ) ) : esc_url( home_url( '/shop/' ) );
			case 'about_url':        return esc_url( home_url( '/about/' ) );
			case 'journal_url':
				$blog = get_option( 'page_for_posts' );
				return esc_url( $blog ? get_permalink( $blog ) : home_url( '/journal/' ) );
			case 'masters_url':      return esc_url( home_url( '/masters/' ) );
			case 'contact_url':      return esc_url( home_url( '/contact/' ) );
			case 'currency_symbol':  return function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '€';
		}

		// Unknown — return original so the literal stays.
		return $matches[0];
	}

	/**
	 * Pre-resolved scalar dictionary. Built once per compile() call.
	 */
	private function scalars() {
		if ( $this->scalars !== null ) return $this->scalars;

		$wc_active     = ! empty( $this->snap['wc']['active'] );
		$product_count = $wc_active ? (int) $this->snap['wc']['product_count'] : 0;
		$cats          = $this->snap['wc']['top_cats'] ?? [];
		$cat_count     = count( $cats );
		$sub_count     = count( $this->snap['wc']['sub_cats'] ?? [] );
		$post_count    = (int) ( $this->snap['content']['posts'] ?? 0 );
		$page_count    = (int) ( $this->snap['content']['pages'] ?? 0 );
		$lang_count    = count( $this->snap['i18n']['active'] ?? [] );
		$master_count  = count( $this->snap['masters'] ?? [] );
		$on_sale       = $wc_active ? (int) $this->snap['wc']['on_sale_count'] : 0;

		// Top maker name (used in hero card defaults).
		$top_master = $this->snap['masters'][0] ?? null;
		$top_master_name = $top_master ? $top_master['name'] : '';
		$top_master_init = $top_master ? $top_master['init'] : '';

		// Featured product → hero img tag (e.g. "Sultan Turkish Oud — Walnut").
		$top_seller = $this->snap['top_sellers'][0] ?? null;
		$top_seller_name = $top_seller ? $top_seller['title'] : '';

		// Category name list — for hero copy ("Anatolia, Persia & the Mediterranean")
		// we want the top 3 cat names joined intelligently.
		$cat_names = wp_list_pluck( array_slice( $cats, 0, 3 ), 'name' );
		$cat_phrase = '';
		if ( count( $cat_names ) >= 3 ) {
			$cat_phrase = $cat_names[0] . ', ' . $cat_names[1] . ' &amp; ' . $cat_names[2];
		} elseif ( count( $cat_names ) === 2 ) {
			$cat_phrase = $cat_names[0] . ' &amp; ' . $cat_names[1];
		} elseif ( count( $cat_names ) === 1 ) {
			$cat_phrase = $cat_names[0];
		}

		// Master count phrase — for "Twenty-eight masters" headline.
		$master_phrase = $master_count > 0
			? sprintf(
				/* translators: %d: master count */
				_n( '%d master', '%d masters', $master_count, 'luwipress-gold' ),
				$master_count
			)
			: '';

		// Country count — read from a theme_mod or fall back to a sane default.
		$countries = (int) get_theme_mod( 'luwipress_gold_countries_shipped', 0 );
		if ( $countries === 0 ) {
			// Try to derive from WC shipping zones.
			$countries = $this->derive_country_count();
		}

		$this->scalars = [
			// Counts (numeric, for stat cards).
			'product_count'       => number_format_i18n( $product_count ),
			'product_count_raw'   => $product_count,
			'category_count'      => number_format_i18n( $cat_count ),
			'subcategory_count'   => number_format_i18n( $sub_count ),
			'post_count'          => number_format_i18n( $post_count ),
			'page_count'          => number_format_i18n( $page_count ),
			'language_count'      => number_format_i18n( $lang_count ),
			'master_count'        => number_format_i18n( $master_count ),
			'on_sale_count'       => number_format_i18n( $on_sale ),
			'countries_count'     => number_format_i18n( max( $countries, 1 ) ),

			// Phrases (already-formatted human strings).
			'category_phrase'     => $cat_phrase,
			'master_phrase'       => $master_phrase,

			// Top-of-list values.
			'top_seller_name'     => $top_seller_name,
			'top_master_name'     => $top_master_name,
			'top_master_init'     => $top_master_init,

			// i18n helpers.
			'languages_label'     => $lang_count > 1 ? implode( ' · ', wp_list_pluck( $this->snap['i18n']['active'], 'code' ) ) : '',

			// Theme tokens (mirror Customizer for no-Elementor renders).
			'primary_color'       => get_theme_mod( 'luwipress_gold_color_primary', '#735c00' ),
			'primary_light'       => get_theme_mod( 'luwipress_gold_color_primary_light', '#D4AF37' ),
			'sale_color'          => get_theme_mod( 'luwipress_gold_color_sale', '#a33b3e' ),

			// Conditionals (string "1"/"" so JSON renders cleanly).
			'has_woocommerce'     => $wc_active ? '1' : '',
			'has_masters'         => $master_count > 0 ? '1' : '',
			'has_multilingual'    => $lang_count > 1 ? '1' : '',
		];
		return $this->scalars;
	}

	/**
	 * Read WC shipping zones to estimate country reach.
	 */
	private function derive_country_count() {
		if ( ! class_exists( 'WC_Shipping_Zones' ) ) return 0;
		$count = 0;
		try {
			foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
				$zone_obj = new WC_Shipping_Zone( (int) $zone['id'] );
				$locations = $zone_obj->get_zone_locations();
				$countries = array_filter( $locations, function ( $l ) {
					return isset( $l->type ) && $l->type === 'country';
				} );
				$count += count( $countries );
			}
		} catch ( \Throwable $e ) {
			// Silent.
		}
		return $count;
	}

	/**
	 * Tiny safe-output helper. Designed for HTML attributes + text nodes;
	 * not for raw HTML interpolation.
	 */
	private function safe( $value ) {
		if ( is_int( $value ) || is_float( $value ) ) return (string) $value;
		if ( is_string( $value ) ) return $value;
		if ( is_array( $value ) )  return implode( ', ', array_map( 'strval', $value ) );
		return '';
	}
}
