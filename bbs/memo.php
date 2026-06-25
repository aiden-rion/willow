<?php
include_once('./_common.php');
include_once(G5_PATH.'/willow/notification.lib.php');

if ($is_guest) {
    goto_url(G5_BBS_URL.'/login.php?url='.urlencode(G5_BBS_URL.'/memo.php'));
}

willow_notification_install();

$definitions = willow_notification_setting_definitions();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_token();

    $posted = isset($_POST['settings']) && is_array($_POST['settings']) ? $_POST['settings'] : array();
    $has_consent = isset($_POST['willow_notification_consent']) && $_POST['willow_notification_consent'] === '1';
    $save_settings = array();
    $enabled_count = 0;

    foreach ($definitions as $key => $definition) {
        $save_settings[$key] = $has_consent && in_array($key, $posted, true) ? 1 : 0;
        if ($save_settings[$key]) {
            $enabled_count++;
        }
    }
    $save_settings['all'] = $enabled_count > 0 ? 1 : 0;

    willow_notification_save_settings($member['mb_id'], $save_settings);
    goto_url(G5_BBS_URL.'/memo.php?saved=1');
}

$g5['title'] = '알림센터';
$settings = willow_notification_settings($member['mb_id']);
if (empty($settings['all'])) {
    foreach ($definitions as $setting_key => $definition) {
        $settings[$setting_key] = 0;
    }
}
$enabled_setting_count = 0;
foreach ($definitions as $setting_key => $definition) {
    if (!empty($settings[$setting_key])) {
        $enabled_setting_count++;
    }
}
$setting_total_count = count($definitions);
$is_all_enabled = $setting_total_count > 0 && $enabled_setting_count === $setting_total_count;
$is_all_disabled = $enabled_setting_count === 0;
$has_notification_consent = $enabled_setting_count > 0;
$token = get_token();
$is_saved = isset($_GET['saved']) && $_GET['saved'] === '1';

add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/willow_mobile.css?ver='.G5_CSS_VER.'">', 10);
include_once('./_head.php');
?>

<script>document.body.classList.add('willow_inner_title_body','willow_notification_setting_body');var willowAutoTitle=document.querySelector('.willow_page_title');if(willowAutoTitle)willowAutoTitle.style.display='none';var willowShellHeader=document.querySelector('#hd.willow_shell_header');if(willowShellHeader)willowShellHeader.style.display='none';</script>
<header class="willow_member_confirm_header">
    <a href="javascript:history.back();" aria-label="뒤로가기"></a>
    <h1>알림센터</h1>
</header>

<main class="willow_notification_setting_page">
    <form class="willow_notification_setting_form" method="post" action="<?php echo G5_BBS_URL; ?>/memo.php">
        <input type="hidden" name="token" value="<?php echo $token; ?>">

        <section class="willow_notification_setting_intro">
            <h2>알림설정변경</h2>

            <div class="willow_notification_segment" role="group" aria-label="알림 전체 수신 설정">
                <label class="<?php echo $is_all_enabled ? 'is_active' : ''; ?>">
                    <input type="radio" name="willow_receive_mode" value="all_on" <?php echo $is_all_enabled ? 'checked' : ''; ?>>
                    <span>모두수신</span>
                </label>
                <label class="<?php echo $is_all_disabled ? 'is_active' : ''; ?>">
                    <input type="radio" name="willow_receive_mode" value="all_off" <?php echo $is_all_disabled ? 'checked' : ''; ?>>
                    <span>수신안함</span>
                </label>
            </div>

            <p>* 구독 중인 작가의 신규 게시물 및 활동 소식을 받아볼 수 있습니다.</p>
            <p>* 서비스 운영 및 결제 관련 중요 안내는 수신 설정과 관계없이 발송될 수 있습니다.</p>
        </section>

        <?php if ($is_saved) { ?>
        <p class="willow_notification_saved">알림 설정이 저장되었습니다.</p>
        <?php } ?>

        <section class="willow_notification_setting_list" aria-label="알림 종류별 수신 설정">
            <?php foreach ($definitions as $key => $definition) { ?>
            <label class="willow_notification_setting_item">
                <span>
                    <strong><?php echo get_text($definition['title']); ?></strong>
                    <em><?php echo get_text($definition['description']); ?></em>
                </span>
                <input type="checkbox" name="settings[]" value="<?php echo get_text($key); ?>" <?php echo !empty($settings[$key]) ? 'checked' : ''; ?>>
                <i aria-hidden="true"></i>
            </label>
            <?php } ?>
        </section>

        <div class="willow_notification_consent_row">
            <label>
                <input type="checkbox" name="willow_notification_consent" value="1" <?php echo $has_notification_consent ? 'checked' : ''; ?>>
                <i aria-hidden="true"></i>
                <span>알림 수신동의</span>
            </label>
            <button type="button" class="willow_notification_consent_open">보기</button>
        </div>
        <button type="submit" class="willow_notification_setting_submit">저장하기</button>
    </form>
