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

}, 5 );

/* 商品と同じカテゴリ（キッチン／お風呂／トイレ …）を、コラムでも使えるようにします。
   新しく分類を作らないのは、増やすほど管理がややこしくなるためです。

   ★ここは あとの順番（20）で動かします。
     商品カテゴリそのものが作られるのが、この下の順番だからです。
     前は 5 の中に書いていて、まだ分類が無いうちに動いていたため、
     コラムの編集画面に「商品カテゴリ」の枠が出ていませんでした
     （2026/09/24 ユーザー指摘「コラムに商品カテゴリで『IH・コンロ』がない」）。 */
add_action( 'init', function () {
	if ( taxonomy_exists( 'ymkrf_product_cat' ) && post_type_exists( 'ymkrf_column' ) ) {
		register_taxonomy_for_object_type( 'ymkrf_product_cat', 'ymkrf_column' );
	}
}, 20 );


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
			<?php
			/* 一覧のカードは、直した記事なら更新日を見せます
			   （2026/09/25 ユーザー指示。新しい記事として気づいてもらうため） */
			$ymkrf_cd  = function_exists( 'ymkrf_column_dates' ) ? ymkrf_column_dates() : array( 'pub' => get_the_date( 'Y-m-d H:i:s' ), 'upd' => '' );
			$ymkrf_ct  = strtotime( $ymkrf_cd['upd'] !== '' ? $ymkrf_cd['upd'] : $ymkrf_cd['pub'] );
			$ymkrf_cis = ( $ymkrf_cd['upd'] !== '' );
			?>
			<time class="p-col__date<?php echo $ymkrf_cis ? ' p-col__date--upd' : ''; ?>"
			      datetime="<?php echo esc_attr( date_i18n( 'c', $ymkrf_ct ) ); ?>">
				<?php echo esc_html( date_i18n( 'Y.m.d', $ymkrf_ct ) ); ?><?php
				if ( $ymkrf_cis ) echo '<small>更新</small>'; ?>
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

/* 個人ではなく「広報担当」として出すときの目じるし（スタッフIDのかわりの数字） */
if ( ! defined( 'YMKRF_WRITER_PR' ) ) define( 'YMKRF_WRITER_PR', -1 );

/**
 * 執筆者の値を、しまえる形にそろえます。
 *   スタッフ … 投稿ID（数字）
 *   広報     … -1
 *   店舗     … 'shop:shinkaga' のような文字
 */
function ymkrf_column_writer_clean( $v ) {
	$v = trim( (string) $v );
	if ( preg_match( '#^shop:[a-z0-9_-]+$#', $v ) ) return $v;
	return (string) (int) $v;
}

/** 店舗の英字を返します（店舗でなければ空） */
function ymkrf_column_writer_shop( $v ) {
	$v = (string) $v;
	return ( strpos( $v, 'shop:' ) === 0 ) ? substr( $v, 5 ) : '';
}

/** プルダウンに出す、お店のえらび肢 */
function ymkrf_column_shop_options() {
	$out = array();
	if ( ! function_exists( 'ymkrf_shops' ) ) return $out;
	foreach ( ymkrf_shops() as $s ) {
		if ( empty( $s['slug'] ) || empty( $s['name'] ) ) continue;
		$out[ 'shop:' . $s['slug'] ] = $s['name'];
	}
	return $out;
}

/** 英字から店舗の情報を引きます */
function ymkrf_column_shop_info( $slug ) {
	if ( ! function_exists( 'ymkrf_shops' ) ) return null;
	foreach ( ymkrf_shops() as $s ) {
		if ( isset( $s['slug'] ) && $s['slug'] === $slug ) return $s;
	}
	return null;
}

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'ymkrf_column_writer', '執筆者',
		'ymkrf_column_writer_box', 'ymkrf_column', 'side', 'high' );
} );

