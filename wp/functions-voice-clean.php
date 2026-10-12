<?php
/**
 * アンケート画像のおそうじ
 * リフォームヤマキシ（ymkrf）
 *
 * 2026/10/12 ユーザー
 *   「2601-0395 画像の解像度が低かったので、新しい物にしました。
 *     そうなると、前登録してあった画像はどうなる？メディアから削除したいんだけど」
 *
 * 読み取りをやり直して登録しなおすと、新しい画像がメディアに入り、
 * ページはそちらを見にいきます。前の画像はページから外れますが、
 * メディアには残ったままでした。
 *
 * このファイルがすること
 *   1. 登録しなおしたとき、前のアンケート画像をゴミ箱に移します
 *      （完全に消しません。下のおそうじページから、いつでももどせます）
 *   2.「お客様の声 ＞ アンケート画像のおそうじ」で、
 *      どのページからも使われていないアンケート画像を一覧にします。
 *      ゴミ箱へ移すのも、もどすのも、中川さんがボタンを押したときだけです。
 *
 * ★ 消す判断は、こちらではしません。ボタンを押していただく形です。
 *   自動でするのは「ゴミ箱に移す」ところまでです。
 */

if ( ! defined( 'ABSPATH' ) ) exit;


/* ============================================================
   1. 使われているかどうかの判定
   ============================================================ */

/** その画像を、どこかのお客様の声が使っているか（$except のページは数えません） */
function ymkrf_voice_img_in_use( $att_id, $except = 0 ) {

	global $wpdb;
	$att_id = (int) $att_id;
	if ( ! $att_id ) return true;   /* わからないものは、さわりません */

	$n = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->postmeta}
		  WHERE meta_key IN ( '_ymkrf_survey_id', '_ymkrf_survey_pub_id' )
		    AND meta_value = %s AND post_id <> %d",
		(string) $att_id, (int) $except
	) );
	if ( $n > 0 ) return true;

	/* アイキャッチに使われていないか */
	$n = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->postmeta}
		  WHERE meta_key = '_thumbnail_id' AND meta_value = %s",
		(string) $att_id
	) );
	if ( $n > 0 ) return true;

	/* 施工事例の写真（まえ・あと）にまぎれていないか。
	   配列をそのまま入れているので、数字をはさんだ形でさがします。 */
	$like = '%' . $wpdb->esc_like( '"' . $att_id . '"' ) . '%';
	$n = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->postmeta}
		  WHERE meta_key IN ( '_ymkrf_before_imgs', '_ymkrf_after_imgs' )
		    AND meta_value LIKE %s",
		$like
	) );
	if ( $n > 0 ) return true;

	/* 本文の中で使われていないか（まれですが、念のため） */
	$url  = wp_get_attachment_url( $att_id );
	if ( $url ) {
		$file = wp_basename( $url );
		$n = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->posts}
			  WHERE post_status NOT IN ( 'trash', 'auto-draft' )
			    AND post_content LIKE %s",
			'%' . $wpdb->esc_like( $file ) . '%'
		) );
		if ( $n > 0 ) return true;
	}

	return false;
}


/* ============================================================
   2. 登録しなおしたら、前の画像をゴミ箱へ
   ============================================================ */

/** 1枚ぶん、ゴミ箱に移します。移せたら true */
function ymkrf_voice_img_retire( $att_id, $vid ) {

	$att_id = (int) $att_id;
	$vid    = (int) $vid;

	if ( ! $att_id ) return false;
	if ( get_post_type( $att_id ) !== 'attachment' ) return false;
	if ( get_post_status( $att_id ) === 'trash' )    return false;
	if ( ymkrf_voice_img_in_use( $att_id, $vid ) )   return false;

	/* もどすときのために、どのお客様の声のものだったかを控えます */
	update_post_meta( $att_id, '_ymkrf_was_voice_pub', $vid );
	update_post_meta( $att_id, '_ymkrf_retired_at', current_time( 'mysql' ) );

	wp_update_post( array( 'ID' => $att_id, 'post_status' => 'trash' ) );
	return true;
}

/* アンケート画像の行き先が書きかわる「直前」に、前の番号を覚えておきます。
   （update_post_meta は書きかえる前、updated_post_meta は書きかえた後に動きます） */
