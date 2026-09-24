<?php
/**
 * 水まわり4点セット ─ プラン1つぶんのページ
 * 置き場所： wp-content/themes/ymkrf/ymkrf-pack4-plan.php
 *
 * （2026/09/22 ユーザー指示
 *   「4点セットの商品ページのカラー・取っ手などは削除してください。
 *     選択した4点はすでにページがあると思うので、その情報を引っ張ってきて、
 *     見やすくなるようページを構成してください」）
 *
 * ■ どういうページか
 *   プランには「プラン名・4点の中身・セット価格」しか入力しません。
 *   写真・メーカー・商品名・特徴・単品価格は、
 *   えらばれた4つの商品ページから、そのまま引いてきて出します。
 *   商品の内容を直せば、このページも自動で変わります。
 *
 *   single-ymkrf_product.php から呼ばれます（分類が pack4 のときだけ）。
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$plan_id = get_the_ID();
$items   = function_exists( 'ymkrf_p4_items' ) ? ymkrf_p4_items( $plan_id ) : array();

$was = (int) get_post_meta( $plan_id, '_ymkrf_p4was', true );
$now = (int) get_post_meta( $plan_id, '_ymkrf_p4now', true );

/* 通常価格が入っていないときは、4点の合計から出します */
if ( ! $was ) {
	foreach ( $items as $one ) {
		$t = (int) get_post_meta( $one[2], '_ymkrf_total', true );
		if ( ! $t ) {
			$t = (int) get_post_meta( $one[2], '_ymkrf_work', true )
			   + (int) get_post_meta( $one[2], '_ymkrf_item', true );
		}
		$was += $t;
	}
}
$off = ( $was && $now && $was > $now ) ? $was - $now : 0;

/* ほかのプラン */
$others = get_posts( array(
	'post_type'      => 'ymkrf_product',
	'posts_per_page' => -1,
	'post__not_in'   => array( $plan_id ),
	'orderby'        => 'menu_order',
	'order'          => 'ASC',
	'tax_query'      => array( array(
		'taxonomy' => 'ymkrf_product_cat', 'field' => 'slug', 'terms' => 'pack4',
	) ),
) );
?>

<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li><a href="<?php echo esc_url( ymkrf_products_url() ); ?>">商品・価格</a></li>
    <li><a href="<?php echo esc_url( ymkrf_cat_url( 'pack4' ) ); ?>">水まわり4点セット</a></li>
    <li><?php the_title(); ?></li>
  </ol>
</nav>
<?php
if ( function_exists( 'ymkrf_crumb_ld' ) ) {
	ymkrf_crumb_ld( array(
		array( 'ホーム',           home_url( '/' ) ),
		array( '商品・価格',       ymkrf_products_url() ),
		array( '水まわり4点セット', ymkrf_cat_url( 'pack4' ) ),
		array( get_the_title(),    get_permalink() ),
	) );
}
?>

<main id="main">

<!-- =========== 見出し =========== -->
<div class="p-prd__head">
  <div class="l-wrap">
    <p class="p-prd__catch">キッチン・お風呂・トイレ・洗面台をまとめて</p>
    <h1 class="p-prd__title"><?php the_title(); ?></h1>

    <p class="p-p4__lead">
      <?php
      /* 手で書いた「プランの説明」があれば、それを出します
         （2026/09/23 ユーザー指示「タイトル下にテキスト欄作成」） */
      $own = trim( (string) get_post_meta( $plan_id, '_ymkrf_desc', true ) );
      if ( $own !== '' ) {
        echo nl2br( esc_html( $own ) );
      } else {
      $names = array();
      foreach ( $items as $one ) {
        $nm = (string) get_post_meta( $one[2], '_ymkrf_name', true );
        if ( $nm === '' ) $nm = get_the_title( $one[2] );
        $names[] = $one[0] . 'は' . $nm;
      }
      echo esc_html(
        get_the_title() . 'は、' . implode( '、', $names ) . 'を組み合わせた水まわり4点セットです。'
        . ( $now ? '本体・標準工事費・古い設備の撤去処分まで込みで' . number_format( $now ) . '円（税込）。' : '' )
        . '石川県・福井県のリフォームヤマキシが、お見積りから工事まで承ります。'
      );
      }
      ?>
    </p>
  </div>
