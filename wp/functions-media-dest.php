<?php
/**
 * functions-media-dest.php ─ 写真の「保存先（フォルダ）」を、入れるときにえらぶ・あとから引っ越す
 *
 * 置き場所： wp-content/themes/ymkrf/inc/functions-media-dest.php（functions-media.php のあとで読み込み）
 *
 * （2026/10/23 ユーザー指示「まずどこに保存するか入れる時に指定させたい。いれてからも引っ越しできるような仕組みにして」）
 *
 * ・フォルダは見せかただけです。保存先をえらんでも・引っ越しても、写真のファイルは動かないので URL は変わりません。
 * ・保存先は写真ごとに控えます：
 *     _ymkrf_media_use  … フォルダ（product / catalog / works / voice / column / news / flyer / staff / page）
 *     _ymkrf_media_prod … 商品 ＞ 商品名 のときの商品ID
 *   どちらも無いときは「自動（使った場所で決める）」です。
 * ・入れるとき
 *     メディア ＞ 新規追加／ライブラリ … 保存先をえらばないと入れられません
 *     商品・施工事例・コラムなどの登録画面 … その記事が最初からえらばれています（変えることもできます）
 * ・あとから引っ越す
 *     写真の編集画面（右の「保存先」）／ 一覧でチェック → 「保存先を引っ越す」
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/** 記事の種類 → 保存先 */
function ymkrf_media_dest_of_post( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) return '';
	$pt = get_post_type( $post_id );
	if ( $pt === 'ymkrf_product' ) return 'prod:' . $post_id;
	$k = function_exists( 'ymkrf_media_key_of_type' ) ? ymkrf_media_key_of_type( $pt ) : '';
	return $k;
}

/** いまの保存先（prod:123 / works / … / '' ＝自動） */
function ymkrf_media_dest_now( $id ) {
	$p = (int) get_post_meta( $id, '_ymkrf_media_prod', true );
	if ( $p > 0 ) return 'prod:' . $p;
	return (string) get_post_meta( $id, '_ymkrf_media_use', true );
}

/** 保存先の値が正しいか（正しければ整えて返す。だめなら null） */
function ymkrf_media_dest_clean( $v ) {
	$v = trim( (string) $v );
	if ( $v === '' || $v === 'auto' ) return 'auto';
	if ( preg_match( '/^prod:(\d+)$/', $v, $m ) ) {
		return get_post_type( (int) $m[1] ) === 'ymkrf_product' ? 'prod:' . (int) $m[1] : null;
	}
	$kinds = ymkrf_media_kinds();
	$subk  = ymkrf_media_subkinds();
	if ( ( isset( $kinds[ $v ] ) && $v !== 'none' ) || isset( $subk[ $v ] ) ) return $v;
	return null;
}

/** 保存先を決めます（引っ越し）。ファイルは動かしません */
function ymkrf_media_set_dest( $id, $dest ) {
	$dest = ymkrf_media_dest_clean( $dest );
	if ( $dest === null ) return false;
	if ( $dest === 'auto' ) {
		delete_post_meta( $id, '_ymkrf_media_use' );
		delete_post_meta( $id, '_ymkrf_media_prod' );
	} elseif ( strpos( $dest, 'prod:' ) === 0 ) {
		update_post_meta( $id, '_ymkrf_media_use', 'product' );
		update_post_meta( $id, '_ymkrf_media_prod', (int) substr( $dest, 5 ) );
	} else {
		update_post_meta( $id, '_ymkrf_media_use', $dest );
		delete_post_meta( $id, '_ymkrf_media_prod' );
	}
	if ( function_exists( 'ymkrf_media_forget' ) ) ymkrf_media_forget();
	return true;
}

/** 保存先の名前（例：商品 ＞ トイレ ＞ ピュアレストQR） */
function ymkrf_media_dest_label( $dest ) {
	if ( $dest === '' || $dest === 'auto' ) return '自動（使った場所で決める）';
	if ( preg_match( '/^prod:(\d+)$/', $dest, $m ) ) {
		$ts = get_the_terms( (int) $m[1], 'ymkrf_product_cat' );
		$c  = ( $ts && ! is_wp_error( $ts ) ) ? $ts[0]->name . ' ＞ ' : '';
		return '商品 ＞ ' . $c . get_the_title( (int) $m[1] );
	}
	$subk = ymkrf_media_subkinds();
	if ( isset( $subk[ $dest ] ) ) return '商品 ＞ ' . $subk[ $dest ]['name'];
	$kinds = ymkrf_media_kinds();
	return isset( $kinds[ $dest ] ) ? $kinds[ $dest ]['name'] : $dest;
}

