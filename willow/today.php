<?php
include_once('./_common.php');
include_once('./_data.php');
include_once('./topic.lib.php');

$requested_wt_id = isset($_GET['wt_id']) ? (int) $_GET['wt_id'] : 0;
$willow_topic = $requested_wt_id ? willow_get_topic_by_id($requested_wt_id) : array();
if (empty($willow_topic['wt_id'])) {
    $willow_topic = willow_get_topic();
}
$willow_topic_archive = willow_get_visible_topics(240);
$willow_topic_months = willow_topic_archive_months($willow_topic_archive);
$willow_selected_month = !empty($willow_topic['wt_id']) ? substr(willow_topic_publish_datetime($willow_topic), 0, 7) : substr(G5_TIME_YMD, 0, 7);
$willow_feed_posts = array();
$has_visible_topic = !empty($willow_topic['wt_id']);
$willow_topic_participant_count = $has_visible_topic ? willow_topic_participant_count((int) $willow_topic['wt_id']) : 0;
$willow_topic_recent_participants = $has_visible_topic ? willow_topic_recent_participants((int) $willow_topic['wt_id'], 3) : array();
$topic_posts = $has_visible_topic ? willow_get_topic_posts((int) $willow_topic['wt_id'], 20) : array();
if ($topic_posts) {
    $willow_feed_posts = array_map('willow_topic_post_to_feed', $topic_posts);
}
$write_href = $has_visible_topic ? willow_topic_write_url($willow_topic) : '#';

$g5['title'] = '오늘의 주제';
include_once(G5_PATH.'/head.sub.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/willow_content.css?ver='.G5_CSS_VER.'">', 10);
?>

