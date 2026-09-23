<?php
/**
 * 市町別ページ（/area/kanazawa/ など）
 * 置き場所： wp-content/themes/ymkrf/ymkrf-area.php
 *
 * 中身は inc/functions-area.php が用意します。
 * 施工事例・お客様の声・担当店舗は自動で入ります。
 * 手で入れるのは、書き出しの文・よくあるご相談・対応地区の3つだけです。
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$slug = get_query_var( 'ymkrf_area' );
$a    = ymkrf_area_get( $slug );
if ( ! $a ) { get_header(); get_footer(); return; }

$city = $a['city'];
$c    = ymkrf_area_counts( $city );
$t    = ymkrf_area_text( $slug );

/* 書き出しは「対応エリア」画面 → エリア分類の説明 → 自動の文、の順で使います
   （2026/09/23 ユーザー指示） */
$lead  = function_exists( 'ymkrf_area_lead' ) ? ymkrf_area_lead( $slug, $city ) : '';
if ( $lead === '' && isset( $t['lead'] ) ) $lead = trim( $t['lead'] );
$towns = isset( $t['towns'] ) ? trim( $t['towns'] ) : '';
$faq   = isset( $t['faq'] ) && is_array( $t['faq'] ) ? $t['faq'] : array();

/* エリアの分類（「もっと見る」のリンク先） */
$term = get_term_by( 'name', $city, 'ymkrf_works_area' );

get_header();
?>

<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li><a href="<?php echo esc_url( ymkrf_area_url() ); ?>">対応エリア</a></li>
    <li><?php echo esc_html( $city ); ?></li>
  </ol>
</nav>
<?php
if ( function_exists( 'ymkrf_crumb_ld' ) ) {
	ymkrf_crumb_ld( array(
		array( 'ホーム',     home_url( '/' ) ),
		array( '対応エリア', ymkrf_area_url() ),
		array( $city,        ymkrf_area_url( $slug ) ),
	) );
}
?>

<main id="main">

<!-- =========== 見出し =========== -->
<div class="p-area__head">
  <div class="l-wrap">
    <h1 class="p-area__title"><?php echo esc_html( $city ); ?>のリフォームなら<br>創業127年のリフォームヤマキシ</h1>

    <p class="p-area__lead">
      <?php if ( $lead !== '' ) : ?>
        <?php echo nl2br( esc_html( $lead ) ); ?>
      <?php else :
        /* 正式な住所（福井県丹生郡越前町）を、書き出しに1回だけ入れます。
           郡で探された方にも届くようにするためで、見出しは町名のままにします。
           （2026/09/23 ユーザー指示「越前町は、丹生郡なんです」） */
        $full = function_exists( 'ymkrf_area_full_name' ) ? ymkrf_area_full_name( $city ) : $city;
        ?>
        <?php echo esc_html(
          ( $full !== '' && $full !== $city ? $full : $city )
          . 'で' . ( $c['works'] ? number_format( $c['works'] ) . '件の施工実績。' : 'リフォームを承っています。' )
          . 'キッチン・お風呂・トイレ・洗面台の交換から、外壁塗装・カーポートまで。'
          . '本体・標準工事費・古い設備の撤去処分費まで込みの、分かりやすい価格でご案内します。'
          . '見積り・現地調査は無料です。'
        ); ?>
      <?php endif; ?>
    </p>

    <div class="p-area__stats">
      <?php if ( $c['works'] ) : ?>
        <div class="p-area__stat"><b><?php echo number_format( $c['works'] ); ?></b><span><?php echo esc_html( $city ); ?>の施工事例</span></div>
      <?php endif; ?>
      <?php if ( $c['voices'] ) : ?>
        <div class="p-area__stat"><b><?php echo number_format( $c['voices'] ); ?></b><span><?php echo esc_html( $city ); ?>のお客様の声</span></div>
      <?php endif; ?>
      <?php if ( $a['shops'] ) : ?>
        <div class="p-area__stat"><b><?php echo count( $a['shops'] ); ?></b><span>担当店舗</span></div>
      <?php endif; ?>
      <div class="p-area__stat"><b>127</b><span>創業からの年数</span></div>
    </div>
  </div>
