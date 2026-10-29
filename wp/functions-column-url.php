<?php
/**
 * コラムのURLを、旧ホームページのブログと同じ形にします（2026/10/29）
 *
 * ユーザー指示
 *   「結構バズっているブログもあるのでURLは変えたくない」
 *   「新しく書く記事も /blog/entry/番号（旧ブログの続き）」
 *   「https://yamakishi-reform.jp/blog/ こちらのURLと合わせてください」
 *   「130は、新サイトのコラムのこちらと同じ内容です（アラウーノのV・S・L）」
 *   「旧ブログと新コラム、同じ内容のものは同じURLにしてほしい。
 *     下書きになっているものはまだURLをつけないでほしい。」
 *
 * URL（旧ブログとまったく同じ形）
 *   記事（旧ブログにもある）  /blog/entry/90   ← 最後に「/」は付けません（旧ブログと同じ）
 *   記事（新サイトで書いた）  /column/131      ← 2026/10/29 ユーザー「131と132、またこれ以降に書くコラムは column で」
 *   一覧       /blog/
 *   カテゴリ   /blog/category/キッチン/
 *
 * 番号（記事ごとのメタ _ymkrf_blog_no）
 *   - 旧ブログから取り込んだ記事 … 旧ブログの番号そのまま（_ymkrf_old_blog_id から写す）
 *   - 旧ブログにも同じ記事がある新しいコラム … 旧ブログと同じ番号（編集画面の右で入れられます）
 *   - 新しいコラム … 「公開」したときに、/column/ の次の番号（131 からの続き）を自動で付けます（メタ _ymkrf_col_no）
 *   - 下書き・非公開・予約 … 番号を付けません（URLなし）
 *   - 一度付いた番号は、下書きにもどしても消しません（同じURLをほかの記事が使わないように）
 *
 * 転送（301）
 *   /column/ → /blog/、/column/cat/英字/ → /blog/category/名前/、
 *   /column/<スラッグ>/ → /blog/entry/<番号>、/blog/entry/90/（「/」あり）→ /blog/entry/90、
 *   /blog/?page=2 → /blog/page/2/、/blog/2026/09/（月ごと）→ /blog/、
 *   旧ブログだけにあったカテゴリ（その他・補助金・イベント・水回り・修理・小工事）→ /blog/
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'YMKRF_BLOGNO', '_ymkrf_blog_no' );

/* ============================================================
   0. カテゴリの名前（旧ブログと同じ日本語）
   ============================================================ */

/** 英字 → URLに使う日本語の名前（旧ブログの名前を優先） */
function ymkrf_column_cat_urlname( $slug ) {
	$old = array(
		'kitchen'    => 'キッチン',
		'bathroom'   => 'お風呂',
		'toilet'     => 'トイレ',
		'lavatory'   => '洗面化粧台',
		'ecocute'    => 'エコキュート',
		'boiler'     => 'オール電化・給湯器',
		'interior'   => '内装・増改築',
		'outer-wall' => '外壁・外装',
		'exterior'   => 'エクステリア',
	);
	if ( isset( $old[ $slug ] ) ) return $old[ $slug ];
	$t = get_term_by( 'slug', $slug, 'ymkrf_product_cat' );
	return ( $t && ! is_wp_error( $t ) ) ? $t->name : $slug;
}

/** 日本語の名前（または英字）→ 英字。わからなければ空 */
function ymkrf_column_cat_from_name( $name ) {
	$name = trim( rawurldecode( (string) $name ) );
	if ( $name === '' ) return '';
	foreach ( array( 'kitchen', 'bathroom', 'toilet', 'lavatory', 'ecocute', 'boiler', 'interior', 'outer-wall', 'exterior' ) as $en ) {
		if ( ymkrf_column_cat_urlname( $en ) === $name ) return $en;
	}
	$t = get_term_by( 'slug', sanitize_title( $name ), 'ymkrf_product_cat' );
	if ( $t && ! is_wp_error( $t ) ) return $t->slug;
	$t = get_term_by( 'name', $name, 'ymkrf_product_cat' );
	if ( $t && ! is_wp_error( $t ) ) return $t->slug;
	return '';
}


