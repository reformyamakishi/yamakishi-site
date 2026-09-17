<?php
/**
 * functions-voice-alt.php ─ 画像の説明（ALT）を自動でつけます
 * 置き場所： wp-content/themes/ymkrf/inc/functions-voice-alt.php
 *
 * ★お客様の声と施工事例の、両方をあつかいます。
 *   ファイル名は voice ではじまりますが、施工事例もここです。
 *
 * （2026/09/17 ユーザー指示
 *   「お客様の声、画像と内容からALTを自動で作ってもらえない？」
 *   「お客様の声、施工事例とも自動でつけてください。今後も自動で」）
 *
 * ■ ALT（オルト）とは
 *   画像が出ないときに代わりに出る文章で、
 *   目の見えない方の読み上げソフトも、これを読みます。
 *   Google もここを読むので、入れておくと検索に出やすくなります。
 *
 * ■ どう作るか
 *   画像そのものを機械で見わけるのではなく、
 *   そのアンケートに入っている中身（市町・工事箇所・店舗）から組み立てます。
 *   入力ずみの情報を使うので、まちがいがなく、費用もかかりません。
 *     例）小松市のお風呂リフォームをされたお客様のアンケート（仕事の通信簿）
 *
 * ■ いつ入るか
 *   ・ページに出すとき … いつでも、そのときの中身から作ります
 *   ・メディアの「代替テキスト」… 登録・保存したときに、自動で書き入れます
 *                                  （市町や写真をあとから入れても、入れなおします）
 *   ・いままでのぶん … 2026/09/17 に、まとめて入れおわりました。
 *                      その画面はメニューから外しています（URLを直接ひらけば使えます）
 *
 * ■ 名前について
 *   ALTの置き場所 … _wp_attachment_image_alt（WordPress がもともと使う欄です）
 */
if ( ! defined( 'ABSPATH' ) ) exit;


/* ============================================================
   1. 文章を組み立てます
   ============================================================ */

/** 「お風呂」「お風呂・トイレ」のような、工事箇所のならび */
function ymkrf_valt_parts( $post_id ) {
	$parts = function_exists( 'ymkrf_voice_meta_array' )
		? ymkrf_voice_meta_array( $post_id, '_ymkrf_parts' ) : array();
	$parts = array_values( array_filter( array_map( 'trim', (array) $parts ) ) );
	if ( ! $parts ) return '';
	return implode( '・', array_slice( $parts, 0, 3 ) );
}

/**
 * 画像の説明（ALT）を作ります。
 *
 * $kind … 'sheet' アンケート用紙／'work' 施工事例の写真／'thumb' 一覧の写真
 */
function ymkrf_voice_alt( $post_id, $kind = 'sheet' ) {

	$city  = trim( (string) get_post_meta( $post_id, '_ymkrf_city', true ) );
	$parts = ymkrf_valt_parts( $post_id );
	$shop  = function_exists( 'ymkrf_voice_shop_name' ) ? ymkrf_voice_shop_name( $post_id ) : '';

	/* 「小松市のお風呂リフォーム」の部分 */
	$head = '';
	if ( $city !== '' && $parts !== '' ) $head = $city . 'の' . $parts . 'リフォーム';
	elseif ( $parts !== '' )             $head = $parts . 'リフォーム';
	elseif ( $city !== '' )              $head = $city . 'のリフォーム';

	if ( $kind === 'work' ) {
		/* 施工事例の写真 */
		return $head !== ''
			? $head . 'の施工写真'
			: 'リフォームの施工写真';
	}

	/* アンケート用紙 */
	if ( $head === '' ) {
		return 'お客様アンケート「仕事の通信簿」';
	}

	$alt = $head . 'をされたお客様のアンケート（仕事の通信簿）';

	/* 店舗名が入ると、どこの工事か分かりやすくなります */
	if ( $shop !== '' ) $alt = $shop . 'が担当した' . $alt;

	return $alt;
}


/* ============================================================
   2. メディアの「代替テキスト」に書き入れます
   ============================================================ */

