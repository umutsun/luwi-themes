<?php
/**
 * LuwiPress Emerald — header.php
 *
 * Renders the document head, the optional topbar (ledger-style),
 * and the sticky shrinking site header (logo + primary nav + actions).
 *
 * Driven by the Acme/Emerald design partials — _topbar.html + _header.html.
 * Customizer panels in `inc/customizer/` flip the topbar copy + language
 * pill items + header actions on/off; defaults shown when no customizer
 * data is set.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lwp_emerald_topbar_on = (bool) get_theme_mod( 'luwipress_emerald_topbar_on', true );
$lwp_emerald_topbar_phone = (string) get_theme_mod( 'luwipress_emerald_topbar_phone', '' );
$lwp_emerald_topbar_email = (string) get_theme_mod( 'luwipress_emerald_topbar_email', '' );
$lwp_emerald_topbar_location = (string) get_theme_mod( 'luwipress_emerald_topbar_location', '' );
$lwp_emerald_topbar_secondary = (string) get_theme_mod( 'luwipress_emerald_topbar_secondary', '' );
$lwp_emerald_topbar_secondary_url = (string) get_theme_mod( 'luwipress_emerald_topbar_secondary_url', '#' );
$lwp_emerald_show_cart = function_exists( 'WC' );
$lwp_emerald_show_account = function_exists( 'WC' );
$lwp_emerald_header_cta_label = (string) get_theme_mod( 'luwipress_emerald_header_cta_label', __( 'Book a call', 'luwipress-emerald' ) );
$lwp_emerald_header_cta_url = (string) get_theme_mod( 'luwipress_emerald_header_cta_url', '' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'emerald-site' ); ?>>
<?php wp_body_open(); ?>

<a class="screen-reader-text" href="#emerald-main"><?php esc_html_e( 'Skip to content', 'luwipress-emerald' ); ?></a>

<?php if ( $lwp_emerald_topbar_on ) : ?>
<div class="emerald-topbar" data-screen-label="Topbar">
	<div class="emerald-topbar-inner">
		<div class="emerald-topbar-left">
			<?php if ( $lwp_emerald_topbar_location ) : ?>
				<span style="display:inline-flex;align-items:center;gap:6px;">
					<svg class="emerald-i emerald-i--sm" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-7.5-7-12a7 7 0 0 1 14 0c0 4.5-7 12-7 12Z"/><circle cx="12" cy="9" r="2.5"/></svg>
					<?php echo esc_html( $lwp_emerald_topbar_location ); ?>
				</span>
			<?php endif; ?>
			<?php if ( $lwp_emerald_topbar_phone ) : ?>
				<?php if ( $lwp_emerald_topbar_location ) : ?><span class="emerald-topbar-sep"></span><?php endif; ?>
				<a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $lwp_emerald_topbar_phone ) ); ?>">
					<svg class="emerald-i emerald-i--sm" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h3l2 5-2.5 1.5a11 11 0 0 0 6 6L15 14l5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/></svg>
					<?php echo esc_html( $lwp_emerald_topbar_phone ); ?>
				</a>
			<?php endif; ?>
			<?php if ( $lwp_emerald_topbar_email ) : ?>
				<span class="emerald-topbar-sep"></span>
				<a href="mailto:<?php echo esc_attr( $lwp_emerald_topbar_email ); ?>">
					<svg class="emerald-i emerald-i--sm" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
					<?php echo esc_html( $lwp_emerald_topbar_email ); ?>
				</a>
			<?php endif; ?>
		</div>
		<div class="emerald-topbar-right">
			<?php if ( $lwp_emerald_topbar_secondary ) : ?>
				<a href="<?php echo esc_url( $lwp_emerald_topbar_secondary_url ); ?>">
					<svg class="emerald-i emerald-i--sm" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
					<?php echo esc_html( $lwp_emerald_topbar_secondary ); ?>
				</a>
				<span class="emerald-topbar-sep"></span>
			<?php endif; ?>
			<?php
			/**
			 * WPML / Polylang language pill — emitted by either of the two
			 * plugins via filters. We render an empty wrapper for them to
			 * inject into; if neither is active and the wrapper stays
			 * empty, CSS hides it via :empty.
			 */
			$lwp_emerald_lang_html = apply_filters( 'luwipress_emerald_topbar_language_pill', '' );
			if ( $lwp_emerald_lang_html ) {
				echo '<div class="emerald-lang-pill" role="group" aria-label="' . esc_attr__( 'Language', 'luwipress-emerald' ) . '">' . $lwp_emerald_lang_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} elseif ( function_exists( 'icl_get_languages' ) ) {
				$languages = icl_get_languages( 'skip_missing=0&orderby=code' );
				if ( $languages && is_array( $languages ) ) {
					echo '<div class="emerald-lang-pill" role="group" aria-label="' . esc_attr__( 'Language', 'luwipress-emerald' ) . '">';
					foreach ( $languages as $lang ) {
						$is_current = ! empty( $lang['active'] ) ? 'true' : 'false';
						printf(
							'<a href="%s" aria-current="%s">%s</a>',
							esc_url( $lang['url'] ),
							esc_attr( $is_current ),
							esc_html( strtoupper( $lang['language_code'] ) )
						);
					}
					echo '</div>';
				}
			}
			?>
		</div>
	</div>
