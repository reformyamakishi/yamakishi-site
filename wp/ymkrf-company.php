<?php
/**
 * ymkrf-company.php ─ 会社概要（/company/）
 *
 * 置き場所： wp-content/themes/ymkrf/ymkrf-company.php
 *
 * もとの資料：ユーザーからいただいたPDF
 *   「会社概要｜株式会社 ヤマキシ」（コーポレートサイトを印刷したもの／2026年8月）
 *
 * ★リフォーム専用サイト向けに、次のように組み替えています
 *   ・決算報告（税引前当期純利益24期分）は載せていません。
 *     リフォームをお探しの方が使う情報ではないためです。
 *     必要な方のために、コーポレートサイトへのリンクを置いています。
 *   ・「店舗」は、コーポレートサイトのホームセンター10店舗ではなく、
 *     リフォームの窓口になる店舗を出しています。
 *   ・沿革は全部載せたうえで、リフォームに関わる年に印を付けています。
 *
 * ★内容を直すとき
 *   文章はすべてこのファイルの中に書いてあります。
 *   見た目は assets/css/company.css の .p-comp〜 で調整します。
 *
 * ※「追加請求はありません」といった言い切りは書かないでください。
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$asset = get_stylesheet_directory_uri();

/* 会社概要の表 */
$rows = array(
	array( '会社名',   '株式会社山岸（ヤマキシ）' ),
	array( '代表者',   '代表取締役　山岸 信治' ),
	array( '本社',     '〒919-0628　福井県あわら市大溝1丁目8番13号' ),
	array( '電話番号', '0776-73-1015' ),
	array( '創業',     '1900年（明治33年）' ),
	array( '設立',     '1988年2月' ),
	array( '資本金',   '3,000万円' ),
	array( '年商',     '133億7,300万円（令和7年1月期）' ),
	array( '従業員数', '500名' ),
	array( '事業内容', 'リフォーム事業／ホームセンター事業／燃料事業／クリーンエネルギー事業／インターネット通販事業／移動スーパー事業' ),
	array( '関連会社', '株式会社ヤマキシ不動産' ),
);

/* 沿革。★を付けた年が、リフォームに関わる年です */
$history = array(
	array( '1900年', '先々代 山岸竹次郎が畳表製造業を創業（明治33年ごろ）', false ),
	array( '1935年', '山岸金物店を開店', false ),
	array( '1979年', 'あわら市春宮3丁目にて金物雑貨商を営む<br>'
		               . '本店を郊外型店舗としてあわら市新用に移転', false ),
	array( '1981年', '株式会社山岸を設立', false ),
	array( '1985年', 'ホームセンターヤマキシ加賀店を開店', false ),
	array( '1987年', 'ホームセンターヤマキシ小松店を開店', false ),
	array( '1991年', 'ホームセンターヤマキシ野々市店を開店', false ),
	array( '1993年', '金津店（本店）を改装・増床', false ),
	array( '1994年', 'ホームセンターヤマキシ開発店を開店', false ),
	array( '2000年', '当社初の大型店舗、ホームセンターヤマキシ田鶴浜店を開店（延床面積14,000㎡）', false ),
	array( '2003年', '大型店舗2号店、スーパーホームセンターヤマキシ朝日店を開店（延床面積18,292㎡）', false ),
	array( '2007年', 'インターネット通販事業部を創設（楽天市場店を開店）', false ),
	array( '2008年', '大型店舗3号店、スーパーホームセンターヤマキシ川北店を開店（延床面積15,526㎡）', false ),
	array( '2009年', 'クリーンエネルギー推進室を創設（太陽光発電・オール電化の販売施工を開始）', false ),
	array( '2010年', 'ネットスーパー事業部を創設', false ),
	array( '2012年', '川北店の屋根に約1,000kwのメガソーラーを建設し、売電事業を開始<br>'
	               . '<b>建設業許可を取得</b>し、産業用の大型太陽光発電の施工販売を強化', true ),
	array( '2013年', '田鶴浜店・朝日店の各屋根に1,000kwのメガソーラーを建設し、売電事業を開始<br>'
	               . '<b>リフォーム事業を開始</b>', true ),
	array( '2014年', '地上設置型メガソーラー「坪江太陽光発電所」（2,000kw）の運転を開始', false ),
	array( '2017年', '大型店舗4号店、スーパーホームセンターヤマキシ新加賀店を開店', false ),
	array( '2019年', '<b>リフォーム専門店として、リフォームヤマキシ野々市店を開店</b>', true ),
	array( '2020年', '移動スーパー事業部を創設', false ),
	array( '2022年', '<b>リフォーム専門店2号店として、リフォームヤマキシ小松店を開店</b>', true ),
	array( '2023年', '株式会社ヤマキシ不動産を設立<br>'
	               . '<b>リフォーム専門店3号店として、リフォームヤマキシ羽咋店を開店</b>', true ),
	array( '2024年', '<b>エコキュート＆外壁塗装リフォームの専門店として、ヤマキシ金沢田上店を開店</b>', true ),
	array( '2026年', '<b>リフォームヤマキシ東金沢店を開店予定</b>（2026年10月31日）', true ),
);

