<?php
/**
 * functions-works.php ─ 施工事例
 * 置き場所： wp-content/themes/ymkrf/inc/functions-works.php
 *
 *  1.   入力欄（施工データ）
 *  2.   URL（/works/kitchen/2604-0180/）
 *  3.   表示に使う関数
 *  4.   管理画面の一覧
 *
 * ── 名前について ──────────────────────────────
 * 投稿タイプ … ymkrf_works
 * 分類 ……… ymkrf_works_cat（部位）／ymkrf_works_area（エリア）
 * 入力欄 …… _ymkrf_price 工事費／_ymkrf_period 工期／_ymkrf_done 完工時期
 *             _ymkrf_shop 担当店舗／_ymkrf_products 使った商品
 *             _ymkrf_before_img Before写真／_ymkrf_case_no 案件番号
 * ─────────────────────────────────────────
 */
if ( ! defined( 'ABSPATH' ) ) exit;


/* ============================================================
   1. 入力欄（施工データ）
   ============================================================ */

/** 部位（ymkrf_works_cat）→ URLに使う英字 */
function ymkrf_works_cat_slug( $post_id ) {
	$ts = get_the_terms( $post_id, 'ymkrf_works_cat' );
	if ( ! $ts || is_wp_error( $ts ) ) return 'other';

	/* いくつも選べるので、決めた順（キッチン → お風呂 → …）でいちばん上のものを使います */
	$order = array_keys( ymkrf_works_parts_master() );
	$best  = null;
	$rank  = 9999;
	foreach ( $ts as $t ) {
		$i = array_search( $t->slug, $order, true );
		if ( $i === false ) $i = 900;
		if ( $i < $rank ) { $rank = $i; $best = $t; }
	}
	return ( $best && $best->slug ) ? $best->slug : 'other';
}

/** この施工事例で使った商品（投稿ID の配列） */
function ymkrf_works_products( $post_id ) {
	$v = (string) get_post_meta( $post_id, '_ymkrf_products', true );
	$ids = array_filter( array_map( 'intval', explode( ',', $v ) ) );
	$out = array();
	foreach ( $ids as $id ) {
		$p = get_post( $id );
		if ( $p && $p->post_type === 'ymkrf_product' && $p->post_status === 'publish' ) $out[] = $p;
	}
	return $out;
}

/** この工事の営業担当（スタッフの投稿ID）。いなければ0 */
function ymkrf_works_staff_id( $post_id ) {
	$id = (int) get_post_meta( $post_id, '_ymkrf_staff', true );
	if ( ! $id ) return 0;
	$p = get_post( $id );
	return ( $p && $p->post_type === 'ymkrf_staff' && $p->post_status === 'publish' ) ? $id : 0;
}

/** 「金沢野々市店　山岸 太郎」のように、店舗と名前をつなげたもの */
function ymkrf_works_staff_label( $post_id, $with_shop = true ) {
	$sid = ymkrf_works_staff_id( $post_id );
	if ( ! $sid ) return '';

	$name = trim( (string) get_the_title( $sid ) );
	if ( $name === '' ) return '';

	if ( ! $with_shop ) return $name;

	$shop = function_exists( 'ymkrf_staff_shop_name' ) ? ymkrf_staff_shop_name( $sid ) : '';
	if ( $shop === '' ) $shop = ymkrf_works_shop_name( $post_id );

	return trim( $shop . ( $shop !== '' ? '　' : '' ) . $name );
}

/** 担当店舗の名前 */
function ymkrf_works_shop_name( $post_id ) {
	$slug = trim( (string) get_post_meta( $post_id, '_ymkrf_shop', true ) );
	if ( $slug === '' ) return '';
	$t = get_term_by( 'slug', $slug, 'ymkrf_shop' );
	return ( $t && ! is_wp_error( $t ) ) ? $t->name : '';
}

/* 施工事例は、営業の方が写真と金額を入れるだけの画面です。
   ブロックエディターだと「施工データ」が画面下の引き出しに入ってしまい
   気づきにくいので、昔ながらの編集画面にします。 */
add_filter( 'use_block_editor_for_post_type', function ( $use, $type ) {
	return ( $type === 'ymkrf_works' ) ? false : $use;
}, 10, 2 );

/* ------------------------------------------------------------
   1-a. リフォームした箇所（ymkrf_works_cat）の項目
        商品カテゴリと同じ英字にそろえてあります。
        ページがまだ無いものも、さきに用意しておきます。
   ------------------------------------------------------------ */
function ymkrf_works_parts_master() {
	/* 英字 => array( 画面に出す名前, 題名に使う言い方 )
	   トップページの「リフォームメニュー」と、名前も順番もそろえてあります。 */
	return array(
		'kitchen'    => array( 'キッチン',            'キッチンリフォーム事例' ),
		'bathroom'   => array( 'お風呂',              'お風呂リフォーム事例' ),
		'toilet'     => array( 'トイレ',              'トイレリフォーム事例' ),
		'lavatory'   => array( '洗面化粧台',          '洗面化粧台リフォーム事例' ),
		'boiler'     => array( '給湯器',              '給湯器交換の事例' ),
		'oiltank'    => array( 'オイルタンク',        'オイルタンク工事の事例' ),
		'ecocute'    => array( 'エコキュート',        'エコキュート交換の事例' ),
		'ih'         => array( 'IH・ガスコンロ',       'IH・ガスコンロ交換の事例' ),
		'interior'   => array( '内装・クロス・床',    '内装リフォーム事例' ),
		'renovation' => array( '内装・改装',          '内装・改装の事例' ),
		'window'     => array( '窓・断熱',            '窓・断熱リフォーム事例' ),
		'door'       => array( '玄関ドア',            '玄関ドア交換の事例' ),
		'veranda'    => array( 'ベランダ・サンルーム','ベランダ・サンルームの事例' ),
		'carport'    => array( 'カーポート',          'カーポート設置の事例' ),
		'storage'    => array( '物置',                '物置設置の事例' ),
		'outer-wall' => array( '外壁・屋根',          '外壁・屋根リフォーム事例' ),
		'repair'     => array( '修理・小工事',        '修理・小工事の事例' ),
		'demolition' => array( '解体',                '解体工事の事例' ),
		'other'      => array( 'その他',              'リフォーム事例' ),
	);
}

/** 英字 => 名前 だけの一覧 */
function ymkrf_works_parts_names() {
	$out = array();
	foreach ( ymkrf_works_parts_master() as $slug => $v ) $out[ $slug ] = $v[0];
	return $out;
}

/** 名前 => 題名に使う言い方 */
function ymkrf_works_part_title_word( $name ) {
	foreach ( ymkrf_works_parts_master() as $v ) {
		if ( $v[0] === $name ) return $v[1];
	}
	return $name . 'リフォーム事例';
}

/* 足りない項目を作り、名前が変わったものは付けかえます
   （数字を上げると、もう一度だけ走ります） */
add_action( 'admin_init', function () {
	if ( get_option( 'ymkrf_works_cat_ver' ) === '4' ) return;
	if ( ! taxonomy_exists( 'ymkrf_works_cat' ) ) return;

	foreach ( ymkrf_works_parts_names() as $slug => $name ) {
		$t = get_term_by( 'slug', $slug, 'ymkrf_works_cat' );
		if ( ! $t || is_wp_error( $t ) ) {
			wp_insert_term( $name, 'ymkrf_works_cat', array( 'slug' => $slug ) );
		} elseif ( $t->name !== $name ) {
			wp_update_term( $t->term_id, 'ymkrf_works_cat', array( 'name' => $name ) );
		}
	}

	/* リフォームメニューに無い項目は、使われていなければ片づけます */
	$all = get_terms( array( 'taxonomy' => 'ymkrf_works_cat', 'hide_empty' => false ) );
	if ( ! is_wp_error( $all ) ) {
		$keep = array_keys( ymkrf_works_parts_names() );
		foreach ( $all as $t ) {
			if ( ! in_array( $t->slug, $keep, true ) && (int) $t->count === 0 ) {
				wp_delete_term( $t->term_id, 'ymkrf_works_cat' );
			}
		}
	}

	update_option( 'ymkrf_works_cat_ver', '4' );
} );

/** 箇所の項目を、決めた順に並べて返します */
function ymkrf_works_part_terms() {
	$ts = get_terms( array( 'taxonomy' => 'ymkrf_works_cat', 'hide_empty' => false ) );
	if ( is_wp_error( $ts ) || ! $ts ) return array();
	$order = array_keys( ymkrf_works_parts_master() );
	usort( $ts, function ( $a, $b ) use ( $order ) {
		$ia = array_search( $a->slug, $order, true ); if ( $ia === false ) $ia = 900;
		$ib = array_search( $b->slug, $order, true ); if ( $ib === false ) $ib = 900;
		if ( $ia === $ib ) return strcmp( $a->name, $b->name );
		return ( $ia < $ib ) ? -1 : 1;
	} );
	return $ts;
}

/* ------------------------------------------------------------
   1-b. 使った商品のチェック欄を、まとまりに分けます
        キッチン → お風呂 → トイレ → 手洗い付きカウンタートイレ
        → 洗面化粧台 → … の順に出します。
        商品がまだ1つも無いまとまりも、見出しだけ出しておきます。
   ------------------------------------------------------------ */
function ymkrf_works_prod_groups_master() {
	return array(
		'kitchen'        => 'キッチン',
		'bathroom'       => 'お風呂',
		'toilet'         => 'トイレ',
		'toilet-counter' => '手洗い付きカウンタートイレ',
		'lavatory'       => '洗面化粧台',
		'boiler'         => '給湯器',
		'ecocute'        => 'エコキュート',
		'outer-wall'     => '外壁・屋根',
		'window'         => '窓・玄関ドア',
		'interior'       => '内装・改装',
		'other'          => 'その他',
	);
}

/** 商品を、上のまとまりごとに振り分けて返します */
function ymkrf_works_prod_groups() {

	$groups = array();
	foreach ( ymkrf_works_prod_groups_master() as $k => $label ) {
		$groups[ $k ] = array( 'label' => $label, 'items' => array() );
	}

	/* 商品ページと同じ並び順（グレード順 → 込み価格の安い順）で取ります */
	$q = new WP_Query( array(
		'post_type'      => 'ymkrf_product',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'no_found_rows'  => true,
		'ymkrf_sort'     => 'price',
	) );
	$prods = $q->posts;
	wp_reset_postdata();

	foreach ( (array) $prods as $pr ) {

		$slugs = wp_get_object_terms( $pr->ID, 'ymkrf_product_cat', array( 'fields' => 'slugs' ) );
		if ( is_wp_error( $slugs ) ) $slugs = array();

		$key = 'other';
		foreach ( array_keys( ymkrf_works_prod_groups_master() ) as $k ) {
			if ( in_array( $k, (array) $slugs, true ) ) { $key = $k; break; }
		}

		/* トイレのうち、手洗いカウンターが付いているものは分けます */
		if ( $key === 'toilet'
		  && preg_match( '/手洗い?\s*カウンター/u', (string) $pr->post_title ) ) {
			$key = 'toilet-counter';
		}

		$groups[ $key ]['items'][] = $pr;
	}

	return $groups;
}

add_action( 'add_meta_boxes', function () {
	/* もとの簡単な入力欄は使いません（この下の詳しいものに差しかえます） */
	remove_meta_box( 'ymkrf_works_box', 'ymkrf_works', 'side' );

	/* 「部位」は施工データの中に入れましたので、右側の同じ箱は消します */
	remove_meta_box( 'ymkrf_works_catdiv', 'ymkrf_works', 'side' );

	/* アイキャッチ画像（＝After写真）も、Before写真と横に並べて入れられるようにしたので
	   右側の箱は消します。まちがえて別々の写真が入るのを防ぐためです。 */
	remove_meta_box( 'postimagediv', 'ymkrf_works', 'side' );

	add_meta_box( 'ymkrf_works_data', '施工データ', 'ymkrf_works_metabox', 'ymkrf_works', 'normal', 'high' );
}, 20 );

/* ------------------------------------------------------------
   1-c. 写真（Before・施工中・After）
        それぞれ5枚まで入れられます。
        1枚目が代表の写真で、Before1枚目とAfter1枚目で
        「左右に動かして見くらべる」表示を作ります。
   ------------------------------------------------------------ */
define( 'YMKRF_PHOTO_MAX', 5 );

