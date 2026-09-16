<?php
/**
 * リフォームヤマキシ テーマ
 *
 * 実際の中身は inc/ の中の2ファイルです。
 *   inc/functions-snippet.php … CSS/JSの読み込み、施工事例・お客様の声
 *   inc/functions-product.php … 商品（キッチン・お風呂ほか）
 *   inc/functions-column.php  … コラム（お役立ち情報）
 *   inc/functions-news.php    … お知らせ
 *   inc/functions-flyer.php   … イベント・チラシ
 *   inc/functions-voice.php   … お客様の声（アンケートの自動読み取り）
 *   inc/functions-works.php   … 施工事例（Before/After・権限）
 *   inc/functions-works-import.php … いまのサイトから施工事例を取り込む
 *   inc/functions-media.php   … メディアを使い道べつに分けて見る
 *   inc/functions-voice-mail.php … アンケート登録を担当店へメールでお知らせ
 *   inc/functions-voice-status.php … 公開／下書き／非公開をひとつにまとめる
 */
if ( ! defined( 'ABSPATH' ) ) exit;

require_once get_stylesheet_directory() . '/inc/functions-snippet.php';
require_once get_stylesheet_directory() . '/inc/functions-product.php';
require_once get_stylesheet_directory() . '/inc/functions-column.php';
/* ↓ 旧ブログ（コラム）の取り込み。終わったらこの行とファイルを消してください */
require_once get_stylesheet_directory() . '/inc/functions-column-import.php';
require_once get_stylesheet_directory() . '/inc/functions-news.php';
require_once get_stylesheet_directory() . '/inc/functions-voice.php';
/* ↓ 本番サイトからのお客様の声の取り込み。
      制作中はいまのサイトにも登録されるので、
      新しいサイトを公開するまで残します（2026/09/08 ユーザー指示）。
      公開したら、この行と inc/functions-voice-import.php を消してください */
require_once get_stylesheet_directory() . '/inc/functions-voice-import.php';
require_once get_stylesheet_directory() . '/inc/functions-works.php';
require_once get_stylesheet_directory() . '/inc/functions-works-import.php';
/* ↓ 本番サイトからの施工事例の取り込み。
      制作中はいまのサイトにも登録されるので、
      新しいサイトを公開するまで残します（2026/09/08 ユーザー指示）。
      公開したら、この行と inc/functions-works-import2.php を消してください */
require_once get_stylesheet_directory() . '/inc/functions-works-import2.php';
require_once get_stylesheet_directory() . '/inc/functions-shops.php';
require_once get_stylesheet_directory() . '/inc/functions-staff.php';
require_once get_stylesheet_directory() . '/inc/functions-flyer.php';
require_once get_stylesheet_directory() . '/inc/functions-media.php';
require_once get_stylesheet_directory() . '/inc/functions-voice-status.php';
require_once get_stylesheet_directory() . '/inc/functions-voice-check.php';
require_once get_stylesheet_directory() . '/inc/functions-voice-mail.php';
/* アンケート画像の入れ直しは、終わりました（2026/09/16）。
   ダッシュボードから「アンケートの入れ直し」を消すため、読み込みをやめています。
   inc/functions-voice-swap.php は、もう使いません。消してかまいません。
   もう一度使いたくなったら、下の行の先頭の // を外してください。
// require_once get_stylesheet_directory() . '/inc/functions-voice-swap.php';
*/
/* ↓ 写真の名前をそろえる下見の画面。終わったらこの行とファイルを消してください */
require_once get_stylesheet_directory() . '/inc/functions-media-rename.php';

/* アイキャッチ画像を使えるようにする */
add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
} );
