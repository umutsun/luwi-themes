<?php
/**
 * Theme header. Three rendering paths, picked at runtime:
 *
 *   1. Elementor Pro + Theme Builder Header template active
 *      → Pro renders our 01-header.json template; we yield via
 *        elementor_theme_do_location('header') and stop.
 *
 *   2. Elementor (free) + ElementsKit Lite Header template active
 *      → ElementsKit's Header Builder hooks before our fallback;
 *        elementor_theme_do_location returns true.
 *
 *   3. No theme builder header at all
 *      → We render a full-fidelity Gold header inline:
 *        topbar (location/phone/email + lang switcher) + sticky bar
 *        (logo + mega-menu walker + icon buttons) + megabar (sub-cat strip).
 *      Mega menu is built from any registered nav menu — picks the largest
 *      menu by item count if no `primary` location is set.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="profile" href="https://gmpg.org/xfn/11" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php
$elementor_header_active =
	did_action( 'elementor/loaded' ) &&
	function_exists( 'elementor_theme_do_location' ) &&
	elementor_theme_do_location( 'header' );

if ( ! $elementor_header_active ) :

	$primary_menu_id = 0;
	$locations = get_nav_menu_locations();
	if ( ! empty( $locations['primary'] ) ) {
		$primary_menu_id = (int) $locations['primary'];
	} else {
		// Fall back to the largest registered menu.
		$menus = wp_get_nav_menus();
		$best  = null;
		foreach ( $menus as $m ) {
			$items = wp_get_nav_menu_items( $m->term_id );
			$count = is_array( $items ) ? count( $items ) : 0;
			if ( ! $best || $count > $best['count'] ) {
				$best = [ 'id' => $m->term_id, 'count' => $count ];
			}
		}
		$primary_menu_id = $best ? (int) $best['id'] : 0;
	}

	$site_url = esc_url( home_url( '/' ) );
	$contact_email = get_option( 'admin_email' );
?>

<div class="lwp-topbar">
	<div class="lwp-topbar-inner">
		<div class="lwp-topbar-l">
			<?php
			$loc   = get_theme_mod( 'luwipress_gold_topbar_location', '' );
			$phone = get_theme_mod( 'luwipress_gold_topbar_phone', '' );
			$email = get_theme_mod( 'luwipress_gold_topbar_email', $contact_email );
			if ( $loc !== '' ) {
				echo '<span class="lwp-topbar-loc">📍 ' . esc_html( $loc ) . '</span>';
			}
			if ( $phone !== '' ) {
				echo '<a href="' . esc_url( 'tel:' . preg_replace( '/\s+/', '', $phone ) ) . '">' . esc_html( $phone ) . '</a>';
			}
			if ( $email !== '' ) {
				echo '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
			}
			?>
		</div>
		<div class="lwp-topbar-r">
			<?php
			// Language pill — auto-detected from WPML / Polylang. Reference
			// design renders a compact "EN · IT · FR · ES" strip with the
			// active language underlined in gold. Falls back silently when
			// neither plugin is active (single-language sites get a clean
			// topbar).
			$langs = [];
			$active_code = '';

			if ( has_filter( 'wpml_active_languages' ) ) {
				$wpml = apply_filters( 'wpml_active_languages', null, 'orderby=code' );
				if ( is_array( $wpml ) ) {
					foreach ( $wpml as $code => $l ) {
						$langs[] = [
							'code'   => strtoupper( $l['language_code'] ?? $code ),
							'url'    => $l['url'] ?? '#',
							'active' => ! empty( $l['active'] ),
						];
						if ( ! empty( $l['active'] ) ) $active_code = strtoupper( $l['language_code'] ?? $code );
					}
				}
			} elseif ( function_exists( 'pll_the_languages' ) ) {
				$pll = pll_the_languages( [ 'raw' => 1, 'hide_if_empty' => 0 ] );
				if ( is_array( $pll ) ) {
					foreach ( $pll as $code => $l ) {
						$langs[] = [
							'code'   => strtoupper( $l['slug'] ?? $code ),
							'url'    => $l['url'] ?? '#',
							'active' => ! empty( $l['current_lang'] ),
						];
						if ( ! empty( $l['current_lang'] ) ) $active_code = strtoupper( $l['slug'] ?? $code );
					}
				}
			}

			if ( ! empty( $langs ) && count( $langs ) > 1 ) :
				?>
				<span class="lwp-lang-pill" role="navigation" aria-label="<?php esc_attr_e( 'Languages', 'luwipress-gold' ); ?>">
					<?php
					$last_idx = count( $langs ) - 1;
					foreach ( $langs as $i => $l ) {
						printf(
							'<a href="%s" class="lwp-lang-pill__code%s" hreflang="%s">%s</a>',
							esc_url( $l['url'] ),
							$l['active'] ? ' is-active' : '',
							esc_attr( strtolower( $l['code'] ) ),
							esc_html( $l['code'] )
						);
						if ( $i < $last_idx ) {
							echo '<span class="lwp-lang-pill__sep" aria-hidden="true">·</span>';
						}
					}
					?>
				</span>
				<span class="lwp-topbar-sep" aria-hidden="true">|</span>
			<?php endif; ?>

			<?php
			// Track-order link — points at any URL the operator sets in
			// Customizer (typically a page hosting the [woocommerce_order_tracking]
			// shortcode, or the my-account orders endpoint). Hidden when
			// the URL is empty so the topbar stays clean for stores that
			// don't surface tracking publicly.
			$track_url = get_theme_mod( 'luwipress_gold_topbar_track_url', '' );
			if ( ! $track_url && function_exists( 'wc_get_page_permalink' ) ) {
				// Reasonable fallback — the my-account orders endpoint.
				$shop_id = (int) get_option( 'woocommerce_myaccount_page_id' );
				if ( $shop_id > 0 ) {
					$track_url = get_permalink( $shop_id );
				}
			}
			$track_label = get_theme_mod( 'luwipress_gold_topbar_track_label', __( 'Track order', 'luwipress-gold' ) );
			if ( $track_url ) : ?>
				<a href="<?php echo esc_url( $track_url ); ?>" class="lwp-topbar-track">
					<?php echo esc_html( $track_label ); ?> <span aria-hidden="true">→</span>
				</a>
				<span class="lwp-topbar-sep" aria-hidden="true">|</span>
			<?php endif; ?>

			<?php
			$promo = get_theme_mod( 'luwipress_gold_topbar_promo', __( 'Free DHL shipping over €450', 'luwipress-gold' ) );
			if ( $promo !== '' ) {
				echo '<span class="lwp-topbar-promo">' . esc_html( $promo ) . '</span>';
			}
			?>
		</div>
	</div>
</div>

<header class="lwp-site-header" role="banner">
	<div class="lwp-site-header-inner">
		<?php if ( has_custom_logo() ) : ?>
			<div class="lwp-site-header-logo lwp-site-header-logo--image">
				<?php the_custom_logo(); ?>
			</div>
		<?php else :
			// Stylized text wordmark — Tapadum-style "tapad<em>u</em>m"
			// with the accent letter rendered italic-gold + a red dot
			// suffix. Operator can override the accent letter via
			// Customizer → Brand → "Wordmark accent letter" (defaults
			// to 'u' which matches the Tapadum kit reference).
			$site_name = get_bloginfo( 'name' );
			$accent    = trim( (string) get_theme_mod( 'luwipress_gold_logo_accent_letter', 'u' ) );
			$accent    = mb_substr( $accent, 0, 1 );
			$wordmark_html = esc_html( $site_name );

			if ( $accent !== '' && $site_name !== '' ) {
				// Find first case-insensitive match of the accent letter,
				// then split the site name around it. Preserves the
				// original case of every other character.
				$pos = mb_stripos( $site_name, $accent );
				if ( $pos === false ) {
					// No match — fall back to italicizing the literal
					// middle character so the wordmark still gets visual
					// weight on names that don't contain the configured
					// accent.
					$len = mb_strlen( $site_name );
					if ( $len >= 3 ) {
						$pos = (int) floor( $len / 2 );
					}
				}
				if ( $pos !== false && $pos < mb_strlen( $site_name ) ) {
					$before = mb_substr( $site_name, 0, $pos );
					$letter = mb_substr( $site_name, $pos, 1 );
					$after  = mb_substr( $site_name, $pos + 1 );
					$wordmark_html =
						esc_html( $before ) .
						'<em class="lwp-wordmark__accent">' . esc_html( $letter ) . '</em>' .
						esc_html( $after );
				}
			}
			?>
			<a class="lwp-site-header-logo lwp-site-header-logo--text" href="<?php echo $site_url; ?>" rel="home">
				<span class="lwp-wordmark"><?php echo $wordmark_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — built from esc_html'd parts ?></span>
			</a>
		<?php endif; ?>

		<?php
		if ( $primary_menu_id && class_exists( 'LuwiPress_Gold_Widget_Mega_Menu' ) ) {
			LuwiPress_Gold_Widget_Mega_Menu::render_navigation_html( $primary_menu_id, [
				'threshold'   => 4,
				'cols_pref'   => 'auto',
				'show_counts' => true,
			] );
		}
		?>

		<div class="lwp-site-header-actions">
			<a href="<?php echo esc_url( $site_url . '?s=' ); ?>" class="lwp-icon-btn lwp-search-btn" data-lwp-search-toggle aria-label="<?php esc_attr_e( 'Search', 'luwipress-gold' ); ?>">⌕</a>
			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<div class="lwp-account-wrap">
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="lwp-icon-btn lwp-account-btn" data-lwp-account-toggle aria-label="<?php esc_attr_e( 'Account', 'luwipress-gold' ); ?>" aria-expanded="false">◯</a>
					<?php
					// Smart account popover. Logged-out: quick login form +
					// "Create account" CTA + "Why sign in" perks. Logged-in:
					// greeting + dashboard / orders / wishlist / sign-out.
					?>
					<div class="lwp-account-pop" role="menu" aria-hidden="true">
						<?php if ( is_user_logged_in() ) :
							$cu = wp_get_current_user(); ?>
							<div class="lwp-account-pop__head">
								<span class="lwp-account-pop__hi">
									<?php
									/* translators: %s: customer first name */
									printf( esc_html__( 'Hi, %s.', 'luwipress-gold' ), esc_html( $cu->first_name ?: $cu->display_name ) );
									?>
								</span>
								<span class="lwp-account-pop__em"><?php echo esc_html( $cu->user_email ); ?></span>
							</div>
							<nav class="lwp-account-pop__nav" aria-label="<?php esc_attr_e( 'Account quick links', 'luwipress-gold' ); ?>">
								<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'Dashboard', 'luwipress-gold' ); ?> <span>→</span></a>
								<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>"><?php esc_html_e( 'My orders', 'luwipress-gold' ); ?> <span>→</span></a>
								<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-address' ) ); ?>"><?php esc_html_e( 'Addresses', 'luwipress-gold' ); ?> <span>→</span></a>
								<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-account' ) ); ?>"><?php esc_html_e( 'Account details', 'luwipress-gold' ); ?> <span>→</span></a>
							</nav>
							<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="lwp-account-pop__signout"><?php esc_html_e( 'Sign out →', 'luwipress-gold' ); ?></a>
						<?php else :
							$login_url    = wc_get_page_permalink( 'myaccount' );
							$register_on  = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ); ?>
							<div class="lwp-account-pop__head">
								<span class="lwp-account-pop__hi"><?php esc_html_e( 'Welcome', 'luwipress-gold' ); ?></span>
								<span class="lwp-account-pop__em"><?php esc_html_e( 'Sign in to view orders, saved instruments and addresses.', 'luwipress-gold' ); ?></span>
							</div>
							<form class="lwp-account-pop__form" method="post" action="<?php echo esc_url( $login_url ); ?>">
								<label class="lwp-account-pop__label">
									<?php esc_html_e( 'Email or username', 'luwipress-gold' ); ?>
									<input type="text" name="username" autocomplete="username" required />
								</label>
								<label class="lwp-account-pop__label">
									<?php esc_html_e( 'Password', 'luwipress-gold' ); ?>
									<input type="password" name="password" autocomplete="current-password" required />
								</label>
								<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
								<button type="submit" name="login" value="1" class="lwp-btn-primary"><?php esc_html_e( 'Sign in', 'luwipress-gold' ); ?></button>
							</form>
							<div class="lwp-account-pop__alts">
								<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" class="lwp-ulink"><?php esc_html_e( 'Forgot password?', 'luwipress-gold' ); ?></a>
								<?php if ( $register_on ) : ?>
									<a href="<?php echo esc_url( $login_url ); ?>" class="lwp-ulink"><?php esc_html_e( 'Create account', 'luwipress-gold' ); ?></a>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<?php $cart_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0; ?>
				<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="lwp-icon-btn lwp-cart-btn lwp-cart-icon" data-lwp-cart-toggle aria-label="<?php esc_attr_e( 'Cart', 'luwipress-gold' ); ?>">
					▣<?php if ( $cart_count > 0 ) : ?><span class="lwp-cart-badge"><?php echo (int) $cart_count; ?></span><?php endif; ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</header>

