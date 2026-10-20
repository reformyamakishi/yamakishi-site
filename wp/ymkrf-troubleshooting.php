<?php
/**
 * ymkrf-troubleshooting.php ─ エラーコード一覧（/troubleshooting/）
 *
 * 置き場所： wp-content/themes/ymkrf/ymkrf-troubleshooting.php
 *
 * 1枚で3つの階層をまかないます。
 *   /troubleshooting/                        … 入口（給湯器の種類をえらぶ）
 *   /troubleshooting/<種類>-error/            … メーカーをえらぶ
 *   /troubleshooting/<種類>-error/<メーカー>/  … エラーコードの一覧
 *
 * ★中身は、管理画面の左メニュー「エラーコード」で直せます（2026/10/20〜）。
 *   しくみは inc/functions-errcode.php に書いてあります。
 *
 * 2026/10/20 見やすく作りなおしました（ユーザー指示「見やすいようにバージョンあっぷ。内容は同じで」
 *   「特にエラーコードの英数字をもっとひと目でわかるように」）
 *   ・リモコンの番号を入れると、その場でしぼりこめる検索の欄
 *   ・ページの上に、コードだけを並べた早見表（押すとそのコードへ）
 *   ・コードは大きな太い字で、色の付いた札に
 *   ・対処方法の ①②③ は、番号つきの箇条書きに
 *
 * ※ お客様がご自分で直せるかどうかの判断は、機種によって変わります。
 *   「必ず直ります」といった言い切りは書かないでください。
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$asset = get_stylesheet_directory_uri();
$types = function_exists( 'ymkrf_ts_types' ) ? ymkrf_ts_types() : array();

/* いま開いているのは、どの階層か */
$ts_cat   = (string) get_query_var( 'ymkrf_ts_cat' );
$ts_maker = (string) get_query_var( 'ymkrf_ts_maker' );
if ( $ts_cat !== '' && ! isset( $types[ $ts_cat ] ) ) { $ts_cat = ''; $ts_maker = ''; }

$ts_post = ( $ts_cat && $ts_maker ) ? ymkrf_ts_get( $ts_cat, $ts_maker ) : null;
if ( $ts_maker !== '' && ! $ts_post ) {
	/* 無いメーカーは「ページがありません」に */
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
	include get_query_template( '404' ) ?: get_index_template();
	return;
}
$ts_mname = $ts_post ? (string) get_post_meta( $ts_post->ID, '_ymkrf_ts_maker_name', true ) : '';
$ts_rows  = $ts_post ? ymkrf_ts_rows( $ts_cat, $ts_maker, $ts_post ) : array();
/* 欄がそろっていない行があっても困らないように。あわせて、ページ内リンク用の名前（#code-102 など）を作ります */
$ts_used = array();
foreach ( $ts_rows as $i => $r ) {
	$r = array_merge( array( 'code' => '', 'title' => '', 'cause' => '', 'fix' => '' ), (array) $r );
	$a = 'code-' . ( sanitize_title( $r['code'] ) !== '' ? sanitize_title( $r['code'] ) : $i );
	if ( isset( $ts_used[ $a ] ) ) $a .= '-' . $i;
	$ts_used[ $a ] = true;
	$r['anchor'] = $a;
	$ts_rows[ $i ] = $r;
}

$ts_url = function ( $cat = '', $maker = '' ) {
	$u = home_url( '/troubleshooting/' );
	if ( $cat )   $u .= $cat . '-error/';
	if ( $maker ) $u .= $maker . '/';
	return $u;
};

/** 対処方法の文を、①②③ ごとの箇条書きにします（番号が無ければ段落のまま） */
$ts_steps = function ( $text ) {
	$text  = trim( (string) $text );
	if ( $text === '' ) return '';
	$parts = preg_split( '/(?=[①②③④⑤⑥⑦⑧⑨⑩])/u', $text, -1, PREG_SPLIT_NO_EMPTY );
	$parts = array_values( array_filter( array_map( 'trim', $parts ), 'strlen' ) );
	$numbered = count( $parts ) > 1 || ( $parts && preg_match( '/^[①-⑩]/u', $parts[0] ) );
	if ( ! $numbered ) return '<p class="p-ec__txt">' . nl2br( esc_html( $text ) ) . '</p>';
	$html = '<ol class="p-ec__steps">';
	foreach ( $parts as $p ) {
		$no = '';
		if ( preg_match( '/^([①-⑩])\s*(.*)$/us', $p, $m ) ) { $no = $m[1]; $p = $m[2]; }
		$html .= '<li>' . ( $no !== '' ? '<span class="p-ec__no">' . esc_html( $no ) . '</span>' : '' )
		       . '<span>' . nl2br( esc_html( trim( $p ) ) ) . '</span></li>';
	}
	return $html . '</ol>';
};

