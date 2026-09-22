<?php
/**
 * functions-check.php ─ 1ページずつ見たかどうかの印（チェック済）
 * 置き場所： wp-content/themes/ymkrf/inc/functions-check.php
 *
 * （2026/09/22 ユーザー指示
 *   「施工事例と、お客様の声とコラムを1ページずつチェックしていきます。
 *     公開まで、チェックしたかどうかのチェック欄を一覧で見えるようにつくって。
 *     本番前に✓がすべてついてるか確認もしたいし」）
 *
 * ■ どういうものか
 *   一覧のいちばん左に「確認」の列が出ます。チェックを入れると、
 *   その場で保存されます（ページの読み込み直しは要りません）。
 *   だれが・いつ入れたかも控えるので、マウスを乗せると出ます。
 *
 *   一覧の上に「チェック済 123 / 2,169（のこり 2,046）」と出ます。
 *   「未チェックだけ」を押すと、まだのものだけが並びます。
 *
 * ■ 使う投稿の種類
 *   施工事例（ymkrf_works）／お客様の声（ymkrf_voice）／コラム（ymkrf_column）
 *
 * ■ 名前について
 *   保存さき … _ymkrf_ok（1）／_ymkrf_ok_by（だれが）／_ymkrf_ok_at（いつ）
 *
 *   ※お客様の声の「要確認をさがす」（inc/functions-voice-check.php）とは別ものです。
 *     あちらは「直すところが見つかった」印、こちらは「人が目で見た」印です。
 */
if ( ! defined( 'ABSPATH' ) ) exit;


/** この印を使う投稿の種類 */
function ymkrf_ok_types() {
	return array( 'ymkrf_works', 'ymkrf_voice', 'ymkrf_column' );
}

/** チェック済の件数と全体の件数 */
function ymkrf_ok_counts( $type ) {

	global $wpdb;

	$all = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->posts}
		  WHERE post_type = %s AND post_status IN ('publish','draft','pending','private','future')",
		$type
	) );

	$ok = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->posts} p
		   INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_ymkrf_ok' AND m.meta_value = '1'
		  WHERE p.post_type = %s AND p.post_status IN ('publish','draft','pending','private','future')",
		$type
	) );

	return array( 'all' => $all, 'ok' => $ok );
}


/* ============================================================
   1. 一覧の列
   ============================================================ */

foreach ( ymkrf_ok_types() as $ymkrf_ok_t ) {

	add_filter( "manage_{$ymkrf_ok_t}_posts_columns", function ( $cols ) {
		$new = array();
		foreach ( $cols as $k => $v ) {
			$new[ $k ] = $v;
			if ( $k === 'cb' ) $new['ymkrf_ok'] = '確認';
		}
		if ( ! isset( $new['ymkrf_ok'] ) ) $new['ymkrf_ok'] = '確認';
		return $new;
	} );

	add_action( "manage_{$ymkrf_ok_t}_posts_custom_column", function ( $col, $post_id ) {

		if ( $col !== 'ymkrf_ok' ) return;

		$on = get_post_meta( $post_id, '_ymkrf_ok', true ) === '1';
		$by = (string) get_post_meta( $post_id, '_ymkrf_ok_by', true );
		$at = (string) get_post_meta( $post_id, '_ymkrf_ok_at', true );

		$tip = $on
			? trim( 'チェック済' . ( $at !== '' ? '：' . $at : '' ) . ( $by !== '' ? '（' . $by . '）' : '' ) )
			: 'まだ見ていません。押すとチェックが入ります';

		printf(
			'<label class="ymkrf-ok__wrap" title="%s">'
			. '<input type="checkbox" class="ymkrf-ok" data-id="%d" %s>'
			. '<span class="ymkrf-ok__mark"></span></label>',
			esc_attr( $tip ), (int) $post_id, checked( $on, true, false )
		);
	}, 10, 2 );

	/* 見出しを押すと、チェックの有無でならべ替えられます */
	add_filter( "manage_edit-{$ymkrf_ok_t}_sortable_columns", function ( $cols ) {
		$cols['ymkrf_ok'] = 'ymkrf_ok';
		return $cols;
	} );
}


/* ============================================================
   2. 「未チェックだけ」でしぼる
   ============================================================ */

