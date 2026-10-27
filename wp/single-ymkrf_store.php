<?php
/**
 * single-ymkrf_store.php ─ 店舗ごとのページ（/shops/higashikanazawa/ など）
 *
 * 置き場所： wp-content/themes/ymkrf/single-ymkrf_store.php
 * 中身：     inc/functions-store.php（スタッフ ＞ 店舗ページ で直せます）
 * 見た目：   assets/css/store.css
 *
 * （2026/10/27 ユーザー指示「店舗ページはどんなふうにつくるとSEOに強くお客様に喜ばれるか想定して作って」）
 *
 * 並び（お客様が知りたい順）
 *   1. お店の名前・写真・すぐ連絡できるボタン（来店予約／電話／LINE／地図）
 *   2. お店のご紹介
 *   3. 店舗情報（住所・営業時間・定休日・駐車場・アクセス）
 *   4. 担当エリア（市町と、主な地区）
 *   5. 店内・ショールームの写真
 *   6. スタッフ
 *   7. このお店の施工事例 ／ お客様の声
 *   8. お知らせ・チラシ
 *   9. よくある質問
 *  10. 近くのお店 ／ ご相談の窓口
 *
 * ※「追加請求はありません」といった言い切りは書かないでください。
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$s = ymkrf_store_current();
if ( ! $s ) { get_header(); echo '<main id="main"></main>'; get_footer(); return; }

$asset  = get_stylesheet_directory_uri();
$slug   = $s['slug'];
$name   = $s['name'];
$full   = 'リフォームヤマキシ ' . $name;
$cities = ymkrf_store_cities( $s );
$dist   = ymkrf_store_districts( $s );
$cnt    = ymkrf_store_counts( $s );
$tel    = ! empty( $s['tel'] ) ? $s['tel'] : '';
$telraw = $tel !== '' ? preg_replace( '/[^0-9]/', '', $tel ) : '0800777331';
$map    = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( 'ヤマキシ ' . $s['addr'] );
$line   = 'https://line.me/R/ti/p/@233okcdx';
$rsv    = home_url( '/inquiry/webrsv/' );
$bubble = ! empty( $s['open'] ) ? $s['open'] : ( ! empty( $s['topic'] ) ? $s['topic'] : '' );
$isopen = ! empty( $s['open'] );
$areas  = (array) $s['areas'];
$catch  = ! empty( $s['catch'] ) ? $s['catch']
        : implode( '・', $areas ) . 'のリフォームは、' . $name . 'におまかせください';
$intro  = ! empty( $s['intro'] ) ? $s['intro'] : ( ! empty( $s['feature'] ) ? $s['feature'] : '' );
$faq    = array();
foreach ( (array) ( isset( $s['faq'] ) && is_array( $s['faq'] ) ? $s['faq'] : array() ) as $f ) {
	if ( trim( (string) $f['q'] ) !== '' && trim( (string) $f['a'] ) !== '' ) $faq[] = $f;
}
$gal    = array_filter( array_map( 'intval', (array) ( isset( $s['gallery'] ) && is_array( $s['gallery'] ) ? $s['gallery'] : array() ) ) );

get_header();
?>

<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li><a href="<?php echo esc_url( home_url( '/shops/' ) ); ?>">店舗・対応エリア</a></li>
    <li><?php echo esc_html( $name ); ?></li>
  </ol>
</nav>
<?php
if ( function_exists( 'ymkrf_crumb_ld' ) ) {
	ymkrf_crumb_ld( array(
		array( 'ホーム', home_url( '/' ) ),
		array( '店舗・対応エリア', home_url( '/shops/' ) ),
		array( $name, home_url( '/shops/' . $slug . '/' ) ),
	) );
}
?>

<main id="main" class="p-store">

<!-- =========== 1. お店の名前・写真・すぐ連絡 =========== -->
<section class="p-store__hero">
  <div class="l-wrap p-store__herowrap">

    <div class="p-store__photo">
      <img src="<?php echo esc_url( ymkrf_store_photo( $s ) ); ?>" width="680" height="400"
           alt="<?php echo esc_attr( $full . '（' . $s['addr'] . '）の外観' ); ?>"
           <?php if ( ! empty( $s['pos'] ) && empty( $s['photo'] ) ) echo 'style="object-position:' . esc_attr( $s['pos'] ) . '"'; ?>
           fetchpriority="high">
      <?php if ( $bubble !== '' ) : ?>
        <p class="p-store__bubble<?php echo $isopen ? ' is-open' : ''; ?>"><?php if ( $isopen ) echo '<i>OPEN</i>'; ?><span><?php echo esc_html( $bubble ); ?></span></p>
      <?php endif; ?>
    </div>

    <div class="p-store__head">
      <p class="p-store__pre"><?php echo esc_html( $s['pref'] . ( $cities ? $cities[0] : '' ) ); ?>のリフォーム<?php echo ! empty( $s['sr'] ) ? '・ショールーム' : ''; ?></p>
      <h1 class="p-store__title"><span>リフォームヤマキシ</span><?php echo esc_html( $name ); ?></h1>
      <p class="p-store__catch"><?php echo esc_html( $catch ); ?></p>

      <ul class="p-store__facts">
        <li><b>住所</b><span><?php echo esc_html( $s['addr'] ); ?> <a href="<?php echo esc_url( $map ); ?>" target="_blank" rel="noopener">地図を見る ›</a></span></li>
        <?php if ( ! empty( $s['hours'] ) ) : ?>
          <li><b>営業時間</b><span><?php echo esc_html( $s['hours'] ); ?><?php if ( ! empty( $s['closed'] ) ) echo '（' . esc_html( $s['closed'] ) . '）'; ?></span></li>
        <?php endif; ?>
        <li><b>担当</b><span><?php echo esc_html( implode( '／', $areas ) ); ?></span></li>
      </ul>

      <div class="p-store__btns">
        <a class="p-store__btn p-store__btn--tel" href="tel:<?php echo esc_attr( $telraw ); ?>" data-cta="store-tel">
          <small><?php echo $tel !== '' ? 'お店に電話する' : 'お電話（通話無料）'; ?></small><?php echo esc_html( $tel !== '' ? $tel : '0800-777-3331' ); ?></a>
        <a class="p-store__btn p-store__btn--rsv" href="<?php echo esc_url( $rsv ); ?>" data-cta="store-rsv">来店を予約する</a>
        <a class="p-store__btn p-store__btn--line" href="<?php echo esc_url( $line ); ?>" rel="noopener" data-cta="store-line">LINEで相談する</a>
      </div>
    </div>

  </div>
</section>

<!-- 数字（ひと目で分かる安心） -->
<div class="l-wrap">
  <ul class="p-store__stats">
    <?php if ( $cnt['works'] ) : ?><li><b><?php echo number_format( $cnt['works'] ); ?></b><span>担当エリアの施工事例</span></li><?php endif; ?>
    <?php if ( $cnt['voices'] ) : ?><li><b><?php echo number_format( $cnt['voices'] ); ?></b><span>担当エリアのお客様の声</span></li><?php endif; ?>
    <?php if ( $cnt['staff'] ) : ?><li><b><?php echo (int) $cnt['staff']; ?></b><span>このお店のスタッフ</span></li><?php endif; ?>
    <li><b>127</b><span>創業からの年数</span></li>
  </ul>
</div>

<!-- =========== 2. お店のご紹介 =========== -->
<?php if ( $intro !== '' ) : ?>
<section class="l-section">
  <div class="l-wrap l-wrap--narrow">
    <h2 class="p-prd__bar"><?php echo esc_html( $name ); ?>のご紹介</h2>
    <div class="p-store__intro"><?php echo wpautop( esc_html( $intro ) ); /* phpcs:ignore */ ?></div>
  </div>
