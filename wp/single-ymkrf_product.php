<?php
/**
 * 商品詳細ページ ─ リフォームヤマキシ
 *
 * 置き場所： wp-content/themes/ymkrf-child/single-ymkrf_product.php
 *
 * 管理画面「商品」で入力した内容が、ここに流し込まれます。
 * 見た目・並び順・構造化データはすべてこのファイルが決めるので、
 * 入力する人はデザインを気にする必要がありません。
 *
 * 入力が空の項目は、見出しごと自動で消えます。
 * （例：お風呂やトイレで「取っ手」を使わない場合、その欄は出ません）
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/** 写真を出す小さな道具。IDが空なら何も出しません */
if ( ! function_exists( 'ymkrf_img' ) ) :
function ymkrf_img( $id, $size = 'large', $alt = '', $attr = array() ) {
	$id = (int) $id;
	if ( ! $id ) return '';
	if ( $alt !== '' ) $attr['alt'] = $alt;
	if ( ! isset( $attr['loading'] ) ) $attr['loading'] = 'lazy';
	return wp_get_attachment_image( $id, $size, false, $attr );
}
endif;

get_header();

while ( have_posts() ) : the_post();

$d     = ymkrf_product_data();
$sib   = ymkrf_product_siblings();
$works = ymkrf_product_works( get_the_ID(), 3 );
$cat   = ! empty( $d['cats'] ) ? $d['cats'][0] : null;
$maker = ! empty( $d['makers'] ) ? $d['makers'][0] : null;

?>

<!-- =========== パンくず =========== -->
<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li><a href="<?php echo esc_url( home_url( '/products/' ) ); ?>">商品・価格</a></li>
    <?php if ( $cat ) : ?>
      <li><a href="<?php echo esc_url( get_term_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a></li>
    <?php endif; ?>
    <li><?php echo esc_html( ( $d['grade'] ? '【' . $d['grade'] . '】' : '' ) . $d['name'] ); ?></li>
  </ol>
</nav>

<main id="main">

<!-- =========== 商品ヘッダー =========== -->
<div class="p-prd__head">
  <div class="l-wrap">
    <?php if ( $d['catch'] ) : ?>
      <p class="p-prd__catch"><?php echo esc_html( $d['catch'] ); ?></p>
    <?php endif; ?>

    <h1 class="p-prd__title">
      <?php if ( $d['grade'] ) : ?><span class="p-prd__grade">【<?php echo esc_html( $d['grade'] ); ?>】</span><?php endif; ?>
      <?php echo esc_html( $d['name'] ); ?>
    </h1>

    <div class="p-prd__meta">
      <?php if ( $maker ) echo ymkrf_maker_logo( $maker, 'p-prd__makerlogo' ); /* phpcs:ignore */ ?>
      <?php if ( $d['size'] ) : ?><span class="p-prd__size"><?php echo esc_html( $d['size'] ); ?></span><?php endif; ?>
      <?php if ( $d['days'] ) : ?>
        <span class="p-prd__days">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
          <span class="lbl">工期</span><span class="num"><?php echo esc_html( $d['days'] ); ?></span><span class="unit">日</span>
        </span>
      <?php endif; ?>
    </div>

    <?php if ( $d['points'] ) : ?>
      <ul class="p-prd__points">
        <?php
        /* 特徴3点のアイコン。1つめ＝円マーク、2つめ＝収納、3つめ＝キラキラ */
        $icons = array(
          '<path d="M7 4l5 8 5-8M12 12v8M7.5 13h9M7.5 17h9"/>',
          '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M10 9v6M10 3v6"/>',
          '<path d="M12 3l1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6z"/><path d="M18.5 15.5l.7 1.9 1.9.7-1.9.7-.7 1.9-.7-1.9-1.9-.7 1.9-.7zM5.5 15.5l.6 1.5 1.5.6-1.5.6-.6 1.5-.6-1.5L3.4 18l1.5-.6z"/>',
        );
        foreach ( $d['points'] as $i => $pt ) : ?>
          <li>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?php echo $icons[ $i % 3 ]; ?></svg>
            <?php echo esc_html( $pt ); ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

