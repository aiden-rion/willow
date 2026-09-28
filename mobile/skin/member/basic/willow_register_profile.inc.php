<?php
if (!defined('_GNUBOARD_')) exit;

add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/willow_register_profile.css?ver='.G5_CSS_VER.'">', 20);
include_once(G5_PATH.'/willow/content.lib.php');

$willow_is_author = willow_author_is_escapee($member);
$willow_profile_img = willow_member_avatar($member);

$willow_categories = willow_get_categories(true);
$willow_selected_categories = array();
if (!empty($member['mb_3'])) {
    foreach (explode(',', $member['mb_3']) as $category) {
        $category = trim($category);
        if ($category !== '') {
            $willow_selected_categories[] = $category;
        }
    }
}

$willow_bank_options = array('국민은행', '신한은행', '우리은행', '하나은행', '농협은행', '기업은행', '카카오뱅크', '토스뱅크');
$willow_account_bank = $member['mb_8'];
$willow_account_holder = $member['mb_9'];
if (!in_array($willow_account_bank, $willow_bank_options, true) && in_array($member['mb_9'], $willow_bank_options, true)) {
    $willow_account_bank = $member['mb_9'];
    $willow_account_holder = $member['mb_8'];
}
?>

<script>document.body.classList.add('willow_profile_edit_body');</script>

