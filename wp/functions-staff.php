<?php
/**
 * functions-staff.php ─ スタッフ（営業担当）
 * 置き場所： wp-content/themes/ymkrf/inc/functions-staff.php
 *
 *  1. 投稿タイプ「スタッフ」
 *  2. 入力欄（所属店舗・よみがな・役職・資格・ひとこと）
 *  3. 表示に使う関数
 *  4. 管理画面の一覧
 *
 * ── 名前について ──────────────────────────────
 * 投稿タイプ … ymkrf_staff
 * 入力欄 …… _ymkrf_staff_shop 所属店舗（店舗の英字）
 *             _ymkrf_staff_kana よみがな／_ymkrf_staff_role 役職
 *             _ymkrf_staff_charge 担当／_ymkrf_staff_lic 資格／_ymkrf_staff_hobby 趣味
 *             _ymkrf_staff_word ひとこと
 * 顔写真 …… アイキャッチ画像
 * URL ……… /staff/（一覧）／/staff/yamagishi/（1人）
 * ─────────────────────────────────────────
 */
if ( ! defined( 'ABSPATH' ) ) exit;


/* ============================================================
   1. 投稿タイプ
   ============================================================ */
add_action( 'init', function () {

	register_post_type( 'ymkrf_staff', array(
		'label'         => 'スタッフ',
		'public'        => true,
		'has_archive'   => 'staff',
		'menu_icon'     => 'dashicons-groups',
		'menu_position' => 6,
		'rewrite'       => array( 'slug' => 'staff', 'with_front' => false ),
		'supports'      => array( 'title', 'thumbnail', 'page-attributes' ),
		'show_in_rest'  => false,
		'labels'        => array(
			'name'          => 'スタッフ',
			'singular_name' => 'スタッフ',
			'add_new'       => '新規追加',
			'add_new_item'  => 'スタッフを追加',
			'edit_item'     => 'スタッフを編集',
			'search_items'  => 'スタッフを検索',
			'not_found'     => 'まだ登録がありません',
		),
	) );
}, 11 );


/** 所属さきの一覧。店舗のほかに「本部」を足しています
 *  （本部は店舗分類には作りません。商品の展示店舗に出てしまうためです） */
function ymkrf_staff_shops() {
	$out = array( 'honbu' => '本部' );
	$terms = get_terms( array( 'taxonomy' => 'ymkrf_shop', 'hide_empty' => false ) );
	if ( ! is_wp_error( $terms ) ) {
		foreach ( (array) $terms as $t ) $out[ $t->slug ] = $t->name;
	}
	return $out;
}


/* ============================================================
   2. 入力欄
   ============================================================ */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'ymkrf_staff_data', 'スタッフの情報', 'ymkrf_staff_metabox', 'ymkrf_staff', 'normal', 'high' );
}, 20 );

function ymkrf_staff_metabox( $post ) {
	wp_nonce_field( 'ymkrf_staff_save', 'ymkrf_staff_nonce' );
	$get = function ( $k ) use ( $post ) {
		return (string) get_post_meta( $post->ID, $k, true );
	};
	$shops = ymkrf_staff_shops();
	$cur   = $get( '_ymkrf_staff_shop' );
	?>
	<table class="form-table ymkrf-staff__table">

	  <tr>
	    <th>所属店舗</th>
	    <td>
	      <select name="_ymkrf_staff_shop">
	        <option value="">（えらんでください）</option>
	        <?php foreach ( $shops as $slug => $name ) : ?>
	          <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $cur, $slug ); ?>>
	            <?php echo esc_html( $name ); ?></option>
	        <?php endforeach; ?>
	      </select>
	      <p class="description">
	        施工事例の「担当した店舗」でこの店舗をえらぶと、
	        <b>営業担当のところにこの人が出てくる</b>ようになります。<br>
	        <b>本部</b>の人は、どの店舗をえらんでも出てきます。
	      </p>
	    </td>
	  </tr>

	  <tr>
	    <th>よみがな</th>
	    <td><input type="text" name="_ymkrf_staff_kana" value="<?php echo esc_attr( $get( '_ymkrf_staff_kana' ) ); ?>"
	               class="regular-text" placeholder="例：やまぎし たろう"></td>
	  </tr>

	  <tr>
	    <th>役職</th>
	    <td><input type="text" name="_ymkrf_staff_role" value="<?php echo esc_attr( $get( '_ymkrf_staff_role' ) ); ?>"
	               class="regular-text" placeholder="例：店長／リフォームアドバイザー"></td>
	  </tr>

	  <tr>
	    <th>担当</th>
	    <td>
	      <input type="text" name="_ymkrf_staff_charge" value="<?php echo esc_attr( $get( '_ymkrf_staff_charge' ) ); ?>"
	             class="regular-text" placeholder="例：リフォーム宣伝／キッチン・お風呂">
	    </td>
	  </tr>

	  <tr>
	    <th>資格</th>
	    <td>
	      <input type="text" name="_ymkrf_staff_lic" value="<?php echo esc_attr( $get( '_ymkrf_staff_lic' ) ); ?>"
	             class="large-text" placeholder="例：増改築相談員／福祉住環境コーディネーター2級">
	      <p class="description">いくつかあるときは「／」で区切ってください。</p>
	    </td>
	  </tr>

	  <tr>
	    <th>趣味</th>
	    <td>
	      <input type="text" name="_ymkrf_staff_hobby" value="<?php echo esc_attr( $get( '_ymkrf_staff_hobby' ) ); ?>"
	             class="regular-text" placeholder="例：DIY">
	      <p class="description">人がらが伝わると、お問い合わせのきっかけになります。</p>
	    </td>
	  </tr>

	  <tr>
	    <th>ひとこと</th>
	    <td>
	      <textarea name="_ymkrf_staff_word" rows="4" class="large-text"
	                placeholder="例：キッチンひとすじ30年。使いやすさを第一に考えてご提案します。"><?php
	        echo esc_textarea( $get( '_ymkrf_staff_word' ) ); ?></textarea>
	    </td>
	  </tr>

	  <tr>
	    <th>URLの文字（英字）</th>
	    <td>
	      <?php $sslug = $post->post_name; ?>
	      <code>/staff/</code>
	      <input type="text" name="ymkrf_staff_slug" value="<?php echo esc_attr( urldecode( $sslug ) ); ?>"
	             class="regular-text" placeholder="例：tontokoton">
	      <code>/</code>
	      <p class="description">
	        名前をローマ字にしたものを入れてください（小文字と - だけ）。<br>
	        空のままだと <code>staff-番号</code> になります。日本語のままだとURLが読めない文字になります。
	      </p>
	    </td>
	  </tr>

	  <tr>
	    <th>顔写真</th>
	    <td>
	      <p class="description">
	        右の<b>「アイキャッチ画像」</b>に入れてください。<br>
	        正方形に近い写真だと、まるく切り取ったときにきれいに出ます。
	      </p>
	    </td>
	  </tr>

	</table>
	<?php
}

