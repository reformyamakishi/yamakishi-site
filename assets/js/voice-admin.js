/*! ymkrf voice-admin.js ─ お客様アンケート「仕事の通信簿」の自動読み取り
 *  リフォームヤマキシ
 *
 *  管理画面（お客様の声の編集画面）だけで動きます。
 *  アンケートのスキャン画像を選ぶと、①〜⑨のチェックを自動で読み取って
 *  入力欄にうつします。手書きの感想と点数だけ、切り出した画像を見ながら
 *  打ち込んでいただく形です。
 *
 *  やっていること
 *    1. 用紙のかたむきを直す（横罫線がいちばんそろう角度をさがす）
 *    2. 画面ぜんぶから「チェック欄らしい四角」を拾う
 *    3. 拾った四角と、基準の57個の並びを投票で照合して、倍率と位置を決める
 *    4. 1つずつ微調整して、枠の内側のインクの量でチェックの有無を決める
 *
 *  ※用紙の書式が変わったら REF と AREAS の座標を取り直してください。
 *    （いまの座標は 2026/08 の「仕事の通信簿」から測っています）
 */
(function () {
'use strict';

/* ===== 基準の用紙（4961×3508px）でのチェック欄の左上座標 ===== */
var REF = { W: 4961, H: 3508, BOX: 55 };

var Q1_X = [201, 752, 1109, 1530], Q1_Y = [999, 1076, 1155, 1243, 1320];
var Q2_X = [196, 751, 1107],       Q2_Y = [1608, 1688, 1768];
var Q2_SHOKAI = [196, 1849];
var Q37_X = [196, 754, 1111, 1472], Q37_Y = [2111, 2409, 2715, 3018, 3320];
var Q8_X = [2664, 3127, 3400, 3701], Q8_Y = 245;
var Q9_X = [2669, 3024, 3557, 3993], Q9_Y = 540;

var Q1_LABELS = [
  ['キッチン','浴室','トイレ','洗面室'],
  ['エコキュート','給湯器','オイルタンク','エクステリア'],
  ['カーポート','外壁','屋根','窓・サッシ'],
  ['レンジフード','ドア','蓄電池','太陽光発電'],
  ['修理・小工事','改装・内装','その他']
];
var Q2_LABELS = [
  ['プラン内容','評判','家から近かったから'],
  ['営業担当の対応','専門性','以前工事を選んだ会社だから'],
  ['サービス内容','価格','その他']
];
var RATING4 = ['大変良かった','満足','普通','よくなかった'];
var Q9_LABELS = ['勧める','勧めても良い','わからない','勧められない'];

/* 手書きが入る場所（基準用紙での位置）。切り出して画面に出します。

   2026/08 に、いまのアンケート用紙（きれいな原本）から測り直しました。
   用紙に印刷されている見出し（「今回リフォームする前は…」など）が
   入らないよう、見出しの下から始まる範囲にしてあります。

   Google に送るのは trouble / after / comment の3か所だけです。
   score は画面に出すだけで、送りません（点数は手で打ち込みます）。 */
var AREAS = {
  trouble: [2471,  845, 4885, 1368],
  after:   [2446, 1508, 4893, 2242],
  comment: [2446, 2370, 4098, 3184],
  score:   [4150, 2540, 4740, 3040],
  shokai:  [ 170, 1815, 1580, 1915],  /* ご紹介（　様）… 公開画像では塗りつぶします */

  /* ⑧「工事の仕上がり」と⑨「お知り合いへのおすすめ」は、用紙の右上にあって
     読みまちがえやすいところです。その行だけを切り出して画面に出し、
     人の目で確かめられるようにします（読み取りには使いません）。 */
  q8row:   [2540,  165, 4050,  340],
  q9row:   [2540,  460, 4200,  635]
};

/* このうち、Google に送ってよいのはここだけ */
var OCR_AREAS = ['trouble', 'after', 'comment'];

function refPoints() {
  var p = [], ri, ci;
  for (ri = 0; ri < Q1_Y.length; ri++)
    for (ci = 0; ci < Q1_X.length; ci++) {
      if (ri === 4 && ci === 3) continue;
      p.push(['q1_' + ri + '_' + ci, Q1_X[ci], Q1_Y[ri]]);
    }
  for (ri = 0; ri < Q2_Y.length; ri++)
    for (ci = 0; ci < Q2_X.length; ci++) p.push(['q2_' + ri + '_' + ci, Q2_X[ci], Q2_Y[ri]]);
  p.push(['q2_shokai', Q2_SHOKAI[0], Q2_SHOKAI[1]]);
  for (ri = 0; ri < Q37_Y.length; ri++)
    for (ci = 0; ci < Q37_X.length; ci++) p.push(['q' + (ri + 3) + '_' + ci, Q37_X[ci], Q37_Y[ri]]);
  for (ci = 0; ci < Q8_X.length; ci++) p.push(['q8_' + ci, Q8_X[ci], Q8_Y]);
  for (ci = 0; ci < Q9_X.length; ci++) p.push(['q9_' + ci, Q9_X[ci], Q9_Y]);
  return p;
}
var REFP = refPoints();

/* ===== 画像を扱う小道具 ===== */

/* 画像を、横 maxW px までに縮めて灰色にします */
function toGray(img, maxW) {
  var w = img.naturalWidth || img.width, h = img.naturalHeight || img.height;
  var s = Math.min(1, maxW / w);
  var W = Math.round(w * s), H = Math.round(h * s);
  var cv = document.createElement('canvas'); cv.width = W; cv.height = H;
  var cx = cv.getContext('2d', { willReadFrequently: true });
  cx.fillStyle = '#fff'; cx.fillRect(0, 0, W, H);
  cx.drawImage(img, 0, 0, W, H);
  var d = cx.getImageData(0, 0, W, H).data;
  var g = new Uint8ClampedArray(W * H);
  for (var i = 0, j = 0; i < d.length; i += 4, j++)
    g[j] = (d[i] * 299 + d[i + 1] * 587 + d[i + 2] * 114) / 1000;
  return { g: g, W: W, H: H, scaleFromOriginal: s };
}

/* かたむきをさがす。
   横700pxくらいに縮めてから、うすい紙をずらして重ねるように数えます。
   横罫線がいちばんそろう角度＝用紙のかたむき、です。 */
function findSkew(img) {
  var sm = toGray(img, 700);
  var W = sm.W, H = sm.H, g = sm.g;
  var xs = [], ys = [];
  for (var y = 0; y < H; y++) {
    var row = y * W;
    for (var x = 0; x < W; x++) if (g[row + x] < 170) { xs.push(x); ys.push(y); }
  }
  if (!xs.length) return 0;
  var cx = W / 2, best = 0, bestScore = -1;
  for (var a = -3.0; a <= 3.0001; a += 0.05) {
    var t = Math.tan(a * Math.PI / 180);
    var prof = new Float64Array(H + 8);
    for (var i = 0; i < xs.length; i++) {
      var yy = (ys[i] - (xs[i] - cx) * t + 4) | 0;
      if (yy >= 0 && yy < H + 8) prof[yy]++;
    }
    var sc = 0;
    for (var k = 1; k < H + 8; k++) { var d = prof[k] - prof[k - 1]; sc += d * d; }
    if (sc > bestScore) { bestScore = sc; best = a; }
  }
  return best;
}

/* かたむきを直した灰色画像を作り直します */
function rotateGray(img, maxW, angle) {
  var w = img.naturalWidth || img.width, h = img.naturalHeight || img.height;
  var s = Math.min(1, maxW / w);
  var W = Math.round(w * s), H = Math.round(h * s);
  var cv = document.createElement('canvas'); cv.width = W; cv.height = H;
  var cx = cv.getContext('2d', { willReadFrequently: true });
  cx.fillStyle = '#fff'; cx.fillRect(0, 0, W, H);
  cx.translate(W / 2, H / 2);
  cx.rotate(-angle * Math.PI / 180);
  cx.translate(-W / 2, -H / 2);
  cx.drawImage(img, 0, 0, W, H);
  var d = cx.getImageData(0, 0, W, H).data;
  var g = new Uint8ClampedArray(W * H);
  for (var i = 0, j = 0; i < d.length; i += 4, j++)
    g[j] = (d[i] * 299 + d[i + 1] * 587 + d[i + 2] * 114) / 1000;
  return { g: g, W: W, H: H, canvas: cv, ctx: cx, scaleFromOriginal: s, angle: angle };
}

/* 積分画像（四角の合計をすぐ出すための下ごしらえ） */
function integral(gr) {
  var W = gr.W, H = gr.H, g = gr.g;
  var I = new Float64Array((W + 1) * (H + 1));
  for (var y = 0; y < H; y++) {
    var run = 0, r0 = y * (W + 1), r1 = (y + 1) * (W + 1), gy = y * W;
    for (var x = 0; x < W; x++) {
      run += 255 - g[gy + x];
      I[r1 + x + 1] = I[r0 + x + 1] + run;
    }
  }
  return { I: I, W: W, H: H };
}
function rect(it, x, y, w, h) {
  if (x < 0 || y < 0 || x + w > it.W || y + h > it.H || w <= 0 || h <= 0) return null;
  var W1 = it.W + 1, I = it.I;
  return I[(y + h) * W1 + x + w] - I[y * W1 + x + w] - I[(y + h) * W1 + x] + I[y * W1 + x];
}

/* 枠らしさ ＝ 枠線の濃さ − 外side の濃さ×1.6 − 内側の濃さ×0.8 */
function frameScore(it, x, y, b) {
  var t = Math.max(1, Math.round(b / 11)), o = Math.max(1, Math.round(b / 8));
  var inn = rect(it, x, y, b, b); if (inn === null) return -1e9;
  var cen = rect(it, x + t, y + t, b - 2 * t, b - 2 * t); if (cen === null) return -1e9;
  var big = rect(it, x - o, y - o, b + 2 * o, b + 2 * o); if (big === null) return -1e9;
  var ring = (inn - cen) / Math.max(1, b * b - (b - 2 * t) * (b - 2 * t));
  var out = (big - inn) / Math.max(1, (b + 2 * o) * (b + 2 * o) - b * b);
  var ins = cen / Math.max(1, (b - 2 * t) * (b - 2 * t));
  return ring - 1.6 * out - 0.8 * ins;
}

/* 「四角らしいところ」を拾います */
function findBoxes(it, b, limit) {
  var W = it.W, H = it.H, cand = [];
  var step = b < 18 ? 1 : 2;
  for (var y = 0; y < H - b; y += step)
    for (var x = 0; x < W - b; x += step) {
      var s = frameScore(it, x, y, b);
      if (s > 60) cand.push([x, y, s]);
    }
  cand.sort(function (p, q) { return q[2] - p[2]; });
  var keep = [], min2 = (b * 0.7) * (b * 0.7);
  for (var i = 0; i < cand.length && keep.length < limit; i++) {
    var ok = true;
    for (var k = 0; k < keep.length; k++) {
      var dx = cand[i][0] - keep[k][0], dy = cand[i][1] - keep[k][1];
      if (dx * dx + dy * dy < min2) { ok = false; break; }
    }
    if (ok) keep.push(cand[i]);
  }
  return keep;
}

/* 投票で倍率と位置を決めます */
function voteFit(P, sc0, W, H) {
  var bestN = -1, bestSc = sc0, bestT = null, cell = 6;
  for (var f = 0.86; f <= 1.1601; f += 0.005) {
    var sc = sc0 * f, map = Object.create(null), topKey = null, topN = 0;
    for (var i = 0; i < P.length; i++) {
      for (var r = 0; r < REFP.length; r++) {
        var tx = P[i][0] - REFP[r][1] * sc, ty = P[i][1] - REFP[r][2] * sc;
        if (tx < -0.15 * W || tx > 0.15 * W || ty < -0.15 * H || ty > 0.15 * H) continue;
        var key = Math.round(tx / cell) + ',' + Math.round(ty / cell);
        var e = map[key];
        if (e) { e.n++; e.sx += tx; e.sy += ty; } else { e = map[key] = { n: 1, sx: tx, sy: ty }; }
        if (e.n > topN) { topN = e.n; topKey = key; }
      }
    }
    if (topN > bestN) {
      bestN = topN; bestSc = sc;
      bestT = [map[topKey].sx / topN, map[topKey].sy / topN];
    }
  }
  return { n: bestN, sc: bestSc, t: bestT };
}

/* ===== 読み取り本体 ===== */
function readSurvey(img) {
  var MAXW = 2500;
  var ang = findSkew(img);
  var gr = Math.abs(ang) < 0.05 ? rotateGray(img, MAXW, 0) : rotateGray(img, MAXW, ang);
  var it = integral(gr);

  var sc0 = gr.W / REF.W;
  var b0 = Math.max(6, Math.round(REF.BOX * sc0));
  var P = [];
  [0.90, 1.0, 1.10].forEach(function (f) {
    var b = Math.max(6, Math.round(b0 * f));
    findBoxes(it, b, 200).forEach(function (p) { P.push(p); });
  });
  var fit = voteFit(P, sc0, gr.W, gr.H);
  if (!fit.t || fit.n < 10) throw new Error('アンケート用紙として認識できませんでした。用紙全体が写っているか、解像度が足りているか（横2000px以上）をご確認ください。');

  var sc = fit.sc, dx = fit.t[0], dy = fit.t[1], rot = 0;
  var box = Math.max(6, Math.round(REF.BOX * sc));

  function refineAll(sc, dx, dy, rot, rad) {
    var ca = Math.cos(rot) * sc, sa = Math.sin(rot) * sc, out = {};
    for (var i = 0; i < REFP.length; i++) {
      var rx = REFP[i][1], ry = REFP[i][2];
      var px = Math.round(ca * rx - sa * ry + dx), py = Math.round(sa * rx + ca * ry + dy);
      var bs = -1e9, bx = px, by = py;
      for (var ddy = -rad; ddy <= rad; ddy++)
        for (var ddx = -rad; ddx <= rad; ddx++) {
          var v = frameScore(it, px + ddx, py + ddy, box);
          if (v > bs) { bs = v; bx = px + ddx; by = py + ddy; }
        }
      out[REFP[i][0]] = { s: bs, x: bx, y: by };
    }
    return out;
  }

  var found = refineAll(sc, dx, dy, rot, Math.max(3, Math.round(box * 0.30)));

  /* 2回目：よく合った枠だけを使って、わずかな回転までふくめて合わせ直します */
  var arr = REFP.map(function (p) { return { k: p[0], rx: p[1], ry: p[2], f: found[p[0]] }; })
                .sort(function (a, b) { return b.f.s - a.f.s; })
                .slice(0, Math.round(REFP.length * 0.7));
  if (arr.length >= 8) {
    /* [rx -ry 1 0; ry rx 0 1]·[a b tx ty] = [bx; by] を最小二乗で解きます */
    var N = [[0,0,0,0],[0,0,0,0],[0,0,0,0],[0,0,0,0]], V = [0,0,0,0];
    arr.forEach(function (o) {
      [[o.rx, -o.ry, 1, 0, o.f.x], [o.ry, o.rx, 0, 1, o.f.y]].forEach(function (r) {
        for (var i = 0; i < 4; i++) { for (var j = 0; j < 4; j++) N[i][j] += r[i] * r[j]; V[i] += r[i] * r[4]; }
      });
    });
    var sol = solve4(N, V);
    if (sol) {
      var sc2 = Math.hypot(sol[0], sol[1]), rot2 = Math.atan2(sol[1], sol[0]);
      if (sc2 / sc > 0.7 && sc2 / sc < 1.4 && Math.abs(rot2) < 0.06) {
        sc = sc2; rot = rot2; dx = sol[2]; dy = sol[3];
        box = Math.max(6, Math.round(REF.BOX * sc));
        found = refineAll(sc, dx, dy, rot, Math.max(3, Math.round(box * 0.18)));
      }
    }
  }

  /* インク量は「紙の白さ」を基準にした割合で見ます（うすいスキャンでも読めるように） */
  var hist = new Uint32Array(256), n = 0, i2;
  for (i2 = 0; i2 < gr.g.length; i2 += 3) { hist[gr.g[i2]]++; n++; }
  var paper = percentile(hist, n, 0.72), dark = percentile(hist, n, 0.02);
  var span = Math.max(40, paper - dark);

  var ink = {}, m = Math.max(2, Math.round(box * 0.18));
  Object.keys(found).forEach(function (k) {
    var f = found[k], sum = 0, cnt = 0;
    for (var y = f.y + m; y < f.y + box - m; y++) {
      if (y < 0 || y >= gr.H) continue;
      for (var x = f.x + m; x < f.x + box - m; x++) {
        if (x < 0 || x >= gr.W) continue;
        var v = paper - gr.g[y * gr.W + x];
        sum += v > 0 ? v : 0; cnt++;
      }
    }
    ink[k] = cnt ? (sum / cnt) / span : 0;
  });

  return {
    ink: ink, on: function (k) { return ink[k] > 0.030; },
    gr: gr, sc: sc, rot: rot, dx: dx, dy: dy, box: box, angle: gr.angle, votes: fit.n,
    map: function (rx, ry) {
      var ca = Math.cos(rot) * sc, sa = Math.sin(rot) * sc;
      return [ca * rx - sa * ry + dx, sa * rx + ca * ry + dy];
    }
  };
}

function percentile(hist, n, p) {
  var t = n * p, c = 0;
  for (var i = 0; i < 256; i++) { c += hist[i]; if (c >= t) return i; }
  return 255;
}

function solve4(A, b) {
  var M = [A[0].concat(b[0]), A[1].concat(b[1]), A[2].concat(b[2]), A[3].concat(b[3])];
  for (var c = 0; c < 4; c++) {
    var piv = c;
    for (var r = c + 1; r < 4; r++) if (Math.abs(M[r][c]) > Math.abs(M[piv][c])) piv = r;
    if (Math.abs(M[piv][c]) < 1e-9) return null;
    var tmp = M[c]; M[c] = M[piv]; M[piv] = tmp;
    for (var r2 = 0; r2 < 4; r2++) {
      if (r2 === c) continue;
      var f = M[r2][c] / M[c][c];
      for (var k = c; k < 5; k++) M[r2][k] -= f * M[c][k];
    }
  }
  return [M[0][4] / M[0][0], M[1][4] / M[1][1], M[2][4] / M[2][2], M[3][4] / M[3][3]];
}

/* 読み取り結果を、わかりやすい形に直します */
function toAnswers(R) {
  var a = { parts: [], reasons: [], ratings: {}, q9: 0, q9label: '' };
  Q1_LABELS.forEach(function (row, ri) {
    row.forEach(function (lb, ci) { if (R.on('q1_' + ri + '_' + ci)) a.parts.push(lb); });
  });
  Q2_LABELS.forEach(function (row, ri) {
    row.forEach(function (lb, ci) { if (R.on('q2_' + ri + '_' + ci)) a.reasons.push(lb); });
  });
  if (R.on('q2_shokai')) a.reasons.push('ご紹介');
  ['q3','q4','q5','q6','q7','q8'].forEach(function (q) {
    for (var ci = 0; ci < 4; ci++) if (R.on(q + '_' + ci)) a.ratings[q] = 4 - ci;   /* 大変良かった=4 */
  });
  for (var ci = 0; ci < 4; ci++) if (R.on('q9_' + ci)) { a.q9 = 4 - ci; a.q9label = Q9_LABELS[ci]; }
  return a;
}

/* 手書き部分を切り出して、小さな画像にします */
function cropArea(R, key, maxW) {
  /* maxW に 0 を渡すと、縮めずにそのままの大きさで切り出します（文字起こし用） */
  var A = AREAS[key];
  var p0 = R.map(A[0], A[1]), p1 = R.map(A[2], A[3]);
  var x = Math.round(Math.min(p0[0], p1[0])), y = Math.round(Math.min(p0[1], p1[1]));
  var w = Math.round(Math.abs(p1[0] - p0[0])), h = Math.round(Math.abs(p1[1] - p0[1]));
  x = Math.max(0, x); y = Math.max(0, y);
  w = Math.min(w, R.gr.W - x); h = Math.min(h, R.gr.H - y);
  if (w <= 0 || h <= 0) return null;
  var s = ( maxW === 0 ) ? 1 : Math.min(1, (maxW || 900) / w);
  var cv = document.createElement('canvas');
  cv.width = Math.round(w * s); cv.height = Math.round(h * s);
  cv.getContext('2d').drawImage(R.gr.canvas, x, y, w, h, 0, 0, cv.width, cv.height);
  return cv;
}

/* 公開用の画像を作ります（ご紹介欄を塗りつぶして、横1600pxに） */
function makePublicImage(R, width) {
  width = width || 1600;
  var s = Math.min(1, width / R.gr.W);
  var cv = document.createElement('canvas');
  cv.width = Math.round(R.gr.W * s); cv.height = Math.round(R.gr.H * s);
  var cx = cv.getContext('2d');
  cx.fillStyle = '#fff'; cx.fillRect(0, 0, cv.width, cv.height);
  cx.drawImage(R.gr.canvas, 0, 0, cv.width, cv.height);
  /* ご紹介（　様）の欄を白く塗ります */
  var A = AREAS.shokai, p0 = R.map(A[0], A[1]), p1 = R.map(A[2], A[3]);
  cx.fillStyle = '#fff';
  cx.fillRect(p0[0] * s, p0[1] * s, (p1[0] - p0[0]) * s, (p1[1] - p0[1]) * s);
  return cv;
}

window.YmkrfSurvey = {
  read: readSurvey, answers: toAnswers, crop: cropArea, publicImage: makePublicImage,
  ocrAreas: OCR_AREAS,
  labels: { q1: Q1_LABELS, q2: Q2_LABELS, rating: RATING4, q9: Q9_LABELS }
};

})();