/** 写真のまとまり。before / during / after */
function ymkrf_works_photo_keys() {
	return array(
		'before' => array( '_ymkrf_before_imgs', 'Before（施工前）' ),
		'during' => array( '_ymkrf_during_imgs', '施工中' ),
		'after'  => array( '_ymkrf_after_imgs',  'After（施工後）' ),
	);
}

/** 写真のID（配列）。むかしの1枚だけの入れかたも読みます */
function ymkrf_works_photos( $post_id, $which ) {
	$keys = ymkrf_works_photo_keys();
	if ( ! isset( $keys[ $which ] ) ) return array();

	$v   = (string) get_post_meta( $post_id, $keys[ $which ][0], true );
	$ids = array_values( array_filter( array_map( 'intval', explode( ',', $v ) ) ) );

	/* まだ新しい入れかたにしていない記事のために */
	if ( ! $ids ) {
		if ( $which === 'before' ) {
			$one = (int) get_post_meta( $post_id, '_ymkrf_before_img', true );
			if ( $one ) $ids = array( $one );
		} elseif ( $which === 'after' ) {
			$one = (int) get_post_thumbnail_id( $post_id );
			if ( $one ) $ids = array( $one );
		}
	}

	/* 消された写真は出しません */
	$out = array();
	foreach ( $ids as $id ) {
		if ( get_post_type( $id ) === 'attachment' ) $out[] = $id;
	}
	return array_slice( array_values( array_unique( $out ) ), 0, YMKRF_PHOTO_MAX );
}

/** 写真をしまいます。1枚目はBefore写真／アイキャッチにも入れます */
function ymkrf_works_photos_save( $post_id, $which, $ids ) {
	$keys = ymkrf_works_photo_keys();
	if ( ! isset( $keys[ $which ] ) ) return;

	$ids = array_slice( array_values( array_unique( array_filter( array_map( 'intval', (array) $ids ) ) ) ),
	                    0, YMKRF_PHOTO_MAX );
	update_post_meta( $post_id, $keys[ $which ][0], implode( ',', $ids ) );

	if ( $which === 'before' ) {
		update_post_meta( $post_id, '_ymkrf_before_img', $ids ? (int) $ids[0] : 0 );
	} elseif ( $which === 'after' ) {
		if ( $ids ) set_post_thumbnail( $post_id, (int) $ids[0] );
		else        delete_post_thumbnail( $post_id );
	}
}

/**
 * 写真をえらぶ欄（5枚まで）
 * $which … before / during / after
 */
function ymkrf_works_photo_field( $which, $label, $note, $post_id ) {

	$keys = ymkrf_works_photo_keys();
	$name = $keys[ $which ][0];
	$ids  = ymkrf_works_photos( $post_id, $which );
	$dom  = 'ymkrf-ph-' . $which;

	$data = array();
	foreach ( $ids as $i ) {
		$u = wp_get_attachment_image_url( $i, 'medium' );
		if ( $u ) $data[] = array( 'id' => (int) $i, 'url' => $u );
	}
	?>
	<div class="ymkrf-photo" data-which="<?php echo esc_attr( $which ); ?>">
	  <p class="ymkrf-photo__ttl">
	    <?php echo esc_html( $label ); ?>
	    <span class="ymkrf-photo__count" id="<?php echo esc_attr( $dom ); ?>-count"></span>
	  </p>
	  <div class="ymkrf-photo__list" id="<?php echo esc_attr( $dom ); ?>-list"></div>
	  <input type="hidden" id="<?php echo esc_attr( $dom ); ?>" name="<?php echo esc_attr( $name ); ?>"
	         value="<?php echo esc_attr( implode( ',', $ids ) ); ?>">
	  <p class="ymkrf-photo__btns">
	    <button type="button" class="button button-primary ymkrf-photo__pick"
	            data-target="<?php echo esc_attr( $dom ); ?>"
	            data-title="<?php echo esc_attr( $label . 'をえらぶ' ); ?>">写真をえらぶ</button>
	  </p>
	  <p class="description"><?php echo wp_kses_post( $note ); ?></p>
	  <script>
	    window.ymkrfPhotos = window.ymkrfPhotos || {};
	    window.ymkrfPhotos['<?php echo esc_js( $dom ); ?>'] = <?php echo wp_json_encode( $data ); ?>;
	  </script>
	</div>
	<?php
}

function ymkrf_works_metabox( $post ) {
	wp_nonce_field( 'ymkrf_works_save', 'ymkrf_works_nonce' );
	$get = function ( $k, $d = '' ) use ( $post ) {
		$v = get_post_meta( $post->ID, $k, true );
		return ( $v === '' || $v === null ) ? $d : $v;
	};
	$shops = get_terms( array( 'taxonomy' => 'ymkrf_shop', 'hide_empty' => false ) );
	$sel   = array_map( 'intval', array_filter( explode( ',', (string) $get( '_ymkrf_products' ) ) ) );

	$parts    = ymkrf_works_part_terms();
	$partsel  = wp_get_object_terms( $post->ID, 'ymkrf_works_cat', array( 'fields' => 'ids' ) );
	if ( is_wp_error( $partsel ) ) $partsel = array();
	$partsel  = array_map( 'intval', (array) $partsel );

	$pgroups  = ymkrf_works_prod_groups();

	/* 営業をしない人（本部の事務など）は、ここには出しません */
	$staffs   = function_exists( 'ymkrf_staff_sales_list' ) ? ymkrf_staff_sales_list() : array();
	$staffcur = (int) $get( '_ymkrf_staff' );
	$items    = ymkrf_works_items_text( $post->ID );
	?>
	<div class="ymkrf-works">
	  <table class="form-table ymkrf-works__table">

	    <tr>
	      <th>案件番号</th>
	      <td>
	        <input type="text" name="_ymkrf_case_no" value="<?php echo esc_attr( $get( '_ymkrf_case_no' ) ); ?>" class="regular-text">
	        <p class="description">
	          URLの後半になります（例：/works/kitchen/2604-0180/）。<br>
	          <b>お客様の声と同じ番号を入れると、おたがいに自動でリンクします。</b>
	        </p>
	      </td>
	    </tr>

	    <tr>
	      <th>工事費</th>
	      <td>
	        <input type="text" name="_ymkrf_price" value="<?php echo esc_attr( $get( '_ymkrf_price' ) ); ?>" class="regular-text"
	               placeholder="例：128万円（工事費込み・税込）">
	        <p class="description">金額を出したくないときは、空のままで大丈夫です。</p>
	      </td>
	    </tr>

	    <tr>
	      <th>工期</th>
	      <td><input type="text" name="_ymkrf_period" value="<?php echo esc_attr( $get( '_ymkrf_period' ) ); ?>" class="regular-text"
	                 placeholder="例：3日"></td>
	    </tr>

	    <tr>
	      <th>完工時期</th>
	      <td>
	        <input type="text" name="_ymkrf_done" value="<?php echo esc_attr( $get( '_ymkrf_done' ) ); ?>" class="regular-text"
	               placeholder="例：2026年7月">
	        <p class="description">日にちまでは出しません。年月だけにしておくと、お客様が特定されにくくなります。</p>
	      </td>
	    </tr>

	    <tr>
	      <th>お客様（名字の頭文字）</th>
	      <td>
	        <input type="text" name="_ymkrf_initial" value="<?php echo esc_attr( $get( '_ymkrf_initial' ) ); ?>"
	               class="small-text" maxlength="2" placeholder="例：Y"> 様
	        <p class="description">題名のうしろに「（Y様）」と付きます。空でもかまいません。</p>
	      </td>
	    </tr>

	    <tr>
	      <th>担当した店舗</th>
	      <td>
	        <?php $cur = (string) $get( '_ymkrf_shop' ); ?>
	        <select name="_ymkrf_shop" id="ymkrf-shop-sel">
	          <option value="">（えらんでください）</option>
	          <?php if ( ! is_wp_error( $shops ) ) foreach ( (array) $shops as $sh ) : ?>
	            <option value="<?php echo esc_attr( $sh->slug ); ?>" <?php selected( $cur, $sh->slug ); ?>>
	              <?php echo esc_html( $sh->name ); ?></option>
	          <?php endforeach; ?>
	        </select>
	      </td>
	    </tr>

	    <tr>
	      <th>営業担当 <span class="ymkrf-need">必須</span></th>
	      <td>
	        <?php if ( $staffs ) : ?>
	          <?php
	          /* 画面に出すための一覧をつくります（JavaScriptで組み立てます） */
	          $slist = $staffs;
	          /* すでにえらばれている人が一覧に無いとき（あとから外した人など）は、
	             消えてしまわないように足しておきます */
	          if ( $staffcur ) {
	            $has = false;
	            foreach ( $slist as $st ) { if ( (int) $st->ID === $staffcur ) { $has = true; break; } }
	            if ( ! $has ) {
	              $sp = get_post( $staffcur );
	              if ( $sp && $sp->post_type === 'ymkrf_staff' ) $slist[] = $sp;
	            }
	          }
	          $sdata = array();
	          foreach ( $slist as $st ) {
	            $sdata[] = array(
	              'id'    => (int) $st->ID,
	              'name'  => (string) get_the_title( $st ),
	              'shop'  => (string) get_post_meta( $st->ID, '_ymkrf_staff_shop', true ),
	              'sname' => (string) ymkrf_staff_shop_name( $st->ID ),
	              'role'  => (string) get_post_meta( $st->ID, '_ymkrf_staff_role', true ),
	            );
	          }
	          ?>
	          <div class="ymkrf-pick" id="ymkrf-staff-pick">
	            <input type="hidden" name="_ymkrf_staff" id="ymkrf-staff-val" value="<?php echo (int) $staffcur; ?>">
	            <button type="button" class="button ymkrf-pick__btn" id="ymkrf-staff-btn">（えらんでください）</button>
	            <div class="ymkrf-pick__menu" id="ymkrf-staff-menu" hidden></div>
	          </div>
	          <script>
	            window.ymkrfStaffList  = <?php echo wp_json_encode( $sdata ); ?>;
	            window.ymkrfStaffOrder = <?php echo wp_json_encode( array_keys( ymkrf_staff_shops() ) ); ?>;
	          </script>
	          <p class="description">
	            上の「担当した店舗」をえらぶと、<b>その店舗の人だけ</b>が出ます。<br>
	            ほかの店舗や本部・工事部の人にするときは、いちばん下の
	            <b>「その他」にマウスを乗せる</b>と、横に全員の名前が出ます。<br>
	            名前と顔写真は「スタッフ」で登録してください。<br>
	            <b>えらばないと公開できません。</b>
	          </p>
	        <?php endif; ?>
	      </td>
	    </tr>

	    <tr>
	      <th>営業担当からのひとこと</th>
	      <td>
	        <textarea name="_ymkrf_works_comment" rows="4" class="large-text"
	                  placeholder="例：使いやすさを第一に、ご主人の背の高さに合わせて高さを決めました。&#10;工事中もお気づかいいただき、ありがとうございました。"><?php
	          echo esc_textarea( (string) $get( '_ymkrf_works_comment' ) ); ?></textarea>
	        <p class="description">
	          この工事について、担当者からのひとことです。ページの下のほうに、
	          <b>顔写真といっしょに</b>出ます。空でもかまいません。
	        </p>
	      </td>
	    </tr>

	    <tr>
	      <th>リフォームした箇所</th>
	      <td>
	        <?php if ( $parts ) : ?>
	          <div class="ymkrf-works__parts">
	            <?php foreach ( $parts as $pt ) : ?>
	              <label>
	                <input type="checkbox" name="ymkrf_works_cat[]" value="<?php echo (int) $pt->term_id; ?>"
	                  <?php checked( in_array( (int) $pt->term_id, $partsel, true ) ); ?>>
	                <span><?php echo esc_html( $pt->name ); ?></span>
	              </label>
	            <?php endforeach; ?>
	          </div>
	          <p class="description">
	            いくつでもえらべます。<b>いちばん上でチェックした箇所が、URLになります</b>
	            （例：/works/<b>kitchen</b>/2604-0180/）。
	          </p>
	        <?php else : ?>
	          <p class="description">項目がまだ作られていません。</p>
	        <?php endif; ?>
	      </td>
	    </tr>

	    <tr>
	      <th>写真</th>
	      <td>
	        <div class="ymkrf-photos">
	          <?php
	          ymkrf_works_photo_field( 'before', 'Before（施工前）',
	            '工事にとりかかる前の写真です。', $post->ID );
	          ymkrf_works_photo_field( 'during', '施工中',
	            '工事のとちゅうの様子です。無くてもかまいません。', $post->ID );
	          ymkrf_works_photo_field( 'after', 'After（施工後）',
	            '仕上がりの写真です。', $post->ID );
	          ?>
	        </div>
	        <p class="description">
	          それぞれ<b>5枚まで</b>入れられます。まとめてえらべます。<br>
	          <b>1枚目がいちばん大事な写真</b>です。BeforeとAfterの1枚目で
	          「左右に動かして見くらべる」表示を作り、<b>Afterの1枚目が一覧に出ます</b>。<br>
	          写真の上の <b>◀ ▶</b> で順番を入れかえ、<b>×</b> ではずせます。<br>
	          BeforeとAfterは、同じ場所・同じ向きで撮ると、きれいに見くらべられます。
	        </p>
	      </td>
	    </tr>

	    <tr>
	      <th>おこなった工事</th>
	      <td>
	        <textarea name="_ymkrf_work_items" rows="6" class="large-text ymkrf-works__items"
	                  placeholder="キッチン交換工事&#10;止水栓交換&#10;既存品撤去・処分"><?php
	          echo esc_textarea( $items ); ?></textarea>
	        <p class="description">
	          <b>1行に1つずつ</b>書いてください。ページでは、オレンジのチェック印を付けて並べます。<br>
	          記号（・や※）は要りません。空の行はとばします。
	        </p>
	      </td>
	    </tr>

	    <tr>
	      <th>使った商品</th>
	      <td>
	        <div class="ymkrf-works__pgroups">
	          <?php foreach ( $pgroups as $gk => $g ) : ?>
	            <div class="ymkrf-works__pgroup">
	              <p class="ymkrf-works__pttl"><?php echo esc_html( $g['label'] ); ?>
	                <?php if ( $gk !== 'other' ) : ?>
	                  <span class="ymkrf-works__pnum"><?php echo count( $g['items'] ); ?></span>
	                <?php endif; ?>
	              </p>

	              <?php if ( $g['items'] ) : ?>
	                <div class="ymkrf-works__prods">
	                  <?php foreach ( $g['items'] as $pr ) : ?>
	                    <label>
	                      <input type="checkbox" name="_ymkrf_products[]" value="<?php echo (int) $pr->ID; ?>"
	                        <?php checked( in_array( (int) $pr->ID, $sel, true ) ); ?>>
	                      <span><?php echo esc_html( get_the_title( $pr ) ); ?></span>
	                    </label>
	                  <?php endforeach; ?>
	                </div>
	              <?php elseif ( $gk !== 'other' ) : ?>
	                <p class="ymkrf-works__pempty">商品ページができたら、ここに出ます。<br>
	                  それまでは、いちばん下の「その他」に商品名を書いてください。</p>
	              <?php endif; ?>

	              <?php if ( $gk === 'other' ) : ?>
	                <div class="ymkrf-works__ptext">
	                  <label for="_ymkrf_product_text">ページが無い商品は、ここに名前を書いてください</label>
	                  <input type="text" id="_ymkrf_product_text" name="_ymkrf_product_text"
	                         value="<?php echo esc_attr( $get( '_ymkrf_product_text' ) ); ?>"
	                         class="large-text" placeholder="例：トクラス　Bbプラス">

	                  <label class="ymkrf-works__oldpack">
	                    <input type="checkbox" name="_ymkrf_oldpack" value="1"
	                      <?php checked( (string) $get( '_ymkrf_oldpack' ), '1' ); ?>>
	                    <span>※こちらはヤマキシ旧パック商品となります</span>
	                  </label>
	                  <p class="ymkrf-works__oldpacknote">
	                    えらんだ商品の<b>取り扱いが終わって非表示になると、公開中の事例には
	                    自動でチェックが入ります</b>（下書きのあいだは何もしません）。<br>
	                    自動で外れることはないので、はずしたいときは手でチェックを外してください。
	                  </p>
	                </div>
	              <?php endif; ?>
	            </div>
	          <?php endforeach; ?>
	        </div>
	        <p class="description">チェックすると、ページの下に商品ページへのリンクが出ます（いくつでも可）。</p>
	      </td>
	    </tr>

	  </table>
	</div>
	<?php
}

