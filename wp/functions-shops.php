<?php
/**
 * functions-shops.php ─ 店舗の情報（1か所にまとめています）
 *
 * 置き場所： wp-content/themes/ymkrf/inc/functions-shops.php
 *
 * ★店舗の情報を直すときは、このファイルだけを直してください。
 *   店舗・対応エリアのページ（/shops/）と、
 *   お見積り・お問い合わせのページ（/inquiry/）の両方に反映されます。
 *
 *   slug     … スタッフ紹介のしぼり込みに使う英字
 *   hours    … 営業時間／hnote … 営業時間の但し書き
 *   areas    … 主に担当する市町
 *   sr       … ショールームがあるか
 *   srnote   … ショールームについての但し書き
 *   ld       … 検索エンジン用の営業時間（Mo=月 Tu=火 We=水 Th=木 Fr=金 Sa=土 Su=日）
 *   soon     … まだ開いていないお店
 *   open     … オープンの予定（外観写真の上に「OPEN」の吹き出しで出ます）
 *              ※ soon とちがい、お店の内容（電話・営業時間・リンク）はふつうに出ます
 *   topic    … そのお店の新しいお知らせ（外観写真の上に吹き出しで出ます）
 *   pos      … 外観写真の見せたい位置（例 '50% 22%'。空なら中央）
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'ymkrf_shops' ) ) :
function ymkrf_shops() {
	$list = ymkrf_shops_base();

	/* 管理画面（スタッフ ＞ 店舗の内容）で直したところを、上からかぶせます */
	$ov = function_exists( 'ymkrf_shops_overrides' ) ? ymkrf_shops_overrides() : array();
	if ( $ov ) {
		foreach ( $list as $i => $s ) {
			$slug = isset( $s['slug'] ) ? $s['slug'] : '';
			if ( $slug === '' || empty( $ov[ $slug ] ) ) continue;
			foreach ( (array) $ov[ $slug ] as $k => $v ) {
				if ( $v !== '' ) $list[ $i ][ $k ] = $v;
			}
		}
	}
	return $list;
}
endif;

