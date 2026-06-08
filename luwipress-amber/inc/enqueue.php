<?php
/**
 * Asset enqueue — fonts, Amber design system, chrome behaviours, animation layer.
 *
 * The Amber design system ships as four stylesheets, loaded in cascade order:
 *   amber.css       → core tokens + components + chrome + dark/light
 *   pages.css       → listing / single-tour / booking-box styles
 *   page-styles.css → subpage sections (about, contact, blog, WC, voucher, 404)
 *   tokens.css      → thin brand-knob bridge (LAST, so Customizer overrides win)
 *
 * chrome.js drives the mobile drawer, mega menu re-parenting, theme toggle,
 * FAQ + drawer accordions, sliders and the hero video. frontend.js carries
 * the Gold-inherited animation layer (loader / scroll-reveal / cart bump).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', function () {
	$ver = LUWIPRESS_AMBER_VERSION;

	// Google Fonts — Fraunces (display serif, incl. italic for the <em> accent)
	// + Inter (body / UI). preconnect + display=swap to avoid layout shift.
	wp_enqueue_style(
		'luwipress-amber-fonts',
		'https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;0,9..144,700;0,9..144,900;1,9..144,400;1,9..144,500&family=Inter:wght@400;500;600;700&display=swap',
		[],
		null
	);

	$cb = function ( $rel ) use ( $ver ) {
		$abs = get_template_directory() . $rel;
		return $ver . '.' . ( file_exists( $abs ) ? filemtime( $abs ) : '0' );
	};

	// Shared chrome behaviours — null-guarded, safe on every page.
	wp_enqueue_script(
		'luwipress-amber-chrome',
		LUWIPRESS_AMBER_URI . '/assets/js/chrome.js?cb=' . $cb( '/assets/js/chrome.js' ),
		[],
		null,
		true
	);

	// Travel interactivity — REGISTERED (not enqueued) so the tour widgets pull
	// them via get_script_depends() only on pages that actually use them.
	wp_register_script( 'luwipress-amber-booking', LUWIPRESS_AMBER_URI . '/assets/js/booking.js?cb=' . $cb( '/assets/js/booking.js' ), [], null, true );
	wp_register_script( 'luwipress-amber-tours', LUWIPRESS_AMBER_URI . '/assets/js/tours.js?cb=' . $cb( '/assets/js/tours.js' ), [], null, true );

	// Animation layer (Gold-inherited) — page loader, scroll reveal, cart bump.
	// LiteSpeed "Remove Query Strings" strips `?ver=`, so bake a `?cb=` buster
	// that LS doesn't strip and pass null as the 4th arg.
	$frontend_path = get_template_directory() . '/assets/js/frontend.js';
	$frontend_url  = LUWIPRESS_AMBER_URI . '/assets/js/frontend.js';
	$frontend_url .= '?cb=' . ( file_exists( $frontend_path ) ? $ver . '.' . filemtime( $frontend_path ) : $ver );
	wp_enqueue_script( 'luwipress-amber-frontend', $frontend_url, [], null, true );

	// Keep our JS out of LiteSpeed "Delay JS" rewriting so chrome + animation
	// fire on first paint rather than first user interaction.
	add_filter( 'script_loader_tag', function ( $tag, $handle ) {
		$keep = [ 'luwipress-amber-frontend', 'luwipress-amber-chrome', 'luwipress-amber-booking', 'luwipress-amber-tours' ];
		if ( in_array( $handle, $keep, true ) ) {
			$tag = str_replace( ' src=', ' data-no-optimize="1" data-no-defer="true" data-cfasync="false" src=', $tag );
		}
		return $tag;
	}, 10, 2 );

	// Shop archive surfaces — price slider script + load-more (inherited).
	if ( function_exists( 'is_woocommerce' ) && ( is_shop() || is_product_taxonomy() || is_product_category() || is_product_tag() ) ) {
		wp_enqueue_script( 'wc-price-slider' );

		if ( get_theme_mod( 'luwipress_amber_shop_loadmore', true ) ) {
			wp_enqueue_script(
				'luwipress-amber-loadmore',
				LUWIPRESS_AMBER_URI . '/assets/js/loadmore.js',
				[],
				$cb( '/assets/js/loadmore.js' ),
				true
			);
			wp_localize_script( 'luwipress-amber-loadmore', 'LWP_AMBER_LM', [
				'i18n' => [
					'load_more' => __( 'Load more', 'luwipress-amber' ),
					'loading'   => __( 'Loading…', 'luwipress-amber' ),
					'no_more'   => __( 'You\'ve reached the end.', 'luwipress-amber' ),
					'error'     => __( 'Couldn\'t load more — try again.', 'luwipress-amber' ),
				],
				'mode' => (string) get_theme_mod( 'luwipress_amber_shop_loadmore_mode', 'infinite' ),
			] );
		}
	}
}, 20 );

/**
 * LiteSpeed JS-defer/delay exclusion for our bundles.
 */
if ( ! function_exists( 'luwipress_amber_frontend_litespeed_exclude' ) ) {
	function luwipress_amber_frontend_litespeed_exclude( $excludes ) {
		if ( ! is_array( $excludes ) ) {
			$excludes = array();
		}
		$excludes[] = 'luwipress-amber-elementor/assets/js/frontend.js';
		$excludes[] = 'luwipress-amber-elementor/assets/js/chrome.js';
		$excludes[] = 'luwipress-amber-elementor/assets/js/booking.js';
		$excludes[] = 'luwipress-amber-elementor/assets/js/tours.js';
		$excludes[] = 'luwipress-amber-frontend';
		$excludes[] = 'luwipress-amber-chrome';
		$excludes[] = 'luwipress-amber-booking';
		$excludes[] = 'luwipress-amber-tours';
		return $excludes;
	}
	add_filter( 'litespeed_optm_js_defer_exc', 'luwipress_amber_frontend_litespeed_exclude' );
	add_filter( 'litespeed_optm_js_excludes',  'luwipress_amber_frontend_litespeed_exclude' );
	add_filter( 'litespeed_optm_js_delay_inc', 'luwipress_amber_frontend_litespeed_exclude' );
}

