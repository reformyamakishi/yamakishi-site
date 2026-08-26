<?php
/**
 * functions.php に追記するスニペット ─ リフォームヤマキシ
 *
 * 1. CSS / JS の読み込み（ページごとに出し分け）
 * 2. カスタム投稿タイプ（施工事例 / お客様の声）
 * 3. カスタムフィールド（工事費・工期・Before画像 など）
 * 4. 表示速度まわりの調整
 *
 * ※ 必ず子テーマで作業してください（親テーマ直編集は更新で消えます）
 *
 * ── 名前の重複について ────────────────────────────────
 * 1台のサーバーに複数サイトが同居しているとのことなので、
 * 他のサイトやプラグインとぶつからないよう、すべての名前に
 * 「ymkrf」（YaMaKishi ReForm）という接頭辞を付けています。
 *
 *   定数 …………… YMKRF_VER
 *   投稿タイプ …… ymkrf_works / ymkrf_voice
 *   分類 ………… ymkrf_works_cat / ymkrf_works_area
 *   入力欄 ……… _ymkrf_price / _ymkrf_period / _ymkrf_before_img
 *                 _ymkrf_customer / _ymkrf_star
 *   関数 ………… ymkrf_meta_fields()
 *   読み込み名 … ymkrf-common / ymkrf-home / ymkrf-page
 *
 * URL（/works/ /voice/）は接頭辞なしのまま。見た目は変わりません。
 * ─────────────────────────────────────────────────
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'YMKRF_VER' ) ) define( 'YMKRF_VER', '2.3.3' );   // ファイル更新時はここを上げるとキャッシュが切れます

/* ============================================================
   1. CSS / JS の読み込み
      - 全ページ： common.css / common.js
      - トップ　： + home.css / home.js
      - 下層　　： + page.css
   ============================================================ */
add_action( 'wp_enqueue_scripts', function () {

	$dir = get_stylesheet_directory_uri();

	/* キャッチコピー用の書体（Zen Maru Gothic）。
	   丸ゴシックでやわらかく、太くても読みやすいのでシニアの方にも向いています。
	   使っているのは「地域最安値に挑戦中！」などのひとことだけです。 */
	wp_enqueue_style( 'ymkrf-gfont',
		'https://fonts.googleapis.com/css2?family=Zen+Maru+Gothic:wght@700;900&display=swap',
		array(), null );

	wp_enqueue_style( 'ymkrf-common', $dir . '/assets/css/common.css', array(), YMKRF_VER );
	wp_enqueue_script( 'ymkrf-common', $dir . '/assets/js/common.js', array(), YMKRF_VER, true );

	/* 4点パック・こだわりのページは、WordPressから見ると「指定のないページ」なので
	   そのままではトップページ扱いになってしまいます。ここで除きます。 */
	$is_special = ( function_exists( 'ymkrf_is_pack4' ) && ymkrf_is_pack4() )
	           || ( function_exists( 'ymkrf_is_about' ) && ymkrf_is_about() )
	           || ( function_exists( 'ymkrf_is_message' ) && ymkrf_is_message() )
	           || ( function_exists( 'ymkrf_is_company' ) && ymkrf_is_company() )
	           || ( function_exists( 'ymkrf_is_warranty' ) && ymkrf_is_warranty() )
	           || ( function_exists( 'ymkrf_is_flow' ) && ymkrf_is_flow() )
	           || ( function_exists( 'ymkrf_is_faq' ) && ymkrf_is_faq() )
	           || ( function_exists( 'ymkrf_is_shops' ) && ymkrf_is_shops() )
	           || ( function_exists( 'ymkrf_is_privacy' ) && ymkrf_is_privacy() );
	$is_top = is_front_page() && ! $is_special;

	if ( $is_top ) {
		wp_enqueue_style( 'ymkrf-home', $dir . '/assets/css/home.css', array( 'ymkrf-common' ), YMKRF_VER );
		wp_enqueue_script( 'ymkrf-home', $dir . '/assets/js/home.js', array( 'ymkrf-common' ), YMKRF_VER, true );
	} else {
		wp_enqueue_style( 'ymkrf-page', $dir . '/assets/css/page.css', array( 'ymkrf-common' ), YMKRF_VER );
	}

	/* こだわりページだけ、専用のCSSを1枚足します */
	if ( function_exists( 'ymkrf_is_about' ) && ymkrf_is_about() ) {
		wp_enqueue_style( 'ymkrf-lp', $dir . '/assets/css/lp.css', array( 'ymkrf-page' ), YMKRF_VER );
	}

	/* 代表挨拶・会社概要のページ（CTAの見た目は lp.css を使っています） */
	if ( ( function_exists( 'ymkrf_is_message' ) && ymkrf_is_message() )
	  || ( function_exists( 'ymkrf_is_company' ) && ymkrf_is_company() ) ) {
		wp_enqueue_style( 'ymkrf-lp', $dir . '/assets/css/lp.css', array( 'ymkrf-page' ), YMKRF_VER );
		wp_enqueue_style( 'ymkrf-company', $dir . '/assets/css/company.css', array( 'ymkrf-lp' ), YMKRF_VER );
	}

	/* 保証についてのページ（CTAの見た目は lp.css を使っています） */
	if ( function_exists( 'ymkrf_is_warranty' ) && ymkrf_is_warranty() ) {
		wp_enqueue_style( 'ymkrf-lp', $dir . '/assets/css/lp.css', array( 'ymkrf-page' ), YMKRF_VER );
		wp_enqueue_style( 'ymkrf-warranty', $dir . '/assets/css/warranty.css', array( 'ymkrf-lp' ), YMKRF_VER );
	}

	/* リフォームの流れのページ（CTAの見た目は lp.css を使っています） */
	if ( function_exists( 'ymkrf_is_flow' ) && ymkrf_is_flow() ) {
		wp_enqueue_style( 'ymkrf-lp', $dir . '/assets/css/lp.css', array( 'ymkrf-page' ), YMKRF_VER );
		wp_enqueue_style( 'ymkrf-flow', $dir . '/assets/css/flow.css', array( 'ymkrf-lp' ), YMKRF_VER );
	}

	/* よくあるご質問のページ（CTAの見た目は lp.css を使っています） */
	if ( function_exists( 'ymkrf_is_faq' ) && ymkrf_is_faq() ) {
		wp_enqueue_style( 'ymkrf-lp', $dir . '/assets/css/lp.css', array( 'ymkrf-page' ), YMKRF_VER );
		wp_enqueue_style( 'ymkrf-faq', $dir . '/assets/css/faq.css', array( 'ymkrf-lp' ), YMKRF_VER );
	}

	/* 店舗・対応エリアのページ（CTAの見た目は lp.css を使っています） */
	if ( function_exists( 'ymkrf_is_shops' ) && ymkrf_is_shops() ) {
		wp_enqueue_style( 'ymkrf-lp', $dir . '/assets/css/lp.css', array( 'ymkrf-page' ), YMKRF_VER );
		wp_enqueue_style( 'ymkrf-shops', $dir . '/assets/css/shops.css', array( 'ymkrf-lp' ), YMKRF_VER );
	}

	/* プライバシーポリシーのページ */
	if ( function_exists( 'ymkrf_is_privacy' ) && ymkrf_is_privacy() ) {
		wp_enqueue_style( 'ymkrf-privacy', $dir . '/assets/css/privacy.css', array( 'ymkrf-page' ), YMKRF_VER );
	}

	/* お客様の声のページ */
	if ( is_singular( 'ymkrf_voice' ) || is_post_type_archive( 'ymkrf_voice' ) ) {
		wp_enqueue_style( 'ymkrf-voice', $dir . '/assets/css/voice.css', array( 'ymkrf-page' ), YMKRF_VER );
	}

	/* 施工事例のページ */
	if ( is_singular( 'ymkrf_works' ) || is_post_type_archive( 'ymkrf_works' )
	  || is_tax( array( 'ymkrf_works_cat', 'ymkrf_works_area' ) ) ) {
		wp_enqueue_style( 'ymkrf-works', $dir . '/assets/css/works.css', array( 'ymkrf-page' ), YMKRF_VER );
	}

	/* スタッフのページ（施工事例のカードも使うので works.css も読みます） */
	if ( is_singular( 'ymkrf_staff' ) || is_post_type_archive( 'ymkrf_staff' ) ) {
		wp_enqueue_style( 'ymkrf-works', $dir . '/assets/css/works.css', array( 'ymkrf-page' ), YMKRF_VER );
		wp_enqueue_style( 'ymkrf-staff', $dir . '/assets/css/staff.css', array( 'ymkrf-works' ), YMKRF_VER );
	}
} );


