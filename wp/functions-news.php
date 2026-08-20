<?php
/**
 * お知らせ ─ リフォームヤマキシ
 *
 * 置き場所： wp-content/themes/ymkrf/inc/functions-news.php
 *            （functions.php から読み込みます）
 *
 * ・ダッシュボードに「お知らせ」というメニューが増えます。
 * ・URL は 一覧が /news/ 、1件ずつが /news/<記事のスラッグ>/ です。
 * ・種類（お知らせ／イベント／補助金・助成金／営業のご案内）でしぼり込めます。
 * ・お店を1つえらぶと、そのお店のお知らせとして出ます（えらばなくてもOK）。
 *
 * ★イベント・チラシ（/flyer/）は別の仕組みです。ここには入れません。
 */
if ( ! defined( 'ABSPATH' ) ) exit;


/* ============================================================
   1. 投稿タイプ「お知らせ」と、その種類
   ============================================================ */
add_action( 'init', function () {

	register_post_type( 'ymkrf_news', array(
		'label'         => 'お知らせ',
		'labels'        => array(
			'name'          => 'お知らせ',
			'singular_name' => 'お知らせ',
			'add_new'       => '新規追加',
			'add_new_item'  => 'お知らせを新規追加',
			'edit_item'     => 'お知らせを編集',
			'all_items'     => 'お知らせ一覧',
			'search_items'  => 'お知らせを検索',
		),
		'public'        => true,
		'has_archive'   => 'news',
		'menu_icon'     => 'dashicons-megaphone',
		'menu_position' => 7,
		'rewrite'       => array( 'slug' => 'news', 'with_front' => false ),
		'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
		'show_in_rest'  => false,
	) );

	register_taxonomy( 'ymkrf_news_cat', 'ymkrf_news', array(
		'label'             => 'お知らせの種類',
		'labels'            => array(
			'name'          => 'お知らせの種類',
			'singular_name' => 'お知らせの種類',
			'add_new_item'  => '種類を追加',
			'edit_item'     => '種類を編集',
			'all_items'     => 'すべての種類',
		),
		'public'            => true,
		'hierarchical'      => true,   /* チェックボックスで選べるようにします */
		'show_admin_column' => false,  /* 一覧の列は自分で出します */
		'show_in_rest'      => false,
		'rewrite'           => array( 'slug' => 'news-cat', 'with_front' => false ),
	) );
}, 5 );


/* 施工事例・お客様の声と同じく、昔ながらの編集画面（クラシックエディター）にします。
   ブロックエディターだと「お店」「重要」の欄が画面のはしに埋もれてしまい、
   ほかの登録画面と操作が変わってしまうためです。 */
add_filter( 'use_block_editor_for_post_type', function ( $use, $type ) {
	return ( $type === 'ymkrf_news' ) ? false : $use;
}, 10, 2 );


/* 種類のはじめの一式を、一度だけ作ります */
if ( ! function_exists( 'ymkrf_news_cats' ) ) :
function ymkrf_news_cats() {
	return array(
		'oshirase' => 'お知らせ',
		'event'    => 'イベント',
		'hojokin'  => '補助金・助成金',
		'eigyo'    => '営業のご案内',
	);
}
endif;

add_action( 'init', function () {
	if ( get_option( 'ymkrf_news_setup_ver' ) === '1' ) return;
	foreach ( ymkrf_news_cats() as $slug => $name ) {
		if ( ! term_exists( $slug, 'ymkrf_news_cat' ) ) {
			wp_insert_term( $name, 'ymkrf_news_cat', array( 'slug' => $slug ) );
		}
	}
	flush_rewrite_rules( false );
	update_option( 'ymkrf_news_setup_ver', '1' );
}, 100 );


/* ============================================================
   2. お知らせのページで読み込むCSS
   ============================================================ */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_singular( 'ymkrf_news' ) && ! is_post_type_archive( 'ymkrf_news' )
	     && ! is_tax( 'ymkrf_news_cat' ) ) return;
	$dir = get_stylesheet_directory_uri();
	$ver = defined( 'YMKRF_VER' ) ? YMKRF_VER : null;
	/* 関連ページの一覧（.p-lprel）とCTA（.p-lpcta）の見た目は lp.css を使っています */
	wp_enqueue_style( 'ymkrf-lp',   $dir . '/assets/css/lp.css',   array( 'ymkrf-common', 'ymkrf-page' ), $ver );
	wp_enqueue_style( 'ymkrf-news', $dir . '/assets/css/news.css', array( 'ymkrf-lp' ), $ver );
}, 20 );


/* ============================================================
   3. 便利な関数
   ============================================================ */

/** このお知らせの種類（名前とスラッグ）。ないときは空 */
if ( ! function_exists( 'ymkrf_news_cat_of' ) ) :
function ymkrf_news_cat_of( $post_id ) {
	$ts = get_the_terms( $post_id, 'ymkrf_news_cat' );
	if ( ! $ts || is_wp_error( $ts ) ) return array( 'name' => '', 'slug' => '' );
	return array( 'name' => $ts[0]->name, 'slug' => $ts[0]->slug );
}
endif;

