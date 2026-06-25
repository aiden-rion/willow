<?php
include_once('./_common.php');
include_once('./content.lib.php');

if (!$is_member) {
    goto_url(G5_BBS_URL.'/login.php?url='.urlencode(G5_URL.'/willow/author_register.php'));
}

willow_author_request_install();

$is_author = ((int) $member['mb_level'] >= 3) || (!empty($member['mb_2']) && $member['mb_2'] === 'author');
$latest_request = willow_author_latest_request($member['mb_id']);
$step = isset($_GET['step']) && $_GET['step'] === 'form' ? 'form' : 'intro';
$category_rows = willow_get_categories(true);
$categories = array();
foreach ($category_rows as $category_row) {
    if ($category_row['keyword'] === '') {
        continue;
    }
    $categories[] = ltrim($category_row['label'], '#');
}
if (!$categories) {
    $categories = array('정착꿀팁', '정착상식', '정신건강', '범죄사건', '종교문화', '이야기', '사람', '민주주의', '인권');
}
$banks = array('국민은행', '신한은행', '하나은행', '우리은행', '농협은행', '카카오뱅크', '토스뱅크', '기업은행');
$g5['title'] = $step === 'form' ? '작가등록' : '작가등록 안내';

include_once(G5_PATH.'/head.sub.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/willow_content.css?ver='.G5_CSS_VER.'">', 10);
?>

<?php if ($step !== 'form') { ?>
<main class="willow_content_app willow_author_apply_intro">
    <section class="willow_author_apply_hero">
        <h1>윌로우 작가<br>인증하기</h1>
        <p>탈북이주민 작가와 일반작가 중<br>유형을 선택해 등록할 수 있습니다.</p>
        <img src="<?php echo G5_IMG_URL; ?>/author_register.png" alt="윌로우에서 작가로 활동하세요">
    </section>
    <nav class="willow_author_apply_bottom is_split">
        <a href="<?php echo G5_URL; ?>">건너뛰기</a>
        <?php if ($is_author) { ?>
        <a href="<?php echo G5_URL; ?>/willow/my_posts.php" data-author-alert data-alert-title="작가등록 안내" data-alert-message="이미 작가회원으로 등록되어 있습니다." data-alert-action="내 글 보기">내 글 보기</a>
        <?php } else if (!empty($latest_request['war_status']) && $latest_request['war_status'] === 'pending') { ?>
        <a href="<?php echo G5_URL; ?>/willow/author_register.php?step=form" data-author-alert data-alert-title="작가등록 안내" data-alert-message="작가등록 요청을 검토 중입니다." data-alert-action="요청 확인">요청 확인</a>
        <?php } else if (!empty($latest_request['war_status']) && $latest_request['war_status'] === 'rejected') { ?>
        <a href="<?php echo G5_URL; ?>/willow/author_register.php?step=form" data-author-alert data-alert-title="작가등록 안내" data-alert-message="작가등록 요청이 반려되었습니다. 내용을 보완해 다시 신청해주세요." data-alert-action="다시 신청하기">등록하기</a>
        <?php } else { ?>
        <a href="<?php echo G5_URL; ?>/willow/author_register.php?step=form" data-author-alert data-alert-title="작가등록 안내" data-alert-message="작가로 등록하고 당신만의 글을 세상과 나눠보세요." data-alert-action="등록하기">등록하기</a>
        <?php } ?>
    </nav>
    <div class="willow_author_alert" data-author-alert-layer hidden>
        <div class="willow_author_alert_dim" data-author-alert-close></div>
        <section class="willow_author_alert_panel" role="dialog" aria-modal="true" aria-labelledby="willow_author_alert_title">
            <h2 id="willow_author_alert_title">작가등록 안내</h2>
            <p data-author-alert-message></p>
            <div class="willow_author_alert_actions">
                <button type="button" data-author-alert-close>닫기</button>
                <a href="#" data-author-alert-confirm>확인</a>
            </div>
        </section>
    </div>
    <script>
    (function() {
        var layer = document.querySelector('[data-author-alert-layer]');
        if (!layer) return;
        var title = layer.querySelector('#willow_author_alert_title');
        var message = layer.querySelector('[data-author-alert-message]');
        var confirm = layer.querySelector('[data-author-alert-confirm]');

        function closeAlert() {
            layer.hidden = true;
            document.documentElement.classList.remove('willow_author_alert_open');
        }

        document.querySelectorAll('[data-author-alert]').forEach(function(trigger) {
            trigger.addEventListener('click', function(event) {
                event.preventDefault();
                if (title) title.textContent = trigger.getAttribute('data-alert-title') || '작가등록 안내';
                if (message) message.textContent = trigger.getAttribute('data-alert-message') || '';
                if (confirm) {
                    confirm.href = trigger.getAttribute('href') || '#';
                    confirm.textContent = trigger.getAttribute('data-alert-action') || '확인';
                }
                layer.hidden = false;
                document.documentElement.classList.add('willow_author_alert_open');
            });
        });

        layer.querySelectorAll('[data-author-alert-close]').forEach(function(button) {
            button.addEventListener('click', closeAlert);
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && !layer.hidden) closeAlert();
        });
    })();
    </script>
