<?php
include_once('./_common.php');
include_once('./payment.lib.php');
include_once(G5_LIB_PATH.'/subscription.lib.php');

if (!$is_member) {
    goto_url(G5_BBS_URL.'/login.php?url='.urlencode(G5_URL.'/willow/payment.php'));
}

$return_url = isset($_GET['return']) ? trim($_GET['return']) : G5_URL.'/willow/menu.php';
if ($return_url === '') {
    $return_url = G5_URL.'/willow/menu.php';
}
check_url_host($return_url);

$step = isset($_GET['step']) ? preg_replace('/[^a-z_]/', '', $_GET['step']) : 'list';
$ci_id = isset($_GET['ci_id']) ? (int) $_GET['ci_id'] : 0;
$cards = willow_payment_cards($member['mb_id']);
$default_card = willow_payment_default_card($member['mb_id']);
$default_id = !empty($default_card['ci_id']) ? (int) $default_card['ci_id'] : 0;
if ($default_id && $cards) {
    usort($cards, function ($a, $b) use ($default_id) {
        $a_default = (int) $a['ci_id'] === $default_id ? 0 : 1;
        $b_default = (int) $b['ci_id'] === $default_id ? 0 : 1;
        if ($a_default !== $b_default) {
            return $a_default - $b_default;
        }

        return (int) $b['ci_id'] - (int) $a['ci_id'];
    });
}
$complete_card = array();
$toss_client_key = willow_toss_client_key();
$toss_customer_key = willow_toss_customer_key($member['mb_id']);