/* ============================================================
   1-2. こだわりページ（/about/）のURL
        固定ページを作らずに、専用のURLで出しています。
        やり方は4点パック（inc/functions-product.php）と同じです。
   ============================================================ */
add_action( 'init', function () {
	add_rewrite_rule( '^about/?$', 'index.php?ymkrf_about=1', 'top' );
}, 20 );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'ymkrf_about';
	return $vars;
} );

add_filter( 'template_include', function ( $tpl ) {
	if ( ! get_query_var( 'ymkrf_about' ) ) return $tpl;
	$found = locate_template( 'ymkrf-about.php' );
	return $found ? $found : $tpl;
} );

/* このURLは「見つかりません」でもトップページでもありません。
   ここをはっきりさせないと、下層ページ用のCSSが読み込まれません。 */
add_action( 'wp', function () {
	if ( ! get_query_var( 'ymkrf_about' ) ) return;
	global $wp_query;
	$wp_query->is_404        = false;
	$wp_query->is_home       = false;
	$wp_query->is_front_page = false;
	$wp_query->is_page       = false;
	$wp_query->is_singular   = false;
	$wp_query->is_archive    = false;
	$wp_query->is_post_type_archive = false;
	status_header( 200 );
} );

/* ブラウザのタブに出る題名 */
add_filter( 'document_title_parts', function ( $parts ) {
	if ( get_query_var( 'ymkrf_about' ) ) {
		$parts['title'] = 'ヤマキシのこだわり・特徴と施工体制｜安い・早い・安心のリフォーム';
		unset( $parts['tagline'] );
	}
	return $parts;
} );

/* こだわりページかどうか。CSSの読み分けなどで使います。 */
if ( ! function_exists( 'ymkrf_is_about' ) ) :
function ymkrf_is_about() {
	return (bool) get_query_var( 'ymkrf_about' );
}
endif;

/* 旧URLからの引っ越し。
   /concept/ と /system/ は、この1枚にまとめたので /about/ へ送ります。
   （301＝恒久的な移転。検索エンジンの評価も引き継がれます） */
