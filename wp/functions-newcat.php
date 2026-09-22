<?php
/**
 * functions-newcat.php ─ 新しい商品カテゴリを用意します
 * 置き場所： wp-content/themes/ymkrf/inc/functions-newcat.php
 *
 * （2026/09/22 ユーザー指示
 *   「IHとコンロ／物置／カーポート／テラスなどのエクステリア系／フェンス／
 *     窓リフォームの商品登録ページを作ってほしい」
 *   「カーポート、物置、サンルームなどのエクステリアは種類が少ないので、
 *     1ページに集約しても良いかも」→ 外まわりは1ページにまとめました）
 *
 * ■ 作る分類は3つです
 *
 *   ih        IH・コンロ    … キッチンなどと同じ「商品を1つずつ登録する」形
 *   exterior  エクステリア  … カーポート・物置・サンルーム・フェンスを1ページに
 *   window    窓リフォーム  … 補助金の案内を主役にした1ページ
 *
 *   エクステリアと窓リフォームは、外壁・屋根と同じ「1枚ページ」です。
 *   中身は 商品 ＞ ページの中身 から直せます（inc/functions-lp.php）。
 *
 * ■ 一度だけ作ります
 *   すでに同じスラッグの分類があるときは、なにもしません。
 *   作ったかどうかは ymkrf_newcat_done に控えます。
 */
if ( ! defined( 'ABSPATH' ) ) exit;


/** 新しく足す分類（スラッグ => 名前・説明・並び順） */
function ymkrf_newcat_list() {
	return array(
		'cooktop' => array(
			'name' => 'コンロ・IH',
			'desc' => 'ビルトインコンロとIHクッキングヒーター。取り外し・処分・取り付けまで込みの価格でご案内します。',
			'ord'  => 70,
		),
		'exterior' => array(
			'name' => 'エクステリア',
			'desc' => 'カーポート・物置・サンルーム（テラス）・フェンス。お家の外まわりのリフォームです。',
			'ord'  => 80,
		),
		'window' => array(
			'name' => '窓リフォーム',
			'desc' => '内窓を付けて、寒さ・暑さ・音・結露を軽くします。国の補助金が使えます。',
			'ord'  => 90,
		),
	);
}

/** 1ページにまとめる分類（商品を1つずつ登録しないもの） */
function ymkrf_newcat_lp() {
	return array( 'exterior', 'window' );
}

/* さきに作ってしまった「IH・コンロ（ih）」を「コンロ・IH（cooktop）」に付けかえます。
   ガスコンロもふくむ分類なので、URLに ih が入るのはおかしい、という理由です
   （2026/09/22 ユーザー指摘「ガスコンロはIHではないのに、URLはIHってつくよね？」）。
   一度だけ動きます。 */
add_action( 'admin_init', function () {

	if ( get_option( 'ymkrf_cooktop_done' ) === '1' ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;
	if ( ! taxonomy_exists( 'ymkrf_product_cat' ) ) return;

	$t = get_term_by( 'slug', 'ih', 'ymkrf_product_cat' );
	if ( $t && ! is_wp_error( $t ) && ! get_term_by( 'slug', 'cooktop', 'ymkrf_product_cat' ) ) {
		wp_update_term( (int) $t->term_id, 'ymkrf_product_cat', array(
			'name' => 'コンロ・IH',
			'slug' => 'cooktop',
		) );
	}
	update_option( 'ymkrf_cooktop_done', '1' );
}, 9 );


add_action( 'admin_init', function () {

	if ( get_option( 'ymkrf_newcat_done' ) === '2026-09-22' ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;
	if ( ! taxonomy_exists( 'ymkrf_product_cat' ) ) return;

	foreach ( ymkrf_newcat_list() as $slug => $c ) {

		$t = get_term_by( 'slug', $slug, 'ymkrf_product_cat' );
		if ( $t && ! is_wp_error( $t ) ) continue;

		$r = wp_insert_term( $c['name'], 'ymkrf_product_cat', array(
			'slug'        => $slug,
			'description' => $c['desc'],
		) );
		if ( is_wp_error( $r ) || empty( $r['term_id'] ) ) continue;

		update_term_meta( (int) $r['term_id'], 'ymkrf_order', (int) $c['ord'] );
	}

	update_option( 'ymkrf_newcat_done', '2026-09-22' );
} );
