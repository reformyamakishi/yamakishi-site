<?php
/**
 * functions-voice.php ─ お客様の声（アンケート「仕事の通信簿」）
 * リフォームヤマキシ
 *
 * 置き場所： wp-content/themes/ymkrf/inc/functions-voice.php
 *
 * ■ 使いかた
 *   1. 管理画面の「お客様の声」→「新規追加」
 *   2. 「アンケート画像を選ぶ」でスキャン画像を選ぶ
 *      （ファイル名は案件番号のままで大丈夫です。案件番号は非公開で控えます）
 *   3. 自動でチェック項目が入ります。手書きの感想と点数だけ、
 *      すぐ下に出る切り抜き画像を見ながら打ち込んでください
 *   4. 公開すると、一覧・詳細ページに出ます
 *
 * ■ 個人情報について
 *   ・公開用の画像は「ご紹介（　様）」の欄を白く塗りつぶして作ります
 *   ・ファイル名は案件番号ではなく、通し番号に付け替えます
 *   ・原本（塗りつぶす前）はメディアに残りますが、ページには出しません
 *
 * ■ 読み取りの仕組み
 *   assets/js/voice-admin.js を参照。ブラウザの中だけで処理しますので、
 *   画像が外部に送られることはありません。
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ============================================================
   1. 項目の定義
   ============================================================ */

function ymkrf_voice_parts_list() {
	return array(
		'キッチン','浴室','トイレ','洗面室',
		'エコキュート','給湯器','オイルタンク','エクステリア',
		'カーポート','外壁','屋根','窓・サッシ',
		'レンジフード','ドア','蓄電池','太陽光発電',
		'修理・小工事','改装・内装','その他',
	);
}
function ymkrf_voice_reasons_list() {
	return array(
		'プラン内容','評判','家から近かったから',
		'営業担当の対応','専門性','以前工事を選んだ会社だから',
		'サービス内容','価格','その他','ご紹介',
	);
}
/* ③〜⑧の設問。キー => 表示名 */
function ymkrf_voice_rating_fields() {
	return array(
		'_ymkrf_r_sales'   => '営業担当の「対応」',
		'_ymkrf_r_plan'    => 'ご提案した「プラン内容」',
		'_ymkrf_r_worker'  => '職人の「対応」',
		'_ymkrf_r_process' => '「工事の進め方」',
		'_ymkrf_r_site'    => '現場の「管理状況」',
		'_ymkrf_r_finish'  => '「工事の仕上がり」',
	);
}
/* 4 = 大変良かった … 1 = よくなかった */
function ymkrf_voice_rating_labels() {
	return array( 4 => '大変良かった', 3 => '満足', 2 => '普通', 1 => 'よくなかった' );
}
function ymkrf_voice_recommend_labels() {
	return array( 4 => '勧める', 3 => '勧めても良い', 2 => 'わからない', 1 => '勧められない' );
}

/* このページで扱うメタの一覧（保存時にまとめて使います） */
function ymkrf_voice_meta_keys() {
	return array_merge(
		array(
			'_ymkrf_case_no', '_ymkrf_survey_id', '_ymkrf_survey_pub_id',
			'_ymkrf_parts', '_ymkrf_reasons', '_ymkrf_recommend', '_ymkrf_score',
			'_ymkrf_trouble', '_ymkrf_after', '_ymkrf_comment',
			'_ymkrf_customer', '_ymkrf_area', '_ymkrf_read_info',
			'_ymkrf_city', '_ymkrf_initial', '_ymkrf_shop', '_ymkrf_illust',
		),
		array_keys( ymkrf_voice_rating_fields() )
	);
}


/* ============================================================
   1-2. お客様イメージのイラスト

   assets/img/voice/ に置いたファイルが、そのまま選べるようになります。
   ファイルを足したり消したりすれば一覧も変わります（書き換え不要）。
   ============================================================ */

function ymkrf_voice_illust_dir() {
	return get_stylesheet_directory() . '/assets/img/voice';
}
function ymkrf_voice_illust_url() {
	return get_stylesheet_directory_uri() . '/assets/img/voice';
}

/** フォルダにあるイラストの一覧（ファイル名だけ） */
function ymkrf_voice_illusts() {
	$dir = ymkrf_voice_illust_dir();
	if ( ! is_dir( $dir ) ) return array();
	$out = array();
	foreach ( (array) scandir( $dir ) as $f ) {
		if ( $f === '.' || $f === '..' ) continue;
		if ( ! preg_match( '/\.(png|jpe?g|webp)$/i', $f ) ) continue;
		$out[] = $f;
	}
	natcasesort( $out );
	return array_values( $out );
}

/** お客様の表示名。「金沢市／K様」の形にします。 */
function ymkrf_voice_customer_label( $post_id ) {
	$city = trim( (string) get_post_meta( $post_id, '_ymkrf_city', true ) );
	$ini  = trim( (string) get_post_meta( $post_id, '_ymkrf_initial', true ) );
	if ( $city !== '' || $ini !== '' ) {
		$s = $city;
		if ( $ini !== '' ) $s .= ( $s !== '' ? '　' : '' ) . $ini . '様';
		return $s;
	}
	return trim( (string) get_post_meta( $post_id, '_ymkrf_customer', true ) );
}


/* ============================================================
   2. 管理画面
   ============================================================ */

/* お客様の声は、入力欄がすべて決まった形なので、
   ブロックエディターではなく昔ながらの編集画面にします。
   （ブロックエディターだと、アンケートの欄が画面下の引き出しに入ってしまいます） */
add_filter( 'use_block_editor_for_post_type', function ( $use, $type ) {
	return ( $type === 'ymkrf_voice' ) ? false : $use;
}, 10, 2 );

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'ymkrf_voice_survey', 'お客様アンケート（仕事の通信簿）',
		'ymkrf_voice_metabox', 'ymkrf_voice', 'normal', 'high' );

	/* 古い「お客様情報」欄（評価1〜5）は、満足度の点数に置きかわったので消します */
	remove_meta_box( 'ymkrf_voice_box', 'ymkrf_voice', 'side' );
}, 20 );