</div>

<!-- =========== 担当店舗 =========== -->
<?php if ( $a['shops'] ) : ?>
<section class="l-section">
  <div class="l-wrap">
    <h2 class="p-prd__bar"><?php echo esc_html( $city ); ?>の担当店舗</h2>
    <div class="p-area__shops">
      <?php foreach ( $a['shops'] as $s ) : ?>
        <?php $surl = function_exists( 'ymkrf_shop_url' ) ? ymkrf_shop_url( $s['slug'] ) : home_url( '/shops/' ); ?>
        <div class="p-area__shop">
          <h3>
            <a href="<?php echo esc_url( $surl ); ?>">ヤマキシ <?php echo esc_html( $s['name'] ); ?></a>
            <?php if ( ! empty( $s['open'] ) ) : ?><span class="p-area__shop__new"><?php echo esc_html( $s['open'] ); ?></span><?php endif; ?>
          </h3>
          <dl>
            <dt>住所</dt><dd><?php echo esc_html( isset( $s['addr'] ) ? $s['addr'] : '' ); ?></dd>
            <dt>電話</dt>
            <dd class="p-area__tel">
              <?php if ( ! empty( $s['tel'] ) ) : ?>
                <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9]/', '', $s['tel'] ) ); ?>"><?php echo esc_html( $s['tel'] ); ?></a>
              <?php else : ?>
                <a href="tel:0800777331">0800-777-3331</a><small>（通話無料）</small>
              <?php endif; ?>
            </dd>
            <?php if ( ! empty( $s['hours'] ) ) : ?><dt>営業時間</dt><dd><?php echo esc_html( $s['hours'] ); ?></dd><?php endif; ?>
            <?php if ( ! empty( $s['areas'] ) ) : ?>
              <dt>担当エリア</dt><dd><?php echo esc_html( implode( '・', (array) $s['areas'] ) ); ?></dd>
            <?php endif; ?>
          </dl>
          <?php if ( ! empty( $s['feature'] ) ) : ?>
            <p class="p-area__shop__txt"><?php echo esc_html( $s['feature'] ); ?></p>
          <?php endif; ?>
          <?php /* お店の専用ページへ（2026/09/23 ユーザー指示） */ ?>
          <p class="p-area__shop__link">
            <a href="<?php echo esc_url( $surl ); ?>">ヤマキシ <?php echo esc_html( $s['name'] ); ?>のページを見る</a>
          </p>
        </div>
      <?php endforeach; ?>
    </div>
    <?php if ( count( $a['shops'] ) > 1 ) : ?>
      <p class="p-area__note"><?php echo esc_html( $city ); ?>は広いので、<b>お住まいから近いお店</b>が担当します。どちらにご連絡いただいてもかまいません。</p>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<!-- =========== 施工事例 =========== -->