add_action( 'update_post_meta', function ( $meta_id, $object_id, $meta_key, $meta_value ) {

	if ( $meta_key !== '_ymkrf_survey_pub_id' && $meta_key !== '_ymkrf_survey_id' ) return;
	if ( get_post_type( $object_id ) !== 'ymkrf_voice' ) return;

	$old = (int) get_post_meta( $object_id, $meta_key, true );
	$new = (int) $meta_value;
	if ( $old && $new && $old !== $new ) {
		$GLOBALS['ymkrf_voice_img_old'][ $object_id . '|' . $meta_key ] = $old;
	}
}, 10, 4 );

add_action( 'updated_post_meta', function ( $meta_id, $object_id, $meta_key, $meta_value ) {

	$key = $object_id . '|' . $meta_key;
	if ( empty( $GLOBALS['ymkrf_voice_img_old'][ $key ] ) ) return;

	$old = (int) $GLOBALS['ymkrf_voice_img_old'][ $key ];
	unset( $GLOBALS['ymkrf_voice_img_old'][ $key ] );

	/* 原本と公開用で同じ番号を使っていることがあるので、
	   いまもどこかで使われていないかを見てから移します */
	if ( ymkrf_voice_img_retire( $old, (int) $object_id ) ) {
		$n = (int) get_transient( 'ymkrf_voice_retired_n' );
		set_transient( 'ymkrf_voice_retired_n', $n + 1, DAY_IN_SECONDS );
	}
}, 10, 4 );


/* ============================================================
   3. おそうじページ
   ============================================================ */

add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=ymkrf_voice',
		'アンケート画像のおそうじ',
		'アンケート画像のおそうじ',
		'upload_files',
		'ymkrf-voice-clean',
		'ymkrf_voice_clean_page'
	);
} );

/** アンケート画像らしいものを集めます（$status は inherit か trash） */
function ymkrf_voice_clean_list( $status ) {

	global $wpdb;

	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT ID, post_title, post_date, guid
		   FROM {$wpdb->posts}
		  WHERE post_type = 'attachment'
		    AND post_status = %s
		    AND ( post_title LIKE %s OR post_name LIKE %s OR guid LIKE %s )
		  ORDER BY post_date DESC
		  LIMIT 500",
		$status,
		'%' . $wpdb->esc_like( 'お客様アンケート' ) . '%',
		'%' . $wpdb->esc_like( '-voice' ) . '%',
		'%' . $wpdb->esc_like( '-voice' ) . '%'
	) );

	return (array) $rows;
}

