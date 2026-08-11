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

define( 'YMKRF_SETUP_VER', '27' );

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
			'boiler'     => '給湯器・エコキュート',
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
			'ykkap'          => 'YKK AP',
			'nichiha'        => 'ニチハ',
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
		),
	);

	foreach ( $taxonomies as $tax => $terms ) {
		foreach ( $terms as $slug => $name ) {
			if ( term_exists( $slug, $tax ) ) continue;
			$r = wp_insert_term( $name, $tax, array( 'slug' => $slug ) );
			if ( ! is_wp_error( $r ) ) $log[] = "{$tax}: {$name}";
		}
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
		$dir,                 // 最後の受け皿（wp-content/ymkrf-import）
	);

	/* 見つからなかった写真の数。商品ごとに前後の差を取り、
	   「写真が入りきらなかった商品」を確実に見分けるために使います。 */
	$missing = 0;

	$img = function ( $file, $alt = '' ) use ( $dirs, &$missing ) {
		static $cache = array();
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
	$rebuild_keys = array( '_ymkrf_images', '_ymkrf_colors', '_ymkrf_tops', '_ymkrf_sinks', '_ymkrf_handles', '_ymkrf_specs', '_ymkrf_features', '_ymkrf_options' );

	foreach ( array( 'rakuera', 'refit', 'sierra-s', 's-class', 'stedia', 'edel', 'classo', 'richelle', 'centro' ) as $slug ) {

		$p = get_page_by_path( $slug, OBJECT, 'ymkrf_product' );
		if ( ! $p ) continue;

		$broken = false;

		/* アイキャッチが無い、または実体が消えている */
		$th = (int) get_post_thumbnail_id( $p->ID );
		if ( ! $th || ! get_attached_file( $th ) ) $broken = true;

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
			$works = array(
				array( '撤去工事',             '古いキッチンの撤去にかかる工事です。' ),
				array( '廃棄処分',             '撤去した古いキッチンを廃棄処分するためにかかる費用です。' ),
				array( 'ガス配管変更工事',     'ガスコンロを使うための配管工事です。' ),
				array( 'キッチンパネル設置工事', 'キッチンパネルの取り付け工事費です。' ),
				array( 'キッチンパネル部材費', 'キッチンパネル自体の部材費です。' ),
				array( '下地工事（大工工事）', 'キッチンパネル設置面の補修、補強の工事です。' ),
				array( 'シロッコファン取付工事', 'シロッコファンの取り付け工事費です。' ),
			);
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
	if ( $old_of && get_option( 'ymkrf_ofuroa_fix' ) !== '1' ) {
		wp_delete_post( $old_of->ID, true );
		update_option( 'ymkrf_ofuroa_fix', '1' );
		$log[] = 'オフローラを作り直しました（ポイント・オプションの列名の修正）';
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

			/* --- ヤマキシ標準工事内容 --- */
			$works = array(
				array( '解体撤去工事',     '既存の浴室を解体し、撤去する工事です。' ),
				array( '廃棄処分',         '解体した既存浴室を廃棄処分するためにかかる費用です。' ),
				array( '養生工事',         '搬入経路の床・壁・建具を保護します。' ),
				array( '水道工事',         '給水・給湯・排水の配管工事です。' ),
				array( '電気工事',         '照明・換気扇などの配線接続工事です。' ),
				array( '土間コンクリート工事', 'ユニットバスを据える土間をつくる工事です。' ),
				array( '大工工事',         '開口部の調整や下地の補修・補強を行います。' ),
				array( 'ユニットバス組立設置', '新しいユニットバスの組立・設置工事です。' ),
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
