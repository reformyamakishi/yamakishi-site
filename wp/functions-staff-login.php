<?php
/**
 * スタッフのログイン（施工事例の登録・編集）と、だれが編集したかの記録
 * （2026/10/28）
 *
 * ユーザー指示
 *   「スタッフ登録ぺージですが、ダッシュボードのみメールアドレスが一覧でわかるようにしてほしいのと、
 *     全員が施工事例の登録・編集できるにしたい。」
 *   「1人1つ（ログイン）」
 *   「誰がログインして編集したかもわかりようにしてほしい」「ダッシュボード上でね。」
 *
 * 1. スタッフ（ダッシュボード）
 *    - 一覧に「メール（ログインID）」「施工事例のログイン」の列。メールはダッシュボードだけ（ホームページには出しません）。
 *    - 編集画面の右「施工事例のログイン」：ID＝社員のメールアドレス、パスワードは本部が入れる
 *      （ユーザー「施工事例のみ全員、社員のメールアドレスがID、パスワードはこちらで決めるので
 *        ダッシュボードのスタッフぺージに欄を設けて」）。役割は「施工事例スタッフ」（施工事例だけ）。
 *    - パスワードはどこにも残しません。メールも自動では送りません。
 * 2. 施工事例の一覧に「登録・更新した人」の列、編集画面の右に「編集した人の記録」
 *    - 編集画面・クイック編集・ゴミ箱で保存したときに、人と時刻を残します（最新50件）。
 *    - 取り込み（自動）で入ったものは記録しません。
 *
 * 3. 編集の権限（ユーザー「全編集権限は、本部のみ。ログも本部だけが分かるようにして」「編集権限ね。」）
 *    - 本部（管理者、または所属店舗に「本部」が入っているスタッフ）… すべての施工事例を編集できる・記録が見える
 *    - お店のスタッフ … 施工事例の登録はだれでもできる。編集できるのは
 *        「自分のお店（所属店舗・兼任ふくむ）の事例」「自分が担当者の事例」「自分が登録した事例」だけ。
 *        ほかの事例は一覧で見えますが、ひらけません。ゴミ箱に入れられるのは自分が登録したものだけ。
 *    - 「登録・更新した人」の列と「編集した人の記録」は、本部だけに出ます。
 *
 * つなぎ：スタッフ記事 _ymkrf_staff_user ＝ ユーザー番号 ／ ユーザー ymkrf_staff_post ＝ スタッフ記事番号
 * 最後のログイン：ユーザー ymkrf_last_login（UNIX時刻）
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* ============================================================
   0. 共通
   ============================================================ */

/** スタッフ記事 → ユーザー（無ければ null）。メールでも探します */
function ymkrf_staff_user( $staff_id ) {
	$uid = (int) get_post_meta( $staff_id, '_ymkrf_staff_user', true );
	if ( $uid ) {
		$u = get_userdata( $uid );
		if ( $u ) return $u;
	}
	$mail = trim( (string) get_post_meta( $staff_id, '_ymkrf_staff_mail', true ) );
	if ( $mail !== '' && is_email( $mail ) ) {
		$u = get_user_by( 'email', $mail );
		if ( $u ) return $u;
	}
	return null;
}

/** ユーザー → 名前（スタッフ登録があればその名前） */
function ymkrf_user_label( $uid ) {
	$uid = (int) $uid;
	if ( ! $uid ) return '';
	$sid = (int) get_user_meta( $uid, 'ymkrf_staff_post', true );
	if ( $sid && get_post_type( $sid ) === 'ymkrf_staff' ) {
		$t = trim( (string) get_the_title( $sid ) );
		if ( $t !== '' ) return $t;
	}
	$u = get_userdata( $uid );
	return $u ? ( $u->display_name ? $u->display_name : $u->user_login ) : '（消えたユーザー）';
}

/** 時刻の見せかた（今日なら時刻だけ） */
function ymkrf_when( $ts ) {
	$ts = (int) $ts;
	if ( ! $ts ) return '';
	$today = wp_date( 'Y-m-d' ) === wp_date( 'Y-m-d', $ts );
	return $today ? '今日 ' . wp_date( 'H:i', $ts ) : wp_date( 'Y/n/j H:i', $ts );
}

/** このユーザーが施工事例をさわれるか */
function ymkrf_user_can_works( $u ) {
	return $u && ( user_can( $u, 'edit_ymkrf_works' ) || user_can( $u, 'manage_options' ) );
}

/** 本部の人か（管理者、または所属店舗に「本部」があるスタッフ） */
function ymkrf_is_honbu( $user = null ) {
	$u = $user ? ( is_object( $user ) ? $user : get_userdata( (int) $user ) ) : wp_get_current_user();
	if ( ! $u || empty( $u->ID ) ) return false;
	if ( user_can( $u, 'manage_options' ) ) return true;
	$sid = (int) get_user_meta( $u->ID, 'ymkrf_staff_post', true );
	if ( $sid && function_exists( 'ymkrf_staff_get_shops' ) ) {
		return in_array( 'honbu', (array) ymkrf_staff_get_shops( $sid ), true );
	}
	return false;
}

/* 最後のログイン */
add_action( 'wp_login', function ( $login, $user ) {
	if ( $user && ! empty( $user->ID ) ) update_user_meta( $user->ID, 'ymkrf_last_login', time() );
}, 10, 2 );


/* ============================================================
   1. スタッフ：ログイン（ID＝社員のメールアドレス、パスワードは本部が決める）
   ============================================================
   ユーザー「施工事例のみ全員、社員のメールアドレスがID、パスワードはこちらで決めるので
             ダッシュボードのスタッフぺージに欄を設けて」
   - スタッフの編集画面の右「施工事例のログイン」に、パスワードの欄。
     入れて保存すると、ID＝メールアドレス・役割「施工事例スタッフ」のユーザーができます。
     もうあれば、パスワードだけ変わります。空のまま保存すると、何も変わりません。
   - パスワードはどこにも残しません（WordPress のユーザーに暗号にして入るだけ。画面にも二度と出ません）。
   - メールは自動では送りません。本人へのお知らせは口頭・社内連絡でお願いします。
   - 管理者のユーザー（本部で管理者の人）は、ここではパスワードを変えません（ユーザー画面で）。
   - メールアドレスを変えて保存すると、ログインIDも新しいメールにそろえます。
   ============================================================ */

/* ------------------------------------------------------------
   権限（2026/10/29 ユーザー「本部は全権限、あとの人はとりあえず施工事例のみログインして編集可能にして」）
     所属に「本部」がある人 … 管理者（すべての権限）
     そのほか（工事部・各店）… 施工事例スタッフ（施工事例だけ。編集は自分のお店・担当・登録の事例）
   スタッフの画面で作ったユーザー（ID＝メール）だけ、所属に合わせて自動で切りかえます。
   もとからいる管理者（ymkrf-admin など）は、さわりません。自分自身の権限も下げません。
   ------------------------------------------------------------ */

/** このスタッフの画面で作った（＝ここで面倒をみる）ユーザーか */
function ymkrf_staff_managed( $u, $staff_id ) {
	if ( ! $u ) return false;
	return (int) get_user_meta( $u->ID, 'ymkrf_staff_post', true ) === (int) $staff_id
		&& strtolower( $u->user_login ) === strtolower( $u->user_email );
}