</div>
<?php endif; ?>

<header class="emerald-header" id="siteHeader" data-screen-label="Header">
	<div class="emerald-header-inner">
		<button class="emerald-hamburger" id="navTrigger" aria-label="<?php esc_attr_e( 'Open menu', 'luwipress-emerald' ); ?>" aria-controls="navDrawer" aria-expanded="false">
			<svg class="emerald-i" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
		</button>

		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="emerald-logo" rel="home">
			<?php
			$lwp_emerald_logo_id = get_theme_mod( 'custom_logo' );
			if ( $lwp_emerald_logo_id ) {
				echo wp_get_attachment_image( $lwp_emerald_logo_id, 'full', false, array( 'class' => 'emerald-logo-img', 'alt' => esc_attr( get_bloginfo( 'name' ) ) ) );
			} else {
				$site_name = get_bloginfo( 'name' );
				$mark      = $site_name ? mb_strtoupper( mb_substr( $site_name, 0, 1 ) ) : 'L';
				?>
				<span class="emerald-logo-mark"><?php echo esc_html( $mark ); ?></span>
				<span class="emerald-logo-word"><?php echo esc_html( $site_name ?: 'LuwiPress' ); ?></span>
				<?php
			}
			?>
		</a>

		<nav class="emerald-mainnav" aria-label="<?php esc_attr_e( 'Primary', 'luwipress-emerald' ); ?>">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'emerald-mainnav-list',
						'depth'          => 1,
						'fallback_cb'    => false,
					)
				);
			} else {
				echo '<ul class="emerald-mainnav-list">';
				echo '<li><a href="' . esc_url( home_url( '/' ) ) . '" aria-current="page">' . esc_html__( 'Home', 'luwipress-emerald' ) . '</a></li>';
				if ( function_exists( 'wc_get_page_id' ) ) {
					$shop_id = wc_get_page_id( 'shop' );
					if ( $shop_id > 0 ) {
						echo '<li><a href="' . esc_url( get_permalink( $shop_id ) ) . '">' . esc_html__( 'Solutions', 'luwipress-emerald' ) . '</a></li>';
					}
				}
				echo '<li><a href="' . esc_url( home_url( '/blog/' ) ) . '">' . esc_html__( 'Insights', 'luwipress-emerald' ) . '</a></li>';
				echo '</ul>';
			}
			?>
		</nav>

		<div class="emerald-header-actions">
			<button class="emerald-icon-btn" aria-label="<?php esc_attr_e( 'Search', 'luwipress-emerald' ); ?>" id="searchTrigger" data-emerald-search>
				<svg class="emerald-i" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
			</button>
			<?php if ( $lwp_emerald_show_account ) : ?>
				<a href="<?php echo esc_url( get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) ?: wp_login_url() ); ?>" class="emerald-icon-btn" aria-label="<?php esc_attr_e( 'Account', 'luwipress-emerald' ); ?>">
					<svg class="emerald-i" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
				</a>
			<?php endif; ?>
			<?php if ( $lwp_emerald_show_cart ) :
				$cart_count = WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0; ?>
				<button class="emerald-icon-btn" aria-label="<?php esc_attr_e( 'Open cart', 'luwipress-emerald' ); ?>" id="cartTrigger">
					<svg class="emerald-i" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 7h14l-1.5 11a2 2 0 0 1-2 1.7H8.5a2 2 0 0 1-2-1.7L5 7Z"/><path d="M9 7V5a3 3 0 0 1 6 0v2"/></svg>
					<?php if ( $cart_count > 0 ) : ?>
						<span class="emerald-cart-count" data-cart-count><?php echo esc_html( (string) $cart_count ); ?></span>
					<?php endif; ?>
				</button>
			<?php endif; ?>
			<?php if ( $lwp_emerald_header_cta_label && $lwp_emerald_header_cta_url ) : ?>
				<a href="<?php echo esc_url( $lwp_emerald_header_cta_url ); ?>" class="emerald-btn emerald-btn--primary emerald-header-cta" style="margin-left:var(--sp-2);"><?php echo esc_html( $lwp_emerald_header_cta_label ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</header>

<main class="emerald-main" id="emerald-main" role="main">