function ymkrf_column_writer_box( $post ) {
	wp_nonce_field( 'ymkrf_column_save', 'ymkrf_column_nonce' );
	$cur = ymkrf_column_writer_clean( get_post_meta( $post->ID, '_ymkrf_staff', true ) );

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

	/* 「スタッフ」の一覧と同じ順番にそろえます。
	   本部 → 工事部 → 各店舗、同じ所属なら役職の高い順です。 */
	$list = ymkrf_column_writer_sort( $list );

	/* 所属ごとにまとめて出します（本部が先頭にきます） */
	$groups = array();
	foreach ( $list as $st ) {
		$shop = function_exists( 'ymkrf_staff_shop_name' )
		      ? trim( (string) ymkrf_staff_shop_name( $st->ID ) ) : '';
		if ( $shop === '' ) $shop = 'その他';
		if ( ! isset( $groups[ $shop ] ) ) $groups[ $shop ] = array();
		$groups[ $shop ][] = $st;
	}
	?>
	<?php if ( $list ) : ?>
		<select name="_ymkrf_staff" style="width:100%">
			<option value="0">（出さない）</option>
			<?php /* 個人名を出さないとき用。旧ブログの「広報担当」と同じあつかいです */ ?>
			<option value="<?php echo (int) YMKRF_WRITER_PR; ?>"
				<?php selected( $cur, (string) YMKRF_WRITER_PR ); ?>>広報</option>
			<optgroup label="お店から">
				<?php foreach ( ymkrf_column_shop_options() as $val => $lab ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>"
						<?php selected( $cur, $val ); ?>><?php echo esc_html( $lab ); ?></option>
				<?php endforeach; ?>
			</optgroup>
			<?php foreach ( $groups as $shop => $members ) : ?>
				<optgroup label="<?php echo esc_attr( $shop ); ?>">
					<?php foreach ( $members as $st ) :
						$role = trim( (string) get_post_meta( $st->ID, '_ymkrf_staff_role', true ) ); ?>
						<option value="<?php echo (int) $st->ID; ?>" <?php selected( $cur, (string) $st->ID ); ?>>
							<?php echo esc_html( get_the_title( $st ) . ( $role !== '' ? '（' . $role . '）' : '' ) ); ?>
						</option>
					<?php endforeach; ?>
				</optgroup>
			<?php endforeach; ?>
		</select>
	<?php else : ?>
		<p class="description">スタッフがまだ登録されていません。</p>
	<?php endif; ?>
	<?php
}

/**
 * 執筆者のえらぶ順番。「スタッフ」の一覧と同じにそろえます。
 *   所属（本部 → 工事部 → 各店舗）→ 役職の高い順 → 並び順 → 名前
 */
function ymkrf_column_writer_sort( $list ) {

	/* 本部は0番なので、「空のとき」だけ900にします（0を900にしないこと） */
	$num = function ( $post_id, $key ) {
		$v = get_post_meta( $post_id, $key, true );
		return ( $v === '' || $v === null ) ? 900 : (int) $v;
	};

	$key = function ( $st ) use ( $num ) {
		return array(
			$num( $st->ID, '_ymkrf_staff_shoprank' ),
			$num( $st->ID, '_ymkrf_staff_rank' ),
			(int) $st->menu_order,
			(string) $st->post_title,
		);
	};

	usort( $list, function ( $a, $b ) use ( $key ) {
		$ka = $key( $a ); $kb = $key( $b );
		for ( $i = 0; $i < 3; $i++ ) {
			if ( $ka[ $i ] !== $kb[ $i ] ) return $ka[ $i ] <=> $kb[ $i ];
		}
		return strcmp( $ka[3], $kb[3] );
	} );

	return $list;
}

add_action( 'save_post_ymkrf_column', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! isset( $_POST['ymkrf_column_nonce'] ) ||
	     ! wp_verify_nonce( $_POST['ymkrf_column_nonce'], 'ymkrf_column_save' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;
	if ( isset( $_POST['_ymkrf_staff'] ) ) {
		update_post_meta( $post_id, '_ymkrf_staff',
			ymkrf_column_writer_clean( wp_unslash( $_POST['_ymkrf_staff'] ) ) );
	}
} );

/** 記事の下に出す「書いた人」の枠。書いた人がいなければ何も出しません。 */
if ( ! function_exists( 'ymkrf_column_writer' ) ) :
function ymkrf_column_writer( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	$raw = ymkrf_column_writer_clean( get_post_meta( $post_id, '_ymkrf_staff', true ) );

	/* お店から。お店の名前と、そのお店の特徴を出します */
	$shopslug = ymkrf_column_writer_shop( $raw );
	if ( $shopslug !== '' ) {
		$sp = ymkrf_column_shop_info( $shopslug );
		if ( ! $sp ) return;
		$dir = get_stylesheet_directory();
		$uri = get_stylesheet_directory_uri();
		$ph  = '';
		if ( empty( $sp['nophoto'] ) ) {
			foreach ( array( 'webp', 'jpg' ) as $ext ) {
				if ( file_exists( $dir . '/assets/img/shops/' . $shopslug . '.' . $ext ) ) {
					$ph = $uri . '/assets/img/shops/' . $shopslug . '.' . $ext;
					break;
				}
			}
		}
		?>
		<div class="p-colwriter p-colwriter--shop">
		  <?php if ( $ph !== '' ) : ?>
		    <img class="p-colwriter__ph" src="<?php echo esc_url( $ph ); ?>"
		         width="88" height="88" alt="" loading="lazy" decoding="async"
		         <?php if ( ! empty( $sp['pos'] ) ) : ?>style="object-position:<?php
		           echo esc_attr( $sp['pos'] ); ?>"<?php endif; ?>>
		  <?php endif; ?>
		  <div class="p-colwriter__body">
		    <p class="p-colwriter__lab">この記事に該当するお店</p>
		    <p class="p-colwriter__name">
		      <a href="<?php echo esc_url( home_url( '/shops/#' . $shopslug ) ); ?>"><?php
		        echo esc_html( $sp['name'] ); ?></a>
		    </p>
		    <?php if ( ! empty( $sp['feature'] ) ) : ?>
		      <p class="p-colwriter__word"><?php echo esc_html( $sp['feature'] ); ?></p>
		    <?php endif; ?>
		  </div>
		</div>
		<?php
		return;
	}

	$sid = (int) $raw;
	if ( ! $sid ) return;

	/* 個人名を出さない「広報」のとき。
	   イベントやお店のできごとなど、社内から発信する記事に使います。
	   ★文言を変えたいときは、下の2行を直してください。 */
	if ( $sid === (int) YMKRF_WRITER_PR ) {
		?>
		<div class="p-colwriter p-colwriter--pr">
		  <div class="p-colwriter__body">
		    <p class="p-colwriter__lab">執筆</p>
		    <p class="p-colwriter__name">広報</p>
		    <p class="p-colwriter__word">地域イベントやヤマキシの活動情報をリアルタイムで発信しています。気になるイベントがありましたら、ぜひチェックしてみてください！</p>
		  </div>
		</div>
		<?php
		return;
	}

	$sp = get_post( $sid );
	/* 退職などで非公開にした人は出しません（リンク先が無くなるため） */
	if ( ! $sp || $sp->post_type !== 'ymkrf_staff' || $sp->post_status !== 'publish' ) return;

	$name = trim( (string) get_the_title( $sp ) );
	if ( $name === '' ) return;
	$shop  = function_exists( 'ymkrf_staff_shop_name' ) ? (string) ymkrf_staff_shop_name( $sid ) : '';
	$role  = trim( (string) get_post_meta( $sid, '_ymkrf_staff_role', true ) );
	$thumb = get_the_post_thumbnail_url( $sid, 'medium' );

	/* スタッフ紹介に入れてある「趣味」と「ひとこと」も出します
	   （2026/09/10 ユーザー指示）。空のときは、その行ごと出しません。 */
	$hobby = trim( (string) get_post_meta( $sid, '_ymkrf_staff_hobby', true ) );
	$word  = trim( (string) get_post_meta( $sid, '_ymkrf_staff_word',  true ) );
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

	    <?php if ( $hobby !== '' ) : ?>
	      <p class="p-colwriter__hobby">
	        <span class="p-colwriter__tag">趣味</span><?php echo esc_html( $hobby ); ?>
	      </p>
	    <?php endif; ?>

	    <?php if ( $word !== '' ) : ?>
	      <p class="p-colwriter__word"><?php echo nl2br( esc_html( $word ) ); ?></p>
	    <?php endif; ?>
	  </div>
	</div>
	<?php
}
endif;



/* ============================================================
   管理画面のコラム一覧
   「日付」→「掲載日時」に、そのうしろに「状態」をならべます
   ============================================================ */

add_filter( 'manage_ymkrf_column_posts_columns', function ( $cols ) {

	/* 列のならび（2026/09/24 ユーザー指示
	     「一覧の確認チェック欄、一番左に持ってきて」
	     「その次にタイトル　商品カテゴリ　投稿者　状態　掲載日時」）

	     ☑ ／ 確認 ／ タイトル ／ 商品カテゴリ ／ 執筆者 ／ 状態 ／ 掲載日時

	   ※「確認」は inc/functions-check.php があとの順番で先頭に置きなおします。 */

	unset( $cols['date'] );   /* もとの「日付」は使いません */

	$new = array();
	if ( isset( $cols['cb'] ) ) $new['cb'] = $cols['cb'];
	if ( isset( $cols['title'] ) ) $new['title'] = $cols['title'];

	/* 商品カテゴリ（ワードプレスが作る列をそのまま使います） */
	if ( isset( $cols['taxonomy-ymkrf_product_cat'] ) ) {
		$new['taxonomy-ymkrf_product_cat'] = '商品カテゴリ';
	}

	$new['ymkrf_writer'] = '執筆者';
	$new['ymkrf_status'] = '状態';
	$new['ymkrf_pub']    = '掲載日時';
	/* 更新日（2026/09/25 ユーザー指示
	   「ダッシュボードの一覧にも、更新日を掲載日時の横に入れて」）。
	   リライトしたものだけ日付が入り、まだの記事は「—」です。 */
	$new['ymkrf_upd']    = '更新日';

	/* 上でならべていない列（ほかのプラグインが足したものなど）は、うしろに付けます */
	foreach ( $cols as $key => $label ) {
		if ( ! isset( $new[ $key ] ) ) $new[ $key ] = $label;
	}

	return $new;
} );

add_action( 'manage_ymkrf_column_posts_custom_column', function ( $col, $post_id ) {

	$p = get_post( $post_id );
	if ( ! $p ) return;

	/* ---- 掲載日時 ---- */
	if ( $col === 'ymkrf_pub' ) {
		if ( $p->post_date === '0000-00-00 00:00:00' ) { echo '—'; return; }
		$t = strtotime( $p->post_date );
		echo '<span style="display:block;color:#1d2327;font-weight:700;'
		   . 'font-size:14px;line-height:1.35">'
		   . esc_html( date_i18n( 'Y/m/d', $t ) ) . '</span>';
		echo '<span style="color:#3c434a;font-size:13px">'
		   . esc_html( date_i18n( 'H:i', $t ) ) . '</span>';
		return;
	}

	/* ---- 更新日（リライトした日）----
	   （2026/09/25 ユーザー指示「更新日を掲載日時の横に入れて」）
	   公開中のコラムの 題名か本文が変わって保存されたときだけ入ります。 */
	if ( $col === 'ymkrf_upd' ) {
		$d = function_exists( 'ymkrf_column_dates' )
			? ymkrf_column_dates( $post_id ) : array( 'upd' => '' );
		if ( empty( $d['upd'] ) ) {
			echo '<span style="color:#a7aaad">—</span>';
			return;
		}
		$u = strtotime( $d['upd'] );
		echo '<span style="display:block;color:#b32d2e;font-weight:700;'
		   . 'font-size:14px;line-height:1.35">'
		   . esc_html( date_i18n( 'Y/m/d', $u ) ) . '</span>';
		echo '<span style="color:#3c434a;font-size:13px">'
		   . esc_html( date_i18n( 'H:i', $u ) ) . '</span>';
		return;
	}

	/* ---- 執筆者 ---- */
	if ( $col === 'ymkrf_writer' ) {
		echo ymkrf_column_writer_name( $post_id, true );
		/* クイック編集がいまの値を読むための、見えない目じるし */
		echo '<span class="ymkrf-wnow" style="display:none">'
		   . esc_html( ymkrf_column_writer_clean( get_post_meta( $post_id, '_ymkrf_staff', true ) ) )
		   . '</span>';
		return;
	}

	/* ---- 状態 ---- */
	if ( $col === 'ymkrf_status' ) {

		$now  = current_time( 'timestamp' );
		$mine = $p->post_status;

		if ( $mine === 'publish' && strtotime( $p->post_date ) > $now ) $mine = 'future';

		$map = array(
			'publish' => array( '公開',   '#00782a', '#eaf6ee' ),
			'draft'   => array( '下書き', '#996800', '#fcf3e3' ),
			'pending' => array( '確認待ち', '#996800', '#fcf3e3' ),
			'future'  => array( '予約',   '#2271b1', '#eaf2fa' ),
			'private' => array( '非公開', '#646970', '#f0f0f1' ),
			'trash'   => array( 'ゴミ箱', '#b32d2e', '#fcf0f1' ),
		);
		$m = isset( $map[ $mine ] ) ? $map[ $mine ] : array( $mine, '#646970', '#f0f0f1' );

		printf(
			'<span style="display:inline-block;padding:2px 10px;border-radius:11px;'
			. 'font-weight:700;font-size:12px;color:%s;background:%s">%s</span>',
			esc_attr( $m[1] ), esc_attr( $m[2] ), esc_html( $m[0] )
		);
		return;
	}

}, 10, 2 );

/* 見出しを押すと、並べかえられます */
add_filter( 'manage_edit-ymkrf_column_sortable_columns', function ( $cols ) {
	$cols['ymkrf_pub']    = 'date';
	$cols['ymkrf_status'] = 'ymkrf_status';
	$cols['ymkrf_writer'] = 'ymkrf_writer';
	return $cols;
} );

/**
 * 「状態」の並べかた。
 * WordPress は状態のじゅんに並べる機能をもっていないので、
 * ここで自分で順番を決めています。
 *   公開 → 予約 → 確認待ち → 下書き → 非公開
 * 同じ状態のなかでは、掲載日の新しいものが上にきます。
 */
add_filter( 'posts_orderby', function ( $orderby, $q ) {

	if ( ! is_admin() || ! $q->is_main_query() ) return $orderby;
	if ( $q->get( 'post_type' ) !== 'ymkrf_column' ) return $orderby;
	if ( $q->get( 'orderby' ) !== 'ymkrf_status' )   return $orderby;

	global $wpdb;
	$ord = ( strtoupper( (string) $q->get( 'order' ) ) === 'ASC' ) ? 'ASC' : 'DESC';

	return "FIELD( {$wpdb->posts}.post_status,"
	     . " 'publish','future','pending','draft','private' ) {$ord},"
	     . " {$wpdb->posts}.post_date DESC";

}, 10, 2 );

/* 列はばをととのえます */
add_action( 'admin_head-edit.php', function () {
	$s = get_current_screen();
	if ( ! $s || $s->post_type !== 'ymkrf_column' ) return;
	echo '<style>
	.column-ymkrf_pub{width:7.5em}
	.column-ymkrf_status{width:6.5em}
	.column-title{width:auto}
	</style>';
} );

/* 題名のうしろの「— 下書き」は消します（右の「状態」らんに出しているため） */
add_filter( 'display_post_states', function ( $states, $post ) {
	if ( $post && $post->post_type === 'ymkrf_column' ) return array();
	return $states;
}, 10, 2 );

/* ============================================================
   管理画面のコラム一覧「執筆者」らん

   書いた人は2とおりの入りかたがあります。
     ① スタッフをえらんだもの … _ymkrf_staff（スタッフの投稿ID）
     ② 旧ブログから取り込んだもの … _ymkrf_old_writer（お名前の文字）
   どちらも同じらんに出します。
   ============================================================ */

/**
 * 執筆者のお名前を返します。
 * $html を true にすると、一覧に出す形（リンクや色つき）で返します。
 */
function ymkrf_column_writer_name( $post_id, $html = false ) {

	$raw = ymkrf_column_writer_clean( get_post_meta( $post_id, '_ymkrf_staff', true ) );

	/* お店から */
	$shop = ymkrf_column_writer_shop( $raw );
	if ( $shop !== '' ) {
		$s    = ymkrf_column_shop_info( $shop );
		$name = $s ? $s['name'] : $shop;
		return $html ? '<b>' . esc_html( $name ) . '</b>' : $name;
	}

	$sid = (int) $raw;

	if ( $sid === (int) YMKRF_WRITER_PR ) {
		return $html ? '<b>広報</b>' : '広報';
	}

	if ( $sid ) {
		$sp = get_post( $sid );
		if ( $sp && $sp->post_type === 'ymkrf_staff' ) {
			$name = trim( (string) get_the_title( $sp ) );
			if ( $name !== '' ) {
				if ( ! $html ) return $name;
				$shop = function_exists( 'ymkrf_staff_shop_name' )
				      ? trim( (string) ymkrf_staff_shop_name( $sid ) ) : '';
				$out  = '<a href="' . esc_url( (string) get_edit_post_link( $sid ) ) . '">'
				      . esc_html( $name ) . '</a>';
				if ( $shop !== '' ) {
					$out .= '<br><span style="color:#646970;font-size:12px">'
					      . esc_html( $shop ) . '</span>';
				}
				return $out;
			}
		}
	}

	/* 旧ブログのお名前（スタッフとひもづいていないもの） */
	$old = trim( (string) get_post_meta( $post_id, '_ymkrf_old_writer', true ) );
	if ( $old !== '' ) {
		if ( ! $html ) return $old;
		return '<span style="color:#646970">' . esc_html( $old ) . '</span>'
		     . '<br><span style="color:#a7aaad;font-size:11px">旧ブログ</span>';
	}

	return $html ? '<span style="color:#a7aaad">—</span>' : '';
}

/**
 * 「執筆者」の見出しを押したときの並べかえ。
 * WordPress は2つのちがう項目をまとめて並べる機能がないので、
 * ここで自分でつなぎ合わせています。
 * お名前の無いものは、いちばん後ろにきます。
 */
add_filter( 'posts_clauses', function ( $c, $q ) {

	if ( ! is_admin() || ! $q->is_main_query() ) return $c;
	if ( $q->get( 'post_type' ) !== 'ymkrf_column' ) return $c;
	if ( $q->get( 'orderby' ) !== 'ymkrf_writer' )   return $c;

	global $wpdb;
	$ord = ( strtoupper( (string) $q->get( 'order' ) ) === 'DESC' ) ? 'DESC' : 'ASC';

	$c['join'] .= "
		LEFT JOIN {$wpdb->postmeta} ymkw1
		       ON ( ymkw1.post_id = {$wpdb->posts}.ID AND ymkw1.meta_key = '_ymkrf_staff' )
		LEFT JOIN {$wpdb->posts} ymkws
		       ON ( ymkws.ID = ymkw1.meta_value AND ymkws.post_type = 'ymkrf_staff' )
		LEFT JOIN {$wpdb->postmeta} ymkw2
		       ON ( ymkw2.post_id = {$wpdb->posts}.ID AND ymkw2.meta_key = '_ymkrf_old_writer' )";

	/* 名前をひとつにまとめます（スタッフ名 → 旧ブログの名前 → 空） */
	$name = "COALESCE( NULLIF( ymkws.post_title, '' ), NULLIF( ymkw2.meta_value, '' ), '' )";

	/* 名前の無いものは、昇順でも降順でも、いちばん後ろにします */
	$c['orderby'] = "( {$name} = '' ) ASC, {$name} {$ord}, {$wpdb->posts}.post_date DESC";

	return $c;
}, 10, 2 );

/* 列はばをととのえます */
add_action( 'admin_head-edit.php', function () {
	$s = get_current_screen();
	if ( ! $s || $s->post_type !== 'ymkrf_column' ) return;
	echo '<style>.column-ymkrf_writer{width:9em}</style>';
} );

/* ============================================================
   コラム一覧の「クイック編集」「一括編集」でも執筆者を変えられるように

   一覧から直したいとき、これまでは記事をひらく必要がありました。
   （2026/09/10 ユーザーからの「一覧が変更されない」という指摘より）
   ============================================================ */

/** クイック編集・一括編集の中に出す、執筆者のえらび欄 */
function ymkrf_column_writer_inline_select( $name = '_ymkrf_staff', $bulk = false ) {

	$list = function_exists( 'ymkrf_staff_list' ) ? ymkrf_staff_list() : array();
	$list = ymkrf_column_writer_sort( $list );

	$groups = array();
	foreach ( $list as $st ) {
		$shop = function_exists( 'ymkrf_staff_shop_name' )
		      ? trim( (string) ymkrf_staff_shop_name( $st->ID ) ) : '';
		if ( $shop === '' ) $shop = 'その他';
		$groups[ $shop ][] = $st;
	}
	?>
	<select name="<?php echo esc_attr( $name ); ?>" class="ymkrf-wsel">
		<?php if ( $bulk ) : ?>
			<option value="">— 変更しない —</option>
		<?php endif; ?>
		<option value="0">（出さない）</option>
		<option value="<?php echo (int) YMKRF_WRITER_PR; ?>">広報</option>
		<optgroup label="お店から">
			<?php foreach ( ymkrf_column_shop_options() as $val => $lab ) : ?>
				<option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $lab ); ?></option>
			<?php endforeach; ?>
		</optgroup>
		<?php foreach ( $groups as $shop => $members ) : ?>
			<optgroup label="<?php echo esc_attr( $shop ); ?>">
				<?php foreach ( $members as $st ) :
					$role = trim( (string) get_post_meta( $st->ID, '_ymkrf_staff_role', true ) ); ?>
					<option value="<?php echo (int) $st->ID; ?>"><?php
						echo esc_html( get_the_title( $st ) . ( $role !== '' ? '（' . $role . '）' : '' ) );
					?></option>
				<?php endforeach; ?>
			</optgroup>
		<?php endforeach; ?>
	</select>
	<?php
}

