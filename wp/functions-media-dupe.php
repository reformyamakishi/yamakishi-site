<?php
/**
 * functions-media-dupe.php ─ メディアに同じ名前の写真を入れようとしたとき、入れる前に知らせます
 *
 * 置き場所： wp-content/themes/ymkrf/inc/functions-media-dupe.php
 *
 * （2026/10/21 ユーザー指示「メディアに同じタイトルのものが入ったら入れる前に、
 *   同じタイトルのものがありますと警告して、同じでも入れるか、名前を変えて入れるか
 *   （そこで変えれるようにして）、前のものを削除か選べるようにして」）
 *
 * ・写真をえらんだ（ドラッグした）ところで、アップロードを始める前に止めて調べます
 * ・メディアの「タイトル」は、アップロードしたファイルの名前（拡張子なし）になります。
 *   その名前と同じタイトルの写真がすでにあれば、えらんでもらいます
 *     1. このまま入れる（同じ名前のまま）
 *     2. 名前を変えて入れる（その場で名前を書きかえられます）
 *     3. 前の写真をゴミ箱に移してから入れる
 *        ※ サイトのどこかで使われている写真は、消せないようにしています（ページの写真が消えるため）
 *        ※ ゴミ箱に移した写真は「商品 ＞ 差しかえた写真」からもどせます
 *     4. やめる（この写真は入れない）
 * ・メディアの画面・「新規追加」の画面・各登録画面の「メディアを追加」の、どこから入れても動きます
 */

if ( ! defined( 'ABSPATH' ) ) exit;


/* ============================================================
   1. しらべる（同じタイトルの写真があるか）
   ============================================================ */
add_action( 'wp_ajax_ymkrf_media_dupe', function () {
	check_ajax_referer( 'ymkrf_media_dupe', 'nonce' );
	if ( ! current_user_can( 'upload_files' ) ) wp_send_json_error( 'cap' );

	$names  = isset( $_POST['names'] )  ? (array) wp_unslash( $_POST['names'] )  : array();
	$hashes = isset( $_POST['hashes'] ) ? (array) wp_unslash( $_POST['hashes'] ) : array();
	$sizes  = isset( $_POST['sizes'] )  ? (array) wp_unslash( $_POST['sizes'] )  : array();
	$pid    = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
	$out    = array();
	foreach ( array_slice( $names, 0, 100 ) as $i => $name ) {
		$name  = (string) $name;
		$title = trim( sanitize_text_field( pathinfo( $name, PATHINFO_FILENAME ) ) );
		if ( $title === '' ) continue;
		$hits = ymkrf_media_dupe_find( $title );
		/* 中身（写真そのもの）が同じものも探します（名前が変わっていても見つかるように。2026/10/23） */
		$hash = isset( $hashes[ $i ] ) ? strtolower( preg_replace( '/[^0-9a-f]/i', '', (string) $hashes[ $i ] ) ) : '';
		$size = isset( $sizes[ $i ] ) ? (int) $sizes[ $i ] : 0;
		if ( strlen( $hash ) === 40 && $size > 0 ) {
			$same = ymkrf_media_dupe_find_same( $hash, $size, $pid );
			$seen = array();
			foreach ( $same as $h ) $seen[ $h['id'] ] = true;
			foreach ( $hits as $h ) if ( empty( $seen[ $h['id'] ] ) ) $same[] = $h;
			$hits = $same;
		}
		$out[ $name ] = $hits;
	}
	wp_send_json_success( $out );
} );

/** 同じタイトルの写真。大文字・小文字は区別しません。
 *  ゴミ箱の写真もふくめます（「差しかえて更新 → 前の写真がゴミ箱へ → また同じ写真を入れる」ときに知らせるため。2026/10/22） */
function ymkrf_media_dupe_find( $title ) {
	global $wpdb;
	$ids = $wpdb->get_col( $wpdb->prepare(
		"SELECT p.ID FROM {$wpdb->posts} p
		   LEFT JOIN {$wpdb->postmeta} o ON ( o.post_id = p.ID AND o.meta_key = '_ymkrf_orig_name' )
		  WHERE p.post_type = 'attachment'
		    AND ( LOWER( p.post_title ) = LOWER( %s ) OR LOWER( o.meta_value ) = LOWER( %s ) )
		  GROUP BY p.ID
		  ORDER BY ( p.post_status = 'trash' ) ASC, p.post_date DESC LIMIT 5",
		$title, $title
	) );
	$list = array();
	foreach ( (array) $ids as $id ) $list[] = ymkrf_media_dupe_item( (int) $id, false );
	return $list;
}

