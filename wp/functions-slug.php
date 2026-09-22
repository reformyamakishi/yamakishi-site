<?php
/**
 * functions-slug.php ─ 商品の題名とURLを自動でととのえます
 * 置き場所： wp-content/themes/ymkrf/inc/functions-slug.php
 *
 * （2026/09/22 ユーザー指示
 *   「タイトル自体不要な気がする」
 *   「タイトルは自動で、SEOに強く、英数字でってできる？」
 *   「はい、ではお願いします。全商品」）
 *
 * ■ 決めたこと
 *
 *   題名 … 「商品名」の欄からそのまま（保存のときに自動でそろえます）。
 *           ※題名はダッシュボードの一覧・検索・施工事例の「使った商品」に出ます。
 *             お客様のページに出るのは「商品名」の欄のほうです。
 *
 *   URL  … 分類 ＋ 商品名（英数字）　例： /products/kitchen-rakuera/
 *           コンロ・IHだけは、あいだに「ガスかIHか」が入ります
 *           　　例： /products/cooktop-gas-n3wv6m/　/products/cooktop-ih-kz-k33xst/
 *           ・商品名に英字があれば、それを使います（V-style → v-style）
 *           ・無ければカタカナをローマ字にします（オフローラ → ofurora）
 *             このときは「要確認」の赤い印を出します。正しい綴りに直してください
 *           ・型番が入っているときは、型番をそのまま使います（N3WV6M → n3wv6m）
 *
 *   ★ URLは、一度公開すると変えられません（変えると前のURLが404になります）。
 *      本番公開の前にそろえてください。
 *
 * ■ 自分で入れたURLは、二度と勝手に変えません
 *   URLの欄を手で直すと _ymkrf_slug_auto が 0 になり、以後は自動でさわりません。
 *
 * ■ すでに登録ずみの商品
 *   商品 ＞ URLの見なおし　で、まとめて新しい形にできます。
 */
if ( ! defined( 'ABSPATH' ) ) exit;


/* ============================================================
   1. カタカナ・ひらがな → ローマ字
   ============================================================ */

/** ローマ字の対応表（拗音は2文字のものを先に見ます） */
function ymkrf_roman_map() {
	return array(
		'キャ'=>'kya','キュ'=>'kyu','キョ'=>'kyo','シャ'=>'sha','シュ'=>'shu','ショ'=>'sho',
		'チャ'=>'cha','チュ'=>'chu','チョ'=>'cho','ニャ'=>'nya','ニュ'=>'nyu','ニョ'=>'nyo',
		'ヒャ'=>'hya','ヒュ'=>'hyu','ヒョ'=>'hyo','ミャ'=>'mya','ミュ'=>'myu','ミョ'=>'myo',
		'リャ'=>'rya','リュ'=>'ryu','リョ'=>'ryo','ギャ'=>'gya','ギュ'=>'gyu','ギョ'=>'gyo',
		'ジャ'=>'ja','ジュ'=>'ju','ジョ'=>'jo','ビャ'=>'bya','ビュ'=>'byu','ビョ'=>'byo',
		'ピャ'=>'pya','ピュ'=>'pyu','ピョ'=>'pyo',
		'ヴァ'=>'va','ヴィ'=>'vi','ヴェ'=>'ve','ヴォ'=>'vo',
		'ファ'=>'fa','フィ'=>'fi','フェ'=>'fe','フォ'=>'fo',
		'ティ'=>'ti','ディ'=>'di','デュ'=>'du','トゥ'=>'tu',
		'ウィ'=>'wi','ウェ'=>'we','ウォ'=>'wo','シェ'=>'she','ジェ'=>'je','チェ'=>'che',
		'ア'=>'a','イ'=>'i','ウ'=>'u','エ'=>'e','オ'=>'o',
		'カ'=>'ka','キ'=>'ki','ク'=>'ku','ケ'=>'ke','コ'=>'ko',
		'サ'=>'sa','シ'=>'shi','ス'=>'su','セ'=>'se','ソ'=>'so',
		'タ'=>'ta','チ'=>'chi','ツ'=>'tsu','テ'=>'te','ト'=>'to',
		'ナ'=>'na','ニ'=>'ni','ヌ'=>'nu','ネ'=>'ne','ノ'=>'no',
		'ハ'=>'ha','ヒ'=>'hi','フ'=>'fu','ヘ'=>'he','ホ'=>'ho',
		'マ'=>'ma','ミ'=>'mi','ム'=>'mu','メ'=>'me','モ'=>'mo',
		'ヤ'=>'ya','ユ'=>'yu','ヨ'=>'yo',
		'ラ'=>'ra','リ'=>'ri','ル'=>'ru','レ'=>'re','ロ'=>'ro',
		'ワ'=>'wa','ヲ'=>'o','ン'=>'n',
		'ガ'=>'ga','ギ'=>'gi','グ'=>'gu','ゲ'=>'ge','ゴ'=>'go',
		'ザ'=>'za','ジ'=>'ji','ズ'=>'zu','ゼ'=>'ze','ゾ'=>'zo',
		'ダ'=>'da','ヂ'=>'ji','ヅ'=>'zu','デ'=>'de','ド'=>'do',
		'バ'=>'ba','ビ'=>'bi','ブ'=>'bu','ベ'=>'be','ボ'=>'bo',
		'パ'=>'pa','ピ'=>'pi','プ'=>'pu','ペ'=>'pe','ポ'=>'po',
		'ヴ'=>'vu','ァ'=>'a','ィ'=>'i','ゥ'=>'u','ェ'=>'e','ォ'=>'o',
		'ャ'=>'ya','ュ'=>'yu','ョ'=>'yo',
	);
}

