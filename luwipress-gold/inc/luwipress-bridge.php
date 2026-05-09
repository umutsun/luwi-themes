<?php
/**
 * LuwiPress bridge — single point of contact for every theme-side call into
 * the LuwiPress plugin.
 *
 * The theme declares LuwiPress as a hard dependency in style.css
 * (`Requires Plugins: elementor, luwipress`), so on WordPress 6.5+ activation
 * is blocked when the plugin is missing. This file is the defense-in-depth
 * layer: an admin notice for older WordPress installs, plus a small set of
 * helper functions that wrap LuwiPress class lookups so the theme code never
 * sprinkles `class_exists()` checks at every call site.
 *
 * Public API:
 *   lwp_gold_lp_active()              bool — is LuwiPress loaded?
 *   lwp_gold_lp_chat_enabled()        bool — is the customer chat module on?
 *   lwp_gold_lp_chat_config()         array|null — config for the storefront widget
 *   lwp_gold_lp_detector()            LuwiPress_Plugin_Detector|null
 *   lwp_gold_lp_ai_dispatch($wf,$msgs,$opts)  array|WP_Error — proxy AI engine
 *   lwp_gold_lp_log($msg, $level, $ctx)       void — defensive logger proxy
 *
 * @package luwipress-gold
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the LuwiPress plugin is active and its core classes are loaded.
 * Cached per request — class_exists is fast but get_option in the chat
 * helpers below would still hit the DB if we re-checked everywhere.
 */
function lwp_gold_lp_active() {
	static $active = null;
	if ( null === $active ) {
		$active = class_exists( 'LuwiPress' ) && defined( 'LUWIPRESS_VERSION' );
	}
	return $active;
}

/**
 * Is the LuwiPress customer chat module switched on by the operator?
 * Mirrors the option Customer Chat tab writes to.
 */
function lwp_gold_lp_chat_enabled() {
	if ( ! lwp_gold_lp_active() ) {
		return false;
	}
	return (bool) get_option( 'luwipress_chat_enabled', 0 );
}

/**
 * Storefront chat configuration — avoids a HTTP round-trip to the
 * `/chat/config` endpoint when we're rendering server-side. Returns the
 * same shape so theme JS can read it from a localized object identically
 * regardless of source.
 *
 * @return array|null  Null when LuwiPress is missing.
 */
function lwp_gold_lp_chat_config() {
	if ( ! lwp_gold_lp_active() ) {
		return null;
	}
	return array(
		'enabled'    => (bool) get_option( 'luwipress_chat_enabled', 0 ),
		'greeting'   => (string) get_option( 'luwipress_chat_greeting', __( 'Hi! How can I help you today?', 'luwipress-gold' ) ),
		'store_name' => get_bloginfo( 'name' ),
		'primary'    => (string) get_option( 'luwipress_chat_color_primary', '#9A7B3A' ),
		'position'   => (string) get_option( 'luwipress_chat_position', 'bottom-right' ),
		'rest_url'   => esc_url_raw( rest_url( 'luwipress/v1/chat/' ) ),
		'nonce'      => wp_create_nonce( 'wp_rest' ),
	);
}

/**
 * Plugin detector singleton — lazy-instantiated. Returns null when LuwiPress
 * isn't around so callers can short-circuit cleanly.
 *
 * @return LuwiPress_Plugin_Detector|null
 */
function lwp_gold_lp_detector() {
	if ( ! lwp_gold_lp_active() || ! class_exists( 'LuwiPress_Plugin_Detector' ) ) {
		return null;
	}
	return LuwiPress_Plugin_Detector::get_instance();
}

/**
 * Dispatch an AI workflow. Mirrors LuwiPress_AI_Engine::dispatch() so we can
 * cleanly substitute it later if the engine signature shifts.
 *
 * @param string $workflow  Workflow id (e.g. 'customer-chat').
 * @param array  $messages  OpenAI-style messages.
 * @param array  $options   Provider/model/max_tokens/temperature/json_mode/timeout.
 * @return array|WP_Error
 */
function lwp_gold_lp_ai_dispatch( $workflow, array $messages, array $options = array() ) {
	if ( ! lwp_gold_lp_active() || ! class_exists( 'LuwiPress_AI_Engine' ) ) {
		return new WP_Error(
			'luwipress_inactive',
			__( 'LuwiPress is not active.', 'luwipress-gold' ),
			array( 'status' => 503 )
		);
	}
	return LuwiPress_AI_Engine::dispatch( $workflow, $messages, $options );
}

/**
 * Defensive logger proxy — never fatals when LuwiPress is missing.
 */
function lwp_gold_lp_log( $message, $level = 'info', $context = array() ) {
	if ( class_exists( 'LuwiPress_Logger' ) ) {
		LuwiPress_Logger::log( $message, $level, $context );
	}
}

