<?php
/**
 * functions-pack4.php ─ 水まわり4点セットの登録
 * 置き場所： wp-content/themes/ymkrf/inc/functions-pack4.php
 *
 * （2026/09/18 ユーザー指示
 *   「商品ぺージに4点セットの登録を作ってくれたら今から入れます」
 *   「写真は基本、登録してある商品のものを引っ張ってきてほしい」
 *   「4点セットは、キッチン・バス・トイレ・洗面台の4点がセットになっていて
 *     4点一緒にすることで少し安くなるプランです。プランは4種類あります」）
 *
 * ■ どう登録するか
 *   商品カテゴリに「水まわり4点セット」を作りました。
 *   プラン1つ＝商品1件として登録します。
 *
 *     タイトル       … プラン名（例：ヤマキシ1番人気ベストプラン）
 *     キャッチコピー … 例：ワンランク上のオススメ仕様！
 *     並び順         … 1〜4（右の「公開」の下にあります）
 *     いちばん人気   … 1つだけチェック
 *     通常価格       … 4点をばらばらに買ったときの合計
 *     セット価格     … 4点まとめたときの価格
 *     4点の中身      … キッチン・お風呂・トイレ・洗面台を、
 *                      登録済みの商品からえらぶだけ
 *
 * ■ 写真と仕様は、えらんだ商品から自動で出ます
 *   写真・メーカー・商品名・標準仕様は、えらんだ商品のものが使われます。
 *   商品側を直せば、4点セットのページにも自動で反映されます。
 *   ここで写真を入れ直す必要はありません。
 *
 * ■ 名前について
 *   分類のスラッグ … pack4
 *   入力欄 … _ymkrf_p4_kitchen / _ymkrf_p4_bathroom /
 *            _ymkrf_p4_toilet / _ymkrf_p4_lavatory（えらんだ商品のID）
 *            _ymkrf_p4was（通常価格）／_ymkrf_p4now（セット価格）
 *            _ymkrf_p4best（いちばん人気）
 */
if ( ! defined( 'ABSPATH' ) ) exit;


/** 4点セットの分類のスラッグ */
const YMKRF_P4_CAT = 'pack4';

/**
 * 4点の並び。
 *   キー => array( 見出し, もとになる商品カテゴリ )
 * 「もとになる商品カテゴリ」は、えらぶ一覧をしぼるために使います。
 */
function ymkrf_p4_parts() {
	return array(
		'kitchen'  => array( 'キッチン', 'kitchen' ),
		'bathroom' => array( 'お風呂',   'bathroom' ),
		'toilet'   => array( 'トイレ',   'toilet' ),
		'lavatory' => array( '洗面台',   'lavatory' ),
	);
}

/** 入力欄の名前 */
function ymkrf_p4_key( $part ) {
	return '_ymkrf_p4_' . $part;
}

/** いくつまで出すか */
const YMKRF_P4_FEAT_MAX = 5;

/**
 * その部位の「おすすめポイント」を返します（最大5つ）。
 * （2026/09/18 ユーザー指示
 *   「商品ぺージにある、おすすめポイントを最大5つ入れて」）
 *
 * えらんだ商品の「おすすめポイント」の見出しを、上から順に使います。
 * 手で入れる欄はありません。商品側を直せば、こちらにも反映されます。
 * おすすめポイントが無い商品のときは、「標準仕様」で代わりにします。
 */
function ymkrf_p4_feats( $plan_id, $part ) {

	$pid = (int) get_post_meta( $plan_id, ymkrf_p4_key( $part ), true );
	if ( ! $pid ) return array();

	$out = array();

	/* ① おすすめポイントの見出し */
	$rows = get_post_meta( $pid, '_ymkrf_features', true );
	if ( is_array( $rows ) ) {
		foreach ( $rows as $r ) {
			$t = isset( $r['ttl'] ) ? trim( (string) $r['ttl'] ) : '';
			if ( $t === '' ) continue;
			if ( in_array( $t, $out, true ) ) continue;
			$out[] = $t;
			if ( count( $out ) >= YMKRF_P4_FEAT_MAX ) return $out;
		}
	}
	if ( $out ) return $out;

	/* ② おすすめポイントが無いときは、標準仕様で代わりにします */
	$spec = get_post_meta( $pid, '_ymkrf_speclist', true );
	if ( is_array( $spec ) ) {
		foreach ( $spec as $r ) {
			$body = isset( $r['body'] ) ? (string) $r['body'] : '';
			foreach ( preg_split( '/\r\n|\r|\n/', $body ) as $line ) {
				$line = trim( $line );
				if ( $line === '' ) continue;
				$out[] = $line;
				if ( count( $out ) >= YMKRF_P4_FEAT_MAX ) return $out;
			}
		}
	}
	return $out;
}


/* ============================================================
   1. 商品カテゴリ「水まわり4点セット」を用意します
   ============================================================ */
