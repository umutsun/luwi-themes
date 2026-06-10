<?php
/**
 * LuwiPress Sapphire — theme header (Midnight Sapphire chrome).
 *
 * Two rendering paths:
 *   1. Elementor Pro / theme-builder Header template active
 *      → yield to it via elementor_theme_do_location('header').
 *   2. No theme-builder header
 *      → render the full-fidelity Sapphire chrome inline: utility bar,
 *        sticky header (logo + Product mega nav + search + theme
 *        toggle + burger), the Product mega panel, and the right-side
 *        mobile drawer. Styled by assets/css/sapphire-sections.css, driven by
 *        assets/js/sapphire.js.
 *
 * @package luwipress-sapphire
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
	 * localStorage key "sapphire_theme"; the toggle handlers live in sapphire.js.
	 */
	?>
	<script>
	(function(){
		try{
			var m = localStorage.getItem('sapphire_theme');
			document.documentElement.setAttribute('data-theme', m === 'light' ? 'light' : 'dark');
		}catch(e){ document.documentElement.setAttribute('data-theme','dark'); }
	})();
	</script>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php
$sapphire_header_active =
	did_action( 'elementor/loaded' ) &&
	function_exists( 'elementor_theme_do_location' ) &&
	elementor_theme_do_location( 'header' );

