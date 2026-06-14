/* ===========================================================
   LuwiPress Amber — tour booking box
   Calendar date-picker + pax stepper + live total + date guard.
   Scoped PER .book-box (Elementor lets instances repeat) — never
   getElementById. Null-guarded so it's safe on any page.
   =========================================================== */
(function () {
  'use strict';
  var $  = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  var DOW = ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'];
  var MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July',
                'August', 'September', 'October', 'November', 'December'];

  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function iso(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
  function fmtMoney(n) { return n.toLocaleString(undefined, { maximumFractionDigits: 2 }); }

  function initBox(box) {
    var perPerson = parseFloat(box.getAttribute('data-per-person')) || 0;
    var min = parseInt(box.getAttribute('data-min'), 10) || 1;
    var max = parseInt(box.getAttribute('data-max'), 10) || 99;
    var currency = box.getAttribute('data-currency') || '';

    var qval   = $('.qval', box);
    var paxIn  = $('.bb-pax', box);
    var totalEl = $('.t-amt', box);
    var addons = $$('.bb-addon input[type="checkbox"]', box);

    function pax() { return parseInt(qval ? qval.textContent : (paxIn ? paxIn.value : min), 10) || min; }

    function recalc() {
      if (!totalEl) return;
      var p = pax();
      var t = perPerson * p;
      addons.forEach(function (cb) {
        if (cb.checked) { t += (parseFloat(cb.getAttribute('data-cost')) || 0) * p; }
      });
      totalEl.textContent = currency + fmtMoney(t);
    }

    /* pax stepper */
    var minus = $('.qminus', box), plus = $('.qplus', box);
    function setPax(n) {
      n = Math.max(min, Math.min(max, n));
      if (qval) qval.textContent = n;
      if (paxIn) paxIn.value = n;
      recalc();
    }
    minus && minus.addEventListener('click', function () { setPax(pax() - 1); });
    plus  && plus.addEventListener('click', function () { setPax(pax() + 1); });
    addons.forEach(function (cb) { cb.addEventListener('change', recalc); });
    recalc();

    /* date picker */
    var dp = $('.datepick', box);
    var picker = dp ? initDatePicker(dp, recalc) : null;

    /* Prefill from URL (?fbd_pax= & ?fbd_date=YYYY-MM-DD) — set by the hero
       "tour search" so a visitor lands on the tour with guests + date already
       filled and only checkout left. Values are clamped/validated; anything
       invalid or in the past is ignored. */
    try {
      var qs = new URLSearchParams(window.location.search);
      var paxQ = parseInt(qs.get('fbd_pax'), 10);
      if (!isNaN(paxQ)) setPax(paxQ);
      var dateQ = qs.get('fbd_date');
      if (picker && dateQ && /^\d{4}-\d{2}-\d{2}$/.test(dateQ)) {
        var dp_ = dateQ.split('-');
        var dsel = new Date(+dp_[0], +dp_[1] - 1, +dp_[2]);
        dsel.setHours(0, 0, 0, 0);
        var t0 = new Date(); t0.setHours(0, 0, 0, 0);
        if (!isNaN(dsel.getTime()) && dsel >= t0) picker.pick(dsel);
      }
    } catch (e) {}

    /* date-required guard on reserve / form submit */
    var form = box.closest('form');
    var dateIn = $('.bb-date', box);
    function guard(e) {
      if (dateIn && !dateIn.value) {
        if (e) e.preventDefault();
        dp && dp.classList.add('dp-error');
        var trig = dp && $('.dp-trigger', dp);
        trig && trig.focus();
        return false;
      }
      return true;
    }
    var reserve = $('.bb-reserve', box);
    reserve && reserve.addEventListener('click', guard);
    if (form) form.addEventListener('submit', guard);
  }

  function initDatePicker(dp, onChange) {
    var trigger = $('.dp-trigger', dp);
    var pop     = $('.dp-pop', dp);
    var valEl   = $('.dp-val', dp);
    var title   = $('.dp-title', dp);
    var daysEl  = $('.dp-days', dp);
    var hidden  = $('.bb-date', dp);
    var prevB   = $('.dp-prev', dp);
    var nextB   = $('.dp-next', dp);
    var todayB  = $('.dp-today', dp);
    var clearB  = $('.dp-clear', dp);
    if (!trigger || !pop || !daysEl) return;

    var today = new Date(); today.setHours(0, 0, 0, 0);
    var view = new Date(today.getFullYear(), today.getMonth(), 1);
    var selected = null;

    function render() {
      if (title) title.textContent = MONTHS[view.getMonth()] + ' ' + view.getFullYear();
      daysEl.innerHTML = '';
      var first = new Date(view.getFullYear(), view.getMonth(), 1);
      // Monday-first offset
      var offset = (first.getDay() + 6) % 7;
      var daysInMonth = new Date(view.getFullYear(), view.getMonth() + 1, 0).getDate();
      var i;
      for (i = 0; i < offset; i++) {
        var blank = document.createElement('span');
        blank.className = 'dp-day dp-blank';
        daysEl.appendChild(blank);
      }
      for (i = 1; i <= daysInMonth; i++) {
        var d = new Date(view.getFullYear(), view.getMonth(), i);
        var cell = document.createElement('button');
        cell.type = 'button';
        cell.className = 'dp-day';
        cell.textContent = i;
        if (d.getTime() === today.getTime()) cell.classList.add('today');
        if (selected && d.getTime() === selected.getTime()) cell.classList.add('selected');
        if (d < today) {
          cell.disabled = true;
          cell.classList.add('disabled');
        } else {
          (function (dd) {
            cell.addEventListener('click', function () { pick(dd); });
          })(d);
        }
        daysEl.appendChild(cell);
      }
      // disable prev when viewing current month
      if (prevB) {
        var atStart = view.getFullYear() === today.getFullYear() && view.getMonth() === today.getMonth();
        prevB.disabled = atStart;
      }
    }

    function pick(d) {
      selected = d;
      if (hidden) hidden.value = iso(d);
      if (valEl) {
        valEl.textContent = d.toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
        valEl.classList.remove('placeholder');
      }
      dp.classList.remove('dp-error');
      close();
      render();
      onChange && onChange();
    }

    function open() { pop.classList.add('open'); dp.classList.add('open'); render(); }
    function close() { pop.classList.remove('open'); dp.classList.remove('open'); }

    trigger.addEventListener('click', function (e) {
      e.stopPropagation();
      pop.classList.contains('open') ? close() : open();
    });
    prevB && prevB.addEventListener('click', function () { view.setMonth(view.getMonth() - 1); render(); });
    nextB && nextB.addEventListener('click', function () { view.setMonth(view.getMonth() + 1); render(); });
    todayB && todayB.addEventListener('click', function () { pick(new Date(today)); });
    clearB && clearB.addEventListener('click', function () {
      selected = null;
      if (hidden) hidden.value = '';
      if (valEl) { valEl.textContent = valEl.getAttribute('data-placeholder') || 'Select a date'; valEl.classList.add('placeholder'); }
      close(); render();
    });
    document.addEventListener('click', function (e) {
      if (!dp.contains(e.target)) close();
    });

    render();

    return { pick: pick };
  }

  $$('.book-box').forEach(initBox);
})();
