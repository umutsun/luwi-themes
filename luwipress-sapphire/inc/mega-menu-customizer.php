<?php
/**
 * Mega menu Customizer panel.
 *
 * Surfaces the global mega menu controls in Customize → LuwiPress Sapphire
 * → Mega Menu, so the operator does not have to open the Elementor
 * header template each time they want to flip a setting. The widget
 * render code (`LuwiPress_Sapphire_Widget_Mega_Menu`) reads these
 * theme_mods as fallback when the widget instance has no explicit
 * value, making the Customizer the single global source of truth.
 *
 * Settings:
 *   - menu                Which WP nav menu drives the mega bar.
 *   - threshold           # of children needed for a top item to
 *                         render as a mega panel (vs simple dropdown).
 *   - columns             Mega panel column count (auto / 2 / 3 / 4).
 *   - show_counts         Append product/category counts to items.
 *   - mobile_mode         Off-canvas drawer vs in-place accordion.
 *   - auto_inject_blog    Auto-inject post categories under the BLOG
 *                         top item when that item has no manual children.
 *
 * Generic — no slug or store-specific logic. Operators of any LuwiPress
 * Gold install benefit from the same control surface.
 *
 * @package luwipress-sapphire
 * @since   1.6.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'luwipress_sapphire_mega_menu_customizer' ) ) {

	function luwipress_sapphire_mega_menu_customizer( $wp_customize ) {

		$panel_id = 'luwipress_sapphire';
		if ( ! $wp_customize->get_panel( $panel_id ) ) {
			$wp_customize->add_panel( $panel_id, [
				'title'    => __( 'LuwiPress Sapphire', 'luwipress-sapphire' ),
				'priority' => 10,
			] );
		}

		$section_id = 'luwipress_sapphire_mega_menu';
		$wp_customize->add_section( $section_id, [
			'title'       => __( 'Mega menu', 'luwipress-sapphire' ),
			'panel'       => $panel_id,
			'priority'    => 25,
			'description' => __( 'These settings act as defaults — the per-instance Elementor widget options always win when set.', 'luwipress-sapphire' ),
		] );

		// Menu picker.
		$menu_choices = [ '' => __( '— Pick a menu —', 'luwipress-sapphire' ) ];
		foreach ( wp_get_nav_menus() as $m ) {
			$menu_choices[ (string) $m->term_id ] = $m->name;
		}
		$wp_customize->add_setting( 'luwipress_sapphire_mega_menu_id', [
			'type'              => 'theme_mod',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		] );
		$wp_customize->add_control( 'luwipress_sapphire_mega_menu_id', [
			'label'       => __( 'Menu', 'luwipress-sapphire' ),
			'description' => __( 'Build the menu in Görünüm → Menüler, then pick it here. Used as the default for header mega menu widgets that have no explicit menu set.', 'luwipress-sapphire' ),
			'section'     => $section_id,
			'type'        => 'select',
			'choices'     => $menu_choices,
			'priority'    => 10,
		] );

		// Threshold.
		$wp_customize->add_setting( 'luwipress_sapphire_mega_threshold', [
			'type'              => 'theme_mod',
			'default'           => 4,
			'sanitize_callback' => function ( $v ) { return max( 2, min( 12, (int) $v ) ); },
			'transport'         => 'refresh',
		] );
		$wp_customize->add_control( 'luwipress_sapphire_mega_threshold', [
			'label'       => __( 'Mega panel threshold', 'luwipress-sapphire' ),
			'description' => __( 'A top-level item becomes a multi-column mega panel when it has at least this many children, OR when any of its children has its own children. Default: 4.', 'luwipress-sapphire' ),
			'section'     => $section_id,
			'type'        => 'number',
			'input_attrs' => [ 'min' => 2, 'max' => 12, 'step' => 1 ],
			'priority'    => 20,
		] );

		// Columns.
		$wp_customize->add_setting( 'luwipress_sapphire_mega_columns', [
			'type'              => 'theme_mod',
			'default'           => 'auto',
			'sanitize_callback' => function ( $v ) {
				$v = (string) $v;
				return in_array( $v, [ 'auto', '2', '3', '4' ], true ) ? $v : 'auto';
			},
			'transport'         => 'refresh',
		] );
		$wp_customize->add_control( 'luwipress_sapphire_mega_columns', [
			'label'    => __( 'Mega panel columns', 'luwipress-sapphire' ),
			'section'  => $section_id,
			'type'     => 'select',
			'choices'  => [
				'auto' => __( 'Auto (2 or 3 by item count)', 'luwipress-sapphire' ),
				'2'    => __( '2 columns', 'luwipress-sapphire' ),
				'3'    => __( '3 columns', 'luwipress-sapphire' ),
				'4'    => __( '4 columns', 'luwipress-sapphire' ),
			],
			'priority' => 30,
		] );

		// Counts.
		$wp_customize->add_setting( 'luwipress_sapphire_mega_show_counts', [
			'type'              => 'theme_mod',
			'default'           => true,
			'sanitize_callback' => function ( $v ) { return ! empty( $v ); },
			'transport'         => 'refresh',
		] );
		$wp_customize->add_control( 'luwipress_sapphire_mega_show_counts', [
			'label'       => __( 'Show item counts', 'luwipress-sapphire' ),
			'description' => __( 'Show "(47)"-style product / page counts next to category-style menu items.', 'luwipress-sapphire' ),
			'section'     => $section_id,
			'type'        => 'checkbox',
			'priority'    => 40,
		] );

		// Mobile mode.
		$wp_customize->add_setting( 'luwipress_sapphire_mega_mobile_mode', [
			'type'              => 'theme_mod',
			'default'           => 'drawer',
			'sanitize_callback' => function ( $v ) {
				return in_array( (string) $v, [ 'drawer', 'accordion' ], true ) ? (string) $v : 'drawer';
			},
			'transport'         => 'refresh',
		] );
		$wp_customize->add_control( 'luwipress_sapphire_mega_mobile_mode', [
			'label'    => __( 'Mobile mode', 'luwipress-sapphire' ),
			'section'  => $section_id,
			'type'     => 'select',
			'choices'  => [
				'drawer'    => __( 'Off-canvas drawer (hamburger)', 'luwipress-sapphire' ),
				'accordion' => __( 'In-place accordion', 'luwipress-sapphire' ),
			],
			'priority' => 50,
		] );

		// Auto-inject blog categories on Blog top item.
		$wp_customize->add_setting( 'luwipress_sapphire_mega_auto_inject_blog', [
			'type'              => 'theme_mod',
			'default'           => true,
			'sanitize_callback' => function ( $v ) { return ! empty( $v ); },
			'transport'         => 'refresh',
		] );
		$wp_customize->add_control( 'luwipress_sapphire_mega_auto_inject_blog', [
			'label'       => __( 'Auto-fill BLOG menu with post categories', 'luwipress-sapphire' ),
			'description' => __( 'When the BLOG / Journal / News top-level menu item has no manual children, automatically fill it with every published post category (excluding Uncategorized). Uncheck to leave manual control.', 'luwipress-sapphire' ),
			'section'     => $section_id,
			'type'        => 'checkbox',
			'priority'    => 60,
		] );
	}
	add_action( 'customize_register', 'luwipress_sapphire_mega_menu_customizer', 30 );
}
