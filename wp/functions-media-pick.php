<?php
/**
 * functions-media-pick.php ─ 商品の登録画面の「写真をえらぶ窓」に、しぼり込みを足します
 *
 * 置き場所： wp-content/themes/ymkrf/inc/functions-media-pick.php
 *
 * （2026/11/04 ユーザー「写真をえらぶ窓で、同じ大分類の写真をしぼり込めるようにする」
 *   もとの相談「なるべくサーバー軽くしたいので、同じものは使いまわしできるよう工夫して保存してもらえると選びやすい」）
 *
 * 窓の左上の欄（「すべてのメディア」「この投稿へのアップロード」など）に、2つ足します。
 *   ・この商品の写真
 *   ・同じ大分類（トイレ など）の写真 … 同じ大分類のほかの商品で使っている写真。
 *     同じ写真をもう一度入れずに、ここからえらんで使いまわせます（写真がふえない＝サーバーが軽い）。
 * 中身は「メディア ＞ 商品 ＞ アイテム／商品名」のフォルダと同じです
 *   （functions-media.php の ymkrf_media_map() の prod と ymkrf_media_pcat_ids()）。
 * 写真そのものは動かしません。はじめに出るのは、これまでどおり「すべて」です。
 */

if ( ! defined( 'ABSPATH' ) ) exit;


/* ============================================================
   1. 窓が写真をたずねてきたとき、えらんだしぼり込みで写真をしぼります
   ------------------------------------------------------------
   WordPress は、知らない項目（ymkrf_pcat など）を先に捨ててしまうので、
   送られてきたそのままの値（$_REQUEST['query']）から読みます。
   ============================================================ */
add_filter( 'ajax_query_attachments_args', function ( $q ) {
	$raw = ( isset( $_REQUEST['query'] ) && is_array( $_REQUEST['query'] ) ) ? wp_unslash( $_REQUEST['query'] ) : array();   /* phpcs:ignore */
	$ids = null;

	if ( ! empty( $raw['ymkrf_pcat'] ) && function_exists( 'ymkrf_media_pcat_ids' ) ) {
		$ids = ymkrf_media_pcat_ids( sanitize_title( (string) $raw['ymkrf_pcat'] ) );
	} elseif ( ! empty( $raw['ymkrf_prod'] ) && function_exists( 'ymkrf_media_map' ) ) {
		$map = ymkrf_media_map();
		$pid = (int) $raw['ymkrf_prod'];
		$ids = isset( $map['prod'][ $pid ] ) ? (array) $map['prod'][ $pid ] : array();
	}
	if ( $ids === null ) return $q;

	$ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
	/* カタログの窓のように、はじめから写真がしぼってあるときは、両方に当たるものだけ */
	if ( ! empty( $q['post__in'] ) ) $ids = array_values( array_intersect( $ids, array_map( 'intval', (array) $q['post__in'] ) ) );
	$q['post__in'] = $ids ? $ids : array( 0 );   /* 0＝1枚もない */
	return $q;
} );


/* ============================================================
   2. 窓の左上の欄に、しぼり込みを足します（商品の登録画面だけ）
   ============================================================ */
add_action( 'admin_print_footer_scripts', function () {
	if ( ! wp_script_is( 'media-views', 'done' ) ) return;
	$sc = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $sc || $sc->base !== 'post' || $sc->post_type !== 'ymkrf_product' ) return;

	$pid  = (int) get_the_ID();
	if ( ! $pid && isset( $GLOBALS['post'] ) && is_object( $GLOBALS['post'] ) ) $pid = (int) $GLOBALS['post']->ID;
	$slug = function_exists( 'ymkrf_product_current_cat' ) ? (string) ymkrf_product_current_cat( $pid ) : '';
	$name = '';
	if ( $slug !== '' ) {
		$t = get_term_by( 'slug', $slug, 'ymkrf_product_cat' );
		if ( $t && ! is_wp_error( $t ) ) $name = $t->name; else $slug = '';
	}
	$cfg = array( 'pid' => $pid, 'cat' => $slug, 'catName' => $name );
	?>
	<script id="ymkrf-media-pick-js">
	(function () {
		if (!window.wp || !wp.media || !wp.media.view || !wp.media.view.AttachmentFilters || !window._) return;
		var C = <?php echo wp_json_encode( $cfg ); ?>;

		function patch(View) {
			if (!View || View.prototype._ymkrfPick) return;
			var orig = View.prototype.createFilters;
			View.prototype.createFilters = function () {
				orig.apply(this, arguments);
				var f = this.filters;
				if (!f || !f.all) return;
				/* もとの項目をえらんだときは、こちらのしぼり込みを外します */
				_.each(f, function (x) { x.props = _.extend({ ymkrf_prod: null, ymkrf_pcat: null }, x.props); });
				if (C.pid) {
					f.ymkrf_this = { text: 'この商品の写真', priority: 11,
						props: _.extend({}, f.all.props, { ymkrf_prod: C.pid, ymkrf_pcat: null }) };
				}
				if (C.cat) {
					f.ymkrf_cat = { text: '同じ大分類（' + C.catName + '）の写真', priority: 12,
						props: _.extend({}, f.all.props, { ymkrf_pcat: C.cat, ymkrf_prod: null }) };
				}
			};
			View.prototype._ymkrfPick = true;
		}
		/* 写真の欄の「写真を選ぶ」の窓には、もともと左上の欄がありません。この画面だけ出します */
		var L = wp.media.controller && wp.media.controller.Library;
		if (L && L.prototype.defaults && !L.prototype.defaults.filterable) L.prototype.defaults.filterable = 'uploaded';
		/* 写真の欄の「写真を選ぶ」の窓（Uploaded）と、「メディアを追加」の窓（All）の両方 */
		patch(wp.media.view.AttachmentFilters.Uploaded);
		patch(wp.media.view.AttachmentFilters.All);
	})();
	</script>
	<?php
}, 60 );
