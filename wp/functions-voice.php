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
			'_ymkrf_customer', '_ymkrf_area', '_ymkrf_show_survey', '_ymkrf_read_info',
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
	        <b style="color:#b26a00">自動で入った文字は、かならず切り抜き画像と見くらべてください。</b>
	      <?php else : ?>
	        手書きの部分は、下に出る切り抜きを見ながら打ち込んでください。
	        （<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ymkrf_voice&page=ymkrf-voice-ocr' ) ); ?>">自動で文字起こしする設定</a>もあります）
	      <?php endif; ?>
	    </span>
	  </p>

	  <p>
	    <button type="button" class="button button-primary" id="ymkrf-pick">アンケート画像を選ぶ</button>
	    <button type="button" class="button" id="ymkrf-reread" <?php disabled( ! $sid ); ?>>もう一度読み取る</button>
	    <span id="ymkrf-status" class="ymkrf-voice__status"></span>
	  </p>

	  <input type="hidden" name="_ymkrf_survey_id"     id="ymkrf-survey-id"  value="<?php echo esc_attr( $sid ); ?>">
	  <input type="hidden" name="_ymkrf_survey_pub_id" id="ymkrf-pub-id"     value="<?php echo esc_attr( $pid ); ?>">
	  <input type="hidden" name="_ymkrf_read_info"     id="ymkrf-read-info"  value="<?php echo esc_attr( $get( '_ymkrf_read_info' ) ); ?>">

	  <div id="ymkrf-preview" class="ymkrf-voice__preview">
	    <?php if ( $sid ) : $u = wp_get_attachment_image_url( $sid, 'large' ); ?>
	      <img src="<?php echo esc_url( $u ); ?>" alt="">
	    <?php endif; ?>
	  </div>

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
	        <select name="_ymkrf_shop">
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
	    <tr>
	      <th>アンケート画像の掲載</th>
	      <td>
	        <label><input type="checkbox" name="_ymkrf_show_survey" value="1"
	          <?php checked( (string) $get( '_ymkrf_show_survey', '1' ), '1' ); ?>>
	          ページにアンケート画像を出す（クリックで拡大）</label>
	        <p class="description">「ご紹介（　様）」の欄は自動で白く塗りつぶした画像を使います。</p>
	        <?php if ( $pid ) : ?>
	          <p><a href="<?php echo esc_url( wp_get_attachment_url( $pid ) ); ?>" target="_blank" rel="noopener">公開用の画像を確認する</a></p>
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
	  .ymkrf-voice__preview img{max-width:420px;height:auto;border:1px solid #dcdcde;margin:6px 0 14px}
	  .ymkrf-voice__checks label{display:inline-block;min-width:190px;margin:0 10px 8px 0}
	  .ymkrf-voice__radios label{display:inline-block;margin:0 16px 6px 0}
	  .ymkrf-voice__crop{margin-top:8px}
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
	update_post_meta( $post_id, '_ymkrf_show_survey', isset( $_POST['_ymkrf_show_survey'] ) ? '1' : '0' );

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
	if ( get_post_meta( $post_id, '_ymkrf_show_survey', true ) !== '1' ) return '';
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
      題名だけでは見分けがつかないので、案件番号・施工店舗・
      お客様・満足度も出します。
   ============================================================ */
add_filter( 'manage_ymkrf_voice_posts_columns', function ( $cols ) {
	$new = array();
	foreach ( $cols as $k => $v ) {
		$new[ $k ] = $v;
		if ( $k === 'title' ) {
			$new['ymkrf_case']  = '案件番号';
			$new['ymkrf_shop']  = '施工店舗';
			$new['ymkrf_cust']  = 'お客様';
			$new['ymkrf_score'] = '満足度';
			$new['ymkrf_parts'] = '工事箇所';
		}
	}
	return $new;
} );

add_action( 'manage_ymkrf_voice_posts_custom_column', function ( $col, $post_id ) {
	switch ( $col ) {
		case 'ymkrf_case':
			$v = get_post_meta( $post_id, '_ymkrf_case_no', true );
			echo $v ? esc_html( $v ) : '<span style="color:#a7aaad">—</span>';
			break;
		case 'ymkrf_shop':
			$v = ymkrf_voice_shop_name( $post_id );
			echo $v ? esc_html( $v ) : '<span style="color:#a7aaad">—</span>';
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
			$s = ymkrf_voice_score( $post_id );
			echo $s ? esc_html( $s ) . '点' : '<span style="color:#a7aaad">—</span>';
			break;
		case 'ymkrf_parts':
			$p = ymkrf_voice_meta_array( $post_id, '_ymkrf_parts' );
			echo $p ? esc_html( implode( '／', $p ) ) : '<span style="color:#a7aaad">—</span>';
			break;
	}
}, 10, 2 );

/* 案件番号・満足度は見出しをクリックで並べ替えできます */
add_filter( 'manage_edit-ymkrf_voice_sortable_columns', function ( $cols ) {
	$cols['ymkrf_case']  = 'ymkrf_case';
	$cols['ymkrf_score'] = 'ymkrf_score';
	return $cols;
} );
add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() || ! $q->is_main_query() ) return;
	if ( $q->get( 'post_type' ) !== 'ymkrf_voice' ) return;
	$by = $q->get( 'orderby' );
	if ( $by === 'ymkrf_case' ) {
		$q->set( 'meta_key', '_ymkrf_case_no' );
		$q->set( 'orderby', 'meta_value' );
	} elseif ( $by === 'ymkrf_score' ) {
		$q->set( 'meta_key', '_ymkrf_score' );
		$q->set( 'orderby', 'meta_value_num' );
	}
} );

