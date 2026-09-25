<?php
/**
 * 商品一覧の並べかえ（ドラッグ） ─ リフォームヤマキシ
 *
 * 置き場所： wp-content/themes/ymkrf/inc/functions-product-sort.php
 *
 * （2026/09/25 ユーザー指示
 *   「トイレのダッシュボード、【手洗いカウンター付】は下部へもってきて。
 *     ちなみに順序をこちらで変更できるようにしてほしいな、
 *     キッチン、ユニットバス、トイレ、洗面化粧台のダッシュボード一覧」）
 *
 * ■ どう使うか
 *   ダッシュボード → 商品 → 分類で「キッチン」などにしぼると、
 *   行をつかんで上下に動かせるようになります。
 *   手を放したところで、そのまま保存されます。
 *   「順序」の数字を打ち直す必要はありません。
 *
 * ■ 中で何をしているか
 *   並べたとおりに、上から 1, 2, 3 … と「順序」（wp_posts の menu_order）を
 *   ふり直しています。ホームページの商品一覧も、この順序で並びます。
 *
 * ■ 動かないとき
 *   ・分類でしぼっていないとき（ぜんぶの商品を見ているとき）
 *   ・見出し（価格など）を押して並べかえているとき
 *   ・検索しているとき、2ページ目を見ているとき
 *   は、まぎらわしいので、つかめないようにしてあります。
 */
if ( ! defined( 'ABSPATH' ) ) exit;


/** ドラッグで並べかえられる分類 */
function ymkrf_sortable_cats() {
	/* キッチン・ユニットバス・トイレ・洗面化粧台（ユーザー指示の4つ）に、
	   ふだんよく触る分類を足しています。増やしたいときは、ここに書きます。 */
	return array( 'kitchen', 'bathroom', 'toilet', 'lavatory', 'cooktop', 'pack4' );
}

/**
 * いまの画面が「ドラッグで並べかえてよい一覧」かどうか。
 * そうなら分類のスラッグを、ちがうなら '' を返します。
 */
function ymkrf_sortable_now() {

	if ( ! is_admin() ) return '';
	if ( ! current_user_can( 'edit_posts' ) ) return '';

	$s = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $s || $s->base !== 'edit' || $s->post_type !== 'ymkrf_product' ) return '';

	$cat = isset( $_GET['ymkrf_product_cat'] )
		? sanitize_title( wp_unslash( $_GET['ymkrf_product_cat'] ) ) : '';
	if ( ! in_array( $cat, ymkrf_sortable_cats(), true ) ) return '';

	/* 見出しで並べかえ中・検索中・2ページ目以降は、つかめないようにします */
	if ( ! empty( $_GET['orderby'] ) ) return '';
	if ( ! empty( $_GET['s'] ) ) return '';
	if ( ! empty( $_GET['paged'] ) && (int) $_GET['paged'] > 1 ) return '';
	if ( ! empty( $_GET['post_status'] ) && $_GET['post_status'] === 'trash' ) return '';

	return $cat;
}


/* ============================================================
   画面の仕掛け
   ============================================================ */
