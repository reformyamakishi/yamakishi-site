<?php
/**
 * ymkrf-inquiry.php ─ お見積り・お問い合わせ（/inquiry/）
 *
 * 置き場所： wp-content/themes/ymkrf/ymkrf-inquiry.php
 *
 * ── このページの役目 ──────────────────────────────
 * LINE・お電話・ご来店のほかに、「メールで送っておきたい」という方の
 * 受け口です。LINEをお使いでない方や、夜のうちに送っておきたい方が
 * ここから入ってこられます。
 *
 * 来店予約（/inquiry/webrsv/）とは別のフォームです。
 * あちらは「お店に行く約束」、こちらは「家に来てもらう・値段を知る」ための
 * ものと考えてください。
 *
 * ── フォームの中身について ────────────────────────
 * フォームそのものは Contact Form 7 で作ります。
 * 管理画面 →「お問い合わせ」→「新規追加」で、
 *
 *      タイトルを ★「お見積り・お問い合わせ」★ にして
 *      wp/contact-form-7_inquiry.txt の中身を貼り付けてください。
 *
 * タイトルさえ合っていれば、このページが自動で見つけて表示します。
 * ショートコードの番号を貼り付ける必要はありません。
 * ────────────────────────────────────────────
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$dir  = get_stylesheet_directory_uri();
$line = 'https://lin.ee/UJZuSTrz';
$tel  = '0800-777-3331';

/* ------------------------------------------------------------
   Contact Form 7 のフォームを、タイトルで探します。
   見つからないときは、ご案内だけ出します（ページは壊れません）。
   ------------------------------------------------------------ */
$ymkrf_cf7_title = 'お見積り・お問い合わせ';
$ymkrf_cf7_id    = 0;

if ( post_type_exists( 'wpcf7_contact_form' ) ) {
	$found = get_posts( array(
		'post_type'        => 'wpcf7_contact_form',
		'title'            => $ymkrf_cf7_title,
		'post_status'      => 'any',
		'numberposts'      => 1,
		'fields'           => 'ids',
		'suppress_filters' => false,
	) );
	if ( ! empty( $found ) ) $ymkrf_cf7_id = (int) $found[0];
}

/* 店舗の情報（inc/functions-shops.php にまとまっています）。
   県ごとにまとめ直して、下の一覧で使います。 */
$shops_by_pref = array();
if ( function_exists( 'ymkrf_shops' ) ) {
	foreach ( ymkrf_shops() as $sp ) {
		$shops_by_pref[ $sp['pref'] ][] = $sp;
	}
}

/* ご相談の流れ（3つ）。来店予約の5ステップとは別物です。 */
$steps = array(
	array( 'ttl' => 'この画面からフォームを入力し送信',
	       'txt' => '24時間いつでも受け付けています。ご相談だけでもかまいません。' ),
	array( 'ttl' => 'お近くの店舗からご連絡',
	       'txt' => 'ご住所から、いちばん近い店舗の担当がご連絡します。1〜3日いただくことがあります。' ),
	array( 'ttl' => '現地調査・お見積り',
	       'txt' => 'ご都合のよい日に、実際に見せていただきます。調査もお見積りも無料です。' ),
);

get_header();
?>

<!-- =========== パンくず =========== -->
<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li>お見積り・お問い合わせ</li>
  </ol>
</nav>

<main id="main">

<!-- =========== ページ見出し =========== -->
<div class="p-pagehead">
  <div class="l-wrap p-pagehead__inner">
    <span class="p-pagehead__en">CONTACT</span>
    <h1 class="p-pagehead__title">お見積り・お問い合わせ</h1>
    <p class="p-pagehead__lead">
      現地調査もお見積りも<b>無料</b>です。<br class="xs-only">ご相談だけでもかまいません。<br>
      しつこい営業はいたしません。<br class="xs-only">他社さんとのお見積り比べも大歓迎です。
    </p>
  </div>
</div>

<!-- =========== ご相談の流れ =========== -->
<section class="l-section">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">FLOW</span>
      <h2 class="c-head__title">送信したあとの<span class="marker">流れ</span></h2>
    </div>

    <ol class="p-inq__steps">
      <?php foreach ( $steps as $i => $s ) : ?>
        <li class="p-inq__step">
          <span class="p-inq__stepno"><?php echo (int) ( $i + 1 ); ?></span>
          <h3 class="p-inq__stepttl"><?php echo esc_html( $s['ttl'] ); ?></h3>
          <p class="p-inq__steptxt"><?php echo esc_html( $s['txt'] ); ?></p>
        </li>
      <?php endforeach; ?>
    </ol>

    <p class="p-inq__note">
      ※お見積りのあとで、追加の工事が必要になったときは、着工前にかならずお見積りをお出しします。<br>
      ※いただいた個人情報は、ご相談への対応以外には使いません。<a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>">プライバシーポリシー</a>
    </p>
  </div>
</section>