</div>

<!-- =========== プランの写真と、要点 =========== -->
<?php /* 写真だけだと寂しいので、右に「何が入って、いくらか」を並べます。
         スクロールしなくても中身と価格が分かるようにするためです。
         （2026/09/23 ユーザー承認「じゃ、Cで見せて」） */ ?>
<?php if ( has_post_thumbnail( $plan_id ) || $items ) : ?>
<section class="l-section l-section--soft">
  <div class="l-wrap">
    <div class="p-p4__hero">

      <?php if ( has_post_thumbnail( $plan_id ) ) : ?>
        <figure class="p-p4__hero__ph">
          <?php echo get_the_post_thumbnail( $plan_id, 'large', array(
            'alt' => trim( get_the_title( $plan_id ) . ' 水まわり4点セット' ),
          ) ); ?>
          <figcaption>※写真はイメージです</figcaption>
        </figure>
      <?php endif; ?>

      <div class="p-p4__hero__bd">
        <p class="p-p4__hero__ttl"><?php the_title(); ?></p>

        <?php if ( $items ) : ?>
          <dl class="p-p4__hero__list">
            <?php foreach ( $items as $one ) :
              list( $label, $p, $pid ) = $one;
              $nm = (string) get_post_meta( $pid, '_ymkrf_name', true );
              if ( $nm === '' ) $nm = get_the_title( $pid );
              $mk = get_the_terms( $pid, 'ymkrf_maker' );
              $mk = ( $mk && ! is_wp_error( $mk ) ) ? $mk[0]->name : '';
            ?>
              <dt><?php echo esc_html( $label ); ?></dt>
              <dd>
                <a href="<?php echo esc_url( get_permalink( $pid ) ); ?>">
                  <?php if ( $mk ) : ?><span class="p-p4__hero__mk"><?php echo esc_html( $mk ); ?></span><?php endif; ?>
                  <?php echo esc_html( $nm ); ?>
                </a>
              </dd>
            <?php endforeach; ?>
          </dl>
        <?php endif; ?>

        <?php if ( $now ) : ?>
          <p class="p-p4__hero__yen">
            <span class="lbl">4点セット</span>
            <span class="num"><?php echo esc_html( number_format( $now ) ); ?></span>
            <span class="unit">円<small>（税込）</small></span>
          </p>
          <?php if ( $off ) : ?>
            <?php /* 「◯◯円おトク！」は、黄色いギザギザの中に出します
                     （2026/09/23 ユーザー指示） */ ?>
            <p class="p-p4__hero__off">
              <span class="p-p4__hero__was">別々にすると <s><?php echo esc_html( number_format( $was ) ); ?>円</s></span>
              <span class="p-p4__burst">
                <b><?php echo esc_html( number_format( $off ) ); ?>円</b><span>おトク！</span>
              </span>
            </p>
          <?php endif; ?>
          <p class="p-p4__hero__note">本体・標準工事費・古い設備の撤去処分費まで込みの価格です。</p>
        <?php endif; ?>
      </div>

    </div>
  </div>
</section>
<?php endif; ?>

