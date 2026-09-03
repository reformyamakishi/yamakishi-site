<?php
/**
 * ymkrf-shops.php ─ 店舗・対応エリア（/shops/）
 *
 * 置き場所： wp-content/themes/ymkrf/ymkrf-shops.php
 *
 * もとの資料：ユーザーからいただいたPDF2つ
 *   「店舗・ショールーム｜リフォームヤマキシ」（本番サイト /shops/）
 *   「店舗のご案内｜株式会社 ヤマキシ」（コーポレートサイト）
 *
 * ★地図について
 *   Googleマップは「貼り込み（埋め込み）」ではなく、ただのリンクにしています。
 *   埋め込みだとGoogle Cloudの課金対象になることがあるためです。
 *   リンクなら費用はかかりません。ここは変えないでください。
 *
 * ★内容を直すとき
 *   店舗の情報は、下の $shops を直せば、画面と検索エンジン用のデータの両方が変わります。
 *
 * ※「追加請求はありません」といった言い切りは書かないでください。
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$asset = get_stylesheet_directory_uri();

/* 店舗の情報は inc/functions-shops.php にまとめてあります。
   直すときはそちらを直してください。お問い合わせページにも反映されます。 */
$shops = ymkrf_shops();

/* 市町から担当のお店を引く表（本文にも出しますし、お客様も探しやすくなります） */
$cities = array(
	'石川県' => array(
		'金沢市'       => array( 'nonoichi', 'tagami', 'higashikanazawa' ),
		'野々市市'     => array( 'nonoichi' ),
		'白山市'       => array( 'nonoichi', 'kawakita' ),
		'川北町'       => array( 'kawakita' ),
		'能美市'       => array( 'komathu', 'kawakita' ),
		'小松市'       => array( 'komathu' ),
		'加賀市'       => array( 'shinkaga' ),
		'七尾市'       => array( 'tazuruhama' ),
		'羽咋市'       => array( 'hakui' ),
		'志賀町'       => array( 'hakui', 'tazuruhama' ),
		'中能登町'     => array( 'hakui' ),
		'宝達志水町'   => array( 'hakui' ),
	),
	'福井県' => array(
		'あわら市'     => array( 'kanadu' ),
		'坂井市'       => array( 'kanadu' ),
		'福井市'       => array( 'kahahothu' ),
		'永平寺町'     => array( 'kahahothu' ),
		'越前町'       => array( 'asahi' ),
		'越前市'       => array( 'asahi' ),
		'鯖江市'       => array( 'asahi' ),
	),
);

/* 名前を引くための表 */
$byslug = array();
foreach ( $shops as $s ) $byslug[ $s['slug'] ] = $s['name'];

/* Googleマップは「貼り込み」ではなく、ただのリンクにします（費用がかからないため） */
function ymkrf_map_url( $addr ) {
	return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( 'ヤマキシ ' . $addr );
}

/* 検索エンジン用のデータ（お店ごと） */
$ld = array();
foreach ( $shops as $s ) {
	if ( ! empty( $s['soon'] ) ) continue;   /* まだ開いていないお店は出しません */
	$one = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'HomeAndConstructionBusiness',
		'name'        => 'リフォームヤマキシ ' . $s['name'],
		'parentOrganization' => array( '@type' => 'Organization', 'name' => '株式会社山岸' ),
		'telephone'   => $s['tel'],
		'address'     => array(
			'@type'           => 'PostalAddress',
			'addressCountry'  => 'JP',
			'addressRegion'   => $s['pref'],
			'streetAddress'   => str_replace( $s['pref'], '', $s['addr'] ),
		),
		'openingHours' => $s['ld'],
		'areaServed'   => array_map( function ( $c ) { return array( '@type' => 'City', 'name' => $c ); },
		                             array_map( function ( $a ) { return str_replace( array( '（一部）' ), '', $a ); }, $s['areas'] ) ),
		'url'          => home_url( '/shops/#' . $s['slug'] ),
		'image'        => get_stylesheet_directory_uri() . '/assets/img/shops/' . $s['slug'] . '.jpg',
	);
	$ld[] = $one;
}

