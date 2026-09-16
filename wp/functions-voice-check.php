<?php
/**
 * functions-voice-check.php ─ お客様の声の「要確認」をさがします
 *
 * 置き場所： wp-content/themes/ymkrf/inc/functions-voice-check.php
 *
 * （2026/09/16 ユーザー指示
 *   「お客様の声のデータで不備（お客様情報が掲載されているなど）があれば、
 *     要確認という赤文字をのこして未公開にしておいて」）
 *
 * 見つけたら
 *   ・一覧のタイトルの横に、赤い「要確認」が出ます
 *   ・編集画面の上に、理由が赤い枠で出ます
 *   ・公開中のものは、下書きにもどします
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'YMKRF_VCHK_META', '_ymkrf_needs_check' );
define( 'YMKRF_VCHK_OK',   '_ymkrf_check_ok' );   /* 見て、このままでよいと決めたもの */


/** そのお客様の声に、直したほうがよいところがあるか調べます */
if ( ! function_exists( 'ymkrf_vchk_reasons' ) ) :
function ymkrf_vchk_reasons( $post_id ) {

	$post_id = (int) $post_id;
	$out     = array();
	$g       = function ( $k ) use ( $post_id ) {
		return trim( (string) get_post_meta( $post_id, $k, true ) );
	};

	/* ---- ① お客様のお名前が、そのまま出てしまう形 ----
	   表示名は「金沢市　K様」の形ですが、市町もイニシャルも空のときは
	   お客様のお名前をそのまま出す作りになっています。 */
	$city = $g( '_ymkrf_city' );
	$ini  = $g( '_ymkrf_initial' );
	$cust = $g( '_ymkrf_customer' );
	if ( $city === '' && $ini === '' && $cust !== '' ) {
		$out[] = 'お客様のお名前がページに出てしまいます（市町とイニシャルが空です）';
	}

	/* ---- ② イニシャルの欄に、お名前が入っている ---- */
	if ( $ini !== '' && preg_match( '/[一-龥ぁ-んァ-ヶ]{2,}/u', $ini ) ) {
		$out[] = 'イニシャルの欄に、お名前が入っているかもしれません（' . $ini . '）';
	}

	/* ---- ③〜⑥ 文章の中に、個人情報らしいものがないか ---- */
	$texts = array( get_the_title( $post_id ), (string) get_post_field( 'post_content', $post_id ) );
	foreach ( array( '_ymkrf_trouble', '_ymkrf_after', '_ymkrf_comment', '_ymkrf_recommend' ) as $k ) {
		$texts[] = $g( $k );
	}
	$all = implode( "\n", $texts );

	if ( preg_match( '/0\d{1,3}[-‐ー－(]?\d{2,4}[-‐ー－)]?\d{3,4}/u', $all ) ) {
		$out[] = '文章の中に、電話番号らしい数字があります';
	}
	if ( preg_match( '/[\w.+-]+@[\w-]+\.[\w.-]+/u', $all ) ) {
		$out[] = '文章の中に、メールアドレスらしいものがあります';
	}
	if ( preg_match( '/[0-9０-９]+\s*(丁目|番地|番\s*[0-9０-９]*\s*号)/u', $all ) ) {
		$out[] = '文章の中に、住所（番地）らしいものがあります';
	}
	if ( preg_match( '/[一-龥]{2,4}\s*(様|さん)/u', $all ) ) {
		$out[] = '文章の中に、お名前（〇〇様）らしいものがあります';
	}

	/* ---- ⑦ 公開用の画像が無い ---- */
	$sid = (int) $g( '_ymkrf_survey_id' );
	$pid = (int) $g( '_ymkrf_survey_pub_id' );
	if ( $sid && ! $pid ) {
		$out[] = '塗りつぶした公開用の画像がありません（原本しかありません）';
	}

	/* ---- ⑧ 案件番号が無い ---- */
	if ( $g( '_ymkrf_case_no' ) === '' ) {
		$out[] = '案件番号が入っていません';
	}

	/* ---- ⑨ クレームのアンケート ----
	   （2026/09/16 ユーザー指示「クレームと書いてあるものは、
	     赤字で要確認にして下書きなどにしておいてください」）
	   もとのファイル名に「クレーム」と入っていたものに、印が付いています。 */
	if ( $g( '_ymkrf_claim' ) === '1' ) {
		$out[] = 'クレームのアンケートです。出してよいか確認してください';
	}

	return $out;
}
endif;


