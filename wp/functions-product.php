<?php
/**
 * 商品（ymkrf_product）─ リフォームヤマキシ
 * functions.php に追記、または functions-snippet.php の末尾に足してください。
 *
 * ── 何ができるようになるか ─────────────────────────────
 * 管理画面に「商品」というメニューが増えます。
 * そこから、プログラムの知識なしで商品を追加・修正・削除できます。
 *
 *   入力する項目
 *     グレード ／ 名称 ／ メーカー ／ 型（サイズ）
 *     標準工事費 ／ 商品代 ／ 工期日数
 *     特徴3点 ／ 展示している店舗 ／ 商品写真
 *
 *   込み価格（598,000円）は自動で計算されるので、入力不要です。
 *
 * ── 名前について ───────────────────────────────────
 * サーバーに他サイトが同居しているため、すべて ymkrf 接頭辞を付けています。
 * URL は /products/ のまま。見た目は変わりません。
 * ───────────────────────────────────────────────
 */

if ( ! defined( 'ABSPATH' ) ) exit;


/* ============================================================
   1. 商品という入れ物をつくる
   ============================================================ */
add_action( 'init', function () {

	register_post_type( 'ymkrf_product', array(
		'label'         => '商品',
		'public'        => true,
		'has_archive'   => true,
		'menu_icon'     => 'dashicons-cart',
		'menu_position' => 4,
		'rewrite'       => array( 'slug' => 'products', 'with_front' => false ),
		'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
		'show_in_rest'  => true,
	) );

	/* 商品カテゴリ（キッチン／お風呂／トイレ …）
	   → /products/kitchen/ のような一覧ページが自動でできます */
	register_taxonomy( 'ymkrf_product_cat', 'ymkrf_product', array(
		'label'        => '商品カテゴリ',
		'hierarchical' => true,
		'rewrite'      => array( 'slug' => 'products', 'with_front' => false ),
		'show_in_rest' => true,
		'show_admin_column' => true,
	) );

	/* メーカー（Panasonic／LIXIL／TOTO／クリナップ …） */
	register_taxonomy( 'ymkrf_maker', 'ymkrf_product', array(
		'label'        => 'メーカー',
		'hierarchical' => true,
		'rewrite'      => array( 'slug' => 'maker', 'with_front' => false ),
		'show_in_rest' => true,
		'show_admin_column' => true,
	) );

	/* 展示店舗（金沢野々市店／小松店 …）
	   → 「この商品を見られるお店」の表示と、店舗ページからの逆引きに使います */
	register_taxonomy( 'ymkrf_shop', 'ymkrf_product', array(
		'label'        => '展示店舗',
		'hierarchical' => true,
		'rewrite'      => array( 'slug' => 'shop-display', 'with_front' => false ),
		'show_in_rest' => true,
		'show_admin_column' => true,
	) );
} );


/* ============================================================
   2. 商品の入力欄
   ============================================================ */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'ymkrf_product_box', '商品データ', 'ymkrf_product_box_html', 'ymkrf_product', 'normal', 'high' );
} );

