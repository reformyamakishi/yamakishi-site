<?php
/**
 * functions-product-koji.php ─ 標準工事内容の設定
 * 置き場所： wp-content/themes/ymkrf/inc/functions-product-koji.php
 *
 * （2026/09/17 ユーザー指示
 *   「ヤマキシ標準工事内容は、商品登録ぺージには必要ないわ。
 *     ここは登録するだけのぺージにして。
 *     ここ変更する時、その他設定でできないかな？」）
 *
 * ■ どういうことか
 *   商品ページに出る「標準工事費にふくまれる工事」は、
 *   もともと商品1つずつに同じ内容を書き写していました。
 *   でも中身はカテゴリごとに同じなので、
 *   **カテゴリごとに1回だけ決める**ようにしました。
 *
 *   商品の登録画面からは、この欄をなくしました。
 *   直すのは「商品 ＞ 標準工事内容の設定」です。
 *
 * ■ 商品ページに出ないと困る？
 *   困りません。お客様のページに出ているのは、もともとカテゴリごとの内容です。
 *   商品1つずつに書いてあった内容は、ページには使われていませんでした。
 *
 * ■ 名前について
 *   保存さき … ymkrf_koji（カテゴリの英字をキーにした箱）
 */
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * 設定できるカテゴリ。
 * 標準工事費の欄をもっているカテゴリだけを出します。
 * （2026/09/17 ユーザー「外壁に該当する商品はありません」）
 *
 * ymkrf_pointnote() に中身があるものだけが出るので、
 * あとから増やしたときも、ここを直さずに出てきます。
 */
function ymkrf_koji_cats() {

	$maybe = array( 'kitchen', 'bathroom', 'toilet', 'lavatory',
	                'boiler', 'ecocute', 'interior', 'window', 'outer-wall' );

	$out = array();
	foreach ( $maybe as $slug ) {
		$d = ymkrf_pointnote( $slug );
		if ( ! $d ) continue;                       /* 標準工事費の欄が無いカテゴリは出しません */
		$out[ $slug ] = ymkrf_cat_label( $slug, $slug );
	}
	return $out;
}

/** 設定の中身（空なら、もとの内容がそのまま使われます） */
function ymkrf_koji_opt() {
	$o = get_option( 'ymkrf_koji' );
	return is_array( $o ) ? $o : array();
}

/**
 * もとの内容に、設定画面で直したものを重ねます。
 * 直していないところは、もとのままです。
 */
function ymkrf_koji_over( $slug, $base ) {

	$o = ymkrf_koji_opt();
	if ( empty( $o[ $slug ] ) || ! is_array( $o[ $slug ] ) ) return $base;

	$my = $o[ $slug ];

	foreach ( array( 'label', 'note', 'note2', 'itemsttl' ) as $k ) {
		if ( isset( $my[ $k ] ) && $my[ $k ] !== '' ) $base[ $k ] = $my[ $k ];
	}
	if ( isset( $my['price'] ) && $my['price'] !== '' ) $base['price'] = (int) $my['price'];

	/* 工事の一覧。アイコンは、もとの一覧から工事名で引きつぎます */
	if ( ! empty( $my['items'] ) && is_array( $my['items'] ) ) {

		$icon = array();
		foreach ( (array) ( isset( $base['items'] ) ? $base['items'] : array() ) as $it ) {
			if ( ! empty( $it['name'] ) ) $icon[ $it['name'] ] = isset( $it['icon'] ) ? $it['icon'] : '';
		}

		$rows = array();
		foreach ( $my['items'] as $it ) {
			$name = trim( (string) ( isset( $it['name'] ) ? $it['name'] : '' ) );
			if ( $name === '' ) continue;
			$rows[] = array(
				'name' => $name,
				'sub'  => trim( (string) ( isset( $it['sub'] ) ? $it['sub'] : '' ) ),
				'icon' => isset( $icon[ $name ] ) ? $icon[ $name ] : 'check',
			);
		}
		if ( $rows ) $base['items'] = $rows;
	}

	return $base;
}

