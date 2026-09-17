<?php
/**
 * functions-voice-status.php ─ 「状態」を1つにまとめます
 *   ★お客様の声と商品の、両方であつかいます（ymkrf_vstatus_types）
 * 置き場所： wp-content/themes/ymkrf/inc/functions-voice-status.php
 *
 * （2026/09/16 ユーザー指示
 *   「ステータス、公開、下書き、非公開にまとめては？
 *     パスワード保護とかつかわないので、公開範囲が不要かも」）
 *
 * ■ どうなるか
 *   WordPress のもとの「公開」ボックスには、にた意味のものが2つ並んでいます。
 *     ・ステータス … 下書き／レビュー待ち
 *     ・公開状態　 … 公開／パスワード保護／非公開
 *   これを、ひとつの
 *     状態 … 公開／下書き／非公開
 *   にまとめます。使わない「レビュー待ち」「パスワード保護」は出しません。
 *
 * ■ 気をつけたこと
 *   画面の見た目を変えるだけではなく、保存するときにも
 *   ここでえらんだ状態になるようにしています（wp_insert_post_data）。
 *   状態は、この「状態」でえらんだものだけで決まります。
 *   （2026/09/17 …以前は $_POST['save'] を見て手を引いていましたが、
 *     非公開の記事では「更新」ボタンにも save という名前が付くため、
 *     えらんだ状態がむしされていました）
 *
 * ■ 名前について
 *   入力欄 … ymkrf_vstatus（この画面だけで使う、えらんだ状態）
 */
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * この仕組みを使う投稿タイプ。
 * ここに足せば、その画面も「状態」ひとつにまとまります。
 * （2026/09/17 ユーザー指示「次に商品のところです。ステータスの箇所、
 *   公開、非公開、下書き　にして」→ 商品を追加）
 */
function ymkrf_vstatus_types() {
	return array( 'ymkrf_voice', 'ymkrf_product' );
}

/** この画面で使うかどうか */
function ymkrf_vstatus_on( $type ) {
	return in_array( (string) $type, ymkrf_vstatus_types(), true );
}

/** えらべる状態（2026/09/17 ユーザー指示の並び） */
function ymkrf_vstatus_list() {
	return array(
		'publish' => '公開',
		'private' => '非公開',
		'draft'   => '下書き',
	);
}

/** いまの状態を、上の3つのどれかに寄せます */
function ymkrf_vstatus_now( $post ) {
	$s = $post ? $post->post_status : 'draft';

	/* あたらしく作っているときは「公開」をはじめから選んでおきます
	   （2026/09/17 ユーザー指示「デフォルトで状態は公開にして」
	     「下書きのボタン上にあるし、公開にして」）
	   途中で保存したいときは、上の「下書きとして保存」を押してください。 */
	if ( $s === 'auto-draft' ) return 'publish';

	if ( $s === 'publish' || $s === 'private' ) return $s;
	if ( $s === 'future' ) return 'publish';
	return 'draft';                                  /* pending もここ */
}


/* ------------------------------------------------------------
   「公開」ボックスの中に、まとめた「状態」を出します
   ------------------------------------------------------------ */
add_action( 'post_submitbox_misc_actions', function ( $post ) {

	if ( ! $post || ! ymkrf_vstatus_on( $post->post_type ) ) return;

	$now = ymkrf_vstatus_now( $post );
	wp_nonce_field( 'ymkrf_vstatus_save', 'ymkrf_vstatus_nonce' );
	?>
	<div class="misc-pub-section ymkrf-vstatus">
	  <label for="ymkrf-vstatus" style="font-weight:600">状態：</label>
	  <select name="ymkrf_vstatus" id="ymkrf-vstatus" style="width:100%;margin-top:6px">
	    <?php foreach ( ymkrf_vstatus_list() as $k => $label ) : ?>
	      <option value="<?php echo esc_attr( $k ); ?>" <?php selected( $now, $k ); ?>>
	        <?php echo esc_html( $label ); ?>
	      </option>
	    <?php endforeach; ?>
	  </select>
	  <p class="description" style="margin:6px 0 0">
	    ここでえらんで、下の<b>「更新」</b>を押してください。
	  </p>
	</div>

	<style>
	  /* もとの「ステータス」「公開状態」は、意味がかさなるので出しません
	     （2026/09/16 ユーザー指示） */
	  #submitdiv .misc-pub-post-status,
	  #submitdiv .misc-pub-visibility { display: none !important; }
	  .ymkrf-vstatus select { max-width: 100%; }
	</style>
	<?php
}, 9 );


