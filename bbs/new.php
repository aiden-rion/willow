<?php
include_once('./_common.php');
include_once(G5_PATH.'/willow/notification.lib.php');

$g5['title'] = '알림';
$willow_notifications = array();
$willow_notification_policies = willow_notification_policies();

if ($is_member) {
    $willow_notifications = willow_get_notifications($member['mb_id'], 50);
}

add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/willow_mobile.css?ver='.G5_CSS_VER.'">', 10);

include_once('./_head.php');
include_once($new_skin_path.'/new.skin.php');
include_once('./_tail.php');
