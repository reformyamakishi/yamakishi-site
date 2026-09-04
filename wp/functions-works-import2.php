<?php
/**
 * 施工事例を、いまの公開サイト（yamakishi-reform.jp）から取り込むしくみ。
 *
 * ・一時的なものです。取り込みが終わったら、このファイルは消してください。
 * ・使いかた
 *     1. ブラウザで本番サイトの管理画面にログインして、施工事例を読み取る
 *        （読み取った中身は、ブラウザの中に ymk_scan という名前でしまわれます）
 *     2. その中身を、このファイルの受け口へ送る
 *     3. 管理画面「施工事例 ＞ 本番から取り込み」で、20件ずつ取り込む
 *
 * ・案件No.が同じものが すでにあるときは、飛ばします（二重登録をふせぎます）
 * ・写真は https://yamakishi-reform.jp/uploads/raw/◯◯ から取ってきます
 *
 * @package ymkrf
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'YMKRF_IMP_ORIGIN', 'https://yamakishi-reform.jp' );
define( 'YMKRF_IMP_OPT',    'ymkrf_works_import_data' );   // 送られてきた中身
define( 'YMKRF_IMP_POS',    'ymkrf_works_import_pos'  );   // どこまで済んだか
define( 'YMKRF_IMP_LOG',    'ymkrf_works_import_log'  );   // 記録


/* ============================================================
   1. 受け口 — ブラウザから中身を受け取ります
   ============================================================ */

add_action( 'admin_post_ymkrf_imp_recv',        'ymkrf_imp_recv' );
add_action( 'admin_post_nopriv_ymkrf_imp_recv', 'ymkrf_imp_recv' );

function ymkrf_imp_recv() {

	/* 本番サイトの画面から送れるようにします（この作業のあいだだけ） */
	header( 'Access-Control-Allow-Origin: ' . YMKRF_IMP_ORIGIN );
	header( 'Access-Control-Allow-Methods: POST, OPTIONS' );
	header( 'Access-Control-Allow-Headers: Content-Type' );

	if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS' ) {
		status_header( 200 ); exit;
	}

	$raw  = file_get_contents( 'php://input' );
	$json = json_decode( $raw, true );

	if ( ! is_array( $json ) || ! isset( $json['rows'] ) || ! is_array( $json['rows'] ) ) {
		wp_send_json( array( 'ok' => false, 'msg' => '中身が読めませんでした' ) );
	}

	update_option( YMKRF_IMP_OPT, wp_json_encode( $json['rows'] ), false );
	update_option( YMKRF_IMP_POS, 0, false );
	update_option( YMKRF_IMP_LOG, array(), false );

	wp_send_json( array( 'ok' => true, 'count' => count( $json['rows'] ) ) );
}


/* ============================================================
   2. 入れかたの決めごと
   ============================================================ */

/** 本番の店舗番号 → 新サイトの店舗スラッグ */
function ymkrf_imp_shop( $n ) {
	$m = array(
		'1'  => 'kawakita',   '2'  => 'tazuruhama', '3'  => 'nonoichi',
		'4'  => 'komathu',    '5'  => 'shinkaga',   '6'  => 'kanadu',
		'7'  => 'kahahothu',  '8'  => 'asahi',      '9'  => 'hakui',
		'10' => 'tagami',
	);
	$n = (string) $n;
	return isset( $m[ $n ] ) ? $m[ $n ] : '';
}

