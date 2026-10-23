<?php
/**
 * functions-media-seoname.php ─ 商品写真のファイル名を「何の写真か分かる名前」にそろえます
 *
 * 置き場所： wp-content/themes/ymkrf/inc/functions-media-seoname.php
 *
 * （2026/10/23 ユーザー指示「9/23から本日いれたものもすべてその商品にちなんだ
 *   SEOに強いファイル名にして」「今後もそうして」）
 *
 * ■ ねらい
 *   9/23 から、商品の登録画面で入れた写真は「toto-purerest-qr-2.jpg」のように
 *   商品名＋番号になっていました。番号では何の写真か分からず、画像検索にも効きません。
 *   そこで、その写真が商品のどこで使われているかを調べて、名前を付けなおします。
 *
 *     メイン写真        … toto-purerest-qr.jpg
 *     カラー            … toto-purerest-qr-color-pastel-ivory.jpg
 *     オプション        … toto-purerest-qr-opt-tcf4714ak.jpg（品名に型番があれば型番）
 *     おすすめポイント  … toto-purerest-qr-point-（見出し）.jpg
 *     標準仕様          … toto-purerest-qr-spec-（品名）.jpg
 *     どこにも使っていない写真 … もとのファイル名か ALT があれば、それを使います
 *
 * ■ 使いかた
 *   メディア ＞ 商品写真の名前をそろえる
 *   まず「こう変わります」の一覧が出ます。見てから「この内容で名前を変える」を押します。
 *   ファイル（大きさちがいも全部）・メディアの情報・本文の中のURLを、いっしょに直します。
 *
 * ■ 公開前に変えるのが安全です（公開後に変えると、画像検索の評価がいったんリセットされます）
 */

if ( ! defined( 'ABSPATH' ) ) exit;


/* ============================================================
   1. 名前の部品
   ============================================================ */

/**
 * いま入っている写真の説明 → 英語の名前（2026/10/23 に、一覧を見ながら手で付けました）。
 * ローマ字（midiamugure など）より、英語（medium-gray）のほうが画像検索に効きます。
 * 空白は半角1つにそろえてから見ます。
 */
function ymkrf_seo_manual() {
	return array(
		'手早くおいしく、お手入れラクラク。' => 'quick-cooking-easy-clean',
		'温度・時間を自動設定、調理のタイミングもお知らせ。' => 'auto-temp-timer',
		'おいしさを支える光火力センサー＋（プラス）＆光火力センサー' => 'light-heat-sensor',
		'ハイパワーな強火' => 'high-power-flame',
		'水無し両面自動焼きだからおいしい！' => 'waterless-double-sided-grill',
		'かがまずにグリルまで操作' => 'easy-grill-operation',
		'PROGREだけの13段階の火力微調節機能' => '13-step-flame-control',
		'親水アクアコート' => 'hydrophilic-aqua-coat',
		'コンロで調理中の温度が見える！' => 'cooking-temp-display',
		'オートグリル' => 'auto-grill',
		'ピュアホワイト（BW1）' => 'pure-white-bw1',
		'オフホワイト（BN8）' => 'off-white-bn8',
		'ピンク（LR8）' => 'pink-lr8',
		'ブルーグレー（BB7）' => 'blue-gray-bb7',
		'ウォシュレット（SS1 TCF6624）' => 'washlet-ss1-tcf6624',
		'ウォシュレット（S2 TCF6544）' => 'washlet-s2-tcf6544',
		'肩つつみ湯' => 'shoulder-hot-water',
		'折戸ホワイト（W800mm）' => 'folding-door-white-w800',
		'コンフォートウエーブシャワー（メタル調）' => 'comfort-wave-shower-metal',
		'スライドバーなし シャワーハンガー2個' => 'shower-hanger-2',
		'FRP浴槽' => 'frp-bathtub',
		'ミディアムグレー' => 'medium-gray',
		'ミディアムベージュ' => 'medium-beige',
		'ミディアムホワイト' => 'medium-white',
		'パラレルホワイト' => 'parallel-white',
		'ツイルネイビー' => 'twill-navy',
		'ツイルボルドー' => 'twill-bordeaux',
		'ナチュラル' => 'natural',
		'ピュアホワイト' => 'pure-white',
		'サークルLED照明（1灯）' => 'circle-led-light',
		'シンクキャビネット' => 'sink-cabinet',
		'コンロキャビネット' => 'stove-cabinet',
		'調理キャビネット' => 'prep-cabinet',
		'収納' => 'storage',
		'足元収納' => 'toe-kick-storage',
		'タッチレス水栓' => 'touchless-faucet',
		'セラミックワークトップ' => 'ceramic-worktop',
		'クイックパレット' => 'quick-palette',
		'ハンドシャワーエコ水栓' => 'hand-shower-eco-faucet',
		'ホーロークリーン キッチンパネル' => 'enamel-kitchen-panel',
		'フラットスリムフード' => 'flat-slim-hood',
		'フラットスリムレンジフード' => 'flat-slim-range-hood',
		'混合水栓ハンドシャワー' => 'hand-shower-faucet',
		'ソルティホワイト' => 'salty-white',
		'シルフィーグレー' => 'silphy-gray',
		'シルフィーベージュ' => 'silphy-beige',
		'スキットシンク ステンレス' => 'skit-sink-stainless',
		'スキットシンク' => 'skit-sink',
		'らくエル ステンレスシンク' => 'rakuel-stainless-sink',
		'シロッコファンレンジフード' => 'sirocco-fan-range-hood',
		'吊戸棚耐震ラッチ' => 'wall-cabinet-quake-latch',
		'LED手元照明' => 'led-task-light',
		'シングルレバー水栓' => 'single-lever-faucet',
		'マグネフリーパネル' => 'magnet-free-panel',
		'ステンレス天板' => 'stainless-countertop',
		'ミドル吊戸棚' => 'middle-wall-cabinet',
		'サイレントレール' => 'silent-rail',
		'スリムですっきりデザイン' => 'slim-design',
		'お手入れしやすい内面形状' => 'easy-clean-interior',
		'ステンレス エンボス柄' => 'stainless-emboss',
		'ステンレスシンク（Gシンク76）' => 'stainless-sink-g76',
		'タカラスタンダード キッチン エーデル ※水栓どちらか山善まち 浄水器内蔵ハンドシャワー水栓 TJS-SP19E' => 'water-purifier-faucet-tjs-sp19e',
		'LIXIL キッチン シエラ スキットシンク ステンレス' => 'skit-sink-stainless',
	);
}

