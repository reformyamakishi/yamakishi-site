<?php
/**
 * ymkrf-privacy.php ─ プライバシーポリシー（/privacy/）
 *
 * 置き場所： wp-content/themes/ymkrf/ymkrf-privacy.php
 *
 * もとの資料：ユーザーからいただいたテキスト
 *   「プライバシーポリシー｜株式会社 山岸」（コーポレートサイトのもの／2026年8月）
 *
 * ★ここは「法律の文章」です。勝手に足したり削ったりしないでください。
 *   文言を変えるときは、かならず会社のご担当者に確認をとってください。
 *   （下の $sections を直せば、画面の中身が変わります）
 *
 * ★まだ入っていない項目（必要かどうか、会社でご判断ください）
 *   ・アクセス解析ツール（Google アナリティクスなど）について
 *   ・Cookie（クッキー）について
 *   ・制定日／最終改定日
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$asset = get_stylesheet_directory_uri();

/* ------------------------------------------------------------------
   本文
     t … 見出し
     b … 本文（<p> で囲まれます。改行したいところは <br> ）
     l … 箇条書き（無いときは空の配列のまま）
     a … 箇条書きのあとに続ける文（無いときは ''）
   ------------------------------------------------------------------ */
$sections = array(

	array(
		't' => '個人情報の管理',
		'b' => '当社は、お客さまの個人情報を正確かつ最新の状態に保ち、個人情報への不正アクセス・紛失・破損・改ざん・漏洩などを防止するため、'
		     . 'セキュリティシステムの維持・管理体制の整備・社員教育の徹底等の必要な措置を講じ、安全対策を実施し個人情報の厳重な管理を行ないます。',
		'l' => array(),
		'a' => '',
	),

	array(
		't' => '個人情報の利用目的',
		'b' => '当社が個人情報を利用するにあたっては、取得の際に示した利用目的の範囲内で、業務遂行上必要な分野において利用いたします。'
		     . 'また、以下の場合についても利用いたします。',
		'l' => array(
			'お客様に対する催物等開催のご案内や賞品等の送付',
			'お客様に対するご連絡や業務のご案内',
			'お客様に連絡をとる必要が生じた際の利用',
			'ご質問に対する回答',
			'電子メールや資料の送付',
			'上記以外でお客様の同意があった場合',
		),
		'a' => '',
	),

	array(
		't' => '個人情報の第三者への開示・提供の禁止',
		'b' => '当社は、お客さまよりお預かりした個人情報を適切に管理し、次のいずれかに該当する場合を除き、個人情報を第三者に開示いたしません。',
		'l' => array(
			'お客さまの同意がある場合',
			'お客さまが希望されるサービスを行なうために当社が業務を委託する業者に対して開示する場合',
			'人の生命、身体または財産の保護のために必要であって、お客様の同意を得ることが困難である場合',
			'国の機関、もしくは地方公共団体またはその委託を受けた者が、法令の定める事務を遂行する事に対して協力する必要がある場合であって、'
			. 'お客様の同意を得ることにより、当該事務の遂行に支障を及ぼすおそれがある場合',
		),
		'a' => '',
	),

	array(
		't' => '個人情報の安全対策',
		'b' => '当社は、個人情報の正確性及び安全性確保のために、セキュリティに万全の対策を講じています。',
		'l' => array(),
		'a' => '',
	),

	array(
		't' => 'ご本人の照会',
		'b' => 'お客さまがご本人の個人情報の照会・修正・削除などをご希望される場合には、ご本人であることを確認の上、対応させていただきます。',
		'l' => array(),
		'a' => '',
	),

	array(
		't' => '法令、規範の遵守と見直し',
		'b' => '当社は、保有する個人情報に関して適用される日本の法令、その他規範を遵守するとともに、'
		     . '本ポリシーの内容を適宜見直し、その改善に努めます。',
		'l' => array(),
		'a' => '',
	),

);

get_header();
?>

<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li>プライバシーポリシー</li>
  </ol>
</nav>

<main id="main">

<div class="p-pagehead">
  <div class="l-wrap p-pagehead__inner">
    <img class="p-pagehead__chara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-anshin.webp"
         width="503" height="640" alt="" loading="lazy" decoding="async">
    <span class="p-pagehead__en">PRIVACY POLICY</span>
    <h1 class="p-pagehead__title">プライバシーポリシー</h1>
    <p class="p-pagehead__lead">
      お客さまからお預かりした<b>個人情報の取り扱い</b>について、<br class="xs-only">
      当社の考えかたをご説明します。
    </p>
  </div>
</div>

<section class="l-section">
  <div class="l-wrap l-wrap--narrow">

    <p class="p-pp__intro" data-reveal>
      株式会社山岸（以下「当社」）は、以下のとおり個人情報保護方針を定め、個人情報保護の仕組みを構築し、
      全従業員に個人情報保護の重要性の認識と取組みを徹底させることにより、個人情報の保護を推進致します。
    </p>

    <!-- 目次 -->
    <nav class="p-ppnav" aria-label="このページの目次" data-reveal>
      <p class="p-ppnav__title">このページの内容</p>
      <ol class="p-ppnav__list">
        <?php foreach ( $sections as $i => $sc ) : ?>
          <li><a href="#pp<?php echo (int) ( $i + 1 ); ?>"><?php echo esc_html( $sc['t'] ); ?></a></li>
        <?php endforeach; ?>
      </ol>
    </nav>

    <!-- 本文 -->
    <?php foreach ( $sections as $i => $sc ) : ?>
      <section class="p-pp" id="pp<?php echo (int) ( $i + 1 ); ?>" data-reveal>
        <h2 class="p-pp__title">
          <span class="p-pp__n"><?php echo (int) ( $i + 1 ); ?></span>
          <?php echo esc_html( $sc['t'] ); ?>
        </h2>
        <?php if ( $sc['b'] !== '' ) : ?>
          <p class="p-pp__text"><?php echo wp_kses_post( $sc['b'] ); ?></p>
        <?php endif; ?>
        <?php if ( ! empty( $sc['l'] ) ) : ?>
          <ul class="p-pp__list">
            <?php foreach ( $sc['l'] as $li ) : ?>
              <li><?php echo wp_kses_post( $li ); ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <?php if ( $sc['a'] !== '' ) : ?>
          <p class="p-pp__text"><?php echo wp_kses_post( $sc['a'] ); ?></p>
        <?php endif; ?>
      </section>
    <?php endforeach; ?>

    <!-- お問い合わせ窓口 -->
    <div class="p-ppcontact" data-reveal>
      <h2 class="p-ppcontact__title">個人情報についてのお問い合わせ</h2>
      <p class="p-ppcontact__text">
        個人情報の照会・修正・削除のご依頼や、このページについてのご質問は、
        下記までお気軽にご連絡ください。
      </p>
      <p class="p-ppcontact__name">株式会社山岸（リフォームヤマキシ）</p>
      <p class="p-ppcontact__tel">
        <a href="tel:0800-777-3331" data-cta="privacy">0800-777-3331</a>
        <span>通話無料・受付 9:00〜17:00</span>
      </p>
      <p class="p-ppcontact__links">
        <a href="<?php echo esc_url( home_url( '/company/' ) ); ?>">会社概要を見る</a>
        <a href="<?php echo esc_url( home_url( '/shops/' ) ); ?>">お近くの店舗を探す</a>
      </p>
    </div>

  </div>
</section>

</main>

<?php get_footer();
