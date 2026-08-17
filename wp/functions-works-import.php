<?php
/**
 * functions-works-import.php ─ 施工事例の取り込み
 * 置き場所： wp-content/themes/ymkrf/inc/functions-works-import.php
 *
 * いまの本番サイト（yamakishi-reform.jp）の施工事例ページのURLを貼ると、
 * 中身をそのまま新しいワードプレスに取り込みます。
 *
 *   ・題名／リフォーム内容／担当店舗／現場住所（エリア）
 *   ・工事費／工期／完工時期／商品仕様／お客様のことば
 *   ・Before写真・After写真（メディアに保存します）
 *
 * 取り込んだものは「下書き」になります。
 * かならず中身を見て、直してから公開してください。
 *
 * ※ この画面は管理者だけが使えます。
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'YMKRF_IMPORT_HOST', 'yamakishi-reform.jp' );


/* ============================================================
   1. 取り込みの画面
   ============================================================ */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=ymkrf_works',
		'いまのサイトから取り込む', 'いまのサイトから取り込む',
		'manage_options', 'ymkrf-works-import', 'ymkrf_works_import_page'
	);
} );

function ymkrf_works_import_page() {
	if ( ! current_user_can( 'manage_options' ) ) return;

	$done = array();
	if ( isset( $_POST['ymkrf_import_nonce'] ) && wp_verify_nonce( $_POST['ymkrf_import_nonce'], 'ymkrf_import' ) ) {
		$lines = isset( $_POST['ymkrf_import_list'] ) ? (string) wp_unslash( $_POST['ymkrf_import_list'] ) : '';
		foreach ( preg_split( '/\R/u', $lines ) as $line ) {
			$line = trim( $line );
			if ( $line === '' ) continue;

			/* 「URL	案件番号」または「URL,案件番号」 */
			$cols = preg_split( '/[\t,、\s]+/u', $line );
			$url  = isset( $cols[0] ) ? $cols[0] : '';
			$no   = isset( $cols[1] ) ? $cols[1] : '';
			$done[] = ymkrf_works_import_one( $url, $no );
		}
	}
	?>
	<div class="wrap">
	  <h1>いまのサイトから施工事例を取り込む</h1>

	  <?php if ( $done ) : ?>
	    <h2>取り込みの結果</h2>
	    <table class="widefat striped" style="max-width:1000px">
	      <thead><tr><th style="width:60px">結果</th><th>内容</th></tr></thead>
	      <tbody>
	      <?php foreach ( $done as $r ) : ?>
	        <tr>
	          <td><?php echo $r['ok']
	            ? '<span style="color:#118a3d;font-weight:700">OK</span>'
	            : '<span style="color:#c00;font-weight:700">NG</span>'; ?></td>
	          <td><?php echo wp_kses_post( $r['msg'] ); ?></td>
	        </tr>
	      <?php endforeach; ?>
	      </tbody>
	    </table>
	    <hr>
	  <?php endif; ?>

	  <p style="max-width:900px;line-height:2">
	    いまのサイトの施工事例ページのURLと、案件番号を貼ってください。<br>
	    <b>1行に1件</b>です。URLと案件番号のあいだは、タブ・カンマ・スペースのどれでもかまいません。<br>
	    案件番号が分からないときは、URLだけでも取り込めます（あとから入力できます）。
	  </p>

	  <form method="post">
	    <?php wp_nonce_field( 'ymkrf_import', 'ymkrf_import_nonce' ); ?>
	    <textarea name="ymkrf_import_list" rows="10" style="width:100%;max-width:900px;font-family:monospace"
placeholder="https://yamakishi-reform.jp/works/kitchen/3332/&#10;2601-0395"></textarea>
	    <p class="description" style="max-width:900px">
	      例）<code>https://yamakishi-reform.jp/works/kitchen/3332/　2601-0395</code>
	    </p>
	    <?php submit_button( '取り込む' ); ?>
	  </form>

	  <h2>取り込むときの決まり</h2>
	  <ul style="max-width:900px;line-height:2;list-style:disc;padding-left:1.4em">
	    <li>取り込んだ事例は<b>下書き</b>になります。中身を見て、直してから公開してください。</li>
	    <li>写真は Before・After とも<b>5枚まで</b>取り込みます。
	        1枚目が代表になり、2枚目からは見くらべスライダーの下に小さく並びます。</li>
	    <li>同じURLをもう一度取り込むと、<b>前に取り込んだものを上書き</b>します（増えません）。</li>
	    <li>お客様のことばは本文には入れません。<b>お客様の声</b>のほうに、同じ案件番号で登録してください。</li>
	    <li>取り込めるのは <?php echo esc_html( YMKRF_IMPORT_HOST ); ?> のページだけです。</li>
	  </ul>
	</div>
	<?php
}


