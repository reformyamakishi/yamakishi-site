<?php
/**
 * お知らせ一覧 ─ リフォームヤマキシ
 *
 * 置き場所： wp-content/themes/ymkrf/archive-ymkrf_news.php
 * URL      ： /news/  （/news/?cat=hojokin で種類をしぼり込み）
 *
 * ★内容を直すとき
 *   お知らせの中身は、ダッシュボードの「お知らせ」から入れてください。
 *   このファイルは、並べかたと見た目だけです。
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$asset = get_stylesheet_directory_uri();

$now  = isset( $_GET['cat'] ) ? sanitize_title( wp_unslash( $_GET['cat'] ) ) : '';
$cats = get_terms( array( 'taxonomy' => 'ymkrf_news_cat', 'hide_empty' => true ) );
if ( is_wp_error( $cats ) ) $cats = array();

$term = $now ? get_term_by( 'slug', $now, 'ymkrf_news_cat' ) : null;
$tname = ( $term && ! is_wp_error( $term ) ) ? $term->name : '';

get_header();
?>

<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <?php if ( $tname ) : ?>
      <li><a href="<?php echo esc_url( get_post_type_archive_link( 'ymkrf_news' ) ); ?>">お知らせ</a></li>
      <li><?php echo esc_html( $tname ); ?></li>
    <?php else : ?>
      <li>お知らせ</li>
    <?php endif; ?>
  </ol>
</nav>

<main id="main">

<div class="p-pagehead">
  <div class="l-wrap p-pagehead__inner">
    <img class="p-pagehead__chara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-flag.webp"
         width="503" height="640" alt="" loading="lazy" decoding="async">
    <span class="p-pagehead__en">NEWS</span>
    <h1 class="p-pagehead__title">お知らせ</h1>
    <p class="p-pagehead__lead">
      新しいお店のオープンや<b>補助金</b>のこと、<br class="xs-only">
      営業時間のお知らせなどをお届けします。
    </p>
  </div>
</div>

<section class="l-section">
  <div class="l-wrap l-wrap--narrow">

    <?php if ( $cats ) : ?>
      <nav class="p-newsnav" aria-label="お知らせの種類でしぼり込む">
        <a class="p-newsnav__btn<?php echo $now === '' ? ' is-on' : ''; ?>"
           href="<?php echo esc_url( get_post_type_archive_link( 'ymkrf_news' ) ); ?>">すべて</a>
        <?php foreach ( $cats as $c ) : ?>
          <a class="p-newsnav__btn<?php echo $now === $c->slug ? ' is-on' : ''; ?>"
             href="<?php echo esc_url( add_query_arg( 'cat', $c->slug, get_post_type_archive_link( 'ymkrf_news' ) ) ); ?>"><?php
            echo esc_html( $c->name ); ?></a>
        <?php endforeach; ?>
      </nav>
    <?php endif; ?>

    <?php
    /* 「重要」を上に、そのあと新しい順に並べます */
    $paged = max( 1, (int) get_query_var( 'paged' ) );
    $args  = array(
      'post_type'      => 'ymkrf_news',
      'post_status'    => 'publish',
      'posts_per_page' => 20,
      'paged'          => $paged,
      'meta_key'       => '_ymkrf_news_pin',
      'orderby'        => array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ),
    );
    if ( $now !== '' ) {
      $args['tax_query'] = array( array(
        'taxonomy' => 'ymkrf_news_cat', 'field' => 'slug', 'terms' => $now,
      ) );
    }
    $q = new WP_Query( $args );
    ?>

    <?php if ( $q->have_posts() ) : ?>
      <ul class="p-news p-news--page" data-reveal>
        <?php while ( $q->have_posts() ) : $q->the_post(); ?>
          <?php ymkrf_news_row( get_the_ID() ); ?>
        <?php endwhile; ?>
      </ul>

      <?php
      $links = paginate_links( array(
        'total'     => $q->max_num_pages,
        'current'   => $paged,
        'prev_text' => '前へ',
        'next_text' => '次へ',
        'type'      => 'array',
      ) );
      if ( $links ) : ?>
        <nav class="p-pager" aria-label="ページ送り">
          <?php foreach ( $links as $l ) echo '<span>' . wp_kses_post( $l ) . '</span>'; ?>
        </nav>
      <?php endif; ?>

    <?php else : ?>
      <p class="p-news__none">
        <?php echo $tname ? esc_html( $tname ) . 'の' : ''; ?>お知らせは、いまのところありません。
      </p>
    <?php endif; wp_reset_postdata(); ?>

  </div>
</section>

<!-- =========== 関連ページ =========== -->
<section class="l-section l-section--soft">
  <div class="l-wrap l-wrap--narrow">
    <div class="c-head">
      <h2 class="c-head__title">あわせて<span class="marker">ご覧ください</span></h2>
    </div>
    <ul class="p-lprel">
      <li><a href="<?php echo esc_url( home_url( '/shops/' ) ); ?>">店舗・対応エリア</a><span>石川県・福井県に11店舗</span></li>
      <li><a href="<?php echo esc_url( home_url( '/products/' ) ); ?>">商品・価格</a><span>工事費込みのパック価格</span></li>
      <li><a href="<?php echo esc_url( home_url( '/works/' ) ); ?>">施工事例</a><span>Before・Afterと、かかった費用</span></li>
      <li><a href="<?php echo esc_url( home_url( '/column/' ) ); ?>">お役立ち情報</a><span>リフォームで迷いやすいところ</span></li>
    </ul>
  </div>
</section>

</main>

<?php get_footer();
