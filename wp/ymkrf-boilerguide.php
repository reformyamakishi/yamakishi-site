<?php
/**
 * ymkrf-boilerguide.php ─ 給湯器・エコキュートの選び方（/products/boiler-guide/）
 *
 * 置き場所： wp-content/themes/ymkrf/ymkrf-boilerguide.php
 *
 * どんなページか
 *   「うちの給湯器、そろそろ替えどきかな」というお客様が、
 *   検索でたどりつく入口のページです。
 *   商品を並べる一覧ではなく、
 *     ・うちはどのタイプなのか
 *     ・どう選べばいいのか
 *     ・いつ替えればいいのか
 *   を先に分かっていただくための、読みものです。
 *
 * ★内容を直すとき
 *   文章は、この下の配列（$fuels／$funcs／$signs／$errors／$faqs）に
 *   まとめて書いてあります。ここを直せば表示も変わります。
 *   見た目は assets/css/product.css の「給湯器の選び方ページ」の項です。
 *
 * ★エラーコード一覧について
 *   サイトの中の /troubleshooting/ にリンクしています。
 *   ページの中身は ymkrf-troubleshooting.php、
 *   エラーコードの表は troubleshooting/ フォルダの中にあります。
 *
 * ※「追加請求はありません」といった言い切りは書かないでください。
 *   お家の形や配管・電気・ガスの状態によって、追加の工事が必要なことがあります。
 *   「追加の工事が必要なときは、着工前にかならずお見積りをお出しします」が正しい書き方です。
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$asset = get_stylesheet_directory_uri();

/* ------------------------------------------------------------
   1. 給湯器の種類
        fuel  … 何でお湯をわかすか（お客様が最初に見分けるところ）
        name  … 種類の名前
        text  … 説明
        img   … 写真。assets/img/ からの道すじを、拡張子なしで書きます
                （.webp と .jpg の両方を置いてください）。空なら「準備中」の枠が出ます
                写真は assets/img/guide/ にまとめてあります。
                800×600（4:3）で、まわりの余白を切りつめて大きく見せています。
                商品写真をそのまま使うと、白い余白が多くて小さく見えてしまうためです。
        alt   … 写真の説明（目の見えない方の読み上げに使われます）
        makers… 取り扱いメーカー（空なら出しません）

   ★このページは読みものです。商品への誘導は入れていません
     （2026/09/01 ユーザー指示）。

   ★並べる順（2026/09/02 ユーザー指示）
     1段目に エコキュート／ガス／石油 の3つ、
     2段目に エネファーム／電気温水器 を置いています。
     お客様の多くはこの3つのどれかなので、先に見えるようにしています。
   ------------------------------------------------------------ */
$fuels = array(

	array(
		'fuel'   => '電気',
		'name'   => 'エコキュート',
		'img'    => 'guide/ecocute',
		'alt'    => 'エコキュートの貯湯タンクとヒートポンプ',
		'text'   => '空気の熱を集めてお湯をわかす、電気の給湯器です。'
		          . '外に置くヒートポンプと、お湯をためるタンクの2つで1組になっています。'
		          . '夜のうちにわかしておくので、電気代の安い時間を使えます。',
		'makers' => '三菱電機／パナソニック',
	),

	array(
		'fuel'   => 'ガス',
		'name'   => 'ガス給湯器／エコジョーズ',
		'img'    => 'guide/gas',
		'alt'    => '壁に掛けて取り付けるガス給湯器の本体',
		'text'   => '都市ガス・プロパンガスをお使いのお宅用です。'
		          . '家の外の壁に掛けて取り付けます。'
		          . 'お湯が出るまでが早く、必要なときだけわかすので、タンクを置く場所も要りません。'
		          . 'このうち「エコジョーズ」は、燃費のいいタイプです。'
		          . 'これまで捨てていた排気の熱をもう一度使ってお湯をわかすので、ガスの使用量をおさえられます。'
		          . '本体の値段は少し上がりますが、毎月のガス代でその差を取り返していく考え方です。',
		'makers' => 'ノーリツ／リンナイ',
	),

	array(
		'fuel'   => '灯油',
		'name'   => '石油給湯器／エコフィール',
		'img'    => 'guide/oil',
		'alt'    => '家の外に置いて取り付ける石油給湯器の本体',
		'text'   => '灯油をお使いのお宅用です。家の外に置いて取り付けます。'
		          . '寒い日でもお湯の力が落ちにくいので、'
		          . '石川・福井では今もたくさんのお宅で使われています。'
		          . 'このうち「エコフィール」は、燃費のいいタイプです。'
		          . 'エコジョーズと同じ考え方で、排気の熱をもう一度使って灯油の使用量をおさえます。'
		          . '灯油を多く使うお宅ほど、差が出やすいタイプです。',
		'makers' => 'ノーリツ',
	),

	array(
		'fuel'   => 'ガス',
		'name'   => 'エネファーム',
		'img'    => 'guide/enefarm',
		'alt'    => 'パナソニックのエネファームの本体',
		'text'   => 'ガスから水素を取り出して発電し、'
		          . 'そのときに出る熱でお湯もわかす機械です。'
		          . '「お湯をわかす道具」というより「発電しながらお湯もつくる道具」で、'
		          . '発電した電気はお家で使えます。'
		          . '発電ユニットとタンクを置く場所が要ります。',
		'makers' => '',
	),

	array(
		'fuel'   => '電気',
		'name'   => '電気温水器',
		'img'    => 'guide/electric',
		'alt'    => 'パナソニックの電気温水器の本体',
		'text'   => 'エコキュートとよく間違えられますが、別のものです。'
		          . 'こちらはタンクの中のヒーターでお湯をわかします。'
		          . 'いま電気温水器をお使いなら、エコキュートにお取り替えいただくと'
		          . '電気の使用量をおさえられます。',
		'makers' => '',
	),
);

