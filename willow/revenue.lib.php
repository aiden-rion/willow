<?php
if (!defined('_GNUBOARD_')) exit;

include_once(G5_PATH.'/willow/notification.lib.php');

function willow_revenue_prefix_table($name)
{
    global $g5;

    $prefix = defined('G5_TABLE_PREFIX') ? G5_TABLE_PREFIX : (isset($g5['table_prefix']) ? $g5['table_prefix'] : 'g5_');

    return $prefix.$name;
}

function willow_subscription_payment_table()
{
    return willow_revenue_prefix_table('willow_subscription_payment');
}

function willow_settlement_request_table()
{
    return willow_revenue_prefix_table('willow_settlement_request');
}

function willow_revenue_install()
{
    static $installed = false;

    if ($installed) {
        return;
    }

    willow_notification_install();

    $payment_table = willow_subscription_payment_table();
    sql_query(" create table if not exists `{$payment_table}` (
        wsp_id int unsigned not null auto_increment,
        ws_id int unsigned not null default 0,
        author_mb_id varchar(20) not null default '',
        subscriber_mb_id varchar(20) not null default '',
        wsp_month char(7) not null default '',
        wsp_amount int unsigned not null default 0,
        wsp_author_amount int unsigned not null default 0,
        wsp_platform_amount int unsigned not null default 0,
        wsp_rate tinyint unsigned not null default 70,
        wsp_status varchar(20) not null default 'paid',
        wsp_memo varchar(255) not null default '',
        wsp_paid_datetime datetime not null,
        wsp_datetime datetime not null,
        primary key (wsp_id),
        unique key ws_month (ws_id, wsp_month),
        key author_status (author_mb_id, wsp_status),
        key subscriber_status (subscriber_mb_id, wsp_status),
        key paid_datetime (wsp_paid_datetime)
    ) ", false);

    $settlement_table = willow_settlement_request_table();
    sql_query(" create table if not exists `{$settlement_table}` (
        wsr_id int unsigned not null auto_increment,
        author_mb_id varchar(20) not null default '',
        wsr_amount int unsigned not null default 0,
        wsr_status varchar(20) not null default 'requested',
        wsr_bank_name varchar(100) not null default '',
        wsr_account_holder varchar(100) not null default '',
        wsr_account_number varchar(100) not null default '',
        wsr_memo varchar(255) not null default '',
        wsr_admin_memo varchar(255) not null default '',
        wsr_datetime datetime not null,
        wsr_update_datetime datetime not null,
        wsr_processed_datetime datetime not null default '0000-00-00 00:00:00',
        wsr_processed_mb_id varchar(20) not null default '',
        primary key (wsr_id),
        key author_status (author_mb_id, wsr_status),
        key wsr_datetime (wsr_datetime)
    ) ", false);

    $installed = true;
}

function willow_revenue_author_price($author_mb_id)
{
    $author = get_member($author_mb_id);
    $price = !empty($author['mb_1']) ? (int) preg_replace('/[^0-9]/', '', $author['mb_1']) : 0;

    return $price > 0 ? $price : 8800;
}

