<?php
/**
 * ArshaHomes Projects — CPT registration + catalog wiring (Phase 1).
 *
 * Registers the `arsha_project` custom post type (Dubai development projects,
 * sourced from DLD via arsha-connect) and wires it into the existing Onyx
 * storefront chrome WITHOUT touching the listings JS contract:
 *
 *   - CPT + taxonomies (developer / area / project_status / unit_type) via the
 *     LuwiPress CPT engine when present (gets WPML/Polylang + Translation
 *     Manager for free), with a direct register_post_type fallback otherwise.
 *   - Feeds the storefront catalog through the `luwipress_onyx_listings_data`
 *     filter in the EXACT shape onyx.js expects (name/cat/loc/beds/area/price/
 *     status/glyph/desc) + a `url` field so each card deep-links to its project.
 *   - Repoints the mega menu + the `listings` URL fallback to the real
 *     /projects/ catalog once a page using template-listings.php exists.
 *   - Enables the featured registry for arsha_project so homepage + mega-menu
 *     "Featured Residences" highlight operator-picked projects.
 *
 * Data sync lives in inc/projects-sync.php. Field meta keys are the contract
 * between the two files.
 *
 * @package luwipress-onyx
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

const LWP_ONYX_PROJECT_PT = 'arsha_project';

/** Meta keys — the sync writes these, the catalog reads them. */
const LWP_ONYX_PROJECT_META = array(
	'dld_id'        => '_arsha_dld_id',        // upstream DLD project id (sync key)
	'developer'     => '_arsha_developer',     // developer name (display)
	'price'         => '_arsha_price',         // AED price proxy (area/type avg) — number
	'beds'          => '_arsha_beds',          // bedrooms — number (0 = studio)
	'area_sqm'      => '_arsha_area_sqm',      // size m² — number
	'status'        => '_arsha_status',        // Ready | Off-plan | Under Construction
	'handover'      => '_arsha_handover',      // handover/completion date (string)
	'units'         => '_arsha_units',         // total units — number
	'progress'      => '_arsha_progress',      // build progress % — number
	'lat'           => '_arsha_lat',
	'lng'           => '_arsha_lng',
	'hero'          => '_arsha_hero',          // hero image URL (pre-sideload)
	'glyph'         => '_arsha_glyph',         // tower | interior | exterior (card placeholder)
	'rental_yield'  => '_arsha_rental_yield',  // gross % — MANUAL until arsha-connect exposes it
	'payment_plan'  => '_arsha_payment_plan',  // off-plan milestones — MANUAL / DLD detail
);

/** Listing "type" buckets the catalog filter expects (onyx.js contract). */
const LWP_ONYX_PROJECT_TYPES = array( 'Penthouse', 'Duplex', 'Apartment', 'Studio', 'Villa', 'Business' );

/**
 * Register the CPT + taxonomies through the LuwiPress CPT engine when present
 * (WPML/Polylang/Translation-Manager aware), else self-register.
 */
