<?php
include_once('./_common.php');
include_once('./payment.lib.php');

if (!$is_member) {
    alert('로그인 후 이용해주세요.', G5_BBS_URL.'/login.php');
}

$return_url = isset($_POST['return']) ? trim($_POST['return']) : G5_URL.'/willow/payment.php';
if ($return_url === '') {
    $return_url = G5_URL.'/willow/payment.php';
}
check_url_host($return_url);

$ci_id = isset($_POST['ci_id']) ? (int) $_POST['ci_id'] : 0;
$action = isset($_POST['action']) ? preg_replace('/[^a-z_]/', '', $_POST['action']) : '';

$card_table = willow_payment_card_table();
$card = sql_fetch(" select ci_id from `{$card_table}`
    where ci_id = '{$ci_id}'
        and mb_id = '".sql_escape_string($member['mb_id'])."'
        and card_billkey <> ''
    limit 1 ", false);

if (empty($card['ci_id'])) {
    alert('카드 정보를 찾을 수 없습니다.', $return_url);
}

if ($action === 'default') {
    willow_payment_set_default($member['mb_id'], $ci_id);
    goto_url($return_url);
}

if ($action === 'delete') {
    sql_query(" delete from `{$card_table}`
        where ci_id = '{$ci_id}'
            and mb_id = '".sql_escape_string($member['mb_id'])."' ", false);

    if (willow_payment_default_id($member['mb_id']) === $ci_id) {
        $cards = willow_payment_cards($member['mb_id']);
        if ($cards) {
            willow_payment_set_default($member['mb_id'], (int) $cards[0]['ci_id']);
        } else {
            willow_payment_set_default($member['mb_id'], 0);
        }
    }

    goto_url($return_url);
}

alert('처리할 수 없는 요청입니다.', $return_url);