/** 文字を、ファイル名に使える英字にします（日本語はローマ字・決まった英語に） */
function ymkrf_seo_slugify( $text, $max = 40 ) {
	$text = trim( (string) preg_replace( '/[\s　]+/u', ' ', (string) $text ) );
	if ( $text === '' ) return '';
	$man = ymkrf_seo_manual();
	if ( isset( $man[ $text ] ) ) return $man[ $text ];
	/* 型番（TCF4714AK のような英数字のかたまり）があれば、それを優先します（短く・検索されやすい） */
	if ( preg_match_all( '/[A-Za-z]*[0-9][A-Za-z0-9\-]{2,}|[A-Z]{2,}[0-9][A-Za-z0-9]*/u', $text, $m ) ) {
		$codes = array_filter( array_map( 'trim', $m[0] ), function ( $c ) { return strlen( $c ) >= 4; } );
		if ( $codes ) $text = implode( '-', array_slice( array_unique( $codes ), 0, 2 ) );
	}
	$s = function_exists( 'ymkrf_media_ascii_name' ) ? ymkrf_media_ascii_name( $text . '.x' ) : $text . '.x';
	$s = preg_replace( '/\.x$/', '', $s );
	/* 英字にできなかったとき、ascii の関数は「photo-日時」を返すので、空あつかいにします */
	if ( preg_match( '/^photo-\d{8}-\d{6}$/', $s ) ) return '';
	/* 漢字が入っていて型番も無いときは、ローマ字がくずれるので使いません（短い決まりの名前にします） */
	if ( preg_match( '/[\x{4E00}-\x{9FFF}]/u', $text ) && ! preg_match( '/[0-9]/', $s ) ) return '';
	$s = strtolower( (string) preg_replace( '/[^A-Za-z0-9]+/', '-', $s ) );
	$s = trim( (string) preg_replace( '/-{2,}/', '-', $s ), '-' );
	if ( strlen( $s ) > $max ) $s = rtrim( substr( $s, 0, $max ), '-' );
	return $s;
}

/** 商品のどこでその写真を使っているか → array( 'tag' => 'opt', 'text' => '手すり' ) */
function ymkrf_seo_usage_map( $pid ) {
	$map = array();
	$th  = (int) get_post_thumbnail_id( $pid );
	if ( $th ) $map[ $th ] = array( 'tag' => 'main', 'text' => '' );

	$tags = array(
		'_ymkrf_images' => 'image', '_ymkrf_handles' => 'handle', '_ymkrf_specs' => 'spec',
		'_ymkrf_features' => 'point', '_ymkrf_notes' => 'card', '_ymkrf_options' => 'opt', '_ymkrf_ba' => 'ba',
	);
	$reps = function_exists( 'ymkrf_product_repeaters' ) ? ymkrf_product_repeaters() : array();
	foreach ( $reps as $key => $def ) {
		$tag = ! empty( $def['color'] ) ? 'color' : ( $tags[ $key ] ?? '' );
		if ( $tag === '' ) continue;
		$rows = get_post_meta( $pid, $key, true );
		if ( ! is_array( $rows ) ) continue;
		$carry = '';
		foreach ( $rows as $r ) {
			if ( ! is_array( $r ) ) continue;
			/* 名前の手がかり（使える順） */
			$text = '';
			foreach ( array( 'name', 'ttl', 'code', 'model', 'alt', 'gsub', 'gttl', 'text' ) as $f ) {
				if ( ! empty( $r[ $f ] ) && is_scalar( $r[ $f ] ) ) { $text = (string) $r[ $f ]; break; }
			}
			if ( $tag === 'point' ) { if ( ! empty( $r['gsub'] ) ) $carry = (string) $r['gsub']; if ( $text === '' ) $text = $carry; }
			/* 写真のID（1枚・何枚も・Before/After） */
			$ids = array();
			foreach ( array( 'img', 'img2', 'before', 'after' ) as $f ) if ( ! empty( $r[ $f ] ) ) $ids[ (int) $r[ $f ] ] = $f;
			if ( ! empty( $r['imgs'] ) ) {
				$list = is_array( $r['imgs'] ) ? $r['imgs'] : explode( ',', (string) $r['imgs'] );
				foreach ( $list as $one ) if ( (int) $one ) $ids[ (int) $one ] = 'imgs';
			}
			foreach ( $ids as $id => $f ) {
				if ( isset( $map[ $id ] ) ) continue;
				$t = $tag;
				if ( $tag === 'ba' ) $t = $f === 'after' ? 'after' : 'before';
				$map[ $id ] = array( 'tag' => $t, 'text' => $text );
			}
		}
	}
	$cat = (int) get_post_meta( $pid, '_ymkrf_cat_img', true );
	if ( $cat && ! isset( $map[ $cat ] ) ) $map[ $cat ] = array( 'tag' => 'catalog', 'text' => '' );
	return $map;
}


/* ============================================================
   2. 変える候補の一覧
   ============================================================ */
