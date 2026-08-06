<?php
/**
 * 商品（ymkrf_product）─ リフォームヤマキシ
 *
 * functions.php から読み込んでください。
 *   require_once get_stylesheet_directory() . '/inc/functions-product.php';
 *
 * ── これで何ができるようになるか ────────────────────────
 * 管理画面に「商品」というメニューが増えます。
 * プログラムの知識がなくても、欄を埋めるだけで商品ページが1枚できます。
 * デザイン・レイアウト・構造化データはテンプレート側で自動なので、
 * 入力する人が気にする必要はありません。
 *
 * キッチン／お風呂／トイレ／洗面化粧台 …は「商品カテゴリ」で分けます。
 * どのカテゴリでも同じ入力画面・同じテンプレートが使えます。
 *
 * ── 追加費用はかかりません ─────────────────────────────
 * 有料プラグイン（ACF Proなど）は使っていません。
 * 繰り返し入力する欄（カラー・取っ手・仕様など）も自前です。
 *
 * ── 項目を増やしたくなったら ────────────────────────────
 * 下の「2. 入力欄の定義」だけを直せば入力画面が変わります。
 * （表示にも出したい場合は single-ymkrf_product.php にも追記が必要です）
 *
 * ── 名前について ───────────────────────────────────
 * サーバーに他サイトが同居しているため、すべて ymkrf 接頭辞付きです。
 * ───────────────────────────────────────────────
 */

if ( ! defined( 'ABSPATH' ) ) exit;


/* ============================================================
   1. 商品という入れ物と、分類をつくる
   ============================================================ */
add_action( 'init', function () {

	register_post_type( 'ymkrf_product', array(
		'label'         => '商品',
		'labels'        => array(
			'name'          => '商品',
			'singular_name' => '商品',
			'add_new'       => '新しい商品',
			'add_new_item'  => '商品を追加',
			'edit_item'     => '商品を編集',
			'search_items'  => '商品を検索',
			'not_found'     => '商品がありません',
		),
		'public'        => true,
		'has_archive'   => true,
		'menu_icon'     => 'dashicons-cart',
		'menu_position' => 4,
		'rewrite'       => array( 'slug' => 'products', 'with_front' => false ),
		'supports'      => array( 'title', 'thumbnail', 'page-attributes' ),
	) );

	/* 商品カテゴリ（キッチン／お風呂／トイレ／洗面化粧台 …）
	   → /products/kitchen/ のような一覧ページが自動でできます */
	register_taxonomy( 'ymkrf_product_cat', 'ymkrf_product', array(
		'label'             => '商品カテゴリ',
		'hierarchical'      => true,
		'rewrite'           => array( 'slug' => 'products', 'with_front' => false ),
		'show_admin_column' => true,
	) );

	/* メーカー（Panasonic／LIXIL／TOTO／クリナップ …） */
	register_taxonomy( 'ymkrf_maker', 'ymkrf_product', array(
		'label'             => 'メーカー',
		'hierarchical'      => true,
		'rewrite'           => array( 'slug' => 'maker', 'with_front' => false ),
		'show_admin_column' => true,
	) );

	/* 展示店舗（金沢野々市店／小松店 …）
	   →「この商品を見られるお店」の表示と、店舗ページからの逆引きに使います */
	register_taxonomy( 'ymkrf_shop', 'ymkrf_product', array(
		'label'             => '展示店舗',
		'hierarchical'      => true,
		'rewrite'           => array( 'slug' => 'shop-display', 'with_front' => false ),
		'show_admin_column' => true,
	) );
} );


/* ============================================================
   2. 入力欄の定義　★項目を増やすときはここ
   ============================================================ */

