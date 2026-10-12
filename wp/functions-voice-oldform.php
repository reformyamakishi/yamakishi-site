<?php
/**
 * 旧型アンケート（A4たて「あなたのお声をおきかせください！」）
 * リフォームヤマキシ（ymkrf）
 *
 * 2026/10/12 ユーザー
 *   「もう旧型で使用はしません。その169件だけ、旧型として様式を変更してください。
 *     選択式を削除して、リフォーム前のお悩み、リフォームしていかがでしたか、
 *     スタッフへのメッセージも削除して、Q＆Aを手打ちで入れられるようにしておいて。
 *     あとは同じでOK　くれぐれもその169件分だけね」
 *
 * ★どれが「旧型」か★
 *   アンケート画像が「たて長」のものです。
 *   いまの「仕事の通信簿」はA4よこ、旧型はA4たてなので、
 *   画像の向きだけで、まちがいなく分けられます。
 *   2026/10/12 時点で 169件（2019年26・2020年104・2021年29・2024年2・番号なし8）。
 *   新しく増えることはありません（もう使わない用紙です）。
 *
 * ★この旧型でだけ、こうします★
 *   編集画面  … ②選んでいただいた理由／③〜⑨の評価／満足度／
 *                リフォーム前のお悩み／いかがでしたか／スタッフへのメッセージ　を出しません。
 *                かわりに「アンケートのQ＆A」を手で打ち込みます。
 *                ①工事した箇所は残します（URLと一覧の振り分けに使うため）。
 *   ページ    … Q＆Aをそのまま「お客様にうかがいました」に出します。
 *                ★と点数、「いただいた評価」、「お選びいただいた理由」は出しません
 *                （用紙にその欄がないため。2026/10/12 ユーザー指示）。
 *
 *   ほかの1,300件あまり（いまの「仕事の通信簿」）には、いっさい手を触れません。
 */

if ( ! defined( 'ABSPATH' ) ) exit;


/* ============================================================
   1. 旧型かどうか
   ============================================================ */

/** このお客様の声は、旧型（A4たて）のアンケートか */
function ymkrf_is_oldform( $post_id ) {
	return get_post_meta( (int) $post_id, '_ymkrf_oldform', true ) === '1';
}

/** アンケート画像が「たて長」か（旧型の見分け） */
function ymkrf_voice_sheet_is_tate( $post_id ) {

	$att = (int) get_post_meta( $post_id, '_ymkrf_survey_pub_id', true );
	if ( ! $att ) $att = (int) get_post_meta( $post_id, '_ymkrf_survey_id', true );
	if ( ! $att ) return false;

	$m = wp_get_attachment_metadata( $att );
	$w = isset( $m['width'] )  ? (int) $m['width']  : 0;
	$h = isset( $m['height'] ) ? (int) $m['height'] : 0;

	return ( $w > 0 && $h > 0 && $h > $w );
}

/**
 * いまある分に、1回だけ「旧型」の印をつけます（500件ずつ進みます）。
 * 1,500件あるので、ダッシュボードを3回ほど開くと全部に行きわたります。
 * 印をつけるだけで、中身はいっさい変えません。
 */
add_action( 'admin_init', function () {

	if ( get_option( 'ymkrf_voice_oldform_ver' ) === '1' ) return;
	if ( ! current_user_can( 'upload_files' ) ) return;

	$ids = get_posts( array(
		'post_type'      => 'ymkrf_voice',
		'post_status'    => 'any',
		'posts_per_page' => 500,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_query'     => array(
			array( 'key' => '_ymkrf_oldform_done', 'compare' => 'NOT EXISTS' ),
		),
	) );

	if ( ! $ids ) {
		update_option( 'ymkrf_voice_oldform_ver', '1', false );
		return;
	}

	$n = (int) get_option( 'ymkrf_voice_oldform_n', 0 );
	foreach ( $ids as $vid ) {
		update_post_meta( $vid, '_ymkrf_oldform_done', '1' );
		if ( ymkrf_voice_sheet_is_tate( $vid ) ) {
			update_post_meta( $vid, '_ymkrf_oldform', '1' );
			$n++;
		}
	}
	update_option( 'ymkrf_voice_oldform_n', $n, false );
}, 22 );


/* ============================================================
   2. Q＆A（手打ち）
   ============================================================ */

