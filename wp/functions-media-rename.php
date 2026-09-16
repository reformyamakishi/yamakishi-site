<?php
/**
 * functions-media-rename.php ─ 写真のファイル名を、決まった形にそろえます
 *
 * 置き場所： wp-content/themes/ymkrf/inc/functions-media-rename.php
 *
 * （2026/09/16 ユーザー指示
 *   「お客様の声のメディアのデータ名を voice-番号 に統一できない？」）
 *
 * いまは「下見（こう変わります、の一覧）」だけです。
 * 実際に名前を変えるのは、一覧を見ていただいてからにします。
 *
 * ★名前の付けかた（2026/09/16 ユーザー指示）
 *     uploads/voice/<案件番号>.jpg
 *     ・年月のフォルダ（2026/09）は使いません
 *     ・案件番号は重ならないので、うしろに何も足しません
 *     ・案件番号が無いときだけ id<お客様の声のID>.jpg
 *     ・原本（塗りつぶし前）と公開用は、同じURLにします
 *       → 公開用があるお客様の声では、原本は消えます
 */

if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * 写真の「撮影日」。
 * カメラが画像に書き込んだ日時（EXIF）を、WordPressが
 * アップロードのときに拾って持っています。無いことも多いです。
 * 取り込んだ日ではありません。
 */
if ( ! function_exists( 'ymkrf_mrn_shot_date' ) ) :
function ymkrf_mrn_shot_date( $att_id ) {
	$m = wp_get_attachment_metadata( $att_id );
	if ( empty( $m['image_meta']['created_timestamp'] ) ) return '';
	$t = (int) $m['image_meta']['created_timestamp'];
	if ( $t <= 0 ) return '';
	return wp_date( 'Ymd', $t );
}
endif;

/** 撮影日を「2025年3月12日 10:22」の形で返します */
if ( ! function_exists( 'ymkrf_mrn_shot_text' ) ) :
function ymkrf_mrn_shot_text( $att_id ) {
	$m = wp_get_attachment_metadata( $att_id );
	if ( empty( $m['image_meta']['created_timestamp'] ) ) return '';
	$t = (int) $m['image_meta']['created_timestamp'];
	if ( $t <= 0 ) return '';
	return wp_date( 'Y年n月j日 G:i', $t );
}
endif;