</main>

<div class="willow_notification_consent_modal" hidden>
    <div class="willow_notification_consent_dim" data-willow-consent-close></div>
    <section class="willow_notification_consent_panel" role="dialog" aria-modal="true" aria-labelledby="willow_notification_consent_title">
        <button type="button" class="willow_notification_consent_close" data-willow-consent-close aria-label="닫기"></button>
        <h2 id="willow_notification_consent_title">알림 수신동의</h2>
        <p>윌로우는 좋아하는 작가의 새로운 이야기와 회원님의 활동 소식을 전달해 드립니다.</p>
        <div>
            <strong>수신동의 시 다음과 같은 알림을 받으실 수 있습니다.</strong>
            <span>새로운 게시물 업로드 소식</span>
            <span>댓글 및 답글 알림</span>
            <span>좋아요 및 구독관련 알림</span>
            <span>이벤트 공지사항 및 혜택 안내</span>
        </div>
        <b>TIP</b>
        <p>언제든지 알림 설정을 변경할 수 있으며, 서비스 운영에 필요한 주요 안내는 수신 여부와 관계없이 제공될 수 있습니다.</p>
    </section>
</div>

<script>
(function () {
    var form = document.querySelector('.willow_notification_setting_form');
    if (!form) return;

    var segmentLabels = form.querySelectorAll('.willow_notification_segment label');
    var allOn = form.querySelector('input[name="willow_receive_mode"][value="all_on"]');
    var allOff = form.querySelector('input[name="willow_receive_mode"][value="all_off"]');
    var itemChecks = form.querySelectorAll('.willow_notification_setting_item input[type="checkbox"]');
    var consentCheck = form.querySelector('input[name="willow_notification_consent"]');

    function refreshSegment() {
        for (var i = 0; i < segmentLabels.length; i++) {
            var input = segmentLabels[i].querySelector('input');
            segmentLabels[i].classList.toggle('is_active', !!input.checked);
        }
    }

    function syncSegmentWithItems() {
        if (!allOn || !allOff) return;
        var checkedCount = 0;
        for (var i = 0; i < itemChecks.length; i++) {
            if (itemChecks[i].checked) checkedCount++;
        }
        allOn.checked = itemChecks.length > 0 && checkedCount === itemChecks.length;
        allOff.checked = checkedCount === 0;
        if (consentCheck) consentCheck.checked = checkedCount > 0;
        refreshSegment();
    }

    if (allOn && allOff) {
        allOn.addEventListener('change', function () {
            if (!allOn.checked) return;
            for (var i = 0; i < itemChecks.length; i++) itemChecks[i].checked = true;
            if (consentCheck) consentCheck.checked = true;
            refreshSegment();
        });
        allOff.addEventListener('change', function () {
            if (!allOff.checked) return;
            allOn.checked = false;
            for (var i = 0; i < itemChecks.length; i++) itemChecks[i].checked = false;
            if (consentCheck) consentCheck.checked = false;
            refreshSegment();
        });
    }

    if (consentCheck) {
        consentCheck.addEventListener('change', function () {
            for (var i = 0; i < itemChecks.length; i++) itemChecks[i].checked = consentCheck.checked;
            if (allOn) allOn.checked = consentCheck.checked;
            if (allOff) allOff.checked = !consentCheck.checked;
            refreshSegment();
        });
    }

    for (var i = 0; i < itemChecks.length; i++) {
        itemChecks[i].addEventListener('change', function () {
            syncSegmentWithItems();
        });
    }

    var modal = document.querySelector('.willow_notification_consent_modal');
    var opener = document.querySelector('.willow_notification_consent_open');
    var closers = document.querySelectorAll('[data-willow-consent-close]');

    if (modal && opener) {
        opener.addEventListener('click', function () {
            modal.hidden = false;
            document.body.classList.add('willow_modal_open');
        });
    }

    for (var k = 0; k < closers.length; k++) {
        closers[k].addEventListener('click', function () {
            modal.hidden = true;
            document.body.classList.remove('willow_modal_open');
        });
    }
})();
</script>

<?php
include_once('./_tail.php');
