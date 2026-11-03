<?php
/**
 * 内装・改装（床・窓・断熱）の商品を、旧サイトの内容で足す（2026/11/03）
 *
 * ユーザー「床、窓、断熱のページ 内装・改装のページを、旧サイトから読み取って完成させて。
 *           https://yamakishi-reform.jp/products/interior/」
 *         「内容は同じで良いがシンプルで見やすくお洒落にして」
 *         価格 →「価格を空にして下書きで登録」（旧サイトの価格と、登録済み6商品の価格がちがうため）
 *
 * ■ ここでやること（どれも1回だけ）
 *   1. 登録済みの内装・改装の商品で、価格が「商品代」に入っているものを「価格（＝標準工事費の欄）」へ移す
 *      （内装・改装の登録画面には「価格（材料費・工事費込み）」の欄ひとつだけを出すため。込み価格は変わりません）
 *   2. 旧サイトにあって、まだ無い商品を「下書き・価格なし」で作る
 *      → 商品 ＞ 内装・改装 で価格を入れて「公開」すると、ページに出ます
 *   3. 旧サイトの写真を、作った商品のアイキャッチに入れる（ダッシュボードから1回だけ実行）
 * 登録済みの6商品の価格・文章・写真には、さわりません。
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** 足す商品（旧サイトの内容。価格は入れません） */
function ymkrf_interior_add_list() {
	$wall  = array( array( 'ttl' => '工事の中身', 'body' => "既存壁紙はがし\n下地処理\n壁紙張り\n既存壁紙処分" ) );
	$cf    = array( array( 'ttl' => '工事の中身', 'body' => "既存フロア剥がし\n下地処理\nフロア張り\n既存フロア処分" ) );
	$tcf   = array( array( 'ttl' => '工事の中身', 'body' => "既存床解体\n下地作成\nフロア張り\n既存畳・床材処分" ) );
	$wa    = array( array( 'ttl' => '工事の中身', 'body' => "畳 → フローリング\n押入れ → クローゼット ※\n天井：クロス貼り\n壁：耐火ボード貼り＋クロス貼り\nふすま → ドア" ) );
	$yo    = array( array( 'ttl' => '工事の中身', 'body' => "フローリング重ね貼り\n天井：クロス貼り\n壁：クロス貼り" ) );
	$inp   = function ( $size ) { return array(
		array( 'ttl' => '工事の中身', 'body' => '内窓の取り付け' ),
		array( 'ttl' => '窓の大きさ（引き違い2枚建て・Low-E複層ガラス）', 'body' => $size ),
	); };
	$aqua  = array( array( 'ttl' => '工事の中身', 'body' => '天井・床下への吹付け（厚み75mm）' ) );

	$pack  = '水まわりのパックと同時に工事すると、お得になります。ご相談ください。';
	$d_wall = "各種柄も取り揃えております。くわしくはご相談ください。\n" . $pack;
	$d_cf   = "塩化ビニル樹脂を主成分とする、意匠性・機能性の高い床材です。木目調・石目調など柄が豊富です。\n" . $pack;
	$d_inp  = "今ある窓に“プラス”するだけで、あっという間に快適な暮らしに。インプラスを取り付けると、今の窓とのあいだに空気の層が生まれます。これが壁の役割をして、断熱効果や防音効果を生み出します。\n"
	        . "※取付工事 1枚 8,800円（税込）〜\n※ふかし枠が必要な場合は別途";
	$d_aqua = "家具を動かさずに、天井と床下の断熱リフォーム。天井や床下へ泡状のフォームを吹き付けて、すき間なく施工します。高気密・高断熱で冷暖房の効率も上がり、夏も冬も快適です。家具の移動は不要です。\n"
	        . "上記の面積より大きい場合は、そのつどお見積りいたします。";

	return array(
		array( 'name' => 'クロス張替え（6帖）',                 'g' => 'クロスリフォーム', 'o' => 1,  'days' => '1',     'spec' => $wall, 'desc' => $d_wall, 'img' => 'cloth' ),
		array( 'name' => 'クロス張替え（8帖）',                 'g' => 'クロスリフォーム', 'o' => 2,  'days' => '1',     'spec' => $wall, 'desc' => '',      'img' => 'cloth' ),
		array( 'name' => 'クッションフロア → クッションフロア（6帖）', 'g' => '床リフォーム', 'o' => 5, 'days' => '半日', 'spec' => $cf,   'desc' => $d_cf,  'img' => 'cf' ),
		array( 'name' => 'クッションフロア → クッションフロア（8帖）', 'g' => '床リフォーム', 'o' => 6, 'days' => '半日', 'spec' => $cf,   'desc' => '',     'img' => 'cf' ),
		array( 'name' => '畳 → クッションフロア（6帖）',        'g' => '床リフォーム',     'o' => 7,  'days' => '1',     'spec' => $tcf,  'desc' => $d_cf,  'img' => 'cf' ),
		array( 'name' => '畳 → クッションフロア（8帖）',        'g' => '床リフォーム',     'o' => 8,  'days' => '1',     'spec' => $tcf,  'desc' => '',     'img' => 'cf' ),
		array( 'name' => '和室 → 洋室リフォーム（8帖）',        'g' => '部屋リフォーム',   'o' => 51, 'days' => '',      'spec' => $wa,   'desc' => '※クローゼット、ドアの仕様により値段は変わります', 'img' => '' ),
		array( 'name' => '洋室リフレッシュ（8帖）',             'g' => '部屋リフォーム',   'o' => 61, 'days' => '',      'spec' => $yo,   'desc' => '',     'img' => '' ),
		array( 'name' => '内窓インプラス（小窓）',              'g' => '窓リフォーム',     'o' => 70, 'days' => '',      'spec' => $inp( '小窓：高さ90cm × 幅170cm' ),  'desc' => $d_inp, 'img' => 'inplus' ),
		array( 'name' => '内窓インプラス（中窓）',              'g' => '窓リフォーム',     'o' => 71, 'days' => '',      'spec' => $inp( '中窓：高さ110cm × 幅170cm' ), 'desc' => '',     'img' => 'inplus' ),
		array( 'name' => '内窓インプラス（大窓）',              'g' => '窓リフォーム',     'o' => 72, 'days' => '',      'spec' => $inp( '大窓：高さ200cm × 幅170cm' ), 'desc' => '',     'img' => 'inplus' ),
		array( 'name' => 'アクアフォーム（50㎡以下）',          'g' => '断熱リフォーム',   'o' => 80, 'days' => '最短1日', 'spec' => $aqua, 'desc' => $d_aqua, 'img' => 'aqua' ),
		array( 'name' => 'アクアフォーム（80㎡以下）',          'g' => '断熱リフォーム',   'o' => 81, 'days' => '最短1日', 'spec' => $aqua, 'desc' => '',     'img' => 'aqua' ),
		array( 'name' => 'アクアフォーム（120㎡以下）',         'g' => '断熱リフォーム',   'o' => 82, 'days' => '最短1日', 'spec' => $aqua, 'desc' => '',     'img' => 'aqua' ),
	);
}