/* ------------------------------------------------------------
   2. ふろ機能のちがい
   ------------------------------------------------------------ */
$funcs = array(
	array(
		'name' => '給湯専用',
		'text' => '蛇口をひねるとお湯が出る、いちばんかんたんなタイプです。'
		        . '追い焚きはできないので、ぬるくなったらお湯を足します。'
		        . 'その分、本体のお値段はおさえられます。',
	),
	array(
		'name' => 'オート',
		'text' => 'ボタンひとつで、お湯はり・保温・追い焚きまで自動でやってくれます。'
		        . 'いま多くのお宅で使われているのが、このタイプです。',
	),
	array(
		'name' => 'フルオート',
		'text' => 'オートにできることに加えて、'
		        . 'お湯が減ったときの「たし湯」まで自動です。'
		        . '家族の入る時間がばらばらのお宅で、力を発揮します。',
	),
);

/* ------------------------------------------------------------
   3. こんなときは、そろそろ替えどきです
   ------------------------------------------------------------ */
$signs = array(
	'お湯の温度が安定しない。ぬるくなったり熱くなったりする',
	'お湯が出るまでに、前より時間がかかるようになった',
	'追い焚きに時間がかかる。追い焚きができないことがある',
	'本体から変な音がする。こげたようなにおいがする',
	'本体や配管から水がもれている',
	'リモコンに数字（エラーコード）が出る',
	'使いはじめてから10年以上たっている',
);

/* ------------------------------------------------------------
   4. エラーコード一覧へのリンク

        本番サイトにある、出来上がったページへのリンクです。
        サイトの中の /troubleshooting/ に作ったページへリンクします。
        中身は wp-content/themes/ymkrf/troubleshooting/ にあります。
   ------------------------------------------------------------ */
/* サイトの中のエラーコード一覧へリンクします。
   古いホームページへはリンクしません（2026/09/02 ユーザー指示）。 */
$err_base = home_url( '/troubleshooting/' );

$errors = array(

	array( 'name' => 'エコキュート', 'path' => 'ecocute-error/', 'makers' => array(
		'mitsubishi'     => '三菱電機',
		'panasonic'      => 'パナソニック',
		'daikin'         => 'ダイキン',
		'hitachi'        => '日立',
	) ),

	array( 'name' => 'ガス給湯器', 'path' => 'gas-error/', 'makers' => array(
		'noritz'         => 'ノーリツ',
		'rinnai'         => 'リンナイ',
		'paloma'         => 'パロマ',
	) ),

	array( 'name' => '石油給湯器', 'path' => 'oil-error/', 'makers' => array(
		'noritz'         => 'ノーリツ',
		'chofu'          => '長府製作所',
		'corona'         => 'コロナ',
	) ),

	array( 'name' => '電気温水器', 'path' => 'electric-error/', 'makers' => array(
		'mitsubishi'     => '三菱電機',
		'panasonic'      => 'パナソニック',
		'hitachi'        => '日立',
		'chofu'          => '長府製作所',
		'corona'         => 'コロナ',
		'takarastandard' => 'タカラスタンダード',
	) ),
);

