<?php
/**
 * ymkrf-flyer.php ─ イベント・チラシ（/flyer/）
 *
 * 置き場所： wp-content/themes/ymkrf/ymkrf-flyer.php
 *
 * ── このページの役目 ──────────────────────────────
 * 新聞折込チラシ・店頭チラシを、サイトでもご覧いただくページです。
 *
 * ── いまのサイトからの変更点 ────────────────────────
 * いまのサイトは、10店舗ぶんのチラシ（表・裏で約20枚）を1ページで
 * すべて読み込んでいました。スマホでは表示に時間がかかり、
 * 自分の店のチラシを探すのもたいへんでした。
 *
 * このページは「お店をえらぶ」形にしています。
 *   ・チラシの画像は、えらばれたお店のぶんしか読み込みません
 *     （下の JavaScript が、表示するときにはじめて src を入れています）
 *   ・お店をえらぶと、そのお店の電話番号・住所・営業時間も切り替わります
 *   ・チラシに載せた商品は、サイトに登録ずみの商品から出しています
 *     （価格は商品ページのものなので、古いままになりません）
 *
 * ── お店をえらんだ状態でリンクする ─────────────────────
 * /flyer/?shop=komathu のように付けると、そのお店をえらんだ状態で開きます。
 * 店舗・対応エリアのページ（/shops/）から、この形でリンクしています。
 *
 * ── チラシの登録 ────────────────────────────────
 * ダッシュボードの「イベント・チラシ」から登録します。
 * しくみは inc/functions-flyer.php にあります。
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$asset = get_stylesheet_directory_uri();
$line  = 'https://lin.ee/UJZuSTrz';
$tel   = '0800-777-3331';

$shops = function_exists( 'ymkrf_shops' ) ? ymkrf_shops() : array();

/* ------------------------------------------------------------
   どのお店をえらんだ状態で開くか（/flyer/?shop=komathu）
   ------------------------------------------------------------ */
$sel = isset( $_GET['shop'] ) ? sanitize_key( wp_unslash( $_GET['shop'] ) ) : '';
$ok  = false;
foreach ( $shops as $sp ) { if ( $sp['slug'] === $sel ) { $ok = true; break; } }
if ( ! $ok ) $sel = '';

/* ------------------------------------------------------------
   お店ごとに「出すチラシ」を先に決めておきます。

   同じチラシを何度も書き出さなくてよいように、
   チラシは1件につき1回だけ書き出し、
   「どのお店で、どのチラシを出すか」だけを対応表にします。
   ------------------------------------------------------------ */
$panels = array();   /* チラシID => ほどいたデータ */
$map    = array();   /* お店のスラッグ => 出すチラシIDの並び */

if ( function_exists( 'ymkrf_flyers_for' ) ) {
	foreach ( $shops as $sp ) {
		$ids = array();
		foreach ( ymkrf_flyers_for( $sp['slug'] ) as $fp ) {
			if ( ! isset( $panels[ $fp->ID ] ) ) {
				$panels[ $fp->ID ] = ymkrf_flyer_data( $fp );
			}
			$ids[] = $fp->ID;
		}
		$map[ $sp['slug'] ] = $ids;
	}
}

/* 掲載中のチラシが1枚もないとき */
$has_any = ! empty( $panels );

/* えらばれているお店（?shop=◯◯ で来られたとき）。
   JavaScript が動かない環境でも、そのお店のチラシがそのまま出るように、
   はじめから開いた形で書き出します。 */
$cur     = null;
$sel_ids = array();
if ( $sel !== '' ) {
	foreach ( $shops as $sp ) { if ( $sp['slug'] === $sel ) { $cur = $sp; break; } }
	$sel_ids = isset( $map[ $sel ] ) ? $map[ $sel ] : array();
}

/* 県ごとにまとめ直します（お店をえらぶボタンの並び） */
$by_pref = array();
foreach ( $shops as $sp ) $by_pref[ $sp['pref'] ][] = $sp;

/* 地図のリンク（貼り込みではなく、ただのリンクです。費用がかからないため） */
if ( ! function_exists( 'ymkrf_map_url' ) ) {
	function ymkrf_map_url( $addr ) {
		return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( 'ヤマキシ ' . $addr );
	}
}

