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
if ( ! defined( 'YMKRF_MEDIA_MAP_VER' ) ) define( 'YMKRF_MEDIA_MAP_VER', 11 );


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

/** フォルダの中のフォルダ（2026/10/23 ユーザー「カタログが見つけられない。商品のフォルダのカタログの中に入れておいてほしい」）
 *  中のフォルダの写真は、親のフォルダ（商品）にも数えます。 */
if ( ! function_exists( 'ymkrf_media_subkinds' ) ) :
function ymkrf_media_subkinds() {
	return array(
		'catalog' => array( 'name' => 'カタログ', 'parent' => 'product',
		                    'note' => '商品ページの「メーカーのカタログ」に使う画像です。' ),
	);
}
endif;

/** カタログの画像のID（カタログの欄でえらんだもの・名前に catalog／カタログ が入るもの・手でカタログにしたもの） */
if ( ! function_exists( 'ymkrf_media_catalog_ids' ) ) :
function ymkrf_media_catalog_ids() {
	global $wpdb;
	$ids = array_map( 'intval', (array) $wpdb->get_col(
		"SELECT DISTINCT meta_value FROM {$wpdb->postmeta}
		  WHERE meta_key = '_ymkrf_cat_img' AND meta_value REGEXP '^[0-9]+$'" ) );
	$ids = array_merge( $ids, array_map( 'intval', (array) $wpdb->get_col(
		"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ymkrf_media_use' AND meta_value = 'catalog'" ) ) );
	$ids = array_merge( $ids, array_map( 'intval', (array) $wpdb->get_col(
		"SELECT p.ID FROM {$wpdb->posts} p
		   LEFT JOIN {$wpdb->postmeta} fm ON fm.post_id = p.ID AND fm.meta_key = '_wp_attached_file'
		  WHERE p.post_type = 'attachment'
		    AND ( p.post_title LIKE '%catalog%' OR p.post_title LIKE '%カタログ%' OR fm.meta_value LIKE '%catalog%' )" ) ) );
	/* 手で別のフォルダにしたものは外します */
	$other = array_map( 'intval', (array) $wpdb->get_col(
		"SELECT post_id FROM {$wpdb->postmeta} WHERE ( meta_key = '_ymkrf_media_use' AND meta_value NOT IN ( '', 'catalog', 'product' ) )
		    OR ( meta_key = '_ymkrf_media_prod' AND meta_value <> '' )" ) );
	return array_values( array_diff( array_unique( array_filter( $ids ) ), $other ) );
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
	$manual    = array();   /* 写真のID => 手で決めたフォルダ（引っ越し・入れるときの保存先） */
	foreach ( (array) $rows as $r ) {
		$k = (string) $r->meta_value;
		$manual[ (int) $r->post_id ] = $k;
		if ( isset( $kinds_all[ $k ] ) ) $put( $r->post_id, $k );
	}
	/* 手で決めた「商品 ＞ 商品名」 */
	$mprod = array();
	foreach ( (array) $wpdb->get_results(
		"SELECT post_id, meta_value FROM {$wpdb->postmeta}
		  WHERE meta_key = '_ymkrf_media_prod' AND meta_value REGEXP '^[0-9]+$'" ) as $r ) {
		$mprod[ (int) $r->post_id ] = (int) $r->meta_value;
		$manual[ (int) $r->post_id ] = 'product';
		$put( $r->post_id, 'product' );
	}

	/* 商品ごとの写真（商品のID => 写真のID の表）。商品 ＞ 商品名 のフォルダに使います
	   （2026/10/23 ユーザー「商品＞商品名　とフォルダ分けしてほしい」）。
	   1枚の写真が、いくつかの商品に入ることもあります。 */
	$prod = array();
	$addp = function ( $pid, $id ) use ( &$prod ) {
		$pid = (int) $pid; $id = (int) $id;
		if ( $pid > 0 && $id > 0 ) $prod[ $pid ][ $id ] = 1;
	};

	/* ---- ⓪-2 カタログの画像は「商品」の中の「カタログ」へ ---- */
	$catalog = ymkrf_media_catalog_ids();
	foreach ( $catalog as $cid ) $put( $cid, 'product' );

	/* ---- ① 商品の写真の欄 ----
	   商品は、写真をメディアからえらぶ作りです。
	   「どの記事といっしょに上げたか」では商品に結びつかないので、
	   入力欄の中身を見にいきます。 */
	if ( function_exists( 'ymkrf_product_repeaters' ) ) {

		$reps = array();
		foreach ( ymkrf_product_repeaters() as $key => $def ) {
			$cols = array();
			foreach ( (array) $def['cols'] as $col => $c ) {
				if ( isset( $c[1] ) && ( $c[1] === 'image' || $c[1] === 'imagelist' ) ) $cols[] = $col;
			}
			if ( $cols ) $reps[ $key ] = $cols;
		}

		if ( $reps ) {
			$in   = "'" . implode( "','", array_map( 'esc_sql', array_keys( $reps ) ) ) . "'";
			$rows = $wpdb->get_results(
				"SELECT pm.post_id AS pid, pm.meta_key AS mk, pm.meta_value AS mv
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
						if ( ! isset( $row[ $col ] ) ) continue;
						/* 写真1枚の欄も、何枚も入る欄（おすすめポイント）も拾います（2026/10/23） */
						$pics = array();
						ymkrf_media_pick_ids( $row[ $col ], $pics );
						foreach ( $pics as $pic ) { $put( $pic, 'product' ); $addp( $r->pid, $pic ); }
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
		"SELECT pm.post_id AS pid, pm.meta_value AS att, p.post_type AS pt
		   FROM {$wpdb->postmeta} pm
		   JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		  WHERE pm.meta_key = '_thumbnail_id'"
	);
	foreach ( (array) $rows as $r ) {
		$put( $r->att, ymkrf_media_key_of_type( $r->pt ) );
		if ( $r->pt === 'ymkrf_product' ) $addp( $r->pid, $r->att );
	}

	/* ---- ④ 写真を上げたときの記事 ---- */
	$rows = $wpdb->get_results(
		"SELECT att.ID AS id, att.post_parent AS par, COALESCE( par.post_type, '' ) AS pt,
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
		if ( $r->pt === 'ymkrf_product' ) $addp( $r->par, $id );
		$key = isset( $use[ $id ] ) ? $use[ $id ] : ymkrf_media_key_of_type( $r->pt );
		/* ---- ⑤ それでも分からないときは、ファイル名で見当をつけます ---- */
		/* ---- ⑤ ファイル名で見当をつけます ---- */
		if ( $key === '' && ymkrf_media_is_product_file( $r->f, $r->ttl ) ) $key = 'product';
		if ( $key === '' ) $key = ymkrf_media_key_of_file( $r->ttl . ' ' . $r->f );
		if ( $key === '' ) $key = 'none';
		$out['ids'][ $key ][] = $id;
		$out['count'][ $key ]++;
	}

	/* 中のフォルダ（親の枚数には足しません。親にも入っています） */
	$live = array();
	foreach ( (array) $rows as $r ) $live[ (int) $r->id ] = 1;
	$out['total'] = count( $live );

	/* ---- 施工事例で使っている写真は、ほかのフォルダに入っていても、ぜんぶ「施工事例」にも入れます ----
	   （2026/10/23 ユーザー「施工事例で使用している写真はすべてここに入れて」）
	   ビフォー・途中・アフターの欄、アイキャッチ、施工事例から上げた写真、本文に貼った写真（wp-image-番号） */
	$wset = array();
	foreach ( (array) $wpdb->get_results(
		"SELECT pm.meta_value AS mv FROM {$wpdb->postmeta} pm
		   JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		  WHERE p.post_type = 'ymkrf_works'
		    AND pm.meta_key IN ( '_ymkrf_before_img', '_ymkrf_before_imgs', '_ymkrf_after_imgs', '_ymkrf_during_imgs', '_thumbnail_id' )" ) as $r ) {
		$ids = array();
		ymkrf_media_pick_ids( maybe_unserialize( $r->mv ), $ids );
		foreach ( $ids as $i ) $wset[ $i ] = 1;
	}
	foreach ( (array) $wpdb->get_col(
		"SELECT a.ID FROM {$wpdb->posts} a JOIN {$wpdb->posts} p ON p.ID = a.post_parent
		  WHERE a.post_type = 'attachment' AND p.post_type = 'ymkrf_works'" ) as $i ) $wset[ (int) $i ] = 1;
	foreach ( (array) $wpdb->get_col(
		"SELECT post_content FROM {$wpdb->posts}
		  WHERE post_type = 'ymkrf_works' AND post_content LIKE '%wp-image-%'" ) as $c ) {
		if ( preg_match_all( '/wp-image-(\d+)/', (string) $c, $m ) ) foreach ( $m[1] as $i ) $wset[ (int) $i ] = 1;
	}
	$have = array_flip( $out['ids']['works'] );
	$out['works_also'] = 0;
	foreach ( array_keys( $wset ) as $i ) {
		if ( isset( $manual[ $i ] ) && $manual[ $i ] !== 'works' ) { unset( $wset[ $i ] ); continue; }   /* 手でほかへ引っ越したもの */
		if ( isset( $live[ $i ] ) && ! isset( $have[ $i ] ) ) { $out['ids']['works'][] = $i; $out['works_also']++; }
	}
	$out['count']['works'] = count( $out['ids']['works'] );
	/* 施工事例で使っているなら「どこにも使っていない」からは外します */
	$out['ids']['none']    = array_values( array_filter( $out['ids']['none'], function ( $i ) use ( $wset ) { return ! isset( $wset[ $i ] ); } ) );
	$out['count']['none']  = count( $out['ids']['none'] );
	$out['sub'] = array( 'catalog' => array_values( array_filter( $catalog, function ( $i ) use ( $live ) { return isset( $live[ $i ] ); } ) ) );
	$out['subcount'] = array( 'catalog' => count( $out['sub']['catalog'] ) );

	/* 手で保存先を決めた写真は、その商品だけに入れます（ほかの商品・商品以外にしたものは、商品のフォルダから外します） */
	foreach ( $manual as $att => $mu ) {
		if ( $mu === 'product' && ! isset( $mprod[ $att ] ) ) continue;   /* 「商品（どの商品か決めない）」は自動のまま */
		foreach ( $prod as $pid => $set ) unset( $prod[ $pid ][ $att ] );
		if ( isset( $mprod[ $att ] ) ) $prod[ $mprod[ $att ] ][ $att ] = 1;
	}

	/* 商品ごと（カタログの画像は、カタログのフォルダだけに入れます） */
	$catf = array_flip( $catalog );
	$out['prod'] = array(); $inprod = array();
	foreach ( $prod as $pid => $set ) {
		$ids = array();
		foreach ( array_keys( $set ) as $i ) {
			if ( isset( $live[ $i ] ) && ! isset( $catf[ $i ] ) ) { $ids[] = $i; $inprod[ $i ] = 1; }
		}
		if ( $ids ) $out['prod'][ $pid ] = $ids;
	}
	/* 商品のフォルダにあるのに、どの商品にも入っていない写真（古い写真・下書きの写真など） */
	$out['prodrest'] = array_values( array_filter( $out['ids']['product'], function ( $i ) use ( $inprod, $catf ) {
		return ! isset( $inprod[ $i ] ) && ! isset( $catf[ $i ] );
	} ) );

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
	if ( isset( $_GET['in'] ) && $_GET['in'] === 'product' ) { ymkrf_media_product_folders( $map ); return; }
	$count = $map['count'];
	$all   = isset( $map['total'] ) ? (int) $map['total'] : array_sum( $count );

	$subc = isset( $map['subcount'] ) ? $map['subcount'] : array();
	$card = function ( $name, $key, $n, $note = '' ) use ( $subc ) {
		$url = $key
			? admin_url( 'upload.php?ymkrf_use=' . $key . '&mode=list' )
			: admin_url( 'upload.php?mode=list' );
		/* 商品は、商品名ごとのフォルダの画面へ */
		if ( $key === 'product' ) $url = admin_url( 'upload.php?page=ymkrf-media-folders&in=product' );
		/* 中のフォルダ（例：商品 の中の カタログ） */
		$subs = array();
		foreach ( ymkrf_media_subkinds() as $sk => $sd ) {
			if ( $key !== '' && $sd['parent'] === $key ) $subs[ $sk ] = $sd;
		}
		?>
		<div class="ymkrf-mf__wrap">
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
		<?php foreach ( $subs as $sk => $sd ) :
		  $sn = isset( $subc[ $sk ] ) ? (int) $subc[ $sk ] : 0; ?>
		  <a class="ymkrf-mf__sub" href="<?php echo esc_url( admin_url( 'upload.php?ymkrf_use=' . $sk . '&mode=list' ) ); ?>"
		     title="<?php echo esc_attr( $sd['note'] ); ?>">
		    <span aria-hidden="true">📁</span><?php echo esc_html( $sd['name'] ); ?>
		    <span class="ymkrf-mf__subn"><?php echo $sn ? esc_html( number_format_i18n( $sn ) ) . '枚' : '写真なし'; ?></span>
		  </a>
		<?php endforeach; ?>
		</div>
		<?php
	};

	$notes = array(
		'product' => '商品のページに出している写真です。',
		'works'   => '施工事例で使っている写真ぜんぶです（ほかのフォルダと重なるものも入ります）。',
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
	  .ymkrf-mf__wrap{display:flex;flex-direction:column;gap:6px}
	  .ymkrf-mf__sub{display:flex;align-items:center;gap:6px;margin-left:18px;padding:7px 12px;background:#fff;
	    border:1px solid #00782a;border-radius:6px;text-decoration:none;color:#1d2327;font-size:13.5px;font-weight:700;position:relative}
	  .ymkrf-mf__sub::before{content:"";position:absolute;left:-13px;top:-8px;width:10px;height:24px;
	    border-left:2px solid #b5d9c1;border-bottom:2px solid #b5d9c1;border-bottom-left-radius:4px}
	  .ymkrf-mf__sub:hover{background:#e4f3ea}
	  .ymkrf-mf__subn{margin-left:auto;color:#00782a;font-size:12.5px}
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

/* ------------------------------------------------------------
   商品 ＞ 商品名 のフォルダ（2026/10/23 ユーザー「商品＞商品名　とフォルダ分けしてほしい」）
   分類（キッチン・お風呂…）ごとに見出しを立てて、商品のカードをならべます。
   写真は動かしません。どの商品の欄で使っているかで分けて見せるだけです（写真のURLは変わりません）。
   ------------------------------------------------------------ */
/** アイテム（商品の分類）の写真ID（その分類の商品の写真をあわせたもの） */
if ( ! function_exists( 'ymkrf_media_pcat_ids' ) ) :
function ymkrf_media_pcat_ids( $slug ) {
	$map  = ymkrf_media_map();
	$pids = get_posts( array( 'post_type' => 'ymkrf_product', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids',
	                          'tax_query' => array( array( 'taxonomy' => 'ymkrf_product_cat', 'field' => 'slug', 'terms' => $slug ) ) ) );
	$u = array();
	foreach ( $pids as $pid ) if ( isset( $map['prod'][ $pid ] ) ) foreach ( $map['prod'][ $pid ] as $i ) $u[ (int) $i ] = 1;
	return array_keys( $u );
}
endif;

if ( ! function_exists( 'ymkrf_media_product_folders' ) ) :
function ymkrf_media_product_folders( $map ) {
	$prod  = isset( $map['prod'] ) ? $map['prod'] : array();
	$posts = get_posts( array( 'post_type' => 'ymkrf_product', 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ),
	                           'posts_per_page' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) );
	$groups = array();   /* 分類のslug => 名前・並び・商品 */
	$cats   = get_terms( array( 'taxonomy' => 'ymkrf_product_cat', 'hide_empty' => false ) );
	$order  = array();
	/* 並びはサイトの商品ページと同じ（キッチン → お風呂 → トイレ → 洗面 → 4点セット → 給湯器 → エコキュート → … → 内装） */
	$pref = array( 'kitchen', 'bathroom', 'toilet', 'lavatory', 'pack4', 'boiler', 'ecocute' );
	if ( ! is_wp_error( $cats ) ) foreach ( array_values( $cats ) as $i => $t ) {
		$at = array_search( $t->slug, $pref, true );
		$order[ $t->slug ] = ( $at !== false ) ? $at : ( in_array( $t->slug, array( 'interior', 'outer-wall' ), true ) ? 90 + $i : 50 + $i );
	}
	foreach ( $posts as $p ) {
		$ts = get_the_terms( $p->ID, 'ymkrf_product_cat' );
		$t  = ( $ts && ! is_wp_error( $ts ) ) ? $ts[0] : null;
		$k  = $t ? $t->slug : 'zz-none';
		if ( ! isset( $groups[ $k ] ) ) {
			$groups[ $k ] = array( 'name' => $t ? $t->name : '分類なし',
			                       'ord'  => ( $t && isset( $order[ $t->slug ] ) ) ? $order[ $t->slug ] : 9999,
			                       'items' => array() );
		}
		$mk = get_the_terms( $p->ID, 'ymkrf_maker' );
		$groups[ $k ]['items'][] = array(
			'id'    => $p->ID,
			'title' => get_the_title( $p ),
			'maker' => ( $mk && ! is_wp_error( $mk ) ) ? $mk[0]->name : '',
			'draft' => $p->post_status !== 'publish',
			'n'     => isset( $prod[ $p->ID ] ) ? count( $prod[ $p->ID ] ) : 0,
			'thumb' => get_the_post_thumbnail_url( $p->ID, 'thumbnail' ),
		);
	}
	uasort( $groups, function ( $a, $b ) { return $a['ord'] - $b['ord']; } );
	$back = admin_url( 'upload.php?page=ymkrf-media-folders' );
	$pfu  = admin_url( 'upload.php?page=ymkrf-media-folders&in=product' );
	$all  = isset( $map['count']['product'] ) ? (int) $map['count']['product'] : 0;
	$catn = isset( $map['subcount']['catalog'] ) ? (int) $map['subcount']['catalog'] : 0;
	$rest = isset( $map['prodrest'] ) ? count( $map['prodrest'] ) : 0;
	/* アイテム（分類）ごとの写真の枚数（重なりは1枚） */
	foreach ( $groups as $k => $g ) {
		$u = array(); $th = '';
		foreach ( $g['items'] as $it ) {
			if ( isset( $prod[ $it['id'] ] ) ) foreach ( $prod[ $it['id'] ] as $i ) $u[ $i ] = 1;
			if ( $th === '' && $it['thumb'] && ! $it['draft'] ) $th = $it['thumb'];
		}
		$groups[ $k ]['n'] = count( $u ); $groups[ $k ]['thumb'] = $th;
	}
	/* （2026/10/23 ユーザー「商品はまずアイテムごと（トイレやキッチン）に分けてその中でも商品ごとにしてほしい」）
	   1段目＝アイテム（キッチン・トイレ…）のカード、2段目＝そのアイテムの商品のカード */
	$pcat = isset( $_GET['pcat'] ) ? sanitize_title( wp_unslash( $_GET['pcat'] ) ) : '';
	if ( $pcat !== '' && ! isset( $groups[ $pcat ] ) ) $pcat = '';
	$card = function ( $href, $name, $meta, $n, $thumb, $empty_word = '写真なし', $draft = false ) {
		?>
		<a class="ymkrf-pf__card<?php echo $n ? '' : ' is-empty'; ?>" href="<?php echo esc_url( $href ); ?>">
		  <span class="ymkrf-pf__thumb"><?php if ( $thumb ) : ?><img src="<?php echo esc_url( $thumb ); ?>" alt="" loading="lazy"><?php else : ?>📁<?php endif; ?></span>
		  <span class="ymkrf-pf__body">
		    <span class="ymkrf-pf__name"><?php echo esc_html( $name ); ?><?php if ( $draft ) echo '<span class="ymkrf-pf__draft">下書き</span>'; ?></span>
		    <span class="ymkrf-pf__meta"><?php echo esc_html( $meta ); ?></span>
		  </span>
		  <span class="ymkrf-pf__n"><?php echo $n ? esc_html( number_format_i18n( $n ) ) . '枚' : esc_html( $empty_word ); ?></span>
		</a>
		<?php
	};
	?>
	<div class="wrap ymkrf-mf ymkrf-pf">
	<?php if ( $pcat === '' ) : ?>
	  <p class="ymkrf-pf__crumb"><a href="<?php echo esc_url( $back ); ?>">メディア</a> ＞ <b>商品</b></p>
	  <h1>商品の写真
	    <a class="page-title-action" href="<?php echo esc_url( admin_url( 'media-new.php' ) ); ?>">新規追加</a>
	  </h1>
	  <h2 class="ymkrf-mf__h2">アイテムからえらぶ</h2>
	  <div class="ymkrf-pf__grid ymkrf-pf__grid--item">
	    <?php foreach ( $groups as $k => $g ) {
	      $card( add_query_arg( 'pcat', $k, $pfu ), $g['name'], count( $g['items'] ) . '商品', $g['n'], $g['thumb'] );
	    } ?>
	  </div>
	  <h2 class="ymkrf-mf__h2">そのほか</h2>
	  <div class="ymkrf-mf__grid ymkrf-pf__top">
	    <a class="ymkrf-mf__card ymkrf-mf__card--all" href="<?php echo esc_url( admin_url( 'upload.php?ymkrf_use=product&mode=list' ) ); ?>">
	      <span class="ymkrf-mf__name">商品の写真ぜんぶ</span>
	      <span class="ymkrf-mf__cnt"><span class="ymkrf-mf__n" style="color:#fe3301"><?php echo esc_html( number_format_i18n( $all ) ); ?>枚</span></span></a>
	    <a class="ymkrf-mf__card ymkrf-mf__card--cat" href="<?php echo esc_url( admin_url( 'upload.php?ymkrf_use=catalog&mode=list' ) ); ?>">
	      <span class="ymkrf-mf__name">📁 カタログ</span>
	      <span class="ymkrf-mf__note">「メーカーのカタログ」に使う画像です。</span>
	      <span class="ymkrf-mf__cnt"><span class="ymkrf-mf__n"><?php echo esc_html( number_format_i18n( $catn ) ); ?>枚</span></span></a>
	    <?php if ( $rest ) : ?>
	    <a class="ymkrf-mf__card ymkrf-mf__card--cat" style="border-color:#c3c4c7;background:#f6f7f7" href="<?php echo esc_url( admin_url( 'upload.php?ymkrf_prod=rest&mode=list' ) ); ?>">
	      <span class="ymkrf-mf__name">どの商品にも入っていない</span>
	      <span class="ymkrf-mf__note">名前から商品の写真と分かるけれど、いまはどの商品の欄にも入っていない写真です。</span>
	      <span class="ymkrf-mf__cnt"><span class="ymkrf-mf__n" style="color:#50575e"><?php echo esc_html( number_format_i18n( $rest ) ); ?>枚</span></span></a>
	    <?php endif; ?>
	  </div>
	<?php else : $g = $groups[ $pcat ]; ?>
	  <p class="ymkrf-pf__crumb"><a href="<?php echo esc_url( $back ); ?>">メディア</a> ＞ <a href="<?php echo esc_url( $pfu ); ?>">商品</a> ＞ <b><?php echo esc_html( $g['name'] ); ?></b></p>
	  <h1><?php echo esc_html( $g['name'] ); ?>の写真
	    <a class="page-title-action" href="<?php echo esc_url( $pfu ); ?>">← アイテムの一覧にもどる</a>
	  </h1>
	  <div class="ymkrf-mf__grid ymkrf-pf__top">
	    <a class="ymkrf-mf__card ymkrf-mf__card--all" href="<?php echo esc_url( admin_url( 'upload.php?ymkrf_pcat=' . $pcat . '&mode=list' ) ); ?>">
	      <span class="ymkrf-mf__name"><?php echo esc_html( $g['name'] ); ?>の写真ぜんぶ</span>
	      <span class="ymkrf-mf__cnt"><span class="ymkrf-mf__n" style="color:#fe3301"><?php echo esc_html( number_format_i18n( $g['n'] ) ); ?>枚</span></span></a>
	  </div>
	  <h2 class="ymkrf-mf__h2">商品からえらぶ <span style="font-weight:400;color:#787878;font-size:12.5px"><?php echo (int) count( $g['items'] ); ?>商品</span></h2>
	  <div class="ymkrf-pf__grid">
	    <?php foreach ( $g['items'] as $it ) {
	      $card( admin_url( 'upload.php?ymkrf_prod=' . $it['id'] . '&mode=list' ), $it['title'], $it['maker'], $it['n'], $it['thumb'], '写真なし', $it['draft'] );
	    } ?>
	  </div>
	<?php endif; ?>
	  <p class="ymkrf-mf__foot">写真が<b>どの商品の欄で使われているか</b>で分けています。1枚の写真を2つの商品で使っていれば、両方に出ます。<br>
	    写真そのものは動かしていないので、<b>写真のURLは変わりません</b>。</p>
	</div>
	<style>
	  .ymkrf-mf__h2{margin:26px 0 10px;padding-left:9px;font-size:15px;border-left:4px solid #fe3301;line-height:1.5}
	  .ymkrf-mf__grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(268px,1fr));gap:12px}
	  .ymkrf-mf__card{display:block;padding:13px 16px;background:#fff;border:1px solid #dcdcde;border-radius:6px;text-decoration:none;color:inherit}
	  .ymkrf-mf__card--all{border-color:#fe3301;background:#fff8f5}
	  .ymkrf-mf__card--cat{border-color:#00782a;background:#eff8f2}
	  .ymkrf-mf__card--cat:hover{background:#e4f3ea}
	  .ymkrf-mf__name{display:block;font-size:15px;font-weight:700;line-height:1.4}
	  .ymkrf-mf__note{display:block;margin-top:3px;font-size:11.5px;color:#787878;line-height:1.5}
	  .ymkrf-mf__cnt{display:block;margin-top:7px;font-size:12.5px}
	  .ymkrf-mf__n{color:#00782a;font-weight:700}
	  .ymkrf-mf__foot{margin-top:22px;font-size:13px;color:#50575e;line-height:1.9}
	  .ymkrf-pf__crumb{margin:14px 0 0;font-size:13px}
	  .ymkrf-pf__top{margin-top:8px}
	  .ymkrf-pf__grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:8px}
	  .ymkrf-pf__card{display:flex;align-items:center;gap:10px;padding:8px 12px 8px 8px;background:#fff;border:1px solid #00782a;border-radius:6px;text-decoration:none;color:#1d2327}
	  .ymkrf-pf__card:hover{background:#e4f3ea}
	  .ymkrf-pf__card.is-empty{border-color:#dcdcde;color:#787878}
	  .ymkrf-pf__thumb{flex:0 0 44px;width:44px;height:44px;border-radius:4px;background:#f0f0f1;display:grid;place-items:center;overflow:hidden;font-size:20px}
	  .ymkrf-pf__thumb img{width:100%;height:100%;object-fit:cover}
	  .ymkrf-pf__body{flex:1;min-width:0}
	  .ymkrf-pf__name{display:block;font-weight:700;font-size:13.5px;line-height:1.4}
	  .ymkrf-pf__meta{display:block;font-size:11.5px;color:#787878}
	  .ymkrf-pf__draft{margin-left:6px;padding:0 6px;border-radius:3px;background:#f0f0f1;color:#787878;font-size:11px;font-weight:400}
	  .ymkrf-pf__n{font-size:12.5px;font-weight:700;color:#00782a;white-space:nowrap}
	  .ymkrf-pf__card.is-empty .ymkrf-pf__n{color:#a7aaad;font-weight:400}
	  .ymkrf-pf__grid--item{grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:12px}
	  .ymkrf-pf__grid--item .ymkrf-pf__card{padding:12px 16px 12px 12px}
	  .ymkrf-pf__grid--item .ymkrf-pf__thumb{flex-basis:60px;width:60px;height:60px}
	  .ymkrf-pf__grid--item .ymkrf-pf__name{font-size:16px}
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
	/* 商品 ＞ アイテム（キッチン・トイレ…）の写真ぜんぶ */
	if ( ! empty( $_GET['ymkrf_pcat'] ) && isset( $GLOBALS['pagenow'] ) && $GLOBALS['pagenow'] === 'upload.php' ) {
		global $wpdb;
		$ids = ymkrf_media_pcat_ids( sanitize_title( wp_unslash( $_GET['ymkrf_pcat'] ) ) );
		$clauses['where'] .= $ids ? " AND {$wpdb->posts}.ID IN ( " . implode( ',', $ids ) . ' ) ' : ' AND 1=0 ';
		return $clauses;
	}
	/* 商品 ＞ 商品名 */
	if ( ! empty( $_GET['ymkrf_prod'] ) && isset( $GLOBALS['pagenow'] ) && $GLOBALS['pagenow'] === 'upload.php' ) {
		global $wpdb;
		$map = ymkrf_media_map();
		$pv  = sanitize_key( wp_unslash( $_GET['ymkrf_prod'] ) );
		$ids = ( $pv === 'rest' )
			? ( isset( $map['prodrest'] ) ? $map['prodrest'] : array() )
			: ( isset( $map['prod'][ (int) $pv ] ) ? $map['prod'][ (int) $pv ] : array() );
		$clauses['where'] .= $ids ? " AND {$wpdb->posts}.ID IN ( " . implode( ',', array_map( 'intval', $ids ) ) . ' ) ' : ' AND 1=0 ';
		return $clauses;
	}
	if ( empty( $_GET['ymkrf_use'] ) ) return $clauses;
	if ( ! isset( $GLOBALS['pagenow'] ) || $GLOBALS['pagenow'] !== 'upload.php' ) return $clauses;

	$use   = sanitize_key( wp_unslash( $_GET['ymkrf_use'] ) );
	$kinds = ymkrf_media_kinds();
	$subk  = ymkrf_media_subkinds();
	if ( ! isset( $kinds[ $use ] ) && ! isset( $subk[ $use ] ) ) return $clauses;

	global $wpdb;

	$map = ymkrf_media_map();
	$ids = isset( $subk[ $use ] )
		? ( isset( $map['sub'][ $use ] ) ? $map['sub'][ $use ] : array() )
		: ( isset( $map['ids'][ $use ] ) ? $map['ids'][ $use ] : array() );

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
	$pfback = admin_url( 'upload.php?page=ymkrf-media-folders&in=product' );
	if ( ! empty( $_GET['ymkrf_pcat'] ) ) {
		$t = get_term_by( 'slug', sanitize_title( wp_unslash( $_GET['ymkrf_pcat'] ) ), 'ymkrf_product_cat' );
		$cu = $t ? add_query_arg( 'pcat', $t->slug, $pfback ) : $pfback;
		?>
		<div class="notice notice-info" style="margin-top:14px"><p style="font-size:13.5px">
			<a href="<?php echo esc_url( admin_url( 'upload.php?page=ymkrf-media-folders' ) ); ?>">メディア</a> ＞
			<a href="<?php echo esc_url( $pfback ); ?>">商品</a> ＞ <b><?php echo esc_html( $t ? $t->name : '' ); ?></b>　このアイテムの商品で使っている写真ぜんぶです。
			　<a href="<?php echo esc_url( $cu ); ?>">← 商品ごとのフォルダにもどる</a></p></div>
		<?php
		return;
	}
	if ( ! empty( $_GET['ymkrf_prod'] ) ) {
		$pv = sanitize_key( wp_unslash( $_GET['ymkrf_prod'] ) );
		$pp = ( $pv === 'rest' ) ? null : get_post( (int) $pv );
		$nm = $pp ? get_the_title( $pp ) : 'どの商品にも入っていない';
		$pt = $pp ? get_the_terms( $pp->ID, 'ymkrf_product_cat' ) : null;
		$pt = ( $pt && ! is_wp_error( $pt ) ) ? $pt[0] : null;
		if ( $pt ) $pfback = add_query_arg( 'pcat', $pt->slug, $pfback );
		?>
		<div class="notice notice-info" style="margin-top:14px"><p style="font-size:13.5px">
			<a href="<?php echo esc_url( admin_url( 'upload.php?page=ymkrf-media-folders' ) ); ?>">メディア</a> ＞
			<a href="<?php echo esc_url( admin_url( 'upload.php?page=ymkrf-media-folders&in=product' ) ); ?>">商品</a> ＞
			<?php if ( $pt ) : ?><a href="<?php echo esc_url( $pfback ); ?>"><?php echo esc_html( $pt->name ); ?></a> ＞ <?php endif; ?><b><?php echo esc_html( $nm ); ?></b>
			<?php if ( $pp ) : ?>　この商品の欄で使っている写真です。
				<a href="<?php echo esc_url( get_edit_post_link( $pp->ID ) ); ?>">この商品の登録画面をひらく</a><?php endif; ?>
			　<a href="<?php echo esc_url( $pfback ); ?>">← 商品のフォルダにもどる</a></p></div>
		<?php
		return;
	}
	if ( empty( $_GET['ymkrf_use'] ) ) return;

	$use   = sanitize_key( wp_unslash( $_GET['ymkrf_use'] ) );
	$kinds = ymkrf_media_kinds();
	$subk  = ymkrf_media_subkinds();
	if ( ! isset( $kinds[ $use ] ) && ! isset( $subk[ $use ] ) ) return;

	if ( isset( $subk[ $use ] ) ) {
		$parent = $subk[ $use ]['parent'];
		$name   = $kinds[ $parent ]['name'] . ' ＞ ' . $subk[ $use ]['name'];
		$msg    = $subk[ $use ]['note'];
	} else {
		$parent = $use;
		$name   = $kinds[ $use ]['name'];
		$msg    = ( $use === 'none' )
			? 'どのページでも使われていない写真です。'
			: $name . 'で使っている写真だけを出しています。';
	}
	/* 中のフォルダへのボタン（商品 → カタログ） */
	$chips = '';
	foreach ( $subk as $sk => $sd ) {
		if ( $sd['parent'] !== $parent ) continue;
		$on = ( $sk === $use );
		$chips .= ' <a href="' . esc_url( admin_url( 'upload.php?ymkrf_use=' . ( $on ? $parent : $sk ) . '&mode=list' ) ) . '"'
		        . ' style="display:inline-block;margin:0 6px;padding:2px 10px;border:1px solid #00782a;border-radius:999px;text-decoration:none;'
		        . ( $on ? 'background:#fff;color:#00782a' : 'background:#00782a;color:#fff' ) . ';font-weight:700">'
		        . ( $on ? '← ' . esc_html( $kinds[ $parent ]['name'] ) . 'のぜんぶにもどる' : '📁 ' . esc_html( $sd['name'] ) . ' を開く' ) . '</a>';
	}
	?>
	<div class="notice notice-info" style="margin-top:14px">
		<p style="font-size:13.5px"><b><?php echo esc_html( $name ); ?></b>　<?php echo esc_html( $msg ); ?><?php echo $chips; /* phpcs:ignore */ ?>
			<?php if ( $parent === 'product' ) : ?><a href="<?php echo esc_url( $pfback ); ?>">商品のフォルダにもどる</a>　<?php endif; ?>
			<a href="<?php echo esc_url( admin_url( 'upload.php?page=ymkrf-media-folders' ) ); ?>">フォルダ一覧にもどる</a>

			<a href="<?php echo esc_url( admin_url( 'upload.php?mode=list' ) ); ?>">すべての写真を見る</a></p>
	</div>
	<?php
} );


/* 写真の編集画面の「保存先（フォルダ）」の欄は functions-media-dest.php に移しました（2026/10/23） */


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
function ymkrf_media_product_filebase( $pid, $legacy = false ) {

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

	/* 商品のURL（v-style など）がその英語をふくんでいれば、URLのほうを使います。
	   「V-style」から「style」だけを拾ってしまうのを防ぎ、ページのURLと写真の名前をそろえます
	   （2026/10/23。$legacy＝前の決まりのまま＝いまある写真を見つけるときに使います） */
	if ( ! $legacy && $word !== '' ) {
		$slug = rawurldecode( (string) get_post_field( 'post_name', $pid ) );
		$pcat = function_exists( 'ymkrf_product_current_cat' ) ? ymkrf_product_current_cat( $pid ) : '';
		if ( $pcat !== '' && strpos( $slug, $pcat . '-' ) === 0 ) $slug = substr( $slug, strlen( $pcat ) + 1 );
		if ( $slug !== '' && strlen( $slug ) <= 30 && stripos( $slug, $word ) !== false
		  && preg_match( '/^[a-z0-9-]+$/', $slug ) ) $word = $slug;
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

	/* 商品名のうしろに、もとのファイル名（英字に直したもの）を付けます。
	   （2026/10/23 ユーザー指示「ファイル名そもそもついていたのになぜか変えたの？」
	     「その商品にちなんだSEOに強いファイル名にして」「今後もそうして」）
	   例：ピュアレストQR の登録画面で TCF4714AK.jpg → toto-purerest-qr-tcf4714ak.jpg
	   以前は商品名だけ（toto-purerest-qr.jpg → 2枚目から -2, -3…）にしていて、
	   もとの名前が消え、何の写真か分からなくなっていました。 */
	$orig = function_exists( 'ymkrf_seo_slugify' )
		? ymkrf_seo_slugify( pathinfo( (string) $file['name'], PATHINFO_FILENAME ), 50 )
		: '';
	/* もう商品名で始まっている名前は、二重にしません */
	if ( $orig !== '' && strpos( $orig, $base ) === 0 ) $orig = trim( substr( $orig, strlen( $base ) ), '-' );
	$file['name'] = $base . ( $orig !== '' ? '-' . $orig : '' ) . $ext;
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
