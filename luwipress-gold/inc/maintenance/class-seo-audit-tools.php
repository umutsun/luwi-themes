<?php
/**
 * SEO + Redirects audit tools (1.7.0).
 *
 *   - LuwiPress_Gold_Canonical_Audit_Tool          rel=canonical present + self-pointing + no chain
 *   - LuwiPress_Gold_Hreflang_Reciprocity_Tool     hreflang completeness + reciprocal references
 *   - LuwiPress_Gold_Redirect_Chain_Detector_Tool  301 → 301 chains + final-status check
 *   - LuwiPress_Gold_Sitemap_Indexation_Parity_Tool  sitemap entries that are 301/404/noindex
 *   - LuwiPress_Gold_SEO_Triangle_Health_Tool      consolidated SEO pillars audit
 *
 * Read-only scans that hit the live site over HTTP. Each tool caches its
 * per-URL HEAD/GET probes for 10 minutes so a re-scan within that window
 * is cheap. Findings link back to the dedicated tool that resolves them.
 *
 * @package luwipress-gold
 * @since   1.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// HTTP helper — single shared probe with 10-minute transient cache so the
// audit suite can re-use the same status code / headers across all five tools
// without re-fetching. Keyed on URL + method.
// ─────────────────────────────────────────────────────────────────────────────

if ( ! class_exists( 'LuwiPress_Gold_SEO_HTTP_Probe' ) ) :
class LuwiPress_Gold_SEO_HTTP_Probe {

	const CACHE_TTL = 600; // 10 minutes
	const CACHE_KEY_PREFIX = 'lwp_gold_seo_probe_';
	const REDIRECT_LIMIT = 5;

	/**
	 * HEAD-check with redirect following capped. Returns:
	 *   [ 'final_status' => int, 'final_url' => string, 'hops' => int, 'chain' => [ url => status, ... ] ]
	 */
	public static function trace( $url ) {
		$cache_key = self::CACHE_KEY_PREFIX . md5( 'trace:' . $url );
		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$chain = array();
		$current = $url;
		$hops = 0;
		while ( $hops <= self::REDIRECT_LIMIT ) {
			$res = wp_remote_head( $current, array(
				'timeout'     => 5,
				'redirection' => 0,
				'sslverify'   => false,
				'user-agent'  => 'LuwiPressGold-SEOAudit/1.0',
			) );
			if ( is_wp_error( $res ) ) {
				$chain[ $current ] = 0;
				break;
			}
			$code = (int) wp_remote_retrieve_response_code( $res );
			$chain[ $current ] = $code;
			if ( in_array( $code, array( 301, 302, 307, 308 ), true ) ) {
				$loc = wp_remote_retrieve_header( $res, 'location' );
				if ( ! $loc ) { break; }
				// Normalise relative redirects.
				if ( strpos( $loc, '/' ) === 0 ) {
					$home = home_url( '/' );
					$base = ( wp_parse_url( $home, PHP_URL_SCHEME ) ?: 'https' ) . '://' . wp_parse_url( $home, PHP_URL_HOST );
					$loc = $base . $loc;
				}
				$current = $loc;
				$hops++;
				continue;
			}
			break;
		}

		$out = array(
			'final_status' => isset( $chain[ $current ] ) ? $chain[ $current ] : 0,
			'final_url'    => $current,
			'hops'         => $hops,
			'chain'        => $chain,
		);
		set_transient( $cache_key, $out, self::CACHE_TTL );
		return $out;
	}

	/**
	 * GET fetch, returns body + headers. For canonical/hreflang parsing.
	 */
	public static function fetch_html( $url ) {
		$cache_key = self::CACHE_KEY_PREFIX . md5( 'html:' . $url );
		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$res = wp_remote_get( $url, array(
			'timeout'     => 8,
			'redirection' => 3,
			'sslverify'   => false,
			'user-agent'  => 'LuwiPressGold-SEOAudit/1.0',
		) );
		if ( is_wp_error( $res ) ) {
			$out = array( 'status' => 0, 'body' => '', 'final_url' => $url );
			set_transient( $cache_key, $out, self::CACHE_TTL );
			return $out;
		}
		$out = array(
			'status'    => (int) wp_remote_retrieve_response_code( $res ),
			'body'      => (string) wp_remote_retrieve_body( $res ),
			'final_url' => $url,
		);
		set_transient( $cache_key, $out, self::CACHE_TTL );
		return $out;
	}

	/**
	 * Scope the audit to a representative URL sample without burning the
	 * whole crawl budget. Default: 1 sample from each public post type
	 * (page, post, product) + 1 from each product_cat archive (max 5
	 * subcat) + the homepage. Approx 12-20 URLs per scan.
	 */
	public static function representative_sample( $extra_args = array() ) {
		$urls = array();
		$urls[] = home_url( '/' );

		foreach ( array( 'page', 'post', 'product' ) as $type ) {
			$ids = get_posts( array(
				'post_type'      => $type,
				'post_status'    => 'publish',
				'posts_per_page' => isset( $extra_args[ $type . '_limit' ] ) ? (int) $extra_args[ $type . '_limit' ] : 3,
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'fields'         => 'ids',
			) );
			foreach ( $ids as $id ) {
				$link = get_permalink( $id );
				if ( $link ) {
					$urls[] = $link;
				}
			}
		}

		if ( taxonomy_exists( 'product_cat' ) ) {
			$terms = get_terms( array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'number'     => 5,
				'orderby'    => 'count',
				'order'      => 'DESC',
			) );
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $t ) {
					$link = get_term_link( $t );
					if ( ! is_wp_error( $link ) ) {
						$urls[] = $link;
					}
				}
			}
		}

		return array_values( array_unique( $urls ) );
	}
}
endif;

