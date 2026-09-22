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
	if ( get_option( 'ymkrf_maker_ver' ) === '3' ) return;

	$makers = array(
		'ykkap'       => 'YKK AP',
		'woodone'     => 'WOODONE（ウッドワン）',
		'nichiha'     => 'ニチハ',
		'sankyoalumi' => '三協アルミ',
		/* 給湯器・エコキュートのメーカー。施工事例の「メーカーだけ選ぶ」欄にも
		   ここの名前がそのまま並びます。（2026/09/08） */
		'paloma'      => 'パロマ',
		'purpose'     => 'パーパス',
		'corona'      => 'コロナ',
		'chofu'       => '長府製作所',
		'sunpot'      => 'サンポット',
		'toshiba'     => '東芝',
	);
	foreach ( $makers as $slug => $name ) {
		if ( term_exists( $slug, 'ymkrf_maker' ) ) continue;
		wp_insert_term( $name, 'ymkrf_maker', array( 'slug' => $slug ) );
	}
	update_option( 'ymkrf_maker_ver', '3' );
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
	   21 … お知らせ（/news/）を足したため
	   22 … 給湯器・エコキュートの選び方（/products/boiler-guide/）を足したため
	   23 … エラーコード一覧（/troubleshooting/）を足したため
	   24 … お見積り・お問い合わせ（/inquiry/）を足したため
	   25 … ネット来店予約（/inquiry/webrsv/）を足したため
	   26 … イベント・チラシ（/flyer/）を足したため */
	if ( get_option( 'ymkrf_rewrite_ver' ) === '26' ) return;
	flush_rewrite_rules( false );
	update_option( 'ymkrf_rewrite_ver', '26' );
}, 99 );


/* ============================================================
   2. 入力欄の定義　★項目を増やすときはここ
   ============================================================ */

