<?php
include_once('./_common.php');
include_once('./content.lib.php');
include_once('./topic.lib.php');
include_once('./notification.lib.php');
include_once('./revenue.lib.php');
include_once('./payment.lib.php');
include_once(G5_LIB_PATH.'/subscription.lib.php');

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

$card = willow_payment_default_card($member['mb_id']);

if (empty($card['ci_id'])) {
    $payment_return = G5_URL.'/willow/subscribe.php?author='.urlencode($author_id).'&step=confirm';
    $payment_href = G5_URL.'/willow/payment.php?return='.urlencode($payment_return);
    alert('결제수단을 먼저 등록해주세요.', $payment_href);
}

willow_notification_install();
$table = willow_subscription_table();
$author_sql = sql_escape_string($author['mb_id']);
$subscriber_sql = sql_escape_string($member['mb_id']);
$existing = sql_fetch(" select * from `{$table}`
    where author_mb_id = '{$author_sql}'
        and subscriber_mb_id = '{$subscriber_sql}'
        and ws_status = 'active'
    limit 1 ", false);
if (!empty($existing['ws_id'])) {
    alert('이미 구독 중입니다.', G5_URL.'/willow/subscribe.php?author='.urlencode($author['mb_id']).'&step=confirm');
}

$amount = function_exists('willow_revenue_author_price') ? willow_revenue_author_price($author['mb_id']) : (int) preg_replace('/[^0-9]/', '', (string) $author['mb_1']);
$amount = $amount > 0 ? $amount : 8800;
$order_id = willow_toss_order_id('WILLOWSUB', $member['mb_id'], $author['mb_id']);
$order_name = ($author['mb_nick'] ? $author['mb_nick'] : $author['mb_name']).' 작가 정기구독';
$billing_key = willow_payment_card_billkey($card);
if ($billing_key === '') {
    alert('결제카드 인증정보를 확인할 수 없습니다.', G5_URL.'/willow/subscribe.php?author='.urlencode($author_id).'&step=confirm');
}

$billing = willow_toss_bill_card($billing_key, $amount, $order_id, $order_name, $member);
if (empty($billing['success'])) {
    $message = isset($billing['message']) ? $billing['message'] : '정기결제 승인에 실패했습니다.';
    alert($message, G5_URL.'/willow/subscribe.php?author='.urlencode($author_id).'&step=confirm');
}

$billing_response = $billing['response'];
$paid_datetime = G5_TIME_YMDHIS;
if (!empty($billing_response['approvedAt'])) {
    $paid_datetime = str_replace('T', ' ', substr($billing_response['approvedAt'], 0, 19));
}
$next_billing_date = date('Y-m-d', strtotime('+1 month', strtotime(substr($paid_datetime, 0, 10))));

sql_query(" insert into `{$table}`
        (author_mb_id, subscriber_mb_id, ws_status, ws_card_ci_id, ws_last_billing_date, ws_next_billing_date, ws_datetime)
    values
        ('{$author_sql}', '{$subscriber_sql}', 'active', '".(int) $card['ci_id']."', '".sql_escape_string(substr($paid_datetime, 0, 10))."', '".sql_escape_string($next_billing_date)."', '".G5_TIME_YMDHIS."')
    on duplicate key update
        ws_status = 'active',
        ws_card_ci_id = values(ws_card_ci_id),
        ws_last_billing_date = values(ws_last_billing_date),
        ws_next_billing_date = values(ws_next_billing_date),
        ws_datetime = values(ws_datetime) ", false);

$subscription = sql_fetch(" select * from `{$table}`
    where author_mb_id = '{$author_sql}'
        and subscriber_mb_id = '{$subscriber_sql}'
    limit 1 ", false);
if (!empty($subscription['ws_id'])) {
    willow_revenue_record_subscription_payment($subscription, $amount, $paid_datetime, array(
        'pg_service' => 'tosspayments',
        'order_id' => $order_id,
        'transaction_key' => isset($billing_response['lastTransactionKey']) ? $billing_response['lastTransactionKey'] : '',
        'ci_id' => (int) $card['ci_id'],
        'memo' => '토스 정기구독 최초 결제',
    ));
}

goto_url(G5_URL.'/willow/subscribe.php?author='.urlencode($author['mb_id']).'&step=complete');
