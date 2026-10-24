/* TOÀN STORE admin — small vanilla helpers (Bootstrap 5 bundle loaded before this file). */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  /* Collapse / expand the desktop sidebar (remembered per browser). */
  $$('[data-ad-collapse]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var on = document.documentElement.classList.toggle('ad-collapsed');
      try { localStorage.setItem('ad-collapsed', on ? '1' : '0'); } catch (e) {}
    });
  });

  /* "/" focuses the top search box. */
  document.addEventListener('keydown', function (e) {
    if (e.key !== '/' || e.ctrlKey || e.metaKey) return;
    var t = e.target;
    if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.tagName === 'SELECT' || t.isContentEditable)) return;
    var s = $('.ad-search input');
    if (s) { e.preventDefault(); s.focus(); s.select(); }
  });

  /* Toasts: auto-hide after 5s, close button. */
  function dismiss(t) { if (!t || t.classList.contains('is-leaving')) return; t.classList.add('is-leaving'); setTimeout(function () { t.remove(); }, 300); }
  $$('.ad-toast').forEach(function (t, i) {
    var b = $('[data-toast-close]', t);
    if (b) b.addEventListener('click', function () { dismiss(t); });
    setTimeout(function () { dismiss(t); }, 5000 + i * 400);
  });

  /* Confirm dialog for any form/link with data-confirm="message". */
  var modalEl = $('#adConfirm');
  var pending = null;
  if (modalEl && window.bootstrap) {
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    var text = $('[data-confirm-text]', modalEl);
    var ok = $('[data-confirm-ok]', modalEl);
    document.addEventListener('submit', function (e) {
      var f = e.target;
      if (!f.hasAttribute || !f.hasAttribute('data-confirm') || f.dataset.confirmed === '1') return;
      e.preventDefault();
      pending = f;
      text.textContent = f.getAttribute('data-confirm') || 'Thao tác này không thể hoàn tác.';
      ok.textContent = f.getAttribute('data-confirm-ok') || 'Xác nhận';
      modal.show();
    }, true);
    ok.addEventListener('click', function () {
      if (!pending) return;
      pending.dataset.confirmed = '1';
      modal.hide();
      var f = pending; pending = null;
      f.requestSubmit ? f.requestSubmit() : f.submit();
    });
    modalEl.addEventListener('shown.bs.modal', function () { ok.focus(); });
  }

  /* Clickable table rows (ignores clicks on buttons/links/forms inside). */
  $$('tr[data-href]').forEach(function (tr) {
    tr.addEventListener('click', function (e) {
      if (e.target.closest('a, button, form, input, select, label')) return;
      if (e.ctrlKey || e.metaKey) { window.open(tr.dataset.href, '_blank'); return; }
      window.location.href = tr.dataset.href;
    });
  });

  /* File inputs inside .ad-drop: drag state + instant previews. */
  $$('.ad-drop').forEach(function (drop) {
    var input = $('input[type=file]', drop);
    var preview = $('.ad-drop__preview', drop);
    var label = $('[data-drop-label]', drop);
    if (!input) return;
    ['dragenter', 'dragover'].forEach(function (ev) { drop.addEventListener(ev, function () { drop.classList.add('is-over'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function () { drop.classList.remove('is-over'); }); });
    input.addEventListener('change', function () {
      var files = Array.prototype.slice.call(input.files || []);
      if (label) label.textContent = files.length ? files.length + ' ảnh đã chọn' : label.getAttribute('data-drop-label');
      if (!preview) return;
      preview.innerHTML = '';
      files.slice(0, 12).forEach(function (f) {
        if (!/^image\//.test(f.type)) return;
        var img = document.createElement('img');
        img.alt = f.name;
        img.src = URL.createObjectURL(f);
        preview.appendChild(img);
      });
    });
  });

  /* Client-side quick filter for small tables: <input data-table-filter="#tableId">. */
  $$('[data-table-filter]').forEach(function (inp) {
    var table = $(inp.getAttribute('data-table-filter'));
    if (!table) return;
    var norm = function (s) { return String(s).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd'); };
    inp.addEventListener('input', function () {
      var q = norm(inp.value.trim());
      $$('tbody tr', table).forEach(function (tr) { tr.hidden = q !== '' && norm(tr.textContent).indexOf(q) === -1; });
    });
  });

  /* Auto-submit filter selects. */
  $$('form[data-autosubmit] select, form[data-autosubmit] input[type=date]').forEach(function (el) {
    el.addEventListener('change', function () { el.form.requestSubmit ? el.form.requestSubmit() : el.form.submit(); });
  });

  /* Price fields: show the formatted value under the input. */
  $$('[data-money]').forEach(function (inp) {
    var out = document.createElement('div');
    out.className = 'form-text';
    inp.insertAdjacentElement('afterend', out);
    var upd = function () {
      var v = parseFloat(String(inp.value).replace(/[^\d.]/g, ''));
      out.textContent = isNaN(v) || !v ? '' : '= ' + Math.round(v).toLocaleString('vi-VN') + ' ₫';
    };
    inp.addEventListener('input', upd); upd();
  });
})();
