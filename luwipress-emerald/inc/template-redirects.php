<?php
/**
 * Slug-collision redirects (page ↔ product_cat) — WPML / Polylang aware.
 *
 * In a long-lived WooCommerce store it's common to discover that a page
 * and a product category share the same slug — e.g. a legacy Elementor
 * "/<hub>/" page sitting alongside the matching "/product-category/<hub>/"
 * archive. WordPress permalink rewriting picks the page, which means
 * visitors arriving via menus, bookmarks, or search land on the static
 * page even though the live commerce content lives behind the archive
 * route.
 *
 * This module solves that without deleting the operator's pages: at
 * `template_redirect` priority 1, if the current request resolves to a
 * page whose slug also exists as a published product_cat term, the
 * visitor is 301'd to the corresponding /product-category/<slug>/ URL.
 * The original page post stays untouched in the database — admins can
 * still edit it, search/replace its content, or migrate it later.
 *
 * Multilingual stores (WPML / Polylang) are explicitly handled:
 *   - The active-language directory prefix (`/it/`, `/fr/`, `/es/`) is
 *     stripped before lookup and re-applied by `home_url()` /
 *     `get_term_link()` at redirect-time so each visitor stays in
 *     their language.
 *   - When the operator translates PAGE slugs (`/es/percusiones/`)
 *     while keeping TERM slugs in the source language (`percussions`),
 *     the cross-language discovery walks WPML's `icl_translations`
 *     table (or Polylang's `pll_get_post_translations`) to match each
 *     translated page against any sibling term — the gap a literal
 *     SQL JOIN on `posts.post_name = terms.slug` would otherwise miss.
 *   - System slugs (privacy / shop / cart / checkout / my-account /
 *     terms / front / posts pages) are detected at runtime — no
 *     hardcoded slug list — and excluded from auto-discovery.
 *
 * Two behavior modes:
 *
 *   1. AUTO (default off, opt-in via theme_mod `luwipress_emerald_resolve_slug_conflicts`)
 *      Theme scans the database once and caches a slug-conflict map for
 *      one hour. Any page whose slug (or a translated sibling's slug)
 *      matches a non-empty product_cat term redirects to that term's
 *      active-language archive. Operator opts in via Customizer →
 *      LuwiPress Emerald → Performance.
 *
 *   2. EXPLICIT (always available)
 *      Operators / child themes / sister plugins register specific
 *      redirects via the `luwipress_emerald_hub_redirects` filter,
 *      independent of the auto-discovery toggle. Useful when:
 *        - WC isn't installed but the operator still wants /old/ → /new/
 *        - Auto-discovery would catch too much (e.g. an "About" page
 *          coincidentally matches a niche category slug)
 *        - The redirect target is something other than the matching
 *          product_cat archive (e.g. a curated landing page).
 *
 * The auto-discovery cache invalidates whenever a page or product_cat
 * is edited, so newly resolved or newly conflicting slugs reflect
 * within one admin save cycle.
 *
 * Filter hooks (operator extensibility):
 *   - `luwipress_emerald_slug_conflict_skip_slugs` — extend / suppress the
 *     detected system-page skip list before the SQL discovery runs.
 *   - `luwipress_emerald_slug_conflict_map_pre_cache` — modify the final
 *     map before it's written to the transient (add curated entries,
 *     drop noisy ones).
 *   - `luwipress_emerald_hub_redirects` — final composition hook called on
 *     every request. Return [] to fully disable the module.
 *
 * Population check (cross-cutting through every pass):
 *
 *   WooCommerce parent categories typically report `count=0` because
 *   WC only counts products directly attached to a term — not products
 *   attached to its descendants. So `percussions`, `winds`,
 *   `bowed-instruments`, `string-instruments`, `accessories` all sit
 *   at count=0 even when their children carry thousands of products.
 *   Every pass below filters candidates via
 *   `luwipress_emerald_term_has_population()` which recurses into
 *   descendants — a term is "populated" when its own count > 0 OR any
 *   descendant in the hierarchy has count > 0. This is what allows
 *   `/percussions/` to redirect to `/product-category/percussions/`
 *   rather than getting dropped as "unused".
 *
 * Discovery passes (in order):
 *
 *   1. Exact match — page.post_name === term.slug.
 *   2. WPML / Polylang cross-language — page slug in any language
 *      whose translation sibling matches a term slug.
 *   3. Plural + prefix fuzzy — `arabic-oud` → `arabic-ouds`,
 *      `persian-kamancheh` → `persian-kamancheh-kemenches`.
 *   4. Levenshtein-1 — `classical-kemence` ↔ `classical-kemenche`,
 *      `saz-baglama-accessories` ↔ `saz-baglama-acessories`. Single-
 *      candidate gate + 6-char minimum slug length to avoid false
 *      positives on short words (about / blog / shop).
 *   5. Menu-parent inheritance — editorial sub-pages inherit their
 *      parent menu item's category. `/santur/` (under "String
 *      Instruments" in the menu) → `/product-category/string-
 *      instruments/`; `/duduk/` (under "Winds") → `/product-
 *      category/winds/`. Skipped when the parent menu item resolves
 *      to a non-cat URL or when the page slug is in the system-page
 *      skip list.
 *   6. Ancestor fallback for empty terms — when a page slug matches
 *      (exactly or via passes 1–4) a term that is itself empty AND
 *      whose entire subtree is empty, walk the parent chain to find
 *      the first populated ancestor. `/duduk/` → empty leaf
 *      `duduk-armanian-winds` → ancestor `winds` (populous via
 *      Ney/Mey/Zurna/Kaval). Without this pass every editorial
 *      profile page next to a dead leaf term keeps resolving to the
 *      legacy page.
 *
 * Map values may be `bool true` (auto-target /product-category/<slug>/),
 * a full URL string, or an `int` term_id (resolved via
 * `get_term_link()` at request-time so WPML / Polylang re-applies
 * the active language).
 *
 * Operator overrides via the `luwipress_emerald_hub_redirects` filter
 * always win — register a `false` value to suppress an over-eager
 * auto-discovered entry, or an explicit URL/term_id to override the
 * auto-target:
 *
 *     add_filter( 'luwipress_emerald_hub_redirects', function ( $map ) {
 *         $map['some-slug'] = 13235;          // term_id
 *         $map['another-slug'] = false;       // suppress
 *         $map['third-slug'] = '/curated/';   // explicit URL
 *         return $map;
 *     } );
 *
 * @package luwipress-emerald
 * @since   1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Core promotion (3.1.56) — when the canonical resolver class is loaded
 * by core LuwiPress, this theme file becomes a no-op. The core engine
 * runs the same six-pass discovery + the same `template_redirect` p1
 * hook, so leaving both active would double-register the callback and
 * (worse) compete for cache state. The core path also reads the legacy
 * `luwipress_emerald_resolve_slug_conflicts` theme_mod so operators who
 * toggled the theme-tier resolver inherit their setting automatically.
 *
 * Geriye uyumluluk: core 3.1.55 or earlier? Class doesn't exist → fall
 * through to the legacy theme path below. The site keeps working
 * exactly as it did before the core promotion.
 */
