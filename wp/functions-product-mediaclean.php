<?php
/**
 * 商品の写真を差しかえたとき、前の写真をゴミ箱へ
 * リフォームヤマキシ（ymkrf）　2026/10/19
 *
 * ユーザー指示
 *   「写真を変更するたびに新しくメディアが追加されると、
 *     変更前の写真がずっと残ることになるのを防ぎたい」
 *
 * 商品の登録画面で写真を入れかえて「更新」を押すと、
 *   ・その商品から外れた写真のうち、
 *   ・ほかのどこ（ほかの商品・施工事例・お客様の声・ページ・設定など）でも
 *     使われていないものだけを
 * メディアの「ゴミ箱」に移します。完全には消しません。
 *
 * もどしたいときは「商品 ＞ 差しかえた写真」から、ボタンひとつでもどせます。
 * （お客様の声のアンケート画像のおそうじと同じ考え方です）
 *
 * ★「どこかで使われているかも」と少しでも思えるものは、さわりません。
 *   数字が同じだけの別のデータに当たって残ることはあっても、
 *   使っている写真を消してしまうことはないようにしています。
 */

if ( ! defined( 'ABSPATH' ) ) exit;


/* ============================================================
   1. その商品が使っている写真の番号を集めます
   ============================================================ */
function ymkrf_product_att_ids( $post_id ) {

	$ids = array();

	if ( function_exists( 'ymkrf_product_repeaters' ) ) {
		foreach ( ymkrf_product_repeaters() as $key => $def ) {
			$rows = get_post_meta( $post_id, $key, true );
			if ( ! is_array( $rows ) ) continue;
			foreach ( $rows as $r ) {
				if ( ! is_array( $r ) ) continue;
				foreach ( $def['cols'] as $ck => $c ) {
					if ( empty( $r[ $ck ] ) ) continue;
					if ( $c[1] === 'image' ) {
						$ids[] = (int) $r[ $ck ];
					} elseif ( $c[1] === 'imagelist' && is_array( $r[ $ck ] ) ) {
						foreach ( $r[ $ck ] as $one ) $ids[] = (int) $one;
					}
				}
			}
		}
	}

	$ids[] = (int) get_post_meta( $post_id, '_ymkrf_cat_img', true );   /* カタログの画像 */
	$ids[] = (int) get_post_thumbnail_id( $post_id );                    /* アイキャッチ */

	return array_values( array_unique( array_filter( $ids ) ) );
}


/* ============================================================
   2. その写真が、どこかで使われているか
      （わからないときは「使われている」あつかいにします）
   ============================================================ */
function ymkrf_att_in_use_anywhere( $att_id ) {

	global $wpdb;
	$att_id = (int) $att_id;
	if ( ! $att_id ) return true;

	$s    = (string) $att_id;
	$ser1 = '%' . $wpdb->esc_like( 'i:' . $s . ';' ) . '%';     /* 数字のまま入っている配列 */
	$ser2 = '%' . $wpdb->esc_like( '"' . $s . '"' ) . '%';      /* 文字として入っている配列 */

	/* 投稿・ページ・商品・施工事例・お客様の声 …（ゴミ箱と下書きのかけら・リビジョンはのぞく） */
	$n = $wpdb->get_var( $wpdb->prepare(
		"SELECT 1 FROM {$wpdb->postmeta} pm
		   JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		  WHERE p.post_type NOT IN ( 'attachment', 'revision' )
		    AND p.post_status NOT IN ( 'trash', 'auto-draft' )
		    AND pm.meta_key NOT IN ( '_edit_lock', '_edit_last' )
		    AND ( pm.meta_value = %s OR pm.meta_value LIKE %s OR pm.meta_value LIKE %s )
		  LIMIT 1",
		$s, $ser1, $ser2
	) );
	if ( $n ) return true;

	/* カテゴリ・メーカーなどの設定（ロゴやカテゴリ画像） */
	$n = $wpdb->get_var( $wpdb->prepare(
		"SELECT 1 FROM {$wpdb->termmeta}
		  WHERE meta_value = %s OR meta_value LIKE %s OR meta_value LIKE %s LIMIT 1",
		$s, $ser1, $ser2
	) );
	if ( $n ) return true;

	/* サイト全体の設定（サイトアイコン・ロゴ・テーマの設定など） */
	$n = $wpdb->get_var( $wpdb->prepare(
		"SELECT 1 FROM {$wpdb->options}
		  WHERE option_name NOT LIKE %s AND option_name NOT LIKE %s
		    AND ( option_value = %s OR option_value LIKE %s OR option_value LIKE %s ) LIMIT 1",
		$wpdb->esc_like( '_transient' ) . '%', $wpdb->esc_like( '_site_transient' ) . '%',
		$s, $ser1, $ser2
	) );
	if ( $n ) return true;

	/* 本文の中（コラム・お知らせなど）に、ファイル名で入っていないか */
	$url = wp_get_attachment_url( $att_id );
	if ( $url ) {
		$file = wp_basename( $url );
		$n = $wpdb->get_var( $wpdb->prepare(
			"SELECT 1 FROM {$wpdb->posts}
			  WHERE post_type NOT IN ( 'attachment', 'revision' )
			    AND post_status NOT IN ( 'trash', 'auto-draft' )
			    AND ( post_content LIKE %s OR post_content LIKE %s ) LIMIT 1",
			'%' . $wpdb->esc_like( $file ) . '%',
			'%' . $wpdb->esc_like( 'wp-image-' . $s ) . '%'
		) );
		if ( $n ) return true;
	}

	return false;
}


