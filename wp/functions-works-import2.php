<?php
/**
 * 施工事例を、いまの公開サイトから「まとめて」取り込むしくみ。
 *
 * ・1件ぶんの取り込みは、すでにある ymkrf_works_import_one() をそのまま使います。
 *   （公開ページを読んで、写真も項目も取ってくる、実績のある処理です）
 * ・このファイルは、その処理に「2,169件ぶんの行列をつくって、
 *   20件ずつ順ぐりに渡す」役目だけをします。
 *
 * 使いかた
 *   1. wp-content/ymkrf-works.json を置く
 *      （本番サイトの管理画面から読み取った中身。案件No.のあるものだけ）
 *   2. 管理画面「施工事例 ＞ まとめて取り込み」をひらく
 *   3.「つづきを20件 取り込む」を押していく。押すたびに20件ずつ進みます
 *
 * ※ 取り込みが終わったら、このファイルと、functions.php の読み込み行を
 *    消してください。
 *
 * @package ymkrf
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'YMKRF_BULK_FILE', WP_CONTENT_DIR . '/ymkrf-works.json' );
define( 'YMKRF_BULK_POS',  'ymkrf_works_bulk_pos' );   // どこまで済んだか
define( 'YMKRF_BULK_LOG',  'ymkrf_works_bulk_log' );   // 記録


/** 読み取ったデータを配列で返します */
function ymkrf_bulk_rows() {
	static $rows = null;
	if ( $rows !== null ) return $rows;

	$rows = array();
	if ( file_exists( YMKRF_BULK_FILE ) ) {
		$j = json_decode( (string) file_get_contents( YMKRF_BULK_FILE ), true );
		if ( isset( $j['rows'] ) ) $j = $j['rows'];
		if ( is_array( $j ) ) $rows = $j;
	}
	return $rows;
}

/** 1件ぶんの、公開ページのURLを組み立てます */
function ymkrf_bulk_url( $r ) {
	$c = isset( $r['c'] ) ? preg_replace( '/[^a-z]/', '', $r['c'] ) : '';
	$i = isset( $r['i'] ) ? preg_replace( '/[^0-9]/', '', $r['i'] ) : '';
	if ( $c === '' || $i === '' ) return '';
	return 'https://' . YMKRF_IMPORT_HOST . '/works/' . $c . '/' . $i . '/';
}


/* ============================================================
   裏で少しずつ取り込みつづけるしくみ

   ブラウザのタブを開いたままにしなくても進むようにします。
   管理画面のどこかを見ているあいだ、1分おきに10件ずつ取り込みます。
   「自動で取り込む」のボタンで、始める・止めるができます。
   ============================================================ */

define( 'YMKRF_BULK_AUTO', 'ymkrf_works_bulk_auto' );

add_action( 'ymkrf_bulk_tick', 'ymkrf_bulk_tick' );

function ymkrf_bulk_tick() {

	if ( get_option( YMKRF_BULK_AUTO ) !== '1' ) return;

	$rows = ymkrf_bulk_rows();
	$pos  = (int) get_option( YMKRF_BULK_POS, 0 );
	if ( ! $rows || $pos >= count( $rows ) ) {
		update_option( YMKRF_BULK_AUTO, '', false );   /* 終わったら止めます */
		return;
	}

	$log = (array) get_option( YMKRF_BULK_LOG, array() );
	$n = 0; $t0 = time();

	while ( $pos < count( $rows ) && $n < 10 && ( time() - $t0 ) < 100 ) {
		$r   = $rows[ $pos ];
		$url = ymkrf_bulk_url( $r );
		$no  = isset( $r['process_num'] ) ? trim( $r['process_num'] ) : '';
		if ( $url === '' ) {
			array_unshift( $log, 'NG : URLが作れませんでした（' . $no . '）' );
		} else {
			$res = ymkrf_works_import_one( $url, $no );
			array_unshift( $log, ( $res['ok'] ? 'OK' : 'NG' ) . ' : ' . wp_strip_all_tags( $res['msg'] ) );
		}
		$pos++; $n++;
	}

	update_option( YMKRF_BULK_POS, $pos, false );
	update_option( YMKRF_BULK_LOG, array_slice( $log, 0, 80 ), false );

	/* つぎの回を予約します */
	if ( $pos < count( $rows ) ) {
		wp_schedule_single_event( time() + 30, 'ymkrf_bulk_tick' );
	} else {
		update_option( YMKRF_BULK_AUTO, '', false );
	}
}

/* 予約が消えてしまったときのために、管理画面を見るたびに見はります */
add_action( 'admin_init', function () {
	if ( get_option( YMKRF_BULK_AUTO ) !== '1' ) return;
	if ( ! wp_next_scheduled( 'ymkrf_bulk_tick' ) ) {
		wp_schedule_single_event( time() + 10, 'ymkrf_bulk_tick' );
	}
} );


/* ============================================================
   管理画面
   ============================================================ */

