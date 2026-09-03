<?php
/**
 * ymkrf-webrsv.php ─ ネット来店予約（/inquiry/webrsv/）
 *
 * 置き場所： wp-content/themes/ymkrf/ymkrf-webrsv.php
 *
 * ── お見積り・お問い合わせ（/inquiry/）と同じ作りです ──────────
 * フォームそのものは Contact Form 7 で作ります。
 * 管理画面 →「お問い合わせ」→「新規追加」で、
 *
 *      タイトルを ★「ネット来店予約」★ にして
 *      wp/contact-form-7_webrsv.txt の中身を貼り付けてください。
 *
 * タイトルさえ合っていれば、このページが自動で見つけて表示します。
 * ショートコードの番号を貼り付ける必要はありません。
 *
 * ── 2つのフォームのちがい ─────────────────────────
 *   /inquiry/         … 家に来てもらう・値段を知るためのご相談
 *   /inquiry/webrsv/  … お店に行く約束（初回はお買物券500円分）
 * ────────────────────────────────────────────
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$dir  = get_stylesheet_directory_uri();
$tel  = '0800-777-3331';

/* Contact Form 7 のフォームを、タイトルで探します。
   見つからないときは、ご案内だけ出します（ページは壊れません）。 */
$ymkrf_cf7_title = 'ネット来店予約';
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

/* 来店予約の流れ（5つ） */
$steps = array(
	array( 'ttl' => 'この画面からフォームを入力し送信',
	       'txt' => '24時間いつでも受け付けています。入力は3分ほどで終わります。' ),
	array( 'ttl' => 'ご来店',
	       'txt' => '当社からの確認のお電話のあと、ご都合の日にご来店ください。',
	       'sub' => '※ご来店日が確定でご予約となります。' ),
	array( 'ttl' => 'スタッフがご案内',
	       'txt' => '展示品を見ながら、お困りごとをお聞かせください。' ),
	array( 'ttl' => 'アンケートご記入',
	       'txt' => 'いただいたご意見は、サービスの改善に活かしています。' ),
	array( 'ttl' => 'お買物券プレゼント',
	       'txt' => 'ヤマキシお買物券500円分をお渡しします。（初回のご来店の方のみ）',
	       'gift' => true ),
);

/* ------------------------------------------------------------
   店舗ごとの「連絡がつきやすい時間帯」

   お店によって営業時間がちがうので、選択肢もお店ごとに作ります。
   営業時間を、下の $cuts の時刻で区切ったものが選択肢になります。

     例）小松店（10:00〜18:00）
         → 10:00〜12:00 ／ 12:00〜13:00 ／ 13:00〜15:00 ／ 15:00〜18:00
     例）田鶴浜店（8:00〜21:00）
         → 8:00〜12:00 ／ 12:00〜13:00 ／ 13:00〜15:00 ／ 15:00〜18:00 ／ 18:00〜21:00

   ★区切りを変えたいときは、$cuts の数字だけ直してください。
     選択肢は自動で作り直されます。
   ------------------------------------------------------------ */
$cuts = array( 12, 13, 15, 18 );

/* 9.5 → 「9:30」、10 → 「10:00」 */
$ymkrf_hm = function ( $h ) {
	$hh = (int) floor( $h );
	$mm = (int) round( ( $h - $hh ) * 60 );
	return $hh . ':' . str_pad( $mm, 2, '0', STR_PAD_LEFT );
};