function ymkrf_voice_clean_page() {

	if ( ! current_user_can( 'upload_files' ) ) wp_die( '権限がありません' );

	$done = '';

	/* --- ボタンが押されたとき --- */
	if ( isset( $_POST['ymkrf_clean_nonce'] )
	  && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ymkrf_clean_nonce'] ) ), 'ymkrf_clean' ) ) {

		$ids = isset( $_POST['att'] ) ? array_map( 'intval', (array) $_POST['att'] ) : array();
		$n   = 0;

		if ( isset( $_POST['do_trash'] ) ) {
			foreach ( $ids as $id ) {
				if ( get_post_type( $id ) !== 'attachment' ) continue;
				if ( ymkrf_voice_img_in_use( $id ) ) continue;
				update_post_meta( $id, '_ymkrf_retired_at', current_time( 'mysql' ) );
				wp_update_post( array( 'ID' => $id, 'post_status' => 'trash' ) );
				$n++;
			}
			$done = $n . '枚をゴミ箱に移しました。';
		}

		if ( isset( $_POST['do_restore'] ) ) {
			foreach ( $ids as $id ) {
				if ( get_post_type( $id ) !== 'attachment' ) continue;
				wp_update_post( array( 'ID' => $id, 'post_status' => 'inherit' ) );
				delete_post_meta( $id, '_ymkrf_retired_at' );
				$n++;
			}
			$done = $n . '枚をメディアにもどしました。';
		}
	}

	/* --- いま使われていないもの --- */
	$free = array();
	foreach ( ymkrf_voice_clean_list( 'inherit' ) as $r ) {
		if ( ymkrf_voice_img_in_use( (int) $r->ID ) ) continue;
		$free[] = $r;
	}
	$trashed = ymkrf_voice_clean_list( 'trash' );

	?>
	<div class="wrap">
		<h1>アンケート画像のおそうじ</h1>

		<?php if ( $done ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $done ); ?></p></div>
		<?php endif; ?>

		<p>
			読み取りをやり直して登録しなおすと、前のアンケート画像はページから外れますが、
			メディアには残ります。ここには<strong>どのページからも使われていない</strong>
			アンケート画像だけを出しています。<br>
			「ゴミ箱へ移す」を押しても、ファイルは消えません。下の一覧からいつでももどせます。
		</p>

		<form method="post">
			<?php wp_nonce_field( 'ymkrf_clean', 'ymkrf_clean_nonce' ); ?>

			<h2>使われていないアンケート画像（<?php echo count( $free ); ?>枚）</h2>

			<?php if ( ! $free ) : ?>
				<p>ありません。きれいな状態です。</p>
			<?php else : ?>
				<p><button type="submit" name="do_trash" value="1" class="button button-primary">
					えらんだ画像をゴミ箱へ移す
				</button></p>
				<table class="widefat striped">
					<thead><tr>
						<td class="check-column"><input type="checkbox" onclick="document.querySelectorAll('input[name=\'att[]\']').forEach(function(c){c.checked=this.checked}.bind(this))"></td>
						<th style="width:90px">画像</th>
						<th>題名</th>
						<th style="width:170px">入れた日</th>
						<th style="width:120px">大きさ</th>
					</tr></thead>
					<tbody>
					<?php foreach ( $free as $r ) :
						$id  = (int) $r->ID;
						$sz  = wp_get_attachment_metadata( $id );
						$wh  = ( isset( $sz['width'] ) ? (int) $sz['width'] . '×' . (int) $sz['height'] : '―' );
						$src = wp_get_attachment_image_url( $id, 'thumbnail' );
					?>
						<tr>
							<th class="check-column"><input type="checkbox" name="att[]" value="<?php echo $id; ?>"></th>
							<td><?php if ( $src ) : ?>
								<a href="<?php echo esc_url( wp_get_attachment_url( $id ) ); ?>" target="_blank" rel="noopener">
									<img src="<?php echo esc_url( $src ); ?>" alt="" style="max-width:80px;height:auto">
								</a>
							<?php endif; ?></td>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>" target="_blank" rel="noopener"><?php
									echo esc_html( $r->post_title !== '' ? $r->post_title : '（題名なし）' );
								?></a>
							</td>
							<td><?php echo esc_html( mysql2date( 'Y年n月j日 H:i', $r->post_date ) ); ?></td>
							<td><?php echo esc_html( $wh ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h2 style="margin-top:32px">ゴミ箱に入っているアンケート画像（<?php echo count( $trashed ); ?>枚）</h2>

			<?php if ( ! $trashed ) : ?>
				<p>ありません。</p>
			<?php else : ?>
				<p>
					ここにあるものは、メディアの一覧には出てきません。ファイルは残っています。<br>
					<button type="submit" name="do_restore" value="1" class="button">えらんだ画像をメディアにもどす</button>
				</p>
				<table class="widefat striped">
					<thead><tr>
						<td class="check-column"></td>
						<th style="width:90px">画像</th>
						<th>題名</th>
						<th style="width:170px">入れた日</th>
					</tr></thead>
					<tbody>
					<?php foreach ( $trashed as $r ) :
						$id  = (int) $r->ID;
						$src = wp_get_attachment_image_url( $id, 'thumbnail' );
					?>
						<tr>
							<th class="check-column"><input type="checkbox" name="att[]" value="<?php echo $id; ?>"></th>
							<td><?php if ( $src ) : ?>
								<img src="<?php echo esc_url( $src ); ?>" alt="" style="max-width:80px;height:auto">
							<?php endif; ?></td>
							<td><?php echo esc_html( $r->post_title !== '' ? $r->post_title : '（題名なし）' ); ?></td>
							<td><?php echo esc_html( mysql2date( 'Y年n月j日 H:i', $r->post_date ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</form>

		<p style="margin-top:28px;color:#666">
			※ ファイルそのものを完全に消したいときは、ここでゴミ箱へ移したあと、
			サーバーの <code>wp-content/uploads</code> から中川さんの手で消してください。
			こちらからは消しません。
		</p>
	</div>
	<?php
}