add_action( 'luwipress_cpt_engine_register', function ( $engine ) {
	if ( ! is_object( $engine ) || ! method_exists( $engine, 'register_type' ) ) {
		return;
	}
	$engine->register_type( 'arsha_projects', array(
		'post_type'      => LWP_ONYX_PROJECT_PT,
		'source'         => 'preset',
		'self_registers' => false, // let the engine register the CPT + taxonomies + meta
		'enabled'        => true,
		'labels'         => array(
			'singular'  => __( 'Project', 'luwipress-onyx' ),
			'plural'    => __( 'Projects', 'luwipress-onyx' ),
			'menu_icon' => 'dashicons-building',
		),
		'permalink'      => array(
			'archive_slug' => 'projects',
			'with_front'   => false,
		),
		'taxonomies'     => array(
			array( 'slug' => 'arsha_developer',      'label' => __( 'Developer', 'luwipress-onyx' ),    'hierarchical' => false, 'translatable' => false ),
			array( 'slug' => 'arsha_area',           'label' => __( 'Area', 'luwipress-onyx' ),         'hierarchical' => false, 'translatable' => true ),
			array( 'slug' => 'arsha_project_status', 'label' => __( 'Status', 'luwipress-onyx' ),       'hierarchical' => false, 'translatable' => false ),
			array( 'slug' => 'arsha_unit_type',      'label' => __( 'Unit Type', 'luwipress-onyx' ),    'hierarchical' => false, 'translatable' => false ),
		),
		'field_schema'   => array(
			array( 'key' => LWP_ONYX_PROJECT_META['developer'],    'type' => 'text',     'label' => __( 'Developer', 'luwipress-onyx' ),       'translatable' => false ),
			array( 'key' => LWP_ONYX_PROJECT_META['price'],        'type' => 'number',   'label' => __( 'Price from (AED)', 'luwipress-onyx' ), 'translatable' => false ),
			array( 'key' => LWP_ONYX_PROJECT_META['beds'],         'type' => 'number',   'label' => __( 'Bedrooms', 'luwipress-onyx' ),        'translatable' => false ),
			array( 'key' => LWP_ONYX_PROJECT_META['area_sqm'],     'type' => 'number',   'label' => __( 'Size (m²)', 'luwipress-onyx' ),       'translatable' => false ),
			array( 'key' => LWP_ONYX_PROJECT_META['status'],       'type' => 'text',     'label' => __( 'Status', 'luwipress-onyx' ),          'translatable' => false ),
			array( 'key' => LWP_ONYX_PROJECT_META['handover'],     'type' => 'text',     'label' => __( 'Handover', 'luwipress-onyx' ),        'translatable' => false ),
			array( 'key' => LWP_ONYX_PROJECT_META['units'],        'type' => 'number',   'label' => __( 'Total units', 'luwipress-onyx' ),     'translatable' => false ),
			array( 'key' => LWP_ONYX_PROJECT_META['rental_yield'], 'type' => 'number',   'label' => __( 'Gross rental yield (%)', 'luwipress-onyx' ), 'translatable' => false ),
			array( 'key' => LWP_ONYX_PROJECT_META['payment_plan'], 'type' => 'textarea', 'label' => __( 'Payment plan', 'luwipress-onyx' ),    'translatable' => true ),
		),
		'schema_mapping' => array( 'type' => 'residence' ),
	) );
} );

/**
 * Fallback registration when the CPT engine is unavailable (theme on a site
 * without the LuwiPress plugin). Engine path already covers the normal case;
 * post_type_exists() guards against double registration.
 */
add_action( 'init', function () {
	if ( post_type_exists( LWP_ONYX_PROJECT_PT ) ) {
		return; // engine (init p4) or a prior call already handled it
	}
	if ( class_exists( 'LuwiPress_CPT_Engine' ) ) {
		return; // engine present — let it own registration, don't race it
	}
	register_post_type( LWP_ONYX_PROJECT_PT, array(
		'labels'       => array(
			'name'          => __( 'Projects', 'luwipress-onyx' ),
			'singular_name' => __( 'Project', 'luwipress-onyx' ),
		),
		'public'       => true,
		'has_archive'  => 'projects',
		'menu_icon'    => 'dashicons-building',
		'rewrite'      => array( 'slug' => 'projects', 'with_front' => false ),
		'show_in_rest' => true,
		'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions' ),
	) );
	foreach ( array( 'arsha_developer', 'arsha_area', 'arsha_project_status', 'arsha_unit_type' ) as $tax ) {
		if ( ! taxonomy_exists( $tax ) ) {
			register_taxonomy( $tax, LWP_ONYX_PROJECT_PT, array(
				'public'            => true,
				'hierarchical'      => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
			) );
		}
	}
}, 6 );

/**
 * One-shot rewrite flush. The CPT is registered as a code preset (engine init
 * p4 or the p6 fallback) — neither path flushes rewrite rules, so the
 * /projects/ archive rule would be absent until an unrelated permalink save.
 * Flush once per rewrite version; re-armed on theme switch.
 */