$shop_slots = array();
foreach ( ymkrf_shops() as $sp ) {

	if ( empty( $sp['hours'] ) ) continue;   /* まだ開いていないお店 */

	/* 「8:00〜21:00」を、8 と 21 に分けます */
	$hh = preg_split( '/[〜~－\-]/u', $sp['hours'] );
	if ( count( $hh ) < 2 ) continue;
	$to_h = function ( $t ) {
		$t = trim( $t );
		if ( ! preg_match( '/(\d{1,2})(?::(\d{2}))?/', $t, $m ) ) return null;
		return (float) $m[1] + ( isset( $m[2] ) ? (int) $m[2] / 60 : 0 );
	};
	$open  = $to_h( $hh[0] );
	$close = $to_h( $hh[1] );
	if ( $open === null || $close === null || $close <= $open ) continue;

	/* 営業時間の中に入る区切りだけを拾って、区切り目を作ります */
	$pts = array( $open );
	foreach ( $cuts as $c ) {
		if ( $c > $open && $c < $close ) $pts[] = (float) $c;
	}
	$pts[] = $close;

	/* 30分に満たないきれはしは、ひとつ前とくっつけます */
	$fixed = array( $pts[0] );
	for ( $k = 1; $k < count( $pts ); $k++ ) {
		if ( $pts[ $k ] - end( $fixed ) < 0.5 && $k < count( $pts ) - 1 ) continue;
		$fixed[] = $pts[ $k ];
	}
	if ( count( $fixed ) > 2 && end( $fixed ) - $fixed[ count( $fixed ) - 2 ] < 0.5 ) {
		array_splice( $fixed, count( $fixed ) - 2, 1 );
	}

	$slots = array();
	for ( $k = 0; $k < count( $fixed ) - 1; $k++ ) {
		$slots[] = $ymkrf_hm( $fixed[ $k ] ) . '〜' . $ymkrf_hm( $fixed[ $k + 1 ] );
	}

	$shop_slots[ $sp['name'] ] = array(
		'slots' => $slots,
		'hours' => $sp['hours'],
		'hnote' => isset( $sp['hnote'] ) ? $sp['hnote'] : '',
	);
}

/* ご注意事項 */
$notes = array(
	'ヤマキシお買物券は、1名様（1家族様）1回限りとさせていただきます。',
	'未成年者様のみのご来場は対象外とさせていただきます。',
	'アンケートにご協力していただくことが条件になります。',
	'お申し込み後、当社からの予約確認の電話連絡をもって予約完了となります。電話連絡は1〜3日以内にさせていただきます。',
	'お急ぎの場合は、直接お近くのヤマキシへご連絡ください。※その場合は特典対象外となります。',
	'くわしくは現地係員にお問い合わせください。',
	'エコキュート・給湯器・トイレ・洗面などの設備は、現状の写真を撮ってご来店いただくと、その場でお見積りできる場合がございます。設置状況が分かる引きの写真、設備の色々な角度の写真、ラベル表記などがあるとお見積りしやすくなります。なお、キッチンやお風呂、外壁塗装や部屋の改装のお見積りは写真ではできません。現地調査が必要となります。',
);

get_header();
?>

<!-- =========== パンくず =========== -->
<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li><a href="<?php echo esc_url( home_url( '/inquiry/' ) ); ?>">お見積り・お問い合わせ</a></li>
    <li>ネット来店予約</li>
  </ol>
</nav>

<main id="main">

<!-- =========== ページ見出し =========== -->
<div class="p-pagehead">
  <div class="l-wrap p-pagehead__inner">
    <span class="p-pagehead__en">RESERVATION</span>
    <h1 class="p-pagehead__title">ネット来店予約</h1>
    <p class="p-pagehead__lead">
      ショールームには北陸最大級の住宅設備を展示しています。<br>
      複数メーカーの実物を一度に見くらべられます。
    </p>
  </div>
</div>

