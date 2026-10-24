/* TOÀN STORE — storefront behaviours: category menu, search suggest, product rails,
   gallery, detail tabs, sticky buy bar, listing filters. Vanilla JS, no dependencies. */
(function () {
  'use strict';

  var $ = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
  var fmt = function (n) { return Math.round(n).toLocaleString('vi-VN') + '₫'; };
  var esc = function (s) { return String(s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); };

  /* ---------- Header "Danh mục" dropdown ---------- */
  var catMenu = $('.gv-catmenu');
  if (catMenu) {
    var btn = $('.gv-catmenu__btn', catMenu);
    var backdrop = $('.gv-catmenu__backdrop');
    var hoverTimer = null;
    var setOpen = function (open) {
      catMenu.classList.toggle('is-open', open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (backdrop) backdrop.hidden = !open;
    };
    btn.addEventListener('click', function () { setOpen(!catMenu.classList.contains('is-open')); });
    catMenu.addEventListener('mouseenter', function () { clearTimeout(hoverTimer); hoverTimer = setTimeout(function () { setOpen(true); }, 120); });
    catMenu.addEventListener('mouseleave', function () { clearTimeout(hoverTimer); hoverTimer = setTimeout(function () { setOpen(false); }, 220); });
    if (backdrop) backdrop.addEventListener('click', function () { setOpen(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
  }

  /* Mega-menu rows: keyboard users open the flyout with focus */
  $$('.gv-mm__item').forEach(function (li) {
    li.addEventListener('focusin', function () { li.classList.add('is-active'); });
    li.addEventListener('focusout', function (e) { if (!li.contains(e.relatedTarget)) li.classList.remove('is-active'); });
  });

  /* ---------- Search suggestions ---------- */
  $$('form.gv-search[data-suggest]').forEach(function (form) {
    var input = $('input[name="keyword"]', form);
    var box = $('.gv-suggest', form);
    var endpoint = form.getAttribute('data-suggest');
    var timer = null, ctrl = null, active = -1;

    var close = function () { box.hidden = true; active = -1; };
    var items = function () { return $$('.gv-suggest__item', box); };
    var highlight = function (i) {
      var list = items();
      list.forEach(function (el, k) { el.classList.toggle('is-active', k === i); });
      active = i;
    };

    var render = function (data, q) {
      if (!data.length) {
        box.innerHTML = '<div class="gv-suggest__empty">Không có gợi ý cho “' + esc(q) + '”</div>';
      } else {
        box.innerHTML = data.map(function (p) {
          var old = p.priceOld ? '<s>' + fmt(p.priceOld) + '</s>' : '';
          return '<a class="gv-suggest__item" role="option" href="' + esc(p.url) + '">' +
            '<img src="/uploads/sanpham/' + encodeURIComponent(p.photo) + '" alt="">' +
            '<span><b>' + esc(p.name) + '</b><em>' + fmt(p.price) + ' ' + old + '</em></span></a>';
        }).join('') + '<button type="submit" class="gv-suggest__all">Xem tất cả kết quả cho “' + esc(q) + '” <i class="bi bi-arrow-right"></i></button>';
      }
      box.hidden = false;
      active = -1;
    };

    input.addEventListener('input', function () {
      var q = input.value.trim();
      clearTimeout(timer);
      if (q.length < 2) { close(); return; }
      timer = setTimeout(function () {
        if (ctrl) ctrl.abort();
        ctrl = 'AbortController' in window ? new AbortController() : null;
        fetch(endpoint + '?keyword=' + encodeURIComponent(q), { signal: ctrl ? ctrl.signal : undefined })
          .then(function (r) { return r.json(); })
          .then(function (d) { if (input.value.trim() === q) render(d, q); })
          .catch(function () {});
      }, 180);
    });
    input.addEventListener('keydown', function (e) {
      var list = items();
      if (box.hidden || !list.length) return;
      if (e.key === 'ArrowDown') { e.preventDefault(); highlight((active + 1) % list.length); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); highlight((active - 1 + list.length) % list.length); }
      else if (e.key === 'Enter' && active >= 0) { e.preventDefault(); window.location.href = list[active].href; }
      else if (e.key === 'Escape') { close(); }
    });
    input.addEventListener('focus', function () { if (box.innerHTML && input.value.trim().length >= 2) box.hidden = false; });
    document.addEventListener('click', function (e) { if (!form.contains(e.target)) close(); });
  });

  /* ---------- Horizontal product rails ---------- */
  $$('[data-rail]').forEach(function (rail) {
    var track = $('[data-rail-track]', rail);
    var prev = $('[data-rail-prev]', rail);
    var next = $('[data-rail-next]', rail);
    if (!track) return;
    var update = function () {
      var max = track.scrollWidth - track.clientWidth - 2;
      if (prev) prev.disabled = track.scrollLeft <= 2;
      if (next) next.disabled = track.scrollLeft >= max;
      rail.classList.toggle('is-static', max <= 0);
    };
    var step = function (dir) { track.scrollBy({ left: dir * Math.max(track.clientWidth * 0.85, 220), behavior: 'smooth' }); };
    if (prev) prev.addEventListener('click', function () { step(-1); });
    if (next) next.addEventListener('click', function () { step(1); });
    track.addEventListener('scroll', function () { window.requestAnimationFrame(update); }, { passive: true });
    window.addEventListener('resize', update);
    update();
  });

  /* ---------- Product gallery ---------- */
  var gallery = $('[data-gallery]');
  if (gallery) {
    var main = $('#mainImg');
    var thumbs = $$('.pdg__thumb', gallery);
    var indexEl = $('[data-gallery-index]', gallery);
    var current = 0;
    var show = function (i) {
      if (!thumbs.length) return;
      current = (i + thumbs.length) % thumbs.length;
      main.style.opacity = '0';
      setTimeout(function () { main.src = thumbs[current].getAttribute('data-full'); main.style.opacity = '1'; }, 120);
      thumbs.forEach(function (t, k) { t.classList.toggle('is-on', k === current); });
      thumbs[current].scrollIntoView({ block: 'nearest', inline: 'nearest' });
      if (indexEl) indexEl.textContent = current + 1;
    };
    thumbs.forEach(function (t, k) { t.addEventListener('click', function () { show(k); }); });
    var p = $('[data-gallery-prev]', gallery), n = $('[data-gallery-next]', gallery);
    if (p) p.addEventListener('click', function (e) { e.stopPropagation(); show(current - 1); });
    if (n) n.addEventListener('click', function (e) { e.stopPropagation(); show(current + 1); });

    // swipe on touch screens
    var sx = null;
    main.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; }, { passive: true });
    main.addEventListener('touchend', function (e) {
      if (sx === null) return;
      var dx = e.changedTouches[0].clientX - sx;
      if (Math.abs(dx) > 40) show(current + (dx < 0 ? 1 : -1));
      sx = null;
    });

    // lightbox
    var box = $('#lightbox'), boxImg = $('#lightboxImg');
    if (box && boxImg) {
      main.addEventListener('click', function () { boxImg.src = main.src; box.classList.add('open'); });
      box.addEventListener('click', function () { box.classList.remove('open'); });
      document.addEventListener('keydown', function (e) {
        if (!box.classList.contains('open')) return;
        if (e.key === 'Escape') box.classList.remove('open');
        if (e.key === 'ArrowRight') { show(current + 1); boxImg.src = thumbs[current] ? thumbs[current].getAttribute('data-full') : main.src; }
        if (e.key === 'ArrowLeft') { show(current - 1); boxImg.src = thumbs[current] ? thumbs[current].getAttribute('data-full') : main.src; }
      });
    }
  }

  /* ---------- Detail tabs ---------- */
  var tabs = $('#pdTabs');
  if (tabs) {
    var activate = function (name, scroll) {
      $$('[data-tab]', tabs).forEach(function (b) {
        var on = b.getAttribute('data-tab') === name;
        b.classList.toggle('is-on', on);
        b.setAttribute('aria-selected', on ? 'true' : 'false');
      });
      $$('[data-panel]', tabs).forEach(function (p) {
        var on = p.getAttribute('data-panel') === name;
        p.classList.toggle('is-on', on);
        p.hidden = !on;
      });
      if (scroll) {
        var top = tabs.getBoundingClientRect().top + window.pageYOffset - 130;
        window.scrollTo({ top: top, behavior: 'smooth' });
      }
    };
    $$('[data-tab]', tabs).forEach(function (b) { b.addEventListener('click', function () { activate(b.getAttribute('data-tab'), false); }); });
    $$('[data-tab-link]').forEach(function (a) {
      a.addEventListener('click', function (e) { e.preventDefault(); activate(a.getAttribute('data-tab-link'), true); });
    });
    var h = (location.hash || '').replace('#tab-', '');
    if (h && $('[data-panel="' + h + '"]', tabs)) activate(h, true);
  }

  /* ---------- Quantity stepper (mirrors into hidden qty inputs) ---------- */
  var qty = $('#qty');
  if (qty) {
    var sync = function () {
      var max = parseInt(qty.getAttribute('max') || '99', 10);
      var v = Math.min(max, Math.max(1, parseInt(qty.value || '1', 10) || 1));
      qty.value = v;
      $$('.qty-mirror').forEach(function (m) { m.value = v; });
    };
    $$('[data-qty]').forEach(function (b) {
      b.addEventListener('click', function () { qty.value = (parseInt(qty.value || '1', 10) || 1) + parseInt(b.getAttribute('data-qty'), 10); sync(); });
    });
    qty.addEventListener('change', sync);
  }

  /* ---------- Sticky buy bar ---------- */
  var sticky = $('#pdSticky'), buy = $('.pd-buy');
  if (sticky && buy && 'IntersectionObserver' in window) {
    new IntersectionObserver(function (entries) {
      var hidden = !entries[0].isIntersecting && entries[0].boundingClientRect.top < 0;
      sticky.classList.toggle('is-shown', hidden);
      sticky.setAttribute('aria-hidden', hidden ? 'false' : 'true');
      document.body.classList.toggle('has-pd-sticky', hidden);
    }, { threshold: 0 }).observe(buy);
  }

  /* ---------- Share ---------- */
  $$('[data-share]').forEach(function (b) {
    b.addEventListener('click', function () {
      var url = b.getAttribute('data-share');
      if (navigator.share) { navigator.share({ title: document.title, url: url }).catch(function () {}); return; }
      if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(function () {
          var t = $('#cart-toast'), body = $('#cart-toast-body');
          if (t && body && window.bootstrap) { body.textContent = 'Đã sao chép liên kết sản phẩm.'; t.classList.remove('bg-danger'); t.classList.add('bg-success'); bootstrap.Toast.getOrCreateInstance(t, { delay: 2000 }).show(); }
        });
      }
    });
  });

  /* ---------- Listing filters: apply on change ---------- */
  $$('form[data-autosubmit]').forEach(function (form) {
    var submit = function () { form.requestSubmit ? form.requestSubmit() : form.submit(); };
    form.addEventListener('change', function (e) {
      var t = e.target;
      if (t.hasAttribute('data-manual-price')) {
        // typing a custom range overrides the preset buttons
        var none = form.querySelector('input[name="price"][value=""]');
        if (none) none.checked = true;
        return; // wait for "Áp dụng" / Enter
      }
      if (t.name === 'price') {
        $$('[data-manual-price]', form).forEach(function (i) { i.value = ''; });
      }
      // On desktop apply immediately; inside the mobile drawer wait for "Áp dụng".
      if (window.matchMedia('(min-width: 992px)').matches) submit();
    });
    // Strip empty params so URLs stay clean.
    form.addEventListener('submit', function () {
      $$('input, select', form).forEach(function (el) {
        if ((el.type === 'number' || el.type === 'hidden' || el.type === 'radio') && el.value === '') el.disabled = true;
      });
    });
  });
})();
