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
            <a href="javascript:history.back();" aria-label="뒤로가기"><img src="<?php echo G5_IMG_URL; ?>/ico_back.png" alt=""></a>
            <h2><?php echo get_head_title($g5['title']); ?></h2>
        </div>
    <?php } ?>
