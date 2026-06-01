<?php
/**
 * Asset enqueue — fonts, design tokens, WooCommerce loop overrides, animation layer.
 *
 * Intentionally minimal: most page CSS lives inline inside the Elementor Kit
 * JSONs (each section ships its own <style> block). The theme only enqueues
 * the cross-page primitives that Elementor templates rely on.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', function () {
	$ver = LUWIPRESS_GOLD_VERSION;

	// Google Fonts — preconnect + display=swap to avoid layout shift.
	wp_enqueue_style(
		'luwipress-gold-fonts',
		'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap',
		[],
		null
	);

	// Animation layer — page loader, scroll reveal, cart bump.
	// Scripts only; respects prefers-reduced-motion at the JS layer.
	//
	// LiteSpeed's "Remove Query Strings" option strips `?ver=` from asset
	// URLs for CDN cacheability — but it ALSO means edge caches (Hostinger
	// / Cloudflare / disk cache) serve the very FIRST version of each file
	// ever served, even after we deploy a new ZIP. Bake a filemtime cache
	// buster directly into the URL using a non-`?ver=` query name that LS
	// doesn't strip. Pass null as the 4th arg so WP doesn't append its own
	// `?ver=` on top.
	$frontend_path = get_template_directory() . '/assets/js/frontend.js';
	$frontend_url  = LUWIPRESS_GOLD_URI . '/assets/js/frontend.js';
	if ( file_exists( $frontend_path ) ) {
		$frontend_url .= '?cb=' . $ver . '.' . filemtime( $frontend_path );
	} else {
		$frontend_url .= '?cb=' . $ver;
	}
	wp_enqueue_script(
		'luwipress-gold-frontend',
		$frontend_url,
		[],
		null,  // Don't let WP append ?ver= (LS strips it anyway)
		true
	);

	// LiteSpeed exclusion — when "Delay JS Until User Interaction" is active,
	// LS rewrites our script tag to type="litespeed/javascript" which the
	// browser ignores until user clicks/touches/scrolls. That kills every
	// widget interaction (search overlay, reading progress, view toggle,
	// load-more, etc). Excluding by handle keeps the script as text/javascript
	// so the deferred boot inside frontend.js fires on the first page load.
	// Belt-and-braces: also add to attributes filter so LS Optimize doesn't
	// rewrite the tag attributes.
	add_filter( 'script_loader_tag', function ( $tag, $handle ) {
		if ( $handle === 'luwipress-gold-frontend' ) {
			// Add LS-specific opt-out attributes
			$tag = str_replace(
				' src=',
				' data-no-optimize="1" data-no-defer="true" data-cfasync="false" src=',
				$tag
			);
		}
		return $tag;
	}, 10, 2 );

	// Shop archive surfaces — price slider script + load-more (1.7.0+).
	// the_widget() doesn't trigger the WC widget's automatic enqueue path,
	// so the slider track JS never loads on our smart-filters sidebar.
	// Enqueue it explicitly when we're on an archive that actually shows
	// the smart-filters widget.
	if ( function_exists( 'is_woocommerce' ) && ( is_shop() || is_product_taxonomy() || is_product_category() || is_product_tag() ) ) {
		wp_enqueue_script( 'wc-price-slider' );

		if ( get_theme_mod( 'luwipress_gold_shop_loadmore', true ) ) {
			$lm_path = get_template_directory() . '/assets/js/loadmore.js';
			$lm_ver  = $ver . '.' . ( file_exists( $lm_path ) ? filemtime( $lm_path ) : '0' );
			wp_enqueue_script(
				'luwipress-gold-loadmore',
				LUWIPRESS_GOLD_URI . '/assets/js/loadmore.js',
				[],
				$lm_ver,
				true
			);
			wp_localize_script( 'luwipress-gold-loadmore', 'LWP_GOLD_LM', [
				'i18n' => [
					'load_more' => __( 'Load more products', 'luwipress-gold' ),
					'loading'   => __( 'Loading…', 'luwipress-gold' ),
					'no_more'   => __( 'You\'ve reached the end.', 'luwipress-gold' ),
					'error'     => __( 'Couldn\'t load more — try again.', 'luwipress-gold' ),
				],
				'mode' => (string) get_theme_mod( 'luwipress_gold_shop_loadmore_mode', 'infinite' ), // infinite (default) | button
			] );
		}
	}
}, 20 );

/**
 * LiteSpeed JS-defer/delay exclusion for our frontend bundle.
 *
 * When "Delay JS Until User Interaction" is enabled in LSCWP, deferred
 * scripts get rewritten to type="litespeed/javascript" — browsers don't
 * execute them until user interacts. That means our widget JS layer
 * (search overlay, reading progress, view toggle, load-more, mobile
 * drawer) is dead on first page render.
 *
 * The fix: tell LS to skip optimization for the frontend.js handle by
 * registering the JS path as an exclude rule. LS checks each script tag's
 * src against this list during its optimization pass.
 */
