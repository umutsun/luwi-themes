<?php
/**
 * Elementor Kit auto-sync.
 *
 * Forces Elementor's Site Settings (Global Colors + Global Fonts +
 * container width) to match the LuwiPress Amber token palette so the
 * compiled JSON kit's `--e-global-color-*` references resolve to gold,
 * not Elementor's default blue.
 *
 * Triggers
 * --------
 * 1. after_switch_theme  → fires once when the operator activates the theme
 * 2. wizard apply step   → re-runs the sync after the wizard's brand override
 *                          (so the operator's Customizer choices win)
 * 3. manual hook         → operators can call luwipress_amber_sync_kit() from
 *                          a snippet plugin if they want to re-sync after
 *                          editing colors elsewhere
 *
 * Implementation
 * --------------
 * Uses Elementor's own Kit Manager API where possible
 * (`\Elementor\Plugin::$instance->kits_manager`). Falls back to a direct
 * post_meta write when the API is unavailable. After the write, schedules
 * a single Elementor CSS regen pass and a LiteSpeed-compatible cache purge.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Public entry point — call this anywhere to re-sync.
 *
 * @param array $overrides Optional. Override the default Gold palette.
 *                         Keys: primary, secondary, text, accent, font_serif, font_sans, font_mono.
 * @return bool|WP_Error true on success, WP_Error otherwise.
 */
function luwipress_amber_sync_kit( $overrides = [] ) {
	if ( ! did_action( 'elementor/loaded' ) ) {
		return new WP_Error( 'elementor_missing', __( 'Elementor is not active — kit sync skipped.', 'luwipress-amber' ) );
	}

	$defaults = luwipress_amber_kit_default_palette();
	$args = wp_parse_args( $overrides, $defaults );

	// Allow Customizer overrides to take precedence — read at sync time.
	$args['primary']   = get_theme_mod( 'luwipress_amber_color_primary',       $args['primary'] );
	$args['secondary'] = get_theme_mod( 'luwipress_amber_color_primary_light', $args['secondary'] );
	$args['text']      = get_theme_mod( 'luwipress_amber_color_ink',           $args['text'] );
	$args['accent']    = get_theme_mod( 'luwipress_amber_color_sale',          $args['accent'] );

	$kit_id = luwipress_amber_get_active_kit_id();
	if ( ! $kit_id ) {
		return new WP_Error( 'no_kit', __( 'No active Elementor kit found.', 'luwipress-amber' ) );
	}

	$settings = luwipress_amber_compose_kit_settings( $args );

	// Path 1: Elementor's own Kit::update_settings — preferred.
	$wrote = luwipress_amber_write_kit_via_api( $kit_id, $settings );

	// Path 2: direct post_meta fallback.
	if ( ! $wrote ) {
		luwipress_amber_write_kit_via_meta( $kit_id, $settings );
	}

	// Trigger CSS regen.
	luwipress_amber_kit_regen_css( $kit_id );

	// Mark sync.
	update_option( 'luwipress_amber_kit_synced_at', time(), false );

	return true;
}

/**
 * Default Gold palette — used when no overrides + no Customizer mods set.
 */
function luwipress_amber_kit_default_palette() {
	return [
		'primary'     => '#735c00',  // Gold
		'secondary'   => '#D4AF37',  // Gold bright
		'text'        => '#1b1c1c',  // Ink
		'accent'      => '#a33b3e',  // Sale red

		'font_serif'  => 'Playfair Display',
		'font_sans'   => 'Inter',
		'font_mono'   => 'JetBrains Mono',

		'container_w' => 1372,
	];
}

/**
 * Get the active Kit post ID — Elementor stores it in `elementor_active_kit`.
 */
function luwipress_amber_get_active_kit_id() {
	$id = (int) get_option( 'elementor_active_kit' );
	if ( $id > 0 && get_post_status( $id ) ) return $id;

	if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->kits_manager ) ) {
		try {
			$mgr = \Elementor\Plugin::$instance->kits_manager;
			if ( method_exists( $mgr, 'get_active_id' ) ) {
				$mgr_id = (int) $mgr->get_active_id();
				if ( $mgr_id > 0 ) return $mgr_id;
			}
		} catch ( \Throwable $e ) {
			// fall through
		}
	}
	return 0;
}

/**
 * Compose the Elementor Kit settings array — matches the shape Elementor's
 * Kit class expects (system_colors / custom_colors / system_typography /
 * default_generic_fonts / container_width).
 */
