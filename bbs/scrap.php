<?php
include_once('./_common.php');
include_once(G5_PATH.'/willow/notification.lib.php');

if (!$is_member) {
    goto_url(G5_BBS_URL.'/login.php?url='.urlencode(G5_BBS_URL.'/scrap.php'));
}

$g5['title'] = '좋아요한 글';
$tables = willow_topic_tables();
$viewer_key = sql_escape_string(willow_interaction_viewer_key());
$mb_id = sql_escape_string($member['mb_id']);
$page = isset($page) ? max(1, (int) $page) : 1;
$rows = 20;
$from_record = ($page - 1) * $rows;
$like_where = " where viewer_key = '{$viewer_key}' or mb_id = '{$mb_id}' ";

$row = sql_fetch(" select count(*) as cnt from `{$tables['like']}` {$like_where} ");
$total_count = isset($row['cnt']) ? (int) $row['cnt'] : 0;
$total_page = $total_count ? ceil($total_count / $rows) : 1;

$liked_posts = array();
$result = sql_query(" select * from `{$tables['like']}` {$like_where} order by wl_datetime desc, wl_id desc limit {$from_record}, {$rows} ", false);
if ($result) {
    while ($like = sql_fetch_array($result)) {
        $post = willow_notification_post_meta($like['target_type'], (int) $like['target_id']);
        if (!$post) {
            continue;
        }

        $liked_posts[] = array(
            'type' => $like['target_type'] === 'topic' ? '오늘의 주제' : '게시글',
            'title' => get_text($post['title']),
            'excerpt' => get_text($post['excerpt']),
            'author' => get_text($post['author'] ? $post['author'] : '윌로우 회원'),
            'href' => $post['href'],
            'liked_at' => $like['wl_datetime'],
        );
    }
}

$write_pages = get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?page=');

$g5['body_script'] = isset($g5['body_script']) ? $g5['body_script'] : '';
if (strpos($g5['body_script'], 'class=') === false) {
    $g5['body_script'] .= ' class="willow_scrap_body"';
} else if (strpos($g5['body_script'], 'willow_scrap_body') === false) {
    $g5['body_script'] = preg_replace('/class=(["\'])(.*?)\1/', 'class=$1$2 willow_scrap_body$1', $g5['body_script'], 1);
}

add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/willow_mobile.css?ver='.G5_CSS_VER.'">', 10);
include_once('./_head.php');
?>

<main class="willow_list_page willow_liked_page">
    <section class="willow_list_intro">
        <h2>좋아요한 글</h2>
        <p>최근에 좋아요한 글을 최신순으로 모았습니다.</p>
    </section>

    <div class="willow_simple_list">
        <?php if ($liked_posts) { ?>
            <?php foreach ($liked_posts as $item) { ?>
            <article class="willow_simple_item">
                <a href="<?php echo $item['href']; ?>">
                    <em><?php echo $item['type']; ?></em>
                    <strong><?php echo $item['title']; ?></strong>
                    <p><?php echo $item['excerpt']; ?></p>
                    <span><?php echo $item['author']; ?> · 좋아요 <?php echo get_text(substr($item['liked_at'], 0, 16)); ?></span>
                </a>
            </article>
            <?php } ?>
        <?php } else { ?>
            <div class="willow_simple_empty">
                <strong>좋아요한 글이 없습니다.</strong>
                <p>마음에 드는 글의 하트 버튼을 누르면 이곳에 표시됩니다.</p>
                <a href="<?php echo G5_URL; ?>">글 둘러보기</a>
            </div>
        <?php } ?>
    </div>

    <?php if ($total_page > 1) { ?>
    <div class="willow_list_paging"><?php echo $write_pages; ?></div>
    <?php } ?>
</main>

<?php
include_once('./_tail.php');