/** 打ち込まれたQ＆Aを取り出します（質問・答えの両方があるものだけ） */
function ymkrf_voice_qa_rows( $post_id ) {

	$rows = get_post_meta( (int) $post_id, '_ymkrf_qa', true );
	$out  = array();

	foreach ( (array) $rows as $r ) {
		if ( ! is_array( $r ) ) continue;
		$q = isset( $r['q'] ) ? trim( (string) $r['q'] ) : '';
		$a = isset( $r['a'] ) ? trim( (string) $r['a'] ) : '';
		if ( $q === '' || $a === '' ) continue;
		$out[] = array( 'q' => $q, 'a' => $a );
	}
	return $out;
}

/** 旧型の用紙によく出てくる質問（打ち込みの下書きとして、空欄に入れておきます） */
function ymkrf_voice_qa_hints() {
	return array(
		'購入する前は、どのようなことで悩んでいましたか？',
		'何がきっかけで、ヤマキシを知りましたか？',
		'ヤマキシで買うことを決めた理由は何ですか？',
		'実際に工事をしてみて、いかがでしたか？',
		'これから検討される方へ、ひとことお願いします。',
	);
}

add_action( 'add_meta_boxes', function ( $post_type, $post ) {

	if ( $post_type !== 'ymkrf_voice' ) return;
	if ( ! $post || ! ymkrf_is_oldform( $post->ID ) ) return;

	add_meta_box(
		'ymkrf_voice_qa',
		'アンケートのQ＆A（旧型・A4たての用紙）',
		'ymkrf_voice_qa_box',
		'ymkrf_voice',
		'normal',
		'high'
	);
}, 25, 2 );

function ymkrf_voice_qa_box( $post ) {

	wp_nonce_field( 'ymkrf_voice_qa_save', 'ymkrf_voice_qa_nonce' );

	$rows  = ymkrf_voice_qa_rows( $post->ID );
	$hints = ymkrf_voice_qa_hints();

	/* 空の行を足して、いつでも書き足せるようにします */
	$show = $rows;
	for ( $i = count( $show ); $i < max( 5, count( $rows ) + 2 ); $i++ ) {
		$show[] = array( 'q' => isset( $hints[ $i ] ) ? $hints[ $i ] : '', 'a' => '' );
	}
	?>
	<div class="ymkrf-qa">
		<p class="description" style="font-size:14px;line-height:1.9">
			用紙に書かれている<b>質問（Q）</b>と、お客様の<b>手書きの答え（A）</b>を、そのまま打ち込んでください。<br>
			質問と答えの<b>両方が入っている行だけ</b>ページに出ます。空の行は出ません。<br>
			答えは長くてかまいません。改行もそのまま出ます。
		</p>

		<div id="ymkrf-qa-rows">
			<?php foreach ( $show as $i => $r ) : ?>
			<div class="ymkrf-qa__row" style="border:1px solid #dcdcde;border-radius:6px;padding:12px 14px;margin:0 0 12px;background:#fff">
				<p style="margin:0 0 8px">
					<label style="display:block;font-weight:700;margin-bottom:4px">質問（Q<?php echo (int) $i + 1; ?>）</label>
					<input type="text" class="large-text" name="ymkrf_qa[<?php echo (int) $i; ?>][q]"
					       value="<?php echo esc_attr( $r['q'] ); ?>"
					       placeholder="例：給湯器を購入する前どのようなことで悩んでいましたか？">
				</p>
				<p style="margin:0">
					<label style="display:block;font-weight:700;margin-bottom:4px">お客様の答え（A<?php echo (int) $i + 1; ?>）</label>
					<textarea class="large-text" rows="3" name="ymkrf_qa[<?php echo (int) $i; ?>][a]"
					          placeholder="手書きのとおりに打ち込んでください"><?php echo esc_textarea( $r['a'] ); ?></textarea>
				</p>
			</div>
			<?php endforeach; ?>
		</div>

		<p>
			<button type="button" class="button" id="ymkrf-qa-add">質問をもう1つ足す</button>
			<span class="description">（足りないときだけ押してください）</span>
		</p>

		<p style="margin-top:18px;padding-top:14px;border-top:1px solid #e3e3e3">
			<label><input type="checkbox" name="ymkrf_qa_unold" value="1">
				この投稿は旧型ではありません（旧型あつかいをやめる）</label><br>
			<span class="description">
				まちがって旧型になっているときだけチェックしてください。
				保存すると、ふつうの「仕事の通信簿」の画面にもどります。
			</span>
		</p>
	</div>

	<script>
	jQuery(function ($) {
		$('#ymkrf-qa-add').on('click', function () {
			var n = $('#ymkrf-qa-rows .ymkrf-qa__row').length;
			var html =
				'<div class="ymkrf-qa__row" style="border:1px solid #dcdcde;border-radius:6px;padding:12px 14px;margin:0 0 12px;background:#fff">' +
				'<p style="margin:0 0 8px"><label style="display:block;font-weight:700;margin-bottom:4px">質問（Q' + (n + 1) + '）</label>' +
				'<input type="text" class="large-text" name="ymkrf_qa[' + n + '][q]" value=""></p>' +
				'<p style="margin:0"><label style="display:block;font-weight:700;margin-bottom:4px">お客様の答え（A' + (n + 1) + '）</label>' +
				'<textarea class="large-text" rows="3" name="ymkrf_qa[' + n + '][a]"></textarea></p></div>';
			$('#ymkrf-qa-rows').append(html);
		});
	});
	</script>
	<?php
}