get_header();
?>

<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li>店舗・対応エリア</li>
  </ol>
</nav>

<main id="main">

<div class="p-pagehead">
  <div class="l-wrap p-pagehead__inner">
    <img class="p-pagehead__chara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-truck.webp"
         width="640" height="359" alt="" loading="lazy" decoding="async">
    <span class="p-pagehead__en">SHOPS</span>
    <h1 class="p-pagehead__title">店舗・対応エリア</h1>
    <p class="p-pagehead__lead">
      石川県・福井県に<b>11店舗</b>。<br class="xs-only">
      お近くのお店から<b>およそ30分以内</b>でお伺いします。
    </p>
  </div>
</div>

<!-- =========== お住まいの市町から探す =========== -->
<section class="l-section">
  <div class="l-wrap l-wrap--narrow">
    <div class="c-head">
      <h2 class="c-head__title">お住まいの<span class="marker">市や町</span>から探す</h2>
      <p class="c-head__lead">お住まいの地域を押すと、担当するお店へ移動します。</p>
    </div>

    <?php foreach ( $cities as $pref => $list ) : ?>
      <div class="p-city" data-reveal>
        <p class="p-city__pref"><?php echo esc_html( $pref ); ?></p>
        <ul class="p-city__list">
          <?php foreach ( $list as $city => $slugs ) : ?>
            <li>
              <a href="#<?php echo esc_attr( $slugs[0] ); ?>">
                <span class="p-city__name"><?php echo esc_html( $city ); ?></span>
                <span class="p-city__shop"><?php
                  $ns = array();
                  foreach ( $slugs as $sg ) $ns[] = '<span class="p-shop__area">' . esc_html( $byslug[ $sg ] ) . '</span>';
                  echo implode( '<span class="p-shop__sep">／</span>', $ns );
                ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endforeach; ?>

    <p class="p-city__note" data-reveal>
      上に載っていない市町にお住まいの方も、まずはご相談ください。
      工事の内容・距離・規模によって、お伺いできるかどうかをご案内します。
    </p>
  </div>
</section>

<!-- =========== 店舗一覧 =========== -->
<?php
$pref_now = '';
foreach ( $shops as $s ) :
	if ( $s['pref'] !== $pref_now ) :
		if ( $pref_now !== '' ) echo "    </div>\n  </div>\n</section>\n";
		$pref_now = $s['pref'];
		$soft = ( $pref_now === '石川県' ) ? ' l-section--soft' : '';
?>
<section class="l-section<?php echo $soft; ?>" id="pref-<?php echo ( $pref_now === '石川県' ) ? 'ishikawa' : 'fukui'; ?>">
  <div class="l-wrap">
    <div class="c-head">
      <h2 class="c-head__title"><?php echo esc_html( $pref_now ); ?>の<span class="marker">お店</span></h2>
    </div>
    <div class="p-shoplist">
