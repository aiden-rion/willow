<?php
include_once('./_common.php');
include_once('./revenue.lib.php');

if (!$is_member) {
    alert('로그인 후 이용해주세요.', G5_BBS_URL.'/login.php');
}

check_token();

if (!willow_revenue_is_author($member)) {
    alert('작가회원만 정산요청을 할 수 있습니다.', G5_BBS_URL.'/point.php');
}

$amount = isset($_POST['amount']) ? (int) preg_replace('/[^0-9]/', '', $_POST['amount']) : 0;
$bank_name = isset($_POST['bank_name']) ? trim(strip_tags($_POST['bank_name'])) : '';
$account_holder = isset($_POST['account_holder']) ? trim(strip_tags($_POST['account_holder'])) : '';
$account_number = isset($_POST['account_number']) ? trim(preg_replace('/[^0-9-]/', '', $_POST['account_number'])) : '';

$balance = willow_revenue_author_balance($member['mb_id']);
$available_point = isset($balance['available_amount']) ? (int) $balance['available_amount'] : 0;

if ($amount < 10000) {
    alert('정산요청은 10,000포인트 이상부터 가능합니다.', G5_URL.'/willow/point_settlement.php');
}

if ($amount % 10000 !== 0) {
    alert('정산요청 포인트는 만원 단위로 입력해주세요.', G5_URL.'/willow/point_settlement.php');
}

if ($amount > $available_point) {
    alert('정산가능 포인트를 초과했습니다.', G5_URL.'/willow/point_settlement.php');
}

if ($bank_name === '' || $account_holder === '' || $account_number === '') {
    alert('정산 계좌정보를 모두 입력해주세요.', G5_URL.'/willow/point_settlement.php');
}

$settlement_table = willow_settlement_request_table();
$now = G5_TIME_YMDHIS;

sql_query(" insert into `{$settlement_table}`
    set author_mb_id = '".sql_escape_string($member['mb_id'])."',
        wsr_amount = '{$amount}',
        wsr_status = 'requested',
        wsr_bank_name = '".sql_escape_string($bank_name)."',
        wsr_account_holder = '".sql_escape_string($account_holder)."',
        wsr_account_number = '".sql_escape_string($account_number)."',
        wsr_memo = '사용자 정산요청',
        wsr_admin_memo = '',
        wsr_datetime = '{$now}',
        wsr_update_datetime = '{$now}' ", false);

$wsr_id = sql_insert_id();
goto_url(G5_URL.'/willow/point_settlement_complete.php?wsr_id='.(int) $wsr_id);