/* ------------------------------------------------------------
   5. お取り替えの流れ（9ステップ）

        本番サイトの /products/ecocute/ に載っていた
        「エコキュート設置の流れ」そのままです。
        （2026/09/02 ユーザー指示で、こちらのページにも入れました）
   ------------------------------------------------------------ */
$steps = array(
	array( 'ttl' => 'ご相談',
	       'txt' => '現在ご不満に思っていること、ご予算など、どんなことでもお気軽にご相談ください。' ),
	array( 'ttl' => '現地調査',
	       'txt' => 'お見積り・ご提案を作成するために、お宅の現状や寸法などを確認させていただきます。'
	              . '15〜30分ほどです。' ),
	array( 'ttl' => 'お見積り・ご提案',
	       'txt' => 'お客様のご要望に沿って、お見積り・ご提案を提出いたします。' ),
	array( 'ttl' => 'ご検討',
	       'txt' => 'ご家族とご相談ください。リフォームは大きな買い物ですので、入念にご検討ください。' ),
	array( 'ttl' => 'ご契約',
	       'txt' => '金額・支払条件・工事日を決めて、契約をかわします。' ),
	array( 'ttl' => '工事下見',
	       'txt' => '工事の段取り、必要な部材を現場で最終確認します。' ),
	array( 'ttl' => '工事',
	       'txt' => '安全かつ速やかに工事を行います。'
	              . '住まわれているお客様や、近隣のお客様にご迷惑にならないよう細心の注意を払います。' ),
	array( 'ttl' => '完工・お引渡し',
	       'txt' => 'プランどおりにできたか厳しくチェックし、お客様にお引渡しいたします。' ),
	array( 'ttl' => 'アフターサポート',
	       'txt' => '機器の故障などに、24時間365日・年中無休で対応いたします。気になる点は点検もいたします。' ),
);

/* ------------------------------------------------------------
   6. よくあるご質問
   ------------------------------------------------------------ */
