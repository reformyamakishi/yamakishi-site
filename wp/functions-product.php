<?php
/**
 * 商品（ymkrf_product）─ リフォームヤマキシ
 *
 * functions.php から読み込んでください。
 *   require_once get_stylesheet_directory() . '/inc/functions-product.php';
 *
 * ── これで何ができるようになるか ────────────────────────
 * 管理画面に「商品」というメニューが増えます。
 * プログラムの知識がなくても、欄を埋めるだけで商品ページが1枚できます。
 * デザイン・レイアウト・構造化データはテンプレート側で自動なので、
 * 入力する人が気にする必要はありません。
 *
 * キッチン／お風呂／トイレ／洗面化粧台 …は「商品カテゴリ」で分けます。
 * どのカテゴリでも同じ入力画面・同じテンプレートが使えます。
 *
 * ── 追加費用はかかりません ─────────────────────────────
 * 有料プラグイン（ACF Proなど）は使っていません。
 * 繰り返し入力する欄（カラー・取っ手・仕様など）も自前です。
 *
 * ── 項目を増やしたくなったら ────────────────────────────
 * 下の「2. 入力欄の定義」だけを直せば入力画面が変わります。
 * （表示にも出したい場合は single-ymkrf_product.php にも追記が必要です）
 *
 * ── 名前について ───────────────────────────────────
 * サーバーに他サイトが同居しているため、すべて ymkrf 接頭辞付きです。
 * ───────────────────────────────────────────────
 */

if ( ! defined( 'ABSPATH' ) ) exit;


/* ============================================================
   1. 商品という入れ物と、分類をつくる
   ============================================================ */
add_action( 'init', function () {

	register_post_type( 'ymkrf_product', array(
		'label'         => '商品',
		'labels'        => array(
			'name'          => '商品',
			'singular_name' => '商品',
			'add_new'       => '新しい商品',
			'add_new_item'  => '商品を追加',
			'edit_item'     => '商品を編集',
			'search_items'  => '商品を検索',
			'not_found'     => '商品がありません',
		),
		'public'        => true,
		'has_archive'   => true,
		'menu_icon'     => 'dashicons-cart',
		'menu_position' => 4,
		'rewrite'       => array( 'slug' => 'products', 'with_front' => false ),
		'supports'      => array( 'title', 'thumbnail', 'page-attributes' ),
	) );

	/* 商品カテゴリ（キッチン／お風呂／トイレ／洗面化粧台 …）
	   → /products/kitchen/ のような一覧ページが自動でできます */
	register_taxonomy( 'ymkrf_product_cat', 'ymkrf_product', array(
		'label'             => '商品カテゴリ',
		'hierarchical'      => true,
		'rewrite'           => array( 'slug' => 'products', 'with_front' => false ),
		'show_admin_column' => true,
	) );

	/* メーカー（Panasonic／LIXIL／TOTO／クリナップ …） */
	register_taxonomy( 'ymkrf_maker', 'ymkrf_product', array(
		'label'             => 'メーカー',
		'hierarchical'      => true,
		'rewrite'           => array( 'slug' => 'maker', 'with_front' => false ),
		'show_admin_column' => true,
	) );

	/* 展示店舗（金沢野々市店／小松店 …）
	   →「この商品を見られるお店」の表示と、店舗ページからの逆引きに使います */
	register_taxonomy( 'ymkrf_shop', 'ymkrf_product', array(
		'label'             => '展示店舗',
		'hierarchical'      => true,
		'rewrite'           => array( 'slug' => 'shop-display', 'with_front' => false ),
		'show_admin_column' => true,
	) );
} );


/* ------------------------------------------------------------
   1-b. URLの形

     分類　　： /products/kitchen/
     商品　　： /products/kitchen/v-style/
     商品全部： /products/

   今の本番サイト（/products/kitchen/130/）と同じ形です。
   分類を1段はさむので、分類ページと商品ページのURLがぶつかりません。

   むかしの /products/v-style/ でも開けるようにしてあります
   （WordPressが元から作るルールが残るため）。
   ------------------------------------------------------------ */

/* 商品のURLに、その商品の分類を1段はさみます */
add_filter( 'post_type_link', function ( $link, $post ) {
	if ( get_post_type( $post ) !== 'ymkrf_product' ) return $link;

	$terms = get_the_terms( $post, 'ymkrf_product_cat' );
	if ( ! $terms || is_wp_error( $terms ) ) return $link;   // 分類が未設定なら今までどおり

	$cat = $terms[0]->slug;
	return preg_replace( '#/products/([^/]+)/?$#', '/products/' . $cat . '/$1/', $link );
}, 10, 2 );

add_action( 'init', function () {

	$slugs = get_terms( array(
		'taxonomy'   => 'ymkrf_product_cat',
		'hide_empty' => false,
		'fields'     => 'slugs',
	) );

	/* /products/<分類>/ … 分類ページ（1段だけのとき） */
	if ( ! is_wp_error( $slugs ) && $slugs ) {
		$re = implode( '|', array_map( 'preg_quote', $slugs ) );
		add_rewrite_rule( '^products/(' . $re . ')/page/([0-9]{1,})/?$',
			'index.php?ymkrf_product_cat=$matches[1]&paged=$matches[2]', 'top' );
		add_rewrite_rule( '^products/(' . $re . ')/?$',
			'index.php?ymkrf_product_cat=$matches[1]', 'top' );
	}

	/* 水まわり4点パック。分類でも商品でもない専用ページなので、
	   ほかのルールより先に置いています */
	add_rewrite_rule( '^products/pack4/?$', 'index.php?ymkrf_pack4=1', 'top' );

	/* /products/<分類>/<商品>/ … 商品ページ（2段のとき）
	   分類の部分は見ていないので、あとから商品の分類を変えても開けます */
	add_rewrite_rule( '^products/[^/]+/([^/]+)/?$',
		'index.php?ymkrf_product=$matches[1]', 'top' );
}, 20 );

/* 上のルールで使う目印をWordPressに教えます */
add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'ymkrf_pack4';
	return $vars;
} );

/* 目印があったら、4点パックのテンプレートで表示します */
add_filter( 'template_include', function ( $tpl ) {
	if ( ! get_query_var( 'ymkrf_pack4' ) ) return $tpl;
	$found = locate_template( 'ymkrf-pack4.php' );
	return $found ? $found : $tpl;
} );

/* 4点パックのページは「見つかりません」ではありません。

   ★ここが抜けていたため、CSSが当たらない不具合が出ていました。
     このURLはWordPressから見ると「何も指定のないページ」なので、
     そのままだとトップページ（is_front_page）と判定されてしまい、
     下層ページ用の page.css が読み込まれませんでした。
     下で「トップでもなければ一覧でもない」とはっきりさせています。 */
add_action( 'wp', function () {
	if ( ! get_query_var( 'ymkrf_pack4' ) ) return;
	global $wp_query;
	$wp_query->is_404        = false;
	$wp_query->is_home       = false;
	$wp_query->is_front_page = false;
	$wp_query->is_page       = false;
	$wp_query->is_singular   = false;
	$wp_query->is_archive    = false;
	$wp_query->is_post_type_archive = false;
	status_header( 200 );
} );

/* ブラウザのタブに出る題名。ふつうのページと同じ形にそろえます。 */
add_filter( 'document_title_parts', function ( $parts ) {
	if ( get_query_var( 'ymkrf_pack4' ) ) {
		$parts['title'] = 'Web限定 水まわり4点パック';
		unset( $parts['tagline'] );
	}
	return $parts;
} );

/* 4点パックのページかどうか。CSSの読み分けなどで使います。 */
if ( ! function_exists( 'ymkrf_is_pack4' ) ) :
function ymkrf_is_pack4() {
	return (bool) get_query_var( 'ymkrf_pack4' );
}
endif;

/**
 * 念のための保険。
 * URLのルールの順番によっては、/products/kitchen/ が
 * 「kitchen という名前の商品」として読まれてしまうことがあります。
 * そのときは分類ページとして開き直します。
 */
add_filter( 'request', function ( $qv ) {
	if ( empty( $qv['ymkrf_product'] ) ) return $qv;
	if ( ! taxonomy_exists( 'ymkrf_product_cat' ) ) return $qv;

	$slug = $qv['ymkrf_product'];
	if ( get_page_by_path( $slug, OBJECT, 'ymkrf_product' ) ) return $qv;  // 同名の商品があるなら商品を優先

	$term = get_term_by( 'slug', $slug, 'ymkrf_product_cat' );
	if ( ! $term || is_wp_error( $term ) ) return $qv;

	$new = array( 'ymkrf_product_cat' => $term->slug );
	if ( isset( $qv['paged'] ) ) $new['paged'] = $qv['paged'];
	return $new;
} );

/* メーカーを足したいときは、ここに書き足してください。
   いちど登録されたものは、そのままにします。
   （数字を1つ上げると、次の表示のときに1回だけ登録し直します） */
add_action( 'init', function () {
	if ( get_option( 'ymkrf_maker_ver' ) === '2' ) return;

	$makers = array(
		'ykkap'       => 'YKK AP',
		'woodone'     => 'WOODONE（ウッドワン）',
		'nichiha'     => 'ニチハ',
		'sankyoalumi' => '三協アルミ',
	);
	foreach ( $makers as $slug => $name ) {
		if ( term_exists( $slug, 'ymkrf_maker' ) ) continue;
		wp_insert_term( $name, 'ymkrf_maker', array( 'slug' => $slug ) );
	}
	update_option( 'ymkrf_maker_ver', '2' );
}, 25 );

/* 分類を足す・変える・消したときは、URLのルールを作り直します */
foreach ( array( 'created', 'edited', 'delete' ) as $when ) {
	add_action( $when . '_ymkrf_product_cat', function () {
		delete_option( 'ymkrf_rewrite_ver' );
	} );
}

/* 上のルールを1回だけ反映させます（数字を変えると、もう一度だけ反映されます） */
add_action( 'init', function () {
	/* 数字を1つ上げると、次の表示のときに1回だけURLのルールを作り直します。
	   8 … こだわりページ（/about/）を追加したため
	   9 … お客様の声のURLを /voice/工事箇所/案件番号/ に変えたため
	   10 … 工事箇所ごとの一覧（/voice/oiltank/）の作りを直したため
	   11 … 地域ごとの一覧（/voice/area/kanazawa/）を足したため
	   12 … 施工事例のURLを /works/部位/案件番号/ に変えたため
	   13 … スタッフ（/staff/）を足したため
	   14 … 代表挨拶ページ（/message/）を足したため
	   15 … 会社概要ページ（/company/）を足したため
	   20 … プライバシーポリシー（/privacy/）を足したため
	   21 … お知らせ（/news/）を足したため */
	if ( get_option( 'ymkrf_rewrite_ver' ) === '21' ) return;
	flush_rewrite_rules( false );
	update_option( 'ymkrf_rewrite_ver', '21' );
}, 99 );


/* ============================================================
   2. 入力欄の定義　★項目を増やすときはここ
   ============================================================ */

/** 1つだけ入力する欄 */
function ymkrf_product_fields() {
	return array(
		//  キー           => array( 見出し, 種類, 入力例, 補足説明 )
		'_ymkrf_catch'   => array( 'キャッチコピー',   'text',   '例：キレイと快適が毎日つづく快適キッチン！', '商品名の上に、小さな赤い文字で出ます' ),
		'_ymkrf_grade'   => array( 'グレード',         'text',   '例：Fグレード', '空欄でもかまいません' ),
		'_ymkrf_order'   => array( '並び順',           'number', '例：85',
			'空欄のままで大丈夫です。グレードから自動で決まります（J=10／I=20／H=30／G=40／F=50／E=60／D=70／C=80／B=90／A=100／S=110／SS=120／SSS=125／プレミアム=130）。順番を変えたいときだけ、入れたい位置の数字を書いてください。数字が小さいほど先に出ます。' ),
		'_ymkrf_name'    => array( '商品名',           'text',   '例：V-style（Vスタイル）', '空欄なら上のタイトルを使います' ),
		'_ymkrf_size'    => array( '型（サイズ）',     'text',   '例：I型2550サイズ', 'メーカーロゴのとなりに出ます' ),
		'_ymkrf_sub'     => array( '商品名の横の言葉', 'text',   '例：ハイパーキラミック', '商品名のすぐ横に、小さく出ます（トイレの陶器の種類など）' ),
		'_ymkrf_work'    => array( '標準工事費（円・税込）', 'number', '例：240000', '★税込の金額を入れてください。数字だけ。カンマや「円」は不要です' ),
		'_ymkrf_item'    => array( '商品代（円・税込）',     'number', '例：358000', '★税込の金額を入れてください。数字だけ。カンマや「円」は不要です' ),
		'_ymkrf_days'    => array( '工期（日数）',     'number', '例：3', '数字だけ。「日」は自動で付きます' ),
		'_ymkrf_daystext'=> array( '工期の書き方',     'text',   '例：半日', '「半日」など、日数で書けないときだけ入れてください。入れると上の日数より優先されます' ),
		'_ymkrf_pt1'     => array( '特徴 1',           'text',   '例：お手頃価格', '' ),
		'_ymkrf_pt2'     => array( '特徴 2',           'text',   '例：収納抜群', '' ),
		'_ymkrf_pt3'     => array( '特徴 3',           'text',   '例：おそうじ楽々', '' ),
		'_ymkrf_caution' => array( '写真の注意書き',   'text',   '例：※写真はイメージです。', '商品写真の下に小さく出ます' ),

		/* ---- ここから下は給湯器・エコキュートだけで使う欄です（2026/09/01 追加） ----
		   5つめの欄は「えらぶ言葉」、6つめは「この分類だけに出す」という意味です。 */
		'_ymkrf_exterior'   => array( '外装', 'select', '', '',
			array( '', '塗装鋼板', 'ステンレス' ),
			array( 'boiler' ) ),
		'_ymkrf_dim'        => array( '寸法（mm）', 'text', '例：高さ600×幅480×奥行200',
			'空欄のときは、商品ページにこの項目が出ません',
			array(), array( 'boiler', 'ecocute' ) ),
		'_ymkrf_weight'     => array( '質量（kg）', 'number', '例：19',
			'数字だけ。空欄のときは、商品ページにこの項目が出ません',
			array(), array( 'boiler', 'ecocute' ) ),
		'_ymkrf_pressure'   => array( '給湯圧力', 'select', '', '',
			array( '', '水道直圧式', '貯湯式', '高圧力型貯湯式' ),
			array( 'boiler', 'ecocute' ) ),
		'_ymkrf_power'      => array( '給湯能力', 'numunit', '例：20',
			'数字だけ入れてください。「号」ならそのまま（20 →「20号」）、'
			. '「万kcal/h」なら万の単位で（4 →「4万kcal/h」）',
			array( '号', '万kcal/h' ),
			array( 'boiler' ) ),
		/* ↑の単位。上の欄といっしょに出るので、単独では出しません */
		'_ymkrf_power_unit' => array( '', 'unit', '', '', array(), array( 'boiler' ) ),

		/* ---- ここから下はエコキュートだけで使う欄です（2026/09/01 追加） ---- */
		'_ymkrf_tank'      => array( 'タンク容量（L）', 'number', '例：370',
			'数字だけ。空欄のときは、商品ページにこの項目が出ません',
			array(), array( 'ecocute' ) ),
		'_ymkrf_people'    => array( '対象人数', 'text', '例：3〜4人向け',
			'タンク容量のすぐ下に出ます。一覧の分け方にも使われます',
			array(), array( 'ecocute' ) ),
		'_ymkrf_accessory' => array( '付属品', 'text', '例：脚部カバー／ベーシックリモコン',
			'いくつかあるときは「／」で区切ってください',
			array(), array( 'ecocute' ) ),
		/* 国の補助金は、ネットワーク対応リモコンが付いている場合だけ対象になります。
		   そのため「補助金対応リモコン」として出しています。
		   （在庫確認シートの「補助金対応 リモコン品番」の欄と同じものです） */
		'_ymkrf_remote'    => array( '補助金対応リモコン品番', 'text', '例：RMCB-F7SE',
			'標準仕様の表に「補助金対応リモコン」として出ます。空欄のときは、この項目が出ません',
			array(), array( 'ecocute' ) ),
		/* 国の補助金は年度で中身が変わるので、金額は入れず「対象かどうか」だけ持ちます。
		   在庫確認シートの「2026 国 補助金対象」の〇×に合わせてください。 */
		'_ymkrf_hojo'      => array( '補助金', 'select', '',
			'「対象」をえらぶと、商品ページに「補助金適用」と出ます',
			array( '', '対象', '対象外' ), array( 'ecocute' ) ),

		/* ---- ここから下は社内用です。お客様のページには出ません ----
		   Gドライブの「住設機器　在庫確認＆発注確認」を見て入れています。
		   シートは日々変わるので、たまに入れ直してください。 */
		'_ymkrf_stock'      => array( '在庫数（社内用）', 'number', '例：12',
			'★お客様のページには出ません。ダッシュボードの一覧にだけ出ます',
			array(), array( 'ecocute' ) ),
		'_ymkrf_stockshop'  => array( '在庫店舗（社内用）', 'text',
			'例：小松3台／野々市2台／羽咋7台',
			'★お客様のページには出ません。「／」で区切って書いてください',
			array(), array( 'ecocute' ) ),
		'_ymkrf_stockdate'  => array( '在庫の確認日（社内用）', 'text', '例：2026/09/01',
			'★お客様のページには出ません。在庫数をいつ写したかの目印です',
			array(), array( 'ecocute' ) ),
	);
}