if ( ! function_exists( 'luwipress_gold_frontend_litespeed_exclude' ) ) {
	function luwipress_gold_frontend_litespeed_exclude( $excludes ) {
		if ( ! is_array( $excludes ) ) {
			$excludes = array();
		}
		$excludes[] = 'luwipress-gold/assets/js/frontend.js';
		$excludes[] = 'luwipress-gold-frontend';  // handle-based match for some LS builds
		return $excludes;
	}
	add_filter( 'litespeed_optm_js_defer_exc', 'luwipress_gold_frontend_litespeed_exclude' );
	add_filter( 'litespeed_optm_js_excludes',  'luwipress_gold_frontend_litespeed_exclude' );
	// Some LS builds also expose a dedicated "Delay JS" exclusion filter.
	add_filter( 'litespeed_optm_js_delay_inc', 'luwipress_gold_frontend_litespeed_exclude' );
}

/**
 * Localise the WooCommerce category tree on its own dedicated hook
 * (priority 30 — runs AFTER the priority-20 enqueue + the customizer
 * bootstrap.php's `LWP_GOLD_ANIM` localise at priority 25). Both
 * localise calls APPEND to the same `-js-extra` script block, so the
 * frontend gets `var LWP_GOLD_ANIM = {...}; var LuwiGold = {...};`.
 *
 * Used by `setupTapPills()` in frontend.js to power client-side
 * filtering on operator-built `.tap-pills` blocks (e.g. the homepage
 * "All / String / Percussions / Bowed / Winds" tabs above the WC
 * product grid). No operator data attributes needed: pill labels are
 * slugified and matched against the localised tree.
 *
 * Skipped when WC is inactive (no product_cat taxonomy) or when the
 * frontend script never ended up enqueued (some other handler killed
 * it). Map shape: `{ "<top-level-slug>": [ "self-slug", "child-1",
 * "child-2", ... ], ... }`.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! wp_script_is( 'luwipress-gold-frontend', 'enqueued' ) ) {
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

	wp_localize_script(
		'luwipress-gold-frontend',
		'LuwiGold',
		[
			'catTree' => $cat_tree,
		]
	);
}, 30 );

/**
 * THEME CSS at priority 9999 — ensures our stylesheets land AFTER every
 * plugin's CSS (WooCommerce, WCML, ElementsKit Lite, Yoast, etc.) and
 * AFTER any LiteSpeed CSS combine ordering. Specificity wars are won by
 * "last rule wins" when both rules are 0,1,0 — without this Tapadum's
 * plugin stack consistently overrode .lwp-mm li, .lwp-site-header etc.
 */
add_action( 'wp_enqueue_scripts', function () {
	$base_ver = LUWIPRESS_GOLD_VERSION;
	$dir      = LUWIPRESS_GOLD_DIR;
	$uri      = LUWIPRESS_GOLD_URI;

	/**
	 * Cache-bust by filemtime (1.7.0+). Same theme version with edited CSS
	 * still produces a fresh `?ver=` query string so browsers + edge caches
	 * (Apache's 7-day max-age) miss correctly. The kernel-cached file stat
	 * is cheap; performance impact is negligible.
	 */
	$asset_ver = function ( $rel_path ) use ( $base_ver, $dir ) {
		$abs = $dir . $rel_path;
		return $base_ver . '.' . ( file_exists( $abs ) ? filemtime( $abs ) : '0' );
	};

	wp_enqueue_style(
		'luwipress-gold-tokens',
		$uri . '/assets/css/tokens.css',
		[ 'luwipress-gold-fonts' ],
		$asset_ver( '/assets/css/tokens.css' )
	);

	wp_enqueue_style(
		'luwipress-gold-widgets',
		$uri . '/assets/css/widgets.css',
		[ 'luwipress-gold-tokens' ],
		$asset_ver( '/assets/css/widgets.css' )
	);

	if ( class_exists( 'WooCommerce' ) ) {
		wp_enqueue_style(
			'luwipress-gold-woo',
			$uri . '/assets/css/woo-overrides.css',
			[ 'luwipress-gold-widgets' ],
			$asset_ver( '/assets/css/woo-overrides.css' )
		);
	}
}, 9999 );