function willow_revenue_record_subscription_payment($subscription, $amount = 0, $paid_datetime = '')
{
    if (empty($subscription['ws_id']) || empty($subscription['author_mb_id']) || empty($subscription['subscriber_mb_id'])) {
        return 0;
    }

    willow_revenue_install();

    $payment_table = willow_subscription_payment_table();
    $ws_id = (int) $subscription['ws_id'];
    $paid_datetime = $paid_datetime ? $paid_datetime : (!empty($subscription['ws_datetime']) ? $subscription['ws_datetime'] : G5_TIME_YMDHIS);
    $month = substr($paid_datetime, 0, 7);
    $amount = $amount > 0 ? (int) $amount : willow_revenue_author_price($subscription['author_mb_id']);
    $author_amount = (int) floor($amount * 0.7);
    $platform_amount = max(0, $amount - $author_amount);

    $existing = sql_fetch(" select * from `{$payment_table}` where ws_id = '{$ws_id}' and wsp_month = '".sql_escape_string($month)."' ", false);
    if (!empty($existing['wsp_id'])) {
        return (int) $existing['wsp_id'];
    }

    sql_query(" insert into `{$payment_table}`
        set ws_id = '{$ws_id}',
            author_mb_id = '".sql_escape_string($subscription['author_mb_id'])."',
            subscriber_mb_id = '".sql_escape_string($subscription['subscriber_mb_id'])."',
            wsp_month = '".sql_escape_string($month)."',
            wsp_amount = '{$amount}',
            wsp_author_amount = '{$author_amount}',
            wsp_platform_amount = '{$platform_amount}',
            wsp_rate = '70',
            wsp_status = 'paid',
            wsp_memo = '구독료 결제',
            wsp_paid_datetime = '".sql_escape_string($paid_datetime)."',
            wsp_datetime = '".G5_TIME_YMDHIS."' ", false);

    $wsp_id = sql_insert_id();
    if ($wsp_id && $author_amount > 0) {
        insert_point(
            $subscription['author_mb_id'],
            $author_amount,
            'WILLOW 구독료 작가 배분 70%',
            'willow_sub_pay',
            (string) $wsp_id,
            'author_share'
        );
    }

    return (int) $wsp_id;
}

function willow_revenue_backfill_subscription_payments()
{
    willow_revenue_install();

    $subscription_table = willow_subscription_table();
    $result = sql_query(" select * from `{$subscription_table}` where ws_status = 'active' order by ws_id asc ", false);
    if ($result) {
        while ($row = sql_fetch_array($result)) {
            willow_revenue_record_subscription_payment($row);
        }
    }
}

function willow_revenue_summary()
{
    willow_revenue_backfill_subscription_payments();

    $payment_table = willow_subscription_payment_table();
    $settlement_table = willow_settlement_request_table();
    $subscription_table = willow_subscription_table();

    $paid = sql_fetch(" select
            count(*) as cnt,
            coalesce(sum(wsp_amount), 0) as total_amount,
            coalesce(sum(wsp_author_amount), 0) as author_amount,
            coalesce(sum(wsp_platform_amount), 0) as platform_amount
        from `{$payment_table}`
        where wsp_status = 'paid' ", false);
    $subs = sql_fetch(" select count(*) as cnt from `{$subscription_table}` where ws_status = 'active' ", false);
    $pending = sql_fetch(" select coalesce(sum(wsr_amount), 0) as amount, count(*) as cnt from `{$settlement_table}` where wsr_status = 'requested' ", false);
    $settled = sql_fetch(" select coalesce(sum(wsr_amount), 0) as amount, count(*) as cnt from `{$settlement_table}` where wsr_status = 'paid' ", false);

    return array(
        'payment_count' => (int) $paid['cnt'],
        'total_amount' => (int) $paid['total_amount'],
        'author_amount' => (int) $paid['author_amount'],
        'platform_amount' => (int) $paid['platform_amount'],
        'active_subscriptions' => (int) $subs['cnt'],
        'pending_settlement_amount' => (int) $pending['amount'],
        'pending_settlement_count' => (int) $pending['cnt'],
        'settled_amount' => (int) $settled['amount'],
        'settled_count' => (int) $settled['cnt'],
    );
}

function willow_revenue_author_balance_rows()
{
    global $g5;

    willow_revenue_backfill_subscription_payments();

    $payment_table = willow_subscription_payment_table();
    $settlement_table = willow_settlement_request_table();

    $rows = array();
    $result = sql_query(" select p.author_mb_id,
            coalesce(sum(case when p.wsp_status = 'paid' then p.wsp_author_amount else 0 end), 0) as earned_amount,
            coalesce((select sum(s.wsr_amount) from `{$settlement_table}` s where s.author_mb_id = p.author_mb_id and s.wsr_status = 'paid'), 0) as paid_amount,
            coalesce((select sum(s.wsr_amount) from `{$settlement_table}` s where s.author_mb_id = p.author_mb_id and s.wsr_status = 'requested'), 0) as requested_amount,
            count(*) as payment_count,
            m.mb_nick, m.mb_name, m.mb_point, m.mb_8, m.mb_9, m.mb_10
        from `{$payment_table}` p
        left join {$g5['member_table']} m on m.mb_id = p.author_mb_id
        group by p.author_mb_id
        order by earned_amount desc, p.author_mb_id asc ", false);

    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $row['available_amount'] = max(0, (int) $row['earned_amount'] - (int) $row['paid_amount'] - (int) $row['requested_amount']);
            $rows[] = $row;
        }
    }

    return $rows;
}

function willow_revenue_is_author($mb)
{
    if (empty($mb) || empty($mb['mb_id'])) {
        return false;
    }

    return ((int) $mb['mb_level'] >= 3) || (!empty($mb['mb_2']) && $mb['mb_2'] === 'author');
}

function willow_revenue_author_balance($author_mb_id)
{
    global $g5;

    $author_mb_id = trim($author_mb_id);
    if ($author_mb_id === '') {
        return array();
    }

    foreach (willow_revenue_author_balance_rows() as $row) {
        if ($row['author_mb_id'] === $author_mb_id) {
            return $row;
        }
    }

    $member = get_member($author_mb_id);
    if (empty($member['mb_id'])) {
        return array();
    }

    $settlement_table = willow_settlement_request_table();
    $paid = sql_fetch(" select coalesce(sum(wsr_amount), 0) as amount from `{$settlement_table}` where author_mb_id = '".sql_escape_string($author_mb_id)."' and wsr_status = 'paid' ", false);
    $requested = sql_fetch(" select coalesce(sum(wsr_amount), 0) as amount from `{$settlement_table}` where author_mb_id = '".sql_escape_string($author_mb_id)."' and wsr_status = 'requested' ", false);

    return array(
        'author_mb_id' => $author_mb_id,
        'earned_amount' => 0,
        'paid_amount' => (int) $paid['amount'],
        'requested_amount' => (int) $requested['amount'],
        'available_amount' => 0,
        'payment_count' => 0,
        'mb_nick' => $member['mb_nick'],
        'mb_name' => $member['mb_name'],
        'mb_point' => (int) $member['mb_point'],
        'mb_8' => $member['mb_8'],
        'mb_9' => $member['mb_9'],
        'mb_10' => $member['mb_10'],
    );
}
