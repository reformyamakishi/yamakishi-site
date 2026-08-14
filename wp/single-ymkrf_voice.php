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
  $cust    = ymkrf_voice_customer_label( $id );
  $shop    = ymkrf_voice_shop_name( $id );
  $ill     = ymkrf_voice_illust_img( $id, 128 );
  $case    = trim( (string) get_post_meta( $id, '_ymkrf_case_no', true ) );
  $works   = ymkrf_voice_linked_works( $id );
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
  </div>
</div>

<section class="l-section">
  <div class="l-wrap l-wrap--narrow">

    <?php /* ご感想のとなりに、お客様イメージのイラストを置きます */ ?>
    <div class="p-voice__intro">

      <div class="p-voice__introtxt">
        <?php if ( $parts ) : ?>
          <p class="p-voice__tags p-voice__tags--lg">
            <?php foreach ( $parts as $t ) : ?><span class="p-voice__tag"><?php echo esc_html( $t ); ?></span><?php endforeach; ?>
          </p>
        <?php endif; ?>
        <?php if ( $comment ) : ?>
          <blockquote class="p-voice__quote"><?php echo nl2br( esc_html( $comment ) ); ?></blockquote>
        <?php endif; ?>
      </div>

      <?php if ( $ill || $cust || $shop ) : ?>
        <div class="p-voice__introfig">
          <?php if ( $ill ) : ?><span class="p-voice__illfig"><?php echo $ill; ?></span><?php endif; ?>
          <?php if ( $cust ) : ?><span class="p-voice__whopill"><?php echo esc_html( $cust ); ?></span><?php endif; ?>
          <?php if ( $shop ) : ?><span class="p-voice__whoshop"><?php echo esc_html( $shop ); ?>施工</span><?php endif; ?>
        </div>
      <?php endif; ?>

    </div>

    <?php /* お悩み・いかがでしたか は、アンケートに記入があるときだけ出します */ ?>
    <?php if ( $trouble || $after ) : ?>
      <dl class="p-voice__qa">
        <?php if ( $trouble ) : ?>
          <dt>今回リフォームする前は、どのようなお悩み（お困り）でしたか？</dt>
          <dd><?php echo nl2br( esc_html( $trouble ) ); ?></dd>
        <?php endif; ?>
        <?php if ( $after ) : ?>
          <dt>今回リフォームしていかがでしたでしょうか？</dt>
          <dd><?php echo nl2br( esc_html( $after ) ); ?></dd>
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
      <h2 class="p-voice__h2">いただいたアンケートの実物</h2>
      <p class="p-voice__sheetlead">上の文章は、この用紙に手書きでいただいたものを、そのまま文字にして起こしています。</p>
      <?php echo $fig; ?>
    <?php endif; ?>

    <?php /* 手書きの感想がないときは、チェック内容から作った紹介文を出します。
             ページごとに言い回しを変えて、似たページにならないようにしています。 */ ?>
    <?php if ( ! $comment ) : $sum = ymkrf_voice_summary( $id ); if ( $sum ) : ?>
      <p class="p-voice__summary"><?php echo esc_html( $sum ); ?></p>
    <?php endif; endif; ?>

    <?php if ( $works ) : ?>
      <h2 class="p-voice__h2">この工事の施工事例</h2>
      <ul class="p-voice__works">
        <?php foreach ( $works as $w ) : ?>
          <li><a href="<?php echo esc_url( get_permalink( $w ) ); ?>"><?php echo esc_html( get_the_title( $w ) ); ?></a></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>



  </div>
</section>

<?php /* ページごとに違う関連リンク。似たページと見なされにくくなります */ ?>
<?php $rel = ymkrf_voice_related( $id, 4 ); if ( $rel ) : ?>
<section class="l-section l-section--tight">
  <div class="l-wrap">
    <h2 class="p-voice__h2">同じような工事の、ほかのお客様の声</h2>
    <div class="p-voice__rel">
      <?php foreach ( $rel as $r ) :
        $rp = ymkrf_voice_meta_array( $r->ID, '_ymkrf_parts' );
        $rc = ymkrf_voice_customer_label( $r->ID ); ?>
        <a class="p-voice__relcard" href="<?php echo esc_url( get_permalink( $r ) ); ?>">
          <?php echo ymkrf_voice_illust_img( $r->ID, 56 ); ?>
          <span class="p-voice__reltxt">
            <span class="p-voice__relttl"><?php echo esc_html( $rp ? implode( '・', $rp ) : get_the_title( $r ) ); ?></span>
            <?php if ( $rc ) : ?><span class="p-voice__relsub"><?php echo esc_html( $rc ); ?></span><?php endif; ?>
          </span>
          <?php echo ymkrf_stars( ymkrf_voice_score( $r->ID ), false ); ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

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
