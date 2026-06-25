<?php
$sub_menu = '700600';
require_once './_common.php';
include_once G5_PATH.'/willow/revenue.lib.php';

auth_check_menu($auth, $sub_menu, 'r');
willow_revenue_backfill_subscription_payments();

$g5['title'] = '구독현황';
$subscription_table = willow_subscription_table();
$payment_table = willow_subscription_payment_table();
$status = isset($_GET['status']) ? preg_replace('/[^a-z_]/', '', $_GET['status']) : 'active';
$where = " where 1 ";
if ($status !== 'all') {
    $where .= " and s.ws_status = '".sql_escape_string($status)."' ";
}

$items = array();
$result = sql_query(" select s.*,
        a.mb_nick as author_nick, a.mb_name as author_name, a.mb_1 as author_price,
        u.mb_nick as subscriber_nick, u.mb_name as subscriber_name,
        coalesce((select sum(p.wsp_amount) from `{$payment_table}` p where p.ws_id = s.ws_id and p.wsp_status = 'paid'), 0) as paid_amount,
        coalesce((select sum(p.wsp_author_amount) from `{$payment_table}` p where p.ws_id = s.ws_id and p.wsp_status = 'paid'), 0) as author_amount,
        coalesce((select count(*) from `{$payment_table}` p where p.ws_id = s.ws_id and p.wsp_status = 'paid'), 0) as payment_count
    from `{$subscription_table}` s
    left join {$g5['member_table']} a on a.mb_id = s.author_mb_id
    left join {$g5['member_table']} u on u.mb_id = s.subscriber_mb_id
    {$where}
    order by s.ws_datetime desc, s.ws_id desc ", false);
if ($result) {
    while ($row = sql_fetch_array($result)) {
        $items[] = $row;
    }
}

require_once './admin.head.php';
?>

<div class="local_desc01 local_desc">
    <p>현재 구독 상태와 구독료, 결제 로그, 작가 배분 포인트를 확인합니다. 구독료의 70%가 작가 포인트로 적립됩니다.</p>
</div>
<div class="btn_fixed_top">
    <a href="./willow_revenue.php" class="btn btn_02">수익 대시보드</a>
    <a href="./willow_settlement.php" class="btn btn_02">정산관리</a>
</div>
<div class="local_sch01 local_sch">
    <a href="./willow_subscription_status.php?status=active" class="btn <?php echo $status === 'active' ? 'btn_03' : 'btn_02'; ?>">활성</a>
    <a href="./willow_subscription_status.php?status=all" class="btn <?php echo $status === 'all' ? 'btn_03' : 'btn_02'; ?>">전체</a>
</div>

<div class="tbl_head01 tbl_wrap">
    <table>
        <thead>
        <tr><th>ID</th><th>상태</th><th>작가</th><th>구독자</th><th>월 구독료</th><th>누적 결제</th><th>작가 배분</th><th>결제건</th><th>구독일</th></tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item) { ?>
        <?php $price = willow_revenue_author_price($item['author_mb_id']); ?>
        <tr>
            <td class="td_num"><?php echo (int) $item['ws_id']; ?></td>
            <td><?php echo get_text($item['ws_status']); ?></td>
            <td><strong><?php echo get_text($item['author_nick'] ? $item['author_nick'] : $item['author_name']); ?></strong><br><?php echo get_text($item['author_mb_id']); ?></td>
            <td><strong><?php echo get_text($item['subscriber_nick'] ? $item['subscriber_nick'] : $item['subscriber_name']); ?></strong><br><?php echo get_text($item['subscriber_mb_id']); ?></td>
            <td class="td_num"><?php echo number_format($price); ?>원</td>
            <td class="td_num"><?php echo number_format((int) $item['paid_amount']); ?>원</td>
            <td class="td_num"><?php echo number_format((int) $item['author_amount']); ?>P</td>
            <td class="td_num"><?php echo number_format((int) $item['payment_count']); ?>건</td>
            <td><?php echo get_text($item['ws_datetime']); ?></td>
        </tr>
        <?php } ?>
        <?php if (!$items) { ?><tr><td colspan="9" class="empty_table">구독 내역이 없습니다.</td></tr><?php } ?>
        </tbody>
    </table>
</div>

<?php
require_once './admin.tail.php';