</section>
<?php endif; ?>

<!-- =========== 3. 店舗情報 =========== -->
<section class="l-section l-section--soft" id="info">
  <div class="l-wrap l-wrap--narrow">
    <h2 class="p-prd__bar">店舗情報</h2>
    <table class="p-store__info">
      <tbody>
        <tr><th>店舗名</th><td><?php echo esc_html( $full ); ?></td></tr>
        <tr><th>住所</th><td><?php echo esc_html( $s['addr'] ); ?><br><a class="p-store__map" href="<?php echo esc_url( $map ); ?>" target="_blank" rel="noopener">Googleマップで見る ›</a></td></tr>
        <tr><th>電話</th><td>
          <?php if ( $tel !== '' ) : ?>
            <a href="tel:<?php echo esc_attr( $telraw ); ?>" data-cta="store-tel"><?php echo esc_html( $tel ); ?></a>
          <?php else : ?>
            <a href="tel:0800777331" data-cta="store-tel">0800-777-3331</a>（通話無料・各店へおつなぎします）
          <?php endif; ?>
        </td></tr>
        <?php if ( ! empty( $s['hours'] ) ) : ?>
          <tr><th>営業時間</th><td><?php echo esc_html( $s['hours'] ); ?><?php if ( ! empty( $s['hnote'] ) ) echo '<br><small>' . esc_html( $s['hnote'] ) . '</small>'; ?></td></tr>
        <?php endif; ?>
        <?php if ( ! empty( $s['closed'] ) ) : ?><tr><th>定休日</th><td><?php echo esc_html( $s['closed'] ); ?></td></tr><?php endif; ?>
        <tr><th>ショールーム</th><td><?php echo ! empty( $s['sr'] ) ? 'あり（実物をご覧いただけます）' : 'なし（ご相談・お見積りは承ります）'; ?><?php if ( ! empty( $s['srnote'] ) ) echo '<br><small>' . esc_html( $s['srnote'] ) . '</small>'; ?></td></tr>
        <?php if ( ! empty( $s['parking'] ) ) : ?><tr><th>駐車場</th><td><?php echo esc_html( $s['parking'] ); ?></td></tr><?php endif; ?>
        <?php if ( ! empty( $s['access'] ) ) : ?><tr><th>アクセス</th><td><?php echo esc_html( $s['access'] ); ?></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<!-- =========== 4. 担当エリア =========== -->