<?php endif; ?>

    <div class="p-shop<?php echo ! empty( $s['soon'] ) ? ' p-shop--soon' : ''; ?>" id="<?php echo esc_attr( $s['slug'] ); ?>" data-reveal>

      <div class="p-shop__head">
        <h3 class="p-shop__name"><?php echo esc_html( $s['name'] ); ?></h3>
        <?php if ( $s['sr'] ) : ?>
          <span class="p-shop__badge">ショールームあります</span>
        <?php endif; ?>
      </div>

      <div class="p-shop__body">

      <div class="p-shop__media">
      <?php if ( empty( $s['nophoto'] ) ) : ?>
        <figure class="p-shop__photo p-shop__photo--main">
          <picture>
            <source srcset="<?php echo $asset; ?>/assets/img/shops/<?php echo esc_attr( $s['slug'] ); ?>.webp" type="image/webp">
            <img src="<?php echo $asset; ?>/assets/img/shops/<?php echo esc_attr( $s['slug'] ); ?>.jpg"
                 width="680" height="400"<?php if ( ! empty( $s['pos'] ) ) : ?> style="object-position:<?php echo esc_attr( $s['pos'] ); ?>"<?php endif; ?>
                 alt="リフォームヤマキシ <?php echo esc_attr( $s['name'] ); ?>（<?php echo esc_attr( $s['addr'] ); ?>）の外観"
                 loading="lazy" decoding="async">
          </picture>
        </figure>
        <?php /* 案内図は、画像を assets/img/shops/<slug>-map.jpg|webp に置いて $shops に 'mapimg' => true を足してください */ ?>
        <?php if ( ! empty( $s['mapimg'] ) ) : ?>
          <a class="p-shop__photo p-shop__photo--map"
             href="<?php echo esc_url( ymkrf_map_url( $s['addr'] ) ); ?>" target="_blank" rel="noopener">
            <picture>
              <source srcset="<?php echo $asset; ?>/assets/img/shops/<?php echo esc_attr( $s['slug'] ); ?>-map.webp" type="image/webp">
              <img src="<?php echo $asset; ?>/assets/img/shops/<?php echo esc_attr( $s['slug'] ); ?>-map.jpg"
                   alt="リフォームヤマキシ <?php echo esc_attr( $s['name'] ); ?>（<?php echo esc_attr( $s['addr'] ); ?>）への案内図"
                   loading="lazy" decoding="async">
            </picture>
            <span>Googleマップで見る</span>
          </a>
        <?php endif; ?>
      <?php else : ?>
        <div class="p-shop__noimg">
          <img src="<?php echo $asset; ?>/assets/img/character/char-plan.webp" width="640" height="595"
               alt="" loading="lazy" decoding="async">
          <span>オープンに向けて準備中です</span>
        </div>
      <?php endif; ?>

      <?php
		/* お知らせは、外観写真の上に「吹き出し」でふわふわ浮かせます。
		   こうすると、お知らせのあるお店とないお店で、カードの高さがそろいます。 */
		$bubble = ! empty( $s['soon'] ) ? $s['soon'] : ( ! empty( $s['topic'] ) ? $s['topic'] : '' );
		?>
      <?php if ( $bubble !== '' ) : ?>
        <p class="p-shop__bubble<?php echo ! empty( $s['soon'] ) ? ' is-soon' : ''; ?>">
          <i><?php echo ! empty( $s['soon'] ) ? 'OPEN' : 'NEWS'; ?></i><span><?php echo esc_html( $bubble ); ?></span>
        </p>
      <?php endif; ?>
      </div>

      <div class="p-shop__info">

      <table class="p-shop__table">
        <tbody>
          <tr>
            <th>所在地</th>
            <td>
              <?php echo esc_html( $s['addr'] ); ?>
              <?php if ( ! empty( $s['soon'] ) ) : ?>
                <span class="p-shop__todo">※番地は確認中です</span>
              <?php elseif ( empty( $s['mapimg'] ) ) : ?>
                <?php /* 案内図がないお店だけ、文字のリンクを出します */ ?>
                <a class="p-shop__map" href="<?php echo esc_url( ymkrf_map_url( $s['addr'] ) ); ?>"
                   target="_blank" rel="noopener">地図を見る</a>
              <?php endif; ?>
            </td>
          </tr>
          <tr>
            <th>電話番号</th>
            <td>
              <?php if ( $s['tel'] === '' ) : ?>
                <span class="p-shop__todo">※準備中です。お急ぎの方は 0800-777-3331 へどうぞ</span>
              <?php else : ?>
                <a class="p-shop__tel" href="tel:<?php echo esc_attr( $s['tel'] ); ?>" data-cta="shop-tel"><?php echo esc_html( $s['tel'] ); ?></a>
              <?php endif; ?>
            </td>
          </tr>
          <tr>
            <th>営業時間</th>
            <td>
              <?php if ( $s['hours'] === '' ) : ?>
                <span class="p-shop__todo">※準備中です</span>
              <?php else : ?>
                <?php echo esc_html( $s['hours'] ); ?>
                <?php if ( $s['hnote'] !== '' ) : ?>
                  <span class="p-shop__hnote">※<?php echo esc_html( $s['hnote'] ); ?></span>
                <?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
          <?php if ( $s['closed'] !== '' ) : ?>
            <tr><th>定休日</th><td><?php echo esc_html( $s['closed'] ); ?></td></tr>
          <?php endif; ?>
          <tr>
            <th>エリア</th>
            <td><?php
              /* 市町の名前の途中で改行されないよう、1つずつ nowrap の枠に入れます。
                 「／」は枠の外に置いてあるので、そこでだけ折り返します。 */
              $parts = array();
              foreach ( $s['areas'] as $a ) $parts[] = '<span class="p-shop__area">' . esc_html( $a ) . '</span>';
              echo implode( '<span class="p-shop__sep">／</span>', $parts );
            ?></td>
          </tr>
        </tbody>
      </table>

      <?php if ( $s['srnote'] !== '' ) : ?>
        <p class="p-shop__srnote">※<?php echo esc_html( $s['srnote'] ); ?></p>
      <?php endif; ?>

      <?php if ( empty( $s['soon'] ) ) : ?>
        <?php
		/* お店ごとのリンク。アイコンは画像ではなくSVGで書いているので、
		   色や大きさをCSSだけで変えられます（余分なファイルも増えません）。 */
		$shop_links = array(
			array(
				'url'   => add_query_arg( 'shop', $s['slug'], home_url( '/flyer/' ) ),
				'label' => 'チラシ',
				'mod'   => ' is-flyer',
				'icon'  => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/>',
			),
			array(
				'url'   => add_query_arg( 'shop', $s['slug'], home_url( '/staff/' ) ),
				'label' => 'スタッフ',
				'mod'   => '',
				'icon'  => '<circle cx="12" cy="8" r="3.4"/><path d="M4.8 20c0-3.6 3.2-5.6 7.2-5.6s7.2 2 7.2 5.6"/>',
			),
			array(
				'url'   => home_url( '/works/' ),
				'label' => '施工事例',
				'mod'   => '',
				'icon'  => '<path d="M3.5 11 12 4l8.5 7"/><path d="M6 10.2V20h12v-9.8"/><path d="M10 20v-5h4v5"/>',
			),
			array(
				'url'   => home_url( '/voice/' ),
				'label' => 'お客様の声',
				'mod'   => '',
				'icon'  => '<path d="M20 14.5c0 1.4-1.1 2.5-2.5 2.5H9l-4 3v-3H6.5C5.1 17 4 15.9 4 14.5v-8C4 5.1 5.1 4 6.5 4h11C18.9 4 20 5.1 20 6.5z"/>',
			),
		);
		?>
        <ul class="p-shop__links">
          <?php foreach ( $shop_links as $lk ) : ?>
          <li>
            <a class="p-shop__link<?php echo $lk['mod']; ?>" href="<?php echo esc_url( $lk['url'] ); ?>">
              <svg class="p-shop__linkicon" viewBox="0 0 24 24" width="17" height="17"
                   fill="none" stroke="currentColor" stroke-width="1.8"
                   stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
                   focusable="false"><?php echo $lk['icon']; ?></svg>
              <span><?php echo esc_html( $lk['label'] ); ?></span>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      </div><!-- /.p-shop__info -->
      </div><!-- /.p-shop__body -->

    </div>

