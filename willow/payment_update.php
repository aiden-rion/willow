<?php
include_once('./_common.php');
include_once('./payment.lib.php');
include_once(G5_LIB_PATH.'/subscription.lib.php');

if (!$is_member) {
    alert('로그인 후 이용해주세요.', G5_BBS_URL.'/login.php');
}

$return_url = isset($_GET['return']) ? trim($_GET['return']) : (isset($_POST['return']) ? trim($_POST['return']) : G5_URL.'/willow/menu.php');
if ($return_url === '') {
    $return_url = G5_URL.'/willow/menu.php';
}
check_url_host($return_url);

$auth_key = isset($_GET['authKey']) ? trim($_GET['authKey']) : '';
$customer_key = isset($_GET['customerKey']) ? trim($_GET['customerKey']) : '';
$expected_customer_key = willow_toss_customer_key($member['mb_id']);

if ($auth_key === '' || $customer_key === '') {
    alert('토스페이먼츠 카드 인증 정보가 없습니다.', G5_URL.'/willow/payment.php?step=toss&return='.urlencode($return_url));
}

if (!hash_equals($expected_customer_key, $customer_key)) {
    alert('카드 인증 사용자 정보가 일치하지 않습니다.', G5_URL.'/willow/payment.php?return='.urlencode($return_url));
}

$issue = willow_toss_issue_billing_key($auth_key, $customer_key);
if (empty($issue['success'])) {
    $message = isset($issue['message']) ? $issue['message'] : '빌링키 발급에 실패했습니다.';
    alert($message, G5_URL.'/willow/payment.php?step=toss&return='.urlencode($return_url));
}

$result = $issue['response'];
$billing_key = isset($result['billingKey']) ? trim($result['billingKey']) : '';
if ($billing_key === '') {
    alert('토스페이먼츠 빌링키를 확인할 수 없습니다.', G5_URL.'/willow/payment.php?step=toss&return='.urlencode($return_url));
}

$card_table = willow_payment_card_table();
$card_number = willow_toss_card_number($result);
$card_name = willow_toss_card_name($result);
$card_expiry = willow_toss_card_expiry($result);
$tno = '';
if (!empty($result['mId']) && !empty($result['authenticatedAt'])) {
    $tno = $result['mId'].'_'.preg_replace('/[^0-9]/', '', $result['authenticatedAt']);
}
$order_number = willow_toss_order_id('WILLOWCARD', $member['mb_id']);
$od_test = function_exists('get_subs_option') && get_subs_option('su_card_test') ? 1 : 0;
$pg_id = function_exists('get_subs_option') ? (string) get_subs_option('su_tosspayments_mid') : '';

$existing = willow_payment_find_card_by_billkey($member['mb_id'], $billing_key);
$duplicate_card = empty($existing['ci_id']) ? willow_payment_find_duplicate_card($member['mb_id'], $card_number, $card_name, $card_expiry) : array();
if (!empty($duplicate_card['ci_id'])) {
    willow_payment_set_default($member['mb_id'], (int) $duplicate_card['ci_id']);
    alert('이미 등록된 카드입니다.', G5_URL.'/willow/payment.php?return='.urlencode($return_url));
}

$stored_billing_key = willow_payment_encrypt_billkey($billing_key);

if (!empty($existing['ci_id'])) {
    $ci_id = (int) $existing['ci_id'];
    sql_query(" update `{$card_table}`
        set card_mask_number = '".sql_escape_string($card_number)."',
            card_billkey = '".sql_escape_string($stored_billing_key)."',
            od_card_name = '".sql_escape_string($card_name)."',
            od_tno = '".sql_escape_string($tno)."',
            pg_service = 'tosspayments',
            pg_id = '".sql_escape_string($pg_id)."',
            pg_apikey = '',
            od_test = '{$od_test}',
            ci_time = '".G5_TIME_YMDHIS."'
        where ci_id = '{$ci_id}' ", false);
} else {
    sql_query(" insert into `{$card_table}`
            (mb_id, pg_service, pg_id, pg_apikey, first_ordernumber, card_mask_number, card_billkey, od_card_name, od_tno, od_id, od_test, ci_time)
        values
            ('".sql_escape_string($member['mb_id'])."',
             'tosspayments',
             '".sql_escape_string($pg_id)."',
             '',
             '".sql_escape_string($order_number)."',
             '".sql_escape_string($card_number)."',
             '".sql_escape_string($stored_billing_key)."',
             '".sql_escape_string($card_name)."',
             '".sql_escape_string($tno)."',
             0,
             '{$od_test}',
             '".G5_TIME_YMDHIS."') ", false);
    $ci_id = sql_insert_id();
}

if ($ci_id) {
    willow_payment_set_default($member['mb_id'], (int) $ci_id);
    willow_payment_set_card_expiry((int) $ci_id, $card_expiry);
}

goto_url(G5_URL.'/willow/payment.php?step=complete&ci_id='.(int) $ci_id.'&return='.urlencode($return_url));