add_action( 'admin_init', function () {
	if ( get_option( 'ymkrf_interior_add_v1' ) === '1' || ! current_user_can( 'manage_options' ) ) return;
	$term = get_term_by( 'slug', 'interior', 'ymkrf_product_cat' );
	if ( ! $term || is_wp_error( $term ) ) return;

	/* 1. 価格を「価格（標準工事費の欄）」へ移す（込み価格は同じ） */
	$ids = get_posts( array(
		'post_type' => 'ymkrf_product', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids',
		'tax_query' => array( array( 'taxonomy' => 'ymkrf_product_cat', 'field' => 'term_id', 'terms' => (int) $term->term_id ) ),
	) );
	foreach ( $ids as $id ) {
		$w = (int) get_post_meta( $id, '_ymkrf_work', true );
		$i = (int) get_post_meta( $id, '_ymkrf_item', true );
		if ( ! $w && $i ) {
			update_post_meta( $id, '_ymkrf_work', $i );
			update_post_meta( $id, '_ymkrf_item', '' );
			update_post_meta( $id, '_ymkrf_total', $i );
		}
	}

	/* 2. 足りない商品を下書きで作る（同じ名前があれば作りません） */
	$made = array();
	foreach ( ymkrf_interior_add_list() as $p ) {
		$dup = get_posts( array( 'post_type' => 'ymkrf_product', 'post_status' => 'any', 'title' => $p['name'], 'numberposts' => 1, 'fields' => 'ids' ) );
		if ( $dup ) continue;
		$id = wp_insert_post( array(
			'post_type' => 'ymkrf_product', 'post_title' => $p['name'], 'post_status' => 'draft', 'menu_order' => (int) $p['o'],
		) );
		if ( ! $id || is_wp_error( $id ) ) continue;
		wp_set_object_terms( $id, array( (int) $term->term_id ), 'ymkrf_product_cat' );
		update_post_meta( $id, '_ymkrf_catch', $p['g'] );
		update_post_meta( $id, '_ymkrf_order', (int) $p['o'] );
		update_post_meta( $id, '_ymkrf_work', '' );
		update_post_meta( $id, '_ymkrf_item', '' );
		update_post_meta( $id, '_ymkrf_total', 0 );
		update_post_meta( $id, '_ymkrf_speclist', $p['spec'] );
		if ( $p['desc'] !== '' ) update_post_meta( $id, '_ymkrf_desc', $p['desc'] );
		if ( preg_match( '/^[0-9]+$/', $p['days'] ) ) { update_post_meta( $id, '_ymkrf_days', (int) $p['days'] ); update_post_meta( $id, '_ymkrf_daystext', '' ); }
		elseif ( $p['days'] !== '' ) { update_post_meta( $id, '_ymkrf_days', '' ); update_post_meta( $id, '_ymkrf_daystext', $p['days'] ); }
		update_post_meta( $id, '_ymkrf_interior_img', $p['img'] );
		$made[] = $p['name'];
	}
	update_option( 'ymkrf_interior_add_v1', '1', false );
	if ( $made ) set_transient( 'ymkrf_interior_add_made', $made, 300 );
}, 20 );

