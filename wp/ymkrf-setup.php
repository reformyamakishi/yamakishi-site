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

define( 'YMKRF_SETUP_VER', '10' );

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
