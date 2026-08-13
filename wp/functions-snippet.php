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

if ( ! defined( 'YMKRF_VER' ) ) define( 'YMKRF_VER', '1.0.3' );   // ファイル更新時はここを上げるとキャッシュが切れます

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

	if ( is_front_page() ) {
		wp_enqueue_style( 'ymkrf-home', $dir . '/assets/css/home.css', array( 'ymkrf-common' ), YMKRF_VER );
		wp_enqueue_script( 'ymkrf-home', $dir . '/assets/js/home.js', array( 'ymkrf-common' ), YMKRF_VER, true );
	} else {
		wp_enqueue_style( 'ymkrf-page', $dir . '/assets/css/page.css', array( 'ymkrf-common' ), YMKRF_VER );
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

	/* --- お客様の声 --- */
	register_post_type( 'ymkrf_voice', array(
		'label'        => 'お客様の声',
		'public'       => true,
		'has_archive'  => true,
		'menu_icon'    => 'dashicons-format-quote',
		'menu_position'=> 6,
		'rewrite'      => array( 'slug' => 'voice', 'with_front' => false ),
		'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		'show_in_rest' => true,
	) );
} );


/* ============================================================
   3. カスタムフィールド（ACF を使わない場合の簡易メタボックス）
   ============================================================ */
add_action( 'add_meta_boxes', function () {

	add_meta_box( 'ymkrf_works_box', '施工データ', function ( $post ) {
		wp_nonce_field( 'ymkrf_meta_save', 'ymkrf_meta_nonce' );
		ymkrf_meta_fields( $post->ID, array(
			'_ymkrf_price'  => array( '工事費（例：128万円）', 'text' ),
			'_ymkrf_period' => array( '工期（例：3日）', 'text' ),
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

	foreach ( array( '_ymkrf_price', '_ymkrf_period', '_ymkrf_before_img', '_ymkrf_customer', '_ymkrf_star' ) as $key ) {
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
