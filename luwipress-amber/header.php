<?php
/**
 * LuwiPress Amber — theme header (Fly By Deniz "Amber" chrome).
 *
 * Two rendering paths:
 *   1. Elementor Pro / ElementsKit Header template active
 *      → yield to it via elementor_theme_do_location('header').
 *   2. No theme-builder header
 *      → render the full-fidelity Amber chrome inline: utility bar,
 *        sticky header (brand + mega nav + Book Now + theme toggle +
 *        burger), and the off-canvas mobile drawer. Styled by
 *        assets/css/amber.css, driven by assets/js/chrome.js.
 *
 * The mega nav is built from the registered "primary" menu (falls back
 * to the largest menu) so a fresh install surfaces real navigation; an
 * operator who builds an Elementor Mega Menu simply takes over path 1.
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
	 * Dark/light bootstrap — runs before stylesheet paint so light mode is
	 * restored without a flash. Persists to localStorage key "amber-theme"
	 * and re-derives --grad-hero per theme. Toggle handlers live in chrome.js
	 * (#headerTheme / #drawerTheme). Accents (amber/coral) are NOT touched,
	 * so the Customizer brand knob keeps working in both modes.
	 */
	?>
	<script>
	(function(){
		var DARK_BASE=['#1A1412','#211710','#241B16','#2A201A','#322519'];
		window.__amberApplyMode=function(mode){
			var el=document.documentElement,r=el.style,
				bv=['--bg-0','--bg-1','--bg-2','--surface','--surface-2','--grad-hero'];
			if(mode==='light'){
				el.setAttribute('data-theme','light');
				bv.forEach(function(p){ r.removeProperty(p); });
			}else{
				el.setAttribute('data-theme','dark');
				r.setProperty('--bg-0',DARK_BASE[0]);r.setProperty('--bg-1',DARK_BASE[1]);
				r.setProperty('--bg-2',DARK_BASE[2]);r.setProperty('--surface',DARK_BASE[3]);
				r.setProperty('--surface-2',DARK_BASE[4]);
				var R=parseInt(DARK_BASE[1].slice(1,3),16),G=parseInt(DARK_BASE[1].slice(3,5),16),B=parseInt(DARK_BASE[1].slice(5,7),16);
				r.setProperty('--grad-hero','linear-gradient(180deg, rgba('+R+','+G+','+B+',0) 0%, rgba('+R+','+G+','+B+',.4) 52%, rgba('+R+','+G+','+B+',.94) 100%)');
			}
			try{ localStorage.setItem('amber-theme',mode); }catch(e){}
		};
		try{ var m=localStorage.getItem('amber-theme'); if(m==='light') window.__amberApplyMode('light'); }catch(e){}
	})();
	</script>
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

	/**
	 * Build the navigation tree (top-level items + their children) from the
	 * primary menu — or the largest menu as a failsafe.
	 */
	$amber_menu_id = (int) get_theme_mod( 'luwipress_amber_mega_menu_id', 0 );
	if ( ! $amber_menu_id ) {
		$locations = get_nav_menu_locations();
		if ( ! empty( $locations['primary'] ) ) {
			$amber_menu_id = (int) $locations['primary'];
		} else {
			$best = null;
			foreach ( wp_get_nav_menus() as $m ) {
				$items = wp_get_nav_menu_items( $m->term_id );
				$count = is_array( $items ) ? count( $items ) : 0;
				if ( ! $best || $count > $best['count'] ) {
					$best = [ 'id' => $m->term_id, 'count' => $count ];
				}
			}
			$amber_menu_id = $best ? (int) $best['id'] : 0;
		}
	}

	$amber_tree = [];
	if ( $amber_menu_id ) {
		$items = wp_get_nav_menu_items( $amber_menu_id );
		if ( is_array( $items ) ) {
			foreach ( $items as $it ) {
				if ( (int) $it->menu_item_parent === 0 ) {
					$amber_tree[ $it->ID ] = [ 'item' => $it, 'children' => [] ];
				}
			}
			foreach ( $items as $it ) {
				$pid = (int) $it->menu_item_parent;
				if ( $pid && isset( $amber_tree[ $pid ] ) ) {
					$amber_tree[ $pid ]['children'][] = $it;
				}
			}
		}
	}

	$amber_book_label = get_theme_mod( 'luwipress_amber_header_cta_label', '' );
	$amber_book_label = $amber_book_label !== '' ? $amber_book_label : __( 'Book Now', 'luwipress-amber' );
	$amber_book_url   = get_theme_mod( 'luwipress_amber_header_cta_url', '' );
	$amber_book_url   = $amber_book_url !== '' ? $amber_book_url : home_url( '/contact/' );

	$amber_phone    = trim( (string) get_theme_mod( 'luwipress_amber_topbar_phone', '' ) );
	$amber_email    = trim( (string) get_theme_mod( 'luwipress_amber_topbar_email', get_option( 'admin_email' ) ) );
	$amber_location = trim( (string) get_theme_mod( 'luwipress_amber_topbar_location', '' ) );

	// Arrow + chevron SVGs reused across the chrome.
	$amber_arrow = '<svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
	$amber_chev  = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>';

	// Brand mark (custom logo) + wordmark (site name, last word bolded).
	$amber_site_name = get_bloginfo( 'name' );
	$amber_words     = preg_split( '/\s+/', trim( $amber_site_name ) );
	if ( count( $amber_words ) > 1 ) {
		$amber_last  = array_pop( $amber_words );
		$amber_word  = esc_html( implode( ' ', $amber_words ) ) . ' <b>' . esc_html( $amber_last ) . '</b>';
	} else {
		$amber_word  = '<b>' . esc_html( $amber_site_name ) . '</b>';
	}
	$amber_logo_id  = (int) get_theme_mod( 'custom_logo' );
	$amber_logo_src = $amber_logo_id ? wp_get_attachment_image_url( $amber_logo_id, 'medium' ) : '';
