<?php
/**
 * カテゴリ別の商品ページ ─ リフォームヤマキシ
 *
 * 置き場所： wp-content/themes/ymkrf/taxonomy-ymkrf_product_cat.php
 *
 * URL の例
 *   /products/kitchen/    … キッチン
 *   /products/bathroom/   … お風呂
 *
 * ページの構成
 *   1. 紹介（下の $intro に書いたカテゴリだけ出ます）
 *   2. その分類の商品一覧（込み価格の安い順）
 *
 * 他のカテゴリにも同じ紹介を付けたいときは、$intro に
 * そのカテゴリのスラッグでひとかたまり足してください。
 * 書かなければ紹介は出ず、商品一覧だけのページになります。
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$term = get_queried_object();
$slug = ( $term && ! is_wp_error( $term ) ) ? $term->slug : '';
$name = ( $term && ! is_wp_error( $term ) ) ? $term->name : '商品';

/* ============================================================
   カテゴリごとの紹介文
   ============================================================ */
$intro = array(

	'kitchen' => array(

		'en'    => 'KITCHEN',
		'title' => 'キッチンリフォーム',
		/* 見出しの背景写真。テーマの assets からの相対パスで書いてください */
		'hero'  => 'assets/img/products/richelle/richelle-main.jpg',
		'lead'  => '本体・標準工事費・古いキッチンの処分費まで込みのパック価格でご案内します。',

		/* --- ブランド紹介 --- */
		'brandsub'  => '家事の負担を軽くする',
		'brand'     => 'キッチンマルシェ',
		'brandtext' => 'リフォームヤマキシの「キッチンマルシェ」とは？'
		             . '<br>「奥様の家事をもっとラクに！」をテーマに、'
		             . '選び抜いたキッチンを取り揃えたカテゴリーブランドです。',

		/* --- 3つのこだわり --- */
		'points' => array(
			array( 'chara' => 'char-otoku',     'name' => 'お得',
			       'text'  => 'キッチン標準工事費・撤去費用・キッチンパネル代などもコミコミ！' ),
			array( 'chara' => 'char-hinshitsu', 'name' => '品質',
			       'text'  => '経験豊富な自社職人を中心に、質の良い丁寧な工事を致します！' ),
			array( 'chara' => 'char-anshin',    'name' => '安心',
			       'text'  => '商品延長10年保証・工事保証5年・24時間365日トラブル対応付き！' ),
		),
		/* こだわりの下に出す、標準工事費の内訳 */

		/* --- お悩み --- */
		'worrytitle' => 'キッチンのお悩み',
		'worryintro' => 'こんなことで悩んでいませんか？',
		'worries'    => array(
			'整理整頓が出来なくて……',
			'油汚れがなかなか落ちない……',
			'換気扇の掃除が嫌い',
			'収納がなくて物であふれてる',
			'食器洗い乾燥機が欲しい〜',
			'家族と会話しながら家事がしたいのに……',
			'排水溝から臭いがする……',
			'動線のせいで効率が悪いなぁ……',
		),
		'worrylead' => '',

		/* --- 解決できます --- */
		'solvesub'   => '実は、そのお悩み',
		'solvetitle' => '最新キッチンで解決できます！',
		'tags'       => array(),
		'tagnote'    => '※各機種によって異なります　※オプションの場合もございます',

		'solutions' => array(
			array(
				'img'   => 'kitchen-sol-storage.jpg',
				'title' => '使いやすさと収納量を両立',
				'lead'  => '立体構造で収納をムダなく活用。よく使う道具が、立ち位置を変えずに取り出せます。',
				'alt'   => '立体構造で道具がたくさん入る引き出し収納',
			),
			array(
				'img'   => 'kitchen-sol-fan.jpg',
				'title' => 'ファンを自動洗浄',
				'lead'  => '給湯トレイにお湯を入れてボタンを押すだけ。約10年間、ファンを外さずにお掃除できます。',
				'alt'   => 'レンジフードの自動洗浄のしくみ図',
			),
			array(
				'img'   => 'kitchen-sol-faucet.jpg',
				'title' => '浄水も使えるオールインワン浄水栓',
				'lead'  => 'カートリッジは水栓に内蔵。取替も簡単で、お湯の使いすぎを防ぐエコハンドル付き。',
				'alt'   => '浄水器を内蔵したオールインワン浄水栓',
			),
			array(
				'img'   => 'kitchen-sol-wall.jpg',
				'title' => '「手が届く」高さで使えるユニット',
				'lead'  => '収納棚が目の高さまで降りてきて、必要な物がすぐ取り出せます。耐震ロック付き。',
				'alt'   => '目の高さまで降りてくるウォールユニット',
			),
			array(
				'img'   => 'kitchen-sol-ceramic.jpg',
				'title' => '高温フライパンもOKの素材',
				'lead'  => '熱・キズ・汚れに強いセラミック。焼き物ならではの上質な風合いも魅力です。',
				'alt'   => 'セラミックの天板に高温のフライパンを置いたところ',
			),
			array(
				'img'   => 'kitchen-sol-drain.jpg',
				'title' => 'うず水流で洗浄しながら排水',
				'lead'  => '普段どおりシンクを使うだけで、うず状の水流が排水口の汚れを洗浄します。',
				'alt'   => 'うず状の水流が排水口の汚れを洗い流すところ',
			),
		),
	),

	'bathroom' => array(

		'en'    => 'BATHROOM',
		'title' => 'お風呂リフォーム',
		/* 見出しの写真。あたたかい床と足もとの写真です。
		   横長の帯に敷くため、写真の上（壁・ドアの部分）を落として、
		   左右は床の柄をのばして幅いっぱいにしてあります。 */
		'hero'  => 'assets/img/products/head-bathroom.jpg',
		'lead'  => '本体・標準工事費・古いお風呂の解体処分費まで込みのパック価格でご案内します。',

		/* --- ブランド紹介（お風呂は専用のブランド名を付けていません） --- */
		'brandsub'  => '毎日の入浴が、もっと心地よく',
		'brand'     => 'ユニットバスリフォームパック',
		'brandtext' => '在来浴室からの取り替えも承ります。'
		             . '<br>断熱浴槽・お掃除のしやすさを基準に、選び抜いた機種をご用意しました。',

		/* --- 3つのこだわり --- */
		'points' => array(
			array( 'chara' => 'char-otoku',     'name' => 'お得',
			       'text'  => 'お風呂標準工事費・解体撤去費用・脱衣場側の内装工事などもコミコミ！' ),
			array( 'chara' => 'char-hinshitsu', 'name' => '品質',
			       'text'  => '経験豊富な自社職人を中心に、質の良い丁寧な工事を致します！' ),
			array( 'chara' => 'char-anshin',    'name' => '安心',
			       'text'  => '商品延長10年保証・工事保証5年・24時間365日トラブル対応付き！' ),
		),

		/* --- お悩み（いまは非表示） --- */
		'worrytitle' => 'お風呂のお悩み',
		'worryintro' => 'こんなことで悩んでいませんか？',
		'worries'    => array(),
		'worrylead'  => '',
		'solvesub'   => '実は、そのお悩み',
		'solvetitle' => '最新のお風呂で解決できます！',
		'tags'       => array(),
		'tagnote'    => '',
		'solutions'  => array(),
	),

	'toilet' => array(

		'en'    => 'TOILET',
		'title' => 'トイレリフォーム',
		'hero'  => 'assets/img/products/head-toilet.jpg',
		'lead'  => '便器・便座・標準工事費・古いトイレの解体処分費まで込みのパック価格でご案内します。',

		/* --- ブランド紹介 --- */
		'brandsub'  => '毎日使う場所だから、清潔に、気持ちよく',
		'brand'     => 'トイレリフォームパック',
		'brandtext' => '半日で入れ替えができます。'
		             . '<br>お掃除のしやすさと節水を基準に、選び抜いた機種をご用意しました。',

		/* --- 3つのこだわり --- */
		'points' => array(
			array( 'chara' => 'char-otoku',     'name' => 'お得',
			       'text'  => 'トイレ標準工事費・古い便器の解体撤去費用もコミコミ！' ),
			array( 'chara' => 'char-hinshitsu', 'name' => '品質',
			       'text'  => '経験豊富な自社職人を中心に、質の良い丁寧な工事を致します！' ),
			array( 'chara' => 'char-anshin',    'name' => '安心',
			       'text'  => '商品延長10年保証・工事保証5年・24時間365日トラブル対応付き！' ),
		),

		/* --- お悩み（いまは非表示） --- */
		'worrytitle' => 'トイレのお悩み',
		'worryintro' => 'こんなことで悩んでいませんか？',
		'worries'    => array(),
		'worrylead'  => '',
		'solvesub'   => '実は、そのお悩み',
		'solvetitle' => '最新のトイレで解決できます！',
		'tags'       => array(),
		'tagnote'    => '',
		'solutions'  => array(),
	),

	'lavatory' => array(

		'en'    => 'LAVATORY',
		'title' => '洗面化粧台リフォーム',
		'hero'  => 'assets/img/products/head-lavatory.jpg',
		'lead'  => '本体・標準工事費・古い洗面化粧台の解体処分費まで込みのパック価格でご案内します。',

		/* --- ブランド紹介 --- */
		'brandsub'  => '朝いちばんに使う場所を、気持ちよく',
		'brand'     => '洗面化粧台リフォームパック',
		'brandtext' => '毎朝の身支度から、洗濯・つけおき洗いまで。'
		             . '<br>お掃除のしやすさと収納力を基準に、選び抜いた機種をご用意しました。',

		/* --- 3つのこだわり --- */
		'points' => array(
			array( 'chara' => 'char-otoku',     'name' => 'お得',
			       'text'  => '洗面化粧台の標準工事費・古い洗面台の解体撤去費用もコミコミ！' ),
			array( 'chara' => 'char-hinshitsu', 'name' => '品質',
			       'text'  => '経験豊富な自社職人を中心に、質の良い丁寧な工事を致します！' ),
			array( 'chara' => 'char-anshin',    'name' => '安心',
			       'text'  => '商品延長10年保証・工事保証5年・24時間365日トラブル対応付き！' ),
		),

		/* --- お悩み（いまは非表示） --- */
		'worrytitle' => '洗面化粧台のお悩み',
		'worryintro' => 'こんなことで悩んでいませんか？',
		'worries'    => array(),
		'worrylead'  => '',
		'solvesub'   => '実は、そのお悩み',
		'solvetitle' => '最新の洗面化粧台で解決できます！',
		'tags'       => array(),
		'tagnote'    => '',
		'solutions'  => array(),
	),

	'boiler' => array(

		'en'    => 'BOILER',
		'title' => '給湯器リフォーム',
		/* 横長の見出し写真がまだないので、写真なしの見出しにしています。
		   商品写真をここに使うと、真ん中だけが大きく切り取られて何か分からなくなります。 */
		'hero'  => '',
		'lead'  => '本体・標準工事費・リモコン・古い給湯器の撤去処分費まで込みの価格でご案内します。',

		/* --- ブランド紹介 --- */
		'brandsub'  => 'エコキュート＆給湯器専門店',
		'brand'     => 'ヤマキシ給湯センター',
		'brandtext' => 'お湯が出ない、というときこそ急ぎます。'
		             . '<br>在庫のある機種は、工期半日で交換できます。',

		/* --- 3つのこだわり --- */
		'points' => array(
			array( 'chara' => 'char-otoku',     'name' => 'お得',
			       'text'  => '本体・標準工事費・リモコン・古い給湯器の撤去処分費までコミコミ！' ),
			array( 'chara' => 'char-hinshitsu', 'name' => '早い',
			       'text'  => '在庫のある機種なら、工期は半日。すぐに工事にうかがえます！' ),
			array( 'chara' => 'char-anshin',    'name' => '安心',
			       'text'  => '商品延長10年保証・工事保証5年・24時間365日トラブル対応付き！' ),
		),

		/* --- お悩み（いまは非表示） --- */
		'worrytitle' => '給湯器のお悩み',
		'worryintro' => 'こんなことで悩んでいませんか？',
		'worries'    => array(),
		'worrylead'  => '',
		'solvesub'   => '実は、そのお悩み',
		'solvetitle' => '最新の給湯器で解決できます！',
		'tags'       => array(),
		'tagnote'    => '',
		'solutions'  => array(),
	),

);