add_action( 'quick_edit_custom_box', function ( $col, $type ) {
	if ( $type !== 'ymkrf_column' || $col !== 'ymkrf_writer' ) return;
	?>
	<fieldset class="inline-edit-col-right">
	  <div class="inline-edit-col">
	    <label class="inline-edit-group">
	      <span class="title">執筆者</span>
	      <?php ymkrf_column_writer_inline_select( '_ymkrf_staff', false ); ?>
	    </label>
	  </div>
	</fieldset>
	<?php
}, 10, 2 );

add_action( 'bulk_edit_custom_box', function ( $col, $type ) {
	if ( $type !== 'ymkrf_column' || $col !== 'ymkrf_writer' ) return;
	?>
	<fieldset class="inline-edit-col-right">
	  <div class="inline-edit-col">
	    <label class="inline-edit-group">
	      <span class="title">執筆者</span>
	      <?php ymkrf_column_writer_inline_select( '_ymkrf_staff', true ); ?>
	    </label>
	  </div>
	</fieldset>
	<?php
}, 10, 2 );

/* いまの執筆者を、クイック編集の欄にうつします */
add_action( 'admin_footer-edit.php', function () {
	$s = get_current_screen();
	if ( ! $s || $s->post_type !== 'ymkrf_column' ) return;
	?>
	<script>
	jQuery(function($){
	  var orig = ( typeof inlineEditPost !== 'undefined' ) ? inlineEditPost.edit : null;
	  if ( ! orig ) return;
	  inlineEditPost.edit = function ( id ) {
	    orig.apply( this, arguments );
	    var pid = ( typeof id === 'object' ) ? this.getId( id ) : id;
	    if ( ! pid ) return;
	    var now = $('#post-' + pid).find('.ymkrf-wnow').text();
	    $('#edit-' + pid).find('select[name="_ymkrf_staff"]').val( now || '0' );
	  };
	});
	</script>
	<?php
} );

