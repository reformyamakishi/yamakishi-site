<?php
/**
 * index.php ─ ほかに合うテンプレートが無いときの受け皿
 * リフォームヤマキシテーマ
 *
 * ※WordPressはテーマに index.php が無いと「不完全」と判定して
 * 　有効化させてくれません。そのため必ず必要です。
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>
<main id="main" class="l-main">
  <div class="l-container">

    <?php if ( have_posts() ) : ?>

      <?php if ( is_archive() || is_home() || is_search() ) : ?>
        <h1 class="c-title"><?php
          if ( is_search() ) {
            echo '「' . esc_html( get_search_query() ) . '」の検索結果';
          } elseif ( is_archive() ) {
            echo esc_html( wp_strip_all_tags( get_the_archive_title() ) );
          } else {
            echo 'お知らせ';
          }
        ?></h1>
      <?php endif; ?>

      <div class="p-cardlist">
        <?php while ( have_posts() ) : the_post(); ?>
          <?php if ( is_singular() ) : ?>
            <article class="c-entry">
              <h1 class="c-title"><?php the_title(); ?></h1>
              <div class="c-entry__body"><?php the_content(); ?></div>
            </article>
          <?php else : ?>
            <a class="p-card" href="<?php the_permalink(); ?>">
              <?php if ( has_post_thumbnail() ) : ?>
                <div class="p-card__photo"><?php the_post_thumbnail( 'medium_large' ); ?></div>
              <?php endif; ?>
              <div class="p-card__body">
                <h2 class="p-card__title"><?php the_title(); ?></h2>
                <p class="p-card__text"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 60, '…' ) ); ?></p>
              </div>
            </a>
          <?php endif; ?>
        <?php endwhile; ?>
      </div>

      <?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => '前へ', 'next_text' => '次へ' ) ); ?>

    <?php else : ?>
      <h1 class="c-title">ページが見つかりません</h1>
      <p>お探しのページは移動または削除された可能性があります。</p>
      <p><a class="c-btn c-btn--main" href="<?php echo esc_url( home_url( '/' ) ); ?>">トップページへ戻る</a></p>
    <?php endif; ?>

  </div>
</main>
<?php get_footer();
