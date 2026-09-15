<?php
/**
 * functions-column-import.php ─ 旧ブログ（コラム）の取り込み
 * 置き場所： wp-content/themes/ymkrf/inc/functions-column-import.php
 *
 * いまのサイトの「ヤマキシリフォームブログ」127本を、
 * 新しいサイトの「コラム」に移します。
 *
 *   ・題名／本文／写真／掲載日／公開・下書き／カテゴリ
 *   ・本文の中の写真も、ぜんぶメディアに取り込みます
 *
 * ★ 中身は変えません。見た目だけ、新しいサイトの作法にそろえます。
 *    （古い <font> や色・大きさの指定を外して、テーマの見出し・余白に任せます）
 *    （2026/09/09 ユーザー指示「内容を変えずに見た目を」「今どきっぽく」）
 *
 * 使いかた
 *   1. wp-content/ymkrf-columns.json を置く
 *   2. 管理画面「コラム ＞ ブログの取り込み」をひらく
 *   3.「自動で取り込みはじめる」を押す
 *
 * ※ 取り込みが終わったら、このファイルと functions.php の読み込み行を
 *    消してください。
 *
 * @package ymkrf
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'YMKRF_CIMP_FILE', WP_CONTENT_DIR . '/ymkrf-columns.json' );
define( 'YMKRF_CIMP_POS',  'ymkrf_column_imp_pos' );
define( 'YMKRF_CIMP_LOG',  'ymkrf_column_imp_log' );
define( 'YMKRF_CIMP_AUTO', 'ymkrf_column_imp_auto' );
define( 'YMKRF_CIMP_HOST', 'yamakishi-reform.jp' );

/* 取り込んだ印（旧ブログの記事番号） */
define( 'YMKRF_CIMP_KEY', '_ymkrf_old_blog_id' );


/* ============================================================
   1. 読み取ったデータ
   ============================================================ */

function ymkrf_cimp_rows() {
	static $rows = null;
	if ( $rows !== null ) return $rows;

	$rows = array();
	if ( file_exists( YMKRF_CIMP_FILE ) ) {
		$j = json_decode( (string) file_get_contents( YMKRF_CIMP_FILE ), true );
		if ( isset( $j['rows'] ) ) $j = $j['rows'];
		if ( is_array( $j ) ) $rows = $j;
	}
	return $rows;
}


/* ============================================================
   2. 入れかたの決めごと
   ============================================================ */

/**
 * 旧ブログのカテゴリ → 新サイトの商品カテゴリ。
 *
 * 新サイトのコラムは、商品と同じ分類（キッチン・お風呂…）を使います。
 * あてはまるものが無いカテゴリ（その他・水回り・補助金・イベントなど）は、
 * 分類を付けません。コラム一覧（/column/）には出ます。
 */
function ymkrf_cimp_cat_map() {
	return array(
		'キッチン'           => 'kitchen',
		'お風呂'             => 'bathroom',
		'トイレ'             => 'toilet',
		'洗面化粧台'         => 'lavatory',
		'エコキュート'       => 'ecocute',
		'オール電化・給湯器' => 'boiler',
		'内装・増改築'       => 'interior',
		'外壁・外装'         => 'outer-wall',
	);
}

/** 記事に付ける分類（英字の配列） */
function ymkrf_cimp_cats( $names ) {
	$map = ymkrf_cimp_cat_map();
	$out = array();
	foreach ( (array) $names as $n ) {
		$n = trim( (string) $n );
		if ( isset( $map[ $n ] ) && ! in_array( $map[ $n ], $out, true ) ) $out[] = $map[ $n ];
	}
	return $out;
}


/**
 * 本文の見た目を、新しいサイトの作法にそろえます。
 *
 * 消すもの … <font>、style=、class=、幅や高さの決め打ち、空の段落
 * 直すもの … <h1> は <h2> に（ページの大見出しと二重になるため）
 *             <h5>/<h6> は <h4> に
 *             <b> は <strong>、<i> は <em>
 * 文章と写真そのものは、いっさい変えません。
 */
function ymkrf_cimp_clean_html( $html ) {

	$h = (string) $html;

	/* 古い見た目の指定を外します */
	$h = preg_replace( '#</?font[^>]*>#i', '', $h );
	$h = preg_replace( '#\s(style|class|color|face|size|align|bgcolor|border|cellpadding|cellspacing)\s*=\s*"[^"]*"#i', '', $h );
	$h = preg_replace( "#\s(style|class|color|face|size|align|bgcolor|border|cellpadding|cellspacing)\s*=\s*'[^']*'#i", '', $h );

	/* 見出しの深さをそろえます */
	$h = preg_replace( '#<h1([^>]*)>#i', '<h2$1>', $h );
	$h = preg_replace( '#</h1>#i', '</h2>', $h );
	$h = preg_replace( '#<h([56])([^>]*)>#i', '<h4$2>', $h );
	$h = preg_replace( '#</h[56]>#i', '</h4>', $h );

	/* 太字・斜体を、いまの書きかたに */
	$h = preg_replace( '#<b(\s[^>]*)?>#i', '<strong>', $h );
	$h = str_ireplace( '</b>', '</strong>', $h );
	$h = preg_replace( '#<i(\s[^>]*)?>#i', '<em>', $h );
	$h = str_ireplace( '</i>', '</em>', $h );

	/* 中身のない飾りを片づけます */
	$h = preg_replace( '#</?span[^>]*>#i', '', $h );
	$h = preg_replace( '#<p>(\s|&nbsp;|<br\s*/?>)*</p>#i', '', $h );
	$h = preg_replace( '#(<br\s*/?>\s*){3,}#i', '<br><br>', $h );
	$h = preg_replace( '#\s{2,}#u', ' ', $h );

	return trim( $h );
}


