<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if(G5_COMMUNITY_USE === false) {
    define('G5_IS_COMMUNITY_PAGE', true);
    include_once(G5_THEME_SHOP_PATH.'/shop.head.php');
    return;
}

include_once(G5_THEME_PATH.'/head.sub.php');
include_once(G5_LIB_PATH.'/latest.lib.php');
?>

<aside class="willow_desktop_brand" aria-hidden="true">
    <a href="<?php echo G5_URL; ?>" tabindex="-1">
        <img src="<?php echo G5_IMG_URL; ?>/logo.png" alt="">
    </a>
    <strong>이야기를 잇다,<br>willow</strong>
    <p>Where voices take root,<br>and stories become strength.</p>
</aside>
<aside class="willow_desktop_copy" aria-hidden="true">
    <span>WILLOW</span>
    <strong>작은 기록이<br>서로의 힘이 되는 곳</strong>
    <p>오늘의 주제, 작가 구독, 댓글과 공감까지 모바일 앱 화면 그대로 경험하세요.</p>
    <div class="willow_desktop_preview">
        <img src="<?php echo G5_IMG_URL; ?>/splash_005.png" alt="">
        <img src="<?php echo G5_IMG_URL; ?>/splash_001.png" alt="">
        <img src="<?php echo G5_IMG_URL; ?>/splash_008.png" alt="">
    </div>
</aside>

<header id="hd" class="willow_shell_header">
    <?php
    $willow_topbar_open = !empty($willow_topbar_open);
    include G5_THEME_PATH.'/willow_topbar.php';
    ?>
</header>

<div id="wrapper" class="willow_shell">
    <div id="container" class="willow_container">
    <?php if (!defined("_INDEX_")) { ?>
        <div class="willow_page_title">
            <?php $willow_page_back_url = !empty($willow_page_back_url) ? $willow_page_back_url : 'javascript:history.back();'; ?>
            <a href="<?php echo $willow_page_back_url; ?>" aria-label="뒤로가기"><img src="<?php echo G5_IMG_URL; ?>/ico_back.png" alt=""></a>
            <h2><?php echo get_head_title($g5['title']); ?></h2>
        </div>
    <?php } ?>