<!-- =========== 4点の中身 =========== -->
<?php if ( $items ) : ?>
<section class="l-section">
  <div class="l-wrap">
    <h2 class="p-prd__bar">このプランの中身</h2>

    <div class="p-p4__items">
      <?php foreach ( $items as $part => $one ) :
        list( $label, $p, $pid ) = $one;

        $name  = (string) get_post_meta( $pid, '_ymkrf_name', true );
        if ( $name === '' ) $name = get_the_title( $pid );

        $mks   = get_the_terms( $pid, 'ymkrf_maker' );
        $maker = ( $mks && ! is_wp_error( $mks ) ) ? $mks[0] : null;

        $total = (int) get_post_meta( $pid, '_ymkrf_total', true );
        if ( ! $total ) {
          $total = (int) get_post_meta( $pid, '_ymkrf_work', true )
                 + (int) get_post_meta( $pid, '_ymkrf_item', true );
        }

        $pts = array_filter( array(
          (string) get_post_meta( $pid, '_ymkrf_pt1', true ),
          (string) get_post_meta( $pid, '_ymkrf_pt2', true ),
          (string) get_post_meta( $pid, '_ymkrf_pt3', true ),
        ) );

        $grade = function_exists( 'ymkrf_grade_label' )
          ? ymkrf_grade_label( (string) get_post_meta( $pid, '_ymkrf_grade', true ), $part ) : '';
      ?>
        <article class="p-p4__item">
          <p class="p-p4__item__part"><?php echo esc_html( $label ); ?></p>

          <a class="p-p4__item__ph" href="<?php echo esc_url( get_permalink( $pid ) ); ?>">
            <?php if ( has_post_thumbnail( $pid ) ) {
              echo get_the_post_thumbnail( $pid, 'medium_large', array(
                'loading' => 'lazy',
                'alt'     => trim( ( $maker ? $maker->name . ' ' : '' ) . $label . ' ' . $name ),
              ) );
            } else { ?>
              <span class="p-p4__item__noph">写真は準備中です</span>
            <?php } ?>
            <?php /* メーカーロゴは、写真の左上に控えめに重ねます
                     （2026/09/23 ユーザー指示「写真の左上に重ねるようにメーカーロゴを持ってきて」） */ ?>
            <?php if ( $maker ) : ?>
              <span class="p-p4__item__logo"><?php echo ymkrf_maker_logo( $maker, 'p-maker' ); /* phpcs:ignore */ ?></span>
            <?php endif; ?>
            <?php if ( $grade ) : ?><span class="p-p4__item__grade"><?php echo esc_html( $grade ); ?></span><?php endif; ?>
          </a>

          <div class="p-p4__item__body">
            <?php if ( $maker ) : ?>
              <p class="p-p4__item__maker"><?php echo esc_html( $maker->name ); ?></p>
            <?php endif; ?>
            <h3 class="p-p4__item__name">
              <a href="<?php echo esc_url( get_permalink( $pid ) ); ?>"><?php echo esc_html( $name ); ?></a>
            </h3>

            <?php if ( $pts ) : ?>
              <p class="p-p4__item__pts">
                <?php foreach ( array_slice( array_values( $pts ), 0, 3 ) as $pt ) : ?>
                  <span><?php echo esc_html( $pt ); ?></span>
                <?php endforeach; ?>
              </p>
            <?php endif; ?>

            <?php if ( $total ) : ?>
              <p class="p-p4__item__yen">
                <span class="lbl">単品なら</span>
                <span class="num"><?php echo esc_html( number_format( $total ) ); ?></span>
                <span class="unit">円（税込）</span>
              </p>
            <?php endif; ?>

            <a class="p-p4__item__link" href="<?php echo esc_url( get_permalink( $pid ) ); ?>">
              <?php echo esc_html( $label ); ?>のくわしい内容を見る
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <p class="p-p4__note">
      色や取っ手など、えらべる仕様はそれぞれの商品ページでご覧いただけます。<br>
      ショールームでは現物をご覧いただけます。
    </p>
  </div>
</section>
<?php endif; ?>

