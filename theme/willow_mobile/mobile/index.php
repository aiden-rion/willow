<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if(G5_COMMUNITY_USE === false) {
    include_once(G5_THEME_MSHOP_PATH.'/index.php');
    return;
}

$g5['title'] = 'WILLOW';

include_once(G5_THEME_MOBILE_PATH.'/head.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/willow_mobile.css?ver='.G5_CSS_VER.'">', 10);
include_once(G5_PATH.'/willow/topic.lib.php');
include_once(G5_PATH.'/willow/content.lib.php');

$willow_topic = willow_get_topic();
$has_visible_topic = !empty($willow_topic['wt_id']);
$willow_topic_participant_count = $has_visible_topic ? willow_topic_participant_count((int) $willow_topic['wt_id']) : 0;
$willow_topic_recent_participants = $has_visible_topic ? willow_topic_recent_participants((int) $willow_topic['wt_id'], 3) : array();

$login_href = G5_BBS_URL.'/login.php?url='.urlencode(G5_URL);
$today_href = G5_URL.'/willow/today.php';
$write_href = $has_visible_topic ? willow_topic_write_url($willow_topic) : '#';

$chips = willow_get_categories(true);

$featured_posts = willow_get_personalized_feed(0, 3);
$recommended_authors = willow_get_recommended_authors();
?>

