<?php
/**
 * functions-voice-fix.php ─ お客様の声「✓の見直し」（作業用・一時的）
 * 置き場所： wp-content/themes/ymkrf/inc/functions-voice-fix.php
 *
 * （2026/09/22 ユーザー指示
 *   「✓の箇所が漏れていたり違っていたりしました。もう一度直してもらえないかな？
 *     特に最後と最後から2番目の回答が間違っていることが多いから気を付けて」）
 *
 * ■ なにをする画面か
 *   スキャンして登録した「新しい様式」のアンケートについて、
 *   いま入っている✓を一覧（JSON）で書き出し、
 *   直した内容（JSON）を貼りつけると、まとめて入れ直します。
 *
 * ■ 使いかた
 *   お客様の声 ＞ ✓の見直し
 *     1. 上の「いまの内容」をコピーして渡す
 *     2. 直した内容を下の欄に貼って「この内容で直す」
 *
 * ■ 直す欄
 *   _ymkrf_parts（①工事した箇所）／_ymkrf_reasons（②選んでいただいた理由）
 *   _ymkrf_r_sales ③／_ymkrf_r_plan ④／_ymkrf_r_worker ⑤
 *   _ymkrf_r_process ⑥／_ymkrf_r_site ⑦／_ymkrf_r_finish ⑧
 *   _ymkrf_recommend ⑨　（③〜⑨は 4=大変良かった … 1=よくなかった、0=未記入）
 *
 * ★ この画面は作業用です。見直しが終わったら、このファイルと
 *   functions.php の読み込み行を消してください。
 */
if ( ! defined( 'ABSPATH' ) ) exit;


/** 新しい様式（スキャンして登録した分）のお客様の声 */
function ymkrf_vfix_ids() {

	$q = new WP_Query( array(
		'post_type'      => 'ymkrf_voice',
		'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_query'     => array(
			array( 'key' => '_ymkrf_old_voice', 'compare' => 'NOT EXISTS' ),
		),
		'orderby'        => 'ID',
		'order'          => 'ASC',
	) );
	return array_map( 'intval', (array) $q->posts );
}

/** 点数の欄 */
function ymkrf_vfix_rate_keys() {
	return array(
		'_ymkrf_r_sales', '_ymkrf_r_plan', '_ymkrf_r_worker',
		'_ymkrf_r_process', '_ymkrf_r_site', '_ymkrf_r_finish',
		'_ymkrf_recommend',
	);
}

/** いまの内容（JSON にする前の配列） */
function ymkrf_vfix_dump() {

	$out = array();

	foreach ( ymkrf_vfix_ids() as $id ) {

		$sid = (int) get_post_meta( $id, '_ymkrf_survey_id', true );
		$pid = (int) get_post_meta( $id, '_ymkrf_survey_pub_id', true );
		$one = array(
			'id'      => $id,
			'case_no' => (string) get_post_meta( $id, '_ymkrf_case_no', true ),
			'img'     => (string) wp_get_attachment_url( $pid ? $pid : $sid ),
			'parts'   => ymkrf_voice_meta_array( $id, '_ymkrf_parts' ),
			'reasons' => ymkrf_voice_meta_array( $id, '_ymkrf_reasons' ),
		);
		foreach ( ymkrf_vfix_rate_keys() as $k ) {
			$one[ $k ] = (int) get_post_meta( $id, $k, true );
		}
		$out[] = $one;
	}
	return $out;
}


/* ============================================================
   画面
   ============================================================ */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=ymkrf_voice',
		'✓の見直し', '✓の見直し',
		'manage_options', 'ymkrf-voice-fix', 'ymkrf_vfix_page'
	);
}, 60 );

function ymkrf_vfix_page() {

	if ( ! current_user_can( 'manage_options' ) ) return;

	$msg = '';

	if ( isset( $_POST['ymkrf_vfix_nonce'] ) &&
	     wp_verify_nonce( $_POST['ymkrf_vfix_nonce'], 'ymkrf_vfix' ) ) {

		$raw  = (string) wp_unslash( $_POST['ymkrf_vfix_json'] );
		$rows = json_decode( $raw, true );

		if ( ! is_array( $rows ) ) {
			$msg = 'JSONが読めませんでした。貼りつけた内容をご確認ください。';
		} else {

			$n = 0;
			foreach ( $rows as $r ) {

				if ( ! is_array( $r ) || empty( $r['id'] ) ) continue;
				$id = (int) $r['id'];
				if ( get_post_type( $id ) !== 'ymkrf_voice' ) continue;

				if ( isset( $r['parts'] ) && is_array( $r['parts'] ) ) {
					update_post_meta( $id, '_ymkrf_parts',
						implode( ',', array_map( 'sanitize_text_field', $r['parts'] ) ) );
				}
				if ( isset( $r['reasons'] ) && is_array( $r['reasons'] ) ) {
					update_post_meta( $id, '_ymkrf_reasons',
						implode( ',', array_map( 'sanitize_text_field', $r['reasons'] ) ) );
				}
				foreach ( ymkrf_vfix_rate_keys() as $k ) {
					if ( ! isset( $r[ $k ] ) ) continue;
					$v = (int) $r[ $k ];
					if ( $v < 0 || $v > 4 ) continue;
					update_post_meta( $id, $k, $v );
				}
				$n++;
			}
			$msg = $n . '件を直しました。';
		}
	}

	$json = wp_json_encode( ymkrf_vfix_dump(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	?>
	<div class="wrap">
	  <h1>✓の見直し（作業用）</h1>

	  <?php if ( $msg ) : ?>
	    <div class="notice notice-success"><p><?php echo esc_html( $msg ); ?></p></div>
	  <?php endif; ?>

	  <p style="max-width:900px;line-height:1.9">
	    スキャンして登録した<b>新しい様式</b>のアンケート
	    <b><?php echo count( ymkrf_vfix_ids() ); ?>件</b>の、いま入っている✓です。<br>
	    点数の欄は <b>4=大変良かった／3=満足／2=普通／1=よくなかった／0=未記入</b>、
	    ⑨は <b>4=勧める／3=勧めても良い／2=わからない／1=勧められない</b> です。
	  </p>

	  <h2>いまの内容</h2>
	  <textarea id="ymkrf-vfix-now" readonly rows="12" class="large-text code"
	            style="font-family:monospace;font-size:12px"><?php echo esc_textarea( $json ); ?></textarea>

	  <h2>直した内容を貼りつける</h2>
	  <form method="post"
	        onsubmit="return confirm('貼りつけた内容で、✓を入れ直します。よろしいですか？');">
	    <?php wp_nonce_field( 'ymkrf_vfix', 'ymkrf_vfix_nonce' ); ?>
	    <textarea name="ymkrf_vfix_json" rows="12" class="large-text code"
	              style="font-family:monospace;font-size:12px"
	              placeholder='[{"id":19610,"parts":["給湯器"],"reasons":["価格"],"_ymkrf_r_finish":3,"_ymkrf_recommend":4}]'></textarea>
	    <?php submit_button( 'この内容で直す' ); ?>
	  </form>
	</div>
	<?php
}