function ymkrf_voice_metabox( $post ) {
	wp_nonce_field( 'ymkrf_voice_save', 'ymkrf_voice_nonce' );

	$get   = function ( $k, $d = '' ) use ( $post ) {
		$v = get_post_meta( $post->ID, $k, true );
		return ( $v === '' || $v === null ) ? $d : $v;
	};
	$parts   = array_filter( array_map( 'trim', explode( ',', (string) $get( '_ymkrf_parts' ) ) ) );
	$reasons = array_filter( array_map( 'trim', explode( ',', (string) $get( '_ymkrf_reasons' ) ) ) );
	$sid     = (int) $get( '_ymkrf_survey_id', 0 );
	$pid     = (int) $get( '_ymkrf_survey_pub_id', 0 );
	?>
	<div class="ymkrf-voice">

	  <p class="ymkrf-voice__lead">
	    アンケートのスキャン画像を選ぶと、<b>①〜⑨のチェックが自動で入ります。</b>
	    手書きの感想と点数だけ、下に出る切り抜きを見ながら打ち込んでください。<br>
	    <span class="description">
	      チェック欄の読み取りは、このパソコンの中だけで行います。<br>
	      <?php if ( get_option( YMKRF_VISION_OPT, '' ) ) : ?>
	        手書きの文字起こしは Google の文字認識を使います。
	        <b style="color:#b26a00">自動で入った文字は、かならず切り抜き画像と見くらべてください。</b><br>
	        <?php $vleft = ymkrf_vision_left(); ?>
	        文字起こしは<b>「Cloud Visionで文字起こしする」を押したときだけ</b>動きます。<br>
	        <?php if ( $vleft < 3 ) : ?>
	          <b style="color:#b26a00">今月の上限に達したため、文字起こしは止まっています。</b>
	          手で打ち込んでください。
	          （<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ymkrf_voice&page=ymkrf-voice-ocr' ) ); ?>">設定</a>）
	        <?php else : ?>
	          今月ののこり <b><?php echo (int) floor( $vleft / 3 ); ?></b> 件ぶん
	          （<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ymkrf_voice&page=ymkrf-voice-ocr' ) ); ?>">設定</a>）
	        <?php endif; ?>
	      <?php else : ?>
	        手書きの部分は、下に出る切り抜きを見ながら打ち込んでください。
	        （<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ymkrf_voice&page=ymkrf-voice-ocr' ) ); ?>">自動で文字起こしする設定</a>もあります）
	      <?php endif; ?>
	    </span>
	  </p>

	  <p>
	    <button type="button" class="button button-primary" id="ymkrf-pick">アンケート画像を選ぶ</button>
	    <button type="button" class="button" id="ymkrf-reread" <?php disabled( ! $sid ); ?>>もう一度読み取る</button>
	    <?php if ( get_option( YMKRF_VISION_OPT, '' ) ) : ?>
	      <?php /* 手書きの文字起こしは、このボタンを押したときだけ動きます（自動では動きません） */ ?>
	      <button type="button" class="button" id="ymkrf-ocr" <?php disabled( ! $sid ); ?>>Cloud Visionで文字起こしする</button>
	    <?php endif; ?>
	    <span id="ymkrf-status" class="ymkrf-voice__status"></span>
	  </p>

	  <input type="hidden" name="_ymkrf_survey_id"     id="ymkrf-survey-id"  value="<?php echo esc_attr( $sid ); ?>">
	  <input type="hidden" name="_ymkrf_survey_pub_id" id="ymkrf-pub-id"     value="<?php echo esc_attr( $pid ); ?>">
	  <input type="hidden" name="_ymkrf_read_info"     id="ymkrf-read-info"  value="<?php echo esc_attr( $get( '_ymkrf_read_info' ) ); ?>">

	  <?php
	  /* 画面に出すのは「公開用（お名前を塗りつぶしたもの）」です。
	     まだ作られていないときだけ、選んだ画像そのものを出します。 */
	  $show_id = $pid ? $pid : $sid;
	  ?>
	  <div id="ymkrf-preview" class="ymkrf-voice__preview">
	    <?php if ( $show_id ) : ?>
	      <img src="<?php echo esc_url( (string) wp_get_attachment_image_url( $show_id, 'large' ) ); ?>"
	           data-full="<?php echo esc_url( (string) wp_get_attachment_url( $show_id ) ); ?>"
	           data-orig="<?php echo esc_url( $sid ? (string) wp_get_attachment_url( $sid ) : '' ); ?>" alt="">
	    <?php endif; ?>
	  </div>
	  <p class="description ymkrf-voice__previewnote" id="ymkrf-previewnote"
	     <?php if ( ! $show_id ) echo 'style="display:none"'; ?>>
	    画像を押すと大きくなります。ページに出るのは、この画像です。<br>
	    「ご紹介（　様）」の欄は、念のため自動で白く塗りつぶしています。
	  </p>

	  <table class="form-table ymkrf-voice__table">
	    <tr>
	      <th>案件番号</th>
	      <td><input type="text" name="_ymkrf_case_no" value="<?php echo esc_attr( $get( '_ymkrf_case_no' ) ); ?>" class="regular-text">
	          <p class="description">画像のファイル名から自動で入ります。ページの下のほうに小さく出ます。<br>
	            同じ番号の施工事例を登録すると、自動でリンクします。</p></td>
	    </tr>
	    <tr>
	      <th>① 工事した箇所</th>
	      <td class="ymkrf-voice__checks" id="ymkrf-parts">
	        <?php foreach ( ymkrf_voice_parts_list() as $v ) : ?>
	          <label><input type="checkbox" name="_ymkrf_parts[]" value="<?php echo esc_attr( $v ); ?>"
	            <?php checked( in_array( $v, $parts, true ) ); ?>> <?php echo esc_html( $v ); ?></label>
	        <?php endforeach; ?>
	      </td>
	    </tr>
	    <tr>
	      <th>② 選んでいただいた理由</th>
	      <td class="ymkrf-voice__checks" id="ymkrf-reasons">
	        <?php foreach ( ymkrf_voice_reasons_list() as $v ) : ?>
	          <label><input type="checkbox" name="_ymkrf_reasons[]" value="<?php echo esc_attr( $v ); ?>"
	            <?php checked( in_array( $v, $reasons, true ) ); ?>> <?php echo esc_html( $v ); ?></label>
	        <?php endforeach; ?>
	      </td>
	    </tr>

	    <?php foreach ( ymkrf_voice_rating_fields() as $key => $label ) :
	      $cur = (int) $get( $key, 0 ); ?>
	    <tr>
	      <th><?php echo esc_html( $label ); ?></th>
	      <td class="ymkrf-voice__radios" data-field="<?php echo esc_attr( $key ); ?>">
	        <?php foreach ( ymkrf_voice_rating_labels() as $n => $lb ) : ?>
	          <label><input type="radio" name="<?php echo esc_attr( $key ); ?>" value="<?php echo $n; ?>"
	            <?php checked( $cur, $n ); ?>> <?php echo esc_html( $lb ); ?></label>
	        <?php endforeach; ?>
	        <label><input type="radio" name="<?php echo esc_attr( $key ); ?>" value="0" <?php checked( $cur, 0 ); ?>> 未記入</label>
	      </td>
	    </tr>
	    <?php endforeach; ?>

	    <tr>
	      <th>⑨ お知り合いへのおすすめ</th>
	      <td class="ymkrf-voice__radios" data-field="_ymkrf_recommend">
	        <?php $cur = (int) $get( '_ymkrf_recommend', 0 );
	        foreach ( ymkrf_voice_recommend_labels() as $n => $lb ) : ?>
	          <label><input type="radio" name="_ymkrf_recommend" value="<?php echo $n; ?>"
	            <?php checked( $cur, $n ); ?>> <?php echo esc_html( $lb ); ?></label>
	        <?php endforeach; ?>
	        <label><input type="radio" name="_ymkrf_recommend" value="0" <?php checked( $cur, 0 ); ?>> 未記入</label>
	      </td>
	    </tr>

	    <tr>
	      <th>満足度（100点満点）<br><span class="description">★の色と数に連動します</span></th>
	      <td>
	        <input type="number" name="_ymkrf_score" id="ymkrf-score" min="0" max="100"
	               value="<?php echo esc_attr( $get( '_ymkrf_score' ) ); ?>" class="small-text"> 点
	        <p class="ymkrf-voice__need" id="ymkrf-scoreneed">
	          ↓ この切り抜きに点数が書かれていたら、<b>かならず上の欄に入力してください</b>。<br>
	          空のままだと、③〜⑧の評価から計算した点数になります。
	        </p>
	        <div class="ymkrf-voice__crop" id="ymkrf-crop-score"></div>
	        <p class="description">
	          <b>アンケートに書かれた点数をそのまま入れてください。</b>ここが最優先で使われます。<br>
	          点数の記入がないアンケートのときだけ空にしてください。空のときは、上の
	          「営業担当の対応」などの評価から自動で計算します
	          （大変良かった=100／満足=85／普通=70／よくなかった=40 の平均）。</p>
	      </td>
	    </tr>
	    <tr>
	      <th>リフォーム前のお悩み</th>
	      <td><textarea name="_ymkrf_trouble" rows="2" class="large-text"><?php echo esc_textarea( $get( '_ymkrf_trouble' ) ); ?></textarea>
	          <div class="ymkrf-voice__crop" id="ymkrf-crop-trouble"></div></td>
	    </tr>
	    <tr>
	      <th>リフォームしていかがでしたか</th>
	      <td><textarea name="_ymkrf_after" rows="2" class="large-text"><?php echo esc_textarea( $get( '_ymkrf_after' ) ); ?></textarea>
	          <div class="ymkrf-voice__crop" id="ymkrf-crop-after"></div></td>
	    </tr>
	    <tr>
	      <th>スタッフへのメッセージ</th>
	      <td><textarea name="_ymkrf_comment" rows="3" class="large-text"><?php echo esc_textarea( $get( '_ymkrf_comment' ) ); ?></textarea>
	          <div class="ymkrf-voice__crop" id="ymkrf-crop-comment"></div></td>
	    </tr>
	    <tr>
	      <th>お客様（市・町）</th>
	      <td><input type="text" name="_ymkrf_city" value="<?php echo esc_attr( $get( '_ymkrf_city' ) ); ?>" class="regular-text"
	                 placeholder="例：金沢市">
	          <p class="description">お住まいの市や町だけ。番地は入れないでください。</p></td>
	    </tr>
	    <tr>
	      <th>お客様（名字の頭文字）</th>
	      <td><input type="text" name="_ymkrf_initial" value="<?php echo esc_attr( $get( '_ymkrf_initial' ) ); ?>"
	                 maxlength="2" style="width:80px" placeholder="例：K">
	          <p class="description">アルファベット1文字。「様」は自動で付きます。
	            <b>いまの表示：</b><?php
	              $lbl = ymkrf_voice_customer_label( $post->ID );
	              echo esc_html( $lbl !== '' ? $lbl : '（未入力）' ); ?></p></td>
	    </tr>
	    <tr>
	      <th>施工した店舗</th>
	      <td>
	        <?php $cur_shop = (string) $get( '_ymkrf_shop' );
	        $shops = get_terms( array( 'taxonomy' => 'ymkrf_shop', 'hide_empty' => false ) ); ?>
	        <select name="_ymkrf_shop" id="ymkrf-vshop-sel">
	          <option value="">（えらんでください）</option>
	          <?php if ( ! is_wp_error( $shops ) ) foreach ( (array) $shops as $sh ) : ?>
	            <option value="<?php echo esc_attr( $sh->slug ); ?>" <?php selected( $cur_shop, $sh->slug ); ?>>
	              <?php echo esc_html( $sh->name ); ?></option>
	          <?php endforeach; ?>
	        </select>
	        <p class="description">工事を担当した店舗です。ページに出ます。</p>
	      </td>
	    </tr>
	    <tr>
	      <th>担当スタッフ</th>
	      <td>
	        <?php
	        /* 施工事例の登録ページと同じえらび方です。
	           上の「施工した店舗」をえらぶと、その店舗の人だけが出ます。 */
	        $vstaffs   = function_exists( 'ymkrf_staff_sales_list' ) ? ymkrf_staff_sales_list() : array();
	        $vstaffcur = (int) $get( '_ymkrf_staff' );
	        /* すでにえらばれている人が一覧に無いとき（あとから外した人など）は、
	           消えてしまわないように足しておきます */
	        if ( $vstaffcur ) {
	          $has = false;
	          foreach ( $vstaffs as $st ) { if ( (int) $st->ID === $vstaffcur ) { $has = true; break; } }
	          if ( ! $has ) {
	            $sp = get_post( $vstaffcur );
	            if ( $sp && $sp->post_type === 'ymkrf_staff' ) $vstaffs[] = $sp;
	          }
	        }
	        ?>
	        <?php if ( $vstaffs ) : ?>
	          <?php
	          $vsdata = array();
	          foreach ( $vstaffs as $st ) {
	            $vsdata[] = array(
	              'id'    => (int) $st->ID,
	              'name'  => (string) get_the_title( $st ),
	              'shop'  => (string) get_post_meta( $st->ID, '_ymkrf_staff_shop', true ),
	              'sname' => function_exists( 'ymkrf_staff_shop_name' ) ? (string) ymkrf_staff_shop_name( $st->ID ) : '',
	              'role'  => (string) get_post_meta( $st->ID, '_ymkrf_staff_role', true ),
	            );
	          }
	          ?>
	          <div class="ymkrf-pick" id="ymkrf-vstaff-pick">
	            <input type="hidden" name="_ymkrf_staff" id="ymkrf-vstaff-val" value="<?php echo (int) $vstaffcur; ?>">
	            <button type="button" class="button ymkrf-pick__btn" id="ymkrf-vstaff-btn">（えらんでください）</button>
	            <div class="ymkrf-pick__menu" id="ymkrf-vstaff-menu" hidden></div>
	            <button type="button" class="button-link ymkrf-pick__clear" id="ymkrf-vstaff-clear">えらび直す（空にする）</button>
	          </div>
	          <script>
	            window.ymkrfVStaffList  = <?php echo wp_json_encode( $vsdata ); ?>;
	            window.ymkrfVStaffOrder = <?php echo wp_json_encode( function_exists( 'ymkrf_staff_shops' ) ? array_keys( ymkrf_staff_shops() ) : array() ); ?>;
	          </script>
	          <p class="description">
	            上の「施工した店舗」をえらぶと、<b>その店舗の人だけ</b>が出ます。<br>
	            ほかの店舗や本部・工事部の人にするときは、いちばん下の
	            <b>「その他」にマウスを乗せる</b>と、横に全員の名前が出ます。<br>
	            名前と顔写真は「スタッフ」で登録してください。空のままでもかまいません。
	          </p>
	        <?php else : ?>
	          <p class="description">スタッフがまだ登録されていません。</p>
	        <?php endif; ?>
	      </td>
	    </tr>
	    <tr>
	      <th>お客様イメージのイラスト</th>
	      <td>
	        <?php $cur_ill = (string) $get( '_ymkrf_illust' ); $ills = ymkrf_voice_illusts(); ?>
	        <?php if ( ! $ills ) : ?>
	          <p class="description">
	            イラストがまだありません。テーマの <code>assets/img/voice/</code> に
	            画像（png / jpg / webp）を置くと、ここに一覧が出ます。</p>
	        <?php else : ?>
	          <div class="ymkrf-voice__ills">
	            <label class="ymkrf-voice__ill <?php echo $cur_ill === '' ? 'is-on' : ''; ?>">
	              <input type="radio" name="_ymkrf_illust" value="" <?php checked( $cur_ill, '' ); ?>>
	              <span class="ymkrf-voice__illnone">なし</span>
	            </label>
	            <?php foreach ( $ills as $f ) : ?>
	              <label class="ymkrf-voice__ill <?php echo $cur_ill === $f ? 'is-on' : ''; ?>">
	                <input type="radio" name="_ymkrf_illust" value="<?php echo esc_attr( $f ); ?>" <?php checked( $cur_ill, $f ); ?>>
	                <img src="<?php echo esc_url( ymkrf_voice_illust_url() . '/' . $f ); ?>" alt="<?php echo esc_attr( $f ); ?>">
	              </label>
	            <?php endforeach; ?>
	          </div>
	          <p class="description">工事の内容やお客様の雰囲気に合うものをえらんでください（<?php echo count( $ills ); ?>点）。</p>
	        <?php endif; ?>
	      </td>
	    </tr>
	  </table>
	</div>
	<?php
}