/**
 * 本文の中のリンクを、新しいサイトのものに直します。
 *
 * ・いまのサイトのURL（https://yamakishi-reform.jp/…）は、
 *   ドメインを外して「/…」の形にします。新しいサイトも同じドメインになるためです。
 * ・旧ブログの記事（/blog/entry/123）は、取り込んだコラムへつなぎます。
 *   まだ取り込んでいないものは、コラム一覧（/column/）へ。
 * （2026/09/09。「もう古いホームページは使用しませんのでリンクしないで」より）
 */
function ymkrf_cimp_fix_links( $html ) {

	$h = (string) $html;

	/* いまのサイトのドメインを外します（写真のURLは、先に差しかえずみです） */
	$h = preg_replace( '#https?://(www\.)?' . preg_quote( YMKRF_CIMP_HOST, '#' ) . '#i', '', $h );

	/* 旧ブログの記事へのリンク */
	$h = preg_replace_callback( '#(href=")/blog/entry/(\d+)/?(")#i', function ( $m ) {
		$p = get_posts( array(
			'post_type' => 'ymkrf_column', 'posts_per_page' => 1, 'fields' => 'ids',
			'post_status' => 'any',
			'meta_query' => array( array( 'key' => YMKRF_CIMP_KEY, 'value' => $m[2] ) ),
		) );
		$u = $p ? get_permalink( (int) $p[0] ) : get_post_type_archive_link( 'ymkrf_column' );
		return $m[1] . esc_url( $u ? $u : home_url( '/column/' ) ) . $m[3];
	}, $h );

	/* 旧ブログの一覧・カテゴリは、コラム一覧へ */
	$col = get_post_type_archive_link( 'ymkrf_column' );
	if ( ! $col ) $col = home_url( '/column/' );
	$h = preg_replace( '#(href=")/blog/[^"]*(")#i', '$1' . esc_url( $col ) . '$2', $h );

	/* 残った「/」だけのリンクは、トップへ */
	$h = str_replace( 'href="/"', 'href="' . esc_url( home_url( '/' ) ) . '"', $h );

	return $h;
}


/** 写真をもらってきて、メディアに入れます（同じ写真は1回だけ） */
function ymkrf_cimp_photo( $uid, $post_id ) {

	$uid = trim( (string) $uid );
	if ( $uid === '' ) return 0;

	$found = get_posts( array(
		'post_type' => 'attachment', 'posts_per_page' => 1, 'fields' => 'ids',
		'post_status' => 'any',
		'meta_query' => array( array( 'key' => '_ymkrf_imp_key', 'value' => $uid ) ),
	) );
	if ( $found ) return (int) $found[0];

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$url = 'https://' . YMKRF_CIMP_HOST . '/uploads/raw/' . rawurlencode( $uid );
	$tmp = download_url( $url, 60 );
	if ( is_wp_error( $tmp ) ) return 0;

	$file = array(
		'name'     => 'ymkc-' . strtolower( preg_replace( '/[^0-9A-Za-z]/', '', $uid ) ) . '.jpg',
		'tmp_name' => $tmp,
	);
	$id = media_handle_sideload( $file, $post_id, '' );
	if ( is_wp_error( $id ) ) {
		if ( file_exists( $tmp ) ) @unlink( $tmp );
		return 0;
	}
	update_post_meta( $id, '_ymkrf_imp_key', $uid );
	return (int) $id;
}


/**
 * 本文の中の写真を、新しいサイトのメディアに移します。
 * もらってこられなかったものは、旧サイトのURLのままにしておきます
 * （画像が消えるより、そのほうが安全です）。
 */
function ymkrf_cimp_move_images( $html, $post_id, &$n = 0 ) {

	return preg_replace_callback(
		'#https://' . preg_quote( YMKRF_CIMP_HOST, '#' ) . '/uploads/raw/([A-Za-z0-9_-]+)#',
		function ( $m ) use ( $post_id, &$n ) {
			$mid = ymkrf_cimp_photo( $m[1], $post_id );
			if ( ! $mid ) return $m[0];
			$src = wp_get_attachment_image_url( $mid, 'large' );
			if ( ! $src ) $src = wp_get_attachment_url( $mid );
			$n++;
			return $src ? $src : $m[0];
		},
		(string) $html
	);
}


/* ============================================================
   3. 1件を入れます
   ============================================================ */