/** 本番の担当者番号 → 名前 */
function ymkrf_imp_staff_name( $n ) {
	$m = array(
		'6'=>'山岸 直貴','8'=>'藪腰 洋一','9'=>'末永 耕司','13'=>'渡邉 栄',
		'17'=>'久保 宜之','21'=>'谷 外善志','22'=>'西尻 剛宏','23'=>'清水 美由紀',
		'24'=>'下内 貴博','26'=>'池田 昌史','27'=>'松田 祥司','28'=>'島川 朱美',
		'31'=>'才田 憲明','32'=>'池端 治夫','34'=>'神沢 将慶','35'=>'川田 昌和',
		'36'=>'市村 俊英','37'=>'茶木原 豊二','38'=>'西田 芳明','39'=>'山尻 新太郎',
		'40'=>'長谷川 佳代子','41'=>'山本 和也','43'=>'吉田 忍','46'=>'細川 達也',
		'48'=>'中川 ひとみ','51'=>'伊達 雅裕','54'=>'寺西 喜郎','60'=>'とんとこトン',
		'61'=>'泉 雄太','63'=>'筒井 照瑛','65'=>'荒井 敏文','68'=>'三井 一晃',
		'69'=>'林 健二','71'=>'山岸 文佳','77'=>'鈴木 竜司','79'=>'久保 武雄',
		'80'=>'木村 行博','83'=>'八島 智春','85'=>'池田 昌史','86'=>'吉田 忍',
		'87'=>'孫崎 将幸','88'=>'田中 由浩','89'=>'山田 健司','90'=>'今村 英樹',
		'91'=>'山崎 純也','92'=>'倉 光貴','93'=>'西島 和正','94'=>'才田 勇',
		'95'=>'山田 航','96'=>'湊屋 碧偉','97'=>'山口 裕人',
	);
	$n = (string) $n;
	return isset( $m[ $n ] ) ? $m[ $n ] : '';
}

/** 名前から、新サイトのスタッフを探します（空白のちがいは見ません） */
function ymkrf_imp_staff_id( $name ) {
	static $list = null;
	if ( $name === '' ) return 0;

	if ( $list === null ) {
		$list = array();
		$posts = get_posts( array(
			'post_type' => 'ymkrf_staff', 'posts_per_page' => -1,
			'post_status' => 'any',
		) );
		foreach ( $posts as $p ) {
			$k = preg_replace( '/[\s　]/u', '', $p->post_title );
			$list[ $k ] = $p->ID;
		}
	}
	$k = preg_replace( '/[\s　]/u', '', $name );
	return isset( $list[ $k ] ) ? (int) $list[ $k ] : 0;
}

/** 工事の中身の文字から、新サイトの分類スラッグを決めます */
function ymkrf_imp_cat( $r ) {

	$t = '';
	foreach ( array( 'constructions','description','product_1','product_2',
	                 'product_3','spec','image_alt' ) as $k ) {
		if ( ! empty( $r[ $k ] ) ) $t .= ' ' . $r[ $k ];
	}

	$c = isset( $r['c'] ) ? $r['c'] : '';

	switch ( $c ) {
		case 'kitchen':  return 'kitchen';
		case 'bathroom': return 'bathroom';
		case 'toilet':   return 'toilet';
		case 'lavatory': return 'lavatory';
		case 'painting': return 'outer-wall';
		case 'repair':   return 'repair';
		case 'whole':    return 'renovation';

		case 'boiler':
			if ( preg_match( '/オイルタンク|ｵｲﾙﾀﾝｸ/u', $t ) )                        return 'oiltank';
			if ( preg_match( '/エコキュート|ヒートポンプ|オール電化|エコワン/u', $t ) ) return 'ecocute';
			if ( preg_match( '/IHクッキング|IHヒーター|ＩＨ/u', $t ) )                 return 'ih';
			return 'boiler';

		case 'exterior':
			if ( preg_match( '/物置|イナバ|ヨド|タクボ/u', $t ) )                       return 'storage';
			if ( preg_match( '/カーポート|ｶｰﾎﾟｰﾄ|ガレージ|車庫/u', $t ) )               return 'carport';
			if ( preg_match( '/サンルーム|テラス|ベランダ|バルコニー|波板/u', $t ) )      return 'veranda';
			if ( preg_match( '/玄関ドア|玄関引戸|玄関リフォーム|玄関工事|玄関取替|ドアリモ/u', $t ) ) return 'door';
			if ( preg_match( '/シャッター|ｼｬｯﾀｰ|手すり|手摺|融雪|解けルモ|防草|除草/u', $t ) )        return 'repair';
			return 'other';

		case 'interior':
			if ( preg_match( '/クロス|壁紙|床|フローリング|クッションフロア|畳/u', $t ) ) return 'interior';
			return 'renovation';
	}
	return 'other';
}