/* ------------------------------------------------------------
   2-b. 分類ごとに、入力欄の名前・種類・並び順を変えます
        （2026/09/01 ユーザー指示）

        給湯器では
          キャッチコピー → 「給湯器カテゴリ」（4つからえらぶ）
          商品名        → 「型式」
        にします。中に入るデータ（メタ）は同じなので、
        すでに登録ずみの商品もそのまま使えます。
   ------------------------------------------------------------ */
if ( ! function_exists( 'ymkrf_product_field_overrides' ) ) :
function ymkrf_product_field_overrides() {
	return array(
		'boiler' => array(
			'_ymkrf_catch' => array( '給湯器カテゴリ', 'select', '',
				'商品ページで、型式の上に小さく出ます。一覧の分け方にも使われます',
				array( '', 'ガス給湯器', 'エコジョーズ（高効率ガス給湯器）',
				       '石油給湯器', 'エコフィール（高効率石油給湯器）' ) ),
			'_ymkrf_name'  => array( '型式', 'text', '例：GT-2070SAW BL',
				'空欄なら上のタイトルを使います' ),
			'_ymkrf_size'  => array( '設置方法', 'text', '例：壁掛設置',
				'メーカーロゴのとなりと、標準仕様の表に出ます' ),
			'_ymkrf_caution' => array( '注意書き', 'text', '例：※写真はイメージです。',
				'商品写真の下に小さく出ます' ),
			'_ymkrf_grade' => array( 'ふろ機能', 'select', '',
				'商品写真の上の帯に出ます',
				array( '', 'オート', 'フルオート' ) ),
			/* グレード（J・I・H…）は給湯器では使わないので、説明も書きかえます */
			'_ymkrf_order' => array( '並び順', 'number', '例：50',
				'数字が小さいほど先に出ます。同じまとまりの中では、安い順に並びます。'
				. '空欄のままでもかまいません' ),
		),

		/* エコキュートは、給湯器とよく似た並びですが
		     キャッチコピー → 「おすすめ表示」（赤い文字のふだ）
		     グレード       → 「タイプ」（フルオート・高圧・高効率）
		   になります。 */
		'ecocute' => array(
			'_ymkrf_catch' => array( 'おすすめ表示', 'text',
				'例：補助金対象商品！',
				'商品写真の下に、赤い文字で出ます。'
				. '「数量限定！早い者勝ち！」「処分アイテム！在庫残り2台！」なども入れられます。空欄でもかまいません' ),
			'_ymkrf_name'  => array( '型番', 'text', '例：SRT-S377U',
				'空欄なら上のタイトルを使います' ),
			'_ymkrf_size'  => array( '設置方法', 'text', '例：屋外設置',
				'メーカーロゴのとなりと、標準仕様の表に出ます。空欄でもかまいません' ),
			'_ymkrf_grade' => array( 'タイプ', 'select', '',
				'商品写真の上の帯に出ます',
				array( '', 'フルオート', 'フルオート・高圧', 'フルオート・高圧・高効率',
				       'オート', 'オート・高圧', '給湯専用' ) ),
			'_ymkrf_caution' => array( '注意書き', 'text',
				'例：※電気温水器またはエコキュートからの交換限定価格',
				'商品写真の下に小さく出ます' ),
			'_ymkrf_order' => array( '並び順', 'number', '例：50',
				'数字が小さいほど先に出ます。同じまとまりの中では、安い順に並びます。'
				. '空欄のままでもかまいません' ),
		),
	);
}
endif;

/** 分類ごとの並び順（ここに書いていない欄は、うしろに元の順で付きます） */
if ( ! function_exists( 'ymkrf_product_field_order' ) ) :
function ymkrf_product_field_order() {
	return array(
		/* 「並び順」は使うことが少ないので、いちばん下にします */
		'boiler' => array(
			'_ymkrf_catch', '_ymkrf_name', '_ymkrf_size', '_ymkrf_exterior',
			'_ymkrf_dim', '_ymkrf_weight', '_ymkrf_pressure', '_ymkrf_power',
			'_ymkrf_grade',
			'_ymkrf_work', '_ymkrf_item',
			'_ymkrf_days', '_ymkrf_daystext',
			'_ymkrf_pt1', '_ymkrf_pt2', '_ymkrf_pt3',
			'_ymkrf_caution',
			'_ymkrf_order',
		),
		'ecocute' => array(
			'_ymkrf_name', '_ymkrf_tank', '_ymkrf_people', '_ymkrf_grade',
			'_ymkrf_hojo', '_ymkrf_accessory', '_ymkrf_remote',
			'_ymkrf_size',
			'_ymkrf_dim', '_ymkrf_weight', '_ymkrf_pressure',
			'_ymkrf_work', '_ymkrf_item',
			'_ymkrf_days', '_ymkrf_daystext',
			'_ymkrf_catch',
			'_ymkrf_pt1', '_ymkrf_pt2', '_ymkrf_pt3',
			'_ymkrf_caution',
			'_ymkrf_order',
			/* 社内用（お客様のページには出ません） */
			'_ymkrf_stock', '_ymkrf_stockshop', '_ymkrf_stockdate',
		),
	);
}
endif;

/** いま開いている商品の分類（新規作成のときはURLから） */
if ( ! function_exists( 'ymkrf_product_current_cat' ) ) :
function ymkrf_product_current_cat( $post_id = 0 ) {
	if ( isset( $_GET['ymkrf_cat'] ) ) return sanitize_title( wp_unslash( $_GET['ymkrf_cat'] ) );
	$ts = $post_id ? get_the_terms( $post_id, 'ymkrf_product_cat' ) : null;
	return ( $ts && ! is_wp_error( $ts ) ) ? $ts[0]->slug : '';
}
endif;

/** その分類で出す入力欄（名前・種類・並び順を入れかえたもの） */
if ( ! function_exists( 'ymkrf_product_fields_for' ) ) :
function ymkrf_product_fields_for( $cat = '' ) {

	$all = ymkrf_product_fields();

	/* その分類で使わない欄を外します */
	$out = array();
	foreach ( $all as $k => $f ) {
		$only = isset( $f[5] ) ? (array) $f[5] : array();
		if ( $only && ! in_array( $cat, $only, true ) ) continue;
		$out[ $k ] = $f;
	}

	/* 給湯器・エコキュートでは「商品名の横の言葉」は使いません。
	   塗装鋼板・ステンレスは「外装」の欄に入れます（2026/09/01 ユーザー指示） */
	if ( in_array( $cat, array( 'boiler', 'ecocute' ), true ) ) {
		unset( $out['_ymkrf_sub'] );
	}

	/* 名前・種類の入れかえ */
	$ov = ymkrf_product_field_overrides();
	if ( isset( $ov[ $cat ] ) ) {
		foreach ( $ov[ $cat ] as $k => $f ) {
			if ( isset( $out[ $k ] ) ) $out[ $k ] = $f;
		}
	}

	/* 並び順 */
	$or = ymkrf_product_field_order();
	if ( isset( $or[ $cat ] ) ) {
		$sorted = array();
		foreach ( $or[ $cat ] as $k ) {
			if ( isset( $out[ $k ] ) ) { $sorted[ $k ] = $out[ $k ]; unset( $out[ $k ] ); }
		}
		$out = array_merge( $sorted, $out );
	}

	return $out;
}
endif;

/** 何行でも増やせる欄 */
function ymkrf_product_repeaters() {
	return array(
		'_ymkrf_images' => array(
			'label' => '組み合わせイメージ写真',
			'note'  => 'カラーバリエーションのいちばん上に、横一列で並びます。3〜4枚が目安です。無ければ空のままでOK。',
			'cols'  => array(
				'img' => array( '写真', 'image' ),
				'alt' => array( '写真の説明', 'text', '例：木目調の扉に、黒いハンドル取手を合わせた例' ),
			),
		),
		'_ymkrf_colors' => array(
			'label' => '扉カラー',
			'note'  => '色見本の写真と、色の名前を入れてください。',
			'cols'  => array(
				'img'  => array( '色見本', 'image' ),
				'name' => array( '色の名前', 'text', '例：ホワイト' ),
			),
		),
		'_ymkrf_tops' => array(
			'label' => '天板カラー',
			'note'  => 'ワークトップの色見本です。無ければ空のままでOK。見出しごと出なくなります。',
			'cols'  => array(
				'img'  => array( '色見本', 'image' ),
				'name' => array( '色の名前', 'text', '例：シャインベージュ' ),
			),
		),
		'_ymkrf_sinks' => array(
			'label' => 'シンクカラー',
			'note'  => 'シンクの色見本です。無ければ空のままでOK。見出しごと出なくなります。',
			'cols'  => array(
				'img'  => array( '色見本', 'image' ),
				'name' => array( '色の名前', 'text', '例：グラニュールホワイト' ),
			),
		),
		'_ymkrf_c4' => array(
			'label' => 'カラー枠4',
			'note'  => 'お風呂など、色の分類が多い商品用の予備枠です。見出しは下の「カラー見出し」で変えられます。',
			'cols'  => array(
				'img'  => array( '色見本', 'image' ),
				'name' => array( '色の名前', 'text' ),
			),
		),
		'_ymkrf_c5' => array(
			'label' => 'カラー枠5',
			'note'  => '同上。無ければ空のままでOK。',
			'cols'  => array(
				'img'  => array( '色見本', 'image' ),
				'name' => array( '色の名前', 'text' ),
			),
		),
		'_ymkrf_c6' => array(
			'label' => 'カラー枠6',
			'note'  => '同上。無ければ空のままでOK。',
			'cols'  => array(
				'img'  => array( '色見本', 'image' ),
				'name' => array( '色の名前', 'text' ),
			),
		),
		'_ymkrf_handles' => array(
			'label' => '取っ手',
			'note'  => 'キッチン以外で使わない場合は、空のままでOK。見出しごと出なくなります。',
			'cols'  => array(
				'img'  => array( '写真', 'image' ),
				'name' => array( '名称', 'text', '例：ハンドル取手' ),
				'code' => array( '型番', 'text', '例：HAN' ),
			),
		),
		'_ymkrf_specs' => array(
			'label' => '標準仕様',
			'note'  => '標準で付いてくる設備を並べます。',
			'cols'  => array(
				'img'   => array( '写真', 'image' ),
				'name'  => array( '品名', 'text', '例：ホーロー3口トップコンロ' ),
				'model' => array( '型番など', 'text', '例：LEEG32T1V' ),
			),
		),
		'_ymkrf_speclist' => array(
			'label' => '標準仕様（文字だけの一覧）',
			'note'  => 'トイレのように、写真ではなく機能名を並べる商品で使います。'
			         . '「分類」に 快適機能 などを入れ、「機能」に1行ずつ書いてください。'
			         . '上の「標準仕様」に写真を入れている商品は、こちらは空のままでOKです。',
			'cols'  => array(
				'ttl'  => array( '分類',   'text', '例：快適機能' ),
				'body' => array( '機能',   'textarea', "例：\n暖房便座\nスローダウン便座" ),
			),
		),
		'_ymkrf_features' => array(
			'label' => 'おすすめポイント',
			'note'  => '「グループ小見出し」「グループ見出し」を空欄にすると、ひとつ上の行と同じまとまりになります。'
			         . '（例：フロアストッカーの中に Point 1、Point 2 を並べたいとき）',
			'cols'  => array(
				'gsub' => array( 'グループ小見出し', 'text', '例：収納力抜群' ),
				'gttl' => array( 'グループ見出し',   'text', '例：「フロアストッカー」' ),
				'ttl'  => array( '見出し',           'text', '例：大割のスライド収納' ),
				'text' => array( '説明',             'textarea', '' ),
				'note' => array( '注記',             'text', '例：※地質、建物の構造などにより…' ),
				'img'  => array( '写真1',            'image' ),
				'img2' => array( '写真2',            'image' ),
				'frame'=> array( '白い枠をつける',    'text', '説明図・グラフのときだけ 1' ),
			),
		),
		'_ymkrf_options' => array(
			'label' => 'おすすめオプション',
			'note'  => '',
			'cols'  => array(
				'img'   => array( '写真', 'image' ),
				'name'  => array( '品名', 'text', '例：W450mmプルオープン 食器洗い乾燥機' ),
				'text'  => array( '説明', 'textarea', '' ),
				'price' => array( '追加金額（円）', 'number', '例：176000' ),
				'note'  => array( '補足', 'text', '例：※工事費込み' ),
			),
		),
		'_ymkrf_works' => array(
			'label' => 'ヤマキシ標準工事内容',
			'note'  => '同じカテゴリの商品は、だいたい同じ内容になります。'
			         . '商品一覧の「複製して新規作成」から作れば、ここを入力し直さずに済みます。',
			'cols'  => array(
				'name' => array( '工事名', 'text', '例：撤去工事' ),
				'text' => array( '説明',   'text', '例：古いキッチンの撤去にかかる工事です。' ),
			),
		),
	);
}


/* ============================================================
   3. 入力画面
   ============================================================ */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'ymkrf_product_basic', '商品データ（基本）', 'ymkrf_product_box_basic', 'ymkrf_product', 'normal', 'high' );
	foreach ( ymkrf_product_repeaters() as $key => $r ) {
		add_meta_box(
			'ymkrf_box' . $key, $r['label'],
			function ( $post ) use ( $key ) { ymkrf_product_box_repeater( $post, $key ); },
			'ymkrf_product', 'normal', 'default'
		);
	}
} );

/* ============================================================
   3-b. 分類ごとに、入力する欄をしぼります
        （2026/09/01 ユーザー指示。「すべての商品で入力欄が同じだと登録しにくい」）

        キッチンには「扉カラー」「天板カラー」「取っ手」がありますが、
        給湯器には要りません。逆に給湯器には「標準仕様（文字だけの一覧）」を使います。
        そこで、その分類で実際に使われている欄だけを出すようにしました。

        ★どの欄を使うかは、手で決めるのではなく、
          その分類にすでに登録されている商品から自動で調べています。
          あとから商品を足しても、勝手についてきます。

        ★安全のために、次の場合は隠しません。
          ・いま開いている商品に、すでに何か入っている欄
          ・まだ商品が1つも無い分類（どの欄を使うか分からないため）

        ★隠れた欄は、上の「使わない欄も表示する」で出せます。
          （表示・非表示だけの話で、入っているデータは消えません）
   ============================================================ */
if ( ! function_exists( 'ymkrf_product_usedmap' ) ) :
function ymkrf_product_usedmap() {

	$keys = array_keys( ymkrf_product_repeaters() );
	$map  = array();

	$terms = get_terms( array(
		'taxonomy'   => 'ymkrf_product_cat',
		'hide_empty' => false,
	) );
	if ( is_wp_error( $terms ) || ! $terms ) return $map;

	foreach ( $terms as $t ) {

		$ids = get_posts( array(
			'post_type'      => 'ymkrf_product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'tax_query'      => array( array(
				'taxonomy' => 'ymkrf_product_cat',
				'field'    => 'term_id',
				'terms'    => $t->term_id,
			) ),
		) );

		/* まだ商品が無い分類は、ぜんぶ出します */
		if ( ! $ids ) { $map[ (string) $t->term_id ] = null; continue; }

		$use = array();
		foreach ( $keys as $k ) {
			foreach ( $ids as $pid ) {
				$v = get_post_meta( $pid, $k, true );
				if ( is_array( $v ) && $v ) { $use[] = $k; break; }
			}
		}
		$map[ (string) $t->term_id ] = $use;
	}
	return $map;
}
endif;

