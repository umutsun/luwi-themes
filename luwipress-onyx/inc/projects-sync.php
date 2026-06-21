<?php
/**
 * ArshaHomes Projects — DLD sync (Phase 2: full /projects mirror).
 *
 * Mirrors all DLD projects into the `arsha_project` CPT via arsha-connect
 * (`arsha_connect_get('projects', …)`, paginated), keyed by DLD project id.
 * The catalog (archive-arsha_project.php) then renders server-side from the CPT
 * with native pagination + taxonomy filters — so the full 3766-project set
 * scales without loading everything client-side.
 *
 * Projects change rarely, so there is NO auto-cron — the operator runs a manual
 * "Sync from DLD" button (Projects ▸ Sync from DLD) which kicks a one-off
 * background WP-Cron pass. A REST trigger + WP-CLI path exist too.
 *
 * Field contract: inc/projects-cpt.php (LWP_ONYX_PROJECT_META + the four taxonomies).
 *
 * @package luwipress-onyx
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Upstream list resource (arsha-connect v1.2.0). */
const LWP_ONYX_PROJECTS_SOURCE = 'projects';

/**
 * Normalise a DLD status string into a clean human label. The feed sometimes
 * returns raw enum codes like "{PENDING_COMING_SOON}" / "{RERA_CANCELATION}"
 * which look like errors in the UI. Maps known codes and Title-cases the rest.
 *
 * @param string $s
 * @return string
 */
function lwp_onyx_clean_status( $s ) {
	$s = trim( (string) $s );
	if ( '' === $s ) {
		return '';
	}
	if ( preg_match( '/^\{?\s*([A-Za-z_]+)\s*\}?$/', $s, $mm ) && ( strpos( $s, '_' ) !== false || strpos( $s, '{' ) !== false ) ) {
		$code = strtoupper( $mm[1] );
		$map  = array(
			'COMPLETED'           => 'Completed',
			'UNDER_CONSTRUCTION'  => 'Under Construction',
			'CANCELLED'           => 'Cancelled',
			'CANCELED'            => 'Cancelled',
			'UNDER_CANCELLATION'  => 'Under Cancellation',
			'PENDING_COMING_SOON' => 'Coming Soon',
			'COMING_SOON'         => 'Coming Soon',
			'RERA_CANCELATION'    => 'RERA Cancellation',
			'RERA_CANCELLATION'   => 'RERA Cancellation',
			'PENDING'             => 'Pending',
		);
		if ( isset( $map[ $code ] ) ) {
			return $map[ $code ];
		}
		return ucwords( strtolower( str_replace( '_', ' ', $code ) ) );
	}
	return $s;
}

/**
 * Run a sync pass over the paginated upstream list. Idempotent (upsert by DLD id).
 *
 * @param array $opts per_page(≤48), max_pages, images(bool), since(ISO ''), status, area.
 * @return array stats
 */
function lwp_onyx_projects_sync( $opts = array() ) {
	$o = wp_parse_args( $opts, array(
		'per_page'  => 48,
		'max_pages' => 200,
		'images'    => false, // bulk image sideload off — heavy + many DLD URLs 404; featured/detail sideload on demand
		'since'     => '',
		'status'    => '',
		'area'      => '',
	) );
	$result = array( 'ok' => false, 'fetched' => 0, 'created' => 0, 'updated' => 0, 'images' => 0, 'pages' => 0, 'error' => '' );

	if ( ! function_exists( 'arsha_connect_get' ) ) {
		$result['error'] = 'arsha-connect plugin not active';
		return $result;
	}

	$per_page = max( 1, min( 48, (int) $o['per_page'] ) );
	$page     = 1;
	$pages    = 1;

	do {
		$args = array( 'page' => $page, 'per_page' => $per_page );
		foreach ( array( 'since', 'status', 'area' ) as $k ) {
			if ( '' !== (string) $o[ $k ] ) {
				$args[ $k ] = $o[ $k ];
			}
		}
		$data = arsha_connect_get( LWP_ONYX_PROJECTS_SOURCE, $args, true );
		if ( is_wp_error( $data ) ) {
			$result['error'] = $data->get_error_message();
			break;
		}
		$projects = ( is_array( $data ) && isset( $data['projects'] ) && is_array( $data['projects'] ) ) ? $data['projects'] : array();
		if ( 1 === $page && isset( $data['pages'] ) ) {
			$pages = max( 1, (int) $data['pages'] );
		}
		foreach ( $projects as $p ) {
			if ( empty( $p['id'] ) || empty( $p['name'] ) ) {
				continue;
			}
			$result['fetched']++;
			$res = lwp_onyx_projects_upsert_one( $p, (bool) $o['images'] );
			if ( 'created' === $res['op'] ) {
				$result['created']++;
			} elseif ( 'updated' === $res['op'] ) {
				$result['updated']++;
			}
			$result['images'] += $res['image'] ? 1 : 0;
		}
		$result['pages'] = $page;
		$page++;
		// Throttle: the upstream API shares this server's PHP-FPM pool — hammering
		// 79 pages back-to-back saturates FPM and 502s the live site. A short gap
		// between pages keeps the manual "Sync from DLD" button safe on production.
		if ( $page <= $pages && $page <= (int) $o['max_pages'] ) {
			usleep( 350000 ); // 0.35s
		}
	} while ( $page <= $pages && $page <= (int) $o['max_pages'] && ! $result['error'] );

	$result['ok'] = empty( $result['error'] );
	update_option( 'lwp_onyx_projects_last_sync', array( 'at' => time(), 'result' => $result ), false );
	return $result;
}

