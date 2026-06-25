<?php
$sub_menu = '700400';
require_once './_common.php';
include_once G5_PATH.'/willow/content.lib.php';

check_demo();
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();
willow_author_request_install();

$war_id = isset($_POST['war_id']) ? (int) $_POST['war_id'] : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : '';
$admin_memo = isset($_POST['admin_memo']) ? trim(strip_tags($_POST['admin_memo'])) : '';

if (!$war_id) {
    alert('처리할 요청을 찾을 수 없습니다.');
}

$table = willow_author_request_table();
$request = sql_fetch(" select * from `{$table}` where war_id = '{$war_id}' ", false);
if (empty($request['war_id'])) {
    alert('작가등록 요청을 찾을 수 없습니다.');
}

$now = G5_TIME_YMDHIS;
$reviewer = sql_escape_string($member['mb_id']);

if ($action === 'approve') {
    $mb = get_member($request['mb_id']);
    if (empty($mb['mb_id'])) {
        alert('요청 회원을 찾을 수 없습니다.');
    }

    $profile_image_url = willow_author_request_file_url($request['war_profile_image']);
    $profile_image_sql = $profile_image_url ? ", mb_6 = '".sql_escape_string($profile_image_url)."'" : '';
    $author_type = $request['war_is_escapee'] === 'yes' ? 'nk_migrant' : 'general_author';
    $level = max(3, (int) $mb['mb_level']);

    sql_query(" update {$g5['member_table']}
        set mb_level = '{$level}',
            mb_1 = '".sql_escape_string((string) $request['war_subscribe_price'])."',
            mb_2 = 'author',
            mb_3 = '".sql_escape_string($request['war_categories'])."',
            mb_7 = '".sql_escape_string($author_type)."',
            mb_8 = '".sql_escape_string($request['war_bank_name'])."',
            mb_9 = '".sql_escape_string($request['war_account_holder'])."',
            mb_10 = '".sql_escape_string($request['war_account_number'])."',
            mb_profile = '".sql_escape_string($request['war_intro'])."'
            {$profile_image_sql}
        where mb_id = '".sql_escape_string($request['mb_id'])."' ");

    sql_query(" update `{$table}`
        set war_status = 'approved',
            war_admin_memo = '".sql_escape_string($admin_memo)."',
            war_review_datetime = '{$now}',
            war_review_mb_id = '{$reviewer}',
            war_update_datetime = '{$now}'
        where war_id = '{$war_id}' ");
} else if ($action === 'reject') {
    sql_query(" update `{$table}`
        set war_status = 'rejected',
            war_admin_memo = '".sql_escape_string($admin_memo)."',
            war_review_datetime = '{$now}',
            war_review_mb_id = '{$reviewer}',
            war_update_datetime = '{$now}'
        where war_id = '{$war_id}' ");
} else if ($action === 'memo') {
    sql_query(" update `{$table}`
        set war_admin_memo = '".sql_escape_string($admin_memo)."',
            war_update_datetime = '{$now}'
        where war_id = '{$war_id}' ");
} else {
    alert('처리 방식이 올바르지 않습니다.');
}

goto_url('./willow_author_request.php?status=all');
