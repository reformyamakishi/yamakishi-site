<?php
/**
 * front-page.php ─ トップページ（リフォームヤマキシ）
 *
 * index.html のうち、更新が発生する 3 か所だけを WordPress のループに置き換えたものです。
 * それ以外の HTML は index.html とまったく同じなので、
 * このファイルの ★1〜★3 だけを差し替えれば動きます。
 *
 * 【必要な準備】
 *   functions.php に wp/functions-snippet.php の内容を追記
 *   → カスタム投稿タイプ works（施工事例）／ voice（お客様の声）が登録されます
 *   → お知らせは通常の投稿（post）を使います
 *
 * 【画像パスの書き換え】
 *   index.html の  src="assets/img/..."
 *   → PHP では  src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/assets/img/..."
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$asset = get_stylesheet_directory_uri();
get_header();
?>

<main id="main">

<?php /* ── ヒーロー 〜 選ばれる理由 までは index.html をそのまま貼り付け ── */ ?>


<!-- ★1 ==================================================================
     施工事例（カスタム投稿タイプ works）
     ================================================================== -->
<section class="l-section l-section--soft" id="works">
  <div class="l-wrap">
    <div class="c-head" data-reveal>
      <span class="c-head__en">WORKS</span>
      <h2 class="c-head__title">施工事例</h2>
      <p class="c-head__lead">石川・福井の実際のお宅で、どう変わったか。金額も公開しています。</p>
    </div>

    <?php
    $works = new WP_Query( array(
      'post_type'      => 'works',
      'posts_per_page' => 3,
      'no_found_rows'  => true,   // ページャ不要なので高速化
    ) );

    if ( $works->have_posts() ) : ?>
      <div class="p-works__grid">
        <?php
        $i = 0;
        while ( $works->have_posts() ) : $works->the_post();
          $i++;
          $cats   = get_the_terms( get_the_ID(), 'works_cat' );
          $areas  = get_the_terms( get_the_ID(), 'works_area' );
          $price  = get_post_meta( get_the_ID(), 'price', true );
          $period = get_post_meta( get_the_ID(), 'period', true );
          $before = get_post_meta( get_the_ID(), 'before_img', true );
        ?>
        <article class="p-work" data-reveal data-reveal-delay="<?php echo esc_attr( ( $i - 1 ) * 80 ); ?>">

          <div class="p-compare" data-compare style="--pos:50%">
            <div class="p-compare__layer p-compare__layer--before">
              <?php
              if ( $before ) {
                echo wp_get_attachment_image( (int) $before, 'large', false, array(
                  'alt'     => esc_attr( get_the_title() . '（施工前）' ),
                  'loading' => 'lazy',
                ) );
              } else { ?>
                <p class="p-compare__ph">BEFORE 写真</p>
              <?php } ?>
            </div>

            <div class="p-compare__layer p-compare__layer--after">
              <?php
              if ( has_post_thumbnail() ) {
                the_post_thumbnail( 'large', array(
                  'alt'     => esc_attr( get_the_title() . '（施工後）' ),
                  'loading' => 'lazy',
                ) );
              } else { ?>
                <p class="p-compare__ph">AFTER 写真</p>
              <?php } ?>
            </div>

            <span class="p-compare__tag p-compare__tag--before">BEFORE</span>
            <span class="p-compare__tag p-compare__tag--after">AFTER</span>
            <span class="p-compare__handle"></span>
            <span class="p-compare__hint">← 左右に動かして見くらべる →</span>
          </div>

          <div class="p-work__body">
            <p class="p-work__meta">
              <?php if ( $cats  && ! is_wp_error( $cats ) )  echo '<span>' . esc_html( $cats[0]->name )  . '</span>'; ?>
              <?php if ( $areas && ! is_wp_error( $areas ) ) echo '<span>' . esc_html( $areas[0]->name ) . '</span>'; ?>
            </p>
            <h3 class="p-work__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
            <?php if ( $price || $period ) : ?>
              <p class="p-work__data">
                <?php if ( $price )  echo '<span>工事費込み ' . esc_html( $price ) . '</span>'; ?>
                <?php if ( $period ) echo '<span>工期 ' . esc_html( $period ) . '</span>'; ?>
              </p>
            <?php endif; ?>
          </div>

        </article>
        <?php endwhile; ?>
      </div>
    <?php endif; wp_reset_postdata(); ?>

    <a class="c-more" href="<?php echo esc_url( get_post_type_archive_link( 'works' ) ); ?>">施工事例をもっと見る</a>
  </div>