/* ------------------------------------------------------------
   保存するとき、えらんだ状態にします
   ------------------------------------------------------------ */
add_filter( 'wp_insert_post_data', function ( $data, $postarr ) {

	if ( empty( $data['post_type'] ) || ! ymkrf_vstatus_on( $data['post_type'] ) ) return $data;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return $data;

	if ( ! isset( $_POST['ymkrf_vstatus_nonce'] ) ||
	     ! wp_verify_nonce( $_POST['ymkrf_vstatus_nonce'], 'ymkrf_vstatus_save' ) ) return $data;

	/* ★ここで $_POST['save'] を見てはいけません★
	   （2026/09/17）
	   非公開の記事では、WordPressは「更新」ボタンにも save という名前を使います。
	   以前ここで「save があれば何もしない」としていたため、
	   えらんだ「公開」が毎回むしされていました。
	   状態は、下の「状態」でえらんだものだけで決めます。 */

	$want = isset( $_POST['ymkrf_vstatus'] ) ? (string) $_POST['ymkrf_vstatus'] : '';
	$list = ymkrf_vstatus_list();
	if ( ! isset( $list[ $want ] ) ) return $data;

	/* ゴミ箱に入れたときなどは、さわりません */
	if ( in_array( $data['post_status'], array( 'trash', 'auto-draft', 'inherit' ), true ) ) return $data;

	$data['post_status'] = $want;

	/* 非公開ではパスワードを使いません（使わないというご指示） */
	if ( $want === 'private' ) $data['post_password'] = '';

	return $data;
}, 20, 2 );


/* ------------------------------------------------------------
   ★念のための止め★
   保存のいちばん最後に、もういちど「えらんだ状態」にそろえます。

   （2026/09/17 ユーザー「やっぱり非公開に自動でなります」）
   ほかの仕組みがあとから状態を書きかえても、ここで戻します。
   データベースに直接書くので、また別の処理が動くこともありません。
   ------------------------------------------------------------ */
add_action( 'save_post', function ( $post_id ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;
	if ( ! ymkrf_vstatus_on( get_post_type( $post_id ) ) ) return;

	if ( ! isset( $_POST['ymkrf_vstatus_nonce'] ) ||
	     ! wp_verify_nonce( $_POST['ymkrf_vstatus_nonce'], 'ymkrf_vstatus_save' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	/* ここでも $_POST['save'] は見ません（上と同じ理由です） */

	$want = isset( $_POST['ymkrf_vstatus'] ) ? (string) $_POST['ymkrf_vstatus'] : '';
	$list = ymkrf_vstatus_list();
	if ( ! isset( $list[ $want ] ) ) return;

	$now = get_post_status( $post_id );
	if ( $now === $want ) return;
	if ( in_array( $now, array( 'trash', 'auto-draft', 'inherit' ), true ) ) return;

	global $wpdb;
	$wpdb->update( $wpdb->posts,
		array( 'post_status' => $want, 'post_password' => '' ),
		array( 'ID' => $post_id ) );

	clean_post_cache( $post_id );
}, 999 );


/* ------------------------------------------------------------
   一覧のクイック編集からも、使わないものを消します
   ------------------------------------------------------------ */
add_action( 'admin_head-edit.php', function () {

	$s = get_current_screen();
	if ( ! $s || ! ymkrf_vstatus_on( $s->post_type ) ) return;
	?>
	<style>
	  /* パスワード保護は使いません（2026/09/16 ユーザー指示） */
	  .inline-edit-post .inline-edit-password-input,
	  .inline-edit-post .inline-edit-or,
	  .inline-edit-post label.inline-edit-private + br { display: none !important; }
	</style>
	<script>
	jQuery(function ($) {
	  /* クイック編集の「ステータス」から、使わない「レビュー待ち」を外します */
	  $(document).on('click', '.editinline', function () {
	    setTimeout(function () {
	      $('select[name="_status"] option[value="pending"]').remove();
	    }, 50);
	  });
	});
	</script>
	<?php
} );