/** サイトで使われているか（しらべるのに時間がかかるので、1回のたずねで3枚まで。それより後は「わからない」＝消すときにサーバーでもう一度見ます） */
function ymkrf_media_dupe_used( $id ) {
	static $n = 0;
	if ( ++$n > 3 ) return false;
	return function_exists( 'ymkrf_att_in_use_anywhere' ) ? (bool) ymkrf_att_in_use_anywhere( $id ) : true;
}

/** しらべた写真1枚ぶんの情報（$same＝中身がまったく同じ） */
function ymkrf_media_dupe_item( $id, $same ) {
		$trash = ( get_post_status( $id ) === 'trash' );
		$used  = $trash ? false : ymkrf_media_dupe_used( $id );
		$par  = (int) wp_get_post_parent_id( $id );
		return array(
			'same'  => (bool) $same,
			'id'    => $id,
			'title' => get_the_title( $id ),
			'orig'  => (string) get_post_meta( $id, '_ymkrf_orig_name', true ),   /* 上げたときのファイル名 */
			'file'  => wp_basename( (string) get_attached_file( $id ) ),
			'thumb' => (string) wp_get_attachment_image_url( $id, 'thumbnail' ),
			'date'  => get_the_date( 'Y/n/j', $id ),
			'used'  => $used,
			'trash' => $trash,
			'where' => $par ? get_the_title( $par ) : '',
			'edit'  => (string) get_edit_post_link( $id, 'raw' ),
		);
}

/**
 * 中身がまったく同じ写真をさがします（SHA-1 で見くらべます）
 * 見る範囲：① 同じ記事（商品など）に上げた写真　② ファイルの大きさが同じ写真
 * WordPress が大きい写真を縮めた「-scaled」でも、もとのファイルで見くらべます。
 */
