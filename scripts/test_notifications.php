<?php
// CLI-only integration test. All application tables used below are shadowed by
// connection-local temporary tables; no member data or billing is changed.
if (PHP_SAPI !== 'cli') exit;
$_SERVER['HTTP_HOST'] = 'willlow.org';
$_SERVER['SERVER_PORT'] = 443;
$_SERVER['HTTPS'] = 'on';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require dirname(__DIR__).'/common.php';
require_once G5_PATH.'/willow/notification.lib.php';

function notification_test_check($condition, $message)
{
    if (!$condition) throw new RuntimeException($message);
}

$tables = willow_topic_tables();
$subscription_table = willow_subscription_table();
$event_table = willow_notification_event_table();
$setting_table = willow_notification_setting_table();
$board_table = willow_content_table();
$shadow_tables = array_merge(array_values($tables), array($subscription_table, $setting_table,
    $board_table, $g5['member_table'], $g5['board_file_table'], willow_moderation_table()));
foreach (array_unique($shadow_tables) as $table) {
    $definition = sql_fetch("show create table `{$table}`");
    $create = preg_replace('/^CREATE TABLE/', 'CREATE TEMPORARY TABLE', $definition['Create Table']);
    notification_test_check(sql_query($create, false), 'Could not isolate '.$table.': '.sql_error_info());
}
notification_test_check(sql_query("create temporary table `{$event_table}` (
    event_key varchar(64) primary key, recipient_mb_id varchar(20), actor_mb_id varchar(20),
    event_type varchar(30), event_datetime datetime)", false), 'Could not isolate events');
willow_notification_install();
willow_topic_install();

$now = G5_TIME_YMDHIS;
$next = date('Y-m-d', strtotime('+1 day', G5_SERVER_TIME));
sql_query("insert into `{$subscription_table}` (author_mb_id, subscriber_mb_id, ws_status, ws_next_billing_date, ws_datetime)
    values ('test_author', 'test_reader', 'active', '{$next}', '{$now}')");
$subscription = sql_fetch("select * from `{$subscription_table}` limit 1");
willow_notification_record_subscription($subscription, 'subscriber');
willow_notification_record_subscription($subscription, 'subscriber');
$author_items = willow_get_notifications('test_author');
notification_test_check(count(array_filter($author_items, function ($item) { return $item['type'] === 'subscriber'; })) === 1, 'Duplicate or missing signup notification');
notification_test_check(!willow_cancel_subscription('wrong_reader', 'test_author'), 'Unauthorized cancellation');
notification_test_check(willow_cancel_subscription('test_reader', 'test_author'), 'Cancellation failed');
notification_test_check(willow_cancel_subscription('test_reader', 'test_author'), 'Repeat cancellation failed');
$row = sql_fetch("select * from `{$subscription_table}` limit 1");
notification_test_check($row['ws_status'] === 'active' && $row['ws_next_billing_date'] === $next, 'Paid access was removed early');
$row = sql_fetch("select count(*) as cnt from `{$subscription_table}` where ws_status = 'active' and ws_cancelled_datetime is null");
notification_test_check((int) $row['cnt'] === 0, 'Cancelled subscription still eligible for billing');
$author_items = willow_get_notifications('test_author');
notification_test_check(count(array_filter($author_items, function ($item) { return $item['type'] === 'subscription_cancelled'; })) === 1, 'Duplicate or missing cancellation notification');
notification_test_check(count(array_filter($author_items, function ($item) { return $item['type'] === 'subscriber'; })) === 1, 'Cancellation removed signup history');

foreach (array('public', 'subscriber') as $access) {
    sql_query("insert into `{$tables['post']}`
        (wt_id, mb_id, wp_author, wp_subject, wp_content, wp_image, wp_access, wp_topic_mode, wp_category, wp_tags, wp_datetime)
        values (0, 'test_author', 'Test author', 'Test post', 'Test content', '', '{$access}', 'free', '', '', '{$now}')");
}
$post_id = sql_insert_id();
sql_query("insert into `{$tables['comment']}`
    (target_type, target_id, mb_id, wc_author, wc_content, wc_datetime)
    values ('topic', '{$post_id}', 'test_reader', 'Test reader', 'Test comment', '{$now}'),
           ('topic', '{$post_id}', 'test_author', 'Test author', 'Self comment', '{$now}')");
$reader_items = willow_get_notifications('test_reader');
$types = array_column($reader_items, 'type');
notification_test_check(in_array('subscribed_post', $types, true), 'Missing public post notification');
notification_test_check(in_array('membership_post', $types, true), 'Missing membership notification during paid access');
$author_items = willow_get_notifications('test_author');
notification_test_check(count(array_filter($author_items, function ($item) { return $item['type'] === 'comment'; })) === 1, 'Comment notification recipient/self filtering failed');
$settings = willow_notification_settings('test_reader');
$settings['membership_post'] = 0;
willow_notification_save_settings('test_reader', $settings);
notification_test_check(!in_array('membership_post', array_column(willow_get_notifications('test_reader'), 'type'), true), 'Notification opt-out ignored');
sql_query("insert into `".willow_moderation_table()."` (target_type, target_id, blocked, admin_id, updated_at)
    values ('topic', '{$post_id}', 1, '', '{$now}')");
$settings['membership_post'] = 1;
willow_notification_save_settings('test_reader', $settings);
notification_test_check(!in_array('membership_post', array_column(willow_get_notifications('test_reader'), 'type'), true), 'Blocked post appeared in notifications');
$settings['all'] = 0;
willow_notification_save_settings('test_reader', $settings);
notification_test_check(willow_get_notifications('test_reader') === array(), 'Global notification opt-out ignored');
$reactivated = $subscription;
$reactivated['ws_datetime'] = date('Y-m-d H:i:s', strtotime($now) + 1);
willow_notification_record_subscription($reactivated, 'subscriber');
$row = sql_fetch("select count(*) as cnt from `{$event_table}`");
notification_test_check((int) $row['cnt'] === 3, 'Reactivation lost subscription history');
echo "PASS: subscription/comment recipients, public/membership posts, cancellation history and deduplication, paid access, billing exclusion, settings\n";
