<?php
/**
 * footer.php ─ 全ページ共通のフッター（リフォームヤマキシ）
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$asset = get_stylesheet_directory_uri();
$home  = home_url( '/' );
?>

<footer class="p-footer">
  <div class="l-wrap">
    <div class="p-footer__grid">

      <div class="p-footer__col">
        <h3>リフォームメニュー</h3>
        <ul>
          <li><a href="<?php echo esc_url( ymkrf_cat_url( 'kitchen' ) ); ?>">キッチンリフォーム</a></li>
          <li><a href="<?php echo esc_url( ymkrf_cat_url( 'bathroom' ) ); ?>">お風呂・ユニットバス</a></li>
          <li><a href="<?php echo esc_url( ymkrf_cat_url( 'toilet' ) ); ?>">トイレリフォーム</a></li>
          <li><a href="<?php echo esc_url( ymkrf_cat_url( 'lavatory' ) ); ?>">洗面化粧台</a></li>
          <li><a href="<?php echo esc_url( ymkrf_cat_url( 'boiler' ) ); ?>">給湯器・エコキュート</a></li>
          <li><a href="<?php echo esc_url( ymkrf_cat_url( 'outer-wall' ) ); ?>">外壁塗装・屋根</a></li>
          <li><a href="<?php echo esc_url( ymkrf_cat_url( 'exterior' ) ); ?>">エクステリア</a></li>
          <li><a href="<?php echo esc_url( ymkrf_cat_url( 'interior' ) ); ?>">内装・窓・断熱</a></li>
        </ul>
      </div>

      <div class="p-footer__col">
        <h3>ヤマキシについて</h3>
        <ul>
          <li><a href="<?php echo esc_url( $home . 'company/' ); ?>">会社概要</a></li>
          <li><a href="<?php echo esc_url( $home . 'about/' ); ?>">こだわり・特徴</a></li>
          <li><a href="<?php echo esc_url( $home . 'about/' ); ?>#system">施工体制</a></li>
          <li><a href="<?php echo esc_url( $home . 'warranty/' ); ?>">保証について</a></li>
          <li><a href="<?php echo esc_url( $home . 'staff/' ); ?>">スタッフ紹介</a></li>
          <li><a href="<?php echo esc_url( $home . 'message/' ); ?>">代表あいさつ</a></li>
          <li><a href="<?php echo esc_url( $home . 'recruit/' ); ?>">職人・スタッフ募集</a></li>
          <li><a href="<?php echo esc_url( $home . 'privacy/' ); ?>">プライバシーポリシー</a></li>
        </ul>
      </div>

      <div class="p-footer__col">
        <h3>コンテンツ</h3>
        <ul>
          <li><a href="<?php echo esc_url( get_post_type_archive_link( 'works' ) ); ?>">施工事例</a></li>
          <li><a href="<?php echo esc_url( get_post_type_archive_link( 'voice' ) ); ?>">お客様の声</a></li>
          <li><a href="<?php echo esc_url( $home . 'shops/' ); ?>">店舗・対応エリア</a></li>
          <li><a href="<?php echo esc_url( $home . 'flow/' ); ?>">リフォームの流れ</a></li>
          <li><a href="<?php echo esc_url( $home . 'news/' ); ?>">お知らせ</a></li>
          <li><a href="<?php echo esc_url( $home . 'flyer/' ); ?>">イベント・チラシ</a></li>
          <li><a href="<?php echo esc_url( $home . 'column/' ); ?>">コラム・お役立ち情報</a></li>
          <li><a href="<?php echo esc_url( $home . 'faq/' ); ?>">よくあるご質問</a></li>
        </ul>
      </div>

      <div class="p-footer__col">
        <h3>お問い合わせ</h3>
        <address class="p-footer__company">
          株式会社山岸（リフォームヤマキシ）<br>
          <a class="p-footer__tel" href="tel:0800-777-3331" data-cta="footer">0800-777-3331</a><br>
          石川県・福井県に11店舗
        </address>
        <ul style="margin-top:12px">
          <li><a href="<?php echo esc_url( $home . 'inquiry/webrsv/' ); ?>">ネット来店予約</a></li>
          <li><a href="<?php echo esc_url( $home . 'inquiry/' ); ?>">お見積り・お問い合わせ</a></li>
          <li><a href="https://lin.ee/UJZuSTrz" rel="noopener">LINE公式アカウント</a></li>
          <li><a href="https://www.facebook.com/yamakishi.reform/" rel="noopener">Facebook</a></li>
        </ul>
        <ul style="margin-top:12px">
          <li><a href="https://yamakishi-paint.jp/" rel="noopener">外壁・屋根の専門サイト</a></li>
          <li><a href="http://www.yamakishi-solar.biz/" rel="noopener">太陽光発電サポート</a></li>
          <li><a href="https://www.yamakishi-f.com/" rel="noopener">不動産情報</a></li>
        </ul>
      </div>

    </div>
    <p class="p-footer__copy">&copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> 株式会社山岸 リフォームヤマキシ</p>
  </div>
</footer>

<!-- 追従CTA（スマホ） -->
<nav class="p-fixcta" aria-label="お問い合わせ">
  <a class="p-fixcta__btn p-fixcta__btn--line" href="https://lin.ee/UJZuSTrz" rel="noopener" data-cta="fixed">
    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C6.5 2 2 5.6 2 10.1c0 4 3.6 7.4 8.4 8 .3.1.8.2.9.5.1.3.1.7 0 1l-.1.9c0 .3-.2 1 .9.6 1.1-.5 6-3.5 8.2-6C21.7 13.5 22 11.9 22 10.1 22 5.6 17.5 2 12 2z"/></svg>
    LINEで見積り
  </a>
  <a class="p-fixcta__btn p-fixcta__btn--tel" href="tel:0800-777-3331" data-cta="fixed">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/></svg>
    電話する
  </a>
  <a class="p-fixcta__btn p-fixcta__btn--rsv" href="<?php echo esc_url( $home . 'inquiry/webrsv/' ); ?>" data-cta="fixed">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
    来店予約
  </a>
</nav>

<button class="p-pagetop" type="button" aria-label="ページの先頭にもどる"></button>

<?php wp_footer(); ?>
</body>
</html>
