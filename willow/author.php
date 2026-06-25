<?php
include_once('./_common.php');
include_once('./content.lib.php');
include_once('./topic.lib.php');
include_once('./notification.lib.php');

function willow_author_page_row($author_id, $author_name = '')
{
    global $g5;

    $author_id = trim($author_id);
    $author_name = trim($author_name);

    if ($author_id !== '') {
        $row = get_member($author_id);
        if (!empty($row['mb_id'])) {
            if ((int) $row['mb_level'] >= 3 || $row['mb_2'] === 'author') {
                return $row;
            }

            $fallback_name = $row['mb_nick'] ? $row['mb_nick'] : $row['mb_name'];
            if ($fallback_name !== '') {
                $name_sql = sql_escape_string($fallback_name);
                $author_row = sql_fetch(" select *
                    from {$g5['member_table']}
                    where mb_leave_date = ''
                        and mb_level < 10
                        and (mb_level >= 3 or mb_2 = 'author')
                        and (mb_nick = '{$name_sql}' or mb_name = '{$name_sql}')
                    order by mb_level desc, mb_datetime desc
                    limit 1 ", false);
                if (!empty($author_row['mb_id'])) {
                    return $author_row;
                }
            }

            return $row;
        }
    }

    if ($author_name !== '') {
        $name_sql = sql_escape_string($author_name);
        return sql_fetch(" select *
            from {$g5['member_table']}
            where mb_leave_date = ''
                and mb_level < 10
                and (mb_level >= 3 or mb_2 = 'author')
                and (mb_nick = '{$name_sql}' or mb_name = '{$name_sql}')
            order by mb_level desc, mb_datetime desc
            limit 1 ", false);
    }

    return array();
}

function willow_author_post_card($post)
{
    global $member;

    $like_count = (int) str_replace(',', '', (string) $post['likes']);
    $comment_count = (int) str_replace(',', '', (string) $post['comments']);
    $access_label = !empty($post['access']) ? willow_post_access_label($post['access']) : '무료';
    $is_post_owner = !empty($member['mb_id']) && !empty($post['mb_id']) && $member['mb_id'] === $post['mb_id'];
    ob_start();
    ?>
    <article class="willow_author_post_card">
        <div class="willow_author_post_head">
            <img src="<?php echo $post['avatar']; ?>" alt="">
            <div>
                <strong><?php echo $post['author']; ?></strong>
                <span><?php echo get_text($post['date']); ?> · <?php echo $access_label; ?></span>
            </div>
            <div class="willow_more">
                <button type="button" class="willow_more_button" aria-label="더보기"><span></span></button>
                <div class="willow_more_menu">
                    <?php if ($is_post_owner) { ?>
                    <button type="button">수정하기</button>
                    <button type="button">삭제하기</button>
                    <?php } else { ?>
                    <button type="button" class="willow_report_button" data-target-type="<?php echo $post['target_type']; ?>" data-target-id="<?php echo (int) $post['id']; ?>">신고하기</button>
                    <?php } ?>
                </div>
            </div>
        </div>
        <a class="willow_author_post_body" href="<?php echo $post['href']; ?>">
            <h2><?php echo $post['title']; ?></h2>
            <p><?php echo $post['excerpt']; ?></p>
            <?php if (!empty($post['image'])) { ?>
            <img class="willow_author_post_image" src="<?php echo $post['image']; ?>" alt="">
            <?php } ?>
        </a>
        <div class="willow_author_post_meta">
            <span><img src="<?php echo G5_IMG_URL; ?>/ico_heart<?php echo !empty($post['liked']) ? '_active' : ''; ?>.png" alt=""><?php echo $like_count > 0 ? number_format($like_count) : ''; ?></span>
            <a href="<?php echo $post['href']; ?>#willow_comments"><img src="<?php echo G5_IMG_URL; ?>/ico_rep.png" alt=""><?php echo $comment_count > 0 ? number_format($comment_count) : ''; ?></a>
            <b><?php echo $access_label; ?></b>
        </div>
    </article>
    <?php
    return ob_get_clean();
}

$author_id = isset($_GET['author']) ? trim($_GET['author']) : '';
$author_name = isset($_GET['name']) ? trim($_GET['name']) : '';
$author = willow_author_page_row($author_id, $author_name);

if (empty($author['mb_id'])) {
    alert('작가 정보를 찾을 수 없습니다.', G5_URL);
}

$author_name = $author['mb_nick'] ? $author['mb_nick'] : $author['mb_name'];
$author_id = $author['mb_id'];
$author_card = willow_author_card($author);
$author_tags = willow_member_tags($author);
$subscriber_count = willow_author_subscriber_count($author_id);
$post_count = willow_author_post_count($author_id, $author_name);
$subscriber_avatars = willow_author_recent_subscriber_avatars($author_id, 3);
$is_subscribed = willow_author_is_subscribed($author_id);
$is_self = !empty($member['mb_id']) && $member['mb_id'] === $author_id;
$page_limit = 6;

if (isset($_GET['ajax']) && $_GET['ajax'] === 'posts') {
    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
    $posts = willow_get_author_posts($author_id, $author_name, $offset, $page_limit + 1);
    $has_more = count($posts) > $page_limit;
    $posts = array_slice($posts, 0, $page_limit);

    $html = '';
    foreach ($posts as $post) {
        $html .= willow_author_post_card($post);
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array(
        'success' => true,
        'html' => $html,
        'count' => count($posts),
        'has_more' => $has_more,
    ));
    exit;
}

$posts = willow_get_author_posts($author_id, $author_name, 0, $page_limit + 1);
$has_more = count($posts) > $page_limit;
$posts = array_slice($posts, 0, $page_limit);
$subscribe_href = G5_URL.'/willow/subscribe.php?author='.urlencode($author_id);
$g5['title'] = '작가보기';

include_once(G5_PATH.'/head.sub.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/willow_content.css?ver='.G5_CSS_VER.'">', 10);
?>

<main class="willow_content_app willow_author_page" data-author-id="<?php echo get_text($author_id); ?>">
    <header class="willow_author_page_header">
        <a href="javascript:history.back();" aria-label="뒤로가기"><img src="<?php echo G5_IMG_URL; ?>/ico_back.png" alt=""></a>
        <h1>작가보기</h1>
    </header>

    <section class="willow_author_profile">
        <div class="willow_author_profile_top">
            <div>
                <h2><?php echo !empty($author_card['role_name_html']) ? $author_card['role_name_html'] : $author_card['name'].' 작가'; ?></h2>
                <p class="willow_author_profile_meta"><?php if ($author_tags) { ?> · <?php echo get_text(implode(' · ', array_slice($author_tags, 0, 2))); ?><?php } ?></p>
            </div>
            <img src="<?php echo $author_card['avatar']; ?>" alt="<?php echo $author_card['name']; ?> 프로필">
        </div>
        <p class="willow_author_profile_intro"><?php echo nl2br($author_card['profile']); ?></p>
        <div class="willow_author_subscribers">
            <?php if ($subscriber_avatars) { ?>
            <span>
                <?php foreach ($subscriber_avatars as $avatar) { ?>
                <img src="<?php echo $avatar; ?>" alt="">
                <?php } ?>
            </span>
            <?php } ?>
            <strong><?php echo number_format($subscriber_count); ?>명 구독중</strong>
            <em>등록글 <?php echo number_format($post_count); ?>개</em>
        </div>
        <?php if ($is_self) { ?>
        <a class="willow_author_subscribe is_subscribed" href="<?php echo G5_URL; ?>/willow/my_posts.php">내 글 보기</a>
        <?php } else if ($is_subscribed) { ?>
        <a class="willow_author_subscribe is_subscribed" href="<?php echo G5_URL; ?>/willow/subscribe.php?mode=my&amp;author=<?php echo urlencode($author_id); ?>">구독중</a>
        <?php } else { ?>
        <a class="willow_author_subscribe" href="<?php echo $subscribe_href; ?>">구독하기</a>
        <?php } ?>
    </section>

    <section class="willow_author_posts" aria-label="작가 최근 글">
        <div class="willow_author_posts_list">
            <?php foreach ($posts as $post) { echo willow_author_post_card($post); } ?>
        </div>
        <?php if (!$posts) { ?>
        <p class="willow_author_posts_empty">아직 등록된 글이 없습니다.</p>
        <?php } ?>
        <button type="button" class="willow_author_posts_more" data-offset="<?php echo count($posts); ?>" <?php echo $has_more ? '' : 'hidden'; ?>>더보기</button>
    </section>

    <script>
    (function() {
        document.addEventListener('click', function(event) {
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

            var moreButton = event.target.closest('.willow_more_button');
            document.querySelectorAll('.willow_more.is_open').forEach(function(menu) {
                if (!moreButton || !menu.contains(moreButton)) menu.classList.remove('is_open');
            });
            if (moreButton) {
                event.preventDefault();
                moreButton.closest('.willow_more').classList.toggle('is_open');
            }
        });

        var list = document.querySelector('.willow_author_posts_list');
        var more = document.querySelector('.willow_author_posts_more');
        var loading = false;

        function loadMore() {
            if (!more || more.hidden || loading) return;
            loading = true;
            more.textContent = '불러오는 중';
            var offset = parseInt(more.getAttribute('data-offset') || '0', 10);
            fetch('<?php echo G5_URL; ?>/willow/author.php?ajax=posts&author=<?php echo urlencode($author_id); ?>&offset=' + offset, {
                credentials: 'same-origin'
            }).then(function(response) {
                return response.json();
            }).then(function(data) {
                if (!data.success) return;
                if (data.html) {
                    list.insertAdjacentHTML('beforeend', data.html);
                }
                offset += parseInt(data.count || 0, 10);
                more.setAttribute('data-offset', offset);
                more.hidden = !data.has_more;
            }).finally(function() {
                loading = false;
                more.textContent = '더보기';
            });
        }

        if ('IntersectionObserver' in window && more) {
            var observer = new IntersectionObserver(function(entries) {
                if (entries.some(function(entry) { return entry.isIntersecting; })) {
                    loadMore();
                }
            }, { rootMargin: '160px 0px' });
            observer.observe(more);
        }

        if (more) {
            more.addEventListener('click', loadMore);
        }
    })();
    </script>
</main>

<?php
include_once(G5_PATH.'/tail.sub.php');