add_action( 'template_redirect', function () {
	$path = trim( (string) parse_url( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH ), '/' );

	/* localhost では http://localhost/reform_yamakishi/ のように
	   フォルダが1段はさまるので、その分を取り除きます */
	$base = trim( (string) parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	if ( $base !== '' ) {
		if ( $path === $base )                            $path = '';
		elseif ( strpos( $path, $base . '/' ) === 0 )     $path = substr( $path, strlen( $base ) + 1 );
	}

	/* /system/（施工体制）だけは、そのページの中の「営業・施工体制」の節に着地させます */
	if ( $path === 'system' ) {
		wp_redirect( home_url( '/about/' ) . '#system', 301 );
		exit;
	}

	/* スタッフブログは作らず、「コラム（お役立ち情報）」1本にまとめました */
	if ( $path === 'blog' ) {
		wp_redirect( home_url( '/column/' ), 301 );
		exit;
	}

	/* 対応エリアは、店舗ページに1枚でまとめました */
	if ( $path === 'area' ) {
		wp_redirect( home_url( '/shops/' ), 301 );
		exit;
	}

	$old = array( 'concept', 'lp/seikatsu-kaizen' );
	if ( in_array( $path, $old, true ) ) {
		wp_redirect( home_url( '/about/' ), 301 );
		exit;
	}
} );


/* ============================================================
   1-3. 代表挨拶ページ（/message/）のURL
        こだわりページ（1-2）とまったく同じやり方です。
   ============================================================ */
add_action( 'init', function () {
	add_rewrite_rule( '^message/?$', 'index.php?ymkrf_message=1', 'top' );
}, 20 );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'ymkrf_message';
	return $vars;
} );

add_filter( 'template_include', function ( $tpl ) {
	if ( ! get_query_var( 'ymkrf_message' ) ) return $tpl;
	$found = locate_template( 'ymkrf-message.php' );
	return $found ? $found : $tpl;
} );

add_action( 'wp', function () {
	if ( ! get_query_var( 'ymkrf_message' ) ) return;
	global $wp_query;
	$wp_query->is_404        = false;
	$wp_query->is_home       = false;
	$wp_query->is_front_page = false;
	$wp_query->is_page       = false;
	$wp_query->is_singular   = false;
	$wp_query->is_archive    = false;
	$wp_query->is_post_type_archive = false;
	status_header( 200 );
} );

add_filter( 'document_title_parts', function ( $parts ) {
	if ( get_query_var( 'ymkrf_message' ) ) {
		$parts['title'] = '代表挨拶｜株式会社山岸 代表取締役 山岸信治';
		unset( $parts['tagline'] );
	}
	return $parts;
} );

/* 代表挨拶ページかどうか。CSSの読み分けなどで使います。 */
if ( ! function_exists( 'ymkrf_is_message' ) ) :
function ymkrf_is_message() {
	return (bool) get_query_var( 'ymkrf_message' );
}
endif;


/* ============================================================
   1-4. 会社概要ページ（/company/）のURL
        こだわりページ（1-2）とまったく同じやり方です。
   ============================================================ */
add_action( 'init', function () {
	add_rewrite_rule( '^company/?$', 'index.php?ymkrf_company=1', 'top' );
}, 20 );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'ymkrf_company';
	return $vars;
} );

add_filter( 'template_include', function ( $tpl ) {
	if ( ! get_query_var( 'ymkrf_company' ) ) return $tpl;
	$found = locate_template( 'ymkrf-company.php' );
	return $found ? $found : $tpl;
} );

add_action( 'wp', function () {
	if ( ! get_query_var( 'ymkrf_company' ) ) return;
	global $wp_query;
	$wp_query->is_404        = false;
	$wp_query->is_home       = false;
	$wp_query->is_front_page = false;
	$wp_query->is_page       = false;
	$wp_query->is_singular   = false;
	$wp_query->is_archive    = false;
	$wp_query->is_post_type_archive = false;
	status_header( 200 );
} );

add_filter( 'document_title_parts', function ( $parts ) {
	if ( get_query_var( 'ymkrf_company' ) ) {
		$parts['title'] = '会社概要｜株式会社山岸（リフォームヤマキシ）';
		unset( $parts['tagline'] );
	}
	return $parts;
} );

/* 会社概要ページかどうか。CSSの読み分けなどで使います。 */
if ( ! function_exists( 'ymkrf_is_company' ) ) :
function ymkrf_is_company() {
	return (bool) get_query_var( 'ymkrf_company' );
}
endif;


/* ============================================================
   1-5. 保証についてのページ（/warranty/）のURL
        こだわりページ（1-2）とまったく同じやり方です。
   ============================================================ */
add_action( 'init', function () {
	add_rewrite_rule( '^warranty/?$', 'index.php?ymkrf_warranty=1', 'top' );
}, 20 );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'ymkrf_warranty';
	return $vars;
} );

add_filter( 'template_include', function ( $tpl ) {
	if ( ! get_query_var( 'ymkrf_warranty' ) ) return $tpl;
	$found = locate_template( 'ymkrf-warranty.php' );
	return $found ? $found : $tpl;
} );

add_action( 'wp', function () {
	if ( ! get_query_var( 'ymkrf_warranty' ) ) return;
	global $wp_query;
	$wp_query->is_404        = false;
	$wp_query->is_home       = false;
	$wp_query->is_front_page = false;
	$wp_query->is_page       = false;
	$wp_query->is_singular   = false;
	$wp_query->is_archive    = false;
	$wp_query->is_post_type_archive = false;
	status_header( 200 );
} );

add_filter( 'document_title_parts', function ( $parts ) {
	if ( get_query_var( 'ymkrf_warranty' ) ) {
		$parts['title'] = 'リフォームの保証について｜工事保証5年＋メーカー延長保証 最長10年';
		unset( $parts['tagline'] );
	}
	return $parts;
} );