<!-- =========== フォーム =========== -->
<section class="l-section l-section--soft" id="form">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">FORM</span>
      <h2 class="c-head__title">ご相談・お見積り<span class="marker">フォーム</span></h2>
    </div>

    <?php /* ★営業・セールスの連絡をここで止めます（2026/09/03 ユーザー指示）。
             問い合わせ件数がふくらむと、本当のお客様の数が見えなくなるためです。
             売り込みは、株式会社山岸の新規取引企業様向け窓口へお送りします。 */ ?>
    <p class="p-inq__nosales">
      ※営業・セールスに関するご連絡は一切お断りしています。お取引窓口：<a
        href="https://www.yamakishi.co.jp/contact/index.html" target="_blank" rel="noopener"
        >新規取引企業様向け窓口（株式会社山岸）</a>
    </p>

    <div class="p-form">
      <?php if ( $ymkrf_cf7_id ) : ?>

        <?php echo do_shortcode( '[contact-form-7 id="' . $ymkrf_cf7_id . '"]' ); ?>

      <?php else : ?>

        <div class="p-inq__soon">
          <p class="p-inq__soonttl">ただいまフォームを準備しています</p>
          <p>
            お手数をおかけします。<br>
            LINE・お電話でしたら、いますぐご相談いただけます。下のボタンからどうぞ。
          </p>
        </div>

        <?php if ( current_user_can( 'manage_options' ) ) : ?>
          <div class="p-inq__admin">
            <p><b>スタッフの方へ（この案内はログイン中だけ見えています）</b></p>
            <p>
              フォームがまだ作られていません。<br>
              管理画面 →「お問い合わせ」→「新規追加」で、タイトルを
              <b>「<?php echo esc_html( $ymkrf_cf7_title ); ?>」</b>にして、
              <code>wp/contact-form-7_inquiry.txt</code> の中身を貼り付けて保存してください。<br>
              タイトルが合っていれば、このページが自動で見つけて表示します。
            </p>
          </div>
        <?php endif; ?>

      <?php endif; ?>
    </div>

    <?php /* reCAPTCHA のご案内。バッジのかわりに出しています（inc/functions-snippet.php） */ ?>
    <?php if ( function_exists( 'ymkrf_recaptcha_note' ) ) ymkrf_recaptcha_note(); ?>
  </div>
</section>

<!-- =========== お電話でのご相談 ===========
     ★ユーザー指示（2026/09/03）でフォームの下に置いています。
       フリーダイヤルの下に、全店の直通番号を並べます。
       店舗の情報は inc/functions-shops.php から読んでいるので、
       ここを直す必要はありません。 -->
<section class="l-section" id="tel">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">TEL</span>
      <h2 class="c-head__title">お電話でのご相談</h2>
    </div>

    <div class="p-inq__telbox">
      <a class="p-inq__tel" href="tel:<?php echo esc_attr( $tel ); ?>" data-cta="inquiry-tel">
        <span class="p-inq__tellbl">フリーコール</span>
        <span class="p-inq__telnum"><?php echo esc_html( $tel ); ?></span>
        <span class="p-inq__telsub">通話無料・受付 9:00〜17:00</span>
      </a>
      <p class="p-inq__telnote">お急ぎのときは、下記からお近くの店舗をお選びいただき、お電話ください。</p>
    </div>

    <?php foreach ( $shops_by_pref as $pref => $list ) : ?>
      <h3 class="p-inq__pref"><?php echo esc_html( $pref ); ?></h3>
      <ul class="p-inq__shops">
        <?php foreach ( $list as $sp ) : ?>
          <li class="p-inq__shop">
            <p class="p-inq__shopname">
              <?php echo esc_html( $sp['name'] ); ?>
              <?php if ( ! empty( $sp['soon'] ) ) : ?>
                <span class="p-inq__shopsoon">準備中</span>
              <?php endif; ?>
            </p>

            <?php if ( ! empty( $sp['tel'] ) ) : ?>
              <a class="p-inq__shoptel" href="tel:<?php echo esc_attr( $sp['tel'] ); ?>" data-cta="inquiry-shop">
                <?php echo esc_html( $sp['tel'] ); ?>
              </a>
            <?php else : ?>
              <p class="p-inq__shopsoontxt"><?php echo esc_html( $sp['soon'] ); ?></p>
            <?php endif; ?>

            <?php if ( ! empty( $sp['hours'] ) ) : ?>
              <p class="p-inq__shophours">
                <?php echo esc_html( $sp['hours'] ); ?>
                <?php if ( ! empty( $sp['hnote'] ) ) : ?>
                  <small><?php echo esc_html( $sp['hnote'] ); ?></small>
                <?php endif; ?>
              </p>
            <?php endif; ?>

            <?php if ( ! empty( $sp['areas'] ) ) : ?>
              <p class="p-inq__shoparea"><?php echo esc_html( implode( '／', $sp['areas'] ) ); ?></p>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endforeach; ?>

    <p class="p-inq__note" style="text-align:center">
      複数の店舗が入っている市町は、<b>お近くの店舗</b>へどうぞ。<br>
      <a href="<?php echo esc_url( home_url( '/shops/' ) ); ?>">店舗の一覧・地図はこちら</a>
    </p>
  </div>
</section>

<!-- =========== ほかの方法 =========== -->
<section class="l-section l-section--tint">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">OTHER</span>
      <h2 class="c-head__title">入力が面倒な方は、<br class="sp-only">こちらから</h2>
    </div>

    <div class="p-pagecta__btns" style="max-width:520px;margin:0 auto">
      <a class="c-btn c-btn--line c-btn--block" href="<?php echo esc_url( $line ); ?>" rel="noopener" data-cta="inquiry-alt">
        <span class="c-btn__label">LINEで相談する<span class="c-btn__sub">写真を送るだけでもOK・24時間受付</span></span>
      </a>
      <a class="c-btn c-btn--block" href="<?php echo esc_url( home_url( '/inquiry/webrsv/' ) ); ?>" data-cta="inquiry-alt">
        <span class="c-btn__label">来店して現物を見る<span class="c-btn__sub">初回特典500円ヤマキシお買物券<br>※展示のない店舗もあります</span></span>
      </a>
    </div>

    <p class="p-inq__note" style="text-align:center">
      壁のひび割れ、水まわりの傷みなど、<b>お写真を送っていただけると話が早いです</b>。<br>
      LINEなら、その場で撮って送るだけで大丈夫です。
    </p>
  </div>
</section>

</main>

<?php get_footer();
