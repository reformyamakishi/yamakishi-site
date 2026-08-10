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
   6. カテゴリページの下に出す「お役立ち情報」ブロック
   ============================================================ */
if ( ! function_exists( 'ymkrf_column_section' ) ) :
function ymkrf_column_section( $slug, $catname, $number = 3 ) {

	$q = ymkrf_column_query( $slug, $number );

	/* 記事が1件も無いとき。
	   お客様には何も出しませんが、ログイン中のスタッフには
	   「ここに出ます」という案内を表示して、迷わないようにしています。 */
	if ( ! $q->have_posts() ) {
		wp_reset_postdata();
		if ( ! current_user_can( 'edit_posts' ) ) return;
		?>
		<section class="l-section l-section--soft" id="column">
			<div class="l-wrap">
				<h2 class="p-prd__bar"><?php echo esc_html( $catname ); ?>リフォームお役立ち情報</h2>
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
			<h2 class="p-prd__bar"><?php echo esc_html( $catname ); ?>リフォームお役立ち情報</h2>
			<p class="p-col__lead">
				はじめての<?php echo esc_html( $catname ); ?>リフォームで迷いやすいところを、
				ヤマキシのスタッフがかみくだいてご説明します。
			</p>

			<div class="p-col__cards">
				<?php while ( $q->have_posts() ) : $q->the_post(); ymkrf_column_card(); endwhile; ?>
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