$faqs = array(

	/* --- どちらにも当てはまるもの --- */
	array(
		'tag' => '共通',
		'q' => '給湯器の寿命は、どれくらいですか？',
		'a' => 'およそ10年から15年が目安です。'
		     . '10年を過ぎて調子が悪いときは、早めのお取り替えをおすすめします。'
		     . 'とくに冬場は機器への負担が大きく、こわれやすい時期です。'
		     . 'お湯が使えなくなると、とても不便なことになります。',
	),
	array(
		'tag' => '共通',
		'q' => '工事は、どれくらいかかりますか？',
		'a' => '給湯器のお取り替えは、在庫のある機種なら工期半日です。'
		     . 'エコキュートは、特殊な工事でなければ1日で完了します。'
		     . 'ただしエコキュートを置く土間が足りないお宅では、'
		     . '設置工事の3〜4日前に土間コンクリートの工事を行います。',
	),
	array(
		'tag' => '共通',
		'q' => '現地調査には、どれくらい時間がかかりますか？',
		'a' => '15分から30分ほどです。'
		     . 'いまお使いの機種、置き場所、配管や電気・ガスの状態を見せていただきます。'
		     . 'ご相談・現地調査・お見積りまでは無料です。',
	),
	array(
		'tag' => '共通',
		'q' => 'いまと違うメーカーや種類に替えられますか？',
		'a' => 'お取り替えいただけます。'
		     . 'ガスから灯油、灯油から電気（エコキュート）のように、種類そのものを変えることもできます。'
		     . 'その場合は工事の内容が変わりますので、現地を見せていただいたうえでお見積りをお出しします。',
	),
	array(
		'tag' => '共通',
		'q' => '補助金は使えますか？',
		'a' => 'エコキュートには、国の補助金の対象になる機種があります。'
		     . '金額も申請のしめきりも年度によって変わりますので、'
		     . 'そのときにお使いいただけるものを、お見積りの際にご案内します。',
	),

	/* --- 給湯器（ガス・石油）のこと --- */
	array(
		'tag' => '給湯器',
		'q' => '号数は、いまと同じものでないといけませんか？',
		'a' => '同じ号数が基本です。'
		     . 'お湯を同時に使うことが多いお宅では、ひとつ上の号数にすることもできます。'
		     . 'ただしガス管の太さによっては追加の工事が必要になりますので、'
		     . '現地調査のときに確認させていただきます。',
	),
	array(
		'tag' => '給湯器',
		'q' => '急にお湯が出なくなりました。すぐに来てもらえますか？',
		'a' => 'できるかぎり早くうかがいます。'
		     . 'ヤマキシ給湯センターに在庫を持っている機種であれば、工期は半日です。'
		     . 'まずはお電話かLINEで、いまの状態とリモコンに出ている数字をお知らせください。',
	),

	/* --- エコキュートのこと --- */
	array(
		'tag' => 'エコキュート',
		'q' => 'エコキュートのお湯は、飲めますか？',
		'a' => 'そのまま飲むのは避けてください。'
		     . 'タンクにためたお湯は、断水のときの非常用の水としてお使いいただけますが、'
		     . 'その場合も飲用以外でご使用ください。'
		     . 'どうしても飲用が必要なときは、煮沸してください。',
	),
	array(
		'tag' => 'エコキュート',
		'q' => '置く場所は、どれくらい必要ですか？',
		'a' => 'お家のまわりに、本体（タンク）とヒートポンプを置ける広さが必要です。'
		     . '工事の作業スペースも要りますし、機種によって必要な広さも変わります。'
		     . 'また、お風呂からの距離が15m以上はなれているとエラーになることがありますので、'
		     . '設置できるかどうかは現地調査でご確認ください。',
	),
	array(
		'tag' => 'エコキュート',
		'q' => '音は、ご近所の迷惑になりませんか？',
		'a' => 'エコキュートが出す音は40dB程度といわれています。'
		     . 'エアコンの室外機と同じくらいなので、音の大きさで問題になることはほとんどありません。'
		     . 'ただ、ふつうは聞き取れない低い音（12.5Hz程度）も出ていて、'
		     . '人によっては気になることがあります。'
		     . '夜に動くことが多いので、となりのお宅の寝室の近くを避けて置き場所を決めます。',
	),
	array(
		'tag' => 'エコキュート',
		'q' => '井戸水は使えますか？',
		'a' => 'ふつうのエコキュートでは井戸水はお使いになれません。'
		     . '水道水とちがって、機器に悪い影響を与える成分（カルシウムなど）が'
		     . '多くふくまれていることがあるためです。'
		     . '井戸水に対応したエコキュートもありますが、'
		     . 'メーカーの水質検査に合格した場合だけ設置できます。'
		     . 'ふつうのものより高くなりますので、一度ご相談ください。',
	),
	array(
		'tag' => 'エコキュート',
		'q' => '設置したあと、お手入れは必要ですか？',
		'a' => 'タンクの排水栓を2分ほど開いて、たまった不純物を取り除く作業を'
		     . '年に2〜3回していただくと、お湯を清潔にたもてます。エコキュートも長持ちします。'
		     . 'そのほか、追い焚きのお湯が通る浴槽のフィルターは髪や汚れが詰まりやすいので、'
		     . 'ときどき取り除いてください。',
	),
);

get_header();
?>

<!-- =========== パンくず =========== -->
<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li><a href="<?php echo esc_url( ymkrf_products_url() ); ?>">商品・価格</a></li>
    <li>給湯器・エコキュートの選び方</li>
  </ol>
</nav>

<main id="main">

<!-- =========== 見出し =========== -->
<section class="p-guide__hero">
  <div class="l-wrap">
    <span class="c-head__en">GUIDE</span>
    <h1 class="p-guide__title">給湯器・エコキュートの<br class="sp-only">選び方</h1>
    <p class="p-guide__lead">
      「うちのはどのタイプ？」「何を基準に選べばいいの？」<br class="pc-only">
      はじめてのお取り替えでも迷わないように、順番にご説明します。
    </p>
    <img class="p-guide__herochara" src="<?php echo $asset; ?>/assets/img/character/char-search.webp"
         width="592" height="640"
         alt="ヤマキシのキャラクター「とんとこトン」が虫めがねで調べているイラスト"
         fetchpriority="high">
  </div>
</section>

