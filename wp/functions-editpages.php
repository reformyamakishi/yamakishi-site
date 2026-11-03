<?php
/**
 * 文章のページを「固定ページ」で直せるようにする（2026/11/03）
 *
 * ユーザー「基本的には編集はすべてワードプレスで行えるようにしたいので、固定ページへ旧からの内容を移動してください。
 *           フロントの見た目は同じでお願いします」
 *         「すべて固定ページに作って編集できるようにしてください。私がもし退職しても、会社のみんなが編集できる状態にしたいです」
 *
 * ■ 対象のページ（URLはこれまでと同じ）
 *   こだわり・特徴 /about/ ・会社概要 /company/ ・代表挨拶 /message/ ・保証について /warranty/
 *   リフォームの流れ /flow/ ・よくあるご質問 /faq/ ・プライバシーポリシー /privacy/
 *
 * ■ しくみ
 *   ・ダッシュボード「固定ページ」に、同じ名前・同じURL（スラッグ）のページがあります。
 *   ・本文には、いまの見た目の部品（枠・カード・表など）ごと入っています。
 *     編集画面の「ビジュアル」では、ホームページと同じ見た目のまま文字を書きかえられます
 *     （ホームページのCSSを編集画面にも読みこんでいます）。
 *   ・いちばん上の「英字」と「見出しの下の文」は、本文の下の「ページ上部の見出し」の欄で直します。
 *     題名（いちばん上の欄）が、ページの大きな見出しになります。
 *   ・いちばん下の「まずは、お困りごとから」（LINE・来店予約・電話のボタン）は、全ページ共通なので
 *     テーマのファイルのまま（各ページの ymkrf-〇〇.php）です。
 *   ・固定ページが無い・下書きのときは、これまでどおりテーマのファイルの中身を出します（もしものための控え）。
 *
 * ■ 編集画面の設定（このページたちだけ）
 *   ・「ビジュアル」で段落の <p> が消えないように、自動の段落整形（wpautop）を切っています。
 *     ホームページ側も、本文をそのまま出します（勝手に <p> や <br> を足しません）。
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** 対象のページ。キー＝スラッグ＝URL */
function ymkrf_ep_defs() {
	return array(
		'about'    => array( 'title' => 'ヤマキシのこだわり・特徴', 'qv' => 'ymkrf_about',    'css' => array( 'lp' ),            'head' => false ),
		'company'  => array( 'title' => '会社概要',                 'qv' => 'ymkrf_company',  'css' => array( 'lp', 'company' ) ),
		'message'  => array( 'title' => '代表挨拶',                 'qv' => 'ymkrf_message',  'css' => array( 'lp', 'company' ) ),
		'warranty' => array( 'title' => '保証について',             'qv' => 'ymkrf_warranty', 'css' => array( 'lp', 'warranty' ), 'chara' => 'char-anshin.webp' ),
		'flow'     => array( 'title' => 'リフォームの流れ',         'qv' => 'ymkrf_flow',     'css' => array( 'lp', 'flow' ),     'chara' => 'char-plan.webp' ),
		'faq'      => array( 'title' => 'よくあるご質問',           'qv' => 'ymkrf_faq',      'css' => array( 'lp', 'faq' ),      'chara' => 'char-hello.webp' ),
		'privacy'  => array( 'title' => 'プライバシーポリシー',     'qv' => 'ymkrf_privacy',  'css' => array( 'privacy' ),        'chara' => 'char-anshin.webp' ),
	);
}

/** 固定ページのID（スラッグで探します。1回の表示で1回だけ） */
function ymkrf_ep_id( $key ) {
	static $ids = array();
	if ( isset( $ids[ $key ] ) ) return $ids[ $key ];
	$p = get_page_by_path( $key, OBJECT, 'page' );
	return $ids[ $key ] = ( $p && $p->post_status !== 'trash' ) ? (int) $p->ID : 0;
}

/** その固定ページのキー（固定ページのIDから） */
function ymkrf_ep_key_of( $post ) {
	$post = get_post( $post );
	if ( ! $post || $post->post_type !== 'page' ) return '';
	$defs = ymkrf_ep_defs();
	return isset( $defs[ $post->post_name ] ) && ymkrf_ep_id( $post->post_name ) === (int) $post->ID ? $post->post_name : '';
}

/**
 * ホームページに出す固定ページ（公開中のときだけ）。
 * 「プレビュー」のときは、編集中の内容（自動保存）を使います。
 */
