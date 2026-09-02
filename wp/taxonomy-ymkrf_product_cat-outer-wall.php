<?php
/**
 * taxonomy-ymkrf_product_cat-outer-wall.php ─ 外壁・屋根（/products/outer-wall/）
 *
 * 置き場所： wp-content/themes/ymkrf/taxonomy-ymkrf_product_cat-outer-wall.php
 *
 * ★このファイル名は WordPress の決まりごとです。
 *   「taxonomy-（分類名）-（スラッグ）.php」という名前にしておくと、
 *   その分類のページだけ、この1枚が使われます。
 *   ですので、URLの設定（functions）を足す必要はありません。
 *
 * ── なぜ、ほかの分類とちがう作りにしているか ─────────────────
 * 外壁・屋根は「ヤマキシペイント（外壁・屋根サポートサイト）」という
 * 専門のサイトが別にあります。
 * 同じ内容を2つのサイトに置くと、検索エンジンがどちらを見せればよいか
 * 決められず、両方とも順位が上がりにくくなります。
 *
 * そこで、このページの役目は次の2つだけにしています。
 *   ① キッチンや給湯器を見に来た方に「外壁もヤマキシでできる」と知っていただく
 *   ② くわしく知りたい方を、専門サイトへお送りする
 *
 * ですので、塗料ごとの料金表・工事の流れ・よくあるご質問といった
 * 「くわしい話」は、ここには書かないでください。専門サイトの担当です。
 * ─────────────────────────────────────────────────
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$dir = get_stylesheet_directory_uri();

/* 外壁・屋根サポートサイト（ヤマキシペイント）。
   URLが変わったときは、ここ1か所を直せば、このページ全部に反映されます。 */
$paint_url = 'https://yamakishi-paint.jp/';

/* ------------------------------------------------------------
   外壁のチェック
   lv … 劣化度（1〜5）。数が大きいほど、急いだほうがよい状態です。
   ------------------------------------------------------------ */
$wall_checks = array(
	array(
		'img'  => 'wall1-fade',
		'name' => '色あせ・くすみ',
		'lv'   => 1,
		'alt'  => '外壁全体の色があせて、くすんでしまっている家',
		'text' => '新築のころとくらべて、壁の色がぼんやりしてきた。'
		        . '日ざしで塗膜が弱りはじめたサインです。'
		        . 'すぐに困ることはありませんが、ここから先へ進んでいきます。',
	),
	array(
		'img'  => 'wall2-chalking',
		'name' => 'チョーキング',
		'lv'   => 2,
		'alt'  => '外壁を手でこすると白い粉がつく、チョーキングの状態',
		'text' => '壁を手でこすると、白い粉が手につく。'
		        . '塗膜が粉になって浮いてきた状態です。'
		        . '雨のたびに、塗料が少しずつ流れ出しています。',
	),
	array(
		'img'  => 'wall3-sealing',
		'name' => 'シーリングの傷み',
		'lv'   => 3,
		'alt'  => '外壁のつなぎ目のシーリングが固くなり、切れている状態',
		'text' => '壁と壁のつなぎ目のゴムが、かたくなったり、切れたりしている。'
		        . 'ここは雨の入り口になります。壁そのものより先に傷みます。',
	),
	array(
		'img'  => 'wall4-moss',
		'name' => 'カビ・苔・藻',
		'lv'   => 4,
		'alt'  => '外壁に苔や藻がついて緑色になっている状態',
		'text' => '北側や日かげの壁が、緑っぽく・黒っぽくなっている。'
		        . '壁が水をふくむようになったサインです。'
		        . '洗っても、またすぐに出てくるようなら塗り替えどきです。',
	),
	array(
		'img'  => 'wall5-crack',
		'name' => 'ひび割れ・塗装の剥げ',
		'lv'   => 5,
		'alt'  => '外壁にひび割れが入り、塗装が剥がれている状態',
		'text' => '壁が割れている、塗装がめくれている。'
		        . 'ここまで来ると、塗るだけでは済みません。'
		        . '下地の補修から必要になり、工事も大きくなります。',
	),
	array(
		'img'  => 'wall6-rust',
		'name' => 'トタンのサビ・剥げ',
		'lv'   => 5,
		'alt'  => 'トタンの外壁や門扉にサビが出て、塗膜が剥がれている状態',
		'text' => '門扉・フェンス・シャッター・トタンの壁のサビ。'
		        . 'サビは塗膜のはがれを広げながら、下地まで進んでいきます。'
		        . '5〜7年に一度の塗り替えをおすすめしています。',
	),
);

