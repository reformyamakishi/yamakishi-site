<?php
/**
 * functions-area.php ─ 市町別ページ（/area/kanazawa/ など）
 * 置き場所： wp-content/themes/ymkrf/inc/functions-area.php
 *
 * （2026/09/23 ユーザー承認
 *   「市町別ページ（/area/kanazawa/ など）」→ 見本を見ていただいて「いいね！ではお願い」）
 *
 * ■ なぜ作るか
 *   「金沢市 リフォーム」「小松市 風呂 リフォーム」は、
 *   実際に工事を頼む直前の方が打つ言葉です。いちばん成約に近いところです。
 *   施工事例（2,169件）とお客様の声（1,590件）が市町ごとに入っているので、
 *   中身のあるページを自動で作れます。
 *
 * ■ どこからデータを取るか
 *   市町の一覧   … 店舗の「担当エリア」（inc/functions-shops.php の areas）
 *   担当店舗     … 同上（その市町を担当しているお店）
 *   施工事例     … ymkrf_works_area（部位ではなくエリアの分類）
 *   お客様の声   … _ymkrf_city
 *   ローマ字     … ymkrf_voice_city_roman()（お客様の声のURLと同じ表）
 *
 * ■ 手で入れるのはここだけ
 *   ダッシュボード「対応エリア」で、市町ごとに
 *     ・書き出しの文（空なら自動の文が出ます）
 *     ・よくあるご相談（3つまで）
 *     ・対応地区（田上・泉野…）
 *   を入れられます。空のままでもページは成り立ちます。
 *
 * ■ 中身のうすい市町は、検索エンジンに出しません
 *   施工事例が3件に満たない市町は noindex にします。
 *   うすいページが並ぶと、サイト全体の評価が下がるためです。
 *   事例が増えれば、自動で出るようになります。
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'YMKRF_AREA_OPT', 'ymkrf_area_texts' );
define( 'YMKRF_AREA_MIN', 3 );   /* これ未満の施工事例なら noindex */


/* ============================================================
   1. 市町の一覧
   ============================================================ */

/** 市町名 → ローマ字 */
function ymkrf_area_roman( $city ) {
	$map = function_exists( 'ymkrf_voice_city_roman' ) ? ymkrf_voice_city_roman() : array();
	return isset( $map[ $city ] ) ? $map[ $city ] : '';
}

/**
 * 作る市町の一覧。
 * 戻り値： array( 'kanazawa' => array( 'city' => '金沢市', 'pref' => '石川県', 'shops' => array(店の配列) ) )
 */