<main id="willow_app" class="willow_app willow_user_home">
    <a class="willow_topic_card" href="<?php echo $today_href; ?>" aria-label="오늘의 주제 페이지로 이동">
        <strong>오늘의 주제</strong>
        <?php if ($has_visible_topic) { ?>
        <span class="willow_topic_date"><?php echo willow_topic_date($willow_topic['wt_date']); ?> <i class="fa fa-angle-down" aria-hidden="true"></i></span>
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
        <?php } else { ?>
        <span class="willow_topic_date"><?php echo willow_topic_date(G5_TIME_YMD); ?></span>
        <h2>아직 공개된 오늘의 주제가 없습니다.</h2>
        <div class="willow_topic_participants">
            <p>새 주제가 열리면 이곳에서 확인할 수 있어요</p>
        </div>
        <?php } ?>
    </a>

    <nav class="willow_search_tags willow_chip_nav" aria-label="주제 카테고리">
        <?php foreach ($chips as $chip) { ?>
        <a href="<?php echo $chip['href']; ?>"><?php echo $chip['label']; ?><?php if ($chip['keyword'] === '') { ?> <i class="fa fa-angle-right" aria-hidden="true"></i><?php } ?></a>
        <?php } ?>
    </nav>

    <section class="willow_feed willow_home_initial_feed" aria-label="맞춤 글">
        <?php foreach ($featured_posts as $post) { ?>
        <?php echo willow_render_post_card($post); ?>
        <?php } ?>
    </section>

    <?php if ($recommended_authors) { ?>
    <section class="willow_author_recommend" aria-label="추천 작가">
        <div class="willow_author_recommend_head">
            <h3>작가추천</h3>
            <p>최근 글의 댓글과 좋아요 반응이 좋은 작가들이에요</p>
        </div>
        <div class="willow_author_recommend_grid">
            <?php foreach ($recommended_authors as $author) { ?>
            <a class="willow_author_recommend_card" href="<?php echo $author['href']; ?>">
                <img class="willow_author_recommend_avatar" src="<?php echo $author['avatar']; ?>" alt="">
                <div class="willow_author_recommend_info">
                    <strong><?php echo !empty($author['role_name_html']) ? $author['role_name_html'] : $author['name'].' 작가'; ?></strong>
                    <p><?php echo $author['profile']; ?></p>
                    <span>구독자 <?php echo number_format($author['subscriber_count']); ?>명</span>
                    <em>좋아요 <?php echo number_format($author['likes']); ?> · 댓글 <?php echo number_format($author['comments']); ?> · 최근글 <?php echo number_format($author['post_count']); ?></em>
                </div>
            </a>
            <?php } ?>
        </div>
    </section>
    <?php } ?>

    <section id="willow_home_feed_more" class="willow_feed willow_home_more_feed" aria-label="더 많은 맞춤 글"></section>
    <div class="willow_feed_state" data-feed-state>
        <span data-feed-loading hidden>글을 불러오는 중입니다.</span>
        <span data-feed-end hidden>모든 글을 확인했습니다.</span>
    </div>

    <?php willow_render_banner_area('home', 'willow_home_main_banner', '메인 배너'); ?>

    <script>
    (function() {
        var feedOffset = <?php echo count($featured_posts); ?>;
        var feedLimit = 6;
        var feedLoading = false;
        var feedEnded = false;
        var feedMore = document.getElementById('willow_home_feed_more');
        var loadingText = document.querySelector('[data-feed-loading]');
        var endText = document.querySelector('[data-feed-end]');

        function setFeedState() {
            if (loadingText) loadingText.hidden = !feedLoading;
            if (endText) endText.hidden = !feedEnded;
        }

        function loadMoreFeed() {
            if (!feedMore || feedLoading || feedEnded) return;
            feedLoading = true;
            setFeedState();
            fetch('<?php echo G5_URL; ?>/willow/home_feed.php?offset=' + encodeURIComponent(feedOffset) + '&limit=' + encodeURIComponent(feedLimit), {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            }).then(function(response) {
                return response.json();
            }).then(function(data) {
                if (!data.success) {
                    feedEnded = true;
                    return;
                }
                if (data.html) {
                    feedMore.insertAdjacentHTML('beforeend', data.html);
                }
                feedOffset += parseInt(data.count || 0, 10);
                feedEnded = !data.has_more || parseInt(data.count || 0, 10) < feedLimit;
            }).catch(function() {
                feedEnded = true;
            }).finally(function() {
                feedLoading = false;
                setFeedState();
            });
        }

        if ('IntersectionObserver' in window && feedMore) {
            var sentinel = document.createElement('div');
            sentinel.className = 'willow_feed_sentinel';
            feedMore.after(sentinel);
            var observer = new IntersectionObserver(function(entries) {
                if (entries.some(function(entry) { return entry.isIntersecting; })) {
                    loadMoreFeed();
                }
            }, { rootMargin: '420px 0px' });
            observer.observe(sentinel);
        } else {
            window.addEventListener('scroll', function() {
                if (feedEnded || feedLoading) return;
                if (window.innerHeight + window.scrollY > document.documentElement.scrollHeight - 520) {
                    loadMoreFeed();
                }
            }, { passive: true });
        }

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

        document.querySelectorAll('.willow_writer_banner').forEach(function(banner) {
            var track = banner.querySelector('.willow_writer_banner_track');
            var count = banner.querySelector('.willow_writer_banner_count');
            var slides = banner.querySelectorAll('.willow_writer_banner_slide');
            if (!track || !count || !slides.length) return;
            var update = function() {
                var index = Math.round(track.scrollLeft / Math.max(1, track.clientWidth));
                count.textContent = String(index + 1).padStart(2, '0') + ' / ' + String(slides.length).padStart(2, '0');
            };
            track.addEventListener('scroll', update, { passive: true });
            update();
        });
    })();
    </script>

    <footer class="willow_footer">
        <button type="button" class="willow_footer_toggle" aria-expanded="false" aria-controls="willow_footer_panel">
            <img src="<?php echo G5_IMG_URL; ?>/m_logo.png" alt="WILLOW">
            <i class="fa fa-angle-down" aria-hidden="true"></i>
        </button>
        <div id="willow_footer_panel" class="willow_footer_panel" hidden>
            <nav>
                <a href="<?php echo get_pretty_url('content', 'company'); ?>">회사소개</a>
                <a href="<?php echo get_pretty_url('content', 'provision'); ?>">이용약관</a>
                <a href="<?php echo get_pretty_url('content', 'privacy'); ?>">개인정보처리방침</a>
                <a href="#">영상정보처리방침</a>
            </nav>
            <p><b>대표이사 :</b> 박대현 <b>E-mail :</b> admin@woorion.org</p>
            <p><b>개인정보관리책임자 :</b> 홍길동 <b>법인등록번호 :</b> 678-82-00212</p>
            <p><b>주소 :</b> 서울특별시 양천구 목동서로 159-1 CBS 방송국 14층 우리온</p>
        </div>
    </footer>

    <script>
    (function() {
        var footer = document.querySelector('.willow_footer');
        if (!footer) return;
        var toggle = footer.querySelector('.willow_footer_toggle');
        var panel = footer.querySelector('.willow_footer_panel');
        if (!toggle || !panel) return;

        toggle.addEventListener('click', function() {
            var isOpen = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            panel.hidden = isOpen;
            footer.classList.toggle('is_open', !isOpen);
        });
    })();
    </script>
</main>

<?php
include_once(G5_THEME_MOBILE_PATH.'/tail.php');
