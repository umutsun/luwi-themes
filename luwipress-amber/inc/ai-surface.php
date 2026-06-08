<?php
/**
 * AI surface — two storefront features that turn the LuwiPress plugin's
 * AI engine + Knowledge Graph into visible parts of the theme:
 *
 *   1. AI search suggestions  — inside the search overlay. As the visitor
 *      types, we run a fast WP_Query for matching products and ask the
 *      LuwiPress AI engine for 3 complementary search terms (cached 30 min
 *      per query). Lands as product cards + suggestion chips below the input.
 *
 *   2. KG-related rail        — on single-product pages, below the buy
 *      column. Reads the current product's master / luthier taxonomy +
 *      primary category and surfaces 4 sibling products. Uses direct PHP
 *      (admin-only KG REST endpoint isn't appropriate for storefront);
 *      caches per product ID for 1 hour.
 *
 * Customer chat is intentionally NOT in this file — the LuwiPress core
 * plugin owns that surface end-to-end (shell + bubble + panel + JS).
 *
 * Each feature is self-gated. If LuwiPress is missing the surface stays
 * dark instead of erroring out — the bridge file's admin notice handles
 * the "fix it" prompt for the operator.
 *
 * @package luwipress-amber
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ────────────────────────────────────────────────────────────────────
 *  REST namespace & rate limiting
 * ──────────────────────────────────────────────────────────────────── */

define( 'LUWIPRESS_AMBER_REST_NS', 'luwipress-amber/v1' );

/**
 * Per-IP rate limit gate. Storefront endpoints (search-suggest) are
 * unauthenticated — without this they're an open AI billing tap.
 *
 * Default: 30 calls / hour / IP. Operator can raise/lower via filter.
 *
 * @return true|WP_Error true when allowed, WP_Error 429 when throttled.
 */
function lwp_amber_storefront_rate_gate( $bucket ) {
	$ip      = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' );
	$key     = 'lwp_amber_rl_' . $bucket . '_' . md5( $ip );
	$count   = (int) get_transient( $key );
	$limit   = (int) apply_filters( 'luwipress_amber_storefront_rate_limit', 30, $bucket );

	if ( $count >= $limit ) {
		return new WP_Error(
			'rate_limit',
			__( 'Too many requests. Please wait a moment.', 'luwipress-amber' ),
			array( 'status' => 429 )
		);
	}
	set_transient( $key, $count + 1, HOUR_IN_SECONDS );
	return true;
}

/* ────────────────────────────────────────────────────────────────────
 *  AI search suggestions — REST endpoint + UI hook
 * ──────────────────────────────────────────────────────────────────── */

add_action( 'rest_api_init', function () {
	register_rest_route( LUWIPRESS_AMBER_REST_NS, '/search-suggest', array(
		'methods'             => 'POST',
		'callback'            => 'lwp_amber_handle_search_suggest',
		'permission_callback' => '__return_true',
		'args'                => array(
			'q'     => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function ( $v ) {
					return is_string( $v ) && mb_strlen( $v ) <= 100;
				},
			),
			'limit' => array(
				'required'          => false,
				'type'              => 'integer',
				'default'           => 5,
				'sanitize_callback' => 'absint',
			),
		),
	) );
} );

/**
 * POST /wp-json/luwipress-amber/v1/search-suggest
 * Body: { q: "darbuka", limit?: 5 }
 * Returns: { products: [{id,title,url,thumb,price_html,eyebrow}], suggestions: [string] }
 */
function lwp_amber_handle_search_suggest( WP_REST_Request $request ) {
	$gate = lwp_amber_storefront_rate_gate( 'search' );
	if ( is_wp_error( $gate ) ) {
		return $gate;
	}

	$query = trim( (string) $request->get_param( 'q' ) );
	$limit = max( 1, min( 8, (int) $request->get_param( 'limit' ) ) );

	if ( mb_strlen( $query ) < 2 ) {
		return rest_ensure_response( array( 'products' => array(), 'suggestions' => array() ) );
	}

	// Cache per (query, limit) for 30 minutes — burst protection + cost cap.
	$cache_key = 'lwp_amber_ss_' . md5( $query . '|' . $limit );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		$cached['_cache'] = true;
		return rest_ensure_response( $cached );
	}

	$products = lwp_amber_search_products_fast( $query, $limit );

	// AI suggestion chips — only worth firing when we already have at least
	// some product matches. Empty results means we'd be paying tokens for a
	// 'sorry, nothing' chip which has no value to the visitor.
	$suggestions = array();
	if ( ! empty( $products ) && lwp_amber_lp_active() ) {
		$suggestions = lwp_amber_ai_search_suggestions( $query, $products );
	}

	$payload = array(
		'products'    => $products,
		'suggestions' => $suggestions,
	);

	set_transient( $cache_key, $payload, 30 * MINUTE_IN_SECONDS );
	return rest_ensure_response( $payload );
}