<!-- =========== 1. 種類 =========== -->
<section class="l-section" id="type">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">TYPE</span>
      <h2 class="c-head__title">うちはどのタイプ？<br class="sp-only">給湯器の<span class="marker">種類</span></h2>
      <p class="c-head__lead">
        まず見ていただきたいのは「何でお湯をわかしているか」です。<br class="pc-only">
        ガス・灯油・電気のどれかで、選べる機種が決まります。
      </p>
    </div>

    <div class="p-guide__types">
      <?php foreach ( $fuels as $f ) : ?>
        <div class="p-guide__type">
          <?php /* 写真があるものは出します。無いものは、札の高さがそろうように
                   同じ大きさの仮の枠を出します。 */ ?>
          <div class="p-guide__typeph">
            <?php if ( $f['img'] ) : ?>
              <picture>
                <source srcset="<?php echo $asset; ?>/assets/img/<?php echo esc_attr( $f['img'] ); ?>.webp" type="image/webp">
                <img src="<?php echo $asset; ?>/assets/img/<?php echo esc_attr( $f['img'] ); ?>.jpg"
                     width="800" height="800" alt="<?php echo esc_attr( $f['alt'] ); ?>"
                     loading="lazy" decoding="async">
              </picture>
            <?php else : ?>
              <span class="p-guide__typenoph">写真は準備中です</span>
            <?php endif; ?>
          </div>
          <p class="p-guide__typefuel"><?php echo esc_html( $f['fuel'] ); ?></p>
          <h3 class="p-guide__typename"><?php echo esc_html( $f['name'] ); ?></h3>
          <p class="p-guide__typetext"><?php echo ymkrf_brk( $f['text'] ) /* phpcs:ignore */; ?></p>
          <?php if ( $f['makers'] ) : ?>
            <p class="p-guide__typemaker">取り扱いメーカー：<?php echo esc_html( $f['makers'] ); ?></p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <p class="p-guide__note">
      ※写真はイメージです。同じ種類でも、機種によって形や大きさは変わります。<br>
      いまお使いのものと同じ種類にお取り替えいただくのが、いちばん工事が少なくて済みます。
      種類そのものを変えることもできますので、迷われたときはご相談ください。
    </p>
  </div>
</section>

<!-- =========== 2. ふろ機能 =========== -->
<section class="l-section l-section--gray" id="func">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">FUNCTION</span>
      <img class="c-head__chara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-smile.webp"
           width="592" height="640" alt="" loading="lazy" decoding="async">
      <h2 class="c-head__title">お風呂の<span class="marker">機能</span>のちがい</h2>
      <p class="c-head__lead">
        同じ種類の給湯器でも、お風呂まわりでできることが3つに分かれます。
      </p>
    </div>

    <div class="p-guide__funcs">
      <?php foreach ( $funcs as $i => $fn ) : ?>
        <div class="p-guide__func">
          <p class="p-guide__funcno"><?php echo (int) ( $i + 1 ); ?></p>
          <h3 class="p-guide__funcname"><?php echo esc_html( $fn['name'] ); ?></h3>
          <p class="p-guide__functext"><?php echo ymkrf_brk( $fn['text'] ) /* phpcs:ignore */; ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- =========== 3. 大きさの選び方 =========== -->