/* お店の情報を、JavaScript にわたす形にします */
$shop_js = array();
foreach ( $shops as $sp ) {
	$shop_js[ $sp['slug'] ] = array(
		'name'  => $sp['name'],
		'tel'   => $sp['tel'],
		'addr'  => $sp['addr'],
		'hours' => ! empty( $sp['hours'] ) ? $sp['hours'] : '',
		'hnote' => ! empty( $sp['hnote'] ) ? $sp['hnote'] : '',
		'soon'  => ! empty( $sp['soon'] )  ? $sp['soon']  : '',
		'map'   => ymkrf_map_url( $sp['addr'] ),
		'ids'   => isset( $map[ $sp['slug'] ] ) ? $map[ $sp['slug'] ] : array(),
	);
}

get_header();
?>

<!-- =========== パンくず =========== -->
<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li>イベント・チラシ</li>
  </ol>
</nav>

<main id="main">

<!-- =========== ページ見出し =========== -->
<div class="p-pagehead">
  <div class="l-wrap p-pagehead__inner">
    <span class="p-pagehead__en">FLYER</span>
    <h1 class="p-pagehead__title">イベント・チラシ</h1>
    <p class="p-pagehead__lead">
      新聞折込・店頭でお配りしているチラシです。<br class="xs-only">
      お店をえらぶと、そのお店のチラシが出ます。
    </p>
  </div>
</div>

<?php if ( ! $has_any ) : ?>

  <!-- チラシが1枚も登録されていない／期間外のとき -->
  <section class="l-section">
    <div class="l-wrap">
      <div class="p-flyer__empty">
        <img class="p-flyer__emptychara" src="<?php echo esc_url( $asset ); ?>/assets/img/character/char-stand.webp"
             width="503" height="640" alt="" loading="lazy" decoding="async">
        <p class="p-flyer__emptyttl">ただいま掲載中のチラシはありません</p>
        <p>
          次のチラシができましたら、このページでお知らせします。<br>
          お困りごとがございましたら、いつでもご相談ください。現地調査もお見積りも無料です。
        </p>
      </div>

      <?php if ( current_user_can( 'manage_options' ) ) : ?>
        <div class="p-flyer__admin">
          <p><b>スタッフの方へ（この案内はログイン中だけ見えています）</b></p>
          <p>
            ダッシュボードの<b>「イベント・チラシ」→「新規追加」</b>から登録してください。<br>
            入れるのは、チラシの<b>表面・裏面の画像</b>と<b>掲載期間</b>だけで大丈夫です。<br>
            「対象のお店」を空にすると全店共通、お店にチェックを入れるとその店だけのチラシになります。<br>
            掲載期間のおわりの日をすぎると、自動でこのページから消えます（削除は不要です）。
          </p>
        </div>
      <?php endif; ?>
    </div>
  </section>

<?php else : ?>

<!-- =========== お店をえらぶ =========== -->
<section class="l-section" id="shop">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">SELECT</span>
      <h2 class="c-head__title">お店を<span class="marker">えらぶ</span></h2>
    </div>

    <p class="p-flyer__lead">
      チラシは、お店によって内容が違うことがあります。<br class="sp-only">
      お近くのお店をえらんでください。
    </p>

    <?php /* JavaScript が動かない環境でも使えるように、
             ふつうのリンク（?shop=◯◯）にしてあります。 */ ?>
    <?php foreach ( $by_pref as $pref => $list ) : ?>
      <h3 class="p-flyer__pref"><?php echo esc_html( $pref ); ?></h3>
      <div class="p-flyer__picks">
        <?php foreach ( $list as $sp ) :
          $n = isset( $map[ $sp['slug'] ] ) ? count( $map[ $sp['slug'] ] ) : 0; ?>
          <a class="p-flyer__pick<?php echo ( $sel === $sp['slug'] ) ? ' is-on' : ''; ?>"
             href="<?php echo esc_url( add_query_arg( 'shop', $sp['slug'], home_url( '/flyer/' ) ) ); ?>#flyer"
             data-shop="<?php echo esc_attr( $sp['slug'] ); ?>">
            <span class="p-flyer__pickname"><?php echo esc_html( $sp['name'] ); ?></span>
            <?php if ( ! empty( $sp['soon'] ) ) : ?>
              <span class="p-flyer__picksoon">準備中</span>
            <?php elseif ( ! $n ) : ?>
              <span class="p-flyer__picknone">チラシなし</span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- =========== チラシ =========== -->
