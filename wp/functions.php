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
 */
if ( ! defined( 'ABSPATH' ) ) exit;

require_once get_stylesheet_directory() . '/inc/functions-snippet.php';
require_once get_stylesheet_directory() . '/inc/functions-product.php';
require_once get_stylesheet_directory() . '/inc/functions-column.php';
require_once get_stylesheet_directory() . '/inc/functions-news.php';
require_once get_stylesheet_directory() . '/inc/functions-voice.php';
require_once get_stylesheet_directory() . '/inc/functions-works.php';
require_once get_stylesheet_directory() . '/inc/functions-works-import.php';
require_once get_stylesheet_directory() . '/inc/functions-shops.php';
require_once get_stylesheet_directory() . '/inc/functions-staff.php';
require_once get_stylesheet_directory() . '/inc/functions-flyer.php';

/* アイキャッチ画像を使えるようにする */
add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
} );
