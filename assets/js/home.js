/*!
 * home.js ─ トップページ専用スクリプト
 * リフォームヤマキシ
 * 依存ライブラリなし（バニラJS）／ common.js の後に読み込んでください
 */
(function () {
  'use strict';

  var reduceMotion = (window.YMK && window.YMK.reduceMotion) || false;

  document.addEventListener('DOMContentLoaded', function () {
    initHeroTitle();
    initHeroSlider();
    initCountUp();
    initCompare();
    initFaq();
    initShopTabs();
    initSimulator();
  });

  /* ==================================================================
     1. ヒーロー見出しを1文字ずつ表示
     ================================================================== */
  function initHeroTitle() {
    var el = document.querySelector('[data-split-text]');
    if (!el || reduceMotion) return;

    var i = 0;
    /* テキストノードだけを1文字ずつ span に包む（タグ構造は保つ） */
    (function walk(node) {
      Array.prototype.slice.call(node.childNodes).forEach(function (child) {
        if (child.nodeType === 3) {
          var frag = document.createDocumentFragment();
          child.textContent.split('').forEach(function (c) {
            if (c === '\n' || c === ' ') { frag.appendChild(document.createTextNode(c)); return; }
            var s = document.createElement('span');
            s.className = 'char';
            s.style.setProperty('--i', i++);
            s.textContent = c;
            frag.appendChild(s);
          });
          node.replaceChild(frag, child);
        } else if (child.nodeType === 1 && child.tagName !== 'BR') {
          walk(child);
        }
      });
    })(el);
  }

  /* ==================================================================
     2. ヒーロースライダー（自動フェード切替）
     ================================================================== */
  function initHeroSlider() {
    var slider = document.querySelector('[data-slider]');
    if (!slider) return;

    var items = slider.querySelectorAll('.p-heroslider__item');
    var dotsWrap = slider.querySelector('.p-heroslider__dots');
    if (items.length < 2) return;

    var current = 0, timer = null;
    var INTERVAL = 5000;

    /* ドットを生成 */
    var dots = [];
    items.forEach(function (_, idx) {
      var b = document.createElement('button');
      b.type = 'button';
      b.setAttribute('aria-label', (idx + 1) + '枚目の写真を表示');
      b.addEventListener('click', function () { go(idx); restart(); });
      dotsWrap.appendChild(b);
      dots.push(b);
    });

    function go(n) {
      current = (n + items.length) % items.length;
      items.forEach(function (el, i) { el.classList.toggle('is-active', i === current); });
      dots.forEach(function (d, i) { d.setAttribute('aria-current', i === current ? 'true' : 'false'); });
    }
    function next()    { go(current + 1); }
    function start()   { if (!reduceMotion) timer = setInterval(next, INTERVAL); }
    function stop()    { clearInterval(timer); }
    function restart() { stop(); start(); }

    go(0);
    start();

    /* マウスが乗っている間・タブが非表示の間は止める */
    slider.addEventListener('mouseenter', stop);
    slider.addEventListener('mouseleave', start);
    document.addEventListener('visibilitychange', function () {
      document.hidden ? stop() : restart();
    });

    /* スワイプ操作 */
    var sx = null;
    slider.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; }, { passive: true });
    slider.addEventListener('touchend', function (e) {
      if (sx === null) return;
      var dx = e.changedTouches[0].clientX - sx;
      if (Math.abs(dx) > 45) { go(current + (dx < 0 ? 1 : -1)); restart(); }
      sx = null;
    }, { passive: true });
  }

  /* ==================================================================
     3. 数字のカウントアップ
     ================================================================== */
  function initCountUp() {
    var nums = document.querySelectorAll('[data-count]');
    if (!nums.length) return;

    if (reduceMotion || !('IntersectionObserver' in window)) {
      nums.forEach(function (el) { el.textContent = el.dataset.count; });
      return;
    }

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        run(en.target);
        io.unobserve(en.target);
      });
    }, { threshold: 0.4 });

    nums.forEach(function (el) { el.textContent = '0'; io.observe(el); });

    function run(el) {
      var goal = parseFloat(el.dataset.count);
      var dur  = 1400;
      var t0   = performance.now();
      (function tick(now) {
        var p = Math.min((now - t0) / dur, 1);
        var e = 1 - Math.pow(1 - p, 3);           /* easeOutCubic */
        el.textContent = Math.round(goal * e).toLocaleString('ja-JP');
        if (p < 1) requestAnimationFrame(tick);
        else el.textContent = goal.toLocaleString('ja-JP');
      })(t0);
    }
  }

  /* ==================================================================
     4. Before / After 比較スライダー
     ================================================================== */
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

  /* ==================================================================
     5. よくある質問アコーディオン（高さをなめらかに開閉）
     ================================================================== */
  function initFaq() {
    document.querySelectorAll('[data-faq]').forEach(function (item) {
      var btn = item.querySelector('.p-faq__q');
      var box = item.querySelector('.p-faq__a');
      if (!btn || !box) return;

      var inner = box.querySelector('.p-faq__a-inner');
      var opened = item.classList.contains('is-open');

      btn.setAttribute('aria-expanded', opened ? 'true' : 'false');
      box.style.height = opened ? 'auto' : '0px';

      btn.addEventListener('click', function () {
        opened = !opened;
        item.classList.toggle('is-open', opened);
        btn.setAttribute('aria-expanded', opened ? 'true' : 'false');

        if (reduceMotion) { box.style.height = opened ? 'auto' : '0px'; return; }

        if (opened) {
          box.style.height = inner.offsetHeight + 'px';
          box.addEventListener('transitionend', function h() {
            box.style.height = 'auto';
            box.removeEventListener('transitionend', h);
          });
        } else {
          box.style.height = inner.offsetHeight + 'px';
          requestAnimationFrame(function () {
            requestAnimationFrame(function () { box.style.height = '0px'; });
          });
        }
      });
    });
  }

  /* ==================================================================
     6. 店舗の県別タブ
     ================================================================== */
  function initShopTabs() {
    var tabs = document.querySelectorAll('[data-shoptab]');
    if (!tabs.length) return;

    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        var key = tab.dataset.shoptab;
        tabs.forEach(function (t) {
          var on = t === tab;
          t.classList.toggle('is-active', on);
          t.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        document.querySelectorAll('[data-shoplist]').forEach(function (list) {
          list.hidden = (list.dataset.shoplist !== key);
        });
      });
    });
  }

  /* ==================================================================
     7. かんたん見積りシミュレーター
        3つの質問に答えると概算価格が出て、そのままLINEへ誘導します
        ※ 金額は目安。実データに合わせて PRICE を書き換えてください
     ================================================================== */
  var PRICE = {
    /* 部位ごとの [下限, 上限]（万円・税込・工事費込み）
       ※金額はすべて仮の数字です。実際の相場に合わせて書き換えてください。 */
    kitchen: { label: 'キッチン',           range: [55, 130] },
    bath:    { label: 'お風呂',             range: [65, 150] },
    toilet:  { label: 'トイレ・洗面台',     range: [15, 40]  },
    boiler:  { label: '給湯器・エコキュート', range: [25, 65] },
    wall:    { label: '外壁・屋根',         range: [80, 180] },
    /* 「その他」は内容によって幅が大きすぎるため、金額を出さずご相談へ誘導します */
    other:   { label: 'その他',             range: null, ask: true }
  };
  /* 築年数による補正 */
  var AGE_FACTOR = {
    '10':  { label: '築0〜10年',   factor: 1.00 },
    '20':  { label: '築11〜20年',  factor: 1.04 },
    '30':  { label: '築21〜30年',  factor: 1.09 },
    '40':  { label: '築31〜40年',  factor: 1.14 },
    '50':  { label: '築41年以上',  factor: 1.20 }
  };
  /* 時期による補正はしないが、要望として見積りに引き継ぐ */
  var WHEN = {
    now:   'できるだけ早く',
    m3:    '3か月以内に',
    m6:    '半年〜1年のうちに',
    plan:  'まだ考えはじめたところ'
  };

  function initSimulator() {
    var sim = document.querySelector('[data-sim]');
    if (!sim) return;

    var panels = sim.querySelectorAll('.p-sim__panel');
    var steps  = sim.querySelectorAll('.p-sim__steps li');
    var answer = { part: null, age: null, when: null };
    var step   = 0;

    function show(n) {
      step = n;
      panels.forEach(function (p, i) { p.classList.toggle('is-active', i === n); });
      steps.forEach(function (s, i) {
        s.classList.toggle('is-active', i === n);
        s.classList.toggle('is-done', i < n);
      });
      /* シミュレーターの先頭が見えるようにスクロール */
      if (n > 0) {
        var h = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--header-h'), 10) || 72;
        var top = sim.getBoundingClientRect().top + window.scrollY - h - 16;
        window.scrollTo({ top: top, behavior: reduceMotion ? 'auto' : 'smooth' });
      }
    }

    /* 選択肢のクリック */
    sim.addEventListener('click', function (e) {
      var choice = e.target.closest('.p-sim__choice');
      if (choice) {
        var key = choice.dataset.simKey;      /* part / age / when */
        answer[key] = choice.dataset.simValue;
        choice.parentNode.querySelectorAll('.p-sim__choice').forEach(function (c) {
          c.classList.toggle('is-selected', c === choice);
        });
        setTimeout(function () {
          if (key === 'when') { calc(); show(3); }
          else show(step + 1);
        }, 180);
        return;
      }
      if (e.target.closest('.p-sim__back'))  { e.preventDefault(); show(Math.max(0, step - 1)); return; }
      if (e.target.closest('.p-sim__retry')) {
        e.preventDefault();
        answer = { part: null, age: null, when: null };
        sim.querySelectorAll('.p-sim__choice').forEach(function (c) { c.classList.remove('is-selected'); });
        show(0);
      }
    });

    /* 概算を計算して表示 */
    function calc() {
      var p = PRICE[answer.part];
      var a = AGE_FACTOR[answer.age];
      if (!p || !a) return;

      var numEl  = sim.querySelector('[data-sim-num]');
      var partEl = sim.querySelector('[data-sim-part]');
      var units  = sim.querySelectorAll('.p-sim__price .unit');
      var labelEl= sim.querySelector('.p-sim__result-label');
      if (partEl) partEl.textContent = p.label;

      /* 金額を出さない項目（その他）は、数字のかわりにご案内を出します */
      if (p.ask) {
        if (numEl) { numEl.textContent = '内容をお聞かせください'; numEl.classList.add('is-text'); }
        for (var i = 0; i < units.length; i++) units[i].style.display = 'none';
        if (labelEl) labelEl.textContent = 'ご希望の内容によって費用が変わります';
        return;
      }
      if (numEl) numEl.classList.remove('is-text');
      for (var j = 0; j < units.length; j++) units[j].style.display = '';
      if (labelEl) {
        labelEl.innerHTML = '<span data-sim-part></span>のリフォーム費用は、だいたい';
        labelEl.querySelector('[data-sim-part]').textContent = p.label;
      }

      var lo = Math.round(p.range[0] * a.factor);
      var hi = Math.round(p.range[1] * a.factor);

      /* 数字をカウントアップして表示 */
      if (numEl) {
        if (reduceMotion) {
          numEl.textContent = lo + '〜' + hi;
        } else {
          var t0 = performance.now();
          (function tick(now) {
            var t = Math.min((now - t0) / 900, 1);
            var e2 = 1 - Math.pow(1 - t, 3);
            numEl.textContent = Math.round(lo * e2) + '〜' + Math.round(hi * e2);
            if (t < 1) requestAnimationFrame(tick);
            else numEl.textContent = lo + '〜' + hi;
          })(t0);
        }
      }

      /* LINEボタンに、選んだ内容を引き継ぐ */
      var lineBtn = sim.querySelector('[data-sim-line]');
      if (lineBtn) {
        lineBtn.dataset.cta = 'simulator_result_' + answer.part;
      }
      var memo = sim.querySelector('[data-sim-memo]');
      if (memo) {
        memo.textContent = p.label + '／' + a.label + '／' + (WHEN[answer.when] || '');
      }

      /* 計測イベント */
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push({
        event: 'simulator_complete',
        sim_part: answer.part,
        sim_age:  answer.age,
        sim_when: answer.when
      });
    }

    show(0);
  }
})();
