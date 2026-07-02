/* ===========================================================
   ArshaHomes — "Onyx"  ·  shared site interactions
   Quiet luxury, after dark. Vanilla JS, null-guarded so every
   block is safe to load on every page. Ported from the React
   design references (onyx-*.jsx) to a single enqueued script.
   =========================================================== */
(function () {
  'use strict';
  var $  = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- theme toggle (header + drawer) ---------- */
  function currentTheme() {
    return document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
  }
  function applyTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    try { localStorage.setItem('onyx_theme', t); } catch (e) {}
  }
  function toggleTheme() { applyTheme(currentTheme() === 'light' ? 'dark' : 'light'); }
  $$('.theme-toggle').forEach(function (b) { b.addEventListener('click', toggleTheme); });

  /* ---------- sticky header shadow ---------- */
  var hdr = $('.hdr');
  if (hdr) {
    var onStick = function () { hdr.classList.toggle('hdr--stuck', window.scrollY > 40); };
    onStick();
    window.addEventListener('scroll', onStick, { passive: true });
  }

  /* ---------- mobile drawer ---------- */
  var burger = $('.hdr-burger');
  var drawer = $('.drawer');
  var dScrim = $('.drawer-scrim');
  var dClose = $('.drawer-close');
  function openDrawer() { if (!drawer) return; drawer.classList.add('open'); dScrim && dScrim.classList.add('open'); document.body.style.overflow = 'hidden'; }
  function closeDrawer() { if (!drawer) return; drawer.classList.remove('open'); dScrim && dScrim.classList.remove('open'); document.body.style.overflow = ''; }
  burger && burger.addEventListener('click', openDrawer);
  dScrim && dScrim.addEventListener('click', closeDrawer);
  dClose && dClose.addEventListener('click', closeDrawer);
  drawer && $$('.drawer-nav a, .drawer-chips a', drawer).forEach(function (a) { a.addEventListener('click', closeDrawer); });

  /* ---------- mega menu (hover, 110ms close delay) ---------- */
  var navItem = $('.nav-item');
  var megaScrim = $('.mega-scrim');
  if (navItem) {
    var mega = navItem.querySelector('.mega');
    var t = null;
    var openMega = function () { clearTimeout(t); navItem.classList.add('open'); mega && mega.classList.add('open'); megaScrim && megaScrim.classList.add('open'); };
    var closeMega = function () { t = setTimeout(function () { navItem.classList.remove('open'); mega && mega.classList.remove('open'); megaScrim && megaScrim.classList.remove('open'); }, 110); };
    navItem.addEventListener('mouseenter', openMega);
    navItem.addEventListener('mouseleave', closeMega);
    mega && mega.addEventListener('mouseenter', function () { clearTimeout(t); });
    mega && mega.addEventListener('mouseleave', closeMega);
    var hdrEl = $('.hdr');
    hdrEl && hdrEl.addEventListener('mouseleave', closeMega);
  }

  /* ---------- reveal on scroll ---------- */
  (function () {
    var els = $$('.reveal');
    if (!els.length) return;
    if (reduceMotion || !('IntersectionObserver' in window)) { els.forEach(function (e) { e.classList.add('in'); }); return; }
    var vh = window.innerHeight || 0;
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
    }, { threshold: 0.08 });
    els.forEach(function (el) {
      var r = el.getBoundingClientRect();
      if (r.top >= vh * 0.92) { el.classList.add('before'); io.observe(el); }
      else { el.classList.add('in'); }
    });
  })();

  /* ---------- FAQ accordion (single open) ---------- */
  (function () {
    var items = $$('.faq-item');
    if (!items.length) return;
    items.forEach(function (item) {
      var q = item.querySelector('.faq-q');
      if (!q) return;
      q.addEventListener('click', function () {
        var isOpen = item.classList.contains('open');
        items.forEach(function (i) { i.classList.remove('open'); });
        if (!isOpen) item.classList.add('open');
      });
    });
  })();

  /* ---------- hero slider (variant A, optional) ---------- */
  (function () {
    var slides = $$('.hero-slide');
    var dots = $$('.hero-dots button');
    if (slides.length < 2) return;
    var cur = 0;
    var go = function (n) { cur = (n + slides.length) % slides.length; slides.forEach(function (s, i) { s.classList.toggle('on', i === cur); }); dots.forEach(function (d, i) { d.classList.toggle('on', i === cur); }); };
    dots.forEach(function (d, i) { d.addEventListener('click', function () { go(i); }); });
    go(0);
    if (!reduceMotion) setInterval(function () { go(cur + 1); }, 6500);
  })();

  /* ---------- testimonials carousel ---------- */
  (function () {
    var slides = $$('.tst-slide');
    var dots = $$('.tst-dots button');
    if (!slides.length) return;
    var cur = 0;
    var go = function (n) { cur = (n + slides.length) % slides.length; slides.forEach(function (s, i) { s.classList.toggle('on', i === cur); }); dots.forEach(function (d, i) { d.classList.toggle('on', i === cur); }); };
    dots.forEach(function (d, i) { d.addEventListener('click', function () { go(i); }); });
    go(0);
    if (!reduceMotion) setInterval(function () { go(cur + 1); }, 7000);
  })();

  /* ---------- count-up stats ---------- */
  (function () {
    var nums = $$('[data-countup]');
    if (!nums.length) return;
    if (reduceMotion || !('IntersectionObserver' in window)) { nums.forEach(function (n) { n.textContent = (+n.dataset.countup).toLocaleString('en-US'); }); return; }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        var el = en.target, target = +el.dataset.countup, dur = 1500, start = null;
        var ease = function (t) { return 1 - Math.pow(1 - t, 3); };
        var tick = function (ts) { if (!start) start = ts; var p = Math.min((ts - start) / dur, 1); el.textContent = Math.round(ease(p) * target).toLocaleString('en-US'); if (p < 1) requestAnimationFrame(tick); };
        requestAnimationFrame(tick);
        io.unobserve(el);
      });
    }, { threshold: 0.4 });
    nums.forEach(function (n) { io.observe(n); });
  })();

  /* ---------- apartment plan tabs (home) ---------- */
  (function () {
    var tabs = $$('.plans-tabs button');
    if (!tabs.length) return;
    var panels = {};
    $$('.plans-panel').forEach(function (p) { panels[p.dataset.plan] = p; });
    var activate = function (key) {
      tabs.forEach(function (b) { b.classList.toggle('on', b.dataset.plan === key); });
      Object.keys(panels).forEach(function (k) { panels[k].hidden = (k !== key); });
    };
    tabs.forEach(function (b) { b.addEventListener('click', function () { activate(b.dataset.plan); }); });
  })();

  /* ---------- generic non-wired forms (prevent reload demo) ---------- */
  $$('form[data-demo]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      e.preventDefault();
      var note = f.querySelector('[data-form-note]');
      if (note) note.style.display = 'block';
      f.reset && f.reset();
    });
  });

  /* ===========================================================
     LISTINGS  ·  client-rendered from JSON data island
     =========================================================== */
  (function () {
    var root = $('[data-onyx-listings]');
    var dataEl = $('#onyx-listings-data');
    if (!root || !dataEl) return;

    var DATA;
    try { DATA = JSON.parse(dataEl.textContent); } catch (e) { return; }

    var PMIN = 1000000, PMAX = 35000000, PAGE = 6;
    var state = { types: [], locs: [], beds: 'Any', status: 'Any', min: PMIN, max: PMAX, sort: 'featured', view: 'grid', page: 1, favs: {} };

    var resultsEl = $('[data-listings-results]', root);
    var countEl   = $('[data-listings-count]', root);
    var pagerEl   = $('[data-listings-pager]', root);

    function fmt(n) { if (n >= 1000000) { var m = n / 1000000; return 'AED ' + (m % 1 === 0 ? m : m.toFixed(1)) + 'M'; } return 'AED ' + n.toLocaleString('en-US'); }
    function fmtM(n) { var m = n / 1000000; return (m % 1 === 0 ? m : m.toFixed(1)) + 'M'; }
    function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]; }); }
    var ICON = {
      pin: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-6.4 7-11a7 7 0 1 0-14 0c0 4.6 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>',
      bed: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6h18v6"/><path d="M3 14V8h7v4"/><path d="M21 12V9a2 2 0 0 0-2-2h-7"/><path d="M3 18v2M21 18v2"/></svg>',
      ruler: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="8" width="18" height="8" rx="1"/><path d="M7 8v3M11 8v4M15 8v3M19 8v4"/></svg>',
      car: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 15l1.5-5h11L19 15"/><rect x="3" y="15" width="18" height="4" rx="1"/><circle cx="7" cy="19" r="1"/><circle cx="17" cy="19" r="1"/></svg>',
      heart: '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20s-7-4.6-9.2-9C1.3 8 2.8 5 6 5c2 0 3.2 1.4 4 2.6C10.8 6.4 12 5 14 5c3.2 0 4.7 3 3.2 6-2.2 4.4-9.2 9-9.2 9z"/></svg>',
      arrowUR: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17L17 7"/><path d="M8 7h9v9"/></svg>',
      search: '<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>'
    };
    function phMedia(glyph) {
      var inner = glyph === 'tower'
        ? '<path d="M20 44V14l4-6 4 6v30M16 44V22h16M14 44h20M22 18h4M22 24h4M22 30h4M22 36h4"/>'
        : glyph === 'interior'
          ? '<path d="M6 30h36M10 30V18a4 4 0 0 1 4-4h20a4 4 0 0 1 4 4v12M14 30v6M34 30v6M14 22h8v8M26 22h8"/>'
          : '<rect x="8" y="8" width="32" height="32"/><path d="M8 20h20M28 8v20M28 28h12M20 28v12"/>';
      return '<div class="ph"><div class="ph-glyph"><svg width="56" height="56" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">' + inner + '</svg></div></div>';
    }
    var href = root.getAttribute('data-property-url') || '#';

    function gridCard(p) {
      var fav = state.favs[p.name];
      var beds = p.beds === 0 ? 'Studio' : p.beds + ' Bed';
      return '<article class="pcard" style="background:var(--onyx-900)">' +
        '<div class="pcard-media"><span class="pcard-cat">' + esc(p.cat) + '</span>' +
        '<button class="fav' + (fav ? ' on' : '') + '" data-fav="' + esc(p.name) + '" aria-label="Save">' + ICON.heart + '</button>' + phMedia(p.glyph) + '</div>' +
        '<a href="' + (p.url || href) + '" style="display:block;color:inherit"><div class="pcard-body">' +
        '<div class="pcard-loc"><span class="ic">' + ICON.pin + '</span>' + esc(p.loc) + '</div>' +
        '<h3>' + esc(p.name) + '</h3>' +
        '<div class="pcard-meta"><span><span class="ic">' + ICON.bed + '</span>' + esc(beds) + '</span><span><span class="ic">' + ICON.ruler + '</span>' + p.area + ' m²</span></div>' +
        '<div class="pcard-foot"><div class="pcard-price"><small>Price from</small>' + fmt(p.price) + '</div><span class="pcard-arrow">' + ICON.arrowUR + '</span></div>' +
        '</div></a></article>';
    }
    function listCard(p) {
      var fav = state.favs[p.name];
      var beds = p.beds === 0 ? 'Studio' : p.beds + ' Bed';
      var park = Math.max(1, Math.round(p.beds / 2));
      return '<div class="lcard">' +
        '<div class="lcard-media"><span class="lcard-cat">' + esc(p.cat) + '</span>' +
        '<button class="fav' + (fav ? ' on' : '') + '" data-fav="' + esc(p.name) + '" aria-label="Save">' + ICON.heart + '</button>' + phMedia(p.glyph) + '</div>' +
        '<a class="lcard-body" href="' + (p.url || href) + '">' +
        '<div class="lcard-loc"><span class="ic">' + ICON.pin + '</span>' + esc(p.loc) + ' · ' + esc(p.status) + '</div>' +
        '<h3>' + esc(p.name) + '</h3><p class="lcard-desc">' + esc(p.desc || '') + '</p>' +
        '<div class="lcard-meta"><span><span class="ic">' + ICON.bed + '</span>' + esc(beds) + '</span><span><span class="ic">' + ICON.ruler + '</span>' + p.area + ' m²</span><span><span class="ic">' + ICON.car + '</span>' + park + ' Parking</span></div>' +
        '<div class="lcard-foot"><div class="lcard-price"><small>Price from</small>' + fmt(p.price) + '</div><span class="pcard-arrow">' + ICON.arrowUR + '</span></div>' +
        '</a></div>';
    }

    function filtered() {
      var r = DATA.filter(function (p) {
        return (state.types.length === 0 || state.types.indexOf(p.cat) > -1) &&
          (state.locs.length === 0 || state.locs.indexOf(p.loc) > -1) &&
          (state.status === 'Any' || p.status === state.status) &&
          (state.beds === 'Any' || (state.beds === '4' ? p.beds >= 4 : p.beds === +state.beds)) &&
          p.price >= state.min && p.price <= state.max;
      });
      var s = { 'price-asc': function (a, b) { return a.price - b.price; }, 'price-desc': function (a, b) { return b.price - a.price; }, 'area-desc': function (a, b) { return b.area - a.area; } };
      if (s[state.sort]) r = r.slice().sort(s[state.sort]);
      return r;
    }

    function render() {
      var r = filtered();
      var pages = Math.max(1, Math.ceil(r.length / PAGE));
      if (state.page > pages) state.page = pages;
      var shown = r.slice((state.page - 1) * PAGE, state.page * PAGE);

      if (countEl) countEl.innerHTML = '<b>' + r.length + '</b> residence' + (r.length !== 1 ? 's' : '');

      if (!shown.length) {
        resultsEl.className = 'empty';
        resultsEl.innerHTML = '<span class="ic">' + ICON.search + '</span><h3>No residences match</h3><p>Try widening your price range or clearing a filter.</p><button class="btn btn-ghost" data-listings-reset style="margin-top:24px">Reset filters</button>';
      } else {
        resultsEl.className = 'results ' + state.view;
        resultsEl.innerHTML = shown.map(function (p, k) {
          return '<div class="rcard-wrap" style="animation-delay:' + (k * 55) + 'ms">' + (state.view === 'list' ? listCard(p) : gridCard(p)) + '</div>';
        }).join('');
      }

      if (pagerEl) {
        if (pages < 2) { pagerEl.innerHTML = ''; }
        else {
          var html = '<button data-pg="' + (state.page - 1) + '"' + (state.page === 1 ? ' disabled' : '') + ' aria-label="Previous">‹</button>';
          for (var n = 1; n <= pages; n++) html += '<button data-pg="' + n + '"' + (n === state.page ? ' class="on"' : '') + '>' + n + '</button>';
          html += '<button data-pg="' + (state.page + 1) + '"' + (state.page === pages ? ' disabled' : '') + ' aria-label="Next">›</button>';
          pagerEl.innerHTML = html;
        }
      }
    }

    // --- filter controls wiring ---
    function toggleArr(arr, v) { var i = arr.indexOf(v); if (i > -1) arr.splice(i, 1); else arr.push(v); }

    $$('[data-f-type]', root).forEach(function (el) {
      el.addEventListener('click', function () { toggleArr(state.types, el.dataset.fType); el.classList.toggle('on'); state.page = 1; render(); });
    });
    $$('[data-f-loc]', root).forEach(function (el) {
      el.addEventListener('click', function () { toggleArr(state.locs, el.dataset.fLoc); el.classList.toggle('on'); state.page = 1; render(); });
    });
    $$('[data-f-beds]', root).forEach(function (el) {
      el.addEventListener('click', function () { state.beds = el.dataset.fBeds; $$('[data-f-beds]', root).forEach(function (b) { b.classList.toggle('on', b === el); }); state.page = 1; render(); });
    });
    $$('[data-f-status]', root).forEach(function (el) {
      el.addEventListener('click', function () { state.status = el.dataset.fStatus; $$('[data-f-status]', root).forEach(function (b) { b.classList.toggle('on', b === el); }); state.page = 1; render(); });
    });

    var rMin = $('[data-range-min]', root), rMax = $('[data-range-max]', root);
    var fill = $('[data-range-fill]', root), vMin = $('[data-range-vmin]', root), vMax = $('[data-range-vmax]', root);
    function syncRange() {
      var pMin = ((state.min - PMIN) / (PMAX - PMIN)) * 100;
      var pMax = ((state.max - PMIN) / (PMAX - PMIN)) * 100;
      if (fill) { fill.style.left = pMin + '%'; fill.style.width = (pMax - pMin) + '%'; }
      if (vMin) vMin.textContent = fmtM(state.min);
      if (vMax) vMax.textContent = state.max >= PMAX ? fmtM(PMAX) + '+' : fmtM(state.max);
    }
    if (rMin) rMin.addEventListener('input', function () { state.min = Math.min(+rMin.value, state.max - 500000); rMin.value = state.min; state.page = 1; syncRange(); render(); });
    if (rMax) rMax.addEventListener('input', function () { state.max = Math.max(+rMax.value, state.min + 500000); rMax.value = state.max; state.page = 1; syncRange(); render(); });

    var sortSel = $('[data-listings-sort]', root);
    sortSel && sortSel.addEventListener('change', function () { state.sort = sortSel.value; render(); });

    $$('[data-view]', root).forEach(function (el) {
      el.addEventListener('click', function () { state.view = el.dataset.view; $$('[data-view]', root).forEach(function (b) { b.classList.toggle('on', b === el); }); render(); });
    });

    function reset() {
      state.types = []; state.locs = []; state.beds = 'Any'; state.status = 'Any'; state.min = PMIN; state.max = PMAX; state.page = 1;
      $$('[data-f-type],[data-f-loc]', root).forEach(function (b) { b.classList.remove('on'); });
      $$('[data-f-beds]', root).forEach(function (b) { b.classList.toggle('on', b.dataset.fBeds === 'Any'); });
      $$('[data-f-status]', root).forEach(function (b) { b.classList.toggle('on', b.dataset.fStatus === 'Any'); });
      if (rMin) rMin.value = PMIN; if (rMax) rMax.value = PMAX;
      syncRange(); render();
    }
    var resetBtn = $('[data-listings-reset-all]', root);
    resetBtn && resetBtn.addEventListener('click', reset);

    // delegated: pager, fav, in-empty reset
    root.addEventListener('click', function (e) {
      var pg = e.target.closest && e.target.closest('[data-pg]');
      if (pg && !pg.disabled) { state.page = +pg.dataset.pg; render(); window.scrollTo({ top: root.getBoundingClientRect().top + window.scrollY - 120, behavior: 'smooth' }); return; }
      var fav = e.target.closest && e.target.closest('[data-fav]');
      if (fav) { e.preventDefault(); var key = fav.dataset.fav; state.favs[key] = !state.favs[key]; fav.classList.toggle('on', state.favs[key]); return; }
      if (e.target.closest && e.target.closest('[data-listings-reset]')) { reset(); }
    });

    // mobile filter drawer
    var side = $('[data-listings-side]', root), sideScrim = $('[data-listings-side-scrim]', root), filtToggle = $('[data-listings-filter-toggle]', root);
    filtToggle && filtToggle.addEventListener('click', function () { side && side.classList.add('open'); sideScrim && sideScrim.classList.add('open'); });
    sideScrim && sideScrim.addEventListener('click', function () { side.classList.remove('open'); sideScrim.classList.remove('open'); });

    syncRange();
    render();
  })();

  /* ===========================================================
     GALLERY  ·  category chip filter over server-rendered cards
     =========================================================== */
  (function () {
    var root = $('[data-onyx-gallery]');
    if (!root) return;
    var chips = $$('[data-gal-cat]', root);
    var cards = $$('[data-card-cat]', root);
    var count = $('[data-gal-count]', root);
    function apply(cat) {
      var shown = 0;
      cards.forEach(function (c) { var ok = (cat === 'All' || c.dataset.cardCat === cat); c.style.display = ok ? '' : 'none'; if (ok) shown++; });
      if (count) count.textContent = shown + ' residence' + (shown !== 1 ? 's' : '');
      chips.forEach(function (ch) { ch.classList.toggle('on', ch.dataset.galCat === cat); });
    }
    chips.forEach(function (ch) { ch.addEventListener('click', function () { apply(ch.dataset.galCat); }); });
  })();

  /* ===========================================================
     PROPERTY  ·  gallery lightbox + mortgage calc + sticky CTA
     =========================================================== */
  (function () {
    var gal = $('[data-onyx-gallery-lb]');
    var lb = $('.lightbox');
    if (gal && lb) {
      var items = $$('[data-gal-item]', gal); // hidden source list of {glyph,tag} via data
      var main = $('.gal-main', gal);
      var thumbs = $$('.gal-thumb', gal);
      var stage = $('.lb-stage', lb), counter = $('.lb-count', lb);
      var idx = 0;
      function phHTML(glyph) {
        var inner = glyph === 'tower'
          ? '<path d="M20 44V14l4-6 4 6v30M16 44V22h16M14 44h20M22 18h4M22 24h4M22 30h4M22 36h4"/>'
          : glyph === 'interior'
            ? '<path d="M6 30h36M10 30V18a4 4 0 0 1 4-4h20a4 4 0 0 1 4 4v12M14 30v6M34 30v6M14 22h8v8M26 22h8"/>'
            : '<rect x="8" y="8" width="32" height="32"/><path d="M8 20h20M28 8v20M28 28h12M20 28v12"/>';
        return '<div class="ph"><div class="ph-glyph"><svg width="56" height="56" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">' + inner + '</svg></div></div>';
      }
      var GAL = items.map(function (it) { return { glyph: it.dataset.galItem, tag: it.dataset.galTag || '' }; });
      function setMain(i) {
        idx = (i + GAL.length) % GAL.length;
        if (main) {
          var tag = main.querySelector('.gal-tag'); var zoom = main.querySelector('.gal-zoom');
          main.querySelectorAll('.ph,.photo').forEach(function (n) { n.remove(); });
          main.insertAdjacentHTML('beforeend', phHTML(GAL[idx].glyph));
          if (tag) tag.textContent = GAL[idx].tag;
        }
        thumbs.forEach(function (th, k) { th.classList.toggle('on', k === idx); });
      }
      function openLB(i) {
        setMain(i);
        if (stage) { stage.innerHTML = phHTML(GAL[idx].glyph) + '<span class="gal-tag">' + GAL[idx].tag + '</span>'; }
        if (counter) counter.textContent = ('0' + (idx + 1)).slice(-2) + ' / ' + ('0' + GAL.length).slice(-2);
        lb.classList.add('open');
      }
      function nav(d) { setMain(idx + d); if (stage) stage.innerHTML = phHTML(GAL[idx].glyph) + '<span class="gal-tag">' + GAL[idx].tag + '</span>'; if (counter) counter.textContent = ('0' + (idx + 1)).slice(-2) + ' / ' + ('0' + GAL.length).slice(-2); }
      function closeLB() { lb.classList.remove('open'); }
      main && main.addEventListener('click', function () { openLB(idx); });
      thumbs.forEach(function (th, k) { th.addEventListener('click', function () { setMain(k); }); });
      var moreBtn = $('.gal-more', gal); moreBtn && moreBtn.addEventListener('click', function (e) { e.stopPropagation(); openLB(idx); });
      $('.lb-close', lb) && $('.lb-close', lb).addEventListener('click', closeLB);
      $('.lb-prev', lb) && $('.lb-prev', lb).addEventListener('click', function (e) { e.stopPropagation(); nav(-1); });
      $('.lb-next', lb) && $('.lb-next', lb).addEventListener('click', function (e) { e.stopPropagation(); nav(1); });
      lb.addEventListener('click', function (e) { if (e.target === lb) closeLB(); });
      window.addEventListener('keydown', function (e) {
        if (!lb.classList.contains('open')) return;
        if (e.key === 'Escape') closeLB();
        if (e.key === 'ArrowRight') nav(1);
        if (e.key === 'ArrowLeft') nav(-1);
      });
      setMain(0);
    }

    // mortgage calculator
    var calc = $('[data-onyx-calc]');
    if (calc) {
      var money = function (n) { return 'AED ' + Math.round(n).toLocaleString('en-US'); };
      var get = function (k) { return $('[data-calc="' + k + '"]', calc); };
      var price = get('price'), dp = get('dp'), years = get('years'), rate = get('rate');
      var outMonthly = get('monthly'), outSub = get('sub');
      var lblPrice = get('lbl-price'), lblDp = get('lbl-dp'), lblYears = get('lbl-years'), lblRate = get('lbl-rate');
      function recompute() {
        var P = +price.value, D = +dp.value, Y = +years.value, R = +rate.value;
        var loan = P * (1 - D / 100), mr = R / 100 / 12, n = Y * 12;
        var monthly = mr === 0 ? loan / n : loan * mr * Math.pow(1 + mr, n) / (Math.pow(1 + mr, n) - 1);
        if (lblPrice) lblPrice.textContent = money(P);
        if (lblDp) lblDp.textContent = D + '% · ' + money(P * D / 100);
        if (lblYears) lblYears.textContent = Y + ' years';
        if (lblRate) lblRate.textContent = R.toFixed(1) + '%';
        if (outMonthly) outMonthly.textContent = money(monthly);
        if (outSub) outSub.textContent = 'Loan ' + money(loan) + ' over ' + Y + ' years';
      }
      [price, dp, years, rate].forEach(function (el) { el && el.addEventListener('input', recompute); });
      recompute();
    }

    // sticky CTA bar
    var scta = $('.scta');
    if (scta) {
      var onScroll = function () {
        var past = window.scrollY > 720;
        var foot = $('#footer') || $('.foot');
        var nearFoot = foot && foot.getBoundingClientRect().top < window.innerHeight + 40;
        var show = past && !nearFoot;
        scta.classList.toggle('show', show);
        document.body.classList.toggle('scta-on', show);
      };
      onScroll();
      window.addEventListener('scroll', onScroll, { passive: true });
    }
  })();

  /* ---------- neighborhoods map hover (home) ---------- */
  (function () {
    var items = $$('[data-nb-item]');
    if (!items.length) return;
    var pins = $$('[data-nb-pin]');
    items.forEach(function (li) {
      li.addEventListener('mouseenter', function () {
        var k = li.dataset.nbItem;
        items.forEach(function (i) { i.classList.toggle('active', i === li); });
        pins.forEach(function (p) { p.classList.toggle('on', p.dataset.nbPin === k); });
      });
    });
  })();

  /* ===========================================================
     PROJECT DETAIL  ·  tabs + ROI/payment calculators + gallery lightbox
     =========================================================== */
  (function () {
    var root = $('[data-onyx-proj]');
    if (!root) return;
    var aed = function (n) { return 'AED ' + Math.round(n).toLocaleString('en-US'); };

    /* ---- tabs ---- */
    (function () {
      var nav = $('[data-onyx-tabs]', root);
      if (!nav) return;
      var tabs = $$('.pdp-tab', nav);
      var panels = {};
      $$('[data-panel]', root).forEach(function (p) { panels[p.dataset.panel] = p; });
      function activate(key) {
        tabs.forEach(function (t) { t.classList.toggle('on', t.dataset.tab === key); });
        Object.keys(panels).forEach(function (k) { panels[k].hidden = (k !== key); });
      }
      tabs.forEach(function (t) { t.addEventListener('click', function () { activate(t.dataset.tab); }); });
    })();

    /* ---- ROI calculator ---- */
    (function () {
      var box = $('[data-onyx-roi]', root);
      if (!box) return;
      var get = function (k) { return $('[data-roi="' + k + '"]', box); };
      var price = get('price'), yld = get('yield'), occ = get('occ'), apx = get('apx');
      var oRent = get('rent'), oNet = get('netyield'), oPay = get('payback'), oT5 = get('total5');
      function calc() {
        var P = +price.value || 0, Y = +yld.value || 0, O = (+occ.value || 0) / 100, A = (+apx.value || 0) / 100;
        var rent = P * (Y / 100) * O;
        var netY = P > 0 ? (rent / P) * 100 : 0;
        var pay = rent > 0 ? P / rent : 0;
        var rent5 = rent * 5;
        var apx5 = P * (Math.pow(1 + A, 5) - 1);
        var total5 = rent5 + apx5;
        if (oRent) oRent.textContent = aed(rent);
        if (oNet) oNet.textContent = netY.toFixed(1) + '%';
        if (oPay) oPay.textContent = pay > 0 ? pay.toFixed(1) + ' yrs' : '—';
        if (oT5) oT5.textContent = aed(total5) + (P > 0 ? '  ·  +' + ((total5 / P) * 100).toFixed(0) + '%' : '');
      }
      [price, yld, occ, apx].forEach(function (el) { el && el.addEventListener('input', calc); });
      calc();
    })();

    /* ---- off-plan payment plan ---- */
    (function () {
      var box = $('[data-onyx-plan]', root);
      if (!box) return;
      var get = function (k) { return $('[data-plan="' + k + '"]', box); };
      var price = get('price'), down = get('down'), during = get('during'), handover = get('handover');
      var oD = get('downv'), oC = get('duringv'), oH = get('handoverv'), oS = get('sumv'), oSL = get('sumlbl');
      function calc() {
        var P = +price.value || 0, d = +down.value || 0, c = +during.value || 0, h = +handover.value || 0;
        var sum = d + c + h;
        if (oD) oD.textContent = aed(P * d / 100);
        if (oC) oC.textContent = aed(P * c / 100);
        if (oH) oH.textContent = aed(P * h / 100);
        if (oS) oS.textContent = aed(P * sum / 100);
        if (oSL) oSL.textContent = (sum === 100 ? 'Total' : 'Total (' + sum + '% — should be 100%)');
        if (oS) oS.style.color = (sum === 100 ? '' : 'var(--gold)');
      }
      [price, down, during, handover].forEach(function (el) { el && el.addEventListener('input', calc); });
      calc();
    })();

    /* ---- gallery lightbox ---- */
    (function () {
      var items = $$('[data-pg-item]', root);
      var lb = $('[data-pg-lightbox]');
      if (!items.length || !lb) return;
      var img = $('[data-pg-img]', lb);
      var urls = items.map(function (a) { return a.getAttribute('href'); });
      var idx = 0;
      function show(i) { idx = (i + urls.length) % urls.length; if (img) img.src = urls[idx]; }
      function open(i) { show(i); lb.hidden = false; document.body.style.overflow = 'hidden'; }
      function close() { lb.hidden = true; document.body.style.overflow = ''; }
      items.forEach(function (a, i) { a.addEventListener('click', function (e) { e.preventDefault(); open(i); }); });
      var cl = $('[data-pg-close]', lb), pv = $('[data-pg-prev]', lb), nx = $('[data-pg-next]', lb);
      cl && cl.addEventListener('click', close);
      pv && pv.addEventListener('click', function (e) { e.stopPropagation(); show(idx - 1); });
      nx && nx.addEventListener('click', function (e) { e.stopPropagation(); show(idx + 1); });
      lb.addEventListener('click', function (e) { if (e.target === lb) close(); });
      window.addEventListener('keydown', function (e) {
        if (lb.hidden) return;
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowRight') show(idx + 1);
        if (e.key === 'ArrowLeft') show(idx - 1);
      });
    })();
  })();

  /* ===========================================================
     PROJECTS CATALOG  ·  live filter + infinite scroll + autocomplete
     =========================================================== */
  (function () {
    var root = $('[data-onyx-catalog]');
    if (!root) return;
    function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]; }); }

    var restBase = root.getAttribute('data-rest');
    var suggestBase = root.getAttribute('data-suggest');
    var form = $('[data-cf-form]', root);
    var results = $('[data-cf-results]', root);
    var countEl = $('[data-cf-count]', root);
    var sortSel = $('[data-cf-sort]', root);
    var loader = $('[data-cf-loader]', root);
    var sentinel = $('[data-cf-sentinel]', root);
    var page = 1, pages = 1, loading = false;
    try { var st = JSON.parse($('[data-cf-state]', root).textContent); page = st.page || 1; pages = st.pages || 1; } catch (e) {}

    function params() {
      var p = new URLSearchParams();
      var q = $('[data-cf-q]', root); if (q && q.value) p.set('q', q.value);
      var status = form.querySelector('input[name="status"]:checked'); if (status && status.value) p.set('status', status.value);
      $$('input[name="type[]"]:checked', form).forEach(function (c) { p.append('type[]', c.value); });
      $$('input[name="area[]"]:checked', form).forEach(function (c) { p.append('area[]', c.value); });
      var dev = $('[data-cf-dev]', root); if (dev && dev.value) p.set('dev', dev.value);
      if (sortSel && sortSel.value && sortSel.value !== 'recent') p.set('sort', sortSel.value);
      return p;
    }

    function fetchPage(pg, append) {
      if (loading) return;
      loading = true; if (loader) loader.hidden = false;
      var p = params(); p.set('page', pg);
      fetch(restBase + '?' + p.toString(), { headers: { Accept: 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          var cards = d.cards || '';
          if (append) { results.insertAdjacentHTML('beforeend', cards); }
          else { results.innerHTML = cards || '<p class="cat-empty" style="color:var(--muted);padding:40px 0">' + 'No projects match these filters.' + '</p>'; }
          page = d.page || pg; pages = d.pages || 1;
          if (countEl) countEl.innerHTML = '<b>' + (d.total || 0) + '</b> projects';
          loading = false; if (loader) loader.hidden = true;
          if (!append) { var u = location.pathname; var qs = params().toString(); history.replaceState(null, '', qs ? u + '?' + qs : u); window.scrollTo({ top: 0, behavior: 'smooth' }); }
        })
        .catch(function () { loading = false; if (loader) loader.hidden = true; });
    }
    function reset() { page = 1; fetchPage(1, false); }

    // filter change → live refetch + toggle .on visuals
    form.addEventListener('change', function (e) {
      var t = e.target;
      if (t.name === 'type[]' || t.name === 'area[]') { var l = t.closest('.chk'); if (l) l.classList.toggle('on', t.checked); }
      if (t.name === 'status') { $$('.seg-opt', form).forEach(function (s) { var i = s.querySelector('input'); s.classList.toggle('on', i && i.checked); }); }
      reset();
    });
    form.addEventListener('submit', function (e) { e.preventDefault(); reset(); });
    sortSel && sortSel.addEventListener('change', reset);

    var resetLink = $('[data-cf-reset]', root);
    resetLink && resetLink.addEventListener('click', function (e) {
      e.preventDefault();
      $$('input[name="type[]"],input[name="area[]"]', form).forEach(function (c) { c.checked = false; });
      $$('.chk.on', form).forEach(function (l) { l.classList.remove('on'); });
      var any = form.querySelector('input[name="status"][value=""]'); if (any) any.checked = true;
      $$('.seg-opt', form).forEach(function (s) { var i = s.querySelector('input'); s.classList.toggle('on', i && i.checked); });
      var q = $('[data-cf-q]', root); if (q) q.value = '';
      var dev = $('[data-cf-dev]', root); if (dev) dev.value = '';
      var ds = $('[data-cf-devsearch]', root); if (ds) ds.value = '';
      if (sortSel) sortSel.value = 'recent';
      reset();
    });

    // infinite scroll
    if ('IntersectionObserver' in window && sentinel) {
      var io = new IntersectionObserver(function (ents) {
        ents.forEach(function (en) { if (en.isIntersecting && !loading && page < pages) fetchPage(page + 1, true); });
      }, { rootMargin: '700px' });
      io.observe(sentinel);
    }

    // project-name autocomplete (debounced) + live filter on the same input
    var qEl = $('[data-cf-q]', root), qMenu = $('[data-cf-q-menu]', root), qDeb, qSug;
    if (qEl) {
      qEl.addEventListener('input', function () {
        clearTimeout(qDeb); qDeb = setTimeout(reset, 320);
        var v = qEl.value;
        if (!qMenu) return;
        if (v.length < 2) { qMenu.hidden = true; qMenu.innerHTML = ''; return; }
        clearTimeout(qSug); qSug = setTimeout(function () {
          fetch(suggestBase + '?q=' + encodeURIComponent(v)).then(function (r) { return r.json(); }).then(function (list) {
            if (!list || !list.length) { qMenu.hidden = true; return; }
            qMenu.innerHTML = list.map(function (it) { return '<a href="' + it.url + '"><b>' + esc(it.title) + '</b>' + (it.area ? '<span>' + esc(it.area) + '</span>' : '') + '</a>'; }).join('');
            qMenu.hidden = false;
          }).catch(function () {});
        }, 160);
      });
      document.addEventListener('click', function (e) { if (qMenu && !qMenu.contains(e.target) && e.target !== qEl) qMenu.hidden = true; });
    }

    // developer typeahead (client-side over the term island)
    var devs = []; try { devs = JSON.parse($('[data-cf-devs]', root).textContent) || []; } catch (e) {}
    var devSearch = $('[data-cf-devsearch]', root), devHidden = $('[data-cf-dev]', root), devMenu = $('[data-cf-dev-menu]', root);
    function renderDevs(v) {
      if (!devMenu) return;
      v = (v || '').toLowerCase().trim();
      var list = (v ? devs.filter(function (d) { return d.n.toLowerCase().indexOf(v) > -1; }) : devs).slice(0, 24);
      devMenu.innerHTML = '<a data-dev="" class="cat-ac-all">All developers</a>' + list.map(function (d) { return '<a data-dev="' + d.s + '">' + esc(d.n) + ' <span>(' + d.c + ')</span></a>'; }).join('');
      devMenu.hidden = false;
    }
    if (devSearch && devMenu && devHidden) {
      devSearch.addEventListener('focus', function () { renderDevs(devSearch.value); });
      devSearch.addEventListener('input', function () { renderDevs(devSearch.value); });
      devMenu.addEventListener('click', function (e) {
        var a = e.target.closest('[data-dev]'); if (!a) return; e.preventDefault();
        devHidden.value = a.getAttribute('data-dev');
        devSearch.value = a.getAttribute('data-dev') ? a.textContent.replace(/\s*\(\d+\)\s*$/, '') : '';
        devMenu.hidden = true; reset();
      });
      document.addEventListener('click', function (e) { if (!devMenu.contains(e.target) && e.target !== devSearch) devMenu.hidden = true; });
    }

    // mobile filter drawer
    var toggle = $('[data-cf-toggle]', root), side = $('[data-cf-side]', root), scrim = $('[data-cf-scrim]', root);
    toggle && toggle.addEventListener('click', function () { side && side.classList.add('open'); scrim && scrim.classList.add('open'); });
    scrim && scrim.addEventListener('click', function () { side.classList.remove('open'); scrim.classList.remove('open'); });
  })();

})();

