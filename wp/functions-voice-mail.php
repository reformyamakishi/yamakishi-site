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
 *  8. 一覧の「担当店へ連絡」（済／未 ＋ クレーム）
 *  9. ★1回だけ★ いままでのぶんを「済」にそろえる
 *
 * ── 名前について ──────────────────────────────
 * 設定の保存さき … ymkrf_vmail（ひとつの箱にまとめています）
 * 送信の記録 …… ymkrf_vmail_log（新しい順に50件まで）
 * 入力欄 …… _ymkrf_mail_send  担当店へ連絡する（1なら送る）
 *             _ymkrf_claim      クレーム（1なら有）
 *             _ymkrf_mail_name  件名（○○邸○○リフォーム）。メールの中だけで使います
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


/* ------------------------------------------------------------
   この仕組みで使う入力欄は、ぜんぶ社内用です。
   ホームページ（フロント）には、いっさい出しません。
   （2026/09/16 ユーザー「もちろんその内容はフロントでは非公開にしてね」）

   ・名前が _ ではじまるので、WordPress が「守られた欄」としてあつかいます
   ・ページのテンプレート（single-ymkrf_voice.php など）でも呼んでいません
   ・念のため、下で「守られた欄」だとはっきり指定しておきます
   ------------------------------------------------------------ */
add_filter( 'is_protected_meta', function ( $protected, $key ) {
	$mine = array(
		'_ymkrf_mail_name',   /* 件名（○○邸○○リフォーム） */
		'_ymkrf_mail_send',
		'_ymkrf_mail_sent',
		'_ymkrf_claim',
		'_ymkrf_staff_mail',
	);
	return in_array( $key, $mine, true ) ? true : $protected;
}, 10, 2 );


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
			: 'このサーバーからはメールを出せませんでした。'
			  . 'パソコンのXAMPPにはメールの仕組みが入っていないので、制作中はこれでふつうです。'
			  . '下の「メールが送れないとき」をお読みください。';
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

	  <details style="max-width:900px;margin:18px 0;padding:12px 16px;background:#fff;
	                  border:1px solid #dcdcde;border-radius:6px">
	    <summary style="cursor:pointer;font-weight:700;font-size:14px">メールが送れないとき</summary>

	    <p style="font-size:13.5px;line-height:1.9">
	      <b>いま作っているパソコン（XAMPP）では、メールは送れません。</b>
	      メールを出す仕組みが入っていないためです。こわれているわけではありません。<br>
	      本番のサーバーにうつせば、お問い合わせフォームと同じように送れるようになります。
	    </p>

	    <p style="font-size:13.5px;line-height:1.9">
	      送れなかったときは、<b>文面をファイルに残します</b>ので、そちらで中身を確かめられます。<br>
	      置き場所：<code><?php echo esc_html( dirname( wp_upload_dir()['basedir'] ) ); ?>\ymkrf-mail\</code><br>
	      メモ帳で開けます。文面や宛名のご確認は、これでできます。
	    </p>

	    <p style="font-size:13.5px;line-height:1.9">
	      どうしてもこのパソコンから実際に送ってみたいときは、
	      <b>WP Mail SMTP</b> というプラグインを入れて、会社のメールサーバーの設定を入れる方法があります。
	      そのときの<b>パスワードは、中川さんご自身で入力してください</b>（こちらでは入力できません）。
	    </p>
	  </details>

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
	  $has    = ymkrf_vmail_staff_with_mail_count();
	  ?>
	  <p style="font-size:13.5px">
	    メールアドレスが入っている人：<b style="color:#00782a"><?php echo (int) $has; ?></b> 人
	  </p>

	  <?php if ( $noaddr ) : ?>
	    <div class="notice notice-info" style="margin:12px 0">
	      <p style="font-size:13.5px;line-height:1.9">
	        <b>メールアドレスを入れていない人（<?php echo count( $noaddr ); ?>人）には、
	        お知らせメールを送りません。</b><br>
	        <span style="color:#50575e">
	          入れていないこと自体は、まちがいではありません。
	          送らなくてよい人は、空のままにしておいてください。
	          送りたくなったら、その人のスタッフ登録にアドレスを入れるだけです。
	        </span>
	      </p>
	      <p style="max-height:9em;overflow:auto;color:#50575e;font-size:12.5px">
	        <?php echo esc_html( implode( '、', $noaddr ) ); ?>
	      </p>
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