<!-- =========== 初回特典 =========== -->
<section class="l-section p-benefit">
  <div class="l-wrap">
    <div class="p-benefit__box">
      <div class="p-benefit__body">
        <span class="p-benefit__tag">初回のみ</span>
        <h2 class="p-benefit__title">ヤマキシお買物券<br><em>500円分</em>プレゼント</h2>
        <p class="p-benefit__text">
          ネットからご予約のうえ、はじめてご来店いただいた方に、
          ホームセンターヤマキシでお使いいただけるお買物券をお渡しします。
        </p>
        <ul class="p-benefit__notes">
          <li>お一人さま1回限りです。2回目以降のご来店は対象外となります。</li>
          <li>ご来店時に、予約された方のお名前をお伝えください。</li>
          <li>お電話でのご予約は対象外です。このページからのご予約が対象になります。</li>
        </ul>
      </div>
      <figure class="p-benefit__fig">
        <picture>
          <source srcset="<?php echo $dir; ?>/assets/img/common/coupon-500.webp" type="image/webp">
          <img src="<?php echo $dir; ?>/assets/img/common/coupon-500.jpg" width="900" height="454"
               alt="ホームセンターヤマキシのお買物券 金500円券" loading="lazy" decoding="async">
        </picture>
      </figure>
    </div>
  </div>
</section>

<!-- =========== 来店予約の流れ ===========
     ★お問い合わせページ（/inquiry/）と同じく、矢印でつないでいます。 -->
<section class="l-section l-section--soft">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">FLOW</span>
      <h2 class="c-head__title">来店予約の<span class="marker">流れ</span></h2>
    </div>

    <ol class="p-inq__steps p-inq__steps--5">
      <?php foreach ( $steps as $i => $s ) : ?>
        <li class="p-inq__step<?php echo ! empty( $s['gift'] ) ? ' p-inq__step--gift' : ''; ?>">
          <span class="p-inq__stepno"><?php echo (int) ( $i + 1 ); ?></span>
          <h3 class="p-inq__stepttl"><?php echo esc_html( $s['ttl'] ); ?></h3>
          <p class="p-inq__steptxt"><?php echo esc_html( $s['txt'] ); ?></p>
          <?php if ( ! empty( $s['sub'] ) ) : ?>
            <p class="p-inq__stepsub"><?php echo esc_html( $s['sub'] ); ?></p>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ol>

    <p class="p-inq__note">
      ※お急ぎの場合は、お電話（<a href="tel:<?php echo esc_attr( $tel ); ?>" data-cta="rsv-flow"><?php echo esc_html( $tel ); ?></a>／受付 9:00〜17:00）でも承ります。<br>
      ※いただいた個人情報は、ご予約への対応以外には使いません。<a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>">プライバシーポリシー</a>
    </p>
  </div>
</section>

<!-- =========== フォーム =========== -->
<section class="l-section" id="form">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">FORM</span>
      <h2 class="c-head__title">来店予約<span class="marker">フォーム</span></h2>
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
            お電話でしたら、いますぐご予約いただけます。下のボタンからどうぞ。
          </p>
        </div>

        <?php if ( current_user_can( 'manage_options' ) ) : ?>
          <div class="p-inq__admin">
            <p><b>スタッフの方へ（この案内はログイン中だけ見えています）</b></p>
            <p>
              フォームがまだ作られていません。<br>
              管理画面 →「お問い合わせ」→「新規追加」で、タイトルを
              <b>「<?php echo esc_html( $ymkrf_cf7_title ); ?>」</b>にして、
              <code>wp/contact-form-7_webrsv.txt</code> の中身を貼り付けて保存してください。<br>
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

<!-- =========== お電話でのご予約 ===========
     ★お問い合わせページ（/inquiry/）と同じ作りです。
       店舗の情報は inc/functions-shops.php から読んでいます。 -->
<section class="l-section l-section--soft" id="tel">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">TEL</span>
      <h2 class="c-head__title">お電話でのご予約</h2>
    </div>

    <div class="p-inq__telbox">
      <a class="p-inq__tel" href="tel:<?php echo esc_attr( $tel ); ?>" data-cta="rsv-tel">
        <span class="p-inq__tellbl">フリーコール</span>
        <span class="p-inq__telnum"><?php echo esc_html( $tel ); ?></span>
        <span class="p-inq__telsub">通話無料・受付 9:00〜17:00</span>
      </a>
      <p class="p-inq__telnote">お急ぎのときは、下記からお近くの店舗をお選びいただき、お電話ください。</p>
      <p class="p-inq__telcaution">※お電話でのご予約は、お買物券の対象外となります。</p>
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
              <a class="p-inq__shoptel" href="tel:<?php echo esc_attr( $sp['tel'] ); ?>" data-cta="rsv-shop">
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
      ショールームのある店舗・地図は<a href="<?php echo esc_url( home_url( '/shops/' ) ); ?>">こちら</a>でご確認いただけます。
    </p>
  </div>
