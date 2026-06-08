/* ===========================================================
   LuwiPress Amber — tour listing filter / sort
   Client-side facet filter + sort + price range over the cards
   already rendered in a [data-lwp-tours] grid. Pairs a grid with
   its [data-lwp-filters] sidebar + .listing-top toolbar by an
   optional data-tours-group (blank matches blank). Multi-instance
   safe; null-guarded.
   =========================================================== */
(function () {
  'use strict';
  var $  = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  function group(el) { return el.getAttribute('data-tours-group') || ''; }

  function matchByGroup(list, g) {
    // exact group match first; if none and g is blank, take the first.
    for (var i = 0; i < list.length; i++) { if (group(list[i]) === g) return list[i]; }
    return (g === '' && list.length) ? list[0] : null;
  }

  function initSet(grid) {
    var g = group(grid);
    var filters = matchByGroup($$('[data-lwp-filters]'), g);
    var toolbar = matchByGroup($$('.listing-top'), g);
    var cards = $$('.tour-card', grid);
    if (!cards.length) return;

    var noResults = $('.no-results', grid);
    var resCount  = toolbar && $('.res-count', toolbar);
    var sortSel   = toolbar && $('.sort-select', toolbar);
    var priceIn   = filters && $('.price-range-input', filters);
    var priceOut  = filters && $('.price-out', filters);
    var resetBtn  = filters && $('.reset', filters);

    function checkedValues(sel) {
      return $$(sel, filters).filter(function (i) { return i.checked; }).map(function (i) { return i.value; });
    }

    function apply() {
      if (!filters && !sortSel) { if (resCount) resCount.textContent = cards.length; return; }
      var cats = filters ? checkedValues('.f-cat') : [];
      var durs = filters ? checkedValues('.f-dur') : [];
      var maxPrice = priceIn ? parseFloat(priceIn.value) : Infinity;

      var visible = 0;
      cards.forEach(function (card) {
        var okC = !cats.length || cats.indexOf(card.getAttribute('data-cat')) > -1;
        var okD = !durs.length || durs.indexOf(card.getAttribute('data-dur')) > -1;
        var okP = (parseFloat(card.getAttribute('data-price')) || 0) <= maxPrice;
        var show = okC && okD && okP;
        card.classList.toggle('hidden', !show);
        if (show) visible++;
      });

      /* sort visible cards */
      if (sortSel) {
        var mode = sortSel.value;
        var sorted = cards.slice().sort(function (a, b) {
          if (mode === 'price-asc')  return num(a, 'data-price') - num(b, 'data-price');
          if (mode === 'price-desc') return num(b, 'data-price') - num(a, 'data-price');
          if (mode === 'rating')     return num(b, 'data-rating') - num(a, 'data-rating');
          return num(b, 'data-pop') - num(a, 'data-pop'); /* popular */
        });
        sorted.forEach(function (card) { grid.insertBefore(card, noResults || null); });
      }

      if (resCount) resCount.textContent = visible;
      if (noResults) noResults.hidden = visible !== 0;
    }

    function num(el, attr) { return parseFloat(el.getAttribute(attr)) || 0; }

    if (filters) {
      $$('.f-cat, .f-dur', filters).forEach(function (i) { i.addEventListener('change', apply); });
      if (priceIn) {
        priceIn.addEventListener('input', function () {
          if (priceOut) priceOut.textContent = (grid.getAttribute('data-currency') || '') + parseFloat(priceIn.value).toLocaleString();
          apply();
        });
      }
      resetBtn && resetBtn.addEventListener('click', function () {
        $$('.f-cat, .f-dur', filters).forEach(function (i) { i.checked = false; });
        if (priceIn) { priceIn.value = priceIn.max; priceIn.dispatchEvent(new Event('input')); }
        apply();
      });
      var ftoggle = toolbar && $('.filter-toggle', toolbar);
      ftoggle && ftoggle.addEventListener('click', function () { filters.classList.toggle('open'); });
    }
    sortSel && sortSel.addEventListener('change', apply);

    /* save/heart toggle */
    $$('.tc-fav', grid).forEach(function (btn) {
      btn.addEventListener('click', function (e) { e.preventDefault(); btn.classList.toggle('on'); });
    });

    /* URL prefilter (?cat= / ?dur=) — bridges the Hero Booking Bar */
    if (filters) {
      try {
        var params = new URLSearchParams(window.location.search);
        ['cat', 'dur'].forEach(function (k) {
          var v = params.get(k);
          if (!v) return;
          var sel = k === 'cat' ? '.f-cat' : '.f-dur';
          $$(sel, filters).forEach(function (i) { if (i.value === v) i.checked = true; });
        });
      } catch (e) {}
    }

    apply();
  }

  $$('[data-lwp-tours]').forEach(initSet);
})();