/** ある店舗（英字）に所属するスタッフのメールアドレス
 *  アドレスを入れていない人は、はじめから外れます
 *  （2026/09/16 ユーザー「メールアドレスない人にはアンケート送らなくて良いです」） */
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

/** メールアドレスが入っているスタッフの人数 */
function ymkrf_vmail_staff_with_mail_count() {
	$q = new WP_Query( array(
		'post_type'      => 'ymkrf_staff',
		'post_status'    => array( 'publish', 'draft', 'private' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );
	$n = 0;
	foreach ( $q->posts as $sid ) {
		$m = trim( (string) get_post_meta( $sid, '_ymkrf_staff_mail', true ) );
		if ( $m !== '' && is_email( $m ) ) $n++;
	}
	return $n;
}

/** メールアドレスを入れていないスタッフの名前（この人たちには送りません） */
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

/**
 * メールに添付する、アンケート画像のファイルの場所を返します。
 *
 * （2026/09/17 ユーザー指示
 *   「クレームのあったアンケートはそもそも非表示なので、URLは見れません。
 *     なので、クレームの有無に限らずどちらもアンケートの画像を添付してほしい」）
 */
function ymkrf_vmail_files( $post_id ) {

	$att = (int) get_post_meta( $post_id, '_ymkrf_survey_pub_id', true );
	if ( ! $att ) $att = (int) get_post_meta( $post_id, '_ymkrf_survey_id', true );
	if ( ! $att ) return array();

	$path = get_attached_file( $att );
	if ( ! $path || ! @is_file( $path ) ) return array();

	return array( $path );
}

/** メールの本文をつくります */
function ymkrf_vmail_body( $post_id ) {
	$claim = ( get_post_meta( $post_id, '_ymkrf_claim', true ) === '1' );
	$shop  = ymkrf_voice_shop_name( $post_id );
	$staff = ymkrf_vmail_staff_label( $post_id );
	$date  = date_i18n( 'n月j日', get_post_time( 'U', false, $post_id ) );
	$url   = ymkrf_vmail_url( $post_id );

	$name = trim( (string) get_post_meta( $post_id, '_ymkrf_mail_name', true ) );

	$b  = YMKRF_VMAIL_SUBJECT . "\n\n";
	$b .= '到着日：' . $date . "\n";
	if ( $name !== '' ) $b .= '件名：' . $name . "\n";
	$b .= '営業店：' . ( $shop !== '' ? $shop : '（未設定）' ) . "\n";
	$b .= '営業担当者：' . ( $staff !== '' ? $staff : '（未設定）' ) . "\n";
	$b .= 'クレーム：' . ( $claim ? '有 ☑　／　無 □' : '有 □　／　無 ☑' ) . "\n";
	$b .= "\n";

	/* アンケートの画像は、クレームの有無にかかわらず、いつでも添付します */
	if ( ymkrf_vmail_files( $post_id ) ) {
		$b .= "アンケートの画像を、このメールに添付しています。\n";
	} else {
		$b .= "※アンケートの画像が登録されていないため、添付できませんでした。\n";
	}

	/* ページのURLは、クレームでないときだけ出します。
	   クレームのアンケートは非公開なので、URLをお知らせしても開けないためです。
	   （2026/09/17 ユーザー指示「URLはクレームがなかったときだけでOK」） */
	if ( ! $claim ) {
		$b .= "\n";
		$b .= "▼アンケートのページはこちらです\n" . $url . "\n";
		if ( get_post_status( $post_id ) !== 'publish' ) {
			$b .= "（このアンケートは、まだ公開していません）\n";
		}
	}

	if ( $claim ) {
		$b .= "\n";
		$b .= "※内容をご確認いただき、ご対応お願いします。\n";
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

	$body  = ymkrf_vmail_body( $post_id );
	$files = ymkrf_vmail_files( $post_id );
	$ok    = wp_mail( $to, YMKRF_VMAIL_SUBJECT, $body, ymkrf_vmail_headers(), $files );

	if ( $ok ) update_post_meta( $post_id, '_ymkrf_mail_sent', current_time( 'mysql' ) );

	ymkrf_vmail_log( $post_id, implode( ', ', $to ), $ok );

	if ( $ok ) {
		return array( 'ok' => true, 'to' => implode( '、', $to ), 'why' => '' );
	}

	/* 送れなかったときは、文面をファイルに残します。
	   パソコンのXAMPPにはメールを送る仕組みが入っていないので、
	   制作中はこのファイルで中身を確かめられるようにするためです。 */
	$saved = ymkrf_vmail_save_file( $post_id, $to, $body );

	return array(
		'ok'  => false,
		'to'  => implode( '、', $to ),
		'why' => 'このサーバーからはメールを出せませんでした。'
		       . ( $saved ? '文面はファイルに保存しました（' . $saved . '）。' : '' )
		       . 'パソコンのXAMPPにはメールの仕組みが入っていないので、制作中はこれでふつうです。',
	);
}

/** 送れなかったメールの文面を、ファイルに残します */
function ymkrf_vmail_save_file( $post_id, $to, $body ) {

	$up  = wp_upload_dir();
	$dir = dirname( $up['basedir'] ) . '/ymkrf-mail';
	if ( ! wp_mkdir_p( $dir ) ) return '';

	/* 中身がウェブから見えないようにしておきます */
	if ( ! file_exists( $dir . '/index.html' ) ) @file_put_contents( $dir . '/index.html', '' );
	if ( ! file_exists( $dir . '/.htaccess' ) )  @file_put_contents( $dir . '/.htaccess', "Deny from all\n" );

	$no   = trim( (string) get_post_meta( $post_id, '_ymkrf_case_no', true ) );
	if ( $no === '' ) $no = 'id' . (int) $post_id;
	$name = current_time( 'Ymd-His' ) . '_' . preg_replace( '/[^0-9A-Za-z-]/', '', $no ) . '.txt';

	$txt  = "To: " . implode( ', ', (array) $to ) . "\n";
	$txt .= "Subject: " . YMKRF_VMAIL_SUBJECT . "\n";
	foreach ( ymkrf_vmail_files( $post_id ) as $f ) {
		$txt .= "添付: " . basename( $f ) . "\n";
	}
	$txt .= str_repeat( '-', 50 ) . "\n";
	$txt .= $body;

	return ( @file_put_contents( $dir . '/' . $name, $txt ) !== false ) ? 'wp-content/ymkrf-mail/' . $name : '';
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

	$mname = (string) get_post_meta( $post->ID, '_ymkrf_mail_name', true );

	wp_nonce_field( 'ymkrf_vmail_save', 'ymkrf_vmail_nonce' );
	?>
	<p style="margin-top:0;margin-bottom:4px"><b>件名（○○邸○○リフォーム）</b></p>
	<p style="margin-top:0">
	  <input type="text" name="_ymkrf_mail_name" style="width:100%"
	         value="<?php echo esc_attr( $mname ); ?>"
	         placeholder="例：吉田邸キッチンリフォーム">
	</p>
	<p class="description" style="margin-top:-6px">
	  お知らせメールの中に、そのまま入ります。<br>
	  <b style="color:#b32d00">ホームページには出ません。</b>社内へのメールだけに使います。
	</p>

	<hr>

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
	  「有」のアンケートは<b style="color:#b32d00">非公開</b>にします（ログインした人だけが見られます）。
	</p>

	<hr>

	<?php if ( ! empty( $o['test'] ) ) : ?>
	  <p style="background:#fff5f5;border-left:3px solid #d63638;padding:6px 8px;margin:0 0 8px">
	    <b>いまはテスト中です。</b><br>
	    メールは <code><?php echo esc_html( $o['test_to'] ); ?></code> にだけとどきます。
	  </p>
	<?php endif; ?>

	<p style="margin:0 0 6px">
	  担当店へ連絡：
	  <?php if ( $sent !== '' ) : ?>
	    <b style="color:#118a3d;font-size:15px">済</b>
	    <span style="color:#666">（<?php echo esc_html( $sent ); ?>）</span>
	  <?php else : ?>
	    <b style="color:#50575e;font-size:15px">未</b>
	  <?php endif; ?>
	  <?php if ( $claim ) : ?>
	    <b style="color:#d63638"> クレーム</b>
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

	<details style="margin-top:10px">
	  <summary style="cursor:pointer">送る文面を見る</summary>
	  <p class="description" style="margin:6px 0">送りさき：
	    <?php $to = ymkrf_vmail_to( $post->ID );
	          echo $to ? esc_html( implode( '、', $to ) )
	                   : '<span style="color:#b32d00">まだ1つもありません</span>'; ?>
	  </p>
	  <pre style="white-space:pre-wrap;font-size:12px;line-height:1.8;background:#f6f7f7;
	              border:1px solid #dcdcde;padding:8px;max-height:24em;overflow:auto"><?php
	    echo esc_html( ymkrf_vmail_body( $post->ID ) ); ?></pre>
	</details>
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

	/* 件名（○○邸○○リフォーム）。メールの中だけで使います */
	update_post_meta( $post_id, '_ymkrf_mail_name',
		isset( $_POST['_ymkrf_mail_name'] )
			? sanitize_text_field( wp_unslash( $_POST['_ymkrf_mail_name'] ) ) : '' );
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
		if ( $k === 'title' ) $new['ymkrf_vmail'] = '担当店へ連絡';   /* 済／未 ＋ クレーム */
	}
	return $new;
}, 30 );

add_action( 'manage_ymkrf_voice_posts_custom_column', function ( $col, $post_id ) {
	if ( $col !== 'ymkrf_vmail' ) return;

	$sent  = (string) get_post_meta( $post_id, '_ymkrf_mail_sent', true );
	$claim = ( get_post_meta( $post_id, '_ymkrf_claim', true ) === '1' );

	/* 済／未のあとに、クレームなら赤字をならべます
	   （2026/09/16 ユーザー指示「担当者へ連絡した場合は済、まだの場合は未。
	     クレームの場合は 未・済 のあとにクレームと赤字にして」） */
	if ( $sent !== '' ) {
		echo '<span style="color:#118a3d;font-weight:700;font-size:15px">済</span>';
	} else {
		echo '<span style="color:#50575e;font-weight:700;font-size:15px">未</span>';
	}

	if ( $claim ) {
		echo ' <span style="color:#d63638;font-weight:700">クレーム</span>';
	}

	if ( $sent !== '' ) {
		echo '<br><span style="color:#888;font-size:11px">'
		   . esc_html( mb_substr( $sent, 0, 10 ) ) . '</span>';
	}
}, 10, 2 );


/* ============================================================
   9. ★1回だけ★ いままでのアンケートを、ぜんぶ「済」にします
   ------------------------------------------------------------
   （2026/09/16 ユーザー指示「既存のものは全て済にして」）
   この仕組みを作る前に登録したものは、すでに担当店へお伝えずみなので、
   これから改めてメールが飛ばないように「済」にしておきます。
   一度動いたら、もう動きません。
   ============================================================ */
add_action( 'admin_init', function () {

	if ( get_option( 'ymkrf_vmail_backfill_done' ) ) return;
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) return;

	global $wpdb;

	/* 件数がおおいので、SQLでまとめて入れます（1,000件以上あります） */
	$n = $wpdb->query(
		"INSERT INTO {$wpdb->postmeta} ( post_id, meta_key, meta_value )
		 SELECT p.ID, '_ymkrf_mail_sent', p.post_date
		   FROM {$wpdb->posts} p
		  WHERE p.post_type = 'ymkrf_voice'
		    AND p.post_status <> 'trash'
		    AND NOT EXISTS (
		          SELECT 1 FROM {$wpdb->postmeta} m
		           WHERE m.post_id = p.ID AND m.meta_key = '_ymkrf_mail_sent'
		        )"
	);

	update_option( 'ymkrf_vmail_backfill_done', (int) $n, false );

	set_transient( 'ymkrf_vmail_backfill_note', (int) $n, 120 );
}, 6 );

/** 1回だけの「済」そろえが終わったことを、お知らせします */
add_action( 'admin_notices', function () {
	$n = get_transient( 'ymkrf_vmail_backfill_note' );
	if ( $n === false ) return;
	delete_transient( 'ymkrf_vmail_backfill_note' );

	echo '<div class="notice notice-success is-dismissible"><p>'
	   . 'いままでのお客様の声 <b>' . (int) $n . '</b> 件を、担当店へ連絡「<b>済</b>」にしました。'
	   . 'これから登録するものが「未」になります。'
	   . '</p></div>';
} );