<section class="l-section l-section--soft" id="flyer">
  <div class="l-wrap">

    <?php /* お店をまだえらんでいないとき（?shop= なしで来られたとき）に出します */ ?>
    <div class="p-flyer__ask" id="flyer-ask"<?php echo $sel ? ' hidden' : ''; ?>>
      <p class="p-flyer__askttl">↑ 上から、お店をえらんでください</p>
      <p>えらんだお店のチラシだけを出しますので、すぐに開きます。</p>
    </div>

    <?php
      $cur_hours = '';
      if ( $cur ) {
        $cur_hours = ! empty( $cur['soon'] ) ? $cur['soon']
          : ( ! empty( $cur['hours'] ) ? '営業時間 ' . $cur['hours']
              . ( ! empty( $cur['hnote'] ) ? '（' . $cur['hnote'] . '）' : '' ) : '' );
      }
    ?>
    <div class="p-flyer__now" id="flyer-now"<?php echo $cur ? '' : ' hidden'; ?>>
      <p class="p-flyer__nowshop"><b id="flyer-shopname"><?php echo $cur ? esc_html( $cur['name'] ) : ''; ?></b>のチラシ</p>
      <p class="p-flyer__nowinfo" id="flyer-shopinfo"><?php echo esc_html( $cur_hours ); ?></p>
    </div>

    <?php foreach ( $panels as $fid => $f ) :
      $term = ymkrf_flyer_term_text( $f['start'], $f['end'] );
      /* えらばれていないお店のチラシは、画像を src ではなく data-src に入れておきます。
         こうすると読み込まれません。えらばれたときに JavaScript が src へ移します。 */
      $on   = in_array( $fid, $sel_ids, true );
      $at   = $on ? 'src' : 'data-src';
      ?>
      <article class="p-flyer__item" data-flyer="<?php echo (int) $fid; ?>"<?php echo $on ? '' : ' hidden'; ?>>

        <header class="p-flyer__head">
          <?php if ( $f['catch'] ) : ?>
            <p class="p-flyer__catch"><?php echo esc_html( $f['catch'] ); ?></p>
          <?php endif; ?>
          <h2 class="p-flyer__title"><?php echo esc_html( $f['title'] ); ?></h2>
          <?php if ( $term ) : ?>
            <p class="p-flyer__term"><span>掲載期間</span><?php echo esc_html( $term ); ?></p>
          <?php endif; ?>
        </header>

        <?php if ( $f['body'] !== '' ) : ?>
          <div class="p-flyer__body"><?php echo wp_kses_post( wpautop( $f['body'] ) ); ?></div>
        <?php endif; ?>

        <?php if ( $f['front'] || $f['back'] ) : ?>
          <?php
          /* チラシはB4で、縦のときも横のときもあります（2026/09/03 ユーザー確認）。
             ・縦（たて長）… 表・裏を横に2枚ならべます
             ・横（よこ長）… 横にならべると小さくなりすぎるので、上下にならべます
             どちらかは、表面の画像のかたちで自動で決めています。 */
          $land = false;
          $ref  = $f['front'] ? $f['front'] : $f['back'];
          if ( $ref && $ref['h'] > 0 ) $land = ( $ref['w'] / $ref['h'] ) > 1.05;
          ?>
          <div class="p-flyer__sheets<?php echo $land ? ' p-flyer__sheets--land' : ''; ?>">
            <?php
            $sheets = array(
              array( 'img' => $f['front'], 'lbl' => '表面' ),
              array( 'img' => $f['back'],  'lbl' => '裏面' ),
            );
            foreach ( $sheets as $sh ) : if ( ! $sh['img'] ) continue; ?>
              <figure class="p-flyer__sheet">
                <a class="js-lightbox" href="<?php echo esc_url( $sh['img']['full'] ); ?>"
                   data-caption="<?php echo esc_attr( $f['title'] . '　' . $sh['lbl'] ); ?>">
                  <img <?php echo esc_attr( $at ); ?>="<?php echo esc_url( $sh['img']['src'] ); ?>"
                       width="<?php echo (int) $sh['img']['w']; ?>"
                       height="<?php echo (int) $sh['img']['h']; ?>"
                       alt="<?php echo esc_attr( $f['title'] . ' ' . $sh['lbl'] ); ?>"
                       decoding="async">
                </a>
                <figcaption><?php echo esc_html( $sh['lbl'] ); ?>　<small>押すと大きくなります</small></figcaption>
              </figure>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ( $f['pdf'] ) : ?>
          <p class="p-flyer__pdf">
            <a class="c-btn c-btn--ghost" href="<?php echo esc_url( $f['pdf'] ); ?>"
               target="_blank" rel="noopener" data-cta="flyer-pdf">
              <span class="c-btn__label">チラシをPDFで見る<span class="c-btn__sub">印刷・保存ができます</span></span>
            </a>
          </p>
        <?php endif; ?>

        <?php
        /* ---- チラシに載せた商品 ----
           商品ページに登録ずみのものを出しています。
           値段は商品ページのものなので、古いままになることがありません。 */
        if ( $f['prd'] ) :
          $pq = new WP_Query( array(
            'post_type'      => 'ymkrf_product',
            'post_status'    => 'publish',
            'post__in'       => $f['prd'],
            'orderby'        => 'post__in',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
          ) );
          if ( $pq->have_posts() ) : ?>
            <div class="p-flyer__prd">
              <h3 class="p-prd__bar">チラシに載せた商品</h3>
              <p class="p-flyer__prdlead">
                価格はすべて<strong>標準工事費・既存品の撤去処分費まで込み</strong>の税込表示です。<br>
                商品ページでは、色・大きさ・くわしい仕様までご覧いただけます。
              </p>

              <div class="p-cat__cards">
                <?php global $post; while ( $pq->have_posts() ) : $pq->the_post();
                  $d  = function_exists( 'ymkrf_product_data' ) ? ymkrf_product_data() : array();
                  $mt = ! empty( $d['makers'] ) ? $d['makers'][0] : null;
                ?>
                  <a class="p-cat__card" href="<?php the_permalink(); ?>" data-cta="flyer-product">
                    <div class="p-cat__cardph">
                      <?php if ( has_post_thumbnail() ) : ?>
                        <img <?php echo esc_attr( $at ); ?>="<?php echo esc_url( (string) get_the_post_thumbnail_url( null, 'medium_large' ) ); ?>"
                             alt="" loading="lazy" decoding="async">
                      <?php else : ?>
                        <span class="p-cat__cardnoph">写真は準備中です</span>
                      <?php endif; ?>
                      <?php if ( ! empty( $d['grade'] ) ) : ?>
                        <span class="p-cat__cardgrade"><?php echo esc_html( $d['grade'] ); ?></span>
                      <?php endif; ?>
                    </div>
                    <div class="p-cat__cardbody">
                      <h4 class="p-cat__cardname"><?php echo esc_html( ! empty( $d['name'] ) ? $d['name'] : get_the_title() ); ?><?php
                        if ( ! empty( $d['sub'] ) ) echo '<span class="p-cat__cardsub">' . esc_html( $d['sub'] ) . '</span>'; ?></h4>
                      <p class="p-cat__cardmeta">
                        <?php if ( $mt && function_exists( 'ymkrf_maker_logo' ) ) echo ymkrf_maker_logo( $mt, 'p-maker' ); /* phpcs:ignore */ ?>
                        <?php if ( ! empty( $d['daystext'] ) ) : ?><span class="p-cat__carddays">工期<?php echo esc_html( $d['daystext'] ); ?></span>
                        <?php elseif ( ! empty( $d['days'] ) ) : ?><span class="p-cat__carddays">工期<?php echo esc_html( $d['days'] ); ?>日</span><?php endif; ?>
                      </p>
                      <?php if ( ! empty( $d['total'] ) ) : ?>
                        <p class="p-cat__cardprice">
                          <span class="lbl">工事費込み</span>
                          <span class="num"><?php echo esc_html( number_format( $d['total'] ) ); ?></span>
                          <span class="unit">円（税込）</span>
                        </p>
                      <?php endif; ?>
                      <span class="p-cat__cardlink">くわしく見る</span>
                    </div>
                  </a>
                <?php endwhile; ?>
              </div>
            </div>
          <?php endif; wp_reset_postdata();
        endif; ?>

        <p class="p-flyer__note">
          ※チラシの内容は、掲載期間をすぎると変わることがあります。<br>
          ※お住まいの状況によっては、追加の工事が必要になることがあります。そのときは、着工前にかならずお見積りをお出しします。
        </p>

      </article>
    <?php endforeach; ?>

    <?php /* えらんだお店にチラシが無いとき */ ?>
    <div class="p-flyer__none" id="flyer-none"<?php echo ( $cur && ! $sel_ids ) ? '' : ' hidden'; ?>>
      <p class="p-flyer__nonettl">このお店の今月のチラシは、ただいま準備中です</p>
      <p>
        チラシがなくても、ご相談・お見積りはいつでも承っています。<br>
        現地調査もお見積りも無料です。
      </p>
    </div>

  </div>