/* ------------------------------------------------------------
   屋根のチェック
   ------------------------------------------------------------ */
$roof_checks = array(
	array(
		'img'  => 'roof1-rust',
		'name' => 'サビが出ている',
		'sub'  => 'トタン屋根',
		'lv'   => 3,
		'alt'  => 'トタン屋根の塗装面がサビてぼろぼろになっている状態',
		'text' => '塗装面がぼろぼろになっています。'
		        . 'そのままにしておくと、雨もりにつながることがあります。',
	),
	array(
		'img'  => 'roof2-fade',
		'name' => 'かなり色が褪せている',
		'sub'  => 'スレート瓦',
		'lv'   => 3,
		'alt'  => 'スレート瓦の色が褪せて白っぽくなっている屋根',
		'text' => '色が抜けて、白っぽく見えるようになった屋根です。'
		        . '瓦そのものが水を吸いはじめています。',
	),
	array(
		'img'  => 'roof3-peel',
		'name' => '色が褪せ、塗膜が剥がれている',
		'sub'  => 'セメント瓦・乾式洋瓦',
		'lv'   => 3,
		'alt'  => 'セメント瓦の色が褪せ、塗膜が剥がれている屋根',
		'text' => '色が変わっているだけでなく、水をはじく力も落ちています。'
		        . '屋根は下から見えにくい場所です。'
		        . '足場を組むときに、いっしょに見せていただくのがいちばん確実です。',
	),
);

/* ------------------------------------------------------------
   料金表

   ★金額を直すときは、ここだけ直してください。
     数字は「万円（税込）」です。「49.8」のように書きます。
     面積の見出し（head）と、行の数字（cols）の数は
     かならず同じにしてください。ずれると表が崩れます。

   ※塗料の並びは、お安いものから順にしています。
   ------------------------------------------------------------ */
$price_tables = array(

	array(
		'ttl'  => '外壁塗装',
		'lead' => '足場・高圧洗浄・下地処理・下塗り・中塗り・上塗り・10年保証まで込みの価格です。'
		        . '（シリコンのみ保証3年になります）',
		'head' => array( '〜100㎡', '〜150㎡', '〜200㎡' ),
		'rows' => array(
			array( 'name' => 'シリコン',                   'cols' => array( '49.8', '74.7', '99.6' ),  'life' => '12〜15年' ),
			array( 'name' => 'プレミアムシリコン',         'sub' => 'ラジカル', 'cols' => array( '54.8', '82.2', '109.6' ), 'life' => '15〜17年' ),
			array( 'name' => '遮熱シリコン',               'cols' => array( '59.8', '89.7', '119.6' ), 'life' => '13〜15年' ),
			array( 'name' => '無機シリコン',               'cols' => array( '64.8', '97.2', '129.6' ), 'life' => '17〜20年' ),
			array( 'name' => 'フッ素',                     'cols' => array( '69.8', '104.7', '139.6' ), 'life' => '17〜20年' ),
			array( 'name' => '無機',                       'cols' => array( '74.8', '112.2', '149.6' ), 'life' => '20年以上' ),
		),
		'note' => array(
			'破風・軒天の塗装は、別にお見積りをお出しします。',
			'2024年4月1日の法改正で足場の基準が変わりました。上の金額は、新しい基準の足場代を含んだ価格です。',
		),
	),

	array(
		'ttl'  => '屋根塗装',
		'lead' => '高圧洗浄・下地処理・下塗り・中塗り・上塗り・3〜5年保証まで込みの価格です。',
		'head' => array( '50㎡', '100㎡', '150㎡' ),
		'rows' => array(
			array( 'name' => '遮熱シリコン', 'cols' => array( '19.8', '39.6', '59.4' ), 'life' => '13〜15年' ),
			array( 'name' => '遮熱フッ素',   'cols' => array( '29.8', '59.6', '89.4' ), 'life' => '17〜20年' ),
		),
		'note' => array(
			'保証の年数は、屋根の状態によって変わります。',
			'屋根だけを塗る場合は、足場代が別になります。',
		),
	),
);

