<?php
if (!defined('_GNUBOARD_')) exit;

add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/willow_register_profile.css?ver='.G5_CSS_VER.'">', 20);
include_once(G5_PATH.'/willow/content.lib.php');

$willow_is_author = ((int) $member['mb_level'] >= 3) || (!empty($member['mb_2']) && $member['mb_2'] === 'author');
$willow_profile_img = G5_IMG_URL.'/no_profile.gif';
if ($w == 'u' && file_exists($mb_img_path)) {
    $willow_profile_img = $mb_img_url;
} else if (!empty($member['mb_6'])) {
    $willow_profile_img = $member['mb_6'];
}

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
?>

<script>document.body.classList.add('willow_profile_edit_body');</script>

<div class="willow_profile_edit">
    <header class="willow_detail_header">
        <a class="willow_back" href="javascript:history.back();" aria-label="뒤로가기"></a>
        <h1><?php echo get_head_title($g5['title']); ?></h1>
    </header>

    <form name="fregisterform" id="fregisterform" action="<?php echo $register_action_url ?>" onsubmit="return fregisterform_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="w" value="<?php echo $w ?>">
        <input type="hidden" name="url" value="<?php echo $urlencode ?>">
        <input type="hidden" name="agree" value="<?php echo $agree ?>">
        <input type="hidden" name="agree2" value="<?php echo $agree2 ?>">
        <input type="hidden" name="cert_type" value="<?php echo $member['mb_certify']; ?>">
        <input type="hidden" name="cert_no" value="">
        <input type="hidden" name="willow_profile_edit" value="1">
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
                <label for="reg_mb_8">예금주</label>
                <input type="text" name="mb_8" value="<?php echo get_text($member['mb_8'] ? $member['mb_8'] : $member['mb_name']); ?>" id="reg_mb_8">
                <p>* 예금통장의 예금주명을 정확하게 입력해주세요</p>
            </div>
            <div class="willow_profile_field">
                <label for="reg_mb_9">은행명</label>
                <select name="mb_9" id="reg_mb_9">
                    <?php foreach ($willow_bank_options as $bank) { ?>
                    <option value="<?php echo get_text($bank); ?>" <?php echo get_selected($member['mb_9'], $bank); ?>><?php echo get_text($bank); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="willow_profile_field">
                <label for="reg_mb_10">계좌번호</label>
                <input type="text" name="mb_10" value="<?php echo get_text($member['mb_10']); ?>" id="reg_mb_10" inputmode="numeric">
            </div>
            <button type="button" class="willow_profile_sub_button">계좌정보 변경</button>
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
function fregisterform_submit(f)
{
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
</script>