$c   = isset( $intro[ $slug ] ) ? $intro[ $slug ] : null;
$dir = get_stylesheet_directory_uri();

/* ============================================================
   この分類の商品を、込み価格の安い順に取り出します
   ============================================================ */
$q = new WP_Query( array(
	'post_type'      => 'ymkrf_product',
	'posts_per_page' => -1,
	'tax_query'      => array( array(
		'taxonomy' => 'ymkrf_product_cat',
		'field'    => 'term_id',
		'terms'    => $term->term_id,
	) ),
	/* 並び順は inc/functions-product.php にまとめてあります。
	   「込み価格の安い順、同じ価格ならグレードの低い順」です。
	   管理画面の一覧とまったく同じ順番になります。 */
	'ymkrf_sort' => 'price',
) );

/* 価格が未入力の商品があると上の並べ替えから漏れるので、
   1件も出なかったときだけ、並べ替え無しでもう一度探します。 */
if ( ! $q->have_posts() ) {
	$q = new WP_Query( array(
		'post_type'      => 'ymkrf_product',
		'posts_per_page' => -1,
		'tax_query'      => array( array(
			'taxonomy' => 'ymkrf_product_cat',
			'field'    => 'term_id',
			'terms'    => $term->term_id,
		) ),
		'orderby' => 'title',
		'order'   => 'ASC',
	) );
}