<section class="l-section" id="area">
  <div class="l-wrap l-wrap--narrow">
    <h2 class="p-prd__bar"><?php echo esc_html( $name ); ?>の担当エリア</h2>
    <div class="p-store__area">
      <?php foreach ( $areas as $a ) :
        $city = trim( (string) preg_replace( '/（[^）]*）$/u', '', $a ) );
        $rm   = function_exists( 'ymkrf_area_roman' ) ? ymkrf_area_roman( $city ) : '';
        $aurl = ( $rm !== '' && function_exists( 'ymkrf_area_url' ) ) ? ymkrf_area_url( $rm ) : '';
      ?>
        <?php if ( $aurl ) : ?><a class="p-store__city" href="<?php echo esc_url( $aurl ); ?>"><?php echo esc_html( $a ); ?></a>
        <?php else : ?><span class="p-store__city"><?php echo esc_html( $a ); ?></span><?php endif; ?>
      <?php endforeach; ?>
    </div>
    <?php if ( $dist ) : ?>
      <h3 class="p-store__h3">主な地区</h3>
      <p class="p-area__towns">
        <?php foreach ( $dist as $d ) : ?><span><?php echo esc_html( $d ); ?></span><?php endforeach; ?>
      </p>
    <?php endif; ?>
    <p class="p-store__note">お店から車で30分ほどの範囲をうかがっています。<b>どのお店にご連絡いただいても、お住まいから近いお店がうかがいます。</b></p>
  </div>
</section>

<!-- =========== 5. 店内・ショールームの写真 =========== -->
<?php if ( $gal ) : ?>
<section class="l-section l-section--soft">
  <div class="l-wrap">
    <h2 class="p-prd__bar"><?php echo esc_html( $name ); ?>の店内<?php echo ! empty( $s['sr'] ) ? '・ショールーム' : ''; ?></h2>
    <div class="p-store__gal">
      <?php $gi = 0; foreach ( $gal as $gid ) : $gi++;
        $alt = trim( (string) get_post_meta( $gid, '_wp_attachment_image_alt', true ) );
        if ( $alt === '' || mb_strpos( $alt, $name ) === false ) $alt = $full . ' 店内のようす' . ( count( $gal ) > 1 ? '（' . $gi . '）' : '' );
        echo wp_get_attachment_image( $gid, 'medium_large', false, array( 'loading' => 'lazy', 'alt' => $alt ) );
      endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- =========== 6. スタッフ =========== -->
<?php $staff = function_exists( 'ymkrf_staff_list' ) ? ymkrf_staff_list( $slug ) : array(); ?>
<?php if ( $staff ) : ?>
<section class="l-section">
  <div class="l-wrap">
    <h2 class="p-prd__bar"><?php echo esc_html( $name ); ?>のスタッフ</h2>
    <div class="p-staff__grid p-store__staff">
      <?php foreach ( $staff as $st ) :
        $role = trim( (string) get_post_meta( $st->ID, '_ymkrf_staff_role', true ) );
        $word = trim( (string) get_post_meta( $st->ID, '_ymkrf_staff_word', true ) );
        $lic  = trim( (string) get_post_meta( $st->ID, '_ymkrf_staff_lic', true ) );
      ?>
        <a class="p-staff" href="<?php echo esc_url( get_permalink( $st ) ); ?>">
          <span class="p-staff__face"><?php echo function_exists( 'ymkrf_staff_face' ) ? ymkrf_staff_face( $st->ID, 'medium' ) : ''; /* phpcs:ignore */ ?></span>
          <span class="p-staff__txt">
            <?php if ( $role ) : ?><span class="p-staff__role"><?php echo esc_html( $role ); ?></span><?php endif; ?>
            <span class="p-staff__name"><?php echo esc_html( get_the_title( $st ) ); ?></span>
            <?php if ( $word ) : ?><span class="p-staff__word"><?php echo esc_html( wp_trim_words( $word, 50, '…' ) ); ?></span><?php endif; ?>
            <?php if ( $lic ) : ?><span class="p-staff__lic">資格：<?php echo esc_html( $lic ); ?></span><?php endif; ?>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- =========== 7. 施工事例 ／ お客様の声 =========== -->