/**
 * Defense-in-depth admin notice for sites running WordPress 6.4 (which
 * doesn't enforce style.css `Requires Plugins`). Activation slips through
 * on those, then half the theme features fall back silently. Tell the
 * operator instead.
 */
add_action( 'admin_notices', function () {
	if ( lwp_gold_lp_active() ) {
		return;
	}
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	?>
	<div class="notice notice-warning">
		<p>
			<strong><?php esc_html_e( 'LuwiPress Gold:', 'luwipress-gold' ); ?></strong>
			<?php
			printf(
				/* translators: 1: plugin install URL, 2: plugin search URL on the WP repo */
				esc_html__( 'This theme requires the LuwiPress plugin (AI engine, chat, knowledge graph). %1$sInstall it%2$s to unlock the full storefront experience.', 'luwipress-gold' ),
				'<a href="' . esc_url( admin_url( 'plugin-install.php?s=luwipress&tab=search&type=term' ) ) . '">',
				'</a>'
			);
			?>
		</p>
	</div>
	<?php
} );

/**
 * Register this theme with the LuwiPress companion-theme contract.
 *
 * Two filter surfaces (both introduced in core 3.1.45):
 *
 *   • `luwipress_official_themes` — flat list of official theme slugs.
 *     Drives `LuwiPress_Plugin_Detector::detect_theme()['is_official_companion']`,
 *     which surfaces the green "✓ Theme paired" pill on the LuwiPress
 *     admin dashboard. `luwipress-gold` is the default; we register
 *     defensively in case a downstream plugin replaces the default array.
 *
 *   • `luwipress_theme_companion` — capability matrix the plugin (and
 *     companion plugins like WebMCP, Marketplace Sync, Open Claw) can
 *     read to know what storefront features ship with this theme.
 *     Foundation for Track C (tiered packaging) but already useful as a
 *     single source of truth.
 */
add_filter( 'luwipress_official_themes', function ( $slugs ) {
	$slugs   = is_array( $slugs ) ? $slugs : array();
	$slugs[] = 'luwipress-gold';
	return array_values( array_unique( array_filter( $slugs ) ) );
} );

add_filter( 'luwipress_theme_companion', function ( $registry ) {
	if ( ! is_array( $registry ) ) {
		$registry = array();
	}
	$registry['luwipress-gold'] = array(
		'name'         => 'LuwiPress Gold',
		'version'      => defined( 'LUWIPRESS_GOLD_VERSION' ) ? LUWIPRESS_GOLD_VERSION : '',
		'capabilities' => array(
			'ai-search-overlay'  => true,
			'sticky-chat-widget' => true,
			'kg-related-rail'    => true,
			'mega-menu'          => true,
			'cart-drawer'        => true,
			'pdp-sticky-cta'     => true,
			'migration-archive'  => defined( 'LUWIPRESS_GOLD_VERSION' )
				&& version_compare( LUWIPRESS_GOLD_VERSION, '1.4.0', '>=' ),
			'master-overlay'     => defined( 'LUWIPRESS_GOLD_VERSION' )
				&& version_compare( LUWIPRESS_GOLD_VERSION, '1.4.1', '>=' ),
			'youtube-lightbox'   => defined( 'LUWIPRESS_GOLD_VERSION' )
				&& version_compare( LUWIPRESS_GOLD_VERSION, '1.4.1', '>=' ),
			'theme-tools-bridge' => defined( 'LUWIPRESS_GOLD_VERSION' )
				&& version_compare( LUWIPRESS_GOLD_VERSION, '1.7.0', '>=' ),
		),
	);
	return $registry;
} );

/**
 * Register maintenance tools with the LuwiPress Theme Bridge (plugin 3.1.48+).
 * Each tool is a self-contained class with scan/execute/restore static methods;
 * the bridge handles capability checks, nonce, WPML sibling expansion, and
 * backup persistence.
 *
 * Tools are filtered by active theme slug; only render for `luwipress-gold`.
 */