/** このアンケートの画像に、ALTを入れます。入れた枚数を返します */
function ymkrf_valt_apply( $post_id, $overwrite = true ) {

	$alt = ymkrf_voice_alt( $post_id, 'sheet' );
	if ( $alt === '' ) return 0;

	$ids = array(
		(int) get_post_meta( $post_id, '_ymkrf_survey_pub_id', true ),
		(int) get_post_meta( $post_id, '_ymkrf_survey_id', true ),
	);

	$n = 0;
	foreach ( array_unique( array_filter( $ids ) ) as $att ) {
		$now = (string) get_post_meta( $att, '_wp_attachment_image_alt', true );
		if ( ! $overwrite && $now !== '' ) continue;
		if ( $now === $alt ) continue;
		update_post_meta( $att, '_wp_attachment_image_alt', $alt );
		$n++;
	}
	return $n;
}

/* 保存したときに、入れなおします */
add_action( 'save_post_ymkrf_voice', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;
	ymkrf_valt_apply( $post_id );
}, 80 );


/* ============================================================
   3. 施工事例（Before / After）
   ============================================================ */

/**
 * 施工事例の画像の説明（ALT）を作ります。
 *
 * $which … 'before' 施工前／'after' 施工後／'during' 工事中／'' ふつうの写真
 * $no …… 同じまとまりの中で何枚目か（2枚目からは「（2枚目）」と付けます）
 *         同じ説明がいくつも並ぶと、読み上げソフトが同じ言葉をくり返すためです。
 */
function ymkrf_works_alt( $post_id, $which = '', $no = 0 ) {

	$cats = function_exists( 'ymkrf_works_term_names' )
		? ymkrf_works_term_names( $post_id, 'ymkrf_works_cat' ) : array();
	$area = function_exists( 'ymkrf_works_term_names' )
		? ymkrf_works_term_names( $post_id, 'ymkrf_works_area' ) : array();

	$part = $cats ? implode( '・', array_slice( $cats, 0, 2 ) ) : '';
	$city = $area ? (string) $area[0] : '';

	/* 「小松市のキッチンリフォーム」の部分 */
	if ( $city !== '' && $part !== '' ) $head = $city . 'の' . $part . 'リフォーム';
	elseif ( $part !== '' )             $head = $part . 'リフォーム';
	elseif ( $city !== '' )             $head = $city . 'のリフォーム';
	else                                $head = 'リフォーム';

	$tail = ( (int) $no >= 2 ) ? '（' . (int) $no . '枚目）' : '';

	if ( $which === 'before' ) return $head . 'の施工前のようす' . $tail;
	if ( $which === 'after' )  return $head . 'の施工後のようす' . $tail;
	if ( $which === 'during' ) return $head . 'の工事中のようす' . $tail;

	return $head . 'の施工写真' . $tail;
}

/** この施工事例の写真ぜんぶに、ALTを入れます。入れた枚数を返します */
function ymkrf_walt_apply( $post_id ) {

	if ( ! function_exists( 'ymkrf_works_photos' ) ) return 0;

	$n = 0;
	foreach ( array( 'before', 'during', 'after' ) as $which ) {
		$i = 0;
		foreach ( (array) ymkrf_works_photos( $post_id, $which ) as $att ) {
			$att = (int) $att;
			if ( ! $att ) continue;
			$i++;
			$alt = ymkrf_works_alt( $post_id, $which, $i );
			if ( (string) get_post_meta( $att, '_wp_attachment_image_alt', true ) === $alt ) continue;
			update_post_meta( $att, '_wp_attachment_image_alt', $alt );
			$n++;
		}
	}

	/* アイキャッチ（一覧に出る写真） */
	$th = (int) get_post_thumbnail_id( $post_id );
	if ( $th ) {
		$alt = ymkrf_works_alt( $post_id, '' );
		if ( (string) get_post_meta( $th, '_wp_attachment_image_alt', true ) !== $alt ) {
			update_post_meta( $th, '_wp_attachment_image_alt', $alt );
			$n++;
		}
	}
	return $n;
}

/* 保存したときに、入れなおします */
add_action( 'save_post_ymkrf_works', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;
	ymkrf_walt_apply( $post_id );
}, 80 );


/* ============================================================
   4. 登録したそのときに、かならず入るようにします
   ------------------------------------------------------------
   （2026/09/17 ユーザー「今後は、アンケートと施工事例を登録した際に、
     それに使用されている写真に自動でaltを付けてほしい」）

   保存のときだけを見ていると、取り込みの画面のように
   「先に記事を作って、あとから市町や写真を入れる」つくりのときに、
   まだ中身が入っていない状態でALTを作ってしまいます。

   そこで、ALTのもとになるものが変わったら、そのたびに入れなおします。
     ・お客様の声 … 市町／工事箇所／店舗／アンケート画像
     ・施工事例 … 部位・エリアの分類／Before・施工中・Afterの写真／アイキャッチ
   ============================================================ */

