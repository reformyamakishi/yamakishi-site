<?php
/**
 * functions-pack-list.php ─ クロス・床／内装・改装／窓・断熱 の商品一覧（ダッシュボード）を見やすくします
 *
 * 置き場所： wp-content/themes/ymkrf/inc/functions-pack-list.php
 *
 * （2026/11/05 ユーザー「ダッシュボードの管理画面一覧のところが見にくいな」→「一覧も直す」）
 *
 * この3つの分類は、メーカー・グレード・展示店舗を使わず、
 * 「6帖・8帖のように値段だけちがう商品」が並ぶ、パック料金の分類です（ymkrf_pack_cats()）。
 * そこで、この3つの一覧だけ、列と並びを変えます。ほかの分類（キッチン・トイレなど）の一覧は変えません。
 *
 *   列：工事名 → 広さ → 価格（工事費込） → 写真 → まとまり → 日付
 *       ・広さ ＝ 商品名の（　）の中（6帖パック など）
 *       ・写真 ＝ Before/After の写真（無ければアイキャッチ）。
 *                 この商品に無くて、同じ工事のほかの広さの写真を使っているときは「6帖と同じ写真」
 *       ・まとまり ＝ 「まとまり（見出し）」欄（床リフォーム／部屋リフォーム など）
 *   並び：まとまり → 同じ工事（6帖と8帖が上下に） → 安い順
 *         （見出しを押して並べかえたときは、そちらが先）
 *   内装・改装の一覧の上には、「床リフォームはクロス・床に入っています」の案内を出します
 *   （ホームページの内装・改装のページには、クロス・床の床リフォームも出しているため）
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/** いま、この3つの分類の一覧を見ているか（見ているならその分類のslug） */
function ymkrf_pk_list_cat() {
	global $pagenow;
	if ( ! is_admin() || $pagenow !== 'edit.php' ) return '';
	if ( ! isset( $_GET['post_type'] ) || $_GET['post_type'] !== 'ymkrf_product' ) return '';   /* phpcs:ignore */
	$cat = isset( $_GET['ymkrf_product_cat'] ) ? sanitize_title( wp_unslash( $_GET['ymkrf_product_cat'] ) ) : '';   /* phpcs:ignore */
	$packs = function_exists( 'ymkrf_pack_cats' ) ? ymkrf_pack_cats() : array( 'interior', 'renovation', 'window' );
	return in_array( $cat, $packs, true ) ? $cat : '';
}

/* 商品名から（　）の前（工事名）と中（広さ）を取り出します */
function ymkrf_pk_list_base( $t ) { return trim( preg_replace( '/\s*[（(][^（）()]*[）)]\s*$/u', '', (string) $t ) ); }
function ymkrf_pk_list_size( $t ) { return preg_match( '/[（(]([^（）()]*)[）)]\s*$/u', (string) $t, $m ) ? trim( $m[1] ) : ''; }
function ymkrf_pk_list_total( $id ) {
	$v = (int) get_post_meta( $id, '_ymkrf_total', true );
	if ( ! $v ) $v = (int) get_post_meta( $id, '_ymkrf_work', true ) + (int) get_post_meta( $id, '_ymkrf_item', true );
	return $v;
}

/** その商品の写真（Before/After の after → before → アイキャッチ の順）。無ければ 0 */
function ymkrf_pk_list_photo( $id ) {
	$ba = get_post_meta( $id, '_ymkrf_ba', true );
	if ( is_array( $ba ) ) {
		foreach ( $ba as $r ) {
			if ( ! is_array( $r ) ) continue;
			if ( ! empty( $r['after'] ) )  return (int) $r['after'];
			if ( ! empty( $r['before'] ) ) return (int) $r['before'];
		}
	}
	return has_post_thumbnail( $id ) ? (int) get_post_thumbnail_id( $id ) : 0;
}


/* ============================================================
   1. 列
   ============================================================ */
add_filter( 'manage_ymkrf_product_posts_columns', function ( $cols ) {
	if ( ymkrf_pk_list_cat() === '' ) return $cols;
	$out = array();
	if ( isset( $cols['cb'] ) ) $out['cb'] = $cols['cb'];
	$out['title']          = '工事名';
	$out['ymkrf_pk_size']  = '広さ';
	$out['ymkrf_pk_price'] = '価格（工事費込）';
	$out['ymkrf_pk_photo'] = '写真';
	$out['ymkrf_pk_group'] = 'まとまり';
	$out['date']           = '日付';
	return $out;
}, 60 );

