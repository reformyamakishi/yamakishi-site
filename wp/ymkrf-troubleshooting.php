<?php
/**
 * ymkrf-troubleshooting.php ─ エラーコード一覧（/troubleshooting/）
 *
 * 置き場所： wp-content/themes/ymkrf/ymkrf-troubleshooting.php
 *
 * 1枚で3つの階層をまかないます。
 *   /troubleshooting/                        … 入口（給湯器の種類をえらぶ）
 *   /troubleshooting/<種類>-error/            … メーカーをえらぶ
 *   /troubleshooting/<種類>-error/<メーカー>/  … エラーコードの一覧
 *
 * ★エラーコードの中身は、この下の $data の場所にある PHP ファイルです。
 *   wp-content/themes/ymkrf/troubleshooting/<種類>-<メーカー>.php
 *   中身は、次の形の配列を return するだけのファイルです。
 *
 *     <?php return array(
 *       array(
 *         'code'  => '102',
 *         'title' => 'わき上げ不良',
 *         'cause' => "ヒートポンプユニットの部品不具合によるエラー（圧力センサ異常）",
 *         'fix'   => "①要点検。給水配管専用止水栓を閉じ、点検・修理を依頼。\n②エラー解除方法は…",
 *       ),
 *       …
 *     );
 *
 *   ファイルがまだ無いメーカーは、一覧のかわりに
 *   「ただいま準備中です」とご案内を出します。
 *
 * ※ お客様がご自分で直せるかどうかの判断は、機種によって変わります。
 *   「必ず直ります」といった言い切りは書かないでください。
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$asset = get_stylesheet_directory_uri();

/* ------------------------------------------------------------
   給湯器の種類と、そのメーカー

   ★メーカーを足すときは、ここに1行足して、
     troubleshooting/<種類>-<メーカー>.php を置いてください。
   ------------------------------------------------------------ */
$ymkrf_ts_cats = array(

	'ecocute' => array(
		'name'   => 'エコキュート',
		'lead'   => '空気の熱でお湯をわかす、電気の給湯器です。'
		          . 'タンクとヒートポンプの2つで1組になっています。',
		'makers' => array(
			'mitsubishi' => '三菱電機',
			'panasonic'  => 'パナソニック',
			'daikin'     => 'ダイキン',
			'hitachi'    => '日立',
		),
	),

	'gas' => array(
		'name'   => 'ガス給湯器',
		'lead'   => '都市ガス・プロパンガスをお使いのお宅の給湯器です。'
		          . '家の外の壁に掛けて取り付けます。',
		'makers' => array(
			'noritz' => 'ノーリツ',
			'rinnai' => 'リンナイ',
			'paloma' => 'パロマ',
		),
	),

	'oil' => array(
		'name'   => '石油給湯器',
		'lead'   => '灯油をお使いのお宅の給湯器です。家の外に置いて取り付けます。',
		'makers' => array(
			'noritz' => 'ノーリツ',
			'chofu'  => '長府製作所',
			'corona' => 'コロナ',
		),
	),

	'electric' => array(
		'name'   => '電気温水器',
		'lead'   => 'タンクの中のヒーターでお湯をわかすタイプです。'
		          . 'エコキュートとは別のものです。',
		'makers' => array(
			'mitsubishi'     => '三菱電機',
			'panasonic'      => 'パナソニック',
			'hitachi'        => '日立',
			'chofu'          => '長府製作所',
			'corona'         => 'コロナ',
			'takarastandard' => 'タカラスタンダード',
		),
	),
);

/* いま開いているのは、どの階層か */
$ts_cat   = (string) get_query_var( 'ymkrf_ts_cat' );
$ts_maker = (string) get_query_var( 'ymkrf_ts_maker' );

if ( $ts_cat !== '' && ! isset( $ymkrf_ts_cats[ $ts_cat ] ) ) { $ts_cat = ''; $ts_maker = ''; }
if ( $ts_maker !== '' && ! isset( $ymkrf_ts_cats[ $ts_cat ]['makers'][ $ts_maker ] ) ) $ts_maker = '';

