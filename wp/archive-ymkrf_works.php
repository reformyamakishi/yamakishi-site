<?php
/**
 * archive-ymkrf_works.php ─ 施工事例 一覧
 * 置き場所： wp-content/themes/ymkrf/archive-ymkrf_works.php
 *
 * このファイルは3つのURLで使われます。
 *   /works/            … ぜんぶ
 *   /works/kitchen/    … 部位ごと
 *   /works-area/kanazawa/ … エリアごと
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$asset = get_stylesheet_directory_uri();

/* いまえらばれているしぼり込み。部位とエリアは両方いっぺんにえらべます。
     /works/kitchen/                … 部位だけ
     /works-area/kanazawa/          … エリアだけ
     /works/kitchen/?area=kanazawa  … 両方 */
$wcat  = function_exists( 'ymkrf_works_sel_cat' )  ? ymkrf_works_sel_cat()  : '';
$warea = function_exists( 'ymkrf_works_sel_area' ) ? ymkrf_works_sel_area() : '';

$wcatT  = $wcat  !== '' ? get_term_by( 'slug', $wcat,  'ymkrf_works_cat' )  : null;
$wareaT = $warea !== '' ? get_term_by( 'slug', $warea, 'ymkrf_works_area' ) : null;
$wcatN  = ( $wcatT  && ! is_wp_error( $wcatT ) )  ? $wcatT->name  : '';
$wareaN = ( $wareaT && ! is_wp_error( $wareaT ) ) ? $wareaT->name : '';

/* 見出しに出す言葉（エリア＋部位） */
$wname = trim( $wareaN . ( $wareaN !== '' && $wcatN !== '' ? 'の' : '' ) . $wcatN );

/* しぼり込みボタン用（件数が0のものは出しません） */
$wcats  = get_terms( array( 'taxonomy' => 'ymkrf_works_cat',  'hide_empty' => true ) );
$wareas = get_terms( array( 'taxonomy' => 'ymkrf_works_area', 'hide_empty' => true ) );
if ( is_wp_error( $wcats ) )  $wcats  = array();
if ( is_wp_error( $wareas ) ) $wareas = array();

/* 片方をえらんでいるときは、その中での件数にします */
$wcatCount  = function_exists( 'ymkrf_works_term_counts' )
	? ymkrf_works_term_counts( 'ymkrf_works_cat',  '', $warea ) : array();
$wareaCount = function_exists( 'ymkrf_works_term_counts' )
	? ymkrf_works_term_counts( 'ymkrf_works_area', $wcat, '' ) : array();

get_header(); ?>

<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <?php if ( $wname !== '' ) : ?>
      <li><a href="<?php echo esc_url( get_post_type_archive_link( 'ymkrf_works' ) ); ?>">施工事例</a></li>
      <li><?php echo esc_html( $wname ); ?></li>
    <?php else : ?>
      <li>施工事例</li>
    <?php endif; ?>
  </ol>
</nav>

<main id="main">

<div class="p-pagehead">
  <div class="l-wrap p-pagehead__inner">
    <img class="p-pagehead__chara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-hinshitsu.webp"
         width="592" height="640" alt="" loading="lazy" decoding="async">
    <span class="p-pagehead__en">WORKS</span>
    <?php if ( $wname !== '' ) : ?>
      <h1 class="p-pagehead__title"><?php echo esc_html( $wname ); ?>の施工事例</h1>
      <p class="p-pagehead__lead">
        <?php echo esc_html( $wname ); ?>の工事を、<br class="xs-only">写真と金額でご覧いただけます。<br>
        実際に石川・福井のお宅で<br class="xs-only">させていただいた工事です。
      </p>
    <?php else : ?>
      <h1 class="p-pagehead__title">施工事例</h1>
      <p class="p-pagehead__lead">
        石川・福井の実際のお宅で、<br class="xs-only">どう変わったか。<br>
        工事費と工期も、あわせて<br class="xs-only">公開しています。
      </p>
    <?php endif; ?>
  </div>
</div>

