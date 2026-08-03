<?php
/**
 * functions.php に追記するスニペット ─ リフォームヤマキシ
 *
 * 1. CSS / JS の読み込み（ページごとに出し分け）
 * 2. カスタム投稿タイプ（施工事例 works / お客様の声 voice）
 * 3. カスタムフィールド（工事費・工期・Before画像 など）
 * 4. 表示速度まわりの調整
 *
 * ※ 必ず子テーマで作業してください（親テーマ直編集は更新で消えます）
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'YMK_VER', '1.0.0' );   // ファイル更新時はここを上げるとキャッシュが切れます

/* ============================================================
   1. CSS / JS の読み込み
      - 全ページ： common.css / common.js
      - トップ　： + home.css / home.js
      - 下層　　： + page.css
   ============================================================ */
add_action( 'wp_enqueue_scripts', function () {

	$dir = get_stylesheet_directory_uri();

	wp_enqueue_style( 'ymk-common', $dir . '/assets/css/common.css', array(), YMK_VER );
	wp_enqueue_script( 'ymk-common', $dir . '/assets/js/common.js', array(), YMK_VER, true );

	if ( is_front_page() ) {
		wp_enqueue_style( 'ymk-home', $dir . '/assets/css/home.css', array( 'ymk-common' ), YMK_VER );
		wp_enqueue_script( 'ymk-home', $dir . '/assets/js/home.js', array( 'ymk-common' ), YMK_VER, true );
	} else {
		wp_enqueue_style( 'ymk-page', $dir . '/assets/css/page.css', array( 'ymk-common' ), YMK_VER );
	}
} );

/* defer 属性を付けて描画をブロックしないようにする */
add_filter( 'script_loader_tag', function ( $tag, $handle ) {
	if ( in_array( $handle, array( 'ymk-common', 'ymk-home' ), true ) ) {
		return str_replace( ' src', ' defer src', $tag );
	}
	return $tag;
}, 10, 2 );


/* ============================================================
   2. カスタム投稿タイプ
   ============================================================ */
add_action( 'init', function () {

	/* --- 施工事例 --- */
	register_post_type( 'works', array(
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
	register_taxonomy( 'works_cat', 'works', array(
		'label'        => '部位',
		'hierarchical' => true,
		'rewrite'      => array( 'slug' => 'works', 'with_front' => false ),
		'show_in_rest' => true,
	) );

	/* エリア（金沢市／小松市 …）＝ 地域検索の受け皿になります */
	register_taxonomy( 'works_area', 'works', array(
		'label'        => 'エリア',
		'hierarchical' => true,
		'rewrite'      => array( 'slug' => 'works-area', 'with_front' => false ),
		'show_in_rest' => true,
	) );

	/* --- お客様の声 --- */
	register_post_type( 'voice', array(
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

	add_meta_box( 'ymk_works', '施工データ', function ( $post ) {
		wp_nonce_field( 'ymk_meta_save', 'ymk_meta_nonce' );
		ymk_meta_fields( $post->ID, array(
			'price'      => array( '工事費（例：128万円）', 'text' ),
			'period'     => array( '工期（例：3日）', 'text' ),
			'before_img' => array( 'Before画像の添付ファイルID', 'number' ),
		) );
	}, 'works', 'side' );

	add_meta_box( 'ymk_voice', 'お客様情報', function ( $post ) {
		wp_nonce_field( 'ymk_meta_save', 'ymk_meta_nonce' );
		ymk_meta_fields( $post->ID, array(
			'customer' => array( 'お客様（例：金沢市／K様（40代）・キッチンリフォーム）', 'text' ),
			'star'     => array( '評価（1〜5）', 'number' ),
		) );
	}, 'voice', 'side' );
} );

function ymk_meta_fields( $post_id, $fields ) {
	foreach ( $fields as $key => $f ) {
		printf(
			'<p><label for="%3$s" style="display:block;font-weight:600">%1$s</label>
			 <input type="%2$s" id="%3$s" name="%3$s" value="%4$s" style="width:100%%"></p>',
			esc_html( $f[0] ), esc_attr( $f[1] ), esc_attr( $key ),
			esc_attr( get_post_meta( $post_id, $key, true ) )
		);
	}
}

add_action( 'save_post', function ( $post_id ) {
	if ( ! isset( $_POST['ymk_meta_nonce'] ) ||
	     ! wp_verify_nonce( sanitize_key( $_POST['ymk_meta_nonce'] ), 'ymk_meta_save' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	foreach ( array( 'price', 'period', 'before_img', 'customer', 'star' ) as $key ) {
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