/* 管理画面で使うファイルの読み込み */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) return;
	$screen = get_current_screen();
	if ( ! $screen || $screen->post_type !== 'ymkrf_voice' ) return;

	$dir = get_stylesheet_directory_uri();
	wp_enqueue_media();
	wp_enqueue_script( 'ymkrf-voice-admin', $dir . '/assets/js/voice-admin.js',
		array( 'jquery' ), defined( 'YMKRF_VER' ) ? YMKRF_VER : '1.0', true );
	wp_localize_script( 'ymkrf-voice-admin', 'YMKRF_VOICE', array(
		'ajax'  => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'ymkrf_voice_img' ),
		'parts'   => ymkrf_voice_parts_list(),
		'reasons' => ymkrf_voice_reasons_list(),
		/* 手書きの文字起こしが使えるかどうか（APIキーが入っていればtrue） */
		'ocr'     => (bool) get_option( YMKRF_VISION_OPT, '' ),
	) );
	wp_add_inline_script( 'jquery', "jQuery(function($){"
		. "$(document).on('change','.ymkrf-voice__ill input',function(){"
		. "$('.ymkrf-voice__ill').removeClass('is-on');"
		. "$(this).closest('.ymkrf-voice__ill').addClass('is-on');});});" );
	wp_add_inline_style( 'wp-admin', '
	  .ymkrf-voice__lead{margin:4px 0 14px}
	  .ymkrf-voice__status{margin-left:12px;font-weight:700}
	  .ymkrf-voice__status.is-ok{color:#118a3d}
	  .ymkrf-voice__status.is-ng{color:#c00}
	  .ymkrf-voice__status.is-warn{color:#b26a00}
	  /* 自動で文字起こしした欄は、色を変えて「確認してね」と分かるようにします */
	  .ymkrf-voice textarea.is-ocr, .ymkrf-voice input.is-ocr{
	    background:#fffbe6; border-color:#e0b000; box-shadow:0 0 0 1px #e0b000 inset }
	  .ymkrf-voice__preview img{max-width:420px;height:auto;border:1px solid #dcdcde;margin:6px 0 4px;
	    cursor:zoom-in}
	  .ymkrf-voice__previewnote{margin:0 0 14px}
	  /* 押したときに出る、大きい画像 */
	  .ymkrf-voice__zoom{position:fixed;inset:0;z-index:100100;display:flex;align-items:center;
	    justify-content:center;background:rgba(0,0,0,.8);cursor:zoom-out;padding:20px}
	  .ymkrf-voice__zoom img{max-width:96vw;max-height:92vh;background:#fff;box-shadow:0 10px 40px rgba(0,0,0,.5)}
	  .ymkrf-voice__checks label{display:inline-block;min-width:190px;margin:0 10px 8px 0}
	  .ymkrf-voice__radios label{display:inline-block;margin:0 16px 6px 0}
	  .ymkrf-voice__crop{margin-top:8px}
	  /* 満足度が空のときに出す注意 */
	  .ymkrf-voice__need{display:none;margin:8px 0 0;padding:9px 12px;border-radius:6px;
	    background:#fff4e5;border:1px solid #e0b000;color:#7a4a00;font-size:13px;line-height:1.8}
	  .ymkrf-voice__need.is-on{display:block}
	  #ymkrf-score.is-need{background:#fffbe6;border-color:#e0b000;box-shadow:0 0 0 2px #e0b000 inset}
	  .ymkrf-voice__crop img{max-width:100%;border:1px solid #dcdcde;background:#fff}
	  .ymkrf-voice__table th{width:220px}
	  .ymkrf-voice__ills{display:flex;flex-wrap:wrap;gap:8px;max-width:900px}
	  .ymkrf-voice__ill{display:block;cursor:pointer;border:3px solid #dcdcde;border-radius:10px;
	    background:#fff;padding:2px;line-height:0}
	  .ymkrf-voice__ill.is-on{border-color:#fe3301;box-shadow:0 0 0 2px rgba(254,51,1,.18)}
	  .ymkrf-voice__ill input{position:absolute;opacity:0;width:0;height:0}
	  .ymkrf-voice__ill img{width:76px;height:76px;object-fit:contain;display:block}
	  .ymkrf-voice__illnone{display:flex;width:76px;height:76px;align-items:center;justify-content:center;
	    font-size:12px;color:#666;line-height:1.4}
	' );
} );

/* 保存 */
add_action( 'save_post_ymkrf_voice', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! isset( $_POST['ymkrf_voice_nonce'] ) ||
	     ! wp_verify_nonce( $_POST['ymkrf_voice_nonce'], 'ymkrf_voice_save' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	foreach ( array( '_ymkrf_parts', '_ymkrf_reasons' ) as $k ) {
		$v = isset( $_POST[ $k ] ) ? (array) $_POST[ $k ] : array();
		$v = array_map( 'sanitize_text_field', $v );
		update_post_meta( $post_id, $k, implode( ',', $v ) );
	}
	foreach ( array_keys( ymkrf_voice_rating_fields() ) as $k ) {
		update_post_meta( $post_id, $k, isset( $_POST[ $k ] ) ? max( 0, min( 4, (int) $_POST[ $k ] ) ) : 0 );
	}
	update_post_meta( $post_id, '_ymkrf_recommend',
		isset( $_POST['_ymkrf_recommend'] ) ? max( 0, min( 4, (int) $_POST['_ymkrf_recommend'] ) ) : 0 );
	update_post_meta( $post_id, '_ymkrf_score',
		isset( $_POST['_ymkrf_score'] ) && $_POST['_ymkrf_score'] !== ''
			? max( 0, min( 100, (int) $_POST['_ymkrf_score'] ) ) : '' );

	foreach ( array( '_ymkrf_trouble', '_ymkrf_after', '_ymkrf_comment' ) as $k ) {
		update_post_meta( $post_id, $k, isset( $_POST[ $k ] ) ? sanitize_textarea_field( $_POST[ $k ] ) : '' );
	}
	foreach ( array( '_ymkrf_case_no', '_ymkrf_customer', '_ymkrf_read_info',
	                 '_ymkrf_city', '_ymkrf_initial', '_ymkrf_shop', '_ymkrf_illust' ) as $k ) {
		update_post_meta( $post_id, $k, isset( $_POST[ $k ] ) ? sanitize_text_field( $_POST[ $k ] ) : '' );
	}
	foreach ( array( '_ymkrf_survey_id', '_ymkrf_survey_pub_id' ) as $k ) {
		update_post_meta( $post_id, $k, isset( $_POST[ $k ] ) ? (int) $_POST[ $k ] : 0 );
	}

	/* 担当スタッフ（スタッフの投稿ID。0なら未設定） */
	if ( isset( $_POST['_ymkrf_staff'] ) ) {
		update_post_meta( $post_id, '_ymkrf_staff', (int) $_POST['_ymkrf_staff'] );
	}

	/* 題名が空なら、内容から自動でつけます */
	$p = get_post( $post_id );
	if ( $p && trim( $p->post_title ) === '' ) {
		$parts = ymkrf_voice_meta_array( $post_id, '_ymkrf_parts' );
		$cust  = get_post_meta( $post_id, '_ymkrf_customer', true );
		$t = ( $parts ? implode( '・', array_slice( $parts, 0, 2 ) ) . 'のリフォーム' : 'お客様の声' );
		if ( $cust ) $t = $cust . '　' . $t;
		remove_action( 'save_post_ymkrf_voice', __FUNCTION__ );
		wp_update_post( array( 'ID' => $post_id, 'post_title' => $t ) );
	}
} );

/* 公開用の画像（塗りつぶし済み）を受け取ってメディアに入れます */
add_action( 'wp_ajax_ymkrf_voice_pub_image', function () {
	check_ajax_referer( 'ymkrf_voice_img', 'nonce' );
	if ( ! current_user_can( 'upload_files' ) ) wp_send_json_error( '権限がありません' );

	$data = isset( $_POST['data'] ) ? (string) $_POST['data'] : '';
	if ( strpos( $data, 'data:image/jpeg;base64,' ) !== 0 ) wp_send_json_error( '画像を受け取れませんでした' );
	$bin = base64_decode( substr( $data, strlen( 'data:image/jpeg;base64,' ) ) );
	if ( ! $bin ) wp_send_json_error( '画像を読み取れませんでした' );

	/* ファイル名は案件番号ではなく通し番号にします（番号が外に出ないように） */
	$seq  = (int) get_option( 'ymkrf_voice_seq', 0 ) + 1;
	update_option( 'ymkrf_voice_seq', $seq );
	$name = sprintf( 'voice-%04d.jpg', $seq );

	$up = wp_upload_bits( $name, null, $bin );
	if ( ! empty( $up['error'] ) ) wp_send_json_error( $up['error'] );

	$att = array(
		'post_mime_type' => 'image/jpeg',
		'post_title'     => 'お客様アンケート ' . sprintf( '%04d', $seq ),
		'post_content'   => '',
		'post_status'    => 'inherit',
	);
	$id = wp_insert_attachment( $att, $up['file'] );
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $up['file'] ) );
	update_post_meta( $id, '_ymkrf_is_survey_public', '1' );

	wp_send_json_success( array( 'id' => $id, 'url' => wp_get_attachment_url( $id ) ) );
} );


/* ============================================================
   3. 表示に使う関数
   ============================================================ */

function ymkrf_voice_meta_array( $post_id, $key ) {
	$v = (string) get_post_meta( $post_id, $key, true );
	return array_values( array_filter( array_map( 'trim', explode( ',', $v ) ) ) );
}

/**
 * 満足度の点数。
 *
 *   1. お客様がアンケートに書かれた点数（「満足度は何点ですか？」の欄）を最優先
 *   2. その記入がないときだけ、③〜⑧の評価から計算
 *
 * 管理画面の「満足度」欄が空のときだけ 2 になります。
 */
function ymkrf_voice_score( $post_id ) {
	$s = get_post_meta( $post_id, '_ymkrf_score', true );
	if ( $s !== '' && $s !== null ) return (int) $s;
	$r = array();
	foreach ( array_keys( ymkrf_voice_rating_fields() ) as $k ) {
		$v = (int) get_post_meta( $post_id, $k, true );
		if ( $v ) $r[] = $v;
	}
	return function_exists( 'ymkrf_score_from_ratings' ) ? ymkrf_score_from_ratings( $r ) : 0;
}

/**
 * チェックの内容から、紹介文を組み立てます。
 *
 * 同じ言い回しばかりになると、Googleに「同じページ」と見なされます。
 * 案件番号をもとに言い回しを選び、ページごとに違う文になるようにしています。
 * （番号が同じなら毎回同じ文になるので、表示がころころ変わることはありません）
 */
function ymkrf_voice_summary( $post_id ) {
	$parts   = ymkrf_voice_meta_array( $post_id, '_ymkrf_parts' );
	$reasons = ymkrf_voice_meta_array( $post_id, '_ymkrf_reasons' );
	$cust    = ymkrf_voice_customer_label( $post_id );
	$shop    = ymkrf_voice_shop_name( $post_id );
	$out     = array();

	/* 言い回しの選び方。案件番号の数字を種にします */
	$seed = (int) preg_replace( '/[^0-9]/', '', (string) get_post_meta( $post_id, '_ymkrf_case_no', true ) );
	if ( ! $seed ) $seed = (int) $post_id;
	$pick = function ( $list ) use ( $seed ) { return $list[ $seed % count( $list ) ]; };

	if ( $parts ) {
		$pl = implode( '・', $parts );
		$out[] = $pick( array(
			( $cust ? $cust . 'より、' : '' ) . $pl . 'の工事をご用命いただきました。',
			$pl . 'のリフォームをお手伝いさせていただきました' . ( $cust ? '（' . $cust . '）' : '' ) . '。',
			( $shop ? $shop . 'が担当し、' : '' ) . $pl . 'の工事をさせていただきました。',
		) );
	}
	if ( $reasons ) {
		$rl = implode( '」「', $reasons );
		$out[] = $pick( array(
			'ヤマキシをお選びいただいた理由は「' . $rl . '」でした。',
			'「' . $rl . '」を決め手にお選びいただきました。',
			'お選びいただいた理由としては「' . $rl . '」を挙げていただいています。',
		) );
	}

	$good = array();
	foreach ( ymkrf_voice_rating_fields() as $k => $label ) {
		if ( (int) get_post_meta( $post_id, $k, true ) >= 3 ) $good[] = trim( $label, '「」' );
	}
	if ( $good ) {
		$gl = implode( '・', $good );
		$out[] = $pick( array(
			$gl . 'について、良い評価をいただきました。',
			$gl . 'は、ご満足いただけたとのことです。',
			'とくに' . $gl . 'をご評価いただいています。',
		) );
	}

	$rec = (int) get_post_meta( $post_id, '_ymkrf_recommend', true );
	if ( $rec >= 3 ) {
		$rl = ymkrf_voice_recommend_labels();
		$out[] = 'お知り合いへのご紹介についても「' . $rl[ $rec ] . '」とお答えいただいています。';
	}
	$score = ymkrf_voice_score( $post_id );
	if ( $score ) {
		$out[] = $pick( array(
			'満足度は100点満点中' . $score . '点でした。',
			'いただいた満足度は' . $score . '点です。',
			'満足度の欄には' . $score . '点とご記入いただきました。',
		) );
	}
	return implode( '', $out );
}

/* 一覧・詳細で使う、アンケート画像のタグ。
   クリックで拡大しますが、<a href> で包んであるので
   検索エンジンからもふつうの画像リンクとして読まれます。 */
function ymkrf_voice_survey_figure( $post_id ) {
	$pid = (int) get_post_meta( $post_id, '_ymkrf_survey_pub_id', true );
	if ( ! $pid ) return '';

	/* 縮小されたサイズを使うとにじむので、いつでも原寸（幅1600px）を出します */
	$full = wp_get_attachment_url( $pid );
	if ( ! $full ) return '';
	$meta = wp_get_attachment_metadata( $pid );
	$w    = ! empty( $meta['width'] )  ? (int) $meta['width']  : 1600;
	$hgt  = ! empty( $meta['height'] ) ? (int) $meta['height'] : 1132;

	$alt  = 'お客様アンケート「仕事の通信簿」の実物';
	$case = trim( (string) get_post_meta( $post_id, '_ymkrf_case_no', true ) );

	$h  = '<figure class="p-voice__sheet">';
	$h .= '<a class="js-lightbox" href="' . esc_url( $full ) . '" data-caption="' . esc_attr( $alt ) . '">';
	$h .= '<img src="' . esc_url( $full ) . '" width="' . $w . '" height="' . $hgt . '"'
	    . ' alt="' . esc_attr( $alt ) . '" loading="lazy" decoding="async">';
	$h .= '<span class="p-voice__zoom">クリックで拡大</span>';
	$h .= '</a>';
	/* 案件番号は、画像の下の右はしに小さく */
	if ( $case !== '' ) $h .= '<figcaption class="p-voice__case">' . esc_html( $case ) . '</figcaption>';
	$h .= '</figure>';
	return $h;
}

/* お客様イメージのイラスト（無ければ空） */
function ymkrf_voice_illust_img( $post_id, $size = 96 ) {
	$f = trim( (string) get_post_meta( $post_id, '_ymkrf_illust', true ) );
	if ( $f === '' ) return '';
	if ( ! file_exists( ymkrf_voice_illust_dir() . '/' . $f ) ) return '';
	return '<img class="p-voice__illust" src="' . esc_url( ymkrf_voice_illust_url() . '/' . $f ) . '"'
	     . ' width="' . (int) $size . '" height="' . (int) $size . '"'
	     . ' alt="" loading="lazy" decoding="async">';
}

/* 施工した店舗の名前 */
function ymkrf_voice_shop_name( $post_id ) {
	$slug = trim( (string) get_post_meta( $post_id, '_ymkrf_shop', true ) );
	if ( $slug === '' ) return '';
	$t = get_term_by( 'slug', $slug, 'ymkrf_shop' );
	return ( $t && ! is_wp_error( $t ) ) ? $t->name : '';
}

/**
 * 案件番号でつながる施工事例をさがします。
 * 施工事例の側にも同じ「案件番号」を入れておくと、自動でリンクが出ます。
 * （施工事例はこれから登録していく予定とのことなので、
 *   見つからないときは何も出しません）
 */
function ymkrf_voice_linked_works( $post_id ) {
	$no = trim( (string) get_post_meta( $post_id, '_ymkrf_case_no', true ) );
	if ( $no === '' ) return array();
	return get_posts( array(
		'post_type'      => 'ymkrf_works',
		'posts_per_page' => 5,
		'post_status'    => 'publish',
		'meta_query'     => array( array( 'key' => '_ymkrf_case_no', 'value' => $no ) ),
	) );
}

/* 一覧に出す、短いご感想 */
function ymkrf_voice_excerpt( $post_id, $len = 90 ) {
	$c = trim( (string) get_post_meta( $post_id, '_ymkrf_comment', true ) );
	if ( $c === '' ) $c = trim( (string) get_post_meta( $post_id, '_ymkrf_after', true ) );
	if ( $c === '' ) $c = ymkrf_voice_summary( $post_id );
	return mb_strimwidth( $c, 0, $len * 2, '…', 'UTF-8' );
}


/* ============================================================
   4. 管理画面の一覧に出す列
      一覧の主役は「案件番号」です。
      題名（オイルタンクのリフォーム など）は同じものが並んでしまい
      見分けがつかないので、一覧では出しません。
      かわりに、いちばん左の列に案件番号を出して、
      そこから編集画面へ入っていただきます。
   ============================================================ */
/**
 * 案件番号でつながっている投稿をさがします。
 * お客様の声と施工事例に、同じ案件番号を入れておくと、たがいにつながります。
 * 下書きのものも数えます（一覧で「もう入れたかどうか」を見たいので）。
 */
function ymkrf_linked_by_case_no( $case_no, $post_type ) {
	$no = trim( (string) $case_no );
	if ( $no === '' ) return array();
	return get_posts( array(
		'post_type'      => $post_type,
		'posts_per_page' => 5,
		'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
		'fields'         => 'ids',
		'meta_query'     => array( array( 'key' => '_ymkrf_case_no', 'value' => $no ) ),
	) );
}

/**
 * 一覧の「つながっているか」の欄。
 *   ● 済 …… 同じ案件番号のものがあります（クリックでその編集画面へ）
 *   未 ……… まだありません
 *   —  ……… 案件番号が入っていないので、つなげられません
 */
function ymkrf_case_link_cell( $post_id, $target_type ) {
	$no = trim( (string) get_post_meta( $post_id, '_ymkrf_case_no', true ) );
	if ( $no === '' ) {
		return '<span style="color:#a7aaad" title="案件番号が入っていないので、つなげられません">—</span>';
	}

	$ids = ymkrf_linked_by_case_no( $no, $target_type );
	if ( ! $ids ) {
		$label = ( $target_type === 'ymkrf_works' ) ? '施工事例' : 'お客様の声';
		return '<span style="color:#b26a00" title="同じ案件番号の' . esc_attr( $label ) . 'は、まだありません">未</span>';
	}

	/* 「● 済」だけを出します。押すと、つながっている相手の編集画面へ移ります。
	   （題名まで出すと一覧が読みにくくなるため） */
	$label = ( $target_type === 'ymkrf_works' ) ? '施工事例' : 'お客様の声';
	$url   = (string) get_edit_post_link( (int) $ids[0] );
	$title = trim( (string) get_post_field( 'post_title', (int) $ids[0] ) );

	return '<a href="' . esc_url( $url ) . '" style="color:#118a3d;font-weight:700;text-decoration:none"'
	     . ' title="' . esc_attr( $label . '「' . $title . '」をひらく' ) . '">● 済</a>';
}

/* ============================================================
   担当スタッフのえらび方（施工事例の登録ページと同じ見た目・同じ動きです）
   ============================================================ */
add_action( 'admin_head', function () {
	$sc = get_current_screen();
	if ( ! $sc || $sc->post_type !== 'ymkrf_voice' ) return;
	if ( ! in_array( $sc->base, array( 'post' ), true ) ) return;
	echo '<style>
	  .ymkrf-pick{position:relative;display:inline-block}
	  .ymkrf-pick__btn{min-width:260px;text-align:left}
	  .ymkrf-pick__btn--set{font-weight:700}
	  .ymkrf-pick__btn::after{content:" \25be";float:right;color:#787c82}
	  .ymkrf-pick__clear{margin-left:10px;font-size:12px;color:#787c82;text-decoration:underline;cursor:pointer}
	  .ymkrf-pick__menu{
	    position:absolute;z-index:100;top:100%;left:0;margin-top:2px;
	    min-width:260px;background:#fff;border:1px solid #c3c4c7;border-radius:6px;
	    box-shadow:0 6px 20px rgba(0,0,0,.14);padding:4px}
	  .ymkrf-pick__scroll{max-height:300px;overflow:auto}
	  .ymkrf-pick__item{
	    display:block;width:100%;text-align:left;border:0;background:none;cursor:pointer;
	    padding:6px 10px;border-radius:4px;font-size:13.5px;line-height:1.5}
	  .ymkrf-pick__item:hover{background:#fff2ee}
	  .ymkrf-pick__nm{font-weight:700}
	  .ymkrf-pick__ro{margin-left:8px;font-size:11.5px;color:#787c82}
	  .ymkrf-pick__empty{margin:6px 10px;font-size:12px;color:#a7aaad}
	  .ymkrf-pick__more{
	    position:relative;margin-top:4px;padding:7px 10px;border-top:1px solid #f0f0f1;
	    font-size:13.5px;font-weight:700;color:#2271b1;cursor:default}
	  .ymkrf-pick__more>span::after{content:" \25b8"}
	  .ymkrf-pick__more:hover{background:#f6f7f7}
	  .ymkrf-pick__sub{
	    display:none;position:absolute;left:100%;top:-4px;margin-left:2px;
	    width:520px;max-height:420px;overflow:auto;
	    background:#fff;border:1px solid #c3c4c7;border-radius:6px;
	    box-shadow:0 6px 20px rgba(0,0,0,.14);padding:8px}
	  .ymkrf-pick__more:hover .ymkrf-pick__sub{display:block}
	  .ymkrf-pick__gttl{
	    margin:8px 0 4px;padding:0 6px;font-size:11.5px;font-weight:700;color:#646970;
	    border-left:3px solid #fe3301;line-height:1.4}
	  .ymkrf-pick__gttl:first-child{margin-top:0}
	  .ymkrf-pick__grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 8px}
	</style>';
} );

add_action( 'admin_footer', function () {
	$sc = get_current_screen();
	if ( ! $sc || $sc->post_type !== 'ymkrf_voice' ) return;
	if ( ! in_array( $sc->base, array( 'post' ), true ) ) return;
	?>
	<script>
	jQuery(function ($) {
		var $pick = $('#ymkrf-vstaff-pick');
		if (!$pick.length || !window.ymkrfVStaffList) return;

		var LIST  = window.ymkrfVStaffList;
		var ORDER = window.ymkrfVStaffOrder || [];
		var $val  = $('#ymkrf-vstaff-val'), $btn = $('#ymkrf-vstaff-btn'), $menu = $('#ymkrf-vstaff-menu');
		var $shopSel = $('#ymkrf-vshop-sel');

		function byId(id) {
			for (var i = 0; i < LIST.length; i++) if (LIST[i].id === +id) return LIST[i];
			return null;
		}
		function label(p) { return p.name + (p.sname ? '（' + p.sname + '）' : ''); }

		function drawBtn() {
			var p = byId($val.val());
			$btn.text(p ? label(p) : '（えらんでください）')
				.toggleClass('ymkrf-pick__btn--set', !!p);
			$('#ymkrf-vstaff-clear').toggle(!!p);
		}
		function row(p) {
			return $('<button type="button" class="ymkrf-pick__item">')
				.attr('data-id', p.id)
				.append($('<span class="ymkrf-pick__nm">').text(p.name))
				.append(p.role ? $('<span class="ymkrf-pick__ro">').text(p.role) : null);
		}
		/* 全員を店舗ごとにまとめた枠 */
		function allPanel() {
			var $sub = $('<div class="ymkrf-pick__sub">'), seen = {};
			var order = ORDER.slice();
			LIST.forEach(function (p) { if (order.indexOf(p.shop) < 0) order.push(p.shop); });
			order.forEach(function (sh) {
				var mem = LIST.filter(function (p) { return p.shop === sh; });
				if (!mem.length || seen[sh]) return;
				seen[sh] = 1;
				$sub.append($('<p class="ymkrf-pick__gttl">').text(mem[0].sname || 'そのほか'));
				var $g = $('<div class="ymkrf-pick__grid">');
				mem.forEach(function (p) { $g.append(row(p)); });
				$sub.append($g);
			});
			return $sub;
		}
		function build() {
			var sh = $shopSel.length ? $shopSel.val() : '';
			$menu.empty();
			var $scroll = $('<div class="ymkrf-pick__scroll">');
			var mem = sh ? LIST.filter(function (p) { return p.shop === sh; }) : LIST;
			mem.forEach(function (p) { $scroll.append(row(p)); });
			if (sh && !mem.length) {
				$scroll.append($('<p class="ymkrf-pick__empty">').text('この店舗のスタッフはまだ登録されていません。'));
			}
			$menu.append($scroll);
			if (sh) {
				$menu.append($('<div class="ymkrf-pick__more">')
					.append($('<span>').text('その他（全員から選ぶ）'))
					.append(allPanel()));
			}
		}
		function open()  { build(); $menu.prop('hidden', false); }
		function close() { $menu.prop('hidden', true); }

		$btn.on('click', function (e) {
			e.preventDefault();
			if ($menu.prop('hidden')) open(); else close();
		});
		$menu.on('click', '.ymkrf-pick__item', function (e) {
			e.preventDefault();
			$val.val($(this).attr('data-id'));
			drawBtn();
			close();
		});
		$('#ymkrf-vstaff-clear').on('click', function (e) {
			e.preventDefault();
			$val.val(0); drawBtn(); close();
		});
		/* 店舗を変えたら、えらんでいた人がその店舗にいなければ外します */
		$shopSel.on('change', function () {
			var p = byId($val.val()), sh = $shopSel.val();
			if (p && sh && p.shop !== sh) { $val.val(0); drawBtn(); }
			if (!$menu.prop('hidden')) build();
		});
		$(document).on('click', function (e) {
			if (!$(e.target).closest('#ymkrf-vstaff-pick').length) close();
		});
		$(document).on('keydown', function (e) { if (e.key === 'Escape') close(); });

		drawBtn();
	});
	</script>
	<?php
} );

add_filter( 'manage_ymkrf_voice_posts_columns', function ( $cols ) {
	$new = array();
	foreach ( $cols as $k => $v ) {
		if ( $k === 'title' ) {
			$new['title'] = '案件番号';        /* 見出しだけ差しかえます */
			$new['ymkrf_shop']  = '施工店舗';
			$new['ymkrf_vstaff'] = '担当';
			$new['ymkrf_cust']  = 'お客様';
			$new['ymkrf_score'] = '満足度';
			$new['ymkrf_parts'] = '工事箇所';
			$new['ymkrf_works'] = '施工事例';
			continue;
		}
		$new[ $k ] = $v;
	}
	return $new;
} );

/* 一覧のリンク文字を、題名ではなく案件番号にします。
   （編集・クイック編集・ゴミ箱のリンクはそのまま使えます） */
add_filter( 'the_title', function ( $title, $post_id = 0 ) {
	if ( ! is_admin() ) return $title;
	if ( ! isset( $GLOBALS['pagenow'] ) || $GLOBALS['pagenow'] !== 'edit.php' ) return $title;
	if ( get_post_type( $post_id ) !== 'ymkrf_voice' ) return $title;

	$no = trim( (string) get_post_meta( $post_id, '_ymkrf_case_no', true ) );
	return $no !== '' ? $no : '（案件番号なし）';
}, 10, 2 );

add_action( 'manage_ymkrf_voice_posts_custom_column', function ( $col, $post_id ) {
	switch ( $col ) {
		case 'ymkrf_shop':
			$v = ymkrf_voice_shop_name( $post_id );
			echo $v ? esc_html( $v ) : '<span style="color:#a7aaad">—</span>';
			break;
		case 'ymkrf_vstaff':
			echo ymkrf_staff_admin_cell( (int) get_post_meta( $post_id, '_ymkrf_staff', true ) );
			break;
		case 'ymkrf_cust':
			$v = ymkrf_voice_customer_label( $post_id );
			$ill = trim( (string) get_post_meta( $post_id, '_ymkrf_illust', true ) );
			if ( $ill && file_exists( ymkrf_voice_illust_dir() . '/' . $ill ) ) {
				echo '<img src="' . esc_url( ymkrf_voice_illust_url() . '/' . $ill ) . '"'
				   . ' style="width:30px;height:30px;object-fit:contain;vertical-align:-9px;margin-right:6px" alt="">';
			}
			echo $v ? esc_html( $v ) : '<span style="color:#a7aaad">—</span>';
			break;
		case 'ymkrf_score':
			$s   = ymkrf_voice_score( $post_id );
			$raw = get_post_meta( $post_id, '_ymkrf_score', true );
			if ( ! $s ) {
				echo '<span style="color:#a7aaad">—</span>';
			} elseif ( $raw === '' || $raw === null ) {
				/* お客様が書かれた点数ではなく、③〜⑧から計算した点数です */
				echo esc_html( $s ) . '点<br><span style="color:#b26a00;font-size:11px"'
				   . ' title="アンケートに点数の記入が入力されていません。③〜⑧の評価から計算しています">自動計算</span>';
			} else {
				echo esc_html( $s ) . '点';
			}
			break;
		case 'ymkrf_parts':
			$p = ymkrf_voice_meta_array( $post_id, '_ymkrf_parts' );
			echo $p ? esc_html( implode( '／', $p ) ) : '<span style="color:#a7aaad">—</span>';
			break;
		case 'ymkrf_works':
			echo ymkrf_case_link_cell( $post_id, 'ymkrf_works' );
			break;
	}
}, 10, 2 );

/* 見出しをクリックで並べ替えできる列。
   ふだんは登録順（日付順）です。見出しを押したときだけ並びかわります。
     案件番号 …… 番号順
     施工店舗 …… 店舗ごとにまとまります
     工事箇所 …… 箇所ごとにまとまります
     施工事例 …… 済／未でまとまります
   満足度は並べ替えできません（点数で順位をつけたくないため）。 */
add_filter( 'manage_edit-ymkrf_voice_sortable_columns', function ( $cols ) {
	unset( $cols['ymkrf_score'] );
	$cols['title']       = 'ymkrf_case';   /* いちばん左＝案件番号 */
	$cols['ymkrf_shop']  = 'ymkrf_shop';
	$cols['ymkrf_parts'] = 'ymkrf_parts';
	$cols['ymkrf_works'] = 'ymkrf_link';
	return $cols;
} );
add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() || ! $q->is_main_query() ) return;
	if ( $q->get( 'post_type' ) !== 'ymkrf_voice' ) return;

	$map = array(
		'ymkrf_case'  => '_ymkrf_case_no',
		'ymkrf_shop'  => '_ymkrf_shop',
		'ymkrf_parts' => '_ymkrf_parts',
	);
	$by = $q->get( 'orderby' );
	if ( isset( $map[ $by ] ) ) {
		$q->set( 'meta_key', $map[ $by ] );
		$q->set( 'orderby', 'meta_value' );
	}
} );

/**
 * 「施工事例（済／未）」での並べ替え。
 * 同じ案件番号のものが相手側にあるかどうかを、その場で数えて並べます。
 * （印を持たせずに毎回数えるので、ずれることがありません）
 */
add_filter( 'posts_clauses', function ( $c, $q ) {
	if ( ! is_admin() || ! $q->is_main_query() ) return $c;
	if ( $q->get( 'orderby' ) !== 'ymkrf_link' ) return $c;

	$type = $q->get( 'post_type' );
	if ( $type === 'ymkrf_voice' )      $other = 'ymkrf_works';
	elseif ( $type === 'ymkrf_works' )  $other = 'ymkrf_voice';
	else return $c;

	global $wpdb;
	$c['join'] .= " LEFT JOIN {$wpdb->postmeta} ymkrf_cn"
	            . " ON ymkrf_cn.post_id = {$wpdb->posts}.ID"
	            . " AND ymkrf_cn.meta_key = '_ymkrf_case_no' ";

	$has = $wpdb->prepare(
		"( SELECT COUNT(*) FROM {$wpdb->postmeta} ymkrf_m2"
		. " INNER JOIN {$wpdb->posts} ymkrf_p2 ON ymkrf_p2.ID = ymkrf_m2.post_id"
		. " WHERE ymkrf_p2.post_type = %s AND ymkrf_p2.post_status <> 'trash'"
		. " AND ymkrf_m2.meta_key = '_ymkrf_case_no'"
		. " AND ymkrf_m2.meta_value = ymkrf_cn.meta_value"
		. " AND ymkrf_cn.meta_value <> '' )",
		$other
	);

	$order = ( strtoupper( (string) $q->get( 'order' ) ) === 'ASC' ) ? 'ASC' : 'DESC';
	$c['orderby'] = "( {$has} > 0 ) {$order}, {$wpdb->posts}.post_date DESC";
	return $c;
}, 10, 2 );

/* 列の幅 */
add_action( 'admin_head', function () {
	$s = get_current_screen();
	if ( ! $s ) return;
	if ( $s->id === 'edit-ymkrf_voice' ) {
		echo '<style>
		  .column-title{width:130px}
		  .column-ymkrf_shop{width:110px}
		  .column-ymkrf_vstaff{width:130px}
		  .column-ymkrf_cust{width:170px}
		  .column-ymkrf_score{width:80px}
		  .column-ymkrf_parts{width:170px}
		  .column-ymkrf_works{width:170px}
		</style>';
	} elseif ( $s->id === 'edit-ymkrf_works' ) {
		echo '<style>
		  .column-ymkrf_case{width:110px}
		  .column-ymkrf_voice{width:170px}
		</style>';
	}
} );


/* ============================================================
   4-2. 施工事例の一覧にも、つながりを出します
        お客様の声と同じ「案件番号」を入れておくと、
        たがいに ● 済 と出て、クリックで行き来できます。
   ============================================================ */
add_filter( 'manage_ymkrf_works_posts_columns', function ( $cols ) {
	$new = array();
	foreach ( $cols as $k => $v ) {
		$new[ $k ] = $v;
		if ( $k === 'title' ) {
			$new['ymkrf_case']  = '案件番号';
			$new['ymkrf_voice'] = 'お客様の声';
		}
	}
	return $new;
} );

add_action( 'manage_ymkrf_works_posts_custom_column', function ( $col, $post_id ) {
	if ( $col === 'ymkrf_case' ) {
		$v = trim( (string) get_post_meta( $post_id, '_ymkrf_case_no', true ) );
		echo $v ? esc_html( $v ) : '<span style="color:#a7aaad">—</span>';
	} elseif ( $col === 'ymkrf_voice' ) {
		echo ymkrf_case_link_cell( $post_id, 'ymkrf_voice' );
	}
}, 10, 2 );

/* 案件番号・お客様の声（済／未）で並べ替えできます */
add_filter( 'manage_edit-ymkrf_works_sortable_columns', function ( $cols ) {
	$cols['ymkrf_case']  = 'ymkrf_case';
	$cols['ymkrf_voice'] = 'ymkrf_link';
	return $cols;
} );
add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() || ! $q->is_main_query() ) return;
	if ( $q->get( 'post_type' ) !== 'ymkrf_works' ) return;
	if ( $q->get( 'orderby' ) === 'ymkrf_case' ) {
		$q->set( 'meta_key', '_ymkrf_case_no' );
		$q->set( 'orderby', 'meta_value' );
	}
} );


/* ============================================================
   5. 検索エンジン対策
      お客様の声は、どうしてもページの形が似ます。
      アンケート用紙が同じで、見出しも同じだからです。
      そのままだと Google に「同じページ」と見なされ、
      サーチコンソールに「重複したコンテンツ」の指摘が出ます。

      対策は3つです。
        (1) URLを内容の分かる英字にして、重複させない
        (2) 題名・説明文をページごとに変える
        (3) ページごとに違う関連リンクを持たせる
   ============================================================ */

/* --- (1) URL --------------------------------------------------
   「修理・小工事のリフォーム」のような題名は何件も出てくるので、
   URLは「工事箇所 ／ 案件番号」の英字にします。

     1件のページ … /voice/oiltank/2607-0389/
     箇所ごとの一覧 … /voice/oiltank/
     ぜんぶの一覧 …… /voice/

   前半（oiltank）が工事箇所、後半（2607-0389）が案件番号です。
   案件番号は重ならないので、URLも絶対に重複しません。
   工事箇所だけのURLを開くと、その箇所のお客様の声だけが並びます。
   ------------------------------------------------------------- */

/** 石川・福井の市町 → ローマ字 */
function ymkrf_voice_city_roman() {
	return array(
		'金沢市'=>'kanazawa','小松市'=>'komatsu','白山市'=>'hakusan','野々市市'=>'nonoichi',
		'加賀市'=>'kaga','能美市'=>'nomi','川北町'=>'kawakita','津幡町'=>'tsubata',
		'内灘町'=>'uchinada','かほく市'=>'kahoku','羽咋市'=>'hakui','七尾市'=>'nanao',
		'志賀町'=>'shika','宝達志水町'=>'hodatsushimizu','中能登町'=>'nakanoto',
		'輪島市'=>'wajima','珠洲市'=>'suzu','穴水町'=>'anamizu','能登町'=>'noto',
		'福井市'=>'fukui','あわら市'=>'awara','坂井市'=>'sakai','勝山市'=>'katsuyama',
		'大野市'=>'ono','永平寺町'=>'eiheiji','鯖江市'=>'sabae','越前市'=>'echizen',
		'敦賀市'=>'tsuruga','小浜市'=>'obama','越前町'=>'echizencho','池田町'=>'ikeda',
	);
}

/** 工事箇所 → ローマ字 */
function ymkrf_voice_part_roman() {
	return array(
		'キッチン'=>'kitchen','浴室'=>'bath','トイレ'=>'toilet','洗面室'=>'washstand',
		'エコキュート'=>'ecocute','給湯器'=>'boiler','オイルタンク'=>'oiltank',
		'エクステリア'=>'exterior','カーポート'=>'carport','外壁'=>'wall','屋根'=>'roof',
		'窓・サッシ'=>'window','レンジフード'=>'rangehood','ドア'=>'door',
		'蓄電池'=>'battery','太陽光発電'=>'solar','修理・小工事'=>'repair',
		'改装・内装'=>'interior','その他'=>'other',
	);
}

/** ローマ字 → 工事箇所（上の表の逆引き。例：oiltank → オイルタンク） */
function ymkrf_voice_part_from_roman( $roman ) {
	$map = array_flip( ymkrf_voice_part_roman() );
	$roman = strtolower( (string) $roman );
	return isset( $map[ $roman ] ) ? $map[ $roman ] : '';
}

/**
 * この投稿の「工事箇所」の英字。URLの前半（/voice/ここ/番号/）に入ります。
 * 工事箇所が複数あるときは、いちばん上のものを使います。
 * 何も選ばれていないときは other になります。
 */
function ymkrf_voice_part_slug( $post_id ) {
	$pmap = ymkrf_voice_part_roman();
	foreach ( ymkrf_voice_meta_array( $post_id, '_ymkrf_parts' ) as $p ) {
		if ( isset( $pmap[ $p ] ) ) return $pmap[ $p ];
	}
	return 'other';
}

/** URLの中の %ymkrf_vpart% を、その投稿の工事箇所に置きかえます */
add_filter( 'post_type_link', function ( $link, $post ) {
	if ( ! $post || $post->post_type !== 'ymkrf_voice' ) return $link;
	return str_replace( '%ymkrf_vpart%', ymkrf_voice_part_slug( $post->ID ), $link );
}, 10, 2 );

/** この投稿にふさわしいURLの後半（＝案件番号） */
function ymkrf_voice_make_slug( $post_id ) {
	return strtolower( preg_replace( '/[^0-9A-Za-z-]/', '',
		(string) get_post_meta( $post_id, '_ymkrf_case_no', true ) ) );
}

/**
 * 保存のたびにURLの後半を、案件番号にそろえます。
 * （案件番号が入っていないあいだは、触りません）
 */
add_action( 'save_post_ymkrf_voice', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;

	$want = ymkrf_voice_make_slug( $post_id );
	if ( $want === '' ) return;

	$p = get_post( $post_id );
	if ( ! $p ) return;
	$now = rawurldecode( (string) $p->post_name );

	/* すでに案件番号だけのURLになっていれば、そのままにします
	   （2607-0389 や、重なったときの 2607-0389-2 を含みます） */
	if ( preg_match( '/^' . preg_quote( $want, '/' ) . '(-[0-9]+)?$/', $now ) ) return;

	remove_action( 'save_post_ymkrf_voice', __FUNCTION__ );
	wp_update_post( array(
		'ID'        => $post_id,
		'post_name' => wp_unique_post_slug( $want, $post_id, $p->post_status, $p->post_type, 0 ),
	) );
}, 30 );


/* --- (1-1) 投稿の題名 --------------------------------------------
   ワードプレスの題名欄と、ページに出る大見出しを同じにします。

     金沢市｜オイルタンクのリフォーム
     └市・町┘ └ 工事の内容 ─────┘

   保存するたびに、いまの「お客様（市・町）」に合わせて組み立て直します。
   工事の内容の部分（｜より右）は、手で書きかえていただけます。
   ------------------------------------------------------------- */

/**
 * 工事箇所から、題名の「工事の内容」の部分を作ります。
 *   例）キッチン・その他 → キッチンリフォームのお客様の声
 * 「その他」は検索されない言葉なので、題名には使いません。
 */
function ymkrf_voice_title_from_parts( $post_id ) {
	$ps = ymkrf_voice_meta_array( $post_id, '_ymkrf_parts' );
	$ps = array_values( array_filter( $ps, function ( $p ) { return $p !== 'その他'; } ) );
	if ( ! $ps ) return 'リフォームのお客様の声';

	$name = implode( '・', array_slice( $ps, 0, 2 ) );

	/* 「修理・小工事」「改装・内装」は、それだけで意味が通るので
	   うしろに「リフォーム」を足しません */
	$asis = array( '修理・小工事', '改装・内装' );
	if ( in_array( $ps[0], $asis, true ) ) return $name . 'のお客様の声';

	return $name . 'リフォームのお客様の声';
}

/** 題名から「工事の内容」の部分だけを取り出します */
function ymkrf_voice_title_body( $title ) {
	$t = (string) $title;
	$t = preg_replace( '/^お客様の声[\s　]*/u', '', $t );
	if ( strpos( $t, '｜' ) !== false ) {
		$p = explode( '｜', $t, 2 );
		$t = $p[1];
	}
	return trim( $t );
}

/** 一覧や関連リンクで使う、短い題名（例：オイルタンクのリフォーム） */
function ymkrf_voice_short_title( $post_id ) {
	return ymkrf_voice_title_body( get_post_field( 'post_title', $post_id ) );
}

add_action( 'save_post_ymkrf_voice', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;

	$p = get_post( $post_id );
	if ( ! $p ) return;
	$now = (string) $p->post_title;

	$body = ymkrf_voice_title_body( $now );
	if ( $body === '' ) $body = ymkrf_voice_title_from_parts( $post_id );

	$city = trim( (string) get_post_meta( $post_id, '_ymkrf_city', true ) );
	$want = ( $city !== '' ? $city . '｜' : '' ) . $body;
	if ( $want === $now ) return;

	remove_action( 'save_post_ymkrf_voice', __FUNCTION__, 25 );
	wp_update_post( array( 'ID' => $post_id, 'post_title' => $want ) );
}, 25 );



/* すでに登録ずみの題名も、1回だけそろえます
   （数字を上げると、もう一度だけ実行されます） */
add_action( 'admin_init', function () {
	if ( get_option( 'ymkrf_voice_title_ver' ) === '3' ) return;

	$ids = get_posts( array(
		'post_type' => 'ymkrf_voice', 'posts_per_page' => -1,
		'fields' => 'ids', 'post_status' => 'any',
	) );
	foreach ( (array) $ids as $id ) {
		$p = get_post( $id );
		if ( ! $p ) continue;
		/* 今回は工事箇所から作り直します（「その他」を題名から外すため） */
		$body = ymkrf_voice_title_from_parts( $id );
		$city = trim( (string) get_post_meta( $id, '_ymkrf_city', true ) );
		$want = ( $city !== '' ? $city . '｜' : '' ) . $body;
		if ( $want !== $p->post_title ) {
			wp_update_post( array( 'ID' => $id, 'post_title' => $want ) );
		}
	}
	update_option( 'ymkrf_voice_title_ver', '3' );
}, 21 );

/**
 * すでに登録ずみのお客様の声のURLを、1回だけ新しい形にそろえます。
 * 前のURLは自動で控えが残るので、古いリンクで来られた方も
 * 新しいページへご案内できます。
 * （数字を上げると、もう一度だけ実行されます）
 */
add_action( 'admin_init', function () {
	if ( get_option( 'ymkrf_voice_url_ver' ) === '2' ) return;

	$ids = get_posts( array(
		'post_type' => 'ymkrf_voice', 'posts_per_page' => -1,
		'fields' => 'ids', 'post_status' => 'any',
	) );
	foreach ( (array) $ids as $id ) {
		$want = ymkrf_voice_make_slug( $id );
		if ( $want === '' ) continue;
		$p = get_post( $id );
		if ( ! $p ) continue;
		$now = rawurldecode( (string) $p->post_name );
		if ( preg_match( '/^' . preg_quote( $want, '/' ) . '(-[0-9]+)?$/', $now ) ) continue;
		wp_update_post( array(
			'ID'        => $id,
			'post_name' => wp_unique_post_slug( $want, $id, $p->post_status, $p->post_type, 0 ),
		) );
	}
	update_option( 'ymkrf_voice_url_ver', '2' );
}, 20 );


/* --- (1-2) 工事箇所ごとの一覧（/voice/oiltank/） ----------------
   /voice/oiltank/ というURLのルールは、ワードプレスが
   「1件のページ」のルールを作るときに、いっしょに作ってくれます。
   ただ、そのままだと「どの投稿タイプの一覧か」が伝わらないので、
   ここで「お客様の声の一覧です」と足しています。
   ------------------------------------------------------------- */

add_filter( 'request', function ( $qv ) {
	if ( ! isset( $qv['ymkrf_vpart'] ) || $qv['ymkrf_vpart'] === '' ) return $qv;

	/* 1件のページ（/voice/oiltank/2607-0389/）のときは、そのままにします */
	if ( isset( $qv['ymkrf_voice'] ) || isset( $qv['name'] ) || isset( $qv['attachment'] ) ) return $qv;

	$qv['post_type'] = 'ymkrf_voice';
	return $qv;
} );

/* --- (1-3) 地域ごとの一覧（/voice/area/kanazawa/） ----------------
   「金沢市 リフォーム 口コミ」のような検索の受け皿になるページです。
   1件ごとのページのURLに市の名前を入れるより、
   こうしてまとまったページを作るほうが検索に強くなります。
   ------------------------------------------------------------- */

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'ymkrf_varea';
	return $vars;
} );

add_action( 'init', function () {
	/* 2ページ目から先 */
	add_rewrite_rule(
		'^voice/area/([a-z0-9-]+)/page/([0-9]+)/?$',
		'index.php?post_type=ymkrf_voice&ymkrf_varea=$matches[1]&paged=$matches[2]',
		'top'
	);
	/* 1ページ目（1件ページのルールより先に見てほしいので top） */
	add_rewrite_rule(
		'^voice/area/([a-z0-9-]+)/?$',
		'index.php?post_type=ymkrf_voice&ymkrf_varea=$matches[1]',
		'top'
	);
}, 5 );

/** ローマ字 → 市町（例：kanazawa → 金沢市） */
function ymkrf_voice_city_from_roman( $roman ) {
	$map = array_flip( ymkrf_voice_city_roman() );
	$roman = strtolower( (string) $roman );
	return isset( $map[ $roman ] ) ? $map[ $roman ] : '';
}

/** いま見ている一覧の市町（日本語）。ふつうの一覧のときは空です */
function ymkrf_voice_current_city() {
	$r = get_query_var( 'ymkrf_varea' );
	return $r ? ymkrf_voice_city_from_roman( $r ) : '';
}

/** 地域ごとの一覧のURL（例：/voice/area/kanazawa/） */
function ymkrf_voice_area_url( $roman ) {
	return home_url( '/voice/area/' . rawurlencode( $roman ) . '/' );
}

/**
 * 市町ごとの件数。
 * 返るのは array( '金沢市' => array( 'roman' => 'kanazawa', 'count' => 3 ), … )
 */
function ymkrf_voice_area_counts() {
	$ids = get_posts( array(
		'post_type' => 'ymkrf_voice', 'posts_per_page' => -1, 'fields' => 'ids',
	) );
	$cmap = ymkrf_voice_city_roman();
	$out  = array();
	foreach ( (array) $ids as $id ) {
		$c = trim( (string) get_post_meta( $id, '_ymkrf_city', true ) );
		if ( $c === '' || ! isset( $cmap[ $c ] ) ) continue;
		if ( ! isset( $out[ $c ] ) ) $out[ $c ] = array( 'roman' => $cmap[ $c ], 'count' => 0 );
		$out[ $c ]['count']++;
	}
	uasort( $out, function ( $a, $b ) { return $b['count'] - $a['count']; } );
	return $out;
}

/** いま見ている一覧の工事箇所（日本語）。ふつうの一覧のときは空です */
function ymkrf_voice_current_part() {
	$roman = get_query_var( 'ymkrf_vpart' );
	if ( ! $roman ) return '';
	return ymkrf_voice_part_from_roman( $roman );
}

/* 一覧を、その工事箇所のものだけにしぼります */
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) return;
	if ( ! $q->is_post_type_archive( 'ymkrf_voice' ) ) return;

	/* 1ページに出す件数。3列ならびに合わせて12件ずつにします。
	   13件目からは自動で2ページ目になります（下に「前へ／次へ」が出ます）。 */
	$q->set( 'posts_per_page', 12 );

	/* 市町でしぼる（/voice/area/kanazawa/） */
	$city = ymkrf_voice_city_from_roman( $q->get( 'ymkrf_varea' ) );
	if ( $city !== '' ) {
		$q->set( 'meta_query', array( array(
			'key'     => '_ymkrf_city',
			'value'   => $city,
			'compare' => '=',
		) ) );
		return;
	}

	/* 工事箇所でしぼる（/voice/kitchen/） */
	$part = ymkrf_voice_part_from_roman( $q->get( 'ymkrf_vpart' ) );
	if ( $part === '' ) return;

	$q->set( 'meta_query', array( array(
		'key'     => '_ymkrf_parts',
		'value'   => $part,
		'compare' => 'LIKE',
	) ) );
} );

/* 知らない市町のURLは、見つかりませんでした（404）にします */
add_action( 'template_redirect', function () {
	if ( ! is_post_type_archive( 'ymkrf_voice' ) ) return;
	$r = (string) get_query_var( 'ymkrf_varea' );
	if ( $r === '' ) return;
	if ( ymkrf_voice_city_from_roman( $r ) !== '' ) return;

	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
} );

/**
 * 前のURL（/voice/kanazawa-oiltank-2607-0389/ など）で来られた方を、
 * 新しいURLへご案内します（301＝ずっとこちら、という合図）。
 * 知らない工事箇所のURLは、見つかりませんでした（404）にします。
 */
add_action( 'template_redirect', function () {
	if ( ! is_post_type_archive( 'ymkrf_voice' ) ) return;

	$roman = (string) get_query_var( 'ymkrf_vpart' );
	if ( $roman === '' ) return;                                  /* ふつうの一覧 */
	if ( ymkrf_voice_part_from_roman( $roman ) !== '' ) return;   /* 正しい工事箇所 */

	$id = 0;

	/* いまのURL／前のURLで、そのままさがします */
	foreach ( array(
		array( 'name'       => $roman ),
		array( 'meta_key'   => '_wp_old_slug', 'meta_value' => $roman ),
	) as $args ) {
		if ( $id ) break;
		$hit = get_posts( array_merge( $args, array(
			'post_type' => 'ymkrf_voice', 'posts_per_page' => 1, 'fields' => 'ids',
		) ) );
		if ( $hit ) $id = (int) $hit[0];
	}

	/* それでも見つからなければ、末尾の案件番号でさがします
	   （kanazawa-oiltank-2607-0389 → 2607-0389） */
	if ( ! $id && preg_match( '/(\d{4})-?(\d{4})$/', $roman, $m ) ) {
		foreach ( array( $m[1] . '-' . $m[2], $m[1] . $m[2] ) as $no ) {
			if ( $id ) break;
			$hit = get_posts( array(
				'post_type' => 'ymkrf_voice', 'posts_per_page' => 1, 'fields' => 'ids',
				'meta_key'  => '_ymkrf_case_no', 'meta_value' => $no,
			) );
			if ( $hit ) $id = (int) $hit[0];
		}
	}

	if ( $id ) {
		wp_safe_redirect( get_permalink( $id ), 301 );
		exit;
	}

	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
} );

/**
 * 工事箇所ごとの件数。
 * 一覧ページの「箇所でさがす」ボタンに使います。
 * 返るのは array( 'オイルタンク' => array( 'roman' => 'oiltank', 'count' => 3 ), … )
 */
function ymkrf_voice_part_counts() {
	$ids = get_posts( array(
		'post_type' => 'ymkrf_voice', 'posts_per_page' => -1, 'fields' => 'ids',
	) );
	$pmap = ymkrf_voice_part_roman();
	$out  = array();
	foreach ( (array) $ids as $id ) {
		foreach ( ymkrf_voice_meta_array( $id, '_ymkrf_parts' ) as $p ) {
			if ( ! isset( $pmap[ $p ] ) ) continue;
			if ( ! isset( $out[ $p ] ) ) $out[ $p ] = array( 'roman' => $pmap[ $p ], 'count' => 0 );
			$out[ $p ]['count']++;
		}
	}
	/* 多い順に */
	uasort( $out, function ( $a, $b ) { return $b['count'] - $a['count']; } );
	return $out;
}

/** 工事箇所ごとの一覧のURL（例：/voice/oiltank/） */
function ymkrf_voice_part_url( $roman ) {
	return home_url( '/voice/' . rawurlencode( $roman ) . '/' );
}


/* --- (2) 題名・説明文 ------------------------------------------
   ページごとに違う文にします。
   ------------------------------------------------------------- */

/* 工事箇所ごと・地域ごとの一覧の題名 */
add_filter( 'document_title_parts', function ( $parts ) {
	if ( ! is_post_type_archive( 'ymkrf_voice' ) ) return $parts;

	$city = ymkrf_voice_current_city();
	if ( $city !== '' ) {
		$parts['title'] = $city . 'のリフォーム｜お客様の声・口コミ';
		return $parts;
	}

	$part = ymkrf_voice_current_part();
	if ( $part === '' ) return $parts;
	$parts['title'] = $part . 'リフォームのお客様の声・口コミ';
	return $parts;
}, 20 );

/* ブラウザのタブと検索結果に出る題名 */
add_filter( 'document_title_parts', function ( $parts ) {
	if ( ! is_singular( 'ymkrf_voice' ) ) return $parts;
	$id = get_the_ID();

	$p    = ymkrf_voice_meta_array( $id, '_ymkrf_parts' );
	$p    = array_values( array_filter( $p, function ( $v ) { return $v !== 'その他'; } ) );
	$cust = ymkrf_voice_customer_label( $id );
	$shop = ymkrf_voice_shop_name( $id );
	$sc   = ymkrf_voice_score( $id );

	$t = ( $p ? implode( '・', array_slice( $p, 0, 2 ) ) . 'のリフォーム' : 'リフォーム' );
	if ( $cust ) $t .= '｜' . $cust . 'の口コミ';
	if ( $sc )   $t .= '（満足度' . $sc . '点）';
	if ( $shop ) $t .= '｜' . $shop;

	$parts['title'] = $t;
	return $parts;
}, 20 );

/* 検索結果の説明文（お客様のことばを先頭に置きます） */
add_action( 'wp_head', function () {
	if ( ! is_singular( 'ymkrf_voice' ) ) return;
	$id = get_the_ID();

	$c = trim( (string) get_post_meta( $id, '_ymkrf_comment', true ) );
	if ( $c === '' ) $c = trim( (string) get_post_meta( $id, '_ymkrf_after', true ) );
	if ( $c === '' ) $c = trim( (string) get_post_meta( $id, '_ymkrf_trouble', true ) );

	$head = array();
	$cust = ymkrf_voice_customer_label( $id );
	$p    = ymkrf_voice_meta_array( $id, '_ymkrf_parts' );
	if ( $cust ) $head[] = $cust;
	if ( $p )    $head[] = implode( '・', $p ) . 'のリフォーム';
	$sc = ymkrf_voice_score( $id );
	if ( $sc ) $head[] = '満足度' . $sc . '点';

	$desc = implode( '／', $head );
	if ( $c !== '' ) $desc .= '。「' . $c . '」';
	$desc .= '　工事後にいただいたアンケート「仕事の通信簿」の実物も掲載しています。';

	echo '<meta name="description" content="' . esc_attr( mb_strimwidth( $desc, 0, 240, '…', 'UTF-8' ) ) . '">' . "\n";

	/* お客様の声であることを、検索エンジンにも伝えます。
	   ※自社サイトに載せた自社へのレビューには、Googleは星マークを出しません。
	     それでも「1件ずつ違うページ」だと伝わるので入れています。 */
	$data = array(
		'@context' => 'https://schema.org',
		'@type'    => 'Review',
		'itemReviewed' => array(
			'@type' => 'LocalBusiness',
			'name'  => 'リフォームヤマキシ（株式会社山岸）' . ( ymkrf_voice_shop_name( $id ) ? '　' . ymkrf_voice_shop_name( $id ) : '' ),
		),
		'author'   => array( '@type' => 'Person', 'name' => ( $cust ? $cust : 'お客様' ) ),
		'datePublished' => get_the_date( 'Y-m-d', $id ),
	);
	if ( $sc ) $data['reviewRating'] = array(
		'@type' => 'Rating', 'ratingValue' => round( $sc / 20, 1 ), 'bestRating' => 5, 'worstRating' => 1,
	);
	if ( $c !== '' ) $data['reviewBody'] = $c;
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}, 5 );


/* --- (3) ページごとに違う関連リンク ----------------------------
   同じ工事箇所の声・同じ店舗の声を出します。
   ページごとに中身が変わるので、似たページになりにくくなります。
   ------------------------------------------------------------- */
function ymkrf_voice_related( $post_id, $num = 4 ) {
	$parts = ymkrf_voice_meta_array( $post_id, '_ymkrf_parts' );
	$shop  = (string) get_post_meta( $post_id, '_ymkrf_shop', true );

	$args = array(
		'post_type'      => 'ymkrf_voice',
		'posts_per_page' => $num,
		'post__not_in'   => array( $post_id ),
		'orderby'        => 'rand',
	);
	$out = array();

	if ( $parts ) {
		$a = $args;
		$a['meta_query'] = array( array( 'key' => '_ymkrf_parts', 'value' => $parts[0], 'compare' => 'LIKE' ) );
		$out = get_posts( $a );
	}
	if ( count( $out ) < $num && $shop !== '' ) {
		$a = $args;
		$a['posts_per_page'] = $num - count( $out );
		$a['post__not_in']   = array_merge( array( $post_id ), wp_list_pluck( $out, 'ID' ) );
		$a['meta_query']     = array( array( 'key' => '_ymkrf_shop', 'value' => $shop ) );
		$out = array_merge( $out, get_posts( $a ) );
	}
	if ( count( $out ) < $num ) {
		$a = $args;
		$a['posts_per_page'] = $num - count( $out );
		$a['post__not_in']   = array_merge( array( $post_id ), wp_list_pluck( $out, 'ID' ) );
		$out = array_merge( $out, get_posts( $a ) );
	}
	return $out;
}


/* ============================================================
   6. 手書きの文字起こし（Google Cloud Vision）

   アンケートの手書き部分を、自動で文字にします。
   チェック欄の読み取りとちがい、こちらは外部のサービスを使います。

   ■ 使うために必要なこと
     1. Google Cloud で「Cloud Vision API」を有効にする
     2. APIキーを作る
     3. 管理画面「お客様の声 → 文字起こしの設定」にそのキーを貼る

   ■ 費用の目安
     1枚あたり1回ぶんとして数えられます。1件のアンケートで3〜4枚使います。
     月1000枚までは無料枠、それ以降は1000枚あたり数百円程度です。
     （正確な金額はGoogleの料金表をご確認ください）

   ■ 安全のために
     APIキーはサーバーの中だけで使います。ブラウザには送りません。
     画像は文字起こしのためだけにGoogleへ送られ、こちらには保存されません。

   ■ 大事なこと
     手書きの読み取りは、かならず間違いが混ざります。
     切り抜き画像を横に出していますので、**保存前に必ず目で確かめてください。**
   ============================================================ */

define( 'YMKRF_VISION_OPT',     'ymkrf_vision_key' );   /* APIキー */
define( 'YMKRF_VISION_CAP_OPT', 'ymkrf_vision_cap' );   /* 1か月の上限（枚） */
define( 'YMKRF_VISION_USE_OPT', 'ymkrf_vision_use' );   /* 今月の枚数 */

/* ------------------------------------------------------------------
   お金がかからないようにする安全装置

   Google は「1か月に1000枚まで無料」です。
   ここでは、その手前（はじめは950枚）で自動的に止めます。
   上限に達したら、それ以上は Google に送りません。
   翌月になると、枚数は自動で0に戻ります。
   ------------------------------------------------------------------ */

/** 1か月の上限（枚）。0にすると自動の文字起こしを完全に止めます */
function ymkrf_vision_cap() {
	$v = get_option( YMKRF_VISION_CAP_OPT, '' );
	if ( $v === '' ) return 950;
	return max( 0, (int) $v );
}

/** いまの年月（サイトの時計） */
function ymkrf_vision_month() {
	return function_exists( 'wp_date' ) ? wp_date( 'Y-m' ) : date_i18n( 'Y-m' );
}

/** 今月すでに送った枚数 */
function ymkrf_vision_used() {
	$u = get_option( YMKRF_VISION_USE_OPT, array() );
	if ( ! is_array( $u ) || ! isset( $u['ym'] ) || $u['ym'] !== ymkrf_vision_month() ) return 0;
	return (int) $u['n'];
}

/** 今月ののこり枚数 */
function ymkrf_vision_left() {
	return max( 0, ymkrf_vision_cap() - ymkrf_vision_used() );
}

/** 送った枚数を足します */
function ymkrf_vision_add( $n ) {
	update_option( YMKRF_VISION_USE_OPT, array(
		'ym' => ymkrf_vision_month(),
		'n'  => ymkrf_vision_used() + max( 0, (int) $n ),
	), false );
}

/* --- 設定ページ --- */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=ymkrf_voice',
		'文字起こしの設定', '文字起こしの設定',
		'manage_options', 'ymkrf-voice-ocr', 'ymkrf_voice_ocr_page'
	);
} );