/* 劣化度のブタさんを並べます（5つのうち、いくつ分か）

   ★絵を変えたいときは assets/img/outerwall/lv-pig.webp（と .png）を
     差し替えてください。ここを直す必要はありません。 */
$lv_html = function ( $lv ) use ( $dir ) {
	$out = '';
	for ( $i = 1; $i <= 5; $i++ ) {
		$out .= '<img class="p-ow__pig' . ( $i <= $lv ? '' : ' is-off' ) . '" src="'
		      . esc_url( $dir . '/assets/img/outerwall/lv-pig.webp' )
		      . '" width="207" height="207" alt="" loading="lazy" decoding="async">';
	}
	return $out;
};

get_header();
?>

<!-- =========== パンくず =========== -->
<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li><a href="<?php echo esc_url( ymkrf_products_url() ); ?>">商品・価格</a></li>
    <li>外壁・屋根</li>
  </ol>
</nav>

<main id="main">

<!-- =========== 見出し =========== -->
<section class="p-guide__hero">
  <div class="l-wrap">
    <span class="c-head__en">EXTERIOR</span>
    <h1 class="p-guide__title">外壁・屋根の<br class="sp-only">塗装リフォーム</h1>
    <p class="p-guide__lead">
      北陸の雨と雪、そして夏の日ざし。<br class="pc-only">
      外壁と屋根は、家のなかでいちばん天気にさらされている場所です。<br>
      「そろそろかな」と思われたら、まず見せていただくところから。<br class="pc-only">
      現地調査とお見積りは無料です。
    </p>
    <img class="p-guide__herochara" src="<?php echo $dir; ?>/assets/img/character/char-paint-wall.webp"
         width="640" height="619"
         alt="ヤマキシのキャラクター「とんとこトン」がローラーで壁を塗っているイラスト"
         fetchpriority="high">
  </div>
</section>

<!-- =========== 外壁チェック =========== -->
<section class="l-section" id="wall">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">WALL CHECK</span>
      <img class="c-head__chara c-chara--float" src="<?php echo $dir; ?>/assets/img/character/char-search.webp"
           width="592" height="640" alt="" loading="lazy" decoding="async">
      <h2 class="c-head__title">外壁から<br class="sp-only">こんな<span class="marker">サイン</span>が出ていませんか</h2>
      <p class="c-head__lead">
        下にいくほど、急いだほうがよい状態です。<br class="pc-only">
        ひとつでも当てはまるものがあれば、一度ご相談ください。
      </p>
    </div>

    <div class="p-ow__checks">
      <?php foreach ( $wall_checks as $c ) : ?>
        <div class="p-ow__check">
          <div class="p-ow__ph">
            <picture>
              <source srcset="<?php echo esc_url( $dir . '/assets/img/outerwall/' . $c['img'] . '.webp' ); ?>" type="image/webp">
              <img src="<?php echo esc_url( $dir . '/assets/img/outerwall/' . $c['img'] . '.jpg' ); ?>"
                   width="800" height="600" alt="<?php echo esc_attr( $c['alt'] ); ?>"
                   loading="lazy" decoding="async">
            </picture>
          </div>
          <div class="p-ow__body">
            <h3 class="p-ow__name"><?php echo esc_html( $c['name'] ); ?></h3>
            <p class="p-ow__lv">
              <span class="p-ow__lvlbl">劣化度</span>
              <span class="p-ow__pigs" role="img"
                    aria-label="劣化度5段階のうち<?php echo (int) $c['lv']; ?>"><?php
                echo $lv_html( $c['lv'] ) /* phpcs:ignore WordPress.Security.EscapeOutput */; ?></span>
            </p>
            <p class="p-ow__text"><?php echo ymkrf_brk( $c['text'] ) /* phpcs:ignore */; ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- =========== 屋根チェック =========== -->
