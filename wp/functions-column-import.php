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

	$log = (array) get_option( YMKRF_CIMP_LOG, array() );
	$n = 0; $t0 = time();

	/* 写真が多いので、1回に3本ずつにします */
	while ( $pos < count( $rows ) && $n < 3 && ( time() - $t0 ) < 100 ) {
		list( $ok, $msg ) = ymkrf_cimp_one( $rows[ $pos ] );
		array_unshift( $log, ( $ok ? 'OK' : 'NG' ) . ' : ' . $msg );
		$pos++; $n++;
	}

	update_option( YMKRF_CIMP_POS, $pos, false );
	update_option( YMKRF_CIMP_LOG, array_slice( $log, 0, 60 ), false );

	if ( $pos < count( $rows ) ) wp_schedule_single_event( time() + 20, 'ymkrf_cimp_tick' );
	else                         update_option( YMKRF_CIMP_AUTO, '', false );
}

add_action( 'admin_init', function () {
	if ( get_option( YMKRF_CIMP_AUTO ) !== '1' ) return;
	if ( ! wp_next_scheduled( 'ymkrf_cimp_tick' ) ) {
		wp_schedule_single_event( time() + 10, 'ymkrf_cimp_tick' );
	}
} );


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
	            ● 自動で取り込んでいます（20秒ごとに3本ずつ）</p>
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
