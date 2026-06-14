<?php
/**
 * LuwiPress Amber — Elementor-ready theme bootstrap.
 *
 * Keeps the core minimal: theme support, asset enqueue, and a small Elementor
 * compatibility layer. All page layout is delivered via the Elementor Kit
 * shipped under elementor-kit/ — operators import that kit on activation.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme version — read DYNAMICALLY from style.css via `wp_get_theme()`
 * so a single bump in the style.css `Version:` header propagates to
 * every cache-buster query string (frontend.js?ver=, widgets.css?ver=)
 * without requiring a parallel constant edit. Avoids the regression
 * where stylesheet bumps but the constant stayed behind, leaving
 * browsers serving stale CSS/JS from their disk cache.
 *
 * Hardcoded fallback only fires if `wp_get_theme()` somehow returns
 * empty (defensive — shouldn't happen during a live request).
 */
$lwp_amber_theme_obj = function_exists( 'wp_get_theme' ) ? wp_get_theme() : null;
define(
	'LUWIPRESS_AMBER_VERSION',
	( $lwp_amber_theme_obj && $lwp_amber_theme_obj->get( 'Version' ) )
		? (string) $lwp_amber_theme_obj->get( 'Version' )
		: '1.0.0'
);
unset( $lwp_amber_theme_obj );
define( 'LUWIPRESS_AMBER_DIR', get_template_directory() );
define( 'LUWIPRESS_AMBER_URI', get_template_directory_uri() );

require_once LUWIPRESS_AMBER_DIR . '/inc/setup.php';
require_once LUWIPRESS_AMBER_DIR . '/inc/enqueue.php';
require_once LUWIPRESS_AMBER_DIR . '/inc/elementor-compat.php';
require_once LUWIPRESS_AMBER_DIR . '/inc/elementor-kit-sync.php';

// Maintenance tool classes (1.7.0+) — registered into the LuwiPress Theme
// Bridge by inc/luwipress-bridge.php below. Loaded first so the class_exists
// guards in the bridge filters always find them.
require_once LUWIPRESS_AMBER_DIR . '/inc/maintenance/class-elementor-shell-tool.php';
require_once LUWIPRESS_AMBER_DIR . '/inc/maintenance/class-maintenance-tools.php';
require_once LUWIPRESS_AMBER_DIR . '/inc/maintenance/class-fix-tools.php';
require_once LUWIPRESS_AMBER_DIR . '/inc/maintenance/class-extra-audit-tools.php';
require_once LUWIPRESS_AMBER_DIR . '/inc/maintenance/class-seo-audit-tools.php';
require_once LUWIPRESS_AMBER_DIR . '/inc/maintenance/class-language-drift-tool.php';
require_once LUWIPRESS_AMBER_DIR . '/inc/maintenance/elementor-template-force.php';
require_once LUWIPRESS_AMBER_DIR . '/inc/maintenance/seo-enforcement.php';

/**
 * Append the theme version as a query string to the screenshot URL shown in
 * `Appearance → Themes → Theme Details`. WP fetches `screenshot.png` without
 * any cache-buster, so browsers serve a years-old cached copy and operators
 * see stale art after every legitimate screenshot update. Tying the URL to
 * the live theme version forces a fresh download on every bump.
 */
add_filter( 'wp_prepare_themes_for_js', function ( $themes ) {
	$slug = get_template();
	if ( isset( $themes[ $slug ]['screenshot'][0] ) ) {
		$url = $themes[ $slug ]['screenshot'][0];
		$themes[ $slug ]['screenshot'][0] = add_query_arg( 'ver', LUWIPRESS_AMBER_VERSION, $url );
	}
	return $themes;
} );

// LuwiPress bridge — single point of contact for the AI engine, chat,
// plugin detector, and ecosystem helpers. Loaded unconditionally so the
// admin notice can warn even when LuwiPress is missing.
require_once LUWIPRESS_AMBER_DIR . '/inc/luwipress-bridge.php';

