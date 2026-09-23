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


/**
 * パンくずの構造化データ（BreadcrumbList）を出します。
 * 画面に出ているパンくずと同じ中身を、Googleにも伝えるためのものです。
 * （2026/09/22 ユーザー指示「SEOに強くなるように」）
 *
 * 使いかた：
 *   ymkrf_crumb_ld( array(
 *     array( 'ホーム',   home_url( '/' ) ),
 *     array( '施工事例', get_post_type_archive_link( 'ymkrf_works' ) ),
 *     array( 'キッチン', get_term_link( $t ) ),
 *     array( '金沢市 K様', get_permalink() ),
 *   ) );
 */
if ( ! function_exists( 'ymkrf_crumb_ld' ) ) :
function ymkrf_crumb_ld( $crumbs ) {

	$items = array();
	$n     = 0;

	foreach ( (array) $crumbs as $c ) {
		$name = isset( $c[0] ) ? trim( (string) $c[0] ) : '';
		$url  = isset( $c[1] ) ? (string) $c[1] : '';
		if ( $name === '' ) continue;
		$n++;
		$one = array( '@type' => 'ListItem', 'position' => $n, 'name' => $name );
		if ( $url !== '' && ! is_wp_error( $url ) ) $one['item'] = $url;
		$items[] = $one;
	}
	if ( ! $items ) return;

	echo '<script type="application/ld+json">' . wp_json_encode( array(
		'@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items,
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
endif;

if ( ! defined( 'YMKRF_VER' ) ) define( 'YMKRF_VER', '4.6.3' );   // ファイル更新時はここを上げるとキャッシュが切れます

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
	           || ( function_exists( 'ymkrf_is_bguide' ) && ymkrf_is_bguide() )
	           || ( function_exists( 'ymkrf_is_ts' ) && ymkrf_is_ts() )
	           || ( function_exists( 'ymkrf_is_inquiry' ) && ymkrf_is_inquiry() )
	           || ( function_exists( 'ymkrf_is_webrsv' ) && ymkrf_is_webrsv() )
	           || ( function_exists( 'ymkrf_is_flyer' ) && ymkrf_is_flyer() )
	           || ( function_exists( 'ymkrf_is_privacy' ) && ymkrf_is_privacy() );
	$is_top = is_front_page() && ! $is_special;

	if ( $is_top ) {
		wp_enqueue_style( 'ymkrf-home', $dir . '/assets/css/home.css', array( 'ymkrf-common' ), YMKRF_VER );
		wp_enqueue_script( 'ymkrf-home', $dir . '/assets/js/home.js', array( 'ymkrf-common' ), YMKRF_VER, true );
	} else {
		wp_enqueue_style( 'ymkrf-page', $dir . '/assets/css/page.css', array( 'ymkrf-common' ), YMKRF_VER );
	}

	/* 給湯器・エコキュートの選び方のページ。
	   商品ページと同じ見た目の部品を使うので、product.css を足します。 */
	if ( ( function_exists( 'ymkrf_is_bguide' ) && ymkrf_is_bguide() )
	  || ( function_exists( 'ymkrf_is_ts' ) && ymkrf_is_ts() ) ) {
		wp_enqueue_style( 'ymkrf-product', $dir . '/assets/css/product.css', array( 'ymkrf-page' ), YMKRF_VER );
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


/* ============================================================
   1-3. 給湯器・エコキュートの選び方（/products/boiler-guide/）のURL
        やり方は上の「こだわりページ」と同じです。
        商品を並べる一覧ではなく、読みもののページです。
   ============================================================ */
add_action( 'init', function () {
	add_rewrite_rule( '^products/boiler-guide/?$', 'index.php?ymkrf_bguide=1', 'top' );
}, 20 );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'ymkrf_bguide';
	return $vars;
} );

add_filter( 'template_include', function ( $tpl ) {
	if ( ! get_query_var( 'ymkrf_bguide' ) ) return $tpl;
	$found = locate_template( 'ymkrf-boilerguide.php' );
	return $found ? $found : $tpl;
} );

/* このURLは「見つかりません」でもトップページでもありません。
   ここをはっきりさせないと、下層ページ用のCSSが読み込まれません。 */
add_action( 'wp', function () {
	if ( ! get_query_var( 'ymkrf_bguide' ) ) return;
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
	if ( get_query_var( 'ymkrf_bguide' ) ) {
		$parts['title'] = '給湯器・エコキュートの選び方｜種類・号数・替えどき・エラーコード';
		unset( $parts['tagline'] );
	}
	return $parts;
} );

if ( ! function_exists( 'ymkrf_is_bguide' ) ) :
function ymkrf_is_bguide() {
	return (bool) get_query_var( 'ymkrf_bguide' );
}
endif;


/* ============================================================
   1-4. エラーコード一覧（/troubleshooting/）のURL

        1枚のテンプレートで、3つの階層をまかないます。
          /troubleshooting/                       … 入口
          /troubleshooting/<種類>-error/           … メーカーをえらぶ
          /troubleshooting/<種類>-error/<メーカー>/ … エラーコードの表

        並べる順が大事です。長いものから先に書かないと、
        短いほうの決まりが先に当たってしまいます。
   ============================================================ */
add_action( 'init', function () {
	add_rewrite_rule( '^troubleshooting/([a-z0-9-]+)-error/([a-z0-9-]+)/?$',
		'index.php?ymkrf_ts=1&ymkrf_ts_cat=$matches[1]&ymkrf_ts_maker=$matches[2]', 'top' );
	add_rewrite_rule( '^troubleshooting/([a-z0-9-]+)-error/?$',
		'index.php?ymkrf_ts=1&ymkrf_ts_cat=$matches[1]', 'top' );
	add_rewrite_rule( '^troubleshooting/?$', 'index.php?ymkrf_ts=1', 'top' );
}, 20 );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'ymkrf_ts';
	$vars[] = 'ymkrf_ts_cat';
	$vars[] = 'ymkrf_ts_maker';
	return $vars;
} );