/* クイック編集・一括編集からの保存 */
add_action( 'save_post_ymkrf_column', function ( $post_id ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;
	if ( ! isset( $_POST['_ymkrf_staff'] ) ) return;

	$act = isset( $_POST['action'] ) ? sanitize_key( $_POST['action'] ) : '';

	/* クイック編集 */
	if ( $act === 'inline-save' ) {
		if ( ! check_ajax_referer( 'inlineeditnonce', '_inline_edit', false ) ) return;
		update_post_meta( $post_id, '_ymkrf_staff',
			ymkrf_column_writer_clean( wp_unslash( $_POST['_ymkrf_staff'] ) ) );
		return;
	}

	/* 一括編集（「— 変更しない —」のときは、さわりません） */
	if ( isset( $_GET['bulk_edit'] ) || isset( $_POST['bulk_edit'] ) ) {
		if ( $_POST['_ymkrf_staff'] === '' ) return;
		update_post_meta( $post_id, '_ymkrf_staff',
			ymkrf_column_writer_clean( wp_unslash( $_POST['_ymkrf_staff'] ) ) );
	}
}, 20 );

/* ============================================================
   アイキャッチ画像が無いときは、本文の1枚目の写真を使う

   （2026/09/10 ユーザー指示
     「アイキャッチを設定していない場合、一番最初の写真もしくは
       イラストをアイキャッチにして」）

   ・記事を保存したときに、自動で入ります。
   ・すでにある記事にも、一度だけまとめて入れます。
   ・手でアイキャッチを設定してあるものは、さわりません。
   ============================================================ */

