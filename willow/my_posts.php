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
        $post = willow_board_post_to_feed($row);
        $items[] = array(
            'title' => $post['title'],
            'excerpt' => $post['excerpt'],
            'date' => $post['date'],
            'href' => $post['href'],
            'type' => $post['access'] === 'free' ? '무료글' : '구독 전용',
            'sort_datetime' => $post['sort_datetime'],
        );
    }
}

$result = sql_query(" select * from `{$topic_tables['post']}` where mb_id = '{$mb_id}' order by wp_datetime desc, wp_id desc ", false);
if ($result) {
    while ($row = sql_fetch_array($result)) {
        $items[] = array(
            'title' => get_text($row['wp_subject']),
            'excerpt' => cut_str(trim(preg_replace('/\s+/', ' ', strip_tags($row['wp_content']))), 110, '...'),
            'date' => willow_format_date($row['wp_datetime']),
            'href' => willow_topic_post_url($row),
            'type' => '오늘의 주제',
            'sort_datetime' => $row['wp_datetime'],
        );
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

<main class="willow_list_page">
    <section class="willow_list_intro">
        <p>일반 글과 오늘의 주제 참여글을 최신순으로 모았습니다.</p>
    </section>

    <div class="willow_simple_list">
        <?php if ($items) { ?>
            <?php foreach ($items as $item) { ?>
            <article class="willow_simple_item">
                <a href="<?php echo $item['href']; ?>">
                    <em><?php echo get_text($item['type']); ?></em>
                    <strong><?php echo $item['title']; ?></strong>
                    <p><?php echo get_text($item['excerpt']); ?></p>
                    <span><?php echo get_text($item['date']); ?></span>
                </a>
            </article>
            <?php } ?>
        <?php } else { ?>
            <div class="willow_simple_empty">
                <strong>작성한 글이 없습니다.</strong>
                <p>첫 글을 작성하고 윌로우에 이야기를 남겨보세요.</p>
                <a href="<?php echo G5_BBS_URL; ?>/write.php?bo_table=<?php echo willow_content_board(); ?>">글쓰기</a>
            </div>
        <?php } ?>
    </div>
</main>

<?php
include_once(G5_THEME_MOBILE_PATH.'/tail.php');
