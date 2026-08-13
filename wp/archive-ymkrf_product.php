<?php
/**
 * archive-ymkrf_product.php ─ 商品・価格一覧（/products/）
 *
 * 置き場所： wp-content/themes/ymkrf/archive-ymkrf_product.php
 *
 * このページは「リンクの受け皿」です。
 * トップページやメニューには、まだ商品を入れていない分類
 * （エコキュート・IH・玄関ドア…）のボタンも並んでいます。
 * それらは inc/functions-product.php の ymkrf_cat_url() を通して
 * かならずこのページに来るようにしてあります。
 * ですので、ここは「どの分類があるか」がひと目で分かる形にしています。
 *
 * カードの金額は、その分類に入っている商品のいちばん安いセット価格を
 * その場で数えて出しています。商品を足せば自動で変わります。
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$asset = get_stylesheet_directory_uri();

/* 出す順番と、カードに使う写真・ひとこと説明。
   写真は トップページのパック価格と同じものを使っています。 */
$cards = array(
	'kitchen'    => array( 'ttl' => 'キッチン',            'photo' => 'price-kitchen',    'desc' => 'クリナップ・LIXIL・TOTOなど主要メーカーを、標準工事費込みで。' ),
	'bathroom'   => array( 'ttl' => 'お風呂（ユニットバス）', 'photo' => 'price-bath',      'desc' => '断熱浴槽で冬もあたたか。工期は3〜5日が目安です。' ),
	'toilet'     => array( 'ttl' => 'トイレ',              'photo' => 'price-toilet',     'desc' => '最短半日で交換完了。お掃除がラクな最新モデルにも対応します。' ),
	'lavatory'   => array( 'ttl' => '洗面化粧台',           'photo' => 'price-washstand',  'desc' => '朝の身支度がしやすく。収納が増えて、掃除もラクになります。' ),
	'boiler'     => array( 'ttl' => '給湯器・エコキュート',   'photo' => 'price-ecocute',    'desc' => 'お湯が出ない、というときもすぐお伺いします。夜間・休日のトラブルにも対応。' ),
	'outer-wall' => array( 'ttl' => '外壁・屋根',           'photo' => 'price-paint',      'desc' => '北陸の雪と雨に耐える塗料選びから。専門サイトもご用意しています。' ),
	'window'     => array( 'ttl' => '窓・玄関ドア',          'photo' => '',                 'desc' => '内窓をつけるだけでも、寒さと結露がぐんと減ります。補助金の対象です。' ),
	'interior'   => array( 'ttl' => '内装・改装',           'photo' => '',                 'desc' => 'クロス・床の張り替えから、間取りの変更まで承ります。' ),
);

/* それぞれの分類に商品が何件あるか、いちばん安いセット価格はいくらか */
$ready = array();   // 商品が入っている分類
$soon  = array();   // まだ準備中の分類

foreach ( $cards as $slug => $c ) {

	$term = get_term_by( 'slug', $slug, 'ymkrf_product_cat' );
	if ( ! $term || is_wp_error( $term ) || ! $term->count ) {
		$soon[] = $c['ttl'];
		continue;
	}

	$ids = get_posts( array(
		'post_type'      => 'ymkrf_product',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'post_status'    => 'publish',
		'tax_query'      => array( array(
			'taxonomy' => 'ymkrf_product_cat', 'field' => 'term_id', 'terms' => $term->term_id,
		) ),
	) );

	$min = 0;
	foreach ( (array) $ids as $pid ) {
		$v = (int) get_post_meta( $pid, '_ymkrf_total', true );
		if ( ! $v ) $v = (int) get_post_meta( $pid, '_ymkrf_work', true )
		                + (int) get_post_meta( $pid, '_ymkrf_item', true );
		if ( $v && ( ! $min || $v < $min ) ) $min = $v;
	}

	$c['slug']  = $slug;
	$c['count'] = count( (array) $ids );
	$c['min']   = $min;
	$ready[]    = $c;
}

get_header();
?>

<!-- =========== パンくず =========== -->
<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li>商品・価格一覧</li>
  </ol>
</nav>

<main id="main">

<!-- =========== ページ見出し =========== -->
<div class="p-pagehead">
  <div class="l-wrap p-pagehead__inner">
    <span class="p-pagehead__en">PRODUCTS</span>
    <h1 class="p-pagehead__title">商品・価格一覧</h1>
    <p class="p-pagehead__lead">
      本体・標準工事費・古い設備の撤去・処分費・保証まで<br class="xs-only">すべて込みの安心パック価格！<br>
      追加の工事が必要なときは、着工前にかならずお見積りをお出しします。
    </p>
  </div>
</div>

