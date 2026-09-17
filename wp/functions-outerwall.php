<?php
/**
 * functions-outerwall.php ─ 外壁・屋根ページ
 *   （商品 ＞ 商品カテゴリ ＞ 外壁・屋根 ＞「ページの中身を直す」）
 * 置き場所： wp-content/themes/ymkrf/inc/functions-outerwall.php
 *
 * （2026/09/17 ユーザー指示
 *   「ぺージの内容を全く変えず、ダッシュボードの商品の外壁・屋根のぺージに、
 *     HTML・CSSを入れてほしい。
 *     そしたらサーバーの中に行かなくても他の従業員が修正できる」）
 *
 * ■ どういうことか
 *   外壁・屋根（/products/outer-wall/）は、商品を1つずつ登録するページでは
 *   ありません。1枚のご案内ページ（LP）です。
 *   これまでは、中身がテーマのファイルの中に書いてあったので、
 *   直すにはサーバーの中のファイルをさわる必要がありました。
 *
 *   このファイルで、HTMLとCSSをそのまま直せる画面を作ります。
 *   サーバーに入らなくても、ダッシュボードだけで直せます。
 *
 * ■ どこから開くか（左メニューには出していません）
 *   ・商品 ＞ 商品カテゴリ ＞「外壁・屋根」の行の「ページの中身を直す」
 *   ・商品一覧を「外壁・屋根」でしぼる（自動でこの画面に移ります）
 *
 * ■ はじめの中身は、いま出ているページとまったく同じです
 *   まだ一度も保存していないときは、下の ymkrf_ow_default_html() が使われます。
 *   「もとの内容にもどす」を押せば、いつでもここに戻せます。
 *
 * ■ 差しこみの合言葉（テンプレートタグ）
 *   HTMLの中に、次の言葉を書くと、その場所に中身が入ります。
 *     {{テーマ}}       … テーマのURL（写真の置き場所）
 *     {{ホーム}}       … サイトのトップのURL
 *     {{商品一覧}}     … 商品・価格ページのURL
 *     {{劣化度3}}      … とんとこトンの目じるし（1〜5）
 *     {{施工事例}}     … 「外壁・屋根」の施工事例3件
 *     {{お問い合わせ}} … 「無料の現地調査・お見積り」「お電話」のボタン
 *
 * ■ 名前について
 *   保存さき … ymkrf_ow_html（HTML）／ ymkrf_ow_css（CSS）
 */
if ( ! defined( 'ABSPATH' ) ) exit;


/* ============================================================
   1. はじめの中身（いま出ているページと同じもの）
   ============================================================ */
