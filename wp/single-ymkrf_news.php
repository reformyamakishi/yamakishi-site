<?php
/**
 * お知らせ 1件ずつのページ ─ リフォームヤマキシ
 *
 * 置き場所： wp-content/themes/ymkrf/single-ymkrf_news.php
 * URL      ： /news/<記事のスラッグ>/
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$asset = get_stylesheet_directory_uri();
get_header();

while ( have_posts() ) : the_post();
	$id   = get_the_ID();
	$cat  = ymkrf_news_cat_of( $id );
	$shop = ymkrf_news_shop_name( $id );
	$pin  = get_post_meta( $id, '_ymkrf_news_pin', true ) === '1';
?>

<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li><a href="<?php echo esc_url( get_post_type_archive_link( 'ymkrf_news' ) ); ?>">お知らせ</a></li>
    <li><?php echo esc_html( get_the_title() ); ?></li>
  </ol>
</nav>

<main id="main">

<article class="l-section">
  <div class="l-wrap l-wrap--narrow">

    <header class="p-newshead">
      <p class="p-newshead__meta">
        <time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>"><?php
          echo esc_html( get_the_date( 'Y.m.d' ) ); ?></time>
        <?php if ( $cat['slug'] !== '' ) : ?>
          <a class="p-news__cat p-news__cat--<?php echo esc_attr( $cat['slug'] ); ?>"
             href="<?php echo esc_url( add_query_arg( 'cat', $cat['slug'], get_post_type_archive_link( 'ymkrf_news' ) ) ); ?>"><?php
            echo esc_html( $cat['name'] ); ?></a>
        <?php endif; ?>
        <?php if ( $pin ) : ?><span class="p-news__pin">重要</span><?php endif; ?>
      </p>
      <h1 class="p-newshead__title"><?php the_title(); ?></h1>
      <?php if ( $shop !== '' ) : ?>
        <p class="p-newshead__shop">
          <a href="<?php echo esc_url( home_url( '/shops/' ) ); ?>"><?php echo esc_html( $shop ); ?>のお知らせ</a>
        </p>
      <?php endif; ?>
    </header>

    <?php if ( has_post_thumbnail() ) : ?>
      <figure class="p-newsimg"><?php the_post_thumbnail( 'large' ); ?></figure>
    <?php endif; ?>

    <div class="p-newsbody">
      <?php the_content(); ?>
    </div>

    <p class="p-newsback">
      <a href="<?php echo esc_url( get_post_type_archive_link( 'ymkrf_news' ) ); ?>">お知らせ一覧にもどる</a>
    </p>

  </div>
</article>

<!-- =========== ほかのお知らせ =========== -->
<?php
$others = new WP_Query( array(
  'post_type'      => 'ymkrf_news',
  'posts_per_page' => 5,
  'post__not_in'   => array( $id ),
  'no_found_rows'  => true,
) );
if ( $others->have_posts() ) : ?>
<section class="l-section l-section--soft">
  <div class="l-wrap l-wrap--narrow">
    <div class="c-head">
      <h2 class="c-head__title">ほかの<span class="marker">お知らせ</span></h2>
    </div>
    <ul class="p-news p-news--page" data-reveal>
      <?php while ( $others->have_posts() ) : $others->the_post(); ?>
        <?php ymkrf_news_row( get_the_ID() ); ?>
      <?php endwhile; ?>
    </ul>
  </div>
</section>
<?php endif; wp_reset_postdata(); ?>

<!-- =========== CTA =========== -->
<section class="l-section">
  <div class="l-wrap l-wrap--narrow">
    <div class="p-lpcta">
      <img class="p-lpcta__chara" src="<?php echo $asset; ?>/assets/img/character/char-stand.webp" width="503" height="640"
           alt="" loading="lazy" decoding="async">
      <h2 class="p-lpcta__title">気になることが<span class="marker">あれば</span></h2>
      <p class="p-lpcta__text">
        見積り・現地調査は無料です。しつこい営業は一切いたしません。<br>
        「うちの場合はどうなの？」というご質問だけでも歓迎です。
      </p>
      <div class="p-lpcta__btns">
        <a class="c-btn c-btn--line c-btn--block" href="https://lin.ee/UJZuSTrz" rel="noopener" data-cta="news-cta">
          <span class="c-btn__label">LINEで無料見積り<span class="c-btn__sub">ご相談だけでもOK・24時間受付</span></span>
        </a>
        <a class="c-btn c-btn--ghost c-btn--block" href="tel:0800-777-3331" data-cta="news-cta">
          <span class="c-btn__label">0800-777-3331<span class="c-btn__sub">通話無料・受付 9:00〜17:00</span></span>
        </a>
      </div>
    </div>
  </div>
</section>

</main>

<?php
endwhile;
get_footer();