add_action( 'admin_footer', function () {

	$s = get_current_screen();
	if ( ! $s || $s->post_type !== 'ymkrf_product' ) return;
	if ( ! in_array( $s->base, array( 'post' ), true ) ) return;

	$post_id = isset( $GLOBALS['post'] ) ? (int) $GLOBALS['post']->ID : 0;

	/* いま開いている商品に、すでに何か入っている欄 */
	$has = array();
	foreach ( array_keys( ymkrf_product_repeaters() ) as $k ) {
		$v = $post_id ? get_post_meta( $post_id, $k, true ) : '';
		if ( is_array( $v ) && $v ) $has[] = $k;
	}
	?>
	<script>
	(function () {
		var MAP  = <?php echo wp_json_encode( ymkrf_product_usedmap() ); ?>;
		var HAS  = <?php echo wp_json_encode( $has ); ?>;
		var KEYS = <?php echo wp_json_encode( array_keys( ymkrf_product_repeaters() ) ); ?>;

		var list = document.getElementById('ymkrf_product_catchecklist');
		if (!list) return;

		/* 「使わない欄も表示する」のスイッチ */
		var first = document.getElementById('ymkrf_box' + KEYS[0]);
		if (!first) return;

		var bar = document.createElement('div');
		bar.className = 'postbox';
		bar.style.cssText = 'padding:11px 14px;background:#f6f7f7;border-color:#dcdcde';
		bar.innerHTML = '<label style="font-weight:600;cursor:pointer">'
		  + '<input type="checkbox" id="ymkrf-showall"> 使わない欄も表示する</label>'
		  + '<span id="ymkrf-hidnum" style="margin-left:12px;color:#787c82;font-size:12px"></span>';
		first.parentNode.insertBefore(bar, first);

		var showall = document.getElementById('ymkrf-showall');
		var numTxt  = document.getElementById('ymkrf-hidnum');
		try { showall.checked = (localStorage.getItem('ymkrfShowAllBoxes') === '1'); } catch (e) {}

		function apply() {
			var checked = [].slice.call(list.querySelectorAll('input[type=checkbox]:checked'));

			/* えらばれている分類の「使う欄」をぜんぶ足します。
			   ひとつでも「ぜんぶ出す」分類があれば、しぼりません。 */
			var use = null;
			if (checked.length) {
				use = [];
				for (var i = 0; i < checked.length; i++) {
					var m = MAP[String(checked[i].value)];
					if (m === null || m === undefined) { use = null; break; }
					use = use.concat(m);
				}
			}

			var hidden = 0;
			KEYS.forEach(function (k) {
				var box = document.getElementById('ymkrf_box' + k);
				if (!box) return;
				var keep = showall.checked
				        || use === null
				        || use.indexOf(k) > -1
				        || HAS.indexOf(k) > -1;
				box.style.display = keep ? '' : 'none';
				if (!keep) hidden++;
			});
			numTxt.textContent = hidden ? ('この分類で使わない ' + hidden + ' 項目を隠しています') : '';
		}

		list.addEventListener('change', apply);
		showall.addEventListener('change', function () {
			try { localStorage.setItem('ymkrfShowAllBoxes', showall.checked ? '1' : '0'); } catch (e) {}
			apply();
		});
		apply();
	})();
	</script>
	<?php
} );

/** 写真を選ぶ機能のために、WordPressのメディア画面と並べ替え機能を読み込む */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	global $post_type;
	if ( $post_type === 'ymkrf_product' && in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		wp_enqueue_media();
		wp_enqueue_script( 'jquery-ui-sortable' );
	}
} );

function ymkrf_product_box_basic( $post ) {
	wp_nonce_field( 'ymkrf_product_save', 'ymkrf_product_nonce' );
	echo ymkrf_product_admin_assets();

	$cat = ymkrf_product_current_cat( $post->ID );

	echo '<table class="ymkrf-tbl">';
	foreach ( ymkrf_product_fields_for( $cat ) as $key => $f ) {

		/* 単位の欄は、ひとつ上の「給湯能力」といっしょに出しています */
		if ( $f[1] === 'unit' ) continue;

		$val  = (string) get_post_meta( $post->ID, $key, true );
		$note = ! empty( $f[3] ) ? '<span class="ymkrf-note">' . esc_html( $f[3] ) . '</span>' : '';

		echo '<tr><th><label for="' . esc_attr( $key ) . '">' . esc_html( $f[0] ) . '</label></th><td>';

		if ( $f[1] === 'select' ) {

			/* えらぶ欄。いま入っている言葉が一覧に無いときは、消えないように足します */
			$opts = isset( $f[4] ) ? (array) $f[4] : array();
			if ( $val !== '' && ! in_array( $val, $opts, true ) ) $opts[] = $val;

			echo '<select id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">';
			foreach ( $opts as $o ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $o ),
					selected( $val, $o, false ),
					$o === '' ? '（えらんでください）' : esc_html( $o )
				);
			}
			echo '</select>';

		} elseif ( $f[1] === 'numunit' ) {

			/* 数字＋単位。単位は「<キー>_unit」に入ります */
			$ukey = $key . '_unit';
			$uval = (string) get_post_meta( $post->ID, $ukey, true );
			$uopt = isset( $f[4] ) ? (array) $f[4] : array();
			if ( $uval === '' && $uopt ) $uval = $uopt[0];

			printf(
				'<input type="number" step="any" id="%1$s" name="%1$s" value="%2$s" placeholder="%3$s" style="width:110px">',
				esc_attr( $key ), esc_attr( $val ), esc_attr( $f[2] )
			);
			echo ' <select name="' . esc_attr( $ukey ) . '" style="width:auto">';
			foreach ( $uopt as $o ) {
				printf( '<option value="%s"%s>%s</option>',
					esc_attr( $o ), selected( $uval, $o, false ), esc_html( $o ) );
			}
			echo '</select>';

		} else {

			printf(
				'<input type="%1$s" id="%2$s" name="%2$s" value="%3$s" placeholder="%4$s">',
				esc_attr( $f[1] ), esc_attr( $key ), esc_attr( $val ), esc_attr( $f[2] )
			);
		}

		echo $note . '</td></tr>'; /* phpcs:ignore */
	}
	echo '</table>';

	$total = (int) get_post_meta( $post->ID, '_ymkrf_work', true ) + (int) get_post_meta( $post->ID, '_ymkrf_item', true );
	printf(
		'<p class="ymkrf-total">込み価格（自動計算）　<b id="ymkrf-total">%s</b> 円（税込）
		 <span class="ymkrf-note">標準工事費 ＋ 商品代 の合計です。入力の必要はありません。</span></p>',
		number_format( $total )
	);
	?>
	<script>
	(function(){
	  var w = document.getElementById('_ymkrf_work'),
	      i = document.getElementById('_ymkrf_item'),
	      t = document.getElementById('ymkrf-total');
	  if (!w || !i || !t) return;
	  function calc(){ t.textContent = ((+w.value||0) + (+i.value||0)).toLocaleString(); }
	  w.addEventListener('input', calc); i.addEventListener('input', calc);
	})();
	</script>
	<p class="ymkrf-note" style="margin-top:14px">
	  ※ メーカー・商品カテゴリ・展示店舗は、画面右側の欄からチェックを入れてください。<br>
	  ※ 商品写真（いちばん大きく出るもの）は、右側の「アイキャッチ画像」に設定してください。<br>
	  ※ 「グレードUP／グレードを戻す」と「施工事例」は自動で出ます。入力は不要です。
	</p>
	<?php
}

/** 何行でも増やせる欄の画面 */
function ymkrf_product_box_repeater( $post, $key ) {
	$reps = ymkrf_product_repeaters();
	$def  = $reps[ $key ];
	$rows = get_post_meta( $post->ID, $key, true );
	if ( ! is_array( $rows ) ) $rows = array();

	if ( $def['note'] ) echo '<p class="ymkrf-note">' . esc_html( $def['note'] ) . '</p>';
	echo '<div class="ymkrf-rep" data-key="' . esc_attr( $key ) . '">';
	echo '<div class="ymkrf-rep__rows">';
	if ( $rows ) {
		foreach ( $rows as $n => $row ) ymkrf_product_row_html( $key, $def['cols'], $n, $row );
	} else {
		ymkrf_product_row_html( $key, $def['cols'], 0, array() );
	}
	echo '</div>';
	echo '<p><button type="button" class="button ymkrf-rep__add">＋ 行を追加</button></p>';
	echo '</div>';

	/* 「行を追加」で使うひな型 */
	echo '<script type="text/html" class="ymkrf-tpl-' . esc_attr( $key ) . '">';
	ymkrf_product_row_html( $key, $def['cols'], '__i__', array() );
	echo '</script>';
}

function ymkrf_product_row_html( $key, $cols, $n, $row ) {
	echo '<div class="ymkrf-row">';
	echo '<span class="ymkrf-row__handle" title="ドラッグで並べ替え">≡</span>';
	echo '<div class="ymkrf-row__body">';
	foreach ( $cols as $ck => $c ) {
		$name = sprintf( '%s[%s][%s]', $key, $n, $ck );
		$val  = isset( $row[ $ck ] ) ? $row[ $ck ] : '';
		echo '<label class="ymkrf-f"><span>' . esc_html( $c[0] ) . '</span>';
		if ( $c[1] === 'image' ) {
			$src = $val ? wp_get_attachment_image_url( (int) $val, 'thumbnail' ) : '';
			printf(
				'<span class="ymkrf-img">
				   <span class="ymkrf-img__prev">%s</span>
				   <input type="hidden" name="%s" value="%s">
				   <span class="ymkrf-img__btns">
				     <button type="button" class="button ymkrf-img__pick">写真を選ぶ</button>
				     <button type="button" class="button-link ymkrf-img__clear">消す</button>
				   </span>
				 </span>',
				$src ? '<img src="' . esc_url( $src ) . '" alt="">' : '',
				esc_attr( $name ), esc_attr( $val )
			);
		} elseif ( $c[1] === 'textarea' ) {
			printf( '<textarea name="%s" rows="2" placeholder="%s">%s</textarea>',
				esc_attr( $name ), esc_attr( isset( $c[2] ) ? $c[2] : '' ), esc_textarea( $val ) );
		} else {
			printf( '<input type="%s" name="%s" value="%s" placeholder="%s">',
				esc_attr( $c[1] ), esc_attr( $name ), esc_attr( $val ),
				esc_attr( isset( $c[2] ) ? $c[2] : '' ) );
		}
		echo '</label>';
	}
	echo '</div>';
	echo '<button type="button" class="button-link ymkrf-row__del" title="この行を消す">×</button>';
	echo '</div>';
}


/* ============================================================
   4. 保存
   ============================================================ */
add_action( 'save_post_ymkrf_product', function ( $post_id ) {
	if ( ! isset( $_POST['ymkrf_product_nonce'] ) ||
	     ! wp_verify_nonce( sanitize_key( $_POST['ymkrf_product_nonce'] ), 'ymkrf_product_save' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	foreach ( array_keys( ymkrf_product_fields() ) as $key ) {
		if ( ! isset( $_POST[ $key ] ) ) continue;
		update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
	}

	foreach ( ymkrf_product_repeaters() as $key => $def ) {
		$rows  = ( isset( $_POST[ $key ] ) && is_array( $_POST[ $key ] ) ) ? wp_unslash( $_POST[ $key ] ) : array();
		$clean = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) continue;
			$r = array();
			foreach ( $def['cols'] as $ck => $c ) {
				$v = isset( $row[ $ck ] ) ? $row[ $ck ] : '';
				$r[ $ck ] = ( $c[1] === 'textarea' )
					? sanitize_textarea_field( $v )
					: sanitize_text_field( $v );
			}
			/* すべて空の行は保存しない */
			if ( strlen( trim( implode( '', $r ) ) ) ) $clean[] = $r;
		}
		update_post_meta( $post_id, $key, $clean );
	}

	/* 込み価格を保存（並べ替えや絞り込みに使うため） */
	update_post_meta( $post_id, '_ymkrf_total',
		(int) get_post_meta( $post_id, '_ymkrf_work', true ) + (int) get_post_meta( $post_id, '_ymkrf_item', true ) );
} );


/* ============================================================
   5. 商品を複製する
      標準工事内容や標準仕様は商品ごとにほぼ同じなので、
      複製して直す方が、毎回入力するよりずっと早くなります。
      商品一覧で商品名にマウスを乗せると「複製して新規作成」が出ます。
   ============================================================ */
add_filter( 'post_row_actions', function ( $actions, $post ) {
	if ( $post->post_type === 'ymkrf_product' && current_user_can( 'edit_posts' ) ) {
		$url = wp_nonce_url(
			admin_url( 'admin.php?action=ymkrf_duplicate&post=' . $post->ID ), 'ymkrf_dup_' . $post->ID );
		$actions['ymkrf_dup'] = '<a href="' . esc_url( $url ) . '">複製して新規作成</a>';
	}
	return $actions;
}, 10, 2 );

add_action( 'admin_action_ymkrf_duplicate', function () {
	$id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0;
	if ( ! $id || ! current_user_can( 'edit_posts' ) ) wp_die( '権限がありません。' );
	check_admin_referer( 'ymkrf_dup_' . $id );

	$src = get_post( $id );
	if ( ! $src || $src->post_type !== 'ymkrf_product' ) wp_die( '商品が見つかりません。' );

	$new = wp_insert_post( array(
		'post_type'   => 'ymkrf_product',
		'post_title'  => $src->post_title . '（複製）',
		'post_status' => 'draft',
		'menu_order'  => $src->menu_order,
	) );
	if ( is_wp_error( $new ) ) wp_die( '複製できませんでした。' );

	foreach ( array( 'ymkrf_product_cat', 'ymkrf_maker', 'ymkrf_shop' ) as $tax ) {
		$ids = wp_get_object_terms( $id, $tax, array( 'fields' => 'ids' ) );
		if ( ! is_wp_error( $ids ) ) wp_set_object_terms( $new, $ids, $tax );
	}
	foreach ( get_post_meta( $id ) as $k => $v ) {
		if ( in_array( $k, array( '_edit_lock', '_edit_last' ), true ) ) continue;
		update_post_meta( $new, $k, maybe_unserialize( $v[0] ) );
	}
	wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $new ) );
	exit;
} );


/* ============================================================
   6. 一覧を見やすくする（管理画面）
   ============================================================ */
add_filter( 'manage_ymkrf_product_posts_columns', function ( $cols ) {

	/* いま分類でしぼっているかどうか（2026/09/01 ユーザー指示） */
	$cat = isset( $_GET['ymkrf_product_cat'] )
		? sanitize_title( wp_unslash( $_GET['ymkrf_product_cat'] ) ) : '';

	$new = array();
	foreach ( $cols as $k => $v ) {

		/* 分類でしぼっているときは「商品カテゴリ」の列は要りません。
		   給湯器だけを見ているのに「給湯器」と並ぶだけだからです。 */
		if ( $cat !== '' && $k === 'taxonomy-ymkrf_product_cat' ) continue;

		/* 給湯器・エコキュートは展示品ではないので「展示店舗」の列も出しません */
		if ( in_array( $cat, array( 'boiler', 'ecocute' ), true )
			&& $k === 'taxonomy-ymkrf_shop' ) continue;

		$new[ $k ] = $v;

		if ( $k === 'title' ) {

			/* 給湯器は、商品名ではなく型番が並ぶので見出しも変えます */
			if ( in_array( $cat, array( 'boiler', 'ecocute' ), true ) ) {
				$new['title'] = '型番';
			}

			/* エコキュートは列が多くなるので、写真は出しません
			   （2026/09/01 ユーザー指示） */
			if ( $cat !== 'ecocute' ) $new['ymkrf_thumb'] = '写真';

			/* 給湯器は4つの種類（ガス給湯器・エコジョーズ…）を出します */
			if ( $cat === 'boiler' ) $new['ymkrf_kind'] = '種類';

			/* エコキュートは、タンク容量とタイプのかわりに
			   補助金（国・福井県・石川県）と在庫を出します。
			   どれも社内で見るための欄で、お客様のページには出ません。 */
			if ( $cat === 'ecocute' ) {
				$new['ymkrf_hojo_k']    = '補助金';
				$new['ymkrf_stock']     = '在庫数';
				$new['ymkrf_stockshop'] = '在庫店舗';
			}

			if ( $cat === 'boiler' )      $new['ymkrf_grade'] = 'ふろ機能';
			elseif ( $cat !== 'ecocute' ) $new['ymkrf_grade'] = 'グレード';
			$new['ymkrf_price'] = '込み価格';
		}
	}
	return $new;
} );

