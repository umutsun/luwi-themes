/* LuwiPress Amber — AI surface (1.3.1)
 *
 *   1. Search suggestions inside the existing search overlay.
 *
 * Customer chat is owned end-to-end by the LuwiPress core plugin (its own
 * shell, JS, and CSS). The KG-related rail is fully server-rendered and
 * needs no JS.
 *
 * Vanilla JS, single IIFE, defers gracefully when the LuwiPress plugin is
 * inactive (window.lwpGoldAi is missing).
 */
(function () {
	'use strict';

	if ( typeof window.lwpGoldAi === 'undefined' ) {
		return;
	}

	var cfg = window.lwpGoldAi;
	var i18n = cfg.i18n || {};

	/* ───────── helpers ───────── */

	function $( sel, root ) { return ( root || document ).querySelector( sel ); }
	function debounce( fn, wait ) {
		var t;
		return function () {
			var ctx = this, args = arguments;
			clearTimeout( t );
			t = setTimeout( function () { fn.apply( ctx, args ); }, wait );
		};
	}
	function el( tag, cls, html ) {
		var n = document.createElement( tag );
		if ( cls ) n.className = cls;
		if ( html != null ) n.innerHTML = html;
		return n;
	}
	function escapeHtml( s ) {
		return String( s ).replace( /[&<>"']/g, function ( c ) {
			return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[ c ];
		} );
	}

	/* ───────────────────────────────────────────────────────────────── */
	/*  1. Search suggestions                                              */
	/* ───────────────────────────────────────────────────────────────── */

	// Two search-overlay variants ship with the theme: the hardcoded header.php
	// overlay (.lwp-search-panel / #lwp-search-input) and the Elementor
	// search-overlay widget (.lwp-search__panel / [data-lwp-search-input]).
	// $() is querySelector (returns null when absent), so || falls through to
	// whichever variant the active header rendered. Without this, sites using an
	// Elementor theme-builder header got no search suggestions at all.
	var searchPanel = $( '.lwp-search-panel' ) || $( '.lwp-search__panel' );
	var searchInput = $( '#lwp-search-input' ) || $( '[data-lwp-search-input]' );
	var searchForm  = $( '.lwp-search-form' ) || $( '.lwp-search__form' );
	var suggestNode = null;
	var lastQ = '';
	var inFlight = null;

	function ensureSuggestNode() {
		if ( suggestNode ) return suggestNode;
		var tpl = $( '#lwp-amber-search-suggest-template' );
		if ( ! tpl || ! searchPanel ) return null;
		suggestNode = tpl.content.firstElementChild.cloneNode( true );
		searchPanel.appendChild( suggestNode );
		return suggestNode;
	}

	function renderSuggestions( payload ) {
		var node = ensureSuggestNode();
		if ( ! node ) return;
		node.hidden = false;

		var prods = node.querySelector( '.lwp-search-suggest__products' );
		var chips = node.querySelector( '.lwp-search-suggest__chips' );
		var empty = node.querySelector( '.lwp-search-suggest__empty' );

		prods.innerHTML = '';
		chips.innerHTML = '';

		if ( ! payload.products || payload.products.length === 0 ) {
			empty.hidden = false;
		} else {
			empty.hidden = true;
			payload.products.forEach( function ( p ) {
				var card =
					'<a class="lwp-search-suggest__card" href="' + p.url + '" role="listitem">' +
						'<div class="lwp-search-suggest__card-img">' +
							( p.thumb
								? '<img src="' + p.thumb + '" alt="' + escapeHtml( p.title ) + '" loading="lazy" />'
								: '<span class="lwp-skel" aria-hidden="true"></span>' ) +
						'</div>' +
						'<div class="lwp-search-suggest__card-meta">' +
							( p.eyebrow ? '<span class="lwp-eyebrow">— ' + escapeHtml( p.eyebrow ) + '</span>' : '' ) +
							'<h4 class="lwp-search-suggest__card-title">' + escapeHtml( p.title ) + '</h4>' +
							'<span class="lwp-search-suggest__card-price">' + ( p.price_html || '' ) + '</span>' +
						'</div>' +
					'</a>';
				prods.insertAdjacentHTML( 'beforeend', card );
			} );
		}

		if ( payload.suggestions && payload.suggestions.length > 0 ) {
			var label = el( 'span', 'lwp-search-suggest__chips-label', '— ' + ( i18n.tryThese || 'Try these' ) );
			chips.appendChild( label );
			payload.suggestions.forEach( function ( term ) {
				var chip = el( 'button', 'lwp-search-suggest__chip', escapeHtml( term ) );
				chip.type = 'button';
				chip.setAttribute( 'role', 'listitem' );
				chip.addEventListener( 'click', function () {
					if ( searchInput ) {
						searchInput.value = term;
						runSuggest( term );
					}
				} );
				chips.appendChild( chip );
			} );
		}
	}

	function runSuggest( q ) {
		var qt = q.trim();
		if ( qt.length < 2 ) {
			if ( suggestNode ) suggestNode.hidden = true;
			return;
		}
		if ( qt === lastQ ) return;
		lastQ = qt;

		// Cancel previous in-flight request.
		if ( inFlight && typeof inFlight.abort === 'function' ) {
			try { inFlight.abort(); } catch ( e ) {}
		}

		var ctrl = ( typeof AbortController === 'function' ) ? new AbortController() : null;
		inFlight = ctrl;

		fetch( cfg.searchEndpoint, {
			method:  'POST',
			headers: { 'Content-Type': 'application/json' },
			body:    JSON.stringify( { q: qt, limit: 5 } ),
			signal:  ctrl ? ctrl.signal : undefined,
			credentials: 'same-origin'
		} )
			.then( function ( r ) { return r.ok ? r.json() : null; } )
			.then( function ( data ) {
				if ( ! data ) return;
				renderSuggestions( data );
			} )
			.catch( function () { /* aborted or network — silent */ } );
	}

	if ( searchInput ) {
		searchInput.addEventListener( 'input', debounce( function ( e ) {
			runSuggest( e.target.value || '' );
		}, 280 ) );

		// Close suggestions when the overlay closes. Match both the header.php
		// overlay (.lwp-search-close) and the Elementor widget
		// (.lwp-overlay__close / [data-lwp-overlay-close]) close affordances.
		document.addEventListener( 'click', function ( e ) {
			if ( e.target.closest( '.lwp-search-close, .lwp-overlay__close, [data-lwp-overlay-close]' ) ) {
				if ( suggestNode ) suggestNode.hidden = true;
				lastQ = '';
			}
		} );
	}


})();
