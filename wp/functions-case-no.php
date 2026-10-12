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


/* ============================================================
   5. 案件番号のダブりを知らせます
   ------------------------------------------------------------
   （2026/10/12 ユーザー指示
     「お客様の声、施工事例、同じ案件番号で重複できない様に
       登録の際にもし重複であれば、コメントで教えてほしい」）

   ★保存じたいは止めません。
     1つの工事をわざと2件に分けたいこともあり得るので、
     こちらで勝手に消したり、保存を拒んだりはしません。
     「同じ番号のものが、もうありますよ」とお知らせするだけです。

   お客様の声どうし・施工事例どうしで見ます。
   お客様の声と施工事例のあいだは、つながるのが正しいので見ません。
   ============================================================ */

/** 同じ種類の中で、番号がぶつかっている相手をさがします */
function ymkrf_case_dupes( $post_id ) {

	$post_id = (int) $post_id;
	$type    = get_post_type( $post_id );
	if ( ! in_array( $type, ymkrf_case_types(), true ) ) return array();

	$nos = ymkrf_case_no_list( $post_id );
	if ( ! $nos ) return array();

	$out = array();
	foreach ( $nos as $no ) {
		foreach ( ymkrf_case_find( array( $no ), $type, 20 ) as $id ) {
			$id = (int) $id;
			if ( $id === $post_id ) continue;
			if ( get_post_status( $id ) === 'trash' ) continue;
			if ( ! isset( $out[ $id ] ) ) $out[ $id ] = array();
			if ( ! in_array( $no, $out[ $id ], true ) ) $out[ $id ][] = $no;
		}
	}
	return $out;
}

/* 保存のたびに、ぶつかっていないか見ます */
foreach ( ymkrf_case_types() as $ymkrf_dup_t ) {
	add_action( "save_post_{$ymkrf_dup_t}", function ( $post_id, $post ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( wp_is_post_revision( $post_id ) ) return;
		if ( ! $post || $post->post_status === 'auto-draft' ) return;

		$d = ymkrf_case_dupes( $post_id );

		if ( $d ) set_transient( 'ymkrf_case_dupe_' . $post_id, $d, 120 );
		else      delete_transient( 'ymkrf_case_dupe_' . $post_id );

	}, 40, 2 );
}
unset( $ymkrf_dup_t );

/* 編集画面のいちばん上に、赤いお知らせを出します */
add_action( 'admin_notices', function () {

	$s = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $s || $s->base !== 'post' ) return;
	if ( ! in_array( $s->post_type, ymkrf_case_types(), true ) ) return;

	$id = isset( $GLOBALS['post'] ) ? (int) $GLOBALS['post']->ID : 0;
	if ( ! $id ) return;

	/* 保存した直後は控えを使い、ふだんはその場で数えます */
	$d = get_transient( 'ymkrf_case_dupe_' . $id );
	if ( $d === false ) $d = ymkrf_case_dupes( $id );
	if ( ! $d ) return;

	$label = ( $s->post_type === 'ymkrf_voice' ) ? 'お客様の声' : '施工事例';

	echo '<div class="notice notice-error"><p><b>'
	   . '同じ案件番号の' . esc_html( $label ) . 'が、すでに登録されています。</b></p>'
	   . '<ul style="margin:0 0 10px 1.4em;list-style:disc">';

	foreach ( $d as $other => $nos ) {
		$t = trim( (string) get_the_title( $other ) );
		if ( $t === '' ) $t = '（題名なし）';
		echo '<li><b>' . esc_html( implode( '／', $nos ) ) . '</b>　'
		   . '<a href="' . esc_url( (string) get_edit_post_link( $other ) ) . '" target="_blank" rel="noopener">'
		   . esc_html( wp_trim_words( $t, 18, '…' ) ) . '</a>'
		   . '（' . esc_html( get_post_status( $other ) === 'publish' ? '公開済み' : '下書きなど' ) . '）</li>';
	}

	echo '</ul><p>二重登録でしたら、どちらかをゴミ箱に入れてください。'
	   . 'わざと2件に分けている場合は、このままで大丈夫です。</p></div>';
} );

/* 一覧でも、ダブっている行に印を付けます */
add_action( 'admin_footer-edit.php', function () {

	$s = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $s || ! in_array( $s->post_type, ymkrf_case_types(), true ) ) return;
	if ( empty( $GLOBALS['wp_query']->posts ) ) return;

	$hit = array();
	foreach ( $GLOBALS['wp_query']->posts as $p ) {
		if ( ymkrf_case_dupes( $p->ID ) ) $hit[] = (int) $p->ID;
	}
	if ( ! $hit ) return;

	echo '<style>.ymkrf-dupe{color:#d63638;font-weight:700;margin-left:6px;white-space:nowrap}</style>';
	echo '<script>(function(){var ids=' . wp_json_encode( $hit ) . ';'
	   . 'ids.forEach(function(id){var tr=document.getElementById("post-"+id);if(!tr)return;'
	   . 'var a=tr.querySelector(".row-title");if(!a||a.parentNode.querySelector(".ymkrf-dupe"))return;'
	   . 'var s=document.createElement("span");s.className="ymkrf-dupe";'
	   . 's.textContent="⚠ 番号ダブり";'
	   . 'a.parentNode.insertBefore(s,a.nextSibling);});})();</script>';
} );


