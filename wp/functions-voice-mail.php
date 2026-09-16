<?php
/**
 * functions-voice-mail.php ─ アンケートを登録したら担当店へお知らせメール
 * 置き場所： wp-content/themes/ymkrf/inc/functions-voice-mail.php
 *
 *  1. 設定（お客様の声 ＞ お知らせメールの設定）
 *  2. 送りさきを組み立てる
 *  3. メールの文面を組み立てる
 *  4. 送る
 *  5. 編集画面の「担当店へのお知らせ」欄
 *  6. 送信のきっかけ（保存したとき／「いま送る」ボタン）
 *  7. 送信の記録
 *
 * ── 名前について ──────────────────────────────
 * 設定の保存さき … ymkrf_vmail（ひとつの箱にまとめています）
 * 送信の記録 …… ymkrf_vmail_log（新しい順に50件まで）
 * 入力欄 …… _ymkrf_mail_send  担当店へ連絡する（1なら送る）
 *             _ymkrf_claim      クレーム（1なら有）
 *             _ymkrf_mail_sent  送った日時（送れていたら入ります）
 *             _ymkrf_staff_mail スタッフのメールアドレス（functions-staff.php）
 * ─────────────────────────────────────────
 *
 * ★いまはテスト中です★
 *   設定画面の「テスト中」にチェックが入っているあいだは、
 *   何人にあてたメールでも、ぜんぶ下の1アドレスにだけ届きます。
 *   本番前にチェックを外すと、担当店の全員と本部の全員に届くようになります。
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** テスト中の送りさき（はじめの値） */
const YMKRF_VMAIL_TEST_TO = 'h-nakagawa@yamakishi.co.jp';

/** メールの件名 */
const YMKRF_VMAIL_SUBJECT = 'お客様よりアンケートが届きましたのでお知らせいたします。';

/** 差出人の名前 */
const YMKRF_VMAIL_FROM_NAME = 'リフォームヤマキシ マーケティング室';


/* ============================================================
   1. 設定
   ============================================================ */

/** 設定を読みます（足りないところは、はじめの値でうめます） */
function ymkrf_vmail_opt() {
	$d = array(
		'on'      => '1',                      // 仕組みを動かす
		'test'    => '1',                      // テスト中（1アドレスだけに送る）
		'test_to' => YMKRF_VMAIL_TEST_TO,      // テスト中の送りさき
		'hq'      => '',                       // 本部などの追加アドレス（1行に1つ）
		'from'    => '',                       // 差出人（空ならWordPressの既定）
	);
	$o = get_option( 'ymkrf_vmail' );
	if ( ! is_array( $o ) ) $o = array();
	return array_merge( $d, $o );
}

/** 設定の画面を「お客様の声」の下に出します */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=ymkrf_voice',
		'お知らせメールの設定',
		'お知らせメールの設定',
		'manage_options',
		'ymkrf-vmail',
		'ymkrf_vmail_settings_page'
	);
}, 997 );