/** 現場住所を、市町村の単位にそろえます */
function ymkrf_imp_area( $a ) {

	$t = preg_replace( '/[\s　]/u', '', (string) $a );
	if ( $t === '' ) return '';

	$t = preg_replace( '/^(石川県|福井県|富山県)/u', '', $t );

	/* 郡の名前だけのとき（町がひとつしかない郡は、その町にします） */
	if ( preg_match( '/^鹿島郡$/u', $t ) ) return '中能登町';
	if ( preg_match( '/^(能美郡|能美群)$/u', $t ) ) return '川北町';
	if ( preg_match( '/^丹生郡$/u', $t ) ) return '越前町';
	if ( preg_match( '/^吉田郡$/u', $t ) ) return '永平寺町';
	/* 羽咋郡・鳳珠郡は町が2つ以上あるので決められません → 空にします */
	if ( preg_match( '/^(羽咋郡|鳳珠郡)$/u', $t ) ) return '';

	$t = preg_replace( '/^(鹿島郡|能美郡|能美群|羽咋郡|鳳珠郡|丹生郡|吉田郡)/u', '', $t );

	if ( preg_match( '/^(.+?市)/u', $t, $m ) ) {
		$v = $m[1];
		if ( $v === '野々市' ) $v = '野々市市';
		return $v;
	}
	if ( preg_match( '/^(中能登町|志賀町|宝達志水町|川北町|津幡町|内灘町|穴水町|能登町|越前町|池田町|永平寺町)/u', $t, $m ) ) {
		return $m[1];
	}
	if ( preg_match( '/^(.+?[町村])/u', $t, $m ) ) return $m[1];

	if ( preg_match( '/^野々市/u', $t ) ) return '野々市市';
	if ( preg_match( '/^金津/u',   $t ) ) return 'あわら市';

	return '';
}

/** 写真を1枚もらってきて、メディアに入れます */
function ymkrf_imp_photo( $key, $post_id, $alt = '' ) {

	if ( $key === '' ) return 0;

	/* 同じ写真を二度もらわないように、印をつけておきます */
	$found = get_posts( array(
		'post_type' => 'attachment', 'posts_per_page' => 1, 'fields' => 'ids',
		'post_status' => 'any',
		'meta_query' => array( array( 'key' => '_ymkrf_imp_key', 'value' => $key ) ),
	) );
	if ( $found ) return (int) $found[0];

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$url = YMKRF_IMP_ORIGIN . '/uploads/raw/' . rawurlencode( $key );
	$tmp = download_url( $url, 60 );
	if ( is_wp_error( $tmp ) ) return 0;

	$file = array(
		'name'     => 'ymk-' . strtolower( preg_replace( '/[^0-9A-Za-z]/', '', $key ) ) . '.jpg',
		'tmp_name' => $tmp,
	);
	$id = media_handle_sideload( $file, $post_id, $alt );

	if ( is_wp_error( $id ) ) {
		if ( file_exists( $tmp ) ) @unlink( $tmp );
		return 0;
	}
	update_post_meta( $id, '_ymkrf_imp_key', $key );
	if ( $alt !== '' ) update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	return (int) $id;
}


/* ============================================================
   3. 1件を入れます
   ============================================================ */