add_filter( 'luwipress_theme_tools', function ( $tools, $slug ) {
	if ( $slug !== 'luwipress-gold' ) {
		return $tools;
	}
	if ( ! is_array( $tools ) ) {
		$tools = array();
	}

	if ( class_exists( 'LuwiPress_Gold_Elementor_Shell_Tool' ) ) {
		$tools[] = array(
			'id'          => 'elementor_shell_cleanup',
			'label'       => __( 'Elementor Shell Cleanup', 'luwipress-gold' ),
			'description' => __( 'Find posts whose Elementor data is an empty skeleton hiding real Gutenberg content underneath. Strips the shell so the underlying content renders normally. Backs up every stripped meta value for one-click restore.', 'luwipress-gold' ),
			'category'    => 'maintenance',
			'capability'  => 'edit_others_posts',
			'wpml_aware'  => true,
			'destructive' => true,
			'callbacks'   => array(
				'scan'    => array( 'LuwiPress_Gold_Elementor_Shell_Tool', 'scan' ),
				'execute' => array( 'LuwiPress_Gold_Elementor_Shell_Tool', 'execute' ),
				'restore' => array( 'LuwiPress_Gold_Elementor_Shell_Tool', 'restore' ),
			),
		);
	}

	if ( class_exists( 'LuwiPress_Gold_Slug_Conflict_Audit_Tool' ) ) {
		$tools[] = array(
			'id'          => 'slug_conflict_audit',
			'label'       => __( 'Slug Conflict Audit', 'luwipress-gold' ),
			'description' => __( 'Read-only view of the current slug-conflict redirect map. Shows every page slug that auto-redirects to a product category and the discovery pass that found it.', 'luwipress-gold' ),
			'category'    => 'audit',
			'capability'  => 'manage_options',
			'wpml_aware'  => false,
			'destructive' => false,
			'callbacks'   => array(
				'scan' => array( 'LuwiPress_Gold_Slug_Conflict_Audit_Tool', 'scan' ),
			),
		);
	}

	if ( class_exists( 'LuwiPress_Gold_WPML_Drift_Tool' ) ) {
		$tools[] = array(
			'id'          => 'wpml_translation_drift',
			'label'       => __( 'WPML Translation Drift', 'luwipress-gold' ),
			'description' => __( 'List translated posts whose source has been edited since the last sync. Output is read-only — use the Translation Manager to re-sync.', 'luwipress-gold' ),
			'category'    => 'audit',
			'capability'  => 'manage_options',
			'wpml_aware'  => false,
			'destructive' => false,
			'callbacks'   => array(
				'scan' => array( 'LuwiPress_Gold_WPML_Drift_Tool', 'scan' ),
			),
		);
	}

	if ( class_exists( 'LuwiPress_Gold_Legacy_Canvas_Tool' ) ) {
		$tools[] = array(
			'id'          => 'legacy_canvas_migration',
			'label'       => __( 'Legacy Canvas Migration', 'luwipress-gold' ),
			'description' => __( 'Find posts assigned the Hello Elementor canvas / header-footer page templates and confirm whether the active theme overrides them.', 'luwipress-gold' ),
			'category'    => 'migration',
			'capability'  => 'manage_options',
			'wpml_aware'  => false,
			'destructive' => false,
			'callbacks'   => array(
				'scan' => array( 'LuwiPress_Gold_Legacy_Canvas_Tool', 'scan' ),
			),
		);
	}

	if ( class_exists( 'LuwiPress_Gold_Kit_CSS_Health_Tool' ) ) {
		$tools[] = array(
			'id'          => 'kit_css_health',
			'label'       => __( 'Kit CSS Health', 'luwipress-gold' ),
			'description' => __( 'Audit Kit CSS size, headroom against the 412 KB silent-truncation threshold, and verify every layer marker has a matching BEGIN/end pair.', 'luwipress-gold' ),
			'category'    => 'audit',
			'capability'  => 'manage_options',
			'wpml_aware'  => false,
			'destructive' => false,
			'callbacks'   => array(
				'scan' => array( 'LuwiPress_Gold_Kit_CSS_Health_Tool', 'scan' ),
			),
		);
	}

	if ( class_exists( 'LuwiPress_Gold_Orphan_Media_Tool' ) ) {
		$tools[] = array(
			'id'          => 'orphan_media_scan',
			'label'       => __( 'Orphan Media Scan', 'luwipress-gold' ),
			'description' => __( 'Read-only scan for uploaded media not referenced in any post content. Cross-check before deleting from the Media library.', 'luwipress-gold' ),
			'category'    => 'audit',
			'capability'  => 'manage_options',
			'wpml_aware'  => false,
			'destructive' => false,
			'callbacks'   => array(
				'scan' => array( 'LuwiPress_Gold_Orphan_Media_Tool', 'scan' ),
			),
		);
	}

	if ( class_exists( 'LuwiPress_Gold_WPML_Structure_Sync_Tool' ) ) {
		$tools[] = array(
			'id'          => 'wpml_structure_sync',
			'label'       => __( 'WPML Structural Sync Audit', 'luwipress-gold' ),
			'description' => __( 'Read-only audit of WPML/Polylang drift across menus (item count + depth), nav-menu locations (assigned per language?), and tracked theme_mods (any per-language value differences?). Surfaces gaps so the operator can fix them in the appropriate UI.', 'luwipress-gold' ),
			'category'    => 'audit',
			'capability'  => 'manage_options',
			'wpml_aware'  => false,
			'destructive' => false,
			'callbacks'   => array(
				'scan' => array( 'LuwiPress_Gold_WPML_Structure_Sync_Tool', 'scan' ),
			),
		);
	}

	if ( class_exists( 'LuwiPress_Gold_Unwanted_Landing_Pages_Tool' ) ) {
		$tools[] = array(
			'id'          => 'unwanted_landing_pages',
			'label'       => __( 'Unwanted Landing Pages', 'luwipress-gold' ),
			'description' => __( 'Find legacy SEO landing pages with long keyword-stuffed slugs and near-empty bodies that aren\'t reachable from any nav menu. Default execute mode trashes (recoverable); pass mode=delete in args for a hard delete (still backed up). WPML-aware — siblings expanded automatically.', 'luwipress-gold' ),
			'category'    => 'maintenance',
			'capability'  => 'delete_others_posts',
			'wpml_aware'  => true,
			'destructive' => true,
			'callbacks'   => array(
				'scan'    => array( 'LuwiPress_Gold_Unwanted_Landing_Pages_Tool', 'scan' ),
				'execute' => array( 'LuwiPress_Gold_Unwanted_Landing_Pages_Tool', 'execute' ),
				'restore' => array( 'LuwiPress_Gold_Unwanted_Landing_Pages_Tool', 'restore' ),
			),
		);
	}

	if ( class_exists( 'LuwiPress_Gold_Subcategory_Template_Parity_Tool' ) ) {
		$tools[] = array(
			'id'          => 'subcategory_template_parity',
			'label'       => __( 'Subcategory Template Parity', 'luwipress-gold' ),
			'description' => __( 'HEAD-check every product_cat archive in every active language. Surfaces 404s and 5xxs — typically a WPML term-translation gap where the menu drawer shows the term but the archive URL itself is broken.', 'luwipress-gold' ),
			'category'    => 'audit',
			'capability'  => 'manage_options',
			'wpml_aware'  => false,
			'destructive' => false,
			'callbacks'   => array(
				'scan' => array( 'LuwiPress_Gold_Subcategory_Template_Parity_Tool', 'scan' ),
			),
		);
	}

	if ( class_exists( 'LuwiPress_Gold_Triangle_Health_Tool' ) ) {
		$tools[] = array(
			'id'          => 'triangle_health',
			'label'       => __( 'Elementor + WC + WPML Triangle Health', 'luwipress-gold' ),
			'description' => __( 'Consolidated single-call audit of the three pillars: WooCommerce core pages translated, Elementor Theme Builder templates assigned (PDP + archive), WPML menu/structure drift, subcategory parity, orphan SEO landings. Each finding cites the dedicated tool that resolves it.', 'luwipress-gold' ),
			'category'    => 'audit',
			'capability'  => 'manage_options',
			'wpml_aware'  => false,
			'destructive' => false,
			'callbacks'   => array(
				'scan' => array( 'LuwiPress_Gold_Triangle_Health_Tool', 'scan' ),
			),
		);
	}

	if ( class_exists( 'LuwiPress_Gold_WPML_Term_Repair_Tool' ) ) {
		$tools[] = array(
			'id'          => 'wpml_term_repair',
			'label'       => __( 'WPML Term Repair', 'luwipress-gold' ),
			'description' => __( 'Auto-create placeholder translated product_cat terms for every default-language term that has missing translations. Inserts a term in the target language, links it via WPML\'s trid, and uses parent inheritance so menu pages resolve correctly. Operator can rename the placeholder names afterwards in WPML → Taxonomy Translation.', 'luwipress-gold' ),
			'category'    => 'migration',
			'capability'  => 'manage_options',
			'wpml_aware'  => false,
			'destructive' => true,
			'callbacks'   => array(
				'scan'    => array( 'LuwiPress_Gold_WPML_Term_Repair_Tool', 'scan' ),
				'execute' => array( 'LuwiPress_Gold_WPML_Term_Repair_Tool', 'execute' ),
				'restore' => array( 'LuwiPress_Gold_WPML_Term_Repair_Tool', 'restore' ),
			),
		);
	}

	if ( class_exists( 'LuwiPress_Gold_Menu_Propagate_Tool' ) ) {
		$tools[] = array(
			'id'          => 'menu_translation_propagate',
			'label'       => __( 'Menu Translation Propagate', 'luwipress-gold' ),
			'description' => __( 'For each source-language menu, append top-level items missing in sibling-language menus. Conservative: depth 0 only. Backed up; restore drops the propagated items.', 'luwipress-gold' ),
			'category'    => 'migration',
			'capability'  => 'manage_options',
			'wpml_aware'  => false,
			'destructive' => true,
			'callbacks'   => array(
				'scan'    => array( 'LuwiPress_Gold_Menu_Propagate_Tool', 'scan' ),
				'execute' => array( 'LuwiPress_Gold_Menu_Propagate_Tool', 'execute' ),
				'restore' => array( 'LuwiPress_Gold_Menu_Propagate_Tool', 'restore' ),
			),
		);
	}

	if ( class_exists( 'LuwiPress_Gold_Product_Translation_Tool' ) ) {
		$tools[] = array(
			'id'          => 'product_translation_completeness',
			'label'       => __( 'Product Translation Completeness', 'luwipress-gold' ),
			'description' => __( 'List every published product missing in one or more languages. Read-only — pass the IDs to LuwiPress\'s /translation/batch endpoint to queue AI translation.', 'luwipress-gold' ),
			'category'    => 'audit',
			'capability'  => 'manage_options',
			'wpml_aware'  => false,
			'destructive' => false,
			'callbacks'   => array(
				'scan' => array( 'LuwiPress_Gold_Product_Translation_Tool', 'scan' ),
			),
		);
	}

	if ( class_exists( 'LuwiPress_Gold_Template_Assignment_Tool' ) ) {
		$tools[] = array(
			'id'          => 'wc_template_assignment',
			'label'       => __( 'WC Template Assignment', 'luwipress-gold' ),
			'description' => __( 'Show the active Elementor Pro PDP / archive template binding (forced via theme_mod or deferred to Elementor conditions) plus a list of candidate templates. Apply via the Settings tab — pdp_template_id / archive_template_id.', 'luwipress-gold' ),
			'category'    => 'audit',
			'capability'  => 'manage_options',
			'wpml_aware'  => false,
			'destructive' => false,
			'callbacks'   => array(
				'scan' => array( 'LuwiPress_Gold_Template_Assignment_Tool', 'scan' ),
			),
		);
	}

	if ( class_exists( 'LuwiPress_Gold_Broken_Internal_Links_Tool' ) ) {
		$tools[] = array(
			'id'          => 'broken_internal_links',
			'label'       => __( 'Broken Internal Links', 'luwipress-gold' ),
			'description' => __( 'HEAD-check every internal href appearing in published post content. Skips assets and external domains; capped at 60 URLs per scan to stay within request budget.', 'luwipress-gold' ),
			'category'    => 'audit',
			'capability'  => 'manage_options',
			'wpml_aware'  => false,
			'destructive' => false,
			'callbacks'   => array(
				'scan' => array( 'LuwiPress_Gold_Broken_Internal_Links_Tool', 'scan' ),
			),
		);
	}

	if ( class_exists( 'LuwiPress_Gold_Empty_Term_Archives_Tool' ) ) {
		$tools[] = array(
			'id'          => 'empty_term_archives',
			'label'       => __( 'Empty Term Archives in Menu', 'luwipress-gold' ),
			'description' => __( 'Find product_cat terms with zero published products that nonetheless appear in a navigation menu. Either add products or drop the menu link.', 'luwipress-gold' ),
			'category'    => 'audit',
			'capability'  => 'manage_options',
			'wpml_aware'  => false,
			'destructive' => false,
			'callbacks'   => array(
				'scan' => array( 'LuwiPress_Gold_Empty_Term_Archives_Tool', 'scan' ),
			),
		);
	}

	if ( class_exists( 'LuwiPress_Gold_WPML_Strings_Tool' ) ) {
		$tools[] = array(
			'id'          => 'wpml_string_translation_pending',
			'label'       => __( 'WPML String Translation Pending', 'luwipress-gold' ),
			'description' => __( 'List entries in the WPML String Translation table whose status isn\'t 10 (translated). Resolve in WPML → String Translation.', 'luwipress-gold' ),
			'category'    => 'audit',
			'capability'  => 'manage_options',
			'wpml_aware'  => false,
			'destructive' => false,
			'callbacks'   => array(
				'scan' => array( 'LuwiPress_Gold_WPML_Strings_Tool', 'scan' ),
			),
		);
	}

	if ( class_exists( 'LuwiPress_Gold_Page_Speed_Signals_Tool' ) ) {
		$tools[] = array(
			'id'          => 'page_speed_signals',
			'label'       => __( 'Page Speed Signals', 'luwipress-gold' ),
			'description' => __( 'Probe wp_options autoload size, expired transient cruft, and object cache health. Catches the silent option-truncation bug + autoload bloat early.', 'luwipress-gold' ),
			'category'    => 'audit',
			'capability'  => 'manage_options',
			'wpml_aware'  => false,
			'destructive' => false,
			'callbacks'   => array(
				'scan' => array( 'LuwiPress_Gold_Page_Speed_Signals_Tool', 'scan' ),
			),
		);
	}

	// ─── SEO + Redirects audit pack (1.7.0) ──────────────────────────────────
	if ( class_exists( 'LuwiPress_Gold_Canonical_Audit_Tool' ) ) {
		$tools[] = array(
			'id'          => 'canonical_audit',
			'label'       => __( 'Canonical URL Audit', 'luwipress-gold' ),
			'description' => __( 'Per-URL rel=canonical health: present, self-pointing, same domain, target resolves 200 without chain. Surfaces SEO-plugin overrides that point off-page.', 'luwipress-gold' ),
			'category'    => 'audit',
			'capability'  => 'manage_options',
			'wpml_aware'  => false,
			'destructive' => false,
			'callbacks'   => array(
				'scan' => array( 'LuwiPress_Gold_Canonical_Audit_Tool', 'scan' ),
			),
		);
	}

	if ( class_exists( 'LuwiPress_Gold_Hreflang_Reciprocity_Tool' ) ) {
		$tools[] = array(
			'id'          => 'hreflang_reciprocity_audit',
			'label'       => __( 'Hreflang Reciprocity Audit', 'luwipress-gold' ),
			'description' => __( 'Verify every translated page emits the full hreflang set (incl. x-default) AND each alternate reciprocates. Catches WPML/Polylang drift after translations are unlinked.', 'luwipress-gold' ),
			'category'    => 'audit',
			'capability'  => 'manage_options',
			'wpml_aware'  => false,
			'destructive' => false,
			'callbacks'   => array(
				'scan' => array( 'LuwiPress_Gold_Hreflang_Reciprocity_Tool', 'scan' ),
			),
		);
	}

	if ( class_exists( 'LuwiPress_Gold_Redirect_Chain_Detector_Tool' ) ) {
		$tools[] = array(
			'id'          => 'redirect_chain_detector',
			'label'       => __( 'Redirect Chain Detector', 'luwipress-gold' ),
			'description' => __( 'Walk every URL in the slug-conflict redirect map plus a sample of internal hrefs; flag 2+ hop chains and any 4xx/5xx final status. Single-hop 200 endings are healthy.', 'luwipress-gold' ),
			'category'    => 'audit',
			'capability'  => 'manage_options',
			'wpml_aware'  => false,
			'destructive' => false,
			'callbacks'   => array(
				'scan' => array( 'LuwiPress_Gold_Redirect_Chain_Detector_Tool', 'scan' ),
			),
		);
	}

	if ( class_exists( 'LuwiPress_Gold_Sitemap_Indexation_Parity_Tool' ) ) {
		$tools[] = array(
			'id'          => 'sitemap_indexation_parity',
			'label'       => __( 'Sitemap Indexation Parity', 'luwipress-gold' ),
			'description' => __( 'Discover the active sitemap (Rank Math / Yoast / AIOSEO / WP core), HEAD-check up to 80 entries, flag any URL that\'s 301/4xx or sits in the slug-conflict redirect map. Resolve at the SEO plugin level.', 'luwipress-gold' ),
			'category'    => 'audit',
			'capability'  => 'manage_options',
			'wpml_aware'  => false,
			'destructive' => false,
			'callbacks'   => array(
				'scan' => array( 'LuwiPress_Gold_Sitemap_Indexation_Parity_Tool', 'scan' ),
			),
		);
	}

	if ( class_exists( 'LuwiPress_Gold_SEO_Triangle_Health_Tool' ) ) {
		$tools[] = array(
			'id'          => 'seo_triangle_health',
			'label'       => __( 'SEO Triangle Health', 'luwipress-gold' ),
			'description' => __( 'Single-call audit over six SEO pillars (canonical, hreflang, redirect chains, sitemap parity, broken internal links, slug-conflict map). Returns a weighted 0-100 score plus per-pillar drill-downs that link to the dedicated tool.', 'luwipress-gold' ),
			'category'    => 'audit',
			'capability'  => 'manage_options',
			'wpml_aware'  => false,
			'destructive' => false,
			'callbacks'   => array(
				'scan' => array( 'LuwiPress_Gold_SEO_Triangle_Health_Tool', 'scan' ),
			),
		);
	}

	return $tools;
}, 10, 2 );