/* ------------------------------------------------------------
   一覧のタイトルの横に、赤い「要確認」を出します
   ------------------------------------------------------------ */
add_filter( 'display_post_states', function ( $states, $post ) {
	if ( ! $post || $post->post_type !== 'ymkrf_voice' ) return $states;
	$r = get_post_meta( $post->ID, YMKRF_VCHK_META, true );
	if ( ! $r ) return $states;
	$states['ymkrf_check'] =
		'<span style="color:#b32d2e;font-weight:700">要確認</span>';
	return $states;
}, 10, 2 );

/* ------------------------------------------------------------
   編集画面の上に、理由を赤い枠で出します
   ------------------------------------------------------------ */
add_action( 'admin_notices', function () {

	$s = get_current_screen();
	if ( ! $s || $s->post_type !== 'ymkrf_voice' || $s->base !== 'post' ) return;
	if ( empty( $GLOBALS['post'] ) ) return;

	$id = (int) $GLOBALS['post']->ID;
	$r  = get_post_meta( $id, YMKRF_VCHK_META, true );

	/* 「確認しました」にしたものは、灰色の小さな案内だけにします */
	if ( get_post_meta( $id, YMKRF_VCHK_OK, true ) === '1' ) {
		$back = wp_nonce_url(
			add_query_arg( array( 'ymkrf_vchk_ok' => '0', 'post' => $id ), admin_url( 'post.php' ) ),
			'ymkrf_vchk_ok_' . $id );
		?>
		<div class="notice notice-info" style="margin-top:14px">
		  <p style="font-size:13.5px">このお客様の声は「<b>確認ずみ</b>」にしてあります。
		    <a href="<?php echo esc_url( $back ); ?>">もう一度調べる</a></p>
		</div>
		<?php
		return;
	}

	if ( ! is_array( $r ) || ! $r ) return;

	$ok = wp_nonce_url(
		add_query_arg( array( 'ymkrf_vchk_ok' => '1', 'post' => $id ), admin_url( 'post.php' ) ),
		'ymkrf_vchk_ok_' . $id );
	/* 保存した直後だけ、文言を強くします
	   （2026/09/16 ユーザー指示「登録完了の際に警告を出して」） */
	$just = isset( $_GET['message'] );
	?>
	<div class="notice notice-error" style="margin-top:14px;border-left-width:6px">
	  <?php
	  $drafted = get_transient( 'ymkrf_vchk_drafted_' . $id );
	  if ( $drafted ) delete_transient( 'ymkrf_vchk_drafted_' . $id );
	  ?>
	  <?php if ( $just ) : ?>
	    <p style="font-size:15px;font-weight:700;color:#b32d2e;margin-bottom:4px">
	      登録しましたが、お客様の情報らしいものが見つかりました
	    </p>
	    <p style="font-size:13.5px;margin-top:0">
	      <?php if ( $drafted ) : ?>
	        念のため、<b>公開をやめて下書きにもどしました。</b>
	        下のところを直してから、あらためて公開してください。
	      <?php else : ?>
	        このまま公開すると、下の内容がページに出るおそれがあります。
	      <?php endif; ?>
	    </p>
	  <?php else : ?>
	    <p style="font-size:14px"><b style="color:#b32d2e">要確認</b>
	      　下のところを直してから、公開してください。</p>
	  <?php endif; ?>
	  <ul style="margin:0 0 10px 22px;list-style:disc;font-size:13.5px;line-height:1.9">
	    <?php foreach ( $r as $one ) : ?>
	      <li><?php echo esc_html( $one ); ?></li>
	    <?php endforeach; ?>
	  </ul>
	  <p style="margin:0 0 12px">
	    <a class="button" href="<?php echo esc_url( $ok ); ?>">確認しました（このままでよい）</a>
	    <span style="margin-left:10px;color:#50575e;font-size:12.5px">
	      直せないもの（古くて案件番号が分からない、など）は、これで赤い表示を消せます。
	    </span>
	  </p>
	</div>
	<?php
} );

