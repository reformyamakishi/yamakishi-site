<?php
/**
 * ymkrf-pack4.php ─ Web限定 水まわり4点パック（/products/pack4/）
 *
 * 置き場所： wp-content/themes/ymkrf/ymkrf-pack4.php
 *
 * このページは商品カテゴリでも商品でもなく、専用のURLで出しています。
 * URLの割りあては inc/functions-product.php の「4点パック」の項をごらんください。
 *
 * ★内容を直すとき
 *   プラン名・製品・仕様・金額は、すべてこのファイルの中に書いてあります。
 *   商品ページへのリンクは ymkrf_prd_url( 'スラッグ' ) を通しているので、
 *   その商品がまだ無いときは商品一覧に着地します（リンク切れになりません）。
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$asset = get_stylesheet_directory_uri();
get_header();
?>

<!-- =========== パンくず =========== -->
<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li><a href="<?php echo esc_url( ymkrf_products_url() ); ?>">商品・価格</a></li>
    <li>水まわり4点パック</li>
  </ol>
</nav>

<main id="main">

<div class="p-pagehead">
  <div class="l-wrap p-pagehead__inner">
    <span class="p-pagehead__en">4-PIECE PACK</span>
    <h1 class="p-pagehead__title">Web限定<br class="xs-only">水まわり4点パック</h1>
    <p class="p-pagehead__lead">
      キッチン・お風呂・洗面化粧台・トイレを<br class="xs-only">まとめて交換。<br>
      別々に頼むより、ぐんとおトクです。
    </p>
  </div>
</div>

<section class="l-section">
  <div class="l-wrap">

    <div class="p-pack__lead" data-reveal>
      キッチン・お風呂・洗面化粧台・トイレを<b>まとめて4点</b>取り替えるプランです。<br>
      別々に頼むより工事がまとまるぶん、<b>費用も工期もぐっと抑えられます。</b><br>
      下の金額は、本体・標準工事費・古い設備の撤去・処分費・保証まで<b>すべて込みの税込価格</b>です。
      あとからの追加請求はありません。
    </div>

      <div class="p-pack__plan" data-reveal>

        <div class="p-pack__head">
          <span class="p-pack__no">PLAN 1</span>
          <h2 class="p-pack__name">お財布に優しいプラン</h2>
          <p class="p-pack__catch">価格を抑えた激安仕様</p>
        </div>

        <div class="p-pack__grid">
        <a class="p-pack__item" href="<?php echo esc_url( ymkrf_prd_url( 'v-style' ) ); ?>">
          <p class="p-pack__part">キッチン</p>
          <div class="p-pack__photo">
            <picture>
              <source srcset="<?php echo $asset; ?>/assets/img/products/v-style/main.webp" type="image/webp">
              <img src="<?php echo $asset; ?>/assets/img/products/v-style/main.jpg" width="800" height="600"
                   alt="Panasonic リビングステーションV" loading="lazy" decoding="async">
            </picture>
          </div>
          <div class="p-pack__body">
            <p class="p-pack__maker">Panasonic</p>
            <h3 class="p-pack__prd">リビングステーションV</h3>
            <p class="p-pack__spectitle">製品仕様</p>
            <ul class="p-pack__spec">
            <li>I型 2550サイズ</li>
            <li>ホーロー3口トップコンロ</li>
            <li>静音ステンレスシンク</li>
            <li>フロアストッカー</li>
            <li>耐震ロック機構付き吊戸棚</li>
            </ul>
            <p class="p-pack__more">くわしく見る</p>
          </div>
        </a>
        <a class="p-pack__item" href="<?php echo esc_url( ymkrf_prd_url( 'ofuroa' ) ); ?>">
          <p class="p-pack__part">お風呂</p>
          <div class="p-pack__photo">
            <picture>
              <source srcset="<?php echo $asset; ?>/assets/img/products/ofuroa/ofuroa-main.webp" type="image/webp">
              <img src="<?php echo $asset; ?>/assets/img/products/ofuroa/ofuroa-main.jpg" width="800" height="600"
                   alt="Panasonic オフローラ" loading="lazy" decoding="async">
            </picture>
          </div>
          <div class="p-pack__body">
            <p class="p-pack__maker">Panasonic</p>
            <h3 class="p-pack__prd">オフローラ</h3>
            <p class="p-pack__spectitle">製品仕様</p>
            <ul class="p-pack__spec">
            <li>スミピカフロア</li>
            <li>オーバルカウンター</li>
            <li>スキットドア</li>
            <li>ささっと排水口</li>
            </ul>
            <p class="p-pack__more">くわしく見る</p>
          </div>
        </a>
        <a class="p-pack__item" href="<?php echo esc_url( ymkrf_prd_url( 'v1' ) ); ?>">
          <p class="p-pack__part">洗面化粧台</p>
          <div class="p-pack__photo">
            <picture>
              <source srcset="<?php echo $asset; ?>/assets/img/products/v1/v1-main.webp" type="image/webp">
              <img src="<?php echo $asset; ?>/assets/img/products/v1/v1-main.jpg" width="800" height="600"
                   alt="LIXIL V1" loading="lazy" decoding="async">
            </picture>
          </div>
          <div class="p-pack__body">
            <p class="p-pack__maker">LIXIL</p>
            <h3 class="p-pack__prd">V1</h3>
            <p class="p-pack__spectitle">製品仕様</p>
            <ul class="p-pack__spec">
            <li>広々スペース（奥行50cm）</li>
            <li>スキマなし排水口</li>
            <li>省エネ設計エコハンドル</li>
            <li>開き扉キャビネット</li>
            </ul>
            <p class="p-pack__more">くわしく見る</p>
          </div>
        </a>
        <a class="p-pack__item" href="<?php echo esc_url( ymkrf_prd_url( 'amage-z' ) ); ?>">
          <p class="p-pack__part">トイレ</p>
          <div class="p-pack__photo">
            <picture>
              <source srcset="<?php echo $asset; ?>/assets/img/products/amage-z/amzi-main.webp" type="image/webp">
              <img src="<?php echo $asset; ?>/assets/img/products/amage-z/amzi-main.jpg" width="800" height="600"
                   alt="LIXIL アメージュZ ハイパーキラミックシャワートイレRG10H" loading="lazy" decoding="async">
            </picture>
          </div>
          <div class="p-pack__body">
            <p class="p-pack__maker">LIXIL</p>
            <h3 class="p-pack__prd">アメージュZ ハイパーキラミック<br>シャワートイレRG10H</h3>
            <p class="p-pack__spectitle">製品仕様</p>
            <ul class="p-pack__spec">
            <li>パワーストリーム洗浄</li>
            <li>フチレス形状・キレイ便座</li>
            <li>レディスノズル</li>
            <li>シャープなフォルム</li>
            <li>暖房便座</li>
            </ul>
            <p class="p-pack__more">くわしく見る</p>
          </div>
        </a>
        </div>

        <div class="p-pack__price">
          <p class="p-pack__was">通常価格 <b>1,650,600円</b>（税込）のところ</p>
          <p class="p-pack__now"><span class="num">1,628,000</span><span class="unit">円<small class="tax">（税込）</small></span></p>
          <p class="p-pack__incl">標準工事費込み</p>
          <p class="p-pack__save">4点まとめて 22,600円 おトク</p>
        </div>

      </div>

      <div class="p-pack__plan" data-reveal>

        <div class="p-pack__head">
          <span class="p-pack__no">PLAN 2</span>
          <h2 class="p-pack__name">間違いないスタンダードプラン</h2>
          <p class="p-pack__catch">大好評の機能をパッケージ！</p>
        </div>

        <div class="p-pack__grid">
        <a class="p-pack__item" href="<?php echo esc_url( ymkrf_prd_url( 'rakuera' ) ); ?>">
          <p class="p-pack__part">キッチン</p>
          <div class="p-pack__photo">
            <picture>
              <source srcset="<?php echo $asset; ?>/assets/img/products/rakuera/raku-main-product.webp" type="image/webp">
              <img src="<?php echo $asset; ?>/assets/img/products/rakuera/raku-main-product.jpg" width="800" height="600"
                   alt="クリナップ ラクエラ" loading="lazy" decoding="async">
            </picture>
          </div>
          <div class="p-pack__body">
            <p class="p-pack__maker">クリナップ</p>
            <h3 class="p-pack__prd">ラクエラ</h3>
            <p class="p-pack__spectitle">製品仕様</p>
            <ul class="p-pack__spec">
            <li>ステンレス天板・シンク</li>
            <li>フラットスリムレンジフード</li>
            <li>サイレントレール</li>
            <li>ミドル吊戸棚</li>
            <li>省エネシングル水栓</li>
            </ul>
            <p class="p-pack__more">くわしく見る</p>
          </div>
        </a>
        <a class="p-pack__item" href="<?php echo esc_url( ymkrf_prd_url( 'lidea-m' ) ); ?>">
          <p class="p-pack__part">お風呂</p>
          <div class="p-pack__photo">
            <picture>
              <source srcset="<?php echo $asset; ?>/assets/img/products/lidea-m/lidea-main.webp" type="image/webp">
              <img src="<?php echo $asset; ?>/assets/img/products/lidea-m/lidea-main.jpg" width="800" height="600"
                   alt="LIXIL リデア Mタイプ" loading="lazy" decoding="async">
            </picture>
          </div>
          <div class="p-pack__body">
            <p class="p-pack__maker">LIXIL</p>
            <h3 class="p-pack__prd">リデア Mタイプ</h3>
            <p class="p-pack__spectitle">製品仕様</p>
            <ul class="p-pack__spec">
            <li>キレイサーモフロア</li>
            <li>あたたかパック</li>
            <li>まる洗いカウンター</li>
            <li>パッとくるりんぽい排水口</li>
            </ul>
            <p class="p-pack__more">くわしく見る</p>
          </div>
        </a>
        <a class="p-pack__item" href="<?php echo esc_url( ymkrf_prd_url( 'j1' ) ); ?>">
          <p class="p-pack__part">洗面化粧台</p>
          <div class="p-pack__photo">
            <picture>
              <source srcset="<?php echo $asset; ?>/assets/img/products/j1/j1-main.webp" type="image/webp">
              <img src="<?php echo $asset; ?>/assets/img/products/j1/j1-main.jpg" width="800" height="600"
                   alt="LIXIL J1" loading="lazy" decoding="async">
            </picture>
          </div>
          <div class="p-pack__body">
            <p class="p-pack__maker">LIXIL</p>
            <h3 class="p-pack__prd">J1</h3>
            <p class="p-pack__spectitle">製品仕様</p>
            <ul class="p-pack__spec">
            <li>3面鏡ミラーキャビネット</li>
            <li>くもり止めコート</li>
            <li>人造大理石ボウル</li>
            <li>シングルレバー洗髪シャワー水栓</li>
            <li>洗面器一体カウンター</li>
            <li>新てまなし排水口</li>
            </ul>
            <p class="p-pack__more">くわしく見る</p>
          </div>
        </a>
        <a class="p-pack__item" href="<?php echo esc_url( ymkrf_prd_url( 'amage-z-premium' ) ); ?>">
          <p class="p-pack__part">トイレ</p>
          <div class="p-pack__photo">
            <picture>
              <source srcset="<?php echo $asset; ?>/assets/img/products/amage-z-premium/amze-main.webp" type="image/webp">
              <img src="<?php echo $asset; ?>/assets/img/products/amage-z-premium/amze-main.jpg" width="800" height="600"
                   alt="LIXIL アメージュZ アクアセラミック高機能モデル（CW-RWA30HQ）" loading="lazy" decoding="async">
            </picture>
          </div>
          <div class="p-pack__body">
            <p class="p-pack__maker">LIXIL</p>
            <h3 class="p-pack__prd">アメージュZ アクアセラミック<br>高機能モデル（CW-RWA30HQ）</h3>
            <p class="p-pack__spectitle">製品仕様</p>
            <ul class="p-pack__spec">
            <li>アクアセラミック</li>
            <li>フチレス形状・キレイ便座</li>
            <li>おしり泡ジェット洗浄</li>
            <li>パワーストリーム洗浄</li>
            <li>シャープなフォルム</li>
            <li>ワンタッチ節電・超節水ECO5</li>
            </ul>
            <p class="p-pack__more">くわしく見る</p>
          </div>
        </a>
        </div>

        <div class="p-pack__price">
          <p class="p-pack__was">通常価格 <b>2,055,600円</b>（税込）のところ</p>
          <p class="p-pack__now"><span class="num">2,018,000</span><span class="unit">円<small class="tax">（税込）</small></span></p>
          <p class="p-pack__incl">標準工事費込み</p>
          <p class="p-pack__save">4点まとめて 37,600円 おトク</p>
        </div>

      </div>

      <div class="p-pack__plan" data-reveal>

        <div class="p-pack__head p-pack__head--best">
          <p class="p-pack__ribbon">いちばん人気</p>
          <span class="p-pack__no">PLAN 3</span>
          <h2 class="p-pack__name">ヤマキシ1番人気ベストプラン</h2>
          <p class="p-pack__catch">ワンランク上のオススメ仕様！</p>
        </div>

        <div class="p-pack__grid">
        <a class="p-pack__item" href="<?php echo esc_url( ymkrf_prd_url( 'stedia' ) ); ?>">
          <p class="p-pack__part">キッチン</p>
          <div class="p-pack__photo">
            <picture>
              <source srcset="<?php echo $asset; ?>/assets/img/products/stedia/stedia-main.webp" type="image/webp">
              <img src="<?php echo $asset; ?>/assets/img/products/stedia/stedia-main.jpg" width="800" height="600"
                   alt="クリナップ ステディア" loading="lazy" decoding="async">
            </picture>
          </div>
          <div class="p-pack__body">
            <p class="p-pack__maker">クリナップ</p>
            <h3 class="p-pack__prd">ステディア</h3>
            <p class="p-pack__spectitle">製品仕様</p>
            <ul class="p-pack__spec">
            <li>I型 2550サイズ</li>
            <li>ステンレス天板・シンク</li>
            <li>とってもクリーンフード</li>
            <li>流レールシンク</li>
            <li>アイエリアボックス</li>
            </ul>
            <p class="p-pack__more">くわしく見る</p>
          </div>
        </a>
        <a class="p-pack__item" href="<?php echo esc_url( ymkrf_prd_url( 'sazana-t' ) ); ?>">
          <p class="p-pack__part">お風呂</p>
          <div class="p-pack__photo">
            <picture>
              <source srcset="<?php echo $asset; ?>/assets/img/products/sazana-t/sazanat-main.webp" type="image/webp">
              <img src="<?php echo $asset; ?>/assets/img/products/sazana-t/sazanat-main.jpg" width="800" height="600"
                   alt="TOTO サザナ Tタイプ" loading="lazy" decoding="async">
            </picture>
          </div>
          <div class="p-pack__body">
            <p class="p-pack__maker">TOTO</p>
            <h3 class="p-pack__prd">サザナ Tタイプ</h3>
            <p class="p-pack__spectitle">製品仕様</p>
            <ul class="p-pack__spec">
            <li>ほっカラリ床</li>
            <li>魔法びん浴槽 ゆるリラ浴槽</li>
            <li>2wayタッチ水栓</li>
            <li>お掃除ラクラクカウンター</li>
            </ul>
            <p class="p-pack__more">くわしく見る</p>
          </div>
        </a>
        <a class="p-pack__item" href="<?php echo esc_url( ymkrf_prd_url( 'fansio' ) ); ?>">
          <p class="p-pack__part">洗面化粧台</p>
          <div class="p-pack__photo">
            <picture>
              <source srcset="<?php echo $asset; ?>/assets/img/products/fansio/fansio-main.webp" type="image/webp">
              <img src="<?php echo $asset; ?>/assets/img/products/fansio/fansio-main.jpg" width="800" height="600"
                   alt="クリナップ ファンシオ" loading="lazy" decoding="async">
            </picture>
          </div>
          <div class="p-pack__body">
            <p class="p-pack__maker">クリナップ</p>
            <h3 class="p-pack__prd">ファンシオ</h3>
            <p class="p-pack__spectitle">製品仕様</p>
            <ul class="p-pack__spec">
            <li>流レールボールLL</li>
            <li>壁出し水栓</li>
            <li>スキンケア3面鏡ミラーキャビネット</li>
            <li>人工大理石ボウル</li>
            <li>オールスライドキャビネット</li>
            </ul>
            <p class="p-pack__more">くわしく見る</p>
          </div>
        </a>
        <a class="p-pack__item" href="<?php echo esc_url( ymkrf_prd_url( 'alauno-s160' ) ); ?>">
          <p class="p-pack__part">トイレ</p>
          <div class="p-pack__photo">
            <picture>
              <source srcset="<?php echo $asset; ?>/assets/img/products/alauno-s160/s160-main.webp" type="image/webp">
              <img src="<?php echo $asset; ?>/assets/img/products/alauno-s160/s160-main.jpg" width="800" height="600"
                   alt="Panasonic アラウーノS160タイプ1K" loading="lazy" decoding="async">
            </picture>
          </div>
          <div class="p-pack__body">
            <p class="p-pack__maker">Panasonic</p>
            <h3 class="p-pack__prd">アラウーノS160<br>タイプ1K</h3>
            <p class="p-pack__spectitle">製品仕様</p>
            <ul class="p-pack__spec">
            <li>オート洗浄</li>
            <li>クローズ洗浄モード</li>
            <li>アラウーノアプリ対応</li>
            <li>トリプル汚れガード</li>
            <li>スゴピカ素材</li>
            <li>暖房便座</li>
            </ul>
            <p class="p-pack__more">くわしく見る</p>
          </div>
        </a>
        </div>

        <div class="p-pack__price">
          <p class="p-pack__was">通常価格 <b>2,745,600円</b>（税込）のところ</p>
          <p class="p-pack__now"><span class="num">2,668,000</span><span class="unit">円<small class="tax">（税込）</small></span></p>
          <p class="p-pack__incl">標準工事費込み</p>
          <p class="p-pack__save">4点まとめて 77,600円 おトク</p>
        </div>

      </div>

      <div class="p-pack__plan" data-reveal>

        <div class="p-pack__head">
          <span class="p-pack__no">PLAN 4</span>
          <h2 class="p-pack__name">主婦が憧れるプレミアムプラン</h2>
          <p class="p-pack__catch">こだわりの大満足仕様！</p>
        </div>

        <div class="p-pack__grid">
        <a class="p-pack__item" href="<?php echo esc_url( ymkrf_prd_url( 'richelle' ) ); ?>">
          <p class="p-pack__part">キッチン</p>
          <div class="p-pack__photo">
            <picture>
              <source srcset="<?php echo $asset; ?>/assets/img/products/richelle/richelle-main.webp" type="image/webp">
              <img src="<?php echo $asset; ?>/assets/img/products/richelle/richelle-main.jpg" width="800" height="600"
                   alt="LIXIL リシェル" loading="lazy" decoding="async">
            </picture>
          </div>
          <div class="p-pack__body">
            <p class="p-pack__maker">LIXIL</p>
            <h3 class="p-pack__prd">リシェル</h3>
            <p class="p-pack__spectitle">製品仕様</p>
            <ul class="p-pack__spec">
            <li>セラミックトップ</li>
            <li>ハンズフリー水栓</li>
            <li>ハイブリッドクォーツシンク</li>
            <li>ソフトモーションレール</li>
            <li>LED照明</li>
            </ul>
            <p class="p-pack__more">くわしく見る</p>
          </div>
        </a>
        <a class="p-pack__item" href="<?php echo esc_url( ymkrf_prd_url( 'selevia' ) ); ?>">
          <p class="p-pack__part">お風呂</p>
          <div class="p-pack__photo">
            <picture>
              <source srcset="<?php echo $asset; ?>/assets/img/products/selevia/selevia-main.webp" type="image/webp">
              <img src="<?php echo $asset; ?>/assets/img/products/selevia/selevia-main.jpg" width="800" height="600"
                   alt="クリナップ セレヴィア" loading="lazy" decoding="async">
            </picture>
          </div>
          <div class="p-pack__body">
            <p class="p-pack__maker">クリナップ</p>
            <h3 class="p-pack__prd">セレヴィア</h3>
            <p class="p-pack__spectitle">製品仕様</p>
            <ul class="p-pack__spec">
            <li>浴室まるごと保温</li>
            <li>まるごとクリンパッキン</li>
            <li>ハイポジション設計</li>
            <li>足ピタパターン</li>
            <li>とってもクリンカウンター</li>
            <li>こだわりデザイン</li>
            </ul>
            <p class="p-pack__more">くわしく見る</p>
          </div>
        </a>
        <a class="p-pack__item" href="<?php echo esc_url( ymkrf_prd_url( 'sakua' ) ); ?>">
          <p class="p-pack__part">洗面化粧台</p>
          <div class="p-pack__photo">
            <picture>
              <source srcset="<?php echo $asset; ?>/assets/img/products/sakua/sakua-main.webp" type="image/webp">
              <img src="<?php echo $asset; ?>/assets/img/products/sakua/sakua-main.jpg" width="800" height="600"
                   alt="TOTO サクア" loading="lazy" decoding="async">
            </picture>
          </div>
          <div class="p-pack__body">
            <p class="p-pack__maker">TOTO</p>
            <h3 class="p-pack__prd">サクア</h3>
            <p class="p-pack__spectitle">製品仕様</p>
            <ul class="p-pack__spec">
            <li>きれい除菌水</li>
            <li>スウィング三面鏡</li>
            <li>エアインスウィング水栓</li>
            <li>ワイドスウィング三面鏡</li>
            <li>ひろびろ陶器ボウル</li>
            <li>2段引出し収納 サイレントレール</li>
            </ul>
            <p class="p-pack__more">くわしく見る</p>
          </div>
        </a>
        <a class="p-pack__item" href="<?php echo esc_url( ymkrf_prd_url( 'neorest-rs3' ) ); ?>">
          <p class="p-pack__part">トイレ</p>
          <div class="p-pack__photo">
            <picture>
              <source srcset="<?php echo $asset; ?>/assets/img/products/neorest-rs3/rs3-main.webp" type="image/webp">
              <img src="<?php echo $asset; ?>/assets/img/products/neorest-rs3/rs3-main.jpg" width="800" height="600"
                   alt="TOTO ネオレスト" loading="lazy" decoding="async">
            </picture>
          </div>
          <div class="p-pack__body">
            <p class="p-pack__maker">TOTO</p>
            <h3 class="p-pack__prd">ネオレスト</h3>
            <p class="p-pack__spectitle">製品仕様</p>
            <ul class="p-pack__spec">
            <li>きれい除菌水</li>
            <li>3.8L洗浄で超節水</li>
            <li>オート開閉</li>
            <li>ワンダーウェーブ洗浄</li>
            <li>スーパーおまかせ節電</li>
            <li>マッサージ洗浄</li>
            <li>セルフクリーニング</li>
            </ul>
            <p class="p-pack__more">くわしく見る</p>
          </div>
        </a>
        </div>

        <div class="p-pack__price">
          <p class="p-pack__was">通常価格 <b>4,065,600円</b>（税込）のところ</p>
          <p class="p-pack__now"><span class="num">3,918,000</span><span class="unit">円<small class="tax">（税込）</small></span></p>
          <p class="p-pack__incl">標準工事費込み</p>
          <p class="p-pack__save">4点まとめて 147,600円 おトク</p>
        </div>

      </div>

  </div>
</section>


<section class="l-section l-section--soft">
  <div class="l-wrap">
    <div class="p-pagecta" data-reveal="zoom">
      <img class="p-pagecta__chara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-tel.webp" width="570" height="640" alt="" loading="lazy">
      <h2 class="p-pagecta__title">どのプランが合うか、<br>一緒に考えます</h2>
      <p class="p-pagecta__text">
        いまのお住まいを見せていただければ、<br class="xs-only">ぴったりのプランをご提案します。<br>
        見積り・現地調査は無料。<br class="xs-only">しつこい営業はいたしません。
      </p>
      <?php ymkrf_product_cta( 'pack4', true ); ?>
    </div>
  </div>
</section>

</main>

<?php get_footer();
