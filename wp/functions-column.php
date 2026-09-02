<?php
/**
 * コラム（お役立ち情報） ─ リフォームヤマキシ
 *
 * 置き場所： wp-content/themes/ymkrf/inc/functions-column.php
 *            （functions.php から読み込みます）
 *
 * ・ダッシュボードに「コラム」というメニューが増えます。
 * ・分類は商品と同じ「商品カテゴリ（キッチン／お風呂 …）」を使います。
 *   記事にキッチンを付けると、キッチンのページに自動で並びます。
 * ・URL は /column/<記事のスラッグ>/ 、一覧は /column/ です。
 */
if ( ! defined( 'ABSPATH' ) ) exit;


/* ============================================================
   1. 投稿タイプ「コラム」
   ============================================================ */
add_action( 'init', function () {

	register_post_type( 'ymkrf_column', array(
		'label'         => 'コラム',
		'labels'        => array(
			'name'          => 'コラム',
			'singular_name' => 'コラム',
			'add_new'       => '新規追加',
			'add_new_item'  => 'コラムを新規追加',
			'edit_item'     => 'コラムを編集',
			'all_items'     => 'コラム一覧',
			'search_items'  => 'コラムを検索',
		),
		'public'        => true,
		'has_archive'   => 'column',
		'menu_icon'     => 'dashicons-edit-page',
		'menu_position' => 6,
		'rewrite'       => array( 'slug' => 'column', 'with_front' => false ),
		'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
		'show_in_rest'  => true,
	) );

	/* 商品と同じカテゴリ（キッチン／お風呂／トイレ …）を使えるようにします。
	   新しく分類を作らないのは、増やすほど managing がややこしくなるためです。 */
	if ( taxonomy_exists( 'ymkrf_product_cat' ) ) {
		register_taxonomy_for_object_type( 'ymkrf_product_cat', 'ymkrf_column' );
	}
}, 5 );


/* 投稿タイプを足したあと、一度だけURLの設定を作り直します */
add_action( 'init', function () {
	if ( get_option( 'ymkrf_column_rewrite_ver' ) === '1' ) return;
	flush_rewrite_rules( false );
	update_option( 'ymkrf_column_rewrite_ver', '1' );
}, 100 );


/* ============================================================
   2. カテゴリの絞り込み（/products/kitchen/ の一覧には商品だけを出す）
      分類を商品と共用しているため、分類ページの本体の検索からは
      コラムを外しておきます。
   ============================================================ */
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) return;
	if ( ! $q->is_tax( 'ymkrf_product_cat' ) ) return;
	$q->set( 'post_type', 'ymkrf_product' );
} );


/* ============================================================
   3. コラムページでも product.css を読み込みます
   ============================================================ */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_singular( 'ymkrf_column' ) && ! is_post_type_archive( 'ymkrf_column' ) ) return;
	wp_enqueue_style( 'ymkrf-product',
		get_stylesheet_directory_uri() . '/assets/css/product.css',
		array( 'ymkrf-common', 'ymkrf-page' ), defined( 'YMKRF_VER' ) ? YMKRF_VER : null );
}, 20 );


/* ============================================================
   4. 一覧を出すための関数
      $slug   … 商品カテゴリのスラッグ（'kitchen' など／空なら全部）
      $number … 何件出すか
   ============================================================ */