function ymkrf_product_box_html( $post ) {
	wp_nonce_field( 'ymkrf_product_save', 'ymkrf_product_nonce' );

	$f = function ( $key, $default = '' ) use ( $post ) {
		$v = get_post_meta( $post->ID, $key, true );
		return $v !== '' ? $v : $default;
	};

	$rows = array(
		'_ymkrf_grade'   => array( 'グレード',        'text',   '例：Fグレード', '' ),
		'_ymkrf_name'    => array( '名称',            'text',   '例：リビングステーションV', '空欄なら記事タイトルを使います' ),
		'_ymkrf_size'    => array( '型（サイズ）',    'text',   '例：I型2550サイズ', '' ),
		'_ymkrf_work'    => array( '標準工事費（円）','number', '例：240000', '数字だけ。カンマや「円」は不要です' ),
		'_ymkrf_item'    => array( '商品代（円）',    'number', '例：358000', '数字だけ。カンマや「円」は不要です' ),
		'_ymkrf_days'    => array( '工期日数',        'text',   '例：3日', '' ),
		'_ymkrf_point1'  => array( '特徴 1',          'text',   '例：収納力抜群', '' ),
		'_ymkrf_point2'  => array( '特徴 2',          'text',   '例：快適性', '' ),
		'_ymkrf_point3'  => array( '特徴 3',          'text',   '例：安心', '' ),
	);

	echo '<style>
	.ymkrf-tbl{width:100%;border-collapse:collapse}
	.ymkrf-tbl th{width:180px;text-align:left;padding:12px 10px;vertical-align:top;font-weight:700}
	.ymkrf-tbl td{padding:10px}
	.ymkrf-tbl tr+tr{border-top:1px solid #eee}
	.ymkrf-tbl input{width:100%;max-width:420px}
	.ymkrf-note{display:block;margin-top:4px;color:#777;font-size:12px}
	.ymkrf-total{background:#fff4f0;border:2px solid #fe3301;border-radius:8px;
	             padding:14px 16px;margin-top:16px;font-weight:700}
	.ymkrf-total b{font-size:24px;color:#fe3301}
	</style>';

	echo '<table class="ymkrf-tbl">';
	foreach ( $rows as $key => $r ) {
		printf(
			'<tr><th><label for="%1$s">%2$s</label></th><td>
			   <input type="%3$s" id="%1$s" name="%1$s" value="%4$s" placeholder="%5$s">
			   %6$s</td></tr>',
			esc_attr( $key ), esc_html( $r[0] ), esc_attr( $r[1] ),
			esc_attr( $f( $key ) ), esc_attr( $r[2] ),
			$r[3] ? '<span class="ymkrf-note">' . esc_html( $r[3] ) . '</span>' : ''
		);
	}
	echo '</table>';

	/* 込み価格は自動計算。入力欄ではなく、確認用の表示にしています */
	$total = (int) $f( '_ymkrf_work' ) + (int) $f( '_ymkrf_item' );
	printf(
		'<p class="ymkrf-total">込み価格（自動計算）　<b id="ymkrf-total">%s</b> 円（税込）
		 <span class="ymkrf-note">標準工事費 ＋ 商品代 の合計です。入力の必要はありません。</span></p>',
		number_format( $total )
	);

	/* 入力しながら合計が変わるようにする */
	echo '<script>
	(function(){
	  var w=document.getElementById("_ymkrf_work"), i=document.getElementById("_ymkrf_item"),
	      t=document.getElementById("ymkrf-total");
	  function calc(){ t.textContent=((+w.value||0)+(+i.value||0)).toLocaleString(); }
	  w.addEventListener("input",calc); i.addEventListener("input",calc);
	})();
	</script>';

	echo '<p class="ymkrf-note" style="margin-top:14px">
	  ※ メーカー・商品カテゴリ・展示店舗は、右側の欄からチェックを入れてください。<br>
	  ※ 商品写真は、右側の「アイキャッチ画像」に設定してください。</p>';
}

add_action( 'save_post_ymkrf_product', function ( $post_id ) {
	if ( ! isset( $_POST['ymkrf_product_nonce'] ) ||
	     ! wp_verify_nonce( sanitize_key( $_POST['ymkrf_product_nonce'] ), 'ymkrf_product_save' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	$keys = array( '_ymkrf_grade', '_ymkrf_name', '_ymkrf_size', '_ymkrf_work',
	               '_ymkrf_item', '_ymkrf_days', '_ymkrf_point1', '_ymkrf_point2', '_ymkrf_point3' );

	foreach ( $keys as $key ) {
		if ( ! isset( $_POST[ $key ] ) ) continue;
		update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
	}

	/* 込み価格を保存しておく（並べ替えや絞り込みに使えるようにするため） */
	$total = (int) get_post_meta( $post_id, '_ymkrf_work', true )
	       + (int) get_post_meta( $post_id, '_ymkrf_item', true );
	update_post_meta( $post_id, '_ymkrf_total', $total );
} );


/* ============================================================
   3. 一覧を見やすくする（管理画面）
   ============================================================ */
add_filter( 'manage_ymkrf_product_posts_columns', function ( $cols ) {
	$new = array();
	foreach ( $cols as $k => $v ) {
		$new[ $k ] = $v;
		if ( $k === 'title' ) {
			$new['ymkrf_thumb'] = '写真';
			$new['ymkrf_price'] = '込み価格';
		}
	}
	return $new;
} );

add_action( 'manage_ymkrf_product_posts_custom_column', function ( $col, $post_id ) {
	if ( $col === 'ymkrf_thumb' ) {
		echo has_post_thumbnail( $post_id )
			? get_the_post_thumbnail( $post_id, array( 70, 70 ) )
			: '<span style="color:#c00">未設定</span>';
	}
	if ( $col === 'ymkrf_price' ) {
		$t = (int) get_post_meta( $post_id, '_ymkrf_total', true );
		echo $t ? esc_html( number_format( $t ) ) . ' 円' : '—';
	}
}, 10, 2 );


/* ============================================================
   4. テンプレートから呼び出すための小さな関数
   ============================================================ */
if ( ! function_exists( 'ymkrf_product_data' ) ) :
function ymkrf_product_data( $post_id = null ) {
	$post_id = $post_id ?: get_the_ID();
	$m = function ( $k ) use ( $post_id ) { return get_post_meta( $post_id, $k, true ); };

	$points = array_values( array_filter( array( $m('_ymkrf_point1'), $m('_ymkrf_point2'), $m('_ymkrf_point3') ) ) );

	$term_names = function ( $tax ) use ( $post_id ) {
		$terms = get_the_terms( $post_id, $tax );
		return ( $terms && ! is_wp_error( $terms ) ) ? $terms : array();
	};

	return array(
		'grade'  => $m('_ymkrf_grade'),
		'name'   => $m('_ymkrf_name') ?: get_the_title( $post_id ),
		'size'   => $m('_ymkrf_size'),
		'work'   => (int) $m('_ymkrf_work'),
		'item'   => (int) $m('_ymkrf_item'),
		'total'  => (int) $m('_ymkrf_total'),
		'days'   => $m('_ymkrf_days'),
		'points' => $points,
		'makers' => $term_names( 'ymkrf_maker' ),
		'cats'   => $term_names( 'ymkrf_product_cat' ),
		'shops'  => $term_names( 'ymkrf_shop' ),
	);
}
endif;
