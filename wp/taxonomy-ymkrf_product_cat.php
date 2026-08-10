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
		'lead'  => '本体・標準工事費・古いキッチンの処分費まで込みのパック価格でご案内します。'
		         . '<br>あとからの追加請求はありません。',

		/* --- ブランド紹介 --- */
		'brandsub'  => '家事の負担を軽くする',
		'brand'     => 'キッチンマルシェ',
		'brandtext' => 'リフォームヤマキシの「キッチンマルシェ」とは？'
		             . '<br>「奥様の家事をもっとラクに！」をテーマに、'
		             . '選び抜いたキッチンを取り揃えたカテゴリーブランドです。',

		/* --- 3つのこだわり --- */
		'points' => array(
			array( 'chara' => 'char-otoku',     'name' => 'お得',
			       'text'  => 'キッチンパネル代もコミコミ！' ),
			array( 'chara' => 'char-hinshitsu', 'name' => '品質',
			       'text'  => '経験豊富な自社職人を中心に、質の良い丁寧な工事を致します！' ),
			array( 'chara' => 'char-anshin',    'name' => '安心',
			       'text'  => '24時間365日トラブル対応！10年延長保証もコミコミ！' ),
		),

		/* --- お悩み --- */
		'worrytitle' => 'キッチンでこんなお悩みありませんか？',
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
		'worrylead' => 'ヤマキシが、お客様にぴったりのキッチンをご提案します。',

		/* --- 解決できます --- */
		'solvesub'   => '実は、そのお悩み',
		'solvetitle' => '最新キッチンで解決できます！',
		'tags'       => array( '調味料棚付き', '流れるシンク', 'IHクッキングヒーター',
		                       'お掃除簡単レンジフード', '食器洗い乾燥機', 'ハイブリッドコンロ' ),
		'tagnote'    => '※各機種によって異なります　※オプションの場合もございます',

		'solutions' => array(
			array(
				'img'   => 'kitchen-sol-storage.jpg',
				'title' => '使いやすさと収納量を両立',
				'lead'  => 'よく使う道具が立ち位置を変えずに取り出せるので、スムーズに作業ができます。',
				'text'  => 'たくさん入って、使いやすい。立体構造で収納スペースをムダなく活かし、大容量を実現！',
				'alt'   => '立体構造で道具がたくさん入る引き出し収納',
			),
			array(
				'img'   => 'kitchen-sol-fan.jpg',
				'title' => 'ファンを自動洗浄',
				'lead'  => '約2ヶ月に1回の洗浄で、約10年間ファンフィルターを取り外さずにお掃除できます。',
				'text'  => '給湯トレイにお湯（40〜45℃）を入れて本体にセットし、洗浄ボタンを押すと'
				         . '集めた油汚れを自動洗浄（約10分）。あとは、排水トレイの水を捨てるだけの簡単洗浄です。',
				'alt'   => 'レンジフードの自動洗浄のしくみ図',
			),
			array(
				'img'   => 'kitchen-sol-faucet.jpg',
				'title' => '浄水も使えるオールインワン浄水栓',
				'lead'  => 'カートリッジの取替も簡単です。',
				'text'  => 'カートリッジは水栓本体にスリムに内蔵されているので、簡単に取り換えることができます。'
				         . '無意識でのお湯の使用を防止する「エコハンドル」採用。',
				'alt'   => '浄水器を内蔵したオールインワン浄水栓',
			),
			array(
				'img'   => 'kitchen-sol-wall.jpg',
				'title' => '「手が届く」高さで使えるユニット',
				'lead'  => '500〜900mmまで4サイズから選べるウォールユニット。収納量や設置スペースに合わせて選べます。',
				'text'  => '収納棚をアイレベル（目の高さ）まで降ろして、必要な物がすぐに取り出せます。'
				         . '（手動式）開き戸には耐震ロックを装備しているので、収納物が落下するのを防ぎます。',
				'alt'   => '目の高さまで降りてくるウォールユニット',
			),
			array(
				'img'   => 'kitchen-sol-ceramic.jpg',
				'title' => '高温フライパンもOKの素材',
				'lead'  => '焼き物ならではの上質で高品位な素材が、本物の風格を演出します。',
				'text'  => '熱やキズ、汚れに優れた耐久性を発揮するセラミック。'
				         . '耐衝撃性を高める強化構造を採用していますので、安心してお使いいただけます。',
				'alt'   => 'セラミックの天板に高温のフライパンを置いたところ',
			),
			array(
				'img'   => 'kitchen-sol-drain.jpg',
				'title' => 'うず水流で洗浄しながら排水',
				'lead'  => 'キレイが続く賢い排水口。普段通りに使うだけで洗浄してくれます。',
				'text'  => '食器や調理道具を洗ったり、普通にシンクを使うだけで、うず状の水流が排水口の汚れを洗浄。'
				         . '面倒だった排水口のお掃除がラクになります。',
				'alt'   => 'うず状の水流が排水口の汚れを洗い流すところ',
			),
		),
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
<div class="p-pagehead">
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

  </div>
