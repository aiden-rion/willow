<?php
include_once('./_common.php');
include_once('./content.lib.php');
include_once('./notification.lib.php');
include_once('./revenue.lib.php');

if (!$is_member) {
    goto_url(G5_BBS_URL.'/login.php?url='.urlencode(G5_URL.'/willow/my_subscribers.php'));
}

$is_author = ((int) $member['mb_level'] >= 3) || (!empty($member['mb_2']) && $member['mb_2'] === 'author');
if (!$is_author) {
    alert('작가회원만 이용할 수 있습니다.', G5_URL.'/willow/menu.php');
}

willow_notification_install();
willow_revenue_install();

$author_id = sql_escape_string($member['mb_id']);
$subscription_table = willow_subscription_table();
$payment_table = willow_subscription_payment_table();
$current_month = date('Y-m');
$month_start = $current_month.'-01 00:00:00';

$active_row = sql_fetch(" select count(*) as cnt from `{$subscription_table}` where author_mb_id = '{$author_id}' and ws_status = 'active' ", false);
$new_row = sql_fetch(" select count(*) as cnt from `{$subscription_table}` where author_mb_id = '{$author_id}' and ws_status = 'active' and ws_datetime >= '{$month_start}' ", false);
$failed_row = sql_fetch(" select count(*) as cnt from `{$payment_table}` where author_mb_id = '{$author_id}' and wsp_status not in ('paid') ", false);
$revenue_row = sql_fetch(" select
        coalesce(sum(case when wsp_status = 'paid' then wsp_amount else 0 end), 0) as total_amount,
        coalesce(sum(case when wsp_status = 'paid' then wsp_author_amount else 0 end), 0) as author_amount,
        coalesce(sum(case when wsp_status = 'paid' and wsp_paid_datetime >= '{$month_start}' then wsp_author_amount else 0 end), 0) as month_author_amount
    from `{$payment_table}`
    where author_mb_id = '{$author_id}' ", false);

$subscriber_count = isset($active_row['cnt']) ? (int) $active_row['cnt'] : 0;
$new_count = isset($new_row['cnt']) ? (int) $new_row['cnt'] : 0;
$failed_count = isset($failed_row['cnt']) ? (int) $failed_row['cnt'] : 0;
$total_revenue = isset($revenue_row['author_amount']) ? (int) $revenue_row['author_amount'] : 0;
$month_revenue = isset($revenue_row['month_author_amount']) ? (int) $revenue_row['month_author_amount'] : 0;
$expected_month_revenue = 0;
$price = willow_revenue_author_price($member['mb_id']);
$share_rate = willow_revenue_author_share_rate();
if ($subscriber_count > 0 && $price > 0) {
    $expected_month_revenue = (int) floor(($subscriber_count * $price) * ($share_rate / 100));
}

$subscribers = array();
$result = sql_query(" select s.*, m.mb_id, m.mb_nick, m.mb_name, m.mb_profile, m.mb_datetime
    from `{$subscription_table}` s
    left join {$g5['member_table']} m on m.mb_id = s.subscriber_mb_id
    where s.author_mb_id = '{$author_id}'
    order by s.ws_datetime desc, s.ws_id desc
    limit 100 ", false);
if ($result) {
    while ($row = sql_fetch_array($result)) {
        $payment = sql_fetch(" select wsp_status, wsp_amount, wsp_paid_datetime
            from `{$payment_table}`
            where ws_id = '".(int) $row['ws_id']."'
            order by wsp_paid_datetime desc, wsp_id desc
            limit 1 ", false);
        $name = $row['mb_nick'] ? $row['mb_nick'] : ($row['mb_name'] ? $row['mb_name'] : '윌로우 회원');
        $row['display_name'] = get_text($name);
        $row['avatar'] = willow_member_avatar($row);
        $row['last_payment_status'] = !empty($payment['wsp_status']) ? $payment['wsp_status'] : '';
        $row['last_payment_amount'] = !empty($payment['wsp_amount']) ? (int) $payment['wsp_amount'] : 0;
        $row['last_payment_datetime'] = !empty($payment['wsp_paid_datetime']) ? $payment['wsp_paid_datetime'] : '';
        $subscribers[] = $row;
    }
}

function willow_my_subscriber_status_label($status, $payment_status)
{
    if ($status === 'active') {
        return $payment_status && $payment_status !== 'paid' ? '결제확인 필요' : '구독중';
    }

    if ($status === 'cancelled') {
        return '구독해지';
    }

    return $status ? get_text($status) : '상태확인';
}

$g5['title'] = '나의 구독자';
include_once(G5_PATH.'/head.sub.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/willow_content.css?ver='.G5_CSS_VER.'">', 10);
?>

<main class="willow_content_app willow_my_subscribers_page">
    <header class="willow_author_page_header">
        <a href="<?php echo G5_URL; ?>/willow/menu.php" aria-label="뒤로가기"><img src="<?php echo G5_IMG_URL; ?>/ico_back.png" alt=""></a>
        <h1>나의 구독자</h1>
    </header>

    <section class="willow_subscriber_summary">
        <span>현재 구독자</span>
        <strong><?php echo number_format($subscriber_count); ?>명</strong>
        <p>이번 달 신규 <?php echo number_format($new_count); ?>명 · 월 예상수익 <?php echo number_format($expected_month_revenue); ?>P</p>
    </section>

    <section class="willow_subscriber_metrics" aria-label="구독자 지표">
        <article>
            <span>이번 달 수익</span>
            <strong><?php echo number_format($month_revenue); ?>P</strong>
        </article>
        <article>
            <span>누적 수익</span>
            <strong><?php echo number_format($total_revenue); ?>P</strong>
        </article>
        <article>
            <span>결제확인</span>
            <strong><?php echo number_format($failed_count); ?>건</strong>
        </article>
    </section>

    <section class="willow_subscriber_list">
        <h2>최근 구독자</h2>
        <?php if ($subscribers) { ?>
            <?php foreach ($subscribers as $subscriber) { ?>
            <?php
            $status_label = willow_my_subscriber_status_label($subscriber['ws_status'], $subscriber['last_payment_status']);
            $status_class = $subscriber['ws_status'] === 'active' && ($subscriber['last_payment_status'] === '' || $subscriber['last_payment_status'] === 'paid') ? 'is_active' : 'is_attention';
            ?>
            <article class="willow_subscriber_item">
                <img src="<?php echo $subscriber['avatar']; ?>" alt="">
                <div>
                    <strong><?php echo $subscriber['display_name']; ?></strong>
                    <span>구독 시작 <?php echo get_text(substr($subscriber['ws_datetime'], 0, 10)); ?></span>
                    <?php if ($subscriber['last_payment_datetime']) { ?>
                    <small>최근 결제 <?php echo get_text(substr($subscriber['last_payment_datetime'], 0, 10)); ?> · <?php echo number_format($subscriber['last_payment_amount']); ?>원</small>
                    <?php } else { ?>
                    <small>결제 내역 확인 전</small>
                    <?php } ?>
                </div>
                <em class="<?php echo $status_class; ?>"><?php echo $status_label; ?></em>
            </article>
            <?php } ?>
        <?php } else { ?>
        <div class="willow_subscriber_empty">
            <strong>아직 구독자가 없습니다.</strong>
            <p>새 글을 꾸준히 발행하면 구독자가 이곳에 표시됩니다.</p>
        </div>
        <?php } ?>
    </section>
</main>

<?php
include_once(G5_PATH.'/tail.sub.php');