/** 「お客様の声」の写真について、新しい名前を考えます */
if ( ! function_exists( 'ymkrf_mrn_plan_voice' ) ) :
function ymkrf_mrn_plan_voice() {

	global $wpdb;

	/* どの写真が、どのお客様の声のものか（アンケートの欄から引きます） */
	$owner = array();   /* 写真ID => array( voice投稿ID, 'raw' or 'pub' ) */

	$rows = $wpdb->get_results(
		"SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta}
		  WHERE meta_key IN ( '_ymkrf_survey_id', '_ymkrf_survey_pub_id' )
		    AND meta_value <> '' AND meta_value <> '0'"
	);
	foreach ( (array) $rows as $r ) {
		$att = (int) $r->meta_value;
		if ( $att <= 0 ) continue;
		$owner[ $att ] = array(
			(int) $r->post_id,
			( $r->meta_key === '_ymkrf_survey_pub_id' ) ? 'pub' : 'raw',
		);
	}

	/* 欄からたどれないものは、アップロード元の記事で見ます */
	$rows = $wpdb->get_results(
		"SELECT att.ID AS id, att.post_parent AS par
		   FROM {$wpdb->posts} att
		   JOIN {$wpdb->posts} par ON par.ID = att.post_parent
		  WHERE att.post_type = 'attachment' AND par.post_type = 'ymkrf_voice'"
	);
	foreach ( (array) $rows as $r ) {
		$id = (int) $r->id;
		if ( ! isset( $owner[ $id ] ) ) $owner[ $id ] = array( (int) $r->par, 'raw' );
	}

	if ( ! $owner ) return array();

	/* 案件番号をまとめて引きます */
	$vids = array_unique( array_map( function ( $v ) { return $v[0]; }, $owner ) );
	$case = array();
	if ( $vids ) {
		$in   = implode( ',', array_map( 'intval', $vids ) );
		$rows = $wpdb->get_results(
			"SELECT post_id, meta_value FROM {$wpdb->postmeta}
			  WHERE meta_key = '_ymkrf_case_no' AND post_id IN ( {$in} )"
		);
		foreach ( (array) $rows as $r ) $case[ (int) $r->post_id ] = trim( (string) $r->meta_value );
	}

	/* いまのファイル名を引きます */
	$in   = implode( ',', array_map( 'intval', array_keys( $owner ) ) );
	$rows = $wpdb->get_results(
		"SELECT p.ID AS id, p.post_title AS ttl, COALESCE( fm.meta_value, '' ) AS f
		   FROM {$wpdb->posts} p
		   LEFT JOIN {$wpdb->postmeta} fm
		          ON fm.post_id = p.ID AND fm.meta_key = '_wp_attached_file'
		  WHERE p.ID IN ( {$in} )
		  ORDER BY p.ID ASC"
	);

	/* 1つのお客様の声につき、残すのは1枚（公開用があればそちら）です */
	$cur  = array();   /* 写真ID => いまのファイル名 */
	$ttl  = array();
	foreach ( (array) $rows as $r ) {
		$cur[ (int) $r->id ] = (string) $r->f;
		$ttl[ (int) $r->id ] = (string) $r->ttl;
	}

	$byvoice = array();   /* voiceID => array( 'raw' => 写真ID, 'pub' => 写真ID ) */
	foreach ( $owner as $att => $o ) {
		if ( ! isset( $cur[ $att ] ) || $cur[ $att ] === '' ) continue;
		$byvoice[ $o[0] ][ $o[1] ] = $att;
	}

	$plan = array();
	$seen = array();   /* 撮影日が重なったときのため */

	foreach ( $byvoice as $vid => $set ) {

		$no = isset( $case[ $vid ] ) ? $case[ $vid ] : '';
		$no = preg_replace( '/[^0-9A-Za-z-]/', '', $no );

		/* 残すほう（公開用があれば公開用） */
		$keep = isset( $set['pub'] ) ? $set['pub'] : ( isset( $set['raw'] ) ? $set['raw'] : 0 );
		if ( ! $keep ) continue;

		/* 案件番号が無いものは、あとでスキャン日の古い順に 001 から付けます */
		$shot = ( $no === '' ) ? ymkrf_mrn_shot_date( $keep ) : '';
		$base = ( $no !== '' ) ? strtolower( $no ) : '';

		$ext = '.jpg';
		if ( preg_match( '/(\.[A-Za-z0-9]{1,5})$/', $cur[ $keep ], $m ) ) $ext = strtolower( $m[1] );

		$plan[] = array(
			'id'    => $keep,
			'ttl'   => isset( $ttl[ $keep ] ) ? $ttl[ $keep ] : '',
			'voice' => (int) $vid,
			'no'    => $no,
			'kind'  => isset( $set['pub'] ) && $set['pub'] == $keep ? '公開用' : '原本',
			'shot'  => $shot,
			'from'  => $cur[ $keep ],
			'to'    => ( $base !== '' ) ? 'voice/' . $base . $ext : '',
			'ext'   => $ext,
			'drop'  => 0,
		);

		/* 公開用があるときは、原本は消します（同じURLにするため） */
		if ( isset( $set['pub'], $set['raw'] ) && $set['pub'] != $set['raw'] ) {
			$plan[] = array(
				'id'    => $set['raw'],
				'ttl'   => isset( $ttl[ $set['raw'] ] ) ? $ttl[ $set['raw'] ] : '',
				'voice' => (int) $vid,
				'no'    => $no,
				'kind'  => '原本',
				'shot'  => '',
				'from'  => $cur[ $set['raw'] ],
				'to'    => '',
				'drop'  => 1,
				'keep'  => $keep,
			);
		}
	}

	/* 案件番号が無いものに、スキャン日（撮影日）の古い順で 001 から付けます。
	   日付が読み取れなかったものは、うしろにまわします。 */
	$nums = array();
	foreach ( $plan as $i => $p ) {
		if ( $p['drop'] || $p['no'] !== '' ) continue;
		$nums[ $i ] = ( $p['shot'] !== '' ) ? $p['shot'] : '99999999';
	}
	asort( $nums );

	$n = 0;
	foreach ( $nums as $i => $d ) {
		$n++;

		/* お客様の声のほうに通し番号が振ってあれば、それに合わせます
		   （URL /voice/toilet/001/ と 写真 voice/001.jpg をそろえるため） */
		$seq = trim( (string) get_post_meta( $plan[ $i ]['voice'], '_ymkrf_noseq', true ) );
		if ( $seq === '' ) $seq = sprintf( '%03d', $n );

		$plan[ $i ]['seq'] = $seq;
		$plan[ $i ]['to']  = 'voice/' . $seq . $plan[ $i ]['ext'];
	}

	foreach ( $plan as $i => $p ) {
		$plan[ $i ]['same'] = ( ! $p['drop'] && $p['from'] === $p['to'] );
	}

	return $plan;
}
endif;


