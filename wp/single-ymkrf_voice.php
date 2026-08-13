<?php
/**
 * single-ymkrf_voice.php ─ お客様の声 詳細
 * 置き場所： wp-content/themes/ymkrf/single-ymkrf_voice.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$asset = get_stylesheet_directory_uri();
get_header();

while ( have_posts() ) : the_post();
  $id      = get_the_ID();
  $score   = ymkrf_voice_score( $id );
  $parts   = ymkrf_voice_meta_array( $id, '_ymkrf_parts' );
  $reasons = ymkrf_voice_meta_array( $id, '_ymkrf_reasons' );
  $cust    = get_post_meta( $id, '_ymkrf_customer', true );
  $trouble = get_post_meta( $id, '_ymkrf_trouble', true );
  $after   = get_post_meta( $id, '_ymkrf_after', true );
  $comment = get_post_meta( $id, '_ymkrf_comment', true );
  $rl      = ymkrf_voice_rating_labels();
?>

<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li><a href="<?php echo esc_url( get_post_type_archive_link( 'ymkrf_voice' ) ); ?>">お客様の声</a></li>
    <li><?php the_title(); ?></li>
  </ol>
</nav>

<main id="main">

<div class="p-pagehead">
  <div class="l-wrap p-pagehead__inner">
    <span class="p-pagehead__en">VOICE</span>
    <h1 class="p-pagehead__title"><?php the_title(); ?></h1>
    <div class="p-voice__headstars"><?php echo ymkrf_stars( $score ); ?></div>
    <?php if ( $cust ) : ?><p class="p-pagehead__lead"><?php echo esc_html( $cust ); ?></p><?php endif; ?>
  </div>
</div>

<section class="l-section">
  <div class="l-wrap l-wrap--narrow">

    <?php if ( $parts ) : ?>
      <p class="p-voice__tags p-voice__tags--lg">
        <?php foreach ( $parts as $t ) : ?><span class="p-voice__tag"><?php echo esc_html( $t ); ?></span><?php endforeach; ?>
      </p>
    <?php endif; ?>

    <?php if ( $comment ) : ?>
      <blockquote class="p-voice__quote"><?php echo nl2br( esc_html( $comment ) ); ?></blockquote>
    <?php endif; ?>

    <?php if ( $trouble || $after ) : ?>
      <dl class="p-voice__qa">
        <?php if ( $trouble ) : ?>
          <dt>リフォーム前は、どのようなお悩みでしたか</dt><dd><?php echo nl2br( esc_html( $trouble ) ); ?></dd>
        <?php endif; ?>
        <?php if ( $after ) : ?>
          <dt>リフォームしていかがでしたか</dt><dd><?php echo nl2br( esc_html( $after ) ); ?></dd>
        <?php endif; ?>
      </dl>
    <?php endif; ?>

    <h2 class="p-voice__h2">いただいた評価</h2>
    <table class="p-voice__ratings">
      <tbody>
      <?php foreach ( ymkrf_voice_rating_fields() as $k => $label ) :
        $v = (int) get_post_meta( $id, $k, true ); if ( ! $v ) continue; ?>
        <tr>
          <th><?php echo esc_html( $label ); ?></th>
          <td><span class="p-voice__rate p-voice__rate--<?php echo $v; ?>"><?php echo esc_html( $rl[ $v ] ); ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php $rec = (int) get_post_meta( $id, '_ymkrf_recommend', true ); if ( $rec ) :
        $recl = ymkrf_voice_recommend_labels(); ?>
        <tr>
          <th>お知り合いへのおすすめ</th>
          <td><span class="p-voice__rate p-voice__rate--<?php echo $rec; ?>"><?php echo esc_html( $recl[ $rec ] ); ?></span></td>
        </tr>
      <?php endif; ?>
      </tbody>
    </table>

    <?php if ( $reasons ) : ?>
      <h2 class="p-voice__h2">ヤマキシをお選びいただいた理由</h2>
      <p class="p-voice__tags"><?php foreach ( $reasons as $t ) : ?><span class="p-voice__tag p-voice__tag--r"><?php echo esc_html( $t ); ?></span><?php endforeach; ?></p>
    <?php endif; ?>

    <?php $fig = ymkrf_voice_survey_figure( $id ); if ( $fig ) : ?>
      <h2 class="p-voice__h2">いただいたアンケート</h2>
      <?php echo $fig; ?>
    <?php endif; ?>

    <p class="p-voice__note">
      ※お客様からいただいたアンケートを、内容を変えずに掲載しています。<br>
      　ご記入いただいたお名前の欄は、塗りつぶしたうえで掲載しています。
    </p>

  </div>
</section>

<section class="l-section l-section--soft">
  <div class="l-wrap">
    <div class="p-pagecta">
      <img class="p-pagecta__chara" src="<?php echo esc_url( $asset . '/assets/img/character/char-search-fly.webp' ); ?>"
           width="480" height="480" alt="" loading="lazy">
      <h2 class="p-pagecta__title"><span class="marker">まずは、お困りごとから</span></h2>
      <p class="p-pagecta__text">見積り・現地調査は無料。<br class="xs-only">しつこい営業はいたしません。</p>
      <?php if ( function_exists( 'ymkrf_product_cta' ) ) ymkrf_product_cta( 'voice-single', true ); ?>
    </div>
  </div>
</section>

<div class="l-wrap l-wrap--narrow" style="padding-bottom:40px">
  <a class="c-more" href="<?php echo esc_url( get_post_type_archive_link( 'ymkrf_voice' ) ); ?>">お客様の声の一覧にもどる</a>
</div>

</main>

<?php endwhile; get_footer();
