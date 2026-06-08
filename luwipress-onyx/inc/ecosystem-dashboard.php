<?php
/**
 * Ecosystem dashboard — admin page that surfaces the full LuwiPress
 * ecosystem from the theme's perspective.
 *
 * This is the cleanest demonstration of "tam entegrasyon": one panel
 * that reads from the plugin's plugin detector + AI engine + chat module
 * + token tracker, then projects the result back through the theme's
 * design language. The operator sees one story — theme + plugin + every
 * friendly plugin — instead of bouncing between two admin sections.
 *
 * Page lives at Appearance → LuwiPress Onyx (top-level page if Appearance
 * isn't suitable). Read-only by design — every setting is owned by either
 * the LuwiPress plugin or the Customizer; this panel just observes.
 *
 * @package luwipress-onyx
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', function () {
	add_theme_page(
		__( 'LuwiPress Onyx', 'luwipress-onyx' ),
		__( 'LuwiPress Onyx', 'luwipress-onyx' ),
		'manage_options',
		'luwipress-onyx-ecosystem',
		'lwp_onyx_render_ecosystem_dashboard'
	);
}, 9 );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'appearance_page_luwipress-onyx-ecosystem' !== $hook ) {
		return;
	}
	wp_enqueue_style(
		'luwipress-onyx-ecosystem',
		LUWIPRESS_ONYX_URI . '/assets/css/ecosystem-dashboard.css',
		array(),
		LUWIPRESS_ONYX_VERSION
	);
} );

/**
 * Main dashboard renderer. Composes:
 *   - Hero strip with theme version + LuwiPress version + WC version
 *   - Ecosystem health grid: required (LuwiPress, Elementor, WC) + friendly
 *   - AI surface state: chat enabled? search suggestions cache hits? token spend
 *   - Theme features earned by each detected plugin
 *   - Quick links to relevant LuwiPress admin sections
 */
