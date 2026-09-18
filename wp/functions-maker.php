<?php
/**
 * functions-maker.php ─ メーカーの設定（商品 ＞ メーカーの設定）
 * 置き場所： wp-content/themes/ymkrf/inc/functions-maker.php
 *
 * （2026/09/18 ユーザー指示
 *   「その他設定でキッチンのメーカー追加できるような仕組みにしておいて。
 *     メーカーが選択肢にない場合は、まずそこで作ってからという流れにします」）
 *
 * ■ どういうことか
 *   商品の登録画面の「メーカー」は、その分類で使うものだけを出しています。
 *   使うメーカーをここで決めます。新しいメーカーも、ここで作れます。
 *
 *   ・分類ごとに、出すメーカーにチェックを入れます
 *   ・一覧にないメーカーは、いちばん下の欄で作ってください
 *     （作ると、その分類に自動でチェックが入ります）
 *
 * ■ まだ何も決めていない分類は？
 *   これまでどおり、その分類の商品に付いているメーカーが自動で出ます。
 *   ここで1度でも保存すると、こちらの内容が使われます。
 *
 * ■ 名前について
 *   保存さき … ymkrf_maker_cats（分類のスラッグ => メーカーIDの配列）
 */
if ( ! defined( 'ABSPATH' ) ) exit;


/** 設定の中身 */
function ymkrf_maker_opt() {
	$o = get_option( 'ymkrf_maker_cats' );
	return is_array( $o ) ? $o : array();
}

/**
 * その分類で出すメーカーのID。
 * ここで決めていなければ、商品から自動で調べたものを返します。
 */
function ymkrf_maker_choices( $cat_slug ) {

	$cat_slug = (string) $cat_slug;
	$o = ymkrf_maker_opt();

	if ( isset( $o[ $cat_slug ] ) && is_array( $o[ $cat_slug ] ) ) {
		return array_map( 'intval', $o[ $cat_slug ] );
	}

	/* 決めていないときは、これまでどおり商品から自動で */
	if ( ! function_exists( 'ymkrf_maker_map' ) ) return array();
	$t = get_term_by( 'slug', $cat_slug, 'ymkrf_product_cat' );
	if ( ! $t || is_wp_error( $t ) ) return array();

	$map = ymkrf_maker_map();
	$k   = (int) $t->term_id;
	return ( isset( $map[ $k ] ) && is_array( $map[ $k ] ) ) ? array_map( 'intval', $map[ $k ] ) : array();
}

/** 設定の画面を開くURL */
function ymkrf_maker_admin_url( $cat = '' ) {
	$a = array( 'post_type' => 'ymkrf_product', 'page' => 'ymkrf-makers' );
	if ( $cat !== '' ) $a['cat'] = $cat;
	return add_query_arg( $a, admin_url( 'edit.php' ) );
}


/* ============================================================
   画面（商品 ＞ メーカーの設定）
   ============================================================ */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=ymkrf_product',
		'メーカーの設定', 'メーカーの設定',
		'manage_options', 'ymkrf-makers', 'ymkrf_maker_page'
	);
}, 42 );

/** 設定できる分類 */
function ymkrf_maker_cats_list() {

	$out = array();
	$ts  = get_terms( array( 'taxonomy' => 'ymkrf_product_cat', 'hide_empty' => false ) );
	if ( is_wp_error( $ts ) || ! $ts ) return $out;

	/* メーカーを使わない分類は出しません */
	$skip = array( 'pack4', 'outer-wall', 'interior' );

	foreach ( $ts as $t ) {
		if ( in_array( $t->slug, $skip, true ) ) continue;
		$out[ $t->slug ] = $t->name;
	}
	return $out;
}