if ( ! $sapphire_header_active ) :

	$sapphire_phone   = trim( (string) get_theme_mod( 'luwipress_sapphire_topbar_phone', '' ) );
	$sapphire_email   = trim( (string) get_theme_mod( 'luwipress_sapphire_topbar_email', 'hello@sapphire.dev' ) );
	$sapphire_address = trim( (string) get_theme_mod( 'luwipress_sapphire_topbar_location', 'Free 14-day trial — no credit card required' ) );
	$sapphire_logo    = sapphire_logo_url();
	$sapphire_name    = get_bloginfo( 'name' );

	// Mega lists + the simple nav items (resolved to real pages when present).
	$sapphire_mega = sapphire_mega_lists();
	$sapphire_nav  = array(
		'Pricing' => sapphire_page_url( 'pricing' ),
		'Blog'    => sapphire_page_url( 'journal' ),
		'About'   => sapphire_page_url( 'about' ),
		'Contact' => sapphire_page_url( 'contact' ),
	);
	$sapphire_listings = home_url( '/#features' );

	// Language strip — WPML / Polylang aware, else the static EN·AR·RU display.
	$sapphire_langs = array();
	if ( has_filter( 'wpml_active_languages' ) ) {
		$wpml = apply_filters( 'wpml_active_languages', null, 'orderby=code' );
		if ( is_array( $wpml ) ) {
			foreach ( $wpml as $code => $l ) {
				$sapphire_langs[] = array( 'code' => strtoupper( $l['language_code'] ?? $code ), 'url' => $l['url'] ?? '#', 'active' => ! empty( $l['active'] ) );
			}
		}
	} elseif ( function_exists( 'pll_the_languages' ) ) {
		$pll = pll_the_languages( array( 'raw' => 1, 'hide_if_empty' => 0 ) );
		if ( is_array( $pll ) ) {
			foreach ( $pll as $code => $l ) {
				$sapphire_langs[] = array( 'code' => strtoupper( $l['slug'] ?? $code ), 'url' => $l['url'] ?? '#', 'active' => ! empty( $l['current_lang'] ) );
			}
		}
	}

	$sapphire_socials = array(
		'Instagram' => array( get_theme_mod( 'luwipress_sapphire_social_instagram', '' ), 'IG' ),
		'LinkedIn'  => array( get_theme_mod( 'luwipress_sapphire_social_linkedin', '' ),  'LI' ),
		'Facebook'  => array( get_theme_mod( 'luwipress_sapphire_social_facebook', '' ),  'FB' ),
	);
	?>

	<!-- ============ UTILITY BAR ============ -->
	<?php if ( get_theme_mod( 'luwipress_sapphire_topbar_show', 1 ) ) : ?>
	<div class="ubar">
		<div class="wrap ubar-in">
			<div class="ubar-left">
				<?php if ( $sapphire_address !== '' ) : ?>
					<span class="ubar-item ubar-addr"><span class="ic"><?php echo sapphire_icon( 'sparkle', 14 ); // phpcs:ignore ?></span><?php echo esc_html( $sapphire_address ); ?></span>
				<?php endif; ?>
				<?php if ( $sapphire_email !== '' ) : ?>
					<a class="ubar-item" href="<?php echo esc_url( 'mailto:' . $sapphire_email ); ?>"><span class="ic"><?php echo sapphire_icon( 'mail', 14 ); // phpcs:ignore ?></span><?php echo esc_html( $sapphire_email ); ?></a>
				<?php endif; ?>
			</div>
			<div class="ubar-right">
				<?php if ( $sapphire_phone !== '' ) : ?>
					<a class="ubar-call" href="<?php echo esc_url( 'tel:' . preg_replace( '/\s+/', '', $sapphire_phone ) ); ?>"><span class="ic"><?php echo sapphire_icon( 'phone', 13 ); // phpcs:ignore ?></span><?php esc_html_e( 'Talk to sales', 'luwipress-sapphire' ); ?> <b><?php echo esc_html( $sapphire_phone ); ?></b></a>
				<?php endif; ?>
				<span class="ubar-div"></span>
				<div class="lang">
					<span class="lang-ic"><?php echo sapphire_icon( 'globe', 13 ); // phpcs:ignore ?></span>
					<?php if ( count( $sapphire_langs ) > 1 ) :
						$first = true;
						foreach ( $sapphire_langs as $l ) :
							if ( ! $first ) { echo '<span class="lang-sep">/</span>'; }
							$first = false; ?>
							<a class="lang-b<?php echo $l['active'] ? ' on' : ''; ?>" href="<?php echo esc_url( $l['url'] ); ?>" hreflang="<?php echo esc_attr( strtolower( $l['code'] ) ); ?>"><?php echo esc_html( $l['code'] ); ?></a>
						<?php endforeach; ?>
					<?php else :
						$static = array( 'EN', 'DE', 'FR' );
						foreach ( $static as $i => $code ) :
							if ( $i > 0 ) { echo '<span class="lang-sep">/</span>'; } ?>
							<button class="lang-b<?php echo 0 === $i ? ' on' : ''; ?>" type="button"><?php echo esc_html( $code ); ?></button>
						<?php endforeach;
					endif; ?>
				</div>
				<?php
				$sapphire_has_social = false;
				foreach ( $sapphire_socials as $cfg ) { if ( $cfg[0] !== '' ) { $sapphire_has_social = true; break; } }
				if ( $sapphire_has_social ) : ?>
					<span class="ubar-social">
						<?php foreach ( $sapphire_socials as $label => $cfg ) :
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
			<a class="logo<?php echo $sapphire_logo ? '' : ' logo--text'; ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
				<?php if ( $sapphire_logo ) : ?>
					<img src="<?php echo esc_url( $sapphire_logo ); ?>" alt="<?php echo esc_attr( $sapphire_name ); ?>">
				<?php else : ?>
					<span class="logo-text"><?php echo esc_html( $sapphire_name ? $sapphire_name : 'Sapphire' ); ?></span>
				<?php endif; ?>
			</a>

			<nav class="nav" aria-label="<?php esc_attr_e( 'Primary', 'luwipress-sapphire' ); ?>">
				<span class="nav-item">
					<a class="nav-trigger" href="<?php echo esc_url( $sapphire_listings ); ?>"><?php esc_html_e( 'Product', 'luwipress-sapphire' ); ?><span class="chev"></span></a>
					<!-- mega panel -->
					<div class="mega" role="menu">
						<div class="wrap mega-in">
							<div class="mega-col">
								<h5><?php esc_html_e( 'Features', 'luwipress-sapphire' ); ?></h5>
								<div class="mega-links">
									<?php foreach ( $sapphire_mega['types'] as $t ) : ?>
										<a class="mega-link" href="<?php echo esc_url( $t[1] ); ?>"><?php echo esc_html( $t[0] ); ?><span class="mc"><?php echo esc_html( $t[2] ); ?></span></a>
									<?php endforeach; ?>
								</div>
							</div>
							<div class="mega-col">
								<h5><?php esc_html_e( 'Use cases', 'luwipress-sapphire' ); ?></h5>
								<div class="mega-links">
									<?php foreach ( $sapphire_mega['areas'] as $a ) : ?>
										<a class="mega-link" href="<?php echo esc_url( $a[1] ); ?>"><?php echo esc_html( $a[0] ); ?><span class="mc"><?php echo sapphire_icon( 'arrowUR', 13 ); // phpcs:ignore ?></span></a>
									<?php endforeach; ?>
								</div>
							</div>
							<div class="mega-col">
								<h5><?php esc_html_e( 'Highlights', 'luwipress-sapphire' ); ?></h5>
								<div class="mega-feat">
									<?php
									$sapphire_feat = array(
										array( __( 'Just shipped', 'luwipress-sapphire' ), __( 'Realtime collaboration', 'luwipress-sapphire' ), 'v2.4', 'zap' ),
										array( __( 'Most loved', 'luwipress-sapphire' ), __( 'Analytics & export', 'luwipress-sapphire' ), 'Pro', 'chart' ),
									);
									foreach ( $sapphire_feat as $f ) : ?>
										<a class="mega-card" href="<?php echo esc_url( home_url( '/#features' ) ); ?>">
											<div class="mega-thumb" style="display:grid;place-items:center;color:var(--accent-soft)"><?php echo sapphire_icon( $f[3], 26 ); // phpcs:ignore ?></div>
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
								<span class="mf-note"><?php esc_html_e( 'From idea to production — Sapphire scales with your team, from a weekend project to thousands of seats.', 'luwipress-sapphire' ); ?></span>
								<div class="mf-links">
									<a class="btn btn-ghost" href="<?php echo esc_url( home_url( '/#features' ) ); ?>"><?php esc_html_e( 'See all features', 'luwipress-sapphire' ); ?> <span class="arr"><?php echo sapphire_icon( 'arrow', 15 ); // phpcs:ignore ?></span></a>
									<a class="btn btn-gold" href="<?php echo esc_url( sapphire_page_url( 'pricing' ) ); ?>"><?php esc_html_e( 'View pricing', 'luwipress-sapphire' ); ?></a>
								</div>
							</div>
						</div>
					</div>
				</span>
				<?php foreach ( $sapphire_nav as $label => $url ) : ?>
					<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>

			<div class="hdr-right">
				<a class="hdr-search" href="<?php echo esc_url( sapphire_page_url( 'search' ) ); ?>" aria-label="<?php esc_attr_e( 'Search', 'luwipress-sapphire' ); ?>"><?php echo sapphire_icon( 'search', 18 ); // phpcs:ignore ?></a>
				<button class="theme-toggle" type="button" aria-label="<?php esc_attr_e( 'Toggle light / dark', 'luwipress-sapphire' ); ?>">
					<span class="tt-track">
						<span class="tt-ic tt-sun"><?php echo sapphire_icon( 'sun', 15 ); // phpcs:ignore ?></span>
						<span class="tt-ic tt-moon"><?php echo sapphire_icon( 'moon', 14 ); // phpcs:ignore ?></span>
						<span class="tt-knob"></span>
					</span>
				</button>
				<a class="btn btn-gold hdr-cta" href="<?php echo esc_url( sapphire_page_url( 'pricing' ) ); ?>"><?php esc_html_e( 'Start free', 'luwipress-sapphire' ); ?></a>
				<button class="hdr-burger" type="button" aria-label="<?php esc_attr_e( 'Menu', 'luwipress-sapphire' ); ?>"><?php echo sapphire_icon( 'menu', 20 ); // phpcs:ignore ?></button>
			</div>
		</div>
	</header>
	<div class="mega-scrim"></div>

	<!-- ============ MOBILE DRAWER ============ -->
	<div class="drawer-scrim"></div>
	<aside class="drawer" aria-hidden="true">
		<div class="drawer-top">
			<a class="logo<?php echo $sapphire_logo ? '' : ' logo--text'; ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>" data-small="true"><?php if ( $sapphire_logo ) : ?><img src="<?php echo esc_url( $sapphire_logo ); ?>" alt="<?php echo esc_attr( $sapphire_name ); ?>"><?php else : ?><span class="logo-text"><?php echo esc_html( $sapphire_name ? $sapphire_name : 'Sapphire' ); ?></span><?php endif; ?></a>
			<button class="drawer-close" type="button" aria-label="<?php esc_attr_e( 'Close', 'luwipress-sapphire' ); ?>"><?php echo sapphire_icon( 'close', 20 ); // phpcs:ignore ?></button>
		</div>
		<div class="drawer-scroll">
			<nav class="drawer-nav">
				<?php
				$sapphire_drawer_items = array_merge(
					array( 'Product' => $sapphire_listings ),
					$sapphire_nav,
					array( 'Search' => sapphire_page_url( 'search' ) )
				);
				$di = 0;
				foreach ( $sapphire_drawer_items as $label => $url ) :
					$ic = ( 'Search' === $label ) ? 'search' : 'arrowUR';
					?>
					<a href="<?php echo esc_url( $url ); ?>" style="--di:<?php echo (int) $di; ?>">
						<span class="dn-i"><?php echo esc_html( str_pad( (string) ( $di + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
						<span class="dn-l"><?php echo esc_html( $label ); ?></span>
						<span class="ic"><?php echo sapphire_icon( $ic, 17 ); // phpcs:ignore ?></span>
					</a>
					<?php $di++;
				endforeach; ?>
			</nav>
			<div class="drawer-group">
				<span class="drawer-sub"><?php esc_html_e( 'Features', 'luwipress-sapphire' ); ?></span>
				<div class="drawer-chips">
					<?php foreach ( $sapphire_mega['types'] as $t ) : ?>
						<a href="<?php echo esc_url( $t[1] ); ?>"><?php echo esc_html( $t[0] ); ?></a>
					<?php endforeach; ?>
				</div>
				<span class="drawer-sub"><?php esc_html_e( 'Use cases', 'luwipress-sapphire' ); ?></span>
				<div class="drawer-chips">
					<?php foreach ( $sapphire_mega['areas'] as $a ) : ?>
						<a href="<?php echo esc_url( $a[1] ); ?>"><?php echo esc_html( $a[0] ); ?></a>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<div class="drawer-foot">
			<div class="drawer-foot-row">
				<div class="lang lang--block">
					<span class="lang-ic"><?php echo sapphire_icon( 'globe', 13 ); // phpcs:ignore ?></span>
					<?php if ( count( $sapphire_langs ) > 1 ) :
						$first = true;
						foreach ( $sapphire_langs as $l ) :
							if ( ! $first ) { echo '<span class="lang-sep">/</span>'; }
							$first = false; ?>
							<a class="lang-b<?php echo $l['active'] ? ' on' : ''; ?>" href="<?php echo esc_url( $l['url'] ); ?>"><?php echo esc_html( $l['code'] ); ?></a>
						<?php endforeach;
					else :
						foreach ( array( 'EN', 'DE', 'FR' ) as $i => $code ) :
							if ( $i > 0 ) { echo '<span class="lang-sep">/</span>'; } ?>
							<button class="lang-b<?php echo 0 === $i ? ' on' : ''; ?>" type="button"><?php echo esc_html( $code ); ?></button>
						<?php endforeach;
					endif; ?>
				</div>
				<button class="theme-toggle" type="button" aria-label="<?php esc_attr_e( 'Toggle light / dark', 'luwipress-sapphire' ); ?>">
					<span class="tt-track">
						<span class="tt-ic tt-sun"><?php echo sapphire_icon( 'sun', 15 ); // phpcs:ignore ?></span>
						<span class="tt-ic tt-moon"><?php echo sapphire_icon( 'moon', 14 ); // phpcs:ignore ?></span>
						<span class="tt-knob"></span>
					</span>
				</button>
			</div>
			<?php if ( $sapphire_phone !== '' ) : ?>
				<a class="dcall" href="<?php echo esc_url( 'tel:' . preg_replace( '/\s+/', '', $sapphire_phone ) ); ?>"><span class="ic"><?php echo sapphire_icon( 'phone', 16 ); // phpcs:ignore ?></span><?php echo esc_html( $sapphire_phone ); ?></a>
			<?php endif; ?>
			<a class="btn btn-gold" href="<?php echo esc_url( sapphire_page_url( 'pricing' ) ); ?>" style="justify-content:center"><?php esc_html_e( 'Start free', 'luwipress-sapphire' ); ?> <span class="arr"><?php echo sapphire_icon( 'arrow', 16 ); // phpcs:ignore ?></span></a>
			<?php if ( $sapphire_address !== '' ) : ?>
				<span class="drawer-addr"><?php echo esc_html( $sapphire_address ); ?></span>
			<?php endif; ?>
		</div>
	</aside>

<?php endif; // ! $sapphire_header_active ?>
