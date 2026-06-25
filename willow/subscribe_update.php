<?php
include_once('./_common.php');
include_once('./content.lib.php');
include_once('./topic.lib.php');
include_once('./notification.lib.php');
include_once('./revenue.lib.php');

if (!$is_member) {
    alert('로그인 후 이용해주세요.', G5_BBS_URL.'/login.php');
}

$author_id = isset($_POST['author']) ? trim($_POST['author']) : '';
if ($author_id === '') {
    alert('구독할 작가를 찾을 수 없습니다.', G5_URL);
}

$author = get_member($author_id);
if (empty($author['mb_id']) || !((int) $author['mb_level'] >= 3 || $author['mb_2'] === 'author')) {
    alert('구독할 작가를 찾을 수 없습니다.', G5_URL);
}

if ($author['mb_id'] === $member['mb_id']) {
    alert('본인 계정은 구독할 수 없습니다.', G5_URL.'/willow/subscribe.php?author='.urlencode($author_id));
}

$card_table = isset($g5['g5_subscription_mb_cardinfo_table']) ? $g5['g5_subscription_mb_cardinfo_table'] : G5_TABLE_PREFIX.'subscription_mb_cardinfo';
$card = sql_fetch(" select ci_id from `{$card_table}`
    where card_billkey <> ''
        and mb_id = '".sql_escape_string($member['mb_id'])."'
    order by ci_id desc
    limit 1 ", false);

if (empty($card['ci_id'])) {
    $payment_return = G5_URL.'/willow/subscribe.php?author='.urlencode($author_id).'&step=confirm';
    $payment_href = G5_URL.'/willow/payment.php?return='.urlencode($payment_return);
    alert('결제수단을 먼저 등록해주세요.', $payment_href);
}

willow_notification_install();
$table = willow_subscription_table();
$author_sql = sql_escape_string($author['mb_id']);
$subscriber_sql = sql_escape_string($member['mb_id']);

sql_query(" insert into `{$table}`
        (author_mb_id, subscriber_mb_id, ws_status, ws_datetime)
    values
        ('{$author_sql}', '{$subscriber_sql}', 'active', '".G5_TIME_YMDHIS."')
    on duplicate key update
        ws_status = 'active',
        ws_datetime = values(ws_datetime) ", false);

$subscription = sql_fetch(" select * from `{$table}`
    where author_mb_id = '{$author_sql}'
        and subscriber_mb_id = '{$subscriber_sql}'
    limit 1 ", false);
if (!empty($subscription['ws_id'])) {
    willow_revenue_record_subscription_payment($subscription);
}

goto_url(G5_URL.'/willow/subscribe.php?author='.urlencode($author['mb_id']).'&step=complete');