$ts_url = function ( $cat = '', $maker = '' ) {
	$u = home_url( '/troubleshooting/' );
	if ( $cat )   $u .= $cat . '-error/';
	if ( $maker ) $u .= $maker . '/';
	return $u;
};

/* エラーコードの中身を読みます。無ければ空の配列です。 */
$ts_rows = array();
if ( $ts_cat && $ts_maker ) {
	$file = get_stylesheet_directory() . '/troubleshooting/' . $ts_cat . '-' . $ts_maker . '.php';
	if ( file_exists( $file ) ) {
		$loaded = include $file;
		if ( is_array( $loaded ) ) $ts_rows = $loaded;
	}
}

get_header();
?>

<!-- =========== パンくず =========== -->
<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <?php if ( $ts_cat ) : ?>
      <li><a href="<?php echo esc_url( $ts_url() ); ?>">エラーコード一覧</a></li>
      <?php if ( $ts_maker ) : ?>
        <li><a href="<?php echo esc_url( $ts_url( $ts_cat ) ); ?>"><?php echo esc_html( $ymkrf_ts_cats[ $ts_cat ]['name'] ); ?></a></li>
        <li><?php echo esc_html( $ymkrf_ts_cats[ $ts_cat ]['makers'][ $ts_maker ] ); ?></li>
      <?php else : ?>
        <li><?php echo esc_html( $ymkrf_ts_cats[ $ts_cat ]['name'] ); ?></li>
      <?php endif; ?>
    <?php else : ?>
      <li>エラーコード一覧</li>
    <?php endif; ?>
  </ol>
</nav>

<main id="main">

<!-- =========== 見出し =========== -->
<section class="p-guide__hero">
  <div class="l-wrap">
    <span class="c-head__en">ERROR CODE</span>
    <?php if ( $ts_maker ) : ?>
      <?php /* どの機器のエラーコードなのかが、ひと目で分かるようにします。
               「日立のエラーコード」だけだと、エアコンなのか給湯器なのか伝わりません。 */ ?>
      <p class="p-ts__device"><?php echo esc_html( $ymkrf_ts_cats[ $ts_cat ]['name'] ); ?></p>
      <h1 class="p-guide__title">
        <?php echo esc_html( $ymkrf_ts_cats[ $ts_cat ]['makers'][ $ts_maker ] ); ?>の<br class="sp-only">エラーコード一覧
      </h1>
    <?php elseif ( $ts_cat ) : ?>
      <p class="p-ts__device"><?php echo esc_html( $ymkrf_ts_cats[ $ts_cat ]['name'] ); ?></p>
      <h1 class="p-guide__title">エラーコード一覧</h1>
      <p class="p-guide__lead">メーカーをお選びください。</p>
    <?php else : ?>
      <h1 class="p-guide__title">エラーコード一覧</h1>
      <p class="p-guide__lead">
        故障かな？！　修理をご依頼になる前に。<br class="pc-only">
        よくあるエラー表示について、その原因と対処方法をご案内します。<br>
        まずはお家の給湯器の種類をお選びください。
      </p>
    <?php endif; ?>
    <img class="p-guide__herochara" src="<?php echo $asset; ?>/assets/img/character/char-wrench.webp"
         width="592" height="640"
         alt="ヤマキシのキャラクター「とんとこトン」がレンチを持っているイラスト"
         fetchpriority="high">
  </div>
</section>

<?php if ( ! $ts_cat ) : ?>
<!-- =========== 入口：種類をえらぶ =========== -->
<section class="l-section">
  <div class="l-wrap">
    <div class="p-ts__cats">
      <?php foreach ( $ymkrf_ts_cats as $ck => $cv ) : ?>
        <a class="p-ts__cat" href="<?php echo esc_url( $ts_url( $ck ) ); ?>">
          <span class="p-ts__catttl"><?php echo esc_html( $cv['name'] ); ?></span>
          <span class="p-ts__catlead"><?php echo esc_html( $cv['lead'] ); ?></span>
          <span class="p-ts__catlink"><?php echo esc_html( $cv['name'] ); ?>のエラーコード一覧</span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php elseif ( ! $ts_maker ) : ?>