/**
 * Fast product search — used by the search overlay's suggestion list.
 * Pre-AI deterministic matches: title LIKE first, then SKU, then short desc.
 *
 * @return array<int,array{id:int,title:string,url:string,thumb:string,price_html:string,eyebrow:string}>
 */
function lwp_amber_search_products_fast( $query, $limit ) {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return array();
	}

	$args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		's'              => $query,
		'orderby'        => 'relevance',
		'no_found_rows'  => true,
	);

	$wpq = new WP_Query( $args );
	if ( empty( $wpq->posts ) ) {
		return array();
	}

	$out = array();
	foreach ( $wpq->posts as $post ) {
		$product = wc_get_product( $post->ID );
		if ( ! $product || ! $product->is_visible() ) {
			continue;
		}
		$thumb_id  = $product->get_image_id();
		$thumb_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'luwipress-amber-card' ) : '';

		$eyebrow = '';
		$cat_ids = $product->get_category_ids();
		if ( ! empty( $cat_ids ) ) {
			$cat = get_term( (int) $cat_ids[0], 'product_cat' );
			if ( $cat && ! is_wp_error( $cat ) ) {
				$eyebrow = $cat->name;
			}
		}

		$out[] = array(
			'id'         => (int) $product->get_id(),
			'title'      => $product->get_name(),
			'url'        => get_permalink( $post->ID ),
			'thumb'      => $thumb_url ?: '',
			'price_html' => $product->get_price_html(),
			'eyebrow'    => $eyebrow,
		);
	}
	wp_reset_postdata();
	return $out;
}

/**
 * Ask the LuwiPress AI engine for up to 3 complementary search terms.
 *
 * The prompt is intentionally tight — JSON mode + low temperature + ~120
 * output tokens, which keeps the suggestion call well under $0.001 per
 * unique cached query. Returns an empty array on any error so the overlay
 * still renders product matches without blocking.
 *
 * @return string[]
 */
function lwp_amber_ai_search_suggestions( $query, array $products ) {
	$titles = array();
	foreach ( array_slice( $products, 0, 5 ) as $p ) {
		$titles[] = $p['title'];
	}
	$titles_str = implode( '; ', $titles );

	$store_name  = get_bloginfo( 'name' );
	$catalog_hint = $store_name ?: 'this storefront';

	$messages = array(
		array(
			'role'    => 'system',
			'content' =>
				"You write tight search suggestions for {$catalog_hint}. Given a customer query and a few matching product titles, return 3 short alternative search terms (1-3 words each) that a curious visitor might try next. Stay in the same product domain. " .
				"Reply ONLY as JSON: {\"suggestions\": [\"term1\", \"term2\", \"term3\"]}. Do not explain.",
		),
		array(
			'role'    => 'user',
			'content' => "Query: \"{$query}\". Top matches: {$titles_str}.",
		),
	);

	$result = lwp_amber_lp_ai_dispatch( 'customer-chat', $messages, array(
		'max_tokens'  => 160,
		'temperature' => 0.4,
		'json_mode'   => true,
	) );

	if ( is_wp_error( $result ) ) {
		return array();
	}

	$content = isset( $result['content'] ) ? (string) $result['content'] : '';
	if ( $content === '' ) {
		return array();
	}

	// Strip ```json fences if any provider slipped them through despite json_mode.
	$content = preg_replace( '/```(?:json)?\s*|\s*```/i', '', $content );
	$decoded = json_decode( $content, true );
	if ( ! is_array( $decoded ) || empty( $decoded['suggestions'] ) ) {
		return array();
	}

	$out = array();
	foreach ( (array) $decoded['suggestions'] as $term ) {
		$term = sanitize_text_field( (string) $term );
		if ( $term !== '' && mb_strlen( $term ) <= 60 ) {
			$out[] = $term;
		}
		if ( count( $out ) >= 3 ) {
			break;
		}
	}
	return $out;
}

/**
 * Inject the suggestions container into the existing search overlay markup
 * already rendered by header.php. We append after </form> so the stock
 * markup isn't disturbed and a JS-disabled visitor still gets the basic
 * search form.
 */
