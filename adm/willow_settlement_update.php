<?php
$sub_menu = '700700';
require_once './_common.php';
include_once G5_PATH.'/willow/revenue.lib.php';

check_demo();
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();
willow_revenue_backfill_subscription_payments();

$action = isset($_POST['action']) ? preg_replace('/[^a-z_]/', '', $_POST['action']) : '';
$settlement_table = willow_settlement_request_table();
$now = G5_TIME_YMDHIS;

if ($action === 'request') {
    $authors = isset($_POST['author_mb_id']) && is_array($_POST['author_mb_id']) ? $_POST['author_mb_id'] : array();
    if (!$authors) {
        alert('정산요청을 생성할 작가를 선택해주세요.');
    }

    $balances = array();
    foreach (willow_revenue_author_balance_rows() as $row) {
        $balances[$row['author_mb_id']] = $row;
    }

    foreach ($authors as $author_mb_id) {
        $author_mb_id = trim($author_mb_id);
        if (!isset($balances[$author_mb_id]) || (int) $balances[$author_mb_id]['available_amount'] <= 0) {
            continue;
        }
        $row = $balances[$author_mb_id];
        sql_query(" insert into `{$settlement_table}`
            set author_mb_id = '".sql_escape_string($author_mb_id)."',
                wsr_amount = '".(int) $row['available_amount']."',
                wsr_status = 'requested',
                wsr_bank_name = '".sql_escape_string($row['mb_8'])."',
                wsr_account_holder = '".sql_escape_string($row['mb_9'])."',
                wsr_account_number = '".sql_escape_string($row['mb_10'])."',
                wsr_memo = '관리자 생성 정산요청',
                wsr_admin_memo = '',
                wsr_datetime = '{$now}',
                wsr_update_datetime = '{$now}' ", false);
    }

    goto_url('./willow_settlement.php?status=requested');
}

$wsr_id = isset($_POST['wsr_id']) ? (int) $_POST['wsr_id'] : 0;
$admin_memo = isset($_POST['admin_memo']) ? trim(strip_tags($_POST['admin_memo'])) : '';
if (!$wsr_id) {
    alert('처리할 정산요청을 찾을 수 없습니다.');
}

$request = sql_fetch(" select * from `{$settlement_table}` where wsr_id = '{$wsr_id}' ", false);
if (empty($request['wsr_id'])) {
    alert('정산요청을 찾을 수 없습니다.');
}

if ($action === 'paid') {
    if ($request['wsr_status'] !== 'requested') {
        alert('요청 상태의 정산만 완료 처리할 수 있습니다.');
    }

    $amount = (int) $request['wsr_amount'];
    if ($amount > 0) {
        insert_point(
            $request['author_mb_id'],
            -$amount,
            'WILLOW 작가 수익 정산 지급',
            'willow_settlement',
            (string) $request['wsr_id'],
            'paid'
        );
    }

    sql_query(" update `{$settlement_table}`
        set wsr_status = 'paid',
            wsr_admin_memo = '".sql_escape_string($admin_memo)."',
            wsr_update_datetime = '{$now}',
            wsr_processed_datetime = '{$now}',
            wsr_processed_mb_id = '".sql_escape_string($member['mb_id'])."'
        where wsr_id = '{$wsr_id}' ");
} else if ($action === 'rejected') {
    sql_query(" update `{$settlement_table}`
        set wsr_status = 'rejected',
            wsr_admin_memo = '".sql_escape_string($admin_memo)."',
            wsr_update_datetime = '{$now}',
            wsr_processed_datetime = '{$now}',
            wsr_processed_mb_id = '".sql_escape_string($member['mb_id'])."'
        where wsr_id = '{$wsr_id}' ");
} else if ($action === 'memo') {
    sql_query(" update `{$settlement_table}`
        set wsr_admin_memo = '".sql_escape_string($admin_memo)."',
            wsr_update_datetime = '{$now}'
        where wsr_id = '{$wsr_id}' ");
} else {
    alert('처리 방식이 올바르지 않습니다.');
}

goto_url('./willow_settlement.php?status=all');
