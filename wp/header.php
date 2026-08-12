<?php
/**
 * header.php ─ 全ページ共通のヘッダー（リフォームヤマキシ）
 * index.html のヘッダー部分を WordPress 用に置き換えたものです。
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$asset = get_stylesheet_directory_uri();
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="format-detection" content="telephone=no">
<meta name="theme-color" content="#fe3301">

<!-- ファビコン一式 -->
<link rel="icon" href="<?php echo esc_url( home_url( '/favicon.ico' ) ); ?>" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo esc_url( $asset ); ?>/assets/img/favicon/icon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?php echo esc_url( $asset ); ?>/assets/img/favicon/icon-16x16.png">
<link rel="icon" type="image/png" sizes="48x48" href="<?php echo esc_url( $asset ); ?>/assets/img/favicon/icon-48x48.png">
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url( $asset ); ?>/assets/img/favicon/apple-touch-icon-180x180.png">
<link rel="manifest" href="<?php echo esc_url( $asset ); ?>/assets/img/favicon/manifest.json">
<meta name="msapplication-TileColor" content="#fe3301">
<meta name="msapplication-TileImage" content="<?php echo esc_url( $asset ); ?>/assets/img/favicon/site-tile-150x150.png">

<!-- キャッチコピー用の書体を、先につなぎに行っておきます（表示が速くなります） -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php wp_head(); /* CSS/JS は functions.php の wp_enqueue_scripts から読み込まれます */ ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main">本文へスキップ</a>

<!-- お知らせ帯（管理画面から変えたい場合はカスタマイザーの設定値に差し替えてください） -->
<div class="p-notice">
  住宅省エネ2026キャンペーン 受付中／窓リフォーム 最大100万円・エコキュート 最大12万円
  <a href="<?php echo esc_url( home_url( '/#subsidy' ) ); ?>">くわしく見る</a>
</div>

<header class="p-header">
  <div class="p-header__inner">

    <?php if ( is_front_page() ) : ?><h1 class="p-logo-wrap"><?php endif; ?>
    <a class="p-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
      <img class="p-logo__img" src="<?php echo esc_url( $asset ); ?>/assets/img/logo/logo01.png"
           width="696" height="162" alt="リフォームヤマキシ｜リフォーム＆増改築（株式会社山岸）">
    </a>
    <?php if ( is_front_page() ) : ?></h1><?php endif; ?>

    <nav class="p-gnav" aria-label="メインメニュー">
      <?php
      /* 管理画面「外観 › メニュー」で “global” に登録したメニューを出します。
         未登録なら下の固定リンクが表示されます。 */
      if ( has_nav_menu( 'global' ) ) {
        wp_nav_menu( array(
          'theme_location' => 'global',
          'container'      => false,
          'menu_class'     => 'p-gnav__list',
          'depth'          => 1,
        ) );
      } else { ?>
        <ul class="p-gnav__list">
          <li><a href="<?php echo esc_url( home_url( '/products/' ) ); ?>">商品・価格</a></li>
          <li><a href="<?php echo esc_url( home_url( '/works/' ) ); ?>">施工事例</a></li>
          <li><a href="<?php echo esc_url( home_url( '/voice/' ) ); ?>">お客様の声</a></li>
          <li><a href="<?php echo esc_url( home_url( '/shops/' ) ); ?>">店舗・エリア</a></li>
          <li><a href="<?php echo esc_url( home_url( '/flow/' ) ); ?>">リフォームの流れ</a></li>
          <li><a href="<?php echo esc_url( home_url( '/faq/' ) ); ?>">よくある質問</a></li>
        </ul>
      <?php } ?>
    </nav>

    <div class="p-header__cta">
      <a class="p-header__tel" href="tel:0800-777-3331" data-cta="header">
        <small>お電話でのご相談（通話無料）</small>
        <strong>0800-777-3331</strong>
      </a>
      <a class="c-btn c-btn--line" href="https://lin.ee/UJZuSTrz" rel="noopener" data-cta="header">LINEで見積り</a>
      <a class="c-btn" href="<?php echo esc_url( home_url( '/inquiry/webrsv/' ) ); ?>" data-cta="header">来店予約</a>
    </div>

    <button class="p-hamburger" type="button" aria-expanded="false" aria-controls="drawer" aria-label="メニューを開く">
      <span class="p-hamburger__box"><span></span><span></span><span></span></span>
      <span class="p-hamburger__label">MENU</span>
    </button>
  </div>

  <?php if ( is_front_page() ) : ?>
  <!-- スマホ用 横スクロールナビ（トップページのみ） -->
  <nav class="p-subnav" aria-label="ページ内メニュー">
    <ul class="p-subnav__list">
      <li><a href="#sim">かんたん見積り</a></li>
      <li><a href="#price">パック価格</a></li>
      <li><a href="#menu">メニュー</a></li>
      <li><a href="#works">施工事例</a></li>
      <li><a href="#reason">選ばれる理由</a></li>
      <li><a href="#voice">お客様の声</a></li>
      <li><a href="#shops">店舗・エリア</a></li>
      <li><a href="#faq">よくある質問</a></li>
    </ul>
  </nav>
  <?php endif; ?>
</header>

<!-- ドロワーメニュー（スマホ） -->
<div class="p-drawer" id="drawer">
  <div class="p-drawer__overlay"></div>
  <div class="p-drawer__panel" role="dialog" aria-modal="true" aria-label="メニュー">
    <button class="p-drawer__close" type="button" aria-label="メニューを閉じる">×</button>

    <p class="p-drawer__title">まずはご相談</p>
    <div class="p-drawer__cta">
      <a class="c-btn c-btn--line c-btn--block" href="https://lin.ee/UJZuSTrz" rel="noopener" data-cta="drawer">LINEで無料見積り</a>
      <a class="c-btn c-btn--block" href="tel:0800-777-3331" data-cta="drawer">0800-777-3331</a>
      <a class="c-btn c-btn--ghost c-btn--block" href="<?php echo esc_url( home_url( '/inquiry/webrsv/' ) ); ?>" data-cta="drawer">ネット来店予約</a>
    </div>

    <p class="p-drawer__title">リフォームメニュー</p>
    <?php
    if ( has_nav_menu( 'drawer' ) ) {
      wp_nav_menu( array(
        'theme_location' => 'drawer',
        'container'      => false,
        'menu_class'     => 'p-drawer__list',
        'depth'          => 1,
      ) );
    } else { ?>
      <ul class="p-drawer__list">
        <li><a href="<?php echo esc_url( home_url( '/products/kitchen/' ) ); ?>">キッチン</a></li>
        <li><a href="<?php echo esc_url( home_url( '/products/bathroom/' ) ); ?>">お風呂・ユニットバス</a></li>
        <li><a href="<?php echo esc_url( home_url( '/products/toilet/' ) ); ?>">トイレ</a></li>
        <li><a href="<?php echo esc_url( home_url( '/products/lavatory/' ) ); ?>">洗面化粧台</a></li>
        <li><a href="<?php echo esc_url( home_url( '/products/boiler/' ) ); ?>">給湯器・エコキュート</a></li>
        <li><a href="<?php echo esc_url( home_url( '/products/outer-wall/' ) ); ?>">外壁・屋根</a></li>
        <li><a href="<?php echo esc_url( home_url( '/products/exterior/' ) ); ?>">エクステリア</a></li>
        <li><a href="<?php echo esc_url( home_url( '/products/interior/' ) ); ?>">内装・窓・断熱</a></li>
      </ul>
    <?php } ?>
  </div>
</div>
