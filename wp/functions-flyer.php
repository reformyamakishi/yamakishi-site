<?php
/**
 * イベント・チラシ ─ リフォームヤマキシ
 *
 * 置き場所： wp-content/themes/ymkrf/inc/functions-flyer.php
 *            （functions.php から読み込みます）
 *
 * ── なぜこの作りにしたか ─────────────────────────────
 * いまのサイトのチラシページは、10店舗ぶんのチラシ（表・裏で約20枚）を
 * 1ページで全部読み込んでいました。そのため
 *   ・スマホだと表示までに時間がかかる
 *   ・自分の店のチラシがどこにあるのか分からない
 * という2つの困りごとが起きていました。
 *
 * そこで
 *   ① お店を先にえらんでいただき、そのお店のチラシだけを出す
 *      （画像は、えらばれたぶんしか読み込みません）
 *   ② チラシの中身を、サイトに登録ずみの商品からも読めるようにする
 *   ③ 管理画面から、画像2枚と期間を入れるだけで差し替えられるようにする
 * という形にしました。
 *
 * ── お店ごとにチラシが違うとき ───────────────────────
 * チラシの編集画面の右側「対象のお店」で、お店にチェックを入れます。
 *   ・どこにもチェックを入れない … 全店共通のチラシです
 *   ・お店にチェックを入れる　　　… そのお店だけのチラシです
 *
 * お客様のページには、そのお店のチラシ ＋ 全店共通のチラシ の両方が出ます。
 * 並びは「そのお店のもの → 全店共通」です。
 *
 * ── 出す日・やめる日 ────────────────────────────
 * 右の「公開」の欄だけで決めます（2026/09/03 ユーザー要望）。
 *   出す日　… WordPressの公開日時を先にして「予約投稿」
 *   やめる日… 「予約非公開日時」にチェックを入れて日時を入れる
 * 編集画面のいちばん上に、いま出ているかどうかを大きく出しています。
 *
 * ── 古いチラシのかたづけ ──────────────────────────
 * 予約非公開日時をすぎて21日たつと、チラシと画像を自動で削除します。
 * 下書き・予約済み、「自動で削除しない」のもの、ほかでも使っている画像は
 * 消しません。くわしくは「3-c」を見てください。
 *
 * ── 掲載商品について ────────────────────────────
 * 「チラシに載せた商品」は、サイトに登録ずみの商品からえらぶだけです。
 * 価格や写真は商品ページのものをそのまま使うので、
 * 毎月の打ち込みは要りませんし、値段が古いままになることもありません。
 */
if ( ! defined( 'ABSPATH' ) ) exit;


/* ============================================================
   1. 投稿タイプ「イベント・チラシ」
   ------------------------------------------------------------
   1件ずつのページ（/flyer/xxxx/）は作りません。
   お客様がご覧になるのは /flyer/ の1ページだけです。
   ============================================================ */
add_action( 'init', function () {

	register_post_type( 'ymkrf_flyer', array(
		'label'  => 'イベント・チラシ',
		'labels' => array(
			'name'          => 'イベント・チラシ',
			'singular_name' => 'チラシ',
			'add_new'       => '新規追加',
			'add_new_item'  => 'チラシを新規追加',
			'edit_item'     => 'チラシを編集',
			'all_items'     => 'チラシ一覧',
			'search_items'  => 'チラシを検索',
		),
		'public'              => false,   /* 1件ずつのページは作りません */
		'show_ui'             => true,
		/* メニューは下の「4. ダッシュボードのメニュー」で自分で作ります。
		   お店をえらぶ画面をいちばん先に出したいためです。 */
		'show_in_menu'        => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'has_archive'         => false,
		'rewrite'             => false,
		'menu_icon'           => 'dashicons-tickets-alt',
		/* editor（本文）は supports からはずし、下でたためる箱として出しています。
		   ふだん使わない欄が、タイトルのすぐ下を占めてしまうためです。 */
		'supports'            => array( 'title', 'page-attributes' ),
		'show_in_rest'        => false,
	) );

}, 6 );

/* タイトル欄に、うすい文字で出す案内 */
add_filter( 'enter_title_here', function ( $t, $post ) {
	return ( $post && $post->post_type === 'ymkrf_flyer' ) ? 'タイトル（例：秋のリフォームフェア！）' : $t;
}, 10, 2 );

/* 「対象のお店」は、商品・スタッフと同じ ymkrf_shop を使いまわします。
   お店を1か所で直せるようにするためです。
   ymkrf_shop は inc/functions-product.php で init のふつうの順番（10）に
   作られるので、ここはそのあと（11）で結びつけます。 */
add_action( 'init', function () {
	if ( taxonomy_exists( 'ymkrf_shop' ) ) {
		register_taxonomy_for_object_type( 'ymkrf_shop', 'ymkrf_flyer' );
	}
}, 11 );


/* 昔ながらの編集画面にします。
   ブロックエディターだと、右側の「チラシの中身」の欄が
   はしに埋もれて見つけにくくなるためです。 */
add_filter( 'use_block_editor_for_post_type', function ( $use, $type ) {
	return ( $type === 'ymkrf_flyer' ) ? false : $use;
}, 10, 2 );


/* ============================================================
   2. チラシのページで読み込むCSS
   ============================================================ */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! function_exists( 'ymkrf_is_flyer' ) || ! ymkrf_is_flyer() ) return;
	$dir = get_stylesheet_directory_uri();
	$ver = defined( 'YMKRF_VER' ) ? YMKRF_VER : null;
	/* 商品の札（.p-cat__card）をそのまま使うので product.css も読みます */
	wp_enqueue_style( 'ymkrf-lp',      $dir . '/assets/css/lp.css',      array( 'ymkrf-common', 'ymkrf-page' ), $ver );
	wp_enqueue_style( 'ymkrf-product', $dir . '/assets/css/product.css', array( 'ymkrf-page' ), $ver );
	wp_enqueue_style( 'ymkrf-flyer',   $dir . '/assets/css/flyer.css',   array( 'ymkrf-lp' ), $ver );
}, 20 );


/* ============================================================
   3. 編集画面の欄
   ============================================================ */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'ymkrf_flyer_main', 'チラシの画像',
		'ymkrf_flyer_box_main', 'ymkrf_flyer', 'normal', 'high' );
	add_meta_box( 'ymkrf_flyer_prd', 'チラシに載せた商品',
		'ymkrf_flyer_box_prd', 'ymkrf_flyer', 'normal', 'default' );

	/* 本文。ふだんは使わないので、たたんだ状態で出します
	   （2026/09/03 ユーザー要望）。見出しを押すと開きます。 */
	add_meta_box( 'ymkrf_flyer_body', '本文',
		'ymkrf_flyer_box_body', 'ymkrf_flyer', 'normal', 'low' );

	/* お店の欄は、そのままだと商品と同じ「展示店舗」という題名になってしまい、
	   並び順もばらばらです。チラシ用に作りなおします。 */
	remove_meta_box( 'ymkrf_shopdiv', 'ymkrf_flyer', 'side' );
	/* 右のせまい列だと11店舗が入りきらず、スクロールが必要でした。
	   左（本文と同じ列）のいちばん上に置いて、全店を一度に出します
	   （2026/09/03 ユーザー要望）。 */
	add_meta_box( 'ymkrf_flyer_shop', 'このチラシを出すお店',
		'ymkrf_flyer_box_shop', 'ymkrf_flyer', 'normal', 'high' );
}, 20 );

/* WordPress は、いちど出した欄の置き場所をユーザーごとに覚えています。
   そのままだと「お店」の欄が右のままになってしまうので、
   この画面の記憶だけを一度きり消します（2026/09/03）。
   このあとは、ご自分でドラッグして動かした位置を覚えます。 */
add_filter( 'get_user_option_meta-box-order_ymkrf_flyer', function ( $order ) {
	if ( ! is_array( $order ) ) return $order;

	/* 覚えている並びから「お店」の欄だけを取りのぞきます。
	   そうすると、上の add_meta_box で決めた左の位置がそのまま使われます。
	   ほかの欄は、これまでどおりドラッグで動かせます。 */
	foreach ( $order as $ctx => $ids ) {
		$keep = array_filter(
			explode( ',', (string) $ids ),
			function ( $id ) { return $id !== '' && $id !== 'ymkrf_flyer_shop'; }
		);
		$order[ $ctx ] = implode( ',', $keep );
	}
	return $order;
} );

/**
 * 「このチラシを出すお店」の欄。
 *
 * ・お店の一覧は inc/functions-shops.php の並び（県ごと）に合わせています
 * ・お店をえらぶ画面から「＋追加」で来たときは、そのお店にはじめから
 *   チェックが入っています（?ymkrf_shop=komathu）
 */
