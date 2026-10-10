<?php
if (!defined('_GNUBOARD_')) exit;

if (defined('WILLOW_DESKTOP_BRAND_RENDERED')) {
    return;
}
define('WILLOW_DESKTOP_BRAND_RENDERED', true);

$willow_desktop_keywords = array('북한생활', '정착상식', '마음돌봄', '가족', '인권');
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
        <?php foreach ($willow_desktop_keywords as $willow_desktop_keyword) { ?>
        <a href="<?php echo G5_BBS_URL; ?>/search.php?sfl=wr_subject%7C%7Cwr_content&amp;sop=and&amp;stx=<?php echo urlencode($willow_desktop_keyword); ?>">#<?php echo get_text($willow_desktop_keyword); ?></a>
        <?php } ?>
    </nav>
</aside>
