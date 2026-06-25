<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$willow_topbar_open = !empty($willow_topbar_open);
$willow_topbar_menu_href = $willow_topbar_open ? G5_URL : G5_URL.'/willow/menu.php';
$willow_topbar_menu_label = $willow_topbar_open ? '닫기' : '메뉴';
$willow_topbar_menu_class = 'willow_menu_toggle';
if ($willow_topbar_open) {
    $willow_topbar_menu_class .= ' willow_menu_toggle_open willow_menu_close';
}
?>

<div class="willow_topbar">
    <a class="willow_brand" href="<?php echo G5_URL; ?>" aria-label="WILLOW 홈">
        <img src="<?php echo G5_IMG_URL; ?>/m_logo.png" alt="WILLOW">
    </a>
    <div class="willow_top_actions">
        <a href="<?php echo G5_BBS_URL; ?>/new.php" aria-label="알림">
            <img class="willow_header_icon_img" src="<?php echo G5_IMG_URL; ?>/ico_alert.png" alt="">
        </a>
        <a class="<?php echo $willow_topbar_menu_class; ?>" href="<?php echo $willow_topbar_menu_href; ?>" aria-label="<?php echo $willow_topbar_menu_label; ?>">
            <?php if (!$willow_topbar_open) { ?>
            <img class="willow_header_icon_img" src="<?php echo G5_IMG_URL; ?>/ico_menu.png" alt="">
            <?php } ?>
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
        </a>
    </div>
</div>

<?php if (!$willow_topbar_open) { ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var menuToggle = document.querySelector('.willow_menu_toggle[href*="/willow/menu.php"]');
    if (!menuToggle) {
        return;
    }

    menuToggle.addEventListener('click', function (event) {
        if (event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
            return;
        }

        event.preventDefault();
        menuToggle.classList.add('is-opening');
        window.setTimeout(function () {
            window.location.href = menuToggle.href;
        }, 180);
    });
});
</script>
<?php } ?>
