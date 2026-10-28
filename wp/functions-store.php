<?php
/**
 * functions-store.php ─ 店舗ごとのページ（/shops/higashikanazawa/ など）
 *
 * 置き場所： wp-content/themes/ymkrf/inc/functions-store.php（functions-shops.php のあとで読み込み）
 * 見た目：   single-ymkrf_store.php ／ assets/css/store.css
 *
 * （2026/10/27 ユーザー指示
 *   「店舗ページはどんなふうにつくるとSEOに強くお客様に喜ばれるか想定して作って」
 *   「編集できるよう、ワードプレスに入れてね」）
 *
 * ・お店1つ＝WordPress の記事1つ（記事の種類 ymkrf_store「店舗ページ」）。
 *   左メニュー「スタッフ ＞ 店舗ページ」から、お店ごとに直せます。
 * ・はじめて開いたときに、11店舗ぶんの記事を自動で作ります（中身は inc/functions-shops.php から）。
 *   作ったあとは、電話・営業時間などは **この記事が正** です（ファイルの値は最初の1回だけ使います）。
 * ・住所・担当の市町・お店の英字（URL）は、地図や市町ページとつながっているので、
 *   ここでは直せません（inc/functions-shops.php で直します）。
 * ・URL は /shops/<お店の英字>/ で固定です（記事のスラッグを変えても、自動でもどします）。
 *
 * ※記事の種類の名前は ymkrf_store です。商品の「取扱店舗」分類（ymkrf_shop）と名前がぶつからないようにしています。
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'YMKRF_STORE_SEED', 'ymkrf_store_seed_v1' );

/* ============================================================
   1. 記事の種類と URL
   ============================================================ */
add_action( 'init', function () {
	register_post_type( 'ymkrf_store', array(
		'labels' => array(
			'name'          => '店舗ページ',
			'singular_name' => '店舗ページ',
			'edit_item'     => '店舗ページを編集',
			'all_items'     => '店舗ページ',
			'menu_name'     => '店舗ページ',
			'search_items'  => '店舗ページを検索',
		),
		'public'              => true,
		'show_ui'             => true,
		/* 左メニューで「スタッフ」と同じ段に出します（2026/10/28 ユーザー「スタッフと同じ階層にして」） */
		'show_in_menu'        => true,
		'menu_position'       => 7,
		'menu_icon'           => 'dashicons-store',
		'show_in_rest'        => false,                                /* 入力欄はこの画面だけにします */
		'has_archive'         => false,                                /* 一覧は /shops/（ymkrf-shops.php）です */
		'rewrite'             => array( 'slug' => 'shops', 'with_front' => false ),
		'supports'            => array( 'title', 'revisions' ),
		'capability_type'     => 'page',
		'map_meta_cap'        => true,
		'exclude_from_search' => true,
	) );
}, 9 );

/* URL の作りかえを1回だけ（記事の種類をふやしたため） */
add_action( 'init', function () {
	if ( get_option( 'ymkrf_store_rewrite_v1' ) === '1' ) return;
	flush_rewrite_rules( false );
	update_option( 'ymkrf_store_rewrite_v1', '1', false );
}, 99 );

/** お店の英字 → 店舗ページの記事 */
function ymkrf_store_post( $slug ) {
	static $map = null;
	if ( $map === null ) {
		$map = array();
		foreach ( get_posts( array( 'post_type' => 'ymkrf_store', 'post_status' => array( 'publish', 'draft', 'private' ),
		                            'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC',
		                            'suppress_filters' => true ) ) as $p ) {
			$k = (string) get_post_meta( $p->ID, '_ymkrf_store_slug', true );
			if ( $k === '' ) $k = $p->post_name;
			$map[ $k ] = $p;
		}
	}
	return isset( $map[ $slug ] ) ? $map[ $slug ] : null;
}

/* ============================================================
   2. 入力できる項目
   ============================================================ */
function ymkrf_store_fields() {
	return array(
		/* 一覧ページ（/shops/）・お問い合わせ・市町ページにも出るもの */
		'feature'   => '特徴（ひとこと）',
		'tel'       => '電話番号',
		'hours'     => '営業時間',
		'hnote'     => '営業時間の但し書き',
		'closed'    => '定休日',
		'topic'     => 'お知らせ（ふきだし）',
		'open'      => 'オープンのお知らせ（ふきだし）',
		/* 店舗ページだけに出るもの */
		'catch'     => 'キャッチコピー',
		'intro'     => 'お店のご紹介',
		'districts' => '主な地区',
		'access'    => 'アクセス・目印',
		'parking'   => '駐車場',
		'gallery'   => '店内・ショールームの写真',
		'photo'     => '外観写真（差しかえ）',
		'faq'       => 'よくある質問',
	);
}

/** 店舗ページの記事に入っている値（お店の英字 => 項目） */
function ymkrf_store_values( $slug ) {
	$p = ymkrf_store_post( $slug );
	if ( ! $p ) return array();
	$out = array();
	foreach ( array_keys( ymkrf_store_fields() ) as $k ) {
		if ( ! metadata_exists( 'post', $p->ID, '_ymkrf_st_' . $k ) ) continue;
		$v = get_post_meta( $p->ID, '_ymkrf_st_' . $k, true );
		$out[ $k ] = $v;
	}
	$out['_post'] = $p;
	return $out;
}

/** ymkrf_shops() の上にかぶせます（functions-shops.php から呼びます） */
function ymkrf_store_overlay( $list ) {
	if ( ! post_type_exists( 'ymkrf_store' ) && ! did_action( 'init' ) ) return $list;
	foreach ( $list as $i => $s ) {
		if ( empty( $s['slug'] ) ) continue;
		$v = ymkrf_store_values( $s['slug'] );
		if ( ! $v ) continue;
		foreach ( $v as $k => $val ) {
			if ( $k === '_post' ) { $list[ $i ]['store_id'] = $v['_post']->ID; $list[ $i ]['store_pub'] = ( $v['_post']->post_status === 'publish' ); continue; }
			$list[ $i ][ $k ] = $val;
		}
	}
	return $list;
}

