<?php
/**
 * functions-voice-swap.php ─ アンケートの画像を、きれいなものに入れ直します
 *
 * 置き場所： wp-content/themes/ymkrf/inc/functions-voice-swap.php
 *
 * （2026/09/16 ユーザー指示
 *   「案件番号とアンケートの画像の内容が一致していたら、入れ替えてほしい」）
 *
 * ■ 使いかた
 *   ① パソコンの
 *        Desktop\アンケート未登録\済
 *      の中身を、まるごと
 *        wp-content\ymkrf-survey-in\
 *      にコピーします（エクスプローラーでドラッグするだけです）
 *   ② 管理画面の「お客様の声 ＞ アンケートの入れ直し」を開きます
 *   ③ 「入れ替える」を押すと、順番に処理します
 *
 * ■ 安全のためのきまり
 *   ・ファイル名の中の案件番号（2502-0029 の形）で相手を見つけます
 *   ・ブラウザの中でA3の「仕事の通信簿」の枠を読み取り、
 *     枠が合ったものだけ入れ替えます。
 *     ハガキサイズなど様式のちがうものは、自動で飛ばします。
 *   ・入れ替えるのは、かたむきを直したフォルダの画像そのままです。
 *     こちらで消したり塗ったりはしません
 *     （2026/09/16 ユーザー「勝手に内容削除はしないで」
 *       「基本はこちらで名前など入っていたら塗りつぶして入れています」）。
 *   ・入れ替える前の古い画像は、メディアから消します
 *     （2026/09/16 ユーザー「前のは削除してください」）。
 *   ・画像は幅2400pxで作ります。
 *
 * ■ 終わったら
 *   この行とファイル、wp-content\ymkrf-survey-in\ を消してください。
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/** 入れ直すもとの画像を置くフォルダ */
if ( ! defined( 'YMKRF_VSWAP_DIR' ) ) define( 'YMKRF_VSWAP_DIR', 'ymkrf-survey-in' );

/** 枠がいくつ合えば「仕事の通信簿」とみなすか */
if ( ! defined( 'YMKRF_VSWAP_VOTES' ) ) define( 'YMKRF_VSWAP_VOTES', 6 );

/** 入れ直しが終わった印（お客様の声に入ります）
 *  これが付いたものは、一覧から消えて、次からも出てきません。
 *  （2026/09/16 ユーザー指示「入替完了したアンケートは、アンケートの入れ直しから削除して」） */
if ( ! defined( 'YMKRF_VSWAP_DONE' ) ) define( 'YMKRF_VSWAP_DONE', '_ymkrf_vswap_done' );


/**
 * 画像を探すフォルダ。上から順に見て、見つかったところを使います。
 *
 * ★パソコンのフォルダ（Desktop\アンケート未登録\済）を直接読みます。
 *   コピーしなくてよいようにするためです（2026/09/16 ユーザー「拾っていってほしい」）。
 *   Windows版のPHPは日本語のフォルダ名をシフトJISで扱うので、
 *   読めなかったときのために、変換した名前でももう一度ためします。
 */
if ( ! function_exists( 'ymkrf_vswap_dirs' ) ) :
function ymkrf_vswap_dirs() {

	$up  = wp_upload_dir();
	$out = array();

	/* ① wp-content の中（コピーした場合） */
	$out[] = dirname( $up['basedir'] ) . '/' . YMKRF_VSWAP_DIR;

	/* ② パソコンのフォルダ（そのまま／シフトJISに変換） */
	$desk = 'C:/Users/マーケティング室/Desktop/アンケート未登録/済';
	$out[] = $desk;
	if ( function_exists( 'mb_convert_encoding' ) ) {
		$out[] = mb_convert_encoding( $desk, 'SJIS-win', 'UTF-8' );
	}

	return $out;
}
endif;

/** 実際に使えるフォルダを1つ返します（無ければ空） */
if ( ! function_exists( 'ymkrf_vswap_dir' ) ) :
function ymkrf_vswap_dir() {
	foreach ( ymkrf_vswap_dirs() as $d ) {
		if ( @is_dir( $d ) ) return $d;
	}
	return '';
}
endif;

/**
 * アンケートではない画像を、名前で外します。
 * （2026/09/16 ユーザー「お役立ちハガキというタイトルの画像はアンケートではありません」）
 * ここに言葉を足せば、その言葉が入った画像は扱わなくなります。
 */