function ymkrf_cimp_one( $r ) {

	$oid = isset( $r['id'] ) ? trim( (string) $r['id'] ) : '';
	if ( $oid === '' ) return array( false, '記事番号がありません' );

	$title = trim( (string) ( isset( $r['title'] ) ? $r['title'] : '' ) );
	if ( $title === '' ) $title = '（題名なし）';

	/* すでに取り込んでいたら、それを更新します（増えません） */
	$exist = get_posts( array(
		'post_type' => 'ymkrf_column', 'posts_per_page' => 1, 'fields' => 'ids',
		'post_status' => 'any',
		'meta_query' => array( array( 'key' => YMKRF_CIMP_KEY, 'value' => $oid ) ),
	) );

	$status = ( isset( $r['status'] ) && $r['status'] === 'publish' ) ? 'publish' : 'draft';
	$date   = trim( (string) ( isset( $r['date'] ) ? $r['date'] : '' ) );

	$post = array(
		'post_type'    => 'ymkrf_column',
		'post_title'   => $title,
		'post_status'  => $status,
		'post_content' => '',   /* 写真を移してから入れます */
		'post_author'  => function_exists( 'ymkrf_works_import_author' ) ? ymkrf_works_import_author() : 1,
	);
	if ( $date !== '' && strtotime( $date ) ) {
		$post['post_date']     = date( 'Y-m-d H:i:s', strtotime( $date ) );
		$post['post_date_gmt'] = get_gmt_from_date( $post['post_date'] );
	}

	if ( $exist ) {
		$post['ID'] = (int) $exist[0];
		$id = wp_update_post( $post, true );
	} else {
		$id = wp_insert_post( $post, true );
	}
	if ( is_wp_error( $id ) ) return array( false, $title . ' … 保存できませんでした' );

	update_post_meta( $id, YMKRF_CIMP_KEY, $oid );
	if ( ! empty( $r['by'] ) ) update_post_meta( $id, '_ymkrf_old_writer', sanitize_text_field( $r['by'] ) );

	/* 本文。見た目をそろえてから、写真を新しいサイトへ移します */
	$n_img = 0;
	$html  = ymkrf_cimp_clean_html( isset( $r['html'] ) ? $r['html'] : '' );
	$html  = ymkrf_cimp_move_images( $html, $id, $n_img );
	$html  = ymkrf_cimp_fix_links( $html );

	/* 抜粋（＝検索結果の説明文）。無ければ本文の頭から */
	$ex = trim( (string) ( isset( $r['desc'] ) ? $r['desc'] : '' ) );
	$ex = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $ex ) ) );
	if ( $ex === '' ) $ex = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $html ) ) );
	$ex = mb_strimwidth( $ex, 0, 200, '…', 'UTF-8' );

	wp_update_post( array( 'ID' => $id, 'post_content' => $html, 'post_excerpt' => $ex ) );

	/* アイキャッチ画像 */
	if ( ! empty( $r['image'] ) && ! has_post_thumbnail( $id ) ) {
		$mid = ymkrf_cimp_photo( $r['image'], $id );
		if ( $mid ) { set_post_thumbnail( $id, $mid ); $n_img++; }
	}

	/* 分類 */
	$cats = ymkrf_cimp_cats( isset( $r['cats'] ) ? $r['cats'] : array() );
	if ( $cats ) wp_set_object_terms( $id, $cats, 'ymkrf_product_cat' );

	$link = '<a href="' . esc_url( get_edit_post_link( $id ) ) . '">' . esc_html( $title ) . '</a>';
	return array( true, wp_strip_all_tags( $link ) . '（写真' . $n_img . '枚'
		. ( $status === 'draft' ? '／下書き' : '' )
		. ( $exist ? '／上書き' : '' ) . '）' );
}


/* ============================================================
   4. 裏で少しずつ取り込みつづけるしくみ
   ============================================================ */

add_action( 'ymkrf_cimp_tick', 'ymkrf_cimp_tick' );

function ymkrf_cimp_tick() {

	if ( get_option( YMKRF_CIMP_AUTO ) !== '1' ) return;

	$rows = ymkrf_cimp_rows();
	$pos  = (int) get_option( YMKRF_CIMP_POS, 0 );
	if ( ! $rows || $pos >= count( $rows ) ) {
		update_option( YMKRF_CIMP_AUTO, '', false );
		return;
	}

	/* 二重に走らないようにします。
	   （同じ記事を2つのしくみが同時に取り込むと、コラムが2本できてしまいます） */
	if ( get_transient( 'ymkrf_cimp_lock' ) ) return;
	set_transient( 'ymkrf_cimp_lock', 1, 300 );

	$log = (array) get_option( YMKRF_CIMP_LOG, array() );
	$n = 0; $t0 = time();

	/* 1回に10本ずつ取り込みます。
	   1本おわるたびに「どこまで進んだか」を保存するので、
	   途中で止まっても、同じものを二重に取り込むことはありません。 */
	@set_time_limit( 300 );

	while ( $pos < count( $rows ) && $n < 10 && ( time() - $t0 ) < 120 ) {
		list( $ok, $msg ) = ymkrf_cimp_one( $rows[ $pos ] );
		array_unshift( $log, ( $ok ? 'OK' : 'NG' ) . ' : ' . $msg );
		$pos++; $n++;
		update_option( YMKRF_CIMP_POS, $pos, false );
	}

	update_option( YMKRF_CIMP_POS, $pos, false );
	update_option( YMKRF_CIMP_LOG, array_slice( $log, 0, 60 ), false );
	delete_transient( 'ymkrf_cimp_lock' );

	if ( $pos < count( $rows ) ) wp_schedule_single_event( time() + 5, 'ymkrf_cimp_tick' );
	else                         update_option( YMKRF_CIMP_AUTO, '', false );
}