/**
 * カタカナ・ひらがなの文字列をローマ字にします。
 * 漢字など、読めない文字が入っていたら '' を返します。
 */
function ymkrf_roman( $text ) {

	$text = trim( (string) $text );
	if ( $text === '' ) return '';

	/* よく出る言葉は、先に英語に置きかえます
	   （内装・改装の「畳 → フローリング（6帖パック）」などのため。2026/09/22） */
	$words = array(
		'→' => '-to-', '＞' => '-to-',
		'畳' => 'tatami', '和室' => 'washitsu', '洋室' => 'yoshitsu',
		'帖' => 'jo', '室' => '', '間' => '',
		'リフォーム' => '', 'リフレッシュ' => 'refresh', 'パック' => 'pack',
		'張替' => 'harikae', '張り替え' => 'harikae', '重ね貼り' => 'kasanebari',
		'クロス' => 'cross', '天井' => 'tenjo', '床' => 'yuka', '壁' => 'kabe',
		'フローリング' => 'flooring', 'カウンター' => 'counter',
	);
	$text = strtr( $text, $words );

	/* ひらがな → カタカナ */
	if ( function_exists( 'mb_convert_kana' ) ) {
		$text = mb_convert_kana( $text, 'KVC', 'UTF-8' );
	}

	$map  = ymkrf_roman_map();
	$out  = '';
	$len  = mb_strlen( $text, 'UTF-8' );
	$i    = 0;
	$sokuon = false;   /* 「ッ」のあと、つぎの子音を重ねます */

	while ( $i < $len ) {

		$two = ( $i + 1 < $len ) ? mb_substr( $text, $i, 2, 'UTF-8' ) : '';
		$one = mb_substr( $text, $i, 1, 'UTF-8' );

		/* 長音「ー」だけ落とします。英字のハイフンは区切りとして残します */
		if ( $one === 'ー' || $one === '―' ) { $i++; continue; }
		if ( $one === '-' ) { $out .= '-'; $i++; continue; }

		/* 促音「ッ」 */
		if ( $one === 'ッ' ) { $sokuon = true; $i++; continue; }

		$r = '';
		if ( $two !== '' && isset( $map[ $two ] ) ) { $r = $map[ $two ]; $i += 2; }
		elseif ( isset( $map[ $one ] ) )            { $r = $map[ $one ]; $i += 1; }
		elseif ( preg_match( '/^[0-9A-Za-z]$/', $one ) ) { $r = strtolower( $one ); $i += 1; }
		/* 空白や記号は、区切りの「-」にします（言葉がくっつかないように） */
		elseif ( preg_match( '/^[\s　・（）\(\)【】\[\]\/／,、。]$/u', $one ) ) { $out .= '-'; $i += 1; continue; }
		else {
			/* 漢字などは読めません */
			return '';
		}

		if ( $sokuon ) { $out .= substr( $r, 0, 1 ); $sokuon = false; }
		$out .= $r;
	}

	return $out;
}