/* ============================================================
   2. 1件ぶんの取り込み
   ============================================================ */
function ymkrf_works_import_one( $url, $case_no = '' ) {

	$url = esc_url_raw( trim( $url ) );
	$bad = function ( $m ) use ( $url ) {
		return array( 'ok' => false, 'msg' => esc_html( $url ) . ' … ' . $m );
	};

	if ( $url === '' ) return $bad( 'URLが読み取れませんでした' );
	$host = parse_url( $url, PHP_URL_HOST );
	if ( $host !== YMKRF_IMPORT_HOST ) return $bad( 'このサイトのURLではありません' );

	$res = wp_remote_get( $url, array( 'timeout' => 30, 'redirection' => 3 ) );
	if ( is_wp_error( $res ) ) return $bad( '開けませんでした（' . $res->get_error_message() . '）' );
	if ( wp_remote_retrieve_response_code( $res ) !== 200 ) {
		return $bad( '開けませんでした（' . wp_remote_retrieve_response_code( $res ) . '）' );
	}

	$html = wp_remote_retrieve_body( $res );
	$d    = ymkrf_works_import_parse( $html, $url );
	if ( ! $d ) return $bad( '中身を読み取れませんでした' );

	/* すでに取り込んだものがあれば、それを更新します */
	$exist = get_posts( array(
		'post_type' => 'ymkrf_works', 'posts_per_page' => 1, 'fields' => 'ids',
		'post_status' => 'any', 'meta_key' => '_ymkrf_src_url', 'meta_value' => $url,
	) );

	$post = array(
		'post_type'    => 'ymkrf_works',
		/* 題名は保存のときに「野々市市｜キッチンリフォームの施工事例（Y様）」の
		   形に自動でととのえます（inc/functions-works.php の 2-2） */
		'post_title'   => $d['title'],
		'post_content' => $d['body'],
		'post_status'  => 'draft',
	);
	if ( $exist ) {
		$post['ID'] = (int) $exist[0];
		$id = wp_update_post( $post, true );
	} else {
		$id = wp_insert_post( $post, true );
	}
	if ( is_wp_error( $id ) ) return $bad( '保存できませんでした（' . $id->get_error_message() . '）' );

	update_post_meta( $id, '_ymkrf_src_url', $url );
	if ( $case_no !== '' ) update_post_meta( $id, '_ymkrf_case_no', sanitize_text_field( $case_no ) );
	if ( $d['price'] )  update_post_meta( $id, '_ymkrf_price',  $d['price'] );
	if ( $d['period'] ) update_post_meta( $id, '_ymkrf_period', $d['period'] );
	if ( $d['done'] )   update_post_meta( $id, '_ymkrf_done',   $d['done'] );
	if ( $d['shop'] )   update_post_meta( $id, '_ymkrf_shop',   $d['shop'] );
	if ( $d['initial'] ) update_post_meta( $id, '_ymkrf_initial', $d['initial'] );
	if ( $d['spec'] )   update_post_meta( $id, '_ymkrf_product_text', $d['spec'] );

	/* おこなった工事（1行に1つ）。編集画面の「おこなった工事」の欄に入ります */
	if ( ! empty( $d['items'] ) ) {
		update_post_meta( $id, '_ymkrf_work_items', implode( "\n", (array) $d['items'] ) );
	}

	if ( $d['cat'] )  wp_set_object_terms( $id, $d['cat'], 'ymkrf_works_cat' );
	if ( $d['area'] ) wp_set_object_terms( $id, $d['area'], 'ymkrf_works_area' );

	/* 写真 */
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	/* Before・Afterとも、5枚まで取り込みます。
	   1枚目が代表（Before写真／アイキャッチ画像）になります。 */
	$n_img = 0;
	foreach ( array( 'before', 'after' ) as $which ) {
		$ids = array();
		foreach ( array_slice( (array) $d[ $which ], 0, YMKRF_PHOTO_MAX ) as $u ) {
			$mid = ymkrf_works_import_image( $u, $id, ( $which === 'before' ? '施工前' : '施工後' ) );
			if ( $mid ) { $ids[] = $mid; $n_img++; }
		}
		if ( $ids ) ymkrf_works_photos_save( $id, $which, $ids );
	}

	/* 題名は、エリア・部位・頭文字がそろってから組み立てます
	   （取り込みの途中では、まだ分類が付いていないため） */
	$title = ymkrf_works_auto_title( $id );
	if ( $title !== get_post_field( 'post_title', $id ) ) {
		update_post_meta( $id, '_ymkrf_auto_title', $title );
		wp_update_post( array( 'ID' => $id, 'post_title' => $title ) );
	}

	$link = '<a href="' . esc_url( get_edit_post_link( $id ) ) . '">'
	      . esc_html( get_post_field( 'post_title', $id ) ) . '</a>';
	return array( 'ok' => true, 'msg' => $link
		. '（下書き／写真' . $n_img . '枚'
		. ( $exist ? '／上書き' : '' ) . '）' );
}