/** Q＆Aを保存します */
add_action( 'save_post_ymkrf_voice', function ( $post_id ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;
	if ( ! isset( $_POST['ymkrf_voice_qa_nonce'] ) ) return;
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ymkrf_voice_qa_nonce'] ) ), 'ymkrf_voice_qa_save' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	/* 旧型あつかいをやめる */
	if ( ! empty( $_POST['ymkrf_qa_unold'] ) ) {
		delete_post_meta( $post_id, '_ymkrf_oldform' );
		return;
	}

	$in  = isset( $_POST['ymkrf_qa'] ) ? (array) wp_unslash( $_POST['ymkrf_qa'] ) : array();
	$out = array();

	foreach ( $in as $r ) {
		if ( ! is_array( $r ) ) continue;
		$q = isset( $r['q'] ) ? trim( sanitize_textarea_field( $r['q'] ) ) : '';
		$a = isset( $r['a'] ) ? trim( sanitize_textarea_field( $r['a'] ) ) : '';
		if ( $q === '' && $a === '' ) continue;
		$out[] = array( 'q' => $q, 'a' => $a );
	}

	if ( $out ) update_post_meta( $post_id, '_ymkrf_qa', $out );
	else        delete_post_meta( $post_id, '_ymkrf_qa' );
}, 25 );


/* ============================================================
   3. 編集画面で、旧型に関係のない欄を出さない
   ============================================================ */

add_action( 'admin_footer-post.php', 'ymkrf_voice_oldform_admin_js' );
function ymkrf_voice_oldform_admin_js() {

	$screen = get_current_screen();
	if ( ! $screen || $screen->post_type !== 'ymkrf_voice' ) return;

	$post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0;
	if ( ! $post_id || ! ymkrf_is_oldform( $post_id ) ) return;
	?>
	<script>
	jQuery(function ($) {

		var T = $('.ymkrf-voice__table');
		if ( ! T.length ) return;

		/* 旧型の用紙にない欄は、画面から外します。
		   （中身は消しません。もし旧型あつかいをやめれば、また出てきます） */
		function dropRow(sel) { T.find(sel).closest('tr').remove(); }

		dropRow('#ymkrf-reasons');                 /* ② 選んでいただいた理由 */
		dropRow('.ymkrf-voice__radios');           /* ③〜⑨ の評価 */
		dropRow('#ymkrf-score');                   /* 満足度（点数） */
		dropRow('textarea[name="_ymkrf_trouble"]');
		dropRow('textarea[name="_ymkrf_after"]');
		dropRow('textarea[name="_ymkrf_comment"]');

		/* 読み取りのボタンは、旧型では使えません */
		$('#ymkrf-reread, #ymkrf-ocr').remove();

		/* 上の説明文を、旧型むけに差しかえます */
		$('.ymkrf-voice__lead').html(
			'<b>旧型（A4たて）のアンケートです。</b>' +
			'この用紙にはチェック欄も点数欄もないので、' +
			'手書きの<b>Q＆Aを下の欄に打ち込んでください</b>。<br>' +
			'<span class="description">' +
			'①工事した箇所だけは、ページの振り分け（/voice/boiler/ など）に使うので残してあります。<br>' +
			'自動の読み取りは、この用紙には対応していません。' +
			'</span>'
		);
	});
	</script>
	<?php
}

/* 一覧で見分けられるように、題名のうしろに小さく出します */
add_filter( 'display_post_states', function ( $states, $post ) {
	if ( $post && $post->post_type === 'ymkrf_voice' && ymkrf_is_oldform( $post->ID ) ) {
		$states['ymkrf_oldform'] = '旧型';
	}
	return $states;
}, 10, 2 );
