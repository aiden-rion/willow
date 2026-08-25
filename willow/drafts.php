<?php
include_once('./_common.php');
include_once('./content.lib.php');
include_once('./topic.lib.php');

if (!$is_member) {
    goto_url(G5_BBS_URL.'/login.php?url='.urlencode(G5_URL.'/willow/drafts.php'));
}

$g5['title'] = '임시저장 글';
include_once(G5_THEME_MOBILE_PATH.'/head.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/willow_mobile.css?ver='.G5_CSS_VER.'">', 10);

willow_topic_install();
$tables = willow_topic_tables();
$drafts = array();
$result = sql_query(" select d.*, t.wt_subject, t.wt_date
    from `{$tables['draft']}` d
    left join `{$tables['topic']}` t on t.wt_id = d.wt_id
    where d.mb_id = '".sql_escape_string($member['mb_id'])."'
    order by d.wd_update_datetime desc, d.wd_id desc ", false);
if ($result) {
    while ($row = sql_fetch_array($result)) {
        $drafts[] = $row;
    }
}

$draft_author_name = !empty($member['mb_nick']) ? $member['mb_nick'] : (!empty($member['mb_name']) ? $member['mb_name'] : '윌로우 회원');
$draft_author_avatar = willow_member_avatar($member);
?>

<script>document.body.classList.add('willow_inner_title_body');var willowAutoTitle=document.querySelector('.willow_page_title');if(willowAutoTitle)willowAutoTitle.style.display='none';var willowShellHeader=document.querySelector('#hd.willow_shell_header');if(willowShellHeader)willowShellHeader.style.display='none';</script>
<header class="willow_member_confirm_header">
    <a href="javascript:history.back();" aria-label="뒤로가기"></a>
    <h1>임시저장글</h1>
</header>

<main class="willow_app willow_drafts_page">
    <section class="willow_feed" aria-label="임시저장 글 목록">
        <?php if ($drafts) { ?>
            <?php foreach ($drafts as $draft) { ?>
            <?php
            $excerpt = willow_content_excerpt($draft['wd_content'], 180);
            $write_href = G5_URL.'/willow/write.php?wt_id='.(int) $draft['wt_id'].'&amp;wd_id='.(int) $draft['wd_id'];
            $topic_label = $draft['wd_topic_mode'] === 'free' ? '자유주제' : '오늘의 주제';
            $category_label = !empty($draft['wd_category']) ? willow_story_category_label($draft['wd_category']) : '';
            $draft_tags = !empty($draft['wd_tags']) ? array_filter(array_map('trim', explode(',', $draft['wd_tags']))) : array();
            $draft_images = !empty($draft['wd_images']) ? array_values(array_filter(explode('|', $draft['wd_images']))) : array();
            $draft_image = isset($draft_images[0]) ? $draft_images[0] : '';
            ?>
            <article class="willow_post_card willow_draft_card">
                <div class="willow_post_head">
                    <img src="<?php echo $draft_author_avatar; ?>" alt="">
                    <div>
                        <strong><?php echo get_text($draft_author_name); ?></strong>
                        <span><?php echo get_text(substr($draft['wd_update_datetime'], 0, 16)); ?></span>
                    </div>
                    <em>임시저장</em>
                </div>
                <a href="<?php echo $write_href; ?>">
                    <p><?php echo get_text($excerpt ? $excerpt : '저장된 내용이 없습니다.'); ?></p>
                    <?php if ($draft_image) { ?>
                    <img class="willow_post_image" src="<?php echo get_text($draft_image); ?>" alt="">
                    <?php } ?>
                </a>
                <div class="willow_post_meta">
                    <div class="willow_post_badges">
                        <span class="willow_post_badge"><?php echo get_text($topic_label); ?></span>
                        <?php if (!empty($draft['wt_date'])) { ?><span class="willow_post_badge"><?php echo get_text(str_replace('-', '.', $draft['wt_date'])); ?></span><?php } ?>
                        <?php if ($category_label !== '') { ?><span class="willow_post_badge"><?php echo get_text($category_label); ?></span><?php } ?>
                        <?php foreach (array_slice($draft_tags, 0, 2) as $tag) { ?><span class="willow_post_badge is_tag">#<?php echo get_text($tag); ?></span><?php } ?>
                    </div>
                    <a class="willow_draft_resume" href="<?php echo $write_href; ?>">이어쓰기</a>
                </div>
            </article>
            <?php } ?>
        <?php } else { ?>
            <div class="willow_simple_empty">
                <strong>임시저장 글이 없습니다.</strong>
                <p>글 작성 중 임시저장을 누르면 이곳에 표시됩니다.</p>
                <a href="<?php echo G5_URL; ?>/willow/write.php">글쓰기</a>
            </div>
        <?php } ?>
    </section>
</main>

<?php
include_once(G5_THEME_MOBILE_PATH.'/tail.php');