/** このお知らせのお店の名前。ないときは空 */
if ( ! function_exists( 'ymkrf_news_shop_name' ) ) :
function ymkrf_news_shop_name( $post_id ) {
	$slug = trim( (string) get_post_meta( $post_id, '_ymkrf_shop', true ) );
	if ( $slug === '' ) return '';
	$t = get_term_by( 'slug', $slug, 'ymkrf_shop' );
	return ( $t && ! is_wp_error( $t ) ) ? $t->name : '';
}
endif;

/** トップページなどで使う一覧。$number件、新しい順（固定したものが先） */
if ( ! function_exists( 'ymkrf_news_query' ) ) :
function ymkrf_news_query( $number = 4, $cat = '' ) {
	$args = array(
		'post_type'           => 'ymkrf_news',
		'posts_per_page'      => (int) $number,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'meta_key'            => '_ymkrf_news_pin',
		'orderby'             => array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ),
	);
	if ( $cat !== '' ) {
		$args['tax_query'] = array( array(
			'taxonomy' => 'ymkrf_news_cat', 'field' => 'slug', 'terms' => $cat,
		) );
	}
	return new WP_Query( $args );
}
endif;

/** 一覧の1行ぶんを出します（トップページ・お知らせ一覧の両方で使います） */
if ( ! function_exists( 'ymkrf_news_row' ) ) :
function ymkrf_news_row( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	$cat  = ymkrf_news_cat_of( $post_id );
	$shop = ymkrf_news_shop_name( $post_id );
	$pin  = get_post_meta( $post_id, '_ymkrf_news_pin', true ) === '1';
	?>
	<li class="p-news__item<?php echo $pin ? ' is-pin' : ''; ?>">
	  <a class="p-news__link" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
	    <time class="p-news__date" datetime="<?php echo esc_attr( get_the_date( 'Y-m-d', $post_id ) ); ?>"><?php
	      echo esc_html( get_the_date( 'Y.m.d', $post_id ) ); ?></time>
	    <?php if ( $cat['slug'] !== '' ) : ?>
	      <span class="p-news__cat p-news__cat--<?php echo esc_attr( $cat['slug'] ); ?>"><?php
	        echo esc_html( $cat['name'] ); ?></span>
	    <?php endif; ?>
	    <span class="p-news__title"><?php
	      if ( $pin ) echo '<span class="p-news__pin">重要</span>';
	      echo esc_html( get_the_title( $post_id ) );
	      if ( $shop !== '' ) echo '<span class="p-news__shop">' . esc_html( $shop ) . '</span>';
	    ?></span>
	  </a>
	</li>
	<?php
}
endif;


/* ============================================================
   4. 編集画面の入力欄（お店・重要）
   ============================================================ */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'ymkrf_news_box', 'このお知らせについて',
		'ymkrf_news_box', 'ymkrf_news', 'side', 'high' );
} );

function ymkrf_news_box( $post ) {
	wp_nonce_field( 'ymkrf_news_save', 'ymkrf_news_nonce' );
	$shop = (string) get_post_meta( $post->ID, '_ymkrf_shop', true );
	$pin  = get_post_meta( $post->ID, '_ymkrf_news_pin', true ) === '1';
	$shops = get_terms( array( 'taxonomy' => 'ymkrf_shop', 'hide_empty' => false ) );
	?>
	<p>
	  <label for="ymkrf-news-shop"><b>お店</b></label><br>
	  <select name="_ymkrf_shop" id="ymkrf-news-shop" style="width:100%">
	    <option value="">（お店を問わない）</option>
	    <?php if ( ! is_wp_error( $shops ) ) foreach ( (array) $shops as $sh ) : ?>
	      <option value="<?php echo esc_attr( $sh->slug ); ?>" <?php selected( $shop, $sh->slug ); ?>>
	        <?php echo esc_html( $sh->name ); ?></option>
	    <?php endforeach; ?>
	  </select>
	  <span class="description">そのお店だけの話のときにえらびます。全店なら空のままで。</span>
	</p>
	<p>
	  <label><input type="checkbox" name="_ymkrf_news_pin" value="1" <?php checked( $pin ); ?>>
	    <b>重要（いちばん上に固定する）</b></label><br>
	  <span class="description">日付に関係なく、一覧のいちばん上に出ます。</span>
	</p>
	<?php
}

add_action( 'save_post_ymkrf_news', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! isset( $_POST['ymkrf_news_nonce'] ) ||
	     ! wp_verify_nonce( $_POST['ymkrf_news_nonce'], 'ymkrf_news_save' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	update_post_meta( $post_id, '_ymkrf_shop',
		isset( $_POST['_ymkrf_shop'] ) ? sanitize_text_field( $_POST['_ymkrf_shop'] ) : '' );
	update_post_meta( $post_id, '_ymkrf_news_pin',
		empty( $_POST['_ymkrf_news_pin'] ) ? '' : '1' );
} );