/* ============================================================
   一覧のグループ分け

   トイレだけは「手洗いカウンターなし」と「手洗いカウンター付き」で
   まとまりを分けて出します（標準工事費が 38,000円／53,000円 と
   ちがうため、まぜて並べるとお客様が比べにくいからです）。
   ほかの分類は、これまでどおり1つのまとまりのままです。

   ★グループを増やしたいときは、下の $groups に
     array( 'ttl' => '見出し', 'sub' => '説明', 'posts' => 商品の配列 )
     を足してください。
   ============================================================ */
$groups = array();

if ( $slug === 'toilet' ) {

	$g_plain = array();
	$g_count = array();

	foreach ( $q->posts as $_p ) {
		/* 「商品名の横の言葉」か商品名に「カウンター」が入っていれば、
		   手洗いカウンター付きとみなします。 */
		$_sub = (string) get_post_meta( $_p->ID, '_ymkrf_sub', true );
		if ( strpos( $_sub, 'カウンター' ) !== false || strpos( $_p->post_title, 'カウンター' ) !== false ) {
			$g_count[] = $_p;
		} else {
			$g_plain[] = $_p;
		}
	}

	if ( $g_plain ) $groups[] = array(
		'ttl'   => '手洗いカウンターなし',
		'sub'   => '便器・便座の交換のみ。標準工事費 38,000円（税込）込みの価格です。',
		'posts' => $g_plain,
	);
	if ( $g_count ) $groups[] = array(
		'ttl'   => '手洗いカウンター付き',
		'sub'   => '手洗い器とカウンターも一緒に。標準工事費 53,000円（税込）込みの価格です。',
		'posts' => $g_count,
	);
}