/* 種類ごとの交換のご案内（商品カテゴリへ） */
$ts_prod_url = ( $ts_cat && function_exists( 'ymkrf_cat_url' ) ) ? ymkrf_cat_url( $types[ $ts_cat ]['product'] ) : '';
$ts_line     = 'https://line.me/R/ti/p/@233okcdx';

get_header();
?>

<!-- =========== パンくず =========== -->
<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <?php if ( $ts_cat ) : ?>
      <li><a href="<?php echo esc_url( $ts_url() ); ?>">エラーコード一覧</a></li>
      <?php if ( $ts_post ) : ?>
        <li><a href="<?php echo esc_url( $ts_url( $ts_cat ) ); ?>"><?php echo esc_html( $types[ $ts_cat ]['name'] ); ?></a></li>
        <li><?php echo esc_html( $ts_mname ); ?></li>
      <?php else : ?>
        <li><?php echo esc_html( $types[ $ts_cat ]['name'] ); ?></li>
      <?php endif; ?>
    <?php else : ?>
      <li>エラーコード一覧</li>
    <?php endif; ?>
  </ol>
</nav>
<?php
if ( function_exists( 'ymkrf_crumb_ld' ) ) {
	$crumb = array( array( 'ホーム', home_url( '/' ) ), array( 'エラーコード一覧', $ts_url() ) );
	if ( $ts_cat )  $crumb[] = array( $types[ $ts_cat ]['name'], $ts_url( $ts_cat ) );
	if ( $ts_post ) $crumb[] = array( $ts_mname, $ts_url( $ts_cat, $ts_maker ) );
	ymkrf_crumb_ld( $crumb );
}
?>

<main id="main">

<!-- =========== 見出し =========== -->
<section class="p-guide__hero">
  <div class="l-wrap">
    <span class="c-head__en">ERROR CODE</span>
    <?php if ( $ts_post ) : ?>
      <p class="p-ts__device"><?php echo esc_html( $types[ $ts_cat ]['name'] ); ?></p>
      <h1 class="p-guide__title">
        <?php echo esc_html( $ts_mname ); ?>の<br class="sp-only">エラーコード一覧
      </h1>
      <p class="p-guide__lead">
        リモコンに出ている番号を、下の欄に入れてください。<br class="pc-only">
        原因と対処方法がすぐに見つかります（全<?php echo (int) count( $ts_rows ); ?>件）。
      </p>
    <?php elseif ( $ts_cat ) : ?>
      <p class="p-ts__device"><?php echo esc_html( $types[ $ts_cat ]['name'] ); ?></p>
      <h1 class="p-guide__title">エラーコード一覧</h1>
      <p class="p-guide__lead">メーカーをお選びください。</p>
    <?php else : ?>
      <h1 class="p-guide__title">エラーコード一覧</h1>
      <p class="p-guide__lead">
        故障かな？！　修理をご依頼になる前に。<br class="pc-only">
        よくあるエラー表示について、その原因と対処方法をご案内します。<br>
        まずはお家の給湯器の種類をお選びください。
      </p>
    <?php endif; ?>
    <img class="p-guide__herochara" src="<?php echo esc_url( $asset ); ?>/assets/img/character/char-wrench.webp"
         width="592" height="640"
         alt="ヤマキシのキャラクター「とんとこトン」がレンチを持っているイラスト"
         fetchpriority="high">
  </div>
</section>

<?php if ( ! $ts_cat ) : ?>
<!-- =========== 入口：種類をえらぶ =========== -->
<section class="l-section">
  <div class="l-wrap">
    <div class="p-ts__cats">
      <?php foreach ( $types as $ck => $cv ) : ?>
        <a class="p-ts__cat" href="<?php echo esc_url( $ts_url( $ck ) ); ?>">
          <span class="p-ts__catttl"><?php echo esc_html( $cv['name'] ); ?></span>
          <span class="p-ts__catlead"><?php echo esc_html( $cv['lead'] ); ?></span>
          <span class="p-ts__catlink"><?php echo esc_html( $cv['name'] ); ?>のエラーコード一覧</span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php elseif ( ! $ts_post ) : ?>
