<?php
/**
 * Onboarding wizard bootstrap.
 *
 * Loads the wizard's pieces (detector, mapper, importer, UI), registers the
 * `Appearance → LuwiPress Gold` admin page, and shows a one-time activation
 * notice that links to the wizard.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LuwiPress_Gold_Wizard' ) ) :

class LuwiPress_Gold_Wizard {

	const DISMISSED_OPTION = 'luwipress_gold_wizard_dismissed';
	const COMPLETED_OPTION = 'luwipress_gold_wizard_completed';
	const NONCE            = 'luwipress_gold_wizard';
	const SLUG             = 'luwipress-gold-wizard';
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
		add_action( 'wp_ajax_luwipress_gold_wizard_step', [ $this, 'handle_ajax_step' ] );
		add_action( 'wp_ajax_luwipress_gold_wizard_dismiss', [ $this, 'handle_ajax_dismiss' ] );
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
			__( 'LuwiPress Gold setup', 'luwipress-gold' ),
			__( 'LuwiPress Gold', 'luwipress-gold' ),
			self::CAPABILITY,
			self::SLUG,
			[ $this, 'render_page' ]
		);
	}

	public function get_url() {
		return admin_url( 'admin.php?page=' . self::SLUG );
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

		$detector = new LuwiPress_Gold_Detector();
		$snapshot = $detector->snapshot();
		$summary  = $detector->summary_phrase( $snapshot );

		$url = esc_url( $this->get_url() );
		?>
		<div class="notice notice-info luwipress-gold-notice is-dismissible" data-lwp-wizard-notice>
			<p style="font-size:14px">
				<strong style="font-family:'Playfair Display',Georgia,serif;font-size:18px;">
					<?php esc_html_e( 'Welcome to LuwiPress Gold.', 'luwipress-gold' ); ?>
				</strong>
				<br>
				<?php echo esc_html( $summary ); ?>
				<?php esc_html_e( 'Run the setup wizard — we\'ll detect what you have and adapt the theme to it (no data is overwritten).', 'luwipress-gold' ); ?>
			</p>
			<p>
				<a href="<?php echo $url; ?>" class="button button-primary">
					<?php esc_html_e( 'Start setup wizard', 'luwipress-gold' ); ?>
				</a>
				<a href="#" class="button button-secondary" data-lwp-wizard-dismiss>
					<?php esc_html_e( 'Skip for now', 'luwipress-gold' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	public function enqueue_assets( $hook ) {
		// Notice is on every screen → minimal CSS + dismiss JS always loaded.
		wp_enqueue_style(
			'luwipress-gold-wizard-notice',
			LUWIPRESS_GOLD_URI . '/assets/wizard-css/notice.css',
			[],
			LUWIPRESS_GOLD_VERSION
		);
		wp_enqueue_script(
			'luwipress-gold-wizard-notice',
			LUWIPRESS_GOLD_URI . '/assets/wizard-js/notice.js',
			[ 'jquery' ],
			LUWIPRESS_GOLD_VERSION,
			true
		);
		wp_localize_script( 'luwipress-gold-wizard-notice', 'LWP_GOLD_WIZARD', [
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( self::NONCE ),
		] );

		// Wizard page — load the heavy CSS/JS only on the wizard screen.
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== self::SLUG ) {
			return;
		}
		wp_enqueue_style(
			'luwipress-gold-wizard',
			LUWIPRESS_GOLD_URI . '/assets/wizard-css/wizard.css',
			[ 'luwipress-gold-wizard-notice' ],
			LUWIPRESS_GOLD_VERSION
		);
		wp_enqueue_script(
			'luwipress-gold-wizard',
			LUWIPRESS_GOLD_URI . '/assets/wizard-js/wizard.js',
			[ 'jquery', 'luwipress-gold-wizard-notice' ],
			LUWIPRESS_GOLD_VERSION,
			true
		);
		wp_localize_script( 'luwipress-gold-wizard', 'LWP_GOLD_WIZARD_DATA', [
			'restUrl' => esc_url_raw( rest_url() ),
			'i18n'    => [
				'detecting'  => __( 'Scanning your site…', 'luwipress-gold' ),
				'mapping'    => __( 'Mapping content to templates…', 'luwipress-gold' ),
				'importing'  => __( 'Importing templates…', 'luwipress-gold' ),
				'finishing'  => __( 'Almost there…', 'luwipress-gold' ),
				'completed'  => __( 'All done!', 'luwipress-gold' ),
				'errored'    => __( 'Something went wrong. Check the WP debug log.', 'luwipress-gold' ),
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
				$d = new LuwiPress_Gold_Detector();
				wp_send_json_success( [
					'snapshot' => $d->snapshot(),
					'summary'  => $d->summary_phrase( $d->snapshot() ),
				] );

			case 'map':
				$path = isset( $payload['path'] ) ? sanitize_key( $payload['path'] ) : 'use_existing';
				$d = new LuwiPress_Gold_Detector();
				$snap = $d->snapshot();
				$mapper = new LuwiPress_Gold_Mapper();
				$plan = $mapper->plan( $snap, $path );
				wp_send_json_success( [ 'plan' => $plan, 'snapshot' => $snap ] );

			case 'plugin_status':
				$bridge = new LuwiPress_Gold_TGM_Bridge();
				wp_send_json_success( [ 'plugins' => $bridge->required_plugins_status() ] );

			case 'ai_status':
				wp_send_json_success( [
					'available' => LuwiPress_Gold_AI_Content::is_luwipress_available(),
					'enabled'   => LuwiPress_Gold_AI_Content::is_enabled(),
					'slots'     => LuwiPress_Gold_AI_Content::slots(),
					'meta'      => get_option( LuwiPress_Gold_AI_Content::META_OPTION, [] ),
				] );

			case 'ai_toggle':
				$enable = ! empty( $payload['enable'] );
				update_option( LuwiPress_Gold_AI_Content::ENABLED_OPTION, $enable ? 1 : 0 );
				if ( ! $enable ) {
					LuwiPress_Gold_AI_Content::flush_cache();
				}
				wp_send_json_success( [ 'enabled' => $enable ] );

			case 'ai_generate':
				if ( ! LuwiPress_Gold_AI_Content::is_luwipress_available() ) {
					wp_send_json_error( [ 'message' => 'LuwiPress AI engine not installed.' ] );
				}
				update_option( LuwiPress_Gold_AI_Content::ENABLED_OPTION, 1 );
				$results = [];
				foreach ( LuwiPress_Gold_AI_Content::slots() as $slot => $cfg ) {
					$text = LuwiPress_Gold_AI_Content::resolve( $slot );
					$results[ $slot ] = [
						'label' => $cfg['label'],
						'text'  => $text,
						'is_default' => trim( $text ) === trim( $cfg['default'] ),
					];
				}
				wp_send_json_success( [ 'results' => $results ] );

			case 'ai_flush':
				LuwiPress_Gold_AI_Content::flush_cache();
				wp_send_json_success();

			case 'apply':
				$path = isset( $payload['path'] ) ? sanitize_key( $payload['path'] ) : 'use_existing';
				$brand = isset( $payload['brand'] ) ? (array) $payload['brand'] : [];
				$importer = new LuwiPress_Gold_Importer();
				$result = $importer->apply( $path, $brand );
				if ( is_wp_error( $result ) ) {
					wp_send_json_error( [ 'message' => $result->get_error_message() ] );
				}
				wp_send_json_success( $result );

			case 'complete':
				update_option( self::COMPLETED_OPTION, time() );
				wp_send_json_success();

			default:
				wp_send_json_error( [ 'message' => 'unknown step' ], 400 );
		}
	}
}

LuwiPress_Gold_Wizard::instance();

endif;