if ( $slug === 'boiler' ) {

	/* 給湯器の並べ方（2026/09/01 ユーザー指示）

	     大分類 … エコフィール ／ 石油給湯器 ／ エコジョーズ ／ ガス給湯器
	              （チラシと同じ分けかた・同じ順番）
	     中分類 … メーカー（ノーリツ ／ リンナイ）

	   メーカーの説明は、同じ文章が何度も出てしまうので、
	   一覧のいちばん上に「取り扱いメーカー」としてまとめて出します。

	   ★どの種類かは、その商品の「キャッチコピー」で見分けています。
	     「エコフィール」→ エコフィール
	     「エコジョーズ」→ エコジョーズ
	     上の2つが無くて「石油」→ 石油給湯器
	     上の3つが無くて「ガス」→ ガス給湯器
	     どれも入っていない商品は、小見出しなしで最後に並びます。

	   ★メーカーの説明は、ダッシュボードの「商品 → メーカー」の
	     「説明」欄を直せば変わります。
	   ★並べる順は、下の $maker_order と $fuels を書きかえてください。 */

	$maker_order = array( 'noritz', 'rinnai' );

	/* チラシと同じ4つに分けます（順番もチラシと同じ） */
	$fuels = array(
		'ecofeel' => array( 'エコフィール（高効率石油給湯器）',
			'灯油をお使いのお宅用です。排気の熱を再利用して、灯油の使用量をおさえます。' ),
		'oil'     => array( '石油給湯器',
			'灯油をお使いのお宅用です。屋外に置いて取り付けます。' ),
		'ecojaws' => array( 'エコジョーズ（高効率ガス給湯器）',
			'ガスをお使いのお宅用です。排気の熱を再利用して、ガスの使用量をおさえます。' ),
		'gas'     => array( 'ガス給湯器',
			'ガスをお使いのお宅用です。壁に掛けて取り付けます。' ),
		''        => array( '', '' ),
	);

	/* 燃料 → メーカー の順で、商品を仕分けます */
	$bin = array();
	foreach ( $q->posts as $_p ) {
		$_c = (string) get_post_meta( $_p->ID, '_ymkrf_catch', true );
		if ( strpos( $_c, 'エコフィール' ) !== false )     { $_f = 'ecofeel'; }
		elseif ( strpos( $_c, 'エコジョーズ' ) !== false ) { $_f = 'ecojaws'; }
		elseif ( strpos( $_c, '石油' ) !== false )         { $_f = 'oil';     }
		elseif ( strpos( $_c, 'ガス' ) !== false )         { $_f = 'gas';     }
		else                                               { $_f = '';        }

		$_ts = get_the_terms( $_p->ID, 'ymkrf_maker' );
		$_m  = ( $_ts && ! is_wp_error( $_ts ) ) ? $_ts[0]->slug : '';

		$bin[ $_f ][ $_m ][] = $_p;
	}

	foreach ( $fuels as $_fk => $_fd ) {
		if ( empty( $bin[ $_fk ] ) ) continue;

		/* 決めた順のメーカーを先に、そこに無いメーカーはうしろに */
		$mkeys = array_merge( $maker_order, array_diff( array_keys( $bin[ $_fk ] ), $maker_order ) );

		$subs  = array();
		$all   = array();
		foreach ( $mkeys as $_mk ) {
			if ( empty( $bin[ $_fk ][ $_mk ] ) ) continue;
			$_t = $_mk ? get_term_by( 'slug', $_mk, 'ymkrf_maker' ) : null;
			if ( is_wp_error( $_t ) ) $_t = null;
			$subs[] = array(
				'ttl'   => $_t ? $_t->name : '',
				'maker' => $_t,
				'posts' => $bin[ $_fk ][ $_mk ],
			);
			$all = array_merge( $all, $bin[ $_fk ][ $_mk ] );
		}

		$groups[] = array(
			'ttl'   => $_fd[0],
			'sub'   => $_fd[1],
			'maker' => null,
			'posts' => $all,
			'subs'  => $subs,
		);
	}

	/* いちばん上に出す「取り扱いメーカー」の紹介 */
	$maker_intro = array();
	$seen_makers = array();
	foreach ( $bin as $_bym ) foreach ( array_keys( $_bym ) as $_mk ) $seen_makers[ $_mk ] = true;
	$mkeys2 = array_merge( $maker_order, array_diff( array_keys( $seen_makers ), $maker_order ) );
	foreach ( $mkeys2 as $_mk ) {
		if ( empty( $seen_makers[ $_mk ] ) ) continue;
		$_t = $_mk ? get_term_by( 'slug', $_mk, 'ymkrf_maker' ) : null;
		if ( ! $_t || is_wp_error( $_t ) ) continue;
		$maker_intro[] = $_t;
	}
}

