<?php
/**
 * 協力業者さん・職人さん募集（/inquiry/conpanyrecruit/）（2026/11/03）
 *
 * ユーザー「スタッフ募集のページは別で作ってほしい」
 *   旧サイト：お知らせ https://yamakishi-reform.jp/news/488/（協力業者さん・職人さん大募集！！）
 *             エントリー https://yamakishi-reform.jp/inquiry/conpanyrecruit/
 * → 新サイトでは、案内とエントリーフォームを1ページにまとめました。
 *   URLは旧エントリーフォームと同じにしています（検索の評価・ほかのサイトからのリンクを引き継ぐため）。
 *   旧お知らせ /news/488/ は、このページへ転送します（301）。
 *
 * 編集：ダッシュボード「固定ページ」→「協力業者さん・職人さん募集」（お知らせと同じ書きかた）
 *   2026/11/03 ユーザー「編集ページ、普通にお知らせみたいな感じの作りで良いです」
 *   本文に書いたものが、ページのまん中に出ます。エントリーフォームと「社員の採用」の案内は、本文の下に自動で付きます。
 *   アイキャッチ画像＝トップページの「お知らせ」の下に出るバナー。
 * ページの見た目：ymkrf-partner.php
 * フォーム：Contact Form 7「協力業者さん・職人さんエントリー」（タイトルで探します）
 *   作りかたは wp/contact-form-7_partner.txt
 * 社員の採用（スタッフ募集）は、これまでどおり会社の採用サイト https://yamakishi.co.jp/recruit/ です。
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* URL */
add_action( 'init', function () {
	add_rewrite_rule( '^inquiry/conpanyrecruit/?$', 'index.php?ymkrf_partner=1', 'top' );
}, 20 );

/* 上のURLを WordPress に覚えさせます（1回だけ） */
add_action( 'init', function () {
	if ( get_option( 'ymkrf_partner_rule_v1' ) === '1' ) return;
	flush_rewrite_rules( false );
	update_option( 'ymkrf_partner_rule_v1', '1', false );
}, 120 );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'ymkrf_partner';
	return $vars;
} );

if ( ! function_exists( 'ymkrf_is_partner' ) ) :
function ymkrf_is_partner() {
	return (bool) get_query_var( 'ymkrf_partner' );
}
endif;

/** このページのURL（フッターなどから使います） */
function ymkrf_partner_url() {
	return home_url( '/inquiry/conpanyrecruit/' );
}

add_filter( 'template_include', function ( $tpl ) {
	if ( ! get_query_var( 'ymkrf_partner' ) ) return $tpl;
	$found = locate_template( 'ymkrf-partner.php' );
	return $found ? $found : $tpl;
} );