<section class="l-section l-section--gray" id="roof">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">ROOF CHECK</span>
      <h2 class="c-head__title">屋根も、<br class="sp-only">いっしょに<span class="marker">見ておきたい</span>ところ</h2>
      <p class="c-head__lead">
        屋根は下から見えません。<br class="pc-only">
        外壁の足場を組むときに、あわせて見るのがいちばん無駄がありません。
      </p>
    </div>

    <div class="p-ow__checks p-ow__checks--roof">
      <?php foreach ( $roof_checks as $c ) : ?>
        <div class="p-ow__check">
          <div class="p-ow__ph">
            <picture>
              <source srcset="<?php echo esc_url( $dir . '/assets/img/outerwall/' . $c['img'] . '.webp' ); ?>" type="image/webp">
              <img src="<?php echo esc_url( $dir . '/assets/img/outerwall/' . $c['img'] . '.jpg' ); ?>"
                   width="800" height="600" alt="<?php echo esc_attr( $c['alt'] ); ?>"
                   loading="lazy" decoding="async">
            </picture>
          </div>
          <div class="p-ow__body">
            <h3 class="p-ow__name">
              <?php echo esc_html( $c['name'] ); ?>
              <span class="p-ow__sub"><?php echo esc_html( $c['sub'] ); ?></span>
            </h3>
            <p class="p-ow__lv">
              <span class="p-ow__lvlbl">劣化度</span>
              <span class="p-ow__pigs" role="img"
                    aria-label="劣化度5段階のうち<?php echo (int) $c['lv']; ?>"><?php
                echo $lv_html( $c['lv'] ) /* phpcs:ignore WordPress.Security.EscapeOutput */; ?></span>
            </p>
            <p class="p-ow__text"><?php echo ymkrf_brk( $c['text'] ) /* phpcs:ignore */; ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- =========== 料金表 ===========
     ★スマホでは、表が横スクロールにならないよう、
       1行ずつのカードに組み替わります（CSSで切り替えています）。
       ですので、セルには data-lbl（見出しの控え）を付けてください。 -->
<section class="l-section" id="price">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">PRICE</span>
      <h2 class="c-head__title">外壁・屋根塗装の<span class="marker">価格</span></h2>
      <p class="c-head__lead">
        すべて税込・コミコミの価格です。<br class="pc-only">
        塗る面積と、塗料の種類でお値段が変わります。
      </p>
    </div>

    <?php foreach ( $price_tables as $pt ) : ?>
      <div class="p-ow__pricebox">
        <h3 class="p-ow__pricettl"><?php echo esc_html( $pt['ttl'] ); ?></h3>
        <p class="p-ow__pricelead"><?php echo esc_html( $pt['lead'] ); ?></p>

        <div class="p-ow__table">
          <div class="p-ow__thead">
            <span>塗料の種類</span>
            <?php foreach ( $pt['head'] as $h ) : ?>
              <span><?php echo esc_html( $h ); ?></span>
            <?php endforeach; ?>
            <span>もちの目安</span>
          </div>

          <?php foreach ( $pt['rows'] as $row ) : ?>
            <div class="p-ow__trow">
              <div class="p-ow__tname">
                <?php echo esc_html( $row['name'] ); ?>
                <?php if ( ! empty( $row['sub'] ) ) : ?>
                  <span class="p-ow__tsub"><?php echo esc_html( $row['sub'] ); ?></span>
                <?php endif; ?>
              </div>
              <?php foreach ( $row['cols'] as $i => $v ) : ?>
                <div class="p-ow__tcell" data-lbl="<?php echo esc_attr( $pt['head'][ $i ] ); ?>">
                  <span class="p-ow__tyen"><?php echo esc_html( $v ); ?><small>万円</small></span>
                </div>
              <?php endforeach; ?>
              <div class="p-ow__tcell p-ow__tcell--life" data-lbl="もちの目安">
                <span class="p-ow__tlife"><?php echo esc_html( $row['life'] ); ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <ul class="p-ow__pricenotes">
          <?php foreach ( $pt['note'] as $n ) : ?>
            <li><?php echo esc_html( $n ); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endforeach; ?>

    <p class="p-guide__note">
      ※上の金額は税込です。お家の形や、いまの壁・屋根の傷みぐあいによって変わることがあります。<br>
      まずは無料の現地調査で、実際に測ってからお見積りをお出しします。<br>
      追加の工事が必要なときは、着工前にかならずお見積りをお出しします。
    </p>
  </div>