add_action( 'save_post_ymkrf_staff', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! isset( $_POST['ymkrf_staff_nonce'] ) ||
	     ! wp_verify_nonce( $_POST['ymkrf_staff_nonce'], 'ymkrf_staff_save' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	foreach ( array( '_ymkrf_staff_shop', '_ymkrf_staff_kana',
	                 '_ymkrf_staff_role', '_ymkrf_staff_lic',
	                 '_ymkrf_staff_hobby', '_ymkrf_staff_charge' ) as $k ) {
		update_post_meta( $post_id, $k, isset( $_POST[ $k ] ) ? sanitize_text_field( $_POST[ $k ] ) : '' );
	}
	update_post_meta( $post_id, '_ymkrf_staff_word',
		isset( $_POST['_ymkrf_staff_word'] )
			? sanitize_textarea_field( wp_unslash( $_POST['_ymkrf_staff_word'] ) ) : '' );
}, 10 );

/* URLの文字を英字にそろえます。
   日本語のままだと %E3%81%A8… のような読めないURLになるためです。 */
add_action( 'save_post_ymkrf_staff', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;

	$p = get_post( $post_id );
	if ( ! $p || $p->post_status === 'auto-draft' ) return;

	$want = '';
	if ( isset( $_POST['ymkrf_staff_slug'] ) ) {
		$want = strtolower( preg_replace( '/[^a-zA-Z0-9\-]/', '', (string) wp_unslash( $_POST['ymkrf_staff_slug'] ) ) );
	}

	$now = rawurldecode( (string) $p->post_name );
	if ( $want === '' ) {
		/* 何も入れていないとき、日本語のままなら staff-番号 にします */
		if ( preg_match( '/^[a-z0-9\-]+$/', $now ) ) return;
		$want = 'staff-' . (int) $post_id;
	}
	if ( $want === $now ) return;

	remove_action( 'save_post_ymkrf_staff', __FUNCTION__, 20 );
	wp_update_post( array(
		'ID'        => $post_id,
		'post_name' => wp_unique_post_slug( $want, $post_id, $p->post_status, $p->post_type, 0 ),
	) );
}, 20 );


/* ============================================================
   3. 表示に使う関数
   ============================================================ */

/** 所属店舗の名前 */
function ymkrf_staff_shop_name( $post_id ) {
	$slug = trim( (string) get_post_meta( $post_id, '_ymkrf_staff_shop', true ) );
	if ( $slug === '' ) return '';
	if ( $slug === 'honbu' ) return '本部';
	$t = get_term_by( 'slug', $slug, 'ymkrf_shop' );
	return ( $t && ! is_wp_error( $t ) ) ? $t->name : '';
}

/** スタッフを取り出します。店舗の英字を渡すと、その店舗だけ */
function ymkrf_staff_list( $shop_slug = '' ) {
	$args = array(
		'post_type'      => 'ymkrf_staff',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'no_found_rows'  => true,
	);
	if ( $shop_slug !== '' ) {
		$args['meta_query'] = array( array(
			'key' => '_ymkrf_staff_shop', 'value' => $shop_slug,
		) );
	}
	$q = new WP_Query( $args );
	$out = $q->posts;
	wp_reset_postdata();
	return $out;
}