add_action( 'manage_ymkrf_product_posts_custom_column', function ( $col, $post_id ) {
	if ( $col === 'ymkrf_thumb' ) {
		echo has_post_thumbnail( $post_id )
			? get_the_post_thumbnail( $post_id, array( 70, 70 ) )
			: '<span style="color:#c00">未設定</span>';
	}
	if ( $col === 'ymkrf_kind' ) {
		/* 給湯器の種類（キャッチコピーの欄に入っています） */
		$k = get_post_meta( $post_id, '_ymkrf_catch', true );
		echo $k ? esc_html( $k ) : '<span style="color:#c00">未設定</span>';
	}
	/* 補助金（国の補助金）。〇か—だけの、せまい列です。
	   対象にすると、商品ページに「補助金適用」と出ます。

	   ※福井県・石川県の補助金の列もいちど作りましたが、
	     ユーザー指示（2026/09/01）で外しました。 */
	if ( $col === 'ymkrf_hojo_k' ) {
		$v = get_post_meta( $post_id, '_ymkrf_hojo', true );
		if ( $v === '対象' ) {
			echo '<span style="color:#0a6b2d;font-size:17px;font-weight:700" title="補助金適用">〇</span>';
		} elseif ( $v === '対象外' ) {
			echo '<span style="color:#c7c7c7" title="対象外">—</span>';
		} else {
			echo '<span style="color:#c00;font-size:11px" title="まだ入っていません">未</span>';
		}
	}

	/* 在庫（社内用）。Gドライブの在庫確認シートを写したものです */
	if ( $col === 'ymkrf_stock' ) {
		$v = get_post_meta( $post_id, '_ymkrf_stock', true );
		$d = get_post_meta( $post_id, '_ymkrf_stockdate', true );
		if ( $v === '' || $v === null ) {
			echo '<span style="color:#a7aaad">—</span>';
		} else {
			$n = (int) $v;
			$c = $n >= 4 ? '#0a6b2d' : '#c00';
			echo '<strong style="color:' . $c . '">' . esc_html( number_format( $n ) ) . '台</strong>';
			if ( $d ) echo '<br><small style="color:#a7aaad">' . esc_html( $d ) . '現在</small>';
		}
	}
	if ( $col === 'ymkrf_stockshop' ) {
		$v = get_post_meta( $post_id, '_ymkrf_stockshop', true );
		echo $v
			? '<small>' . esc_html( str_replace( '／', ' / ', $v ) ) . '</small>'
			: '<span style="color:#a7aaad">—</span>';
	}
	if ( $col === 'ymkrf_grade' ) {
		$g = get_post_meta( $post_id, '_ymkrf_grade', true );
		echo $g ? esc_html( $g ) : '<span style="color:#c00">未設定</span>';
	}
	if ( $col === 'ymkrf_price' ) {
		$t = (int) get_post_meta( $post_id, '_ymkrf_total', true );
		echo $t ? esc_html( number_format( $t ) ) . ' 円' : '—';
	}
}, 10, 2 );

/* ------------------------------------------------------------
   グレードの序列

   「Jグレード」「Cグレード」「SSグレード」…という文字のままでは
   正しい順に並びません（文字の順だと C が E より先、SS が S より先に
   なってしまいます）。そこで数字に置きかえて持っておきます。
   数字が大きいほど上位のグレードです。

   同じ価格の商品が並んだとき、この数字で順番を決めます。
   （洗面化粧台の「Eグレード リジャスト」と「Cグレード K1」は
     どちらも179,800円のため、ここが無いと順番が入れかわります）
   ------------------------------------------------------------ */
if ( ! function_exists( 'ymkrf_grade_rank' ) ) :
function ymkrf_grade_rank( $text ) {
	$t = trim( (string) $text );
	if ( $t === '' ) return 999;
	if ( preg_match( '/premium|プレミアム/iu', $t ) ) return 130;
	if ( preg_match( '/(SSS|SS|S|A|B|C|D|E|F|G|H|I|J)\s*グレード/u', $t, $m ) ) {
		$map = array( 'J' => 10, 'I' => 20, 'H' => 30, 'G' => 40, 'F' => 50, 'E' => 60,
		              'D' => 70, 'C' => 80, 'B' => 90, 'A' => 100,
		              'S' => 110, 'SS' => 120, 'SSS' => 125 );
		return isset( $map[ $m[1] ] ) ? $map[ $m[1] ] : 999;
	}
	return 999;
}
endif;

/* グレードの数字を保存しなおします（商品を保存したときに自動で走ります） */
if ( ! function_exists( 'ymkrf_update_grade_sort' ) ) :
function ymkrf_update_grade_sort( $post_id ) {
	update_post_meta( $post_id, '_ymkrf_gsort',
		ymkrf_grade_rank( get_post_meta( $post_id, '_ymkrf_grade', true ) ) );
}
endif;
add_action( 'save_post_ymkrf_product', 'ymkrf_update_grade_sort', 20 );

/* 既にある商品にも1回だけ入れます（数字を上げると、もう一度だけ走ります） */
add_action( 'admin_init', function () {
	if ( get_option( 'ymkrf_gsort_ver' ) === '2' ) return;
	$ids = get_posts( array(
		'post_type' => 'ymkrf_product', 'posts_per_page' => -1,
		'fields' => 'ids', 'post_status' => 'any',
	) );
	foreach ( (array) $ids as $id ) ymkrf_update_grade_sort( $id );
	update_option( 'ymkrf_gsort_ver', '2' );
} );


/* 「グレード」「込み価格」の見出しをクリックで並べ替えられるようにします */
add_filter( 'manage_edit-ymkrf_product_sortable_columns', function ( $cols ) {
	$cols['ymkrf_grade'] = 'ymkrf_grade';
	$cols['ymkrf_price'] = 'ymkrf_price';
	return $cols;
} );

/**
 * 一覧の並び順。
 * ・見出しをクリックしていないときは「グレード順」
 *     J → I → H → G → F → E → D → C → B → A → S → SS → SSS → プレミアム
 *     同じグレードが2つ以上あるときは、込み価格の安い順です。
 * ・「グレード」「込み価格」の見出しをクリックしたら、その順
 * ・商品の編集画面で「並び順」に数字を入れると、その数字が優先されます
 *
 * meta_key で並べると価格が未入力の商品が一覧から消えてしまうため、
 * LEFT JOIN を自前で足しています（未入力の商品も必ず出ます）。
 */
add_filter( 'posts_clauses', function ( $clauses, $q ) {

	global $wpdb;

	/* 表示ページ側（分類ページ）からも同じ並び順を使えるようにしています。
	   'ymkrf_sort' => 'price' を付けた WP_Query が対象です。 */
	$front = ( $q->get( 'ymkrf_sort' ) === 'price' );

	if ( ! $front ) {
		if ( ! is_admin() || ! $q->is_main_query() ) return $clauses;
		if ( $q->get( 'post_type' ) !== 'ymkrf_product' ) return $clauses;
	}

	$by    = $q->get( 'orderby' );
	$order = ( strtoupper( (string) $q->get( 'order' ) ) === 'DESC' ) ? 'DESC' : 'ASC';

	if ( $front ) {
		$by = 'ymkrf_grade';   // 表示ページはいつもグレード順
		$order = 'ASC';
	} elseif ( ! $by ) {
		$by = 'ymkrf_grade';   // 見出しを押していないときの既定
		$order = 'ASC';
	} elseif ( ! in_array( $by, array( 'ymkrf_price', 'ymkrf_grade' ), true ) ) {
		return $clauses;       // 日付順・タイトル順などは、そのまま
	}

	/* グレードの序列（数字）と込み価格、どちらも使います */
	$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS ymkrf_gs"
	                  . " ON ( ymkrf_gs.post_id = {$wpdb->posts}.ID AND ymkrf_gs.meta_key = '_ymkrf_gsort' )";
	$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS ymkrf_od"
	                  . " ON ( ymkrf_od.post_id = {$wpdb->posts}.ID AND ymkrf_od.meta_key = '_ymkrf_order' )";
	$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS ymkrf_ot"
	                  . " ON ( ymkrf_ot.post_id = {$wpdb->posts}.ID AND ymkrf_ot.meta_key = '_ymkrf_total' )";

	/* 並び順に数字が入っていればそれを、無ければグレードの序列を使います */
	$gs = "CAST( COALESCE( NULLIF( ymkrf_od.meta_value, '' ), ymkrf_gs.meta_value, 999 ) AS SIGNED )";
	$pr = "CAST( COALESCE( NULLIF( ymkrf_ot.meta_value, '' ), 0 ) AS SIGNED )";

	/* エコキュートの一覧は「メーカーごと → その中は価格の安い順」にします。
	   （2026/09/01 ユーザー指示。ダッシュボードの一覧だけです）

	   ・メーカーは、エコキュートのページと同じ 三菱電機 → Panasonic → 日立 → ダイキン の順
	   ・価格が空の商品（下書き）は、そのメーカーのいちばん下に置きます
	   ・見出しを押したときは、これまでどおりその見出しの順になります */
	$eco_list = ( ! $front && ! $q->get( 'orderby' )
		&& isset( $_GET['ymkrf_product_cat'] )
		&& sanitize_title( wp_unslash( $_GET['ymkrf_product_cat'] ) ) === 'ecocute' );

	if ( $eco_list ) {

		$mk_order = array( 'mitsubishi', 'panasonic', 'hitachi', 'daikin' );
		$cases    = '';
		foreach ( $mk_order as $n => $mk ) {
			$t = get_term_by( 'slug', $mk, 'ymkrf_maker' );
			if ( $t && ! is_wp_error( $t ) ) {
				$cases .= $wpdb->prepare( ' WHEN %d THEN %d ', $t->term_id, $n );
			}
		}
		/* 表にないメーカーは、うしろにまわします。

		   ★MIN( ) で囲んでいるのが大事なところです。
		     1つの商品に「商品カテゴリ」「メーカー」「展示店舗」の行が
		     ぶらさがっているので、そのまま並べるとメーカー以外の行
		     （＝空っぽ）を見てしまい、ぜんぶ「99」になってしまいます。
		     いちばん小さい値を取れば、かならずメーカーの行が選ばれます。 */
		$mkexp = $cases ? "MIN( CASE ymkrf_mk.term_id {$cases} ELSE 99 END )" : '99';

		$clauses['join'] .= " LEFT JOIN {$wpdb->term_relationships} AS ymkrf_tr"
		                  . " ON ( ymkrf_tr.object_id = {$wpdb->posts}.ID )"
		                  . " LEFT JOIN {$wpdb->term_taxonomy} AS ymkrf_mk"
		                  . " ON ( ymkrf_mk.term_taxonomy_id = ymkrf_tr.term_taxonomy_id"
		                  . " AND ymkrf_mk.taxonomy = 'ymkrf_maker' )";

		/* 価格が空（＝0）の商品は、そのメーカーのいちばん下へ */
		$noprice = "CASE WHEN {$pr} > 0 THEN 0 ELSE 1 END";

		$clauses['groupby'] = "{$wpdb->posts}.ID";
		$clauses['orderby'] = "{$mkexp} ASC, {$noprice} ASC, {$pr} ASC, {$wpdb->posts}.post_title ASC";

		return $clauses;
	}

	if ( $by === 'ymkrf_price' ) {
		/* 「込み価格」の見出しを押したとき */
		$clauses['orderby'] = "{$pr} {$order}, {$gs} ASC, {$wpdb->posts}.post_title ASC";
	} else {
		/* 既定＝グレード順。同じグレードが並んだら、込み価格の安い順。
		   （文字のままだと C が E より先、SS が S より先になってしまうので数字で並べます） */
		$clauses['orderby'] = "{$gs} {$order}, {$pr} ASC, {$wpdb->posts}.post_title ASC";
	}

	return $clauses;
}, 10, 2 );


/* ------------------------------------------------------------
   6-b. 左メニューに「カテゴリ別の入口」を出す
        商品 → キッチン ／ お風呂 ／ トイレ … と直接開けるようにします。
        商品が1件も無いカテゴリは出しません（メニューが長くなるのを防ぐため）。
   ------------------------------------------------------------ */
add_action( 'admin_menu', function () {

	$terms = get_terms( array(
		'taxonomy'   => 'ymkrf_product_cat',
		'hide_empty' => true,
	) );
	if ( is_wp_error( $terms ) || ! $terms ) return;

	/* 並べる順は、商品一覧ページ（/products/）のカードと同じにします。
	   ここに無い分類は、うしろに付きます。 */
	$order = array( 'kitchen', 'bathroom', 'toilet', 'lavatory', 'boiler', 'ecocute',
	                'outer-wall', 'window', 'interior' );
	usort( $terms, function ( $a, $b ) use ( $order ) {
		$ia = array_search( $a->slug, $order, true );
		$ib = array_search( $b->slug, $order, true );
		if ( $ia === false ) $ia = 900;
		if ( $ib === false ) $ib = 900;
		if ( $ia === $ib ) return strcmp( $a->slug, $b->slug );
		return $ia - $ib;
	} );

	foreach ( $terms as $t ) {
		add_submenu_page(
			'edit.php?post_type=ymkrf_product',                       // 親メニュー
			$t->name . 'の商品',                                       // ページの見出し
			'　' . $t->name . '（' . $t->count . '）',                 // メニューに出る文字
			'edit_posts',
			'edit.php?post_type=ymkrf_product&ymkrf_product_cat=' . $t->slug
		);
	}
} );


/* ------------------------------------------------------------
   6-b2. 左メニューの並べかた（2026/09/01 ユーザー指示）

        商品
        　キッチン（10）
        　お風呂（9）
        　…（分類の数だけ）
        　その他設定        … 商品カテゴリ／メーカー／展示店舗をまとめた画面

        ・分類は「商品」のすぐ下に並べます。商品を押してから、
          左側で登録したい分類をえらべます。
        ・いちばん上には、見えない「商品（一覧）」が残っています。
          WordPress は「商品」を押したとき、いちばん上の項目を開くしくみなので、
          消すと「商品」を押しただけでキッチンが開いてしまいます。
          そのため、配列には残したまま画面上だけ隠しています（6-b5）。
        ・ふだん触らない3つは「その他設定」にまとめました
        ・分類が増えたときのために、商品一覧の上にも切り替えを出しています（6-c2）
   ------------------------------------------------------------ */

/* 「その他設定」の画面 */
if ( ! function_exists( 'ymkrf_product_settings_page' ) ) :
function ymkrf_product_settings_page() {

	$items = array(
		array(
			'name' => '商品カテゴリ',
			'url'  => 'edit-tags.php?taxonomy=ymkrf_product_cat&post_type=ymkrf_product',
			'text' => '「キッチン」「給湯器」といった分け方そのものを作る・直す画面です。'
			        . 'ここで作った分類は、商品一覧の上の切り替えにも自動で並びます。',
		),
		array(
			'name' => 'メーカー',
			'url'  => 'edit-tags.php?taxonomy=ymkrf_maker&post_type=ymkrf_product',
			'text' => '「ノーリツ」「LIXIL」などのメーカー名を作る・直す画面です。'
			        . '「説明」に書いた文章は、商品一覧のページに出ます。',
		),
		array(
			'name' => '展示店舗',
			'url'  => 'edit-tags.php?taxonomy=ymkrf_shop&post_type=ymkrf_product',
			'text' => '「金沢野々市店」など、展示のあるお店の選択肢を作る画面です。'
			        . 'どの商品をどのお店に置いているかは、商品ごとにチェックします。',
		),
	);
	?>
	<div class="wrap">
		<h1>その他設定</h1>
		<p class="description" style="margin:6px 0 18px;font-size:13px">
			商品を登録するときに使う「えらぶ項目」を用意しておく画面です。ふだんは触りません。
		</p>
		<div style="display:grid;gap:12px;max-width:760px">
			<?php foreach ( $items as $it ) : ?>
				<div class="postbox" style="padding:16px 18px">
					<h2 style="margin:0 0 6px;font-size:15px">
						<a href="<?php echo esc_url( admin_url( $it['url'] ) ); ?>"><?php
							echo esc_html( $it['name'] ); ?></a>
					</h2>
					<p style="margin:0;color:#50575e;font-size:13px;line-height:1.8"><?php
						echo esc_html( $it['text'] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}
endif;

add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=ymkrf_product',
		'その他設定',
		'その他設定',
		'manage_categories',
		'ymkrf-product-settings',
		'ymkrf_product_settings_page'
	);
}, 997 );