add_action( 'init', function () {
	if ( get_option( 'lwp_onyx_projects_rewrite_v' ) !== '1' ) {
		flush_rewrite_rules( false );
		update_option( 'lwp_onyx_projects_rewrite_v', '1' );
	}
}, 99 );
add_action( 'after_switch_theme', function () {
	delete_option( 'lwp_onyx_projects_rewrite_v' );
} );

/* ────────────────────────────────────────────────────────────────────
 *  Featured registry — enable for arsha_project
 * ──────────────────────────────────────────────────────────────────── */
add_filter( 'luwipress_onyx_featured_post_types', function ( $types ) {
	$types = (array) $types;
	if ( ! in_array( LWP_ONYX_PROJECT_PT, $types, true ) ) {
		$types[] = LWP_ONYX_PROJECT_PT;
	}
	return $types;
} );

/* ────────────────────────────────────────────────────────────────────
 *  Catalog — feed the storefront listings grid from real projects
 *  (zero JS change: same JSON shape; adds a `url` field for deep-links).
 * ──────────────────────────────────────────────────────────────────── */

/**
 * Map one arsha_project post to the onyx.js listing shape.
 *
 * @param int|WP_Post $post
 * @return array<string,mixed>
 */
function lwp_onyx_project_to_listing( $post ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return array();
	}
	$id  = $post->ID;
	$get = function ( $key ) use ( $id ) {
		return get_post_meta( $id, LWP_ONYX_PROJECT_META[ $key ], true );
	};

	// Type/cat: prefer the unit_type term, else a meta hint, else 'Apartment'.
	$cat   = lwp_onyx_project_first_term( $id, 'arsha_unit_type' );
	if ( '' === $cat || ! in_array( $cat, LWP_ONYX_PROJECT_TYPES, true ) ) {
		$cat = 'Apartment';
	}
	$loc    = lwp_onyx_project_first_term( $id, 'arsha_area' );
	$status = lwp_onyx_project_first_term( $id, 'arsha_project_status' );
	if ( '' === $status ) {
		$status = (string) $get( 'status' );
	}
	// onyx.js status filter expects exactly "Ready" or "Off-plan".
	$status = ( stripos( $status, 'ready' ) !== false || stripos( $status, 'complete' ) !== false ) ? 'Ready' : 'Off-plan';

	$glyph = (string) $get( 'glyph' );
	if ( ! in_array( $glyph, array( 'tower', 'interior', 'exterior' ), true ) ) {
		$glyph = 'tower';
	}

	return array(
		'name'   => get_the_title( $id ),
		'cat'    => $cat,
		'loc'    => $loc !== '' ? $loc : (string) $get( 'developer' ),
		'beds'   => (int) $get( 'beds' ),
		'area'   => (int) $get( 'area_sqm' ),
		'price'  => (int) $get( 'price' ),
		'status' => $status,
		'glyph'  => $glyph,
		'desc'   => has_excerpt( $id ) ? wp_strip_all_tags( get_the_excerpt( $id ) ) : '',
		'url'    => get_permalink( $id ),
	);
}

/**
 * Fetch the rich DLD detail for a project by its DLD id, via the in-process
 * arsha-connect REST route (no HTTP round-trip). Cached 6h per project.
 *
 * Returns the full detail array (rental yield, gallery, escrow,
 * transactions_summary, property_type_mix, developer_rating, …) or array().
 *
 * @param int $dld_id
 * @return array<string,mixed>
 */
function lwp_onyx_project_detail( $dld_id ) {
	$dld_id = (int) $dld_id;
	if ( $dld_id <= 0 ) {
		return array();
	}
	$cache_key = 'lwp_onyx_pdetail_' . $dld_id;
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	if ( ! class_exists( 'WP_REST_Request' ) ) {
		return array();
	}
	$req = new WP_REST_Request( 'GET', '/arsha/v1/projects/' . $dld_id );
	$res = rest_do_request( $req );
	if ( ! $res || $res->is_error() ) {
		return array();
	}
	$data = (array) $res->get_data();
	set_transient( $cache_key, $data, 6 * HOUR_IN_SECONDS );
	return $data;
}

