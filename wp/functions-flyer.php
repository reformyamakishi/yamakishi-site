<?php
/**
 * イベント・チラシ ─ リフォームヤマキシ
 *
 * 置き場所： wp-content/themes/ymkrf/inc/functions-flyer.php
 *            （functions.php から読み込みます）
 *
 * ── なぜこの作りにしたか ─────────────────────────────
 * いまのサイトのチラシページは、10店舗ぶんのチラシ（表・裏で約20枚）を
 * 1ページで全部読み込んでいました。そのため
 *   ・スマホだと表示までに時間がかかる
 *   ・自分の店のチラシがどこにあるのか分からない
 * という2つの困りごとが起きていました。
 *
 * そこで
 *   ① お店を先にえらんでいただき、そのお店のチラシだけを出す
 *      （画像は、えらばれたぶんしか読み込みません）
 *   ② チラシの中身を、サイトに登録ずみの商品からも読めるようにする
 *   ③ 管理画面から、画像2枚と期間を入れるだけで差し替えられるようにする
 * という形にしました。
 *
 * ── お店ごとにチラシが違うとき ───────────────────────
 * チラシの編集画面の右側「対象のお店」で、お店にチェックを入れます。
 *   ・どこにもチェックを入れない … 全店共通のチラシです
 *   ・お店にチェックを入れる　　　… そのお店だけのチラシです
 *
 * ページの出し分けは、こうなります。
 *   そのお店だけのチラシがある → そちらを出します（共通のものは出しません）
 *   そのお店だけのチラシがない → 全店共通のチラシを出します
 *
 * ── 掲載商品について ────────────────────────────
 * 「チラシに載せた商品」は、サイトに登録ずみの商品からえらぶだけです。
 * 価格や写真は商品ページのものをそのまま使うので、
 * 毎月の打ち込みは要りませんし、値段が古いままになることもありません。
 */
if ( ! defined( 'ABSPATH' ) ) exit;


/* ============================================================
   1. 投稿タイプ「イベント・チラシ」
   ------------------------------------------------------------
   1件ずつのページ（/flyer/xxxx/）は作りません。
   お客様がご覧になるのは /flyer/ の1ページだけです。
   ============================================================ */
add_action( 'init', function () {

	register_post_type( 'ymkrf_flyer', array(
		'label'  => 'イベント・チラシ',
		'labels' => array(
			'name'          => 'イベント・チラシ',
			'singular_name' => 'チラシ',
			'add_new'       => '新規追加',
			'add_new_item'  => 'チラシを新規追加',
			'edit_item'     => 'チラシを編集',
			'all_items'     => 'チラシ一覧',
			'search_items'  => 'チラシを検索',
		),
		'public'              => false,   /* 1件ずつのページは作りません */
		'show_ui'             => true,
		/* メニューは下の「4. ダッシュボードのメニュー」で自分で作ります。
		   お店をえらぶ画面をいちばん先に出したいためです。 */
		'show_in_menu'        => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'has_archive'         => false,
		'rewrite'             => false,
		'menu_icon'           => 'dashicons-tickets-alt',
		'supports'            => array( 'title', 'editor', 'page-attributes' ),
		'show_in_rest'        => false,
	) );

}, 6 );

/* 「順序」の欄の題名を「並び順」にします。
   1つのお店に何種類かチラシがあるとき、数の小さいほうが先に出ます。 */
add_filter( 'enter_title_here', function ( $t, $post ) {
	return ( $post && $post->post_type === 'ymkrf_flyer' ) ? 'チラシの名前（例：2026年10月 秋のリフォームフェア）' : $t;
}, 10, 2 );

/* 「対象のお店」は、商品・スタッフと同じ ymkrf_shop を使いまわします。
   お店を1か所で直せるようにするためです。
   ymkrf_shop は inc/functions-product.php で init のふつうの順番（10）に
   作られるので、ここはそのあと（11）で結びつけます。 */
add_action( 'init', function () {
	if ( taxonomy_exists( 'ymkrf_shop' ) ) {
		register_taxonomy_for_object_type( 'ymkrf_shop', 'ymkrf_flyer' );
	}
}, 11 );


/* 昔ながらの編集画面にします。
   ブロックエディターだと、右側の「チラシの中身」の欄が
   はしに埋もれて見つけにくくなるためです。 */
add_filter( 'use_block_editor_for_post_type', function ( $use, $type ) {
	return ( $type === 'ymkrf_flyer' ) ? false : $use;
}, 10, 2 );


/* ============================================================
   2. チラシのページで読み込むCSS
   ============================================================ */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! function_exists( 'ymkrf_is_flyer' ) || ! ymkrf_is_flyer() ) return;
	$dir = get_stylesheet_directory_uri();
	$ver = defined( 'YMKRF_VER' ) ? YMKRF_VER : null;
	/* 商品の札（.p-cat__card）をそのまま使うので product.css も読みます */
	wp_enqueue_style( 'ymkrf-lp',      $dir . '/assets/css/lp.css',      array( 'ymkrf-common', 'ymkrf-page' ), $ver );
	wp_enqueue_style( 'ymkrf-product', $dir . '/assets/css/product.css', array( 'ymkrf-page' ), $ver );
	wp_enqueue_style( 'ymkrf-flyer',   $dir . '/assets/css/flyer.css',   array( 'ymkrf-lp' ), $ver );
}, 20 );