add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=ymkrf_works',
		'まとめて取り込み', 'まとめて取り込み',
		'manage_options', 'ymkrf-works-bulk', 'ymkrf_bulk_page'
	);
}, 30 );

function ymkrf_bulk_page() {

	if ( ! current_user_can( 'manage_options' ) ) return;

	$rows = ymkrf_bulk_rows();
	$pos  = (int) get_option( YMKRF_BULK_POS, 0 );
	$log  = (array) get_option( YMKRF_BULK_LOG, array() );

	/* 20件ぶん進めます */
	if ( isset( $_POST['ymkrf_bulk_go'] ) && check_admin_referer( 'ymkrf_bulk' ) ) {

		$want = isset( $_POST['n'] ) ? max( 1, min( 50, (int) $_POST['n'] ) ) : 20;
		$n = 0;
		$t0 = time();

		while ( $pos < count( $rows ) && $n < $want && ( time() - $t0 ) < 150 ) {

			$r   = $rows[ $pos ];
			$url = ymkrf_bulk_url( $r );
			$no  = isset( $r['process_num'] ) ? trim( $r['process_num'] ) : '';

			if ( $url === '' ) {
				array_unshift( $log, 'NG : URLが作れませんでした（' . $no . '）' );
			} else {
				$res = ymkrf_works_import_one( $url, $no );
				array_unshift( $log, ( $res['ok'] ? 'OK' : 'NG' ) . ' : '
				               . wp_strip_all_tags( $res['msg'] ) );
			}
			$pos++; $n++;
		}

		$log = array_slice( $log, 0, 80 );
		update_option( YMKRF_BULK_POS, $pos, false );
		update_option( YMKRF_BULK_LOG, $log, false );
	}

	/* 投稿者が空の施工事例を、管理者にそろえます。
	   裏で自動で取り込んだぶんは、誰もログインしていないので空になります。
	   （2026/09/07 ユーザー指摘「所有の数字は何？」） */
	if ( isset( $_POST['ymkrf_bulk_author'] ) && check_admin_referer( 'ymkrf_bulk' ) ) {
		global $wpdb;
		$uid = ymkrf_works_import_author();
		$n = $wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->posts} SET post_author = %d
			  WHERE post_type = 'ymkrf_works' AND ( post_author = 0 OR post_author IS NULL )",
			$uid
		) );
		clean_post_cache( 0 );
		wp_cache_flush();
		$fixed = (int) $n;
	}

	/* 自動で取り込む（始める・止める） */
	if ( isset( $_POST['ymkrf_bulk_auto_on'] ) && check_admin_referer( 'ymkrf_bulk' ) ) {
		update_option( YMKRF_BULK_AUTO, '1', false );
		if ( ! wp_next_scheduled( 'ymkrf_bulk_tick' ) ) {
			wp_schedule_single_event( time() + 5, 'ymkrf_bulk_tick' );
		}
	}
	if ( isset( $_POST['ymkrf_bulk_auto_off'] ) && check_admin_referer( 'ymkrf_bulk' ) ) {
		update_option( YMKRF_BULK_AUTO, '', false );
		wp_clear_scheduled_hook( 'ymkrf_bulk_tick' );
	}

	/* 数えなおし（入れた記事は消しません） */
	if ( isset( $_POST['ymkrf_bulk_reset'] ) && check_admin_referer( 'ymkrf_bulk' ) ) {
		update_option( YMKRF_BULK_POS, 0, false );
		update_option( YMKRF_BULK_LOG, array(), false );
		$pos = 0; $log = array();
	}

	/* いま入っている施工事例の数 */
	$have = (int) wp_count_posts( 'ymkrf_works' )->draft
	      + (int) wp_count_posts( 'ymkrf_works' )->publish;
	?>
	<div class="wrap">
	  <h1>施工事例を まとめて取り込む</h1>

	  <?php if ( ! $rows ) : ?>
	    <div class="notice notice-warning"><p>
	      <b>読み取ったデータが見つかりません。</b><br>
	      <code><?php echo esc_html( YMKRF_BULK_FILE ); ?></code> に
	      <code>ymkrf-works.json</code> を置いてください。
	    </p></div>
	  <?php else : ?>

	    <table class="widefat" style="max-width:680px;margin-bottom:16px">
	      <tr><th style="width:14em">読み取ったデータ</th>
	          <td><?php echo count( $rows ); ?> 件</td></tr>
	      <tr><th>取り込みずみ</th><td><?php echo (int) $pos; ?> 件</td></tr>
	      <tr><th>のこり</th>
	          <td><?php echo max( 0, count( $rows ) - $pos ); ?> 件</td></tr>
	      <tr><th>いまの施工事例の数</th><td><?php echo $have; ?> 件</td></tr>
	    </table>

	    <?php if ( $pos < count( $rows ) ) :
	      $next = ymkrf_bulk_rows(); $nr = $next[ $pos ]; ?>
	      <p class="description">つぎに取り込むのは
	        <code><?php echo esc_html( ymkrf_bulk_url( $nr ) ); ?></code>
	        （案件No. <?php echo esc_html( isset( $nr['process_num'] ) ? $nr['process_num'] : '' ); ?>）です。</p>
	    <?php endif; ?>

	    <?php $auto = ( get_option( YMKRF_BULK_AUTO ) === '1' ); ?>
	    <div style="margin:16px 0;padding:14px 18px;border-radius:6px;
	                border:2px solid <?php echo $auto ? '#00782a' : '#dcdcde'; ?>;
	                background:<?php echo $auto ? '#f2fbf5' : '#fff'; ?>">
	      <form method="post" style="margin:0">
	        <?php wp_nonce_field( 'ymkrf_bulk' ); ?>
	        <?php if ( $auto ) : ?>
	          <p style="margin:0 0 8px;font-weight:700;color:#00782a">
	            ● 自動で取り込んでいます（30秒ごとに10件ずつ）</p>
	          <p class="description" style="margin:0 0 10px">
	            ボタンを押しつづけなくても進みます。この画面はひとりでに新しくなります。<br>
	            WordPressのしくみ上、<b>管理画面のどこかを開いているあいだ</b>だけ進みます。
	          </p>
	          <button class="button" name="ymkrf_bulk_auto_off" value="1">自動をやめる</button>
	        <?php else : ?>
	          <p style="margin:0 0 10px;font-weight:700">自動で取り込む</p>
	          <p class="description" style="margin:0 0 10px">
	            押したあとは、30秒ごとに10件ずつひとりでに取り込みます。<br>
	            管理画面を開いているあいだ進みます。いつでも止められます。
	          </p>
	          <button class="button button-primary" name="ymkrf_bulk_auto_on" value="1">自動で取り込みはじめる</button>
	        <?php endif; ?>
	      </form>
	    </div>
	    <?php if ( $auto ) : ?><meta http-equiv="refresh" content="30"><?php endif; ?>

	    <?php
	    /* 投稿者が空のものが残っていたら、そろえるボタンを出します */
	    global $wpdb;
	    $noauthor = (int) $wpdb->get_var(
	      "SELECT COUNT(*) FROM {$wpdb->posts}
	        WHERE post_type = 'ymkrf_works' AND ( post_author = 0 OR post_author IS NULL )" );
	    ?>
	    <?php if ( isset( $fixed ) ) : ?>
	      <div class="notice notice-success"><p>
	        投稿者を <?php echo (int) $fixed; ?> 件そろえました。</p></div>
	    <?php endif; ?>
	    <?php if ( $noauthor ) : ?>
	      <div style="margin:16px 0;padding:14px 18px;border:1px solid #dcdcde;border-radius:6px;background:#fff">
	        <form method="post" style="margin:0">
	          <?php wp_nonce_field( 'ymkrf_bulk' ); ?>
	          <p style="margin:0 0 8px;font-weight:700">投稿者が空のものが <?php echo $noauthor; ?> 件あります</p>
	          <p class="description" style="margin:0 0 10px">
	            裏で自動で取り込んだぶんは、誰もログインしていない状態で入るので、
	            投稿者の欄が空になります。表示や検索には影響しませんが、
	            一覧の「所有」の数がずれて見えます。<br>
	            押すと、管理者にそろえます。
	          </p>
	          <button class="button" name="ymkrf_bulk_author" value="1">投稿者をそろえる</button>
	        </form>
	      </div>
	    <?php endif; ?>

	    <form method="post">
	      <?php wp_nonce_field( 'ymkrf_bulk' ); ?>
	      <p>
	        いちどに
	        <select name="n">
	          <option value="10">10</option>
	          <option value="20" selected>20</option>
	          <option value="30">30</option>
	        </select> 件ずつ
	      </p>
	      <p>
	        <button class="button button-primary button-hero" name="ymkrf_bulk_go" value="1">
	          つづきを取り込む
	        </button>
	        <button class="button" name="ymkrf_bulk_reset" value="1"
	          onclick="return confirm('1件目から数えなおします。取り込んだ記事は消えません。よろしいですか？')">
	          はじめから数えなおす
	        </button>
	      </p>
	      <p class="description">
	        写真をもらってくるので、20件で1〜2分かかります。押したまま、しばらくお待ちください。<br>
	        同じ事例をもう一度取り込んだときは、上書きされます（増えません）。<br>
	        取り込んだ事例は<b>下書き</b>で入ります。
	      </p>
	    </form>
	  <?php endif; ?>

	  <?php if ( $log ) : ?>
	    <h2>記録（新しい順）</h2>
	    <ol style="background:#fff;border:1px solid #ccd0d4;padding:12px 12px 12px 34px;
	               max-height:460px;overflow:auto;line-height:1.9">
	      <?php foreach ( $log as $l ) : ?>
	        <li style="<?php echo strpos( $l, 'NG' ) === 0 ? 'color:#b32d2e' : ''; ?>">
	          <?php echo esc_html( $l ); ?></li>
	      <?php endforeach; ?>
	    </ol>
	  <?php endif; ?>
	</div>
	<?php
}