/** お店の英字から、1店ぶん（かぶせたあと） */
function ymkrf_store_get( $slug ) {
	foreach ( ymkrf_shops() as $s ) if ( isset( $s['slug'] ) && $s['slug'] === $slug ) return $s;
	return null;
}

/** 主な地区（「、」「・」「，」改行で区切ったもの）を配列に */
function ymkrf_store_districts( $s ) {
	$t = isset( $s['districts'] ) ? (string) $s['districts'] : '';
	$a = preg_split( '/[、，,・\/／\r\n]+/u', $t );
	return array_values( array_filter( array_map( 'trim', (array) $a ), 'strlen' ) );
}


/* ============================================================
   3. はじめの1回：11店舗ぶんの記事を作ります
   ============================================================ */

/** はじめの「主な地区」（金沢の3店は 2026/10/27 の案。各店の確認待ち） */
function ymkrf_store_seed_districts() {
	return array(
		'higashikanazawa' => '森本、東金沢駅まわり、小坂、鳴和、大樋、東山・卯辰山、金沢駅東口、浅野川沿い、粟崎、大河端',
		'tagami'          => '田上、杜の里、若松、鈴見、小立野、石引、東兼六、涌波、崎浦、湯涌、末町',
		'nonoichi'        => '金沢駅西口、西念、西金沢、新神田、示野、押野、御経塚、有松、寺町、平和町、額、四十万、金石、大野',
	);
}

/** はじめの「よくある質問」 */
function ymkrf_store_seed_faq( $s ) {
	$area = implode( '・', array_map( function ( $a ) { return preg_replace( '/（[^）]*）$/u', '', $a ); }, (array) $s['areas'] ) );
	return array(
		array( 'q' => 'お店に行かなくても相談できますか？',
		       'a' => 'はい。お電話・LINE・メールでもご相談いただけます。現地調査・お見積りは無料です。' ),
		array( 'q' => '見積りだけでもお願いできますか？',
		       'a' => 'はい、見積りだけでもお気軽にどうぞ。しつこい営業はいたしません。' ),
		array( 'q' => $area . '以外でもお願いできますか？',
		       'a' => 'はい、ご相談ください。お住まいからいちばん近いお店がうかがいます。どのお店にご連絡いただいても大丈夫です。' ),
	);
}

function ymkrf_store_seed() {
	if ( ! function_exists( 'ymkrf_shops' ) ) return;
	$dist = ymkrf_store_seed_districts();
	$ord  = 0;
	foreach ( ymkrf_shops() as $s ) {
		if ( empty( $s['slug'] ) ) continue;
		$ord++;
		if ( ymkrf_store_post( $s['slug'] ) ) continue;   /* もうあるお店は、さわりません */
		$id = wp_insert_post( array(
			'post_type'   => 'ymkrf_store',
			'post_status' => 'publish',
			'post_title'  => $s['name'],
			'post_name'   => $s['slug'],
			'menu_order'  => $ord,
		), true );
		if ( is_wp_error( $id ) || ! $id ) continue;
		update_post_meta( $id, '_ymkrf_store_slug', $s['slug'] );
		foreach ( array( 'feature', 'tel', 'hours', 'hnote', 'closed', 'topic', 'open' ) as $k ) {
			update_post_meta( $id, '_ymkrf_st_' . $k, isset( $s[ $k ] ) ? (string) $s[ $k ] : '' );
		}
		update_post_meta( $id, '_ymkrf_st_districts', isset( $dist[ $s['slug'] ] ) ? $dist[ $s['slug'] ] : '' );
		update_post_meta( $id, '_ymkrf_st_faq', ymkrf_store_seed_faq( $s ) );
	}
}
add_action( 'admin_init', function () {
	if ( ! current_user_can( 'edit_pages' ) ) return;
	if ( get_option( YMKRF_STORE_SEED ) === '1' ) return;
	ymkrf_store_seed();
	update_option( YMKRF_STORE_SEED, '1', false );
} );

/* 前の「店舗の内容」の画面は、店舗ページの一覧へ案内します（同じ内容を2か所で直さないように） */
add_action( 'admin_init', function () {
	if ( isset( $_GET['page'] ) && $_GET['page'] === 'ymkrf-shops-edit' ) {   /* phpcs:ignore */
		wp_safe_redirect( admin_url( 'edit.php?post_type=ymkrf_store' ) );
		exit;
	}
} );
add_action( 'admin_menu', function () {
	remove_submenu_page( 'edit.php?post_type=ymkrf_staff', 'ymkrf-shops-edit' );
}, 99 );

/* 左メニューで「店舗ページ」を「スタッフ」のすぐ下に置きます（2026/10/28 ユーザー「スタッフと同じ階層にして」） */
add_action( 'admin_menu', function () {
	global $menu;
	if ( ! is_array( $menu ) ) return;
	$staff = null; $store = null;
	foreach ( $menu as $k => $m ) {
		if ( isset( $m[2] ) && $m[2] === 'edit.php?post_type=ymkrf_staff' ) $staff = $k;
		if ( isset( $m[2] ) && $m[2] === 'edit.php?post_type=ymkrf_store' ) $store = $k;
	}
	if ( $staff === null || $store === null ) return;
	$item = $menu[ $store ];
	unset( $menu[ $store ] );
	$pos = (float) $staff + 0.0101;
	while ( isset( $menu[ (string) $pos ] ) ) $pos += 0.0001;
	$menu[ (string) $pos ] = $item;
}, 1000 );


/* ============================================================
   4. 編集画面（ページの並びと同じ順に、カードで入力します）
   ============================================================ */