if ( class_exists( 'LuwiPress_Slug_Resolver' ) ) {
	return;
}

const LUWIPRESS_EMERALD_SLUG_CONFLICT_TRANSIENT = 'luwipress_emerald_slug_conflicts_v1';
const LUWIPRESS_EMERALD_SLUG_CONFLICT_OPTION    = 'luwipress_emerald_resolve_slug_conflicts';

if ( ! function_exists( 'luwipress_emerald_skip_redirect_slugs' ) ) {

	/**
	 * Build the list of page slugs the slug-collision auto-discovery
	 * should ignore. We never want to redirect WordPress system pages
	 * (privacy policy) or WooCommerce checkout-flow pages
	 * (cart / checkout / my-account / shop / terms) even if they
	 * coincidentally collide with a product_cat slug — redirecting
	 * `/cart/` to a product archive would break commerce.
	 *
	 * Every value is detected at runtime from the live install — no
	 * hardcoded slug list. Operators / sister plugins extend or
	 * suppress via the `luwipress_emerald_slug_conflict_skip_slugs`
	 * filter, e.g. to add a custom landing page that should never
	 * be auto-redirected.
	 *
	 * @return string[] Lowercase slugs (no leading/trailing slash).
	 *                  May be empty when no system pages are configured.
	 */
	function luwipress_emerald_skip_redirect_slugs() {
		$slugs = [];

		// WP core: privacy policy page slug, when one is set.
		$priv_id = (int) get_option( 'wp_page_for_privacy_policy', 0 );
		if ( $priv_id > 0 ) {
			$priv = get_post( $priv_id );
			if ( $priv && $priv->post_name ) {
				$slugs[] = (string) $priv->post_name;
			}
		}

		// WooCommerce: every commerce-flow page slug. wc_get_page_id()
		// returns -1 when the page isn't configured, so we filter.
		if ( function_exists( 'wc_get_page_id' ) ) {
			foreach ( [ 'shop', 'cart', 'checkout', 'myaccount', 'terms' ] as $key ) {
				$pid = (int) wc_get_page_id( $key );
				if ( $pid > 0 ) {
					$p = get_post( $pid );
					if ( $p && $p->post_name ) {
						$slugs[] = (string) $p->post_name;
					}
				}
			}
		}

		// Front + posts pages — visitors hitting these should never
		// 301-redirect away even if a category coincidentally shares
		// the slug.
		foreach ( [ 'page_on_front', 'page_for_posts' ] as $opt ) {
			$pid = (int) get_option( $opt, 0 );
			if ( $pid > 0 ) {
				$p = get_post( $pid );
				if ( $p && $p->post_name ) {
					$slugs[] = (string) $p->post_name;
				}
			}
		}

		// Normalise + dedupe + apply filter for operator overrides.
		$slugs = array_values( array_unique( array_filter( array_map( 'strtolower', $slugs ) ) ) );

		/**
		 * Filter the auto-discovered list of slugs that should be
		 * excluded from slug-collision redirection.
		 *
		 * @param string[] $slugs  Detected slugs (privacy, wc commerce flow).
		 */
		$slugs = (array) apply_filters( 'luwipress_emerald_slug_conflict_skip_slugs', $slugs );
		return array_values( array_unique( array_filter( array_map( 'strval', $slugs ) ) ) );
	}
}