/* ============================================================
   2. URLの候補を作ります
   ============================================================ */

/**
 * URLの候補。
 * 戻り値： array( 'slug' => 'kitchen-rakuera', 'check' => true/false )
 *   check … ローマ字に直したので、綴りを見てほしいとき true
 */
function ymkrf_slug_guess( $post_id ) {

	$cat  = function_exists( 'ymkrf_product_current_cat' ) ? ymkrf_product_current_cat( $post_id ) : '';
	$name = trim( (string) get_post_meta( $post_id, '_ymkrf_name', true ) );
	if ( $name === '' ) $name = trim( (string) get_post_field( 'post_title', $post_id ) );

	$check = false;
	$base  = '';

	/* ⓪ 水まわり4点セットのプランは、名前が日本語なのでローマ字にできません。
	     並び順の番号を使って plan1〜plan4 にします（2026/09/22） */
	if ( $cat === 'pack4' ) {
		$no = (int) get_post_field( 'menu_order', $post_id );
		if ( $no < 1 ) $no = 1;
		return array( 'slug' => 'pack4-plan' . $no, 'check' => false );
	}

	/* ① 型番があれば、それがいちばん確かです
	     （IH・コンロやフェンスのように、商品名がブランド名になっていないもの）
	     2026/09/22 ユーザー指摘「商品名が無いものは型番っていってなかった？」 */
	$mo = trim( (string) get_post_meta( $post_id, '_ymkrf_model', true ) );
	if ( $mo !== '' ) $base = $mo;

	/* ② いま付いているURLが英数字なら、それを活かします
	     （2026/09/22 ユーザー確認。alauno-vs5 や gga1-counter など、
	       手で付けたURLがよくできているため。頭に分類を足すだけにします） */
	if ( $base === '' ) {
		$now = (string) get_post_field( 'post_name', $post_id );
		$now = strtolower( rawurldecode( $now ) );
		if ( $now !== ''
		  && preg_match( '/^[a-z0-9\-]+$/', $now )     /* 日本語や％が入っていない */
		  && ! preg_match( '/^[0-9\-]+$/', $now ) ) {  /* 番号だけでもない */
			/* すでに分類が頭に付いているときは、二重にしません */
			$base = ( $cat !== '' && strpos( $now, $cat . '-' ) === 0 )
				? substr( $now, strlen( $cat ) + 1 ) : $now;
		}
	}

	/* ③ 商品名の中の英字（V-style（Vスタイル） → v-style）。
	     ただし「NewアラウーノV」の New のように、名前のごく一部でしかないときは使いません */
	if ( $base === '' && preg_match_all( '/[0-9A-Za-z][0-9A-Za-z\-\. ]*/', $name, $m ) ) {
		$cand = '';
		foreach ( $m[0] as $one ) {
			$one = trim( $one );
			if ( mb_strlen( $one, 'UTF-8' ) > mb_strlen( $cand, 'UTF-8' ) ) $cand = $one;
		}
		/* 記号や空白をのぞいた文字数でくらべます */
		$bare = preg_replace( '/[\s　（）\(\)【】\[\]・,、。\/／]/u', '', $name );
		$ok   = ( mb_strlen( $cand, 'UTF-8' ) * 2 >= mb_strlen( (string) $bare, 'UTF-8' ) );
		if ( preg_match( '/[A-Za-z0-9]/', $cand ) && $ok ) $base = $cand;
	}

	/* ④ カタカナをローマ字に */
	if ( $base === '' ) {
		$r = ymkrf_roman( $name );
		if ( $r !== '' ) {
			$base = $r;
			/* かなが入っていたときだけ「要確認」にします（D7 などは確認不要） */
			$check = (bool) preg_match( '/[ぁ-んァ-ヶ]/u', $name );
		}
	}

	/* ⑤ それでもだめなら、番号で置いておきます */
	if ( $base === '' ) { $base = 'item-' . (int) $post_id; $check = true; }

	$base = strtolower( $base );
	$base = preg_replace( '/[^a-z0-9]+/', '-', $base );
	$base = trim( (string) $base, '-' );
	if ( $base === '' ) { $base = 'item-' . (int) $post_id; $check = true; }

	/* コンロ・IHは、分類と型番のあいだに「ガスかIHか」を入れます
	   （2026/09/22 ユーザー指示。/products/cooktop-gas-n3wv6m/ の形） */
	$mid = '';
	if ( $cat === 'cooktop' ) {
		$kind = (string) get_post_meta( $post_id, '_ymkrf_ihtype', true );
		if ( strpos( $kind, 'ガス' ) !== false )      $mid = 'gas';
		elseif ( strpos( $kind, 'IH' ) !== false )    $mid = 'ih';
	}

	$slug = ( $cat !== '' ? $cat . '-' : '' ) . ( $mid !== '' ? $mid . '-' : '' ) . $base;

	return array( 'slug' => $slug, 'check' => $check );
}


