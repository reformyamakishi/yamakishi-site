<?php
/**
 * ymkrf-faq.php ─ よくあるご質問（/faq/）
 *
 * 置き場所： wp-content/themes/ymkrf/ymkrf-faq.php
 *
 * もとの資料：ユーザーからいただいたPDF
 *   「よくある質問｜福井・石川のリフォームならヤマキシへお任せ！」
 *   （本番サイト /faq/ を印刷したもの）
 *
 * ★見た目
 *   お客様（お客様の声のイラスト）が質問し、とんとこトンが答える会話の形にしています。
 *   イラストは assets/img/voice/voice-01.png 〜 を順番に使っています。
 *   見た目は assets/css/faq.css の .p-qa〜 で調整します。
 *
 * ★内容を直すとき
 *   下の $faq を直せば、画面と、検索エンジン用のデータ（FAQPage）の両方が変わります。
 *
 * ※「追加請求はありません」といった言い切りは書かないでください。
 *   このページの「追加の費用について」は、
 *   「必ず事前に説明し、ご了承をいただいてから進めます」という書き方にしてあります。
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$asset = get_stylesheet_directory_uri();

/* まとまり（カテゴリ）と、質問・答え */
$faq = array(

	array(
		'id'    => 'estimate',
		'ttl'   => 'お見積り・ご相談のこと',
		'chara' => array( 'char-search.webp', 592, 640 ),
		'qa'    => array(
			array( 'お見積りに費用はかかりますか？',
			       'お見積りは無料です。' ),
			array( '現地調査も無料ですか？',
			       'はい、無料です。お見積りをつくるために必要な寸法の確認・状況の確認を行ったうえで、ご提案します。' ),
			array( 'まだリフォームするか決めていません。相談だけでも大丈夫？',
			       'もちろん大丈夫です。ご不満な点・ご予算・優先順位をうかがい、必要な範囲から整理してご提案します。' ),
			array( '図面がなくてもお見積りできますか？',
			       'はい、できます。現地で採寸と現状確認を行い、お見積りを作成します。' ),
			array( '相見積もり（他社と比べること）をしてもいいですか？',
			       '問題ありません。内容・保証・工事範囲が同じ条件かどうかも含めて、比べるときのポイントをご案内します。' ),
			array( 'ショールームで実物を見ながら相談できますか？',
			       'はい。各店で住宅設備を展示しており、実物を見比べていただけます（展示のない店舗もありますので、店舗のご案内をご確認ください）。' ),
			array( 'お見積書で確かめるべきポイントは？',
			       '3つあります。①工事範囲（どこまで含むか）②保証（メーカー／工事／延長）③追加になり得る条件（下地・配管など）です。' ),
		),
	),

	array(
		'id'    => 'price',
		'ttl'   => '料金・お支払いのこと',
		'chara' => array( 'char-otoku.webp', 488, 640 ),
		'qa'    => array(
			array( '予算が決まっています。予算内で提案してもらえますか？',
			       'はい。ご希望をうかがい、優先順位に合わせてプラン・機器のグレード・工事範囲を調整してご提案します。' ),
			array( 'リフォームローンの取り扱いはありますか？',
			       '提携のリフォームローンをご紹介いたします。お気軽にご相談ください。' ),
			array( '広告に載っている商品・工事金額で対応してくれますか？',
			       '広告に記載のとおり施工・販売いたします。ただし、それぞれの現場の状況や、お家の仕様・プラン変更にともなって、多少の金額の変更はございます。' ),
			array( '「工事費込み」とは、どこまで含まれますか？',
			       'パックに含まれる工事範囲をはっきりさせたうえでご案内します。現場の状況で追加が必要な場合は、かならず事前にご説明し、ご了承をいただいてから進めます。' ),
			array( '現金以外での支払いはできますか？',
			       'できます。お振込み、クレジットカード（JCB／マスターカード／VISA）、PayPayでのお支払いが可能です。クレジットカードとPayPayでの決済は、店頭までお越しいただくようお願いいたします。' ),
			array( 'ヤマキシのお買物券は使えますか？',
			       'ご利用いただけます。お使いになるときは、あらかじめ担当者へお伝えください。ほかのサービスと併用できない場合がありますので、お見積りのときにご確認ください。' ),
			array( 'どうして安いのですか？',
			       '仕入れの工夫と、中間コストを減らすことで価格を抑えています。工事は営業担当が現場を確認しながら進めます。' ),
		),
	),

	array(
		'id'    => 'extra',
		'ttl'   => '追加の費用のこと',
		'chara' => array( 'char-plan.webp', 640, 595 ),
		'qa'    => array(
			array( '契約のあとに、追加の費用が発生することはありませんか？',
			       '工事の前には確認できなかったことが分かり、追加の費用が発生することがあります。たとえば、お風呂を解体したあとに配管の傷みが激しいことが分かり、予定になかった配管の交換が必要になる場合です。そのようなときは、<b>かならず状況と追加の費用をお客様にご確認いただいたうえで</b>、追加の工事を行います。だまって費用が増えることはありません。' ),
			array( '追加の費用が出るのは、どんなときですか？',
			       '解体したあとに分かる劣化（配管・下地など）で、交換が必要になった場合などです。かならず状況と費用をご確認いただいてから行います。' ),
			array( '追加の工事は割高になりませんか？',
			       'ほとんどの追加工事は、オプション工事として価格が決まっています。契約の前でも後でも、価格は同じです。' ),
		),
	),

	array(
		'id'    => 'work',
		'ttl'   => '工事のこと',
		'chara' => array( 'char-hammer.webp', 503, 640 ),
		'qa'    => array(
			array( '小さな工事でも対応してもらえますか？',
			       'どんな小さな工事でも、喜んでお受けします。カギの交換、網戸の張り替え、混合栓の取り換えなど、ささいなことでもお気軽にご相談ください。' ),
			array( 'どのような施工体制をとっているのですか？',
			       '丸投げの発注はしていません。当社の常用の職人、もしくは協力会社の職人が工事を行います。また、担当の営業が責任を持って全体の工事管理を行います。' ),
			array( '住みながら工事できますか？',
			       'できます。工事の範囲・日数・使えない時間帯を事前に共有し、ご負担の少ない段取りで進めます。' ),
			array( '工事中に家を空けても大丈夫ですか？',
			       '大丈夫です。施錠や貴重品の管理など、事前に取り決めをしたうえで進めます。' ),
			array( '工事中の職人さんに、気づかいはしたほうがいいですか？',
			       '職人へのお気づかいは無用です。休憩時間のお茶菓子のご提供や、お心付けなども必要ありません。お気づかいの有無で工事の出来が変わるようなことは、一切ございません。' ),
			array( '仕上がりに満足できないときは、やり直してもらえますか？',
			       'お客様にご納得いただけるよう、責任を持ってやり直しいたします。' ),
		),
	),

	array(
		'id'    => 'warranty',
		'ttl'   => '保証のこと',
		'chara' => array( 'char-anshin.webp', 503, 640 ),
		'qa'    => array(
			array( '工事に対する保証はありますか？',
			       'メーカーの保証はもちろん、自社の工事保証書も発行しています。工事が終わったあとも責任を持って対応いたします。' ),
			array( '工事の保証は何年ですか？',
			       '当社の工事保証は5年間です。メーカー保証とあわせて保証書をお渡しします。' ),
			array( 'メーカー保証が切れたあとはどうなりますか？',
			       'メーカー保証（通常1〜2年）が終わったあとも、設置から最長10年まで保証する「延長保証」をご用意しています。' ),
			array( '水まわりパックの延長保証は有料ですか？',
			       '水まわりパックは、10年の延長保証が無料でパック価格に含まれています。' ),
			array( '水まわりパック以外でも、延長保証は付けられますか？',
			       'はい、有償でお付けできます。延長の期間は5年・8年・10年からお選びいただけます。' ),
		),
	),

	array(
		'id'    => 'area',
		'ttl'   => 'エリア・店舗のこと',
		'chara' => array( 'char-truck.webp', 640, 359 ),
		'qa'    => array(
			array( '対応エリアはどこですか？',
			       '各店舗から30分でうかがえる市町村を中心に対応しています。' ),
			array( '対応エリアの外でも相談できますか？',
			       'まずはご相談ください。内容・距離・工事の規模によって、対応できるかどうかをご案内します（基本は30分圏内が中心です）。' ),
			array( '担当の店舗は選べますか？',
			       'お困りごとがあったときにできるだけ早く対応できるよう、基本的にはお近くの店舗が担当になります。特別な理由でほかの店舗に担当してもらいたい場合は、まずはご相談ください。' ),
		),
	),
);

