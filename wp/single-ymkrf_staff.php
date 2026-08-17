<?php
/**
 * single-ymkrf_staff.php ─ スタッフ 1人のページ
 * 置き場所： wp-content/themes/ymkrf/single-ymkrf_staff.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$asset = get_stylesheet_directory_uri();
get_header();

while ( have_posts() ) : the_post();
  $id   = get_the_ID();
  $shop = ymkrf_staff_shop_name( $id );
  $sslug= (string) get_post_meta( $id, '_ymkrf_staff_shop', true );
  $role = trim( (string) get_post_meta( $id, '_ymkrf_staff_role', true ) );
  $kana = trim( (string) get_post_meta( $id, '_ymkrf_staff_kana', true ) );
  $word = trim( (string) get_post_meta( $id, '_ymkrf_staff_word', true ) );
  $lic  = trim( (string) get_post_meta( $id, '_ymkrf_staff_lic', true ) );
  $hob  = trim( (string) get_post_meta( $id, '_ymkrf_staff_hobby', true ) );
  $chg  = trim( (string) get_post_meta( $id, '_ymkrf_staff_charge', true ) );

  /* この人が担当した施工事例 */
  $wq = new WP_Query( array(
    'post_type'      => 'ymkrf_works',
    'post_status'    => 'publish',
    'posts_per_page' => 3,
    'no_found_rows'  => true,
    'meta_query'     => array( array( 'key' => '_ymkrf_staff', 'value' => $id ) ),
  ) );
  $works = $wq->posts;
  wp_reset_postdata();
?>

<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li><a href="<?php echo esc_url( get_post_type_archive_link( 'ymkrf_staff' ) ); ?>">スタッフ紹介</a></li>
    <li><?php the_title(); ?></li>
  </ol>
</nav>

<main id="main">

<div class="p-pagehead">
  <div class="l-wrap p-pagehead__inner">
    <span class="p-pagehead__en">STAFF</span>
    <div class="p-staffone__head">
      <span class="p-staffone__face"><?php echo ymkrf_staff_face( $id, 'medium' ); ?></span>
      <div>
        <?php if ( $shop || $role ) : ?>
          <p class="p-staff__shop"><?php echo esc_html( trim( $shop . ( $shop && $role ? '　' : '' ) . $role ) ); ?></p>
        <?php endif; ?>
        <h1 class="p-pagehead__title"><?php the_title(); ?></h1>
        <?php if ( $kana ) : ?><p class="p-staff__kana"><?php echo esc_html( $kana ); ?></p><?php endif; ?>
      </div>
    </div>
  </div>
</div>

<section class="l-section">
  <div class="l-wrap l-wrap--narrow">

    <?php if ( $word !== '' ) : ?>
      <h2 class="p-work__h2">ごあいさつ</h2>
      <div class="p-work__body"><p><?php echo nl2br( esc_html( $word ) ); ?></p></div>
    <?php endif; ?>

    <table class="p-staffone__spec">
      <tbody>
        <?php if ( $shop ) : ?>
          <tr><th>所属店舗</th>
            <td><?php echo esc_html( $shop ); ?></td></tr>
        <?php endif; ?>
        <?php if ( $role ) : ?>
          <tr><th>役職</th><td><?php echo esc_html( $role ); ?></td></tr>
        <?php endif; ?>
        <?php if ( $chg ) : ?>
          <tr><th>担当</th><td><?php echo esc_html( $chg ); ?></td></tr>
        <?php endif; ?>
        <?php if ( $lic ) : ?>
          <tr><th>資格</th><td><?php echo esc_html( $lic ); ?></td></tr>
        <?php endif; ?>
        <?php if ( $hob ) : ?>
          <tr><th>趣味</th><td><?php echo esc_html( $hob ); ?></td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <?php if ( $works ) : ?>
      <h2 class="p-work__h2">担当した施工事例</h2>
      <div class="p-works__grid">
        <?php foreach ( $works as $w ) :
          $wcat = ymkrf_works_term_names( $w->ID, 'ymkrf_works_cat' );
          $wpr  = trim( (string) get_post_meta( $w->ID, '_ymkrf_price', true ) );
        ?>
        <article class="p-work">
          <div class="p-work__thumb"><?php echo ymkrf_works_compare( $w->ID ); ?></div>
          <div class="p-work__body">
            <p class="p-work__meta">
              <?php foreach ( array_slice( $wcat, 0, 2 ) as $t ) : ?><span><?php echo esc_html( $t ); ?></span><?php endforeach; ?>
            </p>
            <h3 class="p-work__title">
              <a href="<?php echo esc_url( get_permalink( $w ) ); ?>"><?php echo esc_html( get_the_title( $w ) ); ?></a>
            </h3>
            <?php if ( $wpr ) : ?><p class="p-work__data"><span><?php echo esc_html( $wpr ); ?></span></p><?php endif; ?>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ( $sslug ) : ?>
      <p style="margin-top:26px;text-align:center">
        <a class="p-staff__fbtn"
           href="<?php echo esc_url( add_query_arg( 'shop', $sslug, get_post_type_archive_link( 'ymkrf_staff' ) ) ); ?>">
          <?php echo esc_html( $shop ); ?>のスタッフを見る
        </a>
      </p>
    <?php endif; ?>

  </div>
</section>

<section class="l-section l-section--soft">
  <div class="l-wrap">
    <div class="p-pagecta">
      <img class="p-pagecta__chara" src="<?php echo esc_url( $asset . '/assets/img/character/char-search-fly.webp' ); ?>"
           width="480" height="480" alt="" loading="lazy">
      <h2 class="p-pagecta__title"><span class="marker">お気軽にご相談ください</span></h2>
      <p class="p-pagecta__text">
        見積り・現地調査は無料。<br class="xs-only">しつこい営業はいたしません。
      </p>
      <?php if ( function_exists( 'ymkrf_product_cta' ) ) ymkrf_product_cta( 'staff-single', true ); ?>
    </div>
  </div>
</section>

</main>

<?php endwhile; get_footer();
