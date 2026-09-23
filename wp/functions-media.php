<?php
/**
 * functions-media.php ─ メディア（写真）を、使い道べつのフォルダに分けて見られるようにします
 *
 * 置き場所： wp-content/themes/ymkrf/inc/functions-media.php
 *
 * （2026/09/16 ユーザー指示
 *   「イベント・チラシみたいに右にフォルダ分けして、左のプルダウンは不要」
 *   「どこからの流入ではなく、どのカテゴリで使われているかで分けてください」）
 *
 * WordPress のメディアには、もともとフォルダがありません。
 * そこで、写真1枚ずつについて「どこで使われているか」を調べて、
 * フォルダに分けたように見せています。写真そのものは動かしません。
 *
 * 使われている場所は、次の順番で調べます。
 *   ① 商品の写真の欄（商品はメディアからえらぶ作りなので、ここが要ります）
 *   ② 施工事例のビフォー・アフター、チラシの表・裏の欄
 *   ③ アイキャッチ画像
 *   ④ その写真を上げたときの記事（post_parent）
 *   ⑤ ファイル名と写真の名前
 *      （rakuera-… のように商品の名前で始まれば 商品、
 *        「アンケート」なら お客様の声、など）
 * どれにも当てはまらないものが「どこにも使っていない」に入ります。
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* 分けかたを直したら、この数字をひとつ増やしてください。
   おぼえていた古い表が自動で捨てられ、すぐ新しい分けかたになります。 */
if ( ! defined( 'YMKRF_MEDIA_MAP_VER' ) ) define( 'YMKRF_MEDIA_MAP_VER', 7 );


/** フォルダの一覧（出る順番です） */
if ( ! function_exists( 'ymkrf_media_kinds' ) ) :
function ymkrf_media_kinds() {
	return array(
		'product' => array( 'name' => '商品',             'types' => array( 'ymkrf_product' ) ),
		'works'   => array( 'name' => '施工事例',         'types' => array( 'ymkrf_works' ) ),
		'voice'   => array( 'name' => 'お客様の声',       'types' => array( 'ymkrf_voice' ) ),
		'column'  => array( 'name' => 'コラム',           'types' => array( 'ymkrf_column' ) ),
		'news'    => array( 'name' => 'お知らせ',         'types' => array( 'ymkrf_news' ) ),
		'flyer'   => array( 'name' => 'イベント・チラシ', 'types' => array( 'ymkrf_flyer' ) ),
		'staff'   => array( 'name' => 'スタッフ',         'types' => array( 'ymkrf_staff' ) ),
		'page'    => array( 'name' => 'ページ・その他',   'types' => array( 'page', 'post' ) ),
		'none'    => array( 'name' => 'どこにも使っていない', 'types' => array() ),
	);
}
endif;

/** 記事の種類（post_type）から、フォルダの名前を引きます */
if ( ! function_exists( 'ymkrf_media_key_of_type' ) ) :
function ymkrf_media_key_of_type( $type ) {
	static $map = null;
	if ( $map === null ) {
		$map = array();
		foreach ( ymkrf_media_kinds() as $key => $k ) {
			foreach ( $k['types'] as $t ) $map[ $t ] = $key;
		}
	}
	return isset( $map[ $type ] ) ? $map[ $type ] : '';
}
endif;

/** 入れ子になった値の中から、写真のID（数字）だけを拾います */
if ( ! function_exists( 'ymkrf_media_pick_ids' ) ) :
function ymkrf_media_pick_ids( $v, &$out ) {
	if ( is_array( $v ) ) {
		foreach ( $v as $x ) ymkrf_media_pick_ids( $x, $out );
		return;
	}
	if ( is_numeric( $v ) ) {
		$n = (int) $v;
		if ( $n > 0 ) $out[] = $n;
	}
}
endif;


/**
 * 商品の名前（英字）の一覧。
 * 写真のファイル名が商品の名前で始まっていたら、商品の写真とみなします。
 *   例： rakuera-main.jpg → ラクエラ（キッチン）の写真
 * WordPressに登録した商品と、テーマの assets/img/products/ の
 * フォルダ名の、両方から集めます。
 */
if ( ! function_exists( 'ymkrf_media_product_slugs' ) ) :
function ymkrf_media_product_slugs() {

	static $slugs = null;
	if ( $slugs !== null ) return $slugs;

	global $wpdb;
	$out = array();

	$rows = $wpdb->get_col(
		"SELECT post_name FROM {$wpdb->posts}
		  WHERE post_type = 'ymkrf_product' AND post_name <> ''"
	);
	foreach ( (array) $rows as $n ) $out[] = strtolower( $n );

	$dir = get_stylesheet_directory() . '/assets/img/products';
	if ( is_dir( $dir ) ) {
		foreach ( (array) scandir( $dir ) as $n ) {
			if ( $n === '.' || $n === '..' ) continue;
			if ( $n[0] === '_' ) continue;               /* _pack4 などは除きます */
			if ( is_dir( $dir . '/' . $n ) ) $out[] = strtolower( $n );
		}
	}

	/* みじかすぎる名前は、ほかの写真に当たってしまうので外します */
	$out = array_values( array_unique( array_filter( $out, function ( $v ) {
		return strlen( $v ) >= 4;
	} ) ) );

	/* ながい名前から先に見ます（rakuera を raku より先に当てるため） */
	usort( $out, function ( $a, $b ) { return strlen( $b ) - strlen( $a ); } );

	$slugs = $out;
	return $slugs;
}
endif;

/**
 * 商品の日本語の名前の一覧（「クラッソ」「ラクエラ」など）。
 * 写真の名前にこれが入っていたら、商品の写真とみなします。
 */
if ( ! function_exists( 'ymkrf_media_product_names' ) ) :
function ymkrf_media_product_names() {

	static $names = null;
	if ( $names !== null ) return $names;

	global $wpdb;
	$out  = array();
	$rows = $wpdb->get_col(
		"SELECT post_title FROM {$wpdb->posts}
		  WHERE post_type = 'ymkrf_product' AND post_title <> ''"
	);
	foreach ( (array) $rows as $t ) {
		$t = trim( (string) $t );
		/* みじかい名前は、ほかの写真に当たってしまうので外します */
		if ( mb_strlen( $t, 'UTF-8' ) >= 3 ) $out[] = mb_strtolower( $t, 'UTF-8' );
	}

	$out = array_values( array_unique( $out ) );
	usort( $out, function ( $a, $b ) {
		return mb_strlen( $b, 'UTF-8' ) - mb_strlen( $a, 'UTF-8' );
	} );

	$names = $out;
	return $names;
}
endif;

