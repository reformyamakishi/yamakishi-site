<?php
/**
 * メーカーのデジタルカタログへのリンク
 * リフォームヤマキシ（ymkrf）　2026/10/13
 *
 * ユーザー相談「カタログの画像を入れて、クリックしたらメーカーのカタログが
 *              開くようにしたら素敵じゃない？」→「Vスタイルで作ってみてほしい」
 *
 * ・登録画面に「メーカーのカタログ」の欄（URL／ページのメモ／画像は任意）
 * ・フロントは「おすすめオプション」の前にカードを1枚（2026/10/13 ユーザー指示で移動）。押すと新しいタブでカタログが開きます。
 *   ヤマキシのページは後ろに残るので、戻ってきてもらえます。
 * ・カードの下に「気になる色や品番は LINE で」のひとことを添えて、
 *   「カタログで選ぶ → ヤマキシに相談」の流れにします。
 *
 * 画像は任意です。入れなければ、とんとこトン（虫めがね）を出します。
 * ★カタログの表紙をそのまま使うときは、メーカーの販売店向けの決まりで
 *   使ってよいか確かめてから入れてください。
 */

if ( ! defined( 'ABSPATH' ) ) exit;


/* ============================================================
   登録画面
   ============================================================ */

add_action( 'add_meta_boxes', function () {
	add_meta_box(
		'ymkrf_product_catalog',
		'メーカーのカタログ <span style="font-weight:400;font-size:12px;color:#787c82">入れると、商品ページの「他のグレード」の上にカタログのカードが出ます</span>',
		'ymkrf_catalog_box',
		'ymkrf_product', 'normal', 'low'
	);
} );

function ymkrf_catalog_box( $post ) {

	wp_nonce_field( 'ymkrf_catalog_save', 'ymkrf_catalog_nonce' );

	$url  = (string) get_post_meta( $post->ID, '_ymkrf_cat_url', true );
	$page = (string) get_post_meta( $post->ID, '_ymkrf_cat_page', true );
	$img  = (int) get_post_meta( $post->ID, '_ymkrf_cat_img', true );
	$src  = $img ? wp_get_attachment_image_url( $img, 'medium' ) : '';
	?>
	<table class="form-table" style="margin:0">
		<tr>
			<th style="width:150px">カタログのURL</th>
			<td>
				<input type="url" name="_ymkrf_cat_url" value="<?php echo esc_attr( $url ); ?>"
				       class="large-text" placeholder="https://（メーカーのデジタルカタログの、この商品のページ）">
				<p class="description">メーカーのデジタルカタログで、この商品が載っているページを開き、
					アドレス欄のURLをそのまま貼ってください。空なら、カードは出ません。</p>
			</td>
		</tr>
		<tr>
			<th>ページのメモ</th>
			<td>
				<input type="text" name="_ymkrf_cat_page" value="<?php echo esc_attr( $page ); ?>"
				       class="regular-text" placeholder="例：2026年版 P.45〜62">
				<p class="description">カードに小さく出ます。年度版が変わったときの目印にもなります。</p>
			</td>
		</tr>
		<tr>
			<th>画像（なくてもOK）</th>
			<td>
				<span class="ymkrf-catimg" style="display:inline-flex;align-items:center;gap:10px">
					<span class="ymkrf-catimg__prev"
					      style="width:90px;height:90px;border:1px dashed #c3c4c7;border-radius:6px;background:#fff;
					             display:grid;place-items:center;overflow:hidden;cursor:pointer"
					      title="押すと写真をえらべます"><?php
						echo $src ? '<img src="' . esc_url( $src ) . '" alt="" style="max-width:100%;max-height:100%">'
						          : '<span style="color:#a7aaad;font-size:12px;font-weight:700">＋ 画像</span>';
					?></span>
					<input type="hidden" name="_ymkrf_cat_img" value="<?php echo (int) $img; ?>">
					<button type="button" class="button-link ymkrf-catimg__clear"<?php if ( ! $img ) echo ' style="display:none"'; ?>>画像を外す</button>
				</span>
				<?php /* 説明文は消しました（2026/10/14 ユーザー指示） */ ?>
			</td>
		</tr>
	</table>
	<script>
	jQuery(function ($) {
		var $w = $('.ymkrf-catimg');
		$w.on('click', '.ymkrf-catimg__prev', function () {
			var f = wp.media({ title: 'カタログの画像をえらぶ', multiple: false, library: { type: 'image' } });
			f.on('select', function () {
				var a = f.state().get('selection').first().toJSON();
				var u = (a.sizes && a.sizes.medium) ? a.sizes.medium.url : a.url;
				$w.find('input[name="_ymkrf_cat_img"]').val(a.id);
				$w.find('.ymkrf-catimg__prev').html('<img src="' + u + '" alt="" style="max-width:100%;max-height:100%">');
				$w.find('.ymkrf-catimg__clear').show();
			});
			f.open();
		});
		$w.on('click', '.ymkrf-catimg__clear', function () {
			$w.find('input[name="_ymkrf_cat_img"]').val('');
			$w.find('.ymkrf-catimg__prev').html('<span style="color:#a7aaad;font-size:12px;font-weight:700">＋ 画像</span>');
			$(this).hide();
		});
	});
	</script>
	<?php
}