function luwipress_amber_compose_kit_settings( $args ) {
	return [
		'system_colors' => [
			[ '_id' => 'primary',   'title' => 'Primary',   'color' => $args['primary'] ],
			[ '_id' => 'secondary', 'title' => 'Secondary', 'color' => $args['secondary'] ],
			[ '_id' => 'text',      'title' => 'Text',      'color' => $args['text'] ],
			[ '_id' => 'accent',    'title' => 'Accent',    'color' => $args['accent'] ],
		],
		'custom_colors' => [
			[ '_id' => 'lwp_amber_bright', 'title' => 'Gold Bright', 'color' => $args['secondary'] ],
			[ '_id' => 'lwp_sale',        'title' => 'Sale Red',    'color' => $args['accent'] ],
			[ '_id' => 'lwp_icon_red',    'title' => 'Icon Red',    'color' => '#d83131' ],
			[ '_id' => 'lwp_black',       'title' => 'Deep Black',  'color' => '#0c0c0c' ],
			[ '_id' => 'lwp_cream',       'title' => 'Cream',       'color' => '#fcf9f8' ],
			[ '_id' => 'lwp_ink_soft',    'title' => 'Ink Soft',    'color' => '#4d4635' ],
			[ '_id' => 'lwp_line',        'title' => 'Line',        'color' => '#e8e2d3' ],
		],
		'system_typography' => [
			[
				'_id' => 'primary', 'title' => 'Primary',
				'typography_typography' => 'custom',
				'typography_font_family' => $args['font_serif'],
				'typography_font_weight' => '500',
			],
			[
				'_id' => 'secondary', 'title' => 'Secondary',
				'typography_typography' => 'custom',
				'typography_font_family' => $args['font_serif'],
				'typography_font_weight' => '400',
			],
			[
				'_id' => 'text', 'title' => 'Text',
				'typography_typography' => 'custom',
				'typography_font_family' => $args['font_sans'],
				'typography_font_weight' => '400',
			],
			[
				'_id' => 'accent', 'title' => 'Accent',
				'typography_typography' => 'custom',
				'typography_font_family' => $args['font_mono'],
				'typography_font_weight' => '500',
			],
		],
		'custom_typography' => [],
		'default_generic_fonts' => 'sans-serif',
		'container_width' => [
			'unit' => 'px',
			'size' => (int) $args['container_w'],
		],
		'space_between_widgets' => [
			'unit' => 'px',
			'size' => 20,
		],
		'site_name' => get_bloginfo( 'name' ),
	];
}

/**
 * Write through Elementor's own Kit class — handles serialization correctly.
 *
 * @return bool true if written via API, false if API unavailable.
 */
function luwipress_amber_write_kit_via_api( $kit_id, $settings ) {
	if ( ! class_exists( '\Elementor\Plugin' ) ) return false;

	try {
		$plugin = \Elementor\Plugin::$instance;
		// Elementor's Documents Manager → get the Kit document
		if ( ! isset( $plugin->documents ) || ! method_exists( $plugin->documents, 'get' ) ) return false;
		$doc = $plugin->documents->get( $kit_id );
		if ( ! $doc ) return false;

		// Kit document has save() that handles validation + serialization.
		if ( method_exists( $doc, 'save' ) ) {
			$doc->save( [
				'settings' => $settings,
			] );
			return true;
		}

		// Older Elementor: update_meta directly
		if ( method_exists( $doc, 'update_meta' ) ) {
			$doc->update_meta( '_elementor_page_settings', $settings );
			return true;
		}
	} catch ( \Throwable $e ) {
		// fall through to fallback
	}
	return false;
}

/**
 * Fallback — direct update_post_meta. WP auto-serializes the array.
 */
function luwipress_amber_write_kit_via_meta( $kit_id, $settings ) {
	$existing = get_post_meta( $kit_id, '_elementor_page_settings', true );
	if ( is_array( $existing ) ) {
		// Merge so we don't blow away custom settings the operator may have set.
		$settings = array_replace( $existing, $settings );
	}
	update_post_meta( $kit_id, '_elementor_page_settings', $settings );

	// Verify and retry via $wpdb if cache layer ate the write.
	wp_cache_delete( $kit_id, 'post_meta' );
	$readback = get_post_meta( $kit_id, '_elementor_page_settings', true );
	if ( ! is_array( $readback ) || empty( $readback['system_colors'] ) ) {
		global $wpdb;
		$wpdb->replace( $wpdb->postmeta, [
			'post_id'    => $kit_id,
			'meta_key'   => '_elementor_page_settings',
			'meta_value' => maybe_serialize( $settings ),
		] );
		wp_cache_delete( $kit_id, 'post_meta' );
	}
}

/**
 * Regenerate Elementor's CSS for the kit + flush LiteSpeed.
 */
function luwipress_amber_kit_regen_css( $kit_id ) {
	if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
		try {
			$css = new \Elementor\Core\Files\CSS\Post( $kit_id );
			$css->update();
		} catch ( \Throwable $e ) {
			// silent
		}
	}
	// Also re-render every page CSS — the Kit's tokens cascade into them.
	if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
		try {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		} catch ( \Throwable $e ) {
			// silent
		}
	}
	// LiteSpeed
	if ( class_exists( '\LiteSpeed\Purge' ) ) {
		try { \LiteSpeed\Purge::purge_all(); } catch ( \Throwable $e ) {}
	}
}

/* -----------------------------------------------------------------
 * Hook registrations
 * --------------------------------------------------------------- */

// 1. Theme activation — only on the actual switch event, not every page load.
add_action( 'after_switch_theme', function () {
	// Defer until after Elementor has finished loading on the next admin
	// request. This keeps activation fast + avoids a fatal if Elementor
	// is still initialising during the switch.
	update_option( 'luwipress_amber_kit_sync_pending', 1, false );
} );

// 2. Pending sync — runs once on the first admin_init after activation.
add_action( 'admin_init', function () {
	if ( ! get_option( 'luwipress_amber_kit_sync_pending' ) ) return;
	if ( ! did_action( 'elementor/loaded' ) ) return;
	luwipress_amber_sync_kit();
	delete_option( 'luwipress_amber_kit_sync_pending' );
}, 99 );

// 3. Customizer save — re-sync when brand colors change.
add_action( 'customize_save_after', function () {
	if ( did_action( 'elementor/loaded' ) ) {
		luwipress_amber_sync_kit();
	}
} );
