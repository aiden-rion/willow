<?php
include_once('./_common.php');
include_once('./_data.php');
include_once('./topic.lib.php');
include_once('./content.lib.php');
include_once('./notification.lib.php');

$type = isset($_GET['type']) && $_GET['type'] === 'general' ? 'general' : 'paid';
$is_general = $type === 'general';
$article_requires_subscription = !$is_general;
$article_title = $is_general ? '윌로우와 함께한 시간을 이야기' : '나를 잠시 멈추게한 것';
$topic_post = array();
$topic = array();
$board_post = array();
$wr_id = isset($_GET['wr_id']) ? (int) $_GET['wr_id'] : 0;
$article_author = '김나영';
$article_author_avatar = $willow_author_avatar;
$article_date = '2026년 1월 1일';
$article_like = '30';
$article_comment = '0';
$article_view_count = 0;
$article_liked = false;
$willow_target_type = '';
$willow_target_id = 0;
$willow_comments = array();
$article_body = array();
$article_image = '';
$article_images = array();
$article_author_id = '';
$willow_author_other_posts = array('free' => array(), 'paid' => array());
$article_locked = false;
$article_subscribe_href = G5_URL.'/willow/subscribe.php';
$article_author_profile = '안녕하세요 저는 북한의 일상을 다루는 작가입니다. 윌로우를 통해 우리의 좋은 소식들을 많이 전달할 수 있도록 노력하겠습니다.';
$article_author_post_count = 0;
$article_author_subscriber_count = 0;
$article_author_href = '';
$article_author_subscribed = false;
$article_author_member = array();

function willow_article_increment_board_hit($wr_id)
{
    $wr_id = (int) $wr_id;
    if (!$wr_id) {
        return;
    }

    $session_key = 'willow_board_hit_'.$wr_id;
    if (!empty($_SESSION[$session_key])) {
        return;
    }

    $table = willow_content_table();
    sql_query(" update {$table} set wr_hit = wr_hit + 1 where wr_id = '{$wr_id}' and wr_is_comment = 0 ", false);
    $_SESSION[$session_key] = G5_SERVER_TIME;
}

function willow_article_author_post_count($mb_id, $author_name = '')
{
    $board_table = willow_content_table();
    $board_where = " wr_is_comment = 0 ";
    $topic_where = " 1 ";

    if ($mb_id !== '') {
        $mb_id_sql = sql_escape_string($mb_id);
        $board_where .= " and mb_id = '{$mb_id_sql}' ";
        $topic_where .= " and mb_id = '{$mb_id_sql}' ";
    } else if ($author_name !== '') {
        $author_sql = sql_escape_string($author_name);
        $board_where .= " and wr_name = '{$author_sql}' ";
        $topic_where .= " and wp_author = '{$author_sql}' ";
    } else {
        return 0;
    }

    $board = sql_fetch(" select count(*) as cnt from {$board_table} where {$board_where} ", false);
    $topic_count = 0;
    if (function_exists('willow_topic_tables')) {
        $tables = willow_topic_tables();
        willow_topic_install();
        $topic = sql_fetch(" select count(*) as cnt from `{$tables['post']}` where {$topic_where} ", false);
        $topic_count = isset($topic['cnt']) ? (int) $topic['cnt'] : 0;
    }

    return (int) $board['cnt'] + $topic_count;
}

