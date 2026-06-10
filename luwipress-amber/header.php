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
	$amber_book_label = $amber_book_label !== '' ? $amber_book_label : __( 'Browse Tours', 'luwipress-amber' );
	$amber_book_url   = get_theme_mod( 'luwipress_amber_header_cta_url', '' );
	$amber_book_url   = $amber_book_url !== '' ? $amber_book_url : ( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) );

	// Topbar contact details. Defaults are the Fly By Deniz concierge line; any
	// site overrides them via Customizer → LuwiPress Amber → Topbar. Filterable.
	$amber_phone    = trim( (string) get_theme_mod( 'luwipress_amber_topbar_phone', apply_filters( 'luwipress_amber_default_phone', '+971 56 776 1946' ) ) );
	$amber_email    = trim( (string) get_theme_mod( 'luwipress_amber_topbar_email', apply_filters( 'luwipress_amber_default_email', 'ayhan@flybydeniz.com' ) ) );
	$amber_location = trim( (string) get_theme_mod( 'luwipress_amber_topbar_location', apply_filters( 'luwipress_amber_default_location', 'Dubai, United Arab Emirates' ) ) );

	// Arrow + chevron SVGs reused across the chrome.
	$amber_arrow = '<svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
	$amber_chev  = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>';

	// Brand mark (custom logo) + wordmark. The wordmark is capped to the first
	// three words so a long legal site name ("… Tourism LLC") can't blow out the
	// header width and collide with the nav. It is shown ONLY when no logo image
	// is uploaded — an uploaded logo already carries the brand name, so printing
	// the wordmark next to it double-brands and overlaps (see Fly By Deniz).
	$amber_site_name = get_bloginfo( 'name' );
	$amber_words     = preg_split( '/\s+/', trim( $amber_site_name ) );
	$amber_words     = array_slice( $amber_words, 0, 3 ); // cap long legal names
	if ( count( $amber_words ) > 1 ) {
		$amber_last  = array_pop( $amber_words );
		$amber_word  = esc_html( implode( ' ', $amber_words ) ) . ' <b>' . esc_html( $amber_last ) . '</b>';
	} else {
		$amber_word  = '<b>' . esc_html( $amber_site_name ) . '</b>';
	}
	// Logo: uploaded custom logo wins; otherwise fall back to the shipped gold
	// Fly By Deniz emblem so the brand is on-art out of the box.
	$amber_logo_id   = (int) get_theme_mod( 'custom_logo' );
	$amber_logo_src  = $amber_logo_id
		? wp_get_attachment_image_url( $amber_logo_id, 'full' )
		: get_template_directory_uri() . '/logos/fbd-emblem.png';
	// Show the wordmark next to the (emblem) logo, per the design — emblem +
	// "Fly By DENIZ". Filterable for sites whose logo already bakes in the name.
	$amber_show_word = (bool) apply_filters( 'luwipress_amber_show_wordmark', true );
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
						$amber_langs[] = [ 'code' => strtoupper( explode( '-', (string) ( $l['language_code'] ?? $code ) )[0] ), 'url' => $l['url'] ?? '#', 'active' => ! empty( $l['active'] ) ];
					}
				}
			} elseif ( function_exists( 'pll_the_languages' ) ) {
				$pll = pll_the_languages( [ 'raw' => 1, 'hide_if_empty' => 0 ] );
				if ( is_array( $pll ) ) {
					foreach ( $pll as $code => $l ) {
						$amber_langs[] = [ 'code' => strtoupper( explode( '-', (string) ( $l['slug'] ?? $code ) )[0] ), 'url' => $l['url'] ?? '#', 'active' => ! empty( $l['current_lang'] ) ];
					}
				}
			}
			// Operator-preferred topbar language order (filterable). Codes not in
			// the list fall to the end keeping their relative order. Default puts
			// the primary markets first: EN · TR · RU · AR · ZH · DE · FR · ES.
			$amber_lang_order = apply_filters( 'luwipress_amber_lang_order', array( 'EN', 'TR', 'RU', 'AR', 'ZH', 'DE', 'FR', 'ES' ) );
			if ( ! empty( $amber_lang_order ) && count( $amber_langs ) > 1 ) {
				$amber_order_idx = array_flip( array_values( $amber_lang_order ) );
				usort(
					$amber_langs,
					function ( $a, $b ) use ( $amber_order_idx ) {
						$ia = isset( $amber_order_idx[ $a['code'] ] ) ? $amber_order_idx[ $a['code'] ] : 999;
						$ib = isset( $amber_order_idx[ $b['code'] ] ) ? $amber_order_idx[ $b['code'] ] : 999;
						return $ia <=> $ib;
					}
				);
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
			<?php if ( $amber_show_word ) : ?>
				<span class="word"><?php echo $amber_word; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — built from esc_html'd parts ?></span>
			<?php endif; ?>
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
									<?php
									// Design layout: when the item points at a product category,
									// render its sub-items as the "By Experience" column and the
									// newest products as a "Popular" column (image+price). Otherwise
									// split the children across two columns. A featured card follows
									// either way, filling the 3-col grid the stylesheet expects.
									$amber_pop = function_exists( 'luwipress_amber_mega_products' ) ? luwipress_amber_mega_products( $top->url, 4 ) : array();
									if ( ! empty( $amber_pop ) ) : ?>
										<div class="mega-col">
											<span class="mega-h"><?php echo $label; ?></span>
											<?php foreach ( $children as $child ) :
												$desc = trim( (string) $child->description ); ?>
												<a href="<?php echo esc_url( $child->url ); ?>"><b><?php echo esc_html( $child->title ); ?></b><?php if ( $desc !== '' ) : ?><small><?php echo esc_html( $desc ); ?></small><?php endif; ?></a>
											<?php endforeach; ?>
											<a href="<?php echo $url; ?>" class="mega-all"><?php esc_html_e( 'View all', 'luwipress-amber' ); ?> <?php echo $amber_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
										</div>
										<div class="mega-col">
											<span class="mega-h"><?php esc_html_e( 'Popular', 'luwipress-amber' ); ?></span>
											<?php foreach ( $amber_pop as $amber_pp ) : ?>
												<a href="<?php echo esc_url( $amber_pp['url'] ); ?>"><b><?php echo esc_html( $amber_pp['title'] ); ?></b><?php if ( $amber_pp['price'] ) : ?><small><?php echo wp_kses_post( $amber_pp['price'] ); ?></small><?php endif; ?></a>
											<?php endforeach; ?>
										</div>
									<?php else :
										$amber_per_col = (int) ceil( count( $children ) / 2 );
										$amber_cols    = array_chunk( $children, max( 1, $amber_per_col ) );
										foreach ( $amber_cols as $amber_ci => $amber_col_items ) : ?>
											<div class="mega-col">
												<span class="mega-h"><?php echo $amber_ci === 0 ? $label : esc_html__( 'More', 'luwipress-amber' ); ?></span>
												<?php foreach ( $amber_col_items as $child ) :
													$desc = trim( (string) $child->description ); ?>
													<a href="<?php echo esc_url( $child->url ); ?>"><b><?php echo esc_html( $child->title ); ?></b><?php if ( $desc !== '' ) : ?><small><?php echo esc_html( $desc ); ?></small><?php endif; ?></a>
												<?php endforeach; ?>
												<?php if ( $amber_ci === count( $amber_cols ) - 1 ) : ?>
													<a href="<?php echo $url; ?>" class="mega-all"><?php esc_html_e( 'View all', 'luwipress-amber' ); ?> <?php echo $amber_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
												<?php endif; ?>
											</div>
										<?php endforeach;
									endif; ?>
									<?php
									// Featured card — newest product in the category this item
									// points at (falls back to newest product overall).
									$amber_feat = function_exists( 'luwipress_amber_mega_featured' ) ? luwipress_amber_mega_featured( $top->url ) : null;
									if ( $amber_feat ) : ?>
										<a class="mega-feature scene" href="<?php echo esc_url( $amber_feat['url'] ); ?>">
											<?php if ( $amber_feat['img'] ) : ?><img class="scene-img" src="<?php echo esc_url( $amber_feat['img'] ); ?>" alt="<?php echo esc_attr( $amber_feat['title'] ); ?>" loading="lazy"><?php endif; ?>
											<div class="mf-scrim"></div>
											<div class="mf-body">
												<span class="mf-tag">&#9733; <?php esc_html_e( 'Featured', 'luwipress-amber' ); ?></span>
												<h4><?php echo esc_html( $amber_feat['title'] ); ?></h4>
												<?php if ( $amber_feat['price'] ) : ?><div class="mf-meta"><span class="price"><?php echo wp_kses_post( $amber_feat['price'] ); ?></span></div><?php endif; ?>
											</div>
										</a>
									<?php endif; ?>
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
			<?php
			// WooCommerce header chrome — search overlay trigger, account popover,
			// cart drawer trigger. JS lives in frontend.js (setupSearchOverlay /
			// setupAccountPopover / setupCartDrawer); styled by widgets.css.
			if ( class_exists( 'WooCommerce' ) ) : ?>
				<a href="<?php echo esc_url( home_url( '/?s=' ) ); ?>" class="lwp-icon-btn lwp-search-btn" data-lwp-search-toggle aria-label="<?php esc_attr_e( 'Search', 'luwipress-amber' ); ?>">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
				</a>
				<div class="lwp-account-wrap">
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="lwp-icon-btn lwp-account-btn" data-lwp-account-toggle aria-label="<?php esc_attr_e( 'Account', 'luwipress-amber' ); ?>" aria-expanded="false">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
					</a>
					<div class="lwp-account-pop" role="menu" aria-hidden="true">
						<?php if ( is_user_logged_in() ) : $amber_cu = wp_get_current_user(); ?>
							<div class="lwp-account-pop__head">
								<span class="lwp-account-pop__hi"><?php printf( esc_html__( 'Hi, %s.', 'luwipress-amber' ), esc_html( $amber_cu->first_name ?: $amber_cu->display_name ) ); ?></span>
								<span class="lwp-account-pop__em"><?php echo esc_html( $amber_cu->user_email ); ?></span>
							</div>
							<nav class="lwp-account-pop__nav" aria-label="<?php esc_attr_e( 'Account quick links', 'luwipress-amber' ); ?>">
								<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'Dashboard', 'luwipress-amber' ); ?> <span>&rarr;</span></a>
								<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>"><?php esc_html_e( 'My bookings', 'luwipress-amber' ); ?> <span>&rarr;</span></a>
								<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-address' ) ); ?>"><?php esc_html_e( 'Addresses', 'luwipress-amber' ); ?> <span>&rarr;</span></a>
								<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-account' ) ); ?>"><?php esc_html_e( 'Account details', 'luwipress-amber' ); ?> <span>&rarr;</span></a>
							</nav>
							<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="lwp-account-pop__signout"><?php esc_html_e( 'Sign out &rarr;', 'luwipress-amber' ); ?></a>
						<?php else : $amber_login_url = wc_get_page_permalink( 'myaccount' ); ?>
							<div class="lwp-account-pop__head">
								<span class="lwp-account-pop__hi"><?php esc_html_e( 'Welcome', 'luwipress-amber' ); ?></span>
								<span class="lwp-account-pop__em"><?php esc_html_e( 'Sign in to view bookings, vouchers and trip details.', 'luwipress-amber' ); ?></span>
							</div>
							<form class="lwp-account-pop__form" method="post" action="<?php echo esc_url( $amber_login_url ); ?>">
								<label class="lwp-account-pop__label"><?php esc_html_e( 'Email or username', 'luwipress-amber' ); ?><input type="text" name="username" autocomplete="username" required /></label>
								<label class="lwp-account-pop__label"><?php esc_html_e( 'Password', 'luwipress-amber' ); ?><input type="password" name="password" autocomplete="current-password" required /></label>
								<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
								<button type="submit" name="login" value="1" class="btn btn--amber" style="width:100%;justify-content:center"><?php esc_html_e( 'Sign in', 'luwipress-amber' ); ?></button>
							</form>
							<div class="lwp-account-pop__alts">
								<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Forgot password?', 'luwipress-amber' ); ?></a>
								<a href="<?php echo esc_url( $amber_login_url ); ?>"><?php esc_html_e( 'Create account', 'luwipress-amber' ); ?></a>
							</div>
						<?php endif; ?>
					</div>
				</div>
				<?php $amber_cart_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0; ?>
				<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="lwp-icon-btn lwp-cart-btn lwp-cart-icon" data-lwp-cart-toggle aria-label="<?php esc_attr_e( 'Cart', 'luwipress-amber' ); ?>">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><path d="M6 7h12l-1.2 11a2 2 0 0 1-2 1.8H9.2a2 2 0 0 1-2-1.8L6 7z"/><path d="M9 7V5a3 3 0 1 1 6 0v2"/></svg><?php if ( $amber_cart_count > 0 ) : ?><span class="lwp-cart-badge"><?php echo (int) $amber_cart_count; ?></span><?php endif; ?>
				</a>
			<?php endif; ?>
			<a href="<?php echo esc_url( $amber_book_url ); ?>" class="btn btn--amber"><?php echo esc_html( $amber_book_label ); ?> <?php echo $amber_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			<button class="theme-toggle" id="headerTheme" aria-label="<?php esc_attr_e( 'Toggle theme', 'luwipress-amber' ); ?>">
				<svg class="icon-moon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
				<svg class="icon-sun" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4.5"/><path d="M12 2v2.5M12 19.5V22M2 12h2.5M19.5 12H22M4.9 4.9l1.8 1.8M17.3 17.3l1.8 1.8M19.1 4.9l-1.8 1.8M6.7 17.3l-1.8 1.8"/></svg>
			</button>
			<button class="burger" id="burger" aria-label="<?php esc_attr_e( 'Menu', 'luwipress-amber' ); ?>"><span></span></button>
		</div>
	</div>