<section class="l-section">
  <div class="l-wrap">

    <?php if ( $ready ) : ?>
      <div class="p-price__grid">

        <!-- 水まわり4点パックは分類ではなく専用ページなので、ここだけ手書きです -->
        <div class="p-price__card p-price__card--feature" data-reveal>
          <p class="p-price__badge">WEB限定・いちばん人気</p>
          <div class="p-price__photo">
            <picture>
              <source srcset="<?php echo $asset; ?>/assets/img/top/price-pack4.webp" type="image/webp">
              <img src="<?php echo $asset; ?>/assets/img/top/price-pack4.jpg" width="1200" height="900"
                   alt="水まわり4点パック（キッチン・お風呂・洗面化粧台・トイレ）" loading="lazy" decoding="async">
            </picture>
          </div>
          <div class="p-price__body">
            <h2 class="p-price__name">水まわり4点パック</h2>
            <p class="p-price__desc">キッチン・お風呂・洗面化粧台・トイレをまとめて。別々に頼むよりぐんとおトクです。</p>
            <p class="p-price__yen"><span class="above above--all"><i class="fuki">工事費も<br>処分費も</i><b>全部コミコミ!!</b></span><span class="p-price__amount"><span class="num">162<span class="dec">.8</span></span><span class="unit">万円〜<small class="tax">（税込）</small></span></span></p>
            <a class="p-price__link" href="<?php echo esc_url( ymkrf_cat_url( 'pack4' ) ); ?>">4つのプランを見る</a>
          </div>
        </div>

        <?php foreach ( $ready as $i => $c ) : ?>
          <div class="p-price__card" data-reveal<?php echo $i % 3 ? ' data-reveal-delay="' . ( ( $i % 3 ) * 80 ) . '"' : ''; ?>>

            <div class="p-price__photo">
              <?php if ( $c['photo'] ) : ?>
                <picture>
                  <source srcset="<?php echo esc_url( $asset . '/assets/img/top/' . $c['photo'] . '.webp' ); ?>" type="image/webp">
                  <img src="<?php echo esc_url( $asset . '/assets/img/top/' . $c['photo'] . '.jpg' ); ?>"
                       width="1200" height="900"
                       alt="<?php echo esc_attr( $c['ttl'] ); ?>のリフォーム" loading="lazy" decoding="async">
                </picture>
              <?php else : ?>
                ［写真］<?php echo esc_html( $c['ttl'] ); ?>
              <?php endif; ?>
            </div>

            <div class="p-price__body">
              <h2 class="p-price__name"><?php echo esc_html( $c['ttl'] ); ?></h2>
              <p class="p-price__desc"><?php echo esc_html( $c['desc'] ); ?></p>

              <?php if ( $c['min'] ) : ?>
                <?php
                /* 円 → 「◯◯.◯万円」。トップページと同じ見た目にそろえます。
                   10000で割り切れるときは小数点以下を出しません。 */
                $man  = $c['min'] / 10000;
                $intp = (int) floor( $man );
                $dec  = rtrim( rtrim( number_format( $man - $intp, 2, '.', '' ), '0' ), '.' );
                $dec  = ( $dec === '0' || $dec === '' ) ? '' : substr( $dec, 1 );  // 「.8」の形
                ?>
                <p class="p-price__yen">
                  <span class="above above--all"><i class="fuki">工事費も<br>処分費も</i><b>全部コミコミ!!</b></span>
                  <span class="p-price__amount">
                    <span class="num"><?php echo esc_html( $intp ); ?><?php
                      if ( $dec ) echo '<span class="dec">' . esc_html( $dec ) . '</span>'; ?></span>
                    <span class="unit">万円〜<small class="tax">（税込）</small></span>
                  </span>
                </p>
              <?php endif; ?>

              <a class="p-price__link" href="<?php echo esc_url( ymkrf_cat_url( $c['slug'] ) ); ?>">
                <?php echo esc_html( $c['ttl'] ); ?>の商品を見る（<?php echo (int) $c['count']; ?>機種）
              </a>
            </div>

          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ( $soon ) : ?>
      <div class="p-cat__group" style="margin-top:48px">
        <h2 class="p-cat__groupttl">ただいま準備中</h2>
        <p class="p-cat__groupsub">
          <?php echo esc_html( implode( '／', $soon ) ); ?> は、ページを準備しています。<br>
          お急ぎの方は、LINEかお電話でお気軽にお問い合わせください。すぐにお見積りいたします。
        </p>
      </div>
    <?php endif; ?>

  </div>
</section>

<!-- =========== 最後のご案内 =========== -->
<section class="l-section l-section--soft">
  <div class="l-wrap">
    <div class="p-pagecta">
      <img class="p-pagecta__chara"
           src="<?php echo esc_url( $asset . '/assets/img/character/char-search-fly.webp' ); ?>"
           width="480" height="480" alt="" loading="lazy">
      <h2 class="p-pagecta__title"><span class="marker">まずは、現物を見に<br class="xs-only">いらしてください</span></h2>
      <p class="p-pagecta__text">
        ショールームには北陸最大級の<br class="xs-only">住宅設備を展示しています。<br>
        見積り・現地調査は無料。<br class="xs-only">しつこい営業はいたしません。
      </p>
      <?php ymkrf_product_cta( 'products-archive', true ); ?>
    </div>
  </div>
</section>

</main>

<?php get_footer();