/** 1つだけ入力する欄 */
function ymkrf_product_fields() {
	return array(
		//  キー           => array( 見出し, 種類, 入力例, 補足説明 )
		'_ymkrf_catch'   => array( 'キャッチコピー',   'text',   '例：キレイと快適が毎日つづく快適キッチン！', '商品名の上に、小さな赤い文字で出ます' ),
		'_ymkrf_grade'   => array( 'グレード',         'text',   '例：Fグレード', '空欄でもかまいません' ),
		'_ymkrf_name'    => array( '商品名',           'text',   '例：V-style（Vスタイル）', '空欄なら上のタイトルを使います' ),
		'_ymkrf_size'    => array( '型（サイズ）',     'text',   '例：I型2550サイズ', '' ),
		'_ymkrf_work'    => array( '標準工事費（円）', 'number', '例：240000', '数字だけ。カンマや「円」は不要です' ),
		'_ymkrf_item'    => array( '商品代（円）',     'number', '例：358000', '数字だけ。カンマや「円」は不要です' ),
		'_ymkrf_days'    => array( '工期（日数）',     'number', '例：3', '数字だけ。「日」は自動で付きます' ),
		'_ymkrf_pt1'     => array( '特徴 1',           'text',   '例：お手頃価格', '' ),
		'_ymkrf_pt2'     => array( '特徴 2',           'text',   '例：収納抜群', '' ),
		'_ymkrf_pt3'     => array( '特徴 3',           'text',   '例：おそうじ楽々', '' ),
		'_ymkrf_caution' => array( '写真の注意書き',   'text',   '例：※写真はイメージです。', '商品写真の下に小さく出ます' ),
	);
}

/** 何行でも増やせる欄 */
function ymkrf_product_repeaters() {
	return array(
		'_ymkrf_images' => array(
			'label' => '組み合わせイメージ写真',
			'note'  => 'カラーバリエーションのいちばん上に、横一列で並びます。3〜4枚が目安です。無ければ空のままでOK。',
			'cols'  => array(
				'img' => array( '写真', 'image' ),
				'alt' => array( '写真の説明', 'text', '例：木目調の扉に、黒いハンドル取手を合わせた例' ),
			),
		),
		'_ymkrf_colors' => array(
			'label' => '扉カラー',
			'note'  => '色見本の写真と、色の名前を入れてください。',
			'cols'  => array(
				'img'  => array( '色見本', 'image' ),
				'name' => array( '色の名前', 'text', '例：ホワイト' ),
			),
		),
		'_ymkrf_handles' => array(
			'label' => '取っ手',
			'note'  => 'キッチン以外で使わない場合は、空のままでOK。見出しごと出なくなります。',
			'cols'  => array(
				'img'  => array( '写真', 'image' ),
				'name' => array( '名称', 'text', '例：ハンドル取手' ),
				'code' => array( '型番', 'text', '例：HAN' ),
			),
		),
		'_ymkrf_specs' => array(
			'label' => '標準仕様',
			'note'  => '標準で付いてくる設備を並べます。',
			'cols'  => array(
				'img'   => array( '写真', 'image' ),
				'name'  => array( '品名', 'text', '例：ホーロー3口トップコンロ' ),
				'model' => array( '型番など', 'text', '例：LEEG32T1V' ),
			),
		),
		'_ymkrf_features' => array(
			'label' => 'おすすめポイント',
			'note'  => '「グループ小見出し」「グループ見出し」を空欄にすると、ひとつ上の行と同じまとまりになります。'
			         . '（例：フロアストッカーの中に Point 1、Point 2 を並べたいとき）',
			'cols'  => array(
				'gsub' => array( 'グループ小見出し', 'text', '例：収納力抜群' ),
				'gttl' => array( 'グループ見出し',   'text', '例：「フロアストッカー」' ),
				'ttl'  => array( '見出し',           'text', '例：大割のスライド収納' ),
				'text' => array( '説明',             'textarea', '' ),
				'note' => array( '注記',             'text', '例：※地質、建物の構造などにより…' ),
				'img'  => array( '写真1',            'image' ),
				'img2' => array( '写真2',            'image' ),
			),
		),
		'_ymkrf_options' => array(
			'label' => 'おすすめオプション',
			'note'  => '',
			'cols'  => array(
				'img'   => array( '写真', 'image' ),
				'name'  => array( '品名', 'text', '例：W450mmプルオープン 食器洗い乾燥機' ),
				'text'  => array( '説明', 'textarea', '' ),
				'price' => array( '追加金額（円）', 'number', '例：176000' ),
				'note'  => array( '補足', 'text', '例：※工事費込み' ),
			),
		),
		'_ymkrf_works' => array(
			'label' => 'ヤマキシ標準工事内容',
			'note'  => '同じカテゴリの商品は、だいたい同じ内容になります。'
			         . '商品一覧の「複製して新規作成」から作れば、ここを入力し直さずに済みます。',
			'cols'  => array(
				'name' => array( '工事名', 'text', '例：撤去工事' ),
				'text' => array( '説明',   'text', '例：古いキッチンの撤去にかかる工事です。' ),
			),
		),
	);
}