/** First term name for a taxonomy on a post, or '' . */
function lwp_onyx_project_first_term( $post_id, $taxonomy ) {
	$terms = get_the_terms( $post_id, $taxonomy );
	if ( is_array( $terms ) && ! empty( $terms ) && ! is_wp_error( $terms ) ) {
		return (string) $terms[0]->name;
	}
	return '';
}

/**
 * Replace the demo listings array with real projects when any exist.
 * Falls through to the theme's demo data on an empty catalog so the page is
 * never blank before the first sync.
 */
add_filter( 'luwipress_onyx_listings_data', function ( $demo ) {
	// Scope to the /projects/ archive only — don't silently swap data on any
	// other page an operator builds with the Listings page template.
	if ( ! is_post_type_archive( LWP_ONYX_PROJECT_PT ) ) {
		return $demo;
	}
	$q = new WP_Query( array(
		'post_type'      => LWP_ONYX_PROJECT_PT,
		'post_status'    => 'publish',
		'posts_per_page' => 200,
		'orderby'        => 'menu_order date',
		'order'          => 'DESC',
		'no_found_rows'  => true,
	) );
	if ( empty( $q->posts ) ) {
		return $demo;
	}
	$out = array();
	foreach ( $q->posts as $p ) {
		$row = lwp_onyx_project_to_listing( $p );
		if ( ! empty( $row ) ) {
			$out[] = $row;
		}
	}
	wp_reset_postdata();
	return $out ? $out : $demo;
}, 20 );

/* ────────────────────────────────────────────────────────────────────
 *  Mega menu + listings URL — point at the real /projects/ catalog page
 *  once a page using template-listings.php exists. Until then the existing
 *  market-insights fallback (template-helpers.php) stays in effect.
 * ──────────────────────────────────────────────────────────────────── */

/** URL of the projects catalog — the arsha_project post-type archive (/projects/). */
function lwp_onyx_projects_catalog_url() {
	$link = get_post_type_archive_link( LWP_ONYX_PROJECT_PT );
	return $link ? $link : '';
}

// Repoint the listings fallback to the catalog page when it exists.
add_filter( 'luwipress_onyx_listings_fallback_url', function ( $url ) {
	$catalog = lwp_onyx_projects_catalog_url();
	return $catalog !== '' ? $catalog : $url;
}, 20 );

// Rebuild the mega menu from real DLD data: top developers + top neighbourhoods,
// each deep-linking into the /projects/ catalog (the server catalog reads ?dev=
// and ?area= query vars). header.php renders column 1 ("By Developer") from
// 'types' and column 2 ("By Neighbourhood") from 'areas'.
add_filter( 'luwipress_onyx_mega_lists', function ( $lists ) {
	$base = lwp_onyx_projects_catalog_url();
	if ( '' === $base ) {
		return $lists;
	}
	$devs = get_terms( array( 'taxonomy' => 'arsha_developer', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 6 ) );
	$areas = get_terms( array( 'taxonomy' => 'arsha_area', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 6 ) );
	$tcol = array();
	if ( ! is_wp_error( $devs ) ) {
		foreach ( $devs as $t ) {
			$tcol[] = array( $t->name, add_query_arg( 'dev', $t->slug, $base ), (string) $t->count );
		}
	}
	$acol = array();
	if ( ! is_wp_error( $areas ) ) {
		foreach ( $areas as $t ) {
			$acol[] = array( $t->name, add_query_arg( 'area', $t->slug, $base ) );
		}
	}
	if ( $tcol ) { $lists['types'] = $tcol; }
	if ( $acol ) { $lists['areas'] = $acol; }
	return $lists;
}, 20 );

/**
 * Render a single project catalog card (shared by the archive's first page and
 * the REST infinite-scroll endpoint so markup stays identical).
 *
 * @param int $pid
 * @param int $i  running index (drives the placeholder glyph rotation)
 * @return string HTML
 */
