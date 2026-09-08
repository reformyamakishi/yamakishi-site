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
 * ※ このファイルは、新しいサイトを公開するまで残します。
 *    制作中のあいだは、いまのサイトのほうにも施工事例が登録されるので、
 *    ふえたぶんを取り込めるようにしておく必要があるためです。
 *    （2026/09/08 ユーザー指示）
 *    公開したら、このファイルと functions.php の読み込み行を消してください。
 *
 * ふえたぶんだけ取り込むには
 *   1. いまのサイトの管理画面をもう一度読み取って、
 *      新しい ymkrf-works.json を wp-content に置く
 *   2.「はじめから数えなおす」を押す
 *   3.「すでに入っているものは飛ばす」にチェックを入れて始める
 *      → 入っているものは読みにいかないので、すぐ終わります
 *
 * @package ymkrf
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'YMKRF_BULK_FILE', WP_CONTENT_DIR . '/ymkrf-works.json' );
define( 'YMKRF_BULK_POS',  'ymkrf_works_bulk_pos' );   // どこまで済んだか
define( 'YMKRF_BULK_LOG',  'ymkrf_works_bulk_log' );   // 記録
define( 'YMKRF_BULK_SKIP', 'ymkrf_works_bulk_skip' );  // 入っているものを飛ばすか


/**
 * このURLの施工事例は、もう入っている？
 *
 * 取り込むと `_ymkrf_src_url` にいまのサイトのURLを控えるので、それで見ます。
 * ゴミ箱のものも「入っている」とみなします（うっかり戻さないため）。
 */