/* 検索エンジン用のデータ（FAQPage）。上の $faq からそのまま作ります */
$ld = array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array() );
foreach ( $faq as $g ) {
	foreach ( $g['qa'] as $qa ) {
		$ld['mainEntity'][] = array(
			'@type'          => 'Question',
			'name'           => $qa[0],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => wp_strip_all_tags( $qa[1] ),
			),
		);
	}
}

$vi = 0;   /* お客様のイラストの番号（1〜40をぐるぐる使います） */

get_header();
?>

<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li>よくあるご質問</li>
  </ol>
</nav>

<main id="main">

<div class="p-pagehead">
  <div class="l-wrap p-pagehead__inner">
    <img class="p-pagehead__chara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-hello.webp"
         width="503" height="640" alt="" loading="lazy" decoding="async">
    <span class="p-pagehead__en">FAQ</span>
    <h1 class="p-pagehead__title">よくあるご質問</h1>
    <p class="p-pagehead__lead">
      お客様からよくいただくご質問に、<br class="xs-only">とんとこトンがお答えします。<br>
      ここにないことも、<b>お気軽にお聞きください</b>。
    </p>
  </div>
</div>

<!-- =========== 目次 =========== -->
<section class="l-section">
  <div class="l-wrap l-wrap--narrow">
    <p class="p-qanav__ttl">知りたいことから見る</p>
    <ul class="p-qanav">
      <?php foreach ( $faq as $g ) : ?>
        <li><a href="#<?php echo esc_attr( $g['id'] ); ?>"><?php echo esc_html( $g['ttl'] ); ?>
          <span><?php echo count( $g['qa'] ); ?></span></a></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<!-- =========== 質問と答え =========== -->
