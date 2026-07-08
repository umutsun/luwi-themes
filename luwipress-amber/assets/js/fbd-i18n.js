/* Fly by Deniz — flight widgets i18n bridge.
 *
 * The fbd-connect search / booking / confirmation widgets render their UI in
 * vanilla JS with hard-coded English labels. This translates them in place from
 * window.FBD_I18N (built server-side from the gettext catalog in
 * wp-content/languages/plugins/fbd-connect-*.mo), so the single source of truth
 * stays in the .mo files and survives plugin updates. The plugin's own scripts are
 * never modified — we only post-process the DOM they produce.
 *
 * Idempotent: each pass maps English -> localized, and localized strings no longer
 * match the English keys/patterns, so re-running on later mutations is a no-op.
 */
( function () {
	'use strict';

	var D = window.FBD_I18N || {};
	var dict = D.dict || {};
	var has = function ( o, k ) { return Object.prototype.hasOwnProperty.call( o, k ); };
	if ( ! Object.keys( dict ).length && ! D.nonstop ) {
		return; // nothing translated for this locale (likely the source language)
	}

	// ── small helpers ─────────────────────────────────────────────────────────
	function setFull( e ) {
		var s = e.textContent.trim();
		if ( has( dict, s ) ) { e.textContent = dict[ s ]; }
	}
	function setLeadText( e, key ) {
		// Translate only the leading text node, preserving child elements (caret,
		// <strong>, <select>, …). `key` overrides the lookup string when given.
		var n = e.firstChild;
		if ( ! n || 3 !== n.nodeType ) { return; }
		var raw = n.nodeValue;
		var t = raw.trim();
		var src = key || t;
		if ( has( dict, src ) ) {
			// keep any trailing space the original had
			n.nodeValue = dict[ src ] + ( /\s$/.test( raw ) ? ' ' : '' );
		}
	}
	function trStops( s ) {
		if ( s == null ) { return s; }
		var out = String( s );
		if ( D.nonstop ) { out = out.replace( /Non-stop/g, D.nonstop ); }
		out = out.replace( /(\d+)\s+stops?\b/g, function ( m, n ) {
			var word = parseInt( n, 10 ) > 1 ? D.stops : D.stop;
			return word ? n + ' ' + word : m;
		} );
		return out;
	}

	// ── search results ────────────────────────────────────────────────────────
	function localizeSearch( root ) {
		root.querySelectorAll( '.fbd-search-status' ).forEach( function ( e ) {
			var s = e.textContent.trim();
			if ( has( dict, s ) ) { e.textContent = dict[ s ]; return; }
			var m = s.match( /^(\d+)\s+flights, ranked by value$/ );
			if ( m && D.results ) { e.textContent = D.results.replace( '%d', m[ 1 ] ); }
		} );
		root.querySelectorAll(
			'.fbd-score-lbl, .fbd-det-h, .fbd-bar-l, .fbd-det-row > span, .fbd-book-btn'
		).forEach( setFull );
		root.querySelectorAll( '.fbd-det-row > b' ).forEach( function ( e ) {
			var s = e.textContent.trim();
			if ( has( dict, s ) ) { e.textContent = dict[ s ]; return; }
			var t = trStops( s );
			if ( t !== s ) { e.textContent = t; }
		} );
		root.querySelectorAll( '.fbd-tag' ).forEach( function ( e ) {
			setFull( e );
			if ( e.title && has( dict, e.title.trim() ) ) { e.title = dict[ e.title.trim() ]; }
		} );
		root.querySelectorAll( '.fbd-offer-stops' ).forEach( function ( e ) {
			var t = trStops( e.textContent );
			if ( t !== e.textContent ) { e.textContent = t; }
		} );
		root.querySelectorAll( '.fbd-det-toggle' ).forEach( function ( e ) {
			setLeadText( e, 'Flight details' );
		} );
	}

	// ── booking form ──────────────────────────────────────────────────────────
	function localizeBooking( root ) {
		// Legends: "Passenger N" (count) / "Contact".
		root.querySelectorAll( '.fbd-pax-set > legend' ).forEach( function ( l ) {
			var s = l.textContent.trim();
			if ( has( dict, s ) ) { l.textContent = dict[ s ]; return; }
			var m = s.match( /^Passenger\s+(\d+)$/ );
			if ( m && D.passenger ) { l.textContent = D.passenger.replace( '%d', m[ 1 ] ); }
		} );
		// Field labels — translate the leading text node, keep the input/select.
		root.querySelectorAll( '.fbd-grid label' ).forEach( function ( lab ) {
			setLeadText( lab );
		} );
		// Select options (Mr/Ms/Mrs, Male/Female).
		root.querySelectorAll( 'select option' ).forEach( setFull );
		// "Pay <money>" submit button — keep the formatted amount.
		root.querySelectorAll( '.fbd-booking-form button[type=submit]' ).forEach( function ( b ) {
			var s = b.textContent.trim();
			var m = s.match( /^Pay\s+(.+)$/ );
			if ( m && D.pay ) { b.textContent = D.pay.replace( '%s', m[ 1 ] ); }
		} );
		// Notices, live status, requote "Choose".
		root.querySelectorAll( '.fbd-notice, .fbd-booking-status, .fbd-pick' ).forEach( setFull );
	}

	// ── confirmation states ──────────────────────────────────────────────────
	function localizeConfirm( root ) {
		root.querySelectorAll( '.fbd-conf h3, .fbd-notice' ).forEach( setFull );
		root.querySelectorAll( '.fbd-conf p' ).forEach( function ( p ) {
			// "Booking reference: <strong>PNR</strong>" — keep the PNR.
			if ( p.querySelector( 'strong' ) ) {
				setLeadText( p, 'Booking reference:' );
				return;
			}
			var s = p.textContent.trim();
			if ( has( dict, s ) ) { p.textContent = dict[ s ]; return; }
			var m = s.match( /^Reference:\s*(.+)$/ );
			if ( m && D.reference ) { p.textContent = D.reference.replace( '%s', m[ 1 ] ); }
		} );
	}

	// ── wiring ────────────────────────────────────────────────────────────────
	function localize( root ) {
		if ( root.classList.contains( 'fbd-flight-search' ) ) { localizeSearch( root ); }
		else if ( root.classList.contains( 'fbd-flight-booking' ) ) { localizeBooking( root ); }
		else if ( root.classList.contains( 'fbd-flight-confirmation' ) ) { localizeConfirm( root ); }
	}

	function debounce( fn, ms ) {
		var t;
		return function () { clearTimeout( t ); t = setTimeout( fn, ms ); };
	}
	function wire( root ) {
		var opts = { childList: true, subtree: true };
		var obs = new MutationObserver(
			debounce( function () {
				obs.disconnect();
				try { localize( root ); } finally { obs.observe( root, opts ); }
			}, 50 )
		);
		localize( root );
		obs.observe( root, opts );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		document.querySelectorAll(
			'.fbd-flight-search, .fbd-flight-booking, .fbd-flight-confirmation'
		).forEach( wire );
	} );
}() );