function willow_article_author_subscriber_count($mb_id)
{
    if ($mb_id === '') {
        return 0;
    }

    willow_notification_install();
    $subscription_table = willow_subscription_table();
    $row = sql_fetch(" select count(*) as cnt
        from `{$subscription_table}`
        where author_mb_id = '".sql_escape_string($mb_id)."'
            and ws_status = 'active' ", false);

    return isset($row['cnt']) ? (int) $row['cnt'] : 0;
}

function willow_article_author_meta($mb_id, $author_name)
{
    $member_row = $mb_id !== '' ? get_member($mb_id) : array();
    $profile = !empty($member_row['mb_profile']) ? get_text($member_row['mb_profile']) : '윌로우에서 새로운 이야기를 전하는 작가입니다.';

    return array(
        'profile' => $profile,
        'post_count' => willow_article_author_post_count($mb_id, $author_name),
        'subscriber_count' => willow_article_author_subscriber_count($mb_id),
    );
}

if ($wr_id) {
    willow_article_increment_board_hit($wr_id);
    $board_post = willow_get_board_post($wr_id);
    if (empty($board_post['id'])) {
        alert('글을 찾을 수 없습니다.', G5_URL);
    }
    $is_general = empty($board_post['access']) || !willow_is_paid_access($board_post['access']);
    $article_requires_subscription = !empty($board_post['access']) && willow_is_paid_access($board_post['access']);
    $article_title = $board_post['title'];
    $article_author = $board_post['author'];
    $article_author_avatar = $board_post['avatar'];
    $article_date = str_replace('.', '-', $board_post['date']);
    $article_time = strtotime($article_date);
    $article_date = $article_time ? date('Y년 m월 d일', $article_time) : $board_post['date'];
    $article_like = $board_post['likes'];
    $article_comment = $board_post['comments'];
    $article_view_count = isset($board_post['views']) ? (int) $board_post['views'] : 0;
    $article_liked = !empty($board_post['liked']);
    $willow_target_type = 'board';
    $willow_target_id = (int) $board_post['id'];
    $article_body = willow_content_paragraphs(!empty($board_post['raw_content']) ? $board_post['raw_content'] : $board_post['body']);
    $article_image = $board_post['image'];
    $article_images = array_merge(!empty($board_post['images']) ? $board_post['images'] : willow_split_images($article_image), willow_get_board_attached_images((int) $board_post['id']));
    $article_author_id = !empty($board_post['mb_id']) ? $board_post['mb_id'] : '';
    $g5['title'] = $article_title;
}

$wp_id = isset($_GET['wp_id']) ? (int) $_GET['wp_id'] : 0;
if ($wp_id) {
    willow_topic_install();
    $tables = willow_topic_tables();
    $topic_post = sql_fetch(" select * from `{$tables['post']}` where wp_id = '{$wp_id}' ");
    if (empty($topic_post['wp_id'])) {
        alert('글을 찾을 수 없습니다.', G5_URL.'/willow/today.php');
    }
    $topic = sql_fetch(" select * from `{$tables['topic']}` where wt_id = '".(int) $topic_post['wt_id']."' ");
    $is_general = false;
    $article_requires_subscription = false;
    $article_title = $topic_post['wp_subject'];
    $article_author = $topic_post['wp_author'] ? $topic_post['wp_author'] : '윌로우 회원';
    $member = $topic_post['mb_id'] ? get_member($topic_post['mb_id']) : array();
    $article_author_avatar = willow_member_avatar($member);
    $article_date = !empty($topic_post['wp_datetime']) ? get_text(substr($topic_post['wp_datetime'], 0, 10)) : '2026-01-01';
    $article_like = number_format(willow_like_count('topic', (int) $topic_post['wp_id']));
    $article_comment = number_format(willow_comment_count('topic', (int) $topic_post['wp_id']));
    $article_view_count = 0;
    $article_liked = willow_has_liked('topic', (int) $topic_post['wp_id']);
    $willow_target_type = 'topic';
    $willow_target_id = (int) $topic_post['wp_id'];
    $article_body = willow_content_paragraphs($topic_post['wp_content']);
    $article_image = willow_first_image($topic_post['wp_image']);
    $article_images = willow_split_images($topic_post['wp_image']);
    $article_author_id = $topic_post['mb_id'];
    $g5['title'] = $article_title;
}

if ($article_author_id !== '') {
    $article_author_member = get_member($article_author_id);
    if (!empty($article_author_member['mb_id']) && !((int) $article_author_member['mb_level'] >= 3 || $article_author_member['mb_2'] === 'author') && $article_author !== '') {
        $article_author_sql = sql_escape_string($article_author);
        $resolved_author = sql_fetch(" select *
            from {$g5['member_table']}
            where mb_leave_date = ''
                and mb_level < 10
                and (mb_level >= 3 or mb_2 = 'author')
                and (mb_nick = '{$article_author_sql}' or mb_name = '{$article_author_sql}')
            order by mb_level desc, mb_datetime desc
            limit 1 ", false);
        if (!empty($resolved_author['mb_id'])) {
            $article_author_id = $resolved_author['mb_id'];
            $article_author_avatar = willow_member_avatar($resolved_author);
        }
    }
}

if ($willow_target_id) {
    $willow_author_other_posts = willow_get_author_other_posts($article_author_id, $article_author, $willow_target_type, $willow_target_id, 3);
}

if (empty($g5['title'])) {
    $g5['title'] = $is_general ? '일반주제의 글' : '유료구독글';
}

if ($willow_target_id) {
    $willow_comments = willow_get_comments($willow_target_type, $willow_target_id, 100);
}

if ($article_requires_subscription) {
    willow_notification_install();
    $article_locked = true;

    if ($is_admin || (!empty($member['mb_id']) && $article_author_id !== '' && $member['mb_id'] === $article_author_id)) {
        $article_locked = false;
    } else if (!empty($member['mb_id']) && $article_author_id !== '') {
        $subscription_table = willow_subscription_table();
        $subscription = sql_fetch(" select ws_id from `{$subscription_table}`
            where author_mb_id = '".sql_escape_string($article_author_id)."'
                and subscriber_mb_id = '".sql_escape_string($member['mb_id'])."'
                and ws_status = 'active'
            limit 1 ", false);
        if (!empty($subscription['ws_id'])) {
            $article_locked = false;
        }
    }
}

if ($article_author_id !== '') {
    $article_subscribe_href = G5_URL.'/willow/subscribe.php?author='.urlencode($article_author_id);
    $article_author_href = G5_URL.'/willow/author.php?author='.urlencode($article_author_id);
    $article_author_subscribed = function_exists('willow_author_is_subscribed') ? willow_author_is_subscribed($article_author_id) : false;
} else if ($article_author !== '') {
    $article_author_href = G5_URL.'/willow/author.php?name='.urlencode($article_author);
}

if ($article_author_id !== '' || $article_author !== '') {
    $article_author_meta = willow_article_author_meta($article_author_id, $article_author);
    $article_author_profile = $article_author_meta['profile'];
    $article_author_post_count = $article_author_meta['post_count'];
    $article_author_subscriber_count = $article_author_meta['subscriber_count'];
}

if ($article_author_id !== '') {
    $article_author_member = get_member($article_author_id);
}

$article_author_name_html = !empty($article_author_member['mb_id']) ? willow_author_name_html($article_author_member) : get_text($article_author);

include_once(G5_PATH.'/head.sub.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/willow_content.css?ver='.G5_CSS_VER.'">', 10);
?>

<main class="willow_content_app willow_article_page">
    <header class="willow_detail_header">
        <a class="willow_back" href="<?php echo G5_URL; ?>/willow/today.php" aria-label="뒤로가기"></a>
    </header>

    <article class="willow_article_inner">
        <div class="willow_article_tags">
            <?php if (!$is_general) { ?><span>유료구독</span><?php } ?>
            <span>스토리</span>
        </div>

        <?php if (!$is_general) { ?><p class="willow_article_kicker">오늘의 주제<?php if (!empty($topic['wt_subject'])) { ?> · <?php echo get_text($topic['wt_subject']); ?><?php } ?></p><?php } ?>
        <h1 class="willow_article_title"><?php echo get_text($article_title); ?></h1>

        <a class="willow_article_byline" href="<?php echo $article_author_href ? $article_author_href : $article_subscribe_href; ?>">
            <div>
                <strong>작가 <?php echo $article_author_name_html; ?></strong>
                <span><?php echo get_text($article_date); ?> | 조회 <?php echo number_format($article_view_count); ?></span>
            </div>
            <img src="<?php echo $article_author_avatar; ?>" alt="">
        </a>

        <?php if ($willow_target_id) { ?>
        <div class="willow_article_like">
            <button type="button" class="willow_like_button <?php echo $article_liked ? 'is_liked' : ''; ?>" data-target-type="<?php echo $willow_target_type; ?>" data-target-id="<?php echo (int) $willow_target_id; ?>" aria-pressed="<?php echo $article_liked ? 'true' : 'false'; ?>">
                <img class="willow_meta_icon" src="<?php echo G5_IMG_URL; ?>/ico_heart<?php echo $article_liked ? '_active' : ''; ?>.png" alt="" data-icon-heart data-icon-default="<?php echo G5_IMG_URL; ?>/ico_heart.png" data-icon-active="<?php echo G5_IMG_URL; ?>/ico_heart_active.png"><span data-like-count><?php echo (int) str_replace(',', '', $article_like) > 0 ? $article_like : ''; ?></span>
            </button>
        </div>
        <?php } ?>

        <div class="willow_paid_preview <?php echo $article_locked ? 'is_locked' : ''; ?>">
            <div class="willow_article_body">
                <?php if (!empty($article_body)) { ?>
                <?php foreach ($article_body as $paragraph) { ?>
                <p><?php echo nl2br(get_text($paragraph)); ?></p>
                <?php } ?>
                <?php } else { ?>
                <?php foreach ($willow_article_paragraphs as $paragraph) { ?>
                <p><?php echo $paragraph; ?></p>
                <?php } ?>
                <?php } ?>
            </div>

            <?php if ($article_images) { ?>
            <div class="willow_article_images" aria-label="첨부 이미지">
                <?php foreach ($article_images as $image_index => $image_url) { ?>
                <a class="willow_video_thumb" href="<?php echo $image_url; ?>" data-gallery-index="<?php echo (int) $image_index; ?>">
                    <img src="<?php echo $image_url; ?>" alt="">
                </a>
                <?php } ?>
            </div>
            <?php } else if (empty($topic_post)) { ?>
            <div class="willow_article_images">
                <a class="willow_video_thumb" href="<?php echo $article_subscribe_href; ?>">
                    <img src="<?php echo $willow_video_thumb; ?>" alt="">
                    <span>26:19</span>
                </a>
            </div>
            <?php } ?>

            <?php if ($article_locked) { ?>
            <div class="willow_paid_fade" aria-hidden="true"></div>
            <?php } ?>
        </div>

        <?php if ($article_locked) { ?>
        <section class="willow_paid_gate" aria-label="구독 안내">
            <h2>구독자 전용 글 입니다.</h2>
            <p>구독 후 이용이 가능합니다.</p>
            <a href="<?php echo $article_subscribe_href; ?>">구독하기</a>
        </section>
        <?php } else { ?>

        <section class="willow_author_intro">
            <h2>작가소개</h2>
            <p><?php echo nl2br($article_author_profile); ?></p>
        </section>

        <section>
            <div class="willow_author_box">
                <img src="<?php echo $article_author_avatar; ?>" alt="">
                <div>
                    <strong>작가 <?php echo $article_author_name_html; ?></strong>
                    <span>작성 글 : <?php echo number_format($article_author_post_count); ?>개, 구독자 : <?php echo number_format($article_author_subscriber_count); ?>명</span>
                </div>
            </div>
            <a class="willow_subscribe_button <?php echo $article_author_subscribed ? 'is_subscribed' : ''; ?>" href="<?php echo $article_author_subscribed ? G5_URL.'/willow/subscribe.php?mode=my&amp;author='.urlencode($article_author_id) : $article_subscribe_href; ?>"><?php echo $article_author_subscribed ? '구독중' : '작가 구독하기'; ?></a>
        </section>

        <?php if ($willow_target_id) { ?>
        <section id="willow_comments" class="willow_comments" data-target-type="<?php echo $willow_target_type; ?>" data-target-id="<?php echo (int) $willow_target_id; ?>">
            <div class="willow_comments_head">
                <h2>댓글 <span data-comment-count><?php echo $article_comment; ?></span></h2>
            </div>
            <div class="willow_comment_list" aria-live="polite">
                <?php if ($willow_comments) { ?>
                <?php foreach ($willow_comments as $comment) { ?>
                <article class="willow_comment_item">
                    <img class="willow_comment_avatar" src="<?php echo $comment['avatar']; ?>" alt="">
                    <div class="willow_comment_content">
                        <strong><?php echo $comment['author']; ?></strong>
                        <p><?php echo nl2br($comment['content']); ?></p>
                        <time><?php echo get_text($comment['date']); ?></time>
                    </div>
                </article>
                <?php } ?>
                <?php } else { ?>
                <p class="willow_comment_empty">첫 댓글을 남겨보세요.</p>
                <?php } ?>
            </div>
            <form class="willow_comment_form">
                <textarea name="content" rows="3" placeholder="댓글을 입력하세요" required></textarea>
                <button type="submit">등록</button>
            </form>
        </section>
        <?php } ?>

        <section class="willow_other_posts">
            <h2>작가의 다른 글<?php echo $is_general ? '들' : ''; ?></h2>
            <?php if (!empty($willow_author_other_posts['items'])) { ?>
            <?php foreach ($willow_author_other_posts['items'] as $item) { ?>
            <?php
            $item_access_label = !empty($item['access']) ? willow_post_access_label($item['access']) : '무료';
            ?>
            <article class="<?php echo !empty($item['image']) ? 'has_thumb' : 'no_thumb'; ?>">
                <a class="willow_other_post_link" href="<?php echo !empty($item['href']) ? $item['href'] : '#'; ?>">
                    <div>
                        <h3><?php echo $item['title']; ?></h3>
                        <p><?php echo get_text(willow_content_excerpt($item['excerpt'], 46)); ?></p>
                        <span><?php echo !empty($item['author']) ? '작가 '.$item['author'] : '작가 '.$article_author; ?> · <?php echo $item['date']; ?> · <?php echo $item_access_label; ?></span>
                    </div>
                    <?php if (!empty($item['image'])) { ?>
                    <img src="<?php echo $item['image']; ?>" alt="">
                    <?php } ?>
                </a>
            </article>
            <?php } ?>
            <?php } ?>
            <?php if (empty($willow_author_other_posts['items'])) { ?>
            <p class="willow_other_empty">아직 이 작가의 다른 글이 없습니다.</p>
            <?php } ?>
        </section>
        <?php } ?>
    </article>

    <?php if (!$article_requires_subscription) { ?>
    <?php willow_render_banner_area('post_bottom', 'willow_article_bottom_banner', '게시글상세 하단 배너'); ?>
    <?php } ?>

    <div class="willow_image_viewer" aria-hidden="true">
        <button type="button" class="willow_image_viewer_close" aria-label="닫기"></button>
        <button type="button" class="willow_image_viewer_prev" aria-label="이전 이미지"></button>
        <div class="willow_image_viewer_track" aria-live="polite"></div>
        <button type="button" class="willow_image_viewer_next" aria-label="다음 이미지"></button>
        <span class="willow_image_viewer_count"></span>
    </div>

    <script>
    (function() {
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }

        function resetArticleScroll() {
            if (window.location.hash) {
                return;
            }

            window.scrollTo(0, 0);
            document.documentElement.scrollTop = 0;
            document.body.scrollTop = 0;
        }

        resetArticleScroll();
        window.addEventListener('load', function() {
            setTimeout(resetArticleScroll, 0);
            setTimeout(resetArticleScroll, 120);
        });
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                setTimeout(resetArticleScroll, 0);
            }
        });

        var galleryLinks = Array.prototype.slice.call(document.querySelectorAll('.willow_article_images [data-gallery-index]'));
        var viewer = document.querySelector('.willow_image_viewer');
        var viewerTrack = viewer ? viewer.querySelector('.willow_image_viewer_track') : null;
        var viewerCount = viewer ? viewer.querySelector('.willow_image_viewer_count') : null;
        var currentImageIndex = 0;
        var touchStartX = 0;

        function showGalleryImage(index) {
            if (!viewer || !viewerTrack || !galleryLinks.length) return;
            currentImageIndex = (index + galleryLinks.length) % galleryLinks.length;
            var image = galleryLinks[currentImageIndex].querySelector('img');
            if (!image) return;
            viewerTrack.innerHTML = '';
            var largeImage = document.createElement('img');
            largeImage.src = galleryLinks[currentImageIndex].getAttribute('href');
            largeImage.alt = image.getAttribute('alt') || '';
            viewerTrack.appendChild(largeImage);
            if (viewerCount) viewerCount.textContent = (currentImageIndex + 1) + ' / ' + galleryLinks.length;
        }

        function openGallery(index) {
            if (!viewer) return;
            showGalleryImage(index);
            viewer.classList.add('is_open');
            viewer.setAttribute('aria-hidden', 'false');
            document.documentElement.classList.add('willow_viewer_open');
        }

        function closeGallery() {
            if (!viewer) return;
            viewer.classList.remove('is_open');
            viewer.setAttribute('aria-hidden', 'true');
            document.documentElement.classList.remove('willow_viewer_open');
        }

        galleryLinks.forEach(function(link) {
            link.addEventListener('click', function(event) {
                event.preventDefault();
                openGallery(parseInt(link.getAttribute('data-gallery-index'), 10) || 0);
            });
        });

        if (viewer) {
            viewer.addEventListener('click', function(event) {
                if (event.target === viewer || event.target.closest('.willow_image_viewer_close')) {
                    closeGallery();
                } else if (event.target.closest('.willow_image_viewer_prev')) {
                    showGalleryImage(currentImageIndex - 1);
                } else if (event.target.closest('.willow_image_viewer_next')) {
                    showGalleryImage(currentImageIndex + 1);
                }
            });

            viewer.addEventListener('touchstart', function(event) {
                touchStartX = event.touches && event.touches[0] ? event.touches[0].clientX : 0;
            }, { passive: true });

            viewer.addEventListener('touchend', function(event) {
                if (!touchStartX || !event.changedTouches || !event.changedTouches[0]) return;
                var deltaX = event.changedTouches[0].clientX - touchStartX;
                if (Math.abs(deltaX) > 45) {
                    showGalleryImage(deltaX < 0 ? currentImageIndex + 1 : currentImageIndex - 1);
                }
                touchStartX = 0;
            }, { passive: true });

            document.addEventListener('keydown', function(event) {
                if (!viewer.classList.contains('is_open')) return;
                if (event.key === 'Escape') closeGallery();
                if (event.key === 'ArrowLeft') showGalleryImage(currentImageIndex - 1);
                if (event.key === 'ArrowRight') showGalleryImage(currentImageIndex + 1);
            });
        }

        document.addEventListener('click', function(event) {
            var likeButton = event.target.closest('.willow_like_button');
            if (!likeButton) return;
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
        });

        var commentSection = document.querySelector('.willow_comments');
        if (!commentSection) return;
        var commentForm = commentSection.querySelector('.willow_comment_form');
        var commentList = commentSection.querySelector('.willow_comment_list');
        if (!commentForm || !commentList) return;

        commentForm.addEventListener('submit', function(event) {
            event.preventDefault();
            var textarea = commentForm.querySelector('textarea[name="content"]');
            var submit = commentForm.querySelector('button[type="submit"]');
            if (!textarea || !textarea.value.trim()) {
                alert('댓글 내용을 입력해주세요.');
                return;
            }
            if (submit) submit.disabled = true;

            var formData = new FormData();
            formData.append('target_type', commentSection.getAttribute('data-target-type'));
            formData.append('target_id', commentSection.getAttribute('data-target-id'));
            formData.append('content', textarea.value);

            fetch('<?php echo G5_URL; ?>/willow/comment_update.php', {
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
                    alert(data.message || '댓글 등록에 실패했습니다.');
                    return;
                }
                var empty = commentList.querySelector('.willow_comment_empty');
                if (empty) empty.remove();
                var item = document.createElement('article');
                item.className = 'willow_comment_item';
                item.innerHTML = '<img class="willow_comment_avatar" alt=""><div class="willow_comment_content"><strong></strong><p></p><time></time></div>';
                item.querySelector('img').src = data.comment.avatar;
                item.querySelector('strong').textContent = data.comment.author;
                item.querySelector('p').textContent = data.comment.content;
                item.querySelector('time').textContent = data.comment.date;
                commentList.appendChild(item);
                textarea.value = '';
                var count = commentSection.querySelector('[data-comment-count]');
                if (count) count.textContent = data.count;
            }).catch(function() {
                alert('댓글 등록 중 오류가 발생했습니다.');
            }).finally(function() {
                if (submit) submit.disabled = false;
            });
        });
    })();
    </script>

    <?php include_once(G5_PATH.'/willow/bottom_nav.inc.php'); ?>
</main>

<?php
include_once(G5_PATH.'/tail.sub.php');