function ymkrf_area_list() {

	static $out = null;
	if ( $out !== null ) return $out;

	$out = array();

	/* ① お店の「担当エリア」から（inc/functions-shops.php）
	     東金沢店のように、これから開くお店の地域もここに入れておけば、
	     自動でページができます（2026/09/23 ユーザー
	     「来月から東金沢店が追加されます。施工地域は金沢市
	       （もしかしたら内灘・津幡・かほく市も入るかも）」） */
	if ( function_exists( 'ymkrf_shops' ) ) {
		foreach ( ymkrf_shops() as $s ) {

			$areas = isset( $s['areas'] ) ? (array) $s['areas'] : array();
			foreach ( $areas as $city ) {

				$city = ymkrf_area_clean( $city );
				$slug = ymkrf_area_roman( $city );
				if ( $slug === '' ) continue;   /* ローマ字の表に無い市町は作りません */

				if ( ! isset( $out[ $slug ] ) ) {
					$out[ $slug ] = array(
						'city'  => $city,
						'pref'  => isset( $s['pref'] ) ? $s['pref'] : '',
						'shops' => array(),
					);
				}
				$out[ $slug ]['shops'][] = $s;
			}
		}
	}

	/* ② 施工事例の「エリア」分類からも拾います。
	     施工事例はこの分類で市町ごとに分かれているので、
	     お店の担当エリアに書き忘れがあっても、ページができます。 */
	$ts = get_terms( array( 'taxonomy' => 'ymkrf_works_area', 'hide_empty' => false ) );
	if ( $ts && ! is_wp_error( $ts ) ) {
		foreach ( $ts as $t ) {

			$city = ymkrf_area_clean( $t->name );
			$slug = ymkrf_area_roman( $city );
			if ( $slug === '' ) continue;
			if ( isset( $out[ $slug ] ) ) continue;

			$out[ $slug ] = array(
				'city'  => $city,
				'pref'  => ymkrf_area_pref( $city ),
				'shops' => array(),
			);
		}
	}

	/* ③ エリアの画面で「担当店舗」をえらんであれば、そちらを使います。
	     （2026/09/23 ユーザー指示「担当店舗もえらべるようにして」）
	     お店の設定と、エリアの設定と、2か所がずれないようにするためです。 */
	if ( $ts && ! is_wp_error( $ts ) && function_exists( 'ymkrf_shops' ) ) {

		$by = array();
		foreach ( ymkrf_shops() as $s ) {
			if ( ! empty( $s['slug'] ) ) $by[ $s['slug'] ] = $s;
		}

		foreach ( $ts as $t ) {

			$saved = (string) get_term_meta( $t->term_id, '_ymkrf_shops', true );
			if ( $saved === '' ) continue;

			$slug = ymkrf_area_roman( ymkrf_area_clean( $t->name ) );
			if ( $slug === '' || ! isset( $out[ $slug ] ) ) continue;

			/* 「担当店舗なし」（2026/09/23 ユーザー指示） */
			if ( $saved === '_none' ) { $out[ $slug ]['shops'] = array(); continue; }

			$list = array();
			foreach ( array_filter( array_map( 'trim', explode( ',', $saved ) ) ) as $sl ) {
				if ( isset( $by[ $sl ] ) ) $list[] = $by[ $sl ];
			}
			$out[ $slug ]['shops'] = $list;
		}
	}

	return $out;
}

/**
 * 市町名のゆれをそろえます。
 * 取り込んだデータには「石川県かほく市」のように県名が付いたものが混じっています。
 */
function ymkrf_area_clean( $city ) {
	$city = trim( (string) $city );
	$city = preg_replace( '/^(石川県|福井県|富山県)/u', '', $city );
	/* 「金沢市（北・東エリア）」「志賀町（一部）」のような、うしろのかっこ書きは外します（2026/10/27） */
	$city = preg_replace( '/（[^）]*）$/u', '', trim( (string) $city ) );
	return trim( (string) $city );
}

/** 県名（ローマ字の表の並びから決めています） */
function ymkrf_area_pref( $city ) {
	$fukui = array( '福井市','あわら市','坂井市','勝山市','大野市','永平寺町',
	                '鯖江市','越前市','敦賀市','小浜市','越前町','池田町' );
	return in_array( $city, $fukui, true ) ? '福井県' : '石川県';
}

/** スラッグから1つぶん */
function ymkrf_area_get( $slug ) {
	$all = ymkrf_area_list();
	return isset( $all[ $slug ] ) ? $all[ $slug ] : null;
}

/** 手で入れた文（書き出し・よくあるご相談・対応地区） */
function ymkrf_area_text( $slug, $key = '' ) {
	$o = get_option( YMKRF_AREA_OPT );
	$o = is_array( $o ) ? $o : array();
	$one = isset( $o[ $slug ] ) && is_array( $o[ $slug ] ) ? $o[ $slug ] : array();
	if ( $key === '' ) return $one;
	return isset( $one[ $key ] ) ? $one[ $key ] : '';
}

/**
 * エリアの分類（施工事例 ＞ エリア）に書いた「説明」。
 * （2026/09/23 ユーザー
 *   「七尾市に説明を追加しました」「ここに説明表示すればよいんじゃないの？」）
 */
