/* LuwiPress Gold — frontend animation + interaction layer.
 *
 * Source: Claude Design `00-animations.json` (handoff bundle) + smart-UI
 * additions for 1.2.0 (search overlay, mini-cart drawer, sticky add-to-cart).
 * Vanilla JS, no jQuery dependency for the core layer (cart bump uses
 * jQuery only when WooCommerce is active, with a native CustomEvent
 * fallback). Respects `prefers-reduced-motion`.
 */
(function () {
	'use strict';

	var prefersReduced = window.matchMedia &&
		window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	/* ───────────────────────────────────────────────────────────────── */
	/*  1. Page loader — SVG spinning arc + brand mark + progress bar     */
	/* ───────────────────────────────────────────────────────────────── */
	function buildLoader() {
		if ( prefersReduced ) return;
		if ( document.body.classList.contains( 'elementor-editor-active' ) ) return;
		if ( document.querySelector( '.lwp-loader' ) ) return;

		var loader = document.createElement( 'div' );
		loader.className = 'lwp-loader';
		loader.id = 'lwp-loader';
		loader.setAttribute( 'aria-hidden', 'true' );
		loader.innerHTML =
			'<div class="lwp-loader-inner">' +
				'<svg class="lwp-loader-mark" viewBox="0 0 60 60" width="60" height="60" aria-hidden="true">' +
					'<circle cx="30" cy="30" r="26" fill="none" stroke="currentColor" stroke-width="2" opacity=".25"/>' +
					'<circle class="lwp-loader-arc" cx="30" cy="30" r="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="40 200"/>' +
				'</svg>' +
				'<span class="lwp-loader-text">Loading</span>' +
				'<span class="lwp-loader-bar"><span></span></span>' +
			'</div>';
		document.body.insertBefore( loader, document.body.firstChild );

		var hide = function () {
			loader.classList.add( 'lwp-loaded' );
			setTimeout( function () {
				loader.parentNode && loader.parentNode.removeChild( loader );
			}, 600 );
		};
		if ( document.readyState === 'complete' ) {
			setTimeout( hide, 250 );
		} else {
			window.addEventListener( 'load', function () { setTimeout( hide, 250 ); } );
			setTimeout( hide, 4000 ); /* hard cap */
		}
	}

	/* ───────────────────────────────────────────────────────────────── */
	/*  2. Scroll reveal — observe [data-lwp-reveal] + [data-lwp-stagger] */
	/* ───────────────────────────────────────────────────────────────── */
	var revealIO = null;
	function setupRevealObserver() {
		if ( prefersReduced || ! ( 'IntersectionObserver' in window ) ) return;
		if ( revealIO ) return;
		revealIO = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( e ) {
				if ( e.isIntersecting ) {
					e.target.classList.add( 'in' );
					revealIO.unobserve( e.target );
				}
			} );
		}, { rootMargin: '0px 0px -10% 0px', threshold: 0.08 } );
	}
	function observeReveal() {
		if ( ! revealIO ) {
			/* Reduced motion / no IO → reveal everything inline */
			document.querySelectorAll( '[data-lwp-reveal], [data-lwp-stagger]' ).forEach( function ( el ) {
				el.classList.add( 'in' );
			} );
			return;
		}
		document.querySelectorAll( '[data-lwp-reveal]:not(.in), [data-lwp-stagger]:not(.in)' ).forEach( function ( el ) {
			revealIO.observe( el );
		} );
	}

	/* ───────────────────────────────────────────────────────────────── */
	/*  3. Auto-reveal injection — apply data-lwp-reveal to common        */
	/*  section selectors so existing Elementor pages animate without     */
	/*  the operator having to touch every widget.                        */
	/* ───────────────────────────────────────────────────────────────── */
	function autoInjectReveal() {
		if ( prefersReduced ) return;
		var selectors = [
			'.lwp-page > *',
			'.lwp-page-container > *',
			'.elementor-section',
			'.e-con.e-parent',
			'.lwp-pcard',
			'.lwp-acct-card',
			'.lwp-co-step',
			'.lwp-addr-card',
			'.lwp-acct-stat',
			'.lwp-cart-row',
			'.lwp-mm-feat'
		];
		selectors.forEach( function ( sel ) {
			document.querySelectorAll( sel ).forEach( function ( el ) {
				if ( ! el.hasAttribute( 'data-lwp-reveal' ) && ! el.hasAttribute( 'data-lwp-stagger' ) ) {
					el.setAttribute( 'data-lwp-reveal', '' );
				}
			} );
		} );
		/* Grids → stagger */
		[ 'ul.products', '.lwp-acct-stats', '.lwp-addr-grid', '.lwp-cart-list', '.lwp-shop-grid-wrap', '.lwp-mm-panel-cols' ].forEach( function ( sel ) {
			document.querySelectorAll( sel ).forEach( function ( el ) {
				if ( ! el.hasAttribute( 'data-lwp-stagger' ) && ! el.hasAttribute( 'data-lwp-reveal' ) ) {
					el.setAttribute( 'data-lwp-stagger', '' );
				}
			} );
		} );
	}

	/* ───────────────────────────────────────────────────────────────── */
	/*  4. Image fade-in on load                                          */
	/* ───────────────────────────────────────────────────────────────── */
	function setupImageFade() {
		document.querySelectorAll( 'img[data-lwp-fade]' ).forEach( function ( img ) {
			if ( img.complete ) {
				img.classList.add( 'loaded' );
			} else {
				img.addEventListener( 'load', function () { img.classList.add( 'loaded' ); }, { once: true } );
			}
		} );
	}

	/* ───────────────────────────────────────────────────────────────── */
	/*  5. Cart bump on WooCommerce add-to-cart                           */
	/* ───────────────────────────────────────────────────────────────── */
	function bumpCart() {
		document.querySelectorAll( '.lwp-cart-btn, .lwp-cart-icon, [data-lwp-cart-toggle]' ).forEach( function ( el ) {
			el.classList.remove( 'lwp-cart-bump' );
			void el.offsetWidth;
			el.classList.add( 'lwp-cart-bump' );
		} );
	}
	function setupCartBump() {
		if ( window.jQuery ) {
			window.jQuery( document.body ).on( 'added_to_cart removed_from_cart wc_fragments_refreshed', bumpCart );
		}
		document.addEventListener( 'wc_added_to_cart', bumpCart );
	}

	/* ───────────────────────────────────────────────────────────────── */
	/*  6. Search overlay — fullscreen panel toggled by .lwp-search-btn   */
	/* ───────────────────────────────────────────────────────────────── */
	function setupSearchOverlay() {
		var triggers = document.querySelectorAll( '.lwp-search-btn, [data-lwp-search-toggle]' );
		var overlay  = document.querySelector( '.lwp-search-overlay' );
		if ( ! triggers.length || ! overlay ) return;

		var input = overlay.querySelector( 'input[type="search"], input.lwp-search-input' );
		var close = overlay.querySelector( '.lwp-search-close' );

		function open( e ) {
			if ( e ) e.preventDefault();
			overlay.classList.add( 'is-open' );
			document.body.classList.add( 'lwp-locked' );
			setTimeout( function () { input && input.focus(); }, 200 );
		}
		function shut() {
			overlay.classList.remove( 'is-open' );
			document.body.classList.remove( 'lwp-locked' );
		}

		triggers.forEach( function ( t ) { t.addEventListener( 'click', open ); } );
		close && close.addEventListener( 'click', shut );
		overlay.addEventListener( 'click', function ( e ) { if ( e.target === overlay ) shut(); } );
		document.addEventListener( 'keydown', function ( e ) { if ( e.key === 'Escape' ) shut(); } );
	}

	/* ───────────────────────────────────────────────────────────────── */
	/*  7. Mini-cart drawer — slide in from right; toggled by cart icon   */
	/*  Operator can opt in by adding `data-lwp-cart-toggle` to the cart  */
	/*  button instead of letting it navigate to /cart/.                  */
	/* ───────────────────────────────────────────────────────────────── */
	function setupCartDrawer() {
		var triggers = document.querySelectorAll( '[data-lwp-cart-toggle]' );
		var drawer   = document.querySelector( '.lwp-cart-drawer' );
		if ( ! triggers.length || ! drawer ) return;

		var close = drawer.querySelector( '.lwp-cart-drawer__close' );
		function open( e ) {
			if ( e ) e.preventDefault();
			drawer.classList.add( 'is-open' );
			document.body.classList.add( 'lwp-locked' );
		}
		function shut() {
			drawer.classList.remove( 'is-open' );
			document.body.classList.remove( 'lwp-locked' );
		}
		triggers.forEach( function ( t ) { t.addEventListener( 'click', open ); } );
		close && close.addEventListener( 'click', shut );
		drawer.addEventListener( 'click', function ( e ) { if ( e.target === drawer ) shut(); } );
		document.addEventListener( 'keydown', function ( e ) { if ( e.key === 'Escape' ) shut(); } );

		/* Auto-open on add-to-cart, when WC fragments refresh fires */
		if ( window.jQuery ) {
			window.jQuery( document.body ).on( 'added_to_cart', function () {
				setTimeout( open, 300 );
			} );
		}
	}

	/* ───────────────────────────────────────────────────────────────── */
	/*  7b. Account popover — small dropdown anchored to the account icon */
	/*  Smart content: logged-in shows greeting + nav; logged-out shows   */
	/*  inline login form. Click outside / Esc closes. Hover fallback for */
	/*  mouse users via CSS `.lwp-account-wrap:hover .lwp-account-pop`.   */
	/* ───────────────────────────────────────────────────────────────── */
	function setupAccountPopover() {
		var triggers = document.querySelectorAll( '[data-lwp-account-toggle]' );
		if ( ! triggers.length ) return;
		triggers.forEach( function ( trigger ) {
			var wrap = trigger.closest( '.lwp-account-wrap' );
			var pop  = wrap ? wrap.querySelector( '.lwp-account-pop' ) : null;
			if ( ! pop ) return;

			function shut() {
				wrap.classList.remove( 'is-open' );
				trigger.setAttribute( 'aria-expanded', 'false' );
				pop.setAttribute( 'aria-hidden', 'true' );
			}
			function open() {
				wrap.classList.add( 'is-open' );
				trigger.setAttribute( 'aria-expanded', 'true' );
				pop.setAttribute( 'aria-hidden', 'false' );
			}
			function toggle( e ) {
				e.preventDefault();
				if ( wrap.classList.contains( 'is-open' ) ) shut(); else open();
			}

			trigger.addEventListener( 'click', toggle );
			document.addEventListener( 'click', function ( e ) {
				if ( ! wrap.contains( e.target ) ) shut();
			} );
			document.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'Escape' ) shut();
			} );
		} );
	}

	/* ───────────────────────────────────────────────────────────────── */
	/*  8. PDP sticky add-to-cart — show a fixed bottom bar when the      */
	/*  primary `.single_add_to_cart_button` scrolls off-screen.          */
	/* ───────────────────────────────────────────────────────────────── */
	function setupStickyPdp() {
		var trigger = document.querySelector( 'form.cart .single_add_to_cart_button' );
		if ( ! trigger ) return;
		var bar = document.querySelector( '.lwp-pdp-sticky' );
		if ( ! bar ) return;

		if ( ! ( 'IntersectionObserver' in window ) ) return;
		var io = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( e ) {
				/* Show bar when CTA is OFF screen (not intersecting) */
				if ( e.isIntersecting ) {
					bar.classList.remove( 'is-visible' );
				} else if ( e.boundingClientRect.top < 0 ) {
					bar.classList.add( 'is-visible' );
				} else {
					bar.classList.remove( 'is-visible' );
				}
			} );
		}, { threshold: 0 } );
		io.observe( trigger );

		/* Clicking the sticky CTA proxies to the real WC button */
		var stickyCta = bar.querySelector( '.lwp-pdp-sticky__cta' );
		stickyCta && stickyCta.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			trigger.scrollIntoView( { behavior: 'smooth', block: 'center' } );
			setTimeout( function () { trigger.click(); }, 350 );
		} );
	}

	/* ───────────────────────────────────────────────────────────────── */
	/*  Boot                                                              */
	/* ───────────────────────────────────────────────────────────────── */
	function boot() {
		buildLoader();
		setupRevealObserver();
		autoInjectReveal();
		observeReveal();
		setupImageFade();
		setupCartBump();
		setupSearchOverlay();
		setupCartDrawer();
		setupAccountPopover();
		setupStickyPdp();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}

	/* Re-scan on Elementor frontend init — picks up sections rendered
	 * by Elementor's lazy boot order. */
	document.addEventListener( 'elementor/frontend/init', function () {
		setTimeout( function () {
			autoInjectReveal();
			observeReveal();
			setupImageFade();
		}, 60 );
	} );

	/* WC fragments refresh sometimes recreates the cart icon — re-bind. */
	if ( window.jQuery ) {
		window.jQuery( document.body ).on( 'wc_fragments_refreshed wc_fragments_loaded', function () {
			setupCartDrawer();
		} );
	}

	/* ────────────────────────────────────────────────────────────────── *
	 * YouTube lightbox — `[data-lwp-yt]` opens a modal with the embed.    *
	 * Markup: <a href="https://youtu.be/ID" data-lwp-yt>Watch</a>         *
	 * Or:    <button data-lwp-yt="ID">Watch</button>                      *
	 * Esc / backdrop / × close. Modal removes the iframe on close so      *
	 * audio doesn't keep playing.                                          *
	 * ────────────────────────────────────────────────────────────────── */
	function ytExtractId( raw ) {
		if ( ! raw ) return '';
		// Plain id?
		if ( /^[A-Za-z0-9_-]{6,15}$/.test( raw ) ) return raw;
		try {
			var u = new URL( raw, window.location.href );
			if ( u.hostname.indexOf( 'youtu.be' ) !== -1 ) {
				return u.pathname.replace( /^\//, '' ).split( '/' )[0];
			}
			if ( u.hostname.indexOf( 'youtube' ) !== -1 ) {
				if ( u.pathname.indexOf( '/embed/' ) === 0 ) {
					return u.pathname.replace( '/embed/', '' ).split( '/' )[0];
				}
				return u.searchParams.get( 'v' ) || '';
			}
		} catch ( e ) {}
		return '';
	}
	function ytOpen( id ) {
		if ( ! id ) return;
		ytClose(); // any existing
		var modal = document.createElement( 'div' );
		modal.className = 'lwp-yt-modal';
		modal.setAttribute( 'role', 'dialog' );
		modal.setAttribute( 'aria-modal', 'true' );
		modal.setAttribute( 'aria-label', 'YouTube video' );
		modal.innerHTML = ''
			+ '<div class="lwp-yt-modal__backdrop" data-lwp-yt-close></div>'
			+ '<div class="lwp-yt-modal__panel">'
			+   '<button type="button" class="lwp-yt-modal__close" data-lwp-yt-close aria-label="Close">×</button>'
			+   '<div class="lwp-yt-modal__frame">'
			+     '<iframe src="https://www.youtube-nocookie.com/embed/' + encodeURIComponent( id ) + '?autoplay=1&rel=0&modestbranding=1" '
			+       'title="YouTube video" frameborder="0" '
			+       'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" '
			+       'allowfullscreen></iframe>'
			+   '</div>'
			+ '</div>';
		document.body.appendChild( modal );
		document.body.classList.add( 'lwp-yt-open' );
		// Animate in.
		requestAnimationFrame( function () { modal.classList.add( 'is-open' ); } );
		// Close handlers.
		modal.addEventListener( 'click', function ( e ) {
			if ( e.target.closest( '[data-lwp-yt-close]' ) ) ytClose();
		} );
		document.addEventListener( 'keydown', ytEscHandler );
	}
	function ytClose() {
		var modal = document.querySelector( '.lwp-yt-modal' );
		if ( ! modal ) return;
		modal.classList.remove( 'is-open' );
		document.body.classList.remove( 'lwp-yt-open' );
		document.removeEventListener( 'keydown', ytEscHandler );
		setTimeout( function () { if ( modal.parentNode ) modal.parentNode.removeChild( modal ); }, 220 );
	}
	function ytEscHandler( e ) { if ( e.key === 'Escape' ) ytClose(); }
	document.addEventListener( 'click', function ( e ) {
		var trig = e.target.closest( '[data-lwp-yt]' );
		if ( ! trig ) return;
		// Skip if the trigger is inside the modal itself (close button etc).
		if ( trig.closest( '.lwp-yt-modal' ) ) return;
		var raw = trig.getAttribute( 'data-lwp-yt' ) || trig.getAttribute( 'href' ) || '';
		var id  = ytExtractId( raw );
		if ( ! id ) return;
		e.preventDefault();
		ytOpen( id );
	} );

	/* ───────────────────────────────────────────────────────────────── */
	/* UI-FIXES — float bar ↔ footer smooth handoff + mobile drawer       */
	/* ───────────────────────────────────────────────────────────────── */

	/* (1) Float bar fades out as the footer enters the viewport, so the
	 * sticky panel doesn't visually collide with the footer's header.
	 * Activates only when the PDP sticky bar is rendered AND a footer
	 * landmark exists. Falls back gracefully without IntersectionObserver. */
	function initFloatBarFooterHandoff() {
		var bar = document.querySelector( '.lwp-pdp-sticky' );
		if ( ! bar ) return;
		var footer = document.querySelector(
			'footer.elementor-location-footer, footer.lwp-footer, footer.site-footer, body > footer'
		);
		if ( ! footer || ! ( 'IntersectionObserver' in window ) ) return;
		var io = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( e ) {
				if ( e.isIntersecting ) {
					bar.classList.add( 'is-near-footer' );
				} else {
					bar.classList.remove( 'is-near-footer' );
				}
			} );
		}, { rootMargin: '0px 0px -10% 0px', threshold: 0 } );
		io.observe( footer );
	}
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initFloatBarFooterHandoff );
	} else {
		initFloatBarFooterHandoff();
	}

	/* (4) Mobile drawer — clones the desktop mega-menu top-level into a
	 * slide-in panel. Each parent <li> with children becomes an accordion
	 * row. The drawer is built once on first hamburger tap so we don't
	 * pay the DOM cost on desktop. */
	function buildMobileDrawer() {
		if ( document.querySelector( '.lwp-mobile-drawer' ) ) return;
		// Source menu — prefer the Gold mega-menu top list. Fall back to the
		// first nav inside an Elementor Pro header, then to any visible
		// horizontal nav. Drawer is built once on first hamburger tap.
		var sourceMenu =
			document.querySelector( '.lwp-mm-top' ) ||
			document.querySelector( 'header.elementor-location-header nav ul.menu' ) ||
			document.querySelector( 'header.elementor-location-header nav ul' ) ||
			document.querySelector( 'header nav ul.menu' ) ||
			document.querySelector( 'nav.main-navigation ul' );
		if ( ! sourceMenu ) return;

		var siteName = ( document.querySelector( '.lwp-wordmark, .lwp-site-header-logo' ) || {} ).textContent || document.title || 'Menu';
		siteName = siteName.replace( /\s+/g, ' ' ).trim().slice( 0, 32 );

		var scrim = document.createElement( 'div' );
		scrim.className = 'lwp-mobile-scrim';
		document.body.appendChild( scrim );

		// Wordmark with italic-gold accent letter (Tapadum-style)
		function wordmarkHtml( name ) {
			var n = name || 'Menu';
			var pos = Math.max( 1, Math.floor( n.length / 2 ) );
			var letter = n.charAt( pos );
			if ( ! letter ) return '<span>' + n + '</span>';
			return n.slice( 0, pos ) + '<em>' + letter + '</em>' + n.slice( pos + 1 );
		}

		var drawer = document.createElement( 'nav' );
		drawer.className = 'lwp-mobile-drawer';
		drawer.setAttribute( 'role', 'dialog' );
		drawer.setAttribute( 'aria-modal', 'true' );
		drawer.setAttribute( 'aria-label', 'Site menu' );
		drawer.innerHTML =
			'<header class="lwp-mobile-drawer__head">' +
				'<a href="' + ( window.location.origin || '/' ) + '" class="lwp-mobile-drawer__brand">' + wordmarkHtml( siteName ) + '</a>' +
				'<button type="button" class="lwp-mobile-drawer__close" aria-label="Close menu">' +
					'<span class="bar"></span><span class="bar"></span>' +
				'</button>' +
			'</header>' +
			'<div class="lwp-mobile-drawer__search">' +
				'<label class="lwp-mobile-drawer__search-field">' +
					'<span class="icon" aria-hidden="true">⌕</span>' +
					'<input type="search" placeholder="Search instruments, masters…" data-lwp-drw-search />' +
					'<span class="ai-badge">AI</span>' +
				'</label>' +
			'</div>' +
			'<div class="lwp-mobile-drawer__body">' +
				'<div class="lwp-mobile-drawer__lbl">Browse</div>' +
				'<div data-lwp-drw-nav></div>' +
				'<div data-lwp-drw-utility></div>' +
				'<div data-lwp-drw-pick></div>' +
			'</div>' +
			'<footer class="lwp-mobile-drawer__foot" data-lwp-drw-foot></footer>';
		document.body.appendChild( drawer );

		var navHost  = drawer.querySelector( '[data-lwp-drw-nav]' );
		var utilHost = drawer.querySelector( '[data-lwp-drw-utility]' );
		var pickHost = drawer.querySelector( '[data-lwp-drw-pick]' );
		var footHost = drawer.querySelector( '[data-lwp-drw-foot]' );

		// Build nav rows from source menu — children → <details> accordion,
		// childless → flat italic row. WPML/Polylang language items already
		// stripped server-side in the mega-menu walker since 1.5.1.
		Array.prototype.forEach.call( sourceMenu.children, function ( li ) {
			var anchor = li.querySelector( ':scope > a' );
			if ( ! anchor ) return;
			var label = anchor.textContent.replace( /[▸▾▼+−·]+|\s+\d+\s*$/g, '' ).trim();
			if ( ! label ) return;

			var subSource = li.querySelector( '.lwp-mm-panel, .lwp-mm-dropdown' );
			var subLinks  = subSource ? subSource.querySelectorAll( 'a[href]' ) : [];
			var topCount  = ( anchor.querySelector( '.lwp-mm-top-count' ) || {} ).textContent;
			topCount = ( topCount || '' ).trim();

			if ( subLinks.length ) {
				var det = document.createElement( 'details' );
				det.className = 'lwp-drw-acc';
				var sum = document.createElement( 'summary' );
				sum.innerHTML =
					'<span class="nm">' +
						'<span>' + label + '</span>' +
						( topCount ? '<span class="ct">' + topCount + '</span>' : '' ) +
					'</span>' +
					'<span class="pm" aria-hidden="true"></span>';
				det.appendChild( sum );

				var panel = document.createElement( 'div' );
				panel.className = 'lwp-drw-acc-panel';
				var panelInner = document.createElement( 'div' );
				var list = document.createElement( 'div' );
				list.className = 'lwp-drw-acc-list';

				// "All <category>" entry → the parent link itself
				if ( anchor.getAttribute( 'href' ) && anchor.getAttribute( 'href' ) !== '#' ) {
					var aAll = document.createElement( 'a' );
					aAll.href = anchor.href;
					aAll.innerHTML = '<span>↳ ' + label + '</span>';
					list.appendChild( aAll );
				}
				Array.prototype.forEach.call( subLinks, function ( a ) {
					var raw = a.textContent.replace( /\s+/g, ' ' ).trim();
					if ( ! raw ) return;
					// Pull trailing count "Arabic Oud 24" → name "Arabic Oud", count "24"
					var m = raw.match( /^(.+?)\s+(\d+)\s*$/ );
					var nm = m ? m[1].trim() : raw;
					var ct = m ? m[2] : '';
					var aSub = document.createElement( 'a' );
					aSub.href = a.href;
					aSub.innerHTML = '<span>' + nm + '</span>' + ( ct ? '<span class="ct">' + ct + '</span>' : '' );
					list.appendChild( aSub );
				} );
				panelInner.appendChild( list );
				panel.appendChild( panelInner );
				det.appendChild( panel );
				navHost.appendChild( det );
			} else {
				var flat = document.createElement( 'a' );
				flat.className = 'lwp-drw-flat';
				flat.href = anchor.href;
				flat.innerHTML = label + ' <span class="arr" aria-hidden="true">→</span>';
				navHost.appendChild( flat );
			}
		} );

		// Utility row — Account · Cart · Track order
		var cfg = ( window.LuwiGold && window.LuwiGold.drawer ) || {};
		var accountUrl = cfg.accountUrl || '/my-account/';
		var cartUrl    = cfg.cartUrl    || '/cart/';
		var trackUrl   = cfg.trackUrl   || accountUrl;
		var cartCount  = parseInt( ( document.querySelector( '.lwp-cart-badge' ) || {} ).textContent || '0', 10 ) || 0;
		utilHost.outerHTML =
			'<div class="lwp-drw-utility">' +
				'<a class="lwp-drw-util" href="' + accountUrl + '">' +
					'<span class="glyph" aria-hidden="true">a</span><span>Account</span>' +
				'</a>' +
				'<a class="lwp-drw-util" href="' + cartUrl + '">' +
					'<span class="glyph" aria-hidden="true">⌂</span><span>Cart</span>' +
					( cartCount > 0 ? '<span class="badge">' + cartCount + '</span>' : '' ) +
				'</a>' +
				'<a class="lwp-drw-util" href="' + trackUrl + '">' +
					'<span class="glyph" aria-hidden="true">↗</span><span>Track</span>' +
				'</a>' +
			'</div>';

		// Atelier pick — pulled from window.LuwiGold.drawer.pick if present,
		// otherwise the first product card on the page (cheap heuristic).
		var pick = cfg.pick;
		if ( ! pick ) {
			var fallbackCard = document.querySelector( '.lwp-pcard a[href]' );
			if ( fallbackCard ) {
				var thumb = fallbackCard.querySelector( 'img' );
				var name  = ( fallbackCard.querySelector( '.lwp-pcard-title' ) || {} ).textContent || '';
				var price = ( fallbackCard.querySelector( '.lwp-pcard-price' ) || {} ).textContent || '';
				var maker = ( fallbackCard.querySelector( '.lwp-pcard-maker' ) || {} ).textContent || '';
				if ( name ) {
					pick = {
						url: fallbackCard.href,
						img: thumb ? thumb.src : '',
						name: name.trim(),
						maker: maker.trim(),
						price: price.replace( /\s+/g, ' ' ).trim(),
						label: 'This week'
					};
				}
			}
		}
		if ( pick ) {
			var imgStyle = pick.img ? ' style="background-image:url(' + pick.img + ');background-size:cover;background-position:center;"' : '';
			pickHost.outerHTML =
				'<a class="lwp-drw-pick" href="' + ( pick.url || '#' ) + '">' +
					'<div class="img"' + imgStyle + '><span class="stamp">' + ( pick.label || 'Pick' ) + '</span></div>' +
					'<div class="meta">' +
						'<span class="lbl">Atelier pick</span>' +
						'<h4>' + pick.name + '</h4>' +
						( pick.maker ? '<span class="mk">' + pick.maker + '</span>' : '' ) +
						( pick.price ? '<span class="px">' + pick.price + '</span>' : '' ) +
					'</div>' +
				'</a>';
		} else {
			pickHost.outerHTML = '';
		}

		// Foot — language switcher + social row + contact line
		var langSrc = document.querySelector( '.lwp-lang-pill' );
		var langHtml = '';
		if ( langSrc ) {
			var langInner = '';
			Array.prototype.forEach.call( langSrc.querySelectorAll( 'a' ), function ( a ) {
				var on = a.classList.contains( 'is-active' ) ? ' is-active' : '';
				var code = ( a.textContent || '' ).trim();
				if ( ! code ) return;
				langInner += '<a href="' + a.href + '" class="' + on.replace( ' ', '' ) + '">' + code + '</a>';
			} );
			if ( langInner ) {
				langHtml =
					'<div class="lwp-drw-foot-row">' +
						'<span class="lwp-drw-foot-lbl">Language</span>' +
						'<div class="lwp-mobile-drawer__lang">' + langInner + '</div>' +
					'</div>';
			}
		}

		var social = cfg.social || [];
		var socialHtml = '';
		if ( social.length ) {
			var socialInner = '';
			social.forEach( function ( s ) {
				socialInner += '<a href="' + s.url + '" aria-label="' + s.label + '" target="_blank" rel="noopener">' + s.code + '</a>';
			} );
			socialHtml =
				'<div class="lwp-drw-foot-row">' +
					'<span class="lwp-drw-foot-lbl">Follow</span>' +
					'<div class="lwp-drw-social">' + socialInner + '</div>' +
				'</div>';
		}

		var contact = cfg.contact || {};
		var contactHtml = '';
		if ( contact.phone || contact.email || contact.location ) {
			contactHtml = '<div class="lwp-drw-contact">';
			if ( contact.phone )    contactHtml += '<a href="tel:' + contact.phone.replace( /\s+/g, '' ) + '">' + contact.phone + '</a>';
			if ( contact.email )    contactHtml += '<a href="mailto:' + contact.email + '">' + contact.email + '</a>';
			if ( contact.location ) contactHtml += '<span>' + contact.location + '</span>';
			contactHtml += '</div>';
		}

		footHost.innerHTML = langHtml + socialHtml + contactHtml;
		if ( ! footHost.innerHTML ) {
			drawer.removeChild( footHost );
		}

		function close() {
			drawer.classList.remove( 'is-open' );
			scrim.classList.remove( 'is-open' );
			document.body.classList.remove( 'lwp-drw-locked' );
			document.body.style.overflow = '';
			var t = document.querySelector( '.lwp-mobile-toggle' );
			if ( t ) { t.classList.remove( 'is-open' ); t.setAttribute( 'aria-expanded', 'false' ); }
		}
		drawer.querySelector( '.lwp-mobile-drawer__close' ).addEventListener( 'click', close );
		scrim.addEventListener( 'click', close );
		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && drawer.classList.contains( 'is-open' ) ) close();
		} );
	}

	function initMobileDrawer() {
		// Inject hamburger button if it isn't already in the header markup.
		// Falls back across header rendering paths:
		//   1. Gold fallback header  → .lwp-site-header-inner
		//   2. Elementor Pro Theme Builder → header.elementor-location-header
		//      first inner container
		//   3. Generic theme header → first <header> with descendants
		// In every case we inject the toggle button as the FIRST child so it
		// sits at the left edge of the bar. The CSS @media (max-width: 900px)
		// rule decides when it actually shows.
		if ( ! document.querySelector( '.lwp-mobile-toggle' ) ) {
			var host =
				document.querySelector( '.lwp-site-header-inner' ) ||
				document.querySelector( 'header.elementor-location-header .e-con-inner, header.elementor-location-header .elementor-container, header.elementor-location-header > .elementor-section' ) ||
				document.querySelector( 'header.site-header > .container, header.site-header > .wrap' ) ||
				document.querySelector( 'body > header, #masthead' );
			if ( host ) {
				var btn = document.createElement( 'button' );
				btn.type = 'button';
				btn.className = 'lwp-mobile-toggle';
				btn.setAttribute( 'aria-label', 'Toggle menu' );
				btn.setAttribute( 'aria-expanded', 'false' );
				btn.innerHTML = '<span class="lwp-mobile-toggle__bars"><span></span></span>';
				host.insertBefore( btn, host.firstChild );
			}
		}

		document.addEventListener( 'click', function ( e ) {
			var trig = e.target.closest( '.lwp-mobile-toggle' );
			if ( ! trig ) return;
			e.preventDefault();
			buildMobileDrawer();
			var drawer = document.querySelector( '.lwp-mobile-drawer' );
			var scrim  = document.querySelector( '.lwp-mobile-scrim' );
			if ( ! drawer || ! scrim ) return;
			var open = drawer.classList.toggle( 'is-open' );
			scrim.classList.toggle( 'is-open', open );
			trig.classList.toggle( 'is-open', open );
			trig.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			document.body.classList.toggle( 'lwp-drw-locked', open );
			document.body.style.overflow = open ? 'hidden' : '';
		} );
	}
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initMobileDrawer );
	} else {
		initMobileDrawer();
	}
})();