<div class="willow_profile_edit">
    <header class="willow_detail_header">
        <a class="willow_back" href="<?php echo G5_URL; ?>/willow/menu.php" data-willow-safe-back aria-label="뒤로가기"></a>
        <h1><?php echo get_head_title($g5['title']); ?></h1>
    </header>

    <form name="fregisterform" id="fregisterform" action="<?php echo $register_action_url ?>" onsubmit="return fregisterform_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="w" value="<?php echo $w ?>">
        <input type="hidden" name="url" value="<?php echo $urlencode ?>">
        <input type="hidden" name="agree" value="<?php echo $agree ?>">
        <input type="hidden" name="agree2" value="<?php echo $agree2 ?>">
        <input type="hidden" name="cert_type" value="<?php echo $member['mb_certify']; ?>">
        <input type="hidden" name="cert_no" value="">
        <input type="hidden" name="token" value="<?php echo $token; ?>">
        <input type="hidden" name="willow_profile_edit" value="1">
        <input type="hidden" name="willow_account_verified" value="0">
        <input type="hidden" name="mb_id" value="<?php echo get_text($member['mb_id']); ?>">
        <input type="hidden" name="mb_name" value="<?php echo get_text($member['mb_name']); ?>">
        <input type="hidden" name="old_email" value="<?php echo get_text($member['mb_email']); ?>">
        <input type="hidden" name="mb_email" value="<?php echo get_text($member['mb_email']); ?>">
        <input type="hidden" name="mb_open" value="<?php echo (int) $member['mb_open']; ?>">
        <input type="hidden" name="mb_open_default" value="<?php echo (int) $member['mb_open']; ?>">
        <input type="hidden" name="mb_mailling" value="<?php echo (int) $member['mb_mailling']; ?>">
        <input type="hidden" name="mb_mailling_default" value="<?php echo (int) $member['mb_mailling']; ?>">
        <input type="hidden" name="mb_sms" value="<?php echo (int) $member['mb_sms']; ?>">
        <input type="hidden" name="mb_sms_default" value="<?php echo (int) $member['mb_sms']; ?>">
        <input type="hidden" name="mb_marketing_agree" value="<?php echo (int) $member['mb_marketing_agree']; ?>">
        <input type="hidden" name="mb_marketing_agree_default" value="<?php echo (int) $member['mb_marketing_agree']; ?>">
        <input type="hidden" name="mb_thirdparty_agree" value="<?php echo (int) $member['mb_thirdparty_agree']; ?>">
        <input type="hidden" name="mb_thirdparty_agree_default" value="<?php echo (int) $member['mb_thirdparty_agree']; ?>">
        <input type="hidden" name="mb_nick_default" value="<?php echo get_text($member['mb_nick']); ?>">
        <input type="hidden" name="mb_2" value="<?php echo get_text($member['mb_2']); ?>">
        <input type="hidden" name="mb_4" value="<?php echo get_text($member['mb_4']); ?>">
        <input type="hidden" name="mb_5" value="<?php echo get_text($member['mb_5']); ?>">
        <input type="hidden" name="mb_6" value="<?php echo get_text($member['mb_6']); ?>">
        <input type="hidden" name="mb_7" value="<?php echo get_text($member['mb_7']); ?>">
        <?php if (!$willow_is_author) { ?>
        <input type="hidden" name="mb_hp" value="<?php echo get_text($member['mb_hp']); ?>">
        <input type="hidden" name="mb_profile" value="<?php echo get_text($member['mb_profile']); ?>">
        <input type="hidden" name="mb_1" value="<?php echo get_text($member['mb_1']); ?>">
        <input type="hidden" name="mb_3" value="<?php echo get_text($member['mb_3']); ?>">
        <input type="hidden" name="mb_8" value="<?php echo get_text($member['mb_8']); ?>">
        <input type="hidden" name="mb_9" value="<?php echo get_text($member['mb_9']); ?>">
        <input type="hidden" name="mb_10" value="<?php echo get_text($member['mb_10']); ?>">
        <?php } ?>

        <section class="willow_profile_section willow_profile_intro">
            <h2>프로필 등록</h2>
            <div class="willow_profile_photo">
                <img src="<?php echo $willow_profile_img; ?>" alt="">
                <?php if ($config['cf_member_img_size'] && $config['cf_member_img_width'] && $config['cf_member_img_height']) { ?>
                <label for="reg_mb_img">사진변경</label>
                <input type="file" name="mb_img" id="reg_mb_img" accept="image/gif,image/jpeg,image/png">
                <?php } ?>
            </div>

            <div class="willow_profile_field">
                <label for="reg_mb_nick">닉네임</label>
                <input type="text" name="mb_nick" value="<?php echo get_text($member['mb_nick']); ?>" id="reg_mb_nick" required class="required nospace" maxlength="20">
                <span id="msg_mb_nick"></span>
            </div>

            <?php if ($willow_is_author) { ?>
            <div class="willow_profile_field">
                <label for="reg_mb_hp">연락처</label>
                <input type="text" name="mb_hp" value="<?php echo get_text($member['mb_hp']); ?>" id="reg_mb_hp" readonly>
                <p>* 연락처 정보는 변경이 불가능합니다.</p>
            </div>

            <div class="willow_profile_field">
                <label for="reg_mb_profile">작가소개</label>
                <textarea name="mb_profile" id="reg_mb_profile" rows="4"><?php echo get_text($member['mb_profile']); ?></textarea>
            </div>
            <?php } ?>
        </section>

        <?php if ($willow_is_author) { ?>
        <section class="willow_profile_section">
            <h2>주요 카테고리</h2>
            <p class="willow_profile_help">* 주로 작성하는 글 카테고리를 선택하세요</p>
            <input type="hidden" name="mb_3" id="willow_mb_3" value="<?php echo get_text($member['mb_3']); ?>">
            <div class="willow_category_group">
                <?php foreach ($willow_categories as $category) { ?>
                <?php if ($category['keyword'] === '') continue; ?>
                <?php $checked = in_array($category['keyword'], $willow_selected_categories) ? 'checked' : ''; ?>
                <label>
                    <input type="checkbox" value="<?php echo get_text($category['keyword']); ?>" <?php echo $checked; ?>>
                    <span><?php echo get_text($category['label']); ?></span>
                </label>
                <?php } ?>
            </div>
        </section>

        <section class="willow_profile_section">
            <h2>정산 계좌정보</h2>
            <div class="willow_profile_field">
                <label for="reg_mb_8">은행명</label>
                <select name="mb_8" id="reg_mb_8" data-account-bank>
                    <?php foreach ($willow_bank_options as $bank) { ?>
                    <option value="<?php echo get_text($bank); ?>" <?php echo get_selected($willow_account_bank, $bank); ?>><?php echo get_text($bank); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="willow_profile_field">
                <label for="reg_mb_9">예금주</label>
                <input type="text" name="mb_9" value="<?php echo get_text($willow_account_holder ? $willow_account_holder : $member['mb_name']); ?>" id="reg_mb_9" data-account-holder>
                <p>* 예금통장의 예금주명을 정확하게 입력해주세요</p>
            </div>
            <div class="willow_profile_field">
                <label for="reg_mb_10">계좌번호</label>
                <input type="text" name="mb_10" value="<?php echo get_text($member['mb_10']); ?>" id="reg_mb_10" inputmode="numeric" data-account-number>
            </div>
            <button type="button" class="willow_profile_sub_button" data-account-check>계좌 인증</button>
            <p class="willow_account_check_message" data-account-message>* 계좌정보 변경 시 계좌 인증이 필요합니다.</p>
        </section>

        <section class="willow_profile_section">
            <h2>구독설정</h2>
            <div class="willow_profile_field">
                <label for="reg_mb_1">구독금액</label>
                <input type="text" name="mb_1" value="<?php echo get_text($member['mb_1'] ? $member['mb_1'] : '8,800'); ?>" id="reg_mb_1" inputmode="numeric">
                <p>* 고객이 구독설정할 수 있는 금액을 입력해주세요</p>
                <p>* 구독금액은 수수료 25% 차감 후 지급 정산 됩니다.</p>
            </div>
            <button type="button" class="willow_profile_sub_button">구독금액 변경</button>
        </section>
        <?php } ?>

        <div class="willow_profile_submit_bar">
            <button type="submit" id="btn_submit">수정하기</button>
        </div>
    </form>
</div>

<script>
document.querySelectorAll('[data-willow-safe-back]').forEach(function(link) {
    link.addEventListener('click', function(event) {
        event.preventDefault();
        if (document.referrer && document.referrer !== window.location.href && window.history.length > 1) {
            window.history.back();
            return;
        }
        window.location.href = link.href;
    });
});

function fregisterform_submit(f)
{
    var accountRequired = f.querySelector('[data-account-check]');
    if (accountRequired && typeof window.willowAccountNeedsVerification === 'function' && window.willowAccountNeedsVerification()) {
        alert('계좌 인증을 먼저 완료해주세요.');
        return false;
    }

    if (f.mb_nick && f.mb_nick.defaultValue != f.mb_nick.value) {
        var nickMsg = reg_mb_nick_check();
        if (nickMsg) {
            alert(nickMsg);
            f.mb_nick.focus();
            return false;
        }
    }

    if (typeof f.mb_img != "undefined" && f.mb_img.value) {
        if (!f.mb_img.value.toLowerCase().match(/\.(gif|jpe?g|png)$/i)) {
            alert("회원이미지가 이미지 파일이 아닙니다.");
            f.mb_img.focus();
            return false;
        }
    }

    var categoryField = document.getElementById('willow_mb_3');
    if (categoryField) {
        var selected = [];
        document.querySelectorAll('.willow_category_group input:checked').forEach(function(input) {
            selected.push(input.value);
        });
        categoryField.value = selected.join(',');
    }

    document.getElementById("btn_submit").disabled = "disabled";
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    var imageInput = document.getElementById('reg_mb_img');
    var preview = document.querySelector('.willow_profile_photo img');
    if (!imageInput || !preview) return;

    imageInput.addEventListener('change', function() {
        if (!this.files || !this.files[0]) return;
        preview.src = URL.createObjectURL(this.files[0]);
    });
});

