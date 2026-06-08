<?php
/**
 * LuwiPress Emerald — Elementor-ready theme bootstrap.
 *
 * Forks the LuwiPress Gold backbone end-to-end (wizard, theme bridge,
 * maintenance tools, ecosystem dashboard, AI surface, featured-products
 * registry, mega-menu admin, footer enhancements, page loader, smart
 * filters, slug-collision shim, layout fixes, WC fallbacks, custom
 * Elementor widgets) and re-skins the visual layer with the
 * Acme/Emerald design system: Inter + JetBrains Mono · slate neutrals
 * + emerald jewel green · 1280px container · 12-step spacing scale.
 *
 * Tuned for B2B consulting / agencies / knowledge work rather than
 * catalog-heavy retail. Gold remains the music/craft-retail theme.
 *
 * Module loading mirrors Gold's load order exactly — every behaviour
 * Gold customers rely on (onboarding wizard, 23-tool maintenance
 * suite, Featured Products UI, AI search modal, sticky chat shell,
 * KG-related rail, page loader, footer Customizer, mega-menu admin
 * UI, server-side slug collision shim) is available to Emerald
 * customers out of the box.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Version read dynamically from style.css so a single bump propagates
 * to every cache-buster query string without a parallel constant edit.
 */
$lwp_emerald_theme_obj = function_exists( 'wp_get_theme' ) ? wp_get_theme() : null;
define(
	'LUWIPRESS_EMERALD_VERSION',
	( $lwp_emerald_theme_obj && $lwp_emerald_theme_obj->get( 'Version' ) )
		? (string) $lwp_emerald_theme_obj->get( 'Version' )
		: '1.0.0'
);
unset( $lwp_emerald_theme_obj );
define( 'LUWIPRESS_EMERALD_DIR', get_template_directory() );
define( 'LUWIPRESS_EMERALD_URI', get_template_directory_uri() );

require_once LUWIPRESS_EMERALD_DIR . '/inc/setup.php';
require_once LUWIPRESS_EMERALD_DIR . '/inc/enqueue.php';
require_once LUWIPRESS_EMERALD_DIR . '/inc/elementor-compat.php';
require_once LUWIPRESS_EMERALD_DIR . '/inc/elementor-kit-sync.php';

// Maintenance tool classes — registered into the LuwiPress Theme Bridge
// by inc/luwipress-bridge.php below. Loaded first so the class_exists
// guards in the bridge filters always find them.
require_once LUWIPRESS_EMERALD_DIR . '/inc/maintenance/class-elementor-shell-tool.php';
require_once LUWIPRESS_EMERALD_DIR . '/inc/maintenance/class-maintenance-tools.php';
require_once LUWIPRESS_EMERALD_DIR . '/inc/maintenance/class-fix-tools.php';
require_once LUWIPRESS_EMERALD_DIR . '/inc/maintenance/class-extra-audit-tools.php';
require_once LUWIPRESS_EMERALD_DIR . '/inc/maintenance/class-seo-audit-tools.php';
require_once LUWIPRESS_EMERALD_DIR . '/inc/maintenance/class-language-drift-tool.php';
require_once LUWIPRESS_EMERALD_DIR . '/inc/maintenance/elementor-template-force.php';
require_once LUWIPRESS_EMERALD_DIR . '/inc/maintenance/seo-enforcement.php';

/**
 * Append theme version as a query string to the screenshot URL in
 * Appearance → Themes → Theme Details so operators see fresh art after
 * a bump (WP fetches screenshot.png without a cache-buster otherwise).
 */
add_filter( 'wp_prepare_themes_for_js', function ( $themes ) {
	$slug = get_template();
	if ( isset( $themes[ $slug ]['screenshot'][0] ) ) {
		$url = $themes[ $slug ]['screenshot'][0];
		$themes[ $slug ]['screenshot'][0] = add_query_arg( 'ver', LUWIPRESS_EMERALD_VERSION, $url );
	}
	return $themes;
} );

// LuwiPress bridge — single point of contact for the AI engine, chat,
// plugin detector, ecosystem helpers, Theme Bridge tool registry. Loaded
// unconditionally so the admin notice can warn even when LuwiPress is
// missing.
require_once LUWIPRESS_EMERALD_DIR . '/inc/luwipress-bridge.php';

// Featured Products registry — per-product flag, admin meta box,
// admin-bar toggle on single-product pages, REST endpoint, helper API.
// Consumed by the mega menu Featured panel and featured-product widgets.
require_once LUWIPRESS_EMERALD_DIR . '/inc/featured-products.php';

// Mega menu admin UI — per-menu-item "Featured product (override)"
// field on Appearance → Menus.
require_once LUWIPRESS_EMERALD_DIR . '/inc/mega-menu-admin.php';