/* ============================================================
   3. 編集画面の欄
   ============================================================ */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'ymkrf_flyer_main', 'チラシの画像と期間',
		'ymkrf_flyer_box_main', 'ymkrf_flyer', 'normal', 'high' );
	add_meta_box( 'ymkrf_flyer_prd', 'チラシに載せた商品',
		'ymkrf_flyer_box_prd', 'ymkrf_flyer', 'normal', 'default' );

	/* お店の欄は、そのままだと商品と同じ「展示店舗」という題名になってしまい、
	   並び順もばらばらです。チラシ用に作りなおします。 */
	remove_meta_box( 'ymkrf_shopdiv', 'ymkrf_flyer', 'side' );
	add_meta_box( 'ymkrf_flyer_shop', 'このチラシを出すお店',
		'ymkrf_flyer_box_shop', 'ymkrf_flyer', 'side', 'high' );
}, 20 );

/**
 * 「このチラシを出すお店」の欄。
 *
 * ・お店の一覧は inc/functions-shops.php の並び（県ごと）に合わせています
 * ・お店をえらぶ画面から「＋追加」で来たときは、そのお店にはじめから
 *   チェックが入っています（?ymkrf_shop=komathu）
 */
function ymkrf_flyer_box_shop( $post ) {
	wp_nonce_field( 'ymkrf_flyer_shop_save', 'ymkrf_flyer_shop_nonce' );

	$now = wp_get_object_terms( $post->ID, 'ymkrf_shop', array( 'fields' => 'slugs' ) );
	if ( is_wp_error( $now ) ) $now = array();

	/* 新規追加でお店の指定つきのときは、はじめからチェックを入れます */
	if ( ! $now && get_post_status( $post ) === 'auto-draft' && ! empty( $_GET['ymkrf_shop'] ) ) {
		$now = array( sanitize_key( wp_unslash( $_GET['ymkrf_shop'] ) ) );
	}

	$shops = function_exists( 'ymkrf_shops' ) ? ymkrf_shops() : array();
	$by_pref = array();
	foreach ( $shops as $sp ) $by_pref[ $sp['pref'] ][] = $sp;
	?>
	<div class="ymkrf-fl__shop">
	  <p class="ymkrf-fl__shopall">
	    <label>
	      <input type="checkbox" id="ymkrf-fl-shopnone" <?php checked( ! $now ); ?>>
	      <b>全店共通にする</b>
	    </label><br>
	    <span class="description">どのお店でも出ます。全店で同じチラシのときに使います。</span>
	  </p>

	  <div id="ymkrf-fl-shoplist" class="ymkrf-fl__shoplist<?php echo $now ? '' : ' is-off'; ?>">
	    <?php foreach ( $by_pref as $pref => $list ) : ?>
	      <p class="ymkrf-fl__shoppref"><?php echo esc_html( $pref ); ?></p>
	      <?php foreach ( $list as $sp ) : ?>
	        <label class="ymkrf-fl__shopitem">
	          <input type="checkbox" class="ymkrf-fl__shopchk" name="ymkrf_flyer_shop[]"
	                 value="<?php echo esc_attr( $sp['slug'] ); ?>"
	                 <?php checked( in_array( $sp['slug'], $now, true ) ); ?>>
	          <span><?php echo esc_html( $sp['name'] ); ?></span>
	        </label>
	      <?php endforeach; ?>
	    <?php endforeach; ?>
	  </div>

	  <p class="description ymkrf-fl__shophelp">
	    お店にチェックを入れると、そのお店のチラシになります。<br>
	    お客様のページでは、<b>そのお店のチラシ</b>と<b>全店共通のチラシ</b>の両方が出ます。
	  </p>
	</div>
	<?php
}

add_action( 'save_post_ymkrf_flyer', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! isset( $_POST['ymkrf_flyer_shop_nonce'] ) ||
	     ! wp_verify_nonce( $_POST['ymkrf_flyer_shop_nonce'], 'ymkrf_flyer_shop_save' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	$slugs = isset( $_POST['ymkrf_flyer_shop'] ) && is_array( $_POST['ymkrf_flyer_shop'] )
		? array_map( 'sanitize_key', wp_unslash( $_POST['ymkrf_flyer_shop'] ) )
		: array();

	/* 空にすると「全店共通」になります */
	wp_set_object_terms( $post_id, $slugs ? $slugs : null, 'ymkrf_shop', false );
} );

/** 画像を1枚えらぶ欄をひとつ出します */
function ymkrf_flyer_imgfield( $key, $label, $post_id, $note = '' ) {
	$id  = (int) get_post_meta( $post_id, $key, true );
	$url = $id ? wp_get_attachment_image_url( $id, 'medium' ) : '';
	$dom = 'ymkrf-fl-' . str_replace( '_ymkrf_flyer_', '', $key );   /* 例 ymkrf-fl-front */
	?>
	<div class="ymkrf-fl__img">
	  <p class="ymkrf-fl__lbl"><b><?php echo esc_html( $label ); ?></b></p>
	  <div class="ymkrf-fl__prev" id="<?php echo esc_attr( $dom ); ?>-prev">
	    <?php if ( $url ) : ?>
	      <img src="<?php echo esc_url( $url ); ?>" alt="">
	    <?php else : ?>
	      <span class="ymkrf-fl__none">まだ入っていません</span>
	    <?php endif; ?>
	  </div>
	  <input type="hidden" name="<?php echo esc_attr( $key ); ?>"
	         id="<?php echo esc_attr( $dom ); ?>" value="<?php echo esc_attr( $id ); ?>">
	  <p>
	    <button type="button" class="button ymkrf-fl__pick"
	            data-target="<?php echo esc_attr( $dom ); ?>"
	            data-title="<?php echo esc_attr( $label . 'をえらぶ' ); ?>">画像をえらぶ</button>
	    <button type="button" class="button-link ymkrf-fl__clear"
	            data-target="<?php echo esc_attr( $dom ); ?>">はずす</button>
	  </p>
	  <?php if ( $note ) : ?><p class="description"><?php echo esc_html( $note ); ?></p><?php endif; ?>
	</div>
	<?php
}

