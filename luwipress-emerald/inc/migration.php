<?php
/**
 * Migration Archive — gracefully retire legacy static pages and let the
 * theme's canonical structure take over their URLs.
 *
 * Two collision types are handled (hybrid model):
 *
 *   • Type A — WC archive collision
 *     Page slug matches a product_cat slug (e.g. /percussions/). Renaming
 *     the page to <slug>-legacy frees the URL; the WC product_cat archive
 *     (via the theme's enhanced archive-product.php) takes it over.
 *
 *   • Type B — Canonical shadow swap
 *     The theme ships shadow pages at *-gold slugs (about-gold, contact-gold,
 *     journal-gold, masters-gold, …) that mirror what the canonical slug
 *     SHOULD render. When a customer's legacy page holds the canonical slug,
 *     this archives the customer page to <slug>-legacy and renames our
 *     shadow from *-gold to the canonical slug in one atomic swap.
 *
 * Both types share one registry, one browser, and one restore flow. Restore
 * for Type B reverses the whole pair (customer page back to canonical, our
 * shadow back to *-gold).
 *
 * Lossless principle: nothing is deleted, no widget data is touched, no
 * meta is stripped. The customer can always restore a page — the registry
 * holds the original_slug pointer (and shadow_id for Type B).
 *
 * @package luwipress-emerald
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LuwiPress_Emerald_Migration' ) ) :

class LuwiPress_Emerald_Migration {

	const PAGE_SLUG       = 'luwipress-emerald-migration';
	const ARCHIVE_OPTION  = 'luwipress_emerald_migration_archive';
	const NONCE_ACTION    = 'luwipress_emerald_migration';
	const CAPABILITY      = 'manage_options';
	const DEFAULT_SUFFIX  = 'legacy';

	private static $instance;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu',                                        [ $this, 'register_admin_page' ], 11 );
		add_action( 'admin_enqueue_scripts',                             [ $this, 'enqueue_assets' ] );
		add_action( 'wp_ajax_luwipress_emerald_migration_scan',             [ $this, 'ajax_scan' ] );
		add_action( 'wp_ajax_luwipress_emerald_migration_archive',          [ $this, 'ajax_archive' ] );
		add_action( 'wp_ajax_luwipress_emerald_migration_swap',             [ $this, 'ajax_swap' ] );
		add_action( 'wp_ajax_luwipress_emerald_migration_restore',          [ $this, 'ajax_restore' ] );
	}

	/**
	 * Auto-detected canonical reserves: any page whose slug ends in
	 * `-gold` is treated as our shadow for the base slug. Filter
	 * `luwipress_emerald_canonical_reserves` to add bespoke pairs (e.g. when
	 * the shadow lives at a non `-gold` slug like `about-tapadum`).
	 *
	 * @return array<string, int> map of canonical_slug => shadow_page_id
	 */
	public function get_canonical_reserves() {
		$pairs = [];
		$shadows = get_posts( [
			'post_type'      => 'page',
			'post_status'    => [ 'publish', 'draft', 'private', 'pending' ],
			'posts_per_page' => -1,
			'fields'         => 'ids',
		] );
		foreach ( $shadows as $sid ) {
			$p = get_post( $sid );
			if ( ! $p || empty( $p->post_name ) ) continue;
			if ( substr( $p->post_name, -5 ) !== '-gold' ) continue;
			$canonical = substr( $p->post_name, 0, -5 );
			if ( '' === $canonical ) continue;
			$pairs[ $canonical ] = (int) $sid;
		}
		/**
		 * Add or override canonical reserves.
		 *
		 * @param array<string, int> $pairs canonical_slug => shadow_page_id
		 */
		return apply_filters( 'luwipress_emerald_canonical_reserves', $pairs );
	}

	public function register_admin_page() {
		add_theme_page(
			__( 'LuwiPress Migration', 'luwipress-emerald' ),
			__( 'LuwiPress Migration', 'luwipress-emerald' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			[ $this, 'render_admin_page' ]
		);
	}

	public function enqueue_assets( $hook ) {
		if ( 'appearance_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style(
			'luwipress-emerald-ecosystem',
			LUWIPRESS_EMERALD_URI . '/assets/css/ecosystem-dashboard.css',
			[],
			LUWIPRESS_EMERALD_VERSION
		);
	}

	/* ───────────────────────────────────────────────────────────────── *
	 * Detection                                                          *
	 * ───────────────────────────────────────────────────────────────── */

	/**
	 * Find pages whose slug collides with a product_cat term slug.
	 *
	 * Pages already in the archive registry are skipped — once archived,
	 * the slug is `<orig>-legacy` and no longer collides; rescanning
	 * shouldn't re-flag them.
	 *
	 * @return array<int, array{page_id:int, slug:string, title:string, status:string, term_id:int, term_name:string, product_count:int, lang:string}>
	 */
	public function scan_collisions() {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return [];
		}

		$terms = get_terms( [
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'fields'     => 'all',
		] );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return [];
		}

		$by_slug = [];
		foreach ( $terms as $t ) {
			$by_slug[ $t->slug ] = $t;
		}

		$archived_ids = wp_list_pluck( $this->get_archive_registry(), 'page_id' );
		$archived_ids = array_map( 'intval', (array) $archived_ids );

		$collisions = [];
		$pages = get_posts( [
			'post_type'      => 'page',
			'post_status'    => [ 'publish', 'draft', 'private', 'pending' ],
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
			'suppress_filters' => false,
		] );
		foreach ( $pages as $page_id ) {
			$page = get_post( $page_id );
			if ( ! $page || empty( $page->post_name ) ) {
				continue;
			}
			if ( in_array( (int) $page_id, $archived_ids, true ) ) {
				continue;
			}
			if ( ! isset( $by_slug[ $page->post_name ] ) ) {
				continue;
			}
			$term = $by_slug[ $page->post_name ];
			$lang = '';
			if ( function_exists( 'apply_filters' ) ) {
				$lang_info = apply_filters( 'wpml_post_language_details', null, (int) $page_id );
				if ( is_array( $lang_info ) && ! empty( $lang_info['language_code'] ) ) {
					$lang = (string) $lang_info['language_code'];
				}
			}
			$collisions[] = [
				'page_id'       => (int) $page_id,
				'slug'          => $page->post_name,
				'title'         => $page->post_title,
				'status'        => $page->post_status,
				'term_id'       => (int) $term->term_id,
				'term_name'     => $term->name,
				'product_count' => (int) $term->count,
				'lang'          => $lang,
			];
		}

		return $collisions;
	}

	/**
	 * Find Type B candidates — customer pages whose slug matches a
	 * canonical reserve where we ALSO have a shadow page ready to take
	 * the slot.
	 *
	 * Already-archived pages and the shadow pages themselves are skipped.
	 *
	 * @return array<int, array{canonical_slug:string, customer_page_id:int, customer_title:string, customer_status:string, shadow_page_id:int, shadow_title:string, lang:string}>
	 */
	public function scan_canonical_swaps() {
		$reserves = $this->get_canonical_reserves();
		if ( empty( $reserves ) ) {
			return [];
		}

		$archived_ids = array_map( 'intval', wp_list_pluck( $this->get_archive_registry(), 'page_id' ) );

		$out = [];
		foreach ( $reserves as $canonical => $shadow_id ) {
			$shadow_id = (int) $shadow_id;
			$shadow    = get_post( $shadow_id );
			if ( ! $shadow ) continue;

			// Find the customer page sitting on the canonical slug.
			$customer = $this->find_page_with_slug( $canonical, $shadow_id );
			if ( ! $customer ) continue;
			if ( in_array( (int) $customer->ID, $archived_ids, true ) ) continue;

			$lang = '';
			$lang_info = apply_filters( 'wpml_post_language_details', null, (int) $customer->ID );
			if ( is_array( $lang_info ) && ! empty( $lang_info['language_code'] ) ) {
				$lang = (string) $lang_info['language_code'];
			}

			$out[] = [
				'canonical_slug'    => $canonical,
				'customer_page_id'  => (int) $customer->ID,
				'customer_title'    => $customer->post_title,
				'customer_status'   => $customer->post_status,
				'shadow_page_id'    => $shadow_id,
				'shadow_title'      => $shadow->post_title,
				'shadow_slug'       => $shadow->post_name,
				'lang'              => $lang,
			];
		}
		return $out;
	}

	/* ───────────────────────────────────────────────────────────────── *
	 * Archive (rename)                                                    *
	 * ───────────────────────────────────────────────────────────────── */

	/**
	 * Rename pages to <slug>-<suffix> and record in the archive registry.
	 *
	 * @param array<int, int> $page_ids
	 * @param string          $suffix Default 'legacy'.
	 * @return array{ok:array, failed:array}
	 */
	public function archive_pages( array $page_ids, $suffix = self::DEFAULT_SUFFIX ) {
		$suffix = sanitize_title( $suffix );
		if ( '' === $suffix ) {
			$suffix = self::DEFAULT_SUFFIX;
		}

		$ok       = [];
		$failed   = [];
		$registry = $this->get_archive_registry();

		foreach ( $page_ids as $page_id ) {
			$page_id = (int) $page_id;
			$page    = get_post( $page_id );
			if ( ! $page || 'page' !== $page->post_type ) {
				$failed[] = [ 'page_id' => $page_id, 'reason' => 'not-found' ];
				continue;
			}

			$old_slug = $page->post_name;
			$new_slug = $this->resolve_unique_slug( $old_slug . '-' . $suffix, $page_id );

			$result = wp_update_post( [
				'ID'        => $page_id,
				'post_name' => $new_slug,
			], true );
			if ( is_wp_error( $result ) ) {
				$failed[] = [ 'page_id' => $page_id, 'reason' => $result->get_error_message() ];
				continue;
			}

			update_post_meta( $page_id, '_luwipress_emerald_archived_at', time() );
			update_post_meta( $page_id, '_luwipress_emerald_original_slug', $old_slug );

			$registry[] = [
				'page_id'            => $page_id,
				'original_slug'      => $old_slug,
				'current_slug'       => $new_slug,
				'archived_at'        => time(),
				'original_title'     => $page->post_title,
				'original_status'    => $page->post_status,
				'reason'             => 'product_cat_collision',
			];

			$ok[] = [
				'page_id'   => $page_id,
				'old_slug'  => $old_slug,
				'new_slug'  => $new_slug,
				'title'     => $page->post_title,
			];
		}

		update_option( self::ARCHIVE_OPTION, $registry, false );
		$this->purge_caches();

		return [ 'ok' => $ok, 'failed' => $failed ];
	}

	/**
	 * Execute Type B swaps. For each pair:
	 *   1. Customer page (currently at canonical slug) → renamed to <canonical>-legacy
	 *   2. Our shadow page (currently at *-gold) → renamed to canonical
	 *
	 * Both moves go through wp_update_post; if step 1 succeeds but step 2
	 * fails, step 1 is rolled back to keep the slot consistent.
	 *
	 * @param array<int, int> $customer_page_ids Customer page IDs to swap.
	 * @param string          $suffix
	 * @return array{ok:array, failed:array}
	 */
	public function swap_canonical_pairs( array $customer_page_ids, $suffix = self::DEFAULT_SUFFIX ) {
		$suffix = sanitize_title( $suffix );
		if ( '' === $suffix ) {
			$suffix = self::DEFAULT_SUFFIX;
		}

		$pairs    = $this->scan_canonical_swaps();
		$by_pid   = [];
		foreach ( $pairs as $p ) {
			$by_pid[ $p['customer_page_id'] ] = $p;
		}

		$ok       = [];
		$failed   = [];
		$registry = $this->get_archive_registry();

		foreach ( $customer_page_ids as $cust_id ) {
			$cust_id = (int) $cust_id;
			if ( ! isset( $by_pid[ $cust_id ] ) ) {
				$failed[] = [ 'page_id' => $cust_id, 'reason' => 'pair-not-found' ];
				continue;
			}
			$pair      = $by_pid[ $cust_id ];
			$customer  = get_post( $cust_id );
			$shadow_id = (int) $pair['shadow_page_id'];
			$canonical = $pair['canonical_slug'];

			if ( ! $customer ) {
				$failed[] = [ 'page_id' => $cust_id, 'reason' => 'not-found' ];
				continue;
			}

			// 1) Move customer to <canonical>-<suffix>.
			$archived_slug = $this->resolve_unique_slug( $canonical . '-' . $suffix, $cust_id );
			$r1 = wp_update_post( [ 'ID' => $cust_id, 'post_name' => $archived_slug ], true );
			if ( is_wp_error( $r1 ) ) {
				$failed[] = [ 'page_id' => $cust_id, 'reason' => 'rename-customer: ' . $r1->get_error_message() ];
				continue;
			}

			// 2) Move our shadow into the canonical slot.
			$r2 = wp_update_post( [ 'ID' => $shadow_id, 'post_name' => $canonical ], true );
			if ( is_wp_error( $r2 ) ) {
				// Rollback step 1 so we don't leave the canonical slot empty.
				wp_update_post( [ 'ID' => $cust_id, 'post_name' => $canonical ] );
				$failed[] = [ 'page_id' => $cust_id, 'reason' => 'rename-shadow: ' . $r2->get_error_message() ];
				continue;
			}

			update_post_meta( $cust_id, '_luwipress_emerald_archived_at', time() );
			update_post_meta( $cust_id, '_luwipress_emerald_original_slug', $canonical );
			update_post_meta( $shadow_id, '_luwipress_emerald_promoted_at', time() );
			update_post_meta( $shadow_id, '_luwipress_emerald_shadow_slug', $pair['shadow_slug'] );

			$registry[] = [
				'page_id'         => $cust_id,
				'original_slug'   => $canonical,
				'current_slug'    => $archived_slug,
				'archived_at'     => time(),
				'original_title'  => $customer->post_title,
				'original_status' => $customer->post_status,
				'reason'          => 'canonical_swap',
				'shadow_page_id'  => $shadow_id,
				'shadow_original_slug' => $pair['shadow_slug'],
			];

			$ok[] = [
				'customer_page_id' => $cust_id,
				'customer_old'     => $canonical,
				'customer_new'     => $archived_slug,
				'shadow_page_id'   => $shadow_id,
				'shadow_old'       => $pair['shadow_slug'],
				'shadow_new'       => $canonical,
			];
		}

		update_option( self::ARCHIVE_OPTION, $registry, false );
		$this->purge_caches();

		return [ 'ok' => $ok, 'failed' => $failed ];
	}

	/**
	 * Restore a single archived page to its original slug.
	 *
	 * For Type A archives: simply renames the page back to its original
	 * slug. If the original slug is currently taken by another page, the
	 * restore is rejected and the operator must resolve manually.
	 *
	 * For Type B (canonical_swap): reverses the pair — moves our shadow
	 * out of the canonical slot back to its *-gold slug, then moves the
	 * customer page back to canonical.
	 */
	public function restore_page( $page_id ) {
		$page_id  = (int) $page_id;
		$registry = $this->get_archive_registry();
		$found    = null;
		$idx      = null;
		foreach ( $registry as $i => $row ) {
			if ( (int) $row['page_id'] === $page_id ) {
				$found = $row;
				$idx   = $i;
				break;
			}
		}
		if ( ! $found ) {
			return new WP_Error( 'not-archived', __( 'Page is not in the archive registry.', 'luwipress-emerald' ) );
		}

		$page = get_post( $page_id );
		if ( ! $page ) {
			return new WP_Error( 'not-found', __( 'Page no longer exists.', 'luwipress-emerald' ) );
		}

		$target  = $found['original_slug'];
		$is_swap = ( isset( $found['reason'] ) && 'canonical_swap' === $found['reason'] && ! empty( $found['shadow_page_id'] ) );

		// For canonical_swap: first move our shadow OUT of the canonical
		// slot (back to its *-gold slug) so the customer page can take
		// canonical back without colliding.
		if ( $is_swap ) {
			$shadow_id   = (int) $found['shadow_page_id'];
			$shadow_back = isset( $found['shadow_original_slug'] ) ? $found['shadow_original_slug'] : ( $target . '-gold' );
			$shadow_back = $this->resolve_unique_slug( $shadow_back, $shadow_id );
			$rs = wp_update_post( [ 'ID' => $shadow_id, 'post_name' => $shadow_back ], true );
			if ( is_wp_error( $rs ) ) {
				return $rs;
			}
			delete_post_meta( $shadow_id, '_luwipress_emerald_promoted_at' );
			delete_post_meta( $shadow_id, '_luwipress_emerald_shadow_slug' );
		} else {
			$blocker = $this->find_page_with_slug( $target, $page_id );
			if ( $blocker ) {
				return new WP_Error(
					'slug-blocked',
					sprintf(
						/* translators: 1: target slug, 2: blocking page id, 3: blocker title */
						__( 'Original slug "%1$s" is currently held by another page (#%2$d "%3$s"). Move that one out of the way first.', 'luwipress-emerald' ),
						$target,
						(int) $blocker->ID,
						$blocker->post_title
					)
				);
			}
		}

		$result = wp_update_post( [
			'ID'        => $page_id,
			'post_name' => $target,
		], true );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		delete_post_meta( $page_id, '_luwipress_emerald_archived_at' );
		delete_post_meta( $page_id, '_luwipress_emerald_original_slug' );

		array_splice( $registry, $idx, 1 );
		update_option( self::ARCHIVE_OPTION, $registry, false );
		$this->purge_caches();

		return [
			'page_id'  => $page_id,
			'restored' => $target,
			'reverted_swap' => $is_swap,
		];
	}

	/* ───────────────────────────────────────────────────────────────── *
	 * Helpers                                                            *
	 * ───────────────────────────────────────────────────────────────── */

	public function get_archive_registry() {
		$reg = get_option( self::ARCHIVE_OPTION, [] );
		return is_array( $reg ) ? $reg : [];
	}

	private function resolve_unique_slug( $candidate, $exclude_id = 0 ) {
		$candidate = sanitize_title( $candidate );
		$try       = $candidate;
		$n         = 2;
		while ( $this->find_page_with_slug( $try, (int) $exclude_id ) ) {
			$try = $candidate . '-' . $n;
			$n++;
			if ( $n > 50 ) break;
		}
		return $try;
	}

	private function find_page_with_slug( $slug, $exclude_id = 0 ) {
		$slug  = sanitize_title( $slug );
		$pages = get_posts( [
			'post_type'      => 'page',
			'post_status'    => [ 'publish', 'draft', 'private', 'pending', 'future' ],
			'name'           => $slug,
			'posts_per_page' => 1,
			'exclude'        => $exclude_id ? [ (int) $exclude_id ] : [],
		] );
		return ! empty( $pages ) ? $pages[0] : null;
	}

	private function purge_caches() {
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
		do_action( 'litespeed_purge_all' );
		do_action( 'rocket_clean_domain' );
	}

	/* ───────────────────────────────────────────────────────────────── *
	 * AJAX                                                                *
	 * ───────────────────────────────────────────────────────────────── */

	public function ajax_scan() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => __( 'Forbidden', 'luwipress-emerald' ) ], 403 );
		}
		wp_send_json_success( [
			'collisions' => $this->scan_collisions(),
		] );
	}

	public function ajax_archive() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => __( 'Forbidden', 'luwipress-emerald' ) ], 403 );
		}
		$ids    = isset( $_POST['ids'] ) ? array_map( 'intval', (array) $_POST['ids'] ) : [];
		$suffix = isset( $_POST['suffix'] ) ? sanitize_title( wp_unslash( $_POST['suffix'] ) ) : self::DEFAULT_SUFFIX;
		if ( empty( $ids ) ) {
			wp_send_json_error( [ 'message' => __( 'No pages selected.', 'luwipress-emerald' ) ] );
		}
		$result = $this->archive_pages( $ids, $suffix );
		wp_send_json_success( $result );
	}

	public function ajax_swap() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => __( 'Forbidden', 'luwipress-emerald' ) ], 403 );
		}
		$ids    = isset( $_POST['ids'] ) ? array_map( 'intval', (array) $_POST['ids'] ) : [];
		$suffix = isset( $_POST['suffix'] ) ? sanitize_title( wp_unslash( $_POST['suffix'] ) ) : self::DEFAULT_SUFFIX;
		if ( empty( $ids ) ) {
			wp_send_json_error( [ 'message' => __( 'No pairs selected.', 'luwipress-emerald' ) ] );
		}
		$result = $this->swap_canonical_pairs( $ids, $suffix );
		wp_send_json_success( $result );
	}

	public function ajax_restore() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => __( 'Forbidden', 'luwipress-emerald' ) ], 403 );
		}
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		if ( ! $id ) {
			wp_send_json_error( [ 'message' => __( 'Missing page id.', 'luwipress-emerald' ) ] );
		}
		$result = $this->restore_page( $id );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}
		wp_send_json_success( $result );
	}

	/* ───────────────────────────────────────────────────────────────── *
	 * UI                                                                  *
	 * ───────────────────────────────────────────────────────────────── */

	public function render_admin_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'luwipress-emerald' ) );
		}

		$collisions = $this->scan_collisions();
		$swaps      = $this->scan_canonical_swaps();
		$registry   = $this->get_archive_registry();
		$nonce      = wp_create_nonce( self::NONCE_ACTION );
		$ajax_url   = admin_url( 'admin-ajax.php' );
		?>
		<div class="wrap lwp-emerald-eco">
			<h1><?php esc_html_e( 'LuwiPress Migration Archive', 'luwipress-emerald' ); ?></h1>
			<p class="lwp-emerald-eco__lead"><?php esc_html_e(
				'Gracefully retire legacy static pages whose URLs collide with the theme’s canonical archive structure. Renamed pages are kept published at <slug>-legacy so you can pull content from them, and a one-click restore brings the original slug back any time.',
				'luwipress-emerald'
			); ?></p>

			<section class="lwp-emerald-eco__card" style="margin-top:24px;padding:20px 24px;background:#fff;border:1px solid #e8e2d3;border-radius:8px;">
				<h2 style="margin:0 0 8px;font-size:18px;"><?php esc_html_e( 'Detected slug collisions', 'luwipress-emerald' ); ?></h2>
				<p style="margin:0 0 16px;color:#4d4635;"><?php
				/* translators: %d: number of collisions */
				printf( esc_html( _n(
					'%d page has a slug that matches a WooCommerce product category. Archive the page to free the URL — the WooCommerce category archive will take over.',
					'%d pages have slugs that match WooCommerce product categories. Archive selected pages to free their URLs — the WooCommerce category archive will take over.',
					count( $collisions ),
					'luwipress-emerald'
				) ), count( $collisions ) ); ?></p>

				<?php if ( empty( $collisions ) ) : ?>
					<p style="padding:18px;background:#f6f3f2;border-radius:6px;color:#4d4635;">
						<?php esc_html_e( '✓ No collisions found. Your slug structure is already canonical.', 'luwipress-emerald' ); ?>
					</p>
				<?php else : ?>
					<form id="lwp-emerald-migration-form">
						<table class="widefat striped" style="margin-top:8px;">
							<thead>
								<tr>
									<th style="width:36px;"><input type="checkbox" id="lwp-emerald-migration-all" /></th>
									<th><?php esc_html_e( 'Page', 'luwipress-emerald' ); ?></th>
									<th><?php esc_html_e( 'Slug', 'luwipress-emerald' ); ?></th>
									<th><?php esc_html_e( 'Lang', 'luwipress-emerald' ); ?></th>
									<th><?php esc_html_e( 'Will become', 'luwipress-emerald' ); ?></th>
									<th><?php esc_html_e( 'Term · products', 'luwipress-emerald' ); ?></th>
									<th><?php esc_html_e( 'Status', 'luwipress-emerald' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $collisions as $c ) : ?>
									<tr>
										<td><input type="checkbox" class="lwp-emerald-migration-row" value="<?php echo (int) $c['page_id']; ?>" /></td>
										<td>
											<a href="<?php echo esc_url( get_edit_post_link( $c['page_id'] ) ); ?>"><?php echo esc_html( $c['title'] ); ?></a>
											<br><small style="color:#7f7663;">#<?php echo (int) $c['page_id']; ?> · <a href="<?php echo esc_url( get_permalink( $c['page_id'] ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'view', 'luwipress-emerald' ); ?></a></small>
										</td>
										<td><code><?php echo esc_html( $c['slug'] ); ?></code></td>
										<td><?php echo $c['lang'] ? '<code>' . esc_html( $c['lang'] ) . '</code>' : '<span style="color:#7f7663;">—</span>'; ?></td>
										<td><code><?php echo esc_html( $c['slug'] . '-legacy' ); ?></code></td>
										<td><?php echo esc_html( $c['term_name'] ); ?> · <?php echo (int) $c['product_count']; ?></td>
										<td><?php echo esc_html( $c['status'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>

						<div style="margin-top:16px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
							<label>
								<?php esc_html_e( 'Suffix:', 'luwipress-emerald' ); ?>
								<input type="text" id="lwp-emerald-migration-suffix" value="legacy" style="width:120px;margin-left:6px;" />
							</label>
							<button type="button" class="button button-primary" id="lwp-emerald-migration-execute">
								<?php esc_html_e( 'Archive selected', 'luwipress-emerald' ); ?>
							</button>
							<span id="lwp-emerald-migration-status" style="color:#4d4635;"></span>
						</div>
					</form>
				<?php endif; ?>
			</section>

			<section class="lwp-emerald-eco__card" style="margin-top:24px;padding:20px 24px;background:#fff;border:1px solid #e8e2d3;border-radius:8px;">
				<h2 style="margin:0 0 8px;font-size:18px;"><?php esc_html_e( 'Canonical reserves', 'luwipress-emerald' ); ?> <small style="font-weight:400;color:#7f7663;">— <?php esc_html_e( 'Type B · shadow-page swap', 'luwipress-emerald' ); ?></small></h2>
				<p style="margin:0 0 16px;color:#4d4635;"><?php esc_html_e( 'These slugs (about, contact, journal, masters, …) belong to the theme’s canonical structure and the theme has shipped a *-gold shadow page for each. Run the swap to archive the customer’s legacy page and promote our shadow into the canonical slot in one atomic move.', 'luwipress-emerald' ); ?></p>

				<?php if ( empty( $swaps ) ) : ?>
					<p style="padding:18px;background:#f6f3f2;border-radius:6px;color:#4d4635;">
						<?php esc_html_e( '✓ No canonical-reserve swaps pending. Either no shadow pages exist, or the canonical slugs are already ours.', 'luwipress-emerald' ); ?>
					</p>
				<?php else : ?>
					<form id="lwp-emerald-swap-form">
						<table class="widefat striped" style="margin-top:8px;">
							<thead>
								<tr>
									<th style="width:36px;"><input type="checkbox" id="lwp-emerald-swap-all" /></th>
									<th><?php esc_html_e( 'Customer page', 'luwipress-emerald' ); ?></th>
									<th><?php esc_html_e( 'Holds slug', 'luwipress-emerald' ); ?></th>
									<th><?php esc_html_e( 'Lang', 'luwipress-emerald' ); ?></th>
									<th><?php esc_html_e( 'Our shadow', 'luwipress-emerald' ); ?></th>
									<th><?php esc_html_e( 'After swap', 'luwipress-emerald' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $swaps as $s ) : ?>
									<tr>
										<td><input type="checkbox" class="lwp-emerald-swap-row" value="<?php echo (int) $s['customer_page_id']; ?>" /></td>
										<td>
											<a href="<?php echo esc_url( get_edit_post_link( $s['customer_page_id'] ) ); ?>"><?php echo esc_html( $s['customer_title'] ); ?></a>
											<br><small style="color:#7f7663;">#<?php echo (int) $s['customer_page_id']; ?></small>
										</td>
										<td><code><?php echo esc_html( $s['canonical_slug'] ); ?></code></td>
										<td><?php echo $s['lang'] ? '<code>' . esc_html( $s['lang'] ) . '</code>' : '<span style="color:#7f7663;">—</span>'; ?></td>
										<td>
											<a href="<?php echo esc_url( get_edit_post_link( $s['shadow_page_id'] ) ); ?>"><?php echo esc_html( $s['shadow_title'] ); ?></a>
											<br><small style="color:#7f7663;">#<?php echo (int) $s['shadow_page_id']; ?> · <code><?php echo esc_html( $s['shadow_slug'] ); ?></code></small>
										</td>
										<td>
											<small style="color:#4d4635;">customer → <code><?php echo esc_html( $s['canonical_slug'] . '-legacy' ); ?></code></small><br>
											<small style="color:#4d4635;">shadow  → <code><?php echo esc_html( $s['canonical_slug'] ); ?></code></small>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>

						<div style="margin-top:16px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
							<button type="button" class="button button-primary" id="lwp-emerald-swap-execute">
								<?php esc_html_e( 'Swap selected pairs', 'luwipress-emerald' ); ?>
							</button>
							<span id="lwp-emerald-swap-status" style="color:#4d4635;"></span>
						</div>
					</form>
				<?php endif; ?>
			</section>

			<section class="lwp-emerald-eco__card" style="margin-top:24px;padding:20px 24px;background:#fff;border:1px solid #e8e2d3;border-radius:8px;">
				<h2 style="margin:0 0 8px;font-size:18px;"><?php esc_html_e( 'Archive browser', 'luwipress-emerald' ); ?></h2>
				<p style="margin:0 0 16px;color:#4d4635;"><?php esc_html_e( 'Pages you’ve archived. Each one is still published at its <slug>-legacy URL — pull content from it any time, or restore the original slug with one click.', 'luwipress-emerald' ); ?></p>

				<?php if ( empty( $registry ) ) : ?>
					<p style="padding:18px;background:#f6f3f2;border-radius:6px;color:#4d4635;">
						<?php esc_html_e( 'No pages archived yet.', 'luwipress-emerald' ); ?>
					</p>
				<?php else : ?>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Page', 'luwipress-emerald' ); ?></th>
								<th><?php esc_html_e( 'Type', 'luwipress-emerald' ); ?></th>
								<th><?php esc_html_e( 'Original slug', 'luwipress-emerald' ); ?></th>
								<th><?php esc_html_e( 'Current slug', 'luwipress-emerald' ); ?></th>
								<th><?php esc_html_e( 'Archived', 'luwipress-emerald' ); ?></th>
								<th style="width:180px;"><?php esc_html_e( 'Actions', 'luwipress-emerald' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $registry as $row ) :
								$pid     = (int) $row['page_id'];
								$current = isset( $row['current_slug'] ) ? $row['current_slug'] : '';
								$page    = get_post( $pid );
								$reason  = isset( $row['reason'] ) ? $row['reason'] : 'product_cat_collision';
								$type_label = ( 'canonical_swap' === $reason ) ? __( 'Type B · swap', 'luwipress-emerald' ) : __( 'Type A · WC archive', 'luwipress-emerald' );
								?>
								<tr data-pid="<?php echo $pid; ?>">
									<td>
										<a href="<?php echo esc_url( get_edit_post_link( $pid ) ); ?>"><?php echo esc_html( $row['original_title'] ); ?></a>
										<br><small style="color:#7f7663;">#<?php echo $pid;
										if ( 'canonical_swap' === $reason && ! empty( $row['shadow_page_id'] ) ) {
											/* translators: %d: shadow page id */
											printf( ' · ' . esc_html__( 'paired with #%d', 'luwipress-emerald' ), (int) $row['shadow_page_id'] );
										}
										?></small>
									</td>
									<td><small style="color:#7f7663;"><?php echo esc_html( $type_label ); ?></small></td>
									<td><code><?php echo esc_html( $row['original_slug'] ); ?></code></td>
									<td><code><?php echo esc_html( $current ); ?></code></td>
									<td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $row['archived_at'] ) ); ?></td>
									<td>
										<a class="button button-small" href="<?php echo $page ? esc_url( get_permalink( $pid ) ) : '#'; ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'luwipress-emerald' ); ?></a>
										<button type="button" class="button button-small lwp-emerald-migration-restore" data-id="<?php echo $pid; ?>"><?php esc_html_e( 'Restore', 'luwipress-emerald' ); ?></button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</section>
		</div>

		<script>
		(function () {
			var ajaxUrl = <?php echo wp_json_encode( $ajax_url ); ?>;
			var nonce   = <?php echo wp_json_encode( $nonce ); ?>;

			function post(action, payload, cb) {
				var fd = new FormData();
				fd.append('action', action);
				fd.append('nonce', nonce);
				Object.keys(payload || {}).forEach(function (k) {
					var v = payload[k];
					if (Array.isArray(v)) {
						v.forEach(function (item) { fd.append(k + '[]', item); });
					} else {
						fd.append(k, v);
					}
				});
				fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
					.then(function (r) { return r.json(); })
					.then(function (j) { cb(null, j); })
					.catch(function (e) { cb(e); });
			}

			var all = document.getElementById('lwp-emerald-migration-all');
			if (all) {
				all.addEventListener('change', function () {
					document.querySelectorAll('.lwp-emerald-migration-row').forEach(function (cb) { cb.checked = all.checked; });
				});
			}

			var execBtn = document.getElementById('lwp-emerald-migration-execute');
			if (execBtn) {
				execBtn.addEventListener('click', function () {
					var ids = [].slice.call(document.querySelectorAll('.lwp-emerald-migration-row:checked')).map(function (cb) { return cb.value; });
					if (!ids.length) {
						alert(<?php echo wp_json_encode( esc_html__( 'Select at least one page first.', 'luwipress-emerald' ) ); ?>);
						return;
					}
					if (!confirm(<?php echo wp_json_encode( esc_html__( 'Rename slugs of selected pages? They stay published — only the URL slot is freed.', 'luwipress-emerald' ) ); ?>)) return;
					var status = document.getElementById('lwp-emerald-migration-status');
					status.textContent = <?php echo wp_json_encode( esc_html__( 'Archiving…', 'luwipress-emerald' ) ); ?>;
					execBtn.disabled = true;
					var suffix = (document.getElementById('lwp-emerald-migration-suffix').value || 'legacy').trim();
					post('luwipress_emerald_migration_archive', { ids: ids, suffix: suffix }, function (err, j) {
						execBtn.disabled = false;
						if (err || !j || !j.success) {
							status.textContent = <?php echo wp_json_encode( esc_html__( 'Failed.', 'luwipress-emerald' ) ); ?>;
							return;
						}
						status.textContent = <?php echo wp_json_encode( esc_html__( 'Done. Reloading…', 'luwipress-emerald' ) ); ?>;
						setTimeout(function () { location.reload(); }, 600);
					});
				});
			}

			var swapAll = document.getElementById('lwp-emerald-swap-all');
			if (swapAll) {
				swapAll.addEventListener('change', function () {
					document.querySelectorAll('.lwp-emerald-swap-row').forEach(function (cb) { cb.checked = swapAll.checked; });
				});
			}

			var swapBtn = document.getElementById('lwp-emerald-swap-execute');
			if (swapBtn) {
				swapBtn.addEventListener('click', function () {
					var ids = [].slice.call(document.querySelectorAll('.lwp-emerald-swap-row:checked')).map(function (cb) { return cb.value; });
					if (!ids.length) {
						alert(<?php echo wp_json_encode( esc_html__( 'Select at least one pair first.', 'luwipress-emerald' ) ); ?>);
						return;
					}
					if (!confirm(<?php echo wp_json_encode( esc_html__( 'Swap selected pairs? Customer pages move to <slug>-legacy and our shadow pages take the canonical slugs.', 'luwipress-emerald' ) ); ?>)) return;
					var status = document.getElementById('lwp-emerald-swap-status');
					status.textContent = <?php echo wp_json_encode( esc_html__( 'Swapping…', 'luwipress-emerald' ) ); ?>;
					swapBtn.disabled = true;
					var suffix = (document.getElementById('lwp-emerald-migration-suffix') ? document.getElementById('lwp-emerald-migration-suffix').value : 'legacy') || 'legacy';
					post('luwipress_emerald_migration_swap', { ids: ids, suffix: suffix.trim() }, function (err, j) {
						swapBtn.disabled = false;
						if (err || !j || !j.success) {
							status.textContent = <?php echo wp_json_encode( esc_html__( 'Failed.', 'luwipress-emerald' ) ); ?>;
							return;
						}
						status.textContent = <?php echo wp_json_encode( esc_html__( 'Done. Reloading…', 'luwipress-emerald' ) ); ?>;
						setTimeout(function () { location.reload(); }, 600);
					});
				});
			}

			document.querySelectorAll('.lwp-emerald-migration-restore').forEach(function (btn) {
				btn.addEventListener('click', function () {
					var id = btn.getAttribute('data-id');
					if (!confirm(<?php echo wp_json_encode( esc_html__( 'Restore the original slug for this page?', 'luwipress-emerald' ) ); ?>)) return;
					btn.disabled = true;
					post('luwipress_emerald_migration_restore', { id: id }, function (err, j) {
						btn.disabled = false;
						if (err || !j || !j.success) {
							alert((j && j.data && j.data.message) || <?php echo wp_json_encode( esc_html__( 'Restore failed.', 'luwipress-emerald' ) ); ?>);
							return;
						}
						location.reload();
					});
				});
			});
		})();
		</script>
		<?php
	}
}

LuwiPress_Emerald_Migration::instance();

endif;