if ( ! function_exists( 'luwipress_emerald_term_has_population' ) ) {

	/**
	 * Test whether a product_cat term is "populated" — either has direct
	 * product assignments (count > 0) or any descendant in the hierarchy
	 * tree does. Top-level WooCommerce parent categories often have
	 * count=0 because WC only counts products directly attached to a
	 * term, not products attached to its children. Without this widened
	 * check, slug-collision discovery would skip every parent category
	 * as if it were unused, leaving `/percussions/`, `/winds/`,
	 * `/bowed-instruments/` etc. to keep resolving to legacy pages.
	 *
	 * Result is memoised per-request to keep deep descendant walks cheap.
	 *
	 * @param int    $term_id  product_cat term ID.
	 * @param string $taxonomy Taxonomy name (default 'product_cat').
	 * @return bool true when term self count > 0 OR any descendant count > 0.
	 */
	function luwipress_emerald_term_has_population( $term_id, $taxonomy = 'product_cat' ) {
		static $cache = [];
		$term_id = (int) $term_id;
		if ( $term_id <= 0 ) {
			return false;
		}
		$cache_key = $taxonomy . ':' . $term_id;
		if ( isset( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}
		$term = get_term( $term_id, $taxonomy );
		if ( ! $term instanceof \WP_Term || is_wp_error( $term ) ) {
			return $cache[ $cache_key ] = false;
		}
		if ( (int) $term->count > 0 ) {
			return $cache[ $cache_key ] = true;
		}
		$children = get_term_children( $term_id, $taxonomy );
		if ( is_wp_error( $children ) || empty( $children ) ) {
			return $cache[ $cache_key ] = false;
		}
		foreach ( $children as $child_id ) {
			$child = get_term( (int) $child_id, $taxonomy );
			if ( $child instanceof \WP_Term && (int) $child->count > 0 ) {
				return $cache[ $cache_key ] = true;
			}
		}
		return $cache[ $cache_key ] = false;
	}
}

if ( ! function_exists( 'luwipress_emerald_term_find_populated_ancestor' ) ) {

	/**
	 * Walk the parent chain of a product_cat term and return the first
	 * ancestor (or the term itself) whose `term_has_population` is true.
	 * Used by Pass 5 of slug-conflict discovery to handle the case where
	 * a page slug matches (exactly or via prefix/levenshtein) a term whose
	 * own subtree is empty — e.g. `duduk-armanian-winds` (count=0,
	 * descendant=0) → ancestor `winds` (count=0 but populated descendants
	 * Ney/Mey/Zurna/Kaval) → returns the `winds` term_id.
	 *
	 * Loop-guarded by a visited set in case of malformed term parent
	 * cycles (rare but recoverable).
	 *
	 * @param int    $term_id  Starting product_cat term ID.
	 * @param string $taxonomy Taxonomy name (default 'product_cat').
	 * @return int Term ID of the first populated ancestor, or 0 when none.
	 */
	function luwipress_emerald_term_find_populated_ancestor( $term_id, $taxonomy = 'product_cat' ) {
		$term_id = (int) $term_id;
		$visited = [];
		while ( $term_id > 0 && ! isset( $visited[ $term_id ] ) ) {
			$visited[ $term_id ] = true;
			if ( luwipress_emerald_term_has_population( $term_id, $taxonomy ) ) {
				return $term_id;
			}
			$term = get_term( $term_id, $taxonomy );
			if ( ! $term instanceof \WP_Term || is_wp_error( $term ) ) {
				return 0;
			}
			$term_id = (int) $term->parent;
		}
		return 0;
	}
}

if ( ! function_exists( 'luwipress_emerald_resolve_slug_conflicts_enabled' ) ) {

	/**
	 * @return bool true when the operator has opted into auto slug-collision
	 *              redirects via Customizer (or theme-mod, or wp-cli).
	 */
	function luwipress_emerald_resolve_slug_conflicts_enabled() {
		// theme_mod first (Customizer surface), with a fall-through to the
		// operator-friendly wp_option key so wp-cli / programmatic toggles
		// from sister plugins also work.
		$mod = get_theme_mod( LUWIPRESS_EMERALD_SLUG_CONFLICT_OPTION, null );
		if ( $mod !== null ) {
			return (bool) $mod;
		}
		return (bool) get_option( LUWIPRESS_EMERALD_SLUG_CONFLICT_OPTION, false );
	}
}

if ( ! function_exists( 'luwipress_emerald_discover_slug_conflicts' ) ) {

	/**
	 * Scan the DB for `page` slugs that collide with non-empty
	 * `product_cat` term slugs. Cached for one hour in a transient.
	 *
	 * @return array<string,bool> slug => true map. Empty when WC is off
	 *                            or no conflicts exist.
	 */
	function luwipress_emerald_discover_slug_conflicts() {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return [];
		}
		$cached = get_transient( LUWIPRESS_EMERALD_SLUG_CONFLICT_TRANSIENT );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		global $wpdb;

		// 1) Exact slug matches — page.post_name === term.slug. These are
		// the obvious conflicts where the visitor URL `/<slug>/` could
		// land on either; redirect target is the term's permalink.
		//
		// We DON'T filter `tt.count > 0` in SQL. Top-level parent
		// categories in WooCommerce often have count=0 because WC counts
		// only direct product assignments, not descendants. Without that
		// gate, `/percussions/`, `/winds/`, `/bowed-instruments/` (all
		// parent terms with count=0 but populous children) would never
		// enter the redirect map. The PHP-side filter below uses
		// `term_has_population()` which recurses into descendants.
		$exact_rows = $wpdb->get_results(
			"SELECT DISTINCT p.post_name AS page_slug, t.term_id, tt.taxonomy
			   FROM {$wpdb->posts} p
			   INNER JOIN {$wpdb->terms} t ON p.post_name = t.slug
			   INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
			  WHERE p.post_type = 'page'
			    AND p.post_status = 'publish'
			    AND tt.taxonomy = 'product_cat'",
			ARRAY_A
		);

		$map = [];
		// Pass 5 input — exact / fuzzy matches whose target term is empty
		// AND has no populated descendants. Pass 5 walks parent chain to
		// find a usable ancestor (e.g. `duduk-armanian-winds` empty → ancestor
		// `winds` populous).
		$empty_term_candidates = [];
		if ( is_array( $exact_rows ) ) {
			foreach ( $exact_rows as $r ) {
				$slug = (string) $r['page_slug'];
				if ( $slug === '' ) {
					continue;
				}
				$tid = (int) $r['term_id'];
				if ( $tid <= 0 ) {
					$map[ $slug ] = true;
					continue;
				}
				if ( luwipress_emerald_term_has_population( $tid ) ) {
					// Store term_id (not the resolved permalink) so request-time
					// callers can apply WPML/Polylang current-language filter
					// via `get_term_link()`. Storing the EN permalink would
					// freeze the redirect to one language. Also lets the mega
					// menu render-time fallback consume the same map shape.
					$map[ $slug ] = $tid;
				} else {
					// Defer to Pass 5; record candidate for ancestor walk.
					$empty_term_candidates[ $slug ] = $tid;
				}
			}
		}

		// 1b) WPML / Polylang cross-language matches.
		//
		// On multilingual stores it's common for the OPERATOR to keep one
		// English term tree (`product_cat` slugs stay in English: percussions,
		// winds…) while translating PAGE slugs (`/es/percusiones/`,
		// `/fr/vents/`). The exact-slug query above can't catch these
		// because the JOIN is literal — it never sees that
		// `posts.post_name = 'percusiones'` and `terms.slug = 'percussions'`
		// belong to the same conceptual entity in different languages.
		//
		// To close that gap we walk WPML's translation table: for every
		// page, find every sibling translation and check whether ANY
		// sibling's slug matches a non-empty product_cat term. If so we
		// add the page's own (translated) slug to the map and let
		// `get_term_link()` re-apply the active-language prefix at
		// request time. Polylang uses post_meta `_translations` instead
		// of a SQL table; handled in a separate pass below.
		$wpml_table = $wpdb->prefix . 'icl_translations';
		$wpml_active = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpml_table ) ) === $wpml_table );

		if ( $wpml_active ) {
			// Filter `tt.count > 0` intentionally OMITTED — same rationale
			// as Pass 1 (parent categories have count=0 in WC). PHP-side
			// `term_has_population()` widens the gate to include parent
			// categories with populous descendant trees.
			$cross_rows = $wpdb->get_results(
				"SELECT DISTINCT p.post_name AS page_slug, t.term_id
				   FROM {$wpdb->posts} p
				   INNER JOIN {$wpml_table} pl
				     ON pl.element_id = p.ID
				    AND pl.element_type = CONCAT('post_', p.post_type)
				   INNER JOIN {$wpml_table} sib
				     ON sib.trid = pl.trid
				   INNER JOIN {$wpdb->posts} sp
				     ON sp.ID = sib.element_id
				    AND sp.post_status = 'publish'
				   INNER JOIN {$wpdb->terms} t
				     ON sp.post_name = t.slug
				   INNER JOIN {$wpdb->term_taxonomy} tt
				     ON tt.term_id = t.term_id
				    AND tt.taxonomy = 'product_cat'
				  WHERE p.post_type = 'page'
				    AND p.post_status = 'publish'",
				ARRAY_A
			);
			if ( is_array( $cross_rows ) ) {
				foreach ( $cross_rows as $r ) {
					$slug = (string) $r['page_slug'];
					if ( $slug === '' || isset( $map[ $slug ] ) ) {
						continue; // exact-match already handled it
					}
					$tid = (int) $r['term_id'];
					if ( $tid <= 0 ) {
						continue;
					}
					if ( luwipress_emerald_term_has_population( $tid ) ) {
						$map[ $slug ] = $tid;
					} elseif ( ! isset( $empty_term_candidates[ $slug ] ) ) {
						$empty_term_candidates[ $slug ] = $tid;
					}
				}
			}
		}

		// 1c) Polylang fallback — same idea, different storage. Polylang
		// records translation groups in post_meta `_translations` as a
		// serialised PHP array of [ lang_code => post_id ]. We unserialise,
		// pull each sibling's post_name, and check term collisions.
		if ( function_exists( 'pll_get_post_translations' ) || taxonomy_exists( 'language' ) ) {
			$skip_slugs = luwipress_emerald_skip_redirect_slugs();
			$placeholders = implode( ',', array_fill( 0, max( 1, count( $skip_slugs ) ), '%s' ) );
			$page_rows = $skip_slugs
				? $wpdb->get_results(
					$wpdb->prepare(
						"SELECT ID, post_name FROM {$wpdb->posts}
						  WHERE post_type = 'page' AND post_status = 'publish'
						    AND post_name <> ''
						    AND post_name NOT IN ($placeholders)",
						$skip_slugs
					),
					ARRAY_A
				)
				: $wpdb->get_results(
					"SELECT ID, post_name FROM {$wpdb->posts}
					  WHERE post_type = 'page' AND post_status = 'publish' AND post_name <> ''",
					ARRAY_A
				);
			foreach ( (array) $page_rows as $r ) {
				$pid  = (int) $r['ID'];
				$slug = (string) $r['post_name'];
				if ( $slug === '' || isset( $map[ $slug ] ) ) {
					continue;
				}
				$siblings = function_exists( 'pll_get_post_translations' )
					? (array) pll_get_post_translations( $pid )
					: [];
				if ( empty( $siblings ) ) {
					continue;
				}
				foreach ( $siblings as $sib_id ) {
					$sib_id = (int) $sib_id;
					if ( $sib_id <= 0 ) continue;
					$sib_post = get_post( $sib_id );
					if ( ! $sib_post ) continue;
					$term = get_term_by( 'slug', (string) $sib_post->post_name, 'product_cat' );
					if ( ! ( $term instanceof \WP_Term ) ) continue;
					if ( luwipress_emerald_term_has_population( (int) $term->term_id ) ) {
						$map[ $slug ] = (int) $term->term_id;
						break;
					}
					if ( ! isset( $empty_term_candidates[ $slug ] ) ) {
						$empty_term_candidates[ $slug ] = (int) $term->term_id;
					}
				}
			}
		}

		// 2) Fuzzy slug matches — every page slug that has no exact
		// term but does have a UNIQUE near-equivalent product_cat
		// term. Catches plural forms (arabic-oud → arabic-ouds), suffix
		// extensions (persian-kamancheh → persian-kamancheh-kemenches),
		// and "main + descriptor" patterns (santur → santur-hammered-
		// dulcimer). When more than one term plausibly matches the
		// page slug we skip — ambiguous redirects do more harm than
		// leaving the page alone.
		$skip_slugs = luwipress_emerald_skip_redirect_slugs();
		if ( $skip_slugs ) {
			$placeholders = implode( ',', array_fill( 0, count( $skip_slugs ), '%s' ) );
			$page_slugs = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT p.post_name
					   FROM {$wpdb->posts} p
					  WHERE p.post_type = 'page'
					    AND p.post_status = 'publish'
					    AND p.post_name <> ''
					    AND p.post_name NOT IN ($placeholders)",
					$skip_slugs
				)
			);
		} else {
			$page_slugs = $wpdb->get_col(
				"SELECT DISTINCT p.post_name
				   FROM {$wpdb->posts} p
				  WHERE p.post_type = 'page'
				    AND p.post_status = 'publish'
				    AND p.post_name <> ''"
			);
		}
		$page_slugs = is_array( $page_slugs ) ? $page_slugs : [];

		// Pull every product_cat term once for in-memory matching.
		// `hide_empty = false` so parent categories (count=0 but populous
		// descendants) enter the pool. Per-candidate filter below uses
		// `term_has_population()` to keep noise out of the final map
		// while still allowing parent-category resolution.
		$all_terms = get_terms( [
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'fields'     => 'all',
			'number'     => 0,
		] );
		if ( is_wp_error( $all_terms ) ) {
			$all_terms = [];
		}
		$terms_by_slug = [];
		foreach ( $all_terms as $t ) {
			$terms_by_slug[ (string) $t->slug ] = $t;
		}

		foreach ( $page_slugs as $slug ) {
			$slug = (string) $slug;
			if ( $slug === '' || isset( $map[ $slug ] ) ) {
				continue; // already exact-matched
			}

			$candidates = [];

			// Plural forms: append 's' or 'es'.
			foreach ( [ $slug . 's', $slug . 'es' ] as $cand_slug ) {
				if ( isset( $terms_by_slug[ $cand_slug ] ) ) {
					$candidates[] = $terms_by_slug[ $cand_slug ];
				}
			}

			// Prefix match: term slug starts with "<page_slug>-".
			$prefix = $slug . '-';
			$prefix_len = strlen( $prefix );
			foreach ( $terms_by_slug as $tslug => $tobj ) {
				if ( $tslug !== $slug && substr( $tslug, 0, $prefix_len ) === $prefix ) {
					$candidates[] = $tobj;
				}
			}

			// Deduplicate by term_id.
			$by_id = [];
			foreach ( $candidates as $c ) {
				$by_id[ (int) $c->term_id ] = $c;
			}
			$candidates = array_values( $by_id );

			// Only a single unique candidate is safe to auto-redirect.
			if ( count( $candidates ) !== 1 ) {
				continue;
			}
			$tid = (int) $candidates[0]->term_id;
			if ( $tid <= 0 ) {
				continue;
			}
			if ( luwipress_emerald_term_has_population( $tid ) ) {
				$map[ $slug ] = $tid; // term_id, not link — see exact-pass note above
			} elseif ( ! isset( $empty_term_candidates[ $slug ] ) ) {
				$empty_term_candidates[ $slug ] = $tid;
			}
		}

		// 3) Levenshtein-1 fuzzy match — catches translit nuance and
		// single-character typos (`black-sea-kemence` page vs
		// `black-sea-kemenche` term, `saz-baglama-accessories` vs
		// `saz-baglama-acessories`). Safety gates:
		//   - Slugs ≥ 6 chars (short slugs like `about` / `blog` /
		//     `shop` would over-match neighbours by 1 edit).
		//   - Single-candidate within distance 1 (if 2+ terms are
		//     equally close, abstain — ambiguity beats wrong target).
		//   - Length pre-filter: term length must be within ±1 of
		//     page slug length (Levenshtein-1 implies that).
		//   - Term must already be in `$terms_by_slug` (non-empty count).
		foreach ( $page_slugs as $slug ) {
			$slug = (string) $slug;
			if ( $slug === '' || isset( $map[ $slug ] ) ) {
				continue;
			}
			$slug_len = strlen( $slug );
			if ( $slug_len < 6 ) {
				continue;
			}
			$candidates = [];
			foreach ( $terms_by_slug as $tslug => $tobj ) {
				$diff = abs( strlen( $tslug ) - $slug_len );
				if ( $diff > 1 || $tslug === $slug ) {
					continue;
				}
				if ( levenshtein( $slug, $tslug ) === 1 ) {
					$candidates[] = $tobj;
				}
			}
			if ( count( $candidates ) !== 1 ) {
				continue;
			}
			$tid = (int) $candidates[0]->term_id;
			if ( $tid <= 0 ) {
				continue;
			}
			if ( luwipress_emerald_term_has_population( $tid ) ) {
				$map[ $slug ] = $tid; // term_id (consistent map shape)
			} elseif ( ! isset( $empty_term_candidates[ $slug ] ) ) {
				$empty_term_candidates[ $slug ] = $tid;
			}
		}

		// 4) Menu-parent inheritance — for pages still unmatched, walk
		// every nav menu and check if the page is registered as a
		// child of a menu item that DOES resolve to a product_cat. If
		// so, register the page slug → parent term (visitor flow:
		// `/santur/` → `/product-category/string-instruments/`,
		// `/duduk/` → `/product-category/winds/`). This is the
		// editorial-page rescue: a profile page sitting under
		// "String Instruments" in the menu inherits its category for
		// routing purposes without losing its page render.
		//
		// We DON'T register a redirect for items whose parent points
		// at a non-cat URL (custom URL, post, etc) — only when the
		// parent is unambiguously a category route do we cascade.
		$menus = wp_get_nav_menus();
		foreach ( (array) $menus as $menu ) {
			$items = wp_get_nav_menu_items( $menu->term_id );
			if ( empty( $items ) || ! is_array( $items ) ) {
				continue;
			}
			$by_id = [];
			foreach ( $items as $i ) {
				$by_id[ (int) $i->ID ] = $i;
			}
			foreach ( $items as $item ) {
				if ( $item->object !== 'page' || empty( $item->object_id ) ) {
					continue;
				}
				$parent_menu_id = (int) $item->menu_item_parent;
				if ( ! $parent_menu_id || ! isset( $by_id[ $parent_menu_id ] ) ) {
					continue;
				}
				$parent = $by_id[ $parent_menu_id ];

				// Resolve parent menu item to a product_cat term. Widened
				// gate (population includes descendants) so visitors of a
				// page nested under a "Strings" menu item inherit the
				// parent category even when the parent term itself has
				// count=0 (its children carry the products).
				$parent_term_id = 0;
				if ( $parent->object === 'product_cat' && ! empty( $parent->object_id ) ) {
					$pt = get_term( (int) $parent->object_id );
					if ( $pt instanceof \WP_Term && luwipress_emerald_term_has_population( (int) $pt->term_id ) ) {
						$parent_term_id = (int) $pt->term_id;
					}
				}
				if ( ! $parent_term_id && ! empty( $parent->url ) ) {
					$ppath = (string) wp_parse_url( $parent->url, PHP_URL_PATH );
					$psegs = array_values( array_filter( explode( '/', trim( $ppath, '/' ) ) ) );
					$pslug = ! empty( $psegs ) ? (string) end( $psegs ) : '';
					if ( $pslug !== '' && isset( $terms_by_slug[ $pslug ] ) ) {
						$parent_term_id = (int) $terms_by_slug[ $pslug ]->term_id;
					}
				}
				if ( ! $parent_term_id ) {
					continue;
				}

				// Page slug → parent term_id, if not already mapped.
				$page_post = get_post( (int) $item->object_id );
				if ( ! $page_post || $page_post->post_status !== 'publish' ) {
					continue;
				}
				$page_slug = (string) $page_post->post_name;
				if ( $page_slug === '' || isset( $map[ $page_slug ] ) ) {
					continue;
				}
				if ( in_array( $page_slug, $skip_slugs, true ) ) {
					continue; // never redirect system pages even via inheritance
				}
				$map[ $page_slug ] = $parent_term_id;
			}
		}

		// 5) Ancestor fallback for empty terms.
		//
		// Some page slugs match (exactly, by prefix/plural, by
		// Levenshtein-1, or via WPML/Polylang cross-language) a real
		// product_cat term that is itself unused — count=0 with no
		// populated descendants. Common Tapadum-style case:
		// page `duduk` → prefix candidate `duduk-armanian-winds`
		// (count=0, no children) → ancestor chain → `winds` (count=0
		// itself but populous descendants Ney/Mey/Zurna/Kaval) →
		// `term_has_population` true → use winds as the redirect target.
		//
		// Without this pass, every editorial profile page sitting next
		// to an empty leaf term would never get rescued from the legacy
		// page URL.
		//
		// Empty terms whose entire ancestor chain is also empty are
		// dropped — redirecting visitors to an empty archive is worse
		// than leaving them on the editorial page.
		foreach ( $empty_term_candidates as $slug => $empty_tid ) {
			if ( isset( $map[ $slug ] ) ) {
				continue;
			}
			$anc = luwipress_emerald_term_find_populated_ancestor( (int) $empty_tid );
			if ( $anc > 0 ) {
				$map[ $slug ] = $anc;
			}
		}

		// Operator override hook: change / suppress / extend the map
		// before it gets cached. Keys are page slugs (no leading slash);
		// values may be `true` (auto-target /product-category/<slug>/),
		// `false` (suppress), or a full URL / relative path.
		$map = (array) apply_filters( 'luwipress_emerald_slug_conflict_map_pre_cache', $map );

		set_transient( LUWIPRESS_EMERALD_SLUG_CONFLICT_TRANSIENT, $map, HOUR_IN_SECONDS );
		return $map;
	}
}