function ymkrf_flyer_box_main( $post ) {
	wp_nonce_field( 'ymkrf_flyer_save', 'ymkrf_flyer_nonce' );
	$start = (string) get_post_meta( $post->ID, '_ymkrf_flyer_start', true );
	$end   = (string) get_post_meta( $post->ID, '_ymkrf_flyer_end',   true );
	$catch = (string) get_post_meta( $post->ID, '_ymkrf_flyer_catch', true );
	$pdf   = (int)    get_post_meta( $post->ID, '_ymkrf_flyer_pdf',   true );
	?>
	<p>
	  <label for="ymkrf-fl-catch"><b>キャッチコピー</b></label><br>
	  <input type="text" class="widefat" id="ymkrf-fl-catch" name="_ymkrf_flyer_catch"
	         value="<?php echo esc_attr( $catch ); ?>"
	         placeholder="例：秋のリフォームフェア　水まわり4点まとめてお得！">
	  <span class="description">チラシの上に大きく出ます。空でもかまいません。</span>
	</p>

	<p>
	  <b>掲載期間</b><br>
	  <label>はじまり <input type="date" name="_ymkrf_flyer_start" value="<?php echo esc_attr( $start ); ?>"></label>
	  　〜
	  <label>おわり <input type="date" name="_ymkrf_flyer_end" value="<?php echo esc_attr( $end ); ?>"></label><br>
	  <span class="description">
	    おわりの日をすぎると、ページに出なくなります（消さなくて大丈夫です）。<br>
	    はじまりの日を空にすると、公開したその日から出ます。
	    おわりの日を空にすると、ずっと出つづけます。
	  </span>
	</p>

	<hr>

	<div class="ymkrf-fl__imgs">
	  <?php ymkrf_flyer_imgfield( '_ymkrf_flyer_front', 'チラシ 表面', $post->ID,
		'B4のたて・よこ、どちらでも大丈夫です。長いほうの辺が1600px以上のJPEGをおすすめします。' ); ?>
	  <?php ymkrf_flyer_imgfield( '_ymkrf_flyer_back', 'チラシ 裏面', $post->ID,
		'裏がないチラシのときは、空のままでかまいません。表面と同じ向きにしてください。' ); ?>
	</div>

	<hr>

	<p>
	  <b>PDF（ご希望の方がダウンロードできます）</b><br>
	  <input type="hidden" name="_ymkrf_flyer_pdf" id="ymkrf-fl-pdf" value="<?php echo esc_attr( $pdf ); ?>">
	  <span id="ymkrf-fl-pdf-name" class="description">
	    <?php echo $pdf ? esc_html( get_the_title( $pdf ) ) : 'まだ入っていません'; ?>
	  </span><br>
	  <button type="button" class="button ymkrf-fl__pickpdf">PDFをえらぶ</button>
	  <button type="button" class="button-link ymkrf-fl__clearpdf">はずす</button><br>
	  <span class="description">入れなくてもかまいません。入れると「チラシをPDFで見る」のボタンが出ます。</span>
	</p>

	<hr>
	<p class="description">
	  ★<b>本文（上の大きな入力欄）</b>には、チラシに書ききれなかったご案内や、
	  お店からのひとことを入れてください。空でもかまいません。<br>
	  ★<b>対象のお店</b>は、右側の「お店」の欄でえらびます。
	  どこにもチェックを入れなければ、全店共通のチラシになります。
	</p>
	<?php
}

function ymkrf_flyer_box_prd( $post ) {
	$ids = array_filter( array_map( 'intval',
		explode( ',', (string) get_post_meta( $post->ID, '_ymkrf_flyer_products', true ) ) ) );

	$cats = get_terms( array( 'taxonomy' => 'ymkrf_product_cat', 'hide_empty' => false ) );
	?>
	<p class="description">
	  チラシに載せた商品にチェックを入れてください。<br>
	  価格・写真は<b>商品ページのものをそのまま使います</b>ので、ここに値段を打ち込む必要はありません。
	  商品の値段を直せば、チラシのページの値段も自動で変わります。
	</p>

	<input type="hidden" name="_ymkrf_flyer_products" id="ymkrf-fl-prd" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>">

	<div class="ymkrf-fl__prd">
	<?php
	if ( ! is_wp_error( $cats ) ) :
		foreach ( (array) $cats as $ct ) :
			$q = new WP_Query( array(
				'post_type'      => 'ymkrf_product',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
				'tax_query'      => array( array(
					'taxonomy' => 'ymkrf_product_cat',
					'field'    => 'term_id',
					'terms'    => $ct->term_id,
				) ),
			) );
			if ( ! $q->have_posts() ) { wp_reset_postdata(); continue; }
			?>
			<div class="ymkrf-fl__prdgrp">
			  <p class="ymkrf-fl__prdttl"><?php echo esc_html( $ct->name ); ?></p>
			  <div class="ymkrf-fl__prdlist">
			  <?php while ( $q->have_posts() ) : $q->the_post(); $pid = get_the_ID(); ?>
			    <label class="ymkrf-fl__prditem">
			      <input type="checkbox" class="ymkrf-fl__prdchk" value="<?php echo (int) $pid; ?>"
			        <?php checked( in_array( $pid, $ids, true ) ); ?>>
			      <span><?php echo esc_html( get_the_title() ); ?></span>
			    </label>
			  <?php endwhile; ?>
			  </div>
			</div>
			<?php
			wp_reset_postdata();
		endforeach;
	endif;
	?>
	</div>
	<?php
}

