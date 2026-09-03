<?php
/**
 * ymkrf-message.php ─ 代表挨拶（/message/）
 *
 * 置き場所： wp-content/themes/ymkrf/ymkrf-message.php
 *
 * もとの資料：ユーザーからいただいたPDF
 *   「ヤマキシの水まわり市場について代表取締役挨拶」（本番サイト /message/ を印刷したもの）
 * 写真：assets/img/company/message-hero.jpg（いただいた白黒写真を16:9に切ったもの）
 *
 * ★内容を直すとき
 *   文章はすべてこのファイルの中に書いてあります。
 *   見た目は assets/css/company.css の .p-msg〜 で調整します。
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
    <li>代表挨拶</li>
  </ol>
</nav>

<main id="main">

<div class="p-pagehead">
  <div class="l-wrap p-pagehead__inner">
    <span class="p-pagehead__en">MESSAGE</span>
    <h1 class="p-pagehead__title">代表挨拶</h1>
    <p class="p-pagehead__lead">
      ホームセンターで物を買うように、<br class="xs-only">もっと手軽にリフォームを。
    </p>
  </div>
</div>

<!-- =========== 写真 =========== -->
<div class="p-msghero" data-reveal>
  <div class="l-wrap">
    <figure class="p-msghero__fig">
      <picture>
        <source srcset="<?php echo $asset; ?>/assets/img/company/message-hero.webp" type="image/webp">
        <img src="<?php echo $asset; ?>/assets/img/company/message-hero.jpg"
             width="2000" height="1125" alt="株式会社山岸 代表取締役 山岸信治"
             fetchpriority="high" decoding="async">
      </picture>
      <figcaption class="p-msghero__cap">
        <span class="p-msghero__role">株式会社山岸　代表取締役</span>
        <span class="p-msghero__name">山岸 信治</span>
      </figcaption>
    </figure>
  </div>
</div>

<!-- =========== 本文 =========== -->
<section class="l-section">
  <div class="l-wrap l-wrap--narrow">

    <div class="p-msg" data-reveal>

      <p class="p-msg__lead">
        皆さま、こんにちは。<br>
        株式会社山岸 代表取締役の山岸信治です。
      </p>

      <h2 class="p-msg__h2">小さな修理の現場で、聞こえてきた声</h2>

      <p>
        ヤマキシはホームセンター事業とあわせて、約50年前からガス・灯油といった燃料販売を
        行っており、燃料の販売を通じて、給湯器や水まわりの工事や修理を行ってきました。
      </p>
      <p>
        水まわりの小さな修理を行う中で、<b>「リフォームしたいけれど、金額が高そうなので、
        修理しながら古い設備を使っている」</b>という声を、たびたび聞くことがありました。
      </p>
      <p>
        私たちは、ホームセンターで物を買うように、もっと手軽にリフォームをしてもらいたい
        との想いから、「水まわり市場」としてトイレ・キッチン・お風呂などを中心とした
        水まわりリフォームの専門店を、ホームセンター内で開始しました。
      </p>

      <h2 class="p-msg__h2">価格の分かりにくさを、なくすところから</h2>

      <p>
        まず事業を始めるにあたり、工事の価格が分かりにくいことがリフォームへの敷居を
        高くしているように思いましたので、トイレ・キッチン・ユニットバス・洗面化粧台などは
        <b>工事費込みのパック商品</b>にしました。
      </p>
      <p>
        また、少しでも高品質なリフォームをより安く提供できるように、仕入れを工夫しています。
        たとえば、石川・福井で運営する11店舗で一括仕入れを行ったり、全国100社以上で
        共同仕入れを行う「ファストリフォーム」グループへ参加することも、コストダウンをして
        安く販売するための工夫のひとつです。
      </p>
      <p>
        工事に関しても、基本は<b>自社の社員である職人が責任を持って施工</b>を行い、工事の
        品質アップとコストダウンを実現しています。外部の施工業者にお願いする場合も工事を
        丸投げにせず、職人ひとりひとりと契約を行うことで、管理費などの中間コストが
        かからないよう工夫しています。
      </p>

      <h2 class="p-msg__h2">リフォームには、人生を豊かにする力がある</h2>

      <p>
        リフォームで「家事が楽になった」「家族のだんらんが増えた」という喜びの声を聞くたびに、
        リフォームには人生を豊かにしてくれる力があると感じています。
      </p>
      <p>
        そんなリフォームを少しでも気楽に行ってもらえるよう、私たちヤマキシは今後も
        工夫と努力を行ってまいります。
      </p>

      <p class="p-msg__sign">
        <span class="p-msg__signco">株式会社山岸</span>
        <span class="p-msg__signrole">代表取締役</span>
        <span class="p-msg__signname">山岸 信治</span>
      </p>

    </div>

    <div class="p-msg__links" data-reveal>
      <a class="p-msg__link" href="<?php echo esc_url( home_url( '/about/' ) ); ?>">
        <span class="p-msg__linkttl">ヤマキシのこだわり・特徴</span>
        <span class="p-msg__linktxt">安い・早い・安心の3つのコンセプトと、営業・施工体制について</span>
      </a>
      <a class="p-msg__link" href="<?php echo esc_url( home_url( '/company/' ) ); ?>">
        <span class="p-msg__linkttl">会社概要</span>
        <span class="p-msg__linktxt">株式会社山岸の会社案内・沿革</span>
      </a>
    </div>

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
        「いくらかかるか知りたいだけ」でも歓迎です。
      </p>
      <div class="p-lpcta__btns">
        <a class="c-btn c-btn--line c-btn--block" href="https://lin.ee/UJZuSTrz" rel="noopener" data-cta="message-cta">
          <span class="c-btn__label">LINEで相談する<span class="c-btn__sub">写真を送るだけでもOK・24時間受付</span></span>
        </a>
        <a class="c-btn c-btn--block" href="<?php echo esc_url( home_url( '/inquiry/webrsv/' ) ); ?>" data-cta="message-cta">
          <span class="c-btn__label">ショールーム来店予約<span class="c-btn__sub">初回特典500円ヤマキシお買物券<br>※展示のない店舗もあります</span></span>
        </a>
        <a class="c-btn c-btn--ghost c-btn--block" href="tel:0800-777-3331" data-cta="message-cta">
          <span class="c-btn__label">0800-777-3331<span class="c-btn__sub">通話無料・受付 9:00〜17:00</span></span>
        </a>
      </div>
    </div>
  </div>
</section>

</main>

<?php get_footer();
