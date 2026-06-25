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
?>

<script>document.body.classList.add('willow_inner_title_body');var willowAutoTitle=document.querySelector('.willow_page_title');if(willowAutoTitle)willowAutoTitle.style.display='none';var willowShellHeader=document.querySelector('#hd.willow_shell_header');if(willowShellHeader)willowShellHeader.style.display='none';</script>
<header class="willow_member_confirm_header">
    <a href="javascript:history.back();" aria-label="뒤로가기"></a>
    <h1>임시저장글</h1>
</header>

<main class="willow_list_page">
    <section class="willow_list_intro">
        <p>작성 중 저장한 글을 다시 이어서 작성할 수 있습니다.</p>
    </section>

    <div class="willow_simple_list">
        <?php if ($drafts) { ?>
            <?php foreach ($drafts as $draft) { ?>
            <?php
            $subject = $draft['wd_subject'] ? $draft['wd_subject'] : ($draft['wt_subject'] ? $draft['wt_subject'] : '제목 없는 임시글');
            $excerpt = cut_str(trim(preg_replace('/\s+/', ' ', strip_tags($draft['wd_content']))), 110, '...');
            $write_href = G5_URL.'/willow/write.php?wt_id='.(int) $draft['wt_id'].'&amp;wd_id='.(int) $draft['wd_id'];
            $topic_label = $draft['wd_topic_mode'] === 'free' ? '자유주제' : '오늘의 주제';
            ?>
            <article class="willow_simple_item">
                <a href="<?php echo $write_href; ?>">
                    <em><?php echo get_text($topic_label); ?><?php if (!empty($draft['wt_date'])) { ?> · <?php echo get_text(str_replace('-', '.', $draft['wt_date'])); ?><?php } ?></em>
                    <strong><?php echo get_text($subject); ?></strong>
                    <p><?php echo get_text($excerpt ? $excerpt : '저장된 내용이 없습니다.'); ?></p>
                    <span><?php echo get_text(substr($draft['wd_update_datetime'], 0, 16)); ?></span>
                </a>
            </article>
            <?php } ?>
        <?php } else { ?>
            <div class="willow_simple_empty">
                <strong>임시저장 글이 없습니다.</strong>
                <p>글 작성 중 임시저장을 누르면 이곳에 표시됩니다.</p>
                <a href="<?php echo G5_URL; ?>/willow/write.php">글쓰기</a>
            </div>
        <?php } ?>
    </div>
</main>

<?php
include_once(G5_THEME_MOBILE_PATH.'/tail.php');