/* 店舗。リフォームの窓口になるお店です */
$shops = array(
	'石川県' => array( '田鶴浜店', '羽咋店', '金沢田上店', '金沢野々市店', '東金沢店（2026年10月オープン予定）', '川北店', '小松店', '新加賀店' ),
	'福井県' => array( '金津店（本社）', '開発店', '朝日店' ),
);

get_header();
?>

<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li>会社概要</li>
  </ol>
</nav>

<main id="main">

<div class="p-pagehead">
  <div class="l-wrap p-pagehead__inner">
    <span class="p-pagehead__en">COMPANY</span>
    <h1 class="p-pagehead__title">会社概要</h1>
    <p class="p-pagehead__lead">
      1900年創業。石川県・福井県で<br class="xs-only">ホームセンターとリフォームを営んでいます。
    </p>
  </div>
</div>

<!-- =========== 数字で見るヤマキシ =========== -->
<section class="l-section">
  <div class="l-wrap l-wrap--narrow">
    <ul class="p-compnum" data-reveal>
      <li><span class="p-compnum__n">1900</span><span class="p-compnum__u">年創業</span>
        <span class="p-compnum__t">明治33年、畳表づくりから</span></li>
      <li><span class="p-compnum__n">11</span><span class="p-compnum__u">店舗</span>
        <span class="p-compnum__t">石川県・福井県</span></li>
      <li><span class="p-compnum__n">500</span><span class="p-compnum__u">名</span>
        <span class="p-compnum__t">従業員数</span></li>
      <li><span class="p-compnum__n">133</span><span class="p-compnum__u">億円</span>
        <span class="p-compnum__t">年商（令和7年1月期）</span></li>
    </ul>
    <p class="p-comp__lead" data-reveal>
      ヤマキシは、<b>地元でホームセンターを営んでいる会社</b>です。
      燃料の配達で給湯器や水まわりの修理をするうちに、
      「もっと気軽にリフォームを頼めるように」という思いから、
      2013年にリフォーム事業を始めました。<br>
      工事のあとも、お店がすぐ近くにあります。
    </p>
  </div>
</section>

<!-- =========== 会社概要 =========== -->
<section class="l-section l-section--soft">
  <div class="l-wrap l-wrap--narrow">
    <div class="c-head">
      <h2 class="c-head__title">会社の<span class="marker">概要</span></h2>
    </div>

    <table class="p-comp__table" data-reveal>
      <tbody>
        <?php foreach ( $rows as $r ) : ?>
          <tr>
            <th><?php echo esc_html( $r[0] ); ?></th>
            <td><?php echo esc_html( $r[1] ); ?></td>
          </tr>
        <?php endforeach; ?>
        <tr>
          <th>建設業許可</th>
          <td>
            <?php
            /* ★ 番号が分かったら、ここに入れてください
               例）石川県知事許可（般-○）第○○○○○号 */
            $kyoka = '';
            echo $kyoka !== '' ? esc_html( $kyoka )
               : '2012年取得<span class="p-comp__todo">※許可番号は準備中です</span>';
            ?>
          </td>
        </tr>
      </tbody>
    </table>

    <p class="p-comp__note" data-reveal>
      決算報告（税引前当期純利益）など、経営に関するくわしい情報は
      コーポレートサイトに公開しています。
    </p>
  </div>
</section>