<section class="l-section" id="size">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">SIZE</span>
      <img class="c-head__chara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-flag.webp"
           width="592" height="640" alt="" loading="lazy" decoding="async">
      <h2 class="c-head__title"><span class="marker">大きさ</span>の選び方</h2>
    </div>

    <div class="p-guide__sizes">

      <div class="p-guide__size">
        <h3 class="p-guide__sizettl">ガス給湯器は「号数」</h3>
        <p class="p-guide__sizetext">
          一度にどれだけのお湯をつくれるか、を表す数字です。
          数字が大きいほど、たくさんのお湯を一度に使えます。
        </p>
        <ul class="p-guide__sizelist">
          <li><b>16号</b>　1〜2人でお使いのご家庭に</li>
          <li><b>20号</b>　2〜4人でお使いのご家庭に</li>
          <li><b>24号</b>　4人以上、お湯を同時に使うことが多いご家庭に</li>
        </ul>
        <p class="p-guide__sizetext">
          <strong>基本は、いまお使いのものと同じ号数をお選びください。</strong>
          お風呂とキッチンでお湯を同時に使うことが多いお宅は、
          ひとつ上の号数にすると、お湯の勢いが落ちにくくなります。
        </p>
      </div>

      <div class="p-guide__size">
        <h3 class="p-guide__sizettl">石油給湯器は「キロ数」</h3>
        <p class="p-guide__sizetext">
          ガス給湯器の号数と同じで、一度につくれるお湯の量を表します。
        </p>
        <ul class="p-guide__sizelist">
          <li><b>3万キロ</b>　少人数でお使いのご家庭に</li>
          <li><b>4万キロ</b>　ご家族でお使いのご家庭に。いちばん多いのがこの大きさです</li>
        </ul>
        <p class="p-guide__sizetext">
          <strong>こちらも、いまお使いのものと同じ大きさが基本です。</strong>
        </p>
      </div>

      <div class="p-guide__size">
        <h3 class="p-guide__sizettl">エコキュートは「タンク容量」</h3>
        <p class="p-guide__sizetext">
          お湯をためておく大きさです。ご家族の人数で選びます。
        </p>
        <ul class="p-guide__sizelist">
          <li><b>370L</b>　3〜4人でお使いのご家庭に</li>
          <li><b>460L</b>　4〜7人でお使いのご家庭、お湯をたくさん使うご家庭に</li>
        </ul>
        <p class="p-guide__sizetext">
          シャワーの勢いを重ねて大事にされる方には、<b>高圧タイプ</b>もございます。
          2階・3階でシャワーをお使いのお宅では、こちらが向いています。
        </p>
      </div>

    </div>
  </div>
</section>

<!-- =========== 4. 替えどき =========== -->
<section class="l-section l-section--tint" id="sign">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">TIMING</span>
      <img class="c-head__chara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-search.webp"
           width="592" height="640" alt="" loading="lazy" decoding="async">
      <h2 class="c-head__title">こんなときは、<br class="sp-only">そろそろ<span class="marker">替えどき</span></h2>
      <p class="c-head__lead">
        ひとつでも当てはまるものがあれば、一度見せていただくことをおすすめします。
      </p>
    </div>

    <ul class="p-guide__signs">
      <?php foreach ( $signs as $s ) : ?>
        <li><?php echo esc_html( $s ); ?></li>
      <?php endforeach; ?>
    </ul>

    <p class="p-guide__note">
      給湯器は、こわれてから慌てて選ぶことになりがちです。
      とくに真冬は工事のご依頼が重なり、お待ちいただくこともあります。
      <strong>お湯が出ているうちにご相談いただくと、ゆっくり選べます。</strong>
    </p>
  </div>
</section>

<!-- =========== 5. エラーコード =========== -->
<section class="l-section" id="error">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">ERROR CODE</span>
      <img class="c-head__chara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-wrench.webp"
           width="592" height="640" alt="" loading="lazy" decoding="async">
      <h2 class="c-head__title">故障かな？<br class="sp-only"><span class="marker">エラーコード</span>から調べる</h2>
      <p class="c-head__lead">
        リモコンに数字が出ていたら、修理をご依頼になる前にこちらをご覧ください。<br class="pc-only">
        ご自分で直せるものもあります。お家の給湯器の種類とメーカーをお選びください。
      </p>
    </div>

    <div class="p-guide__errors">
      <?php foreach ( $errors as $e ) : ?>
        <div class="p-guide__error">
          <p class="p-guide__errorttl"><?php echo esc_html( $e['name'] ); ?></p>
          <ul class="p-guide__errorlist">
            <?php foreach ( $e['makers'] as $mslug => $mname ) : ?>
              <li>
                <a href="<?php echo esc_url( $err_base . $e['path'] . $mslug . '/' ); ?>">
                  <?php echo esc_html( $mname ); ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>

    <p class="p-guide__note">
      エラーが消えないとき、水がもれているときは、お使いにならずにご連絡ください。
      お近くの店舗からうかがいます。
    </p>
  </div>
</section>

