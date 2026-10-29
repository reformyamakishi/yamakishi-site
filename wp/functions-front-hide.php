<?php
/**
 * ホームページ側（フロント）に、サンプル・下書き・非公開を出さない（2026/10/29）
 *
 * ユーザー指示「フロントにサンプル、下書き、非公開は出さないで」
 *
 * 1. 下書き・非公開
 *    WordPress は、管理者がログインしていると「非公開」の記事も一覧や記事ページに出します。
 *    ログインしていてもいなくても、ホームページ側は「公開」だけにします。
 *    （お客様には、もともと出ていません。ログイン中の見え方を、お客様と同じにそろえます）
 *    ※「プレビュー」ボタンからの下見は、これまでどおり見られます。
 * 2. サンプル
 *    題名が「【サンプル】」で始まる記事（コラム・施工事例・お客様の声・商品など、種類を問わず）は、
 *    公開になっていても、一覧にも記事ページにも出しません（記事ページは「見つかりません」）。
 *    ダッシュボードには、これまでどおり出ます。消してはいません。
 *
 * ダッシュボード（管理画面）・プレビュー・REST・cron には何もしません。
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** サンプルの記事ID（題名が【サンプル】で始まるもの）。1回の表示につき1回だけ調べます */
function ymkrf_sample_ids() {
	static $ids = null;
	if ( $ids !== null ) return $ids;
	global $wpdb;
	$ids = array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts} WHERE post_title LIKE %s AND post_type NOT IN ('revision','nav_menu_item')",
		$wpdb->esc_like( '【サンプル】' ) . '%' ) ) );
	return $ids;
}

/** いまフロントの表示中か（ダッシュボード・REST・cron・プレビューはのぞく） */
function ymkrf_is_front_view( $q = null ) {
	if ( is_admin() || wp_doing_cron() ) return false;
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) return false;
	if ( defined( 'WP_CLI' ) && WP_CLI ) return false;
	if ( $q && $q->is_preview() ) return false;
	if ( isset( $_GET['preview'] ) ) return false;
	return true;
}

add_action( 'pre_get_posts', function ( $q ) {
	if ( ! ymkrf_is_front_view( $q ) ) return;

	$pt = $q->get( 'post_type' );
	if ( $pt === 'attachment' || $pt === 'nav_menu_item' || $q->is_attachment() ) return;

	/* 1. 下書き・非公開：何も決めていない問い合わせは「公開」だけに */
	$st = $q->get( 'post_status' );
	if ( $st === '' || $st === array() || $st === 'any' ) $q->set( 'post_status', 'publish' );

	/* 2. サンプルを外します */
	$skip = ymkrf_sample_ids();
	if ( $skip ) {
		$not = array_map( 'intval', (array) $q->get( 'post__not_in' ) );
		$q->set( 'post__not_in', array_values( array_unique( array_merge( $not, $skip ) ) ) );
	}
}, 5 );

/* 念のため：記事ページを直接ひらかれても、サンプル・下書き・非公開は「見つかりません」にします */
add_action( 'template_redirect', function () {
	if ( ! ymkrf_is_front_view() || ! is_singular() ) return;
	$p = get_queried_object();
	if ( ! $p || ! isset( $p->post_status ) ) return;
	$sample = isset( $p->post_title ) && strpos( $p->post_title, '【サンプル】' ) === 0;
	if ( $p->post_status === 'publish' && ! $sample ) return;
	if ( $p->post_type === 'attachment' ) return;
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}, 0 );
