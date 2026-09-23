<?php
/**
 * 対応エリアの一覧（/area/）
 * 置き場所： wp-content/themes/ymkrf/ymkrf-areas.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$all = ymkrf_area_list();

/* 県ごとに分けて、施工事例の多い順に */
$by = array();
foreach ( $all as $slug => $a ) {
	$c = ymkrf_area_counts( $a['city'] );
	$by[ $a['pref'] ][] = array( 'slug' => $slug, 'a' => $a, 'c' => $c );
}
foreach ( $by as $k => $v ) {
	usort( $v, function ( $x, $y ) { return $y['c']['works'] - $x['c']['works']; } );
	$by[ $k ] = $v;
}

get_header();
?>

<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li>対応エリア</li>
  </ol>
</nav>
<?php
if ( function_exists( 'ymkrf_crumb_ld' ) ) {
	ymkrf_crumb_ld( array(
		array( 'ホーム',     home_url( '/' ) ),
		array( '対応エリア', ymkrf_area_url() ),
	) );
}
?>

<main id="main">

<div class="p-area__head">
  <div class="l-wrap">
    <h1 class="p-area__title">リフォームの対応エリア</h1>
    <p class="p-area__lead">
      石川県・福井県の各市町でリフォームを承っています。
      キッチン・お風呂・トイレ・洗面台の交換から、外壁塗装・カーポートまで。
      見積り・現地調査は無料です。
    </p>
  </div>
</div>

<?php foreach ( $by as $pref => $list ) : if ( ! $list ) continue; ?>
<section class="l-section<?php echo $pref === '福井県' ? ' l-section--soft' : ''; ?>">
  <div class="l-wrap">
    <h2 class="p-prd__bar"><?php echo esc_html( $pref ); ?></h2>
    <div class="p-area__list">
      <?php foreach ( $list as $one ) : ?>
        <a class="p-area__listitem" href="<?php echo esc_url( ymkrf_area_url( $one['slug'] ) ); ?>">
          <span class="p-area__listitem__name"><?php echo esc_html( $one['a']['city'] ); ?></span>
          <?php if ( $one['c']['works'] ) : ?>
            <span class="p-area__listitem__num">施工事例 <?php echo number_format( $one['c']['works'] ); ?>件</span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endforeach; ?>

<section class="l-section">
  <div class="l-wrap">
    <div class="p-area__cta">
      <b>お住まいの市町が見あたらないときも、まずはご相談ください</b>
      <p>近くのお店から、お伺いできる場合があります。見積り・現地調査は無料です。</p>
      <div class="p-area__cta__btns">
        <a class="p-area__btn p-area__btn--line" href="<?php echo esc_url( home_url( '/inquiry/line/' ) ); ?>">LINEで相談する</a>
        <a class="p-area__btn" href="<?php echo esc_url( home_url( '/inquiry/' ) ); ?>">無料見積りを依頼する</a>
      </div>
    </div>
  </div>
</section>

</main>

<?php get_footer();