document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('fregisterform');
    if (!form) return;

    var bank = form.querySelector('[data-account-bank]');
    var holder = form.querySelector('[data-account-holder]');
    var number = form.querySelector('[data-account-number]');
    var button = form.querySelector('[data-account-check]');
    var message = form.querySelector('[data-account-message]');
    var verified = form.querySelector('input[name="willow_account_verified"]');
    if (!bank || !holder || !number || !button || !message || !verified) return;

    var initial = {
        bank: bank.value,
        holder: holder.value,
        number: number.value.replace(/[^0-9]/g, '')
    };

    function accountChanged() {
        return bank.value !== initial.bank || holder.value !== initial.holder || number.value.replace(/[^0-9]/g, '') !== initial.number;
    }

    window.willowAccountNeedsVerification = function() {
        return accountChanged() && verified.value !== '1';
    };

    function setMessage(text, state) {
        message.textContent = text;
        message.classList.remove('is_success', 'is_error');
        if (state) message.classList.add(state);
    }

    function markDirty() {
        verified.value = accountChanged() ? '0' : '1';
        setMessage(accountChanged() ? '* 계좌정보 변경 시 계좌 인증이 필요합니다.' : '* 기존 인증 계좌정보입니다.', '');
    }

    [bank, holder, number].forEach(function(input) {
        input.addEventListener('input', markDirty);
        input.addEventListener('change', markDirty);
    });

    button.addEventListener('click', function() {
        var params = new FormData();
        params.append('token', form.querySelector('input[name="token"]').value);
        params.append('mb_8', bank.value);
        params.append('mb_9', holder.value);
        params.append('mb_10', number.value);

        button.disabled = true;
        setMessage('계좌정보를 확인하고 있습니다.', '');

        fetch('<?php echo G5_URL; ?>/willow/account_check.php', {
            method: 'POST',
            body: params,
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).then(function(response) {
            return response.json();
        }).then(function(data) {
            if (data && data.success) {
                verified.value = '1';
                initial.bank = bank.value;
                initial.holder = holder.value;
                initial.number = number.value.replace(/[^0-9]/g, '');
                setMessage(data.message || '계좌 인증이 완료되었습니다.', 'is_success');
            } else {
                verified.value = '0';
                setMessage((data && data.message) ? data.message : '계좌 인증에 실패했습니다.', 'is_error');
            }
        }).catch(function() {
            verified.value = '0';
            setMessage('계좌 인증 중 오류가 발생했습니다.', 'is_error');
        }).finally(function() {
            button.disabled = false;
        });
    });

    markDirty();
});
</script>
