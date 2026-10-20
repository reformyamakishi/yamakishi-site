<?php
/**
 * エラーコード一覧を、管理画面から直せるようにします
 * リフォームヤマキシ（ymkrf）　2026/10/20
 *
 * ユーザー指示
 *   「旧サイトにある給湯器・エコキュートのエラーコード一覧のぺージを追加してほしい」
 *   「見やすいようにバージョンあっぷさせて。内容は同じで」
 *   「特にエラーコードの英数字をもっとひと目でわかるように」
 *   「この内容をワードプレスに組み込んで。後から修正できるようにしておいてほしい」
 *
 * ■ しくみ
 *   管理画面の左メニュー「エラーコード」に、
 *   「エコキュート｜三菱電機」のように、種類×メーカーごとに1つずつ表があります。
 *   開くと、コード・内容・原因・対処方法を1件ずつ直したり、足したり、消したりできます。
 *
 *   ページの場所（URL）はこれまでと同じです。
 *     /troubleshooting/                         … 入口（給湯器の種類をえらぶ）
 *     /troubleshooting/<種類>-error/             … メーカーをえらぶ
 *     /troubleshooting/<種類>-error/<メーカー>/   … エラーコードの表
 *
 * ■ 中身のもと
 *   はじめは theme/troubleshooting/<種類>-<メーカー>.php（旧サイトから写したもの）を、
 *   1回だけ管理画面の表に流しこみます。そのあとは管理画面の表が正です。
 *   （PHPファイルは、管理画面に表が無いときの予備としてだけ読みます）
 *
 * ■ 名前
 *   投稿タイプ … ymkrf_errcode
 *   保存さき   … _ymkrf_ts_cat（種類） / _ymkrf_ts_maker（URLの英字） /
 *                _ymkrf_ts_maker_name（メーカー名） / _ymkrf_ts_rows（表の中身）
 */

if ( ! defined( 'ABSPATH' ) ) exit;


/* ============================================================
   1. 種類（ここは めったに変わらないので、ファイルに書いています）
   ============================================================ */
function ymkrf_ts_types() {
	return array(
		'ecocute' => array(
			'name'    => 'エコキュート',
			'lead'    => '空気の熱でお湯をわかす、電気の給湯器です。タンクとヒートポンプの2つで1組になっています。',
			'product' => 'ecocute',   /* 交換のご案内で出す商品カテゴリ */
		),
		'gas' => array(
			'name'    => 'ガス給湯器',
			'lead'    => '都市ガス・プロパンガスをお使いのお宅の給湯器です。家の外の壁に掛けて取り付けます。',
			'product' => 'boiler',
		),
		'oil' => array(
			'name'    => '石油給湯器',
			'lead'    => '灯油をお使いのお宅の給湯器です。家の外に置いて取り付けます。',
			'product' => 'boiler',
		),
		'electric' => array(
			'name'    => '電気温水器',
			'lead'    => 'タンクの中のヒーターでお湯をわかすタイプです。エコキュートとは別のものです。',
			'product' => 'ecocute',
		),
	);
}

/* 最初に流しこむときの、メーカーの名前と並び順（旧サイトと同じ） */
function ymkrf_ts_seed_makers() {
	return array(
		'ecocute'  => array( 'mitsubishi' => '三菱電機', 'panasonic' => 'パナソニック', 'daikin' => 'ダイキン', 'hitachi' => '日立' ),
		'gas'      => array( 'noritz' => 'ノーリツ', 'rinnai' => 'リンナイ', 'paloma' => 'パロマ' ),
		'oil'      => array( 'noritz' => 'ノーリツ', 'chofu' => '長府製作所', 'corona' => 'コロナ' ),
		'electric' => array( 'mitsubishi' => '三菱電機', 'panasonic' => 'パナソニック', 'hitachi' => '日立',
		                     'chofu' => '長府製作所', 'corona' => 'コロナ', 'takarastandard' => 'タカラスタンダード' ),
	);
}


/* ============================================================
   2. 管理画面の「エラーコード」
   ============================================================ */
add_action( 'init', function () {
	register_post_type( 'ymkrf_errcode', array(
		'labels' => array(
			'name'          => 'エラーコード',
			'singular_name' => 'エラーコード表',
			'menu_name'     => 'エラーコード',
			'all_items'     => 'エラーコード表の一覧',
			'add_new'       => '表を足す',
			'add_new_item'  => 'エラーコード表を足す（新しいメーカー）',
			'edit_item'     => 'エラーコード表を直す',
			'search_items'  => '表をさがす',
		),
		'public'              => false,   /* ページそのものは /troubleshooting/ で出します */
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_rest'        => false,
		'exclude_from_search' => true,
		'menu_icon'           => 'dashicons-warning',
		'menu_position'       => 27,
		'supports'            => array( 'title', 'page-attributes' ),
		'capability_type'     => 'post',
	) );
}, 5 );

/** その種類の表（公開しているもの）を、並び順どおりに */
function ymkrf_ts_lists( $cat ) {
	return get_posts( array(
		'post_type'      => 'ymkrf_errcode',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ),
		'meta_query'     => array( array( 'key' => '_ymkrf_ts_cat', 'value' => (string) $cat ) ),
	) );
}

/** 種類×メーカーの表を1つ */
function ymkrf_ts_get( $cat, $maker ) {
	$p = get_posts( array(
		'post_type'      => 'ymkrf_errcode',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'meta_query'     => array(
			'relation' => 'AND',
			array( 'key' => '_ymkrf_ts_cat',   'value' => (string) $cat ),
			array( 'key' => '_ymkrf_ts_maker', 'value' => (string) $maker ),
		),
	) );
	return $p ? $p[0] : null;
}

