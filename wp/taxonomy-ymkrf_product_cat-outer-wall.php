<?php
/**
 * taxonomy-ymkrf_product_cat-outer-wall.php ─ 外壁・屋根（/products/outer-wall/）
 *
 * 置き場所： wp-content/themes/ymkrf/taxonomy-ymkrf_product_cat-outer-wall.php
 *
 * ★このファイル名は WordPress の決まりごとです。
 *   「taxonomy-（分類名）-（スラッグ）.php」という名前にしておくと、
 *   その分類のページだけ、この1枚が使われます。
 *
 * ★★ ページの中身（HTML・CSS）は、このファイルにはありません。
 *     ダッシュボードの「商品 ＞ 外壁・屋根ページ」から直します。
 *     （2026/09/17 ユーザー指示
 *       「ダッシュボードの商品の外壁・屋根のぺージに、HTML・CSSを入れてほしい。
 *         そしたらサーバーの中に行かなくても他の従業員が修正できる」）
 *     中身を持っているのは inc/functions-outerwall.php です。
 *     ここは、パンくずと、その中身を出すことだけをしています。
 *
 * ── なぜ、ほかの分類とちがう作りにしているか ─────────────────
 * 外壁・屋根は「ヤマキシペイント（外壁・屋根サポートサイト）」という
 * 専門のサイトが別にあります。
 * 同じ内容を2つのサイトに置くと、検索エンジンがどちらを見せればよいか
 * 決められず、両方とも順位が上がりにくくなります。
 *
 * そこで、このページの役目は次の2つだけにしています。
 *   ① キッチンや給湯器を見に来た方に「外壁もヤマキシでできる」と知っていただく
 *   ② くわしく知りたい方を、専門サイトへお送りする
 * ─────────────────────────────────────────────────
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
?>

<!-- =========== パンくず =========== -->
<nav class="p-breadcrumb" aria-label="パンくずリスト">
  <ol class="p-breadcrumb__list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
    <li><a href="<?php echo esc_url( ymkrf_products_url() ); ?>">商品・価格</a></li>
    <li>外壁・屋根</li>
  </ol>
</nav>

<?php
/* ページの中身。直すのは「商品 ＞ 外壁・屋根ページ」です。
   出しているのは管理者が書いたHTMLなので、そのまま出します。 */
if ( function_exists( 'ymkrf_ow_render' ) ) {
	echo ymkrf_ow_render(); /* phpcs:ignore WordPress.Security.EscapeOutput */
}

get_footer();