add_action( 'manage_ymkrf_product_posts_custom_column', function ( $col, $post_id ) {
	if ( strpos( $col, 'ymkrf_pk_' ) !== 0 ) return;
	$title = get_the_title( $post_id );

	if ( $col === 'ymkrf_pk_size' ) {
		$s = ymkrf_pk_list_size( $title );
		echo $s !== '' ? '<span class="ymkrf-pkl__size">' . esc_html( $s ) . '</span>' : '<span class="ymkrf-pkl__none">—</span>';
	}

	if ( $col === 'ymkrf_pk_price' ) {
		$v = ymkrf_pk_list_total( $post_id );
		if ( $v ) {
			$man = rtrim( rtrim( number_format( $v / 10000, 1, '.', '' ), '0' ), '.' );
			echo '<b class="ymkrf-pkl__man">' . esc_html( $man ) . '万円〜</b><br><small class="ymkrf-pkl__yen">' . esc_html( number_format( $v ) ) . '円</small>';
		} else {
			echo '<span class="ymkrf-pkl__red">未入力</span>';
		}
	}

	if ( $col === 'ymkrf_pk_group' ) {
		$g = trim( (string) get_post_meta( $post_id, '_ymkrf_catch', true ) );
		echo $g !== '' ? esc_html( $g ) : '<span class="ymkrf-pkl__none">—</span>';
	}

	if ( $col === 'ymkrf_pk_photo' ) {
		$img = ymkrf_pk_list_photo( $post_id );
		$from = '';
		if ( ! $img ) {
			/* 同じ工事のほかの広さ（同じ分類の中）の写真を、ホームページでは使っています */
			$base  = ymkrf_pk_list_base( $title );
			$terms = wp_get_post_terms( $post_id, 'ymkrf_product_cat', array( 'fields' => 'ids' ) );
			if ( $base !== '' && $terms && ! is_wp_error( $terms ) ) {
				$sibs = get_posts( array( 'post_type' => 'ymkrf_product', 'post_status' => 'any', 'numberposts' => 30,
					'post__not_in' => array( $post_id ), 'no_found_rows' => true,
					'tax_query' => array( array( 'taxonomy' => 'ymkrf_product_cat', 'field' => 'term_id', 'terms' => array_map( 'intval', $terms ) ) ) ) );
				foreach ( $sibs as $s ) {
					if ( ymkrf_pk_list_base( $s->post_title ) !== $base ) continue;
					$si = ymkrf_pk_list_photo( $s->ID );
					if ( $si ) { $img = $si; $from = ymkrf_pk_list_size( $s->post_title ); break; }
				}
			}
		}
		if ( $img ) {
			echo '<span class="ymkrf-pkl__ph' . ( $from !== '' ? ' is-shared' : '' ) . '">' . wp_get_attachment_image( $img, array( 64, 64 ) ) . '</span>';
			if ( $from !== '' ) echo '<small class="ymkrf-pkl__share">' . esc_html( $from ) . 'と同じ写真</small>';
		} else {
			echo '<span class="ymkrf-pkl__red">未設定</span>';
		}
	}
}, 20, 2 );


/* ============================================================
   2. 並び：まとまり → 同じ工事（6帖と8帖を上下に） → 安い順
   ============================================================ */
