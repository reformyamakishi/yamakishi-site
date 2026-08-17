<?php
/**
 * single-ymkrf_works.php ─ 施工事例 詳細
 * 置き場所： wp-content/themes/ymkrf/single-ymkrf_works.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$asset = get_stylesheet_directory_uri();
get_header();

while ( have_posts() ) : the_post();
  $id     = get_the_ID();
  $wcat   = ymkrf_works_term_names( $id, 'ymkrf_works_cat' );
  $warea  = ymkrf_works_term_names( $id, 'ymkrf_works_area' );
  $wprice = trim( (string) get_post_meta( $id, '_ymkrf_price', true ) );
  $wperi  = trim( (string) get_post_meta( $id, '_ymkrf_period', true ) );
  $wdone  = trim( (string) get_post_meta( $id, '_ymkrf_done', true ) );
  $wshop  = ymkrf_works_shop_name( $id );
  $wcase  = trim( (string) get_post_meta( $id, '_ymkrf_case_no', true ) );
  $wprods = ymkrf_works_products( $id );
  $wvoice = ymkrf_works_linked_voices( $id );
  $wrel   = ymkrf_works_related( $id, 3 );
?>

<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li><a href="<?php echo esc_url( get_post_type_archive_link( 'ymkrf_works' ) ); ?>">施工事例</a></li>
    <?php $wct = get_the_terms( $id, 'ymkrf_works_cat' ); if ( $wct && ! is_wp_error( $wct ) ) :
      $wc1 = reset( $wct ); ?>
      <li><a href="<?php echo esc_url( get_term_link( $wc1 ) ); ?>"><?php echo esc_html( $wc1->name ); ?></a></li>
    <?php endif; ?>
    <li><?php the_title(); ?></li>
  </ol>
</nav>

<main id="main">

<div class="p-pagehead">
  <div class="l-wrap p-pagehead__inner">
    <span class="p-pagehead__en">WORKS</span>
    <?php if ( $wcat || $warea ) : ?>
      <p class="p-work__badges">
        <?php foreach ( $wcat as $t ) : ?><span class="p-work__badge"><?php echo esc_html( $t ); ?></span><?php endforeach; ?>
        <?php foreach ( $warea as $t ) : ?><span class="p-work__badge p-work__badge--a"><?php echo esc_html( $t ); ?></span><?php endforeach; ?>
      </p>
    <?php endif; ?>
    <h1 class="p-pagehead__title"><?php the_title(); ?></h1>
    <?php if ( $wshop ) : ?>
      <p class="p-work__headshop">施工店舗：<?php echo esc_html( $wshop ); ?></p>
    <?php endif; ?>
  </div>
</div>

<section class="l-section">
  <div class="l-wrap l-wrap--narrow">

    <?php /* Before / After。両方の写真があるときは、左右に動かして見くらべられます */ ?>
    <?php $cmp = ymkrf_works_compare( $id, true ); ?>
    <?php if ( $cmp ) : ?>
      <div class="p-work__hero"><?php echo $cmp; ?></div>
    <?php endif; ?>

    <?php /* 工事のデータ */ ?>
    <?php if ( $wprice || $wperi || $wdone || $wshop || $wcat || $warea ) : ?>
      <h2 class="p-work__h2">この工事のデータ</h2>
      <table class="p-work__spec">
        <tbody>
          <?php if ( $wcat ) : ?>
            <tr><th>工事した箇所</th><td><?php echo esc_html( implode( '／', $wcat ) ); ?></td></tr>
          <?php endif; ?>
          <?php if ( $wprice ) : ?>
            <tr><th>工事費</th><td class="p-work__price"><?php echo esc_html( $wprice ); ?></td></tr>
          <?php endif; ?>
          <?php if ( $wperi ) : ?>
            <tr><th>工期</th><td><?php echo esc_html( $wperi ); ?></td></tr>
          <?php endif; ?>
          <?php if ( $wdone ) : ?>
            <tr><th>完工時期</th><td><?php echo esc_html( $wdone ); ?></td></tr>
          <?php endif; ?>
          <?php if ( $warea ) : ?>
            <tr><th>施工エリア</th><td><?php echo esc_html( implode( '／', $warea ) ); ?></td></tr>
          <?php endif; ?>
          <?php if ( $wshop ) : ?>
            <tr><th>担当した店舗</th><td><?php echo esc_html( $wshop ); ?></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
      <?php if ( $wprice ) : ?>
        <p class="p-work__note">
          金額は工事当時のものです。お住まいの形や条件によって変わりますので、
          くわしくは無料のお見積りでご確認ください。
        </p>
      <?php endif; ?>
    <?php endif; ?>

    <?php /* 工事の内容（本文） */ ?>
    <?php $wbody = trim( get_the_content() ); ?>
    <?php if ( $wbody !== '' ) : ?>
      <h2 class="p-work__h2">工事の内容</h2>
      <div class="p-work__body"><?php the_content(); ?></div>
    <?php endif; ?>

    <?php /* 使った商品 */ ?>
    <?php if ( $wprods ) : ?>
      <h2 class="p-work__h2">この工事で使った商品</h2>
      <div class="p-work__prods">
        <?php foreach ( $wprods as $pr ) :
          $pimg = get_the_post_thumbnail( $pr->ID, 'medium', array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
          <a class="p-work__prod" href="<?php echo esc_url( get_permalink( $pr ) ); ?>">
            <?php if ( $pimg ) : ?><span class="p-work__prodimg"><?php echo $pimg; ?></span><?php endif; ?>
            <span class="p-work__prodtxt">
              <span class="p-work__prodname"><?php echo esc_html( get_the_title( $pr ) ); ?></span>
              <span class="p-work__prodmore">商品ページを見る</span>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php /* 同じ案件番号のお客様の声 */ ?>
    <?php if ( $wvoice ) : ?>
      <h2 class="p-work__h2">このお客様からいただいた声</h2>
      <div class="p-work__voices">
        <?php foreach ( $wvoice as $v ) :
          $vsc   = ymkrf_voice_score( $v->ID );
          $vcust = ymkrf_voice_customer_label( $v->ID );
          $vill  = ymkrf_voice_illust_img( $v->ID, 96 );
        ?>
        <a class="p-work__voice" href="<?php echo esc_url( get_permalink( $v ) ); ?>">
          <?php if ( $vill ) : ?><span class="p-work__voiceill"><?php echo $vill; ?></span><?php endif; ?>
          <span class="p-work__voicetxt">
            <span class="p-work__voicetop"><?php echo ymkrf_stars( $vsc ); ?></span>
            <span class="p-work__voicequote"><?php echo esc_html( ymkrf_voice_excerpt( $v->ID, 70 ) ); ?></span>
            <?php if ( $vcust ) : ?><span class="p-work__voicewho"><?php echo esc_html( $vcust ); ?></span><?php endif; ?>
          </span>
        </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ( $wcase ) : ?>
      <p class="p-work__case"><?php echo esc_html( $wcase ); ?></p>
    <?php endif; ?>

  </div>
</section>

<?php if ( $wrel ) : ?>
<section class="l-section l-section--gray">
  <div class="l-wrap">
    <h2 class="p-work__h2 p-work__h2--c">ほかの施工事例</h2>
    <div class="p-works__grid">
      <?php foreach ( $wrel as $r ) :
        $rcat  = ymkrf_works_term_names( $r->ID, 'ymkrf_works_cat' );
        $rarea = ymkrf_works_term_names( $r->ID, 'ymkrf_works_area' );
        $rpr   = trim( (string) get_post_meta( $r->ID, '_ymkrf_price', true ) );
      ?>
      <article class="p-work">
        <a class="p-work__thumb" href="<?php echo esc_url( get_permalink( $r ) ); ?>" aria-label="<?php echo esc_attr( get_the_title( $r ) ); ?>">
          <?php echo ymkrf_works_compare( $r->ID ); ?>
        </a>
        <div class="p-work__body">
          <p class="p-work__meta">
            <?php foreach ( array_slice( $rcat, 0, 2 ) as $t ) : ?><span><?php echo esc_html( $t ); ?></span><?php endforeach; ?>
            <?php foreach ( array_slice( $rarea, 0, 1 ) as $t ) : ?><span><?php echo esc_html( $t ); ?></span><?php endforeach; ?>
          </p>
          <h3 class="p-work__title">
            <a href="<?php echo esc_url( get_permalink( $r ) ); ?>"><?php echo esc_html( get_the_title( $r ) ); ?></a>
          </h3>
          <?php if ( $rpr ) : ?><p class="p-work__data"><span><?php echo esc_html( $rpr ); ?></span></p><?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="l-section l-section--soft">
  <div class="l-wrap">
    <div class="p-pagecta">
      <img class="p-pagecta__chara" src="<?php echo esc_url( $asset . '/assets/img/character/char-search-fly.webp' ); ?>"
           width="480" height="480" alt="" loading="lazy">
      <h2 class="p-pagecta__title"><span class="marker">同じような工事も、<br class="xs-only">ご相談ください</span></h2>
      <p class="p-pagecta__text">
        見積り・現地調査は無料。<br class="xs-only">しつこい営業はいたしません。
      </p>
      <?php if ( function_exists( 'ymkrf_product_cta' ) ) ymkrf_product_cta( 'works-single', true ); ?>
    </div>
  </div>
</section>

</main>

<?php endwhile; get_footer();
