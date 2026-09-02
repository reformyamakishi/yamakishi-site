<?php
/**
 * Plugin Name: リフォームヤマキシ 初期セットアップ
 * Description: 商品カテゴリ・メーカー・展示店舗の登録と、V-styleの見本データ登録を1回だけ自動で行います。作業が終わったらこのファイルは削除して構いません。
 * Version: 1
 *
 * 置き場所： wp-content/mu-plugins/ymkrf-setup.php
 * 写真置き場： wp-content/ymkrf-import/  （jpgをそのまま置く）
 *
 * 一度実行すると ymkrf_setup_done というオプションに記録され、
 * 二度目以降は何もしません。作り直したいときは
 * 下の YMKRF_SETUP_VER の数字を増やしてください。
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'YMKRF_SETUP_VER', '95' );

/* キッチンの「ヤマキシ標準工事内容」。
   ホームページの一覧表（7項目）と、番号入りの図（8項目）の
   両方に載っているものを、重複を除いてまとめた11項目です。
   10商品すべてで同じ内容を使います。 */
if ( ! function_exists( 'ymkrf_kitchen_works' ) ) :
function ymkrf_kitchen_works() {
	return array(
		array( '既存流し台解体撤去工事', '古いキッチンの撤去にかかる工事です。' ),
		array( '養生工事',               '床・壁・下地を保護します。' ),
		array( '産業廃棄物処理運輸工事', '撤去した古いキッチンを廃棄処分するためにかかる費用です。' ),
		array( '水道工事',               '給水・給湯・排水の工事です。' ),
		array( '電気工事',               '設備機器の配線接続等の工事です。' ),
		array( 'ガス配管変更工事',       'ガスコンロを使うための配管工事です。' ),
		array( 'キッチンパネル設置工事', 'キッチンパネル部材費込み施工いたします。' ),
		array( '下地工事',               '大工工事です。キッチンパネル設置面の補修、補強を行います。' ),
		array( 'システムキッチン取付設置', '新しいシステムキッチンの取り付け・設置工事です。' ),
		array( 'シロッコファン取付工事', 'シロッコファンの取付工事です。' ),
	);
}
endif;



/* 給湯器の「主な機能」の一覧をつくります（2026/09/01）。
   オート／フルオートの違いは、リンナイの説明のとおりです。
     オート　　… 湯はり・保温・追いだきまでが自動
     フルオート… それに加えて「たし湯」まで自動 */
if ( ! function_exists( 'ymkrf_boiler_speclist' ) ) :
function ymkrf_boiler_speclist( $bp ) {

	$feat = array( '自動湯はり', '自動保温', '追い焚き' );
	if ( ! empty( $bp['full'] ) ) $feat[] = '自動たし湯';

	$rows = array(
		array( 'ttl' => '主な機能', 'body' => implode( "\n", $feat ) ),
	);

	/* 石油給湯機は「特定保守製品」です。機能ではないので、別の枠にします。 */
	if ( ! empty( $bp['hoshu'] ) ) {
		$rows[] = array(
			'ttl'  => '特定保守製品です',
			'body' => "石油給湯機は、法律で点検がすすめられている「特定保守製品」です。\n"
			        . "10年をめどに、有料の点検のご案内をいたします。",
		);
	}

	return $rows;
}
endif;


/* エコキュートの「主な機能」をつくります（2026/09/01）。

   エコキュートは、いただいたPDF（本番サイトの /products/ecocute/）に
   「タイプ」しか書かれていません。
   そこで、そのタイプから読み取れることだけを並べています。
     フルオート … 湯はり・保温・追いだき・たし湯まで自動
     高圧　　　 … シャワーの勢いが強いタイプ
     高効率　　 … 電気の使用量をよりおさえるタイプ
   メーカーのカタログにしか無い数値（年間給湯保温効率など）は、
   確認できていないので入れていません。 */
if ( ! function_exists( 'ymkrf_ecocute_speclist' ) ) :
function ymkrf_ecocute_speclist( $ep ) {

	$type = isset( $ep['grade'] ) ? (string) $ep['grade'] : '';

	$feat = array( '自動湯はり', '自動保温', '追い焚き' );
	if ( strpos( $type, 'フルオート' ) !== false ) $feat[] = '自動たし湯';
	if ( strpos( $type, '高圧' ) !== false )       $feat[] = '高圧給湯（シャワーの勢いが強いタイプ）';
	if ( strpos( $type, '高効率' ) !== false )     $feat[] = '高効率（電気の使用量をよりおさえるタイプ）';

	$rows = array(
		array( 'ttl' => '主な機能', 'body' => implode( "\n", $feat ) ),
	);

	/* 補助金は年度で内容が変わるので、金額や条件は書きません。
	   対象かどうかは、在庫確認シートの「2026 国 補助金対象」に合わせています。 */
	if ( ! empty( $ep['hojo'] ) ) {
		$rows[] = array(
			'ttl'  => '補助金適用',
			'body' => "国の補助金の対象になる機種です。\n"
			        . "年度によって、金額も申請のしめきりも変わります。\n"
			        . "そのときにお使いいただけるものを、お見積りの際にご案内します。",
		);
	}

	return $rows;
}
endif;


/**
 * init（優先度99）に付けているので、管理画面だけでなく
 * サイトの普通のページを開いたときにも動きます。
 * 「管理画面にログインしないと商品が増えない」という手間をなくすためです。
 *
 * ただし本番サーバーで誰でも動かせては困るので、
 * ・管理者としてログインしている
 * ・または localhost（手元のXAMPP）で見ている
 * のどちらかのときだけ動きます。
 */
add_action( 'init', function () {

	if ( get_option( 'ymkrf_setup_done' ) === YMKRF_SETUP_VER ) return;
	if ( ! post_type_exists( 'ymkrf_product' ) ) return; // テーマがまだ有効でない
	if ( wp_doing_ajax() || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) return;
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) return;

	$host  = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
	$host  = explode( ':', $host )[0];
	$local = in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true );

	if ( ! $local && ! current_user_can( 'manage_options' ) ) return;

	/* 写真を何十枚もまとめて取り込むので、時間とメモリの上限を外します。
	   （これが無いと途中で止まり、写真が入らない商品ができてしまいます） */
	@set_time_limit( 0 );
	@ini_set( 'memory_limit', '512M' );
	@ignore_user_abort( true );

	/* サイト側から動かすときは、管理画面用の関数がまだ読み込まれていないので、
	   ここで読み込みます（wp_generate_attachment_metadata などを使うため）。 */
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/post.php';

	$log = array();

	/* ------------------------------------------------------------
	   1. 分類（カテゴリ・メーカー・展示店舗）を登録
	   ------------------------------------------------------------ */
	$taxonomies = array(

		'ymkrf_product_cat' => array(
			'kitchen'    => 'キッチン',
			'bathroom'   => 'お風呂',
			'toilet'     => 'トイレ',
			'lavatory'   => '洗面化粧台',
			'boiler'     => '給湯器',
			'ecocute'    => 'エコキュート',
			'outer-wall' => '外壁・屋根',
			'window'     => '窓・玄関ドア',
			'interior'   => '内装・改装',
		),

		'ymkrf_maker' => array(
			'panasonic'      => 'Panasonic',
			'lixil'          => 'LIXIL',
			'toto'           => 'TOTO',
			'cleanup'        => 'クリナップ',
			'takara'         => 'タカラスタンダード',
			'toclas'         => 'トクラス',
			'rinnai'         => 'リンナイ',
			'noritz'         => 'ノーリツ',
			'daikin'         => 'ダイキン',
			'mitsubishi'     => '三菱電機',
			'hitachi'        => '日立',
			'ykkap'          => 'YKK AP',
			'nichiha'        => 'ニチハ',
			'woodone'        => 'WOODONE（ウッドワン）',
			'sankyoalumi'    => '三協アルミ',
		),

		/* スラッグは店舗ページのURLに合わせています */
		'ymkrf_shop' => array(
			'tazuruhama' => '田鶴浜店',
			'hakui'      => '羽咋店',
			'tagami'     => '金沢田上店',
			'nonoichi'   => '金沢野々市店',
			'kawakita'   => '川北店',
			'komathu'    => '小松店',
			'shinkaga'   => '新加賀店',
			'kanadu'     => '金津店',
			'kahahothu'  => '開発店',
			'asahi'      => '朝日店',
			/* 2026年10月オープン予定 */
			'higashikanazawa' => '東金沢店',
		),
	);

	/* ------------------------------------------------------------
	   2026/08/31 ─ 「給湯器・エコキュート」を2つに分けます

	   もともと1つだった商品カテゴリ「給湯器・エコキュート（boiler）」を、
	   　・エコキュート … ecocute
	   　・給湯器　　　 … boiler（あいたスラッグを使い直します）
	   の2つにします。boiler を給湯器にしたのは、いまの本番サイトの
	   /products/boiler/ が給湯器のページで、施工事例の部位も
	   boiler＝給湯器・ecocute＝エコキュート に分かれているためです。

	   この付け替えは1回だけです。名前が「給湯器・エコキュート」の
	   ときだけ動くので、二度目からは何もしません。
	   ------------------------------------------------------------ */
	if ( taxonomy_exists( 'ymkrf_product_cat' ) ) {
		$oldcat = get_term_by( 'slug', 'boiler', 'ymkrf_product_cat' );
		if ( $oldcat && ! is_wp_error( $oldcat )
			&& $oldcat->name === '給湯器・エコキュート'
			&& ! get_term_by( 'slug', 'ecocute', 'ymkrf_product_cat' ) ) {

			wp_update_term( $oldcat->term_id, 'ymkrf_product_cat', array(
				'name' => 'エコキュート',
				'slug' => 'ecocute',
			) );
			$log[] = '商品カテゴリ「給湯器・エコキュート」を「エコキュート」に変えました（このあと「給湯器」を新しく作ります）';
		}
	}

	foreach ( $taxonomies as $tax => $terms ) {
		foreach ( $terms as $slug => $name ) {
			if ( term_exists( $slug, $tax ) ) continue;
			$r = wp_insert_term( $name, $tax, array( 'slug' => $slug ) );
			if ( ! is_wp_error( $r ) ) $log[] = "{$tax}: {$name}";
		}
	}

	/* ------------------------------------------------------------
	   2-b. キッチン全商品の「ヤマキシ標準工事内容」をそろえます
	        写真の取り込みより先に済ませます。あとに置くと、
	        取り込みが長引いたときにここまで届かないことがあるためです。
	        ホームページの一覧表と番号入りの図の両方に合わせた11項目です。
	   ------------------------------------------------------------ */
	$kt2 = get_term_by( 'slug', 'kitchen', 'ymkrf_product_cat' );
	if ( $kt2 && ! is_wp_error( $kt2 ) && get_option( 'ymkrf_kitchen_works_ver' ) !== '5' ) {
		$rows = array();
		foreach ( ymkrf_kitchen_works() as $r ) $rows[] = array( 'name' => $r[0], 'text' => $r[1] );

		$ks2 = get_posts( array(
			'post_type'      => 'ymkrf_product',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'tax_query'      => array( array(
				'taxonomy' => 'ymkrf_product_cat', 'field' => 'term_id', 'terms' => $kt2->term_id,
			) ),
		) );
		foreach ( (array) $ks2 as $kid ) update_post_meta( $kid, '_ymkrf_works', $rows );
		if ( $ks2 ) $log[] = 'キッチン' . count( $ks2 ) . '件の標準工事内容を11項目にそろえました';
		update_option( 'ymkrf_kitchen_works_ver', '5' );
	}


	/* ------------------------------------------------------------
	   2. 写真をメディアに取り込む
	   ------------------------------------------------------------ */
	$dir = WP_CONTENT_DIR . '/ymkrf-import';

	/**
	 * ファイル名を渡すとメディアのIDを返します。
	 * すでに取り込み済みなら、そのIDを使い回します。
	 */
	/* 写真の置き場所は2か所。上から順に探します。
	   2つめはテーマの assets（作業フォルダへの入口）なので、
	   GitHubに入れた写真がそのままメディアに取り込めます。 */
	$theme = WP_CONTENT_DIR . '/themes/ymkrf/assets/img/products';
	$dirs = array(
		$theme . '/rakuera',
		$theme . '/refit',
		$theme . '/sierra',
		$theme . '/sclass',
		$theme . '/stedia',
		$theme . '/edel',
		$theme . '/classo',
		$theme . '/richelle',
		$theme . '/centro',
		$theme . '/v-style',
		$theme . '/ofuroa',
		$theme . '/sazana-n',
		$theme . '/lidea-m',
		$theme . '/rakuvia',
		$theme . '/sazana-t',
		$theme . '/lidea-b',
		$theme . '/granspa',
		$theme . '/selevia',
		$theme . '/sinla',
		$theme . '/amage-z',
		$theme . '/amage-z-aqua',
		$theme . '/purerest-qr',
		$theme . '/alauno-vs5',
		$theme . '/amage-z-premium',
		$theme . '/pleas-ls',
		$theme . '/alauno-s160',
		$theme . '/alauno-s160-counter',
		$theme . '/gga3',
		$theme . '/gga1-counter',
		$theme . '/satis-s',
		$theme . '/satis-s-counter',
		$theme . '/neorest-rs3',
		$theme . '/neorest-rs3-counter',
		$theme . '/v1',
		$theme . '/d7',
		$theme . '/bga',
		$theme . '/rakutowa',
		$theme . '/j1',
		$theme . '/rejust',
		$theme . '/k1',
		$theme . '/fansio',
		$theme . '/r1',
		$theme . '/sakua',
		$theme . '/utsukushiizu',
		$theme . '/octave',
		$theme . '/woodone',
		$theme . '/otq-c4706say',
		$theme . '/otq-c4706ay',
		$theme . '/otq-4706say',
		$theme . '/otq-4706says',
		$theme . '/srt-c2071saw',
		$theme . '/ruf-e2006aw',
		$theme . '/ruf-205saw',
		$theme . '/gt-2070saw',
		$theme . '/he-s37lqs',
		$theme . '/he-s46lqs',
		$theme . '/srt-w466',
		$theme . '/srt-s376ua',
		$theme . '/srt-s466ua',
		$theme . '/srt-s377u',
		$theme . '/srt-s467u',
		WP_CONTENT_DIR . '/themes/ymkrf/assets/img/works',
		$dir,                 // 最後の受け皿（wp-content/ymkrf-import）
	);

	/* 見つからなかった写真の数。商品ごとに前後の差を取り、
	   「写真が入りきらなかった商品」を確実に見分けるために使います。 */
	$missing = 0;

	/* $force に true を渡すと、覚えている番号を捨てて、もう一度探し直します。
	   （古い写真を消してから入れ直すときに使います） */
	$img = function ( $file, $alt = '', $force = false ) use ( $dirs, &$missing ) {
		static $cache = array();
		if ( $force ) unset( $cache[ $file ] );
		if ( isset( $cache[ $file ] ) ) return $cache[ $file ];

		$found = get_posts( array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_ymkrf_import',
			'meta_value'     => $file,
		) );
		if ( $found ) return $cache[ $file ] = (int) $found[0];

		$path = '';
		foreach ( $dirs as $d ) {
			if ( file_exists( $d . '/' . $file ) ) { $path = $d . '/' . $file; break; }
		}
		if ( ! $path ) { $missing++; return $cache[ $file ] = 0; }

		$up = wp_upload_bits( $file, null, file_get_contents( $path ) );
		if ( ! empty( $up['error'] ) ) { $missing++; return $cache[ $file ] = 0; }

		$type = wp_check_filetype( $up['file'] );
		$id   = wp_insert_attachment( array(
			'post_mime_type' => $type['type'],
			'post_title'     => $alt ?: pathinfo( $file, PATHINFO_FILENAME ),
			'post_status'    => 'inherit',
		), $up['file'] );
		if ( ! $id || is_wp_error( $id ) ) { $missing++; return $cache[ $file ] = 0; }

		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $up['file'] ) );
		update_post_meta( $id, '_ymkrf_import', $file );
		if ( $alt ) update_post_meta( $id, '_wp_attachment_image_alt', $alt );

		return $cache[ $file ] = (int) $id;
	};

	/* ------------------------------------------------------------
	   2-b. ラクエラの写真をメディアに入れておく
	        （商品そのものは、管理画面から手で登録します）
	   ------------------------------------------------------------ */
	$stock = array(
		'raku-main.jpg'             => 'クリナップ ラクエラ I型2550サイズ（設置イメージ）',
		'raku-main-product.jpg'     => 'クリナップ ラクエラ I型2550サイズ（ペールウッド扉）',
		'raku-cabinet.jpg'          => '扉カラー4色の面材を重ねて並べたところ',
		'raku-color-white.jpg'      => '扉カラー トーンホワイト',
		'raku-color-charcoal.jpg'   => '扉カラー トーンチャコール',
		'raku-color-palewood.jpg'   => '扉カラー ペールウッド',
		'raku-color-mokawood.jpg'   => '扉カラー モカウッド',
		'raku-color-charcoalwood.jpg' => '扉カラー チャコールウッド',
		'raku-color-white-set.jpg'  => 'トーンホワイトのキッチンと色見本',
		'raku-color-charcoal-set.jpg' => 'トーンチャコールのキッチンと色見本',
		'raku-color-palewood-set.jpg' => 'ペールウッドのキッチンと色見本',
		'raku-color-mokawood-set.jpg' => 'モカウッドのキッチンと色見本',
		'raku-color-charcoalwood-set.jpg' => 'チャコールウッドのキッチンと色見本',
		'raku-handle-bar.jpg'       => 'L:バー取手（シルバー）',
		'raku-spec-conro.jpg'       => 'ホーロー3口トップコンロ',
		'raku-spec-top.jpg'         => 'ステンレス天板',
		'raku-spec-hood.jpg'        => 'フラットスリムレンジフード',
		'raku-spec-sink.jpg'        => 'ステンレスTUシンク',
		'raku-spec-rail.jpg'        => 'サイレントレール',
		'raku-spec-faucet.jpg'      => 'シングルレバー水栓',
		'raku-spec-wallcab.jpg'     => 'ミドル吊戸棚',
		'raku-spec-panel.jpg'       => 'キッチンパネル',
		'raku-spec-floor.jpg'       => '床板メラミン化粧板',
		'raku-point-foot1.jpg'      => 'フットエリア収納にストックをしまっているところ',
		'raku-point-foot2.jpg'      => '蹴込みレスデザインで足元まですっきりした状態',
		'raku-point-hand1.jpg'      => 'ハンドエリア収納に調味料や鍋を収めたところ',
		'raku-point-hand2.jpg'      => 'マグネフリーパネルに市販の収納アイテムを取り付けたところ',
		'raku-point-hood1.jpg'      => 'フラットスリムレンジフードの外観',
		'raku-point-hood2.jpg'      => 'レンジフードの内面を手で拭いているところ',
		'raku-point-top1.jpg'       => 'ドット柄コイニング加工のステンレス天板',
		'raku-opt-dish.jpg'         => 'W450mmプルオープン食器洗い乾燥機',
		'raku-opt-wallcab.jpg'      => 'ハンドムーブ吊戸棚（水切りタイプ照明付）',
		'raku-opt-hood.jpg'         => '洗エールレンジフード',

		/* ---- リフィット（Dグレード・タカラスタンダード） ---- */
		'refit-main.jpg'            => 'タカラスタンダード リフィット I型2550サイズ',
		'refit-color-light.jpg'          => '扉カラー ライト',
		'refit-color-lightwhite.jpg'     => '扉カラー ライトホワイト',
		'refit-color-mediumbrown.jpg'    => '扉カラー ミディアムブラウン',
		'refit-color-darkbrown.jpg'      => '扉カラー ダークブラウン',
		'refit-color-white.jpg'          => '扉カラー ホワイト',
		'refit-color-superwhite.jpg'     => '扉カラー スーパーホワイト',
		'refit-color-winered.jpg'        => '扉カラー ワインレッド',
		'refit-top-beige.jpg'       => '天板カラー シャインベージュ',
		'refit-top-gray.jpg'        => '天板カラー シャイングレー',
		'refit-top-white.jpg'       => '天板カラー シャインホワイト',
		'refit-spec-top.jpg'        => '人造大理石天板',
		'refit-spec-sink.jpg'       => 'らくエルステンレスシンク',
		'refit-spec-faucet.jpg'     => 'シングルレバー水栓',
		'refit-spec-cabinet.jpg'    => '木製キャビネット（引出し底板ホーロー）',
		'refit-spec-rail.jpg'       => 'ソフトクローズレール',
		'refit-spec-hood.jpg'       => 'シロッコファンレンジフード',
		'refit-spec-conro.jpg'      => 'ホーロー3口トップコンロ',
		'refit-spec-led.jpg'        => 'LED手元照明',
		'refit-spec-foot.jpg'       => '足元収納スライドタイプ',
		'refit-spec-latch.jpg'      => '吊戸棚耐震ラッチ',
		'refit-point-sink1.jpg'     => 'らくエルシンクの広さ（幅30cm・奥行50cm）',
		'refit-point-fit.jpg'       => '1cm刻みでオーダーできることを示した図',
		'refit-point-wall.jpg'      => '吊戸棚の高さ4種類（40・50・60・70cm）',
		'refit-point-option.jpg'    => '食器棚のサイズバリエーション',
		'refit-point-flow.jpg'      => '洗った水が手前に流れてこないシンクの段差',
		'refit-point-drain.jpg'     => 'シンクと一体成型された浅型排水口',
		'refit-point-horo1.jpg'     => 'ホーロー整流板を金属たわしで洗えることを示す写真',
		'refit-point-horo2.jpg'     => 'ホーロー整流板を拭いているところ',
		'refit-opt-dish.jpg'        => 'W450mmプルオープン食器洗い乾燥機',
		'refit-opt-wallcab.jpg'     => '電動昇降吊戸棚',
		'refit-opt-hood.jpg'        => 'ホーロークリーンフード',
		'refit-opt-faucet.jpg'      => '浄水器内蔵ハンドシャワー水栓',

		/* ---- シエラS（Cグレード・LIXIL） ---- */
		'sierra-main.jpg'                   => 'LIXIL シエラS I型2550タイプ',
		'sierra-color-blackstucco.jpg'      => '扉カラー ブラックスタッコ',
		'sierra-color-greigestucco.jpg'     => '扉カラー グレージュスタッコ',
		'sierra-color-whitestucco.jpg'      => '扉カラー ホワイトスタッコ',
		'sierra-color-greenbronze.jpg'      => '扉カラー グリーンブロンズ',
		'sierra-color-sunsetcopper.jpg'     => '扉カラー サンセットカッパー',
		'sierra-color-brownbrass.jpg'       => '扉カラー ブラウンブラス',
		'sierra-color-deepred.jpg'          => '扉カラー ディープレッド',
		'sierra-color-palewhite-gloss.jpg'  => '扉カラー ペールホワイト（光沢）',
		'sierra-color-burnedwood.jpg'       => '扉カラー バーンドウッド',
		'sierra-color-bleachwood.jpg'       => '扉カラー ブリーチウッド',
		'sierra-color-criedark.jpg'         => '扉カラー クリエダーク',
		'sierra-color-criemocha.jpg'        => '扉カラー クリエモカ',
		'sierra-color-criecherry.jpg'       => '扉カラー クリエチェリー',
		'sierra-color-crieoak.jpg'          => '扉カラー クリエオーク',
		'sierra-color-palewhite-wood.jpg'   => '扉カラー ペールホワイト（木目）',
		'sierra-top-white.jpg'      => '天板カラー ソルティホワイト',
		'sierra-top-gray.jpg'       => '天板カラー シルフィーグレー',
		'sierra-top-beige.jpg'      => '天板カラー シルフィーベージュ',
		'sierra-handle-slim-black.jpg'  => 'スリム取手 ブラック',
		'sierra-handle-slim-nickel.jpg' => 'スリム取手 シャインニッケル',
		'sierra-handle-slim-silver.jpg' => 'スリム取手 シルバー',
		'sierra-handle-mid-black.jpg'   => 'ミドル取手 ブラック',
		'sierra-handle-mid-nickel.jpg'  => 'ミドル取手 シャインニッケル',
		'sierra-handle-mid-silver.jpg'  => 'ミドル取手 シルバー',
		'sierra-spec-top.jpg'       => '人造大理石天板',
		'sierra-spec-sink.jpg'      => 'スキットシンク（ステンレス）',
		'sierra-spec-faucet.jpg'    => 'オールインワン浄水栓',
		'sierra-spec-hood.jpg'      => 'シロッコファンレンジフード',
		'sierra-spec-stocker.jpg'   => 'スライドストッカー',
		'sierra-spec-rail.jpg'      => 'ソフトモーションレール',
		'sierra-spec-wallcab.jpg'   => 'ミドル吊戸棚（扉キャッチ機構）',
		'sierra-spec-light.jpg'     => 'システムライト',
		'sierra-spec-panel.jpg'     => 'キッチンパネル',
		'sierra-spec-ih.jpg'        => 'IHヒーター',
		'sierra-point-flow1.jpg'    => 'ナイアガラフロー式で水が段差に流れ込むところ',
		'sierra-point-flow2.jpg'    => '水滴を段差で受け止めるところ',
		'sierra-point-pocket.jpg'   => 'シンクまわりが片付く大きなポケット',
		'sierra-point-cartridge.jpg'=> '浄水カートリッジを内蔵した水栓の切替図',
		'sierra-point-shower.jpg'   => 'ひろびろシャワーと整流の比較',
		'sierra-point-eco.jpg'      => 'エコハンドルのシャワー',
		'sierra-point-stocker1.jpg' => '足元のけこみ部分まで使えるスライドストッカー',
		'sierra-point-rail.jpg'     => '奥行きいっぱい引き出せるソフトモーションレール',
		'sierra-opt-dish.jpg'       => 'W450mmプルオープン食器洗い乾燥機',
		'sierra-opt-faucet.jpg'     => 'ハンズフリー水栓',
		'sierra-opt-pallet.jpg'     => 'クイックパレット W900',
		'sierra-opt-sink.jpg'       => 'キレイシンク（人造大理石シンク）',

		/* --- Sクラス（Bグレード・Panasonic） --- */
		'sclass-main.jpg'                => 'Sクラスのキッチン全体',
		'sclass-top-white.jpg'           => '天板カラー グラニュールホワイト',
		'sclass-sink-white.jpg'          => 'シンクカラー グラニュールホワイト',
		'sclass-sink-beige.jpg'          => 'シンクカラー ミストベージュ',
		'sclass-sink-gray.jpg'           => 'シンクカラー グレー',
		'sclass-color-walnut.jpg'        => '扉カラー ソフトウォールナット柄',
		'sclass-color-teak.jpg'          => '扉カラー ソフトチーク柄',
		'sclass-color-chestnut.jpg'      => '扉カラー ナチュラルチェスナット柄',
		'sclass-color-whiteash.jpg'      => '扉カラー ホワイトアッシュ柄',
		'sclass-color-greyoak.jpg'       => '扉カラー グレーオーク柄',
		'sclass-color-vintagemetal.jpg'  => '扉カラー ヴィンテージメタル柄',
		'sclass-color-vintagebrown.jpg'  => '扉カラー ヴィンテージブラウン柄',
		'sclass-color-scratchmetal.jpg'  => '扉カラー スクラッチメタル柄',
		'sclass-color-earthwhite.jpg'    => '扉カラー アースホワイト',
		'sclass-color-alberoblack.jpg'   => '扉カラー アルベロブラック',
		'sclass-color-alberowhite.jpg'   => '扉カラー アルベロホワイト',
		'sclass-color-navy.jpg'          => '扉カラー ネイビー',
		'sclass-color-beige.jpg'         => '扉カラー ベージュ',
		'sclass-color-beautywhite.jpg'   => '扉カラー ビューティホワイト',
		'sclass-handle-lca.jpg'          => 'アルミライン取手 LCA',
		'sclass-handle-han.jpg'          => 'ハンドル取手 HAN',
		'sclass-handle-hcd.jpg'          => 'ハンドル取手 HCD',
		'sclass-handle-hda.jpg'          => 'ハンドル取手 HDA',
		'sclass-handle-hae.jpg'          => 'ハンドル取手 HAE',
		'sclass-handle-hce.jpg'          => 'ハンドル取手 HCE',
		'sclass-handle-hde.jpg'          => 'ハンドル取手 HDE',
		'sclass-handle-mjd.jpg'          => 'ハンドル取手＋つまみ取手 MJD',
		'sclass-handle-mje.jpg'          => 'ハンドル取手＋つまみ取手 MJE',
		'sclass-handle-mjg.jpg'          => 'ハンドル取手＋つまみ取手 MJG',
		'sclass-spec-ih.jpg'             => 'IHクッキングヒーター',
		'sclass-spec-top.jpg'            => '人造大理石カウンター',
		'sclass-spec-hood.jpg'           => 'スマートフードII さっとれるファン仕様',
		'sclass-spec-sink.jpg'           => 'ムーブラックシンク（人造大理石）',
		'sclass-spec-soft.jpg'           => 'ソフトクロージング機構',
		'sclass-spec-faucet.jpg'         => '混合水栓ハンドシャワー',
		'sclass-spec-stocker.jpg'        => '扉ストッカー',
		'sclass-spec-wallcab.jpg'        => '吊戸棚（耐震ロック機構）',
		'sclass-spec-outlet.jpg'         => 'クッキングコンセント（シルバー）',
		'sclass-spec-panel.jpg'          => 'キッチンパネル',
		'sclass-point-sink1.jpg'         => '洗剤ラックを動かして広く使えるシンク',
		'sclass-point-sink2.jpg'         => 'ラックを縦横自由に置ける説明図',
		'sclass-point-rack.jpg'          => '付属の洗剤ラック',
		'sclass-point-net.jpg'           => 'ムーブラック用スラくるネット',
		'sclass-point-outlet1.jpg'       => '手元のクッキングコンセント',
		'sclass-point-outlet2.jpg'       => '小物フックにミトンを掛けたところ',
		'sclass-point-hood1.jpg'         => 'スマートフードIIの本体とボタンスイッチ',
		'sclass-point-soft1.jpg'         => '静かに閉まるソフトクロージング機構',
		'sclass-opt-dish.jpg'            => 'W450mmプルオープン食器洗い乾燥機',
		'sclass-opt-sink.jpg'            => 'ラクするーシンク（スゴピカ素材）',
		'sclass-opt-hood.jpg'            => 'ほっとくリーンフード',
		'sclass-opt-faucet.jpg'          => '混合水栓サラサラワイドシャワー',

		/* --- ステディア（Aグレード・クリナップ） --- */
		'stedia-main.jpg'              => 'ステディアのキッチン全体',
		'stedia-top-dot.jpg'           => 'ステンレス天板 ドット柄コイニング加工',
		'stedia-color-catwhite.jpg'    => '扉カラー スエードホワイト CAT',
		'stedia-color-c9klatte.jpg'    => '扉カラー ミクスドラテ C9K',
		'stedia-color-ckggreige.jpg'   => '扉カラー ルオントグレージュ CKG',
		'stedia-color-e5kgrey.jpg'     => '扉カラー ロッシュグレー E5K',
		'stedia-color-cazcharcoal.jpg' => '扉カラー スエードチャコール CAZ',
		'stedia-color-ecggrey.jpg'     => '扉カラー トワルグレー ECG',
		'stedia-color-cklsepia.jpg'    => '扉カラー ルオントセピア CKL',
		'stedia-color-e5hcharcoal.jpg' => '扉カラー ロッシュチャコール E5H',
		'stedia-color-ecurose.jpg'     => '扉カラー トワルローズ ECU',
		'stedia-color-c4bbirch.jpg'    => '扉カラー クラシカルバーチ C4B',
		'stedia-handle-silver.jpg'     => 'ロングバー取手 シルバー',
		'stedia-handle-black.jpg'      => 'ロングバー取手 ブラック',
		'stedia-handle-nekoashi.jpg'   => 'ネコアシ取手 ブラック',
		'stedia-handle-line.jpg'       => 'ライン取手 シルバー',
		'stedia-handle-lineblack.jpg'  => 'ライン取手 ブラック',
		'stedia-spec-ih.jpg'           => 'IHクッキングヒーター',
		'stedia-spec-top.jpg'          => 'ステンレス天板',
		'stedia-spec-sink.jpg'         => 'ステンレスシンク',
		'stedia-spec-faucet.jpg'       => 'シャワーホース付き水栓',
		'stedia-spec-hood.jpg'         => 'とってもクリンフード',
		'stedia-spec-rail.jpg'         => 'サイレントレール',
		'stedia-spec-panel.jpg'        => 'キッチンパネル',
		'stedia-spec-cabinet.jpg'      => 'ステンレスキャビネット',
		'stedia-spec-wallcab.jpg'      => 'ミドル吊戸棚',
		'stedia-point-eco1.jpg'        => '骨組みまでステンレスのエコキャビネット',
		'stedia-point-pocket1.jpg'     => '引き出しの中を立体的に使えるツールポケット',
		'stedia-point-pocket2.jpg'     => 'ラップやホイルを立てて収納したところ',
		'stedia-point-sink1.jpg'       => '水にのって排水口へ流れる流レールシンク',
		'stedia-point-sink2.jpg'       => '継ぎ目の無い排水口',
		'stedia-point-hood2.jpg'       => 'リーフプレートをスポンジで洗っているところ',
		'stedia-point-hood3.jpg'       => '親水性塗装が油汚れを浮かせる仕組みの図',
		'stedia-point-hood4.jpg'       => '立体構造フィルターの分解図',
		'stedia-opt-dish.jpg'          => 'W450mmプルオープン食器洗い乾燥機',
		'stedia-opt-acryston.jpg'      => 'アクリストン天板とアクリストンシンク',
		'stedia-opt-trap.jpg'          => 'かってにクリントラップの仕組み図',
		'stedia-opt-araeru.jpg'        => '洗エールレンジフード',
		'stedia-opt-hood.jpg'          => 'とってもクリンフード',
		'stedia-opt-faucet.jpg'        => 'シャワーホース付き水栓',

		/* --- エーデル（Sグレード・タカラスタンダード） --- */
		'edel-main.jpg'            => 'エーデルのキッチン全体',
		'edel-color-white.jpg'     => '扉カラー ホワイト',
		'edel-color-ivory.jpg'     => '扉カラー フローラルアイボリー',
		'edel-color-beige.jpg'     => '扉カラー ベージュ',
		'edel-color-lightgray.jpg' => '扉カラー ライトグレー',
		'edel-color-lightpink.jpg' => '扉カラー ライトピンク',
		'edel-color-darkblue.jpg'  => '扉カラー ダークブルー',
		'edel-color-brown.jpg'     => '扉カラー ブラウン',
		'edel-top-beige.jpg'       => '天板カラー ソリッドベージュ',
		'edel-top-gray.jpg'        => '天板カラー ソリッドグレー',
		'edel-top-white.jpg'       => '天板カラー ソリッドホワイト',
		'edel-sink-white.jpg'      => 'シンクカラー ホワイト',
		'edel-sink-gray.jpg'       => 'シンクカラー グレー',
		'edel-sink-beige.jpg'      => 'シンクカラー ベージュ',
		'edel-spec-top.jpg'        => 'アクリル人造大理石天板',
		'edel-spec-sink.jpg'       => 'アクリル人造大理石シンク',
		'edel-spec-faucet.jpg'     => '浄水器内蔵ハンドシャワー',
		'edel-spec-cabinet.jpg'    => 'ホーロー製キャビネット',
		'edel-spec-rail.jpg'       => 'ソフトクローズレール',
		'edel-spec-panel.jpg'      => 'ホーロークリーン キッチンパネル',
		'edel-spec-rack.jpg'       => 'どこでもラック（アルミタイプ）',
		'edel-spec-led.jpg'        => 'LED手元照明',
		'edel-spec-hood.jpg'       => 'シロッコファンレンジフード',
		'edel-spec-wallcab.jpg'    => '吊戸棚（H700）',
		'edel-point-horo1.jpg'     => '油汚れも油性ペンも水拭きで落ちるホーロー面材',
		'edel-point-horo2.jpg'     => 'ホーロー面材に火を近づけているところ',
		'edel-point-horo3.jpg'     => 'ホーロー面材をタワシで磨いているところ',
		'edel-point-horo4.jpg'     => 'キッチンのそばで遊ぶ子ども',
		'edel-point-rack1.jpg'     => '壁に取り付けたどこでもラック',
		'edel-point-rack2.jpg'     => 'どこでもラックの収納パーツいろいろ',
		'edel-point-acryl1.jpg'    => '天板に熱い鍋を置いているところ',
		'edel-point-acryl2.jpg'    => '天板の汚れを水拭きしているところ',
		'edel-point-acryl3.jpg'    => '天板に瓶が倒れたところ',
		'edel-opt-dish.jpg'        => 'W450mmプルオープン食器洗い乾燥機',
		'edel-opt-lift.jpg'        => '電動昇降吊戸棚',
		'edel-opt-hood.jpg'        => 'ホーロークリーンフード',
		'edel-opt-irack.jpg'       => 'アイラック水切りタイプ',

		/* --- ザ・クラッソ（SSグレード・TOTO） --- */
		'classo-main.jpg'             => 'ザ・クラッソのキッチン全体',
		'classo-top-snow.jpg'         => '天板 クリスタルスノー',
		'classo-top-gray.jpg'         => '天板 クリスタルグレー',
		'classo-top-greige.jpg'       => '天板 クリスタルグレージュ',
		'classo-top-palegreen.jpg'    => '天板 クリスタルペールグリーン',
		'classo-top-lightpink.jpg'    => '天板 クリスタルライトピンク',
		'classo-top-dullgray.jpg'     => '天板 クリスタルダルグレー',
		'classo-sink-snow.jpg'        => 'シンク クリスタルスノー',
		'classo-sink-gray.jpg'        => 'シンク クリスタルグレー',
		'classo-sink-greige.jpg'      => 'シンク クリスタルグレージュ',
		'classo-sink-palegreen.jpg'   => 'シンク クリスタルペールグリーン',
		'classo-sink-lightpink.jpg'   => 'シンク クリスタルライトピンク',
		'classo-sink-dullgray.jpg'    => 'シンク クリスタルダルグレー',
		'classo-color-unigray.jpg'    => '扉カラー ユニグレー',
		'classo-color-barawhite.jpg'  => '扉カラー バラホワイト',
		'classo-color-barabeige.jpg'  => '扉カラー バラベージュ',
		'classo-color-baramarron.jpg' => '扉カラー バラマロン',
		'classo-color-baranavy.jpg'   => '扉カラー バラネイビー',
		'classo-color-uninature.jpg'  => '扉カラー ユニナチュレ',
		'classo-handle-slim-silver.jpg'  => 'スリム取手 ステンシルバー',
		'classo-handle-round-silver.jpg' => 'ラウンド取手 ステンシルバー',
		'classo-handle-classic.jpg'      => 'クラシック取手',
		'classo-handle-slim-black.jpg'   => 'スリム取手 ブラック',
		'classo-handle-round-black.jpg'  => 'ラウンド取手 ブラック',
		'classo-handle-line.jpg'         => 'ライン取手',
		'classo-spec-ih.jpg'          => 'IHクッキングヒーター',
		'classo-spec-counter.jpg'     => 'クリスタルカウンター単色',
		'classo-spec-hood.jpg'        => 'ゼロフィルターフードeco',
		'classo-spec-sink.jpg'        => 'スクエアすべり台シンク クリスタル',
		'classo-spec-cabinet.jpg'     => '2段引き出しキャビネット',
		'classo-spec-faucet.jpg'      => 'タッチレス水ほうき水栓LF',
		'classo-spec-rack.jpg'        => 'アイレベルラック W900',
		'classo-spec-panel.jpg'       => 'キッチンパネル',
		'classo-spec-jokin.jpg'       => 'タッチレス「きれい除菌水」生成器',
		'classo-spec-led.jpg'         => 'LEDスリムライト',
		'classo-point-crystal1.jpg'   => '透明感のあるクリスタルカウンター',
		'classo-point-crystal2.jpg'   => 'カウンター端部のクリアエッジ仕上げ',
		'classo-point-crystal3.jpg'   => '磨いてキレイ・熱に強い・衝撃に強いカウンター',
		'classo-point-faucet1.jpg'    => '幅広シャワーでお皿を洗っているところ',
		'classo-point-faucet2.jpg'    => '大きな鍋を洗っているところ',
		'classo-point-jokin1.jpg'     => 'センサーに手をかざすと除菌水が出るところ',
		'classo-point-jokin2.jpg'     => '網かご・まな板・布巾を除菌水できれいに',
		'classo-opt-dish.jpg'         => 'W450食器洗い乾燥機',
		'classo-opt-pattern.jpg'      => 'クリスタルカウンター柄入り天板カラー',
		'classo-opt-faucet.jpg'       => '水ほうき水栓LFクロムメッキ＋きれい除菌水生成器',
		'classo-opt-basket.jpg'       => '水切りバスケット',

		/* --- リシェル（SSSグレード・LIXIL） --- */
		'richelle-main.jpg'                => 'リシェルのキッチン全体',
		'richelle-top-carbon.jpg'          => '天板 ラパートカーボン',
		'richelle-top-taupe.jpg'           => '天板 ラパートトープ',
		'richelle-top-silk.jpg'            => '天板 ラパートシルク',
		'richelle-top-glazegray.jpg'       => '天板 グレーズグレー',
		'richelle-top-basaltblack.jpg'     => '天板 バサルトブラック',
		'richelle-top-calacattawhite.jpg'  => '天板 カラカッタホワイト',
		'richelle-color-blackstucco.jpg'   => '扉カラー ブラックスタッコ',
		'richelle-color-greigestucco.jpg'  => '扉カラー グレージュスタッコ',
		'richelle-color-whitestucco.jpg'   => '扉カラー ホワイトスタッコ',
		'richelle-color-airywhite.jpg'     => '扉カラー エアリィホワイト',
		'richelle-color-criedark.jpg'      => '扉カラー クリエダーク',
		'richelle-color-criemocha.jpg'     => '扉カラー クリエモカ',
		'richelle-color-crieivory.jpg'     => '扉カラー クリエアイボリー',
		'richelle-color-plainwalnut.jpg'   => '扉カラー プレーンウォルナット',
		'richelle-color-rusticash.jpg'     => '扉カラー ラスティックアッシュ',
		'richelle-color-rusticoak.jpg'     => '扉カラー ラスティックオーク',
		'richelle-sink-cosmicgray.jpg'     => 'シンク コズミックグレー',
		'richelle-sink-taupebeige.jpg'     => 'シンク トープベージュ',
		'richelle-sink-shellwhite.jpg'     => 'シンク シェルホワイト',
		'richelle-spec-top.jpg'            => 'セラミックトップ',
		'richelle-spec-sink.jpg'           => 'ハイブリットクォーツシンク',
		'richelle-spec-led.jpg'            => 'LED照明（クイックポケット一体型）',
		'richelle-spec-hood.jpg'           => 'シロッコファンレンジフード',
		'richelle-spec-rakupat.jpg'        => 'らくパット収納',
		'richelle-spec-rail.jpg'           => 'ソフトモーションレール',
		'richelle-spec-bottom.jpg'         => 'ステンレス引出し底板',
		'richelle-spec-pocket.jpg'         => 'クイックポケット',
		'richelle-spec-pallet.jpg'         => 'クイックパレット',
		'richelle-spec-faucet.jpg'         => 'ハンズフリー水栓H7エコハンドル',
		'richelle-point-ceramic1.jpg'      => 'セラミックトップに高温の鍋を直接置いたところ',
		'richelle-point-ceramic2.jpg'      => 'セラミックトップを金属のフォークでこすったところ',
		'richelle-point-ceramic3.jpg'      => 'セラミックトップにこぼれた油汚れ',
		'richelle-point-ceramic4.jpg'      => '缶が倒れても割れにくい強化構造',
		'richelle-point-raku1.jpg'         => '斜めに傾く扉のらくパット収納',
		'richelle-point-raku2.jpg'         => '引き出しを大きく開けずに取り出せるパッとシェルフ',
		'richelle-point-quartz1.jpg'       => 'ハイブリットクォーツシンク',
		'richelle-point-quartz2.jpg'       => 'ナイアガラフロー方式で水が段差へ流れるところ',
		'richelle-opt-dish.jpg'            => 'W450mmプルオープン食器洗い乾燥機',
		'richelle-opt-downwall.jpg'        => 'オートダウンウォール',
		'richelle-opt-hood.jpg'            => 'よごれんフード',
		'richelle-opt-water.jpg'           => 'ビルトイン型浄水器 専用水栓ナビッシュ',

		/* --- セントロ（プレミアム・クリナップ） --- */
		'centro-main.jpg'              => 'セントロのキッチン全体',
		'centro-top-albanium.jpg'      => '天板 アルバニウム',
		'centro-top-creta.jpg'         => '天板 クレタ',
		'centro-top-sirius.jpg'        => '天板 シリウス',
		'centro-top-edra.jpg'          => '天板 エドラ',
		'centro-color-white.jpg'        => '扉カラー ホワイト',
		'centro-color-charcoal.jpg'     => '扉カラー チャコール',
		'centro-color-silver.jpg'       => '扉カラー シルバー',
		'centro-color-midnightgray.jpg' => '扉カラー ミッドナイトグレー',
		'centro-color-ash.jpg'          => '扉カラー アッシュ',
		'centro-color-oak.jpg'          => '扉カラー オーク',
		'centro-color-cherry.jpg'       => '扉カラー チェリー',
		'centro-color-walnut.jpg'       => '扉カラー ウォールナット',
		'centro-color-rocagreige.jpg'   => '扉カラー ロカグレージュ',
		'centro-color-rocacharcoal.jpg' => '扉カラー ロカチャコール',
		'centro-handle-longbar-silver.jpg' => 'ロングバー取手 シャンパンシルバー',
		'centro-handle-longbar-black.jpg'  => 'ロングバー取手 ブラック',
		'centro-handle-line-silver.jpg'    => 'ライン取手 シルバー',
		'centro-handle-line-black.jpg'     => 'ライン取手 ブラック',
		'centro-handle-bar-gold.jpg'       => 'バー取手 オクトゴールド',
		'centro-handle-bar-bronze.jpg'     => 'バー取手 コッピングブロンズ',
		'centro-spec-ih.jpg'           => 'IHクッキングヒーター',
		'centro-spec-top.jpg'          => 'セラミックワークトップ',
		'centro-spec-hood.jpg'         => 'とってもクリンレンジフード',
		'centro-spec-sink.jpg'         => 'ステンレスシンク',
		'centro-spec-sinkcab.jpg'      => 'シンクキャビネット（ツールコンテナ付き）',
		'centro-spec-conrocab.jpg'     => 'コンロキャビネット（ツールコンテナ付き）',
		'centro-spec-wallcab.jpg'      => 'ハンドムーブ吊戸棚',
		'centro-spec-drain.jpg'        => '収納水切りタイプ（照明付き）',
		'centro-spec-panel.jpg'        => 'キッチンパネル',
		'centro-spec-faucet.jpg'       => 'タッチレス水栓',
		'centro-point-eco1.jpg'        => '骨組みまでステンレスのエコキャビネット',
		'centro-point-ceramic1.jpg'    => 'セラミックワークトップでの調理風景',
		'centro-point-ceramic2.jpg'    => 'セラミックワークトップの端部',
		'centro-point-sink1.jpg'       => '水にのって排水口へ流れる流レールシンク',
		'centro-point-sink2.jpg'       => '継ぎ目の無い排水口',
		'centro-point-hand1.jpg'       => 'ハンドムーブ吊戸棚の調味料タイプ',
		'centro-point-hand2.jpg'       => 'ハンドムーブ吊戸棚の水切りタイプ',
		'centro-opt-dish.jpg'          => 'W450mmプルオープン食器洗い乾燥機',
		'centro-opt-fortex.jpg'        => '流レールシンク（フォルテックス）',
		'centro-opt-trap.jpg'          => 'かってにクリントラップの仕組み図',
		'centro-opt-araeru.jpg'        => '洗エールレンジフード',
	);
	foreach ( $stock as $f => $alt ) {
		if ( $img( $f, $alt ) ) $log[] = 'メディアに追加: ' . $f;
	}

	/* 元ファイルを作り直したときは、メディア側も差し替えます。
	   （メディアは uploads/ に別のコピーを持つので、フォルダを更新しただけでは変わりません）
	   対象は「このしくみで取り込んだ写真すべて」。V-styleの写真も含みます。 */
	$imported = get_posts( array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_key'       => '_ymkrf_import',
	) );
	foreach ( $imported as $id ) {
		$f = get_post_meta( $id, '_ymkrf_import', true );
		if ( ! $f ) continue;
		$src = '';
		foreach ( $dirs as $d ) {
			if ( file_exists( $d . '/' . $f ) ) { $src = $d . '/' . $f; break; }
		}
		$dst = get_attached_file( $id );
		if ( ! $src || ! $dst || ! file_exists( $dst ) ) continue;
		if ( md5_file( $src ) === md5_file( $dst ) ) continue;   // 中身が同じなら何もしない
		if ( ! copy( $src, $dst ) ) continue;
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $dst ) );
		$log[] = '写真を差し替え: ' . $f;
	}

	/* ------------------------------------------------------------
	   2-c. サムネイルが無い写真に、あらためて作らせる
	        PHPの画像処理機能（GD）が入っていない時期に取り込んだぶんの手当てです。
	        GDを有効にしてApacheを再起動したあと、1回だけ動きます。
	   ------------------------------------------------------------ */
	if ( function_exists( 'imagecreatetruecolor' ) || class_exists( 'Imagick' ) ) {
		$atts = get_posts( array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );
		$fixed = 0;
		foreach ( $atts as $aid ) {
			$meta = wp_get_attachment_metadata( $aid );
			if ( ! empty( $meta['sizes'] ) ) continue;   // すでにある
			$file = get_attached_file( $aid );
			if ( ! $file || ! file_exists( $file ) ) continue;
			$new = wp_generate_attachment_metadata( $aid, $file );
			if ( $new && ! empty( $new['sizes'] ) ) {
				wp_update_attachment_metadata( $aid, $new );
				$fixed++;
			}
		}
		if ( $fixed ) $log[] = "サムネイルを作り直した写真：{$fixed}点";
	}

	/* ------------------------------------------------------------
	   2-d. 写真が入らないまま登録された商品を、作り直せるようにする
	        取り込みが途中で止まると、商品は出来ているのに写真だけ
	        空っぽという状態になり、次に動かしても「もう有る」と
	        判断されて直りませんでした。その手当てです。
	        写真がそろっている商品には、いっさい触りません。

	        登録するときに「見つからなかった写真の枚数」を
	        _ymkrf_img_missing に控えてあるので、それを見ます。
	        （ステディアのように、文章だけで写真の無い行を持つ商品を
	          「壊れている」と誤判定しないためです）
	        この控えが無い＝古い版で登録された商品は、
	        これまでどおり「空っぽの行があるか」で見分けます。
	   ------------------------------------------------------------ */
	$rebuild_keys = array( '_ymkrf_images', '_ymkrf_colors', '_ymkrf_tops', '_ymkrf_sinks', '_ymkrf_c4', '_ymkrf_c5', '_ymkrf_c6', '_ymkrf_handles', '_ymkrf_specs', '_ymkrf_speclist', '_ymkrf_features', '_ymkrf_options' );

	foreach ( array(
		/* キッチン */
		'rakuera', 'refit', 'sierra-s', 's-class', 'stedia', 'edel', 'classo', 'richelle', 'centro',
		/* ユニットバス */
		'ofuroa', 'sazana-n', 'lidea-m', 'rakuvia', 'sazana-t', 'lidea-b', 'granspa', 'selevia', 'sinla',
		/* トイレ */
		'amage-z', 'amage-z-aqua', 'purerest-qr', 'alauno-vs5', 'amage-z-premium',
		'pleas-ls', 'alauno-s160', 'alauno-s160-counter', 'gga3', 'gga1-counter',
		'satis-s', 'satis-s-counter', 'neorest-rs3', 'neorest-rs3-counter',
		/* 洗面化粧台 */
		'v1', 'd7', 'bga', 'rakutowa', 'j1', 'rejust', 'k1',
	) as $slug ) {

		$p = get_page_by_path( $slug, OBJECT, 'ymkrf_product' );
		if ( ! $p ) continue;

		$broken = false;

		/* アイキャッチが空なだけでは作り直しません。
		   （作り直すと商品が二重にできることがあったためです。
		     アイキャッチは 3-j と 3-y で入れ直します） */

		$note = get_post_meta( $p->ID, '_ymkrf_img_missing', true );

		if ( ! $broken && $note !== '' ) {
			/* 新しい版で登録された商品：控えた枚数だけを見ます */
			$broken = ( (int) $note > 0 );

		} elseif ( ! $broken ) {
			/* 古い版で登録された商品：空っぽの行があるかで見分けます */
			foreach ( $rebuild_keys as $k ) {
				$rows = get_post_meta( $p->ID, $k, true );
				if ( ! is_array( $rows ) ) continue;
				foreach ( $rows as $r ) {
					if ( is_array( $r ) && array_key_exists( 'img', $r ) && ! (int) $r['img'] ) {
						$broken = true;
						break 2;
					}
				}
			}
		}

		if ( $broken ) {
			wp_delete_post( $p->ID, true );
			$log[] = "写真が入っていなかったので「{$slug}」を登録し直しました";
		}
	}

	/* ------------------------------------------------------------
	   2-z. 同じ商品が2つできてしまったときの掃除

	        アイキャッチが空だと商品を作り直す処理が入っていたため、
	        ザ・クラッソとリデアMが二重に登録されてしまいました。
	        同じ商品名のものが2つ以上あったら、1つだけ残します。
	        （残すのは、写真が入っているほう。両方入っていれば先にできたほう）
	   ------------------------------------------------------------ */
	$dups = get_posts( array(
		'post_type'      => 'ymkrf_product',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'orderby'        => 'ID',
		'order'          => 'ASC',
	) );
	$seen_titles = array();
	foreach ( $dups as $dp ) {
		$key = $dp->post_title;
		if ( ! isset( $seen_titles[ $key ] ) ) { $seen_titles[ $key ] = $dp; continue; }

		$keep = $seen_titles[ $key ];
		$drop = $dp;

		$kt = (int) get_post_thumbnail_id( $keep->ID );
		$dt = (int) get_post_thumbnail_id( $drop->ID );
		$kok = ( $kt && get_attached_file( $kt ) );
		$dok = ( $dt && get_attached_file( $dt ) );
		if ( ! $kok && $dok ) { $tmp = $keep; $keep = $drop; $drop = $tmp; }

		wp_delete_post( $drop->ID, true );
		$seen_titles[ $key ] = $keep;
		$log[] = '重複していた商品「' . $key . '」を1つに整理しました';

		/* URLのうしろに付いた -2 などを外して、元のURLに戻します */
		if ( preg_match( '/^(.+)-\d+$/', $keep->post_name, $m2 )
			&& ! get_page_by_path( $m2[1], OBJECT, 'ymkrf_product' ) ) {
			wp_update_post( array( 'ID' => $keep->ID, 'post_name' => $m2[1] ) );
			$keep->post_name = $m2[1];
			$log[] = '「' . $key . '」のURLを ' . $m2[1] . ' に戻しました';
		}
	}


	/* ------------------------------------------------------------
	   3. V-style を1件つくる
	   ------------------------------------------------------------ */
	$exists = get_page_by_path( 'v-style', OBJECT, 'ymkrf_product' );

	if ( ! $exists && is_dir( $dir ) ) {

		$post_id = wp_insert_post( array(
			'post_type'   => 'ymkrf_product',
			'post_status' => 'publish',
			'post_title'  => 'V-style（Vスタイル）',
			'post_name'   => 'v-style',
		) );

		if ( $post_id && ! is_wp_error( $post_id ) ) {

			$m0 = $missing;   /* この商品で見つからなかった写真を数えるための起点 */

			/* --- 単一の欄 --- */
			$fields = array(
				'_ymkrf_catch'   => 'キレイと快適が毎日つづく快適キッチン！',
				'_ymkrf_grade'   => 'Fグレード',
				'_ymkrf_name'    => 'V-style（Vスタイル）',
				'_ymkrf_size'    => 'I型2550サイズ',
				'_ymkrf_work'    => '240000',
				'_ymkrf_item'    => '358000',
				'_ymkrf_days'    => '3',
				'_ymkrf_pt1'     => 'お手頃価格',
				'_ymkrf_pt2'     => '収納抜群',
				'_ymkrf_pt3'     => 'おそうじ楽々',
				'_ymkrf_caution' => '※写真はイメージです。レンジフードなどが本プランと異なる場合があります。',
			);
			foreach ( $fields as $k => $v ) update_post_meta( $post_id, $k, $v );
			update_post_meta( $post_id, '_ymkrf_total', 598000 );

			/* --- アイキャッチ（いちばん大きく出る商品写真） --- */
			$main = $img( 'main.jpg', 'Panasonic V-style（Vスタイル） I型2550サイズ（ホワイト扉・深型レンジフード）' );
			if ( $main ) set_post_thumbnail( $post_id, $main );

			/* --- 組み合わせイメージ写真 --- */
			$images = array(
				array( 'cabinet-1.jpg', '木目調の扉に、黒いハンドル取手を合わせた例' ),
				array( 'cabinet-2.jpg', 'ダークウッド調の扉に、黒いハンドル取手を合わせた例' ),
				array( 'cabinet-3.jpg', '黒い石目調の扉に、シルバーのハンドル取手を合わせた例' ),
				array( 'cabinet-4.jpg', 'ホワイトの扉に、アルミライン取手を合わせた例' ),
			);
			$rows = array();
			foreach ( $images as $r ) $rows[] = array( 'img' => $img( $r[0], $r[1] ), 'alt' => $r[1] );
			update_post_meta( $post_id, '_ymkrf_images', $rows );

			/* --- 扉カラー --- */
			$colors = array(
				array( 'color-white.jpg',  'ホワイト' ),
				array( 'color-beige.jpg',  'ポタリーベージュ' ),
				array( 'color-brown.jpg',  'テコラッタブラウン' ),
				array( 'color-green.jpg',  'グレイッシュグリーン' ),
			);
			$rows = array();
			foreach ( $colors as $r ) $rows[] = array( 'img' => $img( $r[0], '扉カラー ' . $r[1] ), 'name' => $r[1] );
			update_post_meta( $post_id, '_ymkrf_colors', $rows );

			/* --- 取っ手 --- */
			$handles = array(
				array( 'handle-han.jpg', 'ハンドル取手',   'HAN' ),
				array( 'handle-hda.jpg', 'ハンドル取手',   'HDA' ),
				array( 'handle-lca.jpg', 'アルミライン取手', 'LCA' ),
				array( 'handle-hce.jpg', 'ハンドル取手',   'HCE' ),
				array( 'handle-hde.jpg', 'ハンドル取手',   'HDE' ),
			);
			$rows = array();
			foreach ( $handles as $r ) {
				$rows[] = array( 'img' => $img( $r[0], $r[1] . ' ' . $r[2] ), 'name' => $r[1], 'code' => $r[2] );
			}
			update_post_meta( $post_id, '_ymkrf_handles', $rows );

			/* --- 標準仕様 --- */
			$specs = array(
				array( 'spec-conro.jpg',    'ホーロー3口トップコンロ', 'LEEG32T1V' ),
				array( 'spec-top.jpg',      'ステンレス天板',         'エンボス柄' ),
				array( 'spec-hood.jpg',     '深型レンジフード',       'シロッコ' ),
				array( 'spec-sink.jpg',     'ステンレスシンク',       'Gシンク76' ),
				array( 'spec-sinkcab.jpg',  'シンクキャビネット',     '' ),
				array( 'spec-faucet.jpg',   'シングルレバー水栓',     'LE02FPBNA' ),
				array( 'spec-cookcab.jpg',  '調理キャビネット',       '' ),
				array( 'lock.jpg',          'ウォールキャビネット',   '耐震ロック機構付き' ),
				array( 'spec-conrocab.jpg', 'コンロキャビネット',     '' ),
				array( 'spec-panel.jpg',    'キッチンパネル',         'キッチン正面・コンロ側側面' ),
			);
			$rows = array();
			foreach ( $specs as $r ) {
				$rows[] = array( 'img' => $img( $r[0], $r[1] ), 'name' => $r[1], 'model' => $r[2] );
			}
			update_post_meta( $post_id, '_ymkrf_specs', $rows );

			/* --- おすすめポイント（グループ見出しが空の行は上の行と同じまとまり） --- */
			$features = array(
				array(
					'gsub' => '収納力抜群',
					'gttl' => '「フロアストッカー」',
					'ttl'  => '大割のスライド収納',
					'text' => '',
					'note' => '',
					'img'  => $img( 'point-stocker.jpg', 'フロアストッカーの収納例' ),
					'img2' => $img( 'point-drawer.jpg',  '大割のスライド収納を開けたところ' ),
				),
				array(
					'gsub' => '',
					'gttl' => '',
					'ttl'  => '内引出し付き調理キャビネット',
					'text' => '調理キャビネットには、キッチンツールなど小物の収納が可能な内引出し付き。',
					'note' => '',
					'img'  => $img( 'point-inner.jpg', '調理キャビネットの内引出し' ),
					'img2' => '',
				),
				array(
					'gsub' => '快適性がアップ',
					'gttl' => '「静音ステンレスシンク」',
					'ttl'  => '水ハネ音などを抑えられます',
					'text' => 'ステンレスタイプのシンクは静音仕様。シンクの裏に貼ったシートが、お皿の当たる音やシャワーの水ハネ音などを抑えてくれます。',
					'note' => '',
					'img'  => $img( 'point-sink.jpg',    '静音ステンレスシンク' ),
					'img2' => $img( 'point-sinkfig.jpg', 'シンク裏の制振材の構造図' ),
				),
				array(
					'gsub' => '万が一の地震にも安心。',
					'gttl' => '「耐震ロック機構付き吊戸棚」',
					'ttl'  => '揺れを感じると扉をロック',
					'text' => 'ユニットが振動すると自動的にロックされ、収納物の飛び出しを防ぎます。',
					'note' => '※地質、建物の構造、階数などにより、性能を十分に発揮できない場合があります。',
					'img'  => $img( 'lock.jpg',       '吊戸棚の内側に付いている耐震ロックの金具' ),
					'img2' => $img( 'point-lock.jpg', '耐震ロックなしの場合は揺れで扉が開いて食器が落ちるが、耐震ロックありなら扉が閉じたまま保たれる比較イラスト' ),
				),
			);
			update_post_meta( $post_id, '_ymkrf_features', $features );

			/* --- おすすめオプション --- */
			$options = array(
				array( 'opt-dish.jpg',   'W450mmプルオープン 食器洗い乾燥機',
					'手洗いより節水で省エネ。家事の手間を省きます。（LES45HD9S）', '176000', '※工事費込み' ),
				array( 'opt-faucet.jpg', 'ハンドシャワー水栓 エコカチット（LE04FPSNE）',
					'ホースを引き出せるので、シンクまわりの清掃にも便利。', '42000', '' ),
				array( 'opt-hood.jpg',   'ほっとくリーンフード（LES16BHWZ2M）',
					'全自動お掃除ファン付き。ファンのお手入れは、10年に一度でOK。', '197000', '' ),
				array( 'opt-rail.jpg',   'ソフトクローズ機構スライドレール',
					'引出しが静かにゆっくり閉まるので、中の収納物も安心！', '23000', '' ),
			);
			$rows = array();
			foreach ( $options as $r ) {
				$rows[] = array(
					'img'   => $img( $r[0], $r[1] ),
					'name'  => $r[1],
					'text'  => $r[2],
					'price' => $r[3],
					'note'  => $r[4],
				);
			}
			update_post_meta( $post_id, '_ymkrf_options', $rows );

			/* --- ヤマキシ標準工事内容 --- */
			$works = ymkrf_kitchen_works();
			$rows = array();
			foreach ( $works as $r ) $rows[] = array( 'name' => $r[0], 'text' => $r[1] );
			update_post_meta( $post_id, '_ymkrf_works', $rows );

			/* --- 分類を割り当て --- */
			wp_set_object_terms( $post_id, 'kitchen',   'ymkrf_product_cat' );
			wp_set_object_terms( $post_id, 'panasonic', 'ymkrf_maker' );
			wp_set_object_terms( $post_id,
				array( 'nonoichi', 'komathu', 'hakui', 'shinkaga', 'tazuruhama', 'kanadu' ),
				'ymkrf_shop' );

			update_post_meta( $post_id, '_ymkrf_img_missing', $missing - $m0 );
			$log[] = '商品「V-style（Vスタイル）」を登録しました → ' . get_permalink( $post_id );
		}
	}

	/* ------------------------------------------------------------
	   3-a. ラクエラ（Eグレード・クリナップ）を1件つくる
	   ------------------------------------------------------------ */
	if ( ! get_page_by_path( 'rakuera', OBJECT, 'ymkrf_product' ) ) {

		$rid = wp_insert_post( array(
			'post_type'   => 'ymkrf_product',
			'post_status' => 'publish',
			'post_title'  => 'ラクエラ',
			'post_name'   => 'rakuera',
		) );

		if ( $rid && ! is_wp_error( $rid ) ) {

			$m0 = $missing;   /* この商品で見つからなかった写真を数えるための起点 */

			$f = array(
				'_ymkrf_catch'   => 'シンプルなデザインが特徴のスタイリッシュキッチン。',
				'_ymkrf_grade'   => 'Eグレード',
				'_ymkrf_name'    => 'ラクエラ',
				'_ymkrf_size'    => 'I型2550サイズ',
				'_ymkrf_work'    => '240000',
				'_ymkrf_item'    => '458000',
				'_ymkrf_days'    => '3',
				'_ymkrf_pt1'     => '足元収納',
				'_ymkrf_pt2'     => 'シンク前収納',
				'_ymkrf_pt3'     => '節水省エネ',
				'_ymkrf_caution' => '※写真はイメージになります。',
			);
			foreach ( $f as $k => $v ) update_post_meta( $rid, $k, $v );
			update_post_meta( $rid, '_ymkrf_total', 698000 );

			$m = $img( 'raku-main.jpg' );
			if ( $m ) set_post_thumbnail( $rid, $m );

			/* 組み合わせイメージ（1枚） */
			update_post_meta( $rid, '_ymkrf_images', array(
				array( 'img' => $img( 'raku-cabinet.jpg' ), 'alt' => '扉カラー4色の面材を重ねて並べたところ' ),
			) );

			/* 扉カラー5色（施工イメージ＋色見本の合成） */
			$rc = array(
				array( 'raku-color-white-set.jpg',        'トーンホワイト' ),
				array( 'raku-color-charcoal-set.jpg',     'トーンチャコール' ),
				array( 'raku-color-palewood-set.jpg',     'ペールウッド' ),
				array( 'raku-color-mokawood-set.jpg',     'モカウッド' ),
				array( 'raku-color-charcoalwood-set.jpg', 'チャコールウッド' ),
			);
			$rows = array();
			foreach ( $rc as $r ) $rows[] = array( 'img' => $img( $r[0] ), 'name' => $r[1] );
			update_post_meta( $rid, '_ymkrf_colors', $rows );

			/* 取っ手1種 */
			update_post_meta( $rid, '_ymkrf_handles', array(
				array( 'img' => $img( 'raku-handle-bar.jpg' ), 'name' => 'バー取手（シルバー）', 'code' => 'L' ),
			) );

			/* 標準仕様9点 */
			$rs = array(
				array( 'raku-spec-conro.jpg',   'ホーロー3口トップコンロ',     'ZGFNK6R18NKE-E' ),
				array( 'raku-spec-top.jpg',     'ステンレス天板',             'ドット柄コイニング加工' ),
				array( 'raku-spec-hood.jpg',    'フラットスリムレンジフード', 'ZRS75ABZ21FS(R/L)-E・幕板扉柄' ),
				array( 'raku-spec-sink.jpg',    'ステンレスTUシンク',         '' ),
				array( 'raku-spec-rail.jpg',    'サイレントレール',           '' ),
				array( 'raku-spec-faucet.jpg',  'シングルレバー水栓',         'ZZKM5111TCLE' ),
				array( 'raku-spec-wallcab.jpg', 'ミドル吊戸棚',               '' ),
				array( 'raku-spec-panel.jpg',   'キッチンパネル',             'キッチン正面・コンロ側側面' ),
				array( 'raku-spec-floor.jpg',   '床板メラミン化粧板',         '' ),
			);
			$rows = array();
			foreach ( $rs as $r ) $rows[] = array( 'img' => $img( $r[0], $r[1] ), 'name' => $r[1], 'model' => $r[2] );
			update_post_meta( $rid, '_ymkrf_specs', $rows );

			/* おすすめポイント（4グループ・7ポイント） */
			update_post_meta( $rid, '_ymkrf_features', array(
				array( 'gsub'=>'収納スペースを効率的に確保', 'gttl'=>'「フットエリア収納」',
				       'ttl'=>'ストックの収納に',
				       'text'=>'食品を扱う場所には、もっともふさわしい素材。', 'note'=>'',
				       'img'=>$img('raku-point-foot1.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'美しく揃う、蹴込みレスデザイン',
				       'text'=>'開き扉タイプのキッチンによく見られる「蹴込み仕様」ではないから、足元までスッキリ。', 'note'=>'',
				       'img'=>$img('raku-point-foot2.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'使いやすさがアップ', 'gttl'=>'「ハンドエリア収納」',
				       'ttl'=>'よく使うものはハンドエリアへ',
				       'text'=>'毎日の料理に欠かせない、調味料・鍋の収納に便利。', 'note'=>'',
				       'img'=>$img('raku-point-hand1.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'市販のアイテムを取り入れる自由度がアップ',
				       'text'=>'オプションの「マグネフリーパネル」を取り付けることで、市販のマグネット収納アイテムを取り付けることができます。', 'note'=>'',
				       'img'=>$img('raku-point-hand2.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'やわらかな質感が魅力', 'gttl'=>'「フラットスリムレンジフード」',
				       'ttl'=>'スリムですっきりデザイン',
				       'text'=>'フラットな内面形状でお手入れが簡単です。', 'note'=>'',
				       'img'=>$img('raku-point-hood1.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'お手入れしやすい内面形状',
				       'text'=>'継ぎ目や凹凸の少ない内面形状に加え、ファンの入り口付近もお手入れしやすい形状です。さらに油汚れをはじきやすい「はつ油塗装」を施しました。', 'note'=>'',
				       'img'=>$img('raku-point-hood2.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'丈夫で扱いやすい', 'gttl'=>'「ステンレス天板＋シンク」',
				       'ttl'=>'傷が目立ちにくい',
				       'text'=>'傷が目立ちにくいコイニング加工の天板。', 'note'=>'',
				       'img'=>$img('raku-point-top1.jpg'), 'img2'=>'' ),
			) );

			/* おすすめオプション3点 */
			update_post_meta( $rid, '_ymkrf_options', array(
				array( 'img'=>$img('raku-opt-dish.jpg'),    'name'=>'W450mmプルオープン 食器洗い乾燥機',
				       'text'=>'手洗いより節水で省エネ。家事の手間を省きます。（ZWPP45R21LDS-E）',
				       'price'=>'132000', 'note'=>'※工事費込み' ),
				array( 'img'=>$img('raku-opt-wallcab.jpg'), 'name'=>'ハンドムーブ吊戸棚（水切りタイプ照明付）W900/H700',
				       'text'=>'洗い物などをしまえるので、流し台がすっきり片付きます。',
				       'price'=>'92000', 'note'=>'' ),
				array( 'img'=>$img('raku-opt-hood.jpg'),    'name'=>'洗エールレンジフード（W750/H700扉面材）',
				       'text'=>'面倒なフィルターのお掃除は、レンジフードが自動洗浄。ボタンひとつで済みます。',
				       'price'=>'187000', 'note'=>'' ),
			) );

			/* ヤマキシ標準工事内容（キッチン共通） */
			$rw = array(
				array( '撤去工事',               '古いキッチンの撤去にかかる工事です。' ),
				array( '廃棄処分',               '撤去した古いキッチンを廃棄処分するためにかかる費用です。' ),
				array( 'ガス配管変更工事',       'ガスコンロを使うための配管工事です。' ),
				array( 'キッチンパネル設置工事', 'キッチンパネルの取り付け工事費です。' ),
				array( 'キッチンパネル部材費',   'キッチンパネル自体の部材費です。' ),
				array( '下地工事（大工工事）',   'キッチンパネル設置面の補修、補強の工事です。' ),
				array( 'シロッコファン取付工事', 'シロッコファンの取り付け工事費です。' ),
			);
			$rows = array();
			foreach ( $rw as $r ) $rows[] = array( 'name' => $r[0], 'text' => $r[1] );
			update_post_meta( $rid, '_ymkrf_works', $rows );

			wp_set_object_terms( $rid, 'kitchen', 'ymkrf_product_cat' );
			wp_set_object_terms( $rid, 'cleanup', 'ymkrf_maker' );
			wp_set_object_terms( $rid, array( 'komathu', 'kanadu' ), 'ymkrf_shop' );

			update_post_meta( $rid, '_ymkrf_img_missing', $missing - $m0 );
			$log[] = '商品「ラクエラ」を登録しました → ' . get_permalink( $rid );
		}
	}

	/* ------------------------------------------------------------
	   3-a2. リフィット（Dグレード・タカラスタンダード）を1件つくる
	   ------------------------------------------------------------ */
	if ( ! get_page_by_path( 'refit', OBJECT, 'ymkrf_product' ) ) {

		$fid = wp_insert_post( array(
			'post_type'   => 'ymkrf_product',
			'post_status' => 'publish',
			'post_title'  => 'リフィット',
			'post_name'   => 'refit',
		) );

		if ( $fid && ! is_wp_error( $fid ) ) {

			$m0 = $missing;   /* この商品で見つからなかった写真を数えるための起点 */

			$f = array(
				'_ymkrf_catch'   => '毎日の家事を楽しく、自分らしく彩る台所',
				'_ymkrf_grade'   => 'Dグレード',
				'_ymkrf_name'    => 'リフィット',
				'_ymkrf_size'    => 'I型2550サイズ',
				'_ymkrf_work'    => '240000',
				'_ymkrf_item'    => '558000',
				'_ymkrf_days'    => '3',
				'_ymkrf_pt1'     => 'お洒落',
				'_ymkrf_pt2'     => 'ホーロー製',
				'_ymkrf_pt3'     => '衛生的',
				'_ymkrf_caution' => '※写真はイメージになります。こちらの商品は2026年4月からの取り扱いとなっております。',
			);
			foreach ( $f as $k => $v ) update_post_meta( $fid, $k, $v );
			update_post_meta( $fid, '_ymkrf_total', 798000 );

			$m = $img( 'refit-main.jpg' );
			if ( $m ) set_post_thumbnail( $fid, $m );

			/* 扉カラー（グループ3・全7色） */
			$fc = array(
				array( 'refit-color-light.jpg',       'ライト' ),
				array( 'refit-color-lightwhite.jpg',  'ライトホワイト' ),
				array( 'refit-color-mediumbrown.jpg', 'ミディアムブラウン' ),
				array( 'refit-color-darkbrown.jpg',   'ダークブラウン' ),
				array( 'refit-color-white.jpg',       'ホワイト' ),
				array( 'refit-color-superwhite.jpg',  'スーパーホワイト' ),
				array( 'refit-color-winered.jpg',     'ワインレッド' ),
			);
			$rows = array();
			foreach ( $fc as $r ) $rows[] = array( 'img' => $img( $r[0] ), 'name' => $r[1] );
			update_post_meta( $fid, '_ymkrf_colors', $rows );

			/* 天板カラー（全3色） */
			$ft = array(
				array( 'refit-top-beige.jpg', 'シャインベージュ' ),
				array( 'refit-top-gray.jpg',  'シャイングレー' ),
				array( 'refit-top-white.jpg', 'シャインホワイト' ),
			);
			$rows = array();
			foreach ( $ft as $r ) $rows[] = array( 'img' => $img( $r[0] ), 'name' => $r[1] );
			update_post_meta( $fid, '_ymkrf_tops', $rows );

			/* 取っ手はカタログに記載が無いので空のまま（見出しごと出ません） */
			update_post_meta( $fid, '_ymkrf_handles', array() );
			update_post_meta( $fid, '_ymkrf_images', array() );

			/* 標準仕様10点 */
			$fs = array(
				array( 'refit-spec-top.jpg',     '人造大理石天板',       '' ),
				array( 'refit-spec-sink.jpg',    'らくエルステンレスシンク', '' ),
				array( 'refit-spec-faucet.jpg',  'シングルレバー水栓',   'KXS871JT' ),
				array( 'refit-spec-cabinet.jpg', '木製キャビネット',     '引出し底板ホーロー' ),
				array( 'refit-spec-rail.jpg',    'ソフトクローズレール', '' ),
				array( 'refit-spec-hood.jpg',    'シロッコファンレンジフード', 'VUA901AD(V)' ),
				array( 'refit-spec-conro.jpg',   'ホーロー3口トップコンロ', 'ZGFNK6R18NKE-E' ),
				array( 'refit-spec-led.jpg',     'LED手元照明',          '' ),
				array( 'refit-spec-foot.jpg',    '足元収納',             'スライドタイプ' ),
				array( 'refit-spec-latch.jpg',   '吊戸棚耐震ラッチ',     '' ),
			);
			$rows = array();
			foreach ( $fs as $r ) $rows[] = array( 'img' => $img( $r[0], $r[1] ), 'name' => $r[1], 'model' => $r[2] );
			update_post_meta( $fid, '_ymkrf_specs', $rows );

			/* おすすめポイント（3グループ・7ポイント） */
			update_post_meta( $fid, '_ymkrf_features', array(
				array( 'gsub'=>'専業メーカーならではの品質と設計、かつ高コストパフォーマンス',
				       'gttl'=>'「おしゃれすっきり台所へジャストにRe-フィット！」',
				       'ttl'=>'使いやすいシンク設計',
				       'text'=>'らくエルシンクは、小物収納を自分好みにカスタマイズ。おおきなフライパンも縦置きできる、うれしいゆったり設計。',
				       'note'=>'', 'img'=>$img('refit-point-sink1.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'ぴったり美しく',
				       'text'=>'リフォームにありがちな微妙な隙間も、1cm刻みでオーダー可能。一軒一軒ジャストフィットで、収まりもよりキレイに！',
				       'note'=>'', 'img'=>$img('refit-point-fit.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'吊戸棚も4種類から',
				       'text'=>'通常2〜3サイズに加え、業界初の高さ40cmもご用意。大きな窓のある現場にも気の利く収納設計です。',
				       'note'=>'', 'img'=>$img('refit-point-wall.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'豊富なオプションバリエーション',
				       'text'=>'お求めやすい価格でも、充実のオプションラインナップをご用意。私だけの台所空間を演出できます。',
				       'note'=>'', 'img'=>$img('refit-point-option.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'まだまだすごい', 'gttl'=>'「らくエルシンク」が衛生的',
				       'ttl'=>'手洗い物も◎',
				       'text'=>'奥に段差を設けて、洗った水の流れが手前に流れてこない設計です。',
				       'note'=>'', 'img'=>$img('refit-point-flow.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'おそうじ◎',
				       'text'=>'シンクと浅型排水口が、なんと一体成型。継ぎ目がありません。',
				       'note'=>'', 'img'=>$img('refit-point-drain.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'「ホーロー整流板」つきレンジフード',
				       'ttl'=>'ホーロー製だから金属たわしもOK',
				       'text'=>'ギトギトの油汚れがこびりついたとしても、キレイにゴシゴシ洗えます！',
				       'note'=>'', 'img'=>$img('refit-point-horo1.jpg'), 'img2'=>$img('refit-point-horo2.jpg') ),
			) );

			/* おすすめオプション4点 */
			update_post_meta( $fid, '_ymkrf_options', array(
				array( 'img'=>$img('refit-opt-dish.jpg'),    'name'=>'W450mmプルオープン 食器洗い乾燥機（EW-45R3ST）',
				       'text'=>'手洗いより節水で省エネ。家事の手間を省きます。', 'price'=>'141800', 'note'=>'' ),
				array( 'img'=>$img('refit-opt-wallcab.jpg'), 'name'=>'電動昇降吊戸棚（W900）',
				       'text'=>'スイッチひとつで、吊戸棚が下りてきます。', 'price'=>'183200', 'note'=>'' ),
				array( 'img'=>$img('refit-opt-hood.jpg'),    'name'=>'ホーロークリーンフード（扉色幕板・W900）',
				       'text'=>'本体・整流板もホーロー製なので、汚れても水拭きでキレイに。', 'price'=>'97000', 'note'=>'' ),
				array( 'img'=>$img('refit-opt-faucet.jpg'),  'name'=>'浄水器内蔵ハンドシャワー水栓（TJS-SP30E）',
				       'text'=>'シャワーヘッドで原水・浄水の切り替えができ、カートリッジ交換も手軽です。', 'price'=>'22500', 'note'=>'' ),
			) );

			/* ヤマキシ標準工事内容（キッチン共通） */
			$fw = array(
				array( '撤去工事',               '古いキッチンの撤去にかかる工事です。' ),
				array( '廃棄処分',               '撤去した古いキッチンを廃棄処分するためにかかる費用です。' ),
				array( 'ガス配管変更工事',       'ガスコンロを使うための配管工事です。' ),
				array( 'キッチンパネル設置工事', 'キッチンパネルの取り付け工事費です。' ),
				array( 'キッチンパネル部材費',   'キッチンパネル自体の部材費です。' ),
				array( '下地工事（大工工事）',   'キッチンパネル設置面の補修、補強の工事です。' ),
				array( 'シロッコファン取付工事', 'シロッコファンの取り付け工事費です。' ),
			);
			$rows = array();
			foreach ( $fw as $r ) $rows[] = array( 'name' => $r[0], 'text' => $r[1] );
			update_post_meta( $fid, '_ymkrf_works', $rows );

			wp_set_object_terms( $fid, 'kitchen', 'ymkrf_product_cat' );
			wp_set_object_terms( $fid, 'takara',  'ymkrf_maker' );

			update_post_meta( $fid, '_ymkrf_img_missing', $missing - $m0 );
			$log[] = '商品「リフィット」を登録しました → ' . get_permalink( $fid );
		}
	}

	/* ------------------------------------------------------------
	   3-a3. シエラS（Cグレード・LIXIL）を1件つくる
	   ------------------------------------------------------------ */
	if ( ! get_page_by_path( 'sierra-s', OBJECT, 'ymkrf_product' ) ) {

		$sid = wp_insert_post( array(
			'post_type'   => 'ymkrf_product',
			'post_status' => 'publish',
			'post_title'  => 'シエラS',
			'post_name'   => 'sierra-s',
		) );

		if ( $sid && ! is_wp_error( $sid ) ) {

			$m0 = $missing;   /* この商品で見つからなかった写真を数えるための起点 */

			$f = array(
				'_ymkrf_catch'   => 'シンプルで使いやすいキッチン',
				'_ymkrf_grade'   => 'Cグレード',
				'_ymkrf_name'    => 'シエラS',
				'_ymkrf_size'    => 'I型2550タイプ',
				'_ymkrf_work'    => '240000',
				'_ymkrf_item'    => '658000',
				'_ymkrf_days'    => '3',
				'_ymkrf_pt1'     => 'スタイリッシュ',
				'_ymkrf_pt2'     => 'エコ',
				'_ymkrf_pt3'     => '収納力',
				'_ymkrf_caution' => '※写真はイメージになります。朝日店はオプション付のみの展示です。',
			);
			foreach ( $f as $k => $v ) update_post_meta( $sid, $k, $v );
			update_post_meta( $sid, '_ymkrf_total', 898000 );

			$m = $img( 'sierra-main.jpg' );
			if ( $m ) set_post_thumbnail( $sid, $m );
			update_post_meta( $sid, '_ymkrf_images', array() );

			/* 扉カラー グループ2（全15色） */
			$sc = array(
				array( 'sierra-color-blackstucco.jpg',     'ブラックスタッコ' ),
				array( 'sierra-color-greigestucco.jpg',    'グレージュスタッコ' ),
				array( 'sierra-color-whitestucco.jpg',     'ホワイトスタッコ' ),
				array( 'sierra-color-greenbronze.jpg',     'グリーンブロンズ' ),
				array( 'sierra-color-sunsetcopper.jpg',    'サンセットカッパー' ),
				array( 'sierra-color-brownbrass.jpg',      'ブラウンブラス' ),
				array( 'sierra-color-deepred.jpg',         'ディープレッド' ),
				array( 'sierra-color-palewhite-gloss.jpg', 'ペールホワイト（光沢）' ),
				array( 'sierra-color-burnedwood.jpg',      'バーンドウッド' ),
				array( 'sierra-color-bleachwood.jpg',      'ブリーチウッド' ),
				array( 'sierra-color-criedark.jpg',        'クリエダーク' ),
				array( 'sierra-color-criemocha.jpg',       'クリエモカ' ),
				array( 'sierra-color-criecherry.jpg',      'クリエチェリー' ),
				array( 'sierra-color-crieoak.jpg',         'クリエオーク' ),
				array( 'sierra-color-palewhite-wood.jpg',  'ペールホワイト（木目）' ),
			);
			$rows = array();
			foreach ( $sc as $r ) $rows[] = array( 'img' => $img( $r[0] ), 'name' => $r[1] );
			update_post_meta( $sid, '_ymkrf_colors', $rows );

			/* 天板（全3色） */
			$st = array(
				array( 'sierra-top-white.jpg', 'ソルティホワイト' ),
				array( 'sierra-top-gray.jpg',  'シルフィーグレー' ),
				array( 'sierra-top-beige.jpg', 'シルフィーベージュ' ),
			);
			$rows = array();
			foreach ( $st as $r ) $rows[] = array( 'img' => $img( $r[0] ), 'name' => $r[1] );
			update_post_meta( $sid, '_ymkrf_tops', $rows );

			/* 取っ手（スリム3色・ミドル3色） */
			$sh = array(
				array( 'sierra-handle-slim-black.jpg',  'スリム取手', 'ブラック' ),
				array( 'sierra-handle-slim-nickel.jpg', 'スリム取手', 'シャインニッケル' ),
				array( 'sierra-handle-slim-silver.jpg', 'スリム取手', 'シルバー' ),
				array( 'sierra-handle-mid-black.jpg',   'ミドル取手', 'ブラック' ),
				array( 'sierra-handle-mid-nickel.jpg',  'ミドル取手', 'シャインニッケル' ),
				array( 'sierra-handle-mid-silver.jpg',  'ミドル取手', 'シルバー' ),
			);
			$rows = array();
			foreach ( $sh as $r ) $rows[] = array( 'img' => $img( $r[0] ), 'name' => $r[1], 'code' => $r[2] );
			update_post_meta( $sid, '_ymkrf_handles', $rows );

			/* 標準仕様10点 */
			$ss = array(
				array( 'sierra-spec-top.jpg',     '人造大理石 天板',        '' ),
				array( 'sierra-spec-sink.jpg',    'スキットシンク',         'ステンレス' ),
				array( 'sierra-spec-faucet.jpg',  'オールインワン浄水栓',   'JFAK461SYXJG5C' ),
				array( 'sierra-spec-hood.jpg',    'シロッコファンレンジフード', 'ASR-734KRT' ),
				array( 'sierra-spec-stocker.jpg', 'スライドストッカー',     '' ),
				array( 'sierra-spec-rail.jpg',    'ソフトモーションレール', '' ),
				array( 'sierra-spec-wallcab.jpg', 'ミドル吊戸棚',           '扉キャッチ機構' ),
				array( 'sierra-spec-light.jpg',   'システムライト',         '' ),
				array( 'sierra-spec-panel.jpg',   'キッチンパネル',         'キッチン正面・コンロ側側面' ),
				array( 'sierra-spec-ih.jpg',      'IHヒーター',             'CS-G321MS' ),
			);
			$rows = array();
			foreach ( $ss as $r ) $rows[] = array( 'img' => $img( $r[0], $r[1] ), 'name' => $r[1], 'model' => $r[2] );
			update_post_meta( $sid, '_ymkrf_specs', $rows );

			/* おすすめポイント（3グループ・7ポイント） */
			update_post_meta( $sid, '_ymkrf_features', array(
				array( 'gsub'=>'水が無駄なく、スムーズに流れる。', 'gttl'=>'「スキットシンク」',
				       'ttl'=>'汚れがスムーズに流れる、ナイアガラフロー式',
				       'text'=>'水の広がりを抑えて、すばやく段差に流し込みます。カルキの原因になる水滴も、段差で受け止めます。',
				       'note'=>'', 'img'=>$img('sierra-point-flow1.jpg'), 'img2'=>$img('sierra-point-flow2.jpg') ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'シンクまわりがキレイに片付く、大きなポケット',
				       'text'=>'', 'note'=>'', 'img'=>$img('sierra-point-pocket.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'スタイリッシュで多彩な機能を搭載', 'gttl'=>'「オールインワン浄水栓」',
				       'ttl'=>'浄水カートリッジをスマートに内蔵',
				       'text'=>'シャワーと整流はダイヤル、浄水と原水はプッシュで切り替え。4段階切替も交換もラクラクです。',
				       'note'=>'', 'img'=>$img('sierra-point-cartridge.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'「ひろびろシャワー」で、洗い物をスピードアップ',
				       'text'=>'', 'note'=>'', 'img'=>$img('sierra-point-shower.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'エコハンドルで、無駄な給湯エネルギーを使いません',
				       'text'=>'', 'note'=>'', 'img'=>$img('sierra-point-eco.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'限られたスペースを有効活用。', 'gttl'=>'「スライドストッカー」',
				       'ttl'=>'足元までしっかり収納',
				       'text'=>'足元のけこみの部分も、収納スペースとして無駄なく使えます。',
				       'note'=>'', 'img'=>$img('sierra-point-stocker1.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'ソフトモーションレール',
				       'text'=>'引出しを滑らかに引き込む機構と、奥行きいっぱいまで引き出せる構造のソフトモーションレール仕様です。',
				       'note'=>'', 'img'=>$img('sierra-point-rail.jpg'), 'img2'=>'' ),
			) );

			/* おすすめオプション4点 */
			update_post_meta( $sid, '_ymkrf_options', array(
				array( 'img'=>$img('sierra-opt-dish.jpg'),   'name'=>'W450mmプルオープン 食器洗い乾燥機（NP-45RS9WJGT）',
				       'text'=>'手洗いより節水で省エネ。家事の手間を省きます。', 'price'=>'124000', 'note'=>'' ),
				array( 'img'=>$img('sierra-opt-faucet.jpg'), 'name'=>'ハンズフリー水栓（SFNAH472SYJG5）',
				       'text'=>'手を使わずに水が出て、シンク作業がはかどります。', 'price'=>'116000', 'note'=>'' ),
				array( 'img'=>$img('sierra-opt-pallet.jpg'), 'name'=>'クイックパレット W900',
				       'text'=>'必要な時だけスッと下ろして使える、水切り構造の仮置き棚。', 'price'=>'29000', 'note'=>'' ),
				array( 'img'=>$img('sierra-opt-sink.jpg'),   'name'=>'キレイシンク（人造大理石シンク）',
				       'text'=>'形・色・拭きやすさ、3つのキレイを備えたシンクです。', 'price'=>'29000', 'note'=>'' ),
			) );

			/* ヤマキシ標準工事内容（キッチン共通） */
			$sw = array(
				array( '撤去工事',               '古いキッチンの撤去にかかる工事です。' ),
				array( '廃棄処分',               '撤去した古いキッチンを廃棄処分するためにかかる費用です。' ),
				array( 'ガス配管変更工事',       'ガスコンロを使うための配管工事です。' ),
				array( 'キッチンパネル設置工事', 'キッチンパネルの取り付け工事費です。' ),
				array( 'キッチンパネル部材費',   'キッチンパネル自体の部材費です。' ),
				array( '下地工事（大工工事）',   'キッチンパネル設置面の補修、補強の工事です。' ),
				array( 'シロッコファン取付工事', 'シロッコファンの取り付け工事費です。' ),
			);
			$rows = array();
			foreach ( $sw as $r ) $rows[] = array( 'name' => $r[0], 'text' => $r[1] );
			update_post_meta( $sid, '_ymkrf_works', $rows );

			wp_set_object_terms( $sid, 'kitchen', 'ymkrf_product_cat' );
			wp_set_object_terms( $sid, 'lixil',   'ymkrf_maker' );
			wp_set_object_terms( $sid, array( 'komathu', 'hakui', 'tazuruhama', 'asahi', 'kanadu' ), 'ymkrf_shop' );

			update_post_meta( $sid, '_ymkrf_img_missing', $missing - $m0 );
			$log[] = '商品「シエラS」を登録しました → ' . get_permalink( $sid );
		}
	}

	/* ------------------------------------------------------------
	   3-a4. Sクラス（Bグレード・Panasonic）を1件つくる
	   ------------------------------------------------------------ */
	if ( ! get_page_by_path( 's-class', OBJECT, 'ymkrf_product' ) ) {

		$pid = wp_insert_post( array(
			'post_type'   => 'ymkrf_product',
			'post_status' => 'publish',
			'post_title'  => 'Sクラス',
			'post_name'   => 's-class',
		) );

		if ( $pid && ! is_wp_error( $pid ) ) {

			$m0 = $missing;   /* この商品で見つからなかった写真を数えるための起点 */

			$f = array(
				'_ymkrf_catch'   => '無意識の快適を追求したキッチン！',
				'_ymkrf_grade'   => 'Bグレード',
				'_ymkrf_name'    => 'Sクラス',
				'_ymkrf_size'    => 'I型2550サイズ',
				'_ymkrf_work'    => '240000',
				'_ymkrf_item'    => '758000',
				'_ymkrf_days'    => '3',
				'_ymkrf_pt1'     => 'ひろびろ',
				'_ymkrf_pt2'     => 'すっきり',
				'_ymkrf_pt3'     => '便利',
				'_ymkrf_caution' => '※写真はイメージになります。一部オプションがはいっています。',
			);
			foreach ( $f as $k => $v ) update_post_meta( $pid, $k, $v );
			update_post_meta( $pid, '_ymkrf_total', 998000 );

			$m = $img( 'sclass-main.jpg' );
			if ( $m ) set_post_thumbnail( $pid, $m );
			update_post_meta( $pid, '_ymkrf_images', array() );

			/* 扉カラー（全14色） */
			$pc = array(
				array( 'sclass-color-walnut.jpg',       'ソフトウォールナット柄' ),
				array( 'sclass-color-teak.jpg',         'ソフトチーク柄' ),
				array( 'sclass-color-chestnut.jpg',     'ナチュラルチェスナット柄' ),
				array( 'sclass-color-whiteash.jpg',     'ホワイトアッシュ柄' ),
				array( 'sclass-color-greyoak.jpg',      'グレーオーク柄' ),
				array( 'sclass-color-vintagemetal.jpg', 'ヴィンテージメタル柄' ),
				array( 'sclass-color-vintagebrown.jpg', 'ヴィンテージブラウン柄' ),
				array( 'sclass-color-scratchmetal.jpg', 'スクラッチメタル柄' ),
				array( 'sclass-color-earthwhite.jpg',   'アースホワイト' ),
				array( 'sclass-color-alberoblack.jpg',  'アルベロブラック' ),
				array( 'sclass-color-alberowhite.jpg',  'アルベロホワイト' ),
				array( 'sclass-color-navy.jpg',         'ネイビー' ),
				array( 'sclass-color-beige.jpg',        'ベージュ' ),
				array( 'sclass-color-beautywhite.jpg',  'ビューティホワイト' ),
			);
			$rows = array();
			foreach ( $pc as $r ) $rows[] = array( 'img' => $img( $r[0] ), 'name' => $r[1] );
			update_post_meta( $pid, '_ymkrf_colors', $rows );

			/* 天板（全1色） */
			update_post_meta( $pid, '_ymkrf_tops', array(
				array( 'img' => $img( 'sclass-top-white.jpg' ), 'name' => 'グラニュールホワイト' ),
			) );

			/* シンク（全3色） */
			update_post_meta( $pid, '_ymkrf_sinks', array(
				array( 'img' => $img( 'sclass-sink-white.jpg' ), 'name' => 'グラニュールホワイト' ),
				array( 'img' => $img( 'sclass-sink-beige.jpg' ), 'name' => 'ミストベージュ' ),
				array( 'img' => $img( 'sclass-sink-gray.jpg' ),  'name' => 'グレー' ),
			) );

			/* 取っ手（全10種） */
			$ph = array(
				array( 'sclass-handle-lca.jpg', 'アルミライン取手',            'LCA' ),
				array( 'sclass-handle-han.jpg', 'ハンドル取手',                'HAN' ),
				array( 'sclass-handle-hcd.jpg', 'ハンドル取手',                'HCD' ),
				array( 'sclass-handle-hda.jpg', 'ハンドル取手',                'HDA' ),
				array( 'sclass-handle-hae.jpg', 'ハンドル取手',                'HAE' ),
				array( 'sclass-handle-hce.jpg', 'ハンドル取手',                'HCE' ),
				array( 'sclass-handle-hde.jpg', 'ハンドル取手',                'HDE' ),
				array( 'sclass-handle-mjd.jpg', 'ハンドル取手＋つまみ取手',    'MJD' ),
				array( 'sclass-handle-mje.jpg', 'ハンドル取手＋つまみ取手',    'MJE' ),
				array( 'sclass-handle-mjg.jpg', 'ハンドル取手＋つまみ取手',    'MJG' ),
			);
			$rows = array();
			foreach ( $ph as $r ) $rows[] = array( 'img' => $img( $r[0] ), 'name' => $r[1], 'code' => $r[2] );
			update_post_meta( $pid, '_ymkrf_handles', $rows );

			/* 標準仕様10点 */
			$ps = array(
				array( 'sclass-spec-ih.jpg',      'IHクッキングヒーター',           'KZ-J1H6AST' ),
				array( 'sclass-spec-top.jpg',     '人造大理石カウンター',           '' ),
				array( 'sclass-spec-hood.jpg',    'スマートフードII',               'さっとれるファン仕様' ),
				array( 'sclass-spec-sink.jpg',    'ムーブラックシンク',             '人造大理石' ),
				array( 'sclass-spec-soft.jpg',    'ソフトクロージング機構',         '' ),
				array( 'sclass-spec-faucet.jpg',  '混合水栓ハンドシャワー',         'エコカチットあり' ),
				array( 'sclass-spec-stocker.jpg', '扉ストッカー',                   '' ),
				array( 'sclass-spec-wallcab.jpg', '吊戸棚',                         '耐震ロック機構' ),
				array( 'sclass-spec-outlet.jpg',  'クッキングコンセント',           'シルバー' ),
				array( 'sclass-spec-panel.jpg',   'キッチンパネル',                 'キッチン正面・コンロ側側面' ),
			);
			$rows = array();
			foreach ( $ps as $r ) $rows[] = array( 'img' => $img( $r[0], $r[1] ), 'name' => $r[1], 'model' => $r[2] );
			update_post_meta( $pid, '_ymkrf_specs', $rows );

			/* おすすめポイント（4グループ・8ポイント） */
			update_post_meta( $pid, '_ymkrf_features', array(
				array( 'gsub'=>'広びろ使える', 'gttl'=>'「ムーブラックシンク」',
				       'ttl'=>'洗剤ラックが自由に動かせ広びろ使えるシンク',
				       'text'=>'', 'note'=>'',
				       'img'=>$img('sclass-point-sink1.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'ラックを縦横自由に置けるので調理スペースが広がります',
				       'text'=>'ヤマキシのクラスSのキッチンパックには、ムーブラックシンク用洗剤ラックとムーブラック用スラくるネットが付属でついています。',
				       'note'=>'',
				       'img'=>$img('sclass-point-sink2.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'付属の「洗剤ラック」と「スラくるネット」',
				       'text'=>'', 'note'=>'',
				       'img'=>$img('sclass-point-rack.jpg'), 'img2'=>$img('sclass-point-net.jpg') ),
				array( 'gsub'=>'家電調理が使いやすい', 'gttl'=>'「クッキングコンセント」',
				       'ttl'=>'手元にコンセント',
				       'text'=>'手元にコンセントがあるから、コードが邪魔にならず、とても便利です。',
				       'note'=>'',
				       'img'=>$img('sclass-point-outlet1.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'便利な小物フック付き',
				       'text'=>'ハンドタオルやミトン、レジ袋などを掛けられます。',
				       'note'=>'',
				       'img'=>$img('sclass-point-outlet2.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'すっきりしたデザイン', 'gttl'=>'「スマートフードII」',
				       'ttl'=>'お手入れがしやすいスタイリッシュデザイン',
				       'text'=>'お手入れがラクなコンパクト清流板。凹凸がなだらかで拭きやすい内フード。リモコンのボタンスイッチ部もスッキリしたデザイン！',
				       'note'=>'',
				       'img'=>$img('sclass-point-hood1.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'収納物も安心', 'gttl'=>'「ソフトクロージング機構」',
				       'ttl'=>'静かに閉まる',
				       'text'=>'閉まりきる6cm手前からダンパーが働き、衝撃で食器同士がぶつかり合うのを抑えながら静かにしまります。',
				       'note'=>'',
				       'img'=>$img('sclass-point-soft1.jpg'), 'img2'=>'' ),
			) );

			/* おすすめオプション4点 */
			update_post_meta( $pid, '_ymkrf_options', array(
				array( 'img'=>$img('sclass-opt-dish.jpg'),   'name'=>'W450mmプルオープン 食器洗い乾燥機',
				       'text'=>'手洗いより節水で省エネ。家事の手間を省きます。',
				       'price'=>'77000', 'note'=>'※工事費込み' ),
				array( 'img'=>$img('sclass-opt-sink.jpg'),   'name'=>'ラクするーシンク（スゴピカ素材）',
				       'text'=>'汚れが落ちやすく、傷にも強いスゴピカ素材。使いやすくお手入れしやすい形状のシンク。',
				       'price'=>'31000', 'note'=>'' ),
				array( 'img'=>$img('sclass-opt-hood.jpg'),   'name'=>'ほっとくリーンフード（シルバー／静電タッチタイプ）',
				       'text'=>'面倒な換気扇フィルターのお手入れも、ボタンひとつで自動洗浄。',
				       'price'=>'137000', 'note'=>'' ),
				array( 'img'=>$img('sclass-opt-faucet.jpg'), 'name'=>'混合水栓サラサラワイドシャワー（スゴピカ素材）JUA03FWSNEホワイト',
				       'text'=>'水垢汚れが目立ちやすい水栓に、汚れに強いスゴピカ素材を採用。',
				       'price'=>'27000', 'note'=>'※オプション価格は近日中に値上げするため、現時点での参考価格となります。' ),
			) );

			/* 標準工事に含まれる工事 */
			$pw = array(
				array( '撤去工事',               '古いキッチンの撤去にかかる工事です。' ),
				array( '廃棄処分',               '撤去した古いキッチンを廃棄処分するためにかかる費用です。' ),
				array( 'ガス配管変更工事',       'ガスコンロを使うための配管工事です。' ),
				array( 'キッチンパネル設置工事', 'キッチンパネルの取り付け工事費です。' ),
				array( 'キッチンパネル部材費',   'キッチンパネル自体の部材費です。' ),
				array( '下地工事（大工工事）',   'キッチンパネル設置面の補修、補強の工事です。' ),
				array( 'シロッコファン取付工事', 'シロッコファンの取付工事です。' ),
			);
			$rows = array();
			foreach ( $pw as $r ) $rows[] = array( 'name' => $r[0], 'text' => $r[1] );
			update_post_meta( $pid, '_ymkrf_works', $rows );

			wp_set_object_terms( $pid, 'kitchen',   'ymkrf_product_cat' );
			wp_set_object_terms( $pid, 'panasonic', 'ymkrf_maker' );
			/* 展示店舗はカタログに記載が無いので未設定。管理画面から選んでください。 */

			update_post_meta( $pid, '_ymkrf_img_missing', $missing - $m0 );
			$log[] = '商品「Sクラス」を登録しました → ' . get_permalink( $pid );
		}
	}

	/* ------------------------------------------------------------
	   3-a5. ステディア（Aグレード・クリナップ）を1件つくる
	   ------------------------------------------------------------ */
	if ( ! get_page_by_path( 'stedia', OBJECT, 'ymkrf_product' ) ) {

		$tid = wp_insert_post( array(
			'post_type'   => 'ymkrf_product',
			'post_status' => 'publish',
			'post_title'  => 'ステディア',
			'post_name'   => 'stedia',
		) );

		if ( $tid && ! is_wp_error( $tid ) ) {

			$m0 = $missing;   /* この商品で見つからなかった写真を数えるための起点 */

			$f = array(
				'_ymkrf_catch'   => 'キレイと快適が毎日つづく快適キッチン！',
				'_ymkrf_grade'   => 'Aグレード',
				'_ymkrf_name'    => 'ステディア',
				'_ymkrf_size'    => 'I型2550サイズ',
				'_ymkrf_work'    => '240000',
				'_ymkrf_item'    => '858000',
				'_ymkrf_days'    => '3',  /* カタログに記載はありませんが、標準工期3日にそろえています */
				'_ymkrf_pt1'     => '美しさが長持ち',
				'_ymkrf_pt2'     => '長寿命',
				'_ymkrf_pt3'     => 'エコ',
				'_ymkrf_caution' => '※写真はイメージになります。※食洗器はオプションになります。'
				                  . '※扉カラーはクラス05です。※扉色によって選べる取手が異なります（全7種）。',
			);
			foreach ( $f as $k => $v ) update_post_meta( $tid, $k, $v );
			update_post_meta( $tid, '_ymkrf_total', 1098000 );

			$m = $img( 'stedia-main.jpg' );
			if ( $m ) set_post_thumbnail( $tid, $m );
			update_post_meta( $tid, '_ymkrf_images', array() );

			/* 扉カラー クラス05（全10色） */
			$tc = array(
				array( 'stedia-color-catwhite.jpg',    'スエードホワイト（CAT）' ),
				array( 'stedia-color-c9klatte.jpg',    'ミクスドラテ（C9K）' ),
				array( 'stedia-color-ckggreige.jpg',   'ルオントグレージュ（CKG）' ),
				array( 'stedia-color-e5kgrey.jpg',     'ロッシュグレー（E5K）' ),
				array( 'stedia-color-cazcharcoal.jpg', 'スエードチャコール（CAZ）' ),
				array( 'stedia-color-ecggrey.jpg',     'トワルグレー（ECG）' ),
				array( 'stedia-color-cklsepia.jpg',    'ルオントセピア（CKL）' ),
				array( 'stedia-color-e5hcharcoal.jpg', 'ロッシュチャコール（E5H）' ),
				array( 'stedia-color-ecurose.jpg',     'トワルローズ（ECU）' ),
				array( 'stedia-color-c4bbirch.jpg',    'クラシカルバーチ（C4B）' ),
			);
			$rows = array();
			foreach ( $tc as $r ) $rows[] = array( 'img' => $img( $r[0] ), 'name' => $r[1] );
			update_post_meta( $tid, '_ymkrf_colors', $rows );

			/* 天板（ステンレス・1種） */
			update_post_meta( $tid, '_ymkrf_tops', array(
				array( 'img' => $img( 'stedia-top-dot.jpg' ), 'name' => 'ステンレス ドット柄コイニング加工' ),
			) );
			update_post_meta( $tid, '_ymkrf_sinks', array() );

			/* 取手（写真のある5種） */
			$th = array(
				array( 'stedia-handle-silver.jpg',    'ロングバー', 'シルバー' ),
				array( 'stedia-handle-black.jpg',     'ロングバー', 'ブラック' ),
				array( 'stedia-handle-nekoashi.jpg',  'ネコアシ',   'ブラック' ),
				array( 'stedia-handle-line.jpg',      'ライン',     'シルバー' ),
				array( 'stedia-handle-lineblack.jpg', 'ライン',     'ブラック' ),
			);
			$rows = array();
			foreach ( $th as $r ) $rows[] = array( 'img' => $img( $r[0] ), 'name' => $r[1], 'code' => $r[2] );
			update_post_meta( $tid, '_ymkrf_handles', $rows );

			/* 標準仕様9点 */
			$ts = array(
				array( 'stedia-spec-ih.jpg',      'IHクッキングヒーター', 'CS-G321MS' ),
				array( 'stedia-spec-top.jpg',     'ステンレス天板',       '' ),
				array( 'stedia-spec-sink.jpg',    'ステンレスシンク',     'SA' ),
				array( 'stedia-spec-faucet.jpg',  'シャワーホース付き水栓', '' ),
				array( 'stedia-spec-hood.jpg',    'とってもクリンフード', 'ZRS90ACH22FSZ' ),
				array( 'stedia-spec-rail.jpg',    'サイレントレール',     '' ),
				array( 'stedia-spec-panel.jpg',   'キッチンパネル',       'キッチン正面・コンロ側側面' ),
				array( 'stedia-spec-cabinet.jpg', 'ステンレスキャビネット', '' ),
				array( 'stedia-spec-wallcab.jpg', 'ミドル吊戸棚',         '' ),
			);
			$rows = array();
			foreach ( $ts as $r ) $rows[] = array( 'img' => $img( $r[0], $r[1] ), 'name' => $r[1], 'model' => $r[2] );
			update_post_meta( $tid, '_ymkrf_specs', $rows );

			/* おすすめポイント（4グループ・11ポイント）
			   ステンレスエコキャビネットと流レールシンクには、
			   カタログに写真の無い行があります（文章だけ）。 */
			update_post_meta( $tid, '_ymkrf_features', array(
				array( 'gsub'=>'水や熱に強くキレイが長持ち。', 'gttl'=>'「ステンレスエコキャビネット」',
				       'ttl'=>'カビやニオイがつきにくい。',
				       'text'=>'食品を扱う場所には、もっともふさわしい素材。', 'note'=>'',
				       'img'=>$img('stedia-point-eco1.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'水汚れ、サビ、熱に強い。',
				       'text'=>'料理を思い切り楽しめます。', 'note'=>'',
				       'img'=>'', 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'美しさが長持ち。',
				       'text'=>'底板・側面・骨組みまでステンレス。お手入れ簡単。', 'note'=>'',
				       'img'=>'', 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'長寿命で、環境にやさしい。',
				       'text'=>'耐久年数が長く、リサイクル率は80％以上。', 'note'=>'',
				       'img'=>'', 'img2'=>'' ),
				array( 'gsub'=>'すぐに取り出せる。', 'gttl'=>'「ツールポケット」',
				       'ttl'=>'出し入れラクラク！',
				       'text'=>'よく使うものは手前に集めて引き出し内は立体的に。より効率的に出し入れできます。', 'note'=>'',
				       'img'=>$img('stedia-point-pocket1.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'こんなものの収納に！',
				       'text'=>'ラップやホイルなどの収納におすすめ。', 'note'=>'',
				       'img'=>$img('stedia-point-pocket2.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'汚れにくい、洗いやすい。', 'gttl'=>'「流レールシンク」',
				       'ttl'=>'お手入れカンタン。',
				       'text'=>'野菜くずも油汚れも、水にのって排水口へ。手間をかけずにキレイが保てます。', 'note'=>'',
				       'img'=>$img('stedia-point-sink1.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'清潔な排水口。',
				       'text'=>'継ぎ目無し＋美コートで、汚れをガード。', 'note'=>'',
				       'img'=>$img('stedia-point-sink2.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'水音静かなシンク。',
				       'text'=>'水はね音を抑えて、会話を妨げられません。', 'note'=>'',
				       'img'=>'', 'img2'=>'' ),
				array( 'gsub'=>'お手入れしやすい工夫が満載', 'gttl'=>'「とってもクリンフード」',
				       'ttl'=>'リーフプレート',
				       'text'=>'親水性塗装を施したプレートはスポンジで簡単に汚れを洗い流せます。', 'note'=>'',
				       'img'=>$img('stedia-point-hood2.jpg'), 'img2'=>$img('stedia-point-hood3.jpg') ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'立体構造フィルター',
				       'text'=>'リーフプレートとベルマウスや煙道部によって形成された立体型フィルター。油捕集性能とお手入れのしやすさを両立。', 'note'=>'',
				       'img'=>$img('stedia-point-hood4.jpg'), 'img2'=>'' ),
			) );

			/* おすすめオプション6点 */
			update_post_meta( $tid, '_ymkrf_options', array(
				array( 'img'=>$img('stedia-opt-dish.jpg'), 'name'=>'W450mmプルオープン 食器洗い乾燥機（ZWPP45R21LDS-E）',
				       'text'=>'手洗いより節水で省エネ。家事の手間を省きます。',
				       'price'=>'119000', 'note'=>'※工事費込み' ),
				array( 'img'=>$img('stedia-opt-acryston.jpg'), 'name'=>'アクリストン天板（ソリッド）＋アクリストン（AE）シンク（かってにクリントラップなし）',
				       'text'=>'美しさと丈夫さを兼ね備えた素材です。',
				       'price'=>'89000', 'note'=>'' ),
				array( 'img'=>$img('stedia-opt-trap.jpg'), 'name'=>'かってにクリントラップ仕様（SAシンク）',
				       'text'=>'自動洗浄機能を搭載した新機能トラップ。ヌメリの発生を抑止し、キレイをキープします。',
				       'price'=>'33000', 'note'=>'' ),
				array( 'img'=>$img('stedia-opt-araeru.jpg'), 'name'=>'洗エールレンジフード',
				       'text'=>'面倒な換気扇フィルターのお手入れも、ボタンひとつで自動洗浄。（W900／H700扉面材）',
				       'price'=>'106000', 'note'=>'' ),
				array( 'img'=>$img('stedia-opt-hood.jpg'), 'name'=>'とってもクリンフード',
				       'text'=>'お手入れしやすい工夫が満載。親水性塗装を施したプレートはスポンジで簡単に汚れを洗い流せます。',
				       'price'=>'90200', 'note'=>'' ),
				array( 'img'=>$img('stedia-opt-faucet.jpg'), 'name'=>'シャワーホース付き水栓',
				       'text'=>'引き出し可能なシャワーホースでシンクの掃除も快適に行え、省スペースで設置可能なモデルです。',
				       'price'=>'21300', 'note'=>'※オプション価格は近日中に値上げするため、現時点での参考価格となります。' ),
			) );

			/* 標準工事に含まれる工事 */
			$tw = array(
				array( '撤去工事',               '古いキッチンの撤去にかかる工事です。' ),
				array( '廃棄処分',               '撤去した古いキッチンを廃棄処分するためにかかる費用です。' ),
				array( 'ガス配管変更工事',       'ガスコンロを使うための配管工事です。' ),
				array( 'キッチンパネル設置工事', 'キッチンパネルの取り付け工事費です。' ),
				array( 'キッチンパネル部材費',   'キッチンパネル自体の部材費です。' ),
				array( '下地工事（大工工事）',   'キッチンパネル設置面の補修、補強の工事です。' ),
				array( 'シロッコファン取付工事', 'シロッコファンの取付工事です。' ),
			);
			$rows = array();
			foreach ( $tw as $r ) $rows[] = array( 'name' => $r[0], 'text' => $r[1] );
			update_post_meta( $tid, '_ymkrf_works', $rows );

			wp_set_object_terms( $tid, 'kitchen',  'ymkrf_product_cat' );
			wp_set_object_terms( $tid, 'cleanup',  'ymkrf_maker' );
			wp_set_object_terms( $tid, array( 'nonoichi', 'komathu', 'hakui', 'shinkaga',
				'kawakita', 'tazuruhama', 'asahi', 'kanadu' ), 'ymkrf_shop' );

			update_post_meta( $tid, '_ymkrf_img_missing', $missing - $m0 );
			$log[] = '商品「ステディア」を登録しました → ' . get_permalink( $tid );
		}
	}

	/* ------------------------------------------------------------
	   3-a6. エーデル（Sグレード・タカラスタンダード）を1件つくる
	   ------------------------------------------------------------ */
	if ( ! get_page_by_path( 'edel', OBJECT, 'ymkrf_product' ) ) {

		$eid = wp_insert_post( array(
			'post_type'   => 'ymkrf_product',
			'post_status' => 'publish',
			'post_title'  => 'エーデル',
			'post_name'   => 'edel',
		) );

		if ( $eid && ! is_wp_error( $eid ) ) {

			$m0 = $missing;   /* この商品で見つからなかった写真を数えるための起点 */

			$f = array(
				'_ymkrf_catch'   => 'キレイと快適が毎日つづくキッチン。',
				'_ymkrf_grade'   => 'Sグレード',
				'_ymkrf_name'    => 'エーデル',
				'_ymkrf_size'    => 'I型2550サイズ',
				'_ymkrf_work'    => '240000',
				'_ymkrf_item'    => '958000',
				'_ymkrf_days'    => '3',
				'_ymkrf_pt1'     => '一生ものの品質',
				'_ymkrf_pt2'     => 'お手入れ簡単',
				'_ymkrf_pt3'     => '収納力',
				'_ymkrf_caution' => '※写真はイメージになります。※食洗機はオプションになります。',
			);
			foreach ( $f as $k => $v ) update_post_meta( $eid, $k, $v );
			update_post_meta( $eid, '_ymkrf_total', 1198000 );

			$m = $img( 'edel-main.jpg' );
			if ( $m ) set_post_thumbnail( $eid, $m );
			update_post_meta( $eid, '_ymkrf_images', array() );

			/* 扉カラー（全7色） */
			$ec = array(
				array( 'edel-color-white.jpg',     'ホワイト' ),
				array( 'edel-color-ivory.jpg',     'フローラルアイボリー' ),
				array( 'edel-color-beige.jpg',     'ベージュ' ),
				array( 'edel-color-lightgray.jpg', 'ライトグレー' ),
				array( 'edel-color-lightpink.jpg', 'ライトピンク' ),
				array( 'edel-color-darkblue.jpg',  'ダークブルー' ),
				array( 'edel-color-brown.jpg',     'ブラウン' ),
			);
			$rows = array();
			foreach ( $ec as $r ) $rows[] = array( 'img' => $img( $r[0] ), 'name' => $r[1] );
			update_post_meta( $eid, '_ymkrf_colors', $rows );

			/* 天板カラー（全3色） */
			update_post_meta( $eid, '_ymkrf_tops', array(
				array( 'img' => $img( 'edel-top-beige.jpg' ), 'name' => 'ソリッドベージュ' ),
				array( 'img' => $img( 'edel-top-gray.jpg' ),  'name' => 'ソリッドグレー' ),
				array( 'img' => $img( 'edel-top-white.jpg' ), 'name' => 'ソリッドホワイト' ),
			) );

			/* シンクカラー（全3色） */
			update_post_meta( $eid, '_ymkrf_sinks', array(
				array( 'img' => $img( 'edel-sink-white.jpg' ), 'name' => 'ホワイト' ),
				array( 'img' => $img( 'edel-sink-gray.jpg' ),  'name' => 'グレー' ),
				array( 'img' => $img( 'edel-sink-beige.jpg' ), 'name' => 'ベージュ' ),
			) );

			/* 取っ手はカタログに記載が無いので空のまま（見出しごと出ません） */
			update_post_meta( $eid, '_ymkrf_handles', array() );

			/* 標準仕様10点 */
			$es = array(
				array( 'edel-spec-top.jpg',     'アクリル人造大理石天板',       '' ),
				array( 'edel-spec-sink.jpg',    'アクリル人造大理石シンク',     '' ),
				array( 'edel-spec-faucet.jpg',  '浄水器内蔵ハンドシャワー',     'TJS-SP19E' ),
				array( 'edel-spec-cabinet.jpg', 'ホーロー製キャビネット',       '' ),
				array( 'edel-spec-rail.jpg',    'ソフトクローズレール',         '' ),
				array( 'edel-spec-panel.jpg',   'ホーロークリーン キッチンパネル', '' ),
				array( 'edel-spec-rack.jpg',    'どこでもラック',               'アルミタイプ' ),
				array( 'edel-spec-led.jpg',     'LED手元照明',                  '' ),
				array( 'edel-spec-hood.jpg',    'シロッコファンレンジフード',   'VRAT-752AD(L/R)(V)' ),
				array( 'edel-spec-wallcab.jpg', '吊戸棚',                       'H700' ),
			);
			$rows = array();
			foreach ( $es as $r ) $rows[] = array( 'img' => $img( $r[0], $r[1] ), 'name' => $r[1], 'model' => $r[2] );
			update_post_meta( $eid, '_ymkrf_specs', $rows );

			/* おすすめポイント（3グループ・9ポイント） */
			update_post_meta( $eid, '_ymkrf_features', array(
				array( 'gsub'=>'一生ものの品質', 'gttl'=>'「まるごとホーローキャビネット」',
				       'ttl'=>'汚れに強い。',
				       'text'=>'汚れが染みこまないので、お手入れカンタン。油汚れも、水拭きでサッと落とせます。', 'note'=>'',
				       'img'=>$img('edel-point-horo1.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'熱に強い。',
				       'text'=>'高温になりがちなコンロまわりも、おまかせ。火を近づけても、変形・変色いたしません。', 'note'=>'',
				       'img'=>$img('edel-point-horo2.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'傷や衝撃に強い。',
				       'text'=>'硬いお鍋が当たっても、タワシでゴシゴシ磨いても、ちょっとやそっとではキズ付きません。', 'note'=>'',
				       'img'=>$img('edel-point-horo3.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'安心素材',
				       'text'=>'シックハウス症候群の原因となるホルムアルデヒドなどの有害物質を発生しません。', 'note'=>'',
				       'img'=>$img('edel-point-horo4.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'壁全体を収納スペースに。', 'gttl'=>'「どこでもラック」',
				       'ttl'=>'脱着カンタン。',
				       'text'=>'取付け・取外しが、自由自在。マグネットと吸盤のWの力でしっかり壁にくっつきます。', 'note'=>'',
				       'img'=>$img('edel-point-rack1.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'用途いろいろ。',
				       'text'=>'用途に応じた収納パーツを多彩にご用意。（オプション）',
				       'note'=>'※小物棚・ふきん掛け・レードル掛けは標準装備。',
				       'img'=>$img('edel-point-rack2.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'耐久性バツグン', 'gttl'=>'「アクリル人造大理石」天板・シンク',
				       'ttl'=>'熱や衝撃に強い耐久性',
				       'text'=>"大理石の質感を再現した美しさと、熱や衝撃に強い機能性を兼ね備えています。\n熱い物を置いても大丈夫！ほとんど変色しない耐久性を持っています。",
				       'note'=>'※日常は鍋敷きをお使いください。',
				       'img'=>$img('edel-point-acryl1.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'汚れにくい',
				       'text'=>'汚れにくい素材だから、お掃除が簡単！油汚れもサッと水拭きするだけで毎日のお手入れが簡単です。', 'note'=>'',
				       'img'=>$img('edel-point-acryl2.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'衝撃に強く、傷がつきにくい',
				       'text'=>'硬度の高い傷付きにくい素材なので、美しさが長持ちします。', 'note'=>'',
				       'img'=>$img('edel-point-acryl3.jpg'), 'img2'=>'' ),
			) );

			/* おすすめオプション4点 */
			update_post_meta( $eid, '_ymkrf_options', array(
				array( 'img'=>$img('edel-opt-dish.jpg'),  'name'=>'W450mmプルオープン 食器洗い乾燥機（EW-45R3ST）',
				       'text'=>'手洗いより節水で省エネ。家事の手間を省きます。',
				       'price'=>'95800', 'note'=>'' ),
				array( 'img'=>$img('edel-opt-lift.jpg'),  'name'=>'電動昇降吊戸棚（W900）',
				       'text'=>'スイッチひとつで、吊戸棚が昇降します。',
				       'price'=>'169500', 'note'=>'' ),
				array( 'img'=>$img('edel-opt-hood.jpg'),  'name'=>'ホーロークリーンフード（扉色幕板・W750）',
				       'text'=>'本体・整流板もホーロー製なので、汚れても水拭きでキレイに。',
				       'price'=>'55500', 'note'=>'' ),
				array( 'img'=>$img('edel-opt-irack.jpg'), 'name'=>'アイラック水切りタイプ（間口90cm）',
				       'text'=>'シンクの上の水切り棚で、家事を効率的に。使用しない時は、スッキリ納まります。',
				       'price'=>'48600', 'note'=>'※オプション価格は近日中に値上げするため、現時点での参考価格となります。' ),
			) );

			/* 標準工事に含まれる工事 */
			$ew = array(
				array( '撤去工事',               '古いキッチンの撤去にかかる工事です。' ),
				array( '廃棄処分',               '撤去した古いキッチンを廃棄処分するためにかかる費用です。' ),
				array( 'ガス配管変更工事',       'ガスコンロを使うための配管工事です。' ),
				array( 'キッチンパネル設置工事', 'キッチンパネルの取り付け工事費です。' ),
				array( 'キッチンパネル部材費',   'キッチンパネル自体の部材費です。' ),
				array( '下地工事（大工工事）',   'キッチンパネル設置面の補修、補強の工事です。' ),
				array( 'シロッコファン取付工事', 'シロッコファンの取付工事です。' ),
			);
			$rows = array();
			foreach ( $ew as $r ) $rows[] = array( 'name' => $r[0], 'text' => $r[1] );
			update_post_meta( $eid, '_ymkrf_works', $rows );

			wp_set_object_terms( $eid, 'kitchen', 'ymkrf_product_cat' );
			wp_set_object_terms( $eid, 'takara',  'ymkrf_maker' );
			wp_set_object_terms( $eid, array( 'nonoichi', 'komathu', 'hakui', 'kawakita', 'kanadu' ), 'ymkrf_shop' );

			update_post_meta( $eid, '_ymkrf_img_missing', $missing - $m0 );
			$log[] = '商品「エーデル」を登録しました → ' . get_permalink( $eid );
		}
	}

	/* ------------------------------------------------------------
	   3-a7. ザ・クラッソ（SSグレード・TOTO）を1件つくる
	   ------------------------------------------------------------ */
	if ( ! get_page_by_path( 'classo', OBJECT, 'ymkrf_product' ) ) {

		$cid = wp_insert_post( array(
			'post_type'   => 'ymkrf_product',
			'post_status' => 'publish',
			'post_title'  => 'ザ・クラッソ',
			'post_name'   => 'classo',
		) );

		if ( $cid && ! is_wp_error( $cid ) ) {

			$m0 = $missing;   /* この商品で見つからなかった写真を数えるための起点 */

			$f = array(
				'_ymkrf_catch'   => '気持ち、まいにち、きらめくキッチン。',
				'_ymkrf_grade'   => 'SSグレード',
				'_ymkrf_name'    => 'ザ・クラッソ',
				'_ymkrf_size'    => 'I型2550サイズ',
				'_ymkrf_work'    => '240000',
				'_ymkrf_item'    => '1158000',
				'_ymkrf_days'    => '3',
				'_ymkrf_pt1'     => '透明感',
				'_ymkrf_pt2'     => 'きらめき',
				'_ymkrf_pt3'     => 'お手軽きれい',
				'_ymkrf_caution' => '※写真はイメージになります。※食洗機はオプションになります。'
				                  . '※シンク色は、選択した人造大理石カラーに連動します。',
			);
			foreach ( $f as $k => $v ) update_post_meta( $cid, $k, $v );
			update_post_meta( $cid, '_ymkrf_total', 1398000 );

			$m = $img( 'classo-main.jpg' );
			if ( $m ) set_post_thumbnail( $cid, $m );
			update_post_meta( $cid, '_ymkrf_images', array() );

			/* 扉カラー グループ1（全6色） */
			$cc = array(
				array( 'classo-color-unigray.jpg',    'ユニグレー' ),
				array( 'classo-color-barawhite.jpg',  'バラホワイト' ),
				array( 'classo-color-barabeige.jpg',  'バラベージュ' ),
				array( 'classo-color-baramarron.jpg', 'バラマロン' ),
				array( 'classo-color-baranavy.jpg',   'バラネイビー' ),
				array( 'classo-color-uninature.jpg',  'ユニナチュレ' ),
			);
			$rows = array();
			foreach ( $cc as $r ) $rows[] = array( 'img' => $img( $r[0] ), 'name' => $r[1] );
			update_post_meta( $cid, '_ymkrf_colors', $rows );

			/* 天板 クリスタルカウンター単色（全6色） */
			$ct = array(
				array( 'classo-top-snow.jpg',      'クリスタルスノー' ),
				array( 'classo-top-gray.jpg',      'クリスタルグレー' ),
				array( 'classo-top-greige.jpg',    'クリスタルグレージュ' ),
				array( 'classo-top-palegreen.jpg', 'クリスタルペールグリーン' ),
				array( 'classo-top-lightpink.jpg', 'クリスタルライトピンク' ),
				array( 'classo-top-dullgray.jpg',  'クリスタルダルグレー' ),
			);
			$rows = array();
			foreach ( $ct as $r ) $rows[] = array( 'img' => $img( $r[0] ), 'name' => $r[1] );
			update_post_meta( $cid, '_ymkrf_tops', $rows );

			/* シンク クリスタルシンク単色（全6色） */
			$cs = array(
				array( 'classo-sink-snow.jpg',      'クリスタルスノー' ),
				array( 'classo-sink-gray.jpg',      'クリスタルグレー' ),
				array( 'classo-sink-greige.jpg',    'クリスタルグレージュ' ),
				array( 'classo-sink-palegreen.jpg', 'クリスタルペールグリーン' ),
				array( 'classo-sink-lightpink.jpg', 'クリスタルライトピンク' ),
				array( 'classo-sink-dullgray.jpg',  'クリスタルダルグレー' ),
			);
			$rows = array();
			foreach ( $cs as $r ) $rows[] = array( 'img' => $img( $r[0] ), 'name' => $r[1] );
			update_post_meta( $cid, '_ymkrf_sinks', $rows );

			/* 取手（全6種） */
			$ch = array(
				array( 'classo-handle-slim-silver.jpg',  'スリム取手',   'ステンシルバー' ),
				array( 'classo-handle-round-silver.jpg', 'ラウンド取手', 'ステンシルバー' ),
				array( 'classo-handle-classic.jpg',      'クラシック取手', '' ),
				array( 'classo-handle-slim-black.jpg',   'スリム取手',   'ブラック' ),
				array( 'classo-handle-round-black.jpg',  'ラウンド取手', 'ブラック' ),
				array( 'classo-handle-line.jpg',         'ライン取手',   '' ),
			);
			$rows = array();
			foreach ( $ch as $r ) $rows[] = array( 'img' => $img( $r[0] ), 'name' => $r[1], 'code' => $r[2] );
			update_post_meta( $cid, '_ymkrf_handles', $rows );

			/* 標準仕様10点 */
			$cp = array(
				array( 'classo-spec-ih.jpg',      'IHクッキングヒーター',       'CS-G321MS シルバー W600' ),
				array( 'classo-spec-counter.jpg', 'クリスタルカウンター単色',   '' ),
				array( 'classo-spec-hood.jpg',    'ゼロフィルターフードeco',    '扉材前板シルバー W900/H700' ),
				array( 'classo-spec-sink.jpg',    'スクエアすべり台シンク',     'クリスタル' ),
				array( 'classo-spec-cabinet.jpg', '2段引き出しキャビネット',    'ステンレス底板' ),
				array( 'classo-spec-faucet.jpg',  'タッチレス水ほうき水栓LF',   'センサースイッチ' ),
				array( 'classo-spec-rack.jpg',    'アイレベルラック W900',      '※背面壁が必要です' ),
				array( 'classo-spec-panel.jpg',   'キッチンパネル',             'キッチン正面・コンロ側側面' ),
				array( 'classo-spec-jokin.jpg',   'タッチレス「きれい除菌水」生成器', '' ),
				array( 'classo-spec-led.jpg',     'LEDスリムライト',            '' ),
			);
			$rows = array();
			foreach ( $cp as $r ) $rows[] = array( 'img' => $img( $r[0], $r[1] ), 'name' => $r[1], 'model' => $r[2] );
			update_post_meta( $cid, '_ymkrf_specs', $rows );

			/* おすすめポイント（3グループ・6ポイント） */
			update_post_meta( $cid, '_ymkrf_features', array(
				array( 'gsub'=>'光を取り込み、キッチンに透明感を。', 'gttl'=>'「クリスタルカウンター・シンク」',
				       'ttl'=>'透明感と明るさのあるカウンタートップ',
				       'text'=>"すりガラスのような仕上げで、滑らかな手触り。空間を明るく魅力的に演出します。\n カウンター端部のクリアエッジ仕上げが輝きを放ち、空間に明るいアクセントを加えます。",
				       'note'=>'',
				       'img'=>$img('classo-point-crystal1.jpg'), 'img2'=>$img('classo-point-crystal2.jpg') ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'お手入れしやすく、使いやすい',
				       'text'=>"磨いてキレイ！ 汚れや擦り傷は磨いてキレイに落とせます。\n熱に強い！ 熱冷の繰り返しに強く、安心して使えます。\n衝撃に強い！ 硬い物を落としても割れにくく、美しさを損ないません。",
				       'note'=>'',
				       'img'=>$img('classo-point-crystal3.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'タッチレスでスムーズに吐止水', 'gttl'=>'「タッチレス水ほうき水栓LF」',
				       'ttl'=>'水はねしにくい、幅広シャワー。',
				       'text'=>'水はねしにくさと洗浄力を両立した、幅広のミクロソフトシャワー。', 'note'=>'',
				       'img'=>$img('classo-point-faucet1.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'大きな鍋や大皿も洗えます！',
				       'text'=>'吐水位置が高いので、洗い物などの際に水栓が邪魔になりません。', 'note'=>'',
				       'img'=>$img('classo-point-faucet2.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'パパっと手軽に仕上げのひとふき。', 'gttl'=>'「タッチレスきれい除菌水生成器」',
				       'ttl'=>'タッチレスでお手軽きれい。',
				       'text'=>'センサーに手をかざすと、除菌効果のある水がミスト状で噴霧されます。', 'note'=>'',
				       'img'=>$img('classo-point-jokin1.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'除菌やヌメリ防止に！',
				       'text'=>"仕上げに「きれい除菌水」をふきかければ除菌ができます。\n網かご、まな板・包丁、布巾もきれいに。",
				       'note'=>'',
				       'img'=>$img('classo-point-jokin2.jpg'), 'img2'=>'' ),
			) );

			/* おすすめオプション4点 */
			update_post_meta( $cid, '_ymkrf_options', array(
				array( 'img'=>$img('classo-opt-dish.jpg'), 'name'=>'W450食器洗い乾燥機 Pストリーム洗浄・浅型扉材 DWD27',
				       'text'=>'手洗いより節水で省エネ。家事の手間を省きます。',
				       'price'=>'229600', 'note'=>'※工事費込み' ),
				array( 'img'=>$img('classo-opt-pattern.jpg'), 'name'=>'クリスタルカウンター柄入り天板カラー',
				       'text'=>'柔らかく浮かび上がる柄が洗練された高級感を演出。',
				       'price'=>'148700', 'note'=>'' ),
				array( 'img'=>$img('classo-opt-faucet.jpg'), 'name'=>'水ほうき水栓LFクロムメッキ（センサースイッチ／浄水器兼用）＋タッチレスきれい除菌水生成器',
				       'text'=>'タッチレスで浄水の切り替えも可能です。',
				       'price'=>'82900', 'note'=>'' ),
				array( 'img'=>$img('classo-opt-basket.jpg'), 'name'=>'水切りバスケット（スクエアすべり台シンク用）',
				       'text'=>'ボタンを押すだけで上げ下げ。洗い物もスッキリ収納。',
				       'price'=>'7500', 'note'=>'※オプション価格は近日中に値上げするため、現時点での参考価格となります。' ),
			) );

			/* 標準工事に含まれる工事 */
			$cw = array(
				array( '撤去工事',               '古いキッチンの撤去にかかる工事です。' ),
				array( '廃棄処分',               '撤去した古いキッチンを廃棄処分するためにかかる費用です。' ),
				array( 'ガス配管変更工事',       'ガスコンロを使うための配管工事です。' ),
				array( 'キッチンパネル設置工事', 'キッチンパネルの取り付け工事費です。' ),
				array( 'キッチンパネル部材費',   'キッチンパネル自体の部材費です。' ),
				array( '下地工事（大工工事）',   'キッチンパネル設置面の補修、補強の工事です。' ),
				array( 'シロッコファン取付工事', 'シロッコファンの取付工事です。' ),
			);
			$rows = array();
			foreach ( $cw as $r ) $rows[] = array( 'name' => $r[0], 'text' => $r[1] );
			update_post_meta( $cid, '_ymkrf_works', $rows );

			wp_set_object_terms( $cid, 'kitchen', 'ymkrf_product_cat' );
			wp_set_object_terms( $cid, 'toto',    'ymkrf_maker' );
			wp_set_object_terms( $cid, array( 'nonoichi', 'komathu', 'hakui' ), 'ymkrf_shop' );

			update_post_meta( $cid, '_ymkrf_img_missing', $missing - $m0 );
			$log[] = '商品「ザ・クラッソ」を登録しました → ' . get_permalink( $cid );
		}
	}

	/* ------------------------------------------------------------
	   3-a8. リシェル（SSSグレード・LIXIL）を1件つくる
	   ------------------------------------------------------------ */
	if ( ! get_page_by_path( 'richelle', OBJECT, 'ymkrf_product' ) ) {

		$xid = wp_insert_post( array(
			'post_type'   => 'ymkrf_product',
			'post_status' => 'publish',
			'post_title'  => 'リシェル',
			'post_name'   => 'richelle',
		) );

		if ( $xid && ! is_wp_error( $xid ) ) {

			$m0 = $missing;   /* この商品で見つからなかった写真を数えるための起点 */

			$f = array(
				'_ymkrf_catch'   => 'シンプルで使いやすいキッチン',
				'_ymkrf_grade'   => 'SSSグレード',
				'_ymkrf_name'    => 'リシェル',
				'_ymkrf_size'    => 'I型2550タイプ',
				'_ymkrf_work'    => '240000',
				'_ymkrf_item'    => '1458000',
				'_ymkrf_days'    => '3',
				'_ymkrf_pt1'     => '強い素材',
				'_ymkrf_pt2'     => '快適',
				'_ymkrf_pt3'     => '収納力',
				'_ymkrf_caution' => '※写真はイメージになります。'
				                  . '※扉カラーのグループ1は4シリーズ・全10色の中からお選びいただけます。',
			);
			foreach ( $f as $k => $v ) update_post_meta( $xid, $k, $v );
			update_post_meta( $xid, '_ymkrf_total', 1698000 );

			$m = $img( 'richelle-main.jpg' );
			if ( $m ) set_post_thumbnail( $xid, $m );
			update_post_meta( $xid, '_ymkrf_images', array() );

			/* 扉カラー グループ1（4シリーズ・全10色） */
			$xc = array(
				array( 'richelle-color-blackstucco.jpg',  'ブラックスタッコ（KP1）' ),
				array( 'richelle-color-greigestucco.jpg', 'グレージュスタッコ（VP1）' ),
				array( 'richelle-color-whitestucco.jpg',  'ホワイトスタッコ（WP1）' ),
				array( 'richelle-color-airywhite.jpg',    'エアリィホワイト（WP0）' ),
				array( 'richelle-color-criedark.jpg',     'クリエダーク（AQ1）' ),
				array( 'richelle-color-criemocha.jpg',    'クリエモカ（UQ1）' ),
				array( 'richelle-color-crieivory.jpg',    'クリエアイボリー（HQ1）' ),
				array( 'richelle-color-plainwalnut.jpg',  'プレーンウォルナット（AQ0）' ),
				array( 'richelle-color-rusticash.jpg',    'ラスティックアッシュ（IQ0）' ),
				array( 'richelle-color-rusticoak.jpg',    'ラスティックオーク（NQ0）' ),
			);
			$rows = array();
			foreach ( $xc as $r ) $rows[] = array( 'img' => $img( $r[0] ), 'name' => $r[1] );
			update_post_meta( $xid, '_ymkrf_colors', $rows );

			/* 天板（全6色） */
			$xt = array(
				array( 'richelle-top-carbon.jpg',         'ラパートカーボン' ),
				array( 'richelle-top-taupe.jpg',          'ラパートトープ' ),
				array( 'richelle-top-silk.jpg',           'ラパートシルク' ),
				array( 'richelle-top-glazegray.jpg',      'グレーズグレー' ),
				array( 'richelle-top-basaltblack.jpg',    'バサルトブラック' ),
				array( 'richelle-top-calacattawhite.jpg', 'カラカッタホワイト' ),
			);
			$rows = array();
			foreach ( $xt as $r ) $rows[] = array( 'img' => $img( $r[0] ), 'name' => $r[1] );
			update_post_meta( $xid, '_ymkrf_tops', $rows );

			/* シンク（全3色） */
			update_post_meta( $xid, '_ymkrf_sinks', array(
				array( 'img' => $img( 'richelle-sink-cosmicgray.jpg' ), 'name' => 'コズミックグレー' ),
				array( 'img' => $img( 'richelle-sink-taupebeige.jpg' ), 'name' => 'トープベージュ' ),
				array( 'img' => $img( 'richelle-sink-shellwhite.jpg' ), 'name' => 'シェルホワイト' ),
			) );

			/* 取っ手はカタログに一覧が無いので空のまま（見出しごと出ません） */
			update_post_meta( $xid, '_ymkrf_handles', array() );

			/* 標準仕様10点 */
			$xs = array(
				array( 'richelle-spec-top.jpg',     'セラミックトップ',           '' ),
				array( 'richelle-spec-sink.jpg',    'ハイブリットクォーツシンク', '' ),
				array( 'richelle-spec-led.jpg',     'LED照明',                    'クイックポケット一体型' ),
				array( 'richelle-spec-hood.jpg',    'シロッコファンレンジフード', 'SER-933S1' ),
				array( 'richelle-spec-rakupat.jpg', 'らくパット収納',             'シンク下・コンロ下' ),
				array( 'richelle-spec-rail.jpg',    'ソフトモーションレール',     '' ),
				array( 'richelle-spec-bottom.jpg',  'ステンレス引出し底板',       '' ),
				array( 'richelle-spec-pocket.jpg',  'クイックポケット',           '' ),
				array( 'richelle-spec-pallet.jpg',  'クイックパレット',           '' ),
				array( 'richelle-spec-faucet.jpg',  'ハンズフリー水栓H7エコハンドル', 'SFNAH472SYJG5' ),
			);
			$rows = array();
			foreach ( $xs as $r ) $rows[] = array( 'img' => $img( $r[0], $r[1] ), 'name' => $r[1], 'model' => $r[2] );
			update_post_meta( $xid, '_ymkrf_specs', $rows );

			/* おすすめポイント（3グループ・8ポイント） */
			update_post_meta( $xid, '_ymkrf_features', array(
				array( 'gsub'=>'熱にもキズにも汚れにも、強い。だから、調理に専念できます。', 'gttl'=>'「セラミックトップ」',
				       'ttl'=>'熱に強い。',
				       'text'=>"焼き物ならではの繊細な味わい深い表情が、キッチンを個性的に彩ります。\n高温の鍋を直接置いても変形や変色が起こりにくい丈夫な素材です。",
				       'note'=>'',
				       'img'=>$img('richelle-point-ceramic1.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'キズに強い。',
				       'text'=>'表面硬度が高く、金属などでこすってもキズがつきにくくなっています。', 'note'=>'',
				       'img'=>$img('richelle-point-ceramic2.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'汚れに強い。',
				       'text'=>'汚れが染み込みにくいので、軽く拭くだけでお手入れできます。', 'note'=>'',
				       'img'=>$img('richelle-point-ceramic3.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'衝撃に強い。',
				       'text'=>'耐衝撃性を高める強化構造を採用。', 'note'=>'',
				       'img'=>$img('richelle-point-ceramic4.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'斜めに傾く扉がポイント！', 'gttl'=>'「らくパッと収納」',
				       'ttl'=>'パッとポケット',
				       'text'=>"軽い力でラクに開けられます。\n包丁などは扉を傾けるだけで取り出せます。", 'note'=>'',
				       'img'=>$img('richelle-point-raku1.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'パッとシェルフ',
				       'text'=>'ボウルなども、引き出しを大きく開けなくても、取り出せます。', 'note'=>'',
				       'img'=>$img('richelle-point-raku2.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'シンクに適した素材。', 'gttl'=>'「ハイブリットクォーツシンク」',
				       'ttl'=>'ハイブリットクォーツ',
				       'text'=>'耐摩耗性、耐衝撃性、耐防汚性などシンクに求められる様々な性能をバランスよく達成しました。', 'note'=>'',
				       'img'=>$img('richelle-point-quartz1.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'ナイアガラフロー方式',
				       'text'=>'シンクの奥の段差に向けて汚れやゴミをスムーズに洗い流せます。', 'note'=>'',
				       'img'=>$img('richelle-point-quartz2.jpg'), 'img2'=>'' ),
			) );

			/* おすすめオプション4点 */
			update_post_meta( $xid, '_ymkrf_options', array(
				array( 'img'=>$img('richelle-opt-dish.jpg'), 'name'=>'W450mmプルオープン 食器洗い乾燥機 NP-45ME9WPJG',
				       'text'=>'手洗いより節水で省エネ。家事の手間を省きます。',
				       'price'=>'423000', 'note'=>'' ),
				array( 'img'=>$img('richelle-opt-downwall.jpg'), 'name'=>'オートダウンウォール（W900収納＋W750水切り）',
				       'text'=>'スイッチ操作で棚が自動的に昇降。上段も目の高さで便利に使えます。',
				       'price'=>'193000', 'note'=>'' ),
				array( 'img'=>$img('richelle-opt-hood.jpg'), 'name'=>'よごれんフード（W900・扉色幕板）',
				       'text'=>'フード内部の面倒なお掃除から開放してくれます。',
				       'price'=>'75000', 'note'=>'' ),
				array( 'img'=>$img('richelle-opt-water.jpg'), 'name'=>'ビルトイン型浄水器 専用水栓ナビッシュ JF-ND701-JG',
				       'text'=>'手をかざすだけで楽々吐水。カートリッジ1本付き。',
				       'price'=>'95000', 'note'=>'※オプション価格は近日中に値上げするため、現時点での参考価格となります。' ),
			) );

			/* 標準工事に含まれる工事 */
			$xw = array(
				array( '撤去工事',               '古いキッチンの撤去にかかる工事です。' ),
				array( '廃棄処分',               '撤去した古いキッチンを廃棄処分するためにかかる費用です。' ),
				array( 'ガス配管変更工事',       'ガスコンロを使うための配管工事です。' ),
				array( 'キッチンパネル設置工事', 'キッチンパネルの取り付け工事費です。' ),
				array( 'キッチンパネル部材費',   'キッチンパネル自体の部材費です。' ),
				array( '下地工事（大工工事）',   'キッチンパネル設置面の補修、補強の工事です。' ),
				array( 'シロッコファン取付工事', 'シロッコファンの取付工事です。' ),
			);
			$rows = array();
			foreach ( $xw as $r ) $rows[] = array( 'name' => $r[0], 'text' => $r[1] );
			update_post_meta( $xid, '_ymkrf_works', $rows );

			wp_set_object_terms( $xid, 'kitchen', 'ymkrf_product_cat' );
			wp_set_object_terms( $xid, 'lixil',   'ymkrf_maker' );
			wp_set_object_terms( $xid, array( 'nonoichi', 'komathu', 'hakui', 'kanadu' ), 'ymkrf_shop' );

			update_post_meta( $xid, '_ymkrf_img_missing', $missing - $m0 );
			$log[] = '商品「リシェル」を登録しました → ' . get_permalink( $xid );
		}
	}

	/* ------------------------------------------------------------
	   3-a9. セントロ（プレミアム・クリナップ）を1件つくる
	   ------------------------------------------------------------ */
	if ( ! get_page_by_path( 'centro', OBJECT, 'ymkrf_product' ) ) {

		$nid = wp_insert_post( array(
			'post_type'   => 'ymkrf_product',
			'post_status' => 'publish',
			'post_title'  => 'セントロ',
			'post_name'   => 'centro',
		) );

		if ( $nid && ! is_wp_error( $nid ) ) {

			$m0 = $missing;   /* この商品で見つからなかった写真を数えるための起点 */

			$f = array(
				'_ymkrf_catch'   => 'キレイと快適が毎日つづく快適キッチン！',
				'_ymkrf_grade'   => 'プレミアム',
				'_ymkrf_name'    => 'セントロ',
				'_ymkrf_size'    => 'I型2550サイズ',
				'_ymkrf_work'    => '240000',
				'_ymkrf_item'    => '1508000',
				'_ymkrf_days'    => '3',  /* カタログに記載はありませんが、標準工期3日にそろえています */
				'_ymkrf_pt1'     => 'キレイが長持ち',
				'_ymkrf_pt2'     => '味わい深い',
				'_ymkrf_pt3'     => '快適',
				'_ymkrf_caution' => '※写真はイメージになります。※食洗機はオプションになります。',
			);
			foreach ( $f as $k => $v ) update_post_meta( $nid, $k, $v );
			update_post_meta( $nid, '_ymkrf_total', 1748000 );

			$m = $img( 'centro-main.jpg' );
			if ( $m ) set_post_thumbnail( $nid, $m );
			update_post_meta( $nid, '_ymkrf_images', array() );

			/* 扉カラー06クラス（全10色） */
			$nc = array(
				array( 'centro-color-white.jpg',        'ホワイト（CAT）' ),
				array( 'centro-color-charcoal.jpg',     'チャコール（CAZ）' ),
				array( 'centro-color-silver.jpg',       'シルバー' ),
				array( 'centro-color-midnightgray.jpg', 'ミッドナイトグレー（CAV）' ),
				array( 'centro-color-ash.jpg',          'アッシュ（E3M）' ),
				array( 'centro-color-oak.jpg',          'オーク（C3A）' ),
				array( 'centro-color-cherry.jpg',       'チェリー（C3B）' ),
				array( 'centro-color-walnut.jpg',       'ウォールナット（C3L）' ),
				array( 'centro-color-rocagreige.jpg',   'ロカグレージュ（E5K）' ),
				array( 'centro-color-rocacharcoal.jpg', 'ロカチャコール（E5H）' ),
			);
			$rows = array();
			foreach ( $nc as $r ) $rows[] = array( 'img' => $img( $r[0] ), 'name' => $r[1] );
			update_post_meta( $nid, '_ymkrf_colors', $rows );

			/* 天板 インダストリアルコレクション（全4種） */
			update_post_meta( $nid, '_ymkrf_tops', array(
				array( 'img' => $img( 'centro-top-albanium.jpg' ), 'name' => 'アルバニウム' ),
				array( 'img' => $img( 'centro-top-creta.jpg' ),    'name' => 'クレタ' ),
				array( 'img' => $img( 'centro-top-sirius.jpg' ),   'name' => 'シリウス' ),
				array( 'img' => $img( 'centro-top-edra.jpg' ),     'name' => 'エドラ（J）' ),
			) );
			update_post_meta( $nid, '_ymkrf_sinks', array() );

			/* 取手（全6種） */
			$nh = array(
				array( 'centro-handle-longbar-silver.jpg', 'ロングバー取手', 'シャンパンシルバー' ),
				array( 'centro-handle-longbar-black.jpg',  'ロングバー取手', 'ブラック' ),
				array( 'centro-handle-line-silver.jpg',    'ライン取手',     'シルバー' ),
				array( 'centro-handle-line-black.jpg',     'ライン取手',     'ブラック' ),
				array( 'centro-handle-bar-gold.jpg',       'バー取手',       'オクトゴールド' ),
				array( 'centro-handle-bar-bronze.jpg',     'バー取手',       'コッピングブロンズ' ),
			);
			$rows = array();
			foreach ( $nh as $r ) $rows[] = array( 'img' => $img( $r[0] ), 'name' => $r[1], 'code' => $r[2] );
			update_post_meta( $nid, '_ymkrf_handles', $rows );

			/* 標準仕様10点 */
			$ns = array(
				array( 'centro-spec-ih.jpg',       'IHクッキングヒーター',       'CS-G321MS シルバー W600' ),
				array( 'centro-spec-top.jpg',      'セラミックワークトップ',     '' ),
				array( 'centro-spec-hood.jpg',     'とってもクリンレンジフード', 'ZRS90ACH22FSZ' ),
				array( 'centro-spec-sink.jpg',     'ステンレスシンク',           'SC' ),
				array( 'centro-spec-sinkcab.jpg',  'シンクキャビネット',         'ツールコンテナ付き' ),
				array( 'centro-spec-conrocab.jpg', 'コンロキャビネット',         'ツールコンテナ付き' ),
				array( 'centro-spec-wallcab.jpg',  'ハンドムーブ吊戸棚',         '' ),
				array( 'centro-spec-drain.jpg',    '収納水切りタイプ',           '照明付き・収納タイプ' ),
				array( 'centro-spec-panel.jpg',    'キッチンパネル',             'キッチン正面・コンロ側側面' ),
				array( 'centro-spec-faucet.jpg',   'タッチレス水栓',             'ZZSFNA451SY' ),
			);
			$rows = array();
			foreach ( $ns as $r ) $rows[] = array( 'img' => $img( $r[0], $r[1] ), 'name' => $r[1], 'model' => $r[2] );
			update_post_meta( $nid, '_ymkrf_specs', $rows );

			/* おすすめポイント（4グループ・12ポイント） */
			update_post_meta( $nid, '_ymkrf_features', array(
				array( 'gsub'=>'水や熱に強くキレイが長持ち。', 'gttl'=>'「ステンレスエコキャビネット」',
				       'ttl'=>'カビやニオイがつきにくい。',
				       'text'=>'食品を扱う場所には、もっともふさわしい素材。', 'note'=>'',
				       'img'=>$img('centro-point-eco1.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'水汚れ、サビ、熱に強い。',
				       'text'=>'料理を思い切り楽しめます。', 'note'=>'', 'img'=>'', 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'美しさが長持ち。',
				       'text'=>'底板・側面・骨組みまでステンレス。お手入れ簡単。', 'note'=>'', 'img'=>'', 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'長寿命で、環境にやさしい。',
				       'text'=>'耐久年数が長く、リサイクル率は80％以上。', 'note'=>'', 'img'=>'', 'img2'=>'' ),
				array( 'gsub'=>'陶器のような味わい深さと輝き。', 'gttl'=>'「セラミックワークトップ」',
				       'ttl'=>'熱・洗剤での変色なし！',
				       'text'=>'高温に耐え得る高いパフォーマンスで、変色や変質の心配がほとんどなく長くお使いいただけます。ほぼ無孔質なので化学品に対しても高い抵抗性を誇ります。',
				       'note'=>'', 'img'=>$img('centro-point-ceramic1.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'衝撃に強く割れにくい！',
				       'text'=>'引っ掻き傷に対し強靭な強さを発揮します。', 'note'=>'',
				       'img'=>$img('centro-point-ceramic2.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'汚れにくい、洗いやすい。', 'gttl'=>'「流レールシンク」',
				       'ttl'=>'お手入れカンタン。',
				       'text'=>'野菜くずも油汚れも、水にのって排水口へ。手間をかけずにキレイが保てます。', 'note'=>'',
				       'img'=>$img('centro-point-sink1.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'清潔な排水口。',
				       'text'=>'継ぎ目無し＋美コートで、汚れをガード。', 'note'=>'',
				       'img'=>$img('centro-point-sink2.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'水音静かなシンク。',
				       'text'=>'水はね音を抑えるから、会話を妨げられません。', 'note'=>'', 'img'=>'', 'img2'=>'' ),
				array( 'gsub'=>'楽な姿勢で、手が届く。', 'gttl'=>'「ハンドムーブ」',
				       'ttl'=>'カウンター面はいつもスッキリ。',
				       'text'=>'調味料や洗い物などをしまえるので、調理台がすっきり片付きます。', 'note'=>'',
				       'img'=>'', 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'調味料タイプ',
				       'text'=>'キッチンペーパーなどをまとめて収納。', 'note'=>'',
				       'img'=>$img('centro-point-hand1.jpg'), 'img2'=>'' ),
				array( 'gsub'=>'', 'gttl'=>'',
				       'ttl'=>'水切りタイプ',
				       'text'=>'洗い物を濡れたままそのまま置けます。', 'note'=>'',
				       'img'=>$img('centro-point-hand2.jpg'), 'img2'=>'' ),
			) );

			/* おすすめオプション4点 */
			update_post_meta( $nid, '_ymkrf_options', array(
				array( 'img'=>$img('centro-opt-dish.jpg'), 'name'=>'W450mmプルオープン 食器洗い乾燥機 ZWPJ45M21PDZ',
				       'text'=>'手洗いより節水で省エネ。家事の手間を省きます。',
				       'price'=>'289900', 'note'=>'' ),
				array( 'img'=>$img('centro-opt-fortex.jpg'), 'name'=>'流レールシンク（フォルテックス）',
				       'text'=>'アクリストンよりも硬度をあげ、丈夫さが増した硬質アクリル人造大理石。',
				       'price'=>'57500', 'note'=>'' ),
				array( 'img'=>$img('centro-opt-trap.jpg'), 'name'=>'かってにクリントラップ仕様（SAシンク）',
				       'text'=>'自動洗浄機能を搭載した新機能トラップ。ヌメリの発生を抑止し、キレイをキープします。',
				       'price'=>'52300', 'note'=>'' ),
				array( 'img'=>$img('centro-opt-araeru.jpg'), 'name'=>'洗エールレンジフード',
				       'text'=>'面倒な換気扇フィルターのお手入れも、ボタンひとつで自動洗浄。（W900／H700扉面材）',
				       'price'=>'94700', 'note'=>'※オプション価格は近日中に値上げするため、現時点での参考価格となります。' ),
			) );

			/* 標準工事に含まれる工事 */
			$nw = array(
				array( '撤去工事',               '古いキッチンの撤去にかかる工事です。' ),
				array( '廃棄処分',               '撤去した古いキッチンを廃棄処分するためにかかる費用です。' ),
				array( 'ガス配管変更工事',       'ガスコンロを使うための配管工事です。' ),
				array( 'キッチンパネル設置工事', 'キッチンパネルの取り付け工事費です。' ),
				array( 'キッチンパネル部材費',   'キッチンパネル自体の部材費です。' ),
				array( '下地工事（大工工事）',   'キッチンパネル設置面の補修、補強の工事です。' ),
				array( 'シロッコファン取付工事', 'シロッコファンの取付工事です。' ),
			);
			$rows = array();
			foreach ( $nw as $r ) $rows[] = array( 'name' => $r[0], 'text' => $r[1] );
			update_post_meta( $nid, '_ymkrf_works', $rows );

			wp_set_object_terms( $nid, 'kitchen', 'ymkrf_product_cat' );
			wp_set_object_terms( $nid, 'cleanup', 'ymkrf_maker' );
			wp_set_object_terms( $nid, array( 'nonoichi', 'komathu', 'hakui' ), 'ymkrf_shop' );

			update_post_meta( $nid, '_ymkrf_img_missing', $missing - $m0 );
			$log[] = '商品「セントロ」を登録しました → ' . get_permalink( $nid );
		}
	}

	/* ------------------------------------------------------------
	   3-b. すでに登録済みの商品の文言直し
	        （新しく作り直さず、変わったところだけを上書きします）
	   ------------------------------------------------------------ */
	$fixes = array(
		'v-style' => array(
			'_ymkrf_pt3' => array( 'おそうじラクラク', 'おそうじ楽々' ),
		),
	);
	foreach ( $fixes as $slug => $keys ) {
		$post = get_page_by_path( $slug, OBJECT, 'ymkrf_product' );
		if ( ! $post ) continue;
		foreach ( $keys as $key => $pair ) {
			list( $old, $new ) = $pair;
			if ( get_post_meta( $post->ID, $key, true ) === $old ) {
				update_post_meta( $post->ID, $key, $new );
				$log[] = "{$slug}: {$old} → {$new}";
			}
		}
	}

	/* ------------------------------------------------------------
	   3-c. ラクエラのアイキャッチを「商品だけの写真」に差し替え
	        （手で別の写真に変えてある場合は、触りません）
	   ------------------------------------------------------------ */
	$rp = get_page_by_path( 'rakuera', OBJECT, 'ymkrf_product' );
	if ( $rp ) {
		$cur = (int) get_post_thumbnail_id( $rp->ID );
		$old = $img( 'raku-main.jpg' );
		$new = $img( 'raku-main-product.jpg' );
		if ( $new && $cur === $old ) {
			set_post_thumbnail( $rp->ID, $new );
			$log[] = 'ラクエラのアイキャッチを商品だけの写真に変更しました';
		}
	}

	/* ------------------------------------------------------------
	   3-g. オフローラ（Fグレード・Panasonic）── ユニットバス1本目
	   ------------------------------------------------------------ */
	/* v27：ポイント・オプションの列名が違っていたので、一度消して作り直します */
	$old_of = get_page_by_path( 'ofuroa', OBJECT, 'ymkrf_product' );
	if ( $old_of && get_option( 'ymkrf_ofuroa_fix' ) !== '3' ) {
		wp_delete_post( $old_of->ID, true );
		update_option( 'ymkrf_ofuroa_fix', '3' );
		$log[] = 'オフローラを作り直しました（標準工事内容の修正）';
	}

	if ( ! get_page_by_path( 'ofuroa', OBJECT, 'ymkrf_product' ) ) {

		$bid = wp_insert_post( array(
			'post_type'   => 'ymkrf_product',
			'post_status' => 'publish',
			'post_title'  => 'オフローラ',
			'post_name'   => 'ofuroa',
		) );

		if ( $bid && ! is_wp_error( $bid ) ) {

			$m0 = $missing;

			$f = array(
				'_ymkrf_catch'   => 'キレイがいつまでもつづく、心地よいくつろぎの空間へ',
				'_ymkrf_grade'   => 'Fグレード',
				'_ymkrf_name'    => 'オフローラ',
				'_ymkrf_size'    => '1616 １坪タイプ',
				'_ymkrf_work'    => '370000',
				'_ymkrf_item'    => '478000',
				'_ymkrf_days'    => '5',
				'_ymkrf_pt1'     => 'スミピカフロア',
				'_ymkrf_pt2'     => 'ささっと排水口',
				'_ymkrf_pt3'     => 'スキットドア',
				'_ymkrf_caution' => '※写真はイメージです。',
			);
			foreach ( $f as $k => $v ) update_post_meta( $bid, $k, $v );
			update_post_meta( $bid, '_ymkrf_total', 848000 );

			/* カラーの見出し（お風呂用の呼び方に差し替え） */
			$labels = array(
				'_ymkrf_lbl_colors' => '浴槽カラー',
				'_ymkrf_lbl_tops'   => 'エプロンカラー',
				'_ymkrf_lbl_sinks'  => '壁カラー（アクセントパネル）',
				'_ymkrf_lbl_c4'     => '壁カラー（周辺パネル）',
				'_ymkrf_lbl_c5'     => 'フロア（床）',
				'_ymkrf_lbl_c6'     => 'カウンター',
			);
			foreach ( $labels as $k => $v ) update_post_meta( $bid, $k, $v );

			$main = $img( 'ofuroa-main.jpg', 'Panasonic オフローラ 1616 １坪タイプ（ユニットバス）' );
			if ( $main ) set_post_thumbnail( $bid, $main );

			/* --- 色見本 --- */
			$sets = array(
				'_ymkrf_colors' => array(
					array( 'ofuroa-tub-purewhite.jpg', 'ピュアホワイト' ),
					array( 'ofuroa-tub-natural.jpg',   'ナチュラル' ),
				),
				'_ymkrf_tops' => array(
					array( 'ofuroa-apron-purewhite.jpg', 'ピュアホワイト' ),
					array( 'ofuroa-apron-beige.jpg',     'ミディアムベージュ' ),
					array( 'ofuroa-apron-gray.jpg',      'ミディアムグレー' ),
				),
				'_ymkrf_sinks' => array(
					array( 'ofuroa-accent-navy.jpg',     'ツイルネイビー' ),
					array( 'ofuroa-accent-bordeaux.jpg', 'ツイルボルドー' ),
				),
				'_ymkrf_c4' => array(
					array( 'ofuroa-panel-parallel.jpg', 'パラレルホワイト' ),
					array( 'ofuroa-panel-grace.jpg',    'グレイスホワイト' ),
				),
				'_ymkrf_c5' => array(
					array( 'ofuroa-floor-white.jpg', 'ミディアムホワイト' ),
					array( 'ofuroa-floor-gray.jpg',  'ミディアムグレー' ),
					array( 'ofuroa-floor-beige.jpg', 'ミディアムベージュ' ),
				),
				'_ymkrf_c6' => array(
					array( 'ofuroa-counter-white.jpg', 'ホワイト' ),
					array( 'ofuroa-counter-black.jpg', 'ブラック' ),
				),
			);
			foreach ( $sets as $key => $list ) {
				$rows = array();
				foreach ( $list as $r ) $rows[] = array( 'img' => $img( $r[0], $r[1] ), 'name' => $r[1] );
				update_post_meta( $bid, $key, $rows );
			}

			/* --- 標準仕様 --- */
			$specs = array(
				array( '',                          'FRP浴槽',                     '' ),
				array( 'ofuroa-spec-fan.jpg',       '天井換気扇',                  '' ),
				array( 'ofuroa-spec-lid.jpg',       '巻きフタ',                    '' ),
				array( 'ofuroa-spec-mirror.jpg',    'シンプルミラーⅡ／ライトシェルフ2段（ホワイト）', '' ),
				array( 'ofuroa-spec-counter.jpg',   'オーバルカウンター（W800mm）', '' ),
				array( 'ofuroa-spec-door.jpg',      '折戸ホワイト',                '' ),
				array( 'ofuroa-spec-faucet.jpg',    'ライン水栓（ホワイト）／シャワーヘッド', '' ),
				array( 'ofuroa-spec-hook.jpg',      'シャワーフック（樹脂）',      '' ),
				array( 'ofuroa-spec-light.jpg',     'フラット天井／サークルLED照明（1灯）', '' ),
				array( 'ofuroa-spec-bar.jpg',       '握りバーI型600ホワイト／A／タオル掛け', '' ),
			);
			$rows = array();
			foreach ( $specs as $r ) {
				$rows[] = array(
					'img'   => $r[0] ? $img( $r[0], $r[1] ) : '',
					'name'  => $r[1],
					'model' => $r[2],
				);
			}
			update_post_meta( $bid, '_ymkrf_specs', $rows );

			/* --- おすすめポイント ---
			   gsub / gttl を入れた行から新しいまとまりが始まります。
			   空にすると、ひとつ上の行と同じまとまりの Point 2、Point 3 になります。 */
			$feats = array(
				array(
					'gsub' => '汚れが落としやすい。',
					'gttl' => '「スミピカフロア」「ささっと排水口」',
					'ttl'  => '床のすみずみまですっきり。',
					'text' => '床の隅に目地がないので、汚れが落としやすい。'
					        . 'また、床の隅が立ち上がっているので防水性に優れ、汚れが入りにくく、おそうじラクラクです。',
					'img'  => 'ofuroa-point-floor.jpg',
				),
				array(
					'gsub' => '', 'gttl' => '',
					'ttl'  => '排水口のお掃除を簡単に、美しく保ちます。',
					'text' => '髪の毛が集まりやすく、捨てやすい形状のヘアキャッチャー。',
					'img'  => 'ofuroa-point-drain.jpg',
				),
				array(
					'gsub' => '足元すっきり、コンパクトなカウンター',
					'gttl' => '「オーバルカウンター」',
					'ttl'  => 'お掃除の動線がコンパクト。',
					'text' => 'カウンターの両脇が空いていて、下もすっきりしているので、手が届きやすく、おそうじラクラク。',
					'img'  => 'ofuroa-point-counter.jpg',
				),
				array(
					'gsub' => 'お掃除性と換気性能を両立。',
					'gttl' => '「スリムスキットドア」',
					'ttl'  => 'パッキンをなくし、換気口を上にして汚れにくく。',
					'text' => '水のかかりにくいドアの上に換気口を移動。ドア面材まわりのパッキンがなく、カビがはえにくい。',
					'img'  => 'ofuroa-point-door.jpg',
				),
				array(
					'gsub' => '', 'gttl' => '',
					'ttl'  => '浴室全体をしっかり換気します。',
					'text' => '入った空気がドアに沿うように下に流れて、浴室全体を換気します。',
					'img'  => 'ofuroa-point-air.jpg',
				),
			);
			$rows = array();
			foreach ( $feats as $r ) {
				$rows[] = array(
					'gsub' => $r['gsub'],
					'gttl' => $r['gttl'],
					'ttl'  => $r['ttl'],
					'text' => $r['text'],
					'note' => '',
					'img'  => $img( $r['img'], $r['ttl'] ),
					'img2' => '',
				);
			}
			update_post_meta( $bid, '_ymkrf_features', $rows );

			/* --- おすすめオプション --- */
			$opts = array(
				array( 'ofuroa-opt-heater.jpg',   'オートルーバー 暖房換気乾燥機（100V）', '71000',
				       'ムラ無くすばやく浴室を暖めます。※工事費込み' ),
				array( 'ofuroa-opt-tub.jpg',      'スゴピカ保温浴槽（浴槽保温あり）', '65000',
				       '美しいツヤとなめらかさで汚れにくく、「スゴくキレイ」が長持ち。' ),
				array( 'ofuroa-opt-slidebar.jpg', '握りバー兼用スライドバー', '20000',
				       'シャワーの位置を、好きな高さにスライドして調整できます。' ),
				array( 'ofuroa-opt-led.jpg',      'フラットラインLED照明（白色）', '16000',
				       'ライン状のスマートなフォルムで、バスルームを心地よく照らします。' ),
				array( 'ofuroa-opt-dressing.jpg', '脱衣場内装パック（1坪）天井・壁クロス張替え＋床クッションフロアー貼り替え', '80000',
				       '1坪まで' ),
				array( 'ofuroa-opt-dressing.jpg', '脱衣場内装パック（1坪）壁クロス張替え', '65000',
				       '1坪まで' ),
			);
			$rows = array();
			foreach ( $opts as $r ) {
				$rows[] = array(
					'img'   => $img( $r[0], $r[1] ),
					'name'  => $r[1],
					'text'  => $r[3],
					'price' => $r[2],
					'note'  => ( $r[1] === 'オートルーバー 暖房換気乾燥機（100V）' ) ? '※工事費込み' : '',
				);
			}
			update_post_meta( $bid, '_ymkrf_options', $rows );

			/* --- ヤマキシ標準工事内容（お風呂）--- */
			$works = array(
				array( '既存ユニットバス解体撤去工事', '古い浴槽の撤去にかかる工事です。' ),
				array( '産業廃棄物処理運搬工事',       '撤去した浴槽（ユニットバス）などを廃棄処分するためにかかる費用です。' ),
				array( '水道工事',                     '給水・給湯・排水の工事です。' ),
				array( '電気工事',                     '配線の工事です。' ),
				array( '木工事',                       '脱衣所の壁下地をつくる工事です。' ),
				array( 'ユニットバス組立設置',         '新しいユニットバスの組立・設置工事です。' ),
				array( '浴室壁面造作・内装工事',       '脱衣場側の壁面を、造作する工事です。その壁面のクロスやサニタリーボードなどの内装も含みます。' ),
				array( '換気扇取付工事',               '換気扇の取り付け工事です。' ),
				array( '浴室ドア枠造作工事',           '浴室のドア枠を造作します。' ),
			);
			$rows = array();
			foreach ( $works as $r ) $rows[] = array( 'name' => $r[0], 'text' => $r[1] );
			update_post_meta( $bid, '_ymkrf_works', $rows );

			wp_set_object_terms( $bid, 'bathroom',  'ymkrf_product_cat' );
			wp_set_object_terms( $bid, 'panasonic', 'ymkrf_maker' );
			wp_set_object_terms( $bid,
				array( 'nonoichi', 'komathu', 'hakui', 'shinkaga', 'kawakita', 'kanadu' ),
				'ymkrf_shop' );

			update_post_meta( $bid, '_ymkrf_img_missing', $missing - $m0 );
			$log[] = '商品「オフローラ」を登録しました → ' . get_permalink( $bid );
		}
	}


	/* ------------------------------------------------------------
	   3-h. ユニットバス 3商品（Eサザナ・Dリデア・Cラクヴィア）
	        ホームページの内容をそのまま入れています。
	        標準工事費はどの機種も一律370,000円（税込）です。
	   ------------------------------------------------------------ */

	/* お風呂の「ヤマキシ標準工事内容」（9項目）は全機種共通です */
	$bath_works = array(
		array( '既存ユニットバス解体撤去工事', '古い浴槽の撤去にかかる工事です。' ),
		array( '産業廃棄物処理運搬工事',       '撤去した浴槽（ユニットバス）などを廃棄処分するためにかかる費用です。' ),
		array( '水道工事',                     '給水・給湯・排水の工事です。' ),
		array( '電気工事',                     '配線の工事です。' ),
		array( '木工事',                       '脱衣所の壁下地をつくる工事です。' ),
		array( 'ユニットバス組立設置',         '新しいユニットバスの組立・設置工事です。' ),
		array( '浴室壁面造作・内装工事',       '脱衣場側の壁面を、造作する工事です。その壁面のクロスやサニタリーボードなどの内装も含みます。' ),
		array( '換気扇取付工事',               '換気扇の取り付け工事です。' ),
		array( '浴室ドア枠造作工事',           '浴室のドア枠を造作します。' ),
	);

	/* 脱衣場内装パックは3機種とも同じ内容・同じ金額です */
	$bath_dressing = array(
		array( 'opt-dressing', '脱衣場内装パック（1坪）天井・壁クロス張替え＋床クッションフロアー貼り替え', '80000', '1坪まで', '' ),
		array( 'opt-dressing', '脱衣場内装パック（1坪）壁クロス張替え', '65000', '1坪まで', '' ),
	);

	$bath_products = array(

		/* ===== Eグレード サザナ Nタイプ（TOTO） ===== */
		array(
			'slug'   => 'sazana-n',
			'prefix' => 'sazana',
			'title' => 'サザナ Nタイプ',
			'meta'  => array(
				'_ymkrf_catch'   => '浴槽に身をまかせた瞬間からリラックス。',
				'_ymkrf_grade'   => 'Eグレード',
				'_ymkrf_name'    => 'サザナ Nタイプ',
				'_ymkrf_size'    => 'Nタイプ 1坪サイズ',
				'_ymkrf_work'    => '370000',
				'_ymkrf_item'    => '558000',
				'_ymkrf_days'    => '5',
				'_ymkrf_pt1'     => 'ゆるりら浴槽',
				'_ymkrf_pt2'     => 'カラリ床',
				'_ymkrf_pt3'     => 'お掃除ラクラク',
				'_ymkrf_caution' => '※写真はイメージです。',
			),
			'total'  => 928000,
			'maker'  => 'toto',
			'shops'  => array(),
			'main'   => array( 'main', 'TOTO サザナ Nタイプ 1坪サイズ（ユニットバス）' ),
			'labels' => array(
				'_ymkrf_lbl_colors' => '浴槽カラー（全4色）',
				'_ymkrf_lbl_tops'   => 'エプロンカラー（全5色）',
				'_ymkrf_lbl_sinks'  => '壁カラー：アクセントパネル（全4色）',
				'_ymkrf_lbl_c4'     => '壁カラー：周辺パネル（全3色）',
				'_ymkrf_lbl_c5'     => 'フロア：カラリ床（全3色）',
				'_ymkrf_lbl_c6'     => 'カウンター（全3色）',
			),
			'sets' => array(
				'_ymkrf_colors' => array(
					array( 'tub-white', 'ホワイト' ),
					array( 'tub-beige', 'ベージュN' ),
					array( 'tub-pink',  'ピンクN' ),
					array( 'tub-aqua',  'アクアN' ),
				),
				'_ymkrf_tops' => array(
					array( 'apron-white', 'ホワイト' ),
					array( 'apron-beige', 'ベージュ' ),
					array( 'apron-pink',  'ピンクN' ),
					array( 'apron-aqua',  'アクアN' ),
					array( 'apron-black', 'ブラック' ),
				),
				'_ymkrf_sinks' => array(
					array( 'accent-ebony',    'プリエエボニー' ),
					array( 'accent-darkgray', 'プリエダークグレー' ),
					array( 'accent-aqua',     'プリエアクア' ),
					array( 'accent-beige',    'プリエベージュ' ),
				),
				'_ymkrf_c4' => array(
					array( 'wall-white',      'プリエホワイト' ),
					array( 'wall-beige',      'プリエベージュ' ),
					array( 'wall-basiswhite', 'ベーシスホワイト' ),
				),
				'_ymkrf_c5' => array(
					array( 'floor-white',     'ホワイト' ),
					array( 'floor-beige',     'ベージュ' ),
					array( 'floor-lightgray', 'ライトグレー' ),
				),
				'_ymkrf_c6' => array(
					array( 'counter-white', 'ホワイト' ),
					array( 'counter-beige', 'ベージュN' ),
					array( 'counter-black', 'ブラック' ),
				),
			),
			'specs' => array(
				array( 'spec-fan',     '天井換気扇', '' ),
				array( 'spec-tub',     'ゆるリラ浴槽（FRP浴槽）', '' ),
				array( 'spec-mirror',  'お掃除ラクラク鏡 四角ミラー', '' ),
				array( 'spec-lid',     'シャッターふた', '' ),
				array( 'spec-counter', 'お掃除ラクラクカウンター', '' ),
				array( 'spec-door',    '折戸ホワイト（W800mm）', '' ),
				array( 'spec-hanger',  'スライドバーなし シャワーハンガー2個', '' ),
				array( 'spec-faucet',  'スッキリ棚水栓（棚W215）', '' ),
				array( 'spec-shower',  'コンフォートウエーブシャワー（メタル調）', '' ),
				array( 'spec-light',   '平天井 シーリング照明（LED1灯）', '' ),
				array( 'spec-towel',   'タオル掛け（角型シルバー）', '' ),
				array( 'spec-shelf',   'セパレート収納棚 W185 正面2段', '' ),
			),
			'feats' => array(
				array( '「気持ちいい」を科学したらこのカタチになりました。', '「ゆるりら浴」',
				       '身も心もリラックス', '気持ちよさを支えるヒミツは「4点支持」設計。身体を支える面が増えるため、1カ所にかかる力が小さくなります。', 'point-relax' ),
				array( '', '', '頭・首にフィットするヘッドレスト', '浴槽と一体化した形状で頭と首を自然にサポートします。', 'point-headrest' ),
				array( '', '', '背中を包み込むカーブ', '背中への圧力を分散し、かかる負荷を軽減します。', 'point-curve' ),
				array( '翌朝にはカラリ。', '「カラリ床」',
				       '乾きやすいからカビにくい', 'タテヨコに刻まれたパターンで、表面の水を誘導。翌朝にはカラリと乾き、靴下のまま入っても大丈夫です。', 'point-karari' ),
				array( 'すみずみまで洗える。', '「おそうじラクラクカウンター」',
				       'ぐるりと一周手が届く、浮島のようなデザイン', '掃除のしにくかったカウンターの奥や側面、カウンター下の壁際まで手が届き、お掃除が簡単です。', 'point-counter' ),
			),
			'opts' => array(
				array( 'opt-heater',    '100V換気暖房乾燥機', '112000', '浴室全体を暖めます。換気・暖房・乾燥・涼風の4つの機能付き。', '※工事費込み' ),
				array( 'opt-mahobin',   '魔法びん浴槽', '20000', '湯張りから4時間経っても、湯温の低下は2.5℃以内。ずっとあたたか。※メーカー調べ', '' ),
				array( 'opt-hokkarari', 'お掃除ラクラクほっカラリ床', '34000', 'クッション性にすぐれ、まるで畳のような足触り。翌朝にはカラリと乾きます。', '' ),
				array( 'opt-shower',    'コンフォートウエーブクリックシャワー', '5000', '手元のボタンで一時止水ができて、さらにエコ。', '' ),
			),
		),

		/* ===== Dグレード リデア Mタイプ（LIXIL） ===== */
		array(
			'slug'   => 'lidea-m',
			'prefix' => 'lidea',
			'title' => 'リデア Mタイプ',
			'meta'  => array(
				'_ymkrf_catch'   => 'バスルームがいつもキレイで気持ちいい。',
				'_ymkrf_grade'   => 'Dグレード',
				'_ymkrf_name'    => 'リデア Mタイプ',
				'_ymkrf_size'    => 'Mタイプ 1坪サイズ',
				'_ymkrf_work'    => '370000',
				'_ymkrf_item'    => '628000',
				'_ymkrf_days'    => '5',
				'_ymkrf_pt1'     => 'あたたか',
				'_ymkrf_pt2'     => 'いつもキレイ',
				'_ymkrf_pt3'     => 'お掃除カンタン',
				'_ymkrf_caution' => '※画像はイメージです。',
			),
			'total'  => 998000,
			'maker'  => 'lixil',
			'shops'  => array( 'komathu', 'hakui', 'kawakita', 'asahi', 'kanadu' ),
			'main'   => array( 'main', 'LIXIL リデア Mタイプ 1坪サイズ（ユニットバス）' ),
			'labels' => array(
				'_ymkrf_lbl_colors' => '浴槽カラー（全4色）',
				'_ymkrf_lbl_tops'   => 'エプロンカラー（全6色）',
				'_ymkrf_lbl_sinks'  => '壁カラー：アクセントパネル（ハイクラス全22色）',
				'_ymkrf_lbl_c5'     => 'フロア：キレイサーモフロア（全3色）',
				'_ymkrf_lbl_c6'     => 'カウンター（全2色）',
			),
			'sets' => array(
				'_ymkrf_colors' => array(
					array( 'tub-white', 'ホワイト／NW1' ),
					array( 'tub-beige', 'ベージュ／Y71' ),
					array( 'tub-green', 'グリーン／G94' ),
					array( 'tub-pink',  'ピンク／P91' ),
				),
				'_ymkrf_tops' => array(
					array( 'apron-white', 'ホワイト' ),
					array( 'apron-pink',  'ピンク' ),
					array( 'apron-green', 'グリーン' ),
					array( 'apron-gray',  'グレー' ),
					array( 'apron-beige', 'ベージュ' ),
					array( 'apron-black', 'ブラック' ),
				),
				'_ymkrf_sinks' => array(
					array( 'wall-woodgrain-light',   'ウッドグレインライト／HN642（光沢）' ),
					array( 'wall-shinewood-white',   'シャインウッドホワイト／HN651（光沢）' ),
					array( 'wall-cherry',            'チェリー／HN661（光沢）' ),
					array( 'wall-walnut',            'ウォールナット／HN662（光沢）' ),
					array( 'wall-dark-walnut',       'ダークウォールナット／HN663（光沢）' ),
					array( 'wall-white-stone',       'ホワイトストーン／HN751（光沢）' ),
					array( 'wall-marble-beige',      'マーブルベージュ／HN988（光沢）' ),
					array( 'wall-neopro-stone',      'ネオプロストーン／HN491（光沢）' ),
					array( 'wall-stone-mosaic-dark', 'ストーンモザイクダーク／HN735（光沢）' ),
					array( 'wall-hazy-silver',       'ヘイジーシルバー／HN985（光沢）' ),
					array( 'wall-stone-shell-gray',  'ストーンシェルグレー／HN987（光沢）' ),
					array( 'wall-crumb-white',       'クルムホワイト／HN986（光沢）' ),
					array( 'wall-green-leaf',        'グリーンリーフ／HN984（光沢）' ),
					array( 'wall-mosaic-aqua',       'モザイクアクア／HN954（光沢）' ),
					array( 'wall-flower-scene',      'フラワーシーン／HN951（光沢）' ),
					array( 'wall-random-wood',       'ランダムウッド／HT541（つや消し）' ),
					array( 'wall-stucco-beige',      'スタッコベージュ／HT613（つや消し）' ),
					array( 'wall-moltio-dark',       'モルティオダーク／HT614（つや消し）' ),
					array( 'wall-herringbone-mint',  'ヘリンボーンミント／HT615（つや消し）' ),
					array( 'wall-silver-gray',       'シルバーグレー／HT611（つや消し）' ),
					array( 'wall-stain-blue',        'ステインブルー／HT612（つや消し）' ),
					array( 'wall-mirror-white',      '鏡面ホワイト／HN301（光沢）' ),
				),
				'_ymkrf_c5' => array(
					array( 'floor-white', 'ホワイト／N86' ),
					array( 'floor-beige', 'ベージュ／Y71' ),
					array( 'floor-gray',  'グレー／U61' ),
				),
				'_ymkrf_c6' => array(
					array( 'counter-white', 'ファブリック調ホワイト' ),
					array( 'counter-black', 'ファブリック調ブラック' ),
				),
			),
			'specs' => array(
				array( 'spec-fan',      '天井換気扇', '' ),
				array( 'spec-tub',      'ミナモ浴槽（FRP浴槽）', '浴槽内握りバーは付いておりません' ),
				array( 'spec-counter',  'まる洗いカウンター（ワイドタイプ）', '' ),
				array( 'spec-thermo',   'サーモバスS', '' ),
				array( 'spec-door',     '折戸ホワイト（W800mm）', '' ),
				array( 'spec-slidebar', 'フルフォールスライドバー（メタル調）1160L＋シャワーホースフック', '' ),
				array( 'spec-faucet',   'ワイドレバー水栓 エコアクアシャワー（メタル調ホワイト）', '' ),
				array( 'spec-mirror',   'タテ型ミラー（3080）', '' ),
				array( 'spec-shelf',    'マグネットシェルフW180 ホワイト（2段）', '' ),
				array( 'spec-light',    '平天井（廻り縁なし）／パネルダウンライト（2灯）', '' ),
			),
			'feats' => array(
				array( '「キレイ」も「あたたかい」も叶える床。', '「キレイサーモフロア」',
				       'スポンジでお掃除ラクラク', '汚れにくい表面処理と、溝の奥までスポンジが届きやすい形状で、お掃除がラクラクです。', 'point-floor' ),
				array( '', '', '足元が冷ヤッとしない', '中空バルーンを含む独自の断熱層で、寒い季節でも床が冷たく感じにくくなっています。', 'point-cold' ),
				array( '心地よい超節水シャワー。', '「エコアクアシャワー」',
				       'ボリュームのある浴び心地と節水を両立', '散水板の穴の大きさや位置を工夫し、水滴に空気を含ませることで、少ない水でもたっぷり感のある浴び心地に。GOOD DESIGN 2020受賞のシャワーヘッドです。', 'point-shower' ),
				array( '外して洗えて、裏までキレイ。', '「まる洗いカウンター」',
				       '壁掛けできるから、洗い場を広く使える', 'カウンターをワンアクションで折りたたんで、壁に掛けることができます。', 'point-counter' ),
				array( '', '', '外して丸洗いできる', '洗いにくい壁や床も、ラクな姿勢で洗えます。', 'point-wash' ),
				array( 'パッとゴミがまとまる。', '「パッとくるりんポイ排水口」',
				       '渦でまとまったゴミをポイっとするだけ', '浴槽の排水を利用してできる「うず」で、ゴミや毛髪をまとめて捨てやすくします。', 'point-drain' ),
			),
			'opts' => array(
				array( 'opt-heater',  '100V換気乾燥暖房機', '94000', '浴室全体を暖めます。換気・暖房・乾燥・涼風の4つの機能付き。', '※工事費込み' ),
				array( 'opt-mirror',  'ワイドミラー（キレイ鏡）', '11000', 'ひろびろとした空間を演出するワイドミラー。汚れをはじく仕様です。', '' ),
				array( 'opt-atataka', 'あたたかパック', '19000', '浴室の天井・壁・洗い場に保温材をプラスして保温性を高めます。', '' ),
				array( 'opt-aquajet', 'アクアジェット（2穴）＋浴槽パン', '187000', 'ジェットの刺激で身体をほぐし、リフレッシュ。低騒音ポンプを採用。', '' ),
			),
		),

		/* ===== Cグレード ラクヴィア（クリナップ） ===== */
		array(
			'slug'   => 'rakuvia',
			'prefix' => 'rakuvia',
			'title' => 'ラクヴィア',
			'meta'  => array(
				'_ymkrf_catch'   => '体の芯まで暖まる、あったかバスルーム。',
				'_ymkrf_grade'   => 'Cグレード',
				'_ymkrf_name'    => 'ラクヴィア',
				'_ymkrf_size'    => '1坪サイズ',
				'_ymkrf_work'    => '370000',
				'_ymkrf_item'    => '728000',
				'_ymkrf_days'    => '5',
				'_ymkrf_pt1'     => 'まるごと保温',
				'_ymkrf_pt2'     => '足ピタフロア',
				'_ymkrf_pt3'     => '機能性を追求',
				'_ymkrf_caution' => '※写真はイメージです。',
			),
			'total'  => 1098000,
			'maker'  => 'cleanup',
			'shops'  => array( 'nonoichi', 'asahi' ),
			'main'   => array( 'main', 'クリナップ ラクヴィア 1坪サイズ（ユニットバス）' ),
			'labels' => array(
				'_ymkrf_lbl_colors' => '浴槽カラー（全5色）',
				'_ymkrf_lbl_tops'   => 'エプロンカラー（全3色）',
				'_ymkrf_lbl_sinks'  => '壁カラー：アクセントパネル（全15色）',
				'_ymkrf_lbl_c5'     => 'フロア：足ピタフロア（全3色）',
				'_ymkrf_lbl_c6'     => 'カウンター（全4色）',
			),
			'sets' => array(
				'_ymkrf_colors' => array(
					array( 'tub-white',  'ホワイト' ),
					array( 'tub-gray',   'グレー' ),
					array( 'tub-greige', 'グレージュ' ),
					array( 'tub-aqua',   'アクア' ),
					array( 'tub-pink',   'ピンク' ),
				),
				'_ymkrf_tops' => array(
					array( 'apron-white',  'ホワイト' ),
					array( 'apron-gray',   'グレー' ),
					array( 'apron-greige', 'グレージュ' ),
				),
				'_ymkrf_sinks' => array(
					array( 'accent-woodtile',            'ウッドタイル（TILE 鏡面仕上げ）' ),
					array( 'accent-stonetile-beige',     'ストーンタイルベージュ（TILE 鏡面仕上げ）' ),
					array( 'accent-stonetile-gray',      'ストーンタイルグレー（TILE 鏡面仕上げ）' ),
					array( 'accent-glasstile-white',     'グラスタイルホワイト（TILE 鏡面仕上げ）' ),
					array( 'accent-glasstile-mint',      'グラスタイルミント（TILE 鏡面仕上げ）' ),
					array( 'accent-glasstile-cobalt',    'グラスタイルコバルト（TILE 鏡面仕上げ）' ),
					array( 'accent-calm-ice',            'カルムラインアイス（TEXTURE 鏡面仕上げ）' ),
					array( 'accent-calm-pastel',         'カルムラインパステル（TEXTURE 鏡面仕上げ）' ),
					array( 'accent-calm-white',          'カルムラインホワイト（TEXTURE 鏡面仕上げ）' ),
					array( 'accent-calm-gray',           'カルムライングレー（TEXTURE 鏡面仕上げ）' ),
					array( 'accent-tenderwood-medium',   'テンダーウッドミディアム（WOOD 鏡面仕上げ）' ),
					array( 'accent-tenderwood-natural',  'テンダーウッドナチュラル（WOOD 鏡面仕上げ）' ),
					array( 'accent-tenderwood-white',    'テンダーウッドホワイト（WOOD 鏡面仕上げ）' ),
					array( 'accent-darkparlor',          'ダークパーラー（STONE 鏡面仕上げ）' ),
					array( 'accent-calacatta-white',     'カラカッタホワイト（STONE 鏡面仕上げ）' ),
				),
				'_ymkrf_c5' => array(
					array( 'floor-white',  'ホワイト' ),
					array( 'floor-gray',   'グレー' ),
					array( 'floor-greige', 'グレージュ' ),
				),
				'_ymkrf_c6' => array(
					array( 'counter-mediumwood',  'ミディアムウッド' ),
					array( 'counter-naturalwood', 'ナチュラルウッド' ),
					array( 'counter-whitestone',  'ホワイトストーン' ),
					array( 'counter-darkstone',   'ダークストーン' ),
				),
			),
			'specs' => array(
				array( 'spec-fan',         '天井換気扇', '' ),
				array( 'spec-tub',         'ストレートラグーン浴槽（FRP保温浴槽）', '' ),
				array( 'spec-mirror',      'スリムロングミラー（ヒーターなし）', '' ),
				array( 'spec-lid',         '断熱組フタ（1点止めフック）', '' ),
				array( 'spec-counter',     'とってもクリンカウンター', '' ),
				array( 'spec-door',        '折戸ホワイト（W800mm）', '' ),
				array( 'spec-shelf',       '手元収納棚', '' ),
				array( 'spec-faucet',      '壁出しメッキ水栓 シルクベールシャワー スライドバー付', '' ),
				array( 'spec-haircatcher', '樹脂ヘアキャッチャー', '' ),
				array( 'spec-light',       'フラット天井／天井付け照明（LED）', '' ),
			),
			'feats' => array(
				array( 'あたたかさを逃さない。', '「まるごと保温」',
				       '浴室を保温材で包みました', '入浴後30分経っても、浴室内に暖かさがとどまります。サンドイッチ天井・サンドイッチパネルはそれぞれ2.5cmの保温材入りです。', 'point-hoon' ),
				array( '', '', '高断熱浴槽', '追い焚き回数を減らし、省エネにも効果的です。', 'point-tub' ),
				array( 'みんなに配慮した安心感。', '「足ピタフロア」',
				       'いつも清潔', '床の溝までスポンジが届く形状です。水滴が残りにくく、乾きやすいのが特長です。', 'point-floor' ),
				array( '', '', '転倒事故を防ぎます', '特殊な凹凸構造で、高いすべり止め効果を発揮します。', 'point-slip' ),
				array( '心地よさと機能性を追求。', '「シルクベールシャワー」',
				       'シルクのような肌触り', '3種類の水流を組み合わせた独自構造。ウルトラファインバブルがなめらかな肌触りで毛穴汚れまで洗い流します。', 'point-shower' ),
				array( '', '', '水温が低下しにくい', 'ミスト吐水をシャワー吐水が包み込むことで、温度が低下しにくい設計です。', 'point-temp' ),
			),
			'opts' => array(
				array( 'opt-heater',      '100V換気乾燥暖房機', '77000', '浴室全体を暖めます。換気・暖房・乾燥・涼風の4つの機能付き。', '※工事費込み' ),
				array( 'opt-haircatcher', 'クリンヘアキャッチャー', '6000', '美コートで汚れを浮かしてサッと流す。髪の毛なども片手で処理できます。（ステンレス）', '' ),
				array( 'opt-slidedoor',   '片引戸', '50000', '開け閉めしやすくスムーズに出入りできます。（W800）', '' ),
				array( 'opt-handrail',    'I型手すり（600）', '10000', '浴槽横や出入り口に。', '' ),
			),
		),
		array(
			'slug'   => 'sazana-t',
			'prefix' => 'sazanat',
			'title'  => 'サザナ Tタイプ',
			'meta'   => array(
				'_ymkrf_catch' => '心地よさにつつまれて、至福のひとときを',
				'_ymkrf_grade' => 'Bグレード',
				'_ymkrf_name' => 'サザナ Tタイプ',
				'_ymkrf_size' => 'Tタイプ 1坪サイズ',
				'_ymkrf_work' => '370000',
				'_ymkrf_item' => '828000',
				'_ymkrf_days' => '5',
				'_ymkrf_pt1' => 'ほっカラリ床',
				'_ymkrf_pt2' => '魔法びん浴槽',
				'_ymkrf_pt3' => 'ずっとあったか',
				'_ymkrf_caution' => '※写真はイメージです。',
			),
			'total'  => 1198000,
			'maker'  => 'toto',
			'shops'  => array('hakui', 'kawakita', 'asahi'),
			'main'   => array( 'main', 'TOTO サザナ Tタイプ 1坪サイズ（ユニットバス）' ),
			'labels' => array(
				'_ymkrf_lbl_colors' => '浴槽カラー（全5色）',
				'_ymkrf_lbl_tops' => 'エプロンカラー（全5色）',
				'_ymkrf_lbl_sinks' => '壁カラー：アクセントパネル（全22色）',
				'_ymkrf_lbl_c4' => '壁カラー：周辺パネル（全3色）',
				'_ymkrf_lbl_c5' => 'フロア：ほっカラリ床（全14色）',
				'_ymkrf_lbl_c6' => 'カウンター（全3色）',
			),
			'sets' => array(
				'_ymkrf_colors' => array(
					array( 'tub-white', 'ジュエリーホワイトN' ),
					array( 'tub-cream', 'ジュエリークリームN' ),
					array( 'tub-pink', 'ジュエリーピンクN2' ),
					array( 'tub-aqua', 'ジュエリーアクアN' ),
					array( 'tub-black', 'ジュエリーブラック' ),
				),
				'_ymkrf_tops' => array(
					array( 'apron-white', 'ジュエリーホワイトN' ),
					array( 'apron-cream', 'ジュエリークリームN' ),
					array( 'apron-pink', 'ジュエリーピンクN2' ),
					array( 'apron-aqua', 'ジュエリーアクアN' ),
					array( 'apron-black', 'ジュエリーブラック' ),
				),
				'_ymkrf_sinks' => array(
					array( 'ac-timber-green', 'ティンバーグリーン（鏡面）' ),
					array( 'ac-botanic-glass-green', 'ボタニックグラスグリーン（鏡面）' ),
					array( 'ac-robust-matt-greige', 'ロブストマットグレージュ（つや消し）' ),
					array( 'ac-robust-gray', 'ロブストグレー（鏡面）' ),
					array( 'ac-rifle-white', 'リフルホワイト（鏡面）' ),
					array( 'ac-rifle-brown', 'リフルブラウン（鏡面）' ),
					array( 'ac-marquina-gray', 'マルキーナグレー（鏡面）' ),
					array( 'ac-lutish-pink', 'ルティシュピンク（鏡面）' ),
					array( 'ac-facet-beige', 'ファセットベージュ（鏡面）' ),
					array( 'ac-falty-wood', 'ファルティウッド（鏡面）' ),
					array( 'ac-tarsia-beige', 'タルシアベージュ（鏡面）' ),
					array( 'ac-nordic-graywood', 'ノルディグレーウッド（鏡面）' ),
					array( 'ac-versacy-blue', 'ベルセシーブルー（鏡面）' ),
					array( 'ac-grani-gray', 'グラーニグレー（つや消し）' ),
					array( 'ac-material-aroma-green', 'マテリアルアロマグリーン（鏡面）' ),
					array( 'ac-material-aroma-pink', 'マテリアルアロマピンク（鏡面）' ),
					array( 'ac-prism-blue', 'プリズムブルー（鏡面）' ),
					array( 'ac-seiran', 'セイラン（鏡面）' ),
					array( 'ac-clear-lightgray', 'クレアライトグレー（鏡面）' ),
					array( 'ac-grayish-walnut', 'グレイッシュウォルナット（鏡面）' ),
					array( 'ac-flore-beige', 'フロールベージュ（つや消し）' ),
					array( 'ac-savanna-gray', 'サバナグレー（つや消し）' ),
				),
				'_ymkrf_c4' => array(
					array( 'wall-white', 'プリエホワイト' ),
					array( 'wall-basiswhite', 'ベーシスホワイト' ),
					array( 'wall-beige', 'プリエベージュ' ),
				),
				'_ymkrf_c5' => array(
					array( 'floor-rug-white', 'ホワイト（ラグ調）' ),
					array( 'floor-rug-beige', 'ベージュ（ラグ調）' ),
					array( 'floor-rug-lightgray', 'ライトグレー（ラグ調）' ),
					array( 'floor-rug-brown', 'ブラウン（ラグ調）' ),
					array( 'floor-tile-white', 'ホワイト（タイル調）' ),
					array( 'floor-tile-beige', 'ベージュ（タイル調）' ),
					array( 'floor-tile-lightgray', 'ライトグレー（タイル調）' ),
					array( 'floor-tile-paleblue', 'ペールブルー（タイル調）' ),
					array( 'floor-tile-coral', 'コーラル（タイル調）' ),
					array( 'floor-tile-lightbrown', 'ライトブラウン（タイル調）' ),
					array( 'floor-tile-black', 'ブラック（タイル調）' ),
					array( 'floor-white', 'ホワイト' ),
					array( 'floor-beige', 'ベージュ' ),
					array( 'floor-lightgray', 'ライトグレー' ),
				),
				'_ymkrf_c6' => array(
					array( 'counter-white', 'ホワイト' ),
					array( 'counter-beige', 'ベージュN' ),
					array( 'counter-black', 'ブラック' ),
				),
			),
			'specs' => array(
				array( 'spec-fan', '天井換気扇', '' ),
				array( 'spec-tub', 'ゆるリラ浴槽 お掃除ラクラク人大浴槽', '' ),
				array( 'spec-mirror', 'お掃除ラクラク鏡 フレーム付縦長ミラー', '' ),
				array( 'spec-lid', 'ラクかるふろふた（断熱）', '' ),
				array( 'spec-counter', 'お掃除ラクラクカウンター', '' ),
				array( 'spec-door', '折戸ホワイト（W800mm）', '' ),
				array( 'spec-showerbar', 'コンフォートシャワーバー', '' ),
				array( 'spec-faucet', 'スッキリ棚水栓（棚W300）／コンフォートウエーブシャワー（メタル調）', '' ),
				array( 'spec-towel', 'タオル掛け（ホワイト）', '' ),
				array( 'spec-light', '平天井 シーリング照明（LED1灯）', '' ),
			),
			'feats' => array(
				array( '一歩目から“ほっ”。', '「ほっカラリ床」', '入った瞬間から心地よい', 'クッション性にすぐれ、まるで畳のような足触り。ひざをついても痛くありません。', 'point-soft' ),
				array( '', '', 'ヒヤッとしない、W断熱構造', '床裏からの冷気をシャットアウト。優れた断熱性能で、室温とほぼ同じ温度を実現します。', 'point-warm' ),
				array( '', '', '翌朝には、カラリ', 'タテヨコに刻まれたパターンで、表面の水を誘導。翌朝にはカラリと乾き、靴下のまま入っても大丈夫です。', '' ),
				array( 'ずっとあったか。', '「魔法びん浴槽」', '4時間経ってもあたたかい', '湯張りから4時間経っても、湯温の低下は2.5℃以内。時間が経っても、すぐにお風呂に入れます。', 'point-mahobin' ),
				array( '', '', '残り湯は洗濯にぴったり', '魔法びん浴槽なら翌朝でもぬるま湯の状態。生地を傷めにくく、洗剤の力を引き出すと言われる、洗濯にぴったりの温度です。', 'point-laundry' ),
			),
			'opts' => array(
				array( 'opt-heater', '100V換気暖房乾燥機', '112000', '浴室全体を暖めます。換気・暖房・乾燥・涼風の4つの機能付き。（三乾王 ヒカルリモコン付）', '※工事費込み' ),
				array( 'opt-wiper', '床ワイパー洗浄（きれい除菌水）', '69000', 'カビ・ピンク汚れの発生を抑え、床まわりのきれいがつづきます。', '' ),
				array( 'opt-clearkeep', '浴室クリアキープ洗浄（きれい除菌水）', '101000', '拡散されたきれい除菌水の成分が、カビやピンク汚れを抑制します。', '※三乾王または暖房換気扇を同時にお選びください' ),
				array( 'opt-autowash', 'おそうじ浴槽', '201000', 'スイッチひとつで浴槽を自動洗浄します。', '※ブローバスとの同時選択はできません' ),
			),
		),
		array(
			'slug'   => 'lidea-b',
			'prefix' => 'lideab',
			'title'  => 'リデア Bタイプ',
			'meta'   => array(
				'_ymkrf_catch' => 'くつろぎの浴室へエスコート',
				'_ymkrf_grade' => 'Aグレード',
				'_ymkrf_name' => 'リデア Bタイプ',
				'_ymkrf_size' => 'Bタイプ 1坪サイズ',
				'_ymkrf_work' => '370000',
				'_ymkrf_item' => '928000',
				'_ymkrf_days' => '5',
				'_ymkrf_pt1' => 'うるつや浄水',
				'_ymkrf_pt2' => 'いつもキレイ',
				'_ymkrf_pt3' => 'お掃除カンタン',
				'_ymkrf_caution' => '※画像はイメージです。',
			),
			'total'  => 1298000,
			'maker'  => 'lixil',
			'shops'  => array('nonoichi'),
			'main'   => array( 'main', 'LIXIL リデア Bタイプ 1坪サイズ（ユニットバス）' ),
			'labels' => array(
				'_ymkrf_lbl_colors' => '浴槽カラー（全3色・人造大理石パールクォーツ浴槽）',
				'_ymkrf_lbl_tops' => 'エプロンカラー（全6色）',
				'_ymkrf_lbl_sinks' => '壁カラー：アクセントパネル（プレミアムIクラス 全9色）',
				'_ymkrf_lbl_c4' => '収納棚：スマートエスコートバー（全3色）',
				'_ymkrf_lbl_c5' => 'フロア：キレイサーモフロア（全3色）',
				'_ymkrf_lbl_c6' => 'カウンター（全2色）',
			),
			'sets' => array(
				'_ymkrf_colors' => array(
					array( 'tub-white', 'ホワイト／CW1' ),
					array( 'tub-black', 'ブラック／CN1' ),
					array( 'tub-beige', 'ベージュ／CY1' ),
				),
				'_ymkrf_tops' => array(
					array( 'apron-white', 'ホワイト' ),
					array( 'apron-pink', 'ピンク' ),
					array( 'apron-green', 'グリーン' ),
					array( 'apron-gray', 'グレー' ),
					array( 'apron-beige', 'ベージュ' ),
					array( 'apron-black', 'ブラック' ),
				),
				'_ymkrf_sinks' => array(
					array( 'ac-calcatta-gold', 'カルカッタゴールド' ),
					array( 'ac-flower-garden', 'フラワーガーデン' ),
					array( 'ac-cuore-poplar', 'クオーレポプラ' ),
					array( 'ac-roughsawn-wood', 'ラフソーンウッド' ),
					array( 'ac-stone-border', 'ストーンボーダー' ),
					array( 'ac-grace-pattern', 'グレースパターン' ),
					array( 'ac-quercia-taupe', 'クオーチェトープ' ),
					array( 'ac-paradiso-black', 'パラディソブラック' ),
					array( 'ac-mani-greige', 'マニグレージュ' ),
				),
				'_ymkrf_c4' => array(
					array( 'bar-white', 'ファブリック調ホワイト' ),
					array( 'bar-black', 'ファブリック調ブラック' ),
					array( 'bar-brown', 'レザー調ブラウン' ),
				),
				'_ymkrf_c5' => array(
					array( 'floor-white', 'ホワイト／N86' ),
					array( 'floor-beige', 'ベージュ／Y71' ),
					array( 'floor-gray', 'グレー／U61' ),
				),
				'_ymkrf_c6' => array(
					array( 'counter-white', 'ファブリック調ホワイト' ),
					array( 'counter-black', 'ファブリック調ブラック' ),
				),
			),
			'specs' => array(
				array( 'spec-atataka', 'あたたかパック', '' ),
				array( 'spec-tub', 'ミナモ浴槽（人造大理石パールクォーツ浴槽）', '浴槽内握りバーは付いておりません' ),
				array( 'spec-counter', 'まる洗いカウンター（ワイドタイプ）', '' ),
				array( 'spec-thermo', 'サーモバスS', '' ),
				array( 'spec-escortbar', 'スマートエスコートバー メタルシェルフ（洗い場側）', '' ),
				array( 'spec-door', '折戸ホワイト（W800mm）', '' ),
				array( 'spec-grip', 'スライドフック付握りバー（メタルタイプ）', '' ),
				array( 'spec-faucet', 'ワイドレバー水栓 スイッチ付 エコアクアシャワー', '' ),
				array( 'spec-mirror', 'タテ型ミラー（3080）／握りバーI型 ホワイト600L', '' ),
				array( 'spec-light', '内組平天井・天井換気扇 パネルダウンライト（2灯）', '' ),
			),
			'feats' => array(
				array( '座ったままで手が届く。', '「スマートエスコートバー」（洗い場側）', '浴槽内の動作を効率的に', '座ったままで水栓やシャンプーに楽に手が届き、バーの好きな位置にシャワーを仮置きできるレイアウト。洗い場から浴槽への移動もエスコートします。', 'point-escort' ),
				array( '髪や肌へのダメージを抑える。', '「うるつや浄水」', '髪や肌を健やかに保つ', '専用の浄水カートリッジが水道水に含まれる残留塩素を低減して、髪や肌へのダメージを抑えます。※カートリッジは別売りです', 'point-jousui' ),
				array( '外して洗えて、裏までキレイ。', '「まる洗いカウンター」', '壁掛けできるから、洗い場を広く使える', 'カウンターをワンアクションで折りたたんで、壁に掛けることができます。', 'point-counter' ),
				array( '', '', '外して丸洗いできる', '洗いにくい壁や床も、ラクな姿勢で洗えます。', 'point-wash' ),
				array( 'パッとゴミがまとまる。', '「パッとくるりんポイ排水口」', '渦でまとまったゴミをポイっとするだけ', '浴槽の排水を利用してできる「うず」で、ゴミや毛髪をまとめて捨てやすくします。', 'point-drain' ),
			),
			'opts' => array(
				array( 'opt-heater', '100V換気乾燥暖房機', '105000', '浴室全体を暖めます。換気・暖房・乾燥・涼風の4つの機能付き。', '※工事費込み' ),
				array( 'opt-mirror', 'ワイドミラー', '12000', 'ひろびろとした空間を演出するワイドミラー。汚れをはじく仕様です。', '' ),
				array( 'opt-slidedoor', '2枚引戸ホワイト（W800×H2000）', '62000', 'お子様や高齢の方にも開閉しやすい2枚引き戸。ブラック・シルバーも同価格です。', '' ),
				array( 'opt-aquajet', 'アクアジェット（2穴）＋浴槽パン', '194000', 'ジェットの刺激で身体をほぐし、リフレッシュ。低騒音ポンプを採用。', '' ),
			),
		),
		array(
			'slug'   => 'granspa',
			'prefix' => 'granspa',
			'title'  => 'グランスパ',
			'meta'   => array(
				'_ymkrf_catch' => 'ホーロが叶える手間いらずの清潔空間。',
				'_ymkrf_grade' => 'Sグレード',
				'_ymkrf_name' => 'グランスパ',
				'_ymkrf_size' => '1坪サイズ',
				'_ymkrf_work' => '370000',
				'_ymkrf_item' => '1028000',
				'_ymkrf_days' => '5',
				'_ymkrf_pt1' => 'きれいキープ！',
				'_ymkrf_pt2' => '続くぬくもり',
				'_ymkrf_pt3' => 'うるぽか',
				'_ymkrf_caution' => '※画像はイメージです。',
			),
			'total'  => 1398000,
			'maker'  => 'takara',
			'shops'  => array('nonoichi', 'komathu', 'hakui'),
			'main'   => array( 'main', 'タカラスタンダード グランスパ 1坪サイズ（ユニットバス）' ),
			'labels' => array(
				'_ymkrf_lbl_colors' => '浴槽カラー（全4色）',
				'_ymkrf_lbl_tops' => 'エプロンカラー（全3色）',
				'_ymkrf_lbl_sinks' => '壁カラー（ハイクラス全20色・ホーロークリーンパネル）',
				'_ymkrf_lbl_c5' => 'フロア（ハイクラス全4色・キープクリンフロア）',
				'_ymkrf_lbl_c6' => 'カウンターカラー（全3色）',
			),
			'sets' => array(
				'_ymkrf_colors' => array(
					array( 'tub-white', 'WN：クリスタルパールホワイト' ),
					array( 'tub-lightgray', 'GN：シュガーライトグレー' ),
					array( 'tub-lightbeige', 'DA：シュガーライトベージュ' ),
					array( 'tub-black', 'K：シュガーブラック' ),
				),
				'_ymkrf_tops' => array(
					array( 'apron-white', 'W：ホワイト' ),
					array( 'apron-beige', 'D：ベージュ' ),
					array( 'apron-black', 'K：ブラック' ),
				),
				'_ymkrf_sinks' => array(
					array( 'wa-terrazzo-white', 'テラゾーホワイト' ),
					array( 'wa-stucco-gray', 'スタッコグレー' ),
					array( 'wa-concrete-gray', 'コンクリートグレー' ),
					array( 'wa-brick-gray', 'ブリックグレー' ),
					array( 'wa-brick-beige', 'ブリックベージュ' ),
					array( 'wa-brick-dark', 'ブリックダーク' ),
					array( 'wa-flowstone-gray', 'フロウストーングレー' ),
					array( 'wa-flowstone-terracotta', 'フロウストーンテラコッタ' ),
					array( 'wa-linen-white', 'リネンホワイト' ),
					array( 'wa-white-ash', 'ホワイトアッシュ' ),
					array( 'wa-greige-ash', 'グレージュアッシュ' ),
					array( 'wa-canvas-yellow', 'キャンバスイエロー' ),
					array( 'wa-walnut-white', 'ウォルナットホワイト' ),
					array( 'wa-walnut-greige', 'ウォルナットグレージュ' ),
					array( 'wa-walnut-black', 'ウォルナットブラック' ),
					array( 'wa-pearl-white', 'パールホワイト' ),
					array( 'wa-pearl-beige', 'パールベージュ' ),
					array( 'wa-pearl-black', 'パールブラック' ),
					array( 'wa-caribbean-blue', 'カリビアンブルー' ),
					array( 'wa-collage-mix', 'コラージュミックス' ),
				),
				'_ymkrf_c5' => array(
					array( 'floor-white', 'HW：カームホワイト' ),
					array( 'floor-beige', 'HD：カームベージュ' ),
					array( 'floor-gray', 'HG：カームグレー' ),
					array( 'floor-darkgray', 'HC：カームダークグレー' ),
				),
				'_ymkrf_c6' => array(
					array( 'counter-white', 'WA：ホワイト' ),
					array( 'counter-beige', 'DA：ベージュ' ),
					array( 'counter-darkgray', 'KA：ダークグレー' ),
				),
			),
			'specs' => array(
				array( 'spec-fan', '天井換気扇', '' ),
				array( 'spec-tub', 'ラウンド浴槽（ベンチ付）', '' ),
				array( 'spec-mirror', 'ロングクリアミラー', '' ),
				array( 'spec-lid', '断熱風呂ふた／固定式風呂ふたフック', '' ),
				array( 'spec-counter', '樹脂製ワイドカウンター', '' ),
				array( 'spec-door', '折戸ホワイト（W800mm）', '' ),
				array( 'spec-faucet', 'サーモスタット水栓 標準シャワーヘッド 樹脂ホース シルバー', '' ),
				array( 'spec-rack', 'どこでもラック／タオルハンガーL', '' ),
				array( 'spec-light', '天井付スクエア照明（電球色／白色）', '' ),
				array( 'spec-bar', 'シャワーフックスライドバー／ハンドバーI型600（ステンレス）', '' ),
			),
			'feats' => array(
				array( '汚れが落としやすい！', '「ホーロークリーンパネル」', '手間いらずの清潔空間', '長年使ってもお手入れのしやすさは変わりません。汚れを落としやすく、カビをガードし、傷もつきにくいホーローです。', 'point-horo' ),
				array( '', '', '洗剤なしでらくらくきれい', '水拭きだけで汚れが落ちるので、洗剤を使わずにお掃除できます。', 'point-horo2' ),
				array( 'キレイをキープ', '「キープクリンフロア」', 'やさしいぬくもり', 'お湯をかけると、磁器タイルはじんわりと温まります。足元を快適に保ちます。', 'point-floor' ),
				array( '', '', '傷つきにくい！', '硬いブラシで擦っても傷がつきにくい磁器タイルを使用。頑固な汚れもゴシゴシお掃除できます。', 'point-floor2' ),
				array( '続くぬくもり', '「パーフェクト保温」', '浴室全体を包む保温材を標準装備', '浴室まるごとを保温材で包み込み、あたたかさが長つづきします。', 'point-hoon' ),
			),
			'opts' => array(
				array( 'opt-slidedoor', '片引き戸', '54900', '開閉動作がラクな引き戸タイプ。', '' ),
				array( 'opt-heater', '100V浴室暖房乾燥機', '75900', '浴室全体を暖めます。換気・暖房・乾燥・涼風の4つの機能付き。', '※工事費込み' ),
				array( 'opt-ecoshower', 'エコシャワーX', '6500', '流量を抑えた散水と手元止水で、しっかり節水します。', '' ),
				array( 'opt-urupoka', 'うるぽか湯', '177600', 'マイクロバブルで湯あたりもなめらか。おうちでスパ気分を味わえます。', '' ),
			),
		),
		array(
			'slug'   => 'selevia',
			'prefix' => 'selevia',
			'title'  => 'セレヴィア',
			'meta'   => array(
				'_ymkrf_catch' => '体の芯まで暖まる、あったかバスルーム。',
				'_ymkrf_grade' => 'SSグレード',
				'_ymkrf_name' => 'セレヴィア',
				'_ymkrf_size' => '1坪サイズ',
				'_ymkrf_work' => '370000',
				'_ymkrf_item' => '1328000',
				'_ymkrf_days' => '5',
				'_ymkrf_pt1' => 'まるごと保温',
				'_ymkrf_pt2' => '室内デザイン',
				'_ymkrf_pt3' => '足ピタフロア',
				'_ymkrf_caution' => '※写真はイメージです。写真にはオプションが含まれています。',
			),
			'total'  => 1698000,
			'maker'  => 'cleanup',
			'shops'  => array(),
			'main'   => array( 'main', 'クリナップ セレヴィア 1坪サイズ（ユニットバス）' ),
			'labels' => array(
				'_ymkrf_lbl_colors' => '浴槽カラー（全5色）',
				'_ymkrf_lbl_tops' => 'エプロン（全4色）',
				'_ymkrf_lbl_sinks' => '壁カラー：全面パネル（全12色）',
				'_ymkrf_lbl_c4' => '天井（全4色）',
				'_ymkrf_lbl_c5' => 'フロア（全4色）',
				'_ymkrf_lbl_c6' => 'カウンター（全4色）',
			),
			'sets' => array(
				'_ymkrf_colors' => array(
					array( 'tub-pearlwhite', 'パールホワイト' ),
					array( 'tub-forno-white', 'フォルノホワイト' ),
					array( 'tub-forno-gray', 'フォルノグレー' ),
					array( 'tub-forno-beige', 'フォルノベージュ' ),
					array( 'tub-forno-charcoal', 'フォルノチャコール' ),
				),
				'_ymkrf_tops' => array(
					array( 'apron-mediumwood', 'ミディアムウッド' ),
					array( 'apron-naturalwood', 'ナチュラルウッド' ),
					array( 'apron-roche-white', 'ロッシュホワイト' ),
					array( 'apron-pietra-dark', 'ピアトラダーク' ),
				),
				'_ymkrf_sinks' => array(
					array( 'wa-tenderwood-medium', 'テンダーウッドミディアム（WOOD 鏡面仕上げ）' ),
					array( 'wa-tenderwood-natural', 'テンダーウッドナチュラル（WOOD 鏡面仕上げ）' ),
					array( 'wa-tenderwood-white', 'テンダーウッドホワイト（WOOD 鏡面仕上げ）' ),
					array( 'wa-glasstile-white', 'グラスタイルホワイト（TILE 鏡面仕上げ）' ),
					array( 'wa-glasstile-mint', 'グラスタイルミント（TILE 鏡面仕上げ）' ),
					array( 'wa-glasstile-cobalt', 'グラスタイルコバルト（TILE 鏡面仕上げ）' ),
					array( 'wa-darkparlor', 'ダークパーラー（STONE 鏡面仕上げ）' ),
					array( 'wa-calacatta-white', 'カラカッタホワイト（STONE 鏡面仕上げ）' ),
					array( 'wa-calm-ice', 'カルムラインアイス（TEXTURE 鏡面仕上げ）' ),
					array( 'wa-calm-pastel', 'カルムラインパステル（TEXTURE 鏡面仕上げ）' ),
					array( 'wa-calm-white', 'カルムラインホワイト（TEXTURE 鏡面仕上げ）' ),
					array( 'wa-calm-gray', 'カルムライングレー（TEXTURE 鏡面仕上げ）' ),
				),
				'_ymkrf_c4' => array(
					array( 'ceil-walnut', 'ウォールナット' ),
					array( 'ceil-oak', 'オーク' ),
					array( 'ceil-mortar', 'モルタル' ),
					array( 'ceil-concrete', 'コンクリート' ),
				),
				'_ymkrf_c5' => array(
					array( 'floor-mediumwood', 'ミディアムウッド' ),
					array( 'floor-naturalwood', 'ナチュラルウッド' ),
					array( 'floor-roche-white', 'ロッシュホワイト' ),
					array( 'floor-pietra-dark', 'ピアトラダーク' ),
				),
				'_ymkrf_c6' => array(
					array( 'counter-mediumwood', 'ミディアムウッド' ),
					array( 'counter-naturalwood', 'ナチュラルウッド' ),
					array( 'counter-whitestone', 'ホワイトストーン' ),
					array( 'counter-darkstone', 'ダークストーン' ),
				),
			),
			'specs' => array(
				array( 'spec-counter', 'とってもクリンカウンター', '' ),
				array( 'spec-tub', 'アクリストン保温浴槽（機器タイル・パール調）', '' ),
				array( 'spec-mirror', 'スクエアミラー 防汚加工・ヒーターなし', '' ),
				array( 'spec-lid', '断熱組フタ（3点止めフック）', '' ),
				array( 'spec-bar', 'サポートバー＆シェルフ（浴槽側サポートバーあり）', '' ),
				array( 'spec-door', '折戸ホワイト（W800mm）', '' ),
				array( 'spec-haircatcher', 'クリンヘアキャッチャー', '' ),
				array( 'spec-faucet', '壁出しメッキ水栓 シルクベールシャワー スライドバー付', '' ),
				array( 'spec-fan', '天井換気扇（ホワイト）', '' ),
				array( 'spec-light', 'フラット天井 ダウンライト4灯（ホワイト）', '' ),
			),
			'feats' => array(
				array( 'あたたかさが続くから、冬場の入浴も快適。', '「浴室まるごと保温」', '浴室全体を保温材でパック', '入浴後30分経っても、浴室は20度以上。浴室内に暖かさがとどまります。', 'point-hoon' ),
				array( '', '', '高断熱浴槽', '4時間後も温度低下をわずか2.5℃以内に抑えるので、次の人にも温かくてエコです。', 'point-tub' ),
				array( '高いコーディネート性。', '「室内デザイン」', '天井からフロアまで統一したコーディネートが可能', '「天井」「浴槽」「フロア」「エプロン」まで、色をそろえてコーディネートできます。', 'point-design' ),
				array( '滑りにくいから、安心。', '「足ピタフロア」', '滑り止め効果を高めたパターン加工', '水の表面張力のはたらきで足裏を引き寄せます。床の溝までスポンジが届く形状なので、水滴が残りにくく乾きやすいのも特長です。', 'point-floor' ),
			),
			'opts' => array(
				array( 'opt-heater', '100V換気乾燥暖房機', '95000', '浴室全体を暖めます。換気・暖房・乾燥・涼風の4つの機能付き。', '※工事費込み' ),
				array( 'opt-grip', '浴槽ハンドグリップ（スムーズ浴槽用）', '13000', '2タイプの入浴姿勢で、くつろぎの時を満喫できます。', '' ),
				array( 'opt-slidedoor', '片引戸（ホワイト）', '76000', '開け閉めがしやすく、スムーズに出入りできます。', '' ),
				array( 'opt-chair', 'スムーズクッションチェア', '12000', '座っていても疲れにくい高さ。座面が外せて洗えます。', '' ),
			),
		),
		array(
			'slug'   => 'sinla',
			'prefix' => 'sinla',
			'title'  => 'シンラ',
			'meta'   => array(
				'_ymkrf_catch' => '上質で心休まる、穏やかな時間をすごす。',
				'_ymkrf_grade' => 'Premiumグレード',
				'_ymkrf_name' => 'シンラ',
				'_ymkrf_size' => 'Dタイプ 1坪サイズ',
				'_ymkrf_work' => '370000',
				'_ymkrf_item' => '1628000',
				'_ymkrf_days' => '5',
				'_ymkrf_pt1' => '楽湯',
				'_ymkrf_pt2' => 'リラックス',
				'_ymkrf_pt3' => '上品で美しい',
				'_ymkrf_caution' => '※写真はイメージです。',
			),
			'total'  => 1998000,
			'maker'  => 'toto',
			'shops'  => array('nonoichi'),
			'main'   => array( 'main', 'TOTO シンラ Dタイプ 1坪サイズ（ユニットバス）' ),
			'labels' => array(
				'_ymkrf_lbl_colors' => '浴槽・浴槽エプロンカラー（各全6色）',
				'_ymkrf_lbl_sinks' => '壁カラー：アクセントパネル（ハイグレードⅡ 全14色）',
				'_ymkrf_lbl_c5' => '床カラー（全10色）',
				'_ymkrf_lbl_c6' => 'カウンター（全4色）',
			),
			'sets' => array(
				'_ymkrf_colors' => array(
					array( 'tub-white', 'エレノアホワイト' ),
					array( 'tub-ivory', 'エレノアアイボリー' ),
					array( 'tub-pink', 'エレノアピンク' ),
					array( 'tub-gray', 'エレノアグレー' ),
					array( 'tub-brown', 'エレノアブラウン' ),
					array( 'tub-black', 'エレノアブラック' ),
				),
				'_ymkrf_sinks' => array(
					array( 'ac-sandstone-beige', 'サンドストーンベージュ（ストーン）' ),
					array( 'ac-ondry-beige', 'オンドリーベージュ（ストーン）' ),
					array( 'ac-onisiata-gray', 'オニシアータグレー（ストーン）' ),
					array( 'ac-piaclair-softgray', 'ピアクレアソフトグレー（ストーン）' ),
					array( 'ac-crafty-black', 'クラフティブラック（ストーン）' ),
					array( 'ac-empera-black', 'エンペラブラック（ストーン）' ),
					array( 'ac-confo-walnut', 'コンフォウォルナット（ウッド）' ),
					array( 'ac-blanche-oak', 'ブランシュオーク（ウッド）' ),
					array( 'ac-grayish-oak', 'グレイッシュオーク（ウッド）' ),
					array( 'ac-noir-wood', 'ノワウッド（ウッド）' ),
					array( 'ac-luce-oak', 'ルーセオーク（ウッド）' ),
					array( 'ac-magnifique-cherry', 'マニフィークチェリー（ウッド）' ),
					array( 'ac-perl-gray', 'ペルグレー' ),
					array( 'ac-beton-white', 'ベトンホワイト' ),
				),
				'_ymkrf_c5' => array(
					array( 'floor-etoffe-white', 'エトフホワイト（布調）' ),
					array( 'floor-etoffe-beige', 'エトフベージュ（布調）' ),
					array( 'floor-etoffe-gray', 'エトフグレー（布調）' ),
					array( 'floor-etoffe-marrone', 'エトフマローネ（布調）' ),
					array( 'floor-etoffe-black', 'エトフブラック（布調）' ),
					array( 'floor-cobble-white', 'コブルホワイト（石調）' ),
					array( 'floor-cobble-beige', 'コブルベージュ（石調）' ),
					array( 'floor-cobble-gray', 'コブルグレー（石調）' ),
					array( 'floor-cobble-marrone', 'コブルマローネ（石調）' ),
					array( 'floor-cobble-black', 'コブルブラック（石調）' ),
				),
				'_ymkrf_c6' => array(
					array( 'counter-white', 'グランツホワイト' ),
					array( 'counter-beige', 'グランツベージュ' ),
					array( 'counter-brown', 'グランツブラウン' ),
					array( 'counter-black', 'グランツブラック' ),
				),
			),
			'specs' => array(
				array( 'spec-fan', '天井換気扇', '' ),
				array( 'spec-tub', 'ファーストクラス浴槽 お掃除ラクラク人大浴槽（楽湯装備）', '' ),
				array( 'spec-mirror', 'お掃除ラクラク鏡 アルミフレーム付縦長ミラー くもり止めヒーター付／ワイヤーシェルフ2段', '' ),
				array( 'spec-lid', 'ラクかるふろふた（断熱）', '' ),
				array( 'spec-counter', 'お掃除ラクラクカウンター（床ワイパー洗浄装備）', '' ),
				array( 'spec-door', '折戸ホワイト（W800mm）', '' ),
				array( 'spec-bar', 'スライドハンガー付インテリアバー', '' ),
				array( 'spec-faucet', '2wayタッチ水栓 コンフォートウェーブシャワー3モード（メタル調）', '' ),
				array( 'spec-towel', 'タオル掛け（メタル調）', '' ),
				array( 'spec-light', 'ダウンライト（温白色）', '' ),
			),
			'feats' => array(
				array( 'たっぷりのお湯で肩と腰を心地よく刺激', '「楽湯 RAKU-YU」', '幅広で大流量のお湯で温かバスタイム', '気分や好みにあわせて流量を変えられ、広がるお湯に包まれます。', 'point-rakuyu' ),
				array( '', '', '水流が生み出す適度な刺激', 'ランダムな曲線で円を描くよう噴出された水流が、腰を中心に身体の広い範囲へ、変化に富んだ飽きのない刺激を与えます。', 'point-rakuyu2' ),
				array( 'リラックスへと導く', '「ファーストクラス浴槽」', '光の加減で表情が変わる、パールのような上品な美しさ', '滑らかな曲面で構成されたヘッドレストと浴槽の形状。一体感あるつながりにこだわりました。', 'point-firstclass' ),
				array( 'スイッチひとつで洗浄・除菌', '「床ワイパー洗浄（きれい除菌水）」', '見えない汚れ・菌も洗い流す', '①ワイパーのように水道水を散布 ②きれい除菌水で仕上げ完了。最新の床ワイパー洗浄です。', 'point-wiper' ),
			),
			'opts' => array(
				array( 'opt-slidedoor', '2枚引き戸', '97000', '出入り口の開口を広くとることができます。', '' ),
				array( 'opt-heater', '100V換気暖房乾燥機', '166000', '浴室全体を暖めます。換気・暖房・乾燥・涼風の4つの機能付き。（三乾王 ヒカルリモコン付）', '※工事費込み' ),
				array( 'opt-autowash', 'おそうじ浴槽', '190000', 'スイッチひとつで浴槽を自動洗浄します。', '※ブローバスとの同時選択はできません' ),
				array( 'opt-dressheat', '洗面所暖房（AC100V）', '96000', '寒い時期も温風暖房で安心・快適。涼風・ドライヤー機能も付いています。', '' ),
			),
		),
	);

	foreach ( $bath_products as $bp ) {

		if ( get_page_by_path( $bp['slug'], OBJECT, 'ymkrf_product' ) ) continue;

		$pid = wp_insert_post( array(
			'post_type'   => 'ymkrf_product',
			'post_status' => 'publish',
			'post_title'  => $bp['title'],
			'post_name'   => $bp['slug'],
		) );
		if ( ! $pid || is_wp_error( $pid ) ) continue;

		$m0 = $missing;
		$pf = $bp['prefix'];

		/* 画像ファイル名は「接頭辞-用途.jpg」でそろえています */
		$bimg = function ( $key, $alt = '' ) use ( $img, $pf ) {
			return $key ? $img( $pf . '-' . $key . '.jpg', $alt ) : '';
		};

		foreach ( $bp['meta'] as $k => $v )   update_post_meta( $pid, $k, $v );
		foreach ( $bp['labels'] as $k => $v ) update_post_meta( $pid, $k, $v );
		update_post_meta( $pid, '_ymkrf_total', $bp['total'] );

		$main = $bimg( $bp['main'][0], $bp['main'][1] );
		if ( $main ) set_post_thumbnail( $pid, $main );

		/* --- カラーバリエーション --- */
		foreach ( $bp['sets'] as $key => $list ) {
			$rows = array();
			foreach ( $list as $r ) $rows[] = array( 'img' => $bimg( $r[0], $r[1] ), 'name' => $r[1] );
			update_post_meta( $pid, $key, $rows );
		}

		/* --- 標準仕様 --- */
		$rows = array();
		foreach ( $bp['specs'] as $r ) {
			$rows[] = array( 'img' => $bimg( $r[0], $r[1] ), 'name' => $r[1], 'model' => $r[2] );
		}
		update_post_meta( $pid, '_ymkrf_specs', $rows );

		/* --- おすすめポイント --- */
		$rows = array();
		foreach ( $bp['feats'] as $r ) {
			$rows[] = array(
				'gsub' => $r[0], 'gttl' => $r[1], 'ttl' => $r[2], 'text' => $r[3],
				'note' => '', 'img' => $bimg( $r[4], $r[2] ), 'img2' => '',
			);
		}
		update_post_meta( $pid, '_ymkrf_features', $rows );

		/* --- おすすめオプション（最後に脱衣場内装パックを足します）--- */
		$rows = array();
		foreach ( array_merge( $bp['opts'], $bath_dressing ) as $r ) {
			$rows[] = array(
				'img'   => $bimg( $r[0], $r[1] ),
				'name'  => $r[1],
				'text'  => $r[3],
				'price' => $r[2],
				'note'  => $r[4],
			);
		}
		update_post_meta( $pid, '_ymkrf_options', $rows );

		/* --- ヤマキシ標準工事内容（お風呂・9項目）--- */
		$rows = array();
		foreach ( $bath_works as $r ) $rows[] = array( 'name' => $r[0], 'text' => $r[1] );
		update_post_meta( $pid, '_ymkrf_works', $rows );

		wp_set_object_terms( $pid, 'bathroom', 'ymkrf_product_cat' );
		wp_set_object_terms( $pid, $bp['maker'], 'ymkrf_maker' );
		if ( $bp['shops'] ) wp_set_object_terms( $pid, $bp['shops'], 'ymkrf_shop' );

		update_post_meta( $pid, '_ymkrf_img_missing', $missing - $m0 );
		$log[] = '商品「' . $bp['title'] . '」を登録しました → ' . get_permalink( $pid );
	}


	/* ------------------------------------------------------------
	   3-i. サザナNのメイン写真を差し替え
	        取り込んだ元の写真に左右の黒帯が入っていたため、
	        黒帯を落としたものに入れ替えます。
	   ------------------------------------------------------------ */
	$sz = get_page_by_path( 'sazana-n', OBJECT, 'ymkrf_product' );
	if ( $sz && get_post_meta( $sz->ID, '_ymkrf_main_ver', true ) !== '3' ) {

		$old = $img( 'sazana-main.jpg' );
		if ( $old ) wp_delete_attachment( $old, true );

		$new = $img( 'sazana-main.jpg', 'TOTO サザナ Nタイプ 1坪サイズ（ユニットバス）', true );
		if ( $new ) {
			set_post_thumbnail( $sz->ID, $new );
			update_post_meta( $sz->ID, '_ymkrf_main_ver', '3' );
			$log[] = 'サザナNのメイン写真を、黒帯のないものに差し替えました';
		}
	}


	/* ------------------------------------------------------------
	   3-j. アイキャッチ（一覧やグレード欄に出る写真）の自動修復
	        写真を差し替えたときなどに、もとの写真だけ消えて
	        新しい写真が入らないことがあるため、毎回チェックして直します。
	   ------------------------------------------------------------ */
	$main_files = array(
		'v-style'  => 'main.jpg',
		'rakuera'  => 'raku-main-product.jpg',
		'refit'    => 'refit-main.jpg',
		'sierra-s' => 'sierra-main.jpg',
		's-class'  => 'sclass-main.jpg',
		'stedia'   => 'stedia-main.jpg',
		'edel'     => 'edel-main.jpg',
		'classo'   => 'classo-main.jpg',
		'richelle' => 'richelle-main.jpg',
		'centro'   => 'centro-main.jpg',
		'ofuroa'   => 'ofuroa-main.jpg',
		'sazana-n' => 'sazana-main.jpg',
		'lidea-m'  => 'lidea-main.jpg',
		'rakuvia'  => 'rakuvia-main.jpg',
		'sazana-t' => 'sazanat-main.jpg',
		'lidea-b'  => 'lideab-main.jpg',
		'granspa'  => 'granspa-main.jpg',
		'selevia'  => 'selevia-main.jpg',
		'sinla'    => 'sinla-main.jpg',
		/* トイレ */
		'amage-z'         => 'amzi-main.jpg',
		'amage-z-aqua'    => 'amzh-main.jpg',
		'purerest-qr'     => 'qr-main.jpg',
		'alauno-vs5'      => 'vs5-main.jpg',
		'amage-z-premium' => 'amze-main.jpg',
		'pleas-ls'            => 'pleas-main.jpg',
		'alauno-s160'         => 's160-main.jpg',
		'alauno-s160-counter' => 's160c-main.jpg',
		'gga3'                => 'gga3-main.jpg',
		'gga1-counter'        => 'gga1-main.jpg',
		'satis-s'             => 'satis-main.jpg',
		'satis-s-counter'     => 'satisc-main.jpg',
		'neorest-rs3'         => 'rs3-main.jpg',
		'neorest-rs3-counter' => 'rs3c-main.jpg',
		/* 洗面化粧台 */
		'v1'                  => 'v1-main.jpg',
		'd7'                  => 'd7-main.jpg',
		'bga'                 => 'bga-main.jpg',
		'rakutowa'            => 'rakutowa-main.jpg',
		'j1'                  => 'j1-main.jpg',
		'rejust'              => 'rejust-main.jpg',
		'k1'                  => 'k1-main.jpg',
		'fansio'              => 'fansio-main.jpg',
		'r1'                  => 'r1-main.jpg',
		'sakua'               => 'sakua-main.jpg',
		'utsukushiizu'        => 'uts-main.jpg',
		'octave'              => 'octave-main.jpg',
		'woodone'             => 'woodone-main.jpg',
	);
	foreach ( $main_files as $slug => $file ) {
		$pp = get_page_by_path( $slug, OBJECT, 'ymkrf_product' );
		if ( ! $pp ) continue;

		$tid = get_post_thumbnail_id( $pp->ID );
		/* 番号はあるのに実体が無い（消された）／そもそも番号が無い、のどちらも直します */
		if ( $tid && get_post( $tid ) && get_attached_file( $tid ) ) continue;

		if ( $tid ) delete_post_thumbnail( $pp->ID );
		$re = $img( $file, get_the_title( $pp->ID ), true );
		if ( $re ) {
			set_post_thumbnail( $pp->ID, $re );
			$log[] = get_the_title( $pp->ID ) . 'のアイキャッチを入れ直しました';
		}
	}


	/* ------------------------------------------------------------
	   3-j2. アイキャッチの入れ直し（手動指定）
	         上の自動修復で直らなかった商品を、名指しで入れ直します。
	         数字を変えると、もう一度だけ入れ直します。
	   ------------------------------------------------------------ */
	$force_main = array(
		'classo'  => array( 'classo-main.jpg', 'TOTO ザ・クラッソのキッチン全体' ),
		'lidea-m' => array( 'lidea-main.jpg',  'LIXIL リデア Mタイプ 1坪サイズ（ユニットバス）' ),
	);
	foreach ( $force_main as $fs => $fi ) {
		$fp = get_page_by_path( $fs, OBJECT, 'ymkrf_product' );
		if ( ! $fp || get_post_meta( $fp->ID, '_ymkrf_main_fix', true ) === '1' ) continue;

		$old = $img( $fi[0] );
		if ( $old ) wp_delete_attachment( $old, true );

		$new = $img( $fi[0], $fi[1], true );   /* 第3引数の true で、覚えている番号を捨てて探し直します */
		if ( $new ) {
			set_post_thumbnail( $fp->ID, $new );
			update_post_meta( $fp->ID, '_ymkrf_main_fix', '1' );
			$log[] = get_the_title( $fp->ID ) . 'のメイン写真を入れ直しました';
		}
	}


	/* ------------------------------------------------------------
	   3-k. 一覧の写真を横幅いっぱいにするため、メイン写真の左右の余白を
	        切り落としたものに差し替えます。
	   ------------------------------------------------------------ */
	$retrim = array(
		'sazana-t' => array( 'sazanat-main.jpg', 'TOTO サザナ Tタイプ 1坪サイズ（ユニットバス）' ),
		'lidea-b'  => array( 'lideab-main.jpg',  'LIXIL リデア Bタイプ 1坪サイズ（ユニットバス）' ),
		'granspa'  => array( 'granspa-main.jpg', 'タカラスタンダード グランスパ 1坪サイズ（ユニットバス）' ),
		'sinla'    => array( 'sinla-main.jpg',   'TOTO シンラ Dタイプ 1坪サイズ（ユニットバス）' ),
		/* オフローラとリデアMも、同じように左右の白い余白を切りました */
		'ofuroa'   => array( 'ofuroa-main.jpg',  'Panasonic オフローラ 1坪サイズ（ユニットバス）' ),
		'lidea-m'  => array( 'lidea-main.jpg',   'LIXIL リデア Mタイプ 1坪サイズ（ユニットバス）' ),
		/* 洗面化粧台V1は、いただいた新しい写真（中央ぞろえ）に差し替えました */
		'v1'       => array( 'v1-main.jpg',      'LIXIL V1 洗面化粧台 間口75cm', 'v8' ),
		/* D7・BGAも、いただいた商品写真に差し替えました */
		'd7'       => array( 'd7-main.jpg',      'LIXIL D7 洗面化粧台 間口75cm', 'v2' ),
		'bga'      => array( 'bga-main.jpg',     'クリナップ BGA 洗面化粧台 間口75cm', 'v2' ),
	);
	foreach ( $retrim as $slug => $info ) {
		$tp = get_page_by_path( $slug, OBJECT, 'ymkrf_product' );
		/* 3つめの値があれば、それを合言葉にします（写真を替えるたびに増やせます） */
		$ver = isset( $info[2] ) ? $info[2] : 'trim';
		if ( ! $tp || get_post_meta( $tp->ID, '_ymkrf_main_ver', true ) === $ver ) continue;

		$old = $img( $info[0] );
		if ( $old ) wp_delete_attachment( $old, true );

		$new = $img( $info[0], $info[1], true );
		if ( $new ) {
			set_post_thumbnail( $tp->ID, $new );
			update_post_meta( $tp->ID, '_ymkrf_main_ver', $ver );
			$log[] = get_the_title( $tp->ID ) . 'のメイン写真を差し替えました';
		}
	}

	/* ------------------------------------------------------------
	   3-z. 価格の直し
	        キッチンの標準工事費は、どの機種も一律240,000円（税込）です。
	        Sクラスだけ工事費と商品代の入力が違っていたので直します。
	   ------------------------------------------------------------ */
	$sc = get_page_by_path( 's-class', OBJECT, 'ymkrf_product' );
	if ( $sc && get_post_meta( $sc->ID, '_ymkrf_work', true ) !== '240000' ) {
		update_post_meta( $sc->ID, '_ymkrf_work', '240000' );
		update_post_meta( $sc->ID, '_ymkrf_item', '758000' );
		update_post_meta( $sc->ID, '_ymkrf_total', 998000 );
		$log[] = 'Sクラスの工事費を240,000円・商品代を758,000円に直しました（合計998,000円は変わりません）';
	}

	/* ------------------------------------------------------------
	   3-d. ザ・クラッソのメイン写真を新しいものに差し替え
	        メディアに取り込んだ classo-main.jpg を新しい画像で入れ替え、
	        アイキャッチも付け直します。
	   ------------------------------------------------------------ */
	$cp = get_page_by_path( 'classo', OBJECT, 'ymkrf_product' );
	if ( $cp && get_post_meta( $cp->ID, '_ymkrf_main_ver', true ) !== '2' ) {

		/* 古い classo-main.jpg の添付を消してから、新しいファイルを取り込み直します */
		$old = $img( 'classo-main.jpg' );
		if ( $old ) wp_delete_attachment( $old, true );

		$new = $img( 'classo-main.jpg' );
		if ( $new ) {
			set_post_thumbnail( $cp->ID, $new );

			/* ギャラリー（写真の並び）の中の古いIDも入れ替えます */
			foreach ( array( '_ymkrf_gallery', '_ymkrf_hero' ) as $k ) {
				$g = get_post_meta( $cp->ID, $k, true );
				if ( is_array( $g ) ) {
					$g2 = array();
					foreach ( $g as $gid ) $g2[] = ( (int) $gid === (int) $old ) ? $new : $gid;
					update_post_meta( $cp->ID, $k, $g2 );
				}
			}
			update_post_meta( $cp->ID, '_ymkrf_main_ver', '2' );
			$log[] = 'ザ・クラッソのメイン写真を新しいものに差し替えました';
		}
	}

	/* ------------------------------------------------------------
	   3-e. キッチンの工期を全機種3日にそろえます
	   ------------------------------------------------------------ */
	$kt = get_term_by( 'slug', 'kitchen', 'ymkrf_product_cat' );
	if ( $kt && ! is_wp_error( $kt ) && get_option( 'ymkrf_kitchen_days3' ) !== '1' ) {
		$ks = get_posts( array(
			'post_type'      => 'ymkrf_product',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'tax_query'      => array( array(
				'taxonomy' => 'ymkrf_product_cat', 'field' => 'term_id', 'terms' => $kt->term_id,
			) ),
		) );
		$n = 0;
		foreach ( (array) $ks as $kid ) {
			if ( get_post_meta( $kid, '_ymkrf_days', true ) !== '3' ) {
				update_post_meta( $kid, '_ymkrf_days', '3' );
				$n++;
			}
		}
		if ( $n ) $log[] = "キッチン{$n}件の工期を3日にそろえました";
		update_option( 'ymkrf_kitchen_days3', '1' );
	}

	/* ------------------------------------------------------------
	   3-f. 施工事例のサンプルを3件つくります（見え方の確認用）
	        タイトルに【サンプル】と入れてあります。
	        本番の記事を入れたら、この3件は削除してください。
	        一度作ったあとは、消しても作り直しません。
	   ------------------------------------------------------------ */
	if ( post_type_exists( 'ymkrf_works' ) && get_option( 'ymkrf_works_sample' ) !== '1' ) {

		$samples = array(
			array(
				'title' => '【サンプル】使いにくかった対面キッチンを、片づくキッチンに',
				'slug'  => 'sample-kitchen-01',
				'area'  => '白山市',
				'price' => '128万円',
				'days'  => '3日',
				'img'   => 'stedia-main.jpg',
				'text'  => "調理中に家族の様子が見えないことがお悩みでした。対面のかたちはそのままに、収納の中身を見直しています。

※これは表示確認用のサンプル記事です。",
			),
			array(
				'title' => '【サンプル】収納が足りないI型キッチンを、引き出し収納たっぷりに',
				'slug'  => 'sample-kitchen-02',
				'area'  => '金沢市',
				'price' => '98万円',
				'days'  => '3日',
				'img'   => 'edel-main.jpg',
				'text'  => "開き戸の奥の物が取り出しにくいとのご相談。すべて引き出しに替え、立ったまま出し入れできるようにしました。

※これは表示確認用のサンプル記事です。",
			),
			array(
				'title' => '【サンプル】25年使ったキッチンを、お手入れがラクな仕様に',
				'slug'  => 'sample-kitchen-03',
				'area'  => '小松市',
				'price' => '148万円',
				'days'  => '3日',
				'img'   => 'richelle-main.jpg',
				'text'  => "レンジフードの掃除がご負担でした。自動洗浄タイプとキッチンパネルで、拭くだけのお手入れになりました。

※これは表示確認用のサンプル記事です。",
			),
		);

		foreach ( $samples as $sm ) {
			if ( get_page_by_path( $sm['slug'], OBJECT, 'ymkrf_works' ) ) continue;

			$wid = wp_insert_post( array(
				'post_type'    => 'ymkrf_works',
				'post_status'  => 'publish',
				'post_title'   => $sm['title'],
				'post_name'    => $sm['slug'],
				'post_content' => $sm['text'],
				'post_excerpt' => mb_substr( strtok( $sm['text'], "\n" ), 0, 80 ),
			) );
			if ( ! $wid || is_wp_error( $wid ) ) continue;

			wp_set_object_terms( $wid, 'kitchen', 'ymkrf_works_cat' );

			/* エリアは無ければ作ります */
			if ( taxonomy_exists( 'ymkrf_works_area' ) ) {
				$at = term_exists( $sm['area'], 'ymkrf_works_area' );
				if ( ! $at ) $at = wp_insert_term( $sm['area'], 'ymkrf_works_area' );
				if ( ! is_wp_error( $at ) ) {
					wp_set_object_terms( $wid, (int) $at['term_id'], 'ymkrf_works_area' );
				}
			}

			update_post_meta( $wid, '_ymkrf_price',  $sm['price'] );
			update_post_meta( $wid, '_ymkrf_period', $sm['days'] );

			$mid = $img( $sm['img'] );
			if ( $mid ) set_post_thumbnail( $wid, $mid );

			$log[] = '施工事例のサンプルを追加：' . $sm['title'];
		}
		update_option( 'ymkrf_works_sample', '1' );
	}


	/* ------------------------------------------------------------
	   3-m. 施工事例の「部位」に、きちんとした名前の項目を用意します。
	        （文字列だけで登録すると、名前が英語のままになるためです）
	   ------------------------------------------------------------ */
	if ( taxonomy_exists( 'ymkrf_works_cat' ) ) {
		foreach ( array( 'kitchen' => 'キッチン', 'bathroom' => 'お風呂', 'toilet' => 'トイレ', 'lavatory' => '洗面化粧台' ) as $ws => $wn ) {
			if ( ! term_exists( $ws, 'ymkrf_works_cat' ) ) {
				wp_insert_term( $wn, 'ymkrf_works_cat', array( 'slug' => $ws ) );
			}
		}
	}

	/* ------------------------------------------------------------
	   3-n. お風呂の施工事例サンプルを3件つくります（見え方の確認用）
	        本番の記事を入れたら、この3件は削除してください。
	   ------------------------------------------------------------ */
	if ( post_type_exists( 'ymkrf_works' ) && get_option( 'ymkrf_works_sample_bath' ) !== '1' ) {

		$bsamples = array(
			array(
				'title' => '【サンプル】在来のお風呂を、あたたかいユニットバスに',
				'slug'  => 'sample-bath-01',
				'area'  => '野々市市',
				'price' => '150万円',
				'days'  => '5日',
				'img'   => 'works-bath-01.jpg',
				'text'  => "冬の寒さがお悩みでした。断熱浴槽のユニットバスに入れ替え、浴室の内窓と外壁の補修もあわせて行いました。

※これは表示確認用のサンプル記事です。",
			),
			array(
				'title' => '【サンプル】古くなったユニットバスを、お手入れのラクな仕様に',
				'slug'  => 'sample-bath-02',
				'area'  => '加賀市',
				'price' => '70万円',
				'days'  => '5日',
				'img'   => 'works-bath-02.jpg',
				'text'  => "床の汚れと目地のカビがご負担でした。乾きやすい床と、目地の少ないパネルに替えています。

※これは表示確認用のサンプル記事です。",
			),
			array(
				'title' => '【サンプル】在来浴室からユニットバスへ。洗面脱衣場とトイレも',
				'slug'  => 'sample-bath-03',
				'area'  => '福井市',
				'price' => '164万円',
				'days'  => '7日',
				'img'   => 'works-bath-03.jpg',
				'text'  => "水まわりをまとめてご相談いただきました。お風呂・洗面脱衣場・トイレを一度に工事し、動線も整えています。

※これは表示確認用のサンプル記事です。",
			),
		);

		foreach ( $bsamples as $sm ) {
			if ( get_page_by_path( $sm['slug'], OBJECT, 'ymkrf_works' ) ) continue;

			$wid = wp_insert_post( array(
				'post_type'    => 'ymkrf_works',
				'post_status'  => 'publish',
				'post_title'   => $sm['title'],
				'post_name'    => $sm['slug'],
				'post_content' => $sm['text'],
				'post_excerpt' => mb_substr( strtok( $sm['text'], "\n" ), 0, 80 ),
			) );
			if ( ! $wid || is_wp_error( $wid ) ) continue;

			wp_set_object_terms( $wid, 'bathroom', 'ymkrf_works_cat' );

			if ( taxonomy_exists( 'ymkrf_works_area' ) ) {
				$at = term_exists( $sm['area'], 'ymkrf_works_area' );
				if ( ! $at ) $at = wp_insert_term( $sm['area'], 'ymkrf_works_area' );
				if ( ! is_wp_error( $at ) ) {
					wp_set_object_terms( $wid, (int) $at['term_id'], 'ymkrf_works_area' );
				}
			}

			update_post_meta( $wid, '_ymkrf_price',  $sm['price'] );
			update_post_meta( $wid, '_ymkrf_period', $sm['days'] );

			$mid = $img( $sm['img'] );
			if ( $mid ) set_post_thumbnail( $wid, $mid );

			$log[] = 'お風呂の施工事例サンプルを追加：' . $sm['title'];
		}
		update_option( 'ymkrf_works_sample_bath', '1' );
	}


	/* ------------------------------------------------------------
	   3-n0. キッチンの施工事例サンプルを3件つくります（見え方の確認用）
	         キッチン一覧ページの下も、お風呂・トイレと同じ見え方に
	         そろえるためです。本番の記事を入れたら削除してください。
	   ------------------------------------------------------------ */
	if ( post_type_exists( 'ymkrf_works' ) && get_option( 'ymkrf_works_sample_kitchen' ) !== '1' ) {

		$ksamples = array(
			array(
				'title' => '【サンプル】使いにくかった対面キッチンを、片付くキッチンに',
				'slug'  => 'sample-kitchen-01',
				'area'  => '白山市',
				'price' => '158万円',
				'days'  => '3日',
				'img'   => 'classo-main.jpg',
				'text'  => "収納が足りず、調理台に物があふれていました。引き出し収納の多い機種に入れ替え、キッチンパネルも張り替えています。\n\n※これは表示確認用のサンプル記事です。",
			),
			array(
				'title' => '【サンプル】壁付けキッチンを対面に。家族の様子が見えるように',
				'slug'  => 'sample-kitchen-02',
				'area'  => '金沢市',
				'price' => '192万円',
				'days'  => '3日',
				'img'   => 'richelle-main.jpg',
				'text'  => "お子さまの様子を見ながら料理がしたい、とのご希望でした。給排水と換気の位置を移し、対面キッチンに変更しています。\n\n※これは表示確認用のサンプル記事です。",
			),
			array(
				'title' => '【サンプル】20年使ったキッチンを、お手入れのラクな仕様に',
				'slug'  => 'sample-kitchen-03',
				'area'  => '小松市',
				'price' => '146万円',
				'days'  => '3日',
				'img'   => 'centro-main.jpg',
				'text'  => "コンロまわりの油汚れとレンジフードの掃除がご負担でした。汚れの落ちやすい天板と、ファンを自動で洗うレンジフードにしています。\n\n※これは表示確認用のサンプル記事です。",
			),
		);

		foreach ( $ksamples as $sm ) {
			if ( get_page_by_path( $sm['slug'], OBJECT, 'ymkrf_works' ) ) continue;

			$wid = wp_insert_post( array(
				'post_type'    => 'ymkrf_works',
				'post_status'  => 'publish',
				'post_title'   => $sm['title'],
				'post_name'    => $sm['slug'],
				'post_content' => $sm['text'],
				'post_excerpt' => mb_substr( strtok( $sm['text'], "\n" ), 0, 80 ),
			) );
			if ( ! $wid || is_wp_error( $wid ) ) continue;

			wp_set_object_terms( $wid, 'kitchen', 'ymkrf_works_cat' );

			if ( taxonomy_exists( 'ymkrf_works_area' ) ) {
				$at = term_exists( $sm['area'], 'ymkrf_works_area' );
				if ( ! $at ) $at = wp_insert_term( $sm['area'], 'ymkrf_works_area' );
				if ( ! is_wp_error( $at ) ) {
					wp_set_object_terms( $wid, (int) $at['term_id'], 'ymkrf_works_area' );
				}
			}

			update_post_meta( $wid, '_ymkrf_price',  $sm['price'] );
			update_post_meta( $wid, '_ymkrf_period', $sm['days'] );

			$mid = $img( $sm['img'] );
			if ( $mid ) set_post_thumbnail( $wid, $mid );

			$log[] = 'キッチンの施工事例サンプルを追加：' . $sm['title'];
		}
		update_option( 'ymkrf_works_sample_kitchen', '1' );
	}


	/* ------------------------------------------------------------
	   3-n2. トイレの施工事例サンプルを3件つくります（見え方の確認用）
	         キッチン・お風呂と同じように、トイレ一覧ページの下にも
	         施工事例が並ぶようにするためです。
	         本番の記事を入れたら、この3件は削除してください。
	   ------------------------------------------------------------ */
	if ( post_type_exists( 'ymkrf_works' ) && get_option( 'ymkrf_works_sample_toilet' ) !== '1' ) {

		$tsamples = array(
			array(
				'title' => '【サンプル】和式トイレを、手洗いカウンター付きの洋式トイレに',
				'slug'  => 'sample-toilet-01',
				'area'  => '羽咋市',
				'price' => '39万円',
				'days'  => '2日',
				'img'   => 'works-toilet-01.jpg',
				'text'  => "段差のある和式トイレで、ご家族の立ち座りがご負担でした。床の段差をなくし、手洗いカウンター付きの洋式トイレに入れ替えています。手すりもあわせて取り付けました。

※これは表示確認用のサンプル記事です。",
			),
			array(
				'title' => '【サンプル】20年使ったトイレを、お手入れのラクな一体型に',
				'slug'  => 'sample-toilet-02',
				'area'  => '金津',
				'price' => '50万円',
				'days'  => '半日',
				'img'   => 'works-toilet-02.jpg',
				'text'  => "便器のフチ裏の汚れがなかなか落ちない、というご相談でした。フチなし形状の一体型トイレに替え、掃除の手間がぐっと減ったと喜んでいただいています。工事は半日で終わりました。

※これは表示確認用のサンプル記事です。",
			),
			array(
				'title' => '【サンプル】トイレの交換とあわせて、壁紙と床も張り替え',
				'slug'  => 'sample-toilet-03',
				'area'  => '小松市',
				'price' => '32万円',
				'days'  => '1日',
				'img'   => 'works-toilet-03.jpg',
				'text'  => "便器を外したときにしか張り替えられない場所ですので、トイレの交換と同時に壁紙と床もきれいにしました。あとから頼むより費用がおさえられます。

※これは表示確認用のサンプル記事です。",
			),
		);

		foreach ( $tsamples as $sm ) {
			if ( get_page_by_path( $sm['slug'], OBJECT, 'ymkrf_works' ) ) continue;

			$wid = wp_insert_post( array(
				'post_type'    => 'ymkrf_works',
				'post_status'  => 'publish',
				'post_title'   => $sm['title'],
				'post_name'    => $sm['slug'],
				'post_content' => $sm['text'],
				'post_excerpt' => mb_substr( strtok( $sm['text'], "\n" ), 0, 80 ),
			) );
			if ( ! $wid || is_wp_error( $wid ) ) continue;

			wp_set_object_terms( $wid, 'toilet', 'ymkrf_works_cat' );

			if ( taxonomy_exists( 'ymkrf_works_area' ) ) {
				$at = term_exists( $sm['area'], 'ymkrf_works_area' );
				if ( ! $at ) $at = wp_insert_term( $sm['area'], 'ymkrf_works_area' );
				if ( ! is_wp_error( $at ) ) {
					wp_set_object_terms( $wid, (int) $at['term_id'], 'ymkrf_works_area' );
				}
			}

			update_post_meta( $wid, '_ymkrf_price',  $sm['price'] );
			update_post_meta( $wid, '_ymkrf_period', $sm['days'] );

			$mid = $img( $sm['img'] );
			if ( $mid ) set_post_thumbnail( $wid, $mid );

			$log[] = 'トイレの施工事例サンプルを追加：' . $sm['title'];
		}
		update_option( 'ymkrf_works_sample_toilet', '1' );
	}


	/* ------------------------------------------------------------
	   3-n3. 洗面化粧台の施工事例を3件つくります。

	         これは実際のホームページに載っている事例です
	         （能美市・白山市・福井県坂井市）。サンプルではないので、
	         そのまま残していただいて大丈夫です。
	   ------------------------------------------------------------ */
	if ( post_type_exists( 'ymkrf_works' ) && get_option( 'ymkrf_works_lavatory' ) !== '1' ) {

		$vsamples = array(
			array(
				'title' => '洗面化粧台の交換と、洗濯水栓の取付',
				'slug'  => 'works-lavatory-01',
				'area'  => '能美市',
				'price' => '15万円',
				'days'  => '',
				'img'   => 'works-lavatory-01.jpg',
				'text'  => '洗面化粧台の交換にあわせて、洗濯水栓の取付も行いました。',
			),
			array(
				'title' => '洗面化粧台の交換工事',
				'slug'  => 'works-lavatory-02',
				'area'  => '白山市',
				'price' => '14万円',
				'days'  => '',
				'img'   => 'works-lavatory-02.jpg',
				'text'  => '長くお使いだった洗面化粧台を、三面鏡タイプに交換しました。',
			),
			array(
				'title' => '洗面化粧台の取替工事',
				'slug'  => 'works-lavatory-03',
				'area'  => '福井県坂井市',
				'price' => '14万円',
				'days'  => '',
				'img'   => 'works-lavatory-03.jpg',
				'text'  => '洗面化粧台を取り替えました。',
			),
		);

		foreach ( $vsamples as $sm ) {
			if ( get_page_by_path( $sm['slug'], OBJECT, 'ymkrf_works' ) ) continue;

			$wid = wp_insert_post( array(
				'post_type'    => 'ymkrf_works',
				'post_status'  => 'publish',
				'post_title'   => $sm['title'],
				'post_name'    => $sm['slug'],
				'post_content' => $sm['text'],
				'post_excerpt' => mb_substr( $sm['text'], 0, 80 ),
			) );
			if ( ! $wid || is_wp_error( $wid ) ) continue;

			wp_set_object_terms( $wid, 'lavatory', 'ymkrf_works_cat' );

			if ( taxonomy_exists( 'ymkrf_works_area' ) ) {
				$at = term_exists( $sm['area'], 'ymkrf_works_area' );
				if ( ! $at ) $at = wp_insert_term( $sm['area'], 'ymkrf_works_area' );
				if ( ! is_wp_error( $at ) ) {
					wp_set_object_terms( $wid, (int) $at['term_id'], 'ymkrf_works_area' );
				}
			}

			update_post_meta( $wid, '_ymkrf_price',  $sm['price'] );
			update_post_meta( $wid, '_ymkrf_period', $sm['days'] );

			$mid = $img( $sm['img'] );
			if ( $mid ) set_post_thumbnail( $wid, $mid );

			$log[] = '洗面化粧台の施工事例を追加：' . $sm['title'];
		}
		update_option( 'ymkrf_works_lavatory', '1' );
	}


	/* ------------------------------------------------------------
	   3-o. お役立ち情報（コラム）のサンプルを、キッチンとお風呂に
	        3件ずつつくります。施工事例と同じ見え方にするためです。

	        そのカテゴリに本物の記事がすでに1件でもあるときは、
	        じゃまをしないよう、サンプルは作りません。
	        本番の記事を入れたら、【サンプル】の3件は削除してください。
	   ------------------------------------------------------------ */
	if ( post_type_exists( 'ymkrf_column' ) && get_option( 'ymkrf_column_sample' ) !== '1' ) {

		$colsets = array(

			'kitchen' => array(
				array(
					'slug'  => 'sample-column-kitchen-01',
					'title' => '【サンプル】キッチンの天板はどう選ぶ？人造大理石・ステンレス・セラミックのちがい',
					'img'   => 'classo-spec-counter.jpg',
					'lead'  => '毎日ふれる場所だからこそ、天板選びで満足度が変わります。3つの素材を、傷・熱・お手入れの3点で見くらべました。',
					'text'  => "キッチンを選ぶとき、いちばん迷うのが天板（ワークトップ）です。ショールームで見ためを比べるのも大切ですが、10年20年と使う場所ですので、お手入れのしやすさもあわせて考えてみてください。

<h2>人造大理石</h2>

いちばん多く選ばれている素材です。色の種類が多く、シンクとつなぎ目なく仕上げられるので、水あかがたまりにくいのが利点です。熱い鍋を直接置くと変色することがあるので、鍋敷きをお使いください。

<h2>ステンレス</h2>

熱にも水にも強く、いちばん丈夫な素材です。使っているうちに細かい傷は付きますが、それが全体になじむと、かえって目立たなくなります。業務用の厨房で使われているのは、この丈夫さのためです。

<h2>セラミック</h2>

いちばん傷と熱に強い素材です。熱い鍋をそのまま置けますし、包丁を直接あてても傷になりません。そのぶん価格は上がりますので、上位グレードでのお選びになります。

<h2>迷ったときは</h2>

ヤマキシのショールームには、どの素材も実物を展示しています。同じ「白」でも素材で表情がまったく違いますので、ぜひ手でさわって比べてみてください。

※これは表示確認用のサンプル記事です。",
				),
				array(
					'slug'  => 'sample-column-kitchen-02',
					'title' => '【サンプル】I型・L型・対面。キッチンの型のちがいと、間取りからの選び方',
					'img'   => 'classo-main.jpg',
					'lead'  => '「対面にしたい」というご相談をよくいただきます。それぞれの型の向き・不向きを、間取りと家事の動きから整理しました。',
					'text'  => "キッチンの型を変えると、家事の動きが大きく変わります。ただし、型を変える工事は水道と換気の位置も動かすことがあり、費用にも差が出ます。

<h2>I型</h2>

壁に向かってまっすぐ並ぶ、いちばん一般的な型です。工事の費用がおさえられ、せまい間取りでも置けます。

<h2>L型</h2>

角を使うので、作業できる場所が広くとれます。コンロとシンクの行き来が短くなるのが利点です。角の奥が使いにくくならないよう、収納の中身を選ぶことが大切です。

<h2>対面（アイランド・ペニンシュラ）</h2>

料理をしながらリビングの様子が見えます。お子さまがいるご家庭で人気です。手もとを隠したい場合は、立ち上がりの壁を付けるかどうかもご相談ください。

<h2>まずは今の不満から</h2>

「収納が足りない」「調理中に家族が見えない」など、いま困っていることを教えていただければ、型を変えずに解決できる場合もあります。無理におすすめはいたしません。

※これは表示確認用のサンプル記事です。",
				),
				array(
					'slug'  => 'sample-column-kitchen-03',
					'title' => '【サンプル】キッチンの工事は何日かかる？当日の流れと、その間のお食事',
					'img'   => 'centro-main.jpg',
					'lead'  => 'キッチンマルシェは工期3日です。1日目・2日目・3日目に何をしているのか、そのあいだのお食事のしかたまでご説明します。',
					'text'  => "「工事のあいだ、料理はどうするの？」というご質問をよくいただきます。ヤマキシのキッチンマルシェは、どの機種も工期3日です。

<h2>1日目：解体と撤去</h2>

今までのキッチンを外して運び出します。この日から水道が使えなくなります。

<h2>2日目：下地と設備</h2>

床・壁の下地を直し、水道と電気の位置を合わせます。キッチンパネルもこの日に貼ります。

<h2>3日目：組み立てと仕上げ</h2>

新しいキッチンを組み立て、水道・ガス・換気をつないで確認します。夕方にはお使いいただけます。

<h2>そのあいだのお食事</h2>

3日間は、洗面所の水道をお使いいただけます。電子レンジと電気ケトルを別の部屋に移しておくと安心です。お弁当やお惣菜で乗り切られるお客様が多いです。

<h2>工事の前にしていただくこと</h2>

引き出しの中身を出しておいてください。食器の避難場所に困る場合はご相談ください。

※これは表示確認用のサンプル記事です。",
				),
			),

			'bathroom' => array(
				array(
					'slug'  => 'sample-column-bath-01',
					'title' => '【サンプル】お風呂の寒さ対策。断熱浴槽と、あたたかい床のこと',
					'img'   => 'rakuvia-point-hoon.jpg',
					'lead'  => '北陸の冬、脱衣所から浴室に入った瞬間の寒さは体にこたえます。いまのユニットバスがどう解決しているかをご説明します。',
					'text'  => "冬のお風呂の寒さは、我慢するものではありません。急な温度差は体に負担がかかりますので、リフォームのご相談でもいちばん多いお悩みです。

<h2>断熱浴槽</h2>

浴槽を魔法びんのように包む仕組みです。4時間たってもお湯の温度がほとんど下がらないので、追いだきの回数が減り、光熱費もおさえられます。ご家族の入浴時間がばらばらのお宅ほど効果があります。

<h2>あたたかい床</h2>

足の裏がふれた瞬間の「ひやっ」は、床の下に断熱材が入っているかどうかで変わります。いまのユニットバスは、素足で入っても冷たさを感じにくい床が標準になっています。

<h2>浴室暖房乾燥機</h2>

入る前にあたためておけます。雨や雪の日の洗濯物を干す場所としてもお使いいただけます。

<h2>在来のお風呂の場合</h2>

タイル貼りの在来浴室からユニットバスに替えると、寒さの改善はいちばん大きく感じていただけます。壁のうしろに断熱材を入れられるかどうかは、現地を見てご説明します。

※これは表示確認用のサンプル記事です。",
				),
				array(
					'slug'  => 'sample-column-bath-02',
					'title' => '【サンプル】お風呂の掃除がラクになる仕組み。床・排水口・カビのこと',
					'img'   => 'rakuvia-point-floor.jpg',
					'lead'  => '「掃除がしんどい」は、ユニットバスを替えるだけでかなり軽くなります。どこが変わったのかを場所ごとに見ていきます。',
					'text'  => "お風呂のお掃除は、毎日のことだけに負担になります。10年20年前のお風呂と今のお風呂では、汚れにくさの考え方そのものが変わりました。

<h2>床</h2>

水が流れる細い溝が付いていて、翌朝には乾いています。乾いていれば、ぬめりもカビも出にくくなります。

<h2>排水口</h2>

髪の毛が中心にまとまる形になっていて、つまんで捨てるだけです。フタを外して洗う手間が減ります。

<h2>壁のパネル</h2>

目地の少ない大きなパネルなので、黒カビが出る場所そのものが減ります。汚れも拭くだけで落ちます。

<h2>カウンターと鏡</h2>

外して洗えるカウンターや、鏡を付けない選び方もできます。「掃除する物を減らす」という考え方です。

<h2>お手入れをラクにしたいとお伝えください</h2>

ご予算のなかでどこにお金をかけるかが変わります。ショールームで実物をさわりながらご相談ください。

※これは表示確認用のサンプル記事です。",
				),
				array(
					'slug'  => 'sample-column-bath-03',
					'title' => '【サンプル】在来浴室からユニットバスへ。費用の目安と工事の日数',
					'img'   => 'selevia-main.jpg',
					'lead'  => 'タイル貼りのお風呂からの入れ替えは、何にいくらかかるのか。ヤマキシの工事費コミコミ価格の中身をご説明します。',
					'text'  => "「お風呂のリフォームはいくらかかりますか」というご質問に、ひとことでお答えするのは難しいのですが、ヤマキシでは商品代と標準工事費を分けて、はっきりお出ししています。

<h2>標準工事費は一律370,000円（税込）</h2>

商品がどれでも変わりません。既存のお風呂の解体撤去、産業廃棄物の処理運搬、水道・電気・木工事、ユニットバスの組立設置、浴室まわりの内装、換気扇の取付、ドア枠の造作まで含みます。

<h2>商品代はグレードで変わります</h2>

いちばんお求めやすいものから、上位グレードまでご用意しています。工事費コミコミの金額は商品一覧でご覧いただけます。

<h2>工期は5日</h2>

解体から仕上げまで、どの機種も5日です。そのあいだ、お風呂はお使いいただけませんので、近くの温泉施設をご案内することもあります。

<h2>追加になることがある工事</h2>

土台が傷んでいた場合の補修、給湯器の入れ替え、脱衣場の内装などは別途になります。現地調査のときに、見つかったものはその場でご説明します。

<h2>見積りは無料です</h2>

しつこい営業はいたしません。まずは現物を見にいらしてください。

※これは表示確認用のサンプル記事です。",
				),
			),
		);

		foreach ( $colsets as $ccat => $items ) {

			$cterm = get_term_by( 'slug', $ccat, 'ymkrf_product_cat' );
			if ( ! $cterm || is_wp_error( $cterm ) ) continue;

			/* すでに本物の記事があるカテゴリには、サンプルを足しません */
			$hasq = new WP_Query( array(
				'post_type'      => 'ymkrf_column',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'tax_query'      => array( array(
					'taxonomy' => 'ymkrf_product_cat',
					'field'    => 'term_id',
					'terms'    => (int) $cterm->term_id,
				) ),
			) );
			$already = $hasq->have_posts();
			wp_reset_postdata();
			if ( $already ) continue;

			foreach ( $items as $sm ) {
				if ( get_page_by_path( $sm['slug'], OBJECT, 'ymkrf_column' ) ) continue;

				$cid = wp_insert_post( array(
					'post_type'    => 'ymkrf_column',
					'post_status'  => 'publish',
					'post_title'   => $sm['title'],
					'post_name'    => $sm['slug'],
					'post_content' => $sm['text'],
					'post_excerpt' => $sm['lead'],
				) );
				if ( ! $cid || is_wp_error( $cid ) ) continue;

				wp_set_object_terms( $cid, array( (int) $cterm->term_id ), 'ymkrf_product_cat' );

				$mid = $img( $sm['img'] );
				if ( $mid ) set_post_thumbnail( $cid, $mid );

				$log[] = 'コラムのサンプルを追加：' . $sm['title'];
			}
		}
		update_option( 'ymkrf_column_sample', '1' );
	}


	/* ------------------------------------------------------------
	   3-o2. お役立ち情報（コラム）のサンプルを、トイレにも3件つくります。
	         キッチン・お風呂と同じ見え方にするためです。
	         トイレに本物の記事がすでに1件でもあるときは作りません。
	         本番の記事を入れたら、【サンプル】の3件は削除してください。
	   ------------------------------------------------------------ */
	if ( post_type_exists( 'ymkrf_column' ) && get_option( 'ymkrf_column_sample_toilet' ) !== '1' ) {

		$tcols = array(
			array(
				'slug'  => 'sample-column-toilet-01',
				'title' => '【サンプル】トイレの交換、工事は何日かかる？半日で終わるって本当？',
				'img'   => 'gga1-main.jpg',
				'lead'  => 'ヤマキシのトイレリフォームパックは、ほとんどの機種が工期半日です。当日の流れと、その間トイレが使えない時間をご説明します。',
				'text'  => "「工事のあいだ、トイレはどうするの？」というご質問をいちばん多くいただきます。便器の交換だけであれば、朝からはじめてお昼すぎには使えるようになります。

<h2>午前：古いトイレの取り外し</h2>

止水栓を閉めて、便器・タンク・便座を取り外します。運び出しと処分まで、標準工事費にふくまれています。

<h2>午前〜昼：床と給排水の確認</h2>

便器を外したときにしか見えない場所ですので、床の傷みや配管の状態をこの時点で確認します。手直しが必要な場合は、その場でご相談させていただきます。

<h2>午後：新しいトイレの取り付け</h2>

便器を据えて、給水・排水をつなぎ、水もれがないか確認します。リモコンの使い方をご説明して終わりです。

<h2>手洗いカウンター付きの場合</h2>

カウンターの取り付けと、そこまでの給排水の工事が加わります。それでも1日で終わることがほとんどです。

<h2>その間のトイレ</h2>

工事中の数時間は使えません。ご近所やお勤め先ですませていただくか、簡易トイレをご用意しますのでお申し付けください。

※これは表示確認用のサンプル記事です。",
			),
			array(
				'slug'  => 'sample-column-toilet-02',
				'title' => '【サンプル】掃除がラクなトイレとは？フチなし・防汚素材・自動おそうじのちがい',
				'img'   => 's160-point-bubble.jpg',
				'lead'  => '「掃除がラクなトイレにしたい」というご要望が年々増えています。メーカーごとの工夫を、しくみから見くらべました。',
				'text'  => "トイレ掃除がつらい理由は、だいたい決まっています。フチの裏・便器の表面・便座のすきま、この3つです。いまのトイレは、それぞれに手が打たれています。

<h2>フチなし形状</h2>

汚れがたまる「フチの裏」そのものをなくした形です。ひとふきで拭き取れるので、ブラシを入れて奥をこする作業がなくなります。

<h2>汚れがつきにくい素材</h2>

TOTOのセフィオンテクト、LIXILのアクアセラミック、Panasonicのスゴピカ素材など、メーカーごとに呼び方は違いますが、ねらいは同じです。表面をなめらかにして、汚れを引っかからせません。

<h2>自動おそうじ</h2>

流すたびに洗剤や泡で洗ってくれる機能です。Panasonicのアラウーノは、泡と水流で毎回洗います。日々のこすり洗いの回数が目に見えて減ります。

<h2>すきまの少ない設計</h2>

便座と便器のあいだ、タンクと便器のあいだ。ここに凹凸が少ないほど、拭き掃除がラクになります。

<h2>どれを選ぶか</h2>

すべてを備えた機種は価格も上がります。「いちばん面倒なのはどこか」を教えていただければ、そこに効く機種をおすすめします。

※これは表示確認用のサンプル記事です。",
			),
			array(
				'slug'  => 'sample-column-toilet-03',
				'title' => '【サンプル】手洗いカウンターは付けるべき？費用と、向いているお宅',
				'img'   => 'rs3c-main.jpg',
				'lead'  => 'タンクなしのトイレにすると、手を洗う場所が別に必要になります。付ける・付けないの判断材料を整理しました。',
				'text'  => "タンク付きのトイレは、タンクの上で手を洗えます。タンクをなくしたスッキリした形にすると、そこがなくなりますので、手洗いをどうするかを決める必要があります。

<h2>手洗いカウンターを付ける場合</h2>

ヤマキシでは、標準工事費が38,000円（税込）から53,000円（税込）に変わります。カウンター本体の費用は商品代にふくまれています。収納が付くタイプを選べば、掃除道具やペーパーのストックもしまえます。

<h2>付けない場合</h2>

トイレを出てすぐに洗面所があるお宅では、無理に付けなくても不便を感じないことが多いです。そのぶん費用をおさえられますし、トイレも広く使えます。

<h2>迷ったときの目安</h2>

・トイレから洗面所までが遠い
・お子さまやご年配の方がいる
・来客が多い

このいずれかに当てはまるなら、付けておくと便利です。

<h2>あとから付けられる？</h2>

給排水の工事が必要になりますので、トイレの交換と同時のほうが費用はおさえられます。

※これは表示確認用のサンプル記事です。",
			),
		);

		$tterm = get_term_by( 'slug', 'toilet', 'ymkrf_product_cat' );
		if ( $tterm && ! is_wp_error( $tterm ) ) {

			/* すでに本物の記事があるときは、サンプルを足しません */
			$hasq = new WP_Query( array(
				'post_type'      => 'ymkrf_column',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'tax_query'      => array( array(
					'taxonomy' => 'ymkrf_product_cat',
					'field'    => 'term_id',
					'terms'    => (int) $tterm->term_id,
				) ),
			) );
			$already = $hasq->have_posts();
			wp_reset_postdata();

			if ( ! $already ) {
				foreach ( $tcols as $sm ) {
					if ( get_page_by_path( $sm['slug'], OBJECT, 'ymkrf_column' ) ) continue;

					$cid = wp_insert_post( array(
						'post_type'    => 'ymkrf_column',
						'post_status'  => 'publish',
						'post_title'   => $sm['title'],
						'post_name'    => $sm['slug'],
						'post_content' => $sm['text'],
						'post_excerpt' => $sm['lead'],
					) );
					if ( ! $cid || is_wp_error( $cid ) ) continue;

					wp_set_object_terms( $cid, array( (int) $tterm->term_id ), 'ymkrf_product_cat' );

					$mid = $img( $sm['img'] );
					if ( $mid ) set_post_thumbnail( $cid, $mid );

					$log[] = 'トイレのコラムのサンプルを追加：' . $sm['title'];
				}
			}
			update_option( 'ymkrf_column_sample_toilet', '1' );
		}
	}



	/* ------------------------------------------------------------
	   3-o3. お役立ち情報（コラム）のサンプルを、洗面化粧台にも3件。
	         キッチン・お風呂・トイレと同じ見え方にするためです。
	         本番の記事を入れたら、【サンプル】の3件は削除してください。
	   ------------------------------------------------------------ */
	if ( post_type_exists( 'ymkrf_column' ) && get_option( 'ymkrf_column_sample_lavatory' ) !== '1' ) {

		$vcols = array(
			array(
				'slug'  => 'sample-column-lavatory-01',
				'title' => '【サンプル】洗面化粧台の交換は何日かかる？最短当日で終わります',
				'img'   => 'v1-main.jpg',
				'lead'  => '洗面化粧台の交換は、ほとんどのお宅で当日中に終わります。当日の流れと、水が使えない時間をご説明します。',
				'text'  => "「工事のあいだ、洗濯や洗面はどうするの？」というご質問をよくいただきます。洗面化粧台の入れ替えだけであれば、朝からはじめて夕方にはお使いいただけます。\n\n<h2>午前：古い洗面化粧台の取り外し</h2>\n\n止水栓を閉めて、ミラーキャビネット・洗面ボウル・下のキャビネットを外します。運び出しと処分まで、標準工事費にふくまれています。\n\n<h2>昼：壁と給排水の確認</h2>\n\n洗面化粧台を外したときにしか見えない場所ですので、壁の傷みや配管の状態をこの時点で確認します。手直しが必要な場合は、その場でご相談させていただきます。\n\n<h2>午後：新しい洗面化粧台の取り付け</h2>\n\n本体を据えて、給水・排水・電気をつなぎ、水もれがないか確認します。使い方をご説明して終わりです。\n\n<h2>その間の水まわり</h2>\n\n工事中の数時間は、洗面所の水道が使えません。キッチンやお風呂の水道はお使いいただけます。\n\n※これは表示確認用のサンプル記事です。",
			),
			array(
				'slug'  => 'sample-column-lavatory-02',
				'title' => '【サンプル】間口75cmと60cm、どちらを選ぶ？置ける幅の測り方',
				'img'   => 'v1-spec-mirror.jpg',
				'lead'  => '洗面化粧台の大きさは「間口」で決まります。いまのサイズの測り方と、大きくできる場合・できない場合を整理しました。',
				'text'  => "洗面化粧台は、幅（間口）が60cm・75cm・90cmといった決まったサイズで作られています。いまお使いのものと同じ間口であれば、そのまま入れ替えられます。\n\n<h2>間口の測り方</h2>\n\n洗面化粧台の左端から右端までを測ってください。壁から壁までではなく、本体の幅です。だいたい60cmか75cmのどちらかに近い数字になるはずです。\n\n<h2>75cmにすると変わること</h2>\n\nボウルが広くなり、つけおき洗いがしやすくなります。下の収納も増えます。洗面所に余裕があるお宅では、60cmから75cmに広げるご相談も多くいただきます。\n\n<h2>広げられない場合</h2>\n\n洗濯機や扉との距離が足りないと、大きくできないことがあります。現地を見てご説明しますので、まずはご相談ください。\n\n<h2>迷ったときは</h2>\n\nショールームに実物を並べて展示しています。同じ間口でも、収納の作りで使い勝手はかなり変わります。\n\n※これは表示確認用のサンプル記事です。",
			),
			array(
				'slug'  => 'sample-column-lavatory-03',
				'title' => '【サンプル】洗面化粧台の交換と一緒にやると得なこと',
				'img'   => 'v1-opt-interior-full.jpg',
				'lead'  => '本体を外したときにしかできない工事があります。あとから頼むより費用がおさえられるものをまとめました。',
				'text'  => "洗面化粧台を外すと、いつもは見えない壁と床が現れます。このタイミングでしかできない工事があります。\n\n<h2>脱衣場の壁紙・床の張り替え</h2>\n\n洗面化粧台の裏側は、あとから張り替えようとすると本体をもう一度外すことになります。同時であれば、その手間がかかりません。ヤマキシでは1坪までの内装パックをご用意しています。\n\n<h2>洗濯水栓の取り替え</h2>\n\n古い水栓は、ゴムホースが外れて水漏れすることがあります。緊急止水弁の付いたものに替えておくと安心です。\n\n<h2>コンセントの増設</h2>\n\nドライヤーや電動歯ブラシで足りなくなりがちです。壁を触るタイミングで一緒に。\n\n<h2>まとめてのご相談を</h2>\n\n「ついでにここも」というご相談は大歓迎です。お見積りの段階でお伝えください。\n\n※これは表示確認用のサンプル記事です。",
			),
		);
		$vterm = get_term_by( 'slug', 'lavatory', 'ymkrf_product_cat' );
		if ( $vterm && ! is_wp_error( $vterm ) ) {

			$hasq = new WP_Query( array(
				'post_type'      => 'ymkrf_column',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'tax_query'      => array( array(
					'taxonomy' => 'ymkrf_product_cat',
					'field'    => 'term_id',
					'terms'    => (int) $vterm->term_id,
				) ),
			) );
			$already = $hasq->have_posts();
			wp_reset_postdata();

			if ( ! $already ) {
				foreach ( $vcols as $sm ) {
					if ( get_page_by_path( $sm['slug'], OBJECT, 'ymkrf_column' ) ) continue;

					$cid = wp_insert_post( array(
						'post_type'    => 'ymkrf_column',
						'post_status'  => 'publish',
						'post_title'   => $sm['title'],
						'post_name'    => $sm['slug'],
						'post_content' => $sm['text'],
						'post_excerpt' => $sm['lead'],
					) );
					if ( ! $cid || is_wp_error( $cid ) ) continue;

					wp_set_object_terms( $cid, array( (int) $vterm->term_id ), 'ymkrf_product_cat' );

					$mid = $img( $sm['img'] );
					if ( $mid ) set_post_thumbnail( $cid, $mid );

					$log[] = '洗面化粧台のコラムのサンプルを追加：' . $sm['title'];
				}
			}
			update_option( 'ymkrf_column_sample_lavatory', '1' );
		}
	}


	/* ------------------------------------------------------------
	   3-p. トイレ商品
	        ホームページの内容をそのまま入れています。
	        標準工事費はどの機種も一律38,000円（税込）、工期は半日です。

	        トイレは「標準仕様」を写真ではなく機能名の一覧で出すため、
	        _ymkrf_speclist（分類＋機能）を使っています。
	   ------------------------------------------------------------ */

	/* URLのつづり直し。
	   最初 amaze-z で登録してしまったので、正しい amage-z に付け替えます。
	   （すでに amage-z がある場合は何もしません） */
	$old_amz = get_page_by_path( 'amaze-z', OBJECT, 'ymkrf_product' );
	if ( $old_amz && ! get_page_by_path( 'amage-z', OBJECT, 'ymkrf_product' ) ) {
		wp_update_post( array( 'ID' => $old_amz->ID, 'post_name' => 'amage-z' ) );
		$log[] = 'アメージュZのURLを amaze-z → amage-z に直しました';
	}

	/* 標準仕様（文字だけの一覧）を入れ直します。
	   最初に入れたときの表示処理に不具合があり、機能名が途中で切れていたためです。
	   数字を上げると、もう一度だけ入れ直します。 */
	$sl_fix = array(
		'amage-z' => array(
			array( '快適機能',   "暖房便座\nスローダウン便座\n暖房便座コード内蔵\n点字対応" ),
			array( '洗浄機能',   "おしり泡ジェット洗浄（泡沫）\nソフトビデ洗浄（泡沫）" ),
			array( '省エネ機能', "超節水ECO５\nワンタッチ節電（8h）" ),
			array( '清潔機能',   "パワーストリーム洗浄\n本体スライド着脱\n女性専用レディスノズル\nノズルそうじ\nノズルオートクリーニング\nノズル先端着脱\nキレイ便座\n便フタワンタッチ着脱" ),
		),
	);
	foreach ( $sl_fix as $ss => $groups ) {
		$sp = get_page_by_path( $ss, OBJECT, 'ymkrf_product' );
		if ( ! $sp ) continue;
		if ( get_post_meta( $sp->ID, '_ymkrf_speclist_ver', true ) === '2' ) continue;

		$rows = array();
		foreach ( $groups as $g ) $rows[] = array( 'ttl' => $g[0], 'body' => $g[1] );
		update_post_meta( $sp->ID, '_ymkrf_speclist', $rows );
		update_post_meta( $sp->ID, '_ymkrf_speclist_ver', '2' );
		$log[] = get_the_title( $sp->ID ) . 'の標準仕様を入れ直しました';
	}

	/* 陶器の種類は商品名の横、便座はメーカーロゴの横に分けて出します。
	   先に登録済みの商品にも当てます。 */
	$fix_sub = array(
		'amage-z' => array( 'ハイパーキラミック', 'シャワートイレRG10H' ),
	);
	foreach ( $fix_sub as $fs => $v ) {
		$fp = get_page_by_path( $fs, OBJECT, 'ymkrf_product' );
		if ( ! $fp ) continue;
		if ( get_post_meta( $fp->ID, '_ymkrf_sub', true ) !== '' ) continue;
		update_post_meta( $fp->ID, '_ymkrf_sub',  $v[0] );
		update_post_meta( $fp->ID, '_ymkrf_size', $v[1] );
		$log[] = get_the_title( $fp->ID ) . 'の陶器の種類を、商品名の横に移しました';
	}

	/* トイレの「ヤマキシ標準工事内容」（3項目）は全機種共通です */
	$toilet_works = array(
		array( '既存トイレ解体撤去工事', '古い便器・便座の取り外しと撤去にかかる工事です。' ),
		array( '水道工事',               '給水・排水の工事です。' ),
		array( 'トイレ設置工事',         '新しい便器・便座の取り付け工事です。' ),
	);

	$toilet_products = array(

		/* ===== Iグレード アメージュZ（LIXIL） ===== */
		array(
			'slug'   => 'amage-z',
			'prefix' => 'amzi',
			'title'  => 'アメージュZ',
			'meta'   => array(
				'_ymkrf_catch'    => 'お掃除ラクラクで、エコロジーな超節水タイプ。',
				'_ymkrf_grade'    => 'Iグレード',
				'_ymkrf_name'     => 'アメージュZ',
				'_ymkrf_size'     => 'シャワートイレRG10H',
				'_ymkrf_sub'      => 'ハイパーキラミック',
				'_ymkrf_work'     => '38000',
				'_ymkrf_item'     => '91800',
				'_ymkrf_days'     => '',
				'_ymkrf_daystext' => '半日',
				'_ymkrf_pt1'      => '節水',
				'_ymkrf_pt2'      => '足元スリム',
				'_ymkrf_pt3'      => 'お掃除ラクラク',
				'_ymkrf_caution'  => '※写真はイメージになります。本体及び便座のみとなります。本体：アメージュZ（ハイパーキラミック）／便座：シャワートイレRG10H',
			),
			'total'  => 129800,
			'maker'  => 'lixil',
			'shops'  => array( 'nonoichi', 'hakui', 'komathu', 'kawakita', 'kanadu' ),
			'main'   => array( 'main', 'LIXIL アメージュZ（ハイパーキラミック／シャワートイレRG10H）' ),
			'labels' => array(
				'_ymkrf_lbl_colors' => '便器カラー（全2色）',
			),
			'sets' => array(
				'_ymkrf_colors' => array(
					array( 'color-purewhite', 'ピュアホワイト' ),
					array( 'color-offwhite',  'オフホワイト' ),
				),
			),
			'speclist' => array(
				array( '快適機能', "暖房便座\nスローダウン便座\n暖房便座コード内蔵\n点字対応" ),
				array( '洗浄機能', "おしり泡ジェット洗浄（泡沫）\nソフトビデ洗浄（泡沫）" ),
				array( '省エネ機能', "超節水ECO５\nワンタッチ節電（8h）" ),
				array( '清潔機能', "パワーストリーム洗浄\n本体スライド着脱\n女性専用レディスノズル\nノズルそうじ\nノズルオートクリーニング\nノズル先端着脱\nキレイ便座\n便フタワンタッチ着脱" ),
			),
			'feats' => array(
				array( '節水しながら全面キレイ', '「パワーストリーム洗浄」',
				       '少ない水でしっかりと鉢を洗浄。',
				       '強力な水流が便器鉢内のすみずみまで回り、少ない水でもしっかり汚れを洗い流します。', 'point-stream' ),
				array( 'お掃除らくらく！', '「フチレス形状」・「キレイ便座」',
				       'サッとひと拭き「フチレス形状」',
				       '奥も手前も便器のフチを丸ごとなくし、サッとひと拭き、お掃除ラクラクです。', 'point-rimless' ),
				array( '', '',
				       'お掃除ラクラク「キレイ便座」',
				       "汚れが入りやすい継ぎ目をなくしました。さらに便座裏は防汚素材なのでお掃除ラクラク。\n（左：従来／右：キレイ便座）", 'point-seat' ),
				array( 'ノズルも清潔・キレイ。', '「レディスノズル」',
				       'ノズルが別々、だから清潔で安心！',
				       'おしり洗浄用ノズルとは別に、女性にやさしいビデ洗浄用ノズルを搭載しています。', 'point-nozzle' ),
				array( '足元スリム', 'シャープなフォルム',
				       '汚れが拭きやすい',
				       'シャープで足元スリムなフォルムは、汚れも拭きやすく、お手入れ簡単です。', 'point-slim' ),
			),
			'opts' => array(
				array( 'opt-rwa30', 'シャワートイレ（CW-RWA30HQ）', '70000',
				       '★フルオート便座。フルオート便器洗浄。アクアセラミック仕様。', '' ),
			),
		),

		/* ===== Hグレード アメージュZ（アクアセラミック／LIXIL） ===== */
		array(
			'slug'   => 'amage-z-aqua',
			'prefix' => 'amzh',
			'title'  => 'アメージュZ（アクアセラミック）',
			'meta'   => array(
				'_ymkrf_catch'    => 'お掃除ラクラクで、エコロジーな超節水タイプ。',
				'_ymkrf_grade'    => 'Hグレード',
				'_ymkrf_name'     => 'アメージュZ',
				'_ymkrf_sub'      => 'アクアセラミック',
				'_ymkrf_size'     => 'シャワートイレRG10H',
				'_ymkrf_work'     => '38000',
				'_ymkrf_item'     => '101800',
				'_ymkrf_days'     => '',
				'_ymkrf_daystext' => '半日',
				'_ymkrf_pt1'      => '汚れにくい',
				'_ymkrf_pt2'      => '傷に強い',
				'_ymkrf_pt3'      => 'お掃除ラクラク',
				'_ymkrf_caution'  => '※写真はイメージになります。本体及び便座のみとなります。本体：アメージュZ（アクアセラミック）／便座：シャワートイレRG10H',
				'_ymkrf_note_colors' => 'none',
			),
			'total'  => 139800,
			'maker'  => 'lixil',
			'shops'  => array( 'hakui', 'komathu', 'shinkaga', 'tazuruhama', 'kanadu' ),
			'main'   => array( 'main', 'LIXIL アメージュZ（アクアセラミック／シャワートイレRG10H）' ),
			'labels' => array( '_ymkrf_lbl_colors' => '便器カラー' ),
			'sets' => array(
				'_ymkrf_colors' => array(
					array( 'color-purewhite', 'ピュアホワイト' ),
					array( 'color-offwhite',  'オフホワイト' ),
				),
			),
			'speclist' => array(
				array( '快適機能',   "暖房便座\nスローダウン便座\n暖房便座コード内蔵\n点字対応" ),
				array( '洗浄機能',   "おしり泡ジェット洗浄（泡沫）\nソフトビデ洗浄（泡沫）" ),
				array( '省エネ機能', "超節水ECO５\nワンタッチ節電（8h）" ),
				array( '清潔機能',   "パワーストリーム洗浄\n本体スライド着脱\n女性専用レディスノズル\nノズルそうじ\nノズルオートクリーニング\nノズル先端着脱\nキレイ便座\n便フタワンタッチ着脱\nアクアセラミック" ),
			),
			'feats' => array(
				array( '汚れがつかない衛生陶器に最適な新素材', '「アクアセラミック」',
				       '汚れが洗浄ごとにつるんと落ちます！',
				       '便器鉢内に付いた汚れが、トイレを洗浄すると、つるんと落ちます。', 'point-aqua' ),
				array( '', '', '簡単お掃除！',
				       '簡単お掃除で、新品のようなツルツルが長続きします。', 'point-aqua2' ),
				array( 'お掃除らくらく！', '「フチレス形状」・「キレイ便座」',
				       'サッとひと拭き「フチレス形状」',
				       '奥も手前も便器のフチを丸ごとなくし、サッとひと拭き、お掃除ラクラクです。', 'point-rimless' ),
				array( '', '', 'お掃除ラクラク「キレイ便座」',
				       "汚れが入りやすい継ぎ目をなくしました。さらに便座裏は防汚素材なのでお掃除ラクラク。\n（左：従来／右：キレイ便座）", 'point-seat' ),
				array( '強力に洗い流す。', '「パワーストリーム洗浄」',
				       '少ない水でしっかり洗浄。',
				       '強力な水流が便器鉢内のすみずみまで回り、少ない水でもしっかり汚れを洗い流します。', 'point-stream' ),
				array( '足元スリム', 'シャープなフォルム',
				       '汚れが拭きやすい',
				       'シャープで足元スリムなフォルムは、汚れも拭きやすく、お手入れ簡単です。', 'point-slim' ),
			),
			'opts' => array(
				array( 'opt-rwa30', 'シャワートイレ（CW-RWA30HQ）', '70000',
				       '★フルオート便座。フルオート便器洗浄。アクアセラミック仕様。', '' ),
			),
		),

		/* ===== Gグレード ピュアレストQR（TOTO） ===== */
		array(
			'slug'   => 'purerest-qr',
			'prefix' => 'qr',
			'title'  => 'ピュアレストQR',
			'meta'   => array(
				'_ymkrf_catch'    => '簡単操作のベーシックモデル。',
				'_ymkrf_grade'    => 'Gグレード',
				'_ymkrf_name'     => 'ピュアレストQR',
				'_ymkrf_size'     => 'ウォシュレットBV1 TCF2213E',
				'_ymkrf_work'     => '38000',
				'_ymkrf_item'     => '131800',
				'_ymkrf_days'     => '',
				'_ymkrf_daystext' => '半日',
				'_ymkrf_pt1'      => 'ローシルエット',
				'_ymkrf_pt2'      => 'ノズルきれい',
				'_ymkrf_pt3'      => '環境にやさしい',
				'_ymkrf_caution'  => '※写真はイメージになります。本体：ピュアレストQR／便座：ウォシュレットBV1 TCF2213E　※リフォーム配管が必要な場合は別途（+5,500円）がかかります。',
				'_ymkrf_note_colors' => '※パステルアイボリーは受注生産のため、到着まで2週間ほどかかります',
			),
			'total'  => 169800,
			'maker'  => 'toto',
			'shops'  => array( 'komathu', 'hakui', 'shinkaga', 'tazuruhama', 'kanadu', 'asahi' ),
			'main'   => array( 'main', 'TOTO ピュアレストQR（ウォシュレットBV1）' ),
			'labels' => array( '_ymkrf_lbl_colors' => '便器カラー' ),
			'sets' => array(
				'_ymkrf_colors' => array(
					array( 'color-white', 'ホワイト' ),
					array( 'color-ivory', 'パステルアイボリー' ),
				),
			),
			'speclist' => array(
				array( '快適機能',   "暖房便座\n着座センサー\n便座・便ふたソフト閉止" ),
				array( '洗浄機能',   "おしり洗浄\nビデ洗浄\nムーブ洗浄\n水勢調節\n洗浄位置調節" ),
				array( '省エネ機能', "タイマー節電\nおまかせ節電" ),
				array( '清潔機能',   "セルフクリーニング\nクリーン便座\nクリーンノズル\n抗菌\n便ふた着脱\n本体ワンタッチ着脱\nノズルそうじ" ),
			),
			'feats' => array(
				array( '撥水性のある樹脂が汚れをはじく！', '「クリーン便座」「クリーンノズル」',
				       'サッとひとふきでお手入れ',
				       'ウォシュレットの便座とノズルには、防汚効果の高いクリーン樹脂を採用。撥水性のある特殊な樹脂が汚れをはじくから、汚れてもサッとひとふきでお手入れできます。',
				       'point-resin', '', 'point-clean' ),
				array( '快適な洗浄機能！', '「ウォシュレット」',
				       '快適な洗い心地',
				       '一定の間隔で、水に空気を混ぜることで洗浄の強弱をつけ、たっぷりとした洗い心地と、しっかりとした洗い心地の両立を実現しました。',
				       'point-washlet', '※写真はイメージです' ),
				array( 'かしこく節電。', '「タイマー節電」「おまかせ節電」',
				       '2つのモードで、かしこく節電',
				       '使わない時間帯に合わせて便座の保温を控えます。ご家庭の使い方に合わせて、無理なく電気代をおさえられます。',
				       'point-eco' ),
			),
			'opts' => array(
				array( 'opt-bv2',   'ウォシュレット（BV2 TCF2223E）', '4000',   'オートパワー脱臭付。', '' ),
				array( 'opt-s1',    'ウォシュレット（S1 TCF6543）',   '61000',  'レバー便器洗浄タイプ。', '' ),
				array( 'opt-f1a',   'アプリコット（F1A TCF4713AKR）オート洗浄タイプ', '98000', '便座・便ふたソフト閉止。', '' ),
				array( 'opt-f4a',   'アプリコット（F4A TCF4744AK）オート洗浄タイプ',  '175000', '瞬間暖房便座・オート開閉・においきれい・やわらかライト・温風乾燥。', '' ),
				array( 'opt-rwa30', 'シャワートイレ（CW-RWA30HQ）',   '70000',  '★フルオート便座。フルオート便器洗浄。アクアセラミック仕様。', '' ),
			),
		),

		/* ===== Fグレード NewアラウーノV(S5)（Panasonic） ===== */
		array(
			'slug'   => 'alauno-vs5',
			'prefix' => 'vs5',
			'title'  => 'NewアラウーノV（S5）',
			'meta'   => array(
				'_ymkrf_catch'    => '手洗いが設置できない狭いスペースにもおすすめです。',
				'_ymkrf_grade'    => 'Fグレード',
				'_ymkrf_name'     => 'NewアラウーノV（S5）',
				'_ymkrf_size'     => '専用トワレS5',
				'_ymkrf_work'     => '38000',
				'_ymkrf_item'     => '161800',
				'_ymkrf_days'     => '',
				'_ymkrf_daystext' => '半日',
				'_ymkrf_pt1'      => '3Dツイスター',
				'_ymkrf_pt2'      => 'エコ',
				'_ymkrf_pt3'      => 'お掃除ラクラク',
				'_ymkrf_caution'  => '※写真はイメージになります。※朝日店は手洗付きの展示です。',
				'_ymkrf_note_colors' => 'none',
			),
			'total'  => 199800,
			'maker'  => 'panasonic',
			'shops'  => array( 'komathu', 'hakui', 'shinkaga', 'kawakita', 'kanadu', 'asahi' ),
			'main'   => array( 'main', 'Panasonic NewアラウーノV（専用トワレS5）' ),
			'labels' => array( '_ymkrf_lbl_colors' => '便器カラー' ),
			'sets' => array(
				'_ymkrf_colors' => array(
					array( 'color-white', 'ホワイト' ),
				),
			),
			'speclist' => array(
				array( '快適機能',   "暖房便座\n着座センサー\nリモコン\n低水圧対応\n停電対応（手動）" ),
				array( '洗浄機能',   "おしり洗浄（パワーパルス洗浄）\nおしりムーブ洗浄\nリズム洗浄\nビデ洗浄\nビデムーブ洗浄" ),
				array( '省エネ機能', "おまかせ節電\n8時間切りタイマー" ),
				array( '清潔機能',   "スゴピカ素材（有機ガラス系）\nひとふき形状\n水位調節\nステンレスノズル\nノズル位置調整機能\n便座本体\n便フタ着脱機能\n抗菌加工（便座表）\nおまかせノズルクリーニング" ),
			),
			'feats' => array(
				array( '洗浄力と節水の両立を実現。', '３Ｄツイスター水流・ターントラップ洗浄',
				       'まんべんなく洗って、一気に流し切る',
				       '時間をかけて（約20秒）まんべんなく洗い、流れの方向を変えて一気に排水。スゴピカ素材（有機ガラス系）ならではの形状が生み出した、お掃除ラクラク水流です！',
				       'point-twister' ),
				array( '', '', '小洗浄1回がわずか、3.0Ｌ！',
				       '独自の技術「ターントラップ方式」が、洗浄する水量をおさえて、年間の水道代を約1/4に。',
				       'point-turntrap', '※メーカー測定値。（カタログより抜粋）' ),
				array( 'フチ裏もさっとキレイに！', 'ひとふき形状',
				       'フチ裏がおそうじしやすい形に',
				       '汚れがたまりやすかった便器のフチ裏を、おそうじがラクな形状にしました。',
				       'point-hitofuki' ),
			),
			'opts' => array(
				array( 'opt-towelring', 'タオルリング（CHA22WS）', '3600',  'スタンダードなタオルリングです。', '' ),
				array( 'opt-paper',     '紙巻器（CHA21WS）',       '4400',  '１連タイプの紙巻器です。', '' ),
				array( 'opt-handrail',  '手すり Ｉ型セット Φ35×600mm', '7400', '', '' ),
				array( 'opt-towares3',  '専用トワレS3',            '11200', '便フタ自動開閉付きで、機能充実。', '' ),
			),
		),

		/* ===== Eグレード アメージュZ 高機能モデル（LIXIL） ===== */
		array(
			'slug'   => 'amage-z-premium',
			'prefix' => 'amze',
			'title'  => 'アメージュZ 高機能モデル',
			'meta'   => array(
				'_ymkrf_catch'    => '欲しい機能を凝縮した、おすすめトイレパック。',
				'_ymkrf_grade'    => 'Eグレード',
				'_ymkrf_name'     => 'アメージュZ 高機能モデル',
				'_ymkrf_sub'      => 'アクアセラミック',
				'_ymkrf_size'     => 'シャワートイレCW-RWA30AHQ',
				'_ymkrf_work'     => '38000',
				'_ymkrf_item'     => '171800',
				'_ymkrf_days'     => '',
				'_ymkrf_daystext' => '半日',
				'_ymkrf_pt1'      => 'フルオート',
				'_ymkrf_pt2'      => 'フチレス形状',
				'_ymkrf_pt3'      => 'お掃除ラクラク',
				'_ymkrf_caution'  => '※写真はイメージになります。便座：シャワートイレCW-RWA30AHQ',
				'_ymkrf_note_colors' => 'none',
			),
			'total'  => 209800,
			'maker'  => 'lixil',
			'shops'  => array( 'nonoichi', 'komathu', 'hakui', 'asahi' ),
			'main'   => array( 'main', 'LIXIL アメージュZ 高機能モデル（アクアセラミック／CW-RWA30AHQ）' ),
			'labels' => array( '_ymkrf_lbl_colors' => '便器カラー' ),
			'sets' => array(
				'_ymkrf_colors' => array(
					array( 'color-purewhite', 'ピュアホワイト（BW1）' ),
					array( 'color-offwhite',  'オフホワイト（BN8）' ),
					array( 'color-pink',      'ピンク（LR8）' ),
					array( 'color-bluegray',  'ブルーグレー（BB7）' ),
				),
			),
			'speclist' => array(
				array( '快適機能',   "フルオート便器洗浄\nフルオート便座\nＷパワー脱臭\nターボ脱臭\n便座ヒーターオートＯＦＦ\n着座センサー（荷重式）\nリモコン\n暖房便座\nスローダウン便座\n暖房便座コード内蔵\n点字対応" ),
				array( '洗浄機能',   "おしり洗浄（パワフル・マイルド）\nビデ洗浄" ),
				array( '省エネ機能', "超節水ECO５\nワンタッチ節電（8h）" ),
				array( '清潔機能',   "アクアセラミック\nフチレス便器\n女性専用レディスノズル\nノズルオートクリーニング\nノズル先端着脱\nキレイ便座\n便フタワンタッチ着脱\n抗菌樹脂\n鉢内スプレー\nノズル除菌\nスッキリノズルシャッター" ),
			),
			'feats' => array(
				array( '便器に手を触れず衛生的！', '「フルオート便座」「フルオート便器洗浄」',
				       '便フタを開け閉めする必要がありません',
				       '便器に近づくと自動で便フタが開き、離れると閉まります。節電にもつながります。',
				       'point-fullauto', '※写真はイメージです。' ),
				array( '', '', '流し忘れの心配もありません。',
				       '着座時間に応じて大・小を切り替え。便座から立ち上がると自動で洗浄します。',
				       'point-autoflush' ),
				array( 'お掃除らくらく！', '「フチレス形状」',
				       'フチを丸ごとなくしました。',
				       '奥も手前も便器のフチを丸ごとなくし、サッとひと拭き、お掃除ラクラクです。',
				       'point-rimless' ),
				array( '', '', '汚れが入りやすい継ぎ目をなくしました。',
				       'さらに、便座裏は防汚素材なのでお掃除ラクラク。',
				       'point-seat' ),
				array( '強力に洗い流す。', '「パワーストリーム洗浄」',
				       '少ない水でしっかり洗浄。',
				       '強力な水流が便器鉢内のすみずみまで回り、少ない水でもしっかり汚れを洗い流します。',
				       'point-stream' ),
				array( '汚れがつかない衛生陶器に最適な新素材', '「アクアセラミック」',
				       '汚れが洗浄ごとにつるんと落ちます！',
				       '便器鉢内に付いた汚れが、トイレを洗浄すると、つるんと落ちます。',
				       'point-aqua' ),
			),
			'opts' => array(),
		),

		/* ===== Dグレード プレアスLS（LIXIL） ===== */
		array(
			'slug'   => 'pleas-ls', 'prefix' => 'pleas', 'title' => 'プレアスLS',
			'meta'   => array(
				'_ymkrf_catch' => 'お掃除ラクラクで、エコロジーな超節水タイプ。',
				'_ymkrf_grade' => 'Dグレード', '_ymkrf_name' => 'プレアスLS',
				'_ymkrf_sub' => 'CLR4Aグレード', '_ymkrf_size' => '',
				'_ymkrf_work' => '38000', '_ymkrf_item' => '181800',
				'_ymkrf_days' => '', '_ymkrf_daystext' => '半日',
				'_ymkrf_pt1' => '汚れにくい', '_ymkrf_pt2' => 'エコ', '_ymkrf_pt3' => 'お掃除ラクラク',
				'_ymkrf_caution' => '※写真はイメージです。',
				'_ymkrf_note_colors' => 'none',
			),
			'total' => 219800, 'maker' => 'lixil',
			'shops' => array( 'nonoichi', 'komathu', 'shinkaga' ),
			'main'  => array( 'main', 'LIXIL プレアスLS（トイレ）' ),
			'labels' => array( '_ymkrf_lbl_colors' => '便器カラー' ),
			'sets' => array( '_ymkrf_colors' => array(
				array( 'color-purewhite', 'ピュアホワイト（BW1）' ),
				array( 'color-offwhite',  'オフホワイト（BN8）' ),
				array( 'color-pink',      'ピンク（LR8）' ),
				array( 'color-bluegray',  'ブルーグレー（BB7）' ),
			) ),
			'speclist' => array(
				array( '快適機能', "フルオート便器洗浄\nＷパワー脱臭\nターボ脱臭\n暖房便座\nスローダウン便座\n便座ヒーターオートＯＦＦ\n着座センサー\nリモコン\n点字対応" ),
				array( '洗浄機能', "おしり洗浄（泡ジェット洗浄）\nビデ洗浄（泡沫ソフト）\nおしりワイド洗浄\nおしりマッサージ洗浄\nワイドビデ洗浄\nノズル位置調節" ),
				array( '省エネ機能', "超節水ＥＣＯ５\nスーパー節電\nワンタッチ節電（8h）\n電源スイッチ" ),
				array( '清潔機能', "アクアセラミック\n鉢内除菌\nパワーストリーム洗浄\nお掃除リフトアップ（手動）\n女性専用レディスノズル\nスッキリノズルシャッター\nノズルそうじ\nノズルオートクリーニング\nノズル先端着脱\nキレイ便座\n便フタワンタッチ着脱\n抗菌樹脂" ),
			),
			'feats' => array(
				array( '汚れがつかない衛生陶器に最適な新素材', '「アクアセラミック」', '汚れが洗浄ごとにつるんと落ちます！',
				       '便器鉢内に付いた汚れが、トイレを洗浄すると、つるんと落ちます。', 'point-aqua' ),
				array( '', '', '簡単お掃除で新品のようなツルツルが長続き。', '', 'point-aqua2' ),
				array( '見た目もスッキリ', '「コンパクトなフォルム」', '狭いトイレ空間にも納まりやすい',
				       '奥行を抑えたコンパクトな形なので、狭いトイレ空間にも納まりやすくなっています。', 'point-compact' ),
				array( 'トイレまるごと除菌！', '「鉢内除菌」', 'プラズマクラスターイオンで除菌・消臭',
				       '水のかからない便座裏や便器内のすみずみまで行き渡り、除菌します。', 'point-jokin',
				       '※プラズマクラスター、Plasmaclusterは、シャープ株式会社の商標です' ),
			),
			'opts' => array(
				array( 'opt-towelring', 'タオルリング', '3300', 'スタンダードなタオルリングです。', '' ),
				array( 'opt-paper', 'ワンタッチ式紙巻器（CF-AA22H/WA）', '3200', '片手で簡単にペーパーの交換ができるワンタッチ式。', '' ),
				array( 'opt-grade5', '便器5グレード', '15000', '温風乾燥付き。', '' ),
				array( 'opt-grade6', '便器6グレード', '82000', 'フルオート便座、温風乾燥付き。', '' ),
			),
		),

		/* ===== Cグレード アラウーノS160タイプ１K（Panasonic） ===== */
		array(
			'slug'   => 'alauno-s160', 'prefix' => 's160', 'title' => 'アラウーノS160タイプ１K',
			'meta'   => array(
				'_ymkrf_catch' => 'シンプルさが好評の全自動おそうじトイレ。',
				'_ymkrf_grade' => 'Cグレード', '_ymkrf_name' => 'アラウーノS160タイプ１K',
				'_ymkrf_work' => '38000', '_ymkrf_item' => '211800',
				'_ymkrf_days' => '', '_ymkrf_daystext' => '半日',
				'_ymkrf_pt1' => '泡のパワー', '_ymkrf_pt2' => 'スキマレス設計', '_ymkrf_pt3' => 'スリム形状',
				'_ymkrf_caution' => '※写真はイメージです。',
				'_ymkrf_note_colors' => 'none',
			),
			'total' => 249800, 'maker' => 'panasonic',
			'shops' => array( 'komathu', 'nonoichi' ),
			'main'  => array( 'main', 'Panasonic アラウーノS160 タイプ１K（トイレ）' ),
			'labels' => array( '_ymkrf_lbl_colors' => '便器カラー' ),
			'sets' => array( '_ymkrf_colors' => array( array( 'color-white', 'ホワイト' ) ) ),
			'speclist' => array(
				array( '快適機能', "オート開閉（便ふた）\n電動開閉（便座・便ふた）\nオート脱臭\n暖房便座\nリモコン\nオート洗浄\nアラウーノアプリ対応\n低水圧対応\n停電対応（電池式）\n停電対応（手動）\nチャイルドロック" ),
				array( '洗浄機能', "おしり洗浄\nビデ洗浄\nおしりリズム洗浄\nビデリズム洗浄" ),
				array( '省エネ機能', "エコモード\n4・6・8時間切タイマー" ),
				array( '清潔機能', "スゴピカ素材（有機ガラス系）\nひとふき形状\n水位調節\n自動おそうじ機能\nトリプル汚れガード（ハネガード便座連動）\nスキマレス設計\nステンレスノズル\nクローズ洗浄モード\nノズル位置調整機能" ),
			),
			'feats' => array(
				array( '泡で受け止めて、泡で洗う。', '「激落ちバブル」「スパイラル水流」', '流すたびに「泡」と「水流」でしっかりお掃除！',
				       'ミリバブル（直径約5mm）で大きな汚れを強力に除去し、次に微細なマイクロバブル（直径約60μm）で小さな汚れを除去します。', 'point-bubble' ),
				array( '', '', 'お手入れがラクなスキマレス設計',
				       "継ぎ目がほとんどなく、お手入れがラクなスキマレス設計です。\n（左：従来商品／右：スキマレス設計）", 'point-sukimaless', '※パナソニックHPより' ),
				array( '便ふたを閉じてから流す', '「クローズ洗浄モード」', '衛生面が気になる方におすすめ',
				       '立ち上がると、便ふたが閉まって洗浄します。立ったまま用を足した場合は、洗浄ボタンを押すと便ふたが閉まって流れます。', 'point-close' ),
				array( '便利と健康をサポート！', 'アラウーノアプリ対応', '日々のお通じの記録ができ、体調管理にお役立ち',
				       '『お通じモニタ』『みまもりモニタ』『わたし好み登録』。スマートフォンとの連携でここまで便利になります。', 'point-app' ),
				array( 'トビハネヨゴレを泡でおさえる。', '「トリプル汚れガード」', 'ハネガード・タレガード・モレガード',
				       "泡のクッションで受け止める「ハネガード」、フチの立ち上がりで垂れ出しにくい「タレガード」、便座と便器の合わせでせき止める「モレガード」。",
				       'point-guard', '※男性の立ったままの小用を想定した機能です。角度や勢いによって外に漏れる場合があります。' ),
			),
			'opts' => array(
				array( 'opt-towelring', 'タオルリング', '3600', 'アラウーノの四角丸形状を採用したデザイン。', '' ),
				array( 'opt-paper', '紙巻器', '4400', '1連タイプの紙巻器です。', '' ),
				array( 'opt-remote', 'スティックリモコン', '8300', 'スタイリッシュなスティックリモコンです。', '※色はホワイトまたはブラック' ),
				array( 'opt-handrail', '手すり I型', '7400', '立ち上がり時に便利な手すり。', '' ),
			),
		),

		/* ===== Bグレード GGA3（TOTO） ===== */
		array(
			'slug'   => 'gga3', 'prefix' => 'gga3', 'title' => 'GGA3',
			'meta'   => array(
				'_ymkrf_catch' => 'シンプルな機能とローシルエットデザイン。',
				'_ymkrf_grade' => 'Bグレード', '_ymkrf_name' => 'GGA3',
				'_ymkrf_work' => '38000', '_ymkrf_item' => '251800',
				'_ymkrf_days' => '', '_ymkrf_daystext' => '半日',
				'_ymkrf_pt1' => 'ローシルエット', '_ymkrf_pt2' => 'ノズルきれい', '_ymkrf_pt3' => '環境にやさしい',
				'_ymkrf_caution' => '※写真はイメージです。',
				'_ymkrf_note_colors' => 'none',
			),
			'total' => 289800, 'maker' => 'toto', 'shops' => array(),
			'main'  => array( 'main', 'TOTO GGA3（トイレ）' ),
			'labels' => array( '_ymkrf_lbl_colors' => '便器カラー' ),
			'sets' => array( '_ymkrf_colors' => array(
				array( 'color-white', 'ホワイト' ),
				array( 'color-ivory', 'パステルアイボリー' ),
			) ),
			'speclist' => array(
				array( '快適機能', "オート便器洗浄\nオート開閉\nリモコン便座・便フタ開閉\n温風乾燥\nオートパワー脱臭・脱臭\nリモコン便器洗浄（大・小）\n着座センサー\nリモコン\n停電時安心設計\n便座・便ふたソフト閉止" ),
				array( '洗浄機能', "おしり洗浄\nやわらか洗浄\nビデ洗浄\nムーブ洗浄\n水勢調節\n洗浄位置調節" ),
				array( '省エネ機能', "ダブル保温便座\nタイマー節電\nおまかせ節電" ),
				array( '清潔機能', "ノズルきれい\nセルフクリーニング\nプレミスト\nクリーン便座（継ぎ目なし）\nクリーンノズル\nクリーンケース\n抗菌\nお掃除リフト\n便ふた着脱\nノズルそうじ\nセフィオンテクト\nフチなし形状" ),
			),
			'feats' => array(
				/* この2つは説明図（もともと白地）なので、白い枠を残しています＝8番目の '1' */
				array( 'GGAが提案する、', '３つの特長。', 'ノズルきれい',
				       '「きれい除菌水」でノズルの内側も外側も自動で洗浄・除菌。使用していない時も定期的に洗浄し、キレイが長持ちします。', 'point-nozzle', '', '', '1' ),
				array( '', '', 'セフィオンテクト',
				       '約1200℃で焼き上げた、ナノレベルに滑らかな陶器表面だから、汚れがツルっと落ちてずっときれいが続きます。', 'point-cefiontect' ),
				array( '', '', 'トルネード洗浄',
				       '渦を巻くようなトルネード洗浄が、少ない水で、汚れをしっかり洗い流します。', 'point-tornado' ),
				array( 'エコロジーでエコノミー。', '「4.8L洗浄で超節水！」', '従来の1/3の水で洗浄',
				       '1回あたり13L必要だった15年前の便器と比べて、大幅な節水を実現しました。毎日普通に使っているだけで、いつの間にか大きな節約効果があります。', 'point-eco', '', '', '1' ),
			),
			'opts' => array(
				array( 'opt-towelbar', 'タオル掛け（YT408S4R）', '7900', 'スタンダードなタオル掛けです。', '' ),
				array( 'opt-handrail', '木製手すり I型（φ32×616mm）', '13500', '立ち上がり時に便利な手すりです。', '' ),
				array( 'opt-mirror', '化粧鏡（300×800）', '16200', 'ベーシックなタイプの鏡です。', '' ),
				array( 'opt-gg3800', 'GG3-800グレードに変更', '-900', "オート開閉／オートECO小／温風便座／リモコン便座便ふた開閉。", '' ),
			),
		),

		/* ===== Bグレード GGA1【手洗いカウンター付】（TOTO） ===== */
		array(
			'slug'   => 'gga1-counter', 'prefix' => 'gga1', 'title' => 'GGA1【手洗いカウンター付】',
			'meta'   => array(
				'_ymkrf_catch' => 'シンプルな機能とローシルエットデザイン。',
				'_ymkrf_grade' => 'Bグレード', '_ymkrf_name' => 'GGA1', '_ymkrf_sub' => '【手洗いカウンター付】',
				'_ymkrf_work' => '53000', '_ymkrf_item' => '336800',
				'_ymkrf_days' => '', '_ymkrf_daystext' => '半日',
				'_ymkrf_pt1' => 'ローシルエット', '_ymkrf_pt2' => 'ノズルきれい', '_ymkrf_pt3' => '環境にやさしい',
				'_ymkrf_caution' => '※写真はオプションを含んだイメージ画像となります。',
				'_ymkrf_note_colors' => 'none',
			),
			'total' => 389800, 'maker' => 'toto', 'shops' => array(),
			'main'  => array( 'main', 'TOTO GGA1 手洗いカウンター付（トイレ）' ),
			'labels' => array( '_ymkrf_lbl_colors' => '便器カラー' ),
			'sets' => array( '_ymkrf_colors' => array( array( 'color-white', 'ホワイト' ) ) ),
			'speclist' => array(
				array( '快適機能', "オート便器洗浄\nオートパワー脱臭\n脱臭\nリモコン便器洗浄\n着座センサー\nリモコン\n停電時安心設計\n便座・便ふたソフト閉止" ),
				array( '洗浄機能', "おしり洗浄\nやわらか洗浄\nビデ洗浄\nムーブ洗浄\n水勢調節\n洗浄位置調節" ),
				array( '省エネ機能', "ダブル保温便座\nタイマー節電\nおまかせ節電" ),
				array( '清潔機能', "ノズルきれい\nセルフクリーニング\nプレミスト\nクリーン便座（継ぎ目なし）\nクリーンノズル\nクリーンケース\n抗菌\nお掃除リフト\n便ふた着脱\nノズルそうじ\nセフィオンテクト\nフチなし形状" ),
			),
			'feats' => array(
				array( '空間の可能性を広げる', '「ローシルエット」', 'ローシルエット',
				       'ローシルエットな形状と、上面に平面部があることで、トイレ空間に余裕が生まれます。', 'point-lowsilhouette' ),
				array( '', '', 'クリーンなデザイン', '直線的な造形と、凹凸の少ないクリーンなデザイン。', 'point-design' ),
				array( 'エコロジーでエコノミー。', '「4.8L洗浄で超節水！」', '従来の1/3の水で洗浄',
				       '1回あたり13L必要だった15年前の便器と比べて、大幅な節水を実現しました。毎日普通に使っているだけで、いつの間にか大きな節約効果があります。', 'point-eco' ),
				array( 'ノズルを除菌', '「ノズルきれい」', '使うたびに自動で洗浄',
				       '使用後に「きれい除菌水」でノズルを自動洗浄します。', 'point-nozzle' ),
			),
			'opts' => array(
				array( 'opt-towelbar', 'タオル掛け（YT408S4R）', '7900', 'スタンダードなタオル掛けです。', '' ),
				array( 'opt-handrail', '木製手すり I型（φ32×616mm）', '13500', '立ち上がり時に便利な手すりです。', '' ),
				array( 'opt-mirror', '化粧鏡（300×800）', '16200', 'ベーシックなタイプの鏡です。', '' ),
				array( 'opt-gg3', 'GG3グレードに変更', '45500', 'オート開閉／リモコン便座／便ふた開閉／温風乾燥。', '' ),
			),
		),

		/* ===== Aグレード アラウーノS160タイプ１K【手洗いカウンター付】（Panasonic） ===== */
		array(
			'slug'   => 'alauno-s160-counter', 'prefix' => 's160c', 'title' => 'アラウーノS160タイプ１K【手洗いカウンター付】',
			'meta'   => array(
				'_ymkrf_catch' => 'シンプルさが好評の全自動おそうじトイレ。',
				'_ymkrf_grade' => 'Aグレード', '_ymkrf_name' => 'アラウーノS160タイプ１K', '_ymkrf_sub' => '【手洗いカウンター付】',
				'_ymkrf_work' => '53000', '_ymkrf_item' => '386800',
				'_ymkrf_days' => '', '_ymkrf_daystext' => '半日',
				'_ymkrf_pt1' => '泡のパワー', '_ymkrf_pt2' => 'スキマレス設計', '_ymkrf_pt3' => 'スリム形状',
				'_ymkrf_caution' => '※写真はオプションを含んだイメージ画像となります。',
				'_ymkrf_note_colors' => 'none',
			),
			'total' => 439800, 'maker' => 'panasonic',
			'shops' => array( 'hakui', 'kanadu' ),
			'main'  => array( 'main', 'Panasonic アラウーノS160 タイプ１K 手洗いカウンター付（トイレ）' ),
			'labels' => array( '_ymkrf_lbl_colors' => '便器カラー' ),
			'sets' => array( '_ymkrf_colors' => array( array( 'color-white', 'ホワイト' ) ) ),
			'speclist' => array(
				array( '快適機能', "オート開閉（便ふた）\n電動開閉（便座・便ふた）\nオート脱臭\n暖房便座\nリモコン\nオート洗浄\nアラウーノアプリ対応\n低水圧対応\n停電対応（電池式）\n停電対応（手動）\nチャイルドロック" ),
				array( '洗浄機能', "おしり洗浄\nビデ洗浄\nおしりリズム洗浄\nビデリズム洗浄" ),
				array( '省エネ機能', "エコモード\n4・6・8時間切タイマー" ),
				array( '清潔機能', "スゴピカ素材（有機ガラス系）\nひとふき形状\n水位調節\n自動おそうじ機能\nトリプル汚れガード（ハネガード便座連動）\nスキマレス設計\nステンレスノズル\nクローズ洗浄モード\nノズル位置調整機能" ),
			),
			'feats' => array(
				array( '泡で受け止めて、泡で洗う。', '「激落ちバブル」「スパイラル水流」', '流すたびに「泡」と「水流」でしっかりお掃除！',
				       'ミリバブル（直径約5mm）で大きな汚れを強力に除去し、次に微細なマイクロバブル（直径約60μm）で小さな汚れを除去します。', 'point-bubble' ),
				array( '', '', 'お手入れがラクなスキマレス設計',
				       "継ぎ目がほとんどなく、お手入れがラクなスキマレス設計です。\n（左：従来商品／右：スキマレス設計）", 'point-sukimaless', '※パナソニックHPより' ),
				array( '便ふたを閉じてから流す', '「クローズ洗浄モード」', '衛生面が気になる方におすすめ',
				       '立ち上がると、便ふたが閉まって洗浄します。立ったまま用を足した場合は、洗浄ボタンを押すと便ふたが閉まって流れます。', 'point-close' ),
				array( '便利と健康をサポート！', 'アラウーノアプリ対応', '日々のお通じの記録ができ、体調管理にお役立ち',
				       '『お通じモニタ』『みまもりモニタ』『わたし好み登録』。スマートフォンとの連携でここまで便利になります。', 'point-app' ),
				array( 'トビハネヨゴレを泡でおさえる。', '「トリプル汚れガード」', 'ハネガード・タレガード・モレガード',
				       "泡のクッションで受け止める「ハネガード」、フチの立ち上がりで垂れ出しにくい「タレガード」、便座と便器の合わせでせき止める「モレガード」。",
				       'point-guard', '※男性の立ったままの小用を想定した機能です。角度や勢いによって外に漏れる場合があります。' ),
				array( 'カウンター付き手洗い', '「手洗いユニット」', '汚れがつきにくい！',
				       '便器と同様、水アカをはじく新素材。排水口も一体成形でお手入れが楽です。',
				       'point-hand', '', 'point-hand2' ),
			),
			'opts' => array(
				array( 'opt-towelring', 'タオルリング', '3600', 'アラウーノの四角丸形状を採用したデザイン。', '' ),
				array( 'opt-paper', '紙巻器', '4400', '1連タイプの紙巻器です。', '' ),
				array( 'opt-remote', 'スティックリモコン', '8300', 'スタイリッシュなスティックリモコンです。', '※色はホワイトまたはブラック' ),
				array( 'opt-handrail', '手すり I型', '7400', '立ち上がり時に便利な手すり。', '' ),
			),
		),

		/* ===== Sグレード サティスS（LIXIL） ===== */
		array(
			'slug'   => 'satis-s', 'prefix' => 'satis', 'title' => 'サティスS',
			'meta'   => array(
				'_ymkrf_catch' => 'お掃除ラクラクで、エコロジーな超節水タイプ。',
				'_ymkrf_grade' => 'Sグレード', '_ymkrf_name' => 'サティスS', '_ymkrf_sub' => 'SR6グレード',
				'_ymkrf_work' => '38000', '_ymkrf_item' => '301800',
				'_ymkrf_days' => '', '_ymkrf_daystext' => '半日',
				'_ymkrf_pt1' => '汚れにくい', '_ymkrf_pt2' => 'エコ', '_ymkrf_pt3' => 'お掃除ラクラク',
				'_ymkrf_caution' => '※写真はイメージです。',
				'_ymkrf_note_colors' => 'none',
			),
			'total' => 339800, 'maker' => 'lixil',
			'shops' => array( 'nonoichi', 'hakui', 'kawakita', 'shinkaga', 'kanadu' ),
			'main'  => array( 'main', 'LIXIL サティスS（トイレ）' ),
			'images' => array( array( 'lineup', 'サティスSの4色そろい踏み' ) ),
			'labels' => array( '_ymkrf_lbl_colors' => '便器カラー' ),
			'sets' => array( '_ymkrf_colors' => array(
				array( 'color-purewhite', 'ピュアホワイト（BW1）' ),
				array( 'color-offwhite',  'オフホワイト（BN8）' ),
				array( 'color-pink',      'ピンク（LR8）' ),
				array( 'color-bluegray',  'ブルーグレー（BB7）' ),
			) ),
			'speclist' => array(
				array( '快適機能', "フルオート便器洗浄\nフルオート便座\nほのかライト\nＷパワー脱臭\n温風乾燥\n暖房便座\nスローダウン便座\n便座ヒーターオートＯＦＦ\n着座センサー\nリモコン（設定リモコン対応）\nスマートフォンリモコン\nスマートフォン対応\n点字対応" ),
				array( '洗浄機能', "おしり洗浄（パワフル・マイルド）\nビデ洗浄\nおしりワイド洗浄\nおしりターボ洗浄\nスーパーワイドビデ洗浄\nノズル位置調節" ),
				array( '省エネ機能', "超節水ＥＣＯ５\nスーパー節電\nワンタッチ節電（8h）\n電源スイッチ" ),
				array( '清潔機能', "アクアセラミック\n鉢内除菌\nパワーストリーム洗浄\nお掃除リフトアップ\n女性専用レディスノズル\nスッキリノズルシャッター\nノズルお掃除モード\nノズルそうじ\nノズルオートクリーニング\nノズル先端着脱\nキレイ便座\n便フタワンタッチ着脱\n抗菌樹脂" ),
			),
			'feats' => array(
				array( '汚れがつかない衛生陶器に最適な新素材', '「アクアセラミック」', '汚れが洗浄ごとにつるんと落ちます！',
				       '便器鉢内に付いた汚れが、トイレを洗浄すると、つるんと落ちます。', 'point-aqua' ),
				array( '', '', '簡単お掃除で新品のようなツルツルが長続き。', '', 'point-aqua2' ),
				array( 'お掃除ラクラク', '「キレイ便座」', '汚れが入りやすいつぎ目をなくしました！',
				       'つぎ目がないのでサッとひと拭き！さらに便座裏は防汚素材で、汚れてもサッとひと拭き、お掃除ラクラク。', 'point-seat' ),
				array( 'さらに快適に。', '「便フタ閉後洗浄モード」', '衛生的で清掃性も向上。',
				       '便座から立ち上がると自動で便フタが閉まり、便器洗浄します。', 'point-autoclose' ),
			),
			'opts' => array(
				array( 'opt-towelring', 'タオルリング', '3300', 'スタンダードなタオルリングです。', '' ),
				array( 'opt-paper', '紙巻器', '3200', '1連タイプ。', '' ),
				array( 'opt-remote', 'スマートリモコン', '9800', 'スタイリッシュなリモコンです。', '' ),
				array( 'opt-booster', '機能部ＳＲ６ ブースター付', '34100', '水圧が低い環境下でも安心して設置できます。', '' ),
			),
		),

		/* ===== Sグレード サティスS【手洗いカウンター付】（LIXIL） ===== */
		array(
			'slug'   => 'satis-s-counter', 'prefix' => 'satisc', 'title' => 'サティスS【手洗いカウンター付】',
			'meta'   => array(
				'_ymkrf_catch' => 'お掃除ラクラクで、エコロジーな超節水タイプ。',
				'_ymkrf_grade' => 'Sグレード', '_ymkrf_name' => 'サティスS', '_ymkrf_sub' => '【手洗いカウンター付】',
				'_ymkrf_work' => '53000', '_ymkrf_item' => '446800',
				'_ymkrf_days' => '', '_ymkrf_daystext' => '半日',
				'_ymkrf_pt1' => '汚れにくい', '_ymkrf_pt2' => 'エコ', '_ymkrf_pt3' => 'お掃除ラクラク',
				'_ymkrf_caution' => '※画像はイメージです。',
				'_ymkrf_note_colors' => 'none',
			),
			'total' => 499800, 'maker' => 'lixil',
			'shops' => array( 'nonoichi', 'komathu', 'hakui' ),
			'main'  => array( 'main', 'LIXIL サティスS 手洗いカウンター付（トイレ）' ),
			'labels' => array( '_ymkrf_lbl_colors' => '便器カラー', '_ymkrf_lbl_tops' => 'キャビネットカラー' ),
			'sets' => array(
				'_ymkrf_colors' => array(
					array( 'color-purewhite', 'ピュアホワイト' ),
					array( 'color-offwhite',  'オフホワイト' ),
				),
				'_ymkrf_tops' => array(
					array( 'cab-mocha', 'クリエモカ' ),
					array( 'cab-pale',  'クリエペール' ),
				),
			),
			'speclist' => array(
				array( '快適機能', "フルオート便器洗浄\nフルオート便座\nほのかライト\nＷパワー脱臭\n温風乾燥\n暖房便座\nスローダウン便座\n便座ヒーターオートＯＦＦ\n着座センサー\nリモコン（設定リモコン対応）\nスマートフォンリモコン\nスマートフォン対応\n点字対応" ),
				array( '洗浄機能', "おしり洗浄（パワフル・マイルド）\nビデ洗浄\nおしりワイド洗浄\nおしりターボ洗浄\nスーパーワイドビデ洗浄\nノズル位置調節" ),
				array( '省エネ機能', "超節水ＥＣＯ５\nスーパー節電\nワンタッチ節電（8h）\n電源スイッチ" ),
				array( '清潔機能', "アクアセラミック\n鉢内除菌\nパワーストリーム洗浄\nお掃除リフトアップ\n女性専用レディスノズル\nスッキリノズルシャッター\nノズルお掃除モード\nノズルそうじ\nノズルオートクリーニング\nノズル先端着脱\nキレイ便座\n便フタワンタッチ着脱\n抗菌樹脂" ),
			),
			'feats' => array(
				array( '汚れがつかない衛生陶器に最適な新素材', '「アクアセラミック」', '汚れが洗浄ごとにつるんと落ちます！',
				       '便器鉢内に付いた汚れが、トイレを洗浄すると、つるんと落ちます。', 'point-aqua' ),
				array( '', '', '簡単お掃除で新品のようなツルツルが長続き。', '', 'point-aqua2' ),
				array( 'お掃除ラクラク', '「キレイ便座」', '汚れが入りやすいつぎ目をなくしました！',
				       'つぎ目がないのでサッとひと拭き！さらに便座裏は防汚素材で、汚れてもサッとひと拭き、お掃除ラクラク。', 'point-seat' ),
				array( 'さらに快適に。', '「便フタ閉後洗浄モード」', '衛生的で清掃性も向上。',
				       '便座から立ち上がると自動で便フタが閉まり、便器洗浄します。', 'point-autoclose' ),
			),
			'opts' => array(
				array( 'opt-counterl', '手洗いカウンターＬ型', '21000', '長さ1,400mmハンドル水栓。配管・コード類を隠せます。', '' ),
				array( 'opt-corner', 'コーナー手洗い付に変更', '-62000', '狭小スペースに最適。', '' ),
				array( 'opt-paper', '紙巻器（CF-AA22H）', '2800', '１連タイプ。', '' ),
			),
		),

		/* ===== プレミアム ネオレストRS3（TOTO） ===== */
		array(
			'slug'   => 'neorest-rs3', 'prefix' => 'rs3', 'title' => 'ネオレストRS3',
			'meta'   => array(
				'_ymkrf_catch' => 'すっきりとしたレストルーム空間を実現。',
				'_ymkrf_grade' => 'プレミアム', '_ymkrf_name' => 'ネオレストRS3',
				'_ymkrf_work' => '38000', '_ymkrf_item' => '341800',
				'_ymkrf_days' => '', '_ymkrf_daystext' => '半日',
				'_ymkrf_pt1' => 'きれい除菌水', '_ymkrf_pt2' => '上質な洗い心地', '_ymkrf_pt3' => '環境にやさしい',
				'_ymkrf_caution' => '※写真はイメージです。',
				'_ymkrf_note_colors' => 'none',
			),
			'total' => 379800, 'maker' => 'toto',
			'shops' => array( 'nonoichi', 'hakui', 'shinkaga', 'kanadu' ),
			'main'  => array( 'main', 'TOTO ネオレストRS3（トイレ）' ),
			'labels' => array( '_ymkrf_lbl_colors' => '便器カラー' ),
			'sets' => array( '_ymkrf_colors' => array(
				array( 'color-white',     'ホワイト' ),
				array( 'color-ivory',     'パステルアイボリー' ),
				array( 'color-pink',      'パステルピンク' ),
				array( 'color-whitegray', 'ホワイトグレー' ),
			) ),
			'speclist' => array(
				array( '快適機能', "オート開閉\n温風乾燥\nやわらかライト\nリモコン便座・便ふた開閉\nオート便器洗浄（大・小）\nオートパワー脱臭\n脱臭\nリモコン便器洗浄（大・小）\n水面下げる\n着座センサー\nリモコン\n個人設定" ),
				array( '洗浄機能', "おしり洗浄\nやわらか洗浄\nビデ洗浄\nムーブ洗浄\nマッサージ洗浄\n水勢調節\n洗浄位置調節" ),
				array( '省エネ機能', "タイマー節電\nスーパーおまかせ節電\nおまかせ節電" ),
				array( '清潔機能', "便器きれい\nお掃除ミスト\nノズルきれい\nセルフクリーニング\nプレミスト\nクリーン便座（継ぎ目なし）\nクリーンノズル\nクリーンケース\n抗菌\nお掃除リフト\n便ふた着脱\n便器そうじ\nノズルそうじ" ),
			),
			'feats' => array(
				array( '便器とノズルを自動で除菌。', '「きれい除菌水」トイレ！', '便器きれい！',
				       '使用前に「きれい除菌水」ミストを自動で吹きかけ、汚れをつきにくくします。', 'point-bowl' ),
				array( '', '', 'ノズルきれい！', '「きれい除菌水」でノズルを自動洗浄。キレイが長持ちします。', 'point-nozzle' ),
				array( '', '', '環境にやさしい！',
				       '薬品や薬剤を使わず、水道水から電気分解で作られる除菌成分。環境にやさしいのが特長です。', 'point-eco' ),
				array( 'エコロジーでエコノミー。', '「3.8L洗浄で超節水！」', '1回あたりの洗浄水量を大幅に節水',
				       "ネオレストは1回あたりの洗浄水量に対し、大幅な節水を実現しました。\nRS（床排水）：大3.8L・小3.0L／回　RS（壁排水）：大4.8L・小3.4L／回", 'point-water',
				       '※試算条件（2019年12月現在）：家族4人／水道料金265円（税込）/㎥・消費税率10％で試算' ),
				array( '心地よく使えるオート機能。', '「オート開閉」', '人に合わせて、ふたが自動で開閉。',
				       '人の動きを検知して、便ふたが自動で開閉します。閉め忘れがないので節電効果も。', 'point-autoopen' ),
			),
			'opts' => array(
				array( 'opt-towelring', 'タオルリング（YT51R）', '3200', 'ブラケット：抗菌樹脂製／リング：ステンレス製。', '' ),
				array( 'opt-handrail', '木製手すり I型（YHB603）', '13500', '立ち上がり時に便利な手すりです。', '' ),
				array( 'opt-paper', '紙巻器（YH51R）', '3200', '1連タイプ。', '' ),
				array( 'opt-as1', 'AS1タイプ便器に変更', '20200', '瞬間暖房、室内暖房、においきれい機能搭載。', '' ),
			),
		),

		/* ===== プレミアム ネオレストRS3【手洗いカウンター付】（TOTO） ===== */
		array(
			'slug'   => 'neorest-rs3-counter', 'prefix' => 'rs3c', 'title' => 'ネオレストRS3【手洗いカウンター付】',
			'meta'   => array(
				'_ymkrf_catch' => '便器とノズルを自動で除菌。キレイ除菌水トイレ！',
				'_ymkrf_grade' => 'プレミアム', '_ymkrf_name' => 'ネオレストRS3', '_ymkrf_sub' => '【手洗いカウンター付】',
				'_ymkrf_work' => '53000', '_ymkrf_item' => '526800',
				'_ymkrf_days' => '', '_ymkrf_daystext' => '半日',
				'_ymkrf_pt1' => '便座きれい', '_ymkrf_pt2' => 'ノズルきれい', '_ymkrf_pt3' => '環境にやさしい',
				'_ymkrf_caution' => '※写真はイメージです。',
				'_ymkrf_note_colors' => '※手洗いカラーは便器カラーと同色になります',
			),
			'total' => 579800, 'maker' => 'toto',
			'shops' => array( 'nonoichi', 'komathu', 'hakui' ),
			'main'  => array( 'main', 'TOTO ネオレストRS3 手洗いカウンター付（トイレ）' ),
			'labels' => array(
				'_ymkrf_lbl_colors' => '便器カラー',
				'_ymkrf_lbl_tops'   => 'カウンターカラー',
				'_ymkrf_lbl_sinks'  => '手洗いキャビネットカラー',
			),
			'sets' => array(
				'_ymkrf_colors' => array(
					array( 'color-white', 'ホワイト' ),
					array( 'color-ivory', 'パステルアイボリー' ),
				),
				'_ymkrf_tops' => array(
					array( 'counter-white',     'ホワイト' ),
					array( 'counter-lightwood', 'ライトウッドN' ),
					array( 'counter-darkbrown', 'ダルブラウン' ),
				),
				'_ymkrf_sinks' => array(
					array( 'cab-white',     'ホワイト' ),
					array( 'cab-lightwood', 'ライトウッドN' ),
					array( 'cab-darkbrown', 'ダルブラウン' ),
				),
			),
			'speclist' => array(
				array( '快適機能', "オート開閉\n温風乾燥\nやわらかライト\nオート便器洗浄（大・小）\nオートパワー脱臭\n脱臭\nリモコン便座（大・小）\n水面下げる\n着座センサー\nリモコン\n個人設定" ),
				array( '洗浄機能', "おしり洗浄\nやわらか洗浄\nビデ洗浄\nムーブ洗浄\nマッサージ洗浄\n水勢調節\n洗浄位置調節" ),
				array( '省エネ機能', "タイマー節電\nスーパーおまかせ節電\nおまかせ節電" ),
				array( '清潔機能', "便器きれい\nお掃除ミスト\nノズルきれい\nセルフクリーニング\nプレミスト\nクリーン便座（継ぎ目なし）\nクリーンノズル\nクリーンケース\n抗菌\nお掃除リフト\n便ふた着脱\n便器そうじ\nノズルそうじ" ),
			),
			'feats' => array(
				array( '便器とノズルを自動で除菌。', '「きれい除菌水」トイレ！', '便器きれい！／ノズルきれい！',
				       "使用前に「きれい除菌水」ミストを自動で吹きかけ、汚れをつきにくくします。\n「きれい除菌水」でノズルを自動洗浄。キレイが長持ちします。", 'point-jokin' ),
				array( '', '', '環境にやさしい！',
				       '薬品や薬剤を使わず、水道水から電気分解で作られる除菌成分。環境にやさしいのが特長です。', 'point-eco' ),
				array( 'エコロジーでエコノミー。', '「3.8L洗浄で超節水！」', '1回あたりの洗浄水量を大幅に節水',
				       'ネオレストは1回あたりの洗浄水量に対し、大幅な節水を実現しました。', 'point-water' ),
				array( '心地よく使えるオート機能。', '「オート開閉」', '人に合わせて、ふたが自動で開閉。',
				       '人の動きを検知して便ふたが自動で開閉します。閉め忘れがないので節電効果も。', 'point-autoopen' ),
			),
			'opts' => array(
				array( 'opt-vessel', 'ベッセル角型手洗器 自動水栓', '66400', '人気のベッセルタイプの手洗い器。', '' ),
				array( 'opt-handrail', '木製手すり I型', '14700', '立ち上がり時に便利な手すりです。', '' ),
				array( 'opt-autofaucet', '手洗い自動水栓', '34800', '手をかざすだけで、水が出ます。', '※写真はイメージです' ),
				array( 'opt-as2', 'AS2タイプ便器に変更', '68600', '瞬間暖房、室内暖房、においきれい機能搭載。', '' ),
			),
		),

	);

	foreach ( $toilet_products as $tp2 ) {

		if ( get_page_by_path( $tp2['slug'], OBJECT, 'ymkrf_product' ) ) continue;

		$pid = wp_insert_post( array(
			'post_type'   => 'ymkrf_product',
			'post_status' => 'publish',
			'post_title'  => $tp2['title'],
			'post_name'   => $tp2['slug'],
		) );
		if ( ! $pid || is_wp_error( $pid ) ) continue;

		$m0 = $missing;
		$pf = $tp2['prefix'];

		$timg = function ( $key, $alt = '' ) use ( $img, $pf ) {
			return $key ? $img( $pf . '-' . $key . '.jpg', $alt ) : '';
		};

		foreach ( $tp2['meta'] as $k => $v )   update_post_meta( $pid, $k, $v );
		foreach ( $tp2['labels'] as $k => $v ) update_post_meta( $pid, $k, $v );
		update_post_meta( $pid, '_ymkrf_total', $tp2['total'] );

		$main = $timg( $tp2['main'][0], $tp2['main'][1] );
		if ( $main ) set_post_thumbnail( $pid, $main );

		/* --- カラーの上に出す写真（あれば） --- */
		if ( ! empty( $tp2['images'] ) ) {
			$rows = array();
			foreach ( $tp2['images'] as $r ) $rows[] = array( 'img' => $timg( $r[0], $r[1] ), 'alt' => $r[1] );
			update_post_meta( $pid, '_ymkrf_images', $rows );
		}

		/* --- カラーバリエーション --- */
		foreach ( $tp2['sets'] as $key => $list ) {
			$rows = array();
			foreach ( $list as $r ) $rows[] = array( 'img' => $timg( $r[0], $r[1] ), 'name' => $r[1] );
			update_post_meta( $pid, $key, $rows );
		}

		/* --- 標準仕様（文字だけの一覧）--- */
		$rows = array();
		foreach ( $tp2['speclist'] as $r ) $rows[] = array( 'ttl' => $r[0], 'body' => $r[1] );
		update_post_meta( $pid, '_ymkrf_speclist', $rows );

		/* --- おすすめポイント ---
		   5番目＝写真1、6番目＝注記、7番目＝写真2、8番目＝白い枠（どれも省略できます）
		   8番目に '1' を入れると、その図版だけ白い下じきと細い枠が付きます。
		   グラフや説明図（もともと白地のもの）に使ってください。 */
		$rows = array();
		foreach ( $tp2['feats'] as $r ) {
			$rows[] = array(
				'gsub' => $r[0], 'gttl' => $r[1], 'ttl' => $r[2], 'text' => $r[3],
				'note' => isset( $r[5] ) ? $r[5] : '',
				'img'  => $timg( $r[4], $r[2] ),
				'img2' => isset( $r[6] ) ? $timg( $r[6], $r[2] ) : '',
				'frame'=> isset( $r[7] ) ? $r[7] : '',
			);
		}
		update_post_meta( $pid, '_ymkrf_features', $rows );

		/* --- おすすめオプション --- */
		$rows = array();
		foreach ( $tp2['opts'] as $r ) {
			$rows[] = array(
				'img'   => $timg( $r[0], $r[1] ),
				'name'  => $r[1],
				'text'  => $r[3],
				'price' => $r[2],
				'note'  => $r[4],
			);
		}
		update_post_meta( $pid, '_ymkrf_options', $rows );

		/* --- ヤマキシ標準工事内容（トイレ・3項目）--- */
		$rows = array();
		foreach ( $toilet_works as $r ) $rows[] = array( 'name' => $r[0], 'text' => $r[1] );
		update_post_meta( $pid, '_ymkrf_works', $rows );

		wp_set_object_terms( $pid, 'toilet', 'ymkrf_product_cat' );
		wp_set_object_terms( $pid, $tp2['maker'], 'ymkrf_maker' );
		if ( $tp2['shops'] ) wp_set_object_terms( $pid, $tp2['shops'], 'ymkrf_shop' );

		update_post_meta( $pid, '_ymkrf_img_missing', $missing - $m0 );
		$log[] = '商品「' . $tp2['title'] . '」を登録しました → ' . get_permalink( $pid );
	}


	/* ------------------------------------------------------------
	   3-p2. すでに登録ずみの商品の「おすすめポイント」を入れ直します。

	         写真まわりの白い枠をやめたとき、GGA3の
	         「ノズルきれい」と「従来の1/3の水で洗浄」だけは
	         説明図なので枠を残す、という指定を反映するためです。

	   ★ここに商品のスラッグを足して、その商品の
	     _ymkrf_features_ver を上げれば、いつでも入れ直せます。
	   ------------------------------------------------------------ */
	$feat_fix = array( 'gga3' => '2' );

	foreach ( $toilet_products as $tp3 ) {

		if ( ! isset( $feat_fix[ $tp3['slug'] ] ) ) continue;

		$p3 = get_page_by_path( $tp3['slug'], OBJECT, 'ymkrf_product' );
		if ( ! $p3 ) continue;
		if ( get_post_meta( $p3->ID, '_ymkrf_features_ver', true ) === $feat_fix[ $tp3['slug'] ] ) continue;

		$pf3   = $tp3['prefix'];
		$timg3 = function ( $key, $alt = '' ) use ( $img, $pf3 ) {
			return $key ? $img( $pf3 . '-' . $key . '.jpg', $alt ) : '';
		};

		$rows = array();
		foreach ( $tp3['feats'] as $r ) {
			$rows[] = array(
				'gsub' => $r[0], 'gttl' => $r[1], 'ttl' => $r[2], 'text' => $r[3],
				'note' => isset( $r[5] ) ? $r[5] : '',
				'img'  => $timg3( $r[4], $r[2] ),
				'img2' => isset( $r[6] ) ? $timg3( $r[6], $r[2] ) : '',
				'frame'=> isset( $r[7] ) ? $r[7] : '',
			);
		}
		update_post_meta( $p3->ID, '_ymkrf_features', $rows );
		update_post_meta( $p3->ID, '_ymkrf_features_ver', $feat_fix[ $tp3['slug'] ] );
		$log[] = get_the_title( $p3->ID ) . 'のおすすめポイントを入れ直しました';
	}


	/* ------------------------------------------------------------
	   3-q. 洗面化粧台の商品を登録します。

	        トイレと同じつくりですが、標準仕様が「写真つき」なので
	        specs（_ymkrf_specs）に対応した別のループにしています。

	   ★商品を足すときは、下の $lav_products に1つ配列を足すだけです。
	     写真は assets/img/products/<スラッグ>/ に
	     <接頭辞>-main.jpg のように置いてください。
	   ------------------------------------------------------------ */

	/* 洗面化粧台の「ヤマキシ標準工事内容」（3項目）は全機種共通です */
	$lav_works = array(
		array( '既存洗面化粧台解体撤去工事', '古い洗面化粧台の取り外しと撤去にかかる工事です。' ),
		array( '水道工事',                   '給水・排水の工事です。' ),
		array( '洗面設置工事',               '新しい洗面化粧台の取り付け工事です。' ),
	);

	$lav_products = array(

		/* ===== Jグレード V1（LIXIL） ===== */
		array(
			'slug' => 'v1', 'prefix' => 'v1', 'title' => 'V1',
			'meta' => array(
				'_ymkrf_catch' => '使い勝手を追求したベーシックな洗面化粧台。',
				'_ymkrf_grade' => 'Jグレード', '_ymkrf_name' => 'V1',
				'_ymkrf_size'  => '間口75cm',
				'_ymkrf_work'  => '24200', '_ymkrf_item' => '50600',
				'_ymkrf_days'  => '', '_ymkrf_daystext' => '最短当日',
				'_ymkrf_pt1'   => '使いやすい', '_ymkrf_pt2' => '省エネ設計', '_ymkrf_pt3' => '収納抜群',
				'_ymkrf_caution' => '※写真はイメージです。',
				'_ymkrf_note_colors' => 'none',
				'_ymkrf_note_tops'   => 'none',
			),
			'total' => 74800, 'maker' => 'lixil',
			'shops' => array( 'nonoichi', 'komathu', 'hakui', 'shinkaga', 'kawakita', 'kanadu', 'kahahothu', 'asahi' ),
			'main'  => array( 'main', 'LIXIL V1 洗面化粧台 間口75cm' ),
			/* 1つめの枠をボウル、2つめの枠を扉にしています */
			'labels' => array(
				'_ymkrf_lbl_colors' => 'ボウルカラー',
				'_ymkrf_lbl_tops'   => '扉カラー',
			),
			'sets' => array(
				'_ymkrf_colors' => array(
					array( 'color-bowl-white', 'ホワイト' ),
				),
				'_ymkrf_tops' => array(
					array( 'door-white',    'ホワイト' ),
					array( 'door-criepale', 'クリエペール' ),
				),
			),
			'specs' => array(
				array( 'spec-bowl',    '広々洗面器',                 '素材：樹脂／ボウル容量15リットル。右奥の排水口で作業スペース広々' ),
				array( 'spec-mirror',  '１面鏡ミラーキャビネット',   '40W型電球型LED×2灯（消費電力8.8W）／コンセント1個／くもり止めヒーターなし' ),
				array( 'spec-faucet',  'シングルレバー洗髪シャワー水栓', 'ホース収納式／リフトアップ付／整流吐水切替付／エコハンドル' ),
				array( 'spec-cabinet', '両開き扉タイプ',             '仕切りのない広い収納空間' ),
			),
			'feats' => array(
				array( '排水口と一体で汚れが溜まりにくい。', '「スキマなし排水口」', '一体成型',
				       '洗面器と一体成型になった新構造。だから、汚れが溜まりにくくカンタンにお掃除できます。', 'point-drain' ),
				array( '', '', '上下昇降式排水栓',
				       '回すだけで開閉できる排水栓。ヘアキャッチャーに溜まったゴミが直接見えず、すっきり。', 'point-plug' ),
				array( '', '', 'ヘアキャッチャー',
				       'ななめ形状のヘアキャッチャーが、ゴミをキャッチする部分と水を通す部分を分け、スムーズに通水します。', 'point-haircatcher' ),
				array( '省エネ設計', '「エコハンドル」', '無意識に使っていたお湯を節約。',
				       'よく使う正面の位置で「水」を出す省エネ設計。お湯を無意識に使うことが無いため、ムダな給湯エネルギーを使いません。',
				       'point-ecohandle', '', '', '1' ),
				array( '収納スペース充実。', '「開き扉キャビネット」', '右奥の排水口で収納スペースたっぷり。',
				       '排水管を右奥にレイアウトすることにより、収納スペースが広がりました。',
				       'point-storage', '', '', '1' ),
			),
			'opts' => array(
				array( 'opt-base600',        'ベースキャビネット 間口600mm',        '0',     '間口を600mmに変更できます。', '※差額なし' ),
				array( 'opt-mirror600',      'ミラーキャビネット 間口600mm',        '550',   '間口を600mmに変更できます。', '' ),
				array( 'opt-mirror1kumori',  '1面鏡 曇り止めコートあり（LED）間口750mm', '2750',  '曇り止めコート装備。', '' ),
				array( 'opt-mirror3kumori',  '3面鏡 曇り止めコートあり（LED）間口750mm', '13640', '鏡の裏面はタップリ収納。曇り止めコート装備。', '' ),
				array( 'opt-interior-full',  '脱衣場内装パック（1坪）',              '80000', '天井・壁クロス張替え＋床クッションフロアー貼り替え（1坪まで）。', '' ),
				array( 'opt-interior-wall',  '脱衣場内装パック（1坪／壁のみ）',      '65000', '壁クロス張替え（1坪まで）。', '' ),
			),
		),

		/* ===== Iグレード D7（LIXIL） ===== */
		array(
			'slug' => 'd7', 'prefix' => 'd7', 'title' => 'D7',
			'meta' => array(
				'_ymkrf_catch' => 'みんなが快適でエコな、スタンダード洗面化粧台。',
				'_ymkrf_grade' => 'Iグレード', '_ymkrf_name' => 'D7',
				'_ymkrf_size'  => '間口75cm',
				'_ymkrf_work'  => '24200', '_ymkrf_item' => '58600',
				'_ymkrf_days'  => '', '_ymkrf_daystext' => '最短当日',
				'_ymkrf_pt1'   => 'お掃除ラクラク', '_ymkrf_pt2' => '省エネ設計', '_ymkrf_pt3' => 'ストレスフリー',
				'_ymkrf_caution' => '※写真はイメージです。',
				'_ymkrf_note_colors' => 'none',
			),
			'total' => 82800, 'maker' => 'lixil',
			'shops' => array( 'komathu', 'hakui', 'shinkaga', 'kawakita', 'asahi' ),
			'main'  => array( 'main', 'LIXIL D7 洗面化粧台 間口75cm' ),
			'labels' => array( '_ymkrf_lbl_colors' => '扉・洗面器カラー' ),
			'sets' => array(
				'_ymkrf_colors' => array(
					array( 'color-white',      'ホワイト' ),
					array( 'color-lightbeige', 'ライトベージュ' ),
					array( 'color-criemocha',  'クリエモカ' ),
					array( 'color-clearveil',  'クリアベール' ),
				),
			),
			'specs' => array(
				array( 'spec-mirror',  '１面鏡ミラーキャビネット', '40W型電球型LED×2灯（消費電力8.8W）／コンセント1個／くもり止めコートあり' ),
				array( 'spec-bowl',    '広々陶器製大型洗面器',     '素材：陶器／色：ホワイト／ボウル容量15リットル' ),
				array( 'spec-cabinet', '両開き扉タイプ',           '背の高いものやかさばるものをたっぷり収納' ),
				array( 'spec-faucet',  'シングルレバー洗髪シャワー水栓', 'ホース収納式／リフトアップ付／整流吐水切替付／エコハンドル' ),
			),
			'feats' => array(
				array( '日々のお掃除がラクラク、キレイ。', '「ラクとれヘアキャッチャー」', '髪の毛がからみにくいなめらか形状',
				       "髪の毛が絡まず、するっととれます。凹凸が少なくブラシでラクにお掃除できるので、簡単なお手入れでいつでもキレイを保てます。",
				       'point-haircatch', '', 'point-haircatch2' ),
				array( '', '', 'プッシュワンウェイ排水栓', '栓の開閉は押すだけ。', 'point-push' ),
				array( '省エネ設計', '「エコハンドル」', '無意識に使っていたお湯を節約。',
				       'よく使う正面の位置で「水」を出す省エネ設計。お湯を無意識に使うことが無いため、ムダな給湯エネルギーを使いません。',
				       'point-ecohandle', '', '', '1' ),
				array( 'ストレスフリー、電力フリー', '「くもり止めコート」', '消費電力ゼロでつかえる。',
				       '電気を使わず表面コーティングでくもりを抑えるので、待ち時間が無く消し忘れもありません。', 'point-kumori', '', '', '1' ),
			),
			'opts' => array(
				array( 'opt-base600',       'ベースキャビネット 間口600mm',            '-4711',  'ベースキャビネットの間口を600mmに変更できます。', '' ),
				array( 'opt-basedraw',      'ベースキャビネット 引出タイプ 間口750mm', '4815',   'よく使う小物と、背の高いものやストック品を分けて収納できます。', '' ),
				array( 'opt-mirror3',       '3面鏡 曇り止めコートあり（LED）間口750mm', '10230',  '鏡の裏面はタップリ収納。曇り止めコート装備。', '' ),
				array( 'opt-brush',         '歯ブラシ立て',                             '1540',   'ミラーキャビネットの収納トレイに納まります。', '' ),
				array( 'opt-interior-full', '脱衣場内装パック（1坪）',                  '80000',  '天井・壁クロス張替え＋床クッションフロアー貼り替え（1坪まで）。', '' ),
				array( 'opt-interior-wall', '脱衣場内装パック（1坪／壁のみ）',          '65000',  '壁クロス張替え（1坪まで）。', '' ),
			),
		),

		/* ===== Hグレード BGA（クリナップ） ===== */
		array(
			'slug' => 'bga', 'prefix' => 'bga', 'title' => 'BGA',
			'meta' => array(
				'_ymkrf_catch' => 'シンプルなデザインと機能性が魅力。',
				'_ymkrf_grade' => 'Hグレード', '_ymkrf_name' => 'BGA',
				'_ymkrf_size'  => '間口75cm',
				'_ymkrf_work'  => '24200', '_ymkrf_item' => '85600',
				'_ymkrf_days'  => '', '_ymkrf_daystext' => '最短当日',
				'_ymkrf_pt1'   => 'スタイリッシュ', '_ymkrf_pt2' => '洗髪しやすい', '_ymkrf_pt3' => '節湯',
				'_ymkrf_caution' => '※写真はイメージです。',
				'_ymkrf_note_colors' => 'none', '_ymkrf_note_tops' => 'none',
			),
			'total' => 109800, 'maker' => 'cleanup',
			'shops' => array( 'nonoichi', 'komathu', 'hakui', 'shinkaga', 'kawakita', 'kanadu' ),
			'main'  => array( 'main', 'クリナップ BGA 洗面化粧台 間口75cm' ),
			'labels' => array( '_ymkrf_lbl_colors' => 'ボウルカラー', '_ymkrf_lbl_tops' => '扉カラー（ハイグレード）' ),
			'sets' => array(
				'_ymkrf_colors' => array( array( 'color-white', 'ホワイト' ) ),
				'_ymkrf_tops' => array(
					array( 'door-naturalwood', 'ナチュラルウッド' ),
					array( 'door-darkwood',    'ダークウッド' ),
					array( 'door-smoothwhite', 'スムースホワイト' ),
				),
			),
			'specs' => array(
				array( 'spec-mirror',  '3面鏡ミラーキャビネット', 'くもり止めヒーター付き（LEDランプ）' ),
				array( 'spec-bowl',    '人工大理石ボウル',        '容量15L' ),
				array( 'spec-cabinet', '引出しタイプキャビネット', '' ),
				array( 'spec-handle',  'バー取手',                '' ),
				array( 'spec-faucet',  'シャワー付シングルレバー水栓', '' ),
			),
			'feats' => array(
				array( '限られたスペースでも広々使える、', 'コンパクト＆ワイドなデザイン', '奥行き50cmだから、狭い空間でも広々つかえる。',
				       'すれ違いがスムーズで、余裕のある洗面空間になります。', 'point-depth', '', '', '1' ),
				array( '', '', 'コンパクトでも広々使えるスクエアなボール。',
				       "コンパクトながら底面積が広い洗面ボール。バケツも入ります。\nボール前ぶちを薄くしたスタイリッシュなデザインです。",
				       'point-square', '', 'point-square2' ),
				array( '洗髪もしやすいホース式で、さらにエコ。', '「シャワー付水栓（節湯C1対応）」', '洗髪時や、背の高いものの水汲みに便利。',
				       '高さ7cmまでリフトアップするので、便利です。', 'point-lift', '', 'point-lift2' ),
				array( '', '', '節湯水栓でエコ。',
				       'よく使うレバー中央位置で水を優先して吐水。水と湯の境にクリック感を設け、使い分けができます。ムダなガスや電気の使用を防ぎます。',
				       'point-eco', '', '', '1' ),
			),
			'opts' => array(
				array( 'opt-opendoor',      'キャビネット 開きタイプ 間口750mm',      '-4400',  '開き戸タイプキャビネットに変更できます。', '' ),
				array( 'opt-wallwasher',    '洗濯機用ウォールキャビネット',            '14300',  '洗濯機の上部スペースを活かして効率的に収納。', '' ),
				array( 'opt-middle',        '洗濯機用ミドルキャビネット（オープン）',   '13200',  '洗濯機の上部スペースを活かして効率的に収納。', '' ),
				array( 'opt-wall750',       'ウォールキャビネット（W750）',             '14300',  'W750×D330×H400。洗面台と同色の吊戸棚です。', '' ),
				array( 'opt-interior-full', '脱衣場内装パック（1坪）',                  '80000',  '天井・壁クロス張替え＋床クッションフロアー貼り替え（1坪まで）。', '' ),
				array( 'opt-interior-wall', '脱衣場内装パック（1坪／壁のみ）',          '65000',  '壁クロス張替え（1坪まで）。', '' ),
			),
		),

		/* ===== Gグレード ラクトワ（クリナップ） ===== */
		array(
			'slug' => 'rakutowa', 'prefix' => 'rakutowa', 'title' => 'ラクトワ',
			'meta' => array(
				'_ymkrf_catch' => '水ハネ、水垂れを気にすることなく使えます。',
				'_ymkrf_grade' => 'Gグレード', '_ymkrf_name' => 'ラクトワ',
				'_ymkrf_size'  => '間口75cm',
				'_ymkrf_work'  => '24200', '_ymkrf_item' => '105600',
				'_ymkrf_days'  => '', '_ymkrf_daystext' => '最短当日',
				'_ymkrf_pt1'   => 'スタイリッシュ', '_ymkrf_pt2' => 'コンパクト', '_ymkrf_pt3' => '収納ひろびろ',
				'_ymkrf_caution' => '※写真はイメージです。',
				'_ymkrf_note_colors' => 'none', '_ymkrf_note_tops' => 'none',
			),
			'total' => 129800, 'maker' => 'cleanup',
			'shops' => array( 'hakui', 'nonoichi', 'komathu', 'tazuruhama', 'kanadu', 'asahi', 'kahahothu' ),
			'main'  => array( 'main', 'クリナップ ラクトワ 洗面化粧台 間口75cm' ),
			'labels' => array( '_ymkrf_lbl_colors' => 'ボウルカラー', '_ymkrf_lbl_tops' => '扉カラー' ),
			'sets' => array(
				'_ymkrf_colors' => array( array( 'color-white', 'ホワイト' ) ),
				'_ymkrf_tops'   => array( array( 'door-white', 'ホワイト' ) ),
			),
			'handles' => array(
				array( 'handle-silver', 'シルバー' ),
				array( 'handle-black',  'ブラック' ),
			),
			'specs' => array(
				array( 'spec-mirror',  'スタンダードLED3面鏡', '' ),
				array( 'spec-bowl',    '人工大理石ボウル',      '容量16L' ),
				array( 'spec-cabinet', '開きタイプ',            '' ),
				array( 'spec-handle',  'バー取手',              '' ),
				array( 'spec-faucet',  'シャワー付シングルレバー水栓', '' ),
			),
			'feats' => array(
				array( '楽に選べてスタイリッシュ。', '「コンパクトでも広く使えるスクエアボール」', '十分な容量のボウル',
				       'ボール底面は広くスクエアな形状で、十分な容量を確保しています。', 'point-bowl' ),
				array( '', '', 'スタイリッシュな見た目',
				       '両サイドのカウンターは、濡らしたくない化粧品などの一時置きとして使えます。', 'point-bowl2', '※間口75cmの場合は11cmです。' ),
				array( 'お手入れしやすい。', '「排水口・ヘアキャッチャー」', '外して洗える',
				       'フタが簡単に分解できる構造で、お手入れラクラクです。', 'point-drain', '', 'point-drain2' ),
				array( '', '', '段差の少ないフランジ形状', '排水口まわりの段差が少なく、汚れがたまりにくい形状です。', '' ),
				array( '高さのある収納に便利。', '「開き戸タイプ」', '収納スペースが広い',
				       '高さのあるバケツや掃除道具などをたっぷり収納できます。', '' ),
			),
			'opts' => array(
				array( 'opt-w900',          '間口900mm',                          '20000',  'スタンダード3面鏡、開き扉W900幅に変更の場合。', '' ),
				array( 'opt-slide',         '下台オールスライド（W750）',          '21400',  '全開するレールを採用し、奥の収納物もラクに取り出せます。', '' ),
				array( 'opt-slimmirror',    'スリムLED3面鏡（W750）＋アンダーパネル', '38400', 'マグネットアイテムを使用できるアンダーパネル。デザイン性も向上します。', '' ),
				array( 'opt-touchless',     'タッチレスシングルレバー水栓',        '77800',  '吐水は横のセンサーに手をかざすだけ。便利なシャワー付タッチレス水栓です。', '' ),
				array( 'opt-interior-full', '脱衣場内装パック（1坪）',             '80000',  '天井・壁クロス張替え＋床クッションフロアー貼り替え（1坪まで）。', '' ),
				array( 'opt-interior-wall', '脱衣場内装パック（1坪／壁のみ）',     '65000',  '壁クロス張替え（1坪まで）。', '' ),
			),
		),

		/* ===== Fグレード J1（LIXIL） ===== */
		array(
			'slug' => 'j1', 'prefix' => 'j1', 'title' => 'J1',
			'meta' => array(
				'_ymkrf_catch' => 'スクエアなフォルムで洗練された洗面化粧台。',
				'_ymkrf_grade' => 'Fグレード', '_ymkrf_name' => 'J1',
				'_ymkrf_size'  => '間口75cm',
				'_ymkrf_work'  => '24200', '_ymkrf_item' => '125600',
				'_ymkrf_days'  => '', '_ymkrf_daystext' => '最短当日',
				'_ymkrf_pt1'   => '広々ボウル', '_ymkrf_pt2' => 'お掃除カンタン', '_ymkrf_pt3' => 'ストレスフリー',
				'_ymkrf_caution' => '※写真はイメージです。',
				'_ymkrf_note_colors' => 'none',
			),
			'total' => 149800, 'maker' => 'lixil',
			'shops' => array( 'nonoichi', 'komathu', 'hakui' ),
			'main'  => array( 'main', 'LIXIL J1 洗面化粧台 間口75cm' ),
			'labels' => array( '_ymkrf_lbl_colors' => '扉カラー' ),
			'sets' => array(
				'_ymkrf_colors' => array(
					array( 'door-criepale',   'クリエペール' ),
					array( 'door-criedark',   'クリエダーク' ),
					array( 'door-urbanblue',  'アーバンブルー' ),
					array( 'door-criemocha',  'クリエモカ' ),
					array( 'door-lightbeige', 'ライトベージュ' ),
					array( 'door-glosswhite', 'グロスホワイト' ),
				),
			),
			'specs' => array(
				array( 'spec-mirror',  '3面鏡ミラーキャビネット 全収納', 'LED照明（消費電力9.8W）／コンセント2個／くもり止めコートあり（中央鏡）' ),
				array( 'spec-bowl',    '人造大理石ボウル',               'ポリエステル系／ボウル容量11リットル' ),
				array( 'spec-faucet',  'シングルレバー洗髪シャワー水栓', 'ホース収納式／リフトアップ付／整流吐水切替付／エコハンドル' ),
				array( 'spec-cabinet', '引出タイプキャビネット',         '取り出したい物が見つけやすい引出収納' ),
			),
			'feats' => array(
				array( 'お掃除ラクラク、アイデアいっぱい。', '「洗面器一体カウンター」', '小物を置けるドライスペース',
				       '水栓取付面を一段低くすることで、サイドをドライスペースとして活用できます。', 'point-dry' ),
				array( '', '', 'やわらかな手のひらカーブ',
				       '手になじむやわらかなカーブ。より安心でラクに使えます。', 'point-curve' ),
				array( '', '', 'ボウルを広くする右奥の排水口',
				       '見た目もスッキリ、広く使えます。', 'point-drainpos' ),
				array( '排水口のお掃除簡単', '「新てまなし排水口」', '排水口の中までスポンジでサッとお掃除できます',
				       '排水口の中まで手を入れずに、スポンジでサッとお掃除できます。', 'point-drain', '', '', '1' ),
				array( 'ストレスフリー、電力フリー', '「くもり止めコート」', '消費電力ゼロでつかえる。',
				       '電気を使わず表面コーティングでくもりを抑えるので、待ち時間が無く消し忘れもありません。', 'point-kumori', '', '', '1' ),
			),
			'opts' => array(
				array( 'opt-base900',       'ベースキャビネット 間口900mm',        '7720',  'ベースキャビネットの間口を900mmに変更できます。', '' ),
				array( 'opt-mirror900',     'ミラーキャビネット 間口900mm',        '15290', 'ミラーキャビネットの間口を900mmに変更できます。', '' ),
				array( 'opt-slimled',       '3面鏡 スリムLED 間口750mm',           '6820',  'すっきりしたデザインの薄型LED照明タイプ。', '' ),
				array( 'opt-smartpocket',   '3面鏡 スマートポケット付LED 間口750mm', '13530', '', '' ),
				array( 'opt-interior-full', '脱衣場内装パック（1坪）',              '80000', '天井・壁クロス張替え＋床クッションフロアー貼り替え（1坪まで）。', '' ),
				array( 'opt-interior-wall', '脱衣場内装パック（1坪／壁のみ）',      '65000', '壁クロス張替え（1坪まで）。', '' ),
			),
		),

		/* ===== Eグレード リジャスト（タカラスタンダード） ===== */
		array(
			'slug' => 'rejust', 'prefix' => 'rejust', 'title' => 'リジャスト',
			'meta' => array(
				'_ymkrf_catch' => 'シンプルなデザインの洗面化粧台。',
				'_ymkrf_grade' => 'Eグレード', '_ymkrf_name' => 'リジャスト',
				'_ymkrf_size'  => '間口75cm',
				'_ymkrf_work'  => '24200', '_ymkrf_item' => '155600',
				'_ymkrf_days'  => '', '_ymkrf_daystext' => '最短当日',
				'_ymkrf_pt1'   => 'たっぷり収納', '_ymkrf_pt2' => 'ぴったりサイズ', '_ymkrf_pt3' => '省エネタイプ',
				'_ymkrf_caution' => '※写真はイメージです。',
				'_ymkrf_note_colors' => 'none',
			),
			'total' => 179800, 'maker' => 'takara',
			'shops' => array( 'nonoichi', 'komathu', 'hakui', 'kawakita' ),
			'main'  => array( 'main', 'タカラスタンダード リジャスト 洗面化粧台 間口75cm' ),
			'labels' => array( '_ymkrf_lbl_colors' => '扉カラー（グループ1）' ),
			'sets' => array(
				'_ymkrf_colors' => array(
					array( 'door-light',       'ライト' ),
					array( 'door-lightwhite',  'ライトホワイト' ),
					array( 'door-mediumbrown', 'ミディアムブラウン' ),
					array( 'door-darkbrown',   'ダークブラウン' ),
					array( 'door-superwhite',  'スーパーホワイト' ),
					array( 'door-white',       'ホワイト' ),
					array( 'door-winered',     'ワインレッド' ),
				),
			),
			'specs' => array(
				array( 'spec-mirror',  'LED照明3面鏡',              'くもり止めコートあり' ),
				array( 'spec-bowl',    '人造大理石 フラットカウンター', '容量12L' ),
				array( 'spec-faucet',  'シングルレバー式シャワー水栓', '' ),
				array( 'spec-cabinet', '引き出し付きキャビネット',     '木製' ),
			),
			'feats' => array(
				array( 'ミラー裏に収納スペースを確保。', '「3面鏡ミラー」', 'たっぷり収納スペース。',
				       '中は内側コンセントや小物ラックを備えた、整頓に便利なたっぷりの収納スペースです。',
				       'point-storage', '', '', '1' ),
				array( '', '', '便利な内部コンセント付き。',
				       '電動歯ブラシなど、棚に収納したままで充電でき、便利です。', 'point-outlet' ),
				array( '1cm刻みでオーダー。', '「ぴったりサイズ洗面台」', 'リフォームに最適。',
				       '特殊なサイズの空間にも壁とのスキマがなく、ぴったり収まります（1cm刻み）。',
				       'point-fit', '※こちらはオプションとなります。価格は別途ご確認ください。', '', '1' ),
				array( '省エネタイプ。', '「くもり止めコーティング」', '電気ヒーターなしでもくもり止め効果を発揮。',
				       'ミラー表面に特殊なコーティングを施し、くもり止め効果を発揮します。', 'point-kumori', '', '', '1' ),
			),
			'opts' => array(
				array( 'opt-highback',      'ハイバック仕様 間口750mm',                 '0',     'シングルレバー式シャワー水栓（クロムメッキ）エコタイプ／3面鏡LEDコートあり。', '※差額なし' ),
				array( 'opt-faceclear',     'フェイスクリアミラー 3面鏡 間口750mm',     '93800', '手元照明付。自然な発色で顔に影が出来ず、メイクアップにおすすめ。', '' ),
				array( 'opt-slide2',        '2段スライドタイプ 間口750mm',              '15900', '奥の収納物もラクに取り出せます。', '' ),
				array( 'opt-whitefaucet',   'シングルレバーシャワー水栓（ホワイト）エコ水栓', '1000', 'レバー中央のクリック感で湯水をお知らせ。お湯のムダ使いをカットできます。', '' ),
				array( 'opt-interior-full', '脱衣場内装パック（1坪）',                  '80000', '天井・壁クロス張替え＋床クッションフロアー貼り替え（1坪まで）。', '' ),
				array( 'opt-interior-wall', '脱衣場内装パック（1坪／壁のみ）',          '65000', '壁クロス張替え（1坪まで）。', '' ),
			),
		),

		/* ===== Cグレード K1（LIXIL） ===== */
		array(
			'slug' => 'k1', 'prefix' => 'k1', 'title' => 'K1',
			'meta' => array(
				'_ymkrf_catch' => 'ひろびろボウル＆くるくる水栓を搭載！',
				'_ymkrf_grade' => 'Cグレード', '_ymkrf_name' => 'K1',
				'_ymkrf_size'  => '間口75cm',
				'_ymkrf_work'  => '24200', '_ymkrf_item' => '155600',
				'_ymkrf_days'  => '', '_ymkrf_daystext' => '最短当日',
				'_ymkrf_pt1'   => 'くるくる水栓', '_ymkrf_pt2' => 'ひろびろボウル', '_ymkrf_pt3' => 'すっきり収納',
				'_ymkrf_caution' => '※写真はイメージです。',
				'_ymkrf_note_colors' => 'none', '_ymkrf_note_tops' => 'none',
			),
			'total' => 179800, 'maker' => 'lixil',
			'shops' => array( 'nonoichi', 'komathu', 'hakui' ),
			'main'  => array( 'main', 'LIXIL K1 洗面化粧台 間口75cm' ),
			'labels' => array( '_ymkrf_lbl_colors' => 'ボウルカラー', '_ymkrf_lbl_tops' => '扉カラー' ),
			'sets' => array(
				'_ymkrf_colors' => array( array( 'color-white', 'ホワイト' ) ),
				'_ymkrf_tops' => array(
					array( 'door-criepale',   'クリエペール' ),
					array( 'door-criemocha',  'クリエモカ' ),
					array( 'door-criedark',   'クリエダーク' ),
					array( 'door-lightbeige', 'ライトベージュ' ),
					array( 'door-urbanblue',  'アーバンブルー' ),
					array( 'door-glosswhite', 'グロスホワイト' ),
				),
			),
			'specs' => array(
				array( 'spec-mirror',  'スマートポケット付3面鏡', 'LED照明（消費電力6W）／コンセント3個／くもり止めコートあり' ),
				array( 'spec-bowl',    '人造大理石ボウル',        'ポリエステル系／ボウル容量20リットル' ),
				array( 'spec-faucet',  'くるくる水栓 シングルレバー水栓', 'ホース収納式／整流吐水切替付／エコハンドル' ),
				array( 'spec-cabinet', '引出タイプキャビネット',   '取り出したい物が見つけやすい引出収納' ),
			),
			'feats' => array(
				array( '使いやすい！', '「くるくる水栓」', '使いたい位置にくるりと回せます',
				       '左右に180度回転するので、使いたい位置に水を向けられます。', 'point-turn', '', '', '1' ),
				array( '', '', 'グースネック形状', 'コップや花瓶の水汲みにも便利な形状です。', 'point-goose' ),
				array( '', '', '吐水切替', '微細シャワーと整流吐水の切替ができます。', 'point-switch' ),
				array( '家事をラクにする', '「ひろびろボウル」', '広くて作業しやすい底面',
				       'バケツの水汲みや衣類洗いに便利です。', 'point-bowl' ),
				array( '', '', 'ウェットパレット／ドライパレット',
				       "水を使うウェットパレットと、小物を置けるドライパレット。使い分けができます。",
				       'point-wet', '', 'point-dry' ),
				array( 'すっきり収納', '「3面鏡スマートポケット」', '散らばりがちな小物も便利に収納',
				       '鏡を見ながら、メイク小物を自然な動作でサッと取り出せるポケットです。ヘアピンやゴムなどの収納に便利！', 'point-pocket' ),
			),
			'opts' => array(
				array( 'opt-upper',         'アッパーキャビネット 間口750mm', '26400', 'W750×D400。', '' ),
				array( 'opt-base900',       'ベースキャビネット 間口900mm',   '5220',  'ベースキャビネットの間口変更。', '' ),
				array( 'opt-mirror900',     'ミラーキャビネット 間口900mm',   '12540', 'ミラーキャビネットの間口変更。', '' ),
				array( 'opt-fullslide',     'フルスライド収納 間口750mm',     '29420', '出し入れしやすく、収納力もアップします。', '' ),
				array( 'opt-interior-full', '脱衣場内装パック（1坪）',        '80000', '天井・壁クロス張替え＋床クッションフロアー貼り替え（1坪まで）。', '' ),
				array( 'opt-interior-wall', '脱衣場内装パック（1坪／壁のみ）', '65000', '壁クロス張替え（1坪まで）。', '' ),
			),
		),

		/* ===== Bグレード ファンシオ（クリナップ） ===== */
		array(
			'slug' => 'fansio', 'prefix' => 'fansio', 'title' => 'ファンシオ',
			'meta' => array(
				'_ymkrf_catch' => '水ハネ、水垂れを気にすることなく使えます。',
				'_ymkrf_grade' => 'Bグレード', '_ymkrf_name' => 'ファンシオ',
				'_ymkrf_size'  => '間口75cm',
				'_ymkrf_work'  => '24200', '_ymkrf_item' => '175600',
				'_ymkrf_days'  => '', '_ymkrf_daystext' => '最短当日',
				'_ymkrf_pt1'   => '流れるボール', '_ymkrf_pt2' => '使いやすい高さ', '_ymkrf_pt3' => '壁出し水栓',
				'_ymkrf_caution' => '※サイドの片面収納はオプションになります。※写真はイメージです。',
				'_ymkrf_note_colors' => 'none', '_ymkrf_note_tops' => 'none',
			),
			'total' => 199800, 'maker' => 'cleanup',
			'shops' => array( 'nonoichi', 'komathu', 'hakui', 'shinkaga', 'kawakita', 'kanadu', 'asahi' ),
			'main'  => array( 'main', 'クリナップ ファンシオ 洗面化粧台 間口75cm' ),
			'labels' => array( '_ymkrf_lbl_colors' => 'ボウルカラー', '_ymkrf_lbl_tops' => '扉カラー' ),
			'sets' => array(
				'_ymkrf_colors' => array( array( 'color-white', 'ホワイト' ) ),
				'_ymkrf_tops' => array(
					array( 'door-smoothwhite', 'スムースホワイト' ),
					array( 'door-milkash',     'ミルクアッシュ' ),
					array( 'door-smoothrose',  'スムースロゼ' ),
					array( 'door-browncherry', 'ブラウンチェリー' ),
					array( 'door-smoothmint',  'スムースミントグリーン' ),
					array( 'door-burntwalnut', 'バーントウォールナット' ),
				),
			),
			'specs' => array(
				array( 'spec-mirror',  '3面鏡ミラーキャビネット', 'LED照明つき' ),
				array( 'spec-bowl',    '人工大理石ボウル 流レールボールLL', 'ボウル容量23リットル' ),
				array( 'spec-cabinet', 'オールスライドタイプ',   '奥まで引き出せて、出し入れがラクな引出収納' ),
				array( 'spec-handle',  'バー取手',               '握りやすいバータイプの取手' ),
				array( 'spec-faucet',  '壁出し水栓',             '水栓まわりに水や汚れがたまりにくい形' ),
			),
			'feats' => array(
				array( 'ボール全体が汚れにくく、広くて使いやすい。', '「流レールボールＬＬ」',
				       '髪の毛や泡を集めて流す、「流レール」',
				       'ボール全体に水が行き届いて、「流レール」に髪の毛や泡を集めて排水口へ導きます。', 'point-nagare' ),
				array( '', '', '洗濯物の「予洗い」も、「深い」からジャブジャブ洗える！',
				       'たっぷりの容量23リットル。深いボールだから、水ハネやこぼれも気になりません。洗濯物の予洗いがしやすいサイズです。', 'point-yoarai' ),
				array( '', '', '気兼ねなく洗顔できるサイズです。',
				       'ゆったりとした大きさなので、朝の洗顔もラクにできます。', 'point-size' ),
				array( '', '', '一時置きに便利なウェットゾーン。',
				       'ボール内に一時置きできるスペースを設けているので、スポンジなどを濡れたまま置けます。サイドが高く設計されているため、水ハネも防止します。',
				       'point-wet', '', 'point-splash' ),
				array( 'カラダへの負担が22％も軽減！', '「使いやすい、高さ85cm」',
				       '毎日、無理な負担を掛けずに使えます。',
				       '洗髪のときの腰への負担がやわらぎます。※高さ85cmは、身長158cm以上の方におすすめです。', 'point-height', '', '', '1' ),
				array( 'お手入れが簡単。', '「壁出し水栓」', '水や汚れがたまりにくい！',
				       'カウンターの上に水栓の根元がないので、水栓まわりのお手入れがラクラクです。水ハネが少なく、節水効果もある微細シャワーを採用しています。',
				       'point-faucet', '', 'point-shower' ),
			),
			'opts' => array(
				array( 'opt-scale',         'オールスライド体重計収納付き（W750）', '25500', 'けこみ部分に体重計が収納できます。取手のないプッシュオープン式。', '' ),
				array( 'opt-side',          '片面収納タイプ（W150）下台＋上台',     '59300', 'オープンタイプの収納棚。よく使うものを簡単に取り出せます。', '' ),
				array( 'opt-mirror',        'スキンケアミラー3面鏡（ダブルLED）',   '33900', 'スキンケア用品や化粧品などをミラー下に収納でき、出し入れもスムーズ。', '' ),
				array( 'opt-wall',          'ウォールキャビネット（W750）',         '23100', 'W750×D270×H450。洗濯機上に取り付けるミドルキャビネット。', '' ),
				array( 'opt-interior-full', '脱衣場内装パック（1坪）',              '80000', '天井・壁クロス張替え＋床クッションフロアー貼り替え（1坪まで）。', '' ),
				array( 'opt-interior-wall', '脱衣場内装パック（1坪／壁のみ）',       '65000', '壁クロス張替え（1坪まで）。', '' ),
			),
		),

		/* ===== Aグレード タッチレス洗面化粧台R1（LIXIL） ===== */
		array(
			'slug' => 'r1', 'prefix' => 'r1', 'title' => 'タッチレス洗面化粧台R1',
			'meta' => array(
				'_ymkrf_catch' => '手を差し出すだけで、水が自動で出る。',
				'_ymkrf_grade' => 'Aグレード', '_ymkrf_name' => 'タッチレス洗面化粧台R1',
				'_ymkrf_size'  => '間口75cm',
				'_ymkrf_work'  => '24200', '_ymkrf_item' => '225600',
				'_ymkrf_days'  => '', '_ymkrf_daystext' => '最短当日',
				'_ymkrf_pt1'   => 'タッチレス', '_ymkrf_pt2' => '奥行ひろびろ', '_ymkrf_pt3' => 'お手入れ簡単',
				'_ymkrf_caution' => '※写真はイメージです。',
				'_ymkrf_note_colors' => 'none', '_ymkrf_note_tops' => 'none',
			),
			'total' => 249800, 'maker' => 'lixil',
			'shops' => array( 'hakui', 'komathu' ),
			'main'  => array( 'main', 'LIXIL タッチレス洗面化粧台R1 間口75cm' ),
			'labels' => array( '_ymkrf_lbl_colors' => '扉カラー' ),
			'sets' => array(
				'_ymkrf_colors' => array(
					array( 'door-lightoak',     'ライトオーク' ),
					array( 'door-cherry',       'チェリー' ),
					array( 'door-chocolatoak',  'ショコラオーク' ),
					array( 'door-glosswhite',   'グロスホワイト（鏡面）' ),
					array( 'door-deepgray',     'ディープグレー（鏡面）' ),
				),
			),
			'specs' => array(
				array( 'spec-mirror',  '3面鏡ミラーキャビネット全収納', 'LED照明（消費電力9.8W）／コンセント2個／くもり止めコートあり（中央鏡）' ),
				array( 'spec-cabinet', '引出タイプキャビネット',       '取り出したいものが見つけやすい、引出し収納がついたキャビネットです。' ),
				array( 'spec-bowl',    '人造大理石ボウル',             'ボウル容量16リットル' ),
				array( 'spec-faucet',  'タッチレス水栓 ナビッシュ',     'ホース収納式／エコハンドル' ),
			),
			'feats' => array(
				array( '手を差し出すだけで、水が自動で出る。', '「タッチレス水栓 ナビッシュ」',
				       '自動で手洗いがラクラク',
				       'センサーが手を感知して、自動で水が出ます。蛇口にさわらないので、水栓まわりも汚れにくくなります。', 'point-touchless' ),
				array( '', '', '自動と手動の切替が不要！',
				       '手を差し出すだけでセンサーが感知する自動吐水。花瓶の水汲みなどの連続吐水には、ハンドル操作で手動吐水に切り替わります。',
				       'point-auto', '', 'point-manual' ),
				array( 'お手入れ簡単！', '「キレイアップカウンター」',
				       '水栓まわりに水がたまりにくく、拭き掃除も簡単。',
				       'カウンターとボウルの継ぎ目がない一体成型カウンターなので、サッとひと拭きでお手入れできます。', 'point-counter' ),
				array( '奥行ひろびろでたっぷり収納', '「ミラーキャビネット」',
				       '家電から小物まで使いやすくしまえる。',
				       'トレイの奥行が125mmとひろく、これまで収納するのが難しかった大きなドライヤーやシェーバーも収納できます。', 'point-mirror' ),
			),
			'opts' => array(
				array( 'opt-push',          'プッシュ水栓に変更',       '-10000', 'ボタンを押すだけの簡単操作です。（お値引きになります）', '' ),
				array( 'opt-w900',          '間口900mmに変更',         '10000',  'ベース及びミラーのキャビネットの間口変更。', '' ),
				array( 'opt-interior-full', '脱衣場内装パック（1坪）',   '80000',  '天井・壁クロス張替え＋床クッションフロアー貼り替え（1坪まで）。', '' ),
				array( 'opt-interior-wall', '脱衣場内装パック（1坪／壁のみ）', '65000', '壁クロス張替え（1坪まで）。', '' ),
			),
		),

		/* ===== Sグレード サクア（TOTO） ===== */
		array(
			'slug' => 'sakua', 'prefix' => 'sakua', 'title' => 'サクア',
			'meta' => array(
				'_ymkrf_catch' => 'アイデア機能充実の洗面化粧台。',
				'_ymkrf_grade' => 'Sグレード', '_ymkrf_name' => 'サクア',
				'_ymkrf_size'  => '間口75cm',
				'_ymkrf_work'  => '24200', '_ymkrf_item' => '265600',
				'_ymkrf_days'  => '', '_ymkrf_daystext' => '最短当日',
				'_ymkrf_pt1'   => 'きれい除菌水', '_ymkrf_pt2' => 'ひろびろボウル', '_ymkrf_pt3' => 'スウィング水栓',
				'_ymkrf_caution' => '※写真はオプションが含まれています。',
				'_ymkrf_note_colors' => 'none', '_ymkrf_note_tops' => 'none',
			),
			'total' => 289800, 'maker' => 'toto',
			'shops' => array( 'nonoichi', 'komathu', 'hakui', 'shinkaga', 'kawakita', 'tazuruhama' ),
			'main'  => array( 'main', 'TOTO サクア 洗面化粧台 間口75cm' ),
			'labels' => array( '_ymkrf_lbl_colors' => 'ボウルカラー', '_ymkrf_lbl_tops' => '扉カラー' ),
			'sets' => array(
				'_ymkrf_colors' => array( array( 'color-white', 'ホワイト' ) ),
				'_ymkrf_tops' => array(
					array( 'door-panachewhite', 'パナシェホワイト（W）' ),
					array( 'door-panachepink',  'パナシェピンク（P）' ),
					array( 'door-panacheaqua',  'パナシェアクア（B）' ),
					array( 'door-mediumwood',   'ロイミディアムウッド（C）' ),
					array( 'door-royalbrown',   'ロイダルブラウン（M）' ),
					array( 'door-milbeige',     'ミルベージュ（J）' ),
				),
			),
			'specs' => array(
				array( 'spec-mirror',  'ワイドスウィング三面鏡', 'LED・エコミラー' ),
				array( 'spec-bowl',    'ひろびろ陶器ボウル',     'ボウル容量21リットル' ),
				array( 'spec-cabinet', '2段引出し収納',          'サイレントレール仕様' ),
				array( 'spec-handle',  'バー取手',               '握りやすいバータイプの取手' ),
				array( 'spec-faucet',  'エアインスウィング水栓', '前後左右に動かせる水栓' ),
			),
			'feats' => array(
				array( 'クリーン技術。', '「きれい除菌水」',
				       '水から作られる除菌成分で、毎日キレイに。',
				       '環境にやさしいTOTOの「きれい除菌水」で、洗面まわりをいつも清潔に使えます。', 'point-clean' ),
				array( '', '', '歯ブラシきれい！',
				       '歯磨き後に水道水で歯ブラシをすすぎ、「きれい除菌水」をふきかけて洗浄・除菌します。', 'point-brush' ),
				array( '', '', '排水口きれい！',
				       '8時間使用しないときは自動でふきかけるので、いつもキレイが続きます。', 'point-drain' ),
				array( '', '', '使わない時間も、汚れを抑えます。',
				       '左が今までの排水口、右が「きれい除菌水」を使ったときのイメージです。',
				       'point-drain-before', '', 'point-drain-after', '1' ),
				array( '手元に引き出せ左右にも開く。', '「スウィング三面鏡」',
				       '鏡裏はすべて収納スペース。',
				       '手元に引き出せ、左右どちらからでも開閉できる便利な両開きの鏡扉。普段よく使う小物がたっぷり入ります。', 'point-mirror' ),
				array( '', '', 'パットレイ',
				       '小さな化粧品などを斜めに置くことができます。中身が見えやすく、取り出しやすくなります。', 'point-pattray' ),
				array( '前後左右に動かせる。', '「エアインスウィング水栓」',
				       'さまざまなシーンにあわせて動かせます。',
				       '水栓の向きを前後左右に動かせるので、洗顔にも、大きな物を洗うときにも使いやすくなります。',
				       'point-swing-lr', '', 'point-swing-fb' ),
				array( '', '', '水滴に空気を含ませて最大20％節水。',
				       'やわらかな肌ざわりのまま、水の使用量をおさえます。', 'point-airin' ),
			),
			'opts' => array(
				array( 'opt-wall',          'ウォールキャビネット（W750×H300）', '29100', '間口を広げることなく、収納力をアップ。', '' ),
				array( 'opt-3way',          '3Wayキャビネット',                  '19100', 'サイズの異なる引き出しに、きっちりしまえる。', '' ),
				array( 'opt-scale',         '体重計収納搭載',                    '22800', 'デッドスペースだったけこみ部分を利用して体重計をスッキリ収納。', '' ),
				array( 'opt-tall',          'トールキャビネット（W150）',        '74700', '出し入れしやすい横位置を収納スペースに。', '' ),
				array( 'opt-interior-full', '脱衣場内装パック（1坪）',           '80000', '天井・壁クロス張替え＋床クッションフロアー貼り替え（1坪まで）。', '' ),
				array( 'opt-interior-wall', '脱衣場内装パック（1坪／壁のみ）',    '65000', '壁クロス張替え（1坪まで）。', '' ),
			),
		),

		/* ===== SSグレード ウツクシーズ（Panasonic） ===== */
		array(
			'slug' => 'utsukushiizu', 'prefix' => 'uts', 'title' => 'ウツクシーズ',
			'meta' => array(
				'_ymkrf_catch' => '水ハネ、水垂れを気にすることなく使えます。',
				'_ymkrf_grade' => 'SSグレード', '_ymkrf_name' => 'ウツクシーズ',
				'_ymkrf_size'  => '間口75cm',
				'_ymkrf_work'  => '24200', '_ymkrf_item' => '335600',
				'_ymkrf_days'  => '', '_ymkrf_daystext' => '最短当日',
				'_ymkrf_pt1'   => '汚れシャット', '_ymkrf_pt2' => 'タッチレス', '_ymkrf_pt3' => '節水・節約',
				'_ymkrf_caution' => '※写真はイメージです。',
				'_ymkrf_note_colors' => 'none', '_ymkrf_note_tops' => 'none',
			),
			'total' => 359800, 'maker' => 'panasonic',
			'shops' => array( 'komathu', 'hakui' ),
			'main'  => array( 'main', 'Panasonic ウツクシーズ 洗面化粧台 間口75cm' ),
			'labels' => array( '_ymkrf_lbl_colors' => 'ボウルカラー', '_ymkrf_lbl_tops' => '扉カラー' ),
			'sets' => array(
				'_ymkrf_colors' => array( array( 'color-white', 'ホワイト' ) ),
				'_ymkrf_tops'   => array( array( 'door-white',  'ホワイト' ) ),
			),
			'specs' => array(
				array( 'spec-faucet',      'タッチレス水栓ツイストシャワー', 'センサーで自動吐水／水の広がりが選べます' ),
				array( 'spec-counter',     'スゴピカカウンター',             '有機ガラス系素材・抗菌加工' ),
				array( 'spec-drain',       'ささっと排水口',                 'フチがなく、サッとふけるかたち' ),
				array( 'spec-haircatcher', 'ささっとキレイ ヘアキャッチャー', 'ゴミが捨てやすい形状' ),
				array( 'spec-mirror',      'スリムLED3面鏡',                 'タッチレス照明つき' ),
			),
			'feats' => array(
				array( '手を近づけるだけで水が出る、止まる。', '「タッチレス水栓ツイストシャワー」',
				       'センサーが感知して、使う分だけ。',
				       'ムダな出しっぱなしがなくなります。4人家族の場合、1日24L、年間なら2Lのペットボトル約4,380本分も節水できます。',
				       'point-touchless', '※4人家族で1人が1日1回「石けんで手洗い・洗顔」する場合の当社試算です。', 'point-save' ),
				array( '', '', '用途に合わせて、水の広がりを選べる。',
				       '水流が交差して集中したところ（吐水口から約8cm）と、広がりのあるところを組み合わせた新形状の水流です。', 'point-flow' ),
				array( '', '', '「エコカチット」でお湯のムダを上手に節約。',
				       'ハンドルが正面のときは水だけが出るので、気づかずにお湯を使ってしまうことがありません。', 'point-ecokachi', '', '', '1' ),
				array( '触れずに、点灯、消灯。', '「タッチレス照明」',
				       '手を差し出すだけで点灯・消灯します。',
				       'ぬれた手でスイッチにさわらなくてよいので、まわりが汚れません。※汚れシャットミラーはセンサーミラー部分のみとなります。', 'point-light' ),
				array( '', '', '手元を明るく照らす',
				       'ミラー照明と連動して、手元照明もオンオフします。', 'point-handlight' ),
				array( '汚れにくく、おそうじラクラク、しかも抗菌', '「スゴピカカウンター」',
				       '汚れの原因“水”をはじく。',
				       'さっとふくだけでキレイになります。（左：当社従来品の陶器／右：スゴピカ素材）', 'point-sugopika', 'パナソニックホームページより', '', '1' ),
				array( '', '', '衛生面に配慮した、抗菌加工のカウンター。',
				       '毎日使う場所だから、清潔に保てる素材を選びました。', 'point-kokin', 'パナソニックホームページより', '', '1' ),
			),
			'opts' => array(
				array( 'opt-step',          'ステップストッカータイプ（W750）',       '23400', '下台が踏み台になっている収納です。', '' ),
				array( 'opt-mirakuru',      '美ルックツインラインLED照明 両開き3面鏡（ミラくるミラー）', '62200', '鏡を手前に引き寄せて、ラクな姿勢で使えます。', '' ),
				array( 'opt-wall',          'ウォールキャビネット（W750）',           '36800', 'W750×D310×H400。洗面台と同色の吊戸棚です。', '' ),
				array( 'opt-tall',          'トールキャビネット（W150）',             '91100', 'オープン棚と、開き戸キャビネットの組み合わせタイプです。', '' ),
				array( 'opt-interior-full', '脱衣場内装パック（1坪）',                '80000', '天井・壁クロス張替え＋床クッションフロアー貼り替え（1坪まで）。', '' ),
				array( 'opt-interior-wall', '脱衣場内装パック（1坪／壁のみ）',         '65000', '壁クロス張替え（1坪まで）。', '' ),
			),
		),

		/* ===== プレミアム オクターブ（TOTO） ===== */
		array(
			'slug' => 'octave', 'prefix' => 'octave', 'title' => 'オクターブ',
			'meta' => array(
				'_ymkrf_catch' => '一連の動作がスムーズに行える、上質な洗面化粧台。',
				'_ymkrf_grade' => 'プレミアム', '_ymkrf_name' => 'オクターブ',
				'_ymkrf_size'  => '間口90cm',
				'_ymkrf_work'  => '24200', '_ymkrf_item' => '405600',
				'_ymkrf_days'  => '', '_ymkrf_daystext' => '最短当日',
				'_ymkrf_pt1'   => 'スムーズ', '_ymkrf_pt2' => '掃除しやすい', '_ymkrf_pt3' => '奥ひろし',
				'_ymkrf_caution' => '※写真のサイド収納はオプションになります。',
				'_ymkrf_note_colors' => 'none', '_ymkrf_note_tops' => 'none',
			),
			'total' => 429800, 'maker' => 'toto',
			'shops' => array( 'nonoichi', 'komathu', 'hakui' ),
			'main'  => array( 'main', 'TOTO オクターブ 洗面化粧台 間口90cm' ),
			'labels' => array( '_ymkrf_lbl_colors' => 'ボウルカラー', '_ymkrf_lbl_tops' => '扉カラー（ミドルグレード）' ),
			'sets' => array(
				'_ymkrf_colors' => array( array( 'color-white', 'ホワイト' ) ),
				'_ymkrf_tops' => array(
					array( 'door-whitewood',    'ホワイトウッド' ),
					array( 'door-lightwood',    'ライトウッド' ),
					array( 'door-mediumwood',   'ミディアムウッド' ),
					array( 'door-royalbrown',   'ロイダルブラウン' ),
					array( 'door-panachewhite', 'パナシェホワイト' ),
					array( 'door-panacheaqua',  'パナシェアクア' ),
					array( 'door-panacheblack', 'パナシェブラック' ),
				),
			),
			'specs' => array(
				array( 'spec-mirror',    'スウィング三面鏡',                 'LED・エコミラー／全面裏収納付き' ),
				array( 'spec-bowl',      'すべり台ボウル',                   '泡や髪がスイスイ流れるかたち' ),
				array( 'spec-faucet',    'タッチレスお掃除ラクラク水栓',      'ちょい置きカウンター付き' ),
				array( 'spec-jokinsui',  '自動キレイ除菌水',                 '使用後に自動で排水口へふきかけます' ),
				array( 'spec-cabinet',   '3Wayキャビネット',                 'サイズの異なる引き出しにきっちりしまえます' ),
				array( 'spec-drain',     'お掃除ラクラク排水口',             'らくポイヘアキャッチャー付き' ),
			),
			'feats' => array(
				array( '一連の動作がスムーズに行える', '「タッチレスお掃除ラクラク水栓」',
				       'ちょい置きカウンター',
				       '手洗い・洗顔のとき、メガネや指輪の一時置きに便利。スマートフォンを置いて、身支度しながら天気予報や予定のチェックにもぴったりです。', 'point-choioki' ),
				array( '', '', '自動水栓',
				       'センサーが手を感知して、自動で水が使えます。泡や汚れが付いた手でレバーを触る必要がなく、レバーに手が届きにくい小さなお子様でも、かんたんに使えます。',
				       'point-auto', '', 'point-child' ),
				array( '', '', 'キレイ除菌水',
				       '水栓使用後、自動で排水口にふきかけて汚れを抑制します。使用後の歯ブラシにふきかけておくことで菌を抑制することもできます。',
				       'point-jokin', '※「きれい除菌水」は、水に含まれる塩化物イオンを電気分解して作られる除菌成分（次亜塩素酸）を含む水です。薬品や洗剤は使いません。', 'point-brush' ),
				array( '泡や髪がスイスイ流れる', '「すべり台ボウル」',
				       'ひろびろ設計',
				       'ゆったり心地よく使え、洗濯もしやすいひろびろ設計のボウル。厚手のセーターもしっかり洗えて、一時置きエリアと水ためエリアに分かれて使いやすい！', 'point-hirobiro', '', '', '1' ),
				array( '', '', 'ハイバックガード',
				       '背面が立ち上がっているので、水ハネしにくく汚れにくい。掃除もしやすいかたちです。', 'point-highback' ),
				array( '大きさそのまま 収納量が大幅アップ', '「奥ひろし」',
				       '排水管の形をシンプルにして奥へ配置',
				       'これまで使えなかったデッドスペースも、収納に利用できます。', 'point-okuhiroshi', '', '', '1' ),
			),
			'opts' => array(
				array( 'opt-h850',          '化粧台高さ H850',            '2800',  '身長に合わせて、使いやすい高さに変えられます。', '' ),
				array( 'opt-wall',          'ウォールキャビネット（W900）', '38000', '間口を広げることなく収納力をアップ！', '' ),
				array( 'opt-tall150',       'サイドトール収納（W150）',    '78000', '洗面台と同色のトール収納です。', '' ),
				array( 'opt-tall300',       'サイドトール収納（W300）',    '90000', 'オープン棚と、開き戸キャビネットの組み合わせタイプです。', '' ),
				array( 'opt-interior-full', '脱衣場内装パック（1坪）',      '80000', '天井・壁クロス張替え＋床クッションフロアー貼り替え（1坪まで）。', '' ),
				array( 'opt-interior-wall', '脱衣場内装パック（1坪／壁のみ）', '65000', '壁クロス張替え（1坪まで）。', '' ),
			),
		),

		/* ===== WOODONE（ウッドワン） ===== */
		array(
			'slug' => 'woodone', 'prefix' => 'woodone', 'title' => 'WOODONE（ウッドワン）',
			'meta' => array(
				'_ymkrf_catch' => '無垢の木のやさしさをサニタリー空間に。',
				'_ymkrf_grade' => 'プレミアム', '_ymkrf_name' => 'WOODONE（ウッドワン）',
				'_ymkrf_size'  => '',
				'_ymkrf_work'  => '', '_ymkrf_item' => '657800',
				'_ymkrf_days'  => '', '_ymkrf_daystext' => '最短当日',
				'_ymkrf_pt1'   => '無垢の木', '_ymkrf_pt2' => '凛とした佇まい', '_ymkrf_pt3' => '飾らない美しさ',
				'_ymkrf_caution' => '※657,800円〜（税込）。組み合わせにより価格が変わります。くわしくはご相談ください。※写真はイメージです。',
				'_ymkrf_note_colors' => 'none', '_ymkrf_note_tops' => 'none', '_ymkrf_note_sinks' => 'none',
			),
			'total' => 657800, 'maker' => 'woodone',
			'shops' => array( 'nonoichi' ),
			'main'  => array( 'main', 'WOODONE（ウッドワン）無垢の木の洗面台' ),
			'labels' => array(
				'_ymkrf_lbl_colors' => 'ボウルカラー',
				'_ymkrf_lbl_tops'   => '樹種',
				'_ymkrf_lbl_sinks'  => 'アッパーキャビネットつまみ',
			),
			'sets' => array(
				'_ymkrf_colors' => array( array( 'color-white',  'ホワイト' ) ),
				'_ymkrf_tops'   => array( array( 'wood-walnut',  'ウォールナット（クリア塗装）' ) ),
				'_ymkrf_sinks'  => array( array( 'knob-iron',    'アイアンつまみN型' ) ),
			),
			'specs' => array(
				array( 'spec-counter', '糸面（幕板付）カウンター',       '無垢集成材のカウンター' ),
				array( 'spec-bowl',    'スリムエッジ陶器ボウル',         '金属管Sストラップセット付き／容量6.9L・深さ120mm' ),
				array( 'spec-bracket', 'カウンターブラケット',           'カウンターを支える金具' ),
				array( 'spec-faucet',  'シングルレバー水栓 L254',        '高さのあるスリムなデザイン' ),
				array( 'spec-mirror',  'スリムミラー（小）',             '細枠のシンプルなミラー' ),
				array( 'spec-flap',    'アッパーフラップアップ',         '開いた状態で固定できる扉' ),
			),
			'feats' => array(
				array( '「無垢ならでは」の風合いを洗面台に。', '', '洗練されたミニマム洗面台',
				       'シンプルで洗練された佇まいが美しい無垢集成材カウンター。無垢ならではの味わい深い風合いに触れることで、やさしい気持ちになれます。', 'point-minimal' ),
				array( '', '', '無垢の木ならではの熱伝導率',
				       '木は熱を伝えにくいので、金属のようにヒヤリとしません。多孔性素材である木は、微小空間の中に熱伝導率がゼロに近い空気をたくさん封じ込めているため、ぬくもりのある優しい感触です。', 'point-netsu', '', '', '1' ),
				array( '', '', '節・入り皮',
				       '節は、木の成長過程で枝の付け根が幹に包み込まれてできたもの。入り皮は、傷ついた樹皮を幹のなかに取り込んで治癒しようとした痕跡です。どちらも木がたくましく生きてきた証です。', 'point-fushi', '', '', '1' ),
				array( '', '', '濃淡',
				       '1本の木から採った材でも、一枚一枚、色の濃淡や木目の紋様は異なります。成長する過程や年齢によって、さまざまな表情を楽しめます。', 'point-noutan', '', '', '1' ),
				array( '', '', 'キャラクターマーク',
				       '木の個性は、木肌の模様にも現れます。見る角度によって色が変化して見えるものなど、本物の木ならではの表情です。', 'point-character', '', '', '1' ),
				array( '', '', '水につよいウレタン塗装',
				       '通常の塗装工程よりも中塗工程を増やし、さらに水に強い仕上げにしています。木端面・木口もしっかり塗装しています。',
				       'point-coat', '', 'point-water', '1' ),
				array( '', '', '調湿作用でいつも快適',
				       '湿度が高いときは水分を吸収し、乾燥しているときは水分を放出して、常に60％前後の湿度に調節してくれます。結露の少ない、快適で健康的な空間になります。', 'point-choushitsu', '', '', '1' ),
				array( '水仕舞いのよいボウル', '「スリムエッジ陶器ボウル」', '洗練されたデザイン',
				       '直線と曲線、それぞれの持つ美しさを見事に調和した、洗練されたボウルです。', 'point-slimedge', '排水目皿固定式／容量6.9L／深さ120mm　※水を溜めることはできません。' ),
				array( '出し入れ簡単', '「フラップアップ扉」', '開け閉めスムーズ！',
				       '扉の両側にステー金具を使用し、開閉時の負荷を軽減。開いた状態で固定できるため、出し入れしやすい扉です。', 'point-flapup' ),
			),
			'opts' => array(
				array( 'opt-shelf',         '無垢の木収納 棚＋サンカクブラケット', '12100', 'ほしい場所に手軽に収納棚を設置できます。（W750×奥行250mm×厚み18mm）', '' ),
				array( 'opt-cabinet',       'キャビネット（W450）2段引出タイプ',   '73480', 'サニタリールームの使い勝手を向上させる便利なキャビネット。', '' ),
				array( 'opt-oval',          'オーバル洗面ボウル',                  '0',     'ポップアップ式排水で、水を留めることが可能です。（追加費用なし）', '' ),
				array( 'opt-mirror3',       '3面鏡（W750）',                       '19800', '鏡裏面は収納スペース。洗面まわりをすっきりさせたい方に。', '' ),
				array( 'opt-interior-full', '脱衣場内装パック（1坪）',             '80000', '天井・壁クロス張替え＋床クッションフロアー貼り替え（1坪まで）。', '' ),
				array( 'opt-interior-wall', '脱衣場内装パック（1坪／壁のみ）',      '65000', '壁クロス張替え（1坪まで）。', '' ),
			),
		),

	);

	/* ★1回のページ読み込みで登録するのは「1機種だけ」にしています。
	     写真が1機種で20枚以上あり、まとめて処理すると
	     PHPの制限時間を超えて途中で止まってしまうためです。
	     残りは、次にページを開いたときに1機種ずつ登録されます。 */
	$lav_made = 0;

	foreach ( $lav_products as $lp ) {

		if ( get_page_by_path( $lp['slug'], OBJECT, 'ymkrf_product' ) ) continue;
		if ( $lav_made >= 1 ) break;

		$pid = wp_insert_post( array(
			'post_type'   => 'ymkrf_product',
			'post_status' => 'publish',
			'post_title'  => $lp['title'],
			'post_name'   => $lp['slug'],
		) );
		if ( ! $pid || is_wp_error( $pid ) ) continue;

		$m0  = $missing;
		$pfl = $lp['prefix'];

		$limg = function ( $key, $alt = '' ) use ( $img, $pfl ) {
			return $key ? $img( $pfl . '-' . $key . '.jpg', $alt ) : '';
		};

		foreach ( $lp['meta'] as $k => $v )   update_post_meta( $pid, $k, $v );
		foreach ( $lp['labels'] as $k => $v ) update_post_meta( $pid, $k, $v );
		update_post_meta( $pid, '_ymkrf_total', $lp['total'] );

		$main = $limg( $lp['main'][0], $lp['main'][1] );
		if ( $main ) set_post_thumbnail( $pid, $main );

		/* --- カラーバリエーション --- */
		foreach ( $lp['sets'] as $key => $list ) {
			$rows = array();
			foreach ( $list as $r ) $rows[] = array( 'img' => $limg( $r[0], $r[1] ), 'name' => $r[1] );
			update_post_meta( $pid, $key, $rows );
		}

		/* --- 取っ手（あれば）--- */
		if ( ! empty( $lp['handles'] ) ) {
			$rows = array();
			foreach ( $lp['handles'] as $r ) {
				$rows[] = array( 'img' => $limg( $r[0], $r[1] ), 'name' => $r[1], 'code' => isset( $r[2] ) ? $r[2] : '' );
			}
			update_post_meta( $pid, '_ymkrf_handles', $rows );
		}

		/* --- 標準仕様（写真つき）--- */
		$rows = array();
		foreach ( $lp['specs'] as $r ) {
			$rows[] = array( 'img' => $limg( $r[0], $r[1] ), 'name' => $r[1], 'model' => $r[2] );
		}
		update_post_meta( $pid, '_ymkrf_specs', $rows );

		/* --- おすすめポイント（8番目に '1' で白い枠）--- */
		$rows = array();
		foreach ( $lp['feats'] as $r ) {
			$rows[] = array(
				'gsub' => $r[0], 'gttl' => $r[1], 'ttl' => $r[2], 'text' => $r[3],
				'note' => isset( $r[5] ) ? $r[5] : '',
				'img'  => $limg( $r[4], $r[2] ),
				'img2' => isset( $r[6] ) ? $limg( $r[6], $r[2] ) : '',
				'frame'=> isset( $r[7] ) ? $r[7] : '',
			);
		}
		update_post_meta( $pid, '_ymkrf_features', $rows );

		/* --- おすすめオプション --- */
		$rows = array();
		foreach ( $lp['opts'] as $r ) {
			$rows[] = array(
				'img'   => $limg( $r[0], $r[1] ),
				'name'  => $r[1],
				'text'  => $r[3],
				'price' => $r[2],
				'note'  => $r[4],
			);
		}
		update_post_meta( $pid, '_ymkrf_options', $rows );

		/* --- ヤマキシ標準工事内容（洗面化粧台・3項目）--- */
		$rows = array();
		foreach ( $lav_works as $r ) $rows[] = array( 'name' => $r[0], 'text' => $r[1] );
		update_post_meta( $pid, '_ymkrf_works', $rows );

		wp_set_object_terms( $pid, 'lavatory', 'ymkrf_product_cat' );
		wp_set_object_terms( $pid, $lp['maker'], 'ymkrf_maker' );
		if ( $lp['shops'] ) wp_set_object_terms( $pid, $lp['shops'], 'ymkrf_shop' );

		update_post_meta( $pid, '_ymkrf_img_missing', $missing - $m0 );
		$log[] = '商品「' . $lp['title'] . '」を登録しました → ' . get_permalink( $pid );
		$lav_made++;
	}

	/* 登録できなかった商品があれば、管理画面のお知らせに出します。
	   （何が入っていないのか、ひと目で分かるようにするためです） */
	$lav_missing = array();
	foreach ( $lav_products as $lpx ) {
		if ( ! get_page_by_path( $lpx['slug'], OBJECT, 'ymkrf_product' ) ) {
			$lav_missing[] = $lpx['title'] . '（' . $lpx['slug'] . '）';
		}
	}
	if ( $lav_missing ) {
		/* ★ここで一度URLの設定を更新します。
		     以前は、いちばん最後（すべて登録し終わったあと）でしか
		     更新していなかったため、途中まで登録した商品のURLが
		     「ページが見つかりません」になっていました。 */
		if ( $lav_made ) flush_rewrite_rules();

		/* まだ残っているときは、ここでいったん終わります。
		   完了の印（ymkrf_setup_done）を付けないので、
		   次にページを開いたときに続きから登録されます。 */
		$log[] = '洗面化粧台の残り：' . implode( '／', $lav_missing )
		       . '　→ ページをもう一度開くと、続きを1機種ずつ登録します';
		update_option( 'ymkrf_setup_log', $log );
		return;
	}
	$log[] = '洗面化粧台は' . count( $lav_products ) . '機種すべて登録ずみです';

	/* 洗面化粧台は、どの機種も工期「最短当日」です。
	   すでに登録ずみの商品にも、あとから入れます。 */
	foreach ( $lav_products as $lp2 ) {
		$p2 = get_page_by_path( $lp2['slug'], OBJECT, 'ymkrf_product' );
		if ( ! $p2 ) continue;
		if ( get_post_meta( $p2->ID, '_ymkrf_daystext', true ) === '最短当日' ) continue;
		update_post_meta( $p2->ID, '_ymkrf_days', '' );
		update_post_meta( $p2->ID, '_ymkrf_daystext', '最短当日' );
		$log[] = get_the_title( $p2->ID ) . 'の工期を「最短当日」にしました';
	}


	/* ------------------------------------------------------------
	   3-r. 給湯器の商品を登録します。

	        いただいたPDF（本番サイトの /products/boiler/ ）の8機種です。
	        給湯器は色や取っ手を選ぶ商品ではないので、
	        カラー・おすすめポイント・オプションは使っていません。
	        かわりに「標準仕様（文字だけの一覧）」に、
	        その機種にある機能／ない機能を並べています。

	   ★商品を足すときは、下の $boi_products に1つ配列を足すだけです。
	     写真は assets/img/products/<スラッグ>/ に
	     <スラッグ>-main.jpg と <スラッグ>-spec-remote.jpg を置いてください。
	   ------------------------------------------------------------ */

	/* 給湯器の「ヤマキシ標準工事内容」は全機種共通です
	   （PDFの【標準工事内容】標準工事費 / 撤去・処分 / 設置工事 / 配管工事） */
	$boi_works = array(
		array( '既存給湯器 解体撤去工事', '古い給湯器の取り外しにかかる工事です。' ),
		array( '撤去・処分',             '取り外した古い給湯器を廃棄処分するための費用です。' ),
		array( '給湯器設置工事',         '新しい給湯器の取り付け工事です。' ),
		array( '配管工事',               '給水・給湯・追い焚きなどの配管の接続工事です。' ),
	);

	/* 「主な機能」に出す言葉です（2026/09/01 ユーザー指示で作りかえました）。

	   もとは本番サイトと同じ9つのバッジを「ある機能／ない機能」の
	   2つの一覧にしていましたが、
	     ・「ない機能」は8機種ともほぼ同じで、選ぶ材料にならない
	     ・いちばん大事な「オートかフルオートか」は、写真の上の帯に出ている
	   ため、やめました。

	   オートとフルオートの違いは、リンナイの説明のとおりです。
	     オート　　… 湯はり・保温・追いだきまでが自動
	     フルオート… それに加えて、お湯が減ったときの「たし湯」まで自動
	   'full' => true の機種にだけ「自動たし湯」が付きます。

	   'hoshu' => true は「特定保守製品」（石油給湯機）です。
	   機能ではなく法律の区分なので、機能の一覧とは別に出します。 */
	$boi_products = array(

		/* ===== ガス給湯器 オート20号（ノーリツ） ===== */
		array(
			'slug' => 'gt-2070saw', 'title' => 'GT-2070SAW BL',
			'catch' => 'ガス給湯器', 'grade' => 'オート 20号', 'size' => '壁掛設置',
			'total' => 178000, 'order' => 10, 'maker' => 'noritz',
			'list'  => '390720',
			'remote'=> array( 'お風呂＋台所リモコン', 'RC-B001', '29920' ),
			'full'  => false, 'hoshu' => false,
			'alt'   => 'ノーリツ ガス給湯器 GT-2070SAW BL 本体',
		),

		/* ===== ガス給湯器 オート20号（リンナイ） ===== */
		array(
			'slug' => 'ruf-205saw', 'title' => 'RUF-205SAW(A)',
			'catch' => 'ガス給湯器', 'grade' => 'オート 20号', 'size' => '壁掛設置',
			'total' => 178000, 'order' => 20, 'maker' => 'rinnai',
			'list'  => '384340',
			'remote'=> array( 'お風呂＋台所リモコン', 'MBC-155V', '36740' ),
			'full'  => false, 'hoshu' => false,
			'alt'   => 'リンナイ ガス給湯器 RUF-205SAW(A) 本体',
		),

		/* ===== エコジョーズ オート20号（ノーリツ） ===== */
		array(
			'slug' => 'srt-c2071saw', 'title' => 'SRT-C2071SAW BL',
			'catch' => 'エコジョーズ（高効率ガス給湯器）', 'grade' => 'オート 20号', 'size' => '壁掛設置',
			'total' => 188000, 'order' => 30, 'maker' => 'noritz',
			'list'  => '408320',
			'remote'=> array( 'お風呂＋台所リモコン', 'RC-B001', '29920' ),
			'full'  => false, 'hoshu' => false,
			'alt'   => 'ノーリツ エコジョーズ SRT-C2071SAW BL 本体',
		),

		/* ===== エコジョーズ フルオート20号（リンナイ） ===== */
		array(
			'slug' => 'ruf-e2006aw', 'title' => 'RUF-E2006AW',
			'catch' => 'エコジョーズ（高効率ガス給湯器）', 'grade' => 'フルオート 20号', 'size' => '壁掛設置',
			'total' => 238000, 'order' => 40, 'maker' => 'rinnai',
			'list'  => '466840',
			'remote'=> array( 'お風呂＋台所リモコン', 'MBC-155V', '36740' ),
			'full'  => true, 'hoshu' => false,
			'alt'   => 'リンナイ エコジョーズ RUF-E2006AW 本体',
		),

		/* ===== 石油給湯器 オート4万キロ・塗装鋼板（ノーリツ） ===== */
		array(
			'slug' => 'otq-4706say', 'title' => 'OTQ-4706SAY',
			'catch' => '石油給湯器', 'sub' => '塗装鋼板', 'grade' => 'オート 4万キロ', 'size' => '屋外設置・水道直圧',
			'total' => 278000, 'order' => 50, 'maker' => 'noritz',
			'list'  => '483780',
			'remote'=> array( 'お風呂＋台所リモコン', 'RC-J101 (T)', '42680' ),
			'full'  => false, 'hoshu' => true,
			'alt'   => 'ノーリツ 石油給湯器 OTQ-4706SAY 本体',
		),

		/* ===== 石油給湯器 オート4万キロ・ステンレス（ノーリツ） ===== */
		array(
			'slug' => 'otq-4706says', 'title' => 'OTQ-4706SAYS',
			'catch' => '石油給湯器', 'sub' => 'ステンレス', 'grade' => 'オート 4万キロ', 'size' => '屋外設置・水道直圧',
			'total' => 298000, 'order' => 60, 'maker' => 'noritz',
			'list'  => '503580',
			'remote'=> array( 'お風呂＋台所リモコン', 'RC-J101 (T)', '42680' ),
			'full'  => false, 'hoshu' => true,
			'alt'   => 'ノーリツ 石油給湯器 OTQ-4706SAYS 本体',
		),

		/* ===== エコフィール オート4万キロ（ノーリツ） ===== */
		array(
			'slug' => 'otq-c4706say', 'title' => 'OTQ-C4706SAY BL',
			'catch' => 'エコフィール（高効率石油給湯器）', 'sub' => '塗装鋼板', 'grade' => 'オート 4万キロ', 'size' => '屋外設置・水道直圧',
			'total' => 298000, 'order' => 70, 'maker' => 'noritz',
			'list'  => '548680',
			'remote'=> array( 'お風呂＋台所リモコン', 'RC-J101E (T)', '42680' ),
			'full'  => false, 'hoshu' => true,
			'alt'   => 'ノーリツ エコフィール OTQ-C4706SAY BL 本体',
		),

		/* ===== エコフィール フルオート4万キロ（ノーリツ） ===== */
		array(
			'slug' => 'otq-c4706ay', 'title' => 'OTQ-C4706AY BL',
			'catch' => 'エコフィール（高効率石油給湯器）', 'sub' => '塗装鋼板', 'grade' => 'フルオート 4万キロ', 'size' => '屋外設置・水道直圧',
			'total' => 348000, 'order' => 80, 'maker' => 'noritz',
			'list'  => '586080',
			'remote'=> array( 'お風呂＋台所リモコン', 'RC-J101E (T)', '42680' ),
			'full'  => true, 'hoshu' => true,
			'alt'   => 'ノーリツ エコフィール OTQ-C4706AY BL 本体',
		),

	);

	/* 給湯器は写真が2枚だけなので、1回のページ読み込みで
	   まとめて登録しても止まりません。 */
	$boi_made = 0;

	foreach ( $boi_products as $bp ) {

		if ( get_page_by_path( $bp['slug'], OBJECT, 'ymkrf_product' ) ) continue;

		$pid = wp_insert_post( array(
			'post_type'   => 'ymkrf_product',
			'post_status' => 'publish',
			'post_title'  => $bp['title'],
			'post_name'   => $bp['slug'],
		) );
		if ( ! $pid || is_wp_error( $pid ) ) continue;

		$m0  = $missing;
		$sg  = $bp['slug'];

		$bimg = function ( $key, $alt = '' ) use ( $img, $sg ) {
			return $img( $sg . '-' . $key . '.jpg', $alt );
		};

		update_post_meta( $pid, '_ymkrf_catch',    $bp['catch'] );
		update_post_meta( $pid, '_ymkrf_grade',    $bp['grade'] );
		update_post_meta( $pid, '_ymkrf_order',    $bp['order'] );
		update_post_meta( $pid, '_ymkrf_name',     $bp['title'] );
		update_post_meta( $pid, '_ymkrf_size',     $bp['size'] );
		update_post_meta( $pid, '_ymkrf_sub',      isset( $bp['sub'] ) ? $bp['sub'] : '' );
		update_post_meta( $pid, '_ymkrf_days',     '' );
		update_post_meta( $pid, '_ymkrf_daystext', '半日' );
		update_post_meta( $pid, '_ymkrf_pt1',      '在庫あり' );
		update_post_meta( $pid, '_ymkrf_pt2',      '工期は半日' );
		update_post_meta( $pid, '_ymkrf_pt3',      '工事費・リモコン込' );
		update_post_meta( $pid, '_ymkrf_caution',
			'※写真はイメージです。メーカー希望小売価格 ' . number_format( (int) $bp['list'] )
			. '円（税込・リモコン込）の品です。' );

		/* ★給湯器は「工事費・リモコン込」の一本価格で、
		     商品代と工事費の内訳をいただいていません。
		     込み価格は自動で「標準工事費 ＋ 商品代」から計算される決まりなので、
		     込み価格をそのまま「商品代」の欄に入れ、「標準工事費」は空にしています。
		     （こうしておくと、管理画面で開いて保存しても価格が消えません） */
		update_post_meta( $pid, '_ymkrf_work',  '' );
		update_post_meta( $pid, '_ymkrf_item',  $bp['total'] );
		update_post_meta( $pid, '_ymkrf_total', $bp['total'] );

		$main = $bimg( 'main', $bp['alt'] );
		if ( $main ) set_post_thumbnail( $pid, $main );

		/* --- 標準仕様（写真つき）＝ 付いてくるリモコン --- */
		update_post_meta( $pid, '_ymkrf_specs', array(
			array(
				'img'   => $bimg( 'spec-remote', $bp['remote'][0] ),
				'name'  => $bp['remote'][0],
				'model' => $bp['remote'][1] . '／メーカー希望小売価格 '
				         . number_format( (int) $bp['remote'][2] ) . '円（税込）',
			),
		) );

		/* --- 標準仕様（文字だけの一覧）＝ 主な機能 --- */
		update_post_meta( $pid, '_ymkrf_speclist', ymkrf_boiler_speclist( $bp ) );

		/* --- ヤマキシ標準工事内容（給湯器・4項目）--- */
		$rows = array();
		foreach ( $boi_works as $r ) $rows[] = array( 'name' => $r[0], 'text' => $r[1] );
		update_post_meta( $pid, '_ymkrf_works', $rows );

		wp_set_object_terms( $pid, 'boiler', 'ymkrf_product_cat' );
		wp_set_object_terms( $pid, $bp['maker'], 'ymkrf_maker' );

		update_post_meta( $pid, '_ymkrf_img_missing', $missing - $m0 );
		$log[] = '商品「' . $bp['title'] . '」を登録しました → ' . get_permalink( $pid );
		$boi_made++;
	}

	if ( $boi_made ) {
		flush_rewrite_rules();
		$log[] = '給湯器を' . $boi_made . '機種登録しました';
	}

	/* チラシに合わせた直し（1回だけ）。2026/09/01
	     ・材質（塗装鋼板／ステンレス）を、商品名の横に出します
	     ・キャッチコピーから材質のカッコ書きを外します
	       （材質は商品名の横に出るようになったため） */
	if ( get_option( 'ymkrf_boiler_sub_ver' ) !== '1' ) {
		$boi_sub = array(
			'otq-c4706say' => array( '塗装鋼板', 'エコフィール（高効率石油給湯器）' ),
			'otq-c4706ay'  => array( '塗装鋼板', 'エコフィール（高効率石油給湯器）' ),
			'otq-4706say'  => array( '塗装鋼板', '石油給湯器' ),
			'otq-4706says' => array( 'ステンレス', '石油給湯器' ),
		);
		$done_sub = 0;
		foreach ( $boi_sub as $bs => $bv ) {
			$pb = get_page_by_path( $bs, OBJECT, 'ymkrf_product' );
			if ( ! $pb ) continue;
			update_post_meta( $pb->ID, '_ymkrf_sub',   $bv[0] );
			update_post_meta( $pb->ID, '_ymkrf_catch', $bv[1] );
			$done_sub++;
		}
		if ( $done_sub ) $log[] = '給湯器' . $done_sub . '機種に材質（塗装鋼板／ステンレス）を入れました';
		update_option( 'ymkrf_boiler_sub_ver', '1' );
	}

	/* メーカーの「説明」を入れます（1回だけ）。
	   給湯器のページで、メーカーごとの見出しの下に出ます。
	   文章を直したいときは、ダッシュボードの
	   「商品 → メーカー」でそのメーカーの「説明」を書きかえてください。
	   （すでに何か書いてあるメーカーには、いっさい触りません） */
	if ( get_option( 'ymkrf_maker_desc_ver' ) !== '1' ) {
		$maker_desc = array(
			'noritz' => '石油（灯油）とガス、どちらの給湯器もつくっているメーカーです。'
			          . '灯油をお使いのお宅には、排気の熱を再利用して灯油の使用量をおさえる'
			          . '高効率タイプ「エコフィール」もあります。',
			'rinnai' => 'ガス機器を専門につくっているメーカーです。'
			          . 'ガス給湯器には、排気の熱を再利用してガスの使用量をおさえる'
			          . '高効率タイプ「エコジョーズ」もあります。',
		);
		foreach ( $maker_desc as $mslug => $mtext ) {
			$mt2 = get_term_by( 'slug', $mslug, 'ymkrf_maker' );
			if ( ! $mt2 || is_wp_error( $mt2 ) ) continue;
			if ( trim( (string) $mt2->description ) !== '' ) continue;   /* 書いてあるものは触りません */
			wp_update_term( $mt2->term_id, 'ymkrf_maker', array( 'description' => $mtext ) );
			$log[] = 'メーカー「' . $mt2->name . '」の説明を入れました';
		}
		update_option( 'ymkrf_maker_desc_ver', '1' );
	}

	/* 基本仕様（設置方式・寸法・質量・給湯圧力・給湯能力）を入れます。2026/09/01
	     チラシと、ノーリツ公式サイトで確認できたものだけ入れています。
	     分からないもの（ガス4機種の寸法・質量など）は空のままです。
	     空の項目は、商品ページに出ません。 */
	if ( get_option( 'ymkrf_boiler_basic_ver' ) !== '1' ) {

		$boi_basic = array(
			/* スラッグ => 設置方式, 給湯圧力, 給湯能力, 単位, 寸法, 質量 */
			'gt-2070saw'   => array( '壁掛設置', '', '20', '号', '', '' ),
			'ruf-205saw'   => array( '壁掛設置', '', '20', '号', '', '' ),
			'srt-c2071saw' => array( '壁掛設置', '', '20', '号', '', '' ),
			'ruf-e2006aw'  => array( '壁掛設置', '', '20', '号', '', '' ),
			'otq-4706say'  => array( '屋外据置設置', '水道直圧式', '4', '万kcal/h', '高さ770×幅540×奥行250', '' ),
			'otq-4706says' => array( '屋外据置設置', '水道直圧式', '4', '万kcal/h', '高さ770×幅540×奥行250', '' ),
			'otq-c4706say' => array( '屋外据置設置', '水道直圧式', '4', '万kcal/h', '', '' ),
			'otq-c4706ay'  => array( '屋外据置設置', '水道直圧式', '4', '万kcal/h', '', '' ),
		);

		$done_basic = 0;
		foreach ( $boi_basic as $bs2 => $bv2 ) {
			$pb2 = get_page_by_path( $bs2, OBJECT, 'ymkrf_product' );
			if ( ! $pb2 ) continue;
			update_post_meta( $pb2->ID, '_ymkrf_setup',      $bv2[0] );
			update_post_meta( $pb2->ID, '_ymkrf_pressure',   $bv2[1] );
			update_post_meta( $pb2->ID, '_ymkrf_power',      $bv2[2] );
			update_post_meta( $pb2->ID, '_ymkrf_power_unit', $bv2[3] );
			update_post_meta( $pb2->ID, '_ymkrf_dim',        $bv2[4] );
			update_post_meta( $pb2->ID, '_ymkrf_weight',     $bv2[5] );
			$done_basic++;
		}
		if ( $done_basic ) $log[] = '給湯器' . $done_basic . '機種に基本仕様を入れました';
		update_option( 'ymkrf_boiler_basic_ver', '1' );
	}

	/* 「設置方法」と「ふろ機能」に入れ直します。2026/09/01
	     ・設置方式（別の欄）はやめて、「型（サイズ）」を「設置方法」として使います
	     ・グレードは「ふろ機能」（オート／フルオート）にします
	       号数・キロ数は「給湯能力」の欄に移したので、ここには入れません */
	if ( get_option( 'ymkrf_boiler_size_ver' ) !== '2' ) {

		$boi_size = array(
			/* スラッグ => 設置方法, ふろ機能 */
			'gt-2070saw'   => array( '壁掛設置',     'オート' ),
			'ruf-205saw'   => array( '壁掛設置',     'オート' ),
			'srt-c2071saw' => array( '壁掛設置',     'オート' ),
			'ruf-e2006aw'  => array( '壁掛設置',     'フルオート' ),
			'otq-4706say'  => array( '屋外据置設置', 'オート' ),
			'otq-4706says' => array( '屋外据置設置', 'オート' ),
			'otq-c4706say' => array( '屋外据置設置', 'オート' ),
			'otq-c4706ay'  => array( '屋外据置設置', 'フルオート' ),
		);

		$done_size = 0;
		foreach ( $boi_size as $bs3 => $bv3 ) {
			$pb3 = get_page_by_path( $bs3, OBJECT, 'ymkrf_product' );
			if ( ! $pb3 ) continue;
			update_post_meta( $pb3->ID, '_ymkrf_size',  $bv3[0] );
			update_post_meta( $pb3->ID, '_ymkrf_grade', $bv3[1] );
			delete_post_meta( $pb3->ID, '_ymkrf_setup' );   /* 使わなくなった欄 */
			$done_size++;
		}
		if ( $done_size ) $log[] = '給湯器' . $done_size . '機種の「設置方法」「ふろ機能」を入れ直しました';
		update_option( 'ymkrf_boiler_size_ver', '2' );
	}

	/* 「外装」の欄に入れ直します。2026/09/01
	     塗装鋼板・ステンレスは「商品名の横の言葉」に入れていましたが、
	     専用の「外装」の欄をつくったので、そちらに移します。 */
	if ( get_option( 'ymkrf_boiler_ext_ver' ) !== '1' ) {

		$boi_ext = array(
			'otq-4706say'  => '塗装鋼板',
			'otq-4706says' => 'ステンレス',
			'otq-c4706say' => '塗装鋼板',
			'otq-c4706ay'  => '塗装鋼板',
		);

		$done_ext = 0;
		foreach ( $boi_ext as $bs4 => $bv4 ) {
			$pb4 = get_page_by_path( $bs4, OBJECT, 'ymkrf_product' );
			if ( ! $pb4 ) continue;
			update_post_meta( $pb4->ID, '_ymkrf_exterior', $bv4 );
			update_post_meta( $pb4->ID, '_ymkrf_sub', '' );   /* 商品名の横には出しません */
			$done_ext++;
		}
		if ( $done_ext ) $log[] = '給湯器' . $done_ext . '機種の外装を入れ直しました';
		update_option( 'ymkrf_boiler_ext_ver', '1' );
	}

	/* 「主な機能」の入れ直し（1回だけ）。2026/09/01
	     もとは「この機種にある機能／ない機能」の2つの一覧でしたが、
	     「ない機能」は8機種ともほぼ同じで選ぶ材料にならないため、やめました。 */
	if ( get_option( 'ymkrf_boiler_spec_ver' ) !== '2' ) {
		$done_spec = 0;
		foreach ( $boi_products as $bp5 ) {
			$p5 = get_page_by_path( $bp5['slug'], OBJECT, 'ymkrf_product' );
			if ( ! $p5 ) continue;
			update_post_meta( $p5->ID, '_ymkrf_speclist', ymkrf_boiler_speclist( $bp5 ) );
			$done_spec++;
		}
		if ( $done_spec ) $log[] = '給湯器' . $done_spec . '機種の「主な機能」を入れ直しました';
		update_option( 'ymkrf_boiler_spec_ver', '2' );
	}

	/* 本体写真の入れ直し（1回だけ）。
	   一覧のカードは写真の上下を切って表示するため、
	   はじめの写真は給湯器の頭と足が切れていました。
	   正方形の中に小さめに置き直したものに差し替えます。 */
	if ( get_option( 'ymkrf_boiler_main_ver' ) !== '2' ) {
		$done_main = 0;
		foreach ( $boi_products as $bp4 ) {
			$p4 = get_page_by_path( $bp4['slug'], OBJECT, 'ymkrf_product' );
			if ( ! $p4 ) continue;

			/* 前に取り込んだ写真を消してから、同じ名前で入れ直します */
			$olds = get_posts( array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_ymkrf_import',
				'meta_value'     => $bp4['slug'] . '-main.jpg',
			) );
			foreach ( (array) $olds as $oid ) wp_delete_attachment( $oid, true );

			$new = $img( $bp4['slug'] . '-main.jpg', $bp4['alt'], true );
			if ( $new ) { set_post_thumbnail( $p4->ID, $new ); $done_main++; }
		}
		if ( $done_main ) $log[] = '給湯器' . $done_main . '機種の本体写真を入れ直しました';
		update_option( 'ymkrf_boiler_main_ver', '2' );
	}

	/* すでに登録ずみの給湯器の価格を入れ直します。
	   （はじめ「商品代」を空にしていたため、価格が出ませんでした） */
	foreach ( $boi_products as $bp2 ) {
		$bpx = get_page_by_path( $bp2['slug'], OBJECT, 'ymkrf_product' );
		if ( ! $bpx ) continue;
		if ( (int) get_post_meta( $bpx->ID, '_ymkrf_item', true ) === (int) $bp2['total'] ) continue;
		update_post_meta( $bpx->ID, '_ymkrf_work',  '' );
		update_post_meta( $bpx->ID, '_ymkrf_item',  $bp2['total'] );
		update_post_meta( $bpx->ID, '_ymkrf_total', $bp2['total'] );
		$log[] = $bp2['title'] . 'の価格を入れ直しました';
	}


	/* ------------------------------------------------------------
	   3-w. エコキュート（ecocute）　2026/09/01

	        いただいたPDF（本番サイトの /products/ecocute/ ）の7機種です。
	        給湯器と同じく、色や取っ手を選ぶ商品ではないので、
	        カラー・おすすめポイント・オプションは使っていません。

	   ★商品を足すときは、下の $eco_products に1つ配列を足すだけです。
	     写真は assets/img/products/<スラッグ>/<スラッグ>-main.jpg に置いて、
	     上の $dirs にもそのフォルダを足してください。
	   ------------------------------------------------------------ */

	/* エコキュートの「ヤマキシ標準工事内容」は全機種共通です
	   （PDFの【ヤマキシのエコキュート標準工事に含まれる工事】4項目） */
	$eco_works = array(
		array( '既存給湯器 解体撤去工事', '古い電気温水器・エコキュートの取り外しと処分にかかる工事です。' ),
		array( '水道工事',               '給水・給湯・排水の配管工事です。' ),
		array( '電気工事',               '配線工事です。北陸電力への申請作業もふくみます。' ),
		array( 'エコキュート設置工事',   '新しいエコキュート本体とヒートポンプの取り付け工事です。' ),
	);

	/* 全機種に共通の付属品（PDFの「付属品」欄） */
	$eco_acc = '脚部カバー／ベーシックリモコン';

	/* 'grade' … PDFの「タイプ」欄そのままです（写真の上の帯に出ます）
	   'catch' … 写真の下に赤い文字で出るふだです
	   'hojo'  … 補助金の対象になる機種
	   'kouatsu' … PDFのタイプ欄に「高圧」と書かれている機種。
	               三菱のSシリーズは高圧力型貯湯式です。
	               書かれていない機種は、確認できていないので空のままにします。 */
	$eco_products = array(

		/* ===== 三菱 460L フルオート（数量限定） ===== */
		array(
			'slug' => 'srt-w466', 'title' => 'SRT-W466',
			'maker' => 'mitsubishi', 'tank' => '460', 'people' => '4〜5人向け',
			'grade' => 'フルオート',
			'catch' => '数量限定！早い者勝ち！',
			'note'  => '無くなり次第終了となります。',
			'total' => 398000, 'order' => 10, 'hojo' => false, 'kouatsu' => false,
			'alt'   => '三菱電機 エコキュート SRT-W466 本体とヒートポンプ',
		),

		/* ===== 三菱 370L フルオート・高圧（処分品） ===== */
		array(
			'slug' => 'srt-s376ua', 'title' => 'SRT-S376UA',
			'maker' => 'mitsubishi', 'tank' => '370', 'people' => '3〜4人向け',
			'grade' => 'フルオート・高圧',
			'catch' => '在庫処分！数量限定！',
			'note'  => '無くなり次第終了となります。',
			'total' => 428000, 'order' => 20, 'hojo' => false, 'kouatsu' => true,
			'alt'   => '三菱電機 エコキュート SRT-S376UA 本体とヒートポンプ',
		),

		/* ===== 三菱 460L フルオート・高圧（処分品） ===== */
		array(
			'slug' => 'srt-s466ua', 'title' => 'SRT-S466UA',
			'maker' => 'mitsubishi', 'tank' => '460', 'people' => '4〜5人向け',
			'grade' => 'フルオート・高圧',
			'catch' => '在庫処分！数量限定！',
			'note'  => '無くなり次第終了となります。',
			'total' => 438000, 'order' => 30, 'hojo' => false, 'kouatsu' => true,
			'alt'   => '三菱電機 エコキュート SRT-S466UA 本体とヒートポンプ',
		),

		/* ===== パナソニック 370L フルオート ===== */
		array(
			'slug' => 'he-s37lqs', 'title' => 'HE-S37LQS',
			'maker' => 'panasonic', 'tank' => '370', 'people' => '3〜4人向け',
			'grade' => 'フルオート',
			'catch' => '補助金対象商品！',
			'note'  => '',
			'total' => 468000, 'order' => 40, 'hojo' => true, 'kouatsu' => false,
			'alt'   => 'パナソニック エコキュート HE-S37LQS 本体とヒートポンプ',
		),

		/* ===== パナソニック 460L フルオート ===== */
		array(
			'slug' => 'he-s46lqs', 'title' => 'HE-S46LQS',
			'maker' => 'panasonic', 'tank' => '460', 'people' => '4〜7人向け',
			'grade' => 'フルオート',
			'catch' => '補助金対象商品！',
			'note'  => '',
			'total' => 508000, 'order' => 50, 'hojo' => true, 'kouatsu' => false,
			'alt'   => 'パナソニック エコキュート HE-S46LQS 本体とヒートポンプ',
		),

		/* ===== 三菱 370L フルオート・高圧・高効率 ===== */
		array(
			'slug' => 'srt-s377u', 'title' => 'SRT-S377U',
			'maker' => 'mitsubishi', 'tank' => '370', 'people' => '3〜4人向け',
			'grade' => 'フルオート・高圧・高効率',
			'catch' => '補助金対象商品！',
			'note'  => '',
			'total' => 548000, 'order' => 60, 'hojo' => true, 'kouatsu' => true,
			'alt'   => '三菱電機 エコキュート SRT-S377U 本体とヒートポンプ',
		),

		/* ===== 三菱 460L フルオート・高圧・高効率 ===== */
		array(
			'slug' => 'srt-s467u', 'title' => 'SRT-S467U',
			'maker' => 'mitsubishi', 'tank' => '460', 'people' => '4〜5人向け',
			'grade' => 'フルオート・高圧・高効率',
			'catch' => '補助金対象商品！',
			'note'  => '',
			'total' => 578000, 'order' => 70, 'hojo' => true, 'kouatsu' => true,
			'alt'   => '三菱電機 エコキュート SRT-S467U 本体とヒートポンプ',
		),

	);

	$eco_made = 0;

	foreach ( $eco_products as $ep ) {

		if ( get_page_by_path( $ep['slug'], OBJECT, 'ymkrf_product' ) ) continue;

		$pid = wp_insert_post( array(
			'post_type'   => 'ymkrf_product',
			'post_status' => 'publish',
			'post_title'  => $ep['title'],
			'post_name'   => $ep['slug'],
		) );
		if ( ! $pid || is_wp_error( $pid ) ) continue;

		$m0 = $missing;

		update_post_meta( $pid, '_ymkrf_catch',     $ep['catch'] );
		update_post_meta( $pid, '_ymkrf_grade',     $ep['grade'] );
		update_post_meta( $pid, '_ymkrf_order',     $ep['order'] );
		update_post_meta( $pid, '_ymkrf_name',      $ep['title'] );
		update_post_meta( $pid, '_ymkrf_size',      '' );
		update_post_meta( $pid, '_ymkrf_sub',       '' );
		update_post_meta( $pid, '_ymkrf_tank',      $ep['tank'] );
		update_post_meta( $pid, '_ymkrf_people',    $ep['people'] );
		update_post_meta( $pid, '_ymkrf_accessory', $eco_acc );
		update_post_meta( $pid, '_ymkrf_pressure',  $ep['kouatsu'] ? '高圧力型貯湯式' : '' );
		update_post_meta( $pid, '_ymkrf_days',      '1' );
		update_post_meta( $pid, '_ymkrf_daystext',  '' );
		update_post_meta( $pid, '_ymkrf_pt1',       '工事は1日' );
		update_post_meta( $pid, '_ymkrf_pt2',       '北陸電力への申請もおまかせ' );
		update_post_meta( $pid, '_ymkrf_pt3',       '工事費・リモコン込' );
		update_post_meta( $pid, '_ymkrf_caution',
			'※写真はイメージです。※電気温水器またはエコキュートからのお取り替えの場合の価格です。'
			. ( $ep['note'] ? $ep['note'] : '' ) );

		/* ★給湯器と同じく、工事費込みの一本価格でご案内しています。
		     込み価格は「標準工事費 ＋ 商品代」から計算される決まりなので、
		     込み価格をそのまま「商品代」に入れ、「標準工事費」は空にしています。 */
		update_post_meta( $pid, '_ymkrf_work',  '' );
		update_post_meta( $pid, '_ymkrf_item',  $ep['total'] );
		update_post_meta( $pid, '_ymkrf_total', $ep['total'] );

		$main = $img( $ep['slug'] . '-main.jpg', $ep['alt'] );
		if ( $main ) set_post_thumbnail( $pid, $main );

		update_post_meta( $pid, '_ymkrf_speclist', ymkrf_ecocute_speclist( $ep ) );

		$rows = array();
		foreach ( $eco_works as $r ) $rows[] = array( 'name' => $r[0], 'text' => $r[1] );
		update_post_meta( $pid, '_ymkrf_works', $rows );

		wp_set_object_terms( $pid, 'ecocute', 'ymkrf_product_cat' );
		wp_set_object_terms( $pid, $ep['maker'], 'ymkrf_maker' );

		update_post_meta( $pid, '_ymkrf_img_missing', $missing - $m0 );
		$log[] = '商品「' . $ep['title'] . '」を登録しました → ' . get_permalink( $pid );
		$eco_made++;
	}

	if ( $eco_made ) {
		flush_rewrite_rules();
		$log[] = 'エコキュートを' . $eco_made . '機種登録しました';
	}

	/* すでに登録ずみのエコキュートの価格を入れ直します
	   （管理画面で開いて保存したあとも、価格が消えないようにするため） */
	foreach ( $eco_products as $ep2 ) {
		$epx = get_page_by_path( $ep2['slug'], OBJECT, 'ymkrf_product' );
		if ( ! $epx ) continue;
		if ( (int) get_post_meta( $epx->ID, '_ymkrf_item', true ) === (int) $ep2['total'] ) continue;
		update_post_meta( $epx->ID, '_ymkrf_work',  '' );
		update_post_meta( $epx->ID, '_ymkrf_item',  $ep2['total'] );
		update_post_meta( $epx->ID, '_ymkrf_total', $ep2['total'] );
		$log[] = $ep2['title'] . 'の価格を入れ直しました';
	}

	/* ------------------------------------------------------------
	   3-w2. 在庫確認シートに合わせた追加　2026/09/01

	        Gドライブの「住設機器　在庫確認＆発注確認」の
	        エコキュート在庫表（合計数・2026 国 補助金対象・
	        補助金対応リモコン品番）を見て入れています。

	        ★合計数が4以上の機種だけを対象にしています（ユーザー指示）。
	        ★もともと載っていた7機種いがいは、
	          価格が未確認なので「価格は空・下書き」で登録しています。
	          価格を入れて「公開」に変えると、一覧に出ます。
	   ------------------------------------------------------------ */

	/* すでに載っている機種に、補助金とリモコン品番を入れます（1回だけ）。
	   〇×は在庫確認シートの「2026 国 補助金対象」のとおりです。 */
	if ( get_option( 'ymkrf_eco_hojo_ver' ) !== '1' ) {

		$eco_hojo = array(
			/* スラッグ => 補助金（対象／対象外）, リモコン品番 */
			'he-s37lqs'  => array( '対象',   'HE-TQWLW' ),
			'he-s46lqs'  => array( '対象',   'HE-TQWLW' ),
			'srt-s377u'  => array( '対象',   'RMCB-F7SE' ),
			'srt-s467u'  => array( '対象',   'RMCB-F7SE' ),
			'srt-w466'   => array( '対象外', '' ),
			'srt-s376ua' => array( '対象外', '' ),
			'srt-s466ua' => array( '対象外', '' ),
		);

		$done_hojo = 0;
		foreach ( $eco_hojo as $hs => $hv ) {
			$hp = get_page_by_path( $hs, OBJECT, 'ymkrf_product' );
			if ( ! $hp ) continue;
			update_post_meta( $hp->ID, '_ymkrf_hojo',   $hv[0] );
			update_post_meta( $hp->ID, '_ymkrf_remote', $hv[1] );
			$done_hojo++;
		}
		if ( $done_hojo ) $log[] = 'エコキュート' . $done_hojo . '機種に補助金・リモコン品番を入れました';
		update_option( 'ymkrf_eco_hojo_ver', '1' );
	}

	/* SRT-W466 を下書きにもどします（1回だけ）。2026/09/01 ユーザー指示
	   在庫確認シートの合計数が1台で、「合計数4以上」の条件から外れたためです。
	   消してはいません。在庫がもどったら「公開」に変えれば、また一覧に出ます。 */
	if ( get_option( 'ymkrf_eco_w466_ver' ) !== '1' ) {
		$w466 = get_page_by_path( 'srt-w466', OBJECT, 'ymkrf_product' );
		if ( $w466 && $w466->post_status === 'publish' ) {
			wp_update_post( array( 'ID' => $w466->ID, 'post_status' => 'draft' ) );
			$log[] = 'SRT-W466 を下書きにもどしました（在庫1台のため）';
		}
		update_option( 'ymkrf_eco_w466_ver', '1' );
	}

	/* 赤いふだから「在庫残り○台！」の台数を外します（1回だけ）。2026/09/01 ユーザー指示
	   PDFの「残り2台／残り1台」のままになっていましたが、
	   在庫確認シートでは9台・12台あり、合っていなかったためです。
	   台数は動くので、「在庫処分！数量限定！」という言い方にしています。
	   台数を出したいときは、登録ぺージの「おすすめ表示」に書いてください。 */
	if ( get_option( 'ymkrf_eco_stock_ver' ) !== '2' ) {
		$done_st = 0;
		foreach ( array( 'srt-s376ua', 'srt-s466ua' ) as $ss ) {
			$sp = get_page_by_path( $ss, OBJECT, 'ymkrf_product' );
			if ( ! $sp ) continue;
			/* 2026/09/01 ユーザー指示で「処分アイテム！」から言いかえました */
			update_post_meta( $sp->ID, '_ymkrf_catch', '在庫処分！数量限定！' );
			$done_st++;
		}
		if ( $done_st ) $log[] = 'エコキュート' . $done_st . '機種の赤いふだを「在庫処分！数量限定！」にしました';
		update_option( 'ymkrf_eco_stock_ver', '2' );
	}

	/* 県の補助金（福井県・石川県）は、いちど入れましたが
	   ユーザー指示（2026/09/01）で外しました。
	   商品に残っている欄を消します（1回だけ）。

	   国の補助金（_ymkrf_hojo）は、そのまま使っています。 */
	if ( get_option( 'ymkrf_eco_ken_ver' ) !== '2' ) {
		$eco_ken_slugs = array(
			'srt-w466', 'srt-s376ua', 'srt-s466ua', 'srt-s376u', 'srt-s376',
			'srt-s466', 'srt-n466-2', 'srt-s377u', 'srt-s467u', 'srt-s377',
			'srt-s467', 'he-ns46lqs', 'he-s37lqs', 'he-s46lqs',
		);
		$done_ken = 0;
		foreach ( $eco_ken_slugs as $ns ) {
			$np = get_page_by_path( $ns, OBJECT, 'ymkrf_product' );
			if ( ! $np ) continue;
			if ( get_post_meta( $np->ID, '_ymkrf_hojo_fukui', true ) === ''
				&& get_post_meta( $np->ID, '_ymkrf_hojo_ishikawa', true ) === '' ) continue;
			delete_post_meta( $np->ID, '_ymkrf_hojo_fukui' );
			delete_post_meta( $np->ID, '_ymkrf_hojo_ishikawa' );
			$done_ken++;
		}
		if ( $done_ken ) $log[] = 'エコキュート' . $done_ken . '機種から県の補助金の欄を消しました';
		update_option( 'ymkrf_eco_ken_ver', '2' );
	}

	/* 在庫数と在庫店舗を入れます（1回だけ）。2026/09/01
	   Gドライブの「住設機器　在庫確認＆発注確認」のエコキュート在庫表を写したものです。

	   ★これは社内用です。お客様のページには出ません。
	     ダッシュボードの「商品 → エコキュート」の一覧にだけ出ます。
	   ★シートは毎日変わります。ここは写した時点のものなので、
	     新しくしたいときは、その商品の登録ぺージで直接直してください。
	     （この処理は1回きりなので、直したものが上書きされることはありません） */
	if ( get_option( 'ymkrf_eco_stockdata_ver' ) !== '1' ) {

		$eco_stock_date = '2026/09/01';

		$eco_stock = array(
			/* スラッグ => 合計数, 在庫のある店舗 */
			'srt-w466'   => array( '1',  '野々市1台' ),
			'srt-s376ua' => array( '9',  '小松3台／野々市1台／羽咋5台' ),
			'srt-s466ua' => array( '12', '小松3台／野々市2台／羽咋7台' ),
			'srt-s376u'  => array( '4',  '金津1台／野々市2台／羽咋1台' ),
			'srt-s376'   => array( '6',  '開発1台／羽咋5台' ),
			'srt-s466'   => array( '5',  '開発1台／羽咋4台' ),
			'srt-n466-2' => array( '8',  '小松3台／野々市2台／新加賀1台／羽咋2台' ),
			'srt-s377u'  => array( '46', '金津3台／小松9台／野々市4台／開発5台／田鶴浜2台／朝日3台／川北5台／新加賀1台／羽咋14台' ),
			'srt-s467u'  => array( '40', '金津5台／小松1台／野々市5台／開発5台／田鶴浜3台／朝日5台／川北5台／新加賀2台／羽咋9台' ),
			'srt-s377'   => array( '19', '金津8台／小松2台／野々市5台／新加賀1台／羽咋3台' ),
			'srt-s467'   => array( '20', '金津5台／小松8台／野々市7台／羽咋2台' ),
			'he-ns46lqs' => array( '9',  '金津2台／川北1台／羽咋6台' ),
			'he-s37lqs'  => array( '28', '金津3台／小松4台／野々市5台／開発2台／田鶴浜1台／朝日1台／川北3台／新加賀2台／羽咋7台' ),
			'he-s46lqs'  => array( '39', '金津3台／小松1台／野々市4台／開発1台／田鶴浜4台／朝日3台／川北5台／新加賀2台／羽咋16台' ),
		);

		$done_stk = 0;
		foreach ( $eco_stock as $ks => $kv ) {
			$kp = get_page_by_path( $ks, OBJECT, 'ymkrf_product' );
			if ( ! $kp ) continue;
			update_post_meta( $kp->ID, '_ymkrf_stock',     $kv[0] );
			update_post_meta( $kp->ID, '_ymkrf_stockshop', $kv[1] );
			update_post_meta( $kp->ID, '_ymkrf_stockdate', $eco_stock_date );
			$done_stk++;
		}
		if ( $done_stk ) $log[] = 'エコキュート' . $done_stk . '機種に在庫数・在庫店舗を入れました（' . $eco_stock_date . '現在）';
		update_option( 'ymkrf_eco_stockdata_ver', '1' );
	}

	/* 「主な機能」の見出しを「補助金適用」に入れ直します（1回だけ）。
	   2026/09/01 ユーザー指示で「2026年」を外しました。
	   年をつけると、年が変わるたびに全機種を直すことになるためです。 */
	if ( get_option( 'ymkrf_eco_spec_ver' ) !== '2' ) {

		/* 商品ごとに書いてある「タイプ」と「補助金」から作り直します。
		   下書きの商品もふくめて、エコキュート全部が対象です。 */
		$eco_all = get_posts( array(
			'post_type'      => 'ymkrf_product',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'tax_query'      => array( array(
				'taxonomy' => 'ymkrf_product_cat',
				'field'    => 'slug',
				'terms'    => 'ecocute',
			) ),
		) );

		$done_es = 0;
		foreach ( (array) $eco_all as $eid ) {
			update_post_meta( $eid, '_ymkrf_speclist', ymkrf_ecocute_speclist( array(
				'grade' => get_post_meta( $eid, '_ymkrf_grade', true ),
				'hojo'  => ( get_post_meta( $eid, '_ymkrf_hojo', true ) === '対象' ),
			) ) );
			$done_es++;
		}
		if ( $done_es ) $log[] = 'エコキュート' . $done_es . '機種の「主な機能」を入れ直しました';
		update_option( 'ymkrf_eco_spec_ver', '2' );
	}

	/* 在庫が4台以上あって、まだ載っていなかった機種です。
	   価格は空のまま・下書きで登録します（ユーザー指示 2026/09/01）。

	   ★人数は、すでに載っている同じ大きさの機種に合わせています。
	     ちがっていたら、登録ぺージで直してください。
	   ★写真はまだありません。アイキャッチを入れてください。 */
	$eco_draft = array(

		/* 三菱・高圧（在庫4台） */
		array( 'slug' => 'srt-s376u', 'title' => 'SRT-S376U', 'maker' => 'mitsubishi',
		       'tank' => '370', 'people' => '3〜4人向け', 'grade' => 'フルオート・高圧',
		       'hojo' => '対象', 'remote' => 'RMCB-F6SE-T', 'kouatsu' => true, 'stock' => 4 ),

		/* 三菱（在庫6台） */
		array( 'slug' => 'srt-s376', 'title' => 'SRT-S376', 'maker' => 'mitsubishi',
		       'tank' => '370', 'people' => '3〜4人向け', 'grade' => 'フルオート',
		       'hojo' => '対象', 'remote' => 'RMCB-F6SE-T', 'kouatsu' => false, 'stock' => 6 ),

		/* 三菱（在庫5台） */
		array( 'slug' => 'srt-s466', 'title' => 'SRT-S466', 'maker' => 'mitsubishi',
		       'tank' => '460', 'people' => '4〜5人向け', 'grade' => 'フルオート',
		       'hojo' => '対象', 'remote' => 'RMCB-F6SE-T', 'kouatsu' => false, 'stock' => 5 ),

		/* 三菱・新品番（在庫19台） */
		array( 'slug' => 'srt-s377', 'title' => 'SRT-S377', 'maker' => 'mitsubishi',
		       'tank' => '370', 'people' => '3〜4人向け', 'grade' => 'フルオート',
		       'hojo' => '対象', 'remote' => 'RMCB-F7SE', 'kouatsu' => false, 'stock' => 19 ),

		/* 三菱・新品番（在庫20台） */
		array( 'slug' => 'srt-s467', 'title' => 'SRT-S467', 'maker' => 'mitsubishi',
		       'tank' => '460', 'people' => '4〜5人向け', 'grade' => 'フルオート',
		       'hojo' => '対象', 'remote' => 'RMCB-F7SE', 'kouatsu' => false, 'stock' => 20 ),

		/* 三菱・給湯専用（在庫8台）。国の補助金は対象外、石川県は対象です */
		array( 'slug' => 'srt-n466-2', 'title' => 'SRT-N466-2', 'maker' => 'mitsubishi',
		       'tank' => '460', 'people' => '4〜5人向け', 'grade' => '給湯専用',
		       'hojo' => '対象外', 'remote' => '', 'kouatsu' => false, 'stock' => 8 ),

		/* パナソニック（在庫9台）。国の補助金は対象外です */
		array( 'slug' => 'he-ns46lqs', 'title' => 'HE-NS46LQS', 'maker' => 'panasonic',
		       'tank' => '460', 'people' => '4〜7人向け', 'grade' => 'フルオート',
		       'hojo' => '対象外', 'remote' => '', 'kouatsu' => false, 'stock' => 9 ),
	);

	$eco_dmade = 0;

	foreach ( $eco_draft as $ed ) {

		if ( get_page_by_path( $ed['slug'], OBJECT, 'ymkrf_product' ) ) continue;

		$did = wp_insert_post( array(
			'post_type'   => 'ymkrf_product',
			'post_status' => 'draft',          /* ★下書きです */
			'post_title'  => $ed['title'],
			'post_name'   => $ed['slug'],
		) );
		if ( ! $did || is_wp_error( $did ) ) continue;

		update_post_meta( $did, '_ymkrf_name',      $ed['title'] );
		update_post_meta( $did, '_ymkrf_tank',      $ed['tank'] );
		update_post_meta( $did, '_ymkrf_people',    $ed['people'] );
		update_post_meta( $did, '_ymkrf_grade',     $ed['grade'] );
		update_post_meta( $did, '_ymkrf_hojo',      $ed['hojo'] );
		update_post_meta( $did, '_ymkrf_remote',    $ed['remote'] );
		update_post_meta( $did, '_ymkrf_accessory', $eco_acc );
		update_post_meta( $did, '_ymkrf_pressure',  $ed['kouatsu'] ? '高圧力型貯湯式' : '' );
		update_post_meta( $did, '_ymkrf_days',      '1' );
		update_post_meta( $did, '_ymkrf_pt1',       '工事は1日' );
		update_post_meta( $did, '_ymkrf_pt2',       '北陸電力への申請もおまかせ' );
		update_post_meta( $did, '_ymkrf_pt3',       '工事費・リモコン込' );
		update_post_meta( $did, '_ymkrf_caution',
			'※写真はイメージです。※電気温水器またはエコキュートからのお取り替えの場合の価格です。' );

		/* ★価格は空のままにします（あとで調べて入れていただきます） */
		update_post_meta( $did, '_ymkrf_work',  '' );
		update_post_meta( $did, '_ymkrf_item',  '' );
		update_post_meta( $did, '_ymkrf_total', '' );

		update_post_meta( $did, '_ymkrf_speclist',
			ymkrf_ecocute_speclist( array( 'grade' => $ed['grade'], 'hojo' => ( $ed['hojo'] === '対象' ) ) ) );

		$rows = array();
		foreach ( $eco_works as $r ) $rows[] = array( 'name' => $r[0], 'text' => $r[1] );
		update_post_meta( $did, '_ymkrf_works', $rows );

		wp_set_object_terms( $did, 'ecocute', 'ymkrf_product_cat' );
		wp_set_object_terms( $did, $ed['maker'], 'ymkrf_maker' );

		$log[] = '商品「' . $ed['title'] . '」を下書きで登録しました（在庫' . $ed['stock'] . '台・価格は空）';
		$eco_dmade++;
	}

	if ( $eco_dmade ) {
		$log[] = 'エコキュートを' . $eco_dmade . '機種、下書きで追加しました。'
		       . '価格を入れて「公開」にすると一覧に出ます';
	}

	/* 下書きで足した7機種を公開にします（1回だけ）。2026/09/01 ユーザー指示
	   「価格と商品写真は、あとで自分で足す」とのことなので、
	   価格・写真が空のまま先に出します。

	   ★SRT-W466 は、ここにふくめていません。
	     こちらは「在庫1台で、合計数4以上の条件から外れたから」下書きにしたもので、
	     価格待ちの7機種とは理由がちがうためです。
	     出したいときは、ダッシュボードで「公開」に変えてください。 */
	if ( get_option( 'ymkrf_eco_pub_ver' ) !== '1' ) {
		$eco_pub = array(
			'srt-s376u', 'srt-s376', 'srt-s466',
			'srt-s377', 'srt-s467', 'srt-n466-2', 'he-ns46lqs',
		);
		$done_pub = 0;
		foreach ( $eco_pub as $ps ) {
			$pp2 = get_page_by_path( $ps, OBJECT, 'ymkrf_product' );
			if ( ! $pp2 || $pp2->post_status !== 'draft' ) continue;
			wp_update_post( array( 'ID' => $pp2->ID, 'post_status' => 'publish' ) );
			$done_pub++;
		}
		if ( $done_pub ) {
			flush_rewrite_rules();
			$log[] = 'エコキュート' . $done_pub . '機種を公開にしました（価格・写真はこれからです）';
		}
		update_option( 'ymkrf_eco_pub_ver', '1' );
	}

	/* エコキュートのメーカーの「説明」を入れます（1回だけ）。
	   すでに何か書いてあるメーカーには、いっさい触りません。 */
	if ( get_option( 'ymkrf_eco_maker_ver' ) !== '1' ) {
		$eco_mdesc = array(
			'mitsubishi' => 'エコキュートを日本ではじめて発売したメーカーです。'
			              . 'タンクの中の湯量をこまかく見て、使う分だけわかす省エネの仕組みが得意です。',
			'panasonic'  => '住宅設備を幅広くつくっているメーカーです。'
			              . 'ご家庭の使い方をおぼえて、わかす量をかしこく調整する仕組みが入っています。',
		);
		foreach ( $eco_mdesc as $ems => $emt ) {
			$emt2 = get_term_by( 'slug', $ems, 'ymkrf_maker' );
			if ( ! $emt2 || is_wp_error( $emt2 ) ) continue;
			if ( trim( (string) $emt2->description ) !== '' ) continue;
			wp_update_term( $emt2->term_id, 'ymkrf_maker', array( 'description' => $emt ) );
			$log[] = 'メーカー「' . $emt2->name . '」の説明を入れました';
		}
		update_option( 'ymkrf_eco_maker_ver', '1' );
	}


	/* ------------------------------------------------------------
	   3-y. 最後にもう一度、アイキャッチを点検します。

	        上の 3-j でいちど直しても、そのあとの
	        「写真の差し替え」の処理でまた空になることがあったため、
	        いちばん最後にもう一度だけ見ています。
	        （ザ・クラッソとリデアMで実際に起きました）
	   ------------------------------------------------------------ */
	foreach ( $main_files as $slug => $file ) {
		$pp = get_page_by_path( $slug, OBJECT, 'ymkrf_product' );
		if ( ! $pp ) continue;

		$tid = get_post_thumbnail_id( $pp->ID );
		if ( $tid && get_post( $tid ) && get_attached_file( $tid ) ) continue;

		if ( $tid ) delete_post_thumbnail( $pp->ID );
		$re = $img( $file, get_the_title( $pp->ID ), true );
		if ( $re ) {
			set_post_thumbnail( $pp->ID, $re );
			$log[] = get_the_title( $pp->ID ) . 'のアイキャッチを入れ直しました（最終点検）';
		}
	}


	/* ------------------------------------------------------------
	   4. URLの設定を更新して、終了を記録
	   ------------------------------------------------------------ */
	flush_rewrite_rules();
	update_option( 'ymkrf_setup_done', YMKRF_SETUP_VER );
	update_option( 'ymkrf_setup_log', $log );
}, 99 );   /* 99 ＝ テーマが商品の投稿タイプを登録し終わったあとに動かすため */


/* 管理画面の上に、結果を1度だけ表示します */
add_action( 'admin_notices', function () {
	$log = get_option( 'ymkrf_setup_log' );
	if ( ! $log ) return;
	delete_option( 'ymkrf_setup_log' );
	echo '<div class="notice notice-success"><p><strong>リフォームヤマキシ 初期セットアップが完了しました（'
	   . count( $log ) . '件）</strong></p><p>'
	   . implode( '<br>', array_map( 'esc_html', array_slice( $log, -3 ) ) )
	   . '</p></div>';
} );
