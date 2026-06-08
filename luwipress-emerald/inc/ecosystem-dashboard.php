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
 * Page lives at Appearance → LuwiPress Emerald (top-level page if Appearance
 * isn't suitable). Read-only by design — every setting is owned by either
 * the LuwiPress plugin or the Customizer; this panel just observes.
 *
 * @package luwipress-emerald
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', function () {
	add_theme_page(
		__( 'LuwiPress Emerald', 'luwipress-emerald' ),
		__( 'LuwiPress Emerald', 'luwipress-emerald' ),
		'manage_options',
		'luwipress-emerald-ecosystem',
		'lwp_emerald_render_ecosystem_dashboard'
	);
}, 9 );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'appearance_page_luwipress-emerald-ecosystem' !== $hook ) {
		return;
	}
	wp_enqueue_style(
		'luwipress-emerald-ecosystem',
		LUWIPRESS_EMERALD_URI . '/assets/css/ecosystem-dashboard.css',
		array(),
		LUWIPRESS_EMERALD_VERSION
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
function lwp_emerald_render_ecosystem_dashboard() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to view this page.', 'luwipress-emerald' ) );
	}

	$lp_active = lwp_emerald_lp_active();
	$detector  = lwp_emerald_lp_detector();

	$lp_version    = $lp_active ? LUWIPRESS_VERSION : '';
	$theme_version = LUWIPRESS_EMERALD_VERSION;
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

	$features = apply_filters( 'luwipress_emerald_ecosystem_features', array() );

	$chat_enabled  = lwp_emerald_lp_chat_enabled();
	$chat_greeting = $chat_enabled ? (string) get_option( 'luwipress_chat_greeting', '' ) : '';

	?>
	<div class="wrap lwp-eco">

		<header class="lwp-eco__hero">
			<div class="lwp-eco__hero-text">
				<span class="lwp-eco__eyebrow">— <?php esc_html_e( 'LuwiPress ecosystem', 'luwipress-emerald' ); ?></span>
				<h1 class="lwp-eco__title">
					<?php
					printf(
						/* translators: %s: theme version */
						esc_html__( 'LuwiPress Emerald %s', 'luwipress-emerald' ),
						esc_html( $theme_version )
					);
					?>
				</h1>
				<p class="lwp-eco__lead">
					<?php esc_html_e( 'Your theme reads from the LuwiPress plugin’s AI engine, knowledge graph, and plugin detector. Every friendly plugin you activate amplifies a feature on the storefront.', 'luwipress-emerald' ); ?>
				</p>
			</div>
			<div class="lwp-eco__hero-stats">
				<?php
				$stats = array(
					array(
						'label' => __( 'LuwiPress', 'luwipress-emerald' ),
						'value' => $lp_version ?: '—',
						'state' => $lp_active ? 'ok' : 'warn',
					),
					array(
						'label' => __( 'Elementor', 'luwipress-emerald' ),
						'value' => $el_version ?: '—',
						'state' => $el_active ? 'ok' : 'warn',
					),
					array(
						'label' => __( 'WooCommerce', 'luwipress-emerald' ),
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
				<strong><?php esc_html_e( 'LuwiPress plugin is not active.', 'luwipress-emerald' ); ?></strong>
				<?php esc_html_e( 'AI search, customer chat, and Knowledge-Graph-curated related products will stay dark until you activate it.', 'luwipress-emerald' ); ?>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'plugin-install.php?s=luwipress&tab=search&type=term' ) ); ?>">
					<?php esc_html_e( 'Install LuwiPress', 'luwipress-emerald' ); ?>
				</a>
			</div>
		<?php endif; ?>

		<section class="lwp-eco__section">
			<h2 class="lwp-eco__section-title">
				<span class="lwp-eco__eyebrow">— <?php esc_html_e( 'AI surfaces', 'luwipress-emerald' ); ?></span>
				<?php esc_html_e( 'What’s live on the storefront', 'luwipress-emerald' ); ?>
			</h2>

			<div class="lwp-eco__cards">
				<article class="lwp-eco__card lwp-eco__card--<?php echo $lp_active ? 'on' : 'off'; ?>">
					<header>
						<span class="lwp-eco__chip"><?php esc_html_e( 'AI search', 'luwipress-emerald' ); ?></span>
					</header>
					<p>
						<?php
						echo $lp_active
							? esc_html__( 'Search overlay shows product matches plus AI-suggested next searches as the visitor types. Cached 30 minutes per query.', 'luwipress-emerald' )
							: esc_html__( 'Idle — activate LuwiPress to bring this online.', 'luwipress-emerald' );
						?>
					</p>
				</article>

				<article class="lwp-eco__card lwp-eco__card--<?php echo $chat_enabled ? 'on' : 'off'; ?>">
					<header>
						<span class="lwp-eco__chip"><?php esc_html_e( 'Customer chat', 'luwipress-emerald' ); ?></span>
					</header>
					<?php if ( $chat_enabled ) : ?>
						<p>
							<?php esc_html_e( 'Sticky bubble live. Greeting:', 'luwipress-emerald' ); ?>
							<em>"<?php echo esc_html( $chat_greeting ); ?>"</em>
						</p>
						<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=luwipress&tab=customer-chat' ) ); ?>">
							<?php esc_html_e( 'Tune chat settings →', 'luwipress-emerald' ); ?>
						</a>
					<?php else : ?>
						<p><?php esc_html_e( 'Disabled — turn on Customer Chat in the LuwiPress plugin to surface the bubble.', 'luwipress-emerald' ); ?></p>
						<?php if ( $lp_active ) : ?>
							<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=luwipress&tab=customer-chat' ) ); ?>">
								<?php esc_html_e( 'Enable chat →', 'luwipress-emerald' ); ?>
							</a>
						<?php endif; ?>
					<?php endif; ?>
				</article>

				<article class="lwp-eco__card lwp-eco__card--<?php echo $wc_active ? 'on' : 'off'; ?>">
					<header>
						<span class="lwp-eco__chip"><?php esc_html_e( 'KG-related rail', 'luwipress-emerald' ); ?></span>
					</header>
					<p>
						<?php
						echo $wc_active
							? esc_html__( 'On every single-product page: 4 sibling instruments curated by master / luthier with category fallback. Cached 1 hour per product.', 'luwipress-emerald' )
							: esc_html__( 'Idle — needs WooCommerce active.', 'luwipress-emerald' );
						?>
					</p>
				</article>
			</div>
		</section>

		<section class="lwp-eco__section">
			<h2 class="lwp-eco__section-title">
				<span class="lwp-eco__eyebrow">— <?php esc_html_e( 'Friendly plugins', 'luwipress-emerald' ); ?></span>
				<?php esc_html_e( 'Detected and amplified', 'luwipress-emerald' ); ?>
			</h2>

			<table class="widefat lwp-eco__table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Capability', 'luwipress-emerald' ); ?></th>
						<th><?php esc_html_e( 'Plugin', 'luwipress-emerald' ); ?></th>
						<th><?php esc_html_e( 'What it gains the storefront', 'luwipress-emerald' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$rows = array();

					// SEO
					if ( $seo && $seo['plugin'] !== 'none' ) {
						$rows[] = array(
							'cap'    => __( 'SEO', 'luwipress-emerald' ),
							'plugin' => sprintf( '%s %s', $seo['plugin'], $seo['version'] ?: '' ),
							'gain'   => __( 'Theme breadcrumb yields to the SEO plugin’s rendered breadcrumb. Schema and meta keys auto-detected.', 'luwipress-emerald' ),
							'state'  => 'ok',
						);
					} else {
						$rows[] = array(
							'cap'    => __( 'SEO', 'luwipress-emerald' ),
							'plugin' => __( 'none detected', 'luwipress-emerald' ),
							'gain'   => __( 'Theme falls back to its own breadcrumb markup.', 'luwipress-emerald' ),
							'state'  => 'idle',
						);
					}

					// Translation
					if ( $lang && $lang['plugin'] !== 'none' ) {
						$active = isset( $lang['active_languages'] ) && is_array( $lang['active_languages'] )
							? implode( ', ', array_map( 'strtoupper', $lang['active_languages'] ) )
							: '';
						$rows[] = array(
							'cap'    => __( 'Translation', 'luwipress-emerald' ),
							'plugin' => sprintf( '%s %s', $lang['plugin'], $lang['version'] ?: '' ),
							'gain'   => sprintf(
								/* translators: %s: comma-separated active language codes */
								__( 'Topbar language pill renders %s. Hreflang tags auto-injected when the SEO plugin doesn’t emit them.', 'luwipress-emerald' ),
								$active
							),
							'state'  => 'ok',
						);
					}

					// Page builder
					if ( $pb && $pb['plugin'] !== 'none' ) {
						$rows[] = array(
							'cap'    => __( 'Page builder', 'luwipress-emerald' ),
							'plugin' => sprintf( '%s %s', $pb['plugin'], $pb['version'] ?? '' ),
							'gain'   => __( 'Theme yields header/footer to Theme Builder when configured. Custom Elementor widgets registered.', 'luwipress-emerald' ),
							'state'  => 'ok',
						);
					}

					// Cache
					if ( $cache && $cache['plugin'] !== 'none' ) {
						$rows[] = array(
							'cap'    => __( 'Cache', 'luwipress-emerald' ),
							'plugin' => sprintf( '%s %s', $cache['plugin'], $cache['version'] ?? '' ),
							'gain'   => __( 'Theme respects the cache plugin’s headers; AI surface endpoints are marked no-store at the plugin layer.', 'luwipress-emerald' ),
							'state'  => 'ok',
						);
					}

					// CRM
					if ( $crm && $crm['plugin'] !== 'none' ) {
						$rows[] = array(
							'cap'    => __( 'CRM', 'luwipress-emerald' ),
							'plugin' => sprintf( '%s %s', $crm['plugin'], $crm['version'] ?? '' ),
							'gain'   => __( 'LuwiPress avoids duplicating contact lists. Theme account popover stays single-source.', 'luwipress-emerald' ),
							'state'  => 'ok',
						);
					}

					// Newsletter
					if ( ! empty( $features['newsletter_form']['providers'] ) ) {
						$rows[] = array(
							'cap'    => __( 'Newsletter form', 'luwipress-emerald' ),
							'plugin' => implode( ', ', $features['newsletter_form']['providers'] ),
							'gain'   => __( 'Footer newsletter slot is ready to render the operator-picked shortcode.', 'luwipress-emerald' ),
							'state'  => 'ok',
						);
					}

					if ( empty( $rows ) ) {
						?>
						<tr><td colspan="3"><em><?php esc_html_e( 'No friendly plugins detected yet.', 'luwipress-emerald' ); ?></em></td></tr>
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
				<span class="lwp-eco__eyebrow">— <?php esc_html_e( 'Quick links', 'luwipress-emerald' ); ?></span>
				<?php esc_html_e( 'Where to tune things', 'luwipress-emerald' ); ?>
			</h2>

			<div class="lwp-eco__links">
				<a class="button" href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>">
					<?php esc_html_e( 'Theme Customizer', 'luwipress-emerald' ); ?>
				</a>
				<?php if ( $lp_active ) : ?>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=luwipress' ) ); ?>">
						<?php esc_html_e( 'LuwiPress dashboard', 'luwipress-emerald' ); ?>
					</a>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=luwipress&tab=customer-chat' ) ); ?>">
						<?php esc_html_e( 'Customer chat', 'luwipress-emerald' ); ?>
					</a>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=luwipress-knowledge-graph' ) ); ?>">
						<?php esc_html_e( 'Knowledge graph', 'luwipress-emerald' ); ?>
					</a>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=luwipress-usage' ) ); ?>">
						<?php esc_html_e( 'Token usage', 'luwipress-emerald' ); ?>
					</a>
				<?php endif; ?>
				<?php if ( $el_active ) : ?>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=elementor' ) ); ?>">
						<?php esc_html_e( 'Elementor settings', 'luwipress-emerald' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</section>

	</div>
	<?php
}
