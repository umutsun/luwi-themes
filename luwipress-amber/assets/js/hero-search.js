/* ===========================================================
   LuwiPress Amber — hero "tour search"
   Upgrades the static .bookbar into a real tour finder:
   Experience (a bookable tour) + Date + Guests → Go → that
   tour's page with ?fbd_date & ?fbd_pax prefilled, so the
   visitor only has to check out. booking.js reads the params.

   Progressive enhancement: the static markup is the no-JS
   fallback; we replace each field's value in place. Scoped,
   null-guarded, safe if the hero markup ever changes.
   =========================================================== */
(function () {
  'use strict';

  var D = window.LWP_HERO;
  if (!D || !D.tours || !D.tours.length) return;

  var bar = document.querySelector('.hero .bookbar') || document.querySelector('.bookbar');
  if (!bar) return;

  var fields = bar.querySelectorAll('.field');
  if (fields.length < 3) return; // unexpected markup — leave the static bar alone

  var expVal   = fields[0].querySelector('.val');
  var dateVal  = fields[1].querySelector('.val');
  var guestVal = fields[2].querySelector('.val');
  if (!expVal || !dateVal || !guestVal) return;

  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function isoToday() { var d = new Date(); return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }

  /* ---- Experience: a real <select> of bookable tours ---- */
  var sel = document.createElement('select');
  sel.className = 'hs-exp';
  D.tours.forEach(function (t, i) {
    var o = document.createElement('option');
    o.value = String(i);
    o.textContent = t.label;
    sel.appendChild(o);
  });
  expVal.innerHTML = '';
  expVal.appendChild(sel);

  /* ---- Date: native date input, no past dates ---- */
  var dateIn = document.createElement('input');
  dateIn.type = 'date';
  dateIn.className = 'hs-date';
  dateIn.min = isoToday();
  dateVal.innerHTML = '';
  dateVal.appendChild(dateIn);

  // The native calendar indicator is easy to miss on a dark bar, so open the
  // picker on any click in the Date field. showPicker() is the reliable
  // cross-browser trigger (Chrome 99+/FF 101+/Safari 16+); wrapped in try/catch
  // because it throws outside a user gesture, with the native click as fallback.
  function openDatePicker() { try { if (dateIn.showPicker) dateIn.showPicker(); } catch (e) {} }
  dateIn.addEventListener('click', openDatePicker);
  dateVal.addEventListener('click', function (e) { if (e.target !== dateIn) openDatePicker(); });

  /* ---- Guests: number input bounded to the selected tour's pax range ---- */
  var paxIn = document.createElement('input');
  paxIn.type = 'number';
  paxIn.className = 'hs-pax';
  paxIn.step = '1';
  guestVal.innerHTML = '';
  guestVal.appendChild(paxIn);

  function currentTour() { return D.tours[parseInt(sel.value, 10)] || D.tours[0]; }

  function applyBounds() {
    var t = currentTour();
    var mn = t.min || 1, mx = t.max || 20;
    paxIn.min = mn;
    paxIn.max = mx;
    var cur = parseInt(paxIn.value, 10);
    if (isNaN(cur)) cur = t.def || mn;
    paxIn.value = Math.max(mn, Math.min(mx, cur));
  }
  applyBounds();
  sel.addEventListener('change', applyBounds);

  /* ---- Go → tour page with prefilled date + guests ---- */
  function go() {
    var t = currentTour();
    if (!t || !t.url) return;
    var params = [];
    var dv = (dateIn.value || '').trim();
    if (/^\d{4}-\d{2}-\d{2}$/.test(dv)) params.push('fbd_date=' + encodeURIComponent(dv));
    var pv = parseInt(paxIn.value, 10);
    if (!isNaN(pv) && pv > 0) params.push('fbd_pax=' + pv);
    var glue = t.url.indexOf('?') >= 0 ? '&' : '?';
    window.location.href = params.length ? (t.url + glue + params.join('&')) : t.url;
  }

  var goBtn = bar.querySelector('.go');
  if (goBtn) {
    if (goBtn.tagName === 'BUTTON' && !goBtn.getAttribute('type')) goBtn.setAttribute('type', 'button');
    goBtn.addEventListener('click', function (e) { e.preventDefault(); go(); });
  }
  [sel, dateIn, paxIn].forEach(function (el) {
    el.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); go(); } });
  });

  /* "Plan My Trip" hero button links to #contact, which may not exist on the
     page (dead click). If there's no real #contact target, route it to the
     contact page so the button actually does something. A real #contact anchor
     is left untouched. */
  var planBtn = document.querySelector('.hero a[href="#contact"], .hero a[href$="#contact"]');
  if (planBtn && !document.getElementById('contact') && D.contactUrl) {
    planBtn.setAttribute('href', D.contactUrl);
  }
})();