/** 表の中身。管理画面の表が無いときは、もとの PHP ファイルを読みます */
function ymkrf_ts_rows( $cat, $maker, $post = null ) {
	if ( $post ) {
		$rows = get_post_meta( $post->ID, '_ymkrf_ts_rows', true );
		if ( is_array( $rows ) ) return $rows;
	}
	$file = get_stylesheet_directory() . '/troubleshooting/' . $cat . '-' . $maker . '.php';
	if ( file_exists( $file ) ) {
		$loaded = include $file;
		if ( is_array( $loaded ) ) return $loaded;
	}
	return array();
}


/* ------------------------------------------------------------
   2-1. 旧サイトから写した表を、1回だけ管理画面に流しこみます
   ------------------------------------------------------------ */
/* 管理画面を開く前に表のページが見られても空にならないよう、どのページを開いたときでも動きます（1回だけ） */
add_action( 'init', function () {
	if ( get_option( 'ymkrf_errcode_import_v1' ) === '1' ) return;
	if ( ! post_type_exists( 'ymkrf_errcode' ) ) return;

	$order = 0;
	$made  = 0;
	foreach ( ymkrf_ts_seed_makers() as $cat => $makers ) {
		foreach ( $makers as $slug => $mname ) {
			$order += 10;
			if ( ymkrf_ts_get( $cat, $slug ) ) continue;   /* もう有る表は、さわりません */

			$rows  = ymkrf_ts_rows( $cat, $slug );
			$types = ymkrf_ts_types();
			$id = wp_insert_post( array(
				'post_type'   => 'ymkrf_errcode',
				'post_status' => 'publish',
				'post_title'  => $types[ $cat ]['name'] . '｜' . $mname,
				'menu_order'  => $order,
			) );
			if ( ! $id || is_wp_error( $id ) ) continue;
			update_post_meta( $id, '_ymkrf_ts_cat', $cat );
			update_post_meta( $id, '_ymkrf_ts_maker', $slug );
			update_post_meta( $id, '_ymkrf_ts_maker_name', $mname );
			update_post_meta( $id, '_ymkrf_ts_rows', ymkrf_ts_clean_rows( $rows ) );
			$made++;
		}
	}
	update_option( 'ymkrf_errcode_import_v1', '1', false );
	if ( $made ) set_transient( 'ymkrf_errcode_imported', $made, DAY_IN_SECONDS );
}, 20 );

add_action( 'admin_notices', function () {
	$n = get_transient( 'ymkrf_errcode_imported' );
	if ( ! $n ) return;
	delete_transient( 'ymkrf_errcode_imported' );
	echo '<div class="notice notice-success is-dismissible"><p>'
	   . 'エラーコード一覧の表を <b>' . (int) $n . 'つ</b> 管理画面に入れました。'
	   . '左メニューの「エラーコード」から直せます。</p></div>';
} );

/** 表の中身をそろえます（空の行は落とします） */
function ymkrf_ts_clean_rows( $rows ) {
	$out = array();
	foreach ( (array) $rows as $r ) {
		if ( ! is_array( $r ) ) continue;
		$one = array(
			'code'  => trim( sanitize_text_field( (string) ( $r['code'] ?? '' ) ) ),
			'title' => trim( sanitize_text_field( (string) ( $r['title'] ?? '' ) ) ),
			'cause' => trim( sanitize_textarea_field( (string) ( $r['cause'] ?? '' ) ) ),
			'fix'   => trim( sanitize_textarea_field( (string) ( $r['fix'] ?? '' ) ) ),
		);
		if ( implode( '', $one ) === '' ) continue;
		$out[] = $one;
	}
	return $out;
}


/* ------------------------------------------------------------
   2-2. 一覧画面の列（種類・メーカー・件数・ページ）
   ------------------------------------------------------------ */
add_filter( 'manage_ymkrf_errcode_posts_columns', function ( $cols ) {
	$out = array();
	foreach ( $cols as $k => $v ) {
		if ( $k === 'date' ) continue;
		$out[ $k ] = $v;
		if ( $k === 'title' ) {
			$out['ymkrf_ts_n']    = '件数';
			$out['ymkrf_ts_page'] = 'ページ';
		}
	}
	return $out;
} );
add_action( 'manage_ymkrf_errcode_posts_custom_column', function ( $col, $id ) {
	$cat = (string) get_post_meta( $id, '_ymkrf_ts_cat', true );
	$mk  = (string) get_post_meta( $id, '_ymkrf_ts_maker', true );
	if ( $col === 'ymkrf_ts_n' ) {
		$rows = get_post_meta( $id, '_ymkrf_ts_rows', true );
		echo (int) ( is_array( $rows ) ? count( $rows ) : 0 ) . '件';
	}
	if ( $col === 'ymkrf_ts_page' && $cat && $mk ) {
		$u = home_url( '/troubleshooting/' . $cat . '-error/' . $mk . '/' );
		echo '<a href="' . esc_url( $u ) . '" target="_blank" rel="noopener">ページを見る</a>';
	}
}, 10, 2 );

/* ------------------------------------------------------------
   2-2b. 左メニューを種類ごとに分けます
         （2026/10/20 ユーザー指示「商品登録のところみたいに、エコキュート、ガス給湯器、
           石油給湯器、電気温水器のカテゴリにまず分けて見やすくして」）
   ------------------------------------------------------------ */
add_action( 'admin_menu', function () {
	$base = 'edit.php?post_type=ymkrf_errcode';
	/* 「エラーコード」を押したときに開く、種類とメーカーをえらぶ画面（商品と同じ形） */
	add_submenu_page( $base, '種類からえらぶ', '種類からえらぶ', 'edit_posts', 'ymkrf-errcode-home', 'ymkrf_errcode_home_page' );
	foreach ( ymkrf_ts_types() as $k => $t ) {
		add_submenu_page( $base, $t['name'], $t['name'], 'edit_posts', $base . '&ectype=' . $k, '' );
	}
}, 20 );