/** 写真のファイル名や名前が、商品のものか */
if ( ! function_exists( 'ymkrf_media_is_product_file' ) ) :
function ymkrf_media_is_product_file( $file, $title = '' ) {

	/* 日本語の商品名（クラッソ など）が、写真の名前に入っていないか */
	$hay = mb_strtolower( trim( $title . ' ' . $file ), 'UTF-8' );
	if ( strpos( $hay, '%' ) !== false ) $hay .= ' ' . mb_strtolower( rawurldecode( $hay ), 'UTF-8' );
	if ( $hay !== '' ) {
		foreach ( ymkrf_media_product_names() as $n ) {
			if ( mb_strpos( $hay, $n ) !== false ) return true;
		}
	}

	$base = strtolower( basename( (string) $file ) );
	if ( $base === '' ) return false;

	$base = preg_replace( '/\.[a-z0-9]+$/', '', $base );      /* 拡張子を外します */
	$base = preg_replace( '/-\d+x\d+$/', '', $base );        /* -800x600 を外します */

	foreach ( ymkrf_media_product_slugs() as $slug ) {
		if ( $base === $slug ) return true;
		if ( strpos( $base, $slug ) === 0 ) {
			$next = substr( $base, strlen( $slug ), 1 );
			if ( $next === '-' || $next === '_' || ctype_digit( (string) $next ) ) return true;
		}
	}
	return false;
}
endif;

/** ファイル名から、フォルダの見当をつけます（どこにも結びつかなかったとき用） */
if ( ! function_exists( 'ymkrf_media_key_of_file' ) ) :
function ymkrf_media_key_of_file( $file ) {

	/* ファイル名が %e3%81%82… の形（日本語をURLの形にしたもの）でも
	   読めるように、もどしてから見ます。 */
	$f = (string) $file;
	if ( strpos( $f, '%' ) !== false ) $f .= ' ' . rawurldecode( $f );
	$f = mb_strtolower( $f, 'UTF-8' );
	if ( trim( $f ) === '' ) return '';

	/* 上から順に見て、はじめに当たったものにします */
	$rules = array(
		'voice'   => array( 'voice', 'アンケート', 'お客様の声' ),
		'works'   => array( 'works', 'before', 'after', '施工事例', 'ビフォー', 'アフター' ),
		'flyer'   => array( 'flyer', 'チラシ' ),
		'staff'   => array( 'staff', 'スタッフ' ),
		'product' => array( 'product', '商品' ),
		'column'  => array( 'column', 'コラム' ),
		'news'    => array( 'news', 'お知らせ' ),
	);

	foreach ( $rules as $key => $words ) {
		foreach ( $words as $w ) {
			if ( mb_strpos( $f, $w ) !== false ) return $key;
		}
	}
	return '';
}
endif;


/* ============================================================
   写真1枚ずつが、どのフォルダに入るかの表を作ります
   （数がおおいので、1時間おぼえておきます）
   ============================================================ */
if ( ! function_exists( 'ymkrf_media_map' ) ) :
function ymkrf_media_map( $force = false ) {

	if ( ! $force ) {
		$hit = get_transient( 'ymkrf_media_map_' . YMKRF_MEDIA_MAP_VER );
		if ( is_array( $hit ) ) return $hit;
	}

	global $wpdb;

	/* 使われている場所（写真のID => フォルダ）。
	   先に入れたものを残します（上のほうの調べかたを優先します）。 */
	$use = array();
	$put = function ( $id, $key ) use ( &$use ) {
		$id = (int) $id;
		if ( $id > 0 && $key !== '' && ! isset( $use[ $id ] ) ) $use[ $id ] = $key;
	};

	/* ---- ⓪ 手でフォルダを決めたもの（写真の編集画面でえらべます） ---- */
	$rows = $wpdb->get_results(
		"SELECT post_id, meta_value FROM {$wpdb->postmeta}
		  WHERE meta_key = '_ymkrf_media_use' AND meta_value <> ''"
	);
	$kinds_all = ymkrf_media_kinds();
	foreach ( (array) $rows as $r ) {
		$k = (string) $r->meta_value;
		if ( isset( $kinds_all[ $k ] ) ) $put( $r->post_id, $k );
	}

	/* ---- ① 商品の写真の欄 ----
	   商品は、写真をメディアからえらぶ作りです。
	   「どの記事といっしょに上げたか」では商品に結びつかないので、
	   入力欄の中身を見にいきます。 */
	if ( function_exists( 'ymkrf_product_repeaters' ) ) {

		$reps = array();
		foreach ( ymkrf_product_repeaters() as $key => $def ) {
			$cols = array();
			foreach ( (array) $def['cols'] as $col => $c ) {
				if ( isset( $c[1] ) && $c[1] === 'image' ) $cols[] = $col;
			}
			if ( $cols ) $reps[ $key ] = $cols;
		}

		if ( $reps ) {
			$in   = "'" . implode( "','", array_map( 'esc_sql', array_keys( $reps ) ) ) . "'";
			$rows = $wpdb->get_results(
				"SELECT pm.meta_key AS mk, pm.meta_value AS mv
				   FROM {$wpdb->postmeta} pm
				   JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				  WHERE pm.meta_key IN ( {$in} )
				    AND p.post_type = 'ymkrf_product'"
			);
			foreach ( (array) $rows as $r ) {
				$v = maybe_unserialize( $r->mv );
				if ( ! is_array( $v ) ) continue;
				$cols = isset( $reps[ $r->mk ] ) ? $reps[ $r->mk ] : array();
				foreach ( $v as $row ) {
					if ( ! is_array( $row ) ) continue;
					foreach ( $cols as $col ) {
						if ( isset( $row[ $col ] ) ) $put( $row[ $col ], 'product' );
					}
				}
			}
		}
	}

	/* ---- ② 施工事例・チラシの、決まった写真の欄 ---- */
	$fixed = array(
		'_ymkrf_before_img'   => 'works',
		'_ymkrf_before_imgs'  => 'works',
		'_ymkrf_after_imgs'   => 'works',
		'_ymkrf_during_imgs'  => 'works',
		'_ymkrf_flyer_front'  => 'flyer',
		'_ymkrf_flyer_back'   => 'flyer',
		/* お客様アンケートの読み取り画像（そのままのものと、出してよいもの） */
		'_ymkrf_survey_id'     => 'voice',
		'_ymkrf_survey_pub_id' => 'voice',
	);
	$in   = "'" . implode( "','", array_map( 'esc_sql', array_keys( $fixed ) ) ) . "'";
	$rows = $wpdb->get_results(
		"SELECT meta_key AS mk, meta_value AS mv
		   FROM {$wpdb->postmeta}
		  WHERE meta_key IN ( {$in} )"
	);
	foreach ( (array) $rows as $r ) {
		$ids = array();
		ymkrf_media_pick_ids( maybe_unserialize( $r->mv ), $ids );
		foreach ( $ids as $id ) $put( $id, $fixed[ $r->mk ] );
	}

	/* ---- ③ アイキャッチ画像 ---- */
	$rows = $wpdb->get_results(
		"SELECT pm.meta_value AS att, p.post_type AS pt
		   FROM {$wpdb->postmeta} pm
		   JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		  WHERE pm.meta_key = '_thumbnail_id'"
	);
	foreach ( (array) $rows as $r ) $put( $r->att, ymkrf_media_key_of_type( $r->pt ) );

	/* ---- ④ 写真を上げたときの記事 ---- */
	$rows = $wpdb->get_results(
		"SELECT att.ID AS id, COALESCE( par.post_type, '' ) AS pt,
		        att.post_title AS ttl,
		        COALESCE( fm.meta_value, '' ) AS f
		   FROM {$wpdb->posts} att
		   LEFT JOIN {$wpdb->posts} par ON par.ID = att.post_parent
		   LEFT JOIN {$wpdb->postmeta} fm
		          ON fm.post_id = att.ID AND fm.meta_key = '_wp_attached_file'
		  WHERE att.post_type = 'attachment'"
	);

	$out = array( 'ids' => array(), 'count' => array() );
	foreach ( ymkrf_media_kinds() as $key => $k ) {
		$out['ids'][ $key ]   = array();
		$out['count'][ $key ] = 0;
	}

	foreach ( (array) $rows as $r ) {
		$id  = (int) $r->id;
		$key = isset( $use[ $id ] ) ? $use[ $id ] : ymkrf_media_key_of_type( $r->pt );
		/* ---- ⑤ それでも分からないときは、ファイル名で見当をつけます ---- */
		/* ---- ⑤ ファイル名で見当をつけます ---- */
		if ( $key === '' && ymkrf_media_is_product_file( $r->f, $r->ttl ) ) $key = 'product';
		if ( $key === '' ) $key = ymkrf_media_key_of_file( $r->ttl . ' ' . $r->f );
		if ( $key === '' ) $key = 'none';
		$out['ids'][ $key ][] = $id;
		$out['count'][ $key ]++;
	}

	set_transient( 'ymkrf_media_map_' . YMKRF_MEDIA_MAP_VER, $out, HOUR_IN_SECONDS );
	return $out;
}
endif;

