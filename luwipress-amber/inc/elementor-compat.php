<?php
/**
 * Elementor compatibility layer.
 *
 * The theme is designed for Elementor (Free or Pro) plus ElementsKit Lite.
 * This file:
 *   1. Surfaces an admin notice when Elementor is missing.
 *   2. Adds a body class so Elementor templates can detect they're inside
 *      LuwiPress Amber and react accordingly.
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
			<strong><?php esc_html_e( 'LuwiPress Amber needs Elementor.', 'luwipress-amber' ); ?></strong>
			<?php
			printf(
				/* translators: %s: install plugin link */
				esc_html__( 'Install Elementor (free) to use the LuwiPress Amber templates: %s.', 'luwipress-amber' ),
				'<a href="' . esc_url( $plugin_url ) . '">' . esc_html__( 'Install Elementor', 'luwipress-amber' ) . '</a>'
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
	$classes[] = 'luwipress-amber';
	if ( did_action( 'elementor/loaded' ) ) {
		$classes[] = 'luwipress-amber--elementor';
	}
	return $classes;
} );

/* ---------------------------------------------------------------------------
 * 3. Template-include resolution — runs at priority 99 so it overrides
 *    Elementor's own page-templates module (which hooks at default 10).
 *
 *    Two routing rules, in priority order:
 *
 *    a) `_lwp_canvas = 1` post meta → load Elementor's bundled Canvas
 *       template (full-bleed, no header/footer wrapper). Set this via
 *       Kit JSON import or manually on a landing page.
 *
 *    b) `_wp_page_template = elementor_header_footer` (with or without
 *       `.php` extension) → load OUR `elementor_header_footer.php`
 *       template, NOT Elementor's bundled `header-footer.php`.
 *
 *       Why we override Elementor's bundled file: it's a bare
 *       `get_header() + the_content() + get_footer()` — emits no
 *       `<main>` wrapper, no `<article>`, no breadcrumb, no
 *       reading-progress hook. Posts migrated from Hello Elementor
 *       carry this template meta, so Elementor's bare file kicks in
 *       and the post renders without theme chrome — exactly the
 *       "single post gelmiyor" symptom. Our version wraps the content
 *       in `<main class="lwp-elementor-page lwp-elementor-page--canvas"
 *       id="primary">` so accessibility, reading-progress, and Gold
 *       chrome all stay intact.
 * ------------------------------------------------------------------------- */
add_filter( 'template_include', function ( $template ) {
	if ( ! did_action( 'elementor/loaded' ) ) return $template;

	$post = get_queried_object();
	if ( ! $post || ! isset( $post->ID ) ) return $template;

	// (a) _lwp_canvas → Elementor bare canvas
	$canvas = get_post_meta( $post->ID, '_lwp_canvas', true );
	if ( $canvas ) {
		$canvas_path = WP_PLUGIN_DIR . '/elementor/modules/page-templates/templates/canvas.php';
		if ( file_exists( $canvas_path ) ) {
			return $canvas_path;
		}
	}

	// (b) _wp_page_template = elementor_header_footer → OUR canvas with
	//     theme chrome. Match both `elementor_header_footer` (legacy
	//     Hello Elementor migration shape) and `elementor_header_footer.php`.
	if ( is_singular() ) {
		$page_tpl = (string) get_post_meta( $post->ID, '_wp_page_template', true );
		if ( $page_tpl === 'elementor_header_footer' || $page_tpl === 'elementor_header_footer.php' ) {
			$ours = LUWIPRESS_AMBER_DIR . '/elementor_header_footer.php';
			if ( file_exists( $ours ) ) {
				return $ours;
			}
		}
	}

	return $template;
}, 99 );

/* ---------------------------------------------------------------------------
 * 4. Surface bundled Kit folder to operators.
 * ------------------------------------------------------------------------- */
add_filter( 'luwipress_amber_kit_path', function () {
	return LUWIPRESS_AMBER_DIR . '/elementor-kit/';
} );

/**
 * Helper exposed for sites that want to list / link the bundled Kit JSONs
 * from a custom admin screen. Returns absolute file paths.
 */
function luwipress_amber_kit_files() {
	$dir = apply_filters( 'luwipress_amber_kit_path', LUWIPRESS_AMBER_DIR . '/elementor-kit/' );
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
