/* ===========================================================
   Fly By Deniz — Amber  ·  shared site chrome JS
   Null-guarded so it can load on EVERY page (home + subpages)
   without throwing when a section is absent.
   =========================================================== */
(function () {
  'use strict';
  var $  = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  /* ---------- mobile drawer ---------- */
  var burger  = $('#burger');
  var drawer  = $('#drawer');
  var overlay = $('#drawerOverlay');
  var dClose  = $('#drawerClose');
  function openDrawer() { if (!drawer) return; drawer.classList.add('open'); overlay && overlay.classList.add('open'); document.body.style.overflow = 'hidden'; }
  function closeDrawer() { if (!drawer) return; drawer.classList.remove('open'); overlay && overlay.classList.remove('open'); document.body.style.overflow = ''; }
  burger  && burger.addEventListener('click', openDrawer);
  overlay && overlay.addEventListener('click', closeDrawer);
  dClose  && dClose.addEventListener('click', closeDrawer);
  drawer  && $$('a', drawer).forEach(function (a) { a.addEventListener('click', closeDrawer); });

  /* ---------- drawer accordions ---------- */
  drawer && $$('[data-acc]', drawer).forEach(function (btn) {
    btn.addEventListener('click', function () {
      var acc = btn.closest('.d-acc');
      var body = acc.querySelector('.d-acc-body');
      var isOpen = acc.classList.contains('open');
      $$('.d-acc', drawer).forEach(function (a) { a.classList.remove('open'); a.querySelector('.d-acc-body').style.maxHeight = null; });
      if (!isOpen) { acc.classList.add('open'); body.style.maxHeight = body.scrollHeight + 'px'; }
    });
  });

  /* ---------- theme toggles (header + drawer) ---------- */
  function toggleTheme() {
    var cur = document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
    var nx = cur === 'light' ? 'dark' : 'light';
    if (window.__amberApplyMode) { window.__amberApplyMode(nx); }
    else { document.documentElement.setAttribute('data-theme', nx); try { localStorage.setItem('amber-theme', nx); } catch (e) {} }
    var tr = $('#tweaks-root');
    if (window.__amberApplyMode && tr && tr.children.length > 0) window.__amberApplyMode(nx);
  }
  var hTheme = $('#headerTheme'); hTheme && hTheme.addEventListener('click', toggleTheme);
  var dTheme = $('#drawerTheme'); dTheme && dTheme.addEventListener('click', toggleTheme);

  /* ---------- FAQ accordion ---------- */
  $$('.faq-item').forEach(function (item) {
    var q = item.querySelector('.faq-q');
    var a = item.querySelector('.faq-a');
    if (!q || !a) return;
    if (item.classList.contains('open')) a.style.maxHeight = a.scrollHeight + 'px';
    q.addEventListener('click', function () {
      var isOpen = item.classList.contains('open');
      $$('.faq-item').forEach(function (i) { i.classList.remove('open'); var ia = i.querySelector('.faq-a'); if (ia) ia.style.maxHeight = null; });
      if (!isOpen) { item.classList.add('open'); a.style.maxHeight = a.scrollHeight + 'px'; }
    });
  });

  /* ---------- newsletter / generic non-wired forms (prevent reload) ---------- */
  $$('form[data-demo]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      e.preventDefault();
      var note = f.querySelector('[data-form-note]');
      if (note) { note.style.display = 'block'; }
      f.reset && f.reset();
    });
  });
  $$('.news-input button').forEach(function (b) {
    b.addEventListener('click', function (e) { e.preventDefault(); b.classList.add('sent'); });
  });

  /* ---------- mega menu (re-parent to body, fixed-position, clamped) ---------- */
  var header = $('#header');
  var megaItems = $$('.has-mega');
  function posMega(m) {
    var panel = m._panel; if (!panel || !header) return;
    var tr = m.getBoundingClientRect();
    var hd = header.getBoundingClientRect();
    var margin = 16, pw = panel.offsetWidth;
    var left = tr.left + tr.width / 2 - pw / 2;
    if (left + pw > window.innerWidth - margin) left = window.innerWidth - margin - pw;
    if (left < margin) left = margin;
    panel.style.left = Math.round(left) + 'px';
    panel.style.top = Math.round(hd.bottom) + 'px';
  }
  megaItems.forEach(function (m) {
    var panel = m.querySelector('.mega');
    if (panel) { document.body.appendChild(panel); m._panel = panel; }
    var t;
    var open = function () {
      clearTimeout(t);
      megaItems.forEach(function (o) { if (o !== m) { o.classList.remove('open'); o._panel && o._panel.classList.remove('open'); } });
      posMega(m); m.classList.add('open'); panel && panel.classList.add('open');
    };
    var close = function () { t = setTimeout(function () { m.classList.remove('open'); panel && panel.classList.remove('open'); }, 150); };
    m.addEventListener('mouseenter', open);
    m.addEventListener('mouseleave', close);
    if (panel) { panel.addEventListener('mouseenter', function () { clearTimeout(t); }); panel.addEventListener('mouseleave', close); }
  });
  window.addEventListener('resize', function () { megaItems.forEach(function (m) { if (m.classList.contains('open')) posMega(m); }); });

  /* ---------- activities horizontal scroller (home only) ---------- */
  var scroller = $('#actScroller');
  if (scroller) {
    var step = function () { return Math.min(scroller.clientWidth * 0.85, 500); };
    var nextB = $('#actNext'), prevB = $('#actPrev');
    nextB && nextB.addEventListener('click', function () { scroller.scrollBy({ left: step(), behavior: 'smooth' }); });
    prevB && prevB.addEventListener('click', function () { scroller.scrollBy({ left: -step(), behavior: 'smooth' }); });
  }

  /* ---------- testimonials (home only) ---------- */
  var slides = $$('.testi-slide');
  if (slides.length) {
    var dots = $$('.testi-dots button');
    var cur = 0;
    var go = function (n) { cur = n; slides.forEach(function (s, i) { s.classList.toggle('active', i === n); }); dots.forEach(function (d, i) { d.classList.toggle('active', i === n); }); };
    dots.forEach(function (d) { d.addEventListener('click', function () { go(+d.dataset.slide); }); });
    setInterval(function () { go((cur + 1) % slides.length); }, 7000);
  }

  /* ---------- hero video autoplay ---------- */
  var v = $('.scene-video');
  if (v) {
    v.muted = true; v.defaultMuted = true;
    var tryPlay = function () { var p = v.play && v.play(); if (p && p.catch) p.catch(function () {}); };
    tryPlay();
    v.addEventListener('canplay', tryPlay, { once: true });
    document.addEventListener('visibilitychange', function () { if (!document.hidden) tryPlay(); });
  }
})();