/* ============================================================
   1. 番号
   ------------------------------------------------------------
   番号は2種類（2026/10/29 ユーザー「非公開と下書き、131と132、またこれ以降に書くコラムは、
   column でエントリーナンバーのURLにして下さい」）
     _ymkrf_blog_no … 旧ブログにある記事 → /blog/entry/90（取り込んだ記事と 130）
     _ymkrf_col_no  … 新サイトで書いた記事 → /column/131
   どちらも最後に「/」は付けません。
   ============================================================ */
define( 'YMKRF_COLNO', '_ymkrf_col_no' );

/** 旧ブログの番号（無ければ 0） */
function ymkrf_column_no( $post_id ) {
	return (int) get_post_meta( $post_id, YMKRF_BLOGNO, true );
}
/** 新サイトの番号（無ければ 0） */
function ymkrf_column_colno( $post_id ) {
	return (int) get_post_meta( $post_id, YMKRF_COLNO, true );
}

/** 番号 → 記事ID（無ければ 0）。$key は YMKRF_BLOGNO か YMKRF_COLNO */
function ymkrf_column_find( $no, $key = YMKRF_BLOGNO ) {
	$no = (int) $no;
	if ( $no < 1 ) return 0;
	$ids = get_posts( array(
		'post_type' => 'ymkrf_column', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids',
		'meta_key' => $key, 'meta_value' => (string) $no, 'suppress_filters' => true,
		'orderby' => 'ID', 'order' => 'ASC',
	) );
	return $ids ? (int) $ids[0] : 0;
}
function ymkrf_column_by_no( $no ) { return ymkrf_column_find( $no, YMKRF_BLOGNO ); }

/** いちばん大きい番号 */
function ymkrf_column_max( $key ) {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT MAX( CAST( meta_value AS UNSIGNED ) ) FROM {$wpdb->postmeta} WHERE meta_key = %s", $key ) );
}
/** 次の新サイトの番号（旧ブログの続き＝131 から） */
function ymkrf_column_next_colno() {
	return max( ymkrf_column_max( YMKRF_COLNO ), 130 ) + 1;
}

/** 公開した記事に番号を付けます（もう付いていれば何もしません） */
function ymkrf_column_give_no( $post_id ) {
	if ( ymkrf_column_no( $post_id ) || ymkrf_column_colno( $post_id ) ) return;
	$old = (int) get_post_meta( $post_id, '_ymkrf_old_blog_id', true );   /* 旧ブログから取り込んだ記事 */
	if ( $old && ! ymkrf_column_by_no( $old ) ) {
		update_post_meta( $post_id, YMKRF_BLOGNO, (string) $old );
		return;
	}
	update_post_meta( $post_id, YMKRF_COLNO, (string) ymkrf_column_next_colno() );
}

/* 「公開」になったとき（予約投稿が公開されたときもふくむ）。下書き・非公開には付けません */
add_action( 'transition_post_status', function ( $new, $old, $post ) {
	if ( $new !== 'publish' || ! $post || $post->post_type !== 'ymkrf_column' ) return;
	ymkrf_column_give_no( $post->ID );
}, 5, 3 );

/* 1回だけ（v1）：いまある公開中の記事に番号を付けました（旧ブログの番号・130＝アラウーノ）
   1回だけ（v2）：新サイトで書いた 131・132・133 を /column/ の番号に移します */