add_action( 'admin_init', function () {
	if ( get_option( YMKRF_CIMP_AUTO ) !== '1' ) return;
	if ( ! wp_next_scheduled( 'ymkrf_cimp_tick' ) ) {
		wp_schedule_single_event( time() + 5, 'ymkrf_cimp_tick' );
	}
} );


/* ============================================================
   4-2. 重なって入ったコラムを見つける
        （同じ旧ブログ記事から、コラムが2本できてしまったもの）
   ============================================================ */

/**
 * 同じ旧ブログ記事番号のコラムが2本以上あるものを返します。
 * 返すのは「あとからできた方」＝ 片づける候補です。
 * いちばん古い1本（番号の小さい方）は、かならず残します。
 *
 * @return array 旧記事番号 => array( 'keep' => 投稿ID, 'extra' => array(投稿ID), 'title' => 題名 )
 */
function ymkrf_cimp_dups() {

	$ids = get_posts( array(
		'post_type'      => 'ymkrf_column',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'orderby'        => 'ID',
		'order'          => 'ASC',
		'meta_query'     => array( array( 'key' => YMKRF_CIMP_KEY, 'compare' => 'EXISTS' ) ),
	) );

	$by = array();
	foreach ( $ids as $id ) {
		$oid = (string) get_post_meta( $id, YMKRF_CIMP_KEY, true );
		if ( $oid === '' ) continue;
		if ( ! isset( $by[ $oid ] ) ) $by[ $oid ] = array();
		$by[ $oid ][] = (int) $id;
	}

	$out = array();
	foreach ( $by as $oid => $list ) {
		if ( count( $list ) < 2 ) continue;
		sort( $list );

		/* 中身がいちばん多い1本を残します。
		   （同じなら、先にできた方＝番号の小さい方を残します） */
		$len  = array();
		foreach ( $list as $id ) {
			$p = get_post( $id );
			$len[ $id ] = $p ? mb_strlen( (string) $p->post_content ) : 0;
		}
		$keep = $list[0];
		foreach ( $list as $id ) {
			if ( $len[ $id ] > $len[ $keep ] ) $keep = $id;
		}

		$extra = array();
		foreach ( $list as $id ) if ( $id !== $keep ) $extra[] = $id;

		$out[ $oid ] = array(
			'keep'  => $keep,
			'extra' => $extra,
			'len'   => $len,
			'title' => get_the_title( $keep ),
		);
	}
	return $out;
}

/**
 * 重なって入った方をゴミ箱へ入れます（消しません）。
 * ゴミ箱なので、まちがっていても元にもどせます。
 */
function ymkrf_cimp_dups_trash() {
	$n = 0;
	foreach ( ymkrf_cimp_dups() as $d ) {
		foreach ( $d['extra'] as $id ) {
			if ( wp_trash_post( $id ) ) $n++;
		}
	}
	return $n;
}


/* ============================================================
   5. 管理画面
   ============================================================ */

add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=ymkrf_column',
		'ブログの取り込み', 'ブログの取り込み',
		'manage_options', 'ymkrf-column-bulk', 'ymkrf_cimp_page'
	);
}, 30 );