/** 所属に「本部」があるスタッフか */
function ymkrf_staff_in_honbu( $staff_id ) {
	return function_exists( 'ymkrf_staff_get_shops' ) && in_array( 'honbu', (array) ymkrf_staff_get_shops( $staff_id ), true );
}

/* ------------------------------------------------------------
   権限：ページごとにえらぶ（2026/10/29 ユーザー「ページごとに全権か、施工事例、コラム、担当店舗のぺージ　選択できるようにして」）
     all    … すべて（ダッシュボード全部）＝ 管理者
     works  … 施工事例
     column … コラム
     store  … 担当店舗のページ（所属しているお店の「店舗ページ」だけ）
   all 以外は組み合わせ自由。スタッフの編集画面の右で✓します。メタ _ymkrf_staff_perms（配列）。
   ✓していない人は「なし」（自動はやめました）。10/29 にログインがある人だけ、前の決め方を✓の形で残しました。
   全権以外の人は、役割「スタッフ（ページごと）」＝ ymkrf_staff_editor（ログイン・メディア・プロフィールだけ）にして、
   ✓したページの権限を、ログインのたびに足します（user_has_cap）。✓を変えれば、すぐ効きます。
   ------------------------------------------------------------ */
function ymkrf_staff_perm_list() {
	return array(
		'all'    => 'すべて',   /* 2026/10/29 ユーザー「すべてはすべて　のみにして」 */
		'works'  => '施工事例',
		'column' => 'コラム',
		'store'  => '担当店舗のページ',
	);
}

/** そのスタッフの権限（配列） */
function ymkrf_staff_perms( $staff_id ) {
	$v = get_post_meta( $staff_id, '_ymkrf_staff_perms', true );
	if ( is_array( $v ) ) {
		$v = array_values( array_intersect( array_keys( ymkrf_staff_perm_list() ), $v ) );
		return in_array( 'all', $v, true ) ? array( 'all' ) : $v;
	}
	return array();   /* まだえらんでいない人は「なし」（2026/10/29 ユーザー「権限、自動は不要よ」） */
}

/** 前の決め方（自動）。下の1回だけの引きつぎにだけ使います */
function ymkrf_staff_perms_legacy( $staff_id ) {
	$lv = (string) get_post_meta( $staff_id, '_ymkrf_staff_level', true );
	if ( $lv === 'all' ) return array( 'all' );
	if ( $lv === 'works_column' ) return array( 'works', 'column' );
	if ( $lv === 'works' ) return array( 'works' );
	return ymkrf_staff_in_honbu( $staff_id ) ? array( 'all' ) : array( 'works' );
}

/* 1回だけ：「自動」をやめる前に、ログインがある人はいまの権限を✓の形で残します。
   ログインがない人は「なし」から（パスワードを決めるときに✓をえらぶ） */
add_action( 'init', function () {
	if ( get_option( 'ymkrf_staff_perms_fix_v1' ) === '1' ) return;
	if ( ! post_type_exists( 'ymkrf_staff' ) ) return;
	foreach ( get_posts( array( 'post_type' => 'ymkrf_staff', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ) ) as $sid ) {
		if ( is_array( get_post_meta( $sid, '_ymkrf_staff_perms', true ) ) ) continue;
		update_post_meta( $sid, '_ymkrf_staff_perms', ymkrf_staff_user( $sid ) ? ymkrf_staff_perms_legacy( $sid ) : array() );
	}
	update_option( 'ymkrf_staff_perms_fix_v1', '1', false );
}, 32 );

/** 権限の名前（表示用） */
function ymkrf_staff_perms_label( $perms ) {
	$l = ymkrf_staff_perm_list();
	$o = array();
	foreach ( (array) $perms as $p ) if ( isset( $l[ $p ] ) ) $o[] = $l[ $p ];
	return $o ? implode( '・', $o ) : 'なし';
}

/** 担当店舗（店舗ページがあるお店の英字） */
function ymkrf_staff_store_slugs( $staff_id ) {
	$mine = function_exists( 'ymkrf_staff_get_shops' ) ? (array) ymkrf_staff_get_shops( $staff_id ) : array();
	return array_values( array_diff( $mine, array( 'honbu', 'kouji' ) ) );
}

/* 役割「スタッフ（ページごと）」を作ります */
add_action( 'init', function () {
	if ( get_option( 'ymkrf_staff_editor_role_ver' ) === '1' ) return;
	remove_role( 'ymkrf_staff_editor' );
	add_role( 'ymkrf_staff_editor', 'スタッフ（ページごと）', array( 'read' => true, 'upload_files' => true ) );
	update_option( 'ymkrf_staff_editor_role_ver', '1', false );
}, 31 );

/** ページごとの人か（全権の人・もとからの管理者はのぞく）。その人のスタッフ記事IDを返します（ちがえば 0） */
function ymkrf_staff_editor_of( $u = null ) {
	$u = $u ? ( is_object( $u ) ? $u : get_userdata( (int) $u ) ) : wp_get_current_user();
	if ( ! $u || empty( $u->ID ) ) return 0;
	$roles = (array) $u->roles;
	if ( in_array( 'administrator', $roles, true ) ) return 0;
	if ( ! array_intersect( $roles, array( 'ymkrf_staff_editor', 'ymkrf_works_staff', 'ymkrf_works_column' ) ) ) return 0;
	$sid = (int) get_user_meta( $u->ID, 'ymkrf_staff_post', true );
	return ( $sid && get_post_type( $sid ) === 'ymkrf_staff' ) ? $sid : 0;
}

/** その人の権限（ページごとの人だけ。ちがえば空） */
function ymkrf_user_perms( $u = null ) {
	$sid = ymkrf_staff_editor_of( $u );
	return $sid ? array_diff( ymkrf_staff_perms( $sid ), array( 'all' ) ) : array();
}

/* ✓したページの権限を足します */
add_filter( 'user_has_cap', function ( $all, $caps, $args, $user ) {
	static $busy = false;
	if ( $busy || ! $user || empty( $user->ID ) ) return $all;
	$busy = true;
	$perms = ymkrf_user_perms( $user );
	$busy = false;
	if ( ! $perms ) return $all;
	$add = array();
	if ( in_array( 'works', $perms, true ) ) {
		$add = array_merge( $add, array( 'edit_ymkrf_works', 'edit_others_ymkrf_works', 'edit_published_ymkrf_works',
			'publish_ymkrf_works', 'read_private_ymkrf_works', 'delete_ymkrf_works', 'delete_published_ymkrf_works', 'create_ymkrf_works' ) );
	}
	if ( in_array( 'column', $perms, true ) ) {
		$add = array_merge( $add, array( 'edit_posts', 'edit_others_posts', 'edit_published_posts', 'publish_posts',
			'delete_posts', 'delete_published_posts', 'read_private_posts' ) );
	}
	if ( in_array( 'store', $perms, true ) ) {
		$add = array_merge( $add, array( 'edit_pages', 'edit_others_pages', 'edit_published_pages', 'publish_pages', 'read_private_pages' ) );
	}
	foreach ( $add as $c ) $all[ $c ] = true;
	return $all;
}, 20, 4 );

/* ✓していない種類の記事は、さわれないようにします
   （コラム・店舗ページは WordPress のふつうの権限で動くので、ほかの記事に広がらないように止めます） */