if ( ! function_exists( 'ymkrf_column_query' ) ) :
function ymkrf_column_query( $slug = '', $number = 3 ) {
	$args = array(
		'post_type'           => 'ymkrf_column',
		'posts_per_page'      => (int) $number,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	if ( $slug ) {
		$args['tax_query'] = array( array(
			'taxonomy' => 'ymkrf_product_cat',
			'field'    => 'slug',
			'terms'    => $slug,
		) );
	}
	return new WP_Query( $args );
}
endif;


/* ============================================================
   5. コラムのカード1枚分を出力します
      一覧ページ・カテゴリページの両方から使います。
   ============================================================ */
if ( ! function_exists( 'ymkrf_column_card' ) ) :
function ymkrf_column_card() {
	$terms = get_the_terms( get_the_ID(), 'ymkrf_product_cat' );
	$tag   = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
	?>
	<a class="p-col__card" href="<?php the_permalink(); ?>">
		<div class="p-col__ph">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
			<?php endif; ?>
			<?php if ( $tag ) : ?><span class="p-col__tag"><?php echo esc_html( $tag ); ?></span><?php endif; ?>
		</div>
		<div class="p-col__body">
			<time class="p-col__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
				<?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?>
			</time>
			<h3 class="p-col__title"><?php the_title(); ?></h3>
			<p class="p-col__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 60, '…' ) ); ?></p>
			<span class="p-col__more">くわしく読む</span>
		</div>
	</a>
	<?php
}
endif;


/* ============================================================
   5-b. お役立ち情報のいちばん上に、いつも出しておくページ

        コラムの記事とは別に、ずっと置いておきたい読みものです。
        いまは「給湯器・エコキュートの選び方」だけで、
        給湯器とエコキュートの2つのカテゴリに出しています。
        （2026/09/02 ユーザー指示）

   ★ほかの分類にも足したいときは、下の $pin に1つ足してください。
     'cats' に書いた分類のページで、いちばん先頭に出ます。
   ============================================================ */
if ( ! function_exists( 'ymkrf_column_pinned' ) ) :
function ymkrf_column_pinned( $slug ) {

	$pin = array(
		array(
			'cats'  => array( 'boiler', 'ecocute' ),
			'url'   => home_url( '/products/boiler-guide/' ),
			'tag'   => '選び方',
			'title' => '給湯器・エコキュートの選び方',
			'text'  => 'うちはどのタイプ？　号数やタンクの大きさは？　'
			         . 'いつ替えればいい？　はじめてのお取り替えでも迷わないように、順番にご説明します。',
			'img'   => 'assets/img/guide/ecocute.jpg',
			'alt'   => '',
		),
	);

	$out = array();
	foreach ( $pin as $p ) {
		if ( ! in_array( $slug, (array) $p['cats'], true ) ) continue;
		$out[] = $p;
	}
	return $out;
}
endif;

/* 固定のカードを1枚出します。コラムのカードと同じ見た目です。 */
if ( ! function_exists( 'ymkrf_column_pincard' ) ) :
function ymkrf_column_pincard( $p ) {
	$dir = get_stylesheet_directory_uri();
	?>
	<a class="p-col__card p-col__card--pin" href="<?php echo esc_url( $p['url'] ); ?>">
		<div class="p-col__ph">
			<?php if ( ! empty( $p['img'] ) ) : ?>
				<img src="<?php echo esc_url( $dir . '/' . ltrim( $p['img'], '/' ) ); ?>"
				     alt="<?php echo esc_attr( $p['alt'] ); ?>" loading="lazy" decoding="async">
			<?php endif; ?>
			<?php if ( ! empty( $p['tag'] ) ) : ?>
				<span class="p-col__tag"><?php echo esc_html( $p['tag'] ); ?></span>
			<?php endif; ?>
		</div>
		<div class="p-col__body">
			<h3 class="p-col__title"><?php echo esc_html( $p['title'] ); ?></h3>
			<p class="p-col__excerpt"><?php echo esc_html( $p['text'] ); ?></p>
			<span class="p-col__more">くわしく読む</span>
		</div>
	</a>
	<?php
}
endif;


/* ============================================================
   6. カテゴリページの下に出す「お役立ち情報」ブロック
   ============================================================ */
if ( ! function_exists( 'ymkrf_column_section' ) ) :
function ymkrf_column_section( $slug, $catname, $number = 3 ) {

	$q = ymkrf_column_query( $slug, $number );

	/* いちばん上にいつも出しておくカード（選び方のページなど）。
	   その分だけコラムの数を減らして、全体の枚数は変えません。 */
	$pins = function_exists( 'ymkrf_column_pinned' ) ? ymkrf_column_pinned( $slug ) : array();
	$rest = max( 0, $number - count( $pins ) );

	/* 固定のカードだけがあって、コラムの記事が無いとき */
	if ( $pins && ! $q->have_posts() ) {
		wp_reset_postdata();
		?>
		<section class="l-section l-section--soft" id="column">
			<div class="l-wrap">
				<div class="c-head">
					<span class="c-head__en">COLUMN</span>
					<h2 class="c-head__title"><?php echo esc_html( $catname ); ?>リフォームお役立ち情報</h2>
				</div>
				<div class="p-col__cards">
					<?php foreach ( $pins as $p ) ymkrf_column_pincard( $p ); ?>
				</div>
			</div>
		</section>
		<?php
		return;
	}

	/* 記事が1件も無いとき。
	   お客様には何も出しませんが、ログイン中のスタッフには
	   「ここに出ます」という案内を表示して、迷わないようにしています。 */
	if ( ! $q->have_posts() ) {
		wp_reset_postdata();
		if ( ! current_user_can( 'edit_posts' ) ) return;
		?>
		<section class="l-section l-section--soft" id="column">
			<div class="l-wrap">
				<div class="c-head">
				<span class="c-head__en">COLUMN</span>
				<h2 class="c-head__title"><?php echo esc_html( $catname ); ?>リフォームお役立ち情報</h2>
			</div>
				<div class="p-col__placeholder">
					<p><b>この場所に、コラムが新しい順で<?php echo (int) $number; ?>件並びます。</b></p>
					<p>
						ダッシュボードの「コラム」から記事を追加し、
						<b>商品カテゴリで「<?php echo esc_html( $catname ); ?>」にチェック</b>してください。<br>
						記事が1件でも公開されると、ここが自動で記事一覧に変わります。
					</p>
					<p class="p-col__placeholder__note">
						※このご案内は、ログイン中のスタッフにだけ見えています。お客様には表示されません。
					</p>
					<p>
						<a class="p-col__all" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=ymkrf_column' ) ); ?>">
							コラムを追加する
						</a>
					</p>
				</div>
			</div>
		</section>
		<?php
		return;
	}

	$more = get_post_type_archive_link( 'ymkrf_column' );
	if ( $slug && $more ) $more = add_query_arg( 'ymkrf_product_cat', $slug, $more );
	?>
	<section class="l-section l-section--soft" id="column">
		<div class="l-wrap">
			<div class="c-head">
				<span class="c-head__en">COLUMN</span>
				<h2 class="c-head__title"><?php echo esc_html( $catname ); ?>リフォームお役立ち情報</h2>
			</div>
			<div class="p-col__cards">
				<?php foreach ( $pins as $p ) ymkrf_column_pincard( $p ); ?>
				<?php $shown = 0;
				while ( $q->have_posts() ) : $q->the_post();
					if ( $shown >= $rest ) break;
					ymkrf_column_card(); $shown++;
				endwhile; ?>
			</div>

			<?php if ( $more ) : ?>
				<p class="p-col__allwrap">
					<a class="p-col__all" href="<?php echo esc_url( $more ); ?>">
						<?php echo esc_html( $catname ); ?>リフォームコラム一覧へ
					</a>
				</p>
			<?php endif; ?>
		</div>
	</section>
	<?php
	wp_reset_postdata();
}
endif;


/* ============================================================
   9. 書いた人（スタッフ）
      「スタッフブログ」を別に作らず、このコラムを読みものの入れ物に
      1本化したため、記事に書いた人の顔と名前を出せるようにしています。
   ============================================================ */

/* 昔ながらの編集画面にします（右の「書いた人」が埋もれないように） */
add_filter( 'use_block_editor_for_post_type', function ( $use, $type ) {
	return ( $type === 'ymkrf_column' ) ? false : $use;
}, 10, 2 );

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'ymkrf_column_writer', '書いた人',
		'ymkrf_column_writer_box', 'ymkrf_column', 'side', 'high' );
} );

