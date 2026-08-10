<?php
/**
 * リフォームヤマキシ テーマ
 *
 * 実際の中身は inc/ の中の2ファイルです。
 *   inc/functions-snippet.php … CSS/JSの読み込み、施工事例・お客様の声
 *   inc/functions-product.php … 商品（キッチン・お風呂ほか）
 *   inc/functions-column.php  … コラム（お役立ち情報）
 */
if ( ! defined( 'ABSPATH' ) ) exit;

require_once get_stylesheet_directory() . '/inc/functions-snippet.php';
require_once get_stylesheet_directory() . '/inc/functions-product.php';
require_once get_stylesheet_directory() . '/inc/functions-column.php';

/* アイキャッチ画像を使えるようにする */
add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
} );
