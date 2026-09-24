<?php
/**
 * functions-product-seo.php ─ 商品ページの検索対策（題名・説明文・書き出しの一文）
 * 置き場所： wp-content/themes/ymkrf/inc/functions-product-seo.php
 *
 * （2026/09/22 ユーザー指示
 *   「今後はとにかくSEOに強いページ作りを意識して提案お願い」
 *   「コンロの商品ページは型番が主役で検索されにくい」→ 承認いただいて着手）
 *
 * ■ 何を直すか
 *
 *   1. ブラウザのタブと検索結果に出る題名（<title>）
 *      これまで … 「N3WV6M ｜ リフォームヤマキシ」
 *      これから … 「ビルトインコンロ交換 ノーリツ N3WV6M｜工事費込10万9,800円｜リフォームヤマキシ」
 *
 *   2. 検索結果の説明文（meta description）
 *      商品ごとに違う文にします。金額・工期・エリアを入れます。
 *
 *   3. 商品ページの書き出しの一文
 *      「ノーリツのビルトインコンロ N3WV6M の交換なら、工事費込み109,800円（税込）。
 *        石川・福井のリフォームヤマキシがお取り付けまで承ります。」
 *      人が打つ言葉（コンロ 交換、地名）をページの中に入れるためのものです。
 *
 * ■ なぜ必要か
 *   型番で検索するのは、すでに機種が決まっている方だけです。
 *   「コンロ 交換 金沢」で探している方に届くよう、
 *   分類の言葉（ビルトインコンロ交換）と地名を入れます。
 */
if ( ! defined( 'ABSPATH' ) ) exit;


/* ============================================================
   1. 分類ごとの「検索される言葉」
   ============================================================ */

/** その商品が何の工事かを、人が打つ言葉で返します */
function ymkrf_seo_word( $slug, $post_id = 0 ) {

	$map = array(
		'kitchen'    => 'システムキッチン交換',
		'bathroom'   => 'ユニットバス・浴室リフォーム',
		'toilet'     => 'トイレ交換',
		'lavatory'   => '洗面化粧台交換',
		'boiler'     => '給湯器交換',
		'ecocute'    => 'エコキュート交換',
		'interior'   => 'クロス・床の張りかえ',
		'fence'      => 'フェンス取付',
		'outer-wall' => '外壁・屋根塗装',
		'window'     => '内窓・窓リフォーム',
		'exterior'   => 'カーポート・物置・サンルーム',
		'pack4'      => '水まわり4点セット（キッチン・お風呂・トイレ・洗面台）',
	);

	/* コンロ・IHは、ガスかIHかで言葉を変えます */
	if ( $slug === 'cooktop' ) {
		$kind = $post_id ? (string) get_post_meta( $post_id, '_ymkrf_ihtype', true ) : '';
		if ( strpos( $kind, 'IH' ) !== false ) return 'IHクッキングヒーター交換';
		return 'ビルトインコンロ交換';
	}

	return isset( $map[ $slug ] ) ? $map[ $slug ] : 'リフォーム';
}

/** 商品ページの材料をまとめて取ります */
function ymkrf_seo_bits( $post_id ) {

	$cats  = get_the_terms( $post_id, 'ymkrf_product_cat' );
	$slug  = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0]->slug : '';

	$mks   = get_the_terms( $post_id, 'ymkrf_maker' );
	$maker = ( $mks && ! is_wp_error( $mks ) ) ? $mks[0]->name : '';

	$name  = trim( (string) get_post_meta( $post_id, '_ymkrf_name', true ) );
	if ( $name === '' ) $name = get_the_title( $post_id );

	$total = (int) get_post_meta( $post_id, '_ymkrf_total', true );
	if ( ! $total ) {
		$total = (int) get_post_meta( $post_id, '_ymkrf_work', true )
		       + (int) get_post_meta( $post_id, '_ymkrf_item', true );
	}

	$days  = (string) get_post_meta( $post_id, '_ymkrf_daystext', true );
	if ( $days === '' ) {
		$d = (string) get_post_meta( $post_id, '_ymkrf_days', true );
		if ( $d !== '' ) $days = $d . '日';
	}

	$pts = array_filter( array(
		(string) get_post_meta( $post_id, '_ymkrf_pt1', true ),
		(string) get_post_meta( $post_id, '_ymkrf_pt2', true ),
		(string) get_post_meta( $post_id, '_ymkrf_pt3', true ),
	) );

	return array(
		'slug'  => $slug,
		'word'  => ymkrf_seo_word( $slug, $post_id ),
		'maker' => $maker,
		'name'  => $name,
		'model' => trim( (string) get_post_meta( $post_id, '_ymkrf_model', true ) ),
		'total' => $total,
		'days'  => $days,
		'pts'   => array_values( $pts ),
	);
}


/* ============================================================
   2. ブラウザのタブ・検索結果の題名
   ============================================================ */