/* 保証ページかどうか。CSSの読み分けなどで使います。 */
if ( ! function_exists( 'ymkrf_is_warranty' ) ) :
function ymkrf_is_warranty() {
	return (bool) get_query_var( 'ymkrf_warranty' );
}
endif;


/* ============================================================
   1-6. リフォームの流れのページ（/flow/）のURL
        こだわりページ（1-2）とまったく同じやり方です。
   ============================================================ */
add_action( 'init', function () {
	add_rewrite_rule( '^flow/?$', 'index.php?ymkrf_flow=1', 'top' );
}, 20 );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'ymkrf_flow';
	return $vars;
} );

add_filter( 'template_include', function ( $tpl ) {
	if ( ! get_query_var( 'ymkrf_flow' ) ) return $tpl;
	$found = locate_template( 'ymkrf-flow.php' );
	return $found ? $found : $tpl;
} );

add_action( 'wp', function () {
	if ( ! get_query_var( 'ymkrf_flow' ) ) return;
	global $wp_query;
	$wp_query->is_404        = false;
	$wp_query->is_home       = false;
	$wp_query->is_front_page = false;
	$wp_query->is_page       = false;
	$wp_query->is_singular   = false;
	$wp_query->is_archive    = false;
	$wp_query->is_post_type_archive = false;
	status_header( 200 );
} );

add_filter( 'document_title_parts', function ( $parts ) {
	if ( get_query_var( 'ymkrf_flow' ) ) {
		$parts['title'] = 'リフォームの流れ｜ご相談から工事・アフターサポートまで9ステップ';
		unset( $parts['tagline'] );
	}
	return $parts;
} );

/* 流れのページかどうか。CSSの読み分けなどで使います。 */
if ( ! function_exists( 'ymkrf_is_flow' ) ) :
function ymkrf_is_flow() {
	return (bool) get_query_var( 'ymkrf_flow' );
}
endif;


/* ============================================================
   1-7. よくあるご質問のページ（/faq/）のURL
        こだわりページ（1-2）とまったく同じやり方です。
   ============================================================ */
add_action( 'init', function () {
	add_rewrite_rule( '^faq/?$', 'index.php?ymkrf_faq=1', 'top' );
}, 20 );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'ymkrf_faq';
	return $vars;
} );

add_filter( 'template_include', function ( $tpl ) {
	if ( ! get_query_var( 'ymkrf_faq' ) ) return $tpl;
	$found = locate_template( 'ymkrf-faq.php' );
	return $found ? $found : $tpl;
} );

add_action( 'wp', function () {
	if ( ! get_query_var( 'ymkrf_faq' ) ) return;
	global $wp_query;
	$wp_query->is_404        = false;
	$wp_query->is_home       = false;
	$wp_query->is_front_page = false;
	$wp_query->is_page       = false;
	$wp_query->is_singular   = false;
	$wp_query->is_archive    = false;
	$wp_query->is_post_type_archive = false;
	status_header( 200 );
} );

add_filter( 'document_title_parts', function ( $parts ) {
	if ( get_query_var( 'ymkrf_faq' ) ) {
		$parts['title'] = 'よくあるご質問｜見積り・追加費用・保証・工事のことまで';
		unset( $parts['tagline'] );
	}
	return $parts;
} );

/* よくあるご質問のページかどうか。CSSの読み分けなどで使います。 */
if ( ! function_exists( 'ymkrf_is_faq' ) ) :
function ymkrf_is_faq() {
	return (bool) get_query_var( 'ymkrf_faq' );
}
endif;


/* ============================================================
   1-8. 店舗・対応エリアのページ（/shops/）のURL
        「対応エリア」も1枚にまとめています。/area/ はここへ送ります。
   ============================================================ */
add_action( 'init', function () {
	add_rewrite_rule( '^shops/?$', 'index.php?ymkrf_shops=1', 'top' );
}, 20 );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'ymkrf_shops';
	return $vars;
} );

add_filter( 'template_include', function ( $tpl ) {
	if ( ! get_query_var( 'ymkrf_shops' ) ) return $tpl;
	$found = locate_template( 'ymkrf-shops.php' );
	return $found ? $found : $tpl;
} );

add_action( 'wp', function () {
	if ( ! get_query_var( 'ymkrf_shops' ) ) return;
	global $wp_query;
	$wp_query->is_404        = false;
	$wp_query->is_home       = false;
	$wp_query->is_front_page = false;
	$wp_query->is_page       = false;
	$wp_query->is_singular   = false;
	$wp_query->is_archive    = false;
	$wp_query->is_post_type_archive = false;
	status_header( 200 );
} );

add_filter( 'document_title_parts', function ( $parts ) {
	if ( get_query_var( 'ymkrf_shops' ) ) {
		$parts['title'] = '店舗・対応エリア｜石川県・福井県に11店舗（金沢市・小松市・福井市ほか）';
		unset( $parts['tagline'] );
	}
	return $parts;
} );

/* 店舗ページかどうか。CSSの読み分けなどで使います。 */
if ( ! function_exists( 'ymkrf_is_shops' ) ) :
function ymkrf_is_shops() {
	return (bool) get_query_var( 'ymkrf_shops' );
}
endif;

/* ============================================================
   1-9. プライバシーポリシーのページ（/privacy/）のURL
   ============================================================ */
add_action( 'init', function () {
	add_rewrite_rule( '^privacy/?$', 'index.php?ymkrf_privacy=1', 'top' );
}, 20 );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'ymkrf_privacy';
	return $vars;
} );