// Friendly-plugin glue — Rank Math / Yoast / WPML / Polylang / WC hook
// amplifiers. Self-gates; safe to load when nothing is detected.
require_once LUWIPRESS_EMERALD_DIR . '/inc/friendly-plugins.php';

// AI surface — search modal, sticky chat shell, KG-related rail. Hard-
// deps on LuwiPress core; the bridge emits an admin notice if missing.
require_once LUWIPRESS_EMERALD_DIR . '/inc/ai-surface.php';

if ( class_exists( 'WooCommerce' ) ) {
	require_once LUWIPRESS_EMERALD_DIR . '/inc/wc-pdp-hooks.php';
	// Vanilla-JS PDP gallery — replaces WC's Flexslider stack.
	require_once LUWIPRESS_EMERALD_DIR . '/inc/wc-pdp-gallery-override.php';
	// Parent-term archive enrichment (atelier note + featured product band).
	require_once LUWIPRESS_EMERALD_DIR . '/inc/archive-parent-enrichment.php';
	// Smart filter sidebar — price + attribute layered nav.
	require_once LUWIPRESS_EMERALD_DIR . '/inc/smart-filters.php';
	// Cart/checkout/my-account fallback when Elementor Pro WC widgets are
	// in the page but Pro is not installed.
	require_once LUWIPRESS_EMERALD_DIR . '/inc/wc-page-fallback.php';
}

// Blog page auto-fallback — promotes a Blog/Journal page to WP "Posts
// page" when none is set, and injects a recent-posts grid into empty
// blog-style pages.
require_once LUWIPRESS_EMERALD_DIR . '/inc/blog-page-fallback.php';

// Slug-collision redirects — 301 legacy /<hub>/ pages to their matching
// /product-category/<hub>/ archives. Operator opts in via Customizer.
// When core LuwiPress 3.1.56+ is active, the theme tier early-returns
// in favor of the core LuwiPress_Slug_Resolver.
require_once LUWIPRESS_EMERALD_DIR . '/inc/template-redirects.php';

// Cross-template layout safety net.
require_once LUWIPRESS_EMERALD_DIR . '/inc/layout-fixes.php';

// Footer enhancements — Customizer panel + render helpers (social URLs,
// newsletter signup, trust signals, payment row). footer.php calls
// helpers directly.
require_once LUWIPRESS_EMERALD_DIR . '/inc/footer-enhancements.php';

// Mega menu Customizer panel — defaults for menu choice, threshold,
// columns, counts, mobile mode, blog auto-inject.
require_once LUWIPRESS_EMERALD_DIR . '/inc/mega-menu-customizer.php';

// Server-side page loader — eliminates flash-of-content-before-loader.
require_once LUWIPRESS_EMERALD_DIR . '/inc/page-loader.php';

// Onboarding wizard + admin dashboards — admin-only.
if ( is_admin() ) {
	require_once LUWIPRESS_EMERALD_DIR . '/inc/wizard/bootstrap.php';
	require_once LUWIPRESS_EMERALD_DIR . '/inc/ecosystem-dashboard.php';
	require_once LUWIPRESS_EMERALD_DIR . '/inc/migration.php';
}

// Custom Elementor widgets — registered on `elementor/init`.
require_once LUWIPRESS_EMERALD_DIR . '/inc/widgets/loader.php';

// Customizer panel — Brand / Topbar / Header / Footer / Animation /
// Performance. Owns the user-overrideable --primary color.
require_once LUWIPRESS_EMERALD_DIR . '/inc/customizer/bootstrap.php';

// Mobile card width override — guaranteed-cascade-winner inline style
// at wp_head p999 for the 90vw mobile rule.
require_once LUWIPRESS_EMERALD_DIR . '/inc/mobile-card-width-override.php';

/**
 * Mark the theme as Elementor + Pro friendly so Theme Builder
 * templates take over for Single / Archive / Header / Footer when
 * the operator wires them up.
 */
add_action( 'after_setup_theme', function () {
	add_theme_support( 'elementor-pro' );
	add_theme_support( 'elementor' );
}, 11 );

/**
 * WooCommerce mini-cart fragment — keeps `span[data-cart-count]` in
 * sync with the cart over AJAX so the header bubble + drawer header
 * update without a page reload.
 */
if ( class_exists( 'WooCommerce' ) ) {
	add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {
		$count = WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
		ob_start();
		if ( $count > 0 ) {
			echo '<span class="emerald-cart-count" data-cart-count>' . esc_html( (string) $count ) . '</span>';
		} else {
			echo '<span class="emerald-cart-count" data-cart-count style="display:none">0</span>';
		}
		$fragments['span[data-cart-count]'] = ob_get_clean();
		return $fragments;
	} );
}