add_filter( 'template_include', function ( $tpl ) {
	if ( ! get_query_var( 'ymkrf_ts' ) ) return $tpl;
	$found = locate_template( 'ymkrf-troubleshooting.php' );
	return $found ? $found : $tpl;
} );

add_action( 'wp', function () {
	if ( ! get_query_var( 'ymkrf_ts' ) ) return;
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
	if ( ! get_query_var( 'ymkrf_ts' ) ) return $parts;

	$names = array(
		'ecocute'  => 'エコキュート',
		'gas'      => 'ガス給湯器',
		'oil'      => '石油給湯器',
		'electric' => '電気温水器',
	);
	$makers = array(
		'mitsubishi'     => '三菱電機',
		'panasonic'      => 'パナソニック',
		'daikin'         => 'ダイキン',
		'hitachi'        => '日立',
		'noritz'         => 'ノーリツ',
		'rinnai'         => 'リンナイ',
		'paloma'         => 'パロマ',
		'chofu'          => '長府製作所',
		'corona'         => 'コロナ',
		'takarastandard' => 'タカラスタンダード',
	);

	$c = (string) get_query_var( 'ymkrf_ts_cat' );
	$m = (string) get_query_var( 'ymkrf_ts_maker' );

	if ( $m !== '' && isset( $names[ $c ], $makers[ $m ] ) ) {
		$parts['title'] = $makers[ $m ] . 'のエラーコード一覧｜' . $names[ $c ] . '｜原因と対処方法';
	} elseif ( isset( $names[ $c ] ) ) {
		$parts['title'] = $names[ $c ] . 'のエラーコード一覧｜メーカー別に原因と対処方法';
	} else {
		$parts['title'] = '給湯器エラーコード一覧｜故障かな？と思ったら';
	}
	unset( $parts['tagline'] );
	return $parts;
} );

if ( ! function_exists( 'ymkrf_is_ts' ) ) :
function ymkrf_is_ts() {
	return (bool) get_query_var( 'ymkrf_ts' );
}
endif;

/* ============================================================
   1-8. reCAPTCHA のご案内文

        reCAPTCHA v3 は、ふつう画面の右下に灰色のバッジを出します。
        ところがこのサイトは、右下に「ページの先頭にもどる」ボタンと、
        スマホでは下に固定したボタンの帯があるため、バッジが
        そのうしろに隠れてしまいます。

        Google は「バッジを消すかわりに、下の文をフォームの近くに
        出すこと」を認めています。そちらのやり方にしています。
        （バッジを消すCSSは assets/css/page.css にあります）
   ============================================================ */
