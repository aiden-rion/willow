<?php
include_once('./_common.php');
include_once('./revenue.lib.php');
include_once('./content.lib.php');

if (!$is_member) {
    goto_url(G5_BBS_URL.'/login.php?url='.urlencode(G5_URL.'/willow/settlement.php'));
}

$is_author = ((int) $member['mb_level'] >= 3) || (!empty($member['mb_2']) && $member['mb_2'] === 'author');
if (!$is_author) {
    alert('작가회원만 이용할 수 있습니다.', G5_URL.'/willow/menu.php');
}

willow_revenue_backfill_subscription_payments();
$balance = array();
foreach (willow_revenue_author_balance_rows() as $row) {
    if ($row['author_mb_id'] === $member['mb_id']) {
        $balance = $row;
        break;
    }
}

if (!$balance) {
    $balance = array(
        'earned_amount' => 0,
        'paid_amount' => 0,
        'requested_amount' => 0,
        'available_amount' => 0,
        'mb_point' => (int) $member['mb_point'],
        'mb_8' => $member['mb_8'],
        'mb_9' => $member['mb_9'],
        'mb_10' => $member['mb_10'],
    );
}

$settlement_table = willow_settlement_request_table();
$requests = array();
$result = sql_query(" select * from `{$settlement_table}`
    where author_mb_id = '".sql_escape_string($member['mb_id'])."'
    order by wsr_id desc
    limit 20 ", false);
if ($result) {
    while ($row = sql_fetch_array($result)) {
        $requests[] = $row;
    }
}

$g5['title'] = '정산요청';
include_once(G5_PATH.'/head.sub.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/willow_content.css?ver='.G5_CSS_VER.'">', 10);
?>

<main class="willow_content_app willow_settlement_page">
    <header class="willow_author_page_header">
        <a href="<?php echo G5_URL; ?>/willow/menu.php" aria-label="뒤로가기"><img src="<?php echo G5_IMG_URL; ?>/ico_back.png" alt=""></a>
        <h1>정산요청</h1>
    </header>

    <section class="willow_settlement_summary">
        <span>정산가능 포인트</span>
        <strong><?php echo number_format((int) $balance['available_amount']); ?>P</strong>
        <p>누적 배분 <?php echo number_format((int) $balance['earned_amount']); ?>P · 정산완료 <?php echo number_format((int) $balance['paid_amount']); ?>P · 요청중 <?php echo number_format((int) $balance['requested_amount']); ?>P</p>
    </section>

    <section class="willow_settlement_account">
        <h2>정산계좌</h2>
        <p><?php echo get_text($balance['mb_8'] ? $balance['mb_8'] : '은행 미등록'); ?> / <?php echo get_text($balance['mb_9'] ? $balance['mb_9'] : '예금주 미등록'); ?></p>
        <strong><?php echo get_text($balance['mb_10'] ? $balance['mb_10'] : '계좌번호 미등록'); ?></strong>
    </section>

    <form class="willow_settlement_request" method="post" action="<?php echo G5_URL; ?>/willow/settlement_update.php">
        <input type="hidden" name="token" value="<?php echo get_token(); ?>">
        <button type="submit" <?php echo (int) $balance['available_amount'] <= 0 ? 'disabled' : ''; ?>>정산요청하기</button>
    </form>

    <section class="willow_settlement_history">
        <h2>정산내역</h2>
        <?php if ($requests) { ?>
        <?php foreach ($requests as $request) { ?>
        <article>
            <div>
                <strong><?php echo number_format((int) $request['wsr_amount']); ?>P</strong>
                <span><?php echo get_text($request['wsr_datetime']); ?></span>
            </div>
            <em><?php echo get_text($request['wsr_status']); ?></em>
        </article>
        <?php } ?>
        <?php } else { ?>
        <p class="willow_settlement_empty">정산 내역이 없습니다.</p>
        <?php } ?>
    </section>
</main>

<?php
include_once(G5_PATH.'/tail.sub.php');