add_action( 'admin_menu', function () {

	global $submenu;
	$key = 'edit.php?post_type=ymkrf_product';
	if ( empty( $submenu[ $key ] ) ) return;

	$all  = array();   // すべての商品
	$cats = array();   // カテゴリ別の入口
	$sets = array();   // その他設定
	foreach ( $submenu[ $key ] as $row ) {
		if ( ! isset( $row[2] ) ) continue;
		/* 分類をつくる3つの画面は「その他設定」にまとめたので出しません */
		if ( strpos( $row[2], 'edit-tags.php' ) === 0 ) continue;
		/* 新規追加は、分類から入る形にしたので出しません */
		if ( strpos( $row[2], 'post-new.php' ) === 0 ) continue;

		if ( $row[2] === $key ) {
			/* ★この項目は消してはいけません。
			     WordPress は「商品」を押したとき、いちばん上の項目を開くしくみなので、
			     これが無いと「商品」を押しただけでキッチンが開いてしまいます。
			     見た目には要らないので、下の「6-b5」で隠しています。 */
			$all[] = $row;
		} elseif ( strpos( $row[2], 'ymkrf_product_cat=' ) !== false ) {
			$cats[] = $row;
		} else {
			$sets[] = $row;
		}
	}
	$submenu[ $key ] = array_merge( $all, $cats, $sets );
}, 999 );


/* ------------------------------------------------------------
   6-b4b. 分類をえらんだとき、左メニューのその分類に色を付けます
          （2026/09/01 ユーザー指摘「給湯器をえらんでも見た目が変わらない」）

          WordPress は、URL にうしろの文字（?ymkrf_product_cat=boiler）が
          付いていると「いまここ」だと気づいてくれないので、教えてあげます。
   ------------------------------------------------------------ */
add_filter( 'submenu_file', function ( $file, $parent_file ) {

	if ( $parent_file !== 'edit.php?post_type=ymkrf_product' ) return $file;

	$slug = '';

	/* ① 一覧を分類でしぼっているとき */
	if ( ! empty( $_GET['ymkrf_product_cat'] ) ) {
		$slug = sanitize_title( wp_unslash( $_GET['ymkrf_product_cat'] ) );
	}

	/* ② 商品を1つ開いているとき（新規作成もふくむ） */
	if ( $slug === '' && isset( $GLOBALS['pagenow'] )
		&& in_array( $GLOBALS['pagenow'], array( 'post.php', 'post-new.php' ), true ) ) {

		if ( ! empty( $_GET['ymkrf_cat'] ) ) {
			$slug = sanitize_title( wp_unslash( $_GET['ymkrf_cat'] ) );
		} elseif ( ! empty( $GLOBALS['post'] ) && $GLOBALS['post']->post_type === 'ymkrf_product' ) {
			$ts = get_the_terms( $GLOBALS['post']->ID, 'ymkrf_product_cat' );
			if ( $ts && ! is_wp_error( $ts ) ) $slug = $ts[0]->slug;
		}
	}

	if ( $slug === '' ) return $file;
	return 'edit.php?post_type=ymkrf_product&ymkrf_product_cat=' . $slug;
}, 10, 2 );

/* 「いまここ」がひと目で分かるように、水色の帯を付けます */
add_action( 'admin_head', function () {
	?>
	<style>
	#menu-posts-ymkrf_product .wp-submenu li.current a,
	#menu-posts-ymkrf_product .wp-submenu a.current{
		background:#a7d8f5; color:#0a2540; border-radius:3px; font-weight:700;
	}
	#menu-posts-ymkrf_product .wp-submenu li.current a:hover,
	#menu-posts-ymkrf_product .wp-submenu a.current:hover{
		background:#8ccbf2; color:#0a2540;
	}
	</style>
	<?php
} );


/* ------------------------------------------------------------
   6-b5. 左メニューの「商品」（一覧への入口）は、画面上だけ隠します
        （2026/09/01 ユーザー指示「左のすべては不要」）

        消してしまうと「商品」を押したときにキッチンが開いてしまうので、
        中身は残したまま、見た目だけ隠しています。
   ------------------------------------------------------------ */
add_action( 'admin_footer', function () {
	?>
	<script>
	(function () {
		var ul = document.querySelector('#menu-posts-ymkrf_product .wp-submenu');
		if (!ul) return;
		var a = ul.querySelector('a[href$="edit.php?post_type=ymkrf_product"]');
		var li = a && a.closest ? a.closest('li') : null;
		if (li && !li.classList.contains('wp-submenu-head')) li.style.display = 'none';
	})();
	</script>
	<?php
} );


/* ------------------------------------------------------------
   6-b3. 商品の追加は「分類から」に統一します（2026/09/01 ユーザー指示）

        左メニューの「商品を追加」をなくし、
        キッチン・給湯器などの分類を開いてから追加する形にしました。
        分類から入ると、その分類が最初からチェックされた状態で始まるので、
        入力する欄も最初からその分類のものだけになります。
   ------------------------------------------------------------ */
add_action( 'admin_menu', function () {
	remove_submenu_page( 'edit.php?post_type=ymkrf_product', 'post-new.php?post_type=ymkrf_product' );
}, 998 );

add_action( 'admin_footer', function () {

	$s = get_current_screen();
	if ( ! $s || $s->post_type !== 'ymkrf_product' ) return;

	/* ---- 商品一覧の「新規追加」ボタン ---- */
	if ( $s->base === 'edit' ) {

		$slug = isset( $_GET['ymkrf_product_cat'] )
			? sanitize_title( wp_unslash( $_GET['ymkrf_product_cat'] ) ) : '';
		$name = '';
		if ( $slug ) {
			$t = get_term_by( 'slug', $slug, 'ymkrf_product_cat' );
			if ( $t && ! is_wp_error( $t ) ) $name = $t->name;
		}
		?>
		<script>
		(function () {
			var btn = document.querySelector('.wrap .page-title-action');
			if (!btn) return;
			var slug = <?php echo wp_json_encode( $slug ); ?>;
			var name = <?php echo wp_json_encode( $name ); ?>;

			if (slug && name) {
				btn.href = 'post-new.php?post_type=ymkrf_product&ymkrf_cat=' + encodeURIComponent(slug);
				btn.textContent = name + 'に商品を追加';
			} else {
				/* 分類を選んでいないときは、追加ボタンを出しません */
				btn.style.display = 'none';
				var p = document.createElement('p');
				p.className = 'description';
				p.style.cssText = 'margin:8px 0 0;font-size:13px';
				p.textContent = '商品を追加するときは、上の「分類」からキッチン・給湯器などをえらんでから'
				              + '「○○に商品を追加」を押してください。分類が最初から入った状態ではじめられます。';
				btn.parentNode.insertBefore(p, btn.nextSibling);
			}
		})();
		</script>
		<?php
		return;
	}

	/* ---- 新規作成のとき、分類を最初からチェックしておく ---- */
	if ( $s->base === 'post' && $s->action === 'add' ) {
		$slug = isset( $_GET['ymkrf_cat'] ) ? sanitize_title( wp_unslash( $_GET['ymkrf_cat'] ) ) : '';
		if ( ! $slug ) return;
		$t = get_term_by( 'slug', $slug, 'ymkrf_product_cat' );
		if ( ! $t || is_wp_error( $t ) ) return;
		?>
		<script>
		(function () {
			var id  = <?php echo (int) $t->term_id; ?>;
			var box = document.getElementById('ymkrf_product_catchecklist');
			if (!box) return;
			var cb = box.querySelector('input[value="' + id + '"]');
			if (!cb || cb.checked) return;
			cb.checked = true;
			/* 入力欄のしぼり込みにも知らせます */
			box.dispatchEvent(new Event('change', { bubbles: true }));
		})();
		</script>
		<?php
	}
} );



/* ------------------------------------------------------------
   6-b4. 画面上の「＋新規 → 商品」も、分類を選んでから（2026/09/01 ユーザー指示）

        黒い帯の「＋新規」から商品を作ると、分類が入らないまま
        始まってしまいます。そこで「商品」にマウスを乗せると
        キッチン・給湯器…と分類が出るようにしました。
        えらんだ分類は、最初からチェックされた状態ではじまります。
   ------------------------------------------------------------ */
add_action( 'admin_bar_menu', function ( $bar ) {

	if ( ! $bar->get_node( 'new-ymkrf_product' ) ) return;
	if ( ! current_user_can( 'edit_posts' ) ) return;

	$terms = get_terms( array(
		'taxonomy'   => 'ymkrf_product_cat',
		'hide_empty' => false,
	) );
	if ( is_wp_error( $terms ) || ! $terms ) return;

	/* 並べる順は、左メニューや商品一覧ページと同じにします */
	$order = array( 'kitchen', 'bathroom', 'toilet', 'lavatory', 'boiler', 'ecocute',
	                'outer-wall', 'window', 'interior' );
	usort( $terms, function ( $a, $b ) use ( $order ) {
		$ia = array_search( $a->slug, $order, true );
		$ib = array_search( $b->slug, $order, true );
		if ( $ia === false ) $ia = 900;
		if ( $ib === false ) $ib = 900;
		if ( $ia === $ib ) return strcmp( $a->slug, $b->slug );
		return $ia - $ib;
	} );

	/* 「商品」そのものは、押しても進まないようにします（分類を選んでもらうため） */
	$bar->add_node( array(
		'id'     => 'new-ymkrf_product',
		'parent' => 'new-content',
		'title'  => '商品',
		'href'   => false,
		'meta'   => array( 'title' => '分類をえらんでください' ),
	) );

	foreach ( $terms as $t ) {
		$bar->add_node( array(
			'id'     => 'new-ymkrf_product-' . $t->slug,
			'parent' => 'new-ymkrf_product',
			'title'  => $t->name,
			'href'   => admin_url( 'post-new.php?post_type=ymkrf_product&ymkrf_cat=' . $t->slug ),
		) );
	}
}, 99 );



/* ------------------------------------------------------------
   6-c2. 商品一覧の上に「分類」の切り替えを出す（2026/09/01 ユーザー指示）

        左メニューに分類を並べると、分類が増えるたびに縦に長くなります。
        そこで、WordPress の「すべて｜公開済み｜ゴミ箱」と同じ行に、
        分類も横並びで出すようにしました。

        商品が1件も無い分類は出しません。
   ------------------------------------------------------------ */
add_filter( 'views_edit-ymkrf_product', function ( $views ) {

	$terms = get_terms( array(
		'taxonomy'   => 'ymkrf_product_cat',
		'hide_empty' => true,
	) );
	if ( is_wp_error( $terms ) || ! $terms ) return $views;

	/* 並べる順は、商品一覧ページ（/products/）のカードと同じ */
	$order = array( 'kitchen', 'bathroom', 'toilet', 'lavatory', 'boiler', 'ecocute',
	                'outer-wall', 'window', 'interior' );
	usort( $terms, function ( $a, $b ) use ( $order ) {
		$ia = array_search( $a->slug, $order, true );
		$ib = array_search( $b->slug, $order, true );
		if ( $ia === false ) $ia = 900;
		if ( $ib === false ) $ib = 900;
		if ( $ia === $ib ) return strcmp( $a->slug, $b->slug );
		return $ia - $ib;
	} );

	/* 「所有」（自分が登録したもの）は、まぎらわしいので出しません。
	   一括登録で入れた商品は作った人が別扱いになり、数字に意味がないためです。
	   （2026/09/01 ユーザー指示） */
	unset( $views['mine'] );

	/* WordPress は0件の状態を出しません。
	   「下書き」「ゴミ箱」は、0件のときも出しておきます。
	   （2026/09/01 ユーザー指示。いつも同じ場所にあるほうが分かりやすいため） */
	$cnt  = wp_count_posts( 'ymkrf_product' );
	$stat = isset( $_GET['post_status'] ) ? sanitize_key( wp_unslash( $_GET['post_status'] ) ) : '';
	$plain = admin_url( 'edit.php?post_type=ymkrf_product' );

	foreach ( array( 'draft' => '下書き', 'trash' => 'ゴミ箱' ) as $k => $label ) {
		if ( ! empty( $views[ $k ] ) ) continue;
		$views[ $k ] = sprintf(
			'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
			esc_url( add_query_arg( 'post_status', $k, $plain ) ),
			$stat === $k ? ' class="current" aria-current="page"' : '',
			esc_html( $label ),
			isset( $cnt->$k ) ? (int) $cnt->$k : 0
		);
	}

	/* 並べる順をそろえます：すべて → 公開済み → 下書き → ゴミ箱 */
	$sorted = array();
	foreach ( array( 'all', 'publish', 'draft', 'trash' ) as $k ) {
		if ( isset( $views[ $k ] ) ) { $sorted[ $k ] = $views[ $k ]; unset( $views[ $k ] ); }
	}
	$views = array_merge( $sorted, $views );

	$cur  = isset( $_GET['ymkrf_product_cat'] )
		? sanitize_title( wp_unslash( $_GET['ymkrf_product_cat'] ) ) : '';
	$base = admin_url( 'edit.php?post_type=ymkrf_product' );

	/* 分類でしぼっているときは、WordPress側の「すべて」を太字にしません */
	if ( $cur !== '' ) {
		foreach ( $views as $k => $v ) {
			$views[ $k ] = str_replace( ' class="current" aria-current="page"', '', $v );
		}
	}

	$views['ymkrf-catlabel'] = '<b style="color:#1d2327">分類</b>';

	foreach ( $terms as $t ) {
		$views[ 'ymkrf-cat-' . $t->slug ] = sprintf(
			'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
			esc_url( add_query_arg( 'ymkrf_product_cat', $t->slug, $base ) ),
			$cur === $t->slug ? ' class="current" aria-current="page"' : '',
			esc_html( $t->name ),
			(int) $t->count
		);
	}

	return $views;
} );


/* ------------------------------------------------------------
   6-c. 商品一覧の上に「カテゴリで絞り込む」プルダウンを出す
   ------------------------------------------------------------ */
add_action( 'restrict_manage_posts', function ( $post_type ) {
	if ( $post_type !== 'ymkrf_product' ) return;

	$tax = 'ymkrf_product_cat';
	$cur = isset( $_GET[ $tax ] ) ? sanitize_text_field( wp_unslash( $_GET[ $tax ] ) ) : '';

	wp_dropdown_categories( array(
		'taxonomy'        => $tax,
		'name'            => $tax,
		'value_field'     => 'slug',      // URLにスラッグを渡す（IDではなく）
		'show_option_all' => 'すべてのカテゴリ',
		'selected'        => $cur,
		'hierarchical'    => true,
		'hide_empty'      => false,
		'orderby'         => 'name',
		'show_count'      => true,
	) );
} );


/* ============================================================
   7. テンプレートから呼び出すための関数
   ============================================================ */
if ( ! function_exists( 'ymkrf_product_data' ) ) :
function ymkrf_product_data( $post_id = null ) {
	$post_id = $post_id ?: get_the_ID();
	$m   = function ( $k ) use ( $post_id ) { return get_post_meta( $post_id, $k, true ); };
	/* 何行でも増やせる欄。
	   古い商品や、あとから列が増えた欄でも「Undefined array key」が出ないよう、
	   定義にある列をすべて空文字で埋めてから返します。 */
	$defs = function_exists( 'ymkrf_product_repeaters' ) ? ymkrf_product_repeaters() : array();
	$rep = function ( $k ) use ( $post_id, $defs ) {
		$v = get_post_meta( $post_id, $k, true );
		if ( ! is_array( $v ) ) return array();
		$cols = isset( $defs[ $k ]['cols'] ) ? array_keys( $defs[ $k ]['cols'] ) : array();
		if ( ! $cols ) return $v;
		$blank = array_fill_keys( $cols, '' );
		$out   = array();
		foreach ( $v as $row ) {
			if ( ! is_array( $row ) ) continue;
			$out[] = array_merge( $blank, $row );
		}
		return $out;
	};
	$terms = function ( $tax ) use ( $post_id ) {
		$t = get_the_terms( $post_id, $tax );
		return ( $t && ! is_wp_error( $t ) ) ? $t : array();
	};

	$work = (int) $m( '_ymkrf_work' );
	$item = (int) $m( '_ymkrf_item' );

	/* 「タイプ」を付けるかどうかは分類で変わるので、先に分類を出しておきます */
	$cats = $terms( 'ymkrf_product_cat' );
	$cat1 = $cats ? $cats[0]->slug : '';

	return array(
		'catch'    => $m( '_ymkrf_catch' ),
		'grade'    => ymkrf_grade_label( $m( '_ymkrf_grade' ), $cat1 ),
		'name'     => $m( '_ymkrf_name' ) ?: get_the_title( $post_id ),
		'size'     => $m( '_ymkrf_size' ),
		'sub'      => $m( '_ymkrf_sub' ),
		'work'     => $work,
		'item'     => $item,
		'total'    => $work + $item,
		'days'     => $m( '_ymkrf_days' ),
		'daystext' => $m( '_ymkrf_daystext' ),
		'points'   => array_values( array_filter( array( $m('_ymkrf_pt1'), $m('_ymkrf_pt2'), $m('_ymkrf_pt3') ) ) ),
		'caution'  => $m( '_ymkrf_caution' ),
		/* 給湯器・エコキュートの基本仕様（2026/09/01 追加） */
		'dim'      => $m( '_ymkrf_dim' ),
		'weight'   => $m( '_ymkrf_weight' ),
		'exterior' => $m( '_ymkrf_exterior' ),
		'pressure' => $m( '_ymkrf_pressure' ),
		'power'    => $m( '_ymkrf_power' ),
		'powerunit'=> $m( '_ymkrf_power_unit' ),
		/* エコキュートの基本仕様（2026/09/01 追加） */
		'tank'      => $m( '_ymkrf_tank' ),
		'people'    => $m( '_ymkrf_people' ),
		'accessory' => $m( '_ymkrf_accessory' ),
		'remote'    => $m( '_ymkrf_remote' ),
		'hojo'      => $m( '_ymkrf_hojo' ),
		'images'   => $rep( '_ymkrf_images' ),
		'colors'   => $rep( '_ymkrf_colors' ),
		'tops'     => $rep( '_ymkrf_tops' ),
		'sinks'    => $rep( '_ymkrf_sinks' ),
		'c4'       => $rep( '_ymkrf_c4' ),
		'c5'       => $rep( '_ymkrf_c5' ),
		'c6'       => $rep( '_ymkrf_c6' ),
		'handles'  => $rep( '_ymkrf_handles' ),
		'specs'    => $rep( '_ymkrf_specs' ),
		'speclist' => $rep( '_ymkrf_speclist' ),
		'features' => $rep( '_ymkrf_features' ),
		'options'  => $rep( '_ymkrf_options' ),
		'works'    => $rep( '_ymkrf_works' ),
		'makers'   => $terms( 'ymkrf_maker' ),
		'cats'     => $cats,
		'shops'    => $terms( 'ymkrf_shop' ),
	);
}
endif;

