<?php
/**
 * ymkrf-partner.php ─ 協力業者さん・職人さん募集（/inquiry/conpanyrecruit/）
 *
 * 置き場所： wp-content/themes/ymkrf/ymkrf-partner.php
 * しくみ：   inc/functions-partner.php
 *
 * ── 中身の直しかた（2026/11/03 ユーザー「お知らせみたいな感じの作りで良い」） ──
 * ダッシュボード「固定ページ」→「協力業者さん・職人さん募集」。
 *   題名 ＝ このページの見出し
 *   本文 ＝ まん中に出ます（文章・写真・職種・エリア・流れ・電話番号など）
 *   アイキャッチ画像 ＝ トップページの「お知らせ」の下に出るバナー
 * エントリーフォームと「社員の採用」の案内は、本文の下に自動で付きます。
 *
 * 見た目は前の作り（写真が右・職種のボタン・流れのカード・チラシ）のまま。
 * 本文から「写真」「募集職種」「募集エリア」「お仕事の流れ」「チラシ」「電話番号」を見つけて
 * 決まった場所に並べます（inc/functions-partner.php の ymkrf_partner_parse()）。
 * どれにも当たらない文や見出しは、流れの下にそのまま出します。
 * 編集画面の「このページだけのCSS」に書いたCSSも、このページに読みこみます。
 *
 * ── フォーム ───────────────────────────────
 * Contact Form 7「協力業者さん・職人さんエントリー」（タイトルで探します）。
 * 項目・届け先・メール文は「お問い合わせ」で直します。控え：wp/contact-form-7_partner.txt
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* 本文（プレビューのときは、プレビュー中の内容） */
$pid  = function_exists( 'ymkrf_partner_page_id' ) ? ymkrf_partner_page_id() : 0;
$pg   = ( $pid && is_page( $pid ) ) ? get_queried_object() : ( function_exists( 'ymkrf_partner_page' ) ? ymkrf_partner_page() : null );
$name = ( $pg && trim( get_the_title( $pg ) ) !== '' ) ? get_the_title( $pg ) : '協力業者さん・職人さん募集';
$html = '';
if ( $pg ) {
	$GLOBALS['post'] = $pg;
	setup_postdata( $pg );
	$html = apply_filters( 'the_content', $pg->post_content );
	wp_reset_postdata();
}
$d       = ymkrf_partner_parse( $html );
$tel     = $d['tel'];
$tel_who = $d['tel_who'];
$jobs    = $d['jobs'];
$areas   = $d['areas'];
$steps   = $d['steps'];
$photo   = $d['photo'];
$poster  = $d['poster'];
/* 段落の <p> をはずして、改行でつなぎます（見出しの下の文） */
$unwrap  = function ( $h ) {
	$h = preg_replace( '#</p>\s*<p[^>]*>#', '<br>', trim( $h ) );
	return preg_replace( '#</?p[^>]*>#', '', $h );
};

$ymkrf_cf7_title = '協力業者さん・職人さんエントリー';
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

get_header();
?>

<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li><?php echo esc_html( $name ); ?></li>
  </ol>
</nav>

<main id="main">

<div class="p-pagehead">
  <div class="l-wrap p-pagehead__inner">
    <span class="p-pagehead__en">PARTNER</span>
    <h1 class="p-pagehead__title"><?php echo esc_html( $name ); ?></h1>
    <?php if ( trim( $d['lead'] ) !== '' ) : ?>
      <p class="p-pagehead__lead"><?php echo wp_kses_post( $unwrap( $d['lead'] ) ); ?></p>
    <?php endif; ?>
  </div>
</div>

<!-- =========== 募集の内容 =========== -->
<section class="l-section">
  <div class="l-wrap">
    <div class="p-partner__intro<?php echo $photo ? '' : ' p-partner__intro--noimg'; ?>">

      <div class="p-partner__body">
        <?php if ( trim( $d['intro'] ) !== '' ) : ?>
          <div class="p-partner__lead"><?php echo wp_kses_post( $d['intro'] ); ?></div>
        <?php endif; ?>

        <?php if ( $jobs ) : ?>
          <h2 class="p-partner__h">募集職種</h2>
          <ul class="p-partner__jobs">
            <?php foreach ( $jobs as $j ) : ?><li><?php echo esc_html( $j ); ?></li><?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <?php if ( $areas ) : ?>
          <h2 class="p-partner__h">募集エリア</h2>
          <ul class="p-area__towns p-partner__areas">
            <?php foreach ( $areas as $a ) : ?><li><span><?php echo esc_html( $a ); ?></span></li><?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

      <?php if ( $photo ) : ?>
        <figure class="p-partner__photo">
          <?php echo wp_get_attachment_image( $photo, 'large', false, array(
            'loading' => 'eager',
            'alt'     => ymkrf_partner_alt( $photo, 'リフォームヤマキシのスタッフととんとこトン' ),
          ) ); ?>
        </figure>
      <?php endif; ?>
    </div>

    <div class="p-partner__btns<?php echo $tel === '' ? ' p-partner__btns--one' : ''; ?>">
      <a class="c-btn c-btn--block" href="#form" data-cta="partner-form">
        <span class="c-btn__label">応募フォームはこちら<span class="c-btn__sub">24時間受付</span></span>
      </a>
      <?php if ( $tel !== '' ) : ?>
        <a class="p-partner__tel" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $tel ) ); ?>" data-cta="partner-tel">
          <span class="p-partner__tellbl">お電話でのお問い合わせ</span>
          <span class="p-partner__telnum"><?php echo esc_html( $tel ); ?></span>
          <?php if ( $tel_who !== '' ) : ?><span class="p-partner__telsub"><?php echo esc_html( $tel_who ); ?></span><?php endif; ?>
        </a>
      <?php endif; ?>
    </div>
    <?php if ( $d['tel_note'] !== '' ) : ?>
      <p class="p-partner__telnote"><?php echo nl2br( esc_html( $d['tel_note'] ) ); ?></p>
    <?php endif; ?>
  </div>