/* ── Hero featured-project rotator ──────────────────────────────────────────
   Shuffles the real-project slides per visit (so a cached page still varies)
   and cross-fades through them, updating the photo, caption name/link + index. */
(function () {
  function initHeroRotator() {
    var root = document.querySelector('[data-hero-rotator]');
    if (!root) return;
    var dataEl = root.querySelector('[data-hero-slides]');
    if (!dataEl) return;
    var slides;
    try { slides = JSON.parse(dataEl.textContent || '[]'); } catch (e) { return; }
    if (!Array.isArray(slides) || slides.length < 2) return;
    for (var i = slides.length - 1; i > 0; i--) {            // Fisher–Yates shuffle
      var j = Math.floor(Math.random() * (i + 1));
      var t = slides[i]; slides[i] = slides[j]; slides[j] = t;
    }
    var img  = root.querySelector('.photo img');
    var cap  = root.querySelector('[data-hero-cap]');
    var name = root.querySelector('.hb-cap-name');
    var idx  = root.querySelector('[data-hero-idx]');
    if (!img) return;
    slides.forEach(function (s) { var p = new Image(); p.src = s.img; }); // preload
    img.style.transition = 'opacity .6s ease';
    var cur = 0;
    function show(n) {
      var s = slides[n];
      img.style.opacity = '0';
      setTimeout(function () {
        img.src = s.img; img.alt = s.name || '';
        if (cap && s.url) cap.setAttribute('href', s.url);
        if (name) name.textContent = s.name || '';
        if (idx) idx.textContent = ('0' + (n + 1)).slice(-2);
        img.style.opacity = '1';
      }, 600);
    }
    show(0);                                                  // jump to shuffled-first
    setInterval(function () { cur = (cur + 1) % slides.length; show(cur); }, 5500);
  }
  if (document.readyState !== 'loading') initHeroRotator();
  else document.addEventListener('DOMContentLoaded', initHeroRotator);
})();

/* ── Homepage film modal — play button opens the operator's video. ───────── */
(function () {
  function initFilm() {
    var btn = document.querySelector('.life-play');
    var modal = document.getElementById('onyx-film-modal');
    if (!btn || !modal) return;
    var video = document.getElementById('onyx-film-video');
    var closeBtn = modal.querySelector('.film-modal-close');
    function open() {
      modal.hidden = false;
      requestAnimationFrame(function () { modal.classList.add('open'); });
      document.body.style.overflow = 'hidden';
      if (video) { var p = video.play(); if (p && p.catch) p.catch(function () {}); }
    }
    function close() {
      modal.classList.remove('open');
      if (video) { try { video.pause(); } catch (e) {} }
      document.body.style.overflow = '';
      setTimeout(function () { modal.hidden = true; }, 300);
    }
    btn.addEventListener('click', open);
    if (closeBtn) closeBtn.addEventListener('click', close);
    modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) close(); });
  }
  if (document.readyState !== 'loading') initFilm();
  else document.addEventListener('DOMContentLoaded', initFilm);
})();
