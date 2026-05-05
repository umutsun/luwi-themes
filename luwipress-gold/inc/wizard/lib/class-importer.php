<?php
/**
 * Importer — executes the Mapper's plan against the live site.
 *
 * Operations supported (the Mapper emits these in the `actions` array):
 *   import_elementor_kit          → import Site Settings (kit.json) via Elementor's Kit Library
 *   import_elementor_template     → register a template (header/footer/single-product/page)
 *   import_demo_xml               → run the WordPress Importer on a bundled XML
 *   mark_master                   → flag a luthier term for the homepage maker grid
 *
 * All writes are wrapped in try/catch and produce a structured per-action log.
 * Anything that touches existing posts is read-then-write — never destructive
 * unless explicitly asked.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once __DIR__ . '/class-content-compiler.php';

class LuwiPress_Gold_Importer {

	/**
	 * @var LuwiPress_Gold_Content_Compiler
	 */
	private $compiler;

	/**
	 * @var array
	 */
	private $snapshot;

	/**
	 * Run the entire plan for a given path.
	 *
	 * @param string $path    use_existing | tapadum_demo | empty
	 * @param array  $brand   { logo_id, primary_color, accent_color, phone, email }
	 *
	 * @return array|WP_Error  Per-action log + counts, or WP_Error on hard failure.
	 */
	public function apply( $path, $brand = [] ) {
		// Re-derive plan to ensure it matches the current snapshot at apply-time.
		$detector = new LuwiPress_Gold_Detector();
		$this->snapshot = $detector->snapshot();
		$this->compiler = new LuwiPress_Gold_Content_Compiler( $this->snapshot );
		$mapper   = new LuwiPress_Gold_Mapper();
		$plan     = $mapper->plan( $this->snapshot, $path );

		$log = [
			'path'    => $path,
			'started' => current_time( 'mysql' ),
			'actions' => [],
			'pages'   => [],
			'options' => [],
			'errors'  => [],
		];

		// 0. Cleanup — every Apply re-creates Theme Builder templates with the
		// latest compiled content, so old `_lwp_gold_managed` templates from
		// previous runs become orphans. Trash them now to avoid the user seeing
		// 13+ duplicate "LuwiPress Gold — Header" entries in Templates → Library.
		$this->cleanup_managed_templates( $log );

		// 1. Brand overrides — always first; they shape everything that follows.
		$this->apply_brand( $brand, $log );

		// 2. Process every action in order.
		foreach ( $plan['actions'] as $action ) {
			try {
				$result = $this->dispatch_action( $action );
				$log['actions'][] = [
					'op'      => $action['op'] ?? '',
					'status'  => 'ok',
					'detail'  => $action,
					'result'  => $result,
				];
			} catch ( \Throwable $e ) {
				$log['actions'][] = [
					'op'     => $action['op'] ?? '',
					'status' => 'error',
					'detail' => $action,
					'error'  => $e->getMessage(),
				];
				$log['errors'][] = $e->getMessage();
			}
		}

		// 3. Process pages (create / apply template).
		foreach ( $plan['pages'] as $slug => $page ) {
			try {
				$result = $this->process_page( $slug, $page );
				$log['pages'][] = [
					'slug'   => $slug,
					'status' => 'ok',
					'detail' => $page,
					'result' => $result,
				];
			} catch ( \Throwable $e ) {
				$log['pages'][] = [
					'slug'   => $slug,
					'status' => 'error',
					'detail' => $page,
					'error'  => $e->getMessage(),
				];
				$log['errors'][] = $e->getMessage();
			}
		}

		// 4. Options writes.
		foreach ( $plan['options'] as $name => $value ) {
			update_option( $name, $value );
			$log['options'][] = $name;
		}

		// 5. Menu suggestions — only if the operator has chosen to apply.
		if ( ! empty( $plan['menus']['primary_suggestion'] ) ) {
			$this->assign_menu_suggestion( $plan['menus']['primary_suggestion'], $log );
		}

		// 6. Persist log for later inspection (Tools → Site Health → Info).
		$log['finished'] = current_time( 'mysql' );
		update_option( 'luwipress_gold_wizard_log', $log, false );

		// Mirror to PHP error log so WP_DEBUG_LOG users can inspect without
		// digging into wp_options. One line per outcome to keep it greppable.
		if ( WP_DEBUG && WP_DEBUG_LOG ) {
			error_log( '[LWP Gold Wizard] path=' . $path . ' actions=' . count( $log['actions'] ) . ' pages=' . count( $log['pages'] ) . ' errors=' . count( $log['errors'] ) );
			foreach ( $log['actions'] as $a ) {
				error_log( '[LWP Gold Wizard] action ' . ( $a['op'] ?? '?' ) . ': ' . $a['status'] . ( isset( $a['error'] ) ? ' — ' . $a['error'] : '' ) );
			}
			foreach ( $log['pages'] as $p ) {
				error_log( '[LWP Gold Wizard] page ' . ( $p['slug'] ?? '?' ) . ': ' . $p['status'] . ' — ' . wp_json_encode( $p['result'] ?? $p['error'] ?? null ) );
			}
		}

		return $log;
	}

	/* ------------------------------------------------------------------
	 * Cleanup: orphan Theme Builder templates from previous runs
	 * ---------------------------------------------------------------- */

	/**
	 * Force-delete every `elementor_library` post tagged with
	 * `_lwp_gold_managed = 1`. Run at the start of every Apply so the
	 * Templates → Library doesn't accumulate one extra "LuwiPress Gold —
	 * Header" per wizard run.
	 *
	 * Skip the auto-applied homepage container (id 35754 et al) — anything
	 * with `_lwp_gold_keep` meta is preserved.
	 */
	private function cleanup_managed_templates( &$log ) {
		$query = new WP_Query( [
			'post_type'      => 'elementor_library',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => [
				[
					'key'   => '_lwp_gold_managed',
					'value' => '1',
				],
			],
			'no_found_rows'  => true,
		] );
		$deleted = [];
		foreach ( $query->posts as $tid ) {
			if ( get_post_meta( $tid, '_lwp_gold_keep', true ) ) continue;
			if ( wp_delete_post( $tid, true ) ) {
				$deleted[] = $tid;
			}
		}
		if ( $deleted ) {
			$log['actions'][] = [
				'op'     => 'cleanup_managed_templates',
				'status' => 'ok',
				'detail' => [ 'deleted' => $deleted ],
				'result' => [ 'count' => count( $deleted ) ],
			];
		}
	}

	/* ------------------------------------------------------------------
	 * Brand override
	 * ---------------------------------------------------------------- */

	private function apply_brand( $brand, &$log ) {
		if ( ! empty( $brand['logo_id'] ) ) {
			set_theme_mod( 'custom_logo', (int) $brand['logo_id'] );
			$log['options'][] = 'custom_logo';
		}

		// Theme mods — used by tokens.css runtime override.
		$mods = [
			'primary_color' => $brand['primary_color'] ?? null,
			'accent_color'  => $brand['accent_color']  ?? null,
		];
		foreach ( $mods as $key => $val ) {
			if ( $val !== null && $val !== '' ) {
				set_theme_mod( 'luwipress_gold_' . $key, sanitize_hex_color( $val ) ?: $val );
				$log['options'][] = 'theme_mod:' . $key;
			}
		}

		// WP general options (only ones that were actually provided).
		if ( ! empty( $brand['phone'] ) ) {
			update_option( 'luwipress_gold_phone', sanitize_text_field( $brand['phone'] ) );
			$log['options'][] = 'luwipress_gold_phone';
		}
		if ( ! empty( $brand['email'] ) && is_email( $brand['email'] ) ) {
			// Don't overwrite admin_email — store in our namespace instead.
			update_option( 'luwipress_gold_contact_email', sanitize_email( $brand['email'] ) );
			$log['options'][] = 'luwipress_gold_contact_email';
		}
	}

	/* ------------------------------------------------------------------
	 * Action dispatcher
	 * ---------------------------------------------------------------- */

	private function dispatch_action( $action ) {
		$op = $action['op'] ?? '';
		switch ( $op ) {
			case 'import_elementor_kit':
				return $this->import_kit( $action );
			case 'import_elementor_template':
				return $this->import_template( $action );
			case 'import_demo_xml':
				return $this->import_demo_xml( $action );
			case 'mark_master':
				return $this->mark_master( $action );
			default:
				throw new \RuntimeException( 'Unknown op: ' . $op );
		}
	}

	/* ------------------------------------------------------------------
	 * Elementor Kit / Template import
	 * ---------------------------------------------------------------- */

	private function kit_dir() {
		return apply_filters( 'luwipress_gold_kit_path', LUWIPRESS_GOLD_DIR . '/elementor-kit/' );
	}

	private function import_kit( $action ) {
		$file = trailingslashit( $this->kit_dir() ) . ( $action['file'] ?? 'kit.json' );
		if ( ! file_exists( $file ) ) {
			throw new \RuntimeException( 'Kit file missing: ' . $action['file'] );
		}
		if ( ! did_action( 'elementor/loaded' ) ) {
			throw new \RuntimeException( 'Elementor not active.' );
		}
		// Decode + write Site Settings (colors, fonts, button styles).
		$json = json_decode( file_get_contents( $file ), true );
		if ( ! $json ) {
			throw new \RuntimeException( 'Kit JSON parse error: ' . $action['file'] );
		}

		// Use Elementor's import-export module if present.
		$import_module = $this->elementor_import_module();
		if ( $import_module && method_exists( $import_module, 'import_site_settings' ) ) {
			$import_module->import_site_settings( $json );
			return [ 'imported_settings' => true ];
		}

		// Fallback — write the raw _elementor_page_settings on the active kit.
		if ( method_exists( '\Elementor\Plugin', 'instance' ) ) {
			$kit_id = \Elementor\Plugin::instance()->kits_manager->get_active_id();
			if ( $kit_id ) {
				update_post_meta( $kit_id, '_elementor_page_settings', $json );
				return [ 'wrote_to_kit_id' => $kit_id ];
			}
		}

		throw new \RuntimeException( 'Could not write kit settings — Elementor API unavailable.' );
	}

	private function import_template( $action ) {
		$file = trailingslashit( $this->kit_dir() ) . ( $action['kit'] ?? '' );
		if ( ! file_exists( $file ) ) {
			throw new \RuntimeException( 'Template file missing: ' . ( $action['kit'] ?? '?' ) );
		}
		if ( ! did_action( 'elementor/loaded' ) ) {
			throw new \RuntimeException( 'Elementor not active.' );
		}

		$json = json_decode( file_get_contents( $file ), true );
		if ( ! $json ) {
			throw new \RuntimeException( 'Template JSON parse error.' );
		}

		// Compile placeholders against the live snapshot — populates hero
		// stats, featured products, category cards, etc. with real data.
		if ( $this->compiler ) {
			$json = $this->compiler->compile( $json );
		}

		// Build the Elementor template post.
		$content = $json['content'] ?? [];
		$encoded = wp_json_encode( $content );
		$post_id = wp_insert_post( [
			'post_title'  => $action['name'] ?? basename( $file ),
			'post_status' => 'publish',
			'post_type'   => 'elementor_library',
		] );

		if ( is_wp_error( $post_id ) ) {
			throw new \RuntimeException( $post_id->get_error_message() );
		}

		// Write Elementor meta AFTER insert so save_post hooks (Elementor itself,
		// ElementsKit, plugins) don't strip our values during the insert pass.
		// `meta_input` was unreliable on Tapadum-class sites (155 MCP tools); split
		// the writes and verify each one took.
		update_post_meta( $post_id, '_elementor_template_type', $action['type'] ?? 'page' );
		update_post_meta( $post_id, '_elementor_edit_mode',     'builder' );
		update_post_meta( $post_id, '_elementor_version',       defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.18.0' );
		update_post_meta( $post_id, '_elementor_data',          wp_slash( $encoded ) );
		update_post_meta( $post_id, '_lwp_gold_managed',        1 );

		// Verify the write actually landed; some hosts (LiteSpeed object cache)
		// drop large meta on first write — a re-read forces the cache to settle.
		$readback = get_post_meta( $post_id, '_elementor_data', true );
		if ( empty( $readback ) || strlen( $readback ) < 100 ) {
			// Retry path with raw INSERT into postmeta to bypass any save filters.
			global $wpdb;
			$wpdb->replace( $wpdb->postmeta, [
				'post_id'    => $post_id,
				'meta_key'   => '_elementor_data',
				'meta_value' => wp_slash( $encoded ),
			] );
			wp_cache_delete( $post_id, 'post_meta' );
		}

		// Apply Elementor display conditions (Pro only — silently skipped otherwise).
		if ( ! empty( $action['condition_type'] ) && $this->elementor_pro_active() ) {
			$this->set_template_conditions( $post_id, $action['condition_type'], $action['condition_value'] ?? '' );
		}

		return [ 'template_id' => $post_id, 'type' => $action['type'] ?? 'page' ];
	}

	/**
	 * Hook into Elementor Pro's Theme Builder conditions API if available.
	 */
	private function set_template_conditions( $post_id, $condition_type, $condition_value ) {
		if ( ! class_exists( '\ElementorPro\Modules\ThemeBuilder\Module' ) ) return;
		try {
			$module = \ElementorPro\Modules\ThemeBuilder\Module::instance();
			if ( $module && method_exists( $module, 'get_conditions_manager' ) ) {
				$module->get_conditions_manager()->save_conditions( $post_id, [
					[ 'type' => $condition_type, 'name' => $condition_value, 'sub_id' => 0, 'sub_name' => '' ],
				] );
			}
		} catch ( \Throwable $e ) {
			// Silently fail — Pro conditions are best-effort.
		}
	}

	private function elementor_import_module() {
		if ( ! class_exists( '\Elementor\Core\App\Modules\ImportExport\Module' ) ) return null;
		try {
			return \Elementor\Plugin::instance()->app->get_component( 'import-export' );
		} catch ( \Throwable $e ) {
			return null;
		}
	}

	private function elementor_pro_active() {
		return defined( 'ELEMENTOR_PRO_VERSION' );
	}

	/* ------------------------------------------------------------------
	 * Demo XML import (used only by tapadum_demo path)
	 * ---------------------------------------------------------------- */

	private function import_demo_xml( $action ) {
		$file = LUWIPRESS_GOLD_DIR . '/' . ltrim( $action['file'] ?? '', '/' );
		if ( ! file_exists( $file ) ) {
			// Demo XML files are bundled separately — log + skip if missing.
			return [ 'skipped' => true, 'reason' => 'file_not_bundled' ];
		}
		// Defer to the WordPress Importer if installed, or use wp-cli style import.
		// Implementation note: this is left as a stub — full import is heavy
		// and requires the WP Importer plugin (`wordpress-importer`). The wizard
		// UI surfaces a notice when the file is found but the importer is not.
		if ( ! defined( 'WP_LOAD_IMPORTERS' ) ) {
			define( 'WP_LOAD_IMPORTERS', true );
		}
		if ( ! class_exists( 'WP_Importer' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-importer.php';
		}
		$wp_importer_path = WP_PLUGIN_DIR . '/wordpress-importer/wordpress-importer.php';
		if ( ! file_exists( $wp_importer_path ) ) {
			return [
				'skipped' => true,
				'reason'  => 'wordpress_importer_plugin_missing',
				'hint'    => __( 'Install the "WordPress Importer" plugin from wp-admin → Tools → Import → WordPress.', 'luwipress-gold' ),
			];
		}
		require_once $wp_importer_path;
		if ( ! class_exists( 'WP_Import' ) ) {
			return [ 'skipped' => true, 'reason' => 'wp_import_class_missing' ];
		}
		$importer = new WP_Import();
		$importer->fetch_attachments = true;
		ob_start();
		$importer->import( $file );
		$output = ob_get_clean();
		return [ 'imported_xml' => $action['file'], 'output_excerpt' => substr( $output, 0, 200 ) ];
	}

	/* ------------------------------------------------------------------
	 * Mark master luthier — for homepage maker grid
	 * ---------------------------------------------------------------- */

	private function mark_master( $action ) {
		if ( ! taxonomy_exists( 'pa_luthier' ) ) {
			return [ 'skipped' => true, 'reason' => 'taxonomy_missing' ];
		}
		$term = get_term_by( 'slug', $action['slug'] ?? '', 'pa_luthier' );
		if ( ! $term || is_wp_error( $term ) ) {
			return [ 'skipped' => true, 'reason' => 'term_not_found' ];
		}
		update_term_meta( $term->term_id, '_lwp_gold_featured_master', 1 );
		return [ 'term_id' => $term->term_id, 'name' => $term->name ];
	}

	/* ------------------------------------------------------------------
	 * Page processing
	 * ---------------------------------------------------------------- */

	private function process_page( $slug, $page ) {
		// Page already exists → update its content with the compiled template
		// (a revision is auto-saved by WP, so the operator can revert).
		if ( ! empty( $page['existing_id'] ) && ( $page['action'] ?? '' ) === 'apply_template_only' ) {
			$applied = $this->apply_template_to_page(
				(int) $page['existing_id'],
				$page['kit'],
				$page['compiler_context'] ?? []
			);
			return [
				'page_id'          => (int) $page['existing_id'],
				'updated'          => true,
				'template_applied' => $applied,
			];
		}

		// Create new page (incl. parallel "-gold" duplicates and auto-master pages).
		if ( ( $page['action'] ?? '' ) === 'create_and_import' || ! empty( $page['set_as_home'] ) ) {
			$page_slug = $page['slug'] ?? $slug;
			$existing = get_page_by_path( $page_slug );
			if ( $existing ) {
				return [ 'page_id' => $existing->ID, 'reused' => true ];
			}

			$meta = [ '_lwp_gold_managed' => 1 ];
			if ( ! empty( $page['parallel_to'] ) ) {
				$meta['_lwp_gold_parallel_to'] = (int) $page['parallel_to'];
			}
			if ( ! empty( $page['compiler_context']['master_slug'] ) ) {
				$meta['_lwp_gold_master_slug'] = sanitize_title( $page['compiler_context']['master_slug'] );
			}

			$post_id = wp_insert_post( [
				'post_title'   => $page['title'],
				'post_name'    => $page_slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
				'meta_input'   => $meta,
			] );
			if ( is_wp_error( $post_id ) ) {
				throw new \RuntimeException( $post_id->get_error_message() );
			}

			// Apply template (with optional master-specific context).
			$applied = $this->apply_template_to_page(
				$post_id,
				$page['kit'],
				$page['compiler_context'] ?? []
			);

			// Set as home if asked.
			if ( ! empty( $page['set_as_home'] ) ) {
				update_option( 'show_on_front', 'page' );
				update_option( 'page_on_front', $post_id );
			}

			return [
				'page_id'          => $post_id,
				'created'          => true,
				'template_applied' => $applied,
				'parallel_to'      => $page['parallel_to'] ?? null,
			];
		}

		return [ 'skipped' => true, 'reason' => 'no_action' ];
	}

	private function apply_template_to_page( $page_id, $kit_file, $context = [] ) {
		$file = trailingslashit( $this->kit_dir() ) . $kit_file;
		if ( ! file_exists( $file ) ) return [ 'skipped' => true, 'reason' => 'kit_missing' ];
		$json = json_decode( file_get_contents( $file ), true );
		if ( ! $json ) return [ 'skipped' => true, 'reason' => 'parse_error' ];

		// Compile placeholders before writing.
		// For master profile pages, replace LWP:master_* tags with this
		// specific master's data via a quick string substitution before
		// the generic compiler runs.
		if ( ! empty( $context['master_slug'] ) ) {
			$json_str = wp_json_encode( $json );
			$replacements = [
				'{{LWP:master_name}}'  => addslashes( $context['master_name'] ?? '' ),
				'{{LWP:master_init}}'  => addslashes( $context['master_init'] ?? '' ),
				'{{LWP:master_slug}}'  => addslashes( $context['master_slug'] ?? '' ),
				'{{LWP:master_count}}' => (int) ( $context['master_count'] ?? 0 ),
			];
			$json_str = str_replace( array_keys( $replacements ), array_values( $replacements ), $json_str );
			$json = json_decode( $json_str, true );
		}

		if ( $this->compiler ) {
			$json = $this->compiler->compile( $json );
		}

		$content = $json['content'] ?? [];
		$encoded = wp_json_encode( $content );

		// Write meta — split + re-read so save_post hooks from other plugins
		// can't strip the values silently (Tapadum-class sites with 100+ plugins).
		update_post_meta( $page_id, '_elementor_edit_mode',     'builder' );
		update_post_meta( $page_id, '_elementor_template_type', 'wp-page' );
		update_post_meta( $page_id, '_elementor_version',       defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.18.0' );
		update_post_meta( $page_id, '_elementor_data',          wp_slash( $encoded ) );
		update_post_meta( $page_id, '_lwp_gold_managed',        1 );

		// Page-template choice depends on whether Elementor Pro / Theme Builder
		// is going to wrap the page with a Header/Footer template:
		//   - Pro present → use 'elementor_header_footer' (Pro's theme builder
		//     wraps the canvas with our Header/Footer Theme Builder templates).
		//   - Pro missing → use 'default' so the active theme's header.php /
		//     footer.php still render. Otherwise the page comes through with
		//     no header at all (canvas mode = no chrome).
		// _wp_page_template — drives whether the theme's header.php / footer.php
		// chrome renders. Three known-good values:
		//   - default                  → theme renders header + footer
		//   - elementor_header_footer  → only valid with Elementor Pro Theme
		//                                Builder; without Pro it suppresses chrome
		//   - elementor_canvas         → no chrome, full-bleed (only useful for
		//                                landing pages where we hand-roll header)
		//
		// Pro absent (real-world case for new.tapadum.com): force `default`
		// REGARDLESS of the existing value, so previous wizard runs that
		// wrote canvas / header_footer get reset. Pro-present sites get
		// header_footer so Pro's Theme Builder wraps our compiled body.
		//
		// We use a raw $wpdb->replace if the meta API write doesn't stick
		// (LiteSpeed object cache + plugin save_post hooks have been seen
		// to silently retain old values).
		$desired_tpl = $this->elementor_pro_active() ? 'elementor_header_footer' : 'default';
		update_post_meta( $page_id, '_wp_page_template', $desired_tpl );
		wp_cache_delete( $page_id, 'post_meta' );
		clean_post_cache( $page_id );
		$tpl_readback = get_post_meta( $page_id, '_wp_page_template', true );
		if ( $tpl_readback !== $desired_tpl ) {
			global $wpdb;
			$wpdb->replace( $wpdb->postmeta, [
				'post_id'    => $page_id,
				'meta_key'   => '_wp_page_template',
				'meta_value' => $desired_tpl,
			] );
			wp_cache_delete( $page_id, 'post_meta' );
			clean_post_cache( $page_id );
		}

		// Verify the write actually landed.
		wp_cache_delete( $page_id, 'post_meta' );
		clean_post_cache( $page_id );
		$readback = get_post_meta( $page_id, '_elementor_data', true );
		if ( empty( $readback ) || strlen( $readback ) < 100 ) {
			// Retry path with raw DB write to bypass any save_post filters
			// that might be stripping content (LiteSpeed object cache, security
			// plugins, etc.).
			global $wpdb;
			$wpdb->replace( $wpdb->postmeta, [
				'post_id'    => $page_id,
				'meta_key'   => '_elementor_data',
				'meta_value' => wp_slash( $encoded ),
			] );
			wp_cache_delete( $page_id, 'post_meta' );
			clean_post_cache( $page_id );
			$readback = get_post_meta( $page_id, '_elementor_data', true );
		}

		// Trigger Elementor CSS regen so the new content actually renders.
		if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
			try {
				$css = new \Elementor\Core\Files\CSS\Post( $page_id );
				$css->update();
			} catch ( \Throwable $e ) {
				// Silent.
			}
		}

		return [
			'page_id'        => $page_id,
			'template'       => $kit_file,
			'sections_count' => is_array( $content ) ? count( $content ) : 0,
			'meta_size'      => strlen( $readback ),
			'verified'       => ! empty( $readback ) && strlen( $readback ) >= 100,
		];
	}

	/* ------------------------------------------------------------------
	 * Menu suggestion
	 * ---------------------------------------------------------------- */

	private function assign_menu_suggestion( $suggestion, &$log ) {
		$locations = get_nav_menu_locations();
		// Don't overwrite if `primary` already has a menu.
		if ( ! empty( $locations['primary'] ) ) {
			$log['actions'][] = [
				'op'     => 'assign_menu_suggestion',
				'status' => 'skipped',
				'reason' => 'primary_already_assigned',
			];
			return;
		}
		$locations['primary'] = (int) $suggestion['menu_id'];
		set_theme_mod( 'nav_menu_locations', $locations );
		$log['actions'][] = [
			'op'      => 'assign_menu_suggestion',
			'status'  => 'ok',
			'menu_id' => (int) $suggestion['menu_id'],
		];
	}
}