/* トイレ以外、または上でうまく分けられなかったときは、まとめて1つに */
if ( ! $groups ) {
	$groups[] = array( 'ttl' => '', 'sub' => '', 'posts' => $q->posts );
}

/* どのまとめ方でも 'maker' と 'subs' の欄があるようにしておきます。
   小見出しで分けないまとまりは、「見出しなしの小見出しが1つ」という形にします。
   こうしておくと、表示側は subs だけを見ればよくなります。 */
foreach ( $groups as $_i => $_g ) {
	if ( ! isset( $_g['maker'] ) ) $groups[ $_i ]['maker'] = null;
	if ( empty( $_g['subs'] ) ) {
		$groups[ $_i ]['subs'] = array( array( 'ttl' => '', 'maker' => null, 'posts' => $_g['posts'] ) );
	} else {
		foreach ( $groups[ $_i ]['subs'] as $_j => $_sg ) {
			if ( ! isset( $_sg['maker'] ) ) $groups[ $_i ]['subs'][ $_j ]['maker'] = null;
		}
	}
}

/* 一覧のいちばん上に出すメーカー紹介（給湯器だけ。ほかの分類では空です） */
if ( ! isset( $maker_intro ) ) $maker_intro = array();

/* 商品代のいちばん安い金額（「商品代＋工事費」の説明に使います） */
$minitem = 0;
foreach ( $q->posts as $_p ) {
	$v = (int) get_post_meta( $_p->ID, '_ymkrf_item', true );
	if ( $v && ( ! $minitem || $v < $minitem ) ) $minitem = $v;
}

/* 工事費内訳のアイコンは inc/functions-product.php の ymkrf_work_icon() にあります */

get_header();
?>

<!-- =========== パンくず =========== -->
<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li><a href="<?php echo esc_url( ymkrf_products_url() ); ?>">商品・価格</a></li>
    <li><?php echo esc_html( $name ); ?></li>
  </ol>
</nav>

<main id="main">

<!-- =========== ページ見出し =========== -->
<?php
$hero = ( $c && ! empty( $c['hero'] ) ) ? $dir . '/' . $c['hero'] : '';
?>
<div class="p-pagehead<?php echo $hero ? ' p-pagehead--photo' : ''; ?>"<?php
  if ( $hero ) echo ' style="--ph-img:url(' . esc_url( $hero ) . ')"'; ?>>
  <div class="l-wrap p-pagehead__inner">
    <?php if ( $c && $c['en'] ) : ?>
      <span class="p-pagehead__en"><?php echo esc_html( $c['en'] ); ?></span>
    <?php endif; ?>
    <h1 class="p-pagehead__title"><?php echo esc_html( $c ? $c['title'] : $name ); ?></h1>
    <?php if ( $c && $c['lead'] ) : ?>
      <p class="p-pagehead__lead"><?php echo wp_kses( $c['lead'], array( 'br' => array() ) ); ?></p>
    <?php endif; ?>
  </div>
</div>

<?php if ( $c ) : ?>

<!-- =========== ブランド紹介・3つのこだわり =========== -->
<section class="l-section l-section--soft">
  <div class="l-wrap">

    <div class="p-cat__brand">
      <p class="p-cat__brandsub"><?php echo esc_html( $c['brandsub'] ); ?></p>
      <h2 class="p-cat__brandname"><?php echo esc_html( $c['brand'] ); ?></h2>
      <p class="p-cat__brandtext"><?php echo wp_kses( $c['brandtext'], array( 'br' => array() ) ); ?></p>
    </div>

    <p class="p-cat__pointlead"><span class="p-cat__pointnum">3</span>つのこだわり</p>

    <div class="p-cat__points">
      <?php foreach ( $c['points'] as $p ) : ?>
        <div class="p-cat__point">
          <img class="p-cat__pointchara"
               src="<?php echo esc_url( $dir . '/assets/img/character/' . $p['chara'] . '.webp' ); ?>"
               width="480" height="480" alt="" loading="lazy" decoding="async">
          <h3 class="p-cat__pointname"><?php echo esc_html( $p['name'] ); ?></h3>
          <p class="p-cat__pointtext"><?php echo esc_html( $p['text'] ); ?></p>
        </div>
      <?php endforeach; ?>
    </div>

    <?php ymkrf_product_cta( 'category-top' ); ?>

  </div>
</section>

<?php
/* 「キッチンのお悩み」「最新キッチンで解決できます」は、
   別ページに作り直す予定のためこのページでは非表示にしています。
   復活させるときは false を true に戻してください。 */