/** もとの内容（このファイルに書いてある内容） */
function ymkrf_shops_base() {
	return array(

	/* ---------------- 石川県 ---------------- */
	array(
		'pref' => '石川県', 'slug' => 'higashikanazawa', 'name' => '東金沢店',
		'tel'  => '',
		'addr' => '石川県金沢市大樋町1番地4',
		'hours' => '10:00〜18:00', 'hnote' => '',
		'closed' => '',
		'areas' => array( '金沢市' ),
		'sr' => true, 'srnote' => '',
		'feature' => '2026年に金沢市大樋町にオープンする、いちばん新しいお店です。ショールームもあります。金沢市の東部をぐるりと担当します。',
		/* ★2026/10/31 のオープンがすんだら、この 'open' の行を消してください */
		'open' => '2026年10月31日（土）オープン！',
		'ld' => array( 'Mo-Su 10:00-18:00' ),
		/* 2026/09/04 ユーザー指示：サイト公開のころには開店しているので
		   「準備中」の扱いをやめました（'soon' を外しました）。
		   ★直通の電話番号が決まりましたら 'tel' に入れてください。
		     空のあいだは、フリーコール 0800-777-3331 をご案内します。 */
		'pos'  => '50% 22%',   /* 写真の見せたい位置（上のほうを見せます） */
	),
	array(
		'pref' => '石川県', 'slug' => 'hakui', 'name' => '羽咋店',
		'tel'  => '0767-23-4747',
		'addr' => '石川県羽咋市鶴多町五石高34-1',
		'hours' => '10:00〜18:00', 'hnote' => '毎週(金)は17:00閉店',
		'closed' => '年中無休 ※盆・年末年始を除く',
		'areas' => array( '羽咋市', '志賀町', '中能登町', '宝達志水町' ),
		'sr' => true, 'srnote' => '',
		'feature' => '羽咋市のショールーム。羽咋市・志賀町・中能登町・宝達志水町と、能登のひろい範囲をお伺いします。',
		'ld' => array( 'Mo-Th 10:00-18:00', 'Fr 10:00-17:00', 'Sa-Su 10:00-18:00' ),
	),
	array(
		'pref' => '石川県', 'slug' => 'nonoichi', 'name' => '金沢野々市店',
		'tel'  => '076-294-6101',
		'addr' => '石川県野々市市本町6丁目12-70',
		'hours' => '10:00〜18:00', 'hnote' => '毎週(水)は17:00閉店',
		'closed' => '年中無休 ※盆・年末年始を除く',
		'areas' => array( '金沢市', '野々市市', '白山市' ),
		'sr' => true, 'srnote' => '',
		'feature' => '金沢市・野々市市・白山市を担当する、まちなかのショールームです。',
		'ld' => array( 'Mo-Tu 10:00-18:00', 'We 10:00-17:00', 'Th-Su 10:00-18:00' ),
	),
	array(
		'pref' => '石川県', 'slug' => 'komathu', 'name' => '小松店',
		'tel'  => '0761-23-3636',
		'addr' => '石川県小松市長田町イ4-1',
		'hours' => '10:00〜18:00', 'hnote' => '毎週(木)は17:00閉店',
		'closed' => '年中無休 ※盆・正月を除く',
		'areas' => array( '小松市', '能美市' ),
		'sr' => true, 'srnote' => '',
		'feature' => '小松市・能美市を担当するショールーム。加賀地方のまんなかにあります。',
		'ld' => array( 'Mo-We 10:00-18:00', 'Th 10:00-17:00', 'Fr-Su 10:00-18:00' ),
	),
	array(
		'pref' => '石川県', 'slug' => 'tagami', 'name' => '金沢田上店',
		'tel'  => '076-213-6331',
		'addr' => '石川県金沢市田上さくら2丁目14',
		'hours' => '10:00〜17:00', 'hnote' => '',
		'closed' => '年中無休 ※盆・年末年始を除く',
		'areas' => array( '金沢市' ),
		'sr' => true, 'srnote' => 'エコキュートと外壁塗装の専門店です。',
		'feature' => 'エコキュートと外壁塗装の専門店です。金沢市の南部を担当します。',
		'ld' => array( 'Mo-Su 10:00-17:00' ),
	),
	array(
		'pref' => '石川県', 'slug' => 'tazuruhama', 'name' => '田鶴浜店',
		'tel'  => '0767-68-6600',
		'addr' => '石川県七尾市高田町ほ部34番地',
		'hours' => '8:00〜21:00', 'hnote' => '',
		'closed' => '年中無休',
		'areas' => array( '七尾市', '志賀町（一部）' ),
		'sr' => true,
		'srnote' => '',
		'feature' => '七尾市のショールーム。朝8時から夜9時まで、年中無休であいています。2026年10月にリフォームコーナーが復興オープンします。',
		'ld' => array( 'Mo-Su 08:00-21:00' ),
		'topic' => '2026年10月 リフォームコーナーが復興オープン！',
	),
	array(
		'pref' => '石川県', 'slug' => 'kawakita', 'name' => '川北店',
		'tel'  => '076-277-7550',
		'addr' => '石川県能美郡川北町三反田中216-1',
		'hours' => '8:00〜22:00', 'hnote' => '',
		'closed' => '年中無休',
		'areas' => array( '川北町', '白山市', '能美市（一部）' ),
		'sr' => true, 'srnote' => '',
		'feature' => '朝8時から夜10時まで、年中無休。ヤマキシでいちばん遅くまであいているお店です。',
		'ld' => array( 'Mo-Su 08:00-22:00' ),
	),
	array(
		'pref' => '石川県', 'slug' => 'shinkaga', 'name' => '新加賀店',
		'tel'  => '0761-74-0017',
		'addr' => '石川県加賀市桑原町ホ48-1',
		'hours' => '8:00〜21:00', 'hnote' => '',
		'closed' => '年中無休',
		'areas' => array( '加賀市' ),
		'sr' => true, 'srnote' => '',
		'feature' => '加賀市のショールーム。朝8時から夜9時まで、年中無休であいています。2026年10月にリフォームコーナーがリニューアルします。',
		'ld' => array( 'Mo-Su 08:00-21:00' ),
		'topic' => '2026年10月 リフォームコーナーがリニューアル！',
	),
	/* ---------------- 福井県 ---------------- */
	array(
		'pref' => '福井県', 'slug' => 'kanadu', 'name' => '金津店',
		'tel'  => '0776-73-1015',
		'addr' => '福井県あわら市大溝1丁目8-13',
		'hours' => '8:00〜19:00', 'hnote' => '',
		'closed' => '年中無休 ※元旦を除く',
		'areas' => array( 'あわら市', '坂井市' ),
		'sr' => true, 'srnote' => '株式会社山岸の本社があるお店です。',
		'feature' => '株式会社山岸の本社があるお店です。あわら市・坂井市を担当します。',
		'ld' => array( 'Mo-Su 08:00-19:00' ),
	),
	array(
		'pref' => '福井県', 'slug' => 'kahahothu', 'name' => '開発店',
		'tel'  => '0776-54-9939',
		'addr' => '福井県福井市開発町9字5-1',
		'hours' => '9:00〜19:00', 'hnote' => '',
		'closed' => '年中無休 ※元旦を除く',
		'areas' => array( '福井市', '永平寺町' ),
		'sr' => false, 'srnote' => '',
		'feature' => '福井市・永平寺町を担当します。朝9時から夜7時まで、年中無休であいています。',
		'ld' => array( 'Mo-Su 09:00-19:00' ),
	),
	array(
		'pref' => '福井県', 'slug' => 'asahi', 'name' => '朝日店',
		'tel'  => '0778-34-8885',
		'addr' => '福井県丹生郡越前町乙坂39字1番',
		'hours' => '7:00〜21:30', 'hnote' => 'ホームセンター資材館（灯油販売を含む）は21:00まで',
		'closed' => '年中無休',
		'areas' => array( '越前町', '越前市', '鯖江市' ),
		'sr' => true, 'srnote' => '',
		'feature' => '朝7時から夜9時半まで。越前町・越前市・鯖江市を担当する、福井県で南のお店です。',
		'ld' => array( 'Mo-Su 07:00-21:30' ),
	),
	);
}

