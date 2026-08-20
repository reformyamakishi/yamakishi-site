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
		'_ymkrf_work'    => array( '標準工事費（円）', 'number', '例：240000', '数字だけ。カンマや「円」は不要です' ),
		'_ymkrf_item'    => array( '商品代（円）',     'number', '例：358000', '数字だけ。カンマや「円」は不要です' ),
		'_ymkrf_days'    => array( '工期（日数）',     'number', '例：3', '数字だけ。「日」は自動で付きます' ),
		'_ymkrf_daystext'=> array( '工期の書き方',     'text',   '例：半日', '「半日」など、日数で書けないときだけ入れてください。入れると上の日数より優先されます' ),
		'_ymkrf_pt1'     => array( '特徴 1',           'text',   '例：お手頃価格', '' ),
		'_ymkrf_pt2'     => array( '特徴 2',           'text',   '例：収納抜群', '' ),
		'_ymkrf_pt3'     => array( '特徴 3',           'text',   '例：おそうじ楽々', '' ),
		'_ymkrf_caution' => array( '写真の注意書き',   'text',   '例：※写真はイメージです。', '商品写真の下に小さく出ます' ),
	);
}

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

	echo '<table class="ymkrf-tbl">';
	foreach ( ymkrf_product_fields() as $key => $f ) {
		printf(
			'<tr><th><label for="%1$s">%2$s</label></th><td>
			   <input type="%3$s" id="%1$s" name="%1$s" value="%4$s" placeholder="%5$s">%6$s</td></tr>',
			esc_attr( $key ), esc_html( $f[0] ), esc_attr( $f[1] ),
			esc_attr( get_post_meta( $post->ID, $key, true ) ), esc_attr( $f[2] ),
			$f[3] ? '<span class="ymkrf-note">' . esc_html( $f[3] ) . '</span>' : ''
		);
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
	$new = array();
	foreach ( $cols as $k => $v ) {
		$new[ $k ] = $v;
		if ( $k === 'title' ) {
			$new['ymkrf_thumb'] = '写真';
			$new['ymkrf_grade'] = 'グレード';
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
        すべて出したい場合は、下の 'hide_empty' を false にしてください。
   ------------------------------------------------------------ */
add_action( 'admin_menu', function () {

	$terms = get_terms( array(
		'taxonomy'   => 'ymkrf_product_cat',
		'hide_empty' => true,
		'orderby'    => 'count',
		'order'      => 'DESC',
	) );
	if ( is_wp_error( $terms ) || ! $terms ) return;

	$i = 0;
	foreach ( $terms as $t ) {
		add_submenu_page(
			'edit.php?post_type=ymkrf_product',                       // 親メニュー
			$t->name . 'の商品',                                       // ページの見出し
			'　└ ' . $t->name . '（' . $t->count . '）',               // メニューに出る文字
			'edit_posts',
			'edit.php?post_type=ymkrf_product&ymkrf_product_cat=' . $t->slug,
			'',
			11 + $i                                                    // 「新しい商品」のすぐ下
		);
		$i++;
	}
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

	return array(
		'catch'    => $m( '_ymkrf_catch' ),
		'grade'    => $m( '_ymkrf_grade' ),
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
		'cats'     => $terms( 'ymkrf_product_cat' ),
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
