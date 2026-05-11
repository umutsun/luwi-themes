/**
 * LuwiPress Gold — Shop archive Load More
 *
 * Activates when the operator toggles `luwipress_gold_shop_loadmore` on.
 * Hijacks the woocommerce_pagination output:
 *   • mode="button"   shows a "Load more products" button below the grid
 *   • mode="infinite" auto-fetches the next page when the sentinel scrolls
 *                     into view (IntersectionObserver, prefers-reduced-motion respected)
 *
 * Strategy: fetch the next paginated archive URL (?paged=N), parse the HTML,
 * pluck `<ul class="products">` and the existing pagination block, append
 * products to the current grid, replace pagination so navigation stays
 * functional for SEO/accessibility (we hide it visually via CSS).
 */
( function () {
	'use strict';
	if ( typeof window.LWP_GOLD_LM === 'undefined' ) { return; }
	var cfg = window.LWP_GOLD_LM;

	function $( sel, root ) { return ( root || document ).querySelector( sel ); }
	function $$( sel, root ) { return Array.from( ( root || document ).querySelectorAll( sel ) ); }

	function setStatus( wrap, msg ) {
		var s = wrap.querySelector( '.lwp-loadmore-status' );
		if ( s ) { s.textContent = msg || ''; }
	}

	// Spinner shown during the fetch so users see immediate visual feedback
	// when infinite-scroll triggers. Without it the page feels stalled.
	function setSpinner( wrap, on ) {
		var sp = wrap.querySelector( '.lwp-loadmore-spinner' );
		if ( ! sp ) { return; }
		if ( on ) {
			sp.hidden = false;
			sp.removeAttribute( 'aria-hidden' );
		} else {
			sp.hidden = true;
			sp.setAttribute( 'aria-hidden', 'true' );
		}
	}

	function nextUrl( wrap ) {
		var current = parseInt( wrap.dataset.current || '1', 10 );
		var max     = parseInt( wrap.dataset.max || '1', 10 );
		if ( current >= max ) { return null; }
		var next = current + 1;
		// Prefer the actual "next" link from the existing pagination — that
		// gives us the WP-correct URL shape for our permalink structure.
		var nextLink = $( '.woocommerce-pagination a.next' );
		if ( nextLink && nextLink.href ) {
			// Replace ?paged=X / /page/X/ in the next link with our target page.
			var hrefURL = new URL( nextLink.href, window.location.origin );
			if ( hrefURL.searchParams.has( 'paged' ) ) {
				hrefURL.searchParams.set( 'paged', next );
				return hrefURL.toString();
			}
			// Fall through to manual construction.
		}
		// Manual construction: append /page/N/ to current pathname.
		var url = new URL( window.location.href );
		var path = url.pathname.replace( /\/page\/\d+\/?$/, '/' );
		if ( path.slice( -1 ) !== '/' ) { path += '/'; }
		url.pathname = path + 'page/' + next + '/';
		return url.toString();
	}

	// Symbolic state markers — language-neutral so we don't ship 4 i18n strings
	// per state across FR/IT/ES/DE/AR/etc. Glyphs picked from common Unicode
	// punctuation present in every modern font:
	//   '·'   while loading (resting; spinner ring covers active state)
	//   'X / Y'  numeric pagination after each successful append
	//   '✓'   end of list
	//   '⚠'   error
	var SYM = { loading: '', done: '✓', error: '⚠' };

	function loadNext( wrap, btn ) {
		var url = nextUrl( wrap );
		if ( ! url ) {
			btn && ( btn.hidden = true );
			setSpinner( wrap, false );
			setStatus( wrap, SYM.done );
			return;
		}
		btn && ( btn.disabled = true );
		var origLabel = btn ? btn.textContent : '';
		// Button label stays textual ONLY in button-mode (operator opt-in);
		// infinite mode hides the button so text label never renders.
		if ( btn ) { btn.textContent = cfg.i18n.loading; }
		setStatus( wrap, SYM.loading );
		setSpinner( wrap, true );

		fetch( url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } } )
			.then( function ( r ) {
				if ( ! r.ok ) { throw new Error( 'http ' + r.status ); }
				return r.text();
			} )
			.then( function ( html ) {
				var dom = new DOMParser().parseFromString( html, 'text/html' );

				// Append product cards from the next page's `<ul.products>`.
				var newUl = dom.querySelector( 'ul.products' );
				var ourUl = $( 'ul.products' );
				if ( newUl && ourUl ) {
					Array.from( newUl.children ).forEach( function ( li ) {
						ourUl.appendChild( li );
					} );
				}

				// Sync pagination so the next click hits page+1 again. We
				// keep WC's pagination markup visually hidden (.lwp-loadmore-active
				// hides .woocommerce-pagination) so the cycle continues.
				var newPag = dom.querySelector( '.woocommerce-pagination' );
				var ourPag = $( '.woocommerce-pagination' );
				if ( newPag && ourPag ) {
					ourPag.innerHTML = newPag.innerHTML;
				}

				// Bump current.
				var current = parseInt( wrap.dataset.current || '1', 10 );
				var max     = parseInt( wrap.dataset.max || '1', 10 );
				current++;
				wrap.dataset.current = String( current );
				setSpinner( wrap, false );
				if ( current >= max ) {
					if ( btn ) { btn.hidden = true; }
					setStatus( wrap, SYM.done );
				} else {
					if ( btn ) { btn.disabled = false; btn.textContent = origLabel || cfg.i18n.load_more; }
					setStatus( wrap, current + ' / ' + max );
				}

				// Trigger any lazy-load observer on newly appended images.
				if ( typeof window.dispatchEvent === 'function' ) {
					window.dispatchEvent( new CustomEvent( 'lwp:loadmore:appended' ) );
				}
			} )
			.catch( function () {
				if ( btn ) { btn.disabled = false; btn.textContent = origLabel || cfg.i18n.load_more; }
				setSpinner( wrap, false );
				setStatus( wrap, SYM.error );
			} );
	}

	function bindButton( wrap ) {
		var btn = wrap.querySelector( '.lwp-loadmore-btn' );
		if ( ! btn ) { return; }
		btn.addEventListener( 'click', function () { loadNext( wrap, btn ); } );
	}

	function bindInfinite( wrap ) {
		// Infinite mode hides the explicit button; uses an IntersectionObserver
		// over the wrapper itself.
		var btn = wrap.querySelector( '.lwp-loadmore-btn' );
		if ( btn ) { btn.hidden = true; }
		if ( ! ( 'IntersectionObserver' in window ) ) {
			// No-op: surface as a visible button for older browsers.
			if ( btn ) { btn.hidden = false; bindButton( wrap ); }
			return;
		}
		var prefersReduced = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
		if ( prefersReduced ) {
			// Never auto-fetch when motion is reduced; show the button.
			if ( btn ) { btn.hidden = false; bindButton( wrap ); }
			return;
		}
		var fetching = false;
		var io = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( e ) {
				if ( ! e.isIntersecting || fetching ) { return; }
				var current = parseInt( wrap.dataset.current || '1', 10 );
				var max     = parseInt( wrap.dataset.max || '1', 10 );
				if ( current >= max ) { io.disconnect(); return; }
				fetching = true;
				setStatus( wrap, cfg.i18n.loading );
				loadNext( wrap, null );
				// Throttle: re-arm after a tick so we don't fire twice.
				setTimeout( function () { fetching = false; }, 1000 );
			} );
		}, { rootMargin: '200px 0px' } );
		io.observe( wrap );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var wraps = $$( '.lwp-loadmore-wrap' );
		if ( ! wraps.length ) { return; }
		// Apply mode marker to the page so CSS hides the woocommerce_pagination.
		document.body.classList.add( 'lwp-loadmore-active' );
		wraps.forEach( function ( w ) {
			if ( cfg.mode === 'infinite' ) {
				bindInfinite( w );
			} else {
				bindButton( w );
			}
		} );
	} );
} )();