if ( ! function_exists( 'luwipress_emerald_get_hub_redirects' ) ) {

	/**
	 * Compose the final redirect map. Auto-discovered conflicts (when the
	 * operator has opted in) are merged with explicitly registered
	 * filter entries; explicit wins on key collision so an operator can
	 * suppress a single auto-discovered conflict by registering it as
	 * `false` via the filter.
	 *
	 * Map shape:
	 *   slug (string, no leading/trailing slash) =>
	 *     - true   → auto-target /product-category/<slug>/
	 *     - false  → suppress this slug (no redirect)
	 *     - string → explicit absolute URL or relative path
	 *
	 * @return array<string,bool|string>
	 */
	function luwipress_emerald_get_hub_redirects() {
		$auto = luwipress_emerald_resolve_slug_conflicts_enabled()
			? luwipress_emerald_discover_slug_conflicts()
			: [];

		/**
		 * Allow operators / child themes / sister plugins to extend or
		 * suppress the hub-redirect map. Returning an empty array fully
		 * disables this module.
		 *
		 * @param array $auto Auto-discovered slug => true map (may be empty).
		 */
		$composed = (array) apply_filters( 'luwipress_emerald_hub_redirects', $auto );
		return $composed;
	}
}

if ( ! function_exists( 'luwipress_emerald_maybe_redirect_hub' ) ) {

	/**
	 * Inspect the current request path; if it matches a registered slug,
	 * 301 to the resolved target.
	 *
	 * Hooked at `template_redirect` priority 1 so it runs before WP
	 * surfaces the static page or 404. Skips admin / cron / REST / feed /
	 * non-GET requests.
	 */
	function luwipress_emerald_maybe_redirect_hub() {
		if ( is_admin() || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			return;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) !== 'GET' ) {
			return;
		}
		if ( is_feed() || is_robots() ) {
			return;
		}

		$redirects = luwipress_emerald_get_hub_redirects();
		if ( empty( $redirects ) ) {
			return;
		}

		$req = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
		if ( $req === '' ) {
			return;
		}
		$path = (string) wp_parse_url( $req, PHP_URL_PATH );
		$path = trim( $path, '/' );
		if ( $path === '' ) {
			return;
		}

		// Strip any active language directory prefix (WPML / Polylang) so
		// the lookup works regardless of locale. home_url() in the target
		// re-applies the active language prefix.
		$lang_prefixes = [];
		if ( function_exists( 'icl_get_languages' ) ) {
			$langs = icl_get_languages( 'skip_missing=0' );
			if ( is_array( $langs ) ) {
				foreach ( $langs as $code => $info ) {
					$lang_prefixes[] = (string) $code;
				}
			}
		} elseif ( function_exists( 'pll_languages_list' ) ) {
			$langs = pll_languages_list();
			if ( is_array( $langs ) ) {
				$lang_prefixes = array_map( 'strval', $langs );
			}
		}
		if ( ! empty( $lang_prefixes ) ) {
			$first = strtok( $path, '/' );
			if ( $first !== false && in_array( $first, $lang_prefixes, true ) ) {
				$path = (string) substr( $path, strlen( $first ) + 1 );
				$path = trim( $path, '/' );
			}
		}

		// Match exact slug only — do not redirect nested sub-paths.
		if ( ! array_key_exists( $path, $redirects ) ) {
			return;
		}

		$rule = $redirects[ $path ];
		if ( $rule === false ) {
			return;
		}

		if ( $rule === true ) {
			$target = home_url( '/product-category/' . $path . '/' );
		} elseif ( is_int( $rule ) || ( is_string( $rule ) && ctype_digit( $rule ) ) ) {
			// Term-ID rule (set by the WPML / Polylang cross-language
			// discovery branch). Resolve to the term's permalink AT
			// REQUEST TIME so WPML / Polylang can rewrite the URL into
			// the visitor's active language. `home_url()` /
			// `get_term_link()` are language-aware on multilingual
			// installs — passing the cached EN permalink would freeze
			// the redirect into one language and break /it/, /fr/, /es/
			// flows.
			$term = get_term( (int) $rule, 'product_cat' );
			if ( $term instanceof \WP_Term ) {
				$link = get_term_link( $term );
				if ( ! is_wp_error( $link ) && $link ) {
					$target = (string) $link;
				} else {
					return;
				}
			} else {
				return;
			}
		} else {
			$rule = (string) $rule;
			$target = ( strpos( $rule, 'http' ) === 0 )
				? $rule
				: home_url( '/' . ltrim( $rule, '/' ) );
		}

		// Loop guard.
		$current = home_url( $req );
		if ( untrailingslashit( $current ) === untrailingslashit( $target ) ) {
			return;
		}

		wp_safe_redirect( esc_url_raw( $target ), 301 );
		exit;
	}
}