if ( ! function_exists( 'ymkrf_vswap_skip_name' ) ) :
function ymkrf_vswap_skip_name( $file ) {

	$ng = array( 'お役立ちハガキ', 'ハガキ', 'はがき', '葉書', 'かわら版',
	             /* クレームのものは入れ替えません。かわりに「要確認」の印を付けます */
	             'クレーム' );

	/* フォルダの名前がシフトJISのときは、ファイル名もシフトJISで返ってきます */
	$name = $file;
	if ( function_exists( 'mb_detect_encoding' )
	  && ! mb_detect_encoding( $name, 'UTF-8', true ) ) {
		$name = mb_convert_encoding( $name, 'UTF-8', 'SJIS-win' );
	}

	foreach ( $ng as $w ) {
		if ( mb_strpos( $name, $w ) !== false ) return true;
	}

	/* アンケートではないと分かっているもの（案件番号で外します）。
	   画面の「アンケートでない」ボタンで増やせます。 */
	foreach ( ymkrf_vswap_skip_nos() as $no ) {
		if ( $no !== '' && mb_strpos( $name, $no ) !== false ) return true;
	}

	return false;
}
endif;

/** アンケートではない案件番号の一覧（画面から増やせます） */
if ( ! function_exists( 'ymkrf_vswap_skip_nos' ) ) :
function ymkrf_vswap_skip_nos() {
	$v = get_option( 'ymkrf_vswap_skip', array( '2403-0430' ) );
	return is_array( $v ) ? $v : array();
}
endif;