/** 1行「工事名｜説明」の文字列 ⇔ 配列 */
function ymkrf_koji_text_to_items( $text ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
		$line = trim( $line );
		if ( $line === '' ) continue;
		$p = preg_split( '/\s*[|｜]\s*/u', $line, 2 );
		$out[] = array(
			'name' => trim( $p[0] ),
			'sub'  => isset( $p[1] ) ? trim( $p[1] ) : '',
		);
	}
	return $out;
}

function ymkrf_koji_items_to_text( $items ) {
	$out = array();
	foreach ( (array) $items as $it ) {
		$n = isset( $it['name'] ) ? $it['name'] : '';
		$s = isset( $it['sub'] )  ? $it['sub']  : '';
		if ( $n === '' ) continue;
		$out[] = $s !== '' ? $n . '｜' . $s : $n;
	}
	return implode( "\n", $out );
}


/* ------------------------------------------------------------
   設定の画面（商品 ＞ 標準工事内容の設定）
   ------------------------------------------------------------ */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=ymkrf_product',
		'標準工事内容の設定', '標準工事内容の設定',
		'manage_options', 'ymkrf-koji', 'ymkrf_koji_page'
	);
}, 40 );

function ymkrf_koji_page() {

	if ( ! current_user_can( 'manage_options' ) ) return;

	$cats = ymkrf_koji_cats();
	$now  = isset( $_GET['cat'] ) ? sanitize_title( wp_unslash( $_GET['cat'] ) ) : 'kitchen';
	if ( ! isset( $cats[ $now ] ) ) $now = 'kitchen';

	$msg = '';

	/* 保存 */
	if ( isset( $_POST['ymkrf_koji_nonce'] ) &&
	     wp_verify_nonce( $_POST['ymkrf_koji_nonce'], 'ymkrf_koji_save' ) ) {

		$o = ymkrf_koji_opt();

		$o[ $now ] = array(
			'label'    => sanitize_text_field( wp_unslash( $_POST['koji_label'] ) ),
			'price'    => preg_replace( '/[^0-9]/', '', (string) wp_unslash( $_POST['koji_price'] ) ),
			'note'     => sanitize_text_field( wp_unslash( $_POST['koji_note'] ) ),
			'note2'    => sanitize_text_field( wp_unslash( $_POST['koji_note2'] ) ),
			'itemsttl' => sanitize_text_field( wp_unslash( $_POST['koji_itemsttl'] ) ),
			'items'    => ymkrf_koji_text_to_items( wp_unslash( $_POST['koji_items'] ) ),
		);

		update_option( 'ymkrf_koji', $o );
		$msg = '保存しました。商品ページにすぐ反映されます。';
	}

	/* もとにもどす */
	if ( isset( $_POST['ymkrf_koji_reset'] ) &&
	     isset( $_POST['ymkrf_koji_nonce_r'] ) &&
	     wp_verify_nonce( $_POST['ymkrf_koji_nonce_r'], 'ymkrf_koji_reset' ) ) {

		$o = ymkrf_koji_opt();
		unset( $o[ $now ] );
		update_option( 'ymkrf_koji', $o );
		$msg = 'もとの内容にもどしました。';
	}

	$d = ymkrf_pointnote( $now );
	?>
	<div class="wrap">
	  <h1>標準工事内容の設定</h1>

	  <?php if ( $msg ) : ?>
	    <div class="notice notice-success"><p><?php echo esc_html( $msg ); ?></p></div>
	  <?php endif; ?>

	  <p style="max-width:900px;font-size:13.5px;line-height:1.9">
	    商品ページと商品一覧に出る<b>「標準工事費にふくまれる工事」</b>を、ここで決めます。<br>
	    <b>カテゴリごとに1回だけ決めれば、そのカテゴリの商品すべてに出ます。</b>
	    商品を1つずつ直す必要はありません。
	  </p>

	  <h2 class="nav-tab-wrapper" style="margin-bottom:18px">
	    <?php foreach ( $cats as $k => $label ) : ?>
	      <a class="nav-tab <?php echo $k === $now ? 'nav-tab-active' : ''; ?>"
	         href="<?php echo esc_url( add_query_arg( array(
	           'post_type' => 'ymkrf_product', 'page' => 'ymkrf-koji', 'cat' => $k,
	         ), admin_url( 'edit.php' ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
	    <?php endforeach; ?>
	  </h2>

	  <?php if ( ! $d ) : ?>
	    <div class="notice notice-warning"><p>
	      このカテゴリには、まだ標準工事費の欄がありません。
	    </p></div>
	  <?php else : ?>

	  <form method="post">
	    <?php wp_nonce_field( 'ymkrf_koji_save', 'ymkrf_koji_nonce' ); ?>
	    <table class="form-table">

	      <tr>
	        <th>見出し</th>
	        <td>
	          <input type="text" name="koji_label" class="large-text"
	                 value="<?php echo esc_attr( isset( $d['label'] ) ? $d['label'] : '' ); ?>">
	          <p class="description">例：キッチンの標準工事費</p>
	        </td>
	      </tr>

	      <tr>
	        <th>標準工事費（円・税込）</th>
	        <td>
	          <input type="number" name="koji_price" class="regular-text"
	                 value="<?php echo esc_attr( isset( $d['price'] ) ? $d['price'] : '' ); ?>">
	          <p class="description">数字だけ入れてください。</p>
	        </td>
	      </tr>

	      <tr>
	        <th>説明</th>
	        <td>
	          <input type="text" name="koji_note" class="large-text"
	                 value="<?php echo esc_attr( isset( $d['note'] ) ? $d['note'] : '' ); ?>">
	        </td>
	      </tr>

	      <tr>
	        <th>小さい但し書き</th>
	        <td>
	          <input type="text" name="koji_note2" class="large-text"
	                 value="<?php echo esc_attr( isset( $d['note2'] ) ? $d['note2'] : '' ); ?>">
	          <p class="description">例：※お家の形状により追加がかかる場合は…</p>
	        </td>
	      </tr>

	      <tr>
	        <th>一覧の見出し</th>
	        <td>
	          <input type="text" name="koji_itemsttl" class="large-text"
	                 value="<?php echo esc_attr( isset( $d['itemsttl'] ) ? $d['itemsttl'] : '' ); ?>">
	          <p class="description">
	            <code>|</code> は「スマホではここで改行」の目印です。
	          </p>
	        </td>
	      </tr>

	      <tr>
	        <th>ふくまれる工事</th>
	        <td>
	          <textarea name="koji_items" rows="14" class="large-text code"><?php
	            echo esc_textarea( ymkrf_koji_items_to_text( isset( $d['items'] ) ? $d['items'] : array() ) );
	          ?></textarea>
	          <p class="description">
	            <b>1行に1つ</b>書いてください。工事名と説明は「<b>｜</b>」で区切ります。<br>
	            例：<code>水道工事｜給水・給湯・排水の工事です。</code><br>
	            説明がいらないときは、工事名だけでかまいません。<br>
	            アイコンは工事名から自動で選ばれます。新しい工事名にはチェックの印が付きます。
	          </p>
	        </td>
	      </tr>

	    </table>
	    <?php submit_button( 'この内容で保存する' ); ?>
	  </form>

	  <hr>

	  <form method="post"
	        onsubmit="return confirm('<?php echo esc_js( $cats[ $now ] ); ?>の内容を、もとにもどしますか？');">
	    <?php wp_nonce_field( 'ymkrf_koji_reset', 'ymkrf_koji_nonce_r' ); ?>
	    <button class="button" name="ymkrf_koji_reset" value="1">もとの内容にもどす</button>
	    <p class="description">ここで直したものを取り消して、はじめの内容にもどします。</p>
	  </form>

	  <?php endif; ?>
	</div>
	<?php
}