function ymkrf_flyer_box_shop( $post ) {
	wp_nonce_field( 'ymkrf_flyer_shop_save', 'ymkrf_flyer_shop_nonce' );

	$now = wp_get_object_terms( $post->ID, 'ymkrf_shop', array( 'fields' => 'slugs' ) );
	if ( is_wp_error( $now ) ) $now = array();

	/* 新規追加でお店の指定つきのときは、はじめからチェックを入れます */
	if ( ! $now && get_post_status( $post ) === 'auto-draft' && ! empty( $_GET['ymkrf_shop'] ) ) {
		$now = array( sanitize_key( wp_unslash( $_GET['ymkrf_shop'] ) ) );
	}

	$shops = function_exists( 'ymkrf_shops' ) ? ymkrf_shops() : array();

	/* この欄だけの並び順（2026/09/03 ユーザー指定）。
	   1段5つ出るので、よく使うお店が上の段にそろいます。
	     上の段 … 東金沢／羽咋／金沢野々市／金沢田上／小松
	     下の段 … そのほか
	   ここに書いていないお店は、そのままうしろに続きます。
	   ※店舗ページ（/shops/）などの並びは変えていません。 */
	$box_order = array(
		'higashikanazawa', 'hakui', 'nonoichi', 'tagami', 'komathu',
	);

	$by_pref = array();
	foreach ( $shops as $sp ) $by_pref[ $sp['pref'] ][] = $sp;

	foreach ( $by_pref as $pref => $list ) {
		usort( $list, function ( $a, $b ) use ( $box_order ) {
			$ia = array_search( $a['slug'], $box_order, true );
			$ib = array_search( $b['slug'], $box_order, true );
			if ( false === $ia ) $ia = PHP_INT_MAX;
			if ( false === $ib ) $ib = PHP_INT_MAX;
			return $ia <=> $ib;   /* 同じ順位のときは、もとの並びのままです */
		} );
		$by_pref[ $pref ] = $list;
	}
	?>
	<div class="ymkrf-fl__shop">
	  <p class="ymkrf-fl__shopall">
	    <label>
	      <input type="checkbox" id="ymkrf-fl-shopnone" <?php checked( ! $now ); ?>>
	      <b>全店共通にする</b>
	    </label>
	  </p>

	  <div id="ymkrf-fl-shoplist" class="ymkrf-fl__shoplist<?php echo $now ? '' : ' is-off'; ?>">
	    <?php foreach ( $by_pref as $pref => $list ) : ?>
	      <div class="ymkrf-fl__shopgrp">
	        <p class="ymkrf-fl__shoppref"><?php echo esc_html( $pref ); ?></p>
	        <div class="ymkrf-fl__shopgrid">
	          <?php foreach ( $list as $sp ) : ?>
	            <label class="ymkrf-fl__shopitem">
	              <input type="checkbox" class="ymkrf-fl__shopchk" name="ymkrf_flyer_shop[]"
	                     value="<?php echo esc_attr( $sp['slug'] ); ?>"
	                     <?php checked( in_array( $sp['slug'], $now, true ) ); ?>>
	              <span><?php echo esc_html( $sp['name'] ); ?></span>
	            </label>
	          <?php endforeach; ?>
	        </div>
	      </div>
	    <?php endforeach; ?>
	  </div>
	</div>
	<?php
}

add_action( 'save_post_ymkrf_flyer', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! isset( $_POST['ymkrf_flyer_shop_nonce'] ) ||
	     ! wp_verify_nonce( $_POST['ymkrf_flyer_shop_nonce'], 'ymkrf_flyer_shop_save' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	$slugs = isset( $_POST['ymkrf_flyer_shop'] ) && is_array( $_POST['ymkrf_flyer_shop'] )
		? array_map( 'sanitize_key', wp_unslash( $_POST['ymkrf_flyer_shop'] ) )
		: array();

	/* 空にすると「全店共通」になります */
	wp_set_object_terms( $post_id, $slugs ? $slugs : null, 'ymkrf_shop', false );
} );

/* ============================================================
   3-a. 右の「公開」の欄に「予約非公開日時」を足します
   ------------------------------------------------------------
   ★2026/09/03 ユーザー要望：
     チラシを出す日・やめる日は、右の「公開」の欄だけで決めます。
     （「掲載期間」の欄はなくしました）

       出す日　… WordPressの「公開日時」を先の日にして「予約投稿」
       やめる日… ここで足す「予約非公開日時」

     やめる日時をすぎると、お客様のページから消えます。
     記事そのものは消えませんし、下書きにも戻りません。
     日時をのばせば、また出ます。
   ============================================================ */
add_action( 'post_submitbox_misc_actions', function ( $post ) {
	if ( ! $post || $post->post_type !== 'ymkrf_flyer' ) return;

	$val = ymkrf_flyer_unpublish_at( $post->ID );          /* 'Y-m-d H:i' か '' */
	$in  = $val !== '' ? str_replace( ' ', 'T', $val ) : '';
	wp_nonce_field( 'ymkrf_flyer_off_save', 'ymkrf_flyer_off_nonce' );
	?>
	<div class="misc-pub-section ymkrf-fl__off">
	  <label class="ymkrf-fl__offchk">
	    <input type="checkbox" name="_ymkrf_flyer_off_on" id="ymkrf-fl-off-on" value="1" <?php checked( $val !== '' ); ?>>
	    <b>予約非公開日時</b>
	  </label>
	  <p class="ymkrf-fl__offbody" id="ymkrf-fl-off-body"<?php echo $val === '' ? ' hidden' : ''; ?>>
	    <input type="datetime-local" name="_ymkrf_flyer_off" id="ymkrf-fl-off"
	           value="<?php echo esc_attr( $in ); ?>" style="width:100%">
	    <span class="description">この日時になると、お客様のページから自動で消えます。<br>
	      日時をのばせば、また出ます。</span>
	  </p>
	</div>

	<div class="misc-pub-section ymkrf-fl__keep">
	  <label class="ymkrf-fl__offchk">
	    <input type="checkbox" name="_ymkrf_flyer_keep" value="1"
	      <?php checked( get_post_meta( $post->ID, '_ymkrf_flyer_keep', true ) === '1' ); ?>>
	    <b>自動で削除しない（ずっと残す）</b>
	  </label>
	  <p class="description">
	    ふだんは、ページから消えて<b><?php echo (int) YMKRF_FLYER_KEEP_DAYS; ?>日</b>たつと、
	    このチラシと画像を自動で削除します。<br>
	    残しておきたいチラシは、ここにチェックを入れてください。
	  </p>
	  <?php $del = ymkrf_flyer_delete_at( $post->ID ); if ( $del !== '' ) : ?>
	    <p class="ymkrf-fl__delnote">
	      <b><?php echo esc_html( ymkrf_flyer_day_text( $del ) ); ?></b>ごろに自動で削除されます
	    </p>
	  <?php endif; ?>
	</div>
	<script>
	(function () {
	  var c = document.getElementById('ymkrf-fl-off-on');
	  var b = document.getElementById('ymkrf-fl-off-body');
	  var i = document.getElementById('ymkrf-fl-off');
	  if (!c || !b) return;
	  c.addEventListener('change', function () {
	    b.hidden = !c.checked;
	    /* はじめてチェックしたときは、1か月後を入れておきます */
	    if (c.checked && !i.value) {
	      var d = new Date(); d.setMonth(d.getMonth() + 1);
	      var p = function (n) { return ('0' + n).slice(-2); };
	      i.value = d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()) + 'T22:00';
	    }
	  });
	})();
	</script>
	<?php
} );

add_action( 'save_post_ymkrf_flyer', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! isset( $_POST['ymkrf_flyer_off_nonce'] ) ||
	     ! wp_verify_nonce( $_POST['ymkrf_flyer_off_nonce'], 'ymkrf_flyer_off_save' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	if ( ! empty( $_POST['_ymkrf_flyer_keep'] ) ) {
		update_post_meta( $post_id, '_ymkrf_flyer_keep', '1' );
	} else {
		delete_post_meta( $post_id, '_ymkrf_flyer_keep' );
	}

	$on  = ! empty( $_POST['_ymkrf_flyer_off_on'] );
	$raw = isset( $_POST['_ymkrf_flyer_off'] ) ? sanitize_text_field( wp_unslash( $_POST['_ymkrf_flyer_off'] ) ) : '';
	$raw = str_replace( 'T', ' ', trim( $raw ) );

	/* 「2026-09-28 22:00」の形だけ受けつけます */
	if ( ! $on || ! preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $raw ) ) {
		delete_post_meta( $post_id, '_ymkrf_flyer_off' );
		return;
	}
	update_post_meta( $post_id, '_ymkrf_flyer_off', $raw );
} );


/* ============================================================
   3-c. 古くなったチラシを自動でかたづけます
   ------------------------------------------------------------
   ★2026/09/03 ユーザー要望：
     「チラシは非表示になって3週間後に自動で削除してほしい。
       メディアも削除。下書きの場合は残す。」

   ── かならず守っていること ──────────────────────
     ・下書き／予約済みのチラシは、いっさい消しません
     ・「予約非公開日時」を決めていないチラシも消しません
       （いつまでも出しつづけるものなので）
     ・「自動で削除しない」にチェックのあるものは消しません
     ・画像は、ほかのチラシでも使われていたら消しません
     ・消える日は、編集画面と一覧にあらかじめ出しています

   ── いつ動くか ────────────────────────────
     1日に1回、WordPressの定期処理（wp-cron）で動きます。
     お客様のアクセスがきっかけで動くしくみなので、
     ぴったりの時刻ではなく「その日のうち」に消えます。
   ============================================================ */
if ( ! defined( 'YMKRF_FLYER_KEEP_DAYS' ) ) define( 'YMKRF_FLYER_KEEP_DAYS', 21 );   /* 3週間 */

