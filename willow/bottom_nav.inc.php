<?php
if (!defined('_GNUBOARD_')) exit;

if (!empty($GLOBALS['willow_bottom_nav_rendered'])) {
    return;
}
$GLOBALS['willow_bottom_nav_rendered'] = true;

$willow_nav_script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
$willow_nav_home_active = preg_match('#/(index\.php)?$#', $willow_nav_script)
    || strpos($willow_nav_script, '/willow/today.php') !== false
    || strpos($willow_nav_script, '/willow/post.php') !== false;
$willow_nav_write_href = !empty($write_href)
    ? $write_href
    : ($is_member ? G5_URL.'/willow/write.php' : G5_BBS_URL.'/login.php?url='.urlencode(G5_URL.'/willow/write.php'));
$willow_nav_account_href = $is_member
    ? G5_URL.'/willow/menu.php'
    : G5_BBS_URL.'/login.php?url='.urlencode(G5_URL);
$willow_nav_items = array(
    array('key' => 'feed', 'label' => '홈', 'href' => G5_URL, 'active' => $willow_nav_home_active),
    array('key' => 'explore', 'label' => '검색', 'href' => G5_BBS_URL.'/search.php', 'active' => strpos($willow_nav_script, '/search.php') !== false),
    array('key' => 'write', 'label' => '글쓰기', 'href' => $willow_nav_write_href, 'active' => strpos($willow_nav_script, '/write.php') !== false),
    array('key' => 'like', 'label' => '좋아요', 'href' => G5_BBS_URL.'/scrap.php', 'active' => strpos($willow_nav_script, '/scrap.php') !== false),
    array('key' => 'account', 'label' => '계정설정', 'href' => $willow_nav_account_href, 'active' => strpos($willow_nav_script, '/willow/menu.php') !== false || strpos($willow_nav_script, '/willow/payment.php') !== false || strpos($willow_nav_script, '/member_confirm.php') !== false || strpos($willow_nav_script, '/register_form.php') !== false || strpos($willow_nav_script, '/login.php') !== false),
);
?>
<nav class="willow_bottom_nav" aria-label="WILLOW 주요 메뉴">
    <?php foreach ($willow_nav_items as $willow_nav_item) {
        $willow_nav_icon = $willow_nav_item['key'].($willow_nav_item['active'] ? '_on' : '').'.png';
    ?>
    <a class="<?php echo $willow_nav_item['active'] ? 'is_active' : ''; ?>" href="<?php echo $willow_nav_item['href']; ?>" aria-label="<?php echo $willow_nav_item['label']; ?>">
        <img class="willow_nav_icon" src="<?php echo G5_IMG_URL; ?>/<?php echo $willow_nav_icon; ?>" alt="">
    </a>
    <?php } ?>
</nav>