<!-- =========== 価格・写真 =========== -->
<section class="l-section">
  <div class="l-wrap">
    <?php if ( $d['total'] ) : ?>
      <div class="p-prd__pricebox">
        <div class="p-prd__pricemain">
          <span class="p-prd__pricelabel">セット価格</span>
          <span class="p-prd__priceline">
            <span class="p-prd__price"><?php echo esc_html( number_format( $d['total'] ) ); ?><span class="yen">円</span></span>
            <span class="p-prd__tax">（税込）</span>
          </span>
        </div>
        <?php if ( $d['work'] && $d['item'] ) : ?>
          <div class="p-prd__breakdown">
            <span><em>標準工事費</em><?php echo esc_html( number_format( $d['work'] ) ); ?>円</span>
            <span aria-hidden="true">＋</span>
            <span><em>商品代</em><?php echo esc_html( number_format( $d['item'] ) ); ?>円</span>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ( has_post_thumbnail() ) : ?>
      <div class="p-prd__photo">
        <?php the_post_thumbnail( 'large', array(
          'alt'           => esc_attr( trim( ( $maker ? $maker->name . ' ' : '' ) . $d['name'] . ' ' . $d['size'] ) ),
          'fetchpriority' => 'high',
        ) ); ?>
      </div>
    <?php endif; ?>

    <?php if ( $d['shops'] ) : ?>
      <p class="p-prd__shops"><b>展示店舗</b>：<?php
        $links = array();
        foreach ( $d['shops'] as $s ) {
          $links[] = '<a href="' . esc_url( get_term_link( $s ) ) . '">' . esc_html( $s->name ) . '</a>';
        }
        echo implode( '・', $links );
      ?></p>
    <?php endif; ?>

    <?php if ( $d['caution'] ) : ?>
      <p class="p-prd__caution"><?php echo esc_html( $d['caution'] ); ?></p>
    <?php endif; ?>

    <?php ymkrf_product_cta( 'product' ); ?>
  </div>
</section>

<?php if ( ! empty( $d['images'] ) || ! empty( $d['colors'] ) || ! empty( $d['tops'] ) || ! empty( $d['sinks'] ) || ! empty( $d['handles'] ) ) : ?>
<!-- =========== カラーバリエーション =========== -->
<section class="l-section l-section--soft">
  <div class="l-wrap">
    <h2 class="p-prd__bar">カラーバリエーション</h2>

    <?php if ( $d['images'] ) : ?>
      <figure class="p-prd__hero">
        <div class="p-prd__hero__grid">
          <?php foreach ( $d['images'] as $r ) echo ymkrf_img( $r['img'], 'medium_large', $r['alt'] ); ?>
        </div>
        <figcaption>
          <strong><?php
            /* 取っ手が1種類しかない商品では「組み合わせ」と書くと事実に合わないので、文を変えます */
            echo ( count( $d['handles'] ) > 1 )
              ? '扉の色と取っ手の組み合わせで、キッチンの雰囲気はぐっと変わります。'
              : '扉の色で、キッチンの雰囲気はぐっと変わります。';
          ?></strong><small>※画像はイメージです</small>
        </figcaption>
      </figure>
    <?php endif; ?>

    <?php if ( $d['colors'] ) : ?>
      <p class="p-prd__sub">扉カラー（全<?php echo count( $d['colors'] ); ?>色）<small class="p-prd__note">※下記カラー以外選択の場合はオプションとなります</small></p>
      <div class="p-prd__colors">
        <?php foreach ( $d['colors'] as $r ) : ?>
          <figure>
            <div class="p-prd__swatch"><?php echo ymkrf_img( $r['img'], 'medium', '扉カラー ' . $r['name'] ); ?></div>
            <figcaption><?php echo esc_html( $r['name'] ); ?></figcaption>
          </figure>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ( $d['tops'] ) : ?>
      <p class="p-prd__sub" style="margin-top:26px">天板カラー（全<?php echo count( $d['tops'] ); ?>色）</p>
      <div class="p-prd__colors">
        <?php foreach ( $d['tops'] as $r ) : ?>
          <figure>
            <div class="p-prd__swatch"><?php echo ymkrf_img( $r['img'], 'medium', '天板カラー ' . $r['name'] ); ?></div>
            <figcaption><?php echo esc_html( $r['name'] ); ?></figcaption>
          </figure>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ( ! empty( $d['sinks'] ) ) : ?>
      <p class="p-prd__sub" style="margin-top:26px">シンクカラー（全<?php echo count( $d['sinks'] ); ?>色）</p>
      <div class="p-prd__colors">
        <?php foreach ( $d['sinks'] as $r ) : ?>
          <figure>
            <div class="p-prd__swatch"><?php echo ymkrf_img( $r['img'], 'medium', 'シンクカラー ' . $r['name'] ); ?></div>
            <figcaption><?php echo esc_html( $r['name'] ); ?></figcaption>
          </figure>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ( $d['handles'] ) : ?>
      <p class="p-prd__sub" style="margin-top:26px">取っ手（全<?php echo count( $d['handles'] ); ?>種）</p>
      <div class="p-prd__handles">
        <?php foreach ( $d['handles'] as $r ) : ?>
          <figure>
            <div class="ph"><?php echo ymkrf_img( $r['img'], 'medium', trim( $r['name'] . ' ' . $r['code'] ) ); ?></div>
            <figcaption><?php echo esc_html( $r['name'] ); ?><?php
              if ( $r['code'] ) echo '<span class="p-prd__code">' . esc_html( $r['code'] ) . '</span>';
            ?></figcaption>
          </figure>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if ( $d['specs'] ) : ?>