/**
 * 商品代＋標準工事費の内訳（カテゴリごと）。
 *
 * 一覧ページ（taxonomy-ymkrf_product_cat.php）と
 * 商品ページ（single-ymkrf_product.php）の両方から使うので、
 * ここ1か所にまとめています。直すときはここだけ直せば両方に効きます。
 *
 *   label    … 標準工事費の見出し
 *   price    … 標準工事費（円）
 *   note     … 下に出す説明
 *   note2    … さらに小さい但し書き
 *   itemsttl … 工事の一覧の見出し（| はスマホでの改行位置）
 *   items    … name … 工事名／sub … 説明（省略可）／icon … ymkrf_work_icon() の名前
 */
if ( ! function_exists( 'ymkrf_pointnote' ) ) :
function ymkrf_pointnote( $slug ) {
	$d = array(
		'kitchen' => array(
				'label' => 'キッチンの標準工事費',
				'price' => 240000,
				'note'  => 'キッチンの標準工事費は、どの機種も一律同価格です。',
				/* note の下に、さらに小さく出す但し書き */
				'note2' => '※お家の形状により追加がかかる場合は、お見積りの際に詳細をお伝えさせていただきます。',
				/* | は「スマホではここで改行」の目印です */
				'itemsttl' => 'リフォームヤマキシの|標準工事費にふくまれる工事',
				/* name … 工事名／sub … 説明（省略可）／icon … 下の ymkrf_work_icon() の名前 */
				'items' => array(
					array( 'name' => '既存流し台解体撤去工事', 'icon' => 'hammer',
					       'sub'  => '古いキッチンの撤去にかかる工事' ),
					array( 'name' => '養生工事',             'icon' => 'sheet',
					       'sub'  => '床・壁・下地を保護します' ),
					array( 'name' => '産業廃棄物処理運輸工事', 'icon' => 'truck',
					       'sub'  => '撤去した古いキッチンを廃棄処分するためにかかる費用' ),
					array( 'name' => '水道工事',             'icon' => 'water',
					       'sub'  => '給水・給湯・排水' ),
					array( 'name' => '電気工事',             'icon' => 'bolt',
					       'sub'  => '設備機器の配線接続等' ),
					array( 'name' => 'ガス配管変更工事',     'icon' => 'flame',
					       'sub'  => 'ガスコンロを使うための配管工事' ),
					array( 'name' => 'キッチンパネル設置工事', 'icon' => 'grid',
					       'sub'  => 'キッチンパネル部材費込み施工いたします' ),
					array( 'name' => '下地工事',             'icon' => 'wall',
					       'sub'  => '大工工事。キッチンパネル設置面の補修、補強' ),
					array( 'name' => 'システムキッチン取付設置', 'icon' => 'kitchen',
					       'sub'  => '新しいシステムキッチンの取り付け・設置' ),
					array( 'name' => 'シロッコファン取付工事', 'icon' => 'fan',
					       'sub'  => 'シロッコファンの取付工事' ),
				),
		),
		'bathroom' => array(
				'label' => 'お風呂の標準工事費',
				'price' => 370000,
				'note'  => 'お風呂の標準工事費は、どの機種も一律同価格です。',
				'note2' => '※お家の形状により追加がかかる場合は、お見積りの際に詳細をお伝えさせていただきます。',
				'itemsttl' => 'リフォームヤマキシの|標準工事費にふくまれる工事',
				'items' => array(
					array( 'name' => '既存ユニットバス解体撤去工事', 'icon' => 'hammer',
					       'sub'  => '古い浴槽の撤去にかかる工事' ),
					array( 'name' => '産業廃棄物処理運搬工事', 'icon' => 'truck',
					       'sub'  => '撤去した浴槽などを廃棄処分するためにかかる費用' ),
					array( 'name' => '水道工事',       'icon' => 'water',
					       'sub'  => '給水・給湯・排水' ),
					array( 'name' => '電気工事',       'icon' => 'bolt',
					       'sub'  => '配線' ),
					array( 'name' => '木工事',         'icon' => 'saw',
					       'sub'  => '脱衣所の壁下地をつくる工事' ),
					array( 'name' => 'ユニットバス組立設置', 'icon' => 'bath',
					       'sub'  => '' ),
					array( 'name' => '浴室壁面造作・内装工事', 'icon' => 'wall',
					       'sub'  => '脱衣場側の壁面の造作と、クロス・サニタリーボードなどの内装' ),
					array( 'name' => '換気扇取付工事', 'icon' => 'fan',
					       'sub'  => '換気扇の取り付け工事' ),
					array( 'name' => '浴室ドア枠造作工事', 'icon' => 'door',
					       'sub'  => '浴室のドア枠を造作します' ),
				),
		),
		'toilet' => array(
				'label' => 'トイレの標準工事費',
				'price' => 38000,
				'note'  => 'トイレの標準工事費は38,000円（税込）です。手洗いカウンター付きは53,000円（税込）になります。',
				'note2' => '※お家の形状により追加がかかる場合は、お見積りの際に詳細をお伝えさせていただきます。',
				'itemsttl' => 'リフォームヤマキシの|標準工事費にふくまれる工事',
				'items' => array(
					array( 'name' => '既存トイレ解体撤去工事', 'icon' => 'hammer',
					       'sub'  => '古い便器・便座の取り外しと撤去にかかる工事' ),
					array( 'name' => '水道工事',   'icon' => 'water',
					       'sub'  => '給水・排水' ),
					array( 'name' => 'トイレ設置工事', 'icon' => 'toilet',
					       'sub'  => '新しい便器・便座の取り付け' ),
				),
		),
		/* 給湯器は「工事費・リモコン込」の一本価格でご案内しているので、
		   標準工事費の金額（price）は入れていません。
		   price が空のときは、金額のカードを出さずに
		   「ふくまれる工事」の一覧だけを出します。 */
		'boiler' => array(
				'label' => '給湯器の標準工事費',
				'price' => 0,
				/* nocalc … 金額のカード（商品代＋標準工事費）を出しません */
				'nocalc' => true,
				'note'  => '給湯器の価格は、本体・標準工事費・リモコン・古い給湯器の撤去処分まで込みの価格です。',
				'note2' => '※お住まいの形や、配管・電気・ガスの状態によっては、追加の工事が必要になることがあります。'
				         . 'その場合も着工前にかならずお見積りをお出しし、ご了承をいただいてから進めます。',
				'itemsttl' => 'リフォームヤマキシの|標準工事費にふくまれる工事',
				'items' => array(
					array( 'name' => '既存給湯器 解体撤去工事', 'icon' => 'hammer',
					       'sub'  => '古い給湯器の取り外しにかかる工事' ),
					array( 'name' => '撤去・処分',             'icon' => 'truck',
					       'sub'  => '取り外した古い給湯器を廃棄処分するための費用' ),
					array( 'name' => '給湯器設置工事',         'icon' => 'flame',
					       'sub'  => '新しい給湯器の取り付け工事' ),
					array( 'name' => '配管工事',               'icon' => 'water',
					       'sub'  => '給水・給湯・追い焚きなどの配管の接続' ),
				),
		),
		/* エコキュートも給湯器と同じく、工事費込みの一本価格でご案内しています。
		   PDF（本番サイトの /products/ecocute/）の
		   【ヤマキシのエコキュート標準工事に含まれる工事】4項目です。 */
		'ecocute' => array(
				'label' => 'エコキュートの標準工事費',
				'price' => 0,
				/* nocalc … 金額のカード（商品代＋標準工事費）を出しません */
				'nocalc' => true,
				'note'  => 'エコキュートの価格は、本体・標準工事費・リモコン・古い機器の撤去処分まで込みの価格です。'
				         . '北陸電力への申請作業もふくまれています。',
				'note2' => '※価格は、電気温水器またはエコキュートからのお取り替えの場合です。'
				         . 'それ以外の給湯器からのお取り替えは、別途お見積りをお出しします。'
				         . '※お住まいの形や、配管・電気の状態によっては、追加の工事が必要になることがあります。'
				         . 'その場合も着工前にかならずお見積りをお出しし、ご了承をいただいてから進めます。',
				'itemsttl' => 'リフォームヤマキシの|標準工事費にふくまれる工事',
				'items' => array(
					array( 'name' => '既存給湯器 解体撤去工事', 'icon' => 'hammer',
					       'sub'  => '古い電気温水器・エコキュートの取り外しと処分にかかる工事' ),
					array( 'name' => '水道工事',               'icon' => 'water',
					       'sub'  => '給水・給湯・排水' ),
					array( 'name' => '電気工事',               'icon' => 'bolt',
					       'sub'  => '配線。北陸電力への申請作業もふくみます' ),
					array( 'name' => 'エコキュート設置工事',   'icon' => 'flame',
					       'sub'  => '新しいエコキュート本体とヒートポンプの取り付け工事' ),
				),
		),
		'lavatory' => array(
				'label' => '洗面化粧台の標準工事費',
				'price' => 24200,
				'note'  => '洗面化粧台の標準工事費は24,200円（税込）です。',
				'note2' => '※お家の形状により追加がかかる場合は、お見積りの際に詳細をお伝えさせていただきます。',
				'itemsttl' => 'リフォームヤマキシの|標準工事費にふくまれる工事',
				'items' => array(
					array( 'name' => '既存洗面化粧台解体撤去工事', 'icon' => 'hammer',
					       'sub'  => '古い洗面化粧台の取り外しと撤去にかかる工事' ),
					array( 'name' => '水道工事',   'icon' => 'water',
					       'sub'  => '給水・排水' ),
					array( 'name' => '洗面設置工事', 'icon' => 'sink',
					       'sub'  => '新しい洗面化粧台の取り付け・設置' ),
				),
		),
	);
	return isset( $d[ $slug ] ) ? $d[ $slug ] : array();
}
endif;

/* 工事費内訳のアイコン。線画（stroke）で描いています */
if ( ! function_exists( 'ymkrf_work_icon' ) ) {
	function ymkrf_work_icon( $key ) {
		$d = array(
			'hammer' => '<path d="M14 3l7 7-3 3-7-7z"/><path d="M11 6L3 14l4 4 8-8"/>',
			'trash'  => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/><path d="M10 11v6M14 11v6"/>',
			'water'  => '<path d="M12 3s6 6.6 6 10.5A6 6 0 0 1 6 13.5C6 9.6 12 3 12 3z"/>',
			'bolt'   => '<path d="M13 2L4 14h7l-1 8 9-12h-7z"/>',
			'wrench' => '<path d="M21 4a5.5 5.5 0 0 1-7.4 7.4L5 20l-1-1 8.6-8.6A5.5 5.5 0 0 1 20 3z"/>',
			'saw'    => '<path d="M3 8h13l5 5-5 5H3z"/><path d="M3 8l2 3 2-3 2 3 2-3 2 3 2-3"/>',
			'box'    => '<path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
			'grid'   => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 12h18M9 4v16"/>',
			'sheet'  => '<path d="M12 3l9 5-9 5-9-5z"/><path d="M3 13l9 5 9-5"/>',
			'truck'  => '<path d="M3 6h11v10H3z"/><path d="M14 9.5h4l3 3.2V16h-7z"/>'
			          . '<circle cx="7.2" cy="18" r="2"/><circle cx="17.3" cy="18" r="2"/>',
			'wall'   => '<rect x="3" y="5" width="18" height="14" rx="1.5"/>'
			          . '<path d="M3 9.7h18M3 14.3h18M10 5v4.7M6.5 9.7v4.6M14 9.7v4.6M10 14.3V19"/>',
			'kitchen'=> '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18"/>'
			          . '<circle cx="8.5" cy="15" r="1.7"/><circle cx="15.5" cy="15" r="1.7"/>',
			'flame'  => '<path d="M12 2.6c2.6 3.2 5.5 5.3 5.5 9a5.5 5.5 0 0 1-11 0c0-2 1-3.5 2.2-4.7'
			          . '.3 1.2 1 2 1.9 2.4C10.2 7.2 11 4.8 12 2.6z"/>',
			'fan'    => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="2.2"/>'
			          . '<path d="M12 9.8c1.6-2.6 4.4-3.6 5.4-2.6s0 3.8-2.6 5.4"/>'
			          . '<path d="M12 14.2c-1.6 2.6-4.4 3.6-5.4 2.6s0-3.8 2.6-5.4"/>',
			'bath'   => '<path d="M4 11h17v3.5a4.5 4.5 0 0 1-4.5 4.5h-8A4.5 4.5 0 0 1 4 14.5z"/>'
			          . '<path d="M4 11V6.2A2.2 2.2 0 0 1 6.2 4c1 0 1.8.6 2.1 1.5"/>'
			          . '<path d="M6.5 19l-1 2M18 19l1 2"/>',
			'door'   => '<rect x="5" y="3" width="14" height="18" rx="1.5"/>'
			          . '<path d="M3 21h18"/><circle cx="15.4" cy="12" r="1"/>',
			'toilet' => '<path d="M5 4h4v5h9v3.2a6.8 6.8 0 0 1-6.8 6.8H11A6 6 0 0 1 5 13z"/>'
			          . '<path d="M9 19l-1 2M15.5 19l1 2"/><path d="M5 9h4"/>',
			/* 洗面化粧台。上が鏡、下が洗面ボウルとキャビネットです */
			'sink'   => '<rect x="7" y="2.6" width="10" height="6.4" rx="1"/>'
			          . '<path d="M12 9v2.6"/><path d="M4 12h16"/>'
			          . '<path d="M5.4 12v4.2A3.2 3.2 0 0 0 8.6 19.4h6.8A3.2 3.2 0 0 0 18.6 16.2V12"/>'
			          . '<path d="M7.5 19.4V21.4M16.5 19.4V21.4"/>',
		);
		if ( empty( $d[ $key ] ) ) return '';
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" '
		     . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d[ $key ] . '</svg>';
	}
}

/* ============================================================
   商品カテゴリへのリンク先

   ★ここが「リンクが外れる」問題の対策です。

   トップページやメニューには、まだ商品を入れていない分類
   （エコキュート・IH・玄関ドア…）へのボタンも並んでいます。
   これまでは /products/<分類>/ を直接書いていたので、
   その分類がまだ無いとき・中身が空のときに
   「ページが見つかりません」（404）になっていました。

   この関数を通すと、
     ・その分類があって、商品も入っている → その一覧ページへ
     ・まだ無い／中身が空                  → 商品一覧（/products/）へ
   と、かならずどこかに着地します。リンクが切れることはありません。

   使い方： <a href="<?php echo esc_url( ymkrf_cat_url( 'kitchen' ) ); ?>">
   ============================================================ */
if ( ! function_exists( 'ymkrf_products_url' ) ) :
function ymkrf_products_url() {
	$url = get_post_type_archive_link( 'ymkrf_product' );
	return $url ? $url : home_url( '/products/' );
}
endif;