add_action( 'admin_notices', function () {
	$made = get_transient( 'ymkrf_interior_add_made' );
	if ( ! $made ) return;
	delete_transient( 'ymkrf_interior_add_made' );
	echo '<div class="notice notice-success is-dismissible"><p>内装・改装に、旧サイトの商品を <b>' . count( (array) $made )
	   . '件</b> 下書きで足しました（価格は空）。<b>商品 ＞ 内装・改装</b> で価格を入れて「公開」すると、ページに出ます。</p></div>';
} );

/* 3. 旧サイトの写真をアイキャッチに入れる（ダッシュボードから1回だけ。admin-ajax.php?action=ymkrf_interior_imgs） */
add_action( 'wp_ajax_ymkrf_interior_imgs', function () {
	if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'no' );
	check_ajax_referer( 'ymkrf_interior_imgs' );
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$src = array(
		'cloth'  => array( 'https://yamakishi-reform.jp/assets-202103/img/static/interior-cloth1.jpg', 'interior-wallpaper-cloth-replacement', '天井・壁のクロス張替え' ),
		'cf'     => array( 'https://yamakishi-reform.jp/assets-202103/img/static/interior-floor1.jpg', 'interior-cushion-floor-replacement',  'クッションフロアの張替え' ),
		'inplus' => array( isset( $_POST['inplus'] ) ? esc_url_raw( wp_unslash( $_POST['inplus'] ) ) : '', 'interior-inner-window-inplus', '内窓インプラスの取り付け' ),
		'aqua'   => array( isset( $_POST['aqua'] ) ? esc_url_raw( wp_unslash( $_POST['aqua'] ) ) : '',   'interior-insulation-aquafoam',  'アクアフォームの吹付け断熱' ),
	);
	$done = get_option( 'ymkrf_interior_imgs', array() );
	if ( ! is_array( $done ) ) $done = array();
	$out = array();
	foreach ( $src as $k => $s ) {
		if ( ! empty( $done[ $k ] ) && get_post( $done[ $k ] ) ) { $out[ $k ] = 'あり ' . $done[ $k ]; continue; }
		if ( $s[0] === '' || strpos( $s[0], 'https://yamakishi-reform.jp/' ) !== 0 ) { $out[ $k ] = 'URLなし'; continue; }
		$tmp = download_url( $s[0], 30 );
		if ( is_wp_error( $tmp ) ) { $out[ $k ] = 'NG ' . $tmp->get_error_message(); continue; }
		$mime = wp_get_image_mime( $tmp );
		$ext  = array( 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif' );
		if ( empty( $ext[ $mime ] ) ) { @unlink( $tmp ); $out[ $k ] = 'NG 画像ではない'; continue; }
		$aid = media_handle_sideload( array( 'name' => $s[1] . '.' . $ext[ $mime ], 'tmp_name' => $tmp ), 0, $s[2] );
		if ( is_wp_error( $aid ) ) { @unlink( $tmp ); $out[ $k ] = 'NG ' . $aid->get_error_message(); continue; }
		update_post_meta( $aid, '_wp_attachment_image_alt', $s[2] );
		$done[ $k ] = (int) $aid;
		$out[ $k ]  = 'OK ' . $aid;
	}
	update_option( 'ymkrf_interior_imgs', $done, false );
	/* 作った商品のアイキャッチに入れる（すでに入っているものは、さわりません） */
	$set = 0;
	foreach ( get_posts( array( 'post_type' => 'ymkrf_product', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids',
	                            'meta_key' => '_ymkrf_interior_img' ) ) as $pid ) {
		$k = (string) get_post_meta( $pid, '_ymkrf_interior_img', true );
		if ( $k === '' || empty( $done[ $k ] ) || has_post_thumbnail( $pid ) ) continue;
		set_post_thumbnail( $pid, (int) $done[ $k ] );
		if ( ! wp_get_post_parent_id( (int) $done[ $k ] ) ) wp_update_post( array( 'ID' => (int) $done[ $k ], 'post_parent' => $pid ) );   /* メディアのフォルダ「商品」に入るように */
		$set++;
	}
	$out['set'] = $set;
	wp_send_json_success( $out );
} );