<!-- =========== 標準仕様 =========== -->
<section class="l-section">
  <div class="l-wrap">
    <h2 class="p-prd__bar">標準仕様</h2>
    <div class="p-prd__specs">
      <?php foreach ( $d['specs'] as $r ) : ?>
        <figure class="p-prd__spec">
          <div class="ph"><?php echo ymkrf_img( $r['img'], 'medium', $r['name'] ); ?></div>
          <figcaption><?php echo esc_html( $r['name'] );
            if ( $r['model'] ) echo '<small>' . esc_html( $r['model'] ) . '</small>';
          ?></figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ( $d['features'] ) : ?>
<!-- =========== おすすめポイント =========== -->
<section class="l-section l-section--soft">
  <div class="l-wrap">
    <h2 class="p-prd__bar">おすすめポイント</h2>
    <?php
    $open = false; $no = 0;
    foreach ( $d['features'] as $r ) :
      $new_group = ( $r['gsub'] !== '' || $r['gttl'] !== '' );
      if ( $new_group ) :
        if ( $open ) echo '</div>' . "\n";
        $no = 0; $open = true;
        ?>
        <div class="p-prd__feature">
          <div class="p-prd__flabel">
            <img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/character/char-anshin.webp' ); ?>"
                 width="503" height="640" alt="" loading="lazy">
            <div>
              <?php if ( $r['gsub'] ) : ?><span><?php echo esc_html( $r['gsub'] ); ?></span><?php endif; ?>
              <strong><?php echo esc_html( $r['gttl'] ); ?></strong>
            </div>
          </div>
      <?php endif; ?>

      <?php $no++; ?>
      <div class="p-prd__point">
        <span class="p-prd__pno">Point <?php echo (int) $no; ?></span><h3 class="p-prd__ptitle"><?php echo esc_html( $r['ttl'] ); ?></h3>
        <?php if ( $r['text'] ) : ?><p class="p-prd__ptext"><?php echo nl2br( esc_html( $r['text'] ) ); ?></p><?php endif; ?>
        <?php if ( $r['note'] ) : ?><p class="p-prd__pnote"><?php echo esc_html( $r['note'] ); ?></p><?php endif; ?>
        <?php if ( $r['img'] || $r['img2'] ) : ?>
          <?php $stack = ( $r['img'] && $r['img2'] ) ? ' p-prd__pfig--stack' : ''; ?>
          <div class="p-prd__pfig p-prd__pfig--img<?php echo $stack; ?>">
            <?php echo ymkrf_img( $r['img'], 'large', $r['ttl'] ); ?>
            <?php echo ymkrf_img( $r['img2'], 'large', $r['ttl'] ); ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <?php if ( $open ) echo '</div>'; ?>
  </div>
</section>
<?php endif; ?>

<?php if ( $d['options'] ) : ?>
<!-- =========== おすすめオプション =========== -->
<section class="l-section">
  <div class="l-wrap">
    <h2 class="p-prd__bar">おすすめオプション</h2>
    <p class="p-prd__packnote">※現時点での参考価格となります。</p>
    <div class="p-prd__opts">
      <?php foreach ( $d['options'] as $r ) : ?>
        <article class="p-prd__opt">
          <div class="ph"><?php echo ymkrf_img( $r['img'], 'medium', $r['name'] ); ?></div>
          <div>
            <h3><?php echo esc_html( $r['name'] ); ?></h3>
            <?php if ( $r['text'] ) : ?><p><?php echo nl2br( esc_html( $r['text'] ) ); ?></p><?php endif; ?>
            <?php if ( $r['price'] ) : ?>
              <p class="p-prd__optprice">+<?php echo esc_html( number_format( (int) $r['price'] ) ); ?>円<small>（税込）</small><?php
                if ( $r['note'] ) echo '<em>' . esc_html( $r['note'] ) . '</em>';
              ?></p>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ( $d['works'] ) : ?>