/** 1つだけ入力する欄 */
function ymkrf_product_fields() {
	return array(
		//  キー           => array( 見出し, 種類, 入力例, 補足説明 )
		'_ymkrf_catch'   => array( 'キャッチコピー',   'text',   '例：キレイと快適が毎日つづく快適キッチン！', '商品名の上に、小さな赤い文字で出ます' ),
		'_ymkrf_grade'   => array( 'グレード',         'short',  '例：F',
			'「グレード」の文字は自動で付きます。「F」「SS」のように、記号だけ入れてください。'
			. '「プレミアム」など、そのまま出したい言葉はそのまま入れてください。空欄でもかまいません' ),
		'_ymkrf_order'   => array( '並び順',           'number', '例：85',
			'空欄のままで大丈夫です。グレードから自動で決まります（J=10／I=20／H=30／G=40／F=50／E=60／D=70／C=80／B=90／A=100／S=110／SS=120／SSS=125／プレミアム=130）。順番を変えたいときだけ、入れたい位置の数字を書いてください。数字が小さいほど先に出ます。' ),
		/* メーカーは、右の箱ではなくここでえらびます
		   （2026/09/18 ユーザー指示「グレードの下に、メーカー選択。
		     右の商品カテゴリ、メーカーは不要です」） */
		'_ymkrf_makerpick' => array( 'メーカー', 'maker', '', '' ),
		'_ymkrf_name'    => array( '商品名',           'text',   '例：V-style（Vスタイル）', '空欄なら上のタイトルを使います' ),
		'_ymkrf_size'    => array( '型（サイズ）',     'text',   '例：I型2550サイズ', 'メーカーロゴのとなりに出ます' ),
		'_ymkrf_sub'     => array( '商品名の横の言葉', 'text',   '例：ハイパーキラミック', '商品名のすぐ横に、小さく出ます（トイレの陶器の種類など）' ),
		'_ymkrf_work'    => array( '標準工事費', 'yen', '例：240,000', '★税込の金額を入れてください。カンマは自動で付きます' ),
		'_ymkrf_item'    => array( '商品代',     'yen', '例：358,000', '★税込の金額を入れてください。カンマは自動で付きます' ),
		/* 数字でも「半日」でも、この欄ひとつで入れられます
		   （2026/09/18 ユーザー指示「半日も入れられるようにしておいて。
		     その下の工期の書き方は不要」） */
		'_ymkrf_days'    => array( '工期', 'days', '例：4／半日',
			'数字だけ入れると「4日」のように「日」が自動で付きます。'
			. '「半日」など、日数で書けないときは、その言葉をそのまま入れてください' ),
		'_ymkrf_daystext'=> array( '', 'hidden' ),
		/* 特徴は3つを横にならべて、ひとつの欄として見せます
		   （2026/09/18 ユーザー指示「特徴1〜3ってつけず、特徴だけにして、
		     横に3つの入力欄並ばせて」）
		   保存さきは、これまでどおり _ymkrf_pt1 / _ymkrf_pt2 / _ymkrf_pt3 です */
		'_ymkrf_pts'     => array( '特徴', 'pt3', '', '商品名の下に、丸い札で3つまで出ます' ),
		'_ymkrf_pt1'     => array( '特徴 1',           'text',   '例：お手頃価格', '' ),
		'_ymkrf_pt2'     => array( '特徴 2',           'text',   '例：収納抜群', '' ),
		'_ymkrf_pt3'     => array( '特徴 3',           'text',   '例：おそうじ楽々', '' ),
		/* 長くなることもあるので、たてに広げられる欄にしています
		   （2026/09/18 ユーザー指示「文字数多くなることも予想して、広げられる欄にしておいて」） */
		'_ymkrf_caution' => array( '写真の注意書き',   'area',   '例：※写真はイメージです。',
			'商品写真の下に小さく出ます。右下をドラッグすると、欄をたてに広げられます' ),

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
		/* ── IH・コンロ（2026/09/22 ユーザー指示「こんろね」） ── */
		'_ymkrf_ihtype'  => array( '種類', 'select', '', '一覧ページの「ガスコンロ／IH」の切り替えに使います',
			array( 'ガスコンロ', 'IHクッキングヒーター' ), array( 'ih' ) ),
		'_ymkrf_gas'     => array( 'ガス種', 'select', '', 'IHのときは「—」のままでかまいません',
			array( '—', 'LPガス用', '都市ガス用' ), array( 'ih' ) ),
		'_ymkrf_model'   => array( '型番', 'text', '例：N3WV6M', '商品名の下に小さく出ます',
			array(), array( 'ih', 'fence' ) ),
		'_ymkrf_list'    => array( 'メーカー定価', 'yen', '例：249,370',
			'入れると「定価249,370円の品」と出ます。無ければ空のままでOK',
			array(), array( 'ih' ) ),

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
		/* IH・コンロ（2026/09/22 追加）。
		   工事費と商品代に分けず、「入替工事込の価格」の1つだけにします。 */
		'ih' => array(
			'_ymkrf_name'  => array( '商品名', 'text', '例：LPガス用 スタンダードタイプ',
				'空欄なら上のタイトルを使います' ),
			'_ymkrf_item'  => array( '入替工事込の価格', 'yen', '例：109,800',
				'★税込の金額を入れてください。カンマは自動で付きます。'
				. '取り外し・処分・取り付けまで込みの金額です' ),
			'_ymkrf_pts'   => array( '特徴', 'pt3', '',
				'商品名の下に、丸い札で3つまで出ます' ),
			'_ymkrf_model' => array( '型番', 'short', '例：N3WV6M',
				'商品名の下に小さく出ます' ),
			'_ymkrf_catch' => array( 'キャッチコピー', 'text', '例：数量限定！',
				'商品名の上に、小さな赤い文字で出ます' ),
			'_ymkrf_caution' => array( '注意書き', 'area', '例：※写真はイメージです。',
				'商品写真の下に小さく出ます' ),
		),
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
		/* IH・コンロ（2026/09/22 ユーザー指示「こんろね」）。
		   グレードは使いません。工事費込みの一本価格です。 */
		'ih' => array(
			'_ymkrf_makerpick', '_ymkrf_ihtype',
			/* 特徴は型番のすぐ下です
			   （2026/09/22 ユーザー指示「特徴を型番の下に移動して」） */
			'_ymkrf_name', '_ymkrf_model', '_ymkrf_pts', '_ymkrf_caution',
			'_ymkrf_list', '_ymkrf_item',
			'_ymkrf_days',
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

	/* 並び順は、画面右の「ページ属性 ＞ 順序」を使います。
	   まん中の欄には出しません（2026/09/18 ユーザー指示
	   「並び順は右にあるから商品データ（基本）からは削除」） */
	unset( $all['_ymkrf_order'] );

	/* 「工期の書き方」は、上の「工期」の欄にまとめました（2026/09/18 ユーザー指示） */
	unset( $all['_ymkrf_daystext'] );

	/* 特徴1〜3は、ひとつの「特徴」の欄にまとめて出します（2026/09/18 ユーザー指示） */
	unset( $all['_ymkrf_pt1'], $all['_ymkrf_pt2'], $all['_ymkrf_pt3'] );

	/* その分類で使わない欄を外します */
	$out = array();
	foreach ( $all as $k => $f ) {
		$only = isset( $f[5] ) ? (array) $f[5] : array();
		if ( $only && ! in_array( $cat, $only, true ) ) continue;
		$out[ $k ] = $f;
	}

	/* 内装・改装は、使う欄がとても少ないので、ここでしぼります
	   （2026/09/17 ユーザー指示
	     「グレード／商品名／商品名の横の言葉／標準工事費／特徴1〜3」） */
	if ( $cat === 'interior' ) {
		$keep = array( '_ymkrf_grade', '_ymkrf_name', '_ymkrf_catch', '_ymkrf_sub',
		               '_ymkrf_pts', '_ymkrf_work' );
		$only = array();
		foreach ( $keep as $k ) {
			if ( isset( $out[ $k ] ) ) $only[ $k ] = $out[ $k ];
		}
		return $only;
	}

	/* IH・コンロは、使う欄だけにしぼります
	   （2026/09/22 ユーザー指示「グレード 削除」） */
	if ( $cat === 'ih' ) {
		/* キャッチコピーはやめて、特徴に一本化しました
		   （2026/09/22 ユーザー指示「キャッチコピーを特徴にして」） */
		/* ガス種は使わないことになりました
		   （2026/09/22 ユーザー指示「ガス種は不要です」） */
		$keep = array(
			'_ymkrf_makerpick', '_ymkrf_ihtype',
			'_ymkrf_name', '_ymkrf_model', '_ymkrf_pts', '_ymkrf_caution',
			'_ymkrf_list', '_ymkrf_item', '_ymkrf_days',
		);
		foreach ( array_keys( $out ) as $k ) {
			if ( ! in_array( $k, $keep, true ) ) unset( $out[ $k ] );
		}
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

	/* 金額の欄は、いちばん下（「総額」のすぐ上）に置きます
	   （2026/09/18 ユーザー指示「標準工事費と商品代は総額の上に移動して」） */
	$money = array();
	if ( $cat !== 'ih' ) {   /* IH・コンロは金額も上の並び順のままにします */
		foreach ( array( '_ymkrf_work', '_ymkrf_item' ) as $k ) {
			if ( isset( $out[ $k ] ) ) { $money[ $k ] = $out[ $k ]; unset( $out[ $k ] ); }
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

	/* 上のほうは、この順にそろえます
	   （2026/09/18 ユーザー指示「キャッチコピーは商品名の下に、その下に特徴1〜3を」）
	     グレード → 商品名 → キャッチコピー → 特徴1・2・3 → （のこり） */
	$head = array();
	if ( $cat !== 'ih' ) {   /* IH・コンロは上の並び順をそのまま使います */
		foreach ( array( '_ymkrf_grade', '_ymkrf_makerpick', '_ymkrf_name',
		                 '_ymkrf_catch', '_ymkrf_pts' ) as $k ) {
			if ( isset( $out[ $k ] ) ) { $head[ $k ] = $out[ $k ]; unset( $out[ $k ] ); }
		}
	}
	if ( $head ) $out = array_merge( $head, $out );

	/* 金額の欄を、いちばん下にもどします */
	if ( $money ) $out = array_merge( $out, $money );

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
			'color' => true,
			'label' => '扉カラー',
			'size'  => '色見本は 600×400px くらい（よこ3：たて2）',
			'note'  => '色見本の写真と、色の名前を入れてください。',
			'cols'  => array(
				'img'  => array( '色見本', 'image' ),
				'name' => array( '色の名前', 'text', '例：ホワイト' ),
			),
		),
		'_ymkrf_tops' => array(
			'color' => true,
			'label' => '天板カラー',
			'size'  => '色見本は 600×400px くらい（よこ3：たて2）',
			'note'  => 'ワークトップの色見本です。無ければ空のままでOK。見出しごと出なくなります。',
			'cols'  => array(
				'img'  => array( '色見本', 'image' ),
				'name' => array( '色の名前', 'text', '例：シャインベージュ' ),
			),
		),
		'_ymkrf_sinks' => array(
			'color' => true,
			'label' => 'シンクカラー',
			'size'  => '色見本は 600×400px くらい（よこ3：たて2）',
			'note'  => 'シンクの色見本です。無ければ空のままでOK。見出しごと出なくなります。',
			'cols'  => array(
				'img'  => array( '色見本', 'image' ),
				'name' => array( '色の名前', 'text', '例：グラニュールホワイト' ),
			),
		),
		'_ymkrf_c4' => array(
			'color' => true,
			'spare' => true,   /* 中身が無いときは出しません（2026/09/18 ユーザー指示） */
			'label' => 'カラー枠4',
			'size'  => '色見本は 600×400px くらい（よこ3：たて2）',
			'note'  => 'お風呂など、色の分類が多い商品で使う予備の枠です。'
			         . '上の「この枠の見出し」に入れた言葉が、ページの見出しになります。',
			'cols'  => array(
				'img'  => array( '色見本', 'image' ),
				'name' => array( '色の名前', 'text' ),
			),
		),
		'_ymkrf_c5' => array(
			'color' => true,
			'spare' => true,
			'label' => 'カラー枠5',
			'size'  => '色見本は 600×400px くらい（よこ3：たて2）',
			'note'  => '同上。無ければ空のままでOK。',
			'cols'  => array(
				'img'  => array( '色見本', 'image' ),
				'name' => array( '色の名前', 'text' ),
			),
		),
		'_ymkrf_c6' => array(
			'color' => true,
			'spare' => true,
			'label' => 'カラー枠6',
			'size'  => '色見本は 600×400px くらい（よこ3：たて2）',
			'note'  => '同上。無ければ空のままでOK。',
			'cols'  => array(
				'img'  => array( '色見本', 'image' ),
				'name' => array( '色の名前', 'text' ),
			),
		),
		/* 足りなくならないように、予備をもう2つ持たせています
		   （2026/09/18 ユーザー指示「増やしていったり削除したりできるようにして」）。
		   使っていない枠は画面に出ないので、多くても邪魔になりません。 */
		'_ymkrf_c7' => array(
			'color' => true,
			'spare' => true,
			'label' => 'カラー枠7',
			'size'  => '色見本は 600×400px くらい（よこ3：たて2）',
			'note'  => '同上。無ければ空のままでOK。',
			'cols'  => array(
				'img'  => array( '色見本', 'image' ),
				'name' => array( '色の名前', 'text' ),
			),
		),
		'_ymkrf_c8' => array(
			'color' => true,
			'spare' => true,
			'label' => 'カラー枠8',
			'size'  => '色見本は 600×400px くらい（よこ3：たて2）',
			'note'  => '同上。無ければ空のままでOK。',
			'cols'  => array(
				'img'  => array( '色見本', 'image' ),
				'name' => array( '色の名前', 'text' ),
			),
		),
		'_ymkrf_c9' => array(
			'color' => true,
			'spare' => true,
			'label' => 'カラー枠9',
			'size'  => '色見本は 600×400px くらい（よこ3：たて2）',
			'note'  => '同上。無ければ空のままでOK。',
			'cols'  => array(
				'img'  => array( '色見本', 'image' ),
				'name' => array( '色の名前', 'text' ),
			),
		),
		'_ymkrf_c10' => array(
			'color' => true,
			'spare' => true,
			'label' => 'カラー枠10',
			'size'  => '色見本は 600×400px くらい（よこ3：たて2）',
			'note'  => '同上。無ければ空のままでOK。',
			'cols'  => array(
				'img'  => array( '色見本', 'image' ),
				'name' => array( '色の名前', 'text' ),
			),
		),
		'_ymkrf_c11' => array(
			'color' => true,
			'spare' => true,
			'label' => 'カラー枠11',
			'size'  => '色見本は 600×400px くらい（よこ3：たて2）',
			'note'  => '同上。無ければ空のままでOK。',
			'cols'  => array(
				'img'  => array( '色見本', 'image' ),
				'name' => array( '色の名前', 'text' ),
			),
		),
		'_ymkrf_c12' => array(
			'color' => true,
			'spare' => true,
			'label' => 'カラー枠12',
			'size'  => '色見本は 600×400px くらい（よこ3：たて2）',
			'note'  => '同上。無ければ空のままでOK。',
			'cols'  => array(
				'img'  => array( '色見本', 'image' ),
				'name' => array( '色の名前', 'text' ),
			),
		),
		'_ymkrf_handles' => array(
			/* 色見本とならべて入力できるよう、「カラー」の箱の中に入れます
			   （2026/09/18 ユーザー指示「カラーの中にハンドル取手も入れて」） */
			'incolor' => true,
			'label' => '取っ手',
			'size'  => '写真は 960×400px くらい（よこ長・2.4：1）',
			'note'  => 'キッチン以外で使わない場合は、空のままでOK。見出しごと出なくなります。',
			'cols'  => array(
				'img'  => array( '写真', 'image' ),
				'name' => array( '名称', 'text', '例：ハンドル取手' ),
				'code' => array( '型番', 'text', '例：HAN' ),
			),
		),
		'_ymkrf_specs' => array(
			'label' => '標準仕様',
			/* 写真のおすすめの大きさ。見出しのよこに出ます
			   （2026/09/18 ユーザー指示「標準仕様の写真の推奨サイズを、
			     標準仕様の横に記載して」） */
			'size'  => '写真は 800×600px くらい（よこ4：たて3）',
			'note'  => '標準で付いてくる設備を並べます。',
			'cols'  => array(
				'img'   => array( '写真', 'image' ),
				'name'  => array( '品名', 'text', '例：ホーロー3口トップコンロ' ),
				'model' => array( '型番など', 'text', '例：LEEG32T1V' ),
			),
		),
		'_ymkrf_speclist' => array(
			'label' => '標準仕様（文字だけの一覧）',
			/* キッチン・お風呂・トイレ・洗面化粧台は、上の「標準仕様」に
			   写真つきで入れます（2026/09/18 ユーザー指示
			   「キッチン、お風呂、トイレ、化粧台も同じ様式にして。
			     トイレも文字だけじゃなく、同じ形式にします」）。
			   すでに文字で入れてある商品では、消えないように引きつづき出します
			   （下の add_meta_boxes のところで見ています）。 */
			'not'   => array( 'kitchen', 'bathroom', 'toilet', 'lavatory' ),
			'note'  => '給湯器・エコキュートのように、写真ではなく機能名を並べる商品で使います。'
			         . '「分類」に 快適機能 などを入れ、「機能」に1行ずつ書いてください。'
			         . '上の「標準仕様」に写真を入れている商品は、こちらは空のままでOKです。',
			'cols'  => array(
				'ttl'  => array( '分類',   'text', '例：快適機能' ),
				'body' => array( '機能',   'textarea', "例：\n暖房便座\nスローダウン便座" ),
			),
		),
		'_ymkrf_features' => array(
			'label' => 'おすすめポイント',
			'kind'  => 'point',   /* 専用の見た目で出します（2026/09/18 ユーザー指示） */
			'size'  => '写真は 800×600px くらい（よこ4：たて3）',
			/* 入れ子の画面にしたので、説明は要らなくなりました
			   （2026/09/18 ユーザー指示） */
			'note'  => '',
			'cols'  => array(
				'gsub' => array( 'グループ小見出し', 'text', '例：収納力抜群' ),
				'gttl' => array( 'グループ見出し',   'text', '例：「フロアストッカー」' ),
				'ttl'  => array( '見出し',           'text', '例：大割のスライド収納' ),
				'text' => array( '説明',             'textarea', '' ),
				'note' => array( '注記',             'text', '例：※地質、建物の構造などにより…' ),
				/* 写真は何枚でも足せます（2026/09/18 ユーザー指示
				   「写真を追加したり減らしたり自由にしてほしい」）。
				   img / img2 は、前に入れたぶんを読むために残しています */
				'imgs' => array( '写真',             'imagelist' ),
				/* 写真1枚ごとの説明（ALT）。空なら見出しから自動で作ります
				   （2026/09/18 ユーザー「まさかの同じaltなの？」） */
				'alts' => array( 'ALT',              'textlist' ),
				/* 写真の下に見える説明（2026/09/18 ユーザー指示
				   「写真下に、altのランとキャプションのラン作って」） */
				'caps' => array( 'キャプション',     'textlist' ),
				'img'  => array( '',                 'image' ),
				'img2' => array( '',                 'image' ),
				'frame'=> array( '白い枠をつける',    'text', '説明図・グラフのときだけ 1' ),
			),
		),
		'_ymkrf_options' => array(
			'label' => 'おすすめオプション',
			'kind'  => 'option',   /* 専用の見た目で出します（2026/09/18 ユーザー指示） */
			/* ページでは小さな正方形で出るので、大きな写真は要りません
			   （2026/09/18 ユーザー指示「たぶん200px正方形かと」） */
			'size'  => '写真は 200×200px くらい（正方形）',
			'note'  => '標準の仕様に足せるものを、1つずつ書きます。金額は、そのオプションぶんの追加額です。',
			'cols'  => array(
				'img'   => array( '写真', 'image' ),
				/* 写真の説明（2026/09/18 ユーザー指示
				   「オススメオプションにもalt欄つけて」） */
				'alt'   => array( 'ALT', 'text' ),
				'name'  => array( '品名', 'text', '例：W450mmプルオープン 食器洗い乾燥機' ),
				'text'  => array( '説明', 'textarea', '' ),
				'price' => array( '追加金額（円）', 'number', '例：176000' ),
				'note'  => array( '補足', 'text', '例：※工事費込み' ),
			),
		),
		/* 内装・改装だけで使います（2026/09/17 ユーザー指示
		   「施工事例みたいに、BeforeとAfterにするので、写真入れる箇所作って」） */
		'_ymkrf_ba' => array(
			'label' => 'Before / After の写真',
			'size'  => '写真は 1200×900px くらい（よこ4：たて3）',
			'note'  => '1行につき、施工前と施工後の写真を1枚ずつ入れてください。'
			         . '何組でも足せます。説明は空でもかまいません。',
			'only'  => array( 'interior' ),
			'cols'  => array(
				'before' => array( 'Before（施工前）', 'image' ),
				'after'  => array( 'After（施工後）',  'image' ),
				'text'   => array( '説明', 'text', '例：6帖の和室をフローリングに' ),
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

	$ymkrf_has_color = false;    /* 色見本の枠が1つでもあるか */

	foreach ( ymkrf_product_repeaters() as $key => $r ) {

		/* 「ヤマキシ標準工事内容」は、商品ごとではなくカテゴリごとに決めます。
		   （2026/09/17 ユーザー指示「商品登録ぺージには必要ないわ。
		     ここは登録するだけのぺージにして」）
		   直す場所は 商品 ＞ 標準工事内容の設定 です。 */
		if ( $key === '_ymkrf_works' ) continue;

		/* 「組み合わせイメージ写真」は使わないことになりました
		   （2026/09/18 ユーザー指示「これは不要です　削除して」）
		   すでに入っている写真は消していません。
		   また使いたくなったら、この2行を消してください。 */
		if ( $key === '_ymkrf_images' ) continue;

		/* 分類をしぼっている欄（only）は、その分類のときだけ出します */
		$cat_now = function_exists( 'ymkrf_product_current_cat' )
			? ymkrf_product_current_cat( get_the_ID() ) : '';
		if ( ! empty( $r['only'] ) && ! in_array( $cat_now, (array) $r['only'], true ) ) continue;

		/* 反対に、この分類では出さない（not）。
		   ただし、もう中身が入っている商品では、消えないように出します */
		if ( ! empty( $r['not'] ) && in_array( $cat_now, (array) $r['not'], true ) ) {
			$ymkrf_have = get_post_meta( get_the_ID(), $key, true );
			if ( ! ( is_array( $ymkrf_have ) && $ymkrf_have ) ) continue;
		}

		/* 内装・改装では、Before/After だけを出します
		   （2026/09/17 ユーザー指示。標準仕様や扉カラーなどは使いません） */
		if ( $cat_now === 'interior' && $key !== '_ymkrf_ba' ) continue;

		/* IH・コンロは、いまのところ「商品データ（基本）」だけです
		   （2026/09/22 ユーザー指示「これらの商品は、とりあえず基本情報だけでよいわ」）。
		   おすすめポイントなどを使いたくなったら、下の array に欄の名前を足してください。
		   例：array( '_ymkrf_features', '_ymkrf_options' ) */
		if ( $cat_now === 'ih' && ! in_array( $key, array(), true ) ) continue;

		/* 色見本の枠は、ぜんぶまとめて1つの箱に入れます
		   （2026/09/18 ユーザー指示「自由に増やしたり消したりできるように」）。
		   下の「カラー（色見本）」の箱で作ります。 */
		if ( ! empty( $r['color'] ) || ! empty( $r['incolor'] ) ) { $ymkrf_has_color = true; continue; }

		$box_ttl = $r['label'];

		/* 写真のおすすめの大きさは、見出しのよこに小さく出します
		   （2026/09/18 ユーザー指示「オススメポイントの横に小さく入れて」） */
		if ( ! empty( $r['size'] ) ) {
			$box_ttl .= ' <span class="ymkrf-boxsize" style="font-weight:400;font-size:12px;color:#787c82">'
			          . esc_html( $r['size'] ) . '</span>';
		}

		add_meta_box(
			'ymkrf_box' . $key, $box_ttl,
			function ( $post ) use ( $key ) { ymkrf_product_box_repeater( $post, $key ); },
			'ymkrf_product', 'normal', 'default'
		);
	}

	/* 色見本の枠は、この1つの箱の中で、自由に足したり消したりします */
	if ( $ymkrf_has_color ) {
		add_meta_box(
			'ymkrf_product_colors',
			'カラー・取っ手',
			'ymkrf_product_box_colors', 'ymkrf_product', 'normal', 'default'
		);
	}
} );


/**
 * カラー（色見本）の箱。
 * （2026/09/18 ユーザー「自由に増やしたり消したりできないのね」）
 *
 * 扉カラー・天板カラー・シンクカラー＋予備の枠を、ぜんぶこの中に入れました。
 * ・「＋ カラーの枠を足す」で、その場で枠が増えます（保存前に増やせます）
 * ・枠の右上の「× この枠を消す」で、その場で消えます
 * ・枠の見出しは、枠の上の赤い欄にそのまま書きます
 *
 * ※ 保存さきは、これまでと同じ（_ymkrf_colors／_ymkrf_tops／_ymkrf_sinks／_ymkrf_c4…）です。
 *    画面に出ていない枠には、消す印（ymkrf_delbox）を付けて送っています。
 */
function ymkrf_product_box_colors( $post ) {

	$reps = ymkrf_product_repeaters();
	echo '<div class="ymkrf-cwrap">';

	/* はじめから出しておく枠の数
	   （2026/09/18 ユーザー指示「デフォルトで天板カラー、シンクカラーはあって良い。
	     5つ目以降はこちらで＋したら出てくるようにして」） */
	$open_num = 4;
	$no       = 0;
	$after    = array();   /* 色見本のあとに出すもの（取っ手） */

	foreach ( $reps as $key => $def ) {

		if ( empty( $def['color'] ) && empty( $def['incolor'] ) ) continue;
		if ( ! empty( $def['color'] ) ) $no++;

		$short = substr( $key, 7 );                       /* _ymkrf_c4 → c4 */
		$rows  = get_post_meta( $post->ID, $key, true );
		$rows  = is_array( $rows ) ? $rows : array();
		$lbl   = ! empty( $def['color'] )
			? (string) get_post_meta( $post->ID, '_ymkrf_lbl_' . $short, true ) : '';

		/* 取っ手は、いつも出します（消したり足したりはしません）。
		   場所は「＋ カラーの枠を足す」より下なので、あとでまとめて出します */
		if ( ! empty( $def['incolor'] ) ) { $after[ $key ] = $def; continue; }

		/* はじめの4つはいつも出します。5つ目からは、
		   中身か見出しが入っているものだけ出します（あとは「＋」で出てきます） */
		$open = ( $no <= $open_num || $rows || $lbl !== '' );

		printf(
			'<div class="ymkrf-cframe%s" data-key="%s"%s>',
			$open ? '' : ' is-off',
			esc_attr( $key ),
			$open ? '' : ' style="display:none"'
		);

		/* 出していない枠には「消す」印を付けておきます */
		printf(
			'<input type="hidden" class="ymkrf-cframe__del" name="ymkrf_delbox[%s]" value="%s">',
			esc_attr( $key ), $open ? '0' : '1'
		);

		echo '<div class="ymkrf-cframe__head">';
		$ph = ! empty( $def['spare'] ) ? '見出しを入れてください（例：パネルカラー）' : $def['label'];
		printf(
			'<span class="ymkrf-hlbl"><input type="text" name="ymkrf_lbl[%s]" value="%s" placeholder="%s"></span>',
			esc_attr( $short ), esc_attr( $lbl ), esc_attr( $ph )
		);
		echo '<span class="ymkrf-note" style="display:inline">'
		   . '色見本は 600×400px くらい（よこ3：たて2）。'
		   . '空のままなら「' . esc_html( $def['label'] ) . '」と出ます</span>';
		echo '<button type="button" class="ymkrf-cframe__x" title="この枠を消す">× この枠を消す</button>';
		echo '</div>';

		ymkrf_product_box_repeater( $post, $key );

		echo '</div>';
	}

	/* 取っ手（2026/09/18 ユーザー指示「カラーの中にハンドル取手も入れて」） */
	foreach ( $after as $key => $def ) {
		echo '<div class="ymkrf-cframe" data-key="' . esc_attr( $key ) . '">';
		printf(
			'<input type="hidden" class="ymkrf-cframe__del" name="ymkrf_delbox[%s]" value="0">',
			esc_attr( $key )
		);
		echo '<div class="ymkrf-cframe__head">';
		echo '<b class="ymkrf-cframe__name">' . esc_html( $def['label'] ) . '</b>';
		if ( ! empty( $def['size'] ) ) {
			echo '<span class="ymkrf-note" style="display:inline">' . esc_html( $def['size'] ) . '</span>';
		}
		echo '<button type="button" class="ymkrf-cframe__x" title="この枠を消す">× この枠を消す</button>';
		echo '</div>';
		ymkrf_product_box_repeater( $post, $key );
		echo '</div>';
	}

	/* 枠を足すボタン。いちばん下に、「＋」だけで置きます
	   （2026/09/18 ユーザー指示「取手の下に（というか一番下に）」「＋だけでわかる」） */
	echo '<button type="button" class="ymkrf-cadd" title="カラーの枠を1つ足す">＋</button>';

	echo '</div>';
}

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

	/* 右の「商品カテゴリ」の箱は出しません
	   （2026/09/18 ユーザー指示「キッチンの商品カテゴリから入って登録しているので、
	     キッチンって分かっていると思います。だから右の商品カテゴリは不要です」）

	   ★消すのは見た目だけです。中身はそのまま残してあるので、
	     保存したときの分類は、これまでどおり付きます。 */
	$catname = '';
	if ( $cat !== '' ) {
		$t = get_term_by( 'slug', $cat, 'ymkrf_product_cat' );
		if ( $t && ! is_wp_error( $t ) ) $catname = $t->name;
	}
	echo '<style>#ymkrf_product_catdiv,#ymkrf_makerdiv{display:none !important}</style>';
	if ( $catname !== '' ) {
		echo '<p style="margin:0 0 12px;font-size:14px;font-weight:700">分類：'
		   . esc_html( $catname ) . '</p>';
	}

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

		} elseif ( $f[1] === 'short' ) {

			/* 数文字しか入れない欄。長い枠だと、かえって分かりにくくなります */
			printf(
				'<input type="text" id="%1$s" name="%1$s" value="%2$s" placeholder="%3$s" style="width:140px">',
				esc_attr( $key ), esc_attr( $val ), esc_attr( $f[2] )
			);

		} elseif ( $f[1] === 'area' ) {

			printf(
				'<textarea id="%1$s" name="%1$s" rows="3" placeholder="%3$s"'
				. ' style="width:100%%;max-width:760px;resize:vertical">%2$s</textarea>',
				esc_attr( $key ), esc_textarea( $val ), esc_attr( $f[2] )
			);

		} elseif ( $f[1] === 'pt1' ) {

			/* 特徴を1つだけ入れる欄（2026/09/22 ユーザー指示「特徴1つで」）。
			   保存さきは特徴1と同じ _ymkrf_pt1 です。 */
			printf(
				'<input type="text" id="%1$s" name="%1$s" value="%2$s" placeholder="%3$s"'
				. ' style="width:100%%;max-width:420px">',
				'_ymkrf_pt1',
				esc_attr( (string) get_post_meta( $post->ID, '_ymkrf_pt1', true ) ),
				esc_attr( $f[2] !== '' ? $f[2] : '例：水無し両面焼き' )
			);

		} elseif ( $f[1] === 'pt3' ) {

			/* 特徴を3つ、横にならべます */
			echo '<span style="display:flex;gap:8px;flex-wrap:wrap;max-width:760px">';
			foreach ( array( '_ymkrf_pt1', '_ymkrf_pt2', '_ymkrf_pt3' ) as $n => $pk ) {
				printf(
					'<input type="text" id="%1$s" name="%1$s" value="%2$s" placeholder="%3$s"'
					. ' style="flex:1 1 180px;min-width:0;width:auto">',
					esc_attr( $pk ),
					esc_attr( (string) get_post_meta( $post->ID, $pk, true ) ),
					esc_attr( $cat === 'ih'
						? array( '例：水無し両面焼き', '例：60cm', '例：3口' )[ $n ]
						: array( '例：お手頃価格', '例：収納抜群', '例：おそうじ楽々' )[ $n ] )
				);
			}
			echo '</span>';

		} elseif ( $f[1] === 'maker' ) {

			/* メーカーをえらぶ欄。右の「メーカー」の箱は出しません */
			$now_mk = 0;
			$mks = wp_get_object_terms( $post->ID, 'ymkrf_maker', array( 'fields' => 'ids' ) );
			if ( $mks && ! is_wp_error( $mks ) ) $now_mk = (int) $mks[0];

			$all_mk = get_terms( array( 'taxonomy' => 'ymkrf_maker', 'hide_empty' => false ) );
			if ( is_wp_error( $all_mk ) ) $all_mk = array();

			/* その分類でよく使うメーカーを、上にまとめます */
			/* どのメーカーを出すかは「商品 ＞ メーカーの設定」で決めます。
			   決めていないときは、その分類の商品から自動で調べます。 */
			$used = function_exists( 'ymkrf_maker_choices' ) ? ymkrf_maker_choices( $cat ) : array();

			/* その分類で使われているメーカーだけを出します
			   （2026/09/18 ユーザー指示「メーカー選択はキッチンであるもののみを表示」）
			   まだ1件も商品が無い分類のときは、どれを使うか分からないので、ぜんぶ出します。
			   いま選ばれているメーカーは、一覧から外れないようにしています。 */
			$show = $all_mk;
			if ( $used ) {
				$used = array_map( 'intval', $used );
				if ( $now_mk && ! in_array( $now_mk, $used, true ) ) $used[] = $now_mk;
				$show = array();
				foreach ( $all_mk as $t ) {
					if ( in_array( (int) $t->term_id, $used, true ) ) $show[] = $t;
				}
			}

			echo '<select id="ymkrf_makerpick" name="ymkrf_makerpick" style="max-width:420px">';
			echo '<option value="">（えらんでください）</option>';
			foreach ( $show as $t ) {
				printf( '<option value="%d"%s>%s</option>',
					(int) $t->term_id, selected( $now_mk, $t->term_id, false ), esc_html( $t->name ) );
			}
			echo '</select>';
			if ( function_exists( 'ymkrf_maker_admin_url' ) ) {
				echo ' <a href="' . esc_url( ymkrf_maker_admin_url( $cat ) ) . '" target="_blank" rel="noopener"'
				   . ' style="margin-left:8px;font-size:12px">メーカーを追加する</a>';
			}

		} elseif ( $f[1] === 'days' ) {

			/* 数字でも「半日」でも受けます。
			   保存するときに、数字なら _ymkrf_days、言葉なら _ymkrf_daystext に振り分けます */
			$dtext = (string) get_post_meta( $post->ID, '_ymkrf_daystext', true );
			printf(
				'<input type="text" id="%1$s" name="%1$s" value="%2$s" placeholder="%3$s" style="width:140px">',
				esc_attr( $key ),
				esc_attr( $dtext !== '' ? $dtext : $val ),
				esc_attr( $f[2] )
			);

		} elseif ( $f[1] === 'yen' ) {

			/* 金額。打ちながら3けたごとに「,」が入ります
			   （2026/09/18 ユーザー指示「価格はカンマが自動でつくようにして」） */
			printf(
				'<input type="text" inputmode="numeric" class="ymkrf-yen" id="%1$s" name="%1$s"'
				. ' value="%2$s" placeholder="%3$s" style="max-width:220px"> <span>円（税込）</span>',
				esc_attr( $key ),
				esc_attr( $val !== '' ? number_format( (int) $val ) : '' ),
				esc_attr( $f[2] )
			);

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
		'<p class="ymkrf-total">総額　<b id="ymkrf-total">%s</b> 円（税込）
		 <span class="ymkrf-note">標準工事費 ＋ 商品代 の合計です。入力の必要はありません。</span></p>',
		number_format( $total )
	);
	?>
	<script>
	(function(){
	  function num(el){ return el ? (parseInt(String(el.value).replace(/[^0-9]/g,''),10) || 0) : 0; }
	  function comma(n){ return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ','); }

	  /* 金額の欄は、打ちながら3けたごとに「,」を入れます */
	  var yens = document.querySelectorAll('.ymkrf-yen');
	  for (var k = 0; k < yens.length; k++) {
	    yens[k].addEventListener('input', function(){
	      var n = String(this.value).replace(/[^0-9]/g,'');
	      this.value = n ? comma(n) : '';
	      calc();
	    });
	  }

	  var w = document.getElementById('_ymkrf_work'),
	      i = document.getElementById('_ymkrf_item'),
	      t = document.getElementById('ymkrf-total');
	  function calc(){ if (t) t.textContent = comma(num(w) + num(i)); }
	  calc();
	})();
	</script>
	<p class="ymkrf-note" style="margin-top:14px">
	  ※ 展示店舗は、画面右側の欄からチェックを入れてください。<br>
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

	/* 写真がいちばん上にあって、欄が少ないものは「カード」にして横にならべます
	   （2026/09/18 ユーザー指示「写真をもう少し大きく、クリックで写真を選ぶようにして。
	     写真を選ぶ のテキスト不要。その下に入力する欄をもってて。その塊を横並びにして」） */
	$kind  = isset( $def['kind'] ) ? $def['kind'] : '';

	/* おすすめポイントは、まとまり＋ポイントの形で出します */
	if ( $kind === 'point' ) { ymkrf_product_box_point( $post, $key, $def ); return; }
	$first = current( $def['cols'] );
	$card  = ( $kind === '' && isset( $first[1] ) && $first[1] === 'image' && count( $def['cols'] ) <= 3 );

	$cls = 'ymkrf-rep';
	if ( $card )                                            $cls .= ' ymkrf-rep--card';
	if ( $kind === 'point' || $kind === 'option' )          $cls .= ' ymkrf-rep--point';

	echo '<div class="' . esc_attr( $cls ) . '" data-key="' . esc_attr( $key ) . '">';
	echo '<div class="ymkrf-rep__rows">';

	$list = $rows ? $rows : array( 0 => array() );
	foreach ( $list as $n => $row ) {
		if ( $kind === 'point' )       ymkrf_product_row_point( $key, $n, (array) $row );
		elseif ( $kind === 'option' )  ymkrf_product_row_option( $key, $n, (array) $row );
		else                           ymkrf_product_row_html( $key, $def['cols'], $n, $row, $card );
	}

	/* カードのときは、いちばん最後に「＋」の空き枠を置きます。
	   下の「＋ 行を追加」のボタンは出しません
	   （2026/09/18 ユーザー指示「＋行を追加 を削除して、追加する場合は
	     その最後の要素に空の枠を作って ＋ などの表示して」） */
	if ( $card ) {
		echo '<button type="button" class="ymkrf-rep__addcard" title="押すと1つ増やせます">＋</button>';
	}
	echo '</div>';

	if ( $kind === 'point' ) {
		echo '<p><button type="button" class="button button-primary ymkrf-rep__add">＋ ポイントを1つ足す</button></p>';
	} elseif ( $kind === 'option' ) {
		echo '<p><button type="button" class="button button-primary ymkrf-rep__add">＋ オプションを1つ足す</button></p>';
	} elseif ( ! $card ) {
		echo '<p><button type="button" class="button ymkrf-rep__add">＋ 行を追加</button></p>';
	}
	echo '</div>';

	/* 「行を追加」で使うひな型 */
	echo '<script type="text/html" class="ymkrf-tpl-' . esc_attr( $key ) . '">';
	if ( $kind === 'point' )       ymkrf_product_row_point( $key, '__i__', array() );
	elseif ( $kind === 'option' )  ymkrf_product_row_option( $key, '__i__', array() );
	else                           ymkrf_product_row_html( $key, $def['cols'], '__i__', array(), $card );
	echo '</script>';
}


/**
 * おすすめオプションの1つぶん。
 * （2026/09/18 ユーザー「おすすめオプションも同様に見やすくして」）
 * 左に写真、右に品名・説明・追加金額・補足をならべます。
 */
function ymkrf_product_row_option( $key, $n, $row ) {

	$v = function ( $k ) use ( $row ) {
		return isset( $row[ $k ] ) ? (string) $row[ $k ] : '';
	};
	$name = function ( $k ) use ( $key, $n ) {
		return sprintf( '%s[%s][%s]', $key, $n, $k );
	};

	$img_val = $v( 'img' );
	$img_src = $img_val ? wp_get_attachment_image_url( (int) $img_val, 'medium' ) : '';
	$price   = $v( 'price' );
	?>
	<div class="ymkrf-row ymkrf-point ymkrf-opt">
		<span class="ymkrf-row__handle" title="ドラッグで並べ替え">≡</span>
		<button type="button" class="button-link ymkrf-row__del" title="このオプションを消す">×</button>

		<div class="ymkrf-point__body">
			<div class="ymkrf-point__pics ymkrf-opt__pics">
				<span class="ymkrf-img ymkrf-pic">
					<span class="ymkrf-img__prev ymkrf-img__pick" title="押すと写真をえらべます"><?php
						if ( $img_src ) echo '<img src="' . esc_url( $img_src ) . '" alt="">';
					?></span>
					<input type="hidden" name="<?php echo esc_attr( $name( 'img' ) ); ?>"
					       value="<?php echo esc_attr( $img_val ); ?>">
					<?php
					/* いまメディアに入っているALTを、うすい字で見せます
					   （2026/09/18 ユーザー指示「自動でメディアに入っているものを表示しといて」） */
					$now = $img_val ? (string) get_post_meta( (int) $img_val, '_wp_attachment_image_alt', true ) : '';
					$aph = trim( $now ) !== '' ? $now : 'alt';
					?>
					<textarea class="ymkrf-pic__alt" rows="1"
					          name="<?php echo esc_attr( $name( 'alt' ) ); ?>"
					          placeholder="<?php echo esc_attr( $aph ); ?>"
					          title="ページには出ません。目の見えない方の読み上げと、検索エンジンのための説明です。空のままなら、うすい字の言葉が使われます"><?php
						echo esc_textarea( $v( 'alt' ) ); ?></textarea>
				</span>
			</div>

			<div class="ymkrf-point__txt ymkrf-opt__txt">
				<label class="ymkrf-f"><span>品名</span>
					<input type="text" name="<?php echo esc_attr( $name( 'name' ) ); ?>"
					       value="<?php echo esc_attr( $v( 'name' ) ); ?>"
					       placeholder="例：W450mmプルオープン 食器洗い乾燥機"></label>
				<label class="ymkrf-f"><span>説明</span>
					<textarea name="<?php echo esc_attr( $name( 'text' ) ); ?>" rows="2"><?php
						echo esc_textarea( $v( 'text' ) ); ?></textarea></label>
				<label class="ymkrf-f"><span>追加金額</span>
					<span style="display:flex;align-items:center;gap:6px">
						<input type="text" inputmode="numeric" class="ymkrf-yen"
						       name="<?php echo esc_attr( $name( 'price' ) ); ?>"
						       value="<?php echo esc_attr( $price !== '' ? number_format( (int) $price ) : '' ); ?>"
						       placeholder="例：176,000" style="max-width:180px">
						<span style="font-size:12.5px">円（税込）</span>
					</span></label>
				<label class="ymkrf-f"><span>補足（小さい字。いらなければ空のまま）</span>
					<input type="text" name="<?php echo esc_attr( $name( 'note' ) ); ?>"
					       value="<?php echo esc_attr( $v( 'note' ) ); ?>" placeholder="例：※工事費込み"></label>
			</div>
		</div>
	</div>
	<?php
}


/**
 * おすすめポイント（まとまり＋その中のポイント）
 * （2026/09/18 ユーザー指示
 *   「フロントと同じ順番にしてほしい。ちょっと何がどれか分からない」
 *   「ポイントから下の塊を増やせるように」）
 *
 * ページでは、こういう形で出ています。
 *
 *   ［まとまり］ 小見出し：収納抜群！ ／ 見出し：「フロアストッカー」
 *     ├ Point 1  ポイント：大割のスライド収納／説明／注記／写真
 *     ├ Point 2  …
 *     └ Point 3  …
 *
 * 画面もこの形にそろえました。
 * 保存のしかたは前と同じ（1ポイント＝1行）なので、
 * すでに入れてある中身は、そのまま読めます。
 */
function ymkrf_product_box_point( $post, $key, $def ) {

	$rows = get_post_meta( $post->ID, $key, true );
	if ( ! is_array( $rows ) ) $rows = array();

	/* 行を「まとまり」にまとめます。
	   小見出し・見出しのどちらかが入っている行から、新しいまとまりが始まります。 */
	$groups = array();
	foreach ( $rows as $n => $row ) {
		$row  = (array) $row;
		$head = ( trim( (string) ( $row['gsub'] ?? '' ) ) !== ''
		       || trim( (string) ( $row['gttl'] ?? '' ) ) !== '' );
		if ( ! $groups || $head ) {
			$groups[] = array(
				'gsub'   => (string) ( $row['gsub'] ?? '' ),
				'gttl'   => (string) ( $row['gttl'] ?? '' ),
				'points' => array(),
			);
		}
		$groups[ count( $groups ) - 1 ]['points'][] = array( 'idx' => $n, 'row' => $row );
	}
	if ( ! $groups ) {
		$groups[] = array( 'gsub' => '', 'gttl' => '',
		                   'points' => array( array( 'idx' => 0, 'row' => array() ) ) );
	}

	echo '<div class="ymkrf-rep ymkrf-rep--point" data-key="' . esc_attr( $key ) . '">';
	echo '<div class="ymkrf-grps">';
	foreach ( $groups as $gi => $g ) ymkrf_product_group_html( $key, $g, $gi + 1 );
	echo '</div>';
	echo '<p><button type="button" class="button button-primary ymkrf-grp__add">'
	   . '＋ まとまりを足す</button></p>';
	echo '</div>';

	/* 足すときに使うひな型 */
	echo '<script type="text/html" class="ymkrf-tpl-grp">';
	ymkrf_product_group_html( $key, array( 'gsub' => '', 'gttl' => '',
		'points' => array( array( 'idx' => '__i__', 'row' => array() ) ) ), 1 );
	echo '</script>';

	echo '<script type="text/html" class="ymkrf-tpl-pt">';
	ymkrf_product_point_html( $key, '__i__', array(), 1, false );
	echo '</script>';
}


/** まとまり1つぶん */
function ymkrf_product_group_html( $key, $g, $gno = 1 ) {

	$first = $g['points'][0]['idx'];
	?>
	<div class="ymkrf-grp">
		<p class="ymkrf-grp__ttl">オススメ<span class="ymkrf-grp__num"><?php echo (int) $gno; ?></span></p>
		<button type="button" class="ymkrf-grp__del" title="このまとまりをぜんぶ消す">× まとまりを消す</button>

		<div class="ymkrf-grp__head">
			<label class="ymkrf-f"><span class="ymkrf-grp__lblsub">オススメ<?php echo (int) $gno; ?>　小見出し</span>
				<input type="text" class="ymkrf-grp__gsub"
				       name="<?php echo esc_attr( sprintf( '%s[%s][gsub]', $key, $first ) ); ?>"
				       value="<?php echo esc_attr( $g['gsub'] ); ?>" placeholder="例：収納抜群！"></label>
			<label class="ymkrf-f"><span class="ymkrf-grp__lbl">オススメ<?php echo (int) $gno; ?></span>
				<input type="text" class="ymkrf-grp__gttl"
				       name="<?php echo esc_attr( sprintf( '%s[%s][gttl]', $key, $first ) ); ?>"
				       value="<?php echo esc_attr( $g['gttl'] ); ?>" placeholder="例：「フロアストッカー」"></label>
		</div>

		<div class="ymkrf-grp__points">
			<?php foreach ( $g['points'] as $i => $pt )
				ymkrf_product_point_html( $key, $pt['idx'], $pt['row'], $i + 1, ( $i === 0 ) ); ?>
		</div>

		<p><button type="button" class="button ymkrf-pt__add">＋ ポイントを足す</button></p>
	</div>
	<?php
}


/** ポイント1つぶん */
function ymkrf_product_point_html( $key, $n, $row, $no = 1, $is_first = false ) {

	$v = function ( $k ) use ( $row ) {
		return isset( $row[ $k ] ) ? (string) $row[ $k ] : '';
	};
	$name = function ( $k ) use ( $key, $n ) {
		return sprintf( '%s[%s][%s]', $key, $n, $k );
	};

	/* いま入っている写真。前に「写真1・写真2」で入れたものも読みます */
	$pics = isset( $row['imgs'] ) && is_array( $row['imgs'] ) ? array_map( 'intval', $row['imgs'] ) : array();
	if ( ! $pics ) {
		foreach ( array( 'img', 'img2' ) as $k ) {
			$one = (int) $v( $k );
			if ( $one ) $pics[] = $one;
		}
	}
	$alts = isset( $row['alts'] ) && is_array( $row['alts'] ) ? array_values( $row['alts'] ) : array();
	$caps = isset( $row['caps'] ) && is_array( $row['caps'] ) ? array_values( $row['caps'] ) : array();

	$pic = function ( $id, $alt, $cap ) use ( $name ) {
		$src = $id ? wp_get_attachment_image_url( (int) $id, 'medium' ) : '';

		/* いまメディアに入っているALTを、うすい字で見せます
		   （2026/09/18 ユーザー指示「自動で入っているalt、ダッシュボードにも表示して」）。
		   ここに書けば、その言葉が優先されます。 */
		$now = $id ? (string) get_post_meta( (int) $id, '_wp_attachment_image_alt', true ) : '';
		$aph = trim( $now ) !== '' ? $now : 'alt';

		return sprintf(
			'<span class="ymkrf-img ymkrf-pic">
			   <span class="ymkrf-img__prev ymkrf-img__pick" title="押すと写真をえらべます">%s</span>
			   <input type="hidden" name="%s[]" value="%s">
			   <button type="button" class="ymkrf-pic__del" title="この写真を消す">×</button>
			   <textarea class="ymkrf-pic__alt" name="%s[]" rows="1"
			          placeholder="%s"
			          title="ページには出ません。目の見えない方の読み上げと、検索エンジンのための説明です。空のままなら、ポイントの見出しから自動で作ります。右下をつまむと広げられます">%s</textarea>
			   <textarea class="ymkrf-pic__cap" name="%s[]" rows="1"
			          placeholder="キャプション"
			          title="写真のすぐ下に、小さな文字で出ます。いらなければ空のままでかまいません。右下をつまむと広げられます">%s</textarea>
			 </span>',
			$src ? '<img src="' . esc_url( $src ) . '" alt="">' : '',
			esc_attr( $name( 'imgs' ) ), esc_attr( $id ),
			esc_attr( $name( 'alts' ) ), esc_attr( $aph ), esc_textarea( $alt ),
			esc_attr( $name( 'caps' ) ), esc_textarea( $cap )
		);
	};
	?>
	<div class="ymkrf-row ymkrf-point" data-idx="<?php echo esc_attr( $n ); ?>">
		<span class="ymkrf-row__handle" title="ドラッグで並べ替え">≡</span>
		<span class="ymkrf-point__no">Point <?php echo (int) $no; ?></span>
		<button type="button" class="button-link ymkrf-row__del" title="このポイントを消す">×</button>

		<div class="ymkrf-point__txt">
			<label class="ymkrf-f"><span>ポイントの見出し</span>
				<input type="text" name="<?php echo esc_attr( $name( 'ttl' ) ); ?>"
				       value="<?php echo esc_attr( $v( 'ttl' ) ); ?>" placeholder="例：大割のスライド収納"></label>
			<label class="ymkrf-f"><span>説明</span>
				<textarea name="<?php echo esc_attr( $name( 'text' ) ); ?>" rows="3"><?php
					echo esc_textarea( $v( 'text' ) ); ?></textarea></label>
			<label class="ymkrf-f"><span>注記（小さい字。いらなければ空のまま）</span>
				<input type="text" name="<?php echo esc_attr( $name( 'note' ) ); ?>"
				       value="<?php echo esc_attr( $v( 'note' ) ); ?>"
				       placeholder="例：※地質、建物の構造などにより…"></label>
		</div>

		<div class="ymkrf-point__pics">
			<?php foreach ( $pics as $pi => $one )
				echo $pic( $one,
					isset( $alts[ $pi ] ) ? $alts[ $pi ] : '',
					isset( $caps[ $pi ] ) ? $caps[ $pi ] : '' ); /* phpcs:ignore */ ?>
			<button type="button" class="ymkrf-pic__add" title="写真を1枚足す">＋</button>
		</div>

		<label class="ymkrf-point__frame">
			<input type="checkbox" name="<?php echo esc_attr( $name( 'frame' ) ); ?>" value="1"
			       <?php checked( $v( 'frame' ), '1' ); ?>>
			説明図・グラフなので、白い枠をつける
		</label>
	</div>
	<?php
}

function ymkrf_product_row_html( $key, $cols, $n, $row, $card = false ) {
	echo '<div class="ymkrf-row">';
	echo '<span class="ymkrf-row__handle" title="ドラッグで並べ替え">≡</span>';
	echo '<div class="ymkrf-row__body">';
	foreach ( $cols as $ck => $c ) {
		$name = sprintf( '%s[%s][%s]', $key, $n, $ck );
		$val  = isset( $row[ $ck ] ) ? $row[ $ck ] : '';
		$hide_label = ( $card && $c[1] === 'image' );
		echo '<label class="ymkrf-f">'
		   . ( $hide_label ? '' : '<span>' . esc_html( $c[0] ) . '</span>' );
		if ( $c[1] === 'image' && $card ) {

			/* 写真そのものを押すと、えらぶ画面がひらきます（ボタンは出しません） */
			$src = $val ? wp_get_attachment_image_url( (int) $val, 'medium' ) : '';
			/* 「写真を消す」は出しません。右上の × で行ごと消せます
			   （2026/09/18 ユーザー指示「写真を消すの文字は削除。×あるから分かる」） */
			printf(
				'<span class="ymkrf-img">
				   <span class="ymkrf-img__prev ymkrf-img__pick" title="押すと写真をえらべます">%s</span>
				   <input type="hidden" name="%s" value="%s">
				 </span>',
				$src ? '<img src="' . esc_url( $src ) . '" alt="">' : '',
				esc_attr( $name ), esc_attr( $val )
			);

		} elseif ( $c[1] === 'image' ) {
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

	$defs = ymkrf_product_fields();
	foreach ( $defs as $key => $f ) {
		if ( ! isset( $_POST[ $key ] ) ) continue;
		$v = wp_unslash( $_POST[ $key ] );
		/* 金額は「,」を外して、数字だけで保存します */
		if ( isset( $f[1] ) && $f[1] === 'yen' ) $v = preg_replace( '/[^0-9]/', '', (string) $v );
		/* 何行かに分けて書ける欄は、改行を残して保存します */
		if ( isset( $f[1] ) && $f[1] === 'area' ) {
			update_post_meta( $post_id, $key, sanitize_textarea_field( $v ) );
			continue;
		}
		update_post_meta( $post_id, $key, sanitize_text_field( $v ) );
	}

	/* 並び順は、画面右の「ページ属性 ＞ 順序」を使います。
	   並べかえに使っている _ymkrf_order に、その数字を写します */
	if ( isset( $_POST['menu_order'] ) ) {
		$mo = (int) $_POST['menu_order'];
		update_post_meta( $post_id, '_ymkrf_order', $mo ? $mo : '' );
	}

	/* カラーの枠の見出し */
	if ( isset( $_POST['ymkrf_lbl'] ) && is_array( $_POST['ymkrf_lbl'] ) ) {
		foreach ( wp_unslash( $_POST['ymkrf_lbl'] ) as $k => $v ) {
			$k = preg_replace( '/[^a-z0-9_]/', '', (string) $k );
			if ( $k === '' ) continue;
			update_post_meta( $post_id, '_ymkrf_lbl_' . $k, sanitize_text_field( $v ) );
		}
	}

	/* メーカー。ここでえらんだものだけにします */
	if ( isset( $_POST['ymkrf_makerpick'] ) ) {
		$mk = (int) $_POST['ymkrf_makerpick'];
		wp_set_object_terms( $post_id, $mk ? array( $mk ) : array(), 'ymkrf_maker' );
	}

	/* 工期。数字なら「日数」、そうでなければ「言葉」として保存します */
	if ( isset( $_POST['_ymkrf_days'] ) ) {
		$dv = trim( sanitize_text_field( wp_unslash( $_POST['_ymkrf_days'] ) ) );
		if ( $dv === '' ) {
			update_post_meta( $post_id, '_ymkrf_days', '' );
			update_post_meta( $post_id, '_ymkrf_daystext', '' );
		} elseif ( preg_match( '/^[0-9]+$/', $dv ) ) {
			update_post_meta( $post_id, '_ymkrf_days', (int) $dv );
			update_post_meta( $post_id, '_ymkrf_daystext', '' );
		} else {
			update_post_meta( $post_id, '_ymkrf_days', '' );
			update_post_meta( $post_id, '_ymkrf_daystext', $dv );
		}
	}


	foreach ( ymkrf_product_repeaters() as $key => $def ) {
		$rows  = ( isset( $_POST[ $key ] ) && is_array( $_POST[ $key ] ) ) ? wp_unslash( $_POST[ $key ] ) : array();
		$clean = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) continue;
			$r = array();
			foreach ( $def['cols'] as $ck => $c ) {
				$v = isset( $row[ $ck ] ) ? $row[ $ck ] : '';
				if ( $c[1] === 'textlist' ) {
					$txt = array();
					foreach ( (array) $v as $one ) $txt[] = sanitize_text_field( $one );
					$r[ $ck ] = $txt;
				} elseif ( $c[1] === 'imagelist' ) {
					/* 写真のIDの一覧 */
					$ids = array();
					foreach ( (array) $v as $one ) {
						$one = (int) $one;
						if ( $one ) $ids[] = $one;
					}
					$r[ $ck ] = $ids;
				} elseif ( $c[1] === 'textarea' ) {
					$r[ $ck ] = sanitize_textarea_field( $v );
				} elseif ( $c[1] === 'number' ) {
					/* 金額などは「,」を外して、数字だけで保存します */
					$r[ $ck ] = preg_replace( '/[^0-9]/', '', (string) $v );
				} else {
					$r[ $ck ] = sanitize_text_field( $v );
				}
			}
			/* すべて空の行は保存しない（写真の一覧は、中身の数で見ます） */
			$flat = '';
			foreach ( $r as $one ) $flat .= is_array( $one ) ? implode( '', $one ) : (string) $one;
			if ( strlen( trim( $flat ) ) ) $clean[] = $r;
		}
		update_post_meta( $post_id, $key, $clean );
	}

	/* 「この枠を消す」にチェックが入ったカラー枠を、まるごと消します
	   （2026/09/18 ユーザー指示「増やしていったり削除したりできるようにして」） */
	if ( isset( $_POST['ymkrf_delbox'] ) && is_array( $_POST['ymkrf_delbox'] ) ) {
		$ymkrf_reps = ymkrf_product_repeaters();
		foreach ( wp_unslash( $_POST['ymkrf_delbox'] ) as $ymkrf_dk => $ymkrf_dv ) {
			if ( (string) $ymkrf_dv !== '1' ) continue;      /* 出ている枠は 0 で送られます */
			$ymkrf_dk = preg_replace( '/[^a-z0-9_]/', '', (string) $ymkrf_dk );
			if ( ! isset( $ymkrf_reps[ $ymkrf_dk ] ) ) continue;
			if ( empty( $ymkrf_reps[ $ymkrf_dk ]['color'] )
			  && empty( $ymkrf_reps[ $ymkrf_dk ]['incolor'] ) ) continue;
			delete_post_meta( $post_id, $ymkrf_dk );
			delete_post_meta( $post_id, '_ymkrf_lbl_' . substr( $ymkrf_dk, 7 ) );
		}
	}

	/* 込み価格を保存（並べ替えや絞り込みに使うため） */
	update_post_meta( $post_id, '_ymkrf_total',
		(int) get_post_meta( $post_id, '_ymkrf_work', true ) + (int) get_post_meta( $post_id, '_ymkrf_item', true ) );
} );


/* ============================================================
   4-b. メディアの「代替テキスト（ALT）」を自動で入れる
        （2026/09/18 ユーザー指示
          「メディアについているaltは自動で入れておいてほしい。
            なんか違うなと思ったら自分で変更します」）

        商品を保存したときに、その商品で使っている写真のうち
        ALTが空のものだけに、自動で言葉を入れます。
        すでに入っているALTは、さわりません。

        入る言葉： メーカー名 ＋ 分類 ＋ 商品名 ＋ その写真の名前
        例： パナソニック キッチン V-style（Vスタイル） 扉カラー ホワイト
   ============================================================ */

/** 頭に付ける言葉（メーカー名 ＋ 分類 ＋ 商品名） */
function ymkrf_alt_base_of( $post_id ) {

	$out = array();

	$mk = get_the_terms( $post_id, 'ymkrf_maker' );
	if ( $mk && ! is_wp_error( $mk ) ) $out[] = $mk[0]->name;

	$ct = get_the_terms( $post_id, 'ymkrf_product_cat' );
	if ( $ct && ! is_wp_error( $ct ) ) $out[] = $ct[0]->name;

	$nm = (string) get_post_meta( $post_id, '_ymkrf_name', true );
	if ( $nm === '' ) $nm = get_the_title( $post_id );
	if ( $nm !== '' ) $out[] = $nm;

	return trim( implode( ' ', array_filter( $out ) ) );
}

/** 空のALTにだけ入れます */
function ymkrf_alt_put( $att_id, $text ) {

	$att_id = (int) $att_id;
	$text   = trim( preg_replace( '/\s+/u', ' ', (string) $text ) );
	if ( ! $att_id || $text === '' ) return;
	if ( get_post_type( $att_id ) !== 'attachment' ) return;

	$now = get_post_meta( $att_id, '_wp_attachment_image_alt', true );
	if ( trim( (string) $now ) !== '' ) return;   /* 入っているものは、さわりません */

	update_post_meta( $att_id, '_wp_attachment_image_alt', $text );
}

/** その商品で使っている写真の、空のALTをうめます */
function ymkrf_alt_fill_product( $post_id ) {

	if ( get_post_type( $post_id ) !== 'ymkrf_product' ) return;

	$base = ymkrf_alt_base_of( $post_id );
	if ( $base === '' ) return;

	/* アイキャッチ */
	ymkrf_alt_put( get_post_thumbnail_id( $post_id ), $base );

	/* 写真の名前に使う欄 */
	$use = array( 'name', 'code', 'model', 'ttl' );

	foreach ( ymkrf_product_repeaters() as $key => $def ) {

		$rows = get_post_meta( $post_id, $key, true );
		if ( ! is_array( $rows ) || ! $rows ) continue;

		/* 色見本・取っ手は、枠の名前も入れます（例：扉カラー ホワイト） */
		$lbl = '';
		if ( ! empty( $def['color'] ) ) {
			$lbl = (string) get_post_meta( $post_id, '_ymkrf_lbl_' . substr( $key, 7 ), true );
			if ( $lbl === '' ) $lbl = $def['label'];
		}

		foreach ( $rows as $row ) {

			if ( ! is_array( $row ) ) continue;

			$words = array();
			$own   = isset( $row['alt'] ) && ! is_array( $row['alt'] ) ? trim( (string) $row['alt'] ) : '';
			if ( $own !== '' ) {
				/* 自分で書いたALTがあれば、それを使います */
				$words[] = $own;
			} else {
				if ( $lbl !== '' ) $words[] = $lbl;
				foreach ( $use as $ck ) {
					if ( isset( $row[ $ck ] ) && ! is_array( $row[ $ck ] ) && $row[ $ck ] !== '' ) {
						$words[] = (string) $row[ $ck ];
					}
				}
			}
			$one = trim( $base . ' ' . implode( ' ', $words ) );

			foreach ( $def['cols'] as $ck => $c ) {

				if ( $c[1] === 'image' ) {
					ymkrf_alt_put( isset( $row[ $ck ] ) ? $row[ $ck ] : 0, $one );

				} elseif ( $c[1] === 'imagelist' ) {
					$ids  = isset( $row[ $ck ] ) && is_array( $row[ $ck ] ) ? $row[ $ck ] : array();
					$alts = isset( $row['alts'] ) && is_array( $row['alts'] ) ? array_values( $row['alts'] ) : array();
					foreach ( array_values( $ids ) as $i => $id ) {
						$own = isset( $alts[ $i ] ) ? trim( (string) $alts[ $i ] ) : '';
						ymkrf_alt_put( $id, $own !== '' ? trim( $base . ' ' . $own ) : $one );
					}
				}
			}
		}
	}
}

add_action( 'save_post_ymkrf_product', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;
	ymkrf_alt_fill_product( $post_id );
}, 30 );