add_filter( 'template_include', function ( $tpl ) {
	if ( ! get_query_var( 'ymkrf_privacy' ) ) return $tpl;
	$found = locate_template( 'ymkrf-privacy.php' );
	return $found ? $found : $tpl;
} );

add_action( 'wp', function () {
	if ( ! get_query_var( 'ymkrf_privacy' ) ) return;
	global $wp_query;
	$wp_query->is_404        = false;
	$wp_query->is_home       = false;
	$wp_query->is_front_page = false;
	$wp_query->is_page       = false;
	$wp_query->is_singular   = false;
	$wp_query->is_archive    = false;
	$wp_query->is_post_type_archive = false;
	status_header( 200 );
} );

add_filter( 'document_title_parts', function ( $parts ) {
	if ( get_query_var( 'ymkrf_privacy' ) ) {
		$parts['title'] = 'プライバシーポリシー（個人情報保護方針）';
		unset( $parts['tagline'] );
	}
	return $parts;
} );

/* プライバシーポリシーのページかどうか。CSSの読み分けなどで使います。 */
if ( ! function_exists( 'ymkrf_is_privacy' ) ) :
function ymkrf_is_privacy() {
	return (bool) get_query_var( 'ymkrf_privacy' );
}
endif;

/* ============================================================
   1-10. コメントは使いません
        リフォーム会社のサイトでコメント欄を開けておくと、
        迷惑コメント（スパム）の的になるだけなので、全部閉じます。
        2026/08/20 ユーザー判断。
   ============================================================ */

/* これから書く記事に、コメント欄を作らない */
add_action( 'init', function () {
	foreach ( get_post_types() as $t ) {
		if ( post_type_supports( $t, 'comments' ) ) {
			remove_post_type_support( $t, 'comments' );
			remove_post_type_support( $t, 'trackbacks' );
		}
	}
}, 100 );

/* すでにある記事のコメント欄も閉じて、たまっているコメントも出さない */
add_filter( 'comments_open',  '__return_false', 20 );
add_filter( 'pings_open',     '__return_false', 20 );
add_filter( 'comments_array', '__return_empty_array', 20 );

/* 管理画面から「コメント」を消す */
add_action( 'admin_menu', function () {
	remove_menu_page( 'edit-comments.php' );
}, 999 );

add_action( 'admin_bar_menu', function ( $bar ) {
	$bar->remove_node( 'comments' );
}, 999 );

add_action( 'admin_init', function () {
	/* コメントの画面をひらこうとしたら、ダッシュボードにもどします */
	if ( isset( $GLOBALS['pagenow'] ) && $GLOBALS['pagenow'] === 'edit-comments.php' ) {
		wp_safe_redirect( admin_url() );
		exit;
	}
	/* 編集画面の「ディスカッション」「コメント」の欄も消します */
	foreach ( get_post_types() as $t ) {
		remove_meta_box( 'commentsdiv',       $t, 'normal' );
		remove_meta_box( 'commentstatusdiv',  $t, 'normal' );
		remove_meta_box( 'trackbacksdiv',     $t, 'normal' );
	}
} );

/* ============================================================
   1-11. 「投稿」は使いません
        「投稿」はWordPress本体の機能なので、取り除くことはできません。
        そのかわり、管理画面から隠して、URLは「お知らせ」に飛ばします。
        こうしておけば、まちがって投稿に書いてしまっても、
        デザインの当たっていないページがお客様に見えることはありません。
        2026/08/20 ユーザー判断。書きものは「お知らせ」と「コラム」の2つだけです。
   ============================================================ */

/* 管理画面の左メニューから「投稿」を隠す */
add_action( 'admin_menu', function () {
	remove_menu_page( 'edit.php' );                                   /* 投稿一覧・新規追加 */
	remove_submenu_page( 'edit.php', 'edit-tags.php?taxonomy=category' );
	remove_submenu_page( 'edit.php', 'edit-tags.php?taxonomy=post_tag' );
}, 999 );

/* 上のバーの「＋新規 → 投稿」も消す */
add_action( 'admin_bar_menu', function ( $bar ) {
	$bar->remove_node( 'new-post' );
}, 999 );

/* 投稿の画面をURLで直接ひらこうとしたら、お知らせにもどします */
add_action( 'admin_init', function () {
	if ( ! isset( $GLOBALS['pagenow'] ) ) return;
	$now  = $GLOBALS['pagenow'];
	$type = isset( $_GET['post_type'] ) ? sanitize_key( $_GET['post_type'] ) : 'post';
	$tax  = isset( $_GET['taxonomy'] )  ? sanitize_key( $_GET['taxonomy'] )  : '';

	$is_post_screen =
		   ( in_array( $now, array( 'edit.php', 'post-new.php' ), true ) && $type === 'post' )
		|| ( $now === 'edit-tags.php' && in_array( $tax, array( 'category', 'post_tag' ), true ) );

	/* 既存の1件を編集しにきた場合だけは通します（中身を移したり消したりできるように） */
	if ( $now === 'post.php' ) return;

	if ( $is_post_screen ) {
		wp_safe_redirect( admin_url( 'edit.php?post_type=ymkrf_news' ) );
		exit;
	}
} );

/* サイト側：投稿・カテゴリー・タグ・投稿の年月別ページは、すべてお知らせへ */
add_action( 'template_redirect', function () {
	if ( is_admin() ) return;
	if ( is_singular( 'post' ) || is_category() || is_tag() || is_date() || is_author()
	     || ( is_home() && ! is_front_page() ) ) {
		wp_safe_redirect( home_url( '/news/' ), 301 );
		exit;
	}
}, 1 );

