<?php
/**
 * archive-ymkrf_staff.php ─ スタッフ 一覧
 * 置き場所： wp-content/themes/ymkrf/archive-ymkrf_staff.php
 *
 * /staff/          … 全員（店舗ごとにまとめて出します）
 * /staff/?shop=xxx … その店舗だけ
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$asset = get_stylesheet_directory_uri();

$shops = ymkrf_staff_shops();   /* 本部 → 各店舗 の順 */

$now   = isset( $_GET['shop'] ) ? sanitize_key( $_GET['shop'] ) : '';
$all   = ymkrf_staff_list();

/* 店舗ごとに分けます */
$by = array();
foreach ( $all as $st ) {
	$k = (string) get_post_meta( $st->ID, '_ymkrf_staff_shop', true );
	if ( $k === '' ) $k = 'other';
	$by[ $k ][] = $st;
}

get_header(); ?>

<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li>スタッフ紹介</li>
  </ol>
</nav>

<main id="main">

<div class="p-pagehead">
  <div class="l-wrap p-pagehead__inner">
    <img class="p-pagehead__chara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-smile.webp"
         width="592" height="640" alt="" loading="lazy" decoding="async">
    <span class="p-pagehead__en">STAFF</span>
    <h1 class="p-pagehead__title">スタッフ紹介</h1>
    <p class="p-pagehead__lead">
      お住まいのご相談を、<br class="xs-only">お受けするスタッフです。<br>
      どんな小さなことでも、<br class="xs-only">お気軽にお声かけください。
    </p>
  </div>
</div>

<?php if ( $all && $shops ) : ?>
<section class="l-section l-section--tight">
  <div class="l-wrap">
    <nav class="p-staff__filter" aria-label="店舗でさがす">
      <p class="p-staff__filterttl">店舗でさがす</p>
      <ul class="p-staff__filterlist">
        <li><a class="p-staff__fbtn<?php echo ( $now === '' ? ' is-current' : '' ); ?>"
               href="<?php echo esc_url( get_post_type_archive_link( 'ymkrf_staff' ) ); ?>">すべて</a></li>
        <?php foreach ( $shops as $sslug => $sname ) :
          if ( empty( $by[ $sslug ] ) ) continue; ?>
          <li>
            <a class="p-staff__fbtn<?php echo ( $now === $sslug ? ' is-current' : '' ); ?>"
               href="<?php echo esc_url( add_query_arg( 'shop', $sslug, get_post_type_archive_link( 'ymkrf_staff' ) ) ); ?>">
              <?php echo esc_html( $sname ); ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </nav>
  </div>
</section>
<?php endif; ?>

<section class="l-section">
  <div class="l-wrap">

    <?php if ( ! $all ) : ?>
      <p class="p-staff__empty">
        ただいま準備中です。<br>
        もうしばらくお待ちください。
      </p>
    <?php else :

      /* 出す順番は、店舗の登録順です */
      $order = array_keys( $shops );
      $order[] = 'other';

      foreach ( $order as $slug ) :
        if ( empty( $by[ $slug ] ) ) continue;
        if ( $now !== '' && $now !== $slug ) continue;

        $sname = isset( $shops[ $slug ] ) ? $shops[ $slug ] : 'そのほか';
      ?>
      <div class="p-staff__group">
        <h2 class="p-staff__groupttl"><?php echo esc_html( $sname ); ?></h2>
        <div class="p-staff__grid">
          <?php foreach ( $by[ $slug ] as $st ) :
            $role = trim( (string) get_post_meta( $st->ID, '_ymkrf_staff_role', true ) );
            $kana = trim( (string) get_post_meta( $st->ID, '_ymkrf_staff_kana', true ) );
            $word = trim( (string) get_post_meta( $st->ID, '_ymkrf_staff_word', true ) );
            $lic  = trim( (string) get_post_meta( $st->ID, '_ymkrf_staff_lic', true ) );
            $hob  = trim( (string) get_post_meta( $st->ID, '_ymkrf_staff_hobby', true ) );
            $chg  = trim( (string) get_post_meta( $st->ID, '_ymkrf_staff_charge', true ) );
          ?>
          <a class="p-staff" href="<?php echo esc_url( get_permalink( $st ) ); ?>">
            <span class="p-staff__face"><?php echo ymkrf_staff_face( $st->ID, 'medium' ); ?></span>
            <span class="p-staff__txt">
              <?php if ( $role ) : ?><span class="p-staff__role"><?php echo esc_html( $role ); ?></span><?php endif; ?>
              <span class="p-staff__name"><?php echo esc_html( get_the_title( $st ) ); ?></span>
              <?php if ( $kana ) : ?><span class="p-staff__kana"><?php echo esc_html( $kana ); ?></span><?php endif; ?>
              <?php if ( $word ) : ?><span class="p-staff__word"><?php echo esc_html( wp_trim_words( $word, 60, '…' ) ); ?></span><?php endif; ?>
              <?php if ( $chg ) : ?><span class="p-staff__lic">担当：<?php echo esc_html( $chg ); ?></span><?php endif; ?>
              <?php if ( $lic ) : ?><span class="p-staff__lic">資格：<?php echo esc_html( $lic ); ?></span><?php endif; ?>
              <?php if ( $hob ) : ?><span class="p-staff__lic">趣味：<?php echo esc_html( $hob ); ?></span><?php endif; ?>
            </span>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>

  </div>
</section>

<section class="l-section l-section--soft">
  <div class="l-wrap">
    <div class="p-pagecta">
      <img class="p-pagecta__chara" src="<?php echo esc_url( $asset . '/assets/img/character/char-search-fly.webp' ); ?>"
           width="480" height="480" alt="" loading="lazy">
      <h2 class="p-pagecta__title"><span class="marker">まずは、ご相談ください</span></h2>
      <p class="p-pagecta__text">
        見積り・現地調査は無料。<br class="xs-only">しつこい営業はいたしません。
      </p>
      <?php if ( function_exists( 'ymkrf_product_cta' ) ) ymkrf_product_cta( 'staff-archive', true ); ?>
    </div>
  </div>
</section>

</main>

<?php get_footer();
