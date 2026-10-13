<?php
/**
 * 【1回だけ使う】旧型アンケートのQ＆Aを流し込む
 * リフォームヤマキシ（ymkrf）　2026/10/13
 *
 * 旧型（A4たて「あなたのお声をおきかせください！」）169件の手書きを
 * 文字起こししたものが wp-content/ymkrf-qa.json に入っています。
 * それを各お客様の声の「アンケートのQ＆A」（_ymkrf_qa）に入れます。
 *
 * ・どのお客様の声に入れるかは、アンケート画像のファイル名で決めます
 *   （案件番号のない 2942 のようなものも、これで確実につながります）。
 *   画像で見つからないときだけ、URLの末尾（案件番号）でさがします。
 * ・**すでにQ＆Aが入っているものには入れません**（手で打ったものを守るため）。
 * ・入れたものには _ymkrf_qa_auto = 1 の印をつけます。
 *
 * ★終わったら、このファイルと functions.php の読み込み行、
 *   wp-content/ymkrf-qa.json を消してください。
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'YMKRF_QA_JSON', WP_CONTENT_DIR . '/ymkrf-qa.json' );

add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=ymkrf_voice',
		'旧型Q＆Aの取り込み',
		'旧型Q＆Aの取り込み',
		'edit_posts',
		'ymkrf-qa-import',
		'ymkrf_qa_import_page'
	);
} );

/* お客様の声の画面のいちばん上に、取り込み画面への案内を出します
   （2026/10/13 ユーザー「どこにある？」）。取り込みが終わったら出なくなります。 */
add_action( 'admin_notices', function () {
	if ( get_option( 'ymkrf_qa_imported' ) ) return;
	if ( ! current_user_can( 'edit_posts' ) ) return;
	$s = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $s || $s->post_type !== 'ymkrf_voice' ) return;
	if ( isset( $_GET['page'] ) && $_GET['page'] === 'ymkrf-qa-import' ) return;

	$url = admin_url( 'edit.php?post_type=ymkrf_voice&page=ymkrf-qa-import' );
	echo '<div class="notice notice-info" style="border-left-color:#fe3301"><p style="font-size:14px">'
	   . '<b>旧型アンケート（A4たて）169件の手書きQ＆Aを、まとめて取り込めます。</b>　'
	   . '<a class="button button-primary" href="' . esc_url( $url ) . '">取り込み画面をひらく</a>'
	   . '</p></div>';
} );

/** JSONを読みます */
function ymkrf_qa_import_load() {
	if ( ! file_exists( YMKRF_QA_JSON ) ) return null;
	$d = json_decode( (string) file_get_contents( YMKRF_QA_JSON ), true );
	return is_array( $d ) ? $d : null;
}

/** 1件ぶんの行き先（お客様の声のID）をさがします */
function ymkrf_qa_import_find( $row ) {

	global $wpdb;

	$file = isset( $row['file'] ) ? (string) $row['file'] : '';
	$case = isset( $row['case'] ) ? (string) $row['case'] : '';

	/* ① 画像のファイル名から（いちばん確実） */
	if ( $file !== '' ) {
		$base = preg_replace( '/\.[a-z]+$/i', '', $file );   /* 拡張子は jpg／png のどちらもあるので外す */
		$atts = $wpdb->get_col( $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta}
			  WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s",
			'%/' . $wpdb->esc_like( $base ) . '.%'
		) );
		foreach ( $atts as $att ) {
			$vid = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT pm.post_id FROM {$wpdb->postmeta} pm
				   JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				  WHERE pm.meta_key IN ( '_ymkrf_survey_pub_id', '_ymkrf_survey_id' )
				    AND pm.meta_value = %s
				    AND p.post_type = 'ymkrf_voice' AND p.post_status <> 'trash'
				  LIMIT 1",
				(string) (int) $att
			) );
			if ( $vid ) return $vid;
		}
	}

	/* ② だめなら、URLの末尾（案件番号）で */
	if ( $case !== '' ) {
		$slug = strtolower( preg_replace( '/[^0-9A-Za-z-]/', '', $case ) );
		$p = get_posts( array(
			'post_type'   => 'ymkrf_voice', 'name' => $slug,
			'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids',
		) );
		if ( $p ) return (int) $p[0];
	}
	return 0;
}