/** 本文の1枚目の写真の、メディアの番号を返します（見つからなければ 0） */
function ymkrf_column_first_image_id( $post_id ) {

	$p = get_post( $post_id );
	if ( ! $p ) return 0;

	$html = (string) $p->post_content;
	if ( $html === '' ) return 0;

	if ( ! preg_match_all( '#<img[^>]+src=["\']([^"\']+)["\']#i', $html, $m ) ) return 0;

	$first_any = 0;   /* 帯しか無かったときの、ひかえ */

	foreach ( $m[1] as $src ) {

		/* wp-image-123 のような印が付いていれば、それがいちばん確かです */
		$id = attachment_url_to_postid( $src );

		/* 大・中などに縮めた画像は、もとの画像として引きなおします
		   （例 …-1024x768.jpg → ….jpg） */
		if ( ! $id ) {
			$base = preg_replace( '#-\d+x\d+(\.[A-Za-z0-9]+)$#', '$1', $src );
			if ( $base !== $src ) $id = attachment_url_to_postid( $base );
		}

		if ( ! $id ) continue;

		if ( ! $first_any ) $first_any = (int) $id;

		/* 横に長い画像（キャンペーンの帯など）は、アイキャッチにすると
		   カードで上下が切れてしまうので飛ばします。
		   （2026/09/10 ユーザー指示「帯のものは変更して」） */
		if ( ymkrf_column_is_banner( $id ) ) continue;

		return (int) $id;
	}

	/* 帯しか無かったときは、その帯を使います */
	if ( $first_any ) return $first_any;

	/* src では見つからないとき、class の wp-image-123 を見ます */
	if ( preg_match( '#wp-image-(\d+)#', $html, $mm ) ) return (int) $mm[1];

	return 0;
}

/**
 * 「帯」かどうか。よこ長すぎる画像を帯とみなします。
 * よこ ÷ たて が 1.9 以上のものが対象です
 * （キャンペーンの帯は 1000×468 ＝ 2.1 ぐらいです）。
 */
function ymkrf_column_is_banner( $att_id ) {
	$meta = wp_get_attachment_metadata( $att_id );
	if ( empty( $meta['width'] ) || empty( $meta['height'] ) ) return false;
	return ( $meta['width'] / $meta['height'] ) >= 1.9;
}

/** アイキャッチが空なら、本文の1枚目を入れます */
function ymkrf_column_fill_thumb( $post_id ) {

	if ( get_post_thumbnail_id( $post_id ) ) return false;

	$id = ymkrf_column_first_image_id( $post_id );
	if ( ! $id ) return false;

	$att = get_post( $id );
	if ( ! $att || $att->post_type !== 'attachment' ) return false;

	set_post_thumbnail( $post_id, $id );

	/* あとから「これは自動で入れたもの」と分かるようにしておきます */
	update_post_meta( $post_id, '_ymkrf_thumb_auto', '1' );
	return true;
}

add_action( 'save_post_ymkrf_column', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;
	ymkrf_column_fill_thumb( $post_id );
}, 50 );

/* 自動で入れたアイキャッチが「帯」だったものを、写真に入れなおします
   （2026/09/10 ユーザー指示。手で設定したものは、さわりません） */
add_action( 'admin_init', function () {

	if ( get_option( 'ymkrf_column_rebanner' ) === '1' ) return;
	if ( ! current_user_can( 'edit_posts' ) ) return;

	$ids = get_posts( array(
		'post_type'      => 'ymkrf_column',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => array( array( 'key' => '_ymkrf_thumb_auto', 'value' => '1' ) ),
	) );

	foreach ( $ids as $id ) {
		$now = (int) get_post_thumbnail_id( $id );
		if ( ! $now || ! ymkrf_column_is_banner( $now ) ) continue;

		delete_post_thumbnail( $id );
		$new = ymkrf_column_first_image_id( $id );
		if ( $new && $new !== $now ) {
			set_post_thumbnail( $id, $new );
		} else {
			set_post_thumbnail( $id, $now );   /* ほかに写真が無いので、そのまま */
		}
	}

	update_option( 'ymkrf_column_rebanner', '1', false );
}, 21 );