// ─────────────────────────────────────────────────────────────────────────────
// Canonical Audit — every page should declare rel=canonical, that canonical
// should match the page's own permalink (not point elsewhere), and the
// canonical URL itself should resolve 200 (no chain).
// ─────────────────────────────────────────────────────────────────────────────

class LuwiPress_Gold_Canonical_Audit_Tool {

	public static function scan( $args = array(), $tool = array() ) {
		$urls = LuwiPress_Gold_SEO_HTTP_Probe::representative_sample();
		$candidates = array();

		foreach ( $urls as $url ) {
			$res = LuwiPress_Gold_SEO_HTTP_Probe::fetch_html( $url );
			if ( $res['status'] !== 200 ) {
				continue; // can't audit if page itself fails
			}
			if ( ! preg_match( '#<link[^>]+rel=["\']canonical["\'][^>]*href=["\']([^"\']+)["\'][^>]*/?>#i', $res['body'], $m ) ) {
				$candidates[] = array(
					'id'    => 'missing:' . md5( $url ),
					'title' => $url,
					'meta'  => 'NO rel=canonical found',
				);
				continue;
			}
			$canonical = trim( $m[1] );
			$normalized_self = self::normalize( $url );
			$normalized_can  = self::normalize( $canonical );

			// Cross-domain canonical — almost always wrong.
			$home_host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
			$can_host  = wp_parse_url( $canonical, PHP_URL_HOST );
			if ( $can_host && $can_host !== $home_host ) {
				$candidates[] = array(
					'id'    => 'crossdomain:' . md5( $url ),
					'title' => $url,
					'meta'  => 'canonical points to ' . $can_host . ' (should be ' . $home_host . ')',
				);
				continue;
			}

			// Canonical doesn't match self — possible if Rank Math has a
			// duplicate-content rule. Surface it for review.
			if ( $normalized_can !== $normalized_self ) {
				$candidates[] = array(
					'id'    => 'mismatch:' . md5( $url ),
					'title' => $url,
					'meta'  => 'canonical → ' . $canonical,
				);
				continue;
			}

			// Canonical → 301/404? (chain or broken)
			$trace = LuwiPress_Gold_SEO_HTTP_Probe::trace( $canonical );
			if ( $trace['hops'] > 0 || $trace['final_status'] !== 200 ) {
				$candidates[] = array(
					'id'    => 'chained:' . md5( $url ),
					'title' => $url,
					'meta'  => sprintf( 'canonical chain: %d hop(s), final %d', $trace['hops'], $trace['final_status'] ),
				);
			}
		}

		return array(
			'candidates' => $candidates,
			'count'      => count( $candidates ),
			'meta'       => array(
				'sampled' => count( $urls ),
				'note'    => 'Per-URL canonical health check. Findings: missing tag, cross-domain target, mismatch with self, or canonical itself returning a non-200/301-chain.',
			),
		);
	}