</section>

<!-- =========== おおまかな流れ =========== -->
<?php if ( $steps || $poster ) : ?>
<section class="l-section l-section--soft">
  <div class="l-wrap">
    <?php if ( $steps ) : ?>
    <div class="c-head">
      <span class="c-head__en">FLOW</span>
      <h2 class="c-head__title">お仕事の<span class="marker">流れ</span></h2>
    </div>
    <ol class="p-inq__steps p-inq__steps--5">
      <?php foreach ( $steps as $i => $s ) : ?>
        <li class="p-inq__step">
          <span class="p-inq__stepno"><?php echo (int) ( $i + 1 ); ?></span>
          <h3 class="p-inq__stepttl"><?php echo esc_html( $s['ttl'] ); ?></h3>
          <p class="p-inq__steptxt"><?php echo esc_html( $s['txt'] ); ?></p>
        </li>
      <?php endforeach; ?>
    </ol>
    <?php endif; ?>

    <?php if ( $poster ) : ?>
      <figure class="p-partner__poster">
        <a href="<?php echo esc_url( wp_get_attachment_url( $poster ) ); ?>" target="_blank" rel="noopener">
          <?php echo wp_get_attachment_image( $poster, 'large', false, array(
            'alt' => ymkrf_partner_alt( $poster, '協力業者さん・職人さん大募集のチラシ'
                     . ( $jobs ? '（募集職種：' . implode( '・', $jobs ) . '）' : '' ) ),
          ) ); ?>
        </a>
        <figcaption>募集チラシ（押すと大きく見られます）</figcaption>
      </figure>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<!-- =========== 本文で、上のどれにも当たらなかったもの =========== -->
<?php if ( trim( wp_strip_all_tags( $d['extra'], true ) ) !== '' || strpos( $d['extra'], '<img' ) !== false ) : ?>
<section class="l-section">
  <div class="l-wrap">
    <div class="p-partner__content"><?php echo $d['extra']; // phpcs:ignore ?></div>
  </div>
</section>
<?php endif; ?>

<!-- =========== エントリーフォーム =========== -->
<section class="l-section" id="form">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">ENTRY</span>
      <h2 class="c-head__title">協力業者様用<span class="marker">エントリーフォーム</span></h2>
    </div>

    <p class="p-partner__formlead">
      ご入力いただきますと、採用担当者よりご連絡させていただきます。<br>
      協力会社様、職人として独立されている方、どうぞお気軽にエントリーください。
    </p>

    <div class="p-form">
      <?php if ( $ymkrf_cf7_id ) : ?>
        <?php echo do_shortcode( '[contact-form-7 id="' . $ymkrf_cf7_id . '"]' ); ?>
      <?php else : ?>
        <div class="p-inq__soon">
          <p class="p-inq__soonttl">ただいまフォームを準備しています</p>
          <p><?php echo $tel !== '' ? 'お手数ですが、お電話（' . esc_html( $tel ) . '）でご連絡ください。' : 'しばらくお待ちください。'; ?></p>
        </div>
        <?php if ( current_user_can( 'manage_options' ) ) : ?>
          <div class="p-inq__admin">
            <p><b>スタッフの方へ（ログイン中だけ見えています）</b></p>
            <p>管理画面 →「お問い合わせ」→「新規追加」で、タイトルを
              <b>「<?php echo esc_html( $ymkrf_cf7_title ); ?>」</b>にして、
              <code>wp/contact-form-7_partner.txt</code> の中身を貼り付けて保存してください。</p>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <?php if ( function_exists( 'ymkrf_recaptcha_note' ) ) ymkrf_recaptcha_note(); ?>

    <p class="p-inq__note">
      ※いただいた個人情報は、このエントリーへの対応以外には使いません。<a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>">プライバシーポリシー</a>
    </p>
  </div>
</section>

<!-- =========== 社員の採用はこちら =========== -->
<section class="l-section l-section--tint">
  <div class="l-wrap">
    <div class="p-partner__staff">
      <p class="p-partner__staffttl">社員（スタッフ）の採用情報はこちら</p>
      <p>株式会社山岸で働くスタッフの募集は、採用サイトでご案内しています。</p>
      <a class="c-btn" href="https://yamakishi.co.jp/recruit/" target="_blank" rel="noopener">
        <span class="c-btn__label">採用サイトを見る</span>
      </a>
    </div>
  </div>
</section>


</main>

<?php get_footer();
