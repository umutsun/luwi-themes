/**
 * LuwiPress Amber — generic archive Load More / Infinite Scroll.
 *
 * Works for ANY archive (WooCommerce shop, Vendor/Luthier CPT, Event CPT,
 * blog/category/tag, future CPTs). Each `.lwp-loadmore-wrap` describes its own
 * selectors via data attributes, so one script drives every listing:
 *
 *   data-grid        items container to append into   (e.g. ".lwp-people-archive__grid")
 *   data-pagination  pagination block to keep in sync (e.g. ".pagination")
 *   data-next        the "next page" link selector     (e.g. ".pagination a.next")
 *   data-mode        "infinite" (default) | "button"
 *   data-current / data-max   current page / total pages
 *
 * SEO-SAFE BY DESIGN: the real paginated links (/page/2/, ?paged=2) stay in the
 * DOM (only visually hidden via .lwp-loadmore-active in CSS), so search engines
 * crawl every page. The script is pure progressive enhancement over them —
 * it fetches the next page's HTML, appends the items, and swaps the pagination
 * block so the cycle continues.
 *
 * Backward-compatible: a wrap with no data-* selectors falls back to the
 * WooCommerce shop defaults (ul.products / .woocommerce-pagination).
 */
( function () {
	'use strict';

	var cfg = window.LWP_AMBER_LM || { i18n: {}, mode: 'infinite' };
	var I18N = cfg.i18n || {};

	function $( sel, root ) { return ( root || document ).querySelector( sel ); }
	function $$( sel, root ) { return Array.from( ( root || document ).querySelectorAll( sel ) ); }

	// Per-wrap selector resolution (data-* with WooCommerce fallbacks).
	function sel( wrap ) {
		var grid = wrap.getAttribute( 'data-grid' ) || 'ul.products';
		var pag  = wrap.getAttribute( 'data-pagination' ) || '.woocommerce-pagination';
		var next = wrap.getAttribute( 'data-next' ) || ( pag + ' a.next' );
		return { grid: grid, pag: pag, next: next };
	}

	function mode( wrap ) {
		return wrap.getAttribute( 'data-mode' ) || cfg.mode || 'infinite';
	}

	function setStatus( wrap, msg ) {
		var s = wrap.querySelector( '.lwp-loadmore-status' );
		if ( s ) { s.textContent = msg || ''; }
	}

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

	// The next-page URL is simply the href of the live "next" link — that link
	// is the WP-correct URL for the current permalink structure, and it's the
	// crawlable element search engines follow. No manual page math needed.
	function nextUrl( wrap ) {
		var s = sel( wrap );
		var a = $( s.next );
		return ( a && a.href ) ? a.href : null;
	}

	// Symbolic state markers — language-neutral (no per-locale strings):
	//   ''     resting / loading (spinner ring covers the active state)
	//   'X / Y' numeric pagination after each successful append
	//   '✓'    end of list   ·   '⚠' error
	var SYM = { loading: '', done: '✓', error: '⚠' };

	function loadNext( wrap, btn ) {
		var url = nextUrl( wrap );
		if ( ! url ) {
			if ( btn ) { btn.hidden = true; }
			setSpinner( wrap, false );
			setStatus( wrap, SYM.done );
			return;
		}
		var s = sel( wrap );
		if ( btn ) { btn.disabled = true; }
		var origLabel = btn ? btn.textContent : '';
		if ( btn ) { btn.textContent = I18N.loading || 'Loading…'; }
		setStatus( wrap, SYM.loading );
		setSpinner( wrap, true );

		fetch( url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } } )
			.then( function ( r ) {
				if ( ! r.ok ) { throw new Error( 'http ' + r.status ); }
				return r.text();
			} )
			.then( function ( html ) {
				var dom = new DOMParser().parseFromString( html, 'text/html' );

				// Append the next page's items into our grid.
				var newGrid = dom.querySelector( s.grid );
				var ourGrid = $( s.grid );
				if ( newGrid && ourGrid ) {
					Array.from( newGrid.children ).forEach( function ( child ) {
						ourGrid.appendChild( child );
					} );
				}

				// Swap the pagination block so the "next" link advances + stays
				// crawlable (CSS keeps it visually hidden while load-more is active).
				var newPag = dom.querySelector( s.pag );
				var ourPag = $( s.pag );
				if ( newPag && ourPag ) {
					ourPag.innerHTML = newPag.innerHTML;
				} else if ( ! newPag && ourPag ) {
					// No pagination on the fetched page = we just loaded the last one.
					ourPag.innerHTML = '';
				}

				var current = parseInt( wrap.dataset.current || '1', 10 ) + 1;
				var max     = parseInt( wrap.dataset.max || '1', 10 );
				wrap.dataset.current = String( current );
				setSpinner( wrap, false );

				if ( current >= max || ! nextUrl( wrap ) ) {
					if ( btn ) { btn.hidden = true; }
					setStatus( wrap, SYM.done );
				} else {
					if ( btn ) { btn.disabled = false; btn.textContent = origLabel || I18N.load_more || 'Load more'; }
					setStatus( wrap, current + ' / ' + max );
				}

				if ( typeof window.dispatchEvent === 'function' ) {
					window.dispatchEvent( new CustomEvent( 'lwp:loadmore:appended' ) );
				}
			} )
			.catch( function () {
				if ( btn ) { btn.disabled = false; btn.textContent = origLabel || I18N.load_more || 'Load more'; }
				setSpinner( wrap, false );
				setStatus( wrap, SYM.error );
			} );
	}

	function bindButton( wrap ) {
		var btn = wrap.querySelector( '.lwp-loadmore-btn' );
		if ( ! btn ) { return; }
		btn.hidden = false;
		btn.addEventListener( 'click', function () { loadNext( wrap, btn ); } );
	}

	function bindInfinite( wrap ) {
		var btn = wrap.querySelector( '.lwp-loadmore-btn' );
		if ( btn ) { btn.hidden = true; }

		// Fall back to an explicit button when IntersectionObserver is missing
		// or the user prefers reduced motion (never auto-fetch on scroll then).
		var prefersReduced = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
		if ( ! ( 'IntersectionObserver' in window ) || prefersReduced ) {
			bindButton( wrap );
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
				loadNext( wrap, null );
				setTimeout( function () { fetching = false; }, 1000 );
			} );
		}, { rootMargin: '300px 0px' } );
		io.observe( wrap );
	}

	function init() {
		var wraps = $$( '.lwp-loadmore-wrap' );
		if ( ! wraps.length ) { return; }
		document.body.classList.add( 'lwp-loadmore-active' );
		wraps.forEach( function ( w ) {
			if ( mode( w ) === 'infinite' ) {
				bindInfinite( w );
			} else {
				bindButton( w );
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
