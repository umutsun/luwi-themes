/**
 * PDP gallery — vanilla JS, zero dependencies.
 * Replaces WooCommerce's Flexslider/Zoom/PhotoSwipe stack so we are not
 * at the mercy of LiteSpeed JS Defer/Delay or any other script-blocker.
 *
 * Markup contract (rendered by inc/wc-pdp-gallery-override.php):
 *   [data-lwp-gallery]
 *     .lwp-gallery__main
 *       a.lwp-gallery__slide[data-idx][.is-active] > img
 *     ul.lwp-gallery__thumbs
 *       li.lwp-gallery__thumb[.is-active] > button[data-idx] > img
 *
 * Behaviours:
 *   1. Thumb click          → activate that slide
 *   2. Main slide click     → advance to next slide (no native nav)
 *   3. Arrow Left / Right   → previous / next when gallery has focus
 *   4. Swipe left / right   → previous / next on touch devices
 *
 * Native fallback (when JS doesn't run for any reason): each slide is
 * wrapped in <a href="full-size" target="_blank">, so a click on the
 * first slide still opens the full-size image. Thumbs remain visible
 * but inert — operator still sees there are multiple images.
 */
(function () {
	'use strict';

	function activate(gallery, idx) {
		var slides = gallery.querySelectorAll('.lwp-gallery__slide');
		var thumbs = gallery.querySelectorAll('.lwp-gallery__thumb');
		var max = slides.length;
		if (max === 0) return;

		// Wrap around if out of range.
		idx = ((idx % max) + max) % max;

		for (var i = 0; i < max; i++) {
			if (i === idx) {
				slides[i].classList.add('is-active');
			} else {
				slides[i].classList.remove('is-active');
			}
		}
		for (var t = 0; t < thumbs.length; t++) {
			var btn = thumbs[t].querySelector('button');
			if (t === idx) {
				thumbs[t].classList.add('is-active');
				if (btn) btn.setAttribute('aria-selected', 'true');
			} else {
				thumbs[t].classList.remove('is-active');
				if (btn) btn.setAttribute('aria-selected', 'false');
			}
		}

		// Update :focus to the active thumb's button for screen readers.
		gallery.dataset.activeIdx = String(idx);
	}

	function currentIdx(gallery) {
		var raw = parseInt(gallery.dataset.activeIdx || '0', 10);
		return isNaN(raw) ? 0 : raw;
	}

	function bindGallery(gallery) {
		if (gallery.dataset.lwpGalleryBound === '1') return;
		gallery.dataset.lwpGalleryBound = '1';

		// Thumb click → activate.
		gallery.querySelectorAll('.lwp-gallery__thumb button').forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				var idx = parseInt(btn.dataset.idx, 10);
				if (!isNaN(idx)) activate(gallery, idx);
			});
		});

		// Main slide click → advance (native <a href> is prevented).
		gallery.querySelectorAll('.lwp-gallery__slide').forEach(function (slide) {
			slide.addEventListener('click', function (e) {
				// Allow modifier-click + middle-click to use native <a> (open in new tab).
				if (e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1) return;
				e.preventDefault();
				activate(gallery, currentIdx(gallery) + 1);
			});
		});

		// Keyboard nav — gallery has tabindex=0.
		gallery.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowRight') {
				e.preventDefault();
				activate(gallery, currentIdx(gallery) + 1);
			} else if (e.key === 'ArrowLeft') {
				e.preventDefault();
				activate(gallery, currentIdx(gallery) - 1);
			} else if (e.key === 'Home') {
				e.preventDefault();
				activate(gallery, 0);
			} else if (e.key === 'End') {
				e.preventDefault();
				var slides = gallery.querySelectorAll('.lwp-gallery__slide');
				activate(gallery, slides.length - 1);
			}
		});

		// Touch swipe — main viewer only.
		var main = gallery.querySelector('.lwp-gallery__main');
		if (main) {
			var startX = 0;
			var startY = 0;
			var dx = 0;
			main.addEventListener('touchstart', function (e) {
				if (!e.touches || e.touches.length === 0) return;
				startX = e.touches[0].clientX;
				startY = e.touches[0].clientY;
				dx = 0;
			}, { passive: true });
			main.addEventListener('touchmove', function (e) {
				if (!e.touches || e.touches.length === 0) return;
				dx = e.touches[0].clientX - startX;
			}, { passive: true });
			main.addEventListener('touchend', function () {
				var threshold = 40;
				if (Math.abs(dx) > threshold) {
					if (dx < 0) {
						activate(gallery, currentIdx(gallery) + 1);
					} else {
						activate(gallery, currentIdx(gallery) - 1);
					}
				}
				dx = 0;
			}, { passive: true });
		}

		// Initialise active index from DOM state.
		var initialActive = gallery.querySelector('.lwp-gallery__slide.is-active');
		if (initialActive) {
			var i = parseInt(initialActive.dataset.idx, 10);
			gallery.dataset.activeIdx = String(isNaN(i) ? 0 : i);
		} else {
			gallery.dataset.activeIdx = '0';
		}
	}

	function initAll() {
		document.querySelectorAll('[data-lwp-gallery]').forEach(bindGallery);
	}

	// === WooCommerce tabs switcher ============================================
	// We dequeue `wc-single-product.js` (it depends on jQuery + Flexslider
	// hand-off and gets blocked by LiteSpeed JS Defer anyway). WC's tabs DOM
	// is straightforward enough to re-bind with vanilla JS:
	//   .wc-tabs li.{tab-id}_tab > a   -> click activates that tab
	//   .wc-tab.panel.{tab-id} (or #tab-{id}) -> shown when active
	// WC ships server-side default with the first tab `.active` and the
	// rest hidden via inline `style="display:none"`. We just swap classes
	// + display when an inactive tab's link is clicked.
	function bindTabs(wrap) {
		if (wrap.dataset.lwpTabsBound === '1') return;
		wrap.dataset.lwpTabsBound = '1';

		var tabLinks = wrap.querySelectorAll('.wc-tabs > li > a, ul.tabs > li > a');
		var panels   = wrap.querySelectorAll('.wc-tab, .panel');
		if (tabLinks.length === 0 || panels.length === 0) return;

		function activate(targetId) {
			tabLinks.forEach(function (a) {
				var li = a.parentElement;
				var href = a.getAttribute('href') || '';
				var id = href.replace(/^#/, '');
				if (id === targetId) {
					li.classList.add('active');
				} else {
					li.classList.remove('active');
				}
			});
			panels.forEach(function (p) {
				if (p.id === targetId) {
					p.style.display = '';
					p.classList.add('active');
				} else {
					p.style.display = 'none';
					p.classList.remove('active');
				}
			});
		}

		tabLinks.forEach(function (a) {
			a.addEventListener('click', function (e) {
				e.preventDefault();
				var href = a.getAttribute('href') || '';
				var id = href.replace(/^#/, '');
				if (id) activate(id);
			});
		});

		// Honour initial state — find the first `.active` tab or fall back
		// to the first one. WC server-renders the default tab pre-active.
		var activeLink = wrap.querySelector('.wc-tabs > li.active > a, ul.tabs > li.active > a') || tabLinks[0];
		if (activeLink) {
			var href = activeLink.getAttribute('href') || '';
			var id = href.replace(/^#/, '');
			if (id) activate(id);
		}
	}

	function initTabs() {
		document.querySelectorAll('.woocommerce-tabs').forEach(bindTabs);
	}

	function initAllPdp() {
		initAll();
		initTabs();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAllPdp);
	} else {
		initAllPdp();
	}

	// Expose re-bind hooks for AJAX-injected content (e.g. variation switch).
	window.LuwiGoldPdpGallery = { rebind: initAll };
	window.LuwiGoldPdpTabs    = { rebind: initTabs };
})();
