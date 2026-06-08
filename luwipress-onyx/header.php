<?php
/**
 * LuwiPress Onyx — theme header ("quiet luxury, after dark" chrome).
 *
 * Two rendering paths:
 *   1. Elementor Pro / theme-builder Header template active
 *      → yield to it via elementor_theme_do_location('header').
 *   2. No theme-builder header
 *      → render the full-fidelity Onyx chrome inline: utility bar,
 *        sticky header (logo + Residences mega nav + search + theme
 *        toggle + burger), the Residences mega panel, and the right-side
 *        mobile drawer. Styled by assets/css/onyx-sections.css, driven by
 *        assets/js/onyx.js.
 *
 * @package luwipress-onyx
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="profile" href="https://gmpg.org/xfn/11" />
	<?php
	/**
	 * Dark/light bootstrap — runs before stylesheet paint so a stored light
	 * preference is restored without a flash. Default is dark. Persists to
	 * localStorage key "onyx_theme"; the toggle handlers live in onyx.js.
	 */
	?>
	<script>
	(function(){
		try{
			var m = localStorage.getItem('onyx_theme');
			document.documentElement.setAttribute('data-theme', m === 'light' ? 'light' : 'dark');
		}catch(e){ document.documentElement.setAttribute('data-theme','dark'); }
	})();
	</script>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php
$onyx_header_active =
	did_action( 'elementor/loaded' ) &&
	function_exists( 'elementor_theme_do_location' ) &&
	elementor_theme_do_location( 'header' );

