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

	$onyx_phone   = trim( (string) get_theme_mod( 'luwipress_onyx_topbar_phone', '+971 56 776 1946' ) );
	$onyx_email   = trim( (string) get_theme_mod( 'luwipress_onyx_topbar_email', 'sales@arshahomes.com' ) );
	$onyx_address = trim( (string) get_theme_mod( 'luwipress_onyx_topbar_location', 'Blue Bay Tower, Office 608 — Business Bay, Dubai' ) );
	$onyx_logo    = onyx_logo_url();
	$onyx_name    = get_bloginfo( 'name' );

	// Mega lists + the simple nav items (resolved to real pages when present).
	$onyx_mega = onyx_mega_lists();
	$onyx_nav  = array(
		'About'   => onyx_page_url( 'about' ),
		'Journal' => onyx_page_url( 'journal' ),
		'Contact' => onyx_page_url( 'contact' ),
	);
	$onyx_listings = onyx_page_url( 'listings' );

	// Language strip — WPML / Polylang aware, else the static EN·AR·RU display.
	$onyx_langs = array();
	if ( has_filter( 'wpml_active_languages' ) ) {
		// skip_missing=0 → always return every configured language, even on CPT
		// archives / untranslated singles (otherwise WPML hides them and the
		// switcher falls back to the static EN·AR·RU strip on subpages).
		$wpml = apply_filters( 'wpml_active_languages', null, 'skip_missing=0&orderby=custom' );
		if ( is_array( $wpml ) ) {
			foreach ( $wpml as $code => $l ) {
				$onyx_langs[] = array( 'code' => strtoupper( explode( '-', (string) ( $l['language_code'] ?? $code ) )[0] ), 'url' => $l['url'] ?? '#', 'active' => ! empty( $l['active'] ) );
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

	// Fallback: on non-translatable contexts (the arsha_project CPT archive /
	// single project) WPML returns only the current language, so the switcher
	// collapses to the static strip. Those URLs DO resolve per language
	// (/ar/projects/ → 200), so synthesize the full switcher from the site's
	// active languages, converting the current request URL with wpml_permalink.
	if ( count( $onyx_langs ) < 2 && isset( $GLOBALS['sitepress'] ) && is_object( $GLOBALS['sitepress'] )
		&& method_exists( $GLOBALS['sitepress'], 'get_active_languages' ) ) {
		global $wp;
		$onyx_cur_path = ( isset( $wp->request ) && '' !== $wp->request ) ? '/' . trim( $wp->request, '/' ) . '/' : '/';
		$onyx_cur_url  = home_url( $onyx_cur_path );
		$onyx_cur_lang = apply_filters( 'wpml_current_language', null );
		$onyx_active   = $GLOBALS['sitepress']->get_active_languages();
		if ( is_array( $onyx_active ) && count( $onyx_active ) > 1 ) {
			$onyx_langs = array();
			foreach ( $onyx_active as $onyx_lc => $onyx_li ) {
				$onyx_lurl    = apply_filters( 'wpml_permalink', $onyx_cur_url, $onyx_lc );
				$onyx_langs[] = array(
					'code'   => strtoupper( explode( '-', (string) $onyx_lc )[0] ),
					'url'    => $onyx_lurl ? $onyx_lurl : $onyx_cur_url,
					'active' => ( $onyx_lc === $onyx_cur_lang ),
				);
			}
		}
	}

	// Current language code for the dropdown trigger label.
	$onyx_cur_code = '';
	foreach ( $onyx_langs as $l ) { if ( ! empty( $l['active'] ) ) { $onyx_cur_code = $l['code']; break; } }
	if ( '' === $onyx_cur_code && ! empty( $onyx_langs ) ) { $onyx_cur_code = $onyx_langs[0]['code']; }

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
				<button class="theme-toggle theme-toggle--ubar" type="button" aria-label="<?php esc_attr_e( 'Toggle light / dark', 'luwipress-onyx' ); ?>">
					<span class="tt-track">
						<span class="tt-ic tt-sun"><?php echo onyx_icon( 'sun', 15 ); // phpcs:ignore ?></span>
						<span class="tt-ic tt-moon"><?php echo onyx_icon( 'moon', 14 ); // phpcs:ignore ?></span>
						<span class="tt-knob"></span>
					</span>
				</button>
				<span class="ubar-div"></span>
				<div class="lang lang--dd">
					<?php if ( count( $onyx_langs ) > 1 ) : ?>
						<span class="lang-trigger" tabindex="0" role="button" aria-haspopup="true" aria-label="<?php esc_attr_e( 'Language', 'luwipress-onyx' ); ?>">
							<span class="lang-ic"><?php echo onyx_icon( 'globe', 13 ); // phpcs:ignore ?></span>
							<span class="lang-cur"><?php echo esc_html( $onyx_cur_code ); ?></span>
							<span class="lang-caret" aria-hidden="true"></span>
						</span>
						<div class="lang-menu" role="menu">
							<?php foreach ( $onyx_langs as $l ) : ?>
								<a class="lang-b<?php echo $l['active'] ? ' on' : ''; ?>" href="<?php echo esc_url( $l['url'] ); ?>" hreflang="<?php echo esc_attr( strtolower( $l['code'] ) ); ?>" role="menuitem"><?php echo esc_html( $l['code'] ); ?></a>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<span class="lang-ic"><?php echo onyx_icon( 'globe', 13 ); // phpcs:ignore ?></span>
						<?php $static = array( 'EN', 'AR', 'RU' );
						foreach ( $static as $i => $code ) :
							if ( $i > 0 ) { echo '<span class="lang-sep">/</span>'; } ?>
							<button class="lang-b<?php echo 0 === $i ? ' on' : ''; ?>" type="button"><?php echo esc_html( $code ); ?></button>
						<?php endforeach; ?>
					<?php endif; ?>
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
					<a class="nav-trigger" href="<?php echo esc_url( $onyx_listings ); ?>"><?php esc_html_e( 'Projects', 'luwipress-onyx' ); ?><span class="chev"></span></a>
					<!-- mega panel -->
					<div class="mega" role="menu">
						<div class="wrap mega-in">
							<div class="mega-col">
								<h5><?php esc_html_e( 'By Developer', 'luwipress-onyx' ); ?></h5>
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
								<h5><?php esc_html_e( 'Featured Projects', 'luwipress-onyx' ); ?></h5>
								<div class="mega-feat">
									<?php
									// Featured Projects — operator-picked arsha_project posts via the featured
									// registry; falls back to two showcase cards before any project is featured.
									$onyx_feat = array();
									if ( function_exists( 'lwp_onyx_get_featured_products' ) ) {
										foreach ( lwp_onyx_get_featured_products( array( 'post_type' => 'arsha_project', 'number' => 2 ) ) as $fp ) {
											$f_dev   = (string) get_post_meta( $fp->ID, '_arsha_developer', true );
											$f_terms = get_the_terms( $fp->ID, 'arsha_area' );
											$f_area  = ( is_array( $f_terms ) && $f_terms && ! is_wp_error( $f_terms ) ) ? $f_terms[0]->name : '';
											$f_price = (int) get_post_meta( $fp->ID, '_arsha_price', true );
											$f_glyph = (string) get_post_meta( $fp->ID, '_arsha_glyph', true );
											if ( ! in_array( $f_glyph, array( 'tower', 'interior', 'exterior' ), true ) ) { $f_glyph = 'tower'; }
											$f_from = __( 'From', 'luwipress-onyx' );
											if ( $f_price >= 1000000 ) {
												$f_plabel = $f_from . ' AED ' . rtrim( rtrim( number_format( $f_price / 1000000, 1 ), '0' ), '.' ) . 'M';
											} elseif ( $f_price > 0 ) {
												$f_plabel = $f_from . ' AED ' . number_format( $f_price );
											} else {
												$f_plabel = '';
											}
											$onyx_feat[] = array(
												trim( $f_dev . ( $f_area !== '' ? ' · ' . $f_area : '' ), ' ·' ),
												get_the_title( $fp->ID ),
												$f_plabel,
												$f_glyph,
												get_permalink( $fp->ID ),
											);
										}
									}
									if ( empty( $onyx_feat ) ) {
										$onyx_feat = array(
											array( 'Penthouse · Downtown', 'The Onyx Penthouse', 'From AED 14.8M', 'interior', $onyx_listings ),
											array( 'Villa · Palm Jumeirah', 'Palm Shore Villa', 'From AED 28M', 'exterior', $onyx_listings ),
										);
									}
									foreach ( $onyx_feat as $f ) : ?>
										<a class="mega-card" href="<?php echo esc_url( ! empty( $f[4] ) ? $f[4] : $onyx_listings ); ?>">
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
								</div>
							</div>
						</div>
					</div>
				</span>
				<?php foreach ( $onyx_nav as $label => $url ) : ?>
					<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( __( $label, 'luwipress-onyx' ) ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText ?></a>
				<?php endforeach; ?>
			</nav>

			<div class="hdr-right">
				<a class="hdr-search" href="<?php echo esc_url( onyx_page_url( 'search' ) ); ?>" aria-label="<?php esc_attr_e( 'Search', 'luwipress-onyx' ); ?>"><?php echo onyx_icon( 'search', 18 ); // phpcs:ignore ?></a>
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
			<div class="drawer-top-actions">
				<button class="theme-toggle" type="button" aria-label="<?php esc_attr_e( 'Toggle light / dark', 'luwipress-onyx' ); ?>">
					<span class="tt-track">
						<span class="tt-ic tt-sun"><?php echo onyx_icon( 'sun', 15 ); // phpcs:ignore ?></span>
						<span class="tt-ic tt-moon"><?php echo onyx_icon( 'moon', 14 ); // phpcs:ignore ?></span>
						<span class="tt-knob"></span>
					</span>
				</button>
				<button class="drawer-close" type="button" aria-label="<?php esc_attr_e( 'Close', 'luwipress-onyx' ); ?>"><?php echo onyx_icon( 'close', 20 ); // phpcs:ignore ?></button>
			</div>
		</div>
		<div class="drawer-scroll">
			<nav class="drawer-nav">
				<?php
				$onyx_drawer_items = array_merge(
					array( 'Projects' => $onyx_listings ),
					$onyx_nav,
					array( 'Search' => onyx_page_url( 'search' ) )
				);
				$di = 0;
				foreach ( $onyx_drawer_items as $label => $url ) :
					$ic = ( 'Search' === $label ) ? 'search' : 'arrowUR';
					?>
					<a href="<?php echo esc_url( $url ); ?>" style="--di:<?php echo (int) $di; ?>">
						<span class="dn-i"><?php echo esc_html( str_pad( (string) ( $di + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
						<span class="dn-l"><?php echo esc_html( __( $label, 'luwipress-onyx' ) ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText ?></span>
						<span class="ic"><?php echo onyx_icon( $ic, 17 ); // phpcs:ignore ?></span>
					</a>
					<?php $di++;
				endforeach; ?>
			</nav>
			<div class="drawer-group">
				<span class="drawer-sub"><?php esc_html_e( 'By Developer', 'luwipress-onyx' ); ?></span>
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