add_action( 'init', function () {
	if ( ! post_type_exists( 'ymkrf_column' ) ) return;

	if ( get_option( 'ymkrf_blogno_v1' ) !== '1' ) {
		foreach ( get_posts( array( 'post_type' => 'ymkrf_column', 'post_status' => 'publish', 'posts_per_page' => -1,
		                            'fields' => 'ids', 'suppress_filters' => true ) ) as $id ) {
			$old = (int) get_post_meta( $id, '_ymkrf_old_blog_id', true );
			if ( $old && ! ymkrf_column_no( $id ) && ! ymkrf_column_by_no( $old ) ) update_post_meta( $id, YMKRF_BLOGNO, (string) $old );
		}
		$p = get_post( 19794 );
		if ( $p && $p->post_status === 'publish' && strpos( $p->post_title, 'アラウーノ' ) !== false && ! ymkrf_column_by_no( 130 ) ) {
			update_post_meta( 19794, YMKRF_BLOGNO, '130' );
		}
		update_option( 'ymkrf_blogno_v1', '1', false );
	}

	if ( get_option( 'ymkrf_colno_v2' ) !== '1' ) {
		$log = array();
		foreach ( get_posts( array( 'post_type' => 'ymkrf_column', 'post_status' => 'any', 'posts_per_page' => -1,
		                            'fields' => 'ids', 'suppress_filters' => true,
		                            'meta_key' => YMKRF_BLOGNO ) ) as $id ) {
			$no = ymkrf_column_no( $id );
			if ( (int) get_post_meta( $id, '_ymkrf_old_blog_id', true ) ) continue;   /* 取り込んだ記事はそのまま */
			if ( $no === 130 && (int) $id === 19794 ) continue;                    /* 旧ブログにもある記事 */
			if ( ymkrf_column_find( $no, YMKRF_COLNO ) ) continue;
			update_post_meta( $id, YMKRF_COLNO, (string) $no );   /* 同じ番号のまま /column/ へ */
			delete_post_meta( $id, YMKRF_BLOGNO );
			$log[] = $id . '：/blog/entry/' . $no . ' → /column/' . $no;
		}
		update_option( 'ymkrf_colno_log', $log, false );
		update_option( 'ymkrf_colno_v2', '1', false );
	}
}, 30 );


/* ============================================================
   2. URL
   ============================================================ */

/** その記事のURLの道（/blog/entry/90 か /column/131。まだ無ければ空） */
function ymkrf_column_path( $post_id ) {
	$b = ymkrf_column_no( $post_id );
	if ( $b ) return '/blog/entry/' . $b;
	$c = ymkrf_column_colno( $post_id );
	if ( $c ) return '/column/' . $c;
	return '';
}

/* 番号が無い（＝まだ公開していない）記事は、WordPress のままのURL（プレビュー用） */
add_filter( 'post_type_link', function ( $url, $post ) {
	if ( ! $post || $post->post_type !== 'ymkrf_column' ) return $url;
	$path = ymkrf_column_path( $post->ID );
	return $path !== '' ? home_url( $path ) : $url;
}, 20, 2 );

add_filter( 'query_vars', function ( $v ) { $v[] = 'ymkrf_blogno'; $v[] = 'ymkrf_colno'; return $v; } );

add_action( 'init', function () {
	add_rewrite_rule( '^blog/entry/([0-9]+)/?$', 'index.php?ymkrf_blogno=$matches[1]', 'top' );
	add_rewrite_rule( '^column/([0-9]+)/?$', 'index.php?ymkrf_colno=$matches[1]', 'top' );
}, 21 );

/* 番号 → 記事 */
add_filter( 'request', function ( $vars ) {
	if ( ! empty( $vars['ymkrf_blogno'] ) ) {
		$id = ymkrf_column_by_no( $vars['ymkrf_blogno'] );
		if ( ! $id ) return array( 'error' => '404' );
		return array( 'post_type' => 'ymkrf_column', 'p' => $id, 'ymkrf_blogno' => $vars['ymkrf_blogno'] );
	}
	if ( ! empty( $vars['ymkrf_colno'] ) ) {
		$id = ymkrf_column_find( $vars['ymkrf_colno'], YMKRF_COLNO );
		if ( ! $id ) return array( 'error' => '404' );
		return array( 'post_type' => 'ymkrf_column', 'p' => $id, 'ymkrf_colno' => $vars['ymkrf_colno'] );
	}
	return $vars;
} );

/* WordPress が最後に「/」を付け足して転送しないようにします */
add_filter( 'redirect_canonical', function ( $to ) {
	if ( get_query_var( 'ymkrf_blogno' ) || get_query_var( 'ymkrf_colno' ) ) return false;
	return $to;
} );


/* ============================================================
   3. 転送（301）
   ============================================================ */
