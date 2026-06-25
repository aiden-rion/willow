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

<main class="willow_content_app willow_write_app">
    <header class="willow_detail_header">
        <a class="willow_back" href="<?php echo G5_URL; ?>/willow/today.php" aria-label="뒤로가기"></a>
        <h1>글쓰기</h1>
    </header>

    <form class="willow_topic_write_form" action="<?php echo G5_URL; ?>/willow/write_update.php" method="post" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="MAX_FILE_SIZE" value="5242880">
        <input type="hidden" name="wt_id" value="<?php echo (int) $topic['wt_id']; ?>">
        <input type="hidden" id="wd_id" name="wd_id" value="<?php echo (int) $draft_id; ?>">
        <input type="hidden" id="wp_tags" name="wp_tags" value="<?php echo get_text(!empty($draft['wd_tags']) ? $draft['wd_tags'] : ''); ?>">
        <?php foreach ($draft_images as $draft_image) { ?>
        <input type="hidden" name="existing_images[]" value="<?php echo get_text($draft_image); ?>" data-existing-image="<?php echo get_text($draft_image); ?>">
        <?php } ?>

        <section class="willow_write_topic" data-topic-card>
            <button type="button" class="willow_topic_toggle" aria-label="주제 유형 선택">
                <span data-topic-label>오늘의 주제</span>
                <i class="fa fa-angle-down" aria-hidden="true"></i>
            </button>
            <h2 data-topic-title><?php echo $willow_topic_title; ?></h2>
            <?php if ($willow_topic_description) { ?>
            <p data-topic-description><?php echo $willow_topic_description; ?></p>
            <?php } else { ?>
            <p data-topic-description></p>
            <?php } ?>
        </section>

        <div class="willow_write_subject">
            <label for="wp_subject" class="sound_only">제목</label>
            <input type="text" id="wp_subject" name="wp_subject" value="<?php echo $draft_subject; ?>" maxlength="80" placeholder="제목을 입력해주세요" required>
        </div>

        <div class="willow_write_field">
            <label for="wp_content" class="sound_only">내용</label>
            <textarea id="wp_content" name="wp_content" maxlength="500" placeholder="오늘의 주제에 대한 생각을&#10;자유롭게 작성해주세요" required><?php echo $draft_content; ?></textarea>
            <div class="willow_write_count"><span id="willow_write_count">0</span> / 500자</div>
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

        <section class="willow_write_options">
            <div class="willow_option_group">
                <strong>감정태그선택</strong>
                <div class="willow_tag_chips" role="group" aria-label="감정태그선택">
                    <?php foreach ($willow_tags as $tag) { ?>
                    <button type="button" class="willow_tag_chip <?php echo in_array($tag, $draft_tags, true) ? 'is_selected' : ''; ?>" data-tag="<?php echo $tag; ?>">#<?php echo $tag; ?></button>
                    <?php } ?>
                </div>
            </div>

            <div class="willow_option_group">
                <label for="wp_access">구독자 전용 선택</label>
                <div class="willow_select_shell">
                    <select id="wp_access" name="wp_access">
                        <option value="public" <?php echo $draft_access === 'public' ? 'selected' : ''; ?>>전체공개</option>
                        <option value="subscriber" <?php echo $draft_access === 'subscriber' ? 'selected' : ''; ?>>구독자 전용</option>
                    </select>
                    <i class="fa fa-angle-down" aria-hidden="true"></i>
                </div>
            </div>
        </section>

        <div class="willow_write_actions">
            <button type="button" class="willow_save_button">임시저장</button>
            <button type="submit" class="willow_submit_button">작성완료</button>
        </div>
    </form>
</main>

<script>
(function() {
    var topicTitle = <?php echo json_encode($willow_topic_title); ?>;
    var topicDescription = <?php echo json_encode($willow_topic_description); ?>;
    var maxImageSize = 5 * 1024 * 1024;
    var topicMode = <?php echo json_encode($draft_topic_mode); ?>;
    var form = document.querySelector('.willow_topic_write_form');
    var content = document.getElementById('wp_content');
    var count = document.getElementById('willow_write_count');
    var draftId = document.getElementById('wd_id');
    var tags = document.getElementById('wp_tags');
    var app = document.querySelector('.willow_write_app');
    var label = document.querySelector('[data-topic-label]');
    var title = document.querySelector('[data-topic-title]');
    var description = document.querySelector('[data-topic-description]');
    var toggle = document.querySelector('.willow_topic_toggle');
    var saveButton = document.querySelector('.willow_save_button');

    function syncTopic() {
        var isFree = topicMode === 'free';
        app.classList.toggle('is_free_topic', isFree);
        label.textContent = isFree ? '자유주제' : '오늘의 주제';
        title.textContent = isFree ? '' : topicTitle;
        description.innerHTML = isFree ? '' : topicDescription;
        content.placeholder = isFree ? '자유롭게 글을 작성해주세요' : '오늘의 주제에 대한 생각을\n자유롭게 작성해주세요';
    }

    function syncCount() {
        count.textContent = (content.value || '').length;
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
        var firstEmptyShown = false;

        items.forEach(function(item) {
            item.classList.remove('is_visible');
        });

        items.forEach(function(item, index) {
            var hasPreview = item.classList.contains('has_preview');
            if (hasPreview || index === 0) {
                item.classList.add('is_visible');
                if (!hasPreview) {
                    firstEmptyShown = true;
                }
                return;
            }

            if (!firstEmptyShown) {
                item.classList.add('is_visible');
                firstEmptyShown = true;
            }
        });
    }

    toggle.addEventListener('click', function() {
        topicMode = topicMode === 'today' ? 'free' : 'today';
        syncTopic();
    });

    content.addEventListener('input', syncCount);

    document.querySelectorAll('.willow_tag_chip').forEach(function(chip) {
        chip.addEventListener('click', function() {
            chip.classList.toggle('is_selected');
            syncTags();
        });
    });

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
    syncCount();
    syncTags();
    syncAttachVisibility();
})();
</script>

<?php
include_once(G5_PATH.'/tail.sub.php');