/** ALTのもとになる入力欄が変わったら、入れなおします */
function ymkrf_alt_meta_touch( $mid, $post_id, $key ) {

	static $busy = false;
	if ( $busy ) return;

	$voice = array( '_ymkrf_city', '_ymkrf_parts', '_ymkrf_shop',
	                '_ymkrf_survey_id', '_ymkrf_survey_pub_id' );
	$works = array( '_ymkrf_before_imgs', '_ymkrf_during_imgs', '_ymkrf_after_imgs',
	                '_ymkrf_before_img', '_thumbnail_id' );

	$type = get_post_type( $post_id );

	$busy = true;
	if ( $type === 'ymkrf_voice' && in_array( $key, $voice, true ) ) {
		ymkrf_valt_apply( $post_id );
	} elseif ( $type === 'ymkrf_works' && in_array( $key, $works, true ) ) {
		ymkrf_walt_apply( $post_id );
	}
	$busy = false;
}
add_action( 'added_post_meta',   'ymkrf_alt_meta_touch', 20, 3 );
add_action( 'updated_post_meta', 'ymkrf_alt_meta_touch', 20, 3 );

/** 施工事例の「部位」「エリア」を付けかえたときも、入れなおします */
add_action( 'set_object_terms', function ( $post_id, $terms, $tt_ids, $taxonomy ) {

	if ( ! in_array( $taxonomy, array( 'ymkrf_works_cat', 'ymkrf_works_area' ), true ) ) return;
	if ( get_post_type( $post_id ) !== 'ymkrf_works' ) return;

	ymkrf_walt_apply( $post_id );
}, 20, 4 );


/* ============================================================
   5. いままでのぶんを、まとめて入れる画面
   ============================================================ */
/*
 * ★この画面は、ふだんは出しません★
 *   いままでのぶんを入れおわったので、メニューから外しました
 *   （2026/09/17 ユーザー「押しました。必要なければ、その画面は非表示にして」）。
 *
 *   これからのぶんは、登録・保存したときに自動で入ります。
 *   もういちど使いたくなったときは、このURLで開けます。
 *     /yam-admin/edit.php?post_type=ymkrf_voice&page=ymkrf-voice-alt
 */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=ymkrf_voice',
		'画像の説明をつける', '画像の説明をつける',
		'manage_options', 'ymkrf-voice-alt', 'ymkrf_valt_page'
	);
}, 32 );

/* メニューからだけ消します。URLを直接ひらけば、いつでも使えます */
add_action( 'admin_menu', function () {
	remove_submenu_page( 'edit.php?post_type=ymkrf_voice', 'ymkrf-voice-alt' );
}, 999 );

