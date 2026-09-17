<?php
/**
 * functions-voice-debug.php ─ ★一時的な調べもの用★
 * 置き場所： wp-content/themes/ymkrf/inc/functions-voice-debug.php
 *
 * （2026/09/17 「状態を公開にしても、自動で非公開になる」の原因さがし）
 *
 * お客様の声を保存したときに、何が起きたかを
 *   wp-content/ymkrf-debug.txt
 * に書き出します。原因がわかったら、この行とファイルを消してください。
 *
 * ※ 画面の見た目も動きも、いっさい変えません。記録するだけです。
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** 1行書きます */
function ymkrf_dbg( $line ) {
	$dir = dirname( wp_upload_dir()['basedir'] );
	@file_put_contents( $dir . '/ymkrf-debug.txt',
		current_time( 'H:i:s' ) . '  ' . $line . "\n", FILE_APPEND );
}

/* 保存のはじめに、画面から送られてきたものを記録します */
add_action( 'save_post_ymkrf_voice', function ( $post_id ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;

	ymkrf_dbg( str_repeat( '=', 60 ) );
	ymkrf_dbg( "保存がはじまりました  投稿ID={$post_id}" );
	ymkrf_dbg( '  いまの状態         : ' . get_post_status( $post_id ) );
	ymkrf_dbg( '  ymkrf_vstatus      : ' .
		( isset( $_POST['ymkrf_vstatus'] ) ? $_POST['ymkrf_vstatus'] : '（送られていません）' ) );
	ymkrf_dbg( '  ymkrf_vstatus_nonce: ' .
		( isset( $_POST['ymkrf_vstatus_nonce'] )
			? ( wp_verify_nonce( $_POST['ymkrf_vstatus_nonce'], 'ymkrf_vstatus_save' ) ? 'OK' : '合いません' )
			: '（送られていません）' ) );
	ymkrf_dbg( '  post_status(POST)  : ' .
		( isset( $_POST['post_status'] ) ? $_POST['post_status'] : '（なし）' ) );
	ymkrf_dbg( '  visibility(POST)   : ' .
		( isset( $_POST['visibility'] ) ? $_POST['visibility'] : '（なし）' ) );
	ymkrf_dbg( '  save(POST)         : ' .
		( isset( $_POST['save'] ) ? '「' . $_POST['save'] . '」' : '（なし）' ) );
	ymkrf_dbg( '  _ymkrf_claim(POST) : ' .
		( isset( $_POST['_ymkrf_claim'] ) ? '「' . $_POST['_ymkrf_claim'] . '」' : '（なし）' ) );
	ymkrf_dbg( '  クレームの印(DB)   : ' .
		( get_post_meta( $post_id, '_ymkrf_claim', true ) === '1' ? '有' : '無' ) );
}, 1 );

/* とちゅうの状態を、いくつかの場所で記録します */
foreach ( array( 45, 65, 75, 998 ) as $ymkrf_dbg_pri ) {
	add_action( 'save_post_ymkrf_voice', function ( $post_id ) use ( $ymkrf_dbg_pri ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( wp_is_post_revision( $post_id ) ) return;
		ymkrf_dbg( "  優先度{$ymkrf_dbg_pri}のあと    : " . get_post_status( $post_id ) );
	}, $ymkrf_dbg_pri );
}

/* いちばん最後（表示の直前）に、データベースの本当の値を記録します */
add_action( 'shutdown', function () {

	if ( ! isset( $GLOBALS['ymkrf_dbg_id'] ) ) return;

	global $wpdb;
	$id = (int) $GLOBALS['ymkrf_dbg_id'];
	$st = $wpdb->get_var( $wpdb->prepare(
		"SELECT post_status FROM {$wpdb->posts} WHERE ID = %d", $id ) );

	ymkrf_dbg( '  ★さいごのデータベース: ' . $st );
}, 9999 );

add_action( 'save_post_ymkrf_voice', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;
	$GLOBALS['ymkrf_dbg_id'] = $post_id;
}, 1 );

/* 状態を書きかえた犯人をつかまえます */
add_filter( 'wp_insert_post_data', function ( $data, $postarr ) {

	if ( empty( $data['post_type'] ) || $data['post_type'] !== 'ymkrf_voice' ) return $data;

	$who = '';
	foreach ( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 25 ) as $t ) {
		if ( ! empty( $t['file'] ) && strpos( $t['file'], 'themes' ) !== false ) {
			$who = basename( $t['file'] ) . ':' . ( $t['line'] ?? '' );
			break;
		}
	}
	ymkrf_dbg( '  状態を書こうとしています: ' . $data['post_status']
	         . ( $who !== '' ? '  ← ' . $who : '  ← WordPress本体' ) );

	return $data;
}, 9999, 2 );