function ymkrf_media_dupe_find_same( $hash, $size, $pid ) {
	global $wpdb;
	$ids = array();
	if ( $pid > 0 ) {
		$ids = array_merge( $ids, (array) $wpdb->get_col( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_parent = %d ORDER BY ID DESC LIMIT 300", $pid ) ) );
	}
	$ids = array_merge( $ids, (array) $wpdb->get_col( $wpdb->prepare(
		"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attachment_metadata' AND meta_value LIKE %s LIMIT 100",
		'%' . $wpdb->esc_like( '"filesize";i:' . (int) $size . ';' ) . '%' ) ) );
	$out = array();
	foreach ( array_unique( array_map( 'intval', $ids ) ) as $id ) {
		if ( get_post_type( $id ) !== 'attachment' ) continue;
		$paths = array_unique( array_filter( array(
			function_exists( 'wp_get_original_image_path' ) ? (string) wp_get_original_image_path( $id ) : '',
			(string) get_attached_file( $id ),
		) ) );
		foreach ( $paths as $path ) {
			if ( ! is_file( $path ) || (int) filesize( $path ) !== (int) $size ) continue;
			if ( strtolower( (string) sha1_file( $path ) ) === $hash ) { $out[] = ymkrf_media_dupe_item( $id, true ); break; }
		}
		if ( count( $out ) >= 5 ) break;
	}
	/* メディアにあるものを先、ゴミ箱のものをあとに */
	usort( $out, function ( $a, $b ) { return (int) $a['trash'] - (int) $b['trash']; } );
	return $out;
}


/* ------------------------------------------------------------
   1-b. 上げたときのファイル名をおぼえておきます
   ------------------------------------------------------------
   商品の登録画面から上げた写真は、ファイル名もタイトルも「toto-purerest-qr」のような
   商品の名前に置きかわります（functions-media.php）。そのままでは、同じ写真
   （TCF4714AK.jpg など）をもう一度上げても、名前で見分けられません。
   そこで、もとのファイル名（拡張子なし）を _ymkrf_orig_name にとっておき、
   しらべるときに、タイトルといっしょに見ます。
   （2026/10/23 ユーザー指摘「QRの登録ページで、オプションの写真 TCF4714AK.jpg を連続で登録したが（知らせが出なかった）」）
   ------------------------------------------------------------ */
add_action( 'add_attachment', function ( $id ) {
	if ( empty( $_FILES ) ) return;
	foreach ( array( 'async-upload', 'file' ) as $k ) {
		if ( empty( $_FILES[ $k ]['name'] ) ) continue;
		$orig = trim( sanitize_text_field( pathinfo( (string) $_FILES[ $k ]['name'], PATHINFO_FILENAME ) ) );
		if ( $orig !== '' ) update_post_meta( $id, '_ymkrf_orig_name', $orig );
		return;
	}
} );


/* ============================================================
   2. 前の写真をゴミ箱に移す（使われていないものだけ）
   ============================================================ */
add_action( 'wp_ajax_ymkrf_media_dupe_trash', function () {
	check_ajax_referer( 'ymkrf_media_dupe', 'nonce' );
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	if ( ! $id || get_post_type( $id ) !== 'attachment' ) wp_send_json_error( 'notfound' );
	if ( ! current_user_can( 'delete_post', $id ) )        wp_send_json_error( 'cap' );
	if ( function_exists( 'ymkrf_att_in_use_anywhere' ) && ymkrf_att_in_use_anywhere( $id ) ) {
		wp_send_json_error( 'inuse' );
	}
	/* 「商品 ＞ 差しかえた写真」の一覧からもどせるように、同じしるしを付けます */
	update_post_meta( $id, '_ymkrf_retired_product', (int) wp_get_post_parent_id( $id ) );
	update_post_meta( $id, '_ymkrf_retired_at', current_time( 'mysql' ) );
	update_post_meta( $id, '_ymkrf_retired_dupe', '1' );
	wp_update_post( array( 'ID' => $id, 'post_status' => 'trash' ) );
	if ( function_exists( 'ymkrf_media_forget' ) ) ymkrf_media_forget( $id );
	wp_send_json_success();
} );


/* 2-a. ゴミ箱の写真をもどす（「ゴミ箱の写真をもどして使う」を選んだとき） */
add_action( 'wp_ajax_ymkrf_media_dupe_restore', function () {
	check_ajax_referer( 'ymkrf_media_dupe', 'nonce' );
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	if ( ! $id || get_post_type( $id ) !== 'attachment' || get_post_status( $id ) !== 'trash' ) wp_send_json_error( 'notfound' );
	if ( ! current_user_can( 'edit_post', $id ) ) wp_send_json_error( 'cap' );
	wp_update_post( array( 'ID' => $id, 'post_status' => 'inherit' ) );
	delete_post_meta( $id, '_ymkrf_retired_at' );
	delete_post_meta( $id, '_ymkrf_retired_dupe' );
	if ( function_exists( 'ymkrf_media_forget' ) ) ymkrf_media_forget( $id );
	wp_send_json_success();
} );


/* ============================================================
   2-b. 「名前を変えて入れる」で書きかえた名前を、メディアのタイトルとファイル名に使います
   ------------------------------------------------------------
   写真の中身は、もとのファイル名のまま送られてきます。
   いっしょに送られる「name」（書きかえた名前）があれば、そちらにそろえます。
   ============================================================ */
function ymkrf_media_dupe_use_name() {
	if ( empty( $_FILES['async-upload']['name'] ) || empty( $_REQUEST['name'] ) ) return;   /* phpcs:ignore */
	$want = wp_basename( sanitize_text_field( wp_unslash( $_REQUEST['name'] ) ) );          /* phpcs:ignore */
	if ( $want === '' || $want === $_FILES['async-upload']['name'] ) return;
	/* 拡張子は、もとのファイルのものを使います（形式が変わらないように） */
	$ext = strtolower( pathinfo( (string) $_FILES['async-upload']['name'], PATHINFO_EXTENSION ) );
	if ( $ext !== '' && strtolower( pathinfo( $want, PATHINFO_EXTENSION ) ) !== $ext ) $want .= '.' . $ext;
	$_FILES['async-upload']['name'] = $want;
}
/* 写真はどこから入れても wp-admin/async-upload.php に届きます。そこで最初に書きかえます */
add_action( 'admin_init', function () {
	if ( wp_basename( (string) ( $_SERVER['SCRIPT_NAME'] ?? '' ) ) !== 'async-upload.php' ) return;   /* phpcs:ignore */
	ymkrf_media_dupe_use_name();
}, 0 );


/* ============================================================
   3. 画面の動き
   ============================================================ */
add_action( 'admin_print_footer_scripts', function () {
	if ( ! current_user_can( 'upload_files' ) ) return;
	if ( ! wp_script_is( 'plupload', 'done' ) && ! wp_script_is( 'wp-plupload', 'done' )
	  && ! wp_script_is( 'plupload-handlers', 'done' ) && ! wp_script_is( 'plupload-all', 'done' ) ) return;
	$cfg = array(
		'ajax'  => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'ymkrf_media_dupe' ),
		'trash' => admin_url( 'edit.php?post_type=ymkrf_product&page=ymkrf-product-media-trash' ),
	);
	?>
	<style id="ymkrf-dupe-css">
	.ymkrf-dupe{position:fixed;inset:0;z-index:200000;display:grid;place-items:center;background:rgba(0,0,0,.45)}
	.ymkrf-dupe__box{width:min(560px,calc(100vw - 32px));max-height:calc(100vh - 40px);overflow:auto;background:#fff;border-radius:12px;
	  box-shadow:0 12px 40px rgba(0,0,0,.25);padding:22px 24px 20px;font-size:13px;color:#1d2327}
	.ymkrf-dupe__ttl{margin:0 0 4px;font-size:17px;font-weight:700;display:flex;align-items:center;gap:8px}
	.ymkrf-dupe__ttl::before{content:"!";display:inline-grid;place-items:center;width:22px;height:22px;border-radius:50%;background:#dba617;color:#fff;font-size:14px}
	.ymkrf-dupe__lead{margin:0 0 14px;color:#50575e}
	.ymkrf-dupe__cmp{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:0 0 16px}
	.ymkrf-dupe__card{border:1px solid #dcdcde;border-radius:8px;padding:10px;background:#f6f7f7}
	.ymkrf-dupe__card b{display:block;font-size:11px;color:#646970;margin:0 0 6px}
	.ymkrf-dupe__ph{display:grid;place-items:center;aspect-ratio:4/3;background:#fff;border-radius:6px;overflow:hidden;margin:0 0 6px}
	.ymkrf-dupe__ph img{max-width:100%;max-height:100%;object-fit:contain}
	.ymkrf-dupe__nm{font-weight:600;word-break:break-all}
	.ymkrf-dupe__sub{font-size:11.5px;color:#787c82;margin-top:2px}
	.ymkrf-dupe__use{display:inline-block;margin-top:4px;font-size:11px;font-weight:700;color:#8a2424;background:#fcf0f1;border-radius:999px;padding:1px 8px}
	.ymkrf-dupe__opts{display:grid;gap:8px}
	.ymkrf-dupe__opt{display:flex;align-items:center;gap:10px;width:100%;text-align:left;padding:10px 12px;border:1px solid #c3c4c7;border-radius:8px;
	  background:#fff;cursor:pointer;font-size:13.5px;font-weight:600;color:#1d2327}
	.ymkrf-dupe__opt:hover:not(:disabled){border-color:#2271b1;background:#f0f6fc}
	.ymkrf-dupe__opt small{display:block;font-weight:400;color:#646970;font-size:11.5px;margin-top:1px}
	.ymkrf-dupe__opt:disabled{opacity:.55;cursor:not-allowed}
	.ymkrf-dupe__opt i{font-style:normal;flex:none;width:22px;text-align:center;font-size:15px}
	.ymkrf-dupe__ren{display:none;gap:6px;align-items:center;margin:2px 0 0 32px}
	.ymkrf-dupe__ren.is-on{display:flex}
	.ymkrf-dupe__ren input{flex:1;min-width:0}
	.ymkrf-dupe__cancel{margin-top:12px;text-align:right}
	.ymkrf-dupe__cancel button{background:none;border:0;color:#646970;text-decoration:underline;cursor:pointer;font-size:12.5px}
	.ymkrf-dupe-wait{position:fixed;right:20px;bottom:20px;z-index:200001;background:#1d2327;color:#fff;font-size:13px;
	  padding:9px 14px;border-radius:999px;box-shadow:0 4px 14px rgba(0,0,0,.2)}
	body:has(.ymkrf-dupe) .ymkrf-dupe-wait{display:none}
	.ymkrf-dupe__err{color:#b32d2e;font-weight:600;margin:8px 0 0;min-height:1em}
	</style>
	<script id="ymkrf-dupe-js">
	(function ($) {
		var CFG = <?php echo wp_json_encode( $cfg ); ?>;

		function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
		function base(n) { return String(n).replace(/\.[^.\/\\]+$/, ''); }
		function ext(n)  { var m = String(n).match(/(\.[^.\/\\]+)$/); return m ? m[1] : ''; }

		/* サーバーに「同じタイトルがあるか」をたずねます（試験のときは差しかえられるよう外に出しています） */
		window.ymkrfDupeCheck = window.ymkrfDupeCheck || function (names, extra) {
			extra = extra || {};
			return $.post(CFG.ajax, { action: 'ymkrf_media_dupe', nonce: CFG.nonce, names: names,
			                          hashes: extra.hashes || [], sizes: extra.sizes || [], post_id: extra.post_id || 0 })
				.then(function (r) { return (r && r.success) ? r.data : {}; }, function () { return $.Deferred().resolve({}); });
		};
		window.ymkrfDupeTrash = window.ymkrfDupeTrash || function (id) {
			return $.post(CFG.ajax, { action: 'ymkrf_media_dupe_trash', nonce: CFG.nonce, id: id });
		};
		window.ymkrfDupeRestore = window.ymkrfDupeRestore || function (id) {
			return $.post(CFG.ajax, { action: 'ymkrf_media_dupe_restore', nonce: CFG.nonce, id: id });
		};

		/* 1枚ぶんのえらぶ画面。結果は 'keep'（このまま）／'rename'（file.name を書きかえ済み）／'skip'（やめる） */
		function ask(file, all) {
			var d = $.Deferred(), name = file.name;
			/* メディアにあるもの（live）を先に。無ければ、ゴミ箱のもの（trash）でききます */
			var live = all.filter(function (x) { return !x.trash; }), bin = all.filter(function (x) { return x.trash; });
			var inBin = !live.length, hits = inBin ? bin : live, h = hits[0];
			var anyUsed = hits.some(function (x) { return x.used; });
			var sameBody = hits.some(function (x) { return x.same; });
			var $m = $(
				'<div class="ymkrf-dupe" role="dialog" aria-modal="true"><div class="ymkrf-dupe__box">' +
				'<p class="ymkrf-dupe__ttl">' + (sameBody
				    ? (inBin ? 'まったく同じ写真が、ゴミ箱にあります' : 'まったく同じ写真が、もうメディアにあります')
				    : (inBin ? '同じ名前の写真が、ゴミ箱にあります' : '同じ名前の写真が、もうメディアにあります')) + '</p>' +
				'<p class="ymkrf-dupe__lead">入れる前に、どうするかえらんでください。</p>' +
				'<div class="ymkrf-dupe__cmp">' +
				  '<div class="ymkrf-dupe__card"><b>これから入れる写真</b><div class="ymkrf-dupe__ph ymkrf-dupe__new"></div>' +
				    '<div class="ymkrf-dupe__nm">' + esc(name) + '</div></div>' +
				  '<div class="ymkrf-dupe__card"><b>' + (inBin ? 'ゴミ箱にある写真' : 'すでにある写真') + (hits.length > 1 ? '（' + (sameBody ? '同じ写真' : '同じ名前') + 'が' + hits.length + '枚）' : '') + '</b>' +
				    '<div class="ymkrf-dupe__ph">' + (h.thumb ? '<img src="' + esc(h.thumb) + '" alt="">' : '') + '</div>' +
				    '<div class="ymkrf-dupe__nm">' + esc(h.orig || h.title) + '</div>' +
				    (h.orig && h.orig !== h.title ? '<div class="ymkrf-dupe__sub">メディアのタイトル：' + esc(h.title) + '</div>' : '') +
				    '<div class="ymkrf-dupe__sub">' + esc(h.date) + ' に追加' + (h.where ? '・' + esc(h.where) : '') + '</div>' +
				    (anyUsed ? '<span class="ymkrf-dupe__use">サイトで使われています</span>' : '') +
				  '</div>' +
				'</div>' +
				'<div class="ymkrf-dupe__opts">' +
				  '<button type="button" class="ymkrf-dupe__opt" data-a="keep"><i>＋</i><span>このまま入れる<small>' + (inBin ? 'ゴミ箱の写真はそのまま、新しい写真を入れます' : (sameBody ? '同じ写真が、もう1枚ふえます' : '同じ名前の写真が2枚になります')) + '</small></span></button>' +
				  '<button type="button" class="ymkrf-dupe__opt" data-a="ren"><i>✎</i><span>名前を変えて入れる<small>下の欄で名前を書きかえてください</small></span></button>' +
				  '<div class="ymkrf-dupe__ren"><input type="text" class="regular-text" aria-label="新しい名前"><span>' + esc(ext(name)) + '</span>' +
				    '<button type="button" class="button button-primary" data-a="ren-ok">この名前で入れる</button></div>' +
				  (inBin
				    ? '<button type="button" class="ymkrf-dupe__opt" data-a="restore"><i>↩</i><span>ゴミ箱の写真をもどして使う' +
				      '<small>新しい写真は入れずに、前の写真をメディアにもどします（あとは登録画面でえらびなおしてください）</small></span></button>'
				    : '<button type="button" class="ymkrf-dupe__opt" data-a="del"' + (anyUsed ? ' disabled' : '') + '><i>🗑</i><span>前の写真をゴミ箱に移してから入れる' +
				      '<small>' + (anyUsed ? 'サイトのページで使われているので、消せません（消すとページの写真が消えるため）'
				                           : 'ゴミ箱に移した写真は「商品 ＞ 差しかえた写真」からもどせます') + '</small></span></button>') +
				'</div>' +
				'<p class="ymkrf-dupe__err" aria-live="polite"></p>' +
				'<div class="ymkrf-dupe__cancel"><button type="button" data-a="skip">この写真は入れない</button></div>' +
				'</div></div>'
			).appendTo(document.body);

			/* これから入れる写真の見本（できるときだけ） */
			try {
				var src = file.getNative ? file.getNative() : null;
				if (src && /^image\//.test(src.type) && window.URL) {
					$m.find('.ymkrf-dupe__new').append($('<img alt="">').attr('src', URL.createObjectURL(src)));
				}
			} catch (e) {}

			var $in = $m.find('.ymkrf-dupe__ren input').val(base(name) + '-2');
			function done(r) { $m.remove(); $(document).off('keydown.ymkrfDupe'); d.resolve(r); }
			$(document).on('keydown.ymkrfDupe', function (e) { if (e.key === 'Escape') done('skip'); });

			$m.on('click', '[data-a]', function () {
				var a = $(this).data('a');
				if (a === 'keep') return done('keep');
				if (a === 'skip') return done('skip');
				if (a === 'ren') { $m.find('.ymkrf-dupe__ren').addClass('is-on'); $in.trigger('focus').trigger('select'); return; }
				if (a === 'ren-ok') {
					var nb = $.trim($in.val());
					if (!nb) { $m.find('.ymkrf-dupe__err').text('新しい名前を入れてください。'); return; }
					file.name = nb + ext(name);
					return done('rename');
				}
				if (a === 'restore') {
					var $r = $(this).prop('disabled', true);
					ymkrfDupeRestore(h.id).then(function (res) {
						if (res && res.success === false) throw 0;
						done('skip');
					}).fail(function () {
						$r.prop('disabled', false);
						$m.find('.ymkrf-dupe__err').text('もどせませんでした。もう一度ためすか、ほかをえらんでください。');
					});
					return;
				}
				if (a === 'del') {
					var $b = $(this).prop('disabled', true);
					var reqs = hits.filter(function (x) { return !x.used; }).map(function (x) { return ymkrfDupeTrash(x.id); });
					$.when.apply($, reqs).then(function () { done('keep'); }, function () {
						$b.prop('disabled', false);
						$m.find('.ymkrf-dupe__err').text('ゴミ箱に移せませんでした。もう一度ためすか、ほかをえらんでください。');
					});
				}
			});
			$in.on('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); $m.find('[data-a="ren-ok"]').trigger('click'); } });
			$m.find('[data-a="keep"]').trigger('focus');
			return d.promise();
		}

		/* 何枚か入れたときは、1枚ずつ順番にきいていきます。名前を変えたら、その名前でもう一度しらべます */
		/* 写真の中身の「指紋」（SHA-1）。名前を変えた同じ写真も見つけるために使います */
		function fingerprint(f) {
			var d = $.Deferred();
			try {
				var src = f.getNative ? f.getNative() : null;
				if (!src || !window.crypto || !crypto.subtle || !src.arrayBuffer || src.size > 60 * 1024 * 1024) return d.resolve('').promise();
				src.arrayBuffer().then(function (buf) { return crypto.subtle.digest('SHA-1', buf); })
					.then(function (h) {
						d.resolve(Array.prototype.map.call(new Uint8Array(h), function (b) { return ('0' + b.toString(16)).slice(-2); }).join(''));
					}, function () { d.resolve(''); });
			} catch (e) { d.resolve(''); }
			return d.promise();
		}

		function walk(files, i, keep, up) {
			if (i >= files.length) return $.Deferred().resolve(keep).promise();
			var f = files[i];
			var pid = 0;
			try { pid = (up && up.settings && up.settings.multipart_params && up.settings.multipart_params.post_id) || 0; } catch (e) {}
			return fingerprint(f).then(function (hash) {
				/* 「名前を変えて入れる」をえらんだあとは、名前だけで見ます（中身はもちろん同じなので） */
				var useHash = hash && !f._ymkrfRenamed;
				return ymkrfDupeCheck([f.name], { hashes: useHash ? [hash] : [], sizes: useHash ? [f.size || 0] : [], post_id: pid });
			}).then(function (map) {
				var hits = (map && map[f.name]) || [];
				if (!hits.length) { keep.push(f); return walk(files, i + 1, keep, up); }
				return ask(f, hits).then(function (r) {
					if (r === 'keep')   { keep.push(f); return walk(files, i + 1, keep, up); }
					if (r === 'rename') { f._ymkrfRenamed = true; return walk(files, i, keep, up); }   /* 新しい名前でもう一度 */
					f._ymkrfSkip = true; return walk(files, i + 1, keep, up);
				});
			});
		}

		/* plupload に「写真が足された」ときの見張りを、いちばん先に付けます */
		function hook(up) {
			if (!up || !up.bind || up._ymkrfDupe) return;
			up._ymkrfDupe = true;
			up.bind('FilesAdded', function (u, files) {
				if (files && files._ymkrfChecked) return;          /* しらべ終わって流しなおした分 */
				var list = (files || []).slice();
				if (!list.length) return;
				/* しらべているあいだ、右下に小さく出します（1〜3秒ほど） */
				var $t = $('<div class="ymkrf-dupe-wait" role="status">同じ写真がないか、しらべています…</div>').appendTo(document.body);
				walk(list, 0, [], u).always(function () { $t.remove(); }).then(function (keep) {
					list.forEach(function (f) { if (f._ymkrfSkip) { try { u.removeFile(f); } catch (e) {} } });
					if (!keep.length) return;
					keep._ymkrfChecked = true;
					u.trigger('FilesAdded', keep);
				});
				return false;   /* ここでいったん止めます（このあとの「アップロード開始」を動かさない） */
			}, null, 100);
		}

		/* メディアの画面・登録画面の「メディアを追加」（wp.Uploader） */
		if (window.wp && wp.Uploader && !wp.Uploader._ymkrfDupe) {
			var Orig = wp.Uploader;
			var Wrapped = function () {
				Orig.apply(this, arguments);
				hook(this.uploader);
			};
			Wrapped.prototype = Orig.prototype;
			$.extend(Wrapped, Orig);
			Wrapped._ymkrfDupe = true;
			wp.Uploader = Wrapped;
		}
		/* 「メディア ＞ 新規追加」の画面（window.uploader） */
		$(function () { window.setTimeout(function () { if (window.uploader) hook(window.uploader); }, 0); });
		$(window).on('load', function () { if (window.uploader) hook(window.uploader); });
	})(jQuery);
	</script>
	<?php
}, 50 );