add_action( 'wp_footer', function () {
	if ( ! lwp_amber_lp_active() ) {
		return;
	}
	?>
	<template id="lwp-amber-search-suggest-template">
		<div class="lwp-search-suggest" hidden>
			<div class="lwp-search-suggest__products" role="list"></div>
			<div class="lwp-search-suggest__chips" role="list" aria-label="<?php esc_attr_e( 'AI suggestions', 'luwipress-amber' ); ?>"></div>
			<div class="lwp-search-suggest__empty" hidden>
				<?php esc_html_e( 'No products match yet — try a broader term.', 'luwipress-amber' ); ?>
			</div>
		</div>
	</template>
	<?php
}, 11 );

/* ────────────────────────────────────────────────────────────────────
 *  Customer chat — owned by LuwiPress core
 *
 *  The chat widget shell + bubble + panel are rendered by the LuwiPress
 *  plugin itself (`LuwiPress::render_chat_assets` on wp_footer p1). The
 *  theme deliberately does NOT render its own; the plugin's UI is the
 *  one operators trust and tune through `LuwiPress -> Customer Chat`.
 *
 *  The plugin's rendering pipeline (CSS + config + JS) is the single
 *  source of truth for the storefront chat. To restyle the icon to match
 *  this theme's gold gradient identity, edit the plugin's
 *  `assets/css/luwipress-chat.css` — not this theme.
 * ──────────────────────────────────────────────────────────────────── */

/* ────────────────────────────────────────────────────────────────────
 *  KG-related rail — single-product page
 * ──────────────────────────────────────────────────────────────────── */

/**
 * Render a "Pieces with the same hand" rail of related products on
 * single-product pages — strictly when the current product matches a
 * master / luthier taxonomy (`pa_master` or `pa_luthier`). The plain
 * "category fallback" branch was removed in 1.6.2 because WooCommerce's
 * own related products section (priority 20, just below this hook)
 * already covers the broad "more like this" surface and rendering both
 * produced a visible duplicate to visitors.
 *
 * The rail still adds value for master matches because that taxonomy
 * isn't surfaced anywhere else — visitors get an editorial cross-sell
 * to other pieces by the same craftsperson, which WC's tag/category
 * fallback can't reproduce.
 *
 * Filter `luwipress_amber_show_kg_related_rail` lets a child theme or
 * sister plugin force the rail back on for category fallback if they
 * also remove the WC default. Default behavior keeps the duplicate
 * suppressed.
 */
add_action( 'woocommerce_after_single_product_summary', function () {
	if ( ! function_exists( 'wc_get_product' ) || ! is_product() ) {
		return;
	}
	$product = wc_get_product( get_the_ID() );
	if ( ! $product ) {
		return;
	}

	$related = lwp_amber_kg_related_products( $product, 4 );
	if ( empty( $related ) ) {
		return;
	}

	$by_master = ! empty( $related['master_name'] );

	// Suppress the rail unless we have a master/luthier match. WC's
	// default related products handles the broader fallback below.
	if ( ! apply_filters( 'luwipress_amber_show_kg_related_rail', $by_master, $product, $related ) ) {
		return;
	}

	/* translators: %s: master / luthier name */
	$rail_eyebrow = sprintf( esc_html__( 'More from %s', 'luwipress-amber' ), $related['master_name'] );
	?>
	<section class="lwp-related-rail" aria-labelledby="lwp-related-rail-title">
		<header class="lwp-related-rail__head">
			<span class="lwp-eyebrow lwp-related-rail__eyebrow">— <?php echo esc_html( $rail_eyebrow ); ?></span>
			<h2 id="lwp-related-rail-title" class="lwp-related-rail__title">
				<?php esc_html_e( 'Pieces with the same hand', 'luwipress-amber' ); ?>
			</h2>
			<?php if ( ! empty( $related['cat_url'] ) ) : ?>
				<a class="lwp-related-rail__more lwp-ulink" href="<?php echo esc_url( $related['cat_url'] ); ?>">
					<?php esc_html_e( 'Browse the family', 'luwipress-amber' ); ?> →
				</a>
			<?php endif; ?>
		</header>

		<div class="lwp-related-rail__grid">
			<?php foreach ( $related['products'] as $p ) : ?>
				<a class="lwp-related-rail__card" href="<?php echo esc_url( $p['url'] ); ?>">
					<div class="lwp-related-rail__card-img">
						<?php if ( $p['thumb'] ) : ?>
							<img src="<?php echo esc_url( $p['thumb'] ); ?>" alt="<?php echo esc_attr( $p['title'] ); ?>" loading="lazy" />
						<?php else : ?>
							<span class="lwp-skel" aria-hidden="true"></span>
						<?php endif; ?>
					</div>
					<div class="lwp-related-rail__card-meta">
						<?php if ( $p['eyebrow'] ) : ?>
							<span class="lwp-eyebrow">— <?php echo esc_html( $p['eyebrow'] ); ?></span>
						<?php endif; ?>
						<h3 class="lwp-related-rail__card-title"><?php echo esc_html( $p['title'] ); ?></h3>
						<span class="lwp-related-rail__card-price"><?php echo wp_kses_post( $p['price_html'] ); ?></span>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}, 15 );