/* 前に登録した商品の写真にも、ALTを入れておきます。
   管理画面を開くたびに、少しずつ（1回100件）すすめます。
   ぜんぶ終わったら、なにもしません。 */
add_action( 'admin_init', function () {

	if ( ! current_user_can( 'edit_posts' ) ) return;
	if ( get_option( 'ymkrf_alt_fill_done' ) === 'yes' ) return;

	$done = (array) get_option( 'ymkrf_alt_fill_ids', array() );

	$ids = get_posts( array(
		'post_type'      => 'ymkrf_product',
		'post_status'    => 'any',
		'posts_per_page' => 100,
		'fields'         => 'ids',
		'exclude'        => $done,
		'orderby'        => 'ID',
		'order'          => 'ASC',
	) );

	if ( ! $ids ) { update_option( 'ymkrf_alt_fill_done', 'yes' ); return; }

	foreach ( $ids as $id ) {
		ymkrf_alt_fill_product( $id );
		$done[] = (int) $id;
	}
	update_option( 'ymkrf_alt_fill_ids', array_slice( $done, -5000 ) );
}, 99 );


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

			if ( $cat === 'boiler' )                            $new['ymkrf_grade'] = 'ふろ機能';
			elseif ( ! in_array( $cat, array( 'ecocute', 'ih' ), true ) ) $new['ymkrf_grade'] = 'グレード';
			$new['ymkrf_price'] = '込み価格';
		}
	}

	/* IH・コンロの一覧（2026/09/22 ユーザー指示
	   「グレードは不要。商品名、写真、メーカー、価格、公開か未公開か、展示店舗、日付の順に」） */
	if ( $cat === 'ih' ) {

		$out = array();
		if ( isset( $new['cb'] ) ) $out['cb'] = $new['cb'];

		$out['title']                 = '商品名';
		$out['ymkrf_model']           = '型番';
		$out['ymkrf_thumb']           = '写真';
		$out['taxonomy-ymkrf_maker']  = 'メーカー';
		$out['ymkrf_price']           = '入替工事込の価格';
		$out['ymkrf_state']           = '公開';
		$out['taxonomy-ymkrf_shop']   = '展示店舗';
		$out['date']                  = '日付';

		return $out;
	}

	return $new;
} );