<!-- =========== 6. 流れ =========== -->
<section class="l-section l-section--gray" id="flow">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">FLOW</span>
      <img class="c-head__chara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-truck.webp"
           width="592" height="640" alt="" loading="lazy" decoding="async">
      <h2 class="c-head__title">お取り替えの<span class="marker">流れ</span></h2>
      <p class="c-head__lead">
        ご相談から工事の完了まで、ヤマキシがまとめてお引き受けします。<br class="pc-only">
        ご相談・現地調査・お見積りまでは無料です。
      </p>
    </div>

    <div class="p-guide__flow">
      <?php foreach ( $steps as $i => $st ) : ?>
        <div class="p-guide__step">
          <span class="no"><?php echo (int) ( $i + 1 ); ?></span>
          <b><?php echo esc_html( $st['ttl'] ); ?></b><?php echo esc_html( $st['txt'] ); ?>
        </div>
      <?php endforeach; ?>
    </div>

    <p class="p-guide__note">
      工事そのものは、給湯器なら在庫のある機種で<strong>半日</strong>、エコキュートは<strong>1日</strong>で完了します。
      その日のうちにお湯が使えるようになります。<br>
      エコキュートは、<strong>北陸電力への申請作業も標準工事にふくまれています。</strong>
      お客様にしていただく手続きはありません。
    </p>
  </div>
</section>

<!-- =========== 7. ヤマキシの強み =========== -->
<section class="l-section" id="reason">
  <div class="l-wrap">
    <div class="c-head">
      <span class="c-head__en">REASON</span>
      <img class="c-head__chara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-cape.webp"
           width="592" height="640" alt="" loading="lazy" decoding="async">
      <h2 class="c-head__title">ヤマキシに<span class="marker">おまかせいただく理由</span></h2>
    </div>

    <div class="p-guide__reasons">
      <div class="p-guide__reason">
        <h3>在庫のある機種なら、工期は半日</h3>
        <p>お湯が出ない、というときこそ急ぎます。給湯センターに在庫を持っているので、すぐに工事にうかがえます。</p>
      </div>
      <div class="p-guide__reason">
        <h3>申請も工事も、まとめておまかせ</h3>
        <p>エコキュートの北陸電力への申請、古い機器の撤去処分まで、標準工事にふくまれています。</p>
      </div>
      <div class="p-guide__reason">
        <h3>石川・福井に11店舗。顔が見えます</h3>
        <p>工事のあとも、近くの店にお立ち寄りいただけます。商品延長10年保証・工事保証5年・24時間365日トラブル対応付きです。</p>
      </div>
    </div>
  </div>
</section>

<!-- =========== 8. よくあるご質問 =========== -->
<section class="l-section l-section--soft" id="faq">
  <div class="l-wrap l-wrap--narrow">
    <div class="c-head">
      <span class="c-head__en">FAQ</span>
      <img class="c-head__chara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-peek.webp"
           width="592" height="640" alt="" loading="lazy" decoding="async">
      <h2 class="c-head__title">よくある<span class="marker">ご質問</span></h2>
    </div>

    <div class="p-guide__faqs">
      <?php foreach ( $faqs as $fq ) : ?>
        <details class="p-guide__faq">
          <summary>
            <?php if ( ! empty( $fq['tag'] ) ) : ?>
              <span class="p-guide__faqtag"><?php echo esc_html( $fq['tag'] ); ?></span>
            <?php endif; ?>
            <?php echo esc_html( $fq['q'] ); ?>
          </summary>
          <p><?php echo ymkrf_brk( $fq['a'] ) /* phpcs:ignore */; ?></p>
        </details>
      <?php endforeach; ?>
    </div>

    <p class="p-guide__note">
      ほかのご質問は<a href="<?php echo esc_url( home_url( '/faq/' ) ); ?>">よくあるご質問</a>にもまとめています。
    </p>
  </div>
</section>

<!-- =========== 9. 最後のひとこと ===========
     このページは読みもののページなので、商品への誘導は入れていません
     （2026/09/01 ユーザー指示）。
     ご相談への声かけだけにしています。
     お問い合わせのボタンは、ヘッダーとフッターに出ています。 -->
<section class="l-section" id="cta">
  <div class="l-wrap l-wrap--narrow">
    <div class="c-head">
      <img class="c-head__chara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-bow.webp"
           width="592" height="640" alt="" loading="lazy" decoding="async">
      <h2 class="c-head__title">迷われたら、<span class="marker">今のお湯まわり</span>を見せてください</h2>
      <p class="c-head__lead">
        機種が決まっていなくても大丈夫です。<br class="pc-only">
        いまお使いのものを見せていただければ、お宅に合うものをご案内します。<br>
        ご相談・お見積りは無料です。石川・福井の11店舗から、お近くの店がうかがいます。
      </p>
    </div>
  </div>
</section>

</main>

<?php get_footer(); ?>