function ymkrf_ep_page( $key ) {
	$id = ymkrf_ep_id( $key );
	if ( ! $id ) return null;
	$p = get_post( $id );
	if ( ! $p ) return null;
	if ( isset( $_GET['preview'] ) && current_user_can( 'edit_page', $id ) ) {
		$auto = wp_get_post_autosave( $id, get_current_user_id() );
		if ( $auto && strtotime( $auto->post_modified_gmt ) >= strtotime( $p->post_modified_gmt ) ) {
			$p = clone $p;
			$p->post_title   = $auto->post_title;
			$p->post_content = $auto->post_content;
		}
		return $p;
	}
	return $p->post_status === 'publish' ? $p : null;
}

/** パンくずなどに出すページ名 */
function ymkrf_ep_crumb( $key, $fallback ) {
	$p = ymkrf_ep_page( $key );
	$t = $p ? trim( wp_strip_all_tags( $p->post_title ) ) : '';
	return $t !== '' ? $t : $fallback;
}

/** 本文（ショートコードだけ動かします。段落の自動整形はしません） */
function ymkrf_ep_body_html( $p ) {
	$html = do_shortcode( (string) $p->post_content );
	if ( function_exists( 'wp_filter_content_tags' ) ) $html = wp_filter_content_tags( $html );
	return $html;
}

/** 見出し（英字・題名・見出しの下の文）と本文を出します */
function ymkrf_ep_output( $key, $p ) {
	$defs = ymkrf_ep_defs();
	$d    = $defs[ $key ];
	$html = ymkrf_ep_body_html( $p );

	if ( $key === 'faq' ) $html = ymkrf_ep_faq_fix( $html );

	if ( ! isset( $d['head'] ) || $d['head'] !== false ) {
		$en   = (string) get_post_meta( $p->ID, 'ymkrf_ep_en', true );
		$lead = (string) get_post_meta( $p->ID, 'ymkrf_ep_lead', true );
		$asset = get_stylesheet_directory_uri();
		?>
<div class="p-pagehead">
  <div class="l-wrap p-pagehead__inner">
    <?php if ( ! empty( $d['chara'] ) ) : ?>
    <img class="p-pagehead__chara c-chara--float" src="<?php echo esc_url( $asset . '/assets/img/character/' . $d['chara'] ); ?>"
         width="503" height="640" alt="" loading="lazy" decoding="async">
    <?php endif; ?>
    <?php if ( trim( $en ) !== '' ) : ?><span class="p-pagehead__en"><?php echo esc_html( $en ); ?></span><?php endif; ?>
    <h1 class="p-pagehead__title"><?php echo esc_html( $p->post_title ); ?></h1>
    <?php if ( trim( wp_strip_all_tags( $lead ) ) !== '' ) : ?>
    <p class="p-pagehead__lead"><?php echo wp_kses_post( $lead ); ?></p>
    <?php endif; ?>
  </div>
</div>
		<?php
	}
	echo $html; // phpcs:ignore
}

/* ---- よくあるご質問：目次の数をかぞえなおす・検索エンジン用のデータを本文から作る ---- */
function ymkrf_ep_faq_fix( $html ) {
	if ( ! class_exists( 'DOMDocument' ) ) return $html;
	$prev = libxml_use_internal_errors( true );
	$doc  = new DOMDocument();
	$doc->loadHTML( '<?xml encoding="UTF-8"><div id="ymkep">' . $html . '</div>' );
	libxml_clear_errors();
	libxml_use_internal_errors( $prev );
	$xp = new DOMXPath( $doc );
	$has = function ( $cls ) { return "contains(concat(' ',normalize-space(@class),' '),' $cls ')"; };

	/* 目次の数 */
	foreach ( $xp->query( '//ul[' . $has( 'p-qanav' ) . ']/li/a' ) as $a ) {
		$id = ltrim( (string) $a->getAttribute( 'href' ), '#' );
		$sec = $id !== '' ? $doc->getElementById( $id ) : null;
		$n = $sec ? $xp->query( './/*[' . $has( 'p-qa__set' ) . ']', $sec )->length : 0;
		$sp = $a->getElementsByTagName( 'span' )->item( 0 );
		if ( $sp ) $sp->nodeValue = (string) $n;
	}
	/* 検索エンジン用（FAQPage） */
	$qa = array();
	foreach ( $xp->query( '//*[' . $has( 'p-qa__set' ) . ']' ) as $set ) {
		$q = $xp->query( './/*[' . $has( 'p-qa__bubble--q' ) . ']', $set )->item( 0 );
		$a = $xp->query( './/*[' . $has( 'p-qa__bubble--a' ) . ']', $set )->item( 0 );
		if ( ! $q || ! $a ) continue;
		$qa[] = array(
			'@type' => 'Question',
			'name'  => trim( preg_replace( '/\s+/u', ' ', $q->textContent ) ),
			'acceptedAnswer' => array( '@type' => 'Answer', 'text' => trim( preg_replace( '/\s+/u', ' ', $a->textContent ) ) ),
		);
	}
	$GLOBALS['ymkrf_ep_faq_ld'] = $qa ? array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $qa ) : null;

	$root = $doc->getElementById( 'ymkep' );
	$out  = '';
	foreach ( $root->childNodes as $c ) $out .= $doc->saveHTML( $c );
	return $out;
}

