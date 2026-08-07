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

define( 'YMKRF_SETUP_VER', '13' );

add_action( 'admin_init', function () {

	if ( get_option( 'ymkrf_setup_done' ) === YMKRF_SETUP_VER ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;
	if ( ! post_type_exists( 'ymkrf_product' ) ) return; // テーマがまだ有効でない

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

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
		$theme . '/v-style',
		$dir,                 // 最後の受け皿（wp-content/ymkrf-import）
	);

	$img = function ( $file, $alt = '' ) use ( $dirs ) {
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
		if ( ! $path ) return $cache[ $file ] = 0;

		$up = wp_upload_bits( $file, null, file_get_contents( $path ) );
		if ( ! empty( $up['error'] ) ) return $cache[ $file ] = 0;

		$type = wp_check_filetype( $up['file'] );
		$id   = wp_insert_attachment( array(
			'post_mime_type' => $type['type'],
			'post_title'     => $alt ?: pathinfo( $file, PATHINFO_FILENAME ),
			'post_status'    => 'inherit',
		), $up['file'] );
		if ( ! $id || is_wp_error( $id ) ) return $cache[ $file ] = 0;

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

			$f = array(
				'_ymkrf_catch'   => '毎日の家事を楽しく、自分らしく彩る台所',
				'_ymkrf_grade'   => 'Dグレード',
				'_ymkrf_name'    => 'リフィット',
				'_ymkrf_size'    => 'I型2550サイズ',
				'_ymkrf_work'    => '240000',
				'_ymkrf_item'    => '558000',
				'_ymkrf_days'    => '2',
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

			$log[] = '商品「シエラS」を登録しました → ' . get_permalink( $sid );
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
	   4. URLの設定を更新して、終了を記録
	   ------------------------------------------------------------ */
	flush_rewrite_rules();
	update_option( 'ymkrf_setup_done', YMKRF_SETUP_VER );
	update_option( 'ymkrf_setup_log', $log );
} );


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