/* ============================================================
   1-12. ダッシュボード（管理画面のトップ）の片づけ
        「投稿」を使わないので、投稿まわりのパネルを消して、
        かわりに「どこに書けばいいか」が分かるパネルを出します。
   ============================================================ */
add_action( 'wp_dashboard_setup', function () {
	/* いらないパネルを消します */
	remove_meta_box( 'dashboard_quick_press',   'dashboard', 'side' );   /* クイックドラフト（投稿ができてしまう） */
	remove_meta_box( 'dashboard_primary',       'dashboard', 'side' );   /* WordPressイベントとニュース */
	remove_meta_box( 'dashboard_activity',      'dashboard', 'normal' ); /* アクティビティ（投稿とコメント） */
	remove_meta_box( 'dashboard_right_now',     'dashboard', 'normal' ); /* 概要（投稿数・コメント数） */
	remove_meta_box( 'dashboard_incoming_links','dashboard', 'normal' );
	remove_meta_box( 'dashboard_plugins',       'dashboard', 'normal' );
	remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' );
	remove_meta_box( 'dashboard_recent_comments','dashboard','normal' );

	wp_add_dashboard_widget( 'ymkrf_dash', 'リフォームヤマキシ　書くところ', 'ymkrf_dashboard_widget' );
} );

function ymkrf_dashboard_widget() {
	$items = array(
		array( 'ymkrf_news',    'お知らせ',     '新しいお店・補助金・営業時間など、日付もののお知らせ' ),
		array( 'ymkrf_column',  'コラム',       'ずっと読まれる解説記事。スタッフブログもここに書きます' ),
		array( 'ymkrf_works',   '施工事例',     'Before / After と、かかった費用' ),
		array( 'ymkrf_voice',   'お客様の声',   'アンケート（仕事の通信簿）の登録' ),
		array( 'ymkrf_staff',   'スタッフ',     '名前・顔写真・店舗' ),
		array( 'ymkrf_product', '商品',         'キッチン・お風呂・トイレ・洗面化粧台' ),
	);
	echo '<p style="margin:0 0 12px;color:#646970">'
	   . 'サイトに出るのは、この6つに書いたものだけです。'
	   . '<b>「投稿」は使いません</b>（メニューから隠してあります）。</p>';
	echo '<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px">';
	foreach ( $items as $it ) {
		list( $type, $name, $desc ) = $it;
		$n = wp_count_posts( $type );
		$c = $n ? (int) $n->publish : 0;
		printf(
			'<a href="%s" style="display:block;padding:10px 12px;border:1px solid #dcdcde;border-radius:6px;'
			. 'background:#fff;text-decoration:none;color:#1d2327">'
			. '<b style="font-size:13.5px">%s</b>'
			. '<span style="float:right;color:#fe3301;font-weight:700">%d</span>'
			. '<span style="display:block;margin-top:3px;font-size:11.5px;color:#787c82;line-height:1.5">%s</span></a>',
			esc_url( admin_url( 'edit.php?post_type=' . $type ) ),
			esc_html( $name ), $c, esc_html( $desc )
		);
	}
	echo '</div>';
}

/* defer 属性を付けて描画をブロックしないようにする */
add_filter( 'script_loader_tag', function ( $tag, $handle ) {
	if ( in_array( $handle, array( 'ymkrf-common', 'ymkrf-home' ), true ) ) {
		return str_replace( ' src', ' defer src', $tag );
	}
	return $tag;
}, 10, 2 );


/* ============================================================
   2. カスタム投稿タイプ
   ============================================================ */
add_action( 'init', function () {

	/* --- 施工事例 ---
	   URLは「/works/部位/案件番号/」の形にします。
	     例）/works/kitchen/2604-0180/
	   %ymkrf_wpart% のところに、部位の英字が入ります（詳しくは inc/functions-works.php の2）。

	   権限は施工事例だけの専用にしています。
	   これで「施工事例スタッフ」の人に、ここだけをまかせられます。 */
	add_rewrite_tag( '%ymkrf_wpart%', '([a-z0-9-]+)' );

	register_post_type( 'ymkrf_works', array(
		'label'        => '施工事例',
		'public'       => true,
		'has_archive'  => 'works',   /* 一覧は /works/（部位は付けません） */
		'menu_icon'    => 'dashicons-hammer',
		'menu_position'=> 5,
		'rewrite'      => array( 'slug' => 'works/%ymkrf_wpart%', 'with_front' => false ),
		/* 本文（エディター）は使いません。工事の内容は「施工データ」の
		   「おこなった工事」に、写真は同じく「写真」に入れます。 */
		'supports'     => array( 'title', 'thumbnail', 'excerpt' ),
		'show_in_rest' => true,
		'capability_type' => array( 'ymkrf_work', 'ymkrf_works' ),
		'map_meta_cap'    => true,
	) );

	/* 部位（キッチン／お風呂／トイレ …） */
	register_taxonomy( 'ymkrf_works_cat', 'ymkrf_works', array(
		'label'        => '部位',
		'hierarchical' => true,
		'rewrite'      => array( 'slug' => 'works', 'with_front' => false ),
		'show_in_rest' => true,
		'capabilities' => array(
			'manage_terms' => 'manage_ymkrf_works_terms',
			'edit_terms'   => 'manage_ymkrf_works_terms',
			'delete_terms' => 'manage_ymkrf_works_terms',
			'assign_terms' => 'edit_ymkrf_works',
		),
	) );

	/* エリア（金沢市／小松市 …）＝ 地域検索の受け皿になります */
	register_taxonomy( 'ymkrf_works_area', 'ymkrf_works', array(
		'label'        => 'エリア',
		'hierarchical' => true,
		'rewrite'      => array( 'slug' => 'works-area', 'with_front' => false ),
		'show_in_rest' => true,
		'capabilities' => array(
			'manage_terms' => 'manage_ymkrf_works_terms',
			'edit_terms'   => 'manage_ymkrf_works_terms',
			'delete_terms' => 'manage_ymkrf_works_terms',
			'assign_terms' => 'edit_ymkrf_works',
		),
	) );

	/* --- お客様の声 ---
	   本文（エディター）と抜粋は使いません。
	   ページに出る中身は、すべて「お客様アンケート」の欄から組み立てています。
	   入力画面に出しておくと「ここに書くのかな」と迷うもとになるので外しています。 */
	/* URLは「/voice/工事箇所/案件番号/」の形にします。
	   例）/voice/oiltank/2607-0389/
	   %ymkrf_vpart% のところに、工事箇所の英字が入ります（詳しくは functions-voice.php の5-1）。 */
	add_rewrite_tag( '%ymkrf_vpart%', '([a-z0-9-]+)' );

	register_post_type( 'ymkrf_voice', array(
		'label'        => 'お客様の声',
		'public'       => true,
		'has_archive'  => 'voice',   /* 一覧は /voice/（工事箇所は付けません） */
		'menu_icon'    => 'dashicons-format-quote',
		'menu_position'=> 6,
		'rewrite'      => array( 'slug' => 'voice/%ymkrf_vpart%', 'with_front' => false ),
		'supports'     => array( 'title' ),
		'show_in_rest' => true,
	) );
} );


