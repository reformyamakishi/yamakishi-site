<?php
/**
 * お客様の声を、いまの公開サイト（yamakishi-reform.jp）から取り込むしくみ。
 *
 * ・一時的なものです。取り込みが終わったら、このファイルと
 *   functions.php の読み込み行を消してください。
 *
 * 使いかた
 *   1. wp-content/ymkrf-voices.json を置く
 *   2. 管理画面「お客様の声 ＞ まとめて取り込み」をひらく
 *   3.「自動で取り込みはじめる」を押す
 *
 * ★ 大事な決めごと（2026/09/07 ユーザー確認）
 *
 *   ・古いアンケートは中身がうすい（お客様のことば1行＋工事内容8文字ほど）ので、
 *     1件ずつのページは<b>検索エンジンに登録させません</b>（noindex）。
 *     保存はできて、施工事例のページには出るので、お客様には届きます。
 *
 *   ・アンケートの画像は、お名前が塗りつぶされていません。
 *     そのため <b>「元の画像」の欄にだけ</b>入れ、公開用の欄には入れません。
 *     管理画面では見られますが、お客様のページには出ません。
 *
 * @package ymkrf
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'YMKRF_VIMP_HOST', 'yamakishi-reform.jp' );
define( 'YMKRF_VIMP_FILE', WP_CONTENT_DIR . '/ymkrf-voices.json' );
define( 'YMKRF_VIMP_POS',  'ymkrf_voice_imp_pos' );
define( 'YMKRF_VIMP_LOG',  'ymkrf_voice_imp_log' );
define( 'YMKRF_VIMP_AUTO', 'ymkrf_voice_imp_auto' );

/* 取り込んだ印。この印が付いたものは検索エンジンに登録させません */
define( 'YMKRF_VIMP_MARK', '_ymkrf_old_voice' );


/* ============================================================
   1. 読み取ったデータ
   ============================================================ */

function ymkrf_vimp_rows() {
	static $rows = null;
	if ( $rows !== null ) return $rows;

	$rows = array();
	if ( file_exists( YMKRF_VIMP_FILE ) ) {
		$j = json_decode( (string) file_get_contents( YMKRF_VIMP_FILE ), true );
		if ( isset( $j['rows'] ) ) $j = $j['rows'];
		if ( is_array( $j ) ) $rows = $j;
	}
	return $rows;
}


/* ============================================================
   2. 入れかたの決めごと
   ============================================================ */

/** 本番の店舗番号 → 新サイトの店舗スラッグ */
function ymkrf_vimp_shop( $n ) {
	$m = array(
		'1'  => 'kawakita',  '2' => 'tazuruhama', '3' => 'nonoichi',
		'4'  => 'komathu',   '5' => 'shinkaga',   '6' => 'kanadu',
		'7'  => 'kahahothu', '8' => 'asahi',      '9' => 'hakui',
		'10' => 'tagami',
	);
	$n = (string) $n;
	return isset( $m[ $n ] ) ? $m[ $n ] : '';
}

/**
 * 本番の「リフォーム箇所」＋工事内容の文字から、
 * 新サイトの工事箇所（ymkrf_voice_parts_list）を決めます。
 * 施工事例の振り分けと同じ考えかたです。
 */
function ymkrf_vimp_parts( $r ) {

	$t = ( isset( $r['content'] ) ? $r['content'] : '' )
	   . ' ' . ( isset( $r['title'] ) ? $r['title'] : '' );

	$w = isset( $r['work_type'] ) ? $r['work_type'] : '';

	switch ( $w ) {
		case 'kitchen':
			return preg_match( '/レンジフード|換気扇/u', $t )
				? array( 'キッチン', 'レンジフード' ) : array( 'キッチン' );

		case 'bathroom': return array( '浴室' );
		case 'toilet':   return array( 'トイレ' );
		case 'lavatory': return array( '洗面室' );
		case 'painting': return preg_match( '/屋根|瓦|雨樋|雨どい/u', $t )
			? array( '外壁', '屋根' ) : array( '外壁' );
		case 'repair':   return array( '修理・小工事' );
		case 'interior': return array( '改装・内装' );
		case 'whole':    return array( '改装・内装' );

		case 'boiler':
			if ( preg_match( '/オイルタンク|ｵｲﾙﾀﾝｸ/u', $t ) )                        return array( 'オイルタンク' );
			if ( preg_match( '/エコキュート|ヒートポンプ|オール電化|エコワン/u', $t ) ) return array( 'エコキュート' );
			if ( preg_match( '/太陽光/u', $t ) )                                      return array( '太陽光発電' );
			if ( preg_match( '/蓄電池/u', $t ) )                                      return array( '蓄電池' );
			return array( '給湯器' );

		case 'exterior':
			if ( preg_match( '/カーポート|ｶｰﾎﾟｰﾄ|ガレージ|車庫/u', $t ) ) return array( 'カーポート' );
			if ( preg_match( '/内窓|窓|サッシ|прозр/u', $t ) )             return array( '窓・サッシ' );
			if ( preg_match( '/玄関|ドア|勝手口/u', $t ) )                 return array( 'ドア' );
			return array( 'エクステリア' );
	}

	/* リフォーム箇所が入っていないときは、工事内容の文字から見ます */
	if ( preg_match( '/内窓|サッシ|窓/u', $t ) )         return array( '窓・サッシ' );
	if ( preg_match( '/玄関|ドア|勝手口/u', $t ) )       return array( 'ドア' );
	if ( preg_match( '/レンジフード|換気扇/u', $t ) )    return array( 'レンジフード' );
	return array( 'その他' );
}