/* ============================================================
   3. 入力画面
   ============================================================ */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'ymkrf_product_basic', '商品データ（基本）', 'ymkrf_product_box_basic', 'ymkrf_product', 'normal', 'high' );
	foreach ( ymkrf_product_repeaters() as $key => $r ) {
		add_meta_box(
			'ymkrf_box' . $key, $r['label'],
			function ( $post ) use ( $key ) { ymkrf_product_box_repeater( $post, $key ); },
			'ymkrf_product', 'normal', 'default'
		);
	}
} );

/** 写真を選ぶ機能のために、WordPressのメディア画面と並べ替え機能を読み込む */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	global $post_type;
	if ( $post_type === 'ymkrf_product' && in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		wp_enqueue_media();
		wp_enqueue_script( 'jquery-ui-sortable' );
	}
} );

function ymkrf_product_box_basic( $post ) {
	wp_nonce_field( 'ymkrf_product_save', 'ymkrf_product_nonce' );
	echo ymkrf_product_admin_assets();

	echo '<table class="ymkrf-tbl">';
	foreach ( ymkrf_product_fields() as $key => $f ) {
		printf(
			'<tr><th><label for="%1$s">%2$s</label></th><td>
			   <input type="%3$s" id="%1$s" name="%1$s" value="%4$s" placeholder="%5$s">%6$s</td></tr>',
			esc_attr( $key ), esc_html( $f[0] ), esc_attr( $f[1] ),
			esc_attr( get_post_meta( $post->ID, $key, true ) ), esc_attr( $f[2] ),
			$f[3] ? '<span class="ymkrf-note">' . esc_html( $f[3] ) . '</span>' : ''
		);
	}
	echo '</table>';

	$total = (int) get_post_meta( $post->ID, '_ymkrf_work', true ) + (int) get_post_meta( $post->ID, '_ymkrf_item', true );
	printf(
		'<p class="ymkrf-total">込み価格（自動計算）　<b id="ymkrf-total">%s</b> 円（税込）
		 <span class="ymkrf-note">標準工事費 ＋ 商品代 の合計です。入力の必要はありません。</span></p>',
		number_format( $total )
	);
	?>
	<script>
	(function(){
	  var w = document.getElementById('_ymkrf_work'),
	      i = document.getElementById('_ymkrf_item'),
	      t = document.getElementById('ymkrf-total');
	  if (!w || !i || !t) return;
	  function calc(){ t.textContent = ((+w.value||0) + (+i.value||0)).toLocaleString(); }
	  w.addEventListener('input', calc); i.addEventListener('input', calc);
	})();
	</script>
	<p class="ymkrf-note" style="margin-top:14px">
	  ※ メーカー・商品カテゴリ・展示店舗は、画面右側の欄からチェックを入れてください。<br>
	  ※ 商品写真（いちばん大きく出るもの）は、右側の「アイキャッチ画像」に設定してください。<br>
	  ※ 「グレードUP／グレードを戻す」と「施工事例」は自動で出ます。入力は不要です。
	</p>
	<?php
}

/** 何行でも増やせる欄の画面 */
function ymkrf_product_box_repeater( $post, $key ) {
	$reps = ymkrf_product_repeaters();
	$def  = $reps[ $key ];
	$rows = get_post_meta( $post->ID, $key, true );
	if ( ! is_array( $rows ) ) $rows = array();

	if ( $def['note'] ) echo '<p class="ymkrf-note">' . esc_html( $def['note'] ) . '</p>';
	echo '<div class="ymkrf-rep" data-key="' . esc_attr( $key ) . '">';
	echo '<div class="ymkrf-rep__rows">';
	if ( $rows ) {
		foreach ( $rows as $n => $row ) ymkrf_product_row_html( $key, $def['cols'], $n, $row );
	} else {
		ymkrf_product_row_html( $key, $def['cols'], 0, array() );
	}
	echo '</div>';
	echo '<p><button type="button" class="button ymkrf-rep__add">＋ 行を追加</button></p>';
	echo '</div>';

	/* 「行を追加」で使うひな型 */
	echo '<script type="text/html" class="ymkrf-tpl-' . esc_attr( $key ) . '">';
	ymkrf_product_row_html( $key, $def['cols'], '__i__', array() );
	echo '</script>';
}