/**
 * 市や町から、担当するお店を引く表
 *
 * ★ここを直すと、店舗・対応エリアのページ（/shops/）と
 *   イベント・チラシのページ（/flyer/）の両方に反映されます。
 *
 * 1つの市町に何店かあるときは、近い順・案内したい順にならべてください。
 */
if ( ! function_exists( 'ymkrf_shop_cities' ) ) :
function ymkrf_shop_cities() {
	return array(
		'石川県' => array(
			'金沢市'       => array( 'nonoichi', 'tagami', 'higashikanazawa' ),
			'野々市市'     => array( 'nonoichi' ),
			'白山市'       => array( 'nonoichi', 'kawakita' ),
			'川北町'       => array( 'kawakita' ),
			'能美市'       => array( 'komathu', 'kawakita' ),
			'小松市'       => array( 'komathu' ),
			'加賀市'       => array( 'shinkaga' ),
			'七尾市'       => array( 'tazuruhama' ),
			'羽咋市'       => array( 'hakui' ),
			'志賀町'       => array( 'hakui', 'tazuruhama' ),
			'中能登町'     => array( 'hakui' ),
			'宝達志水町'   => array( 'hakui' ),
		),
		'福井県' => array(
			'あわら市'     => array( 'kanadu' ),
			'坂井市'       => array( 'kanadu' ),
			'福井市'       => array( 'kahahothu' ),
			'永平寺町'     => array( 'kahahothu' ),
			'越前町'       => array( 'asahi' ),
			'越前市'       => array( 'asahi' ),
			'鯖江市'       => array( 'asahi' ),
		),
	);
}
endif;

/* ============================================================
   店舗の内容を、WordPress から直せるようにします
   （2026/09/10 ユーザー指示）

   ・もとの内容は、このファイルの ymkrf_shops() に書いてあります。
   ・管理画面で直したところだけが、上書きされます。
   ・「もとにもどす」を押すと、ファイルの内容にもどります。

   直せるのは：特徴／電話／営業時間／定休日／お知らせ（ふきだし）
   ============================================================ */

define( 'YMKRF_SHOPS_OPT', 'ymkrf_shops_edit' );

/** 管理画面で直した内容（店舗の英字 => 項目の配列） */
function ymkrf_shops_overrides() {
	$v = get_option( YMKRF_SHOPS_OPT, array() );
	return is_array( $v ) ? $v : array();
}