/**
 * Upsert one DLD project record into the CPT. Tolerant of both the v1.2.0
 * /projects payload (units_count, area_avg_price_aed) and the legacy /flagship
 * shape (units, from_price_aed).
 *
 * @return array{op:string,post_id:int,image:bool}
 */
function lwp_onyx_projects_upsert_one( array $p, $images = false ) {
	$dld_id = (string) $p['id'];

	$existing = get_posts( array(
		'post_type'        => LWP_ONYX_PROJECT_PT,
		'post_status'      => 'any',
		'posts_per_page'   => 1,
		'fields'           => 'ids',
		'meta_key'         => LWP_ONYX_PROJECT_META['dld_id'],
		'meta_value'       => $dld_id,
		'no_found_rows'    => true,
		'suppress_filters' => false,
	) );

	$postarr = array(
		'post_type'   => LWP_ONYX_PROJECT_PT,
		'post_title'  => sanitize_text_field( (string) $p['name'] ),
		'post_status' => 'publish',
	);

	if ( ! empty( $existing ) ) {
		$post_id       = (int) $existing[0];
		$postarr['ID'] = $post_id;
		$upd           = wp_update_post( $postarr, true );
		if ( is_wp_error( $upd ) ) {
			return array( 'op' => 'skipped', 'post_id' => $post_id, 'image' => false );
		}
		$op = 'updated';
	} else {
		$post_id = (int) wp_insert_post( $postarr, true );
		if ( $post_id <= 0 ) {
			return array( 'op' => 'skipped', 'post_id' => 0, 'image' => false );
		}
		$op = 'created';
	}

	$units = isset( $p['units_count'] ) ? $p['units_count'] : ( $p['units'] ?? null );
	$price = isset( $p['area_avg_price_aed'] ) ? $p['area_avg_price_aed'] : ( $p['from_price_aed'] ?? null );

	update_post_meta( $post_id, LWP_ONYX_PROJECT_META['dld_id'], $dld_id );
	if ( isset( $p['developer'] ) ) {
		update_post_meta( $post_id, LWP_ONYX_PROJECT_META['developer'], sanitize_text_field( (string) $p['developer'] ) );
	}
	if ( $price !== null && $price !== '' ) {
		update_post_meta( $post_id, LWP_ONYX_PROJECT_META['price'], (int) $price );
	}
	if ( $units !== null && $units !== '' ) {
		update_post_meta( $post_id, LWP_ONYX_PROJECT_META['units'], (int) $units );
	}
	if ( isset( $p['progress'] ) ) {
		update_post_meta( $post_id, LWP_ONYX_PROJECT_META['progress'], (float) $p['progress'] );
	}
	if ( isset( $p['handover'] ) ) {
		update_post_meta( $post_id, LWP_ONYX_PROJECT_META['handover'], sanitize_text_field( (string) $p['handover'] ) );
	}
	$status_clean = lwp_onyx_clean_status( isset( $p['status'] ) ? (string) $p['status'] : '' );
	if ( '' !== $status_clean ) {
		update_post_meta( $post_id, LWP_ONYX_PROJECT_META['status'], $status_clean );
	}
	if ( ! empty( $p['updated_at'] ) ) {
		update_post_meta( $post_id, '_arsha_updated', sanitize_text_field( (string) $p['updated_at'] ) );
	}
	foreach ( array( 'lat' => 'lat', 'lng' => 'lng', 'latitude' => 'lat', 'longitude' => 'lng' ) as $src => $dst ) {
		if ( isset( $p[ $src ] ) && is_numeric( $p[ $src ] ) ) {
			update_post_meta( $post_id, LWP_ONYX_PROJECT_META[ $dst ], (float) $p[ $src ] );
		}
	}
	if ( ! empty( $p['hero_image_url'] ) ) {
		update_post_meta( $post_id, LWP_ONYX_PROJECT_META['hero'], esc_url_raw( (string) $p['hero_image_url'] ) );
	}

	// Dominant property type (Unit / Building) from the mix.
	$ptype = '';
	if ( ! empty( $p['property_type_mix'] ) && is_array( $p['property_type_mix'] ) ) {
		$best = 0;
		foreach ( $p['property_type_mix'] as $m ) {
			$c = (int) ( $m['count'] ?? 0 );
			if ( $c >= $best && ! empty( $m['type'] ) ) {
				$best  = $c;
				$ptype = (string) $m['type'];
			}
		}
	}

	// Taxonomy terms — store the REAL DLD values.
	if ( ! empty( $p['developer'] ) ) {
		wp_set_object_terms( $post_id, sanitize_text_field( (string) $p['developer'] ), 'arsha_developer', false );
	}
	if ( ! empty( $p['area'] ) ) {
		wp_set_object_terms( $post_id, sanitize_text_field( (string) $p['area'] ), 'arsha_area', false );
	}
	if ( '' !== $status_clean ) {
		wp_set_object_terms( $post_id, $status_clean, 'arsha_project_status', false );
	}
	if ( '' !== $ptype ) {
		wp_set_object_terms( $post_id, sanitize_text_field( $ptype ), 'arsha_unit_type', false );
	}

	$did_image = false;
	if ( $images && ! has_post_thumbnail( $post_id ) && ! empty( $p['hero_image_url'] )
		&& ! get_post_meta( $post_id, '_arsha_hero_sideloaded', true ) ) {
		$attempts = (int) get_post_meta( $post_id, '_arsha_hero_attempts', true );
		if ( $attempts < 3 ) {
			$att = lwp_onyx_projects_sideload_hero( (string) $p['hero_image_url'], $post_id );
			if ( $att ) {
				update_post_meta( $post_id, '_arsha_hero_sideloaded', 1 );
				$did_image = true;
			} else {
				update_post_meta( $post_id, '_arsha_hero_attempts', $attempts + 1 );
			}
		}
	}

	return array( 'op' => $op, 'post_id' => $post_id, 'image' => $did_image );
}

