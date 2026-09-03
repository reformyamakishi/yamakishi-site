<?php
/**
 * ymkrf-warranty.php ─ 保証について（/warranty/）
 *
 * 置き場所： wp-content/themes/ymkrf/ymkrf-warranty.php
 *
 * もとの資料：ユーザーからいただいたPDF
 *   「ヤマキシの水まわり市場の保証について」（本番サイト /warranty/ を印刷したもの）
 * 保証書の写真は、そのPDFに埋め込まれていたものを取り出しました。
 *
 * ★内容を直すとき
 *   文章はすべてこのファイルの中に書いてあります。
 *   見た目は assets/css/warranty.css の .p-war〜 で調整します。
 *
 * ※「追加請求はありません」といった言い切りは書かないでください。
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$asset = get_stylesheet_directory_uri();
get_header();
?>

<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li>保証について</li>
  </ol>
</nav>

<main id="main">

<div class="p-pagehead">
  <div class="l-wrap p-pagehead__inner">
    <img class="p-pagehead__chara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-anshin.webp"
         width="503" height="640" alt="" loading="lazy" decoding="async">
    <span class="p-pagehead__en">WARRANTY</span>
    <h1 class="p-pagehead__title">保証について</h1>
    <p class="p-pagehead__lead">
      ヤマキシは<b>商品と工事の両方</b>を保証します。<br class="xs-only">
      工事保証は5年、商品は最長10年です。
    </p>
  </div>
</div>

<!-- =========== 保証書には2通りある =========== -->
<section class="l-section">
  <div class="l-wrap l-wrap--narrow">
    <div class="c-head">
      <h2 class="c-head__title">保証書に<span class="marker">2通りある</span>のを<br class="sp-only">ご存じですか？</h2>
    </div>

    <p class="p-war__lead" data-reveal>
      リフォーム工事の保証には、じつは2種類あります。<br>
      住宅設備・屋根材・外壁塗料などの<b>メーカーが「製品」に対して出す保証</b>と、
      リフォーム会社が<b>「工事した部分」に対して自社で出す保証</b>です。
    </p>

    <div class="p-war2" data-reveal>
      <div class="p-war2__card">
        <span class="p-war2__tag">1</span>
        <h3>メーカー保証</h3>
        <p class="p-war2__who">出すのは<b>メーカー</b></p>
        <p>キッチンや給湯器といった<b>製品そのもの</b>の保証です。期間は<b>ふつう1〜2年</b>ほど。</p>
      </div>
      <div class="p-war2__card p-war2__card--on">
        <span class="p-war2__tag">2</span>
        <h3>工事保証</h3>
        <p class="p-war2__who">出すのは<b>リフォーム会社</b></p>
        <p>配管のつなぎ方、取り付け方など、<b>工事した部分</b>の保証です。会社が自分で発行します。</p>
      </div>
    </div>

    <div class="p-war__caution" data-reveal>
      <p class="p-war__caution-ttl">ここを確かめてください</p>
      <p>
        保証を<b>「メーカー保証」だけ</b>にしているリフォーム会社も多くあります。
        どこにお願いするか迷われたときは、<b>その会社が自分の工事に保証を付けているか</b>を
        聞いてみてください。
      </p>
    </div>
  </div>
</section>

<!-- =========== Ⅰ 工事保証 =========== -->
<section class="l-section l-section--soft">
  <div class="l-wrap l-wrap--narrow">
    <div class="c-head">
      <span class="c-head__en">WARRANTY 1</span>
      <h2 class="c-head__title">ヤマキシの<span class="marker">工事保証</span></h2>
      <p class="c-head__lead">期間は5年間。ヤマキシが自分で発行します。</p>
    </div>

    <div class="p-warbox" data-reveal>
      <div class="p-warbox__txt">
        <p class="p-warbox__num"><b>5</b><span>年間</span></p>
        <p>
          ヤマキシは、自分たちの仕事内容と品質に誇りと責任を持っています。
          そのため、メーカー保証とあわせて、<b>工事した部分まで含めた独自の
          「リフォーム工事保証書」</b>をお渡ししています。
        </p>
        <p class="p-warbox__sub">
          大工・設備・電気・塗装など、ヤマキシが手をかけた部分が対象です。
        </p>
      </div>
      <figure class="p-warbox__fig">
        <picture>
          <source srcset="<?php echo $asset; ?>/assets/img/warranty/koji-hosho.webp" type="image/webp">
          <img src="<?php echo $asset; ?>/assets/img/warranty/koji-hosho.jpg"
               width="561" height="779" alt="ヤマキシのリフォーム工事保証書の見本"
               loading="lazy" decoding="async">
        </picture>
        <figcaption>リフォーム工事保証書（見本）</figcaption>
      </figure>
    </div>
  </div>
</section>

<!-- =========== Ⅱ メーカー延長保証 =========== -->
<section class="l-section">
  <div class="l-wrap l-wrap--narrow">
    <div class="c-head">
      <span class="c-head__en">WARRANTY 2</span>
      <h2 class="c-head__title">メーカー<span class="marker">延長保証</span></h2>
      <p class="c-head__lead">1〜2年で終わるメーカー保証を、設置から最長10年まで延ばします。</p>
    </div>

    <div class="p-warbox p-warbox--rev" data-reveal>
      <div class="p-warbox__txt">
        <p class="p-warbox__num"><b>10</b><span>年まで</span></p>
        <p>
          メーカー保証はふつう1〜2年ほどで終わってしまいます。
          ヤマキシでは、そのあとも<b>設置から最長10年まで</b>保証を延ばせるよう、
          「延長保証」をご用意しています。
        </p>
        <p class="p-warbox__free">
          <b>水まわりパック商品は、10年の延長保証が無料</b>でパック価格に含まれています。
        </p>
        <p class="p-warbox__sub">
          水まわりパック以外の機器・設備も、有償で延長保証をお付けできます。
          期間は<b>5年・8年・10年</b>からお選びいただけます。
          金額は機器と期間によって変わりますので、お気軽にご相談ください。
        </p>
      </div>
      <figure class="p-warbox__fig">
        <picture>
          <source srcset="<?php echo $asset; ?>/assets/img/warranty/encho-hosho.webp" type="image/webp">
          <img src="<?php echo $asset; ?>/assets/img/warranty/encho-hosho.jpg"
               width="561" height="798" alt="ヤマキシリフォーム延長保証の保証書の見本"
               loading="lazy" decoding="async">
        </picture>
        <figcaption>リフォーム延長保証 保証書（見本）</figcaption>
      </figure>
    </div>
  </div>
</section>

<!-- =========== まとめ（ダブル保証） =========== -->
<section class="l-section l-section--soft">
  <div class="l-wrap l-wrap--narrow">
    <div class="c-head">
      <h2 class="c-head__title"><span class="marker">ダブル</span>で、お守りします</h2>
    </div>

    <div class="p-warsum" data-reveal>
      <div class="p-warsum__card">
        <span class="p-warsum__tag">商品</span>
        <p class="p-warsum__name">メーカー延長保証</p>
        <p class="p-warsum__len">最長 <b>10</b> 年</p>
        <p class="p-warsum__note">※水まわりパック商品は無料。<br>それ以外は有償になります。</p>
      </div>
      <span class="p-warsum__plus" aria-hidden="true">＋</span>
      <div class="p-warsum__card">
        <span class="p-warsum__tag">工事</span>
        <p class="p-warsum__name">リフォーム工事保証書</p>
        <p class="p-warsum__len"><b>5</b> 年間</p>
        <p class="p-warsum__note">※ヤマキシが自分で発行します。</p>
      </div>
    </div>

    <p class="p-war__after" data-reveal>
      保証の期間が過ぎたあとも、<b>年中無休・24時間</b>でトラブルに対応しています。
      お近くの店舗がそのまま窓口ですので、「どこに言えばいいか分からない」ということがありません。
    </p>
  </div>
</section>

<!-- =========== 関連ページ =========== -->
<section class="l-section">
  <div class="l-wrap l-wrap--narrow">
    <h2 class="p-lprel__h2">あわせてご覧ください</h2>
    <ul class="p-lprel">
      <li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">こだわり・特徴と施工体制</a><span>安い・早い・安心の3つのコンセプト</span></li>
      <li><a href="<?php echo esc_url( home_url( '/flow/' ) ); ?>">リフォームの流れ</a><span>ご相談からアフターサポートまで</span></li>
      <li><a href="<?php echo esc_url( home_url( '/products/' ) ); ?>">商品・価格</a><span>工事費込みのパック価格</span></li>
      <li><a href="<?php echo esc_url( home_url( '/works/' ) ); ?>">施工事例</a><span>Before・Afterと、かかった費用</span></li>
      <li><a href="<?php echo esc_url( home_url( '/voice/' ) ); ?>">お客様の声</a><span>アンケート「仕事の通信簿」</span></li>
      <li><a href="<?php echo esc_url( home_url( '/message/' ) ); ?>">代表挨拶</a><span>「水まわり市場」を始めた理由</span></li>
    </ul>
  </div>
</section>

<!-- =========== CTA =========== -->
<section class="l-section l-section--soft">
  <div class="l-wrap l-wrap--narrow">
    <div class="p-lpcta">
      <img class="p-lpcta__chara" src="<?php echo $asset; ?>/assets/img/character/char-stand.webp" width="503" height="640"
           alt="" loading="lazy" decoding="async">
      <h2 class="p-lpcta__title">まずは、<span class="marker">お困りごと</span>から</h2>
      <p class="p-lpcta__text">
        見積り・現地調査は無料です。しつこい営業は一切いたしません。<br>
        「保証はどうなるの？」というご質問だけでも歓迎です。
      </p>
      <div class="p-lpcta__btns">
        <a class="c-btn c-btn--line c-btn--block" href="https://lin.ee/UJZuSTrz" rel="noopener" data-cta="warranty-cta">
          <span class="c-btn__label">LINEで相談する<span class="c-btn__sub">写真を送るだけでもOK・24時間受付</span></span>
        </a>
        <a class="c-btn c-btn--block" href="<?php echo esc_url( home_url( '/inquiry/webrsv/' ) ); ?>" data-cta="warranty-cta">
          <span class="c-btn__label">ショールーム来店予約<span class="c-btn__sub">初回特典500円ヤマキシお買物券<br>※展示のない店舗もあります</span></span>
        </a>
        <a class="c-btn c-btn--ghost c-btn--block" href="tel:0800-777-3331" data-cta="warranty-cta">
          <span class="c-btn__label">0800-777-3331<span class="c-btn__sub">通話無料・受付 9:00〜17:00</span></span>
        </a>
      </div>
    </div>
  </div>
</section>

</main>

<?php get_footer();