function ymkrf_ow_default_html() {

	return <<<'YMKRF_OW_HTML'
<main id="main">

<!-- ============ 見出し ============ -->
<section class="p-guide__hero">
  <div class="l-wrap">
    <span class="c-head__en">EXTERIOR</span>
    <h1 class="p-guide__title">外壁・屋根の<br class="sp-only">塗装リフォーム</h1>
    <p class="p-guide__lead">
      北陸の雨と雪、そして夏の日ざし。<br class="pc-only">
      外壁と屋根は、家のなかでいちばん天気にさらされている場所です。<br>
      「そろそろかな」と思われたら、まず見せていただくところから。<br class="pc-only">
      現地調査とお見積りは無料です。
    </p>
    <img class="p-guide__herochara" src="{{テーマ}}/assets/img/character/char-paint-wall.webp"
         width="640" height="619"
         alt="ヤマキシのキャラクター「とんとこトン」がローラーで壁を塗っているイラスト"
         fetchpriority="high">
  </div>
</section>

<!-- ============ 外壁のチェック ============ -->
<section class="l-section" id="wall">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">WALL CHECK</span>
      <h2 class="c-head__title">外壁から<br class="sp-only">こんな<span class="marker">サイン</span>が出ていませんか</h2>
      <p class="c-head__lead">
        下にいくほど、急いだほうがよい状態です。<br class="pc-only">
        ひとつでも当てはまるものがあれば、一度ご相談ください。
      </p>
    </div>

    <div class="p-ow__checks">
      <div class="p-ow__check">
        <p class="p-ow__lv"><span class="p-ow__lvlbl">劣化度</span>{{劣化度1}}</p>
        <div class="p-ow__ph">
          <picture>
            <source srcset="{{テーマ}}/assets/img/outerwall/wall1-fade.webp" type="image/webp">
            <img src="{{テーマ}}/assets/img/outerwall/wall1-fade.jpg" width="800" height="600"
                 alt="外壁全体の色があせて、くすんでしまっている家" loading="lazy" decoding="async">
          </picture>
        </div>
        <div class="p-ow__body">
          <h3 class="p-ow__name">色あせ・くすみ</h3>
          <p class="p-ow__text">新築のころとくらべて、壁の色がぼんやりしてきた。<br>日ざしで塗膜が弱りはじめたサインです。<br>すぐに困ることはありませんが、ここから先へ進んでいきます。</p>
        </div>
      </div>

      <div class="p-ow__check">
        <p class="p-ow__lv"><span class="p-ow__lvlbl">劣化度</span>{{劣化度2}}</p>
        <div class="p-ow__ph">
          <picture>
            <source srcset="{{テーマ}}/assets/img/outerwall/wall2-chalking.webp" type="image/webp">
            <img src="{{テーマ}}/assets/img/outerwall/wall2-chalking.jpg" width="800" height="600"
                 alt="外壁を手でこすると白い粉がつく、チョーキングの状態" loading="lazy" decoding="async">
          </picture>
        </div>
        <div class="p-ow__body">
          <h3 class="p-ow__name">チョーキング</h3>
          <p class="p-ow__text">壁を手でこすると、白い粉が手につく。<br>塗膜が粉になって浮いてきた状態です。<br>雨のたびに、塗料が少しずつ流れ出しています。</p>
        </div>
      </div>

      <div class="p-ow__check">
        <p class="p-ow__lv"><span class="p-ow__lvlbl">劣化度</span>{{劣化度3}}</p>
        <div class="p-ow__ph">
          <picture>
            <source srcset="{{テーマ}}/assets/img/outerwall/wall3-sealing.webp" type="image/webp">
            <img src="{{テーマ}}/assets/img/outerwall/wall3-sealing.jpg" width="800" height="600"
                 alt="外壁のつなぎ目のシーリングが固くなり、切れている状態" loading="lazy" decoding="async">
          </picture>
        </div>
        <div class="p-ow__body">
          <h3 class="p-ow__name">シーリングの傷み</h3>
          <p class="p-ow__text">壁と壁のつなぎ目のゴムが、かたくなったり、切れたりしている。<br>ここは雨の入り口になります。壁そのものより先に傷みます。</p>
        </div>
      </div>

      <div class="p-ow__check">
        <p class="p-ow__lv"><span class="p-ow__lvlbl">劣化度</span>{{劣化度4}}</p>
        <div class="p-ow__ph">
          <picture>
            <source srcset="{{テーマ}}/assets/img/outerwall/wall4-moss.webp" type="image/webp">
            <img src="{{テーマ}}/assets/img/outerwall/wall4-moss.jpg" width="800" height="600"
                 alt="外壁に苔や藻がついて緑色になっている状態" loading="lazy" decoding="async">
          </picture>
        </div>
        <div class="p-ow__body">
          <h3 class="p-ow__name">カビ・苔・藻</h3>
          <p class="p-ow__text">北側や日かげの壁が、緑っぽく・黒っぽくなっている。<br>壁が水をふくむようになったサインです。<br>洗っても、またすぐに出てくるようなら塗り替えどきです。</p>
        </div>
      </div>

      <div class="p-ow__check">
        <p class="p-ow__lv"><span class="p-ow__lvlbl">劣化度</span>{{劣化度5}}</p>
        <div class="p-ow__ph">
          <picture>
            <source srcset="{{テーマ}}/assets/img/outerwall/wall5-crack.webp" type="image/webp">
            <img src="{{テーマ}}/assets/img/outerwall/wall5-crack.jpg" width="800" height="600"
                 alt="外壁にひび割れが入り、塗装が剥がれている状態" loading="lazy" decoding="async">
          </picture>
        </div>
        <div class="p-ow__body">
          <h3 class="p-ow__name">ひび割れ・塗装の剥げ</h3>
          <p class="p-ow__text">壁が割れている、塗装がめくれている。<br>ここまで来ると、塗るだけでは済みません。<br>下地の補修から必要になり、工事も大きくなります。</p>
        </div>
      </div>

      <div class="p-ow__check">
        <p class="p-ow__lv"><span class="p-ow__lvlbl">劣化度</span>{{劣化度5}}</p>
        <div class="p-ow__ph">
          <picture>
            <source srcset="{{テーマ}}/assets/img/outerwall/wall6-rust.webp" type="image/webp">
            <img src="{{テーマ}}/assets/img/outerwall/wall6-rust.jpg" width="800" height="600"
                 alt="トタンの外壁や門扉にサビが出て、塗膜が剥がれている状態" loading="lazy" decoding="async">
          </picture>
        </div>
        <div class="p-ow__body">
          <h3 class="p-ow__name">トタンのサビ・剥げ</h3>
          <p class="p-ow__text">門扉・フェンス・シャッター・トタンの壁のサビ。<br>サビは塗膜のはがれを広げながら、下地まで進んでいきます。<br>5〜7年に一度の塗り替えをおすすめしています。</p>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ============ 屋根のチェック ============ -->
<section class="l-section l-section--gray" id="roof">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">ROOF CHECK</span>
      <h2 class="c-head__title">屋根も、<br class="sp-only">いっしょに<span class="marker">見ておきたい</span>ところ</h2>
      <p class="c-head__lead">
        屋根は下から見えません。<br class="pc-only">
        外壁の足場を組むときに、あわせて見るのがいちばん無駄がありません。
      </p>
    </div>

    <div class="p-ow__checks p-ow__checks--roof">
      <div class="p-ow__check">
        <p class="p-ow__lv"><span class="p-ow__lvlbl">劣化度</span>{{劣化度3}}</p>
        <div class="p-ow__ph">
          <picture>
            <source srcset="{{テーマ}}/assets/img/outerwall/roof1-rust.webp" type="image/webp">
            <img src="{{テーマ}}/assets/img/outerwall/roof1-rust.jpg" width="800" height="600"
                 alt="トタン屋根の塗装面がサビてぼろぼろになっている状態" loading="lazy" decoding="async">
          </picture>
        </div>
        <div class="p-ow__body">
          <h3 class="p-ow__name">サビが出ている<span class="p-ow__sub">トタン屋根</span></h3>
          <p class="p-ow__text">塗装面がぼろぼろになっています。<br>そのままにしておくと、雨もりにつながることがあります。</p>
        </div>
      </div>

      <div class="p-ow__check">
        <p class="p-ow__lv"><span class="p-ow__lvlbl">劣化度</span>{{劣化度3}}</p>
        <div class="p-ow__ph">
          <picture>
            <source srcset="{{テーマ}}/assets/img/outerwall/roof2-fade.webp" type="image/webp">
            <img src="{{テーマ}}/assets/img/outerwall/roof2-fade.jpg" width="800" height="600"
                 alt="スレート瓦の色が褪せて白っぽくなっている屋根" loading="lazy" decoding="async">
          </picture>
        </div>
        <div class="p-ow__body">
          <h3 class="p-ow__name">かなり色が褪せている<span class="p-ow__sub">スレート瓦</span></h3>
          <p class="p-ow__text">色が抜けて、白っぽく見えるようになった屋根です。<br>瓦そのものが水を吸いはじめています。</p>
        </div>
      </div>

      <div class="p-ow__check">
        <p class="p-ow__lv"><span class="p-ow__lvlbl">劣化度</span>{{劣化度3}}</p>
        <div class="p-ow__ph">
          <picture>
            <source srcset="{{テーマ}}/assets/img/outerwall/roof3-peel.webp" type="image/webp">
            <img src="{{テーマ}}/assets/img/outerwall/roof3-peel.jpg" width="800" height="600"
                 alt="セメント瓦の色が褪せ、塗膜が剥がれている屋根" loading="lazy" decoding="async">
          </picture>
        </div>
        <div class="p-ow__body">
          <h3 class="p-ow__name">色が褪せ、塗膜が剥がれている<span class="p-ow__sub">セメント瓦・乾式洋瓦</span></h3>
          <p class="p-ow__text">色が変わっているだけでなく、水をはじく力も落ちています。<br>屋根は下から見えにくい場所です。<br>足場を組むときに、いっしょに見せていただくのがいちばん確実です。</p>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ============ 価格 ============
     ★スマホでは、表が横スクロールにならないよう、1行ずつのカードに
       組み替わります（CSSで切り替えています）。
       ですので、金額のところには data-lbl="〜100㎡" のような
       見出しの控えをかならず付けてください。 -->
<section class="l-section" id="price">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">PRICE</span>
      <h2 class="c-head__title">外壁・屋根塗装の<span class="marker">価格</span></h2>
      <p class="c-head__lead">
        すべて税込・コミコミの価格です。<br class="pc-only">
        塗る面積と、塗料の種類でお値段が変わります。
      </p>
    </div>

    <div class="p-ow__pricebox">
      <h3 class="p-ow__pricettl">外壁塗装</h3>
      <p class="p-ow__pricelead">足場・高圧洗浄・下地処理・下塗り・中塗り・上塗り・10年保証まで込みの価格です。（シリコンのみ保証3年になります）</p>

      <div class="p-ow__table">
        <div class="p-ow__thead">
          <span>塗料の種類</span>
          <span>〜100㎡</span>
          <span>〜150㎡</span>
          <span>〜200㎡</span>
          <span>もちの目安</span>
        </div>

        <div class="p-ow__trow">
          <div class="p-ow__tname">シリコン</div>
          <div class="p-ow__tcell" data-lbl="〜100㎡"><span class="p-ow__tyen">49.8<small>万円</small></span></div>
          <div class="p-ow__tcell" data-lbl="〜150㎡"><span class="p-ow__tyen">74.7<small>万円</small></span></div>
          <div class="p-ow__tcell" data-lbl="〜200㎡"><span class="p-ow__tyen">99.6<small>万円</small></span></div>
          <div class="p-ow__tcell p-ow__tcell--life" data-lbl="もちの目安"><span class="p-ow__tlife">12〜15年</span></div>
        </div>
        <div class="p-ow__trow">
          <div class="p-ow__tname">プレミアムシリコン<span class="p-ow__tsub">ラジカル</span></div>
          <div class="p-ow__tcell" data-lbl="〜100㎡"><span class="p-ow__tyen">54.8<small>万円</small></span></div>
          <div class="p-ow__tcell" data-lbl="〜150㎡"><span class="p-ow__tyen">82.2<small>万円</small></span></div>
          <div class="p-ow__tcell" data-lbl="〜200㎡"><span class="p-ow__tyen">109.6<small>万円</small></span></div>
          <div class="p-ow__tcell p-ow__tcell--life" data-lbl="もちの目安"><span class="p-ow__tlife">15〜17年</span></div>
        </div>
        <div class="p-ow__trow">
          <div class="p-ow__tname">遮熱シリコン</div>
          <div class="p-ow__tcell" data-lbl="〜100㎡"><span class="p-ow__tyen">59.8<small>万円</small></span></div>
          <div class="p-ow__tcell" data-lbl="〜150㎡"><span class="p-ow__tyen">89.7<small>万円</small></span></div>
          <div class="p-ow__tcell" data-lbl="〜200㎡"><span class="p-ow__tyen">119.6<small>万円</small></span></div>
          <div class="p-ow__tcell p-ow__tcell--life" data-lbl="もちの目安"><span class="p-ow__tlife">13〜15年</span></div>
        </div>
        <div class="p-ow__trow">
          <div class="p-ow__tname">無機シリコン</div>
          <div class="p-ow__tcell" data-lbl="〜100㎡"><span class="p-ow__tyen">64.8<small>万円</small></span></div>
          <div class="p-ow__tcell" data-lbl="〜150㎡"><span class="p-ow__tyen">97.2<small>万円</small></span></div>
          <div class="p-ow__tcell" data-lbl="〜200㎡"><span class="p-ow__tyen">129.6<small>万円</small></span></div>
          <div class="p-ow__tcell p-ow__tcell--life" data-lbl="もちの目安"><span class="p-ow__tlife">17〜20年</span></div>
        </div>
        <div class="p-ow__trow">
          <div class="p-ow__tname">フッ素</div>
          <div class="p-ow__tcell" data-lbl="〜100㎡"><span class="p-ow__tyen">69.8<small>万円</small></span></div>
          <div class="p-ow__tcell" data-lbl="〜150㎡"><span class="p-ow__tyen">104.7<small>万円</small></span></div>
          <div class="p-ow__tcell" data-lbl="〜200㎡"><span class="p-ow__tyen">139.6<small>万円</small></span></div>
          <div class="p-ow__tcell p-ow__tcell--life" data-lbl="もちの目安"><span class="p-ow__tlife">17〜20年</span></div>
        </div>
        <div class="p-ow__trow">
          <div class="p-ow__tname">無機</div>
          <div class="p-ow__tcell" data-lbl="〜100㎡"><span class="p-ow__tyen">74.8<small>万円</small></span></div>
          <div class="p-ow__tcell" data-lbl="〜150㎡"><span class="p-ow__tyen">112.2<small>万円</small></span></div>
          <div class="p-ow__tcell" data-lbl="〜200㎡"><span class="p-ow__tyen">149.6<small>万円</small></span></div>
          <div class="p-ow__tcell p-ow__tcell--life" data-lbl="もちの目安"><span class="p-ow__tlife">20年以上</span></div>
        </div>
      </div>

      <ul class="p-ow__pricenotes">
        <li>破風・軒天の塗装は、別にお見積りをお出しします。</li>
        <li>2024年4月1日の法改正で足場の基準が変わりました。上の金額は、新しい基準の足場代を含んだ価格です。</li>
      </ul>
    </div>

    <div class="p-ow__pricebox">
      <h3 class="p-ow__pricettl">屋根塗装</h3>
      <p class="p-ow__pricelead">高圧洗浄・下地処理・下塗り・中塗り・上塗り・3〜5年保証まで込みの価格です。</p>

      <div class="p-ow__table">
        <div class="p-ow__thead">
          <span>塗料の種類</span>
          <span>50㎡</span>
          <span>100㎡</span>
          <span>150㎡</span>
          <span>もちの目安</span>
        </div>

        <div class="p-ow__trow">
          <div class="p-ow__tname">遮熱シリコン</div>
          <div class="p-ow__tcell" data-lbl="50㎡"><span class="p-ow__tyen">19.8<small>万円</small></span></div>
          <div class="p-ow__tcell" data-lbl="100㎡"><span class="p-ow__tyen">39.6<small>万円</small></span></div>
          <div class="p-ow__tcell" data-lbl="150㎡"><span class="p-ow__tyen">59.4<small>万円</small></span></div>
          <div class="p-ow__tcell p-ow__tcell--life" data-lbl="もちの目安"><span class="p-ow__tlife">13〜15年</span></div>
        </div>
        <div class="p-ow__trow">
          <div class="p-ow__tname">遮熱フッ素</div>
          <div class="p-ow__tcell" data-lbl="50㎡"><span class="p-ow__tyen">29.8<small>万円</small></span></div>
          <div class="p-ow__tcell" data-lbl="100㎡"><span class="p-ow__tyen">59.6<small>万円</small></span></div>
          <div class="p-ow__tcell" data-lbl="150㎡"><span class="p-ow__tyen">89.4<small>万円</small></span></div>
          <div class="p-ow__tcell p-ow__tcell--life" data-lbl="もちの目安"><span class="p-ow__tlife">17〜20年</span></div>
        </div>
      </div>

      <ul class="p-ow__pricenotes">
        <li>保証の年数は、屋根の状態によって変わります。</li>
        <li>屋根だけを塗る場合は、足場代が別になります。</li>
      </ul>
    </div>

    <p class="p-guide__note">
      ※上の金額は税込です。お家の形や、いまの壁・屋根の傷みぐあいによって変わることがあります。<br>
      まずは無料の現地調査で、実際に測ってからお見積りをお出しします。<br>
      追加の工事が必要なときは、着工前にかならずお見積りをお出しします。
    </p>
  </div>
</section>

<!-- ============ 専門サイトへ ============
     このページの本命の出口です。目立たせています。 -->
<section class="l-section l-section--tint" id="paint">
  <div class="l-wrap">
    <div class="p-ow__site">
      <picture class="p-ow__sitechara">
        <source srcset="{{テーマ}}/assets/img/outerwall/site-pig.webp" type="image/webp">
        <img src="{{テーマ}}/assets/img/outerwall/site-pig.png" width="593" height="640"
             alt="ヤマキシのキャラクター「とんとこトン」がローラーを持って歩いているイラスト"
             loading="lazy" decoding="async">
      </picture>
      <div class="p-ow__sitebody">
        <p class="p-ow__siteen">YAMAKISHI PAINT</p>
        <h2 class="p-ow__sitettl">外壁・屋根の専門サイトが<br class="sp-only">あります</h2>
        <p class="p-ow__sitetext">
          工事の流れ、よくあるご質問、施工事例、対応している地域。<br>
          外壁と屋根のことは、こちらにくわしくまとめています。
        </p>
        <a class="p-ow__sitebtn" href="https://yamakishi-paint.jp/" target="_blank" rel="noopener">
          外壁・屋根サポートサイトを見る
          <span class="p-ow__sitebtnsub">ヤマキシペイント（別のサイトが開きます）</span>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ============ まとめて頼めます ============
     このサイトにしか書けないことです。専門サイトとかぶりません。 -->
<section class="l-section" id="together">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">TOGETHER</span>
      <img class="c-head__chara c-chara--float" src="{{テーマ}}/assets/img/character/char-anshin.webp"
           width="530" height="640" alt="" loading="lazy" decoding="async">
      <h2 class="c-head__title">家のことは、<br class="sp-only"><span class="marker">まとめて</span>ご相談ください</h2>
    </div>

    <ul class="p-ow__merits">
      <li class="p-ow__merit">
        <h3 class="p-ow__meritttl">足場は一度で済ませる</h3>
        <p>外壁のために足場を組むときに、屋根・雨どい・ベランダの防水もいっしょに。
        あとから組みなおすと、そのぶん足場代がまたかかります。</p>
      </li>
      <li class="p-ow__merit">
        <h3 class="p-ow__meritttl">中と外を、同じ担当が見ます</h3>
        <p>キッチン・お風呂・給湯器のお取り替えと、外壁・屋根。
        窓口はひとつですので、何度も同じ話をしていただかずに済みます。</p>
      </li>
      <li class="p-ow__merit">
        <h3 class="p-ow__meritttl">近くの店舗がうかがいます</h3>
        <p>石川・福井に9店舗。工事のあとも、気になることがあればすぐに見にまいります。</p>
      </li>
    </ul>
  </div>
</section>

<!-- ============ 施工事例 ============
     ダッシュボードの「施工事例」で、部位に「外壁・屋根」を付けた記事が
     ここに新しい順で3件出ます。1件も無いときは、このかたまりごと出ません。 -->
{{施工事例}}

<!-- ============ さいごのご案内 ============ -->
<section class="l-section l-section--soft">
  <div class="l-wrap">
    <div class="p-pagecta">
      <img class="p-pagecta__chara" src="{{テーマ}}/assets/img/character/char-search-fly.webp"
           width="480" height="480" alt="" loading="lazy">
      <h2 class="p-pagecta__title"><span class="marker">まずは、見せて<br class="xs-only">いただくところから</span></h2>
      <p class="p-pagecta__text">
        現地調査とお見積りは無料です。<br class="xs-only">しつこい営業はいたしません。<br>
        他社さんとのお見積り比べに<br class="xs-only">お使いいただくのも大歓迎です。
      </p>
      {{お問い合わせ}}
    </div>
  </div>
</section>

</main>
YMKRF_OW_HTML;
}