/* ============================================================
   3. 保存のとき（題名とURL）
   ============================================================ */

add_action( 'save_post_ymkrf_product', function ( $post_id ) {

	static $busy = false;          /* 無限ループよけ。__FUNCTION__ は使いません */
	if ( $busy ) return;

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;
	if ( get_post_status( $post_id ) === 'auto-draft' ) return;

	/* ---- 手で入れたURL ---- */
	if ( isset( $_POST['ymkrf_slug_nonce'] ) &&
	     wp_verify_nonce( $_POST['ymkrf_slug_nonce'], 'ymkrf_slug' ) ) {

		$typed = isset( $_POST['ymkrf_slug'] ) ? sanitize_title( wp_unslash( $_POST['ymkrf_slug'] ) ) : '';
		$auto  = ! empty( $_POST['ymkrf_slug_auto'] );

		if ( ! $auto && $typed !== '' ) {
			update_post_meta( $post_id, '_ymkrf_slug_auto', '0' );
			delete_post_meta( $post_id, '_ymkrf_slug_check' );
			$busy = true;
			wp_update_post( array( 'ID' => $post_id, 'post_name' => $typed ) );
			$busy = false;
		} else {
			update_post_meta( $post_id, '_ymkrf_slug_auto', '1' );
		}
	}

	/* ---- 題名を「商品名」にそろえます ---- */
	$name = trim( (string) get_post_meta( $post_id, '_ymkrf_name', true ) );
	if ( $name !== '' && $name !== get_post_field( 'post_title', $post_id ) ) {
		$busy = true;
		wp_update_post( array( 'ID' => $post_id, 'post_title' => $name ) );
		$busy = false;
	}

	/* ---- URLを自動でそろえます ---- */
	if ( get_post_meta( $post_id, '_ymkrf_slug_auto', true ) === '0' ) return;

	$g   = ymkrf_slug_guess( $post_id );
	$now = (string) get_post_field( 'post_name', $post_id );

	if ( $g['check'] ) update_post_meta( $post_id, '_ymkrf_slug_check', '1' );
	else               delete_post_meta( $post_id, '_ymkrf_slug_check' );

	if ( $now !== $g['slug'] ) {
		$busy = true;
		wp_update_post( array( 'ID' => $post_id, 'post_name' => $g['slug'] ) );
		$busy = false;
	}
}, 25 );