function ymkrf_voice_ocr_page() {
	if ( ! current_user_can( 'manage_options' ) ) return;

	if ( isset( $_POST['ymkrf_vision_nonce'] ) && wp_verify_nonce( $_POST['ymkrf_vision_nonce'], 'ymkrf_vision' ) ) {

		/* 上限（枚／月） */
		if ( isset( $_POST['ymkrf_vision_cap'] ) ) {
			update_option( YMKRF_VISION_CAP_OPT, max( 0, (int) $_POST['ymkrf_vision_cap'] ) );
		}

		/* 今月の枚数を0に戻す */
		if ( isset( $_POST['ymkrf_vision_reset'] ) ) {
			delete_option( YMKRF_VISION_USE_OPT );
			echo '<div class="notice notice-success"><p>今月の枚数を0に戻しました。</p></div>';
		}

		/* キーを消す */
		if ( isset( $_POST['ymkrf_vision_clear'] ) ) {
			delete_option( YMKRF_VISION_OPT );
			echo '<div class="notice notice-success"><p>キーを消しました。自動の文字起こしは止まっています。</p></div>';

		} elseif ( isset( $_POST['ymkrf_vision_key'] ) ) {
			$key = trim( sanitize_text_field( $_POST['ymkrf_vision_key'] ) );
			if ( $key === '' ) {
				/* 空のときは、いまのキーをそのまま残します */
				echo '<div class="notice notice-success"><p>保存しました。</p></div>';
			} elseif ( preg_match( '/^[A-Za-z0-9_\-]{20,}$/', $key ) ) {
				update_option( YMKRF_VISION_OPT, $key );
				echo '<div class="notice notice-success"><p>保存しました。</p></div>';
			} else {
				echo '<div class="notice notice-error"><p>キーの形が正しくないようです。もう一度ご確認ください。</p></div>';
			}
		}
	}
	$cur  = (string) get_option( YMKRF_VISION_OPT, '' );
	$mask = $cur === '' ? '' : substr( $cur, 0, 4 ) . str_repeat( '•', max( 8, strlen( $cur ) - 8 ) ) . substr( $cur, -4 );
	$cap  = ymkrf_vision_cap();
	$used = ymkrf_vision_used();
	$left = ymkrf_vision_left();
	?>
	<div class="wrap">
	  <h1>手書きの文字起こしの設定</h1>

	  <p>アンケートの手書き部分を、自動で文字にする機能です。<br>
	     設定しなくても、これまでどおり手で打ち込めます。<b>使いたいときだけ設定してください。</b></p>

	  <?php if ( $cur !== '' ) : ?>
	    <p><span class="dashicons dashicons-yes" style="color:#118a3d"></span>
	       <b>設定ずみです。</b>いまのキー：<code><?php echo esc_html( $mask ); ?></code></p>
	  <?php else : ?>
	    <p><span class="dashicons dashicons-info" style="color:#b26a00"></span>
	       まだ設定されていません。いまは手で打ち込む形になっています。</p>
	  <?php endif; ?>

	  <form method="post">
	    <?php wp_nonce_field( 'ymkrf_vision', 'ymkrf_vision_nonce' ); ?>
	    <table class="form-table">
	      <tr>
	        <th><label for="ymkrf_vision_key">APIキー</label></th>
	        <td>
	          <input type="password" id="ymkrf_vision_key" name="ymkrf_vision_key" value=""
	                 class="regular-text" autocomplete="off" placeholder="<?php echo $cur !== '' ? '変更するときだけ入力してください' : 'ここに貼りつけます'; ?>">
	          <p class="description">
	            空のまま保存すると、いまのキーはそのまま残ります。<br>
	            機能を止めたいときは、下の「キーを消す」を押してください。
	          </p>
	        </td>
	      </tr>
	      <tr>
	        <th><label for="ymkrf_vision_cap">1か月の上限（枚）</label></th>
	        <td>
	          <input type="number" id="ymkrf_vision_cap" name="ymkrf_vision_cap" min="0" step="10"
	                 value="<?php echo esc_attr( $cap ); ?>" class="small-text"> 枚 ／ 月
	          <p class="description">
	            <b>お金がかからないようにするための安全装置です。</b><br>
	            Googleは「1か月1000枚まで無料」なので、その手前の <b>950枚</b> をおすすめします。<br>
	            この枚数に達すると、それ以上は<b>自動でGoogleに送らなくなります</b>（手入力に切りかわります）。<br>
	            <b>0</b> にすると、自動の文字起こしを完全に止められます。<br>
	            枚数は毎月1日に自動で0に戻ります。
	          </p>
	        </td>
	      </tr>
	      <tr>
	        <th>今月の枚数</th>
	        <td>
	          <p style="font-size:16px;margin:0">
	            <b><?php echo (int) $used; ?></b> 枚 使用　／　のこり <b><?php echo (int) $left; ?></b> 枚
	            <?php if ( $left === 0 ) : ?>
	              <span style="color:#b26a00;font-weight:700">（上限に達しています。いまは止まっています）</span>
	            <?php endif; ?>
	          </p>
	          <p class="description">
	            アンケート1件につき最大3枚（お悩み・いかがでしたか・メッセージ）を送ります。<br>
	            すでに文字が入っている欄は送らないので、実際はもっと少なくなります。<br>
	            <?php echo (int) floor( $left / 3 ); ?>件ぶんの余裕があります。
	          </p>
	        </td>
	      </tr>
	    </table>
	    <?php submit_button( '保存する' ); ?>
	  </form>

	  <form method="post" style="display:inline-block;margin-right:10px"
	        onsubmit="return confirm('今月の枚数を0に戻します。Google側の実際の枚数は戻りませんのでご注意ください。よろしいですか？');">
	    <?php wp_nonce_field( 'ymkrf_vision', 'ymkrf_vision_nonce' ); ?>
	    <input type="hidden" name="ymkrf_vision_reset" value="1">
	    <?php submit_button( '今月の枚数を0に戻す', 'secondary', 'submit', false ); ?>
	  </form>

	  <?php if ( $cur !== '' ) : ?>
	  <form method="post" style="display:inline-block"
	        onsubmit="return confirm('キーを消すと、自動の文字起こしは止まります。よろしいですか？');">
	    <?php wp_nonce_field( 'ymkrf_vision', 'ymkrf_vision_nonce' ); ?>
	    <input type="hidden" name="ymkrf_vision_clear" value="1">
	    <?php submit_button( 'キーを消す', 'delete', 'submit', false ); ?>
	  </form>
	  <?php endif; ?>

	  <hr>
	  <h2>キーの取りかた</h2>
	  <ol style="max-width:760px;line-height:2">
	    <li>Google Cloud のコンソールで、プロジェクトを選ぶ（なければ作る）</li>
	    <li>「APIとサービス」→「ライブラリ」で <b>Cloud Vision API</b> を有効にする</li>
	    <li>「APIとサービス」→「認証情報」→「認証情報を作成」→「APIキー」</li>
	    <li>できたキーを、<b>Cloud Vision API だけに使えるよう制限</b>しておく（安全のため）</li>
	    <li>そのキーを上の欄に貼って保存</li>
	  </ol>

	  <h2>費用の目安</h2>
	  <p style="max-width:760px;line-height:2">
	    1枚の画像につき1回ぶんとして数えられます。アンケート1件で3〜4枚使います。<br>
	    月1000枚までは無料枠があり、それを超えると1000枚あたり数百円程度です。<br>
	    月100件のアンケートなら、400枚ほどなので<b>無料枠に収まります。</b><br>
	    正確な金額は Google の料金表をご確認ください。
	  </p>

	  <h2 style="color:#b26a00">注意していただきたいこと</h2>
	  <ul style="max-width:760px;line-height:2;list-style:disc;padding-left:1.4em">
	    <li><b>手書きの読み取りは、かならず間違いが混ざります。</b>
	        くずし字や薄い鉛筆は特に苦手です。</li>
	    <li>編集画面には、切り抜いた手書き画像を横に出しています。
	        <b>保存する前に、かならず目で見くらべてください。</b></li>
	    <li>アンケートの画像は、文字起こしのために Google へ送られます。
	        お名前が写っている場合はご注意ください。</li>
	    <li>APIキーはサーバーの中だけで使います。ブラウザには送りません。</li>
	  </ul>
	</div>
	<?php
}

