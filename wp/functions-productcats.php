<?php
/**
 * 商品の大分類を、トップページの「リフォームメニュー」と同じにする（2026/11/03）
 *
 * ユーザー「内装改装の中に床（フローリング）や断熱（アクアフォーム）が入っているが、大分類があるのにお…」
 *         →「大分類へ移す」
 *         「商品の大分類は、トップページのリフォームメニューと同じにして」
 *
 * トップページのメニュー（front-page.php の #menu）と、施工事例の分類（functions-works.php）と、同じ英字・名前にそろえます。
 *   kitchen キッチン／bathroom お風呂／toilet トイレ／lavatory 洗面化粧台／boiler 給湯器／oiltank オイルタンク／
 *   ecocute エコキュート／cooktop IH・ガスコンロ／ventilation 換気扇・レンジフード／interior クロス・床／
 *   renovation 内装・改装／window 窓・断熱／door ドア／exterior エクステリア／carport カーポート／storage 物置／
 *   fence フェンス・門まわり／outer-wall 外壁・屋根／repair 修理・小工事／demolition 解体
 *   （水まわり4点セット pack4 は、そのまま残します。トップの「その他」は分類にしません）
 *
 * ■ 1回だけやること
 *   1. 名前を変える：interior 内装・改装 → クロス・床／window 窓・玄関ドア → 窓・断熱／cooktop コンロ・IH → IH・ガスコンロ
 *   2. 足りない分類を作る：oiltank・ventilation・renovation・door・carport・storage・fence・repair・demolition
 *   3. 商品を移す：和室 → 洋室リフォーム・洋室リフレッシュ → 内装・改装（renovation）
 *                  内窓インプラス・アクアフォーム → 窓・断熱（window）
 *                  （クロス・クッションフロア・フローリングは クロス・床＝interior のまま）
 *   4. コラムの画面で作られていた「床」「断熱」「窓・内窓」（日本語のスラッグ）は、
 *      付いていたコラムを クロス・床／窓・断熱 に付けかえてから、空になった分類を消します
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_init', function () {
	if ( get_option( 'ymkrf_cats_menu_v1' ) === '1' || ! current_user_can( 'manage_options' ) ) return;
	if ( ! taxonomy_exists( 'ymkrf_product_cat' ) ) return;
	$tx  = 'ymkrf_product_cat';
	$log = array();

	/* 1. 名前を変える（新しい分類と名前がぶつからないよう、先に） */
	foreach ( array( 'interior' => 'クロス・床', 'window' => '窓・断熱', 'cooktop' => 'IH・ガスコンロ' ) as $slug => $name ) {
		$t = get_term_by( 'slug', $slug, $tx );
		if ( $t && $t->name !== $name ) {
			$r = wp_update_term( $t->term_id, $tx, array( 'name' => $name ) );
			if ( ! is_wp_error( $r ) ) $log[] = '名前：' . $t->name . ' → ' . $name;
		}
	}

	/* 2. 足りない分類を作る */
	$want = array(
		'oiltank' => 'オイルタンク', 'ventilation' => '換気扇・レンジフード', 'renovation' => '内装・改装',
		'door' => 'ドア', 'carport' => 'カーポート', 'storage' => '物置', 'fence' => 'フェンス・門まわり',
		'repair' => '修理・小工事', 'demolition' => '解体',
	);
	foreach ( $want as $slug => $name ) {
		if ( term_exists( $slug, $tx ) ) continue;
		$r = wp_insert_term( $name, $tx, array( 'slug' => $slug ) );
		if ( ! is_wp_error( $r ) ) $log[] = '作成：' . $name;
	}

	/* 3. 商品を移す */
	$move = array( 'renovation' => array( '和室 → 洋室リフォーム', '洋室リフレッシュ' ), 'window' => array( '内窓インプラス', 'アクアフォーム' ) );
	$from = get_term_by( 'slug', 'interior', $tx );
	if ( $from ) {
		$ids = get_posts( array( 'post_type' => 'ymkrf_product', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids',
			'tax_query' => array( array( 'taxonomy' => $tx, 'field' => 'term_id', 'terms' => (int) $from->term_id ) ) ) );
		foreach ( $ids as $id ) {
			$title = get_the_title( $id );
			foreach ( $move as $to => $heads ) {
				foreach ( $heads as $head ) {
					if ( mb_strpos( $title, $head ) !== 0 ) continue;
					$tt = get_term_by( 'slug', $to, $tx );
					if ( $tt ) { wp_set_object_terms( $id, array( (int) $tt->term_id ), $tx ); $log[] = '移動：' . $title . ' → ' . $tt->name; }
					continue 3;
				}
			}
		}
	}

	/* 4. 日本語スラッグの「床」「断熱」「窓・内窓」をまとめる */
	$merge = array( '床' => 'interior', '断熱' => 'window', '窓・内窓' => 'window' );
	foreach ( $merge as $jname => $to ) {
		$jt = get_term_by( 'name', $jname, $tx );
		$tt = get_term_by( 'slug', $to, $tx );
		if ( ! $jt || ! $tt || in_array( $jt->slug, array( 'interior', 'window', 'renovation' ), true ) ) continue;
		$objs = get_objects_in_term( (int) $jt->term_id, $tx );
		if ( is_wp_error( $objs ) ) continue;
		foreach ( $objs as $oid ) {
			wp_add_object_terms( (int) $oid, array( (int) $tt->term_id ), $tx );
			wp_remove_object_terms( (int) $oid, array( (int) $jt->term_id ), $tx );
		}
		$left = get_objects_in_term( (int) $jt->term_id, $tx );
		if ( ! is_wp_error( $left ) && ! $left ) {
			wp_delete_term( (int) $jt->term_id, $tx );
			$log[] = 'まとめ：' . $jname . ' → ' . $tt->name . '（' . count( $objs ) . '件）';
		}
	}

	update_option( 'ymkrf_cats_menu_v1', '1', false );
	update_option( 'ymkrf_cats_menu_log', $log, false );
	if ( $log ) set_transient( 'ymkrf_cats_menu_made', $log, 300 );
}, 7 );

