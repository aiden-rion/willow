<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/willow_mobile.css?ver='.G5_CSS_VER.'">', 0);
add_stylesheet('<link rel="stylesheet" href="'.$search_skin_url.'/style.css">', 10);
include_once(G5_PATH.'/willow/content.lib.php');

$willow_keyword = trim($text_stx);
$willow_has_query = $willow_keyword !== '';
$willow_tags = willow_get_categories(true);
$willow_story_categories = function_exists('willow_story_categories') ? willow_story_categories() : array();
$willow_primary_tags = array_values(array_filter($willow_tags, function($tag) {
    return !empty($tag['keyword']);
}));
$willow_primary_tags = array_slice($willow_primary_tags, 0, 10);
$willow_recent_searches = willow_get_recent_searches(6);
$willow_suggestion_seeds = array('작가명', '정신건강', '정착상식');
$willow_suggestions = willow_get_search_suggestions($willow_suggestion_seeds, 60);
$willow_suggestion_fallbacks = array_slice($willow_suggestions, 0, 4);
$willow_post_results = $willow_has_query ? willow_get_search_posts(6, $willow_keyword) : array();
$willow_author_results = $willow_has_query ? willow_get_authors($willow_keyword, 3) : array();
?>

<script>document.body.classList.add('willow_search_body');</script>

