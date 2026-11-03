<?php
/**
 * page.php ─ 固定ページ（ふつうのページ）
 * リフォームヤマキシテーマ
 *
 * 2026/11/03 ユーザー「他の固定ページも同様なスタイルにして」
 *   協力業者さん募集のページと同じく、
 *   パンくず → 題名の帯（p-pagehead） → 本文（お知らせと同じ読みやすい見た目：.p-pagebody）
 *   の順に出します。本文はダッシュボード「固定ページ」で、お知らせと同じ書きかたで直せます。
 *   このページだけ見た目を変えたいときは、編集画面の「このページだけのCSS」へ。
 *
 * ※専用の作りのページ（会社概要・プライバシーポリシー・協力業者さん募集など）は、
 *   それぞれのファイルで出しているので、ここは使いません。
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>

<?php while ( have_posts() ) : the_post(); ?>

<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <?php foreach ( array_reverse( get_post_ancestors( get_the_ID() ) ) as $anc ) : ?>
      <li><a href="<?php echo esc_url( get_permalink( $anc ) ); ?>"><?php echo esc_html( get_the_title( $anc ) ); ?></a></li>
    <?php endforeach; ?>
    <li><?php the_title(); ?></li>
  </ol>
</nav>

<main id="main">

<div class="p-pagehead">
  <div class="l-wrap p-pagehead__inner">
    <h1 class="p-pagehead__title"><?php the_title(); ?></h1>
  </div>
</div>

<section class="l-section">
  <div class="l-wrap">
    <?php if ( has_post_thumbnail() ) : ?>
      <figure class="p-pagebody__eyecatch"><?php the_post_thumbnail( 'large' ); ?></figure>
    <?php endif; ?>
    <div class="p-pagebody">
      <?php the_content(); ?>
    </div>
  </div>
</section>

</main>

<?php endwhile; ?>

<?php get_footer();