add_action( 'admin_notices', function () {
	$log = get_transient( 'ymkrf_cats_menu_made' );
	if ( ! $log ) return;
	delete_transient( 'ymkrf_cats_menu_made' );
	echo '<div class="notice notice-success is-dismissible"><p><b>商品の大分類を、トップページのリフォームメニューと同じにしました。</b><br>'
	   . implode( '<br>', array_map( 'esc_html', (array) $log ) ) . '</p></div>';
} );

/* 「その他」を足します（2026/11/03 ユーザー「商品の大カテゴリ、その他をつくっておいて」） */
add_action( 'admin_init', function () {
	if ( get_option( 'ymkrf_cats_menu_v2' ) === '1' || ! current_user_can( 'manage_options' ) ) return;
	if ( ! taxonomy_exists( 'ymkrf_product_cat' ) ) return;
	if ( ! term_exists( 'other', 'ymkrf_product_cat' ) ) wp_insert_term( 'その他', 'ymkrf_product_cat', array( 'slug' => 'other' ) );
	update_option( 'ymkrf_cats_menu_v2', '1', false );
}, 7 );

/**
 * トップページの「リフォームメニュー」のリンク先（2026/11/03 ユーザー「トップページのリフォームメニュー、URLリンクさせて」）
 * 商品がまだ無い分類でも、その分類のページ（/products/〇〇/）へ直接つなぎます。
 * （ymkrf_cat_url() は、商品が0件だと商品一覧へ回すので、メニューではこちらを使います）
 * 分類そのものが無いときだけ、商品一覧へ。
 */
function ymkrf_menu_cat_link( $slug ) {
	if ( $slug === 'pack4' && function_exists( 'ymkrf_cat_url' ) ) return ymkrf_cat_url( 'pack4' );
	$t = get_term_by( 'slug', $slug, 'ymkrf_product_cat' );
	if ( $t && ! is_wp_error( $t ) ) {
		$u = get_term_link( $t );
		if ( ! is_wp_error( $u ) ) return $u;
	}
	return function_exists( 'ymkrf_products_url' ) ? ymkrf_products_url() : home_url( '/products/' );
}

/**
 * 商品の大分類の並び順（ダッシュボードの「商品 ＞ カテゴリからえらぶ」と同じ。2026/11/03）
 * 水まわり4点セットがいちばん上、その他がいちばん下。メディアのフォルダ分けでも使います。
 */
function ymkrf_product_cat_order_list() {
	return array( 'pack4', 'kitchen', 'bathroom', 'toilet', 'lavatory', 'boiler', 'oiltank', 'ecocute', 'cooktop',
	              'ventilation', 'interior', 'renovation', 'window', 'door', 'exterior', 'carport', 'storage',
	              'fence', 'outer-wall', 'repair', 'demolition', 'other' );
}