/** 商品をアイテムごとに（サイトと同じ並び） */
function ymkrf_media_dest_products() {
	static $out = null;
	if ( $out !== null ) return $out;
	$pref  = array( 'kitchen', 'bathroom', 'toilet', 'lavatory', 'pack4', 'boiler', 'ecocute' );
	$posts = get_posts( array( 'post_type' => 'ymkrf_product', 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ),
	                           'posts_per_page' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) );
	$g = array();
	foreach ( $posts as $p ) {
		$ts = get_the_terms( $p->ID, 'ymkrf_product_cat' );
		$t  = ( $ts && ! is_wp_error( $ts ) ) ? $ts[0] : null;
		$k  = $t ? $t->slug : 'zz';
		if ( ! isset( $g[ $k ] ) ) {
			$at = $t ? array_search( $t->slug, $pref, true ) : false;
			$g[ $k ] = array( 'name' => $t ? $t->name : '分類なし',
			                  'ord'  => $at !== false ? $at : ( $t && in_array( $t->slug, array( 'interior', 'outer-wall' ), true ) ? 90 : 50 ),
			                  'items' => array() );
		}
		$g[ $k ]['items'][] = array( $p->ID, get_the_title( $p ) . ( $p->post_status !== 'publish' ? '（下書き）' : '' ) );
	}
	uasort( $g, function ( $a, $b ) { return $a['ord'] - $b['ord']; } );
	$out = $g;
	return $out;
}

/** 保存先をえらぶ欄 */
function ymkrf_media_dest_select( $name, $selected, $class = '', $placeholder = false ) {
	$h = '<select name="' . esc_attr( $name ) . '" class="ymkrf-dest ' . esc_attr( $class ) . '">';
	if ( $placeholder ) $h .= '<option value="">― 保存先をえらんでください ―</option>';
	foreach ( ymkrf_media_dest_products() as $g ) {
		$h .= '<optgroup label="' . esc_attr( '商品 ＞ ' . $g['name'] ) . '">';
		foreach ( $g['items'] as $it ) {
			$v  = 'prod:' . $it[0];
			$h .= '<option value="' . esc_attr( $v ) . '"' . selected( $selected, $v, false ) . '>' . esc_html( $g['name'] . ' ＞ ' . $it[1] ) . '</option>';
		}
		$h .= '</optgroup>';
	}
	$h .= '<optgroup label="商品（そのほか）">';
	$h .= '<option value="catalog"' . selected( $selected, 'catalog', false ) . '>商品 ＞ カタログ</option>';
	$h .= '<option value="product"' . selected( $selected, 'product', false ) . '>商品（どの商品かは決めない）</option>';
	$h .= '</optgroup><optgroup label="商品のほか">';
	foreach ( ymkrf_media_kinds() as $k => $d ) {
		if ( $k === 'product' || $k === 'none' ) continue;
		$h .= '<option value="' . esc_attr( $k ) . '"' . selected( $selected, $k, false ) . '>' . esc_html( $d['name'] ) . '</option>';
	}
	$h .= '</optgroup>';
	$h .= '<option value="auto"' . selected( in_array( $selected, array( '', 'auto' ), true ) && ! $placeholder, true, false ) . '>自動（使った場所で決める）</option>';
	return $h . '</select>';
}


/* ============================================================
   1. 入れるとき
   ============================================================ */

/* 商品をえらんで入れたときは、その商品の登録画面から入れたのと同じにします（名前が「メーカー－商品－もとの名前」になります） */
add_action( 'admin_init', function () {
	if ( wp_basename( (string) ( $_SERVER['SCRIPT_NAME'] ?? '' ) ) !== 'async-upload.php' ) return;   /* phpcs:ignore */
	if ( empty( $_REQUEST['ymkrf_dest'] ) ) return;                                                     /* phpcs:ignore */
	$d = ymkrf_media_dest_clean( wp_unslash( $_REQUEST['ymkrf_dest'] ) );                                /* phpcs:ignore */
	if ( $d && strpos( $d, 'prod:' ) === 0 && empty( $_REQUEST['post_id'] ) ) {                          /* phpcs:ignore */
		$_REQUEST['post_id'] = $_POST['post_id'] = (int) substr( $d, 5 );                                 /* phpcs:ignore */
	}
}, 1 );