/**
 * 商品ページへのリンク。まだその商品が無いときは商品一覧に着地します。
 * 4点パックのページから各商品へ飛ぶときに使っています。
 */
if ( ! function_exists( 'ymkrf_prd_url' ) ) :
function ymkrf_prd_url( $slug ) {
	$p = $slug ? get_page_by_path( $slug, OBJECT, 'ymkrf_product' ) : null;
	if ( ! $p ) return ymkrf_products_url();
	$url = get_permalink( $p );
	return $url ? $url : ymkrf_products_url();
}
endif;

if ( ! function_exists( 'ymkrf_cat_url' ) ) :
function ymkrf_cat_url( $slug ) {

	$slug = trim( (string) $slug );
	if ( $slug === '' ) return ymkrf_products_url();

	/* 水まわり4点パックだけは、分類ではなく専用ページです */
	if ( $slug === 'pack4' ) return home_url( '/products/pack4/' );

	$term = get_term_by( 'slug', $slug, 'ymkrf_product_cat' );

	/* 分類そのものが無い（＝これまで404になっていたところ） */
	if ( ! $term || is_wp_error( $term ) ) return ymkrf_products_url();

	/* 分類はあるが、商品がまだ1つも入っていない
	   （子分類に入れている場合もあるので、子の数もたします） */
	$count = (int) $term->count;
	if ( ! $count ) {
		$kids = get_terms( array(
			'taxonomy'   => 'ymkrf_product_cat',
			'child_of'   => $term->term_id,
			'hide_empty' => true,
			'fields'     => 'ids',
		) );
		if ( is_wp_error( $kids ) || ! $kids ) return ymkrf_products_url();
	}

	$url = get_term_link( $term );
	return is_wp_error( $url ) ? ymkrf_products_url() : $url;
}
endif;

/**
 * カテゴリの呼び名。コラム・施工事例の見出しなどに使います。
 * 「お風呂」だと硬いので、ページ上では「ユニットバス」と呼びます。
 */
if ( ! function_exists( 'ymkrf_cat_label' ) ) :
function ymkrf_cat_label( $slug, $fallback = '' ) {
	$map = array(
		'kitchen'  => 'キッチン',
		'bathroom' => 'ユニットバス',
		'toilet'   => 'トイレ',
		'lavatory' => '洗面化粧台',
		'boiler'   => '給湯器',
		'ecocute'  => 'エコキュート',
	);
	return isset( $map[ $slug ] ) ? $map[ $slug ] : $fallback;
}
endif;

/**
 * 商品一覧の見出し。カテゴリごとに呼び方を変えます。
 */
if ( ! function_exists( 'ymkrf_cat_listtitle' ) ) :
function ymkrf_cat_listtitle( $slug, $fallback = '商品' ) {
	$map = array(
		'kitchen'  => 'キッチンマルシェの商品一覧',
		'bathroom' => 'ユニットバス商品一覧',
		'toilet'   => 'トイレ商品一覧',
		'lavatory' => '洗面化粧台商品一覧',
	);
	return isset( $map[ $slug ] ) ? $map[ $slug ] : $fallback . 'の商品一覧';
}
endif;

/**
 * カテゴリごとのブランド名。「グレードUP」の見出しなどに使います。
 * ここに書いていないカテゴリは「〇〇マルシェ」になります。
 */
if ( ! function_exists( 'ymkrf_cat_brand' ) ) :
function ymkrf_cat_brand( $cat ) {
	if ( ! $cat || is_wp_error( $cat ) ) return '商品';
	$map = array(
		'kitchen'  => 'キッチンマルシェ',
		'bathroom' => 'ユニットバスリフォームパック',
		'toilet'   => 'トイレリフォームパック',
		'lavatory' => '洗面化粧台リフォームパック',
		'boiler'   => 'ヤマキシ給湯センター',
		'ecocute'  => 'ヤマキシ給湯センター',
	);
	return isset( $map[ $cat->slug ] ) ? $map[ $cat->slug ] : $cat->name . 'マルシェ';
}
endif;

/**
 * 同じカテゴリの中で、ひとつ下（安い）・ひとつ上（高い）のグレードを返します。
 * 「グレードを戻す／グレードUP」の表示に使います。入力は不要です。
 */
if ( ! function_exists( 'ymkrf_product_siblings' ) ) :
function ymkrf_product_siblings( $post_id = null ) {
	$post_id = $post_id ?: get_the_ID();
	$cats = wp_get_object_terms( $post_id, 'ymkrf_product_cat', array( 'fields' => 'ids' ) );
	if ( empty( $cats ) || is_wp_error( $cats ) ) return array( 'prev' => null, 'next' => null );

	$all = get_posts( array(
		'post_type'      => 'ymkrf_product',
		'posts_per_page' => -1,
		'meta_key'       => '_ymkrf_total',
		'orderby'        => 'meta_value_num',
		'order'          => 'ASC',
		'fields'         => 'ids',
		'tax_query'      => array( array(
			'taxonomy' => 'ymkrf_product_cat', 'field' => 'term_id', 'terms' => $cats,
		) ),
	) );
	$i = array_search( $post_id, $all, true );
	if ( $i === false ) return array( 'prev' => null, 'next' => null );

	return array(
		'prev' => $i > 0 ? $all[ $i - 1 ] : null,
		'next' => isset( $all[ $i + 1 ] ) ? $all[ $i + 1 ] : null,
	);
}
endif;

/**
 * この商品と同じ部位の施工事例を取り出します（自動）。
 * 施工事例側の「部位」（ymkrf_works_cat）に、商品カテゴリと同じ名前の項目を作っておいてください。
 */
if ( ! function_exists( 'ymkrf_product_works' ) ) :
function ymkrf_product_works( $post_id = null, $num = 3 ) {
	$post_id = $post_id ?: get_the_ID();
	$cats = get_the_terms( $post_id, 'ymkrf_product_cat' );
	if ( ! $cats || is_wp_error( $cats ) ) return array();

	$slugs = wp_list_pluck( $cats, 'slug' );
	$args  = array(
		'post_type'      => 'ymkrf_works',
		'posts_per_page' => $num,
		'fields'         => 'ids',
		'tax_query'      => array( array(
			'taxonomy' => 'ymkrf_works_cat', 'field' => 'slug', 'terms' => $slugs,
		) ),
	);
	$ids = get_posts( $args );
	return is_array( $ids ) ? $ids : array();
}
endif;


/**
 * 商品ページ専用のCSSを読み込みます。
 * common.css → page.css → product.css の順です。
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_singular( 'ymkrf_product' )
	  && ! is_tax( 'ymkrf_product_cat' )
	  && ! is_post_type_archive( 'ymkrf_product' )
	  && ! ymkrf_is_pack4() ) return;   // ← 商品一覧・4点パックでも使います
	wp_enqueue_style( 'ymkrf-product',
		get_stylesheet_directory_uri() . '/assets/css/product.css',
		array( 'ymkrf-common', 'ymkrf-page' ), defined( 'YMKRF_VER' ) ? YMKRF_VER : null );
}, 20 );


/**
 * お問い合わせボタン。文言を1か所で管理するため関数にしています。
 * 変えたいときはここだけ直せば、全ページに反映されます。
 */
if ( ! function_exists( 'ymkrf_product_cta' ) ) :
function ymkrf_product_cta( $place = 'product', $with_tel = false ) {
	$rsv  = esc_url( home_url( '/inquiry/webrsv/' ) );
	$line = 'https://lin.ee/UJZuSTrz';
	?>
	<div class="p-pagecta__btns" style="margin-top:26px">
	  <a class="c-btn c-btn--block" href="<?php echo $rsv; ?>" data-cta="<?php echo esc_attr( $place ); ?>">
	    <span class="c-btn__label">来店して現物を見る<span class="c-btn__sub">初回特典500円ヤマキシお買物券<br>※展示のない店舗もあります</span></span>
	  </a>
	  <a class="c-btn c-btn--line c-btn--block" href="<?php echo esc_url( $line ); ?>" rel="noopener" data-cta="<?php echo esc_attr( $place ); ?>">
	    <span class="c-btn__label">LINEで相談・見積もり<span class="c-btn__sub">ご相談だけでもOK・24時間受付</span></span>
	  </a>
	  <?php if ( $with_tel ) : ?>
	  <a class="c-btn c-btn--ghost c-btn--block" href="tel:0800-777-3331" data-cta="<?php echo esc_attr( $place ); ?>">
	    <span class="c-btn__label">0800-777-3331<span class="c-btn__sub">通話無料・受付 9:00〜17:00</span></span>
	  </a>
	  <?php endif; ?>
	</div>
	<?php
}
endif;


/* ============================================================
   8. 入力画面の見た目と動き
   ============================================================ */