add_action( 'admin_init', function () {

	if ( get_option( 'ymkrf_p4_cat' ) === '1' ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;
	if ( ! taxonomy_exists( 'ymkrf_product_cat' ) ) return;

	if ( ! term_exists( YMKRF_P4_CAT, 'ymkrf_product_cat' ) ) {
		wp_insert_term( '水まわり4点セット', 'ymkrf_product_cat',
			array( 'slug' => YMKRF_P4_CAT ) );
	}
	update_option( 'ymkrf_p4_cat', '1', false );
}, 9 );


/* ============================================================
   2. えらべる商品の一覧
   ============================================================ */

/**
 * その部位の商品を、並び順で返します。
 * 「水まわり4点セット」そのものは、えらべないようにしています。
 */
function ymkrf_p4_choices( $cat_slug ) {

	$posts = get_posts( array(
		'post_type'      => 'ymkrf_product',
		'post_status'    => array( 'publish', 'private', 'draft' ),
		'posts_per_page' => -1,
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'tax_query'      => array( array(
			'taxonomy' => 'ymkrf_product_cat',
			'field'    => 'slug',
			'terms'    => $cat_slug,
		) ),
	) );

	$out = array();
	foreach ( $posts as $p ) {

		$name  = trim( (string) get_post_meta( $p->ID, '_ymkrf_name', true ) );
		if ( $name === '' ) $name = get_the_title( $p->ID );

		/* メーカー名を頭に付けると、同じ商品名でも見分けられます */
		$mk = wp_get_object_terms( $p->ID, 'ymkrf_maker', array( 'fields' => 'names' ) );
		if ( $mk && ! is_wp_error( $mk ) ) $name = $mk[0] . '　' . $name;

		$size = trim( (string) get_post_meta( $p->ID, '_ymkrf_size', true ) );
		if ( $size !== '' ) $name .= '（' . $size . '）';

		if ( $p->post_status !== 'publish' ) $name .= ' ※' . ( $p->post_status === 'draft' ? '下書き' : '非公開' );

		$out[ $p->ID ] = $name;
	}
	return $out;
}


/* ============================================================
   3. 登録画面
   ============================================================ */

/** いま開いている商品が「水まわり4点セット」かどうか */
function ymkrf_p4_is( $post_id ) {
	if ( ! function_exists( 'ymkrf_product_current_cat' ) ) return false;
	return ymkrf_product_current_cat( $post_id ) === YMKRF_P4_CAT;
}

add_action( 'add_meta_boxes', function ( $type, $post ) {

	if ( $type !== 'ymkrf_product' ) return;
	if ( ! $post || ! ymkrf_p4_is( $post->ID ) ) return;

	/* プランは4つで増えないので、URLの箱は出しません。
	   かわりに「商品を編集」の横に、URLを見るだけの形で出します。
	   （2026/09/23 ユーザー指示「URLを商品を編集の横に編集無しで表示にして」） */
	remove_meta_box( 'ymkrf_product_url', 'ymkrf_product', 'normal' );

	/* タイトルのすぐ下に置きます（2026/09/23 ユーザー指示
	   「タイトル下にテキスト欄作成」） */
	add_meta_box( 'ymkrf_p4_lead', 'プランの説明',
		'ymkrf_p4_box_lead', 'ymkrf_product', 'normal', 'high' );

	add_meta_box( 'ymkrf_p4_items', '4点の中身',
		'ymkrf_p4_box_items', 'ymkrf_product', 'normal', 'high' );

	add_meta_box( 'ymkrf_p4_price', 'セットの価格',
		'ymkrf_p4_box_price', 'ymkrf_product', 'normal', 'high' );

}, 20, 2 );


/* 4つのプランのURLを、pack4-plan1 → plan1 に直します（1回だけ）
   （2026/09/23 ユーザー指摘「pack4/pack4-plan1 ➡ pack4/plan1 でよくない？」）
   分類の pack4 がURLの前に付くので、スラッグに pack4 は要りません。
   数字を上げると、もう一度だけ走ります。 */
add_action( 'admin_init', function () {

	if ( get_option( 'ymkrf_p4_slug_ver' ) === '1' ) return;
	if ( ! post_type_exists( 'ymkrf_product' ) ) return;

	$ids = get_posts( array(
		'post_type'      => 'ymkrf_product',
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'fields'         => 'ids',
		'tax_query'      => array( array(
			'taxonomy' => 'ymkrf_product_cat', 'field' => 'slug', 'terms' => YMKRF_P4_CAT,
		) ),
	) );

	foreach ( (array) $ids as $id ) {
		$now = (string) get_post_field( 'post_name', $id );
		if ( strpos( $now, 'pack4-' ) !== 0 ) continue;
		wp_update_post( array( 'ID' => $id, 'post_name' => substr( $now, 6 ) ) );
	}

	update_option( 'ymkrf_p4_slug_ver', '1' );
} );


/* 「商品を編集」の横に、URLを見るだけの形で出します
   （2026/09/23 ユーザー指示） */
add_action( 'admin_footer', function () {

	$s = get_current_screen();
	if ( ! $s || $s->base !== 'post' || $s->post_type !== 'ymkrf_product' ) return;

	$id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0;
	if ( ! $id || ! ymkrf_p4_is( $id ) ) return;

	$url = get_permalink( $id );
	$path = $url ? str_replace( home_url(), '', $url ) : '';

	/* ほかのプランへ、すぐ行けるようにします
	   （2026/09/23 ユーザー指示「他のプランを編集したいのに、
	     上の階層（4点パック一覧ページ）にはいけないんだ」） */
	$list = get_posts( array(
		'post_type'      => 'ymkrf_product',
		'posts_per_page' => -1,
		'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
		'tax_query'      => array( array(
			'taxonomy' => 'ymkrf_product_cat', 'field' => 'slug', 'terms' => YMKRF_P4_CAT,
		) ),
	) );

	$others = array();
	foreach ( $list as $one ) {
		$others[] = array(
			'id'   => (int) $one->ID,
			'name' => get_the_title( $one ),
			'url'  => get_edit_post_link( $one->ID, '' ),
			'now'  => ( (int) $one->ID === $id ),
		);
	}

	$all = admin_url( 'edit.php?post_type=ymkrf_product&ymkrf_product_cat=' . YMKRF_P4_CAT );
	?>
	<style>
	  /* もどる ← （2026/09/23 ユーザー指示「矢印もっと太くして 背景は濃いグレーで」） */
	  /* もどる ←（2026/09/23 ユーザー指示「矢印サークルの中央にして」
	     文字の「←」だと字の下地のぶん中心がずれるので、絵（SVG）にしました） */
	  .ymkrf-back{
	    display:inline-flex;align-items:center;justify-content:center;
	    width:36px;height:36px;margin-right:12px;border-radius:50%;
	    background:#3c434a;color:#fff;text-decoration:none;
	    vertical-align:middle;line-height:0}
	  /* 「商品を編集」の文字と、高さをそろえます
	     （2026/09/23 ユーザー指示「商品を編集の文字と同じ高さにして、並べて」） */
	  .wp-heading-inline{vertical-align:middle}
	  .ymkrf-back svg{display:block}
	  .ymkrf-back:hover{background:#1d2327;color:#fff}
	  .ymkrf-back{
	    display:inline-flex;align-items:center;justify-content:center;
	    width:36px;height:36px;margin-right:12px;border-radius:50%;
	    background:#3c434a;color:#fff;text-decoration:none;
	    vertical-align:middle;line-height:0}
	  /* 「商品を編集」の文字と、高さをそろえます
	     （2026/09/23 ユーザー指示「商品を編集の文字と同じ高さにして、並べて」） */
	  .wp-heading-inline{vertical-align:middle}
	  .ymkrf-back svg{display:block}
	  .ymkrf-back:hover{background:#1d2327;color:#fff}
	  /* 見出し・URL・「商品を追加」を、一列にそろえます
	     （2026/09/23 ユーザー指示「URLと商品を追加の文字も横一列キレイにそろえて」） */
	  .ymkrf-p4url{
	    display:inline-flex;align-items:center;gap:8px;
	    margin-left:12px;padding:5px 14px;border-radius:999px;
	    background:#f6f7f7;border:1px solid #dcdcde;
	    font-size:12.5px;font-weight:400;color:#50575e;
	    vertical-align:middle;line-height:1.6}
	  .post-type-ymkrf_product .page-title-action{
	    vertical-align:middle;margin-left:12px;top:0}
	  .ymkrf-p4url code{background:none;padding:0;font-size:12.5px;color:#1d2327;font-weight:700}
	  .ymkrf-p4url a{text-decoration:none;font-size:12px}
	</style>
	<script>
	jQuery(function ($) {

	  var $h = $('.wp-heading-inline').first();
	  if (!$h.length) return;

	  /* 見出しの左に ←（2026/09/23 ユーザー指示「商品を編集の左横に←」） */
	  $h.before(
	    $('<a class="ymkrf-back"></a>').html('<svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M19 12H6"/><path d="M11 5 4 12l7 7"/></svg>')
	      .attr('href', <?php echo wp_json_encode( $all ); ?>)
	      .attr('title', '水まわり4点セットの一覧へもどる')
	      .attr('aria-label', '水まわり4点セットの一覧へもどる')
	  );

	  <?php if ( $path !== '' ) : ?>
	  $h.after(
	    $('<span class="ymkrf-p4url"></span>')
	      .append($('<span>URL</span>'))
	      .append($('<code></code>').text(<?php echo wp_json_encode( $path ); ?>))
	      .append($('<a target="_blank" rel="noopener">ページを見る</a>')
	        .attr('href', <?php echo wp_json_encode( $url ); ?>))
	  );
	  <?php endif; ?>

	});
	</script>
	<?php
} );


/** プランの説明（タイトルのすぐ下） */
function ymkrf_p4_box_lead( $post ) {

	$v = (string) get_post_meta( $post->ID, '_ymkrf_desc', true );
	?>
	<textarea name="_ymkrf_p4desc" rows="4" style="width:100%;line-height:1.8"
	  placeholder="例：はじめてのリフォームでも選びやすい、いちばん人気の組み合わせです。キッチンとお風呂は掃除のしやすさを重視しました。"><?php
	  echo esc_textarea( $v ); ?></textarea>
	<p class="description" style="margin-top:6px">
	  ページの見出しのすぐ下に出ます。空のときは、4点の商品名から自動で文を作ります。<br>
	  そのプランならではのことを1〜3行で書くと、検索にも強くなります。
	</p>
	<?php
}

/** 4点の中身 */
function ymkrf_p4_box_items( $post ) {

	wp_nonce_field( 'ymkrf_p4_save', 'ymkrf_p4_nonce' );
	?>
	<p class="ymkrf-note" style="font-size:13px;line-height:1.9;margin:0 0 12px">
	  キッチン・お風呂・トイレ・洗面台を、<b>登録済みの商品からえらぶだけ</b>です。<br>
	  <b>写真・メーカー・商品名・標準仕様は、えらんだ商品のものが自動で出ます。</b>
	  ここで写真を入れ直す必要はありません。商品側を直せば、このセットのページにも反映されます。
	</p>

	<table class="ymkrf-tbl">
	  <?php foreach ( ymkrf_p4_parts() as $part => $def ) :
	    list( $label, $cat ) = $def;
	    $key  = ymkrf_p4_key( $part );
	    $now  = (int) get_post_meta( $post->ID, $key, true );
	    $list = ymkrf_p4_choices( $cat );
	  ?>
	    <tr>
	      <th><?php echo esc_html( $label ); ?></th>
	      <td>
	        <?php if ( ! $list ) : ?>
	          <p style="color:#b32d2e;margin:0">
	            「<?php echo esc_html( $label ); ?>」の商品がまだ登録されていません。
	            先に商品を登録してください。
	          </p>
	        <?php else : ?>
	          <select name="<?php echo esc_attr( $key ); ?>" style="max-width:420px;width:100%">
	            <option value="">（えらんでください）</option>
	            <?php foreach ( $list as $id => $name ) : ?>
	              <option value="<?php echo (int) $id; ?>" <?php selected( $now, $id ); ?>>
	                <?php echo esc_html( $name ); ?>
	              </option>
	            <?php endforeach; ?>
	          </select>
	          <?php
	          /* えらんだ商品の、工事費込みの金額をよこに出します
	             （2026/09/18 ユーザー「選択する欄の横に、選択したアイテムの価格を表示できる？」） */
	          $p4one = 0;
	          if ( $now ) {
	            $p4one = (int) get_post_meta( $now, '_ymkrf_total', true );
	            if ( ! $p4one ) {
	              $p4one = (int) get_post_meta( $now, '_ymkrf_work', true )
	                     + (int) get_post_meta( $now, '_ymkrf_item', true );
	            }
	          }
	          ?>
	          <span class="ymkrf-p4-one" data-part="<?php echo esc_attr( $part ); ?>"
	                style="display:inline-block;min-width:140px;margin-left:12px;
	                       font-weight:700;color:#50575e;white-space:nowrap"><?php
	            echo $p4one ? esc_html( number_format( $p4one ) ) . ' 円（税込）' : ''; ?></span>

	        <?php endif; ?>
	      </td>
	    </tr>
	  <?php endforeach; ?>
	</table>
	<?php
}


/** 商品ID => 工事費込みの合計（自動計算に使います） */
function ymkrf_p4_totals() {

	$out = array();
	foreach ( ymkrf_p4_parts() as $def ) {
		foreach ( array_keys( ymkrf_p4_choices( $def[1] ) ) as $id ) {
			$t = (int) get_post_meta( $id, '_ymkrf_total', true );
			if ( ! $t ) {
				$t = (int) get_post_meta( $id, '_ymkrf_work', true )
				   + (int) get_post_meta( $id, '_ymkrf_item', true );
			}
			$out[ (string) $id ] = $t;
		}
	}
	return $out;
}

/** えらんだ4点の合計（工事費込み） */
function ymkrf_p4_sum( $post_id ) {

	$sum = 0;
	foreach ( array_keys( ymkrf_p4_parts() ) as $part ) {
		$id = (int) get_post_meta( $post_id, ymkrf_p4_key( $part ), true );
		if ( ! $id ) continue;
		$t = (int) get_post_meta( $id, '_ymkrf_total', true );
		if ( ! $t ) {
			$t = (int) get_post_meta( $id, '_ymkrf_work', true )
			   + (int) get_post_meta( $id, '_ymkrf_item', true );
		}
		$sum += $t;
	}
	return $sum;
}


/** セットの価格（プラン名は、いちばん上のタイトルです） */
function ymkrf_p4_box_price( $post ) {

	$now  = get_post_meta( $post->ID, '_ymkrf_p4now', true );
	$sum  = ymkrf_p4_sum( $post->ID );
	?>
	<table class="ymkrf-tbl">

	  <tr>
	    <th>通常価格（自動計算）</th>
	    <td>
	      <input type="text" id="ymkrf-p4-sum" value="<?php echo esc_attr( $sum ? number_format( $sum ) : '' ); ?>"
	             readonly tabindex="-1" style="background:#f0f0f1;color:#50575e;max-width:220px">
	      <span style="margin-left:8px">円（税込）</span>
	    </td>
	  </tr>

	  <tr>
	    <th>セット価格</th>
	    <td>
	      <?php /* 3けたごとの「,」を入れて見やすくします。
	               保存するときは数字だけにするので、「,」が入っていても大丈夫です */ ?>
	      <input type="text" inputmode="numeric" name="_ymkrf_p4now" id="ymkrf-p4-now"
	             value="<?php echo esc_attr( $now !== '' ? number_format( (int) $now ) : '' ); ?>"
	             placeholder="例：2,668,000" style="max-width:220px">
	      <span style="margin-left:8px">円（税込）</span>
	    </td>
	  </tr>

	</table>

	<script>
	/* 4点をえらびなおしたら、その場で総額を計算しなおします */
	jQuery(function ($) {

	  var TOTAL = <?php echo wp_json_encode( ymkrf_p4_totals() ); ?>;
	  var $out  = $('#ymkrf-p4-sum');
	  if (!$out.length) return;

	  function comma(n) { return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ','); }

	  function calc() {
	    var sum = 0;
	    $('select[name^="_ymkrf_p4_"]').each(function () {
	      var v = $(this).val();
	      var t = (v && TOTAL[v]) ? TOTAL[v] : 0;
	      sum += t;
	      /* えらんだ商品の金額を、そのプルダウンのよこに出します */
	      $(this).closest('td').find('.ymkrf-p4-one')
	             .text(t ? comma(t) + ' 円（税込）' : '');
	    });
	    $out.val(sum ? comma(sum) : '');
	  }

	  $(document).on('change', 'select[name^="_ymkrf_p4_"]', calc);
	  calc();

	  /* セット価格も、打ちながら「,」を入れます */
	  $(document).on('input', '#ymkrf-p4-now', function () {
	    var n = $(this).val().replace(/[^0-9]/g, '');
	    $(this).val(n ? comma(n) : '');
	  });
	});
	</script>
	<?php
}


/* ============================================================
   4. 保存
   ============================================================ */
add_action( 'save_post_ymkrf_product', function ( $post_id ) {

	if ( ! isset( $_POST['ymkrf_p4_nonce'] ) ||
	     ! wp_verify_nonce( sanitize_key( $_POST['ymkrf_p4_nonce'] ), 'ymkrf_p4_save' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	foreach ( array_keys( ymkrf_p4_parts() ) as $part ) {
		$key = ymkrf_p4_key( $part );
		update_post_meta( $post_id, $key, isset( $_POST[ $key ] ) ? (int) $_POST[ $key ] : 0 );
	}

	/* プラン名の欄は、画面から外しました（2026/09/18 ユーザー指示）。
	   プラン名は、いちばん上のタイトルを使います。 */

	/* プランの説明（2026/09/23 追加） */
	if ( isset( $_POST['_ymkrf_p4desc'] ) ) {
		$d = trim( sanitize_textarea_field( wp_unslash( $_POST['_ymkrf_p4desc'] ) ) );
		if ( $d !== '' ) update_post_meta( $post_id, '_ymkrf_desc', $d );
		else             delete_post_meta( $post_id, '_ymkrf_desc' );
	}

	if ( isset( $_POST['_ymkrf_p4now'] ) ) {
		update_post_meta( $post_id, '_ymkrf_p4now',
			preg_replace( '/[^0-9]/', '', (string) wp_unslash( $_POST['_ymkrf_p4now'] ) ) );
	}

	/* 通常価格は、えらんだ4点から自動で出します（手入力はありません） */
	update_post_meta( $post_id, '_ymkrf_p4was', ymkrf_p4_sum( $post_id ) );

	/* 「いちばん人気」の欄は、画面から外しました（2026/09/18 ユーザー指示「削除」）。
	   いま付いている印は、そのまま残します。
	   トップページのバナーの写真は、この印が付いたプランの4点から取ります。
	   付けかえたいときは、お知らせください。 */
}, 20 );


/* ============================================================
   5. ほかから使うための取り出し口
   ============================================================ */

/** 登録されているプランを、並び順で返します */
function ymkrf_p4_plans() {

	return get_posts( array(
		'post_type'      => 'ymkrf_product',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
		'tax_query'      => array( array(
			'taxonomy' => 'ymkrf_product_cat',
			'field'    => 'slug',
			'terms'    => YMKRF_P4_CAT,
		) ),
	) );
}

/** 「いちばん人気」のプラン。無ければ、いちばん上のプラン */
function ymkrf_p4_best() {
	$plans = ymkrf_p4_plans();
	if ( ! $plans ) return null;
	foreach ( $plans as $p ) {
		if ( get_post_meta( $p->ID, '_ymkrf_p4best', true ) === '1' ) return $p;
	}
	return $plans[0];
}

/**
 * プランの4点を返します。
 * 返すのは  部位のキー => array( 見出し, 商品の投稿, 商品ID )
 */
/**
 * プランの値段（セット価格・別々にしたときの合計・差額）
 * （2026/09/23 ユーザー指示。一覧のカードでも使うので、ここにまとめました）
 */
function ymkrf_p4_prices( $plan_id ) {

	$was = (int) get_post_meta( $plan_id, '_ymkrf_p4was', true );
	$now = (int) get_post_meta( $plan_id, '_ymkrf_p4now', true );

	/* 通常価格が入っていないときは、4点の合計から出します */
	if ( ! $was ) {
		foreach ( ymkrf_p4_items( $plan_id ) as $one ) {
			$t = (int) get_post_meta( $one[2], '_ymkrf_total', true );
			if ( ! $t ) {
				$t = (int) get_post_meta( $one[2], '_ymkrf_work', true )
				   + (int) get_post_meta( $one[2], '_ymkrf_item', true );
			}
			$was += $t;
		}
	}

	return array(
		'was' => $was,
		'now' => $now,
		'off' => ( $was && $now && $was > $now ) ? $was - $now : 0,
	);
}

function ymkrf_p4_items( $plan_id ) {

	$out = array();
	foreach ( ymkrf_p4_parts() as $part => $def ) {
		$id = (int) get_post_meta( $plan_id, ymkrf_p4_key( $part ), true );
		if ( ! $id ) continue;
		$p = get_post( $id );
		if ( ! $p || $p->post_type !== 'ymkrf_product' ) continue;
		$out[ $part ] = array( $def[0], $p, $id );
	}
	return $out;
}


/* ============================================================
   6. 4つのプランを用意します（1度だけ）
   ------------------------------------------------------------
   （2026/09/18 ユーザー指示
     「ダッシュボードの水まわり4点セットに4種類のプランを作ってください」）

   いまの4点セットのページ（ymkrf-pack4.php）と同じ
   プラン名・キャッチコピー・セット価格・4点の中身で作ります。
   すでに1件でも登録があるときは、何もしません。
   ============================================================ */
add_action( 'admin_init', function () {

	$ver = '2026-09-18a';
	if ( get_option( 'ymkrf_p4_seed' ) === $ver ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;
	if ( ! post_type_exists( 'ymkrf_product' ) ) return;

	$term = get_term_by( 'slug', YMKRF_P4_CAT, 'ymkrf_product_cat' );
	if ( ! $term || is_wp_error( $term ) ) return;

	/* すでに登録があるときは、さわりません */
	$has = get_posts( array(
		'post_type'      => 'ymkrf_product',
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'tax_query'      => array( array(
			'taxonomy' => 'ymkrf_product_cat',
			'field'    => 'slug',
			'terms'    => YMKRF_P4_CAT,
		) ),
	) );
	if ( $has ) { update_option( 'ymkrf_p4_seed', $ver, false ); return; }

	/* 商品のスラッグから、登録してある商品をさがします */
	$find = function ( $slug ) {
		$p = get_page_by_path( $slug, OBJECT, 'ymkrf_product' );
		return $p ? (int) $p->ID : 0;
	};

	$plans = array(
		array(
			'name'  => 'お財布に優しいプラン',
			'catch' => '価格を抑えた激安仕様',
			'now'   => 1628000,
			'best'  => false,
			'items' => array( 'kitchen' => 'v-style', 'bathroom' => 'ofuroa',
			                  'lavatory' => 'v1',     'toilet'   => 'amage-z' ),
		),
		array(
			'name'  => '間違いないスタンダードプラン',
			'catch' => '大好評の機能をパッケージ！',
			'now'   => 2018000,
			'best'  => false,
			'items' => array( 'kitchen' => 'rakuera', 'bathroom' => 'lidea-m',
			                  'lavatory' => 'j1',     'toilet'   => 'amage-z-premium' ),
		),
		array(
			'name'  => 'ヤマキシ1番人気ベストプラン',
			'catch' => 'ワンランク上のオススメ仕様！',
			'now'   => 2668000,
			'best'  => true,
			'items' => array( 'kitchen' => 'stedia', 'bathroom' => 'sazana-t',
			                  'lavatory' => 'fansio', 'toilet'  => 'alauno-s160' ),
		),
		array(
			'name'  => '主婦が憧れるプレミアムプラン',
			'catch' => 'こだわりの大満足仕様！',
			'now'   => 3918000,
			'best'  => false,
			'items' => array( 'kitchen' => 'richelle', 'bathroom' => 'selevia',
			                  'lavatory' => 'sakua',   'toilet'   => 'neorest-rs3' ),
		),
	);

	$made = 0;
	foreach ( $plans as $n => $pl ) {

		$id = wp_insert_post( array(
			'post_type'   => 'ymkrf_product',
			'post_title'  => $pl['name'],
			'post_status' => 'publish',
			'menu_order'  => $n + 1,
		) );
		if ( is_wp_error( $id ) || ! $id ) continue;

		wp_set_object_terms( $id, array( (int) $term->term_id ), 'ymkrf_product_cat' );

		/* プラン名は、タイトルをそのまま使います */
		update_post_meta( $id, '_ymkrf_catch',  $pl['catch'] );
		update_post_meta( $id, '_ymkrf_order',  $n + 1 );
		update_post_meta( $id, '_ymkrf_p4now',  $pl['now'] );
		if ( $pl['best'] ) update_post_meta( $id, '_ymkrf_p4best', '1' );

		foreach ( $pl['items'] as $part => $slug ) {
			update_post_meta( $id, ymkrf_p4_key( $part ), $find( $slug ) );
		}

		/* 通常価格は、えらんだ4点から自動で出します */
		update_post_meta( $id, '_ymkrf_p4was', ymkrf_p4_sum( $id ) );

		$made++;
	}

	update_option( 'ymkrf_p4_seed', $ver, false );
	if ( $made ) set_transient( 'ymkrf_p4_made', $made, 120 );
}, 12 );

add_action( 'admin_notices', function () {
	$made = get_transient( 'ymkrf_p4_made' );
	if ( ! $made ) return;
	delete_transient( 'ymkrf_p4_made' );
	$url = add_query_arg( array(
		'post_type' => 'ymkrf_product', 'ymkrf_product_cat' => YMKRF_P4_CAT,
	), admin_url( 'edit.php' ) );
	echo '<div class="notice notice-success is-dismissible"><p>'
	   . '水まわり4点セットのプランを <b>' . (int) $made . '件</b> 作りました。'
	   . '<a href="' . esc_url( $url ) . '">4点セットの一覧を開く</a>'
	   . '</p></div>';
} );


/* ============================================================
   7. 4点セットの一覧画面を、見やすく整えます
   ------------------------------------------------------------
   （2026/09/18 ユーザー指示
     「商品と書いてある左上の文字を、水まわり4点セット に変更」
     「商品追加はありませんので、水まわり4点セットに商品を追加 を削除」）

   プランは4つで固定なので、追加のボタンは出しません。
   ============================================================ */
add_action( 'admin_head-edit.php', function () {

	$s = get_current_screen();
	if ( ! $s || $s->post_type !== 'ymkrf_product' ) return;

	$cat = isset( $_GET['ymkrf_product_cat'] ) ? sanitize_title( wp_unslash( $_GET['ymkrf_product_cat'] ) ) : '';
	if ( $cat !== YMKRF_P4_CAT ) return;
	?>
	<style>
	  /* プランは4つで固定なので、「新規追加」は出しません */
	  .post-type-ymkrf_product .page-title-action{ display:none !important; }
	</style>
	<script>
	jQuery(function ($) {
	  /* 左上の見出しを「商品」から「水まわり4点セット」に変えます */
	  $('.wp-heading-inline').first().text('水まわり4点セット');
	});
	</script>
	<?php
} );


/* ============================================================
   8. プランの登録画面には、ふつうの商品の欄を出しません
   ------------------------------------------------------------
   プランで入れるのは、プラン名・4点の中身・セット価格だけです。
   グレードや標準工事費、扉カラーなどは使いません。
   画面に出ていると、どこに何を入れるのか分からなくなるので消します。
   ============================================================ */
add_action( 'add_meta_boxes', function () {

	if ( ! function_exists( 'ymkrf_product_current_cat' ) ) return;
	if ( ymkrf_product_current_cat( get_the_ID() ) !== YMKRF_P4_CAT ) return;

	remove_meta_box( 'ymkrf_product_basic', 'ymkrf_product', 'normal' );  /* 商品データ（基本） */
	remove_meta_box( 'ymkrf_product_catdiv', 'ymkrf_product', 'side' );   /* 商品カテゴリ */
	remove_meta_box( 'ymkrf_makerdiv',       'ymkrf_product', 'side' );   /* メーカー */
	remove_meta_box( 'ymkrf_shopdiv',        'ymkrf_product', 'side' );   /* 展示店舗 */

	/* カラー・取っ手の箱（2026/09/22 に1つにまとめたもの）も消します */
	remove_meta_box( 'ymkrf_product_colors', 'ymkrf_product', 'normal' );

	/* 何行でも増やせる欄（扉カラー・標準仕様など）も、ぜんぶ消します */
	if ( function_exists( 'ymkrf_product_repeaters' ) ) {
		foreach ( array_keys( ymkrf_product_repeaters() ) as $key ) {
			remove_meta_box( 'ymkrf_box' . $key, 'ymkrf_product', 'normal' );
		}
	}
}, 100 );

/* カテゴリの箱を消した画面に、目じるしを1つ置いておきます */
add_action( 'edit_form_after_title', function ( $post ) {
	if ( ! $post || $post->post_type !== 'ymkrf_product' ) return;
	if ( ! function_exists( 'ymkrf_product_current_cat' ) ) return;
	if ( ymkrf_product_current_cat( $post->ID ) !== YMKRF_P4_CAT ) return;
	echo '<input type="hidden" name="ymkrf_p4_keep" value="1">';
} );

/* 保存のときに、カテゴリが外れないようにします */
add_action( 'save_post_ymkrf_product', function ( $post_id ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;
	if ( ! isset( $_POST['ymkrf_p4_keep'] ) ) return;

	$term = get_term_by( 'slug', YMKRF_P4_CAT, 'ymkrf_product_cat' );
	if ( $term && ! is_wp_error( $term ) ) {
		wp_set_object_terms( $post_id, array( (int) $term->term_id ), 'ymkrf_product_cat' );
	}
}, 5 );


/* ============================================================
   9. アイキャッチを自動で作ります（4分割の写真）
   ------------------------------------------------------------
   （2026/09/18 ユーザー指示
     「アイキャッチも自動で4点、上段に（キッチン・お風呂）
       下段（トイレ・洗面台）で4分割した写真を作って入れて」）

   えらんだ4点の写真を、1枚にまとめます。
       ┌──────┬──────┐
       │キッチン│お風呂 │
       ├──────┼──────┤
       │トイレ │洗面台 │
       └──────┴──────┘
   4点をえらび直して「更新」すると、作り直します。

   ★商品側の写真を差し替えたときも、そのプランを1度「更新」すれば
     新しい写真で作り直します。
   ============================================================ */

/** 写真のファイルを読みこみます（jpg / png / gif / webp） */
function ymkrf_p4_load_img( $file ) {

	if ( ! $file || ! file_exists( $file ) ) return null;
	$info = @getimagesize( $file );
	if ( ! $info ) return null;

	switch ( $info[2] ) {
		case IMAGETYPE_JPEG: return @imagecreatefromjpeg( $file );
		case IMAGETYPE_PNG:  return @imagecreatefrompng( $file );
		case IMAGETYPE_GIF:  return @imagecreatefromgif( $file );
		case IMAGETYPE_WEBP:
			return function_exists( 'imagecreatefromwebp' ) ? @imagecreatefromwebp( $file ) : null;
	}
	return null;
}

/** その商品の写真のファイルの場所（大きすぎないものを選びます） */
function ymkrf_p4_img_file( $product_id ) {

	$att = (int) get_post_thumbnail_id( $product_id );
	if ( ! $att ) return '';

	$full = get_attached_file( $att );
	if ( ! $full ) return '';

	/* 「大」サイズがあれば、そちらを使います（重くならないように） */
	$mid = image_get_intermediate_size( $att, 'large' );
	if ( $mid && ! empty( $mid['path'] ) ) {
		$up = wp_get_upload_dir();
		$p  = trailingslashit( $up['basedir'] ) . $mid['path'];
		if ( file_exists( $p ) ) return $p;
	}
	return $full;
}

/**
 * 4分割の写真を作って、アイキャッチにします。
 * 作れなかったときは、何もしません（前の写真はそのままです）。
 */
function ymkrf_p4_make_thumb( $plan_id ) {

	if ( ! function_exists( 'imagecreatetruecolor' ) ) return false;   /* GDが無い環境 */

	/* 上段：キッチン・お風呂／下段：トイレ・洗面台 */
	$order = array( 'kitchen', 'bathroom', 'toilet', 'lavatory' );

	$ids = array();
	foreach ( $order as $part ) {
		$ids[] = (int) get_post_meta( $plan_id, ymkrf_p4_key( $part ), true );
	}
	if ( ! array_filter( $ids ) ) return false;   /* 1つもえらばれていない */

	/* 4点が変わっていないなら、作り直しません */
	$sig = implode( '-', $ids );
	if ( get_post_meta( $plan_id, '_ymkrf_p4sig', true ) === $sig
	     && has_post_thumbnail( $plan_id ) ) return false;

	$W = 1200; $H = 900; $CW = 600; $CH = 450; $PAD = 14;

	$canvas = imagecreatetruecolor( $W, $H );
	$white  = imagecolorallocate( $canvas, 255, 255, 255 );
	$line   = imagecolorallocate( $canvas, 232, 224, 220 );
	imagefilledrectangle( $canvas, 0, 0, $W, $H, $white );

	foreach ( $ids as $n => $pid ) {

		$cx = ( $n % 2 ) * $CW;          /* 左 or 右 */
		$cy = ( $n < 2 ) ? 0 : $CH;      /* 上 or 下 */

		if ( ! $pid ) continue;

		$src = ymkrf_p4_load_img( ymkrf_p4_img_file( $pid ) );
		if ( ! $src ) continue;

		$sw = imagesx( $src );
		$sh = imagesy( $src );
		if ( $sw < 1 || $sh < 1 ) { imagedestroy( $src ); continue; }

		/* 枠に収まるように、切り取らずに縮めます */
		$maxw = $CW - $PAD * 2;
		$maxh = $CH - $PAD * 2;
		$r    = min( $maxw / $sw, $maxh / $sh );
		$dw   = max( 1, (int) round( $sw * $r ) );
		$dh   = max( 1, (int) round( $sh * $r ) );
		$dx   = $cx + (int) round( ( $CW - $dw ) / 2 );
		$dy   = $cy + (int) round( ( $CH - $dh ) / 2 );

		imagecopyresampled( $canvas, $src, $dx, $dy, 0, 0, $dw, $dh, $sw, $sh );
		imagedestroy( $src );
	}

	/* 4分割の区切り線 */
	imagefilledrectangle( $canvas, $CW - 1, 0, $CW, $H, $line );
	imagefilledrectangle( $canvas, 0, $CH - 1, $W, $CH, $line );

	/* 保存さき。すでに作ってあれば、同じファイルに上書きします */
	$att_id = (int) get_post_meta( $plan_id, '_ymkrf_p4thumb', true );
	$path   = $att_id ? get_attached_file( $att_id ) : '';

	if ( ! $path ) {
		$up = wp_upload_dir();
		if ( ! empty( $up['error'] ) ) { imagedestroy( $canvas ); return false; }
		$path = trailingslashit( $up['path'] ) . 'pack4-' . $plan_id . '.jpg';
		$att_id = 0;
	}

	$ok = imagejpeg( $canvas, $path, 88 );
	imagedestroy( $canvas );
	if ( ! $ok ) return false;

	require_once ABSPATH . 'wp-admin/includes/image.php';

	if ( ! $att_id ) {
		$att_id = wp_insert_attachment( array(
			'post_mime_type' => 'image/jpeg',
			'post_title'     => get_the_title( $plan_id ) . '（4点セットの写真）',
			'post_status'    => 'inherit',
		), $path, $plan_id );
		if ( is_wp_error( $att_id ) || ! $att_id ) return false;
		update_post_meta( $plan_id, '_ymkrf_p4thumb', (int) $att_id );
	}

	wp_update_attachment_metadata( $att_id, wp_generate_attachment_metadata( $att_id, $path ) );

	update_post_meta( $att_id, '_wp_attachment_image_alt',
		get_the_title( $plan_id ) . 'の4点（キッチン・お風呂・トイレ・洗面台）' );

	set_post_thumbnail( $plan_id, $att_id );
	update_post_meta( $plan_id, '_ymkrf_p4sig', $sig );

	return true;
}

/* 保存のたびに、必要なら作り直します */
add_action( 'save_post_ymkrf_product', function ( $post_id ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;
	if ( ! function_exists( 'ymkrf_product_current_cat' ) ) return;
	if ( ymkrf_product_current_cat( $post_id ) !== YMKRF_P4_CAT ) return;

	ymkrf_p4_make_thumb( $post_id );
}, 30 );

/* すでにあるプランにも、まだ写真が無ければ作ります。
   （4点が変わっていないときは、何もしないので重くなりません） */
add_action( 'admin_init', function () {

	if ( ! current_user_can( 'manage_options' ) ) return;
	if ( ! post_type_exists( 'ymkrf_product' ) ) return;

	foreach ( ymkrf_p4_plans() as $p ) {
		ymkrf_p4_make_thumb( $p->ID );
	}
}, 14 );