/** 「アンケートでない」を押したとき */
add_action( 'admin_init', function () {

	if ( empty( $_GET['ymkrf_vswap_skip'] ) ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;
	check_admin_referer( 'ymkrf_vswap_skip' );

	$no  = preg_replace( '/[^0-9-]/', '', (string) $_GET['ymkrf_vswap_skip'] );
	$now = ymkrf_vswap_skip_nos();
	if ( $no !== '' && ! in_array( $no, $now, true ) ) {
		$now[] = $no;
		update_option( 'ymkrf_vswap_skip', $now, false );
	}

	wp_safe_redirect( admin_url( 'edit.php?post_type=ymkrf_voice&page=ymkrf-voice-swap' ) );
	exit;
} );

/** 除外リストを空にする */
add_action( 'admin_init', function () {
	if ( empty( $_GET['ymkrf_vswap_skip_clear'] ) ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;
	check_admin_referer( 'ymkrf_vswap_skip' );
	delete_option( 'ymkrf_vswap_skip' );
	wp_safe_redirect( admin_url( 'edit.php?post_type=ymkrf_voice&page=ymkrf-voice-swap' ) );
	exit;
} );

/**
 * もう一度やり直したい案件番号の一覧。
 * ここに入っているものは、入れ直しずみでも、画像がきれいでも、かならず一覧に出します。
 * （2026/09/16 ユーザー「原本はちゃんと映っているのに、一部映っていない」への対応）
 */
if ( ! function_exists( 'ymkrf_vswap_force_nos' ) ) :
function ymkrf_vswap_force_nos() {
	$v = get_option( 'ymkrf_vswap_force', array() );
	return is_array( $v ) ? $v : array();
}
endif;

/** 「この案件番号だけ、もう一度」を押したとき */
add_action( 'admin_init', function () {

	if ( empty( $_POST['ymkrf_vswap_redo'] ) ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;
	check_admin_referer( 'ymkrf_vswap_redo' );

	$no = preg_replace( '/[^0-9-]/', '', (string) $_POST['ymkrf_vswap_redo'] );
	if ( $no !== '' ) {
		$now = ymkrf_vswap_force_nos();
		if ( ! in_array( $no, $now, true ) ) {
			$now[] = $no;
			update_option( 'ymkrf_vswap_force', $now, false );
		}
		$vid = ymkrf_vswap_voice_by_case( $no );
		if ( $vid ) delete_post_meta( $vid, YMKRF_VSWAP_DONE );
	}

	wp_safe_redirect( admin_url( 'edit.php?post_type=ymkrf_voice&page=ymkrf-voice-swap' ) );
	exit;
} );

/**
 * 「このままでよい」を押したとき。
 * その案件は一覧から消えて、次からも出てきません。
 * お客様の声の赤い「解像度が低い」も出なくなります
 * （functions-voice-check.php と同じ印を使います）。
 * （2026/09/16 ユーザー指示「対処したらここからは消して」）
 */
add_action( 'admin_init', function () {

	if ( empty( $_GET['ymkrf_vswap_ok'] ) ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;
	check_admin_referer( 'ymkrf_vswap_skip' );

	$no  = preg_replace( '/[^0-9-]/', '', (string) $_GET['ymkrf_vswap_ok'] );
	$vid = $no !== '' ? ymkrf_vswap_voice_by_case( $no ) : 0;

	if ( $vid ) {
		update_post_meta( $vid,
			defined( 'YMKRF_VCHK_LOWOK' ) ? YMKRF_VCHK_LOWOK : '_ymkrf_lowres_ok', '1' );

		/* 「もう一度」の指定が残っていたら、外します */
		$force = ymkrf_vswap_force_nos();
		if ( in_array( $no, $force, true ) ) {
			update_option( 'ymkrf_vswap_force', array_values( array_diff( $force, array( $no ) ) ), false );
		}
	}

	wp_safe_redirect( admin_url( 'edit.php?post_type=ymkrf_voice&page=ymkrf-voice-swap' ) );
	exit;
} );

/** 「入れ直しずみ」の印をぜんぶ外す（もう一度やり直したいとき用） */
add_action( 'admin_init', function () {
	if ( empty( $_GET['ymkrf_vswap_done_clear'] ) ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;
	check_admin_referer( 'ymkrf_vswap_skip' );

	global $wpdb;
	$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => YMKRF_VSWAP_DONE ) );

	wp_safe_redirect( admin_url( 'edit.php?post_type=ymkrf_voice&page=ymkrf-voice-swap' ) );
	exit;
} );

/** ファイル名をUTF-8にそろえます（フォルダがシフトJISのとき用） */
if ( ! function_exists( 'ymkrf_vswap_utf8' ) ) :
function ymkrf_vswap_utf8( $s ) {
	if ( function_exists( 'mb_detect_encoding' ) && ! mb_detect_encoding( $s, 'UTF-8', true ) ) {
		return mb_convert_encoding( $s, 'UTF-8', 'SJIS-win' );
	}
	return $s;
}
endif;

/**
 * 「クレーム」と書かれた画像の案件番号に、印をつけます。
 * （2026/09/16 ユーザー指示「クレームと書いてあるものは、
 *   赤字で要確認にして下書きなどにしておいてください」）
 *
 * 印がつくと、お客様の声の一覧に赤い「要確認」が出て、
 * 公開中のものは「非公開」にもどります。
 */
if ( ! function_exists( 'ymkrf_vswap_mark_claims' ) ) :
function ymkrf_vswap_mark_claims() {

	$dir = ymkrf_vswap_dir();
	if ( $dir === '' ) return array( 'found' => 0, 'marked' => 0, 'drafted' => 0 );

	$found = 0; $marked = 0; $drafted = 0;

	foreach ( (array) @scandir( $dir ) as $f ) {

		if ( $f === '.' || $f === '..' ) continue;
		if ( ! preg_match( '/\.(jpe?g|png)$/i', $f ) ) continue;

		$name = ymkrf_vswap_utf8( $f );
		if ( mb_strpos( $name, 'クレーム' ) === false ) continue;
		if ( ! preg_match( '/(\d{4}-\d{4})/', $name, $m ) ) continue;

		$found++;
		$vid = ymkrf_vswap_voice_by_case( $m[1] );
		if ( ! $vid ) continue;

		if ( get_post_meta( $vid, '_ymkrf_claim', true ) !== '1' ) {
			update_post_meta( $vid, '_ymkrf_claim', '1' );
			$marked++;
		}

		/* 要確認の印を付けなおします */
		if ( function_exists( 'ymkrf_vchk_reasons' ) ) {
			$r = ymkrf_vchk_reasons( $vid );
			if ( $r ) update_post_meta( $vid, YMKRF_VCHK_META, $r );
		}

		/* クレームのアンケートは「非公開」にします
		   （2026/09/16 ユーザー指示「クレームのアンケートは、下書きではなく、非公開にして」） */
		if ( get_post_status( $vid ) === 'publish' ) {
			wp_update_post( array( 'ID' => $vid, 'post_status' => 'private' ) );
			$drafted++;
		}
	}

	return array( 'found' => $found, 'marked' => $marked, 'drafted' => $drafted );
}
endif;

/** フォルダの中の画像を、案件番号ごとにまとめます */
if ( ! function_exists( 'ymkrf_vswap_files' ) ) :
function ymkrf_vswap_files() {

	$dir = ymkrf_vswap_dir();
	if ( $dir === '' ) return array();

	$out = array();
	foreach ( (array) @scandir( $dir ) as $f ) {
		if ( $f === '.' || $f === '..' ) continue;
		if ( ! preg_match( '/\.(jpe?g|png)$/i', $f ) ) continue;
		if ( ymkrf_vswap_skip_name( $f ) ) continue;
		if ( ! preg_match( '/(\d{4}-\d{4})/', $f, $m ) ) continue;
		$no = $m[1];
		if ( ! isset( $out[ $no ] ) ) $out[ $no ] = array();
		$out[ $no ][] = $f;
	}
	ksort( $out );
	return $out;
}
endif;

/**
 * 画像を1枚だけ、ブラウザに送ります。
 * パソコンのフォルダはウェブから見えないので、ここを通して渡します。
 * 管理者だけ、そのフォルダの中の画像だけ、が通れます。
 */
add_action( 'wp_ajax_ymkrf_vswap_img', function () {

	if ( ! current_user_can( 'manage_options' ) ) wp_die( '', '', array( 'response' => 403 ) );
	check_admin_referer( 'ymkrf_vswap', 'nonce' );

	$dir = ymkrf_vswap_dir();
	$f   = isset( $_GET['f'] ) ? wp_unslash( (string) $_GET['f'] ) : '';
	$f   = str_replace( array( '..', '/', '\\' ), '', $f );      /* ほかの場所は見せません */

	if ( $dir === '' || $f === '' ) wp_die( '', '', array( 'response' => 404 ) );
	if ( ! preg_match( '/\.(jpe?g|png)$/i', $f ) ) wp_die( '', '', array( 'response' => 404 ) );

	$path = $dir . '/' . $f;
	if ( ! @is_file( $path ) ) wp_die( '', '', array( 'response' => 404 ) );

	$ext = strtolower( pathinfo( $f, PATHINFO_EXTENSION ) );
	header( 'Content-Type: ' . ( $ext === 'png' ? 'image/png' : 'image/jpeg' ) );
	header( 'Cache-Control: private, max-age=600' );
	@readfile( $path );
	exit;
} );

/** 案件番号から、お客様の声を引きます */
if ( ! function_exists( 'ymkrf_vswap_voice_by_case' ) ) :
function ymkrf_vswap_voice_by_case( $no ) {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT p.ID FROM {$wpdb->posts} p
		   JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_ymkrf_case_no'
		  WHERE p.post_type = 'ymkrf_voice' AND p.post_status <> 'trash'
		    AND m.meta_value = %s
		  LIMIT 1", $no
	) );
}
endif;

/** いまの公開用画像の幅 */
if ( ! function_exists( 'ymkrf_vswap_now_width' ) ) :
function ymkrf_vswap_now_width( $voice_id ) {
	$att = (int) get_post_meta( $voice_id, '_ymkrf_survey_pub_id', true );
	if ( ! $att ) $att = (int) get_post_meta( $voice_id, '_ymkrf_survey_id', true );
	if ( ! $att ) return 0;
	$m = wp_get_attachment_metadata( $att );
	return ! empty( $m['width'] ) ? (int) $m['width'] : 0;
}
endif;


/* ------------------------------------------------------------
   画面
   ------------------------------------------------------------ */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=ymkrf_voice',
		'アンケートの入れ直し', 'アンケートの入れ直し',
		'manage_options', 'ymkrf-voice-swap', 'ymkrf_vswap_page'
	);
}, 31 );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( strpos( (string) $hook, 'ymkrf-voice-swap' ) === false ) return;
	$dir = get_stylesheet_directory_uri();
	wp_enqueue_script( 'ymkrf-voice-admin', $dir . '/assets/js/voice-admin.js',
		array( 'jquery' ), defined( 'YMKRF_VER' ) ? YMKRF_VER : '1.0', true );
	wp_localize_script( 'ymkrf-voice-admin', 'YMKRF_VSWAP', array(
		'ajax'  => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'ymkrf_vswap' ),
		'votes' => YMKRF_VSWAP_VOTES,
	) );
} );