function lwp_onyx_render_project_card( $pid, $i = 0 ) {
	$glyphs = array( 'tower', 'interior', 'exterior' );
	$area   = lwp_onyx_project_first_term( $pid, 'arsha_area' );
	$stat   = lwp_onyx_project_first_term( $pid, 'arsha_project_status' );
	$dev    = (string) get_post_meta( $pid, LWP_ONYX_PROJECT_META['developer'], true );
	$units  = (int) get_post_meta( $pid, LWP_ONYX_PROJECT_META['units'], true );
	$img    = get_the_post_thumbnail_url( $pid, 'large' );
	$glyph  = $glyphs[ $i % 3 ];
	ob_start();
	?>
	<a class="pcard" href="<?php echo esc_url( get_permalink( $pid ) ); ?>">
		<div class="pcard-media"<?php echo $img ? ' style="background-image:url(\'' . esc_url( $img ) . '\');background-size:cover;background-position:center"' : ''; ?>>
			<?php if ( $stat ) : ?><span class="pcard-cat"><?php echo esc_html( $stat ); ?></span><?php endif; ?>
			<?php if ( ! $img ) { echo onyx_ph( array( 'glyph' => $glyph ) ); } // phpcs:ignore ?>
		</div>
		<div class="pcard-body">
			<?php if ( $area ) : ?><div class="pcard-loc"><span class="ic"><?php echo onyx_icon( 'pin', 13 ); // phpcs:ignore ?></span><?php echo esc_html( $area ); ?></div><?php endif; ?>
			<h3><?php echo esc_html( get_the_title( $pid ) ); ?></h3>
			<?php if ( $dev ) : ?><div class="pcard-meta"><span><?php echo esc_html( $dev ); ?></span><?php if ( $units > 0 ) : ?><span><?php echo esc_html( number_format_i18n( $units ) . ' ' . __( 'units', 'luwipress-onyx' ) ); ?></span><?php endif; ?></div><?php endif; ?>
			<div class="pcard-foot"><div class="pcard-price"><small><?php esc_html_e( 'View project', 'luwipress-onyx' ); ?></small></div><span class="pcard-arrow"><?php echo onyx_icon( 'arrowUR', 16 ); // phpcs:ignore ?></span></div>
		</div>
	</a>
	<?php
	return ob_get_clean();
}

/**
 * Build WP_Query args for the catalog from filter params. Shared by the archive
 * (page 1) and the REST list endpoint (infinite scroll). The progress/units
 * sorts use a named meta clause so projects MISSING that meta are kept (sorted
 * last) rather than dropped by an INNER JOIN.
 *
 * @param array $params area[],type[],status,dev,q,sort
 * @param int   $paged
 * @param int   $per_page
 * @return array
 */
function lwp_onyx_projects_query_args( $params, $paged = 1, $per_page = 12 ) {
	$tax = array( 'relation' => 'AND' );
	if ( ! empty( $params['area'] ) )   { $tax[] = array( 'taxonomy' => 'arsha_area', 'field' => 'slug', 'terms' => (array) $params['area'] ); }
	if ( ! empty( $params['type'] ) )   { $tax[] = array( 'taxonomy' => 'arsha_unit_type', 'field' => 'slug', 'terms' => (array) $params['type'] ); }
	if ( ! empty( $params['status'] ) ) { $tax[] = array( 'taxonomy' => 'arsha_project_status', 'field' => 'slug', 'terms' => array( $params['status'] ) ); }
	if ( ! empty( $params['dev'] ) )    { $tax[] = array( 'taxonomy' => 'arsha_developer', 'field' => 'slug', 'terms' => array( $params['dev'] ) ); }

	$args = array(
		'post_type'      => LWP_ONYX_PROJECT_PT,
		'post_status'    => 'publish',
		'posts_per_page' => (int) $per_page,
		'paged'          => max( 1, (int) $paged ),
	);
	if ( count( $tax ) > 1 ) { $args['tax_query'] = $tax; }
	if ( ! empty( $params['q'] ) ) { $args['s'] = $params['q']; }

	switch ( $params['sort'] ?? 'recent' ) {
		case 'name':
			$args['orderby'] = 'title';
			$args['order']   = 'ASC';
			break;
		case 'progress':
		case 'units':
			$k = LWP_ONYX_PROJECT_META[ $params['sort'] ];
			$args['meta_query'] = array(
				'relation' => 'OR',
				'mk'       => array( 'key' => $k, 'type' => 'NUMERIC', 'compare' => 'EXISTS' ),
				array( 'key' => $k, 'compare' => 'NOT EXISTS' ),
			);
			$args['orderby'] = array( 'mk' => 'DESC', 'date' => 'DESC' );
			break;
		default:
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
	}
	return $args;
}