/* ============================================================
   じっさいに名前を変える（1回に50枚ずつ）
   ============================================================ */

/** 写真1枚の名前を変えます。うまくいったら true */
if ( ! function_exists( 'ymkrf_mrn_move_one' ) ) :
function ymkrf_mrn_move_one( $id, $from, $to, &$err = '' ) {

	global $wpdb;
	$up   = wp_upload_dir();
	$base = untrailingslashit( $up['basedir'] );
	$burl = untrailingslashit( $up['baseurl'] );

	$src = $base . '/' . $from;
	$dst = $base . '/' . $to;

	if ( ! file_exists( $src ) ) { $err = 'もとのファイルがありません'; return false; }
	if ( file_exists( $dst ) )   { $err = '同じ名前のファイルがすでにあります'; return false; }

	wp_mkdir_p( dirname( $dst ) );
	if ( ! @rename( $src, $dst ) ) { $err = 'ファイルを動かせませんでした'; return false; }

	$olddir  = ( strpos( $from, '/' ) !== false ) ? dirname( $from ) : '';
	$newdir  = ( strpos( $to,   '/' ) !== false ) ? dirname( $to )   : '';
	$oldstem = preg_replace( '/\.[^.]+$/', '', basename( $from ) );
	$newstem = preg_replace( '/\.[^.]+$/', '', basename( $to ) );

	/* 小さいサイズの画像も、いっしょに動かします */
	$meta = wp_get_attachment_metadata( $id );
	if ( is_array( $meta ) ) {
		if ( ! empty( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
			foreach ( $meta['sizes'] as $k => $sz ) {
				if ( empty( $sz['file'] ) ) continue;
				$sname = str_replace( $oldstem, $newstem, $sz['file'] );
				$sfrom = $base . '/' . ( $olddir !== '' ? $olddir . '/' : '' ) . $sz['file'];
				$sto   = $base . '/' . ( $newdir !== '' ? $newdir . '/' : '' ) . $sname;
				if ( file_exists( $sfrom ) && ! file_exists( $sto ) ) @rename( $sfrom, $sto );
				$meta['sizes'][ $k ]['file'] = $sname;
			}
		}
		$meta['file'] = $to;
		wp_update_attachment_metadata( $id, $meta );
	}

	update_post_meta( $id, '_wp_attached_file', $to );
	$wpdb->update( $wpdb->posts, array( 'guid' => $burl . '/' . $to ), array( 'ID' => $id ) );
	clean_post_cache( $id );

	/* 記事の中に書かれたURLも書きかえます（小さいサイズのURLもまとめて） */
	$oldpre = $burl . '/' . ( $olddir !== '' ? $olddir . '/' : '' ) . $oldstem;
	$newpre = $burl . '/' . ( $newdir !== '' ? $newdir . '/' : '' ) . $newstem;
	$wpdb->query( $wpdb->prepare(
		"UPDATE {$wpdb->posts} SET post_content = REPLACE( post_content, %s, %s )
		  WHERE post_content LIKE %s",
		$oldpre, $newpre, '%' . $wpdb->esc_like( $oldpre ) . '%'
	) );
	$wpdb->query( $wpdb->prepare(
		"UPDATE {$wpdb->postmeta} SET meta_value = REPLACE( meta_value, %s, %s )
		  WHERE meta_value LIKE %s AND meta_key NOT IN ( '_wp_attached_file', '_wp_attachment_metadata' )",
		$oldpre, $newpre, '%' . $wpdb->esc_like( $oldpre ) . '%'
	) );

	return true;
}
endif;

/** 下見のとおりに、50枚ぶん実行します */
if ( ! function_exists( 'ymkrf_mrn_run' ) ) :
function ymkrf_mrn_run( $limit = 50 ) {

	@set_time_limit( 300 );

	$plan = ymkrf_mrn_plan_voice();
	$log  = array( 'moved' => 0, 'dropped' => 0, 'skipped' => 0, 'drafted' => 0,
	                   'errors' => array(), 'left' => 0 );

	$todo = array_values( array_filter( $plan, function ( $p ) {
		return $p['drop'] || ! $p['same'];
	} ) );

	$log['left'] = max( 0, count( $todo ) - $limit );

	foreach ( array_slice( $todo, 0, $limit ) as $p ) {

		if ( $p['drop'] ) {
			/* 原本を消して、お客様の声の欄は残すほうに向けます */
			if ( ! empty( $p['keep'] ) ) {
				update_post_meta( $p['voice'], '_ymkrf_survey_id', (int) $p['keep'] );
			}
			if ( wp_delete_attachment( (int) $p['id'], true ) ) $log['dropped']++;
			else $log['errors'][] = '原本を消せませんでした（ID ' . (int) $p['id'] . '）';
			continue;
		}

		$err  = '';
		$from = $p['from'];

		if ( ymkrf_mrn_move_one( (int) $p['id'], $p['from'], $p['to'], $err ) ) {
			$log['moved']++;
		} else {
			$log['skipped']++;
			$log['errors'][] = ( $p['no'] !== '' ? $p['no'] : '番号なし' ) . '： ' . $err;
			continue;
		}

		/* 案件番号が分からないものは、あとで調べられるように印を残します */
		if ( $p['no'] === '' ) {

			$shot = ymkrf_mrn_shot_text( (int) $p['id'] );
			$txt  = "案件番号がわかっていないアンケートです。\n"
			      . 'スキャン日（画像に入っていた日時）：'
			      . ( $shot !== '' ? $shot : '画像に情報がありませんでした' ) . "\n"
			      . '元のファイル名：' . $from . "\n"
			      . 'お客様の声：' . get_the_title( $p['voice'] ) . '（ID ' . (int) $p['voice'] . '）';

			wp_update_post( array(
				'ID'           => (int) $p['id'],
				'post_content' => $txt,
			) );

			/* お客様の声は、まだ出さないようにします（下書きにもどします） */
			if ( get_post_status( $p['voice'] ) === 'publish' ) {
				wp_update_post( array( 'ID' => (int) $p['voice'], 'post_status' => 'draft' ) );
				$log['drafted']++;
			}
		}
	}

	if ( function_exists( 'ymkrf_media_forget' ) ) ymkrf_media_forget();

	return $log;
}
endif;


/* ------------------------------------------------------------
   下見の画面（メディア ＞ 写真の名前をそろえる）
   ------------------------------------------------------------ */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'upload.php',
		'写真の名前をそろえる', '写真の名前をそろえる',
		'manage_options', 'ymkrf-media-rename', 'ymkrf_mrn_page'
	);
}, 21 );