/**
 * Keep the theme's core stylesheets OUT of LiteSpeed's CSS optimization.
 *
 * LSCWP's "Remove Unused CSS" (UCSS) + "CSS Combine" were mangling the theme's
 * widget styling: UCSS keeps only selectors it sees while crawling a page, so
 * rules for widgets inserted later via the REST/MCP page builder (e.g. the
 * homepage product grid, and several bespoke sections) were dropped, while
 * editor-built widgets (e.g. the Masters grid) kept theirs — and the combined
 * chunk was occasionally served stale, so the SAME element measured a different
 * width between reloads. Net effect: mobile spacing broke inconsistently across
 * section after section, even though widgets.css ships the correct responsive
 * rules and is enqueued on every page.
 *
 * `data-no-optimize="1"` tells LiteSpeed to load these sheets verbatim (no
 * combine, no UCSS), so the FULL responsive CSS always applies. Mirrors the
 * existing frontend.js opt-out above. Costs a couple of un-combined requests —
 * a fair trade for deterministic styling. (Operator must Purge All once after
 * deploy so LS drops the old optimized CSS.)
 */
add_filter( 'style_loader_tag', function ( $tag, $handle ) {
	$skip = array( 'luwipress-gold-tokens', 'luwipress-gold-widgets', 'luwipress-gold-woo' );
	if ( in_array( $handle, $skip, true ) ) {
		$tag = str_replace( ' href=', ' data-no-optimize="1" data-no-defer="1" data-cfasync="false" href=', $tag );
	}
	return $tag;
}, 10, 2 );

/**
 * Belt-and-braces: also register the theme CSS paths in LiteSpeed's own CSS
 * optimize-exclude + UCSS-exclude filters (some LSCWP builds honour the filter,
 * others the attribute — cover both, mirroring the frontend.js JS excludes).
 */
add_filter( 'litespeed_optm_css_excludes', 'luwipress_gold_css_ls_excludes' );
add_filter( 'litespeed_optm_ucss_file_exc_inline_gen', 'luwipress_gold_css_ls_excludes' );
if ( ! function_exists( 'luwipress_gold_css_ls_excludes' ) ) {
	function luwipress_gold_css_ls_excludes( $excludes ) {
		if ( ! is_array( $excludes ) ) {
			$excludes = array();
		}
		$excludes[] = 'luwipress-gold-elementor/assets/css/tokens.css';
		$excludes[] = 'luwipress-gold-elementor/assets/css/widgets.css';
		$excludes[] = 'luwipress-gold-elementor/assets/css/woo-overrides.css';
		return $excludes;
	}
}

/**
 * Critical inline stylesheet — written directly into <head> just before
 * </head>. Wins every specificity fight regardless of plugin enqueue
 * order or LiteSpeed combine. Strictly limited to the chrome reset
 * rules that we observed losing on Tapadum's stack:
 *   - <ul> in mega menu shows browser bullets → reset list-style + padding
 *   - <a> in topbar/header/footer shows underlined link → reset
 *   - sticky header z-index conflicts with WCML / chat widget → bump
 */
add_action( 'wp_head', function () {
	?>
<style id="luwipress-gold-critical-reset">
	.lwp-topbar ul,.lwp-topbar ol,.lwp-site-header ul,.lwp-site-header ol,
	.lwp-mm ul,.lwp-mm ol,.lwp-mm-dropdown,.lwp-mm-panel ul,
	.lwp-megabar ul,.lwp-megabar ol,
	.lwp-site-footer ul,.lwp-site-footer ol{
		list-style:none !important;margin:0 !important;padding:0 !important;
	}
	.lwp-topbar a,.lwp-site-header a,.lwp-mm a,.lwp-megabar a,.lwp-site-footer a,
	.lwp-icon-btn{
		text-decoration:none !important;box-shadow:none !important;
	}
	.lwp-site-header{z-index:9999 !important;}
	.lwp-mm-panel,.lwp-mm-dropdown{z-index:10000 !important;}
	body.luwipress-gold .lwp-mm li{
		list-style:none !important;border:0 !important;background:transparent !important;
	}
</style>
	<?php
}, 99999 );

/**
 * Editor styles — show the Gold tokens inside the block editor too.
 */
add_action( 'enqueue_block_editor_assets', function () {
	wp_enqueue_style(
		'luwipress-gold-editor-tokens',
		LUWIPRESS_GOLD_URI . '/assets/css/tokens.css',
		[],
		LUWIPRESS_GOLD_VERSION
	);
} );

/**
 * Preconnect Google Fonts before stylesheet starts downloading. Saves
 * 100–200 ms on first paint.
 */
add_action( 'wp_head', function () {
	echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
}, 0 );