/**
 * Localise the WooCommerce category tree for client-side pill filtering
 * (frontend.js `setupTapPills()`). Skipped when WC / product_cat absent.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! wp_script_is( 'luwipress-amber-frontend', 'enqueued' ) ) {
		return;
	}
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return;
	}

	$top_level = get_terms( [
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'parent'     => 0,
		'fields'     => 'all',
	] );
	if ( is_wp_error( $top_level ) || empty( $top_level ) ) {
		return;
	}

	$cat_tree = [];
	foreach ( $top_level as $top ) {
		$slugs = [ (string) $top->slug ];
		$descendants = get_term_children( (int) $top->term_id, 'product_cat' );
		if ( is_array( $descendants ) ) {
			foreach ( $descendants as $desc_id ) {
				$d = get_term( (int) $desc_id );
				if ( $d instanceof \WP_Term && $d->count > 0 ) {
					$slugs[] = (string) $d->slug;
				}
			}
		}
		$cat_tree[ (string) $top->slug ] = array_values( array_unique( $slugs ) );
	}

	wp_localize_script( 'luwipress-amber-frontend', 'LuwiAmber', [ 'catTree' => $cat_tree ] );
}, 30 );

/**
 * Amber design system CSS at priority 9999 — lands AFTER every plugin's CSS
 * (WooCommerce, ElementsKit, Yoast, …) and after any LiteSpeed CSS combine, so
 * "last rule wins" goes our way. Cascade: amber.css → pages.css → page-styles.css
 * → tokens.css. tokens.css ships LAST so the brand-knob :root + the inline
 * Customizer overrides (output-css.php) win the accent tokens.
 */
add_action( 'wp_enqueue_scripts', function () {
	$base_ver = LUWIPRESS_AMBER_VERSION;
	$dir      = LUWIPRESS_AMBER_DIR;
	$uri      = LUWIPRESS_AMBER_URI;

	$asset_ver = function ( $rel_path ) use ( $base_ver, $dir ) {
		$abs = $dir . $rel_path;
		return $base_ver . '.' . ( file_exists( $abs ) ? filemtime( $abs ) : '0' );
	};

	wp_enqueue_style(
		'luwipress-amber-design',
		$uri . '/assets/css/amber.css',
		[ 'luwipress-amber-fonts' ],
		$asset_ver( '/assets/css/amber.css' )
	);
	wp_enqueue_style(
		'luwipress-amber-pages',
		$uri . '/assets/css/pages.css',
		[ 'luwipress-amber-design' ],
		$asset_ver( '/assets/css/pages.css' )
	);
	wp_enqueue_style(
		'luwipress-amber-page-styles',
		$uri . '/assets/css/page-styles.css',
		[ 'luwipress-amber-pages' ],
		$asset_ver( '/assets/css/page-styles.css' )
	);
	wp_enqueue_style(
		'luwipress-amber-tokens',
		$uri . '/assets/css/tokens.css',
		[ 'luwipress-amber-page-styles' ],
		$asset_ver( '/assets/css/tokens.css' )
	);
}, 9999 );

/**
 * Keep the theme's stylesheets OUT of LiteSpeed CSS optimization (UCSS +
 * combine), which drops selectors for widgets inserted after the crawl and
 * serves stale combined chunks. `data-no-optimize="1"` loads them verbatim.
 */
add_filter( 'style_loader_tag', function ( $tag, $handle ) {
	$skip = [ 'luwipress-amber-design', 'luwipress-amber-pages', 'luwipress-amber-page-styles', 'luwipress-amber-tokens' ];
	if ( in_array( $handle, $skip, true ) ) {
		$tag = str_replace( ' href=', ' data-no-optimize="1" data-no-defer="1" data-cfasync="false" href=', $tag );
	}
	return $tag;
}, 10, 2 );

add_filter( 'litespeed_optm_css_excludes', 'luwipress_amber_css_ls_excludes' );
add_filter( 'litespeed_optm_ucss_file_exc_inline_gen', 'luwipress_amber_css_ls_excludes' );
if ( ! function_exists( 'luwipress_amber_css_ls_excludes' ) ) {
	function luwipress_amber_css_ls_excludes( $excludes ) {
		if ( ! is_array( $excludes ) ) {
			$excludes = array();
		}
		$excludes[] = 'luwipress-amber-elementor/assets/css/amber.css';
		$excludes[] = 'luwipress-amber-elementor/assets/css/pages.css';
		$excludes[] = 'luwipress-amber-elementor/assets/css/page-styles.css';
		$excludes[] = 'luwipress-amber-elementor/assets/css/tokens.css';
		return $excludes;
	}
}

/**
 * Editor styles — surface the Amber design + tokens inside the block editor.
 */
add_action( 'enqueue_block_editor_assets', function () {
	wp_enqueue_style(
		'luwipress-amber-editor-design',
		LUWIPRESS_AMBER_URI . '/assets/css/amber.css',
		[],
		LUWIPRESS_AMBER_VERSION
	);
	wp_enqueue_style(
		'luwipress-amber-editor-tokens',
		LUWIPRESS_AMBER_URI . '/assets/css/tokens.css',
		[ 'luwipress-amber-editor-design' ],
		LUWIPRESS_AMBER_VERSION
	);
} );

/**
 * Preconnect Google Fonts before the stylesheet starts downloading.
 */
add_action( 'wp_head', function () {
	echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
}, 0 );