function ymkrf_product_row_html( $key, $cols, $n, $row ) {
	echo '<div class="ymkrf-row">';
	echo '<span class="ymkrf-row__handle" title="ドラッグで並べ替え">≡</span>';
	echo '<div class="ymkrf-row__body">';
	foreach ( $cols as $ck => $c ) {
		$name = sprintf( '%s[%s][%s]', $key, $n, $ck );
		$val  = isset( $row[ $ck ] ) ? $row[ $ck ] : '';
		echo '<label class="ymkrf-f"><span>' . esc_html( $c[0] ) . '</span>';
		if ( $c[1] === 'image' ) {
			$src = $val ? wp_get_attachment_image_url( (int) $val, 'thumbnail' ) : '';
			printf(
				'<span class="ymkrf-img">
				   <span class="ymkrf-img__prev">%s</span>
				   <input type="hidden" name="%s" value="%s">
				   <span class="ymkrf-img__btns">
				     <button type="button" class="button ymkrf-img__pick">写真を選ぶ</button>
				     <button type="button" class="button-link ymkrf-img__clear">消す</button>
				   </span>
				 </span>',
				$src ? '<img src="' . esc_url( $src ) . '" alt="">' : '',
				esc_attr( $name ), esc_attr( $val )
			);
		} elseif ( $c[1] === 'textarea' ) {
			printf( '<textarea name="%s" rows="2" placeholder="%s">%s</textarea>',
				esc_attr( $name ), esc_attr( isset( $c[2] ) ? $c[2] : '' ), esc_textarea( $val ) );
		} else {
			printf( '<input type="%s" name="%s" value="%s" placeholder="%s">',
				esc_attr( $c[1] ), esc_attr( $name ), esc_attr( $val ),
				esc_attr( isset( $c[2] ) ? $c[2] : '' ) );
		}
		echo '</label>';
	}
	echo '</div>';
	echo '<button type="button" class="button-link ymkrf-row__del" title="この行を消す">×</button>';
	echo '</div>';
}


/* ============================================================
   4. 保存
   ============================================================ */
add_action( 'save_post_ymkrf_product', function ( $post_id ) {
	if ( ! isset( $_POST['ymkrf_product_nonce'] ) ||
	     ! wp_verify_nonce( sanitize_key( $_POST['ymkrf_product_nonce'] ), 'ymkrf_product_save' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	foreach ( array_keys( ymkrf_product_fields() ) as $key ) {
		if ( ! isset( $_POST[ $key ] ) ) continue;
		update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
	}

	foreach ( ymkrf_product_repeaters() as $key => $def ) {
		$rows  = ( isset( $_POST[ $key ] ) && is_array( $_POST[ $key ] ) ) ? wp_unslash( $_POST[ $key ] ) : array();
		$clean = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) continue;
			$r = array();
			foreach ( $def['cols'] as $ck => $c ) {
				$v = isset( $row[ $ck ] ) ? $row[ $ck ] : '';
				$r[ $ck ] = ( $c[1] === 'textarea' )
					? sanitize_textarea_field( $v )
					: sanitize_text_field( $v );
			}
			/* すべて空の行は保存しない */
			if ( strlen( trim( implode( '', $r ) ) ) ) $clean[] = $r;
		}
		update_post_meta( $post_id, $key, $clean );
	}

	/* 込み価格を保存（並べ替えや絞り込みに使うため） */
	update_post_meta( $post_id, '_ymkrf_total',
		(int) get_post_meta( $post_id, '_ymkrf_work', true ) + (int) get_post_meta( $post_id, '_ymkrf_item', true ) );
} );


/* ============================================================
   5. 商品を複製する
      標準工事内容や標準仕様は商品ごとにほぼ同じなので、
      複製して直す方が、毎回入力するよりずっと早くなります。
      商品一覧で商品名にマウスを乗せると「複製して新規作成」が出ます。
   ============================================================ */
