<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if(G5_COMMUNITY_USE === false) {
    include_once(G5_THEME_SHOP_PATH.'/shop.tail.php');
    return;
}
?>
    </div>
</div>

<?php
$willow_nav_script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
$willow_is_board_write_page = strpos($willow_nav_script, '/bbs/write.php') !== false;
?>
<?php if (!$willow_is_board_write_page) { ?>
<?php include G5_PATH.'/willow/bottom_nav.inc.php'; ?>
<?php } ?>

<?php
if ($config['cf_analytics']) {
    echo $config['cf_analytics'];
}

include_once(G5_THEME_PATH."/tail.sub.php");
