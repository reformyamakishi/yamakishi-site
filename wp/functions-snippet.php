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

if ( ! defined( 'YMKRF_VER' ) ) define( 'YMKRF_VER', '1.1.1' );   // ファイル更新時はここを上げるとキャッシュが切れます

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
	           || ( function_exists( 'ymkrf_is_about' ) && ymkrf_is_about() );
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

	/* お客様の声のページ */
	if ( is_singular( 'ymkrf_voice' ) || is_post_type_archive( 'ymkrf_voice' ) ) {
		wp_enqueue_style( 'ymkrf-voice', $dir . '/assets/css/voice.css', array( 'ymkrf-page' ), YMKRF_VER );
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
		$parts['title'] = 'ヤマキシのこだわり・特徴｜安い・早い・安心のリフォーム';
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

	$old = array( 'concept', 'system', 'lp/seikatsu-kaizen' );
	if ( in_array( $path, $old, true ) ) {
		wp_redirect( home_url( '/about/' ), 301 );
		exit;
	}
} );

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

	/* --- 施工事例 --- */
	register_post_type( 'ymkrf_works', array(
		'label'        => '施工事例',
		'public'       => true,
		'has_archive'  => true,
		'menu_icon'    => 'dashicons-hammer',
		'menu_position'=> 5,
		'rewrite'      => array( 'slug' => 'works', 'with_front' => false ),
		'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		'show_in_rest' => true,
	) );

	/* 部位（キッチン／お風呂／トイレ …） */
	register_taxonomy( 'ymkrf_works_cat', 'ymkrf_works', array(
		'label'        => '部位',
		'hierarchical' => true,
		'rewrite'      => array( 'slug' => 'works', 'with_front' => false ),
		'show_in_rest' => true,
	) );

	/* エリア（金沢市／小松市 …）＝ 地域検索の受け皿になります */
	register_taxonomy( 'ymkrf_works_area', 'ymkrf_works', array(
		'label'        => 'エリア',
		'hierarchical' => true,
		'rewrite'      => array( 'slug' => 'works-area', 'with_front' => false ),
		'show_in_rest' => true,
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