<?php
// Search overlay — full-screen panel toggled by the header search button.
// Submits to WP's native ?s= search; product results filterable via the
// post_type=product hidden field below.
?>
<div class="lwp-search-overlay" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Search', 'luwipress-gold' ); ?>" hidden-on-load>
	<div class="lwp-search-panel">
		<button type="button" class="lwp-search-close" aria-label="<?php esc_attr_e( 'Close search', 'luwipress-gold' ); ?>">×</button>
		<form role="search" method="get" class="lwp-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label for="lwp-search-input" class="lwp-search-label"><?php esc_html_e( 'Search the catalogue', 'luwipress-gold' ); ?></label>
			<div class="lwp-search-input-wrap">
				<span class="lwp-search-icon" aria-hidden="true">⌕</span>
				<input id="lwp-search-input" type="search" name="s" class="lwp-search-input" placeholder="<?php esc_attr_e( 'Try “oud”, “darbuka”, “Yildirim”…', 'luwipress-gold' ); ?>" autocomplete="off" />
				<?php if ( post_type_exists( 'product' ) ) : ?>
					<input type="hidden" name="post_type" value="product" />
				<?php endif; ?>
			</div>
			<div class="lwp-search-hint"><?php esc_html_e( 'Press Enter to search · Esc to close', 'luwipress-gold' ); ?></div>
		</form>
	</div>