/* Contact Form 7 は、フォームの中に <p> と <br> を自動で入れます。
   こちらは <div class="p-form__row"> できちんと組んでいるので、
   自動で入る分は余白が広がるだけで、じゃまになります。止めます。 */
add_filter( 'wpcf7_autop_or_not', '__return_false' );


if ( ! function_exists( 'ymkrf_recaptcha_note' ) ) :
function ymkrf_recaptcha_note() {

	/* reCAPTCHA を使っていないときは、何も出しません */
	if ( ! class_exists( 'WPCF7_RECAPTCHA' ) ) return;
	$svc = WPCF7_RECAPTCHA::get_instance();
	if ( ! $svc || ! $svc->is_active() ) return;
	?>
	<p class="p-form__recaptcha">
	  このサイトは reCAPTCHA によって保護されており、Google の<a
	    href="https://policies.google.com/privacy" target="_blank" rel="noopener">プライバシーポリシー</a>と<a
	    href="https://policies.google.com/terms" target="_blank" rel="noopener">利用規約</a>が適用されます。
	</p>
	<?php
}
endif;


/* ============================================================
   1-6. お見積り・お問い合わせ（/inquiry/）のURL

        ★ /inquiry/webrsv/（来店予約）は別のページです。
          ここでは「/inquiry/ ちょうど」だけを拾うようにしています。
          `?$` を外すと来店予約まで飲み込んでしまうので、ご注意ください。
   ============================================================ */
add_action( 'init', function () {
	add_rewrite_rule( '^inquiry/?$', 'index.php?ymkrf_inquiry=1', 'top' );
}, 20 );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'ymkrf_inquiry';
	return $vars;
} );

add_filter( 'template_include', function ( $tpl ) {
	if ( ! get_query_var( 'ymkrf_inquiry' ) ) return $tpl;
	$found = locate_template( 'ymkrf-inquiry.php' );
	return $found ? $found : $tpl;
} );

add_action( 'wp', function () {
	if ( ! get_query_var( 'ymkrf_inquiry' ) ) return;
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
	if ( get_query_var( 'ymkrf_inquiry' ) ) {
		$parts['title'] = 'お見積り・お問い合わせ｜現地調査もお見積りも無料';
		unset( $parts['tagline'] );
	}
	return $parts;
} );

if ( ! function_exists( 'ymkrf_is_inquiry' ) ) :
function ymkrf_is_inquiry() {
	return (bool) get_query_var( 'ymkrf_inquiry' );
}
endif;


/* ============================================================
   1-7. ネット来店予約（/inquiry/webrsv/）のURL
        作りは上の /inquiry/ と同じです。
   ============================================================ */
add_action( 'init', function () {
	add_rewrite_rule( '^inquiry/webrsv/?$', 'index.php?ymkrf_webrsv=1', 'top' );
}, 20 );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'ymkrf_webrsv';
	return $vars;
} );

add_filter( 'template_include', function ( $tpl ) {
	if ( ! get_query_var( 'ymkrf_webrsv' ) ) return $tpl;
	$found = locate_template( 'ymkrf-webrsv.php' );
	return $found ? $found : $tpl;
} );

add_action( 'wp', function () {
	if ( ! get_query_var( 'ymkrf_webrsv' ) ) return;
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
	if ( get_query_var( 'ymkrf_webrsv' ) ) {
		$parts['title'] = 'ネット来店予約｜初回はヤマキシお買物券500円分プレゼント';
		unset( $parts['tagline'] );
	}
	return $parts;
} );

if ( ! function_exists( 'ymkrf_is_webrsv' ) ) :
function ymkrf_is_webrsv() {
	return (bool) get_query_var( 'ymkrf_webrsv' );
}
endif;


/* ============================================================
   1-5. 外壁・屋根（/products/outer-wall/）の題名

        このページは分類ページなので、そのままだと題名が
        「外壁・屋根」だけになってしまいます。
        検索結果に出たときに何のページか分かるよう、書き足します。

        ページの中身は taxonomy-ymkrf_product_cat-outer-wall.php です。
        （WordPressの決まりで、そのファイル名にしておくだけで使われます）
   ============================================================ */