add_filter( 'document_title_parts', function ( $parts ) {

	if ( ! is_singular( 'ymkrf_product' ) ) return $parts;
	if ( function_exists( 'ymkrf_is_pack4' ) && ymkrf_is_pack4() ) return $parts;

	$b = ymkrf_seo_bits( get_the_ID() );

	/* 「ビルトインコンロ交換 ノーリツ N3WV6M｜工事費込109,800円」 */
	$head = trim( $b['word'] . ' ' . $b['maker'] . ' ' . $b['name'] );
	if ( $b['model'] !== '' && stripos( $b['name'], $b['model'] ) === false ) {
		$head .= ' ' . $b['model'];
	}

	if ( $b['total'] ) $head .= '｜工事費込' . number_format( $b['total'] ) . '円';

	$parts['title'] = $head;
	return $parts;
}, 20 );

/* 分類ページ（一覧）の題名 */
add_filter( 'document_title_parts', function ( $parts ) {

	if ( ! is_tax( 'ymkrf_product_cat' ) ) return $parts;

	$t = get_queried_object();
	if ( ! $t || is_wp_error( $t ) ) return $parts;

	$word = ymkrf_seo_word( $t->slug );
	$parts['title'] = $word . 'の価格一覧｜工事費込み｜石川・福井';
	return $parts;
}, 20 );


/* ============================================================
   3. 検索結果の説明文
   ============================================================ */

add_action( 'wp_head', function () {

	$desc = '';

	if ( is_singular( 'ymkrf_product' ) && ! ( function_exists( 'ymkrf_is_pack4' ) && ymkrf_is_pack4() ) ) {

		$b = ymkrf_seo_bits( get_the_ID() );

		/* 検索結果に出る説明文は、いつもこの自動の文を使います。
		   手書きの「商品説明」はサイズ・質量などを書く欄になったので、
		   検索結果の文には向かないためです
		   （2026/09/24 ユーザー指示「SEO的には、自動で作ってくれている文の方が
		     必要でしょうから。それはそれでいる」）。 */
		$desc = trim( $b['maker'] . ' ' . $b['name'] );
		if ( $b['model'] !== '' ) $desc .= '（' . $b['model'] . '）';
		$desc .= 'の' . $b['word'] . 'なら、';

		if ( $b['total'] ) {
			$desc .= '工事費込み' . number_format( $b['total'] ) . '円（税込）。';
		} else {
			$desc .= 'リフォームヤマキシへ。';
		}

		if ( $b['pts'] )       $desc .= implode( '／', array_slice( $b['pts'], 0, 3 ) ) . '。';
		if ( $b['days'] !== '' ) $desc .= '工期の目安は' . $b['days'] . '。';

		$desc .= '取り外し・処分・取り付けまで込みの価格です。'
		       . '石川県・福井県で創業127年、リフォームヤマキシ。見積り・現地調査は無料です。';

	} elseif ( is_tax( 'ymkrf_product_cat' ) ) {

		$t = get_queried_object();
		if ( $t && ! is_wp_error( $t ) ) {
			$word = ymkrf_seo_word( $t->slug );
			$desc = $word . 'の価格を、工事費込みの税込でご案内しています。'
			      . '取り外し・処分・取り付けまで込みの分かりやすい価格です。'
			      . '石川県・福井県で創業127年、リフォームヤマキシ。'
			      . '見積り・現地調査は無料。ショールームで現物をご覧いただけます。';
		}
	}

	if ( $desc === '' ) return;

	echo '<meta name="description" content="'
	   . esc_attr( mb_strimwidth( $desc, 0, 240, '…', 'UTF-8' ) ) . '">' . "\n";
}, 3 );


/* ============================================================
   4. 商品ページの書き出しの一文
      テンプレートから ymkrf_seo_lead() で呼びます
   ============================================================ */

function ymkrf_seo_lead( $post_id = 0 ) {

	$post_id = $post_id ? $post_id : get_the_ID();

	/* 2026/09/24 ユーザー指示
	     「商品説明ですが、こちらで入力したい。自動で作られるものは、つくってほしい。
	       それはきっとSEO的に必要でしょう？分けて考えようかと」
	       「例えば、サイズや質量などの説明を入れたい」

	   前は、手で「商品説明」を書くと、この自動の一文が消えていました。
	   いまは 2つを分けています。
	     ・この自動の一文 … 商品名のすぐ下。いつも出ます（検索で打たれる言葉を入れるため）
	     ・手書きの商品説明 … 商品写真の下。サイズ・質量など、自由に書けます */

	$b = ymkrf_seo_bits( $post_id );

	$s = trim( ( $b['maker'] !== '' ? $b['maker'] . 'の' : '' ) . $b['word'] );
	$s = $b['name'] . '（' . $s . '）';
	if ( $b['model'] !== '' && stripos( $b['name'], $b['model'] ) === false ) {
		$s = $b['name'] . ' ' . $b['model'] . '（' . trim( ( $b['maker'] !== '' ? $b['maker'] . 'の' : '' ) . $b['word'] ) . '）';
	}

	$s .= 'は、';
	if ( $b['total'] ) $s .= '取り外し・処分・取り付けまで込みで' . number_format( $b['total'] ) . '円（税込）。';
	if ( $b['days'] !== '' ) $s .= '工期の目安は' . $b['days'] . 'です。';

	$s .= '石川県・福井県のリフォームヤマキシが、お見積りから工事まで承ります。';

	return $s;
}
