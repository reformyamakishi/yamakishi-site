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

	$names = isset( $_POST['names'] ) ? (array) wp_unslash( $_POST['names'] ) : array();
	$out   = array();
	foreach ( array_slice( $names, 0, 100 ) as $name ) {
		$name  = (string) $name;
		$title = trim( sanitize_text_field( pathinfo( $name, PATHINFO_FILENAME ) ) );
		if ( $title === '' ) continue;
		$out[ $name ] = ymkrf_media_dupe_find( $title );
	}
	wp_send_json_success( $out );
} );

/** 同じタイトルの写真。大文字・小文字は区別しません。
 *  ゴミ箱の写真もふくめます（「差しかえて更新 → 前の写真がゴミ箱へ → また同じ写真を入れる」ときに知らせるため。2026/10/22） */
function ymkrf_media_dupe_find( $title ) {
	global $wpdb;
	$ids = $wpdb->get_col( $wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts}
		  WHERE post_type = 'attachment'
		    AND LOWER( post_title ) = LOWER( %s )
		  ORDER BY ( post_status = 'trash' ) ASC, post_date DESC LIMIT 5",
		$title
	) );
	$list = array();
	foreach ( (array) $ids as $id ) {
		$id   = (int) $id;
		$trash = ( get_post_status( $id ) === 'trash' );
		$used  = $trash ? false : ( function_exists( 'ymkrf_att_in_use_anywhere' ) ? (bool) ymkrf_att_in_use_anywhere( $id ) : true );
		$par  = (int) wp_get_post_parent_id( $id );
		$list[] = array(
			'id'    => $id,
			'title' => get_the_title( $id ),
			'file'  => wp_basename( (string) get_attached_file( $id ) ),
			'thumb' => (string) wp_get_attachment_image_url( $id, 'thumbnail' ),
			'date'  => get_the_date( 'Y/n/j', $id ),
			'used'  => $used,
			'trash' => $trash,
			'where' => $par ? get_the_title( $par ) : '',
			'edit'  => (string) get_edit_post_link( $id, 'raw' ),
		);
	}
	return $list;
}


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
	.ymkrf-dupe__err{color:#b32d2e;font-weight:600;margin:8px 0 0;min-height:1em}
	</style>
	<script id="ymkrf-dupe-js">
	(function ($) {
		var CFG = <?php echo wp_json_encode( $cfg ); ?>;

		function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
		function base(n) { return String(n).replace(/\.[^.\/\\]+$/, ''); }
		function ext(n)  { var m = String(n).match(/(\.[^.\/\\]+)$/); return m ? m[1] : ''; }

		/* サーバーに「同じタイトルがあるか」をたずねます（試験のときは差しかえられるよう外に出しています） */
		window.ymkrfDupeCheck = window.ymkrfDupeCheck || function (names) {
			return $.post(CFG.ajax, { action: 'ymkrf_media_dupe', nonce: CFG.nonce, names: names })
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
			var $m = $(
				'<div class="ymkrf-dupe" role="dialog" aria-modal="true"><div class="ymkrf-dupe__box">' +
				'<p class="ymkrf-dupe__ttl">' + (inBin ? '同じ名前の写真が、ゴミ箱にあります' : '同じ名前の写真が、もうメディアにあります') + '</p>' +
				'<p class="ymkrf-dupe__lead">入れる前に、どうするかえらんでください。</p>' +
				'<div class="ymkrf-dupe__cmp">' +
				  '<div class="ymkrf-dupe__card"><b>これから入れる写真</b><div class="ymkrf-dupe__ph ymkrf-dupe__new"></div>' +
				    '<div class="ymkrf-dupe__nm">' + esc(name) + '</div></div>' +
				  '<div class="ymkrf-dupe__card"><b>' + (inBin ? 'ゴミ箱にある写真' : 'すでにある写真') + (hits.length > 1 ? '（同じ名前が' + hits.length + '枚）' : '') + '</b>' +
				    '<div class="ymkrf-dupe__ph">' + (h.thumb ? '<img src="' + esc(h.thumb) + '" alt="">' : '') + '</div>' +
				    '<div class="ymkrf-dupe__nm">' + esc(h.title) + '</div>' +
				    '<div class="ymkrf-dupe__sub">' + esc(h.date) + ' に追加' + (h.where ? '・' + esc(h.where) : '') + '</div>' +
				    (anyUsed ? '<span class="ymkrf-dupe__use">サイトで使われています</span>' : '') +
				  '</div>' +
				'</div>' +
				'<div class="ymkrf-dupe__opts">' +
				  '<button type="button" class="ymkrf-dupe__opt" data-a="keep"><i>＋</i><span>このまま入れる<small>' + (inBin ? 'ゴミ箱の写真はそのまま、新しい写真を入れます' : '同じ名前の写真が2枚になります') + '</small></span></button>' +
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
		function walk(files, i, keep) {
			if (i >= files.length) return $.Deferred().resolve(keep).promise();
			var f = files[i];
			return ymkrfDupeCheck([f.name]).then(function (map) {
				var hits = (map && map[f.name]) || [];
				if (!hits.length) { keep.push(f); return walk(files, i + 1, keep); }
				return ask(f, hits).then(function (r) {
					if (r === 'keep')   { keep.push(f); return walk(files, i + 1, keep); }
					if (r === 'rename') { return walk(files, i, keep); }   /* 新しい名前でもう一度 */
					f._ymkrfSkip = true; return walk(files, i + 1, keep);
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
				walk(list, 0, []).then(function (keep) {
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
