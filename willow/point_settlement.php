<?php
include_once('./_common.php');
include_once('./revenue.lib.php');

if (!$is_member) {
    goto_url(G5_BBS_URL.'/login.php?url='.urlencode(G5_URL.'/willow/point_settlement.php'));
}

if (!willow_revenue_is_author($member)) {
    alert('작가회원만 정산요청을 할 수 있습니다.', G5_BBS_URL.'/point.php');
}

$balance = willow_revenue_author_balance($member['mb_id']);
$available_point = isset($balance['available_amount']) ? (int) $balance['available_amount'] : 0;
$banks = array('국민은행', '신한은행', '우리은행', '하나은행', '농협은행', '기업은행', '카카오뱅크', '토스뱅크', '케이뱅크', '삼성증권');

$g5['title'] = '포인트';
include_once(G5_PATH.'/head.sub.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/willow_content.css?ver='.G5_CSS_VER.'">', 10);
?>

<main class="willow_point_app willow_point_request_page">
    <header class="willow_member_confirm_header willow_point_header">
        <a href="<?php echo G5_BBS_URL; ?>/point.php" aria-label="뒤로가기"></a>
        <h1>포인트</h1>
    </header>

    <section class="willow_point_intro">
        <h2>정산요청하실 포인트를 입력해주세요</h2>
        <p>정산요청 포인트는 10,000포인트 이상부터 가능합니다.</p>
    </section>

    <section class="willow_point_summary">
        <dl>
            <div>
                <dt>보유포인트</dt>
                <dd><?php echo number_format((int) $member['mb_point']); ?></dd>
            </div>
            <div>
                <dt>정산가능 포인트</dt>
                <dd><?php echo number_format($available_point); ?></dd>
            </div>
        </dl>
    </section>

    <form class="willow_point_request_form" method="post" action="<?php echo G5_URL; ?>/willow/point_settlement_update.php">
        <input type="hidden" name="token" value="<?php echo get_token(); ?>">

        <label class="willow_point_field">
            <span>정산요청 포인트</span>
            <input type="number" name="amount" min="10000" step="10000" inputmode="numeric" placeholder="포인트를 입력해주세요" required>
            <em>정산요청 포인트는 만원 단위로 입력해주세요</em>
        </label>

        <section class="willow_point_account">
            <h2>정산 계좌정보</h2>
            <label class="willow_point_field">
                <span>예금주</span>
                <input type="text" name="account_holder" value="<?php echo get_text($member['mb_9']); ?>" placeholder="예금주를 입력해주세요" required>
            </label>
            <label class="willow_point_field willow_point_select">
                <span>은행명</span>
                <select name="bank_name" required>
                    <option value="">은행을 선택해주세요</option>
                    <?php foreach ($banks as $bank) { ?>
                    <option value="<?php echo get_text($bank); ?>" <?php echo $member['mb_8'] === $bank ? 'selected' : ''; ?>><?php echo get_text($bank); ?></option>
                    <?php } ?>
                </select>
            </label>
            <label class="willow_point_field">
                <span>계좌번호</span>
                <input type="text" name="account_number" value="<?php echo get_text($member['mb_10']); ?>" placeholder="계좌번호를 입력해주세요" required>
            </label>
            <a class="willow_point_account_edit" href="<?php echo G5_BBS_URL; ?>/member_confirm.php?url=register_form.php">계좌정보 수정하기</a>
        </section>

        <div class="willow_point_bottom">
            <button type="submit">정산요청</button>
        </div>
    </form>
</main>

<?php
include_once(G5_PATH.'/tail.sub.php');