function ymkrf_imp_one( $r, $status = 'draft' ) {

	$no = isset( $r['process_num'] ) ? trim( $r['process_num'] ) : '';
	if ( $no === '' ) return array( 'skip', '案件No.がありません' );

	/* すでに入っていたら飛ばします */
	$dup = get_posts( array(
		'post_type' => 'ymkrf_works', 'posts_per_page' => 1, 'fields' => 'ids',
		'post_status' => 'any',
		'meta_query' => array( array( 'key' => '_ymkrf_case_no', 'value' => $no ) ),
	) );
	if ( $dup ) return array( 'skip', '案件No. ' . $no . ' はすでにあります' );

	$body = isset( $r['description'] ) ? $r['description'] : '';

	$id = wp_insert_post( array(
		'post_type'    => 'ymkrf_works',
		'post_status'  => $status,
		'post_title'   => '取り込み中 ' . $no,
		'post_content' => $body,
	), true );
	if ( is_wp_error( $id ) ) return array( 'ng', $id->get_error_message() );

	$put = function ( $k, $v ) use ( $id ) {
		if ( $v !== '' && $v !== null ) update_post_meta( $id, $k, $v );
	};

	$put( '_ymkrf_case_no', $no );
	$put( '_ymkrf_initial', isset( $r['client_name'] )
	      ? preg_replace( '/様$/u', '', trim( $r['client_name'] ) ) : '' );
	$put( '_ymkrf_shop',    ymkrf_imp_shop( isset( $r['shop'] ) ? $r['shop'] : '' ) );
	$put( '_ymkrf_price',   isset( $r['price'] ) ? $r['price'] : '' );
	$put( '_ymkrf_period',  isset( $r['work_period'] ) ? $r['work_period'] : '' );
	$put( '_ymkrf_done',    isset( $r['work_complete'] ) ? $r['work_complete'] : '' );
	$put( '_ymkrf_work_items', isset( $r['constructions'] ) ? $r['constructions'] : '' );
	$put( '_ymkrf_works_comment', isset( $r['point'] ) ? $r['point'] : '' );

	/* 商品名（3つまで）＋その他の部材 */
	$pr = array();
	foreach ( array( 'product_1','product_2','product_3','spec' ) as $k ) {
		if ( ! empty( $r[ $k ] ) ) $pr[] = trim( $r[ $k ] );
	}
	$put( '_ymkrf_product_text', implode( "\n", $pr ) );

	/* 担当者 */
	$sid = ymkrf_imp_staff_id( ymkrf_imp_staff_name( isset( $r['staff'] ) ? $r['staff'] : '' ) );
	if ( $sid ) update_post_meta( $id, '_ymkrf_staff', $sid );

	/* 分類とエリア */
	wp_set_object_terms( $id, ymkrf_imp_cat( $r ), 'ymkrf_works_cat', false );
	$area = ymkrf_imp_area( isset( $r['area'] ) ? $r['area'] : '' );
	if ( $area !== '' ) wp_set_object_terms( $id, $area, 'ymkrf_works_area', false );

	/* 写真 */
	$alt = isset( $r['image_alt'] ) ? $r['image_alt'] : '';
	$ph  = isset( $r['ph'] ) && is_array( $r['ph'] ) ? $r['ph'] : array();

	$grab = function ( $prefix ) use ( $ph, $id, $alt ) {
		$out = array();
		for ( $i = 1; $i <= 5; $i++ ) {
			$k = $prefix . '_photo_' . $i;
			if ( empty( $ph[ $k ] ) ) continue;
			$a = ! empty( $ph[ $k . '_alt' ] ) ? $ph[ $k . '_alt' ] : $alt;
			$att = ymkrf_imp_photo( $ph[ $k ], $id, $a );
			if ( $att ) $out[] = $att;
		}
		return $out;
	};

	$before = $grab( 'before' );
	$after  = $grab( 'after' );
	$during = $grab( 'work' );          // 工事情報写真
	if ( ! $during ) $during = $grab( 'during' );

	if ( $before ) ymkrf_works_photos_save( $id, 'before', $before );
	if ( $during ) ymkrf_works_photos_save( $id, 'during', $during );
	if ( $after )  ymkrf_works_photos_save( $id, 'after',  $after );

	/* 一覧画像（アイキャッチ）。Afterが無いときだけ使います */
	if ( ! $after && ! empty( $r['image'] ) ) {
		$att = ymkrf_imp_photo( $r['image'], $id, $alt );
		if ( $att ) set_post_thumbnail( $id, $att );
	}

	/* 題名とURLを、いまのしくみに合わせて作り直します */
	if ( function_exists( 'ymkrf_works_auto_title' ) ) {
		$t = ymkrf_works_auto_title( $id );
		wp_update_post( array( 'ID' => $id, 'post_title' => $t ) );
		update_post_meta( $id, '_ymkrf_auto_title', $t );
	}

	return array( 'ok', '#' . $id . ' ' . get_the_title( $id )
	              . '（写真 ' . ( count( $before ) + count( $during ) + count( $after ) ) . '枚）' );
}


/* ============================================================
   4. 管理画面
   ============================================================ */

add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=ymkrf_works',
		'本番から取り込み', '本番から取り込み',
		'manage_options', 'ymkrf-works-import', 'ymkrf_imp_page'
	);
}, 30 );

