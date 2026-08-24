<?php
if (!defined('_GNUBOARD_')) exit;

if (!function_exists('willow_report_categories')) {
    include_once(G5_PATH.'/willow/content.lib.php');
}

$willow_report_categories = willow_report_categories();
?>
<div class="willow_report_modal" data-willow-report-modal hidden>
    <div class="willow_report_dim" data-willow-report-close></div>
    <section class="willow_report_panel" role="dialog" aria-modal="true" aria-labelledby="willow_report_title">
        <div class="willow_report_head">
            <h2 id="willow_report_title">신고하기</h2>
            <button type="button" class="willow_report_close" data-willow-report-close aria-label="닫기"></button>
        </div>
        <form class="willow_report_form" data-willow-report-form>
            <input type="hidden" name="target_type" value="">
            <input type="hidden" name="target_id" value="">
            <label>
                <span>신고 카테고리</span>
                <select name="category" required>
                    <option value="">카테고리를 선택해주세요</option>
                    <?php foreach ($willow_report_categories as $key => $label) { ?>
                    <option value="<?php echo $key; ?>"><?php echo get_text($label); ?></option>
                    <?php } ?>
                </select>
            </label>
            <label>
                <span>신고 내용</span>
                <textarea name="content" rows="5" maxlength="1000" placeholder="신고 사유를 입력해주세요" required></textarea>
            </label>
            <p class="willow_report_message" data-willow-report-message aria-live="polite"></p>
            <div class="willow_report_actions">
                <button type="button" data-willow-report-close>취소</button>
                <button type="submit">신고 접수</button>
            </div>
        </form>
    </section>
</div>
<script>
(function() {
    if (window.willowReportModalBound) return;
    window.willowReportModalBound = true;

    var modal = document.querySelector('[data-willow-report-modal]');
    if (!modal) return;

    var form = modal.querySelector('[data-willow-report-form]');
    var message = modal.querySelector('[data-willow-report-message]');
    var category = form ? form.querySelector('[name="category"]') : null;
    var content = form ? form.querySelector('[name="content"]') : null;
    var submit = form ? form.querySelector('[type="submit"]') : null;
    var targetType = form ? form.querySelector('[name="target_type"]') : null;
    var targetId = form ? form.querySelector('[name="target_id"]') : null;
    var lastOpener = null;

    function setMessage(text, isError) {
        if (!message) return;
        message.textContent = text || '';
        message.classList.toggle('is_error', !!isError);
    }

    function closeModal() {
        modal.hidden = true;
        document.body.classList.remove('willow_report_modal_open');
        if (form) form.reset();
        setMessage('');
        if (lastOpener) {
            var wrap = lastOpener.closest('.willow_more');
            if (wrap) wrap.classList.remove('is_open');
        }
    }

    function openModal(button) {
        lastOpener = button;
        if (targetType) targetType.value = button.getAttribute('data-target-type') || 'board';
        if (targetId) targetId.value = button.getAttribute('data-target-id') || '';
        modal.hidden = false;
        document.body.classList.add('willow_report_modal_open');
        setMessage('');
        window.setTimeout(function() {
            if (category) category.focus();
        }, 30);
    }

    document.addEventListener('click', function(event) {
        var opener = event.target.closest('[data-willow-report-open], .willow_report_button');
        if (opener) {
            event.preventDefault();
            openModal(opener);
            return;
        }

        if (event.target.closest('[data-willow-report-close]')) {
            event.preventDefault();
            closeModal();
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && !modal.hidden) closeModal();
    });

    if (!form) return;

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        setMessage('');

        if (!form.category.value) {
            setMessage('신고 카테고리를 선택해주세요.', true);
            category.focus();
            return;
        }

        if (!form.content.value.trim()) {
            setMessage('신고 내용을 입력해주세요.', true);
            content.focus();
            return;
        }

        if (submit) submit.disabled = true;
        fetch('<?php echo G5_URL; ?>/willow/report_update.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new FormData(form),
            credentials: 'same-origin'
        }).then(function(response) {
            return response.json();
        }).then(function(data) {
            if (!data.success) {
                setMessage(data.message || '신고 접수에 실패했습니다.', true);
                return;
            }
            setMessage(data.message || '신고 내용이 접수되었습니다.');
            window.setTimeout(closeModal, 700);
        }).catch(function() {
            setMessage('신고 접수 중 오류가 발생했습니다.', true);
        }).finally(function() {
            if (submit) submit.disabled = false;
        });
    });
})();
</script>