add_filter( 'post_row_actions', function ( $actions, $post ) {
	if ( $post->post_type === 'ymkrf_product' && current_user_can( 'edit_posts' ) ) {
		$url = wp_nonce_url(
			admin_url( 'admin.php?action=ymkrf_duplicate&post=' . $post->ID ), 'ymkrf_dup_' . $post->ID );
		$actions['ymkrf_dup'] = '<a href="' . esc_url( $url ) . '">複製して新規作成</a>';
	}
	return $actions;
}, 10, 2 );

add_action( 'admin_action_ymkrf_duplicate', function () {
	$id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0;
	if ( ! $id || ! current_user_can( 'edit_posts' ) ) wp_die( '権限がありません。' );
	check_admin_referer( 'ymkrf_dup_' . $id );

	$src = get_post( $id );
	if ( ! $src || $src->post_type !== 'ymkrf_product' ) wp_die( '商品が見つかりません。' );

	$new = wp_insert_post( array(
		'post_type'   => 'ymkrf_product',
		'post_title'  => $src->post_title . '（複製）',
		'post_status' => 'draft',
		'menu_order'  => $src->menu_order,
	) );
	if ( is_wp_error( $new ) ) wp_die( '複製できませんでした。' );

	foreach ( array( 'ymkrf_product_cat', 'ymkrf_maker', 'ymkrf_shop' ) as $tax ) {
		$ids = wp_get_object_terms( $id, $tax, array( 'fields' => 'ids' ) );
		if ( ! is_wp_error( $ids ) ) wp_set_object_terms( $new, $ids, $tax );
	}
	foreach ( get_post_meta( $id ) as $k => $v ) {
		if ( in_array( $k, array( '_edit_lock', '_edit_last' ), true ) ) continue;
		update_post_meta( $new, $k, maybe_unserialize( $v[0] ) );
	}
	wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $new ) );
	exit;
} );


/* ============================================================
   6. 一覧を見やすくする（管理画面）
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
   7. テンプレートから呼び出すための関数
   ============================================================ */
if ( ! function_exists( 'ymkrf_product_data' ) ) :
function ymkrf_product_data( $post_id = null ) {
	$post_id = $post_id ?: get_the_ID();
	$m   = function ( $k ) use ( $post_id ) { return get_post_meta( $post_id, $k, true ); };
	$rep = function ( $k ) use ( $post_id ) {
		$v = get_post_meta( $post_id, $k, true );
		return is_array( $v ) ? $v : array();
	};
	$terms = function ( $tax ) use ( $post_id ) {
		$t = get_the_terms( $post_id, $tax );
		return ( $t && ! is_wp_error( $t ) ) ? $t : array();
	};

	$work = (int) $m( '_ymkrf_work' );
	$item = (int) $m( '_ymkrf_item' );

	return array(
		'catch'    => $m( '_ymkrf_catch' ),
		'grade'    => $m( '_ymkrf_grade' ),
		'name'     => $m( '_ymkrf_name' ) ?: get_the_title( $post_id ),
		'size'     => $m( '_ymkrf_size' ),
		'work'     => $work,
		'item'     => $item,
		'total'    => $work + $item,
		'days'     => $m( '_ymkrf_days' ),
		'points'   => array_values( array_filter( array( $m('_ymkrf_pt1'), $m('_ymkrf_pt2'), $m('_ymkrf_pt3') ) ) ),
		'caution'  => $m( '_ymkrf_caution' ),
		'images'   => $rep( '_ymkrf_images' ),
		'colors'   => $rep( '_ymkrf_colors' ),
		'handles'  => $rep( '_ymkrf_handles' ),
		'specs'    => $rep( '_ymkrf_specs' ),
		'features' => $rep( '_ymkrf_features' ),
		'options'  => $rep( '_ymkrf_options' ),
		'works'    => $rep( '_ymkrf_works' ),
		'makers'   => $terms( 'ymkrf_maker' ),
		'cats'     => $terms( 'ymkrf_product_cat' ),
		'shops'    => $terms( 'ymkrf_shop' ),
	);
}
endif;

/**
 * 同じカテゴリの中で、ひとつ下（安い）・ひとつ上（高い）のグレードを返します。
 * 「グレードを戻す／グレードUP」の表示に使います。入力は不要です。
 */