/* すでにある記事にも、一度だけまとめて入れます */
add_action( 'admin_init', function () {

	if ( get_option( 'ymkrf_column_thumbfill' ) === '2' ) return;
	if ( ! current_user_can( 'edit_posts' ) ) return;

	$ids = get_posts( array(
		'post_type'      => 'ymkrf_column',
		'post_status'    => 'any',
		'posts_per_page' => 40,          /* 1回に40本ずつ。数回の画面ひらきで終わります */
		'fields'         => 'ids',
		'meta_query'     => array(
			'relation' => 'AND',
			array( 'key' => '_thumbnail_id', 'compare' => 'NOT EXISTS' ),
			array( 'key' => '_ymkrf_thumb_try', 'compare' => 'NOT EXISTS' ),
		),
	) );

	if ( ! $ids ) { update_option( 'ymkrf_column_thumbfill', '2', false ); return; }

	foreach ( $ids as $id ) {
		update_post_meta( $id, '_ymkrf_thumb_try', '1' );   /* 一度みた印 */
		ymkrf_column_fill_thumb( $id );
	}
}, 20 );


/* ============================================================
   コラムと商品・エリアの紐づけ
   ------------------------------------------------------------
   （2026/09/24 ユーザー指示
     「コラムですが、商品と紐づけるの？コラム登録ぺージに
       商品や場所を選択できるものが必要じゃないかな？」
     「複数該当することがあればさせたい」）

   ・コラムの編集画面で、関係する商品を いくつでも えらべます
   ・コラムの編集画面で、関係するエリアも えらべます（任意。ふつうは空のまま）
   ・商品ページの下に、その商品のコラムが出ます
     （紐づけが無いときは、同じ分類のコラムが出ます）
   ・コラムの記事の下に、紐づけた商品のカードが出ます

   ★エリアは「本当にその地域だけの話」のときだけ付けてください。
     同じ内容を地域名だけ変えて増やすのは、検索エンジンに嫌われます。
   ============================================================ */

/** このコラムに紐づいている商品のID（並び順そのまま） */
if ( ! function_exists( 'ymkrf_col_products' ) ) :
function ymkrf_col_products( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	if ( ! $post_id ) return array();

	$ids = get_post_meta( $post_id, '_ymkrf_col_product' );
	$out = array();
	foreach ( (array) $ids as $one ) {
		$one = (int) $one;
		if ( $one && get_post_status( $one ) && ! in_array( $one, $out, true ) ) $out[] = $one;
	}
	return $out;
}
endif;

/* エリアも、コラムで使えるようにします（任意） */
add_action( 'init', function () {
	if ( taxonomy_exists( 'ymkrf_works_area' ) && post_type_exists( 'ymkrf_column' ) ) {
		register_taxonomy_for_object_type( 'ymkrf_works_area', 'ymkrf_column' );
	}
}, 11 );


/* ------------------------------------------------------------
   コラムの編集画面「関係する商品」
   ------------------------------------------------------------ */
add_action( 'add_meta_boxes', function () {
	add_meta_box(
		'ymkrf_col_products', '関係する商品',
		'ymkrf_col_products_box', 'ymkrf_column', 'normal', 'default'
	);
} );