/* 写真や記事を直したら、おぼえていた表を捨てます */
if ( ! function_exists( 'ymkrf_media_forget' ) ) :
function ymkrf_media_forget() { delete_transient( 'ymkrf_media_map_' . YMKRF_MEDIA_MAP_VER ); }
endif;
add_action( 'add_attachment',    'ymkrf_media_forget' );
add_action( 'delete_attachment', 'ymkrf_media_forget' );
add_action( 'edit_attachment',   'ymkrf_media_forget' );
add_action( 'save_post',         'ymkrf_media_forget' );
add_action( 'deleted_post',      'ymkrf_media_forget' );


/* ------------------------------------------------------------
   「メディア」を押したときの、フォルダをえらぶ画面

   ★リンクに mode=list を付けています。
     写真がタイル状にならぶ「グリッド表示」は、あとから中身を
     読み込むしくみのため、しぼり込みが効かないからです。
   ------------------------------------------------------------ */
if ( ! function_exists( 'ymkrf_media_folders_page' ) ) :
function ymkrf_media_folders_page() {

	$force = ( ! empty( $_GET['ymkrf_recount'] ) && check_admin_referer( 'ymkrf_media_recount' ) );
	$map   = ymkrf_media_map( $force );
	$count = $map['count'];
	$all   = array_sum( $count );

	$card = function ( $name, $key, $n, $note = '' ) {
		$url = $key
			? admin_url( 'upload.php?ymkrf_use=' . $key . '&mode=list' )
			: admin_url( 'upload.php?mode=list' );
		?>
		<a class="ymkrf-mf__card<?php echo $key ? ' ymkrf-mf__card--cat' : ' ymkrf-mf__card--all'; ?>"
		   href="<?php echo esc_url( $url ); ?>">
		  <span class="ymkrf-mf__name"><?php echo esc_html( $name ); ?></span>
		  <?php if ( $note ) : ?><span class="ymkrf-mf__note"><?php echo esc_html( $note ); ?></span><?php endif; ?>
		  <span class="ymkrf-mf__cnt">
		    <?php if ( $n ) : ?>
		      <span class="ymkrf-mf__n"><?php echo esc_html( number_format_i18n( $n ) ); ?>枚</span>
		    <?php else : ?>
		      <span class="ymkrf-mf__zero">写真なし</span>
		    <?php endif; ?>
		  </span>
		</a>
		<?php
	};

	$notes = array(
		'product' => '商品のページに出している写真です。',
		'works'   => '施工事例のビフォー・アフターの写真です。',
		'voice'   => 'お客様の声で使っている写真です。',
		'column'  => 'コラムで使っている写真です。',
		'news'    => 'お知らせで使っている写真です。',
		'flyer'   => 'チラシの表・裏の写真です。',
		'staff'   => 'スタッフ紹介の顔写真です。',
		'page'    => '固定ページなどで使っている写真です。',
		'none'    => 'どのページでも使われていない写真です。',
	);
	?>
	<div class="wrap ymkrf-mf">
	  <h1>メディア
	    <a class="page-title-action" href="<?php echo esc_url( admin_url( 'media-new.php' ) ); ?>">新規追加</a>
	    <a class="page-title-action" href="<?php echo esc_url( wp_nonce_url(
	        admin_url( 'upload.php?page=ymkrf-media-folders&ymkrf_recount=1' ),
	        'ymkrf_media_recount' ) ); ?>">数えなおす</a>
	  </h1>
	  <?php if ( $force ) : ?>
	    <div class="notice notice-success"><p>数えなおしました。</p></div>
	  <?php endif; ?>

	  <h2 class="ymkrf-mf__h2">フォルダからえらぶ</h2>
	  <div class="ymkrf-mf__grid">
	    <?php foreach ( ymkrf_media_kinds() as $key => $k ) {
	      $n = isset( $count[ $key ] ) ? (int) $count[ $key ] : 0;
	      if ( $n === 0 && $key !== 'none' ) continue;   /* 0枚のものは出しません */
	      $card( $k['name'], $key, $n, isset( $notes[ $key ] ) ? $notes[ $key ] : '' );
	    } ?>
	  </div>

	  <h2 class="ymkrf-mf__h2">まとめて見る</h2>
	  <div class="ymkrf-mf__grid">
	    <?php $card( 'すべての写真', '', $all, 'フォルダを分けずに、ぜんぶ見ます。' ); ?>
	  </div>

	  <?php
	  /* 「どこにも使っていない」の中身を、そのまま見られるようにします。
	     どういう写真が入っているかを確かめる（そして私に伝える）ためのものです。 */
	  $none = isset( $map['ids']['none'] ) ? $map['ids']['none'] : array();
	  if ( $none ) :
	    global $wpdb;
	    $look = array_slice( array_map( 'intval', $none ), 0, 50 );
	    $rows = $wpdb->get_results(
	      "SELECT p.ID AS id, p.post_title AS ttl, COALESCE( fm.meta_value, '' ) AS f
	         FROM {$wpdb->posts} p
	         LEFT JOIN {$wpdb->postmeta} fm
	                ON fm.post_id = p.ID AND fm.meta_key = '_wp_attached_file'
	        WHERE p.ID IN ( " . implode( ',', $look ) . " )
	        ORDER BY p.ID DESC"
	    );
	    $txt = '';
	    foreach ( (array) $rows as $r ) {
	      $txt .= $r->id . "\t" . $r->ttl . "\t" . $r->f . "\n";
	    }
	  ?>
	  <details class="ymkrf-mf__peek">
	    <summary>「どこにも使っていない」の中身を見る（先頭50件）</summary>
	    <p>下の枠の中をぜんぶ選んでコピーすると、そのまま貼り付けて相談できます。</p>
	    <textarea readonly rows="12" onclick="this.select()"><?php echo esc_textarea( $txt ); ?></textarea>
	  </details>
	  <?php endif; ?>

	  <p class="ymkrf-mf__foot">
	    写真が<b>どのページで使われているか</b>で分けています。
	    写真そのものを動かしたりコピーしたりはしていません。<br>
	    枚数は1時間おぼえておきます。すぐに数えなおすときは
	    <a href="<?php echo esc_url( wp_nonce_url(
	        admin_url( 'upload.php?page=ymkrf-media-folders&ymkrf_recount=1' ),
	        'ymkrf_media_recount' ) ); ?>">数えなおす</a>
	    を押してください。
	  </p>
	</div>

	<style>
	  .ymkrf-mf__h2{margin:26px 0 10px;padding-left:9px;font-size:15px;
	    border-left:4px solid #fe3301;line-height:1.5}
	  .ymkrf-mf__grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(268px,1fr));gap:12px}
	  .ymkrf-mf__card{display:block;padding:13px 16px;background:#fff;border:1px solid #dcdcde;
	    border-radius:6px;text-decoration:none;color:inherit;transition:border-color .15s,box-shadow .15s}
	  .ymkrf-mf__card:hover{box-shadow:0 1px 6px rgba(0,0,0,.08)}
	  /* 「すべての写真」はオレンジ、フォルダはグリーンで囲みます */
	  .ymkrf-mf__card--all{border-color:#fe3301;background:#fff8f5}
	  .ymkrf-mf__card--all:hover{border-color:#fe3301}
	  .ymkrf-mf__card--cat{border-color:#00782a;background:#eff8f2}
	  .ymkrf-mf__card--cat:hover{border-color:#005a1f;background:#e4f3ea}
	  .ymkrf-mf__name{display:block;font-size:15px;font-weight:700;line-height:1.4}
	  .ymkrf-mf__note{display:block;margin-top:3px;font-size:11.5px;color:#787878;line-height:1.5}
	  .ymkrf-mf__cnt{display:block;margin-top:7px;font-size:12.5px;line-height:1.6}
	  .ymkrf-mf__n{color:#00782a;font-weight:700}
	  .ymkrf-mf__zero{color:#a7aaad}
	  .ymkrf-mf__foot{margin-top:22px;font-size:13px;color:#50575e;line-height:1.9}
	  .ymkrf-mf__peek{margin-top:24px;padding:12px 16px;background:#fff;border:1px solid #dcdcde;
	    border-radius:6px;max-width:900px;font-size:13px}
	  .ymkrf-mf__peek summary{cursor:pointer;font-weight:700}
	  .ymkrf-mf__peek p{margin:8px 0;color:#50575e}
	  .ymkrf-mf__peek textarea{width:100%;font-family:monospace;font-size:12px;white-space:pre}
	</style>
	<?php
}
endif;

/* 小メニューに登録します。
   ★いちばん上に置くと、「メディア」を押したときにこの画面が開きます
     （WordPress は、いちばん上の小メニューを行き先にするしくみです）。 */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'upload.php',
		'フォルダからえらぶ', 'フォルダからえらぶ',
		'upload_files', 'ymkrf-media-folders', 'ymkrf_media_folders_page'
	);
}, 20 );

/* 並べかた ── フォルダをえらぶ画面（かくれています） → ライブラリ → 新規追加 */
add_action( 'admin_menu', function () {
	global $submenu;
	if ( empty( $submenu['upload.php'] ) ) return;

	$top = array(); $rest = array();
	foreach ( $submenu['upload.php'] as $row ) {
		if ( isset( $row[2] ) && $row[2] === 'ymkrf-media-folders' ) $top[] = $row;
		else $rest[] = $row;
	}
	$submenu['upload.php'] = array_merge( $top, $rest );
}, 999 );

/* 「フォルダからえらぶ」は、左の「メディア」を押せば開くので、画面上は隠します */
add_action( 'admin_footer', function () {
	?>
	<script>
	(function () {
		var ul = document.querySelector('#menu-media .wp-submenu');
		if (!ul) return;
		var a  = ul.querySelector('a[href*="page=ymkrf-media-folders"]');
		var li = a && a.closest ? a.closest('li') : null;
		if (li && !li.classList.contains('wp-submenu-head')) li.style.display = 'none';
	})();
	</script>
	<?php
} );


/* ------------------------------------------------------------
   えらんだフォルダの写真だけを出します
   ------------------------------------------------------------ */
add_filter( 'posts_clauses', function ( $clauses, $q ) {

	if ( ! is_admin() || ! $q->is_main_query() ) return $clauses;
	if ( empty( $_GET['ymkrf_use'] ) ) return $clauses;
	if ( ! isset( $GLOBALS['pagenow'] ) || $GLOBALS['pagenow'] !== 'upload.php' ) return $clauses;

	$use   = sanitize_key( wp_unslash( $_GET['ymkrf_use'] ) );
	$kinds = ymkrf_media_kinds();
	if ( ! isset( $kinds[ $use ] ) ) return $clauses;

	global $wpdb;

	$map = ymkrf_media_map();
	$ids = isset( $map['ids'][ $use ] ) ? $map['ids'][ $use ] : array();

	if ( ! $ids ) {
		$clauses['where'] .= ' AND 1=0 ';   /* 1枚もありません */
		return $clauses;
	}

	$ids = array_map( 'intval', $ids );
	$clauses['where'] .= " AND {$wpdb->posts}.ID IN ( " . implode( ',', $ids ) . ' ) ';

	return $clauses;
}, 10, 2 );

/* 見出しに、いま見ているフォルダの名前を出します */
add_action( 'admin_notices', function () {

	if ( ! isset( $GLOBALS['pagenow'] ) || $GLOBALS['pagenow'] !== 'upload.php' ) return;
	if ( empty( $_GET['ymkrf_use'] ) ) return;

	$use   = sanitize_key( wp_unslash( $_GET['ymkrf_use'] ) );
	$kinds = ymkrf_media_kinds();
	if ( ! isset( $kinds[ $use ] ) ) return;

	$name = $kinds[ $use ]['name'];
	$msg  = ( $use === 'none' )
		? 'どのページでも使われていない写真です。'
		: $name . 'で使っている写真だけを出しています。';
	?>
	<div class="notice notice-info" style="margin-top:14px">
		<p style="font-size:13.5px"><b><?php echo esc_html( $name ); ?></b>　<?php echo esc_html( $msg ); ?>
			<a href="<?php echo esc_url( admin_url( 'upload.php?page=ymkrf-media-folders' ) ); ?>">フォルダ一覧にもどる</a>

			<a href="<?php echo esc_url( admin_url( 'upload.php?mode=list' ) ); ?>">すべての写真を見る</a></p>
	</div>
	<?php
} );


/* ------------------------------------------------------------
   写真の編集画面に「フォルダ」の欄を出します
   （自動でうまく分けられなかったものを、手で直すためのものです）
   ------------------------------------------------------------ */
add_filter( 'attachment_fields_to_edit', function ( $fields, $post ) {

	$now  = (string) get_post_meta( $post->ID, '_ymkrf_media_use', true );
	$html = '<select name="attachments[' . (int) $post->ID . '][ymkrf_media_use]">';
	$html .= '<option value="">自動でふりわける</option>';
	foreach ( ymkrf_media_kinds() as $key => $k ) {
		$html .= '<option value="' . esc_attr( $key ) . '"' . selected( $now, $key, false ) . '>'
		       . esc_html( $k['name'] ) . '</option>';
	}
	$html .= '</select>';

	$fields['ymkrf_media_use'] = array(
		'label' => 'フォルダ',
		'input' => 'html',
		'html'  => $html,
		'helps' => 'メディアの画面で、どのフォルダに入れるかです。ふだんは「自動でふりわける」のままで大丈夫です。',
	);
	return $fields;
}, 10, 2 );

add_filter( 'attachment_fields_to_save', function ( $post, $attachment ) {

	if ( ! isset( $attachment['ymkrf_media_use'] ) ) return $post;

	$v     = sanitize_key( $attachment['ymkrf_media_use'] );
	$kinds = ymkrf_media_kinds();

	if ( $v !== '' && isset( $kinds[ $v ] ) ) {
		update_post_meta( $post['ID'], '_ymkrf_media_use', $v );
	} else {
		delete_post_meta( $post['ID'], '_ymkrf_media_use' );
	}
	ymkrf_media_forget();

	return $post;
}, 10, 2 );


/* ============================================================
   写真のファイル名を、自動で英字にそろえます
   （2026/09/16 ユーザー指示「これから上げる写真のファイル名を整える」）

   日本語のファイル名は、ブラウザの中で
     クラッソimage.jpg → %e3%82%af%e3%83%a9%e3%83%83%e3%82%bdimage.jpg
   のような形になり、人にも検索エンジンにも読めません。
   そこで、アップロードするときに英字へ直します。

   直しかたは、上から順に：
     ① 登録してある商品の名前（クラッソ → classo）
     ② よく使う言葉の表（キッチン → kitchen、アンケート → survey）
     ③ カタカナ・ひらがな → ローマ字
     ④ 漢字など、どうにもならないものは外す
   ぜんぶ外れて空になったときは photo-日付 にします。

   ★もともと英字のファイル名は、そのままにします。
   ============================================================ */

/** ①登録してある商品の「日本語の名前 => 英字の名前」 */
if ( ! function_exists( 'ymkrf_media_name_dict_products' ) ) :
function ymkrf_media_name_dict_products() {

	static $d = null;
	if ( $d !== null ) return $d;

	global $wpdb;
	$d    = array();
	$rows = $wpdb->get_results(
		"SELECT post_title AS ttl, post_name AS slug FROM {$wpdb->posts}
		  WHERE post_type = 'ymkrf_product' AND post_title <> '' AND post_name <> ''"
	);
	foreach ( (array) $rows as $r ) {
		$t = trim( (string) $r->ttl );
		if ( mb_strlen( $t, 'UTF-8' ) >= 2 ) $d[ $t ] = rawurldecode( (string) $r->slug );
	}

	/* ながい名前から先に置きかえます */
	uksort( $d, function ( $a, $b ) {
		return mb_strlen( $b, 'UTF-8' ) - mb_strlen( $a, 'UTF-8' );
	} );

	return $d;
}
endif;

/** ②よく使う言葉の表 */
if ( ! function_exists( 'ymkrf_media_name_dict' ) ) :
function ymkrf_media_name_dict() {

	$d = array(
		'お客様の声'   => 'voice',
		'アンケート'   => 'survey',
		'施工事例'     => 'works',
		'ビフォー'     => 'before',
		'アフター'     => 'after',
		'施工前'       => 'before',
		'施工後'       => 'after',
		'チラシ'       => 'flyer',
		'スタッフ'     => 'staff',
		'商品'         => 'product',
		'キッチン'     => 'kitchen',
		'お風呂'       => 'bath',
		'浴室'         => 'bath',
		'トイレ'       => 'toilet',
		'洗面化粧台'   => 'lavatory',
		'洗面'         => 'lavatory',
		'給湯器'       => 'boiler',
		'エコキュート' => 'ecocute',
		'外壁'         => 'outer-wall',
		'屋根'         => 'roof',
		'内装'         => 'interior',
		'窓'           => 'window',
		'玄関'         => 'entrance',
		'カタログ'     => 'catalog',
		'見本'         => 'sample',
		'扉'           => 'door',
		'取手'         => 'handle',
		'色'           => 'color',
		'写真'         => 'photo',
		'画像'         => 'image',
		'イメージ'     => 'image',
		'お客様'       => 'customer',
		'カラー'       => 'color',
		'ホワイト'     => 'white',
		'ブラック'     => 'black',
		'リフォーム'   => 'reform',
		'メーカー'     => 'maker',
		'サイズ'       => 'size',
		'表面'         => 'front',
		'裏面'         => 'back',
		'正面'         => 'front',
		'全体'         => 'full',

		/* ここから下は、もともと英語の言葉です。
		   そのままローマ字にすると nesuto のようになってしまうので、
		   英語のつづりに置きかえます
		   （2026/09/23 ユーザー指示「ネストですが、nestなので、
		     URLがnesutoになっていたら変更して」）。
		   ほかにも出てきたら、この下に1行ずつ足してください。 */
		'ネスト'       => 'nest',
	);

	/* メーカーの名前は、登録してあるメーカーのスラッグを使います
	   （ノーリツ → noritz、クリナップ → cleanup など。
	     2026/09/23 ユーザー指示「ノーリツも、noritzね」）。
	   メーカーを足したり、スラッグを直したりすれば、ここも自動でついてきます。
	   ＝ 商品 ＞ メーカーの設定 で直せます。 */
	if ( taxonomy_exists( 'ymkrf_maker' ) ) {
		$mk = get_terms( array( 'taxonomy' => 'ymkrf_maker', 'hide_empty' => false ) );
		if ( ! is_wp_error( $mk ) ) {
			foreach ( (array) $mk as $t ) {
				$n = trim( (string) $t->name );
				$g = (string) $t->slug;
				if ( $g === '' ) continue;
				if ( mb_strlen( $n, 'UTF-8' ) >= 2 ) $d[ $n ] = $g;
				/* 「WOODONE（ウッドワン）」のように、かっこ書きのカタカナも拾います */
				if ( preg_match( '/[（(]([^）)]+)[）)]/u', $n, $m2 ) ) {
					$in = trim( $m2[1] );
					if ( mb_strlen( $in, 'UTF-8' ) >= 2 ) $d[ $in ] = $g;
				}
			}
		}
	}

	/* ながい言葉から先に置きかえます
	   （「お客様の声」を「お客様」より先に当てるため） */
	uksort( $d, function ( $a, $b ) {
		return mb_strlen( $b, 'UTF-8' ) - mb_strlen( $a, 'UTF-8' );
	} );

	return $d;
}
endif;

/** ③カタカナ・ひらがなを、ローマ字にします */
if ( ! function_exists( 'ymkrf_media_kana_romaji' ) ) :
function ymkrf_media_kana_romaji( $s ) {

	/* ひらがなは、いったんカタカナにそろえます */
	$s = mb_convert_kana( $s, 'KVC', 'UTF-8' );

	$two = array(
		'キャ'=>'kya','キュ'=>'kyu','キョ'=>'kyo','シャ'=>'sha','シュ'=>'shu','ショ'=>'sho',
		'チャ'=>'cha','チュ'=>'chu','チョ'=>'cho','ニャ'=>'nya','ニュ'=>'nyu','ニョ'=>'nyo',
		'ヒャ'=>'hya','ヒュ'=>'hyu','ヒョ'=>'hyo','ミャ'=>'mya','ミュ'=>'myu','ミョ'=>'myo',
		'リャ'=>'rya','リュ'=>'ryu','リョ'=>'ryo','ギャ'=>'gya','ギュ'=>'gyu','ギョ'=>'gyo',
		'ジャ'=>'ja','ジュ'=>'ju','ジョ'=>'jo','ビャ'=>'bya','ビュ'=>'byu','ビョ'=>'byo',
		'ピャ'=>'pya','ピュ'=>'pyu','ピョ'=>'pyo',
		'ファ'=>'fa','フィ'=>'fi','フェ'=>'fe','フォ'=>'fo','ヴァ'=>'va','ヴィ'=>'vi',
		'ヴェ'=>'ve','ヴォ'=>'vo','ウィ'=>'wi','ウェ'=>'we','ウォ'=>'wo',
		'ティ'=>'ti','ディ'=>'di','トゥ'=>'tu','ドゥ'=>'du','シェ'=>'she','ジェ'=>'je','チェ'=>'che',
	);
	$one = array(
		'ア'=>'a','イ'=>'i','ウ'=>'u','エ'=>'e','オ'=>'o',
		'カ'=>'ka','キ'=>'ki','ク'=>'ku','ケ'=>'ke','コ'=>'ko',
		'サ'=>'sa','シ'=>'shi','ス'=>'su','セ'=>'se','ソ'=>'so',
		'タ'=>'ta','チ'=>'chi','ツ'=>'tsu','テ'=>'te','ト'=>'to',
		'ナ'=>'na','ニ'=>'ni','ヌ'=>'nu','ネ'=>'ne','ノ'=>'no',
		'ハ'=>'ha','ヒ'=>'hi','フ'=>'fu','ヘ'=>'he','ホ'=>'ho',
		'マ'=>'ma','ミ'=>'mi','ム'=>'mu','メ'=>'me','モ'=>'mo',
		'ヤ'=>'ya','ユ'=>'yu','ヨ'=>'yo',
		'ラ'=>'ra','リ'=>'ri','ル'=>'ru','レ'=>'re','ロ'=>'ro',
		'ワ'=>'wa','ヲ'=>'o','ン'=>'n',
		'ガ'=>'ga','ギ'=>'gi','グ'=>'gu','ゲ'=>'ge','ゴ'=>'go',
		'ザ'=>'za','ジ'=>'ji','ズ'=>'zu','ゼ'=>'ze','ゾ'=>'zo',
		'ダ'=>'da','ヂ'=>'ji','ヅ'=>'zu','デ'=>'de','ド'=>'do',
		'バ'=>'ba','ビ'=>'bi','ブ'=>'bu','ベ'=>'be','ボ'=>'bo',
		'パ'=>'pa','ピ'=>'pi','プ'=>'pu','ペ'=>'pe','ポ'=>'po',
		'ヴ'=>'vu','ァ'=>'a','ィ'=>'i','ゥ'=>'u','ェ'=>'e','ォ'=>'o',
		'ャ'=>'ya','ュ'=>'yu','ョ'=>'yo','ー'=>'',
	);

	$out = '';
	$len = mb_strlen( $s, 'UTF-8' );
	for ( $i = 0; $i < $len; $i++ ) {

		$c  = mb_substr( $s, $i, 1, 'UTF-8' );
		$c2 = ( $i + 1 < $len ) ? $c . mb_substr( $s, $i + 1, 1, 'UTF-8' ) : '';

		/* 小さい「ッ」は、次の音のはじめの字を2つにします */
		if ( $c === 'ッ' ) {
			$nx = '';
			if ( $c2 !== '' ) {
				$n2 = mb_substr( $s, $i + 1, 2, 'UTF-8' );
				if ( isset( $two[ $n2 ] ) )                      $nx = $two[ $n2 ];
				elseif ( isset( $one[ mb_substr( $s, $i + 1, 1, 'UTF-8' ) ] ) )
					$nx = $one[ mb_substr( $s, $i + 1, 1, 'UTF-8' ) ];
			}
			if ( $nx !== '' ) $out .= substr( $nx, 0, 1 );
			continue;
		}

		if ( $c2 !== '' && isset( $two[ $c2 ] ) ) { $out .= $two[ $c2 ]; $i++; continue; }
		if ( isset( $one[ $c ] ) )                { $out .= $one[ $c ];        continue; }

		$out .= $c;   /* 英数字などは、そのまま */
	}
	return $out;
}
endif;

/** ファイル名まるごとを、英字に直します */
if ( ! function_exists( 'ymkrf_media_ascii_name' ) ) :
function ymkrf_media_ascii_name( $name ) {

	$ext  = '';
	$base = $name;
	if ( preg_match( '/^(.*)(\.[A-Za-z0-9]{1,5})$/', $name, $m ) ) {
		$base = $m[1];
		$ext  = strtolower( $m[2] );
	}

	/* もともと英字だけなら、さわりません */
	if ( preg_match( '/^[A-Za-z0-9._-]+$/', $base ) ) return $name;

	$base = mb_convert_kana( $base, 'as', 'UTF-8' );   /* 全角の英数字と空白を半角に */

	foreach ( ymkrf_media_name_dict_products() as $ja => $en ) {
		if ( $en !== '' ) $base = str_replace( $ja, '-' . $en . '-', $base );
	}
	foreach ( ymkrf_media_name_dict() as $ja => $en ) {
		$base = str_replace( $ja, '-' . $en . '-', $base );
	}

	$base = ymkrf_media_kana_romaji( $base );

	/* 残った漢字などは外します */
	$base = preg_replace( '/[^A-Za-z0-9._-]+/u', '-', $base );
	$base = strtolower( $base );
	$base = preg_replace( '/-{2,}/', '-', $base );
	$base = trim( $base, '-._' );

	if ( $base === '' ) $base = 'photo-' . date_i18n( 'Ymd-His' );

	return $base . $ext;
}
endif;

/* アップロードするときに、名前を英字へ直します */
add_filter( 'wp_handle_upload_prefilter', function ( $file ) {
	if ( ! empty( $file['name'] ) ) $file['name'] = ymkrf_media_ascii_name( $file['name'] );
	return $file;
} );

/* 取り込み（media_handle_sideload）のときも同じようにします */
add_filter( 'wp_handle_sideload_prefilter', function ( $file ) {
	if ( ! empty( $file['name'] ) ) $file['name'] = ymkrf_media_ascii_name( $file['name'] );
	return $file;
} );


/* ============================================================
   すでに入っている写真の名前を、正しいつづりに直します（1回だけ）
   ------------------------------------------------------------
   （2026/09/23 ユーザー指示
     「ネストですが、nestなので、URLがnesutoになっていたら変更して」
     「ノーリツも、noritzね」
      → noritsunest-nesuto.jpg のような名前が残っていたため）

   ・直す表は、登録してあるメーカーから自動で作ります
       ノーリツ … ローマ字にすると noritsu ですが、スラッグは noritz
       クリナップ … kurinappu ですが、スラッグは cleanup
     ＋「nesuto → nest」だけ、手で足しています。
   ・ファイルそのもの（大きさちがいも全部）の名前を変えます
   ・メディアの情報（_wp_attached_file／_wp_attachment_metadata／guid）も直します
   ・本文に古いURLが書いてあれば、そこも書きかえます
   ・すでに正しいつづりが入っている名前は、くり返しにならないように
     まちがいのほうを外します（noritsunest-nesuto → noritznest）

   1回動いたら、二度と動きません（ymkrf_media_name_fix_ver を見ています）。
   本番に出したあとは、この節ごと消してかまいません。
   ============================================================ */

/** まちがったつづり => 正しいつづり の表 */
if ( ! function_exists( 'ymkrf_media_fix_table' ) ) :
function ymkrf_media_fix_table() {

	$t = array( 'nesuto' => 'nest' );

	if ( ! taxonomy_exists( 'ymkrf_maker' ) ) return $t;

	$mk = get_terms( array( 'taxonomy' => 'ymkrf_maker', 'hide_empty' => false ) );
	if ( is_wp_error( $mk ) ) return $t;

	foreach ( (array) $mk as $m ) {

		$slug = (string) $m->slug;
		if ( $slug === '' ) continue;

		/* カタカナの名前を、いったんローマ字にしてみます
		   （＝これまでファイル名に入っていた、まちがったつづり） */
		$was = ymkrf_media_kana_romaji( (string) $m->name );
		$was = strtolower( (string) preg_replace( '/[^A-Za-z0-9]+/', '', $was ) );

		/* 漢字が残ったもの（三菱電機など）や、もう同じものは使いません */
		if ( $was === '' || strlen( $was ) < 4 || $was === $slug ) continue;

		$t[ $was ] = $slug;
	}

	/* ながいつづりから先に直します（takarasutandado を takara より先に） */
	uksort( $t, function ( $a, $b ) { return strlen( $b ) - strlen( $a ); } );

	return $t;
}
endif;

/** ファイル名を、正しいつづりに直します */
if ( ! function_exists( 'ymkrf_media_fix_name' ) ) :
function ymkrf_media_fix_name( $file ) {

	$dot  = strrpos( $file, '.' );
	$ext  = ( $dot === false ) ? '' : substr( $file, $dot );
	$body = ( $dot === false ) ? $file : substr( $file, 0, $dot );
	$new  = $body;

	foreach ( ymkrf_media_fix_table() as $was => $now ) {

		if ( strpos( $new, $was ) === false ) continue;

		/* 正しいつづりが、すでにほかの場所に入っているとき（noritsunest-nesuto の
		   「nest」など）は、くり返しにならないよう、まちがいのほうを外します */
		$off = preg_replace( '/-?' . preg_quote( $was, '/' ) . '/', '', $new );

		if ( strpos( $off, $now ) !== false ) {
			$new = $off;
		} else {
			/* 前後に「-」を入れて、言葉の切れ目が分かるようにします
			   （noritsunest → noritz-nest。
			     2026/09/23 ユーザー指示「noritz-nest.jpg に変えて」） */
			$new = str_replace( $was, '-' . $now . '-', $new );
		}

		/* 「-」「_」がつづいたら、1つの「-」にまとめます（cleanup-_horo3 → cleanup-horo3） */
		$new = trim( (string) preg_replace( '/[-_]{2,}/', '-', $new ), '-_' );
	}

	$new = trim( (string) preg_replace( '/-{2,}/', '-', $new ), '-' );
	if ( $new === '' ) return $file;

	return $new . $ext;
}
endif;

add_action( 'admin_init', function () {

	if ( get_option( 'ymkrf_media_name_fix_ver' ) === '1' ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;

	global $wpdb;

	$rows = $wpdb->get_results(
		"SELECT post_id, meta_value FROM {$wpdb->postmeta}
		  WHERE meta_key = '_wp_attached_file'"
	);

	$up   = wp_get_upload_dir();
	$done = 0;

	foreach ( (array) $rows as $r ) {

		$id  = (int) $r->post_id;
		$rel = (string) $r->meta_value;              /* 例：2026/09/noritsunest-nesuto.jpg */
		$sub = ( dirname( $rel ) === '.' ) ? '' : dirname( $rel ) . '/';

		$newrel = $sub . ymkrf_media_fix_name( basename( $rel ) );
		if ( $newrel === $rel ) continue;

		$from = $up['basedir'] . '/' . $rel;
		$to   = $up['basedir'] . '/' . $newrel;
		if ( ! file_exists( $from ) || file_exists( $to ) ) continue;
		if ( ! @rename( $from, $to ) ) continue;

		update_post_meta( $id, '_wp_attached_file', $newrel );

		/* 大きさちがい（サムネイルなど）も、いっしょに直します */
		$meta = wp_get_attachment_metadata( $id );
		if ( is_array( $meta ) ) {

			if ( ! empty( $meta['file'] ) ) $meta['file'] = $newrel;

			if ( ! empty( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
				foreach ( $meta['sizes'] as $k => $s ) {
					if ( empty( $s['file'] ) ) continue;
					$sn = ymkrf_media_fix_name( $s['file'] );
					if ( $sn === $s['file'] ) continue;
					$sf = $up['basedir'] . '/' . $sub . $s['file'];
					$st = $up['basedir'] . '/' . $sub . $sn;
					if ( file_exists( $sf ) && ! file_exists( $st ) && @rename( $sf, $st ) ) {
						$meta['sizes'][ $k ]['file'] = $sn;
					}
				}
			}

			/* 大きい写真を縮めたときの「元の写真」 */
			if ( ! empty( $meta['original_image'] ) ) {
				$on = ymkrf_media_fix_name( $meta['original_image'] );
				if ( $on !== $meta['original_image'] ) {
					$of = $up['basedir'] . '/' . $sub . $meta['original_image'];
					$ot = $up['basedir'] . '/' . $sub . $on;
					if ( file_exists( $of ) && ! file_exists( $ot ) && @rename( $of, $ot ) ) {
						$meta['original_image'] = $on;
					}
				}
			}

			wp_update_attachment_metadata( $id, $meta );
		}

		/* メディアそのもののURL（guid）と、本文に書かれた古いURL */
		$oldurl = $up['baseurl'] . '/' . $rel;
		$newurl = $up['baseurl'] . '/' . $newrel;
		$wpdb->update( $wpdb->posts, array( 'guid' => $newurl ), array( 'ID' => $id ) );
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->posts} SET post_content = REPLACE( post_content, %s, %s )
			  WHERE post_content LIKE %s",
			$oldurl, $newurl, '%' . $wpdb->esc_like( basename( $rel ) ) . '%'
		) );

		clean_post_cache( $id );
		$done++;
	}

	update_option( 'ymkrf_media_name_fix_ver', '1', false );

	if ( $done ) set_transient( 'ymkrf_media_name_fix_msg', $done, 120 );
} );

/* 直したときだけ、管理画面に一言出します */
add_action( 'admin_notices', function () {
	$n = get_transient( 'ymkrf_media_name_fix_msg' );
	if ( ! $n ) return;
	delete_transient( 'ymkrf_media_name_fix_msg' );
	echo '<div class="notice notice-success is-dismissible"><p>写真の名前のつづりを直しました（'
	   . (int) $n . '枚）。</p></div>';
} );


/* ============================================================
   商品の写真のファイル名を、かんたんな形で自動でつけます
   ------------------------------------------------------------
   （2026/09/23 ユーザー指示「URLはシンプルに、汲み取って自動で作って」）

   商品の編集画面から写真を上げると、名前をこうします。

       メーカー － 商品名の中の英語（なければ型番）

   　例： ノーリツの「ビルトインコンロLPガス用NEST(ネスト)」
   　　　　→ noritz-nest.jpg
   　　　 ノーリツの「…Fami(ファミ)スタンダードタイプ」
   　　　　→ noritz-fami.jpg
   　　　 クリナップの「…ホーロー片面」（英語の名前なし）
   　　　　→ cleanup-zgfnk6r18nke-e.jpg（型番から）

   2枚目からは、WordPressが自動で -1 -2 を付けます。
   ============================================================ */

/** その商品の写真につける、かんたんな名前（拡張子なし） */
if ( ! function_exists( 'ymkrf_media_product_filebase' ) ) :
function ymkrf_media_product_filebase( $pid ) {

	$pid = (int) $pid;
	if ( ! $pid || get_post_type( $pid ) !== 'ymkrf_product' ) return '';

	/* メーカー（登録してあるスラッグをそのまま使います） */
	$mk    = wp_get_object_terms( $pid, 'ymkrf_maker', array( 'fields' => 'slugs' ) );
	$maker = ( ! is_wp_error( $mk ) && ! empty( $mk ) ) ? (string) $mk[0] : '';

	/* 商品名の中の英語（NEST、Fami など）。
	   「LP」のようにみじかいものは、名前ではないので使いません。 */
	$name = trim( (string) get_post_meta( $pid, '_ymkrf_name', true ) );
	if ( $name === '' ) $name = (string) get_the_title( $pid );

	$word = '';
	if ( preg_match_all( '/[A-Za-z][A-Za-z0-9]{2,}/', $name, $m ) ) {
		foreach ( $m[0] as $one ) {
			if ( strlen( $one ) > strlen( $word ) ) $word = $one;
		}
	}

	/* 英語の名前が無ければ、型番を使います */
	if ( $word === '' ) $word = trim( (string) get_post_meta( $pid, '_ymkrf_model', true ) );

	/* それも無ければ、いまのURL（分類の頭は外します） */
	if ( $word === '' ) {
		$word = rawurldecode( (string) get_post_field( 'post_name', $pid ) );
		$cat  = function_exists( 'ymkrf_product_current_cat' ) ? ymkrf_product_current_cat( $pid ) : '';
		if ( $cat !== '' && strpos( $word, $cat . '-' ) === 0 ) $word = substr( $word, strlen( $cat ) + 1 );
	}

	$out = strtolower( trim( $maker . '-' . $word, '-' ) );
	$out = preg_replace( '/[^a-z0-9]+/', '-', $out );
	$out = trim( (string) preg_replace( '/-{2,}/', '-', $out ), '-' );

	return $out;
}
endif;

/* 商品の編集画面から上げた写真は、上の名前にします。
   （メディアの画面から直接上げたときは、これまでどおりです） */
add_filter( 'wp_handle_upload_prefilter', function ( $file ) {

	$pid = 0;
	if ( isset( $_REQUEST['post_id'] ) ) $pid = (int) $_REQUEST['post_id'];   /* phpcs:ignore */
	if ( ! $pid ) return $file;

	$base = ymkrf_media_product_filebase( $pid );
	if ( $base === '' || empty( $file['name'] ) ) return $file;

	$ext = '';
	if ( preg_match( '/(\.[A-Za-z0-9]{1,5})$/', $file['name'], $m ) ) $ext = strtolower( $m[1] );

	$file['name'] = $base . $ext;
	return $file;
}, 5 );   /* 英字に直す filter より先に動かします */


/* ------------------------------------------------------------
   コンロ・IHの商品写真だけ、いまの名前も上の形にそろえます（1回だけ）
   （2026/09/23 ユーザー指示「URLはシンプルに、汲み取って自動で作って」）
   ほかの分類の写真はさわりません。
   本番に出したあとは、この節ごと消してかまいません。
   ------------------------------------------------------------ */
add_action( 'admin_init', function () {

	if ( get_option( 'ymkrf_media_cooktop_name_ver' ) === '1' ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;

	$ids = get_posts( array(
		'post_type'      => 'ymkrf_product',
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'fields'         => 'ids',
		'tax_query'      => array( array(
			'taxonomy' => 'ymkrf_product_cat', 'field' => 'slug', 'terms' => 'cooktop',
		) ),
	) );

	$up   = wp_get_upload_dir();
	$done = 0;

	foreach ( (array) $ids as $pid ) {

		$att = (int) get_post_thumbnail_id( $pid );
		if ( ! $att ) continue;

		$base = ymkrf_media_product_filebase( $pid );
		if ( $base === '' ) continue;

		$rel = (string) get_post_meta( $att, '_wp_attached_file', true );
		if ( $rel === '' ) continue;

		$sub = ( dirname( $rel ) === '.' ) ? '' : dirname( $rel ) . '/';
		$ext = '';
		if ( preg_match( '/(\.[A-Za-z0-9]{1,5})$/', $rel, $m ) ) $ext = strtolower( $m[1] );

		$newrel = $sub . $base . $ext;
		if ( $newrel === $rel ) continue;

		$from = $up['basedir'] . '/' . $rel;
		$to   = $up['basedir'] . '/' . $newrel;
		if ( ! file_exists( $from ) || file_exists( $to ) ) continue;
		if ( ! @rename( $from, $to ) ) continue;

		update_post_meta( $att, '_wp_attached_file', $newrel );

		$meta = wp_get_attachment_metadata( $att );
		if ( is_array( $meta ) ) {

			if ( ! empty( $meta['file'] ) ) $meta['file'] = $newrel;

			if ( ! empty( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
				foreach ( $meta['sizes'] as $k => $s ) {
					if ( empty( $s['file'] ) ) continue;
					/* -800x600 の部分だけ残して、頭を新しい名前にします */
					$tail = '';
					if ( preg_match( '/(-\d+x\d+)(\.[A-Za-z0-9]{1,5})$/', $s['file'], $m2 ) ) {
						$tail = $m2[1] . strtolower( $m2[2] );
					}
					if ( $tail === '' ) continue;
					$sn = $base . $tail;
					$sf = $up['basedir'] . '/' . $sub . $s['file'];
					$st = $up['basedir'] . '/' . $sub . $sn;
					if ( file_exists( $sf ) && ! file_exists( $st ) && @rename( $sf, $st ) ) {
						$meta['sizes'][ $k ]['file'] = $sn;
					}
				}
			}
			wp_update_attachment_metadata( $att, $meta );
		}

		global $wpdb;
		$wpdb->update( $wpdb->posts,
			array( 'guid' => $up['baseurl'] . '/' . $newrel ), array( 'ID' => $att ) );
		clean_post_cache( $att );
		$done++;
	}

	update_option( 'ymkrf_media_cooktop_name_ver', '1', false );
	if ( $done ) set_transient( 'ymkrf_media_name_fix_msg', $done, 120 );
} );
