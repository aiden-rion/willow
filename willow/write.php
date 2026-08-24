<?php
include_once('./_common.php');
include_once('./topic.lib.php');

willow_topic_install();

$tables = willow_topic_tables();
$topic = willow_get_topic();
$wt_id = isset($_GET['wt_id']) ? (int) $_GET['wt_id'] : (int) $topic['wt_id'];
if ($wt_id && $wt_id !== (int) $topic['wt_id']) {
    $row = sql_fetch(" select * from `{$tables['topic']}` where wt_id = '{$wt_id}' ");
    if (!empty($row['wt_id']) && willow_topic_is_visible($row)) {
        $topic = $row;
    }
}

if (empty($topic['wt_id']) || !willow_topic_is_visible($topic)) {
    alert('등록된 오늘의 주제가 없습니다.', G5_URL);
}

$g5['title'] = '오늘의 주제 글쓰기';
include_once(G5_PATH.'/head.sub.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/willow_content.css?ver='.G5_CSS_VER.'">', 10);

$willow_topic_title = get_text($topic['wt_subject']);
$willow_topic_description = !empty($topic['wt_description']) ? nl2br(get_text($topic['wt_description'])) : '';
$willow_tags = array('정착꿀팁', '정착상식', '정신건강', '범죄사건', '종교문화', '이야기', '사람', '민주주의', '인권');
$draft_id = isset($_GET['wd_id']) ? (int) $_GET['wd_id'] : 0;
$draft = array();
$draft_images = array();
$draft_tags = array();
if ($draft_id && $is_member) {
    $draft = sql_fetch(" select * from `{$tables['draft']}` where wd_id = '{$draft_id}' and mb_id = '".sql_escape_string($member['mb_id'])."' ", false);
    if (!empty($draft['wd_id'])) {
        $draft_images = !empty($draft['wd_images']) ? array_values(array_filter(explode('|', $draft['wd_images']))) : array();
        $draft_tags = !empty($draft['wd_tags']) ? array_map('trim', explode(',', $draft['wd_tags'])) : array();
        if ((int) $draft['wt_id'] !== (int) $topic['wt_id']) {
            $draft_topic = sql_fetch(" select * from `{$tables['topic']}` where wt_id = '".(int) $draft['wt_id']."' ", false);
            if (!empty($draft_topic['wt_id']) && willow_topic_is_visible($draft_topic)) {
                $topic = $draft_topic;
                $wt_id = (int) $topic['wt_id'];
                $willow_topic_title = get_text($topic['wt_subject']);
                $willow_topic_description = !empty($topic['wt_description']) ? nl2br(get_text($topic['wt_description'])) : '';
            }
        }
    } else {
        $draft_id = 0;
    }
}
$draft_topic_mode = !empty($draft['wd_topic_mode']) && $draft['wd_topic_mode'] === 'free' ? 'free' : 'today';
$draft_subject = !empty($draft['wd_subject']) ? get_text($draft['wd_subject']) : '';
$draft_content = !empty($draft['wd_content']) ? get_text($draft['wd_content'], 0) : '';
$draft_access = !empty($draft['wd_access']) && $draft['wd_access'] === 'subscriber' ? 'subscriber' : 'public';
?>