/* 入った写真に、保存先を控えます。
   登録画面から入れて、その記事のままなら控えません（自動のまま。商品で使えば「商品」がいちばん強くなります） */
add_action( 'add_attachment', function ( $id ) {
	if ( empty( $_REQUEST['ymkrf_dest'] ) ) return;   /* phpcs:ignore */
	$d = ymkrf_media_dest_clean( wp_unslash( $_REQUEST['ymkrf_dest'] ) );   /* phpcs:ignore */
	if ( $d === null || $d === 'auto' ) return;
	$ctx = ymkrf_media_dest_of_post( isset( $_REQUEST['ymkrf_ctx'] ) ? (int) $_REQUEST['ymkrf_ctx'] : 0 );   /* phpcs:ignore */
	if ( $ctx !== '' && $ctx === $d ) return;
	ymkrf_media_set_dest( $id, $d );
}, 20 );

/* 「メディア ＞ 新規追加」の画面：アップロードの枠の上に、保存先の欄を出します */
add_action( 'pre-plupload-upload-ui', function () {
	$sc = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $sc || $sc->base !== 'media' ) return;
	$pre = isset( $_GET['ymkrf_dest'] ) ? (string) ymkrf_media_dest_clean( wp_unslash( $_GET['ymkrf_dest'] ) ) : '';   /* phpcs:ignore */
	if ( $pre === 'auto' ) $pre = '';
	echo '<div class="ymkrf-dest-bar ymkrf-dest-bar--page"><label><b>① 保存先（フォルダ）</b> ';
	echo ymkrf_media_dest_select( 'ymkrf_dest_pick', $pre, '', true );   /* phpcs:ignore */
	echo '</label><span class="ymkrf-dest-bar__note">② そのあと写真を入れてください。あとから引っ越すこともできます（写真のURLは変わりません）。</span></div>';
} );