/**
 * お客様名（「加賀市　M様」）を、市と頭文字に分けます。
 * 市の名前は、施工事例と同じやり方でそろえます。
 */
function ymkrf_vimp_who( $s ) {

	$t = trim( preg_replace( '/[\s　]+/u', ' ', (string) $s ) );
	if ( $t === '' ) return array( '', '' );

	$ini = '';
	if ( preg_match( '/([A-Za-zＡ-Ｚａ-ｚ])\s*様/u', $t, $m ) ) {
		$ini = strtoupper( mb_convert_kana( $m[1], 'a', 'UTF-8' ) );
	}

	/* 「様」より前を住所とみなします */
	$addr = preg_replace( '/[\s　]*[A-Za-zＡ-Ｚａ-ｚ]?[\s　]*様.*$/u', '', $t );
	$city = function_exists( 'ymkrf_works_import_city' )
	        ? ymkrf_works_import_city( $addr ) : trim( $addr );

	return array( $city, $ini );
}

/** 案件番号は「2604-0365,2605-0083」のように複数のことがあります */
function ymkrf_vimp_case_nos( $s ) {
	$t = (string) $s;
	$t = str_replace( array( '、', '/', '／', ' ', '　' ), ',', $t );
	$out = array();
	foreach ( explode( ',', $t ) as $v ) {
		$v = trim( $v );
		if ( $v === '' ) continue;
		/* 「26030435」のようにハイフンが抜けているものは、入れてあげます */
		if ( preg_match( '/^\d{8}$/', $v ) ) $v = substr( $v, 0, 4 ) . '-' . substr( $v, 4 );
		if ( ! in_array( $v, $out, true ) ) $out[] = $v;
	}
	return $out;
}

/** 写真をもらってきて、メディアに入れます（同じ写真は1回だけ） */
function ymkrf_vimp_photo( $key, $post_id ) {

	$key = trim( (string) $key );
	if ( $key === '' ) return 0;

	$found = get_posts( array(
		'post_type' => 'attachment', 'posts_per_page' => 1, 'fields' => 'ids',
		'post_status' => 'any',
		'meta_query' => array( array( 'key' => '_ymkrf_imp_key', 'value' => $key ) ),
	) );
	if ( $found ) return (int) $found[0];

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$url = 'https://' . YMKRF_VIMP_HOST . '/uploads/raw/' . rawurlencode( $key );
	$tmp = download_url( $url, 60 );
	if ( is_wp_error( $tmp ) ) return 0;

	$file = array(
		'name'     => 'ymkv-' . strtolower( preg_replace( '/[^0-9A-Za-z]/', '', $key ) ) . '.jpg',
		'tmp_name' => $tmp,
	);
	$id = media_handle_sideload( $file, $post_id, '' );
	if ( is_wp_error( $id ) ) {
		if ( file_exists( $tmp ) ) @unlink( $tmp );
		return 0;
	}
	update_post_meta( $id, '_ymkrf_imp_key', $key );
	return (int) $id;
}


/* ============================================================
   3. 1件を入れます
   ============================================================ */