function ymkrf_area_term_text( $city ) {
	$t = get_term_by( 'name', $city, 'ymkrf_works_area' );
	if ( ! $t || is_wp_error( $t ) ) return '';
	return trim( (string) $t->description );
}

/**
 * ページの書き出しに使う文。手で書いたものを先に使います。
 *
 *   ① 「対応エリア」の画面に入れた書き出し
 *   ② エリアの分類に書いた説明
 *   ③ どちらも無ければ、自動の文
 *
 * 市町ごとにちがう文が入っているほど、検索に強くなります
 * （24ページが同じ文だと、中身のうすい似たページと見られます）。
 */
function ymkrf_area_lead( $slug, $city = '' ) {

	$lead = trim( (string) ymkrf_area_text( $slug, 'lead' ) );
	if ( $lead !== '' ) return $lead;

	if ( $city === '' ) {
		$a = ymkrf_area_get( $slug );
		$city = $a ? $a['city'] : '';
	}
	return ymkrf_area_term_text( $city );
}


/* ============================================================
   2. 件数と、中身を取ってくるところ
   ============================================================ */

/** その市町の施工事例（$n 件。0なら件数だけ数えます） */
function ymkrf_area_works( $city, $n = 6 ) {
	return new WP_Query( array(
		'post_type'      => 'ymkrf_works',
		'post_status'    => 'publish',
		'posts_per_page' => $n ? $n : 1,
		'fields'         => $n ? '' : 'ids',
		'tax_query'      => array( array(
			'taxonomy' => 'ymkrf_works_area', 'field' => 'name', 'terms' => $city,
		) ),
	) );
}

/** その市町のお客様の声 */
function ymkrf_area_voices( $city, $n = 3 ) {
	return new WP_Query( array(
		'post_type'      => 'ymkrf_voice',
		'post_status'    => 'publish',
		'posts_per_page' => $n ? $n : 1,
		'meta_query'     => array( array( 'key' => '_ymkrf_city', 'value' => $city ) ),
	) );
}

/** 件数（重いので、1日だけ控えておきます） */
function ymkrf_area_counts( $city ) {

	$key = 'ymkrf_area_cnt_' . md5( $city );
	$c   = get_transient( $key );
	if ( is_array( $c ) ) return $c;

	$w = ymkrf_area_works( $city, 0 );
	$v = ymkrf_area_voices( $city, 0 );
	$c = array( 'works' => (int) $w->found_posts, 'voices' => (int) $v->found_posts );

	set_transient( $key, $c, DAY_IN_SECONDS );
	return $c;
}


/**
 * 近くの市町（地図の北から南の並びで、前後をとります）
 *
 * （2026/09/23 ユーザー承認
 *   「七尾市なのに野々市市・小松市・加賀市が出ています」→ 直します）
 *
 * これまでは「同じ県で、公開ずみの事例が1件以上」という条件だけで、
 * 近さを見ていませんでした。能登の七尾市のページに、
 * 加賀地方の市町が並んでしまっていました。
 *
 * @param string $slug いま見ている市町（ローマ字）
 * @param int    $n    前後にいくつずつ出すか
 * @return array array( array( 'slug' =>, 'city' => ), … )
 */
function ymkrf_area_near( $slug, $n = 3 ) {

	$me = ymkrf_area_get( $slug );
	if ( ! $me ) return array();

	/* 北から南の並び（施工事例の画面と同じ表を使います） */
	$order = function_exists( 'ymkrf_area_northsouth' ) ? ymkrf_area_northsouth() : array();
	$all   = ymkrf_area_list();

	/* ページのある市町だけを、並び順にならべます */
	$line = array();
	foreach ( $order as $city ) {
		foreach ( $all as $sl => $a2 ) {
			if ( $a2['city'] === $city ) { $line[] = array( 'slug' => $sl, 'city' => $city, 'pref' => $a2['pref'] ); break; }
		}
	}
	if ( ! $line ) return array();

	/* 自分の場所 */
	$at = -1;
	foreach ( $line as $i => $one ) if ( $one['slug'] === $slug ) { $at = $i; break; }
	if ( $at < 0 ) return array();

	/* 前後を、同じ県のものから順にひろいます */
	$out = array();
	for ( $d = 1; $d <= count( $line ); $d++ ) {
		foreach ( array( $at - $d, $at + $d ) as $j ) {
			if ( $j < 0 || $j >= count( $line ) ) continue;
			if ( $line[ $j ]['pref'] !== $me['pref'] ) continue;
			$out[] = array( 'slug' => $line[ $j ]['slug'], 'city' => $line[ $j ]['city'] );
			if ( count( $out ) >= $n * 2 ) return $out;
		}
	}
	return $out;
}