if ( ! $onyx_header_active ) :

	$onyx_phone   = trim( (string) get_theme_mod( 'luwipress_onyx_topbar_phone', '056 776 1946' ) );
	$onyx_email   = trim( (string) get_theme_mod( 'luwipress_onyx_topbar_email', 'sales@arshahomes.com' ) );
	$onyx_address = trim( (string) get_theme_mod( 'luwipress_onyx_topbar_location', 'Blue Bay Tower, Office 608 — Business Bay, Dubai' ) );
	$onyx_logo    = onyx_logo_url();
	$onyx_name    = get_bloginfo( 'name' );

	// Mega lists + the simple nav items (resolved to real pages when present).
	$onyx_mega = onyx_mega_lists();
	$onyx_nav  = array(
		'Gallery' => onyx_page_url( 'gallery' ),
		'About'   => onyx_page_url( 'about' ),
		'Journal' => onyx_page_url( 'journal' ),
		'Contact' => onyx_page_url( 'contact' ),
	);
	$onyx_listings = onyx_page_url( 'listings' );

	// Language strip — WPML / Polylang aware, else the static EN·AR·RU display.
	$onyx_langs = array();
	if ( has_filter( 'wpml_active_languages' ) ) {
		$wpml = apply_filters( 'wpml_active_languages', null, 'orderby=code' );
		if ( is_array( $wpml ) ) {
			foreach ( $wpml as $code => $l ) {
				$onyx_langs[] = array( 'code' => strtoupper( $l['language_code'] ?? $code ), 'url' => $l['url'] ?? '#', 'active' => ! empty( $l['active'] ) );
			}
		}
	} elseif ( function_exists( 'pll_the_languages' ) ) {
		$pll = pll_the_languages( array( 'raw' => 1, 'hide_if_empty' => 0 ) );
		if ( is_array( $pll ) ) {
			foreach ( $pll as $code => $l ) {
				$onyx_langs[] = array( 'code' => strtoupper( $l['slug'] ?? $code ), 'url' => $l['url'] ?? '#', 'active' => ! empty( $l['current_lang'] ) );
			}
		}
	}

	$onyx_socials = array(
		'Instagram' => array( get_theme_mod( 'luwipress_onyx_social_instagram', '' ), 'IG' ),
		'LinkedIn'  => array( get_theme_mod( 'luwipress_onyx_social_linkedin', '' ),  'LI' ),
		'Facebook'  => array( get_theme_mod( 'luwipress_onyx_social_facebook', '' ),  'FB' ),
	);
	?>

	<!-- ============ UTILITY BAR ============ -->
	<?php if ( get_theme_mod( 'luwipress_onyx_topbar_show', 1 ) ) : ?>
	<div class="ubar">
		<div class="wrap ubar-in">
			<div class="ubar-left">
				<?php if ( $onyx_address !== '' ) : ?>
					<span class="ubar-item ubar-addr"><span class="ic"><?php echo onyx_icon( 'pin', 14 ); // phpcs:ignore ?></span><?php echo esc_html( $onyx_address ); ?></span>
				<?php endif; ?>
				<?php if ( $onyx_email !== '' ) : ?>
					<a class="ubar-item" href="<?php echo esc_url( 'mailto:' . $onyx_email ); ?>"><span class="ic"><?php echo onyx_icon( 'mail', 14 ); // phpcs:ignore ?></span><?php echo esc_html( $onyx_email ); ?></a>
				<?php endif; ?>
			</div>
			<div class="ubar-right">
				<?php if ( $onyx_phone !== '' ) : ?>
					<a class="ubar-call" href="<?php echo esc_url( 'tel:' . preg_replace( '/\s+/', '', $onyx_phone ) ); ?>"><span class="ic"><?php echo onyx_icon( 'phone', 13 ); // phpcs:ignore ?></span><?php esc_html_e( 'Call experts', 'luwipress-onyx' ); ?> <b><?php echo esc_html( $onyx_phone ); ?></b></a>
				<?php endif; ?>
				<span class="ubar-div"></span>
				<div class="lang">
					<span class="lang-ic"><?php echo onyx_icon( 'globe', 13 ); // phpcs:ignore ?></span>
					<?php if ( count( $onyx_langs ) > 1 ) :
						$first = true;
						foreach ( $onyx_langs as $l ) :
							if ( ! $first ) { echo '<span class="lang-sep">/</span>'; }
							$first = false; ?>
							<a class="lang-b<?php echo $l['active'] ? ' on' : ''; ?>" href="<?php echo esc_url( $l['url'] ); ?>" hreflang="<?php echo esc_attr( strtolower( $l['code'] ) ); ?>"><?php echo esc_html( $l['code'] ); ?></a>
						<?php endforeach; ?>
					<?php else :
						$static = array( 'EN', 'AR', 'RU' );
						foreach ( $static as $i => $code ) :
							if ( $i > 0 ) { echo '<span class="lang-sep">/</span>'; } ?>
							<button class="lang-b<?php echo 0 === $i ? ' on' : ''; ?>" type="button"><?php echo esc_html( $code ); ?></button>
						<?php endforeach;
					endif; ?>
				</div>
				<?php
				$onyx_has_social = false;
				foreach ( $onyx_socials as $cfg ) { if ( $cfg[0] !== '' ) { $onyx_has_social = true; break; } }
				if ( $onyx_has_social ) : ?>
					<span class="ubar-social">
						<?php foreach ( $onyx_socials as $label => $cfg ) :
							if ( $cfg[0] === '' ) { continue; } ?>
							<a href="<?php echo esc_url( $cfg[0] ); ?>" aria-label="<?php echo esc_attr( $label ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $cfg[1] ); ?></a>
						<?php endforeach; ?>
					</span>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<!-- ============ HEADER ============ -->
	<header class="hdr" id="header" role="banner">
		<div class="wrap hdr-in">
			<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
				<img src="<?php echo esc_url( $onyx_logo ); ?>" alt="<?php echo esc_attr( $onyx_name ); ?>">
			</a>

			<nav class="nav" aria-label="<?php esc_attr_e( 'Primary', 'luwipress-onyx' ); ?>">
				<span class="nav-item">
					<a class="nav-trigger" href="<?php echo esc_url( $onyx_listings ); ?>"><?php esc_html_e( 'Residences', 'luwipress-onyx' ); ?><span class="chev"></span></a>
					<!-- mega panel -->
					<div class="mega" role="menu">
						<div class="wrap mega-in">
							<div class="mega-col">
								<h5><?php esc_html_e( 'By Type', 'luwipress-onyx' ); ?></h5>
								<div class="mega-links">
									<?php foreach ( $onyx_mega['types'] as $t ) : ?>
										<a class="mega-link" href="<?php echo esc_url( $t[1] ); ?>"><?php echo esc_html( $t[0] ); ?><span class="mc"><?php echo esc_html( $t[2] ); ?></span></a>
									<?php endforeach; ?>
								</div>
							</div>
							<div class="mega-col">
								<h5><?php esc_html_e( 'By Neighborhood', 'luwipress-onyx' ); ?></h5>
								<div class="mega-links">
									<?php foreach ( $onyx_mega['areas'] as $a ) : ?>
										<a class="mega-link" href="<?php echo esc_url( $a[1] ); ?>"><?php echo esc_html( $a[0] ); ?><span class="mc"><?php echo onyx_icon( 'arrowUR', 13 ); // phpcs:ignore ?></span></a>
									<?php endforeach; ?>
								</div>
							</div>
							<div class="mega-col">
								<h5><?php esc_html_e( 'Featured Residences', 'luwipress-onyx' ); ?></h5>
								<div class="mega-feat">
									<?php
									$onyx_feat = array(
										array( 'Penthouse · Downtown', 'The Onyx Penthouse', 'From AED 14.8M', 'interior' ),
										array( 'Villa · Palm Jumeirah', 'Palm Shore Villa', 'From AED 28M', 'exterior' ),
									);
									foreach ( $onyx_feat as $f ) : ?>
										<a class="mega-card" href="<?php echo esc_url( $onyx_listings ); ?>">
											<div class="mega-thumb"><?php echo onyx_ph( array( 'glyph' => $f[3] ) ); // phpcs:ignore ?></div>
											<div>
												<div class="mt-cat"><?php echo esc_html( $f[0] ); ?></div>
												<h6><?php echo esc_html( $f[1] ); ?></h6>
												<div class="mt-price"><?php echo esc_html( $f[2] ); ?></div>
											</div>
										</a>
									<?php endforeach; ?>
								</div>
							</div>
							<div class="mega-foot">
								<span class="mf-note"><?php esc_html_e( 'Most of our finest residences are placed privately, before they are listed.', 'luwipress-onyx' ); ?></span>
								<div class="mf-links">
									<a class="btn btn-ghost" href="<?php echo esc_url( $onyx_listings ); ?>"><?php esc_html_e( 'Advanced search', 'luwipress-onyx' ); ?> <span class="arr"><?php echo onyx_icon( 'arrow', 15 ); // phpcs:ignore ?></span></a>
									<a class="btn btn-gold" href="<?php echo esc_url( onyx_page_url( 'gallery' ) ); ?>"><?php esc_html_e( 'The full gallery', 'luwipress-onyx' ); ?></a>
								</div>
							</div>
						</div>
					</div>
				</span>
				<?php foreach ( $onyx_nav as $label => $url ) : ?>
					<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>

			<div class="hdr-right">
				<a class="hdr-search" href="<?php echo esc_url( onyx_page_url( 'search' ) ); ?>" aria-label="<?php esc_attr_e( 'Search', 'luwipress-onyx' ); ?>"><?php echo onyx_icon( 'search', 18 ); // phpcs:ignore ?></a>
				<button class="theme-toggle" type="button" aria-label="<?php esc_attr_e( 'Toggle light / dark', 'luwipress-onyx' ); ?>">
					<span class="tt-track">
						<span class="tt-ic tt-sun"><?php echo onyx_icon( 'sun', 15 ); // phpcs:ignore ?></span>
						<span class="tt-ic tt-moon"><?php echo onyx_icon( 'moon', 14 ); // phpcs:ignore ?></span>
						<span class="tt-knob"></span>
					</span>
				</button>
				<button class="hdr-burger" type="button" aria-label="<?php esc_attr_e( 'Menu', 'luwipress-onyx' ); ?>"><?php echo onyx_icon( 'menu', 20 ); // phpcs:ignore ?></button>
			</div>
		</div>
	</header>
	<div class="mega-scrim"></div>

	<!-- ============ MOBILE DRAWER ============ -->
	<div class="drawer-scrim"></div>
	<aside class="drawer" aria-hidden="true">
		<div class="drawer-top">
			<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" data-small="true"><img src="<?php echo esc_url( $onyx_logo ); ?>" alt="<?php echo esc_attr( $onyx_name ); ?>"></a>
			<button class="drawer-close" type="button" aria-label="<?php esc_attr_e( 'Close', 'luwipress-onyx' ); ?>"><?php echo onyx_icon( 'close', 20 ); // phpcs:ignore ?></button>
		</div>
		<div class="drawer-scroll">
			<nav class="drawer-nav">
				<?php
				$onyx_drawer_items = array_merge(
					array( 'Residences' => $onyx_listings ),
					$onyx_nav,
					array( 'Search' => onyx_page_url( 'search' ) )
				);
				$di = 0;
				foreach ( $onyx_drawer_items as $label => $url ) :
					$ic = ( 'Search' === $label ) ? 'search' : 'arrowUR';
					?>
					<a href="<?php echo esc_url( $url ); ?>" style="--di:<?php echo (int) $di; ?>">
						<span class="dn-i"><?php echo esc_html( str_pad( (string) ( $di + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
						<span class="dn-l"><?php echo esc_html( $label ); ?></span>
						<span class="ic"><?php echo onyx_icon( $ic, 17 ); // phpcs:ignore ?></span>
					</a>
					<?php $di++;
				endforeach; ?>
			</nav>
			<div class="drawer-group">
				<span class="drawer-sub"><?php esc_html_e( 'By Type', 'luwipress-onyx' ); ?></span>
				<div class="drawer-chips">
					<?php foreach ( $onyx_mega['types'] as $t ) : ?>
						<a href="<?php echo esc_url( $t[1] ); ?>"><?php echo esc_html( $t[0] ); ?></a>
					<?php endforeach; ?>
				</div>
				<span class="drawer-sub"><?php esc_html_e( 'By Neighborhood', 'luwipress-onyx' ); ?></span>
				<div class="drawer-chips">
					<?php foreach ( $onyx_mega['areas'] as $a ) : ?>
						<a href="<?php echo esc_url( $a[1] ); ?>"><?php echo esc_html( $a[0] ); ?></a>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<div class="drawer-foot">
			<div class="drawer-foot-row">
				<div class="lang lang--block">
					<span class="lang-ic"><?php echo onyx_icon( 'globe', 13 ); // phpcs:ignore ?></span>
					<?php if ( count( $onyx_langs ) > 1 ) :
						$first = true;
						foreach ( $onyx_langs as $l ) :
							if ( ! $first ) { echo '<span class="lang-sep">/</span>'; }
							$first = false; ?>
							<a class="lang-b<?php echo $l['active'] ? ' on' : ''; ?>" href="<?php echo esc_url( $l['url'] ); ?>"><?php echo esc_html( $l['code'] ); ?></a>
						<?php endforeach;
					else :
						foreach ( array( 'EN', 'AR', 'RU' ) as $i => $code ) :
							if ( $i > 0 ) { echo '<span class="lang-sep">/</span>'; } ?>
							<button class="lang-b<?php echo 0 === $i ? ' on' : ''; ?>" type="button"><?php echo esc_html( $code ); ?></button>
						<?php endforeach;
					endif; ?>
				</div>
				<button class="theme-toggle" type="button" aria-label="<?php esc_attr_e( 'Toggle light / dark', 'luwipress-onyx' ); ?>">
					<span class="tt-track">
						<span class="tt-ic tt-sun"><?php echo onyx_icon( 'sun', 15 ); // phpcs:ignore ?></span>
						<span class="tt-ic tt-moon"><?php echo onyx_icon( 'moon', 14 ); // phpcs:ignore ?></span>
						<span class="tt-knob"></span>
					</span>
				</button>
			</div>
			<?php if ( $onyx_phone !== '' ) : ?>
				<a class="dcall" href="<?php echo esc_url( 'tel:' . preg_replace( '/\s+/', '', $onyx_phone ) ); ?>"><span class="ic"><?php echo onyx_icon( 'phone', 16 ); // phpcs:ignore ?></span><?php echo esc_html( $onyx_phone ); ?></a>
			<?php endif; ?>
			<a class="btn btn-gold" href="<?php echo esc_url( onyx_page_url( 'contact' ) ); ?>" style="justify-content:center"><?php esc_html_e( 'Book a viewing', 'luwipress-onyx' ); ?> <span class="arr"><?php echo onyx_icon( 'arrow', 16 ); // phpcs:ignore ?></span></a>
			<?php if ( $onyx_address !== '' ) : ?>
				<span class="drawer-addr"><?php echo esc_html( $onyx_address ); ?></span>
			<?php endif; ?>
		</div>
	</aside>

<?php endif; // ! $onyx_header_active ?>