/* 「確認しました」「もう一度調べる」を押したとき */
add_action( 'admin_init', function () {

	if ( ! isset( $_GET['ymkrf_vchk_ok'], $_GET['post'] ) ) return;

	$id = (int) $_GET['post'];
	if ( ! $id || ! current_user_can( 'edit_post', $id ) ) return;
	check_admin_referer( 'ymkrf_vchk_ok_' . $id );

	if ( $_GET['ymkrf_vchk_ok'] === '1' ) {
		update_post_meta( $id, YMKRF_VCHK_OK, '1' );
		delete_post_meta( $id, YMKRF_VCHK_META );
	} else {
		delete_post_meta( $id, YMKRF_VCHK_OK );
		$r = ymkrf_vchk_reasons( $id );
		if ( $r ) update_post_meta( $id, YMKRF_VCHK_META, $r );
	}

	wp_safe_redirect( admin_url( 'post.php?post=' . $id . '&action=edit' ) );
	exit;
} );

/* 保存したら調べ直し、見つかったら下書きにもどします
   （2026/09/16 ユーザー指示「不備や要確認事項があれば、下書きや未公開にして」） */
add_action( 'save_post_ymkrf_voice', function ( $post_id ) {

	static $busy = false;
	if ( $busy ) return;
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) return;

	/* 「確認しました」にしたものは、そっとしておきます */
	if ( get_post_meta( $post_id, YMKRF_VCHK_OK, true ) === '1' ) {
		delete_post_meta( $post_id, YMKRF_VCHK_META );
		return;
	}

	$r = ymkrf_vchk_reasons( $post_id );

	if ( ! $r ) {
		delete_post_meta( $post_id, YMKRF_VCHK_META );
		return;
	}

	update_post_meta( $post_id, YMKRF_VCHK_META, $r );

	/* 公開中なら、いったん下書きにもどします */
	if ( get_post_status( $post_id ) === 'publish' ) {
		$busy = true;
		wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
		$busy = false;
		set_transient( 'ymkrf_vchk_drafted_' . $post_id, 1, 60 );
	}
}, 60 );


/* ------------------------------------------------------------
   さがす画面（お客様の声 ＞ 要確認をさがす）
   ------------------------------------------------------------ */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=ymkrf_voice',
		'要確認をさがす', '要確認をさがす',
		'manage_options', 'ymkrf-voice-check', 'ymkrf_vchk_page'
	);
}, 30 );