</div>

<?php if ( class_exists( 'WooCommerce' ) ) : ?>
<?php
// Mini-cart drawer — slide-in panel from the right. Auto-opens after
// add-to-cart (jQuery `added_to_cart` event in frontend.js). Content is
// the live WooCommerce mini-cart fragment, so quantity / removal /
// totals update over AJAX without a page reload.
?>
<aside class="lwp-cart-drawer" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Cart', 'luwipress-gold' ); ?>">
	<div class="lwp-cart-drawer__panel">
		<div class="lwp-cart-drawer__head">
			<h3><?php esc_html_e( 'Your cart', 'luwipress-gold' ); ?></h3>
			<button type="button" class="lwp-cart-drawer__close" aria-label="<?php esc_attr_e( 'Close cart', 'luwipress-gold' ); ?>">×</button>
		</div>
		<div class="lwp-cart-drawer__body widget_shopping_cart_content">
			<?php woocommerce_mini_cart(); ?>
		</div>
	</div>
</aside>
<?php endif; ?>

<?php
// Note: the previous auto-rendered "lwp-megabar" sub-category strip was
// retired in 1.4.2 — the mega menu (with top-level + sub-item counts) now
// covers category navigation. Operators who still want the strip on a
// specific page can drop the Megabar Elementor widget there.
?>

<?php endif; // !elementor header ?>