add_action( 'admin_enqueue_scripts', function () {

	$cat = ymkrf_sortable_now();
	if ( $cat === '' ) return;

	wp_enqueue_script( 'jquery-ui-sortable' );

	$nonce = wp_create_nonce( 'ymkrf_product_sort' );
	$ajax  = esc_url_raw( admin_url( 'admin-ajax.php' ) );

	$js = <<<JS
jQuery(function ($) {

  var \$list = $('#the-list');
  if (!\$list.length || \$list.find('tr').length < 2) return;

  /* 動かせることが分かるように、行の左はしに目じるしを出します */
  \$list.closest('table').addClass('ymkrf-sortable');

  var \$msg = $('<div class="ymkrf-sortmsg"></div>').insertBefore(\$list.closest('table'));

  function say(text, ok) {
    \$msg.text(text).css('color', ok ? '#0a6b2d' : '#b32d2e').show();
    if (ok) setTimeout(function () { \$msg.fadeOut(400); }, 2200);
  }

  \$list.sortable({
    items: '> tr',
    axis: 'y',
    cursor: 'move',
    opacity: 0.8,
    placeholder: 'ymkrf-sortph',
    /* 押しても動かないもの（リンクや入力欄）は、そのまま押せるようにします */
    cancel: 'a, input, select, button, textarea, .row-actions, .inline-edit-row',
    helper: function (e, tr) {
      var \$o = tr.children();
      var \$h = tr.clone();
      \$h.children().each(function (i) { $(this).width(\$o.eq(i).width()); });
      return \$h;
    },
    start: function () { \$msg.hide(); },
    update: function () {

      var ids = \$list.find('> tr').map(function () {
        return (this.id || '').replace('post-', '');
      }).get().filter(function (v) { return v !== ''; });

      if (!ids.length) return;

      \$msg.text('保存しています…').css('color', '#50575e').show();

      $.post('{$ajax}', {
        action: 'ymkrf_product_sort',
        nonce: '{$nonce}',
        ids: ids
      }).done(function (res) {
        if (res && res.success) say('並び順を保存しました。', true);
        else say('保存できませんでした。画面を読み込み直してください。', false);
      }).fail(function () {
        say('保存できませんでした。画面を読み込み直してください。', false);
      });
    }
  });
});
JS;

	wp_add_inline_script( 'jquery-ui-sortable', $js );

	$css = '
	.ymkrf-sortmsg{ margin:8px 0 6px; font-weight:700; display:none; }
	.ymkrf-sorttip{
		margin:12px 0 4px; padding:9px 12px; background:#fff;
		border-left:4px solid #fe3301; box-shadow:0 1px 1px rgba(0,0,0,.04);
	}
	.ymkrf-sorttip b{ color:#1d2327; }
	table.ymkrf-sortable #the-list > tr{ cursor:move; }
	table.ymkrf-sortable #the-list > tr:hover > .check-column{ background:#fff4f0; }
	table.ymkrf-sortable #the-list > tr.ymkrf-sortph{
		height:44px; background:#fff4f0; outline:2px dashed #fe3301; outline-offset:-2px;
	}
	';
	wp_add_inline_style( 'list-tables', $css );
} );

/** 一覧の上に、使いかたを一行だけ出します */
add_action( 'admin_notices', function () {

	if ( ymkrf_sortable_now() === '' ) return;
	echo '<div class="ymkrf-sorttip"><b>行をつかんで上下に動かすと、並び順を変えられます。</b>'
	   . ' 手を放したところで、そのまま保存されます。'
	   . 'ホームページの商品一覧も、この順番になります。</div>';
} );


/* ============================================================
   保存
   ============================================================ */
add_action( 'wp_ajax_ymkrf_product_sort', function () {

	check_ajax_referer( 'ymkrf_product_sort', 'nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( 'けんげんがありません' );
	}

	$ids = isset( $_POST['ids'] ) ? (array) wp_unslash( $_POST['ids'] ) : array();
	$ids = array_values( array_filter( array_map( 'intval', $ids ) ) );
	if ( ! $ids ) wp_send_json_error( 'からっぽです' );

	global $wpdb;
	$n = 0;

	foreach ( $ids as $i => $id ) {

		$p = get_post( $id );
		if ( ! $p || $p->post_type !== 'ymkrf_product' ) continue;
		if ( ! current_user_can( 'edit_post', $id ) ) continue;

		$order = $i + 1;
		if ( (int) $p->menu_order === $order ) continue;

		/* wp_update_post を使わないのは、更新日時や版（リビジョン）を
		   増やしたくないためです。並べかえただけで「直した」ことには
		   したくありません。 */
		$wpdb->update( $wpdb->posts, array( 'menu_order' => $order ), array( 'ID' => $id ) );
		clean_post_cache( $id );

		/* 前から使っている控えのほうも、いっしょにそろえておきます */
		update_post_meta( $id, '_ymkrf_order', $order );

		$n++;
	}

	wp_send_json_success( array( 'changed' => $n ) );
} );