$show_worry = false;
if ( $show_worry ) :
?>
<!-- =========== こんなお悩みありませんか？ =========== -->
<section class="l-section">
  <div class="l-wrap">
    <h2 class="p-prd__bar"><?php echo esc_html( $c['worrytitle'] ); ?></h2>
    <p class="p-cat__worryintro"><?php echo esc_html( $c['worryintro'] ); ?></p>

    <?php
    /* お悩みは2段の横スクロール（マーキー）にして、高さを抑えます。
       途切れなくループさせるため、同じ内容を4回くり返して -50% まで動かします。 */
    $ws   = array_values( $c['worries'] );
    $half = (int) ceil( count( $ws ) / 2 );
    $rows = array( array_slice( $ws, 0, $half ), array_slice( $ws, $half ) );
    ?>
    <div class="p-cat__worrybox">
      <img class="p-cat__worrychara"
           src="<?php echo esc_url( $dir . '/assets/img/character/char-search.webp' ); ?>"
           width="480" height="480" alt="" loading="lazy" decoding="async">
      <div class="p-cat__worryflow">
        <?php foreach ( $rows as $ri => $row ) : if ( ! $row ) continue; ?>
          <div class="p-cat__worryrow<?php echo $ri ? ' p-cat__worryrow--rev' : ''; ?>">
            <?php for ( $k = 0; $k < 4; $k++ ) : ?>
              <ul class="p-cat__worries"<?php echo $k ? ' aria-hidden="true"' : ''; ?>>
                <?php foreach ( $row as $w ) : ?><li><?php echo esc_html( $w ); ?></li><?php endforeach; ?>
              </ul>
            <?php endfor; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if ( ! empty( $c['worrylead'] ) ) : ?>
      <p class="p-cat__worrylead"><?php echo esc_html( $c['worrylead'] ); ?></p>
    <?php endif; ?>
  </div>
</section>

<!-- =========== 最新キッチンで解決できます =========== -->
<section class="l-section l-section--soft">
  <div class="l-wrap">

    <p class="p-cat__solvesub"><?php echo esc_html( $c['solvesub'] ); ?></p>
    <h2 class="p-cat__solvetitle"><span class="marker"><?php echo esc_html( $c['solvetitle'] ); ?></span></h2>

    <?php if ( ! empty( $c['tags'] ) ) : ?>
      <ul class="p-cat__tags">
        <?php foreach ( $c['tags'] as $t ) : ?>
          <li><?php echo esc_html( $t ); ?></li>
        <?php endforeach; ?>
        <li class="p-cat__tags--etc">…etc</li>
      </ul>
    <?php endif; ?>

    <div class="p-cat__sols">
      <?php foreach ( $c['solutions'] as $s ) : ?>
        <div class="p-cat__sol">
          <div class="p-cat__solph">
            <img src="<?php echo esc_url( $dir . '/assets/img/category/' . $slug . '/' . $s['img'] ); ?>"
                 width="448" height="300" alt="<?php echo esc_attr( $s['alt'] ); ?>"
                 loading="lazy" decoding="async">
          </div>
          <h3 class="p-cat__soltitle"><span><?php echo esc_html( $s['title'] ); ?></span></h3>
          <p class="p-cat__sollead"><?php echo esc_html( $s['lead'] ); ?></p>
          <?php if ( ! empty( $s['text'] ) ) : ?><p class="p-cat__soltext"><?php echo esc_html( $s['text'] ); ?></p><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ( ! empty( $c['tagnote'] ) ) : ?>
      <p class="p-cat__tagnote"><?php echo esc_html( $c['tagnote'] ); ?></p>
    <?php endif; ?>

  </div>
</section>
<?php endif; /* $show_worry */ ?>

<!-- =========== 商品代＋標準工事費 ===========
     他のカテゴリ（トイレなど）でも、上の $intro にそのカテゴリの
     'pointnote'（price / note / itemsttl / items）を書けば同じ形で出ます。
     ・「最安値」は登録商品の商品代（_ymkrf_item）から自動で計算します
     ・label と note は省略でき、その場合はカテゴリ名から自動で作ります  -->
<?php $pn = ymkrf_pointnote( $slug );
if ( ! empty( $pn['items'] ) ) :
	if ( empty( $pn['label'] ) ) $pn['label'] = $name . 'の標準工事費';
	if ( ! isset( $pn['note'] ) ) $pn['note']  = $name . 'の標準工事費は、どの機種も一律同価格です。';
	if ( empty( $pn['itemsttl'] ) ) $pn['itemsttl'] = 'リフォームヤマキシの|標準工事費にふくまれる工事';