function lwp_onyx_render_ecosystem_dashboard() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to view this page.', 'luwipress-onyx' ) );
	}

	$lp_active = lwp_onyx_lp_active();
	$detector  = lwp_onyx_lp_detector();

	$lp_version    = $lp_active ? LUWIPRESS_VERSION : '';
	$theme_version = LUWIPRESS_ONYX_VERSION;
	$el_active     = did_action( 'elementor/loaded' );
	$el_version    = $el_active && defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '';
	$wc_active     = class_exists( 'WooCommerce' );
	$wc_version    = $wc_active && defined( 'WC_VERSION' ) ? WC_VERSION : '';

	// Friendly-plugin signals (each is null if undetected).
	$seo  = $detector ? $detector->detect_seo()         : null;
	$lang = $detector ? $detector->detect_translation() : null;
	$pb   = $detector ? $detector->detect_page_builder() : null;
	$cache = $detector ? $detector->detect_cache()      : null;
	$crm  = $detector ? $detector->detect_crm()         : null;

	$features = apply_filters( 'luwipress_onyx_ecosystem_features', array() );

	$chat_enabled  = lwp_onyx_lp_chat_enabled();
	$chat_greeting = $chat_enabled ? (string) get_option( 'luwipress_chat_greeting', '' ) : '';

	?>
	<div class="wrap lwp-eco">

		<header class="lwp-eco__hero">
			<div class="lwp-eco__hero-text">
				<span class="lwp-eco__eyebrow">— <?php esc_html_e( 'LuwiPress ecosystem', 'luwipress-onyx' ); ?></span>
				<h1 class="lwp-eco__title">
					<?php
					printf(
						/* translators: %s: theme version */
						esc_html__( 'LuwiPress Onyx %s', 'luwipress-onyx' ),
						esc_html( $theme_version )
					);
					?>
				</h1>
				<p class="lwp-eco__lead">
					<?php esc_html_e( 'Your theme reads from the LuwiPress plugin’s AI engine, knowledge graph, and plugin detector. Every friendly plugin you activate amplifies a feature on the storefront.', 'luwipress-onyx' ); ?>
				</p>
			</div>
			<div class="lwp-eco__hero-stats">
				<?php
				$stats = array(
					array(
						'label' => __( 'LuwiPress', 'luwipress-onyx' ),
						'value' => $lp_version ?: '—',
						'state' => $lp_active ? 'ok' : 'warn',
					),
					array(
						'label' => __( 'Elementor', 'luwipress-onyx' ),
						'value' => $el_version ?: '—',
						'state' => $el_active ? 'ok' : 'warn',
					),
					array(
						'label' => __( 'WooCommerce', 'luwipress-onyx' ),
						'value' => $wc_version ?: '—',
						'state' => $wc_active ? 'ok' : 'idle',
					),
				);
				foreach ( $stats as $stat ) :
				?>
					<div class="lwp-eco__stat lwp-eco__stat--<?php echo esc_attr( $stat['state'] ); ?>">
						<span class="lwp-eco__stat-label"><?php echo esc_html( $stat['label'] ); ?></span>
						<span class="lwp-eco__stat-value"><?php echo esc_html( $stat['value'] ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</header>

		<?php if ( ! $lp_active ) : ?>
			<div class="lwp-eco__alert lwp-eco__alert--warn">
				<strong><?php esc_html_e( 'LuwiPress plugin is not active.', 'luwipress-onyx' ); ?></strong>
				<?php esc_html_e( 'AI search, customer chat, and Knowledge-Graph-curated related products will stay dark until you activate it.', 'luwipress-onyx' ); ?>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'plugin-install.php?s=luwipress&tab=search&type=term' ) ); ?>">
					<?php esc_html_e( 'Install LuwiPress', 'luwipress-onyx' ); ?>
				</a>
			</div>
		<?php endif; ?>

		<section class="lwp-eco__section">
			<h2 class="lwp-eco__section-title">
				<span class="lwp-eco__eyebrow">— <?php esc_html_e( 'AI surfaces', 'luwipress-onyx' ); ?></span>
				<?php esc_html_e( 'What’s live on the storefront', 'luwipress-onyx' ); ?>
			</h2>

			<div class="lwp-eco__cards">
				<article class="lwp-eco__card lwp-eco__card--<?php echo $lp_active ? 'on' : 'off'; ?>">
					<header>
						<span class="lwp-eco__chip"><?php esc_html_e( 'AI search', 'luwipress-onyx' ); ?></span>
					</header>
					<p>
						<?php
						echo $lp_active
							? esc_html__( 'Search overlay shows product matches plus AI-suggested next searches as the visitor types. Cached 30 minutes per query.', 'luwipress-onyx' )
							: esc_html__( 'Idle — activate LuwiPress to bring this online.', 'luwipress-onyx' );
						?>
					</p>
				</article>

				<article class="lwp-eco__card lwp-eco__card--<?php echo $chat_enabled ? 'on' : 'off'; ?>">
					<header>
						<span class="lwp-eco__chip"><?php esc_html_e( 'Customer chat', 'luwipress-onyx' ); ?></span>
					</header>
					<?php if ( $chat_enabled ) : ?>
						<p>
							<?php esc_html_e( 'Sticky bubble live. Greeting:', 'luwipress-onyx' ); ?>
							<em>"<?php echo esc_html( $chat_greeting ); ?>"</em>
						</p>
						<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=luwipress&tab=customer-chat' ) ); ?>">
							<?php esc_html_e( 'Tune chat settings →', 'luwipress-onyx' ); ?>
						</a>
					<?php else : ?>
						<p><?php esc_html_e( 'Disabled — turn on Customer Chat in the LuwiPress plugin to surface the bubble.', 'luwipress-onyx' ); ?></p>
						<?php if ( $lp_active ) : ?>
							<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=luwipress&tab=customer-chat' ) ); ?>">
								<?php esc_html_e( 'Enable chat →', 'luwipress-onyx' ); ?>
							</a>
						<?php endif; ?>
					<?php endif; ?>
				</article>

				<article class="lwp-eco__card lwp-eco__card--<?php echo $wc_active ? 'on' : 'off'; ?>">
					<header>
						<span class="lwp-eco__chip"><?php esc_html_e( 'KG-related rail', 'luwipress-onyx' ); ?></span>
					</header>
					<p>
						<?php
						echo $wc_active
							? esc_html__( 'On every single-product page: 4 sibling instruments curated by master / luthier with category fallback. Cached 1 hour per product.', 'luwipress-onyx' )
							: esc_html__( 'Idle — needs WooCommerce active.', 'luwipress-onyx' );
						?>
					</p>
				</article>
			</div>
		</section>

		<section class="lwp-eco__section">
			<h2 class="lwp-eco__section-title">
				<span class="lwp-eco__eyebrow">— <?php esc_html_e( 'Friendly plugins', 'luwipress-onyx' ); ?></span>
				<?php esc_html_e( 'Detected and amplified', 'luwipress-onyx' ); ?>
			</h2>

			<table class="widefat lwp-eco__table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Capability', 'luwipress-onyx' ); ?></th>
						<th><?php esc_html_e( 'Plugin', 'luwipress-onyx' ); ?></th>
						<th><?php esc_html_e( 'What it gains the storefront', 'luwipress-onyx' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$rows = array();

					// SEO
					if ( $seo && $seo['plugin'] !== 'none' ) {
						$rows[] = array(
							'cap'    => __( 'SEO', 'luwipress-onyx' ),
							'plugin' => sprintf( '%s %s', $seo['plugin'], $seo['version'] ?: '' ),
							'gain'   => __( 'Theme breadcrumb yields to the SEO plugin’s rendered breadcrumb. Schema and meta keys auto-detected.', 'luwipress-onyx' ),
							'state'  => 'ok',
						);
					} else {
						$rows[] = array(
							'cap'    => __( 'SEO', 'luwipress-onyx' ),
							'plugin' => __( 'none detected', 'luwipress-onyx' ),
							'gain'   => __( 'Theme falls back to its own breadcrumb markup.', 'luwipress-onyx' ),
							'state'  => 'idle',
						);
					}

					// Translation
					if ( $lang && $lang['plugin'] !== 'none' ) {
						$active = isset( $lang['active_languages'] ) && is_array( $lang['active_languages'] )
							? implode( ', ', array_map( 'strtoupper', $lang['active_languages'] ) )
							: '';
						$rows[] = array(
							'cap'    => __( 'Translation', 'luwipress-onyx' ),
							'plugin' => sprintf( '%s %s', $lang['plugin'], $lang['version'] ?: '' ),
							'gain'   => sprintf(
								/* translators: %s: comma-separated active language codes */
								__( 'Topbar language pill renders %s. Hreflang tags auto-injected when the SEO plugin doesn’t emit them.', 'luwipress-onyx' ),
								$active
							),
							'state'  => 'ok',
						);
					}

					// Page builder
					if ( $pb && $pb['plugin'] !== 'none' ) {
						$rows[] = array(
							'cap'    => __( 'Page builder', 'luwipress-onyx' ),
							'plugin' => sprintf( '%s %s', $pb['plugin'], $pb['version'] ?? '' ),
							'gain'   => __( 'Theme yields header/footer to Theme Builder when configured. Custom Elementor widgets registered.', 'luwipress-onyx' ),
							'state'  => 'ok',
						);
					}

					// Cache
					if ( $cache && $cache['plugin'] !== 'none' ) {
						$rows[] = array(
							'cap'    => __( 'Cache', 'luwipress-onyx' ),
							'plugin' => sprintf( '%s %s', $cache['plugin'], $cache['version'] ?? '' ),
							'gain'   => __( 'Theme respects the cache plugin’s headers; AI surface endpoints are marked no-store at the plugin layer.', 'luwipress-onyx' ),
							'state'  => 'ok',
						);
					}

					// CRM
					if ( $crm && $crm['plugin'] !== 'none' ) {
						$rows[] = array(
							'cap'    => __( 'CRM', 'luwipress-onyx' ),
							'plugin' => sprintf( '%s %s', $crm['plugin'], $crm['version'] ?? '' ),
							'gain'   => __( 'LuwiPress avoids duplicating contact lists. Theme account popover stays single-source.', 'luwipress-onyx' ),
							'state'  => 'ok',
						);
					}

					// Newsletter
					if ( ! empty( $features['newsletter_form']['providers'] ) ) {
						$rows[] = array(
							'cap'    => __( 'Newsletter form', 'luwipress-onyx' ),
							'plugin' => implode( ', ', $features['newsletter_form']['providers'] ),
							'gain'   => __( 'Footer newsletter slot is ready to render the operator-picked shortcode.', 'luwipress-onyx' ),
							'state'  => 'ok',
						);
					}

					if ( empty( $rows ) ) {
						?>
						<tr><td colspan="3"><em><?php esc_html_e( 'No friendly plugins detected yet.', 'luwipress-onyx' ); ?></em></td></tr>
						<?php
					} else {
						foreach ( $rows as $r ) :
							?>
							<tr class="lwp-eco__row lwp-eco__row--<?php echo esc_attr( $r['state'] ); ?>">
								<td><strong><?php echo esc_html( $r['cap'] ); ?></strong></td>
								<td><?php echo esc_html( $r['plugin'] ); ?></td>
								<td><?php echo esc_html( $r['gain'] ); ?></td>
							</tr>
							<?php
						endforeach;
					}
					?>
				</tbody>
			</table>
		</section>

		<section class="lwp-eco__section">
			<h2 class="lwp-eco__section-title">
				<span class="lwp-eco__eyebrow">— <?php esc_html_e( 'Quick links', 'luwipress-onyx' ); ?></span>
				<?php esc_html_e( 'Where to tune things', 'luwipress-onyx' ); ?>
			</h2>

			<div class="lwp-eco__links">
				<a class="button" href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>">
					<?php esc_html_e( 'Theme Customizer', 'luwipress-onyx' ); ?>
				</a>
				<?php if ( $lp_active ) : ?>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=luwipress' ) ); ?>">
						<?php esc_html_e( 'LuwiPress dashboard', 'luwipress-onyx' ); ?>
					</a>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=luwipress&tab=customer-chat' ) ); ?>">
						<?php esc_html_e( 'Customer chat', 'luwipress-onyx' ); ?>
					</a>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=luwipress-knowledge-graph' ) ); ?>">
						<?php esc_html_e( 'Knowledge graph', 'luwipress-onyx' ); ?>
					</a>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=luwipress-usage' ) ); ?>">
						<?php esc_html_e( 'Token usage', 'luwipress-onyx' ); ?>
					</a>
				<?php endif; ?>
				<?php if ( $el_active ) : ?>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=elementor' ) ); ?>">
						<?php esc_html_e( 'Elementor settings', 'luwipress-onyx' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</section>

	</div>
	<?php
}