/* 列の幅 */
add_action( 'admin_head', function () {
	$s = get_current_screen();
	if ( ! $s || $s->id !== 'edit-ymkrf_voice' ) return;
	echo '<style>
	  .column-ymkrf_case{width:110px}
	  .column-ymkrf_shop{width:110px}
	  .column-ymkrf_cust{width:170px}
	  .column-ymkrf_score{width:80px}
	  .column-ymkrf_parts{width:180px}
	</style>';
} );


/* ============================================================
   5. 検索エンジン対策
      お客様の声は、どうしてもページの形が似ます。
      アンケート用紙が同じで、見出しも同じだからです。
      そのままだと Google に「同じページ」と見なされ、
      サーチコンソールに「重複したコンテンツ」の指摘が出ます。

      対策は4つです。
        (1) URLを内容の分かる英字にして、重複させない
        (2) 題名・説明文をページごとに変える
        (3) 中身がうすいページは、あえて検索結果に出さない
        (4) ページごとに違う関連リンクを持たせる
   ============================================================ */

/* --- (1) URL --------------------------------------------------
   「修理・小工事のリフォーム」のような題名は何件も出てくるので、
   URLは「市町 + 工事箇所 + 案件番号」の英字にします。
     例）/voice/kanazawa-oiltank-26070389/
   案件番号が入っているので、絶対に重複しません。
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

/** この投稿にふさわしいURL（英字） */
function ymkrf_voice_make_slug( $post_id ) {
	$bits = array();

	$city = trim( (string) get_post_meta( $post_id, '_ymkrf_city', true ) );
	$cmap = ymkrf_voice_city_roman();
	if ( $city !== '' && isset( $cmap[ $city ] ) ) $bits[] = $cmap[ $city ];

	$parts = ymkrf_voice_meta_array( $post_id, '_ymkrf_parts' );
	$pmap  = ymkrf_voice_part_roman();
	foreach ( array_slice( $parts, 0, 2 ) as $p ) {
		if ( isset( $pmap[ $p ] ) ) $bits[] = $pmap[ $p ];
	}

	$no = strtolower( preg_replace( '/[^0-9A-Za-z-]/', '', (string) get_post_meta( $post_id, '_ymkrf_case_no', true ) ) );
	if ( $no !== '' ) $bits[] = $no;

	if ( ! $bits ) return '';
	if ( count( $bits ) === 1 && $no === '' ) return '';   // 番号も箇所も無いときは触らない
	return implode( '-', $bits );
}

/**
 * 保存のたびにURLを整えます。
 * すでに英字で手入力されたURLは、そのままにします
 * （日本語のURLか、まだ番号が入っていないURLだけ作り直します）。
 */
add_action( 'save_post_ymkrf_voice', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;

	$want = ymkrf_voice_make_slug( $post_id );
	if ( $want === '' ) return;

	$p = get_post( $post_id );
	if ( ! $p ) return;
	$now = rawurldecode( (string) $p->post_name );

	/* 日本語が入っている／案件番号が入っていない ときだけ作り直します */
	$no = strtolower( preg_replace( '/[^0-9A-Za-z-]/', '', (string) get_post_meta( $post_id, '_ymkrf_case_no', true ) ) );
	$has_jp  = (bool) preg_match( '/[^\x20-\x7E]/', $now );
	$has_no  = ( $no !== '' && strpos( $now, $no ) !== false );
	if ( ! $has_jp && $has_no ) return;

	remove_action( 'save_post_ymkrf_voice', __FUNCTION__ );
	wp_update_post( array(
		'ID'        => $post_id,
		'post_name' => wp_unique_post_slug( $want, $post_id, $p->post_status, $p->post_type, 0 ),
	) );
}, 30 );