<?php endforeach; ?>
    </div>
  </div>
</section>

<!-- =========== エリアについて =========== -->
<section class="l-section l-section--soft">
  <div class="l-wrap l-wrap--narrow">
    <div class="c-head">
      <img class="p-lpchara c-chara--float" src="<?php echo $asset; ?>/assets/img/character/char-truck.webp"
           width="640" height="359" alt="" loading="lazy" decoding="async">
      <h2 class="c-head__title">お店が近いと、<span class="marker">こんなに違います</span></h2>
    </div>

    <div class="p-shopwhy" data-reveal>
      <div class="p-shopwhy__card">
        <p class="p-shopwhy__n"><b>30</b><span>分</span></p>
        <p class="p-shopwhy__t">いざというときに、すぐ来られます</p>
        <p>お伺いするエリアを、お店から30分でかけつけられる市町村に限っています。「お湯が出ない」というときも、素早く動けます。</p>
      </div>
      <div class="p-shopwhy__card">
        <p class="p-shopwhy__n"><b>11</b><span>店舗</span></p>
        <p class="p-shopwhy__t">工事のあとも、窓口はすぐ近く</p>
        <p>気になることがあったとき、お近くのお店がそのまま窓口です。「どこに言えばいいか分からない」ということがありません。</p>
      </div>
      <div class="p-shopwhy__card">
        <p class="p-shopwhy__n"><b>24</b><span>時間</span></p>
        <p class="p-shopwhy__t">年中無休でトラブルに対応</p>
        <p>深夜に給湯器が故障して「お湯が出ない！」というときも、当社の従業員が対応します。</p>
      </div>
    </div>

    <p class="p-city__note" data-reveal>
      担当のお店は、できるだけ早くお伺いできるよう、基本的にお近くの店舗になります。
      特別なご事情でほかのお店をご希望のときは、まずはご相談ください。
    </p>
  </div>