function ymkrf_imp_page() {

	/* ファイルが置いてあれば、そちらを先に読みます。
	   置き場所： wp-content/ymkrf-works.json                       */
	$file = WP_CONTENT_DIR . '/ymkrf-works.json';
	if ( file_exists( $file ) ) {
		$rows = json_decode( (string) file_get_contents( $file ), true );
		if ( isset( $rows['rows'] ) ) $rows = $rows['rows'];
	} else {
		$rows = json_decode( (string) get_option( YMKRF_IMP_OPT, '[]' ), true );
	}
	if ( ! is_array( $rows ) ) $rows = array();
	$pos = (int) get_option( YMKRF_IMP_POS, 0 );
	$log = (array) get_option( YMKRF_IMP_LOG, array() );

	/* 取り込みを進めます */
	if ( isset( $_POST['ymkrf_imp_go'] ) && check_admin_referer( 'ymkrf_imp' ) ) {
		$status = ( isset( $_POST['st'] ) && $_POST['st'] === 'publish' ) ? 'publish' : 'draft';
		$n = 0;
		$t0 = time();
		while ( $pos < count( $rows ) && $n < 20 && ( time() - $t0 ) < 120 ) {
			list( $kind, $msg ) = ymkrf_imp_one( $rows[ $pos ], $status );
			array_unshift( $log, $kind . ' : ' . $msg );
			$pos++; $n++;
		}
		$log = array_slice( $log, 0, 60 );
		update_option( YMKRF_IMP_POS, $pos, false );
		update_option( YMKRF_IMP_LOG, $log, false );
	}

	/* まっさらに戻します（入れた記事は消しません） */
	if ( isset( $_POST['ymkrf_imp_reset'] ) && check_admin_referer( 'ymkrf_imp' ) ) {
		update_option( YMKRF_IMP_POS, 0, false );
		update_option( YMKRF_IMP_LOG, array(), false );
		$pos = 0; $log = array();
	}
	?>
	<div class="wrap">
	  <h1>施工事例を、本番サイトから取り込む</h1>

	  <?php if ( ! $rows ) : ?>
	    <div class="notice notice-warning"><p>
	      まだ中身が届いていません。<br>
	      本番サイトの管理画面から、読み取った中身を送ってください。
	    </p></div>
	  <?php else : ?>
	    <table class="widefat" style="max-width:640px;margin-bottom:16px">
	      <tr><th style="width:12em">届いている件数</th><td><?php echo count( $rows ); ?> 件</td></tr>
	      <tr><th>取り込みずみ</th><td><?php echo (int) $pos; ?> 件</td></tr>
	      <tr><th>のこり</th><td><?php echo max( 0, count( $rows ) - $pos ); ?> 件</td></tr>
	    </table>

	    <form method="post">
	      <?php wp_nonce_field( 'ymkrf_imp' ); ?>
	      <p>
	        <label><input type="radio" name="st" value="draft" checked> 下書きで入れる（おすすめ）</label>
	        <label><input type="radio" name="st" value="publish"> 公開で入れる</label>
	      </p>
	      <p>
	        <button class="button button-primary" name="ymkrf_imp_go" value="1">
	          つづきを20件 取り込む
	        </button>
	        <button class="button" name="ymkrf_imp_reset" value="1"
	          onclick="return confirm('はじめの1件目から数え直します。入れた記事は消えません。よろしいですか？')">
	          はじめから数え直す
	        </button>
	      </p>
	      <p class="description">
	        写真をもらってくるので、20件で1〜2分かかります。<br>
	        案件No.が同じものがすでにあるときは、飛ばします。
	      </p>
	    </form>
	  <?php endif; ?>

	  <?php if ( $log ) : ?>
	    <h2>記録（新しい順）</h2>
	    <ol style="background:#fff;border:1px solid #ccd0d4;padding:12px 12px 12px 32px;
	               max-height:420px;overflow:auto">
	      <?php foreach ( $log as $l ) : ?>
	        <li style="<?php echo strpos( $l, 'ng' ) === 0 ? 'color:#b32d2e' : ''; ?>">
	          <?php echo esc_html( $l ); ?></li>
	      <?php endforeach; ?>
	    </ol>
	  <?php endif; ?>
	</div>
	<?php
}