/* ============================================================
   3. URL（/area/◯◯/）
   ============================================================ */

add_action( 'init', function () {
	add_rewrite_rule( '^area/?$',          'index.php?ymkrf_area=_index', 'top' );
	add_rewrite_rule( '^area/([^/]+)/?$',  'index.php?ymkrf_area=$matches[1]', 'top' );
} );

add_filter( 'query_vars', function ( $v ) {
	$v[] = 'ymkrf_area';
	return $v;
} );

/** URLを作る道具 */
function ymkrf_area_url( $slug = '' ) {
	return home_url( '/area/' . ( $slug !== '' ? $slug . '/' : '' ) );
}

/* 見つからない市町は404にします */
add_action( 'template_redirect', function () {

	$slug = get_query_var( 'ymkrf_area' );
	if ( ! $slug ) return;
	if ( $slug === '_index' ) return;

	if ( ! ymkrf_area_get( $slug ) ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
} );

/* テンプレートの割りあて */
add_filter( 'template_include', function ( $tpl ) {

	$slug = get_query_var( 'ymkrf_area' );
	if ( ! $slug || is_404() ) return $tpl;

	$f = get_stylesheet_directory() . ( $slug === '_index' ? '/ymkrf-areas.php' : '/ymkrf-area.php' );
	return file_exists( $f ) ? $f : $tpl;
} );

/* 一覧ページ・市町ページとも、ふつうのページとして扱わせます */
add_action( 'wp', function () {
	if ( ! get_query_var( 'ymkrf_area' ) ) return;
	global $wp_query;
	$wp_query->is_home     = false;
	$wp_query->is_404      = $wp_query->is_404;
	$wp_query->is_singular = false;
} );


/* ============================================================
   4. 検索対策（題名・説明文・うすいページは出さない）
   ============================================================ */

add_filter( 'document_title_parts', function ( $parts ) {

	$slug = get_query_var( 'ymkrf_area' );
	if ( ! $slug ) return $parts;

	if ( $slug === '_index' ) {
		$parts['title'] = 'リフォームの対応エリア｜石川県・福井県';
		return $parts;
	}

	$a = ymkrf_area_get( $slug );
	if ( ! $a ) return $parts;

	$c = ymkrf_area_counts( $a['city'] );
	$parts['title'] = $a['city'] . 'のリフォーム｜施工実績' . number_format( $c['works'] ) . '件｜創業127年';
	return $parts;
}, 20 );

add_action( 'wp_head', function () {

	$slug = get_query_var( 'ymkrf_area' );
	if ( ! $slug ) return;

	if ( $slug === '_index' ) {
		echo '<meta name="description" content="'
		   . esc_attr( 'リフォームヤマキシの対応エリアです。石川県・福井県の各市町で、'
		             . 'キッチン・お風呂・トイレ・洗面台の交換から外壁塗装・カーポートまで承ります。'
		             . '見積り・現地調査は無料です。' ) . '">' . "\n";
		return;
	}

	$a = ymkrf_area_get( $slug );
	if ( ! $a ) return;

	$c   = ymkrf_area_counts( $a['city'] );
	$sh  = array();
	foreach ( $a['shops'] as $s ) $sh[] = $s['name'];

	/* 手で書いた文があれば、検索結果の説明文にもそれを使います。
	   市町ごとにちがう文のほうが、検索に強くなります。（2026/09/23） */
	$own = ymkrf_area_lead( $slug, $a['city'] );
	if ( $own !== '' ) {
		echo '<meta name="description" content="'
		   . esc_attr( mb_strimwidth( preg_replace( '/\s+/u', ' ', $own ), 0, 240, '…', 'UTF-8' ) ) . '">' . "\n";

		if ( $c['works'] < YMKRF_AREA_MIN ) {
			echo '<meta name="robots" content="noindex,follow">' . "\n";
		}
		return;
	}

	$desc = $a['city'] . 'のリフォームなら創業127年のリフォームヤマキシ。'
	      . $a['city'] . 'での施工実績' . number_format( $c['works'] ) . '件、'
	      . 'お客様の声' . number_format( $c['voices'] ) . '件。'
	      . 'キッチン・お風呂・トイレ・洗面台の交換から外壁塗装・カーポートまで、'
	      . '工事費込みの分かりやすい価格でご案内します。'
	      . ( $sh ? '担当店舗：' . implode( '・', $sh ) . '。' : '' )
	      . '見積り・現地調査は無料です。';

	echo '<meta name="description" content="'
	   . esc_attr( mb_strimwidth( $desc, 0, 240, '…', 'UTF-8' ) ) . '">' . "\n";

	/* 中身がうすい市町は、検索エンジンに出しません */
	if ( $c['works'] < YMKRF_AREA_MIN ) {
		echo '<meta name="robots" content="noindex,follow">' . "\n";
	}
}, 3 );


/* ============================================================
   5. ダッシュボード「対応エリア」
   ============================================================ */

add_action( 'admin_menu', function () {
	add_menu_page(
		'対応エリア', '対応エリア', 'edit_posts', 'ymkrf-areas',
		'ymkrf_area_admin', 'dashicons-location-alt', 27
	);
} );

function ymkrf_area_admin() {

	if ( ! current_user_can( 'edit_posts' ) ) return;

	$all  = ymkrf_area_list();
	$now  = isset( $_GET['area'] ) ? sanitize_title( wp_unslash( $_GET['area'] ) ) : '';
	$msg  = '';

	/* 保存 */
	if ( isset( $_POST['ymkrf_area_nonce'] ) &&
	     wp_verify_nonce( $_POST['ymkrf_area_nonce'], 'ymkrf_area' ) ) {

		$slug = sanitize_title( wp_unslash( $_POST['area'] ) );
		if ( isset( $all[ $slug ] ) ) {

			$o = get_option( YMKRF_AREA_OPT );
			$o = is_array( $o ) ? $o : array();

			$faq = array();
			for ( $i = 1; $i <= 3; $i++ ) {
				$q = isset( $_POST[ 'faq_q' . $i ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'faq_q' . $i ] ) ) : '';
				$a = isset( $_POST[ 'faq_a' . $i ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ 'faq_a' . $i ] ) ) : '';
				if ( trim( $q ) !== '' ) $faq[] = array( 'q' => $q, 'a' => $a );
			}

			$o[ $slug ] = array(
				'lead'  => sanitize_textarea_field( wp_unslash( $_POST['lead'] ) ),
				'towns' => sanitize_text_field( wp_unslash( $_POST['towns'] ) ),
				'faq'   => $faq,
			);
			update_option( YMKRF_AREA_OPT, $o );
			$msg = $all[ $slug ]['city'] . 'を保存しました。';
			$now = $slug;
		}
	}
	?>
	<div class="wrap">
	  <h1>対応エリア</h1>

	  <?php if ( $msg ) : ?>
	    <div class="notice notice-success"><p><?php echo esc_html( $msg ); ?></p></div>
	  <?php endif; ?>

	  <?php if ( $now === '' || ! isset( $all[ $now ] ) ) : ?>

	  <?php /* 一覧を見やすく（2026/10/28 ユーザー「対応エリアが見にくい。文を入れるって何？見るって何？もっとわかりやすくして」）
	           表をやめて、市町ごとのカードにしました。県ごとにまとめています。 */ ?>
	  <div class="ymkrf-ar__intro">
	    <p>市町ごとのページ（例：<a href="<?php echo esc_url( ymkrf_area_url( 'kanazawa' ) ); ?>" target="_blank">金沢市のページ</a>）の一覧です。</p>
	    <ul>
	      <li><b>施工事例・お客様の声・担当店舗は、自動で入ります。</b></li>
	      <li>「<b>ページの文章を書く</b>」から、そのページの<b>書き出しの文・対応地区・よくあるご相談</b>を入れられます。空のままでもページは出ます。</li>
	      <li>「<b>サイトで見る</b>」で、お客様が見るページを新しいタブで開きます。</li>
	      <li><b>Googleに出すのは、施工事例が<?php echo (int) YMKRF_AREA_MIN; ?>件以上ある市町だけ</b>です（中身のうすいページが多いと、サイト全体の評価が下がるため）。事例が増えれば自動で出るようになります。</li>
	    </ul>
	  </div>

	  <?php
	  $by_pref = array();
	  foreach ( $all as $slug => $a ) {
		  $pf = ! empty( $a['pref'] ) ? $a['pref'] : ( function_exists( 'ymkrf_area_pref' ) ? ymkrf_area_pref( $a['city'] ) : '' );
		  $by_pref[ $pf ][ $slug ] = $a;
	  }
	  foreach ( $by_pref as $pf => $list ) : ?>
	    <h2 class="ymkrf-ar__pref"><?php echo esc_html( $pf ); ?><small><?php echo count( $list ); ?>市町</small></h2>
	    <div class="ymkrf-ar__grid">
	    <?php foreach ( $list as $slug => $a ) :
	      $c  = ymkrf_area_counts( $a['city'] );
	      $t  = ymkrf_area_text( $slug );
	      $ld = trim( (string) ( function_exists( 'ymkrf_area_lead' ) ? ymkrf_area_lead( $slug, $a['city'] ) : '' ) );
	      if ( $ld === '' && isset( $t['lead'] ) ) $ld = trim( (string) $t['lead'] );
	      $tw = isset( $t['towns'] ) ? trim( (string) $t['towns'] ) : '';
	      $fq = isset( $t['faq'] ) && is_array( $t['faq'] ) ? count( $t['faq'] ) : 0;
	      $on = ( $c['works'] >= YMKRF_AREA_MIN );
	    ?>
	      <div class="ymkrf-ar__card">
	        <div class="ymkrf-ar__hd">
	          <b><?php echo esc_html( $a['city'] ); ?></b>
	          <?php if ( $on ) : ?>
	            <span class="ymkrf-ar__st is-on">Googleに出ています</span>
	          <?php else : ?>
	            <span class="ymkrf-ar__st">Googleにはまだ出していません</span>
	          <?php endif; ?>
	        </div>
	        <?php if ( ! $on ) : ?>
	          <p class="ymkrf-ar__why">施工事例があと<?php echo (int) ( YMKRF_AREA_MIN - $c['works'] ); ?>件ふえると、自動で出ます。</p>
	        <?php endif; ?>
	        <div class="ymkrf-ar__nums">
	          <span>施工事例<b><?php echo number_format( $c['works'] ); ?></b>件</span>
	          <span>お客様の声<b><?php echo number_format( $c['voices'] ); ?></b>件</span>
	        </div>
	        <?php if ( $a['shops'] ) : ?>
	          <p class="ymkrf-ar__shops">担当：<?php foreach ( $a['shops'] as $s ) echo '<span>' . esc_html( $s['name'] ) . '</span>'; ?></p>
	        <?php endif; ?>
	        <ul class="ymkrf-ar__fill">
	          <li class="<?php echo $ld !== '' ? 'is-ok' : ''; ?>">書き出しの文</li>
	          <li class="<?php echo $tw !== '' ? 'is-ok' : ''; ?>">対応地区</li>
	          <li class="<?php echo $fq ? 'is-ok' : ''; ?>">よくあるご相談<?php echo $fq ? '（' . (int) $fq . '）' : ''; ?></li>
	        </ul>
	        <div class="ymkrf-ar__btns">
	          <a class="button button-primary" href="<?php echo esc_url( add_query_arg( 'area', $slug ) ); ?>">ページの文章を書く</a>
	          <a class="ymkrf-ar__view" href="<?php echo esc_url( ymkrf_area_url( $slug ) ); ?>" target="_blank" rel="noopener">サイトで見る ↗</a>
	        </div>
	      </div>
	    <?php endforeach; ?>
	    </div>
	  <?php endforeach; ?>

	  <style>
	    .ymkrf-ar__intro{max-width:980px;background:#fff;border:1px solid #dcdcde;border-left:4px solid #fe3301;border-radius:6px;padding:10px 16px;margin:12px 0 6px}
	    .ymkrf-ar__intro p{margin:4px 0 6px;font-size:13.5px}
	    .ymkrf-ar__intro ul{margin:0 0 4px 1.2em;list-style:disc;font-size:13px;line-height:1.8}
	    .ymkrf-ar__pref{margin:22px 0 10px;padding-left:9px;border-left:4px solid #fe3301;font-size:16px}
	    .ymkrf-ar__pref small{margin-left:8px;font-size:12px;font-weight:400;color:#787878}
	    .ymkrf-ar__grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:12px;max-width:1400px}
	    .ymkrf-ar__card{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:14px 16px;display:flex;flex-direction:column;gap:8px}
	    .ymkrf-ar__hd{display:flex;align-items:center;justify-content:space-between;gap:8px}
	    .ymkrf-ar__hd b{font-size:18px}
	    .ymkrf-ar__st{font-size:11.5px;font-weight:700;border-radius:999px;padding:2px 10px;background:#fff4e5;color:#995200;white-space:nowrap}
	    .ymkrf-ar__st.is-on{background:#e6f4ea;color:#00782a}
	    .ymkrf-ar__why{margin:-4px 0 0;font-size:12px;color:#995200}
	    .ymkrf-ar__nums{display:flex;gap:16px;font-size:12.5px;color:#50575e}
	    .ymkrf-ar__nums b{font-size:17px;color:#1d2327;margin:0 2px 0 6px}
	    .ymkrf-ar__shops{margin:0;font-size:12.5px;color:#50575e}
	    .ymkrf-ar__shops span{display:inline-block;margin:2px 4px 2px 0;padding:1px 8px;border-radius:999px;background:#f0f0f1;color:#1d2327}
	    .ymkrf-ar__fill{display:flex;flex-wrap:wrap;gap:6px;margin:0;padding:0;list-style:none}
	    .ymkrf-ar__fill li{margin:0;font-size:11.5px;padding:2px 8px;border-radius:6px;background:#f6f7f7;color:#8c8f94}
	    .ymkrf-ar__fill li::before{content:"□ "}
	    .ymkrf-ar__fill li.is-ok{background:#e6f4ea;color:#00782a;font-weight:700}
	    .ymkrf-ar__fill li.is-ok::before{content:"✓ "}
	    .ymkrf-ar__btns{display:flex;align-items:center;gap:12px;margin-top:auto;padding-top:4px}
	    .ymkrf-ar__view{font-size:13px;font-weight:600}
	  </style>

	  <?php else :
	    $a = $all[ $now ];
	    $t = ymkrf_area_text( $now );
	    $faq = isset( $t['faq'] ) && is_array( $t['faq'] ) ? $t['faq'] : array();
	  ?>

	    <p><a href="<?php echo esc_url( remove_query_arg( 'area' ) ); ?>">← 市町の一覧にもどる</a></p>
	    <h2 style="display:flex;align-items:center;gap:14px;flex-wrap:wrap"><?php echo esc_html( $a['city'] ); ?>のページの文章
	      <a class="button" href="<?php echo esc_url( ymkrf_area_url( $now ) ); ?>" target="_blank" rel="noopener">サイトで見る ↗</a></h2>
	    <p class="description" style="max-width:900px">施工事例・お客様の声・担当店舗は自動で入ります。ここで入れるのは下の3つだけです。どれも空のままでかまいません（空ならその場所は出ないか、自動の文が出ます）。</p>

	    <form method="post">
	      <?php wp_nonce_field( 'ymkrf_area', 'ymkrf_area_nonce' ); ?>
	      <input type="hidden" name="area" value="<?php echo esc_attr( $now ); ?>">

	      <table class="form-table">
	        <tr>
	          <th>書き出しの文<br><small style="font-weight:400;color:#787878">ページのいちばん上、見出しのすぐ下に出ます</small></th>
	          <td>
	            <textarea name="lead" rows="3" class="large-text"
	              placeholder="空のままなら、自動の文が出ます"><?php
	              echo esc_textarea( isset( $t['lead'] ) ? $t['lead'] : '' ); ?></textarea>
	            <p class="description">その市町ならではのことを1〜2行で。空のままでもかまいません。</p>
	          </td>
	        </tr>
	        <tr>
	          <th>対応地区<br><small style="font-weight:400;color:#787878">「<?php echo esc_html( $a['city'] ); ?>の対応地区」として、地区名が並びます</small></th>
	          <td>
	            <input type="text" name="towns" class="large-text"
	              value="<?php echo esc_attr( isset( $t['towns'] ) ? $t['towns'] : '' ); ?>"
	              placeholder="例：田上、小立野、泉野、有松、鞍月、金石、森本">
	            <p class="description">「、」で区切って書いてください。空なら出しません。</p>
	          </td>
	        </tr>
	        <?php for ( $i = 1; $i <= 3; $i++ ) :
	          $q = isset( $faq[ $i - 1 ]['q'] ) ? $faq[ $i - 1 ]['q'] : '';
	          $an = isset( $faq[ $i - 1 ]['a'] ) ? $faq[ $i - 1 ]['a'] : ''; ?>
	        <tr>
	          <th>よくあるご相談 <?php echo (int) $i; ?><br><small style="font-weight:400;color:#787878">上が質問、下が答えです</small></th>
	          <td>
	            <input type="text" name="faq_q<?php echo (int) $i; ?>" class="large-text"
	              value="<?php echo esc_attr( $q ); ?>"
	              placeholder="例：雪で傷んだカーポートを直したい">
	            <textarea name="faq_a<?php echo (int) $i; ?>" rows="2" class="large-text"
	              style="margin-top:6px"
	              placeholder="例：積雪が多いため、耐積雪150cmのタイプをおすすめしています。"><?php
	              echo esc_textarea( $an ); ?></textarea>
	          </td>
	        </tr>
	        <?php endfor; ?>
	      </table>

	      <?php submit_button( 'この内容で保存する' ); ?>
	    </form>

	  <?php endif; ?>
	</div>
	<?php
}


/* ============================================================
   6. 保存されたら、件数の控えを捨てます
   ============================================================ */

add_action( 'save_post_ymkrf_works', function () { ymkrf_area_flush(); } );
add_action( 'save_post_ymkrf_voice', function () { ymkrf_area_flush(); } );

function ymkrf_area_flush() {
	foreach ( ymkrf_area_list() as $a ) {
		delete_transient( 'ymkrf_area_cnt_' . md5( $a['city'] ) );
	}
}


/* ============================================================
   7. URLのきまりを1度だけ作り直します
   ============================================================ */

add_action( 'admin_init', function () {
	if ( get_option( 'ymkrf_area_rules' ) === '1' ) return;
	flush_rewrite_rules( false );
	update_option( 'ymkrf_area_rules', '1' );
} );