<?php
$wq = ymkrf_area_works( $city, 6 );
if ( $wq->have_posts() ) : ?>
<section class="l-section l-section--soft">
  <div class="l-wrap">
    <h2 class="p-prd__bar"><?php echo esc_html( $city ); ?>の施工事例</h2>
    <div class="p-area__cards">
      <?php while ( $wq->have_posts() ) : $wq->the_post();
        $wid  = get_the_ID();
        $part = function_exists( 'ymkrf_works_cat_name' ) ? ymkrf_works_cat_name( $wid ) : '';
        if ( $part === '' ) {
          $ct = get_the_terms( $wid, 'ymkrf_works_cat' );
          if ( $ct && ! is_wp_error( $ct ) ) $part = reset( $ct )->name;
        }
      ?>
        <a class="p-area__card" href="<?php the_permalink(); ?>">
          <span class="p-area__card__ph">
            <?php if ( has_post_thumbnail() ) {
              the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy', 'alt' => '' ) );
            } else { ?>
              <span class="p-area__card__noph">写真は準備中です</span>
            <?php } ?>
          </span>
          <span class="p-area__card__bd">
            <?php if ( $part ) : ?><span class="p-area__tag"><?php echo esc_html( $part ); ?></span><?php endif; ?>
            <span class="p-area__card__ttl"><?php echo esc_html( get_the_title() ); ?></span>
          </span>
        </a>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>
    <?php if ( $term && ! is_wp_error( $term ) ) : ?>
      <p style="text-align:center;margin-top:20px">
        <a class="c-more" href="<?php echo esc_url( get_term_link( $term ) ); ?>"><?php
          echo esc_html( $city ); ?>の施工事例をもっと見る（<?php echo number_format( $c['works'] ); ?>件）</a>
      </p>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<!-- =========== お客様の声 =========== -->
<?php
$vq = ymkrf_area_voices( $city, 3 );
if ( $vq->have_posts() ) : ?>
<section class="l-section">
  <div class="l-wrap">
    <h2 class="p-prd__bar"><?php echo esc_html( $city ); ?>のお客様の声</h2>
    <div class="p-area__voices">
      <?php while ( $vq->have_posts() ) : $vq->the_post();
        $vid  = get_the_ID();
        $ini  = (string) get_post_meta( $vid, '_ymkrf_initial', true );
        $sc   = (int) get_post_meta( $vid, '_ymkrf_score', true );
        $cm   = trim( (string) get_post_meta( $vid, '_ymkrf_comment', true ) );
        if ( $cm === '' ) $cm = trim( (string) get_post_meta( $vid, '_ymkrf_after', true ) );
        $ps   = function_exists( 'ymkrf_voice_meta_array' ) ? ymkrf_voice_meta_array( $vid, '_ymkrf_parts' ) : array();
      ?>
        <a class="p-area__voice" href="<?php the_permalink(); ?>">
          <?php if ( $ps ) : ?><span class="p-area__tag"><?php echo esc_html( $ps[0] ); ?></span><?php endif; ?>
          <span class="p-area__voice__who">
            <?php echo esc_html( $city ); ?><?php if ( $ini ) echo '／' . esc_html( $ini ) . '様'; ?>
            <?php if ( $sc ) : ?><small>（満足度<?php echo (int) $sc; ?>点）</small><?php endif; ?>
          </span>
          <?php if ( $cm !== '' ) : ?>
            <span class="p-area__voice__txt">「<?php echo esc_html( mb_strimwidth( $cm, 0, 90, '…', 'UTF-8' ) ); ?>」</span>
          <?php endif; ?>
        </a>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>
    <?php /* 施工事例と同じように、その市町のお客様の声だけの一覧へ送ります
             （2026/09/23 ユーザー指示「お客様の声も同様にできる？」）
             /voice/area/nanao/ の形です。 */
      $vurl = function_exists( 'ymkrf_voice_url' ) && function_exists( 'ymkrf_area_roman' )
        ? ymkrf_voice_url( '', ymkrf_area_roman( $city ) )
        : (string) get_post_type_archive_link( 'ymkrf_voice' );
    ?>
    <p style="text-align:center;margin-top:20px">
      <a class="c-more" href="<?php echo esc_url( $vurl ); ?>"><?php
        echo esc_html( $city ); ?>のお客様の声をもっと見る（<?php echo number_format( $c['voices'] ); ?>件）</a>
    </p>
  </div>
</section>
<?php endif; ?>