function ymkrf_cimp_page() {

	if ( ! current_user_can( 'manage_options' ) ) return;

	$rows = ymkrf_cimp_rows();
	$pos  = (int) get_option( YMKRF_CIMP_POS, 0 );
	$log  = (array) get_option( YMKRF_CIMP_LOG, array() );

	if ( isset( $_POST['ymkrf_cimp_go'] ) && check_admin_referer( 'ymkrf_cimp' ) ) {
		$want = isset( $_POST['n'] ) ? max( 1, min( 20, (int) $_POST['n'] ) ) : 5;
		$n = 0; $t0 = time();
		while ( $pos < count( $rows ) && $n < $want && ( time() - $t0 ) < 150 ) {
			list( $ok, $msg ) = ymkrf_cimp_one( $rows[ $pos ] );
			array_unshift( $log, ( $ok ? 'OK' : 'NG' ) . ' : ' . $msg );
			$pos++; $n++;
		}
		$log = array_slice( $log, 0, 60 );
		update_option( YMKRF_CIMP_POS, $pos, false );
		update_option( YMKRF_CIMP_LOG, $log, false );
	}

	if ( isset( $_POST['ymkrf_cimp_auto_on'] ) && check_admin_referer( 'ymkrf_cimp' ) ) {
		update_option( YMKRF_CIMP_AUTO, '1', false );
		if ( ! wp_next_scheduled( 'ymkrf_cimp_tick' ) ) {
			wp_schedule_single_event( time() + 5, 'ymkrf_cimp_tick' );
		}
	}
	if ( isset( $_POST['ymkrf_cimp_auto_off'] ) && check_admin_referer( 'ymkrf_cimp' ) ) {
		update_option( YMKRF_CIMP_AUTO, '', false );
		wp_clear_scheduled_hook( 'ymkrf_cimp_tick' );
	}
	$dup_done = -1;
	if ( isset( $_POST['ymkrf_cimp_dedupe'] ) && check_admin_referer( 'ymkrf_cimp' ) ) {
		$dup_done = ymkrf_cimp_dups_trash();
	}

	$wl_done = null;
	if ( isset( $_POST['ymkrf_cimp_wlink'] ) && check_admin_referer( 'ymkrf_cimp' ) ) {
		$wl_done = 0;
		$pairs = isset( $_POST['ymkrf_wmap'] ) ? (array) $_POST['ymkrf_wmap'] : array();
		foreach ( $pairs as $old_name => $sid ) {
			$wl_done += ymkrf_cimp_writer_link( wp_unslash( $old_name ), (int) $sid );
		}
	}

	$cta_done = null;
	if ( isset( $_POST['ymkrf_cimp_cta'] ) && check_admin_referer( 'ymkrf_cimp' ) ) {
		$cta_done = ymkrf_cimp_cta_clean_all( false );
	}
	if ( isset( $_POST['ymkrf_cimp_cta_undo'] ) && check_admin_referer( 'ymkrf_cimp' ) ) {
		$cta_done = array( ymkrf_cimp_cta_restore_all(), -1 );
	}
	if ( isset( $_POST['ymkrf_cimp_reset'] ) && check_admin_referer( 'ymkrf_cimp' ) ) {
		update_option( YMKRF_CIMP_POS, 0, false );
		update_option( YMKRF_CIMP_LOG, array(), false );
		$pos = 0; $log = array();
	}

	$have = (int) wp_count_posts( 'ymkrf_column' )->publish
	      + (int) wp_count_posts( 'ymkrf_column' )->draft;
	$auto = ( get_option( YMKRF_CIMP_AUTO ) === '1' );
	?>
	<div class="wrap">
	  <h1>いまのブログを コラムに取り込む</h1>

	  <?php if ( ! $rows ) : ?>
	    <div class="notice notice-warning"><p>
	      <b>読み取ったデータが見つかりません。</b><br>
	      <code><?php echo esc_html( YMKRF_CIMP_FILE ); ?></code> に
	      <code>ymkrf-columns.json</code> を置いてください。
	    </p></div>
	  <?php else : ?>

	    <div class="notice notice-info"><p>
	      <b>中身は変えません。</b>文章と写真はそのまま持ってきて、
	      見た目だけ新しいサイトの作法にそろえます
	      （古い文字色・文字サイズの指定を外し、見出しの深さをそろえます）。
	    </p></div>

	    <table class="widefat" style="max-width:680px;margin-bottom:16px">
	      <tr><th style="width:14em">読み取ったデータ</th><td><?php echo count( $rows ); ?> 本</td></tr>
	      <tr><th>取り込みずみ</th><td><?php echo (int) $pos; ?> 本</td></tr>
	      <tr><th>のこり</th><td><?php echo max( 0, count( $rows ) - $pos ); ?> 本</td></tr>
	      <tr><th>いまのコラムの数</th><td><?php echo $have; ?> 本</td></tr>
	    </table>

	    <div style="margin:16px 0;padding:14px 18px;border-radius:6px;
	                border:2px solid <?php echo $auto ? '#00782a' : '#fe3301'; ?>;
	                background:<?php echo $auto ? '#f2fbf5' : '#fff7f4'; ?>">
	      <form method="post" style="margin:0">
	        <?php wp_nonce_field( 'ymkrf_cimp' ); ?>
	        <?php if ( $auto ) : ?>
	          <p style="margin:0 0 8px;font-weight:700;color:#00782a">
	            ● 自動で取り込んでいます（5秒ごとに10本ずつ）</p>
	          <p class="description" style="margin:0 0 10px">
	            本文の写真もぜんぶ取り込むので、1本あたり少し時間がかかります。<br>
	            <b>管理画面のどこかを開いているあいだ</b>だけ進みます。
	          </p>
	          <button class="button" name="ymkrf_cimp_auto_off" value="1">自動をやめる</button>
	        <?php else : ?>
	          <p style="margin:0 0 10px;font-weight:700">自動で取り込む</p>
	          <button class="button button-primary" name="ymkrf_cimp_auto_on" value="1">自動で取り込みはじめる</button>
	        <?php endif; ?>
	      </form>
	    </div>
	    <?php if ( $auto ) : ?><meta http-equiv="refresh" content="25"><?php endif; ?>

	    <form method="post">
	      <?php wp_nonce_field( 'ymkrf_cimp' ); ?>
	      <p>いちどに
	        <select name="n">
	          <option value="3">3</option>
	          <option value="5" selected>5</option>
	          <option value="10">10</option>
	        </select> 本ずつ</p>
	      <p>
	        <button class="button button-primary" name="ymkrf_cimp_go" value="1">つづきを取り込む</button>
	        <button class="button" name="ymkrf_cimp_reset" value="1"
	          onclick="return confirm('1本目から数えなおします。取り込んだ記事は消えません。よろしいですか？')">
	          はじめから数えなおす</button>
	      </p>
	      <p class="description">
	        旧ブログで<b>下書き</b>だったものは、こちらでも下書きで入ります。<br>
	        掲載日も、旧ブログのものをそのまま引きつぎます。
	      </p>
	    </form>
	  <?php endif; ?>

	  <?php
	  /* ---- 重なって入ったコラムの片づけ ---- */
	  $dups = ymkrf_cimp_dups();
	  ?>
	  <h2 style="margin-top:28px">重なって入ったコラムの片づけ</h2>

	  <?php if ( $dup_done > -1 ) : ?>
	    <div class="notice notice-success"><p>
	      <b><?php echo (int) $dup_done; ?> 本</b>をゴミ箱に入れました。
	      まちがっていたら、コラム一覧の「ゴミ箱」からもどせます。
	    </p></div>
	  <?php endif; ?>

	  <?php if ( ! $dups ) : ?>
	    <p>重なって入ったコラムは<b>ありません</b>。</p>
	  <?php else : ?>
	    <div class="notice notice-warning"><p>
	      同じ記事から、コラムが<b>2本できてしまったもの</b>が
	      <b><?php echo count( $dups ); ?> 件</b>あります。<br>
	      下のボタンを押すと、<b>あとからできた方だけ</b>をゴミ箱に入れます。
	      先にできた方（写真も本文も入っている方）は、そのまま残ります。<br>
	      ゴミ箱に入れるだけなので、まちがっていても元にもどせます。
	    </p></div>

	    <form method="post" style="margin:0 0 14px">
	      <?php wp_nonce_field( 'ymkrf_cimp' ); ?>
	      <button class="button button-primary" name="ymkrf_cimp_dedupe" value="1"
	        onclick="return confirm('あとからできた方をゴミ箱に入れます。よろしいですか？')">
	        重なった方をゴミ箱に入れる</button>
	    </form>

	    <table class="widefat striped" style="max-width:900px">
	      <thead><tr>
	        <th style="width:11em">残す（文字数）</th>
	        <th style="width:12em">ゴミ箱へ（文字数）</th>
	        <th>題名</th>
	      </tr></thead>
	      <tbody>
	      <?php foreach ( $dups as $d ) : ?>
	        <?php
	        $ex = array();
	        foreach ( $d['extra'] as $id ) {
	          $ex[] = '#' . $id . '（' . number_format_i18n( $d['len'][ $id ] ) . '字）';
	        }
	        ?>
	        <tr>
	          <td>#<?php echo (int) $d['keep']; ?>
	            （<?php echo number_format_i18n( $d['len'][ $d['keep'] ] ); ?>字）</td>
	          <td><?php echo esc_html( implode( ' / ', $ex ) ); ?></td>
	          <td><a href="<?php echo esc_url( get_edit_post_link( $d['keep'] ) ); ?>"><?php
	            echo esc_html( $d['title'] ); ?></a></td>
	        </tr>
	      <?php endforeach; ?>
	      </tbody>
	    </table>
	  <?php endif; ?>

	  <?php
	  /* ---- 旧ブログの執筆者を、スタッフにひもづける ---- */
	  $writers = ymkrf_cimp_writers();
	  $staff   = function_exists( 'ymkrf_staff_list' ) ? ymkrf_staff_list() : array();
	  if ( function_exists( 'ymkrf_column_writer_sort' ) ) $staff = ymkrf_column_writer_sort( $staff );
	  ?>
	  <h2 style="margin-top:28px">旧ブログの執筆者を、スタッフにひもづける</h2>

	  <?php if ( $wl_done !== null ) : ?>
	    <div class="notice notice-success"><p>
	      <b><?php echo (int) $wl_done; ?> 本</b>の記事に、執筆者を入れました。
	    </p></div>
	  <?php endif; ?>

	  <div class="notice notice-info"><p>
	    旧ブログの記事には、書いた人が<b>文字だけ</b>で入っています。<br>
	    ここでスタッフをえらぶと、記事の下に<b>顔写真・趣味・ひとこと</b>が出るようになります。<br>
	    すでに執筆者が入っている記事は、そのままにします（上書きしません）。
	  </p></div>

	  <?php if ( ! $writers ) : ?>
	    <p>旧ブログの執筆者は<b>ありません</b>。</p>
	  <?php elseif ( ! $staff ) : ?>
	    <p>スタッフがまだ登録されていません。</p>
	  <?php else : ?>
	    <form method="post" style="margin:0 0 14px">
	      <?php wp_nonce_field( 'ymkrf_cimp' ); ?>
	      <table class="widefat striped" style="max-width:760px">
	        <thead><tr>
	          <th style="width:14em">旧ブログの名前</th>
	          <th style="width:7em">記事の数</th>
	          <th>この人にする</th>
	        </tr></thead>
	        <tbody>
	        <?php foreach ( $writers as $wname => $cnt ) : ?>
	          <tr>
	            <td><b><?php echo esc_html( $wname ); ?></b></td>
	            <td><?php echo (int) $cnt['all']; ?> 本
	              <?php if ( $cnt['done'] ) : ?>
	                <br><span style="color:#00782a;font-size:12px">
	                  <?php echo (int) $cnt['done']; ?> 本ずみ</span>
	              <?php endif; ?>
	            </td>
	            <td>
	              <select name="ymkrf_wmap[<?php echo esc_attr( $wname ); ?>]" style="min-width:22em">
	                <option value="0">（そのまま）</option>
	                <option value="<?php echo (int) YMKRF_WRITER_PR; ?>">広報</option>
	                <?php foreach ( $staff as $st ) :
	                  $shop = function_exists( 'ymkrf_staff_shop_name' )
	                        ? trim( (string) ymkrf_staff_shop_name( $st->ID ) ) : ''; ?>
	                  <option value="<?php echo (int) $st->ID; ?>"><?php
	                    echo esc_html( get_the_title( $st ) . ( $shop !== '' ? '（' . $shop . '）' : '' ) );
	                  ?></option>
	                <?php endforeach; ?>
	              </select>
	            </td>
	          </tr>
	        <?php endforeach; ?>
	        </tbody>
	      </table>
	      <p>
	        <button class="button button-primary" name="ymkrf_cimp_wlink" value="1">
	          えらんだ人をまとめて入れる</button>
	      </p>
	    </form>
	  <?php endif; ?>

	  <?php
	  /* ---- 本文の中の古いボタンを消す ---- */
	  list( $cta_posts, $cta_items ) = ymkrf_cimp_cta_clean_all( true );
	  ?>
	  <h2 style="margin-top:28px">本文の中の古いボタンを消す</h2>

	  <?php if ( is_array( $cta_done ) ) : ?>
	    <div class="notice notice-success"><p>
	      <?php if ( $cta_done[1] === -1 ) : ?>
	        <b><?php echo (int) $cta_done[0]; ?> 本</b>の記事を、消す前の本文にもどしました。
	      <?php else : ?>
	        <b><?php echo (int) $cta_done[0]; ?> 本</b>の記事から、
	        <b><?php echo (int) $cta_done[1]; ?> 個</b>のボタンを消しました。
	      <?php endif; ?>
	    </p></div>
	  <?php endif; ?>

	  <div class="notice notice-info"><p>
	    旧ブログの記事の終わりにある
	    <b>「➡LINEで相談」「➡無料相談・お見積り」「➡来店予約はこちら」</b>
	    の画像を消します。<br>
	    新しいサイトでは、記事の下に同じボタンが出るので重なってしまうためです。<br>
	    <b>消す前の本文は残しておく</b>ので、いつでも元にもどせます。
	  </p></div>

	  <?php if ( $cta_posts < 1 ) : ?>
	    <p>消すものは<b>ありません</b>。</p>
	  <?php else : ?>
	    <p>いま <b><?php echo (int) $cta_posts; ?> 本</b>の記事に、
	       <b><?php echo (int) $cta_items; ?> 個</b>の古いボタンがあります。</p>
	  <?php endif; ?>

	  <form method="post" style="margin:0 0 14px">
	    <?php wp_nonce_field( 'ymkrf_cimp' ); ?>
	    <?php if ( $cta_posts > 0 ) : ?>
	      <button class="button button-primary" name="ymkrf_cimp_cta" value="1"
	        onclick="return confirm('本文の中の古いボタンを消します。あとで元にもどせます。よろしいですか？')">
	        古いボタンを消す</button>
	    <?php endif; ?>
	    <button class="button" name="ymkrf_cimp_cta_undo" value="1"
	      onclick="return confirm('消す前の本文にもどします。よろしいですか？')">
	      消す前にもどす</button>
	  </form>

	  <?php if ( $log ) : ?>
	    <h2>記録（新しい順）</h2>
	    <ol style="background:#fff;border:1px solid #ccd0d4;padding:12px 12px 12px 34px;
	               max-height:420px;overflow:auto;line-height:1.9">
	      <?php foreach ( $log as $l ) : ?>
	        <li style="<?php echo strpos( $l, 'NG' ) === 0 ? 'color:#b32d2e' : ''; ?>">
	          <?php echo esc_html( $l ); ?></li>
	      <?php endforeach; ?>
	    </ol>
	  <?php endif; ?>
	</div>
	<?php
}