function ymkrf_column_writer_box( $post ) {
	wp_nonce_field( 'ymkrf_column_save', 'ymkrf_column_nonce' );
	$cur = (int) get_post_meta( $post->ID, '_ymkrf_staff', true );

	$list = function_exists( 'ymkrf_staff_list' ) ? ymkrf_staff_list() : array();
	/* すでにえらばれている人が一覧に無いときも、消えないように足します */
	if ( $cur ) {
		$has = false;
		foreach ( $list as $st ) { if ( (int) $st->ID === $cur ) { $has = true; break; } }
		if ( ! $has ) {
			$sp = get_post( $cur );
			if ( $sp && $sp->post_type === 'ymkrf_staff' ) $list[] = $sp;
		}
	}
	?>
	<?php if ( $list ) : ?>
		<select name="_ymkrf_staff" style="width:100%">
			<option value="0">（出さない）</option>
			<?php foreach ( $list as $st ) :
				$shop = function_exists( 'ymkrf_staff_shop_name' ) ? ymkrf_staff_shop_name( $st->ID ) : ''; ?>
				<option value="<?php echo (int) $st->ID; ?>" <?php selected( $cur, (int) $st->ID ); ?>>
					<?php echo esc_html( get_the_title( $st ) . ( $shop ? '（' . $shop . '）' : '' ) ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			記事の下に、<b>顔写真と名前</b>が出ます。空（出さない）でもかまいません。<br>
			名前と顔写真は「スタッフ」で登録してください。
		</p>
	<?php else : ?>
		<p class="description">スタッフがまだ登録されていません。</p>
	<?php endif; ?>
	<?php
}

add_action( 'save_post_ymkrf_column', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! isset( $_POST['ymkrf_column_nonce'] ) ||
	     ! wp_verify_nonce( $_POST['ymkrf_column_nonce'], 'ymkrf_column_save' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;
	if ( isset( $_POST['_ymkrf_staff'] ) ) {
		update_post_meta( $post_id, '_ymkrf_staff', (int) $_POST['_ymkrf_staff'] );
	}
} );

/** 記事の下に出す「書いた人」の枠。書いた人がいなければ何も出しません。 */
if ( ! function_exists( 'ymkrf_column_writer' ) ) :
function ymkrf_column_writer( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	$sid = (int) get_post_meta( $post_id, '_ymkrf_staff', true );
	if ( ! $sid ) return;
	$sp = get_post( $sid );
	/* 退職などで非公開にした人は出しません（リンク先が無くなるため） */
	if ( ! $sp || $sp->post_type !== 'ymkrf_staff' || $sp->post_status !== 'publish' ) return;

	$name = trim( (string) get_the_title( $sp ) );
	if ( $name === '' ) return;
	$shop  = function_exists( 'ymkrf_staff_shop_name' ) ? (string) ymkrf_staff_shop_name( $sid ) : '';
	$role  = trim( (string) get_post_meta( $sid, '_ymkrf_staff_role', true ) );
	$thumb = get_the_post_thumbnail_url( $sid, 'medium' );
	?>
	<div class="p-colwriter">
	  <?php if ( $thumb ) : ?>
	    <img class="p-colwriter__ph" src="<?php echo esc_url( $thumb ); ?>"
	         width="88" height="88" alt="" loading="lazy" decoding="async">
	  <?php endif; ?>
	  <div class="p-colwriter__body">
	    <p class="p-colwriter__lab">この記事を書いた人</p>
	    <p class="p-colwriter__name">
	      <a href="<?php echo esc_url( get_permalink( $sid ) ); ?>"><?php echo esc_html( $name ); ?></a>
	    </p>
	    <?php if ( $shop !== '' || $role !== '' ) : ?>
	      <p class="p-colwriter__shop"><?php
	        echo esc_html( trim( $shop . ( $role !== '' ? '　' . $role : '' ) ) ); ?></p>
	    <?php endif; ?>
	  </div>
	</div>
	<?php
}
endif;