/* ============================================================
   編集画面
   ============================================================ */

/* 英字・見出しの下の文（REST でも読み書きできるように） */
add_action( 'init', function () {
	foreach ( array( 'ymkrf_ep_en', 'ymkrf_ep_lead' ) as $k ) {
		register_post_meta( 'page', $k, array(
			'type' => 'string', 'single' => true, 'show_in_rest' => true,
			'auth_callback' => function () { return current_user_can( 'edit_pages' ); },
		) );
	}
} );

add_action( 'add_meta_boxes_page', function ( $post ) {
	$key = ymkrf_ep_key_of( $post );
	if ( $key === '' ) return;
	$defs = ymkrf_ep_defs();
	if ( isset( $defs[ $key ]['head'] ) && $defs[ $key ]['head'] === false ) {
		add_meta_box( 'ymkrf_ep_note', 'このページの直しかた', 'ymkrf_ep_note_box', 'page', 'normal', 'high' );
		return;
	}
	add_meta_box( 'ymkrf_ep_head', 'ページ上部の見出し', function ( $post ) {
		wp_nonce_field( 'ymkrf_ep_head', 'ymkrf_ep_head_nonce' );
		$en   = (string) get_post_meta( $post->ID, 'ymkrf_ep_en', true );
		$lead = (string) get_post_meta( $post->ID, 'ymkrf_ep_lead', true );
		?>
		<p style="margin:0 0 4px;font-weight:700">英字の小見出し（赤い小さな文字）</p>
		<input type="text" name="ymkrf_ep_en" value="<?php echo esc_attr( $en ); ?>" style="width:100%;max-width:360px" placeholder="例）MESSAGE">
		<p style="margin:14px 0 4px;font-weight:700">見出しの下の文</p>
		<textarea name="ymkrf_ep_lead" rows="3" style="width:100%"><?php echo esc_textarea( $lead ); ?></textarea>
		<p class="description">大きな見出しは、いちばん上の「題名」です。太字は &lt;b&gt;〜&lt;/b&gt;、改行は &lt;br&gt;（スマホだけ改行は &lt;br class="xs-only"&gt;）。</p>
		<?php ymkrf_ep_note_box(); ?>
		<?php
	}, 'page', 'normal', 'high' );
} );

function ymkrf_ep_note_box() {
	?>
	<div style="margin-top:14px;padding:10px 14px;background:#fff8f6;border:1px solid #f5c9bd;border-radius:8px;line-height:1.9;font-size:13px">
	  <b>このページの直しかた</b><br>
	  ・本文は「ビジュアル」で、ホームページと同じ見た目のまま文字を書きかえられます。<br>
	  ・枠やカードの中の文字だけを書きかえてください。枠ごと消すと、見た目がくずれることがあります。<br>
	  ・カードや行を増やすときは、同じものをコピーして貼りつけてから、文字を書きかえるのがかんたんです（「コード」のほうが確実です）。<br>
	  ・「プレビュー」で見た目を確かめてから「更新」してください。もとに戻したいときは、右の「リビジョン：◯ 表示」から戻せます。<br>
	  ・いちばん下の「LINEで相談する」などのボタンは、全ページ共通のため、ここにはありません。
	</div>
	<?php
}

add_action( 'save_post_page', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;
	if ( empty( $_POST['ymkrf_ep_head_nonce'] ) || ! wp_verify_nonce( $_POST['ymkrf_ep_head_nonce'], 'ymkrf_ep_head' ) ) return;
	if ( ! current_user_can( 'edit_page', $post_id ) ) return;
	update_post_meta( $post_id, 'ymkrf_ep_en', sanitize_text_field( wp_unslash( isset( $_POST['ymkrf_ep_en'] ) ? $_POST['ymkrf_ep_en'] : '' ) ) );
	update_post_meta( $post_id, 'ymkrf_ep_lead', wp_kses_post( wp_unslash( isset( $_POST['ymkrf_ep_lead'] ) ? $_POST['ymkrf_ep_lead'] : '' ) ) );
} );