<main class="willow_content_app willow_write_app <?php echo $draft_topic_mode === 'free' ? 'is_free_topic' : ''; ?>">
    <header class="willow_detail_header">
        <a class="willow_back" href="<?php echo G5_URL; ?>/willow/today.php" aria-label="뒤로가기"></a>
        <h1>
            <button type="button" class="willow_topic_toggle willow_header_topic_toggle" aria-label="주제 유형 선택">
                <span data-topic-label>오늘의 주제</span>
                <i class="fa fa-angle-down" aria-hidden="true"></i>
            </button>
            <span class="willow_topic_hint_bubble">눌러서 주제 변경</span>
        </h1>
    </header>

    <form class="willow_topic_write_form" action="<?php echo G5_URL; ?>/willow/write_update.php" method="post" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="MAX_FILE_SIZE" value="5242880">
        <input type="hidden" name="wt_id" value="<?php echo (int) $topic['wt_id']; ?>">
        <input type="hidden" id="wd_id" name="wd_id" value="<?php echo (int) $draft_id; ?>">
        <input type="hidden" id="wp_tags" name="wp_tags" value="<?php echo get_text(!empty($draft['wd_tags']) ? $draft['wd_tags'] : ''); ?>">
        <?php foreach ($draft_images as $draft_image) { ?>
        <input type="hidden" name="existing_images[]" value="<?php echo get_text($draft_image); ?>" data-existing-image="<?php echo get_text($draft_image); ?>">
        <?php } ?>

        <section class="willow_write_topic" data-topic-card <?php echo $draft_topic_mode === 'free' ? 'hidden' : ''; ?>>
            <span class="willow_write_topic_label">오늘의 주제</span>
            <h2 data-topic-title><?php echo $willow_topic_title; ?></h2>
        </section>

        <input type="hidden" id="wp_subject" name="wp_subject" value="<?php echo $draft_subject ? $draft_subject : $willow_topic_title; ?>">

        <div class="willow_write_field">
            <label for="wp_content" class="sound_only">내용</label>
            <textarea id="wp_content" name="wp_content" placeholder="오늘의 주제에 대한 생각을&#10;자유롭게 작성해주세요" required><?php echo $draft_content; ?></textarea>
        </div>

        <div class="willow_write_attach" aria-label="이미지 첨부">
            <?php for ($i = 0; $i < 4; $i++) { ?>
            <?php $draft_image = isset($draft_images[$i]) ? $draft_images[$i] : ''; ?>
            <div class="willow_attach_item <?php echo $draft_image ? 'has_preview' : ''; ?>">
                <label class="willow_attach_tile" for="wp_image_<?php echo $i; ?>">
                    <i class="fa fa-picture-o" aria-hidden="true"></i>
                    <img class="willow_attach_preview" src="<?php echo get_text($draft_image); ?>" alt="">
                    <button type="button" class="willow_attach_remove" aria-label="첨부 이미지 삭제"><i class="fa fa-times" aria-hidden="true"></i></button>
                </label>
                <input id="wp_image_<?php echo $i; ?>" class="willow_attach_input" type="file" name="wp_images[]" accept="image/*">
            </div>
            <?php } ?>
        </div>

        <select id="wp_access" name="wp_access" class="willow_access_select" aria-hidden="true" tabindex="-1">
            <option value="public" <?php echo $draft_access === 'public' ? 'selected' : ''; ?>>전체공개</option>
            <option value="subscriber" <?php echo $draft_access === 'subscriber' ? 'selected' : ''; ?>>구독자 전용</option>
        </select>

        <div class="willow_write_actions <?php echo $draft_topic_mode === 'free' ? 'has_tag_bar' : ''; ?>">
            <button type="button" class="willow_write_close_button" data-write-close>
                <i class="fa fa-times" aria-hidden="true"></i>
                <span>닫기</span>
            </button>
            <div class="willow_write_toolset" aria-label="작성 도구">
                <button type="button" class="willow_write_tool willow_write_align_toggle is_active" data-align-cycle data-align-state="left" aria-label="텍스트 정렬 변경">
                    <i class="fa fa-align-left" aria-hidden="true"></i>
                </button>
                <button type="button" class="willow_write_tool" data-trigger-file aria-label="사진첨부">
                    <i class="fa fa-picture-o" aria-hidden="true"></i>
                </button>
                <button type="button" class="willow_write_tool" data-copy-content aria-label="내용 복사">
                    <i class="fa fa-clone" aria-hidden="true"></i>
                </button>
                <button type="button" class="willow_write_tool willow_write_access_tool <?php echo $draft_access === 'subscriber' ? 'is_active' : ''; ?>" data-toggle-access aria-pressed="<?php echo $draft_access === 'subscriber' ? 'true' : 'false'; ?>" aria-label="구독자 전용">
                    <i class="fa fa-lock" aria-hidden="true"></i>
                </button>
            </div>
            <button type="submit" class="willow_submit_button">
                <i class="fa fa-check" aria-hidden="true"></i>
                <span>완료</span>
            </button>
            <div class="willow_write_tag_bar" aria-label="감정태그선택" <?php echo $draft_topic_mode === 'today' ? 'hidden' : ''; ?>>
                <div class="willow_tag_chips" role="group">
                    <?php foreach ($willow_tags as $tag) { ?>
                    <button type="button" class="willow_tag_chip <?php echo in_array($tag, $draft_tags, true) ? 'is_selected' : ''; ?>" data-tag="<?php echo $tag; ?>">#<?php echo $tag; ?></button>
                    <?php } ?>
                </div>
            </div>
        </div>
    </form>
    <div class="willow_write_toast" data-write-toast aria-live="polite" aria-atomic="true"></div>
    <div class="willow_write_confirm" data-write-confirm hidden>
        <div class="willow_write_confirm_dim" data-confirm-cancel></div>
        <section class="willow_write_confirm_panel" role="dialog" aria-modal="true" aria-labelledby="willow_write_confirm_title">
            <h2 id="willow_write_confirm_title">글작성을 취소하시겠습니까?</h2>
            <p>작성 중인 내용은 저장되지 않을 수 있습니다.</p>
            <div class="willow_write_confirm_actions">
                <button type="button" class="willow_write_confirm_cancel" data-confirm-cancel>계속 작성</button>
                <button type="button" class="willow_write_confirm_ok" data-confirm-ok>나가기</button>
            </div>
        </section>
    </div>
