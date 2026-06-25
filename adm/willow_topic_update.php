<?php
$sub_menu = '700100';
require_once './_common.php';
include_once G5_PATH.'/willow/topic.lib.php';

check_demo();
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();
willow_topic_install();

$tables = willow_topic_tables();

$wt_id = isset($_POST['wt_id']) ? (int) $_POST['wt_id'] : 0;
$wt_subject = isset($_POST['wt_subject']) ? trim(strip_tags($_POST['wt_subject'])) : '';
$wt_date = isset($_POST['wt_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['wt_date']) ? $_POST['wt_date'] : G5_TIME_YMD;
$wt_publish_date = isset($_POST['wt_publish_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['wt_publish_date']) ? $_POST['wt_publish_date'] : $wt_date;
$wt_publish_time = isset($_POST['wt_publish_time']) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $_POST['wt_publish_time']) ? $_POST['wt_publish_time'] : '00:00';
$wt_publish_datetime = $wt_publish_date.' '.$wt_publish_time.':00';
$wt_participants = isset($_POST['wt_participants']) ? max(0, (int) $_POST['wt_participants']) : 0;
$wt_description = isset($_POST['wt_description']) ? trim(strip_tags($_POST['wt_description'])) : '';
$wt_active = isset($_POST['wt_active']) ? 1 : 0;

if (!$wt_subject) {
    alert('오늘의 주제를 입력해 주세요.');
}

$now = G5_TIME_YMDHIS;
if ($wt_id) {
    $topic = sql_fetch(" select wt_id from `{$tables['topic']}` where wt_id = '{$wt_id}' ", false);
    if (empty($topic['wt_id'])) {
        alert('수정할 주제를 찾을 수 없습니다.');
    }

    sql_query(" update `{$tables['topic']}`
        set wt_subject = '".sql_escape_string($wt_subject)."',
            wt_date = '".sql_escape_string($wt_date)."',
            wt_publish_datetime = '".sql_escape_string($wt_publish_datetime)."',
            wt_participants = '{$wt_participants}',
            wt_description = '".sql_escape_string($wt_description)."',
            wt_active = '{$wt_active}'
        where wt_id = '{$wt_id}' ");
} else {
    sql_query(" insert into `{$tables['topic']}`
        set wt_subject = '".sql_escape_string($wt_subject)."',
            wt_date = '".sql_escape_string($wt_date)."',
            wt_publish_datetime = '".sql_escape_string($wt_publish_datetime)."',
            wt_participants = '{$wt_participants}',
            wt_description = '".sql_escape_string($wt_description)."',
            wt_active = '{$wt_active}',
            wt_datetime = '{$now}' ");
    $wt_id = sql_insert_id();
}

goto_url('./willow_topic.php?wt_id='.(int) $wt_id);