function ymkrf_vmail_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) return;

	$msg = '';
	if ( isset( $_POST['ymkrf_vmail_nonce_set'] ) &&
	     wp_verify_nonce( $_POST['ymkrf_vmail_nonce_set'], 'ymkrf_vmail_set' ) ) {

		update_option( 'ymkrf_vmail', array(
			'on'      => empty( $_POST['ymkrf_vmail_on'] )   ? '' : '1',
			'test'    => empty( $_POST['ymkrf_vmail_test'] ) ? '' : '1',
			'test_to' => sanitize_email( wp_unslash( $_POST['ymkrf_vmail_test_to'] ) ),
			'hq'      => sanitize_textarea_field( wp_unslash( $_POST['ymkrf_vmail_hq'] ) ),
			'from'    => sanitize_email( wp_unslash( $_POST['ymkrf_vmail_from'] ) ),
		) );
		$msg = '保存しました。';
	}

	/* おためし送信 */
	if ( isset( $_POST['ymkrf_vmail_nonce_try'] ) &&
	     wp_verify_nonce( $_POST['ymkrf_vmail_nonce_try'], 'ymkrf_vmail_try' ) ) {
		$o  = ymkrf_vmail_opt();
		$to = sanitize_email( wp_unslash( $_POST['ymkrf_vmail_try_to'] ) );
		if ( $to === '' ) $to = $o['test_to'];
		$ok = wp_mail( $to, '【おためし】' . YMKRF_VMAIL_SUBJECT,
			"これは、メールがとどくかどうかを試すためのメールです。\n"
			. "このメールがとどいていれば、設定はできています。\n\n"
			. "マーケティング室\n",
			ymkrf_vmail_headers() );
		$msg = $ok
			? $to . ' に、おためしのメールを出しました。'
			: '送れませんでした。サーバーのメール設定（SMTP）をご確認ください。';
	}

	$o   = ymkrf_vmail_opt();
	$log = get_option( 'ymkrf_vmail_log' );
	if ( ! is_array( $log ) ) $log = array();
	?>
	<div class="wrap">
	  <h1>お知らせメールの設定</h1>

	  <?php if ( $msg ) : ?>
	    <div class="notice notice-success"><p><?php echo esc_html( $msg ); ?></p></div>
	  <?php endif; ?>

	  <p>
	    お客様の声（アンケート）を登録して、編集画面の
	    <b>「アンケートを担当店へ連絡する」</b>にチェックを入れて保存すると、
	    担当店のスタッフ全員と本部あてに、お知らせメールがとどきます。
	  </p>

	  <?php if ( ! empty( $o['test'] ) ) : ?>
	    <div class="notice notice-warning" style="border-left-color:#d63638">
	      <p style="font-size:15px">
	        <b>いまはテスト中です。</b>
	        何人あてのメールでも、ぜんぶ
	        <code><?php echo esc_html( $o['test_to'] ); ?></code>
	        にだけとどきます。本番前に、下の「テスト中」のチェックを外してください。
	      </p>
	    </div>
	  <?php endif; ?>

	  <form method="post">
	    <?php wp_nonce_field( 'ymkrf_vmail_set', 'ymkrf_vmail_nonce_set' ); ?>
	    <table class="form-table">

	      <tr>
	        <th>この仕組みを使う</th>
	        <td>
	          <label>
	            <input type="checkbox" name="ymkrf_vmail_on" value="1" <?php checked( $o['on'], '1' ); ?>>
	            お知らせメールを送る
	          </label>
	          <p class="description">チェックを外すと、どこにも送らなくなります。</p>
	        </td>
	      </tr>

	      <tr>
	        <th>テスト中</th>
	        <td>
	          <label>
	            <input type="checkbox" name="ymkrf_vmail_test" value="1" <?php checked( $o['test'], '1' ); ?>>
	            <b>下の1アドレスにだけ送る（テスト中）</b>
	          </label><br><br>
	          <input type="email" name="ymkrf_vmail_test_to" class="regular-text"
	                 value="<?php echo esc_attr( $o['test_to'] ); ?>">
	          <p class="description">
	            チェックが入っているあいだは、担当店にも本部にもとどきません。安心してお試しください。<br>
	            <b style="color:#b32d00">本番前にチェックを外してください。</b>
	          </p>
	        </td>
	      </tr>

	      <tr>
	        <th>本部などの追加アドレス</th>
	        <td>
	          <textarea name="ymkrf_vmail_hq" rows="5" class="large-text"
	                    placeholder="reform@yamakishi.co.jp"><?php echo esc_textarea( $o['hq'] ); ?></textarea>
	          <p class="description">
	            1行に1つ書いてください。担当店のスタッフとはべつに、いつもこのアドレスにも届きます。<br>
	            スタッフ登録で「所属＝本部」の人は、ここに書かなくても自動で入ります。
	          </p>
	        </td>
	      </tr>

	      <tr>
	        <th>差出人のアドレス</th>
	        <td>
	          <input type="email" name="ymkrf_vmail_from" class="regular-text"
	                 value="<?php echo esc_attr( $o['from'] ); ?>" placeholder="（空のままでOK）">
	          <p class="description">空のままなら、WordPressの決めたアドレスで出します。</p>
	        </td>
	      </tr>

	    </table>
	    <?php submit_button( '保存する' ); ?>
	  </form>

	  <hr>

	  <h2>メールがとどくか、ためす</h2>
	  <form method="post">
	    <?php wp_nonce_field( 'ymkrf_vmail_try', 'ymkrf_vmail_nonce_try' ); ?>
	    <input type="email" name="ymkrf_vmail_try_to" class="regular-text"
	           value="<?php echo esc_attr( $o['test_to'] ); ?>">
	    <?php submit_button( 'おためしのメールを出す', 'secondary', 'submit', false ); ?>
	    <p class="description">
	      ここだけは、テスト中かどうかに関係なく、書いたアドレスに1通だけ出します。
	    </p>
	  </form>

	  <hr>

	  <h2>いま、だれにとどくか</h2>
	  <?php
	  $hq = ymkrf_vmail_hq_list();
	  ?>
	  <p>本部あて（いつも入る人）：
	    <?php echo $hq ? esc_html( implode( '、', $hq ) ) : '<span style="color:#b32d00">まだ1人もいません</span>'; ?>
	  </p>
	  <p class="description">
	    店舗のスタッフは、アンケートの「担当店舗」ごとにかわります。<br>
	    メールアドレスは、<b>スタッフ ＞ その人を編集 ＞ メールアドレス（社内）</b>に入れてください。
	  </p>

	  <?php
	  $noaddr = ymkrf_vmail_staff_without_mail();
	  if ( $noaddr ) : ?>
	    <div class="notice notice-warning" style="margin:12px 0">
	      <p><b>メールアドレスがまだ入っていない人（<?php echo count( $noaddr ); ?>人）</b></p>
	      <p style="max-height:9em;overflow:auto"><?php echo esc_html( implode( '、', $noaddr ) ); ?></p>
	    </div>
	  <?php endif; ?>

	  <hr>

	  <h2>送った記録（新しい順に50件）</h2>
	  <?php if ( ! $log ) : ?>
	    <p>まだありません。</p>
	  <?php else : ?>
	    <table class="widefat striped">
	      <thead><tr><th style="width:150px">日時</th><th>アンケート</th><th>送りさき</th><th style="width:70px">結果</th></tr></thead>
	      <tbody>
	      <?php foreach ( $log as $r ) : ?>
	        <tr>
	          <td><?php echo esc_html( $r['time'] ); ?></td>
	          <td><?php
	            $t = get_the_title( $r['id'] );
	            echo $t
	              ? '<a href="' . esc_url( get_edit_post_link( $r['id'] ) ) . '">' . esc_html( $t ) . '</a>'
	              : '（消えました）'; ?></td>
	          <td style="word-break:break-all"><?php echo esc_html( $r['to'] ); ?></td>
	          <td><?php echo $r['ok'] ? '<span style="color:#00782a">○</span>'
	                                  : '<span style="color:#d63638">×</span>'; ?></td>
	        </tr>
	      <?php endforeach; ?>
	      </tbody>
	    </table>
	  <?php endif; ?>
	</div>
	<?php
}