/* ============================================================
   5. 管理画面の一覧
   ============================================================ */
add_filter( 'manage_ymkrf_news_posts_columns', function ( $cols ) {
	$new = array();
	if ( isset( $cols['cb'] ) )    $new['cb']    = $cols['cb'];
	if ( isset( $cols['title'] ) ) $new['title'] = $cols['title'];
	$new['ymkrf_ncat']  = '種類';
	$new['ymkrf_nshop'] = 'お店';
	$new['ymkrf_npin']  = '重要';
	foreach ( $cols as $k => $v ) {
		if ( ! isset( $new[ $k ] ) ) $new[ $k ] = $v;
	}
	return $new;
}, 20 );

add_action( 'manage_ymkrf_news_posts_custom_column', function ( $col, $post_id ) {
	$none = '<span style="color:#a7aaad">—</span>';
	switch ( $col ) {
		case 'ymkrf_ncat':
			$c = ymkrf_news_cat_of( $post_id );
			echo $c['name'] !== '' ? esc_html( $c['name'] ) : $none;
			break;
		case 'ymkrf_nshop':
			$v = ymkrf_news_shop_name( $post_id );
			echo $v !== '' ? esc_html( $v ) : $none;
			break;
		case 'ymkrf_npin':
			echo get_post_meta( $post_id, '_ymkrf_news_pin', true ) === '1'
				? '<span style="color:#d63638;font-weight:700">● 重要</span>' : $none;
			break;
	}
}, 10, 2 );

/* 種類でしぼり込めるようにします */
add_action( 'restrict_manage_posts', function ( $post_type ) {
	if ( $post_type !== 'ymkrf_news' ) return;
	$now = isset( $_GET['ymkrf_news_cat'] ) ? sanitize_key( $_GET['ymkrf_news_cat'] ) : '';
	$ts  = get_terms( array( 'taxonomy' => 'ymkrf_news_cat', 'hide_empty' => false ) );
	if ( is_wp_error( $ts ) ) return;
	echo '<select name="ymkrf_news_cat"><option value="">すべての種類</option>';
	foreach ( (array) $ts as $t ) {
		echo '<option value="' . esc_attr( $t->slug ) . '" ' . selected( $now, $t->slug, false ) . '>'
		   . esc_html( $t->name ) . '（' . (int) $t->count . '）</option>';
	}
	echo '</select>';
} );

add_action( 'admin_head', function () {
	$s = get_current_screen();
	if ( ! $s || $s->id !== 'edit-ymkrf_news' ) return;
	echo '<style>
	  .column-ymkrf_ncat{width:120px}
	  .column-ymkrf_nshop{width:130px}
	  .column-ymkrf_npin{width:80px}
	</style>';
} );


/* ============================================================
   6. 見本のお知らせを2件だけ、一度だけ作ります
      本物が入ったら消してください（一覧から削除するだけでOK）。
      もう一度作られることはありません。
   ============================================================ */
add_action( 'admin_init', function () {
	if ( get_option( 'ymkrf_news_sample_ver' ) === '1' ) return;
	update_option( 'ymkrf_news_sample_ver', '1' );

	$samples = array(
		array(
			'title' => '【見本】東金沢店が2026年10月31日（土）にオープンします',
			'cat'   => 'oshirase',
			'shop'  => 'higashikanazawa',
			'pin'   => '1',
			'body'  => "<p>金沢市大樋町に、リフォームヤマキシ 東金沢店がオープンします。</p>\n"
			         . "<p>くわしい住所・電話番号・営業時間は、決まりしだいこのページでお知らせします。</p>\n"
			         . "<p>※これは見本のお知らせです。本物のお知らせを入れたら削除してください。</p>",
		),
		array(
			'title' => '【見本】住宅省エネ2026キャンペーンの受付が始まりました',
			'cat'   => 'hojokin',
			'shop'  => '',
			'pin'   => '',
			'body'  => "<p>窓リフォームやエコキュートに使える補助金の受付が始まりました。</p>\n"
			         . "<p>お住まいの状況によって使えるかどうかが変わりますので、まずはお気軽にご相談ください。"
			         . "現地調査・お見積りは無料です。</p>\n"
			         . "<p>※これは見本のお知らせです。本物のお知らせを入れたら削除してください。</p>",
		),
	);

	foreach ( $samples as $sp ) {
		$id = wp_insert_post( array(
			'post_type'    => 'ymkrf_news',
			'post_status'  => 'publish',
			'post_title'   => $sp['title'],
			'post_content' => $sp['body'],
		) );
		if ( ! $id || is_wp_error( $id ) ) continue;
		wp_set_object_terms( $id, $sp['cat'], 'ymkrf_news_cat' );
		update_post_meta( $id, '_ymkrf_shop', $sp['shop'] );
		update_post_meta( $id, '_ymkrf_news_pin', $sp['pin'] );
		update_post_meta( $id, '_ymkrf_news_sample', '1' );
	}
}, 30 );
