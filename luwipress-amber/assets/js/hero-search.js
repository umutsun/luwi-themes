/* ===========================================================
   LuwiPress Amber — hero "tabbed search" (Trip.com style)
   Replaces the static .bookbar inner content with a JS-built
   tabbed search UI: [ Flights | Hotels | Tours ]. Each tab is its
   own search form. On submit the hero does NOT search — it builds
   a deep-link query string and NAVIGATES to that type's results
   page (LWP_HERO.urls.<type>), which runs the real search via the
   fbd-connect plugin (same deep-link pattern the old tour hero used).

   Progressive enhancement: the static .bookbar is the no-JS fallback.
   This script REPLACES bar.innerHTML, so it no longer depends on the
   static .field structure. Scoped, null-guarded, vanilla, no deps.

   Reusable amber components (all themed site-wide):
     • Custom dark calendar (.datepick / .dp-*) — styled by pages.css.
       Ported as a factory so every date field gets its own instance.
     • Pax stepper (.hs-pax-stepper / .hs-pax / .hs-pax-btn) — +/-.
     • Autocomplete (.hs-ac) over the fbd-connect REST /places endpoints,
       degrading gracefully to plain text when unavailable.
   Tab bar + per-tab layout styled by the LWP-HERO-TABS-V3 block in
   assets/css/amber.css.
   =========================================================== */
