/* LuwiPress Gold — frontend animation layer.
 *
 * Page loader + scroll-reveal + WooCommerce cart bump. Vanilla JS, no jQuery.
 * Respects prefers-reduced-motion (everything no-ops).
 *
 * Source: 00-animations.json (Claude Design handoff bundle).
 */
(function () {
	'use strict';

	var prefersReduced = window.matchMedia &&
		window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	if (prefersReduced) return;

	/* ─── 1. Page loader ─── */
	function buildLoader() {
		// Skip on Elementor edit screens.
		if (document.body.classList.contains('elementor-editor-active')) return;
		if (document.querySelector('.lwp-loader')) return;

		var loader = document.createElement('div');
		loader.className = 'lwp-loader';
		loader.setAttribute('aria-hidden', 'true');
		loader.innerHTML =
			'<div class="lwp-loader__mark">T</div>' +
			'<div class="lwp-loader__bar"><span></span></div>';
		document.body.appendChild(loader);

		var styles = document.createElement('style');
		styles.textContent =
			'.lwp-loader{position:fixed;inset:0;z-index:99999;background:#fcf9f8;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:32px;transition:opacity .45s ease,visibility .45s ease}' +
			'.lwp-loader.lwp-loader--gone{opacity:0;visibility:hidden;pointer-events:none}' +
			'.lwp-loader__mark{font-family:"Playfair Display",Georgia,serif;font-size:64px;font-weight:500;color:#735c00;font-style:italic;line-height:1}' +
			'.lwp-loader__bar{width:200px;height:2px;background:#e8e2d3;border-radius:2px;overflow:hidden}' +
			'.lwp-loader__bar span{display:block;height:100%;width:0;background:linear-gradient(90deg,#735c00,#D4AF37);transition:width .8s cubic-bezier(.2,.8,.2,1)}';
		document.head.appendChild(styles);

		// Fill the bar to 70 % immediately, finish on window load.
		requestAnimationFrame(function () {
			var fill = loader.querySelector('.lwp-loader__bar span');
			if (fill) fill.style.width = '70%';
		});

		var hide = function () {
			var fill = loader.querySelector('.lwp-loader__bar span');
			if (fill) fill.style.width = '100%';
			setTimeout(function () {
				loader.classList.add('lwp-loader--gone');
				setTimeout(function () {
					loader.parentNode && loader.parentNode.removeChild(loader);
				}, 600);
			}, 200);
		};

		if (document.readyState === 'complete') {
			hide();
		} else {
			window.addEventListener('load', hide);
			// Hard cap so the loader never traps the page.
			setTimeout(hide, 4000);
		}
	}

	/* ─── 2. Scroll reveal (data-lwp-reveal attribute) ─── */
	function setupReveal() {
		var els = document.querySelectorAll('[data-lwp-reveal]');
		if (!els.length || !('IntersectionObserver' in window)) return;

		var styles = document.createElement('style');
		styles.textContent =
			'[data-lwp-reveal]{opacity:0;transform:translateY(24px);transition:opacity .7s cubic-bezier(.2,.8,.2,1),transform .7s cubic-bezier(.2,.8,.2,1)}' +
			'[data-lwp-reveal="left"]{transform:translateX(-32px)}' +
			'[data-lwp-reveal="right"]{transform:translateX(32px)}' +
			'[data-lwp-reveal="scale"]{transform:scale(.94)}' +
			'[data-lwp-reveal].lwp-revealed{opacity:1;transform:none}';
		document.head.appendChild(styles);

		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (e) {
				if (!e.isIntersecting) return;
				e.target.classList.add('lwp-revealed');
				io.unobserve(e.target);
			});
		}, { rootMargin: '0px 0px -10% 0px', threshold: 0.05 });

		els.forEach(function (el) { io.observe(el); });
	}

	/* ─── 3. Stagger grid (data-lwp-stagger on parent) ─── */
	function setupStagger() {
		var grids = document.querySelectorAll('[data-lwp-stagger]');
		grids.forEach(function (grid) {
			var children = grid.children;
			for (var i = 0; i < children.length; i++) {
				children[i].style.transitionDelay = (i * 60) + 'ms';
				children[i].setAttribute('data-lwp-reveal', '');
			}
		});
	}

	/* ─── 4. Cart bump on add-to-cart (WooCommerce hook) ─── */
	function bumpCart() {
		var icons = document.querySelectorAll(
			'[data-lwp-cart-toggle], .luwipress-gold-cart, .menu-cart-trigger, .elementor-menu-cart__toggle'
		);
		icons.forEach(function (el) {
			el.classList.remove('lwp-bump');
			void el.offsetWidth;
			el.classList.add('lwp-bump');
		});
	}
	function setupCartBump() {
		var styles = document.createElement('style');
		styles.textContent =
			'@keyframes lwpCartBump{0%{transform:scale(1)}30%{transform:scale(1.18)}60%{transform:scale(.92)}100%{transform:scale(1)}}' +
			'.lwp-bump{animation:lwpCartBump .42s ease}';
		document.head.appendChild(styles);

		// jQuery-WooCommerce events.
		if (window.jQuery) {
			window.jQuery(document.body).on(
				'added_to_cart removed_from_cart wc_fragments_refreshed',
				bumpCart
			);
		}
		// Native WC pub/sub.
		document.addEventListener('wc_added_to_cart', bumpCart);
	}

	/* ─── boot ─── */
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
	function boot() {
		buildLoader();
		setupStagger();
		setupReveal();
		setupCartBump();
	}
})();