/* 編集画面に、ホームページと同じCSSを読みこみます（ビジュアルで同じ見た目に） */
function ymkrf_ep_editing_key() {
	if ( ! is_admin() ) return '';
	$id = isset( $_GET['post'] ) ? (int) $_GET['post'] : ( isset( $_POST['post_ID'] ) ? (int) $_POST['post_ID'] : 0 );
	return $id ? ymkrf_ep_key_of( $id ) : '';
}
add_filter( 'mce_css', function ( $css ) {
	$key = ymkrf_ep_editing_key();
	if ( $key === '' ) return $css;
	$defs  = ymkrf_ep_defs();
	$dir   = get_stylesheet_directory_uri() . '/assets/css/';
	$ver   = defined( 'YMKRF_VER' ) ? YMKRF_VER : '1';
	$files = array_merge( array( 'common', 'page' ), $defs[ $key ]['css'] );
	$urls  = array();
	foreach ( $files as $f ) $urls[] = $dir . $f . '.css?ver=' . $ver;
	$urls[] = admin_url( 'admin-ajax.php?action=ymkrf_ep_editor_css&ver=' . $ver );
	return ( $css ? $css . ',' : '' ) . implode( ',', $urls );
} );
/* 編集画面の中だけの、ちょっとした調整（アニメーションで隠れている部品を見えるように等） */
add_action( 'wp_ajax_ymkrf_ep_editor_css', function () {
	header( 'Content-Type: text/css; charset=UTF-8' );
	echo 'body.mce-content-body{max-width:none;margin:0;padding:0 0 40px;background:#fff}'
	   . '[data-reveal]{opacity:1!important;transform:none!important}'
	   . '.c-chara--float{animation:none!important}'
	   . 'body.mce-content-body .l-section{outline:1px dashed #f5c9bd;outline-offset:-1px}';
	exit;
} );
/* 段落の自動整形を切ります（このページたちだけ） */
add_filter( 'tiny_mce_before_init', function ( $init ) {
	if ( ymkrf_ep_editing_key() === '' ) return $init;
	$init['wpautop']              = false;
	$init['indent']               = true;
	$init['body_class']           = ( isset( $init['body_class'] ) ? $init['body_class'] . ' ' : '' ) . 'ymkrf-ep';
	$init['extended_valid_elements'] = 'b,i,span[*],div[*],section[*],figure[*],figcaption[*],img[*],a[*],ul[*],ol[*],li[*],p[*],h2[*],h3[*],table[*],tr[*],th[*],td[*],dl[*],dt[*],dd[*],br[*],em[*],small[*]';
	$init['keep_styles']          = true;
	$init['remove_trailing_brs']  = false;
	return $init;
} );
/* 固定ページの一覧の表示（2026/11/03 ユーザー「良い感じ、今後はそんな感じで一覧は表示して」）
   ・どの固定ページにも「/URL/ のページ」と出す（あとから作るページも自動で）
   ・WordPressが付ける「プライバシーポリシーページ」の目じるしは出さない
   ・「下書き」「非公開」などの状態はそのまま出す
   ※協力業者さん募集（/inquiry/conpanyrecruit/）は functions-partner.php で同じように出しています */
add_filter( 'display_post_states', function ( $st, $post ) {
	if ( ! $post || $post->post_type !== 'page' ) return $st;
	unset( $st['page_for_privacy_policy'] );
	if ( isset( $st['ymkrf_partner'] ) ) return $st;
	if ( $post->post_status === 'publish' || $post->post_status === 'private' ) {
		$path = (string) wp_parse_url( get_permalink( $post ), PHP_URL_PATH );
		$base = rtrim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
		if ( $base !== '' && strpos( $path, $base ) === 0 ) $path = substr( $path, strlen( $base ) );
		if ( $path !== '' && strpos( $path, '?' ) === false ) $st['ymkrf_ep'] = $path . ' のページ';
	}
	return $st;
}, 20, 2 );

/* 「このページだけのCSS」（inc/functions-partner.php の欄）を、このページたちでも読みこみます */
add_action( 'wp_head', function () {
	foreach ( ymkrf_ep_defs() as $key => $d ) {
		if ( ! get_query_var( $d['qv'] ) ) continue;
		$id  = ymkrf_ep_id( $key );
		$css = $id ? (string) get_post_meta( $id, '_ymkrf_page_css', true ) : '';
		if ( trim( $css ) !== '' ) echo "\n<style id=\"ymkrf-thispage-css\">\n" . str_ireplace( '</style', '', $css ) . "\n</style>\n"; // phpcs:ignore
		return;
	}
}, 100 );

/* WordPressが最初に作った「プライバシーポリシー」（/privacy-policy/）は下書きにして、
   本物の /privacy/ へ送ります（2026/11/03。固定ページの一覧で、URLの出ないプライバシーポリシーが2つ並んでいたため）。
   設定 → プライバシー の「プライバシーポリシーページ」も、/privacy/ の固定ページにしてあります。 */
add_action( 'template_redirect', function () {
	$path = trim( (string) parse_url( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH ), '/' );
	$base = trim( (string) parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	if ( $base !== '' && strpos( $path, $base . '/' ) === 0 ) $path = substr( $path, strlen( $base ) + 1 );
	if ( $path === 'privacy-policy' ) { wp_redirect( home_url( '/privacy/' ), 301 ); exit; }
}, 1 );