/* ============================================================
   3. ページの中身を読み取る
   ============================================================ */
function ymkrf_works_import_parse( $html, $url ) {

	$prev = libxml_use_internal_errors( true );
	$doc  = new DOMDocument();
	$doc->loadHTML( '<?xml encoding="UTF-8">' . $html );
	libxml_clear_errors();
	libxml_use_internal_errors( $prev );
	$xp = new DOMXPath( $doc );

	$txt = function ( $node ) {
		return trim( preg_replace( '/[ \t\x{3000}]+/u', ' ',
			preg_replace( '/\s*\R\s*/u', "\n", (string) $node->textContent ) ) );
	};

	/* 題名（例：キッチンリフォーム　Y様（野々市）） */
	$title = '';
	$h2 = $xp->query( '//h2[contains(@class,"ip-heading")]' );
	if ( $h2->length ) $title = $txt( $h2->item( 0 ) );
	if ( $title === '' ) return null;

	/* 題名から、お客様の頭文字（例：Y様 → Y）を取り出します */
	$initial = '';
	if ( preg_match( '/([A-Za-zＡ-Ｚａ-ｚ])\s*様/u', $title, $m ) ) {
		$initial = mb_convert_kana( $m[1], 'a', 'UTF-8' );
		$initial = strtoupper( $initial );
	}

	/* h4.shd のラベルと、その次の要素の中身 */
	$vals = array();
	foreach ( $xp->query( '//h4[contains(@class,"shd")]' ) as $h ) {
		$label = $txt( $h );
		$node  = $h->nextSibling;
		while ( $node && $node->nodeType !== XML_ELEMENT_NODE ) $node = $node->nextSibling;
		$vals[ $label ] = $node ? $txt( $node ) : '';
	}
	$get = function ( $k ) use ( $vals ) { return isset( $vals[ $k ] ) ? $vals[ $k ] : ''; };

	/* 工事費（100 万円 → 100万円） */
	$price = preg_replace( '/\s+/u', '', $get( 'リフォーム金額' ) );

	/* 部位（URLの /works/kitchen/ から） */
	$cat = '';
	if ( preg_match( '#/works/([a-z0-9-]+)/#', $url, $m ) ) {
		$t = get_term_by( 'slug', $m[1], 'ymkrf_works_cat' );
		if ( $t && ! is_wp_error( $t ) ) $cat = $t->slug;
	}

	/* エリア（現場住所から。無ければ作ります） */
	$area = '';
	$addr = trim( $get( '現場住所' ) );
	if ( $addr !== '' ) {
		$name = ymkrf_works_import_city( $addr );
		$t = get_term_by( 'name', $name, 'ymkrf_works_area' );
		if ( ! $t || is_wp_error( $t ) ) {
			$r = wp_insert_term( $name, 'ymkrf_works_area' );
			if ( ! is_wp_error( $r ) ) $t = get_term( $r['term_id'], 'ymkrf_works_area' );
		}
		if ( $t && ! is_wp_error( $t ) ) $area = $t->slug;
	}

	/* 担当店舗（金沢野々市店 → nonoichi） */
	$shop = '';
	$sname = trim( $get( '担当店舗' ) );
	if ( $sname !== '' ) {
		$t = get_term_by( 'name', $sname, 'ymkrf_shop' );
		if ( $t && ! is_wp_error( $t ) ) $shop = $t->slug;
	}

	/* 本文（リフォーム内容の箇条書き＋工事内容＋商品仕様） */
	$body = '';
	$items = array();
	foreach ( $xp->query( '//h4[contains(@class,"shd")]' ) as $h ) {
		if ( $txt( $h ) !== 'リフォーム内容' ) continue;
		$node = $h->nextSibling;
		while ( $node && $node->nodeType !== XML_ELEMENT_NODE ) $node = $node->nextSibling;
		if ( $node ) foreach ( $node->getElementsByTagName( 'li' ) as $li ) {
			$v = $txt( $li );
			if ( $v !== '' ) $items[] = $v;
		}
	}
	/* 箇条書きは本文には入れず、「おこなった工事」の欄に入れます */
	/* 商品仕様は本文に入れず、「使った商品」の欄に入れます */
	$spec = trim( preg_replace( '/\s+/u', ' ', $get( '商品仕様' ) ) );

	/* 写真（原寸のリンク先を使います）

	   囲みの div は class="before-after-box" で、その中に
	   class="before" と class="after" の div があります。
	   「before」で探すと外側の箱まで当たってしまうので、
	   class がちょうど before / after のものだけを見ます。 */
	$pick = function ( $cls ) use ( $xp, $url ) {
		$out = array();
		$q = '//div[contains(concat(" ",normalize-space(@class)," ")," ' . $cls . ' ")]//a[@href]';
		foreach ( $xp->query( $q ) as $a ) {
			$h = $a->getAttribute( 'href' );
			if ( strpos( $h, '/uploads/' ) === false ) continue;
			if ( strpos( $h, 'http' ) !== 0 ) $h = 'https://' . YMKRF_IMPORT_HOST . $h;
			if ( ! in_array( $h, $out, true ) ) $out[] = $h;
		}
		return $out;
	};

	return array(
		'title'  => $title,
		'body'   => trim( $body ),
		'items'  => $items,
		'price'  => $price,
		'period' => trim( $get( '工期' ) ),
		'done'   => trim( $get( '完工時期' ) ),
		'shop'   => $shop,
		'cat'    => $cat,
		'area'   => $area,
		'initial'=> $initial,
		'spec'   => $spec,
		'before' => $pick( 'before' ),
		'after'  => $pick( 'after' ),
	);
}