// Featured Products registry — per-product flag, admin meta box, admin-bar
// toggle on single-product pages, REST endpoint, helper API. Consumed by
// the mega menu Featured panel and the featured-product / featured-strip
// widgets (and any custom code reading lwp_amber_get_featured_ids()).
require_once LUWIPRESS_AMBER_DIR . '/inc/featured-products.php';

// Mega menu admin UI — adds the per-menu-item "Featured product (override)"
// field to Appearance → Menus. Loaded unconditionally; admin-only hooks
// internally so the field only renders when admin is editing menus.
require_once LUWIPRESS_AMBER_DIR . '/inc/mega-menu-admin.php';

// Friendly-plugin glue — consolidates Rank Math / Yoast / WPML / Polylang /
// WooCommerce-side hooks that amplify each plugin's presence on the
// storefront. Self-gates internally; safe to load when nothing is detected.
require_once LUWIPRESS_AMBER_DIR . '/inc/friendly-plugins.php';

// AI surface — search suggestions, sticky chat widget shell, KG-related
// rail. Hard-deps on LuwiPress; the bridge file emits the admin notice.
require_once LUWIPRESS_AMBER_DIR . '/inc/ai-surface.php';

if ( class_exists( 'WooCommerce' ) ) {
	require_once LUWIPRESS_AMBER_DIR . '/inc/wc-pdp-hooks.php';
	// Tour Booking layer — tour = WC product; PDP date/pax/add-ons booking box,
	// cart→order booking meta, checkout Trip & pickup fields, admin dispatch
	// meta box, print-ready voucher (+ .ics), TouristTrip JSON-LD. Self-
	// contained, theme-agnostic `_fbd_*` order meta (see inc/booking/).
	require_once LUWIPRESS_AMBER_DIR . '/inc/booking/bootstrap.php';
	// Hero "tour search" — upgrades the static homepage .bookbar into a real
	// tour finder (Experience + Date + Guests → tour page with date/pax
	// prefilled via ?fbd_date & ?fbd_pax). Progressive enhancement; front page.
	require_once LUWIPRESS_AMBER_DIR . '/inc/hero-search.php';
	// Vanilla-JS PDP gallery — replaces WC's Flexslider stack. Immune to
	// LiteSpeed JS Defer/Delay because our script ships with `data-no-defer`
	// + `data-no-optimize` and runs at DOMContentLoaded. Gallery is rendered
	// fully server-side so the first image is visible BEFORE any JS runs.
	require_once LUWIPRESS_AMBER_DIR . '/inc/wc-pdp-gallery-override.php';
	// Parent-term archive enrichment (atelier note + featured product band).
	// Self-gates to product_cat parent terms; safe under WC-only loads.
	require_once LUWIPRESS_AMBER_DIR . '/inc/archive-parent-enrichment.php';
	// Smart filter sidebar — price + attribute layered nav + tags + on-sale
	// / in-stock toggles. Replaces the legacy "Categories" fallback list.
	require_once LUWIPRESS_AMBER_DIR . '/inc/smart-filters.php';
	// Cart/checkout/my-account fallback when the page was built with
	// Elementor Pro WC widgets but Pro is not installed. Detects the empty
	// Pro-widget skeleton in `the_content` and swaps in the matching WC
	// shortcode so the theme's WC template overrides take over.
	require_once LUWIPRESS_AMBER_DIR . '/inc/wc-page-fallback.php';
}

// Blog page auto-fallback — promotes a "Blog/Journal/News" page to WP
// "Posts page" when none is set, and injects a recent-posts grid into
// any empty blog-style page so an unconfigured BLOG menu link never
// renders as an empty chrome.
require_once LUWIPRESS_AMBER_DIR . '/inc/blog-page-fallback.php';

// Slug-collision redirects — 301 legacy /<hub>/ pages to their matching
// /product-category/<hub>/ archives. Operator opts in via Customizer
// (LuwiPress Amber → Performance → "Resolve page/category slug conflicts");
// when off the module is inert. Auto-discovers conflicts via DB scan,
// caches in a transient busted on page/term saves. Generic — no
// hardcoded slugs.
require_once LUWIPRESS_AMBER_DIR . '/inc/template-redirects.php';