<!-- =========== 標準工事費込み =========== -->
<section class="l-section l-section--soft">
  <div class="l-wrap">
    <h2 class="p-prd__bar">ヤマキシリフォームパックは安心の標準工事費込！</h2>
    <div class="p-prd__pack">
      <div class="p-prd__packlead">
        <h3><?php echo esc_html( $cat ? $cat->name : '' ); ?>標準工事のポイント！</h3>
        <p>標準工事費込みで価格を比較する際は、<b>どこまで工事が含まれているかをしっかり確認しましょう。</b></p>
      </div>
      <p class="p-prd__packnote">※会社によって「標準工事」の内容は違います！</p>
      <p class="p-prd__packttl">ヤマキシ標準工事内容</p>
      <ul class="p-prd__packlist">
        <?php foreach ( $d['works'] as $r ) : ?>
          <li>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12.5l5.5 5.5L20 6.5"/></svg>
            <div><b><?php echo esc_html( $r['name'] ); ?></b><span><?php echo esc_html( $r['text'] ); ?></span></div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ( $sib['prev'] || $sib['next'] ) : ?>
<!-- =========== 前後の商品（グレード） =========== -->
<section class="l-section">
  <div class="l-wrap">
    <h2 class="p-prd__bar"><?php echo esc_html( $cat ? $cat->name : '商品' ); ?>マルシェ</h2>
    <?php
    /* 左右2枠。上（または下）のグレードが無い商品では、
       枠が片方だけになって寂しいので、代わりに一覧への案内を入れます。 */
    $slots = array();
    if ( $sib['prev'] ) $slots[] = array( 'id' => $sib['prev'], 'label' => '← グレードを戻す' );
    if ( $sib['next'] ) $slots[] = array( 'id' => $sib['next'], 'label' => 'グレードUP →' );

    if ( count( $slots ) < 2 && $cat ) {
      if ( ! $sib['prev'] ) {
        /* いちばん安い商品。下のグレードが無いので、左の枠に一覧への案内を入れます */
        array_unshift( $slots, array(
          'id'    => 0,
          'label' => '',
          'name'  => '他のグレードの' . $cat->name . 'を見る',
          'url'   => get_term_link( $cat ),
        ) );
      } else {
        /* いちばん高い商品。上が無いので「グレードUP →」は付けません。
           上のグレードが増えれば、自動でその商品に置き換わります */
        $slots[] = array(
          'id'    => 0,
          'label' => '',
          'name'  => '他のグレードの' . $cat->name . 'を見る',
          'url'   => get_term_link( $cat ),
        );
      }
    }
    ?>
    <div class="p-prd__nav<?php echo ( count( $slots ) < 2 ) ? ' p-prd__nav--one' : ''; ?>">
      <?php foreach ( $slots as $s ) :
        $id  = $s['id'];
        $sd  = $id ? ymkrf_product_data( $id ) : null;
        $url = $id ? get_permalink( $id ) : $s['url'];
      ?>
        <a class="p-prd__navcard<?php echo $id ? '' : ' p-prd__navcard--list'; ?>" href="<?php echo esc_url( $url ); ?>">
          <div class="ph"><?php echo ( $id && has_post_thumbnail( $id ) ) ? get_the_post_thumbnail( $id, 'thumbnail' ) : ''; ?></div>
          <div>
            <?php if ( $s['label'] !== '' ) : ?>
              <span class="p-prd__navlabel"><?php echo esc_html( $s['label'] ); ?></span>
            <?php endif; ?>
            <span class="p-prd__navname"><?php
              echo esc_html( $sd
                ? ( $sd['grade'] ? '【' . $sd['grade'] . '】' : '' ) . $sd['name']
                : $s['name'] );
            ?></span>
            <?php if ( $sd && $sd['total'] ) : ?>
              <span class="p-prd__navprice"><?php echo esc_html( number_format( $sd['total'] ) ); ?>円（税込）</span>
            <?php endif; ?>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ( $works ) : ?>