add_action( 'pre_get_posts', function ( $q ) {

	if ( ! is_admin() || ! $q->is_main_query() ) return;
	$type = $q->get( 'post_type' );
	if ( ! in_array( $type, ymkrf_ok_types(), true ) ) return;

	/* ならべ替え */
	if ( $q->get( 'orderby' ) === 'ymkrf_ok' ) {
		$q->set( 'meta_key', '_ymkrf_ok' );
		$q->set( 'orderby', 'meta_value' );
	}

	if ( ! isset( $_GET['ymkrf_ok'] ) ) return;

	$v  = sanitize_text_field( wp_unslash( $_GET['ymkrf_ok'] ) );
	$mq = (array) $q->get( 'meta_query' );

	if ( $v === '1' ) {
		$mq[] = array( 'key' => '_ymkrf_ok', 'value' => '1' );
	} elseif ( $v === '0' ) {
		$mq[] = array(
			'relation' => 'OR',
			array( 'key' => '_ymkrf_ok', 'compare' => 'NOT EXISTS' ),
			array( 'key' => '_ymkrf_ok', 'value' => '1', 'compare' => '!=' ),
		);
	}
	$q->set( 'meta_query', $mq );
} );


/* ============================================================
   3. 一覧の上の進みぐあい
   ============================================================ */

add_action( 'admin_notices', function () {

	$s = get_current_screen();
	if ( ! $s || $s->base !== 'edit' ) return;
	if ( ! in_array( $s->post_type, ymkrf_ok_types(), true ) ) return;

	$c    = ymkrf_ok_counts( $s->post_type );
	$rest = max( 0, $c['all'] - $c['ok'] );
	$pct  = $c['all'] ? round( $c['ok'] * 100 / $c['all'] ) : 0;
	$url  = admin_url( 'edit.php?post_type=' . $s->post_type );
	$now  = isset( $_GET['ymkrf_ok'] ) ? sanitize_text_field( wp_unslash( $_GET['ymkrf_ok'] ) ) : '';
	?>
	<div class="notice <?php echo $rest === 0 ? 'notice-success' : 'notice-info'; ?>"
	     style="padding:12px 14px">
	  <p style="margin:0 0 8px;font-size:14px">
	    <b>チェック済 <?php echo number_format( $c['ok'] ); ?></b>
	    / <?php echo number_format( $c['all'] ); ?> 件
	    （のこり <b><?php echo number_format( $rest ); ?></b> 件）
	    <?php if ( $rest === 0 && $c['all'] > 0 ) : ?>
	      　<b style="color:#0a6b2d">ぜんぶ見おわりました。</b>
	    <?php endif; ?>
	  </p>
	  <div style="height:9px;background:#e6e0dd;border-radius:999px;overflow:hidden;max-width:520px">
	    <div style="height:100%;width:<?php echo (int) $pct; ?>%;background:#fe3301"></div>
	  </div>
	  <p style="margin:8px 0 0;font-size:13px">
	    <a href="<?php echo esc_url( $url ); ?>"<?php if ( $now === '' ) echo ' style="font-weight:700"'; ?>>すべて</a> ｜
	    <a href="<?php echo esc_url( add_query_arg( 'ymkrf_ok', '0', $url ) ); ?>"<?php
	      if ( $now === '0' ) echo ' style="font-weight:700"'; ?>>未チェックだけ</a> ｜
	    <a href="<?php echo esc_url( add_query_arg( 'ymkrf_ok', '1', $url ) ); ?>"<?php
	      if ( $now === '1' ) echo ' style="font-weight:700"'; ?>>チェック済だけ</a>
	  </p>
	</div>
	<?php
} );


/* ============================================================
   4. その場で保存（押したらすぐ入ります）
   ============================================================ */

add_action( 'admin_footer', function () {

	$s = get_current_screen();
	if ( ! $s || $s->base !== 'edit' ) return;
	if ( ! in_array( $s->post_type, ymkrf_ok_types(), true ) ) return;
	?>
	<style>
	  .column-ymkrf_ok{ width:54px; text-align:center }
	  .ymkrf-ok__wrap{ display:inline-flex; cursor:pointer; padding:3px }
	  .ymkrf-ok{ width:20px; height:20px; cursor:pointer }
	  .ymkrf-ok__wrap.is-saving{ opacity:.4 }
	</style>
	<script>
	jQuery(function($){
	  $(document).on('change', '.ymkrf-ok', function(){
	    var $c = $(this), $w = $c.closest('.ymkrf-ok__wrap');
	    $w.addClass('is-saving');
	    $.post(ajaxurl, {
	      action: 'ymkrf_ok_toggle',
	      id:     $c.data('id'),
	      on:     $c.is(':checked') ? 1 : 0,
	      _nonce: '<?php echo esc_js( wp_create_nonce( 'ymkrf_ok' ) ); ?>'
	    }).done(function(r){
	      $w.removeClass('is-saving');
	      if ( r && r.success && r.data ) {
	        $w.attr('title', r.data.tip);
	        /* 上の件数も書きかえます */
	        var $n = $('.notice b').first();
	        if ( $n.length ) $n.text('チェック済 ' + r.data.ok);
	      }
	    }).fail(function(){
	      $w.removeClass('is-saving');
	      window.alert('保存できませんでした。画面を読み込み直してから、もう一度お試しください。');
	      $c.prop('checked', !$c.is(':checked'));
	    });
	  });
	});
	</script>
	<?php
} );