/* --- (2) 題名・説明文 ------------------------------------------
   ページごとに違う文にします。
   ------------------------------------------------------------- */

/* ブラウザのタブと検索結果に出る題名 */
add_filter( 'document_title_parts', function ( $parts ) {
	if ( ! is_singular( 'ymkrf_voice' ) ) return $parts;
	$id = get_the_ID();

	$p    = ymkrf_voice_meta_array( $id, '_ymkrf_parts' );
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


/* --- (3) 中身がうすいページ ------------------------------------
   手書きの文章がまったく無いアンケート（チェックだけ）は、
   ページとしての中身がほとんど同じになります。
   そういうページは、検索結果に出さない設定にします。
   一覧には出ますし、お客様も見られます。消すわけではありません。
   ------------------------------------------------------------- */
function ymkrf_voice_is_thin( $post_id ) {
	$len = 0;
	foreach ( array( '_ymkrf_comment', '_ymkrf_after', '_ymkrf_trouble' ) as $k ) {
		$len += mb_strlen( trim( (string) get_post_meta( $post_id, $k, true ) ), 'UTF-8' );
	}
	return $len < 25;   // 手書きの文章が25文字に満たないもの
}

add_action( 'wp_head', function () {
	if ( ! is_singular( 'ymkrf_voice' ) ) return;
	if ( ymkrf_voice_is_thin( get_the_ID() ) ) {
		echo '<meta name="robots" content="noindex,follow">' . "\n";
	}
}, 4 );

/* 管理画面で「検索結果に出るかどうか」が分かるようにします */
add_filter( 'manage_ymkrf_voice_posts_columns', function ( $cols ) {
	$cols['ymkrf_seo'] = '検索';
	return $cols;
}, 20 );
add_action( 'manage_ymkrf_voice_posts_custom_column', function ( $col, $post_id ) {
	if ( $col !== 'ymkrf_seo' ) return;
	if ( ymkrf_voice_is_thin( $post_id ) ) {
		echo '<span style="color:#b26a00" title="手書きの文章が少ないため、検索結果には出しません。'
		   . '感想を追記すると出るようになります。">出さない</span>';
	} else {
		echo '<span style="color:#118a3d">出す</span>';
	}
}, 10, 2 );


/* --- (4) ページごとに違う関連リンク ----------------------------
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

define( 'YMKRF_VISION_OPT', 'ymkrf_vision_key' );

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
		$key = isset( $_POST['ymkrf_vision_key'] ) ? trim( sanitize_text_field( $_POST['ymkrf_vision_key'] ) ) : '';
		if ( $key === '' || preg_match( '/^[A-Za-z0-9_\-]{20,}$/', $key ) ) {
			update_option( YMKRF_VISION_OPT, $key );
			echo '<div class="notice notice-success"><p>保存しました。</p></div>';
		} else {
			echo '<div class="notice notice-error"><p>キーの形が正しくないようです。もう一度ご確認ください。</p></div>';
		}
	}
	$cur  = (string) get_option( YMKRF_VISION_OPT, '' );
	$mask = $cur === '' ? '' : substr( $cur, 0, 4 ) . str_repeat( '•', max( 8, strlen( $cur ) - 8 ) ) . substr( $cur, -4 );
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
	    </table>
	    <?php submit_button( '保存する' ); ?>
	  </form>

	  <?php if ( $cur !== '' ) : ?>
	  <form method="post" onsubmit="return confirm('キーを消すと、自動の文字起こしは止まります。よろしいですか？');">
	    <?php wp_nonce_field( 'ymkrf_vision', 'ymkrf_vision_nonce' ); ?>
	    <input type="hidden" name="ymkrf_vision_key" value="">
	    <?php submit_button( 'キーを消す', 'delete', 'submit', true ); ?>
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
function ymkrf_voice_tidy_ocr( $text, $kind = '' ) {
	$t = (string) $text;
	if ( $t === '' ) return '';

	/* 用紙にもともと印刷されている見出しを取り除きます */
	$drop = array(
		'今回リフォームする前は、どのような事でお悩み（お困り）でしたか？',
		'今回リフォームする前は、どのような事でお悩み(お困り)でしたか?',
		'今回リフォームしていかがでしたでしょうか？',
		'今回リフォームしていかがでしたでしょうか?',
		'今回の工事について感じたことや、スタッフへのメッセージなどをお願いします。',
		'満足度は何点ですか？', '満足度は何点ですか?',
		'最後までご記入いただき、ありがとうございました。',
	);
	foreach ( $drop as $d ) $t = str_replace( $d, '', $t );

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
	return trim( $t );
}