</main>
<?php } else { ?>
<main class="willow_content_app willow_author_apply_form">
    <header class="willow_author_page_header">
        <a href="<?php echo G5_URL; ?>/willow/author_register.php" aria-label="뒤로가기"><img src="<?php echo G5_IMG_URL; ?>/ico_back.png" alt=""></a>
        <h1>작가등록</h1>
    </header>

    <?php if ($is_author) { ?>
    <section class="willow_author_apply_state">
        <h2>이미 작가회원입니다.</h2>
        <p>내가 작성한 글과 작가 정보를 확인할 수 있습니다.</p>
        <a href="<?php echo G5_URL; ?>/willow/my_posts.php">내 글 보기</a>
    </section>
    <?php } else if (!empty($latest_request['war_status']) && $latest_request['war_status'] === 'pending') { ?>
    <section class="willow_author_apply_state">
        <h2>작가등록 요청 검토중</h2>
        <p>관리자 승인 후 작가회원으로 전환됩니다.</p>
        <span>요청일: <?php echo get_text(substr($latest_request['war_datetime'], 0, 16)); ?></span>
    </section>
    <?php } else { ?>
    <form class="willow_author_apply_fields" method="post" action="<?php echo G5_URL; ?>/willow/author_register_update.php" enctype="multipart/form-data">
        <input type="hidden" name="token" value="<?php echo get_token(); ?>">

        <section>
            <h2>프로필 등록</h2>
            <label class="willow_author_photo_upload">
                <input type="file" name="profile_image" accept="image/jpeg,image/png,image/gif,image/webp">
                <span aria-hidden="true"></span>
                <em>사진등록</em>
            </label>
            <label class="willow_author_field"><span>작가소개</span>
                <textarea name="intro" rows="2" placeholder="소개글을 입력 해 주세요" required><?php echo !empty($latest_request['war_intro']) ? get_text($latest_request['war_intro'], 0) : get_text($member['mb_profile'], 0); ?></textarea>
            </label>
            <label class="willow_author_field willow_author_select"><span>작가 유형</span>
                <select name="is_escapee" required data-placeholder="선택해주세요">
                    <option value="">선택해주세요</option>
                    <option value="yes">탈북이주민 작가</option>
                    <option value="no">일반작가</option>
                </select>
            </label>
            <label class="willow_author_field willow_author_file_field"><span>탈북이주민 확인증</span>
                <input type="file" name="cert_file" accept="image/jpeg,image/png,image/gif,image/webp,application/pdf" data-escapee-cert>
                <em data-file-label>파일을 첨부해주세요</em>
            </label>
            <p class="willow_author_apply_note">* 탈북이주민 작가로 신청하는 경우 확인증 첨부가 필요합니다.</p>
        </section>

        <section>
            <h2>주요 카테고리</h2>
            <p class="willow_author_apply_note">* 주로 작성하는 글 카테고리를 선택하세요.</p>
            <div class="willow_author_category_chips">
                <?php foreach ($categories as $category) { ?>
                <label><input type="checkbox" name="categories[]" value="<?php echo $category; ?>"><span>#<?php echo $category; ?></span></label>
                <?php } ?>
            </div>
        </section>

        <section>
            <h2>정산정보등록</h2>
            <p class="willow_author_apply_note">* 작가 수익 정산을 위해 활용됩니다.</p>
            <label class="willow_author_field"><span>예금주</span>
                <input type="text" name="account_holder" placeholder="예금주명을 입력해주세요" required>
            </label>
            <p class="willow_author_apply_note">* 예금통장의 예금주명을 정확하게 입력해주세요</p>
            <label class="willow_author_field willow_author_select"><span>은행선택</span>
                <select name="bank_name" required data-placeholder="은행을 선택해주세요">
                    <option value="">은행을 선택해주세요</option>
                    <?php foreach ($banks as $bank) { ?>
                    <option value="<?php echo $bank; ?>"><?php echo $bank; ?></option>
                    <?php } ?>
                </select>
            </label>
            <label class="willow_author_field"><span>계좌번호</span>
                <input type="text" name="account_number" inputmode="numeric" placeholder="숫자만 입력하세요" required>
            </label>
        </section>

        <section>
            <h2>구독설정</h2>
            <label class="willow_author_field"><span>구독금액</span>
                <input type="number" name="subscribe_price" min="0" step="100" value="8800" required>
            </label>
            <p class="willow_author_apply_note">* 설명내용</p>
            <button type="button" class="willow_author_apply_ghost">구독금액 변경</button>
        </section>

        <section>
            <h2>정보 수집동의</h2>
            <label class="willow_author_agree willow_author_agree_all"><input type="checkbox" name="agree_terms" value="1" required data-agree-all><span>약관 전체동의</span></label>
            <div class="willow_author_agree_sub">
                <label class="willow_author_agree"><input type="checkbox" name="agree_privacy" value="1" required data-agree-item><span><em>[필수]</em> 선택적 정보 제공 동의</span><i aria-hidden="true"></i></label>
                <label class="willow_author_agree"><input type="checkbox" name="agree_service" value="1" required data-agree-item><span><em>[필수]</em> 선택적 동의 정보 수집·이용 동의</span><i aria-hidden="true"></i></label>
            </div>
        </section>

        <nav class="willow_author_apply_bottom is_split">
            <a href="<?php echo G5_URL; ?>">취소하기</a>
            <button type="submit">신청하기</button>
        </nav>
    </form>
    <?php } ?>
</main>
<?php if ($step === 'form') { ?>
<script>
(function() {
    document.querySelectorAll('.willow_author_file_field input[type="file"], .willow_author_photo_upload input[type="file"]').forEach(function(input) {
        input.addEventListener('change', function() {
            var filename = input.files && input.files[0] ? input.files[0].name : '';
            var field = input.closest('.willow_author_file_field');
            if (field) {
                var label = field.querySelector('[data-file-label]');
                if (label) label.textContent = filename || '파일을 첨부해주세요';
            }
            var photo = input.closest('.willow_author_photo_upload');
            if (photo && filename) {
                photo.classList.add('has_file');
                var text = photo.querySelector('em');
                if (text) text.textContent = '사진변경';
            }
        });
    });

    var agreeAll = document.querySelector('[data-agree-all]');
    var agreeItems = Array.prototype.slice.call(document.querySelectorAll('[data-agree-item]'));
    var authorType = document.querySelector('select[name="is_escapee"]');
    var certInput = document.querySelector('[data-escapee-cert]');
    var certField = certInput ? certInput.closest('.willow_author_file_field') : null;

    function syncCertField() {
        if (!authorType || !certInput || !certField) return;
        var isEscapee = authorType.value === 'yes';
        certInput.required = isEscapee;
        certField.classList.toggle('is_optional', !isEscapee);
        var label = certField.querySelector('[data-file-label]');
        if (label && !certInput.files.length) {
            label.textContent = isEscapee ? '파일을 첨부해주세요' : '해당 시 첨부해주세요';
        }
    }

    if (authorType) {
        authorType.addEventListener('change', syncCertField);
        syncCertField();
    }

    if (agreeAll && agreeItems.length) {
        agreeAll.addEventListener('change', function() {
            agreeItems.forEach(function(item) {
                item.checked = agreeAll.checked;
            });
        });
        agreeItems.forEach(function(item) {
            item.addEventListener('change', function() {
                agreeAll.checked = agreeItems.every(function(checkbox) {
                    return checkbox.checked;
                });
            });
        });
    }
})();
</script>
<?php } ?>
<?php } ?>

<?php
include_once(G5_PATH.'/tail.sub.php');