/* 並び：えらぶ画面（いちばん上＝「エラーコード」を押したときの行き先）→ 種類4つ → すべて → 表を足す */
add_action( 'admin_menu', function () {
	global $submenu;
	$base = 'edit.php?post_type=ymkrf_errcode';
	if ( empty( $submenu[ $base ] ) ) return;
	$home = array(); $types = array(); $rest = array();
	foreach ( $submenu[ $base ] as $row ) {
		if ( ! isset( $row[2] ) ) continue;
		if ( $row[2] === 'ymkrf-errcode-home' )            $home[]  = $row;
		elseif ( strpos( $row[2], '&ectype=' ) !== false ) $types[] = $row;
		else                                               $rest[]  = $row;
	}
	$submenu[ $base ] = array_merge( $home, $types, $rest );
}, 999 );

/* 「種類からえらぶ」「すべての表」は、左の「エラーコード」を押せば行けるので、見た目だけ隠します。
   いま開いている種類には、商品と同じ水色の帯を付けます */
add_action( 'admin_head', function () {
	?>
	<style>
	/* 左メニューは「エラーコード」だけにします。種類（エコキュート・ガス給湯器…）は、
	   押したあとの右側の画面でえらびます（2026/10/20 ユーザー指示「左メニューにはエラーコードだけ。右側で大分類を」） */
	#menu-posts-ymkrf_errcode .wp-submenu,
	#menu-posts-ymkrf_errcode .wp-submenu-wrap{display:none!important}
	#menu-posts-ymkrf_errcode .wp-submenu li.current a,
	#menu-posts-ymkrf_errcode .wp-submenu a.current{background:#a7d8f5;color:#0a2540;border-radius:3px;font-weight:700}
	</style>
	<?php
} );

/** 「エラーコード」を押したときの画面
    ① まず大分類（エコキュート／ガス給湯器／石油給湯器／電気温水器）だけを大きくならべます
    ② 種類を押すと、同じ画面のまま、その種類のメーカーがならびます
    （2026/10/20 ユーザー指示「大分類で、まずエコキュートかガス給湯器か石油給湯器か、電気温水器か にして」） */