/* ============================================================
   1-2. はじめのCSS（いま出ているページと同じもの）
   ------------------------------------------------------------
   （2026/09/17 ユーザー「cssが空と言うか1行しかありません。
     これでは修正できません」）

   もともと assets/css/product.css の中にあったものを、
   まるごとこちらへ移しました。
   product.css には、もう外壁・屋根のCSSはありません。
   ============================================================ */
function ymkrf_ow_default_css() {

	return <<<'YMKRF_OW_CSS'
/* ============================================================
   外壁・屋根のページ（/products/outer-wall/）の見た目

   ★このCSSは、このページだけに読みこまれます。
     ほかのページには影響しません。
     テーマのCSSより後に読まれるので、ここに書いたものが勝ちます。

   ★このページは「入口」です。くわしい話は専門サイト
     （ヤマキシペイント）にまかせているので、部品も少なめです。

   var(--brand) などの色は、サイト全体で決めている色です。
   そのまま使ってかまいません。
   ============================================================ */

/* ── 外壁・屋根チェックの札 ───────────────────────
   写真を大きく、文字は少なく。
   スマホでは1列、パソコンでは2列に並びます。 */
.p-ow__checks{
  display:grid; gap:20px; grid-template-columns:repeat(auto-fit,minmax(320px,1fr));
}
/* 屋根は3つなので、3列まで広がってよいことにします */
.p-ow__checks--roof{ grid-template-columns:repeat(auto-fit,minmax(290px,1fr)); }

.p-ow__check{
  background:#fff; border:1px solid #eee; border-radius:14px; overflow:hidden;
  display:flex; flex-direction:column; box-shadow:var(--shadow);
}
.p-ow__ph{ background:#fff; }
.p-ow__ph img{
  display:block; width:100%; height:auto; aspect-ratio:4/3; object-fit:cover;
}
.p-ow__body{ padding:18px 20px 22px; }

.p-ow__name{
  margin:0 0 10px; font-size:clamp(17px,4.6vw,19px); font-weight:900; color:var(--ink);
  line-height:1.45; word-break:auto-phrase;
}
/* 「（スレート瓦）」のような、屋根材の名前 */
.p-ow__sub{
  display:block; margin-top:4px; font-size:13px; font-weight:700; color:var(--brand-dark);
}

/* 劣化度。「とんとこトン」5匹のうち、いくつ分かで表します。
   数字だけより、ぱっと見て強さが伝わります。 */
/* 「劣化度」の帯。カードのいちばん上、写真の上にのせています。
   ここが札ごとに見くらべる目じるしになるので、先に目に入る場所です。 */
.p-ow__lv{
  display:flex; align-items:center; gap:10px; margin:0;
  background:var(--brand-tint); border-bottom:1px solid var(--brand-tint2);
  padding:9px 14px;
}
.p-ow__lvlbl{
  flex:none; background:var(--brand); color:#fff; border-radius:999px;
  padding:5px 12px; font-size:12.5px; font-weight:900; letter-spacing:.08em;
  white-space:nowrap;
}
.p-ow__pigs{ display:flex; gap:8px; }
.p-ow__pig{
  display:block; width:27px; height:27px;
}
/* まだ届いていない分は、うすく出します */
.p-ow__pig.is-off{ opacity:.22; filter:grayscale(1); }

.p-ow__text{
  margin:0; font-size:14px; line-height:1.95; color:#444; word-break:auto-phrase;
}

/* ── 料金表 ─────────────────────────────────
   パソコンでは「表」、スマホでは「1行＝1枚のカード」に組み替わります。
   横スクロールする表は、シニアの方にはとても読みにくいためです。 */
.p-ow__pricebox{
  background:#fff; border:1px solid #eee; border-radius:14px;
  padding:clamp(20px,4.5vw,30px); margin-bottom:22px; box-shadow:var(--shadow);
}
.p-ow__pricettl{
  margin:0 0 8px; font-size:clamp(18px,4.8vw,22px); font-weight:900; color:var(--ink);
  padding-bottom:10px; border-bottom:3px solid var(--brand);
}
.p-ow__pricelead{
  margin:0 0 18px; font-size:14px; line-height:1.9; color:#444; word-break:auto-phrase;
}

.p-ow__table{ display:block; }

/* 表の見出し（パソコンのみ） */
.p-ow__thead{
  display:grid; grid-template-columns:1.5fr repeat(3,1fr) 1.1fr; gap:1px;
  background:var(--brand); border-radius:8px 8px 0 0; overflow:hidden;
}
.p-ow__thead span{
  padding:11px 8px; text-align:center; color:#fff;
  font-size:13px; font-weight:900; letter-spacing:.02em;
}

.p-ow__trow{
  display:grid; grid-template-columns:1.5fr repeat(3,1fr) 1.1fr; gap:1px;
  background:var(--border);
}
.p-ow__trow:last-child{ border-radius:0 0 8px 8px; overflow:hidden; }

.p-ow__tname{
  background:#fdf8f6; padding:14px 12px; display:flex; flex-direction:column;
  justify-content:center; font-size:14.5px; font-weight:900; color:var(--ink);
  line-height:1.45; word-break:auto-phrase;
}
.p-ow__tsub{ font-size:11.5px; font-weight:700; color:var(--ink-sub); margin-top:3px; }

.p-ow__tcell{
  background:#fff; padding:14px 8px; display:flex; align-items:center; justify-content:center;
}
.p-ow__tyen{
  color:var(--brand); font-weight:900; font-size:clamp(17px,2.4vw,21px); line-height:1.2;
  white-space:nowrap;
}
.p-ow__tyen small{ font-size:.62em; margin-left:1px; }
.p-ow__tlife{ font-size:13.5px; font-weight:900; color:var(--ink); white-space:nowrap; }
.p-ow__tcell--life{ background:#fdf8f6; }

.p-ow__pricenotes{
  margin:14px 0 0; padding:0; list-style:none;
}
.p-ow__pricenotes li{
  position:relative; padding-left:1.2em; margin-bottom:5px;
  font-size:13px; line-height:1.85; color:#555; word-break:auto-phrase;
}
.p-ow__pricenotes li::before{ content:'※'; position:absolute; left:0; color:var(--brand); }

/* スマホ：1行を1枚のカードにします */
@media (max-width:700px){
  .p-ow__thead{ display:none; }
  .p-ow__trow{
    display:block; background:#fff; border:2px solid var(--brand-tint2);
    border-radius:12px; margin-bottom:12px; overflow:hidden;
  }
  .p-ow__trow:last-child{ margin-bottom:0; }
  .p-ow__tname{
    background:var(--brand-tint); padding:11px 14px; font-size:15.5px;
    flex-direction:row; align-items:baseline; gap:8px;
  }
  .p-ow__tsub{ margin-top:0; }
  .p-ow__tcell{
    justify-content:space-between; padding:10px 14px;
    border-top:1px solid var(--border);
  }
  .p-ow__tcell::before{
    content:attr(data-lbl); font-size:13px; font-weight:700; color:var(--ink-sub);
  }
  .p-ow__tcell--life{ background:#fff; }
}

/* ── 専門サイトへの出口 ─────────────────────────
   このページでいちばん大事な部品です。
   ほかのボタンより大きく、目立つようにしています。 */
/* 左にとんとこトン、右に文章とボタン。
   画面がせまいときは、上下に積んで真ん中ぞろえにします。 */
.p-ow__site{
  position:relative; background:#fff; border-radius:var(--radius-lg);
  padding:clamp(28px,6vw,44px) clamp(20px,5vw,48px);
  box-shadow:var(--shadow-md);
  display:flex; align-items:center; gap:clamp(18px,4vw,40px); text-align:left;
}
.p-ow__sitechara{ flex:none; display:block; width:clamp(96px,15vw,180px); }
.p-ow__sitechara img{ display:block; width:100%; height:auto; }
.p-ow__sitebody{ flex:1; min-width:0; }

@media (max-width:640px){
  .p-ow__site{ flex-direction:column; text-align:center; gap:14px; }
  .p-ow__sitechara{ width:120px; }
  .p-ow__sitebody{ width:100%; }
}
.p-ow__siteen{
  margin:0 0 6px; font-size:12px; font-weight:900; letter-spacing:.14em; color:var(--brand);
}
.p-ow__sitettl{
  margin:0 0 14px; font-size:clamp(20px,5.4vw,28px); font-weight:900; color:var(--ink);
  line-height:1.45; word-break:auto-phrase;
}
.p-ow__sitetext{
  margin:0 0 22px; font-size:clamp(14px,3.7vw,15px); line-height:1.95; color:#444;
  word-break:auto-phrase;
}
.p-ow__sitebtn{
  display:inline-flex; flex-direction:column; align-items:center; gap:4px;
  min-width:min(100%,420px); padding:18px 28px;
  background:var(--brand); color:#fff; border-radius:999px; text-decoration:none;
  font-size:clamp(15px,4.2vw,18px); font-weight:900; line-height:1.4;
  box-shadow:0 6px 0 var(--brand-deep); transition:transform .12s, box-shadow .12s;
}
.p-ow__sitebtn:hover{ transform:translateY(2px); box-shadow:0 4px 0 var(--brand-deep); }
.p-ow__sitebtn:focus-visible{ outline:3px solid var(--brand-deep); outline-offset:3px; }
.p-ow__sitebtnsub{
  font-size:11.5px; font-weight:700; letter-spacing:.02em; opacity:.92;
}

/* ── まとめて頼めます ───────────────────────── */
.p-ow__merits{
  margin:0; padding:0; list-style:none;
  display:grid; gap:16px; grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
}
.p-ow__merit{
  background:var(--bg-soft); border-radius:14px; padding:22px 20px;
}
.p-ow__meritttl{
  margin:0 0 8px; font-size:clamp(15px,4vw,17px); font-weight:900; color:var(--brand-dark);
  line-height:1.5; word-break:auto-phrase;
}
.p-ow__merit p{
  margin:0; font-size:14px; line-height:1.95; color:#444; word-break:auto-phrase;
}
YMKRF_OW_CSS;
}

/* ============================================================
   2. 保存してあるもの
   ============================================================ */
function ymkrf_ow_html() {
	$v = get_option( 'ymkrf_ow_html' );
	return ( is_string( $v ) && trim( $v ) !== '' ) ? $v : ymkrf_ow_default_html();
}

function ymkrf_ow_css() {
	$v = get_option( 'ymkrf_ow_css' );
	return ( is_string( $v ) && trim( $v ) !== '' ) ? $v : ymkrf_ow_default_css();
}


/* ============================================================
   3. 合言葉を、中身に入れかえます
   ============================================================ */

/** 劣化度の「とんとこトン」を5つ並べます */
function ymkrf_ow_pigs( $lv ) {
	$lv  = max( 0, min( 5, (int) $lv ) );
	$src = get_stylesheet_directory_uri() . '/assets/img/outerwall/lv-pig.webp';

	$out = '<span class="p-ow__pigs" role="img" aria-label="劣化度5段階のうち' . $lv . '">';
	for ( $i = 1; $i <= 5; $i++ ) {
		$out .= '<img class="p-ow__pig' . ( $i <= $lv ? '' : ' is-off' ) . '"'
		      . ' src="' . esc_url( $src ) . '" width="207" height="207"'
		      . ' alt="" loading="lazy" decoding="async">';
	}
	return $out . '</span>';
}

/** ページに出すHTMLを組み立てます */
function ymkrf_ow_render() {

	$h = ymkrf_ow_html();

	/* 劣化度（{{劣化度3}}） */
	$h = preg_replace_callback( '/\{\{\s*劣化度\s*([0-5])\s*\}\}/u', function ( $m ) {
		return ymkrf_ow_pigs( (int) $m[1] );
	}, $h );

	/* 施工事例 */
	if ( strpos( $h, '{{施工事例}}' ) !== false ) {
		ob_start();
		if ( function_exists( 'ymkrf_works_section' ) ) {
			ymkrf_works_section( 'outer-wall', '外壁・屋根', 3 );
		}
		$h = str_replace( '{{施工事例}}', ob_get_clean(), $h );
	}

	/* お問い合わせのボタン */
	if ( strpos( $h, '{{お問い合わせ}}' ) !== false ) {
		ob_start();
		if ( function_exists( 'ymkrf_product_cta' ) ) {
			ymkrf_product_cta( 'outerwall-bottom', true );
		}
		$h = str_replace( '{{お問い合わせ}}', ob_get_clean(), $h );
	}

	/* URL */
	$h = str_replace(
		array( '{{テーマ}}', '{{ホーム}}', '{{商品一覧}}' ),
		array(
			get_stylesheet_directory_uri(),
			home_url( '/' ),
			function_exists( 'ymkrf_products_url' ) ? ymkrf_products_url() : home_url( '/products/' ),
		),
		$h
	);

	return $h;
}

/** このページだけに、書いたCSSを足します */
add_action( 'wp_head', function () {

	if ( ! is_tax( 'ymkrf_product_cat', 'outer-wall' ) ) return;

	$css = trim( ymkrf_ow_css() );
	if ( $css === '' ) return;   /* からっぽにしたときは、何も出しません */

	/* <style> の中に </style> が入らないようにします */
	$css = str_replace( array( '</style', '<style' ), '', $css );

	echo "\n<style id=\"ymkrf-ow-css\">\n" . $css . "\n</style>\n";
}, 20 );


/* ============================================================
   4. 画面（商品カテゴリ「外壁・屋根」から開きます）
   ============================================================ */
add_action( 'admin_menu', function () {

	add_submenu_page(
		'edit.php?post_type=ymkrf_product',
		'外壁・屋根ページ', '外壁・屋根ページ',
		'manage_options', 'ymkrf-outerwall', 'ymkrf_ow_page'
	);

	/* ★メニューには出しません（2026/09/17 ユーザー指示
	     「ダッシュボードの商品下の外壁・屋根ぺージは不要です、削除して」）

	   画面そのものは残します。消してしまうと、どこからも開けなくなるためです。
	   開く道は、この2つです。
	     ・商品 ＞ 商品カテゴリ ＞「外壁・屋根」の行の「ページの中身を直す」
	     ・商品一覧を「外壁・屋根」でしぼる（自動でこの画面に移ります） */
	remove_submenu_page( 'edit.php?post_type=ymkrf_product', 'ymkrf-outerwall' );

}, 41 );

function ymkrf_ow_tabs() {
	return array(
		'html' => 'HTML（ページの中身）',
		'css'  => 'CSS（見た目）',
		'help' => '書きかた',
	);
}

function ymkrf_ow_page() {

	if ( ! current_user_can( 'manage_options' ) ) return;

	$tabs = ymkrf_ow_tabs();
	$now  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'html';
	if ( ! isset( $tabs[ $now ] ) ) $now = 'html';

	$msg = '';

	/* ---------- 保存 ---------- */
	if ( isset( $_POST['ymkrf_ow_nonce'] ) &&
	     wp_verify_nonce( $_POST['ymkrf_ow_nonce'], 'ymkrf_ow_save' ) ) {

		if ( $now === 'html' && isset( $_POST['ow_html'] ) ) {
			/* HTMLはそのまま保存します（直せるようにするのが目的なので、消しません） */
			update_option( 'ymkrf_ow_html', (string) wp_unslash( $_POST['ow_html'] ) );
			$msg = '保存しました。ページにすぐ反映されます。';
		}
		if ( $now === 'css' && isset( $_POST['ow_css'] ) ) {
			update_option( 'ymkrf_ow_css', (string) wp_unslash( $_POST['ow_css'] ) );
			$msg = '保存しました。ページにすぐ反映されます。';
		}
	}

	/* ---------- もとにもどす ---------- */
	if ( isset( $_POST['ymkrf_ow_reset'] ) &&
	     isset( $_POST['ymkrf_ow_nonce_r'] ) &&
	     wp_verify_nonce( $_POST['ymkrf_ow_nonce_r'], 'ymkrf_ow_reset' ) ) {

		if ( $now === 'html' ) { delete_option( 'ymkrf_ow_html' ); $msg = 'はじめの内容にもどしました。'; }
		if ( $now === 'css' )  { delete_option( 'ymkrf_ow_css' );  $msg = 'はじめの見た目にもどしました。'; }
	}

	$url = function_exists( 'ymkrf_cat_url' ) ? ymkrf_cat_url( 'outer-wall' ) : home_url( '/products/outer-wall/' );
	?>
	<style>
	  .ymkrf-ow-ta{
	    width:100%;font-family:Consolas,"Courier New",monospace;font-size:12.5px;line-height:1.7;
	    white-space:pre;overflow-wrap:normal;overflow-x:auto;tab-size:2;
	  }
	  .ymkrf-ow-help{max-width:960px;background:#fff;border:1px solid #dcdcde;padding:4px 20px 14px}
	  .ymkrf-ow-help h3{margin:20px 0 6px}
	  .ymkrf-ow-help table{border-collapse:collapse;margin:6px 0 10px}
	  .ymkrf-ow-help td{border:1px solid #dcdcde;padding:6px 10px;font-size:13px;vertical-align:top}
	  .ymkrf-ow-help code{background:#f6f7f7;padding:1px 5px;border-radius:3px}
	</style>

	<div class="wrap">
	  <h1>外壁・屋根ページ</h1>

	  <?php if ( $msg ) : ?>
	    <div class="notice notice-success"><p><?php echo esc_html( $msg ); ?></p></div>
	  <?php endif; ?>

	  <p style="max-width:960px;font-size:13.5px;line-height:1.9">
	    外壁・屋根のページ（<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $url ); ?></a>）の中身です。
	    <b>サーバーの中に入らなくても、ここで直せます。</b><br>
	    このページは商品を登録するところではありません。1枚のご案内ページです。<br>
	    くわしい工事の流れ・よくあるご質問は<b>専門サイト（ヤマキシペイント）の担当</b>です。
	    同じ内容を2つのサイトに置くと、どちらも検索で出にくくなるため、ここには書かないでください。
	  </p>

	  <h2 class="nav-tab-wrapper" style="margin-bottom:18px">
	    <?php foreach ( $tabs as $k => $label ) : ?>
	      <a class="nav-tab <?php echo $k === $now ? 'nav-tab-active' : ''; ?>"
	         href="<?php echo esc_url( add_query_arg( array(
	           'post_type' => 'ymkrf_product', 'page' => 'ymkrf-outerwall', 'tab' => $k,
	         ), admin_url( 'edit.php' ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
	    <?php endforeach; ?>
	  </h2>

	  <?php if ( $now === 'html' ) : ?>

	    <div class="notice notice-warning" style="max-width:960px">
	      <p>
	        タグを閉じ忘れると、ページの見た目がくずれます。<br>
	        直したら、かならず<b>ページを開いて確かめて</b>ください。
	        おかしくなったら、いちばん下の<b>「はじめの内容にもどす」</b>で元に戻せます。
	      </p>
	    </div>

	    <form method="post">
	      <?php wp_nonce_field( 'ymkrf_ow_save', 'ymkrf_ow_nonce' ); ?>
	      <textarea class="ymkrf-ow-ta" name="ow_html" rows="34" spellcheck="false"><?php
	        echo esc_textarea( ymkrf_ow_html() ); ?></textarea>
	      <?php submit_button( 'この内容で保存する' ); ?>
	    </form>

	  <?php elseif ( $now === 'css' ) : ?>

	    <p style="max-width:960px;font-size:13.5px;line-height:1.9">
	      このページの見た目を決めているCSSです。<b>いま出ているものがそのまま入っています。</b><br>
	      <b>このページだけ</b>に読みこまれるので、ほかのページには影響しません。
	      テーマのCSSより後に読まれるので、ここに書いたものが勝ちます。<br>
	      <code>var(--brand)</code> などは、サイト全体で決めている色です。そのまま使えます。
	    </p>

	    <div class="notice notice-warning" style="max-width:960px">
	      <p>
	        カッコ <code>{ }</code> を閉じ忘れると、そこから下がぜんぶ効かなくなります。<br>
	        直したら、かならず<b>ページを開いて確かめて</b>ください。
	        おかしくなったら、いちばん下の<b>「はじめの内容にもどす」</b>で元に戻せます。
	      </p>
	    </div>

	    <form method="post">
	      <?php wp_nonce_field( 'ymkrf_ow_save', 'ymkrf_ow_nonce' ); ?>
	      <textarea class="ymkrf-ow-ta" name="ow_css" rows="34" spellcheck="false"><?php
	        echo esc_textarea( ymkrf_ow_css() ); ?></textarea>
	      <?php submit_button( 'この内容で保存する' ); ?>
	    </form>

	  <?php else : ?>

	    <div class="ymkrf-ow-help">
	      <h3>差しこみの合言葉</h3>
	      <p>HTMLの中に次の言葉を書くと、その場所に中身が入ります。</p>
	      <table>
	        <tr><td><code>{{テーマ}}</code></td><td>テーマのURL。写真はこの下にあります。<br>
	          例：<code>&lt;img src="{{テーマ}}/assets/img/outerwall/wall1-fade.jpg"&gt;</code></td></tr>
	        <tr><td><code>{{ホーム}}</code></td><td>サイトのトップのURL</td></tr>
	        <tr><td><code>{{商品一覧}}</code></td><td>商品・価格ページのURL</td></tr>
	        <tr><td><code>{{劣化度3}}</code></td><td>とんとこトンの目じるし。1〜5で書きます</td></tr>
	        <tr><td><code>{{施工事例}}</code></td><td>「外壁・屋根」の施工事例が3件出ます</td></tr>
	        <tr><td><code>{{お問い合わせ}}</code></td><td>「無料の現地調査・お見積り」「お電話」のボタン</td></tr>
	      </table>

	      <h3>写真の入れかた</h3>
	      <p>
	        メディアに入れた写真を使うときは、メディアで写真を開いて<b>「ファイルのURL」</b>をコピーし、
	        <code>src="……"</code> に貼ってください。<br>
	        テーマにもとから入っている写真は <code>{{テーマ}}/assets/img/outerwall/</code> の下にあります。
	      </p>

	      <h3>よく使う部品</h3>
	      <table>
	        <tr><td><code>&lt;section class="l-section"&gt;</code></td><td>白い帯のかたまり</td></tr>
	        <tr><td><code>&lt;section class="l-section l-section--gray"&gt;</code></td><td>グレーの帯</td></tr>
	        <tr><td><code>&lt;div class="l-wrap"&gt;</code></td><td>中身の横はばをそろえる箱</td></tr>
	        <tr><td><code>&lt;span class="marker"&gt;言葉&lt;/span&gt;</code></td><td>マーカーを引く</td></tr>
	        <tr><td><code>&lt;br class="sp-only"&gt;</code></td><td>スマホだけで改行</td></tr>
	        <tr><td><code>&lt;br class="pc-only"&gt;</code></td><td>パソコンだけで改行</td></tr>
	      </table>

	      <h3>料金表について</h3>
	      <p>
	        スマホでは、表が横スクロールにならないよう、1行ずつのカードに組み替わります。<br>
	        そのため、金額のところには <code>data-lbl="〜100㎡"</code> のような<b>見出しの控え</b>を
	        かならず付けてください。付け忘れると、スマホで何の金額か分からなくなります。
	      </p>

	      <h3>書いてはいけない言葉</h3>
	      <p style="color:#b32d2e">
	        「追加請求はありません」「追加料金なし」「これ以上かかりません」は使いません。<br>
	        かわりに「<b>追加の工事が必要なときは、着工前にかならずお見積りをお出しします</b>」と書きます。
	      </p>
	    </div>

	  <?php endif; ?>

	  <?php if ( $now !== 'help' ) : ?>
	    <hr>
	    <form method="post"
	          onsubmit="return confirm('いま書いてある内容を取り消して、もとにもどしますか？');">
	      <?php wp_nonce_field( 'ymkrf_ow_reset', 'ymkrf_ow_nonce_r' ); ?>
	      <button class="button" name="ymkrf_ow_reset" value="1"><?php
	        echo 'はじめの内容にもどす'; ?></button>
	      <p class="description">
	        <?php echo $now === 'html'
	          ? 'ここで直したものを取り消して、はじめのページの中身にもどします。'
	          : 'ここで直したものを取り消して、はじめの見た目にもどします。'; ?>
	      </p>
	    </form>
	  <?php endif; ?>

	</div>
	<?php
}


/* ============================================================
   5. 「どこにあるか分からない」を防ぐための案内
   ------------------------------------------------------------
   （2026/09/17 ユーザー
     「edit.php?post_type=ymkrf_product&ymkrf_product_cat=outer-wall
       このぺージにつくってない？？？」）

   外壁・屋根は商品を登録しないので、商品一覧を「外壁・屋根」で
   しぼると、まっ白で何もありません。
   そこに「直すのはこっちです」という案内を出します。
   ============================================================ */

/** 外壁・屋根ページの編集画面のURL */
function ymkrf_ow_admin_url( $tab = 'html' ) {
	return add_query_arg(
		array( 'post_type' => 'ymkrf_product', 'page' => 'ymkrf-outerwall', 'tab' => $tab ),
		admin_url( 'edit.php' )
	);
}

/* ① 商品一覧を「外壁・屋根」でしぼったら、編集画面をそのまま開きます
      （2026/09/17 ユーザー「え？ここにつくってっていわなかったっけ？」）

      外壁・屋根には商品を1つも登録しないので、
      このしぼりこみを見にくる理由は「ページを直したい」以外にありません。
      ですので、空っぽの一覧を見せずに、編集画面へお連れします。 */
add_action( 'load-edit.php', function () {

	if ( ! isset( $_GET['post_type'] ) || $_GET['post_type'] !== 'ymkrf_product' ) return;
	if ( ! isset( $_GET['ymkrf_product_cat'] ) ) return;
	if ( sanitize_title( wp_unslash( $_GET['ymkrf_product_cat'] ) ) !== 'outer-wall' ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;

	wp_safe_redirect( ymkrf_ow_admin_url() );
	exit;
} );

/* ② 商品カテゴリの一覧で、「外壁・屋根」の行にリンクを足します */
add_filter( 'tag_row_actions', function ( $actions, $term ) {

	if ( ! isset( $term->taxonomy ) || $term->taxonomy !== 'ymkrf_product_cat' ) return $actions;
	if ( $term->slug !== 'outer-wall' ) return $actions;

	$actions['ymkrf_ow'] = '<a href="' . esc_url( ymkrf_ow_admin_url() ) . '"><b>ページの中身を直す</b></a>';
	return $actions;
}, 10, 2 );

/* ③ 商品カテゴリの一覧を開いたときの、ひとこと案内 */
add_action( 'admin_notices', function () {

	$s = get_current_screen();
	if ( ! $s || $s->id !== 'edit-ymkrf_product_cat' ) return;
	?>
	<div class="notice notice-info is-dismissible">
	  <p style="line-height:1.9">
	    <b>外壁・屋根</b>だけは、商品を登録しません。1枚のご案内ページです。<br>
	    ページの中身（HTML・CSS）は、下の一覧の「外壁・屋根」にマウスを乗せると出る
	    <a href="<?php echo esc_url( ymkrf_ow_admin_url() ); ?>"><b>「ページの中身を直す」</b></a>
	    から直します。
	  </p>
	</div>
	<?php
} );


/* ============================================================
   6. 商品の登録画面から「外壁・屋根」を出さないようにします
   ------------------------------------------------------------
   （2026/09/17 ユーザー指示
     「商品の登録画面左のカテゴリ欄に『外壁・屋根』は不要」）

   外壁・屋根には商品を登録しません。
   登録画面にこの名前が出ていると、
   「ここに外壁の商品を入れるのかな？」と迷う元になります。

   ★分類そのものは消しません。
     /products/outer-wall/ というページの住所に使っているためです。
     商品カテゴリの一覧には、これまでどおり出ます。
   ============================================================ */
add_action( 'admin_head', function () {

	$s = get_current_screen();
	if ( ! $s || $s->post_type !== 'ymkrf_product' ) return;
	if ( ! in_array( $s->base, array( 'post' ), true ) ) return;

	$t = get_term_by( 'slug', 'outer-wall', 'ymkrf_product_cat' );
	if ( ! $t || is_wp_error( $t ) ) return;

	$id = (int) $t->term_id;
	echo '<style id="ymkrf-ow-hide">'
	   . '#ymkrf_product_cat-' . $id . ','
	   . '#ymkrf_product_cat-pop-' . $id . '{display:none !important}'
	   . '</style>' . "\n";
} );