/* ============================================================
   2. 送りさき
   ============================================================ */

/** メールの見出し（差出人） */
function ymkrf_vmail_headers() {
	$o = ymkrf_vmail_opt();
	$h = array( 'Content-Type: text/plain; charset=UTF-8' );
	if ( ! empty( $o['from'] ) && is_email( $o['from'] ) ) {
		$h[] = 'From: ' . YMKRF_VMAIL_FROM_NAME . ' <' . $o['from'] . '>';
	}
	return $h;
}

/** ある店舗（英字）に所属するスタッフのメールアドレス */
function ymkrf_vmail_shop_mails( $shop_slug ) {
	$out = array();
	if ( $shop_slug === '' ) return $out;

	$q = new WP_Query( array(
		'post_type'      => 'ymkrf_staff',
		'post_status'    => array( 'publish', 'draft', 'private' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_query'     => array( array( 'key' => '_ymkrf_staff_shop', 'value' => $shop_slug ) ),
	) );
	foreach ( $q->posts as $sid ) {
		$m = trim( (string) get_post_meta( $sid, '_ymkrf_staff_mail', true ) );
		if ( $m !== '' && is_email( $m ) ) $out[] = $m;
	}
	return $out;
}

/** 本部あて（所属＝本部のスタッフ ＋ 設定に書いた追加アドレス） */
function ymkrf_vmail_hq_list() {
	$o   = ymkrf_vmail_opt();
	$out = ymkrf_vmail_shop_mails( 'honbu' );

	foreach ( preg_split( '/[\r\n,]+/', (string) $o['hq'] ) as $line ) {
		$line = trim( $line );
		if ( $line !== '' && is_email( $line ) ) $out[] = $line;
	}
	return array_values( array_unique( $out ) );
}

/** メールアドレスがまだ入っていないスタッフの名前 */
function ymkrf_vmail_staff_without_mail() {
	$q = new WP_Query( array(
		'post_type'      => 'ymkrf_staff',
		'post_status'    => array( 'publish', 'draft', 'private' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );
	$out = array();
	foreach ( $q->posts as $sid ) {
		$m = trim( (string) get_post_meta( $sid, '_ymkrf_staff_mail', true ) );
		if ( $m === '' ) $out[] = get_the_title( $sid );
	}
	return $out;
}

/**
 * このアンケートの送りさきを決めます。
 * テスト中は、なにがあっても1アドレスだけを返します。
 */
function ymkrf_vmail_to( $post_id ) {
	$o = ymkrf_vmail_opt();

	if ( ! empty( $o['test'] ) ) {
		return is_email( $o['test_to'] ) ? array( $o['test_to'] ) : array();
	}

	/* 担当店舗。スタッフの所属より、アンケートの「担当店舗」をさきに見ます */
	$shop = trim( (string) get_post_meta( $post_id, '_ymkrf_shop', true ) );
	if ( $shop === '' ) {
		$sid = (int) get_post_meta( $post_id, '_ymkrf_staff', true );
		if ( $sid ) $shop = trim( (string) get_post_meta( $sid, '_ymkrf_staff_shop', true ) );
	}

	$to = array_merge( ymkrf_vmail_shop_mails( $shop ), ymkrf_vmail_hq_list() );
	return array_values( array_unique( $to ) );
}


/* ============================================================
   3. 文面
   ============================================================ */

/** 「山岸チーフ」「山岸さん」のような呼び名をつくります */
function ymkrf_vmail_staff_label( $post_id ) {
	$sid = (int) get_post_meta( $post_id, '_ymkrf_staff', true );
	if ( ! $sid ) return '';

	$name = trim( (string) get_the_title( $sid ) );
	if ( $name === '' ) return '';

	$role = trim( (string) get_post_meta( $sid, '_ymkrf_staff_role', true ) );
	/* 役職のある人は「さん」ではなく役職で呼びます */
	return $role !== '' ? $name . $role : $name . 'さん';
}

/** アンケートのURL（公開前は編集画面のURLを出します） */
function ymkrf_vmail_url( $post_id ) {
	if ( get_post_status( $post_id ) === 'publish' ) {
		return get_permalink( $post_id );
	}
	return get_edit_post_link( $post_id, 'raw' );
}

/** メールの本文をつくります */
function ymkrf_vmail_body( $post_id ) {
	$claim = ( get_post_meta( $post_id, '_ymkrf_claim', true ) === '1' );
	$shop  = ymkrf_voice_shop_name( $post_id );
	$staff = ymkrf_vmail_staff_label( $post_id );
	$date  = date_i18n( 'n月j日', get_post_time( 'U', false, $post_id ) );
	$url   = ymkrf_vmail_url( $post_id );

	$b  = YMKRF_VMAIL_SUBJECT . "\n\n";
	$b .= $date . "\n";
	$b .= '営業店：' . ( $shop !== '' ? $shop : '（未設定）' ) . "\n";
	$b .= '営業担当者：' . ( $staff !== '' ? $staff : '（未設定）' ) . "\n";
	$b .= 'クレーム：' . ( $claim ? '有 ☑　／　無 □' : '有 □　／　無 ☑' ) . "\n";
	$b .= "\n";
	$b .= "▼アンケートはこちらです\n" . $url . "\n";

	if ( get_post_status( $post_id ) !== 'publish' ) {
		$b .= "（このアンケートは、まだ公開していません）\n";
	}

	if ( $claim ) {
		$b .= "\n";
		$b .= "内容をご確認いただき、ご対応お願いします。\n";
		$b .= "見積システム上では現在「15用追加対応（苦情/補修/メンテ）」となっております。\n";
		$b .= "対応中は「16追加対応中（苦情/補修/メンテ）」\n";
		$b .= "対応後は「17追加対応完了（苦情/補修/メンテ）」\n";
		$b .= "に変更をお願いします。\n";
		$b .= "\n";
		$b .= "※くれぐれも「完了」にはしないで下さい。\n";
		$b .= "　またハガキが1か月ごと6か月後に自動で送られます。\n";
		$b .= "\n";
		$b .= "※また、最終状況が「17追加対応完了（苦情/補修/メンテ）」になるまで\n";
		$b .= "　マーケティング室で追いかけをしています。\n";
		$b .= "　状態に変化がない場合は営業担当の方に定期的に連絡させていただきますので\n";
		$b .= "　よろしくお願いします。\n";
	}

	$b .= "\n";
	$b .= "マーケティング室\n";

	return $b;
}


/* ============================================================
   4. 送る
   ============================================================ */

/**
 * 1件ぶん送ります。
 * 返り値は array( 'ok' => true/false, 'to' => '送りさき', 'why' => '送らなかったわけ' )
 */
function ymkrf_vmail_send( $post_id, $force = false ) {
	$o = ymkrf_vmail_opt();

	if ( empty( $o['on'] ) ) {
		return array( 'ok' => false, 'to' => '', 'why' => 'お知らせメールの設定が「使わない」になっています' );
	}
	if ( get_post_type( $post_id ) !== 'ymkrf_voice' ) {
		return array( 'ok' => false, 'to' => '', 'why' => 'お客様の声ではありません' );
	}
	if ( ! $force && get_post_meta( $post_id, '_ymkrf_mail_sent', true ) !== '' ) {
		return array( 'ok' => false, 'to' => '', 'why' => 'すでに送っています' );
	}

	$to = ymkrf_vmail_to( $post_id );
	if ( ! $to ) {
		return array( 'ok' => false, 'to' => '', 'why' => '送りさきのメールアドレスが1つもありません' );
	}

	$ok = wp_mail( $to, YMKRF_VMAIL_SUBJECT, ymkrf_vmail_body( $post_id ), ymkrf_vmail_headers() );

	if ( $ok ) update_post_meta( $post_id, '_ymkrf_mail_sent', current_time( 'mysql' ) );

	ymkrf_vmail_log( $post_id, implode( ', ', $to ), $ok );

	return array(
		'ok'  => $ok,
		'to'  => implode( '、', $to ),
		'why' => $ok ? '' : 'サーバーがメールを受けつけませんでした（SMTPの設定をご確認ください）',
	);
}


/* ============================================================
   5. 編集画面の「担当店へのお知らせ」欄
   ============================================================ */

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'ymkrf_vmail_box', '担当店へのお知らせメール',
		'ymkrf_vmail_metabox', 'ymkrf_voice', 'side', 'high' );
}, 12 );