function ymkrf_errcode_home_url( $cat = '' ) {
	$u = 'edit.php?post_type=ymkrf_errcode&page=ymkrf-errcode-home';
	return admin_url( $cat !== '' ? $u . '&ectype=' . $cat : $u );
}
function ymkrf_errcode_home_page() {
	$types = ymkrf_ts_types();
	$cat   = isset( $_GET['ectype'] ) ? sanitize_key( wp_unslash( $_GET['ectype'] ) ) : '';
	if ( ! isset( $types[ $cat ] ) ) $cat = '';
	$get = function ( $k ) {
		return get_posts( array(
			'post_type' => 'ymkrf_errcode', 'post_status' => 'any', 'posts_per_page' => -1,
			'orderby' => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ),
			'meta_query' => array( array( 'key' => '_ymkrf_ts_cat', 'value' => $k ) ),
		) );
	};
	$cnt = function ( $p ) { $r = get_post_meta( $p->ID, '_ymkrf_ts_rows', true ); return is_array( $r ) ? count( $r ) : 0; };
	?>
	<div class="wrap ymkrf-ech">
	<?php if ( $cat === '' ) : /* ---------- ① 大分類 ---------- */ ?>
		<h1>エラーコード</h1>
		<p class="ymkrf-ech__lead">まず、給湯器の種類をえらんでください。</p>
		<div class="ymkrf-ech__types">
		<?php foreach ( $types as $k => $t ) :
			$posts = $get( $k ); $total = 0;
			foreach ( $posts as $p ) $total += $cnt( $p ); ?>
			<a class="ymkrf-ech__type" href="<?php echo esc_url( ymkrf_errcode_home_url( $k ) ); ?>">
				<span class="ymkrf-ech__name"><?php echo esc_html( $t['name'] ); ?></span>
				<span class="ymkrf-ech__cnt"><?php echo count( $posts ); ?>メーカー・<?php echo (int) $total; ?>件</span>
				<span class="ymkrf-ech__go">えらぶ →</span>
			</a>
		<?php endforeach; ?>
		</div>
		<p class="ymkrf-ech__foot">
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ymkrf_errcode' ) ); ?>">すべての表を一覧で見る</a>　／
			<a href="<?php echo esc_url( home_url( '/troubleshooting/' ) ); ?>" target="_blank" rel="noopener">お客様が見るページを開く</a>
		</p>
	<?php else : /* ---------- ② メーカー ---------- */
		$posts = $get( $cat ); ?>
		<p class="ymkrf-ech__crumb"><a href="<?php echo esc_url( ymkrf_errcode_home_url() ); ?>">エラーコード</a> ＞ <?php echo esc_html( $types[ $cat ]['name'] ); ?></p>
		<h1><?php echo esc_html( $types[ $cat ]['name'] ); ?>のエラーコード</h1>
		<p class="ymkrf-ech__lead">メーカーをえらぶと、そのエラーコード表が開きます。</p>
		<div class="ymkrf-ech__makers">
			<?php foreach ( $posts as $p ) :
				$mn = (string) get_post_meta( $p->ID, '_ymkrf_ts_maker_name', true );
				$mk = (string) get_post_meta( $p->ID, '_ymkrf_ts_maker', true );
				$dr = $p->post_status !== 'publish'; ?>
				<div class="ymkrf-ech__mk<?php echo $dr ? ' is-draft' : ''; ?>">
					<a class="ymkrf-ech__mkmain" href="<?php echo esc_url( get_edit_post_link( $p->ID ) ); ?>">
						<b><?php echo esc_html( $mn !== '' ? $mn : $p->post_title ); ?></b>
						<span><?php echo (int) $cnt( $p ); ?>件<?php echo $dr ? '・下書き' : ''; ?></span>
					</a>
				</div>
			<?php endforeach; ?>
			<a class="ymkrf-ech__add" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=ymkrf_errcode&ectype=' . $cat ) ); ?>">＋ メーカーを足す</a>
		</div>
		<p class="ymkrf-ech__foot"><a href="<?php echo esc_url( ymkrf_errcode_home_url() ); ?>">← 種類をえらびなおす</a></p>
	<?php endif; ?>
	</div>
	<style>
	.ymkrf-ech__lead{margin:6px 0 18px;color:#50575e;font-size:14px}
	.ymkrf-ech__crumb{margin:4px 0 0;font-size:13px;color:#646970}
	.ymkrf-ech__types{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:16px;max-width:1100px}
	.ymkrf-ech__type{display:flex;flex-direction:column;gap:6px;padding:26px 22px 20px;background:#eff8f2;border:1px solid #00782a;
	  border-radius:10px;text-decoration:none;color:#1d2327;transition:background .15s}
	.ymkrf-ech__type:hover{background:#e0f1e6}
	.ymkrf-ech__name{font-size:22px;font-weight:700}
	.ymkrf-ech__cnt{font-size:13px;color:#00782a;font-weight:700}
	.ymkrf-ech__go{margin-top:10px;font-size:13px;color:#2271b1;font-weight:700}
	.ymkrf-ech__makers{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:12px;max-width:1100px}
	.ymkrf-ech__mk{background:#fff;border:1px solid #dcdcde;border-left:5px solid #fe3301;border-radius:8px;overflow:hidden}
	.ymkrf-ech__mk.is-draft{border-left-color:#a7aaad;opacity:.75}
	.ymkrf-ech__mkmain{display:block;padding:16px;text-decoration:none;color:#1d2327}
	.ymkrf-ech__mkmain b{display:block;font-size:18px}
	.ymkrf-ech__mkmain span{font-size:12.5px;color:#646970}
	.ymkrf-ech__mkmain:hover b{color:#c92800}
	.ymkrf-ech__view{display:block;padding:0 16px 12px;font-size:12px}
	.ymkrf-ech__add{display:flex;align-items:center;justify-content:center;min-height:84px;border:2px dashed #c3c4c7;border-radius:8px;
	  color:#787c82;font-weight:700;text-decoration:none}
	.ymkrf-ech__add:hover{border-color:#fe3301;color:#fe3301}
	.ymkrf-ech__foot{margin-top:22px;font-size:13px}
	</style>
	<?php
}

/* いま開いている種類のメニューを、色つきにします */
add_filter( 'submenu_file', function ( $file ) {
	global $typenow;
	if ( $typenow !== 'ymkrf_errcode' ) return $file;
	$cat = isset( $_GET['ectype'] ) ? sanitize_key( wp_unslash( $_GET['ectype'] ) ) : '';
	if ( $cat === '' && isset( $_GET['post'] ) ) $cat = (string) get_post_meta( (int) $_GET['post'], '_ymkrf_ts_cat', true );
	return isset( ymkrf_ts_types()[ $cat ] ) ? 'edit.php?post_type=ymkrf_errcode&ectype=' . $cat : $file;
} );

/* 種類でしぼりこみます */
add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() || ! $q->is_main_query() || $q->get( 'post_type' ) !== 'ymkrf_errcode' ) return;
	$cat = isset( $_GET['ectype'] ) ? sanitize_key( wp_unslash( $_GET['ectype'] ) ) : '';
	if ( ! isset( ymkrf_ts_types()[ $cat ] ) ) return;
	$q->set( 'meta_query', array( array( 'key' => '_ymkrf_ts_cat', 'value' => $cat ) ) );
} );

/* 一覧の上にも、種類の切りかえを出します（件数つき） */
add_filter( 'views_edit-ymkrf_errcode', function ( $views ) {
	$now = isset( $_GET['ectype'] ) ? sanitize_key( wp_unslash( $_GET['ectype'] ) ) : '';
	$out = array();
	$out['ectype_all'] = sprintf( '<a href="%s"%s>すべて</a>',
		esc_url( admin_url( 'edit.php?post_type=ymkrf_errcode' ) ), $now === '' ? ' class="current"' : '' );
	foreach ( ymkrf_ts_types() as $k => $t ) {
		$n = count( get_posts( array( 'post_type' => 'ymkrf_errcode', 'post_status' => 'any', 'posts_per_page' => -1,
			'fields' => 'ids', 'meta_query' => array( array( 'key' => '_ymkrf_ts_cat', 'value' => $k ) ) ) ) );
		$out[ 'ectype_' . $k ] = sprintf( '<a href="%s"%s>%s <span class="count">（%d）</span></a>',
			esc_url( admin_url( 'edit.php?post_type=ymkrf_errcode&ectype=' . $k ) ),
			$now === $k ? ' class="current"' : '', esc_html( $t['name'] ), $n );
	}
	return $out;
} );

/* 種類の一覧の上に、メーカーごとの札をならべます。押すと、そのメーカーの表がすぐ開きます
   （2026/10/20 ユーザー指示「エコキュートのメーカーでまた分けて。三菱電機、パナソニック、ダイキン、日立」） */
add_action( 'all_admin_notices', function () {
	global $typenow, $pagenow;
	/* 「エラーコード」トップ画面（page=ymkrf-errcode-home）は自分でメーカーを並べるので、ここでは出しません
	   （2026/10/20 ユーザー指摘「エコキュートのメーカーとエコキュートのエラーコード 2行、同じものならんでいる」） */
	if ( $pagenow !== 'edit.php' || $typenow !== 'ymkrf_errcode' || empty( $_GET['ectype'] ) || ! empty( $_GET['page'] ) ) return;
	$cat = sanitize_key( wp_unslash( $_GET['ectype'] ) );
	$types = ymkrf_ts_types();
	if ( ! isset( $types[ $cat ] ) ) return;
	$posts = get_posts( array(
		'post_type' => 'ymkrf_errcode', 'post_status' => 'any', 'posts_per_page' => -1,
		'orderby' => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ),
		'meta_query' => array( array( 'key' => '_ymkrf_ts_cat', 'value' => $cat ) ),
	) );
	?>
	<div class="ymkrf-ecmk">
		<p style="margin:0 0 8px"><a href="<?php echo esc_url( ymkrf_errcode_home_url() ); ?>">← 種類からえらびなおす</a></p>
		<p class="ymkrf-ecmk__ttl"><?php echo esc_html( $types[ $cat ]['name'] ); ?>のメーカー <span>押すと、そのメーカーのエラーコード表が開きます</span></p>
		<div class="ymkrf-ecmk__list">
			<?php foreach ( $posts as $p ) :
				$mn   = (string) get_post_meta( $p->ID, '_ymkrf_ts_maker_name', true );
				$mk   = (string) get_post_meta( $p->ID, '_ymkrf_ts_maker', true );
				$rows = get_post_meta( $p->ID, '_ymkrf_ts_rows', true );
				$n    = is_array( $rows ) ? count( $rows ) : 0; ?>
				<div class="ymkrf-ecmk__card<?php echo $p->post_status !== 'publish' ? ' is-draft' : ''; ?>">
					<a class="ymkrf-ecmk__main" href="<?php echo esc_url( get_edit_post_link( $p->ID ) ); ?>">
						<b><?php echo esc_html( $mn !== '' ? $mn : $p->post_title ); ?></b>
						<span><?php echo (int) $n; ?>件<?php echo $p->post_status !== 'publish' ? '・下書き' : ''; ?></span>
					</a>
				</div>
			<?php endforeach; ?>
			<a class="ymkrf-ecmk__add" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=ymkrf_errcode&ectype=' . $cat ) ); ?>">＋ メーカーを足す</a>
		</div>
	</div>
	<style>
	.ymkrf-ecmk{margin:16px 20px 4px 2px}
	.ymkrf-ecmk__ttl{margin:0 0 8px;font-size:15px;font-weight:700}
	.ymkrf-ecmk__ttl span{margin-left:8px;font-size:12px;font-weight:400;color:#646970}
	.ymkrf-ecmk__list{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:10px}
	.ymkrf-ecmk__card{background:#fff;border:1px solid #dcdcde;border-left:5px solid #fe3301;border-radius:8px;overflow:hidden}
	.ymkrf-ecmk__card.is-draft{border-left-color:#a7aaad;opacity:.75}
	.ymkrf-ecmk__main{display:block;padding:12px 14px;text-decoration:none;color:#1d2327}
	.ymkrf-ecmk__main b{display:block;font-size:17px}
	.ymkrf-ecmk__main span{font-size:12px;color:#646970}
	.ymkrf-ecmk__main:hover b{color:#c92800}
	.ymkrf-ecmk__view{display:block;padding:0 14px 10px;font-size:12px}
	.ymkrf-ecmk__add{display:flex;align-items:center;justify-content:center;min-height:70px;border:2px dashed #c3c4c7;border-radius:8px;color:#787c82;font-weight:700;text-decoration:none}
	.ymkrf-ecmk__add:hover{border-color:#fe3301;color:#fe3301}
	</style>
	<?php
} );

/* 種類をえらんでいるときの「表を足す」は、その種類を最初からえらんだ状態にします */
add_action( 'admin_head-edit.php', function () {
	global $typenow;
	if ( $typenow !== 'ymkrf_errcode' || empty( $_GET['ectype'] ) || ! empty( $_GET['page'] ) ) return;
	$cat = sanitize_key( wp_unslash( $_GET['ectype'] ) );
	if ( ! isset( ymkrf_ts_types()[ $cat ] ) ) return;
	$name = ymkrf_ts_types()[ $cat ]['name'];
	?>
	<script>
	document.addEventListener('DOMContentLoaded', function () {
	  var a = document.querySelector('.page-title-action');
	  if (a) { a.href = a.href + (a.href.indexOf('?') < 0 ? '?' : '&') + 'ectype=<?php echo esc_js( $cat ); ?>'; a.textContent = '<?php echo esc_js( $name ); ?>の表を足す'; }
	  var h = document.querySelector('.wp-heading-inline');
	  if (h) h.textContent = 'エラーコード：<?php echo esc_js( $name ); ?>';
	});
	</script>
	<?php
} );

/* 一覧は並び順（種類ごと → メーカー）で */
add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() || ! $q->is_main_query() ) return;
	if ( $q->get( 'post_type' ) !== 'ymkrf_errcode' || $q->get( 'orderby' ) ) return;
	$q->set( 'orderby', array( 'menu_order' => 'ASC', 'ID' => 'ASC' ) );
} );


/* ------------------------------------------------------------
   2-3. 編集画面
   ------------------------------------------------------------ */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'ymkrf_ts_head', '種類とメーカー', 'ymkrf_ts_box_head', 'ymkrf_errcode', 'normal', 'high' );
	add_meta_box( 'ymkrf_ts_rows', 'エラーコード', 'ymkrf_ts_box_rows', 'ymkrf_errcode', 'normal', 'high' );
} );

function ymkrf_ts_box_head( $post ) {
	wp_nonce_field( 'ymkrf_ts_save', 'ymkrf_ts_nonce' );
	$cat = (string) get_post_meta( $post->ID, '_ymkrf_ts_cat', true );
	if ( $cat === '' && isset( $_GET['ectype'] ) ) $cat = sanitize_key( wp_unslash( $_GET['ectype'] ) );   /* 種類のメニューから足したとき */
	$mk  = (string) get_post_meta( $post->ID, '_ymkrf_ts_maker', true );
	$mn  = (string) get_post_meta( $post->ID, '_ymkrf_ts_maker_name', true );
	?>
	<p style="margin:4px 0 10px"><a href="<?php echo esc_url( ymkrf_errcode_home_url() ); ?>">← エラーコードのトップ（種類からえらぶ）へ</a>
		<?php if ( $cat !== '' ) : ?>　／　<a href="<?php echo esc_url( ymkrf_errcode_home_url( $cat ) ); ?>"><?php echo esc_html( ymkrf_ts_types()[ $cat ]['name'] ?? '' ); ?>のメーカー一覧へ</a><?php endif; ?></p>
	<table class="form-table" style="margin:0">
		<tr><th style="width:150px">給湯器の種類</th><td>
			<select name="ymkrf_ts_cat">
				<?php foreach ( ymkrf_ts_types() as $k => $t ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>"<?php selected( $cat, $k ); ?>><?php echo esc_html( $t['name'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</td></tr>
		<tr><th>メーカー名</th><td>
			<input type="text" name="ymkrf_ts_maker_name" value="<?php echo esc_attr( $mn ); ?>" class="regular-text" placeholder="例：ノーリツ">
		</td></tr>
		<tr><th>URLの英字</th><td>
			<input type="text" name="ymkrf_ts_maker" value="<?php echo esc_attr( $mk ); ?>" class="regular-text" placeholder="例：noritz" pattern="[a-z0-9-]+">
			<p class="description">半角の小文字・数字・ハイフンだけ。ページの住所になります
				（/troubleshooting/<?php echo esc_html( $cat ? $cat : '種類' ); ?>-error/<b><?php echo esc_html( $mk ? $mk : '英字' ); ?></b>/）。
				一度決めたら、なるべく変えないでください。</p>
			<?php if ( $cat && $mk && $post->post_status === 'publish' ) : ?>
				<p><a href="<?php echo esc_url( home_url( '/troubleshooting/' . $cat . '-error/' . $mk . '/' ) ); ?>" target="_blank" rel="noopener">→ このページを見る</a></p>
			<?php endif; ?>
		</td></tr>
	</table>
	<?php
}

function ymkrf_ts_box_rows( $post ) {
	$rows = get_post_meta( $post->ID, '_ymkrf_ts_rows', true );
	if ( ! is_array( $rows ) ) $rows = array();
	$row = function ( $i, $r ) {
		$v = function ( $k ) use ( $r ) { return isset( $r[ $k ] ) ? (string) $r[ $k ] : ''; };
		$n = function ( $k ) use ( $i ) { return 'ymkrf_ts_rows[' . $i . '][' . $k . ']'; };
		ob_start(); ?>
		<div class="ymkrf-ec">
			<div class="ymkrf-ec__side">
				<input type="text" class="ymkrf-ec__code" name="<?php echo esc_attr( $n( 'code' ) ); ?>"
				       value="<?php echo esc_attr( $v( 'code' ) ); ?>" placeholder="コード" aria-label="コード">
				<span class="ymkrf-ec__mv">
					<button type="button" class="button-link ymkrf-ec__up" title="ひとつ上へ">↑</button>
					<button type="button" class="button-link ymkrf-ec__dn" title="ひとつ下へ">↓</button>
				</span>
			</div>
			<div class="ymkrf-ec__main">
				<input type="text" class="ymkrf-ec__title" name="<?php echo esc_attr( $n( 'title' ) ); ?>"
				       value="<?php echo esc_attr( $v( 'title' ) ); ?>" placeholder="内容（例：わき上げ不良）※無ければ空のまま" aria-label="内容">
				<div class="ymkrf-ec__two">
					<label><span>原因</span>
						<textarea name="<?php echo esc_attr( $n( 'cause' ) ); ?>" rows="3"><?php echo esc_textarea( $v( 'cause' ) ); ?></textarea></label>
					<label><span>対処方法 <small>（①②…で行を分けると、ページでは番号つきの箇条書きになります）</small></span>
						<textarea name="<?php echo esc_attr( $n( 'fix' ) ); ?>" rows="3"><?php echo esc_textarea( $v( 'fix' ) ); ?></textarea></label>
				</div>
			</div>
			<button type="button" class="button-link ymkrf-ec__del" title="このコードを消す">×</button>
		</div>
		<?php return ob_get_clean();
	};
	?>
	<div class="ymkrf-ecbar">
		<input type="search" id="ymkrf-ec-find" placeholder="コードや言葉でしぼりこむ（例：102、凍結）">
		<span class="ymkrf-ecbar__n"><?php echo count( $rows ); ?>件</span>
		<button type="button" class="button ymkrf-ec__addtop">＋ いちばん上に足す</button>
	</div>
	<div class="ymkrf-ecs">
		<?php foreach ( $rows as $i => $r ) echo $row( $i, $r ); /* phpcs:ignore */ ?>
	</div>
	<p><button type="button" class="button button-primary ymkrf-ec__add">＋ コードを1つ足す</button></p>
	<script type="text/html" id="ymkrf-ec-tpl"><?php echo $row( '__i__', array() ); /* phpcs:ignore */ ?></script>

	<style>
	.ymkrf-ecbar{position:sticky;top:32px;z-index:5;display:flex;gap:10px;align-items:center;background:#fff;padding:10px 0;border-bottom:1px solid #eee;margin-bottom:10px}
	.ymkrf-ecbar input{flex:1;max-width:420px;font-size:15px;padding:5px 10px}
	.ymkrf-ecbar__n{color:#646970;font-size:13px}
	.ymkrf-ecs{display:grid;gap:10px}
	.ymkrf-ec{position:relative;display:grid;grid-template-columns:120px 1fr;gap:12px;background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:12px 36px 12px 12px}
	.ymkrf-ec__side{display:flex;flex-direction:column;gap:6px;align-items:stretch}
	.ymkrf-ec__code{font:800 22px/1.2 ui-monospace,Menlo,Consolas,monospace!important;text-align:center;color:#c92800;
	  background:#fff4f0!important;border:2px solid #fe3301!important;border-radius:8px!important;padding:8px 4px!important;letter-spacing:.04em}
	.ymkrf-ec__mv{display:flex;justify-content:center;gap:4px}
	.ymkrf-ec__mv button{font-size:15px;text-decoration:none;color:#50575e}
	.ymkrf-ec__title{width:100%;font-size:15px;font-weight:700;margin-bottom:8px}
	.ymkrf-ec__two{display:grid;grid-template-columns:1fr 1fr;gap:10px}
	.ymkrf-ec__two label span{display:block;font-size:12px;font-weight:700;color:#50575e;margin-bottom:3px}
	.ymkrf-ec__two label span small{font-weight:400;color:#8c8f94}
	.ymkrf-ec__two textarea{width:100%;font-size:13.5px;line-height:1.7}
	.ymkrf-ec__del{position:absolute;top:8px;right:10px;color:#b32d2e;font-size:17px;font-weight:700;text-decoration:none}
	@media (max-width:900px){.ymkrf-ec{grid-template-columns:1fr}.ymkrf-ec__two{grid-template-columns:1fr}}
	</style>
	<script>
	jQuery(function ($) {
	  var $wrap = $('.ymkrf-ecs'), tpl = $('#ymkrf-ec-tpl').html();
	  function uid(){ return 'n' + Date.now() + Math.floor(Math.random() * 1000); }
	  function count(){ $('.ymkrf-ecbar__n').text($wrap.children('.ymkrf-ec').length + '件'); }
	  $('.ymkrf-ec__add').on('click', function(){
	    var $r = $(tpl.replace(/__i__/g, uid())).appendTo($wrap); count(); $r.find('.ymkrf-ec__code').trigger('focus');
	  });
	  $('.ymkrf-ec__addtop').on('click', function(){
	    var $r = $(tpl.replace(/__i__/g, uid())).prependTo($wrap); count(); $r.find('.ymkrf-ec__code').trigger('focus');
	  });
	  $wrap.on('click', '.ymkrf-ec__del', function(){
	    var $r = $(this).closest('.ymkrf-ec');
	    var c = $.trim($r.find('.ymkrf-ec__code').val());
	    if (c && !window.confirm('コード「' + c + '」を消します。よろしいですか？（「更新」を押すまでは元にもどせます）')) return;
	    $r.remove(); count();
	  });
	  $wrap.on('click', '.ymkrf-ec__up', function(){ var $r = $(this).closest('.ymkrf-ec'); $r.insertBefore($r.prev('.ymkrf-ec')); });
	  $wrap.on('click', '.ymkrf-ec__dn', function(){ var $r = $(this).closest('.ymkrf-ec'); $r.insertAfter($r.next('.ymkrf-ec')); });
	  /* しぼりこみ（全角も半角として見ます） */
	  function norm(s){ return String(s || '').replace(/[！-～]/g, function(c){ return String.fromCharCode(c.charCodeAt(0) - 0xFEE0); }).toLowerCase(); }
	  $('#ymkrf-ec-find').on('input', function(){
	    var q = norm($.trim(this.value));
	    $wrap.children('.ymkrf-ec').each(function(){
	      var t = norm($(this).find('input,textarea').map(function(){ return this.value; }).get().join(' '));
	      $(this).toggle(q === '' || t.indexOf(q) >= 0);
	    });
	  });
	});
	</script>
	<?php
}

add_action( 'save_post_ymkrf_errcode', function ( $post_id ) {
	if ( ! isset( $_POST['ymkrf_ts_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ymkrf_ts_nonce'] ), 'ymkrf_ts_save' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	$types = ymkrf_ts_types();
	$cat = isset( $_POST['ymkrf_ts_cat'] ) ? sanitize_key( wp_unslash( $_POST['ymkrf_ts_cat'] ) ) : '';
	if ( isset( $types[ $cat ] ) ) update_post_meta( $post_id, '_ymkrf_ts_cat', $cat );

	$mk = isset( $_POST['ymkrf_ts_maker'] ) ? sanitize_title( wp_unslash( $_POST['ymkrf_ts_maker'] ) ) : '';
	if ( $mk !== '' ) update_post_meta( $post_id, '_ymkrf_ts_maker', $mk );

	$mn = isset( $_POST['ymkrf_ts_maker_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ymkrf_ts_maker_name'] ) ) : '';
	update_post_meta( $post_id, '_ymkrf_ts_maker_name', $mn );

	$rows = isset( $_POST['ymkrf_ts_rows'] ) && is_array( $_POST['ymkrf_ts_rows'] ) ? wp_unslash( $_POST['ymkrf_ts_rows'] ) : array();
	update_post_meta( $post_id, '_ymkrf_ts_rows', ymkrf_ts_clean_rows( $rows ) );
} );

/* 題名が空なら「エコキュート｜三菱電機」の形で自動で付けます */
add_filter( 'wp_insert_post_data', function ( $data, $arr ) {
	if ( $data['post_type'] !== 'ymkrf_errcode' ) return $data;
	if ( trim( (string) $data['post_title'] ) !== '' ) return $data;
	$types = ymkrf_ts_types();
	$cat = isset( $_POST['ymkrf_ts_cat'] ) ? sanitize_key( wp_unslash( $_POST['ymkrf_ts_cat'] ) ) : '';
	$mn  = isset( $_POST['ymkrf_ts_maker_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ymkrf_ts_maker_name'] ) ) : '';
	if ( isset( $types[ $cat ] ) && $mn !== '' ) $data['post_title'] = $types[ $cat ]['name'] . '｜' . $mn;
	return $data;
}, 10, 2 );


/* ============================================================
   3. ページの題名・説明文（SEO）
      SEO SIMPLE PACK が題名を上書きしてしまうので、そちらの出口で入れます
   ============================================================ */
function ymkrf_ts_meta() {
	$types = ymkrf_ts_types();
	$c = (string) get_query_var( 'ymkrf_ts_cat' );
	$m = (string) get_query_var( 'ymkrf_ts_maker' );

	if ( $c !== '' && $m !== '' && isset( $types[ $c ] ) && ( $p = ymkrf_ts_get( $c, $m ) ) ) {
		$mn   = (string) get_post_meta( $p->ID, '_ymkrf_ts_maker_name', true );
		$rows = ymkrf_ts_rows( $c, $m, $p );
		$eg   = array_slice( array_filter( wp_list_pluck( $rows, 'code' ) ), 0, 3 );
		return array(
			'title' => $mn . ' ' . $types[ $c ]['name'] . 'のエラーコード一覧【全' . count( $rows ) . '件】原因と対処方法',
			'desc'  => $mn . 'の' . $types[ $c ]['name'] . 'のリモコンに出るエラーコード' . count( $rows ) . '件を、原因と対処方法つきでまとめました。'
			         . ( $eg ? '「' . implode( '」「', $eg ) . '」などの番号で、すぐに探せます。' : '' )
			         . '直らないときは石川県・福井県のリフォームヤマキシへ。',
			'url'   => home_url( '/troubleshooting/' . $c . '-error/' . $m . '/' ),
		);
	}
	if ( $c !== '' && isset( $types[ $c ] ) ) {
		$names = array();
		foreach ( ymkrf_ts_lists( $c ) as $p ) $names[] = (string) get_post_meta( $p->ID, '_ymkrf_ts_maker_name', true );
		return array(
			'title' => $types[ $c ]['name'] . 'のエラーコード一覧｜メーカー別に原因と対処方法',
			'desc'  => $types[ $c ]['name'] . 'のエラーコードを、メーカー別（' . implode( '・', array_filter( $names ) ) . '）にまとめました。リモコンの番号から原因と対処方法を探せます。',
			'url'   => home_url( '/troubleshooting/' . $c . '-error/' ),
		);
	}
	return array(
		'title' => '給湯器・エコキュートのエラーコード一覧｜故障かな？と思ったら',
		'desc'  => 'エコキュート・ガス給湯器・石油給湯器・電気温水器のエラーコードを、メーカー別に原因と対処方法つきでまとめました。修理や交換をご依頼になる前にご確認ください。',
		'url'   => home_url( '/troubleshooting/' ),
	);
}
add_filter( 'ssp_output_title', function ( $t ) {
	if ( ! function_exists( 'ymkrf_is_ts' ) || ! ymkrf_is_ts() ) return $t;
	$m = ymkrf_ts_meta();
	return $m['title'] . ' | ' . get_bloginfo( 'name' );
}, 20 );
add_filter( 'ssp_output_description', function ( $d ) {
	if ( ! function_exists( 'ymkrf_is_ts' ) || ! ymkrf_is_ts() ) return $d;
	$m = ymkrf_ts_meta();
	return $m['desc'];
}, 20 );
add_filter( 'ssp_output_canonical', function ( $u ) {
	if ( ! function_exists( 'ymkrf_is_ts' ) || ! ymkrf_is_ts() ) return $u;
	$m = ymkrf_ts_meta();
	return $m['url'];
}, 20 );
foreach ( array( 'ssp_output_og_title' => 'title', 'ssp_output_og_description' => 'desc', 'ssp_output_og_url' => 'url' ) as $hook => $key ) {
	add_filter( $hook, function ( $v ) use ( $key ) {
		if ( ! function_exists( 'ymkrf_is_ts' ) || ! ymkrf_is_ts() ) return $v;
		$m = ymkrf_ts_meta();
		return $m[ $key ];
	}, 20 );
}


/* ============================================================
   4. 見た目（エラーコードのページだけ読みます）
   ============================================================ */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! function_exists( 'ymkrf_is_ts' ) || ! ymkrf_is_ts() ) return;
	$dir = get_stylesheet_directory_uri();
	$ver = defined( 'YMKRF_VER' ) ? YMKRF_VER : '1';
	wp_enqueue_style( 'ymkrf-errcode', $dir . '/assets/css/errcode.css', array(), $ver );
}, 30 );

/* 2026/10/20 の調べもので書き出した一時ファイルを消します */
add_action( 'admin_init', function () {
	$f = WP_CONTENT_DIR . '/ymkrf-menu-debug.txt';
	if ( file_exists( $f ) ) @unlink( $f );
} );