<!-- =========== 価格 =========== -->
<?php if ( $now ) : ?>
<section class="l-section">
  <div class="l-wrap">
    <div class="p-p4__price">
      <?php if ( $off ) : ?>
        <p class="p-p4__price__was">
          4点を別々にすると <s><?php echo esc_html( number_format( $was ) ); ?>円</s>
        </p>
      <?php endif; ?>
      <p class="p-p4__price__now">
        <span class="lbl">4点セットなら</span>
        <span class="num"><?php echo esc_html( number_format( $now ) ); ?></span>
        <span class="unit">円<small>（税込）</small></span>
      </p>
      <?php if ( $off ) : ?>
        <p class="p-p4__price__off"><?php echo esc_html( number_format( $off ) ); ?>円 おトク</p>
      <?php endif; ?>
      <p class="p-p4__price__note">
        4点それぞれの本体・標準工事費・古い設備の撤去処分費まで込みの価格です。<br>
        お家の形や配管の状態によって追加の工事が必要なときは、着工前にかならずお見積りをお出しします。
      </p>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- =========== ほかのプラン =========== -->
<?php if ( $others ) : ?>
<section class="l-section l-section--soft">
  <div class="l-wrap">
    <h2 class="p-prd__bar">ほかのプラン</h2>
    <div class="p-p4__others">
      <?php foreach ( $others as $o ) :
        $onow = (int) get_post_meta( $o->ID, '_ymkrf_p4now', true ); ?>
        <a class="p-p4__other" href="<?php echo esc_url( get_permalink( $o ) ); ?>">
          <?php if ( has_post_thumbnail( $o->ID ) ) {
            echo get_the_post_thumbnail( $o->ID, 'medium', array( 'loading' => 'lazy', 'alt' => '' ) );
          } ?>
          <span class="p-p4__other__name"><?php echo esc_html( get_the_title( $o ) ); ?></span>
          <?php if ( $onow ) : ?>
            <span class="p-p4__other__yen"><?php echo esc_html( number_format( $onow ) ); ?>円<small>（税込）</small></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
    <p style="text-align:center;margin-top:18px">
      <a class="c-more" href="<?php echo esc_url( ymkrf_cat_url( 'pack4' ) ); ?>">4つのプランを見くらべる</a>
    </p>
  </div>
</section>
<?php endif; ?>

<?php
/* このプランに入っている4つの商品の施工事例を出します。
   商品名・型番が合うものが上に来ます
   （2026/09/24 ユーザー指示
     「4点セットは該当する商品名のものが上位に来るように」）。 */
$p4_wk = array();
if ( function_exists( 'ymkrf_works_for_product' ) ) {
	foreach ( array( 'kitchen', 'bathroom', 'toilet', 'lavatory' ) as $p4_part ) {
		$p4_pid = (int) get_post_meta( get_the_ID(), '_ymkrf_p4_' . $p4_part, true );
		if ( ! $p4_pid ) continue;
		foreach ( ymkrf_works_for_product( $p4_pid, 2 ) as $p4_w ) {
			if ( ! in_array( (int) $p4_w, $p4_wk, true ) ) $p4_wk[] = (int) $p4_w;
		}
	}
}
$p4_wk = array_slice( $p4_wk, 0, 3 );
if ( $p4_wk ) :
?>
<section class="l-section" id="works">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">WORKS</span>
      <h2 class="c-head__title">このプランの商品を使った施工事例</h2>
      <p class="c-head__lead">石川・福井の実際のお宅で、どう変わったか。金額も公開しています。</p>
    </div>
    <div class="p-works__grid">
      <?php foreach ( $p4_wk as $p4_wid ) :
        $GLOBALS['post'] = get_post( $p4_wid ); setup_postdata( $GLOBALS['post'] );
        if ( function_exists( 'ymkrf_works_card' ) ) ymkrf_works_card();
      endforeach; wp_reset_postdata(); ?>
    </div>
    <a class="c-more" href="<?php echo esc_url( get_post_type_archive_link( 'ymkrf_works' ) ); ?>">施工事例をもっと見る</a>
  </div>
</section>
<?php endif; ?>

</main>