</header>

<?php if ( class_exists( 'WooCommerce' ) ) : ?>
<!-- ============ SEARCH OVERLAY ============ -->
<div class="lwp-search-overlay" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Search', 'luwipress-amber' ); ?>">
	<div class="lwp-search-panel">
		<button type="button" class="lwp-search-close" aria-label="<?php esc_attr_e( 'Close search', 'luwipress-amber' ); ?>">&times;</button>
		<form role="search" method="get" class="lwp-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label for="lwp-search-input" class="lwp-search-label"><?php esc_html_e( 'Search tours & experiences', 'luwipress-amber' ); ?></label>
			<div class="lwp-search-input-wrap">
				<span class="lwp-search-icon" aria-hidden="true">&#9906;</span>
				<input id="lwp-search-input" type="search" name="s" class="lwp-search-input" placeholder="<?php esc_attr_e( 'Try “desert safari”, “helicopter”, “Burj Khalifa”…', 'luwipress-amber' ); ?>" autocomplete="off" />
				<?php if ( post_type_exists( 'product' ) ) : ?><input type="hidden" name="post_type" value="product" /><?php endif; ?>
			</div>
			<div class="lwp-search-hint"><?php esc_html_e( 'Press Enter to search · Esc to close', 'luwipress-amber' ); ?></div>
		</form>
	</div>
