<?php
$sub_menu = '700700';
require_once './_common.php';
include_once G5_PATH.'/willow/revenue.lib.php';

auth_check_menu($auth, $sub_menu, 'r');
willow_revenue_backfill_subscription_payments();

$g5['title'] = '정산관리';
$settlement_table = willow_settlement_request_table();
$author_rows = willow_revenue_author_balance_rows();
$requests = array();
$status = isset($_GET['status']) ? preg_replace('/[^a-z_]/', '', $_GET['status']) : 'requested';
$where = " where 1 ";
if ($status !== 'all') {
    $where .= " and r.wsr_status = '".sql_escape_string($status)."' ";
}
$result = sql_query(" select r.*, m.mb_nick, m.mb_name, m.mb_point
    from `{$settlement_table}` r
    left join {$g5['member_table']} m on m.mb_id = r.author_mb_id
    {$where}
    order by r.wsr_datetime desc, r.wsr_id desc ", false);
if ($result) {
    while ($row = sql_fetch_array($result)) {
        $requests[] = $row;
    }
}

require_once './admin.head.php';
?>

<style>
.willow_status{display:inline-block;padding:4px 8px;border-radius:3px;background:#eee;font-weight:700}
.willow_status.requested{background:#fff4d6;color:#9b5d00}
.willow_status.paid{background:#e6f5ee;color:#06703b}
.willow_status.rejected{background:#ffe8e8;color:#c62828}
.willow_settle_actions{display:grid;gap:6px;min-width:170px}
.willow_settle_actions textarea{width:100%;height:42px}
</style>

<div class="local_desc01 local_desc">
    <p>구독료 결제 시 작가에게 70%가 포인트로 적립됩니다. 정산완료 처리 시 작가 포인트에서 정산금만큼 차감됩니다.</p>
</div>
<div class="btn_fixed_top">
    <a href="./willow_revenue.php" class="btn btn_02">수익 대시보드</a>
    <a href="./willow_subscription_status.php" class="btn btn_02">구독현황</a>
</div>

<h2 class="h2_frm">작가별 정산 가능 금액</h2>
<form method="post" action="./willow_settlement_update.php">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
    <input type="hidden" name="action" value="request">
    <div class="tbl_head01 tbl_wrap">
        <table>
            <thead><tr><th>요청</th><th>작가</th><th>누적 배분</th><th>정산완료</th><th>요청중</th><th>정산가능</th><th>현재 포인트</th><th>정산계좌</th></tr></thead>
            <tbody>
            <?php foreach ($author_rows as $row) { ?>
            <tr>
                <td class="td_mng"><input type="checkbox" name="author_mb_id[]" value="<?php echo get_text($row['author_mb_id']); ?>" <?php echo $row['available_amount'] <= 0 ? 'disabled' : ''; ?>></td>
                <td><strong><?php echo get_text($row['mb_nick'] ? $row['mb_nick'] : ($row['mb_name'] ? $row['mb_name'] : $row['author_mb_id'])); ?></strong><br><?php echo get_text($row['author_mb_id']); ?></td>
                <td class="td_num"><?php echo number_format((int) $row['earned_amount']); ?>P</td>
                <td class="td_num"><?php echo number_format((int) $row['paid_amount']); ?>P</td>
                <td class="td_num"><?php echo number_format((int) $row['requested_amount']); ?>P</td>
                <td class="td_num"><?php echo number_format((int) $row['available_amount']); ?>P</td>
                <td class="td_num"><?php echo number_format((int) $row['mb_point']); ?>P</td>
                <td><?php echo get_text($row['mb_8']); ?> / <?php echo get_text($row['mb_9']); ?><br><?php echo get_text($row['mb_10']); ?></td>
            </tr>
            <?php } ?>
            <?php if (!$author_rows) { ?><tr><td colspan="8" class="empty_table">정산 가능한 수익이 없습니다.</td></tr><?php } ?>
            </tbody>
        </table>
    </div>
    <div class="btn_confirm01 btn_confirm">
        <input type="submit" value="선택 작가 정산요청 생성" class="btn_submit btn">
    </div>
</form>

<h2 class="h2_frm">정산 요청 내역</h2>
<div class="local_sch01 local_sch">
    <a href="./willow_settlement.php?status=requested" class="btn <?php echo $status === 'requested' ? 'btn_03' : 'btn_02'; ?>">요청</a>
    <a href="./willow_settlement.php?status=paid" class="btn <?php echo $status === 'paid' ? 'btn_03' : 'btn_02'; ?>">완료</a>
    <a href="./willow_settlement.php?status=rejected" class="btn <?php echo $status === 'rejected' ? 'btn_03' : 'btn_02'; ?>">반려</a>
    <a href="./willow_settlement.php?status=all" class="btn <?php echo $status === 'all' ? 'btn_03' : 'btn_02'; ?>">전체</a>
</div>
<div class="tbl_head01 tbl_wrap">
    <table>
        <thead><tr><th>ID</th><th>상태</th><th>작가</th><th>요청금액</th><th>계좌</th><th>요청구분</th><th>요청일</th><th>처리일</th><th>처리</th></tr></thead>
        <tbody>
        <?php foreach ($requests as $request) { ?>
        <tr>
            <td class="td_num"><?php echo (int) $request['wsr_id']; ?></td>
            <td><span class="willow_status <?php echo get_text($request['wsr_status']); ?>"><?php echo get_text($request['wsr_status']); ?></span></td>
            <td><strong><?php echo get_text($request['mb_nick'] ? $request['mb_nick'] : $request['mb_name']); ?></strong><br><?php echo get_text($request['author_mb_id']); ?></td>
            <td class="td_num"><?php echo number_format((int) $request['wsr_amount']); ?>P</td>
            <td><?php echo get_text($request['wsr_bank_name']); ?> / <?php echo get_text($request['wsr_account_holder']); ?><br><?php echo get_text($request['wsr_account_number']); ?></td>
            <td><?php echo get_text($request['wsr_memo'] ? $request['wsr_memo'] : '-'); ?></td>
            <td><?php echo get_text($request['wsr_datetime']); ?></td>
            <td><?php echo $request['wsr_processed_datetime'] !== '0000-00-00 00:00:00' ? get_text($request['wsr_processed_datetime']) : '-'; ?></td>
            <td>
                <form class="willow_settle_actions" method="post" action="./willow_settlement_update.php">
                    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
                    <input type="hidden" name="wsr_id" value="<?php echo (int) $request['wsr_id']; ?>">
                    <textarea name="admin_memo" placeholder="처리 메모"><?php echo get_text($request['wsr_admin_memo'], 0); ?></textarea>
                    <?php if ($request['wsr_status'] === 'requested') { ?>
                    <button type="submit" name="action" value="paid" class="btn btn_01">정산완료</button>
                    <button type="submit" name="action" value="rejected" class="btn btn_02">반려</button>
                    <?php } else { ?>
                    <button type="submit" name="action" value="memo" class="btn btn_03">메모저장</button>
                    <?php } ?>
                </form>
            </td>
        </tr>
        <?php } ?>
        <?php if (!$requests) { ?><tr><td colspan="9" class="empty_table">정산 요청 내역이 없습니다.</td></tr><?php } ?>
        </tbody>
    </table>
</div>

<?php
require_once './admin.tail.php';