/**
 * Register theme_mod proxies with the bridge — gives operators a remote-friendly
 * settings surface that mirrors the theme Customizer.
 */
add_filter( 'luwipress_theme_settings', function ( $settings, $slug ) {
	if ( $slug !== 'luwipress-gold' ) {
		return $settings;
	}
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}

	$settings[] = array(
		'id'        => 'loader_enabled',
		'theme_mod' => 'luwipress_gold_loader_enabled',
		'label'     => __( 'Page loader overlay', 'luwipress-gold' ),
		'description' => __( 'Server-side loader overlay rendered before content paints; dismissed on window.load.', 'luwipress-gold' ),
		'type'      => 'checkbox',
		'default'   => true,
		'group'     => 'performance',
	);

	$settings[] = array(
		'id'        => 'resolve_slug_conflicts',
		'theme_mod' => 'luwipress_gold_resolve_slug_conflicts',
		'label'     => __( 'Resolve slug conflicts', 'luwipress-gold' ),
		'description' => __( 'Auto-redirect page slugs that collide with product categories. Includes WPML cross-language and Levenshtein-1 fuzzy match.', 'luwipress-gold' ),
		'type'      => 'checkbox',
		'default'   => false,
		'group'     => 'performance',
	);

	$settings[] = array(
		'id'        => 'show_shop_col',
		'theme_mod' => 'luwipress_gold_show_shop_col',
		'label'     => __( 'Footer shop column', 'luwipress-gold' ),
		'description' => __( 'Adds a 5th footer column listing top-level product categories.', 'luwipress-gold' ),
		'type'      => 'checkbox',
		'default'   => true,
		'group'     => 'footer',
	);

	$settings[] = array(
		'id'        => 'shop_col_limit',
		'theme_mod' => 'luwipress_gold_shop_col_limit',
		'label'     => __( 'Shop column item limit', 'luwipress-gold' ),
		'type'      => 'number',
		'default'   => 8,
		'min'       => 3,
		'max'       => 20,
		'group'     => 'footer',
	);

	$settings[] = array(
		'id'        => 'mega_menu_id',
		'theme_mod' => 'luwipress_gold_mega_menu_id',
		'label'     => __( 'Mega menu — menu ID', 'luwipress-gold' ),
		'description' => __( 'WordPress nav-menu ID to render in the mega menu. Set 0 to disable.', 'luwipress-gold' ),
		'type'      => 'number',
		'default'   => 0,
		'min'       => 0,
		'group'     => 'mega_menu',
	);

	$settings[] = array(
		'id'        => 'mega_threshold',
		'theme_mod' => 'luwipress_gold_mega_threshold',
		'label'     => __( 'Mega menu — child count threshold', 'luwipress-gold' ),
		'description' => __( 'Minimum children before a top-level menu item flips to mega-panel rendering.', 'luwipress-gold' ),
		'type'      => 'number',
		'default'   => 4,
		'min'       => 1,
		'max'       => 20,
		'group'     => 'mega_menu',
	);

	$settings[] = array(
		'id'        => 'mega_columns',
		'theme_mod' => 'luwipress_gold_mega_columns',
		'label'     => __( 'Mega menu — columns', 'luwipress-gold' ),
		'type'      => 'select',
		'default'   => 'auto',
		'choices'   => array(
			'auto' => __( 'Auto', 'luwipress-gold' ),
			'2'    => __( '2 columns', 'luwipress-gold' ),
			'3'    => __( '3 columns', 'luwipress-gold' ),
			'4'    => __( '4 columns', 'luwipress-gold' ),
		),
		'group'     => 'mega_menu',
	);

	$settings[] = array(
		'id'        => 'mega_show_counts',
		'theme_mod' => 'luwipress_gold_mega_show_counts',
		'label'     => __( 'Mega menu — show product counts', 'luwipress-gold' ),
		'type'      => 'checkbox',
		'default'   => true,
		'group'     => 'mega_menu',
	);

	$settings[] = array(
		'id'        => 'mega_auto_inject_blog',
		'theme_mod' => 'luwipress_gold_mega_auto_inject_blog',
		'label'     => __( 'Mega menu — auto-inject blog categories', 'luwipress-gold' ),
		'type'      => 'checkbox',
		'default'   => false,
		'group'     => 'mega_menu',
	);

	$settings[] = array(
		'id'        => 'block_orphan_landings',
		'theme_mod' => 'luwipress_gold_block_orphan_landings',
		'label'     => __( 'Block orphan SEO landings on the front-end', 'luwipress-gold' ),
		'description' => __( 'When on, pages flagged by the Unwanted Landing Pages tool return a 410 Gone for visitors and search bots. Lets you defer the actual delete while neutralising the SEO leak.', 'luwipress-gold' ),
		'type'      => 'checkbox',
		'default'   => false,
		'group'     => 'wpml',
	);

	$settings[] = array(
		'id'        => 'wpml_subcat_strict_404',
		'theme_mod' => 'luwipress_gold_wpml_subcat_strict_404',
		'label'     => __( 'WPML — fail loud on missing translated subcategory', 'luwipress-gold' ),
		'description' => __( 'When a translated product_cat resolves to 404, render an explicit "Translation pending" banner instead of WP\'s generic 404. Helps catch WPML term-translation gaps in QA.', 'luwipress-gold' ),
		'type'      => 'checkbox',
		'default'   => false,
		'group'     => 'wpml',
	);

	$settings[] = array(
		'id'        => 'pdp_template_id',
		'theme_mod' => 'luwipress_gold_pdp_template_id',
		'label'     => __( 'PDP — forced Elementor template ID', 'luwipress-gold' ),
		'description' => __( 'Override the Elementor Pro Single Product template selection. Set 0 to defer to Elementor\'s own conditions. Useful when WPML duplicates a template per language and the wrong one wins.', 'luwipress-gold' ),
		'type'      => 'number',
		'default'   => 0,
		'min'       => 0,
		'group'     => 'wpml',
	);

	$settings[] = array(
		'id'        => 'archive_template_id',
		'theme_mod' => 'luwipress_gold_archive_template_id',
		'label'     => __( 'Archive — forced Elementor template ID', 'luwipress-gold' ),
		'description' => __( 'Override the Elementor Pro Products Archive template selection. Set 0 to defer to Elementor.', 'luwipress-gold' ),
		'type'      => 'number',
		'default'   => 0,
		'min'       => 0,
		'group'     => 'wpml',
	);

	// ─── SEO + Redirects (1.7.0) ─────────────────────────────────────────────
	$settings[] = array(
		'id'        => 'seo_strict_canonical',
		'theme_mod' => 'luwipress_gold_seo_strict_canonical',
		'label'     => __( 'Strict canonical = permalink', 'luwipress-gold' ),
		'description' => __( 'Override Rank Math / Yoast / AIOSEO canonical with the page\'s own permalink. Stops cross-page canonicals that hurt indexation.', 'luwipress-gold' ),
		'type'      => 'checkbox',
		'default'   => false,
		'group'     => 'seo',
	);

	$settings[] = array(
		'id'        => 'seo_force_trailing_slash',
		'theme_mod' => 'luwipress_gold_seo_force_trailing_slash',
		'label'     => __( 'Force trailing-slash consistency', 'luwipress-gold' ),
		'description' => __( '301-redirect URLs to match the site\'s permalink_structure (with or without trailing slash). Skips REST, sitemap, and asset paths.', 'luwipress-gold' ),
		'type'      => 'checkbox',
		'default'   => false,
		'group'     => 'seo',
	);

	$settings[] = array(
		'id'        => 'seo_noindex_empty_archives',
		'theme_mod' => 'luwipress_gold_seo_noindex_empty_archives',
		'label'     => __( 'noindex empty term archives', 'luwipress-gold' ),
		'description' => __( 'Emit <meta name="robots" content="noindex,follow"> on product_cat archives flagged by the empty_term_archives audit. Cached 6h; busts on tool execute.', 'luwipress-gold' ),
		'type'      => 'checkbox',
		'default'   => false,
		'group'     => 'seo',
	);

	// `block_orphan_landings` already exists under the `wpml` group as
	// `luwipress_gold_block_orphan_landings`. The duplicate registration here
	// (under `seo` group) gives operators a discoverable "SEO" home for it
	// while keeping the existing theme_mod key — no migration needed.
	$settings[] = array(
		'id'        => 'seo_block_orphan_landings',
		'theme_mod' => 'luwipress_gold_block_orphan_landings',
		'label'     => __( 'Block orphan SEO landings (410 Gone)', 'luwipress-gold' ),
		'description' => __( 'Same toggle as under WPML — duplicated here so operators discover it under SEO. Pages flagged by the unwanted_landing_pages audit return 410 instead of rendering.', 'luwipress-gold' ),
		'type'      => 'checkbox',
		'default'   => false,
		'group'     => 'seo',
	);

	// ─── Shop archive UX (1.7.0) ─────────────────────────────────────────────
	$settings[] = array(
		'id'        => 'shop_loadmore',
		'theme_mod' => 'luwipress_gold_shop_loadmore',
		'label'     => __( 'Shop — Load More instead of pagination', 'luwipress-gold' ),
		'description' => __( 'Replaces the page-numbers pagination on shop / category / tag archives with a Load More flow (theme-side, no Elementor dep). The pagination markup is kept in the DOM (visually hidden) so SEO crawlers still discover paginated URLs.', 'luwipress-gold' ),
		'type'      => 'checkbox',
		'default'   => false,
		'group'     => 'shop',
	);

	$settings[] = array(
		'id'        => 'shop_loadmore_mode',
		'theme_mod' => 'luwipress_gold_shop_loadmore_mode',
		'label'     => __( 'Shop — Load More mode', 'luwipress-gold' ),
		'description' => __( 'Button: visitor clicks "Load more". Infinite: auto-fetch when the sentinel scrolls into view. prefers-reduced-motion users always get the button regardless.', 'luwipress-gold' ),
		'type'      => 'select',
		'default'   => 'button',
		'choices'   => array(
			'button'   => __( 'Button (recommended)', 'luwipress-gold' ),
			'infinite' => __( 'Infinite scroll', 'luwipress-gold' ),
		),
		'group'     => 'shop',
	);

	return $settings;
}, 10, 2 );
