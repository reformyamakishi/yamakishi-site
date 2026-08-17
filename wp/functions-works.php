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
	if ( $ts && ! is_wp_error( $ts ) ) {
		$t = reset( $ts );
		if ( $t && $t->slug ) return $t->slug;
	}
	return 'other';
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

/** 担当店舗の名前 */
function ymkrf_works_shop_name( $post_id ) {
	$slug = trim( (string) get_post_meta( $post_id, '_ymkrf_shop', true ) );
	if ( $slug === '' ) return '';
	$t = get_term_by( 'slug', $slug, 'ymkrf_shop' );
	return ( $t && ! is_wp_error( $t ) ) ? $t->name : '';
}

add_action( 'add_meta_boxes', function () {
	/* もとの簡単な入力欄は使いません（この下の詳しいものに差しかえます） */
	remove_meta_box( 'ymkrf_works_box', 'ymkrf_works', 'side' );

	add_meta_box( 'ymkrf_works_data', '施工データ', 'ymkrf_works_metabox', 'ymkrf_works', 'normal', 'high' );
}, 20 );

function ymkrf_works_metabox( $post ) {
	wp_nonce_field( 'ymkrf_works_save', 'ymkrf_works_nonce' );
	$get = function ( $k, $d = '' ) use ( $post ) {
		$v = get_post_meta( $post->ID, $k, true );
		return ( $v === '' || $v === null ) ? $d : $v;
	};
	$shops = get_terms( array( 'taxonomy' => 'ymkrf_shop', 'hide_empty' => false ) );
	$prods = get_posts( array(
		'post_type' => 'ymkrf_product', 'posts_per_page' => -1,
		'orderby' => 'title', 'order' => 'ASC', 'post_status' => 'publish',
	) );
	$sel   = array_map( 'intval', array_filter( explode( ',', (string) $get( '_ymkrf_products' ) ) ) );
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
	        <select name="_ymkrf_shop">
	          <option value="">（えらんでください）</option>
	          <?php if ( ! is_wp_error( $shops ) ) foreach ( (array) $shops as $sh ) : ?>
	            <option value="<?php echo esc_attr( $sh->slug ); ?>" <?php selected( $cur, $sh->slug ); ?>>
	              <?php echo esc_html( $sh->name ); ?></option>
	          <?php endforeach; ?>
	        </select>
	      </td>
	    </tr>

	    <tr>
	      <th>使った商品</th>
	      <td>
	        <?php if ( $prods ) : ?>
	          <div class="ymkrf-works__prods">
	            <?php foreach ( $prods as $pr ) : ?>
	              <label>
	                <input type="checkbox" name="_ymkrf_products[]" value="<?php echo (int) $pr->ID; ?>"
	                  <?php checked( in_array( (int) $pr->ID, $sel, true ) ); ?>>
	                <?php echo esc_html( get_the_title( $pr ) ); ?>
	              </label>
	            <?php endforeach; ?>
	          </div>
	          <p class="description">えらぶと、ページの下に商品ページへのリンクが出ます（いくつでも可）。</p>
	        <?php else : ?>
	          <p class="description">まだ商品が登録されていません。</p>
	        <?php endif; ?>
	      </td>
	    </tr>

	    <tr>
	      <th>Before写真（施工前）</th>
	      <td>
	        <?php ymkrf_works_before_field( $post->ID ); ?>
	        <p class="description">
	          Before写真とアイキャッチ画像（＝After写真）の両方があると、
	          <b>左右に動かして見くらべる表示</b>になります。
	        </p>
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
	  .ymkrf-works__prods{display:flex;flex-wrap:wrap;gap:6px 18px;max-width:900px}
	  .ymkrf-works__prods label{display:block;min-width:230px}
	</style>';
} );