/* ============================================================
   2-2. 満足度の星
        アンケートの「満足度は何点ですか？」（100点満点）から、
        星のうまり具合と色を出します。
        点数が入っていないときは、③〜⑧の5段階評価から見当をつけます。
   ============================================================ */

/* 点数 → 色の段階。CSSの .c-stars[data-band="…"] と対応しています。 */
if ( ! function_exists( 'ymkrf_score_band' ) ) :
function ymkrf_score_band( $score ) {
	$score = (int) $score;
	if ( $score >= 95 ) return 's';   // きらきらした金
	if ( $score >= 85 ) return 'a';
	if ( $score >= 70 ) return 'b';
	if ( $score >= 55 ) return 'c';
	return 'd';                        // おちついた灰
}
endif;

/**
 * 星を出します。
 *
 * @param int  $score     満足度（0〜100）
 * @param bool $show_num  「80点」の文字も出すか
 */
if ( ! function_exists( 'ymkrf_stars' ) ) :
function ymkrf_stars( $score, $show_num = true ) {
	$score = max( 0, min( 100, (int) $score ) );
	$band  = ymkrf_score_band( $score );
	$star5 = round( $score / 10 ) / 2;      // 5段階に直した数（0.5きざみ）
	$lbl   = sprintf( '満足度 %d点（5段階で%s）', $score,
	         rtrim( rtrim( number_format( $star5, 1 ), '0' ), '.' ) );

	$h  = '<span class="c-starsrow">';
	$h .= '<span class="c-stars" data-band="' . esc_attr( $band ) . '"';
	$h .= ' style="--rate:' . $score . '%"';
	$h .= ' role="img" aria-label="' . esc_attr( $lbl ) . '">';
	$h .= '<span class="c-stars__base" aria-hidden="true">★★★★★</span>';
	$h .= '<span class="c-stars__fill" aria-hidden="true">★★★★★</span>';
	$h .= '</span>';
	if ( $show_num ) $h .= '<span class="c-stars__score">' . $score . '点</span>';
	$h .= '</span>';
	return $h;
}
endif;

/**
 * 点数の記入がなかったときに、③〜⑧の評価から点数を見当づけます。
 *
 * ★点数の記入があるときは、かならずそちらが優先されます（ここは使いません）。
 *
 * 評価と点数の対応
 *   大変良かった … 100点
 *   満足　　　　 …  85点
 *   普通　　　　 …  70点
 *   よくなかった …  40点
 *
 * 「普通」を単純に真ん中（33点）にすると、実際のお客様の感覚とかけ離れます。
 * 実例として、③〜⑧をすべて「普通」とされたお客様が、
 * 満足度の欄にはご自身で「80点」と書かれていました。
 * その感覚に近づけた対応にしてあります。
 */
if ( ! function_exists( 'ymkrf_score_from_ratings' ) ) :
function ymkrf_score_from_ratings( $ratings ) {
	$map = array( 4 => 100, 3 => 85, 2 => 70, 1 => 40 );
	$sum = 0; $n = 0;
	foreach ( (array) $ratings as $r ) {
		$r = (int) $r;
		if ( isset( $map[ $r ] ) ) { $sum += $map[ $r ]; $n++; }
	}
	if ( ! $n ) return 0;
	return (int) round( $sum / $n );
}
endif;


/* ============================================================
   3. カスタムフィールド（ACF を使わない場合の簡易メタボックス）
   ============================================================ */
