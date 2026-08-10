<?php
/**
 * コラム一覧 ─ リフォームヤマキシ
 *
 * 置き場所： wp-content/themes/ymkrf/archive-ymkrf_column.php
 * URL      ： /column/  （/column/?ymkrf_product_cat=kitchen で絞り込み）
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$catslug = isset( $_GET['ymkrf_product_cat'] ) ? sanitize_title( wp_unslash( $_GET['ymkrf_product_cat'] ) ) : '';
$catterm = $catslug ? get_term_by( 'slug', $catslug, 'ymkrf_product_cat' ) : null;
$catname = ( $catterm && ! is_wp_error( $catterm ) ) ? $catterm->name : '';

get_header();
?>

<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <?php if ( $catname ) : ?>
      <li><a href="<?php echo esc_url( get_post_type_archive_link( 'ymkrf_column' ) ); ?>">お役立ち情報</a></li>
      <li><?php echo esc_html( $catname ); ?></li>
    <?php else : ?>
      <li>お役立ち情報</li>
    <?php endif; ?>
  </ol>
</nav>

<main id="main">

<div class="p-pagehead">
  <div class="l-wrap p-pagehead__inner">
    <span class="p-pagehead__en">COLUMN</span>
    <h1 class="p-pagehead__title"><?php echo esc_html( $catname ); ?>リフォームお役立ち情報</h1>
    <p class="p-pagehead__lead">
      リフォームで迷いやすいところを、ヤマキシのスタッフがかみくだいてご説明します。
    </p>
  </div>
</div>

<section class="l-section">
  <div class="l-wrap">

    <?php if ( have_posts() ) : ?>
      <div class="p-col__cards">
        <?php while ( have_posts() ) : the_post(); ymkrf_column_card(); endwhile; ?>
      </div>

      <div class="p-col__pager">
        <?php echo paginate_links( array( 'prev_text' => '前へ', 'next_text' => '次へ' ) ); ?>
      </div>

    <?php else : ?>
      <p class="p-col__empty">記事はまだありません。準備ができ次第、順に公開していきます。</p>
    <?php endif; ?>

  </div>
</section>

<?php
if ( function_exists( 'ymkrf_product_cta' ) ) {
	echo '<section class="l-section l-section--soft"><div class="l-wrap l-wrap--narrow">';
	ymkrf_product_cta( 'column-archive', true );
	echo '</div></section>';
}
?>

</main>

<?php get_footer();