<?php $wids = ymkrf_store_works( $s, 6 ); ?>
<?php if ( $wids ) : ?>
<section class="l-section l-section--soft">
  <div class="l-wrap">
    <h2 class="p-prd__bar"><?php echo esc_html( $name ); ?>の施工事例</h2>
    <div class="p-area__cards">
      <?php foreach ( $wids as $wid ) :
        $part = function_exists( 'ymkrf_works_cat_name' ) ? ymkrf_works_cat_name( $wid ) : '';
        if ( $part === '' ) { $ct = get_the_terms( $wid, 'ymkrf_works_cat' ); if ( $ct && ! is_wp_error( $ct ) ) $part = reset( $ct )->name; }
      ?>
        <a class="p-area__card" href="<?php echo esc_url( get_permalink( $wid ) ); ?>">
          <span class="p-area__card__ph"><?php
            if ( has_post_thumbnail( $wid ) ) echo get_the_post_thumbnail( $wid, 'medium_large', array( 'loading' => 'lazy', 'alt' => '' ) );
            else echo '<span class="p-area__card__noph">写真は準備中です</span>';
          ?></span>
          <span class="p-area__card__bd">
            <?php if ( $part ) : ?><span class="p-area__tag"><?php echo esc_html( $part ); ?></span><?php endif; ?>
            <span class="p-area__card__ttl"><?php echo esc_html( get_the_title( $wid ) ); ?></span>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
    <?php if ( $cities && function_exists( 'ymkrf_area_roman' ) ) :
      $rm0 = ymkrf_area_roman( $cities[0] ); ?>
      <?php if ( $rm0 !== '' ) : ?>
        <p class="p-store__more"><a class="c-more" href="<?php echo esc_url( ymkrf_area_url( $rm0 ) ); ?>"><?php echo esc_html( $cities[0] ); ?>のリフォーム・施工事例をもっと見る</a></p>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php
$vq = ( $cities && function_exists( 'ymkrf_area_voices' ) ) ? ymkrf_area_voices( $cities[0], 3 ) : null;
if ( $vq && $vq->have_posts() ) : ?>
<section class="l-section">
  <div class="l-wrap">
    <h2 class="p-prd__bar"><?php echo esc_html( $cities[0] ); ?>のお客様の声</h2>
    <div class="p-area__voices">
      <?php while ( $vq->have_posts() ) : $vq->the_post();
        $vid = get_the_ID();
        $ini = (string) get_post_meta( $vid, '_ymkrf_initial', true );
        $sc  = (int) get_post_meta( $vid, '_ymkrf_score', true );
        $cm  = trim( (string) get_post_meta( $vid, '_ymkrf_comment', true ) );
        if ( $cm === '' ) $cm = trim( (string) get_post_meta( $vid, '_ymkrf_after', true ) );
        $ps  = function_exists( 'ymkrf_voice_meta_array' ) ? ymkrf_voice_meta_array( $vid, '_ymkrf_parts' ) : array();
      ?>
        <a class="p-area__voice" href="<?php the_permalink(); ?>">
          <?php if ( $ps ) : ?><span class="p-area__tag"><?php echo esc_html( $ps[0] ); ?></span><?php endif; ?>
          <span class="p-area__voice__who"><?php echo esc_html( $cities[0] ); ?><?php if ( $ini ) echo '／' . esc_html( $ini ) . '様'; ?>
            <?php if ( $sc ) : ?><small>（満足度<?php echo (int) $sc; ?>点）</small><?php endif; ?></span>
          <?php if ( $cm !== '' ) : ?><span class="p-area__voice__txt">「<?php echo esc_html( mb_strimwidth( $cm, 0, 90, '…', 'UTF-8' ) ); ?>」</span><?php endif; ?>
        </a>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- =========== 8. お知らせ・チラシ =========== -->