if ( ! function_exists( 'ymkrf_vchk_page' ) ) :
function ymkrf_vchk_page() {

	if ( ! current_user_can( 'manage_options' ) ) return;

	@set_time_limit( 300 );

	$run  = ( isset( $_POST['ymkrf_vchk_run'] ) && check_admin_referer( 'ymkrf_vchk' ) );
	$done = array( 'marked' => 0, 'drafted' => 0, 'cleared' => 0 );

	$ids = get_posts( array(
		'post_type'      => 'ymkrf_voice',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );

	$found = array();

	foreach ( (array) $ids as $id ) {

		/* 「確認しました」にしたものは、もう出しません */
		if ( get_post_meta( $id, YMKRF_VCHK_OK, true ) === '1' ) continue;

		$r = ymkrf_vchk_reasons( $id );

		if ( $r ) {
			$found[ $id ] = $r;
			if ( $run ) {
				update_post_meta( $id, YMKRF_VCHK_META, $r );
				$done['marked']++;
				if ( get_post_status( $id ) === 'publish' ) {
					wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ) );
					$done['drafted']++;
				}
			}
		} elseif ( $run && get_post_meta( $id, YMKRF_VCHK_META, true ) ) {
			delete_post_meta( $id, YMKRF_VCHK_META );
			$done['cleared']++;
		}
	}

	/* 理由ごとの件数 */
	$tally = array();
	foreach ( $found as $r ) foreach ( $r as $one ) {
		if ( ! isset( $tally[ $one ] ) ) $tally[ $one ] = 0;
		$tally[ $one ]++;
	}
	arsort( $tally );
	?>
	<div class="wrap ymkrf-vchk">
	  <h1>要確認をさがす</h1>

	  <p class="ymkrf-vchk__lead">
	    お客様の声を1件ずつ調べて、<b>お客様の情報が出てしまうもの</b>や、
	    入力が足りないものをさがします。<br>
	    ボタンを押すと、見つかったものに<b class="ymkrf-vchk__red">要確認</b>の印を付け、
	    <b>公開中のものは下書きにもどします</b>。
	  </p>

	  <?php if ( $run ) : ?>
	    <div class="notice notice-success"><p>
	      要確認にした <b><?php echo (int) $done['marked']; ?></b> 件／
	      下書きにもどした <b><?php echo (int) $done['drafted']; ?></b> 件／
	      印を外した <b><?php echo (int) $done['cleared']; ?></b> 件
	    </p></div>
	  <?php endif; ?>

	  <div class="ymkrf-vchk__sum">
	    <span>調べた件数　<b><?php echo count( $ids ); ?></b> 件</span>
	    <span>見つかった件数　<b class="ymkrf-vchk__red"><?php echo count( $found ); ?></b> 件</span>
	  </div>

	  <?php if ( $tally ) : ?>
	    <table class="widefat striped" style="max-width:860px;margin-bottom:18px">
	      <thead><tr><th>理由</th><th style="width:90px">件数</th></tr></thead>
	      <tbody>
	        <?php foreach ( $tally as $k => $n ) : ?>
	          <tr><td><?php echo esc_html( $k ); ?></td><td><?php echo (int) $n; ?></td></tr>
	        <?php endforeach; ?>
	      </tbody>
	    </table>
	  <?php endif; ?>

	  <?php if ( $found ) : ?>
	    <form method="post" class="ymkrf-vchk__go">
	      <?php wp_nonce_field( 'ymkrf_vchk' ); ?>
	      <button class="button button-primary button-hero" name="ymkrf_vchk_run" value="1">
	        <?php echo count( $found ); ?> 件に「要確認」を付けて、下書きにもどす
	      </button>
	    </form>

	    <table class="widefat striped">
	      <thead><tr>
	        <th style="width:260px">お客様の声</th>
	        <th style="width:90px">いまの状態</th>
	        <th>直すところ</th>
	      </tr></thead>
	      <tbody>
	        <?php foreach ( array_slice( $found, 0, 300, true ) as $id => $r ) : ?>
	          <tr>
	            <td><a href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>"><?php
	              echo esc_html( get_the_title( $id ) ?: '（名前なし）' ); ?></a></td>
	            <td><?php echo esc_html( get_post_status( $id ) === 'publish' ? '公開中' : '下書きなど' ); ?></td>
	            <td><?php echo esc_html( implode( '／', $r ) ); ?></td>
	          </tr>
	        <?php endforeach; ?>
	      </tbody>
	    </table>
	    <?php if ( count( $found ) > 300 ) : ?>
	      <p class="ymkrf-vchk__note">※ 先頭300件だけ出しています。</p>
	    <?php endif; ?>
	  <?php else : ?>
	    <p>直すところは見つかりませんでした。</p>
	  <?php endif; ?>
	</div>

	<style>
	  .ymkrf-vchk__lead{max-width:860px;font-size:13.5px;line-height:1.9}
	  .ymkrf-vchk__red{color:#b32d2e}
	  .ymkrf-vchk__sum{display:flex;gap:18px;margin:14px 0 18px;padding:12px 16px;
	    background:#fff;border:1px solid #dcdcde;border-radius:6px;max-width:860px;font-size:13.5px}
	  .ymkrf-vchk__sum b{font-size:16px}
	  .ymkrf-vchk__go{margin:0 0 18px;padding:14px 16px;background:#fff8f5;
	    border:1px solid #fe3301;border-radius:6px;max-width:860px}
	  .ymkrf-vchk__note{color:#787878;font-size:12px}
	</style>
	<?php
}
endif;


/* ============================================================
   アンケート画像の解像度が低いものに、印をつけます
   （2026/09/16 ユーザー指示
     「解像度の低いアンケートは、私が時間あるときに手動で入れ直し
       しますので。写真と赤字で書いておいてもらえますか？」）

   ★公開は止めません。1,590件が下書きになってしまうためです。
     一覧に写真と赤字を出して、目で分かるようにするだけです。

   いまの画像の幅
     旧サイトから取り込んだぶん … 1600px（これが上限）
     新しい画面で読み取ったぶん … 2400px（原本が2560pxあるため）
   ============================================================ */

if ( ! defined( 'YMKRF_VCHK_MINW' ) ) define( 'YMKRF_VCHK_MINW', 2000 );

/** アンケート画像の幅。足りていれば 0 を返します */
if ( ! function_exists( 'ymkrf_vchk_lowres' ) ) :
function ymkrf_vchk_lowres( $post_id ) {

	$att = (int) get_post_meta( $post_id, '_ymkrf_survey_pub_id', true );
	if ( ! $att ) $att = (int) get_post_meta( $post_id, '_ymkrf_survey_id', true );
	if ( ! $att ) return 0;

	$m = wp_get_attachment_metadata( $att );
	$w = ! empty( $m['width'] ) ? (int) $m['width'] : 0;
	if ( ! $w ) return 0;

	return ( $w < YMKRF_VCHK_MINW ) ? $w : 0;
}
endif;

/* 一覧に「アンケート」の列を足します（写真と赤字） */
add_filter( 'manage_ymkrf_voice_posts_columns', function ( $cols ) {
	$out = array();
	foreach ( $cols as $k => $v ) {
		$out[ $k ] = $v;
		if ( $k === 'title' ) $out['ymkrf_sheet'] = 'アンケート';
	}
	if ( ! isset( $out['ymkrf_sheet'] ) ) $out['ymkrf_sheet'] = 'アンケート';
	return $out;
}, 30 );

add_action( 'manage_ymkrf_voice_posts_custom_column', function ( $col, $post_id ) {

	if ( $col !== 'ymkrf_sheet' ) return;

	$att = (int) get_post_meta( $post_id, '_ymkrf_survey_pub_id', true );
	if ( ! $att ) $att = (int) get_post_meta( $post_id, '_ymkrf_survey_id', true );

	if ( ! $att ) {
		echo '<span style="color:#b32d2e;font-weight:700">画像なし</span>';
		return;
	}

	$url = wp_get_attachment_image_url( $att, 'thumbnail' );
	$m   = wp_get_attachment_metadata( $att );
	$w   = ! empty( $m['width'] ) ? (int) $m['width'] : 0;
	$low = ymkrf_vchk_lowres( $post_id );

	echo '<div style="display:flex;gap:8px;align-items:flex-start">';
	if ( $url ) {
		printf(
			'<a href="%s" target="_blank" rel="noopener"><img src="%s" width="60" height="42" '
			. 'style="width:60px;height:auto;border:1px solid #dcdcde;border-radius:3px" alt=""></a>',
			esc_url( wp_get_attachment_url( $att ) ), esc_url( $url )
		);
	}
	echo '<span style="font-size:12px;line-height:1.6">';
	if ( $low ) {
		echo '<b style="color:#b32d2e">解像度が低い</b><br>'
		   . '<span style="color:#b32d2e">' . (int) $low . 'px<br>スキャンし直し</span>';
	} else {
		echo '<span style="color:#6b625c">' . (int) $w . 'px</span>';
	}
	echo '</span></div>';
}, 10, 2 );

/* 編集画面にも、写真と赤字で出します */
add_action( 'admin_notices', function () {

	$s = get_current_screen();
	if ( ! $s || $s->post_type !== 'ymkrf_voice' || $s->base !== 'post' ) return;
	if ( empty( $GLOBALS['post'] ) ) return;

	$id  = (int) $GLOBALS['post']->ID;
	$low = ymkrf_vchk_lowres( $id );
	if ( ! $low ) return;

	$att = (int) get_post_meta( $id, '_ymkrf_survey_pub_id', true );
	if ( ! $att ) $att = (int) get_post_meta( $id, '_ymkrf_survey_id', true );
	$url = $att ? wp_get_attachment_image_url( $att, 'medium' ) : '';
	?>
	<div class="notice notice-warning" style="margin-top:14px;border-left-color:#b32d2e;border-left-width:6px">
	  <div style="display:flex;gap:14px;align-items:flex-start;padding:6px 0 10px">
	    <?php if ( $url ) : ?>
	      <a href="<?php echo esc_url( wp_get_attachment_url( $att ) ); ?>" target="_blank" rel="noopener">
	        <img src="<?php echo esc_url( $url ); ?>" style="width:160px;height:auto;
	             border:1px solid #dcdcde;border-radius:4px" alt="">
	      </a>
	    <?php endif; ?>
	    <div>
	      <p style="font-size:15px;font-weight:700;color:#b32d2e;margin:0 0 6px">
	        アンケート画像の解像度が低いです（幅 <?php echo (int) $low; ?>px）
	      </p>
	      <p style="font-size:13.5px;margin:0;line-height:1.9">
	        大きく拡大すると文字がにじみます。お手すきのときに、
	        <b>紙のアンケートをスキャンし直して入れ直してください。</b><br>
	        入れ直すと <?php echo (int) YMKRF_VCHK_MINW; ?>px 以上になり、この赤い表示は消えます。<br>
	        <span style="color:#50575e;font-size:12.5px">
	          ※ 公開は止めていません。このままでも表示はできます。
	        </span>
	      </p>
	    </div>
	  </div>
	</div>
	<?php
} );