</section>

<!-- =========== えらんだお店のご案内 =========== -->
<section class="l-section" id="shopinfo"<?php echo $cur ? '' : ' hidden'; ?>>
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">SHOP</span>
      <h2 class="c-head__title"><span id="flyer-shopname2"><?php echo $cur ? esc_html( $cur['name'] ) : ''; ?></span>のご案内</h2>
    </div>

    <div class="p-flyer__card">
      <p class="p-flyer__cardaddr" id="flyer-addr"><?php echo $cur ? esc_html( $cur['addr'] ) : ''; ?></p>
      <p class="p-flyer__cardhours" id="flyer-hours"><?php echo esc_html( $cur_hours ); ?></p>
      <p class="p-flyer__cardbtns">
        <a class="c-btn" id="flyer-teltop" data-cta="flyer-tel"
           href="<?php echo ( $cur && $cur['tel'] ) ? 'tel:' . esc_attr( $cur['tel'] ) : '#'; ?>"
           <?php echo ( $cur && $cur['tel'] ) ? '' : 'style="display:none"'; ?>>
          <span class="c-btn__label"><span id="flyer-tel"><?php echo $cur ? esc_html( $cur['tel'] ) : ''; ?></span><span class="c-btn__sub">この店に直接つながります</span></span>
        </a>
        <a class="c-btn c-btn--ghost" id="flyer-map" target="_blank" rel="noopener"
           href="<?php echo $cur ? esc_url( ymkrf_map_url( $cur['addr'] ) ) : '#'; ?>">
          <span class="c-btn__label">地図で見る<span class="c-btn__sub">Googleマップが開きます</span></span>
        </a>
      </p>
      <p class="p-flyer__cardlink">
        <a href="<?php echo esc_url( home_url( '/shops/' ) ); ?>">店舗の一覧・対応エリアはこちら</a>
      </p>
    </div>
  </div>