/* JavaScript：入れる前に保存先を確かめ、いっしょに送ります。登録画面の「メディアを追加」の窓にも欄を出します */
add_action( 'admin_print_footer_scripts', function () {
	if ( ! current_user_can( 'upload_files' ) ) return;
	if ( ! wp_script_is( 'plupload', 'done' ) && ! wp_script_is( 'wp-plupload', 'done' ) && ! wp_script_is( 'media-views', 'done' ) ) return;
	$sc  = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	$ctx = 0; $def = '';
	if ( $sc && $sc->base === 'post' ) {
		global $post;
		if ( $post ) { $ctx = (int) $post->ID; $def = ymkrf_media_dest_of_post( $ctx ); }
	}
	if ( $sc && $sc->base === 'upload' ) {   /* フォルダを見ているときは、そのフォルダ */
		if ( ! empty( $_GET['ymkrf_prod'] ) && ctype_digit( (string) $_GET['ymkrf_prod'] ) ) $def = 'prod:' . (int) $_GET['ymkrf_prod'];   /* phpcs:ignore */
		elseif ( ! empty( $_GET['ymkrf_use'] ) && $_GET['ymkrf_use'] !== 'none' ) $def = (string) ymkrf_media_dest_clean( sanitize_key( $_GET['ymkrf_use'] ) );   /* phpcs:ignore */
	}
	if ( $sc && $sc->base === 'media' && ! empty( $_GET['ymkrf_dest'] ) ) $def = (string) ymkrf_media_dest_clean( wp_unslash( $_GET['ymkrf_dest'] ) );   /* phpcs:ignore */
	if ( $def === 'auto' ) $def = '';
	$must = ! ( $sc && $sc->base === 'post' );   /* 登録画面のほかは、えらばないと入れられません */
	?>
	<style>
	  .ymkrf-dest-bar{display:flex;flex-wrap:wrap;align-items:center;gap:8px 14px;margin:0 0 14px;padding:12px 16px;
	    background:#fff8f5;border:2px solid #fe3301;border-radius:8px;text-align:left}
	  .ymkrf-dest-bar label{display:flex;align-items:center;gap:8px;font-size:14px}
	  .ymkrf-dest-bar select{min-width:300px;max-width:100%;font-size:14px}
	  .ymkrf-dest-bar__note{font-size:12.5px;color:#50575e}
	  .ymkrf-dest-bar.is-need{animation:ymkrfNeed .5s 2;background:#fff0ea}
	  .ymkrf-dest-bar.is-need .ymkrf-dest-bar__note{color:#b32d2e;font-weight:700}
	  @keyframes ymkrfNeed{0%,100%{transform:none}25%{transform:translateX(-6px)}75%{transform:translateX(6px)}}
	  .media-modal .ymkrf-dest-bar{margin:0 auto 18px;max-width:760px}
	  .media-modal .uploader-window .ymkrf-dest-bar{display:none}
	</style>
	<template id="ymkrf-dest-tpl"><div class="ymkrf-dest-bar"><label><b>保存先（フォルダ）</b> <?php
		echo ymkrf_media_dest_select( 'ymkrf_dest_pick', $def, '', $must );   /* phpcs:ignore */
	?></label><span class="ymkrf-dest-bar__note"><?php echo $must ? '先に保存先をえらんでから、写真を入れてください。' : 'この記事から入れる写真です。ほかのフォルダにも変えられます。'; ?></span></div></template>
	<script>
	(function ($) {
		/* いまえらんでいる保存先（画面じゅうで1つ） */
		window.ymkrfDest = <?php echo wp_json_encode( $def ); ?>;
		var MUST = <?php echo $must ? 'true' : 'false'; ?>, CTX = <?php echo (int) $ctx; ?>;
		function syncAll() { $('select[name="ymkrf_dest_pick"]').val(window.ymkrfDest || ''); }
		$(document).on('change', 'select[name="ymkrf_dest_pick"]', function () {
			window.ymkrfDest = this.value; syncAll(); $('.ymkrf-dest-bar').removeClass('is-need');
		});
		function need() {
			var $b = $('.ymkrf-dest-bar:visible').first();
			if (!$b.length) { $('.upload-php .page-title-action').first().trigger('click'); inject(); $b = $('.ymkrf-dest-bar:visible').first(); }
			$('.ymkrf-dest-bar').removeClass('is-need'); void document.body.offsetWidth; $('.ymkrf-dest-bar').addClass('is-need');
			$('.ymkrf-dest-bar__note').text('先に「保存先（フォルダ）」をえらんでください。写真はまだ入っていません。');
			if ($b.length && $b[0].scrollIntoView) $b[0].scrollIntoView({ block: 'center', behavior: 'smooth' });
			$b.find('select').trigger('focus');
		}
		function hook(up) {
			if (!up || !up.bind || up._ymkrfDest) return;
			up._ymkrfDest = true;
			/* 保存先がまだなら、入れずに知らせます（同じ写真のしらべより先に動きます） */
			up.bind('FilesAdded', function (u, files) {
				if (files && files._ymkrfChecked) return;
				if (MUST && !window.ymkrfDest) {
					(files || []).slice().forEach(function (f) { try { u.removeFile(f); } catch (e) {} });
					need();
					return false;
				}
			}, null, 200);
			/* 送るときに、保存先をいっしょに送ります */
			up.bind('BeforeUpload', function (u) {
				u.settings.multipart_params = u.settings.multipart_params || {};
				u.settings.multipart_params.ymkrf_dest = window.ymkrfDest || '';
				u.settings.multipart_params.ymkrf_ctx  = CTX;
			});
		}
		if (window.wp && wp.Uploader && !wp.Uploader._ymkrfDestW) {
			var Orig = wp.Uploader;
			var W = function () { Orig.apply(this, arguments); hook(this.uploader); };
			W.prototype = Orig.prototype; $.extend(W, Orig); W._ymkrfDestW = true;
			wp.Uploader = W;
		}
		$(function () { window.setTimeout(function () { if (window.uploader) hook(window.uploader); }, 0); });
		$(window).on('load', function () { if (window.uploader) hook(window.uploader); });

		/* 「メディアを追加」の窓・ライブラリの「新規追加」に、保存先の欄を出します */
		var tpl = document.getElementById('ymkrf-dest-tpl');
		function inject() {
			$('.uploader-inline-content').each(function () {
				if ($(this).children('.ymkrf-dest-bar').length) return;
				$(this).prepend(tpl.content.cloneNode(true));
			});
			syncAll();
		}
		new MutationObserver(function () { if ($('.uploader-inline-content').not(':has(> .ymkrf-dest-bar)').length) inject(); })
			.observe(document.body, { childList: true, subtree: true });
		$(inject);
	})(jQuery);
	</script>
	<?php
}, 51 );


/* ============================================================
   2. あとから引っ越す
   ============================================================ */

/* 写真の編集画面（と、写真をえらぶ窓の右側）の「保存先」 */
add_filter( 'attachment_fields_to_edit', function ( $fields, $post ) {
	$now = ymkrf_media_dest_now( $post->ID );
	$fields['ymkrf_dest'] = array(
		'label' => '保存先（フォルダ）',
		'input' => 'html',
		'html'  => ymkrf_media_dest_select( 'attachments[' . (int) $post->ID . '][ymkrf_dest]', $now === '' ? 'auto' : $now, 'ymkrf-dest--edit' ),
		'helps' => '変えると、その写真がメディアのそのフォルダに引っ越します。写真のURLは変わりません。',
	);
	return $fields;
}, 10, 2 );

add_filter( 'attachment_fields_to_save', function ( $post, $attachment ) {
	if ( isset( $attachment['ymkrf_dest'] ) ) ymkrf_media_set_dest( (int) $post['ID'], wp_unslash( $attachment['ymkrf_dest'] ) );
	return $post;
}, 10, 2 );

/* 一覧でチェックした写真を、まとめて引っ越します */
add_filter( 'bulk_actions-upload', function ( $a ) {
	$a['ymkrf_move'] = '保存先を引っ越す';
	return $a;
} );
add_action( 'restrict_manage_posts', function ( $type, $which = 'top' ) {
	if ( $type !== 'attachment' || ! in_array( $which, array( 'bar', 'top' ), true ) ) return;   /* メディアの一覧では 'bar'（上の絞り込みの帯）で呼ばれます */
	echo '<span class="ymkrf-move" style="display:none;margin-left:6px">引っ越し先：';
	echo ymkrf_media_dest_select( 'ymkrf_move_dest', '', '', true );   /* phpcs:ignore */
	echo '</span>';
	?>
	<script>
	jQuery(function ($) {
		/* 「一括操作」で「保存先を引っ越す」をえらんだときだけ、引っ越し先の欄を出します */
		$('#bulk-action-selector-top').on('change', function () { $('.ymkrf-move').toggle(this.value === 'ymkrf_move'); });
		$('#posts-filter').on('submit', function (e) {
			if ($('#bulk-action-selector-top').val() === 'ymkrf_move' && !$('select[name="ymkrf_move_dest"]').val()) {
				e.preventDefault(); $('select[name="ymkrf_move_dest"]').css('outline', '2px solid #b32d2e').trigger('focus');
			}
		});
		/* 引っ越しの欄を「一括操作」のとなりへ */
		$('.ymkrf-move').insertAfter('#doaction');
	});
	</script>
	<?php
}, 10, 2 );
add_filter( 'handle_bulk_actions-upload', function ( $redirect, $action, $ids ) {
	if ( $action !== 'ymkrf_move' ) return $redirect;
	$dest = isset( $_REQUEST['ymkrf_move_dest'] ) ? wp_unslash( $_REQUEST['ymkrf_move_dest'] ) : '';   /* phpcs:ignore */
	$n = 0;
	foreach ( (array) $ids as $id ) {
		if ( current_user_can( 'edit_post', (int) $id ) && ymkrf_media_set_dest( (int) $id, $dest ) ) $n++;
	}
	return add_query_arg( array( 'ymkrf_moved' => $n, 'ymkrf_moved_to' => rawurlencode( (string) $dest ) ), $redirect );
}, 10, 3 );
add_action( 'admin_notices', function () {
	if ( ! isset( $_GET['ymkrf_moved'] ) ) return;   /* phpcs:ignore */
	$n  = (int) $_GET['ymkrf_moved'];                 /* phpcs:ignore */
	$to = isset( $_GET['ymkrf_moved_to'] ) ? ymkrf_media_dest_label( rawurldecode( wp_unslash( $_GET['ymkrf_moved_to'] ) ) ) : '';   /* phpcs:ignore */
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $n . '枚を「' . $to . '」に引っ越しました。写真のURLは変わっていません。' ) . '</p></div>';
} );

/* 一覧の「アップロード先」の下に、いまの保存先を小さく出します（手で決めたものだけ） */
add_filter( 'media_row_actions', function ( $actions, $post ) {
	$now = ymkrf_media_dest_now( $post->ID );
	if ( $now !== '' ) {
		$actions = array( 'ymkrf_dest' => '<span style="color:#00782a">📁 ' . esc_html( ymkrf_media_dest_label( $now ) ) . '</span>' ) + $actions;
	}
	return $actions;
}, 10, 2 );
