<?php
/**
 * ダッシュボードの「抜粋」を「ディスクリプション」と呼ぶ（2026/11/03）
 *
 * ユーザー「抜粋ってdiscriptionの事ですよね？そうであれば今後、ダッシュボードすべて抜粋を
 *           discriptionって表現にして。そっちの方がピンとくる」
 *
 * このサイトでは「抜粋」に書いた文が、そのまま検索結果の説明文（meta description）になります
 * （SEO PACK の説明文の欄は出さず、抜粋ひとつにしている：functions-snippet.php の「書くところは抜粋ひとつ」の説明）。
 * なので、ダッシュボードに出る「抜粋」という言葉を、ぜんぶ「ディスクリプション」に置きかえます。
 *   ・ふつうの編集画面・一覧（PHPで出る文字）… gettext
 *   ・ブロックエディタ（右側の「抜粋」の欄など。JavaScriptで出る文字）… wp.hooks の i18n.gettext
 * ホームページ側（フロント）には何もしません。保存の場所（post_excerpt）も変えません。
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function ymkrf_excerpt_word( $s ) {
	return ( is_string( $s ) && strpos( $s, '抜粋' ) !== false ) ? str_replace( '抜粋', 'ディスクリプション', $s ) : $s;
}

/* 欄の下の説明（WordPressのもとの文）を、このサイトでの使われかたに */
add_filter( 'gettext', function ( $tr, $text ) {
	if ( is_admin() && strpos( $text, 'Excerpts are optional hand-crafted summaries of your content' ) === 0 ) {
		return 'Googleなどの検索結果に出る説明文です（青い題名の下に出る2行）。80〜120文字くらいがめやすです。空のままなら、本文の書き出しが使われます。';
	}
	return $tr;
}, 98, 2 );

if ( is_admin() ) {
	add_filter( 'gettext', 'ymkrf_excerpt_word', 99 );
	add_filter( 'gettext_with_context', 'ymkrf_excerpt_word', 99 );
	add_filter( 'ngettext', 'ymkrf_excerpt_word', 99 );
}

/* ブロックエディタの文字 */
add_action( 'enqueue_block_editor_assets', function () {
	wp_add_inline_script( 'wp-hooks', "(function(){
	  if(!window.wp||!wp.hooks)return;
	  var f=function(t){return (typeof t==='string'&&t.indexOf('抜粋')>-1)?t.split('抜粋').join('ディスクリプション'):t;};
	  wp.hooks.addFilter('i18n.gettext','ymkrf/excerpt-word',f);
	  wp.hooks.addFilter('i18n.gettext_with_context','ymkrf/excerpt-word',f);
	  wp.hooks.addFilter('i18n.ngettext','ymkrf/excerpt-word',f);
	})();" );
} );