add_action( 'template_redirect', function () {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	$path = (string) parse_url( $uri, PHP_URL_PATH );
	$base = rtrim( (string) parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	if ( $base !== '' && strpos( $path, $base ) === 0 ) $path = substr( $path, strlen( $base ) );
	$path = '/' . ltrim( rawurldecode( $path ), '/' );
	$blog = home_url( '/blog/' );
	$go   = '';

	if ( preg_match( '#^/(blog/entry|column)/([0-9]+)/$#', $path, $m ) ) {
		$go = home_url( '/' . $m[1] . '/' . $m[2] );                                /* 「/」あり → なし */
	} elseif ( preg_match( '#^/blog/?$#', $path ) && isset( $_GET['page'] ) ) {
		$n  = (int) $_GET['page'];                                                  /* 旧ブログのページ送り */
		$go = $n > 1 ? $blog . 'page/' . $n . '/' : $blog;
	} elseif ( preg_match( '#^/blog/[0-9]{4}/[0-9]{1,2}/?#', $path ) ) {
		$go = $blog;                                                                /* 月ごとの一覧 */
	} elseif ( preg_match( '#^/blog/category/([^/]+)/?#', $path, $m ) && ymkrf_column_cat_from_name( $m[1] ) === '' ) {
		$go = $blog;                                                                /* 旧ブログだけのカテゴリ */
	} elseif ( preg_match( '#^/column/?$#', $path ) ) {
		$go = $blog;
	} elseif ( preg_match( '#^/column/page/([0-9]+)/?$#', $path, $m ) ) {
		$go = $blog . 'page/' . (int) $m[1] . '/';
	} elseif ( preg_match( '#^/column/cat/([^/]+)/?(page/([0-9]+)/?)?$#', $path, $m ) ) {
		$go = ymkrf_column_cat_link( sanitize_title( $m[1] ) );
		if ( ! empty( $m[3] ) && (int) $m[3] > 1 ) $go .= 'page/' . (int) $m[3] . '/';
	} elseif ( is_singular( 'ymkrf_column' ) ) {
		/* そのほかのURL（/column/<スラッグ>/ など）で来たら、番号のURLへ */
		$id  = get_queried_object_id();
		$own = ymkrf_column_path( $id );
		if ( $own !== '' && get_post_status( $id ) === 'publish' && $path !== $own ) $go = home_url( $own );
	}

	if ( $go !== '' ) {
		wp_redirect( $go, 301 );
		exit;
	}
}, 1 );


/* ============================================================
   4. 本文の中の古いリンク（/column/<スラッグ>/）を、表示するときだけ新しいURLに
      （本文そのものは書きかえません）
   ============================================================ */
add_filter( 'the_content', function ( $html ) {
	if ( strpos( $html, '/column/' ) === false ) return $html;
	$home = untrailingslashit( home_url() );
	return preg_replace_callback( '#href="(' . preg_quote( $home, '#' ) . ')?/column/([^/"?\#]+)/?"#', function ( $m ) {
		static $cache = array();
		$slug = $m[2];
		if ( $slug === 'cat' || $slug === 'page' || ctype_digit( $slug ) ) return $m[0];
		if ( ! isset( $cache[ $slug ] ) ) {
			$p = get_page_by_path( $slug, OBJECT, 'ymkrf_column' );
			if ( ! $p ) $p = get_page_by_path( rawurldecode( $slug ), OBJECT, 'ymkrf_column' );
			$cache[ $slug ] = ( $p && ymkrf_column_path( $p->ID ) !== '' ) ? get_permalink( $p ) : '';
		}
		return $cache[ $slug ] !== '' ? 'href="' . esc_url( $cache[ $slug ] ) . '"' : $m[0];
	}, $html );
}, 20 );


/* ============================================================
   5. 編集画面：URL
   ============================================================ */
add_action( 'add_meta_boxes_ymkrf_column', function () {
	add_meta_box( 'ymkrf_blogno', 'URL', function ( $post ) {
		$path = ymkrf_column_path( $post->ID );
		$no   = ymkrf_column_no( $post->ID );
		wp_nonce_field( 'ymkrf_blogno', 'ymkrf_blogno_nonce' );
		if ( $path !== '' ) {
			echo '<p style="margin-top:0"><span class="ymkrf-urlline__lab">URL</span><a href="' . esc_url( home_url( $path ) ) . '" target="_blank" rel="noopener">' . esc_html( home_url( $path ) ) . '</a></p>';
		} else {
			echo '<p style="margin-top:0"><span class="ymkrf-urlline__lab">URL</span><b>まだありません。</b>「公開」すると <code>' . esc_html( home_url( '/column/' . (int) ymkrf_column_next_colno() ) ) . '</code> が自動で付きます。</p>';
		}
		if ( current_user_can( 'manage_options' ) ) {
			echo '<details' . ( $no ? ' open' : '' ) . '><summary style="cursor:pointer">旧ブログにも同じ記事があるとき</summary>';
			echo '<p style="margin-bottom:4px"><label for="ymkrf-blogno">旧ブログの番号（/blog/entry/<b>130</b> の数字）</label></p>';
			echo '<input type="number" min="1" step="1" id="ymkrf-blogno" name="ymkrf_blogno" value="' . ( $no ? (int) $no : '' ) . '" style="width:110px" placeholder="例：130">';
			echo '<p class="description">入れると、URLが旧ブログと同じ <code>/blog/entry/番号</code> になります。<br>★公開したあとに変えるとURLが変わります。ふつうはさわらないでください。</p></details>';
		}
	}, 'ymkrf_column', 'side', 'low' );   /* 執筆者より下に（2026/10/29 ユーザー「執筆者、見当たらない」） */
} );

/* 執筆者の欄を、右側のいちばん上に出します（2026/10/29 ユーザー「コラム、執筆者入力する欄ある?」「見当たらないんだけど」）。
   「公開」の枠が長いので、その下にあると画面の外に出てしまっていました */
add_action( 'admin_footer-post.php', 'ymkrf_column_writer_top' );
add_action( 'admin_footer-post-new.php', 'ymkrf_column_writer_top' );
function ymkrf_column_writer_top() {
	$s = get_current_screen();
	if ( ! $s || $s->post_type !== 'ymkrf_column' ) return;
	/* 商品カテゴリ … 「関連する商品」のすぐ上へ／リビジョン … いちばん下へ
	   （2026/10/29 ユーザー「商品カテゴリを選ぶところ、関連する商品の上に置いて。リビジョンは一番下へ」） */
	echo '<style>#edit-slug-box{display:none!important}#ymkrf-urlline{margin:8px 2px 14px;padding:8px 12px;background:#fff;border:1px solid #dcdcde;border-left:4px solid #fe3301;border-radius:4px;font-size:13px}'
		. '#ymkrf-urlline p{margin:0 0 4px}#ymkrf-urlline p:last-child{margin-bottom:0}#ymkrf-urlline details{margin-top:4px}'
		. '#ymkrf-urlline .ymkrf-urlline__lab{color:#646970;font-weight:600;margin-right:6px}'
		/* 商品カテゴリ・エリアは、押しやすい札（チップ）にならべます
		   （2026/10/29 ユーザー「商品カテゴリ、間空きすぎて✔しにくい。エリアももっとなんかみやすくならない？」）。
		   札のどこを押してもチェックが入り、チェックした札はオレンジになります */
		. '#ymkrf_product_catdiv .tabs-panel,#ymkrf_works_areadiv .tabs-panel{max-height:none;border:0;padding:0;background:none}'
		. '#ymkrf_product_catdiv .category-tabs,#ymkrf_works_areadiv .category-tabs{display:none}'
		. '#ymkrf_product_catdiv .categorychecklist,#ymkrf_works_areadiv .categorychecklist ul.children{display:flex;flex-wrap:wrap;gap:6px;margin:0}'
		. '#ymkrf_product_catdiv .categorychecklist li,#ymkrf_works_areadiv .categorychecklist ul.children li{margin:0}'
		. '.ymkrf-chips label.selectit{display:inline-flex;align-items:center;gap:6px;padding:5px 12px 5px 9px;border:1px solid #c3c4c7;border-radius:999px;background:#fff;cursor:pointer;font-size:13px;line-height:1.4;user-select:none}'
		. '.ymkrf-chips label.selectit:hover{border-color:#fe3301}'
		. '.ymkrf-chips label.selectit input{margin:0}'
		. '.ymkrf-chips label.selectit:has(input:checked){background:#fff1ec;border-color:#fe3301;color:#c92800;font-weight:700}'
		. '#ymkrf_works_areadiv .categorychecklist{margin:0}'
		. '#ymkrf_works_areadiv .categorychecklist>li{margin:0 0 12px}'
		. '#ymkrf_works_areadiv .categorychecklist>li>label.selectit{border:0;background:none;padding:0 0 6px;font-weight:700;font-size:13.5px}'
		. '#ymkrf_works_areadiv .categorychecklist ul.children{margin:0;padding:10px 12px;background:#f6f7f7;border-radius:8px}'
		/* リビジョンは、エリアの枠くらいの高さで止めて、中をスクロール（2026/10/29 ユーザー指示） */
		. '#revisionsdiv .inside{max-height:240px;overflow:auto}'
		. '</style>';
	echo '<script>jQuery(function($){var $w=$("#ymkrf_column_writer");if($w.length){$("#side-sortables").prepend($w);$w.removeClass("closed");}'
		/* URL … 題名のすぐ下（パーマリンクの場所）へ。中身だけ移して、右の枠は消します
		   （2026/10/29 ユーザー「URLも左にあるが、パーマリンクの下に置いた方があちこち見なくて流れがスムーズ」）。
		   WordPress のパーマリンクの行は、番号のURLとちがう形を見せてしまうのでかくします */
		. 'var $u=$("#ymkrf_blogno");if($u.length){var $in=$u.find(".inside").children();'
		. 'var $line=$("<div id=\"ymkrf-urlline\"></div>").append($in);'
		. 'var $at=$("#edit-slug-box");if(!$at.length)$at=$("#titlewrap");$at.after($line);$u.hide();$("#ymkrf_blogno-hide").closest("label").hide();}'
		. 'var $c=$("#ymkrf_product_catdiv"),$p=$("#ymkrf_col_products");if($c.length&&$p.length){$p.before($c);$c.removeClass("closed");}'
		/* エリア … 「関係する商品」の下へ／リビジョン … 右のアイキャッチ画像の下へ
		   （2026/10/29 ユーザー「エリアも関連する商品の下において、なんだらリビジョンはアイキャッチの下に置いた方がよいかも」） */
		. 'var $a=$("#ymkrf_works_areadiv");if($a.length&&$p.length){$p.after($a);$a.removeClass("closed");}'
		. '$("#ymkrf_product_catdiv,#ymkrf_works_areadiv").addClass("ymkrf-chips");'
		/* 見出しの名前（コラムの画面だけ。商品・施工事例の画面はそのまま）
		   2026/10/29 ユーザー「商品カテゴリ→関連する商品カテゴリ／エリア→関連するエリア」 */
		. '$("#ymkrf_product_catdiv .hndle, label[for=ymkrf_product_catdiv-hide]").each(function(){var $t=$(this).contents().filter(function(){return this.nodeType===3&&$.trim(this.nodeValue)!=="";}).first();if($t.length)$t[0].nodeValue="関連する商品カテゴリ";else $(this).text("関連する商品カテゴリ");});'
		. '$("#ymkrf_works_areadiv .hndle, label[for=ymkrf_works_areadiv-hide]").each(function(){var $t=$(this).contents().filter(function(){return this.nodeType===3&&$.trim(this.nodeValue)!=="";}).first();if($t.length)$t[0].nodeValue="関連するエリア";else $(this).text("関連するエリア");});'
		. 'var $r=$("#revisionsdiv"),$i=$("#postimagediv");if($r.length){if($i.length){$i.after($r);}else{$("#side-sortables").append($r);}}'
		. '});</script>';
}

add_action( 'save_post_ymkrf_column', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;
	if ( ! isset( $_POST['ymkrf_blogno_nonce'] ) || ! wp_verify_nonce( $_POST['ymkrf_blogno_nonce'], 'ymkrf_blogno' ) ) return;
	if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['ymkrf_blogno'] ) ) return;

	$want = (int) $_POST['ymkrf_blogno'];
	$now  = ymkrf_column_no( $post_id );
	if ( $want < 1 || $want === $now ) return;
	$other = ymkrf_column_by_no( $want );
	if ( $other && $other !== (int) $post_id ) {
		set_transient( 'ymkrf_blogno_note_' . get_current_user_id(),
			'番号 ' . $want . ' は「' . get_the_title( $other ) . '」が使っています。番号は変えていません。', 60 );
		return;
	}
	update_post_meta( $post_id, YMKRF_BLOGNO, (string) $want );
	delete_post_meta( $post_id, YMKRF_COLNO );   /* 旧ブログの番号を入れたら、そちらのURLに */
}, 30 );

