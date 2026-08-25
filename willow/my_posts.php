<?php
include_once('./_common.php');
include_once('./content.lib.php');
include_once('./topic.lib.php');

if (!$is_member) {
    goto_url(G5_BBS_URL.'/login.php?url='.urlencode(G5_URL.'/willow/my_posts.php'));
}

$g5['title'] = '내가 쓴 글';
include_once(G5_THEME_MOBILE_PATH.'/head.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/willow_mobile.css?ver='.G5_CSS_VER.'">', 10);

$items = array();
$mb_id = sql_escape_string($member['mb_id']);
$board_table = willow_content_table();
$topic_tables = willow_topic_tables();

$result = sql_query(" select * from `{$board_table}` where wr_is_comment = 0 and mb_id = '{$mb_id}' order by wr_datetime desc, wr_id desc ", false);
if ($result) {
    while ($row = sql_fetch_array($result)) {
        $items[] = willow_board_post_to_feed($row);
    }
}

$result = sql_query(" select p.*, t.wt_subject
    from `{$topic_tables['post']}` p
    left join `{$topic_tables['topic']}` t on t.wt_id = p.wt_id
    where p.mb_id = '{$mb_id}'
    order by p.wp_datetime desc, p.wp_id desc ", false);
if ($result) {
    while ($row = sql_fetch_array($result)) {
        $items[] = willow_topic_post_to_feed($row);
    }
}

usort($items, function ($a, $b) {
    return strcmp($b['sort_datetime'], $a['sort_datetime']);
});
?>

<script>document.body.classList.add('willow_inner_title_body');var willowAutoTitle=document.querySelector('.willow_page_title');if(willowAutoTitle)willowAutoTitle.style.display='none';var willowShellHeader=document.querySelector('#hd.willow_shell_header');if(willowShellHeader)willowShellHeader.style.display='none';</script>
<header class="willow_member_confirm_header">
    <a href="javascript:history.back();" aria-label="뒤로가기"></a>
    <h1>내가 쓴 글</h1>
</header>

<main class="willow_app willow_my_posts_page">
    <section class="willow_feed" aria-label="내가 쓴 글 목록">
        <?php if ($items) { ?>
            <?php foreach ($items as $item) { ?>
            <?php echo willow_render_post_card($item); ?>
            <?php } ?>
        <?php } else { ?>
            <div class="willow_simple_empty">
                <strong>작성한 글이 없습니다.</strong>
                <p>첫 글을 작성하고 윌로우에 이야기를 남겨보세요.</p>
                <a href="<?php echo G5_URL; ?>/willow/write.php">글쓰기</a>
            </div>
        <?php } ?>
    </section>
</main>

<script>
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
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
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
</script>

<?php
include_once(G5_THEME_MOBILE_PATH.'/tail.php');