?>

<!-- ============ UTILITY BAR ============ -->
<?php if ( get_theme_mod( 'luwipress_amber_topbar_show', 1 ) ) : ?>
<div class="utility">
	<div class="wrap">
		<div class="u-left">
			<?php if ( $amber_location !== '' ) : ?>
				<span class="u-item">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-5.6-7-11a7 7 0 0 1 14 0c0 5.4-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
					<?php echo esc_html( $amber_location ); ?>
				</span>
			<?php endif; ?>
			<?php if ( $amber_location !== '' && $amber_phone !== '' ) : ?><span class="divider"></span><?php endif; ?>
			<?php if ( $amber_phone !== '' ) : ?>
				<a class="u-item" href="<?php echo esc_url( 'tel:' . preg_replace( '/\s+/', '', $amber_phone ) ); ?>">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3.1-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8.1 9.8a16 16 0 0 0 6 6l1.3-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2Z"/></svg>
					<?php echo esc_html( $amber_phone ); ?>
				</a>
			<?php endif; ?>
		</div>
		<div class="u-right">
			<?php
			// Language strip — WPML / Polylang aware.
			$amber_langs = [];
			if ( has_filter( 'wpml_active_languages' ) ) {
				$wpml = apply_filters( 'wpml_active_languages', null, 'orderby=code' );
				if ( is_array( $wpml ) ) {
					foreach ( $wpml as $code => $l ) {
						$amber_langs[] = [ 'code' => strtoupper( $l['language_code'] ?? $code ), 'url' => $l['url'] ?? '#', 'active' => ! empty( $l['active'] ) ];
					}
				}
			} elseif ( function_exists( 'pll_the_languages' ) ) {
				$pll = pll_the_languages( [ 'raw' => 1, 'hide_if_empty' => 0 ] );
				if ( is_array( $pll ) ) {
					foreach ( $pll as $code => $l ) {
						$amber_langs[] = [ 'code' => strtoupper( $l['slug'] ?? $code ), 'url' => $l['url'] ?? '#', 'active' => ! empty( $l['current_lang'] ) ];
					}
				}
			}
			if ( count( $amber_langs ) > 1 ) :
				echo '<span class="lang" role="navigation" aria-label="' . esc_attr__( 'Languages', 'luwipress-amber' ) . '">';
				$amber_codes = [];
				foreach ( $amber_langs as $l ) {
					$amber_codes[] = sprintf(
						'<a href="%s" hreflang="%s"%s>%s</a>',
						esc_url( $l['url'] ),
						esc_attr( strtolower( $l['code'] ) ),
						$l['active'] ? ' class="is-active"' : '',
						esc_html( $l['code'] )
					);
				}
				echo implode( ' · ', $amber_codes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '</span><span class="divider"></span>';
			endif;

			// Social rail — Customizer footer social URLs, reused here.
			$amber_socials = [
				'facebook'  => [ get_theme_mod( 'luwipress_amber_social_facebook', '' ),  '<svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M14 9h3V6h-3c-1.7 0-3 1.3-3 3v2H9v3h2v6h3v-6h2.5l.5-3H14V9.5c0-.3.2-.5.5-.5Z"/></svg>' ],
				'twitter'   => [ get_theme_mod( 'luwipress_amber_social_twitter', '' ),   '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17 3h3l-7 8 8 10h-6l-5-6-5 6H2l8-9L3 3h6l4 5 4-5Z"/></svg>' ],
				'instagram' => [ get_theme_mod( 'luwipress_amber_social_instagram', '' ), '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>' ],
				'tiktok'    => [ get_theme_mod( 'luwipress_amber_social_tiktok', '' ),    '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M16 3c.3 2.2 1.7 3.9 3.9 4.2v3.1c-1.4 0-2.7-.4-3.9-1.1v6.1A6.2 6.2 0 1 1 9.8 9v3.2a3 3 0 1 0 3.1 3V3H16Z"/></svg>' ],
				'youtube'   => [ get_theme_mod( 'luwipress_amber_social_youtube', '' ),   '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M22 8.2a3 3 0 0 0-2.1-2.1C18 5.5 12 5.5 12 5.5s-6 0-7.9.6A3 3 0 0 0 2 8.2 31 31 0 0 0 1.6 12 31 31 0 0 0 2 15.8a3 3 0 0 0 2.1 2.1c1.9.6 7.9.6 7.9.6s6 0 7.9-.6a3 3 0 0 0 2.1-2.1A31 31 0 0 0 22.4 12 31 31 0 0 0 22 8.2ZM10 15V9l5.2 3L10 15Z"/></svg>' ],
			];
			$amber_has_social = false;
			foreach ( $amber_socials as $u ) { if ( $u[0] !== '' ) { $amber_has_social = true; break; } }
			if ( $amber_has_social ) :
				echo '<span class="socials">';
				foreach ( $amber_socials as $platform => $cfg ) {
					if ( $cfg[0] === '' ) continue;
					printf(
						'<a href="%s" aria-label="%s" target="_blank" rel="noopener">%s</a>',
						esc_url( $cfg[0] ),
						esc_attr( ucfirst( $platform ) ),
						$cfg[1] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					);
				}
				echo '</span>';
			endif;
			?>
		</div>
	</div>
</div>
<?php endif; ?>

<!-- ============ HEADER ============ -->
<header class="header" id="header" role="banner">
	<div class="wrap">
		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<?php if ( $amber_logo_src ) : ?>
				<img class="mark" src="<?php echo esc_url( $amber_logo_src ); ?>" alt="<?php echo esc_attr( $amber_site_name ); ?>">
			<?php endif; ?>
			<span class="word"><?php echo $amber_word; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — built from esc_html'd parts ?></span>
		</a>

		<nav class="nav" aria-label="<?php esc_attr_e( 'Primary', 'luwipress-amber' ); ?>">
			<?php
			if ( ! empty( $amber_tree ) ) :
				foreach ( $amber_tree as $node ) :
					$top      = $node['item'];
					$children = $node['children'];
					$url      = esc_url( $top->url );
					$label    = esc_html( $top->title );
					if ( empty( $children ) ) :
						?><a href="<?php echo $url; ?>"><?php echo $label; ?></a><?php
					else :
						?>
						<div class="has-drop has-mega">
							<button><?php echo $label; ?> <?php echo $amber_chev; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
							<div class="mega">
								<div class="mega-inner wrap">
									<div class="mega-col">
										<span class="mega-h"><?php echo $label; ?></span>
										<?php foreach ( $children as $child ) :
											$desc = trim( (string) $child->description ); ?>
											<a href="<?php echo esc_url( $child->url ); ?>"><b><?php echo esc_html( $child->title ); ?></b><?php if ( $desc !== '' ) : ?><small><?php echo esc_html( $desc ); ?></small><?php endif; ?></a>
										<?php endforeach; ?>
										<a href="<?php echo $url; ?>" class="mega-all"><?php esc_html_e( 'View all', 'luwipress-amber' ); ?> <?php echo $amber_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
									</div>
								</div>
							</div>
						</div>
						<?php
					endif;
				endforeach;
			else :
				wp_nav_menu( [
					'theme_location' => 'primary',
					'container'      => false,
					'items_wrap'     => '%3$s',
					'depth'          => 1,
					'fallback_cb'    => false,
					'walker'         => null,
				] );
			endif;
			?>
		</nav>

		<div class="header-actions">
			<a href="<?php echo esc_url( $amber_book_url ); ?>" class="btn btn--amber"><?php echo esc_html( $amber_book_label ); ?> <?php echo $amber_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			<button class="theme-toggle" id="headerTheme" aria-label="<?php esc_attr_e( 'Toggle theme', 'luwipress-amber' ); ?>">
				<svg class="icon-moon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
				<svg class="icon-sun" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4.5"/><path d="M12 2v2.5M12 19.5V22M2 12h2.5M19.5 12H22M4.9 4.9l1.8 1.8M17.3 17.3l1.8 1.8M19.1 4.9l-1.8 1.8M6.7 17.3l-1.8 1.8"/></svg>
			</button>
			<button class="burger" id="burger" aria-label="<?php esc_attr_e( 'Menu', 'luwipress-amber' ); ?>"><span></span></button>
		</div>
	</div>
</header>

<!-- ============ MOBILE DRAWER ============ -->
<div class="drawer-overlay" id="drawerOverlay"></div>
<aside class="drawer" id="drawer">
	<div class="d-head">
		<div class="d-top">
			<span class="brand">
				<?php if ( $amber_logo_src ) : ?><img class="mark" src="<?php echo esc_url( $amber_logo_src ); ?>" alt="<?php echo esc_attr( $amber_site_name ); ?>"><?php endif; ?>
				<span class="word"><?php echo $amber_word; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			</span>
			<div class="d-top-actions">
				<button class="d-theme" id="drawerTheme" aria-label="<?php esc_attr_e( 'Toggle theme', 'luwipress-amber' ); ?>">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4.5"/><path d="M12 2v2.5M12 19.5V22M2 12h2.5M19.5 12H22M4.9 4.9l1.8 1.8M17.3 17.3l1.8 1.8M19.1 4.9l-1.8 1.8M6.7 17.3l-1.8 1.8"/></svg>
				</button>
				<button class="d-close" id="drawerClose" aria-label="<?php esc_attr_e( 'Close', 'luwipress-amber' ); ?>">×</button>
			</div>
		</div>
		<p class="d-tagline"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
		<div class="d-quick">
			<?php if ( $amber_phone !== '' ) : ?>
				<a href="<?php echo esc_url( 'tel:' . preg_replace( '/\s+/', '', $amber_phone ) ); ?>"><span class="qi"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3.1-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8.1 9.8a16 16 0 0 0 6 6l1.3-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2Z"/></svg></span><?php esc_html_e( 'Call', 'luwipress-amber' ); ?></a>
			<?php endif; ?>
			<?php if ( $amber_email !== '' ) : ?>
				<a href="<?php echo esc_url( 'mailto:' . $amber_email ); ?>"><span class="qi"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></span><?php esc_html_e( 'Email', 'luwipress-amber' ); ?></a>
			<?php endif; ?>
			<a href="<?php echo esc_url( $amber_book_url ); ?>"><span class="qi"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-5.6-7-11a7 7 0 0 1 14 0c0 5.4-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg></span><?php esc_html_e( 'Visit', 'luwipress-amber' ); ?></a>
		</div>
	</div>

	<div class="d-scroll">
		<div class="d-nav">
			<?php
			if ( ! empty( $amber_tree ) ) :
				foreach ( $amber_tree as $node ) :
					$top      = $node['item'];
					$children = $node['children'];
					if ( empty( $children ) ) :
						?>
						<a class="d-link" href="<?php echo esc_url( $top->url ); ?>"><span class="li"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M9 12l2 2 4-4"/></svg></span><span class="lt"><?php echo esc_html( $top->title ); ?></span></a>
						<?php
					else :
						?>
						<div class="d-acc">
							<button class="d-link" data-acc><span class="li"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></span><span class="lt"><?php echo esc_html( $top->title ); ?></span><svg class="chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg></button>
							<div class="d-acc-body"><div class="d-acc-body-inner">
								<div class="d-sub">
									<?php foreach ( $children as $child ) :
										$desc = trim( (string) $child->description ); ?>
										<a href="<?php echo esc_url( $child->url ); ?>"><b><?php echo esc_html( $child->title ); ?></b><?php if ( $desc !== '' ) : ?><small><?php echo esc_html( $desc ); ?></small><?php endif; ?></a>
									<?php endforeach; ?>
								</div>
							</div></div>
						</div>
						<?php
					endif;
				endforeach;
			endif;
			?>
		</div>

		<?php if ( count( $amber_langs ) > 1 ) : ?>
			<div class="d-lang">
				<?php foreach ( $amber_langs as $l ) : ?>
					<a href="<?php echo esc_url( $l['url'] ); ?>"<?php echo $l['active'] ? ' class="active"' : ''; ?>><?php echo esc_html( $l['code'] ); ?></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="d-dock">
		<a href="<?php echo esc_url( $amber_book_url ); ?>" class="btn btn--amber"><?php echo esc_html( $amber_book_label ); ?> <?php echo $amber_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
	</div>
</aside>

<?php endif; // ! $elementor_header_active ?>
