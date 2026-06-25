<?php
include_once('./_common.php');
include_once('./content.lib.php');

header('Content-Type: application/json; charset=utf-8');

function willow_report_json($success, $message, $extra = array())
{
    echo json_encode(array_merge(array(
        'success' => $success,
        'message' => $message,
    ), $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($member['mb_id'])) {
    willow_report_json(false, '로그인 후 신고할 수 있습니다.', array('login' => true));
}

$target_type = isset($_POST['target_type']) && $_POST['target_type'] === 'topic' ? 'topic' : 'board';
$target_id = isset($_POST['target_id']) ? (int) $_POST['target_id'] : 0;
$content = isset($_POST['content']) ? trim($_POST['content']) : '';
$content = preg_replace('/\s+/', ' ', $content);

if ($target_id < 1) {
    willow_report_json(false, '신고 대상을 찾을 수 없습니다.');
}

if ($content === '') {
    willow_report_json(false, '신고 내용을 입력해주세요.');
}

$content_length = function_exists('mb_strlen') ? mb_strlen($content, 'UTF-8') : strlen($content);
if ($content_length > 1000) {
    willow_report_json(false, '신고 내용은 1000자 이하로 입력해주세요.');
}

$target = willow_report_target_info($target_type, $target_id);
if (!$target) {
    willow_report_json(false, '신고 대상을 찾을 수 없습니다.');
}

if (!empty($target['author_mb_id']) && $target['author_mb_id'] === $member['mb_id']) {
    willow_report_json(false, '본인이 작성한 글은 신고할 수 없습니다.');
}

willow_report_install();
$table = willow_report_table();
$now = G5_TIME_YMDHIS;
$reporter_name = $member['mb_nick'] ? $member['mb_nick'] : $member['mb_name'];
$target_type_sql = sql_escape_string($target['target_type']);
$target_id_sql = (int) $target['target_id'];
$reporter_sql = sql_escape_string($member['mb_id']);
$content_sql = sql_escape_string($content);

$exists = sql_fetch(" select wrp_id
    from `{$table}`
    where wrp_target_type = '{$target_type_sql}'
        and wrp_target_id = '{$target_id_sql}'
        and wrp_reporter_mb_id = '{$reporter_sql}'
    limit 1 ", false);

if (!empty($exists['wrp_id'])) {
    sql_query(" update `{$table}`
        set wrp_content = '{$content_sql}',
            wrp_status = 'pending',
            wrp_update_datetime = '{$now}'
        where wrp_id = '".(int) $exists['wrp_id']."' ");
    willow_report_json(true, '신고 내용이 접수되었습니다.', array('report_id' => (int) $exists['wrp_id']));
}

sql_query(" insert into `{$table}`
    set wrp_target_type = '{$target_type_sql}',
        wrp_target_id = '{$target_id_sql}',
        wrp_target_title = '".sql_escape_string($target['title'])."',
        wrp_author_mb_id = '".sql_escape_string($target['author_mb_id'])."',
        wrp_author_name = '".sql_escape_string($target['author_name'])."',
        wrp_reporter_mb_id = '{$reporter_sql}',
        wrp_reporter_name = '".sql_escape_string($reporter_name)."',
        wrp_content = '{$content_sql}',
        wrp_status = 'pending',
        wrp_admin_memo = '',
        wrp_datetime = '{$now}',
        wrp_update_datetime = '{$now}' ");

willow_report_json(true, '신고 내용이 접수되었습니다.', array('report_id' => sql_insert_id()));
