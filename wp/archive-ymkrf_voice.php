<?php
/**
 * archive-ymkrf_voice.php ─ お客様の声 一覧（/voice/）
 * 置き場所： wp-content/themes/ymkrf/archive-ymkrf_voice.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$asset = get_stylesheet_directory_uri();

/* いま見ている工事箇所（/voice/oiltank/）と市町（/voice/area/kanazawa/） */
$vpart  = function_exists( 'ymkrf_voice_current_part' ) ? ymkrf_voice_current_part() : '';
$vcity  = function_exists( 'ymkrf_voice_current_city' ) ? ymkrf_voice_current_city() : '';
if ( $vcity !== '' ) $vpart = '';   /* 地域の一覧のときは、工事箇所は使いません */

/* 平均点（いま並んでいるぶんだけ） */
$avg_args = array( 'post_type' => 'ymkrf_voice', 'posts_per_page' => -1, 'fields' => 'ids' );
if ( $vcity !== '' ) {
	$avg_args['meta_query'] = array( array( 'key' => '_ymkrf_city', 'value' => $vcity, 'compare' => '=' ) );
} elseif ( $vpart !== '' ) {
	$avg_args['meta_query'] = array( array( 'key' => '_ymkrf_parts', 'value' => $vpart, 'compare' => 'LIKE' ) );
}
$all = get_posts( $avg_args );
$sum = 0; $cnt = 0;
foreach ( (array) $all as $id ) { $s = ymkrf_voice_score( $id ); if ( $s ) { $sum += $s; $cnt++; } }
$avg = $cnt ? (int) round( $sum / $cnt ) : 0;

/* さがすボタン */
$vcats  = function_exists( 'ymkrf_voice_part_counts' ) ? ymkrf_voice_part_counts() : array();
$vareas = function_exists( 'ymkrf_voice_area_counts' ) ? ymkrf_voice_area_counts() : array();

get_header(); ?>

<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <?php if ( $vcity !== '' ) : ?>
      <li><a href="<?php echo esc_url( home_url( '/voice/' ) ); ?>">お客様の声</a></li>
      <li><?php echo esc_html( $vcity ); ?></li>
    <?php elseif ( $vpart !== '' ) : ?>
      <li><a href="<?php echo esc_url( home_url( '/voice/' ) ); ?>">お客様の声</a></li>
      <li><?php echo esc_html( $vpart ); ?></li>
    <?php else : ?>
      <li>お客様の声</li>
    <?php endif; ?>
  </ol>
</nav>

<main id="main">

<div class="p-pagehead">
  <div class="l-wrap p-pagehead__inner">
    <img class="p-pagehead__chara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-hinshitsu.webp"
         width="592" height="640" alt="" loading="lazy" decoding="async">
    <span class="p-pagehead__en">VOICE</span>
    <?php if ( $vcity !== '' ) : ?>
      <h1 class="p-pagehead__title"><?php echo esc_html( $vcity ); ?>のお客様の声</h1>
      <p class="p-pagehead__lead">
        <?php echo esc_html( $vcity ); ?>で工事をさせていただいたお客様から<br class="xs-only">いただいた、
        アンケート「仕事の通信簿」です。<br>
        いただいたまま載せています。<br class="xs-only">良い評価も、そうでない評価も。
      </p>
    <?php elseif ( $vpart !== '' ) : ?>
      <h1 class="p-pagehead__title"><?php echo esc_html( $vpart ); ?>のお客様の声</h1>
      <p class="p-pagehead__lead">
        <?php echo esc_html( $vpart ); ?>の工事をさせていただいたお客様から<br class="xs-only">いただいた、
        アンケート「仕事の通信簿」です。<br>
        いただいたまま載せています。<br class="xs-only">良い評価も、そうでない評価も。
      </p>
    <?php else : ?>
      <h1 class="p-pagehead__title">お客様の声</h1>
      <p class="p-pagehead__lead">
        工事のあとにお送りしている<br class="xs-only">アンケート「仕事の通信簿」を、<br>
        いただいたまま載せています。<br class="xs-only">良い評価も、そうでない評価も。
      </p>
    <?php endif; ?>

    <?php if ( $avg ) : ?>
      <?php /* 満足度の平均。囲いは付けず、リード文のすぐ下に置きます */ ?>
      <div class="p-voice__avg">
        <p class="p-voice__avgttl">いただいた満足度の平均</p>
        <?php echo ymkrf_stars( $avg ); ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if ( count( $vcats ) > 1 || count( $vareas ) > 1 ) : ?>
