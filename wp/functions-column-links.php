<?php
/**
 * コラムのリンク点検 ─ リフォームヤマキシ
 *
 * 置き場所： wp-content/themes/ymkrf/inc/functions-column-links.php
 *
 * なにをするもの？
 *   旧ブログから持ってきたコラムの本文には、古いホームページのリンクが
 *   そのまま残っていることがあります。古いページはもう使わないので、
 *   放っておくとお客さまが行き止まりに当たってしまいます。
 *
 *   この画面は「どの記事に、どのリンクが入っているか」を数えて並べるだけです。
 *   書きかえはしません（見るだけ）。
 *
 *   ダッシュボード → コラム → リンクの点検
 *
 * 2026/09/25 ユーザー質問
 *   「コラムですが、旧サイトから持ってきておりますが、
 *     商品ぺージにリンクされている場合どうしたらよい？いまはlocalなんで」
 */
if ( ! defined( 'ABSPATH' ) ) exit;


/* ============================================================
   0. 「/」から始まるリンクを、いまの住所に合わせます

   旧ブログから持ってきたコラムには /products/bathroom/… のような
   「/」から始まるリンクが入っています。
   本番（ドメインの直下）ではそのまま効きますが、
   いまのローカルは /reform_yamakishi/ の下にあるため、
   そのままだと Not Found になります（2026/09/25 ユーザー指摘）。

   表示するときだけ、頭に /reform_yamakishi/ を足します。
   本文は書きかえません。本番ではサイトが直下にあるので、
   足すものが無く、これまでと同じ動きになります。
   ============================================================ */
add_filter( 'the_content', function ( $html ) {

	if ( ! is_singular( 'ymkrf_column' ) ) return $html;
	if ( strpos( $html, 'href="/' ) === false && strpos( $html, "href='/" ) === false ) return $html;

	$home = wp_parse_url( home_url( '/' ) );
	$base = isset( $home['path'] ) ? rtrim( $home['path'], '/' ) : '';
	if ( $base === '' ) return $html;   /* 本番はここで終わり（何もしません） */

	return preg_replace_callback(
		'#(href\s*=\s*)(["\'])(/[^"\']*)\2#i',
		function ( $m ) use ( $base ) {
			/* //example.com/… や、すでに /reform_yamakishi/ で始まるものは、そのまま */
			if ( strpos( $m[3], '//' ) === 0 )               return $m[0];
			if ( strpos( $m[3], $base . '/' ) === 0 )        return $m[0];
			if ( strpos( $m[3], '/wp-content/' ) === 0 )     return $m[0];
			return $m[1] . $m[2] . $base . $m[3] . $m[2];
		},
		$html
	);
}, 20 );


/* ============================================================
   1. 画面を足します
   ============================================================ */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=ymkrf_column',
		'リンクの点検',
		'リンクの点検',
		'edit_posts',
		'ymkrf-column-links',
		'ymkrf_column_links_page'
	);
}, 30 );


/* ============================================================
   2. リンクを集めます
   ============================================================ */

/**
 * コラムの本文から、リンク先をぜんぶ集めます。
 * 戻り値： array( URL => array( 'n' => 件数, 'posts' => array( 投稿ID … ) ) )
 */