add_action( 'wp', function () {
	if ( ! get_query_var( 'ymkrf_partner' ) ) return;
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

/* ============================================================
   固定ページ「協力業者さん・職人さん募集」
   ============================================================ */

/** その固定ページのID */
function ymkrf_partner_page_id() {
	$id = (int) get_option( 'ymkrf_partner_page_id' );
	if ( $id && get_post_type( $id ) === 'page' && get_post_status( $id ) !== 'trash' ) return $id;
	return 0;
}

/** その固定ページ（公開中のとき） */
function ymkrf_partner_page() {
	$id = ymkrf_partner_page_id();
	$p  = $id ? get_post( $id ) : null;
	return ( $p && $p->post_status === 'publish' ) ? $p : null;
}

/** ページの名前（固定ページの題名。空なら決まった名前） */
function ymkrf_partner_name() {
	$p = ymkrf_partner_page();
	$t = $p ? trim( wp_strip_all_tags( get_the_title( $p ) ) ) : '';
	return $t !== '' ? $t : '協力業者さん・職人さん募集';
}

/** 本文の中の「募集職種」「募集エリア」のリスト（題名・説明文に使います） */
function ymkrf_partner_list( $cls ) {
	$p = ymkrf_partner_page();
	if ( ! $p || ! preg_match( '#<ul[^>]*class="[^"]*\b' . preg_quote( $cls, '#' ) . '\b[^"]*"[^>]*>(.*?)</ul>#su', $p->post_content, $m ) ) return array();
	preg_match_all( '#<li[^>]*>(.*?)</li>#su', $m[1], $li );
	return array_values( array_filter( array_map( function ( $x ) { return trim( wp_strip_all_tags( $x ) ); }, $li[1] ), 'strlen' ) );
}

/* 題名・説明文（検索結果に出るところ） */
function ymkrf_partner_title() {
	$jobs = ymkrf_partner_list( 'p-partner-jobs' );
	return ymkrf_partner_name() . ( $jobs ? '｜' . implode( '・', $jobs ) : '' ) . '｜石川県';
}
function ymkrf_partner_desc() {
	$p = ymkrf_partner_page();
	if ( $p && trim( $p->post_excerpt ) !== '' ) return trim( $p->post_excerpt );   /* 抜粋に書いたときはそれを使います */
	$jobs  = ymkrf_partner_list( 'p-partner-jobs' );
	$areas = ymkrf_partner_list( 'p-partner-areas' );
	$t = 'リフォームヤマキシ（株式会社山岸）では、リフォーム工事の協力業者さん・職人さんを募集しています。';
	if ( $jobs )  $t .= '募集職種は' . implode( '・', $jobs ) . '。';
	if ( $areas ) $t .= implode( '・', array_slice( $areas, 0, 6 ) ) . ( count( $areas ) > 6 ? 'など' : '' ) . 'の現場です。';
	return $t . '協力会社様、職人として独立されている方、お気軽にエントリーください。';
}

add_filter( 'document_title_parts', function ( $parts ) {
	if ( get_query_var( 'ymkrf_partner' ) ) {
		$parts['title'] = ymkrf_partner_title();
		unset( $parts['tagline'] );
	}
	return $parts;
} );
/* SEO SIMPLE PACK が題名を上書きするので、そちらにも渡します */
foreach ( array( 'ssp_output_title', 'ssp_output_og_title' ) as $ymkrf_f ) {
	add_filter( $ymkrf_f, function ( $t ) {
		return get_query_var( 'ymkrf_partner' ) ? ymkrf_partner_title() . ' | リフォームヤマキシ' : $t;
	}, 20 );
}
foreach ( array( 'ssp_output_description', 'ssp_output_og_description' ) as $ymkrf_f ) {
	add_filter( $ymkrf_f, function ( $d ) {
		return get_query_var( 'ymkrf_partner' ) ? ymkrf_partner_desc() : $d;
	}, 20 );
}
add_filter( 'ssp_output_canonical', function ( $u ) {
	return get_query_var( 'ymkrf_partner' ) ? ymkrf_partner_url() : $u;
}, 20 );
add_filter( 'ssp_output_og_url', function ( $u ) {
	return get_query_var( 'ymkrf_partner' ) ? ymkrf_partner_url() : $u;
}, 20 );
/* SNSで共有されたときの画像：本文の1枚目の写真 */
add_filter( 'ssp_output_og_image', function ( $u ) {
	$p = get_query_var( 'ymkrf_partner' ) ? ymkrf_partner_page() : null;
	if ( $p && preg_match( '#wp-image-(\d+)#', $p->post_content, $m ) ) {
		$src = wp_get_attachment_image_url( (int) $m[1], 'large' );
		if ( $src ) return $src;
	}
	return $u;
}, 20 );

/* 旧お知らせ /news/488/（協力業者さん・職人さん大募集！！）→ このページへ */
add_action( 'template_redirect', function () {
	$path = trim( (string) parse_url( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH ), '/' );
	$base = trim( (string) parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	if ( $base !== '' && strpos( $path, $base . '/' ) === 0 ) $path = substr( $path, strlen( $base ) + 1 );
	if ( $path === 'news/488' ) {
		wp_redirect( ymkrf_partner_url(), 301 );
		exit;
	}
}, 1 );

/** トップページのバナー（＝このページのアイキャッチ画像）。front-page.php から使います */
function ymkrf_partner_imgs() {
	$p  = ymkrf_partner_page();
	$id = $p ? (int) get_post_thumbnail_id( $p ) : 0;
	return $id ? array( 'banner' => $id ) : array();
}

/** 画像の説明文（メディアで入れてあればそれ、なければ決まった文） */
function ymkrf_partner_alt( $id, $fallback ) {
	$a = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
	return $a !== '' ? $a : $fallback;
}

/* 固定ページが無ければ1回だけ作ります */
add_action( 'admin_init', function () {
	if ( ymkrf_partner_page_id() || ! current_user_can( 'manage_options' ) ) return;
	$id = wp_insert_post( array(
		'post_type'   => 'page',
		'post_status' => 'publish',
		'post_title'  => '協力業者さん・職人さん募集',
		'post_name'   => 'partner-recruit',
		'post_content'=> '',
		'menu_order'  => 90,
	) );
	if ( $id && ! is_wp_error( $id ) ) update_option( 'ymkrf_partner_page_id', (int) $id, false );
} );

/* 本文がまだ空なら、旧サイトから写した内容を1回だけ入れます（そのあとは自由に直せます） */
add_action( 'admin_init', function () {
	if ( get_option( 'ymkrf_partner_content_v1' ) === '1' || ! current_user_can( 'manage_options' ) ) return;
	$pid = ymkrf_partner_page_id();
	if ( ! $pid ) return;
	$p = get_post( $pid );
	if ( $p && trim( $p->post_content ) === '' ) {
		wp_update_post( array( 'ID' => $pid, 'post_content' => ymkrf_partner_first_content() ) );
		if ( ! has_post_thumbnail( $pid ) && get_post_type( 20105 ) === 'attachment' ) set_post_thumbnail( $pid, 20105 );
	}
	update_option( 'ymkrf_partner_content_v1', '1', false );
}, 20 );

/** はじめの本文（ブロック）。旧サイトのお知らせ・チラシから */
function ymkrf_partner_first_content() {
	$img = function ( $id, $alt, $link = false ) {
		if ( get_post_type( $id ) !== 'attachment' ) return '';
		$src = wp_get_attachment_image_url( $id, 'large' );
		$full = wp_get_attachment_url( $id );
		$tag = '<img src="' . esc_url( $src ) . '" alt="' . esc_attr( $alt ) . '" class="wp-image-' . (int) $id . '"/>';
		if ( $link ) $tag = '<a href="' . esc_url( $full ) . '" target="_blank" rel="noreferrer noopener">' . $tag . '</a>';
		$attr = $link ? '{"id":' . (int) $id . ',"sizeSlug":"large","linkDestination":"media","align":"center"}' : '{"id":' . (int) $id . ',"sizeSlug":"large","linkDestination":"none"}';
		return "<!-- wp:image $attr -->\n<figure class=\"wp-block-image" . ( $link ? ' aligncenter' : '' ) . " size-large\">$tag</figure>\n<!-- /wp:image -->\n\n";
	};
	$p   = function ( $t ) { return "<!-- wp:paragraph -->\n<p>$t</p>\n<!-- /wp:paragraph -->\n\n"; };
	$h   = function ( $t ) { return "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">$t</h2>\n<!-- /wp:heading -->\n\n"; };
	$ul  = function ( $items, $cls, $ordered = false ) {
		$tag = $ordered ? 'ol' : 'ul';
		$att = '{' . ( $ordered ? '"ordered":true,' : '' ) . '"className":"' . $cls . '"}';
		$o = "<!-- wp:list $att -->\n<$tag class=\"wp-block-list $cls\">";
		foreach ( $items as $it ) $o .= "<!-- wp:list-item -->\n<li>$it</li>\n<!-- /wp:list-item -->";
		return $o . "</$tag>\n<!-- /wp:list -->\n\n";
	};
	$c  = $p( 'おかげさまで、リフォームヤマキシにご依頼くださるお客様の案件が多くなっています。<br>現在、協力業者さま・職人さんを募集しております。' );
	$c .= $img( 20106, 'リフォームヤマキシのスタッフととんとこトン' );
	$c .= $p( '<strong>工事現場・案件が多数</strong>ございます。<br>協力会社様、職人として独立されている方、どうぞお気軽にご連絡ください。<br>お電話、または下の専用フォームで承ります。' );
	$c .= $h( '募集職種' );
	$c .= $ul( array( '大工', '設備', '内装', '塗装' ), 'p-partner-jobs' );
	$c .= $h( '募集エリア' );
	$c .= $ul( array( '金沢市', '小松市', '白山市', '能美市', '羽咋市', '七尾市', '野々市市', '加賀市', '川北町', '宝達志水町', '中能登町', '志賀町' ), 'p-partner-areas' );
	$c .= $h( 'お仕事の流れ' );
	$c .= $ul( array(
		'<strong>業者登録</strong><br>まずは、ヤマキシで事業者登録をしていただきます。契約については、担当者と詳しくお話しください。',
		'<strong>ご連絡</strong><br>工事の案件が入りしだい、ご連絡いたします。日程のご都合がつく場合は、施工をお願いします。',
		'<strong>施工</strong><br>決まった日程で施工をお願いします。施工内容は、ヤマキシの営業担当者と事前に話し合います。',
		'<strong>引き渡し</strong><br>担当する施工箇所が終わりましたら、ヤマキシの営業担当者へご連絡ください。',
		'<strong>報酬</strong><br>施工いただいた報酬は、指定日にお振込みいたします。',
	), 'p-partner-steps', true );
	$c .= $img( 20107, '協力業者さん・職人さん大募集のチラシ（募集職種：大工・設備・内装・塗装）', true );
	$c .= $h( 'お電話でのお問い合わせ' );
	$c .= $p( '<a href="tel:0761233636">0761-23-3636</a>（担当：伊達）<br>ご不明な点・ご質問等は、お電話または下のエントリーフォームからどうぞ。' );
	return $c;
}

/* 編集画面は「お知らせ」と同じ作り（2026/11/03 ユーザー「お知らせみたいな感じの作りで良い」
   「リビジョンは大きくはいりません。お知らせページのように公開の中にあるのがベスト」
   →「他の固定ページも同様なスタイルにして」で、固定ページぜんぶ同じにしました）
   ・ブロックエディタではなく、お知らせと同じ「ビジュアル／コード」の編集画面
   ・大きなリビジョンの欄は出さない（右の「公開」の中の「リビジョン：◯ 表示」から見られます） */
add_filter( 'use_block_editor_for_post_type', function ( $use, $type ) {
	return $type === 'page' ? false : $use;
}, 10, 2 );
/* 固定ページにも「ディスクリプション」（＝抜粋）の欄を出します（検索結果の説明文） */
add_action( 'init', function () { add_post_type_support( 'page', 'excerpt' ); } );
/* この欄は、はじめから見えるように（画面上の「表示オプション」で隠されていても出します） */
add_filter( 'hidden_meta_boxes', function ( $hidden, $screen ) {
	return ( $screen && $screen->id === 'page' ) ? array_values( array_diff( (array) $hidden, array( 'postexcerpt' ) ) ) : $hidden;
}, 10, 2 );
add_action( 'add_meta_boxes_page', function ( $post ) {
	remove_meta_box( 'revisionsdiv', 'page', 'normal' );
	remove_meta_box( 'slugdiv', 'page', 'normal' );
	remove_meta_box( 'commentstatusdiv', 'page', 'normal' );
	remove_meta_box( 'commentsdiv', 'page', 'normal' );
	remove_meta_box( 'authordiv', 'page', 'normal' );
}, 99 );
/* ブロックの目じるし（<!-- wp:… -->）は、お知らせと同じ編集画面ではじゃまになるので1回だけ外します */
add_action( 'admin_init', function () {
	if ( get_option( 'ymkrf_partner_content_v2' ) === '1' || ! current_user_can( 'manage_options' ) ) return;
	$pid = ymkrf_partner_page_id();
	if ( ! $pid ) return;
	$c = (string) get_post_field( 'post_content', $pid );
	if ( strpos( $c, '<!-- wp:' ) !== false ) {
		$c = preg_replace( '#<!-- /?wp:[^>]*-->\n?#', '', $c );
		$c = preg_replace( "#\n{3,}#", "\n\n", $c );
		wp_update_post( array( 'ID' => $pid, 'post_content' => trim( $c ) ) );
	}
	update_option( 'ymkrf_partner_content_v2', '1', false );
}, 30 );

/* 固定ページの一覧・「ページを表示」のURLは /inquiry/conpanyrecruit/ に */
add_filter( 'page_link', function ( $link, $id ) {
	return ( (int) $id && (int) $id === ymkrf_partner_page_id() ) ? ymkrf_partner_url() : $link;
}, 10, 2 );
/* 「プレビュー」のときも、本番と同じ見た目で出します */
add_filter( 'template_include', function ( $tpl ) {
	$pid = ymkrf_partner_page_id();
	if ( $pid && is_page( $pid ) && is_preview() ) {
		$found = locate_template( 'ymkrf-partner.php' );
		if ( $found ) return $found;
	}
	return $tpl;
}, 20 );
/* 固定ページそのもののURL（/partner-recruit/）に来たら、本当のページへ（プレビューはのぞく） */
add_action( 'template_redirect', function () {
	$pid = ymkrf_partner_page_id();
	if ( $pid && is_page( $pid ) && ! is_preview() ) { wp_redirect( ymkrf_partner_url(), 301 ); exit; }
}, 2 );
/* 一覧で、このページにひとこと */
add_filter( 'display_post_states', function ( $st, $post ) {
	if ( (int) $post->ID === ymkrf_partner_page_id() ) $st['ymkrf_partner'] = '/inquiry/conpanyrecruit/ のページ';
	return $st;
}, 10, 2 );

/* ============================================================
   本文を、前の見た目（写真が右・職種のボタン・流れのカード・チラシ）に並べなおす
   （2026/11/03 ユーザー「フロントはさっきの作りの方がよかった」）

   編集はお知らせと同じ書きかたのまま。本文から次のものを見つけて、決まった場所に出します。
     ・1枚目の写真の前の文 …… 見出しの下の文
     ・1枚目の写真 …………… 右の写真
     ・写真のあと、最初の見出しまでの文 … 写真のとなりの案内文
     ・見出し「募集職種」の下のリスト …… 赤いボタン
     ・見出し「募集エリア」の下のリスト … 丸い枠
     ・見出し「お仕事の流れ」の下の番号リスト … 流れのカード（太字＝題名、そのあと＝説明）
     ・2枚目の画像 ………………… チラシ（流れの下）
     ・電話番号のリンク（tel:）の段落 … 電話のボタン（番号のあとの（　）の中＝小さい文字）
   上のどれにも当たらないもの（新しく足した見出しや文）は、流れの下にそのまま出します。
   ============================================================ */
function ymkrf_partner_parse( $html ) {
	$out = array( 'lead' => '', 'photo' => 0, 'intro' => '', 'jobs' => array(), 'areas' => array(),
	              'steps' => array(), 'poster' => 0, 'tel' => '', 'tel_who' => '', 'tel_note' => '', 'extra' => '' );
	if ( trim( $html ) === '' || ! class_exists( 'DOMDocument' ) ) { $out['extra'] = $html; return $out; }

	$prev = libxml_use_internal_errors( true );
	$doc  = new DOMDocument();
	$doc->loadHTML( '<?xml encoding="UTF-8"><div id="ymkp-root">' . $html . '</div>' );
	libxml_clear_errors();
	libxml_use_internal_errors( $prev );
	$root = $doc->getElementById( 'ymkp-root' );
	if ( ! $root ) { $out['extra'] = $html; return $out; }

	$h    = function ( $n ) use ( $doc ) { return $doc->saveHTML( $n ); };
	$txt  = function ( $n ) { return trim( preg_replace( '/\s+/u', ' ', $n->textContent ) ); };
	$imgid = function ( $n ) {
		foreach ( ( $n->nodeName === 'img' ? array( $n ) : iterator_to_array( $n->getElementsByTagName( 'img' ) ) ) as $im ) {
			if ( preg_match( '/wp-image-(\d+)/', (string) $im->getAttribute( 'class' ), $m ) ) return (int) $m[1];
			$id = attachment_url_to_postid( preg_replace( '/-\d+x\d+(?=\.\w+$)/', '', (string) $im->getAttribute( 'src' ) ) );
			if ( $id ) return (int) $id;
		}
		return 0;
	};
	$items = function ( $list ) use ( $txt ) {
		$a = array();
		foreach ( $list->childNodes as $li ) if ( $li->nodeName === 'li' && $txt( $li ) !== '' ) $a[] = $txt( $li );
		return $a;
	};
	$is_list = function ( $n ) { return $n && in_array( $n->nodeName, array( 'ul', 'ol' ), true ); };

	$nodes = array();
	foreach ( $root->childNodes as $n ) {
		if ( $n->nodeType === XML_TEXT_NODE && trim( $n->textContent ) === '' ) continue;
		$nodes[] = $n;
	}
	$used = array();
	$seen_h = false;
	$extra = '';
	$cnt = count( $nodes );
	for ( $i = 0; $i < $cnt; $i++ ) {
		if ( isset( $used[ $i ] ) ) continue;
		$n   = $nodes[ $i ];
		$tag = $n->nodeName;
		$cls = $n instanceof DOMElement ? (string) $n->getAttribute( 'class' ) : '';

		/* リスト（目じるしのクラスつき） */
		if ( $is_list( $n ) && strpos( $cls, 'p-partner-jobs' ) !== false && ! $out['jobs'] )  { $out['jobs']  = $items( $n ); continue; }
		if ( $is_list( $n ) && strpos( $cls, 'p-partner-areas' ) !== false && ! $out['areas'] ) { $out['areas'] = $items( $n ); continue; }

		/* 見出し */
		if ( in_array( $tag, array( 'h2', 'h3', 'h4' ), true ) ) {
			$t    = $txt( $n );
			$next = isset( $nodes[ $i + 1 ] ) ? $nodes[ $i + 1 ] : null;
			if ( mb_strpos( $t, '職種' ) !== false && $is_list( $next ) && ! $out['jobs'] )      { $out['jobs']  = $items( $next ); $used[ $i + 1 ] = 1; $seen_h = true; continue; }
			if ( mb_strpos( $t, 'エリア' ) !== false && $is_list( $next ) && ! $out['areas'] )   { $out['areas'] = $items( $next ); $used[ $i + 1 ] = 1; $seen_h = true; continue; }
			if ( mb_strpos( $t, '流れ' ) !== false && $is_list( $next ) && ! $out['steps'] ) {
				foreach ( $next->childNodes as $li ) {
					if ( $li->nodeName !== 'li' ) continue;
					$st = $li->getElementsByTagName( 'strong' )->item( 0 );
					if ( ! $st ) $st = $li->getElementsByTagName( 'b' )->item( 0 );
					$all = $txt( $li );
					$ttl = $st ? $txt( $st ) : '';
					$tx  = $ttl !== '' ? trim( mb_substr( $all, mb_strlen( $ttl ) ) ) : $all;
					if ( $ttl !== '' || $tx !== '' ) $out['steps'][] = array( 'ttl' => $ttl, 'txt' => $tx );
				}
				$used[ $i + 1 ] = 1; $seen_h = true; continue;
			}
			if ( mb_strpos( $t, '電話' ) !== false && $next && $next->getElementsByTagName( 'a' )->length ) {
				/* 見出し「お電話〜」は、電話のボタンに入るので出しません（下の段落で番号を拾います） */
				$seen_h = true; continue;
			}
			$seen_h = true;
			$extra .= $h( $n );
			continue;
		}

		/* 電話番号のリンクがある段落 */
		if ( $tag === 'p' && $out['tel'] === '' ) {
			foreach ( $n->getElementsByTagName( 'a' ) as $a ) {
				if ( stripos( (string) $a->getAttribute( 'href' ), 'tel:' ) !== 0 ) continue;
				$out['tel'] = $txt( $a );
				/* 行ごと（<br> で区切る）に分けて、番号のあとの（　）を「小さい文字」にします */
				$ph    = str_replace( $h( $a ), '', $h( $n ) );
				$lines = array_values( array_filter( array_map( function ( $x ) {
					return trim( html_entity_decode( wp_strip_all_tags( $x ), ENT_QUOTES, 'UTF-8' ) );
				}, preg_split( '#<br\s*/?>|\R#iu', $ph ) ), 'strlen' ) );
				if ( $lines && preg_match( '/^[（(](.+?)[）)]$/u', $lines[0], $m ) ) { $out['tel_who'] = $m[1]; array_shift( $lines ); }
				$out['tel_note'] = implode( "\n", $lines );
				continue 2;
			}
		}

		/* 画像 */
		$iid = $n instanceof DOMElement ? $imgid( $n ) : 0;
		if ( $iid && $txt( $n ) === '' ) {
			if ( ! $out['photo'] && ! $seen_h )   { $out['photo']  = $iid; continue; }
			if ( ! $out['poster'] )               { $out['poster'] = $iid; continue; }
		}

		/* 文 */
		if ( $tag === 'p' && ! $seen_h ) {
			if ( ! $out['photo'] && $out['lead'] === '' ) { $out['lead']  .= $h( $n ); continue; }
			if ( $out['photo'] || $out['lead'] !== '' )   { $out['intro'] .= $h( $n ); continue; }
		}

		$extra .= $h( $n );
	}
	$out['extra'] = $extra;
	return $out;
}

/* ============================================================
   このページだけのCSS（2026/11/03 ユーザー「CSSも同じページ内で修正はできないもの？」
   →「他の固定ページも同様なスタイルにして」で、固定ページぜんぶに付けました）
   編集画面の本文の下に「このページだけのCSS」の欄。書いたCSSは、そのページにだけ読みこみます
   （ほかのページには効きません）。書けるのは「すべて」の権限の人だけ。
   保存先：この固定ページのメタ `_ymkrf_page_css`。
   ============================================================ */
add_action( 'add_meta_boxes_page', function ( $post ) {
	if ( ! current_user_can( 'manage_options' ) ) return;
	add_meta_box( 'ymkrf_page_css', 'このページだけのCSS', function ( $post ) {
		$is_ptn = (int) $post->ID === ymkrf_partner_page_id();
		wp_nonce_field( 'ymkrf_page_css', 'ymkrf_page_css_nonce' );
		$v = (string) get_post_meta( $post->ID, '_ymkrf_page_css', true );
		?>
		<textarea name="ymkrf_page_css" rows="10" spellcheck="false"
		  style="width:100%;font-family:Consolas,Menlo,monospace;font-size:13px;line-height:1.6;tab-size:2"
		  placeholder="<?php echo $is_ptn ? '例）.p-partner__jobs li{ background:#c92800; }' : '例）.p-pagebody h2{ border-left-color:#c92800; }'; ?>"><?php echo esc_textarea( $v ); ?></textarea>
		<p class="description" style="margin-top:6px;line-height:1.8">
		  ここに書いたCSSは、このページにだけ効きます。「更新」で反映。<br>
		  <?php if ( $is_ptn ) : ?>
		  よく使う場所：<code>.p-partner__jobs li</code>（募集職種のボタン）／<code>.p-partner__areas li span</code>（エリアの枠）／
		  <code>.p-inq__step</code>（流れのカード）／<code>.p-partner__photo img</code>（右の写真）／<code>.p-partner__poster</code>（チラシ）／
		  <code>.p-partner__tel</code>（電話のボタン）
		  <?php else : ?>
		  よく使う場所：<code>.p-pagebody</code>（本文ぜんたい）／<code>.p-pagebody h2</code>（見出し）／<code>.p-pagebody p</code>（文）／
		  <code>.p-pagebody img</code>（画像）／<code>.p-pagehead</code>（いちばん上の題名の帯）
		  <?php endif; ?>
		</p>
		<?php
	}, 'page', 'normal', 'default' );
}, 100 );

add_action( 'save_post_page', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;
	if ( empty( $_POST['ymkrf_page_css_nonce'] ) || ! wp_verify_nonce( $_POST['ymkrf_page_css_nonce'], 'ymkrf_page_css' ) ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;
	$css = isset( $_POST['ymkrf_page_css'] ) ? (string) wp_unslash( $_POST['ymkrf_page_css'] ) : '';
	$css = str_ireplace( array( '</style', '<script', '<?' ), '', wp_strip_all_tags( $css ) );
	if ( trim( $css ) === '' ) delete_post_meta( $post_id, '_ymkrf_page_css' );
	else update_post_meta( $post_id, '_ymkrf_page_css', $css );
} );

add_action( 'wp_head', function () {
	if ( get_query_var( 'ymkrf_partner' ) ) $pid = ymkrf_partner_page_id();
	elseif ( is_page() )                     $pid = (int) get_queried_object_id();
	else return;
	if ( ! $pid ) return;
	$css = (string) get_post_meta( $pid, '_ymkrf_page_css', true );
	if ( trim( $css ) === '' ) return;
	echo "\n<style id=\"ymkrf-thispage-css\">\n" . str_ireplace( '</style', '', $css ) . "\n</style>\n"; // phpcs:ignore
}, 100 );
