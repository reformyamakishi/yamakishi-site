<?php
/**
 * functions-media.php ─ メディア（写真）を、使い道べつに分けて見られるようにします
 *
 * 置き場所： wp-content/themes/ymkrf/inc/functions-media.php
 *
 * （2026/09/16 ユーザー相談「メディアを、使っているページ別に
 *   左側でフォルダ分けできないか」）
 *
 * WordPress のメディアには、もともとフォルダがありません。
 * そのかわり、写真はかならず「どの記事といっしょに上げたか」を持っています
 * （post_parent といいます）。それを見て、自動でふりわけています。
 *
 * ★手でふりわける仕組みは、まだ作っていません。
 *   実際の枚数を見てから決める、というお話でした（2026/09/16）。
 */

if ( ! defined( 'ABSPATH' ) ) exit;


/** フォルダの一覧（左メニューに出る順番です） */
if ( ! function_exists( 'ymkrf_media_kinds' ) ) :
function ymkrf_media_kinds() {
	return array(
		'product' => array( 'name' => '商品',          'types' => array( 'ymkrf_product' ) ),
		'works'   => array( 'name' => '施工事例',      'types' => array( 'ymkrf_works' ) ),
		'voice'   => array( 'name' => 'お客様の声',    'types' => array( 'ymkrf_voice' ) ),
		'column'  => array( 'name' => 'コラム',        'types' => array( 'ymkrf_column' ) ),
		'news'    => array( 'name' => 'お知らせ',      'types' => array( 'ymkrf_news' ) ),
		'flyer'   => array( 'name' => 'イベント・チラシ', 'types' => array( 'ymkrf_flyer' ) ),
		'staff'   => array( 'name' => 'スタッフ',      'types' => array( 'ymkrf_staff' ) ),
		'page'    => array( 'name' => 'ページ・その他', 'types' => array( 'page', 'post' ) ),
		'none'    => array( 'name' => 'どこにも付いていない', 'types' => array() ),
	);
}
endif;

/**
 * フォルダごとの枚数。
 * 1回のSQLで数えて、5分だけ覚えておきます
 * （写真が数千枚あるので、毎回数えると管理画面が重くなります）。
 */
if ( ! function_exists( 'ymkrf_media_counts' ) ) :
function ymkrf_media_counts() {

	$hit = get_transient( 'ymkrf_media_counts' );
	if ( is_array( $hit ) ) return $hit;

	global $wpdb;
	$rows = $wpdb->get_results(
		"SELECT COALESCE( par.post_type, '' ) AS pt, COUNT(*) AS n
		   FROM {$wpdb->posts} att
		   LEFT JOIN {$wpdb->posts} par ON par.ID = att.post_parent
		  WHERE att.post_type = 'attachment'
		  GROUP BY pt"
	);

	/* 記事の種類ごとの枚数を、フォルダごとにまとめ直します */
	$bytype = array();
	foreach ( (array) $rows as $r ) $bytype[ (string) $r->pt ] = (int) $r->n;

	$out = array();
	foreach ( ymkrf_media_kinds() as $key => $k ) {
		if ( $key === 'none' ) {
			$out[ $key ] = isset( $bytype[''] ) ? $bytype[''] : 0;
			continue;
		}
		$n = 0;
		foreach ( $k['types'] as $t ) if ( isset( $bytype[ $t ] ) ) $n += $bytype[ $t ];
		$out[ $key ] = $n;
	}

	set_transient( 'ymkrf_media_counts', $out, 5 * MINUTE_IN_SECONDS );
	return $out;
}
endif;

/* 写真を足したり消したりしたら、覚えていた枚数を捨てます */
add_action( 'add_attachment',    function () { delete_transient( 'ymkrf_media_counts' ); } );
add_action( 'delete_attachment', function () { delete_transient( 'ymkrf_media_counts' ); } );
add_action( 'edit_attachment',   function () { delete_transient( 'ymkrf_media_counts' ); } );


/* ------------------------------------------------------------
   「メディア」を押したときの、フォルダをえらぶ画面
   （2026/09/16 ユーザー指示「イベント・チラシみたいに右にフォルダ分けして、
     左のプルダウンは不要」）

   ★リンクに mode=list を付けています。
     写真がタイル状にならぶ「グリッド表示」は、あとから中身を
     読み込むしくみのため、しぼり込みが効かないからです。
   ------------------------------------------------------------ */