</section>

<!-- =========== こんなお悩みありませんか？ =========== -->
<section class="l-section">
  <div class="l-wrap">
    <h2 class="p-prd__bar"><?php echo esc_html( $c['worrytitle'] ); ?></h2>

    <div class="p-cat__worrybox">
      <img class="p-cat__worrychara"
           src="<?php echo esc_url( $dir . '/assets/img/character/char-search.webp' ); ?>"
           width="480" height="480" alt="" loading="lazy" decoding="async">
      <ul class="p-cat__worries">
        <?php foreach ( $c['worries'] as $w ) : ?>
          <li><?php echo esc_html( $w ); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <p class="p-cat__worrylead"><?php echo esc_html( $c['worrylead'] ); ?></p>
    <?php ymkrf_product_cta( 'category-top' ); ?>
  </div>
</section>

<!-- =========== 最新キッチンで解決できます =========== -->
<section class="l-section l-section--soft">
  <div class="l-wrap">

    <p class="p-cat__solvesub"><?php echo esc_html( $c['solvesub'] ); ?></p>
    <h2 class="p-cat__solvetitle"><span class="marker"><?php echo esc_html( $c['solvetitle'] ); ?></span></h2>

    <ul class="p-cat__tags">
      <?php foreach ( $c['tags'] as $t ) : ?>
        <li><?php echo esc_html( $t ); ?></li>
      <?php endforeach; ?>
      <li class="p-cat__tags--etc">…etc</li>
    </ul>
    <p class="p-cat__tagnote"><?php echo esc_html( $c['tagnote'] ); ?></p>

    <div class="p-cat__sols">
      <?php foreach ( $c['solutions'] as $s ) : ?>
        <div class="p-cat__sol">
          <div class="p-cat__solph">
            <img src="<?php echo esc_url( $dir . '/assets/img/category/' . $slug . '/' . $s['img'] ); ?>"
                 width="448" height="300" alt="<?php echo esc_attr( $s['alt'] ); ?>"
                 loading="lazy" decoding="async">
          </div>
          <h3 class="p-cat__soltitle"><?php echo esc_html( $s['title'] ); ?></h3>
          <p class="p-cat__sollead"><?php echo esc_html( $s['lead'] ); ?></p>
          <p class="p-cat__soltext"><?php echo esc_html( $s['text'] ); ?></p>
        </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>

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
          $d = ymkrf_product_data();
          $m = ! empty( $d['makers'] ) ? $d['makers'][0]->name : '';
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
                <?php if ( $m ) : ?><span><?php echo esc_html( $m ); ?></span><?php endif; ?>
                <?php if ( $d['size'] ) : ?><span><?php echo esc_html( $d['size'] ); ?></span><?php endif; ?>
                <?php if ( $d['days'] ) : ?><span>工期<?php echo esc_html( $d['days'] ); ?>日</span><?php endif; ?>
              </p>
              <?php if ( $d['catch'] ) : ?>
                <p class="p-cat__cardcatch"><?php echo esc_html( $d['catch'] ); ?></p>
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
        <?php endwhile; wp_reset_postdata(); ?>
      </div>

    <?php else : ?>
      <p>この分類には、まだ商品が登録されていません。</p>
    <?php endif; ?>

  </div>
</section>

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
