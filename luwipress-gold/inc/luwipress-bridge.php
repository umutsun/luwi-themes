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
		),
	);
	return $registry;
} );