// Cross-template layout safety net — sticky-footer color leak fix,
// min-viewport-height enforcement, archive hero image fit, mobile
// breadcrumb spacing, tag pill safeguard, PDP gallery↔tabs spacing.
require_once LUWIPRESS_AMBER_DIR . '/inc/layout-fixes.php';

// Footer enhancements — Customizer panel for social URLs, newsletter
// signup, trust signals, payment row + render helpers + inline styles.
// footer.php calls the helpers directly. Loaded unconditionally so the
// Customizer panel is always reachable.
require_once LUWIPRESS_AMBER_DIR . '/inc/footer-enhancements.php';

// Mega menu Customizer panel — global defaults for menu choice,
// threshold, columns, counts, mobile mode, blog auto-inject.
require_once LUWIPRESS_AMBER_DIR . '/inc/mega-menu-customizer.php';

// Server-side page loader. Renders the overlay into the DOM before the
// first paint to eliminate the flash-of-content-before-loader. Pairs
// with the JS handler in assets/js/frontend.js which removes the boot
// class on window.load.
require_once LUWIPRESS_AMBER_DIR . '/inc/page-loader.php';

// Generic archive Load More / Infinite Scroll — drives the Vendor + Event CPT
// archives and blog/category/tag archives (SEO-safe progressive enhancement
// over the real paginated links). WC shop archive has its own setup.
require_once LUWIPRESS_AMBER_DIR . '/inc/loadmore.php';

// Engine-CPT template routing — gives operator-defined CPT Engine types
// (Team, Events, …) a styled single + archive (generic Vendor chrome) instead
// of the bare blog fallback. A type-specific template still wins.
require_once LUWIPRESS_AMBER_DIR . '/inc/engine-cpt-templates.php';

// Onboarding wizard — admin-only.
if ( is_admin() ) {
	require_once LUWIPRESS_AMBER_DIR . '/inc/wizard/bootstrap.php';
	require_once LUWIPRESS_AMBER_DIR . '/inc/ecosystem-dashboard.php';
	require_once LUWIPRESS_AMBER_DIR . '/inc/migration.php';
}

// Custom Elementor widgets — registered on `elementor/init`.
require_once LUWIPRESS_AMBER_DIR . '/inc/widgets/loader.php';

// Customizer panel — Brand / Topbar / Header / Footer / Animation / Performance.
require_once LUWIPRESS_AMBER_DIR . '/inc/customizer/bootstrap.php';

// Mobile card width override — emits a guaranteed-cascade-winner inline
// `<style>` block at `wp_head` p999 so the products / blog / single body
// cards land at uniform 90% width on mobile, regardless of pre-V32 Kit CSS
// baseline rules that previously forced `padding-left: 20px !important` on
// `ul.products`. See `feedback_mobile_card_width_override_via_p999.md`.
require_once LUWIPRESS_AMBER_DIR . '/inc/mobile-card-width-override.php';

/**
 * Resolve a "featured" product for a mega-menu panel.
 *
 * When the top-level menu item points at a product-category archive, returns
 * the newest published product in that category; otherwise the newest product
 * overall. Cached per category for an hour. Returns null when WooCommerce is
 * absent or no product is found, so the header simply omits the featured card.
 *
 * @param string $menu_url Top-level menu item URL.
 * @return array{title:string,url:string,img:string,price:string}|null
 */
