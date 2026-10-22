<?php
/**
 * functions-product-memo.php ─ 商品ページの「管理者メモ」（お客様には見えません）
 *
 * 置き場所： wp-content/themes/ymkrf/inc/functions-product-memo.php
 *
 * （2026/10/22 ユーザー指示「商品ぺージ全てに、写真の注意書きの下に、
 *   管理者しか見えない様（観覧するがわからはみえない）メモの追加してほしい」）
 *
 * ・保存先は _ymkrf_admin_memo（商品ごと）
 * ・サイト（フロント）には一切出しません。HTMLの中にも入れないので、F12（開発者ツール）でも見えません
 * ・登録画面では「商品データ（基本）」の「写真の注意書き」のすぐ下に欄があります（同じメモです）
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'YMKRF_MEMO_KEY', '_ymkrf_admin_memo' );

/** 見てよい人か（その商品を編集できる人だけ） */
function ymkrf_product_memo_can( $post_id ) {
	return is_user_logged_in() && current_user_can( 'edit_post', (int) $post_id );
}

/* ============================================================
   1. 商品ページ（フロント）には出しません
   ------------------------------------------------------------
   （2026/10/22 ユーザー指示「フロント側からは見えないようにして。F12を押されても見えない要素にして」）
   管理者がログインしていても、サイトの HTML には一切出しません。メモは登録画面だけで見て・書きます。
   ============================================================ */

/** メモを保存して、だれが・いつ を控えます。表示用の「いつ・だれ」を返します */
function ymkrf_product_memo_store( $id, $memo ) {
	$old = (string) get_post_meta( $id, YMKRF_MEMO_KEY, true );
	if ( trim( $memo ) === '' ) {
		delete_post_meta( $id, YMKRF_MEMO_KEY );
		delete_post_meta( $id, YMKRF_MEMO_KEY . '_by' );
		delete_post_meta( $id, YMKRF_MEMO_KEY . '_at' );
		return '';
	}
	update_post_meta( $id, YMKRF_MEMO_KEY, $memo );
	if ( $memo !== $old ) {
		$u = wp_get_current_user();
		update_post_meta( $id, YMKRF_MEMO_KEY . '_by', $u && $u->exists() ? $u->display_name : '' );
		update_post_meta( $id, YMKRF_MEMO_KEY . '_at', wp_date( 'Y/n/j H:i' ) );
	}
	$who  = (string) get_post_meta( $id, YMKRF_MEMO_KEY . '_by', true );
	$when = (string) get_post_meta( $id, YMKRF_MEMO_KEY . '_at', true );
	return trim( $when . ( $who !== '' ? '　' . $who : '' ) );
}


/* ============================================================
   2. 登録画面（「写真の注意書き」のすぐ下）
   ============================================================ */
/** 「商品データ（基本）」の表の「写真の注意書き」のすぐ下に出す行
 *  （2026/10/22 ユーザー「いまピュアレストQRの商品登録ぺージ開いてますが、どこにある？」→ 右の箱から、注意書きの下へ） */
function ymkrf_product_memo_admin_row( $post ) {
	$memo = (string) get_post_meta( $post->ID, YMKRF_MEMO_KEY, true );
	$who  = (string) get_post_meta( $post->ID, YMKRF_MEMO_KEY . '_by', true );
	$when = (string) get_post_meta( $post->ID, YMKRF_MEMO_KEY . '_at', true );
	$h  = '<tr class="ymkrf-amemo-row"><th><label for="ymkrf_admin_memo">🔒 管理者メモ</label>'
	    . '<span style="display:block;font-weight:400;font-size:11px;color:#9a8740;margin-top:2px">お客様には見えません</span></th><td>';
	$h .= wp_nonce_field( 'ymkrf_amemo_box', 'ymkrf_amemo_box_nonce', true, false );
	$h .= '<textarea id="ymkrf_admin_memo" name="ymkrf_admin_memo" rows="3" style="width:100%;background:#fffbe6;border:1px dashed #e0b400;border-radius:6px" '
	    . 'placeholder="社内用のメモ（例：価格は11月のカタログで見直し予定／メーカー確認中 など）">' . esc_textarea( $memo ) . '</textarea>';
	$h .= '<span class="ymkrf-note">この画面だけに出ます。サイトには一切出ません。'
	    . ( $when !== '' ? '　最後に書いた人：' . esc_html( $when . ( $who !== '' ? '　' . $who : '' ) ) : '' ) . '</span>';
	return $h . '</td></tr>';
}

add_action( 'save_post_ymkrf_product', function ( $post_id ) {
	if ( ! isset( $_POST['ymkrf_amemo_box_nonce'] )
	  || ! wp_verify_nonce( sanitize_key( $_POST['ymkrf_amemo_box_nonce'] ), 'ymkrf_amemo_box' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;
	if ( ! isset( $_POST['ymkrf_admin_memo'] ) ) return;
	ymkrf_product_memo_store( $post_id, sanitize_textarea_field( wp_unslash( $_POST['ymkrf_admin_memo'] ) ) );
}, 20 );