function ymkrf_vmail_metabox( $post ) {
	$o     = ymkrf_vmail_opt();
	$sent  = (string) get_post_meta( $post->ID, '_ymkrf_mail_sent', true );
	$claim = ( get_post_meta( $post->ID, '_ymkrf_claim', true ) === '1' );

	$send = (string) get_post_meta( $post->ID, '_ymkrf_mail_send', true );
	if ( $send === '' ) {
		/* あたらしく作っているときだけ、はじめからチェックを入れておきます */
		$send = ( $post->post_status === 'auto-draft' ) ? '1' : '0';
	}

	wp_nonce_field( 'ymkrf_vmail_save', 'ymkrf_vmail_nonce' );
	?>
	<p style="margin-top:0">
	  <label style="font-size:14px">
	    <input type="checkbox" name="_ymkrf_mail_send" value="1" <?php checked( $send, '1' ); ?>>
	    <b>アンケートを担当店へ連絡する</b>
	  </label>
	</p>
	<p class="description" style="margin-top:-6px">
	  チェックを入れて保存すると、担当店のスタッフ全員と本部あてに、このアンケートのURLをお知らせします。
	</p>

	<hr>

	<p style="margin-bottom:4px"><b>クレーム</b></p>
	<p style="margin-top:0">
	  <label style="margin-right:14px">
	    <input type="radio" name="_ymkrf_claim" value="1" <?php checked( $claim, true ); ?>>
	    有
	  </label>
	  <label>
	    <input type="radio" name="_ymkrf_claim" value="" <?php checked( $claim, false ); ?>>
	    無
	  </label>
	</p>
	<p class="description" style="margin-top:-6px">
	  「有」にすると、メールに対応のお願いの文がつきます。<br>
	  「有」のアンケートは<b style="color:#b32d00">下書き</b>のままにしておきます。
	</p>

	<hr>

	<?php if ( ! empty( $o['test'] ) ) : ?>
	  <p style="background:#fff5f5;border-left:3px solid #d63638;padding:6px 8px;margin:0 0 8px">
	    <b>いまはテスト中です。</b><br>
	    メールは <code><?php echo esc_html( $o['test_to'] ); ?></code> にだけとどきます。
	  </p>
	<?php endif; ?>

	<p style="margin:0 0 6px">
	  <?php if ( $sent !== '' ) : ?>
	    <span style="color:#00782a">送信ずみ</span>（<?php echo esc_html( $sent ); ?>）
	  <?php else : ?>
	    <span style="color:#666">まだ送っていません</span>
	  <?php endif; ?>
	</p>

	<?php
	$url = wp_nonce_url(
		admin_url( 'post.php?post=' . $post->ID . '&action=edit&ymkrf_vmail_now=1' ),
		'ymkrf_vmail_now_' . $post->ID, 'ymkrf_vmail_now_nonce' );
	?>
	<a href="<?php echo esc_url( $url ); ?>" class="button">
	  <?php echo $sent !== '' ? 'もう一度いま送る' : 'いま送る'; ?>
	</a>
	<p class="description">先に「更新」を押して、内容を保存してからお使いください。</p>
	<?php
}