function luwipress_amber_mega_featured( $menu_url ) {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return null;
	}
	$term_slug = '';
	if ( preg_match( '#/product-category/([^/?#]+)#', (string) $menu_url, $m ) ) {
		$term_slug = $m[1];
	}
	// Scope the cache per current language. Without this, the first language to
	// populate the transient leaks its products into every other language's mega
	// menu (the menu is built from one primary menu, so $term_slug is identical
	// across languages and the keys would collide — a translated product title
	// then surfaces on the English page).
	$amber_lang = apply_filters( 'wpml_current_language', null );
	if ( ! $amber_lang && function_exists( 'pll_current_language' ) ) { $amber_lang = pll_current_language(); }
	if ( ! $amber_lang ) { $amber_lang = substr( get_locale(), 0, 2 ); }
	$cache_key = 'lwp_amber_megafeat2_' . ( $term_slug !== '' ? $term_slug : 'all' ) . '_' . $amber_lang;
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return ! empty( $cached ) ? $cached : null;
	}
	$products = luwipress_amber_query_products( $term_slug, 1 );
	$out      = array();
	if ( ! empty( $products ) ) {
		$p   = $products[0];
		// Force the card to the CURRENT language even if the query surfaced
		// another language's product (WPML cross-language leak — e.g. an ES title
		// showing on /ru/). Resolve the product to $amber_lang before reading it.
		$pid  = $p->get_id();
		$tpid = apply_filters( 'wpml_object_id', $pid, 'product', true, $amber_lang );
		if ( $tpid && (int) $tpid !== (int) $pid && function_exists( 'wc_get_product' ) ) {
			$tp = wc_get_product( $tpid );
			if ( $tp ) { $p = $tp; }
		}
		$img = wp_get_attachment_image_url( $p->get_image_id(), 'large' );
		$out = array(
			'title' => $p->get_name(),
			'url'   => get_permalink( $p->get_id() ),
			'img'   => $img ? $img : '',
			'price' => $p->get_price_html(),
		);
	}
	set_transient( $cache_key, $out, HOUR_IN_SECONDS );
	return ! empty( $out ) ? $out : null;
}

/**
 * Products for a mega-menu panel's "Popular" column.
 *
 * Returns up to $limit newest published products in the product category the
 * menu item points at. Empty array when the item isn't category-linked or WC
 * is absent (the header then falls back to a two-column children layout).
 * Cached per category for an hour.
 *
 * @param string $menu_url Top-level menu item URL.
 * @param int    $limit    Max products.
 * @return array<int,array{title:string,url:string,price:string}>
 */
function luwipress_amber_mega_products( $menu_url, $limit = 4 ) {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}
	$term_slug = '';
	if ( preg_match( '#/product-category/([^/?#]+)#', (string) $menu_url, $m ) ) {
		$term_slug = $m[1];
	}
	if ( $term_slug === '' ) {
		return array();
	}
	// Language-scoped cache key — see luwipress_amber_mega_featured() above.
	$amber_lang = apply_filters( 'wpml_current_language', null );
	if ( ! $amber_lang && function_exists( 'pll_current_language' ) ) { $amber_lang = pll_current_language(); }
	if ( ! $amber_lang ) { $amber_lang = substr( get_locale(), 0, 2 ); }
	$cache_key = 'lwp_amber_megaprod2_' . $term_slug . '_' . (int) $limit . '_' . $amber_lang;
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$products = luwipress_amber_query_products( $term_slug, (int) $limit );
	$out = array();
	foreach ( $products as $p ) {
		$out[] = array(
			'title' => $p->get_name(),
			'url'   => get_permalink( $p->get_id() ),
			'price' => $p->get_price_html(),
		);
	}
	set_transient( $cache_key, $out, HOUR_IN_SECONDS );
	return $out;
}

/**
 * Reliable product query for mega-menu panels — explicit product_cat tax_query
 * (include_children) so a category-linked menu item really filters by category.
 * wc_get_products()'s 'category' arg proved unreliable on this install. Returns
 * WC_Product objects.
 *
 * @param string $term_slug product_cat slug ('' = newest overall).
 * @param int    $limit     Max products.
 * @return array<int,\WC_Product>
 */
function luwipress_amber_query_products( $term_slug, $limit ) {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return array();
	}
	$q = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => (int) $limit,
		'orderby'             => 'date',
		'order'               => 'DESC',
		'fields'              => 'ids',
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
	);
	if ( $term_slug !== '' ) {
		$q['tax_query'] = array( array(
			'taxonomy'         => 'product_cat',
			'field'            => 'slug',
			'terms'            => $term_slug,
			'include_children' => true,
		) );
	}
	$out = array();
	foreach ( get_posts( $q ) as $id ) {
		$p = wc_get_product( $id );
		if ( $p ) {
			$out[] = $p;
		}
	}
	return $out;
}