<!-- =========== 種類：メーカーをえらぶ =========== -->
<section class="l-section">
  <div class="l-wrap">
    <div class="p-ts__makers">
      <?php foreach ( $ymkrf_ts_cats[ $ts_cat ]['makers'] as $mk => $mn ) : ?>
        <a class="p-ts__maker" href="<?php echo esc_url( $ts_url( $ts_cat, $mk ) ); ?>">
          <?php echo esc_html( $mn ); ?>のエラーコード一覧
        </a>
      <?php endforeach; ?>
    </div>
    <p class="p-guide__note">
      <a href="<?php echo esc_url( $ts_url() ); ?>">← 給湯器の種類をえらびなおす</a>
    </p>
  </div>
</section>

<?php else : ?>
<!-- =========== メーカー：エラーコードの表 =========== -->
<section class="l-section">
  <div class="l-wrap">

    <?php if ( $ts_rows ) : ?>

      <p class="p-ts__count">
        <?php echo esc_html( number_format( count( $ts_rows ) ) ); ?>件のエラーコードを載せています。
      </p>

      <div class="p-ts__table">
        <div class="p-ts__thead">
          <span>コード</span><span>内容・原因</span><span>対処方法</span>
        </div>
        <?php foreach ( $ts_rows as $r ) : ?>
          <div class="p-ts__row" id="code-<?php echo esc_attr( sanitize_title( $r['code'] ) ); ?>">
            <div class="p-ts__code"><?php echo esc_html( $r['code'] ); ?></div>
            <div class="p-ts__cause">
              <?php if ( ! empty( $r['title'] ) ) : ?>
                <p class="p-ts__ttl"><?php echo esc_html( $r['title'] ); ?></p>
              <?php endif; ?>
              <?php if ( ! empty( $r['cause'] ) ) : ?>
                <p class="p-ts__lbl">原因</p>
                <p class="p-ts__txt"><?php echo nl2br( esc_html( $r['cause'] ) ); ?></p>
              <?php endif; ?>
            </div>
            <div class="p-ts__fix">
              <?php if ( ! empty( $r['fix'] ) ) : ?>
                <p class="p-ts__lbl p-ts__lbl--fix">対処方法</p>
                <p class="p-ts__txt"><?php echo nl2br( esc_html( $r['fix'] ) ); ?></p>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    <?php else : ?>

      <div class="p-ts__soon">
        <p class="p-ts__soonttl">ただいま準備中です</p>
        <p>
          <?php echo esc_html( $ymkrf_ts_cats[ $ts_cat ]['makers'][ $ts_maker ] ); ?>の
          <?php echo esc_html( $ymkrf_ts_cats[ $ts_cat ]['name'] ); ?>のエラーコード一覧は、
          ただいま準備しています。<br>
          お急ぎのときは、お近くの店舗までお電話ください。エラーの数字をお伝えいただければお調べします。
        </p>
      </div>

    <?php endif; ?>

    <p class="p-guide__note">
      ※ エラーの内容は、機種や年式によって変わることがあります。<br>
      エラーが消えないとき、水がもれているとき、こげたようなにおいがするときは、
      お使いにならずにご連絡ください。お近くの店舗からうかがいます。
    </p>

    <div class="p-ts__nav">
      <a href="<?php echo esc_url( $ts_url( $ts_cat ) ); ?>">← ほかのメーカーを見る</a>
      <a href="<?php echo esc_url( $ts_url() ); ?>">エラーコード一覧のトップへ</a>
    </div>

  </div>
</section>

<?php endif; ?>

</main>

<?php get_footer(); ?>