if ( ! function_exists( 'ymkrf_product_siblings' ) ) :
function ymkrf_product_siblings( $post_id = null ) {
	$post_id = $post_id ?: get_the_ID();
	$cats = wp_get_object_terms( $post_id, 'ymkrf_product_cat', array( 'fields' => 'ids' ) );
	if ( empty( $cats ) || is_wp_error( $cats ) ) return array( 'prev' => null, 'next' => null );

	$all = get_posts( array(
		'post_type'      => 'ymkrf_product',
		'posts_per_page' => -1,
		'meta_key'       => '_ymkrf_total',
		'orderby'        => 'meta_value_num',
		'order'          => 'ASC',
		'fields'         => 'ids',
		'tax_query'      => array( array(
			'taxonomy' => 'ymkrf_product_cat', 'field' => 'term_id', 'terms' => $cats,
		) ),
	) );
	$i = array_search( $post_id, $all, true );
	if ( $i === false ) return array( 'prev' => null, 'next' => null );

	return array(
		'prev' => $i > 0 ? $all[ $i - 1 ] : null,
		'next' => isset( $all[ $i + 1 ] ) ? $all[ $i + 1 ] : null,
	);
}
endif;

/**
 * この商品と同じ部位の施工事例を取り出します（自動）。
 * 施工事例側の「部位」（ymkrf_works_cat）に、商品カテゴリと同じ名前の項目を作っておいてください。
 */
if ( ! function_exists( 'ymkrf_product_works' ) ) :
function ymkrf_product_works( $post_id = null, $num = 3 ) {
	$post_id = $post_id ?: get_the_ID();
	$cats = get_the_terms( $post_id, 'ymkrf_product_cat' );
	if ( ! $cats || is_wp_error( $cats ) ) return array();

	$slugs = wp_list_pluck( $cats, 'slug' );
	$args  = array(
		'post_type'      => 'ymkrf_works',
		'posts_per_page' => $num,
		'fields'         => 'ids',
		'tax_query'      => array( array(
			'taxonomy' => 'ymkrf_works_cat', 'field' => 'slug', 'terms' => $slugs,
		) ),
	);
	$ids = get_posts( $args );
	return is_array( $ids ) ? $ids : array();
}
endif;


/**
 * 商品ページ専用のCSSを読み込みます。
 * common.css → page.css → product.css の順です。
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_singular( 'ymkrf_product' ) && ! is_tax( 'ymkrf_product_cat' ) ) return;
	wp_enqueue_style( 'ymkrf-product',
		get_stylesheet_directory_uri() . '/assets/css/product.css',
		array( 'ymkrf-common', 'ymkrf-page' ), defined( 'YMKRF_VER' ) ? YMKRF_VER : null );
}, 20 );


/**
 * お問い合わせボタン。文言を1か所で管理するため関数にしています。
 * 変えたいときはここだけ直せば、全ページに反映されます。
 */
if ( ! function_exists( 'ymkrf_product_cta' ) ) :
function ymkrf_product_cta( $place = 'product', $with_tel = false ) {
	$rsv  = esc_url( home_url( '/inquiry/webrsv/' ) );
	$line = 'https://lin.ee/UJZuSTrz';
	?>
	<div class="p-pagecta__btns" style="margin-top:26px">
	  <a class="c-btn c-btn--block" href="<?php echo $rsv; ?>" data-cta="<?php echo esc_attr( $place ); ?>">
	    <span class="c-btn__label">来店して現物を見る<span class="c-btn__sub">初回特典500円ヤマキシお買物券／展示のない店舗もあります</span></span>
	  </a>
	  <a class="c-btn c-btn--line c-btn--block" href="<?php echo esc_url( $line ); ?>" rel="noopener" data-cta="<?php echo esc_attr( $place ); ?>">
	    <span class="c-btn__label">LINEで相談・見積もり<span class="c-btn__sub">ご相談だけでもOK・24時間受付</span></span>
	  </a>
	  <?php if ( $with_tel ) : ?>
	  <a class="c-btn c-btn--ghost c-btn--block" href="tel:0800-777-3331" data-cta="<?php echo esc_attr( $place ); ?>">
	    <span class="c-btn__label">0800-777-3331<span class="c-btn__sub">通話無料・受付 9:00〜17:00</span></span>
	  </a>
	  <?php endif; ?>
	</div>
	<?php
}
endif;