<?php if ( $wcats || $wareas ) : ?>
<section class="l-section l-section--tight">
  <div class="l-wrap">

    <?php /* 部位とエリアは、両方いっぺんにえらべます。
             えらんだボタンをもう一度押すと、そのしぼり込みだけ外れます。 */ ?>
    <?php if ( $wcats ) : ?>
    <nav class="p-works__filter" aria-label="部位でさがす">
      <p class="p-works__filterttl">部位でさがす</p>
      <ul class="p-works__filterlist">
        <li>
          <a class="p-works__fbtn<?php echo ( $wcat === '' ? ' is-current' : '' ); ?>"
             href="<?php echo esc_url( ymkrf_works_url( '', $warea ) ); ?>">すべて</a>
        </li>
        <?php foreach ( $wcats as $t ) :
          $n  = $warea === '' ? (int) $t->count : ( isset( $wcatCount[ $t->slug ] ) ? (int) $wcatCount[ $t->slug ] : 0 );
          if ( $n === 0 ) continue;
          $on = ( $wcat === $t->slug ); ?>
        <li>
          <a class="p-works__fbtn<?php echo ( $on ? ' is-current' : '' ); ?>"
             href="<?php echo esc_url( ymkrf_works_url( $on ? '' : $t->slug, $warea ) ); ?>">
            <?php echo esc_html( $t->name ); ?><span class="p-works__fnum"><?php echo $n; ?></span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <?php endif; ?>

    <?php if ( $wareas ) : ?>
    <nav class="p-works__filter p-works__filter--area" aria-label="エリアでさがす">
      <p class="p-works__filterttl">エリアでさがす</p>
      <ul class="p-works__filterlist">
        <li>
          <a class="p-works__fbtn<?php echo ( $warea === '' ? ' is-current' : '' ); ?>"
             href="<?php echo esc_url( ymkrf_works_url( $wcat, '' ) ); ?>">すべて</a>
        </li>
        <?php foreach ( $wareas as $t ) :
          $n  = $wcat === '' ? (int) $t->count : ( isset( $wareaCount[ $t->slug ] ) ? (int) $wareaCount[ $t->slug ] : 0 );
          if ( $n === 0 ) continue;
          $on = ( $warea === $t->slug ); ?>
        <li>
          <a class="p-works__fbtn<?php echo ( $on ? ' is-current' : '' ); ?>"
             href="<?php echo esc_url( ymkrf_works_url( $wcat, $on ? '' : $t->slug ) ); ?>">
            <?php echo esc_html( $t->name ); ?><span class="p-works__fnum"><?php echo $n; ?></span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <?php endif; ?>

    <?php if ( $wcat !== '' && $warea !== '' ) : ?>
      <p class="p-works__clear">
        <span><?php echo esc_html( $wname ); ?>でしぼり込み中</span>
        <a href="<?php echo esc_url( get_post_type_archive_link( 'ymkrf_works' ) ); ?>">しぼり込みをやめる</a>
      </p>
    <?php endif; ?>

  </div>
</section>
<?php endif; ?>

<section class="l-section">
  <div class="l-wrap">

    <?php if ( have_posts() ) : ?>
      <div class="p-works__grid">
        <?php $wi = 0; while ( have_posts() ) : the_post();
          $id     = get_the_ID();
          $wcat   = ymkrf_works_term_names( $id, 'ymkrf_works_cat' );
          $warea  = ymkrf_works_term_names( $id, 'ymkrf_works_area' );
          $wprice = trim( (string) get_post_meta( $id, '_ymkrf_price', true ) );
          $wperi  = trim( (string) get_post_meta( $id, '_ymkrf_period', true ) );
          $wshop  = ymkrf_works_shop_name( $id );
          $wstf   = ymkrf_works_staff_label( $id );
        ?>
        <article class="p-work" data-reveal<?php if ( $wi ) echo ' data-reveal-delay="' . (int) min( $wi * 80, 240 ) . '"'; ?>>
          <?php /* 見くらべスライダーはリンクで囲みません。
                   囲むと、左右に動かしたときにページが開いてしまうためです。 */ ?>
          <div class="p-work__thumb"><?php echo ymkrf_works_compare( $id, $wi < 3 ); ?></div>
          <div class="p-work__body">
            <p class="p-work__meta">
              <?php foreach ( array_slice( $wcat, 0, 2 ) as $t ) : ?><span><?php echo esc_html( $t ); ?></span><?php endforeach; ?>
              <?php foreach ( array_slice( $warea, 0, 1 ) as $t ) : ?><span><?php echo esc_html( $t ); ?></span><?php endforeach; ?>
              <?php if ( $wstf ) : ?><span><?php echo esc_html( $wstf ); ?></span>
              <?php elseif ( $wshop ) : ?><span><?php echo esc_html( $wshop ); ?>施工</span><?php endif; ?>
            </p>
            <h2 class="p-work__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
            <?php if ( $wprice || $wperi ) : ?>
              <p class="p-work__data">
                <?php if ( $wprice ) : ?><span><?php echo esc_html( $wprice ); ?></span><?php endif; ?>
                <?php if ( $wperi ) : ?><span>工期 <?php echo esc_html( $wperi ); ?></span><?php endif; ?>
              </p>
            <?php endif; ?>
          </div>
        </article>
        <?php $wi++; endwhile; ?>
      </div>

      <?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => '前へ', 'next_text' => '次へ' ) ); ?>

    <?php else : ?>
      <div class="p-cat__group">
        <h2 class="p-cat__groupttl">ただいま準備中</h2>
        <p class="p-cat__groupsub">
          施工した事例を、順に掲載しています。<br>
          もうしばらくお待ちください。
        </p>
      </div>
    <?php endif; ?>

  </div>
</section>

<section class="l-section l-section--soft">
  <div class="l-wrap">
    <div class="p-pagecta">
      <img class="p-pagecta__chara" src="<?php echo esc_url( $asset . '/assets/img/character/char-search-fly.webp' ); ?>"
           width="480" height="480" alt="" loading="lazy">
      <h2 class="p-pagecta__title"><span class="marker">同じような工事も、<br class="xs-only">ご相談ください</span></h2>
      <p class="p-pagecta__text">
        見積り・現地調査は無料。<br class="xs-only">しつこい営業はいたしません。
      </p>
      <?php if ( function_exists( 'ymkrf_product_cta' ) ) ymkrf_product_cta( 'works-archive', true ); ?>
    </div>
  </div>
</section>

</main>

<?php get_footer();