function ymkrf_bulk_have( $url ) {
	global $wpdb;
	if ( $url === '' ) return false;
	return (bool) $wpdb->get_var( $wpdb->prepare(
		"SELECT post_id FROM {$wpdb->postmeta}
		  WHERE meta_key = '_ymkrf_src_url' AND meta_value = %s LIMIT 1", $url ) );
}


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

	$log  = (array) get_option( YMKRF_BULK_LOG, array() );
	$skip = ( get_option( YMKRF_BULK_SKIP ) === '1' );
	$n = 0; $t0 = time();

	while ( $pos < count( $rows ) && $n < 10 && ( time() - $t0 ) < 100 ) {
		$r   = $rows[ $pos ];
		$url = ymkrf_bulk_url( $r );
		$no  = isset( $r['process_num'] ) ? trim( $r['process_num'] ) : '';
		if ( $url === '' ) {
			array_unshift( $log, 'NG : URLが作れませんでした（' . $no . '）' );
		} elseif ( $skip && ymkrf_bulk_have( $url ) ) {
			/* もう入っているので、読みにいきません。件数にも数えません */
			$pos++;
			continue;
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
   担当者のコメントを入れる

   いまのサイトの「point」（工事のポイント／担当者のコメント）は、
   公開ページからは取れなかったため、取り込みで抜けていました。
   読み取ったデータには入っているので、そこから入れます。
   すでに手で書いてあるものは、上書きしません。
   （2026/09/08 ユーザー指摘「旧サイトにあった担当者のコメントが抜けてない？」）
   ============================================================ */

define( 'YMKRF_CMT_POS', 'ymkrf_works_cmt_pos' );

/** 1件ぶん。入れたら true */
function ymkrf_bulk_comment_one( $r ) {

	$point = isset( $r['point'] ) ? trim( (string) $r['point'] ) : '';
	if ( $point === '' ) return false;

	$url = ymkrf_bulk_url( $r );
	if ( $url === '' ) return false;

	global $wpdb;
	$id = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT post_id FROM {$wpdb->postmeta}
		  WHERE meta_key = '_ymkrf_src_url' AND meta_value = %s LIMIT 1", $url ) );
	if ( ! $id ) return false;

	/* 手で書いてあるものは、そのままにします */
	$now = trim( (string) get_post_meta( $id, '_ymkrf_works_comment', true ) );
	if ( $now !== '' ) return false;

	update_post_meta( $id, '_ymkrf_works_comment', sanitize_textarea_field( $point ) );
	return true;
}


/* ============================================================
   リフォーム箇所を付けなおす

   さいしょの取り込みでは、いまのサイトのURL（/works/boiler/ など）を
   そのまま分類にしていました。ところが
   　　exterior（外まわり）・painting（塗装）・whole（まるごと）
   は新しいサイトに同じ名前がなく、分類が付かないままになりました。
   また boiler は、新しいサイトでは
   　　給湯器／エコキュート／オイルタンク／IH
   の4つに分かれるのに、ぜんぶ「給湯器」に入ってしまいました。

   ここでは、写真を取りなおさずに、分類だけを付けなおします。
   （2026/09/08）
   ============================================================ */

define( 'YMKRF_RECAT_MARK', '_ymkrf_recat' );   /* 付けなおしずみの印 */
define( 'YMKRF_RECAT_VER',  '3' );   /* 上げると、もう一度つけなおせます */

/** まだ付けなおしていない施工事例のID（先頭から $limit 件） */
function ymkrf_recat_todo( $limit = 200 ) {
	global $wpdb;
	return $wpdb->get_col( $wpdb->prepare(
		"SELECT p.ID FROM {$wpdb->posts} p
		   JOIN {$wpdb->postmeta} src ON src.post_id = p.ID AND src.meta_key = '_ymkrf_src_url'
		   LEFT JOIN {$wpdb->postmeta} mk
		          ON mk.post_id = p.ID AND mk.meta_key = %s AND mk.meta_value = %s
		  WHERE p.post_type = 'ymkrf_works'
		    AND p.post_status IN ('publish','draft','pending','future','private')
		    AND mk.post_id IS NULL
		  ORDER BY p.ID ASC LIMIT %d",
		YMKRF_RECAT_MARK, YMKRF_RECAT_VER, (int) $limit
	) );
}

/** まだ付けなおしていない件数 */
function ymkrf_recat_left() {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->posts} p
		   JOIN {$wpdb->postmeta} src ON src.post_id = p.ID AND src.meta_key = '_ymkrf_src_url'
		   LEFT JOIN {$wpdb->postmeta} mk
		          ON mk.post_id = p.ID AND mk.meta_key = %s AND mk.meta_value = %s
		  WHERE p.post_type = 'ymkrf_works'
		    AND p.post_status IN ('publish','draft','pending','future','private')
		    AND mk.post_id IS NULL",
		YMKRF_RECAT_MARK, YMKRF_RECAT_VER
	) );
}

/** 1件ぶん、分類と題名を付けなおします */
function ymkrf_recat_one( $id ) {

	$id  = (int) $id;
	$url = (string) get_post_meta( $id, '_ymkrf_src_url', true );

	/* 判定に使う文字は、取り込みのときと同じものを、保存ずみの欄から組み立てます */
	$text = get_post_field( 'post_title', $id ) . ' '
	      . (string) get_post_meta( $id, '_ymkrf_work_items', true ) . ' '
	      . (string) get_post_meta( $id, '_ymkrf_product_text', true ) . ' '
	      . (string) get_post_field( 'post_content', $id );

	$cat = ymkrf_works_import_cat( $url, $text );

	if ( $cat !== '' ) {
		wp_set_object_terms( $id, $cat, 'ymkrf_works_cat' );

		/* 題名は分類で決まるので、付けなおします
		   （分類がないと「リフォーム事例｜…」になってしまいます） */
		$title = ymkrf_works_auto_title( $id );
		if ( $title !== '' && $title !== get_post_field( 'post_title', $id ) ) {
			update_post_meta( $id, '_ymkrf_auto_title', $title );
			wp_update_post( array( 'ID' => $id, 'post_title' => $title ) );
		}
	}

	/* 抜粋（＝検索結果に出る説明文）が空なら、ここで入れておきます。
	   取り込んだぶんは本文が空なので、おこなった工事から作ります。（2026/09/08） */
	$p = get_post( $id );
	if ( $p && trim( (string) $p->post_excerpt ) === '' ) {
		$x = trim( ymkrf_works_excerpt( $id, 90 ) );
		if ( $x !== '' && $x !== '…' ) {
			wp_update_post( array( 'ID' => $id, 'post_excerpt' => $x ) );
		}
	}

	update_post_meta( $id, YMKRF_RECAT_MARK, YMKRF_RECAT_VER );
	return $cat;
}


/* 付けなおしも、裏で少しずつ進められるようにします */
define( 'YMKRF_RECAT_AUTO', 'ymkrf_works_recat_auto' );

add_action( 'ymkrf_recat_tick', 'ymkrf_recat_tick' );

function ymkrf_recat_tick() {
	if ( get_option( YMKRF_RECAT_AUTO ) !== '1' ) return;

	$ids = ymkrf_recat_todo( 200 );
	if ( ! $ids ) { update_option( YMKRF_RECAT_AUTO, '', false ); return; }

	$t0 = time();
	foreach ( $ids as $rid ) {
		ymkrf_recat_one( $rid );
		if ( ( time() - $t0 ) > 100 ) break;
	}
	delete_transient( 'ymkrf_works_cat_counts' );

	if ( ymkrf_recat_left() > 0 ) wp_schedule_single_event( time() + 20, 'ymkrf_recat_tick' );
	else                          update_option( YMKRF_RECAT_AUTO, '', false );
}

add_action( 'admin_init', function () {
	if ( get_option( YMKRF_RECAT_AUTO ) !== '1' ) return;
	if ( ! wp_next_scheduled( 'ymkrf_recat_tick' ) ) {
		wp_schedule_single_event( time() + 10, 'ymkrf_recat_tick' );
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

	/* 「すでに入っているものは飛ばす」の入り切りを、先に控えます */
	if ( ( isset( $_POST['ymkrf_bulk_go'] ) || isset( $_POST['ymkrf_bulk_auto_on'] ) )
	     && check_admin_referer( 'ymkrf_bulk' ) ) {
		update_option( YMKRF_BULK_SKIP,
			isset( $_POST['ymkrf_bulk_skip'] ) ? '1' : '', false );
	}

	/* 20件ぶん進めます */
	if ( isset( $_POST['ymkrf_bulk_go'] ) && check_admin_referer( 'ymkrf_bulk' ) ) {

		$want = isset( $_POST['n'] ) ? max( 1, min( 50, (int) $_POST['n'] ) ) : 20;
		$n = 0;
		$t0 = time();
		$skip = ( get_option( YMKRF_BULK_SKIP ) === '1' );

		while ( $pos < count( $rows ) && $n < $want && ( time() - $t0 ) < 150 ) {

			$r   = $rows[ $pos ];
			$url = ymkrf_bulk_url( $r );
			$no  = isset( $r['process_num'] ) ? trim( $r['process_num'] ) : '';

			if ( $url === '' ) {
				array_unshift( $log, 'NG : URLが作れませんでした（' . $no . '）' );
			} elseif ( $skip && ymkrf_bulk_have( $url ) ) {
				$pos++;
				continue;
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

	/* 担当者のコメントを入れる */
	if ( isset( $_POST['ymkrf_cmt_go'] ) && check_admin_referer( 'ymkrf_bulk' ) ) {
		$cpos = (int) get_option( YMKRF_CMT_POS, 0 );
		$t0 = time(); $cn = 0;
		while ( $cpos < count( $rows ) && ( time() - $t0 ) < 100 ) {
			if ( ymkrf_bulk_comment_one( $rows[ $cpos ] ) ) $cn++;
			$cpos++;
		}
		update_option( YMKRF_CMT_POS, $cpos, false );
		$cmt_n = $cn;
	}
	if ( isset( $_POST['ymkrf_cmt_reset'] ) && check_admin_referer( 'ymkrf_bulk' ) ) {
		update_option( YMKRF_CMT_POS, 0, false );
	}

	/* リフォーム箇所を付けなおす */
	if ( isset( $_POST['ymkrf_recat_go'] ) && check_admin_referer( 'ymkrf_bulk' ) ) {
		$t0 = time(); $recat = array(); $n = 0;
		foreach ( ymkrf_recat_todo( 400 ) as $rid ) {
			$c = ymkrf_recat_one( $rid );
			$recat[ $c === '' ? '（付きませんでした）' : $c ] =
				( isset( $recat[ $c === '' ? '（付きませんでした）' : $c ] )
				  ? $recat[ $c === '' ? '（付きませんでした）' : $c ] : 0 ) + 1;
			$n++;
			if ( ( time() - $t0 ) > 100 ) break;
		}
		$recat_n = $n;
		delete_transient( 'ymkrf_works_cat_counts' );
	}
	if ( isset( $_POST['ymkrf_recat_auto_on'] ) && check_admin_referer( 'ymkrf_bulk' ) ) {
		update_option( YMKRF_RECAT_AUTO, '1', false );
		if ( ! wp_next_scheduled( 'ymkrf_recat_tick' ) ) {
			wp_schedule_single_event( time() + 5, 'ymkrf_recat_tick' );
		}
	}
	if ( isset( $_POST['ymkrf_recat_auto_off'] ) && check_admin_referer( 'ymkrf_bulk' ) ) {
		update_option( YMKRF_RECAT_AUTO, '', false );
		wp_clear_scheduled_hook( 'ymkrf_recat_tick' );
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

	  <?php $cpos = (int) get_option( YMKRF_CMT_POS, 0 ); ?>
	  <?php if ( isset( $cmt_n ) ) : ?>
	    <div class="notice notice-success"><p>
	      担当者のコメントを <?php echo (int) $cmt_n; ?> 件入れました
	      （<?php echo (int) $cpos; ?> / <?php echo count( $rows ); ?> 件まで確認）。</p></div>
	  <?php endif; ?>
	  <?php if ( $rows && $cpos < count( $rows ) ) : ?>
	    <div style="margin:16px 0;padding:14px 18px;border-radius:6px;
	                border:2px solid #fe3301;background:#fff7f4">
	      <p style="margin:0 0 8px;font-weight:700">
	        担当者のコメントを入れる（のこり <?php echo count( $rows ) - $cpos; ?> 件）</p>
	      <p class="description" style="margin:0 0 10px">
	        いまのサイトの「工事のポイント（担当者のコメント）」は、公開ページから
	        取れなかったため、取り込みで抜けていました。読み取ったデータには
	        入っているので、そこから入れます。<br>
	        <b>すでに手で書いてあるものは、上書きしません。</b>
	      </p>
	      <form method="post" style="margin:0">
	        <?php wp_nonce_field( 'ymkrf_bulk' ); ?>
	        <button class="button button-primary" name="ymkrf_cmt_go" value="1">つづきを入れる</button>
	        <button class="button" name="ymkrf_cmt_reset" value="1">はじめから数えなおす</button>
	      </form>
	    </div>
	  <?php endif; ?>

	  <?php
	  /* ---- リフォーム箇所の付けなおし ---- */
	  $rleft = ymkrf_recat_left();
	  $rauto = ( get_option( YMKRF_RECAT_AUTO ) === '1' );
	  ?>
	  <?php if ( isset( $recat_n ) ) : ?>
	    <div class="notice notice-success"><p>
	      リフォーム箇所を <?php echo (int) $recat_n; ?> 件つけなおしました。<br>
	      <?php foreach ( (array) $recat as $k => $v ) :
	        $names = ymkrf_works_parts_names(); ?>
	        <?php echo esc_html( isset( $names[ $k ] ) ? $names[ $k ] : $k ); ?>
	        <?php echo (int) $v; ?>件
	      <?php endforeach; ?>
	    </p></div>
	  <?php endif; ?>

	  <?php if ( $rleft || $rauto ) : ?>
	    <div style="margin:16px 0;padding:14px 18px;border-radius:6px;
	                border:2px solid <?php echo $rauto ? '#00782a' : '#fe3301'; ?>;
	                background:<?php echo $rauto ? '#f2fbf5' : '#fff7f4'; ?>">
	      <p style="margin:0 0 8px;font-weight:700">
	        リフォーム箇所を つけなおす（のこり <?php echo (int) $rleft; ?> 件）</p>
	      <p class="description" style="margin:0 0 10px">
	        さいしょの取り込みでは、いまのサイトのURLをそのまま分類にしていました。
	        そのため<b>外まわり・塗装・まるごとリフォーム</b>には分類が付かず、
	        一覧の「リフォーム箇所」が「—」になっています。
	        また<b>給湯器</b>は、新しいサイトの
	        給湯器／エコキュート／オイルタンク／IH に分かれていません。<br>
	        押すと、<b>写真は取りなおさずに</b>、分類と題名だけを付けなおします。
	      </p>
	      <form method="post" style="margin:0">
	        <?php wp_nonce_field( 'ymkrf_bulk' ); ?>
	        <?php if ( $rauto ) : ?>
	          <p style="margin:0 0 8px;font-weight:700;color:#00782a">
	            ● 自動でつけなおしています（20秒ごとに200件ずつ）</p>
	          <button class="button" name="ymkrf_recat_auto_off" value="1">自動をやめる</button>
	        <?php else : ?>
	          <button class="button button-primary" name="ymkrf_recat_go" value="1">
	            400件つけなおす</button>
	          <button class="button" name="ymkrf_recat_auto_on" value="1">
	            自動でぜんぶつけなおす</button>
	        <?php endif; ?>
	      </form>
	    </div>
	    <?php if ( $rauto ) : ?><meta http-equiv="refresh" content="25"><?php endif; ?>
	  <?php endif; ?>

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
	          <p style="margin:0 0 10px">
	            <label><input type="checkbox" name="ymkrf_bulk_skip" value="1"
	              <?php checked( get_option( YMKRF_BULK_SKIP ), '1' ); ?>>
	              <b>すでに入っているものは飛ばす</b>（新しくふえたぶんだけ取り込む）</label>
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
	        <label><input type="checkbox" name="ymkrf_bulk_skip" value="1"
	          <?php checked( get_option( YMKRF_BULK_SKIP ), '1' ); ?>>
	          <b>すでに入っているものは飛ばす</b>（新しくふえたぶんだけ取り込む）</label>
	      </p>
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

	      <div style="margin-top:18px;padding:12px 16px;border-left:4px solid #fe3301;background:#fff7f4">
	        <p style="margin:0 0 6px;font-weight:700">あとからふえたぶんを取り込むには</p>
	        <ol style="margin:0 0 0 18px;line-height:1.9">
	          <li>いまのサイトの管理画面をもう一度読み取って、新しい
	              <code>ymkrf-works.json</code> を <code>wp-content</code> に置く</li>
	          <li><b>はじめから数えなおす</b>を押す</li>
	          <li><b>すでに入っているものは飛ばす</b>にチェックを入れて、始める</li>
	        </ol>
	        <p class="description" style="margin:8px 0 0">
	          入っているものは読みにいかないので、すぐ終わります。
	          新しいサイトを公開するまで、この画面は残しておきます。
	        </p>
	      </div>
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