if ( ! function_exists( 'ymkrf_mrn_page' ) ) :
function ymkrf_mrn_page() {

	if ( ! current_user_can( 'manage_options' ) ) return;

	$done = null;
	if ( isset( $_POST['ymkrf_mrn_run'] ) && check_admin_referer( 'ymkrf_mrn' ) ) {
		$done = ymkrf_mrn_run( 50 );
	}

	$plan   = ymkrf_mrn_plan_voice();
	$change = array_filter( $plan, function ( $p ) { return ! $p['same'] && ! $p['drop']; } );
	$drop   = array_filter( $plan, function ( $p ) { return (bool) $p['drop']; } );
	$nono   = array_filter( $plan, function ( $p ) { return $p['no'] === ''; } );
	?>
	<div class="wrap ymkrf-mrn">
	  <h1>写真の名前をそろえる</h1>

	  <p class="ymkrf-mrn__lead">
	    「お客様の声」の写真を <code>uploads/voice/案件番号.jpg</code> の形にそろえる下見です。
	    年月のフォルダ（2026/09）は使いません。<br>
	    原本（塗りつぶし前）と公開用は<b>同じURL</b>にするので、
	    公開用がある分は<b class="ymkrf-mrn__warn">原本を消します</b>。<br>
	    案件番号が分からないものは、<b>スキャン日の古い順に 001 から</b>付け、
	    写真の「説明」にスキャン日と元のファイル名を書き残したうえで、
	    そのお客様の声を<b>下書きにもどします</b>（あとで調べられるように）。<br>
	    <b>この画面では、まだ何も変えていません。</b>一覧を見て問題がなければ、実行できるようにします。
	  </p>

	  <div class="ymkrf-mrn__sum">
	    <span>お客様の声の写真　<b><?php echo count( $plan ); ?></b> 枚</span>
	    <span>名前が変わるもの　<b><?php echo count( $change ); ?></b> 枚</span>
	    <span>消える原本　<b><?php echo count( $drop ); ?></b> 枚</span>
	    <span>案件番号が無いもの　<b><?php echo count( $nono ); ?></b> 枚</span>
	  </div>

	  <?php if ( $done ) : ?>
	    <div class="notice notice-success">
	      <p>名前を変えた <b><?php echo (int) $done['moved']; ?></b> 枚／
	         消した原本 <b><?php echo (int) $done['dropped']; ?></b> 枚／
	         できなかった <b><?php echo (int) $done['skipped']; ?></b> 枚／
	         下書きにもどしたお客様の声 <b><?php echo (int) $done['drafted']; ?></b> 件
	         <?php if ( $done['left'] ) : ?>
	           ／のこり <b><?php echo (int) $done['left']; ?></b> 枚（もう一度押してください）
	         <?php endif; ?></p>
	      <?php if ( $done['errors'] ) : ?>
	        <p><?php echo esc_html( implode( ' / ', array_slice( $done['errors'], 0, 10 ) ) ); ?></p>
	      <?php endif; ?>
	    </div>
	  <?php endif; ?>

	  <?php if ( $change || $drop ) : ?>
	    <form method="post" class="ymkrf-mrn__go"
	          onsubmit="return confirm('名前を変えます。原本は消えて、もとに戻せません。よろしいですか？');">
	      <?php wp_nonce_field( 'ymkrf_mrn' ); ?>
	      <?php $todo = count( $change ) + count( $drop ); ?>
	      <button class="button button-primary button-hero" name="ymkrf_mrn_run" value="1">
	        <?php echo ( $todo > 50 )
	          ? 'まず50枚、名前を変える（のこり ' . ( $todo - 50 ) . ' 枚）'
	          : 'この ' . $todo . ' 枚の名前を変える'; ?>
	      </button>
	      <span class="ymkrf-mrn__note">
	        いま直すものは <b><?php echo (int) $todo; ?></b> 件です。
	        <?php if ( $todo > 50 ) : ?>1回に50件ずつ進みます。のこりが出たら、もう一度押してください。<?php endif; ?>
	      </span>
	    </form>
	  <?php endif; ?>

	  <?php if ( ! $plan ) : ?>
	    <p>「お客様の声」に結びついた写真が見つかりませんでした。</p>
	  <?php else : ?>

	  <table class="widefat striped ymkrf-mrn__tbl">
	    <thead>
	      <tr>
	        <th style="width:120px">案件番号</th>
	        <th style="width:80px">種類</th>
	        <th style="width:100px">撮影日</th>
	        <th>いまのファイル名</th>
	        <th>これからどうなるか</th>
	      </tr>
	    </thead>
	    <tbody>
	      <?php foreach ( array_slice( $plan, 0, 300 ) as $p ) : ?>
	        <tr<?php echo $p['same'] ? ' class="ymkrf-mrn--same"' : ''; ?>>
	          <td><b><?php echo $p['no'] !== ''
	                ? esc_html( $p['no'] )
	                : '<span class="ymkrf-mrn__warn">番号なし</span>'; ?></b></td>
	          <td><?php echo esc_html( $p['kind'] ); ?></td>
	          <td><?php echo $p['shot'] !== ''
	                ? esc_html( $p['shot'] )
	                : '<span class="ymkrf-mrn__note">—</span>'; ?></td>
	          <td><code><?php echo esc_html( $p['from'] ); ?></code></td>
	          <td>
	            <?php if ( $p['drop'] ) : ?>
	              <span class="ymkrf-mrn__warn">消します</span>
	            <?php else : ?>
	              <code><?php echo esc_html( $p['to'] ); ?></code>
	              <?php if ( $p['same'] ) : ?><span class="ymkrf-mrn__note">（変わりません）</span><?php endif; ?>
	            <?php endif; ?>
	          </td>
	        </tr>
	      <?php endforeach; ?>
	    </tbody>
	  </table>

	  <?php if ( count( $plan ) > 300 ) : ?>
	    <p class="ymkrf-mrn__note">※ 先頭300枚だけ出しています（ぜんぶで <?php
	      echo count( $plan ); ?> 枚）。</p>
	  <?php endif; ?>

	  <?php endif; ?>
	</div>

	<style>
	  .ymkrf-mrn__lead{max-width:860px;font-size:13.5px;line-height:1.9}
	  .ymkrf-mrn__sum{display:flex;gap:18px;flex-wrap:wrap;margin:14px 0 18px;
	    padding:12px 16px;background:#fff;border:1px solid #dcdcde;border-radius:6px;
	    max-width:860px;font-size:13.5px}
	  .ymkrf-mrn__sum b{font-size:16px;color:#00782a}
	  .ymkrf-mrn__tbl code{font-size:12px}
	  .ymkrf-mrn--same{opacity:.55}
	  .ymkrf-mrn__note{color:#787878;font-size:12px}
	  .ymkrf-mrn__warn{color:#b32d2e;font-weight:700}
	  .ymkrf-mrn__go{margin:0 0 20px;padding:14px 16px;background:#fff8f5;
	    border:1px solid #fe3301;border-radius:6px;max-width:860px}
	  .ymkrf-mrn__go .ymkrf-mrn__note{margin-left:12px}
	</style>
	<?php
}
endif;