(function () {
  'use strict';

  var D = window.LWP_HERO || {};

  var bar = document.querySelector('.hero .bookbar') || document.querySelector('.bookbar');
  if (!bar) return;

  /* ---- i18n with English fallbacks (D.i18n may be an old cached shape) ---- */
  var I18N = D.i18n || {};
  function t(key, dflt) { return (I18N && typeof I18N[key] === 'string' && I18N[key]) || dflt; }
  var T = {
    flights:       t('flights', 'Flights'),
    hotels:        t('hotels', 'Hotels'),
    tours:         t('tours', 'Tours'),
    from:          t('from', 'From'),
    to:            t('to', 'To'),
    depart:        t('depart', 'Depart'),
    'return':      t('return', 'Return'),
    travellers:    t('travellers', 'Travellers'),
    cabin:         t('cabin', 'Cabin'),
    destination:   t('destination', 'Destination'),
    checkin:       t('checkin', 'Check-in'),
    checkout:      t('checkout', 'Check-out'),
    guests:        t('guests', 'Guests'),
    rooms:         t('rooms', 'Rooms'),
    date:          t('date', 'Date'),
    searchFlights: t('searchFlights', 'Search flights'),
    searchHotels:  t('searchHotels', 'Search hotels'),
    searchTours:   t('searchTours', 'Search tours'),
    comingSoon:    t('comingSoon', 'Coming soon'),
    cabinEconomy:  t('cabinEconomy', 'Economy'),
    cabinPremium:  t('cabinPremium', 'Premium economy'),
    cabinBusiness: t('cabinBusiness', 'Business'),
    cabinFirst:    t('cabinFirst', 'First'),
    chooseDate:    t('chooseDate', 'Choose a date'),
    choose:        t('choose', 'Choose'),
    today:         t('today', 'Today'),
    clear:         t('clear', 'Clear'),
    prevMonth:     t('prevMonth', 'Previous month'),
    nextMonth:     t('nextMonth', 'Next month'),
    increase:      t('increase', 'Increase'),
    decrease:      t('decrease', 'Decrease'),
    iataHint:      t('iataHint', 'Pick an airport or enter a 3-letter code'),
    fromCity:      t('from', 'From'),
    toCity:        t('to', 'To')
  };

  var URLS = (D.urls && typeof D.urls === 'object') ? D.urls : {};

  /* fbd-connect REST base (flight plugin owns it — read, never duplicate). */
  var REST_BASE = (window.fbdConnect && typeof window.fbdConnect.rest === 'string')
    ? window.fbdConnect.rest.replace(/\/+$/, '')
    : '';

  /* ---- date helpers ---- */
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function iso(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }

  /* Month / weekday labels — localized via toLocaleString on the document lang. */
  var LOCALE = (document.documentElement && document.documentElement.lang) || undefined;
  function monthLabels() {
    var out = [];
    for (var m = 0; m < 12; m++) {
      out.push(new Date(2021, m, 1).toLocaleDateString(LOCALE, { month: 'long' }));
    }
    return out;
  }
  function dowLabels() {
    // Monday-first short weekday names.
    var out = [];
    for (var i = 0; i < 7; i++) {
      // 2021-03-01 was a Monday.
      out.push(new Date(2021, 2, 1 + i).toLocaleDateString(LOCALE, { weekday: 'short' }));
    }
    return out;
  }
  var MONTHS = monthLabels();
  var DOW = dowLabels();

  function el(tag, cls, attrs) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (attrs) {
      for (var k in attrs) {
        if (Object.prototype.hasOwnProperty.call(attrs, k)) n.setAttribute(k, attrs[k]);
      }
    }
    return n;
  }

  var UID = 'hs' + Math.random().toString(36).slice(2, 7) + Date.now().toString(36).slice(-3);
  var uidN = 0;
  function nextId(prefix) { return prefix + '-' + UID + '-' + (uidN++); }

  /* =========================================================================
     DATE PICKER FACTORY — one .datepick instance per date field.
     Builds the DOM the picker expects (.dp-trigger / .dp-pop / .dp-days /
     hidden .bb-date), ported from booking.js. Monday-first weeks, no past
     days, prev disabled at current month, Today/Clear, outside-click close.
     Returns { root, getISO, setMin } so the form can read the value and the
     return/checkout pickers can refuse dates before the depart/check-in date.
     ========================================================================= */
  function createDatePicker(opts) {
    opts = opts || {};
    var placeholder = opts.placeholder || T.chooseDate;

    var root = el('div', 'datepick hs-datepick');

    var trigger = el('button', 'dp-trigger', { type: 'button' });
    var valEl = el('span', 'dp-val placeholder', { 'data-placeholder': placeholder });
    valEl.textContent = placeholder;
    var calIc = el('span', 'cal-ic', { 'aria-hidden': 'true' });
    calIc.innerHTML = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
    trigger.appendChild(valEl);
    trigger.appendChild(calIc);

    var pop = el('div', 'dp-pop');

    var head = el('div', 'dp-head');
    var prevB = el('button', 'dp-nav dp-prev', { type: 'button', 'aria-label': T.prevMonth });
    prevB.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>';
    var title = el('span', 'dp-title');
    var nextB = el('button', 'dp-nav dp-next', { type: 'button', 'aria-label': T.nextMonth });
    nextB.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';
    head.appendChild(prevB);
    head.appendChild(title);
    head.appendChild(nextB);

    var dow = el('div', 'dp-dow');
    DOW.forEach(function (d) {
      var s = el('span');
      s.textContent = d;
      dow.appendChild(s);
    });

    var daysEl = el('div', 'dp-days');

    var foot = el('div', 'dp-foot');
    var todayB = el('button', 'dp-today', { type: 'button' });
    todayB.textContent = T.today;
    var clearB = el('button', 'dp-clear', { type: 'button' });
    clearB.textContent = T.clear;
    foot.appendChild(todayB);
    foot.appendChild(clearB);

    var hidden = el('input', 'bb-date', { type: 'hidden' });

    pop.appendChild(head);
    pop.appendChild(dow);
    pop.appendChild(daysEl);
    pop.appendChild(foot);

    root.appendChild(trigger);
    root.appendChild(pop);
    root.appendChild(hidden);

    var today = new Date(); today.setHours(0, 0, 0, 0);
    var minDate = new Date(today);   // earliest selectable day (>= today, can be raised)
    var view = new Date(today.getFullYear(), today.getMonth(), 1);
    var selected = null;

    function atViewStartLimited() {
      // Prev disabled when the view month is the minDate's month (can't go earlier).
      return view.getFullYear() === minDate.getFullYear() && view.getMonth() === minDate.getMonth();
    }

    function render() {
      title.textContent = MONTHS[view.getMonth()] + ' ' + view.getFullYear();
      daysEl.innerHTML = '';
      var first = new Date(view.getFullYear(), view.getMonth(), 1);
      var offset = (first.getDay() + 6) % 7; // Monday-first
      var daysInMonth = new Date(view.getFullYear(), view.getMonth() + 1, 0).getDate();
      var i;
      for (i = 0; i < offset; i++) {
        daysEl.appendChild(el('span', 'dp-day dp-blank empty'));
      }
      for (i = 1; i <= daysInMonth; i++) {
        var d = new Date(view.getFullYear(), view.getMonth(), i);
        var cell = el('button', 'dp-day', { type: 'button' });
        cell.textContent = i;
        if (d.getTime() === today.getTime()) cell.classList.add('today');
        if (selected && d.getTime() === selected.getTime()) cell.classList.add('selected');
        if (d < minDate) {
          cell.disabled = true;
          cell.classList.add('disabled');
        } else {
          (function (dd) {
            cell.addEventListener('click', function () { pick(dd); });
          })(d);
        }
        daysEl.appendChild(cell);
      }
      prevB.disabled = atViewStartLimited();
    }

    function pick(d) {
      selected = d;
      hidden.value = iso(d);
      valEl.textContent = d.toLocaleDateString(LOCALE, { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
      valEl.classList.remove('placeholder');
      root.classList.remove('dp-error');
      close();
      render();
      if (typeof opts.onChange === 'function') opts.onChange(iso(d));
    }

    function clearSel() {
      selected = null;
      hidden.value = '';
      valEl.textContent = placeholder;
      valEl.classList.add('placeholder');
      render();
      if (typeof opts.onChange === 'function') opts.onChange('');
    }

    function open() { pop.classList.add('open'); root.classList.add('open'); render(); }
    function close() { pop.classList.remove('open'); root.classList.remove('open'); }

    trigger.addEventListener('click', function (e) {
      e.stopPropagation();
      if (root.classList.contains('open')) close(); else open();
    });
    prevB.addEventListener('click', function () { if (prevB.disabled) return; view.setMonth(view.getMonth() - 1); render(); });
    nextB.addEventListener('click', function () { view.setMonth(view.getMonth() + 1); render(); });
    todayB.addEventListener('click', function () {
      // "Today" still respects the floor (a return date can't be earlier than depart).
      if (today < minDate) { pick(new Date(minDate)); } else { pick(new Date(today)); }
    });
    clearB.addEventListener('click', function () { clearSel(); close(); });
    document.addEventListener('click', function (e) {
      if (!root.contains(e.target)) close();
    });

    render();

    return {
      root: root,
      getISO: function () { return hidden.value || ''; },
      // Raise the earliest selectable date (e.g. return >= depart). Clears the
      // current pick if it falls below the new floor; never lowers below today.
      setMin: function (isoStr) {
        var d = (typeof isoStr === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(isoStr))
          ? new Date(isoStr + 'T00:00:00')
          : new Date(today);
        d.setHours(0, 0, 0, 0);
        if (d < today) d = new Date(today);
        minDate = d;
        if (selected && selected < minDate) { clearSel(); }
        // Keep the view from sitting before the new floor.
        if (view < new Date(minDate.getFullYear(), minDate.getMonth(), 1)) {
          view = new Date(minDate.getFullYear(), minDate.getMonth(), 1);
        }
        render();
      }
    };
  }

  /* =========================================================================
     PAX STEPPER FACTORY — reuses .hs-pax-stepper / .hs-pax / .hs-pax-btn.
     ========================================================================= */
  function createStepper(opts) {
    opts = opts || {};
    var min = typeof opts.min === 'number' ? opts.min : 1;
    var max = typeof opts.max === 'number' ? opts.max : 9;
    var def = typeof opts.def === 'number' ? opts.def : min;
    var label = opts.label || '';

    var stepper = el('div', 'hs-pax-stepper');
    var decBtn = el('button', 'hs-pax-btn hs-pax-btn--dec', { type: 'button', tabindex: '-1', 'aria-label': T.decrease + (label ? ' ' + label : '') });
    var input = el('input', 'hs-pax', { type: 'number', inputmode: 'numeric', step: '1' });
    if (label) input.setAttribute('aria-label', label);
    input.min = min;
    input.max = max;
    input.value = Math.max(min, Math.min(max, def));
    var incBtn = el('button', 'hs-pax-btn hs-pax-btn--inc', { type: 'button', tabindex: '-1', 'aria-label': T.increase + (label ? ' ' + label : '') });

    stepper.appendChild(decBtn);
    stepper.appendChild(input);
    stepper.appendChild(incBtn);

    function val() {
      var n = parseInt(input.value, 10);
      if (isNaN(n)) n = def;
      return Math.max(min, Math.min(max, n));
    }
    function refresh() {
      var cur = val();
      decBtn.disabled = cur <= min; decBtn.classList.toggle('is-disabled', cur <= min);
      incBtn.disabled = cur >= max; incBtn.classList.toggle('is-disabled', cur >= max);
    }
    function set(n) {
      input.value = Math.max(min, Math.min(max, n));
      refresh();
    }
    function step(delta) {
      var cur = val();
      var next = Math.max(min, Math.min(max, cur + delta));
      if (next !== cur) input.value = next;
      refresh();
    }
    incBtn.addEventListener('click', function (e) { e.preventDefault(); step(1); });
    decBtn.addEventListener('click', function (e) { e.preventDefault(); step(-1); });
    incBtn.addEventListener('mousedown', function (e) { e.preventDefault(); });
    decBtn.addEventListener('mousedown', function (e) { e.preventDefault(); });
    input.addEventListener('input', refresh);
    input.addEventListener('change', function () { set(val()); });
    input.addEventListener('blur', function () { set(val()); });
    refresh();

    return { root: stepper, get: val, input: input };
  }

  /* =========================================================================
     AUTOCOMPLETE FACTORY — fetches REST_BASE + path ("/places",
     "/hotels/places", "/tours/places") → { places:[{label,value,city}] }.
     Degrades to a plain text input when there is no REST base or the endpoint
     404s. `mode` "iata" stores the picked value on dataset.iata, otherwise on
     dataset.dest. Returns { root, input, getValue } where getValue resolves the
     stored code (IATA / dest) or, for flights, a typed 3-letter code fallback.
     ========================================================================= */
  function createAutocomplete(opts) {
    opts = opts || {};
    var mode = opts.mode || 'dest';            // 'iata' | 'dest'
    var path = opts.path || '/places';
    var label = opts.label || '';

    var wrap = el('div', 'hs-ac');
    var input = el('input', 'hs-input hs-ac-input', { type: 'text', autocomplete: 'off', spellcheck: 'false' });
    if (opts.placeholder) input.setAttribute('placeholder', opts.placeholder);
    if (label) input.setAttribute('aria-label', label);
    var listId = nextId('hs-ac');
    var list = el('div', 'hs-ac-list', { id: listId, role: 'listbox' });
    list.hidden = true;
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-expanded', 'false');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-controls', listId);

    wrap.appendChild(input);
    wrap.appendChild(list);

    var enabled = !!REST_BASE;     // becomes false (degrade) on first 404 / error
    var items = [];
    var activeIdx = -1;
    var debTimer = null;
    var reqSeq = 0;

    function clearStored() {
      input.dataset.iata = '';
      input.dataset.dest = '';
    }
    clearStored();

    function setStored(value) {
      if (mode === 'iata') { input.dataset.iata = value || ''; }
      else { input.dataset.dest = value || ''; }
    }

    function closeList() {
      list.hidden = true;
      list.innerHTML = '';
      items = [];
      activeIdx = -1;
      input.setAttribute('aria-expanded', 'false');
      input.removeAttribute('aria-activedescendant');
    }

    function renderList(places) {
      list.innerHTML = '';
      items = places.slice(0, 8);
      if (!items.length) { closeList(); return; }
      items.forEach(function (p, i) {
        var row = el('div', 'hs-ac-item', { role: 'option', id: nextId('hs-ac-opt') });
        var lbl = el('span', 'hs-ac-lbl');
        lbl.textContent = p.label || p.value || '';
        row.appendChild(lbl);
        if (p.value) {
          var code = el('span', 'hs-ac-code');
          code.textContent = p.value;
          row.appendChild(code);
        }
        row.addEventListener('mousedown', function (e) {
          e.preventDefault(); // keep focus, prevent blur-close before pick
          choose(i);
        });
        list.appendChild(row);
      });
      list.hidden = false;
      input.setAttribute('aria-expanded', 'true');
      activeIdx = -1;
    }

    function setActive(i) {
      var rows = list.querySelectorAll('.hs-ac-item');
      if (!rows.length) return;
      if (i < 0) i = rows.length - 1;
      if (i >= rows.length) i = 0;
      activeIdx = i;
      for (var r = 0; r < rows.length; r++) {
        var on = r === i;
        rows[r].classList.toggle('is-active', on);
        if (on) {
          input.setAttribute('aria-activedescendant', rows[r].id);
          rows[r].scrollIntoView({ block: 'nearest' });
        }
      }
    }

    function choose(i) {
      var p = items[i];
      if (!p) return;
      var display = p.label || p.value || '';
      if (p.value && mode === 'iata') display = display + ' (' + p.value + ')';
      input.value = display;
      setStored(p.value || (p.city || ''));
      closeList();
    }

    function fetchPlaces(q) {
      if (!enabled) return;
      var seq = ++reqSeq;
      var url = REST_BASE + path + (path.indexOf('?') >= 0 ? '&' : '?') + 'q=' + encodeURIComponent(q);
      var headers = {};
      if (window.fbdConnect && window.fbdConnect.nonce) headers['X-WP-Nonce'] = window.fbdConnect.nonce;
      fetch(url, { headers: headers, credentials: 'same-origin' }).then(function (res) {
        if (res.status === 404) { enabled = false; closeList(); return null; }
        if (!res.ok) return null;
        return res.json();
      }).then(function (data) {
        if (seq !== reqSeq) return; // a newer keystroke superseded this response
        if (!data) { closeList(); return; }
        var places = (data && Array.isArray(data.places)) ? data.places : [];
        renderList(places);
      }).catch(function () {
        // Network / parse failure → degrade silently to plain text.
        enabled = false;
        closeList();
      });
    }

    input.addEventListener('input', function () {
      clearStored(); // typing invalidates a previously-picked code
      var q = input.value.trim();
      if (!enabled) return;
      if (q.length < 2) { closeList(); return; }
      if (debTimer) clearTimeout(debTimer);
      debTimer = setTimeout(function () { fetchPlaces(q); }, 200);
    });

    input.addEventListener('keydown', function (e) {
      if (list.hidden) return; // let Enter bubble to the panel submit handler
      if (e.key === 'ArrowDown' || e.key === 'Down') { e.preventDefault(); setActive(activeIdx + 1); }
      else if (e.key === 'ArrowUp' || e.key === 'Up') { e.preventDefault(); setActive(activeIdx - 1); }
      else if (e.key === 'Enter') {
        if (activeIdx >= 0) { e.preventDefault(); e.stopPropagation(); choose(activeIdx); }
      } else if (e.key === 'Escape' || e.key === 'Esc') { closeList(); }
    });

    input.addEventListener('blur', function () {
      // Delay so a mousedown-pick on a row can run first.
      setTimeout(closeList, 120);
    });

    return {
      root: wrap,
      input: input,
      // Resolve the value to send in the deep-link.
      getValue: function () {
        if (mode === 'iata') {
          var code = (input.dataset.iata || '').trim();
          if (code) return code.toUpperCase();
          // Accept a typed bare 3-letter code as an IATA fallback.
          var typed = (input.value || '').trim();
          if (/^[A-Za-z]{3}$/.test(typed)) return typed.toUpperCase();
          return '';
        }
        var dest = (input.dataset.dest || '').trim();
        return dest || (input.value || '').trim();
      }
    };
  }

  /* =========================================================================
     SMALL FIELD BUILDER — label + control wrapper.
     ========================================================================= */
  function field(labelText, controlRoot, mods) {
    var f = el('div', 'hs-field' + (mods ? ' ' + mods : ''));
    var lblId = nextId('hs-lbl');
    var lbl = el('div', 'hs-field-lbl', { id: lblId });
    lbl.textContent = labelText;
    var ctl = el('div', 'hs-field-ctl');
    ctl.appendChild(controlRoot);
    f.appendChild(lbl);
    f.appendChild(ctl);
    return { root: f, labelId: lblId };
  }

  /* =========================================================================
     NAVIGATION — build the contract query for a tab and go (or no-op /
     coming-soon when that results page does not exist yet).
     ========================================================================= */
  function buildQuery(pairs) {
    var parts = [];
    pairs.forEach(function (p) {
      // p = [key, value]; always emit the key (results page reads empty too),
      // but skip null/undefined to keep the URL tidy.
      if (p[1] === null || p[1] === undefined) return;
      parts.push(encodeURIComponent(p[0]) + '=' + encodeURIComponent(p[1]));
    });
    return parts.join('&');
  }

  function navigate(baseUrl, query, noteHost) {
    if (!baseUrl) {
      // Results page not created yet → inline "coming soon", no broken nav.
      if (noteHost) {
        noteHost.textContent = T.comingSoon;
        noteHost.hidden = false;
      }
      return;
    }
    var glue = baseUrl.indexOf('?') >= 0 ? '&' : '?';
    window.location.href = query ? (baseUrl + glue + query) : baseUrl;
  }

  /* =========================================================================
     PANEL BUILDERS
     ========================================================================= */

  function flashHint(host, msg) {
    if (!host) return;
    host.textContent = msg;
    host.hidden = false;
  }
  function clearHint(host) { if (host) { host.hidden = true; host.textContent = ''; } }

  /* ---- FLIGHTS ---- */
  function buildFlightsPanel() {
    var panel = el('div', 'hs-panel hs-panel--flights');
    var row = el('div', 'hs-row');

    var fromAc = createAutocomplete({ mode: 'iata', path: '/places', label: T.from, placeholder: T.fromCity });
    var toAc   = createAutocomplete({ mode: 'iata', path: '/places', label: T.to, placeholder: T.toCity });
    var returnDp = createDatePicker({ placeholder: T.chooseDate });
    // Depart drives the Return picker's earliest selectable day (return >= depart).
    var departDp = createDatePicker({
      placeholder: T.chooseDate,
      onChange: function (val) { returnDp.setMin(val); }
    });
    var pax = createStepper({ min: 1, max: 9, def: 1, label: T.travellers });

    var cabin = el('select', 'hs-select', { 'aria-label': T.cabin });
    [['economy', T.cabinEconomy], ['premium_economy', T.cabinPremium], ['business', T.cabinBusiness], ['first', T.cabinFirst]]
      .forEach(function (o) {
        var op = el('option'); op.value = o[0]; op.textContent = o[1]; cabin.appendChild(op);
      });

    var fFrom = field(T.from, fromAc.root);
    var fTo = field(T.to, toAc.root);
    var fDepart = field(T.depart, departDp.root, 'hs-field--date');
    var fReturn = field(T['return'], returnDp.root, 'hs-field--date');
    var fPax = field(T.travellers, pax.root, 'hs-field--pax');
    var fCabin = field(T.cabin, cabin, 'hs-field--cabin');

    row.appendChild(fFrom.root);
    row.appendChild(fTo.root);
    row.appendChild(fDepart.root);
    row.appendChild(fReturn.root);
    row.appendChild(fPax.root);
    row.appendChild(fCabin.root);

    var note = el('div', 'hs-note', { role: 'status', 'aria-live': 'polite' });
    note.hidden = true;

    var submit = el('button', 'hs-submit', { type: 'submit' });
    submit.textContent = T.searchFlights;

    panel.appendChild(row);
    panel.appendChild(note);
    panel.appendChild(submit);

    function go() {
      clearHint(note);
      var from = fromAc.getValue();
      var to = toAc.getValue();
      if (!from || !to) {
        flashHint(note, T.iataHint);
        return;
      }
      var query = buildQuery([
        ['from', from],
        ['to', to],
        ['depart', departDp.getISO()],
        ['return', returnDp.getISO()],
        ['pax', pax.get()],
        ['cabin', cabin.value]
      ]);
      navigate(URLS.flights, query, note);
    }

    return { root: panel, go: go };
  }

  /* ---- HOTELS ---- */
  function buildHotelsPanel() {
    var panel = el('div', 'hs-panel hs-panel--hotels');
    var row = el('div', 'hs-row');

    var destAc = createAutocomplete({ mode: 'dest', path: '/hotels/places', label: T.destination, placeholder: T.destination });
    var checkoutDp = createDatePicker({ placeholder: T.chooseDate });
    var checkinDp = createDatePicker({
      placeholder: T.chooseDate,
      onChange: function (val) { checkoutDp.setMin(val); }
    });
    var guests = createStepper({ min: 1, max: 9, def: 2, label: T.guests });
    var rooms = createStepper({ min: 1, max: 8, def: 1, label: T.rooms });

    row.appendChild(field(T.destination, destAc.root, 'hs-field--dest').root);
    row.appendChild(field(T.checkin, checkinDp.root, 'hs-field--date').root);
    row.appendChild(field(T.checkout, checkoutDp.root, 'hs-field--date').root);
    row.appendChild(field(T.guests, guests.root, 'hs-field--pax').root);
    row.appendChild(field(T.rooms, rooms.root, 'hs-field--pax').root);

    var note = el('div', 'hs-note', { role: 'status', 'aria-live': 'polite' });
    note.hidden = true;

    var submit = el('button', 'hs-submit', { type: 'submit' });
    submit.textContent = T.searchHotels;

    panel.appendChild(row);
    panel.appendChild(note);
    panel.appendChild(submit);

    function go() {
      clearHint(note);
      var query = buildQuery([
        ['dest', destAc.getValue()],
        ['checkin', checkinDp.getISO()],
        ['checkout', checkoutDp.getISO()],
        ['guests', guests.get()],
        ['rooms', rooms.get()]
      ]);
      navigate(URLS.hotels, query, note);
    }

    return { root: panel, go: go };
  }

  /* ---- TOURS ---- */
  function buildToursPanel() {
    var panel = el('div', 'hs-panel hs-panel--tours');
    var row = el('div', 'hs-row');

    var destAc = createAutocomplete({ mode: 'dest', path: '/tours/places', label: T.destination, placeholder: T.destination });
    var dateDp = createDatePicker({ placeholder: T.chooseDate });
    var guests = createStepper({ min: 1, max: 20, def: 2, label: T.guests });

    row.appendChild(field(T.destination, destAc.root, 'hs-field--dest').root);
    row.appendChild(field(T.date, dateDp.root, 'hs-field--date').root);
    row.appendChild(field(T.guests, guests.root, 'hs-field--pax').root);

    var note = el('div', 'hs-note', { role: 'status', 'aria-live': 'polite' });
    note.hidden = true;

    var submit = el('button', 'hs-submit', { type: 'submit' });
    submit.textContent = T.searchTours;

    panel.appendChild(row);
    panel.appendChild(note);
    panel.appendChild(submit);

    function go() {
      clearHint(note);
      var query = buildQuery([
        ['dest', destAc.getValue()],
        ['date', dateDp.getISO()],
        ['pax', guests.get()],
        ['cat', ''] // cat stays empty
      ]);
      navigate(URLS.tours, query, note);
    }

    return { root: panel, go: go };
  }

  /* =========================================================================
     TABS — build the tablist + panels and wire roving-tabindex a11y.
     ========================================================================= */
  var TABS = [
    { key: 'flights', label: T.flights, build: buildFlightsPanel },
    { key: 'hotels',  label: T.hotels,  build: buildHotelsPanel },
    { key: 'tours',   label: T.tours,   build: buildToursPanel }
  ];

  var root = el('div', 'hs-tabs');

  var tablist = el('div', 'hs-tablist', { role: 'tablist', 'aria-label': T.choose });
  var panelsWrap = el('div', 'hs-panels');

  var tabBtns = [];
  var panels = [];

  TABS.forEach(function (cfg, i) {
    var tabId = nextId('hs-tab');
    var panelId = nextId('hs-panel');

    var tab = el('button', 'hs-tab', {
      type: 'button',
      role: 'tab',
      id: tabId,
      'aria-controls': panelId,
      'aria-selected': i === 0 ? 'true' : 'false',
      tabindex: i === 0 ? '0' : '-1'
    });
    tab.textContent = cfg.label;
    tablist.appendChild(tab);
    tabBtns.push(tab);

    var built = cfg.build();
    var panel = built.root;
    panel.id = panelId;
    panel.setAttribute('role', 'tabpanel');
    panel.setAttribute('aria-labelledby', tabId);
    panel.setAttribute('tabindex', '0');
    if (i !== 0) { panel.hidden = true; }
    else { panel.classList.add('is-active'); tab.classList.add('is-active'); }
    panelsWrap.appendChild(panel);
    panels.push({ panel: panel, go: built.go });

    // Submit on the panel button.
    panel.addEventListener('submit', function (e) { e.preventDefault(); });
    panel.querySelectorAll('.hs-submit').forEach(function (btn) {
      btn.addEventListener('click', function (e) { e.preventDefault(); built.go(); });
    });

    // Enter inside any field of this panel submits the panel (unless an
    // autocomplete list consumed it — those stopPropagation on pick).
    panel.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter') return;
      var tag = (e.target && e.target.tagName) || '';
      if (e.defaultPrevented) return;
      // Don't hijack Enter on the submit button itself (native click handles it),
      // and skip when typing in a textarea (none here, but safe).
      if (e.target && e.target.classList && e.target.classList.contains('hs-submit')) return;
      if (tag === 'BUTTON') return; // datepicker nav/day buttons handle their own
      e.preventDefault();
      built.go();
    });
  });

  function activate(idx, focus) {
    if (idx < 0) idx = TABS.length - 1;
    if (idx >= TABS.length) idx = 0;
    tabBtns.forEach(function (tab, i) {
      var on = i === idx;
      tab.setAttribute('aria-selected', on ? 'true' : 'false');
      tab.setAttribute('tabindex', on ? '0' : '-1');
      tab.classList.toggle('is-active', on);
    });
    panels.forEach(function (p, i) {
      var on = i === idx;
      p.panel.hidden = !on;
      p.panel.classList.toggle('is-active', on);
    });
    if (focus && tabBtns[idx]) tabBtns[idx].focus();
  }

  tabBtns.forEach(function (tab, i) {
    tab.addEventListener('click', function () { activate(i, false); });
    tab.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight' || e.key === 'Right') { e.preventDefault(); activate(i + 1, true); }
      else if (e.key === 'ArrowLeft' || e.key === 'Left') { e.preventDefault(); activate(i - 1, true); }
      else if (e.key === 'Home') { e.preventDefault(); activate(0, true); }
      else if (e.key === 'End') { e.preventDefault(); activate(TABS.length - 1, true); }
    });
  });

  root.appendChild(tablist);
  root.appendChild(panelsWrap);

  /* ---- swap in: replace the static .bookbar content with the tabbed UI ---- */
  bar.innerHTML = '';
  bar.classList.add('bookbar--tabs');
  bar.appendChild(root);

  /* =========================================================================
     "Plan My Trip" #contact fix (UNCHANGED) — a hero button to #contact
     that has no real target on the page is rerouted to the contact page.
     ========================================================================= */
  var planBtn = document.querySelector('.hero a[href="#contact"], .hero a[href$="#contact"]');
  if (planBtn && !document.getElementById('contact') && D.contactUrl) {
    planBtn.setAttribute('href', D.contactUrl);
  }
})();