add_action( 'add_meta_boxes', function () {

	add_meta_box( 'ymkrf_works_box', '施工データ', function ( $post ) {
		wp_nonce_field( 'ymkrf_meta_save', 'ymkrf_meta_nonce' );
		ymkrf_meta_fields( $post->ID, array(
			'_ymkrf_price'   => array( '工事費（例：128万円）', 'text' ),
			'_ymkrf_period'  => array( '工期（例：3日）', 'text' ),
			/* お客様の声と同じ番号を入れると、おたがいに自動でリンクします */
			'_ymkrf_case_no' => array( '案件番号（お客様の声とつなぐ番号）', 'text' ),
		) );
		ymkrf_works_before_field( $post->ID );
	}, 'ymkrf_works', 'side' );

	add_meta_box( 'ymkrf_voice_box', 'お客様情報', function ( $post ) {
		wp_nonce_field( 'ymkrf_meta_save', 'ymkrf_meta_nonce' );
		ymkrf_meta_fields( $post->ID, array(
			'_ymkrf_customer' => array( 'お客様（例：金沢市／K様（40代）・キッチンリフォーム）', 'text' ),
			'_ymkrf_star'     => array( '評価（1〜5）', 'number' ),
		) );
	}, 'ymkrf_voice', 'side' );
} );

if ( ! function_exists( 'ymkrf_meta_fields' ) ) :
function ymkrf_meta_fields( $post_id, $fields ) {
	foreach ( $fields as $key => $f ) {
		printf(
			'<p><label for="%3$s" style="display:block;font-weight:600">%1$s</label>
			 <input type="%2$s" id="%3$s" name="%3$s" value="%4$s" style="width:100%%"></p>',
			esc_html( $f[0] ), esc_attr( $f[1] ), esc_attr( $key ),
			esc_attr( get_post_meta( $post_id, $key, true ) )
		);
	}
}
endif;

/* ------------------------------------------------------------
   Before写真をえらぶ欄（施工事例の編集画面・右側）

   施工事例のカードは、
     ・Before … ここでえらんだ写真
     ・After  … アイキャッチ画像
   の2枚で、トップページと同じ「左右に動かして見くらべる」表示になります。
   Before写真を入れないときは、アイキャッチ画像だけが出ます。
   ------------------------------------------------------------ */
if ( ! function_exists( 'ymkrf_works_before_field' ) ) :
function ymkrf_works_before_field( $post_id ) {

	$bid = (int) get_post_meta( $post_id, '_ymkrf_before_img', true );
	$src = $bid ? wp_get_attachment_image_url( $bid, 'medium' ) : '';
	?>
	<p style="font-weight:600;margin-bottom:4px">Before写真（施工前）</p>
	<div id="ymkrf-before-box" style="margin-bottom:6px">
		<img id="ymkrf-before-prev" src="<?php echo esc_url( $src ); ?>"
		     style="max-width:100%;height:auto;border-radius:6px;<?php echo $src ? '' : 'display:none'; ?>">
	</div>
	<input type="hidden" id="_ymkrf_before_img" name="_ymkrf_before_img" value="<?php echo esc_attr( $bid ); ?>">
	<p>
		<button type="button" class="button" id="ymkrf-before-pick">写真をえらぶ</button>
		<button type="button" class="button" id="ymkrf-before-clear">はずす</button>
	</p>
	<p class="description">
		入れておくと、トップページと同じ「左右に動かして見くらべる」表示になります。<br>
		入れないときは、アイキャッチ画像だけが出ます。
	</p>
	<script>
	jQuery(function ($) {
		var frame;
		$('#ymkrf-before-pick').on('click', function (e) {
			e.preventDefault();
			if (frame) { frame.open(); return; }
			frame = wp.media({ title: 'Before写真をえらぶ', library: { type: 'image' }, multiple: false });
			frame.on('select', function () {
				var a = frame.state().get('selection').first().toJSON();
				$('#_ymkrf_before_img').val(a.id);
				var u = (a.sizes && a.sizes.medium) ? a.sizes.medium.url : a.url;
				$('#ymkrf-before-prev').attr('src', u).show();
			});
			frame.open();
		});
		$('#ymkrf-before-clear').on('click', function (e) {
			e.preventDefault();
			$('#_ymkrf_before_img').val('');
			$('#ymkrf-before-prev').attr('src', '').hide();
		});
	});
	</script>
	<?php
}
endif;

/* 施工事例・お客様の声の編集画面で、写真をえらぶ画面を使えるようにします */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	global $post_type;
	if ( in_array( $post_type, array( 'ymkrf_works', 'ymkrf_voice' ), true )
	     && in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		wp_enqueue_media();
	}
} );

add_action( 'save_post', function ( $post_id ) {
	/* ★以前ここが 'ymk_meta_save' になっていて、上の欄（工事費・工期・Before写真）が
	     まったく保存できていませんでした。'ymkrf_meta_save' が正しい合言葉です。 */
	if ( ! isset( $_POST['ymkrf_meta_nonce'] ) ||
	     ! wp_verify_nonce( sanitize_key( $_POST['ymkrf_meta_nonce'] ), 'ymkrf_meta_save' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	foreach ( array( '_ymkrf_price', '_ymkrf_period', '_ymkrf_before_img',
	                 '_ymkrf_customer', '_ymkrf_star', '_ymkrf_case_no' ) as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
		}
	}
} );


/* ============================================================
   4. 表示速度まわり（Core Web Vitals 対策）
   ============================================================ */
add_action( 'init', function () {
	/* 絵文字用スクリプトを止める */
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
} );

/* ブロックエディタのCSSを使っていない場合は下のコメントを外す
add_action( 'wp_enqueue_scripts', function () {
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'global-styles' );
}, 100 );
*/

/* 抜粋の長さと省略記号 */
add_filter( 'excerpt_length', function () { return 90; } );
add_filter( 'excerpt_more',   function () { return '…'; } );