<!-- =========== 工事費の目安 =========== -->
<section class="l-section l-section--soft">
  <div class="l-wrap">
    <h2 class="p-prd__bar"><?php echo esc_html( $city ); ?>の工事費の目安</h2>
    <div class="p-area__menu">
      <?php
      $menu = array(
        'kitchen'  => array( 'キッチン交換',   240000 ),
        'bathroom' => array( 'お風呂交換',     370000 ),
        'toilet'   => array( 'トイレ交換',      38000 ),
        'lavatory' => array( '洗面化粧台交換',  24200 ),
        'ecocute'  => array( 'エコキュート',   128000 ),
        'cooktop'  => array( 'コンロ・IH',      69800 ),
      );
      foreach ( $menu as $cs => $m ) : ?>
        <a href="<?php echo esc_url( ymkrf_cat_url( $cs ) ); ?>">
          <?php echo esc_html( $m[0] ); ?>
          <b><?php echo esc_html( number_format( $m[1] ) ); ?>円〜</b>
        </a>
      <?php endforeach; ?>
    </div>
    <p class="p-area__note">
      標準工事費・古い設備の撤去処分費まで込みの税込価格です。
      お家の形や配管の状態によって追加の工事が必要なときは、着工前にかならずお見積りをお出しします。
    </p>
  </div>
</section>

<!-- =========== よくあるご相談 =========== -->
<?php if ( $faq ) : ?>
<section class="l-section">
  <div class="l-wrap">
    <h2 class="p-prd__bar"><?php echo esc_html( $city ); ?>でよくあるご相談</h2>
    <?php foreach ( $faq as $f ) : ?>
      <div class="p-area__faq">
        <b><?php echo esc_html( $f['q'] ); ?></b>
        <?php if ( ! empty( $f['a'] ) ) : ?><p><?php echo nl2br( esc_html( $f['a'] ) ); ?></p><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- =========== 対応地区・近隣の市町 =========== -->
<section class="l-section l-section--soft">
  <div class="l-wrap">
    <?php if ( $towns !== '' ) : ?>
      <h2 class="p-prd__bar"><?php echo esc_html( $city ); ?>の対応地区</h2>
      <p class="p-area__towns">
        <?php foreach ( preg_split( '/[、,]\s*/u', $towns ) as $tw ) :
          $tw = trim( $tw ); if ( $tw === '' ) continue; ?>
          <span><?php echo esc_html( $tw ); ?></span>
        <?php endforeach; ?>
      </p>
    <?php endif; ?>

    <h2 class="p-prd__bar" style="margin-top:<?php echo $towns !== '' ? '30px' : '0'; ?>">近くの市町</h2>
    <p class="p-area__towns">
      <?php
      /* 地図の北から南の並びで、前後の市町をとります。
         （2026/09/23 ユーザー承認。これまでは「同じ県で事例が1件以上」
           というだけで、近さを見ていませんでした） */
      $near = function_exists( 'ymkrf_area_near' ) ? ymkrf_area_near( $slug, 3 ) : array();
      foreach ( $near as $one ) : ?>
        <a href="<?php echo esc_url( ymkrf_area_url( $one['slug'] ) ); ?>"><?php echo esc_html( $one['city'] ); ?></a>
      <?php endforeach;
      if ( ! $near ) echo '<a href="' . esc_url( ymkrf_area_url() ) . '">対応エリアの一覧</a>'; ?>
    </p>
  </div>
</section>

<!-- =========== 相談 =========== -->
<section class="l-section">
  <div class="l-wrap">
    <div class="p-area__cta">
      <b><?php echo esc_html( $city ); ?>のリフォーム、まずはご相談ください</b>
      <p>見積り・現地調査は無料です。しつこい営業はいたしません。</p>
      <div class="p-area__cta__btns">
        <a class="p-area__btn p-area__btn--line" href="<?php echo esc_url( home_url( '/inquiry/line/' ) ); ?>">LINEで相談する</a>
        <a class="p-area__btn" href="<?php echo esc_url( home_url( '/inquiry/' ) ); ?>">無料見積りを依頼する</a>
      </div>
    </div>
  </div>
</section>

</main>

<?php get_footer();