add_action( 'template_redirect', 'luwipress_emerald_maybe_redirect_hub', 1 );

if ( ! function_exists( 'luwipress_emerald_bust_slug_conflict_cache' ) ) {

	/**
	 * Drop the transient when a page or product_cat is created / updated /
	 * deleted, so newly resolved or newly created conflicts reflect on the
	 * next front-end hit instead of waiting for the 1-hour TTL.
	 */
	function luwipress_emerald_bust_slug_conflict_cache() {
		delete_transient( LUWIPRESS_EMERALD_SLUG_CONFLICT_TRANSIENT );
	}
	add_action( 'save_post_page',         'luwipress_emerald_bust_slug_conflict_cache' );
	add_action( 'deleted_post',           'luwipress_emerald_bust_slug_conflict_cache' );
	add_action( 'edited_product_cat',     'luwipress_emerald_bust_slug_conflict_cache' );
	add_action( 'created_product_cat',    'luwipress_emerald_bust_slug_conflict_cache' );
	add_action( 'delete_product_cat',     'luwipress_emerald_bust_slug_conflict_cache' );
	add_action( 'switch_theme',           'luwipress_emerald_bust_slug_conflict_cache' );
	add_action( 'update_option_' . LUWIPRESS_EMERALD_SLUG_CONFLICT_OPTION, 'luwipress_emerald_bust_slug_conflict_cache' );
}