if ( ! function_exists( 'ymkrf_vswap_page' ) ) :
function ymkrf_vswap_page() {

	if ( ! current_user_can( 'manage_options' ) ) return;

	$dir   = ymkrf_vswap_dir();
	$files = ymkrf_vswap_files();

	/* パソコンのフォルダの画像は、ウェブから直接は見えないので、
	   admin-ajax.php を通して出します。 */
	$nonce = wp_create_nonce( 'ymkrf_vswap' );
	$imgurl = function ( $file ) use ( $nonce ) {
		return admin_url( 'admin-ajax.php' ) . '?action=ymkrf_vswap_img&nonce=' . $nonce
		     . '&f=' . rawurlencode( $file );
	};

	$rows  = array();
	$done  = 0;
	$force = ymkrf_vswap_force_nos();
	foreach ( $files as $no => $list ) {
		$vid = ymkrf_vswap_voice_by_case( $no );
		if ( ! $vid ) continue;                       /* 登録されていないものは、ここでは扱いません */

		$now = ymkrf_vswap_now_width( $vid );

		/* 「この案件番号だけ、もう一度」で指定したものは、かならず出します */
		if ( ! in_array( (string) $no, $force, true ) ) {

			/* 入れ直しずみのものは、もう出しません
			   （2026/09/16 ユーザー指示「入替完了したアンケートは、アンケートの入れ直しから削除して」） */
			if ( get_post_meta( $vid, YMKRF_VSWAP_DONE, true ) !== '' ) { $done++; continue; }

			/* 手をつけおわったものも、もう出しません。
			   ・もう十分きれいなもの（2000px以上）
			   ・「このままでよい」「確認しました」を押したもの
			   （2026/09/16 ユーザー指示「対処したらここからは消して。
			     何件残っているか分かりにくい」） */
			if ( function_exists( 'ymkrf_vchk_lowres' ) ) {
				if ( ymkrf_vchk_lowres( $vid ) === 0 ) { $done++; continue; }
			} elseif ( $now >= 2000 ) {
				$done++; continue;
			}
		}

		$rows[] = array( 'no' => $no, 'file' => $list[0], 'voice' => $vid, 'now' => $now );
	}
	?>
	<div class="wrap ymkrf-vswap">
	  <h1>アンケートの入れ直し</h1>

	  <?php if ( $dir === '' ) : ?>
	    <div class="notice notice-error"><p>
	      アンケートの画像が入ったフォルダが見つかりませんでした。<br>
	      <code>C:\Users\マーケティング室\Desktop\アンケート未登録\済</code> を見にいきましたが、開けませんでした。<br>
	      （フォルダ名に日本語が入っていると、うまく読めないことがあります）<br>
	      うまくいかないときは、中身を
	      <code><?php echo esc_html( dirname( wp_upload_dir()['basedir'] ) . '/' . YMKRF_VSWAP_DIR ); ?></code>
	      にコピーしてください。
	    </p></div>
	  <?php else : ?>

	  <p style="font-size:12.5px;color:#50575e">
	    読んでいるフォルダ：<code><?php echo esc_html( $dir ); ?></code>
	  </p>

	  <p class="ymkrf-vswap__lead">
	    フォルダの画像と、登録ずみのお客様の声を<b>案件番号</b>で結びつけます。<br>
	    ブラウザの中でA3の「仕事の通信簿」の枠を読み取り、<b>枠が合ったものだけ</b>入れ替えます。
	    ハガキサイズなど様式のちがうものは、自動で飛ばします。<br>
	    入れるのは、<b>かたむきを直したフォルダの画像そのまま</b>（幅2400px）です。
	    こちらで消したり塗ったりは、いっさいしません（2026/09/16 ご指示）。
	  </p>

	  <div class="notice notice-warning" style="max-width:900px">
	    <p style="font-size:13.5px;line-height:1.9">
	      <b>画像の中身は、いっさい消しません。</b>フォルダの画像をそのまま入れます。<br>
	      フォルダの画像は、中川さんがすでにお名前を塗って用意されたものです。
	      「小松市S様」のようなイニシャルは、そのまま公開してかまいません。<br>
	      <span style="color:#50575e">
	        ※ 入れ替えるのは画像だけです。点数やご感想などの入力内容には、いっさい触れません。<br>
	        ※ 入れ替える前の古い画像は、メディアから消します。
	      </span>
	    </p>
	  </div>

	  <?php
	  /* 「クレーム」と書かれたものに、要確認の印を付けます（開くたびに確かめます） */
	  $cl = ymkrf_vswap_mark_claims();
	  ?>
	  <div class="ymkrf-vswap__sum">
	    <span>フォルダの画像　<b><?php echo count( $files ); ?></b> 件</span>
	    <span>入れ直せるもの　<b><?php echo count( $rows ); ?></b> 件</span>
	    <span>手をつけおわったもの　<b style="color:#118a3d"><?php echo (int) $done; ?></b> 件</span>
	    <span>クレームの印　<b style="color:#b32d2e"><?php echo (int) $cl['found']; ?></b> 件</span>
	  </div>

	  <?php if ( $cl['marked'] || $cl['drafted'] ) : ?>
	    <div class="notice notice-warning"><p>
	      「クレーム」と書かれたアンケートに、<b style="color:#b32d2e">要確認</b>の印を付けました
	      （<?php echo (int) $cl['marked']; ?>件）。
	      <?php if ( $cl['drafted'] ) : ?>
	        うち <b><?php echo (int) $cl['drafted']; ?></b> 件は公開中だったので、<b>非公開</b>にしました。
	      <?php endif; ?>
	    </p></div>
	  <?php endif; ?>

	  <?php if ( $done ) : ?>
	    <p style="font-size:12.5px;color:#50575e;max-width:900px">
	      手をつけおわった <b><?php echo (int) $done; ?></b> 件は、この一覧から消えています。次からも出てきません。<br>
	      （入れ直したもの／すでにきれいなもの／「このままでよい」を押したもの）
	      　<a href="<?php echo esc_url( wp_nonce_url(
	          add_query_arg( 'ymkrf_vswap_done_clear', '1',
	            admin_url( 'edit.php?post_type=ymkrf_voice&page=ymkrf-voice-swap' ) ),
	          'ymkrf_vswap_skip' ) ); ?>"
	         onclick="return confirm('入れ直しずみの印をぜんぶ外して、もう一度一覧に出しますか？\n（画像はそのままです）');"
	         >もう一度やり直す</a>
	    </p>
	  <?php endif; ?>

	  <form method="post" style="margin:10px 0;font-size:12.5px;color:#50575e">
	    <?php wp_nonce_field( 'ymkrf_vswap_redo' ); ?>
	    うまくいかなかったものがあれば、その案件番号だけ、もう一度出せます：
	    <input type="text" name="ymkrf_vswap_redo" placeholder="2405-0447" style="width:120px">
	    <button class="button button-small">もう一度出す</button>
	    <?php $forced = ymkrf_vswap_force_nos();
	          if ( $forced ) : ?>
	      <br>いま出しなおしているもの：<b><?php echo esc_html( implode( '、', $forced ) ); ?></b>
	    <?php endif; ?>
	  </form>

	  <?php $skips = ymkrf_vswap_skip_nos(); ?>
	  <p style="font-size:12.5px;color:#50575e;max-width:900px">
	    アンケートでないものが混じっていたら、その行の「<b>アンケートでない</b>」を押してください。
	    一覧から消えて、次からも出てきません。<br>
	    入れ直さなくてよいと決めたものは「<b>このままでよい</b>」を押してください。
	    こちらも一覧から消えて、お客様の声の赤い「解像度が低い」も出なくなります。
	    <?php if ( $skips ) : ?>
	      <br>いま外しているもの（<?php echo count( $skips ); ?>件）：
	      <?php echo esc_html( implode( '、', $skips ) ); ?>
	      　<a href="<?php echo esc_url( wp_nonce_url(
	          add_query_arg( 'ymkrf_vswap_skip_clear', '1',
	            admin_url( 'edit.php?post_type=ymkrf_voice&page=ymkrf-voice-swap' ) ),
	          'ymkrf_vswap_skip' ) ); ?>">ぜんぶ戻す</a>
	    <?php endif; ?>
	  </p>

	  <div class="notice notice-info" style="max-width:900px"><p style="font-size:13.5px">
	    <b>混じっていても、押して大丈夫です。</b>
	    アンケートでないものは枠が合わないので「様式がちがうようです」と出て、そのまま飛ばします。
	    画像が入れ替わることはありません。
	  </p></div>

	  <?php if ( $rows ) : ?>
	    <p>
	      <button class="button button-primary button-hero" id="ymkrf-vswap-go">
	        入れ替えをはじめる（<?php echo count( $rows ); ?>件）
	      </button>
	      <span id="ymkrf-vswap-st" style="margin-left:14px;font-weight:700"></span>
	    </p>

	    <!-- 入れ替えが終わったものは、上の表から消して、ここに出します（確認用） -->
	    <div id="ymkrf-vswap-done-wrap" style="display:none;margin:16px 0 22px">
	      <h2 style="margin-bottom:6px">入れ替えました（一覧からは消しました）</h2>
	      <p class="description" style="margin-top:0">
	        白い塗りつぶしが「ご紹介（　様）」の欄に当たっているか、ここで目で確かめてください。<br>
	        ずれているものがあれば、その案件番号を教えてください。もとにもどします。
	      </p>
	      <div id="ymkrf-vswap-done" style="display:flex;flex-wrap:wrap;gap:14px"></div>
	    </div>

	    <table class="widefat striped" id="ymkrf-vswap-tbl">
	      <thead><tr>
	        <th style="width:110px">案件番号</th>
	        <th style="width:150px">フォルダの画像</th>
	        <th style="width:150px">いまの画像</th>
	        <th style="width:90px">いまの幅</th>
	        <th>結果</th>
	      </tr></thead>
	      <tbody>
	        <?php foreach ( $rows as $r ) :
	          $att = (int) get_post_meta( $r['voice'], '_ymkrf_survey_pub_id', true );
	          if ( ! $att ) $att = (int) get_post_meta( $r['voice'], '_ymkrf_survey_id', true );
	          $nowurl = $att ? wp_get_attachment_image_url( $att, 'medium' ) : '';
	        ?>
	          <tr data-no="<?php echo esc_attr( $r['no'] ); ?>"
	              data-voice="<?php echo (int) $r['voice']; ?>"
	              data-file="<?php echo esc_attr( $r['file'] ); ?>"
	              data-src="<?php echo esc_url( $imgurl( $r['file'] ) ); ?>">
	            <td><b><?php echo esc_html( $r['no'] ); ?></b><br>
	              <a href="<?php echo esc_url( get_edit_post_link( $r['voice'] ) ); ?>"
	                 target="_blank" rel="noopener">編集画面</a><br>
	              <a class="button button-small" style="margin-top:6px"
	                 href="<?php echo esc_url( wp_nonce_url(
	                   add_query_arg( 'ymkrf_vswap_skip', $r['no'],
	                     admin_url( 'edit.php?post_type=ymkrf_voice&page=ymkrf-voice-swap' ) ),
	                   'ymkrf_vswap_skip' ) ); ?>">アンケートでない</a>
	              <a class="button button-small" style="margin-top:4px"
	                 href="<?php echo esc_url( wp_nonce_url(
	                   add_query_arg( 'ymkrf_vswap_ok', $r['no'],
	                     admin_url( 'edit.php?post_type=ymkrf_voice&page=ymkrf-voice-swap' ) ),
	                   'ymkrf_vswap_skip' ) ); ?>">このままでよい</a></td>
	            <td><img src="<?php echo esc_url( $imgurl( $r['file'] ) ); ?>"
	                     style="width:140px;height:auto;border:1px solid #dcdcde" alt=""></td>
	            <td><?php if ( $nowurl ) : ?>
	                  <img src="<?php echo esc_url( $nowurl ); ?>"
	                       style="width:140px;height:auto;border:1px solid #dcdcde" alt="">
	                <?php else : ?><span style="color:#b32d2e">画像なし</span><?php endif; ?></td>
	            <td><?php echo $r['now'] ? (int) $r['now'] . 'px' : '—'; ?></td>
	            <td class="ymkrf-vswap__res">まだです</td>
	          </tr>
	        <?php endforeach; ?>
	      </tbody>
	    </table>
	  <?php else : ?>
	    <p>入れ直せるものは見つかりませんでした。</p>
	  <?php endif; ?>

	  <?php endif; ?>
	</div>

	<style>
	  .ymkrf-vswap__lead{max-width:900px;font-size:13.5px;line-height:1.9}
	  .ymkrf-vswap__sum{display:flex;gap:18px;margin:14px 0 16px;padding:12px 16px;
	    background:#fff;border:1px solid #dcdcde;border-radius:6px;max-width:900px;font-size:13.5px}
	  .ymkrf-vswap__sum b{font-size:16px;color:#00782a}
	  .ymkrf-vswap__res{font-size:13px}
	  .is-ok{color:#118a3d;font-weight:700}
	  .is-ng{color:#b32d2e;font-weight:700}
	  .is-skip{color:#8a6100;font-weight:700}
	</style>

	<script>
	jQuery(function ($) {

	  var $go = $('#ymkrf-vswap-go'), $st = $('#ymkrf-vswap-st');
	  if (!$go.length) return;

	  $go.on('click', function (e) {
	    e.preventDefault();
	    if (!window.YmkrfSurvey) { $st.text('読み取りの部品が読み込めませんでした'); return; }
	    $go.prop('disabled', true);
	    var rows = $('#ymkrf-vswap-tbl tbody tr').toArray();
	    var i = 0, ok = 0, skip = 0, ng = 0;

	    function next() {
	      if (i >= rows.length) {
	        $st.text('終わりました。入れ替え ' + ok + ' 件／様式ちがい ' + skip + ' 件／できなかった ' + ng + ' 件'
                 + (ok ? '（入れ替えた ' + ok + ' 件は一覧から消しました）' : ''));
	        $go.prop('disabled', false);
	        return;
	      }
	      var $tr = $(rows[i]), $res = $tr.find('.ymkrf-vswap__res');
	      $st.text('処理中… ' + (i + 1) + ' / ' + rows.length);
	      $res.text('読み取っています…');

	      var img = new Image();
	      /* 同じサイトの中から出すので、crossOrigin は付けません */
	      img.onload = function () {
	        var R = null;
	        try { R = window.YmkrfSurvey.read(img); } catch (err) { R = null; }

	        if (!R || R.votes < YMKRF_VSWAP.votes) {
	          $res.attr('class', 'ymkrf-vswap__res is-skip')
	              .text('様式がちがうようです（一致 ' + (R ? R.votes : 0) + '個）。飛ばしました');
	          skip++; i++; setTimeout(next, 10); return;
	        }

	        var pub;
	        try { pub = window.YmkrfSurvey.publicImage(R, 2400); }
	        catch (err) {
	          $res.attr('class', 'ymkrf-vswap__res is-ng').text('画像を作れませんでした');
	          ng++; i++; setTimeout(next, 10); return;
	        }

	        $res.text('保存しています…');
	        $.post(YMKRF_VSWAP.ajax, {
	          action: 'ymkrf_vswap_save',
	          nonce:  YMKRF_VSWAP.nonce,
	          voice:  $tr.data('voice'),
	          no:     $tr.data('no'),
	          data:   pub.toDataURL('image/jpeg', 0.9)
        }).done(function (res) {
	          if (res && res.success) {
	            /* 終わったものは、上の表から消して、下の「入れ替えました」に移します。
	               （2026/09/16 ユーザー指示
	                 「入替完了したアンケートは、アンケートの入れ直しから削除して」）
	               できあがった画像も出して、白い塗りつぶしが
	               「ご紹介（　様）」の欄に当たっているか目で確かめられるようにします。 */
	            $('#ymkrf-vswap-done-wrap').show();
	            $('#ymkrf-vswap-done').append(
	              '<figure style="margin:0;width:260px">'
	              + '<img src="' + pub.toDataURL('image/jpeg', 0.6) + '" '
	              + 'style="width:260px;height:auto;border:1px solid #dcdcde">'
	              + '<figcaption style="font-size:12.5px;margin-top:4px">'
	              + '<b>' + $tr.data('no') + '</b> '
	              + '<span class="is-ok">入れ替えました（' + res.data.w + 'px）</span>'
	              + '</figcaption></figure>'
	            );
	            $tr.remove();
	            ok++;
	          } else {
	            $res.attr('class', 'ymkrf-vswap__res is-ng')
	                .text((res && res.data) ? res.data : '保存できませんでした');
	            ng++;
	          }
	          i++; setTimeout(next, 10);
	        }).fail(function () {
	          $res.attr('class', 'ymkrf-vswap__res is-ng').text('通信できませんでした');
	          ng++; i++; setTimeout(next, 10);
	        });
	      };
	      img.onerror = function () {
	        $res.attr('class', 'ymkrf-vswap__res is-ng').text('画像を読み込めませんでした');
	        ng++; i++; setTimeout(next, 10);
	      };
	      img.src = $tr.data('src');
	    }
	    next();
	  });
	});
	</script>
	<?php
}
endif;


/* ------------------------------------------------------------
   受け取って保存します
   ------------------------------------------------------------ */
add_action( 'wp_ajax_ymkrf_vswap_save', function () {

	check_ajax_referer( 'ymkrf_vswap', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( '権限がありません' );

	$vid = isset( $_POST['voice'] ) ? (int) $_POST['voice'] : 0;
	$no  = isset( $_POST['no'] ) ? preg_replace( '/[^0-9A-Za-z-]/', '', (string) $_POST['no'] ) : '';
	if ( ! $vid || $no === '' ) wp_send_json_error( '相手が分かりません' );

	$data = isset( $_POST['data'] ) ? (string) $_POST['data'] : '';
	if ( strpos( $data, 'data:image/jpeg;base64,' ) !== 0 ) wp_send_json_error( '画像を受け取れませんでした' );
	$bin = base64_decode( substr( $data, strlen( 'data:image/jpeg;base64,' ) ) );
	if ( ! $bin ) wp_send_json_error( '画像を読み取れませんでした' );

	/* 前の画像を覚えておきます（あとで消します） */
	$old = array(
		(int) get_post_meta( $vid, '_ymkrf_survey_pub_id', true ),
		(int) get_post_meta( $vid, '_ymkrf_survey_id', true ),
	);

	/* uploads/voice/<案件番号>.jpg に置きます */
	$name = 'voice/' . strtolower( $no ) . '.jpg';
	$up   = wp_upload_dir();
	$path = untrailingslashit( $up['basedir'] ) . '/' . $name;
	wp_mkdir_p( dirname( $path ) );

	/* 同じ名前が残っていたら、いったんどけます */
	if ( file_exists( $path ) ) @unlink( $path );

	if ( file_put_contents( $path, $bin ) === false ) wp_send_json_error( 'ファイルを書けませんでした' );

	require_once ABSPATH . 'wp-admin/includes/image.php';

	$att = wp_insert_attachment( array(
		'post_mime_type' => 'image/jpeg',
		'post_title'     => 'お客様アンケート ' . $no,
		'post_status'    => 'inherit',
	), $path, $vid );
	if ( is_wp_error( $att ) || ! $att ) wp_send_json_error( 'メディアに入れられませんでした' );

	wp_update_attachment_metadata( $att, wp_generate_attachment_metadata( $att, $path ) );
	update_post_meta( $att, '_ymkrf_is_survey_public', '1' );


	/* 原本も公開用も、この1枚にします（同じURL） */
	update_post_meta( $vid, '_ymkrf_survey_id', $att );
	update_post_meta( $vid, '_ymkrf_survey_pub_id', $att );

	/* 入れ直しが終わった印。次からは一覧に出しません
	   （2026/09/16 ユーザー指示「入替完了したアンケートは、アンケートの入れ直しから削除して」） */
	update_post_meta( $vid, YMKRF_VSWAP_DONE, current_time( 'mysql' ) );

	/* 「もう一度」の指定が残っていたら、外します */
	$force = ymkrf_vswap_force_nos();
	if ( in_array( $no, $force, true ) ) {
		update_option( 'ymkrf_vswap_force', array_values( array_diff( $force, array( $no ) ) ), false );
	}

	/* 前の画像を片づけます
	   （2026/09/16 ユーザー指示「前のは削除してください」）
	   入れ替えたあと、古いほうはメディアから消します。
	   ※「勝手に内容削除はしないで」は画像の中身を白く塗ることについてのご指示で、
	     こちらの入れ替え前の画像は消してよい、とのことです。 */
	foreach ( array_unique( array_filter( $old ) ) as $o ) {
		if ( (int) $o !== (int) $att ) wp_delete_attachment( (int) $o, true );
	}

	if ( function_exists( 'ymkrf_media_forget' ) ) ymkrf_media_forget();

	$m = wp_get_attachment_metadata( $att );
	wp_send_json_success( array(
		'id' => $att,
		'w'  => ! empty( $m['width'] ) ? (int) $m['width'] : 0,
	) );
} );