/* ============================================================
   4-3. 本文の中の「古いボタン画像」を消す

   旧ブログの記事の終わりには、
     ➡LINEで相談　※お友だち登録後に相談可能！
     ➡無料相談・お見積り
     ➡来店予約はこちら
   という画像がならんでいます。
   新しいサイトでは、記事の下に同じボタンが出るので重なります。
   ここで、その画像だけを取りのぞきます。

   ★消す前の本文は _ymkrf_cta_backup に残すので、元にもどせます。
   ============================================================ */

define( 'YMKRF_CTA_BK', '_ymkrf_cta_backup' );

/** 消したい文言。どれかが入っていたら、そのかたまりを消します。 */
function ymkrf_cimp_cta_words() {
	return array(
		'LINEで相談',
		'お友だち登録',
		'無料相談・お見積り',
		'無料相談･お見積り',
		'来店予約はこちら',
	);
}

/**
 * 本文から、古いボタンのかたまりを取りのぞきます。
 * 消したのが何個かを $n に入れて返します。
 */
function ymkrf_cimp_cta_strip( $html, &$n = 0 ) {

	$words = ymkrf_cimp_cta_words();
	$n = 0;

	$hit = function ( $chunk ) use ( $words ) {
		$text = wp_strip_all_tags( $chunk );
		/* 画像の alt も見ます */
		if ( preg_match_all( '/alt="([^"]*)"/i', $chunk, $m ) ) {
			$text .= ' ' . implode( ' ', $m[1] );
		}
		foreach ( $words as $w ) {
			if ( mb_strpos( $text, $w ) !== false ) return true;
		}
		return false;
	};

	/* ① <figure>〜</figure> のかたまり */
	$html = preg_replace_callback(
		'#<figure\b[^>]*>.*?</figure>#is',
		function ( $m ) use ( $hit, &$n ) {
			if ( $hit( $m[0] ) ) { $n++; return ''; }
			return $m[0];
		},
		$html
	);

	/* ② ➡ ではじまる短い段落（画像ではなく文字だけのもの） */
	$html = preg_replace_callback(
		'#<p\b[^>]*>(?:(?!</p>).)*?</p>#is',
		function ( $m ) use ( $hit, &$n ) {
			$t = trim( wp_strip_all_tags( $m[0] ) );
			if ( $t !== '' && mb_strpos( $t, '➡' ) === 0 && mb_strlen( $t ) < 40 && $hit( $m[0] ) ) {
				$n++; return '';
			}
			return $m[0];
		},
		$html
	);

	/* あとに残る空の行をかたづけます */
	$html = preg_replace( '#<p>(\s|&nbsp;|<br\s*/?>)*</p>#i', '', $html );
	$html = preg_replace( '#(<br\s*/?>\s*){3,}#i', '<br><br>', $html );

	return $html;
}

