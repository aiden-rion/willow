<?php
if (!defined('_GNUBOARD_')) exit;
include_once(G5_PATH.'/willow/notification.lib.php');
if ($is_member && willow_notification_has_new($member['mb_id'])) {
    echo '<span class="willow_notification_dot" aria-label="새 알림"></span>';
}
add_stylesheet('<style>a:has(> .willow_notification_dot){position:relative}.willow_notification_dot{position:absolute;top:2px;right:1px;width:7px;height:7px;border-radius:50%;background:#f33446;box-shadow:0 0 0 2px #fff;pointer-events:none}</style>', 20);