</section>

<?php endif; /* $has_any */ ?>

<!-- =========== CTA =========== -->
<section class="l-section l-section--tint">
  <div class="l-wrap">
    <div class="p-lpcta">
      <img class="p-lpcta__chara" src="<?php echo esc_url( $asset ); ?>/assets/img/character/char-stand.webp"
           width="503" height="640" alt="" loading="lazy" decoding="async">
      <h2 class="p-lpcta__title">チラシに<span class="marker">ないもの</span>も、ご相談ください</h2>
      <p class="p-lpcta__text">
        チラシに載せているのは、ほんの一部です。<br>
        現地調査もお見積りも無料です。しつこい営業はいたしません。
      </p>
      <div class="p-lpcta__btns">
        <a class="c-btn c-btn--line c-btn--block" href="<?php echo esc_url( $line ); ?>" rel="noopener" data-cta="flyer-cta">
          <span class="c-btn__label">LINEで相談する<span class="c-btn__sub">写真を送るだけでもOK・24時間受付</span></span>
        </a>
        <a class="c-btn c-btn--block" href="<?php echo esc_url( home_url( '/inquiry/' ) ); ?>" data-cta="flyer-cta">
          <span class="c-btn__label">無料の現地調査・お見積り<span class="c-btn__sub">フォームから24時間受付</span></span>
        </a>
        <a class="c-btn c-btn--ghost c-btn--block" href="tel:<?php echo esc_attr( $tel ); ?>" data-cta="flyer-cta">
          <span class="c-btn__label"><?php echo esc_html( $tel ); ?><span class="c-btn__sub">通話無料・受付 9:00〜17:00</span></span>
        </a>
      </div>
    </div>
  </div>