<main class="willow_content_app">
    <header class="willow_detail_header">
        <a class="willow_back" href="<?php echo G5_URL; ?>" aria-label="뒤로가기"></a>
        <h1>오늘의 주제</h1>
    </header>

    <section class="willow_today_page">
        <?php if ($has_visible_topic) { ?>
        <div class="willow_today_topic">
            <button type="button" class="willow_topic_date_button" data-topic-picker-open aria-haspopup="dialog" aria-expanded="false">
                <strong><?php echo willow_topic_date($willow_topic['wt_date']); ?></strong>
                <i class="fa fa-angle-down" aria-hidden="true"></i>
            </button>
            <h2><?php echo get_text($willow_topic['wt_subject']); ?></h2>
            <div class="willow_topic_participants">
                <?php if ($willow_topic_recent_participants) { ?>
                <span class="willow_topic_avatar_stack" aria-hidden="true">
                    <?php foreach ($willow_topic_recent_participants as $participant) { ?>
                    <img src="<?php echo $participant['avatar']; ?>" alt="">
                    <?php } ?>
                </span>
                <?php } ?>
                <p><?php echo willow_topic_participants($willow_topic_participant_count); ?></p>
            </div>
            <a href="<?php echo $write_href; ?>">나도 글쓰기</a>
        </div>
        <div class="willow_topic_picker" data-topic-picker aria-hidden="true">
            <div class="willow_topic_picker_dim" data-topic-picker-close></div>
            <section class="willow_topic_picker_panel" role="dialog" aria-modal="true" aria-labelledby="willow_topic_picker_title">
                <header>
                    <h2 id="willow_topic_picker_title">주제 선택</h2>
                    <button type="button" data-topic-picker-close aria-label="닫기"></button>
                </header>
                <?php if ($willow_topic_months) { ?>
                <div class="willow_topic_months" role="tablist" aria-label="월 선택">
                    <?php foreach ($willow_topic_months as $month_key => $month_label) { ?>
                    <button type="button" class="<?php echo $month_key === $willow_selected_month ? 'is_active' : ''; ?>" data-topic-month="<?php echo get_text($month_key); ?>">
                        <?php echo get_text($month_label); ?>
                    </button>
                    <?php } ?>
                </div>
                <div class="willow_topic_picker_list" tabindex="0">
                    <?php foreach ($willow_topic_archive as $topic_item) { ?>
                    <?php
                    $topic_month = substr(willow_topic_publish_datetime($topic_item), 0, 7);
                    $topic_selected = (int) $topic_item['wt_id'] === (int) $willow_topic['wt_id'];
                    $topic_href = G5_URL.'/willow/today.php?wt_id='.(int) $topic_item['wt_id'];
                    ?>
                    <a class="<?php echo $topic_selected ? 'is_selected' : ''; ?>" href="<?php echo $topic_href; ?>" data-topic-item data-topic-month-item="<?php echo get_text($topic_month); ?>">
                        <time datetime="<?php echo get_text($topic_item['wt_date']); ?>"><?php echo get_text(willow_topic_date($topic_item['wt_date'])); ?></time>
                        <span><?php echo get_text($topic_item['wt_subject']); ?></span>
                    </a>
                    <?php } ?>
                </div>
                <?php } else { ?>
                <p class="willow_topic_picker_empty">선택 가능한 주제가 없습니다.</p>
                <?php } ?>
            </section>
        </div>
        <?php } else { ?>
        <div class="willow_today_topic">
            <strong><?php echo willow_topic_date(G5_TIME_YMD); ?></strong>
            <h2>아직 공개된 오늘의 주제가 없습니다.</h2>
            <div class="willow_topic_participants">
                <p>새 주제가 열리면 이곳에서 확인할 수 있어요</p>
            </div>
        </div>
        <?php } ?>

        <div class="willow_content_feed" aria-label="오늘의 주제 글">
            <?php foreach ($willow_feed_posts as $post) { ?>
            <?php $is_post_owner = !empty($member['mb_id']) && !empty($post['mb_id']) && $member['mb_id'] === $post['mb_id']; ?>
            <article class="willow_feed_card">
                <div class="willow_feed_head">
                    <img src="<?php echo $post['avatar']; ?>" alt="">
                    <div>
                        <strong><?php echo $post['author']; ?><?php if (!empty($post['verified'])) { ?><img class="willow_author_cert_mark" src="<?php echo G5_IMG_URL; ?>/img_cert_mark.png" alt="탈북이주민 인증"><?php } ?></strong>
                        <span><?php echo $post['date']; ?></span>
                    </div>
                    <div class="willow_more">
                        <button class="willow_more_button" type="button" aria-label="더보기" aria-expanded="false">
                            <span aria-hidden="true"></span>
                        </button>
                        <div class="willow_more_menu" role="menu">
                            <?php if ($is_post_owner) { ?>
                            <button type="button" role="menuitem">수정하기</button>
                            <button type="button" role="menuitem">삭제하기</button>
                            <?php } else { ?>
                            <button type="button" role="menuitem" class="willow_report_button" data-target-type="<?php echo $post['target_type']; ?>" data-target-id="<?php echo (int) $post['id']; ?>">신고하기</button>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <a href="<?php echo $post['href']; ?>" aria-label="<?php echo $post['author']; ?> 글 보기">
                    <?php if (!empty($post['title'])) { ?>
                    <h3><?php echo $post['title']; ?></h3>
                    <?php } ?>
                    <p><?php echo $post['body']; ?></p>
                    <?php if (!empty($post['image'])) { ?>
                    <img class="willow_feed_image" src="<?php echo $post['image']; ?>" alt="">
                    <?php } ?>
                </a>
                <div class="willow_feed_meta">
                    <button type="button" class="willow_like_button <?php echo !empty($post['liked']) ? 'is_liked' : ''; ?>" data-target-type="<?php echo $post['target_type']; ?>" data-target-id="<?php echo (int) $post['id']; ?>" aria-pressed="<?php echo !empty($post['liked']) ? 'true' : 'false'; ?>">
                        <img class="willow_meta_icon" src="<?php echo G5_IMG_URL; ?>/ico_heart<?php echo !empty($post['liked']) ? '_active' : ''; ?>.png" alt="" data-icon-heart data-icon-default="<?php echo G5_IMG_URL; ?>/ico_heart.png" data-icon-active="<?php echo G5_IMG_URL; ?>/ico_heart_active.png"><span data-like-count><?php echo (int) str_replace(',', '', $post['likes']) > 0 ? $post['likes'] : ''; ?></span>
                    </button>
                    <a class="willow_comment_link" href="<?php echo $post['href']; ?>#willow_comments">
                        <img class="willow_meta_icon" src="<?php echo G5_IMG_URL; ?>/ico_rep.png" alt=""><span data-comment-count><?php echo (int) str_replace(',', '', $post['comments']) > 0 ? $post['comments'] : ''; ?></span>
                    </a>
                    <?php if (!empty($post['actions'])) { ?>
                    <div class="willow_feed_actions">
                        <a href="<?php echo $write_href; ?>">글쓰기</a>
                        <a href="<?php echo G5_URL; ?>/willow/post.php?type=paid">스토리</a>
                    </div>
                    <?php } ?>
                </div>
            </article>
            <?php } ?>
        </div>
    </section>

    <script>
    (function() {
        var picker = document.querySelector('[data-topic-picker]');
        var pickerOpen = document.querySelector('[data-topic-picker-open]');
        var monthButtons = document.querySelectorAll('[data-topic-month]');
        var topicItems = document.querySelectorAll('[data-topic-item]');

        function setPickerOpen(open) {
            if (!picker) return;
            picker.classList.toggle('is_open', open);
            picker.setAttribute('aria-hidden', open ? 'false' : 'true');
            if (pickerOpen) pickerOpen.setAttribute('aria-expanded', open ? 'true' : 'false');
            document.documentElement.classList.toggle('willow_topic_picker_locked', open);
            if (open) {
                var selected = picker.querySelector('[data-topic-item].is_selected');
                if (selected) selected.scrollIntoView({block: 'nearest'});
            }
        }

        function selectMonth(month) {
            monthButtons.forEach(function(button) {
                button.classList.toggle('is_active', button.getAttribute('data-topic-month') === month);
            });
            topicItems.forEach(function(item) {
                var isMatch = item.getAttribute('data-topic-month-item') === month;
                item.hidden = !isMatch;
            });
        }

        if (pickerOpen) {
            pickerOpen.addEventListener('click', function() {
                var willOpen = !picker || !picker.classList.contains('is_open');
                setPickerOpen(willOpen);
            });
        }

        document.querySelectorAll('[data-topic-picker-close]').forEach(function(closeButton) {
            closeButton.addEventListener('click', function() {
                setPickerOpen(false);
            });
        });

        monthButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                selectMonth(button.getAttribute('data-topic-month'));
            });
        });

        var activeMonth = document.querySelector('[data-topic-month].is_active');
        if (activeMonth) {
            selectMonth(activeMonth.getAttribute('data-topic-month'));
            activeMonth.scrollIntoView({inline: 'center', block: 'nearest'});
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                setPickerOpen(false);
            }
        });

        document.addEventListener('click', function(event) {
            var likeButton = event.target.closest('.willow_like_button');
            if (likeButton) {
                event.preventDefault();
                if (likeButton.disabled) return;
                likeButton.disabled = true;
                var formData = new FormData();
                formData.append('target_type', likeButton.getAttribute('data-target-type'));
                formData.append('target_id', likeButton.getAttribute('data-target-id'));
                fetch('<?php echo G5_URL; ?>/willow/like.php', {
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
                        alert(data.message || '좋아요 처리에 실패했습니다.');
                        return;
                    }
                    likeButton.classList.toggle('is_liked', !!data.liked);
                    likeButton.setAttribute('aria-pressed', data.liked ? 'true' : 'false');
                    var icon = likeButton.querySelector('[data-icon-heart]');
                    if (icon) icon.src = data.liked ? icon.getAttribute('data-icon-active') : icon.getAttribute('data-icon-default');
                    var count = likeButton.querySelector('[data-like-count]');
                    if (count) count.textContent = parseInt(String(data.count).replace(/,/g, ''), 10) > 0 ? data.count : '';
                }).catch(function() {
                    alert('좋아요 처리 중 오류가 발생했습니다.');
                }).finally(function() {
                    likeButton.disabled = false;
                });
                return;
            }

            var reportButton = event.target.closest('.willow_report_button');
            if (reportButton) {
                event.preventDefault();
                var reportContent = prompt('신고 내용을 입력해주세요.');
                if (reportContent === null) return;
                reportContent = reportContent.trim();
                if (!reportContent) {
                    alert('신고 내용을 입력해주세요.');
                    return;
                }
                reportButton.disabled = true;
                var reportData = new FormData();
                reportData.append('target_type', reportButton.getAttribute('data-target-type'));
                reportData.append('target_id', reportButton.getAttribute('data-target-id'));
                reportData.append('content', reportContent);
                fetch('<?php echo G5_URL; ?>/willow/report_update.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: reportData,
                    credentials: 'same-origin'
                }).then(function(response) {
                    return response.json();
                }).then(function(data) {
                    if (!data.success) {
                        alert(data.message || '신고 접수에 실패했습니다.');
                        return;
                    }
                    alert(data.message || '신고 내용이 접수되었습니다.');
                    var wrap = reportButton.closest('.willow_more');
                    if (wrap) wrap.classList.remove('is_open');
                }).catch(function() {
                    alert('신고 접수 중 오류가 발생했습니다.');
                }).finally(function() {
                    reportButton.disabled = false;
                });
                return;
            }

            var toggle = event.target.closest('.willow_more_button');
            document.querySelectorAll('.willow_more.is_open').forEach(function(menu) {
                if (!toggle || !menu.contains(toggle)) {
                    menu.classList.remove('is_open');
                    var button = menu.querySelector('.willow_more_button');
                    if (button) button.setAttribute('aria-expanded', 'false');
                }
            });
            if (!toggle) return;
            event.preventDefault();
            var wrap = toggle.closest('.willow_more');
            var isOpen = wrap.classList.toggle('is_open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    })();
    </script>

    <?php include_once(G5_PATH.'/willow/bottom_nav.inc.php'); ?>
</main>

<?php
include_once(G5_PATH.'/tail.sub.php');