if ($step === 'complete') {
    $card_table = willow_payment_card_table();
    $complete_card = sql_fetch(" select * from `{$card_table}`
        where ci_id = '{$ci_id}'
            and mb_id = '".sql_escape_string($member['mb_id'])."'
        limit 1 ", false);
    if (empty($complete_card['ci_id'])) {
        $complete_card = $default_card;
    }
}

$title = $step === 'list' ? '결제수단 변경' : '카드 등록하기';
$g5['title'] = $title;
include_once(G5_PATH.'/head.sub.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/willow_content.css?ver='.G5_CSS_VER.'">', 10);
?>

<main class="willow_subscribe_app is_light willow_payment_app is_<?php echo $step; ?>">
    <header class="willow_subscribe_header">
        <?php if ($step === 'list') { ?>
        <a class="willow_subscribe_back" href="<?php echo $return_url; ?>" aria-label="뒤로가기"><img src="<?php echo G5_IMG_URL; ?>/ico_back.png" alt=""></a>
        <?php } ?>
        <h1><?php echo $title; ?></h1>
        <?php if ($step !== 'list') { ?>
        <a class="willow_subscribe_close" href="<?php echo G5_URL; ?>/willow/payment.php?return=<?php echo urlencode($return_url); ?>" aria-label="닫기"></a>
        <?php } ?>
    </header>

    <?php if ($step === 'complete') { ?>
    <section class="willow_payment_complete">
        <img class="willow_payment_complete_icon" src="<?php echo G5_IMG_URL; ?>/ico_card.png" alt="">
        <h2>신규카드 등록이<br>완료되었습니다.</h2>
        <p>등록된 카드정보는 암호화를 통해<br>안전하게 관리하고 있습니다.</p>
    </section>

    <section class="willow_subscribe_info willow_payment_complete_info">
        <h2>카드정보</h2>
        <dl>
            <div><dt>카드사</dt><dd><?php echo get_text($complete_card['od_card_name']); ?></dd></div>
            <div><dt>카드번호</dt><dd><?php echo get_text(str_replace(' ', '', $complete_card['card_mask_number'])); ?></dd></div>
            <div><dt>카드구분</dt><dd>개인카드</dd></div>
            <?php $complete_card_expiry = willow_payment_card_expiry($complete_card['ci_id']); ?>
            <div><dt>유효기간</dt><dd><?php echo get_text($complete_card_expiry ? $complete_card_expiry : '-'); ?></dd></div>
            <div><dt>등록일시</dt><dd><?php echo get_text(substr($complete_card['ci_time'], 0, 16)); ?></dd></div>
            <div><dt>결제동의</dt><dd>완료</dd></div>
        </dl>
    </section>

    <nav class="willow_subscribe_bottom is_split" aria-label="카드 등록 완료 메뉴">
        <a href="<?php echo G5_URL; ?>">메인으로</a>
        <a href="<?php echo G5_URL; ?>/willow/payment.php?return=<?php echo urlencode($return_url); ?>">결제수단 확인</a>
    </nav>
    <?php } else if ($step === 'fail') { ?>
    <section class="willow_payment_complete">
        <img class="willow_payment_complete_icon" src="<?php echo G5_IMG_URL; ?>/ico_card.png" alt="">
        <h2>카드 등록에<br>실패했습니다.</h2>
        <p><?php echo get_text(isset($_GET['message']) ? $_GET['message'] : '결제수단 인증을 완료하지 못했습니다.'); ?></p>
    </section>

    <nav class="willow_subscribe_bottom is_split" aria-label="카드 등록 실패 메뉴">
        <a href="<?php echo G5_URL; ?>/willow/payment.php?return=<?php echo urlencode($return_url); ?>">결제수단 확인</a>
        <a href="<?php echo G5_URL; ?>/willow/payment.php?step=toss&amp;return=<?php echo urlencode($return_url); ?>">다시 등록</a>
    </nav>
    <?php } else if ($step === 'toss') { ?>
    <section class="willow_toss_mock">
        <strong class="willow_toss_logo">toss payments</strong>
        <h2>정기결제 카드를<br>등록해주세요</h2>
        <p>토스페이먼츠 인증창에서 카드 본인인증을 완료하면 월 정기결제에 사용할 결제수단이 등록됩니다.</p>
        <?php if ($toss_client_key) { ?>
        <button type="button" class="willow_toss_register_button" data-toss-billing>카드 등록 시작</button>
        <p class="willow_toss_notice">등록된 카드는 구독료 자동결제에만 사용됩니다.</p>
        <?php } else { ?>
        <div class="willow_subscribe_empty_state">
            <strong>토스페이먼츠 키가 설정되어 있지 않습니다.</strong>
            <p>관리자에서 토스페이먼츠 API 키를 등록해주세요.</p>
        </div>
        <?php } ?>
    </section>
    <?php if ($toss_client_key) { ?>
    <script src="https://js.tosspayments.com/v2/standard"></script>
    <script>
    document.querySelector('[data-toss-billing]').addEventListener('click', async function() {
        var button = this;
        button.disabled = true;
        try {
            var tossPayments = TossPayments(<?php echo json_encode($toss_client_key, JSON_UNESCAPED_UNICODE); ?>);
            var payment = tossPayments.payment({customerKey: <?php echo json_encode($toss_customer_key, JSON_UNESCAPED_UNICODE); ?>});
            await payment.requestBillingAuth({
                method: 'CARD',
                successUrl: '<?php echo G5_URL; ?>/willow/payment_update.php?return=<?php echo rawurlencode($return_url); ?>',
                failUrl: '<?php echo G5_URL; ?>/willow/payment.php?step=fail&return=<?php echo rawurlencode($return_url); ?>',
                customerEmail: <?php echo json_encode($member['mb_email'], JSON_UNESCAPED_UNICODE); ?>,
                customerName: <?php echo json_encode($member['mb_name'] ? $member['mb_name'] : $member['mb_nick'], JSON_UNESCAPED_UNICODE); ?>
            });
        } catch (error) {
            button.disabled = false;
            alert(error && error.message ? error.message : '카드 등록창을 열 수 없습니다.');
        }
    });
    </script>
    <?php } ?>
    <?php include G5_PATH.'/willow/bottom_nav.inc.php'; ?>
    <?php } else { ?>
    <section class="willow_payment_body">
        <h2>등록된 카드를 확인해주세요</h2>
        <p>정기결제 등록을 위한 동의가 필요합니다.</p>

        <div class="willow_payment_cards">
            <?php foreach ($cards as $card) { ?>
            <?php $is_default = (int) $card['ci_id'] === $default_id; ?>
            <?php $card_expiry = willow_payment_card_expiry($card['ci_id']); ?>
            <article class="willow_payment_card <?php echo $is_default ? 'is_default' : ''; ?>">
                <div class="willow_payment_card_status">
                    <span><em>카드</em><?php echo $is_default ? ' 기본카드' : ''; ?></span>
                    <?php if (!$is_default) { ?>
                    <form method="post" action="<?php echo G5_URL; ?>/willow/payment_card_update.php">
                        <input type="hidden" name="return" value="<?php echo get_text(G5_URL.'/willow/payment.php?return='.urlencode($return_url)); ?>">
                        <input type="hidden" name="ci_id" value="<?php echo (int) $card['ci_id']; ?>">
                        <button type="submit" name="action" value="delete" onclick="return confirm('카드를 삭제하시겠습니까?');">삭제 <i class="fa fa-trash-o" aria-hidden="true"></i></button>
                    </form>
                    <?php } ?>
                </div>
                <strong><?php echo get_text($card['od_card_name']); ?> <?php echo get_text(substr($card['card_mask_number'], -4)); ?></strong>
                <dl>
                    <div><dt>카드번호</dt><dd><?php echo get_text($card['card_mask_number']); ?></dd></div>
                    <div><dt>유효기간</dt><dd><?php echo get_text($card_expiry ? $card_expiry : '-'); ?></dd></div>
                    <div><dt>등록일시</dt><dd><?php echo get_text(substr($card['ci_time'], 0, 16)); ?></dd></div>
                    <div><dt>결제동의</dt><dd>완료</dd></div>
                </dl>
                <?php if (!$is_default) { ?>
                <form class="willow_payment_change_form" method="post" action="<?php echo G5_URL; ?>/willow/payment_card_update.php">
                    <input type="hidden" name="return" value="<?php echo get_text(G5_URL.'/willow/payment.php?return='.urlencode($return_url)); ?>">
                    <input type="hidden" name="ci_id" value="<?php echo (int) $card['ci_id']; ?>">
                    <button type="submit" name="action" value="default">결제카드 변경</button>
                </form>
                <?php } ?>
            </article>
            <?php } ?>
            <?php if (!$cards) { ?>
            <div class="willow_subscribe_empty_state">
                <strong>등록된 카드가 없습니다.</strong>
                <p>신규카드를 등록해 구독 결제를 준비해주세요.</p>
            </div>
            <?php } ?>
        </div>
    </section>

    <nav class="willow_subscribe_bottom">
        <a href="<?php echo G5_URL; ?>/willow/payment.php?step=toss&amp;return=<?php echo urlencode($return_url); ?>">신규카드 등록하기</a>
    </nav>
    <?php } ?>
</main>

<?php
include_once(G5_PATH.'/tail.sub.php');