/** Sanitise raw filter params (from REST or $_GET) into the query-args shape. */
function lwp_onyx_projects_filter_params( $src ) {
	return array(
		'area'   => array_filter( array_map( 'sanitize_title', (array) ( $src['area'] ?? array() ) ) ),
		'type'   => array_filter( array_map( 'sanitize_title', (array) ( $src['type'] ?? array() ) ) ),
		'status' => sanitize_title( (string) ( $src['status'] ?? '' ) ),
		'dev'    => sanitize_title( (string) ( $src['dev'] ?? '' ) ),
		'q'      => sanitize_text_field( (string) ( $src['q'] ?? '' ) ),
		'sort'   => sanitize_key( (string) ( $src['sort'] ?? 'recent' ) ),
	);
}

/* ────────────────────────────────────────────────────────────────────
 *  REST — catalog list (infinite scroll) + project-name autocomplete
 * ──────────────────────────────────────────────────────────────────── */
add_action( 'rest_api_init', function () {
	register_rest_route( 'luwipress-onyx/v1', '/projects/list', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'callback'            => function ( WP_REST_Request $req ) {
			$params = lwp_onyx_projects_filter_params( $req->get_params() );
			$page   = max( 1, (int) $req->get_param( 'page' ) );
			$per    = 12;
			$q      = new WP_Query( lwp_onyx_projects_query_args( $params, $page, $per ) );
			$cards  = '';
			$i      = ( $page - 1 ) * $per;
			while ( $q->have_posts() ) {
				$q->the_post();
				$cards .= lwp_onyx_render_project_card( get_the_ID(), $i );
				$i++;
			}
			wp_reset_postdata();
			return rest_ensure_response( array(
				'cards' => $cards,
				'total' => (int) $q->found_posts,
				'page'  => $page,
				'pages' => (int) $q->max_num_pages,
			) );
		},
	) );

	register_rest_route( 'luwipress-onyx/v1', '/projects/suggest', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'args'                => array( 'q' => array( 'sanitize_callback' => 'sanitize_text_field' ) ),
		'callback'            => function ( WP_REST_Request $req ) {
			$q = (string) $req->get_param( 'q' );
			if ( mb_strlen( $q ) < 2 ) {
				return rest_ensure_response( array() );
			}
			$ids = get_posts( array(
				'post_type'      => LWP_ONYX_PROJECT_PT,
				'post_status'    => 'publish',
				'posts_per_page' => 8,
				's'              => $q,
				'no_found_rows'  => true,
				'fields'         => 'ids',
			) );
			$out = array();
			foreach ( $ids as $pid ) {
				$out[] = array(
					'title' => get_the_title( $pid ),
					'url'   => get_permalink( $pid ),
					'area'  => lwp_onyx_project_first_term( $pid, 'arsha_area' ),
				);
			}
			return rest_ensure_response( $out );
		},
	) );
} );

/* ────────────────────────────────────────────────────────────────────
 *  Retire the legacy duplicate `lwp_residence` system — 301 to /projects/
 *  (operator decision 2026-06-21: arsha_project is canonical).
 * ──────────────────────────────────────────────────────────────────── */
add_action( 'template_redirect', function () {
	if ( ! post_type_exists( 'lwp_residence' ) ) {
		return;
	}
	$is_res = is_singular( 'lwp_residence' )
		|| is_post_type_archive( 'lwp_residence' )
		|| is_tax( 'residence_area' )
		|| is_tax( 'residence_status' );
	if ( $is_res ) {
		$target = get_post_type_archive_link( LWP_ONYX_PROJECT_PT );
		if ( $target ) {
			wp_safe_redirect( $target, 301 );
			exit;
		}
	}
}, 1 );