</section>

<!-- =========== 関連ページ =========== -->
<section class="l-section">
  <div class="l-wrap l-wrap--narrow">
    <h2 class="p-lprel__h2">あわせてご覧ください</h2>
    <ul class="p-lprel">
      <li><a href="<?php echo esc_url( home_url( '/staff/' ) ); ?>">スタッフ紹介</a><span>各店の営業担当の顔ぶれ</span></li>
      <li><a href="<?php echo esc_url( home_url( '/works/' ) ); ?>">施工事例</a><span>Before・Afterと、かかった費用</span></li>
      <li><a href="<?php echo esc_url( home_url( '/voice/' ) ); ?>">お客様の声</a><span>アンケート「仕事の通信簿」</span></li>
      <li><a href="<?php echo esc_url( home_url( '/products/' ) ); ?>">商品・価格</a><span>工事費込みのパック価格</span></li>
      <li><a href="<?php echo esc_url( home_url( '/flow/' ) ); ?>">リフォームの流れ</a><span>ご相談からアフターサポートまで</span></li>
      <li><a href="<?php echo esc_url( home_url( '/faq/' ) ); ?>">よくあるご質問</a><span>お見積り・追加費用・保証のこと</span></li>
    </ul>
  </div>
</section>

<!-- =========== CTA =========== -->
<section class="l-section l-section--soft">
  <div class="l-wrap l-wrap--narrow">
    <div class="p-lpcta">
      <img class="p-lpcta__chara" src="<?php echo $asset; ?>/assets/img/character/char-stand.webp" width="503" height="640"
           alt="" loading="lazy" decoding="async">
      <h2 class="p-lpcta__title">お近くの<span class="marker">お店</span>から、うかがいます</h2>
      <p class="p-lpcta__text">
        見積り・現地調査は無料です。しつこい営業は一切いたしません。<br>
        どのお店が担当か分からないときも、そのままご連絡ください。
      </p>
      <div class="p-lpcta__btns">
        <a class="c-btn c-btn--line c-btn--block" href="https://lin.ee/UJZuSTrz" rel="noopener" data-cta="shops-cta">
          <span class="c-btn__label">LINEで相談する<span class="c-btn__sub">写真を送るだけでもOK・24時間受付</span></span>
        </a>
        <a class="c-btn c-btn--block" href="<?php echo esc_url( home_url( '/inquiry/webrsv/' ) ); ?>" data-cta="shops-cta">
          <span class="c-btn__label">ショールーム来店予約<span class="c-btn__sub">初回特典500円ヤマキシお買物券<br>※展示のない店舗もあります</span></span>
        </a>
        <a class="c-btn c-btn--ghost c-btn--block" href="tel:0800-777-3331" data-cta="shops-cta">
          <span class="c-btn__label">0800-777-3331<span class="c-btn__sub">通話無料・受付 9:00〜17:00</span></span>
        </a>
      </div>
    </div>
  </div>
</section>

<?php foreach ( $ld as $one ) : ?>
<script type="application/ld+json"><?php echo wp_json_encode( $one, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
<?php endforeach; ?>

</main>

<?php get_footer();