function ymkrf_vimp_one( $r ) {

	$src = isset( $r['i'] ) ? (string) $r['i'] : '';
	if ( $src === '' ) return array( false, 'IDがありません' );

	/* すでに取り込んでいたら、それを更新します（増えません） */
	$exist = get_posts( array(
		'post_type' => 'ymkrf_voice', 'posts_per_page' => 1, 'fields' => 'ids',
		'post_status' => 'any',
		'meta_query' => array( array( 'key' => '_ymkrf_old_src', 'value' => $src ) ),
	) );

	$nos  = ymkrf_vimp_case_nos( isset( $r['process_num'] ) ? $r['process_num'] : '' );

	/* ★ 同じ案件番号のものが、すでに手で登録されていたら飛ばします。
	     新しい様式で入れていただいた14件は、お困りごと・満足度・評価まで
	     そろっていて中身が濃いので、そちらを残します。
	     （2026/09/08 ユーザー確認「はい」＝すでにあるものを優先） */
	if ( ! $exist && $nos ) {
		$same = get_posts( array(
			'post_type' => 'ymkrf_voice', 'posts_per_page' => 1, 'fields' => 'ids',
			'post_status' => 'any',
			'meta_query' => array( array( 'key' => '_ymkrf_case_no', 'value' => $nos[0] ) ),
		) );
		if ( $same ) {
			return array( true, '飛ばしました：案件No. ' . $nos[0]
			              . ' は、すでに登録ずみです（#' . (int) $same[0] . ' '
			              . get_the_title( (int) $same[0] ) . '）' );
		}
	}
	list( $city, $ini ) = ymkrf_vimp_who( isset( $r['client_name'] ) ? $r['client_name'] : '' );
	$parts = ymkrf_vimp_parts( $r );
	$word  = trim( (string) ( isset( $r['title'] ) ? $r['title'] : '' ) );
	$work  = trim( (string) ( isset( $r['content'] ) ? $r['content'] : '' ) );

	$post = array(
		'post_type'    => 'ymkrf_voice',
		'post_status'  => 'publish',
		/* 題名は保存のときに「加賀市｜浴室リフォームのお客様の声」の形に
		   自動でととのえられます（inc/functions-voice.php の 1-1） */
		'post_title'   => ( $city !== '' ? $city . '｜' : '' )
		                . ( $parts ? implode( '・', array_slice( $parts, 0, 2 ) ) . 'リフォーム' : '' )
		                . 'のお客様の声',
		'post_content' => '',
		'post_author'  => function_exists( 'ymkrf_works_import_author' ) ? ymkrf_works_import_author() : 1,
	);
	if ( $exist ) $post['ID'] = (int) $exist[0];

	$id = $exist ? wp_update_post( $post, true ) : wp_insert_post( $post, true );
	if ( is_wp_error( $id ) ) return array( false, $id->get_error_message() );

	$put = function ( $k, $v ) use ( $id ) {
		if ( $v !== '' && $v !== null ) update_post_meta( $id, $k, $v );
	};

	$put( '_ymkrf_old_src', $src );                     /* もとのID（二重取り込みをふせぎます） */
	update_post_meta( $id, YMKRF_VIMP_MARK, '1' );      /* 古いアンケートの印 */

	if ( $nos ) {
		$put( '_ymkrf_case_no', $nos[0] );
		/* 2つ目からは、別の欄にとっておきます（同じお客様の2回目以降の工事） */
		if ( count( $nos ) > 1 ) {
			$put( '_ymkrf_case_no_more', implode( ',', array_slice( $nos, 1 ) ) );
		}
	}
	$put( '_ymkrf_city',    $city );
	$put( '_ymkrf_initial', $ini );
	$put( '_ymkrf_shop',    ymkrf_vimp_shop( isset( $r['shop'] ) ? $r['shop'] : '' ) );
	$put( '_ymkrf_parts',   implode( ',', $parts ) );

	/* お客様のことば。ページに出るのは、ここです */
	$put( '_ymkrf_comment', $word );

	/* 工事の中身（「浴室照明取替」など）。本文に短く入れておきます */
	if ( $work !== '' ) {
		wp_update_post( array( 'ID' => $id, 'post_content' => $work ) );
	}

	/* 写真。
	   アンケート用紙は、お名前がすでに「野々市市　Y様」のように
	   伏せられているので、そのままお客様のページに出せます。
	   元の画像・公開用の両方に、同じものを入れます。
	   （2026/09/07 ユーザー確認）
	   ただし様式が今と違うので、見出しだけ変えます（下の 4-b）。 */
	$n = 0;
	if ( ! empty( $r['survey'] ) ) {
		$att = ymkrf_vimp_photo( $r['survey'], $id );
		if ( $att ) {
			update_post_meta( $id, '_ymkrf_survey_id', $att );
			update_post_meta( $id, '_ymkrf_survey_pub_id', $att );
			$n++;
		}
	}
	if ( ! empty( $r['image'] ) ) {
		$att = ymkrf_vimp_photo( $r['image'], $id );
		if ( $att ) { update_post_meta( $id, '_ymkrf_old_image', $att ); $n++; }
	}

	return array( true, ( $exist ? '（上書き）' : '' ) . '#' . $id . ' '
	              . get_the_title( $id ) . '「' . mb_strimwidth( $word, 0, 40, '…', 'UTF-8' ) . '」'
	              . '（写真' . $n . '枚' . ( $nos ? '／' . $nos[0] : '／番号なし' ) . '）' );
}