add_action( 'manage_ymkrf_product_posts_custom_column', function ( $col, $post_id ) {
	/* 型番（2026/09/22 追加）。IH・コンロの一覧で使います */
	if ( $col === 'ymkrf_model' ) {
		$mo = (string) get_post_meta( $post_id, '_ymkrf_model', true );
		echo $mo !== '' ? esc_html( $mo ) : '<span style="color:#a7aaad">—</span>';
	}
	/* 公開か未公開か（2026/09/22 追加）。IH・コンロの一覧で使います */
	if ( $col === 'ymkrf_state' ) {
		$st = get_post_status( $post_id );
		$d  = array(
			'publish' => array( '公開',   '#0a6b2d' ),
			'draft'   => array( '下書き', '#b26a00' ),
			'pending' => array( '確認待ち', '#b26a00' ),
			'private' => array( '非公開', '#b32d2e' ),
			'future'  => array( '予約',   '#2b5f86' ),
		);
		$one = isset( $d[ $st ] ) ? $d[ $st ] : array( $st, '#6b615c' );
		printf( '<span style="color:%s;font-weight:700">%s</span>',
			esc_attr( $one[1] ), esc_html( $one[0] ) );
	}
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
	/* 「Fグレード」でも「F」だけでも読み取れるようにしています */
	if ( preg_match( '/^(SSS|SS|S|A|B|C|D|E|F|G|H|I|J)\s*(グレード)?$/iu', $t, $m )
	     || preg_match( '/(SSS|SS|S|A|B|C|D|E|F|G|H|I|J)\s*グレード/u', $t, $m ) ) {
		$m[1] = strtoupper( $m[1] );
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

	   ・メーカーは、エコキュートのページと同じ 三菱電機 → Panasonic の順
	     （2026/09/17 ユーザー指示。日立・ダイキンは載せないことになりました）
	   ・価格が空の商品（下書き）は、そのメーカーのいちばん下に置きます
	   ・見出しを押したときは、これまでどおりその見出しの順になります */
	$eco_list = ( ! $front && ! $q->get( 'orderby' )
		&& isset( $_GET['ymkrf_product_cat'] )
		&& sanitize_title( wp_unslash( $_GET['ymkrf_product_cat'] ) ) === 'ecocute' );

	if ( $eco_list ) {

		$mk_order = array( 'mitsubishi', 'panasonic' );
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
   6-b. 左メニューの「カテゴリ別の入口」は出しません
        （2026/09/16 ユーザー指示「左側のプルダウンで出てくるカテゴリはなくす」）

        カテゴリは「商品」を押したときの画面（6-b1）でカードからえらびます。
        左メニューに同じものを並べると二重になるため、やめました。
   ------------------------------------------------------------ */


/* ============================================================
   6-b1. 「商品」を押したときの、カテゴリをえらぶ画面
   ------------------------------------------------------------
   （2026/09/16 ユーザー指示「イベント・チラシみたいにカテゴリに分けて」）

   「商品」を押すと、まずキッチン・お風呂…とカードがならびます。
   カードを押すと、そのカテゴリの商品だけが一覧で出ます。
   イベント・チラシの「お店をえらぶ画面」と同じ作りです。
   ============================================================ */

/** 商品カテゴリを、ならべたい順にそろえます */
if ( ! function_exists( 'ymkrf_product_cat_sort' ) ) :
function ymkrf_product_cat_sort( $terms ) {
	/* 商品一覧ページ（/products/）のカードと同じ順にします。
	   ここに無い分類は、うしろに付きます。 */
	/* 外壁・屋根は商品から外しました（2026/09/17 ユーザー指示） */
	$order = array( 'kitchen', 'bathroom', 'toilet', 'lavatory', 'boiler', 'ecocute',
	                'window', 'interior' );
	usort( $terms, function ( $a, $b ) use ( $order ) {
		$ia = array_search( $a->slug, $order, true );
		$ib = array_search( $b->slug, $order, true );
		if ( $ia === false ) $ia = 900;
		if ( $ib === false ) $ib = 900;
		if ( $ia === $ib ) return strcmp( $a->slug, $b->slug );
		return $ia - $ib;
	} );
	return $terms;
}
endif;

/** カテゴリごとの件数（公開・下書きなど）を数えます */
if ( ! function_exists( 'ymkrf_product_cat_counts' ) ) :
function ymkrf_product_cat_counts() {

	$out = array( '' => array( 'pub' => 0, 'other' => 0 ) );   /* '' は「すべて」 */

	$ids = get_posts( array(
		'post_type'      => 'ymkrf_product',
		'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );
	if ( ! $ids ) return $out;

	/* どの商品がどの分類か、1回でまとめて引きます */
	$map  = array();
	$rows = wp_get_object_terms( $ids, 'ymkrf_product_cat',
		array( 'fields' => 'all_with_object_id' ) );
	if ( ! is_wp_error( $rows ) ) {
		foreach ( $rows as $r ) $map[ $r->object_id ][] = $r->slug;
	}

	foreach ( $ids as $id ) {
		$k = ( get_post_status( $id ) === 'publish' ) ? 'pub' : 'other';
		$out[''][ $k ]++;
		if ( empty( $map[ $id ] ) ) continue;
		foreach ( $map[ $id ] as $slug ) {
			if ( ! isset( $out[ $slug ] ) ) $out[ $slug ] = array( 'pub' => 0, 'other' => 0 );
			$out[ $slug ][ $k ]++;
		}
	}
	return $out;
}
endif;

/** カテゴリをえらぶ画面 */
if ( ! function_exists( 'ymkrf_product_cats_page' ) ) :
function ymkrf_product_cats_page() {

	$terms = get_terms( array(
		'taxonomy'   => 'ymkrf_product_cat',
		'hide_empty' => false,
		'parent'     => 0,
	) );
	if ( is_wp_error( $terms ) ) $terms = array();
	$terms = ymkrf_product_cat_sort( $terms );

	$count = ymkrf_product_cat_counts();

	$url = function ( $slug ) {
		return admin_url( 'edit.php?post_type=ymkrf_product'
			. ( $slug ? '&ymkrf_product_cat=' . rawurlencode( $slug ) : '' ) );
	};

	/* カードまるごとがリンクです。押すと、その分類の商品一覧が開きます。 */
	$card = function ( $name, $slug, $note = '' ) use ( $url, $count ) {
		$c = isset( $count[ $slug ] ) ? $count[ $slug ] : array( 'pub' => 0, 'other' => 0 );
		?>
		<a class="ymkrf-pc__card<?php echo $slug ? ' ymkrf-pc__card--cat' : ' ymkrf-pc__card--all'; ?>"
		   href="<?php echo esc_url( $url( $slug ) ); ?>">
		  <span class="ymkrf-pc__name"><?php echo esc_html( $name ); ?></span>
		  <?php if ( $note ) : ?><span class="ymkrf-pc__note"><?php echo esc_html( $note ); ?></span><?php endif; ?>
		  <span class="ymkrf-pc__cnt">
		    <?php if ( $c['pub'] ) : ?>
		      <span class="ymkrf-pc__pub">公開中 <?php echo (int) $c['pub']; ?>件</span>
		    <?php else : ?>
		      <span class="ymkrf-pc__zero">公開中の商品なし</span>
		    <?php endif; ?>
		    <?php if ( $c['other'] ) : ?>
		      <span class="ymkrf-pc__other">ほか <?php echo (int) $c['other']; ?>件（下書き・非公開）</span>
		    <?php endif; ?>
		  </span>
		</a>
		<?php
	};
	?>
	<div class="wrap ymkrf-pc">
	  <h1>商品</h1>

	  <h2 class="ymkrf-pc__h2">カテゴリからえらぶ</h2>
	  <div class="ymkrf-pc__grid">
	    <?php foreach ( $terms as $t ) $card( $t->name, $t->slug ); ?>
	  </div>

	  <h2 class="ymkrf-pc__h2">まとめて見る</h2>
	  <div class="ymkrf-pc__grid">
	    <?php $card( 'すべての商品', '', 'カテゴリを分けずに、ぜんぶ見ます。' ); ?>
	  </div>

	  <p class="ymkrf-pc__foot">
	    カテゴリそのものを作る・直すときは
	    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ymkrf_product&page=ymkrf-product-settings' ) ); ?>">その他設定</a>
	    からどうぞ。
	  </p>
	</div>

	<style>
	  .ymkrf-pc__h2{margin:26px 0 10px;padding-left:9px;font-size:15px;
	    border-left:4px solid #fe3301;line-height:1.5}
	  .ymkrf-pc__grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(268px,1fr));gap:12px}
	  .ymkrf-pc__card{display:block;padding:13px 16px;background:#fff;border:1px solid #dcdcde;
	    border-radius:6px;text-decoration:none;color:inherit;transition:border-color .15s,box-shadow .15s}
	  .ymkrf-pc__card:hover{box-shadow:0 1px 6px rgba(0,0,0,.08)}
	  /* 「すべての商品」はオレンジ、カテゴリはグリーンで囲みます */
	  .ymkrf-pc__card--all{border-color:#fe3301;background:#fff8f5}
	  .ymkrf-pc__card--all:hover{border-color:#fe3301}
	  .ymkrf-pc__card--cat{border-color:#00782a;background:#eff8f2}
	  .ymkrf-pc__card--cat:hover{border-color:#005a1f;background:#e4f3ea}
	  .ymkrf-pc__name{display:block;font-size:15px;font-weight:700;line-height:1.4}
	  .ymkrf-pc__note{display:block;margin-top:3px;font-size:11.5px;color:#787878;line-height:1.5}
	  .ymkrf-pc__cnt{display:block;margin-top:7px;font-size:12.5px;line-height:1.6}
	  .ymkrf-pc__cnt span{display:block}
	  .ymkrf-pc__pub{color:#00782a;font-weight:700}
	  .ymkrf-pc__zero{color:#a7aaad}
	  .ymkrf-pc__other{color:#787878}
	  .ymkrf-pc__foot{margin-top:22px;font-size:13px;color:#50575e}
	</style>
	<?php
}
endif;

/* 小メニューに登録します。
   ★いちばん上に置くと、「商品」を押したときにこの画面が開きます
     （WordPress は、いちばん上の小メニューを行き先にするしくみです）。
     並べかえは 6-b2 でしています。 */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=ymkrf_product',
		'カテゴリからえらぶ', 'カテゴリからえらぶ',
		'edit_posts', 'ymkrf-product-cats', 'ymkrf_product_cats_page'
	);
}, 996 );


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

	$top  = array();   // カテゴリをえらぶ画面（「商品」を押したときの行き先）
	$all  = array();   // すべての商品
	$cats = array();   // カテゴリ別の入口
	$sets = array();   // その他設定
	foreach ( $submenu[ $key ] as $row ) {
		if ( ! isset( $row[2] ) ) continue;
		if ( $row[2] === 'ymkrf-product-cats' ) { $top[] = $row; continue; }
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
	$submenu[ $key ] = array_merge( $top, $all, $cats, $sets );
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
		var hide = [
			'a[href$="edit.php?post_type=ymkrf_product"]',
			/* 「カテゴリからえらぶ」は、左の「商品」を押せば開くので出しません */
			'a[href*="page=ymkrf-product-cats"]'
		];
		hide.forEach(function (sel) {
			var a  = ul.querySelector(sel);
			var li = a && a.closest ? a.closest('li') : null;
			if (li && !li.classList.contains('wp-submenu-head')) li.style.display = 'none';
		});
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
	/* 外壁・屋根は商品から外しました（2026/09/17 ユーザー指示） */
	$order = array( 'kitchen', 'bathroom', 'toilet', 'lavatory', 'boiler', 'ecocute',
	                'window', 'interior' );
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
	/* 外壁・屋根は商品から外しました（2026/09/17 ユーザー指示） */
	$order = array( 'kitchen', 'bathroom', 'toilet', 'lavatory', 'boiler', 'ecocute',
	                'window', 'interior' );
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
		/* IH・コンロで使います（2026/09/22 追加） */
		'model'    => $m( '_ymkrf_model' ),
		'list'     => (int) $m( '_ymkrf_list' ),
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
		/* IH・コンロ（2026/09/22 追加）。
		   チラシと同じく「入替工事込」の一本価格でご案内します。 */
		'ih' => array(
				'label' => 'IH・コンロの標準工事費',
				'price' => 0,
				'nocalc' => true,
				'note'  => '表示している価格は、いまお使いの機器の取り外し・処分から、'
				         . '新しい機器の取り付けまで込みの価格です。',
				'note2' => '※電気配線の工事が必要なときは、別途お見積りをお出しします。'
				         . '※お家の形や、ガス・電気の状態によっては、追加の工事が必要になることがあります。'
				         . 'その場合も着工前にかならずお見積りをお出しし、ご了承をいただいてから進めます。',
				'itemsttl' => 'リフォームヤマキシの|入替工事にふくまれる工事',
				'items' => array(
					array( 'name' => '既存機器の取り外し', 'icon' => 'hammer',
					       'sub'  => 'いまお使いのコンロ・IHの取り外し' ),
					array( 'name' => '処分・運搬',         'icon' => 'truck',
					       'sub'  => '取り外した機器の処分にかかる費用' ),
					array( 'name' => '取り付け・設置工事', 'icon' => 'flame',
					       'sub'  => '新しいコンロ・IHの取り付けと動作の確認' ),
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
	$out = isset( $d[ $slug ] ) ? $d[ $slug ] : array();

	/* 設定画面（商品 ＞ 標準工事内容の設定）で直したものがあれば、それを使います
	   （2026/09/17 ユーザー指示「ここ変更する時、その他設定でできないかな？」） */
	if ( function_exists( 'ymkrf_koji_over' ) ) $out = ymkrf_koji_over( $slug, $out );

	return $out;
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

	/* 外壁・屋根は、商品を1つも登録しません。
	   専用のページ（taxonomy-ymkrf_product_cat-outer-wall.php）を用意してあるので、
	   商品が0件でも、そちらへお送りします。 */
	if ( $slug === 'outer-wall' ) {
		$u = get_term_link( $term );
		return is_wp_error( $u ) ? ymkrf_products_url() : $u;
	}

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
		/* 2026/09/22 追加 */
		'ih'       => 'IH・コンロ',
		'exterior' => 'エクステリア',
		'window'   => '窓リフォーム',
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
		'ih'       => 'IH・ガスコンロ 商品一覧',
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
		'ih'       => 'IH・ガスコンロ',
		'exterior' => 'エクステリア',
		'window'   => '窓リフォーム',
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
	$inq  = esc_url( home_url( '/inquiry/' ) );
	$line = 'https://line.me/R/ti/p/@233okcdx';
	?>
	<div class="p-pagecta__btns" style="margin-top:26px">

	  <a class="c-cta c-cta--rsv" href="<?php echo $rsv; ?>" data-cta="<?php echo esc_attr( $place ); ?>">
	    <span class="c-cta__ico" aria-hidden="true">
	      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
	           stroke-linecap="round" stroke-linejoin="round">
	        <path d="M3 9.5 4.6 4.6A1 1 0 0 1 5.6 4h12.8a1 1 0 0 1 1 .6L21 9.5"/>
	        <path d="M3 9.5a2.2 2.2 0 0 0 4.5 0 2.2 2.2 0 0 0 4.5 0 2.2 2.2 0 0 0 4.5 0 2.2 2.2 0 0 0 4.5 0"/>
	        <path d="M4.6 12.2V20h14.8v-7.8"/><path d="M9.4 20v-5h5.2v5"/>
	      </svg>
	    </span>
	    <span class="c-cta__name">来店して現物を見る</span>
	  </a>

	  <a class="c-cta c-cta--line" href="<?php echo esc_url( $line ); ?>" rel="noopener" data-cta="<?php echo esc_attr( $place ); ?>">
	    <span class="c-cta__ico" aria-hidden="true">
	      <svg viewBox="0 0 24 24" fill="currentColor">
	        <path d="M12 2C6.5 2 2 5.6 2 10.1c0 4 3.6 7.4 8.4 8 .3.1.6.2.7.4.1.2.1.6 0 .8l-.1.8c0 .2-.2.8.7.4.9-.4 4.8-2.8 6.5-4.8C21.4 13.5 22 11.9 22 10.1 22 5.6 17.5 2 12 2z"/>
	      </svg>
	    </span>
	    <span class="c-cta__name">LINEで相談する</span>
	    <span class="c-cta__sub">24時間受付</span>
	  </a>

	  <?php /* LINEをお使いでない方・夜のうちに送っておきたい方の受け口です。
	           /inquiry/ のフォームへ送ります。 */ ?>
	  <a class="c-cta c-cta--mail" href="<?php echo $inq; ?>" data-cta="<?php echo esc_attr( $place ); ?>">
	    <span class="c-cta__ico" aria-hidden="true">
	      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
	           stroke-linecap="round" stroke-linejoin="round">
	        <rect x="2.8" y="4.8" width="18.4" height="14.4" rx="2.4"/>
	        <path d="m3.6 6.6 8.4 6 8.4-6"/>
	      </svg>
	    </span>
	    <span class="c-cta__name">無料の現地調査・お見積り</span>
	  </a>

	  <?php if ( $with_tel ) : ?>
	  <a class="c-cta c-cta--tel" href="tel:0800-777-3331" data-cta="<?php echo esc_attr( $place ); ?>">
	    <span class="c-cta__ico" aria-hidden="true">
	      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
	           stroke-linecap="round" stroke-linejoin="round">
	        <path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>
	      </svg>
	    </span>
	    <span class="c-cta__name">0800-777-3331</span>
	    <span class="c-cta__sub">通話無料<br>受付 9:00〜17:00</span>
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
/* 文字の欄は長めに。「写真の注意書き」などが途中で切れて見えないようにします
   （2026/09/18 ユーザー「写真の注意書き　入力欄を長くして、文章が見切れておる」）
   金額・工期の欄は、それぞれの欄で短く決めています */
.ymkrf-tbl input{width:100%;max-width:760px}
.ymkrf-note{display:block;margin-top:4px;color:#777;font-size:12px;line-height:1.7}
/* 写真のおすすめの大きさ。欄のいちばん上に出します */
.ymkrf-size{
  margin:0 0 8px;padding:6px 11px;display:inline-block;
  background:#f0f6fb;border:1px solid #d5e5f2;border-radius:5px;
  font-size:12.5px;font-weight:700;color:#2b5f86;
}
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

/* 写真がメインの欄（扉カラー・取っ手など）は、カードにして横にならべます */
.ymkrf-rep--card .ymkrf-rep__rows{
  display:grid;gap:12px;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));
}
.ymkrf-rep--card .ymkrf-row{position:relative;flex-direction:column;gap:6px;padding:10px}
.ymkrf-rep--card .ymkrf-row__handle{align-self:center;padding:0 0 2px}
.ymkrf-rep--card .ymkrf-row__body{width:100%;grid-template-columns:1fr;gap:6px}
.ymkrf-rep--card .ymkrf-row__del{position:absolute;top:2px;right:4px}
.ymkrf-rep--card .ymkrf-img{display:block}
/* 写真そのものが「えらぶボタン」です */
.ymkrf-rep--card .ymkrf-img__prev{
  width:100%;height:auto;aspect-ratio:1/1;cursor:pointer;border-radius:6px;
  background:#fff;border:1px dashed #c3c4c7;
}
.ymkrf-rep--card .ymkrf-img__prev:hover{border-color:#fe3301;border-style:solid}
.ymkrf-rep--card .ymkrf-img__prev img{max-width:100%;max-height:100%;object-fit:contain}
.ymkrf-rep--card .ymkrf-img__prev:empty::before{
  content:"＋ 写真";color:#a7aaad;font-size:13px;font-weight:700;
}
/* いちばん最後の「＋」の空き枠。押すと1つ増えます */
.ymkrf-rep--card .ymkrf-rep__addcard{
  display:grid;place-items:center;min-height:150px;
  background:#fafafa;border:2px dashed #c3c4c7;border-radius:6px;
  color:#a7aaad;font-size:34px;font-weight:700;line-height:1;cursor:pointer;
}
.ymkrf-rep--card .ymkrf-rep__addcard:hover{
  border-color:#fe3301;color:#fe3301;background:#fff6f3;
}

/* おすすめポイント。1つぶんを「まとまりの見出し」「写真」「文章」に分けます */
.ymkrf-point{position:relative;display:block;padding:14px 34px 14px 30px}
.ymkrf-point .ymkrf-row__handle{position:absolute;left:8px;top:12px}
.ymkrf-point .ymkrf-row__del{position:absolute;right:8px;top:8px}
/* まとまり（おすすめポイント） */
.ymkrf-grp{
  position:relative;border:2px solid #fe3301;border-radius:0 8px 8px 8px;
  padding:14px 14px 6px;margin:38px 0 18px;background:#fffaf8;
}
/* 「オススメ1」は、ルーズリーフの見出しタブ（インデックス）のように、
   枠の上ぶちにくっつけて出します
   （2026/09/18 ユーザー指示「ふせんというより、ルーズリーフにあるやつ」）。
   背景はオレンジ、字は白。 */
.ymkrf-grp__ttl{
  position:absolute;left:-2px;top:-29px;z-index:2;margin:0;
  background:#fe3301;color:#fff;
  padding:5px 20px;border-radius:8px 8px 0 0;
  font-size:16px;font-weight:700;line-height:1.5;letter-spacing:.03em;
}
.ymkrf-grp__del{
  position:absolute;top:8px;right:10px;
  background:none;border:0;color:#b32d2e;font-size:12px;font-weight:700;cursor:pointer;
}
/* ページと同じ順に、たてに積みます
   （2026/09/18 ユーザー指示「まとまりの名前を、ひとことの下に持ってきて」） */
.ymkrf-grp__head{
  display:grid;gap:8px;grid-template-columns:1fr;max-width:520px;
  margin:0 90px 12px 0;
}
/* 「オススメ1 小見出し」「オススメ1」の欄は、赤い枠にして目立たせます
   （2026/09/18 ユーザー指示「小見出しとオススメの枠、赤くして」） */
.ymkrf-grp__head input{
  border:2px solid #fe3301;border-radius:5px;
}
.ymkrf-grp__head input:focus{
  border-color:#c92800;box-shadow:0 0 0 1px #c92800;
}
/* 「オススメ1」の文字は大きく。どのまとまりを触っているかが分かります */
.ymkrf-grp__head .ymkrf-f>span{
  color:#c92800;font-weight:700;font-size:16px;margin-bottom:5px;letter-spacing:.02em;
}
.ymkrf-grp__head .ymkrf-grp__lblsub{font-size:13.5px}
/* 入力する字も、少し大きくします */
.ymkrf-grp__head input{font-size:15px;padding:7px 10px;height:auto}
.ymkrf-grp__points{display:grid;gap:10px}
.ymkrf-point__no{
  display:inline-block;background:#fe3301;color:#fff;border-radius:999px;
  padding:2px 12px;font-size:12px;font-weight:700;margin-bottom:8px;
}
.ymkrf-point{background:#fff;border:1px solid #e0e0e0;border-radius:6px;padding:12px 34px 12px 30px}
.ymkrf-point .ymkrf-point__txt{display:grid;gap:8px;margin-bottom:10px}
.ymkrf-point__grp{
  background:#f0f6fb;border:1px solid #d5e5f2;border-radius:6px;
  padding:9px 12px 11px;margin-bottom:12px;
  display:grid;gap:8px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
}
.ymkrf-point__grpttl{
  grid-column:1/-1;margin:0;font-size:12px;font-weight:700;color:#2b5f86;
}
.ymkrf-point__grpttl span{font-weight:400;color:#5c7b93}
.ymkrf-point__body{display:flex;gap:14px;align-items:flex-start;flex-wrap:wrap}
.ymkrf-point__pics{display:flex;gap:8px;flex:0 0 auto;flex-wrap:wrap;max-width:420px}
.ymkrf-pic{position:relative;width:130px}
.ymkrf-pic__alt,
.ymkrf-pic__cap{
  display:block;width:100%;margin-top:4px;
  font-size:11.5px;padding:3px 6px;line-height:1.5;
  /* 長い文でも見られるよう、右下をつまんで広げられます
     （2026/09/18 ユーザー指示「alt、キャプションの欄、広げられるようにして」） */
  min-height:26px;height:26px;resize:vertical;overflow:auto;
  border:1px solid #8c8f94;border-radius:4px;background:#fff;
}
.ymkrf-pic__alt:focus,
.ymkrf-pic__cap:focus{border-color:#fe3301;box-shadow:0 0 0 1px #fe3301;outline:0}
.ymkrf-pic__cap{border-color:#b9cfa9;background:#fbfff8}
.ymkrf-pic__del{
  position:absolute;top:-6px;right:-6px;z-index:2;
  width:20px;height:20px;line-height:18px;text-align:center;
  border:1px solid #dcdcde;border-radius:50%;background:#fff;
  color:#b32d2e;font-size:13px;font-weight:700;cursor:pointer;padding:0;
}
.ymkrf-pic__del:hover{background:#b32d2e;color:#fff;border-color:#b32d2e}
.ymkrf-pic__add{
  width:56px;align-self:flex-start;min-height:98px;
  background:#fafafa;border:2px dashed #c3c4c7;border-radius:6px;
  color:#a7aaad;font-size:22px;font-weight:700;cursor:pointer;
}
.ymkrf-pic__add:hover{border-color:#fe3301;color:#fe3301;background:#fff6f3}
.ymkrf-point__pics .ymkrf-img{display:block}
.ymkrf-point__pics .ymkrf-img__prev{
  width:100%;height:auto;aspect-ratio:4/3;cursor:pointer;border-radius:6px;
  background:#fff;border:1px dashed #c3c4c7;
}
.ymkrf-point__pics .ymkrf-img__prev:hover{border-color:#fe3301;border-style:solid}
.ymkrf-point__pics .ymkrf-img__prev img{max-width:100%;max-height:100%;object-fit:contain}

/* ─────────────────────────────────────────────
   おすすめオプション
   （2026/09/18 ユーザー指示
     「情報量は少ないので、テキストの入力部分はこんなに要りません。
       田2列にして良いかも。写真も小さくてよい」）
   ・写真 … ページでも小さな正方形なので、正方形の小さい枠に
   ・入力 … 品名／説明／追加金額／補足 を 2列（田の字）に
   ───────────────────────────────────────────── */
.ymkrf-opt{padding-top:10px;padding-bottom:10px}
/* 箱の見出しのところに置いた「枠の見出し」の入力欄
   （2026/09/18 ユーザー指示「ここに枠の見出しを入れたい」） */
/* 箱の見出しでは入力欄を、「表示項目」の一覧では文字だけを出します */
.postbox .hndle .ymkrf-hname{display:none}
#adv-settings .ymkrf-hlbl,
#adv-settings .ymkrf-boxsize{display:none}
.ymkrf-hlbl{display:inline-block;vertical-align:middle}
.ymkrf-hlbl input{
  width:320px;max-width:52vw;margin:-4px 0;padding:4px 9px;
  font-size:14px;font-weight:700;line-height:1.5;color:#1d2327;
  border:2px solid #fe3301;border-radius:5px;background:#fff;
}
.ymkrf-hlbl input::placeholder{color:#9aa0a6;font-weight:600}
.ymkrf-hlbl input:focus{border-color:#c92800;box-shadow:0 0 0 1px #c92800;outline:0}
/* カラー（色見本）の枠。自由に足したり消したりできます */
.ymkrf-cframe{
  position:relative;background:#fff;border:1px solid #e0e0e0;border-radius:8px;
  padding:14px 16px 10px;margin-bottom:14px;
}
.ymkrf-cframe__head{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px}
.ymkrf-cframe__name{font-size:15px;font-weight:700;color:#1d2327}
.ymkrf-cframe__x{
  margin-left:auto;background:none;border:0;cursor:pointer;
  color:#b32d2e;font-size:12px;font-weight:700;padding:2px 4px;
}
.ymkrf-cframe__x:hover{text-decoration:underline}
.ymkrf-cadd{
  display:block;width:100%;padding:12px 0;
  background:#fafafa;border:2px dashed #c3c4c7;border-radius:8px;
  color:#a7aaad;font-size:22px;font-weight:700;line-height:1;cursor:pointer;
}
.ymkrf-cadd:hover{border-color:#fe3301;color:#fe3301;background:#fff6f3}

.ymkrf-opt .ymkrf-opt__pics{max-width:96px;flex:0 0 96px}
.ymkrf-opt .ymkrf-opt__pics .ymkrf-img{width:96px}
.ymkrf-opt .ymkrf-opt__pics .ymkrf-img__prev{aspect-ratio:1/1}
.ymkrf-point.ymkrf-opt .ymkrf-point__txt{
  display:grid;gap:8px 14px;grid-template-columns:repeat(2,minmax(0,1fr));
  flex:1 1 380px;min-width:300px;margin-bottom:0;
}
.ymkrf-point.ymkrf-opt .ymkrf-point__txt textarea{min-height:52px}
.ymkrf-point__pics .ymkrf-img__prev:empty::before{
  content:"＋ 写真";color:#a7aaad;font-size:12px;font-weight:700;
}
.ymkrf-point__txt{flex:1 1 320px;min-width:0;display:grid;gap:8px}
.ymkrf-point__frame{font-size:12.5px;font-weight:700;color:#555;display:flex;align-items:center;gap:6px}

</style>
<script>
jQuery(function($){

  /* 箱の見出しにある入力欄をさわっても、箱が閉じないようにします
     （2026/09/18 ユーザー指示「ここに枠の見出しを入れたい」） */
  $('.ymkrf-hlbl input').on('click mousedown keydown', function(e){ e.stopPropagation(); });

  /* カラーの枠を足す（次のあき枠を出します） */
  $(document).on('click', '.ymkrf-cadd', function(){
    var $off = $('.ymkrf-cframe.is-off').first();
    if ( ! $off.length ) { window.alert('カラーの枠は、これ以上ふやせません。'); return; }
    $off.removeClass('is-off').show();
    $off.find('.ymkrf-cframe__del').val('0');
    $off.find('.ymkrf-hlbl input').trigger('focus');
  });

  /* カラーの枠を消す */
  $(document).on('click', '.ymkrf-cframe__x', function(){
    var $f = $(this).closest('.ymkrf-cframe');
    if ( $('.ymkrf-cframe:not(.is-off)').has('.ymkrf-cframe__del').length <= 1 ) {
      window.alert('カラーの枠は、少なくとも1つ残してください。');
      return;
    }
    if ( ! window.confirm('この枠の見出しと色見本を、ぜんぶ消します。よろしいですか？\n（「更新」を押したときに消えます）') ) return;
    $f.addClass('is-off').hide();
    $f.find('.ymkrf-cframe__del').val('1');
  });

  /* 行を追加（下のボタン、またはいちばん最後の「＋」の枠） */
  $(document).on('click', '.ymkrf-rep__add, .ymkrf-rep__addcard', function(){
    var $rep = $(this).closest('.ymkrf-rep'), key = $rep.data('key');
    var uid  = 'n' + Date.now() + Math.floor(Math.random() * 1000);
    var html = $('.ymkrf-tpl-' + key).html().replace(/__i__/g, uid);
    var $rows = $rep.find('.ymkrf-rep__rows');
    var $add  = $rows.find('.ymkrf-rep__addcard');
    if ($add.length) { $add.before(html); } else { $rows.append(html); }
  });

  /* 行を消す（最後の1行は中身だけ空にする） */
  $(document).on('click', '.ymkrf-row:not(.ymkrf-point) .ymkrf-row__del', function(){
    var $rows = $(this).closest('.ymkrf-rep__rows');
    if ($rows.find('.ymkrf-row').length <= 1) {
      var $r = $(this).closest('.ymkrf-row');
      $r.find('input,textarea').val('');
      $r.find('.ymkrf-img__prev').empty();
      return;
    }
    $(this).closest('.ymkrf-row').remove();
  });

  /* ── おすすめポイント ─────────────────────────── */

  function uid(){ return 'n' + Date.now() + Math.floor(Math.random()*1000); }

  /* Point 1・2・3… の番号を振りなおし、まとまりの見出しの名前もそろえます */
  function ptSync($rep){
    var key = $rep.data('key');
    $rep.find('.ymkrf-grp').each(function(i){
      var $g = $(this);
      $g.find('.ymkrf-grp__num').text(i + 1);
      $g.find('.ymkrf-grp__lblsub').text('オススメ' + (i + 1) + '　小見出し');
      $g.find('.ymkrf-grp__lbl').text('オススメ' + (i + 1));
      $g.find('.ymkrf-point').each(function(i){
        $(this).find('.ymkrf-point__no').text('Point ' + (i + 1));
      });
      /* 見出しは、そのまとまりの1つめのポイントの名前で送ります */
      var first = $g.find('.ymkrf-point').first().attr('data-idx');
      if (!first) return;
      $g.find('.ymkrf-grp__gsub').attr('name', key + '[' + first + '][gsub]');
      $g.find('.ymkrf-grp__gttl').attr('name', key + '[' + first + '][gttl]');
    });
  }

  /* まとまりを足す */
  $(document).on('click', '.ymkrf-grp__add', function(){
    var $rep = $(this).closest('.ymkrf-rep');
    $rep.find('.ymkrf-grps').append($('.ymkrf-tpl-grp').html().replace(/__i__/g, uid()));
    ptSync($rep);
  });

  /* まとまりを消す */
  $(document).on('click', '.ymkrf-grp__del', function(){
    var $rep = $(this).closest('.ymkrf-rep');
    if ($rep.find('.ymkrf-grp').length <= 1) {
      $(this).closest('.ymkrf-grp').find('input[type=text],textarea').val('');
      $(this).closest('.ymkrf-grp').find('.ymkrf-pic').remove();
      return;
    }
    $(this).closest('.ymkrf-grp').remove();
    ptSync($rep);
  });

  /* ポイントを足す */
  $(document).on('click', '.ymkrf-pt__add', function(){
    var $rep = $(this).closest('.ymkrf-rep');
    var $g   = $(this).closest('.ymkrf-grp');
    $g.find('.ymkrf-grp__points').append($('.ymkrf-tpl-pt').html().replace(/__i__/g, uid()));
    ptSync($rep);
  });

  /* ポイントを消す */
  $(document).on('click', '.ymkrf-point .ymkrf-row__del', function(e){
    e.stopPropagation();
    var $rep = $(this).closest('.ymkrf-rep');
    var $g   = $(this).closest('.ymkrf-grp');
    if ($g.find('.ymkrf-point').length <= 1) {
      var $r = $(this).closest('.ymkrf-point');
      $r.find('input[type=text],textarea').val('');
      $r.find('.ymkrf-pic').remove();
      return;
    }
    $(this).closest('.ymkrf-point').remove();
    ptSync($rep);
  });

  /* ポイントの写真を1枚足す */
  $(document).on('click', '.ymkrf-pic__add', function(){
    var $pics = $(this).closest('.ymkrf-point__pics');
    var nm    = $pics.find('input[type=hidden]').first().attr('name');
    if (nm) nm = nm.replace(/\[alts\]/, '[imgs]');
    if (!nm) {
      /* まだ1枚も無いときは、この行の名前を作ります */
      var key = $(this).closest('.ymkrf-rep').data('key');
      var idx = $(this).closest('.ymkrf-rep__rows').find('.ymkrf-row').index($(this).closest('.ymkrf-row'));
      var di = $(this).closest('.ymkrf-row').attr('data-idx');
      nm = key + '[' + (di !== undefined ? di : idx) + '][imgs][]';
    }
    var am = nm.replace('[imgs][]', '[alts][]');
    $(this).before(
      '<span class="ymkrf-img ymkrf-pic">'
      + '<span class="ymkrf-img__prev ymkrf-img__pick" title="押すと写真をえらべます"></span>'
      + '<input type="hidden" name="' + nm + '" value="">'
      + '<button type="button" class="ymkrf-pic__del" title="この写真を消す">×</button>'
      + '<textarea class="ymkrf-pic__alt" rows="1" name="' + am + '" placeholder="alt"></textarea>'
      + '<textarea class="ymkrf-pic__cap" rows="1" name="' + nm.replace('[imgs][]', '[caps][]') + '" placeholder="キャプション"></textarea>'
      + '</span>'
    );
  });

  /* ポイントの写真を1枚消す */
  $(document).on('click', '.ymkrf-pic__del', function(){
    $(this).closest('.ymkrf-pic').remove();
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
      /* メディアに入っているALTを、うすい字で見せます
         （2026/09/18 ユーザー指示「自動で入っているalt、ダッシュボードにも表示して」） */
      $box.find('.ymkrf-pic__alt').attr('placeholder', (a.alt && a.alt !== '') ? a.alt : 'alt');
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
    $('.ymkrf-rep__rows').not('.ymkrf-rep--card .ymkrf-rep__rows')
      .sortable({ handle:'.ymkrf-row__handle', axis:'y', cursor:'grabbing', items:'.ymkrf-row' });
    /* カードは横にもならぶので、たて方向だけに固定しません */
    $('.ymkrf-rep--card .ymkrf-rep__rows')
      .sortable({ handle:'.ymkrf-row__handle', cursor:'grabbing', items:'.ymkrf-row' });
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

	/* 「F」「SS」のように記号だけ入っているときは、「グレード」を付けて出します
	   （2026/09/18 ユーザー指示「グレードという文字は自動でつけて」） */
	if ( preg_match( '/^(SSS|SS|S|A|B|C|D|E|F|G|H|I|J)$/i', $g ) ) {
		return strtoupper( $g ) . 'グレード';
	}
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


/* ============================================================
   メーカーの選択肢を、そのカテゴリで使うものだけにします
   ------------------------------------------------------------
   （2026/09/17 ユーザー指示
     「登録画面で出てくるメーカーは、既存の登録してあるメーカーのみに
       しておいて。たくさんあって選択しにくい。例えばウッドワンなんか、
       洗面化粧台にしかないはずなのに、キッチンやエコキュートにあるのうざい」）

   いま登録してある商品を見て、「このカテゴリでは、このメーカーが使われている」
   という表を作ります。商品の登録画面では、えらんだカテゴリに関係のない
   メーカーを隠します。

   ★消すのではなく、隠すだけです。
     新しいメーカーを足したいときは「ぜんぶ出す」を押してください。
   ============================================================ */

/** カテゴリ（term_id）→ そのカテゴリで使われているメーカー（term_id）の表 */
function ymkrf_maker_map( $force = false ) {

	$hit = get_transient( 'ymkrf_maker_map' );
	if ( ! $force && is_array( $hit ) ) return $hit;

	$map = array();

	$ids = get_posts( array(
		'post_type'      => 'ymkrf_product',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );

	foreach ( (array) $ids as $pid ) {

		$cats = wp_get_object_terms( $pid, 'ymkrf_product_cat', array( 'fields' => 'ids' ) );
		$mks  = wp_get_object_terms( $pid, 'ymkrf_maker',       array( 'fields' => 'ids' ) );
		if ( is_wp_error( $cats ) || is_wp_error( $mks ) ) continue;
		if ( ! $cats || ! $mks ) continue;

		foreach ( $cats as $c ) {
			if ( ! isset( $map[ $c ] ) ) $map[ $c ] = array();
			foreach ( $mks as $m ) {
				if ( ! in_array( $m, $map[ $c ], true ) ) $map[ $c ][] = (int) $m;
			}
		}
	}

	set_transient( 'ymkrf_maker_map', $map, DAY_IN_SECONDS );
	return $map;
}

/** 商品を保存したら、表を作りなおします */
add_action( 'save_post_ymkrf_product', function () {
	delete_transient( 'ymkrf_maker_map' );
}, 99 );

/** 商品の登録画面で、関係のないメーカーを隠します */
add_action( 'admin_footer', function () {

	$s = get_current_screen();
	if ( ! $s || $s->post_type !== 'ymkrf_product' || $s->base !== 'post' ) return;

	$map = ymkrf_maker_map();
	if ( ! $map ) return;
	?>
	<script>
	jQuery(function ($) {

	  var MAP = <?php echo wp_json_encode( $map ); ?>;
	  var $mk = $('#ymkrf_makerchecklist');
	  if (!$mk.length) return;

	  var all = false;

	  function refresh() {

	    if (all) { $mk.find('li').show(); return; }

	    /* いまチェックされている商品カテゴリ */
	    var cats = [];
	    $('#ymkrf_product_catchecklist input:checked').each(function () {
	      cats.push(String($(this).val()));
	    });

	    /* そのカテゴリで使われているメーカー */
	    var ok = {};
	    cats.forEach(function (c) {
	      (MAP[c] || []).forEach(function (m) { ok[String(m)] = true; });
	    });

	    /* カテゴリ未選択なら、ぜんぶ出します */
	    if (!cats.length) { $mk.find('li').show(); return; }

	    $mk.find('li').each(function () {
	      var $li = $(this), $in = $li.find('input').first();
	      if (!$in.length) return;
	      /* すでにチェックが入っているものは、かならず出します */
	      $li.toggle( ok[String($in.val())] === true || $in.is(':checked') );
	    });
	  }

	  /* 「ぜんぶ出す」の切りかえ */
	  $mk.closest('.inside').append(
	    '<p style="margin:8px 0 0"><a href="#" id="ymkrf-mk-all" class="button button-small">'
	    + 'ぜんぶのメーカーを出す</a>'
	    + '<span class="description" style="display:block;margin-top:4px">'
	    + 'いまは、このカテゴリで使っているメーカーだけを出しています。</span></p>'
	  );

	  $(document).on('click', '#ymkrf-mk-all', function (e) {
	    e.preventDefault();
	    all = !all;
	    $(this).text(all ? 'このカテゴリのメーカーだけにする' : 'ぜんぶのメーカーを出す');
	    refresh();
	  });

	  $(document).on('change', '#ymkrf_product_catchecklist input', refresh);
	  refresh();
	});
	</script>
	<?php
} );


/* 分類の欄の「すべて／よく使うもの」のタブは使わないので消します
   （2026/09/17 ユーザー指示「よく使うもの　ってテキスト削除して」）
   タブが無くても「すべて」の一覧がそのまま出ます。 */
add_action( 'admin_head', function () {

	$s = get_current_screen();
	if ( ! $s || $s->base !== 'post' ) return;
	if ( strpos( (string) $s->post_type, 'ymkrf_' ) !== 0 ) return;

	echo '<style>.category-tabs{display:none !important}</style>';
} );


/* ============================================================
   足りない商品カテゴリを作ります
   ------------------------------------------------------------
   （2026/09/17 ユーザー「内装・改装が商品一覧ぺージに無いけど」）

   はじめの取り込みのあとに増やしたカテゴリは、
   ダッシュボードの「商品」に出てきません。
   ここで、足りないものだけを作ります。
   すでにあるカテゴリは、いっさいさわりません。
   ============================================================ */
add_action( 'admin_init', function () {

	$ver = '2026-09-17';
	if ( get_option( 'ymkrf_product_cat_fill' ) === $ver ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;
	if ( ! taxonomy_exists( 'ymkrf_product_cat' ) ) return;

	$want = array(
		'kitchen'    => 'キッチン',
		'bathroom'   => 'お風呂',
		'toilet'     => 'トイレ',
		'lavatory'   => '洗面化粧台',
		'boiler'     => '給湯器',
		'ecocute'    => 'エコキュート',
		'window'     => '窓・玄関ドア',
		'interior'   => '内装・改装',
		/* 外壁・屋根は商品から外しましたが、専用ページがあるので消しません */
		'outer-wall' => '外壁・屋根',
	);

	$made = array();
	foreach ( $want as $slug => $name ) {
		if ( term_exists( $slug, 'ymkrf_product_cat' ) ) continue;
		$r = wp_insert_term( $name, 'ymkrf_product_cat', array( 'slug' => $slug ) );
		if ( ! is_wp_error( $r ) ) $made[] = $name;
	}

	update_option( 'ymkrf_product_cat_fill', $ver, false );
	if ( $made ) set_transient( 'ymkrf_product_cat_made', $made, 120 );
}, 8 );

/* 作ったことを1度だけお知らせします */
add_action( 'admin_notices', function () {
	$made = get_transient( 'ymkrf_product_cat_made' );
	if ( ! $made ) return;
	delete_transient( 'ymkrf_product_cat_made' );
	echo '<div class="notice notice-success is-dismissible"><p>'
	   . '足りなかった商品カテゴリを作りました：<b>' . esc_html( implode( '、', (array) $made ) ) . '</b>'
	   . '</p></div>';
} );


/* ============================================================
   内装・改装のパック料金を、商品として登録します
   ------------------------------------------------------------
   （2026/09/17 ユーザー指示
     「商品のところにカテゴリ分けして商品ページ作った方が見やすくない?」
     「どこにぺージがあるかわからないよ、それでは」）

   ページの中に直接書いていると、従業員の方が直す場所を見つけられません。
   ほかのカテゴリと同じように「商品」として登録して、
   ダッシュボードから直せるようにします。

   ★1回だけ走ります。すでに同じ名前の商品があれば、作りません。
     金額や文章を直したあとで、もういちど走って上書きすることはありません。
   ============================================================ */
add_action( 'admin_init', function () {

	$ver = '2026-09-17a';
	if ( get_option( 'ymkrf_interior_packs' ) === $ver ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;

	$term = get_term_by( 'slug', 'interior', 'ymkrf_product_cat' );
	if ( ! $term || is_wp_error( $term ) ) return;      /* カテゴリができてから */

	/* name  … 商品名
	   price … 込み価格（円・税込）
	   catch … 商品名の上に出る小さな赤い文字
	   feat  … 特徴（1行ずつ）
	   order … 並び順（小さいほど上） */
	$packs = array(
		array(
			'name'  => '畳 → フローリング（6帖パック）',
			'price' => 198000,
			'catch' => '床リフォーム',
			'feat'  => array(
				array( 'ttl' => '工事の中身', 'body' => "重ね張りではなく、新規に下地を作ります。" ),
			),
			'order' => 10,
		),
		array(
			'name'  => '畳 → フローリング（8帖パック）',
			'price' => 238000,
			'catch' => '床リフォーム',
			'feat'  => array(
				array( 'ttl' => '工事の中身', 'body' => "重ね張りではなく、新規に下地を作ります。" ),
			),
			'order' => 20,
		),
		array(
			'name'  => 'フローリング → フローリング（6帖パック）',
			'price' => 158000,
			'catch' => '床リフォーム',
			'feat'  => array(
				array( 'ttl' => '工事の中身', 'body' => "既存のフローリングに重ね貼りします。\n既存の床の点検補強も行います。" ),
			),
			'order' => 30,
		),
		array(
			'name'  => 'フローリング → フローリング（8帖パック）',
			'price' => 188000,
			'catch' => '床リフォーム',
			'feat'  => array(
				array( 'ttl' => '工事の中身', 'body' => "既存のフローリングに重ね貼りします。\n既存の床の点検補強も行います。" ),
			),
			'order' => 40,
		),
		array(
			'name'  => '和室 → 洋室リフォーム（6帖）',
			'price' => 468000,
			'catch' => '部屋リフォーム',
			'feat'  => array(
				array( 'ttl' => '工事の中身', 'body' => "畳 → フローリング\n天井：クロス貼り\n壁：耐火ボード貼り＋クロス貼り" ),
			),
			'order' => 50,
		),
		array(
			'name'  => '洋室リフレッシュ（6帖）',
			'price' => 268000,
			'catch' => '部屋リフォーム',
			'feat'  => array(
				array( 'ttl' => '工事の中身', 'body' => "フローリング重ね貼り\n天井：クロス貼り\n壁：クロス貼り" ),
			),
			'order' => 60,
		),
	);

	$made = 0;
	foreach ( $packs as $p ) {

		/* 同じ名前の商品がすでにあれば、作りません */
		$dup = get_posts( array(
			'post_type'      => 'ymkrf_product',
			'post_status'    => 'any',
			'title'          => $p['name'],
			'posts_per_page' => 1,
			'fields'         => 'ids',
		) );
		if ( $dup ) continue;

		$id = wp_insert_post( array(
			'post_type'   => 'ymkrf_product',
			'post_title'  => $p['name'],
			'post_status' => 'publish',
		) );
		if ( is_wp_error( $id ) || ! $id ) continue;

		wp_set_object_terms( $id, array( (int) $term->term_id ), 'ymkrf_product_cat' );

		/* 込み価格は「商品代」に入れます。標準工事費は空です
		   （込み価格＝標準工事費＋商品代 で計算される決まりのため） */
		update_post_meta( $id, '_ymkrf_work',  '' );
		update_post_meta( $id, '_ymkrf_item',  (int) $p['price'] );
		update_post_meta( $id, '_ymkrf_total', (int) $p['price'] );
		update_post_meta( $id, '_ymkrf_catch', $p['catch'] );
		update_post_meta( $id, '_ymkrf_order', (int) $p['order'] );
		update_post_meta( $id, '_ymkrf_speclist', $p['feat'] );

		$made++;
	}

	update_option( 'ymkrf_interior_packs', $ver, false );
	if ( $made ) set_transient( 'ymkrf_interior_made', $made, 120 );
}, 9 );

add_action( 'admin_notices', function () {
	$n = get_transient( 'ymkrf_interior_made' );
	if ( ! $n ) return;
	delete_transient( 'ymkrf_interior_made' );
	echo '<div class="notice notice-success is-dismissible"><p>'
	   . '内装・改装のパック料金を <b>' . (int) $n . '件</b> 商品として登録しました。'
	   . '金額や文章は <b>商品 ＞ 内装・改装</b> から直せます。'
	   . '</p></div>';
} );


/* ============================================================
   内装・改装の登録画面から、使わない箱を消します
   ------------------------------------------------------------
   （2026/09/17 ユーザー指示
     「並び順は右にあるのでセンターにあるやつは削除
       展示店舗も削除　メーカーも削除　カテゴリも不要」）

   ★カテゴリの箱を消しても、商品はカテゴリに入ったままです。
     内装・改装の商品は、はじめから内装・改装に入っているためです。
     保存のときに外れないよう、下でもういちど付けなおしています。
   ============================================================ */
add_action( 'add_meta_boxes', function () {

	if ( ! function_exists( 'ymkrf_product_current_cat' ) ) return;
	if ( ymkrf_product_current_cat( get_the_ID() ) !== 'interior' ) return;

	remove_meta_box( 'ymkrf_product_catdiv', 'ymkrf_product', 'side' );   /* 商品カテゴリ */
	remove_meta_box( 'ymkrf_makerdiv',       'ymkrf_product', 'side' );   /* メーカー */
	remove_meta_box( 'ymkrf_shopdiv',        'ymkrf_product', 'side' );   /* 展示店舗 */
}, 100 );

/* 内装・改装の商品は、保存のときにカテゴリが外れないようにします */
add_action( 'save_post_ymkrf_product', function ( $post_id ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;

	/* 画面にカテゴリの箱が無かったときだけ、付けなおします */
	if ( ! isset( $_POST['ymkrf_interior_keep'] ) ) return;

	$term = get_term_by( 'slug', 'interior', 'ymkrf_product_cat' );
	if ( $term && ! is_wp_error( $term ) ) {
		wp_set_object_terms( $post_id, array( (int) $term->term_id ), 'ymkrf_product_cat' );
	}
}, 5 );

/* カテゴリの箱を消した画面に、目じるしを1つ置いておきます */
add_action( 'edit_form_after_title', function ( $post ) {
	if ( ! $post || $post->post_type !== 'ymkrf_product' ) return;
	if ( ! function_exists( 'ymkrf_product_current_cat' ) ) return;
	if ( ymkrf_product_current_cat( $post->ID ) !== 'interior' ) return;
	echo '<input type="hidden" name="ymkrf_interior_keep" value="1">';
} );

/* 「並び順」は、まん中の入力欄には出しません（右の「公開」の下にあります）。
   上の ymkrf_product_fields_for() の $keep に入れていないので、自動で出ません。
   （2026/09/17 ユーザー指示「並び順は右にあるのでセンターにあるやつは削除」） */


/* ============================================================
   Before / After の見くらべスライダー（商品）
   （2026/09/17 ユーザー指示「施工事例みたいに写真をスライドさせてほしい」）

   施工事例と同じ形（.p-compare）で出します。
   動かす仕組みは assets/js/common.js の [data-compare] が
   サイト全体で共通に受けもっているので、ここでは形を作るだけです。

   写真を入れるのは 商品 ＞ 内装・改装 ＞「Before / After の写真」です。
   ============================================================ */

/** 写真のよこ／たての比（わからないときは 1.33） */
function ymkrf_product_ba_ar( $att_id ) {
	$m = wp_get_attachment_metadata( (int) $att_id );
	if ( is_array( $m ) && ! empty( $m['width'] ) && ! empty( $m['height'] ) ) {
		return (float) $m['width'] / (float) $m['height'];
	}
	return 1.33;
}

/**
 * Before / After を、左右に動かして見くらべる形にします。
 * 片方しか無いときは、ふつうの写真1枚として出します。
 *
 * @param int    $before_id 施工前の写真
 * @param int    $after_id  施工後の写真
 * @param string $name      商品名（画像の説明に使います）
 */
function ymkrf_product_compare( $before_id, $after_id, $name = '' ) {

	$before_id = (int) $before_id;
	$after_id  = (int) $after_id;
	if ( ! $before_id && ! $after_id ) return '';

	$alt = function ( $which ) use ( $name ) {
		$jp = ( $which === 'before' ) ? '施工前' : '施工後';
		return trim( $name ) !== '' ? $name . 'の' . $jp . 'のようす' : $jp . 'のようす';
	};

	$img = function ( $id, $which ) use ( $alt ) {
		$url = wp_get_attachment_image_url( (int) $id, 'large' );
		if ( ! $url ) return '';
		return '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt( $which ) ) . '"'
		     . ' loading="lazy" decoding="async">';
	};

	/* 片方だけのとき */
	if ( ! $before_id || ! $after_id ) {
		$which = $after_id ? 'after' : 'before';
		$one   = $img( $after_id ? $after_id : $before_id, $which );
		if ( $one === '' ) return '';
		return '<div class="p-pack__solo">' . $one . '</div>';
	}

	/* 枠の形は、2枚の写真の平均に合わせます */
	$ar = ( ymkrf_product_ba_ar( $before_id ) + ymkrf_product_ba_ar( $after_id ) ) / 2;
	$ar = max( 0.8, min( 1.9, $ar ) );

	$h  = '<div class="p-compare" data-compare style="--pos:50%;--cmp-ar:' . esc_attr( round( $ar, 3 ) ) . '">';
	$h .= '<div class="p-compare__layer p-compare__layer--before">' . $img( $before_id, 'before' ) . '</div>';
	$h .= '<div class="p-compare__layer p-compare__layer--after">'  . $img( $after_id,  'after'  ) . '</div>';
	$h .= '<span class="p-compare__tag p-compare__tag--before">BEFORE</span>';
	$h .= '<span class="p-compare__tag p-compare__tag--after">AFTER</span>';
	$h .= '<span class="p-compare__handle"></span>';
	$h .= '<span class="p-compare__hint">← 左右に動かして見くらべる →</span>';
	$h .= '</div>';
	return $h;
}


/* ============================================================
   商品一覧の、使わない絞りこみを出しません
   ------------------------------------------------------------
   （2026/09/18 ユーザー「すべて／公開済み／下書き／ゴミ箱／非公開、
     分類のリンク、日付で絞り込む　これらは不要です」）

   ★もし、またゴミ箱や下書きを見たくなったら、
     下の .subsubsub の行を消してください。
     （URLの後ろに &post_status=trash を付けても開けます）
   ============================================================ */
add_filter( 'disable_months_dropdown', function ( $off, $type ) {
	return ( $type === 'ymkrf_product' ) ? true : $off;
}, 10, 2 );

add_action( 'admin_head-edit.php', function () {
	$s = get_current_screen();
	if ( ! $s || $s->post_type !== 'ymkrf_product' ) return;
	echo '<style>.post-type-ymkrf_product .subsubsub{display:none !important}</style>' . "\n";
} );


/* ============================================================
   キッチンの工期を、ぜんぶ4日にします（1度だけ）
   ------------------------------------------------------------
   （2026/09/18 ユーザー指示
     「キッチンの施工日数は3日から、全てキッチンは4日にして」）

   ★もう一度走らせたいときは、下の日付（版）を新しくしてください。
   ============================================================ */
add_action( 'admin_init', function () {

	$ver = '2026-09-18-kitchen4';
	if ( get_option( 'ymkrf_days_fix' ) === $ver ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;
	if ( ! taxonomy_exists( 'ymkrf_product_cat' ) ) return;

	$ids = get_posts( array(
		'post_type'      => 'ymkrf_product',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'tax_query'      => array( array(
			'taxonomy' => 'ymkrf_product_cat',
			'field'    => 'slug',
			'terms'    => 'kitchen',
		) ),
	) );

	$n = 0;
	foreach ( (array) $ids as $id ) {
		if ( (int) get_post_meta( $id, '_ymkrf_days', true ) === 4 ) continue;
		update_post_meta( $id, '_ymkrf_days', 4 );
		$n++;
	}

	update_option( 'ymkrf_days_fix', $ver, false );
	if ( $n ) set_transient( 'ymkrf_days_fixed', $n, 120 );
} );

add_action( 'admin_notices', function () {
	$n = get_transient( 'ymkrf_days_fixed' );
	if ( ! $n ) return;
	delete_transient( 'ymkrf_days_fixed' );
	echo '<div class="notice notice-success is-dismissible"><p>'
	   . 'キッチンの工期を <b>' . (int) $n . '件</b> 4日に直しました。'
	   . '</p></div>';
} );


/* ============================================================
   「商品データ（基本）」は、かならずいちばん上に
   ------------------------------------------------------------
   （2026/09/18 ユーザー指示「商品データ（基本）は必ず一番上に」）

   WordPressは、箱をドラッグして並べかえた順番を人ごとに覚えています。
   そのため、うっかり下げてしまうと、次に開いたときも下のままになります。
   ここで、いつも先頭に来るようにそろえます。
   ============================================================ */
add_filter( 'get_user_option_meta-box-order_ymkrf_product', function ( $order ) {

	if ( ! is_array( $order ) ) return $order;

	$me = 'ymkrf_product_basic';

	/* ほかの列に入っていたら、そこからは外します */
	foreach ( array( 'side', 'advanced' ) as $col ) {
		if ( empty( $order[ $col ] ) ) continue;
		$ids = array_values( array_diff( array_filter( explode( ',', $order[ $col ] ) ), array( $me ) ) );
		$order[ $col ] = implode( ',', $ids );
	}

	$ids = ! empty( $order['normal'] ) ? array_filter( explode( ',', $order['normal'] ) ) : array();
	$ids = array_values( array_diff( $ids, array( $me ) ) );
	array_unshift( $ids, $me );
	$order['normal'] = implode( ',', $ids );

	return $order;
} );