/** 「野々市」→「野々市市」のように、市町の形にそろえます */
function ymkrf_works_import_city( $addr ) {
	$a = trim( preg_replace( '/\s+/u', '', $addr ) );
	if ( $a === '' ) return '';

	/* 「野々市」は市の名前が「野々市市」なので、
	   市・町で終わっているかを見るより先に、この表で照合します。 */
	$known = array(
		'野々市' => '野々市市', '金沢' => '金沢市', '白山' => '白山市', '能美' => '能美市',
		'小松'   => '小松市',   '加賀' => '加賀市', '川北' => '川北町', '津幡' => '津幡町',
		'内灘'   => '内灘町',   'かほく' => 'かほく市', '羽咋' => '羽咋市', '七尾' => '七尾市',
		'志賀'   => '志賀町',   '宝達志水' => '宝達志水町', '中能登' => '中能登町',
		'輪島'   => '輪島市',   '珠洲' => '珠洲市', '穴水' => '穴水町', '能登' => '能登町',
		'福井'   => '福井市',   'あわら' => 'あわら市', '坂井' => '坂井市', '勝山' => '勝山市',
		'大野'   => '大野市',   '永平寺' => '永平寺町', '鯖江' => '鯖江市', '越前' => '越前市',
		'敦賀'   => '敦賀市',   '小浜' => '小浜市', '池田' => '池田町',
	);
	if ( isset( $known[ $a ] ) ) return $known[ $a ];

	/* すでに 市・町・村・郡 で終わっていれば、そのまま */
	if ( preg_match( '/(市|町|村|郡)$/u', $a ) ) return $a;

	return $a;
}

/** 写真を1枚、メディアに取り込みます */
function ymkrf_works_import_image( $url, $post_id, $desc = '' ) {
	/* もう取り込んである写真は、使いまわします */
	$found = get_posts( array(
		'post_type' => 'attachment', 'posts_per_page' => 1, 'fields' => 'ids',
		'post_status' => 'inherit', 'meta_key' => '_ymkrf_src_url', 'meta_value' => $url,
	) );
	if ( $found ) return (int) $found[0];

	$tmp = download_url( $url, 60 );
	if ( is_wp_error( $tmp ) ) return 0;

	/* 拡張子が無いURLなので、中身を見て決めます */
	$info = @getimagesize( $tmp );
	$ext  = 'jpg';
	if ( $info && isset( $info['mime'] ) ) {
		if ( $info['mime'] === 'image/png' )  $ext = 'png';
		if ( $info['mime'] === 'image/webp' ) $ext = 'webp';
		if ( $info['mime'] === 'image/gif' )  $ext = 'gif';
	}
	$name = 'works-' . (int) $post_id . '-' . substr( md5( $url ), 0, 8 ) . '.' . $ext;

	$file = array( 'name' => $name, 'tmp_name' => $tmp );
	$id   = media_handle_sideload( $file, $post_id, $desc );
	if ( is_wp_error( $id ) ) { @unlink( $tmp ); return 0; }

	update_post_meta( $id, '_ymkrf_src_url', $url );
	return (int) $id;
}
