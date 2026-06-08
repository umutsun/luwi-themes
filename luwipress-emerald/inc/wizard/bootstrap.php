<?php
/**
 * Onboarding wizard bootstrap.
 *
 * Loads the wizard's pieces (detector, mapper, importer, UI), registers the
 * `Appearance → LuwiPress Emerald` admin page, and shows a one-time activation
 * notice that links to the wizard.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LuwiPress_Emerald_Wizard' ) ) :

class LuwiPress_Emerald_Wizard {

	const DISMISSED_OPTION = 'luwipress_emerald_wizard_dismissed';
	const COMPLETED_OPTION = 'luwipress_emerald_wizard_completed';
	const NONCE            = 'luwipress_emerald_wizard';
	const SLUG             = 'luwipress-emerald-wizard';
	const CAPABILITY       = 'manage_options';

	private static $instance;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Components.
		require_once __DIR__ . '/lib/class-detector.php';
		require_once __DIR__ . '/lib/class-mapper.php';
		require_once __DIR__ . '/lib/class-importer.php';
		require_once __DIR__ . '/lib/class-tgm-bridge.php';
		require_once __DIR__ . '/lib/class-ai-content.php';

		// Boot order:
		add_action( 'after_switch_theme', [ $this, 'on_theme_activate' ] );
		add_action( 'admin_menu',         [ $this, 'register_admin_page' ] );
		add_action( 'admin_notices',      [ $this, 'render_activation_notice' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_ajax_luwipress_emerald_wizard_step', [ $this, 'handle_ajax_step' ] );
		add_action( 'wp_ajax_luwipress_emerald_wizard_dismiss', [ $this, 'handle_ajax_dismiss' ] );
	}

	/**
	 * Mark "fresh activation" — the notice + wizard show until dismissed or completed.
	 */
	public function on_theme_activate() {
		delete_option( self::DISMISSED_OPTION );
		// Don't reset COMPLETED — re-activation shouldn't re-run the wizard.
	}

	/**
	 * Hidden tools-page (no menu link). Reachable via direct URL or the activation notice.
	 */
	public function register_admin_page() {
		add_submenu_page(
			'',                                    // hidden — no parent
			__( 'LuwiPress Emerald setup', 'luwipress-emerald' ),
			__( 'LuwiPress Emerald', 'luwipress-emerald' ),
			self::CAPABILITY,
			self::SLUG,
			[ $this, 'render_page' ]
		);

		// Always-visible Tools entry — lets the operator re-run the wizard
		// after the first activation (the auto-notice only shows once).
		add_management_page(
			__( 'LuwiPress Emerald setup (re-run)', 'luwipress-emerald' ),
			__( 'LuwiPress Emerald', 'luwipress-emerald' ),
			self::CAPABILITY,
			self::SLUG,
			[ $this, 'render_page' ]
		);
	}

	public function get_url() {
		return admin_url( 'tools.php?page=' . self::SLUG );
	}

	public function should_show_notice() {
		if ( get_option( self::COMPLETED_OPTION ) ) return false;
		if ( get_option( self::DISMISSED_OPTION ) ) return false;
		if ( ! current_user_can( self::CAPABILITY ) ) return false;
		// Don't show inside the wizard itself.
		if ( isset( $_GET['page'] ) && $_GET['page'] === self::SLUG ) return false;
		return true;
	}

	public function render_activation_notice() {
		if ( ! $this->should_show_notice() ) return;

		$detector = new LuwiPress_Emerald_Detector();
		$snapshot = $detector->snapshot();
		$summary  = $detector->summary_phrase( $snapshot );

		$url = esc_url( $this->get_url() );
		?>
		<div class="notice notice-info luwipress-emerald-notice is-dismissible" data-lwp-wizard-notice>
			<p style="font-size:14px">
				<strong style="font-family:'Playfair Display',Georgia,serif;font-size:18px;">
					<?php esc_html_e( 'Welcome to LuwiPress Emerald.', 'luwipress-emerald' ); ?>
				</strong>
				<br>
				<?php echo esc_html( $summary ); ?>
				<?php esc_html_e( 'Run the setup wizard — we\'ll detect what you have and adapt the theme to it (no data is overwritten).', 'luwipress-emerald' ); ?>
			</p>
			<p>
				<a href="<?php echo $url; ?>" class="button button-primary">
					<?php esc_html_e( 'Start setup wizard', 'luwipress-emerald' ); ?>
				</a>
				<a href="#" class="button button-secondary" data-lwp-wizard-dismiss>
					<?php esc_html_e( 'Skip for now', 'luwipress-emerald' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	public function enqueue_assets( $hook ) {
		// Notice is on every screen → minimal CSS + dismiss JS always loaded.
		wp_enqueue_style(
			'luwipress-emerald-wizard-notice',
			LUWIPRESS_EMERALD_URI . '/assets/wizard-css/notice.css',
			[],
			LUWIPRESS_EMERALD_VERSION
		);
		wp_enqueue_script(
			'luwipress-emerald-wizard-notice',
			LUWIPRESS_EMERALD_URI . '/assets/wizard-js/notice.js',
			[ 'jquery' ],
			LUWIPRESS_EMERALD_VERSION,
			true
		);
		wp_localize_script( 'luwipress-emerald-wizard-notice', 'LWP_EMERALD_WIZARD', [
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( self::NONCE ),
		] );

		// Wizard page — load the heavy CSS/JS only on the wizard screen.
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== self::SLUG ) {
			return;
		}
		wp_enqueue_style(
			'luwipress-emerald-wizard',
			LUWIPRESS_EMERALD_URI . '/assets/wizard-css/wizard.css',
			[ 'luwipress-emerald-wizard-notice' ],
			LUWIPRESS_EMERALD_VERSION
		);
		wp_enqueue_script(
			'luwipress-emerald-wizard',
			LUWIPRESS_EMERALD_URI . '/assets/wizard-js/wizard.js',
			[ 'jquery', 'luwipress-emerald-wizard-notice' ],
			LUWIPRESS_EMERALD_VERSION,
			true
		);
		wp_localize_script( 'luwipress-emerald-wizard', 'LWP_EMERALD_WIZARD_DATA', [
			'restUrl' => esc_url_raw( rest_url() ),
			'i18n'    => [
				'detecting'  => __( 'Scanning your site…', 'luwipress-emerald' ),
				'mapping'    => __( 'Mapping content to templates…', 'luwipress-emerald' ),
				'importing'  => __( 'Importing templates…', 'luwipress-emerald' ),
				'finishing'  => __( 'Almost there…', 'luwipress-emerald' ),
				'completed'  => __( 'All done!', 'luwipress-emerald' ),
				'errored'    => __( 'Something went wrong. Check the WP debug log.', 'luwipress-emerald' ),
			],
		] );
	}

	public function render_page() {
		require __DIR__ . '/views/wizard.php';
	}

	/* -----------------------------------------------------------------
	 * AJAX handlers
	 * --------------------------------------------------------------- */

	public function handle_ajax_dismiss() {
		check_ajax_referer( self::NONCE );
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
		}
		update_option( self::DISMISSED_OPTION, time() );
		wp_send_json_success();
	}

	/**
	 * Step dispatcher — UI calls this with `step=<name>` and step-specific payload.
	 *
	 * Supported steps:
	 *   detect        → detector snapshot
	 *   map           → mapping plan from snapshot + chosen path
	 *   plugin_status → which required plugins are active
	 *   apply         → run the importer with the chosen plan
	 *   complete      → mark wizard done
	 */
	public function handle_ajax_step() {
		check_ajax_referer( self::NONCE );
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
		}

		$step    = isset( $_POST['step'] ) ? sanitize_key( $_POST['step'] ) : '';
		$payload = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : [];
		if ( is_string( $payload ) ) {
			$payload = json_decode( $payload, true ) ?: [];
		}

		switch ( $step ) {
			case 'detect':
				$d = new LuwiPress_Emerald_Detector();
				wp_send_json_success( [
					'snapshot' => $d->snapshot(),
					'summary'  => $d->summary_phrase( $d->snapshot() ),
				] );

			case 'map':
				$path = isset( $payload['path'] ) ? sanitize_key( $payload['path'] ) : 'use_existing';
				$d = new LuwiPress_Emerald_Detector();
				$snap = $d->snapshot();
				$mapper = new LuwiPress_Emerald_Mapper();
				$plan = $mapper->plan( $snap, $path );
				wp_send_json_success( [ 'plan' => $plan, 'snapshot' => $snap ] );

			case 'plugin_status':
				$bridge = new LuwiPress_Emerald_TGM_Bridge();
				wp_send_json_success( [ 'plugins' => $bridge->required_plugins_status() ] );

			case 'ai_status':
				wp_send_json_success( [
					'available' => LuwiPress_Emerald_AI_Content::is_luwipress_available(),
					'enabled'   => LuwiPress_Emerald_AI_Content::is_enabled(),
					'slots'     => LuwiPress_Emerald_AI_Content::slots(),
					'meta'      => get_option( LuwiPress_Emerald_AI_Content::META_OPTION, [] ),
				] );

			case 'ai_toggle':
				$enable = ! empty( $payload['enable'] );
				update_option( LuwiPress_Emerald_AI_Content::ENABLED_OPTION, $enable ? 1 : 0 );
				if ( ! $enable ) {
					LuwiPress_Emerald_AI_Content::flush_cache();
				}
				wp_send_json_success( [ 'enabled' => $enable ] );

			case 'ai_generate':
				if ( ! LuwiPress_Emerald_AI_Content::is_luwipress_available() ) {
					wp_send_json_error( [ 'message' => 'LuwiPress AI engine not installed.' ] );
				}
				update_option( LuwiPress_Emerald_AI_Content::ENABLED_OPTION, 1 );
				$results = [];
				foreach ( LuwiPress_Emerald_AI_Content::slots() as $slot => $cfg ) {
					$text = LuwiPress_Emerald_AI_Content::resolve( $slot );
					$results[ $slot ] = [
						'label' => $cfg['label'],
						'text'  => $text,
						'is_default' => trim( $text ) === trim( $cfg['default'] ),
					];
				}
				wp_send_json_success( [ 'results' => $results ] );

			case 'ai_flush':
				LuwiPress_Emerald_AI_Content::flush_cache();
				wp_send_json_success();

			case 'apply':
				$path = isset( $payload['path'] ) ? sanitize_key( $payload['path'] ) : 'use_existing';
				$brand = isset( $payload['brand'] ) ? (array) $payload['brand'] : [];
				$opts  = [
					'resolve_slug_conflicts' => ! empty( $payload['resolve_slug_conflicts'] ),
				];
				$importer = new LuwiPress_Emerald_Importer();
				$result = $importer->apply( $path, $brand, $opts );
				if ( is_wp_error( $result ) ) {
					wp_send_json_error( [ 'message' => $result->get_error_message() ] );
				}
				wp_send_json_success( $result );

			case 'complete':
				update_option( self::COMPLETED_OPTION, time() );
				wp_send_json_success();

			// ── Theme Bridge proxy steps (1.7.0+) ────────────────────────────
			// Lets the wizard wrap the same maintenance tools the operator
			// sees under LuwiPress → Theme. Each tool runs as a dedicated
			// step so the wizard can show per-tool scan results inline.

			case 'tools_list':
				if ( ! class_exists( 'LuwiPress_Theme_Bridge' ) ) {
					wp_send_json_error( [ 'message' => 'Theme Bridge unavailable — requires LuwiPress 3.1.48+.' ] );
				}
				$bridge = LuwiPress_Theme_Bridge::get_instance();
				$tools  = array();
				foreach ( $bridge->get_tools() as $t ) {
					unset( $t['callbacks'] );
					$tools[] = $t;
				}
				wp_send_json_success( [ 'tools' => $tools ] );

			case 'tool_scan':
				if ( ! class_exists( 'LuwiPress_Theme_Bridge' ) ) {
					wp_send_json_error( [ 'message' => 'Theme Bridge unavailable.' ] );
				}
				$tool_id = isset( $payload['tool_id'] ) ? sanitize_key( $payload['tool_id'] ) : '';
				$bridge  = LuwiPress_Theme_Bridge::get_instance();
				$res     = $bridge->run_tool( $tool_id, 'scan', isset( $payload['args'] ) ? (array) $payload['args'] : [] );
				if ( is_wp_error( $res ) ) {
					wp_send_json_error( [ 'message' => $res->get_error_message() ] );
				}
				wp_send_json_success( $res );

			case 'tool_execute':
				if ( ! class_exists( 'LuwiPress_Theme_Bridge' ) ) {
					wp_send_json_error( [ 'message' => 'Theme Bridge unavailable.' ] );
				}
				$tool_id  = isset( $payload['tool_id'] ) ? sanitize_key( $payload['tool_id'] ) : '';
				$post_ids = isset( $payload['post_ids'] ) ? array_map( 'intval', (array) $payload['post_ids'] ) : [];
				$bridge   = LuwiPress_Theme_Bridge::get_instance();
				$args     = isset( $payload['args'] ) ? (array) $payload['args'] : [];
				if ( $post_ids ) {
					$args['post_ids'] = $post_ids;
				}
				$res = $bridge->run_tool( $tool_id, 'execute', $args );
				if ( is_wp_error( $res ) ) {
					wp_send_json_error( [ 'message' => $res->get_error_message() ] );
				}
				wp_send_json_success( $res );

			default:
				wp_send_json_error( [ 'message' => 'unknown step' ], 400 );
		}
	}
}

LuwiPress_Emerald_Wizard::instance();

endif;