/**
 * Sideload a hero image URL and set it as the post thumbnail. Hardened:
 * space-encode, http(s)+safe-host only (SSRF), real extension from bytes.
 *
 * @return int|false Attachment ID or false.
 */
function lwp_onyx_projects_sideload_hero( $url, $post_id ) {
	$url = str_replace( ' ', '%20', trim( (string) $url ) );

	if ( ! wp_http_validate_url( $url ) ) {
		return false;
	}
	$scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );
	if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
		return false;
	}

	if ( ! function_exists( 'download_url' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	if ( ! function_exists( 'media_handle_sideload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
	}
	if ( ! function_exists( 'wp_read_image_metadata' ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}

	add_filter( 'http_request_reject_unsafe_urls', '__return_true' );
	$tmp = download_url( $url, 25 );
	remove_filter( 'http_request_reject_unsafe_urls', '__return_true' );
	if ( is_wp_error( $tmp ) ) {
		return false;
	}

	$ext  = 'jpg';
	$info = @getimagesize( $tmp );
	if ( ! empty( $info['mime'] ) ) {
		$map = array( 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'image/avif' => 'avif' );
		if ( isset( $map[ $info['mime'] ] ) ) {
			$ext = $map[ $info['mime'] ];
		}
	}
	$file = array( 'name' => 'project-' . (int) $post_id . '-hero.' . $ext, 'tmp_name' => $tmp );
	$att  = media_handle_sideload( $file, $post_id, get_the_title( $post_id ) );
	if ( is_wp_error( $att ) ) {
		@unlink( $tmp );
		return false;
	}
	set_post_thumbnail( $post_id, (int) $att );
	return (int) $att;
}

/* ────────────────────────────────────────────────────────────────────
 *  Background full-sync event (kicked by the manual button)
 * ──────────────────────────────────────────────────────────────────── */
add_action( 'lwp_onyx_projects_sync_event', function ( $images = false ) {
	if ( function_exists( 'set_time_limit' ) ) { @set_time_limit( 0 ); }
	lwp_onyx_projects_sync( array( 'images' => (bool) $images ) );
}, 10, 1 );

/* ────────────────────────────────────────────────────────────────────
 *  Admin — "Sync from DLD" page + button (manual, operator-run)
 * ──────────────────────────────────────────────────────────────────── */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=' . LWP_ONYX_PROJECT_PT,
		__( 'Sync from DLD', 'luwipress-onyx' ),
		__( 'Sync from DLD', 'luwipress-onyx' ),
		'manage_options',
		'arsha-projects-sync',
		'lwp_onyx_projects_sync_page'
	);
} );

