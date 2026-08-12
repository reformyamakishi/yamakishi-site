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
/* 施工事例は、この商品詳細ページには出しません。
   商品一覧ページ（/products/<分類>/）とトップページに出しています。 */
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
      <?php if ( $d['sub'] ) : ?><span class="p-prd__namesub"><?php echo esc_html( $d['sub'] ); ?></span><?php endif; ?>
    </h1>

    <div class="p-prd__meta">
      <?php if ( $maker ) echo ymkrf_maker_logo( $maker, 'p-prd__makerlogo' ); /* phpcs:ignore */ ?>
      <?php if ( $d['size'] ) : ?><span class="p-prd__size"><?php echo esc_html( $d['size'] ); ?></span><?php endif; ?>
      <?php if ( $d['daystext'] || $d['days'] ) : ?>
        <span class="p-prd__days">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
          <span class="lbl">工期</span>
          <?php if ( $d['daystext'] ) : /* 「半日」など、日数で書けないとき */ ?>
            <span class="num"><?php echo esc_html( $d['daystext'] ); ?></span>
          <?php else : ?>
            <span class="num"><?php echo esc_html( $d['days'] ); ?></span><span class="unit">日</span>
          <?php endif; ?>
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

<?php
$csets = function_exists( 'ymkrf_colorsets' ) ? ymkrf_colorsets( $d ) : array();
if ( ! empty( $d['images'] ) || $csets || ! empty( $d['handles'] ) ) :
?>
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
              : '色えらびで、お部屋の雰囲気はぐっと変わります。';
          ?></strong><small>※画像はイメージです</small>
        </figcaption>
      </figure>
    <?php endif; ?>

    <?php foreach ( $csets as $i => $cs ) : ?>
      <p class="p-prd__sub"<?php if ( $i ) echo ' style="margin-top:26px"'; ?>>
        <?php echo esc_html( $cs['label'] ); ?>（全<?php echo count( $cs['rows'] ); ?>色）
        <?php if ( $cs['note'] ) : ?><small class="p-prd__note"><?php echo esc_html( $cs['note'] ); ?></small><?php endif; ?>
      </p>
      <div class="p-prd__colors">
        <?php foreach ( $cs['rows'] as $r ) : ?>
          <figure>
            <div class="p-prd__swatch"><?php echo ymkrf_img( $r['img'], 'medium', $cs['label'] . ' ' . $r['name'] ); ?></div>
            <figcaption><?php echo esc_html( $r['name'] ); ?></figcaption>
          </figure>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>

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

<?php if ( $d['specs'] || $d['speclist'] ) : ?>
<!-- =========== 標準仕様 ===========
     写真つきの一覧（キッチン・お風呂）と、
     文字だけの一覧（トイレなど）の両方に対応しています。 -->
<section class="l-section">
  <div class="l-wrap">
    <h2 class="p-prd__bar">標準仕様</h2>

    <?php if ( $d['specs'] ) : ?>
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
    <?php endif; ?>

    <?php if ( $d['speclist'] ) : ?>
      <div class="p-prd__speclist">
        <?php foreach ( $d['speclist'] as $r ) :
          /* 「機能」の欄は1行に1つ書きます。頭の「・」は付いていても外します。 */
          $items = array();
          foreach ( preg_split( '/\r\n|\r|\n/', (string) $r['body'] ) as $line ) {
            /* 頭の「・」や「-」を外します。
               ltrim() は文字ではなくバイト単位で削るため、
               「パ」「ソ」などの日本語を壊してしまいます。
               ここは必ず /u 付きの正規表現で外してください。 */
            $line = preg_replace( '/^[\s・･\-–—]+/u', '', trim( $line ) );
            $line = trim( (string) $line );
            if ( $line !== '' ) $items[] = $line;
          }
          if ( ! $items ) continue;
        ?>
          <div class="p-prd__specgrp">
            <?php if ( $r['ttl'] ) : ?>
              <h3 class="p-prd__specttl"><?php echo esc_html( $r['ttl'] ); ?></h3>
            <?php endif; ?>
            <ul class="p-prd__speclis">
              <?php foreach ( $items as $it ) : ?>
                <li><?php echo esc_html( $it ); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

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
          <?php
          $stack = ( $r['img'] && $r['img2'] ) ? ' p-prd__pfig--stack' : '';
          /* 「白い枠をつける」に 1 と入れた図版だけ、白い下じきと細い枠を付けます。
             グラフや説明図（もともと白地）のときに使ってください。
             ふつうの写真は、枠なしのほうがきれいに見えます。 */
          $frame = ( isset( $r['frame'] ) && $r['frame'] !== '' ) ? ' p-prd__pfig--frame' : '';
          ?>
          <div class="p-prd__pfig p-prd__pfig--img<?php echo $stack . $frame; ?>">
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
              <?php /* マイナスの金額（値引き）も入れられるようにしています */
                $op = (int) $r['price']; ?>
              <p class="p-prd__optprice"><?php echo $op < 0 ? '−' : '+'; ?><?php echo esc_html( number_format( abs( $op ) ) ); ?>円<small>（税込）</small><?php
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

