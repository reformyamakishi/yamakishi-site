<?php
/**
 * メディアのキーワード検索
 * リフォームヤマキシ（ymkrf）　2026/10/13
 *
 * ユーザー指示「メディアを検索する時、キーワードでも検索できるようにしてほしい。
 *              期間で検索は不要」
 *
 * 1. 「メディア」を押したときの画面（フォルダからえらぶ）の上に、検索の欄を出します。
 *    フォルダをえらんで、その中だけをさがすこともできます。
 * 2. さがす中身を広げます。WordPress のふつうの検索は「題名・キャプション・説明・ファイル名」
 *    だけですが、商品の写真などは **ALT（代わりの文字）** に商品名や色の名前が
 *    入っているので、ALT でも見つかるようにします。
 *    （写真をえらぶ小窓の検索にも効きます）
 * 3. 「すべての日付」（期間でしぼる）の欄を出しません。
 */

if ( ! defined( 'ABSPATH' ) ) exit;


/* ------------------------------------------------------------
   1. メディアの入り口に検索の欄
   ------------------------------------------------------------ */
add_action( 'all_admin_notices', function () {

	if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'ymkrf-media-folders' ) return;
	if ( ! current_user_can( 'upload_files' ) ) return;

	$kinds = function_exists( 'ymkrf_media_kinds' ) ? ymkrf_media_kinds() : array();
	?>
	<form method="get" action="<?php echo esc_url( admin_url( 'upload.php' ) ); ?>"
	      class="ymkrf-msearch"
	      style="margin:18px 20px 0 2px;padding:16px 18px;background:#fff;border:1px solid #dcdcde;border-radius:8px;
	             display:flex;flex-wrap:wrap;align-items:center;gap:10px">
		<input type="hidden" name="mode" value="list">
		<label for="ymkrf-msearch-s" style="font-weight:700;font-size:14px">キーワードでさがす</label>
		<input type="search" id="ymkrf-msearch-s" name="s" value=""
		       placeholder="例：Vスタイル　ホワイト　2601-0395"
		       style="min-width:320px;flex:1 1 320px;font-size:15px;padding:6px 10px">
		<?php if ( $kinds ) : ?>
			<select name="ymkrf_use" style="font-size:14px">
				<option value="">すべてのフォルダ</option>
				<?php foreach ( $kinds as $key => $k ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $k['name'] ); ?></option>
					<?php if ( function_exists( 'ymkrf_media_subkinds' ) ) foreach ( ymkrf_media_subkinds() as $sk => $sd ) :
						if ( $sd['parent'] !== $key ) continue; ?>
						<option value="<?php echo esc_attr( $sk ); ?>">　└ <?php echo esc_html( $sd['name'] ); ?></option>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</select>
		<?php endif; ?>
		<button type="submit" class="button button-primary" style="font-size:14px">さがす</button>
		<span class="description" style="flex-basis:100%">
			写真の題名・ファイル名・<b>ALT（代わりの文字）</b>・キャプション・説明からさがします。
			言葉をスペースで区切ると、すべてを含むものだけが出ます。
		</span>
	</form>
	<?php
} );


/* ------------------------------------------------------------
   2. ALT でも見つかるように
      （ライブラリの一覧・写真をえらぶ小窓の両方）
   ------------------------------------------------------------ */
add_filter( 'posts_search', function ( $search, $q ) {

	if ( $search === '' ) return $search;
	if ( ! is_admin() && ! wp_doing_ajax() ) return $search;

	$pt = $q->get( 'post_type' );
	if ( $pt !== 'attachment' && ! ( is_array( $pt ) && in_array( 'attachment', $pt, true ) ) ) return $search;

	$s = trim( (string) $q->get( 's' ) );
	if ( $s === '' ) return $search;

	global $wpdb;

	/* 言葉ごとに「題名など、または ALT・ファイル名」に含まれているかを見ます。
	   ふつうの検索とおなじく、すべての言葉を含むものだけにします。 */
	$terms = preg_split( '/[\s　]+/u', $s, -1, PREG_SPLIT_NO_EMPTY );
	$and   = array();
	foreach ( $terms as $t ) {
		$like  = '%' . $wpdb->esc_like( $t ) . '%';
		$and[] = $wpdb->prepare(
			"( {$wpdb->posts}.post_title LIKE %s
			   OR {$wpdb->posts}.post_excerpt LIKE %s
			   OR {$wpdb->posts}.post_content LIKE %s
			   OR EXISTS ( SELECT 1 FROM {$wpdb->postmeta} ymkms
			                WHERE ymkms.post_id = {$wpdb->posts}.ID
			                  AND ymkms.meta_key IN ( '_wp_attachment_image_alt', '_wp_attached_file' )
			                  AND ymkms.meta_value LIKE %s ) )",
			$like, $like, $like, $like
		);
	}
	return ' AND ( ' . implode( ' AND ', $and ) . ' ) ';
}, 20, 2 );


/* ------------------------------------------------------------
   3. 「すべての日付」（期間でしぼる）を出さない
   ------------------------------------------------------------ */
/* ライブラリの一覧（リスト表示） */
add_filter( 'disable_months_dropdown', function ( $disable, $post_type ) {
	return ( $post_type === 'attachment' ) ? true : $disable;
}, 10, 2 );

/* グリッド表示・写真をえらぶ小窓（月の一覧が空なら、欄そのものが出ません） */
add_filter( 'media_library_months_with_files', function () {
	return array();
} );

/* グリッド表示・写真をえらぶ小窓の「すべての日付」の欄を隠します
   （月の一覧を空にしても、欄の枠だけ残ることがあるため） */
add_action( 'admin_head', function () {
	echo '<style>#media-attachment-date-filters,label[for="media-attachment-date-filters"]{display:none!important}</style>';
} );