<?php $i = 0; foreach ( $faq as $g ) : $i++; ?>
<section class="l-section<?php echo ( $i % 2 === 1 ) ? ' l-section--soft' : ''; ?>" id="<?php echo esc_attr( $g['id'] ); ?>">
  <div class="l-wrap l-wrap--narrow">

    <div class="c-head">
      <img class="p-lpchara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/<?php echo esc_attr( $g['chara'][0] ); ?>"
           width="<?php echo (int) $g['chara'][1]; ?>" height="<?php echo (int) $g['chara'][2]; ?>"
           alt="" loading="lazy" decoding="async">
      <h2 class="c-head__title"><?php echo esc_html( $g['ttl'] ); ?></h2>
    </div>

    <div class="p-qa">
      <?php foreach ( $g['qa'] as $qa ) : $vi++; $vn = sprintf( '%02d', ( ( $vi - 1 ) % 40 ) + 1 ); ?>
        <div class="p-qa__set" data-reveal>

          <div class="p-qa__row p-qa__row--q">
            <span class="p-qa__face">
              <img src="<?php echo $asset; ?>/assets/img/voice/voice-<?php echo $vn; ?>.png"
                   width="117" height="117" alt="" loading="lazy" decoding="async">
            </span>
            <p class="p-qa__bubble p-qa__bubble--q"><?php echo esc_html( $qa[0] ); ?></p>
          </div>

          <div class="p-qa__row p-qa__row--a">
            <p class="p-qa__bubble p-qa__bubble--a"><?php echo wp_kses_post( $qa[1] ); ?></p>
            <span class="p-qa__face p-qa__face--ton">
              <img src="<?php echo $asset; ?>/assets/img/character/char-icon.png"
                   width="117" height="117" alt="とんとこトン" loading="lazy" decoding="async">
            </span>
          </div>

        </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>
<?php endforeach; ?>

<!-- =========== 関連ページ =========== -->
<section class="l-section">
  <div class="l-wrap l-wrap--narrow">
    <h2 class="p-lprel__h2">あわせてご覧ください</h2>
    <ul class="p-lprel">
      <li><a href="<?php echo esc_url( home_url( '/products/' ) ); ?>">商品・価格</a><span>工事費込みのパック価格</span></li>
      <li><a href="<?php echo esc_url( home_url( '/flow/' ) ); ?>">リフォームの流れ</a><span>ご相談からアフターサポートまで</span></li>
      <li><a href="<?php echo esc_url( home_url( '/warranty/' ) ); ?>">保証について</a><span>工事保証5年＋延長保証 最長10年</span></li>
      <li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">こだわり・特徴と施工体制</a><span>安い・早い・安心の3つのコンセプト</span></li>
      <li><a href="<?php echo esc_url( home_url( '/works/' ) ); ?>">施工事例</a><span>Before・Afterと、かかった費用</span></li>
      <li><a href="<?php echo esc_url( home_url( '/voice/' ) ); ?>">お客様の声</a><span>アンケート「仕事の通信簿」</span></li>
    </ul>
  </div>
</section>

<!-- =========== CTA =========== -->
<section class="l-section l-section--soft">
  <div class="l-wrap l-wrap--narrow">
    <div class="p-lpcta">
      <img class="p-lpcta__chara" src="<?php echo $asset; ?>/assets/img/character/char-stand.webp" width="503" height="640"
           alt="" loading="lazy" decoding="async">
      <h2 class="p-lpcta__title">ここに<span class="marker">ないこと</span>も、どうぞ</h2>
      <p class="p-lpcta__text">
        見積り・現地調査は無料です。しつこい営業は一切いたしません。<br>
        「こんなこと聞いていいのかな」ということこそ、お聞かせください。
      </p>
      <div class="p-lpcta__btns">
        <a class="c-btn c-btn--line c-btn--block" href="https://lin.ee/UJZuSTrz" rel="noopener" data-cta="faq-cta">
          <span class="c-btn__label">LINEで無料見積り<span class="c-btn__sub">ご相談だけでもOK・24時間受付</span></span>
        </a>
        <a class="c-btn c-btn--block" href="<?php echo esc_url( home_url( '/inquiry/webrsv/' ) ); ?>" data-cta="faq-cta">
          <span class="c-btn__label">ショールーム来店予約<span class="c-btn__sub">初回特典500円ヤマキシお買物券<br>※展示のない店舗もあります</span></span>
        </a>
        <a class="c-btn c-btn--ghost c-btn--block" href="tel:0800-777-3331" data-cta="faq-cta">
          <span class="c-btn__label">0800-777-3331<span class="c-btn__sub">通話無料・受付 9:00〜17:00</span></span>
        </a>
      </div>
    </div>
  </div>
</section>

<script type="application/ld+json"><?php echo wp_json_encode( $ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>

</main>

<?php get_footer();