add_filter( 'map_meta_cap', function ( $caps, $cap, $uid, $args ) {
	if ( ! in_array( $cap, array( 'edit_post', 'delete_post', 'publish_post' ), true ) || empty( $args[0] ) ) return $caps;
	$p = get_post( (int) $args[0] );
	if ( ! $p || $p->post_type === 'attachment' ) return $caps;
	static $busy = false;
	if ( $busy ) return $caps;
	$busy = true;
	$sid   = ymkrf_staff_editor_of( get_userdata( $uid ) );
	$perms = $sid ? ymkrf_staff_perms( $sid ) : array();
	$busy  = false;
	if ( ! $sid ) return $caps;
	if ( $p->post_type === 'ymkrf_works' && in_array( 'works', $perms, true ) ) return $caps;   /* お店ごとの制限は、下の施工事例のところ */
	if ( $p->post_type === 'ymkrf_column' && in_array( 'column', $perms, true ) ) {
		/* コラムは、自分のお店の人が書いたものだけ（2026/10/29 ユーザー「コラムは基本、自分の店舗間の人が書いたもののみを
		   編集できる権限にして。他店の人が書いたものは編集不可。すべての編集権限の人は全ての編集権限あり」） */
		if ( ymkrf_column_same_shop( $p, $uid, $sid ) ) return $caps;
		return array( 'do_not_allow' );
	}
	if ( $p->post_type === 'ymkrf_store' && in_array( 'store', $perms, true ) && $cap !== 'delete_post' ) {
		$slug = (string) get_post_meta( $p->ID, '_ymkrf_store_slug', true );
		if ( $slug === '' ) $slug = $p->post_name;
		if ( in_array( $slug, ymkrf_staff_store_slugs( $sid ), true ) ) return $caps;
	}
	return array( 'do_not_allow' );
}, 30, 4 );

/** コラムを、この人（スタッフ $sid）が直してよいか
    ・自分が作った記事（新しく書いている途中もふくむ）
    ・執筆者が、自分と同じお店（兼任ふくむ）のスタッフ
    ・執筆者が「お店から」で、そのお店が自分のお店
    「広報」「出さない」の記事・ほかのお店の人の記事は直せません */
function ymkrf_column_same_shop( $p, $uid, $sid ) {
	if ( (int) $p->post_author === (int) $uid ) return true;
	$mine = function_exists( 'ymkrf_staff_get_shops' ) ? (array) ymkrf_staff_get_shops( $sid ) : array();
	if ( ! $mine ) return false;
	$w = trim( (string) get_post_meta( $p->ID, '_ymkrf_staff', true ) );
	if ( $w === '' ) return false;
	if ( strpos( $w, 'shop:' ) === 0 ) return in_array( substr( $w, 5 ), $mine, true );
	$wid = (int) $w;
	if ( $wid <= 0 || get_post_type( $wid ) !== 'ymkrf_staff' ) return false;
	if ( $wid === (int) $sid ) return true;
	return (bool) array_intersect( $mine, (array) ymkrf_staff_get_shops( $wid ) );
}

/** その人が入れる画面（左メニューの道） */
function ymkrf_user_menu_keep( $u = null ) {
	$perms = ymkrf_user_perms( $u );
	$keep  = array( 'upload.php', 'profile.php' );
	if ( in_array( 'works', $perms, true ) )  $keep[] = 'edit.php?post_type=ymkrf_works';
	if ( in_array( 'column', $perms, true ) ) $keep[] = 'edit.php?post_type=ymkrf_column';
	if ( in_array( 'store', $perms, true ) )  $keep[] = 'edit.php?post_type=ymkrf_store';
	return $keep;
}

/* 左のメニューは、✓したページ・メディア・プロフィールだけ */
add_action( 'admin_menu', function () {
	if ( ! ymkrf_staff_editor_of() ) return;
	global $menu;
	$keep = ymkrf_user_menu_keep();
	foreach ( (array) $menu as $m ) {
		if ( empty( $m[2] ) ) continue;
		if ( ! in_array( $m[2], $keep, true ) ) remove_menu_page( $m[2] );
	}
}, 9999 );

/* ほかの画面は、ひらいても入れる画面へもどします */
add_action( 'current_screen', function ( $sc ) {
	if ( ! $sc || wp_doing_ajax() || ! ymkrf_staff_editor_of() ) return;
	if ( in_array( $sc->base, array( 'upload', 'media', 'async-upload', 'profile', 'user-edit' ), true ) ) return;
	$perms = ymkrf_user_perms();
	$types = array();
	if ( in_array( 'works', $perms, true ) )  $types[] = 'ymkrf_works';
	if ( in_array( 'column', $perms, true ) ) $types[] = 'ymkrf_column';
	if ( in_array( 'store', $perms, true ) )  $types[] = 'ymkrf_store';
	if ( in_array( $sc->base, array( 'edit', 'post' ), true ) && in_array( $sc->post_type, $types, true ) ) return;
	$keep = ymkrf_user_menu_keep();
	wp_safe_redirect( admin_url( count( $keep ) > 2 ? $keep[2] : 'profile.php' ) );
	exit;
} );

/* 店舗ページの一覧は、担当店舗だけ出します */
add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() || ! $q->is_main_query() || $q->get( 'post_type' ) !== 'ymkrf_store' ) return;
	$sid = ymkrf_staff_editor_of();
	if ( ! $sid ) return;
	$slugs = ymkrf_staff_store_slugs( $sid );
	$q->set( 'post_name__in', $slugs ? $slugs : array( '-none-' ) );
} );

/* ログインしたら、入れる画面のいちばん目を出します */
add_filter( 'login_redirect', function ( $to, $req, $user ) {
	if ( is_wp_error( $user ) || ! ymkrf_staff_editor_of( $user ) ) return $to;
	$keep = ymkrf_user_menu_keep( $user );
	return admin_url( count( $keep ) > 2 ? $keep[2] : 'profile.php' );
}, 20, 3 );

/** 権限に合わせて、役割をそろえます。変えたら説明の文を返します */
function ymkrf_staff_sync_role( $u, $staff_id ) {
	if ( ! $u || ! ymkrf_staff_managed( $u, $staff_id ) || ! current_user_can( 'promote_users' ) ) return '';
	$want = in_array( 'all', ymkrf_staff_perms( $staff_id ), true ) ? 'administrator' : 'ymkrf_staff_editor';
	if ( in_array( $want, (array) $u->roles, true ) && count( (array) $u->roles ) === 1 ) return '';
	if ( (int) $u->ID === get_current_user_id() && user_can( $u, 'manage_options' ) && $want !== 'administrator' ) return '';   /* 自分の権限は下げません */
	$u->set_role( $want );
	return '権限を「' . ymkrf_staff_perms_label( ymkrf_staff_perms( $staff_id ) ) . '」にしました。';
}

/* ------------------------------------------------------------
   パスワードを忘れた人に、いまのパスワードをメールで送る
   （2026/10/29 ユーザー「パスワード忘れたって言ってくる人には、設定されているパスワードをメールで送れるようにしてほしい」
     →「現在のパスワードを送る。設定は基本ダッシュボード全編集権限者のみ」）
   - WordPress のパスワードは元にもどせない形で入るので、それとは別に、
     サイトの秘密の鍵（wp-config.php の鍵）で鍵をかけた形でユーザーメタ ymkrf_pw_enc に残します。
     データベースを見ても、そのままでは読めません。
   - 送れるのは「すべて」の権限の人だけ。あて先は本人のID（メールアドレス）だけ。画面には出しません。
   - この仕組みを入れる前（10/29 より前）に決めたパスワードは残っていないので、送れません（決めなおすと送れます）。
   - ★テスト用のパソコン（XAMPP）からはメールが出ません。本番のサーバーで使えます。
   ------------------------------------------------------------ */
