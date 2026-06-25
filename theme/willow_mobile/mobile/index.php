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

$featured_posts = willow_get_popular_board_posts(2);

$recommended = willow_get_recommended_posts(3);
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

    <section class="willow_feed" aria-label="커뮤니티 피드">
        <?php foreach ($featured_posts as $post) { ?>
        <?php $is_post_owner = !empty($member['mb_id']) && !empty($post['mb_id']) && $member['mb_id'] === $post['mb_id']; ?>
        <article class="willow_post_card">
            <div class="willow_post_head">
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
            <a href="<?php echo $post['href']; ?>">
                <?php if (!empty($post['title'])) { ?>
                <h3><?php echo $post['title']; ?></h3>
                <?php } ?>
                <p><?php echo $post['body']; ?></p>
                <?php if (!empty($post['image'])) { ?>
                <img class="willow_post_image" src="<?php echo $post['image']; ?>" alt="">
                <?php } ?>
            </a>
            <div class="willow_post_meta">
                <button type="button" class="willow_like_button <?php echo !empty($post['liked']) ? 'is_liked' : ''; ?>" data-target-type="<?php echo $post['target_type']; ?>" data-target-id="<?php echo (int) $post['id']; ?>" aria-pressed="<?php echo !empty($post['liked']) ? 'true' : 'false'; ?>">
                    <img class="willow_meta_icon" src="<?php echo G5_IMG_URL; ?>/ico_heart<?php echo !empty($post['liked']) ? '_active' : ''; ?>.png" alt="" data-icon-heart data-icon-default="<?php echo G5_IMG_URL; ?>/ico_heart.png" data-icon-active="<?php echo G5_IMG_URL; ?>/ico_heart_active.png"><span data-like-count><?php echo (int) str_replace(',', '', $post['likes']) > 0 ? $post['likes'] : ''; ?></span>
                </button>
                <a class="willow_comment_link" href="<?php echo $post['href']; ?>#willow_comments">
                    <img class="willow_meta_icon" src="<?php echo G5_IMG_URL; ?>/ico_rep.png" alt=""><span data-comment-count><?php echo (int) str_replace(',', '', $post['comments']) > 0 ? $post['comments'] : ''; ?></span>
                </a>
                <div class="willow_post_badges">
                    <?php if (!empty($post['access']) && $post['access'] !== 'free') { ?>
                    <span class="willow_post_badge is_subscribe">구독</span>
                    <?php } ?>
                    <?php if (!empty($post['category'])) { ?>
                    <span class="willow_post_badge"><?php echo get_text($post['category']); ?></span>
                    <?php } ?>
                </div>
            </div>
        </article>
        <?php } ?>
    </section>

    <section class="willow_recommend">
        <h3>추천작가 글</h3>
        <?php foreach ($recommended as $item) { ?>
        <article class="<?php echo !empty($item['image']) ? 'has_thumb' : 'no_thumb'; ?>">
            <div>
                <a href="<?php echo $item['href']; ?>">
                    <h4><?php echo $item['title']; ?></h4>
                    <p><?php echo $item['excerpt']; ?></p>
                    <span><b><?php echo $item['author']; ?> 작가</b> · <?php echo $item['date']; ?></span>
                </a>
            </div>
            <?php if (!empty($item['image'])) { ?>
            <img src="<?php echo $item['image']; ?>" alt="">
            <?php } ?>
        </article>
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

    <?php willow_render_banner_area('home', 'willow_home_main_banner', '메인 배너'); ?>

    <script>
    (function() {
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