add_filter( 'document_title_parts', function ( $parts ) {
	if ( is_tax( 'ymkrf_product_cat', 'outer-wall' ) ) {
		/* うしろに「– リフォームヤマキシ」が自動で付きます。
		   ですので、ここに社名を入れると二重になります。 */
		$parts['title'] = '外壁塗装・屋根塗装の価格と劣化のサイン｜石川・福井';
		unset( $parts['tagline'] );
	}
	return $parts;
} );

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

	/* ★/area/ は「対応エリアの一覧」になりました（2026/09/23）。
	     むかしは店舗ページに飛ばしていましたが、市町ごとのページを
	     作ったので、その入口として使います。転送はやめました。 */

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

/**
 * お店1つぶんのページのURL。
 *
 * （2026/09/23 ユーザー「担当店舗は田鶴浜店の専用ページへ。
 *   ちなみに、店舗毎に1ページ作る予定です」）
 *
 * いまは1枚もの（/shops/）の中の位置へ送っています。
 * 店舗ごとのページを作ったら、★の1行を
 *   return home_url( '/shops/' . $slug . '/' );
 * に変えるだけで、サイト中のリンクがいっせいに切りかわります。
 */
if ( ! function_exists( 'ymkrf_shop_url' ) ) :
function ymkrf_shop_url( $slug ) {
	$slug = sanitize_title( (string) $slug );
	if ( $slug === '' ) return home_url( '/shops/' );

	/* ★店舗ごとのページができたら、ここを差しかえます */
	return home_url( '/shops/#' . $slug );
}
endif;

/* ============================================================
   1-9. イベント・チラシのページ（/flyer/）のURL

        お店をえらんだ状態で開くときは /flyer/?shop=komathu のように
        うしろに付けます（店舗・対応エリアのページからのリンクがこの形です）。
        ?shop= は WordPress の shop とはぶつからないよう、
        ページの中で自分で読んでいます（ymkrf-flyer.php）。
   ============================================================ */
add_action( 'init', function () {
	add_rewrite_rule( '^flyer/?$', 'index.php?ymkrf_flyer_page=1', 'top' );
}, 20 );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'ymkrf_flyer_page';
	return $vars;
} );

add_filter( 'template_include', function ( $tpl ) {
	if ( ! get_query_var( 'ymkrf_flyer_page' ) ) return $tpl;
	$found = locate_template( 'ymkrf-flyer.php' );
	return $found ? $found : $tpl;
} );

add_action( 'wp', function () {
	if ( ! get_query_var( 'ymkrf_flyer_page' ) ) return;
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
	if ( get_query_var( 'ymkrf_flyer_page' ) ) {
		$parts['title'] = 'イベント・チラシ｜リフォームヤマキシ（石川県・福井県）';
		unset( $parts['tagline'] );
	}
	return $parts;
} );

if ( ! function_exists( 'ymkrf_is_flyer' ) ) :
function ymkrf_is_flyer() {
	return (bool) get_query_var( 'ymkrf_flyer_page' );
}
endif;


/* ============================================================
   1-10. プライバシーポリシーのページ（/privacy/）のURL
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

/* 一覧の検索で、案件番号でも見つかるようにします。

   WordPressの検索は、題名・本文・抜粋しか見ません。
   施工事例とお客様の声は、一覧の1列目に案件番号を出しているのに、
   その番号で検索しても出てこない、ということが起きていました。
   （2026/09/08 ユーザー「案件番号を検索しても出てきません」） */
add_filter( 'posts_search', function ( $search, $q ) {

	if ( ! is_admin() || ! $q->is_main_query() ) return $search;
	if ( ! $q->is_search() || $search === '' ) return $search;

	$pt = $q->get( 'post_type' );
	if ( ! in_array( $pt, array( 'ymkrf_works', 'ymkrf_voice' ), true ) ) return $search;

	$term = trim( (string) $q->get( 's' ) );
	if ( $term === '' ) return $search;

	global $wpdb;
	$like = '%' . $wpdb->esc_like( $term ) . '%';

	/* 案件番号のほか、お客様の頭文字・商品名でも探せるようにします */
	$add = $wpdb->prepare(
		" OR ( {$wpdb->posts}.ID IN (
			SELECT post_id FROM {$wpdb->postmeta}
			 WHERE meta_key IN ( '_ymkrf_case_no', '_ymkrf_case_no_more',
			                     '_ymkrf_product_text', '_ymkrf_work_items' )
			   AND meta_value LIKE %s ) ) ", $like );

	/* いちばん外側のカッコの中に足します */
	return preg_replace( '/\)\s*$/', $add . ')', $search, 1 );
}, 10, 2 );