/* ============================================================
   6. 送信のきっかけ
   ============================================================ */

/**
 * 編集画面で保存したときに送ります。
 * 「ymkrf_vmail_nonce」があるとき＝編集画面から保存したときだけ動くので、
 * 取り込みや入れ直しの一括処理でまとめて送ってしまうことはありません。
 */
add_action( 'save_post_ymkrf_voice', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! isset( $_POST['ymkrf_vmail_nonce'] ) ||
	     ! wp_verify_nonce( $_POST['ymkrf_vmail_nonce'], 'ymkrf_vmail_save' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	$send = empty( $_POST['_ymkrf_mail_send'] ) ? '' : '1';
	update_post_meta( $post_id, '_ymkrf_mail_send', $send ? '1' : '0' );
	update_post_meta( $post_id, '_ymkrf_claim', empty( $_POST['_ymkrf_claim'] ) ? '' : '1' );
}, 40 );

/**
 * 送るのは、いちばん最後にします。
 * functions-voice-check.php が「要確認」で下書きにもどすのが 60 なので、
 * そのあと（70）に送れば、メールに書くURLが正しくなります。
 */
add_action( 'save_post_ymkrf_voice', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! isset( $_POST['ymkrf_vmail_nonce'] ) ||
	     ! wp_verify_nonce( $_POST['ymkrf_vmail_nonce'], 'ymkrf_vmail_save' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	if ( get_post_meta( $post_id, '_ymkrf_mail_send', true ) !== '1' ) return;
	if ( get_post_meta( $post_id, '_ymkrf_mail_sent', true ) !== '' ) return;

	$r = ymkrf_vmail_send( $post_id );
	set_transient( 'ymkrf_vmail_note_' . $post_id, $r, 60 );
}, 70 );