/* ============================================================
   4. 古いアンケートは、検索エンジンに登録させません
      中身がうすいので、1件ずつのページが大量に並ぶと
      サイト全体の評価が下がるためです。
      保存はできますし、施工事例のページには出ます。
   ============================================================ */
add_action( 'wp_head', function () {
	if ( ! is_singular( 'ymkrf_voice' ) ) return;
	if ( get_post_meta( get_the_ID(), YMKRF_VIMP_MARK, true ) !== '1' ) return;
	echo '<meta name="robots" content="noindex,follow">' . "\n";
}, 1 );

/* サイトマップにも入れません */
add_filter( 'wp_sitemaps_posts_query_args', function ( $args, $type ) {
	if ( $type !== 'ymkrf_voice' ) return $args;
	$args['meta_query'] = array( array( 'key' => YMKRF_VIMP_MARK, 'compare' => 'NOT EXISTS' ) );
	return $args;
}, 10, 2 );


/* ============================================================
   5. 裏で少しずつ取り込みつづけるしくみ
   ============================================================ */

add_action( 'ymkrf_vimp_tick', 'ymkrf_vimp_tick' );

function ymkrf_vimp_tick() {

	if ( get_option( YMKRF_VIMP_AUTO ) !== '1' ) return;

	$rows = ymkrf_vimp_rows();
	$pos  = (int) get_option( YMKRF_VIMP_POS, 0 );
	if ( ! $rows || $pos >= count( $rows ) ) {
		update_option( YMKRF_VIMP_AUTO, '', false );
		return;
	}

	$log = (array) get_option( YMKRF_VIMP_LOG, array() );
	$n = 0; $t0 = time();

	while ( $pos < count( $rows ) && $n < 10 && ( time() - $t0 ) < 100 ) {
		list( $ok, $msg ) = ymkrf_vimp_one( $rows[ $pos ] );
		array_unshift( $log, ( $ok ? 'OK' : 'NG' ) . ' : ' . $msg );
		$pos++; $n++;
	}

	update_option( YMKRF_VIMP_POS, $pos, false );
	update_option( YMKRF_VIMP_LOG, array_slice( $log, 0, 80 ), false );

	if ( $pos < count( $rows ) ) {
		wp_schedule_single_event( time() + 30, 'ymkrf_vimp_tick' );
	} else {
		update_option( YMKRF_VIMP_AUTO, '', false );
	}
}

add_action( 'admin_init', function () {
	if ( get_option( YMKRF_VIMP_AUTO ) !== '1' ) return;
	if ( ! wp_next_scheduled( 'ymkrf_vimp_tick' ) ) {
		wp_schedule_single_event( time() + 10, 'ymkrf_vimp_tick' );
	}
} );


/**
 * 案件番号が二重になっているものを探します。
 * 残す＝手で登録していただいたほう、外す＝取り込んだほう。
 */
function ymkrf_vimp_find_dups() {

	global $wpdb;
	$rows = $wpdb->get_results(
		"SELECT p.ID, m.meta_value AS no,
		        ( SELECT meta_value FROM {$wpdb->postmeta}
		           WHERE post_id = p.ID AND meta_key = '_ymkrf_old_voice' LIMIT 1 ) AS is_old
		   FROM {$wpdb->posts} p
		   JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_ymkrf_case_no'
		  WHERE p.post_type = 'ymkrf_voice'
		    AND p.post_status IN ('publish','draft','pending','private')
		    AND m.meta_value <> ''
		  ORDER BY p.ID ASC"
	);

	$by = array();
	foreach ( (array) $rows as $r ) $by[ $r->no ][] = $r;

	$out = array();
	foreach ( $by as $no => $list ) {
		if ( count( $list ) < 2 ) continue;
		/* 手で入れたもの（印なし）を残します。無ければ、いちばん古いものを残します */
		$keep = null;
		foreach ( $list as $r ) { if ( $r->is_old !== '1' ) { $keep = $r; break; } }
		if ( ! $keep ) $keep = $list[0];
		foreach ( $list as $r ) {
			if ( (int) $r->ID === (int) $keep->ID ) continue;
			$out[] = array( 'no' => $no, 'keep' => (int) $keep->ID, 'drop' => (int) $r->ID );
		}
	}
	return $out;
}