if ( ! function_exists( 'ymkrf_media_folders_page' ) ) :
function ymkrf_media_folders_page() {

	$counts = ymkrf_media_counts();
	$all    = array_sum( $counts );

	$card = function ( $name, $key, $n, $note = '' ) {
		$url = $key
			? admin_url( 'upload.php?ymkrf_use=' . $key . '&mode=list' )
			: admin_url( 'upload.php?mode=list' );
		?>
		<a class="ymkrf-mf__card<?php echo $key ? ' ymkrf-mf__card--cat' : ' ymkrf-mf__card--all'; ?>"
		   href="<?php echo esc_url( $url ); ?>">
		  <span class="ymkrf-mf__name"><?php echo esc_html( $name ); ?></span>
		  <?php if ( $note ) : ?><span class="ymkrf-mf__note"><?php echo esc_html( $note ); ?></span><?php endif; ?>
		  <span class="ymkrf-mf__cnt">
		    <?php if ( $n ) : ?>
		      <span class="ymkrf-mf__n"><?php echo esc_html( number_format_i18n( $n ) ); ?>枚</span>
		    <?php else : ?>
		      <span class="ymkrf-mf__zero">写真なし</span>
		    <?php endif; ?>
		  </span>
		</a>
		<?php
	};
	?>
	<div class="wrap ymkrf-mf">
	  <h1>メディア
	    <a class="page-title-action" href="<?php echo esc_url( admin_url( 'media-new.php' ) ); ?>">新規追加</a>
	  </h1>

	  <h2 class="ymkrf-mf__h2">フォルダからえらぶ</h2>
	  <div class="ymkrf-mf__grid">
	    <?php foreach ( ymkrf_media_kinds() as $key => $k ) {
	      $n = isset( $counts[ $key ] ) ? (int) $counts[ $key ] : 0;
	      if ( $n === 0 && $key !== 'none' ) continue;   /* 0枚のものは出しません */
	      $note = ( $key === 'none' )
	            ? 'メディア画面から直接いれた写真です。'
	            : $k['name'] . 'といっしょに入れた写真です。';
	      $card( $k['name'], $key, $n, $note );
	    } ?>
	  </div>

	  <h2 class="ymkrf-mf__h2">まとめて見る</h2>
	  <div class="ymkrf-mf__grid">
	    <?php $card( 'すべての写真', '', $all, 'フォルダを分けずに、ぜんぶ見ます。' ); ?>
	  </div>

	  <p class="ymkrf-mf__foot">
	    写真は、どの記事といっしょに入れたかで自動でふりわけています。
	    写真そのものを動かしたりコピーしたりはしていません。
	  </p>
	</div>

	<style>
	  .ymkrf-mf__h2{margin:26px 0 10px;padding-left:9px;font-size:15px;
	    border-left:4px solid #fe3301;line-height:1.5}
	  .ymkrf-mf__grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(268px,1fr));gap:12px}
	  .ymkrf-mf__card{display:block;padding:13px 16px;background:#fff;border:1px solid #dcdcde;
	    border-radius:6px;text-decoration:none;color:inherit;transition:border-color .15s,box-shadow .15s}
	  .ymkrf-mf__card:hover{box-shadow:0 1px 6px rgba(0,0,0,.08)}
	  /* 「すべての写真」はオレンジ、フォルダはグリーンで囲みます */
	  .ymkrf-mf__card--all{border-color:#fe3301;background:#fff8f5}
	  .ymkrf-mf__card--all:hover{border-color:#fe3301}
	  .ymkrf-mf__card--cat{border-color:#00782a;background:#eff8f2}
	  .ymkrf-mf__card--cat:hover{border-color:#005a1f;background:#e4f3ea}
	  .ymkrf-mf__name{display:block;font-size:15px;font-weight:700;line-height:1.4}
	  .ymkrf-mf__note{display:block;margin-top:3px;font-size:11.5px;color:#787878;line-height:1.5}
	  .ymkrf-mf__cnt{display:block;margin-top:7px;font-size:12.5px;line-height:1.6}
	  .ymkrf-mf__n{color:#00782a;font-weight:700}
	  .ymkrf-mf__zero{color:#a7aaad}
	  .ymkrf-mf__foot{margin-top:22px;font-size:13px;color:#50575e}
	</style>
	<?php
}
endif;

/* 小メニューに登録します。
   ★いちばん上に置くと、「メディア」を押したときにこの画面が開きます
     （WordPress は、いちばん上の小メニューを行き先にするしくみです）。 */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'upload.php',
		'フォルダからえらぶ', 'フォルダからえらぶ',
		'upload_files', 'ymkrf-media-folders', 'ymkrf_media_folders_page'
	);
}, 20 );

/* 並べかた ── フォルダをえらぶ画面（かくれています） → ライブラリ → 新規追加 */
add_action( 'admin_menu', function () {
	global $submenu;
	if ( empty( $submenu['upload.php'] ) ) return;

	$top = array(); $rest = array();
	foreach ( $submenu['upload.php'] as $row ) {
		if ( isset( $row[2] ) && $row[2] === 'ymkrf-media-folders' ) $top[] = $row;
		else $rest[] = $row;
	}
	$submenu['upload.php'] = array_merge( $top, $rest );
}, 999 );

/* 「フォルダからえらぶ」は、左の「メディア」を押せば開くので、画面上は隠します */
add_action( 'admin_footer', function () {
	?>
	<script>
	(function () {
		var ul = document.querySelector('#menu-media .wp-submenu');
		if (!ul) return;
		var a  = ul.querySelector('a[href*="page=ymkrf-media-folders"]');
		var li = a && a.closest ? a.closest('li') : null;
		if (li && !li.classList.contains('wp-submenu-head')) li.style.display = 'none';
	})();
	</script>
	<?php
} );


/* ------------------------------------------------------------
   えらんだフォルダの写真だけを出します
   ------------------------------------------------------------ */
add_filter( 'posts_clauses', function ( $clauses, $q ) {

	if ( ! is_admin() || ! $q->is_main_query() ) return $clauses;
	if ( empty( $_GET['ymkrf_use'] ) ) return $clauses;
	if ( ! isset( $GLOBALS['pagenow'] ) || $GLOBALS['pagenow'] !== 'upload.php' ) return $clauses;

	$use   = sanitize_key( wp_unslash( $_GET['ymkrf_use'] ) );
	$kinds = ymkrf_media_kinds();
	if ( ! isset( $kinds[ $use ] ) ) return $clauses;

	global $wpdb;

	/* どこにも付いていない写真 */
	if ( $use === 'none' ) {
		$clauses['where'] .= " AND {$wpdb->posts}.post_parent = 0 ";
		return $clauses;
	}

	$types = $kinds[ $use ]['types'];
	if ( ! $types ) return $clauses;

	$in = array();
	foreach ( $types as $t ) $in[] = $wpdb->prepare( '%s', $t );

	$clauses['join']  .= " INNER JOIN {$wpdb->posts} ymkpar ON ymkpar.ID = {$wpdb->posts}.post_parent ";
	$clauses['where'] .= ' AND ymkpar.post_type IN ( ' . implode( ',', $in ) . ' ) ';

	return $clauses;
}, 10, 2 );

/* 見出しに、いま見ているフォルダの名前を出します */
add_action( 'admin_notices', function () {

	if ( ! isset( $GLOBALS['pagenow'] ) || $GLOBALS['pagenow'] !== 'upload.php' ) return;
	if ( empty( $_GET['ymkrf_use'] ) ) return;

	$use   = sanitize_key( wp_unslash( $_GET['ymkrf_use'] ) );
	$kinds = ymkrf_media_kinds();
	if ( ! isset( $kinds[ $use ] ) ) return;

	$name = $kinds[ $use ]['name'];
	$msg  = ( $use === 'none' )
		? 'どの記事にも付いていない写真だけを出しています。メディア画面から直接アップロードした写真は、ここに入ります。'
		: $name . 'といっしょにアップロードした写真だけを出しています。';
	?>
	<div class="notice notice-info" style="margin-top:14px">
		<p style="font-size:13.5px"><b><?php echo esc_html( $name ); ?>の写真</b>　<?php
			echo esc_html( $msg ); ?>
			<a href="<?php echo esc_url( admin_url( 'upload.php?page=ymkrf-media-folders' ) ); ?>">フォルダ一覧にもどる</a>

			<a href="<?php echo esc_url( admin_url( 'upload.php?mode=list' ) ); ?>">すべての写真を見る</a></p>
	</div>
	<?php
} );
