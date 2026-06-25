<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/willow_mobile.css?ver='.G5_CSS_VER.'">', 0);
$willow_confirm_css_ver = is_file($member_skin_path.'/style.css') ? filemtime($member_skin_path.'/style.css') : G5_CSS_VER;
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css?ver='.$willow_confirm_css_ver.'">', 10);

$confirm_phone = isset($member['mb_hp']) ? preg_replace('/[^0-9]/', '', $member['mb_hp']) : '';
$display_phone = $confirm_phone ? hyphen_hp_number($confirm_phone) : '등록된 휴대폰번호 없음';
$confirm_action_url = G5_BBS_URL.'/member_confirm_phone.php';
$is_leave = ($url == 'member_leave.php');
?>

<script>document.body.classList.add('willow_member_confirm_body');</script>

<div id="mb_confirm" class="willow_member_confirm">
    <header class="willow_member_confirm_header">
        <a href="javascript:history.back();" aria-label="뒤로가기"></a>
        <h1>휴대폰인증확인</h1>
    </header>

    <section class="willow_member_confirm_intro">
        <strong>휴대폰 인증이 필요합니다.</strong>
        <p><?php echo $is_leave ? '회원탈퇴를 위해 등록된 휴대폰번호로 본인 확인을 진행합니다.' : '회원님의 정보를 안전하게 보호하기 위해 등록된 휴대폰번호로 본인 확인을 진행합니다.'; ?></p>
    </section>

    <form name="fmemberconfirm" action="<?php echo $confirm_action_url; ?>" onsubmit="return fmemberconfirm_submit(this);" method="post">
    <input type="hidden" name="mb_id" value="<?php echo $member['mb_id'] ?>">
    <input type="hidden" name="w" value="u">
    <input type="hidden" name="url" value="<?php echo get_text($url); ?>">
    <input type="hidden" name="auth_sent" id="auth_sent" value="0">

    <fieldset>
        <div class="willow_confirm_row">
            <span>휴대폰번호</span>
            <strong><?php echo get_text($display_phone); ?></strong>
        </div>
        <button type="button" id="btn_auth_send" class="willow_auth_send" <?php echo $confirm_phone ? '' : 'disabled'; ?>>인증번호 받기</button>
        <label for="confirm_auth_code" class="sound_only">인증번호<strong>필수</strong></label>
        <div class="willow_confirm_code">
            <input type="text" name="auth_code" id="confirm_auth_code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="인증번호 6자리" required class="frm_input" autocomplete="one-time-code" disabled>
            <span id="confirm_auth_timer">03:00</span>
        </div>
        <input type="submit" value="인증번호 확인" id="btn_submit" class="btn_submit">
    </fieldset>

    </form>

</div>

<script>
var willowConfirmTimer = null;
var willowConfirmRemain = 180;
var willowConfirmHasPhone = <?php echo $confirm_phone ? 'true' : 'false'; ?>;
var willowConfirmSent = document.getElementById('auth_sent');
var willowConfirmCode = document.getElementById('confirm_auth_code');
var willowConfirmTimerEl = document.getElementById('confirm_auth_timer');
var willowConfirmSendBtn = document.getElementById('btn_auth_send');

function willowUpdateConfirmTimer()
{
    var minute = String(Math.floor(willowConfirmRemain / 60)).padStart(2, '0');
    var second = String(willowConfirmRemain % 60).padStart(2, '0');
    willowConfirmTimerEl.textContent = minute + ':' + second;
}

function willowStartConfirmTimer()
{
    clearInterval(willowConfirmTimer);
    willowConfirmRemain = 180;
    willowUpdateConfirmTimer();
    willowConfirmTimer = setInterval(function () {
        willowConfirmRemain -= 1;
        willowUpdateConfirmTimer();
        if (willowConfirmRemain <= 0) {
            clearInterval(willowConfirmTimer);
            willowConfirmSent.value = '0';
            willowConfirmCode.disabled = true;
            alert('인증 시간이 만료되었습니다. 인증번호를 다시 요청해 주세요.');
        }
    }, 1000);
}

willowConfirmSendBtn.addEventListener('click', function () {
    if (!willowConfirmHasPhone) {
        alert('회원정보에 등록된 휴대폰번호가 없습니다.');
        return;
    }
    willowConfirmSent.value = '1';
    willowConfirmCode.disabled = false;
    willowConfirmCode.focus();
    willowStartConfirmTimer();
    alert('인증번호가 발송되었습니다.');
});

function fmemberconfirm_submit(f)
{
    var code = f.auth_code.value.replace(/[^0-9]/g, '');
    f.auth_code.value = code;

    if (willowConfirmSent.value !== '1' || willowConfirmRemain <= 0) {
        alert('인증번호를 먼저 받아 주세요.');
        return false;
    }

    if (code.length !== 6) {
        alert('인증번호 6자리를 입력해 주세요.');
        f.auth_code.focus();
        return false;
    }

    document.getElementById("btn_submit").disabled = true;

    return true;
}
</script>
