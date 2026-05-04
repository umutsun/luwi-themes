<?php
/**
 * Elementor compatibility layer.
 *
 * The theme is designed for Elementor (Free or Pro) plus ElementsKit Lite.
 * This file:
 *   1. Surfaces an admin notice when Elementor is missing.
 *   2. Adds a body class so Elementor templates can detect they're inside
 *      LuwiPress Gold and react accordingly.
 *   3. Auto-applies the Elementor Canvas template on pages tagged with the
 *      `_lwp_canvas` post-meta flag (populated by the Kit JSONs).
 *   4. Registers the kit folder location so Elementor's Kit Library can
 *      surface it under "Local kit".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------------------------------------------------------------------------
 * 1. Admin notice when Elementor isn't installed.
 * ------------------------------------------------------------------------- */
add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'install_plugins' ) ) return;
	if ( did_action( 'elementor/loaded' ) ) return;

	$plugin_url = wp_nonce_url(
		self_admin_url( 'update.php?action=install-plugin&plugin=elementor' ),
		'install-plugin_elementor'
	);
	?>
	<div class="notice notice-warning">
		<p>
			<strong><?php esc_html_e( 'LuwiPress Gold needs Elementor.', 'luwipress-gold' ); ?></strong>
			<?php
			printf(
				/* translators: %s: install plugin link */
				esc_html__( 'Install Elementor (free) to use the LuwiPress Gold templates: %s.', 'luwipress-gold' ),
				'<a href="' . esc_url( $plugin_url ) . '">' . esc_html__( 'Install Elementor', 'luwipress-gold' ) . '</a>'
			);
			?>
		</p>
	</div>
	<?php
} );

/* ---------------------------------------------------------------------------
 * 2. Body class for stylesheet hooks.
 * ------------------------------------------------------------------------- */
add_filter( 'body_class', function ( $classes ) {
	$classes[] = 'luwipress-gold';
	if ( did_action( 'elementor/loaded' ) ) {
		$classes[] = 'luwipress-gold--elementor';
	}
	return $classes;
} );

/* ---------------------------------------------------------------------------
 * 3. Auto-pick Elementor "Canvas" page template when the post meta says so.
 *    Set _lwp_canvas = 1 on a page (or use the Kit JSON) and the Elementor
 *    Canvas template kicks in — full-bleed, no header/footer wrapper.
 * ------------------------------------------------------------------------- */
add_filter( 'template_include', function ( $template ) {
	if ( ! did_action( 'elementor/loaded' ) ) return $template;

	$post = get_queried_object();
	if ( ! $post || ! isset( $post->ID ) ) return $template;

	$canvas = get_post_meta( $post->ID, '_lwp_canvas', true );
	if ( $canvas ) {
		$canvas_path = WP_PLUGIN_DIR . '/elementor/modules/page-templates/templates/canvas.php';
		if ( file_exists( $canvas_path ) ) {
			return $canvas_path;
		}
	}

	return $template;
}, 99 );

/* ---------------------------------------------------------------------------
 * 4. Surface bundled Kit folder to operators.
 * ------------------------------------------------------------------------- */
add_filter( 'luwipress_gold_kit_path', function () {
	return LUWIPRESS_GOLD_DIR . '/elementor-kit/';
} );

/**
 * Helper exposed for sites that want to list / link the bundled Kit JSONs
 * from a custom admin screen. Returns absolute file paths.
 */
function luwipress_gold_kit_files() {
	$dir = apply_filters( 'luwipress_gold_kit_path', LUWIPRESS_GOLD_DIR . '/elementor-kit/' );
	if ( ! is_dir( $dir ) ) return [];
	$files = glob( trailingslashit( $dir ) . '*.json' );
	return $files ?: [];
}

/* ---------------------------------------------------------------------------
 * 5. Match Elementor's container width to the kit (1372 px), if the operator
 *    hasn't manually overridden it via Site Settings.
 * ------------------------------------------------------------------------- */
add_filter( 'elementor/frontend/print_google_fonts', '__return_false' );  // we already enqueue them
add_action( 'elementor/element/section/section_layout/before_section_end', function ( $element ) {
	// no-op default — left as an extension point.
}, 10 );
