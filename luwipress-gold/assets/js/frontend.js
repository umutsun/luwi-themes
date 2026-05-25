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
		// 1.6.4+: the loader overlay is now server-rendered (see
		// inc/page-loader.php) and the boot class is set on <html> via
		// an inline script in <head>. JS only handles dismissal here.
		if ( document.body.classList.contains( 'elementor-editor-active' ) ) {
			document.documentElement.classList.remove( 'lwp-booting' );
			return;
		}

		// Find or build the loader. Server-rendered path (recommended)
		// hits the early-return; the legacy fallback below covers
		// installs where the page-loader.php hooks didn't fire (e.g.
		// custom Elementor canvas without wp_body_open, or filter
		// suppression mid-flight).
		var loader = document.querySelector( '.lwp-loader' );
		if ( ! loader ) {
			if ( prefersReduced ) {
				document.documentElement.classList.remove( 'lwp-booting' );
				return;
			}
			loader = document.createElement( 'div' );
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
		}

		var hide = function () {
			// 1.6.7: BEFORE fading the loader, force-reveal every element
			// auto-tagged with [data-lwp-reveal] / [data-lwp-stagger]. Why:
			// the loader signals "page ready" to the visitor — that
			// promise is broken if scrolling below-the-fold uncovers
			// `opacity: 0` sections waiting for IntersectionObserver to
			// fire. Operator reported "I see the loader gone, scroll, and
			// content suddenly appears halfway down — feels broken."
			// The IO-driven fade-in is fine for content added LATER (AJAX,
			// Elementor lazy sections), but everything already in the DOM
			// when the page is "ready" should be visible immediately.
			document.querySelectorAll( '[data-lwp-reveal]:not(.in), [data-lwp-stagger]:not(.in)' )
				.forEach( function ( el ) { el.classList.add( 'in' ); } );

			document.documentElement.classList.remove( 'lwp-booting' );
			loader.classList.add( 'lwp-loaded' );
			setTimeout( function () {
				loader.parentNode && loader.parentNode.removeChild( loader );
			}, 600 );
		};

		// Reduced-motion: hide as soon as the DOM is parseable so the
		// overlay never lingers — the visitor doesn't want decorative
		// chrome.
		if ( prefersReduced ) {
			if ( document.readyState !== 'loading' ) {
				hide();
			} else {
				document.addEventListener( 'DOMContentLoaded', hide, { once: true } );
			}
			return;
		}

		// 1.6.7: dismissal driven by REAL page-ready events, not a fixed
		// timer. Operator: "loading sayfanın yüklenmesi ile entegre olsun,
		// sadece timer olmasın". Sequence:
		//   1) window.load fires (DOM + critical CSS + sync scripts done)
		//   2) document.fonts.ready resolves (no FOIT/FOUT shift mid-read)
		//   3) above-the-fold <img> elements all `.complete === true`
		//   4) small stabilization tick (100ms) so layout settles
		// Each gate has its OWN per-stage timeout (1500ms) so a single
		// stalled font / image doesn't keep the visitor staring at the
		// overlay forever. The hard ceiling lives in page-loader.php's
		// inline `<head>` script (5s) — only fires if frontend.js itself
		// breaks, never during normal rendering.
		whenPageReady( hide );
	}

	function whenPageReady( cb ) {
		var done = false;
		var fire = function () {
			if ( done ) return;
			done = true;
			cb();
		};

		function step3_aboveFoldImages() {
			var imgs = document.querySelectorAll( 'img' );
			var pending = [];
			var vh = window.innerHeight;
			for ( var i = 0; i < imgs.length; i++ ) {
				var img = imgs[ i ];
				if ( img.complete ) continue;
				var r;
				try { r = img.getBoundingClientRect(); } catch ( e ) { continue; }
				// Only wait for images currently visible in initial viewport.
				if ( r.bottom > 0 && r.top < vh ) {
					pending.push( new Promise( function ( resolve ) {
						var settle = function () { resolve(); };
						img.addEventListener( 'load',  settle, { once: true } );
						img.addEventListener( 'error', settle, { once: true } );
						// per-image timeout — slow CDN shouldn't hold the page
						setTimeout( settle, 1500 );
					} ) );
				}
			}
			Promise.all( pending ).then( function () {
				setTimeout( fire, 100 );
			} );
		}

		function step2_fontsReady() {
			if ( document.fonts && document.fonts.ready && typeof document.fonts.ready.then === 'function' ) {
				var fontsTimeout = new Promise( function ( resolve ) { setTimeout( resolve, 1500 ); } );
				Promise.race( [ document.fonts.ready, fontsTimeout ] ).then( step3_aboveFoldImages );
			} else {
				step3_aboveFoldImages();
			}
		}

		function step1_windowLoad() {
			if ( document.readyState === 'complete' ) {
				step2_fontsReady();
			} else {
				window.addEventListener( 'load', step2_fontsReady, { once: true } );
			}
		}

		step1_windowLoad();
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
	/*  9. Operator-built `.tap-pills` filter — client-side category      */
	/*  filtering for HTML-widget pill bars sitting above a WC product   */
	/*  grid. No operator data attributes required: each pill's text is  */
	/*  slugified and matched against the localised category tree.       */
	/*  Designed so a hand-coded Elementor section (`.tap-pills` + the   */
	/*  next `ul.products` grid) becomes interactive without any         */
	/*  operator-side JS or markup additions.                            */
	/* ───────────────────────────────────────────────────────────────── */
	function setupTapPills() {
		var containers = document.querySelectorAll( '.tap-pills' );
		if ( ! containers.length ) return;
		var tree = ( window.LuwiGold && window.LuwiGold.catTree ) || {};

		function slugify( text ) {
			return ( text || '' ).toString().toLowerCase().trim()
				.replace( /[^a-z0-9]+/g, '-' )
				.replace( /^-+|-+$/g, '' );
		}

		// Find the slug-set the pill represents. "All" / empty → null
		// (show everything). Otherwise: exact key, then plural variants,
		// then prefix match. Returns null when no top-level branch fits.
		function resolvePill( label ) {
			var s = slugify( label );
			if ( s === '' || s === 'all' ) return null;
			if ( tree[ s ] ) return tree[ s ];
			if ( tree[ s + 's' ] ) return tree[ s + 's' ];
			if ( tree[ s + 'es' ] ) return tree[ s + 'es' ];
			// Prefix: pill "Bowed" → top-level "bowed-instruments".
			for ( var key in tree ) {
				if ( ! tree.hasOwnProperty( key ) ) continue;
				if ( key === s || key.indexOf( s + '-' ) === 0 ) return tree[ key ];
			}
			// Inverse prefix: pill "String Instruments" → "string-instruments".
			for ( var key2 in tree ) {
				if ( ! tree.hasOwnProperty( key2 ) ) continue;
				if ( s.indexOf( key2 ) === 0 || s === key2 ) return tree[ key2 ];
			}
			return null;
		}

		containers.forEach( function ( container ) {
			var pills = container.querySelectorAll( 'a' );
			if ( ! pills.length ) return;

			// Find the closest product grid downstream of the pill bar.
			// Walk up to find a common ancestor that also contains a
			// `ul.products`, then descend to it.
			var grid = null;
			var ancestor = container.parentElement;
			while ( ancestor && ! grid ) {
				grid = ancestor.querySelector( 'ul.products' );
				if ( ! grid ) ancestor = ancestor.parentElement;
				if ( ancestor === document.body ) break;
			}
			if ( ! grid ) return;

			var items = grid.querySelectorAll( 'li.product' );
			if ( ! items.length ) return;

			// Cache each item's class slug-set once so click handler is fast.
			var itemSlugs = [];
			items.forEach( function ( li ) {
				var slugs = [];
				li.classList.forEach( function ( c ) {
					if ( c.indexOf( 'product_cat-' ) === 0 ) {
						slugs.push( c.substring( 12 ) );
					}
				} );
				itemSlugs.push( slugs );
			} );

			pills.forEach( function ( pill ) {
				pill.addEventListener( 'click', function ( e ) {
					e.preventDefault();
					pills.forEach( function ( p ) { p.classList.remove( 'on' ); } );
					pill.classList.add( 'on' );

					var allowed = resolvePill( pill.textContent );

					items.forEach( function ( li, idx ) {
						if ( allowed === null ) {
							li.style.display = '';
							return;
						}
						var match = false;
						var slugs = itemSlugs[ idx ];
						for ( var i = 0; i < slugs.length; i++ ) {
							if ( allowed.indexOf( slugs[ i ] ) !== -1 ) {
								match = true;
								break;
							}
						}
						li.style.display = match ? '' : 'none';
					} );

					// Smooth fade — leverages existing transition on
					// .lwp-pcard. No layout jump because flex/grid
					// reflows once items disappear.
				} );
			} );
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
		setupTapPills();
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

	/**
	 * Auto-decorate YouTube embeds inside post content (1.7.0+).
	 *
	 * WP block-editor YouTube oEmbed renders an iframe; bare YouTube link
	 * shortcodes / paragraph links render as <a>. Both leave the visitor
	 * one click away from leaving the site to youtube.com. We swap them
	 * for a "lite-stub" trigger element that opens the existing
	 * `[data-lwp-yt]` modal in-place, keeping the visitor on the post.
	 *
	 * Scope: any element matched by the BLOG_SCOPES list. Skips Elementor
	 * widgets that already manage their own playback.
	 */
	function decorateBlogYouTubeEmbeds() {
		var BLOG_SCOPES = '.entry-content, .post-content, .luwipress-gold-single, article.post';
		var roots = document.querySelectorAll( BLOG_SCOPES );
		if ( ! roots.length ) { return; }

		roots.forEach( function ( root ) {
			// (1) Replace YouTube oEmbed iframes with lite-stubs.
			root.querySelectorAll( 'iframe[src*="youtube.com/embed/"], iframe[src*="youtube-nocookie.com/embed/"]' ).forEach( function ( iframe ) {
				var src = iframe.getAttribute( 'src' ) || '';
				var id  = ytExtractId( src );
				if ( ! id ) { return; }
				if ( iframe.dataset.lwpYtSwapped === '1' ) { return; }
				var stub = buildYtStub( id, iframe.getAttribute( 'title' ) || 'YouTube video' );
				// Preserve the iframe's wrapper (figure/div) — replace inline.
				iframe.parentNode.replaceChild( stub, iframe );
			} );

			// (2) Promote raw YouTube anchors to modal triggers.
			root.querySelectorAll( 'a[href*="youtube.com/watch"], a[href*="youtu.be/"], a[href*="youtube.com/shorts/"]' ).forEach( function ( a ) {
				if ( a.hasAttribute( 'data-lwp-yt' ) ) { return; }
				var id = ytExtractId( a.getAttribute( 'href' ) );
				if ( ! id ) { return; }
				a.setAttribute( 'data-lwp-yt', id );
				a.setAttribute( 'rel', ( a.getAttribute( 'rel' ) || '' ).replace( /\b(noopener|noreferrer|external)\b/g, '' ).trim() );
			} );
		} );
	}

	function buildYtStub( id, title ) {
		var a = document.createElement( 'a' );
		a.className = 'lwp-yt-stub';
		a.href = 'https://youtu.be/' + encodeURIComponent( id );
		a.setAttribute( 'data-lwp-yt', id );
		a.setAttribute( 'data-lwp-yt-swapped', '1' );
		a.setAttribute( 'aria-label', 'Play video — ' + title );
		a.style.backgroundImage = 'url("https://i.ytimg.com/vi/' + encodeURIComponent( id ) + '/hqdefault.jpg")';

		var play = document.createElement( 'span' );
		play.className = 'lwp-yt-stub__play';
		play.setAttribute( 'aria-hidden', 'true' );
		play.textContent = '▶'; // ▶

		var caption = document.createElement( 'span' );
		caption.className = 'lwp-yt-stub__caption';
		caption.textContent = title;

		a.appendChild( play );
		a.appendChild( caption );
		return a;
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', decorateBlogYouTubeEmbeds );
	} else {
		decorateBlogYouTubeEmbeds();
	}

	/* ───────────────────────────────────────────────────────────────── */
	/* UI-FIXES — float bar ↔ footer smooth handoff + mobile drawer       */
	/* ───────────────────────────────────────────────────────────────── */

	/* PDP gallery sticky release — REMOVED 2026-05-12. Earlier versions
	 * used an IntersectionObserver to swap `position: sticky` → `static`
	 * when related products entered view. That caused the gallery to
	 * vanish mid-description-scroll because static position dropped it
	 * back to its natural grid row 1 (= top of grid, often off-screen
	 * by then). Per the CSS Grid spec, a grid item's containing block
	 * IS its grid area, so `grid-area: gallery` + `position: sticky`
	 * already bounds the sticky behaviour to rows 1-2 (gallery+summary
	 * +tabs span). When the user scrolls past row 2 (tabs end), the
	 * browser naturally releases the pin. */

	/* (1) Float bar fades out as the footer enters the viewport, so the
	 * sticky panel doesn't visually collide with the footer's header.
	 * Activates only when the PDP sticky bar is rendered AND a footer
	 * landmark exists. Falls back gracefully without IntersectionObserver. */
	function initFloatBarFooterHandoff() {
		var bar = document.querySelector( '.lwp-pdp-sticky' );
		if ( ! bar ) return;
		var footer = document.querySelector(
			'footer.lwp-site-footer, footer.elementor-location-footer, footer.lwp-footer, footer.site-footer, body > footer'
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
		// Strip mega-menu count chips, chevrons and trailing fused digits from any anchor's
		// textContent so the drawer never shows duplicated counts ("Foo47> 47" → "Foo").
		// Mobile Spec Preview design: drawer rows show category name only.
		var cleanLabel = function ( a ) {
			if ( ! a ) return '';
			var clone = a.cloneNode( true );
			clone.querySelectorAll(
				'.lwp-mm-top-count, .lwp-mm-sub-count, .lwp-mm-chev, .lwp-mm-arrow, ' +
				'.menu-item-count, .count, .badge, sup, svg'
			).forEach( function ( el ) { el.remove(); } );
			return clone.textContent
				.replace( /[▸▾▼►▶+−·›»>]+/g, '' )
				.replace( /\s*\d+\s*$/, '' )
				.replace( /\s+/g, ' ' )
				.trim();
		};

		Array.prototype.forEach.call( sourceMenu.children, function ( li ) {
			var anchor = li.querySelector( ':scope > a' );
			if ( ! anchor ) return;
			var label = cleanLabel( anchor );
			if ( ! label ) return;

			var subSource = li.querySelector( '.lwp-mm-panel, .lwp-mm-dropdown' );
			var subLinks  = subSource ? subSource.querySelectorAll( 'a[href]' ) : [];

			if ( subLinks.length ) {
				var det = document.createElement( 'details' );
				det.className = 'lwp-drw-acc';
				var sum = document.createElement( 'summary' );
				sum.innerHTML =
					'<span class="nm"><span>' + label + '</span></span>' +
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
					var nm = cleanLabel( a );
					if ( ! nm ) return;
					var aSub = document.createElement( 'a' );
					aSub.href = a.href;
					aSub.innerHTML = '<span>' + nm + '</span>';
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

	/* ─────────────────────────────────────────────────────────── */
	/* Reading-progress bar — single-post only. rAF-throttled.     */
	/* Source: Mobile Spec Preview §07                              */
	/* ─────────────────────────────────────────────────────────── */
	function initReadingProgress() {
		var bar = document.querySelector( '.lwp-reading-progress > i' );
		if ( ! bar ) return;
		var article = document.querySelector( '.lwp-journal-article, article.type-post, article.post' ) || document.documentElement;
		var ticking = false;
		var update = function () {
			ticking = false;
			var rect = article.getBoundingClientRect();
			var viewportH = window.innerHeight || document.documentElement.clientHeight;
			var scrollable = rect.height - viewportH;
			if ( scrollable <= 0 ) { bar.style.width = '100%'; return; }
			var passed = Math.min( Math.max( -rect.top, 0 ), scrollable );
			bar.style.width = ( passed / scrollable * 100 ).toFixed( 2 ) + '%';
		};
		var onScroll = function () {
			if ( ticking ) return;
			ticking = true;
			window.requestAnimationFrame( update );
		};
		window.addEventListener( 'scroll', onScroll, { passive: true } );
		window.addEventListener( 'resize', onScroll, { passive: true } );
		update();
	}
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initReadingProgress );
	} else {
		initReadingProgress();
	}

	/* ─────────────────────────────────────────────────────────── */
	/* Audio card — Mobile Spec §05 (Master Profile sample player) */
	/* ─────────────────────────────────────────────────────────── */
	function initAudioCards() {
		var cards = document.querySelectorAll( '.lwp-audio-card' );
		var fmt = function ( s ) {
			if ( ! isFinite( s ) ) return '--:--';
			var m = Math.floor( s / 60 );
			var r = Math.floor( s % 60 );
			return m + ':' + ( r < 10 ? '0' : '' ) + r;
		};
		cards.forEach( function ( card ) {
			var audio = card.querySelector( 'audio' );
			var btn   = card.querySelector( '.play' );
			var bar   = card.querySelector( '.bar > i' );
			var cur   = card.querySelector( '.cur' );
			var dur   = card.querySelector( '.dur' );
			if ( ! audio || ! btn ) return;
			btn.addEventListener( 'click', function () {
				if ( audio.paused ) {
					document.querySelectorAll( '.lwp-audio-card audio' ).forEach( function ( a ) { if ( a !== audio ) a.pause(); } );
					audio.play();
					btn.textContent = '❚❚';
				} else {
					audio.pause();
					btn.textContent = '▶';
				}
			} );
			audio.addEventListener( 'loadedmetadata', function () {
				if ( dur ) dur.textContent = fmt( audio.duration );
			} );
			audio.addEventListener( 'timeupdate', function () {
				if ( cur ) cur.textContent = fmt( audio.currentTime );
				if ( bar && audio.duration ) bar.style.width = ( audio.currentTime / audio.duration * 100 ) + '%';
			} );
			audio.addEventListener( 'ended', function () {
				btn.textContent = '▶';
				if ( bar ) bar.style.width = '0%';
			} );
		} );
	}
	// Boot consolidated via initLwpWidgets() below — LS-rewrite-immune trigger.

	/* ─────────────────────────────────────────────────────────── */
	/* Reason chips — Mobile Spec §08 (Contact)                    */
	/* ─────────────────────────────────────────────────────────── */
	function initReasonChips() {
		var groups = document.querySelectorAll( '.lwp-reason-chips' );
		groups.forEach( function ( g ) {
			g.addEventListener( 'click', function ( e ) {
				var btn = e.target.closest( 'button[data-reason], [data-reason]' );
				if ( ! btn ) return;
				g.querySelectorAll( '[data-reason]' ).forEach( function ( b ) {
					b.classList.remove( 'is-active' );
					b.setAttribute( 'aria-checked', 'false' );
				} );
				btn.classList.add( 'is-active' );
				btn.setAttribute( 'aria-checked', 'true' );
				// Mirror to a hidden form field if present (#lwp-reason-input).
				var sink = document.getElementById( 'lwp-reason-input' );
				if ( sink ) sink.value = btn.getAttribute( 'data-reason' );
			} );
		} );
	}
	// Boot consolidated via initLwpWidgets() below — LS-rewrite-immune trigger.

	/* ─────────────────────────────────────────────────────────── */
	/* Newsletter widget (lwp-nl__form) — AJAX submit              */
	/* ─────────────────────────────────────────────────────────── */
	function initNewsletter() {
		var forms = document.querySelectorAll( '[data-lwp-newsletter]' );
		forms.forEach( function ( form ) {
			if ( form._lwpBound ) return;
			form._lwpBound = true;
			form.addEventListener( 'submit', function ( ev ) {
				ev.preventDefault();
				var msg     = form.querySelector( '.lwp-nl__msg' );
				var btn     = form.querySelector( 'button[type="submit"]' );
				var email   = ( form.querySelector( 'input[name="email"]' ) || {} ).value || '';
				var consent = !! ( ( form.querySelector( 'input[name="consent"]' ) || {} ).checked );
				var source  = ( form.querySelector( 'input[name="source"]' ) || {} ).value || '';
				var rest    = form.getAttribute( 'data-rest' );
				var nonce   = form.getAttribute( 'data-nonce' );
				var okMsg   = form.getAttribute( 'data-success' ) || 'Thanks!';
				var errMsg  = form.getAttribute( 'data-error' )   || 'Try again.';
				if ( ! rest || ! /\S+@\S+\.\S+/.test( email ) ) {
					if ( msg ) { msg.textContent = errMsg; msg.className = 'lwp-nl__msg is-error'; }
					return;
				}
				if ( form.querySelector( 'input[name="consent"][required]' ) && ! consent ) {
					if ( msg ) { msg.textContent = errMsg; msg.className = 'lwp-nl__msg is-error'; }
					return;
				}
				if ( btn ) { btn.disabled = true; btn.dataset.label = btn.textContent; btn.textContent = '…'; }
				fetch( rest, {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce || '' },
					body: JSON.stringify( { email: email, consent: consent, source: source } ),
				} )
					.then( function ( r ) { return r.json().then( function ( j ) { return { ok: r.ok, body: j }; } ); } )
					.then( function ( res ) {
						if ( res.ok && res.body && res.body.success ) {
							if ( msg ) { msg.textContent = okMsg; msg.className = 'lwp-nl__msg is-ok'; }
							form.reset();
						} else {
							var m = ( res.body && ( res.body.message || res.body.error ) ) || errMsg;
							if ( msg ) { msg.textContent = m; msg.className = 'lwp-nl__msg is-error'; }
						}
					} )
					.catch( function () {
						if ( msg ) { msg.textContent = errMsg; msg.className = 'lwp-nl__msg is-error'; }
					} )
					.finally( function () {
						if ( btn ) { btn.disabled = false; btn.textContent = btn.dataset.label || 'Subscribe'; }
					} );
			} );
		} );
	}

	/* ─────────────────────────────────────────────────────────── */
	/* Stat Counter (lwp-sc) — IntersectionObserver count-up       */
	/* ─────────────────────────────────────────────────────────── */
	function initStatCounters() {
		var blocks = document.querySelectorAll( '.lwp-sc' );
		if ( ! blocks.length || ! ( 'IntersectionObserver' in window ) ) { return; }
		var prefersReduced = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
		var io = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( e ) {
				if ( ! e.isIntersecting ) return;
				var block = e.target;
				if ( block._lwpRan ) return;
				block._lwpRan = true;
				io.unobserve( block );
				var duration = parseInt( block.getAttribute( 'data-duration' ) || '1800', 10 );
				block.querySelectorAll( '.lwp-sc__num' ).forEach( function ( cell ) {
					var target = parseInt( cell.getAttribute( 'data-target' ) || '0', 10 );
					var val    = cell.querySelector( '.lwp-sc__val' );
					if ( ! val ) return;
					if ( prefersReduced ) { val.textContent = target.toLocaleString(); return; }
					var start = performance.now();
					function step( now ) {
						var p = Math.min( 1, ( now - start ) / duration );
						var eased = 1 - Math.pow( 1 - p, 3 );
						val.textContent = Math.round( target * eased ).toLocaleString();
						if ( p < 1 ) requestAnimationFrame( step );
					}
					requestAnimationFrame( step );
				} );
			} );
		}, { threshold: 0.35 } );
		blocks.forEach( function ( b ) { io.observe( b ); } );
	}

	/* ─────────────────────────────────────────────────────────── */
	/* Countdown (lwp-cd) — tick + locale numbers                  */
	/* ─────────────────────────────────────────────────────────── */
	function initCountdowns() {
		var blocks = document.querySelectorAll( '.lwp-cd' );
		blocks.forEach( function ( block ) {
			var target = parseInt( block.getAttribute( 'data-target' ) || '0', 10 );
			if ( ! target ) return;
			var cells = {
				d: block.querySelector( '.lwp-cd__cell[data-unit="d"] .lwp-cd__num' ),
				h: block.querySelector( '.lwp-cd__cell[data-unit="h"] .lwp-cd__num' ),
				m: block.querySelector( '.lwp-cd__cell[data-unit="m"] .lwp-cd__num' ),
				s: block.querySelector( '.lwp-cd__cell[data-unit="s"] .lwp-cd__num' ),
			};
			var expiredEl = block.querySelector( '.lwp-cd__expired' );
			var cellsWrap = block.querySelector( '.lwp-cd__cells' );
			function tick() {
				var now = Math.floor( Date.now() / 1000 );
				var diff = target - now;
				if ( diff <= 0 ) {
					if ( cellsWrap ) cellsWrap.hidden = true;
					if ( expiredEl ) expiredEl.hidden = false;
					clearInterval( iv );
					return;
				}
				var d = Math.floor( diff / 86400 );
				var h = Math.floor( ( diff % 86400 ) / 3600 );
				var m = Math.floor( ( diff % 3600 ) / 60 );
				var s = diff % 60;
				if ( cells.d ) cells.d.textContent = String( d ).padStart( 2, '0' );
				if ( cells.h ) cells.h.textContent = String( h ).padStart( 2, '0' );
				if ( cells.m ) cells.m.textContent = String( m ).padStart( 2, '0' );
				if ( cells.s ) cells.s.textContent = String( s ).padStart( 2, '0' );
			}
			tick();
			var iv = setInterval( tick, 1000 );
		} );
	}

	/* ─────────────────────────────────────────────────────────── */
	/* Testimonials carousel (lwp-tst) — dots from track items      */
	/* ─────────────────────────────────────────────────────────── */
	function initTestimonials() {
		document.querySelectorAll( '.lwp-tst[data-layout="carousel"]' ).forEach( function ( wrap ) {
			var track = wrap.querySelector( '.lwp-tst__track' );
			var dots  = wrap.querySelector( '.lwp-tst__dots' );
			if ( ! track || ! dots ) return;
			var cards = track.querySelectorAll( '.lwp-tst__card' );
			if ( cards.length < 2 ) { dots.style.display = 'none'; return; }
			dots.innerHTML = '';
			cards.forEach( function ( _, i ) {
				var b = document.createElement( 'button' );
				b.type = 'button';
				b.setAttribute( 'role', 'tab' );
				b.setAttribute( 'aria-label', 'Slide ' + ( i + 1 ) );
				b.className = 'lwp-tst__dot' + ( i === 0 ? ' is-on' : '' );
				b.addEventListener( 'click', function () {
					cards[ i ].scrollIntoView( { behavior: 'smooth', block: 'nearest', inline: 'start' } );
				} );
				dots.appendChild( b );
			} );
			track.addEventListener( 'scroll', function () {
				var w = track.clientWidth;
				var idx = Math.round( track.scrollLeft / Math.max( 1, w ) );
				dots.querySelectorAll( '.lwp-tst__dot' ).forEach( function ( d, j ) {
					d.classList.toggle( 'is-on', j === idx );
				} );
			}, { passive: true } );
		} );
	}

	/* ─────────────────────────────────────────────────────────── */
	/* PDP thumbnail clicks — V61 moved the .flex-control-nav into  */
	/* an absolute-positioned capsule on top of the main image.     */
	/* Flexslider's bound click handler is unreliable in this        */
	/* layout (z-index / pointer-events races with WC's overlay     */
	/* trigger). Vanilla delegated handler bypasses that: read the  */
	/* clicked li's index, drive jQuery Flexslider via its public    */
	/* API if available, else manually swap the visible slide.       */
	/* ─────────────────────────────────────────────────────────── */
	function initPdpThumbClicks() {
		if ( ! document.body.classList.contains( 'single-product' ) ) { return; }

		document.addEventListener( 'click', function ( ev ) {
			var thumbLi = ev.target.closest(
				'.woocommerce-product-gallery .flex-control-nav li, ' +
				'.woocommerce-product-gallery .flex-control-thumbs li'
			);
			if ( ! thumbLi ) { return; }
			ev.preventDefault();
			ev.stopPropagation();

			var nav = thumbLi.parentElement;
			var lis = Array.prototype.slice.call( nav.children );
			var index = lis.indexOf( thumbLi );
			if ( index < 0 ) { return; }

			var gallery = thumbLi.closest( '.woocommerce-product-gallery' );
			if ( ! gallery ) { return; }

			// (1) Try jQuery Flexslider API.
			var handled = false;
			if ( window.jQuery ) {
				try {
					var $g = window.jQuery( gallery );
					var fs = $g.data( 'flexslider' );
					if ( fs && typeof fs.flexAnimate === 'function' ) {
						fs.flexAnimate( index, true );
						handled = true;
					}
				} catch ( e ) { /* fall through */ }
			}

			// (2) Fallback: manual slide swap by display + opacity.
			if ( ! handled ) {
				var slides = gallery.querySelectorAll(
					'.woocommerce-product-gallery__wrapper > .woocommerce-product-gallery__image, ' +
					'.woocommerce-product-gallery__wrapper > div'
				);
				slides.forEach( function ( s, i ) {
					if ( i === index ) {
						s.style.display = '';
						s.style.opacity = '1';
						s.classList.add( 'flex-active-slide' );
					} else {
						s.style.display = 'none';
						s.style.opacity = '0';
						s.classList.remove( 'flex-active-slide' );
					}
				} );
			}

			// (3) Update active thumb visual state.
			nav.querySelectorAll( 'li img' ).forEach( function ( img ) {
				img.classList.remove( 'flex-active' );
			} );
			var targetImg = thumbLi.querySelector( 'img' );
			if ( targetImg ) { targetImg.classList.add( 'flex-active' ); }
		}, true ); // capture phase — beats other handlers
	}

	// Overlay system — search + mobile drawer (1.7.37)
	function initOverlays() {
		var overlays = document.querySelectorAll( '.lwp-overlay[data-lwp-overlay]' );
		if ( ! overlays.length ) { return; }

		function openOverlay( key ) {
			overlays.forEach( function ( o ) {
				if ( o.getAttribute( 'data-lwp-overlay' ) === key ) {
					o.hidden = false;
					document.body.classList.add( 'lwp-overlay-open' );
					var input = o.querySelector( '[data-lwp-search-input]' );
					if ( input ) { setTimeout( function () { input.focus(); }, 50 ); }
				}
			} );
		}
		function closeAll() {
			overlays.forEach( function ( o ) { o.hidden = true; } );
			document.body.classList.remove( 'lwp-overlay-open' );
		}

		document.querySelectorAll( '[data-lwp-trigger]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				openOverlay( btn.getAttribute( 'data-lwp-trigger' ) );
			} );
		} );

		overlays.forEach( function ( o ) {
			o.addEventListener( 'click', function ( e ) {
				if ( e.target === o || ( e.target.hasAttribute && e.target.hasAttribute( 'data-lwp-overlay-close' ) )
					|| ( e.target.closest && e.target.closest( '[data-lwp-overlay-close]' ) ) ) {
					closeAll();
				}
			} );
		} );

		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' ) { closeAll(); }
		} );
	}

	// Shop toolbar grid/list view toggle (1.7.38)
	function initShopViewToggle() {
		var btns = document.querySelectorAll( '.lwp-stb__view-btn[data-lwp-view]' );
		if ( ! btns.length ) { return; }
		var stored;
		try { stored = localStorage.getItem( 'lwp-shop-view' ); } catch ( e ) {}
		if ( stored ) {
			document.body.classList.remove( 'lwp-view-grid', 'lwp-view-list' );
			document.body.classList.add( 'lwp-view-' + stored );
			btns.forEach( function ( b ) {
				b.classList.toggle( 'is-on', b.getAttribute( 'data-lwp-view' ) === stored );
			} );
		}
		btns.forEach( function ( btn ) {
			btn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				var view = btn.getAttribute( 'data-lwp-view' );
				document.body.classList.remove( 'lwp-view-grid', 'lwp-view-list' );
				document.body.classList.add( 'lwp-view-' + view );
				btns.forEach( function ( b ) { b.classList.remove( 'is-on' ); } );
				btn.classList.add( 'is-on' );
				try { localStorage.setItem( 'lwp-shop-view', view ); } catch ( err ) {}
			} );
		} );
	}

	// Reading progress (1.8.0) — sticky bar tracks scroll-through target element
	function initReadingProgress() {
		var bars = document.querySelectorAll( '[data-lwp-reading-progress]' );
		if ( ! bars.length ) { return; }
		bars.forEach( function ( wrap ) {
			var fill   = wrap.querySelector( '.lwp-rp__bar' );
			if ( ! fill ) { return; }
			var sel    = wrap.getAttribute( 'data-target' ) || '';
			var target = sel ? document.querySelector( sel ) : null;
			function update() {
				var pct = 0;
				if ( target ) {
					var rect = target.getBoundingClientRect();
					var h    = target.offsetHeight - window.innerHeight;
					if ( h > 0 ) {
						pct = Math.min( 100, Math.max( 0, ( -rect.top / h ) * 100 ) );
					}
				} else {
					var doc = document.documentElement;
					var max = doc.scrollHeight - window.innerHeight;
					if ( max > 0 ) {
						pct = Math.min( 100, ( window.scrollY / max ) * 100 );
					}
				}
				fill.style.width = pct.toFixed( 1 ) + '%';
			}
			update();
			window.addEventListener( 'scroll', update, { passive: true } );
			window.addEventListener( 'resize', update );
		} );
	}

	// Load-more — AJAX append (1.8.0)
	function initLoadMore() {
		var btns = document.querySelectorAll( '.lwp-lm__btn[data-lwp-loadmore="ajax"]' );
		if ( ! btns.length ) { return; }
		btns.forEach( function ( btn ) {
			btn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				if ( btn.classList.contains( 'is-loading' ) ) return;
				btn.classList.add( 'is-loading' );
				var url    = btn.getAttribute( 'href' );
				var target = document.querySelector( btn.getAttribute( 'data-lwp-target' ) || '' );
				if ( ! url || ! target ) { btn.classList.remove( 'is-loading' ); return; }
				fetch( url, { credentials: 'same-origin' } )
					.then( function ( r ) { return r.text(); } )
					.then( function ( html ) {
						var doc = new DOMParser().parseFromString( html, 'text/html' );
						var newItems = doc.querySelectorAll( btn.getAttribute( 'data-lwp-target' ) + ' > *' );
						newItems.forEach( function ( it ) { target.appendChild( it.cloneNode( true ) ); } );
						// Try to update btn to next page or hide
						var newBtn = doc.querySelector( '.lwp-lm__btn[data-lwp-loadmore="ajax"]' );
						if ( newBtn ) {
							btn.href = newBtn.getAttribute( 'href' );
							btn.textContent = newBtn.textContent;
							btn.classList.remove( 'is-loading' );
						} else {
							btn.style.display = 'none';
						}
					} )
					.catch( function () { btn.classList.remove( 'is-loading' ); } );
			} );
		} );
	}

	function initLwpWidgets() {
		// Try/catch around each init so a failure in one doesn't stop the rest.
		try { initNewsletter();      } catch ( e ) {}
		try { initStatCounters();    } catch ( e ) {}
		try { initCountdowns();      } catch ( e ) {}
		try { initTestimonials();    } catch ( e ) {}
		try { initPdpThumbClicks();  } catch ( e ) {}
		try { initAudioCards();      } catch ( e ) {}
		try { initReasonChips();     } catch ( e ) {}
		try { initOverlays();        } catch ( e ) {}
		try { initShopViewToggle();  } catch ( e ) {}
		try { initReadingProgress(); } catch ( e ) {}
		try { initLoadMore();        } catch ( e ) {}
	}

	// LiteSpeed JS-optimization rewrites the literal string 'DOMContentLoaded'
	// to 'DOMContentLiteSpeedLoaded' inside combined bundles — the standard
	// listener never fires. Boot via FOUR parallel triggers with an
	// idempotency guard so we run exactly once regardless of LS rewrite,
	// defer mode, or readyState at script-eval time.
	var lwpBooted = false;
	function bootLwpWidgets() {
		if ( lwpBooted ) { return; }
		lwpBooted = true;
		initLwpWidgets();
	}
	if ( document.readyState !== 'loading' ) {
		bootLwpWidgets();
	} else {
		// 1. Standard DOMContentLoaded (works when LS doesn't rewrite).
		document.addEventListener( 'DOMContentLoaded', bootLwpWidgets );
		// 2. readystatechange — LS doesn't rename this event.
		document.addEventListener( 'readystatechange', function () {
			if ( document.readyState !== 'loading' ) { bootLwpWidgets(); }
		} );
		// 3. window.load — final safety net.
		window.addEventListener( 'load', bootLwpWidgets );
		// 4. Timer fallback — covers extreme defer/rewrite cases.
		setTimeout( bootLwpWidgets, 100 );
	}
})();