add_action( 'save_post_ymkrf_product', function ( $post_id ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;
	if ( ! isset( $_POST['ymkrf_catalog_nonce'] ) ) return;
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ymkrf_catalog_nonce'] ) ), 'ymkrf_catalog_save' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	$url = isset( $_POST['_ymkrf_cat_url'] ) ? esc_url_raw( trim( wp_unslash( $_POST['_ymkrf_cat_url'] ) ) ) : '';
	$pg  = isset( $_POST['_ymkrf_cat_page'] ) ? sanitize_text_field( wp_unslash( $_POST['_ymkrf_cat_page'] ) ) : '';
	$img = isset( $_POST['_ymkrf_cat_img'] ) ? (int) $_POST['_ymkrf_cat_img'] : 0;

	foreach ( array( '_ymkrf_cat_url' => $url, '_ymkrf_cat_page' => $pg, '_ymkrf_cat_img' => $img ) as $k => $v ) {
		if ( $v ) update_post_meta( $post_id, $k, $v );
		else      delete_post_meta( $post_id, $k );
	}
}, 30 );


/* ============================================================
   フロント（商品ページ）
   ============================================================ */

/**
 * カタログのカードを出します。URLが空なら何も出しません。
 * 使い方： <?php ymkrf_catalog_card( get_the_ID(), $maker_name, $product_name ); ?>
 */
function ymkrf_catalog_card( $post_id, $maker = '', $name = '' ) {

	$url = (string) get_post_meta( $post_id, '_ymkrf_cat_url', true );
	if ( $url === '' ) return;

	$page = (string) get_post_meta( $post_id, '_ymkrf_cat_page', true );
	$img  = (int) get_post_meta( $post_id, '_ymkrf_cat_img', true );
	$line = 'https://line.me/R/ti/p/@233okcdx';

	$alt = trim( $maker . ' ' . $name . ' デジタルカタログ' );
	if ( $img ) {
		$pic = wp_get_attachment_image( $img, 'medium', false, array(
			'class' => 'p-prd__catimg', 'alt' => $alt, 'loading' => 'lazy', 'decoding' => 'async',
		) );
	} else {
		$pic = '<img class="p-prd__catimg p-prd__catimg--ton" src="'
		     . esc_url( get_stylesheet_directory_uri() . '/assets/img/character/char-search.webp' )
		     . '" width="120" height="120" alt="とんとこトン" loading="lazy" decoding="async">';
	}
	?>
	<!-- =========== メーカーのカタログ（2026/10/13） =========== -->
	<section class="l-section">
	  <div class="l-wrap">
	    <div class="p-prd__catalog">
	      <a class="p-prd__catcard" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">
	        <span class="p-prd__catpic"><?php echo $pic; /* phpcs:ignore */ ?></span>
	        <span class="p-prd__cattxt">
	          <span class="p-prd__catttl">メーカーのデジタルカタログで見る</span>
	          <span class="p-prd__catsub"><?php
	            echo esc_html( trim( $maker . ' ' . $name ) );
	            if ( $page !== '' ) echo '　<span class="p-prd__catpage">' . esc_html( $page ) . '</span>';
	          ?></span>
	          <?php /* 2026/10/14 ユーザー指示で「メーカーのサイトが新しいタブで開きます」から差しかえ */ ?>
	          <span class="p-prd__catnote">※表紙はイメージです。</span>
	          <span class="p-prd__catnote">※最新のカタログを掲載するまで、旧カタログの場合があります。</span>
	          <span class="p-prd__catnote">※メーカーのカタログには工事費は含まれておりませんのでご注意ください。</span>
	        </span>
	        <span class="p-prd__catarrow" aria-hidden="true">↗</span>
	      </a>
	      <p class="p-prd__catline">
	        気になる色や品番があれば、<a href="<?php echo esc_url( $line ); ?>" target="_blank" rel="noopener">LINEで送ってください</a>。そのままお見積りします。
	      </p>
	    </div>
	  </div>
	</section>
	<?php
}