function ymkrf_pw_key() {
	return hash( 'sha256', wp_salt( 'auth' ) . '|ymkrf-staff-pw', true );
}
/** 鍵をかけて残します */
function ymkrf_pw_keep( $uid, $pw ) {
	if ( ! function_exists( 'openssl_encrypt' ) ) return;
	$iv  = random_bytes( 16 );
	$enc = openssl_encrypt( (string) $pw, 'aes-256-cbc', ymkrf_pw_key(), OPENSSL_RAW_DATA, $iv );
	if ( $enc === false ) return;
	$mac = hash_hmac( 'sha256', $iv . $enc, ymkrf_pw_key(), true );
	update_user_meta( $uid, 'ymkrf_pw_enc', base64_encode( $iv . $mac . $enc ) );
}
/** 鍵をあけます（だめなら空） */
function ymkrf_pw_open( $uid ) {
	$raw = base64_decode( (string) get_user_meta( $uid, 'ymkrf_pw_enc', true ), true );
	if ( ! $raw || strlen( $raw ) < 49 || ! function_exists( 'openssl_decrypt' ) ) return '';
	$iv = substr( $raw, 0, 16 ); $mac = substr( $raw, 16, 32 ); $enc = substr( $raw, 48 );
	if ( ! hash_equals( $mac, hash_hmac( 'sha256', $iv . $enc, ymkrf_pw_key(), true ) ) ) return '';
	$pw = openssl_decrypt( $enc, 'aes-256-cbc', ymkrf_pw_key(), OPENSSL_RAW_DATA, $iv );
	return $pw === false ? '' : $pw;
}
/** 「メールで送る」ボタン（押すと「本当に送る」に変わる2段階。確認の窓は出しません） */
function ymkrf_pw_send_button( $u, $small = false ) {
	if ( ! $u ) return '';
	if ( (string) get_user_meta( $u->ID, 'ymkrf_pw_enc', true ) === '' ) {
		return '<small class="ymkrf-slg__none">' . ( $small ? '（決めなおすとメールで送れます）' : '10/29 より前に決めたパスワードは残っていないので、メールで送れません。決めなおすと送れるようになります。' ) . '</small>';
	}
	return '<button type="button" class="button' . ( $small ? ' button-small' : '' ) . ' ymkrf-pwsend" data-uid="' . (int) $u->ID . '"'
		. ' data-nonce="' . esc_attr( wp_create_nonce( 'ymkrf_pwsend_' . $u->ID ) ) . '" title="いまのパスワードを、本人のID（メール）に送ります">パスワードをメールで送る</button>'
		. '<span class="ymkrf-pwsend__msg"></span>';
}
add_action( 'wp_ajax_ymkrf_pwsend', function () {
	$uid = isset( $_POST['uid'] ) ? (int) $_POST['uid'] : 0;
	check_ajax_referer( 'ymkrf_pwsend_' . $uid );
	if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( '送れるのは「すべて」の権限の人だけです' );
	$u = get_userdata( $uid );
	if ( ! $u ) wp_send_json_error( 'ユーザーが見つかりません' );
	$pw = ymkrf_pw_open( $uid );
	if ( $pw === '' ) wp_send_json_error( 'パスワードが残っていません（決めなおしてください）' );
	$name = trim( (string) $u->display_name );
	$body = ( $name !== '' ? $name . ' さん' : '' ) . "

"
		. "リフォームヤマキシ ホームページの管理画面のログイン情報です。

"
		. "ログインするページ： " . wp_login_url() . "
"
		. "ID： " . $u->user_login . "
"
		. "パスワード： " . $pw . "

"
		. "このメールは、ほかの人に見せないでください。
"
		. "心当たりがないときは、本部へご連絡ください。
";
	$fail = false;
	$hook = function () use ( &$fail ) { $fail = true; };
	add_action( 'wp_mail_failed', $hook );
	$ok = wp_mail( $u->user_email, '【リフォームヤマキシ】ホームページ管理画面のパスワード', $body );
	remove_action( 'wp_mail_failed', $hook );
	if ( ! $ok || $fail ) wp_send_json_error( 'メールが送れませんでした（テスト用のパソコンからは送れません。本番のサーバーで使えます）' );
	wp_send_json_success( $u->user_email . ' に送りました' );
} );
/* ボタンの動き（スタッフの一覧と編集画面） */
add_action( 'admin_footer', function () {
	$s = get_current_screen();
	if ( ! $s || $s->post_type !== 'ymkrf_staff' || ! current_user_can( 'manage_options' ) ) return;
	?>
	<style>.ymkrf-pwsend{margin-top:4px!important}.ymkrf-pwsend.is-armed{border-color:#c92800;color:#c92800}
	.ymkrf-pwsend__msg{display:block;margin-top:3px;font-size:12px}.ymkrf-pwsend__msg.is-ok{color:#00701a}.ymkrf-pwsend__msg.is-ng{color:#b32d00}</style>
	<script>
	jQuery(function ($) {
	  $(document).on('click', '.ymkrf-pwsend', function () {
	    var $b = $(this), $m = $b.next('.ymkrf-pwsend__msg');
	    if (!$b.hasClass('is-armed')) { $b.addClass('is-armed').data('t', $b.text()).text('本当に送る'); return; }
	    $b.prop('disabled', true).text('送っています…');
	    $.post(ajaxurl, { action: 'ymkrf_pwsend', uid: $b.data('uid'), _wpnonce: $b.data('nonce') }).always(function (r) {
	      var ok = r && r.success;
	      $m.removeClass('is-ok is-ng').addClass(ok ? 'is-ok' : 'is-ng').text(r && r.data ? r.data : 'うまくいきませんでした');
	      $b.prop('disabled', false).removeClass('is-armed').text($b.data('t'));
	    });
	  });
	});
	</script>
	<?php
} );

/* 一覧の列：ID ／ パスワード ／ 編集できるページ
   （2026/10/29 ユーザー「メール（ログインID）を、IDに変更、その横にパスワードの欄、
     施工事例のログインに編集できるぺージを表示しようと思います」） */

/** ユーザーとスタッフをつなぎなおします（一覧を出すたびに、ずれていたら直す） */
function ymkrf_staff_user_sync( $staff_id ) {
	$u = ymkrf_staff_user( $staff_id );
	if ( $u ) {
		if ( (int) get_post_meta( $staff_id, '_ymkrf_staff_user', true ) !== (int) $u->ID ) update_post_meta( $staff_id, '_ymkrf_staff_user', (int) $u->ID );
		if ( ! get_user_meta( $u->ID, 'ymkrf_staff_post', true ) ) update_user_meta( $u->ID, 'ymkrf_staff_post', (int) $staff_id );
	}
	return $u;
}

/** パスワードの欄：文字の数だけ＊（中身はどこにも残していないので出せません）。
    2026/10/29 ユーザー「設定済の人は、パスワードの文字数だけ＊で一覧に表示して」 */
function ymkrf_staff_pw_cell( $staff_id ) {
	$u    = ymkrf_staff_user_sync( $staff_id );
	$mail = trim( (string) get_post_meta( $staff_id, '_ymkrf_staff_mail', true ) );
	$edit = get_edit_post_link( $staff_id );
	if ( $u && user_can( $u, 'manage_options' ) && ! ymkrf_staff_managed( $u, $staff_id ) ) {
		return '<span class="ymkrf-slg__none">管理者（ユーザー画面で）</span>';
	}
	if ( $u ) {
		$len = (int) get_user_meta( $u->ID, 'ymkrf_pw_len', true );
		return '<span class="ymkrf-slg__pw">' . ( $len ? str_repeat( '•', min( $len, 40 ) ) : '設定済み' ) . '</span>'
			. ( current_user_can( 'manage_options' ) ? '<br>' . ymkrf_pw_send_button( $u, true ) : '' );
	}
	if ( $mail === '' || ! is_email( $mail ) ) return '<span class="ymkrf-slg__none">—</span>';
	return ( $edit && current_user_can( 'create_users' ) )
		? '<a href="' . esc_url( $edit . '#ymkrf_staff_login' ) . '">パスワードを決める</a>'
		: '<span class="ymkrf-slg__none">まだ</span>';
}

/** 編集できるページの欄 */
function ymkrf_staff_pages_cell( $staff_id ) {
	$u    = ymkrf_staff_user_sync( $staff_id );
	$mail = trim( (string) get_post_meta( $staff_id, '_ymkrf_staff_mail', true ) );
	ob_start();
	echo '<div class="ymkrf-slg">';
	if ( ! $u ) {
		echo '<span class="ymkrf-slg__none">'
			. ( ( $mail === '' || ! is_email( $mail ) ) ? 'メールを入れると、ログインを作れます' : 'まだログインできません（パスワードを決めるとできます）' )
			. '</span>';
	} elseif ( user_can( $u, 'manage_options' ) ) {
		echo '<span class="ymkrf-slg__pg is-all">すべて</span>' . ( ymkrf_staff_managed( $u, $staff_id ) ? '' : ' <small>（管理者）</small>' );
	} else {
		$perms = ymkrf_staff_perms( $staff_id );
		if ( ! $perms ) {
			echo '<span class="ymkrf-slg__ng">なし</span> <small>（ログインはできますが、編集できるページがありません）</small>';
		}
		if ( in_array( 'works', $perms, true ) ) {
			$names = function_exists( 'ymkrf_staff_shop_names' ) ? (string) ymkrf_staff_shop_names( $staff_id ) : '';
			echo '<span class="ymkrf-slg__pg" title="' . esc_attr( ymkrf_is_honbu( $u ) ? 'すべての事例・編集の記録' : $names . 'の事例、自分が担当・登録した事例' ) . '">施工事例</span> ';
		}
		if ( in_array( 'column', $perms, true ) ) echo '<span class="ymkrf-slg__pg">コラム</span> ';
		if ( in_array( 'store', $perms, true ) ) {
			$sn = array();
			$all_sh = function_exists( 'ymkrf_staff_shops' ) ? ymkrf_staff_shops() : array();
			foreach ( ymkrf_staff_store_slugs( $staff_id ) as $sl ) $sn[] = isset( $all_sh[ $sl ] ) ? $all_sh[ $sl ] : $sl;
			echo '<span class="ymkrf-slg__pg">店舗ページ</span> <small>' . esc_html( $sn ? implode( '・', $sn ) : '担当店舗なし' ) . '</small>';
		}
	}
	if ( $u ) {
		$last = (int) get_user_meta( $u->ID, 'ymkrf_last_login', true );
		echo '<br><small class="ymkrf-slg__last">最後のログイン：' . ( $last ? esc_html( ymkrf_when( $last ) ) : 'まだ' ) . '</small>';
	}
	echo '</div>';
	return ob_get_clean();
}

add_filter( 'manage_ymkrf_staff_posts_columns', function ( $cols ) {
	$cols['ymkrf_smail']  = 'ID';
	$cols['ymkrf_spw']    = 'パスワード';
	$cols['ymkrf_slogin'] = '編集できるページ';
	return $cols;
}, 30 );

add_action( 'manage_ymkrf_staff_posts_custom_column', function ( $col, $post_id ) {
	if ( $col === 'ymkrf_smail' ) {
		$m = trim( (string) get_post_meta( $post_id, '_ymkrf_staff_mail', true ) );
		echo $m !== ''
			? '<a href="mailto:' . esc_attr( $m ) . '">' . esc_html( $m ) . '</a>'
			: '<span style="color:#a7aaad">—</span>';
	} elseif ( $col === 'ymkrf_spw' ) {
		echo ymkrf_staff_pw_cell( $post_id );      // 中で esc 済み
	} elseif ( $col === 'ymkrf_slogin' ) {
		echo ymkrf_staff_pages_cell( $post_id );   // 中で esc 済み
	}
}, 10, 2 );

add_action( 'admin_head', function () {
	$s = get_current_screen();
	if ( ! $s || $s->post_type !== 'ymkrf_staff' ) return;
	echo '<style>
	  .column-ymkrf_smail{width:230px;word-break:break-all}
	  .column-ymkrf_spw{width:170px}
	  .column-ymkrf_slogin{width:auto}   /* あまった幅はここ（左寄せなので間があかない） */
	  .ymkrf-slg{font-size:12.5px;line-height:1.6}
	  .ymkrf-slg__ok{color:#00a32a;font-weight:700;font-size:16px}
	  .ymkrf-slg__ng{color:#b32d00;font-weight:700}
	  .ymkrf-slg__none,.ymkrf-slg__last{color:#8c8f94}
	  .ymkrf-slg__pw{color:#50575e;letter-spacing:0;font-size:11px;line-height:1}
	  .ymkrf-slg__pg{display:inline-block;padding:1px 9px;border-radius:999px;background:#edfaef;color:#00701a;font-weight:700;font-size:12px}
	  .ymkrf-slg__pg.is-all{background:#fff1ec;color:#c92800}
	  #ymkrf_staff_login .ymkrf-pw{display:flex;gap:6px;align-items:center}
	  #ymkrf_staff_login .ymkrf-pw input{flex:1;min-width:0}
	  #ymkrf_staff_login .ymkrf-id{word-break:break-all;font-weight:700}
	</style>';
} );

/* 編集画面の右：ID とパスワード */
add_action( 'add_meta_boxes_ymkrf_staff', function ( $post ) {
	add_meta_box( 'ymkrf_staff_login', 'ログイン・編集できるページ', function ( $post ) {
		$mail = trim( (string) get_post_meta( $post->ID, '_ymkrf_staff_mail', true ) );
		$u    = $post->post_status === 'auto-draft' ? null : ymkrf_staff_user( $post->ID );

		echo '<p style="margin-top:0">ID（社員のメールアドレス）<br><span class="ymkrf-id">'
			. ( $mail !== '' ? esc_html( $mail ) : '<span style="color:#b32d00">まだ入っていません（左の「メールアドレス（社内）」に入れてください）</span>' )
			. '</span></p>';

		$pm_now = ymkrf_staff_perms( $post->ID );
		echo '<p style="margin:0 0 4px"><b>編集できるページ</b></p>';
		if ( current_user_can( 'promote_users' ) ) {
			echo '<input type="hidden" name="ymkrf_staff_perms_sent" value="1"><div class="ymkrf-perms">';
			foreach ( ymkrf_staff_perm_list() as $k => $lab ) {
				$extra = '';
				if ( $k === 'store' ) {
					$sn = array();
					$all_sh = function_exists( 'ymkrf_staff_shops' ) ? ymkrf_staff_shops() : array();
					foreach ( ymkrf_staff_store_slugs( $post->ID ) as $sl ) $sn[] = isset( $all_sh[ $sl ] ) ? $all_sh[ $sl ] : $sl;
					$extra = '<small>（' . esc_html( $sn ? implode( '・', $sn ) : '所属のお店がありません' ) . '）</small>';
				}
				echo '<label class="ymkrf-perms__item' . ( $k === 'all' ? ' is-all' : '' ) . '"><input type="checkbox" name="ymkrf_staff_perms[]" value="' . esc_attr( $k ) . '"'
					. checked( in_array( $k, $pm_now, true ), true, false ) . '> ' . esc_html( $lab ) . $extra . '</label>';
			}
			echo '</div>';
			echo '<script>jQuery(function($){var $b=$(".ymkrf-perms");function f(){var a=$b.find("input[value=all]").prop("checked");$b.find("input").not("[value=all]").prop("disabled",a).closest("label").toggleClass("is-off",a);}$b.on("change","input",f);f();});</script>';
			echo '<style>.ymkrf-perms{display:grid;gap:4px;margin:0 0 10px}.ymkrf-perms__item{display:block;padding:4px 8px;border:1px solid #dcdcde;border-radius:6px;background:#fff;cursor:pointer}'
				. '.ymkrf-perms__item:has(input:checked){border-color:#fe3301;background:#fff1ec;font-weight:700}.ymkrf-perms__item small{display:block;margin-left:22px;font-weight:400;color:#8c8f94}'
				. '.ymkrf-perms__item.is-all{margin-bottom:4px}.ymkrf-perms__item.is-off{opacity:.45}</style>';
		} else {
			echo '<p class="description" style="margin:0 0 10px">' . esc_html( ymkrf_staff_perms_label( $pm_now ) ) . '</p>';
		}

		if ( $u && user_can( $u, 'manage_options' ) && ! ymkrf_staff_managed( $u, $post->ID ) ) {
			echo '<p><b style="color:#00a32a">✓ 管理者</b>（施工事例もふくめ、すべてできます）</p>';
			echo '<p class="description">管理者のパスワードは、ここでは変えません。<a href="' . esc_url( get_edit_user_link( $u->ID ) ) . '">ユーザー画面</a>で変えてください。</p>';
		} elseif ( current_user_can( 'create_users' ) && current_user_can( 'edit_users' ) ) {
			if ( $u ) {
				$last = (int) get_user_meta( $u->ID, 'ymkrf_last_login', true );
				echo '<p>' . ( ymkrf_user_can_works( $u ) ? '<b style="color:#00a32a">〇 ログインできます</b>' : '<b style="color:#b32d00">施工事例はまだできません</b>（保存するとできるようになります）' )
					. '<br>パスワード：' . ( (int) get_user_meta( $u->ID, 'ymkrf_pw_len', true ) ? str_repeat( '•', min( 40, (int) get_user_meta( $u->ID, 'ymkrf_pw_len', true ) ) ) : '設定済み' )
					. '<br>最後のログイン：' . ( $last ? esc_html( ymkrf_when( $last ) ) : 'まだ' ) . '</p>';
				$lbl = '新しいパスワード（変えるときだけ）';
			} else {
				echo '<p><b style="color:#b32d00">まだログインできません。</b><br>パスワードを入れて保存すると、ログインできるようになります。</p>';
				$lbl = 'パスワード（8文字以上）';
			}
			/* autocomplete="new-password"：ブラウザが管理者のパスワードを勝手に入れないように */
			echo '<p style="margin-bottom:4px"><label for="ymkrf-staff-pw">' . esc_html( $lbl ) . '</label></p>';
			echo '<div class="ymkrf-pw"><input type="password" id="ymkrf-staff-pw" name="ymkrf_staff_newpw" value="" autocomplete="new-password" spellcheck="false">'
				. '<button type="button" class="button" id="ymkrf-staff-pwshow">表示</button></div>';
			echo '<script>document.getElementById("ymkrf-staff-pwshow").addEventListener("click",function(){var i=document.getElementById("ymkrf-staff-pw");var s=i.type==="password";i.type=s?"text":"password";this.textContent=s?"かくす":"表示";});</script>';
			if ( $u ) {
				if ( current_user_can( 'manage_options' ) ) {
					echo ymkrf_pw_send_button( $u );   // 中で esc 済み
				}
			}
		} else {
			echo '<p class="description">ログインの設定は本部の人だけができます。</p>';
		}
	}, 'ymkrf_staff', 'side', 'high' );
} );

/* 保存：パスワードが入っていたら、ユーザーを作る／パスワードを変える */
add_action( 'save_post_ymkrf_staff', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;
	if ( ! isset( $_POST['ymkrf_staff_nonce'] ) || ! wp_verify_nonce( $_POST['ymkrf_staff_nonce'], 'ymkrf_staff_save' ) ) return;
	if ( ! current_user_can( 'create_users' ) || ! current_user_can( 'edit_users' ) ) return;

	static $busy = array();
	if ( isset( $busy[ $post_id ] ) ) return;
	$busy[ $post_id ] = true;

	/* 権限の段階 */
	if ( isset( $_POST['ymkrf_staff_perms_sent'] ) && current_user_can( 'promote_users' ) ) {
		$in = isset( $_POST['ymkrf_staff_perms'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['ymkrf_staff_perms'] ) ) : array();
		$in = array_values( array_intersect( array_keys( ymkrf_staff_perm_list() ), $in ) );
		if ( in_array( 'all', $in, true ) ) $in = array( 'all' );
		update_post_meta( $post_id, '_ymkrf_staff_perms', $in );
	}

	$pw   = isset( $_POST['ymkrf_staff_newpw'] ) ? (string) wp_unslash( $_POST['ymkrf_staff_newpw'] ) : '';
	$mail = trim( (string) get_post_meta( $post_id, '_ymkrf_staff_mail', true ) );   /* 上（優先度10）で保存ずみ */
	$name = trim( (string) get_the_title( $post_id ) );
	$u    = ymkrf_staff_user( $post_id );
	$note = '';

	/* メールが変わったら、ログインID（＝メール）もそろえます（管理者はさわりません） */
	if ( $u && ( ! user_can( $u, 'manage_options' ) || ymkrf_staff_managed( $u, $post_id ) ) && $mail !== '' && is_email( $mail )
	     && strtolower( $u->user_email ) !== strtolower( $mail ) ) {
		$other = get_user_by( 'email', $mail );
		if ( $other && (int) $other->ID !== (int) $u->ID ) {
			$note = 'このメールアドレスは、ほかのユーザーが使っています。ログインIDは変えていません。';
		} else {
			global $wpdb;
			wp_update_user( array( 'ID' => $u->ID, 'user_email' => $mail ) );
			if ( ! username_exists( $mail ) ) {
				$wpdb->update( $wpdb->users, array( 'user_login' => $mail ), array( 'ID' => $u->ID ) );
			}
			clean_user_cache( $u->ID );
			$u = get_userdata( $u->ID );
			$note = 'ログインIDを新しいメールアドレスにしました。';
		}
	}

	if ( $pw === '' ) {
		/* パスワード無し：所属に合わせて権限だけそろえます */
		if ( $u ) $note .= ymkrf_staff_sync_role( $u, $post_id );
		if ( $note !== '' ) set_transient( 'ymkrf_staff_login_note_' . get_current_user_id(), array( 'ok', $note ), 60 );
		return;
	}

	if ( strlen( $pw ) < 8 ) {
		set_transient( 'ymkrf_staff_login_note_' . get_current_user_id(), array( 'ng', 'パスワードが短すぎます（8文字以上）。ログインは作っていません／変えていません。' ), 60 );
		return;
	}
	if ( $u && user_can( $u, 'manage_options' ) && ! ymkrf_staff_managed( $u, $post_id ) ) {
		set_transient( 'ymkrf_staff_login_note_' . get_current_user_id(), array( 'ng', '管理者のパスワードは、ここでは変えません。ユーザー画面で変えてください。' ), 60 );
		return;
	}
	if ( $u && (int) $u->ID === get_current_user_id() ) {
		set_transient( 'ymkrf_staff_login_note_' . get_current_user_id(), array( 'ng', '自分のパスワードは、ここでは変えません（変えるとその場でログアウトされるため）。右上の名前 ＞ プロフィール で変えてください。' ), 60 );
		return;
	}

	if ( $u ) {
		wp_set_password( $pw, $u->ID );   /* その人がログイン中なら、ログアウトされます */
		update_user_meta( $u->ID, 'ymkrf_pw_len', mb_strlen( $pw, 'UTF-8' ) );   /* 文字の数だけ（一覧の＊用） */
		ymkrf_pw_keep( $u->ID, $pw );   /* 忘れたときにメールで送れるよう、鍵をかけて残す */
		$note .= 'パスワードを変えました。' . ymkrf_staff_sync_role( $u, $post_id );
	} else {
		if ( $mail === '' || ! is_email( $mail ) ) {
			set_transient( 'ymkrf_staff_login_note_' . get_current_user_id(), array( 'ng', 'メールアドレス（ID）が入っていないので、ログインを作れませんでした。' ), 60 );
			return;
		}
		if ( username_exists( $mail ) ) {
			set_transient( 'ymkrf_staff_login_note_' . get_current_user_id(), array( 'ng', 'このメールアドレスのIDは、もう使われています。' ), 60 );
			return;
		}
		$uid = wp_insert_user( array(
			'user_login'   => $mail,   /* ID＝社員のメールアドレス */
			'user_email'   => $mail,
			'user_pass'    => $pw,
			'display_name' => $name !== '' ? $name : $mail,
			'nickname'     => $name !== '' ? $name : $mail,
			'role'         => in_array( 'all', ymkrf_staff_perms( $post_id ), true ) ? 'administrator' : 'ymkrf_staff_editor',   /* 権限に合わせて */
		) );
		if ( is_wp_error( $uid ) ) {
			set_transient( 'ymkrf_staff_login_note_' . get_current_user_id(), array( 'ng', 'ログインを作れませんでした：' . $uid->get_error_message() ), 60 );
			return;
		}
		update_post_meta( $post_id, '_ymkrf_staff_user', (int) $uid );
		update_user_meta( $uid, 'ymkrf_staff_post', $post_id );
		update_user_meta( $uid, 'ymkrf_pw_len', mb_strlen( $pw, 'UTF-8' ) );   /* 文字の数だけ（一覧の＊用） */
		ymkrf_pw_keep( $uid, $pw );   /* 忘れたときにメールで送れるよう、鍵をかけて残す */
		$note .= 'ログインを作りました（ID：' . $mail . '／権限：' . ymkrf_staff_perms_label( ymkrf_staff_perms( $post_id ) ) . '）。';
	}
	set_transient( 'ymkrf_staff_login_note_' . get_current_user_id(), array( 'ok', $note ), 60 );
}, 40 );

/* 保存したあとのお知らせ */
add_action( 'admin_notices', function () {
	$s = get_current_screen();
	if ( ! $s || $s->post_type !== 'ymkrf_staff' ) return;
	$k = 'ymkrf_staff_login_note_' . get_current_user_id();
	$n = get_transient( $k );
	if ( ! $n ) return;
	delete_transient( $k );
	printf( '<div class="notice notice-%s is-dismissible"><p><b>ログイン：</b>%s</p></div>',
		$n[0] === 'ok' ? 'success' : 'error', esc_html( $n[1] ) );
} );


/* ============================================================
   2. 施工事例：だれが登録・編集したか
   ============================================================ */

/** 記録を1つ足します */
function ymkrf_edit_log_add( $post_id, $what ) {
	$uid = get_current_user_id();
	if ( ! $uid ) return;
	$log = get_post_meta( $post_id, '_ymkrf_edit_log', true );
	if ( ! is_array( $log ) ) $log = array();
	$now  = time();
	$last = end( $log );
	/* 同じ人が1分以内に続けて保存したときは、1つにまとめます */
	if ( $last && (int) $last['u'] === $uid && $now - (int) $last['t'] < 60 && $last['a'] === $what ) {
		$log[ count( $log ) - 1 ]['t'] = $now;
	} else {
		$log[] = array( 'u' => $uid, 't' => $now, 'a' => $what );
	}
	if ( count( $log ) > 50 ) $log = array_slice( $log, -50 );
	update_post_meta( $post_id, '_ymkrf_edit_log', $log );
}

/* 編集画面・クイック編集で保存したとき（取り込みなど自動のものは入れません） */
add_action( 'save_post_ymkrf_works', function ( $post_id, $post, $update ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) || wp_doing_cron() ) return;
	if ( ! is_admin() || ! isset( $_POST['post_ID'] ) || (int) $_POST['post_ID'] !== (int) $post_id ) return;
	if ( $post->post_status === 'auto-draft' ) return;

	static $busy = array();
	if ( isset( $busy[ $post_id ] ) ) return;   /* 1回の保存で何度も呼ばれても1つだけ */
	$busy[ $post_id ] = true;

	$log   = get_post_meta( $post_id, '_ymkrf_edit_log', true );
	$first = ! is_array( $log ) || ! $log;
	$was   = isset( $_POST['original_post_status'] ) ? sanitize_key( wp_unslash( $_POST['original_post_status'] ) ) : '';
	if ( $first && in_array( $was, array( '', 'auto-draft' ), true ) ) {
		$what = 'create';
	} elseif ( $post->post_status === 'publish' && $was !== '' && $was !== 'publish' ) {
		$what = 'publish';
	} else {
		$what = 'edit';
	}
	ymkrf_edit_log_add( $post_id, $what );
}, 99, 3 );

add_action( 'wp_trash_post', function ( $post_id ) {
	if ( get_post_type( $post_id ) === 'ymkrf_works' ) ymkrf_edit_log_add( $post_id, 'trash' );
} );
add_action( 'untrashed_post', function ( $post_id ) {
	if ( get_post_type( $post_id ) === 'ymkrf_works' ) ymkrf_edit_log_add( $post_id, 'untrash' );
} );

/* ------------------------------------------------------------
   編集の権限：本部はすべて、お店のスタッフは自分のお店・自分の担当・自分の登録だけ
   ------------------------------------------------------------ */
add_filter( 'map_meta_cap', function ( $caps, $cap, $uid, $args ) {
	if ( ! in_array( $cap, array( 'edit_post', 'delete_post' ), true ) || empty( $args[0] ) ) return $caps;
	$p = get_post( (int) $args[0] );
	if ( ! $p || $p->post_type !== 'ymkrf_works' ) return $caps;
	if ( in_array( 'do_not_allow', (array) $caps, true ) ) return $caps;
	if ( $p->post_status === 'auto-draft' ) return $caps;   /* 新しく登録中 */

	static $busy = false;   /* ymkrf_is_honbu の中の user_can で、ここがもう一度呼ばれないように */
	if ( $busy ) return $caps;
	$busy = true;
	$honbu = ymkrf_is_honbu( $uid );
	$busy = false;
	if ( $honbu ) return $caps;

	if ( (int) $p->post_author === (int) $uid ) return $caps;   /* 自分が登録した */
	if ( $cap === 'delete_post' ) return array( 'do_not_allow' );   /* ゴミ箱は自分が登録したものだけ */

	$sid = (int) get_user_meta( $uid, 'ymkrf_staff_post', true );
	if ( $sid ) {
		$tanto = trim( (string) get_post_meta( $p->ID, '_ymkrf_staff', true ) );
		if ( $tanto !== '' && (int) $tanto === $sid ) return $caps;   /* 自分が担当者 */
		$mine = function_exists( 'ymkrf_staff_get_shops' ) ? (array) ymkrf_staff_get_shops( $sid ) : array();
		$shop = trim( (string) get_post_meta( $p->ID, '_ymkrf_shop', true ) );
		if ( $shop !== '' && in_array( $shop, $mine, true ) ) return $caps;   /* 自分のお店 */
	}
	return array( 'do_not_allow' );
}, 20, 4 );

/* ひらけない事例をひらこうとしたときの言葉（WordPress の英語まじりの文の代わり） */
add_action( 'load-post.php', function () {
	$id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0;
	if ( ! $id || get_post_type( $id ) !== 'ymkrf_works' ) return;
	if ( current_user_can( 'edit_post', $id ) ) return;
	wp_die( 'この施工事例は、ほかのお店の事例なので編集できません。<br>直したいところがあれば、本部に連絡してください。<br><br><a href="'
		. esc_url( admin_url( 'edit.php?post_type=ymkrf_works' ) ) . '">施工事例の一覧へもどる</a>', '編集できません', array( 'response' => 403 ) );
} );

/* コラムも同じく（ほかのお店の人が書いたコラム） */
add_action( 'load-post.php', function () {
	$id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0;
	if ( ! $id || get_post_type( $id ) !== 'ymkrf_column' ) return;
	if ( current_user_can( 'edit_post', $id ) ) return;
	wp_die( 'このコラムは、ほかのお店の人が書いた記事なので編集できません。<br>直したいところがあれば、本部に連絡してください。<br><br><a href="'
		. esc_url( admin_url( 'edit.php?post_type=ymkrf_column' ) ) . '">コラムの一覧へもどる</a>', '編集できません', array( 'response' => 403 ) );
} );

function ymkrf_edit_log_word( $a ) {
	$w = array( 'create' => '登録', 'edit' => '編集', 'publish' => '公開', 'trash' => 'ゴミ箱へ', 'untrash' => 'ゴミ箱から戻す' );
	return isset( $w[ $a ] ) ? $w[ $a ] : $a;
}

/* 一覧の列 */
add_filter( 'manage_ymkrf_works_posts_columns', function ( $cols ) {
	if ( ! ymkrf_is_honbu() ) return $cols;   /* 記録は本部だけ */
	$new = array();
	foreach ( $cols as $k => $v ) {
		if ( $k === 'date' ) $new['ymkrf_wlog'] = '登録・更新した人';
		$new[ $k ] = $v;
	}
	if ( ! isset( $new['ymkrf_wlog'] ) ) $new['ymkrf_wlog'] = '登録・更新した人';
	return $new;
}, 40 );

add_action( 'manage_ymkrf_works_posts_custom_column', function ( $col, $post_id ) {
	if ( $col !== 'ymkrf_wlog' ) return;
	$log = get_post_meta( $post_id, '_ymkrf_edit_log', true );
	$p   = get_post( $post_id );
	$by  = $p ? (int) $p->post_author : 0;
	echo '<div style="font-size:12px;line-height:1.6">';
	if ( is_array( $log ) && $log ) {
		$first = $log[0];
		$last  = end( $log );
		$mk = ( $first['a'] === 'create' ) ? '登録' : '記録はじめ';
		echo esc_html( $mk ) . '：<b>' . esc_html( ymkrf_user_label( $first['u'] ) ) . '</b><br>';
		if ( count( $log ) > 1 ) {
			echo esc_html( ymkrf_edit_log_word( $last['a'] ) ) . '：<b>' . esc_html( ymkrf_user_label( $last['u'] ) ) . '</b><br>';
		}
		echo '<span style="color:#8c8f94">' . esc_html( ymkrf_when( $last['t'] ) ) . '</span>';
	} else {
		/* 記録を始める前のもの・取り込んだもの */
		echo '<span style="color:#8c8f94">' . ( $by ? '作成：' . esc_html( ymkrf_user_label( $by ) ) . '<br>' : '' ) . '記録なし（取り込み・10/28より前）</span>';
	}
	echo '</div>';
}, 10, 2 );

add_action( 'admin_head', function () {
	$s = get_current_screen();
	if ( ! $s || $s->id !== 'edit-ymkrf_works' ) return;
	echo '<style>.column-ymkrf_wlog{width:150px}</style>';
} );

/* 編集画面の右：編集した人の記録 */
add_action( 'add_meta_boxes_ymkrf_works', function () {
	if ( ! ymkrf_is_honbu() ) return;   /* 記録は本部だけ */
	add_meta_box( 'ymkrf_works_log', '編集した人の記録', function ( $post ) {
		$log = get_post_meta( $post->ID, '_ymkrf_edit_log', true );
		if ( ! is_array( $log ) || ! $log ) {
			echo '<p class="description">まだ記録がありません。保存すると、だれがいつ保存したかがここに残ります。</p>';
			return;
		}
		echo '<ol class="ymkrf-wlog" reversed>';
		foreach ( array_reverse( $log ) as $e ) {
			echo '<li><b>' . esc_html( ymkrf_user_label( $e['u'] ) ) . '</b> が' . esc_html( ymkrf_edit_log_word( $e['a'] ) )
				. '<br><span>' . esc_html( wp_date( 'Y/n/j H:i', (int) $e['t'] ) ) . '</span></li>';
		}
		echo '</ol>';
		echo '<style>.ymkrf-wlog{margin:0 0 0 1.6em;max-height:260px;overflow:auto;font-size:12.5px;line-height:1.5}
		.ymkrf-wlog li{margin:0 0 7px}.ymkrf-wlog span{color:#8c8f94}</style>';
	}, 'ymkrf_works', 'side', 'low' );
} );

/* 1回だけ：もう作ってあるログインの権限を、段階に合わせてそろえます
   （2026/10/29 「本部は全権限」にしたので。管理者がダッシュボードを開いたときに動きます） */
add_action( 'admin_init', function () {
	if ( get_option( 'ymkrf_staff_role_sync_v2' ) === '1' ) return;   /* v2＝ページごとの権限にしたので、役割をそろえなおす */
	if ( ! current_user_can( 'promote_users' ) || ! post_type_exists( 'ymkrf_staff' ) ) return;
	$log = array();
	foreach ( get_posts( array( 'post_type' => 'ymkrf_staff', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ) ) as $sid ) {
		$u = ymkrf_staff_user( $sid );
		if ( ! $u ) continue;
		$n = ymkrf_staff_sync_role( $u, $sid );
		if ( $n !== '' ) $log[] = get_the_title( $sid ) . '：' . $n;
	}
	update_option( 'ymkrf_staff_role_sync_log', $log, false );
	update_option( 'ymkrf_staff_role_sync_v2', '1', false );
}, 50 );
