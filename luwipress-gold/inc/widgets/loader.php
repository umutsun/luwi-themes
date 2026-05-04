<?php
/**
 * Elementor widget loader.
 *
 * Registers a "LuwiPress Gold" widget category and 6 custom widgets:
 *   - lwp-product-card     → Gold-styled product card with sale badge + quick-add
 *   - lwp-master-profile   → Luthier portrait + stats + dual CTA card
 *   - lwp-timeline         → Year + headline + body rows for the dark-band history
 *   - lwp-info-bar         → V32-style 4-column trust strip with red icon circles
 *   - lwp-editorial-grid   → 1-large + 2-small blog post grid
 *   - lwp-hero             → Hero with 3 layout variants (split / centered / full-bleed)
 *
 * Boots only when Elementor is loaded.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Bail until Elementor is fully loaded.
add_action( 'elementor/init', function () {

	// 1. Custom category — keeps our widgets grouped at the top of the panel.
	add_action( 'elementor/elements/categories_registered', function ( $elements_manager ) {
		$elements_manager->add_category(
			'luwipress-gold',
			[
				'title' => __( 'LuwiPress Gold', 'luwipress-gold' ),
				'icon'  => 'eicon-star',
			]
		);
	} );

	// 2. Register widgets after Elementor's widget classes are loaded.
	add_action( 'elementor/widgets/register', function ( $widgets_manager ) {
		$widgets = [
			'class-product-card.php'   => 'LuwiPress_Gold_Widget_Product_Card',
			'class-master-profile.php' => 'LuwiPress_Gold_Widget_Master_Profile',
			'class-timeline.php'       => 'LuwiPress_Gold_Widget_Timeline',
			'class-info-bar.php'       => 'LuwiPress_Gold_Widget_Info_Bar',
			'class-editorial-grid.php' => 'LuwiPress_Gold_Widget_Editorial_Grid',
			'class-hero.php'           => 'LuwiPress_Gold_Widget_Hero',
			'class-mega-menu.php'      => 'LuwiPress_Gold_Widget_Mega_Menu',
			'class-megabar.php'        => 'LuwiPress_Gold_Widget_Megabar',
		];

		foreach ( $widgets as $file => $class ) {
			$path = LUWIPRESS_GOLD_DIR . '/inc/widgets/lib/' . $file;
			if ( ! file_exists( $path ) ) continue;
			require_once $path;
			if ( class_exists( $class ) ) {
				$widgets_manager->register( new $class() );
			}
		}
	} );

	// 3. Load widget CSS only on pages that use them.
	add_action( 'elementor/frontend/after_register_styles', function () {
		wp_register_style(
			'luwipress-gold-widgets',
			LUWIPRESS_GOLD_URI . '/assets/css/widgets.css',
			[ 'luwipress-gold-tokens' ],
			LUWIPRESS_GOLD_VERSION
		);
	} );
} );