/* =====================================================================
   ここから下は「お客様の声」の編集画面のための処理です。
   （編集画面以外では何もしません）
   ===================================================================== */
(function () {
  if (typeof jQuery === 'undefined' || typeof YMKRF_VOICE === 'undefined') return;
  var $ = jQuery;

  $(function () {
    var $pick = $('#ymkrf-pick'), $re = $('#ymkrf-reread'), $st = $('#ymkrf-status');
    var $ocr = $('#ymkrf-ocr');
    if (!$pick.length) return;
    var frame = null;

    /* いちばん最後に読み取った結果を覚えておきます。
       「Cloud Visionで文字起こしする」を押したときに、これを使います。 */
    var lastRead = null;
    var wantOcr  = false;   /* 読み取りのあと、そのまま文字起こしまで進むかどうか */

    function say(msg, cls) { $st.attr('class', 'ymkrf-voice__status ' + (cls || '')).text(msg); }

    $pick.on('click', function (e) {
      e.preventDefault();
      if (frame) { frame.open(); return; }
      frame = wp.media({ title: 'アンケートのスキャン画像を選ぶ',
        library: { type: 'image' }, button: { text: 'この画像を使う' }, multiple: false });
      frame.on('select', function () {
        var a = frame.state().get('selection').first().toJSON();
        $('#ymkrf-survey-id').val(a.id);
        var name = (a.filename || '').replace(/\.[^.]+$/, '')
                     .replace(/-scaled$/, '')      /* WordPressが大きい画像に付ける印 */
                     .replace(/-e\d{10,}$/, '');   /* 画像を編集したときに付く印 */
        if (/^[0-9A-Za-z_-]+$/.test(name) && !$('input[name="_ymkrf_case_no"]').val()) {
          $('input[name="_ymkrf_case_no"]').val(name);      /* ファイル名＝案件番号 */
        }
        var url = (a.sizes && a.sizes.full ? a.sizes.full.url : a.url);
        $('#ymkrf-preview').html('<img src="' + url + '" data-full="' + url +
                                 '" data-orig="' + url + '" alt="">');
        $('#ymkrf-previewnote').show();
        $re.prop('disabled', false);
        wantOcr = false;
        run(url);
      });
      frame.open();
    });

    $re.on('click', function (e) {
      e.preventDefault();
      var $img = $('#ymkrf-preview img');
      if (!$img.length) { say('先に画像を選んでください', 'is-ng'); return; }
      wantOcr = false;
      run($img.attr('data-orig') || $img.attr('src'));
    });

    /* 「Cloud Visionで文字起こしする」ボタン。
       押したときだけ Google に送ります（自動では送りません）。 */
    $ocr.on('click', function (e) {
      e.preventDefault();
      if (lastRead) { runOcr(lastRead); return; }

      /* まだ読み取っていないとき（ページを開き直したときなど）は、
         先に画像を読み取ってから文字起こしします。 */
      var $img = $('#ymkrf-preview img');
      if (!$img.length) { say('先に画像を選んでください', 'is-ng'); return; }
      wantOcr = true;
      run($img.attr('data-orig') || $img.attr('src'));
    });

    function run(url) {
      say('読み取っています…');
      var img = new Image();
      img.crossOrigin = 'anonymous';
      img.onload = function () {
        setTimeout(function () {
          var R;
          try { R = window.YmkrfSurvey.read(img); }
          catch (err) { say(err.message, 'is-ng'); return; }
          apply(R);
        }, 30);
      };
      img.onerror = function () { say('画像を読み込めませんでした', 'is-ng'); };
      img.src = url;
    }

    function apply(R) {
      var A = window.YmkrfSurvey.answers(R);

      /* ① 工事箇所 ／ ② 理由 */
      $('#ymkrf-parts input[type=checkbox]').each(function () {
        this.checked = A.parts.indexOf(this.value) >= 0;
      });
      $('#ymkrf-reasons input[type=checkbox]').each(function () {
        this.checked = A.reasons.indexOf(this.value) >= 0;
      });

      /* ③〜⑧ ／ ⑨ */
      var map = { q3: '_ymkrf_r_sales', q4: '_ymkrf_r_plan', q5: '_ymkrf_r_worker',
                  q6: '_ymkrf_r_process', q7: '_ymkrf_r_site', q8: '_ymkrf_r_finish' };
      Object.keys(map).forEach(function (q) {
        var v = A.ratings[q] || 0;
        $('input[name="' + map[q] + '"][value="' + v + '"]').prop('checked', true);
      });
      $('input[name="_ymkrf_recommend"][value="' + (A.q9 || 0) + '"]').prop('checked', true);

      /* 手書きの部分を切り出して、入力欄の下に出します */
      [['score','ymkrf-crop-score'],['trouble','ymkrf-crop-trouble'],
       ['after','ymkrf-crop-after'],['comment','ymkrf-crop-comment'],
       ['q8row','ymkrf-crop-finish'],['q9row','ymkrf-crop-recommend']].forEach(function (o) {
        var cv = window.YmkrfSurvey.crop(R, o[0], o[0] === 'score' ? 320 : 900);
        var $box = $('#' + o[1]).empty();
        if (!cv) return;
        $box.append($('<img>').attr('src', cv.toDataURL('image/jpeg', 0.85)));
        if (o[0] === 'q8row' || o[0] === 'q9row') {
          $box.append($('<p class="ymkrf-voice__cropnote">')
            .text('※ここは読みまちがえやすいところです。用紙のチェックと合っているか見てください。'));
        }
      });

      $('#ymkrf-read-info').val('かたむき' + R.angle.toFixed(1) + '度 / 一致' + R.votes + '個');

      /* 題名がまだ空なら、工事箇所から自動でつけます。
         （WordPressは題名も本文も空だと投稿を保存してくれません）
         「金沢市｜」の部分は、保存のときにサーバー側で付きます。 */
      var $title = $('#title');
      if ($title.length && !$title.val()) {
        /* 「その他」は検索されない言葉なので、題名には使いません */
        var ps = A.parts.filter(function (p) { return p !== 'その他'; });
        var t;
        if (!ps.length) {
          t = 'リフォームのお客様の声';
        } else if (ps[0] === '修理・小工事' || ps[0] === '改装・内装') {
          t = ps.slice(0, 2).join('・') + 'のお客様の声';
        } else {
          t = ps.slice(0, 2).join('・') + 'リフォームのお客様の声';
        }
        $title.val(t).trigger('change');
      }

      /* 公開用の画像（ご紹介欄を白く塗ったもの）を作って保存します */
      try {
        var pub = window.YmkrfSurvey.publicImage(R, 1600);
        $.post(YMKRF_VOICE.ajax, {
          action: 'ymkrf_voice_pub_image', nonce: YMKRF_VOICE.nonce,
          data: pub.toDataURL('image/jpeg', 0.86)
        }).done(function (res) {
          if (!res || !res.success) return;
          $('#ymkrf-pub-id').val(res.data.id);
          /* 画面に出す画像も、公開用（お名前を塗りつぶしたもの）に差しかえます。
             読み取り用の元画像は data-orig に残しておきます。 */
          var $img = $('#ymkrf-preview img');
          if ($img.length && res.data.url) {
            $img.attr('src', res.data.url).attr('data-full', res.data.url);
          }
        });
      } catch (e) { /* 画像づくりに失敗しても、読み取り結果は残します */ }

      var n = A.parts.length + A.reasons.length +
              Object.keys(A.ratings).length + (A.q9 ? 1 : 0);

      lastRead = R;
      $ocr.prop('disabled', false);

      /* 満足度が空なら、目立つように知らせます
         （点数の欄は Google に送っていないので、手で入力していただきます） */
      var $sc = $('#ymkrf-score');
      if ($sc.length && !$.trim($sc.val())) {
        $sc.addClass('is-need');
        $('#ymkrf-scoreneed').addClass('is-on');
      }

      if (wantOcr) {
        wantOcr = false;
        runOcr(R);
      } else if (YMKRF_VOICE.ocr) {
        say('読み取りました（' + n + '項目）。手書きは打ち込むか、'
          + '「Cloud Visionで文字起こしする」を押してください。', 'is-ok');
      } else {
        say('読み取りました（' + n + '項目）。手書きの感想と点数をご記入ください。', 'is-ok');
      }
    }

    /* 手書きの部分をGoogleに送って、文字にしてもらいます。
       すでに文字が入っている欄は送りません（そのぶん料金がかかりません）。 */
    function runOcr(R) {
      var send = {};
      var FIELD = {
        trouble: 'textarea[name="_ymkrf_trouble"]',
        after:   'textarea[name="_ymkrf_after"]',
        comment: 'textarea[name="_ymkrf_comment"]'
      };
      window.YmkrfSurvey.ocrAreas.forEach(function (key) {
        var $el = $(FIELD[key]);
        if ($el.length && $.trim($el.val()) !== '') return;   /* もう入っている欄はとばします */
        var cv = window.YmkrfSurvey.crop(R, key, 0);          /* 縮めずに送ります */
        if (cv) send[key] = cv.toDataURL('image/jpeg', 0.92);
      });
      var num = Object.keys(send).length;
      if (!num) {
        say('手書きの欄はすべて入力ずみでした。文字起こしは行いません。', 'is-ok');
        return;
      }
      say('手書きの文字起こし中…（画像' + num + '枚）', 'is-ok');

      $.post(YMKRF_VOICE.ajax, {
        action: 'ymkrf_voice_ocr', nonce: YMKRF_VOICE.nonce, images: send
      }).done(function (res) {
        if (!res || !res.success) {
          say('チェックは読み取れました。手書きの文字起こしは失敗しました（'
              + ((res && res.data) || '原因不明') + '）。手で入力してください。', 'is-ng');
          return;
        }
        var d = res.data, filled = [];
        function put(sel, val, label) {
          if (!val) return;
          var $el = $(sel);
          if (!$el.length || $el.val()) return;      /* すでに入っていれば触りません */
          $el.val(val).addClass('is-ocr');
          filled.push(label);
        }
        put('textarea[name="_ymkrf_trouble"]', d.trouble, 'お悩み');
        put('textarea[name="_ymkrf_after"]',   d.after,   'いかがでしたか');
        put('textarea[name="_ymkrf_comment"]', d.comment, 'メッセージ');
        put('#ymkrf-score',                    d.score,   '満足度');

        if (filled.length) {
          say('文字起こししました（' + filled.join('・') + '）。'
            + '★下の切り抜き画像と見くらべて、まちがいがないか確かめてください。', 'is-warn');
        } else {
          say('手書きの文章は見つかりませんでした。記入がないアンケートのようです。', 'is-ok');
        }
      }).fail(function () {
        say('チェックは読み取れました。文字起こしの通信に失敗しました。手で入力してください。', 'is-ng');
      });
    }

    /* 満足度が入力されたら、注意を消します */
    $(document).on('input change', '#ymkrf-score', function () {
      if ($.trim($(this).val()) !== '') {
        $(this).removeClass('is-need');
        $('#ymkrf-scoreneed').removeClass('is-on');
      }
    });

    /* プレビューを押すと、大きく出します（もう一度押すと閉じます） */
    $(document).on('click', '#ymkrf-preview img', function () {
      var src = $(this).attr('data-full') || $(this).attr('src');
      if (!src) return;
      var $z = $('<div class="ymkrf-voice__zoom"><img alt=""></div>');
      $z.find('img').attr('src', src);
      $z.on('click', function () { $z.remove(); });
      $(document).on('keydown.ymkrfzoom', function (ev) {
        if (ev.key === 'Escape') { $z.remove(); $(document).off('keydown.ymkrfzoom'); }
      });
      $('body').append($z);
    });
  });
})();