/* ============================================================
   3. 「更新」の前と後をくらべて、外れた写真をゴミ箱へ
   ============================================================ */

/* アイキャッチは、ほかの欄より先に書きかわるので、書きかわる直前の番号を覚えておきます */
add_action( 'update_post_meta', function ( $meta_id, $object_id, $meta_key ) {
	if ( $meta_key !== '_thumbnail_id' || get_post_type( $object_id ) !== 'ymkrf_product' ) return;
	$old = (int) get_post_meta( $object_id, '_thumbnail_id', true );
	if ( $old ) $GLOBALS['ymkrf_prd_old_thumb'][ (int) $object_id ] = $old;
}, 10, 3 );
add_action( 'delete_post_meta', function ( $meta_ids, $object_id, $meta_key ) {
	if ( $meta_key !== '_thumbnail_id' || get_post_type( $object_id ) !== 'ymkrf_product' ) return;
	$old = (int) get_post_meta( $object_id, '_thumbnail_id', true );
	if ( $old ) $GLOBALS['ymkrf_prd_old_thumb'][ (int) $object_id ] = $old;
}, 10, 3 );

/* 保存の前（ほかの欄が書きかわる前）に、いま使っている写真を控えます */
add_action( 'save_post_ymkrf_product', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;
	if ( ! isset( $_POST['ymkrf_product_nonce'] ) ) return;   /* 登録画面からの「更新」のときだけ */

	$before = ymkrf_product_att_ids( $post_id );
	if ( ! empty( $GLOBALS['ymkrf_prd_old_thumb'][ (int) $post_id ] ) ) {
		$before[] = (int) $GLOBALS['ymkrf_prd_old_thumb'][ (int) $post_id ];
	}
	$GLOBALS['ymkrf_prd_before'][ (int) $post_id ] = array_values( array_unique( $before ) );
}, 1 );

/* 保存のあとで、外れた写真を調べます */
add_action( 'save_post_ymkrf_product', function ( $post_id ) {
	if ( empty( $GLOBALS['ymkrf_prd_before'][ (int) $post_id ] ) ) return;

	$before = $GLOBALS['ymkrf_prd_before'][ (int) $post_id ];
	unset( $GLOBALS['ymkrf_prd_before'][ (int) $post_id ] );

	$after   = ymkrf_product_att_ids( $post_id );
	$removed = array_diff( $before, $after );
	if ( ! $removed ) return;

	$moved = array();
	foreach ( $removed as $id ) {
		$id = (int) $id;
		if ( get_post_type( $id ) !== 'attachment' ) continue;
		if ( get_post_status( $id ) === 'trash' )    continue;
		if ( ymkrf_att_in_use_anywhere( $id ) )      continue;

		/* もどすときのために、どの商品のものだったかを控えます */
		update_post_meta( $id, '_ymkrf_retired_product', (int) $post_id );
		update_post_meta( $id, '_ymkrf_retired_at', current_time( 'mysql' ) );
		wp_update_post( array( 'ID' => $id, 'post_status' => 'trash' ) );
		$moved[] = $id;
	}

	if ( $moved ) set_transient( 'ymkrf_prd_retired_' . get_current_user_id(), $moved, 10 * MINUTE_IN_SECONDS );
}, 99 );