<main class="willow_search_page<?php echo $willow_has_query ? ' is-result' : ''; ?>">
    <form name="fsearch" class="willow_search_form" onsubmit="return fsearch_submit(this);" method="get" autocomplete="off">
        <input type="hidden" name="sfl" value="wr_subject||wr_content">
        <input type="hidden" name="sop" value="or">
        <input type="hidden" name="srows" value="<?php echo $srows ?>">
        <label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
        <span class="willow_search_icon" aria-hidden="true"></span>
        <input type="search" name="stx" value="<?php echo get_text($willow_keyword); ?>" id="stx" maxlength="20" placeholder="제목 또는 작가명을 입력하세요">
        <button type="button" class="willow_search_clear" aria-label="검색어 지우기">×</button>
    </form>

    <?php if (!$willow_has_query) { ?>
    <section class="willow_search_idle" data-search-idle>
        <div class="willow_search_tags" aria-label="추천 검색어">
            <?php foreach ($willow_primary_tags as $tag) { ?>
            <a href="<?php echo $tag['href']; ?>"><?php echo $tag['label']; ?></a>
            <?php } ?>
            <button type="button" data-search-tag-toggle aria-expanded="false">전체보기 <i class="fa fa-angle-down" aria-hidden="true"></i></button>
        </div>
        <section class="willow_search_tag_all" data-search-tag-panel hidden>
            <?php foreach ($willow_story_categories as $category) { ?>
            <article class="willow_search_tag_group">
                <h2><?php echo get_text($category['label']); ?></h2>
                <div>
                    <?php foreach ($category['tags'] as $tag) { ?>
                    <a href="<?php echo G5_BBS_URL; ?>/search.php?sfl=wr_subject%7C%7Cwr_content&amp;sop=or&amp;stx=<?php echo urlencode($tag); ?>">#<?php echo get_text($tag); ?></a>
                    <?php } ?>
                </div>
            </article>
            <?php } ?>
        </section>

        <section class="willow_recent_search">
            <h2>최근 검색어</h2>
            <p>* 최근검색어는 1년동안 유지됩니다.</p>
            <ul data-recent-list>
                <?php foreach ($willow_recent_searches as $recent) { ?>
                <li data-recent-id="<?php echo (int) $recent['id']; ?>">
                    <a href="<?php echo G5_BBS_URL; ?>/search.php?sfl=wr_subject%7C%7Cwr_content&amp;sop=or&amp;stx=<?php echo urlencode($recent['word']); ?>"><?php echo get_text($recent['word']); ?></a>
                    <time><?php echo get_text($recent['date']); ?></time>
                    <button type="button" aria-label="<?php echo get_text($recent['word']); ?> 삭제">×</button>
                </li>
                <?php } ?>
            </ul>
            <button type="button" class="willow_recent_clear" data-recent-clear>검색어 전체삭제 <i class="fa fa-angle-right" aria-hidden="true"></i></button>
        </section>
    </section>

    <section class="willow_search_suggest" data-search-suggest>
        <h2 class="sound_only">추천 검색어</h2>
        <ul data-suggest-list>
            <?php foreach ($willow_suggestion_fallbacks as $suggestion) { ?>
            <li><a href="<?php echo G5_BBS_URL; ?>/search.php?sfl=wr_subject%7C%7Cwr_content&amp;sop=or&amp;stx=<?php echo urlencode($suggestion['keyword']); ?>"><?php echo $suggestion['label']; ?></a></li>
            <?php } ?>
        </ul>
        <p class="willow_search_suggest_empty" data-suggest-empty>관련 검색어가 없습니다.</p>
    </section>
    <?php } else { ?>
    <section class="willow_search_results">
        <section class="willow_result_section willow_post_results">
            <h2>글감 검색</h2>
            <?php if (!empty($willow_post_results)) { ?>
            <div class="willow_post_list">
                <?php foreach ($willow_post_results as $post) { ?>
                <article class="willow_search_post">
                    <a class="willow_search_post_link" href="<?php echo $post['href']; ?>">
                        <div class="willow_post_head">
                            <span class="willow_post_avatar"><img src="<?php echo $post['avatar']; ?>" alt=""></span>
                            <span>
                                <strong><?php echo $post['author']; ?></strong>
                                <time><?php echo $post['date']; ?></time>
                            </span>
                            <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
                        </div>
                        <p><?php echo $post['excerpt']; ?></p>
                        <div class="willow_post_meta">
                            <span><img class="willow_meta_icon" src="<?php echo G5_IMG_URL; ?>/ico_heart.png" alt=""> <?php echo (int) str_replace(',', '', $post['likes']) > 0 ? $post['likes'] : ''; ?></span>
                            <span><img class="willow_meta_icon" src="<?php echo G5_IMG_URL; ?>/ico_rep.png" alt=""> <?php echo (int) str_replace(',', '', $post['comments']) > 0 ? $post['comments'] : ''; ?></span>
                            <em>구독</em>
                            <em>스토리</em>
                        </div>
                    </a>
                </article>
                <?php } ?>
            </div>
            <?php } else { ?>
            <p class="willow_search_empty">검색된 글감이 없습니다.</p>
            <?php } ?>
        </section>

        <section class="willow_result_section willow_author_results">
            <h2>작가 검색</h2>
            <?php foreach ($willow_author_results as $author_row) {
                $author = willow_author_card($author_row);
            ?>
            <article class="willow_author_card">
                <a href="<?php echo $author['href']; ?>">
                    <img src="<?php echo $author['avatar']; ?>" alt="">
                    <span>
                        <strong><?php echo !empty($author['role_name_html']) ? $author['role_name_html'] : $author['name'].' 작가'; ?></strong>
                        <small><?php echo get_text(cut_str($author['profile'], 42)); ?></small>
                        <small>작성글 : <b><?php echo number_format($author['post_count']); ?></b> 구독자 : <b><?php echo number_format($author['subscriber_count']); ?></b></small>
                        <?php foreach (array_slice($author['tags'], 0, 3) as $tag) { ?><em><?php echo get_text($tag); ?></em><?php } ?>
                    </span>
                </a>
            </article>
            <?php } ?>
            <?php if (empty($willow_author_results)) { ?><p class="willow_search_empty">검색된 작가가 없습니다.</p><?php } ?>
        </section>

        <section class="willow_search_banner">
            <img src="<?php echo G5_IMG_URL; ?>/banner001.png" alt="윌로우 에서 작가로 활동하세요! 함께하실 작가분들을 언제나 모집합니다.">
        </section>
    </section>
    <?php echo $write_pages; ?>
    <?php } ?>