<!-- =========== 標準工事費込み ===========
     一覧ページと同じ「標準工事費にふくまれる工事」のカードを出します。
     中身は inc/functions-product.php の ymkrf_pointnote() にあるので、
     直すときはそこ1か所を直せば一覧ページにも同じように効きます。 -->
<?php
$pn = $cat ? ymkrf_pointnote( $cat->slug ) : array();
if ( ! empty( $pn['items'] ) ) :
	$cname = $cat ? $cat->name : '';
	if ( empty( $pn['note'] ) )     $pn['note']     = $cname . 'の標準工事費は、どの機種も一律同価格です。';
	if ( empty( $pn['itemsttl'] ) ) $pn['itemsttl'] = 'リフォームヤマキシの|標準工事費にふくまれる工事';
?>
<section class="l-section l-section--soft">
  <div class="l-wrap">
    <div class="p-cat__calc p-cat__calc--steps">
      <p class="p-cat__stepsttl"><span><?php
        /* 「|」を、スマホだけで効く改行に置きかえます */
        echo str_replace( '|', '<br class="xs-only">', esc_html( $pn['itemsttl'] ) );
      ?></span></p>
      <ol class="p-cat__steps">
        <?php foreach ( $pn['items'] as $i => $w ) : ?>
          <li class="p-cat__step">
            <span class="p-cat__stepnum"><?php echo (int) ( $i + 1 ); ?></span>
            <span class="p-cat__stepicon"><?php echo ymkrf_work_icon( $w['icon'] ); /* phpcs:ignore */ ?></span>
            <span class="p-cat__stepname"><?php echo esc_html( $w['name'] ); ?></span>
            <?php if ( ! empty( $w['sub'] ) ) : ?>
              <span class="p-cat__stepsub"><?php echo esc_html( $w['sub'] ); ?></span>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ol>

      <?php if ( ! empty( $pn['note'] ) ) : ?>
        <p class="p-cat__calcnote"><?php echo esc_html( $pn['note'] ); ?></p>
      <?php endif; ?>
      <?php if ( ! empty( $pn['note2'] ) ) : ?>
        <p class="p-cat__calcnote2"><?php echo esc_html( $pn['note2'] ); ?></p>
      <?php endif; ?>
    </div>

  </div>
</section>
<?php endif; ?>

<?php if ( $sib['prev'] || $sib['next'] ) : ?>
<!-- =========== 前後の商品（グレード） =========== -->
<section class="l-section">
  <div class="l-wrap">
    <h2 class="p-prd__bar"><?php echo esc_html( ymkrf_cat_brand( $cat ) ); ?></h2>
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

<!-- =========== 施工事例 ===========
     ★商品詳細ページには施工事例を出しません（ご指示により削除）。
       施工事例は、商品一覧ページ（/products/<分類>/）と
       トップページに出しています。
       もし戻したくなったときは、ここに
       ymkrf_works_section( $cat->slug, ymkrf_cat_label( $cat->slug, $cat->name ), 3 );
       をPHPタグで囲んで書けば復活します。
-->

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