function ymkrf_maker_page() {

	if ( ! current_user_can( 'manage_options' ) ) return;

	$cats = ymkrf_maker_cats_list();
	if ( ! $cats ) { echo '<div class="wrap"><h1>メーカーの設定</h1><p>商品カテゴリがありません。</p></div>'; return; }

	$keys = array_keys( $cats );
	$now  = isset( $_GET['cat'] ) ? sanitize_title( wp_unslash( $_GET['cat'] ) ) : $keys[0];
	if ( ! isset( $cats[ $now ] ) ) $now = $keys[0];

	$msg = '';

	/* ---------- 新しいメーカーを作る ---------- */
	if ( isset( $_POST['ymkrf_maker_new_nonce'] ) &&
	     wp_verify_nonce( $_POST['ymkrf_maker_new_nonce'], 'ymkrf_maker_new' ) ) {

		$name = trim( sanitize_text_field( wp_unslash( $_POST['ymkrf_maker_new'] ) ) );

		if ( $name === '' ) {
			$msg = 'メーカー名を入れてください。';
		} else {
			$t = get_term_by( 'name', $name, 'ymkrf_maker' );
			if ( ! $t ) {
				$r = wp_insert_term( $name, 'ymkrf_maker' );
				$t = ( ! is_wp_error( $r ) && isset( $r['term_id'] ) ) ? get_term( (int) $r['term_id'] ) : null;
			}
			if ( $t && ! is_wp_error( $t ) ) {
				$o = ymkrf_maker_opt();
				$list = isset( $o[ $now ] ) && is_array( $o[ $now ] ) ? array_map( 'intval', $o[ $now ] ) : ymkrf_maker_choices( $now );
				if ( ! in_array( (int) $t->term_id, $list, true ) ) $list[] = (int) $t->term_id;
				$o[ $now ] = $list;
				update_option( 'ymkrf_maker_cats', $o );
				$msg = '「' . $t->name . '」を作って、' . $cats[ $now ] . 'に入れました。';
			} else {
				$msg = 'メーカーを作れませんでした。';
			}
		}
	}

	/* ---------- チェックの保存 ---------- */
	if ( isset( $_POST['ymkrf_maker_nonce'] ) &&
	     wp_verify_nonce( $_POST['ymkrf_maker_nonce'], 'ymkrf_maker_save' ) ) {

		$in = ( isset( $_POST['ymkrf_makers'] ) && is_array( $_POST['ymkrf_makers'] ) )
			? array_map( 'intval', $_POST['ymkrf_makers'] ) : array();

		$o = ymkrf_maker_opt();
		$o[ $now ] = $in;
		update_option( 'ymkrf_maker_cats', $o );
		$msg = '保存しました。商品の登録画面にすぐ反映されます。';
	}

	/* ---------- もとにもどす ---------- */
	if ( isset( $_POST['ymkrf_maker_reset'] ) &&
	     isset( $_POST['ymkrf_maker_nonce_r'] ) &&
	     wp_verify_nonce( $_POST['ymkrf_maker_nonce_r'], 'ymkrf_maker_reset' ) ) {

		$o = ymkrf_maker_opt();
		unset( $o[ $now ] );
		update_option( 'ymkrf_maker_cats', $o );
		$msg = '自動（いま登録されている商品から調べる）にもどしました。';
	}

	$all  = get_terms( array( 'taxonomy' => 'ymkrf_maker', 'hide_empty' => false ) );
	if ( is_wp_error( $all ) ) $all = array();
	$pick = ymkrf_maker_choices( $now );
	$o    = ymkrf_maker_opt();
	$auto = ! isset( $o[ $now ] );
	?>
	<div class="wrap">
	  <h1>メーカーの設定</h1>

	  <?php if ( $msg ) : ?>
	    <div class="notice notice-success"><p><?php echo esc_html( $msg ); ?></p></div>
	  <?php endif; ?>

	  <p style="max-width:900px;font-size:13.5px;line-height:1.9">
	    商品の登録画面の「メーカー」に出すものを、<b>分類ごとに</b>決めます。<br>
	    えらびたいメーカーが一覧に無いときは、<b>このページの下でメーカーを作ってから</b>、
	    商品の登録画面にもどってえらんでください。
	  </p>

	  <h2 class="nav-tab-wrapper" style="margin-bottom:18px">
	    <?php foreach ( $cats as $k => $label ) : ?>
	      <a class="nav-tab <?php echo $k === $now ? 'nav-tab-active' : ''; ?>"
	         href="<?php echo esc_url( ymkrf_maker_admin_url( $k ) ); ?>"><?php echo esc_html( $label ); ?></a>
	    <?php endforeach; ?>
	  </h2>

	  <?php if ( $auto ) : ?>
	    <div class="notice notice-info inline" style="margin:0 0 16px">
	      <p>
	        いまは<b>自動</b>です（<?php echo esc_html( $cats[ $now ] ); ?>の商品に付いているメーカーが出ています）。<br>
	        下で保存すると、ここで決めた内容に切りかわります。
	      </p>
	    </div>
	  <?php endif; ?>

	  <form method="post">
	    <?php wp_nonce_field( 'ymkrf_maker_save', 'ymkrf_maker_nonce' ); ?>

	    <div style="max-width:900px;background:#fff;border:1px solid #dcdcde;padding:14px 18px;
	                display:grid;gap:8px;grid-template-columns:repeat(auto-fill,minmax(210px,1fr))">
	      <?php foreach ( $all as $t ) : ?>
	        <label style="display:flex;align-items:center;gap:7px;font-size:14px">
	          <input type="checkbox" name="ymkrf_makers[]" value="<?php echo (int) $t->term_id; ?>"
	                 <?php checked( in_array( (int) $t->term_id, $pick, true ) ); ?>>
	          <?php echo esc_html( $t->name ); ?>
	        </label>
	      <?php endforeach; ?>
	    </div>

	    <?php submit_button( 'この内容で保存する' ); ?>
	  </form>

	  <hr>

	  <h2 style="margin-bottom:6px">メーカーを新しく作る</h2>
	  <p class="description" style="margin:0 0 10px">
	    作ると、<b><?php echo esc_html( $cats[ $now ] ); ?></b>にチェックが入った状態になります。
	  </p>
	  <form method="post">
	    <?php wp_nonce_field( 'ymkrf_maker_new', 'ymkrf_maker_new_nonce' ); ?>
	    <input type="text" name="ymkrf_maker_new" class="regular-text" placeholder="例：ウッドワン">
	    <button class="button button-primary">作る</button>
	  </form>

	  <hr>

	  <form method="post"
	        onsubmit="return confirm('<?php echo esc_js( $cats[ $now ] ); ?>を、自動にもどしますか？');">
	    <?php wp_nonce_field( 'ymkrf_maker_reset', 'ymkrf_maker_nonce_r' ); ?>
	    <button class="button" name="ymkrf_maker_reset" value="1">自動にもどす</button>
	    <p class="description">
	      ここで決めた内容を取り消して、<b>その分類の商品に付いているメーカー</b>を自動で出す形にもどします。
	    </p>
	  </form>

	</div>
	<?php
}