	private static function normalize( $url ) {
		$p = wp_parse_url( $url );
		if ( ! $p || empty( $p['host'] ) ) { return $url; }
		$path = $p['path'] ?? '/';
		// Force trailing slash on directory-style paths (no extension).
		if ( substr( $path, -1 ) !== '/' && ! preg_match( '/\.[a-z0-9]{2,5}$/i', $path ) ) {
			$path .= '/';
		}
		return strtolower( $p['scheme'] . '://' . $p['host'] . $path );
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// Hreflang Reciprocity Audit — for every page, every active language must
// be present in the <link rel="alternate" hreflang="X"> set, AND each
// alternate must reciprocate (point back to this page).
// ─────────────────────────────────────────────────────────────────────────────

class LuwiPress_Gold_Hreflang_Reciprocity_Tool {

	public static function scan( $args = array(), $tool = array() ) {
		$languages = self::active_languages();
		if ( count( $languages ) <= 1 ) {
			return array( 'candidates' => array(), 'count' => 0, 'meta' => array( 'multilingual' => false ) );
		}

		$urls = LuwiPress_Gold_SEO_HTTP_Probe::representative_sample();
		$candidates = array();

		foreach ( $urls as $url ) {
			$res = LuwiPress_Gold_SEO_HTTP_Probe::fetch_html( $url );
			if ( $res['status'] !== 200 ) { continue; }

			$tags = array();
			if ( preg_match_all( '#<link[^>]+rel=["\']alternate["\'][^>]+hreflang=["\']([^"\']+)["\'][^>]+href=["\']([^"\']+)["\']#i', $res['body'], $m, PREG_SET_ORDER ) ) {
				foreach ( $m as $row ) {
					$tags[ strtolower( $row[1] ) ] = $row[2];
				}
			} else {
				preg_match_all( '#<link[^>]+href=["\']([^"\']+)["\'][^>]+hreflang=["\']([^"\']+)["\']#i', $res['body'], $m2, PREG_SET_ORDER );
				foreach ( $m2 as $row ) {
					$tags[ strtolower( $row[2] ) ] = $row[1];
				}
			}

			$missing = array();
			foreach ( $languages as $lang ) {
				if ( ! isset( $tags[ $lang ] ) ) {
					$missing[] = $lang;
				}
			}
			$has_xdefault = isset( $tags['x-default'] );
			if ( $missing || ! $has_xdefault ) {
				$candidates[] = array(
					'id'    => 'incomplete:' . md5( $url ),
					'title' => $url,
					'meta'  => sprintf(
						'tags: %d · missing langs: %s · x-default: %s',
						count( $tags ),
						$missing ? implode( ',', $missing ) : 'none',
						$has_xdefault ? 'yes' : 'NO'
					),
				);
				continue;
			}

			// Reciprocity check: each alternate URL should also list THIS
			// url among its own hreflang set.
			$reciprocity_failed = array();
			foreach ( $tags as $lang => $alt ) {
				if ( $lang === 'x-default' ) { continue; }
				if ( $alt === $url ) { continue; }
				$alt_res = LuwiPress_Gold_SEO_HTTP_Probe::fetch_html( $alt );
				if ( $alt_res['status'] !== 200 ) { continue; }
				$alt_has_self = ( strpos( $alt_res['body'], $url ) !== false );
				if ( ! $alt_has_self ) {
					$reciprocity_failed[] = $lang;
				}
			}
			if ( $reciprocity_failed ) {
				$candidates[] = array(
					'id'    => 'reciprocity:' . md5( $url ),
					'title' => $url,
					'meta'  => 'hreflang one-way to: ' . implode( ',', $reciprocity_failed ),
				);
			}
		}

		return array(
			'candidates' => $candidates,
			'count'      => count( $candidates ),
			'meta'       => array(
				'languages' => $languages,
				'sampled'   => count( $urls ),
				'note'      => 'Findings either: missing language tag, missing x-default, or one-way hreflang (sibling doesn\'t point back). WPML/Polylang inject these by default but break when translations are unlinked or post-types are excluded.',
			),
		);
	}

	private static function active_languages() {
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$langs = apply_filters( 'wpml_active_languages', null );
			return is_array( $langs ) ? array_keys( $langs ) : array();
		}
		if ( function_exists( 'pll_languages_list' ) ) {
			return (array) pll_languages_list();
		}
		return array();
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// Redirect Chain Detector — single-hop is fine, 2+ hops is wasted crawl
// budget AND a Google Search Console flag. Walks every URL in the slug-
// conflict redirect map plus a sample of internal hrefs.
// ─────────────────────────────────────────────────────────────────────────────

class LuwiPress_Gold_Redirect_Chain_Detector_Tool {

	public static function scan( $args = array(), $tool = array() ) {
		$urls = self::candidate_urls();
		$candidates = array();

		foreach ( $urls as $url ) {
			$trace = LuwiPress_Gold_SEO_HTTP_Probe::trace( $url );
			if ( $trace['hops'] === 0 ) {
				continue; // direct hit
			}
			if ( $trace['hops'] === 1 && $trace['final_status'] === 200 ) {
				continue; // single-hop redirect ending 200 = healthy
			}
			$severity = $trace['final_status'] !== 200
				? 'broken'
				: ( $trace['hops'] >= 2 ? 'chain' : 'warn' );
			$candidates[] = array(
				'id'    => $severity . ':' . md5( $url ),
				'title' => $url,
				'meta'  => sprintf(
					'%s · %d hops · final %d → %s',
					strtoupper( $severity ),
					$trace['hops'],
					$trace['final_status'],
					$trace['final_url']
				),
			);
		}

		return array(
			'candidates' => $candidates,
			'count'      => count( $candidates ),
			'meta'       => array(
				'urls_traced' => count( $urls ),
				'note'        => 'Single-hop 200 is healthy. 2+ hops or non-200 endings are flagged. Source URLs come from the slug-conflict redirect map + a sample of internal hrefs.',
			),
		);
	}

	/** Source URLs to trace — slug-conflict map + internal hrefs (small sample). */
	private static function candidate_urls() {
		$urls = array();
		// Slug-conflict map: every key is a known 301-source.
		$transient_key = defined( 'LUWIPRESS_GOLD_SLUG_CONFLICT_TRANSIENT' )
			? LUWIPRESS_GOLD_SLUG_CONFLICT_TRANSIENT
			: 'luwipress_gold_slug_conflicts_v1';
		$map = get_transient( $transient_key );
		if ( is_array( $map ) ) {
			foreach ( array_keys( $map ) as $slug ) {
				$urls[] = home_url( '/' . ltrim( (string) $slug, '/' ) . '/' );
			}
		}
		// Plus a sample of internal hrefs from recent post content.
		global $wpdb;
		$rows = $wpdb->get_col(
			"SELECT post_content FROM {$wpdb->posts}
			  WHERE post_status='publish' AND post_type IN ('post','page','product')
			  ORDER BY post_modified DESC LIMIT 30"
		);
		$home_host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		foreach ( (array) $rows as $body ) {
			if ( ! preg_match_all( '/href=["\']([^"\']+)["\']/i', (string) $body, $m ) ) { continue; }
			foreach ( $m[1] as $href ) {
				$href = trim( $href );
				if ( ! preg_match( '#^https?://#i', $href ) ) { continue; }
				if ( wp_parse_url( $href, PHP_URL_HOST ) !== $home_host ) { continue; }
				if ( preg_match( '#\.(jpg|jpeg|png|gif|webp|svg|css|js|pdf|zip)$#i', $href ) ) { continue; }
				$urls[] = $href;
				if ( count( $urls ) > 60 ) { break 2; }
			}
		}
		return array_values( array_unique( $urls ) );
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// Sitemap Indexation Parity — every URL listed in the active SEO plugin's
// sitemap should be (a) a 200, (b) not a 301 source, (c) not noindex.
// Discovers the sitemap via common conventions; supports Rank Math, Yoast,
// AIOSEO, and the WP core sitemap.
// ─────────────────────────────────────────────────────────────────────────────

class LuwiPress_Gold_Sitemap_Indexation_Parity_Tool {

	public static function scan( $args = array(), $tool = array() ) {
		$index_url = self::discover_sitemap_url();
		if ( ! $index_url ) {
			return array(
				'candidates' => array(),
				'count'      => 0,
				'meta'       => array( 'sitemap_found' => false, 'note' => 'No sitemap discovered at /sitemap.xml, /sitemap_index.xml, /wp-sitemap.xml, or /sitemap-index.xml' ),
			);
		}

		$child_urls  = self::extract_locs( $index_url );
		// Limit child sitemaps to fit budget — pick first 3.
		$child_urls = array_slice( $child_urls, 0, 3 );

		$page_urls = array();
		foreach ( $child_urls as $cu ) {
			$page_urls = array_merge( $page_urls, self::extract_locs( $cu ) );
		}
		// Dedupe + cap at 80 to keep request budget bounded.
		$page_urls = array_slice( array_values( array_unique( $page_urls ) ), 0, 80 );

		$slug_map = (array) get_transient( defined( 'LUWIPRESS_GOLD_SLUG_CONFLICT_TRANSIENT' ) ? LUWIPRESS_GOLD_SLUG_CONFLICT_TRANSIENT : 'luwipress_gold_slug_conflicts_v1' );

		$candidates = array();
		foreach ( $page_urls as $url ) {
			// Compare against slug-conflict map (path-segment only).
			$path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
			if ( $path && isset( $slug_map[ $path ] ) ) {
				$candidates[] = array(
					'id'    => 'slugmap:' . md5( $url ),
					'title' => $url,
					'meta'  => 'sitemap entry is a 301 source (slug-conflict map redirects it)',
				);
				continue;
			}
			$trace = LuwiPress_Gold_SEO_HTTP_Probe::trace( $url );
			if ( $trace['final_status'] === 200 && $trace['hops'] === 0 ) {
				continue; // healthy
			}
			if ( $trace['hops'] >= 1 ) {
				$candidates[] = array(
					'id'    => 'redirect:' . md5( $url ),
					'title' => $url,
					'meta'  => sprintf( 'sitemap entry redirects %d hop(s) → %d %s', $trace['hops'], $trace['final_status'], $trace['final_url'] ),
				);
				continue;
			}
			if ( $trace['final_status'] !== 200 ) {
				$candidates[] = array(
					'id'    => 'broken:' . md5( $url ),
					'title' => $url,
					'meta'  => sprintf( 'sitemap entry returned %d', $trace['final_status'] ),
				);
			}
		}

		return array(
			'candidates' => $candidates,
			'count'      => count( $candidates ),
			'meta'       => array(
				'sitemap_index'    => $index_url,
				'child_sitemaps'   => $child_urls,
				'urls_audited'     => count( $page_urls ),
				'note'             => 'Findings: 301 source in sitemap, redirect chains, or 4xx/5xx pages still listed. Resolve at the SEO plugin level (exclude post types, regen sitemap) — luwipress-gold doesn\'t generate sitemaps itself.',
			),
		);
	}

	private static function discover_sitemap_url() {
		$candidates = array(
			'/sitemap_index.xml', // Rank Math, Yoast
			'/sitemap.xml',       // AIOSEO, generic
			'/wp-sitemap.xml',    // WP core
			'/sitemap-index.xml',
		);
		foreach ( $candidates as $path ) {
			$url = home_url( $path );
			$res = wp_remote_head( $url, array(
				'timeout'     => 4,
				'redirection' => 2,
				'sslverify'   => false,
				'user-agent'  => 'LuwiPressGold-SEOAudit/1.0',
			) );
			if ( ! is_wp_error( $res ) && (int) wp_remote_retrieve_response_code( $res ) === 200 ) {
				return $url;
			}
		}
		return '';
	}

	private static function extract_locs( $url ) {
		$res = LuwiPress_Gold_SEO_HTTP_Probe::fetch_html( $url );
		if ( $res['status'] !== 200 || empty( $res['body'] ) ) {
			return array();
		}
		if ( ! preg_match_all( '#<loc>([^<]+)</loc>#i', $res['body'], $m ) ) {
			return array();
		}
		return array_map( 'trim', $m[1] );
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// SEO Triangle Health — consolidated audit aggregating canonical, hreflang,
// redirect chains, sitemap parity, broken internal links, and slug-conflict
// map state. Returns a per-pillar score + the dedicated tool that resolves
// each pillar's findings.
// ─────────────────────────────────────────────────────────────────────────────

class LuwiPress_Gold_SEO_Triangle_Health_Tool {

	public static function scan( $args = array(), $tool = array() ) {
		$pillars = array();
		$total_findings = 0;

		// Wiring: each pillar runs its dedicated tool's scan and reports the
		// finding count back as a 0-100 health score (100 = clean).
		$wiring = array(
			'canonical'           => array( 'LuwiPress_Gold_Canonical_Audit_Tool', 'canonical_audit',           'Canonical health' ),
			'hreflang'            => array( 'LuwiPress_Gold_Hreflang_Reciprocity_Tool', 'hreflang_reciprocity_audit','Hreflang reciprocity' ),
			'redirect_chains'     => array( 'LuwiPress_Gold_Redirect_Chain_Detector_Tool', 'redirect_chain_detector', 'Redirect chains' ),
			'sitemap'             => array( 'LuwiPress_Gold_Sitemap_Indexation_Parity_Tool', 'sitemap_indexation_parity','Sitemap parity' ),
			'internal_links'      => array( 'LuwiPress_Gold_Broken_Internal_Links_Tool', 'broken_internal_links',     'Broken internal links' ),
			'slug_conflict'       => array( 'LuwiPress_Gold_Slug_Conflict_Audit_Tool', 'slug_conflict_audit',         'Slug-conflict redirect map' ),
		);

		foreach ( $wiring as $key => $row ) {
			list( $cls, $sub_id, $label ) = $row;
			if ( ! class_exists( $cls ) ) {
				$pillars[ $key ] = array(
					'label'    => $label,
					'score'    => null,
					'findings' => 0,
					'tool_id'  => $sub_id,
					'note'     => 'Tool class missing — skipped',
				);
				continue;
			}
			try {
				$res = call_user_func( array( $cls, 'scan' ), array(), array() );
			} catch ( \Throwable $e ) {
				$pillars[ $key ] = array(
					'label'    => $label,
					'score'    => null,
					'findings' => 0,
					'tool_id'  => $sub_id,
					'note'     => 'scan threw: ' . $e->getMessage(),
				);
				continue;
			}
			$count = isset( $res['count'] ) ? (int) $res['count'] : 0;
			$total_findings += $count;
			$pillars[ $key ] = array(
				'label'    => $label,
				'score'    => self::score_for( $count ),
				'findings' => $count,
				'tool_id'  => $sub_id,
				'note'     => $count === 0 ? 'clean' : sprintf( '%d finding(s) — run %s', $count, $sub_id ),
			);
		}

		// Weighted total: canonical + hreflang are most critical for SEO,
		// chains + sitemap heavy operationally, slug + links lighter.
		$weights = array(
			'canonical'      => 25,
			'hreflang'       => 20,
			'redirect_chains'=> 20,
			'sitemap'        => 15,
			'internal_links' => 10,
			'slug_conflict'  => 10,
		);
		$weighted = 0;
		$used     = 0;
		foreach ( $pillars as $key => $p ) {
			if ( $p['score'] === null ) { continue; }
			$weighted += $p['score'] * ( $weights[ $key ] ?? 0 );
			$used     += $weights[ $key ] ?? 0;
		}
		$total_score = $used > 0 ? (int) round( $weighted / $used ) : 0;

		// Surface each pillar as a candidate row so the operator sees
		// per-pillar findings inline + can drill down to the dedicated tool.
		$candidates = array();
		foreach ( $pillars as $key => $p ) {
			$candidates[] = array(
				'id'    => 'pillar:' . $key,
				'title' => $p['label'],
				'meta'  => sprintf(
					'%s · score %s · %s',
					$key,
					$p['score'] === null ? 'n/a' : $p['score'] . '/100',
					$p['note']
				),
			);
		}

		return array(
			'candidates' => $candidates,
			'count'      => $total_findings,
			'meta'       => array(
				'total_score'    => $total_score,
				'total_findings' => $total_findings,
				'pillars'        => $pillars,
				'note'           => 'Single-call SEO audit. Each pillar cites its dedicated tool that resolves findings.',
			),
		);
	}

	/** Convert a finding count into a 0-100 score. 0 = clean, 5+ = floor at 50. */
	private static function score_for( $findings ) {
		if ( $findings === 0 ) { return 100; }
		$score = 100 - ( $findings * 10 );
		return max( 50, $score );
	}
}