function ymkrf_product_admin_assets() {
	ob_start(); ?>
<style>
.ymkrf-tbl{width:100%;border-collapse:collapse}
.ymkrf-tbl th{width:180px;text-align:left;padding:12px 10px;vertical-align:top;font-weight:700}
.ymkrf-tbl td{padding:10px}
.ymkrf-tbl tr+tr{border-top:1px solid #eee}
.ymkrf-tbl input{width:100%;max-width:420px}
.ymkrf-note{display:block;margin-top:4px;color:#777;font-size:12px;line-height:1.7}
.ymkrf-total{background:#fff4f0;border:2px solid #fe3301;border-radius:8px;padding:14px 16px;margin-top:16px;font-weight:700}
.ymkrf-total b{font-size:24px;color:#fe3301}

.ymkrf-row{display:flex;align-items:flex-start;gap:8px;background:#fafafa;border:1px solid #e0e0e0;
           border-radius:6px;padding:12px;margin-bottom:8px}
.ymkrf-row__handle{cursor:grab;color:#999;font-size:18px;line-height:1;padding:6px 2px;user-select:none}
.ymkrf-row__body{flex:1;display:grid;gap:10px;grid-template-columns:repeat(auto-fit,minmax(210px,1fr))}
.ymkrf-row__del{color:#b32d2e;font-size:18px;line-height:1;padding:4px 6px;text-decoration:none}
.ymkrf-f{display:block;font-size:12px}
.ymkrf-f>span{display:block;margin-bottom:3px;color:#555;font-weight:700}
.ymkrf-f input,.ymkrf-f textarea{width:100%}
.ymkrf-img{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.ymkrf-img__prev{width:56px;height:56px;flex:none;background:#fff;border:1px solid #ddd;border-radius:4px;
                 display:grid;place-items:center;overflow:hidden}
.ymkrf-img__prev img{max-width:100%;max-height:100%;display:block}
.ymkrf-img__btns{display:flex;flex-direction:column;gap:2px;align-items:flex-start}
.ymkrf-rep__add{margin-top:4px}
</style>
<script>
jQuery(function($){
  /* 行を追加 */
  $(document).on('click', '.ymkrf-rep__add', function(){
    var $rep = $(this).closest('.ymkrf-rep'), key = $rep.data('key');
    var uid  = 'n' + Date.now() + Math.floor(Math.random() * 1000);
    var html = $('.ymkrf-tpl-' + key).html().replace(/__i__/g, uid);
    $rep.find('.ymkrf-rep__rows').append(html);
  });

  /* 行を消す（最後の1行は中身だけ空にする） */
  $(document).on('click', '.ymkrf-row__del', function(){
    var $rows = $(this).closest('.ymkrf-rep__rows');
    if ($rows.find('.ymkrf-row').length <= 1) {
      var $r = $(this).closest('.ymkrf-row');
      $r.find('input,textarea').val('');
      $r.find('.ymkrf-img__prev').empty();
      return;
    }
    $(this).closest('.ymkrf-row').remove();
  });

  /* 写真を選ぶ */
  $(document).on('click', '.ymkrf-img__pick', function(){
    var $box  = $(this).closest('.ymkrf-img');
    var frame = wp.media({ title:'写真を選ぶ', button:{ text:'この写真にする' }, multiple:false });
    frame.on('select', function(){
      var a   = frame.state().get('selection').first().toJSON();
      var url = (a.sizes && a.sizes.thumbnail) ? a.sizes.thumbnail.url : a.url;
      $box.find('input[type=hidden]').val(a.id);
      $box.find('.ymkrf-img__prev').html('<img src="' + url + '" alt="">');
    });
    frame.open();
  });
  $(document).on('click', '.ymkrf-img__clear', function(){
    var $box = $(this).closest('.ymkrf-img');
    $box.find('input[type=hidden]').val('');
    $box.find('.ymkrf-img__prev').empty();
  });

  /* ドラッグで並べ替え */
  if ($.fn.sortable) {
    $('.ymkrf-rep__rows').sortable({ handle:'.ymkrf-row__handle', axis:'y', cursor:'grabbing' });
  }
});
</script>
	<?php
	return ob_get_clean();
}


/* ============================================================
   カテゴリページの下に出す「施工事例」ブロック

   施工事例は「部位（ymkrf_works_cat）」で分類します。
   商品カテゴリ（キッチン／お風呂 …）と同じ名前・スラッグの部位を
   自動で作っておくので、記事側は部位を選ぶだけで対応します。
   ============================================================ */

/* 商品カテゴリと同じ「部位」を用意します（無いものだけ作ります） */
add_action( 'init', function () {
	if ( get_option( 'ymkrf_works_cat_sync' ) === '1' ) return;
	if ( ! taxonomy_exists( 'ymkrf_product_cat' ) || ! taxonomy_exists( 'ymkrf_works_cat' ) ) return;

	$cats = get_terms( array( 'taxonomy' => 'ymkrf_product_cat', 'hide_empty' => false ) );
	if ( is_wp_error( $cats ) || ! $cats ) return;

	foreach ( $cats as $t ) {
		if ( ! term_exists( $t->slug, 'ymkrf_works_cat' ) ) {
			wp_insert_term( $t->name, 'ymkrf_works_cat', array( 'slug' => $t->slug ) );
		}
	}
	update_option( 'ymkrf_works_cat_sync', '1' );
}, 30 );


if ( ! function_exists( 'ymkrf_works_query' ) ) :
function ymkrf_works_query( $slug = '', $number = 3 ) {
	$args = array(
		'post_type'           => 'ymkrf_works',
		'posts_per_page'      => (int) $number,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	if ( $slug ) {
		$args['tax_query'] = array( array(
			'taxonomy' => 'ymkrf_works_cat',
			'field'    => 'slug',
			'terms'    => $slug,
		) );
	}
	return new WP_Query( $args );
}
endif;


/* 施工事例のカード1枚分

   ★トップページの施工事例とまったく同じ形（Before / After 比較スライダー）です。
     見た目の指定は common.css の「施工事例」、
     左右に動かす動きは common.js の initCompare が受け持ちます。

   ・Before写真 … 施工事例の編集画面（右側「施工データ」）の
                  「Before画像」で選びます。
   ・After写真  … アイキャッチ画像をそのまま使います。
   ・Before写真が未登録のときは、スライダーではなく
     アイキャッチ画像だけを同じ枠で出します（形はそろいます）。
*/
if ( ! function_exists( 'ymkrf_works_card' ) ) :
function ymkrf_works_card() {

	$id     = get_the_ID();
	$area   = get_the_terms( $id, 'ymkrf_works_area' );
	$area   = ( $area && ! is_wp_error( $area ) ) ? $area[0]->name : '';
	$part   = get_the_terms( $id, 'ymkrf_works_cat' );
	$part   = ( $part && ! is_wp_error( $part ) ) ? $part[0]->name : '';
	$price  = get_post_meta( $id, '_ymkrf_price', true );
	$period = get_post_meta( $id, '_ymkrf_period', true );

	$before = (int) get_post_meta( $id, '_ymkrf_before_img', true );
	$bimg   = $before ? wp_get_attachment_image( $before, 'medium_large', false, array( 'loading' => 'lazy', 'alt' => '施工前' ) ) : '';
	$aimg   = has_post_thumbnail( $id ) ? get_the_post_thumbnail( $id, 'medium_large', array( 'loading' => 'lazy', 'alt' => '施工後' ) ) : '';
	$link   = get_permalink( $id );
	?>
	<article class="p-work">

		<?php if ( $aimg || $bimg ) : ?>
			<?php /* トップページとまったく同じ、左右に動かして見くらべる形です。
			         Before写真がまだ入っていない事例は、トップページと同じように
			         「BEFORE 写真」の下じきを出します。
			         施工事例の編集画面 右「施工データ」→「Before写真（施工前）」を
			         入れると、その事例から本物の写真に変わります。 */ ?>
			<div class="p-compare" data-compare style="--pos:50%">
				<div class="p-compare__layer p-compare__layer--before">
					<?php if ( $bimg ) : echo $bimg; /* phpcs:ignore */ else : ?>
						<p class="p-compare__ph">BEFORE 写真</p>
					<?php endif; ?>
				</div>
				<div class="p-compare__layer p-compare__layer--after">
					<?php if ( $aimg ) : echo $aimg; /* phpcs:ignore */ else : ?>
						<p class="p-compare__ph">AFTER 写真</p>
					<?php endif; ?>
				</div>
				<span class="p-compare__tag p-compare__tag--before">BEFORE</span>
				<span class="p-compare__tag p-compare__tag--after">AFTER</span>
				<span class="p-compare__handle"></span>
				<span class="p-compare__hint">← 左右に動かして見くらべる →</span>
			</div>
		<?php endif; ?>

		<div class="p-work__body">
			<?php if ( $part || $area ) : ?>
				<p class="p-work__meta">
					<?php if ( $part ) : ?><span><?php echo esc_html( $part ); ?></span><?php endif; ?>
					<?php if ( $area ) : ?><span><?php echo esc_html( $area ); ?></span><?php endif; ?>
				</p>
			<?php endif; ?>
			<h3 class="p-work__title"><a href="<?php echo esc_url( $link ); ?>"><?php the_title(); ?></a></h3>
			<?php if ( $price || $period ) : ?>
				<p class="p-work__data">
					<?php if ( $price ) : ?><span>工事費込み <?php echo esc_html( $price ); ?></span><?php endif; ?>
					<?php if ( $period ) : ?><span>工期 <?php echo esc_html( $period ); ?></span><?php endif; ?>
				</p>
			<?php endif; ?>
		</div>

	</article>
	<?php
}
endif;


if ( ! function_exists( 'ymkrf_works_section' ) ) :
function ymkrf_works_section( $slug, $catname, $number = 3 ) {

	$q = ymkrf_works_query( $slug, $number );

	/* まだ1件も無いとき。お客様には出さず、ログイン中のスタッフにだけ案内します。 */
	if ( ! $q->have_posts() ) {
		wp_reset_postdata();
		if ( ! current_user_can( 'edit_posts' ) ) return;
		?>
		<section class="l-section" id="works">
			<div class="l-wrap">
				<div class="c-head">
					<span class="c-head__en">WORKS</span>
					<h2 class="c-head__title"><?php echo esc_html( $catname ); ?>の施工事例</h2>
				</div>
				<div class="p-col__placeholder">
					<p><b>この場所に、施工事例が新しい順で<?php echo (int) $number; ?>件並びます。</b></p>
					<p>
						ダッシュボードの「施工事例」から追加し、
						<b>部位で「<?php echo esc_html( $catname ); ?>」にチェック</b>してください。<br>
						アイキャッチ画像・エリア・工事費・工期を入れると、カードにそのまま出ます。
					</p>
					<p class="p-col__placeholder__note">
						※このご案内は、ログイン中のスタッフにだけ見えています。お客様には表示されません。
					</p>
					<p>
						<a class="p-col__all" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=ymkrf_works' ) ); ?>">
							施工事例を追加する
						</a>
					</p>
				</div>
			</div>
		</section>
		<?php
		return;
	}

	$term = $slug ? get_term_by( 'slug', $slug, 'ymkrf_works_cat' ) : null;
	$more = ( $term && ! is_wp_error( $term ) )
		? get_term_link( $term )
		: get_post_type_archive_link( 'ymkrf_works' );
	if ( is_wp_error( $more ) ) $more = get_post_type_archive_link( 'ymkrf_works' );
	?>
	<!-- 見出し・カード・ボタンとも、トップページの施工事例とそろえています。
	     背景だけは、ひとつ上の「お役立ち情報」が薄い色なので、
	     交互になるように白にしています。 -->
	<section class="l-section" id="works">
		<div class="l-wrap">
			<div class="c-head">
				<span class="c-head__en">WORKS</span>
				<h2 class="c-head__title"><?php echo esc_html( $catname ); ?>の施工事例</h2>
				<p class="c-head__lead">石川・福井の実際のお宅で、どう変わったか。金額も公開しています。</p>
			</div>
			<div class="p-works__grid">
				<?php while ( $q->have_posts() ) : $q->the_post(); ymkrf_works_card(); endwhile; ?>
			</div>
			<?php if ( $more ) : ?>
				<?php /* すぐ上の見出しに分類名が出ているので、ボタンは短くしています。
				         「洗面化粧台の…」のように長い分類だと2行になってしまうためです。 */ ?>
				<a class="c-more" href="<?php echo esc_url( $more ); ?>">施工事例をもっと見る</a>
			<?php endif; ?>
		</div>
	</section>
	<?php
	wp_reset_postdata();
}
endif;


/* ============================================================
   メーカーのロゴ

   画像は themes/ymkrf/assets/img/logo/maker/<スラッグ>.png に置きます。
   すべて 560×140 の同じ大きさで書き出してあるので、
   どのメーカーでも同じ枠におさまります。
   ロゴが無いメーカーは、これまでどおり文字で出します。
   ============================================================ */
if ( ! function_exists( 'ymkrf_maker_logo' ) ) :
function ymkrf_maker_logo( $term, $class = 'p-maker' ) {

	if ( ! $term || is_wp_error( $term ) ) return '';
	$name = $term->name;
	$slug = $term->slug;

	$rel  = '/assets/img/logo/maker/' . $slug;
	$path = get_stylesheet_directory() . $rel;

	/* ロゴが無いときは文字にもどします */
	if ( ! file_exists( $path . '.png' ) ) {
		return '<span class="' . esc_attr( $class ) . ' ' . esc_attr( $class ) . '--text">'
		     . esc_html( $name ) . '</span>';
	}

	$uri  = get_stylesheet_directory_uri() . $rel;
	$webp = file_exists( $path . '.webp' )
		? '<source srcset="' . esc_url( $uri . '.webp' ) . '" type="image/webp">' : '';

	/* ロゴは高さだけそろえ、幅はロゴなりです。
	   読み込み中に高さが変わらないよう、実寸を width / height に入れます。 */
	$size = @getimagesize( $path . '.png' );
	$wh   = $size ? ' width="' . (int) $size[0] . '" height="' . (int) $size[1] . '"' : '';

	return '<span class="' . esc_attr( $class ) . '"><picture>' . $webp
	     . '<img class="' . esc_attr( $class ) . '__img" src="' . esc_url( $uri . '.png' ) . '"'
	     . $wh
	     . ' alt="' . esc_attr( $name ) . '"'
	     . ' title="' . esc_attr( $name . 'の製品です' ) . '"'
	     . ' loading="lazy" decoding="async"></picture></span>';
}
endif;


/* ============================================================
   グレードの見せかた（2026/09/01 ユーザー指示）

   給湯器の「ふろ機能」は、管理画面では「オート」「フルオート」と
   短くえらべるようにしています。
   ただ、お客様に見えるページでは「オートタイプ」のほうが分かりやすいので、
   表示するときだけ「タイプ」を付けます。
   ============================================================ */
if ( ! function_exists( 'ymkrf_grade_label' ) ) :
function ymkrf_grade_label( $g, $cat = '' ) {
	$g = trim( (string) $g );

	/* エコキュートは「フルオート」「フルオート・高圧」「フルオート・高圧・高効率」と
	   長さがまちまちなので、「タイプ」は付けません。
	   片方だけ「フルオートタイプ」になって、ちぐはぐに見えるためです。
	   （PDF・本番サイトの書き方にもそろえています） */
	if ( $cat === 'ecocute' ) return $g;

	if ( $g === 'オート' || $g === 'フルオート' ) return $g . 'タイプ';
	return $g;
}
endif;


/* ============================================================
   基本仕様の表（給湯器・エコキュート）

   入っている項目だけを返します。空の項目は出しません。
   （2026/09/01 ユーザー指示「無記入の場合はこの項目は非表示」）
   ============================================================ */
if ( ! function_exists( 'ymkrf_product_basicspec' ) ) :
function ymkrf_product_basicspec( $d ) {

	/* この表を出すのは給湯器・エコキュートだけです。
	   （ほかの分類では「設置方法」が「型（サイズ）」の意味になるため） */
	$ok = false;
	foreach ( (array) $d['cats'] as $c ) {
		if ( in_array( $c->slug, array( 'boiler', 'ecocute' ), true ) ) { $ok = true; break; }
	}
	if ( ! $ok ) return array();

	$rows = array();

	/* エコキュートは、タンクの大きさがいちばん大事なので先に出します */
	if ( ! empty( $d['tank'] ) ) {
		$v = $d['tank'] . 'L';
		if ( ! empty( $d['people'] ) ) $v .= "\n" . $d['people'];
		$rows[] = array( 'タンク容量', $v );
	} elseif ( ! empty( $d['people'] ) ) {
		$rows[] = array( '対象人数', $d['people'] );
	}

	if ( ! empty( $d['size'] ) )     $rows[] = array( '設置方法', $d['size'] );
	if ( ! empty( $d['exterior'] ) ) $rows[] = array( '外装',     $d['exterior'] );
	if ( ! empty( $d['pressure'] ) ) $rows[] = array( '給湯圧力', $d['pressure'] );

	if ( $d['power'] !== '' && $d['power'] !== null ) {
		$u = $d['powerunit'] !== '' ? $d['powerunit'] : '号';
		$rows[] = array( '給湯能力', $d['power'] . $u );
	}

	if ( ! empty( $d['dim'] ) )    $rows[] = array( '寸法', $d['dim'] . '（mm）' );
	if ( ! empty( $d['weight'] ) ) $rows[] = array( '質量', $d['weight'] . 'kg' );

	/* 付属品・リモコン品番・補助金はエコキュートだけ。いちばん下に出します */
	if ( ! empty( $d['accessory'] ) ) {
		$rows[] = array( '付属品', str_replace( array( '／', '/' ), "\n", $d['accessory'] ) );
	}
	if ( ! empty( $d['remote'] ) ) {
		$rows[] = array( '補助金対応リモコン', $d['remote'] );
	}
	if ( ! empty( $d['hojo'] ) && $d['hojo'] === '対象' ) {
		$rows[] = array( '補助金', '補助金適用の対象機種です' );
	}

	return $rows;
}
endif;


/* ============================================================
   「。」のところで改行する（2026/09/01 ユーザー指示）

   日本語は文の途中でも折り返してしまうので、
   「そ／の場合も」のような読みにくい切れ方になることがあります。
   句点のうしろで必ず改行するようにして、文のかたまりで読めるようにします。
   （文の最後の「。」では改行しません）
   ============================================================ */
if ( ! function_exists( 'ymkrf_brk' ) ) :
function ymkrf_brk( $text ) {

	$t = esc_html( trim( (string) $text ) );

	/* ① 「。」のうしろで改行します。
	     閉じカッコが続くときは、そこまでをひとかたまりにします。
	     いちばん最後の「。」では改行しません。 */
	$t = preg_replace( '/。((?:&[a-z]+;|[」』）\)])*)(?!$)/u', '。$1<br>', $t );

	/* ② 「エコジョーズ」のようなカギカッコの中の言葉が
	     途中で切れないようにします。
	     長すぎる言葉は、切れないと画面からはみ出すので、そのままにします。 */
	$t = preg_replace_callback(
		'/「[^「」]{1,14}」/u',
		function ( $m ) { return '<span class="ymkrf-nb">' . $m[0] . '</span>'; },
		$t
	);

	return $t;
}
endif;


/* ============================================================
   カラーの見出し

   キッチンは「扉カラー／天板カラー／シンクカラー」ですが、
   お風呂は「浴槽／エプロン／壁パネル …」と呼び方が変わります。
   商品ごとに `_ymkrf_lbl_<枠>` を入れておけば、その名前で出ます。
   入れなければキッチンの呼び方のままです。
   ============================================================ */
if ( ! function_exists( 'ymkrf_colorsets' ) ) :
function ymkrf_colorsets( $d, $post_id = 0 ) {

	if ( ! $post_id ) $post_id = get_the_ID();

	$def = array(
		'colors' => '扉カラー',
		'tops'   => '天板カラー',
		'sinks'  => 'シンクカラー',
		'c4'     => '',
		'c5'     => '',
		'c6'     => '',
	);

	$out = array();
	foreach ( $def as $key => $label ) {
		if ( empty( $d[ $key ] ) ) continue;
		$custom = get_post_meta( $post_id, '_ymkrf_lbl_' . $key, true );
		$name   = $custom ? $custom : $label;
		if ( ! $name ) $name = 'カラー';
		/* 見出しのうしろの「（全4色）」はテンプレート側が自動で付けます。
		   入力に同じものが入っていると二重になるので、ここで外します。 */
		$name = preg_replace( '/[（(]\s*全?\s*[0-9０-９]+\s*色\s*[）)]\s*$/u', '', $name );

		/* 但し書き。商品ごとに入れたいときは _ymkrf_note_<枠> に入れます。
		   「none」と入れると、既定の但し書きも出さなくなります。 */
		$cnote = get_post_meta( $post_id, '_ymkrf_note_' . $key, true );
		if ( $cnote === 'none' ) {
			$cnote = '';
		} elseif ( $cnote === '' && $key === 'colors' ) {
			/* 1つめの枠にだけ、オプション扱いの但し書きを出します */
			$cnote = '※下記カラー以外選択の場合はオプションとなります';
		}

		$out[] = array(
			'key'   => $key,
			'label' => $name,
			'rows'  => $d[ $key ],
			'note'  => $cnote,
		);
	}
	return $out;
}
endif;


/* ============================================================
   キッチンの「ヤマキシ標準工事内容」をそろえます

   ここに置いているのは、mu-plugin（ymkrf-setup.php）の中に入れると
   写真の取り込みなど重い処理と同じ流れに乗ってしまい、
   途中で止まったときに実行されないことがあるためです。
   このファイルはどのページを開いても必ず読み込まれます。

   内容を変えたいときは、下の配列と `ymkrf_works_ver` の番号を
   ひとつ上げてください。次にページを開いたとき1度だけ走ります。
   ============================================================ */
add_action( 'init', function () {

	$ver = '2026-08-11b';
	if ( get_option( 'ymkrf_works_ver' ) === $ver ) return;
	if ( ! post_type_exists( 'ymkrf_product' ) ) return;
	if ( ! taxonomy_exists( 'ymkrf_product_cat' ) ) return;

	$sets = array(
		'kitchen' => array(
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
		),
		'bathroom' => array(
			array( '既存ユニットバス解体撤去工事', '古い浴槽の撤去にかかる工事です。' ),
			array( '産業廃棄物処理運搬工事',       '撤去した浴槽（ユニットバス）などを廃棄処分するためにかかる費用です。' ),
			array( '水道工事',                     '給水・給湯・排水の工事です。' ),
			array( '電気工事',                     '配線の工事です。' ),
			array( '木工事',                       '脱衣所の壁下地をつくる工事です。' ),
			array( 'ユニットバス組立設置',         '新しいユニットバスの組立・設置工事です。' ),
			array( '浴室壁面造作・内装工事',       '脱衣場側の壁面を、造作する工事です。その壁面のクロスやサニタリーボードなどの内装も含みます。' ),
			array( '換気扇取付工事',               '換気扇の取り付け工事です。' ),
			array( '浴室ドア枠造作工事',           '浴室のドア枠を造作します。' ),
		),
	);

	$done = 0;
	foreach ( $sets as $catslug => $list ) {

		$term = get_term_by( 'slug', $catslug, 'ymkrf_product_cat' );
		if ( ! $term || is_wp_error( $term ) ) continue;

		$rows = array();
		foreach ( $list as $r ) $rows[] = array( 'name' => $r[0], 'text' => $r[1] );

		$ids = get_posts( array(
			'post_type'      => 'ymkrf_product',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
			'tax_query'      => array( array(
				'taxonomy' => 'ymkrf_product_cat', 'field' => 'term_id', 'terms' => $term->term_id,
			) ),
		) );
		foreach ( (array) $ids as $id ) {
			update_post_meta( $id, '_ymkrf_works', $rows );
			$done++;
		}
	}

	update_option( 'ymkrf_works_ver', $ver );
	update_option( 'ymkrf_works_ver_log', sprintf( '%s に %d 件を更新', current_time( 'Y-m-d H:i' ), $done ) );
}, 20 );


/* 管理画面の上に、上の更新結果を1度だけ出します（確認用） */
add_action( 'admin_notices', function () {
	$log = get_option( 'ymkrf_works_ver_log' );
	if ( ! $log ) return;
	delete_option( 'ymkrf_works_ver_log' );
	echo '<div class="notice notice-success is-dismissible"><p>ヤマキシ標準工事内容：' . esc_html( $log ) . '</p></div>';
} );