<!-- =========== 種類：メーカーをえらぶ =========== -->
<section class="l-section">
  <div class="l-wrap">
    <div class="p-ts__makers">
      <?php foreach ( ymkrf_ts_lists( $ts_cat ) as $p ) :
        $mk = (string) get_post_meta( $p->ID, '_ymkrf_ts_maker', true );
        $mn = (string) get_post_meta( $p->ID, '_ymkrf_ts_maker_name', true );
        $nn = count( ymkrf_ts_rows( $ts_cat, $mk, $p ) );
        if ( $mk === '' ) continue; ?>
        <a class="p-ts__maker" href="<?php echo esc_url( $ts_url( $ts_cat, $mk ) ); ?>">
          <?php echo esc_html( $mn ); ?>のエラーコード一覧
          <?php if ( $nn ) : ?><small class="p-ec__mkn"><?php echo (int) $nn; ?>件</small><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
    <p class="p-guide__note">
      <a href="<?php echo esc_url( $ts_url() ); ?>">← 給湯器の種類をえらびなおす</a>
    </p>
  </div>
</section>

<?php else : ?>
<!-- =========== メーカー：エラーコード =========== -->
<section class="l-section p-ec">
  <div class="l-wrap">

    <?php if ( $ts_rows ) : ?>

      <!-- 番号でさがす（上に貼りつきます） -->
      <div class="p-ec__search" role="search">
        <label class="p-ec__searchlbl" for="p-ec-q">リモコンの番号でさがす</label>
        <div class="p-ec__searchbox">
          <input type="search" id="p-ec-q" class="p-ec__input" inputmode="text" autocomplete="off"
                 placeholder="例：<?php echo esc_attr( $ts_rows[0]['code'] ); ?>" aria-describedby="p-ec-hit">
          <button type="button" class="p-ec__clear" aria-label="入れた番号を消す" hidden>×</button>
        </div>
        <p class="p-ec__hit" id="p-ec-hit" aria-live="polite">全<?php echo (int) count( $ts_rows ); ?>件</p>
      </div>

      <!-- コードの早見表（押すとそのコードへ） -->
      <details class="p-ec__index" open>
        <summary>コードの早見表（<?php echo (int) count( $ts_rows ); ?>件）<span>押すと、そのコードへ移動します</span></summary>
        <ul class="p-ec__chips">
          <?php foreach ( $ts_rows as $i => $r ) : if ( $r['code'] === '' ) continue; ?>
            <li><a href="#<?php echo esc_attr( $r['anchor'] ); ?>"><?php echo esc_html( $r['code'] ); ?></a></li>
          <?php endforeach; ?>
        </ul>
      </details>

      <ol class="p-ec__list">
        <?php foreach ( $ts_rows as $i => $r ) :
          $find = $r['code'] . ' ' . $r['title'] . ' ' . $r['cause']; ?>
          <li class="p-ec__card" id="<?php echo esc_attr( $r['anchor'] ); ?>"
              data-code="<?php echo esc_attr( $r['code'] ); ?>" data-find="<?php echo esc_attr( $find ); ?>">
            <div class="p-ec__code">
              <span class="p-ec__codelbl">エラー</span>
              <span class="p-ec__codeval"><?php echo esc_html( $r['code'] ); ?></span>
            </div>
            <div class="p-ec__body">
              <h2 class="p-ec__ttl"><?php
                /* 「内容」が無い表は、原因の文をそのまま見出しにします */
                echo esc_html( $r['title'] !== '' ? $r['title'] : $r['cause'] ); ?></h2>
              <?php if ( $r['title'] !== '' && $r['cause'] !== '' ) : ?>
                <div class="p-ec__blk">
                  <span class="p-ec__tag">原因</span>
                  <p class="p-ec__txt"><?php echo nl2br( esc_html( $r['cause'] ) ); ?></p>
                </div>
              <?php endif; ?>
              <?php if ( $r['fix'] !== '' ) : ?>
                <div class="p-ec__blk p-ec__blk--fix">
                  <span class="p-ec__tag p-ec__tag--fix">対処方法</span>
                  <?php echo $ts_steps( $r['fix'] ); /* phpcs:ignore */ ?>
                </div>
              <?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>

      <div class="p-ec__none" hidden>
        <p class="p-ec__nonettl">その番号は、この表にありません</p>
        <p>番号をもう一度お確かめください。それでも見つからないときは、番号をLINEかお電話でお知らせください。お調べします。</p>
      </div>

    <?php else : ?>
      <div class="p-ts__soon">
        <p class="p-ts__soonttl">ただいま準備中です</p>
        <p><?php echo esc_html( $ts_mname ); ?>の<?php echo esc_html( $types[ $ts_cat ]['name'] ); ?>のエラーコード一覧は、ただいま準備しています。<br>
          お急ぎのときは、お近くの店舗までお電話ください。エラーの数字をお伝えいただければお調べします。</p>
      </div>
    <?php endif; ?>

    <!-- 直らないとき（いちばん来てほしいのは LINE なので、先頭に置きます） -->
    <div class="p-ec__help">
      <p class="p-ec__helpttl">エラーが消えない・何度も出るときは</p>
      <p class="p-ec__helptxt">
        エラーの番号を送っていただければ、修理で直るか、交換がよいかをご案内します。<br class="pc-only">
        水がもれている、こげたようなにおいがするときは、お使いにならずにご連絡ください。
      </p>
      <div class="p-ec__helpbtns">
        <a class="p-ec__btn p-ec__btn--line" href="<?php echo esc_url( $ts_line ); ?>" rel="noopener" data-cta="errcode-line">LINEで番号を送って相談</a>
        <a class="p-ec__btn p-ec__btn--tel" href="tel:0800-777-3331" data-cta="errcode-tel">電話する 0800-777-3331</a>
        <?php if ( $ts_prod_url ) : ?>
          <a class="p-ec__btn p-ec__btn--prd" href="<?php echo esc_url( $ts_prod_url ); ?>"><?php
            echo esc_html( $types[ $ts_cat ]['product'] === 'ecocute' ? 'エコキュート' : '給湯器' ); ?>の交換価格を見る</a>
        <?php endif; ?>
      </div>
    </div>

    <p class="p-guide__note">
      ※ エラーの内容は、機種や年式によって変わることがあります。
    </p>

    <div class="p-ts__nav">
      <a href="<?php echo esc_url( $ts_url( $ts_cat ) ); ?>">← ほかのメーカーを見る</a>
      <a href="<?php echo esc_url( $ts_url() ); ?>">エラーコード一覧のトップへ</a>
    </div>

    <?php if ( current_user_can( 'edit_post', $ts_post->ID ) ) : ?>
      <p class="p-ec__edit"><a href="<?php echo esc_url( get_edit_post_link( $ts_post->ID ) ); ?>">✎ この表を管理画面で直す</a></p>
    <?php endif; ?>

  </div>