function ymkrf_col_products_box( $post ) {

	wp_nonce_field( 'ymkrf_col_products', 'ymkrf_col_products_nonce' );

	$now = ymkrf_col_products( $post->ID );

	/* 商品を、分類ごとにまとめて出します */
	$cats = get_terms( array(
		'taxonomy'   => 'ymkrf_product_cat',
		'hide_empty' => false,
	) );
	if ( is_wp_error( $cats ) ) $cats = array();

	/* 商品一覧の並び順に合わせます */
	if ( function_exists( 'ymkrf_product_cat_sort' ) ) $cats = ymkrf_product_cat_sort( $cats );
	?>
	<p class="ymkrf-note">
	  この記事に関係する商品をえらんでください。いくつでもえらべます。<br>
	  えらぶと、その商品のページに この記事が出るようになり、
	  この記事の下には えらんだ商品が出ます。
	</p>

	<p class="ymkrf-colp__find">
	  <input type="search" id="ymkrf-colp-find" placeholder="商品名・型番でしぼりこむ" style="width:280px">
	  <span class="ymkrf-colp__count"></span>
	</p>

	<div class="ymkrf-colp">
	  <?php foreach ( $cats as $c ) :
	    $posts = get_posts( array(
	      'post_type'      => 'ymkrf_product',
	      'posts_per_page' => -1,
	      'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
	      'orderby'        => 'menu_order title',
	      'order'          => 'ASC',
	      'tax_query'      => array( array(
	        'taxonomy' => 'ymkrf_product_cat', 'field' => 'term_id', 'terms' => $c->term_id,
	      ) ),
	    ) );
	    if ( ! $posts ) continue; ?>
	    <div class="ymkrf-colp__cat">
	      <h4><?php echo esc_html( $c->name ); ?></h4>
	      <ul>
	        <?php foreach ( $posts as $p ) :
	          $nm = trim( (string) get_post_meta( $p->ID, '_ymkrf_name', true ) );
	          if ( $nm === '' ) $nm = $p->post_title;
	          $mo = trim( (string) get_post_meta( $p->ID, '_ymkrf_model', true ) );
	          $on = in_array( (int) $p->ID, $now, true ); ?>
	          <li<?php echo $on ? ' class="is-on"' : ''; ?>
	              data-find="<?php echo esc_attr( mb_strtolower( $nm . ' ' . $mo, 'UTF-8' ) ); ?>">
	            <label>
	              <input type="checkbox" name="ymkrf_col_product[]"
	                     value="<?php echo (int) $p->ID; ?>"<?php checked( $on ); ?>>
	              <span><?php echo esc_html( $nm ); ?></span>
	              <?php if ( $mo ) : ?><em><?php echo esc_html( $mo ); ?></em><?php endif; ?>
	              <?php if ( $p->post_status !== 'publish' ) : ?><b>下書き</b><?php endif; ?>
	            </label>
	          </li>
	        <?php endforeach; ?>
	      </ul>
	    </div>
	  <?php endforeach; ?>
	</div>

	<style>
	  .ymkrf-colp__find{margin:6px 0 10px}
	  .ymkrf-colp__count{margin-left:10px;font-size:12px;color:#787c82}
	  .ymkrf-colp{
	    display:grid;gap:14px;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));
	    max-height:420px;overflow:auto;padding:4px;border:1px solid #dcdcde;border-radius:6px;
	    background:#fff}
	  .ymkrf-colp__cat h4{
	    margin:0 0 6px;padding:6px 9px;border-radius:5px;
	    background:#fdece6;border-bottom:2px solid #fe3301;font-size:12.5px}
	  .ymkrf-colp__cat ul{margin:0;padding:0;list-style:none}
	  .ymkrf-colp__cat li{margin:0;padding:3px 6px;border-radius:4px;font-size:13px}
	  .ymkrf-colp__cat li:nth-child(odd){background:#fafafa}
	  .ymkrf-colp__cat li.is-on{background:#fff4f0}
	  .ymkrf-colp__cat label{display:flex;align-items:center;gap:6px;cursor:pointer}
	  .ymkrf-colp__cat em{font-style:normal;font-size:11px;color:#787c82}
	  .ymkrf-colp__cat b{font-size:10.5px;color:#b32d2e;font-weight:700}
	</style>
	<script>
	jQuery(function ($) {

	  function count() {
	    var n = $('.ymkrf-colp input:checked').length;
	    $('.ymkrf-colp__count').text(n ? n + '件えらんでいます' : 'まだえらんでいません');
	  }
	  count();

	  $(document).on('change', '.ymkrf-colp input', function () {
	    $(this).closest('li').toggleClass('is-on', this.checked);
	    count();
	  });

	  /* 名前・型番でしぼりこみます */
	  $('#ymkrf-colp-find').on('input', function () {
	    var q = $.trim(this.value).toLowerCase();
	    $('.ymkrf-colp li').each(function () {
	      $(this).toggle(q === '' || ($(this).data('find') + '').indexOf(q) >= 0);
	    });
	    $('.ymkrf-colp__cat').each(function () {
	      $(this).toggle($(this).find('li:visible').length > 0);
	    });
	  });
	});
	</script>
	<?php
}

add_action( 'save_post_ymkrf_column', function ( $post_id ) {

	if ( ! isset( $_POST['ymkrf_col_products_nonce'] ) ||
	     ! wp_verify_nonce( sanitize_key( $_POST['ymkrf_col_products_nonce'] ), 'ymkrf_col_products' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	delete_post_meta( $post_id, '_ymkrf_col_product' );

	$ids = isset( $_POST['ymkrf_col_product'] ) ? (array) wp_unslash( $_POST['ymkrf_col_product'] ) : array();
	$put = array();
	foreach ( $ids as $one ) {
		$one = (int) $one;
		if ( $one && ! in_array( $one, $put, true ) ) {
			add_post_meta( $post_id, '_ymkrf_col_product', $one );
			$put[] = $one;
		}
	}
} );


/* ------------------------------------------------------------
   商品ページに出すコラム
   ------------------------------------------------------------ */

/** その商品のコラム。紐づけが無ければ、同じ分類のコラムを返します */
if ( ! function_exists( 'ymkrf_columns_for_product' ) ) :
function ymkrf_columns_for_product( $product_id, $n = 3 ) {

	$product_id = (int) $product_id;
	if ( ! $product_id ) return array();

	/* ① この商品に紐づけたコラム */
	$own = get_posts( array(
		'post_type'      => 'ymkrf_column',
		'posts_per_page' => (int) $n,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_query'     => array( array(
			'key'   => '_ymkrf_col_product',
			'value' => $product_id,
		) ),
	) );

	if ( count( $own ) >= $n ) return $own;

	/* ② 足りないぶんは、同じ分類のコラムでうめます */
	$cat = function_exists( 'ymkrf_product_current_cat' ) ? ymkrf_product_current_cat( $product_id ) : '';
	if ( $cat === '' ) return $own;

	$more = get_posts( array(
		'post_type'      => 'ymkrf_column',
		'posts_per_page' => (int) $n - count( $own ),
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'post__not_in'   => $own ? $own : array( 0 ),
		'tax_query'      => array( array(
			'taxonomy' => 'ymkrf_product_cat', 'field' => 'slug', 'terms' => $cat,
		) ),
	) );

	return array_merge( $own, $more );
}
endif;


/* ------------------------------------------------------------
   コラムの記事の下に出す「この記事で紹介した商品」
   ------------------------------------------------------------ */
if ( ! function_exists( 'ymkrf_column_product_cards' ) ) :
function ymkrf_column_product_cards( $post_id = 0 ) {

	$ids = ymkrf_col_products( $post_id );

	/* 公開している商品だけ出します */
	$ids = array_values( array_filter( $ids, function ( $one ) {
		return get_post_status( $one ) === 'publish';
	} ) );
	if ( ! $ids ) return;
	?>
	<section class="l-section">
	  <div class="l-wrap">
	    <h2 class="p-prd__bar">この記事で紹介した商品</h2>
	    <div class="p-colprd">
	      <?php foreach ( $ids as $pid ) :

	        $nm = trim( (string) get_post_meta( $pid, '_ymkrf_name', true ) );
	        if ( $nm === '' ) $nm = get_the_title( $pid );

	        $mk    = wp_get_object_terms( $pid, 'ymkrf_maker' );
	        $mkt   = ( ! is_wp_error( $mk ) && $mk ) ? $mk[0] : null;
	        $total = (int) get_post_meta( $pid, '_ymkrf_total', true );
	        ?>
	        <a class="p-colprd__card" href="<?php echo esc_url( get_permalink( $pid ) ); ?>">
	          <span class="p-colprd__ph"><?php
	            echo has_post_thumbnail( $pid )
	              ? get_the_post_thumbnail( $pid, 'medium', array( 'loading' => 'lazy', 'alt' => '' ) )
	              : '<span class="p-colprd__noph">写真は準備中です</span>';
	          ?></span>
	          <span class="p-colprd__body">
	            <?php if ( $mkt && function_exists( 'ymkrf_maker_logo' ) ) : ?>
	              <span class="p-colprd__maker"><?php
	                echo ymkrf_maker_logo( $mkt, 'p-maker' ); /* phpcs:ignore */ ?></span>
	            <?php endif; ?>
	            <span class="p-colprd__name"><?php echo esc_html( $nm ); ?></span>
	            <?php if ( $total ) : ?>
	              <span class="p-colprd__price">
	                <b><?php echo esc_html( number_format( $total ) ); ?></b>円（税込）
	              </span>
	            <?php endif; ?>
	            <span class="p-colprd__go">くわしく見る</span>
	          </span>
	        </a>
	      <?php endforeach; ?>
	    </div>
	  </div>
	</section>
	<?php
}
endif;


/* ============================================================
   コラムの「公開日」と「更新日」
   ------------------------------------------------------------
   （2026/09/25 ユーザー指示「コラム、そのように構成して」
     ＝ リライトしたときは 公開日は そのまま、更新日を足す）

   ■ なぜ公開日を書きかえないか
     公開日を新しくすると、これまで書いてきた積み重ねが消えてしまいます。
     古い記事ほど「長く書いている会社だ」という裏づけになるので、
     公開日はそのまま、直した日は「更新日」として別に出します。
     中身を直さずに日付だけ新しくするのは、逆に信用を落とします。

   ■ 更新日はどうやって決まるか
     公開中のコラムの 題名か本文が変わって保存されたときだけ、
     その日を _ymkrf_col_updated に控えます。
     ＝ 旧ブログの取り込みや、ちょっとした設定の直しでは動きません。
     手で消したいときは、カスタムフィールドから消してください。
   ============================================================ */

/** 公開中のコラムの中身が変わったら、更新日を控えます */
add_action( 'post_updated', function ( $post_id, $after, $before ) {

	if ( ! $after || $after->post_type !== 'ymkrf_column' ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

	/* 公開中 → 公開中 のときだけ。下書きを直しているあいだは動きません */
	if ( $before->post_status !== 'publish' || $after->post_status !== 'publish' ) return;

	$changed = ( $before->post_content !== $after->post_content )
	        || ( $before->post_title   !== $after->post_title );
	if ( ! $changed ) return;

	update_post_meta( $post_id, '_ymkrf_col_updated', current_time( 'Y-m-d H:i:s' ) );
}, 10, 3 );

/**
 * そのコラムの日付。
 * 戻り値： array( 'pub' => 'Y-m-d H:i:s', 'upd' => 'Y-m-d H:i:s' または '' )
 * 更新日は、公開日と同じ日のときは出しません（同じ日付が2つ並ばないように）。
 */
if ( ! function_exists( 'ymkrf_column_dates' ) ) :
function ymkrf_column_dates( $post_id = 0 ) {

	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	$p       = get_post( $post_id );
	if ( ! $p ) return array( 'pub' => '', 'upd' => '' );

	$pub = (string) $p->post_date;
	$upd = trim( (string) get_post_meta( $post_id, '_ymkrf_col_updated', true ) );

	if ( $upd !== '' && substr( $upd, 0, 10 ) <= substr( $pub, 0, 10 ) ) $upd = '';

	return array( 'pub' => $pub, 'upd' => $upd );
}
endif;

/** 一覧のカードなどで使う「見せる日付」。更新日があればそちら */
if ( ! function_exists( 'ymkrf_column_shown_date' ) ) :
function ymkrf_column_shown_date( $post_id = 0 ) {
	$d = ymkrf_column_dates( $post_id );
	return $d['upd'] !== '' ? $d['upd'] : $d['pub'];
}
endif;

/** 記事の頭に出す「◯年◯月◯日 公開 ／ ◯年◯月◯日 更新」 */
if ( ! function_exists( 'ymkrf_column_dateline' ) ) :
function ymkrf_column_dateline( $post_id = 0 ) {

	$d = ymkrf_column_dates( $post_id );
	if ( $d['pub'] === '' ) return;

	$pt = strtotime( $d['pub'] );
	printf(
		'<time class="p-colart__date" datetime="%s">%s<small>公開</small></time>',
		esc_attr( date_i18n( 'c', $pt ) ),
		esc_html( date_i18n( 'Y.m.d', $pt ) )
	);

	if ( $d['upd'] === '' ) return;

	$ut = strtotime( $d['upd'] );
	printf(
		'<time class="p-colart__date p-colart__date--upd" datetime="%s">%s<small>更新</small></time>',
		esc_attr( date_i18n( 'c', $ut ) ),
		esc_html( date_i18n( 'Y.m.d', $ut ) )
	);
}
endif;

/* 検索エンジンに、公開日と更新日を伝えます（Article の構造化データ） */
add_action( 'wp_head', function () {

	if ( ! is_singular( 'ymkrf_column' ) ) return;

	$id = get_the_ID();
	$d  = ymkrf_column_dates( $id );
	if ( $d['pub'] === '' ) return;

	$ld = array(
		'@context'      => 'https://schema.org',
		'@type'         => 'Article',
		'headline'      => wp_strip_all_tags( get_the_title( $id ) ),
		'mainEntityOfPage' => get_permalink( $id ),
		'datePublished' => date_i18n( 'c', strtotime( $d['pub'] ) ),
		'dateModified'  => date_i18n( 'c', strtotime( $d['upd'] !== '' ? $d['upd'] : $d['pub'] ) ),
		'publisher'     => array(
			'@type' => 'Organization',
			'name'  => '株式会社山岸（リフォームヤマキシ）',
			'url'   => home_url( '/' ),
		),
	);

	if ( has_post_thumbnail( $id ) ) {
		$img = get_the_post_thumbnail_url( $id, 'large' );
		if ( $img ) $ld['image'] = $img;
	}

	/* 執筆者がえらばれていれば、書いた人も伝えます */
	if ( function_exists( 'ymkrf_column_writer_name' ) ) {
		$who = trim( wp_strip_all_tags( (string) ymkrf_column_writer_name( $id, false ) ) );
		if ( $who !== '' && $who !== '—' ) {
			$ld['author'] = array( '@type' => 'Person', 'name' => $who );
		}
	}

	echo '<script type="application/ld+json">'
	   . wp_json_encode( $ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
	   . '</script>' . "\n";
}, 5 );


/* 「更新日」の見出しを押すと、直した順にならべ替えられます
   （2026/09/25 ユーザー指示で足した列です） */
add_filter( 'manage_edit-ymkrf_column_sortable_columns', function ( $cols ) {
	$cols['ymkrf_upd'] = 'ymkrf_upd';
	return $cols;
} );

add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() || ! $q->is_main_query() ) return;
	if ( $q->get( 'post_type' ) !== 'ymkrf_column' ) return;
	if ( $q->get( 'orderby' ) !== 'ymkrf_upd' ) return;

	/* まだ直していない記事も消えないように、「無いもの」も入れて並べます */
	$q->set( 'meta_query', array(
		'relation' => 'OR',
		'has'      => array( 'key' => '_ymkrf_col_updated', 'compare' => 'EXISTS' ),
		'none'     => array( 'key' => '_ymkrf_col_updated', 'compare' => 'NOT EXISTS' ),
	) );
	$q->set( 'orderby', array( 'has' => 'DESC', 'date' => 'DESC' ) );
} );


/* ------------------------------------------------------------
   コラムの編集画面「公開」の枠に、投稿日の下へ最終更新日を出します
   （2026/09/25 ユーザー指示
     「コラム投稿ぺージにも、投稿日の下に更新日が見えるようにしてほしい」
     「最終更新日だけで良いから」）

   一覧の「更新日」の列、記事の「更新」と同じ日付です。
   公開したあとに 題名か本文を直して更新すると入ります。
   ------------------------------------------------------------ */
add_action( 'post_submitbox_misc_actions', function ( $post ) {

	if ( ! $post || $post->post_type !== 'ymkrf_column' ) return;

	$d   = function_exists( 'ymkrf_column_dates' )
		? ymkrf_column_dates( $post->ID ) : array( 'upd' => '' );
	$upd = isset( $d['upd'] ) ? $d['upd'] : '';
	?>
	<div class="misc-pub-section ymkrf-updrow">
	  <span class="dashicons dashicons-update" style="color:#8c8f94;vertical-align:-3px"></span>
	  最終更新日:
	  <?php if ( $upd !== '' ) : ?>
	    <b class="ymkrf-updrow__day"><?php
	      echo esc_html( date_i18n( 'Y年n月j日 H:i', strtotime( $upd ) ) ); ?></b>
	  <?php else : ?>
	    <b class="ymkrf-updrow__none">—</b>
	  <?php endif; ?>
	</div>
	<style>
	  .ymkrf-updrow__day{ color:#b32d2e; }
	  .ymkrf-updrow__none{ color:#a7aaad; font-weight:400; }
	</style>
	<?php
} );
