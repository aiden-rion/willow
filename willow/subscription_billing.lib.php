<?php
if (!defined('_GNUBOARD_')) exit;

include_once(G5_PATH.'/willow/notification.lib.php');
include_once(G5_PATH.'/willow/revenue.lib.php');
include_once(G5_PATH.'/willow/payment.lib.php');
include_once(G5_LIB_PATH.'/subscription.lib.php');

function willow_subscription_bill_one($subscription)
{
    global $g5;

    if (empty($subscription['ws_id']) || empty($subscription['author_mb_id']) || empty($subscription['subscriber_mb_id'])) {
        return array('success' => false, 'message' => '구독 정보가 올바르지 않습니다.');
    }

    $subscriber = get_member($subscription['subscriber_mb_id']);
    $author = get_member($subscription['author_mb_id']);
    if (empty($subscriber['mb_id']) || empty($author['mb_id'])) {
        return array('success' => false, 'message' => '회원 정보를 찾을 수 없습니다.');
    }

    $card_table = willow_payment_card_table();
    $card_where = " mb_id = '".sql_escape_string($subscriber['mb_id'])."' and card_billkey <> '' ";
    if (!empty($subscription['ws_card_ci_id'])) {
        $card_where .= " and ci_id = '".(int) $subscription['ws_card_ci_id']."' ";
    }
    $card = sql_fetch(" select * from `{$card_table}` where {$card_where} order by ci_id desc limit 1 ", false);
    if (empty($card['ci_id'])) {
        $card = willow_payment_default_card($subscriber['mb_id']);
    }
    if (empty($card['ci_id']) || empty($card['card_billkey'])) {
        return array('success' => false, 'message' => '등록된 결제카드가 없습니다.');
    }

    $billing_key = willow_payment_card_billkey($card);
    if ($billing_key === '') {
        return array('success' => false, 'message' => '결제카드 인증정보를 확인할 수 없습니다.');
    }

    $amount = willow_revenue_author_price($author['mb_id']);
    $order_id = willow_toss_order_id('WILLOWMONTH', $subscriber['mb_id'], $subscription['ws_id']);
    $order_name = ($author['mb_nick'] ? $author['mb_nick'] : $author['mb_name']).' 작가 월 정기구독';
    $billing = willow_toss_bill_card($billing_key, $amount, $order_id, $order_name, $subscriber);
    if (empty($billing['success'])) {
        return array('success' => false, 'message' => isset($billing['message']) ? $billing['message'] : '정기결제 승인에 실패했습니다.', 'response' => $billing);
    }

    $response = $billing['response'];
    $paid_datetime = G5_TIME_YMDHIS;
    if (!empty($response['approvedAt'])) {
        $paid_datetime = str_replace('T', ' ', substr($response['approvedAt'], 0, 19));
    }
    $next_billing_date = date('Y-m-d', strtotime('+1 month', strtotime(substr($paid_datetime, 0, 10))));

    willow_revenue_record_subscription_payment($subscription, $amount, $paid_datetime, array(
        'pg_service' => 'tosspayments',
        'order_id' => $order_id,
        'transaction_key' => isset($response['lastTransactionKey']) ? $response['lastTransactionKey'] : '',
        'ci_id' => (int) $card['ci_id'],
        'memo' => '토스 월 정기구독 결제',
    ));

    $table = willow_subscription_table();
    sql_query(" update `{$table}`
        set ws_card_ci_id = '".(int) $card['ci_id']."',
            ws_last_billing_date = '".sql_escape_string(substr($paid_datetime, 0, 10))."',
            ws_next_billing_date = '".sql_escape_string($next_billing_date)."'
        where ws_id = '".(int) $subscription['ws_id']."' ", false);

    return array('success' => true, 'message' => '정기결제가 완료되었습니다.', 'order_id' => $order_id);
}

function willow_subscription_process_due_billings($limit = 20)
{
    willow_notification_install();
    willow_revenue_install();

    $table = willow_subscription_table();
    $limit = max(1, min(100, (int) $limit));
    $items = array();
    $result = sql_query(" select *
        from `{$table}`
        where ws_status = 'active'
            and ws_next_billing_date <> '0000-00-00'
            and ws_next_billing_date <= '".G5_TIME_YMD."'
        order by ws_next_billing_date asc, ws_id asc
        limit {$limit} ", false);

    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $processed = willow_subscription_bill_one($row);
            $processed['ws_id'] = (int) $row['ws_id'];
            $processed['author_mb_id'] = $row['author_mb_id'];
            $processed['subscriber_mb_id'] = $row['subscriber_mb_id'];
            $items[] = $processed;
        }
    }

    return $items;
}