</section>


<!-- ★2 ==================================================================
     お客様の声（カスタム投稿タイプ voice）
     ================================================================== -->
<section class="l-section" id="voice">
  <div class="l-wrap">
    <div class="c-head" data-reveal>
      <span class="c-head__en">VOICE</span>
      <h2 class="c-head__title">お客様の声</h2>
      <p class="c-head__lead">工事のあとに、実際にいただいたご感想です。</p>
    </div>

    <?php
    $voices = new WP_Query( array(
      'post_type'      => 'voice',
      'posts_per_page' => 3,
      'no_found_rows'  => true,
    ) );

    if ( $voices->have_posts() ) : ?>
      <div class="p-voice__grid">
        <?php
        $i = 0;
        while ( $voices->have_posts() ) : $voices->the_post();
          $i++;
          $who  = get_post_meta( get_the_ID(), 'customer', true );
          $star = (int) get_post_meta( get_the_ID(), 'star', true );
          $star = ( $star >= 1 && $star <= 5 ) ? $star : 5;
          $initial = $who ? mb_substr( wp_strip_all_tags( $who ), 0, 1 ) : '—';
        ?>
        <div class="p-voice__card" data-reveal data-reveal-delay="<?php echo esc_attr( ( $i - 1 ) * 80 ); ?>">
          <p class="p-voice__stars" aria-label="5段階中<?php echo esc_attr( $star ); ?>">
            <?php echo esc_html( str_repeat( '★', $star ) . str_repeat( '☆', 5 - $star ) ); ?>
          </p>
          <p class="p-voice__text"><?php echo esc_html( get_the_excerpt() ); ?></p>
          <p class="p-voice__who">
            <span class="p-voice__avatar"><?php echo esc_html( $initial ); ?></span>
            <?php echo esc_html( $who ); ?>
          </p>
        </div>
        <?php endwhile; ?>
      </div>
    <?php endif; wp_reset_postdata(); ?>

    <a class="c-more" href="<?php echo esc_url( get_post_type_archive_link( 'voice' ) ); ?>">お客様の声をもっと見る</a>
  </div>
</section>


<?php /* ── リフォームの流れ／店舗・エリア／よくある質問 は index.html をそのまま ── */ ?>


<!-- ★3 ==================================================================
     お知らせ（通常の投稿）
     ================================================================== -->
<section class="l-section" id="news">
  <div class="l-wrap">
    <div class="c-head" data-reveal>
      <span class="c-head__en">NEWS</span>
      <h2 class="c-head__title">お知らせ・イベント情報</h2>
    </div>

    <?php
    $news = new WP_Query( array(
      'post_type'      => 'post',
      'posts_per_page' => 5,
      'no_found_rows'  => true,
    ) );

    if ( $news->have_posts() ) : ?>
      <ul class="p-news" data-reveal>
        <?php while ( $news->have_posts() ) : $news->the_post();
          $cat = get_the_category();
        ?>
        <li class="p-news__item">
          <a class="p-news__link" href="<?php the_permalink(); ?>">
            <time class="p-news__date" datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>">
              <?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?>
            </time>
            <?php if ( ! empty( $cat ) ) : ?>
              <span class="p-news__cat"><?php echo esc_html( $cat[0]->name ); ?></span>
            <?php endif; ?>
            <span class="p-news__title"><?php the_title(); ?></span>
          </a>
        </li>
        <?php endwhile; ?>
      </ul>
    <?php else : ?>
      <p style="text-align:center;color:var(--ink-sub)">お知らせはまだありません。</p>
    <?php endif; wp_reset_postdata(); ?>

    <a class="c-more" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>">お知らせ一覧へ</a>
  </div>
</section>


<?php /* ── 最終CTA は index.html をそのまま ── */ ?>

</main>

<?php get_footer();