<?php
$news = get_posts( array( 'post_type' => 'ymkrf_news', 'post_status' => 'publish', 'posts_per_page' => 3,
                          'meta_query' => array( array( 'key' => '_ymkrf_shop', 'value' => $slug ) ) ) );
?>
<section class="l-section l-section--soft">
  <div class="l-wrap l-wrap--narrow">
    <h2 class="p-prd__bar"><?php echo esc_html( $name ); ?>のお知らせ・チラシ</h2>
    <?php if ( $news ) : ?>
      <ul class="p-store__news">
        <?php foreach ( $news as $nw ) : ?>
          <li><a href="<?php echo esc_url( get_permalink( $nw ) ); ?>"><time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d', $nw ) ); ?>"><?php echo esc_html( get_the_date( 'Y.m.d', $nw ) ); ?></time><span><?php echo esc_html( get_the_title( $nw ) ); ?></span></a></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <p class="p-store__links">
      <a href="<?php echo esc_url( add_query_arg( 'shop', $slug, home_url( '/flyer/' ) ) ); ?>">このお店のイベント・チラシを見る ›</a>
      <?php if ( $staff ) : ?><a href="<?php echo esc_url( add_query_arg( 'shop', $slug, get_post_type_archive_link( 'ymkrf_staff' ) ) ); ?>">スタッフ紹介を見る ›</a><?php endif; ?>
    </p>
  </div>
</section>

<!-- =========== 9. よくある質問 =========== -->
<?php if ( $faq ) : ?>
<section class="l-section">
  <div class="l-wrap l-wrap--narrow">
    <h2 class="p-prd__bar"><?php echo esc_html( $name ); ?>によくある質問</h2>
    <?php foreach ( $faq as $f ) : ?>
      <div class="p-area__faq"><b>Q. <?php echo esc_html( $f['q'] ); ?></b><p><?php echo nl2br( esc_html( $f['a'] ) ); ?></p></div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- =========== 10. 近くのお店 =========== -->
<?php
$near = array();
foreach ( ymkrf_shops() as $o ) {
	if ( $o['slug'] === $slug || $o['pref'] !== $s['pref'] ) continue;
	$near[] = $o;
}
/* 同じ市町を受けもつお店を先に */
usort( $near, function ( $a, $b ) use ( $cities ) {
	$sa = count( array_intersect( ymkrf_store_cities( $a ), $cities ) );
	$sb = count( array_intersect( ymkrf_store_cities( $b ), $cities ) );
	return $sb - $sa;
} );
$near = array_slice( $near, 0, 4 );
?>
<?php if ( $near ) : ?>
<section class="l-section l-section--soft">
  <div class="l-wrap">
    <h2 class="p-prd__bar"><?php echo esc_html( $s['pref'] ); ?>のほかのお店</h2>
    <div class="p-store__near">
      <?php foreach ( $near as $o ) : ?>
        <a class="p-store__nearcard" href="<?php echo esc_url( ymkrf_shop_url( $o['slug'] ) ); ?>">
          <b><?php echo esc_html( $o['name'] ); ?></b>
          <span><?php echo esc_html( preg_replace( '/^(石川県|福井県)/u', '', $o['addr'] ) ); ?></span>
          <small>担当：<?php echo esc_html( implode( '／', (array) $o['areas'] ) ); ?></small>
        </a>
      <?php endforeach; ?>
    </div>
    <p class="p-store__more"><a class="c-more" href="<?php echo esc_url( home_url( '/shops/' ) ); ?>">すべての店舗・対応エリアを見る</a></p>
  </div>
</section>
<?php endif; ?>

<section class="l-section">
  <div class="l-wrap">
    <div class="p-pagecta">
      <img class="p-pagecta__chara" src="<?php echo esc_url( $asset . '/assets/img/character/char-search-fly.webp' ); ?>"
           width="480" height="480" alt="" loading="lazy">
      <h2 class="p-pagecta__title"><span class="marker"><?php echo esc_html( $name ); ?>へ、お気軽にご相談ください</span></h2>
      <p class="p-pagecta__text">見積り・現地調査は無料。<br class="xs-only">しつこい営業はいたしません。</p>
      <?php if ( function_exists( 'ymkrf_product_cta' ) ) ymkrf_product_cta( 'store-' . $slug, true ); ?>
    </div>
  </div>
</section>

</main>

<?php get_footer();