/**
 * コラム全部を見て、古いボタンを消します。
 * $dry が true のときは、数をかぞえるだけで保存しません。
 *
 * @return array array( 記事数, 消した個数 )
 */
function ymkrf_cimp_cta_clean_all( $dry = true ) {

	$ids = get_posts( array(
		'post_type'      => 'ymkrf_column',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	) );

	$posts = 0; $items = 0;

	foreach ( $ids as $id ) {
		$p = get_post( $id );
		if ( ! $p ) continue;

		$n   = 0;
		$new = ymkrf_cimp_cta_strip( (string) $p->post_content, $n );
		if ( $n < 1 || $new === $p->post_content ) continue;

		$posts++; $items += $n;

		if ( ! $dry ) {
			/* 消す前の本文を残します（元にもどせるように） */
			if ( get_post_meta( $id, YMKRF_CTA_BK, true ) === '' ) {
				update_post_meta( $id, YMKRF_CTA_BK, $p->post_content );
			}
			wp_update_post( array( 'ID' => $id, 'post_content' => $new ) );
		}
	}
	return array( $posts, $items );
}

/** 消す前の本文にもどします */
function ymkrf_cimp_cta_restore_all() {
	$ids = get_posts( array(
		'post_type'      => 'ymkrf_column',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => array( array( 'key' => YMKRF_CTA_BK, 'compare' => 'EXISTS' ) ),
	) );
	$n = 0;
	foreach ( $ids as $id ) {
		$bk = (string) get_post_meta( $id, YMKRF_CTA_BK, true );
		if ( $bk === '' ) continue;
		wp_update_post( array( 'ID' => $id, 'post_content' => $bk ) );
		delete_post_meta( $id, YMKRF_CTA_BK );
		$n++;
	}
	return $n;
}

