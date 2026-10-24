/* TECHSTORE — UX enhancement layer (vanilla JS, no dependencies) */
(function () {
  'use strict';
  var docEl = document.documentElement;
  docEl.classList.add('js');

  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- Top navigation progress bar ---------- */
  var bar = document.getElementById('ts-progress');
  var progTimer = null;
  function startProgress() {
    if (!bar) return;
    bar.classList.add('active');
    bar.style.width = '0%';
    var w = 0;
    clearInterval(progTimer);
    // rAF to ensure the width transition kicks in
    requestAnimationFrame(function () { bar.style.width = '18%'; });
    progTimer = setInterval(function () {
      w += (85 - w) * 0.12;
      bar.style.width = w.toFixed(1) + '%';
    }, 220);
  }
  function doneProgress() {
    if (!bar) return;
    clearInterval(progTimer);
    bar.style.width = '100%';
    setTimeout(function () {
      bar.classList.remove('active');
      bar.style.width = '0%';
    }, 350);
  }
  // Start on real navigations (internal links + navigating forms)
  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('a');
    if (!a) return;
    var href = a.getAttribute('href') || '';
    if (a.target === '_blank' || a.hasAttribute('download')) return;
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1) return;
    if (href === '' || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) return;
    if (a.hasAttribute('data-bs-toggle')) return;
    if (a.host && a.host !== window.location.host) return;
    startProgress();
  }, true);
  window.addEventListener('pageshow', doneProgress);
  window.addEventListener('beforeunload', startProgress);

  /* ---------- Button loading state on navigating form submit ---------- */
  var AJAX_FORMS = ['add-to-cart-form', 'wishlist-form', 'compare-form'];
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    for (var i = 0; i < AJAX_FORMS.length; i++) {
      if (form.classList.contains(AJAX_FORMS[i])) return; // handled by AJAX elsewhere
    }
    if (form.hasAttribute('data-no-loading')) return;
    var btn = e.submitter || form.querySelector('button[type="submit"], button:not([type])');
    if (btn && !btn.disabled) {
      btn.classList.add('is-loading');
      // keep width stable while swapping label for spinner
      btn.style.minWidth = btn.offsetWidth + 'px';
      btn.setAttribute('aria-busy', 'true');
      setTimeout(function () { btn.disabled = true; }, 0);
      startProgress();
    }
  });

  /* ---------- Back-to-top ---------- */
  var toTop = document.getElementById('ts-totop');
  if (toTop) {
    var onScroll = function () {
      if (window.pageYOffset > 400) toTop.classList.add('show');
      else toTop.classList.remove('show');
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
    toTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
    });
  }

  /* ---------- Sticky header shadow on scroll ---------- */
  var header = document.querySelector('.gv-header');
  if (header) {
    var headScroll = function () {
      header.classList.toggle('is-scrolled', window.pageYOffset > 8);
    };
    window.addEventListener('scroll', headScroll, { passive: true });
    headScroll();
  }

  /* ---------- Smooth in-page anchor scroll ---------- */
  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('a[href^="#"]');
    if (!a) return;
    var id = a.getAttribute('href');
    if (id.length < 2) return;
    var target = document.querySelector(id);
    if (!target) return;
    e.preventDefault();
    target.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
  });

  /* ---------- Broken-image fallback (global) ---------- */
  var PLACEHOLDER = 'data:image/svg+xml,' + encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><rect width="200" height="200" fill="#f2f4f7"/>' +
    '<g fill="none" stroke="#c7ccd3" stroke-width="6"><rect x="52" y="60" width="96" height="80" rx="6"/>' +
    '<circle cx="80" cy="90" r="10"/><path d="M60 132l30-28 24 20 18-14 16 22"/></g></svg>');
  document.addEventListener('error', function (e) {
    var img = e.target;
    if (!(img instanceof HTMLImageElement) || img.dataset.fbk) return;
    img.dataset.fbk = '1';
    img.src = PLACEHOLDER;
    img.classList.add('is-loaded');
  }, true);

  /* ---------- Lazy image fade-in ---------- */
  function markLoaded(img) {
    img.classList.add('is-loaded');
    var wrap = img.closest && img.closest('.gv-card__imgwrap, .pc__media');
    if (wrap) wrap.classList.add('loaded'); // stops the skeleton shimmer
  }
  var lazyImgs = document.querySelectorAll('img[loading="lazy"]');
  Array.prototype.forEach.call(lazyImgs, function (img) {
    if (img.complete && img.naturalWidth > 0) markLoaded(img);
    else img.addEventListener('load', function () { markLoaded(img); }, { once: true });
    img.addEventListener('error', function () { markLoaded(img); }, { once: true });
  });

  /* ---------- Scroll reveal (only for below-the-fold elements) ---------- */
  if (!reduce && 'IntersectionObserver' in window) {
    var vh = window.innerHeight || docEl.clientHeight;
    var targets = document.querySelectorAll('.gv-card, .pc, .gv-section, .gv-benefit, .gv-cat-tile, .gv-brand, .gv-flashsale, .reveal-me');
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) {
          en.target.classList.add('reveal-in');
          io.unobserve(en.target);
        }
      });
    }, { rootMargin: '0px 0px -40px 0px', threshold: 0.05 });
    Array.prototype.forEach.call(targets, function (el, i) {
      var top = el.getBoundingClientRect().top;
      if (top < vh - 40) {
        el.classList.add('reveal-in'); // already visible: no animation, just paint
      } else {
        el.classList.add('reveal');
        el.style.transitionDelay = ((i % 6) * 40) + 'ms';
        io.observe(el);
      }
    });
  }

  /* ---------- Subtle button ripple ---------- */
  if (!reduce) {
    document.addEventListener('click', function (e) {
      var btn = e.target.closest && e.target.closest('.btn');
      if (!btn || btn.disabled) return;
      var rect = btn.getBoundingClientRect();
      var span = document.createElement('span');
      span.className = 'ts-ripple';
      var size = Math.max(rect.width, rect.height);
      span.style.width = span.style.height = size + 'px';
      span.style.left = (e.clientX - rect.left - size / 2) + 'px';
      span.style.top = (e.clientY - rect.top - size / 2) + 'px';
      btn.appendChild(span);
      setTimeout(function () { span.remove(); }, 600);
    });
  }

  /* ================= MOTION LAYER ================= */

  /* Re-trigger a CSS animation class on an element */
  function replay(el, cls) {
    el.classList.remove(cls);
    void el.offsetWidth; // force reflow so the animation restarts
    el.classList.add(cls);
  }

  /* Badge bump whenever a counter's text changes (cart / wishlist / compare) */
  if ('MutationObserver' in window) {
    ['cart-count', 'wish-count', 'compare-count'].forEach(function (id) {
      var el = document.getElementById(id);
      if (!el) return;
      new MutationObserver(function () { replay(el, 'bump'); })
        .observe(el, { childList: true, characterData: true, subtree: true });
    });
    /* Flip-clock tick on the flash-sale countdown */
    ['cd-h', 'cd-m', 'cd-s'].forEach(function (id) {
      var el = document.getElementById(id);
      if (!el) return;
      new MutationObserver(function () { if (!reduce) replay(el, 'tick'); })
        .observe(el, { childList: true, characterData: true, subtree: true });
    });
  }

  /* Fly-to-cart ghost image + "Đã thêm" button state (optimistic, visual only) */
  document.addEventListener('submit', function (e) {
    var form = e.target.closest && e.target.closest('.add-to-cart-form');
    if (!form) return;
    var btn = e.submitter || form.querySelector('button');
    if (btn && !btn.classList.contains('is-added') && !btn.disabled) {
      var orig = btn.innerHTML;
      btn.classList.add('is-added');
      btn.innerHTML = btn.classList.contains('pc__cart') ? '<i class="bi bi-check2"></i>' : '<i class="bi bi-check2"></i> Đã thêm';
      setTimeout(function () { btn.classList.remove('is-added'); btn.innerHTML = orig; }, 1500);
    }
    if (reduce) return;
    var cartBtn = document.querySelector('[data-bs-target="#miniCart"]');
    var card = form.closest('.gv-card, .pc');
    var img = (card && card.querySelector('.gv-card__imgwrap img, .pc__media img')) || document.getElementById('mainImg');
    if (!cartBtn || !img) return;
    var r = img.getBoundingClientRect(), c = cartBtn.getBoundingClientRect();
    if (!r.width || !c.width) return;
    var ghost = document.createElement('img');
    ghost.src = img.currentSrc || img.src;
    ghost.alt = '';
    ghost.className = 'ts-fly';
    ghost.style.cssText = 'left:' + r.left + 'px;top:' + r.top + 'px;width:' + r.width + 'px;height:' + r.height + 'px;';
    document.body.appendChild(ghost);
    requestAnimationFrame(function () { requestAnimationFrame(function () {
      ghost.style.left = (c.left + c.width / 2 - 16) + 'px';
      ghost.style.top = (c.top + c.height / 2 - 16) + 'px';
      ghost.style.width = '32px';
      ghost.style.height = '32px';
      ghost.style.opacity = '.25';
      ghost.style.transform = 'rotate(25deg) scale(.6)';
    }); });
    setTimeout(function () { ghost.remove(); }, 900);
  }, true);

  /* Gallery: quick crossfade when a thumbnail is clicked (detail page) */
  document.addEventListener('click', function (e) {
    if (!e.target.closest || !e.target.closest('.thumb-img')) return;
    var m = document.getElementById('mainImg');
    if (!m || reduce) return;
    m.style.opacity = '0';
    setTimeout(function () { m.style.opacity = '1'; }, 160);
  });

  /* Mega menu: click/keyboard toggle (hover alone excludes touch + keyboard users) */
  Array.prototype.forEach.call(document.querySelectorAll('.gv-mega'), function (m) {
    var trigger = m.querySelector(':scope > a');
    if (!trigger) return;
    trigger.addEventListener('click', function (e) {
      var touch = window.matchMedia('(hover: none)').matches;
      if (touch || e.detail === 0) { e.preventDefault(); m.classList.toggle('open'); }
    });
  });
  document.addEventListener('click', function (e) {
    if (e.target.closest && e.target.closest('.gv-mega')) return;
    Array.prototype.forEach.call(document.querySelectorAll('.gv-mega.open'), function (m) { m.classList.remove('open'); });
  });
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    Array.prototype.forEach.call(document.querySelectorAll('.gv-mega.open'), function (m) { m.classList.remove('open'); });
  });

  /* Count-up numbers: <span data-countup="70">70</span> */
  Array.prototype.forEach.call(document.querySelectorAll('[data-countup]'), function (el) {
    var end = parseInt(el.getAttribute('data-countup'), 10) || 0;
    if (reduce || end <= 0) { el.textContent = end; return; }
    var start = null, dur = 900;
    function step(t) {
      if (start === null) start = t;
      var p = Math.min(1, (t - start) / dur);
      p = 1 - Math.pow(1 - p, 3); // ease-out cubic
      el.textContent = Math.round(end * p);
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  });
})();