function lwp_onyx_projects_sync_page() {
	$last = get_option( 'lwp_onyx_projects_last_sync' );
	$running = (bool) wp_next_scheduled( 'lwp_onyx_projects_sync_event' );
	echo '<div class="wrap"><h1>' . esc_html__( 'Sync Projects from DLD', 'luwipress-onyx' ) . '</h1>';
	echo '<p>' . esc_html__( 'Imports/updates all DLD projects into the catalog. Projects change rarely — run this occasionally.', 'luwipress-onyx' ) . '</p>';

	if ( isset( $_GET['queued'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Sync started in the background. Refresh in a minute to see the result.', 'luwipress-onyx' ) . '</p></div>';
	}

	if ( is_array( $last ) && ! empty( $last['result'] ) ) {
		$r  = $last['result'];
		$ago = human_time_diff( (int) ( $last['at'] ?? time() ) ) . ' ' . __( 'ago', 'luwipress-onyx' );
		echo '<table class="widefat" style="max-width:560px;margin:16px 0"><tbody>';
		echo '<tr><th>' . esc_html__( 'Last run', 'luwipress-onyx' ) . '</th><td>' . esc_html( $ago ) . '</td></tr>';
		foreach ( array( 'fetched', 'created', 'updated', 'images', 'pages' ) as $k ) {
			echo '<tr><th>' . esc_html( ucfirst( $k ) ) . '</th><td>' . esc_html( (string) ( $r[ $k ] ?? 0 ) ) . '</td></tr>';
		}
		if ( ! empty( $r['error'] ) ) {
			echo '<tr><th>' . esc_html__( 'Error', 'luwipress-onyx' ) . '</th><td style="color:#b32d2e">' . esc_html( $r['error'] ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}

	$total = (int) wp_count_posts( LWP_ONYX_PROJECT_PT )->publish;
	echo '<p>' . sprintf( esc_html__( 'Projects in catalog now: %d', 'luwipress-onyx' ), $total ) . '</p>';

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="lwp_onyx_projects_sync">';
	wp_nonce_field( 'lwp_onyx_projects_sync' );
	echo '<label style="display:block;margin:10px 0"><input type="checkbox" name="images" value="1"> ' . esc_html__( 'Also download hero images (slower)', 'luwipress-onyx' ) . '</label>';
	submit_button( $running ? __( 'Sync running…', 'luwipress-onyx' ) : __( 'Sync now', 'luwipress-onyx' ), 'primary', 'submit', true, $running ? array( 'disabled' => 'disabled' ) : array() );
	echo '</form></div>';
}

add_action( 'admin_post_lwp_onyx_projects_sync', function () {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'lwp_onyx_projects_sync' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'luwipress-onyx' ) );
	}
	$images = ! empty( $_POST['images'] );
	if ( ! wp_next_scheduled( 'lwp_onyx_projects_sync_event' ) ) {
		wp_schedule_single_event( time() + 1, 'lwp_onyx_projects_sync_event', array( $images ) );
		spawn_cron();
	}
	wp_safe_redirect( add_query_arg( array( 'post_type' => LWP_ONYX_PROJECT_PT, 'page' => 'arsha-projects-sync', 'queued' => 1 ), admin_url( 'edit.php' ) ) );
	exit;
} );

/* ────────────────────────────────────────────────────────────────────
 *  REST — manual sync trigger (admin only); runs inline (use small since/limits)
 * ──────────────────────────────────────────────────────────────────── */
add_action( 'rest_api_init', function () {
	register_rest_route( 'luwipress-onyx/v1', '/projects/sync', array(
		'methods'             => 'POST',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		},
		'args'                => array(
			'per_page'  => array( 'type' => 'integer', 'default' => 48, 'sanitize_callback' => 'absint' ),
			'max_pages' => array( 'type' => 'integer', 'default' => 200, 'sanitize_callback' => 'absint' ),
			'images'    => array( 'type' => 'boolean', 'default' => false ),
			'since'     => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
		),
		'callback'            => function ( WP_REST_Request $req ) {
			if ( function_exists( 'set_time_limit' ) ) { @set_time_limit( 0 ); }
			return rest_ensure_response( lwp_onyx_projects_sync( array(
				'per_page'  => (int) $req->get_param( 'per_page' ),
				'max_pages' => (int) $req->get_param( 'max_pages' ),
				'images'    => (bool) $req->get_param( 'images' ),
				'since'     => (string) $req->get_param( 'since' ),
			) ) );
		},
	) );
} );
