<?php
/**
 * LuwiPress Onyx — Elementor-ready theme bootstrap.
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
$lwp_onyx_theme_obj = function_exists( 'wp_get_theme' ) ? wp_get_theme() : null;
define(
	'LUWIPRESS_ONYX_VERSION',
	( $lwp_onyx_theme_obj && $lwp_onyx_theme_obj->get( 'Version' ) )
		? (string) $lwp_onyx_theme_obj->get( 'Version' )
		: '1.0.0'
);
unset( $lwp_onyx_theme_obj );
define( 'LUWIPRESS_ONYX_DIR', get_template_directory() );
define( 'LUWIPRESS_ONYX_URI', get_template_directory_uri() );

require_once LUWIPRESS_ONYX_DIR . '/inc/setup.php';
require_once LUWIPRESS_ONYX_DIR . '/inc/template-helpers.php';
require_once LUWIPRESS_ONYX_DIR . '/inc/contact-form.php';
require_once LUWIPRESS_ONYX_DIR . '/inc/enqueue.php';
require_once LUWIPRESS_ONYX_DIR . '/inc/elementor-compat.php';
require_once LUWIPRESS_ONYX_DIR . '/inc/elementor-kit-sync.php';

// Maintenance tool classes (1.7.0+) — registered into the LuwiPress Theme
// Bridge by inc/luwipress-bridge.php below. Loaded first so the class_exists
// guards in the bridge filters always find them.
require_once LUWIPRESS_ONYX_DIR . '/inc/maintenance/class-elementor-shell-tool.php';
require_once LUWIPRESS_ONYX_DIR . '/inc/maintenance/class-maintenance-tools.php';
require_once LUWIPRESS_ONYX_DIR . '/inc/maintenance/class-fix-tools.php';
require_once LUWIPRESS_ONYX_DIR . '/inc/maintenance/class-extra-audit-tools.php';
require_once LUWIPRESS_ONYX_DIR . '/inc/maintenance/class-seo-audit-tools.php';
require_once LUWIPRESS_ONYX_DIR . '/inc/maintenance/class-language-drift-tool.php';
require_once LUWIPRESS_ONYX_DIR . '/inc/maintenance/elementor-template-force.php';
require_once LUWIPRESS_ONYX_DIR . '/inc/maintenance/seo-enforcement.php';

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
		$themes[ $slug ]['screenshot'][0] = add_query_arg( 'ver', LUWIPRESS_ONYX_VERSION, $url );
	}
	return $themes;
} );

// LuwiPress bridge — single point of contact for the AI engine, chat,
// plugin detector, and ecosystem helpers. Loaded unconditionally so the
// admin notice can warn even when LuwiPress is missing.
require_once LUWIPRESS_ONYX_DIR . '/inc/luwipress-bridge.php';

// Featured Products registry — per-product flag, admin meta box, admin-bar
// toggle on single-product pages, REST endpoint, helper API. Consumed by
// the mega menu Featured panel and the featured-product / featured-strip
// widgets (and any custom code reading lwp_onyx_get_featured_ids()).
require_once LUWIPRESS_ONYX_DIR . '/inc/featured-products.php';

// Mega menu admin UI — adds the per-menu-item "Featured product (override)"
// field to Appearance → Menus. Loaded unconditionally; admin-only hooks
// internally so the field only renders when admin is editing menus.
require_once LUWIPRESS_ONYX_DIR . '/inc/mega-menu-admin.php';

// Friendly-plugin glue — consolidates Rank Math / Yoast / WPML / Polylang /
// WooCommerce-side hooks that amplify each plugin's presence on the
// storefront. Self-gates internally; safe to load when nothing is detected.
require_once LUWIPRESS_ONYX_DIR . '/inc/friendly-plugins.php';

// AI surface — search suggestions, sticky chat widget shell, KG-related
// rail. Hard-deps on LuwiPress; the bridge file emits the admin notice.
require_once LUWIPRESS_ONYX_DIR . '/inc/ai-surface.php';

if ( class_exists( 'WooCommerce' ) ) {
	require_once LUWIPRESS_ONYX_DIR . '/inc/wc-pdp-hooks.php';
	// Vanilla-JS PDP gallery — replaces WC's Flexslider stack. Immune to
	// LiteSpeed JS Defer/Delay because our script ships with `data-no-defer`
	// + `data-no-optimize` and runs at DOMContentLoaded. Gallery is rendered
	// fully server-side so the first image is visible BEFORE any JS runs.
	require_once LUWIPRESS_ONYX_DIR . '/inc/wc-pdp-gallery-override.php';
	// Parent-term archive enrichment (atelier note + featured product band).
	// Self-gates to product_cat parent terms; safe under WC-only loads.
	require_once LUWIPRESS_ONYX_DIR . '/inc/archive-parent-enrichment.php';
	// Smart filter sidebar — price + attribute layered nav + tags + on-sale
	// / in-stock toggles. Replaces the legacy "Categories" fallback list.
	require_once LUWIPRESS_ONYX_DIR . '/inc/smart-filters.php';
	// Cart/checkout/my-account fallback when the page was built with
	// Elementor Pro WC widgets but Pro is not installed. Detects the empty
	// Pro-widget skeleton in `the_content` and swaps in the matching WC
	// shortcode so the theme's WC template overrides take over.
	require_once LUWIPRESS_ONYX_DIR . '/inc/wc-page-fallback.php';
}

// Blog page auto-fallback — promotes a "Blog/Journal/News" page to WP
// "Posts page" when none is set, and injects a recent-posts grid into
// any empty blog-style page so an unconfigured BLOG menu link never
// renders as an empty chrome.
require_once LUWIPRESS_ONYX_DIR . '/inc/blog-page-fallback.php';

// Slug-collision redirects — 301 legacy /<hub>/ pages to their matching
// /product-category/<hub>/ archives. Operator opts in via Customizer
// (LuwiPress Onyx → Performance → "Resolve page/category slug conflicts");
// when off the module is inert. Auto-discovers conflicts via DB scan,
// caches in a transient busted on page/term saves. Generic — no
// hardcoded slugs.
require_once LUWIPRESS_ONYX_DIR . '/inc/template-redirects.php';

// Cross-template layout safety net — sticky-footer color leak fix,
// min-viewport-height enforcement, archive hero image fit, mobile
// breadcrumb spacing, tag pill safeguard, PDP gallery↔tabs spacing.
require_once LUWIPRESS_ONYX_DIR . '/inc/layout-fixes.php';

// Footer enhancements — Customizer panel for social URLs, newsletter
// signup, trust signals, payment row + render helpers + inline styles.
// footer.php calls the helpers directly. Loaded unconditionally so the
// Customizer panel is always reachable.
require_once LUWIPRESS_ONYX_DIR . '/inc/footer-enhancements.php';

// Mega menu Customizer panel — global defaults for menu choice,
// threshold, columns, counts, mobile mode, blog auto-inject.
require_once LUWIPRESS_ONYX_DIR . '/inc/mega-menu-customizer.php';

// (The Gold-inherited server-side page loader is intentionally NOT loaded
// for Onyx — the design has no loader overlay; onyx.js handles scroll reveal.)

// Generic archive Load More / Infinite Scroll — drives the Vendor + Event CPT
// archives and blog/category/tag archives (SEO-safe progressive enhancement
// over the real paginated links). WC shop archive has its own setup.
require_once LUWIPRESS_ONYX_DIR . '/inc/loadmore.php';

// Engine-CPT template routing — gives operator-defined CPT Engine types
// (Team, Events, …) a styled single + archive (generic Vendor chrome) instead
// of the bare blog fallback. A type-specific template still wins.
require_once LUWIPRESS_ONYX_DIR . '/inc/engine-cpt-templates.php';

// Onboarding wizard — admin-only.
if ( is_admin() ) {
	require_once LUWIPRESS_ONYX_DIR . '/inc/wizard/bootstrap.php';
	require_once LUWIPRESS_ONYX_DIR . '/inc/ecosystem-dashboard.php';
	require_once LUWIPRESS_ONYX_DIR . '/inc/migration.php';
}

// Custom Elementor widgets — registered on `elementor/init`.
require_once LUWIPRESS_ONYX_DIR . '/inc/widgets/loader.php';

// Customizer panel — Brand / Topbar / Header / Footer / Animation / Performance.
require_once LUWIPRESS_ONYX_DIR . '/inc/customizer/bootstrap.php';

// Mobile card width override — emits a guaranteed-cascade-winner inline
// `<style>` block at `wp_head` p999 so the products / blog / single body
// cards land at uniform 90% width on mobile, regardless of pre-V32 Kit CSS
// baseline rules that previously forced `padding-left: 20px !important` on
// `ul.products`. See `feedback_mobile_card_width_override_via_p999.md`.
require_once LUWIPRESS_ONYX_DIR . '/inc/mobile-card-width-override.php';