/* --- 文字起こしの実行（サーバー側でGoogleに問い合わせます） --- */
add_action( 'wp_ajax_ymkrf_voice_ocr', function () {
	check_ajax_referer( 'ymkrf_voice_img', 'nonce' );
	if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( '権限がありません' );

	$key = (string) get_option( YMKRF_VISION_OPT, '' );
	if ( $key === '' ) wp_send_json_error( 'APIキーが設定されていません' );

	$imgs = isset( $_POST['images'] ) ? (array) $_POST['images'] : array();
	if ( ! $imgs ) wp_send_json_error( '画像がありません' );

	$keys = array(); $reqs = array();
	foreach ( $imgs as $k => $data ) {
		$k = sanitize_key( $k );
		$data = (string) $data;
		if ( strpos( $data, 'data:image/jpeg;base64,' ) !== 0 ) continue;
		$keys[] = $k;
		$reqs[] = array(
			'image'    => array( 'content' => substr( $data, strlen( 'data:image/jpeg;base64,' ) ) ),
			'features' => array( array( 'type' => 'DOCUMENT_TEXT_DETECTION' ) ),
			'imageContext' => array( 'languageHints' => array( 'ja' ) ),
		);
	}
	if ( ! $reqs ) wp_send_json_error( '画像を読み取れませんでした' );

	/* ★安全装置★ 今月の上限をこえるときは、Googleに送りません */
	$cap  = ymkrf_vision_cap();
	$left = ymkrf_vision_left();
	if ( $cap === 0 ) {
		wp_send_json_error( '自動の文字起こしは止めてあります（上限が0枚に設定されています）。手で入力してください。' );
	}
	if ( $left < count( $reqs ) ) {
		wp_send_json_error( sprintf(
			'今月の上限（%d枚）に達したので、自動で止めました。'
			. '使用 %d枚／のこり %d枚。'
			. 'お金がかからないようにするための安全装置です。手で入力してください。',
			$cap, ymkrf_vision_used(), $left
		) );
	}

	$res = wp_remote_post(
		'https://vision.googleapis.com/v1/images:annotate?key=' . rawurlencode( $key ),
		array(
			'timeout' => 45,
			'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'body'    => wp_json_encode( array( 'requests' => $reqs ) ),
		)
	);
	if ( is_wp_error( $res ) ) wp_send_json_error( '通信できませんでした：' . $res->get_error_message() );

	$code = wp_remote_retrieve_response_code( $res );
	$body = json_decode( wp_remote_retrieve_body( $res ), true );

	/* Googleが受け取った枚数を数えます（これが料金のもとになります） */
	if ( $code === 200 ) ymkrf_vision_add( count( $reqs ) );

	if ( $code !== 200 ) {
		$msg = isset( $body['error']['message'] ) ? $body['error']['message'] : ( 'エラー ' . $code );
		wp_send_json_error( 'Googleからの返事：' . $msg );
	}

	$out = array();
	foreach ( (array) ( isset( $body['responses'] ) ? $body['responses'] : array() ) as $i => $r ) {
		$k = isset( $keys[ $i ] ) ? $keys[ $i ] : (string) $i;
		$t = isset( $r['fullTextAnnotation']['text'] ) ? $r['fullTextAnnotation']['text'] : '';
		$out[ $k ] = ymkrf_voice_tidy_ocr( $t, $k );
	}
	wp_send_json_success( $out );
} );