/** 「いま送る」ボタン */
add_action( 'admin_init', function () {
	if ( empty( $_GET['ymkrf_vmail_now'] ) || empty( $_GET['post'] ) ) return;
	$pid = (int) $_GET['post'];
	if ( ! wp_verify_nonce( $_GET['ymkrf_vmail_now_nonce'] ?? '', 'ymkrf_vmail_now_' . $pid ) ) return;
	if ( ! current_user_can( 'edit_post', $pid ) ) return;

	$r = ymkrf_vmail_send( $pid, true );
	set_transient( 'ymkrf_vmail_note_' . $pid, $r, 60 );

	wp_safe_redirect( admin_url( 'post.php?post=' . $pid . '&action=edit' ) );
	exit;
} );

/** 結果のおしらせ */
add_action( 'admin_notices', function () {
	$s = get_current_screen();
	if ( ! $s || $s->post_type !== 'ymkrf_voice' || $s->base !== 'post' ) return;

	$pid = (int) ( $_GET['post'] ?? 0 );
	if ( ! $pid ) return;

	$r = get_transient( 'ymkrf_vmail_note_' . $pid );
	if ( ! is_array( $r ) ) return;
	delete_transient( 'ymkrf_vmail_note_' . $pid );

	if ( ! empty( $r['ok'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>'
		   . 'お知らせメールを出しました。送りさき：' . esc_html( $r['to'] )
		   . '</p></div>';
	} else {
		echo '<div class="notice notice-error is-dismissible"><p>'
		   . 'お知らせメールは送っていません。' . esc_html( $r['why'] )
		   . '</p></div>';
	}
} );


/* ============================================================
   7. 送信の記録
   ============================================================ */

function ymkrf_vmail_log( $post_id, $to, $ok ) {
	$log = get_option( 'ymkrf_vmail_log' );
	if ( ! is_array( $log ) ) $log = array();

	array_unshift( $log, array(
		'time' => current_time( 'Y-m-d H:i' ),
		'id'   => (int) $post_id,
		'to'   => (string) $to,
		'ok'   => $ok ? 1 : 0,
	) );
	if ( count( $log ) > 50 ) $log = array_slice( $log, 0, 50 );

	update_option( 'ymkrf_vmail_log', $log, false );
}


/* ============================================================
   8. 一覧に「連絡ずみ」の印
   ============================================================ */

add_filter( 'manage_ymkrf_voice_posts_columns', function ( $cols ) {
	$new = array();
	foreach ( $cols as $k => $v ) {
		$new[ $k ] = $v;
		if ( $k === 'title' ) $new['ymkrf_vmail'] = '担当店へ連絡';
	}
	return $new;
}, 30 );

add_action( 'manage_ymkrf_voice_posts_custom_column', function ( $col, $post_id ) {
	if ( $col !== 'ymkrf_vmail' ) return;

	$sent  = (string) get_post_meta( $post_id, '_ymkrf_mail_sent', true );
	$claim = ( get_post_meta( $post_id, '_ymkrf_claim', true ) === '1' );

	if ( $claim ) {
		echo '<span style="color:#d63638;font-weight:bold">クレーム</span><br>';
	}
	echo $sent !== ''
		? '<span style="color:#00782a">連絡ずみ</span><br><span style="color:#888;font-size:11px">'
		  . esc_html( mb_substr( $sent, 0, 10 ) ) . '</span>'
		: '<span style="color:#aaa">—</span>';
}, 10, 2 );