/**
 * Build the related-products payload for a given product.
 * Cached per product ID for 1 hour.
 *
 * Strategy (ranked):
 *   A. Same `pa_master` term  (Tapadum's actual luthier taxonomy)
 *   B. Same `pa_luthier` term (handoff-doc convention)
 *   C. Same primary `product_cat` (broader fallback)
 *
 * @return array{master_name:string, cat_url:string, products:array<int,array>}|null
 */
function lwp_amber_kg_related_products( WC_Product $product, $limit = 4 ) {
	$cache_key = 'lwp_amber_rel_' . $product->get_id() . '_' . $limit;
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$master_name = '';
	$master_term_id = 0;
	$master_taxonomy = '';

	foreach ( array( 'pa_master', 'pa_luthier' ) as $tax ) {
		if ( ! taxonomy_exists( $tax ) ) {
			continue;
		}
		$terms = wp_get_post_terms( $product->get_id(), $tax );
		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			$master_term_id  = (int) $terms[0]->term_id;
			$master_name     = $terms[0]->name;
			$master_taxonomy = $tax;
			break;
		}
	}

	$primary_cat_id  = 0;
	$primary_cat_url = '';
	$cat_ids = $product->get_category_ids();
	if ( ! empty( $cat_ids ) ) {
		$primary_cat_id = (int) $cat_ids[0];
		$cat = get_term( $primary_cat_id, 'product_cat' );
		if ( $cat && ! is_wp_error( $cat ) ) {
			$primary_cat_url = (string) get_term_link( $cat );
		}
	}

	$query_args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'post__not_in'   => array( $product->get_id() ),
		'orderby'        => 'rand',
		'no_found_rows'  => true,
		'tax_query'      => array(),
	);

	if ( $master_term_id && $master_taxonomy ) {
		$query_args['tax_query'][] = array(
			'taxonomy' => $master_taxonomy,
			'field'    => 'term_id',
			'terms'    => array( $master_term_id ),
		);
	} elseif ( $primary_cat_id ) {
		$query_args['tax_query'][] = array(
			'taxonomy' => 'product_cat',
			'field'    => 'term_id',
			'terms'    => array( $primary_cat_id ),
		);
	} else {
		// Nothing to relate against — caller will skip rendering.
		set_transient( $cache_key, array(), HOUR_IN_SECONDS );
		return null;
	}

	$wpq = new WP_Query( $query_args );

	// If master-based query returned nothing, fall back to primary category.
	if ( empty( $wpq->posts ) && $master_term_id && $primary_cat_id ) {
		$query_args['tax_query'] = array( array(
			'taxonomy' => 'product_cat',
			'field'    => 'term_id',
			'terms'    => array( $primary_cat_id ),
		) );
		$wpq = new WP_Query( $query_args );
	}

	$products = array();
	foreach ( $wpq->posts as $post ) {
		$p = wc_get_product( $post->ID );
		if ( ! $p || ! $p->is_visible() ) {
			continue;
		}
		$thumb_id = $p->get_image_id();
		$thumb    = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'luwipress-amber-card' ) : '';

		$eyebrow = '';
		$pcats = $p->get_category_ids();
		if ( ! empty( $pcats ) ) {
			$pc = get_term( (int) $pcats[0], 'product_cat' );
			if ( $pc && ! is_wp_error( $pc ) ) {
				$eyebrow = $pc->name;
			}
		}

		$products[] = array(
			'id'         => $p->get_id(),
			'title'      => $p->get_name(),
			'url'        => get_permalink( $post->ID ),
			'thumb'      => $thumb ?: '',
			'price_html' => $p->get_price_html(),
			'eyebrow'    => $eyebrow,
		);
	}
	wp_reset_postdata();

	$payload = array(
		'master_name' => $master_name,
		'cat_url'     => $primary_cat_url,
		'products'    => $products,
	);

	set_transient( $cache_key, $payload, HOUR_IN_SECONDS );
	return $payload;
}