/* ============================================================
   8. 入力画面の見た目と動き
   ============================================================ */
function ymkrf_product_admin_assets() {
	ob_start(); ?>
<style>
.ymkrf-tbl{width:100%;border-collapse:collapse}
.ymkrf-tbl th{width:180px;text-align:left;padding:12px 10px;vertical-align:top;font-weight:700}
.ymkrf-tbl td{padding:10px}
.ymkrf-tbl tr+tr{border-top:1px solid #eee}
.ymkrf-tbl input{width:100%;max-width:420px}
.ymkrf-note{display:block;margin-top:4px;color:#777;font-size:12px;line-height:1.7}
.ymkrf-total{background:#fff4f0;border:2px solid #fe3301;border-radius:8px;padding:14px 16px;margin-top:16px;font-weight:700}
.ymkrf-total b{font-size:24px;color:#fe3301}

.ymkrf-row{display:flex;align-items:flex-start;gap:8px;background:#fafafa;border:1px solid #e0e0e0;
           border-radius:6px;padding:12px;margin-bottom:8px}
.ymkrf-row__handle{cursor:grab;color:#999;font-size:18px;line-height:1;padding:6px 2px;user-select:none}
.ymkrf-row__body{flex:1;display:grid;gap:10px;grid-template-columns:repeat(auto-fit,minmax(210px,1fr))}
.ymkrf-row__del{color:#b32d2e;font-size:18px;line-height:1;padding:4px 6px;text-decoration:none}
.ymkrf-f{display:block;font-size:12px}
.ymkrf-f>span{display:block;margin-bottom:3px;color:#555;font-weight:700}
.ymkrf-f input,.ymkrf-f textarea{width:100%}
.ymkrf-img{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.ymkrf-img__prev{width:56px;height:56px;flex:none;background:#fff;border:1px solid #ddd;border-radius:4px;
                 display:grid;place-items:center;overflow:hidden}
.ymkrf-img__prev img{max-width:100%;max-height:100%;display:block}
.ymkrf-img__btns{display:flex;flex-direction:column;gap:2px;align-items:flex-start}
.ymkrf-rep__add{margin-top:4px}
</style>
<script>
jQuery(function($){
  /* 行を追加 */
  $(document).on('click', '.ymkrf-rep__add', function(){
    var $rep = $(this).closest('.ymkrf-rep'), key = $rep.data('key');
    var uid  = 'n' + Date.now() + Math.floor(Math.random() * 1000);
    var html = $('.ymkrf-tpl-' + key).html().replace(/__i__/g, uid);
    $rep.find('.ymkrf-rep__rows').append(html);
  });

  /* 行を消す（最後の1行は中身だけ空にする） */
  $(document).on('click', '.ymkrf-row__del', function(){
    var $rows = $(this).closest('.ymkrf-rep__rows');
    if ($rows.find('.ymkrf-row').length <= 1) {
      var $r = $(this).closest('.ymkrf-row');
      $r.find('input,textarea').val('');
      $r.find('.ymkrf-img__prev').empty();
      return;
    }
    $(this).closest('.ymkrf-row').remove();
  });

  /* 写真を選ぶ */
  $(document).on('click', '.ymkrf-img__pick', function(){
    var $box  = $(this).closest('.ymkrf-img');
    var frame = wp.media({ title:'写真を選ぶ', button:{ text:'この写真にする' }, multiple:false });
    frame.on('select', function(){
      var a   = frame.state().get('selection').first().toJSON();
      var url = (a.sizes && a.sizes.thumbnail) ? a.sizes.thumbnail.url : a.url;
      $box.find('input[type=hidden]').val(a.id);
      $box.find('.ymkrf-img__prev').html('<img src="' + url + '" alt="">');
    });
    frame.open();
  });
  $(document).on('click', '.ymkrf-img__clear', function(){
    var $box = $(this).closest('.ymkrf-img');
    $box.find('input[type=hidden]').val('');
    $box.find('.ymkrf-img__prev').empty();
  });

  /* ドラッグで並べ替え */
  if ($.fn.sortable) {
    $('.ymkrf-rep__rows').sortable({ handle:'.ymkrf-row__handle', axis:'y', cursor:'grabbing' });
  }
});
</script>
	<?php
	return ob_get_clean();
}
