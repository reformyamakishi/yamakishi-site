<?php
/**
 * ymkrf-flow.php ─ リフォームの流れ（/flow/）
 *
 * 置き場所： wp-content/themes/ymkrf/ymkrf-flow.php
 *
 * もとの資料：ユーザーからいただいたPDF
 *   「リフォームの流れ｜リフォームヤマキシ」（本番サイト /flow/ を印刷したもの）
 *   ※PDFの挿絵（クリップアート）は使っていません。ユーザー指示により、
 *     とんとこトンとサイトの番号カードで組み立てています。
 *
 * ★内容を直すとき
 *   9つのステップは、下の $steps に書いてあります。ここを直せば表示も変わります。
 *   見た目は assets/css/flow.css の .p-flow〜 で調整します。
 *
 * ※「追加請求はありません」といった言い切りは書かないでください。
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$asset = get_stylesheet_directory_uri();

/* 9つのステップ。
   group … 3つのまとまりの見出し（空なら、前のまとまりの続き）
   chara … そのまとまりの最初に出すとんとこトンの絵 */
$steps = array(
	array(
		'group' => 'まずは、お話をきかせてください',
		'chara' => array( 'char-hello.webp', 503, 640 ),
		'no'    => '1',
		'ttl'   => 'ご相談',
		'txt'   => 'いま不満に思っていること、ご予算など、どんなことでもお気軽にご相談ください。'
		         . '「これくらいで呼んでいいのかな」ということこそ、お聞かせください。',
		'note'  => 'お電話・LINE・ご来店、どれでも受け付けています。',
	),
	array(
		'group' => '', 'chara' => null,
		'no'    => '2',
		'ttl'   => '現地調査',
		'txt'   => 'お見積り・ご提案をつくるために、お宅の現状や寸法などを確認させていただきます。',
		'note'  => '現地調査は無料です。',
	),
	array(
		'group' => '', 'chara' => null,
		'no'    => '3',
		'ttl'   => 'お見積り・ご提案',
		'txt'   => 'お客様のご要望に沿って、お見積りとご提案をお出しします。',
		'note'  => 'お見積りも無料です。標準工事に入らない工事が必要なときは、'
		         . 'この時点で内訳をはっきりお見せします。',
	),
	array(
		'group' => '', 'chara' => null,
		'no'    => '4',
		'ttl'   => 'ご検討',
		'txt'   => 'ご家族とご相談ください。リフォームは大きな買い物ですので、'
		         . 'じっくりご検討ください。',
		'note'  => 'この間に、しつこい営業をすることは一切ありません。',
	),

	array(
		'group' => 'ご納得いただけたら、工事に入ります',
		'chara' => array( 'char-hammer.webp', 503, 640 ),
		'no'    => '5',
		'ttl'   => 'ご契約',
		'txt'   => '金額・お支払い条件・工事日を決めて、契約を交わします。',
		'note'  => '',
	),
	array(
		'group' => '', 'chara' => null,
		'no'    => '6',
		'ttl'   => '工事下見',
		'txt'   => '工事の段取りと、必要な部材を、現場で最終確認します。',
		'note'  => '',
	),
	array(
		'group' => '', 'chara' => null,
		'no'    => '7',
		'ttl'   => '工事',
		'txt'   => '安全に、そして速やかに工事を行います。'
		         . '住まわれているお客様や、ご近所の方にご迷惑にならないよう、細心の注意を払います。',
		'note'  => '営業担当が現場も管理しますので、工事中の「これ、どうなっているの？」にも'
		         . 'その場でお答えできます。',
	),
	array(
		'group' => '', 'chara' => null,
		'no'    => '8',
		'ttl'   => '完工・お引き渡し',
		'txt'   => 'プランどおりにできているか厳しくチェックし、お客様にお引き渡しいたします。',
		'note'  => 'このとき、工事保証書をお渡しします。',
	),

	array(
		'group' => '工事のあとも、ずっと',
		'chara' => array( 'char-anshin.webp', 503, 640 ),
		'no'    => '9',
		'ttl'   => 'アフターサポート',
		'txt'   => '機器の故障などに、24時間365日・年中無休で対応いたします。'
		         . '気になる点は点検もいたします。',
		'note'  => 'お近くのお店がそのまま窓口です。「どこに言えばいいか分からない」ということがありません。',
	),
);

get_header();
?>

<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li>リフォームの流れ</li>
  </ol>
</nav>

<main id="main">

<div class="p-pagehead">
  <div class="l-wrap p-pagehead__inner">
    <img class="p-pagehead__chara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-plan.webp"
         width="640" height="595" alt="" loading="lazy" decoding="async">
    <span class="p-pagehead__en">FLOW</span>
    <h1 class="p-pagehead__title">リフォームの流れ</h1>
    <p class="p-pagehead__lead">
      ご相談から工事のあとまで、<br class="xs-only">9つのステップでご案内します。<br>
      <b>見積り・現地調査は無料</b>です。
    </p>
  </div>
</div>