</main>

<script>
(function() {
    document.body.classList.add('willow_write_body');

    var topicTitle = <?php echo json_encode($willow_topic_title); ?>;
    var topicDescription = <?php echo json_encode($willow_topic_description); ?>;
    var maxImageSize = 5 * 1024 * 1024;
    var topicMode = <?php echo json_encode($draft_topic_mode); ?>;
    var form = document.querySelector('.willow_topic_write_form');
    var content = document.getElementById('wp_content');
    var subject = document.getElementById('wp_subject');
    var draftId = document.getElementById('wd_id');
    var tags = document.getElementById('wp_tags');
    var app = document.querySelector('.willow_write_app');
    var actions = document.querySelector('.willow_write_actions');
    var label = document.querySelector('[data-topic-label]');
    var topicCardLabel = document.querySelector('.willow_write_topic_label');
    var topicCard = document.querySelector('[data-topic-card]');
    var title = document.querySelector('[data-topic-title]');
    var description = document.querySelector('[data-topic-description]');
    var toggle = document.querySelector('.willow_topic_toggle');
    var tagBar = document.querySelector('.willow_write_tag_bar');
    var saveButton = document.querySelector('.willow_save_button');
    var accessSelect = document.getElementById('wp_access');
    var toast = document.querySelector('[data-write-toast]');
    var toastTimer = null;
    var confirmModal = document.querySelector('[data-write-confirm]');
    var confirmOk = document.querySelector('[data-confirm-ok]');
    var contentLimit = 500;
    var isContentOverLimit = false;

    function showToast(message) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add('is_visible');
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(function() {
            toast.classList.remove('is_visible');
        }, 1800);
    }

    function syncKeyboardOffset() {
        if (!window.visualViewport) return;
        var offset = Math.max(0, window.innerHeight - window.visualViewport.height - window.visualViewport.offsetTop);
        document.documentElement.style.setProperty('--willow-keyboard-offset', offset + 'px');
    }

    function closeWriteConfirm() {
        if (!confirmModal) return;
        confirmModal.hidden = true;
        document.body.classList.remove('willow_write_confirm_open');
    }

    function openWriteConfirm() {
        if (!confirmModal) return;
        confirmModal.hidden = false;
        document.body.classList.add('willow_write_confirm_open');
    }

    function leaveWritePage() {
        window.location.href = '<?php echo G5_URL; ?>/willow/today.php?wt_id=<?php echo (int) $topic['wt_id']; ?>';
    }

    function syncTopic() {
        var isFree = topicMode === 'free';
        app.classList.toggle('is_free_topic', isFree);
        label.textContent = isFree ? '자유주제' : '오늘의 주제';
        if (topicCard) topicCard.hidden = isFree;
        if (topicCardLabel) topicCardLabel.hidden = isFree;
        if (title) title.textContent = isFree ? '' : topicTitle;
        if (description) description.innerHTML = isFree ? '' : topicDescription;
        if (subject) subject.value = isFree ? '자유주제' : topicTitle;
        content.placeholder = isFree ? '자유롭게 글을 작성해주세요' : '오늘의 주제에 대한 생각을\n자유롭게 작성해주세요';
        if (actions) {
            actions.classList.toggle('has_tag_bar', isFree);
        }
        if (tagBar) {
            tagBar.hidden = !isFree;
        }
        if (!isFree) {
            document.querySelectorAll('.willow_tag_chip.is_selected').forEach(function(chip) {
                chip.classList.remove('is_selected');
            });
            syncTags();
        }
    }

    function syncContentLimit() {
        var length = (content.value || '').length;
        var overLimit = length > contentLimit;
        content.classList.toggle('is_over_limit', overLimit);
        if (overLimit && !isContentOverLimit) {
            showToast('500자 이내로 작성해주세요.');
        }
        isContentOverLimit = overLimit;
    }

    function syncTags() {
        var selected = [];
        document.querySelectorAll('.willow_tag_chip.is_selected').forEach(function(chip) {
            selected.push(chip.getAttribute('data-tag'));
        });
        tags.value = selected.join(',');
    }

    function syncAttachVisibility() {
        var items = Array.prototype.slice.call(document.querySelectorAll('.willow_attach_item'));
        var attachWrap = document.querySelector('.willow_write_attach');
        var hasImages = false;

        items.forEach(function(item) {
            if (item.classList.contains('has_preview')) {
                hasImages = true;
            }
        });

        if (attachWrap) {
            attachWrap.classList.toggle('has_images', hasImages);
        }
    }

    toggle.addEventListener('click', function() {
        topicMode = topicMode === 'today' ? 'free' : 'today';
        syncTopic();
    });

    content.addEventListener('input', syncContentLimit);

    document.querySelectorAll('.willow_tag_chip').forEach(function(chip) {
        chip.addEventListener('click', function() {
            chip.classList.toggle('is_selected');
            syncTags();
        });
    });

    var alignButton = document.querySelector('[data-align-cycle]');
    if (alignButton) {
        alignButton.addEventListener('click', function() {
            var states = ['left', 'center', 'right'];
            var icons = {
                left: 'fa-align-left',
                center: 'fa-align-center',
                right: 'fa-align-right'
            };
            var current = alignButton.getAttribute('data-align-state') || 'left';
            var next = states[(states.indexOf(current) + 1) % states.length] || 'left';
            var icon = alignButton.querySelector('i');
            alignButton.setAttribute('data-align-state', next);
            alignButton.setAttribute('aria-label', next === 'left' ? '왼쪽 정렬' : (next === 'center' ? '가운데 정렬' : '오른쪽 정렬'));
            if (icon) {
                icon.className = 'fa ' + icons[next];
            }
            content.style.textAlign = next;
            content.focus();
        });
    }

    var closeButton = document.querySelector('[data-write-close]');
    if (closeButton) {
        closeButton.addEventListener('click', function() {
            openWriteConfirm();
        });
    }

    if (confirmModal) {
        confirmModal.addEventListener('click', function(event) {
            if (event.target.closest('[data-confirm-cancel]')) {
                closeWriteConfirm();
            }
        });
    }

    if (confirmOk) {
        confirmOk.addEventListener('click', leaveWritePage);
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && confirmModal && !confirmModal.hidden) {
            closeWriteConfirm();
        }
    });

    var fileButton = document.querySelector('[data-trigger-file]');
    if (fileButton) {
        fileButton.addEventListener('click', function() {
            var firstInput = Array.prototype.slice.call(document.querySelectorAll('.willow_attach_input')).find(function(input) {
                var item = input.closest('.willow_attach_item');
                return item && !item.classList.contains('has_preview');
            }) || document.querySelector('.willow_attach_input');
            if (firstInput) firstInput.click();
        });
    }

    var copyButton = document.querySelector('[data-copy-content]');
    if (copyButton) {
        copyButton.addEventListener('click', function() {
            var text = content.value || '';
            if (!text.trim()) {
                content.focus();
                showToast('복사할 내용이 없습니다.');
                return;
            }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    showToast('내용이 복사되었습니다.');
                }).catch(function() {
                    content.select();
                    document.execCommand('copy');
                    showToast('내용이 복사되었습니다.');
                });
            } else {
                content.select();
                document.execCommand('copy');
                showToast('내용이 복사되었습니다.');
            }
            content.focus();
        });
    }

    var accessButton = document.querySelector('[data-toggle-access]');
    if (accessButton && accessSelect) {
        accessButton.addEventListener('click', function() {
            var isSubscriber = accessSelect.value !== 'subscriber';
            accessSelect.value = isSubscriber ? 'subscriber' : 'public';
            accessButton.classList.toggle('is_active', isSubscriber);
            accessButton.setAttribute('aria-pressed', isSubscriber ? 'true' : 'false');
            showToast(isSubscriber ? '구독자 전용 글로 전환되었습니다.' : '전체공개 글로 전환되었습니다.');
        });
    }

    if (form) {
        form.addEventListener('submit', function(event) {
            if ((content.value || '').length > contentLimit) {
                event.preventDefault();
                content.classList.add('is_over_limit');
                showToast('500자 이내로 작성해주세요.');
                content.focus();
            }
        });
    }

    document.querySelectorAll('.willow_attach_input').forEach(function(input) {
        input.addEventListener('change', function() {
            var item = input.closest('.willow_attach_item');
            var file = input.files && input.files[0];
            if (!file) {
                item.classList.remove('has_preview');
                item.querySelector('.willow_attach_preview').src = '';
                syncAttachVisibility();
                return;
            }

            if (file.size > maxImageSize) {
                alert('이미지는 5MB 이하 파일만 첨부할 수 있습니다.');
                input.value = '';
                item.classList.remove('has_preview');
                item.querySelector('.willow_attach_preview').src = '';
                syncAttachVisibility();
                return;
            }

            var reader = new FileReader();
            reader.onload = function(event) {
                item.querySelector('.willow_attach_preview').src = event.target.result;
                item.classList.add('has_preview');
                syncAttachVisibility();
            };
            reader.readAsDataURL(file);
        });
    });

    document.querySelectorAll('.willow_attach_remove').forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            var item = button.closest('.willow_attach_item');
            var input = item.querySelector('.willow_attach_input');
            var preview = item.querySelector('.willow_attach_preview');
            var previewSrc = preview ? preview.getAttribute('src') : '';
            if (previewSrc) {
                document.querySelectorAll('input[data-existing-image]').forEach(function(existing) {
                    if (existing.getAttribute('data-existing-image') === previewSrc) {
                        existing.remove();
                    }
                });
            }
            input.value = '';
            item.classList.remove('has_preview');
            if (preview) preview.src = '';
            syncAttachVisibility();
        });
    });

    if (saveButton && form) {
        saveButton.addEventListener('click', function() {
            if (saveButton.disabled) return;
            syncTags();
            var formData = new FormData(form);
            formData.append('topic_mode', topicMode);
            saveButton.disabled = true;
            saveButton.textContent = '저장중';

            fetch('<?php echo G5_URL; ?>/willow/write_draft_update.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData,
                credentials: 'same-origin'
            }).then(function(response) {
                return response.json();
            }).then(function(data) {
                if (!data.success) {
                    alert(data.message || '임시저장에 실패했습니다.');
                    return;
                }
                if (draftId) draftId.value = data.draft_id;
                if (Array.isArray(data.images)) {
                    document.querySelectorAll('input[data-existing-image]').forEach(function(input) {
                        input.remove();
                    });
                    data.images.forEach(function(imageUrl) {
                        var hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'existing_images[]';
                        hidden.value = imageUrl;
                        hidden.setAttribute('data-existing-image', imageUrl);
                        form.insertBefore(hidden, form.firstChild);
                    });
                    document.querySelectorAll('.willow_attach_input').forEach(function(input) {
                        input.value = '';
                    });
                }
                alert(data.message || '임시저장되었습니다.');
            }).catch(function() {
                alert('임시저장 중 오류가 발생했습니다.');
            }).finally(function() {
                saveButton.disabled = false;
                saveButton.textContent = '임시저장';
            });
        });
    }

    syncTopic();
    syncContentLimit();
    syncTags();
    syncAttachVisibility();
    syncKeyboardOffset();

    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', syncKeyboardOffset);
        window.visualViewport.addEventListener('scroll', syncKeyboardOffset);
    }
})();
</script>

<?php
include_once(G5_PATH.'/tail.sub.php');