<!-- =========== 店舗 =========== -->
<section class="l-section">
  <div class="l-wrap l-wrap--narrow">
    <div class="c-head">
      <h2 class="c-head__title">リフォームの<span class="marker">窓口</span></h2>
      <p class="c-head__lead">石川県・福井県に11店舗。お近くのお店からおよそ30分以内でお伺いします。</p>
    </div>

    <div class="p-compshop" data-reveal>
      <?php foreach ( $shops as $pref => $list ) : ?>
        <div class="p-compshop__group">
          <p class="p-compshop__pref"><?php echo esc_html( $pref ); ?>
            <span>（<?php echo count( $list ); ?>店舗）</span></p>
          <ul class="p-compshop__list">
            <?php foreach ( $list as $sh ) : ?>
              <li><?php echo esc_html( $sh ); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>

    <p class="p-lpnext">
      <a class="p-lpnext__link" href="<?php echo esc_url( home_url( '/shops/' ) ); ?>">
        <span class="p-lpnext__ttl">店舗・対応エリアを見る</span>
        <span class="p-lpnext__txt">住所・電話番号・営業時間と、お伺いできる市町村をのせています</span>
      </a>
    </p>
  </div>
</section>

<!-- =========== 沿革 =========== -->
<section class="l-section l-section--soft">
  <div class="l-wrap l-wrap--narrow">
    <div class="c-head">
      <h2 class="c-head__title">会社の<span class="marker">あゆみ</span></h2>
    </div>

    <figure class="p-comphist__old" data-reveal>
      <picture>
        <source srcset="<?php echo $asset; ?>/assets/img/company/history-1958.webp" type="image/webp">
        <img src="<?php echo $asset; ?>/assets/img/company/history-1958.jpg"
             width="400" height="600" alt="1958年ごろの山岸金物店。木造の店先に「山岸金物店」の看板が出ています"
             loading="lazy" decoding="async">
      </picture>
      <figcaption>1958年（昭和33年）ごろの山岸金物店。ここから今のヤマキシが始まりました。</figcaption>
    </figure>

    <ol class="p-comphist" data-reveal>
      <?php foreach ( $history as $h ) : ?>
        <li<?php echo $h[2] ? ' class="is-reform"' : ''; ?>>
          <span class="p-comphist__y"><?php echo esc_html( $h[0] ); ?></span>
          <span class="p-comphist__t"><?php echo wp_kses_post( $h[1] ); ?></span>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<!-- =========== 関連ページ =========== -->
<section class="l-section">
  <div class="l-wrap l-wrap--narrow">
    <h2 class="p-lprel__h2">あわせてご覧ください</h2>
    <ul class="p-lprel">
      <li><a href="<?php echo esc_url( home_url( '/message/' ) ); ?>">代表挨拶</a><span>「水まわり市場」を始めた理由</span></li>
      <li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">こだわり・特徴</a><span>安い・早い・安心の3つのコンセプト</span></li>
      <li><a href="<?php echo esc_url( home_url( '/staff/' ) ); ?>">スタッフ紹介</a><span>各店の営業担当の顔ぶれ</span></li>
      <li><a href="<?php echo esc_url( home_url( '/works/' ) ); ?>">施工事例</a><span>Before・Afterと、かかった費用</span></li>
      <li><a href="<?php echo esc_url( home_url( '/voice/' ) ); ?>">お客様の声</a><span>アンケート「仕事の通信簿」</span></li>
      <li><a href="<?php echo esc_url( home_url( '/shops/' ) ); ?>">店舗・エリア</a><span>石川県・福井県に11店舗</span></li>
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
        「いくらかかるか知りたいだけ」でも歓迎です。
      </p>
      <div class="p-lpcta__btns">
        <a class="c-btn c-btn--line c-btn--block" href="https://lin.ee/UJZuSTrz" rel="noopener" data-cta="company-cta">
          <span class="c-btn__label">LINEで無料見積り<span class="c-btn__sub">ご相談だけでもOK・24時間受付</span></span>
        </a>
        <a class="c-btn c-btn--block" href="<?php echo esc_url( home_url( '/inquiry/webrsv/' ) ); ?>" data-cta="company-cta">
          <span class="c-btn__label">ショールーム来店予約<span class="c-btn__sub">初回特典500円ヤマキシお買物券<br>※展示のない店舗もあります</span></span>
        </a>
        <a class="c-btn c-btn--ghost c-btn--block" href="tel:0800-777-3331" data-cta="company-cta">
          <span class="c-btn__label">0800-777-3331<span class="c-btn__sub">通話無料・受付 9:00〜17:00</span></span>
        </a>
      </div>
    </div>
  </div>
</section>

</main>

<?php get_footer();