/* SEO SIMPLE PACK の「SEO設定」の箱は出しません。
   説明文を書く欄が「抜粋」と2つになって、どちらに書けばよいか
   分からなくなるためです。書くところは「抜粋」ひとつにします。
   （抜粋が、そのまま検索結果の説明文になります）
   （2026/09/08 ユーザー「SEO設定が出てきましたね。でも抜粋があるの？」）

   プラグインの箱は add_meta_boxes のときに作られるので、
   そのあと（優先度999）で外します。 */
add_action( 'add_meta_boxes', function () {
	$s = get_current_screen();
	if ( ! $s ) return;
	foreach ( array( 'side', 'normal', 'advanced' ) as $ctx ) {
		remove_meta_box( 'ssp_metabox', $s->post_type, $ctx );
	}
}, 999 );


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
		/* 画面の言葉を「カテゴリー」から「部位」に
		   （2026/09/23 ユーザー指示「施工事例の部位も同じような仕組みにして」） */
		'labels'       => array(
			'name'              => '部位',
			'singular_name'     => '部位',
			'menu_name'         => '部位',
			'all_items'         => 'すべての部位',
			'add_new_item'      => '部位を追加',
			'new_item_name'     => '新しい部位の名前',
			'edit_item'         => '部位を編集',
			'view_item'         => '部位を表示',
			'update_item'       => '部位を更新',
			'search_items'      => '部位を検索',
			'parent_item'       => '親の部位',
			'parent_item_colon' => '親の部位：',
			'not_found'         => '部位が見つかりません',
			'back_to_items'     => '← 部位の一覧にもどる',
			'name_field_description' => 'ページに出る部位の名前です（例：キッチン）。',
			'slug_field_description' => 'URLに使う英字です（例：kitchen）。部位ごとの一覧 /works/kitchen/ になります。',
		),
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
		/* 画面の言葉を「カテゴリー」から「エリア」に変えます
		   （2026/09/23 ユーザー指示「カテゴリーを追加ではなく、エリアを追加」） */
		'labels'       => array(
			'name'              => 'エリア',
			'singular_name'     => 'エリア',
			'menu_name'         => 'エリア',
			'all_items'         => 'すべてのエリア',
			'add_new_item'      => 'エリアを追加',
			'new_item_name'     => '新しいエリアの名前',
			'edit_item'         => 'エリアを編集',
			'view_item'         => 'エリアを表示',
			'update_item'       => 'エリアを更新',
			'search_items'      => 'エリアを検索',
			'parent_item'       => '親エリア',
			'parent_item_colon' => '親エリア：',
			'not_found'         => 'エリアが見つかりません',
			'back_to_items'     => '← エリアの一覧にもどる',
			/* 2026/09/23 ユーザー指示「県名や町名・字は入れません。削除」 */
			'name_field_description' => 'ページに出る市・町の名前です（例：中能登町）。',
			'slug_field_description' => 'URLに使う英字です（例：nakanoto）。地域ごとのページ /area/nakanoto/ になります。',
			'parent_field_description' => '石川県か福井県をえらんでください。',
		),
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
		/* 抜粋の欄は出しません。お客様の声の説明文は、テーマのほうで
		   お客様のことば・満足度から組み立てているので、書く必要が
		   ないためです。（2026/09/08 ユーザー「うん、不要かな」）
		   ※ 保存のときに post_excerpt へ自動で入れる処理は残してあります。 */
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

/* ============================================================
   SNSのアイコン（LINE・Instagram・Facebook・X）

   使い方： <?php ymkrf_sns_icons(); ?>
            <?php ymkrf_sns_icons( 'dark' ); ?>  ← 背景が濃いところ

   ★リンク先を変えるときは、下の $sns の url だけ書きかえてください。
   ============================================================ */