/* 入力画面の見た目 */
add_action( 'admin_head', function () {
	$s = get_current_screen();
	if ( ! $s || $s->post_type !== 'ymkrf_works' ) return;
	echo '<style>
	  .ymkrf-works__table th{width:190px}
	  .ymkrf-works__oldpacknote{margin:6px 0 0;font-size:12px;color:#787c82;line-height:1.7}

	  /* リフォームした箇所 */
	  .ymkrf-works__parts{
	    display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));
	    gap:6px 14px;max-width:940px}
	  .ymkrf-works__parts label{
	    display:grid;grid-template-columns:22px 1fr;align-items:center;
	    padding:6px 10px;border:1px solid #dcdcde;border-radius:6px;background:#fff}
	  .ymkrf-works__parts label:hover{border-color:#fe3301}
	  .ymkrf-works__parts input{margin:0}

	  /* 使った商品 */
	  .ymkrf-works__pgroups{
	    max-width:940px;border:1px solid #dcdcde;border-radius:8px;
	    background:#fff;max-height:520px;overflow:auto}
	  .ymkrf-works__pgroup{border-bottom:1px solid #f0f0f1;padding:10px 14px 14px}
	  .ymkrf-works__pgroup:last-child{border-bottom:0}
	  .ymkrf-works__pttl{
	    margin:0 0 8px;font-size:13px;font-weight:700;color:#1d2327;
	    display:flex;align-items:center;gap:8px}
	  .ymkrf-works__pnum{
	    font-size:11px;font-weight:700;color:#646970;background:#f0f0f1;
	    border-radius:999px;padding:1px 8px}
	  .ymkrf-works__prods{
	    display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));
	    gap:4px 14px}
	  .ymkrf-works__prods label{
	    display:grid;grid-template-columns:22px 1fr;align-items:start;
	    line-height:1.5;padding:2px 0}
	  .ymkrf-works__prods input{margin:2px 0 0}
	  .ymkrf-works__pempty{margin:0;font-size:12px;color:#8c8f94;line-height:1.7}
	  .ymkrf-works__ptext label{display:block;font-size:12px;color:#646970;margin-bottom:4px}
	  .ymkrf-works__oldpack{
	    display:grid !important;grid-template-columns:22px 1fr;align-items:center;
	    margin:8px 0 0 !important;padding:6px 10px;
	    border:1px solid #dcdcde;border-radius:6px;background:#fff;
	    font-size:13px !important;color:#1d2327 !important;line-height:1.5}
	  .ymkrf-works__oldpack input{margin:0}

	  /* 写真（Before・施工中・After）を横に並べます */
	  .ymkrf-photos{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;max-width:1100px}
	  @media (max-width:1100px){ .ymkrf-photos{grid-template-columns:1fr} }
	  .ymkrf-photo{border:1px solid #dcdcde;border-radius:8px;background:#fff;padding:12px}
	  .ymkrf-photo__ttl{
	    margin:0 0 8px;font-size:13px;font-weight:700;color:#1d2327;
	    display:flex;align-items:center;gap:8px}
	  .ymkrf-photo__count{
	    font-size:11px;font-weight:700;color:#646970;background:#f0f0f1;
	    border-radius:999px;padding:1px 8px}
	  .ymkrf-photo__count.is-full{background:#fe3301;color:#fff}
	  .ymkrf-photo__list{
	    display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin-bottom:10px}
	  .ymkrf-photo__item{
	    position:relative;aspect-ratio:4/3;background:#f0f0f1;border-radius:6px;overflow:hidden}
	  .ymkrf-photo__item img{width:100%;height:100%;object-fit:cover;display:block}
	  .ymkrf-photo__item--1{grid-column:1 / -1;aspect-ratio:16/10}
	  .ymkrf-photo__no{
	    position:absolute;left:4px;bottom:4px;z-index:2;
	    background:rgba(0,0,0,.6);color:#fff;font-size:10px;font-weight:700;
	    border-radius:4px;padding:1px 6px;line-height:1.6}
	  .ymkrf-photo__item--1 .ymkrf-photo__no{background:#fe3301}
	  .ymkrf-photo__ops{
	    position:absolute;top:4px;right:4px;z-index:2;display:flex;gap:3px}
	  .ymkrf-photo__op{
	    width:22px;height:22px;line-height:20px;text-align:center;padding:0;
	    border:0;border-radius:4px;cursor:pointer;font-size:12px;font-weight:700;
	    background:rgba(0,0,0,.6);color:#fff}
	  .ymkrf-photo__op:hover{background:#000}
	  .ymkrf-photo__op--del:hover{background:#c00}
	  .ymkrf-photo__op[disabled]{opacity:.3;cursor:default}
	  .ymkrf-photo__empty{
	    grid-column:1 / -1;display:flex;align-items:center;justify-content:center;
	    aspect-ratio:16/10;background:#f6f7f7;border:1px dashed #c3c4c7;border-radius:6px;
	    font-size:12px;color:#a7aaad}
	  .ymkrf-photo__btns{margin:0 0 6px}

	  /* おこなった工事 */
	  .ymkrf-works__items{max-width:940px;font-size:14px;line-height:1.9}

	  /* 営業担当のえらび方 */
	  .ymkrf-pick{position:relative;display:inline-block}
	  .ymkrf-pick__btn{min-width:260px;text-align:left}
	  .ymkrf-pick__btn--set{font-weight:700}
	  .ymkrf-pick__btn--need{border-color:#d63638;color:#d63638}
	  .ymkrf-need{
	    display:inline-block;margin-left:6px;padding:1px 6px;border-radius:3px;
	    background:#d63638;color:#fff;font-size:10.5px;font-weight:700;vertical-align:2px}
	  .ymkrf-pick__btn::after{content:" \25be";float:right;color:#787c82}
	  .ymkrf-pick__menu{
	    position:absolute;z-index:100;top:100%;left:0;margin-top:2px;
	    min-width:260px;
	    background:#fff;border:1px solid #c3c4c7;border-radius:6px;
	    box-shadow:0 6px 20px rgba(0,0,0,.14);padding:4px}
	  .ymkrf-pick__scroll{max-height:300px;overflow:auto}
	  .ymkrf-pick__item{
	    display:block;width:100%;text-align:left;border:0;background:none;cursor:pointer;
	    padding:6px 10px;border-radius:4px;font-size:13.5px;line-height:1.5}
	  .ymkrf-pick__item:hover{background:#fff2ee}
	  .ymkrf-pick__item--none{color:#787c82}
	  .ymkrf-pick__nm{font-weight:700}
	  .ymkrf-pick__ro{margin-left:8px;font-size:11.5px;color:#787c82}
	  .ymkrf-pick__empty{margin:6px 10px;font-size:12px;color:#a7aaad}
	  .ymkrf-pick__more{
	    position:relative;margin-top:4px;padding:7px 10px;border-top:1px solid #f0f0f1;
	    font-size:13.5px;font-weight:700;color:#2271b1;cursor:default}
	  .ymkrf-pick__more>span::after{content:" \25b8"}
	  .ymkrf-pick__more:hover{background:#f6f7f7}
	  .ymkrf-pick__sub{
	    display:none;position:absolute;left:100%;top:-4px;margin-left:2px;
	    width:520px;max-height:420px;overflow:auto;
	    background:#fff;border:1px solid #c3c4c7;border-radius:6px;
	    box-shadow:0 6px 20px rgba(0,0,0,.14);padding:8px}
	  .ymkrf-pick__more:hover .ymkrf-pick__sub{display:block}
	  .ymkrf-pick__gttl{
	    margin:8px 0 4px;padding:0 6px;font-size:11.5px;font-weight:700;color:#646970;
	    border-left:3px solid #fe3301;line-height:1.4}
	  .ymkrf-pick__gttl:first-child{margin-top:0}
	  .ymkrf-pick__grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 8px}
	</style>';
} );

/* 写真えらび／営業担当のしぼり込み */
add_action( 'admin_footer', function () {
	$s = get_current_screen();
	if ( ! $s || $s->post_type !== 'ymkrf_works' ) return;
	if ( ! in_array( $s->base, array( 'post' ), true ) ) return;
	?>
	<script>
	jQuery(function ($) {

		/* 写真（5枚まで） */
		var MAX = <?php echo (int) YMKRF_PHOTO_MAX; ?>;
		var frames = {};

		function draw(dom) {
			var list = window.ymkrfPhotos[dom] || [];
			var $box = $('#' + dom + '-list').empty();

			$('#' + dom).val(list.map(function (p) { return p.id; }).join(','));
			$('#' + dom + '-count').text(list.length + ' / ' + MAX)
				.toggleClass('is-full', list.length >= MAX);

			if (!list.length) {
				$box.append('<div class="ymkrf-photo__empty">写真がまだありません</div>');
				return;
			}

			list.forEach(function (p, i) {
				var $it = $('<div class="ymkrf-photo__item' + (i === 0 ? ' ymkrf-photo__item--1' : '') + '">');
				$it.append($('<img>').attr('src', p.url));
				$it.append('<span class="ymkrf-photo__no">' + (i === 0 ? '代表' : (i + 1) + '枚目') + '</span>');

				var $ops = $('<span class="ymkrf-photo__ops">');
				$ops.append($('<button type="button" class="ymkrf-photo__op" title="前へ">◀</button>')
					.prop('disabled', i === 0)
					.on('click', function () { move(dom, i, -1); }));
				$ops.append($('<button type="button" class="ymkrf-photo__op" title="うしろへ">▶</button>')
					.prop('disabled', i === list.length - 1)
					.on('click', function () { move(dom, i, 1); }));
				$ops.append($('<button type="button" class="ymkrf-photo__op ymkrf-photo__op--del" title="はずす">×</button>')
					.on('click', function () {
						window.ymkrfPhotos[dom].splice(i, 1);
						draw(dom);
					}));
				$it.append($ops);
				$box.append($it);
			});
		}

		function move(dom, i, d) {
			var a = window.ymkrfPhotos[dom];
			var j = i + d;
			if (j < 0 || j >= a.length) return;
			var t = a[i]; a[i] = a[j]; a[j] = t;
			draw(dom);
		}

		$(document).on('click', '.ymkrf-photo__pick', function (e) {
			e.preventDefault();
			var dom = $(this).data('target'), ttl = $(this).data('title');
			var left = MAX - (window.ymkrfPhotos[dom] || []).length;
			if (left <= 0) {
				window.alert('写真は' + MAX + '枚までです。はずしてから、えらびなおしてください。');
				return;
			}
			if (!frames[dom]) {
				frames[dom] = wp.media({ title: ttl, library: { type: 'image' }, multiple: 'add',
					button: { text: 'この写真をつかう' } });
				frames[dom].on('select', function () {
					var got = frames[dom].state().get('selection').toJSON();
					var a   = window.ymkrfPhotos[dom];
					got.forEach(function (m) {
						if (a.length >= MAX) return;
						if (a.some(function (p) { return p.id === m.id; })) return;
						a.push({ id: m.id, url: (m.sizes && m.sizes.medium) ? m.sizes.medium.url : m.url });
					});
					if (got.length > left) {
						window.alert('写真は' + MAX + '枚までです。はじめの' + left + '枚だけ入れました。');
					}
					draw(dom);
				});
			}
			frames[dom].open();
		});

		Object.keys(window.ymkrfPhotos || {}).forEach(draw);

		/* ------------------------------------------------------------
		   営業担当のえらび方

		   ふだんは「担当した店舗」の人だけを出します。
		   いちばん下の「その他」にマウスを乗せると、横に全員が出ます。
		   ------------------------------------------------------------ */
		var $pick = $('#ymkrf-staff-pick');
		if ($pick.length && window.ymkrfStaffList) {

			var LIST  = window.ymkrfStaffList;
			var ORDER = window.ymkrfStaffOrder || [];
			var $val  = $('#ymkrf-staff-val'), $btn = $('#ymkrf-staff-btn'), $menu = $('#ymkrf-staff-menu');
			var $shopSel = $('#ymkrf-shop-sel');

			function byId(id) {
				for (var i = 0; i < LIST.length; i++) if (LIST[i].id === +id) return LIST[i];
				return null;
			}

			function label(p) {
				return p.name + (p.sname ? '（' + p.sname + '）' : '');
			}

			function drawBtn() {
				var p = byId($val.val());
				$btn.text(p ? label(p) : '（えらんでください）')
					.toggleClass('ymkrf-pick__btn--set', !!p)
					.toggleClass('ymkrf-pick__btn--need', !p);
			}

			function row(p) {
				return $('<button type="button" class="ymkrf-pick__item">')
					.attr('data-id', p.id)
					.append($('<span class="ymkrf-pick__nm">').text(p.name))
					.append(p.role ? $('<span class="ymkrf-pick__ro">').text(p.role) : null);
			}

			/* 全員を店舗ごとにまとめた枠 */
			function allPanel() {
				var $sub = $('<div class="ymkrf-pick__sub">');
				var seen = {};
				var order = ORDER.slice();
				LIST.forEach(function (p) { if (order.indexOf(p.shop) < 0) order.push(p.shop); });

				order.forEach(function (sh) {
					var mem = LIST.filter(function (p) { return p.shop === sh; });
					if (!mem.length || seen[sh]) return;
					seen[sh] = 1;
					$sub.append($('<p class="ymkrf-pick__gttl">').text(mem[0].sname || 'そのほか'));
					var $g = $('<div class="ymkrf-pick__grid">');
					mem.forEach(function (p) { $g.append(row(p)); });
					$sub.append($g);
				});
				return $sub;
			}

			function build() {
				var sh = $shopSel.length ? $shopSel.val() : '';
				$menu.empty();

				/* 名前のところだけスクロールさせます。
				   「その他」を外に出しておかないと、横に開く枠が切れてしまいます。 */
				var $scroll = $('<div class="ymkrf-pick__scroll">');

				var mem = sh ? LIST.filter(function (p) { return p.shop === sh; }) : LIST;
				mem.forEach(function (p) { $scroll.append(row(p)); });

				if (sh && !mem.length) {
					$scroll.append($('<p class="ymkrf-pick__empty">').text('この店舗のスタッフはまだ登録されていません。'));
				}
				$menu.append($scroll);

				if (sh) {
					$menu.append($('<div class="ymkrf-pick__more">')
						.append($('<span>').text('その他（全員から選ぶ）'))
						.append(allPanel()));
				}
			}

			function open()  { build(); $menu.prop('hidden', false); }
			function close() { $menu.prop('hidden', true); }

			$btn.on('click', function (e) {
				e.preventDefault();
				if ($menu.prop('hidden')) open(); else close();
			});

			$menu.on('click', '.ymkrf-pick__item', function (e) {
				e.preventDefault();
				$val.val($(this).attr('data-id'));
				drawBtn();
				close();
			});

			/* 店舗を変えたら、選んでいた人がその店舗にいなければ外します */
			$shopSel.on('change', function () {
				var p = byId($val.val()), sh = $shopSel.val();
				if (p && sh && p.shop !== sh) { $val.val(0); drawBtn(); }
				if (!$menu.prop('hidden')) build();
			});

			$(document).on('click', function (e) {
				if (!$(e.target).closest('#ymkrf-staff-pick').length) close();
			});
			$(document).on('keydown', function (e) {
				if (e.key === 'Escape') close();
			});

			drawBtn();

			/* 営業担当は必須です。えらんでいないと公開できません。
			   （下書き保存は、書きかけを残せるように通します） */
			$('#publish').on('click', function (e) {
				if (byId($val.val())) return;
				e.preventDefault();
				window.alert('営業担当をえらんでください。');
				$('html, body').animate({ scrollTop: $pick.offset().top - 120 }, 200);
				$btn.trigger('focus');
				open();
			});
		}
	});
	</script>
	<?php
} );

add_action( 'save_post_ymkrf_works', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! isset( $_POST['ymkrf_works_nonce'] ) ||
	     ! wp_verify_nonce( $_POST['ymkrf_works_nonce'], 'ymkrf_works_save' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	foreach ( array( '_ymkrf_case_no', '_ymkrf_price', '_ymkrf_period',
	                 '_ymkrf_done', '_ymkrf_shop', '_ymkrf_initial',
	                 '_ymkrf_product_text' ) as $k ) {
		update_post_meta( $post_id, $k, isset( $_POST[ $k ] ) ? sanitize_text_field( $_POST[ $k ] ) : '' );
	}

	/* ヤマキシの旧パック商品かどうか */
	update_post_meta( $post_id, '_ymkrf_oldpack', empty( $_POST['_ymkrf_oldpack'] ) ? '' : '1' );
	/* 写真（Before・施工中・After）。それぞれ5枚まで。
	   Beforeの1枚目は _ymkrf_before_img に、Afterの1枚目はアイキャッチ画像に入ります。 */
	foreach ( ymkrf_works_photo_keys() as $which => $conf ) {
		if ( ! isset( $_POST[ $conf[0] ] ) ) continue;
		$list = explode( ',', (string) wp_unslash( $_POST[ $conf[0] ] ) );
		ymkrf_works_photos_save( $post_id, $which, $list );
	}

	/* おこなった工事（1行に1つ） */
	if ( isset( $_POST['_ymkrf_work_items'] ) ) {
		update_post_meta( $post_id, '_ymkrf_work_items',
			ymkrf_works_items_clean( wp_unslash( $_POST['_ymkrf_work_items'] ) ) );
	}

	/* 営業担当と、そのひとこと */
	if ( isset( $_POST['_ymkrf_staff'] ) ) {
		update_post_meta( $post_id, '_ymkrf_staff', (int) $_POST['_ymkrf_staff'] );
	}
	if ( isset( $_POST['_ymkrf_works_comment'] ) ) {
		update_post_meta( $post_id, '_ymkrf_works_comment',
			sanitize_textarea_field( wp_unslash( $_POST['_ymkrf_works_comment'] ) ) );
	}

	$ids = isset( $_POST['_ymkrf_products'] ) ? (array) $_POST['_ymkrf_products'] : array();
	$ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
	update_post_meta( $post_id, '_ymkrf_products', implode( ',', $ids ) );

	/* リフォームした箇所。右側の「部位」の箱は消してあるので、ここで入れます。
	   URLに使うのは、この中でいちばん上にある箇所です。 */
	if ( current_user_can( 'assign_terms', 'ymkrf_works_cat' )
	  || current_user_can( 'edit_ymkrf_works' ) ) {
		$pt = isset( $_POST['ymkrf_works_cat'] ) ? (array) $_POST['ymkrf_works_cat'] : array();
		$pt = array_values( array_unique( array_filter( array_map( 'intval', $pt ) ) ) );

		/* 決めた順（キッチン → お風呂 → …）に並べなおしてから入れます */
		$order = array_keys( ymkrf_works_parts_master() );
		usort( $pt, function ( $a, $b ) use ( $order ) {
			$sa = get_term( $a, 'ymkrf_works_cat' );
			$sb = get_term( $b, 'ymkrf_works_cat' );
			$ia = ( $sa && ! is_wp_error( $sa ) ) ? array_search( $sa->slug, $order, true ) : false;
			$ib = ( $sb && ! is_wp_error( $sb ) ) ? array_search( $sb->slug, $order, true ) : false;
			if ( $ia === false ) $ia = 900;
			if ( $ib === false ) $ib = 900;
			if ( $ia === $ib ) return 0;
			return ( $ia < $ib ) ? -1 : 1;
		} );

		wp_set_object_terms( $post_id, $pt, 'ymkrf_works_cat', false );
	}
}, 10 );


/* ============================================================
   2. URL

     1件のページ … /works/kitchen/2604-0180/
     部位ごと …… /works/kitchen/
     ぜんぶ …… /works/

   前半（kitchen）が部位、後半（2604-0180）が案件番号です。
   ============================================================ */

/** URLの中の %ymkrf_wpart% を、その事例の部位に置きかえます */
add_filter( 'post_type_link', function ( $link, $post ) {
	if ( ! $post || $post->post_type !== 'ymkrf_works' ) return $link;
	return str_replace( '%ymkrf_wpart%', ymkrf_works_cat_slug( $post->ID ), $link );
}, 10, 2 );

/**
 * 1つだけの階層（/works/kitchen/）は「部位ごとの一覧」として扱います。
 * ワードプレスが作るルールは、そのままでは投稿タイプが伝わらないので、
 * ここで部位の一覧に読みかえます。
 */
add_filter( 'request', function ( $qv ) {
	if ( empty( $qv['ymkrf_wpart'] ) ) return $qv;
	if ( isset( $qv['ymkrf_works'] ) || isset( $qv['name'] ) || isset( $qv['attachment'] ) ) return $qv;

	$slug = sanitize_title( $qv['ymkrf_wpart'] );
	$new  = array( 'ymkrf_works_cat' => $slug );
	if ( isset( $qv['paged'] ) ) $new['paged'] = $qv['paged'];
	if ( isset( $qv['feed'] ) )  $new['feed']  = $qv['feed'];
	return $new;
} );

/* --- 2-2. 題名 --------------------------------------------------
   ワードプレスの題名欄と、ページの大見出しを同じにします。

     野々市市｜キッチンリフォームの施工事例（Y様）
     └エリア┘ └ 工事の内容 ────────────┘

   保存するたびに、エリアに合わせて組み立て直します。
   ｜より右は、手で書きかえていただけます。
   ------------------------------------------------------------- */

/** エリアの名前（ひとつめ） */
function ymkrf_works_area_name( $post_id ) {
	$ts = get_the_terms( $post_id, 'ymkrf_works_area' );
	if ( $ts && ! is_wp_error( $ts ) ) {
		$t = reset( $ts );
		if ( $t ) return $t->name;
	}
	return '';
}

/**
 * 題名を自動で作ります。
 *
 *   キッチンリフォーム事例｜野々市市 Y様｜約100万円・工期2日
 *
 * ・頭に工事の種類を出します（検索する言葉と、頭をそろえるためです）
 * ・金額と工期は、あるときだけ出します
 * ・検索結果で切れないよう、長いときは 工期 → 金額 の順にはずします
 */

/** 題名に出す金額。かっこ書きは省き、「約」を付けます */
function ymkrf_works_price_short( $post_id ) {
	$v = trim( (string) get_post_meta( $post_id, '_ymkrf_price', true ) );
	if ( $v === '' ) return '';

	/* 「128万円（工事費込み・税込）」→「128万円」 */
	$v = preg_split( '/[（(]/u', $v );
	$v = trim( (string) $v[0] );
	if ( $v === '' ) return '';

	/* すでに「約」「およそ」「〜」が付いていれば、そのまま */
	if ( preg_match( '/^(約|およそ|〜|~)/u', $v ) ) return $v;
	return '約' . $v;
}

/** 工事の種類（部位）の部分。1個所・2個所・3個所以上で書き分けます */
function ymkrf_works_title_head( $post_id ) {
	$cats = ymkrf_works_term_names( $post_id, 'ymkrf_works_cat' );
	$cats = array_values( array_filter( $cats, function ( $v ) { return $v !== 'その他'; } ) );

	if ( ! $cats ) return 'リフォーム事例';

	/* 1個所のときは、その箇所に合った言い方を使います
	   （例：解体 →「解体工事の事例」、給湯器 →「給湯器交換の事例」） */
	if ( count( $cats ) === 1 ) return ymkrf_works_part_title_word( $cats[0] );

	/* 2個所は両方、3個所以上は「ほか」でまとめます（題名が長くなりすぎるため） */
	$name = implode( '・', array_slice( $cats, 0, 2 ) );
	if ( count( $cats ) > 2 ) $name .= 'ほか';

	return $name . 'リフォーム事例';
}

/** 題名ぜんぶ */
function ymkrf_works_auto_title( $post_id ) {

	$head = ymkrf_works_title_head( $post_id );

	$area = ymkrf_works_area_name( $post_id );
	$ini  = trim( (string) get_post_meta( $post_id, '_ymkrf_initial', true ) );
	$who  = trim( $area . ( $ini !== '' ? ' ' . $ini . '様' : '' ) );

	$price = ymkrf_works_price_short( $post_id );
	$peri  = trim( (string) get_post_meta( $post_id, '_ymkrf_period', true ) );
	$peri  = ( $peri !== '' ) ? '工期' . $peri : '';

	$make = function ( $pr, $pe ) use ( $head, $who ) {
		$t = $head;
		if ( $who !== '' ) $t .= '｜' . $who;
		$tail = array_values( array_filter( array( $pr, $pe ) ) );
		if ( $tail ) $t .= '｜' . implode( '・', $tail );
		return $t;
	};

	/* Googleの検索結果は全角30字くらいで切れます。少し余裕をみて34字までにします */
	$t = $make( $price, $peri );
	if ( mb_strlen( $t ) > 34 ) $t = $make( $price, '' );
	if ( mb_strlen( $t ) > 34 ) $t = $make( '', '' );
	return $t;
}

/* むかしの関数名でも動くようにしておきます（取り込み画面から呼ばれます） */
function ymkrf_works_title_from_terms( $post_id ) {
	return ymkrf_works_auto_title( $post_id );
}

add_action( 'save_post_ymkrf_works', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;

	$p = get_post( $post_id );
	if ( ! $p || $p->post_status === 'auto-draft' ) return;

	$auto = ymkrf_works_auto_title( $post_id );
	$prev = (string) get_post_meta( $post_id, '_ymkrf_auto_title', true );
	$now  = (string) $p->post_title;

	/* 手で書きかえた題名は、そのままにします。
	   （前に自動で入れた題名と、いまの題名がちがう＝手直しされた、と見ます） */
	$hand = ( $now !== '' && $prev !== '' && $now !== $prev );

	/* まだ一度も自動で入れていない記事のうち、
	   取り込んだ直後の題名や、前の書き方のものは作り直します */
	if ( $prev === '' && $now !== '' && ! preg_match( '/様[（(]|の施工事例/u', $now ) ) {
		$hand = true;
	}

	update_post_meta( $post_id, '_ymkrf_auto_title', $auto );

	if ( $hand || $now === $auto ) return;

	remove_action( 'save_post_ymkrf_works', __FUNCTION__, 25 );
	wp_update_post( array( 'ID' => $post_id, 'post_title' => $auto ) );
}, 25 );

/* すでにある施工事例の題名を、新しい書き方に1回だけそろえます
   （数字を上げると、もう一度だけ走ります） */
add_action( 'admin_init', function () {
	if ( get_option( 'ymkrf_works_title_ver' ) === '3' ) return;
	if ( ! post_type_exists( 'ymkrf_works' ) ) return;

	$ids = get_posts( array(
		'post_type' => 'ymkrf_works', 'posts_per_page' => -1,
		'fields' => 'ids', 'post_status' => 'any',
	) );

	foreach ( (array) $ids as $id ) {
		$now = (string) get_post_field( 'post_title', $id );
		/* 手で書いた題名は、さわりません */
		if ( $now !== '' && ! preg_match( '/様[（(]|の施工事例/u', $now ) ) continue;

		$auto = ymkrf_works_auto_title( $id );
		update_post_meta( $id, '_ymkrf_auto_title', $auto );
		if ( $auto !== $now ) wp_update_post( array( 'ID' => $id, 'post_title' => $auto ) );
	}

	update_option( 'ymkrf_works_title_ver', '3' );
} );


/** URLの後半（＝案件番号）。番号が無いときは w-123 のようにします */
function ymkrf_works_make_slug( $post_id ) {
	$no = strtolower( preg_replace( '/[^0-9A-Za-z-]/', '',
		(string) get_post_meta( $post_id, '_ymkrf_case_no', true ) ) );
	return $no !== '' ? $no : 'w-' . (int) $post_id;
}

/** 保存のたびに、URLの後半を案件番号にそろえます */
add_action( 'save_post_ymkrf_works', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;

	$p = get_post( $post_id );
	if ( ! $p || $p->post_status === 'auto-draft' ) return;

	$want = ymkrf_works_make_slug( $post_id );
	$now  = rawurldecode( (string) $p->post_name );
	if ( preg_match( '/^' . preg_quote( $want, '/' ) . '(-[0-9]+)?$/', $now ) ) return;

	remove_action( 'save_post_ymkrf_works', __FUNCTION__, 30 );
	wp_update_post( array(
		'ID'        => $post_id,
		'post_name' => wp_unique_post_slug( $want, $post_id, $p->post_status, $p->post_type, 0 ),
	) );
}, 30 );

/* 一覧に出す件数（3列ならびに合わせて12件ずつ） */
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) return;
	if ( ! $q->is_post_type_archive( 'ymkrf_works' )
	  && ! $q->is_tax( array( 'ymkrf_works_cat', 'ymkrf_works_area' ) ) ) return;
	$q->set( 'posts_per_page', 12 );
} );

/* 部位・エリアの一覧も、施工事例の一覧と同じ見た目にします */
add_filter( 'template_include', function ( $tpl ) {
	if ( ! is_tax( array( 'ymkrf_works_cat', 'ymkrf_works_area' ) ) ) return $tpl;
	$found = locate_template( 'archive-ymkrf_works.php' );
	return $found ? $found : $tpl;
}, 20 );


/* ============================================================
   3. 表示に使う関数
   ============================================================ */

/* ------------------------------------------------------------
   3-a. 写真の白い余白を切り落とす

   お客様から届く写真には、左右（または上下）に白い帯が
   焼き込まれているものがあります。そのままだと見くらべスライダーに
   白い帯が出てしまうので、表示用に切り落としたものを作ります。

   ・元の写真はさわりません（-trim を付けた別ファイルを作ります）
   ・1回作ったら、その写真の情報として覚えておきます
   ・切る量がわずか（3%未満）なら、何もしません
   ------------------------------------------------------------ */

/** 切り落とし後のURL。作れないときは、もとのURLを返します */
function ymkrf_works_trim_url( $att_id, $size = 'large' ) {

	$att_id = (int) $att_id;
	$src    = wp_get_attachment_image_url( $att_id, $size );
	if ( ! $src ) return '';

	$done = get_post_meta( $att_id, '_ymkrf_trim', true );

	/* 'no' ＝ 切るところが無かった写真。もう調べません */
	if ( $done === 'no' ) return $src;
	if ( is_string( $done ) && $done !== '' && $done !== 'no' ) {
		$up = wp_get_upload_dir();
		if ( file_exists( trailingslashit( $up['basedir'] ) . $done ) ) {
			return trailingslashit( $up['baseurl'] ) . $done;
		}
	}

	$made = ymkrf_works_make_trim( $att_id );
	if ( $made === '' ) return $src;

	$up = wp_get_upload_dir();
	return trailingslashit( $up['baseurl'] ) . $made;
}

/** 実際に切り落としたファイルを作ります。作れたら uploads からの相対パスを返します */
function ymkrf_works_make_trim( $att_id ) {

	if ( ! function_exists( 'imagecreatefromjpeg' ) ) {
		update_post_meta( $att_id, '_ymkrf_trim', 'no' );
		return '';
	}

	$file = get_attached_file( $att_id );
	if ( ! $file || ! file_exists( $file ) ) return '';

	/* 原寸だとファイルが大きすぎるので、large の写真を切ります */
	$meta = wp_get_attachment_metadata( $att_id );
	if ( ! empty( $meta['sizes']['large']['file'] ) ) {
		$try = trailingslashit( dirname( $file ) ) . $meta['sizes']['large']['file'];
		if ( file_exists( $try ) ) $file = $try;
	}

	$info = @getimagesize( $file );
	if ( ! $info ) { update_post_meta( $att_id, '_ymkrf_trim', 'no' ); return ''; }

	switch ( $info[2] ) {
		case IMAGETYPE_JPEG: $im = @imagecreatefromjpeg( $file ); break;
		case IMAGETYPE_PNG:  $im = @imagecreatefrompng( $file );  break;
		case IMAGETYPE_WEBP: $im = function_exists( 'imagecreatefromwebp' ) ? @imagecreatefromwebp( $file ) : null; break;
		default: $im = null;
	}
	if ( ! $im ) { update_post_meta( $att_id, '_ymkrf_trim', 'no' ); return ''; }

	$w = imagesx( $im );
	$h = imagesy( $im );

	/* 白いかどうかの目安。まっ白でなくても、うすい色なら余白とみなします */
	$is_white = function ( $c ) {
		return ( $c >> 16 & 0xFF ) > 243 && ( $c >> 8 & 0xFF ) > 243 && ( $c & 0xFF ) > 243;
	};

	/* 1本の縦線・横線が、ぜんぶ白かどうかを見ます（間引いて速くしています） */
	$step_y = max( 1, (int) floor( $h / 60 ) );
	$step_x = max( 1, (int) floor( $w / 60 ) );

	$col_white = function ( $x ) use ( $im, $h, $step_y, $is_white ) {
		for ( $y = 0; $y < $h; $y += $step_y ) {
			if ( ! $is_white( imagecolorat( $im, $x, $y ) ) ) return false;
		}
		return true;
	};
	$row_white = function ( $y ) use ( $im, $w, $step_x, $is_white ) {
		for ( $x = 0; $x < $w; $x += $step_x ) {
			if ( ! $is_white( imagecolorat( $im, $x, $y ) ) ) return false;
		}
		return true;
	};

	$l = 0;        while ( $l < $w - 1 && $col_white( $l ) )      $l++;
	$r = $w - 1;   while ( $r > $l     && $col_white( $r ) )      $r--;
	$t = 0;        while ( $t < $h - 1 && $row_white( $t ) )      $t++;
	$b = $h - 1;   while ( $b > $t     && $row_white( $b ) )      $b--;

	$nw = $r - $l + 1;
	$nh = $b - $t + 1;

	/* 切る量がわずかなとき・切りすぎのときは、何もしません */
	if ( ( $nw >= $w * 0.97 && $nh >= $h * 0.97 )
	  || $nw < $w * 0.2 || $nh < $h * 0.2 ) {
		imagedestroy( $im );
		update_post_meta( $att_id, '_ymkrf_trim', 'no' );
		update_post_meta( $att_id, '_ymkrf_trim_wh', $w . ',' . $h );
		return '';
	}

	$new = imagecreatetruecolor( $nw, $nh );
	imagecopy( $new, $im, 0, 0, $l, $t, $nw, $nh );
	imagedestroy( $im );

	$up   = wp_get_upload_dir();
	$rel  = _wp_relative_upload_path( $file );
	$dir  = dirname( $rel );
	$base = pathinfo( $rel, PATHINFO_FILENAME );
	$out  = ( $dir === '.' ? '' : $dir . '/' ) . $base . '-ymkrftrim.jpg';
	$path = trailingslashit( $up['basedir'] ) . $out;

	wp_mkdir_p( dirname( $path ) );
	$ok = imagejpeg( $new, $path, 88 );
	imagedestroy( $new );

	if ( ! $ok ) { update_post_meta( $att_id, '_ymkrf_trim', 'no' ); return ''; }

	update_post_meta( $att_id, '_ymkrf_trim', $out );
	update_post_meta( $att_id, '_ymkrf_trim_wh', $nw . ',' . $nh );
	return $out;
}

/** 切り落としたあとの、たて・よこの比（よこ÷たて） */
function ymkrf_works_photo_ar( $att_id ) {
	ymkrf_works_trim_url( $att_id, 'large' );   /* まだなら、ここで作られます */

	$wh = (string) get_post_meta( $att_id, '_ymkrf_trim_wh', true );

	/* まだ覚えていないときは、切り落としたファイルから測って覚えます */
	if ( $wh === '' ) {
		$done = (string) get_post_meta( $att_id, '_ymkrf_trim', true );
		if ( $done !== '' && $done !== 'no' ) {
			$up = wp_get_upload_dir();
			$sz = @getimagesize( trailingslashit( $up['basedir'] ) . $done );
			if ( $sz && $sz[0] > 0 && $sz[1] > 0 ) {
				$wh = $sz[0] . ',' . $sz[1];
				update_post_meta( $att_id, '_ymkrf_trim_wh', $wh );
			}
		}
	}

	if ( $wh !== '' && strpos( $wh, ',' ) !== false ) {
		list( $w, $h ) = array_map( 'intval', explode( ',', $wh ) );
		if ( $w > 0 && $h > 0 ) return $w / $h;
	}

	$m = wp_get_attachment_metadata( $att_id );
	if ( ! empty( $m['width'] ) && ! empty( $m['height'] ) ) {
		return (float) $m['width'] / (float) $m['height'];
	}
	return 1.6;
}

/** 見くらべスライダーに出す1枚 */
function ymkrf_works_compare_img( $att_id, $which, $load ) {
	$url = ymkrf_works_trim_url( $att_id, 'large' );
	if ( $url === '' ) return '';
	$alt = ( $which === 'before' ) ? '施工前' : '施工後';
	return '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '"'
	     . ' loading="' . esc_attr( $load ) . '" decoding="async">';
}

/** Before / After の見くらべ。Before写真が無いときは、写真1枚だけ出します */
function ymkrf_works_compare( $post_id, $eager = false ) {
	$bs = ymkrf_works_photos( $post_id, 'before' );
	$as = ymkrf_works_photos( $post_id, 'after' );
	$before_id = $bs ? (int) $bs[0] : 0;
	$after_id  = $as ? (int) $as[0] : (int) get_post_thumbnail_id( $post_id );
	$load      = $eager ? 'eager' : 'lazy';

	if ( ! $after_id && ! $before_id ) return '';

	/* 片方しか無いときは、ふつうの写真として出します */
	if ( ! $after_id || ! $before_id ) {
		$id  = $after_id ? $after_id : $before_id;
		$url = ymkrf_works_trim_url( $id, 'large' );
		return '<div class="p-work__solo"><img src="' . esc_url( $url ) . '" alt=""'
		     . ' loading="' . esc_attr( $load ) . '" decoding="async"></div>';
	}

	/* 枠の形は、2枚の写真の平均に合わせます。
	   こうすると、切り取られて大きく写りすぎるのを防げます。 */
	$ar = ( ymkrf_works_photo_ar( $before_id ) + ymkrf_works_photo_ar( $after_id ) ) / 2;
	$ar = max( 0.8, min( 1.9, $ar ) );

	$h  = '<div class="p-compare" data-compare style="--pos:50%;--cmp-ar:' . esc_attr( round( $ar, 3 ) ) . '">';
	$h .= '<div class="p-compare__layer p-compare__layer--before">'
	    . ymkrf_works_compare_img( $before_id, 'before', $load ) . '</div>';
	$h .= '<div class="p-compare__layer p-compare__layer--after">'
	    . ymkrf_works_compare_img( $after_id, 'after', $load ) . '</div>';
	$h .= '<span class="p-compare__tag p-compare__tag--before">BEFORE</span>';
	$h .= '<span class="p-compare__tag p-compare__tag--after">AFTER</span>';
	$h .= '<span class="p-compare__handle"></span>';
	$h .= '<span class="p-compare__hint">← 左右に動かして見くらべる →</span>';
	$h .= '</div>';
	return $h;
}

/** 部位・エリアの名前を配列で返します */
function ymkrf_works_term_names( $post_id, $tax ) {
	$ts = get_the_terms( $post_id, $tax );
	if ( ! $ts || is_wp_error( $ts ) ) return array();

	/* 部位は、決めた順（キッチン → お風呂 → …）にそろえます */
	if ( $tax === 'ymkrf_works_cat' ) {
		$order = array_keys( ymkrf_works_parts_master() );
		usort( $ts, function ( $a, $b ) use ( $order ) {
			$ia = array_search( $a->slug, $order, true ); if ( $ia === false ) $ia = 900;
			$ib = array_search( $b->slug, $order, true ); if ( $ib === false ) $ib = 900;
			if ( $ia === $ib ) return strcmp( $a->name, $b->name );
			return ( $ia < $ib ) ? -1 : 1;
		} );
	}

	return wp_list_pluck( $ts, 'name' );
}

/**
 * 見くらべに使った1枚目より後ろの写真を、小さく並べます。
 * 見くらべスライダーのすぐ下に置きます。押すと大きく開きます。
 * 並びは Before2枚目以降 → 施工中 → After2枚目以降 です。
 */
function ymkrf_works_gallery( $post_id ) {

	$sets = array(
		array( 'before', 'BEFORE', '前', 'before', 1 ),
		array( 'during', '施工中',  '中', 'during', 0 ),
		array( 'after',  'AFTER',  '後', 'after',  1 ),
	);

	$items = array();
	foreach ( $sets as $set ) {
		list( $which, $cap, $mark, $mod, $skip ) = $set;
		$ids = array_slice( ymkrf_works_photos( $post_id, $which ), $skip );
		foreach ( $ids as $id ) $items[] = array( $id, $cap, $mark, $mod );
	}
	if ( ! $items ) return '';

	$h = '<div class="p-work__thumbs">';
	foreach ( $items as $it ) {
		list( $id, $cap, $mark, $mod ) = $it;
		$full = wp_get_attachment_image_url( $id, 'full' );
		$h .= '<a class="p-work__thumb2 js-lightbox" href="' . esc_url( (string) $full ) . '"'
		    . ' data-caption="' . esc_attr( $cap . 'の写真' ) . '"'
		    . ' aria-label="' . esc_attr( $cap . 'の写真を大きく見る' ) . '">'
		    . wp_get_attachment_image( $id, 'thumbnail', false, array(
		        'loading' => 'lazy', 'decoding' => 'async', 'alt' => '' ) )
		    . '<span class="p-work__tmark p-work__tmark--' . esc_attr( $mod ) . '">' . esc_html( $mark ) . '</span>'
		    . '</a>';
	}
	return $h . '</div>';
}

/* ------------------------------------------------------------
   3-b. おこなった工事（1行に1つ）
        以前は本文の箇条書きで作っていましたが、
        営業の方が入れやすいように、専用の入力欄にしました。
   ------------------------------------------------------------ */

/** 入力された文字を、1行1つに整えます */
function ymkrf_works_items_clean( $text ) {
	$t = str_replace( array( "\r\n", "\r" ), "\n", (string) $text );
	$out = array();
	foreach ( explode( "\n", $t ) as $line ) {
		/* 行頭の ・ ※ - * 1. などは、あってもなくても良いようにします */
		$line = preg_replace( '/^[\s\x{3000}]*[・･\-\*※•◦o○●]?[\s\x{3000}]*/u', '', $line );
		$line = preg_replace( '/^[0-9０-９]+[\.．、)）]\s*/u', '', $line );
		$line = trim( sanitize_text_field( $line ) );
		if ( $line !== '' ) $out[] = $line;
	}
	return implode( "\n", $out );
}

/** おこなった工事（配列） */
function ymkrf_works_items( $post_id ) {
	$v = (string) get_post_meta( $post_id, '_ymkrf_work_items', true );
	if ( trim( $v ) === '' ) return array();
	return array_values( array_filter( array_map( 'trim', explode( "\n", $v ) ) ) );
}

/** 入力欄に出す文字。まだ入れていない記事は、本文の箇条書きから拾います */
function ymkrf_works_items_text( $post_id ) {
	$v = (string) get_post_meta( $post_id, '_ymkrf_work_items', true );
	if ( trim( $v ) !== '' ) return $v;

	$body = (string) get_post_field( 'post_content', $post_id );
	return implode( "\n", ymkrf_works_items_from_html( $body ) );
}

/** 本文のHTMLから <li> を取り出します（引っ越し用） */
function ymkrf_works_items_from_html( $html ) {
	$out = array();
	if ( preg_match_all( '#<li[^>]*>(.*?)</li>#isu', (string) $html, $m ) ) {
		foreach ( $m[1] as $li ) {
			$t = trim( wp_strip_all_tags( $li ) );
			$t = trim( preg_replace( '/\s+/u', ' ', $t ) );
			if ( $t !== '' ) $out[] = $t;
		}
	}
	return $out;
}

/** ページに出す箇条書き */
function ymkrf_works_items_html( $post_id ) {
	$items = ymkrf_works_items( $post_id );
	if ( ! $items ) return '';
	$h = '<ul class="p-work__list">';
	foreach ( $items as $it ) $h .= '<li>' . esc_html( $it ) . '</li>';
	return $h . '</ul>';
}

/* すでにある記事の本文の箇条書きを、入力欄のほうへ1回だけ引っ越します
   （数字を上げると、もう一度だけ走ります） */
add_action( 'admin_init', function () {
	if ( get_option( 'ymkrf_works_items_ver' ) === '2' ) return;
	if ( ! post_type_exists( 'ymkrf_works' ) ) return;

	$ids = get_posts( array(
		'post_type' => 'ymkrf_works', 'posts_per_page' => -1,
		'fields' => 'ids', 'post_status' => 'any',
	) );

	foreach ( (array) $ids as $id ) {

		$body = (string) get_post_field( 'post_content', $id );

		if ( trim( (string) get_post_meta( $id, '_ymkrf_work_items', true ) ) === '' ) {
			$items = ymkrf_works_items_from_html( $body );
			if ( $items ) update_post_meta( $id, '_ymkrf_work_items', implode( "\n", $items ) );
		}

		/* 本文からは箇条書きだけを取りのぞきます（写真や文章は残します） */
		$rest = preg_replace( '#<(ul|ol)[^>]*>.*?</\1>#isu', '', $body );
		/* ブロックエディターの目じるし（<!-- wp:list --> など）も消します */
		$rest = preg_replace( '#<!--\s*/?wp:list[^>]*-->#isu', '', (string) $rest );
		$rest = trim( preg_replace( '/(\R\s*){3,}/u', "\n\n", (string) $rest ) );

		if ( $rest !== $body ) {
			wp_update_post( array( 'ID' => $id, 'post_content' => $rest ) );
		}
	}

	update_option( 'ymkrf_works_items_ver', '2' );
} );


/** 一覧に出す短い説明 */
function ymkrf_works_excerpt( $post_id, $len = 80 ) {
	$p = get_post( $post_id );
	if ( ! $p ) return '';
	$t = $p->post_excerpt !== '' ? $p->post_excerpt : wp_strip_all_tags( $p->post_content );
	if ( trim( $t ) === '' ) $t = implode( '／', ymkrf_works_items( $post_id ) );
	$t = trim( preg_replace( '/\s+/u', ' ', $t ) );
	return mb_strimwidth( $t, 0, $len * 2, '…', 'UTF-8' );
}

/** 同じ案件番号のお客様の声（公開ぶんだけ） */
function ymkrf_works_linked_voices( $post_id ) {
	$no = trim( (string) get_post_meta( $post_id, '_ymkrf_case_no', true ) );
	if ( $no === '' ) return array();
	return get_posts( array(
		'post_type'      => 'ymkrf_voice',
		'posts_per_page' => 5,
		'post_status'    => 'publish',
		'meta_query'     => array( array( 'key' => '_ymkrf_case_no', 'value' => $no ) ),
	) );
}

/** ほかの施工事例（同じ部位 → 同じ店舗 → 新しい順） */
function ymkrf_works_related( $post_id, $num = 3 ) {
	$cats = wp_get_post_terms( $post_id, 'ymkrf_works_cat', array( 'fields' => 'ids' ) );
	$base = array(
		'post_type'      => 'ymkrf_works',
		'posts_per_page' => $num,
		'post_status'    => 'publish',
		'post__not_in'   => array( $post_id ),
		'orderby'        => 'date',
	);

	$out = array();
	if ( $cats && ! is_wp_error( $cats ) ) {
		$out = get_posts( $base + array( 'tax_query' => array( array(
			'taxonomy' => 'ymkrf_works_cat', 'field' => 'term_id', 'terms' => $cats,
		) ) ) );
	}
	if ( count( $out ) < $num ) {
		$skip = array_merge( array( $post_id ), wp_list_pluck( $out, 'ID' ) );
		$add  = get_posts( array(
			'post_type' => 'ymkrf_works', 'posts_per_page' => $num - count( $out ),
			'post_status' => 'publish', 'post__not_in' => $skip, 'orderby' => 'date',
		) );
		$out = array_merge( $out, $add );
	}
	return $out;
}


/* ============================================================
   4. 管理画面の一覧

   「お客様の声」の一覧と同じ並びにそろえています。

     タイトル／案件番号／施工店舗／お客様／リフォーム箇所／お客様の声／日付

   上のしぼり込みは、日付のかわりに
   「リフォーム箇所」と「施工店舗」を出しています。
   ============================================================ */

/* 列の並び。
   inc/functions-voice.php で 案件番号 と お客様の声 が足されたあとに
   組み立て直すので、順番は 20（あと）にしています。 */
add_filter( 'manage_ymkrf_works_posts_columns', function ( $cols ) {
	$new = array();
	if ( isset( $cols['cb'] ) )    $new['cb']    = $cols['cb'];
	if ( isset( $cols['title'] ) ) $new['title'] = $cols['title'];

	$new['ymkrf_case']  = isset( $cols['ymkrf_case'] ) ? $cols['ymkrf_case'] : '案件番号';
	$new['ymkrf_wshop'] = '施工店舗';
	$new['ymkrf_wstaff'] = '担当';
	$new['ymkrf_wpart'] = 'リフォーム箇所';
	$new['ymkrf_voice'] = isset( $cols['ymkrf_voice'] ) ? $cols['ymkrf_voice'] : 'お客様の声';

	/* 残り（日付など）は、そのうしろに置きます */
	foreach ( $cols as $k => $v ) {
		if ( ! isset( $new[ $k ] ) ) $new[ $k ] = $v;
	}
	return $new;
}, 20 );

add_action( 'manage_ymkrf_works_posts_custom_column', function ( $col, $post_id ) {
	$none = '<span style="color:#a7aaad">—</span>';

	switch ( $col ) {
		case 'ymkrf_wshop':
			$v = ymkrf_works_shop_name( $post_id );
			echo $v ? esc_html( $v ) : $none;
			break;
		case 'ymkrf_wstaff':
			echo ymkrf_staff_admin_cell( (int) get_post_meta( $post_id, '_ymkrf_staff', true ) );
			break;

		case 'ymkrf_wpart':
			$ts = get_the_terms( $post_id, 'ymkrf_works_cat' );
			if ( ! $ts || is_wp_error( $ts ) ) { echo $none; break; }
			$out = array();
			foreach ( $ts as $t ) {
				$out[] = '<a href="' . esc_url( add_query_arg( array(
					'post_type'      => 'ymkrf_works',
					'ymkrf_works_cat' => $t->slug,
				), admin_url( 'edit.php' ) ) ) . '">' . esc_html( $t->name ) . '</a>';
			}
			echo implode( '／', $out );
			break;
	}
}, 10, 2 );

/* 見出しを押すと並べかえできる列（施工店舗） */
add_filter( 'manage_edit-ymkrf_works_sortable_columns', function ( $cols ) {
	$cols['ymkrf_wshop'] = 'ymkrf_wshop';
	return $cols;
}, 20 );

add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() || ! $q->is_main_query() ) return;
	if ( $q->get( 'post_type' ) !== 'ymkrf_works' ) return;

	if ( $q->get( 'orderby' ) === 'ymkrf_wshop' ) {
		$q->set( 'meta_key', '_ymkrf_shop' );
		$q->set( 'orderby', 'meta_value' );
	}
} );

/* 「すべての日付」のプルダウンは使わないので消します
   （かわりに リフォーム箇所 と 施工店舗 を出します） */
add_filter( 'disable_months_dropdown', function ( $disable, $post_type ) {
	return ( $post_type === 'ymkrf_works' ) ? true : $disable;
}, 10, 2 );

add_action( 'restrict_manage_posts', function ( $post_type ) {
	if ( $post_type !== 'ymkrf_works' ) return;

	/* リフォーム箇所（トップページのリフォームメニューの順に出します） */
	$now   = isset( $_GET['ymkrf_works_cat'] ) ? sanitize_title( wp_unslash( $_GET['ymkrf_works_cat'] ) ) : '';
	$terms = get_terms( array( 'taxonomy' => 'ymkrf_works_cat', 'hide_empty' => false ) );
	if ( $terms && ! is_wp_error( $terms ) ) {
		$by = array();
		foreach ( $terms as $t ) $by[ $t->slug ] = $t;

		$sorted = array();
		foreach ( array_keys( ymkrf_works_parts_master() ) as $slug ) {
			if ( isset( $by[ $slug ] ) ) { $sorted[] = $by[ $slug ]; unset( $by[ $slug ] ); }
		}
		foreach ( $by as $t ) $sorted[] = $t;

		echo '<select name="ymkrf_works_cat"><option value="">すべてのリフォーム箇所</option>';
		foreach ( $sorted as $t ) {
			echo '<option value="' . esc_attr( $t->slug ) . '" ' . selected( $now, $t->slug, false ) . '>'
			   . esc_html( $t->name ) . '（' . (int) $t->count . '）</option>';
		}
		echo '</select>';
	}

	/* 施工店舗 */
	$snow  = isset( $_GET['ymkrf_shop_filter'] ) ? sanitize_title( wp_unslash( $_GET['ymkrf_shop_filter'] ) ) : '';
	$shops = get_terms( array( 'taxonomy' => 'ymkrf_shop', 'hide_empty' => false ) );
	if ( $shops && ! is_wp_error( $shops ) ) {
		echo '<select name="ymkrf_shop_filter"><option value="">すべての施工店舗</option>';
		foreach ( $shops as $sh ) {
			echo '<option value="' . esc_attr( $sh->slug ) . '" ' . selected( $snow, $sh->slug, false ) . '>'
			   . esc_html( $sh->name ) . '</option>';
		}
		echo '</select>';
	}
} );

/* 施工店舗のしぼり込み（リフォーム箇所は分類なので、WordPressがそのまま効かせます） */
add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() || ! $q->is_main_query() ) return;
	if ( $q->get( 'post_type' ) !== 'ymkrf_works' ) return;
	if ( empty( $_GET['ymkrf_shop_filter'] ) ) return;

	$q->set( 'meta_query', array( array(
		'key'   => '_ymkrf_shop',
		'value' => sanitize_title( wp_unslash( $_GET['ymkrf_shop_filter'] ) ),
	) ) );
} );

add_action( 'admin_head', function () {
	$s = get_current_screen();
	if ( ! $s || $s->id !== 'edit-ymkrf_works' ) return;
	echo '<style>
	  .column-ymkrf_case{width:100px}
	  .column-ymkrf_wshop{width:110px}
	  .column-ymkrf_wstaff{width:130px}
	  .column-ymkrf_wpart{width:180px}
	  .column-ymkrf_voice{width:110px}
	  .column-date{width:130px}
	</style>';
} );


/* ============================================================
   5. 権限（だれが施工事例をさわれるか）

   「施工事例スタッフ」という役割を作ります。
   この役割の人にできるのは、次のことだけです。

     ・施工事例の追加・編集・公開
     ・写真のアップロード
     ・自分の profile の変更

   商品・お客様の声・コラム・固定ページ・設定・プラグインなど、
   ほかのものは画面にも出ません。

   ── 使いかた ─────────────────────────────
   ユーザー → 新規追加 → 権限グループで「施工事例スタッフ」をえらぶ。
   すでにいる人は、ユーザー一覧から権限グループを変えられます。
   ─────────────────────────────────────
   ============================================================ */

/** 施工事例まわりの権限の一覧 */
function ymkrf_works_caps() {
	return array(
		'edit_ymkrf_work',
		'read_ymkrf_work',
		'delete_ymkrf_work',
		'create_ymkrf_works',
		'edit_ymkrf_works',
		'edit_others_ymkrf_works',
		'edit_published_ymkrf_works',
		'edit_private_ymkrf_works',
		'publish_ymkrf_works',
		'read_private_ymkrf_works',
		'delete_ymkrf_works',
		'delete_others_ymkrf_works',
		'delete_published_ymkrf_works',
		'delete_private_ymkrf_works',
		'manage_ymkrf_works_terms',
	);
}

/* 役割を1回だけ作ります（数字を上げると作り直します） */
add_action( 'init', function () {
	if ( get_option( 'ymkrf_works_role_ver' ) === '2' ) return;

	remove_role( 'ymkrf_works_staff' );
	add_role( 'ymkrf_works_staff', '施工事例スタッフ', array(
		'read'                       => true,
		'upload_files'               => true,
		'edit_ymkrf_works'           => true,
		'edit_others_ymkrf_works'    => true,
		'edit_published_ymkrf_works' => true,
		'publish_ymkrf_works'        => true,
		'read_private_ymkrf_works'   => true,
		'delete_ymkrf_works'         => true,
		'delete_published_ymkrf_works' => true,
		'create_ymkrf_works'         => true,
		/* 部位・エリアは「えらぶ」だけ。新しく作ったり消したりはできません。
		   ほかの人が作った事例を「消す」こともできません（直すのはOK）。 */
	) );

	update_option( 'ymkrf_works_role_ver', '2' );
}, 30 );

/**
 * 管理者（サイト全体をさわれる人）には、施工事例の権限をすべて渡します。
 * 役割の登録し忘れで、管理者が施工事例を編集できなくなるのを防ぐためです。
 */
add_filter( 'user_has_cap', function ( $allcaps ) {
	if ( empty( $allcaps['manage_options'] ) ) return $allcaps;
	foreach ( ymkrf_works_caps() as $c ) $allcaps[ $c ] = true;
	return $allcaps;
}, 10, 1 );

/* 施工事例スタッフには、管理バーの余計な項目を出しません */
add_action( 'admin_bar_menu', function ( $bar ) {
	if ( ! is_user_logged_in() ) return;
	$u = wp_get_current_user();
	if ( ! in_array( 'ymkrf_works_staff', (array) $u->roles, true ) ) return;
	$bar->remove_node( 'comments' );
	$bar->remove_node( 'new-content' );
	$bar->remove_node( 'wp-logo' );
}, 999 );

/* ログインしたら、施工事例の一覧を最初に出します */
add_filter( 'login_redirect', function ( $to, $req, $user ) {
	if ( is_wp_error( $user ) || ! isset( $user->roles ) ) return $to;
	if ( ! in_array( 'ymkrf_works_staff', (array) $user->roles, true ) ) return $to;
	return admin_url( 'edit.php?post_type=ymkrf_works' );
}, 10, 3 );


/* ============================================================
   部位とエリアを「両方いっぺんに」しぼり込む
   ============================================================
   URLの形
     /works/                         … ぜんぶ
     /works/kitchen/                 … 部位だけ
     /works-area/kanazawa/           … エリアだけ
     /works/kitchen/?area=kanazawa   … 両方
   両方えらんだときは、部位をURLの道に、エリアを ?area= に置きます。
   （どちらか一方だけのURLは、今までどおりです）
   ============================================================ */

/** いまえらばれている部位（英字）。無ければ空 */
if ( ! function_exists( 'ymkrf_works_sel_cat' ) ) :
function ymkrf_works_sel_cat() {
	if ( is_tax( 'ymkrf_works_cat' ) ) {
		$t = get_queried_object();
		if ( $t && ! is_wp_error( $t ) ) return $t->slug;
	}
	if ( isset( $_GET['cat'] ) ) {
		$s = sanitize_title( wp_unslash( $_GET['cat'] ) );
		if ( $s !== '' && term_exists( $s, 'ymkrf_works_cat' ) ) return $s;
	}
	return '';
}
endif;

/** いまえらばれているエリア（英字）。無ければ空 */
if ( ! function_exists( 'ymkrf_works_sel_area' ) ) :
function ymkrf_works_sel_area() {
	if ( is_tax( 'ymkrf_works_area' ) ) {
		$t = get_queried_object();
		if ( $t && ! is_wp_error( $t ) ) return $t->slug;
	}
	if ( isset( $_GET['area'] ) ) {
		$s = sanitize_title( wp_unslash( $_GET['area'] ) );
		if ( $s !== '' && term_exists( $s, 'ymkrf_works_area' ) ) return $s;
	}
	return '';
}
endif;

/** 部位とエリアの組み合わせから、一覧のURLを作ります */
if ( ! function_exists( 'ymkrf_works_url' ) ) :
function ymkrf_works_url( $cat = '', $area = '' ) {
	$cat  = (string) $cat;
	$area = (string) $area;

	if ( $cat !== '' ) {
		$t = get_term_by( 'slug', $cat, 'ymkrf_works_cat' );
		$u = ( $t && ! is_wp_error( $t ) ) ? get_term_link( $t ) : get_post_type_archive_link( 'ymkrf_works' );
		if ( is_wp_error( $u ) ) $u = get_post_type_archive_link( 'ymkrf_works' );
		return $area !== '' ? add_query_arg( 'area', $area, $u ) : $u;
	}
	if ( $area !== '' ) {
		$t = get_term_by( 'slug', $area, 'ymkrf_works_area' );
		$u = ( $t && ! is_wp_error( $t ) ) ? get_term_link( $t ) : get_post_type_archive_link( 'ymkrf_works' );
		if ( is_wp_error( $u ) ) $u = get_post_type_archive_link( 'ymkrf_works' );
		return $u;
	}
	return get_post_type_archive_link( 'ymkrf_works' );
}
endif;

/** しぼり込みに当てはまる記事の番号 */
if ( ! function_exists( 'ymkrf_works_filter_ids' ) ) :
function ymkrf_works_filter_ids( $cat = '', $area = '' ) {
	$args = array(
		'post_type'      => 'ymkrf_works',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	);
	$tq = array( 'relation' => 'AND' );
	if ( $cat  !== '' ) $tq[] = array( 'taxonomy' => 'ymkrf_works_cat',  'field' => 'slug', 'terms' => $cat );
	if ( $area !== '' ) $tq[] = array( 'taxonomy' => 'ymkrf_works_area', 'field' => 'slug', 'terms' => $area );
	if ( count( $tq ) > 1 ) $args['tax_query'] = $tq;
	return get_posts( $args );
}
endif;

/** ボタンに出す件数。もう一方のしぼり込みを効かせた数を返します
 *  返るのは array( スラッグ => 件数 ) */
if ( ! function_exists( 'ymkrf_works_term_counts' ) ) :
function ymkrf_works_term_counts( $taxonomy, $cat = '', $area = '' ) {
	$ids = ymkrf_works_filter_ids( $cat, $area );
	$out = array();
	if ( ! $ids ) return $out;
	$terms = wp_get_object_terms( $ids, $taxonomy, array( 'fields' => 'all_with_object_id' ) );
	if ( is_wp_error( $terms ) ) return $out;
	foreach ( $terms as $t ) {
		if ( ! isset( $out[ $t->slug ] ) ) $out[ $t->slug ] = 0;
		$out[ $t->slug ]++;
	}
	return $out;
}
endif;

/* 一覧のしぼり込みに、もう一方の条件を足します */
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) return;
	if ( ! ( $q->is_post_type_archive( 'ymkrf_works' )
	      || $q->is_tax( 'ymkrf_works_cat' ) || $q->is_tax( 'ymkrf_works_area' ) ) ) return;

	$cat  = isset( $_GET['cat'] )  ? sanitize_title( wp_unslash( $_GET['cat'] ) )  : '';
	$area = isset( $_GET['area'] ) ? sanitize_title( wp_unslash( $_GET['area'] ) ) : '';

	$add = array();
	if ( $cat !== '' && ! $q->is_tax( 'ymkrf_works_cat' ) && term_exists( $cat, 'ymkrf_works_cat' ) ) {
		$add[] = array( 'taxonomy' => 'ymkrf_works_cat', 'field' => 'slug', 'terms' => $cat );
	}
	if ( $area !== '' && ! $q->is_tax( 'ymkrf_works_area' ) && term_exists( $area, 'ymkrf_works_area' ) ) {
		$add[] = array( 'taxonomy' => 'ymkrf_works_area', 'field' => 'slug', 'terms' => $area );
	}
	if ( ! $add ) return;

	$tq = (array) $q->get( 'tax_query' );
	$tq = array_merge( $tq, $add );
	$tq['relation'] = 'AND';
	$q->set( 'tax_query', $tq );
}, 20 );

/* 部位＋エリアの組み合わせページは、検索エンジンに登録させません。
   組み合わせの数だけ似たページができてしまい、SEO上よくないためです。
   （/works/kitchen/ や /works-area/kanazawa/ の1つだけのページは、今までどおり登録されます） */
add_action( 'wp_head', function () {
	if ( ! ( is_post_type_archive( 'ymkrf_works' ) || is_tax( array( 'ymkrf_works_cat', 'ymkrf_works_area' ) ) ) ) return;
	if ( empty( $_GET['area'] ) && empty( $_GET['cat'] ) ) return;
	echo '<meta name="robots" content="noindex,follow">' . "\n";
}, 1 );

/* 組み合わせているときは、ページの題名にも両方を出します */
add_filter( 'document_title_parts', function ( $parts ) {
	if ( ! ( is_post_type_archive( 'ymkrf_works' ) || is_tax( array( 'ymkrf_works_cat', 'ymkrf_works_area' ) ) ) ) return $parts;
	if ( empty( $_GET['area'] ) && empty( $_GET['cat'] ) ) return $parts;

	$cat  = function_exists( 'ymkrf_works_sel_cat' )  ? ymkrf_works_sel_cat()  : '';
	$area = function_exists( 'ymkrf_works_sel_area' ) ? ymkrf_works_sel_area() : '';
	$ct = $cat  !== '' ? get_term_by( 'slug', $cat,  'ymkrf_works_cat' )  : null;
	$at = $area !== '' ? get_term_by( 'slug', $area, 'ymkrf_works_area' ) : null;
	$cn = ( $ct && ! is_wp_error( $ct ) ) ? $ct->name : '';
	$an = ( $at && ! is_wp_error( $at ) ) ? $at->name : '';
	$label = trim( $an . ( $an !== '' && $cn !== '' ? 'の' : '' ) . $cn );
	if ( $label !== '' ) $parts['title'] = $label . 'の施工事例';
	return $parts;
}, 20 );


/* ============================================================
   取り扱いが終わった商品は、自動で「旧パック商品」にします
   ============================================================
   商品ページを非表示（下書き・ゴミ箱）にすると、その商品を使った
   施工事例からは、商品ページへのリンクが出なくなります。
   そのままだと「何を入れた工事なのか」が分からなくなるので、
   ※こちらはヤマキシ旧パック商品となります に自動でチェックを入れます。

   ★下書きの施工事例は、何もしません。
     登録の途中で、まだ商品をえらんでいないだけのことがあるためです。
   ★いちどチェックが入ったら、自動で外すことはしません。
     手で外した意図を消してしまわないようにするためです。
   ============================================================ */

/** この施工事例に、取り扱いの終わった商品が入っていれば true */
if ( ! function_exists( 'ymkrf_works_has_gone_product' ) ) :
function ymkrf_works_has_gone_product( $post_id ) {
	$v   = (string) get_post_meta( $post_id, '_ymkrf_products', true );
	$ids = array_filter( array_map( 'intval', explode( ',', $v ) ) );
	if ( ! $ids ) return false;
	foreach ( $ids as $id ) {
		$p = get_post( $id );
		if ( ! $p || $p->post_type !== 'ymkrf_product' || $p->post_status !== 'publish' ) return true;
	}
	return false;
}
endif;

/** 公開中の施工事例1件を見て、必要なら旧パックのチェックを入れます */
if ( ! function_exists( 'ymkrf_works_sync_oldpack' ) ) :
function ymkrf_works_sync_oldpack( $post_id ) {
	$post_id = (int) $post_id;
	$p = get_post( $post_id );
	if ( ! $p || $p->post_type !== 'ymkrf_works' ) return;

	/* 下書き・レビュー待ち・ゴミ箱は、そのままにします */
	if ( $p->post_status !== 'publish' ) return;

	if ( get_post_meta( $post_id, '_ymkrf_oldpack', true ) === '1' ) return;   /* すでに入っている */
	if ( ! ymkrf_works_has_gone_product( $post_id ) ) return;

	update_post_meta( $post_id, '_ymkrf_oldpack', '1' );
}
endif;

/* 施工事例を保存したとき */
add_action( 'save_post_ymkrf_works', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	ymkrf_works_sync_oldpack( $post_id );
}, 30 );

/* 施工事例を「公開」にしたとき（下書きのあいだに商品が終わっていた場合） */
add_action( 'transition_post_status', function ( $new, $old, $post ) {
	if ( ! $post || $post->post_type !== 'ymkrf_works' ) return;
	if ( $new !== 'publish' || $old === 'publish' ) return;
	ymkrf_works_sync_oldpack( $post->ID );
}, 10, 3 );

/* 商品を非表示にしたとき、その商品を使っている公開中の施工事例をまとめて直します */
add_action( 'transition_post_status', function ( $new, $old, $post ) {
	if ( ! $post || $post->post_type !== 'ymkrf_product' ) return;
	if ( $old !== 'publish' || $new === 'publish' ) return;   /* 公開 → 非公開 のときだけ */

	$id = (int) $post->ID;
	/* _ymkrf_products は「12,34,56」の形なので、番号が入っていそうなものを広めに拾って、
	   そのあと1件ずつきちんと確かめます */
	$works = get_posts( array(
		'post_type'      => 'ymkrf_works',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_query'     => array( array(
			'key' => '_ymkrf_products', 'value' => (string) $id, 'compare' => 'LIKE',
		) ),
	) );
	foreach ( (array) $works as $w ) {
		$v   = (string) get_post_meta( $w, '_ymkrf_products', true );
		$ids = array_filter( array_map( 'intval', explode( ',', $v ) ) );
		if ( ! in_array( $id, $ids, true ) ) continue;   /* 「1」と「12」の取りちがえを防ぎます */
		ymkrf_works_sync_oldpack( $w );
	}
}, 10, 3 );
