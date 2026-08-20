<?php
/**
 * コラム記事 ─ リフォームヤマキシ
 *
 * 置き場所： wp-content/themes/ymkrf/single-ymkrf_column.php
 * URL      ： /column/<記事のスラッグ>/
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$terms   = get_the_terms( get_the_ID(), 'ymkrf_product_cat' );
$term    = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
$catname = $term ? $term->name : '';
$catslug = $term ? $term->slug : '';

get_header();
while ( have_posts() ) : the_post();
?>

<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li><a href="<?php echo esc_url( get_post_type_archive_link( 'ymkrf_column' ) ); ?>">お役立ち情報</a></li>
    <li><?php the_title(); ?></li>
  </ol>
</nav>

<main id="main">

<article class="p-colart">

  <div class="p-colart__head">
    <div class="l-wrap l-wrap--narrow">
      <p class="p-colart__meta">
        <?php if ( $catname ) : ?><span class="p-col__tag p-col__tag--solid"><?php echo esc_html( $catname ); ?></span><?php endif; ?>
        <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?></time>
      </p>
      <h1 class="p-colart__title"><?php the_title(); ?></h1>
    </div>
  </div>

  <?php if ( has_post_thumbnail() ) : ?>
    <div class="l-wrap l-wrap--narrow">
      <div class="p-colart__ph"><?php the_post_thumbnail( 'large', array( 'alt' => '' ) ); ?></div>
    </div>
  <?php endif; ?>

  <div class="l-wrap l-wrap--narrow">
    <div class="p-colart__body"><?php the_content(); ?></div>
    <?php if ( function_exists( 'ymkrf_column_writer' ) ) ymkrf_column_writer(); ?>
  </div>

</article>

<?php
if ( function_exists( 'ymkrf_product_cta' ) ) {
	echo '<section class="l-section"><div class="l-wrap l-wrap--narrow">';
	ymkrf_product_cta( 'column-single', true );
	echo '</div></section>';
}
?>

<?php
/* 同じカテゴリのほかの記事 */
$rel = ymkrf_column_query( $catslug, 4 );
$out = array();
if ( $rel->have_posts() ) {
	while ( $rel->have_posts() ) { $rel->the_post(); if ( get_the_ID() !== $post->ID ) $out[] = get_the_ID(); }
	wp_reset_postdata();
}
if ( $out ) :
?>
<section class="l-section l-section--soft">
  <div class="l-wrap">
    <h2 class="p-prd__bar">ほかのお役立ち情報</h2>
    <div class="p-col__cards">
      <?php foreach ( array_slice( $out, 0, 3 ) as $id ) :
        $GLOBALS['post'] = get_post( $id ); setup_postdata( $GLOBALS['post'] );
        ymkrf_column_card();
      endforeach; wp_reset_postdata(); ?>
    </div>

    <?php if ( $catslug ) : ?>
      <p class="p-col__allwrap">
        <a class="p-col__all" href="<?php echo esc_url( add_query_arg( 'ymkrf_product_cat', $catslug, get_post_type_archive_link( 'ymkrf_column' ) ) ); ?>">
          <?php echo esc_html( $catname ); ?>リフォームコラム一覧へ
        </a>
      </p>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

</main>

<?php endwhile; get_footer();