/**
 * 読み取った文字を整えます。
 * 用紙の印刷文字がまざったり、改行がこまかく入ったりするので取り除きます。
 */
/**
 * 「用紙に印刷されている文」を見つけるための、ゆるい検索のしかたを作ります。
 * 文字認識は、字と字のあいだに改行や空白を入れてくることがあります。
 * また「？」と「?」のような全角・半角のちがいも出ます。
 * どちらでも消えるようにしています。
 */
function ymkrf_voice_drop_regex( $phrase ) {
	$pairs = array(
		'？' => '[？?]', '?' => '[？?]',
		'（' => '[（(]', '(' => '[（(]',
		'）' => '[）)]', ')' => '[）)]',
		'、' => '[、,]',  '。' => '[。.]',
	);
	$out = array();
	foreach ( preg_split( '//u', $phrase, -1, PREG_SPLIT_NO_EMPTY ) as $c ) {
		if ( $c === ' ' || $c === '　' ) continue;
		$out[] = isset( $pairs[ $c ] ) ? $pairs[ $c ] : preg_quote( $c, '/' );
	}
	return '/' . implode( '[\s]*', $out ) . '[\s]*[。.？?！!]?/u';
}

function ymkrf_voice_tidy_ocr( $text, $kind = '' ) {
	$t = (string) $text;
	if ( $t === '' ) return '';

	/* -----------------------------------------------------------------
	   用紙にもともと印刷されている文を取り除きます。

	   お客様が書かれた文章ではないので、ページに出してはいけません。
	   アンケート用紙を作り直したときは、この一覧に文を足してください。
	   （文字認識は改行や空白がまざるので、多少ずれても消えるようにしています）
	   ----------------------------------------------------------------- */
	$drop = array(
		/* 見出し */
		'今回リフォームする前は、どのような事でお悩み（お困り）でしたか',
		'今回リフォームしていかがでしたでしょうか',
		'今回の工事について感じたことや、スタッフへのメッセージなどをお願いします',
		'満足度は何点ですか',
		'以下のアンケートにご協力お願いいたします',
		'今回弊社で工事した箇所にチェックを入れて下さい',
		'弊社を選んで頂いた理由を教えて下さい',
		'仕事の通信簿',

		/* とんとこトンのふきだし */
		'今後の参考にさせて頂くトン',
		'今後の参考にさせて頂きます',

		/* 用紙の下のあいさつ文 */
		'最後までご記入いただき、ありがとうございました',
		'最後までご記入いただきありがとうございました',
		'お客様からいただきましたアンケートにつきましては',
		'今後のサービス向上のための貴重な参考資料として',
		'大切に活用させていただきます',

		/* 用紙の上のあいさつ文 */
		'この度は、リフォームヤマキシに工事をご用命頂き誠にありがとうございました',
		'今後のサービス向上のため、弊社の工事内容について率直な評価をお聞かせください',
		'感じたままのご意見をご記入後、同封の返信用封筒にてご返送下さい',
		'お手数をおかけしますが、よろしくお願い申し上げます',
		'副社長山岸直貴',
		'副社長 山岸直貴',
	);
	foreach ( $drop as $d ) {
		$t = preg_replace( ymkrf_voice_drop_regex( $d ), '', $t );
	}

	if ( $kind === 'score' ) {
		/* 「80 / 100」のような書き方から、点数だけ取り出します */
		if ( preg_match_all( '/\d{1,3}/u', $t, $m ) ) {
			foreach ( $m[0] as $n ) {
				$n = (int) $n;
				if ( $n !== 100 && $n >= 0 && $n <= 100 ) return (string) $n;
			}
			return '';
		}
		return '';
	}

	/* 行の途中で切れた改行をつなげます（罫線ごとに改行が入るため） */
	$lines = preg_split( '/\R/u', $t );
	$lines = array_values( array_filter( array_map( 'trim', $lines ), function ( $s ) { return $s !== ''; } ) );
	$t = implode( '', $lines );

	$t = preg_replace( '/[ \t\x{3000}]+/u', '', $t );
	$t = trim( $t );

	/* 記号や短い切れはしだけが残ったときは、無かったことにします */
	if ( preg_replace( '/[\p{P}\p{S}\s]/u', '', $t ) === '' ) return '';

	return $t;
}
