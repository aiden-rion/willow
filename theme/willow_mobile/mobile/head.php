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

<aside class="willow_desktop_brand">
    <a href="<?php echo G5_URL; ?>">
        <img src="<?php echo G5_IMG_URL; ?>/logo.png" alt="">
    </a>
    <strong>작은 기록이<br>서로의 힘이 되는 곳</strong>
    <p>윌로우에 쌓인 글과 작가의 이야기를 검색해보세요.</p>
    <form class="willow_desktop_search" action="<?php echo G5_BBS_URL; ?>/search.php" method="get">
        <input type="hidden" name="sfl" value="wr_subject||wr_content">
        <input type="hidden" name="sop" value="and">
        <label for="willow_desktop_search_keyword" class="sound_only">글 검색</label>
        <input id="willow_desktop_search_keyword" type="search" name="stx" maxlength="20" placeholder="글 검색">
        <button type="submit">검색</button>
    </form>
    <nav class="willow_desktop_keywords" aria-label="주요 키워드">
        <a href="<?php echo G5_BBS_URL; ?>/search.php?sfl=wr_subject%7C%7Cwr_content&amp;sop=and&amp;stx=<?php echo urlencode('북한생활'); ?>">#북한생활</a>
        <a href="<?php echo G5_BBS_URL; ?>/search.php?sfl=wr_subject%7C%7Cwr_content&amp;sop=and&amp;stx=<?php echo urlencode('정착상식'); ?>">#정착상식</a>
        <a href="<?php echo G5_BBS_URL; ?>/search.php?sfl=wr_subject%7C%7Cwr_content&amp;sop=and&amp;stx=<?php echo urlencode('마음돌봄'); ?>">#마음돌봄</a>
        <a href="<?php echo G5_BBS_URL; ?>/search.php?sfl=wr_subject%7C%7Cwr_content&amp;sop=and&amp;stx=<?php echo urlencode('가족'); ?>">#가족</a>
        <a href="<?php echo G5_BBS_URL; ?>/search.php?sfl=wr_subject%7C%7Cwr_content&amp;sop=and&amp;stx=<?php echo urlencode('인권'); ?>">#인권</a>
    </nav>
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
