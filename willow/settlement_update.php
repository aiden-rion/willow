<?php
include_once('./_common.php');
include_once('./revenue.lib.php');

if (!$is_member) {
    alert('로그인 후 이용해주세요.', G5_BBS_URL.'/login.php');
}

check_token();

$is_author = ((int) $member['mb_level'] >= 3) || (!empty($member['mb_2']) && $member['mb_2'] === 'author');
if (!$is_author) {
    alert('작가회원만 이용할 수 있습니다.', G5_URL.'/willow/menu.php');
}

willow_revenue_backfill_subscription_payments();
$balance = array();
foreach (willow_revenue_author_balance_rows() as $row) {
    if ($row['author_mb_id'] === $member['mb_id']) {
        $balance = $row;
        break;
    }
}

if (!$balance || (int) $balance['available_amount'] <= 0) {
    alert('정산 요청 가능한 금액이 없습니다.', G5_URL.'/willow/settlement.php');
}

$settlement_table = willow_settlement_request_table();
$now = G5_TIME_YMDHIS;
sql_query(" insert into `{$settlement_table}`
    set author_mb_id = '".sql_escape_string($member['mb_id'])."',
        wsr_amount = '".(int) $balance['available_amount']."',
        wsr_status = 'requested',
        wsr_bank_name = '".sql_escape_string($balance['mb_8'])."',
        wsr_account_holder = '".sql_escape_string($balance['mb_9'])."',
        wsr_account_number = '".sql_escape_string($balance['mb_10'])."',
        wsr_memo = '작가 정산요청',
        wsr_admin_memo = '',
        wsr_datetime = '{$now}',
        wsr_update_datetime = '{$now}' ", false);

alert('정산요청이 접수되었습니다.', G5_URL.'/willow/settlement.php');
