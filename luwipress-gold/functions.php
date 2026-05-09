<?php
/**
 * LuwiPress Gold — Elementor-ready theme bootstrap.
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
$lwp_gold_theme_obj = function_exists( 'wp_get_theme' ) ? wp_get_theme() : null;
define(
	'LUWIPRESS_GOLD_VERSION',
	( $lwp_gold_theme_obj && $lwp_gold_theme_obj->get( 'Version' ) )
		? (string) $lwp_gold_theme_obj->get( 'Version' )
		: '1.7.0'
);
unset( $lwp_gold_theme_obj );
define( 'LUWIPRESS_GOLD_DIR', get_template_directory() );
define( 'LUWIPRESS_GOLD_URI', get_template_directory_uri() );

require_once LUWIPRESS_GOLD_DIR . '/inc/setup.php';
require_once LUWIPRESS_GOLD_DIR . '/inc/enqueue.php';
require_once LUWIPRESS_GOLD_DIR . '/inc/elementor-compat.php';
require_once LUWIPRESS_GOLD_DIR . '/inc/elementor-kit-sync.php';

// Maintenance tool classes (1.7.0+) — registered into the LuwiPress Theme
// Bridge by inc/luwipress-bridge.php below. Loaded first so the class_exists
// guards in the bridge filters always find them.
require_once LUWIPRESS_GOLD_DIR . '/inc/maintenance/class-elementor-shell-tool.php';
require_once LUWIPRESS_GOLD_DIR . '/inc/maintenance/class-maintenance-tools.php';
require_once LUWIPRESS_GOLD_DIR . '/inc/maintenance/class-fix-tools.php';
require_once LUWIPRESS_GOLD_DIR . '/inc/maintenance/class-extra-audit-tools.php';
require_once LUWIPRESS_GOLD_DIR . '/inc/maintenance/class-seo-audit-tools.php';
require_once LUWIPRESS_GOLD_DIR . '/inc/maintenance/elementor-template-force.php';
require_once LUWIPRESS_GOLD_DIR . '/inc/maintenance/seo-enforcement.php';

// LuwiPress bridge — single point of contact for the AI engine, chat,
// plugin detector, and ecosystem helpers. Loaded unconditionally so the
// admin notice can warn even when LuwiPress is missing.
require_once LUWIPRESS_GOLD_DIR . '/inc/luwipress-bridge.php';

// Friendly-plugin glue — consolidates Rank Math / Yoast / WPML / Polylang /
// WooCommerce-side hooks that amplify each plugin's presence on the
// storefront. Self-gates internally; safe to load when nothing is detected.
require_once LUWIPRESS_GOLD_DIR . '/inc/friendly-plugins.php';

// AI surface — search suggestions, sticky chat widget shell, KG-related
// rail. Hard-deps on LuwiPress; the bridge file emits the admin notice.
require_once LUWIPRESS_GOLD_DIR . '/inc/ai-surface.php';

if ( class_exists( 'WooCommerce' ) ) {
	require_once LUWIPRESS_GOLD_DIR . '/inc/wc-pdp-hooks.php';
	// Parent-term archive enrichment (atelier note + featured product band).
	// Self-gates to product_cat parent terms; safe under WC-only loads.
	require_once LUWIPRESS_GOLD_DIR . '/inc/archive-parent-enrichment.php';
	// Smart filter sidebar — price + attribute layered nav + tags + on-sale
	// / in-stock toggles. Replaces the legacy "Categories" fallback list.
	require_once LUWIPRESS_GOLD_DIR . '/inc/smart-filters.php';
}

// Blog page auto-fallback — promotes a "Blog/Journal/News" page to WP
// "Posts page" when none is set, and injects a recent-posts grid into
// any empty blog-style page so an unconfigured BLOG menu link never
// renders as an empty chrome.
require_once LUWIPRESS_GOLD_DIR . '/inc/blog-page-fallback.php';

// Slug-collision redirects — 301 legacy /<hub>/ pages to their matching
// /product-category/<hub>/ archives. Operator opts in via Customizer
// (LuwiPress Gold → Performance → "Resolve page/category slug conflicts");
// when off the module is inert. Auto-discovers conflicts via DB scan,
// caches in a transient busted on page/term saves. Generic — no
// hardcoded slugs.
require_once LUWIPRESS_GOLD_DIR . '/inc/template-redirects.php';

// Cross-template layout safety net — sticky-footer color leak fix,
// min-viewport-height enforcement, archive hero image fit, mobile
// breadcrumb spacing, tag pill safeguard, PDP gallery↔tabs spacing.
require_once LUWIPRESS_GOLD_DIR . '/inc/layout-fixes.php';

// Footer enhancements — Customizer panel for social URLs, newsletter
// signup, trust signals, payment row + render helpers + inline styles.
// footer.php calls the helpers directly. Loaded unconditionally so the
// Customizer panel is always reachable.
require_once LUWIPRESS_GOLD_DIR . '/inc/footer-enhancements.php';

// Mega menu Customizer panel — global defaults for menu choice,
// threshold, columns, counts, mobile mode, blog auto-inject.
require_once LUWIPRESS_GOLD_DIR . '/inc/mega-menu-customizer.php';

// Server-side page loader. Renders the overlay into the DOM before the
// first paint to eliminate the flash-of-content-before-loader. Pairs
// with the JS handler in assets/js/frontend.js which removes the boot
// class on window.load.
require_once LUWIPRESS_GOLD_DIR . '/inc/page-loader.php';

// Onboarding wizard — admin-only.
if ( is_admin() ) {
	require_once LUWIPRESS_GOLD_DIR . '/inc/wizard/bootstrap.php';
	require_once LUWIPRESS_GOLD_DIR . '/inc/ecosystem-dashboard.php';
	require_once LUWIPRESS_GOLD_DIR . '/inc/migration.php';
}

// Custom Elementor widgets — registered on `elementor/init`.
require_once LUWIPRESS_GOLD_DIR . '/inc/widgets/loader.php';

// Customizer panel — Brand / Topbar / Header / Footer / Animation / Performance.
require_once LUWIPRESS_GOLD_DIR . '/inc/customizer/bootstrap.php';