/**
 * Cache-bust the KG-related rail when a product is saved. Hooked once at
 * file load — placing the add_action inside the lookup function would
 * register a fresh listener per page view (memory leak).
 */
add_action( 'save_post_product', function ( $post_id ) {
	delete_transient( 'lwp_amber_rel_' . (int) $post_id . '_4' );
} );

/**
 * Same bust on term changes — a renamed luthier or category should
 * propagate within a click. Tax-targeted busts are cheap; full flush
 * would over-reach.
 */
add_action( 'edited_term', function ( $term_id, $tt_id, $taxonomy ) {
	if ( ! in_array( $taxonomy, array( 'pa_master', 'pa_luthier', 'product_cat' ), true ) ) {
		return;
	}
	// Walk every product in this term and bust its rail cache. Capped to
	// 200 to keep an avalanche edit cheap; rest will roll over via TTL.
	$pids = get_posts( array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'fields'         => 'ids',
		'posts_per_page' => 200,
		'tax_query'      => array( array(
			'taxonomy' => $taxonomy,
			'field'    => 'term_id',
			'terms'    => array( (int) $term_id ),
		) ),
	) );
	foreach ( (array) $pids as $pid ) {
		delete_transient( 'lwp_amber_rel_' . (int) $pid . '_4' );
	}
}, 10, 3 );

/**
 * Plugin event subscriptions — react to first-class LuwiPress signals
 * (3.1.45+) instead of inferring from generic WP hooks. The save_post +
 * edited_term hooks above stay as defence in depth for non-LuwiPress
 * mutations (e.g. operator editing meta in WP admin directly).
 */
add_action( 'luwipress_after_product_enrich', function ( $product_id ) {
	delete_transient( 'lwp_amber_rel_' . (int) $product_id . '_4' );
	delete_transient( 'lwp_amber_ss_' . md5( '' ) ); // touch nothing — placeholder for future SS scoping
}, 10, 1 );

/**
 * SEO meta change: not directly cache-relevant on the storefront yet, but
 * the chat widget caches a session-scoped product context that derives
 * from titles/descriptions. Bust the session cache to be safe.
 */
add_action( 'luwipress_after_seo_meta_write', function ( $post_id, $fields ) {
	// Currently the AI surface only caches per-product KG rail data.
	// Bust the rail in case the product title fed an upstream snippet.
	if ( 'product' === get_post_type( $post_id ) ) {
		delete_transient( 'lwp_amber_rel_' . (int) $post_id . '_4' );
	}
}, 10, 2 );

/**
 * Translation completed: a fresh language version of a product means the
 * KG-related rail (which ranks by master/luthier/category) is unchanged
 * for the source post but the translated post needs its own warm cache.
 */
add_action( 'luwipress_after_translation_request', function ( $product_id, $language, $status ) {
	if ( 'saved' !== $status ) return;
	// Bust source-side rail too in case translation surfaced new metadata
	// like translated taxonomy terms.
	delete_transient( 'lwp_amber_rel_' . (int) $product_id . '_4' );
}, 10, 3 );

/* ────────────────────────────────────────────────────────────────────
 *  Asset enqueue — AI surface CSS + JS
 * ──────────────────────────────────────────────────────────────────── */

/**
 * Enqueue the AI surface assets only when LuwiPress is active. Loaded at
 * priority 10000 so they land after `luwipress-amber-widgets` (9999) and
 * win specificity ties on shared selectors (`.lwp-search-overlay`, etc.).
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! lwp_amber_lp_active() ) {
		return;
	}

	$ver = LUWIPRESS_AMBER_VERSION;

	wp_enqueue_style(
		'luwipress-amber-ai-surface',
		LUWIPRESS_AMBER_URI . '/assets/css/ai-surface.css',
		array( 'luwipress-amber-widgets' ),
		$ver
	);

	wp_enqueue_script(
		'luwipress-amber-ai-surface',
		LUWIPRESS_AMBER_URI . '/assets/js/ai-surface.js',
		array( 'luwipress-amber-frontend' ),
		$ver,
		true
	);

	wp_localize_script(
		'luwipress-amber-ai-surface',
		'lwpGoldAi',
		array(
			'searchEndpoint' => esc_url_raw( rest_url( LUWIPRESS_AMBER_REST_NS . '/search-suggest' ) ),
			'i18n'           => array(
				'noResults' => __( 'No products match yet — try a broader term.', 'luwipress-amber' ),
				'searching' => __( 'Searching…', 'luwipress-amber' ),
				'tryThese'  => __( 'Try these', 'luwipress-amber' ),
			),
		)
	);
}, 10000 );