</main>

<script>
var willowSearchSuggestions = <?php echo json_encode($willow_suggestions, JSON_UNESCAPED_UNICODE); ?>;

function fsearch_submit(f)
{
    var stx = f.stx.value.trim();
    if (!stx.length) {
        f.stx.focus();
        return false;
    }
    f.stx.value = stx;
    f.action = "";
    return true;
}

document.addEventListener('DOMContentLoaded', function () {
    var page = document.querySelector('.willow_search_page');
    var input = document.getElementById('stx');
    var clearBtn = document.querySelector('.willow_search_clear');
    var idle = document.querySelector('[data-search-idle]');
    var suggest = document.querySelector('[data-search-suggest]');
    var suggestList = document.querySelector('[data-suggest-list]');
    var suggestEmpty = document.querySelector('[data-suggest-empty]');
    var recentList = document.querySelector('[data-recent-list]');
    var recentClear = document.querySelector('[data-recent-clear]');
    var tagToggle = document.querySelector('[data-search-tag-toggle]');
    var tagPanel = document.querySelector('[data-search-tag-panel]');

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (char) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char];
        });
    }

    function normalizeSearch(value) {
        return String(value || '').replace(/^#/, '').replace(/\s+/g, '').toLowerCase();
    }

    function suggestionMatches(item, word) {
        var query = normalizeSearch(word);
        var label = normalizeSearch(item.label);
        var keyword = normalizeSearch(item.keyword);

        return label.indexOf(query) !== -1 || keyword.indexOf(query) !== -1 || query.indexOf(keyword) !== -1;
    }

    function renderSuggestions() {
        if (!suggestList || !suggestEmpty || !input) {
            return;
        }

        var word = input.value.trim();
        var items = word.length
            ? willowSearchSuggestions.filter(function (item) { return suggestionMatches(item, word); }).slice(0, 8)
            : willowSearchSuggestions.slice(0, 4);

        suggestList.innerHTML = items.map(function (item) {
            var href = '<?php echo G5_BBS_URL; ?>/search.php?sfl=wr_subject%7C%7Cwr_content&sop=or&stx=' + encodeURIComponent(item.keyword);
            return '<li><a href="' + href + '">' + escapeHtml(item.label) + '</a></li>';
        }).join('');
        suggestEmpty.style.display = items.length ? 'none' : 'block';
    }

    function syncTypingState() {
        if (!page || page.classList.contains('is-result') || !idle || !suggest) {
            return;
        }
        renderSuggestions();
        page.classList.toggle('is-typing', input.value.trim().length > 0);
    }

    input.addEventListener('input', syncTypingState);
    clearBtn.addEventListener('click', function () {
        input.value = '';
        input.focus();
        syncTypingState();
    });

    if (recentList) {
        recentList.addEventListener('click', function (event) {
            if (event.target.tagName !== 'BUTTON') {
                return;
            }
            var item = event.target.closest('[data-recent-id]');
            var formData = new FormData();
            formData.append('action', 'delete');
            formData.append('recent_id', item ? item.getAttribute('data-recent-id') : '');
            fetch('<?php echo G5_URL; ?>/willow/search_recent_update.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
                credentials: 'same-origin'
            });
            if (item) item.remove();
        });
    }

    if (recentClear) {
        recentClear.addEventListener('click', function () {
            var formData = new FormData();
            formData.append('action', 'clear');
            fetch('<?php echo G5_URL; ?>/willow/search_recent_update.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
                credentials: 'same-origin'
            });
            if (recentList) {
                recentList.innerHTML = '';
            }
        });
    }

    if (tagToggle && tagPanel) {
        tagToggle.addEventListener('click', function () {
            var isExpanded = tagToggle.getAttribute('aria-expanded') === 'true';
            tagToggle.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
            tagPanel.hidden = isExpanded;
        });
    }

    syncTypingState();
});
</script>
