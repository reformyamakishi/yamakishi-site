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
		'pointnote' => array(
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
				array( 'name' => 'シロッコファン取付工事', 'icon' => 'fan',
				       'sub'  => 'シロッコファンの取付工事' ),
				array( 'name' => 'システムキッチン取付設置', 'icon' => 'kitchen',
				       'sub'  => '新しいシステムキッチンの取り付け・設置' ),
			),
		),

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
		'hero'  => 'assets/img/products/ofuroa/ofuroa-main.jpg',
		'lead'  => '本体・標準工事費・古いお風呂の解体処分費まで込みのパック価格でご案内します。',

		/* --- ブランド紹介（お風呂は専用のブランド名を付けていません） --- */
		'brandsub'  => '毎日の入浴が、もっと心地よく',
		'brand'     => 'ユニットバスリフォームパック',
		'brandtext' => '在来浴室からの取り替えも承ります。'
		             . '<br>断熱浴槽・お掃除のしやすさを基準に、選び抜いた機種をご用意しました。',

		/* --- 3つのこだわり --- */
		'points' => array(
			array( 'chara' => 'char-otoku',     'name' => 'お得',
			       'text'  => 'お風呂標準工事費・解体撤去費用・土間工事などもコミコミ！' ),
			array( 'chara' => 'char-hinshitsu', 'name' => '品質',
			       'text'  => '経験豊富な自社職人を中心に、質の良い丁寧な工事を致します！' ),
			array( 'chara' => 'char-anshin',    'name' => '安心',
			       'text'  => '商品延長10年保証・工事保証5年・24時間365日トラブル対応付き！' ),
		),
		'pointnote' => array(
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
	'meta_key' => '_ymkrf_total',
	'orderby'  => 'meta_value_num',
	'order'    => 'ASC',
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

/* 商品代のいちばん安い金額（「商品代＋工事費」の説明に使います） */
$minitem = 0;
foreach ( $q->posts as $_p ) {
	$v = (int) get_post_meta( $_p->ID, '_ymkrf_item', true );
	if ( $v && ( ! $minitem || $v < $minitem ) ) $minitem = $v;
}

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
		);
		if ( empty( $d[ $key ] ) ) return '';
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" '
		     . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d[ $key ] . '</svg>';
	}
}

get_header();
?>

<!-- =========== パンくず =========== -->
<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li><a href="<?php echo esc_url( home_url( '/products/' ) ); ?>">商品・価格</a></li>
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
<?php if ( ! empty( $c['pointnote']['items'] ) ) :
	$pn = $c['pointnote'];
	if ( empty( $pn['label'] ) ) $pn['label'] = $name . 'の標準工事費';
	if ( ! isset( $pn['note'] ) ) $pn['note']  = $name . 'の標準工事費は、どの機種も一律同価格です。';
	if ( empty( $pn['itemsttl'] ) ) $pn['itemsttl'] = 'リフォームヤマキシの|標準工事費にふくまれる工事';
?>
<section class="l-section p-cat__calcsec">
  <div class="l-wrap">
    <div class="p-cat__calc">

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

          <span class="p-cat__calcplus" aria-hidden="true">＋</span>
        <?php endif; ?>

        <div class="p-cat__calccard p-cat__calccard--work">
          <p class="p-cat__calchead p-cat__calchead--work"><?php echo esc_html( $pn['label'] ); ?></p>
          <div class="p-cat__calcin">
            <p class="p-cat__calclead p-cat__calclead--work">追加料金なし！</p>
            <p class="p-cat__calcprice">
              <span class="num"><?php echo esc_html( rtrim( rtrim( number_format( $pn['price'] / 10000, 1 ), '0' ), '.' ) ); ?></span><span class="man">万円</span>
            </p>
            <p class="p-cat__calctax">（税込<?php echo esc_html( number_format( $pn['price'] ) ); ?>円）</p>
          </div>
        </div>

      </div>

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
        <p class="p-cat__calcnote"><?php echo esc_html( $pn['note'] ); ?></p>
      <?php endif; ?>
      <?php if ( ! empty( $pn['note2'] ) ) : ?>
        <p class="p-cat__calcnote2"><?php echo esc_html( $pn['note2'] ); ?></p>
      <?php endif; ?>

    </div>
  </div>
</section>
<?php endif; ?>

<?php endif; /* $c */ ?>

<!-- =========== 商品一覧 =========== -->
<section class="l-section" id="products">
  <div class="l-wrap">
    <h2 class="p-prd__bar"><?php echo esc_html( $name ); ?>マルシェの商品一覧</h2>

    <?php if ( $q->have_posts() ) : ?>

      <p class="p-cat__listlead">
        価格はすべて<strong>標準工事費・既存品の撤去処分費まで込み</strong>の税込表示です。
        安い順に並べています。
      </p>

      <div class="p-cat__cards">
        <?php while ( $q->have_posts() ) : $q->the_post();
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
              <h3 class="p-cat__cardname"><?php echo esc_html( $d['name'] ); ?></h3>
              <p class="p-cat__cardmeta">
                <?php if ( $mt ) echo ymkrf_maker_logo( $mt, 'p-maker' ); /* phpcs:ignore */ ?>
                <?php if ( $d['size'] ) : ?><span><?php echo esc_html( $d['size'] ); ?></span><?php endif; ?>
                <?php if ( $d['days'] ) : ?><span>工期<?php echo esc_html( $d['days'] ); ?>日</span><?php endif; ?>
              </p>
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
        <?php endwhile; wp_reset_postdata(); ?>
      </div>

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
	ymkrf_column_section( $slug, $name, 3 );
}
?>

<!-- =========== 施工事例 ===========
     ダッシュボードの「施工事例」で、部位に「キッチン」を付けた記事が
     ここに新しい順で3件出ます。1件も無いときは出ません。 -->
<?php
if ( function_exists( 'ymkrf_works_section' ) ) {
	ymkrf_works_section( $slug, $name, 3 );
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