<section class="l-section l-section--tight">
  <div class="l-wrap">
    <nav class="p-voice__cats" aria-label="工事箇所でさがす">
      <p class="p-voice__catsttl">工事箇所でさがす</p>
      <ul class="p-voice__catslist">
        <li>
          <a class="p-voice__cat<?php echo ( $vpart === '' && $vcity === '' ? ' is-current' : '' ); ?>"
             href="<?php echo esc_url( home_url( '/voice/' ) ); ?>">すべて</a>
        </li>
        <?php foreach ( $vcats as $name => $c ) : ?>
        <li>
          <a class="p-voice__cat<?php echo ( $vpart === $name ? ' is-current' : '' ); ?>"
             href="<?php echo esc_url( ymkrf_voice_part_url( $c['roman'] ) ); ?>">
            <?php echo esc_html( $name ); ?><span class="p-voice__catnum"><?php echo (int) $c['count']; ?></span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </nav>

    <?php if ( count( $vareas ) > 1 ) : ?>
    <nav class="p-voice__cats p-voice__cats--area" aria-label="地域でさがす">
      <p class="p-voice__catsttl">地域でさがす</p>
      <ul class="p-voice__catslist">
        <?php foreach ( $vareas as $name => $c ) : ?>
        <li>
          <a class="p-voice__cat<?php echo ( $vcity === $name ? ' is-current' : '' ); ?>"
             href="<?php echo esc_url( ymkrf_voice_area_url( $c['roman'] ) ); ?>">
            <?php echo esc_html( $name ); ?><span class="p-voice__catnum"><?php echo (int) $c['count']; ?></span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<section class="l-section">
  <div class="l-wrap">

    <?php if ( have_posts() ) : ?>
      <div class="p-voice__grid p-voice__grid--list">
        <?php while ( have_posts() ) : the_post();
          $id    = get_the_ID();
          $score = ymkrf_voice_score( $id );
          $parts = ymkrf_voice_meta_array( $id, '_ymkrf_parts' );
          $cust  = ymkrf_voice_customer_label( $id );
          $shop  = ymkrf_voice_shop_name( $id );
          $ill   = ymkrf_voice_illust_img( $id, 96 );
        ?>
        <article class="p-voice__card" data-reveal>
          <div class="p-voice__top">
            <?php if ( $ill ) : ?><span class="p-voice__illwrap"><?php echo $ill; ?></span><?php endif; ?>
            <span class="p-voice__topin"><?php echo ymkrf_stars( $score ); ?></span>
          </div>
          <?php if ( $parts ) : ?>
            <p class="p-voice__tags">
              <?php foreach ( array_slice( $parts, 0, 3 ) as $t ) : ?>
                <span class="p-voice__tag"><?php echo esc_html( $t ); ?></span>
              <?php endforeach; ?>
            </p>
          <?php endif; ?>
          <p class="p-voice__text"><?php echo esc_html( ymkrf_voice_excerpt( $id, 100 ) ); ?></p>
          <?php if ( $cust || $shop ) : ?>
            <p class="p-voice__who">
              <?php if ( $cust ) : ?><span><?php echo esc_html( $cust ); ?></span><?php endif; ?>
              <?php if ( $shop ) : ?><span class="p-voice__shop"><?php echo esc_html( $shop ); ?>施工</span><?php endif; ?>
            </p>
          <?php endif; ?>
          <a class="p-voice__more" href="<?php the_permalink(); ?>">くわしく見る</a>
        </article>
        <?php endwhile; ?>
      </div>

      <?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => '前へ', 'next_text' => '次へ' ) ); ?>

    <?php else : ?>
      <div class="p-cat__group">
        <h2 class="p-cat__groupttl">ただいま準備中</h2>
        <p class="p-cat__groupsub">
          いただいたアンケートを、順に掲載しています。<br>
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
      <h2 class="p-pagecta__title"><span class="marker">お困りごとから、<br class="xs-only">ご相談ください</span></h2>
      <p class="p-pagecta__text">
        見積り・現地調査は無料。<br class="xs-only">しつこい営業はいたしません。
      </p>
      <?php if ( function_exists( 'ymkrf_product_cta' ) ) ymkrf_product_cta( 'voice-archive', true ); ?>
    </div>
  </div>
</section>

</main>

<?php get_footer();
