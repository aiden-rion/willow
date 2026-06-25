<?php
$sub_menu = '700500';
require_once './_common.php';
include_once G5_PATH.'/willow/revenue.lib.php';

auth_check_menu($auth, $sub_menu, 'r');
willow_revenue_backfill_subscription_payments();

$g5['title'] = '수익 대시보드';
$summary = willow_revenue_summary();
$author_rows = array_slice(willow_revenue_author_balance_rows(), 0, 10);
$payment_table = willow_subscription_payment_table();
$recent_payments = array();
$result = sql_query(" select p.*, a.mb_nick as author_nick, a.mb_name as author_name, s.mb_nick as subscriber_nick, s.mb_name as subscriber_name
    from `{$payment_table}` p
    left join {$g5['member_table']} a on a.mb_id = p.author_mb_id
    left join {$g5['member_table']} s on s.mb_id = p.subscriber_mb_id
    order by p.wsp_paid_datetime desc, p.wsp_id desc
    limit 10 ", false);
if ($result) {
    while ($row = sql_fetch_array($result)) {
        $recent_payments[] = $row;
    }
}

require_once './admin.head.php';
?>

<style>
.willow_dash_cards{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:0 0 18px}
.willow_dash_card{padding:18px;border:1px solid #ddd;background:#fff}
.willow_dash_card span{display:block;margin-bottom:8px;color:#777}
.willow_dash_card strong{font-size:24px}
.willow_admin_nav{display:flex;gap:6px;margin:0 0 14px}
.willow_admin_nav a{padding:8px 12px;border:1px solid #ddd;background:#fff}
.willow_admin_nav a.on{border-color:#333;background:#333;color:#fff}
@media (max-width:1100px){.willow_dash_cards{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>

<div class="willow_admin_nav">
    <a class="on" href="./willow_revenue.php">수익 대시보드</a>
    <a href="./willow_subscription_status.php">구독현황</a>
    <a href="./willow_settlement.php">정산관리</a>
</div>

<div class="willow_dash_cards">
    <div class="willow_dash_card"><span>총 구독매출</span><strong><?php echo number_format($summary['total_amount']); ?>원</strong></div>
    <div class="willow_dash_card"><span>작가 배분 70%</span><strong><?php echo number_format($summary['author_amount']); ?>P</strong></div>
    <div class="willow_dash_card"><span>플랫폼 수익 30%</span><strong><?php echo number_format($summary['platform_amount']); ?>원</strong></div>
    <div class="willow_dash_card"><span>활성 구독</span><strong><?php echo number_format($summary['active_subscriptions']); ?>건</strong></div>
    <div class="willow_dash_card"><span>정산 요청</span><strong><?php echo number_format($summary['pending_settlement_amount']); ?>P</strong></div>
    <div class="willow_dash_card"><span>정산 완료</span><strong><?php echo number_format($summary['settled_amount']); ?>P</strong></div>
    <div class="willow_dash_card"><span>결제 로그</span><strong><?php echo number_format($summary['payment_count']); ?>건</strong></div>
    <div class="willow_dash_card"><span>대기 요청 수</span><strong><?php echo number_format($summary['pending_settlement_count']); ?>건</strong></div>
</div>

<h2 class="h2_frm">작가별 수익/정산 요약</h2>
<div class="tbl_head01 tbl_wrap">
    <table>
        <thead><tr><th>작가</th><th>누적 배분</th><th>정산완료</th><th>정산요청</th><th>정산가능</th><th>현재 포인트</th><th>결제건</th></tr></thead>
        <tbody>
        <?php foreach ($author_rows as $row) { ?>
        <tr>
            <td><?php echo get_text($row['mb_nick'] ? $row['mb_nick'] : ($row['mb_name'] ? $row['mb_name'] : $row['author_mb_id'])); ?><br><span><?php echo get_text($row['author_mb_id']); ?></span></td>
            <td class="td_num"><?php echo number_format((int) $row['earned_amount']); ?>P</td>
            <td class="td_num"><?php echo number_format((int) $row['paid_amount']); ?>P</td>
            <td class="td_num"><?php echo number_format((int) $row['requested_amount']); ?>P</td>
            <td class="td_num"><?php echo number_format((int) $row['available_amount']); ?>P</td>
            <td class="td_num"><?php echo number_format((int) $row['mb_point']); ?>P</td>
            <td class="td_num"><?php echo number_format((int) $row['payment_count']); ?>건</td>
        </tr>
        <?php } ?>
        <?php if (!$author_rows) { ?><tr><td colspan="7" class="empty_table">수익 내역이 없습니다.</td></tr><?php } ?>
        </tbody>
    </table>
</div>

<h2 class="h2_frm">최근 구독 결제</h2>
<div class="tbl_head01 tbl_wrap">
    <table>
        <thead><tr><th>결제일</th><th>작가</th><th>구독자</th><th>결제금액</th><th>작가배분</th><th>플랫폼</th><th>상태</th></tr></thead>
        <tbody>
        <?php foreach ($recent_payments as $payment) { ?>
        <tr>
            <td><?php echo get_text($payment['wsp_paid_datetime']); ?></td>
            <td><?php echo get_text($payment['author_nick'] ? $payment['author_nick'] : $payment['author_mb_id']); ?></td>
            <td><?php echo get_text($payment['subscriber_nick'] ? $payment['subscriber_nick'] : $payment['subscriber_mb_id']); ?></td>
            <td class="td_num"><?php echo number_format((int) $payment['wsp_amount']); ?>원</td>
            <td class="td_num"><?php echo number_format((int) $payment['wsp_author_amount']); ?>P</td>
            <td class="td_num"><?php echo number_format((int) $payment['wsp_platform_amount']); ?>원</td>
            <td><?php echo get_text($payment['wsp_status']); ?></td>
        </tr>
        <?php } ?>
        <?php if (!$recent_payments) { ?><tr><td colspan="7" class="empty_table">결제 내역이 없습니다.</td></tr><?php } ?>
        </tbody>
    </table>
</div>

<?php
require_once './admin.tail.php';