/** 自動で削除される日時（'Y-m-d H:i'）。消さないものは空 */
if ( ! function_exists( 'ymkrf_flyer_delete_at' ) ) :
function ymkrf_flyer_delete_at( $post_id ) {
	if ( get_post_meta( $post_id, '_ymkrf_flyer_keep', true ) === '1' ) return '';
	if ( get_post_status( $post_id ) !== 'publish' ) return '';          /* 下書き・予約済みは残す */

	$off = ymkrf_flyer_unpublish_at( $post_id );
	if ( $off === '' ) return '';                                        /* 終わりを決めていない */

	$t = strtotime( $off );
	if ( ! $t ) return '';
	return gmdate( 'Y-m-d H:i', $t + YMKRF_FLYER_KEEP_DAYS * DAY_IN_SECONDS );
}
endif;

/* 1日に1回の見まわりを予約します */
add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'ymkrf_flyer_cleanup' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'ymkrf_flyer_cleanup' );
	}
}, 30 );

add_action( 'ymkrf_flyer_cleanup', 'ymkrf_flyer_do_cleanup' );

/** その画像が、ほかのチラシでも使われているか */
if ( ! function_exists( 'ymkrf_flyer_img_used_elsewhere' ) ) :
function ymkrf_flyer_img_used_elsewhere( $att_id, $except_post_id ) {
	if ( ! $att_id ) return true;   /* 念のため「使われている」扱い＝消しません */

	global $wpdb;

	/* ほかのチラシの表面・裏面に使われていないか */
	$n = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->postmeta}
		  WHERE meta_key IN ( '_ymkrf_flyer_front', '_ymkrf_flyer_back' )
		    AND meta_value = %s AND post_id <> %d",
		(string) (int) $att_id, (int) $except_post_id
	) );
	if ( $n > 0 ) return true;

	/* 商品・施工事例などのアイキャッチに使われていないか */
	$n = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->postmeta}
		  WHERE meta_key = '_thumbnail_id' AND meta_value = %s",
		(string) (int) $att_id
	) );
	if ( $n > 0 ) return true;

	/* どこかの本文に貼りこまれていないか（ファイル名で見ます） */
	$file = get_post_meta( $att_id, '_wp_attached_file', true );
	if ( $file ) {
		$base = wp_basename( $file );
		$dot  = strrpos( $base, '.' );
		if ( $dot ) $base = substr( $base, 0, $dot );      /* 拡張子とサイズ違いも拾えるように */
		if ( strlen( $base ) >= 4 ) {
			$n = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts}
				  WHERE post_status NOT IN ( 'trash', 'auto-draft' )
				    AND ID <> %d AND post_content LIKE %s",
				(int) $except_post_id, '%' . $wpdb->esc_like( $base ) . '%'
			) );
			if ( $n > 0 ) return true;
		}
	}

	return false;
}
endif;

/**
 * そのチラシにぶら下がっている画像のIDを、ぜんぶ集めます。
 *
 * ・表面・裏面にえらんでいるもの
 * ・そのチラシの編集画面からアップロードしたもの（post_parent）
 *   ← 入れかえてえらばれなくなった画像も、ここで拾えます。
 *     これを消さないと、使っていない画像がたまりつづけます。
 */
if ( ! function_exists( 'ymkrf_flyer_own_attachments' ) ) :
function ymkrf_flyer_own_attachments( $post_id ) {
	$ids = array();

	foreach ( array( '_ymkrf_flyer_front', '_ymkrf_flyer_back' ) as $k ) {
		$a = (int) get_post_meta( $post_id, $k, true );
		if ( $a ) $ids[] = $a;
	}

	$kids = get_posts( array(
		'post_type'      => 'attachment',
		'post_status'    => 'any',
		'post_parent'    => (int) $post_id,
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );
	foreach ( (array) $kids as $a ) $ids[] = (int) $a;

	return array_values( array_unique( array_filter( $ids ) ) );
}
endif;

/**
 * 見まわりの本体。
 * 消したものは option 'ymkrf_flyer_cleanup_log' に、直近20件だけ残します。
 */
if ( ! function_exists( 'ymkrf_flyer_do_cleanup' ) ) :
function ymkrf_flyer_do_cleanup() {

	$now = current_time( 'Y-m-d H:i' );
	$log = (array) get_option( 'ymkrf_flyer_cleanup_log', array() );

	$posts = get_posts( array(
		'post_type'      => 'ymkrf_flyer',
		'post_status'    => 'publish',          /* 下書き・予約済みは対象外 */
		'posts_per_page' => 50,
		'no_found_rows'  => true,
		'meta_query'     => array(
			array( 'key' => '_ymkrf_flyer_off', 'compare' => 'EXISTS' ),
			array( 'key' => '_ymkrf_flyer_keep', 'compare' => 'NOT EXISTS' ),
		),
	) );

	foreach ( $posts as $p ) {

		$due = ymkrf_flyer_delete_at( $p->ID );
		if ( $due === '' || $now < $due ) continue;

		/* 画像を先にかたづけます。
		   えらんでいる2枚だけでなく、そのチラシからアップロードした画像も
		   ぜんぶ見ます（入れかえて使わなくなったものが残らないように）。 */
		$gone = 0;
		$kept = 0;
		foreach ( ymkrf_flyer_own_attachments( $p->ID ) as $att ) {
			if ( ymkrf_flyer_img_used_elsewhere( $att, $p->ID ) ) { $kept++; continue; }
			if ( wp_delete_attachment( $att, true ) ) $gone++;
		}

		$title = get_the_title( $p->ID );
		wp_delete_post( $p->ID, true );

		array_unshift( $log, array(
			'when'  => current_time( 'Y-m-d H:i' ),
			'title' => ( $title !== '' ? $title : '（名前なし）' ),
			'imgs'  => $gone,
			'kept'  => $kept,
		) );
	}

	if ( $log ) update_option( 'ymkrf_flyer_cleanup_log', array_slice( $log, 0, 20 ), false );
}
endif;


/** 画像を1枚えらぶ欄をひとつ出します */
function ymkrf_flyer_imgfield( $key, $label, $post_id, $note = '' ) {
	$id  = (int) get_post_meta( $post_id, $key, true );
	$url = $id ? wp_get_attachment_image_url( $id, 'medium' ) : '';
	$dom = 'ymkrf-fl-' . str_replace( '_ymkrf_flyer_', '', $key );   /* 例 ymkrf-fl-front */
	?>
	<div class="ymkrf-fl__img">
	  <p class="ymkrf-fl__lbl"><b><?php echo esc_html( $label ); ?></b></p>
	  <div class="ymkrf-fl__prev" id="<?php echo esc_attr( $dom ); ?>-prev">
	    <?php if ( $url ) : ?>
	      <img src="<?php echo esc_url( $url ); ?>" alt="">
	    <?php else : ?>
	      <span class="ymkrf-fl__none">まだ入っていません</span>
	    <?php endif; ?>
	  </div>
	  <input type="hidden" name="<?php echo esc_attr( $key ); ?>"
	         id="<?php echo esc_attr( $dom ); ?>" value="<?php echo esc_attr( $id ); ?>">
	  <p>
	    <button type="button" class="button ymkrf-fl__pick"
	            data-target="<?php echo esc_attr( $dom ); ?>"
	            data-title="<?php echo esc_attr( $label . 'をえらぶ' ); ?>">画像をえらぶ</button>
	    <button type="button" class="button-link ymkrf-fl__clear"
	            data-target="<?php echo esc_attr( $dom ); ?>">はずす</button>
	  </p>
	  <?php if ( $note ) : ?><p class="description"><?php echo esc_html( $note ); ?></p><?php endif; ?>
	</div>
	<?php
}

function ymkrf_flyer_box_main( $post ) {
	wp_nonce_field( 'ymkrf_flyer_save', 'ymkrf_flyer_nonce' );
	$catch = (string) get_post_meta( $post->ID, '_ymkrf_flyer_catch', true );
	?>
	<p>
	  <label for="ymkrf-fl-catch"><b>チラシ有効期限</b></label><br>
	  <input type="text" class="widefat" id="ymkrf-fl-catch" name="_ymkrf_flyer_catch"
	         value="<?php echo esc_attr( $catch ); ?>"
	         placeholder="例：10/24（土）・10/25（日）　2日間かぎり">
	</p>

	<hr>

	<div class="ymkrf-fl__imgs">
	  <?php ymkrf_flyer_imgfield( '_ymkrf_flyer_front', 'チラシ 表面', $post->ID,
		'B4のたて・よこ、どちらでも大丈夫です。長いほうの辺が1600px以上のJPEGをおすすめします。' ); ?>
	  <?php ymkrf_flyer_imgfield( '_ymkrf_flyer_back', 'チラシ 裏面', $post->ID,
		'裏がないチラシのときは、空のままでかまいません。表面と同じ向きにしてください。' ); ?>
	</div>

	<?php
}

/**
 * 本文の欄。
 *
 * 投稿タイプの supports から editor をはずし、かわりにこの箱で出しています。
 * こうすると「たたんでおく」ことができ、ふだんは見出しだけになります。
 *
 * 入力欄の id / name を content にしてあるので、
 * WordPress がこれまでどおり本文として保存します。
 */
function ymkrf_flyer_box_body( $post ) {
	wp_editor( $post->post_content, 'content', array(
		'textarea_name' => 'content',
		'textarea_rows' => 8,
		'media_buttons' => true,
		'teeny'         => true,
		'tinymce'       => array( 'wp_autoresize_on' => true ),
	) );
}

/* はじめて開いたときは、本文の箱をたたんでおきます。
   一度ご自身で開け閉めされたあとは、その状態を覚えます。 */
add_filter( 'get_user_option_closedpostboxes_ymkrf_flyer', function ( $closed ) {
	return ( false === $closed ) ? array( 'ymkrf_flyer_body' ) : $closed;
} );

/* たたんだ中の入力欄は、開いたときに高さがつぶれることがあるので直します */
add_action( 'admin_footer', function () {
	$s = get_current_screen();
	if ( ! $s || $s->post_type !== 'ymkrf_flyer' || $s->base !== 'post' ) return;
	?>
	<script>
	jQuery(function ($) {
		$('#ymkrf_flyer_body .postbox-header, #ymkrf_flyer_body .handlediv').on('click', function () {
			setTimeout(function () {
				if (window.tinymce) {
					var ed = window.tinymce.get('content');
					if (ed && !ed.isHidden()) ed.execCommand('mceRepaint');
				}
			}, 60);
		});
	});
	</script>
	<?php
} );

function ymkrf_flyer_box_prd( $post ) {
	$ids = array_filter( array_map( 'intval',
		explode( ',', (string) get_post_meta( $post->ID, '_ymkrf_flyer_products', true ) ) ) );

	$cats = get_terms( array( 'taxonomy' => 'ymkrf_product_cat', 'hide_empty' => false ) );
	?>
	<p class="description">
	  チラシに載せた商品にチェックを入れてください。<br>
	  価格・写真は<b>商品ページのものをそのまま使います</b>ので、ここに値段を打ち込む必要はありません。
	  商品の値段を直せば、チラシのページの値段も自動で変わります。
	</p>

	<input type="hidden" name="_ymkrf_flyer_products" id="ymkrf-fl-prd" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>">

	<div class="ymkrf-fl__prd">
	<?php
	if ( ! is_wp_error( $cats ) ) :
		foreach ( (array) $cats as $ct ) :
			$q = new WP_Query( array(
				'post_type'      => 'ymkrf_product',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
				'tax_query'      => array( array(
					'taxonomy' => 'ymkrf_product_cat',
					'field'    => 'term_id',
					'terms'    => $ct->term_id,
				) ),
			) );
			if ( ! $q->have_posts() ) { wp_reset_postdata(); continue; }
			?>
			<div class="ymkrf-fl__prdgrp">
			  <p class="ymkrf-fl__prdttl"><?php echo esc_html( $ct->name ); ?></p>
			  <div class="ymkrf-fl__prdlist">
			  <?php while ( $q->have_posts() ) : $q->the_post(); $pid = get_the_ID(); ?>
			    <label class="ymkrf-fl__prditem">
			      <input type="checkbox" class="ymkrf-fl__prdchk" value="<?php echo (int) $pid; ?>"
			        <?php checked( in_array( $pid, $ids, true ) ); ?>>
			      <span><?php echo esc_html( get_the_title() ); ?></span>
			    </label>
			  <?php endwhile; ?>
			  </div>
			</div>
			<?php
			wp_reset_postdata();
		endforeach;
	endif;
	?>
	</div>
	<?php
}

add_action( 'save_post_ymkrf_flyer', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! isset( $_POST['ymkrf_flyer_nonce'] ) ||
	     ! wp_verify_nonce( $_POST['ymkrf_flyer_nonce'], 'ymkrf_flyer_save' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	foreach ( array( '_ymkrf_flyer_catch' ) as $k ) {
		update_post_meta( $post_id, $k,
			isset( $_POST[ $k ] ) ? sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) : '' );
	}
	foreach ( array( '_ymkrf_flyer_front', '_ymkrf_flyer_back' ) as $k ) {
		update_post_meta( $post_id, $k, isset( $_POST[ $k ] ) ? (int) $_POST[ $k ] : 0 );
	}

	$ids = isset( $_POST['_ymkrf_flyer_products'] )
		? array_filter( array_map( 'intval', explode( ',', (string) $_POST['_ymkrf_flyer_products'] ) ) )
		: array();
	update_post_meta( $post_id, '_ymkrf_flyer_products', implode( ',', array_unique( $ids ) ) );
} );