if ( ! function_exists( 'ymkrf_sns_icons' ) ) :
function ymkrf_sns_icons( $mod = '', $lead = 'SNSでも発信しています' ) {

	$sns = array(
		'line'  => array(
			'label' => 'LINEで相談する',
			'url'   => 'https://line.me/R/ti/p/@233okcdx',
		),
		'insta' => array(
			'label' => 'Instagram',
			'url'   => 'https://www.instagram.com/yamakishi_official_account/',
		),
		'fb'    => array(
			'label' => 'Facebook',
			'url'   => 'https://www.facebook.com/yamakishi.reform/',
		),
		'x'     => array(
			'label' => 'X（旧Twitter）',
			'url'   => 'https://x.com/yamakishi_9_',
		),
	);

	$icon = array(

		/* LINE は公式の緑の四角のマークです */
		'line' => '<svg viewBox="0 0 24 24" aria-hidden="true">'
		        . '<rect x="0" y="0" width="24" height="24" rx="5.4" fill="#06c755"/>'
		        /* 白いふきだし */
		        . '<path fill="#fff" d="M12 4.1c-4.6 0-8.3 3-8.3 6.7 0 3.3 2.9 6.1 6.9 6.6.3.1.6.2.7.4.1.2.1.5 0 .7'
		        . 'l-.1.7c0 .2-.2.8.7.4.9-.4 4.8-2.8 6.5-4.8 1.2-1.3 1.8-2.6 1.8-4C20.3 7.1 16.6 4.1 12 4.1z"/>'
		        /* ふきだしの中の LINE の文字 */
		        . '<g fill="#06c755">'
		        . '<path d="M6.6 8.7h.95v3.15h1.7v.9H6.6z"/>'
		        . '<path d="M9.85 8.7h.95v4.05h-.95z"/>'
		        . '<path d="M11.5 8.7h.9l1.6 2.2V8.7h.95v4.05h-.9l-1.6-2.2v2.2h-.95z"/>'
		        . '<path d="M15.75 8.7h2.6v.88h-1.66v.72h1.6v.86h-1.6v.73h1.66v.86h-2.6z"/>'
		        . '</g></svg>',

		/* Instagram は公式のグラデーションのマークです */
		'insta' => '<svg viewBox="0 0 24 24" aria-hidden="true">'
		         . '<defs><radialGradient id="ymkrfIg' . esc_attr( $mod ) . '" cx="0.3" cy="1.05" r="1.25">'
		         . '<stop offset="0%" stop-color="#fdf497"/><stop offset="12%" stop-color="#fdf497"/>'
		         . '<stop offset="34%" stop-color="#fd5949"/><stop offset="58%" stop-color="#d6249f"/>'
		         . '<stop offset="90%" stop-color="#285aeb"/></radialGradient></defs>'
		         . '<rect x="1.4" y="1.4" width="21.2" height="21.2" rx="6.2" fill="url(#ymkrfIg'
		         . esc_attr( $mod ) . ')"/>'
		         . '<rect x="5.3" y="5.3" width="13.4" height="13.4" rx="4.1" fill="none" stroke="#fff" stroke-width="1.75"/>'
		         . '<circle cx="12" cy="12" r="3.5" fill="none" stroke="#fff" stroke-width="1.75"/>'
		         . '<circle cx="16.5" cy="7.5" r="1.05" fill="#fff"/></svg>',

		'fb' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">'
		      . '<path d="M22 12a10 10 0 1 0-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.8 3.7-3.8 1.1 0 2.2.2 2.2.2v2.4'
		      . 'h-1.2c-1.2 0-1.6.8-1.6 1.6V12h2.7l-.4 2.9h-2.3v7A10 10 0 0 0 22 12z"/></svg>',

		'x' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">'
		     . '<path d="M17.5 3h3.1l-6.8 7.7L21.8 21h-6.2l-4.9-6.4L5.1 21H2l7.3-8.3L2.4 3h6.4l4.4 5.8L17.5 3z'
		     . 'm-1.1 16.1h1.7L7.7 4.8H5.9l10.5 14.3z"/></svg>',
	);

	$class = 'p-sns' . ( $mod === 'dark' ? ' p-sns--dark' : '' );
	?>
	<div class="<?php echo esc_attr( $class ); ?>">
	  <?php if ( $lead !== '' ) : ?>
	    <p class="p-sns__lead"><?php echo esc_html( $lead ); ?></p>
	  <?php endif; ?>
	  <ul class="p-sns__list">
	    <?php foreach ( $sns as $key => $s ) : ?>
	      <li>
	        <a class="p-sns__btn p-sns__btn--<?php echo esc_attr( $key ); ?>"
	           href="<?php echo esc_url( $s['url'] ); ?>"
	           aria-label="<?php echo esc_attr( $s['label'] ); ?>"
	           title="<?php echo esc_attr( $s['label'] ); ?>"
	           target="_blank" rel="noopener"
	           data-cta="sns_<?php echo esc_attr( $key ); ?>">
	          <?php echo $icon[ $key ]; ?>
	          <span class="u-vh"><?php echo esc_html( $s['label'] ); ?></span>
	        </a>
	      </li>
	    <?php endforeach; ?>
	  </ul>
	</div>
	<?php
}
endif;

