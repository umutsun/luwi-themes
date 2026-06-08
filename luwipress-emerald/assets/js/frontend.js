/**
 * LuwiPress Emerald — frontend.js
 *
 * Bundled chrome behaviour ported from the Acme/Emerald design
 * _script.html, extended with:
 *   - Mobile nav drawer (left-slide) toggled from the hamburger.
 *   - Cart drawer integration with WooCommerce's
 *     `added_to_cart` / `removed_from_cart` events so the drawer
 *     opens on add-to-cart.
 *   - Header scroll-shrink hysteresis tolerance for elastic scrolling.
 *
 * Self-contained IIFE, no jQuery, no globals.
 */
(function () {
	'use strict';

	// ------------------------------------------------------------------
	// Header scroll-shrink
	// ------------------------------------------------------------------
	var hdr = document.getElementById('siteHeader');
	if (hdr) {
		var onScroll = function () {
			var y = window.pageYOffset || document.documentElement.scrollTop;
			if (y > 12) hdr.classList.add('is-scrolled');
			else hdr.classList.remove('is-scrolled');
		};
		window.addEventListener('scroll', onScroll, { passive: true });
		onScroll();
	}

	// ------------------------------------------------------------------
	// Cart drawer (WooCommerce)
	// ------------------------------------------------------------------
	var cartDrawer = document.getElementById('cartDrawer');
	var cartTrigger = document.getElementById('cartTrigger');
	function openCart() {
		if (!cartDrawer) return;
		cartDrawer.setAttribute('aria-hidden', 'false');
		document.body.style.overflow = 'hidden';
		var firstFocusable = cartDrawer.querySelector('[data-cart-close], a, button');
		if (firstFocusable) firstFocusable.focus();
	}
	function closeCart() {
		if (!cartDrawer) return;
		cartDrawer.setAttribute('aria-hidden', 'true');
		document.body.style.overflow = '';
		if (cartTrigger) cartTrigger.focus();
	}
	if (cartTrigger) cartTrigger.addEventListener('click', openCart);
	document.querySelectorAll('[data-cart-close]').forEach(function (b) {
		b.addEventListener('click', closeCart);
	});

	// WC events — open drawer when an item is added.
	if (typeof jQuery !== 'undefined') {
		jQuery(document.body)
			.on('added_to_cart', function () { openCart(); })
			.on('wc_fragments_refreshed wc_fragments_loaded', function () {
				// noop — fragments handler updates the cart-count and
				// subtotal spans automatically via the fragments filter.
			});
	}

	// ------------------------------------------------------------------
	// Mobile nav drawer
	// ------------------------------------------------------------------
	var navDrawer = document.getElementById('navDrawer');
	var navTrigger = document.getElementById('navTrigger');
	function openNav() {
		if (!navDrawer) return;
		navDrawer.setAttribute('aria-hidden', 'false');
		if (navTrigger) navTrigger.setAttribute('aria-expanded', 'true');
		document.body.style.overflow = 'hidden';
		var firstLink = navDrawer.querySelector('a');
		if (firstLink) firstLink.focus();
	}
	function closeNav() {
		if (!navDrawer) return;
		navDrawer.setAttribute('aria-hidden', 'true');
		if (navTrigger) navTrigger.setAttribute('aria-expanded', 'false');
		document.body.style.overflow = '';
		if (navTrigger) navTrigger.focus();
	}
	if (navTrigger) navTrigger.addEventListener('click', openNav);
	document.querySelectorAll('[data-nav-close]').forEach(function (b) {
		b.addEventListener('click', closeNav);
	});

	// Esc closes whichever drawer is open.
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') {
			if (cartDrawer && cartDrawer.getAttribute('aria-hidden') === 'false') closeCart();
			if (navDrawer && navDrawer.getAttribute('aria-hidden') === 'false') closeNav();
		}
	});

	// ------------------------------------------------------------------
	// Reveal-on-scroll
	// ------------------------------------------------------------------
	var revealEls = document.querySelectorAll('.emerald-reveal, .emerald-stagger');
	if (revealEls.length && 'IntersectionObserver' in window) {
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					entry.target.classList.add('is-revealed');
					io.unobserve(entry.target);
				}
			});
		}, { rootMargin: '0px 0px -8% 0px', threshold: 0 });
		revealEls.forEach(function (el) { io.observe(el); });
		// Safety net — if IntersectionObserver fails for any reason,
		// reveal everything after 1.5s so content never stays hidden.
		setTimeout(function () {
			revealEls.forEach(function (el) { el.classList.add('is-revealed'); });
		}, 1500);
	} else {
		revealEls.forEach(function (el) { el.classList.add('is-revealed'); });
	}

	// ------------------------------------------------------------------
	// Search modal (light) — placeholder. Wires to LuwiPress AI search
	// when the companion plugin is active; otherwise falls back to a
	// quick redirect to /?s=...
	// ------------------------------------------------------------------
	var searchTrigger = document.getElementById('searchTrigger');
	if (searchTrigger) {
		searchTrigger.addEventListener('click', function () {
			// If the LuwiPress AI search modal is present, let it handle the click.
			if (window.LuwiPress && typeof window.LuwiPress.openSearch === 'function') {
				window.LuwiPress.openSearch();
				return;
			}
			var q = window.prompt('Search:');
			if (q && q.trim()) {
				var origin = window.location.origin;
				window.location.href = origin + '/?s=' + encodeURIComponent(q.trim());
			}
		});
	}

	// ------------------------------------------------------------------
	// Counter — animated number for any `.emerald-counter[data-target]`
	// ------------------------------------------------------------------
	var counters = document.querySelectorAll('.emerald-counter[data-target]');
	if (counters.length && 'IntersectionObserver' in window) {
		var countIO = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (!entry.isIntersecting) return;
				var el = entry.target;
				var target = parseFloat(el.getAttribute('data-target')) || 0;
				var duration = parseInt(el.getAttribute('data-duration') || '1200', 10);
				var startTime = null;
				function tick(ts) {
					if (!startTime) startTime = ts;
					var p = Math.min(1, (ts - startTime) / duration);
					var ease = 1 - Math.pow(1 - p, 3); // easeOutCubic
					el.textContent = Math.round(target * ease).toLocaleString();
					if (p < 1) requestAnimationFrame(tick);
					else el.textContent = target.toLocaleString();
				}
				requestAnimationFrame(tick);
				countIO.unobserve(el);
			});
		}, { threshold: 0.3 });
		counters.forEach(function (el) { countIO.observe(el); });
	}
})();
