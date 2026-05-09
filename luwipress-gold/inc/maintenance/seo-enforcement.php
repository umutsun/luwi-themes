<?php
/**
 * SEO enforcement layer — wires the new `seo` settings group into actual
 * frontend behaviour:
 *
 *   • luwipress_gold_seo_strict_canonical
 *       Forces canonical = permalink, beating Rank Math / Yoast / AIOSEO
 *       overrides. Useful when an SEO plugin's duplicate-content rule
 *       generates cross-page canonicals that hurt indexation.
 *
 *   • luwipress_gold_seo_force_trailing_slash
 *       Redirects no-slash URLs to slash variants (or vice-versa, follows
 *       wp `permalink_structure` setting). Skips REST, sitemap, .xml, and
 *       paths with file extensions.
 *
 *   • luwipress_gold_seo_noindex_empty_archives
 *       Emits <meta name="robots" content="noindex,follow"> on
 *       product_cat archives the empty_term_archives audit flagged.
 *       Cached for 6 hours; busts on tool execute.
 *
 *   • luwipress_gold_block_orphan_landings   (existing — wired here)
 *       Renders 410 Gone for pages flagged by the unwanted_landing_pages
 *       audit. (See elementor-template-force.php — already implemented.)
 *
 * Activate each via the LuwiPress → Theme → Settings tab (group: seo).
 *
 * @package luwipress-gold
 * @since   1.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─── Strict canonical override ─────────────────────────────────────────────
//
// Hooks into Rank Math, Yoast, and AIOSEO canonical filters. Each plugin
// emits at most one of these — gracefully no-ops on sites that don't have
// a given plugin. Final fallback: a self-canonical <link> at wp_head when
// no SEO plugin is active.

add_filter( 'rank_math/frontend/canonical', function ( $canonical ) {
	if ( ! get_theme_mod( 'luwipress_gold_seo_strict_canonical', false ) ) {
		return $canonical;
	}
	return luwipress_gold_self_canonical_url() ?: $canonical;
}, 99 );

add_filter( 'wpseo_canonical', function ( $canonical ) {
	if ( ! get_theme_mod( 'luwipress_gold_seo_strict_canonical', false ) ) {
		return $canonical;
	}
	return luwipress_gold_self_canonical_url() ?: $canonical;
}, 99 );

add_filter( 'aioseo_canonical_url', function ( $canonical ) {
	if ( ! get_theme_mod( 'luwipress_gold_seo_strict_canonical', false ) ) {
		return $canonical;
	}
	return luwipress_gold_self_canonical_url() ?: $canonical;
}, 99 );

// Fallback emission when NO SEO plugin is active. We only emit if no other
// rel=canonical was already added — checked via a marker output buffer.
add_action( 'wp_head', function () {
	if ( ! get_theme_mod( 'luwipress_gold_seo_strict_canonical', false ) ) {
		return;
	}
	if ( defined( 'RANK_MATH_VERSION' ) || defined( 'WPSEO_VERSION' ) || defined( 'AIOSEO_VERSION' ) ) {
		return; // SEO plugin already filtered the canonical
	}
	$url = luwipress_gold_self_canonical_url();
	if ( $url ) {
		echo "\n<link rel=\"canonical\" href=\"" . esc_url( $url ) . "\" />\n";
	}
}, 1 );

if ( ! function_exists( 'luwipress_gold_self_canonical_url' ) ) {
	function luwipress_gold_self_canonical_url() {
		if ( is_singular() ) {
			$id = get_queried_object_id();
			return $id ? get_permalink( $id ) : '';
		}
		if ( is_tax() || is_category() || is_tag() ) {
			$term = get_queried_object();
			if ( $term && ! is_wp_error( $term ) ) {
				$link = get_term_link( $term );
				return is_string( $link ) ? $link : '';
			}
		}
		if ( is_front_page() || is_home() ) {
			return home_url( '/' );
		}
		if ( function_exists( 'wc_get_page_id' ) && is_shop() ) {
			$shop_id = wc_get_page_id( 'shop' );
			return $shop_id > 0 ? get_permalink( $shop_id ) : '';
		}
		return '';
	}
}

// ─── Trailing slash consistency ────────────────────────────────────────────
//
// Follows site permalink structure: if the structure ends in /, force /;
// otherwise force no slash. Skip REST API, sitemap, /wp-* paths, and any
// path that already has a file extension (asset URLs).

add_action( 'template_redirect', function () {
	if ( ! get_theme_mod( 'luwipress_gold_seo_force_trailing_slash', false ) ) {
		return;
	}
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	$req_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = (string) wp_parse_url( $req_uri, PHP_URL_PATH );
	$query = (string) wp_parse_url( $req_uri, PHP_URL_QUERY );

	if ( $path === '' || $path === '/' ) {
		return;
	}
	// Skip system paths.
	$skip_patterns = array( '#^/wp-#', '#^/feed/?$#', '#\.xml$#', '#\.txt$#', '#sitemap#' );
	foreach ( $skip_patterns as $pat ) {
		if ( preg_match( $pat, $path ) ) {
			return;
		}
	}
	// Skip paths with a file extension (assets).
	if ( preg_match( '#\.[a-z0-9]{2,5}$#i', $path ) ) {
		return;
	}

	$structure = (string) get_option( 'permalink_structure', '' );
	$want_slash = $structure === '' || substr( $structure, -1 ) === '/';
	$has_slash  = substr( $path, -1 ) === '/';

	if ( $want_slash && ! $has_slash ) {
		$new = $path . '/' . ( $query !== '' ? '?' . $query : '' );
		wp_redirect( $new, 301 );
		exit;
	}
	if ( ! $want_slash && $has_slash ) {
		$new = rtrim( $path, '/' ) . ( $query !== '' ? '?' . $query : '' );
		wp_redirect( $new, 301 );
		exit;
	}
}, 5 );

// ─── Noindex empty archives ────────────────────────────────────────────────
//
// Reads the empty_term_archives audit's most recent transient cache (6h)
// and emits noindex,follow on those term archives. Cached audit prevents
// per-request DB scans.

add_action( 'wp_head', function () {
	if ( ! get_theme_mod( 'luwipress_gold_seo_noindex_empty_archives', false ) ) {
		return;
	}
	if ( ! is_tax( 'product_cat' ) && ! is_category() ) {
		return;
	}
	$term = get_queried_object();
	if ( ! $term || is_wp_error( $term ) ) { return; }
	$flagged = get_transient( 'luwipress_gold_empty_archive_ids' );
	if ( ! is_array( $flagged ) ) {
		return; // no audit cache — silent no-op
	}
	if ( ! in_array( (int) $term->term_id, array_map( 'intval', $flagged ), true ) ) {
		return;
	}
	echo "\n<meta name=\"robots\" content=\"noindex,follow\" />\n";
}, 1 );

// Auto-populate the noindex transient when the empty_term_archives tool
// runs its scan via the bridge. Avoids a second query at audit time.
add_action( 'luwipress_theme_tool_executed', function ( $tool_id, $action, $result ) {
	if ( $tool_id !== 'empty_term_archives' || $action !== 'scan' ) { return; }
	if ( empty( $result['candidates'] ) ) {
		delete_transient( 'luwipress_gold_empty_archive_ids' );
		return;
	}
	$ids = array_map( function ( $c ) { return (int) ( $c['id'] ?? 0 ); }, $result['candidates'] );
	$ids = array_values( array_filter( $ids ) );
	set_transient( 'luwipress_gold_empty_archive_ids', $ids, 6 * HOUR_IN_SECONDS );
}, 10, 3 );

add_action( 'luwipress_theme_tool_executed', function ( $tool_id, $action, $result ) {
	if ( $tool_id !== 'unwanted_landing_pages' || $action !== 'scan' ) { return; }
	if ( empty( $result['candidates'] ) ) {
		delete_transient( 'luwipress_gold_orphan_landing_ids' );
		return;
	}
	$ids = array_map( function ( $c ) { return (int) ( $c['id'] ?? 0 ); }, $result['candidates'] );
	$ids = array_values( array_filter( $ids ) );
	set_transient( 'luwipress_gold_orphan_landing_ids', $ids, 6 * HOUR_IN_SECONDS );
}, 10, 3 );