add_filter( 'the_posts', function ( $posts, $q ) {
	if ( ! $q->is_main_query() || ymkrf_pk_list_cat() === '' ) return $posts;
	if ( ! empty( $_GET['orderby'] ) ) return $posts;   /* phpcs:ignore 見出しを押して並べかえたときは、そのまま */
	if ( count( $posts ) < 2 ) return $posts;

	$gorder = array( '床リフォーム', '部屋リフォーム', 'クロスリフォーム', '窓リフォーム', '断熱リフォーム' );

	/* 同じまとまりの中の工事の順番は、ホームページ（フロント）と同じにします
	   （2026/11/05 ユーザー「編集ページもフロントと同じ並びにして」）。
	   フロントと同じ取り出しかた（ymkrf_sort＝price、公開中だけ）で、工事名がはじめに出てくる順を使います。
	   公開中の商品が無い工事（下書きだけ）は、そのあとに、安い順で。 */
	$front = array();
	$t = get_term_by( 'slug', ymkrf_pk_list_cat(), 'ymkrf_product_cat' );
	if ( $t && ! is_wp_error( $t ) ) {
		$fq = new WP_Query( array( 'post_type' => 'ymkrf_product', 'post_status' => 'publish', 'posts_per_page' => -1, 'no_found_rows' => true,
			'tax_query' => array( array( 'taxonomy' => 'ymkrf_product_cat', 'field' => 'term_id', 'terms' => (int) $t->term_id ) ),
			'ymkrf_sort' => 'price' ) );
		foreach ( $fq->posts as $i => $fp ) {
			$b = ymkrf_pk_list_base( $fp->post_title );
			if ( ! isset( $front[ $b ] ) ) $front[ $b ] = $i;
		}
	}
	$minp = array();   /* 工事ごとのいちばん安い値段（下書きだけの工事の並びに使います） */
	foreach ( $posts as $p ) {
		$b = ymkrf_pk_list_base( $p->post_title );
		$v = ymkrf_pk_list_total( $p->ID );
		if ( $v && ( ! isset( $minp[ $b ] ) || $v < $minp[ $b ] ) ) $minp[ $b ] = $v;
	}
	$key = array();
	foreach ( $posts as $i => $p ) {
		$g  = trim( (string) get_post_meta( $p->ID, '_ymkrf_catch', true ) );
		$gi = array_search( $g, $gorder, true );
		$b  = ymkrf_pk_list_base( $p->post_title );
		$bi = isset( $front[ $b ] ) ? $front[ $b ] : 100000 + ( isset( $minp[ $b ] ) ? (int) floor( $minp[ $b ] / 100 ) : 99999 );
		$v  = ymkrf_pk_list_total( $p->ID );
		$key[ $p->ID ] = array( $gi === false ? 99 : $gi, $bi, $v ? $v : PHP_INT_MAX, $i );
	}
	usort( $posts, function ( $a, $b ) use ( $key ) {
		$ka = $key[ $a->ID ]; $kb = $key[ $b->ID ];
		for ( $n = 0; $n < 4; $n++ ) { if ( $ka[ $n ] !== $kb[ $n ] ) return $ka[ $n ] < $kb[ $n ] ? -1 : 1; }
		return 0;
	} );
	return $posts;
}, 20, 2 );


/* ============================================================
   3. 内装・改装の一覧の上の案内（床リフォームはクロス・床に入っています）
   ============================================================ */
add_action( 'admin_notices', function () {
	if ( ymkrf_pk_list_cat() !== 'renovation' ) return;
	$t = get_term_by( 'slug', 'interior', 'ymkrf_product_cat' );
	if ( ! $t || is_wp_error( $t ) ) return;
	$ids = get_posts( array( 'post_type' => 'ymkrf_product', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'no_found_rows' => true,
		'tax_query' => array( array( 'taxonomy' => 'ymkrf_product_cat', 'field' => 'term_id', 'terms' => (int) $t->term_id ) ) ) );
	$names = array();
	foreach ( $ids as $id ) {
		if ( trim( (string) get_post_meta( $id, '_ymkrf_catch', true ) ) !== '床リフォーム' ) continue;
		$names[ ymkrf_pk_list_base( get_the_title( $id ) ) ] = 1;
	}
	if ( ! $names ) return;
	$url = admin_url( 'edit.php?post_type=ymkrf_product&ymkrf_product_cat=interior' );
	echo '<div class="notice notice-info"><p><b>床リフォーム</b>（' . esc_html( implode( '／', array_keys( $names ) ) )
	   . '）は「クロス・床」に入っています。ホームページの内装・改装のページにも、自動で出ます。'
	   . ' 直すときは <a href="' . esc_url( $url ) . '">クロス・床の一覧</a> からどうぞ。</p></div>';
} );


/* ============================================================
   4. 見た目
   ============================================================ */
add_action( 'admin_head', function () {
	if ( ymkrf_pk_list_cat() === '' ) return;
	?>
	<style id="ymkrf-pkl-css">
	.wp-list-table .column-title{width:auto}
	.wp-list-table .column-ymkrf_pk_size{width:110px}
	.wp-list-table .column-ymkrf_pk_price{width:140px}
	.wp-list-table .column-ymkrf_pk_photo{width:120px}
	.wp-list-table .column-ymkrf_pk_group{width:140px}
	.wp-list-table .column-date{width:150px}
	.ymkrf-pkl__size{display:inline-block;padding:3px 10px;border-radius:999px;background:#fff1eb;color:#c92800;font-weight:700;white-space:nowrap}
	.ymkrf-pkl__man{font-size:15px;color:#c92800}
	.ymkrf-pkl__yen{color:#787c82}
	.ymkrf-pkl__red{color:#b32d2e}
	.ymkrf-pkl__none{color:#a7aaad}
	.ymkrf-pkl__ph img{display:block;width:64px;height:64px;object-fit:cover;border-radius:6px}
	.ymkrf-pkl__ph.is-shared img{opacity:.55}
	.ymkrf-pkl__share{display:block;margin-top:3px;color:#787c82;font-size:11px}
	</style>
	<?php
} );