function ymkrf_seo_plan() {
	global $wpdb;
	$plan = array();
	$up   = wp_get_upload_dir();
	$prods = get_posts( array( 'post_type' => 'ymkrf_product', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ) );
	$taken = array();   /* この一覧の中で、同じ名前にならないように */

	foreach ( $prods as $pid ) {
		/* いまの写真をさがすときは前の決まり（$old）、新しい名前は今の決まり（$base）で作ります */
		$old  = function_exists( 'ymkrf_media_product_filebase' ) ? ymkrf_media_product_filebase( $pid, true ) : '';
		$base = function_exists( 'ymkrf_media_product_filebase' ) ? ymkrf_media_product_filebase( $pid ) : '';
		if ( $base === '' || $old === '' ) continue;
		$atts = $wpdb->get_results( $wpdb->prepare(
			"SELECT p.ID, p.post_title, m.meta_value AS rel FROM {$wpdb->posts} p
			   JOIN {$wpdb->postmeta} m ON ( m.post_id = p.ID AND m.meta_key = '_wp_attached_file' )
			  WHERE p.post_type = 'attachment' AND p.post_parent = %d AND p.post_status <> 'trash'", $pid ) );
		if ( ! $atts ) continue;
		$use = ymkrf_seo_usage_map( $pid );

		foreach ( $atts as $a ) {
			$rel  = (string) $a->rel;
			$file = wp_basename( $rel );
			/* 自動で付いた名前（base / base-2 / base-scaled…）だけが対象です */
			if ( ! preg_match( '/^(' . preg_quote( $old, '/' ) . '|' . preg_quote( $base, '/' ) . ')(-\d+)?(-scaled)?\.([a-z0-9]+)$/i', $file, $mm ) ) continue;
			$ext = strtolower( $mm[4] );
			$id  = (int) $a->ID;

			$u = $use[ $id ] ?? null;
			$why = '';
			if ( $u ) {
				$labels = array( 'main' => 'メイン写真', 'color' => 'カラー', 'handle' => '取っ手', 'spec' => '標準仕様',
					'point' => 'おすすめポイント', 'card' => '特徴カード', 'opt' => 'オプション', 'image' => '組み合わせ写真',
					'before' => 'Before', 'after' => 'After', 'catalog' => 'カタログ' );
				$why  = ( $labels[ $u['tag'] ] ?? $u['tag'] ) . ( $u['text'] !== '' ? '「' . $u['text'] . '」' : '' );
				if ( $u['tag'] === 'main' && preg_match( '/^' . preg_quote( $base, '/' ) . '(-scaled)?\.[a-z0-9]+$/i', $file ) ) continue;   /* メイン写真で、もう商品名だけの名前 */
				$desc = $u['tag'] === 'main' ? 'main' : trim( $u['tag'] . '-' . ymkrf_seo_slugify( $u['text'] ), '-' );
			} else {
				/* どこにも使っていない写真：もとのファイル名 → ALT の順に手がかりにします */
				$orig = (string) get_post_meta( $id, '_ymkrf_orig_name', true );
				$alt  = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
				$hint = $orig !== '' ? $orig : $alt;
				$why  = 'いまは使われていない写真' . ( $hint !== '' ? '（' . ( $orig !== '' ? 'もとの名前' : 'ALT' ) . '：' . $hint . '）' : '' );
				$desc = ymkrf_seo_slugify( $hint );
				if ( $desc !== '' && strpos( $desc, $base ) === 0 ) $desc = trim( substr( $desc, strlen( $base ) ), '-' );
				if ( $desc === '' ) { $plan[] = array( 'id' => $id, 'pid' => $pid, 'from' => $rel, 'to' => '', 'why' => $why . '　→ 名前の手がかりが無いので、そのままにします' ); continue; }
			}

			$body = $desc === '' ? $base : $base . '-' . $desc;
			$sub  = ( dirname( $rel ) === '.' ) ? '' : dirname( $rel ) . '/';
			/* 同じ名前があれば -2, -3 … */
			$try = $body; $n = 1;
			while ( isset( $taken[ $sub . $try ] ) || ( $sub . $try . '.' . $ext !== $rel && file_exists( $up['basedir'] . '/' . $sub . $try . '.' . $ext ) ) ) {
				$n++; $try = $body . '-' . $n;
			}
			$taken[ $sub . $try ] = true;
			$to = $sub . $try . '.' . $ext;
			if ( preg_replace( '/-scaled(\.[a-z0-9]+)$/i', '$1', $rel ) === $to ) continue;   /* もう同じ名前 */
			$plan[] = array( 'id' => $id, 'pid' => $pid, 'from' => $rel, 'to' => $to, 'why' => $why );
		}
	}
	return $plan;
}


/* ============================================================
   3. 名前を変える（ファイル・大きさちがい・元の写真・メディアの情報・本文のURL）
   ============================================================ */
function ymkrf_seo_rename( $id, $newrel, $meta_check = true ) {
	global $wpdb;
	$up  = wp_get_upload_dir();
	$rel = (string) get_post_meta( $id, '_wp_attached_file', true );
	if ( $rel === '' || $newrel === '' ) return false;
	/* 元のファイルが無いときは、何も変えません（2026/10/23）。
	   同じファイルを2つのメディアが指していて、先に片方の名前を変えたときなど。
	   そのときは、先に変えたほうと同じファイルを指すようにします */
	if ( ! file_exists( $up['basedir'] . '/' . $rel ) ) {
		return ymkrf_seo_follow_twin( $id, $rel );
	}
	/* ほかのメディアも同じファイルを指しているときは、変えません（片方の写真が消えるため） */
	if ( $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s AND post_id <> %d LIMIT 1", $rel, $id ) ) ) {
		return false;
	}
	$sub     = ( dirname( $rel ) === '.' ) ? '' : dirname( $rel ) . '/';
	$oldbody = preg_replace( '/(-scaled)?\.[a-z0-9]+$/i', '', wp_basename( $rel ) );
	$newbody = preg_replace( '/\.[a-z0-9]+$/i', '', wp_basename( $newrel ) );
	$swap = function ( $f ) use ( $oldbody, $newbody ) {
		return strpos( $f, $oldbody ) === 0 ? $newbody . substr( $f, strlen( $oldbody ) ) : $f;
	};
	$pairs = array();   /* 古い相対パス => 新しい相対パス */
	$meta  = wp_get_attachment_metadata( $id );
	$files = array( wp_basename( $rel ) );
	if ( is_array( $meta ) ) {
		foreach ( (array) ( $meta['sizes'] ?? array() ) as $s ) if ( ! empty( $s['file'] ) ) $files[] = $s['file'];
		if ( ! empty( $meta['original_image'] ) ) $files[] = $meta['original_image'];
	}
	/* 先に、新しい名前がぜんぶ空いているか確かめます（途中で止まらないように） */
	foreach ( array_unique( $files ) as $f ) {
		$t = $swap( $f );
		if ( $t === $f ) continue;
		if ( file_exists( $up['basedir'] . '/' . $sub . $t ) ) return false;
		$pairs[ $sub . $f ] = $sub . $t;
	}
	foreach ( $pairs as $from => $to ) {
		if ( file_exists( $up['basedir'] . '/' . $from ) ) @rename( $up['basedir'] . '/' . $from, $up['basedir'] . '/' . $to );
	}
	update_post_meta( $id, '_wp_attached_file', $pairs[ $rel ] ?? $rel );
	if ( is_array( $meta ) ) {
		if ( ! empty( $meta['file'] ) ) $meta['file'] = $pairs[ $meta['file'] ] ?? $meta['file'];
		foreach ( (array) ( $meta['sizes'] ?? array() ) as $k => $s ) {
			if ( ! empty( $s['file'] ) ) $meta['sizes'][ $k ]['file'] = $swap( $s['file'] );
		}
		if ( ! empty( $meta['original_image'] ) ) $meta['original_image'] = $swap( $meta['original_image'] );
		wp_update_attachment_metadata( $id, $meta );
	}
	/* URL の書きかえ（本文・メディアのguid・文字だけの欄）。
	   まず古い名前がどこかに書かれているかを1回だけ調べ、あるときだけ1ファイルずつ書きかえます（速くするため） */
	$like  = '%' . $wpdb->esc_like( '/' . $sub . $oldbody ) . '%';
	/* 古い名前が書いてある記事・欄を1回だけさがし、そこだけを書きかえます（速くするため） */
	$pids = array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE %s", $like ) ) );
	$mids = $meta_check ? array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare(
		"SELECT meta_id FROM {$wpdb->postmeta} WHERE meta_value LIKE %s AND meta_key NOT IN ( '_wp_attached_file', '_wp_attachment_metadata', '_ymkrf_renamed_from' )
		   AND meta_value NOT LIKE 'a:%%' AND meta_value NOT LIKE 's:%%'", $like ) ) ) : array();
	foreach ( $pairs as $from => $to ) {
		$ou = $up['baseurl'] . '/' . $from; $nu = $up['baseurl'] . '/' . $to;
		if ( $pids ) $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_content = REPLACE( post_content, %s, %s ) WHERE ID IN ( " . implode( ',', $pids ) . " )", $ou, $nu ) );
		if ( $mids ) $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE( meta_value, %s, %s ) WHERE meta_id IN ( " . implode( ',', $mids ) . " )", $ou, $nu ) );
	}
	if ( $pids ) foreach ( $pids as $cp ) clean_post_cache( $cp );
	$wpdb->update( $wpdb->posts, array( 'guid' => $up['baseurl'] . '/' . ( $pairs[ $rel ] ?? $rel ) ), array( 'ID' => $id ) );
	/* タイトルが自動の名前（toto-purerest-qr）のままなら、新しい名前にそろえます */
	$t = get_the_title( $id );
	if ( preg_match( '/^' . preg_quote( $oldbody, '/' ) . '$/i', (string) $t ) || preg_match( '/^[a-z0-9-]+$/', (string) $t ) ) {
		$wpdb->update( $wpdb->posts, array( 'post_title' => $newbody ), array( 'ID' => $id ) );
	}
	update_post_meta( $id, '_ymkrf_renamed_from', $rel );
	clean_post_cache( $id );
	if ( function_exists( 'ymkrf_media_forget' ) ) ymkrf_media_forget( $id );
	return true;
}


/** 下見・実行の一覧から、ほかのメディアと同じファイルを指している写真（ふたご）を外します。
 *  ふたごの片方だけ名前を変えると、もう片方の写真が消えてしまうためです（2026/10/23 ユーザー「施工事例1件？おかしくない？」） */
function ymkrf_seo_plan_clean( $plan ) {
	global $wpdb;
	$twins = array_flip( array_map( 'intval', (array) $wpdb->get_col(
		"SELECT DISTINCT a.post_id FROM {$wpdb->postmeta} a
		   JOIN {$wpdb->postmeta} b ON b.meta_key = '_wp_attached_file' AND b.meta_value = a.meta_value AND b.post_id <> a.post_id
		  WHERE a.meta_key = '_wp_attached_file'" ) ) );
	if ( ! $twins ) return $plan;
	return array_values( array_filter( (array) $plan, function ( $p ) use ( $twins ) {
		return ! isset( $twins[ (int) $p['id'] ] );
	} ) );
}

/** 同じファイルを指していた「ふたご」のメディアが先に名前を変えていたら、そのファイルを指すようにします
 *  （2026/10/23 点検で、コラムの写真86枚が「ファイルなし」になっていた件の手当て） */
function ymkrf_seo_follow_twin( $id, $oldrel ) {
	global $wpdb;
	$up = wp_get_upload_dir();
	$twins = $wpdb->get_col( $wpdb->prepare(
		"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ymkrf_renamed_from' AND meta_value = %s AND post_id <> %d", $oldrel, $id ) );
	foreach ( (array) $twins as $tw ) {
		$trel = (string) get_post_meta( (int) $tw, '_wp_attached_file', true );
		if ( $trel === '' || ! file_exists( $up['basedir'] . '/' . $trel ) ) continue;
		update_post_meta( $id, '_wp_attached_file', $trel );
		$tm = wp_get_attachment_metadata( (int) $tw );
		if ( is_array( $tm ) ) wp_update_attachment_metadata( $id, $tm );
		$wpdb->update( $wpdb->posts, array( 'guid' => $up['baseurl'] . '/' . $trel ), array( 'ID' => $id ) );
		$t = get_the_title( $id );
		if ( preg_match( '/^[a-z0-9_-]+$/i', (string) $t ) ) {
			$wpdb->update( $wpdb->posts, array( 'post_title' => get_the_title( (int) $tw ) ), array( 'ID' => $id ) );
		}
		if ( get_post_meta( $id, '_ymkrf_renamed_from', true ) === '' ) update_post_meta( $id, '_ymkrf_renamed_from', $oldrel );
		update_post_meta( $id, '_ymkrf_twin_of', (int) $tw );
		clean_post_cache( $id );
		return true;
	}
	return false;
}

/* 点検で「ファイルなし」と出たものを、ふたごのファイルにつなぎ直します（ids を送る） */
add_action( 'wp_ajax_ymkrf_seoname_twinfix', function () {
	check_ajax_referer( 'ymkrf_seoname', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'cap' );
	$up  = wp_get_upload_dir();
	$ids = isset( $_POST['ids'] ) ? array_map( 'intval', (array) $_POST['ids'] ) : array();
	$res = array();
	foreach ( $ids as $id ) {
		$rel = (string) get_post_meta( $id, '_wp_attached_file', true );
		if ( $rel !== '' && file_exists( $up['basedir'] . '/' . $rel ) ) { $res[ $id ] = 'ok'; continue; }
		$was = (string) get_post_meta( $id, '_ymkrf_renamed_from', true );
		if ( $was === '' ) { $res[ $id ] = 'nowas'; continue; }
		/* 前の名前のファイルが残っていれば、それにもどします */
		if ( file_exists( $up['basedir'] . '/' . $was ) ) {
			update_post_meta( $id, '_wp_attached_file', $was );
			$res[ $id ] = 'back'; continue;
		}
		$res[ $id ] = ymkrf_seo_follow_twin( $id, $was ) ? 'twin' : ( 'none:' . $was );
	}
	if ( function_exists( 'ymkrf_media_forget' ) ) ymkrf_media_forget();
	wp_send_json_success( $res );
} );


/* ============================================================
   3-b. 施工事例の写真（2026/10/23 ユーザー「全部変えた方が良いね」）
        名前＝ 市町 － 部位 － reform － 案件番号 － before/during/after － 何枚目
        例： kaga-bathroom-reform-2601-0395-after-3.jpg
   ============================================================ */
function ymkrf_seo_plan_works() {
	global $wpdb;
	$plan = array(); $taken = array();
	$up = wp_get_upload_dir();
	$ids = get_posts( array( 'post_type' => 'ymkrf_works', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) );
	foreach ( $ids as $wid ) {
		/* 市町 */
		$area = '';
		$at = wp_get_object_terms( $wid, 'ymkrf_works_area' );
		if ( ! is_wp_error( $at ) ) foreach ( $at as $t ) {
			if ( (int) $t->parent === 0 ) continue;   /* 県（いちばん上）は使わない */
			$area = function_exists( 'ymkrf_area_roman' ) && function_exists( 'ymkrf_area_clean' )
				? ymkrf_area_roman( ymkrf_area_clean( $t->name ) ) : $t->slug;
			if ( $area === '' ) $area = $t->slug;
			break;
		}
		/* 部位 */
		$pt = wp_get_object_terms( $wid, 'ymkrf_works_cat', array( 'fields' => 'slugs' ) );
		$part = ( ! is_wp_error( $pt ) && $pt ) ? (string) $pt[0] : '';
		/* 案件番号 */
		$no = trim( (string) get_post_meta( $wid, '_ymkrf_case_no', true ) );
		$no = strtolower( trim( (string) preg_replace( '/[^0-9a-z]+/i', '-', $no ), '-' ) );
		if ( $no === '' ) $no = 'w' . $wid;
		$head = implode( '-', array_filter( array( sanitize_title( $area ), sanitize_title( $part ), 'reform', $no ) ) );

		/* どの写真が、施工前・中・後の何枚目か */
		$use = array();
		foreach ( array( 'before', 'during', 'after' ) as $ph ) {
			$list = function_exists( 'ymkrf_works_photos' ) ? ymkrf_works_photos( $wid, $ph ) : array();
			foreach ( array_values( $list ) as $i => $aid ) if ( ! isset( $use[ $aid ] ) ) $use[ $aid ] = array( $ph, $i + 1, count( $list ) );
		}
		/* この事例に上げてあるのに、どこにも入っていない写真 */
		$kids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_parent = %d AND post_status <> 'trash'", $wid ) );
		$k = 0;
		foreach ( $kids as $aid ) if ( ! isset( $use[ (int) $aid ] ) ) { $k++; $use[ (int) $aid ] = array( 'photo', $k, 2 ); }

		foreach ( $use as $aid => $u ) {
			$rel = (string) get_post_meta( $aid, '_wp_attached_file', true );
			if ( $rel === '' ) continue;
			$ext = strtolower( pathinfo( $rel, PATHINFO_EXTENSION ) );
			$sub = ( dirname( $rel ) === '.' ) ? '' : dirname( $rel ) . '/';
			$body = $head . '-' . $u[0] . ( $u[2] > 1 || $u[0] === 'photo' ? '-' . $u[1] : '' );
			$try = $body; $n = 1;
			while ( isset( $taken[ $sub . $try ] ) || ( $sub . $try . '.' . $ext !== $rel && file_exists( $up['basedir'] . '/' . $sub . $try . '.' . $ext ) ) ) { $n++; $try = $body . '-' . $n; }
			$taken[ $sub . $try ] = true;
			$to = $sub . $try . '.' . $ext;
			if ( preg_replace( '/-scaled(\.[a-z0-9]+)$/i', '$1', $rel ) === $to ) continue;
			$labels = array( 'before' => '施工前', 'during' => '施工中', 'after' => '施工後', 'photo' => '（使っていない写真）' );
			$plan[] = array( 'id' => (int) $aid, 'pid' => (int) $wid, 'from' => $rel, 'to' => $to,
				'why' => $labels[ $u[0] ] . ' ' . $u[1] . '枚目' );
		}
	}
	return $plan;
}


/* ============================================================
   3-c. コラムの写真（旧ブログから取りこんだ ymkc-ばらばらの文字.jpg）
        名前＝ column － コラムの内容（英語）－ 何枚目
        例： column-shower-head-diy-2.jpg
        コラムの内容の英語は、題名を見ながら手で付けました（2026/10/23）。
   ============================================================ */
function ymkrf_seo_column_slugs() {
	return array(
		16019 => 'heavy-snow',
		16026 => 'snow-power',
		16042 => 'new-life-spring',
		16047 => 'new-employees',
		16054 => 'spring-color-kitchen-sale',
		16060 => 'washroom-before-after',
		16065 => 'showroom-clearance-sale-2',
		16070 => 'customer-appreciation-sale-report',
		16082 => 'moisture-resistant-wall',
		16086 => 'shopping-voucher-gift',
		16091 => 'chikuden-market-website-open',
		16097 => 'summer-window-energy-saving',
		16101 => 'summer-window-energy-saving-2',
		16106 => 'foam-cleaning-toilet',
		16110 => 'water-leak-check',
		16118 => 'easy-clean-house',
		16130 => 'warm-bathroom-remodel',
		16139 => 'next-generation-housing-points',
		16154 => 'toilet-maker-sterilization-comparison',
		16168 => 'kitchen-remodel-cost',
		16176 => 'old-bathroom-to-unit-bath',
		16185 => 'kitchen-remodel-family-story',
		16195 => 'old-toilet-remodel-story',
		16206 => 'living-memo',
		16218 => 'summer-comfortable-house',
		16226 => 'soap-free-bathtub',
		16242 => 'year-end-cleaning-tips',
		16261 => 'reform-yamakishi-grand-opening',
		16303 => 'window-condensation-winter',
		16322 => 'storage-shed-guide',
		16341 => 'my-toilet-remodel-part1',
		16363 => 'my-toilet-remodel-part2-estimate',
		16393 => 'my-toilet-remodel-part3',
		16415 => 'my-toilet-remodel-part4',
		16431 => 'my-toilet-remodel-part5',
		16465 => 'truck-wrapping',
		16484 => 'sustainability-subsidy',
		16489 => 'newspaper-reuse-ideas',
		16503 => 'winter-exterior-painting',
		16511 => 'no-hot-water-trouble',
		16527 => 'electric-water-heater-broken-1',
		16560 => 'electric-water-heater-broken-2',
		16600 => 'green-housing-eco-points',
		16638 => 'nonoichi-showroom-kitchen-video',
		16661 => 'entrance-gate-exterior',
		16664 => 'summer-heat-house-tips',
		16682 => 'nonoichi-store-event',
		16709 => 'latest-kitchen-features',
		16745 => 'pre-snow-house-checklist',
		16812 => 'water-heater-types-comparison',
		16829 => 'winter-heat-shock-prevention',
		16849 => 'best-time-to-remodel',
		16931 => 'kodomo-mirai-housing-subsidy',
		16944 => 'building-material-price-increase',
		16971 => 'exterior-painting-guide',
		17009 => 'carport-guide',
		17067 => 'toilet-types-and-cleaning',
		17114 => 'typhoon-disaster-prep',
		17132 => 'inner-window-subsidy',
		17152 => 'winter-laundry-drying',
		17208 => 'ecocute-faq',
		17225 => 'low-cost-diy-repair',
		17266 => 'faucet-replacement',
		17301 => 'electricity-price-rise-solutions',
		17315 => 'home-security-tips',
		17338 => 'combination-toilet-popular',
		17365 => 'rainy-season-cleaning',
		17400 => 'toto-unit-bath-sale',
		17480 => 'aircon-cooling-vs-dehumidify-cost',
		17504 => 'hot-summer-heavy-snow-theory',
		17534 => 'ecocute-self-maintenance',
		17581 => 'popular-remodel-cost-survey',
		17630 => 'noto-earthquake-housing-support',
		17653 => 'ecocute-wall-painting',
		17667 => 'spring-pollen-measures',
		17711 => 'remodel-subsidy-2024',
		17748 => 'japanese-to-western-toilet',
		17797 => 'window-screen-types',
		17841 => 'shower-head-diy',
		17868 => 'house-heat-causes',
		17912 => 'typhoon-safety-measures',
		17968 => 'water-heater-subsidy-deadline',
		18024 => 'ecocute-repair-cases',
		18046 => 'winter-heating-cost-saving',
		18088 => 'remodel-subsidy-2025-ecocute',
		18114 => 'frozen-pipes-no-hot-water',
		18133 => 'spring-theft-prevention',
		18162 => 'gas-stove-to-ih',
		18179 => 'toilet-maker-spec-comparison',
		18247 => 'bath-toilet-fan-cleaning',
		18305 => 'remodel-loan-subsidy',
		18317 => 'tariff-impact-remodel',
		18333 => 'senior-ldk-remodel',
		18348 => 'remodel-timing-hokuriku',
		18378 => 'bathroom-remodel-example',
		18387 => 'storage-remodel-example',
		18399 => 'typhoon-flood-home-protection',
		18427 => 'window-remodel-subsidy-2026',
		18450 => 'unit-bath-present-campaign',
		18478 => 'ecocute-subsidy-last-chance',
		18520 => 'ecocute-no-hot-water',
		18564 => 'year-end-safety-checklist-fire',
		18588 => 'year-end-safety-checklist-heat-shock',
		18617 => 'year-end-safety-checklist-quake-security',
		18671 => 'year-end-safety-checklist-snow-humidity',
		18724 => 'kyuto-shoene-2026',
		18740 => 'ohisama-ecocute',
		18752 => 'housing-energy-saving-campaign-2026',
		18768 => 'pollen-free-home-2026',
		18831 => 'aircon-2027-problem',
		18857 => 'middle-east-housing-equipment-2026',
		18880 => 'ishikawa-energy-saving-appliance-campaign',
		18907 => 'rainy-season-dehumidifier',
		18944 => 'fukui-subsidy-2026',
		18969 => 'middle-east-bath-exterior-2026',
		18990 => 'price-increase-yamakishi-fixed-price',
		19018 => 'summer-electricity-heat-measures-2026',
		19064 => 'luxury-home-equipment-7',
		19087 => 'takara-standard-price-adjustment',
		19092 => 'unit-bath-campaign-panasonic-lixil-cleanup',
	);
}
function ymkrf_seo_plan_column() {
	global $wpdb;
	$plan = array(); $taken = array(); $count = array();
	$up = wp_get_upload_dir();
	$map = ymkrf_seo_column_slugs();
	$atts = $wpdb->get_results(
		"SELECT p.ID, p.post_parent, m.meta_value AS rel FROM {$wpdb->posts} p
		   JOIN {$wpdb->postmeta} m ON ( m.post_id = p.ID AND m.meta_key = '_wp_attached_file' )
		  WHERE p.post_type = 'attachment' AND p.post_status <> 'trash' AND m.meta_value LIKE '%ymkc-%'
		  ORDER BY p.post_parent ASC, p.ID ASC" );
	foreach ( (array) $atts as $a ) {
		$rel = (string) $a->rel;
		if ( strpos( wp_basename( $rel ), 'ymkc-' ) !== 0 ) continue;
		$pid = (int) $a->post_parent;
		/* 親がない写真は、本文にその写真が書いてあるコラムをさがします */
		if ( ! $pid ) {
			$body = preg_replace( '/(-scaled)?\.[a-z0-9]+$/i', '', wp_basename( $rel ) );
			$pid = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'ymkrf_column' AND post_status <> 'trash' AND post_content LIKE %s ORDER BY ID ASC LIMIT 1",
				'%' . $wpdb->esc_like( $body ) . '%' ) );
		}
		$slug = $pid && isset( $map[ $pid ] ) ? $map[ $pid ] : '';
		if ( $slug === '' ) {
			$plan[] = array( 'id' => (int) $a->ID, 'pid' => $pid, 'from' => $rel, 'to' => '',
				'why' => $pid ? 'コラムの英語の名前が未登録なので、そのまま' : 'どのコラムにも使われていないので、そのまま' );
			continue;
		}
		$count[ $slug ] = ( $count[ $slug ] ?? 0 ) + 1;
		$ext = strtolower( pathinfo( $rel, PATHINFO_EXTENSION ) );
		$sub = ( dirname( $rel ) === '.' ) ? '' : dirname( $rel ) . '/';
		$body2 = 'column-' . $slug . '-' . $count[ $slug ];
		$try = $body2; $n = 1;
		while ( isset( $taken[ $sub . $try ] ) || ( $sub . $try . '.' . $ext !== $rel && file_exists( $up['basedir'] . '/' . $sub . $try . '.' . $ext ) ) ) { $n++; $try = $body2 . '-' . $n; }
		$taken[ $sub . $try ] = true;
		$plan[] = array( 'id' => (int) $a->ID, 'pid' => $pid, 'from' => $rel, 'to' => $sub . $try . '.' . $ext,
			'why' => 'コラム ' . $count[ $slug ] . '枚目' );
	}
	return $plan;
}


/* ============================================================
   3-d. 商品の写真（はじめに私がまとめて入れた写真。sazanat-tub-white.jpg など）
        商品の名前が略してあるので、「メーカー－商品」をきちんと付けます。
        例： sazanat-tub-white.jpg → toto-sazana-t-tub-white.jpg
             amzi-color-purewhite.jpg → lixil-amage-z-color-purewhite.jpg
        ※ 2つ以上の商品で使っている写真（カタログの表紙など）は、どれか1つの名前にできないので、そのまま
   ============================================================ */
function ymkrf_seo_plan_product2() {
	global $wpdb;
	$plan = array(); $taken = array(); $usedby = array();
	$up = wp_get_upload_dir();
	$prods = get_posts( array( 'post_type' => 'ymkrf_product', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) );
	$uses = array();
	foreach ( $prods as $pid ) {
		$uses[ $pid ] = ymkrf_seo_usage_map( $pid );
		foreach ( array_keys( $uses[ $pid ] ) as $aid ) $usedby[ $aid ][ $pid ] = true;
	}
	$generic = array( 'color', 'spec', 'point', 'opt', 'option', 'cabinet', 'handle', 'tile', 'main', 'img', 'image', 'photo', 'card', 'tub', 'sink', 'top', 'wall', 'floor', 'door' );
	$makers = get_terms( array( 'taxonomy' => 'ymkrf_maker', 'hide_empty' => false, 'fields' => 'slugs' ) );
	$makers = is_wp_error( $makers ) ? array() : (array) $makers;
	foreach ( $prods as $pid ) {
		$base = function_exists( 'ymkrf_media_product_filebase' ) ? ymkrf_media_product_filebase( $pid ) : '';
		$old  = function_exists( 'ymkrf_media_product_filebase' ) ? ymkrf_media_product_filebase( $pid, true ) : '';
		if ( $base === '' ) continue;
		foreach ( $uses[ $pid ] as $aid => $u ) {
			if ( count( $usedby[ $aid ] ) > 1 ) continue;                       /* いくつもの商品で使っている写真 */
			if ( get_post_status( $aid ) === 'trash' ) continue;
			$rel = (string) get_post_meta( $aid, '_wp_attached_file', true );
			if ( $rel === '' ) continue;
			$file = wp_basename( $rel );
			$body = preg_replace( '/(-scaled)?\.[a-z0-9]+$/i', '', $file );
			$ext  = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
			if ( stripos( $body, $base ) === 0 || ( $old !== '' && stripos( $body, $old ) === 0 ) ) continue;   /* もう商品の名前で始まっている */
			$skip = false;
			foreach ( $makers as $mk ) if ( $mk !== '' && stripos( $body, $mk . '-' ) === 0 ) { $skip = true; break; }
			if ( $skip ) continue;                                                /* メーカー名で始まる名前は、もう分かりやすい */
			if ( preg_match( '/^(catalog|pack4|logo|staff|voice|works|ymk[a-z])[-_]/i', $body ) ) continue;
			/* 頭の略した商品名（sazanat- など）を外して、のこりを使います */
			$tok  = preg_split( '/[-_]/', strtolower( $body ) );
			if ( count( $tok ) >= 2 && ! in_array( $tok[0], $generic, true ) ) array_shift( $tok );
			$rest = trim( (string) preg_replace( '/[^a-z0-9]+/', '-', implode( '-', $tok ) ), '-' );
			if ( $rest === '' || preg_match( '/^\d+$/', $rest ) ) $rest = trim( $u['tag'] . '-' . ymkrf_seo_slugify( $u['text'] ), '-' );
			$nb  = $base . ( $rest !== '' && $rest !== 'main' ? '-' . $rest : '' );
			$sub = ( dirname( $rel ) === '.' ) ? '' : dirname( $rel ) . '/';
			$try = $nb; $n = 1;
			while ( isset( $taken[ $sub . $try ] ) || ( $sub . $try . '.' . $ext !== $rel && file_exists( $up['basedir'] . '/' . $sub . $try . '.' . $ext ) ) ) { $n++; $try = $nb . '-' . $n; }
			$taken[ $sub . $try ] = true;
			$to = $sub . $try . '.' . $ext;
			if ( preg_replace( '/-scaled(\.[a-z0-9]+)$/i', '$1', $rel ) === $to ) continue;
			$plan[] = array( 'id' => (int) $aid, 'pid' => (int) $pid, 'from' => $rel, 'to' => $to, 'why' => $u['tag'] . ( $u['text'] !== '' ? '「' . $u['text'] . '」' : '' ) );
		}
	}
	return $plan;
}


/* ============================================================
   4. 画面（メディア ＞ 写真の名前をそろえる）
      グループごとに：下見 → 「この内容で名前を変える」→ 少しずつ（30枚ずつ）進めます
   ============================================================ */
function ymkrf_seo_groups() {
	return array(
		'product' => array( '商品の写真',     'ymkrf_seo_plan' ),
		'works'   => array( '施工事例の写真', 'ymkrf_seo_plan_works' ),
		'column'  => array( 'コラムの写真',   'ymkrf_seo_plan_column' ),
		'product2'=> array( '商品の写真（はじめに入れた分）', 'ymkrf_seo_plan_product2' ),
	);
}

add_action( 'admin_menu', function () {
	add_media_page( '写真の名前をそろえる', '写真の名前をそろえる', 'manage_options', 'ymkrf-seoname', 'ymkrf_seoname_page' );
} );

/* 30枚ずつ進めるための窓口（下見で作った一覧を、おぼえておいて少しずつ） */
add_action( 'wp_ajax_ymkrf_seoname_run', function () {
	check_ajax_referer( 'ymkrf_seoname', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'cap' );
	$g = isset( $_POST['group'] ) ? sanitize_key( $_POST['group'] ) : '';
	$groups = ymkrf_seo_groups();
	if ( ! isset( $groups[ $g ] ) ) wp_send_json_error( 'group' );
	@set_time_limit( 120 );
	$key = 'ymkrf_seo_queue_' . $g;
	$q = get_option( $key );
	if ( ! is_array( $q ) || ! empty( $_POST['start'] ) ) {
		$q = array();
		foreach ( ymkrf_seo_plan_clean( call_user_func( $groups[ $g ][1] ) ) as $p ) if ( $p['to'] !== '' ) $q[] = array( $p['id'], $p['to'] );
		update_option( $key, $q, false );
		update_option( $key . '_total', count( $q ), false );
		update_option( $key . '_ng', array(), false );
	}
	$ng = (array) get_option( $key . '_ng', array() );
	$t0 = microtime( true );
	while ( $q && microtime( true ) - $t0 < 20 ) {
		$it = array_shift( $q );
		/* 施工事例の写真は、文字だけの欄（postmeta）にURLを書いていないので、そこは調べません（速くするため） */
		if ( ! ymkrf_seo_rename( (int) $it[0], (string) $it[1], $g !== 'works' ) ) $ng[] = (int) $it[0];
	}
	update_option( $key, $q, false );
	update_option( $key . '_ng', $ng, false );
	if ( ! $q ) delete_option( $key );
	wp_send_json_success( array( 'left' => count( $q ), 'total' => (int) get_option( $key . '_total' ), 'ng' => count( $ng ) ) );
} );

/* 名前を変えたあとの点検：メディアの情報が指すファイルが、ちゃんとあるか（とちゅうで止まったときの見張り） */
add_action( 'wp_ajax_ymkrf_seoname_verify', function () {
	check_ajax_referer( 'ymkrf_seoname', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'cap' );
	global $wpdb;
	@set_time_limit( 120 );
	$up  = wp_get_upload_dir();
	$off = isset( $_POST['offset'] ) ? (int) $_POST['offset'] : 0;
	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT m.post_id, m.meta_value AS rel, r.meta_value AS was FROM {$wpdb->postmeta} r
		   JOIN {$wpdb->postmeta} m ON ( m.post_id = r.post_id AND m.meta_key = '_wp_attached_file' )
		  WHERE r.meta_key = '_ymkrf_renamed_from' ORDER BY m.post_id LIMIT %d, 2000", $off ) );
	$bad = array();
	foreach ( (array) $rows as $r ) {
		$rel = (string) $r->rel;
		$ok  = file_exists( $up['basedir'] . '/' . $rel );
		if ( ! $ok ) {
			/* 新しい名前のファイルが無いのに、前の名前のファイルが残っていれば、前の名前にもどします */
			$was = (string) $r->was;
			if ( $was !== '' && file_exists( $up['basedir'] . '/' . $was ) ) {
				update_post_meta( (int) $r->post_id, '_wp_attached_file', $was );
				$meta = wp_get_attachment_metadata( (int) $r->post_id );
				if ( is_array( $meta ) && ! empty( $meta['file'] ) ) { $meta['file'] = $was; wp_update_attachment_metadata( (int) $r->post_id, $meta ); }
				$bad[] = $r->post_id . ':fixed';
			} else {
				$bad[] = $r->post_id . ':missing';
			}
		}
	}
	wp_send_json_success( array( 'checked' => count( $rows ), 'bad' => $bad ) );
} );

function ymkrf_seoname_page() {
	if ( ! current_user_can( 'manage_options' ) ) wp_die( '権限がありません' );
	$groups = ymkrf_seo_groups();
	$g = isset( $_GET['group'] ) ? sanitize_key( $_GET['group'] ) : 'product';
	if ( ! isset( $groups[ $g ] ) ) $g = 'product';
	$plan = ymkrf_seo_plan_clean( call_user_func( $groups[ $g ][1] ) );
	$todo = array_values( array_filter( $plan, function ( $p ) { return $p['to'] !== ''; } ) );
	$show = array_slice( $plan, 0, 300 );
	?>
	<div class="wrap">
		<h1>写真の名前をそろえる</h1>
		<p>写真のファイル名を、<b>何の写真か分かる名前</b>にします（画像検索に効きます）。
		   ファイル（大きさちがいも全部）・メディアの情報・本文の中のURLを、いっしょに直します。写真が消えることはありません。
		   前の名前は、それぞれの写真に控えてあります。</p>
		<h2 class="nav-tab-wrapper">
			<?php foreach ( $groups as $k => $gr ) : ?>
				<a class="nav-tab<?php echo $k === $g ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'upload.php?page=ymkrf-seoname&group=' . $k ) ); ?>"><?php echo esc_html( $gr[0] ); ?></a>
			<?php endforeach; ?>
		</h2>
		<?php if ( ! $todo ) : ?>
			<p style="margin-top:16px;color:#646970">このグループで変える写真はありません。</p>
		<?php else : ?>
			<p style="margin:14px 0 8px"><b><?php echo number_format( count( $todo ) ); ?>枚</b>の名前を変えます。<?php
				echo count( $plan ) > 300 ? '（下は、はじめの300枚の見本です）' : ''; ?></p>
			<p><button type="button" class="button button-primary" id="ymkrf-seo-run">この内容で名前を変える（<?php echo number_format( count( $todo ) ); ?>枚）</button>
				<span id="ymkrf-seo-prog" style="margin-left:10px;font-weight:600"></span></p>
		<?php endif; ?>
		<?php if ( $show ) : ?>
			<table class="widefat striped" id="ymkrf-seoname-table" style="margin-top:10px">
				<thead><tr><th style="width:56px">写真</th><th>使っている記事</th><th>いまの名前</th><th>新しい名前</th><th>使っているところ</th></tr></thead>
				<tbody>
				<?php foreach ( $show as $p ) : ?>
					<tr data-id="<?php echo (int) $p['id']; ?>">
						<td><?php echo wp_get_attachment_image( $p['id'], array( 48, 48 ) ); ?></td>
						<td><?php echo esc_html( wp_html_excerpt( get_the_title( $p['pid'] ), 40, '…' ) ); ?></td>
						<td><code><?php echo esc_html( wp_basename( $p['from'] ) ); ?></code></td>
						<td><?php echo $p['to'] !== '' ? '<code style="color:#00782a">' . esc_html( wp_basename( $p['to'] ) ) . '</code>' : '<span style="color:#a7aaad">そのまま</span>'; ?></td>
						<td><?php echo esc_html( $p['why'] ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<script>
	(function () {
		var btn = document.getElementById('ymkrf-seo-run'); if (!btn) return;
		var prog = document.getElementById('ymkrf-seo-prog');
		function step(first) {
			var fd = new FormData();
			fd.append('action', 'ymkrf_seoname_run'); fd.append('group', <?php echo wp_json_encode( $g ); ?>);
			fd.append('nonce', <?php echo wp_json_encode( wp_create_nonce( 'ymkrf_seoname' ) ); ?>);
			if (first) fd.append('start', '1');
			fetch(ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) {
				if (!r || !r.success) throw 0;
				var d = r.data, done = d.total - d.left;
				prog.textContent = done + ' / ' + d.total + ' 枚' + (d.ng ? '（変えられなかったもの ' + d.ng + ' 枚）' : '');
				if (d.left > 0) step(false); else { prog.textContent += ' ── おわりました'; window.setTimeout(function () { location.reload(); }, 1500); }
			}).catch(function () { prog.textContent += ' ── とちゅうで止まりました。もう一度押すと続きから進めます'; btn.disabled = false; });
		}
		btn.addEventListener('click', function () {
			if (!window.confirm('名前を変えます。とちゅうで画面を閉じても、もう一度押せば続きから進めます。よろしいですか？')) return;
			btn.disabled = true; prog.textContent = 'はじめています…'; step(true);
		});
	})();
	</script>
	<?php
}