?>
<section class="l-section p-cat__calcsec">
  <div class="l-wrap">
    <div class="p-cat__calc">

      <?php
      /* 給湯器のように「工事費込みの一本価格」でご案内している分類は、
         商品代と標準工事費に分けられません。
         そのときは金額のカードを出さず、下の「ふくまれる工事」だけを出します。 */
      $show_calc = empty( $pn['nocalc'] ) && ( $minitem || ! empty( $pn['price'] ) );
      if ( $show_calc ) : ?>
      <div class="p-cat__calcbody">

        <?php if ( $minitem ) : ?>
          <div class="p-cat__calccard">
            <p class="p-cat__calchead p-cat__calchead--item"><?php echo esc_html( $name ); ?>の商品代</p>
            <div class="p-cat__calcin">
              <p class="p-cat__calclead">最安値</p>
              <p class="p-cat__calcprice">
                <span class="num"><?php echo esc_html( rtrim( rtrim( number_format( $minitem / 10000, 1 ), '0' ), '.' ) ); ?></span><span class="man">万円</span><span class="kara">〜</span>
              </p>
              <p class="p-cat__calctax">（税込<?php echo esc_html( number_format( $minitem ) ); ?>円〜）</p>
            </div>
          </div>

          <?php if ( ! empty( $pn['price'] ) ) : ?>
            <span class="p-cat__calcplus" aria-hidden="true">＋</span>
          <?php endif; ?>
        <?php endif; ?>

        <?php if ( ! empty( $pn['price'] ) ) : ?>
        <div class="p-cat__calccard p-cat__calccard--work">
          <p class="p-cat__calchead p-cat__calchead--work"><?php echo esc_html( $pn['label'] ); ?></p>
          <div class="p-cat__calcin">
            <?php /* ここにあった「追加料金なし！」は、お家の形状によって
                     追加が出る場合があるため、削除しました。 */ ?>
            <p class="p-cat__calcprice">
              <span class="num"><?php echo esc_html( rtrim( rtrim( number_format( $pn['price'] / 10000, 1 ), '0' ), '.' ) ); ?></span><span class="man">万円</span>
            </p>
            <p class="p-cat__calctax">（税込<?php echo esc_html( number_format( $pn['price'] ) ); ?>円）</p>
          </div>
        </div>
        <?php endif; ?>

      </div>
      <?php endif; /* $show_calc */ ?>

      <p class="p-cat__stepsttl"><span><?php
        /* 「|」を、スマホだけで効く改行に置きかえます */
        echo str_replace( '|', '<br class="xs-only">', esc_html( $pn['itemsttl'] ) );
      ?></span></p>
      <ol class="p-cat__steps">
        <?php foreach ( $pn['items'] as $i => $w ) : ?>
          <li class="p-cat__step">
            <span class="p-cat__stepnum"><?php echo (int) ( $i + 1 ); ?></span>
            <span class="p-cat__stepicon"><?php echo ymkrf_work_icon( $w['icon'] ); /* phpcs:ignore */ ?></span>
            <span class="p-cat__stepname"><?php echo esc_html( $w['name'] ); ?></span>
            <?php if ( $w['sub'] ) : ?>
              <span class="p-cat__stepsub"><?php echo esc_html( $w['sub'] ); ?></span>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ol>

      <?php if ( ! empty( $pn['note'] ) ) : ?>
        <p class="p-cat__calcnote"><?php echo ymkrf_brk( $pn['note'] ) /* phpcs:ignore */; ?></p>
      <?php endif; ?>
      <?php if ( ! empty( $pn['note2'] ) ) : ?>
        <p class="p-cat__calcnote2"><?php echo ymkrf_brk( $pn['note2'] ) /* phpcs:ignore */; ?></p>
      <?php endif; ?>

    </div>
  </div>
</section>
<?php endif; ?>

<?php endif; /* $c */ ?>