add_action( 'admin_notices', function () {
	$k = 'ymkrf_blogno_note_' . get_current_user_id();
	$n = get_transient( $k );
	if ( ! $n ) return;
	delete_transient( $k );
	echo '<div class="notice notice-error is-dismissible"><p><b>URLの番号：</b>' . esc_html( $n ) . '</p></div>';
} );

/* 一覧に「URL」の列（URLの末尾＝番号を大きく。押すとページがひらきます。
   2026/10/29 ユーザー「一覧にURL末尾表示してほしい」） */
add_filter( 'manage_ymkrf_column_posts_columns', function ( $cols ) {
	$new = array();
	foreach ( $cols as $k => $v ) {
		if ( $k === 'date' ) $new['ymkrf_blogno'] = 'URL';
		$new[ $k ] = $v;
	}
	if ( ! isset( $new['ymkrf_blogno'] ) ) $new['ymkrf_blogno'] = 'URL';
	return $new;
}, 40 );
add_action( 'manage_ymkrf_column_posts_custom_column', function ( $col, $post_id ) {
	if ( $col !== 'ymkrf_blogno' ) return;
	$path = ymkrf_column_path( $post_id );
	if ( $path === '' ) {
		echo '<span style="color:#a7aaad">まだなし<br><small>公開すると付きます</small></span>';
		return;
	}
	$pos = strrpos( $path, '/' );
	echo '<a class="ymkrf-blogno' . ( strpos( $path, '/column/' ) === 0 ? ' is-col' : '' ) . '" href="' . esc_url( home_url( $path ) ) . '" target="_blank" rel="noopener">'
		. '<small>' . esc_html( substr( $path, 0, $pos + 1 ) ) . '</small><b>' . esc_html( substr( $path, $pos + 1 ) ) . '</b></a>';
}, 10, 2 );
add_action( 'admin_head', function () {
	$s = get_current_screen();
	if ( ! $s || $s->id !== 'edit-ymkrf_column' ) return;
	echo '<style>.column-ymkrf_blogno{width:150px}
	.ymkrf-blogno{display:inline-flex;align-items:baseline;gap:1px;text-decoration:none;font-family:Consolas,monospace;
	  background:#f6f7f7;border:1px solid #dcdcde;border-radius:4px;padding:2px 8px}
	.ymkrf-blogno.is-col{background:#fff4ef;border-color:#fdc9b5}
	.ymkrf-blogno small{color:#646970;font-size:11.5px}
	.ymkrf-blogno b{font-size:16px;color:#c92800}</style>';
} );

/* 上の /blog/entry/・/column/番号 のURLを WordPress に覚えさせます（1回だけ） */
add_action( 'init', function () {
	if ( get_option( 'ymkrf_column_url_flush' ) === '2' ) return;
	flush_rewrite_rules( false );
	update_option( 'ymkrf_column_url_flush', '2', false );
}, 110 );

/* ホームページ側の一覧（/blog/・トップ・記事の下の「ほかのお役立ち情報」など）には、
   ログインしていても「公開」の記事だけを出します。
   WordPress は、管理者がログインしていると「非公開」の記事も一覧に混ぜて見せるため、
   お客様に見えているように見えてしまっていました（お客様には、もともと出ていません）。
   （2026/10/29 ユーザー「/blog/ このページ、なぜか非公開のコラムも出てくるんだが」）
   ※記事そのもののページ・プレビューは、これまでどおりログイン中なら見られます。 */
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() || $q->is_singular() || $q->get( 'p' ) || $q->get( 'name' ) ) return;
	$pt = (array) $q->get( 'post_type' );
	if ( ! in_array( 'ymkrf_column', $pt, true ) ) return;
	$st = $q->get( 'post_status' );
	if ( $st === '' || $st === array() || $st === 'any' ) $q->set( 'post_status', 'publish' );
}, 20 );