/* 写真の取りこみ用の合言葉（nonce）を、内装・改装の一覧にだけ置きます（取りこみが済んだら出しません） */
add_action( 'admin_footer-edit.php', function () {
	if ( ! current_user_can( 'manage_options' ) ) return;
	if ( empty( $_GET['ymkrf_product_cat'] ) || $_GET['ymkrf_product_cat'] !== 'interior' ) return;
	$done = get_option( 'ymkrf_interior_imgs', array() );
	if ( is_array( $done ) && count( $done ) >= 4 ) return;
	echo '<script>window.ymkrfInteriorImgsNonce=' . wp_json_encode( wp_create_nonce( 'ymkrf_interior_imgs' ) ) . ';</script>';
} );

/* 内窓インプラスの大きさは、料金表の行の名前に入れます（カードの「工事の中身」には1つしか出ないため。2026/11/03） */
add_action( 'admin_init', function () {
	if ( get_option( 'ymkrf_interior_add_v2' ) === '1' || ! current_user_can( 'manage_options' ) ) return;
	$map = array(
		'内窓インプラス（小窓）' => '内窓インプラス（小窓 高さ90×幅170cm）',
		'内窓インプラス（中窓）' => '内窓インプラス（中窓 高さ110×幅170cm）',
		'内窓インプラス（大窓）' => '内窓インプラス（大窓 高さ200×幅170cm）',
	);
	foreach ( $map as $old => $new ) {
		$ids = get_posts( array( 'post_type' => 'ymkrf_product', 'post_status' => 'any', 'title' => $old, 'numberposts' => 1, 'fields' => 'ids' ) );
		if ( ! $ids ) continue;
		wp_update_post( array( 'ID' => $ids[0], 'post_title' => $new ) );
		update_post_meta( $ids[0], '_ymkrf_speclist', array(
			array( 'ttl' => '工事の中身', 'body' => "内窓の取り付け（引き違い2枚建て・Low-E複層ガラス）" ),
		) );
	}
	update_option( 'ymkrf_interior_add_v2', '1', false );
}, 21 );