/* ============================================================
   4-4. 旧ブログの執筆者を、スタッフにひもづける

   旧ブログの記事には、書いた人が「広報担当」「ナカニシ」など
   文字で入っています（_ymkrf_old_writer）。
   これを新しいサイトのスタッフ（_ymkrf_staff）にひもづけると、
   記事の下に顔写真・趣味・ひとことが出るようになります。

   ★もとの文字は消しません。あとから見なおせます。
   ============================================================ */

/** 旧ブログの執筆者の名前と、その本数を数えます */
function ymkrf_cimp_writers() {

	$ids = get_posts( array(
		'post_type'      => 'ymkrf_column',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => array( array( 'key' => '_ymkrf_old_writer', 'compare' => 'EXISTS' ) ),
	) );

	$out = array();
	foreach ( $ids as $id ) {
		$w = trim( (string) get_post_meta( $id, '_ymkrf_old_writer', true ) );
		if ( $w === '' ) continue;
		if ( ! isset( $out[ $w ] ) ) $out[ $w ] = array( 'all' => 0, 'done' => 0 );
		$out[ $w ]['all']++;
		if ( (int) get_post_meta( $id, '_ymkrf_staff', true ) ) $out[ $w ]['done']++;
	}
	ksort( $out );
	return $out;
}

/**
 * 旧ブログの名前 → スタッフをひもづけます。
 * すでにスタッフがえらばれている記事は、さわりません。
 *
 * @return int 直した本数
 */
function ymkrf_cimp_writer_link( $old_name, $staff_id ) {

	$old_name = trim( (string) $old_name );
	$staff_id = (int) $staff_id;
	if ( $old_name === '' || $staff_id === 0 ) return 0;

	/* 「広報担当」はスタッフではないので、そのまま通します */
	if ( $staff_id !== (int) YMKRF_WRITER_PR ) {
		if ( $staff_id < 1 ) return 0;
		$sp = get_post( $staff_id );
		if ( ! $sp || $sp->post_type !== 'ymkrf_staff' ) return 0;
	}

	$ids = get_posts( array(
		'post_type'      => 'ymkrf_column',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => array( array( 'key' => '_ymkrf_old_writer', 'value' => $old_name ) ),
	) );

	$n = 0;
	foreach ( $ids as $id ) {
		if ( (int) get_post_meta( $id, '_ymkrf_staff', true ) ) continue;
		update_post_meta( $id, '_ymkrf_staff', $staff_id );
		$n++;
	}
	return $n;
}