/* ============================================================
   カタログURLを、1つの画面でまとめて入れる
   （2026/10/13 ユーザー指示「他のキッチン、お風呂、トイレも同じようにお願い」
     →「自分で貼る」。商品を1つずつ開かなくてすむように）

   商品 ＞ カタログURLの入力
   ・キッチン／ユニットバス／トイレ／洗面化粧台 ごとに、商品が並びます
   ・URLとページのメモを入れて「保存」を押すだけ
   ・書きかえた行だけを保存します（ほかの画面で直した内容を上書きしないため）
   ============================================================ */

add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=ymkrf_product',
		'カタログURLの入力', 'カタログURLの入力',
		'edit_posts', 'ymkrf-catalog-urls', 'ymkrf_catalog_urls_page'
	);
}, 40 );

function ymkrf_catalog_urls_page() {

	if ( ! current_user_can( 'edit_posts' ) ) wp_die( '権限がありません' );

	$cats = array(
		'kitchen'  => 'キッチン',
		'bathroom' => 'ユニットバス',
		'toilet'   => 'トイレ',
		'lavatory' => '洗面化粧台',
	);

	/* ---- 保存 ---- */
	$saved = 0;
	if ( isset( $_POST['ymkrf_caturls_nonce'] )
	  && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ymkrf_caturls_nonce'] ) ), 'ymkrf_caturls' ) ) {

		$rows = isset( $_POST['cat'] ) && is_array( $_POST['cat'] ) ? wp_unslash( $_POST['cat'] ) : array();
		foreach ( $rows as $id => $r ) {
			$id = (int) $id;
			if ( ! $id || get_post_type( $id ) !== 'ymkrf_product' ) continue;
			if ( ! current_user_can( 'edit_post', $id ) ) continue;

			$url  = isset( $r['url'] )  ? esc_url_raw( trim( (string) $r['url'] ) ) : '';
			$page = isset( $r['page'] ) ? sanitize_text_field( (string) $r['page'] ) : '';
			$ourl = isset( $r['ourl'] ) ? (string) $r['ourl'] : '';
			$opag = isset( $r['opage'] ) ? (string) $r['opage'] : '';

			/* 画面をひらいたときから変わっていない行は、さわりません */
			if ( $url === $ourl && $page === $opag ) continue;

			if ( $url !== '' ) update_post_meta( $id, '_ymkrf_cat_url', $url );
			else               delete_post_meta( $id, '_ymkrf_cat_url' );
			if ( $page !== '' ) update_post_meta( $id, '_ymkrf_cat_page', $page );
			else                delete_post_meta( $id, '_ymkrf_cat_page' );
			$saved++;
		}
	}

	/* ---- 一覧 ---- */
	$all = 0; $have = 0; $groups = array();
	foreach ( $cats as $slug => $label ) {
		$ps = get_posts( array(
			'post_type'      => 'ymkrf_product',
			'post_status'    => array( 'publish', 'draft', 'private', 'pending' ),
			'posts_per_page' => -1,
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'tax_query'      => array( array( 'taxonomy' => 'ymkrf_product_cat', 'field' => 'slug', 'terms' => $slug ) ),
		) );
		$groups[ $slug ] = $ps;
		foreach ( $ps as $p ) {
			$all++;
			if ( get_post_meta( $p->ID, '_ymkrf_cat_url', true ) !== '' ) $have++;
		}
	}
	?>
	<div class="wrap">
		<h1>カタログURLの入力</h1>

		<?php if ( $saved ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo (int) $saved; ?>件を保存しました。</p></div>
		<?php endif; ?>

		<p style="font-size:14px;line-height:1.9">
			メーカーのデジタルカタログで、その商品が載っているページを開き、<b>アドレス欄のURLをそのまま貼って</b>ください。<br>
			URLが入った商品だけ、商品ページの「おすすめオプション」の前にカタログのカードが出ます。
			入力ずみ <b><?php echo (int) $have; ?></b> ／ <?php echo (int) $all; ?>件
		</p>

		<form method="post">
			<?php wp_nonce_field( 'ymkrf_caturls', 'ymkrf_caturls_nonce' ); ?>
			<p><button type="submit" class="button button-primary">保存</button></p>

			<?php foreach ( $groups as $slug => $ps ) : if ( ! $ps ) continue; ?>
				<h2 style="margin-top:26px"><?php echo esc_html( $cats[ $slug ] ); ?>（<?php echo count( $ps ); ?>件）</h2>
				<table class="widefat striped">
					<thead><tr>
						<th style="width:26%">商品</th>
						<th style="width:12%">メーカー</th>
						<th>カタログのURL</th>
						<th style="width:16%">ページのメモ</th>
						<th style="width:5%"></th>
					</tr></thead>
					<tbody>
					<?php foreach ( $ps as $p ) :
						$u  = (string) get_post_meta( $p->ID, '_ymkrf_cat_url', true );
						$pg = (string) get_post_meta( $p->ID, '_ymkrf_cat_page', true );
						$mk = wp_get_post_terms( $p->ID, 'ymkrf_maker', array( 'fields' => 'names' ) );
						$mk = ( ! is_wp_error( $mk ) && $mk ) ? $mk[0] : '';
						$st = get_post_status( $p->ID );
					?>
						<tr>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link( $p->ID ) ); ?>" target="_blank" rel="noopener"><b><?php echo esc_html( get_the_title( $p ) ); ?></b></a>
								<?php if ( $st !== 'publish' ) echo '<span style="color:#787c82;font-size:12px">（' . esc_html( $st === 'draft' ? '下書き' : '非公開' ) . '）</span>'; ?>
							</td>
							<td><?php echo esc_html( $mk ); ?></td>
							<td>
								<input type="url" class="large-text" name="cat[<?php echo (int) $p->ID; ?>][url]" value="<?php echo esc_attr( $u ); ?>" placeholder="https://">
								<input type="hidden" name="cat[<?php echo (int) $p->ID; ?>][ourl]" value="<?php echo esc_attr( $u ); ?>">
							</td>
							<td>
								<input type="text" class="regular-text" style="width:100%" name="cat[<?php echo (int) $p->ID; ?>][page]" value="<?php echo esc_attr( $pg ); ?>" placeholder="例：2026年版 P.45〜">
								<input type="hidden" name="cat[<?php echo (int) $p->ID; ?>][opage]" value="<?php echo esc_attr( $pg ); ?>">
							</td>
							<td><?php if ( $u !== '' ) : ?>
								<a href="<?php echo esc_url( $u ); ?>" target="_blank" rel="noopener" title="カタログを開いて確かめる">開く</a>
							<?php endif; ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endforeach; ?>

			<p style="margin-top:20px"><button type="submit" class="button button-primary">保存</button></p>
		</form>
	</div>
	<?php
}