add_action( 'add_meta_boxes_ymkrf_store', function ( $post ) {
	remove_meta_box( 'slugdiv', 'ymkrf_store', 'normal' );
	add_meta_box( 'ymkrf_store_box', 'このお店のページの中身', 'ymkrf_store_box', 'ymkrf_store', 'normal', 'high' );
} );

function ymkrf_store_box( $post ) {
	$slug = (string) get_post_meta( $post->ID, '_ymkrf_store_slug', true );
	if ( $slug === '' ) $slug = $post->post_name;
	$s    = ymkrf_store_get( $slug );
	$base = array();
	foreach ( ymkrf_shops_base() as $b ) if ( $b['slug'] === $slug ) $base = $b;
	$v = function ( $k ) use ( $s ) { return isset( $s[ $k ] ) ? $s[ $k ] : ''; };
	$n = function ( $k ) { return 'ymkrf_st[' . $k . ']'; };
	$faq = $v( 'faq' ); if ( ! is_array( $faq ) ) $faq = array();
	$gal = $v( 'gallery' ); if ( ! is_array( $gal ) ) $gal = array();
	$photo = (int) $v( 'photo' );
	$asset = get_stylesheet_directory_uri();
	wp_nonce_field( 'ymkrf_store_save', 'ymkrf_store_nonce' );
	?>
	<div class="ymkrf-st">
	  <p class="ymkrf-st__url">ページのURL：<a href="<?php echo esc_url( home_url( '/shops/' . $slug . '/' ) ); ?>" target="_blank"><?php echo esc_html( home_url( '/shops/' . $slug . '/' ) ); ?></a>
	    <span>（URLは変わりません）</span></p>

	  <div class="ymkrf-st__card">
	    <h3>① いちばん上（写真の上と、見出しの下）</h3>
	    <label class="ymkrf-st__f"><span>キャッチコピー</span>
	      <input type="text" name="<?php echo esc_attr( $n( 'catch' ) ); ?>" value="<?php echo esc_attr( $v( 'catch' ) ); ?>"
	             placeholder="例：金沢市の北・東エリアのリフォームは、東金沢店におまかせください">
	      <em>見出し（お店の名前）のすぐ下に出ます。空なら「担当エリア＋のリフォームはおまかせください」と自動で出ます。</em></label>
	    <div class="ymkrf-st__row">
	      <label class="ymkrf-st__f"><span>お知らせ（ふきだし）</span>
	        <input type="text" name="<?php echo esc_attr( $n( 'topic' ) ); ?>" value="<?php echo esc_attr( $v( 'topic' ) ); ?>" placeholder="例：リフォームコーナーがリニューアル！">
	        <em>外観写真の上にふきだしで出ます（28文字くらいまで）。店舗一覧にも出ます。</em></label>
	      <label class="ymkrf-st__f"><span>オープンのお知らせ（ふきだし）</span>
	        <input type="text" name="<?php echo esc_attr( $n( 'open' ) ); ?>" value="<?php echo esc_attr( $v( 'open' ) ); ?>" placeholder="例：2026年11月7日（土）オープン！">
	        <em>「OPEN」のふきだしで出ます。オープンしたら空にしてください。</em></label>
	    </div>
	    <div class="ymkrf-st__photo">
	      <span class="ymkrf-st__lbl">外観写真</span>
	      <span class="ymkrf-st__pv" data-fallback="<?php echo esc_url( $asset . '/assets/img/shops/' . $slug . '.jpg' ); ?>"><img src="<?php
	        echo esc_url( $photo ? wp_get_attachment_image_url( $photo, 'medium' ) : $asset . '/assets/img/shops/' . $slug . '.jpg' ); ?>" alt=""></span>
	      <input type="hidden" name="<?php echo esc_attr( $n( 'photo' ) ); ?>" value="<?php echo $photo ? (int) $photo : ''; ?>">
	      <button type="button" class="button ymkrf-st__pick">写真を差しかえる</button>
	      <button type="button" class="button-link ymkrf-st__unpick"<?php if ( ! $photo ) echo ' style="display:none"'; ?>>はじめの写真にもどす</button>
	    </div>
	  </div>

	  <div class="ymkrf-st__card">
	    <h3>② お店のご紹介</h3>
	    <label class="ymkrf-st__f"><span>特徴（ひとこと）</span>
	      <textarea rows="2" name="<?php echo esc_attr( $n( 'feature' ) ); ?>"><?php echo esc_textarea( $v( 'feature' ) ); ?></textarea>
	      <em>店舗一覧・市町のページ・コラムの執筆者欄にも出る短い紹介です。</em></label>
	    <label class="ymkrf-st__f"><span>お店のご紹介（長めの文）</span>
	      <textarea rows="6" name="<?php echo esc_attr( $n( 'intro' ) ); ?>" placeholder="例：東金沢店は、2026年に金沢市大樋町にオープンしたお店です。ショールームでは…"><?php echo esc_textarea( $v( 'intro' ) ); ?></textarea>
	      <em>このお店のページだけに出ます。<b>地名・お店の近くの目印・得意な工事</b>を入れると、検索に強くなり、お客様も安心します。空なら「特徴」を出します。改行はそのまま出ます。</em></label>
	  </div>

	  <div class="ymkrf-st__card">
	    <h3>③ 店舗情報</h3>
	    <div class="ymkrf-st__row">
	      <label class="ymkrf-st__f"><span>電話番号</span>
	        <input type="text" name="<?php echo esc_attr( $n( 'tel' ) ); ?>" value="<?php echo esc_attr( $v( 'tel' ) ); ?>" placeholder="例：076-000-0000">
	        <em>空なら フリーコール 0800-777-3331 をご案内します。</em></label>
	      <label class="ymkrf-st__f"><span>定休日</span>
	        <input type="text" name="<?php echo esc_attr( $n( 'closed' ) ); ?>" value="<?php echo esc_attr( $v( 'closed' ) ); ?>" placeholder="例：年中無休"></label>
	    </div>
	    <div class="ymkrf-st__row">
	      <label class="ymkrf-st__f"><span>営業時間</span>
	        <input type="text" name="<?php echo esc_attr( $n( 'hours' ) ); ?>" value="<?php echo esc_attr( $v( 'hours' ) ); ?>" placeholder="例：10:00〜18:00"></label>
	      <label class="ymkrf-st__f"><span>営業時間の但し書き</span>
	        <input type="text" name="<?php echo esc_attr( $n( 'hnote' ) ); ?>" value="<?php echo esc_attr( $v( 'hnote' ) ); ?>" placeholder="例：金曜は17:00まで"></label>
	    </div>
	    <div class="ymkrf-st__row">
	      <label class="ymkrf-st__f"><span>駐車場</span>
	        <input type="text" name="<?php echo esc_attr( $n( 'parking' ) ); ?>" value="<?php echo esc_attr( $v( 'parking' ) ); ?>" placeholder="例：店舗前に10台（無料）"></label>
	      <label class="ymkrf-st__f"><span>アクセス・目印</span>
	        <input type="text" name="<?php echo esc_attr( $n( 'access' ) ); ?>" value="<?php echo esc_attr( $v( 'access' ) ); ?>" placeholder="例：東金沢駅から車で5分。〇〇の向かいです"></label>
	    </div>
	    <p class="ymkrf-st__fixed">住所：<b><?php echo esc_html( isset( $s['addr'] ) ? $s['addr'] : '' ); ?></b>
	      ／ ショールーム：<b><?php echo ! empty( $s['sr'] ) ? 'あり' : 'なし'; ?></b>
	      <em>住所・ショールームの有無は、地図や検索エンジン用のデータとつながっているので、ここでは直せません（直すときはお知らせください）。</em></p>
	  </div>

	  <div class="ymkrf-st__card">
	    <h3>④ 担当エリア</h3>
	    <p class="ymkrf-st__fixed">担当の市町：<b><?php echo esc_html( implode( '／', (array) ( isset( $s['areas'] ) ? $s['areas'] : array() ) ) ); ?></b>
	      <em>市町のページ（/area/…）や件数とつながっているので、ここでは直せません。</em></p>
	    <label class="ymkrf-st__f"><span>主な地区</span>
	      <textarea rows="3" name="<?php echo esc_attr( $n( 'districts' ) ); ?>" placeholder="例：森本、東山、金沢駅東口"><?php echo esc_textarea( $v( 'districts' ) ); ?></textarea>
	      <em>「、」で区切って入れます。<b>「森本 リフォーム」のような地区名の検索</b>に効きます。お店がふだん行っている地区を、代表的なものから入れてください。</em></label>
	  </div>

	  <div class="ymkrf-st__card">
	    <h3>⑤ 店内・ショールームの写真</h3>
	    <ul class="ymkrf-st__gal">
	      <?php foreach ( $gal as $gid ) : $gid = (int) $gid; if ( ! $gid ) continue; ?>
	        <li data-id="<?php echo $gid; ?>"><img src="<?php echo esc_url( wp_get_attachment_image_url( $gid, 'thumbnail' ) ); ?>" alt=""><button type="button" title="この写真を外す">×</button></li>
	      <?php endforeach; ?>
	    </ul>
	    <input type="hidden" name="<?php echo esc_attr( $n( 'gallery' ) ); ?>" value="<?php echo esc_attr( implode( ',', array_map( 'intval', $gal ) ) ); ?>">
	    <button type="button" class="button ymkrf-st__galadd">＋ 写真をえらぶ（何枚でも）</button>
	    <em class="ymkrf-st__em">店内・ショールーム・スタッフが働くようすなど。4枚〜8枚くらいがおすすめです。ドラッグで並べかえできます。写真の説明（ALT）は「リフォームヤマキシ ○○店 店内のようす」と自動で付きます。</em>
	  </div>

	  <div class="ymkrf-st__card">
	    <h3>⑥ よくある質問</h3>
	    <div class="ymkrf-st__faqs">
	      <?php $faq[] = array( 'q' => '', 'a' => '' );
	      foreach ( $faq as $i => $f ) : ?>
	        <div class="ymkrf-st__faq">
	          <input type="text" name="ymkrf_st[faq][<?php echo (int) $i; ?>][q]" value="<?php echo esc_attr( isset( $f['q'] ) ? $f['q'] : '' ); ?>" placeholder="質問（例：駐車場はありますか？）">
	          <textarea rows="2" name="ymkrf_st[faq][<?php echo (int) $i; ?>][a]" placeholder="答え"><?php echo esc_textarea( isset( $f['a'] ) ? $f['a'] : '' ); ?></textarea>
	          <button type="button" class="button-link ymkrf-st__faqx">この質問を消す</button>
	        </div>
	      <?php endforeach; ?>
	    </div>
	    <button type="button" class="button ymkrf-st__faqadd">＋ 質問を1つ足す</button>
	    <em class="ymkrf-st__em">質問と答えの両方が入っているものだけページに出ます。Googleの検索結果に質問が出ることもあります。<b>「追加料金なし」のような言い切りは書かないでください。</b></em>
	  </div>

	  <?php
	  /* ⑦ このお店のスタッフ（兼任もここでえらべます。2026/10/28 ユーザー「スタッフも選べるように」） */
	  if ( function_exists( 'ymkrf_staff_get_shops' ) ) :
	    $all_st = get_posts( array( 'post_type' => 'ymkrf_staff', 'post_status' => array( 'publish', 'draft', 'private' ),
	                                'posts_per_page' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) );
	    $snames = function_exists( 'ymkrf_staff_shops' ) ? ymkrf_staff_shops() : array();
	    $groups = array();
	    foreach ( $all_st as $st ) {
	      $mine = ymkrf_staff_get_shops( $st->ID );
	      $main = $mine ? $mine[0] : '';
	      $groups[ $main ][] = array( $st, $mine );
	    }
	    $gorder = array_keys( $snames ); $gorder[] = '';
	  ?>
	  <div class="ymkrf-st__card">
	    <h3>⑦ このお店のスタッフ</h3>
	    <p class="ymkrf-st__em" style="margin:0 0 10px">チェックを入れた人が、このお店のページとスタッフ紹介に出ます。<b>兼任の人もここでえらべます</b>（例：金沢野々市店の人を、金沢田上店にもチェック）。
	      スタッフの登録画面の「所属店舗」と同じものです（どちらで直しても同じになります）。</p>
	    <input type="hidden" name="ymkrf_st_staff_sent" value="1">
	    <div class="ymkrf-st__staff">
	      <?php foreach ( $gorder as $gk ) : if ( empty( $groups[ $gk ] ) ) continue; ?>
	        <div class="ymkrf-st__sg">
	          <b><?php echo esc_html( $gk === '' ? '所属なし' : ( isset( $snames[ $gk ] ) ? $snames[ $gk ] : $gk ) ); ?></b>
	          <?php foreach ( $groups[ $gk ] as $row ) : list( $st, $mine ) = $row; $on = in_array( $slug, $mine, true ); ?>
	            <label class="<?php echo $on ? 'is-on' : ''; ?>">
	              <input type="hidden" name="ymkrf_st_staff_all[]" value="<?php echo (int) $st->ID; ?>">
	              <input type="checkbox" name="ymkrf_st_staff[]" value="<?php echo (int) $st->ID; ?>"<?php checked( $on ); ?>>
	              <?php echo esc_html( get_the_title( $st ) ); ?><?php if ( $st->post_status !== 'publish' ) echo '<small>（下書き）</small>'; ?>
	            </label>
	          <?php endforeach; ?>
	        </div>
	      <?php endforeach; ?>
	    </div>
	  </div>
	  <?php endif; ?>

	  <p class="ymkrf-st__auto">施工事例・お客様の声・お知らせ・チラシ・近くのお店は、<b>自動で入ります</b>（施工事例は「対応店舗」がこのお店のもの）。</p>
	</div>
	<style>
	  #ymkrf_store_box .inside{margin:0;padding:0 12px 12px}
	  .ymkrf-st__url{margin:12px 0;font-size:13px}.ymkrf-st__url span{color:#787878}
	  .ymkrf-st__card{background:#fff;border:2px solid #e6e1de;border-radius:14px;padding:14px 18px 16px;margin:0 0 14px}
	  .ymkrf-st__card h3{margin:0 0 12px;padding:0 0 6px;border-bottom:3px solid #ffd9cc;color:#fe3301;font-size:15px}
	  .ymkrf-st__f{display:block;margin:0 0 12px}
	  .ymkrf-st__f>span,.ymkrf-st__lbl{display:block;font-weight:700;font-size:13px;margin-bottom:4px}
	  .ymkrf-st__f input,.ymkrf-st__f textarea{width:100%}
	  .ymkrf-st__f em,.ymkrf-st__em,.ymkrf-st__fixed em{display:block;font-style:normal;font-size:11.5px;color:#787878;margin-top:4px;line-height:1.6}
	  .ymkrf-st__row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
	  @media(max-width:900px){.ymkrf-st__row{grid-template-columns:1fr}}
	  .ymkrf-st__photo{display:flex;align-items:center;flex-wrap:wrap;gap:10px}
	  .ymkrf-st__pv{width:180px;aspect-ratio:17/10;border-radius:8px;overflow:hidden;background:#f0f0f1;display:block}
	  .ymkrf-st__pv img{width:100%;height:100%;object-fit:cover;display:block}
	  .ymkrf-st__fixed{background:#f6f7f7;border-radius:8px;padding:8px 12px;font-size:13px;margin:4px 0 12px}
	  .ymkrf-st__gal{display:flex;flex-wrap:wrap;gap:8px;list-style:none;margin:0 0 10px;padding:0}
	  .ymkrf-st__gal li{position:relative;width:110px;height:110px;margin:0;border-radius:8px;overflow:hidden;cursor:grab;background:#f0f0f1}
	  .ymkrf-st__gal img{width:100%;height:100%;object-fit:cover;display:block}
	  .ymkrf-st__gal button{position:absolute;top:4px;right:4px;width:24px;height:24px;border:0;border-radius:50%;background:rgba(0,0,0,.55);color:#fff;cursor:pointer;line-height:1}
	  .ymkrf-st__faq{display:grid;grid-template-columns:1fr;gap:6px;padding:10px 12px;border:1px dashed #dcdcde;border-radius:10px;margin:0 0 8px}
	  .ymkrf-st__faq input{font-weight:700}
	  .ymkrf-st__faqx{justify-self:end;color:#b32d2e!important;font-size:12px}
	  .ymkrf-st__staff{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px}
	  .ymkrf-st__sg{background:#f6f7f7;border-radius:10px;padding:10px 12px}
	  .ymkrf-st__sg>b{display:block;font-size:12.5px;color:#50575e;margin-bottom:4px}
	  .ymkrf-st__sg label{display:flex;align-items:center;gap:6px;padding:3px 0;font-size:13px}
	  .ymkrf-st__sg label.is-on{font-weight:700;color:#c92800}
	  .ymkrf-st__sg small{color:#787878}
	  .ymkrf-st__auto{background:#fff8f5;border:1px solid #ffd9cc;border-radius:10px;padding:10px 14px;font-size:13px}
	</style>
	<script>
	jQuery(function ($) {
	  var $box = $('#ymkrf_store_box');
	  /* 外観写真 */
	  $box.on('click', '.ymkrf-st__pick', function () {
	    var f = wp.media({ title: '外観写真をえらぶ', multiple: false, library: { type: 'image' } });
	    f.on('select', function () {
	      var a = f.state().get('selection').first().toJSON();
	      $box.find('input[name="ymkrf_st[photo]"]').val(a.id);
	      $box.find('.ymkrf-st__pv img').attr('src', (a.sizes && a.sizes.medium) ? a.sizes.medium.url : a.url);
	      $box.find('.ymkrf-st__unpick').show();
	    });
	    f.open();
	  });
	  $box.on('click', '.ymkrf-st__unpick', function () {
	    $box.find('input[name="ymkrf_st[photo]"]').val('');
	    $box.find('.ymkrf-st__pv img').attr('src', $box.find('.ymkrf-st__pv').data('fallback'));
	    $(this).hide();
	  });
	  /* 店内の写真 */
	  var $gal = $box.find('.ymkrf-st__gal'), $gin = $box.find('input[name="ymkrf_st[gallery]"]');
	  function gsync() { $gin.val($gal.children('li').map(function () { return $(this).data('id'); }).get().join(',')); }
	  if ($.fn.sortable) $gal.sortable({ update: gsync });
	  $gal.on('click', 'button', function () { $(this).closest('li').remove(); gsync(); });
	  $box.on('click', '.ymkrf-st__galadd', function () {
	    var f = wp.media({ title: '店内・ショールームの写真をえらぶ', multiple: true, library: { type: 'image' } });
	    f.on('select', function () {
	      f.state().get('selection').each(function (m) {
	        var a = m.toJSON(), u = (a.sizes && a.sizes.thumbnail) ? a.sizes.thumbnail.url : a.url;
	        if ($gal.children('[data-id="' + a.id + '"]').length) return;
	        $gal.append($('<li>').attr('data-id', a.id).append($('<img alt="">').attr('src', u)).append('<button type="button" title="この写真を外す">×</button>'));
	      });
	      gsync();
	    });
	    f.open();
	  });
	  /* よくある質問 */
	  var nfaq = $box.find('.ymkrf-st__faq').length + 10;
	  $box.on('click', '.ymkrf-st__faqadd', function () {
	    var i = nfaq++;
	    $box.find('.ymkrf-st__faqs').append(
	      '<div class="ymkrf-st__faq"><input type="text" name="ymkrf_st[faq][' + i + '][q]" placeholder="質問">'
	      + '<textarea rows="2" name="ymkrf_st[faq][' + i + '][a]" placeholder="答え"></textarea>'
	      + '<button type="button" class="button-link ymkrf-st__faqx">この質問を消す</button></div>');
	  });
	  $box.on('click', '.ymkrf-st__faqx', function () { $(this).closest('.ymkrf-st__faq').remove(); });
	});
	</script>
	<?php
}

/* 編集画面で、写真をえらぶ部品と並べかえを使えるように */
add_action( 'admin_enqueue_scripts', function () {
	$sc = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $sc || $sc->post_type !== 'ymkrf_store' || $sc->base !== 'post' ) return;
	wp_enqueue_media();
	wp_enqueue_script( 'jquery-ui-sortable' );
} );

/* 保存 */
add_action( 'save_post_ymkrf_store', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;
	if ( ! isset( $_POST['ymkrf_store_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ymkrf_store_nonce'] ), 'ymkrf_store_save' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;
	$in = isset( $_POST['ymkrf_st'] ) ? (array) wp_unslash( $_POST['ymkrf_st'] ) : array();   /* phpcs:ignore */

	foreach ( array( 'catch', 'feature', 'tel', 'hours', 'hnote', 'closed', 'topic', 'open', 'parking', 'access' ) as $k ) {
		if ( ! isset( $in[ $k ] ) ) continue;
		update_post_meta( $post_id, '_ymkrf_st_' . $k, sanitize_text_field( (string) $in[ $k ] ) );
	}
	foreach ( array( 'intro', 'districts' ) as $k ) {
		if ( ! isset( $in[ $k ] ) ) continue;
		update_post_meta( $post_id, '_ymkrf_st_' . $k, sanitize_textarea_field( (string) $in[ $k ] ) );
	}
	if ( isset( $in['photo'] ) ) update_post_meta( $post_id, '_ymkrf_st_photo', (int) $in['photo'] ? (int) $in['photo'] : '' );
	if ( isset( $in['gallery'] ) ) {
		$ids = array_values( array_filter( array_map( 'intval', explode( ',', (string) $in['gallery'] ) ) ) );
		update_post_meta( $post_id, '_ymkrf_st_gallery', $ids );
	}
	$faq = array();
	foreach ( (array) ( isset( $in['faq'] ) ? $in['faq'] : array() ) as $f ) {
		$q = isset( $f['q'] ) ? sanitize_text_field( (string) $f['q'] ) : '';
		$a = isset( $f['a'] ) ? sanitize_textarea_field( (string) $f['a'] ) : '';
		if ( $q === '' && $a === '' ) continue;
		$faq[] = array( 'q' => $q, 'a' => $a );
	}
	update_post_meta( $post_id, '_ymkrf_st_faq', $faq );

	/* URL（スラッグ）はお店の英字に固定します */
	$slug = (string) get_post_meta( $post_id, '_ymkrf_store_slug', true );
	$p    = get_post( $post_id );
	if ( $slug !== '' && $p && $p->post_name !== $slug ) {
		remove_all_actions( 'save_post_ymkrf_store' );
		wp_update_post( array( 'ID' => $post_id, 'post_name' => $slug ) );
	}
	/* このお店のスタッフ（チェックを入れた人に、このお店を足す・外した人から、このお店を外す） */
	if ( isset( $_POST['ymkrf_st_staff_sent'] ) && $slug !== '' && function_exists( 'ymkrf_staff_set_shops' ) ) {
		$want = array_map( 'intval', isset( $_POST['ymkrf_st_staff'] ) ? (array) $_POST['ymkrf_st_staff'] : array() );
		foreach ( array_map( 'intval', isset( $_POST['ymkrf_st_staff_all'] ) ? (array) $_POST['ymkrf_st_staff_all'] : array() ) as $sid ) {
			if ( ! $sid || get_post_type( $sid ) !== 'ymkrf_staff' || ! current_user_can( 'edit_post', $sid ) ) continue;
			$mine = ymkrf_staff_get_shops( $sid );
			$has  = in_array( $slug, $mine, true );
			$on   = in_array( $sid, $want, true );
			if ( $on && ! $has ) { $mine[] = $slug; ymkrf_staff_set_shops( $sid, $mine ); }
			if ( ! $on && $has ) { ymkrf_staff_set_shops( $sid, array_values( array_diff( $mine, array( $slug ) ) ) ); }
		}
	}
	delete_transient( 'ymkrf_store_cnt_' . $slug );
}, 10 );

/* 一覧：お店の並び・URL・最後に直した日 */
add_filter( 'manage_ymkrf_store_posts_columns', function ( $c ) {
	return array( 'cb' => $c['cb'], 'title' => 'お店', 'ymkrf_st_url' => 'ページ', 'ymkrf_st_area' => '担当エリア', 'date' => '日付' );
} );
add_action( 'manage_ymkrf_store_posts_custom_column', function ( $col, $id ) {
	$slug = (string) get_post_meta( $id, '_ymkrf_store_slug', true );
	if ( $col === 'ymkrf_st_url' ) {
		echo '<a href="' . esc_url( home_url( '/shops/' . $slug . '/' ) ) . '" target="_blank">/shops/' . esc_html( $slug ) . '/</a>';
	}
	if ( $col === 'ymkrf_st_area' ) {
		$s = ymkrf_store_get( $slug );
		echo esc_html( $s ? implode( '／', (array) $s['areas'] ) : '' );
	}
}, 10, 2 );
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() && $q->is_main_query() && $q->get( 'post_type' ) === 'ymkrf_store' && ! $q->get( 'orderby' ) ) {
		$q->set( 'orderby', 'menu_order' ); $q->set( 'order', 'ASC' ); $q->set( 'posts_per_page', 50 );
	}
} );
/* 店舗ページは11店舗ぶんで固定。新しく足すボタンは出しません（お店を足すときは inc/functions-shops.php に足すと自動でできます） */
add_action( 'admin_head', function () {
	$sc = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $sc && $sc->post_type === 'ymkrf_store' ) echo '<style>.post-type-ymkrf_store .page-title-action,.post-type-ymkrf_store #edit-slug-buttons{display:none}</style>';
} );


/* ============================================================
   5. ページ（フロント）
   ============================================================ */

/** 店舗ページのURL（公開している店舗ページがあるときだけ。無ければ一覧の中の位置へ） */
function ymkrf_store_url( $slug ) {
	$p = function_exists( 'ymkrf_store_post' ) ? ymkrf_store_post( $slug ) : null;
	if ( $p && $p->post_status === 'publish' ) return home_url( '/shops/' . $slug . '/' );
	return home_url( '/shops/#' . $slug );
}

/** いまの店舗ページの「お店」 */
function ymkrf_store_current() {
	if ( ! is_singular( 'ymkrf_store' ) ) return null;
	$id   = get_queried_object_id();
	$slug = (string) get_post_meta( $id, '_ymkrf_store_slug', true );
	return $slug !== '' ? ymkrf_store_get( $slug ) : null;
}

/** 担当の市町（かっこ書きを外したもの） */
function ymkrf_store_cities( $s ) {
	$out = array();
	foreach ( (array) ( isset( $s['areas'] ) ? $s['areas'] : array() ) as $a ) {
		$c = trim( (string) preg_replace( '/（[^）]*）$/u', '', trim( $a ) ) );
		if ( $c !== '' ) $out[] = $c;
	}
	return array_values( array_unique( $out ) );
}

/** このお店の施工事例（対応店舗がこのお店のもの → 足りなければ担当の市町のもの） */
function ymkrf_store_works( $s, $n = 6 ) {
	$ids = get_posts( array(
		'post_type' => 'ymkrf_works', 'post_status' => 'publish', 'posts_per_page' => $n, 'fields' => 'ids',
		'meta_query' => array( array( 'key' => '_ymkrf_shop', 'value' => $s['slug'] ) ),
		'no_found_rows' => true,
	) );
	if ( count( $ids ) < $n ) {
		$cities = ymkrf_store_cities( $s );
		if ( $cities ) {
			$more = get_posts( array(
				'post_type' => 'ymkrf_works', 'post_status' => 'publish', 'posts_per_page' => $n - count( $ids ), 'fields' => 'ids',
				'post__not_in' => $ids ? $ids : array( 0 ), 'no_found_rows' => true,
				'tax_query' => array( array( 'taxonomy' => 'ymkrf_works_area', 'field' => 'name', 'terms' => $cities ) ),
			) );
			$ids = array_merge( $ids, $more );
		}
	}
	return $ids;
}

/** 件数（1日おぼえます） */
function ymkrf_store_counts( $s ) {
	$key = 'ymkrf_store_cnt_' . $s['slug'];
	$c   = get_transient( $key );
	if ( is_array( $c ) ) return $c;
	$w = 0; $v = 0;
	foreach ( ymkrf_store_cities( $s ) as $city ) {
		if ( ! function_exists( 'ymkrf_area_counts' ) ) break;
		$cc = ymkrf_area_counts( $city );
		$w += (int) $cc['works']; $v += (int) $cc['voices'];
	}
	$st = function_exists( 'ymkrf_staff_list' ) ? count( ymkrf_staff_list( $s['slug'] ) ) : 0;
	$c  = array( 'works' => $w, 'voices' => $v, 'staff' => $st );
	set_transient( $key, $c, DAY_IN_SECONDS );
	return $c;
}

/** 外観写真のURL */
function ymkrf_store_photo( $s, $size = 'large' ) {
	if ( ! empty( $s['photo'] ) ) {
		$u = wp_get_attachment_image_url( (int) $s['photo'], $size );
		if ( $u ) return $u;
	}
	return get_stylesheet_directory_uri() . '/assets/img/shops/' . $s['slug'] . '.jpg';
}

/* CSS */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_singular( 'ymkrf_store' ) ) return;
	$dir = get_stylesheet_directory_uri();
	$ver = defined( 'YMKRF_VER' ) ? YMKRF_VER : '1';
	wp_enqueue_style( 'ymkrf-staff', $dir . '/assets/css/staff.css', array( 'ymkrf-page' ), $ver );
	wp_enqueue_style( 'ymkrf-store', $dir . '/assets/css/store.css', array( 'ymkrf-page', 'ymkrf-staff' ), $ver );
}, 30 );

/* 題名・説明文 */
add_filter( 'document_title_parts', function ( $parts ) {
	$s = ymkrf_store_current();
	if ( ! $s ) return $parts;
	$parts['title'] = ymkrf_store_title( $s );
	return $parts;
}, 30 );

/** 説明文（検索結果に出る文） */
function ymkrf_store_desc( $s ) {
	$cities = ymkrf_store_cities( $s );
	$dist   = ymkrf_store_districts( $s );
	$t = 'リフォームヤマキシ ' . $s['name'] . '（' . preg_replace( '/^(石川県|福井県)/u', '', $s['addr'] ) . '）。'
	   . implode( '・', $cities ) . ( $dist ? '（' . implode( '・', array_slice( $dist, 0, 4 ) ) . 'など）' : '' )
	   . 'のキッチン・お風呂・トイレ・給湯器のリフォームを承ります。'
	   . ( ! empty( $s['sr'] ) ? 'ショールームで実物をご覧いただけます。' : '' )
	   . ( ! empty( $s['hours'] ) ? '営業時間 ' . $s['hours'] . '。' : '' )
	   . '見積り・現地調査は無料です。';
	return mb_strimwidth( $t, 0, 240, '…', 'UTF-8' );
}
/** 題名（検索結果に出る見出し） */
function ymkrf_store_title( $s ) {
	$cities = ymkrf_store_cities( $s );
	return $s['name'] . '｜' . implode( '・', array_slice( $cities, 0, 3 ) ) . 'のリフォーム' . ( ! empty( $s['sr'] ) ? '・ショールーム' : '' );
}

/* 題名・説明文・画像は、SEO SIMPLE PACK（左メニュー「SEO PACK」）が出しているので、そちらに渡します。
   （このプラグインはふつうの題名の決めかたを上書きするため。2026/10/27） */
add_filter( 'ssp_output_title', function ( $t ) {
	$s = ymkrf_store_current();
	return $s ? ymkrf_store_title( $s ) . ' | リフォームヤマキシ' : $t;
}, 20 );
add_filter( 'ssp_output_og_title', function ( $t ) {
	$s = ymkrf_store_current();
	return $s ? ymkrf_store_title( $s ) . ' | リフォームヤマキシ' : $t;
}, 20 );
add_filter( 'ssp_output_description', function ( $d ) {
	$s = ymkrf_store_current();
	return $s ? ymkrf_store_desc( $s ) : $d;
}, 20 );
add_filter( 'ssp_output_og_description', function ( $d ) {
	$s = ymkrf_store_current();
	return $s ? ymkrf_store_desc( $s ) : $d;
}, 20 );
add_filter( 'ssp_output_og_image', function ( $u ) {
	$s = ymkrf_store_current();
	return $s ? ymkrf_store_photo( $s ) : $u;
}, 20 );

/* 検索エンジン用のデータ（お店・よくある質問） */
add_action( 'wp_footer', function () {
	$s = ymkrf_store_current();
	if ( ! $s ) return;
	$url = home_url( '/shops/' . $s['slug'] . '/' );
	$area = array();
	foreach ( ymkrf_store_cities( $s ) as $c ) $area[] = array( '@type' => 'City', 'name' => $c );
	$c0 = ymkrf_store_cities( $s );
	$c0 = $c0 ? $c0[0] . ' ' : '';
	foreach ( ymkrf_store_districts( $s ) as $d ) $area[] = array( '@type' => 'Place', 'name' => $c0 . $d );
	$one = array(
		'@context' => 'https://schema.org',
		'@type'    => 'HomeAndConstructionBusiness',
		'@id'      => $url . '#store',
		'name'     => 'リフォームヤマキシ ' . $s['name'],
		'url'      => $url,
		'image'    => ymkrf_store_photo( $s ),
		'parentOrganization' => array( '@type' => 'Organization', 'name' => '株式会社山岸' ),
		'address'  => array( '@type' => 'PostalAddress', 'addressCountry' => 'JP', 'addressRegion' => $s['pref'],
		                     'streetAddress' => str_replace( $s['pref'], '', $s['addr'] ) ),
		'areaServed' => $area,
		'hasMap'   => 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( 'ヤマキシ ' . $s['addr'] ),
	);
	if ( ! empty( $s['tel'] ) ) $one['telephone'] = $s['tel'];
	if ( ! empty( $s['ld'] ) ) $one['openingHours'] = $s['ld'];
	if ( ! empty( $s['intro'] ) || ! empty( $s['feature'] ) ) $one['description'] = trim( ! empty( $s['intro'] ) ? $s['intro'] : $s['feature'] );
	echo '<script type="application/ld+json">' . wp_json_encode( $one, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";

	$faq = array();
	foreach ( (array) ( isset( $s['faq'] ) && is_array( $s['faq'] ) ? $s['faq'] : array() ) as $f ) {
		if ( trim( (string) $f['q'] ) === '' || trim( (string) $f['a'] ) === '' ) continue;
		$faq[] = array( '@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $f['a'] ) );
	}
	if ( $faq ) {
		echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faq ),
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}
}, 5 );
