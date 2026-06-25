<?php
include_once('./_common.php');
include_once('./revenue.lib.php');

if (!$is_member) {
    goto_url(G5_BBS_URL.'/login.php?url='.urlencode(G5_URL.'/willow/point_settlement_complete.php'));
}

$wsr_id = isset($_GET['wsr_id']) ? (int) $_GET['wsr_id'] : 0;
$settlement_table = willow_settlement_request_table();
$request = sql_fetch(" select * from `{$settlement_table}` where wsr_id = '{$wsr_id}' and author_mb_id = '".sql_escape_string($member['mb_id'])."' ", false);

if (empty($request['wsr_id'])) {
    alert('정산요청 내역을 찾을 수 없습니다.', G5_BBS_URL.'/point.php');
}

$remaining_point = max(0, (int) $member['mb_point'] - (int) $request['wsr_amount']);

$g5['title'] = '정산요청 완료';
include_once(G5_PATH.'/head.sub.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/willow_content.css?ver='.G5_CSS_VER.'">', 10);
?>

<main class="willow_point_app willow_point_complete_page">
    <header class="willow_member_confirm_header willow_point_header">
        <a class="willow_point_close" href="<?php echo G5_BBS_URL; ?>/point.php" aria-label="닫기"></a>
        <h1>정산요청 완료</h1>
    </header>

    <section class="willow_point_complete_hero">
        <span aria-hidden="true"></span>
        <h2>포인트 정산요청이 완료되었습니다.</h2>
        <p>마이페이지를 통해 정산내역을 확인 가능합니다.</p>
    </section>

    <section class="willow_point_complete_section">
        <h2>정산요청내역</h2>
        <dl class="willow_point_complete_card">
            <div>
                <dt>보유포인트</dt>
                <dd><?php echo number_format((int) $member['mb_point']); ?></dd>
            </div>
            <div>
                <dt>정산요청</dt>
                <dd><?php echo number_format((int) $request['wsr_amount']); ?></dd>
            </div>
            <div>
                <dt>잔여포인트</dt>
                <dd><?php echo number_format($remaining_point); ?></dd>
            </div>
        </dl>
    </section>

    <section class="willow_point_complete_section">
        <h2>정산 계좌정보</h2>
        <dl class="willow_point_complete_card">
            <div>
                <dt>예금주</dt>
                <dd><?php echo get_text($request['wsr_account_holder']); ?></dd>
            </div>
            <div>
                <dt>은행명</dt>
                <dd><?php echo get_text($request['wsr_bank_name']); ?></dd>
            </div>
            <div>
                <dt>계좌번호</dt>
                <dd><?php echo get_text($request['wsr_account_number']); ?></dd>
            </div>
        </dl>
    </section>

    <p class="willow_point_notice">정산요청 후 영업일 기준 7일이내 해당 계좌로 입금됩니다.</p>

    <nav class="willow_point_complete_nav" aria-label="정산요청 완료 이동">
        <a href="<?php echo G5_URL; ?>">메인으로</a>
        <a href="<?php echo G5_BBS_URL; ?>/point.php">포인트내역</a>
    </nav>
</main>

<?php
include_once(G5_PATH.'/tail.sub.php');