function ymkrf_valt_page() {

	if ( ! current_user_can( 'manage_options' ) ) return;

	@set_time_limit( 300 );

	$run  = ( isset( $_POST['ymkrf_valt_run'] ) && check_admin_referer( 'ymkrf_valt' ) );
	$done = 0;

	/* ---- お客様の声 ---- */
	$ids = get_posts( array(
		'post_type'      => 'ymkrf_voice',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );

	$rows = array();
	foreach ( (array) $ids as $id ) {

		$att = (int) get_post_meta( $id, '_ymkrf_survey_pub_id', true );
		if ( ! $att ) $att = (int) get_post_meta( $id, '_ymkrf_survey_id', true );
		if ( ! $att ) continue;

		$now  = (string) get_post_meta( $att, '_wp_attachment_image_alt', true );
		$want = ymkrf_voice_alt( $id, 'sheet' );

		if ( $now === $want ) continue;

		if ( $run ) {
			$done += ymkrf_valt_apply( $id );
		} else {
			$rows[ $id ] = array( 'kind' => 'お客様の声', 'now' => $now, 'want' => $want );
		}
	}

	/* ---- 施工事例 ----
	   （2026/09/17 ユーザー指示「お客様の声、施工事例とも自動でつけてください」）
	   写真が何枚もあるので、1件につき「入れる説明」の代表として施工後のぶんを出します。 */
	$wids = get_posts( array(
		'post_type'      => 'ymkrf_works',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );

	$wrows = array();
	foreach ( (array) $wids as $id ) {

		if ( $run ) {
			$n = ymkrf_walt_apply( $id );
			$done += $n;
			continue;
		}

		/* 入れかえるものが1枚でもあるか、見るだけ */
		$need = false;
		if ( function_exists( 'ymkrf_works_photos' ) ) {
			foreach ( array( 'before', 'during', 'after' ) as $which ) {
				$i = 0;
				foreach ( (array) ymkrf_works_photos( $id, $which ) as $att ) {
					$i++;
					$alt = ymkrf_works_alt( $id, $which, $i );
					if ( (string) get_post_meta( (int) $att, '_wp_attachment_image_alt', true ) !== $alt ) {
						$need = true; break 2;
					}
				}
			}
		}
		$th = (int) get_post_thumbnail_id( $id );
		if ( ! $need && $th &&
		     (string) get_post_meta( $th, '_wp_attachment_image_alt', true ) !== ymkrf_works_alt( $id, '' ) ) {
			$need = true;
		}

		if ( $need ) {
			$wrows[ $id ] = array(
				'kind' => '施工事例',
				'now'  => '',
				'want' => ymkrf_works_alt( $id, 'after' ),
			);
		}
	}

	$all = $rows + $wrows;
	?>
	<div class="wrap">
	  <h1>画像の説明（ALT）をつける</h1>

	  <p style="max-width:900px;font-size:13.5px;line-height:1.9">
	    <b>お客様の声のアンケート画像</b>と<b>施工事例のBefore/After写真</b>の「代替テキスト」を、
	    そのページの中身から自動で作って入れます。<br>
	    画像が出ないときに代わりに出る文章で、目の見えない方の読み上げソフトも、これを読みます。
	    Google もここを読むので、入れておくと検索に出やすくなります。
	  </p>

	  <p style="max-width:900px;font-size:13.5px;line-height:1.9;color:#50575e">
	    ※ 写真そのものを機械で見わけるのではなく、<b>入力ずみの市町・工事箇所・店舗</b>から組み立てます。
	    施工事例は、施工前・工事中・施工後で言いかたを変えます。
	    まちがいがなく、費用もかかりません。<br>
	    ※ これから登録するぶんは、保存したときに自動で入ります。この画面は、いままでのぶん用です。
	  </p>

	  <?php if ( $run ) : ?>
	    <div class="notice notice-success"><p>
	      <b><?php echo (int) $done; ?></b> 枚の写真に、画像の説明を入れました。
	    </p></div>
	  <?php endif; ?>

	  <div style="display:flex;gap:18px;margin:14px 0 16px;padding:12px 16px;background:#fff;
	              border:1px solid #dcdcde;border-radius:6px;max-width:900px;font-size:13.5px">
	    <span>お客様の声　<b><?php echo count( $ids ); ?></b> 件</span>
	    <span>施工事例　<b><?php echo count( $wids ); ?></b> 件</span>
	    <span>入れかえるもの　<b style="color:#00782a"><?php echo count( $all ); ?></b> 件</span>
	  </div>

	  <?php if ( $all ) : ?>
	    <form method="post">
	      <?php wp_nonce_field( 'ymkrf_valt' ); ?>
	      <button class="button button-primary button-hero" name="ymkrf_valt_run" value="1">
	        <?php echo count( $all ); ?> 件に画像の説明を入れる
	      </button>
	    </form>

	    <p style="font-size:12.5px;color:#50575e;margin-top:14px">下は、はじめの100件です。</p>
	    <table class="widefat striped">
	      <thead><tr>
	        <th style="width:90px">種類</th>
	        <th style="width:240px">ページ</th>
	        <th style="width:220px">いまの説明</th>
	        <th>入れる説明</th>
	      </tr></thead>
	      <tbody>
	        <?php foreach ( array_slice( $all, 0, 100, true ) as $id => $r ) : ?>
	          <tr>
	            <td><?php echo esc_html( $r['kind'] ); ?></td>
	            <td><a href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>"><?php
	              echo esc_html( get_the_title( $id ) ?: '（名前なし）' ); ?></a></td>
	            <td style="color:#8a8a8a"><?php
	              echo $r['now'] !== '' ? esc_html( $r['now'] ) : '（空）'; ?></td>
	            <td><b><?php echo esc_html( $r['want'] ); ?></b></td>
	          </tr>
	        <?php endforeach; ?>
	      </tbody>
	    </table>
	  <?php else : ?>
	    <p>ぜんぶ入っています。することはありません。</p>
	  <?php endif; ?>
	</div>
	<?php
}