add_action( 'save_post_ymkrf_flyer', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! isset( $_POST['ymkrf_flyer_nonce'] ) ||
	     ! wp_verify_nonce( $_POST['ymkrf_flyer_nonce'], 'ymkrf_flyer_save' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	foreach ( array( '_ymkrf_flyer_start', '_ymkrf_flyer_end', '_ymkrf_flyer_catch' ) as $k ) {
		update_post_meta( $post_id, $k,
			isset( $_POST[ $k ] ) ? sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) : '' );
	}
	foreach ( array( '_ymkrf_flyer_front', '_ymkrf_flyer_back', '_ymkrf_flyer_pdf' ) as $k ) {
		update_post_meta( $post_id, $k, isset( $_POST[ $k ] ) ? (int) $_POST[ $k ] : 0 );
	}

	$ids = isset( $_POST['_ymkrf_flyer_products'] )
		? array_filter( array_map( 'intval', explode( ',', (string) $_POST['_ymkrf_flyer_products'] ) ) )
		: array();
	update_post_meta( $post_id, '_ymkrf_flyer_products', implode( ',', array_unique( $ids ) ) );
} );


/* 編集画面の見た目と、画像えらび */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	$s = get_current_screen();
	if ( ! $s || $s->post_type !== 'ymkrf_flyer' ) return;
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) return;
	wp_enqueue_media();
} );