</section>

<!-- =========== ご注意事項 =========== -->
<section class="l-section p-notes">
  <div class="l-wrap l-wrap--narrow">
    <p class="p-notes__lead">
      お気軽にリフォームの専門家にご相談！<br class="sp-only">
      専門スタッフがあなたのリフォームの疑問や不安にお応えします。
    </p>
    <h2 class="p-notes__title">ご注意事項</h2>
    <ul class="p-notes__list">
      <?php foreach ( $notes as $n ) : ?>
        <li><?php echo esc_html( $n ); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

</main>

<?php /* ------------------------------------------------------------
         店舗を選ぶと、その店の営業時間に合う時間帯だけ選べるようにします。
         フォームは Contact Form 7 が出すので、ここから後付けしています。

         ★フォームタグの名前（shop / when）を変えると動かなくなります。
         ------------------------------------------------------------ */ ?>
<script>
(function () {
  var DATA = <?php echo wp_json_encode( $shop_slots ); ?>;

  var form = document.querySelector('.p-form form') || document.querySelector('.p-form');
  if (!form) return;
  var shopSel = form.querySelector('[name="shop"]');
  var whenSel = form.querySelector('[name="when"]');
  if (!shopSel || !whenSel) return;

  /* お店を選ぶ前の選択肢を、そのまま覚えておきます。
     お店を選びなおして「選んでください」に戻したときに使います。 */
  var FIRST = whenSel.options.length ? whenSel.options[0].textContent : '選んでください';
  var ANY   = 'いつでもよい';
  var DEFAULT_OPTS = [];
  for (var i = 0; i < whenSel.options.length; i++) {
    DEFAULT_OPTS.push(whenSel.options[i].textContent);
  }

  /* 営業時間のご案内を出す場所を用意します */
  var help = document.createElement('p');
  help.className = 'p-form__help p-form__help--hours';
  whenSel.parentNode.insertBefore(help, whenSel.nextSibling);

  /* 選ばれた店名（「小松店（小松市・能美市）」→「小松店」）を探します */
  function findShop(value) {
    for (var name in DATA) {
      if (Object.prototype.hasOwnProperty.call(DATA, name) && value.indexOf(name) === 0) return name;
    }
    return '';
  }

  function build(list) {
    var keep = whenSel.value;
    whenSel.innerHTML = '';
    for (var i = 0; i < list.length; i++) {
      var o = document.createElement('option');
      o.value = list[i];
      o.textContent = list[i];
      if (i === 0) o.value = '';          /* 「選んでください」は空にします */
      whenSel.appendChild(o);
    }
    /* 前に選んでいたものが残っていれば、そのままにします */
    for (var k = 0; k < whenSel.options.length; k++) {
      if (whenSel.options[k].value === keep) { whenSel.selectedIndex = k; return; }
    }
    whenSel.selectedIndex = 0;
  }

  function apply() {
    var name = findShop(shopSel.value || '');
    var info = name ? DATA[name] : null;

    if (!info) {
      build(DEFAULT_OPTS);
      help.textContent = '店舗をお選びいただくと、その店舗の営業時間に合わせて表示します。';
      return;
    }

    build([FIRST].concat(info.slots, [ANY]));
    help.textContent = name + 'の営業時間は ' + info.hours + ' です。'
                     + (info.hnote ? '（' + info.hnote + '）' : '');
  }

  shopSel.addEventListener('change', apply);
  apply();
})();
</script>

<?php get_footer();