/* 「更新」のあと、上にお知らせを出します */
add_action( 'admin_notices', function () {
	$key   = 'ymkrf_prd_retired_' . get_current_user_id();
	$moved = get_transient( $key );
	if ( ! $moved ) return;
	delete_transient( $key );

	$url = admin_url( 'edit.php?post_type=ymkrf_product&page=ymkrf-product-media-trash' );
	echo '<div class="notice notice-info is-dismissible"><p>'
	   . '差しかえる前の写真 <b>' . count( (array) $moved ) . '枚</b> を、メディアのゴミ箱に移しました'
	   . '（ほかのどこにも使われていない写真だけです）。'
	   . ' <a href="' . esc_url( $url ) . '">もとに戻す</a>'
	   . '</p></div>';
} );


/* ============================================================
   4. 「商品 ＞ 差しかえた写真」… ゴミ箱に移した写真の一覧と、もどすボタン
   ============================================================ */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=ymkrf_product',
		'差しかえた写真',
		'差しかえた写真',
		'upload_files',
		'ymkrf-product-media-trash',
		'ymkrf_product_media_trash_page'
	);
}, 30 );

function ymkrf_product_media_trash_page() {

	if ( ! current_user_can( 'upload_files' ) ) wp_die( '権限がありません' );

	$msg = '';
	if ( isset( $_POST['ymkrf_pmt_nonce'] )
	  && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ymkrf_pmt_nonce'] ) ), 'ymkrf_pmt' )
	  && isset( $_POST['do_restore'] ) ) {
		$n = 0;
		foreach ( array_map( 'intval', (array) ( $_POST['att'] ?? array() ) ) as $id ) {
			if ( get_post_type( $id ) !== 'attachment' || get_post_status( $id ) !== 'trash' ) continue;
			wp_update_post( array( 'ID' => $id, 'post_status' => 'inherit' ) );
			delete_post_meta( $id, '_ymkrf_retired_at' );
			$n++;
		}
		$msg = $n . '枚をメディアにもどしました。商品の登録画面で、もう一度えらびなおしてください。';
	}

	$list = get_posts( array(
		'post_type'      => 'attachment',
		'post_status'    => 'trash',
		'posts_per_page' => 300,
		'meta_key'       => '_ymkrf_retired_product',
		'orderby'        => 'modified',
		'order'          => 'DESC',
	) );
	?>
	<div class="wrap">
		<h1>差しかえた写真</h1>
		<p>商品の登録画面で写真を入れかえたときに、<b>どこにも使われなくなった前の写真</b>を、ここに移しています。<br>
		   メディアの一覧には出ませんが、ファイルは残っています。もどしたい写真にチェックを入れて「もとに戻す」を押してください。</p>
		<?php if ( $msg ) : ?><div class="notice notice-success"><p><?php echo esc_html( $msg ); ?></p></div><?php endif; ?>

		<?php if ( ! $list ) : ?>
			<p style="margin-top:20px;color:#646970">いま、ここに移した写真はありません。</p>
		<?php else : ?>
		<form method="post">
			<?php wp_nonce_field( 'ymkrf_pmt', 'ymkrf_pmt_nonce' ); ?>
			<p><button type="submit" name="do_restore" value="1" class="button button-primary">チェックした写真を、もとに戻す</button></p>
			<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:12px">
			<?php foreach ( $list as $a ) :
				$pid  = (int) get_post_meta( $a->ID, '_ymkrf_retired_product', true );
				$when = (string) get_post_meta( $a->ID, '_ymkrf_retired_at', true );
				$src  = wp_get_attachment_image_url( $a->ID, 'medium' );
				?>
				<label style="display:block;background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:8px;cursor:pointer">
					<span style="display:grid;place-items:center;aspect-ratio:4/3;background:#f6f7f7;border-radius:6px;overflow:hidden">
						<?php if ( $src ) : ?><img src="<?php echo esc_url( $src ); ?>" alt="" style="max-width:100%;max-height:100%;object-fit:contain"><?php endif; ?>
					</span>
					<span style="display:flex;gap:6px;align-items:flex-start;margin-top:6px;font-size:12px;line-height:1.5">
						<input type="checkbox" name="att[]" value="<?php echo (int) $a->ID; ?>" style="margin-top:2px">
						<span>
							<?php echo esc_html( $pid ? get_the_title( $pid ) : '' ); ?><br>
							<span style="color:#646970"><?php echo esc_html( $when ); ?></span>
						</span>
					</span>
				</label>
			<?php endforeach; ?>
			</div>
		</form>
		<?php endif; ?>
	</div>
	<?php
}
