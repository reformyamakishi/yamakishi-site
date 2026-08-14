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
		if ( $ini !== '' ) $s .= ( $s !== '' ? '／' : '' ) . $ini . '様';
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
	    <span class="description">読み取りはこのパソコンの中だけで行います。画像が外部に送られることはありません。</span>
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
	        <p class="description">空のままにすると、上の5段階評価から自動で見当をつけます。</p>
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
	foreach ( array( '_ymkrf_case_no', '_ymkrf_customer', '_ymkrf_read_info' ) as $k ) {
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

/* 満足度。記入がなければ5段階評価から見当をつけます。 */
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
 * 手書きの感想がないときでも、ページに出す文章がある状態にするためのものです。
 */
function ymkrf_voice_summary( $post_id ) {
	$parts   = ymkrf_voice_meta_array( $post_id, '_ymkrf_parts' );
	$reasons = ymkrf_voice_meta_array( $post_id, '_ymkrf_reasons' );
	$labels  = ymkrf_voice_rating_labels();
	$out = array();

	if ( $parts )   $out[] = implode( '・', $parts ) . 'の工事をご用命いただきました。';
	if ( $reasons ) $out[] = 'ヤマキシをお選びいただいた理由は「' . implode( '」「', $reasons ) . '」。';

	/* 「大変良かった」「満足」をいただいた項目をまとめます */
	$good = array();
	foreach ( ymkrf_voice_rating_fields() as $k => $label ) {
		if ( (int) get_post_meta( $post_id, $k, true ) >= 3 ) $good[] = trim( $label, '「」' );
	}
	if ( $good ) $out[] = implode( '・', $good ) . 'について、良い評価をいただいています。';

	$rec = (int) get_post_meta( $post_id, '_ymkrf_recommend', true );
	if ( $rec >= 3 ) {
		$rl = ymkrf_voice_recommend_labels();
		$out[] = 'お知り合いへのご紹介についても「' . $rl[ $rec ] . '」とお答えいただきました。';
	}
	$score = ymkrf_voice_score( $post_id );
	if ( $score ) $out[] = '満足度は100点満点中' . $score . '点です。';

	return implode( '', $out );
}

/* 一覧・詳細で使う、アンケート画像のタグ。
   クリックで拡大しますが、<a href> で包んであるので
   検索エンジンからもふつうの画像リンクとして読まれます。 */
function ymkrf_voice_survey_figure( $post_id ) {
	if ( get_post_meta( $post_id, '_ymkrf_show_survey', true ) !== '1' ) return '';
	$pid = (int) get_post_meta( $post_id, '_ymkrf_survey_pub_id', true );
	if ( ! $pid ) return '';
	$full  = wp_get_attachment_url( $pid );
	$thumb = wp_get_attachment_image_url( $pid, 'large' );
	if ( ! $full ) return '';
	$alt = 'お客様アンケート「仕事の通信簿」の実物';
	$h  = '<figure class="p-voice__sheet">';
	$h .= '<a class="js-lightbox" href="' . esc_url( $full ) . '" data-caption="' . esc_attr( $alt ) . '">';
	$h .= '<img src="' . esc_url( $thumb ? $thumb : $full ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy" decoding="async">';
	$h .= '<span class="p-voice__zoom">クリックで拡大</span>';
	$h .= '</a>';
	$h .= '<figcaption>実際にお客様からいただいたアンケートです。お名前の欄は塗りつぶしています。</figcaption>';
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