<!-- =========== 9つのステップ =========== -->
<section class="l-section">
  <div class="l-wrap l-wrap--narrow">

    <?php foreach ( $steps as $st ) : ?>

      <?php if ( $st['group'] !== '' ) : ?>
        <?php if ( $st['no'] !== '1' ) : ?></ol><?php endif; ?>
        <div class="p-flow__group" data-reveal>
          <?php if ( $st['chara'] ) : ?>
            <img class="p-flow__chara" src="<?php echo $asset; ?>/assets/img/character/<?php echo esc_attr( $st['chara'][0] ); ?>"
                 width="<?php echo (int) $st['chara'][1]; ?>" height="<?php echo (int) $st['chara'][2]; ?>"
                 alt="" loading="lazy" decoding="async">
          <?php endif; ?>
          <h2 class="p-flow__grouptitle"><?php echo esc_html( $st['group'] ); ?></h2>
        </div>
        <ol class="p-flow">
      <?php endif; ?>

      <li class="p-flow__item" data-reveal>
        <span class="p-flow__no"><?php echo esc_html( $st['no'] ); ?></span>
        <div class="p-flow__body">
          <h3 class="p-flow__ttl"><?php echo esc_html( $st['ttl'] ); ?></h3>
          <p class="p-flow__txt"><?php echo esc_html( $st['txt'] ); ?></p>
          <?php if ( $st['note'] !== '' ) : ?>
            <p class="p-flow__note"><?php echo esc_html( $st['note'] ); ?></p>
          <?php endif; ?>
        </div>
      </li>

    <?php endforeach; ?>
    </ol>

    <p class="p-lpnext">
      <a class="p-lpnext__link" href="<?php echo esc_url( home_url( '/about/' ) ); ?>#speed">
        <span class="p-lpnext__ttl">工事にかかる日数の目安を見る</span>
        <span class="p-lpnext__txt">キッチン最短2日、お風呂最短5日、トイレ・洗面台は最短半日から</span>
      </a>
    </p>
    <p class="p-lpnext">
      <a class="p-lpnext__link" href="<?php echo esc_url( home_url( '/warranty/' ) ); ?>">
        <span class="p-lpnext__ttl">お引き渡しのあとの保証を見る</span>
        <span class="p-lpnext__txt">工事保証5年と、メーカー延長保証 最長10年について</span>
      </a>
    </p>

  </div>
</section>

<!-- =========== 関連ページ =========== -->
<section class="l-section l-section--soft">
  <div class="l-wrap l-wrap--narrow">
    <h2 class="p-lprel__h2">あわせてご覧ください</h2>
    <ul class="p-lprel">
      <li><a href="<?php echo esc_url( home_url( '/products/' ) ); ?>">商品・価格</a><span>工事費込みのパック価格</span></li>
      <li><a href="<?php echo esc_url( home_url( '/works/' ) ); ?>">施工事例</a><span>Before・Afterと、かかった費用</span></li>
      <li><a href="<?php echo esc_url( home_url( '/voice/' ) ); ?>">お客様の声</a><span>アンケート「仕事の通信簿」</span></li>
      <li><a href="<?php echo esc_url( home_url( '/warranty/' ) ); ?>">保証について</a><span>工事保証5年＋延長保証 最長10年</span></li>
      <li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">こだわり・特徴と施工体制</a><span>安い・早い・安心の3つのコンセプト</span></li>
      <li><a href="<?php echo esc_url( home_url( '/staff/' ) ); ?>">スタッフ紹介</a><span>各店の営業担当の顔ぶれ</span></li>
    </ul>
  </div>
</section>

<!-- =========== CTA =========== -->
<section class="l-section">
  <div class="l-wrap l-wrap--narrow">
    <div class="p-lpcta">
      <img class="p-lpcta__chara" src="<?php echo $asset; ?>/assets/img/character/char-stand.webp" width="503" height="640"
           alt="" loading="lazy" decoding="async">
      <h2 class="p-lpcta__title">まずは、<span class="marker">ステップ1</span>から</h2>
      <p class="p-lpcta__text">
        見積り・現地調査は無料です。しつこい営業は一切いたしません。<br>
        「いくらかかるか知りたいだけ」でも歓迎です。
      </p>
      <div class="p-lpcta__btns">
        <a class="c-btn c-btn--line c-btn--block" href="https://lin.ee/UJZuSTrz" rel="noopener" data-cta="flow-cta">
          <span class="c-btn__label">LINEで相談する<span class="c-btn__sub">写真を送るだけでもOK・24時間受付</span></span>
        </a>
        <a class="c-btn c-btn--block" href="<?php echo esc_url( home_url( '/inquiry/webrsv/' ) ); ?>" data-cta="flow-cta">
          <span class="c-btn__label">ショールーム来店予約<span class="c-btn__sub">初回特典500円ヤマキシお買物券<br>※展示のない店舗もあります</span></span>
        </a>
        <a class="c-btn c-btn--ghost c-btn--block" href="tel:0800-777-3331" data-cta="flow-cta">
          <span class="c-btn__label">0800-777-3331<span class="c-btn__sub">通話無料・受付 9:00〜17:00</span></span>
        </a>
      </div>
    </div>
  </div>
</section>

</main>

<?php get_footer();