</section>

<!-- =========== 専門サイトへ ===========
     このページの本命の出口です。目立たせています。 -->
<section class="l-section l-section--tint" id="paint">
  <div class="l-wrap">
    <div class="p-ow__site">
      <img class="p-ow__sitechara" src="<?php echo $dir; ?>/assets/img/character/char-roller.webp"
           width="640" height="596" alt="" loading="lazy" decoding="async">
      <p class="p-ow__siteen">YAMAKISHI PAINT</p>
      <h2 class="p-ow__sitettl">外壁・屋根の専門サイトが<br class="sp-only">あります</h2>
      <p class="p-ow__sitetext">
        工事の流れ、よくあるご質問、施工事例、対応している地域。<br>
        外壁と屋根のことは、こちらにくわしくまとめています。
      </p>
      <a class="p-ow__sitebtn" href="<?php echo esc_url( $paint_url ); ?>" target="_blank" rel="noopener">
        外壁・屋根サポートサイトを見る
        <span class="p-ow__sitebtnsub">ヤマキシペイント（別のサイトが開きます）</span>
      </a>
    </div>
  </div>
</section>

<!-- =========== まとめて頼めます ===========
     このサイトにしか書けないことです。専門サイトとかぶりません。 -->
<section class="l-section" id="together">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">TOGETHER</span>
      <img class="c-head__chara c-chara--float" src="<?php echo $dir; ?>/assets/img/character/char-anshin.webp"
           width="530" height="640" alt="" loading="lazy" decoding="async">
      <h2 class="c-head__title">家のことは、<br class="sp-only"><span class="marker">まとめて</span>ご相談ください</h2>
    </div>

    <ul class="p-ow__merits">
      <li class="p-ow__merit">
        <h3 class="p-ow__meritttl">足場は一度で済ませる</h3>
        <p>外壁のために足場を組むときに、屋根・雨どい・ベランダの防水もいっしょに。
        あとから組みなおすと、そのぶん足場代がまたかかります。</p>
      </li>
      <li class="p-ow__merit">
        <h3 class="p-ow__meritttl">中と外を、同じ担当が見ます</h3>
        <p>キッチン・お風呂・給湯器のお取り替えと、外壁・屋根。
        窓口はひとつですので、何度も同じ話をしていただかずに済みます。</p>
      </li>
      <li class="p-ow__merit">
        <h3 class="p-ow__meritttl">近くの店舗がうかがいます</h3>
        <p>石川・福井に9店舗。工事のあとも、気になることがあればすぐに見にまいります。</p>
      </li>
    </ul>
  </div>
</section>

<!-- =========== 施工事例 ===========
     ダッシュボードの「施工事例」で、部位に「外壁・屋根」を付けた記事が
     ここに新しい順で3件出ます。1件も無いときは、このかたまりごと出ません。 -->
<?php
if ( function_exists( 'ymkrf_works_section' ) ) {
	ymkrf_works_section( 'outer-wall', '外壁・屋根', 3 );
}
?>

<!-- =========== 最後のご案内 =========== -->
<section class="l-section l-section--soft">
  <div class="l-wrap">
    <div class="p-pagecta">
      <img class="p-pagecta__chara"
           src="<?php echo esc_url( $dir . '/assets/img/character/char-search-fly.webp' ); ?>"
           width="480" height="480" alt="" loading="lazy">
      <h2 class="p-pagecta__title"><span class="marker">まずは、見せて<br class="xs-only">いただくところから</span></h2>
      <p class="p-pagecta__text">
        現地調査とお見積りは無料です。<br class="xs-only">しつこい営業はいたしません。<br>
        他社さんとのお見積り比べに<br class="xs-only">お使いいただくのも大歓迎です。
      </p>
      <?php ymkrf_product_cta( 'outerwall-bottom', true ); ?>
    </div>
  </div>
</section>

</main>

<?php get_footer();
