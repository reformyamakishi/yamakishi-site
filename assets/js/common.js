/*!
 * common.js ─ 全ページ共通スクリプト
 * リフォームヤマキシ
 * 依存ライブラリなし（バニラJS）
 */
(function () {
  'use strict';

  /* 動きを減らす設定のユーザーには演出を控える */
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  document.addEventListener('DOMContentLoaded', function () {
    setHeaderHeight();
    initHeaderScroll();
    initDrawer();
    initFixedCta();
    initPageTop();
    initReveal();
    initSmoothScroll();
    initCvTracking();
    initCurrentNav();
    initCompare();
    document.documentElement.classList.add('is-loaded');
  });

  /* ------------------------------------------------------------------
     施工事例の Before / After 比較スライダー

     ★もとは home.js（トップページ専用）にありましたが、
       商品ページ・商品一覧ページでも同じ形で施工事例を出すため、
       全ページ共通のこちらへ移しました。
       （home.js からは削除してあります。二重に動かさないでください）
     ------------------------------------------------------------------ */
  function initCompare() {
    document.querySelectorAll('[data-compare]').forEach(function (box) {
      var pos = 50;
      var dragging = false;

      function set(x) {
        var r = box.getBoundingClientRect();
        pos = Math.min(100, Math.max(0, ((x - r.left) / r.width) * 100));
        box.style.setProperty('--pos', pos + '%');
        box.classList.add('is-touched');
      }

      /* ポインタ操作（マウス・タッチ共通） */
      box.addEventListener('pointerdown', function (e) {
        dragging = true;
        set(e.clientX);
        try { box.setPointerCapture(e.pointerId); } catch (err) { /* 非対応環境は無視 */ }
      });
      box.addEventListener('pointermove', function (e) {
        if (dragging) set(e.clientX);
      });
      box.addEventListener('pointerup',     function () { dragging = false; });
      box.addEventListener('pointercancel', function () { dragging = false; });

      /* キーボード操作 */
      box.setAttribute('tabindex', '0');
      box.setAttribute('role', 'slider');
      box.setAttribute('aria-label', '施工前と施工後の写真を比べる');
      box.setAttribute('aria-valuemin', '0');
      box.setAttribute('aria-valuemax', '100');
      box.addEventListener('keydown', function (e) {
        if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
        e.preventDefault();
        pos = Math.min(100, Math.max(0, pos + (e.key === 'ArrowRight' ? 5 : -5)));
        box.style.setProperty('--pos', pos + '%');
        box.setAttribute('aria-valuenow', Math.round(pos));
        box.classList.add('is-touched');
      });

      /* 画面に入ったら一度だけ動かして「動かせる」と気づいてもらう */
      if (!reduceMotion && 'IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (en) {
          if (!en[0].isIntersecting) return;
          io.disconnect();
          var t0 = performance.now();
          (function tick(now) {
            var p = Math.min((now - t0) / 1600, 1);
            var v = 50 + Math.sin(p * Math.PI * 2) * 22;
            if (!box.classList.contains('is-touched')) {
              box.style.setProperty('--pos', v + '%');
              if (p < 1) requestAnimationFrame(tick);
              else box.style.setProperty('--pos', '50%');
            }
          })(t0);
        }, { threshold: 0.5 });
        io.observe(box);
      }
    });
  }

  /* ------------------------------------------------------------------
     ヘッダーの実高さを CSS 変数に反映（アンカーのズレ防止）
     ------------------------------------------------------------------ */
  function setHeaderHeight() {
    var header = document.querySelector('.p-header');
    if (!header) return;
    var apply = function () {
      document.documentElement.style.setProperty('--header-h', header.offsetHeight + 'px');
    };
    apply();
    window.addEventListener('resize', debounce(apply, 200));
  }

  /* ------------------------------------------------------------------
     スクロールでヘッダーに影をつける
     ------------------------------------------------------------------ */
  function initHeaderScroll() {
    var header = document.querySelector('.p-header');
    if (!header) return;
    onScroll(function (y) {
      header.classList.toggle('is-scrolled', y > 10);
    });
  }

  /* ------------------------------------------------------------------
     スマホのドロワーメニュー
     ------------------------------------------------------------------ */
  function initDrawer() {
    var btn    = document.querySelector('.p-hamburger');
    var drawer = document.querySelector('.p-drawer');
    if (!btn || !drawer) return;

    var panel   = drawer.querySelector('.p-drawer__panel');
    var overlay = drawer.querySelector('.p-drawer__overlay');
    var closeBt = drawer.querySelector('.p-drawer__close');
    var lastY   = 0;

    function open() {
      lastY = window.scrollY;
      drawer.classList.add('is-open');
      btn.setAttribute('aria-expanded', 'true');
      document.body.style.cssText = 'position:fixed;top:' + (-lastY) + 'px;left:0;right:0;overflow:hidden';
      var first = panel.querySelector('a,button');
      if (first) first.focus({ preventScroll: true });
    }
    function close() {
      drawer.classList.remove('is-open');
      btn.setAttribute('aria-expanded', 'false');
      document.body.style.cssText = '';
      window.scrollTo(0, lastY);
      btn.focus({ preventScroll: true });
    }

    btn.addEventListener('click', function () {
      drawer.classList.contains('is-open') ? close() : open();
    });
    if (overlay) overlay.addEventListener('click', close);
    if (closeBt) closeBt.addEventListener('click', close);

    /* パネル内のリンクを押したら閉じる */
    panel.addEventListener('click', function (e) {
      if (e.target.closest('a')) close();
    });

    /* Esc で閉じる／フォーカスをパネル内に閉じ込める */
    document.addEventListener('keydown', function (e) {
      if (!drawer.classList.contains('is-open')) return;
      if (e.key === 'Escape') { close(); return; }
      if (e.key !== 'Tab') return;
      var f = panel.querySelectorAll('a[href],button:not([disabled])');
      if (!f.length) return;
      var first = f[0], last = f[f.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });
  }

  /* ------------------------------------------------------------------
     追従CTA：ファーストビューを過ぎたら出す／フッター手前で引っ込める
     ------------------------------------------------------------------ */
  function initFixedCta() {
    var cta = document.querySelector('.p-fixcta');
    if (!cta) return;
    var footer = document.querySelector('.p-footer');

    /* CTAの高さぶんだけ本文に余白を作る */
    var pad = function () {
      if (window.innerWidth < 1024) document.body.style.paddingBottom = cta.offsetHeight + 'px';
      else document.body.style.paddingBottom = '';
    };
    pad();
    window.addEventListener('resize', debounce(pad, 200));

    onScroll(function (y) {
      var passedHero = y > window.innerHeight * 0.45;
      var nearFooter = footer
        ? footer.getBoundingClientRect().top < window.innerHeight - 40
        : false;
      cta.classList.toggle('is-visible', passedHero && !nearFooter);
    });
  }

  /* ------------------------------------------------------------------
     ページトップボタン
     ------------------------------------------------------------------ */
  function initPageTop() {
    var btn = document.querySelector('.p-pagetop');
    if (!btn) return;
    onScroll(function (y) { btn.classList.toggle('is-visible', y > 600); });
    btn.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
    });
  }

  /* ------------------------------------------------------------------
     スクロールで要素をふわっと表示
     ------------------------------------------------------------------ */
  function initReveal() {
    var targets = document.querySelectorAll('[data-reveal]');
    if (!targets.length) return;

    if (reduceMotion || !('IntersectionObserver' in window)) {
      targets.forEach(function (el) { el.classList.add('is-in'); });
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        var delay = parseInt(en.target.dataset.revealDelay || 0, 10);
        setTimeout(function () { en.target.classList.add('is-in'); }, delay);
        io.unobserve(en.target);
      });
    }, { rootMargin: '0px 0px -12% 0px', threshold: 0.08 });

    targets.forEach(function (el) { io.observe(el); });
  }

  /* ------------------------------------------------------------------
     ページ内リンクのスムーススクロール（ヘッダー高さを考慮）
     ------------------------------------------------------------------ */
  function initSmoothScroll() {
    document.addEventListener('click', function (e) {
      var a = e.target.closest('a[href^="#"]');
      if (!a) return;
      var id = a.getAttribute('href');
      if (id === '#' || id === '#!') return;
      var target = document.querySelector(id);
      if (!target) return;
      e.preventDefault();
      var h = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--header-h'), 10) || 72;
      var top = target.getBoundingClientRect().top + window.scrollY - h - 12;
      window.scrollTo({ top: top, behavior: reduceMotion ? 'auto' : 'smooth' });
      history.replaceState(null, '', id);
    });
  }

  /* ------------------------------------------------------------------
     コンバージョン計測（GA4 / GTM）
     LINE・電話・来店予約のクリックをイベントとして送ります
     ------------------------------------------------------------------ */
  function initCvTracking() {
    window.dataLayer = window.dataLayer || [];

    document.addEventListener('click', function (e) {
      var a = e.target.closest('a');
      if (!a || !a.href) return;

      var name = null;
      if (a.href.indexOf('lin.ee') > -1 || a.href.indexOf('line.me') > -1) name = 'line_click';
      else if (a.href.indexOf('tel:') === 0) name = 'tel_click';
      else if (a.href.indexOf('/inquiry/webrsv') > -1) name = 'reserve_click';
      else if (a.href.indexOf('/inquiry') > -1) name = 'contact_click';
      if (!name) return;

      var payload = {
        event: name,
        cta_position: a.dataset.cta || 'unknown',
        page_path: location.pathname
      };
      window.dataLayer.push(payload);
      if (typeof window.gtag === 'function') {
        window.gtag('event', name, {
          cta_position: payload.cta_position,
          page_path: payload.page_path
        });
      }
    });
  }

  /* ------------------------------------------------------------------
     グローバルナビの現在地表示
     ------------------------------------------------------------------ */
  function initCurrentNav() {
    var path = location.pathname.replace(/index\.html?$/, '');
    document.querySelectorAll('.p-gnav__list a[href]').forEach(function (a) {
      var href = a.getAttribute('href');
      if (!href || href.charAt(0) === '#') return;
      var p = new URL(href, location.href).pathname.replace(/index\.html?$/, '');
      if (p !== '/' && path.indexOf(p) === 0) a.classList.add('is-current');
    });
  }

  /* ==================================================================
     共通ユーティリティ
     ================================================================== */
  function debounce(fn, wait) {
    var t;
    return function () {
      var a = arguments, c = this;
      clearTimeout(t);
      t = setTimeout(function () { fn.apply(c, a); }, wait);
    };
  }

  /* requestAnimationFrame でまとめてスクロール処理を行う */
  var scrollHandlers = [];
  var ticking = false;
  function onScroll(fn) {
    scrollHandlers.push(fn);
    fn(window.scrollY);
    if (scrollHandlers.length === 1) {
      window.addEventListener('scroll', function () {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(function () {
          var y = window.scrollY;
          scrollHandlers.forEach(function (h) { h(y); });
          ticking = false;
        });
      }, { passive: true });
    }
  }

  /* 他ファイルからも使えるように公開 */
  window.YMK = { debounce: debounce, onScroll: onScroll, reduceMotion: reduceMotion };
})();
