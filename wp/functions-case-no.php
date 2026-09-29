<?php
/**
 * functions-case-no.php ─ 案件番号（お客様の声と施工事例をつなぐ番号）
 * 置き場所： wp-content/themes/ymkrf/inc/functions-case-no.php
 *
 * （2026/09/29 ユーザー指示
 *   「案件番号ですが、1つのアンケートに2つ以上の工事の案件番号がある
 *     場合があります。その場合、「,」カンマで区切ることで対応して。
 *     これにより、お客様の声と施工事例が紐づく様にして。
 *     同様に施工事例もカンマで対応して」）
 *
 * ■ どういうものか
 *   1枚のアンケートで、2つ以上の工事をまとめてお答えいただくことがあります。
 *   そのときは、案件番号の欄に「2604-0365,2605-0083」のように
 *   カンマで区切って入れてください。
 *
 *   施工事例の側も同じです。どちらかに書いてあれば、つながります。
 *   「2604-0365」だけのお客様の声と、「2604-0365,2605-0083」の施工事例も、
 *   番号が1つでも重なっていればつながります。
 *
 * ■ 書きかたのゆれは、保存のときに自動でそろえます
 *   ・全角の数字・ハイフン・カンマ  → 半角
 *   ・読点（、）・スラッシュ・空白  → カンマ
 *   ・26030435                     → 2603-0435（ハイフン抜けを直します）
 *   ・同じ番号が2つ               → 1つにまとめます
 *
 * ■ 名前について
 *   _ymkrf_case_no      … 案件番号（カンマ区切りの文字）※これまでどおり
 *   _ymkrf_case_no_more … 昔の入れ物。読むときだけ見ます（新しくは書きません）
 *   _ymkrf_case_any     … さがすための控え。番号1つにつき1行ずつ入ります
 *                         （番号がいくつあっても、すばやくさがせます）
 */
if ( ! defined( 'ABSPATH' ) ) exit;


/** この番号を使う投稿の種類 */
function ymkrf_case_types() {
	return array( 'ymkrf_voice', 'ymkrf_works' );
}


/* ============================================================
   1. 書きかたをそろえる
   ============================================================ */

/**
 * 番号1つぶんを、決まった形にそろえます。
 * 直せないときは、そのまま（空文字ではなく）返します。
 */
function ymkrf_case_no_norm( $s ) {

	$s = trim( (string) $s );
	if ( $s === '' ) return '';

	/* 全角 → 半角 */
	$s = mb_convert_kana( $s, 'as', 'UTF-8' );
	$s = str_replace( array( '−', '―', '‐', '–', '—', 'ー' ), '-', $s );
	$s = preg_replace( '/\s+/u', '', $s );

	/* 4桁-4桁 … そのまま */
	if ( preg_match( '/^\d{4}-\d{4}$/', $s ) ) return $s;

	/* 8桁 … まん中にハイフンを入れます（26030435 → 2603-0435） */
	if ( preg_match( '/^\d{8}$/', $s ) ) return substr( $s, 0, 4 ) . '-' . substr( $s, 4 );

	/* 枝番つき（2205-0348-07）などは、そのまま残します */
	return $s;
}

/**
 * カンマ区切りの文字を、番号の配列にします。
 * 読点・スラッシュ・空白で区切っていても読みます。
 */
function ymkrf_case_no_split( $text ) {

	$text = (string) $text;
	if ( trim( $text ) === '' ) return array();

	$text = mb_convert_kana( $text, 'as', 'UTF-8' );
	$text = str_replace( array( '、', '，', '/', '／', '・', "\n", "\r", "\t", ' ' ), ',', $text );

	$out = array();
	foreach ( explode( ',', $text ) as $one ) {
		$one = ymkrf_case_no_norm( $one );
		if ( $one === '' ) continue;
		if ( in_array( $one, $out, true ) ) continue;   /* 同じ番号は1つに */
		$out[] = $one;
	}
	return $out;
}

/**
 * その投稿が持っている案件番号を、ぜんぶ返します。
 * 昔の入れ物（_ymkrf_case_no_more）に入っているぶんも足します。
 */
function ymkrf_case_no_list( $post_id ) {

	$post_id = (int) $post_id;
	if ( ! $post_id ) return array();

	$nos = ymkrf_case_no_split( get_post_meta( $post_id, '_ymkrf_case_no', true ) );

	foreach ( ymkrf_case_no_split( get_post_meta( $post_id, '_ymkrf_case_no_more', true ) ) as $one ) {
		if ( ! in_array( $one, $nos, true ) ) $nos[] = $one;
	}
	return $nos;
}

/** 画面に出すときの形（「2604-0365 ／ 2605-0083」） */
function ymkrf_case_no_label( $post_id, $sep = ' ／ ' ) {
	return implode( $sep, ymkrf_case_no_list( $post_id ) );
}


/* ============================================================
   2. さがすための控えを作りなおす
   ============================================================ */

/**
 * _ymkrf_case_no をそろえて書きなおし、_ymkrf_case_any を作りなおします。
 * 戻り値は、そろえたあとの番号の配列です。
 */