/** 直せる項目と、その見出し */
function ymkrf_shops_fields() {
	return array(
		'feature' => array( 'ttl' => '特徴',   'type' => 'textarea',
		                    'note' => 'この店ならではのこと。コラムの執筆者に店舗をえらんだときと、店舗のページに出ます。' ),
		'tel'     => array( 'ttl' => '電話番号', 'type' => 'text', 'note' => '空にすると、フリーコールをご案内します。' ),
		'hours'   => array( 'ttl' => '営業時間', 'type' => 'text', 'note' => '例：8:00〜21:00' ),
		'closed'  => array( 'ttl' => '定休日',   'type' => 'text', 'note' => '例：年中無休' ),
		'topic'   => array( 'ttl' => 'お知らせ（ふきだし）', 'type' => 'text',
		                    'note' => '店舗のページで、写真の下にふきだしで出ます。' ),
	);
}

add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=ymkrf_staff',
		'店舗の内容', '店舗の内容',
		'manage_options', 'ymkrf-shops-edit', 'ymkrf_shops_edit_page'
	);
}, 30 );

function ymkrf_shops_edit_page() {

	if ( ! current_user_can( 'manage_options' ) ) return;

	$shops  = ymkrf_shops();
	$fields = ymkrf_shops_fields();
	$saved  = false;

	if ( isset( $_POST['ymkrf_shops_save'] ) && check_admin_referer( 'ymkrf_shops_edit' ) ) {

		$now = array();
		$in  = isset( $_POST['ymkrf_shop'] ) ? (array) wp_unslash( $_POST['ymkrf_shop'] ) : array();

		foreach ( $shops as $s ) {
			$slug = $s['slug'];
			if ( ! isset( $in[ $slug ] ) ) continue;

			foreach ( $fields as $key => $f ) {
				$v = isset( $in[ $slug ][ $key ] ) ? trim( (string) $in[ $slug ][ $key ] ) : '';
				$v = ( $f['type'] === 'textarea' )
				   ? sanitize_textarea_field( $v ) : sanitize_text_field( $v );

				/* もとの内容と同じものは、わざわざ持ちません */
				$base = isset( $s[ $key ] ) ? (string) $s[ $key ] : '';
				if ( $v !== '' && $v !== $base ) $now[ $slug ][ $key ] = $v;
			}
		}
		update_option( YMKRF_SHOPS_OPT, $now, false );
		$saved = true;
		$shops = ymkrf_shops();   /* 読みなおします */
	}

	if ( isset( $_POST['ymkrf_shops_reset'] ) && check_admin_referer( 'ymkrf_shops_edit' ) ) {
		delete_option( YMKRF_SHOPS_OPT );
		$saved = true;
		$shops = ymkrf_shops();
	}
	?>
	<div class="wrap">
	  <h1>店舗の内容</h1>

	  <?php if ( $saved ) : ?>
	    <div class="notice notice-success is-dismissible"><p>保存しました。</p></div>
	  <?php endif; ?>

	  <p class="description" style="max-width:900px;margin:10px 0 18px">
	    ここで直した内容は、<b>店舗・対応エリアのページ</b>と、
	    <b>コラムの執筆者にお店をえらんだとき</b>に出ます。
	    空にすると、はじめから決めてある内容にもどります。
	  </p>

	  <style>
	  .ymkrf-shops{ display:grid; gap:14px; max-width:1080px; }
	  @media (min-width:1400px){ .ymkrf-shops{ grid-template-columns:1fr 1fr; max-width:none; } }
	  .ymkrf-shop{
	    background:#fff; border:1px solid #c3c4c7; border-radius:8px; overflow:hidden;
	    box-shadow:0 1px 1px rgba(0,0,0,.04);
	  }
	  .ymkrf-shop__hd{
	    display:flex; align-items:baseline; gap:10px;
	    padding:9px 14px; background:#f6f7f7; border-bottom:1px solid #dcdcde;
	  }
	  .ymkrf-shop__nm{ font-size:15px; font-weight:700; margin:0; }
	  .ymkrf-shop__pref{ font-size:12px; color:#646970; }
	  .ymkrf-shop__bd{ padding:12px 14px 14px; }
	  .ymkrf-f{ display:block; margin:0 0 10px; }
	  .ymkrf-f:last-child{ margin-bottom:0; }
	  .ymkrf-f > span{
	    display:block; font-size:12px; font-weight:700; color:#1d2327; margin-bottom:3px;
	  }
	  .ymkrf-f textarea, .ymkrf-f input[type=text]{ width:100%; }
	  .ymkrf-f textarea{ min-height:62px; }
	  .ymkrf-f em{ display:block; font-style:normal; font-size:11px; color:#646970; margin-top:3px; }
	  .ymkrf-row{ display:grid; gap:10px; grid-template-columns:1fr 1fr; }
	  .ymkrf-jump{ margin:0 0 16px; }
	  .ymkrf-jump a{
	    display:inline-block; margin:0 6px 6px 0; padding:3px 11px;
	    background:#fff; border:1px solid #c3c4c7; border-radius:999px;
	    font-size:12px; text-decoration:none;
	  }
	  .ymkrf-save{
	    position:sticky; bottom:0; z-index:5; margin-top:18px;
	    padding:12px 0; background:#f0f0f1; border-top:1px solid #dcdcde;
	  }
	  </style>

	  <p class="ymkrf-jump">
	    <?php foreach ( $shops as $s ) : ?>
	      <a href="#sh-<?php echo esc_attr( $s['slug'] ); ?>"><?php echo esc_html( $s['name'] ); ?></a>
	    <?php endforeach; ?>
	  </p>

	  <form method="post">
	    <?php wp_nonce_field( 'ymkrf_shops_edit' ); ?>

	    <div class="ymkrf-shops">
	      <?php foreach ( $shops as $s ) : ?>
	        <?php $n = 'ymkrf_shop[' . $s['slug'] . ']'; ?>
	        <div class="ymkrf-shop" id="sh-<?php echo esc_attr( $s['slug'] ); ?>">

	          <div class="ymkrf-shop__hd">
	            <h2 class="ymkrf-shop__nm"><?php echo esc_html( $s['name'] ); ?></h2>
	            <span class="ymkrf-shop__pref"><?php echo esc_html( $s['pref'] ); ?></span>
	          </div>

	          <div class="ymkrf-shop__bd">

	            <label class="ymkrf-f">
	              <span>特徴</span>
	              <textarea name="<?php echo esc_attr( $n ); ?>[feature]" rows="3"><?php
	                echo esc_textarea( isset( $s['feature'] ) ? $s['feature'] : '' ); ?></textarea>
	              <em>このお店ならではのこと。店舗のページと、コラムの執筆者にこのお店をえらんだときに出ます。</em>
	            </label>

	            <div class="ymkrf-row">
	              <label class="ymkrf-f">
	                <span>電話番号</span>
	                <input type="text" name="<?php echo esc_attr( $n ); ?>[tel]"
	                       value="<?php echo esc_attr( isset( $s['tel'] ) ? $s['tel'] : '' ); ?>">
	                <em>空にすると 0800-777-3331 をご案内します</em>
	              </label>
	              <label class="ymkrf-f">
	                <span>営業時間</span>
	                <input type="text" name="<?php echo esc_attr( $n ); ?>[hours]"
	                       value="<?php echo esc_attr( isset( $s['hours'] ) ? $s['hours'] : '' ); ?>">
	                <em>例：8:00〜21:00</em>
	              </label>
	            </div>

	            <div class="ymkrf-row">
	              <label class="ymkrf-f">
	                <span>定休日</span>
	                <input type="text" name="<?php echo esc_attr( $n ); ?>[closed]"
	                       value="<?php echo esc_attr( isset( $s['closed'] ) ? $s['closed'] : '' ); ?>">
	                <em>例：年中無休</em>
	              </label>
	              <label class="ymkrf-f">
	                <span>お知らせ（ふきだし）</span>
	                <input type="text" name="<?php echo esc_attr( $n ); ?>[topic]"
	                       value="<?php echo esc_attr( isset( $s['topic'] ) ? $s['topic'] : '' ); ?>">
	                <em>店舗ページで、写真の上にふきだしで出ます</em>
	              </label>
	            </div>

	          </div>
	        </div>
	      <?php endforeach; ?>
	    </div>

	    <p class="ymkrf-save">
	      <button class="button button-primary button-large" name="ymkrf_shops_save" value="1">保存する</button>
	      <button class="button" name="ymkrf_shops_reset" value="1"
	        onclick="return confirm('ここで直した内容をぜんぶ捨てて、はじめの内容にもどします。よろしいですか？')">
	        はじめの内容にもどす</button>
	    </p>
	  </form>
	</div>
	<?php
}