function ymkrf_qa_import_page() {

	if ( ! current_user_can( 'edit_posts' ) ) wp_die( '権限がありません' );

	$data = ymkrf_qa_import_load();
	echo '<div class="wrap"><h1>旧型アンケートのQ＆Aを取り込む</h1>';

	if ( $data === null ) {
		echo '<p>wp-content/ymkrf-qa.json が見つかりません。</p></div>';
		return;
	}

	$ran = false; $done = array(); $skip = array(); $miss = array(); $empty = array();

	if ( isset( $_POST['ymkrf_qa_go'] )
	  && check_admin_referer( 'ymkrf_qa_import', 'ymkrf_qa_nonce' ) ) {

		$ran = true;
		foreach ( $data as $row ) {
			$label = isset( $row['case'] ) ? (string) $row['case'] : '';
			$qa    = isset( $row['qa'] ) && is_array( $row['qa'] ) ? $row['qa'] : array();

			$out = array();
			foreach ( $qa as $r ) {
				$q = isset( $r['q'] ) ? trim( sanitize_textarea_field( $r['q'] ) ) : '';
				$a = isset( $r['a'] ) ? trim( sanitize_textarea_field( $r['a'] ) ) : '';
				if ( $q === '' || $a === '' ) continue;
				$out[] = array( 'q' => $q, 'a' => $a );
			}
			if ( ! $out ) { $empty[] = $label; continue; }

			$vid = ymkrf_qa_import_find( $row );
			if ( ! $vid ) { $miss[] = $label; continue; }

			$have = get_post_meta( $vid, '_ymkrf_qa', true );
			if ( is_array( $have ) && $have ) { $skip[] = array( $label, $vid ); continue; }

			update_post_meta( $vid, '_ymkrf_qa', $out );
			update_post_meta( $vid, '_ymkrf_qa_auto', '1' );
			update_post_meta( $vid, '_ymkrf_oldform', '1' );   /* 念のため（旧型の画面にします） */
			$done[] = array( $label, $vid );
		}
	}

	if ( $ran ) {
		/* 取り込みがすんだので、上の案内はもう出しません */
		update_option( 'ymkrf_qa_imported', current_time( 'mysql' ), false );

		printf( '<div class="notice notice-success"><p><b>%d件</b>に入れました。</p></div>', count( $done ) );

		echo '<h2>入れなかったもの</h2><ul style="list-style:disc;margin-left:1.5em">';
		printf( '<li>すでに手で打ってあったので、そのままにしたもの：%d件 %s</li>',
			count( $skip ),
			implode( '、', array_map( function ( $s ) {
				return '<a href="' . esc_url( get_edit_post_link( $s[1] ) ) . '" target="_blank" rel="noopener">' . esc_html( $s[0] ) . '</a>';
			}, $skip ) )
		);
		printf( '<li>手書きが無かったもの：%d件 %s</li>', count( $empty ), esc_html( implode( '、', $empty ) ) );
		printf( '<li>行き先のお客様の声が見つからなかったもの：%d件 %s</li>', count( $miss ), esc_html( implode( '、', $miss ) ) );
		echo '</ul>';

		if ( $done ) {
			echo '<h2>入れたもの（押すと編集画面がひらきます）</h2><p style="line-height:2">';
			foreach ( $done as $dn ) {
				echo '<a href="' . esc_url( get_edit_post_link( $dn[1] ) ) . '" target="_blank" rel="noopener">' . esc_html( $dn[0] ) . '</a>　';
			}
			echo '</p>';
		}
		echo '</div>';
		return;
	}

	$n = 0; $rows = 0;
	foreach ( $data as $row ) { if ( ! empty( $row['qa'] ) ) { $n++; $rows += count( $row['qa'] ); } }
	?>
	<p>文字起こしずみの旧型アンケート <b><?php echo (int) count( $data ); ?>件</b>
	（うち手書きあり <b><?php echo (int) $n; ?>件</b>・<?php echo (int) $rows; ?>問）を、
	お客様の声の「アンケートのQ＆A」に入れます。</p>
	<ul style="list-style:disc;margin-left:1.5em;line-height:1.9">
		<li><b>すでにQ＆Aを手で打ってあるものには入れません。</b>そのまま残ります。</li>
		<li>お客様の書かれたとおりに打ってあります。読めなかった字は「〇」です。</li>
		<li>入れたあとも、ふつうに編集画面から直せます。</li>
	</ul>
	<form method="post">
		<?php wp_nonce_field( 'ymkrf_qa_import', 'ymkrf_qa_nonce' ); ?>
		<p><button type="submit" name="ymkrf_qa_go" value="1" class="button button-primary button-hero">取り込む</button></p>
	</form>
	</div>
	<?php
}