</section>

<script>
/* 番号でしぼりこみ（全角・小文字・ハイフンのちがいは気にしません） */
(function () {
  var q = document.getElementById('p-ec-q');
  if (!q) return;
  /* 上に貼りつく検索の欄が、ヘッダーの下にちょうど来るように、ヘッダーの高さを測ります */
  var hd = document.querySelector('.p-header') || document.querySelector('header');
  function setTop(){ if (hd) document.documentElement.style.setProperty('--header-h', (hd.getBoundingClientRect().height || 64) + 'px'); }
  setTop(); window.addEventListener('resize', setTop);
  /* ヘッダーは下にすすむと低くなるので、高さが変わるたびに合わせます */
  if (hd && window.ResizeObserver) new ResizeObserver(setTop).observe(hd);
  /* スマホでは、コードの早見表は閉じておきます（押すと開きます） */
  var ix = document.querySelector('.p-ec__index');
  if (ix && window.innerWidth < 768) ix.removeAttribute('open');
  var cards = [].slice.call(document.querySelectorAll('.p-ec__card'));
  var hit   = document.getElementById('p-ec-hit');
  var none  = document.querySelector('.p-ec__none');
  var clr   = document.querySelector('.p-ec__clear');
  var idx   = document.querySelector('.p-ec__index');
  var total = cards.length;
  function norm(s) {
    return String(s || '').replace(/[！-～]/g, function (c) { return String.fromCharCode(c.charCodeAt(0) - 0xFEE0); })
      .replace(/[\s　\-－ー―]/g, '').toUpperCase();
  }
  cards.forEach(function (c) { c._code = norm(c.getAttribute('data-code')); c._find = norm(c.getAttribute('data-find')); });
  function run() {
    var v = norm(q.value), n = 0, exact = null;
    cards.forEach(function (c) {
      var ok = v === '' || c._code.indexOf(v) === 0 || (v.length >= 2 && c._find.indexOf(v) >= 0);
      c.hidden = !ok;
      c.classList.toggle('is-hit', v !== '' && c._code === v);
      if (ok) n++;
      if (v !== '' && c._code === v && !exact) exact = c;
    });
    hit.textContent = v === '' ? '全' + total + '件' : n + '件 見つかりました';
    none.hidden = !(v !== '' && n === 0);
    clr.hidden = v === '';
    if (idx) idx.hidden = v !== '';
  }
  q.addEventListener('input', run);
  clr.addEventListener('click', function () { q.value = ''; run(); q.focus(); });
})();
</script>

<?php endif; ?>

</main>

<?php get_footer(); ?>
