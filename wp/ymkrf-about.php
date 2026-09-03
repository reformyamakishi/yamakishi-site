<?php
/**
 * ymkrf-about.php ─ ヤマキシのこだわり・特徴（/about/）
 *
 * 置き場所： wp-content/themes/ymkrf/ymkrf-about.php
 *
 * 旧サイトの「ヤマキシのこだわり・特徴（/about/）」と
 * 「リフォーム事業コンセプト（/concept/）」を1枚にまとめたページです。
 * URLの割りあては inc/functions-snippet.php の「こだわりページ」の項をごらんください。
 *
 * ★内容を直すとき
 *   文章はすべてこのファイルの中に書いてあります。
 *   見た目は assets/css/lp.css の .p-lp〜 で調整します。
 *
 * ※「追加請求はありません」といった言い切りは書かないでください。
 *   お家の形状や配管・電気の状態によって、追加の工事が必要なことがあります。
 *   「追加の工事が必要なときは、着工前にかならずお見積りをお出しします」が正しい書き方です。
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$asset = get_stylesheet_directory_uri();
get_header();
?>

<!-- =========== パンくず =========== -->
<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li>ヤマキシのこだわり・特徴</li>
  </ol>
</nav>

<main id="main">


<!-- =========== ヒーロー =========== -->
<section class="p-lphero">
  <div class="l-wrap p-lphero__inner">
    <p class="p-lphero__sub">石川・福井のリフォームなら</p>
    <h1 class="p-lphero__title"><em>安い</em>・<em>早い</em>・<em>安心</em>。</h1>
    <p class="p-lphero__title2">そして、リフォームは<em>生活改善</em>！</p>
    <p class="p-lphero__lead">
      ヤマキシは、ただ住まいを直すだけの会社ではありません。<br class="pc-only">
      いまのお住まいで困っていること・不満に思っていることを、どんなことでもお聞かせください。
    </p>
    <img class="p-lphero__chara" src="<?php echo $asset; ?>/assets/img/character/char-hello.webp" width="503" height="640"
         alt="ヤマキシのキャラクター「とんとこトン」があいさつしているイラスト" fetchpriority="high">
  </div>
</section>

<!-- =========== 考え方 =========== -->
<section class="l-section">
  <div class="l-wrap l-wrap--narrow">
    <div class="c-head">
      <span class="c-head__en">CONCEPT</span>
      <img class="p-lpchara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-search.webp"
           width="592" height="640" alt="" loading="lazy" decoding="async">
      <h2 class="c-head__title">住まいの現状の<span class="marker">不満</span>を<br class="sp-only">お聞かせください</h2>
    </div>
    <div class="p-lpconcept">
      <p>
        どんなキッチン・お風呂がいいか、どんな機能がいいか、予算はどれくらいなのか。
        ご希望をお話しいただく際に、<b>いまのお住まいのお困りごとや不満</b>も、
        どんなことでもお聞かせください。
      </p>
      <p>
        これは、ヤマキシではリフォームを <b>「生活改善」</b> としてとらえているからです。
        設備を新しくすることが目的ではなく、その先の暮らしが良くなることが目的だと考えています。
      </p>
      <p>
        お住まいに関する思いをお聞かせいただき、整理して、
        <b>最適なアドバイス</b>をさせていただきたいと考えております。
      </p>
    </div>

    <figure class="p-lpphoto" data-reveal>
      <picture>
        <source srcset="<?php echo $asset; ?>/assets/img/about/soudan.webp" type="image/webp">
        <img src="<?php echo $asset; ?>/assets/img/about/soudan.jpg" width="1600" height="1067" alt="ヤマキシの店舗で、カタログを広げてリフォームのご相談をお受けしているところ" loading="lazy" decoding="async">
      </picture>
      <figcaption>ご相談は、カタログや実物のサンプルを見ながら。お店でも、ご自宅でもお受けします。</figcaption>
    </figure>
  </div>
</section>

<!-- =========== 3つのコンセプト =========== -->
<section class="l-section l-section--soft">
  <div class="l-wrap">
    <div class="c-head">
      <img class="p-lpchara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-smile.webp"
           width="503" height="640" alt="" loading="lazy" decoding="async">
      <span class="c-head__en">3 CONCEPTS</span>
      <h2 class="c-head__title">ヤマキシの<span class="marker">3つのコンセプト</span></h2>
      <p class="c-head__lead">水まわりを中心としたリフォームを、この3つのコンセプトでお届けしています。</p>
    </div>

    <div class="p-lp3">

      <!-- 安い -->
      <div class="p-lp3__card" data-reveal>
        <span class="p-lp3__label">安い</span>
        <p class="p-lp3__catch">同じ商品・同じ工事なら、<br>どこよりも安く。</p>
        <ul class="p-lp3__list">
          <li><b>仕入れ力があるので安い</b>ホームセンターを多店舗経営し、全店の商品をまとめて交渉しています。さらに、全国100社以上で共同仕入れを行う「ファストリフォーム」グループにも参加しています。</li>
          <li><b>他の事業との協力体制で人件費が安い</b>工事の職人は、工事の仕事がないときはホームセンターや燃料事業の仕事をしています。人件費をリフォーム事業だけでまかなう必要がないぶん、工事価格を安く抑えられます。</li>
          <li><b>中間マージンをカットして安い</b>協力会社にお願いするときも丸投げにはせず、木工事なら大工、設備なら設備職人と、工事の種類ごとに直接ご依頼しています。</li>
        </ul>
      </div>

      <!-- 早い -->
      <div class="p-lp3__card" data-reveal data-reveal-delay="80">
        <span class="p-lp3__label">早い</span>
        <p class="p-lp3__catch">お電話をいただいてから、<br>最短で当日にお伺いします。</p>
        <ul class="p-lp3__list">
          <li><b>店舗の近くに限っているので、対応が早い</b>お伺いするエリアを、ヤマキシの店舗から30分でかけつけられる市町村に限っています。だから、いざというときも素早く動けます。</li>
          <li><b>工事費込みの価格なので、お見積りが早い</b>商品価格を標準工事費込みにしています。現地で標準工事以外の工事を確認し、それを足すだけでお見積りができます。</li>
          <li><b>厳格な工程管理で、施工が早い</b>職人の無駄な動きを省き、事前の打ち合わせで工程を組みます。ご家族やご近所の方の工事中のストレスを短くします。</li>
        </ul>
      </div>

      <!-- 安心 -->
      <div class="p-lp3__card" data-reveal data-reveal-delay="160">
        <span class="p-lp3__label">安心</span>
        <p class="p-lp3__catch">金額が分かりにくいからこそ、<br>はっきりお見せします。</p>
        <ul class="p-lp3__list">
          <li><b>標準工事込みで安心</b>標準工事費込みの価格をご提示し、現場を確認したあとに標準外の工事を足すだけ。金額が明快です。追加の工事が必要なときは、着工前にかならずお見積りをお出しします。</li>
          <li><b>トラブル対応24で安心</b>年中無休・24時間トラブルに対応します。深夜に給湯器が故障して「お湯が出ない！」というときも、当社の従業員が対応します。</li>
          <li><b>工事保証が付いて安心</b>すべてのリフォーム会社が工事保証を付けているわけではありません。ヤマキシは自分たちの仕事と品質に責任を持ち、工事部分まで含めた独自の保証をお付けしています。</li>
        </ul>
      </div>

    </div>

    <figure class="p-lpphoto p-lpphoto--narrow" data-reveal>
      <picture>
        <source srcset="<?php echo $asset; ?>/assets/img/about/shiire.webp" type="image/webp">
        <img src="<?php echo $asset; ?>/assets/img/about/shiire.jpg" width="1600" height="1067" alt="ヤマキシの倉庫で、フォークリフトを使って商品を動かしているところ" loading="lazy" decoding="async">
      </picture>
      <figcaption>石川・福井の11店舗ぶんをまとめて仕入れ、自社の倉庫で在庫を持っています。</figcaption>
    </figure>

    <div class="p-lpprice" data-reveal>
      <p class="p-lpprice__ttl">ヤマキシの「パック価格」のしくみ</p>
      <div class="p-lpprice__calc">
        <span class="p-lpprice__item">商品代</span>
        <span class="p-lpprice__op" aria-hidden="true">＋</span>
        <span class="p-lpprice__item">標準工事費</span>
        <span class="p-lpprice__op" aria-hidden="true">＝</span>
        <span class="p-lpprice__item p-lpprice__item--sum">パック価格<small>（税込）</small></span>
      </div>
      <p class="p-lpprice__note">
        ホームページに出ている価格が、そのまま<b>工事費込み</b>の価格です。<br>
        現地を見て、標準工事に入らない工事が必要なときは、
        <b>着工前にかならずお見積りをお出しし、ご了承をいただいてから</b>進めます。
      </p>
    </div>

    <p class="p-lp3__extra" style="max-width:900px;margin-inline:auto">
      さらに、<b>水まわりのパック商品</b>をお選びいただいた方には、
      製品の<b>10年延長保証</b>を無料でお付けしています。
      ※水まわりパック以外の商品の10年保証は有償になります。
    </p>

    <p class="p-lpnext">
      <a class="p-lpnext__link" href="<?php echo esc_url( home_url( '/products/' ) ); ?>">
        <span class="p-lpnext__ttl">工事費込みのパック価格を見る</span>
        <span class="p-lpnext__txt">キッチン・お風呂・トイレ・洗面化粧台の価格を、すべて出しています</span>
      </a>
    </p>
  </div>
</section>

<!-- =========== 営業・施工体制 =========== -->
<section class="l-section" id="system">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">SYSTEM</span>
      <img class="p-lpchara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-plan.webp"
           width="640" height="595" alt="" loading="lazy" decoding="async">
      <h2 class="c-head__title">営業担当が、<span class="marker">現場も管理</span>します</h2>
      <p class="c-head__lead">「安い・早い・安心」を支えているのは、この体制です。</p>
    </div>

    <div class="p-lpsys__lead" data-reveal>
      <p>
        お打ち合わせのときにうかがったご要望を、<b>営業担当が職人に直接お伝えします</b>。
        あいだに人をはさまないので、「伝えたイメージと違う」「依頼した内容と違う」といった
        ズレを、前もって防ぐことができます。
      </p>
      <p>
        営業担当者が現場を確認しながら工事を進めますので、工事内容へのご質問、施工とちゅうで
        不安なこと、追加の工事などについても、素早く対応いたします。
      </p>
      <p>
        営業担当者が現場管理責任者を兼ねているため、<b>現場管理責任者を別に採用する費用が
        かかりません</b>。そのぶんを、工事の価格におさえて還元しています。
      </p>
    </div>

    <figure class="p-lpphoto p-lpphoto--narrow" data-reveal>
      <picture>
        <source srcset="<?php echo $asset; ?>/assets/img/about/ikeda.webp" type="image/webp">
        <img src="<?php echo $asset; ?>/assets/img/about/ikeda.jpg" width="1600" height="1067" alt="リフォームヤマキシ金沢野々市店の営業担当が、外壁材のサンプルを見せながら説明しているところ" loading="lazy" decoding="async">
      </picture>
      <figcaption>お打ち合わせから現場の管理まで、同じ営業担当がお付き合いします。</figcaption>
    </figure>

    <h3 class="p-lpsys__h3" data-reveal>分離発注システム</h3>
    <p class="p-lpsys__note" data-reveal>
      品質の良いリフォームを少しでも安く実現するために、ヤマキシは「分離発注システム」を
      採用しています。工事を丸ごと1社にお願いするのではなく、大工・電気・給排水・組み立て・
      内装と、<b>工事の種類ごとに職人へ直接ご依頼</b>するやりかたです。
    </p>

    <div class="p-lpsys">

      <!-- ヤマキシの場合 -->
      <div class="p-lpsys__card p-lpsys__card--ymk" data-reveal>
        <p class="p-lpsys__ttl">ヤマキシの場合</p>
        <ol class="p-lpsys__flow">
          <li class="p-lpsys__step">お客様</li>
          <li class="p-lpsys__step p-lpsys__step--me">ヤマキシ<small>営業担当 ＝ 現場管理</small></li>
          <li class="p-lpsys__step">大工／電気工事／給排水<br>組み立て／内装</li>
        </ol>
        <p class="p-lpsys__res p-lpsys__res--good">
          ご要望が正確に職人へ伝わり、<b>中間マージンのカットでコストダウン</b>。
        </p>
      </div>

      <!-- 大手リフォーム会社等の場合 -->
      <div class="p-lpsys__card" data-reveal data-reveal-delay="90">
        <p class="p-lpsys__ttl">大手リフォーム会社などの場合</p>
        <ol class="p-lpsys__flow">
          <li class="p-lpsys__step">お客様</li>
          <li class="p-lpsys__step">大手リフォーム会社等</li>
          <li class="p-lpsys__step p-lpsys__step--sub">下請け工務店</li>
          <li class="p-lpsys__step">大工／電気工事／給排水<br>組み立て／内装</li>
        </ol>
        <p class="p-lpsys__res">
          現場管理を下請け工務店にまかせる工事が多いため、ご要望が職人に伝わりにくく
          なります。いわゆる<b>「丸投げ」</b>は、下請け工務店の管理経費が必要なぶん
          <b>コスト高</b>になります。
        </p>
      </div>

    </div>

    <p class="p-lpnext">
      <a class="p-lpnext__link" href="<?php echo esc_url( home_url( '/staff/' ) ); ?>">
        <span class="p-lpnext__ttl">この体制で動いている、スタッフを見る</span>
        <span class="p-lpnext__txt">石川県・福井県の各店の営業担当を、顔写真つきでご紹介しています</span>
      </a>
    </p>
  </div>
</section>

<!-- =========== 得意な工事 =========== -->
<section class="l-section">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">FIELD</span>
      <img class="p-lpchara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-bow.webp"
           width="517" height="583" alt="" loading="lazy" decoding="async">
      <h2 class="c-head__title">正直に、<span class="marker">得意な工事</span>をお伝えします</h2>
      <p class="c-head__lead">リフォームは会社によって得意分野が違います。</p>
    </div>

    <div class="p-lpfield">
      <div class="p-lpfield__card p-lpfield__card--on" data-reveal>
        <span class="p-lpfield__tag">得意です</span>
        <h3>リペア</h3>
        <p>雨漏り・水漏れなどの修理が中心の工事。緊急のトラブルもすぐに駆けつけます。</p>
      </div>
      <div class="p-lpfield__card p-lpfield__card--on" data-reveal data-reveal-delay="80">
        <span class="p-lpfield__tag">得意です</span>
        <h3>リフレッシュ</h3>
        <p>キッチン・お風呂などの設備の入れ替え、内装や間取りの変更。ヤマキシの中心です。</p>
      </div>
      <div class="p-lpfield__card" data-reveal data-reveal-delay="160">
        <span class="p-lpfield__tag">ご相談ください</span>
        <h3>リモデル</h3>
        <p>増改築・大規模な改修。実績はありますが、高いデザイン性を求められる工事は不得意です。</p>
      </div>
    </div>


    <p class="p-lppros__ttl" data-reveal>工事をするのは、ヤマキシの社員です</p>
    <ul class="p-lppros" data-reveal>
      <li>
        <picture>
          <source srcset="<?php echo $asset; ?>/assets/img/about/pro1.webp" type="image/webp">
          <img src="<?php echo $asset; ?>/assets/img/about/pro1.jpg" width="300" height="300" alt="大工が丸ノコで材料を切っているところ" loading="lazy" decoding="async">
        </picture>
      </li>
      <li>
        <picture>
          <source srcset="<?php echo $asset; ?>/assets/img/about/pro2.webp" type="image/webp">
          <img src="<?php echo $asset; ?>/assets/img/about/pro2.jpg" width="300" height="300" alt="職人の腰道具（工具ベルト）" loading="lazy" decoding="async">
        </picture>
      </li>
      <li>
        <picture>
          <source srcset="<?php echo $asset; ?>/assets/img/about/pro3.webp" type="image/webp">
          <img src="<?php echo $asset; ?>/assets/img/about/pro3.jpg" width="300" height="300" alt="丸ノコで下地材を切っている手元" loading="lazy" decoding="async">
        </picture>
      </li>
      <li>
        <picture>
          <source srcset="<?php echo $asset; ?>/assets/img/about/pro4.webp" type="image/webp">
          <img src="<?php echo $asset; ?>/assets/img/about/pro4.jpg" width="300" height="300" alt="ハンマーとタガネで既存のタイルをはがしているところ" loading="lazy" decoding="async">
        </picture>
      </li>
      <li>
        <picture>
          <source srcset="<?php echo $asset; ?>/assets/img/about/pro5.webp" type="image/webp">
          <img src="<?php echo $asset; ?>/assets/img/about/pro5.jpg" width="300" height="300" alt="給水管まわりの解体をしているところ" loading="lazy" decoding="async">
        </picture>
      </li>
      <li>
        <picture>
          <source srcset="<?php echo $asset; ?>/assets/img/about/pro6.webp" type="image/webp">
          <img src="<?php echo $asset; ?>/assets/img/about/pro6.jpg" width="300" height="300" alt="壁の下地を確かめているところ" loading="lazy" decoding="async">
        </picture>
      </li>
    </ul>

    <p class="p-lpfield__note">
      ヤマキシには <b>2級建築士・大工・設備・電気・内装の職人が社員として在籍</b>しており、
      水まわりを中心とした「リフレッシュ」と「リペア」を得意としています。<br>
      いっぽうで、インテリアデザイナーや内装コーディネーターは在籍していません。
      デザイン担当を専属で採用したり外部にお願いすると、どうしても価格が高くなり、
      <b>「安い・早い・安心」というヤマキシの考え方に合わなくなってしまう</b>からです。
      そこは正直にお伝えしたうえで、できることを精一杯やらせていただきます。
    </p>

    <p class="p-lpnext">
      <a class="p-lpnext__link" href="<?php echo esc_url( home_url( '/works/' ) ); ?>">
        <span class="p-lpnext__ttl">実際の工事を、施工事例で見る</span>
        <span class="p-lpnext__txt">Before・Afterの写真、工事費、工期、担当した営業までのせています</span>
      </a>
    </p>
  </div>
</section>

<!-- =========== お悩みの例 =========== -->
<section class="l-section l-section--soft">
  <div class="l-wrap">
    <div class="c-head">
      <img class="p-lpchara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-wrench.webp"
           width="503" height="640" alt="" loading="lazy" decoding="async">
      <h2 class="c-head__title">こんなことでも、<span class="marker">どうぞ</span></h2>
      <p class="c-head__lead">「これくらいで呼んでいいのかな」と思うことこそ、お聞かせください。</p>
    </div>
    <ul class="p-lpworry">
      <li>お風呂が寒い</li>
      <li>床がきしむ</li>
      <li>給湯器が壊れた</li>
      <li>家が古くなってきた</li>
      <li>雨漏りを見てほしい</li>
      <li>シロアリが心配</li>
      <li>外壁が色あせてきた</li>
      <li>掃除がしにくい</li>
      <li>段差につまずく</li>
      <li>いくらかかるか知りたい</li>
    </ul>

    <figure class="p-lpphoto p-lpphoto--narrow" data-reveal>
      <picture>
        <source srcset="<?php echo $asset; ?>/assets/img/common/hero-03.webp" type="image/webp">
        <img src="<?php echo $asset; ?>/assets/img/common/hero-03.jpg" width="1560" height="1170"
             alt="ヤマキシの店舗で、キッチンの展示の前に立つスタッフ"
             loading="lazy" decoding="async">
      </picture>
      <figcaption>お近くのヤマキシで、お気軽にお声がけください。小さな工事も断りません。</figcaption>
    </figure>
  </div>
</section>

<!-- =========== 6つのこだわり =========== -->
<section class="l-section">
  <div class="l-wrap">
    <div class="c-head">
      <img class="p-lpchara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-hinshitsu.webp"
           width="592" height="640" alt="" loading="lazy" decoding="async">
      <span class="c-head__en">COMMITMENT</span>
      <h2 class="c-head__title">6つに<span class="marker">こだわっています</span></h2>
      <p class="c-head__lead">良いものを安く。そして早く。</p>
    </div>
    <div class="p-lpcommit">
      <article>
        <span class="p-lpcommit__no">1</span>
        <h3>スピード対応</h3>
        <p>お問い合わせから最短当日にご対応。お客様のご都合に合わせ、最短見積・最短工事にこだわります。</p>
      </article>
      <article>
        <span class="p-lpcommit__no">2</span>
        <h3>安心価格</h3>
        <p>工事コミコミの分かりやすい価格で安心をお届け。仕入れや工事の徹底管理で安心価格にこだわります。</p>
      </article>
      <article>
        <span class="p-lpcommit__no">3</span>
        <h3>工事保証</h3>
        <p>リフォーム業界ではまだまだ珍しい独自の「工事保証書」を発行しております。工事後の保証にこだわります。</p>
      </article>
      <article>
        <span class="p-lpcommit__no">4</span>
        <h3>満足保証</h3>
        <p>打ち合わせの内容と仕上がりが違う場合には、工事の途中でも無償でやり直します。ご満足いただけるまでお付き合いします。</p>
      </article>
      <article>
        <span class="p-lpcommit__no">5</span>
        <h3>サービス向上</h3>
        <p>年間500件以上のアンケートをいただいています。お客様の生の声を、サービスの改善に活かしています。</p>
      </article>
      <article>
        <span class="p-lpcommit__no">6</span>
        <h3>アフターサービス</h3>
        <p>工事後の不具合やトラブルが起きた際は、迅速な対応をいたします。年中無休・24時間のトラブル対応も。</p>
      </article>
    </div>
  </div>
</section>

<!-- =========== 工期の目安 =========== -->
<section class="l-section l-section--soft" id="speed">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">SPEED</span>
      <img class="p-lpchara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-run.webp"
           width="514" height="617" alt="" loading="lazy" decoding="async">
      <h2 class="c-head__title">住みながらの工事だから、<br class="sp-only"><span class="marker">早さ</span>にこだわります</h2>
      <p class="c-head__lead">騒音や職人の出入りは、どうしてもご家族のストレスになります。</p>
    </div>
    <ul class="p-lpspeed">
      <li><span class="p-lpspeed__name">キッチン</span><span class="p-lpspeed__day">最短<b>2</b>日〜</span></li>
      <li><span class="p-lpspeed__name">お風呂</span><span class="p-lpspeed__day">最短<b>5</b>日〜</span></li>
      <li><span class="p-lpspeed__name">トイレ</span><span class="p-lpspeed__day">最短<b>半日</b>〜</span></li>
      <li><span class="p-lpspeed__name">洗面台</span><span class="p-lpspeed__day">最短<b>半日</b>〜</span></li>
    </ul>

    <p class="p-lpfin__ttl" data-reveal>この日数で、ここまで変わります</p>
    <ul class="p-lpfin" data-reveal>
      <li>
        <picture>
          <source srcset="<?php echo $asset; ?>/assets/img/works/works-bath-01.webp" type="image/webp">
          <img src="<?php echo $asset; ?>/assets/img/works/works-bath-01.jpg" width="900" height="600" alt="リフォームしたあとのお風呂" loading="lazy" decoding="async">
        </picture>
        <span>お風呂</span>
      </li>
      <li>
        <picture>
          <source srcset="<?php echo $asset; ?>/assets/img/works/works-toilet-01.webp" type="image/webp">
          <img src="<?php echo $asset; ?>/assets/img/works/works-toilet-01.jpg" width="900" height="600" alt="リフォームしたあとのトイレ" loading="lazy" decoding="async">
        </picture>
        <span>トイレ</span>
      </li>
      <li>
        <picture>
          <source srcset="<?php echo $asset; ?>/assets/img/about/fin-lavatory.webp" type="image/webp">
          <img src="<?php echo $asset; ?>/assets/img/about/fin-lavatory.jpg" width="900" height="600" alt="リフォームしたあとの洗面化粧台" loading="lazy" decoding="async">
        </picture>
        <span>洗面化粧台</span>
      </li>
    </ul>

    <p class="p-lpnote">※現状の状態により変わります。正確な工期は現地調査でお伝えします。<br>※仕上がりの写真はイメージです。</p>
  </div>
</section>

<!-- =========== 12の約束 =========== -->
<section class="l-section">
  <div class="l-wrap">
    <div class="c-head">
      <img class="p-lpchara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-hammer.webp"
           width="568" height="640" alt="" loading="lazy" decoding="async">
      <span class="c-head__en">PROMISE</span>
      <h2 class="c-head__title">現場での<span class="marker">12の約束</span></h2>
      <p class="c-head__lead">工事が始まってからの不安を、なくすために。</p>
    </div>

    <div class="p-lppromise">
      <div class="p-lppromise__group">
        <h3>作業前の4つの約束</h3>
        <ul>
          <li><b>作業前のご挨拶</b>当日作業前に、その日の工事内容をお客様に報告してから、作業を実施します。</li>
          <li><b>近隣へのご挨拶</b>騒音等でご迷惑をおかけするお隣のお宅には、予め担当者がご挨拶をします。</li>
          <li><b>工事変更のご連絡</b>工事の途中で変更や追加工事が発生する場合は、必ず事前にご連絡します。</li>
          <li><b>工事前の段取り</b>予定している工事がその時間までに終わるよう、しっかりと段取りを行います。</li>
        </ul>
      </div>
      <div class="p-lppromise__group">
        <h3>作業中の4つの約束</h3>
        <ul>
          <li><b>整理整頓</b>必要部材はお客様のご迷惑にならないように搬入し、整理整頓しながら作業を進めます。</li>
          <li><b>タバコ禁止</b>休憩・車の中以外での喫煙は絶対にしません。マナーを守って吸います。</li>
          <li><b>工具の安全確認</b>特に電動工具は、漏電や誤作動を起こさないよう安全管理に努めます。</li>
          <li><b>確認はしっかりと</b>水道・電気をお借りする時、埃が立ちそうな時などは、お客様にご確認をします。</li>
        </ul>
      </div>
      <div class="p-lppromise__group">
        <h3>作業後の4つの約束</h3>
        <ul>
          <li><b>清掃の徹底</b>休憩前、作業終了後には必ず清掃をします。</li>
          <li><b>工事後の片付け</b>材料・工具置き場は、工事後にきっちり清掃・整理します。</li>
          <li><b>作業後のご挨拶</b>本日の作業報告・明日の段取りをお伝えします。気になった点があれば、お聞かせください。</li>
          <li><b>最後の安全確認</b>最後には安全確認をきっちり行い、確認できてから現場を離れます。</li>
        </ul>
      </div>
    </div>

    <p class="p-lpnext">
      <a class="p-lpnext__link" href="<?php echo esc_url( home_url( '/voice/' ) ); ?>">
        <span class="p-lpnext__ttl">お客様がどう感じたか、声を見る</span>
        <span class="p-lpnext__txt">アンケート「仕事の通信簿」を、いただいたまま載せています</span>
      </a>
    </p>
  </div>
</section>

<!-- =========== ダブル保証 =========== -->
<section class="l-section l-section--soft">
  <div class="l-wrap l-wrap--narrow">
    <div class="c-head">
      <img class="p-lpchara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-anshin.webp"
           width="530" height="640" alt="" loading="lazy" decoding="async">
      <span class="c-head__en">WARRANTY</span>
      <h2 class="c-head__title"><span class="marker">ダブル</span>で安心保証します</h2>
      <p class="c-head__lead">リフォームには、工事に対する保証の法的な義務づけがありません。</p>
    </div>
    <div class="p-lpwarranty">
      <div class="p-lpwarranty__card">
        <span class="p-lpwarranty__tag">商品</span>
        <h3>10年延長保証</h3>
        <p>メーカーの商品保証（通常1〜2年程度）が切れた後も、設置から10年間保証します。</p>
        <small>※水まわりパック商品のみ無料。それ以外は有償になります。</small>
      </div>
      <span class="p-lpwarranty__plus" aria-hidden="true">＋</span>
      <div class="p-lpwarranty__card">
        <span class="p-lpwarranty__tag">工事</span>
        <h3>独自の工事保証書</h3>
        <p>大工・設備・電気・塗装などの「工事部分」に対して、ヤマキシ独自の保証書を発行しています。</p>
        <small>※工事保証の期間は5年間です。</small>
      </div>
    </div>

    <p class="p-lpnext">
      <a class="p-lpnext__link" href="<?php echo esc_url( home_url( '/warranty/' ) ); ?>">
        <span class="p-lpnext__ttl">保証について、くわしく見る</span>
        <span class="p-lpnext__txt">保証書の見本、延長保証の期間と金額の考え方までのせています</span>
      </a>
    </p>
  </div>
</section>

<!-- =========== 関連ページ =========== -->
<section class="l-section">
  <div class="l-wrap l-wrap--narrow">
    <h2 class="p-lprel__h2">あわせてご覧ください</h2>
    <ul class="p-lprel">
      <li><a href="<?php echo esc_url( home_url( '/products/' ) ); ?>">商品・価格</a><span>工事費込みのパック価格</span></li>
      <li><a href="<?php echo esc_url( home_url( '/works/' ) ); ?>">施工事例</a><span>Before・Afterと、かかった費用</span></li>
      <li><a href="<?php echo esc_url( home_url( '/voice/' ) ); ?>">お客様の声</a><span>アンケート「仕事の通信簿」</span></li>
      <li><a href="<?php echo esc_url( home_url( '/area/' ) ); ?>">店舗・エリア</a><span>石川県・福井県に11店舗</span></li>
      <li><a href="<?php echo esc_url( home_url( '/staff/' ) ); ?>">スタッフ紹介</a><span>担当する営業の顔ぶれ</span></li>
      <li><a href="<?php echo esc_url( home_url( '/message/' ) ); ?>">代表挨拶</a><span>「水まわり市場」を始めた理由</span></li>
    </ul>
  </div>
</section>

<!-- =========== CTA =========== -->
<section class="l-section">
  <div class="l-wrap l-wrap--narrow">
    <div class="p-lpcta">
      <img class="p-lpcta__chara" src="<?php echo $asset; ?>/assets/img/character/char-stand.webp" width="503" height="640"
           alt="" loading="lazy">
      <h2 class="p-lpcta__title">まずは、<span class="marker">お困りごと</span>から</h2>
      <p class="p-lpcta__text">
        見積り・現地調査は無料です。しつこい営業は一切いたしません。<br>
        「いくらかかるか知りたいだけ」でも歓迎です。
      </p>
      <div class="p-lpcta__btns">
        <a class="c-btn c-btn--line c-btn--block" href="https://lin.ee/UJZuSTrz" rel="noopener" data-cta="about-cta">
          <span class="c-btn__label">LINEで相談する<span class="c-btn__sub">写真を送るだけでもOK・24時間受付</span></span>
        </a>
        <a class="c-btn c-btn--block" href="<?php echo esc_url( home_url( '/inquiry/webrsv/' ) ); ?>" data-cta="about-cta">
          <span class="c-btn__label">ショールーム来店予約<span class="c-btn__sub">初回特典500円ヤマキシお買物券<br>※展示のない店舗もあります</span></span>
        </a>
        <a class="c-btn c-btn--ghost c-btn--block" href="tel:0800-777-3331" data-cta="about-cta">
          <span class="c-btn__label">0800-777-3331<span class="c-btn__sub">通話無料・受付 9:00〜17:00</span></span>
        </a>
      </div>
    </div>
  </div>
</section>

</main>

<?php get_footer();
