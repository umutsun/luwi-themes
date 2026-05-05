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

define( 'LUWIPRESS_GOLD_VERSION', '1.5.0' );
define( 'LUWIPRESS_GOLD_DIR', get_template_directory() );
define( 'LUWIPRESS_GOLD_URI', get_template_directory_uri() );

require_once LUWIPRESS_GOLD_DIR . '/inc/setup.php';
require_once LUWIPRESS_GOLD_DIR . '/inc/enqueue.php';
require_once LUWIPRESS_GOLD_DIR . '/inc/elementor-compat.php';
require_once LUWIPRESS_GOLD_DIR . '/inc/elementor-kit-sync.php';

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
}

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
