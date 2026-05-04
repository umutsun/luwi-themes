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

define( 'LUWIPRESS_GOLD_VERSION', '1.0.0' );
define( 'LUWIPRESS_GOLD_DIR', get_template_directory() );
define( 'LUWIPRESS_GOLD_URI', get_template_directory_uri() );

require_once LUWIPRESS_GOLD_DIR . '/inc/setup.php';
require_once LUWIPRESS_GOLD_DIR . '/inc/enqueue.php';
require_once LUWIPRESS_GOLD_DIR . '/inc/elementor-compat.php';

// Onboarding wizard — admin-only.
if ( is_admin() ) {
	require_once LUWIPRESS_GOLD_DIR . '/inc/wizard/bootstrap.php';
}

// Custom Elementor widgets — registered on `elementor/init`.
require_once LUWIPRESS_GOLD_DIR . '/inc/widgets/loader.php';