function ymkrf_case_no_sync( $post_id ) {

	$post_id = (int) $post_id;
	if ( ! $post_id ) return array();

	$nos = ymkrf_case_no_list( $post_id );

	/* 本体は、そろえた形で入れなおします */
	$joined = implode( ',', $nos );
	if ( (string) get_post_meta( $post_id, '_ymkrf_case_no', true ) !== $joined ) {
		if ( $joined === '' ) {
			delete_post_meta( $post_id, '_ymkrf_case_no' );
		} else {
			update_post_meta( $post_id, '_ymkrf_case_no', $joined );
		}
	}

	/* 2つ目からを別に入れていた昔の入れ物は、役目を終えたので消します */
	if ( get_post_meta( $post_id, '_ymkrf_case_no_more', true ) !== '' ) {
		delete_post_meta( $post_id, '_ymkrf_case_no_more' );
	}

	/* さがすための控え。番号1つにつき1行 */
	$now = get_post_meta( $post_id, '_ymkrf_case_any' );
	$now = is_array( $now ) ? array_map( 'strval', $now ) : array();

	sort( $now );
	$want = $nos;
	sort( $want );
	if ( $now === $want ) return $nos;   /* 変わっていなければ、さわりません */

	delete_post_meta( $post_id, '_ymkrf_case_any' );
	foreach ( $nos as $one ) add_post_meta( $post_id, '_ymkrf_case_any', $one );

	return $nos;
}

/* 保存のたびに、そろえなおします */
foreach ( ymkrf_case_types() as $ymkrf_case_t ) {
	add_action( "save_post_{$ymkrf_case_t}", function ( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( wp_is_post_revision( $post_id ) ) return;
		ymkrf_case_no_sync( $post_id );
	}, 30 );
}
unset( $ymkrf_case_t );


/* ============================================================
   3. つながっている相手をさがす
   ============================================================ */

/**
 * 番号が1つでも重なっている投稿を返します。
 *
 * $case … 番号の配列、またはカンマ区切りの文字
 */
function ymkrf_case_find( $case, $post_type, $limit = 10 ) {

	$nos = is_array( $case ) ? $case : ymkrf_case_no_split( $case );
	$nos = array_values( array_filter( array_map( 'ymkrf_case_no_norm', $nos ) ) );
	if ( ! $nos ) return array();

	return get_posts( array(
		'post_type'      => $post_type,
		'posts_per_page' => (int) $limit,
		'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
		'fields'         => 'ids',
		'orderby'        => 'date',
		'order'          => 'DESC',
		'no_found_rows'  => true,
		'meta_query'     => array( array(
			'key'     => '_ymkrf_case_any',
			'value'   => $nos,
			'compare' => 'IN',
		) ),
	) );
}

/** その投稿とつながっている相手（自分自身は外します） */
function ymkrf_case_linked( $post_id, $post_type, $limit = 10 ) {

	$ids = ymkrf_case_find( ymkrf_case_no_list( $post_id ), $post_type, $limit + 1 );
	return array_values( array_diff( array_map( 'intval', $ids ), array( (int) $post_id ) ) );
}


/* ============================================================
   4. いまある投稿の控えを、1回だけ作ります
   ------------------------------------------------------------
   お客様の声 1,600件・施工事例 2,100件ぶんの控えを作ります。
   管理画面をひらいたときに、一度だけ動きます。
   もう一度走らせたいときは、下の版の日付を新しくしてください。
   ============================================================ */
add_action( 'admin_init', function () {

	$ver = '2026-09-29-1';
	if ( get_option( 'ymkrf_case_any_ver' ) === $ver ) return;
	if ( ! current_user_can( 'edit_posts' ) ) return;

	global $wpdb;

	$types = "'" . implode( "','", array_map( 'esc_sql', ymkrf_case_types() ) ) . "'";

	$rows = $wpdb->get_results(
		"SELECT p.ID,
		        MAX( CASE WHEN m.meta_key = '_ymkrf_case_no'      THEN m.meta_value END ) AS no1,
		        MAX( CASE WHEN m.meta_key = '_ymkrf_case_no_more' THEN m.meta_value END ) AS no2
		   FROM {$wpdb->posts} AS p
		   INNER JOIN {$wpdb->postmeta} AS m ON m.post_id = p.ID
		  WHERE p.post_type IN ( {$types} )
		    AND m.meta_key IN ( '_ymkrf_case_no', '_ymkrf_case_no_more' )
		  GROUP BY p.ID"
	);

	$n = 0;
	foreach ( (array) $rows as $r ) {

		$nos = ymkrf_case_no_split( $r->no1 );
		foreach ( ymkrf_case_no_split( $r->no2 ) as $one ) {
			if ( ! in_array( $one, $nos, true ) ) $nos[] = $one;
		}
		if ( ! $nos ) continue;

		$joined = implode( ',', $nos );
		if ( (string) $r->no1 !== $joined ) update_post_meta( (int) $r->ID, '_ymkrf_case_no', $joined );
		if ( $r->no2 !== null )             delete_post_meta( (int) $r->ID, '_ymkrf_case_no_more' );

		delete_post_meta( (int) $r->ID, '_ymkrf_case_any' );
		foreach ( $nos as $one ) add_post_meta( (int) $r->ID, '_ymkrf_case_any', $one );

		$n++;
	}

	update_option( 'ymkrf_case_any_ver', $ver, false );
	if ( $n ) set_transient( 'ymkrf_case_any_made', $n, 180 );
}, 12 );

add_action( 'admin_notices', function () {

	$n = get_transient( 'ymkrf_case_any_made' );
	if ( ! $n ) return;
	delete_transient( 'ymkrf_case_any_made' );

	echo '<div class="notice notice-success is-dismissible"><p>'
	   . '案件番号を整理しました（' . number_format( (int) $n ) . ' 件）。'
	   . 'カンマ区切りで2つ以上の番号が入っていても、'
	   . 'お客様の声と施工事例がつながるようになりました。'
	   . '</p></div>';
} );