/* ============================================================
   4. 入力画面（題名の欄を隠して、URLの欄を出します）
   ============================================================ */

add_action( 'admin_head', function () {

	$s = get_current_screen();
	if ( ! $s || $s->post_type !== 'ymkrf_product' ) return;
	if ( ! in_array( $s->base, array( 'post' ), true ) ) return;

	/* 題名の欄は出したままにします
	   （2026/09/22 ユーザー指示「タイトルは商品詳細ページから見えていても問題ありません」）。
	   中身は保存のときに「商品名」の欄からそろえます。
	   WordPressのURL編集だけは、下の「URL（英数字）」の箱と二重になるので隠します。 */
	echo '<style>#edit-slug-box{display:none !important}</style>';
} );

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'ymkrf_product_url', 'URL（英数字）', 'ymkrf_slug_box',
		'ymkrf_product', 'normal', 'high' );
}, 5 );

function ymkrf_slug_box( $post ) {

	wp_nonce_field( 'ymkrf_slug', 'ymkrf_slug_nonce' );

	$auto  = get_post_meta( $post->ID, '_ymkrf_slug_auto', true ) !== '0';
	$check = get_post_meta( $post->ID, '_ymkrf_slug_check', true ) === '1';
	$now   = (string) $post->post_name;
	$g     = ymkrf_slug_guess( $post->ID );
	$base  = trailingslashit( home_url( '/products' ) );
	?>
	<p style="margin:0 0 10px;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
	  <span style="color:#6b615c;font-size:13px"><?php echo esc_html( $base ); ?></span>
	  <input type="text" name="ymkrf_slug" value="<?php echo esc_attr( $now !== '' ? $now : $g['slug'] ); ?>"
	         style="width:320px;font-size:15px;font-weight:700" placeholder="<?php echo esc_attr( $g['slug'] ); ?>">
	  <span style="color:#6b615c;font-size:13px">/</span>
	  <?php if ( $check && $auto ) : ?>
	    <span style="background:#fff1ec;color:#b32d2e;border-radius:999px;padding:3px 10px;
	                 font-size:12px;font-weight:800">要確認</span>
	  <?php endif; ?>
	</p>

	<p style="margin:0 0 8px">
	  <label style="font-size:13.5px">
	    <input type="checkbox" name="ymkrf_slug_auto" value="1" <?php checked( $auto ); ?>>
	    自動でつくる（分類＋商品名の英数字）
	  </label>
	</p>

	<p class="description" style="margin:0;line-height:1.85">
	  <?php if ( $check && $auto ) : ?>
	    <b style="color:#b32d2e">カタカナをローマ字に直したので、綴りをご確認ください。</b>
	    直すときは、上のチェックを外してから書きかえてください。<br>
	  <?php endif; ?>
	  ★<b>公開したあとにURLを変えると、前のURLは開けなくなります（404）。</b>
	  本番公開の前にそろえてください。
	</p>
	<?php
}


/* ============================================================
   5. まとめて直す画面（商品 ＞ URLの見なおし）
   ============================================================ */

add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=ymkrf_product',
		'URLの見なおし', 'URLの見なおし',
		'manage_options', 'ymkrf-slugs', 'ymkrf_slug_page'
	);
}, 44 );