/* ============================================================
   6. 管理画面
   ============================================================ */

add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=ymkrf_voice',
		'まとめて取り込み', 'まとめて取り込み',
		'manage_options', 'ymkrf-voice-bulk', 'ymkrf_vimp_page'
	);
}, 30 );

function ymkrf_vimp_page() {

	if ( ! current_user_can( 'manage_options' ) ) return;

	$rows = ymkrf_vimp_rows();
	$pos  = (int) get_option( YMKRF_VIMP_POS, 0 );
	$log  = (array) get_option( YMKRF_VIMP_LOG, array() );

	if ( isset( $_POST['ymkrf_vimp_go'] ) && check_admin_referer( 'ymkrf_vimp' ) ) {
		$want = isset( $_POST['n'] ) ? max( 1, min( 30, (int) $_POST['n'] ) ) : 10;
		$n = 0; $t0 = time();
		while ( $pos < count( $rows ) && $n < $want && ( time() - $t0 ) < 150 ) {
			list( $ok, $msg ) = ymkrf_vimp_one( $rows[ $pos ] );
			array_unshift( $log, ( $ok ? 'OK' : 'NG' ) . ' : ' . $msg );
			$pos++; $n++;
		}
		$log = array_slice( $log, 0, 80 );
		update_option( YMKRF_VIMP_POS, $pos, false );
		update_option( YMKRF_VIMP_LOG, $log, false );
	}

	if ( isset( $_POST['ymkrf_vimp_auto_on'] ) && check_admin_referer( 'ymkrf_vimp' ) ) {
		update_option( YMKRF_VIMP_AUTO, '1', false );
		if ( ! wp_next_scheduled( 'ymkrf_vimp_tick' ) ) {
			wp_schedule_single_event( time() + 5, 'ymkrf_vimp_tick' );
		}
	}
	if ( isset( $_POST['ymkrf_vimp_auto_off'] ) && check_admin_referer( 'ymkrf_vimp' ) ) {
		update_option( YMKRF_VIMP_AUTO, '', false );
		wp_clear_scheduled_hook( 'ymkrf_vimp_tick' );
	}
	if ( isset( $_POST['ymkrf_vimp_reset'] ) && check_admin_referer( 'ymkrf_vimp' ) ) {
		update_option( YMKRF_VIMP_POS, 0, false );
		update_option( YMKRF_VIMP_LOG, array(), false );
		$pos = 0; $log = array();
	}

	/* 案件番号が二重になっているものを、ゴミ箱に入れます。
	   残すのは、手で登録していただいたほう（新しい様式で中身が濃い）。
	   取り込んだほう（_ymkrf_old_voice の印が付いたもの）を外します。 */
	if ( isset( $_POST['ymkrf_vimp_dedup'] ) && check_admin_referer( 'ymkrf_vimp' ) ) {
		$dups = ymkrf_vimp_find_dups();
		$n = 0;
		foreach ( $dups as $d ) { if ( wp_trash_post( $d['drop'] ) ) $n++; }
		$deduped = $n;
	}

	$auto = ( get_option( YMKRF_VIMP_AUTO ) === '1' );
	$have = (int) wp_count_posts( 'ymkrf_voice' )->publish;
	?>
	<div class="wrap">
	  <h1>お客様の声を まとめて取り込む</h1>

	  <?php if ( ! $rows ) : ?>
	    <div class="notice notice-warning"><p>
	      <b>読み取ったデータが見つかりません。</b><br>
	      <code><?php echo esc_html( YMKRF_VIMP_FILE ); ?></code> に
	      <code>ymkrf-voices.json</code> を置いてください。
	    </p></div>
	  <?php else : ?>

	    <div class="notice notice-info"><p>
	      <b>古いアンケートについて</b><br>
	      ・1件ずつのページは<b>検索エンジンに登録させません</b>
	        （中身がうすいため。施工事例のページには出ます）<br>
	      ・アンケートの画像は<b>お名前が塗りつぶされていない</b>ので、
	        管理画面だけで見られる欄に入れます。<b>お客様のページには出ません。</b>
	    </p></div>

	    <table class="widefat" style="max-width:680px;margin-bottom:16px">
	      <tr><th style="width:14em">読み取ったデータ</th><td><?php echo count( $rows ); ?> 件</td></tr>
	      <tr><th>取り込みずみ</th><td><?php echo (int) $pos; ?> 件</td></tr>
	      <tr><th>のこり</th><td><?php echo max( 0, count( $rows ) - $pos ); ?> 件</td></tr>
	      <tr><th>いまのお客様の声</th><td><?php echo $have; ?> 件</td></tr>
	    </table>

	    <div style="margin:16px 0;padding:14px 18px;border-radius:6px;
	                border:2px solid <?php echo $auto ? '#00782a' : '#dcdcde'; ?>;
	                background:<?php echo $auto ? '#f2fbf5' : '#fff'; ?>">
	      <form method="post" style="margin:0">
	        <?php wp_nonce_field( 'ymkrf_vimp' ); ?>
	        <?php if ( $auto ) : ?>
	          <p style="margin:0 0 8px;font-weight:700;color:#00782a">
	            ● 自動で取り込んでいます（30秒ごとに10件ずつ）</p>
	          <p class="description" style="margin:0 0 10px">
	            ボタンを押しつづけなくても進みます。この画面はひとりでに新しくなります。<br>
	            WordPressのしくみ上、<b>管理画面のどこかを開いているあいだ</b>だけ進みます。
	          </p>
	          <button class="button" name="ymkrf_vimp_auto_off" value="1">自動をやめる</button>
	        <?php else : ?>
	          <p style="margin:0 0 10px;font-weight:700">自動で取り込む</p>
	          <button class="button button-primary" name="ymkrf_vimp_auto_on" value="1">自動で取り込みはじめる</button>
	        <?php endif; ?>
	      </form>
	    </div>
	    <?php if ( $auto ) : ?><meta http-equiv="refresh" content="30"><?php endif; ?>

	    <?php
	    /* 案件番号が二重になっているものがあれば、外すボタンを出します */
	    $dups = ymkrf_vimp_find_dups();
	    ?>
	    <?php if ( isset( $deduped ) ) : ?>
	      <div class="notice notice-success"><p>
	        二重だったもの <?php echo (int) $deduped; ?> 件をゴミ箱に入れました。</p></div>
	    <?php endif; ?>
	    <?php if ( $dups ) : ?>
	      <div style="margin:16px 0;padding:14px 18px;border:2px solid #b26a00;border-radius:6px;background:#fffaf3">
	        <form method="post" style="margin:0">
	          <?php wp_nonce_field( 'ymkrf_vimp' ); ?>
	          <p style="margin:0 0 8px;font-weight:700;color:#b26a00">
	            案件番号が二重になっているものが <?php echo count( $dups ); ?> 件あります</p>
	          <p class="description" style="margin:0 0 10px">
	            手で登録していただいたぶんと、取り込んだぶんが重なっています。<br>
	            押すと、<b>取り込んだほうをゴミ箱に入れます</b>（手で入れたほうを残します）。<br>
	            <?php foreach ( array_slice( $dups, 0, 8 ) as $d ) : ?>
	              ・<?php echo esc_html( $d['no'] ); ?> …
	              のこす #<?php echo $d['keep']; ?>／はずす #<?php echo $d['drop']; ?><br>
	            <?php endforeach; ?>
	            <?php if ( count( $dups ) > 8 ) : ?>…ほか<?php echo count( $dups ) - 8; ?>件<?php endif; ?>
	          </p>
	          <button class="button" name="ymkrf_vimp_dedup" value="1">二重のものをゴミ箱へ</button>
	        </form>
	      </div>
	    <?php endif; ?>

	    <form method="post">
	      <?php wp_nonce_field( 'ymkrf_vimp' ); ?>
	      <p>いちどに
	        <select name="n">
	          <option value="5">5</option>
	          <option value="10" selected>10</option>
	          <option value="20">20</option>
	        </select> 件ずつ</p>
	      <p>
	        <button class="button button-primary" name="ymkrf_vimp_go" value="1">つづきを取り込む</button>
	        <button class="button" name="ymkrf_vimp_reset" value="1"
	          onclick="return confirm('1件目から数えなおします。取り込んだ記事は消えません。よろしいですか？')">
	          はじめから数えなおす</button>
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