/* 編集画面の見た目と、画像えらび */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	$s = get_current_screen();
	if ( ! $s || $s->post_type !== 'ymkrf_flyer' ) return;
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) return;
	wp_enqueue_media();
} );

add_action( 'admin_head', function () {
	$s = get_current_screen();
	if ( ! $s || $s->post_type !== 'ymkrf_flyer' ) return;
	echo '<style>
	  .ymkrf-fl__imgs{display:flex;gap:20px;flex-wrap:wrap}
	  .ymkrf-fl__img{flex:1 1 260px;min-width:240px}
	  .ymkrf-fl__lbl{margin:0 0 6px}
	  .ymkrf-fl__prev{display:flex;align-items:center;justify-content:center;
	    min-height:150px;padding:8px;background:#f6f7f7;border:1px solid #dcdcde;border-radius:4px}
	  .ymkrf-fl__prev img{max-width:100%;max-height:230px;height:auto;display:block}
	  .ymkrf-fl__none{color:#a7aaad;font-size:12px}
	  .ymkrf-fl__prd{max-height:420px;overflow:auto;border:1px solid #dcdcde;
	    border-radius:4px;padding:10px;background:#fff}
	  .ymkrf-fl__prdttl{margin:12px 0 6px;padding-left:6px;font-weight:700;font-size:12.5px;
	    color:#646970;border-left:3px solid #fe3301;line-height:1.4}
	  .ymkrf-fl__prdgrp:first-child .ymkrf-fl__prdttl{margin-top:0}
	  .ymkrf-fl__prdlist{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:2px 10px}
	  .ymkrf-fl__prditem{display:flex;gap:6px;align-items:flex-start;font-size:12.5px;line-height:1.5;padding:2px 0}
	  .ymkrf-fl__shopall{margin:0 0 12px;padding-bottom:12px;border-bottom:1px solid #e5e5e5;font-size:14px}
	  .ymkrf-fl__shopall label{display:inline-flex;gap:6px;align-items:center}
	  .ymkrf-fl__shoplist.is-off{opacity:.4;pointer-events:none}
	  .ymkrf-fl__shopgrp + .ymkrf-fl__shopgrp{margin-top:14px}
	  .ymkrf-fl__shoppref{margin:0 0 6px;padding-left:7px;font-size:12px;font-weight:700;
	    color:#646970;border-left:3px solid #fe3301;line-height:1.4}
	  .ymkrf-fl__shopgrid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:2px 12px}
	  @media (max-width:1100px){.ymkrf-fl__shopgrid{grid-template-columns:repeat(3,minmax(0,1fr))}}
	  @media (max-width:782px){.ymkrf-fl__shopgrid{grid-template-columns:repeat(2,minmax(0,1fr))}}
	  .ymkrf-fl__shopitem{display:flex;gap:6px;align-items:center;font-size:13.5px;line-height:1.9}
	  .ymkrf-fl__off .ymkrf-fl__offchk{display:flex;gap:6px;align-items:center}
	  .ymkrf-fl__offbody{margin:8px 0 0}
	  .ymkrf-fl__offbody .description{display:block;margin-top:4px}
	  .ymkrf-fl__keep .description{display:block;margin-top:4px}
	  .ymkrf-fl__delnote{margin:6px 0 0;padding:5px 8px;font-size:12px;line-height:1.6;
	    background:#fff4f2;border-left:3px solid #d63638;border-radius:3px}
	</style>';
} );

add_action( 'admin_footer', function () {
	$s = get_current_screen();
	if ( ! $s || $s->post_type !== 'ymkrf_flyer' ) return;
	if ( ! in_array( $s->base, array( 'post' ), true ) ) return;
	?>
	<script>
	jQuery(function ($) {

		/* 画像をえらぶ（表面・裏面） */
		var frames = {};
		$(document).on('click', '.ymkrf-fl__pick', function (e) {
			e.preventDefault();
			var dom = $(this).data('target'), ttl = $(this).data('title');
			if (!frames[dom]) {
				frames[dom] = wp.media({ title: ttl, library: { type: 'image' }, multiple: false,
					button: { text: 'この画像をつかう' } });
				frames[dom].on('select', function () {
					var m = frames[dom].state().get('selection').first().toJSON();
					var url = (m.sizes && m.sizes.medium) ? m.sizes.medium.url : m.url;
					$('#' + dom).val(m.id);
					$('#' + dom + '-prev').html($('<img>').attr('src', url));
				});
			}
			frames[dom].open();
		});
		$(document).on('click', '.ymkrf-fl__clear', function (e) {
			e.preventDefault();
			var dom = $(this).data('target');
			$('#' + dom).val('');
			$('#' + dom + '-prev').html('<span class="ymkrf-fl__none">まだ入っていません</span>');
		});

		/* 掲載商品のチェックを、かくれた欄にまとめます */
		function syncPrd() {
			var a = [];
			$('.ymkrf-fl__prdchk:checked').each(function () { a.push($(this).val()); });
			$('#ymkrf-fl-prd').val(a.join(','));
		}
		$(document).on('change', '.ymkrf-fl__prdchk', syncPrd);
		syncPrd();

		/* 「全店共通にする」と、お店のチェックの連動 */
		var $none = $('#ymkrf-fl-shopnone'), $list = $('#ymkrf-fl-shoplist');
		function drawShop() {
			$list.toggleClass('is-off', $none.prop('checked'));
		}
		$none.on('change', function () {
			if ($none.prop('checked')) $('.ymkrf-fl__shopchk').prop('checked', false);
			drawShop();
		});
		$(document).on('change', '.ymkrf-fl__shopchk', function () {
			if ($('.ymkrf-fl__shopchk:checked').length) $none.prop('checked', false);
			drawShop();
		});
		drawShop();
	});
	</script>
	<?php
} );


/* ============================================================
   3-b. ダッシュボードのメニュー（お店をえらぶ画面をいちばん先に）
   ------------------------------------------------------------
   「イベント・チラシ」を押すと、まずお店が11個ならびます。
   お店を押すと、そのお店のチラシだけが一覧で出ます。
   1つのお店に何種類あってもかまいません。
   ============================================================ */
add_action( 'admin_menu', function () {

	add_menu_page(
		'イベント・チラシ', 'イベント・チラシ', 'edit_posts',
		'ymkrf-flyer-shops', 'ymkrf_flyer_shops_page', 'dashicons-tickets-alt', 8
	);

	/* ★この1つめは、下のCSSで見えなくしています。
	   WordPress は「メニューを押したときの行き先」を、いちばん上の小メニューから
	   決めるしくみなので、消してしまうと行き先が変わってしまいます。
	   そのため、置いたまま隠しています。
	   （2026/09/03 ユーザー要望：小メニューの「お店からえらぶ」
	     「チラシ一覧（ぜんぶ）」は出さない） */
	add_submenu_page( 'ymkrf-flyer-shops', 'お店からえらぶ', 'お店からえらぶ',
		'edit_posts', 'ymkrf-flyer-shops', 'ymkrf_flyer_shops_page' );

	add_submenu_page( 'ymkrf-flyer-shops', 'チラシを新規追加', '新規追加',
		'edit_posts', 'post-new.php?post_type=ymkrf_flyer' );
} );

/* 小メニューの1つめ（お店からえらぶ）を見えなくします。
   「イベント・チラシ」を押すと、そのままお店をえらぶ画面が右に出ます。 */
add_action( 'admin_head', function () {
	echo '<style>
	  #toplevel_page_ymkrf-flyer-shops .wp-submenu li.wp-first-item{display:none}
	</style>';
} );

/* チラシを編集しているあいだも、このメニューが開いたままになるようにします */
add_filter( 'parent_file', function ( $parent ) {
	$s = get_current_screen();
	if ( $s && $s->post_type === 'ymkrf_flyer' ) return 'ymkrf-flyer-shops';
	return $parent;
} );
add_filter( 'submenu_file', function ( $sub ) {
	$s = get_current_screen();
	if ( $s && $s->post_type === 'ymkrf_flyer' ) {
		/* 一覧・編集のときは、隠してある1つめ（お店からえらぶ）を
		   「いま開いている場所」にしておきます。
		   小メニューに何も光らない、という見え方を避けるためです。 */
		return ( $s->base === 'post' && $s->action === 'add' )
			? 'post-new.php?post_type=ymkrf_flyer'
			: 'ymkrf-flyer-shops';
	}
	return $sub;
} );

/** お店をえらぶ画面 */
function ymkrf_flyer_shops_page() {

	$shops = function_exists( 'ymkrf_shops' ) ? ymkrf_shops() : array();

	/* お店ごとに「掲載中／期間外・下書き」を数えます */
	$all = get_posts( array(
		'post_type'      => 'ymkrf_flyer',
		'post_status'    => array( 'publish', 'draft', 'pending', 'future' ),   /* future＝予約投稿 */
		'posts_per_page' => -1,
		'no_found_rows'  => true,
	) );

	$zero   = array( 'now' => 0, 'before' => 0, 'other' => 0, 'soon' => '' );
	$count  = array();   /* slug => 件数 */
	$common = $zero;

	foreach ( $all as $p ) {
		$st = ymkrf_flyer_state( $p->ID );
		$k  = ( $st === 'now' || $st === 'before' ) ? $st : 'other';

		$ts = get_the_terms( $p->ID, 'ymkrf_shop' );
		$targets = ( $ts && ! is_wp_error( $ts ) ) ? wp_list_pluck( $ts, 'slug' ) : array( '' );

		foreach ( $targets as $slug ) {
			if ( $slug === '' ) {
				$common[ $k ]++;
				$ref = &$common;
			} else {
				if ( ! isset( $count[ $slug ] ) ) $count[ $slug ] = $zero;
				$count[ $slug ][ $k ]++;
				$ref = &$count[ $slug ];
			}
			/* いちばん近い「これから出る日」を覚えておきます */
			if ( $st === 'before' ) {
				$d = (string) get_post_time( 'Y-m-d H:i', false, $p );
				if ( $ref['soon'] === '' || $d < $ref['soon'] ) $ref['soon'] = $d;
			}
			unset( $ref );
		}
	}

	$list_url = function ( $slug ) {
		return admin_url( 'edit.php?post_type=ymkrf_flyer' . ( $slug ? '&ymkrf_shop=' . rawurlencode( $slug ) : '&ymkrf_flyer_common=1' ) );
	};

	/* カードまるごとがリンクです。ボタンは置きません
	   （2026/09/03 ユーザー要望：店名と件数だけでよい）。
	   押すと、そのお店のチラシ一覧が開きます。 */
	$card = function ( $name, $slug, $c, $note = '' ) use ( $list_url ) {
		?>
		<a class="ymkrf-fs__card<?php echo $slug ? '' : ' ymkrf-fs__card--common'; ?>"
		   href="<?php echo esc_url( $list_url( $slug ) ); ?>">
		  <span class="ymkrf-fs__name"><?php echo esc_html( $name ); ?></span>
		  <?php if ( $note ) : ?><span class="ymkrf-fs__note"><?php echo esc_html( $note ); ?></span><?php endif; ?>
		  <span class="ymkrf-fs__cnt">
		    <?php if ( $c['now'] ) : ?>
		      <span class="ymkrf-fs__now">掲載中 <?php echo (int) $c['now']; ?>件</span>
		    <?php else : ?>
		      <span class="ymkrf-fs__zero">掲載中のチラシなし</span>
		    <?php endif; ?>
		    <?php if ( ! empty( $c['before'] ) ) : ?>
		      <span class="ymkrf-fs__before"><?php
		        echo esc_html( ymkrf_flyer_day_text( $c['soon'] ) ); ?>から <?php echo (int) $c['before']; ?>件</span>
		    <?php endif; ?>
		    <?php if ( $c['other'] ) : ?>
		      <span class="ymkrf-fs__other">ほか <?php echo (int) $c['other']; ?>件（下書き・非公開）</span>
		    <?php endif; ?>
		  </span>
		</a>
		<?php
	};
	?>
	<div class="wrap ymkrf-fs">
	  <h1>イベント・チラシ
	    <a class="page-title-action" href="<?php echo esc_url( admin_url( 'edit.php?post_type=ymkrf_flyer' ) ); ?>">すべてのチラシを見る</a>
	  </h1>

	  <p class="ymkrf-fs__lead">
	    お店を押すと、そのお店のチラシだけが出ます。<br>
	    1つのお店に<b>何種類あってもかまいません</b>（月に1〜3種類ある、といった使い方ができます）。
	    チラシは<b>表面・裏面の2枚</b>で1件です。
	  </p>

	  <h2 class="ymkrf-fs__h2">全店共通</h2>
	  <div class="ymkrf-fs__grid">
	    <?php $card( '全店共通のチラシ', '', $common, 'どのお店でも出ます。全店で同じチラシのときに使います。' ); ?>
	  </div>

	  <?php
	  $by_pref = array();
	  foreach ( $shops as $sp ) $by_pref[ $sp['pref'] ][] = $sp;
	  foreach ( $by_pref as $pref => $list ) : ?>
	    <h2 class="ymkrf-fs__h2"><?php echo esc_html( $pref ); ?></h2>
	    <div class="ymkrf-fs__grid">
	      <?php foreach ( $list as $sp ) :
	        $c = isset( $count[ $sp['slug'] ] ) ? $count[ $sp['slug'] ] : array( 'now' => 0, 'other' => 0 );
	        $card( $sp['name'], $sp['slug'], $c, ! empty( $sp['soon'] ) ? '準備中のお店です' : '' );
	      endforeach; ?>
	    </div>
	  <?php endforeach; ?>

	  <?php
	  /* 自動でかたづけた記録。ほんとうに動いているかを目で見て確かめられます。 */
	  $clog = (array) get_option( 'ymkrf_flyer_cleanup_log', array() );
	  $next = wp_next_scheduled( 'ymkrf_flyer_cleanup' );
	  ?>
	  <div class="ymkrf-fs__log">
	    <p><b>自動でかたづけた記録</b>
	      <?php if ( $next ) : ?>
	        <span class="ymkrf-fs__logsub">次の見まわり：<?php
	          echo esc_html( wp_date( 'n月j日 G:i', $next ) ); ?>ごろ</span>
	      <?php endif; ?>
	    </p>
	    <?php if ( ! $clog ) : ?>
	      <p class="ymkrf-fs__logsub">まだ1件もかたづけていません。<br>
	        ページから消えて<?php echo (int) YMKRF_FLYER_KEEP_DAYS; ?>日たったチラシが出てきたら、ここに残ります。</p>
	    <?php else : ?>
	      <ul class="ymkrf-fs__loglist">
	        <?php foreach ( array_slice( $clog, 0, 8 ) as $l ) : ?>
	          <li>
	            <span class="ymkrf-fs__logday"><?php echo esc_html( $l['when'] ); ?></span>
	            <?php echo esc_html( $l['title'] ); ?>
	            <span class="ymkrf-fs__logsub">画像 <?php echo (int) $l['imgs']; ?>枚を削除<?php
	              if ( ! empty( $l['kept'] ) ) : ?>／<?php echo (int) $l['kept']; ?>枚は
	              ほかでも使っていたので残しました<?php endif; ?></span>
	          </li>
	        <?php endforeach; ?>
	      </ul>
	    <?php endif; ?>
	  </div>

	  <div class="ymkrf-fs__help">
	    <p><b>入れかたのめやす</b></p>
	    <ul>
	      <li>チラシ1件につき、<b>表面と裏面の画像2枚</b>を入れます。</li>
	      <li>B4のたて・よこ、どちらでも大丈夫です。ページの並べ方は自動で変わります。</li>
	      <li>出す日・やめる日は、編集画面の右「公開」の欄で決めます。<br>
	          先の日付で出したいときは「公開日時」を変えて<b>予約投稿</b>、
	          自動で終わらせたいときは<b>予約非公開日時</b>にチェックを入れてください。</li>
	      <li>お客様のページでは、<b>そのお店のチラシ</b>と<b>全店共通のチラシ</b>の両方が出ます。</li>
	      <li>ページから消えて<b><?php echo (int) YMKRF_FLYER_KEEP_DAYS; ?>日</b>たつと、
	          そのチラシと画像は自動で削除されます（下書きは消えません）。<br>
	          そのチラシからアップロードした画像は、えらび直して使わなくなったものもふくめて
	          ぜんぶ消えますので、メディアがたまりつづけることはありません。<br>
	          残しておきたいチラシは、編集画面の右「自動で削除しない」にチェックを入れてください。</li>
	      <li>1つのお店に何種類かあるときは、編集画面の右下「ページ属性」の<b>順序</b>で並び順を決められます（数の小さいほうが先）。</li>
	    </ul>
	  </div>
	</div>

	<style>
	  .ymkrf-fs__lead{max-width:820px;font-size:13.5px;line-height:1.9}
	  .ymkrf-fs__h2{margin:26px 0 10px;padding-left:9px;font-size:15px;
	    border-left:4px solid #fe3301;line-height:1.5}
	  .ymkrf-fs__grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(268px,1fr));gap:12px}
	  .ymkrf-fs__card{display:block;padding:13px 16px;background:#fff;border:1px solid #dcdcde;
	    border-radius:6px;text-decoration:none;color:inherit;transition:border-color .15s,box-shadow .15s}
	  .ymkrf-fs__card:hover{border-color:#fe3301;box-shadow:0 1px 6px rgba(0,0,0,.08)}
	  .ymkrf-fs__card--common{border-color:#fe3301;background:#fff8f5}
	  .ymkrf-fs__name{display:block;font-size:15px;font-weight:700;line-height:1.4}
	  .ymkrf-fs__note{display:block;margin-top:3px;font-size:11.5px;color:#787878;line-height:1.5}
	  .ymkrf-fs__cnt{display:block;margin-top:7px;font-size:12.5px;line-height:1.6}
	  .ymkrf-fs__cnt span{display:block}
	  .ymkrf-fs__now{color:#00782a;font-weight:700}
	  .ymkrf-fs__before{color:#8a6100;font-weight:700}
	  .ymkrf-fs__zero{color:#a7aaad}
	  .ymkrf-fs__other{color:#787878}
	  .ymkrf-fs__log{margin-top:32px;padding:14px 18px;background:#fff;border:1px solid #dcdcde;
	    border-radius:6px;max-width:860px;font-size:13px;line-height:1.8}
	  .ymkrf-fs__log > p{margin:0 0 6px}
	  .ymkrf-fs__logsub{color:#787878;font-size:12px;font-weight:400}
	  .ymkrf-fs__loglist{margin:0;list-style:none}
	  .ymkrf-fs__loglist li{padding:5px 0;border-top:1px solid #f0f0f1}
	  .ymkrf-fs__loglist li:first-child{border-top:0}
	  .ymkrf-fs__logday{display:inline-block;min-width:120px;color:#787878;font-size:12px}
	  .ymkrf-fs__loglist .ymkrf-fs__logsub{display:block;margin-left:120px}
	  .ymkrf-fs__help{margin-top:20px;padding:14px 18px;background:#fffbe6;
	    border:1px solid #f0dc9a;border-radius:6px;max-width:860px;font-size:13px;line-height:1.85}
	  .ymkrf-fs__help ul{margin:6px 0 0 18px;list-style:disc}
	</style>
	<?php
}

/* 「全店共通のチラシを見る」を押したとき、お店の付いていないチラシだけを出します */
add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() || ! $q->is_main_query() ) return;
	if ( $q->get( 'post_type' ) !== 'ymkrf_flyer' ) return;
	if ( empty( $_GET['ymkrf_flyer_common'] ) ) return;

	$ids = get_terms( array(
		'taxonomy' => 'ymkrf_shop', 'hide_empty' => false, 'fields' => 'ids',
	) );
	if ( is_wp_error( $ids ) || ! $ids ) return;

	$q->set( 'tax_query', array( array(
		'taxonomy' => 'ymkrf_shop',
		'field'    => 'term_id',
		'terms'    => $ids,
		'operator' => 'NOT IN',
	) ) );
} );


/* ============================================================
   4. 管理画面の一覧
   ============================================================ */
add_filter( 'manage_ymkrf_flyer_posts_columns', function ( $cols ) {
	$new = array();
	if ( isset( $cols['cb'] ) )    $new['cb']    = $cols['cb'];
	if ( isset( $cols['title'] ) ) $new['title'] = $cols['title'];
	$new['ymkrf_fshop'] = '対象のお店';
	$new['ymkrf_fterm'] = '出す日／やめる日';
	$new['ymkrf_fnow']  = 'いまの状態';
	$new['ymkrf_ford']  = '並び順';
	$new['date']        = '登録日';
	return $new;
}, 20 );

/* 一覧のいちばん上に「お店からえらぶ画面にもどる」を出します */
add_action( 'admin_notices', function () {
	$s = get_current_screen();
	if ( ! $s || $s->id !== 'edit-ymkrf_flyer' ) return;

	$name = '';
	if ( ! empty( $_GET['ymkrf_shop'] ) ) {
		$t = get_term_by( 'slug', sanitize_key( wp_unslash( $_GET['ymkrf_shop'] ) ), 'ymkrf_shop' );
		if ( $t && ! is_wp_error( $t ) ) $name = $t->name;
	} elseif ( ! empty( $_GET['ymkrf_flyer_common'] ) ) {
		$name = '全店共通';
	}
	?>
	<div class="notice notice-info" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
	  <p style="margin:8px 0;font-size:15px">
	    <b><?php echo esc_html( $name !== '' ? $name : 'すべてのお店' ); ?></b>
	  </p>
	</div>
	<?php
} );

add_action( 'manage_ymkrf_flyer_posts_custom_column', function ( $col, $post_id ) {
	$none = '<span style="color:#a7aaad">—</span>';
	switch ( $col ) {
		case 'ymkrf_fshop':
			$ts = get_the_terms( $post_id, 'ymkrf_shop' );
			if ( ! $ts || is_wp_error( $ts ) ) { echo '<b>全店共通</b>'; break; }
			$n = array();
			foreach ( $ts as $t ) $n[] = $t->name;
			echo esc_html( implode( '／', $n ) );
			break;
		case 'ymkrf_fterm':
			$on  = ymkrf_flyer_day_text( get_post_time( 'Y-m-d H:i', false, $post_id ) );
			$off = ymkrf_flyer_unpublish_at( $post_id );
			echo $on ? esc_html( $on ) : $none;
			echo '<br><span style="color:#787878">〜 '
			   . ( $off !== '' ? esc_html( ymkrf_flyer_day_text( $off ) ) : 'ずっと' )
			   . '</span>';
			break;
		case 'ymkrf_fnow':
			switch ( ymkrf_flyer_state( $post_id ) ) {
				case 'draft':
					echo '<span style="color:#a7aaad">下書き</span>';
					break;
				case 'before':
					$d = ymkrf_flyer_day_text( get_post_time( 'Y-m-d H:i', false, $post_id ) );
					echo '<span style="color:#996800;font-weight:700">◯ ' . esc_html( $d ) . 'から</span>'
					   . '<br><span style="color:#a7aaad;font-size:11px">いまは出ていません</span>';
					break;
				case 'after':
					echo '<span style="color:#a7aaad">非公開になりました</span>';
					$d = ymkrf_flyer_delete_at( $post_id );
					if ( $d !== '' ) {
						echo '<br><span style="color:#d63638;font-size:11px">'
						   . esc_html( ymkrf_flyer_day_text( $d ) ) . 'ごろ削除</span>';
					}
					break;
				default:
					echo '<span style="color:#00a32a;font-weight:700">● 掲載中</span>';
			}
			break;
		case 'ymkrf_ford':
			$o = (int) get_post_field( 'menu_order', $post_id );
			echo $o ? (int) $o : $none;
			break;
	}
}, 10, 2 );

/* お店でしぼり込んでいるときは、「チラシを新規追加」ボタンにも
   そのお店を引き継ぎます（お店をえらぶ画面にボタンを置かないかわりです）。 */
add_action( 'admin_footer', function () {
	$s = get_current_screen();
	if ( ! $s || $s->id !== 'edit-ymkrf_flyer' ) return;
	if ( empty( $_GET['ymkrf_shop'] ) ) return;
	$url = admin_url( 'post-new.php?post_type=ymkrf_flyer&ymkrf_shop='
		. rawurlencode( sanitize_key( wp_unslash( $_GET['ymkrf_shop'] ) ) ) );
	?>
	<script>
	(function () {
	  var a = document.querySelector('.wrap .page-title-action');
	  if (a) a.href = <?php echo wp_json_encode( $url ); ?>;
	})();
	</script>
	<?php
} );

/* 一覧を「並び順 → 新しい順」にします。
   1つのお店に何種類かあるとき、お客様のページと同じ順番で見えます。 */
add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() || ! $q->is_main_query() ) return;
	if ( $q->get( 'post_type' ) !== 'ymkrf_flyer' ) return;
	if ( $q->get( 'orderby' ) ) return;   /* 見出しを押して並べかえたときはそのまま */
	$q->set( 'orderby', array( 'menu_order' => 'ASC', 'date' => 'DESC' ) );
} );

add_action( 'admin_head', function () {
	$s = get_current_screen();
	if ( ! $s || $s->id !== 'edit-ymkrf_flyer' ) return;
	echo '<style>
	  .column-ymkrf_fshop{width:190px}
	  .column-ymkrf_fterm{width:210px}
	  .column-ymkrf_fnow{width:100px}
	  .column-ymkrf_ford{width:70px}
	</style>';
} );


/* ============================================================
   5. 便利な関数
   ============================================================ */

/** いま掲載中の期間かどうか */
if ( ! function_exists( 'ymkrf_flyer_is_now' ) ) :
function ymkrf_flyer_is_now( $post_id ) {
	return ymkrf_flyer_state( $post_id ) === 'now';
}
endif;

/**
 * そのチラシが、いまどういう状態かを返します。
 *
 *   draft  … 下書き（公開されていません）
 *   before … 公開の予約が入っていて、その日時がまだ先（ページには出ません）
 *   now    … 掲載中（ページに出ています）
 *   after  … 予約非公開日時をすぎた（ページには出ません）
 *
 * 日にちは、右の「公開」の欄で決めます。
 *   出す日　… WordPressの公開日時（予約投稿）
 *   やめる日… 予約非公開日時（_ymkrf_flyer_off）
 */
if ( ! function_exists( 'ymkrf_flyer_state' ) ) :
function ymkrf_flyer_state( $post_id ) {
	$status = get_post_status( $post_id );

	if ( $status === 'future' ) return 'before';
	if ( $status !== 'publish' ) return 'draft';

	/* 公開日時が未来（予約投稿がまだ動いていない場合の保険） */
	$now  = current_time( 'Y-m-d H:i' );
	$pub  = get_post_time( 'Y-m-d H:i', false, $post_id );
	if ( $pub && $now < $pub ) return 'before';

	$off = ymkrf_flyer_unpublish_at( $post_id );
	if ( $off !== '' && $now >= $off ) return 'after';

	return 'now';
}
endif;

/** 予約非公開日時（'Y-m-d H:i'）。決めていないときは空 */
if ( ! function_exists( 'ymkrf_flyer_unpublish_at' ) ) :
function ymkrf_flyer_unpublish_at( $post_id ) {
	return trim( (string) get_post_meta( $post_id, '_ymkrf_flyer_off', true ) );
}
endif;

/** 「10月3日（土）22:00」のような書き方にします（時刻は00:00のとき省きます） */
if ( ! function_exists( 'ymkrf_flyer_day_text' ) ) :
function ymkrf_flyer_day_text( $when ) {
	$t = strtotime( (string) $when );
	if ( ! $t ) return '';
	$w   = array( '日', '月', '火', '水', '木', '金', '土' );
	$out = date_i18n( 'n月j日', $t ) . '（' . $w[ (int) date_i18n( 'w', $t ) ] . '）';
	$hm  = date_i18n( 'G:i', $t );
	if ( $hm !== '0:00' ) $out .= ' ' . $hm;
	return $out;
}
endif;

/**
 * そのお店に出すチラシを返します。
 *
 * $slug … お店のスラッグ（例 komathu）。空のときは全店共通のものだけ。
 *
 * ★2026/09/03 ユーザー確認：1つのお店に1〜3種類ある月もあるとのことなので、
 *   「そのお店のチラシ」と「全店共通のチラシ」の両方を返します。
 *   お店のチラシが先、そのあとに全店共通です。
 *
 * 並びは、編集画面の「ページ属性 → 順序」の小さいほうが先です。
 * 同じ順序のときは、新しく登録したほうが先になります。
 */
if ( ! function_exists( 'ymkrf_flyers_for' ) ) :
function ymkrf_flyers_for( $slug = '' ) {

	$all = get_posts( array(
		'post_type'      => 'ymkrf_flyer',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		'no_found_rows'  => true,
	) );

	$mine   = array();
	$common = array();

	foreach ( $all as $p ) {
		if ( ! ymkrf_flyer_is_now( $p->ID ) ) continue;

		$ts = get_the_terms( $p->ID, 'ymkrf_shop' );
		$slugs = array();
		if ( $ts && ! is_wp_error( $ts ) ) {
			foreach ( $ts as $t ) $slugs[] = $t->slug;
		}

		if ( ! $slugs ) {
			$common[] = $p;                                 /* 全店共通 */
		} elseif ( $slug !== '' && in_array( $slug, $slugs, true ) ) {
			$mine[] = $p;                                   /* このお店のもの */
		}
	}

	return array_merge( $mine, $common );
}
endif;

/**
 * 石川・福井の地図（お店をえらぶ用）
 *
 * ★2026/09/03 ユーザー要望：お店を地図からえらべるようにする。
 *
 * ── 作り方について ────────────────────────────────
 * 地図は画像ではなく、SVG（線の図）で描いています。
 *   ・どんな大きさでも、にじまずきれいに出ます
 *   ・店名は「文字」なので、押せますし、あとから直せます
 *   ・画像を読み込まないので、ページが重くなりません
 *
 * 輪郭とピンの位置は、実際の緯度・経度から計算しています。
 * ですので、お店どうしの位置関係は実物どおりです。
 *
 * ── スマートフォンでは出しません ───────────────────────
 * 店名が小さくなって押しにくいので、CSSで隠しています（flyer.css）。
 * スマートフォンでは、これまでどおり大きな店名ボタンをお使いいただきます。
 *
 * ── 位置を直したいとき ────────────────────────────
 * $pins の数字を変えます。
 *   x, y   … ピンの場所（地図の中の座標）
 *   lx, ly … 店名のふだの場所
 *   side   … 'r' で右に、'l' で左に出します
 */
if ( ! function_exists( 'ymkrf_flyer_map' ) ) :
function ymkrf_flyer_map( $shops, $sel, $map ) {

	/* 県のかたち（緯度・経度から計算したもの） */
	$path_ishikawa = 'M392.4 0.0 C388.1 -7.0 369.4 6.9 353.8 13.3 C338.2 19.8 333.4 24.1 321.6 29.3'
	             . ' C309.8 34.6 309.6 30.3 300.2 37.3 C290.7 44.4 287.2 47.3 278.7 61.3'
	             . ' C270.3 75.4 266.3 83.7 261.6 101.3 C256.9 118.9 257.3 126.7 257.3 141.3'
	             . ' C257.3 156.0 259.2 156.3 261.6 168.0 C263.9 179.7 268.0 182.9 268.0 194.7'
	             . ' C268.0 206.4 267.7 209.6 261.6 221.3 C255.5 233.1 248.2 236.3 240.1 248.0'
	             . ' C232.1 259.7 233.6 262.9 225.1 274.7 C216.6 286.4 212.4 289.6 201.6 301.3'
	             . ' C190.7 313.1 183.4 318.0 175.8 328.0 C168.3 338.0 162.5 340.8 167.2 346.7'
	             . ' C172.0 352.5 184.5 350.0 197.3 354.7 C210.0 359.4 211.0 363.9 225.1 368.0'
	             . ' C239.3 372.1 247.4 379.2 261.6 373.3 C275.7 367.5 285.7 357.2 289.5 341.3'
	             . ' C293.2 325.5 282.0 316.0 278.7 301.3 C275.4 286.7 273.0 286.4 274.5 274.7'
	             . ' C275.9 262.9 279.5 259.7 285.2 248.0 C290.8 236.3 296.9 231.9 300.2 221.3'
	             . ' C303.5 210.8 303.0 210.0 300.2 200.0 C297.4 190.0 287.3 186.0 287.3 176.0'
	             . ' C287.3 166.0 294.5 163.5 300.2 154.7 C305.8 145.9 305.5 142.5 313.0 136.0'
	             . ' C320.6 129.5 327.4 132.4 334.5 125.3 C341.6 118.3 341.4 114.6 345.2 104.0'
	             . ' C349.0 93.4 345.5 90.2 351.6 77.3 C357.8 64.4 364.1 62.3 373.1 45.3'
	             . ' C382.0 28.3 396.6 7.0 392.4 0.0 Z';
	$path_fukui    = 'M154.4 338.7 C163.3 333.4 169.0 340.5 182.3 344.0 C195.5 347.5 201.7 347.6 214.4 354.7'
	             . ' C227.2 361.7 229.8 365.4 240.1 376.0 C250.5 386.6 254.0 389.8 261.6 402.7'
	             . ' C269.1 415.6 277.8 421.8 274.5 434.7 C271.2 447.6 259.8 450.8 246.6 461.3'
	             . ' C233.4 471.9 230.9 473.9 214.4 482.7 C197.9 491.5 188.0 494.3 171.5 501.3'
	             . ' C155.0 508.4 153.5 508.8 139.4 514.7 C125.2 520.5 121.4 522.1 107.2 528.0'
	             . ' C93.1 533.9 91.6 536.6 75.0 541.3 C58.5 546.0 48.7 545.2 32.2 549.3'
	             . ' C15.7 553.4 2.4 562.9 0.0 560.0 C-2.4 557.1 2.6 544.8 21.4 536.0'
	             . ' C40.3 527.2 64.5 525.9 85.8 520.0 C107.0 514.1 109.4 517.5 117.9 509.3'
	             . ' C126.4 501.1 125.8 496.2 124.4 482.7 C122.9 469.2 112.9 461.5 111.5 448.0'
	             . ' C110.1 434.5 114.2 433.1 117.9 421.3 C121.7 409.6 123.5 406.4 128.7 394.7'
	             . ' C133.8 382.9 135.9 380.3 141.5 368.0 C147.2 355.7 145.4 343.9 154.4 338.7 Z';


	/* ピンと、店名のふだの場所 */
	$pins = array(
		'tazuruhama'      => array( 'x' => 300.2, 'y' => 129.3, 'lx' => 480, 'ly' => 112, 'side' => 'r' ),
		'hakui'           => array( 'x' => 273.8, 'y' => 169.9, 'lx' => -20, 'ly' => 176, 'side' => 'l' ),
		'higashikanazawa' => array( 'x' => 252.4, 'y' => 251.2, 'lx' => 480, 'ly' => 222, 'side' => 'r' ),
		'tagami'          => array( 'x' => 257.3, 'y' => 259.7, 'lx' => 480, 'ly' => 262, 'side' => 'r' ),
		'nonoichi'        => array( 'x' => 240.6, 'y' => 267.2, 'lx' => 480, 'ly' => 302, 'side' => 'r' ),
		'komathu'         => array( 'x' => 204.1, 'y' => 298.9, 'lx' => 480, 'ly' => 352, 'side' => 'r' ),
		'kawakita'        => array( 'x' => 218.7, 'y' => 287.5, 'lx' => -20, 'ly' => 262, 'side' => 'l' ),
		'shinkaga'        => array( 'x' => 182.9, 'y' => 323.2, 'lx' => -20, 'ly' => 316, 'side' => 'l' ),
		'kanadu'          => array( 'x' => 156.1, 'y' => 351.2, 'lx' => -20, 'ly' => 370, 'side' => 'l' ),
		'kahahothu'       => array( 'x' => 162.1, 'y' => 385.1, 'lx' => 480, 'ly' => 420, 'side' => 'r' ),
		'asahi'           => array( 'x' => 140.9, 'y' => 422.1, 'lx' => -20, 'ly' => 448, 'side' => 'l' ),
	);
	?>
	<div class="p-fmap">
	  <svg class="p-fmap__svg" viewBox="-275 -25 980 625" role="img"
	       aria-label="石川県・福井県のお店の地図">

	    <path class="p-fmap__pref" d="<?php echo esc_attr( $path_fukui ); ?>"></path>
	    <path class="p-fmap__pref" d="<?php echo esc_attr( $path_ishikawa ); ?>"></path>

	    <text class="p-fmap__prefname" x="405" y="18">ISHIKAWA</text>
	    <text class="p-fmap__prefname" x="-10" y="575">FUKUI</text>

	    <?php foreach ( $shops as $sp ) :
	      $slug = $sp['slug'];
	      if ( ! isset( $pins[ $slug ] ) ) continue;
	      $p    = $pins[ $slug ];
	      $on   = ( $sel === $slug );
	      /* まだ開いていないお店は、うすく出します。
	         「準備中」「チラシなし」の文字は、下の店名ボタンに出るので
	         地図には入れません（ふだが重なってしまうためです）。 */
	      $soon = ! empty( $sp['soon'] );

	      /* ふだの大きさは、店名の文字数から決めます */
	      $len = function_exists( 'mb_strlen' ) ? mb_strlen( $sp['name'], 'UTF-8' ) : strlen( $sp['name'] ) / 3;
	      $bw  = $len * 16 + 20;
	      $bx  = ( $p['side'] === 'r' ) ? $p['lx'] : $p['lx'] - $bw;
	      $by  = $p['ly'] - 16;
	      /* 引き出し線は、ふだのピン側のはしへ */
	      $ex  = ( $p['side'] === 'r' ) ? $bx : $bx + $bw;
	    ?>
	      <a class="p-flyer__pick p-fmap__pin<?php echo $on ? ' is-on' : ''; ?><?php echo $soon ? ' is-soon' : ''; ?>"
	         href="<?php echo esc_url( add_query_arg( 'shop', $slug, home_url( '/flyer/' ) ) ); ?>#flyer"
	         data-shop="<?php echo esc_attr( $slug ); ?>">
	        <line class="p-fmap__lead" x1="<?php echo esc_attr( $p['x'] ); ?>" y1="<?php echo esc_attr( $p['y'] ); ?>"
	              x2="<?php echo esc_attr( $ex ); ?>" y2="<?php echo esc_attr( $p['ly'] ); ?>"></line>
	        <circle class="p-fmap__dot" cx="<?php echo esc_attr( $p['x'] ); ?>"
	                cy="<?php echo esc_attr( $p['y'] ); ?>" r="7"></circle>
	        <rect class="p-fmap__box" x="<?php echo esc_attr( $bx ); ?>" y="<?php echo esc_attr( $by ); ?>"
	              width="<?php echo esc_attr( $bw ); ?>" height="32" rx="16"></rect>
	        <text class="p-fmap__label" x="<?php echo esc_attr( $bx + $bw / 2 ); ?>"
	              y="<?php echo esc_attr( $p['ly'] + 6 ); ?>"><?php echo esc_html( $sp['name'] ); ?></text>
	      </a>
	    <?php endforeach; ?>
	  </svg>
	</div>
	<?php
}
endif;

/**
 * トップページなどに出す「今月のチラシ」のバナー。
 *
 * ・掲載中のチラシが1件もないときは、何も出しません
 * ・いちばん新しいチラシの表面を、小さく1枚だけ出します
 *   （トップページが重くならないよう、1枚だけです）
 * ・押すと /flyer/ が開きます
 */
if ( ! function_exists( 'ymkrf_flyer_banner' ) ) :
function ymkrf_flyer_banner() {

	$all = get_posts( array(
		'post_type'      => 'ymkrf_flyer',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		'no_found_rows'  => true,
	) );

	$first = null;
	$count = 0;
	foreach ( $all as $p ) {
		if ( ! ymkrf_flyer_is_now( $p->ID ) ) continue;
		$count++;
		if ( ! $first ) $first = $p;
	}
	if ( ! $first ) return;   /* 掲載中のチラシなし */

	$d   = ymkrf_flyer_data( $first );
	$img = $d['front'] ? $d['front'] : $d['back'];
	$url = home_url( '/flyer/' );
	?>
	<section class="l-section p-fbanner__sec">
	  <div class="l-wrap">
	    <a class="p-fbanner" href="<?php echo esc_url( $url ); ?>" data-cta="home-flyer">
	      <?php if ( $img ) : ?>
	        <span class="p-fbanner__ph">
	          <img src="<?php echo esc_url( $img['src'] ); ?>"
	               width="<?php echo (int) $img['w']; ?>" height="<?php echo (int) $img['h']; ?>"
	               alt="" loading="lazy" decoding="async">
	        </span>
	      <?php endif; ?>

	      <span class="p-fbanner__body">
	        <span class="p-fbanner__en">FLYER</span>
	        <span class="p-fbanner__title">今月のチラシ</span>
	        <?php if ( $d['catch'] ) : ?>
	          <span class="p-fbanner__catch"><?php echo esc_html( $d['catch'] ); ?></span>
	        <?php endif; ?>
	        <span class="p-fbanner__text">
	          新聞折込・店頭でお配りしているチラシを、そのままご覧いただけます。<br class="sp-only">
	          お店をえらぶと、そのお店のチラシが出ます。
	        </span>
	        <span class="p-fbanner__btn">チラシを見る</span>
	      </span>
	    </a>
	  </div>
	</section>
	<?php
}
endif;

/** チラシ1件を、ページで使いやすい形にほどきます */
if ( ! function_exists( 'ymkrf_flyer_data' ) ) :
function ymkrf_flyer_data( $post ) {
	$id = is_object( $post ) ? $post->ID : (int) $post;

	$img = function ( $key ) use ( $id ) {
		$aid = (int) get_post_meta( $id, $key, true );
		if ( ! $aid ) return null;
		$big = wp_get_attachment_image_src( $aid, 'full' );
		$mid = wp_get_attachment_image_src( $aid, 'large' );
		if ( ! $big ) return null;
		return array(
			'full' => $big[0],
			'src'  => $mid ? $mid[0] : $big[0],
			'w'    => $mid ? $mid[1] : $big[1],
			'h'    => $mid ? $mid[2] : $big[2],
		);
	};

	return array(
		'id'    => $id,
		'title' => get_the_title( $id ),
		'catch' => (string) get_post_meta( $id, '_ymkrf_flyer_catch', true ),
		'front' => $img( '_ymkrf_flyer_front' ),
		'back'  => $img( '_ymkrf_flyer_back' ),
		'body'  => trim( (string) get_post_field( 'post_content', $id ) ),
		'prd'   => array_filter( array_map( 'intval',
			explode( ',', (string) get_post_meta( $id, '_ymkrf_flyer_products', true ) ) ) ),
	);
}
endif;