function ymkrf_slug_page() {

	if ( ! current_user_can( 'manage_options' ) ) return;

	$msg = '';

	if ( isset( $_POST['ymkrf_slugs_nonce'] ) &&
	     wp_verify_nonce( $_POST['ymkrf_slugs_nonce'], 'ymkrf_slugs' ) ) {

		$ids = ( isset( $_POST['ids'] ) && is_array( $_POST['ids'] ) )
			? array_map( 'intval', $_POST['ids'] ) : array();

		$n = 0;
		foreach ( $ids as $id ) {
			if ( get_post_type( $id ) !== 'ymkrf_product' ) continue;
			$g = ymkrf_slug_guess( $id );
			if ( (string) get_post_field( 'post_name', $id ) === $g['slug'] ) continue;
			wp_update_post( array( 'ID' => $id, 'post_name' => $g['slug'] ) );
			update_post_meta( $id, '_ymkrf_slug_auto', '1' );
			if ( $g['check'] ) update_post_meta( $id, '_ymkrf_slug_check', '1' );
			else               delete_post_meta( $id, '_ymkrf_slug_check' );
			$n++;
		}
		$msg = $n . '件のURLを新しい形にしました。';
	}

	$q = new WP_Query( array(
		'post_type'      => 'ymkrf_product',
		'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	) );
	?>
	<div class="wrap">
	  <h1>URLの見なおし</h1>

	  <?php if ( $msg ) : ?>
	    <div class="notice notice-success"><p><?php echo esc_html( $msg ); ?></p></div>
	  <?php endif; ?>

	  <p style="max-width:900px;line-height:1.9">
	    商品のURLを <b>分類＋商品名（英数字）</b> の形にそろえます。<br>
	    <b style="color:#b32d2e">★公開したあとにURLを変えると、前のURLは開けなくなります（404）。</b>
	    本番公開の前におこなってください。
	  </p>

	  <form method="post">
	    <?php wp_nonce_field( 'ymkrf_slugs', 'ymkrf_slugs_nonce' ); ?>
	    <table class="wp-list-table widefat striped">
	      <thead><tr>
	        <td class="check-column"><input type="checkbox" id="ymkrf-all"></td>
	        <th>商品</th><th>いまのURL</th><th>新しいURL</th><th style="width:90px">状態</th>
	      </tr></thead>
	      <tbody>
	      <?php foreach ( $q->posts as $p ) :
	        $g    = ymkrf_slug_guess( $p->ID );
	        $same = ( $g['slug'] === $p->post_name );
	        $lock = get_post_meta( $p->ID, '_ymkrf_slug_auto', true ) === '0';
	      ?>
	        <tr>
	          <th class="check-column">
	            <?php if ( ! $same && ! $lock ) : ?>
	              <input type="checkbox" name="ids[]" value="<?php echo (int) $p->ID; ?>" checked>
	            <?php endif; ?>
	          </th>
	          <td><a href="<?php echo esc_url( (string) get_edit_post_link( $p->ID ) ); ?>"><?php
	            echo esc_html( get_the_title( $p->ID ) ); ?></a></td>
	          <td><code><?php echo esc_html( $p->post_name ); ?></code></td>
	          <td><code style="<?php echo $same ? '' : 'background:#fff6f3'; ?>"><?php
	            echo esc_html( $g['slug'] ); ?></code></td>
	          <td>
	            <?php if ( $lock ) : ?><span style="color:#2b5f86">手で設定</span>
	            <?php elseif ( $same ) : ?><span style="color:#6b615c">そのまま</span>
	            <?php elseif ( $g['check'] ) : ?><span style="color:#b32d2e;font-weight:700">要確認</span>
	            <?php else : ?><span style="color:#0a6b2d">変更</span><?php endif; ?>
	          </td>
	        </tr>
	      <?php endforeach; wp_reset_postdata(); ?>
	      </tbody>
	    </table>

	    <?php submit_button( 'チェックした商品のURLを新しい形にする' ); ?>
	  </form>

	  <script>
	  document.getElementById('ymkrf-all').addEventListener('change', function(){
	    document.querySelectorAll('input[name="ids[]"]').forEach(function(c){ c.checked = this.checked; }, this);
	  });
	  </script>
	</div>
	<?php
}