add_action( 'save_post_ymkrf_works', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! isset( $_POST['ymkrf_works_nonce'] ) ||
	     ! wp_verify_nonce( $_POST['ymkrf_works_nonce'], 'ymkrf_works_save' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	foreach ( array( '_ymkrf_case_no', '_ymkrf_price', '_ymkrf_period',
	                 '_ymkrf_done', '_ymkrf_shop', '_ymkrf_initial' ) as $k ) {
		update_post_meta( $post_id, $k, isset( $_POST[ $k ] ) ? sanitize_text_field( $_POST[ $k ] ) : '' );
	}
	update_post_meta( $post_id, '_ymkrf_before_img',
		isset( $_POST['_ymkrf_before_img'] ) ? (int) $_POST['_ymkrf_before_img'] : 0 );

	$ids = isset( $_POST['_ymkrf_products'] ) ? (array) $_POST['_ymkrf_products'] : array();
	$ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
	update_post_meta( $post_id, '_ymkrf_products', implode( ',', $ids ) );
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

/** 題名から「工事の内容」の部分だけを取り出します */
function ymkrf_works_title_body( $title ) {
	$t = trim( (string) $title );
	if ( strpos( $t, '｜' ) !== false ) {
		$p = explode( '｜', $t, 2 );
		$t = $p[1];
	}
	return trim( $t );
}

/** 部位とお客様の頭文字から、題名の「工事の内容」を作ります */
function ymkrf_works_title_from_terms( $post_id ) {
	$cats = ymkrf_works_term_names( $post_id, 'ymkrf_works_cat' );
	$cats = array_values( array_filter( $cats, function ( $v ) { return $v !== 'その他'; } ) );
	$name = $cats ? implode( '・', array_slice( $cats, 0, 2 ) ) : 'リフォーム';

	/* 「内装・改装」などは、うしろに「リフォーム」を足しません */
	$asis = array( '内装・改装', '改装・内装', '修理・小工事', 'リフォーム' );
	$body = in_array( $name, $asis, true ) ? $name . 'の施工事例' : $name . 'リフォームの施工事例';

	$ini = trim( (string) get_post_meta( $post_id, '_ymkrf_initial', true ) );
	if ( $ini !== '' ) $body .= '（' . $ini . '様）';

	return $body;
}

add_action( 'save_post_ymkrf_works', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;

	$p = get_post( $post_id );
	if ( ! $p || $p->post_status === 'auto-draft' ) return;

	$body = ymkrf_works_title_body( $p->post_title );
	/* 取り込んだ直後の題名（キッチンリフォーム　Y様（野々市））は作り直します */
	if ( $body === '' || preg_match( '/様[（(]/u', $body ) ) {
		$body = ymkrf_works_title_from_terms( $post_id );
	}

	$area = ymkrf_works_area_name( $post_id );
	$want = ( $area !== '' ? $area . '｜' : '' ) . $body;
	if ( $want === $p->post_title ) return;

	remove_action( 'save_post_ymkrf_works', __FUNCTION__, 25 );
	wp_update_post( array( 'ID' => $post_id, 'post_title' => $want ) );
}, 25 );


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

/** Before / After の見くらべ。Before写真が無いときは、写真1枚だけ出します */
function ymkrf_works_compare( $post_id, $eager = false ) {
	$after_id  = (int) get_post_thumbnail_id( $post_id );
	$before_id = (int) get_post_meta( $post_id, '_ymkrf_before_img', true );
	$load      = $eager ? 'eager' : 'lazy';

	if ( ! $after_id && ! $before_id ) return '';

	/* 片方しか無いときは、ふつうの写真として出します */
	if ( ! $after_id || ! $before_id ) {
		$id = $after_id ? $after_id : $before_id;
		return '<div class="p-work__solo">'
		     . wp_get_attachment_image( $id, 'large', false, array(
		         'loading' => $load, 'decoding' => 'async', 'alt' => '' ) )
		     . '</div>';
	}

	$h  = '<div class="p-compare" data-compare style="--pos:50%">';
	$h .= '<div class="p-compare__layer p-compare__layer--before">'
	    . wp_get_attachment_image( $before_id, 'large', false, array(
	        'loading' => $load, 'decoding' => 'async', 'alt' => '施工前' ) ) . '</div>';
	$h .= '<div class="p-compare__layer p-compare__layer--after">'
	    . wp_get_attachment_image( $after_id, 'large', false, array(
	        'loading' => $load, 'decoding' => 'async', 'alt' => '施工後' ) ) . '</div>';
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
	return wp_list_pluck( $ts, 'name' );
}

/** 一覧に出す短い説明 */
function ymkrf_works_excerpt( $post_id, $len = 80 ) {
	$p = get_post( $post_id );
	if ( ! $p ) return '';
	$t = $p->post_excerpt !== '' ? $p->post_excerpt : wp_strip_all_tags( $p->post_content );
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
   ============================================================ */
add_action( 'admin_head', function () {
	$s = get_current_screen();
	if ( ! $s || $s->id !== 'edit-ymkrf_works' ) return;
	echo '<style>
	  .column-ymkrf_case{width:110px}
	  .column-ymkrf_voice{width:170px}
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