add_action( 'wp_ajax_ymkrf_ok_toggle', function () {

	check_ajax_referer( 'ymkrf_ok', '_nonce' );

	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	$on = ! empty( $_POST['on'] );

	if ( ! $id || ! current_user_can( 'edit_post', $id ) ) wp_send_json_error();
	$type = get_post_type( $id );
	if ( ! in_array( $type, ymkrf_ok_types(), true ) ) wp_send_json_error();

	if ( $on ) {
		$u  = wp_get_current_user();
		$by = $u && $u->display_name ? $u->display_name : '';
		$at = wp_date( 'Y/m/d' );
		update_post_meta( $id, '_ymkrf_ok', '1' );
		update_post_meta( $id, '_ymkrf_ok_by', $by );
		update_post_meta( $id, '_ymkrf_ok_at', $at );
		$tip = 'チェック済：' . $at . ( $by !== '' ? '（' . $by . '）' : '' );
	} else {
		delete_post_meta( $id, '_ymkrf_ok' );
		delete_post_meta( $id, '_ymkrf_ok_by' );
		delete_post_meta( $id, '_ymkrf_ok_at' );
		$tip = 'まだ見ていません。押すとチェックが入ります';
	}

	$c = ymkrf_ok_counts( $type );
	wp_send_json_success( array( 'tip' => $tip, 'ok' => number_format( $c['ok'] ) ) );
} );


/* ============================================================
   5. 本番公開の前に、まとめて見る画面
      ダッシュボード ＞ ツール ＞ 公開前チェック
   ============================================================ */

add_action( 'admin_menu', function () {
	add_management_page( '公開前チェック', '公開前チェック', 'manage_options',
		'ymkrf-ok', 'ymkrf_ok_page' );
} );

function ymkrf_ok_page() {

	if ( ! current_user_can( 'manage_options' ) ) return;

	$label = array(
		'ymkrf_works'  => '施工事例',
		'ymkrf_voice'  => 'お客様の声',
		'ymkrf_column' => 'コラム',
	);
	?>
	<div class="wrap">
	  <h1>公開前チェック</h1>
	  <p style="max-width:860px;line-height:1.9">
	    1ページずつ目で見たものに、一覧で「確認」のチェックを入れていきます。<br>
	    <b>ここが全部そろったら、本番公開の準備ができた合図です。</b>
	  </p>

	  <table class="wp-list-table widefat striped" style="max-width:860px">
	    <thead><tr><th>種類</th><th style="width:130px">チェック済</th>
	      <th style="width:110px">のこり</th><th>進みぐあい</th><th style="width:150px"></th></tr></thead>
	    <tbody>
	    <?php
	    $all_done = true;
	    foreach ( $label as $type => $name ) :
	      $c    = ymkrf_ok_counts( $type );
	      $rest = max( 0, $c['all'] - $c['ok'] );
	      $pct  = $c['all'] ? round( $c['ok'] * 100 / $c['all'] ) : 100;
	      if ( $rest > 0 ) $all_done = false;
	      $url  = add_query_arg( array( 'post_type' => $type, 'ymkrf_ok' => '0' ), admin_url( 'edit.php' ) );
	    ?>
	      <tr>
	        <td><b><?php echo esc_html( $name ); ?></b></td>
	        <td><?php echo number_format( $c['ok'] ); ?> / <?php echo number_format( $c['all'] ); ?></td>
	        <td<?php if ( $rest > 0 ) echo ' style="color:#b32d2e;font-weight:700"'; ?>><?php
	          echo number_format( $rest ); ?></td>
	        <td>
	          <div style="height:9px;background:#e6e0dd;border-radius:999px;overflow:hidden">
	            <div style="height:100%;width:<?php echo (int) $pct; ?>%;background:#fe3301"></div>
	          </div>
	        </td>
	        <td><?php if ( $rest > 0 ) : ?>
	          <a class="button" href="<?php echo esc_url( $url ); ?>">未チェックを見る</a>
	        <?php else : ?><span style="color:#0a6b2d;font-weight:700">完了</span><?php endif; ?></td>
	      </tr>
	    <?php endforeach; ?>
	    </tbody>
	  </table>

	  <?php if ( $all_done ) : ?>
	    <div class="notice notice-success" style="margin-top:18px;max-width:860px">
	      <p><b>すべてチェックが入りました。</b></p>
	    </div>
	  <?php endif; ?>
	</div>
	<?php
}