<!-- =========== 施工事例（自動） =========== -->
<section class="l-section l-section--soft">
  <div class="l-wrap">
    <h2 class="p-prd__bar"><?php echo esc_html( $cat ? $cat->name : '' ); ?>の施工事例</h2>
    <div class="p-cards">
      <?php foreach ( $works as $wid ) : ?>
        <article class="p-card">
          <a class="p-card__photo" href="<?php echo esc_url( get_permalink( $wid ) ); ?>">
            <?php echo has_post_thumbnail( $wid ) ? get_the_post_thumbnail( $wid, 'medium_large' ) : ''; ?>
          </a>
          <div class="p-card__body">
            <p class="p-card__meta"><?php
              $ts = get_the_terms( $wid, 'ymkrf_works_cat' );
              $as = get_the_terms( $wid, 'ymkrf_works_area' );
              foreach ( array( $ts, $as ) as $group ) {
                if ( $group && ! is_wp_error( $group ) ) {
                  foreach ( $group as $t ) echo '<span>' . esc_html( $t->name ) . '</span>';
                }
              }
            ?></p>
            <h3 class="p-card__title"><a href="<?php echo esc_url( get_permalink( $wid ) ); ?>"><?php echo esc_html( get_the_title( $wid ) ); ?></a></h3>
            <p><?php echo esc_html( get_the_excerpt( $wid ) ); ?></p>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <p style="text-align:center;margin-top:20px">
      <a class="c-btn c-btn--ghost" href="<?php echo esc_url( home_url( '/works/' ) ); ?>">施工事例をもっと見る</a>
    </p>
  </div>
</section>
<?php endif; ?>

<!-- =========== 最後のご案内 =========== -->
<section class="l-section">
  <div class="l-wrap">
    <div class="p-pagecta">
      <img class="p-pagecta__chara" src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/character/char-search-fly.webp' ); ?>"
           width="480" height="480" alt="" loading="lazy">
      <h2 class="p-pagecta__title"><span class="marker">まずは、現物を見に<br class="xs-only">いらしてください</span></h2>
      <p class="p-pagecta__text">
        ショールームには北陸最大級の<br class="xs-only">住宅設備を展示しています。<br>
        見積り・現地調査は無料。<br class="xs-only">しつこい営業はいたしません。
      </p>
      <?php ymkrf_product_cta( 'product-bottom', true ); ?>
    </div>
  </div>
</section>

</main>

<?php
/* ---- 構造化データ（Googleの検索結果に価格などが出ることがあります） ---- */
$ld = array(
	'@context' => 'https://schema.org',
	'@type'    => 'Product',
	'name'     => ( $d['grade'] ? '【' . $d['grade'] . '】' : '' ) . $d['name'] . ( $d['size'] ? ' ' . $d['size'] : '' ),
	'category' => $cat ? $cat->name . 'リフォーム' : 'リフォーム',
	'url'      => get_permalink(),
);
if ( $maker )            $ld['brand'] = array( '@type' => 'Brand', 'name' => $maker->name );
if ( has_post_thumbnail() ) $ld['image'] = get_the_post_thumbnail_url( null, 'large' );
if ( $d['total'] ) {
	$ld['offers'] = array(
		'@type'         => 'Offer',
		'price'         => (string) $d['total'],
		'priceCurrency' => 'JPY',
		'availability'  => 'https://schema.org/InStock',
		'url'           => get_permalink(),
		'seller'        => array(
			'@type'      => 'HomeAndConstructionBusiness',
			'name'       => 'リフォームヤマキシ（株式会社山岸）',
			'telephone'  => '0800-777-3331',
			'areaServed' => array( '石川県', '福井県' ),
		),
	);
}
echo '<script type="application/ld+json">' . wp_json_encode( $ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";

$crumbs = array(
	array( 'ホーム', home_url( '/' ) ),
	array( '商品・価格', home_url( '/products/' ) ),
);
if ( $cat ) $crumbs[] = array( $cat->name, get_term_link( $cat ) );
$crumbs[] = array( ( $d['grade'] ? '【' . $d['grade'] . '】' : '' ) . $d['name'], get_permalink() );

$items = array();
foreach ( $crumbs as $i => $c ) {
	$items[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => $c[1] );
}
echo '<script type="application/ld+json">' . wp_json_encode( array(
	'@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";

endwhile;

get_footer();