</div>

<!-- ============ CART DRAWER (slide-in from right) ============ -->
<aside class="lwp-cart-drawer" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Cart', 'luwipress-amber' ); ?>">
	<div class="lwp-cart-drawer__panel">
		<div class="lwp-cart-drawer__head">
			<h3><?php esc_html_e( 'Your cart', 'luwipress-amber' ); ?></h3>
			<button type="button" class="lwp-cart-drawer__close" aria-label="<?php esc_attr_e( 'Close cart', 'luwipress-amber' ); ?>">&times;</button>
		</div>
		<div class="lwp-cart-drawer__body widget_shopping_cart_content">
			<?php woocommerce_mini_cart(); ?>
		</div>
	</div>
</aside>
<?php endif; ?>

<!-- ============ MOBILE DRAWER ============ -->
<div class="drawer-overlay" id="drawerOverlay"></div>
<aside class="drawer" id="drawer">
	<div class="d-head">
		<div class="d-top">
			<span class="brand">
				<?php if ( $amber_logo_src ) : ?><img class="mark" src="<?php echo esc_url( $amber_logo_src ); ?>" alt="<?php echo esc_attr( $amber_site_name ); ?>"><?php endif; ?>
				<?php if ( $amber_show_word ) : ?><span class="word"><?php echo $amber_word; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><?php endif; ?>
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
