<?php
include_once('./_common.php');
include_once('./payment.lib.php');

if (!$is_member) {
    alert('로그인 후 이용해주세요.', G5_BBS_URL.'/login.php');
}

$return_url = isset($_POST['return']) ? trim($_POST['return']) : G5_URL.'/willow/menu.php';
if ($return_url === '') {
    $return_url = G5_URL.'/willow/menu.php';
}
check_url_host($return_url);

$card_name = isset($_POST['card_name']) ? trim($_POST['card_name']) : '';
$card_digits = isset($_POST['card_last4']) ? preg_replace('/[^0-9]/', '', $_POST['card_last4']) : '';
$card_last4 = strlen($card_digits) >= 4 ? substr($card_digits, -4) : $card_digits;
$owner_name = isset($_POST['owner_name']) ? trim($_POST['owner_name']) : ($member['mb_name'] ? $member['mb_name'] : $member['mb_nick']);

if ($card_name === '' || strlen($card_last4) !== 4) {
    alert('카드 정보를 확인해주세요.', G5_URL.'/willow/payment.php?return='.urlencode($return_url));
}

$card_table = isset($g5['g5_subscription_mb_cardinfo_table']) ? $g5['g5_subscription_mb_cardinfo_table'] : G5_TABLE_PREFIX.'subscription_mb_cardinfo';
$mb_id = sql_escape_string($member['mb_id']);
$card_name_sql = sql_escape_string($card_name);
$card_mask = '**** '.$card_last4;
$card_mask_sql = sql_escape_string($card_mask);
$billkey = 'willow_demo_'.sha1($member['mb_id'].'|'.$card_name.'|'.$card_last4.'|'.G5_TIME_YMDHIS);
$order_number = substr(preg_replace('/[^0-9]/', '', G5_TIME_YMDHIS), 2).sprintf('%04d', mt_rand(0, 9999));

sql_query(" insert into `{$card_table}`
        (mb_id, pg_service, pg_id, pg_apikey, first_ordernumber, card_mask_number, card_billkey, od_card_name, od_tno, od_id, od_test, ci_time)
    values
        ('{$mb_id}', 'willow', 'willow', '', '{$order_number}', '{$card_mask_sql}', '".sql_escape_string($billkey)."', '{$card_name_sql}', '', 0, 1, '".G5_TIME_YMDHIS."') ", false);

$ci_id = sql_insert_id();
if ($ci_id) {
    willow_payment_set_default($member['mb_id'], (int) $ci_id);
}

goto_url(G5_URL.'/willow/payment.php?step=complete&ci_id='.(int) $ci_id.'&return='.urlencode($return_url));