/** 顔写真。無いときは、とんとこトンの絵を出します */
function ymkrf_staff_face( $post_id, $size = 'thumbnail' ) {
	$post_id = (int) $post_id;

	/* 0 を渡すと「いま表示中の記事」の写真が出てしまうので、先に分けます */
	$img = $post_id ? get_the_post_thumbnail( $post_id, $size, array( 'loading' => 'lazy', 'alt' => '' ) ) : '';
	if ( $img ) return $img;
	$asset = get_stylesheet_directory_uri();
	/* とんとこトンの絵は、写真より余白が少ないので、少し内側に入れて出します */
	return '<img class="is-chara" src="' . esc_url( $asset . '/assets/img/character/char-icon.png' ) . '"'
	     . ' width="117" height="117" alt="" loading="lazy">';
}

/** 施工事例のページに出す「担当しました」の札
 *  $comment … その工事についてのひとこと（空なら、スタッフの決まったひとこと）
 */
function ymkrf_staff_card( $staff_id, $comment = '' ) {

	$staff_id = (int) $staff_id;
	$comment  = trim( (string) $comment );

	$p = $staff_id ? get_post( $staff_id ) : null;
	$ok = ( $p && $p->post_type === 'ymkrf_staff' && $p->post_status === 'publish' );

	/* 担当者もひとことも無ければ、何も出しません */
	if ( ! $ok && $comment === '' ) return '';

	$shop = $ok ? ymkrf_staff_shop_name( $staff_id ) : '';
	$role = $ok ? trim( (string) get_post_meta( $staff_id, '_ymkrf_staff_role', true ) ) : '';
	$name = $ok ? get_the_title( $staff_id ) : '';

	if ( $comment === '' && $ok ) {
		$comment = trim( (string) get_post_meta( $staff_id, '_ymkrf_staff_word', true ) );
	}

	$face = $ok ? ymkrf_staff_face( $staff_id, 'medium' ) : ymkrf_staff_face( 0, 'medium' );

	$h  = '<div class="p-staffcard">';
	$h .= '<span class="p-staffcard__face">' . $face . '</span>';
	$h .= '<span class="p-staffcard__txt">';

	if ( $shop || $role ) {
		$h .= '<span class="p-staffcard__meta">'
		    . esc_html( trim( $shop . ( $shop && $role ? '　' : '' ) . $role ) ) . '</span>';
	}
	if ( $name !== '' ) {
		$h .= '<span class="p-staffcard__name">' . esc_html( $name ) . '</span>';
	}
	if ( $comment !== '' ) {
		$h .= '<span class="p-staffcard__word">' . nl2br( esc_html( $comment ) ) . '</span>';
	}
	if ( $ok ) {
		$h .= '<a class="p-staffcard__more" href="' . esc_url( get_permalink( $staff_id ) ) . '">'
		    . 'この担当者のページを見る</a>';
	}

	$h .= '</span></div>';
	return $h;
}

/* ============================================================
   4. 管理画面の一覧
   ============================================================ */
add_filter( 'manage_ymkrf_staff_posts_columns', function ( $cols ) {
	$new = array();
	foreach ( $cols as $k => $v ) {
		if ( $k === 'title' ) $new['ymkrf_face'] = '顔写真';
		$new[ $k ] = ( $k === 'title' ) ? '名前' : $v;
		if ( $k === 'title' ) {
			$new['ymkrf_sshop'] = '所属店舗';
			$new['ymkrf_srole'] = '役職';
		}
	}
	return $new;
} );

add_action( 'manage_ymkrf_staff_posts_custom_column', function ( $col, $post_id ) {
	if ( $col === 'ymkrf_face' ) {
		echo '<span style="display:inline-block;width:48px;height:48px;border-radius:50%;overflow:hidden;background:#f0f0f1">';
		$img = get_the_post_thumbnail( $post_id, array( 96, 96 ), array( 'style' => 'width:100%;height:100%;object-fit:cover' ) );
		echo $img ? $img : '';
		echo '</span>';
	} elseif ( $col === 'ymkrf_sshop' ) {
		$v = ymkrf_staff_shop_name( $post_id );
		echo $v ? esc_html( $v ) : '<span style="color:#a7aaad">—</span>';
	} elseif ( $col === 'ymkrf_srole' ) {
		$v = trim( (string) get_post_meta( $post_id, '_ymkrf_staff_role', true ) );
		echo $v ? esc_html( $v ) : '<span style="color:#a7aaad">—</span>';
	}
}, 10, 2 );

add_action( 'admin_head', function () {
	$s = get_current_screen();
	if ( ! $s || $s->post_type !== 'ymkrf_staff' ) return;
	echo '<style>
	  .column-ymkrf_face{width:70px}
	  .column-ymkrf_sshop,.column-ymkrf_srole{width:160px}
	  .ymkrf-staff__table th{width:150px}
	</style>';
} );