if ( ! function_exists( 'luwipress_emerald_register_slug_conflict_customizer' ) ) {

	/**
	 * Register the opt-in toggle in Customizer → LuwiPress Emerald → Performance.
	 * Falls back to creating its own section when the panel hasn't been
	 * registered yet (load-order safety).
	 */
	function luwipress_emerald_register_slug_conflict_customizer( $wp_customize ) {
		// Section: re-use Performance panel if it exists, else add ours.
		$section_id = 'luwipress_emerald_performance';
		if ( ! $wp_customize->get_section( $section_id ) ) {
			$panel_id = 'luwipress_emerald';
			if ( ! $wp_customize->get_panel( $panel_id ) ) {
				$wp_customize->add_panel( $panel_id, [
					'title'    => __( 'LuwiPress Emerald', 'luwipress-emerald' ),
					'priority' => 10,
				] );
			}
			$wp_customize->add_section( $section_id, [
				'title'    => __( 'Performance', 'luwipress-emerald' ),
				'panel'    => $panel_id,
				'priority' => 60,
			] );
		}

		$wp_customize->add_setting( LUWIPRESS_EMERALD_SLUG_CONFLICT_OPTION, [
			'type'              => 'theme_mod',
			'default'           => false,
			'capability'        => 'edit_theme_options',
			'transport'         => 'refresh',
			'sanitize_callback' => function ( $v ) { return ! empty( $v ); },
		] );

		// Build a live count so the description can tell the operator
		// how many conflicts will redirect once toggled on.
		$conflicts = luwipress_emerald_discover_slug_conflicts();
		$count     = count( $conflicts );
		$desc      = $count > 0
			? sprintf(
				/* translators: %d: number of slug conflicts auto-detected */
				_n(
					'%d page slug currently collides with a product category slug. Enabling this 301-redirects the page URL to the matching /product-category/ archive on every visit. Pages are not deleted — they remain editable in admin.',
					'%d page slugs currently collide with product category slugs. Enabling this 301-redirects each page URL to its matching /product-category/ archive on every visit. Pages are not deleted — they remain editable in admin.',
					$count,
					'luwipress-emerald'
				),
				$count
			)
			: __( 'No page/product-category slug conflicts detected. Toggle is harmless to leave on — it only redirects when a conflict actually exists.', 'luwipress-emerald' );

		$wp_customize->add_control( LUWIPRESS_EMERALD_SLUG_CONFLICT_OPTION, [
			'label'       => __( 'Resolve page/category slug conflicts', 'luwipress-emerald' ),
			'description' => $desc,
			'section'     => $section_id,
			'type'        => 'checkbox',
			'priority'    => 20,
		] );
	}
	add_action( 'customize_register', 'luwipress_emerald_register_slug_conflict_customizer', 30 );
}
