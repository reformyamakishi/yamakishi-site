<?php
/**
 * functions-shops.php ─ 店舗の情報（1か所にまとめています）
 *
 * 置き場所： wp-content/themes/ymkrf/inc/functions-shops.php
 *
 * ★店舗の情報を直すときは、このファイルだけを直してください。
 *   店舗・対応エリアのページ（/shops/）と、
 *   お見積り・お問い合わせのページ（/inquiry/）の両方に反映されます。
 *
 *   slug     … スタッフ紹介のしぼり込みに使う英字
 *   hours    … 営業時間／hnote … 営業時間の但し書き
 *   areas    … 主に担当する市町
 *   sr       … ショールームがあるか
 *   srnote   … ショールームについての但し書き
 *   ld       … 検索エンジン用の営業時間（Mo=月 Tu=火 We=水 Th=木 Fr=金 Sa=土 Su=日）
 *   soon     … まだ開いていないお店
 *   topic    … そのお店の新しいお知らせ（外観写真の上に吹き出しで出ます）
 *   pos      … 外観写真の見せたい位置（例 '50% 22%'。空なら中央）
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'ymkrf_shops' ) ) :
function ymkrf_shops() {
	return array(

	/* ---------------- 石川県 ---------------- */
	array(
		'pref' => '石川県', 'slug' => 'higashikanazawa', 'name' => '東金沢店',
		'tel'  => '',
		'addr' => '石川県金沢市大樋町1番地4',
		'hours' => '', 'hnote' => '',
		'closed' => '',
		'areas' => array( '金沢市' ),
		'sr' => false, 'srnote' => '',
		'ld' => array(),
		'soon' => '2026年10月31日（土）グランドオープン！',
		'pos'  => '50% 22%',   /* 写真の見せたい位置（上のほうを見せます） */
	),
	array(
		'pref' => '石川県', 'slug' => 'tazuruhama', 'name' => '田鶴浜店',
		'tel'  => '0767-68-6600',
		'addr' => '石川県七尾市高田町ほ部34番地',
		'hours' => '8:00〜21:00', 'hnote' => '',
		'closed' => '年中無休',
		'areas' => array( '七尾市', '志賀町（一部）' ),
		'sr' => true,
		'srnote' => '',
		'ld' => array( 'Mo-Su 08:00-21:00' ),
		'topic' => '2026年10月 リフォームコーナーが復興オープン！',
	),
	array(
		'pref' => '石川県', 'slug' => 'shinkaga', 'name' => '新加賀店',
		'tel'  => '0761-74-0017',
		'addr' => '石川県加賀市桑原町ホ48-1',
		'hours' => '8:00〜21:00', 'hnote' => '',
		'closed' => '年中無休',
		'areas' => array( '加賀市' ),
		'sr' => true, 'srnote' => '',
		'ld' => array( 'Mo-Su 08:00-21:00' ),
		'topic' => '2026年10月 リフォームコーナーがリニューアル！',
	),
	array(
		'pref' => '石川県', 'slug' => 'hakui', 'name' => '羽咋店',
		'tel'  => '0767-23-4747',
		'addr' => '石川県羽咋市鶴多町五石高34-1',
		'hours' => '10:00〜18:00', 'hnote' => '毎週(金)は17:00閉店',
		'closed' => '年中無休 ※盆・年末年始を除く',
		'areas' => array( '羽咋市', '志賀町', '中能登町', '宝達志水町' ),
		'sr' => true, 'srnote' => '',
		'ld' => array( 'Mo-Th 10:00-18:00', 'Fr 10:00-17:00', 'Sa-Su 10:00-18:00' ),
	),
	array(
		'pref' => '石川県', 'slug' => 'tagami', 'name' => '金沢田上店',
		'tel'  => '076-213-6331',
		'addr' => '石川県金沢市田上さくら2丁目14',
		'hours' => '10:00〜17:00', 'hnote' => '',
		'closed' => '年中無休 ※盆・年末年始を除く',
		'areas' => array( '金沢市' ),
		'sr' => true, 'srnote' => 'エコキュートと外壁塗装の専門店です。',
		'ld' => array( 'Mo-Su 10:00-17:00' ),
	),
	array(
		'pref' => '石川県', 'slug' => 'nonoichi', 'name' => '金沢野々市店',
		'tel'  => '076-294-6101',
		'addr' => '石川県野々市市本町6丁目12-70',
		'hours' => '10:00〜18:00', 'hnote' => '毎週(水)は17:00閉店',
		'closed' => '年中無休 ※盆・年末年始を除く',
		'areas' => array( '金沢市', '野々市市', '白山市' ),
		'sr' => true, 'srnote' => '',
		'ld' => array( 'Mo-Tu 10:00-18:00', 'We 10:00-17:00', 'Th-Su 10:00-18:00' ),
	),
	array(
		'pref' => '石川県', 'slug' => 'kawakita', 'name' => '川北店',
		'tel'  => '076-277-7550',
		'addr' => '石川県能美郡川北町三反田中216-1',
		'hours' => '8:00〜22:00', 'hnote' => '',
		'closed' => '年中無休',
		'areas' => array( '川北町', '白山市', '能美市（一部）' ),
		'sr' => true, 'srnote' => '',
		'ld' => array( 'Mo-Su 08:00-22:00' ),
	),
	array(
		'pref' => '石川県', 'slug' => 'komathu', 'name' => '小松店',
		'tel'  => '0761-23-3636',
		'addr' => '石川県小松市長田町イ4-1',
		'hours' => '10:00〜18:00', 'hnote' => '毎週(木)は17:00閉店',
		'closed' => '年中無休 ※盆・正月を除く',
		'areas' => array( '小松市', '能美市' ),
		'sr' => true, 'srnote' => '',
		'ld' => array( 'Mo-We 10:00-18:00', 'Th 10:00-17:00', 'Fr-Su 10:00-18:00' ),
	),

	/* ---------------- 福井県 ---------------- */
	array(
		'pref' => '福井県', 'slug' => 'kanadu', 'name' => '金津店',
		'tel'  => '0776-73-1015',
		'addr' => '福井県あわら市大溝1丁目8-13',
		'hours' => '8:00〜19:00', 'hnote' => '',
		'closed' => '年中無休 ※元旦を除く',
		'areas' => array( 'あわら市', '坂井市' ),
		'sr' => true, 'srnote' => '株式会社山岸の本社があるお店です。',
		'ld' => array( 'Mo-Su 08:00-19:00' ),
	),
	array(
		'pref' => '福井県', 'slug' => 'kahahothu', 'name' => '開発店',
		'tel'  => '0776-54-9939',
		'addr' => '福井県福井市開発町9字5-1',
		'hours' => '9:00〜19:00', 'hnote' => '',
		'closed' => '年中無休 ※元旦を除く',
		'areas' => array( '福井市', '永平寺町' ),
		'sr' => false, 'srnote' => '',
		'ld' => array( 'Mo-Su 09:00-19:00' ),
	),
	array(
		'pref' => '福井県', 'slug' => 'asahi', 'name' => '朝日店',
		'tel'  => '0778-34-8885',
		'addr' => '福井県丹生郡越前町乙坂39字1番',
		'hours' => '7:00〜21:30', 'hnote' => 'ホームセンター資材館（灯油販売を含む）は21:00まで',
		'closed' => '年中無休',
		'areas' => array( '越前町', '越前市', '鯖江市' ),
		'sr' => true, 'srnote' => '',
		'ld' => array( 'Mo-Su 07:00-21:30' ),
	),
	);
}
endif;
