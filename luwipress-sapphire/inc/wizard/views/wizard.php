<?php
/**
 * Wizard view — single SPA-style page rendered by Wizard::render_page().
 * Drives 5 progressive steps (detect, plan, plugins, brand, apply) entirely
 * over AJAX so each step can be tested/replayed independently.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$detector  = new LuwiPress_Sapphire_Detector();
$snapshot  = $detector->snapshot();
$summary   = $detector->summary_phrase( $snapshot );

$bridge    = new LuwiPress_Sapphire_TGM_Bridge();
$plugins   = $bridge->required_plugins_status();
?>
<div class="wrap luwipress-sapphire-wizard" data-snapshot='<?php echo esc_attr( wp_json_encode( $snapshot ) ); ?>'>

	<header class="lwp-wizard-head">
		<div class="lwp-wizard-mark"><span>L</span></div>
		<div class="lwp-wizard-title">
			<h1><?php esc_html_e( 'LuwiPress Sapphire setup', 'luwipress-sapphire' ); ?></h1>
			<p class="lwp-wizard-summary"><?php echo esc_html( $summary ); ?></p>
		</div>
	</header>

	<nav class="lwp-wizard-steps" aria-label="<?php esc_attr_e( 'Setup steps', 'luwipress-sapphire' ); ?>">
		<ol>
			<li class="is-active" data-step="1"><span class="num">1</span><?php esc_html_e( 'Detect', 'luwipress-sapphire' ); ?></li>
			<li data-step="2"><span class="num">2</span><?php esc_html_e( 'Choose path', 'luwipress-sapphire' ); ?></li>
			<li data-step="3"><span class="num">3</span><?php esc_html_e( 'Brand', 'luwipress-sapphire' ); ?></li>
			<li data-step="4"><span class="num">4</span><?php esc_html_e( 'Plugins', 'luwipress-sapphire' ); ?></li>
			<li data-step="5"><span class="num">5</span><?php esc_html_e( 'Apply', 'luwipress-sapphire' ); ?></li>
		</ol>
	</nav>

	<!-- ─── STEP 1 — DETECT ─────────────────────────────────────── -->
	<section class="lwp-wizard-panel is-active" data-panel="1">
		<h2 class="serif"><?php esc_html_e( 'Here\'s what we found.', 'luwipress-sapphire' ); ?></h2>
		<p class="lead"><?php echo esc_html( $summary ); ?></p>

		<div class="lwp-wizard-snapshot">
			<?php
			// WC card
			if ( ! empty( $snapshot['wc']['active'] ) ) :
				$wc = $snapshot['wc'];
			?>
				<div class="lwp-card">
					<h3>WooCommerce</h3>
					<dl>
						<dt><?php esc_html_e( 'Products', 'luwipress-sapphire' ); ?></dt>
						<dd><?php echo (int) $wc['product_count']; ?></dd>
						<dt><?php esc_html_e( 'Top categories', 'luwipress-sapphire' ); ?></dt>
						<dd><?php echo count( $wc['top_cats'] ); ?> (<?php
							echo esc_html( implode( ', ', array_slice( wp_list_pluck( $wc['top_cats'], 'name' ), 0, 4 ) ) );
							if ( count( $wc['top_cats'] ) > 4 ) echo '…';
						?>)</dd>
						<dt><?php esc_html_e( 'Sub-categories', 'luwipress-sapphire' ); ?></dt>
						<dd><?php echo count( $wc['sub_cats'] ); ?></dd>
						<dt><?php esc_html_e( 'Attributes', 'luwipress-sapphire' ); ?></dt>
						<dd><?php echo count( $wc['attributes'] ); ?>
							<?php if ( $wc['has_luthier'] ) : ?><span class="lwp-tag">pa_luthier ✓</span><?php endif; ?>
						</dd>
						<dt><?php esc_html_e( 'On sale', 'luwipress-sapphire' ); ?></dt>
						<dd><?php echo (int) $wc['on_sale_count']; ?></dd>
						<dt><?php esc_html_e( 'Currency', 'luwipress-sapphire' ); ?></dt>
						<dd><?php echo esc_html( $wc['currency'] ); ?></dd>
					</dl>
				</div>
			<?php else : ?>
				<div class="lwp-card lwp-card--empty">
					<h3>WooCommerce</h3>
					<p><?php esc_html_e( 'Not active yet — install on the next step.', 'luwipress-sapphire' ); ?></p>
				</div>
			<?php endif; ?>

			<!-- Content card -->
			<div class="lwp-card">
				<h3><?php esc_html_e( 'Content', 'luwipress-sapphire' ); ?></h3>
				<dl>
					<dt><?php esc_html_e( 'Pages', 'luwipress-sapphire' ); ?></dt>
					<dd><?php echo (int) $snapshot['content']['pages']; ?></dd>
					<dt><?php esc_html_e( 'Posts', 'luwipress-sapphire' ); ?></dt>
					<dd><?php echo (int) $snapshot['content']['posts']; ?></dd>
					<dt><?php esc_html_e( 'Front page', 'luwipress-sapphire' ); ?></dt>
					<dd>
						<?php if ( $snapshot['content']['front_page']['mode'] === 'page' ) : ?>
							<?php echo esc_html( $snapshot['content']['front_page']['home_title'] ?: __( 'Set, no title', 'luwipress-sapphire' ) ); ?>
						<?php else : ?>
							<?php esc_html_e( 'Latest posts (default)', 'luwipress-sapphire' ); ?>
						<?php endif; ?>
					</dd>
				</dl>
			</div>

			<!-- i18n card -->
			<?php if ( ! empty( $snapshot['i18n']['plugin'] ) ) : ?>
				<div class="lwp-card">
					<h3><?php esc_html_e( 'Languages', 'luwipress-sapphire' ); ?></h3>
					<dl>
						<dt><?php esc_html_e( 'Plugin', 'luwipress-sapphire' ); ?></dt>
						<dd><?php echo esc_html( strtoupper( $snapshot['i18n']['plugin'] ) ); ?></dd>
						<dt><?php esc_html_e( 'Active languages', 'luwipress-sapphire' ); ?></dt>
						<dd>
							<?php
							$codes = wp_list_pluck( $snapshot['i18n']['active'], 'code' );
							echo esc_html( implode( ' · ', array_map( 'strtoupper', $codes ) ) );
							?>
						</dd>
					</dl>
				</div>
			<?php endif; ?>

			<!-- Master luthiers card -->
			<?php if ( ! empty( $snapshot['masters'] ) ) : ?>
				<div class="lwp-card">
					<h3><?php esc_html_e( 'Master luthiers', 'luwipress-sapphire' ); ?></h3>
					<dl>
						<dt><?php esc_html_e( 'Detected', 'luwipress-sapphire' ); ?></dt>
						<dd><?php echo count( $snapshot['masters'] ); ?></dd>
						<dt><?php esc_html_e( 'Top makers', 'luwipress-sapphire' ); ?></dt>
						<dd>
							<?php echo esc_html( implode( ', ', array_slice( wp_list_pluck( $snapshot['masters'], 'name' ), 0, 4 ) ) ); ?>
							<?php if ( count( $snapshot['masters'] ) > 4 ) echo '…'; ?>
						</dd>
					</dl>
				</div>
			<?php endif; ?>

			<!-- Theme card -->
			<div class="lwp-card">
				<h3><?php esc_html_e( 'Theme switch', 'luwipress-sapphire' ); ?></h3>
				<dl>
					<dt><?php esc_html_e( 'Coming from', 'luwipress-sapphire' ); ?></dt>
					<dd><?php echo esc_html( $snapshot['theme_state']['name'] ); ?></dd>
					<?php if ( ! empty( $snapshot['theme_state']['switching_from_hello'] ) ) : ?>
						<dt><?php esc_html_e( 'Note', 'luwipress-sapphire' ); ?></dt>
						<dd><?php esc_html_e( 'Hello Elementor pages will be preserved.', 'luwipress-sapphire' ); ?></dd>
					<?php endif; ?>
				</dl>
			</div>
		</div>

		<?php
		// Slug-conflict transparency card. Renders only when at least one
		// page slug collides with a non-empty product_cat term — i.e.
		// visitors landing on /<slug>/ today reach the static page even
		// though commerce content lives at /product-category/<slug>/.
		$conflicts = isset( $snapshot['slug_conflicts'] ) && is_array( $snapshot['slug_conflicts'] )
			? $snapshot['slug_conflicts']
			: [];
		if ( ! empty( $conflicts ) ) :
		?>
			<div class="lwp-card lwp-card--alert" id="lwp-slug-conflicts-card">
				<h3>
					<?php
					/* translators: %d: number of slug conflicts */
					printf(
						esc_html( _n(
							'%d page-vs-category slug collision detected',
							'%d page-vs-category slug collisions detected',
							count( $conflicts ),
							'luwipress-sapphire'
						) ),
						(int) count( $conflicts )
					);
					?>
				</h3>
				<p class="lwp-alert-lead">
					<?php esc_html_e( 'These pages share their URL with a WooCommerce category. Visitors clicking the menu reach the page, not the live commerce archive — so the category template never renders. The Apply step can 301-redirect each one to its matching /product-category/ archive. The original page posts are kept editable in admin.', 'luwipress-sapphire' ); ?>
				</p>
				<table class="lwp-conflict-table widefat">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Slug', 'luwipress-sapphire' ); ?></th>
							<th><?php esc_html_e( 'Page', 'luwipress-sapphire' ); ?></th>
							<th><?php esc_html_e( 'Will redirect to', 'luwipress-sapphire' ); ?></th>
							<th><?php esc_html_e( 'Products', 'luwipress-sapphire' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $conflicts as $c ) : ?>
							<tr>
								<td><code>/<?php echo esc_html( $c['slug'] ); ?>/</code></td>
								<td>
									<a href="<?php echo esc_url( get_edit_post_link( (int) $c['page_id'] ) ); ?>" target="_blank">
										<?php echo esc_html( $c['page_title'] ); ?>
									</a>
								</td>
								<td><code>/product-category/<?php echo esc_html( $c['slug'] ); ?>/</code></td>
								<td><?php echo (int) $c['term_count']; ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>

		<footer class="lwp-wizard-actions">
			<button type="button" class="button button-primary lwp-next" data-target="2">
				<?php esc_html_e( 'Continue →', 'luwipress-sapphire' ); ?>
			</button>
		</footer>
	</section>

	<!-- ─── STEP 2 — PATH ─────────────────────────────────────── -->
	<section class="lwp-wizard-panel" data-panel="2">
		<h2 class="serif"><?php esc_html_e( 'Pick a setup path.', 'luwipress-sapphire' ); ?></h2>
		<p class="lead"><?php esc_html_e( 'No data is overwritten in any path. Pick the one that fits — you can always re-run the wizard.', 'luwipress-sapphire' ); ?></p>

		<div class="lwp-paths">
			<?php
			$has_content = ! empty( $snapshot['wc']['active'] ) || $snapshot['content']['posts'] > 0 || $snapshot['content']['pages'] > 0;
			?>

			<label class="lwp-path <?php echo $has_content ? 'lwp-path--recommended' : ''; ?>">
				<input type="radio" name="lwp_path" value="use_existing" <?php echo $has_content ? 'checked' : ''; ?>>
				<div class="lwp-path-body">
					<?php if ( $has_content ) : ?><span class="lwp-recommended-badge"><?php esc_html_e( 'Recommended', 'luwipress-sapphire' ); ?></span><?php endif; ?>
					<h3 class="serif"><?php esc_html_e( 'Use my existing content', 'luwipress-sapphire' ); ?></h3>
					<p>
						<?php esc_html_e( 'The wizard adapts the theme to whatever\'s already in your DB — your products, categories, posts, masters and menus all stay where they are.', 'luwipress-sapphire' ); ?>
					</p>
					<ul class="lwp-path-bullets">
						<li>✅ <?php esc_html_e( 'Top sellers auto-fill the homepage Featured grid', 'luwipress-sapphire' ); ?></li>
						<li>✅ <?php esc_html_e( 'Sub-categories auto-fill the megabar', 'luwipress-sapphire' ); ?></li>
						<li>✅ <?php esc_html_e( 'Header / footer / single product templates assigned site-wide', 'luwipress-sapphire' ); ?></li>
						<li>✅ <?php esc_html_e( 'Existing master luthiers wired into the homepage maker grid', 'luwipress-sapphire' ); ?></li>
						<li>✅ <?php esc_html_e( 'Front page is left alone if you already have one', 'luwipress-sapphire' ); ?></li>
					</ul>
				</div>
			</label>

			<label class="lwp-path <?php echo ! $has_content ? 'lwp-path--recommended' : ''; ?>">
				<input type="radio" name="lwp_path" value="tapadum_demo" <?php echo ! $has_content ? 'checked' : ''; ?>>
				<div class="lwp-path-body">
					<?php if ( ! $has_content ) : ?><span class="lwp-recommended-badge"><?php esc_html_e( 'Recommended', 'luwipress-sapphire' ); ?></span><?php endif; ?>
					<h3 class="serif"><?php esc_html_e( 'Start with the Tapadum demo', 'luwipress-sapphire' ); ?></h3>
					<p><?php esc_html_e( 'Sample products + posts + master profiles + a fully designed homepage. Everything tagged with _lwp_demo_data so you can clean it up later.', 'luwipress-sapphire' ); ?></p>
					<ul class="lwp-path-bullets">
						<li>📦 <?php esc_html_e( '~50 sample products across 5 categories', 'luwipress-sapphire' ); ?></li>
						<li>📝 <?php esc_html_e( '15 long-form journal posts', 'luwipress-sapphire' ); ?></li>
						<li>🎨 <?php esc_html_e( 'All 22 page templates pre-populated', 'luwipress-sapphire' ); ?></li>
					</ul>
				</div>
			</label>

			<label class="lwp-path">
				<input type="radio" name="lwp_path" value="empty">
				<div class="lwp-path-body">
					<h3 class="serif"><?php esc_html_e( 'Empty theme', 'luwipress-sapphire' ); ?></h3>
					<p><?php esc_html_e( 'Just the global colors and fonts. No pages, no products, no menus — perfect if you already know exactly what you want to build.', 'luwipress-sapphire' ); ?></p>
					<ul class="lwp-path-bullets">
						<li>🎨 <?php esc_html_e( 'Site Settings only (palette + fonts + buttons)', 'luwipress-sapphire' ); ?></li>
					</ul>
				</div>
			</label>
		</div>

		<footer class="lwp-wizard-actions">
			<button type="button" class="button button-secondary lwp-back" data-target="1"><?php esc_html_e( '← Back', 'luwipress-sapphire' ); ?></button>
			<button type="button" class="button button-primary lwp-next" data-target="3"><?php esc_html_e( 'Continue →', 'luwipress-sapphire' ); ?></button>
		</footer>
	</section>

	<!-- ─── STEP 3 — BRAND ─────────────────────────────────────── -->
	<section class="lwp-wizard-panel" data-panel="3">
		<h2 class="serif"><?php esc_html_e( 'Brand override.', 'luwipress-sapphire' ); ?></h2>
		<p class="lead"><?php esc_html_e( 'Optional. Skip if you want to use the defaults — you can change these later from Customizer.', 'luwipress-sapphire' ); ?></p>

		<div class="lwp-form">
			<div class="lwp-field">
				<label><?php esc_html_e( 'Logo', 'luwipress-sapphire' ); ?></label>
				<?php if ( $snapshot['site']['has_logo'] ) : ?>
					<p class="lwp-help">
						<?php esc_html_e( 'Detected an existing logo — we\'ll use it.', 'luwipress-sapphire' ); ?>
						<a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[control]=custom_logo' ) ); ?>" target="_blank">
							<?php esc_html_e( 'Change in Customizer →', 'luwipress-sapphire' ); ?>
						</a>
					</p>
				<?php else : ?>
					<p class="lwp-help">
						<a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[control]=custom_logo' ) ); ?>" target="_blank">
							<?php esc_html_e( 'Upload a logo via Customizer →', 'luwipress-sapphire' ); ?>
						</a>
						<?php esc_html_e( 'Otherwise we use the site title as a wordmark.', 'luwipress-sapphire' ); ?>
					</p>
				<?php endif; ?>
			</div>

			<div class="lwp-field">
				<label for="lwp-primary"><?php esc_html_e( 'Primary color', 'luwipress-sapphire' ); ?></label>
				<input type="color" id="lwp-primary" data-brand="primary_color" value="#735c00">
				<p class="lwp-help"><?php esc_html_e( 'Used for italic emphasis, prices, and the gold gradient. Default is the heritage gold #735c00.', 'luwipress-sapphire' ); ?></p>
			</div>

			<div class="lwp-field">
				<label for="lwp-accent"><?php esc_html_e( 'Accent color', 'luwipress-sapphire' ); ?></label>
				<input type="color" id="lwp-accent" data-brand="accent_color" value="#D4AF37">
				<p class="lwp-help"><?php esc_html_e( 'Bright accent for hero underlines and footer headings.', 'luwipress-sapphire' ); ?></p>
			</div>

			<div class="lwp-field">
				<label for="lwp-phone"><?php esc_html_e( 'Topbar phone', 'luwipress-sapphire' ); ?></label>
				<input type="text" id="lwp-phone" data-brand="phone" placeholder="+39 0546 614620">
			</div>

			<div class="lwp-field">
				<label for="lwp-email"><?php esc_html_e( 'Contact email', 'luwipress-sapphire' ); ?></label>
				<input type="email" id="lwp-email" data-brand="email" placeholder="<?php echo esc_attr( $snapshot['site']['admin_email'] ); ?>" value="<?php echo esc_attr( $snapshot['site']['admin_email'] ); ?>">
			</div>
		</div>

		<?php
		$lp_available = LuwiPress_Sapphire_AI_Content::is_luwipress_available();
		$ai_enabled   = LuwiPress_Sapphire_AI_Content::is_enabled();
		$ai_slots     = LuwiPress_Sapphire_AI_Content::slots();
		?>
		<div class="lwp-ai-block" data-ai-block>
			<div class="lwp-ai-head">
				<h3 class="serif"><?php esc_html_e( 'AI-generated copy (optional)', 'luwipress-sapphire' ); ?></h3>
				<?php if ( $lp_available ) : ?>
					<span class="lwp-tag" style="background:#fdfaef;border-color:#735c00;color:#735c00"><?php esc_html_e( 'LuwiPress AI detected', 'luwipress-sapphire' ); ?></span>
				<?php else : ?>
					<span class="lwp-tag"><?php esc_html_e( 'LuwiPress not installed', 'luwipress-sapphire' ); ?></span>
				<?php endif; ?>
			</div>
			<p class="lwp-ai-lead">
				<?php esc_html_e( 'Your products, posts, categories and menu stay exactly as they are. The few static lines that ship with the theme — hero lead, about story, master bio template, FAQ intro — can be drafted by AI in your brand voice using the LuwiPress AI engine.', 'luwipress-sapphire' ); ?>
			</p>

			<label class="lwp-ai-toggle">
				<input type="checkbox" id="lwp-ai-toggle" <?php echo $ai_enabled ? 'checked' : ''; ?> <?php echo $lp_available ? '' : 'disabled'; ?>>
				<span class="lwp-ai-toggle-track"><span class="lwp-ai-toggle-knob"></span></span>
				<span class="lwp-ai-toggle-label">
					<?php echo $lp_available
						? esc_html__( 'Generate static text with AI', 'luwipress-sapphire' )
						: esc_html__( 'Install LuwiPress to enable AI generation', 'luwipress-sapphire' );
					?>
				</span>
			</label>

			<div class="lwp-ai-slots" id="lwp-ai-slots" hidden>
				<?php foreach ( $ai_slots as $slot => $cfg ) : ?>
					<div class="lwp-ai-slot" data-slot="<?php echo esc_attr( $slot ); ?>">
						<div class="lwp-ai-slot-head">
							<strong><?php echo esc_html( $cfg['label'] ); ?></strong>
							<span class="lwp-ai-slot-status" data-status>—</span>
						</div>
						<p class="lwp-ai-slot-text" data-text>
							<em><?php echo esc_html( wp_trim_words( $cfg['default'], 24 ) ); ?></em>
						</p>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="lwp-ai-actions" id="lwp-ai-actions" hidden>
				<button type="button" class="button button-primary" id="lwp-ai-generate">
					<?php esc_html_e( 'Generate now', 'luwipress-sapphire' ); ?>
				</button>
				<button type="button" class="button button-secondary" id="lwp-ai-flush">
					<?php esc_html_e( 'Clear AI cache', 'luwipress-sapphire' ); ?>
				</button>
				<span class="lwp-ai-status-msg" id="lwp-ai-msg"></span>
			</div>
		</div>

		<footer class="lwp-wizard-actions">
			<button type="button" class="button button-secondary lwp-back" data-target="2"><?php esc_html_e( '← Back', 'luwipress-sapphire' ); ?></button>
			<button type="button" class="button button-primary lwp-next" data-target="4"><?php esc_html_e( 'Continue →', 'luwipress-sapphire' ); ?></button>
		</footer>
	</section>

	<!-- ─── STEP 4 — PLUGINS ─────────────────────────────────────── -->
	<section class="lwp-wizard-panel" data-panel="4">
		<h2 class="serif"><?php esc_html_e( 'Required plugins.', 'luwipress-sapphire' ); ?></h2>
		<p class="lead"><?php esc_html_e( 'Click "Install" on each missing plugin. We\'ll wait for them all to be active before applying the theme.', 'luwipress-sapphire' ); ?></p>

		<div class="lwp-plugins">
			<?php foreach ( $plugins as $key => $p ) : ?>
				<div class="lwp-plugin <?php echo $p['active'] ? 'is-active' : ( $p['installed'] ? 'is-installed' : 'is-missing' ); ?>" data-plugin="<?php echo esc_attr( $key ); ?>">
					<div class="lwp-plugin-info">
						<h3><?php echo esc_html( $p['name'] ); ?>
							<?php if ( ! $p['required'] ) : ?>
								<span class="lwp-tag"><?php esc_html_e( 'Optional', 'luwipress-sapphire' ); ?></span>
							<?php endif; ?>
						</h3>
						<p><?php echo esc_html( $p['why'] ); ?></p>
					</div>
					<div class="lwp-plugin-status">
						<?php if ( $p['active'] ) : ?>
							<span class="lwp-status lwp-status--ok">✅ <?php esc_html_e( 'Active', 'luwipress-sapphire' ); ?></span>
						<?php elseif ( $p['installed'] ) : ?>
							<a href="<?php echo esc_url( $p['activate_url'] ); ?>" class="button button-secondary"><?php esc_html_e( 'Activate', 'luwipress-sapphire' ); ?></a>
						<?php else : ?>
							<a href="<?php echo esc_url( $p['install_url'] ); ?>" class="button button-primary" target="_blank"><?php esc_html_e( 'Install', 'luwipress-sapphire' ); ?></a>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<footer class="lwp-wizard-actions">
			<button type="button" class="button button-secondary lwp-back" data-target="3"><?php esc_html_e( '← Back', 'luwipress-sapphire' ); ?></button>
			<button type="button" class="button button-secondary" id="lwp-recheck"><?php esc_html_e( '↻ Re-check plugins', 'luwipress-sapphire' ); ?></button>
			<button type="button" class="button button-primary lwp-next" data-target="5"><?php esc_html_e( 'Apply →', 'luwipress-sapphire' ); ?></button>
		</footer>
	</section>

	<!-- ─── STEP 5 — APPLY ─────────────────────────────────────── -->
	<section class="lwp-wizard-panel" data-panel="5">
		<h2 class="serif"><?php esc_html_e( 'Applying changes…', 'luwipress-sapphire' ); ?></h2>

		<?php
		// Repeat the conflict count here so the operator decides at the
		// last gate, with the redirect toggle adjacent. Default ON when
		// any conflict is present — operator unchecks to skip.
		$conflict_count = isset( $snapshot['slug_conflicts'] ) && is_array( $snapshot['slug_conflicts'] )
			? count( $snapshot['slug_conflicts'] )
			: 0;
		if ( $conflict_count > 0 ) :
		?>
			<div class="lwp-apply-opts">
				<label class="lwp-apply-opt">
					<input type="checkbox" id="lwp-resolve-conflicts" checked>
					<span class="lwp-apply-opt__label">
						<strong>
							<?php
							/* translators: %d: number of slug conflicts */
							printf(
								esc_html( _n(
									'Auto-redirect %d slug conflict (recommended)',
									'Auto-redirect %d slug conflicts (recommended)',
									$conflict_count,
									'luwipress-sapphire'
								) ),
								(int) $conflict_count
							);
							?>
						</strong>
						<small><?php esc_html_e( 'Each /<slug>/ URL 301-redirects to /product-category/<slug>/. Pages stay editable in admin.', 'luwipress-sapphire' ); ?></small>
					</span>
				</label>
			</div>
		<?php endif; ?>

		<div class="lwp-apply-status" data-status="idle">
			<div class="lwp-apply-progress">
				<span class="lwp-apply-bar"></span>
			</div>
			<ul class="lwp-apply-log"></ul>
		</div>

		<div class="lwp-apply-done" hidden>
			<h3 class="serif"><?php esc_html_e( 'All set 🎉', 'luwipress-sapphire' ); ?></h3>
			<p><?php esc_html_e( 'Open the front of the site to verify everything looks right. You can re-run this wizard from Tools → Site Health any time.', 'luwipress-sapphire' ); ?></p>
			<div class="lwp-wizard-actions">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="button button-primary" target="_blank"><?php esc_html_e( 'View site →', 'luwipress-sapphire' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Open Customizer', 'luwipress-sapphire' ); ?></a>
			</div>
		</div>

		<footer class="lwp-wizard-actions">
			<button type="button" class="button button-secondary lwp-back" data-target="4"><?php esc_html_e( '← Back', 'luwipress-sapphire' ); ?></button>
			<button type="button" class="button button-primary" id="lwp-apply-run"><?php esc_html_e( 'Run apply', 'luwipress-sapphire' ); ?></button>
		</footer>
	</section>

</div>