</section>

</main>

<?php if ( $has_any ) : ?>
<script>
/* ------------------------------------------------------------------
   お店をえらぶと、そのお店のチラシだけを出します。

   ★画像は data-src に入れてあり、出すときにはじめて src へ移します。
     こうすると、えらばれていないお店のチラシは
     いっさい読み込まれません（いまのサイトの重さの原因はここでした）。
   ------------------------------------------------------------------ */
(function () {
  var SHOPS = <?php echo wp_json_encode( $shop_js, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?>;

  var picks  = document.querySelectorAll('.p-flyer__pick');
  var items  = document.querySelectorAll('.p-flyer__item');
  var ask    = document.getElementById('flyer-ask');
  var now    = document.getElementById('flyer-now');
  var none   = document.getElementById('flyer-none');
  var info   = document.getElementById('shopinfo');

  function hydrate(box) {
    var imgs = box.querySelectorAll('img[data-src]');
    Array.prototype.forEach.call(imgs, function (im) {
      im.src = im.getAttribute('data-src');
      im.removeAttribute('data-src');
    });
  }

  function show(slug, push) {
    var sp = SHOPS[slug];
    if (!sp) return;

    /* えらんだボタンに色をつけます */
    Array.prototype.forEach.call(picks, function (a) {
      a.classList.toggle('is-on', a.getAttribute('data-shop') === slug);
    });

    /* チラシの出し分け */
    var shown = 0;
    Array.prototype.forEach.call(items, function (el) {
      var id = el.getAttribute('data-flyer');
      var on = sp.ids.indexOf(parseInt(id, 10)) >= 0;
      el.hidden = !on;
      if (on) { hydrate(el); shown++; }
    });

    if (ask)  ask.hidden  = true;
    if (none) none.hidden = shown > 0;

    /* いま見ているお店の名前 */
    if (now) {
      now.hidden = false;
      var nm = document.getElementById('flyer-shopname');
      if (nm) nm.textContent = sp.name;
      var iv = document.getElementById('flyer-shopinfo');
      if (iv) {
        iv.textContent = sp.soon ? sp.soon
          : (sp.hours ? '営業時間 ' + sp.hours + (sp.hnote ? '（' + sp.hnote + '）' : '') : '');
      }
    }

    /* お店のご案内 */
    if (info) {
      info.hidden = false;
      var set = function (id, txt) { var e = document.getElementById(id); if (e) e.textContent = txt; };
      set('flyer-shopname2', sp.name);
      set('flyer-addr', sp.addr);
      set('flyer-hours', sp.soon ? sp.soon
        : (sp.hours ? '営業時間 ' + sp.hours + (sp.hnote ? '（' + sp.hnote + '）' : '') : ''));

      var telbtn = document.getElementById('flyer-teltop');
      var telnum = document.getElementById('flyer-tel');
      if (telbtn && telnum) {
        if (sp.tel) {
          telbtn.style.display = '';
          telbtn.href = 'tel:' + sp.tel;
          telnum.textContent = sp.tel;
        } else {
          telbtn.style.display = 'none';
        }
      }
      var map = document.getElementById('flyer-map');
      if (map) map.href = sp.map;
    }

    /* アドレス欄を書きかえます（読み込み直しはしません） */
    if (push && window.history && window.history.replaceState) {
      window.history.replaceState(null, '', '?shop=' + encodeURIComponent(slug));
    }
  }

  Array.prototype.forEach.call(picks, function (a) {
    a.addEventListener('click', function (e) {
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
      e.preventDefault();
      show(a.getAttribute('data-shop'), true);
      var t = document.getElementById('flyer');
      if (t) t.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });

  /* ?shop=◯◯ で来られたときは、はじめから出しておきます */
  var first = <?php echo wp_json_encode( $sel ); ?>;
  if (first) show(first, false);
})();
</script>
<?php endif; ?>

<?php get_footer();