/* ============================================================
   6. ダブった案件番号では登録できないようにします
   ------------------------------------------------------------
   （2026/10/12 ユーザー指示
     「お客様の声と施工事例は絶対に重複しないので、登録できないように
       してほしい。案件番号入れた時点で重複していたら、入力する前に
       すぐ教えてほしい」）

   ■ 2段がまえです
     ① 打った瞬間に知らせる
        案件番号の欄から手をはなした時点でしらべ、ダブっていたら
        欄の下に赤く出し、「公開」「更新」のボタンを押せなくします。
     ② 保存しようとしても止める
        もしボタンをすり抜けても、公開にはなりません。
        下書きのまま止めて、赤いお知らせを出します。

   お客様の声どうし・施工事例どうしで見ます。
   お客様の声と施工事例のあいだは、つながるのが正しいので見ません。
   ============================================================ */

/* ---- ① 打った瞬間にしらべる ---- */
add_action( 'wp_ajax_ymkrf_case_check', function () {

	check_ajax_referer( 'ymkrf_case_check', 'nonce' );
	if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error();

	$type = isset( $_POST['ptype'] ) ? sanitize_key( wp_unslash( $_POST['ptype'] ) ) : '';
	$self = isset( $_POST['post'] )  ? (int) $_POST['post'] : 0;
	$text = isset( $_POST['caseno'] ) ? (string) wp_unslash( $_POST['caseno'] ) : '';

	if ( ! in_array( $type, ymkrf_case_types(), true ) ) wp_send_json_error();

	$nos = ymkrf_case_no_split( $text );
	if ( ! $nos ) wp_send_json_success( array( 'dupe' => array(), 'nos' => array() ) );

	$out = array();
	foreach ( $nos as $no ) {
		foreach ( ymkrf_case_find( array( $no ), $type, 10 ) as $id ) {
			$id = (int) $id;
			if ( $id === $self ) continue;
			if ( get_post_status( $id ) === 'trash' ) continue;
			$t = trim( (string) get_the_title( $id ) );
			$out[] = array(
				'no'    => $no,
				'title' => ( $t !== '' ? wp_trim_words( $t, 16, '…' ) : '（題名なし）' ),
				'url'   => (string) get_edit_post_link( $id, 'raw' ),
				'state' => ( get_post_status( $id ) === 'publish' ? '公開済み' : '下書きなど' ),
			);
		}
	}
	wp_send_json_success( array( 'dupe' => $out, 'nos' => $nos ) );
} );

add_action( 'admin_footer-post.php', 'ymkrf_case_check_js' );
add_action( 'admin_footer-post-new.php', 'ymkrf_case_check_js' );