<!-- =========== 商品一覧 =========== -->
<section class="l-section" id="products">
  <div class="l-wrap">
    <h2 class="p-prd__bar"><?php echo esc_html( ymkrf_cat_listtitle( $slug, $name ) ); ?></h2>

    <?php if ( $q->have_posts() ) : ?>

      <?php /* 給湯器はメーカーごとに並べていて「安い順」ではないので、
               この案内は出しません。 */
      if ( $slug !== 'boiler' ) : ?>
      <p class="p-cat__listlead">
        価格はすべて<strong>標準工事費・既存品の撤去処分費まで込み</strong>の税込表示です。
        安い順に並べています。
      </p>
      <?php endif; ?>

      <?php if ( $maker_intro ) : ?>
      <div class="p-cat__makers">
        <?php foreach ( $maker_intro as $mi ) : ?>
          <div class="p-cat__maker">
            <p class="p-cat__makerhead">
              <span class="p-cat__makername"><?php echo esc_html( $mi->name ); ?></span>
              <?php echo ymkrf_maker_logo( $mi, 'p-cat__makerlogo' ); /* phpcs:ignore */ ?>
            </p>
            <?php if ( trim( (string) $mi->description ) !== '' ) : ?>
              <p class="p-cat__makertext"><?php echo ymkrf_brk( $mi->description ) /* phpcs:ignore */; ?></p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php global $post; foreach ( $groups as $g ) : if ( ! $g['posts'] ) continue; ?>

      <?php if ( $g['ttl'] ) : ?>
        <div class="p-cat__group<?php echo $g['maker'] ? ' p-cat__group--maker' : ''; ?>">
          <h3 class="p-cat__groupttl"><?php echo esc_html( $g['ttl'] );
            if ( $g['maker'] ) echo ymkrf_maker_logo( $g['maker'], 'p-cat__grouplogo' ); /* phpcs:ignore */
          ?></h3>
          <?php if ( $g['sub'] ) : ?><p class="p-cat__groupsub"><?php echo ymkrf_brk( $g['sub'] ) /* phpcs:ignore */; ?></p><?php endif; ?>
        </div>
      <?php endif; ?>

      <?php foreach ( $g['subs'] as $sg ) : if ( ! $sg['posts'] ) continue; ?>

      <?php if ( $sg['ttl'] ) : ?>
        <p class="p-cat__subttl"><span><?php echo esc_html( $sg['ttl'] ); ?></span><?php
          if ( $sg['maker'] ) echo ymkrf_maker_logo( $sg['maker'], 'p-cat__sublogo' ); /* phpcs:ignore */
        ?></p>
      <?php endif; ?>

      <div class="p-cat__cards">
        <?php foreach ( $sg['posts'] as $post ) : setup_postdata( $post );
          $d  = ymkrf_product_data();
          $mt = ! empty( $d['makers'] ) ? $d['makers'][0] : null;
        ?>
          <a class="p-cat__card" href="<?php the_permalink(); ?>">
            <div class="p-cat__cardph">
              <?php echo has_post_thumbnail()
                ? get_the_post_thumbnail( null, 'medium_large', array( 'loading' => 'lazy', 'alt' => '' ) )
                : ''; ?>
              <?php if ( $d['grade'] ) : ?>
                <span class="p-cat__cardgrade"><?php echo esc_html( $d['grade'] ); ?></span>
              <?php endif; ?>
            </div>
            <div class="p-cat__cardbody">
              <h3 class="p-cat__cardname"><?php echo esc_html( $d['name'] ); ?><?php
                if ( $d['sub'] ) echo '<span class="p-cat__cardsub">' . esc_html( $d['sub'] ) . '</span>'; ?></h3>
              <?php /* 1行目：メーカーと工期。工期はどの商品でも右端にそろえます */ ?>
              <p class="p-cat__cardmeta">
                <?php if ( $mt ) echo ymkrf_maker_logo( $mt, 'p-maker' ); /* phpcs:ignore */ ?>
                <?php if ( $d['daystext'] ) : ?><span class="p-cat__carddays">工期<?php echo esc_html( $d['daystext'] ); ?></span>
                <?php elseif ( $d['days'] ) : ?><span class="p-cat__carddays">工期<?php echo esc_html( $d['days'] ); ?>日</span><?php endif; ?>
              </p>
              <?php /* 2行目：型番。長さが商品ごとに違うので、行を分けています */ ?>
              <?php if ( $d['size'] ) : ?>
                <p class="p-cat__cardmeta p-cat__cardmeta--size"><span><?php echo esc_html( $d['size'] ); ?></span></p>
              <?php endif; ?>
              <?php if ( $d['total'] ) : ?>
                <p class="p-cat__cardprice">
                  <span class="lbl">工事費込み</span>
                  <span class="num"><?php echo esc_html( number_format( $d['total'] ) ); ?></span>
                  <span class="unit">円（税込）</span>
                </p>
              <?php endif; ?>
              <span class="p-cat__cardlink">くわしく見る</span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>

      <?php endforeach; /* $sg */ ?>

      <?php endforeach; wp_reset_postdata(); ?>

    <?php else : ?>
      <p>この分類には、まだ商品が登録されていません。</p>
    <?php endif; ?>

  </div>
</section>

<!-- =========== お役立ち情報（コラム） ===========
     記事は、ダッシュボードの「コラム」から追加してください。
     商品カテゴリに「キッチン」を付けた記事が、ここに新しい順で3件出ます。
     1件も無いときは、このかたまりごと出ません。 -->
<?php
if ( function_exists( 'ymkrf_column_section' ) ) {
	ymkrf_column_section( $slug, ymkrf_cat_label( $slug, $name ), 3 );
}
?>

<!-- =========== 施工事例 ===========
     ダッシュボードの「施工事例」で、部位に「キッチン」を付けた記事が
     ここに新しい順で3件出ます。1件も無いときは出ません。 -->
<?php
if ( function_exists( 'ymkrf_works_section' ) ) {
	ymkrf_works_section( $slug, ymkrf_cat_label( $slug, $name ), 3 );
}
?>

<!-- =========== 最後のご案内 =========== -->
<section class="l-section l-section--soft">
  <div class="l-wrap">
    <div class="p-pagecta">
      <img class="p-pagecta__chara"
           src="<?php echo esc_url( $dir . '/assets/img/character/char-search-fly.webp' ); ?>"
           width="480" height="480" alt="" loading="lazy">
      <h2 class="p-pagecta__title"><span class="marker">まずは、現物を見に<br class="xs-only">いらしてください</span></h2>
      <p class="p-pagecta__text">
        ショールームには北陸最大級の<br class="xs-only">住宅設備を展示しています。<br>
        見積り・現地調査は無料。<br class="xs-only">しつこい営業はいたしません。
      </p>
      <?php ymkrf_product_cta( 'category-bottom', true ); ?>
    </div>
  </div>
</section>

</main>

<?php get_footer();