function ymkrf_column_links_collect() {

	$ids = get_posts( array(
		'post_type'      => 'ymkrf_column',
		'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );

	$out = array();

	foreach ( $ids as $id ) {

		$html = (string) get_post_field( 'post_content', $id );
		if ( $html === '' ) continue;

		if ( ! preg_match_all( '/href\s*=\s*["\']([^"\']+)["\']/i', $html, $m ) ) continue;

		$seen = array();
		foreach ( $m[1] as $url ) {

			$url = trim( html_entity_decode( $url, ENT_QUOTES, 'UTF-8' ) );
			if ( $url === '' ) continue;

			/* 「#」だけのリンクは、行き先が空っぽです。これは点検の対象にします
			   （2026/09/25 ユーザー指摘。旧ブログの取り込みで行き先が落ちたものです） */
			if ( $url === '#' ) {
				if ( ! isset( $out['#'] ) ) $out['#'] = array( 'n' => 0, 'posts' => array() );
				if ( ! isset( $seen['#'] ) ) {
					$seen['#'] = 1;
					$out['#']['n']++;
					$out['#']['posts'][] = $id;
				}
				continue;
			}

			/* ページの中のジャンプ（#見出し）・メール・電話は、対象外です */
			if ( $url[0] === '#' ) continue;
			if ( stripos( $url, 'mailto:' ) === 0 ) continue;
			if ( stripos( $url, 'tel:' ) === 0 ) continue;
			if ( stripos( $url, 'javascript:' ) === 0 ) continue;

			if ( isset( $seen[ $url ] ) ) continue;   /* 同じ記事の中の重複は1つと数えます */
			$seen[ $url ] = 1;

			if ( ! isset( $out[ $url ] ) ) $out[ $url ] = array( 'n' => 0, 'posts' => array() );
			$out[ $url ]['n']++;
			$out[ $url ]['posts'][] = $id;
		}
	}

	return $out;
}

/**
 * リンクを、たちばで4つに分けます。
 *   now  … いまのサイトの中（このままでOK）
 *   old  … 古いホームページ・旧ブログ（直す必要あり）
 *   out  … よそのサイト（メーカーなど）
 *   etc  … 判断がつかないもの
 */
function ymkrf_column_link_kind( $url ) {

	$home = wp_parse_url( home_url( '/' ) );
	$host = isset( $home['host'] ) ? strtolower( $home['host'] ) : '';

	/* 行き先が空っぽ（# だけ） */
	if ( $url === '#' ) return 'empty';

	/* 形がこわれているもの（取り込みのときに、記号が混ざってしまったもの） */
	if ( preg_match( '/[;"\'<>\s]/', $url ) ) return 'bad';

	/* 「/」から始まるリンク。本番（ドメインの直下）では、そのまま効きます。
	   いまはローカルが /reform_yamakishi/ の下にあるので、ここでは開けません。 */
	if ( $url[0] === '/' ) {
		if ( strpos( $url, '//' ) === 0 ) {
			$u = wp_parse_url( 'https:' . $url );   /* //example.com/… の形 */
		} else {
			return 'rel';
		}
	} else {
		$u = wp_parse_url( $url );
	}

	if ( empty( $u['host'] ) ) return 'etc';

	$h = strtolower( $u['host'] );
	$h = preg_replace( '/^www\./', '', $h );

	if ( $h === preg_replace( '/^www\./', '', $host ) ) {

		/* 同じ入れ物の中でも、古いページの形なら「古い」に分けます */
		$p = isset( $u['path'] ) ? $u['path'] : '';
		if ( preg_match( '#\.html?$#i', $p ) )   return 'old';
		if ( strpos( $p, '/blog/' ) !== false )  return 'old';
		return 'now';
	}

	/* 本番のドメイン。いまはローカルで見ているので、別あつかいにします */
	if ( $h === 'yamakishi-reform.jp' ) {

		$p = isset( $u['path'] ) ? $u['path'] : '';
		if ( preg_match( '#\.html?$#i', $p ) )   return 'old';
		if ( strpos( $p, '/blog/' ) !== false )  return 'old';
		return 'old';   /* ドメイン付きで書いてあるものは、書きかえ対象にします */
	}

	return 'out';
}


/* ============================================================
   3. 画面
   ============================================================ */
function ymkrf_column_links_page() {

	if ( ! current_user_can( 'edit_posts' ) ) return;

	$all = ymkrf_column_links_collect();

	/* 「行き先をたしかめる」を押したとき。
	   いまのサイトに、そのページがあるかどうかを、実際に開いて調べます。 */
	if ( isset( $_POST['ymkrf_linkcheck'] ) && check_admin_referer( 'ymkrf_linkcheck' ) ) {
		$res = array();
		foreach ( array_keys( $all ) as $url ) {
			$k = ymkrf_column_link_kind( $url );
			if ( $k !== 'rel' && $k !== 'now' ) continue;

			$p = strtok( $url, '#' );
			$p = strtok( $p, '?' );
			$try = ( $k === 'rel' ) ? home_url( $p ) : $p;

			$r = wp_remote_get( $try, array( 'timeout' => 8, 'sslverify' => false ) );
			$res[ $url ] = is_wp_error( $r ) ? 0 : (int) wp_remote_retrieve_response_code( $r );
		}
		set_transient( 'ymkrf_col_linkcheck', $res, DAY_IN_SECONDS );
	}
	$checked = get_transient( 'ymkrf_col_linkcheck' );
	if ( ! is_array( $checked ) ) $checked = array();

	$box = array( 'empty' => array(), 'bad' => array(), 'old' => array(),
	              'rel' => array(), 'now' => array(), 'out' => array(), 'etc' => array() );
	foreach ( $all as $url => $d ) {
		$k = ymkrf_column_link_kind( $url );
		if ( ! isset( $box[ $k ] ) ) $k = 'etc';
		$box[ $k ][ $url ] = $d;
	}
	foreach ( $box as $k => $v ) {
		uasort( $box[ $k ], function ( $a, $b ) { return $b['n'] - $a['n']; } );
	}

	$label = array(
		'empty' => array( '行き先が空っぽのリンク（# だけ）', '#b32d2e',
		                  '押しても、どこにも行きません。旧ブログから持ってきたときに、行き先が落ちたものです。新しい商品ページにつなぎ直してください。' ),
		'bad'   => array( '形がこわれているリンク', '#b32d2e',
		                  '記号が混ざっています。押しても開けません。' ),
		'old'   => array( '古いホームページ・旧ブログへのリンク', '#b32d2e',
		                  'お客さまが行き止まりに当たります。新しいページに置きかえるか、リンクを外してください。' ),
		'rel'   => array( '「/」から始まるリンク', '#996800',
		                  '本番（yamakishi-reform.jp の直下）では、そのまま効きます。いまローカルは /reform_yamakishi/ の下にあるので、ここでは開けません。ただし、行き先のページが新しいサイトにあるかどうかは別の話なので、下の一覧でご確認ください。' ),
		'now'   => array( 'いまのサイトの中へのリンク', '#00733f',
		                  'このままで大丈夫です。本番に移すときに、住所はまとめて置きかわります。' ),
		'out'   => array( 'よそのサイトへのリンク', '#1d2327',
		                  'メーカーのページなどです。いまも開けるかどうかだけ、ときどき見てください。' ),
		'etc'   => array( 'その他', '#996800', '' ),
	);
	$order = array( 'empty', 'bad', 'old', 'rel', 'now', 'out', 'etc' );
	?>
	<div class="wrap">
		<h1>コラムのリンク点検</h1>

		<p style="max-width:48em;line-height:1.8">
			コラムの本文に書かれているリンクを、ぜんぶ数えて並べています。
			<b>この画面では、なにも書きかえません。</b>
			直すときは、記事の題名を押して本文を開いてください。
		</p>

		<form method="post" style="margin:14px 0 22px">
			<?php wp_nonce_field( 'ymkrf_linkcheck' ); ?>
			<button type="submit" name="ymkrf_linkcheck" value="1" class="button button-secondary">
				行き先をたしかめる（実際に開いてみます）
			</button>
			<span style="margin-left:10px;color:#50575e">
				<?php echo $checked ? '前回しらべた結果を出しています。' : 'まだしらべていません。少し時間がかかります。'; ?>
			</span>
		</form>

		<?php foreach ( $order as $k ) :
			$rows = $box[ $k ];
			$l    = $label[ $k ];
		?>
			<h2 style="margin-top:28px;color:<?php echo esc_attr( $l[1] ); ?>">
				<?php echo esc_html( $l[0] ); ?>
				<span style="font-weight:400">（<?php echo count( $rows ); ?> 種類）</span>
			</h2>
			<p style="margin-top:-6px;color:#50575e"><?php echo esc_html( $l[2] ); ?></p>

			<?php if ( ! $rows ) : ?>
				<p style="color:#50575e">ありません。</p>
			<?php else : ?>
				<table class="widefat striped" style="max-width:none">
					<thead>
						<tr>
							<th style="width:40%">リンク先</th>
							<th style="width:8em">開ける？</th>
							<th style="width:6em">記事数</th>
							<th>入っている記事</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $rows as $url => $d ) : ?>
						<tr>
							<td style="word-break:break-all"><code><?php echo esc_html( $url ); ?></code></td>
							<td>
								<?php
								if ( ! isset( $checked[ $url ] ) ) {
									echo '<span style="color:#a7aaad">—</span>';
								} elseif ( $checked[ $url ] === 200 ) {
									echo '<b style="color:#00733f">開けます</b>';
								} elseif ( $checked[ $url ] === 404 ) {
									echo '<b style="color:#b32d2e">ありません</b>';
								} else {
									echo '<b style="color:#996800">'
									   . esc_html( $checked[ $url ] ? $checked[ $url ] : 'つながらず' ) . '</b>';
								}
								?>
							</td>
							<td><?php echo (int) $d['n']; ?></td>
							<td>
								<?php
								$li = array();
								foreach ( array_slice( $d['posts'], 0, 12 ) as $pid ) {
									$li[] = '<a href="' . esc_url( get_edit_post_link( $pid ) ) . '">'
									      . esc_html( wp_trim_words( get_the_title( $pid ), 14, '…' ) ) . '</a>';
								}
								echo implode( '<br>', $li );
								if ( count( $d['posts'] ) > 12 ) {
									echo '<br><span style="color:#50575e">ほか '
									   . ( count( $d['posts'] ) - 12 ) . ' 記事</span>';
								}
								?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		<?php endforeach; ?>

		<h2 style="margin-top:32px">本番に移すときのこと</h2>
		<p style="max-width:48em;line-height:1.8">
			ワードプレスは、リンクの住所を「<?php echo esc_html( home_url( '/' ) ); ?>」のような
			まるごとの形で持ちます。本番に移すときは、
			<code><?php echo esc_html( untrailingslashit( home_url() ) ); ?></code> を
			<code>https://yamakishi-reform.jp</code> にまとめて置きかえます。
			引っ越しのときの決まった作業なので、いまローカルのリンクが入っていても心配いりません。
		</p>
		<p style="max-width:48em;line-height:1.8">
			まとめて置きかえても直らないのが、上のいちばん最初の
			<b>古いホームページ・旧ブログへのリンク</b>です。
			行き先のページそのものが無くなっているので、1本ずつ新しいページに
			つなぎ直すか、リンクを外す必要があります。
		</p>
	</div>
	<?php
}