function ymkrf_case_check_js() {

	$s = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $s || ! in_array( $s->post_type, ymkrf_case_types(), true ) ) return;

	$label = ( $s->post_type === 'ymkrf_voice' ) ? 'お客様の声' : '施工事例';
	$pid   = isset( $GLOBALS['post'] ) ? (int) $GLOBALS['post']->ID : 0;
	?>
	<style>
	  .ymkrf-caseng-box{
	    margin:8px 0 0; padding:10px 12px; border-radius:6px;
	    background:#fcf0f1; border:2px solid #d63638; color:#8a1f21;
	    font-size:13px; line-height:1.8; display:none;
	  }
	  .ymkrf-caseng-box.is-on{ display:block; }
	  .ymkrf-caseng-box a{ color:#8a1f21; font-weight:700; }
	  input[name="_ymkrf_case_no"].is-ng{
	    border-color:#d63638 !important; box-shadow:0 0 0 1px #d63638 inset;
	    background:#fff6f6;
	  }
	  .ymkrf-casewait{ margin-left:10px; color:#646970; font-size:12px; }
	</style>
	<script>
	jQuery(function ($) {

	  var $in = $('input[name="_ymkrf_case_no"]');
	  if (!$in.length) return;

	  var $box = $('<p class="ymkrf-caseng-box"></p>').insertAfter($in.closest('label').length ? $in.closest('label') : $in);
	  var timer = null, lastNg = false;

	  function buttons(off) {
	    $('#publish, #save-post, .editor-post-publish-button').prop('disabled', !!off)
	      .css('opacity', off ? 0.5 : '').css('pointer-events', off ? 'none' : '');
	  }

	  function check() {
	    var v = $.trim($in.val() || '');
	    if (!v) { $box.removeClass('is-on').empty(); $in.removeClass('is-ng'); if (lastNg) { buttons(false); lastNg = false; } return; }

	    $.post(ajaxurl, {
	      action: 'ymkrf_case_check',
	      nonce:  '<?php echo esc_js( wp_create_nonce( 'ymkrf_case_check' ) ); ?>',
	      ptype:  '<?php echo esc_js( $s->post_type ); ?>',
	      post:   <?php echo (int) $pid; ?>,
	      caseno: v
	    }).done(function (res) {

	      if (!res || !res.success) return;
	      var d = res.data.dupe || [];

	      if (!d.length) {
	        $box.removeClass('is-on').empty();
	        $in.removeClass('is-ng');
	        if (lastNg) { buttons(false); lastNg = false; }
	        return;
	      }

	      var html = '<b>この案件番号の<?php echo esc_js( $label ); ?>は、すでに登録されています。</b>'
	               + '<br>番号を直すか、先に登録してあるほうを開いてください。'
	               + '（このままでは登録できません）<ul style="margin:6px 0 0 1.4em;list-style:disc">';
	      d.forEach(function (o) {
	        html += '<li>' + o.no + '　<a href="' + o.url + '" target="_blank" rel="noopener">'
	              + o.title + '</a>（' + o.state + '）</li>';
	      });
	      html += '</ul>';

	      $box.html(html).addClass('is-on');
	      $in.addClass('is-ng');
	      buttons(true); lastNg = true;
	    });
	  }

	  $in.on('input', function () {
	    clearTimeout(timer);
	    timer = setTimeout(check, 500);
	  }).on('blur', check);

	  check();   /* 開いたときにも、いちど見ます */
	});
	</script>
	<?php
}

/* ---- ② 保存しようとしても、公開にはしません ---- */
add_filter( 'wp_insert_post_data', function ( $data, $postarr ) {

	if ( empty( $data['post_type'] ) ) return $data;
	if ( ! in_array( $data['post_type'], ymkrf_case_types(), true ) ) return $data;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return $data;
	if ( ! isset( $_POST['_ymkrf_case_no'] ) ) return $data;   /* クイック編集などは、そのまま */

	$self = ! empty( $postarr['ID'] ) ? (int) $postarr['ID'] : 0;
	$nos  = ymkrf_case_no_split( (string) wp_unslash( $_POST['_ymkrf_case_no'] ) );
	if ( ! $nos ) return $data;

	$hit = array();
	foreach ( $nos as $no ) {
		foreach ( ymkrf_case_find( array( $no ), $data['post_type'], 10 ) as $id ) {
			$id = (int) $id;
			if ( $id === $self ) continue;
			if ( get_post_status( $id ) === 'trash' ) continue;
			$hit[ $id ] = $no;
		}
	}
	if ( ! $hit ) return $data;

	/* 公開しようとしていたら、下書きで止めます */
	if ( in_array( $data['post_status'], array( 'publish', 'future', 'private' ), true ) ) {
		$data['post_status'] = 'draft';
	}
	set_transient( 'ymkrf_case_block_' . get_current_user_id(), $hit, 120 );

	return $data;
}, 20, 2 );

add_action( 'admin_notices', function () {

	$s = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $s || $s->base !== 'post' ) return;
	if ( ! in_array( $s->post_type, ymkrf_case_types(), true ) ) return;

	$hit = get_transient( 'ymkrf_case_block_' . get_current_user_id() );
	if ( ! $hit ) return;
	delete_transient( 'ymkrf_case_block_' . get_current_user_id() );

	$label = ( $s->post_type === 'ymkrf_voice' ) ? 'お客様の声' : '施工事例';

	echo '<div class="notice notice-error"><p><b>'
	   . '案件番号がダブっているので、公開しませんでした（下書きのままです）。</b></p>'
	   . '<p>同じ番号の' . esc_html( $label ) . 'が、すでに登録されています。'
	   . '番号を直すか、先に登録してあるほうを開いてください。</p>'
	   . '<ul style="margin:0 0 10px 1.4em;list-style:disc">';

	foreach ( $hit as $id => $no ) {
		$t = trim( (string) get_the_title( $id ) );
		echo '<li><b>' . esc_html( $no ) . '</b>　'
		   . '<a href="' . esc_url( (string) get_edit_post_link( $id ) ) . '" target="_blank" rel="noopener">'
		   . esc_html( $t !== '' ? wp_trim_words( $t, 16, '…' ) : '（題名なし）' ) . '</a></li>';
	}
	echo '</ul></div>';
} );