add_action( 'admin_head', function () {
	$s = get_current_screen();
	if ( ! $s || $s->post_type !== 'ymkrf_flyer' ) return;
	echo '<style>
	  .ymkrf-fl__imgs{display:flex;gap:20px;flex-wrap:wrap}
	  .ymkrf-fl__img{flex:1 1 260px;min-width:240px}
	  .ymkrf-fl__lbl{margin:0 0 6px}
	  .ymkrf-fl__prev{display:flex;align-items:center;justify-content:center;
	    min-height:150px;padding:8px;background:#f6f7f7;border:1px solid #dcdcde;border-radius:4px}
	  .ymkrf-fl__prev img{max-width:100%;max-height:230px;height:auto;display:block}
	  .ymkrf-fl__none{color:#a7aaad;font-size:12px}
	  .ymkrf-fl__prd{max-height:420px;overflow:auto;border:1px solid #dcdcde;
	    border-radius:4px;padding:10px;background:#fff}
	  .ymkrf-fl__prdttl{margin:12px 0 6px;padding-left:6px;font-weight:700;font-size:12.5px;
	    color:#646970;border-left:3px solid #fe3301;line-height:1.4}
	  .ymkrf-fl__prdgrp:first-child .ymkrf-fl__prdttl{margin-top:0}
	  .ymkrf-fl__prdlist{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:2px 10px}
	  .ymkrf-fl__prditem{display:flex;gap:6px;align-items:flex-start;font-size:12.5px;line-height:1.5;padding:2px 0}
	  .ymkrf-fl__shopall{margin:0 0 10px;padding-bottom:10px;border-bottom:1px solid #e5e5e5}
	  .ymkrf-fl__shoplist{max-height:260px;overflow:auto}
	  .ymkrf-fl__shoplist.is-off{opacity:.4;pointer-events:none}
	  .ymkrf-fl__shoppref{margin:8px 0 3px;padding-left:6px;font-size:11.5px;font-weight:700;
	    color:#646970;border-left:3px solid #fe3301;line-height:1.4}
	  .ymkrf-fl__shoplist p:first-child{margin-top:0}
	  .ymkrf-fl__shopitem{display:flex;gap:6px;align-items:center;font-size:13px;line-height:1.7;padding:1px 0}
	  .ymkrf-fl__shophelp{margin-top:10px}
	</style>';
} );

add_action( 'admin_footer', function () {
	$s = get_current_screen();
	if ( ! $s || $s->post_type !== 'ymkrf_flyer' ) return;
	if ( ! in_array( $s->base, array( 'post' ), true ) ) return;
	?>
	<script>
	jQuery(function ($) {

		/* 画像をえらぶ（表面・裏面） */
		var frames = {};
		$(document).on('click', '.ymkrf-fl__pick', function (e) {
			e.preventDefault();
			var dom = $(this).data('target'), ttl = $(this).data('title');
			if (!frames[dom]) {
				frames[dom] = wp.media({ title: ttl, library: { type: 'image' }, multiple: false,
					button: { text: 'この画像をつかう' } });
				frames[dom].on('select', function () {
					var m = frames[dom].state().get('selection').first().toJSON();
					var url = (m.sizes && m.sizes.medium) ? m.sizes.medium.url : m.url;
					$('#' + dom).val(m.id);
					$('#' + dom + '-prev').html($('<img>').attr('src', url));
				});
			}
			frames[dom].open();
		});
		$(document).on('click', '.ymkrf-fl__clear', function (e) {
			e.preventDefault();
			var dom = $(this).data('target');
			$('#' + dom).val('');
			$('#' + dom + '-prev').html('<span class="ymkrf-fl__none">まだ入っていません</span>');
		});

		/* PDFをえらぶ */
		var pdfFrame = null;
		$(document).on('click', '.ymkrf-fl__pickpdf', function (e) {
			e.preventDefault();
			if (!pdfFrame) {
				pdfFrame = wp.media({ title: 'PDFをえらぶ',
					library: { type: 'application/pdf' }, multiple: false,
					button: { text: 'このPDFをつかう' } });
				pdfFrame.on('select', function () {
					var m = pdfFrame.state().get('selection').first().toJSON();
					$('#ymkrf-fl-pdf').val(m.id);
					$('#ymkrf-fl-pdf-name').text(m.title || m.filename);
				});
			}
			pdfFrame.open();
		});
		$(document).on('click', '.ymkrf-fl__clearpdf', function (e) {
			e.preventDefault();
			$('#ymkrf-fl-pdf').val('');
			$('#ymkrf-fl-pdf-name').text('まだ入っていません');
		});

		/* 掲載商品のチェックを、かくれた欄にまとめます */
		function syncPrd() {
			var a = [];
			$('.ymkrf-fl__prdchk:checked').each(function () { a.push($(this).val()); });
			$('#ymkrf-fl-prd').val(a.join(','));
		}
		$(document).on('change', '.ymkrf-fl__prdchk', syncPrd);
		syncPrd();

		/* 「全店共通にする」と、お店のチェックの連動 */
		var $none = $('#ymkrf-fl-shopnone'), $list = $('#ymkrf-fl-shoplist');
		function drawShop() {
			$list.toggleClass('is-off', $none.prop('checked'));
		}
		$none.on('change', function () {
			if ($none.prop('checked')) $('.ymkrf-fl__shopchk').prop('checked', false);
			drawShop();
		});
		$(document).on('change', '.ymkrf-fl__shopchk', function () {
			if ($('.ymkrf-fl__shopchk:checked').length) $none.prop('checked', false);
			drawShop();
		});
		drawShop();
	});
	</script>
	<?php
} );


/* ============================================================
   3-b. ダッシュボードのメニュー（お店をえらぶ画面をいちばん先に）
   ------------------------------------------------------------
   「イベント・チラシ」を押すと、まずお店が11個ならびます。
   お店を押すと、そのお店のチラシだけが一覧で出ます。
   1つのお店に何種類あってもかまいません。
   ============================================================ */
add_action( 'admin_menu', function () {

	add_menu_page(
		'イベント・チラシ', 'イベント・チラシ', 'edit_posts',
		'ymkrf-flyer-shops', 'ymkrf_flyer_shops_page', 'dashicons-tickets-alt', 8
	);

	add_submenu_page( 'ymkrf-flyer-shops', 'お店からえらぶ', 'お店からえらぶ',
		'edit_posts', 'ymkrf-flyer-shops', 'ymkrf_flyer_shops_page' );

	add_submenu_page( 'ymkrf-flyer-shops', 'チラシ一覧（ぜんぶ）', 'チラシ一覧（ぜんぶ）',
		'edit_posts', 'edit.php?post_type=ymkrf_flyer' );

	add_submenu_page( 'ymkrf-flyer-shops', 'チラシを新規追加', '新規追加',
		'edit_posts', 'post-new.php?post_type=ymkrf_flyer' );
} );

/* チラシを編集しているあいだも、このメニューが開いたままになるようにします */
add_filter( 'parent_file', function ( $parent ) {
	$s = get_current_screen();
	if ( $s && $s->post_type === 'ymkrf_flyer' ) return 'ymkrf-flyer-shops';
	return $parent;
} );
add_filter( 'submenu_file', function ( $sub ) {
	$s = get_current_screen();
	if ( $s && $s->post_type === 'ymkrf_flyer' ) {
		return ( $s->base === 'post' && $s->action === 'add' )
			? 'post-new.php?post_type=ymkrf_flyer'
			: 'edit.php?post_type=ymkrf_flyer';
	}
	return $sub;
} );

/** お店をえらぶ画面 */
function ymkrf_flyer_shops_page() {

	$shops = function_exists( 'ymkrf_shops' ) ? ymkrf_shops() : array();

	/* お店ごとに「掲載中／期間外・下書き」を数えます */
	$all = get_posts( array(
		'post_type'      => 'ymkrf_flyer',
		'post_status'    => array( 'publish', 'draft', 'pending', 'future' ),
		'posts_per_page' => -1,
		'no_found_rows'  => true,
	) );

	$count  = array();   /* slug => array( now, other ) */
	$common = array( 'now' => 0, 'other' => 0 );

	foreach ( $all as $p ) {
		$live = ( $p->post_status === 'publish' && ymkrf_flyer_is_now( $p->ID ) );
		$k    = $live ? 'now' : 'other';

		$ts = get_the_terms( $p->ID, 'ymkrf_shop' );
		if ( ! $ts || is_wp_error( $ts ) ) {
			$common[ $k ]++;
			continue;
		}
		foreach ( $ts as $t ) {
			if ( ! isset( $count[ $t->slug ] ) ) $count[ $t->slug ] = array( 'now' => 0, 'other' => 0 );
			$count[ $t->slug ][ $k ]++;
		}
	}

	$list_url = function ( $slug ) {
		return admin_url( 'edit.php?post_type=ymkrf_flyer' . ( $slug ? '&ymkrf_shop=' . rawurlencode( $slug ) : '&ymkrf_flyer_common=1' ) );
	};
	$new_url = function ( $slug ) {
		return admin_url( 'post-new.php?post_type=ymkrf_flyer' . ( $slug ? '&ymkrf_shop=' . rawurlencode( $slug ) : '' ) );
	};

	/* 1枚のカードを出します */
	$card = function ( $name, $slug, $c, $note = '' ) use ( $list_url, $new_url ) {
		?>
		<div class="ymkrf-fs__card<?php echo $slug ? '' : ' ymkrf-fs__card--common'; ?>">
		  <p class="ymkrf-fs__name"><?php echo esc_html( $name ); ?></p>
		  <?php if ( $note ) : ?><p class="ymkrf-fs__note"><?php echo esc_html( $note ); ?></p><?php endif; ?>
		  <p class="ymkrf-fs__cnt">
		    <?php if ( $c['now'] ) : ?>
		      <span class="ymkrf-fs__now">掲載中 <?php echo (int) $c['now']; ?>件</span>
		    <?php else : ?>
		      <span class="ymkrf-fs__zero">掲載中のチラシなし</span>
		    <?php endif; ?>
		    <?php if ( $c['other'] ) : ?>
		      <span class="ymkrf-fs__other">ほか <?php echo (int) $c['other']; ?>件（下書き・期間外）</span>
		    <?php endif; ?>
		  </p>
		  <p class="ymkrf-fs__btns">
		    <a class="button" href="<?php echo esc_url( $list_url( $slug ) ); ?>"><?php
		      echo $slug ? 'このお店のチラシを見る' : '共通のチラシを見る'; ?></a>
		    <a class="button button-primary" href="<?php echo esc_url( $new_url( $slug ) ); ?>">＋ 追加</a>
		  </p>
		</div>
		<?php
	};
	?>
	<div class="wrap ymkrf-fs">
	  <h1>イベント・チラシ</h1>

	  <p class="ymkrf-fs__lead">
	    お店をえらんでください。そのお店のチラシだけが出ます。<br>
	    1つのお店に<b>何種類あってもかまいません</b>（月に1〜3種類ある、といった使い方ができます）。
	    チラシは<b>表面・裏面の2枚</b>で1件です。
	  </p>

	  <h2 class="ymkrf-fs__h2">全店共通</h2>
	  <div class="ymkrf-fs__grid">
	    <?php $card( '全店共通のチラシ', '', $common, 'どのお店でも出ます。全店で同じチラシのときに使います。' ); ?>
	  </div>

	  <?php
	  $by_pref = array();
	  foreach ( $shops as $sp ) $by_pref[ $sp['pref'] ][] = $sp;
	  foreach ( $by_pref as $pref => $list ) : ?>
	    <h2 class="ymkrf-fs__h2"><?php echo esc_html( $pref ); ?></h2>
	    <div class="ymkrf-fs__grid">
	      <?php foreach ( $list as $sp ) :
	        $c = isset( $count[ $sp['slug'] ] ) ? $count[ $sp['slug'] ] : array( 'now' => 0, 'other' => 0 );
	        $card( $sp['name'], $sp['slug'], $c, ! empty( $sp['soon'] ) ? '準備中のお店です' : '' );
	      endforeach; ?>
	    </div>
	  <?php endforeach; ?>

	  <div class="ymkrf-fs__help">
	    <p><b>入れかたのめやす</b></p>
	    <ul>
	      <li>チラシ1件につき、<b>表面と裏面の画像2枚</b>と<b>掲載期間</b>を入れます。</li>
	      <li>B4のたて・よこ、どちらでも大丈夫です。ページの並べ方は自動で変わります。</li>
	      <li>掲載期間のおわりの日をすぎると、自動でページから消えます（削除は不要です）。</li>
	      <li>お客様のページでは、<b>そのお店のチラシ</b>と<b>全店共通のチラシ</b>の両方が出ます。</li>
	      <li>1つのお店に何種類かあるときは、編集画面の右下「ページ属性」の<b>順序</b>で並び順を決められます（数の小さいほうが先）。</li>
	    </ul>
	  </div>
	</div>

	<style>
	  .ymkrf-fs__lead{max-width:820px;font-size:13.5px;line-height:1.9}
	  .ymkrf-fs__h2{margin:26px 0 10px;padding-left:9px;font-size:15px;
	    border-left:4px solid #fe3301;line-height:1.5}
	  .ymkrf-fs__grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(268px,1fr));gap:12px}
	  .ymkrf-fs__card{padding:14px 16px;background:#fff;border:1px solid #dcdcde;border-radius:6px}
	  .ymkrf-fs__card--common{border-color:#fe3301;background:#fff8f5}
	  .ymkrf-fs__name{margin:0;font-size:15px;font-weight:700;line-height:1.4}
	  .ymkrf-fs__note{margin:3px 0 0;font-size:11.5px;color:#787878;line-height:1.5}
	  .ymkrf-fs__cnt{margin:8px 0 12px;font-size:12.5px;line-height:1.6}
	  .ymkrf-fs__cnt span{display:block}
	  .ymkrf-fs__now{color:#00782a;font-weight:700}
	  .ymkrf-fs__zero{color:#a7aaad}
	  .ymkrf-fs__other{color:#787878}
	  .ymkrf-fs__btns{margin:0;display:flex;gap:6px;flex-wrap:wrap}
	  .ymkrf-fs__help{margin-top:32px;padding:14px 18px;background:#fffbe6;
	    border:1px solid #f0dc9a;border-radius:6px;max-width:860px;font-size:13px;line-height:1.85}
	  .ymkrf-fs__help ul{margin:6px 0 0 18px;list-style:disc}
	</style>
	<?php
}

/* 「全店共通のチラシを見る」を押したとき、お店の付いていないチラシだけを出します */
add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() || ! $q->is_main_query() ) return;
	if ( $q->get( 'post_type' ) !== 'ymkrf_flyer' ) return;
	if ( empty( $_GET['ymkrf_flyer_common'] ) ) return;

	$ids = get_terms( array(
		'taxonomy' => 'ymkrf_shop', 'hide_empty' => false, 'fields' => 'ids',
	) );
	if ( is_wp_error( $ids ) || ! $ids ) return;

	$q->set( 'tax_query', array( array(
		'taxonomy' => 'ymkrf_shop',
		'field'    => 'term_id',
		'terms'    => $ids,
		'operator' => 'NOT IN',
	) ) );
} );


/* ============================================================
   4. 管理画面の一覧
   ============================================================ */
add_filter( 'manage_ymkrf_flyer_posts_columns', function ( $cols ) {
	$new = array();
	if ( isset( $cols['cb'] ) )    $new['cb']    = $cols['cb'];
	if ( isset( $cols['title'] ) ) $new['title'] = $cols['title'];
	$new['ymkrf_fshop'] = '対象のお店';
	$new['ymkrf_fterm'] = '掲載期間';
	$new['ymkrf_fnow']  = 'いまの状態';
	$new['ymkrf_ford']  = '並び順';
	$new['date']        = '登録日';
	return $new;
}, 20 );

/* 一覧のいちばん上に「お店からえらぶ画面にもどる」を出します */
add_action( 'admin_notices', function () {
	$s = get_current_screen();
	if ( ! $s || $s->id !== 'edit-ymkrf_flyer' ) return;

	$name = '';
	if ( ! empty( $_GET['ymkrf_shop'] ) ) {
		$t = get_term_by( 'slug', sanitize_key( wp_unslash( $_GET['ymkrf_shop'] ) ), 'ymkrf_shop' );
		if ( $t && ! is_wp_error( $t ) ) $name = $t->name;
	} elseif ( ! empty( $_GET['ymkrf_flyer_common'] ) ) {
		$name = '全店共通';
	}
	?>
	<div class="notice notice-info" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
	  <p style="margin:8px 0">
	    <?php if ( $name ) : ?>
	      <b><?php echo esc_html( $name ); ?></b>のチラシを出しています。
	    <?php else : ?>
	      すべてのお店のチラシを出しています。
	    <?php endif; ?>
	  </p>
	  <p style="margin:8px 0">
	    <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ymkrf-flyer-shops' ) ); ?>">お店からえらぶ画面へ</a>
	  </p>
	</div>
	<?php
} );

add_action( 'manage_ymkrf_flyer_posts_custom_column', function ( $col, $post_id ) {
	$none = '<span style="color:#a7aaad">—</span>';
	switch ( $col ) {
		case 'ymkrf_fshop':
			$ts = get_the_terms( $post_id, 'ymkrf_shop' );
			if ( ! $ts || is_wp_error( $ts ) ) { echo '<b>全店共通</b>'; break; }
			$n = array();
			foreach ( $ts as $t ) $n[] = $t->name;
			echo esc_html( implode( '／', $n ) );
			break;
		case 'ymkrf_fterm':
			$s = (string) get_post_meta( $post_id, '_ymkrf_flyer_start', true );
			$e = (string) get_post_meta( $post_id, '_ymkrf_flyer_end', true );
			if ( $s === '' && $e === '' ) { echo $none; break; }
			echo esc_html( ( $s !== '' ? $s : '（いつでも）' ) . ' 〜 ' . ( $e !== '' ? $e : '（いつまでも）' ) );
			break;
		case 'ymkrf_fnow':
			if ( get_post_status( $post_id ) !== 'publish' ) {
				echo '<span style="color:#a7aaad">下書き</span>'; break;
			}
			echo ymkrf_flyer_is_now( $post_id )
				? '<span style="color:#00a32a;font-weight:700">● 掲載中</span>'
				: '<span style="color:#a7aaad">期間外</span>';
			break;
		case 'ymkrf_ford':
			$o = (int) get_post_field( 'menu_order', $post_id );
			echo $o ? (int) $o : $none;
			break;
	}
}, 10, 2 );

/* 一覧を「並び順 → 新しい順」にします。
   1つのお店に何種類かあるとき、お客様のページと同じ順番で見えます。 */
add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() || ! $q->is_main_query() ) return;
	if ( $q->get( 'post_type' ) !== 'ymkrf_flyer' ) return;
	if ( $q->get( 'orderby' ) ) return;   /* 見出しを押して並べかえたときはそのまま */
	$q->set( 'orderby', array( 'menu_order' => 'ASC', 'date' => 'DESC' ) );
} );

add_action( 'admin_head', function () {
	$s = get_current_screen();
	if ( ! $s || $s->id !== 'edit-ymkrf_flyer' ) return;
	echo '<style>
	  .column-ymkrf_fshop{width:190px}
	  .column-ymkrf_fterm{width:210px}
	  .column-ymkrf_fnow{width:100px}
	  .column-ymkrf_ford{width:70px}
	</style>';
} );


/* ============================================================
   5. 便利な関数
   ============================================================ */

/** いま掲載中の期間かどうか */
if ( ! function_exists( 'ymkrf_flyer_is_now' ) ) :
function ymkrf_flyer_is_now( $post_id ) {
	$today = current_time( 'Y-m-d' );
	$s = trim( (string) get_post_meta( $post_id, '_ymkrf_flyer_start', true ) );
	$e = trim( (string) get_post_meta( $post_id, '_ymkrf_flyer_end',   true ) );
	if ( $s !== '' && $today < $s ) return false;
	if ( $e !== '' && $today > $e ) return false;
	return true;
}
endif;

/**
 * そのお店に出すチラシを返します。
 *
 * $slug … お店のスラッグ（例 komathu）。空のときは全店共通のものだけ。
 *
 * ★2026/09/03 ユーザー確認：1つのお店に1〜3種類ある月もあるとのことなので、
 *   「そのお店のチラシ」と「全店共通のチラシ」の両方を返します。
 *   お店のチラシが先、そのあとに全店共通です。
 *
 * 並びは、編集画面の「ページ属性 → 順序」の小さいほうが先です。
 * 同じ順序のときは、新しく登録したほうが先になります。
 */
if ( ! function_exists( 'ymkrf_flyers_for' ) ) :
function ymkrf_flyers_for( $slug = '' ) {

	$all = get_posts( array(
		'post_type'      => 'ymkrf_flyer',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		'no_found_rows'  => true,
	) );

	$mine   = array();
	$common = array();

	foreach ( $all as $p ) {
		if ( ! ymkrf_flyer_is_now( $p->ID ) ) continue;

		$ts = get_the_terms( $p->ID, 'ymkrf_shop' );
		$slugs = array();
		if ( $ts && ! is_wp_error( $ts ) ) {
			foreach ( $ts as $t ) $slugs[] = $t->slug;
		}

		if ( ! $slugs ) {
			$common[] = $p;                                 /* 全店共通 */
		} elseif ( $slug !== '' && in_array( $slug, $slugs, true ) ) {
			$mine[] = $p;                                   /* このお店のもの */
		}
	}

	return array_merge( $mine, $common );
}
endif;

/** チラシ1件を、ページで使いやすい形にほどきます */
if ( ! function_exists( 'ymkrf_flyer_data' ) ) :
function ymkrf_flyer_data( $post ) {
	$id = is_object( $post ) ? $post->ID : (int) $post;

	$img = function ( $key ) use ( $id ) {
		$aid = (int) get_post_meta( $id, $key, true );
		if ( ! $aid ) return null;
		$big = wp_get_attachment_image_src( $aid, 'full' );
		$mid = wp_get_attachment_image_src( $aid, 'large' );
		if ( ! $big ) return null;
		return array(
			'full' => $big[0],
			'src'  => $mid ? $mid[0] : $big[0],
			'w'    => $mid ? $mid[1] : $big[1],
			'h'    => $mid ? $mid[2] : $big[2],
		);
	};

	$pdf = (int) get_post_meta( $id, '_ymkrf_flyer_pdf', true );

	return array(
		'id'    => $id,
		'title' => get_the_title( $id ),
		'catch' => (string) get_post_meta( $id, '_ymkrf_flyer_catch', true ),
		'start' => (string) get_post_meta( $id, '_ymkrf_flyer_start', true ),
		'end'   => (string) get_post_meta( $id, '_ymkrf_flyer_end',   true ),
		'front' => $img( '_ymkrf_flyer_front' ),
		'back'  => $img( '_ymkrf_flyer_back' ),
		'pdf'   => $pdf ? wp_get_attachment_url( $pdf ) : '',
		'body'  => trim( (string) get_post_field( 'post_content', $id ) ),
		'prd'   => array_filter( array_map( 'intval',
			explode( ',', (string) get_post_meta( $id, '_ymkrf_flyer_products', true ) ) ) ),
	);
}
endif;

/** 「2026年10月1日（水）〜10月31日（金）」のような書き方にします */
if ( ! function_exists( 'ymkrf_flyer_term_text' ) ) :
function ymkrf_flyer_term_text( $start, $end ) {
	$w = array( '日', '月', '火', '水', '木', '金', '土' );
	$fmt = function ( $ymd, $with_year = true ) use ( $w ) {
		$t = strtotime( $ymd );
		if ( ! $t ) return '';
		return ( $with_year ? date_i18n( 'Y年', $t ) : '' )
		     . date_i18n( 'n月j日', $t ) . '（' . $w[ (int) date_i18n( 'w', $t ) ] . '）';
	};
	$start = trim( (string) $start );
	$end   = trim( (string) $end );

	if ( $start === '' && $end === '' ) return '';
	if ( $start === '' ) return $fmt( $end ) . 'まで';
	if ( $end === '' )   return $fmt( $start ) . 'から';

	/* 同じ年なら、うしろの年は省きます */
	$same = date_i18n( 'Y', strtotime( $start ) ) === date_i18n( 'Y', strtotime( $end ) );
	return $fmt( $start ) . '〜' . $fmt( $end, ! $same );
}
endif;