/* ============================================================
   グループのサイト（外壁・太陽光・不動産・コーポレート）

   使い方： <?php ymkrf_group_sites(); ?>

   ★ロゴの画像は assets/img/group/ に置いてください。
     ファイル名は下の 'img' のとおりです。
     画像が無いときは、これまでどおり文字のリンクで出ます。
   ============================================================ */
if ( ! function_exists( 'ymkrf_group_sites' ) ) :
function ymkrf_group_sites( $lead = 'ヤマキシのほかのサイト', $use_logo = true ) {

	$sites = array(
		array(
			'name' => '外壁・屋根',
			'url'  => 'https://yamakishi-paint.jp/',
			'img'  => 'paint.png',
		),
		array(
			'name' => '太陽光・蓄電池',
			'url'  => 'https://www.yamakishi-solar.biz/',
			'img'  => 'solar.png',
			'wide' => true,   /* 横に長いロゴなので、はばいっぱいに出します */
		),
		array(
			'name' => 'トレーラーハウス',
			'url'  => 'https://www.yamakishi.co.jp/trailerhouse/',
			'img'  => 'trailer.png',
		),
		array(
			'name' => '不動産',
			'url'  => 'https://www.yamakishi-f.com/',
			'img'  => 'estate.png',
		),
		array(
			'name' => 'コーポレート',
			'url'  => 'https://www.yamakishi.co.jp/',
			'img'  => 'corporate.png',
		),
		array(
			'name' => 'リクルート',
			'url'  => 'https://yamakishi.co.jp/recruit/',
			'img'  => 'recruit.png',
		),
	);

	$dir = get_stylesheet_directory() . '/assets/img/group/';
	$uri = get_stylesheet_directory_uri() . '/assets/img/group/';
	?>
	<div class="p-gsites">
	  <?php if ( $lead !== '' ) : ?>
	    <p class="p-gsites__lead"><?php echo esc_html( $lead ); ?></p>
	  <?php endif; ?>
	  <ul class="p-gsites__list">
	    <?php foreach ( $sites as $s ) : ?>
	      <?php $has = $use_logo && file_exists( $dir . $s['img'] ); ?>
	      <?php
	      $cls = $has ? 'p-gsites__item--logo' : 'p-gsites__item--text';
	      if ( $has && ! empty( $s['wide'] ) ) $cls .= ' p-gsites__item--logo--wide';
	      ?>
	      <li class="<?php echo esc_attr( $cls ); ?>">
	        <a href="<?php echo esc_url( $s['url'] ); ?>" target="_blank" rel="noopener"
	           title="<?php echo esc_attr( $s['name'] ); ?>">
	          <?php if ( $has ) : ?>
	            <img src="<?php echo esc_url( $uri . $s['img'] ); ?>"
	                 alt="<?php echo esc_attr( $s['name'] ); ?>" loading="lazy" decoding="async">
	          <?php else : ?>
	            <?php echo esc_html( $s['name'] ); ?>
	          <?php endif; ?>
	        </a>
	      </li>
	    <?php endforeach; ?>
	  </ul>
	</div>
	<?php
}
endif;
