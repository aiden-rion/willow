<?php
if (!defined("_GNUBOARD_")) exit;

add_stylesheet('<link rel="stylesheet" href="'.$new_skin_url.'/style.css">', 0);

$willow_notifications = isset($willow_notifications) && is_array($willow_notifications) ? $willow_notifications : array();
$willow_notification_icons = array(
    'comment' => '댓글',
    'like' => '좋아요',
    'subscriber' => '구독',
    'subscribed_post' => '새글',
    'topic' => '주제',
    'topic_post' => '참여',
);
?>

<script>document.body.classList.add('willow_inner_title_body');var willowAutoTitle=document.querySelector('.willow_page_title');if(willowAutoTitle)willowAutoTitle.style.display='none';var willowShellHeader=document.querySelector('#hd.willow_shell_header');if(willowShellHeader)willowShellHeader.style.display='none';</script>
<header class="willow_member_confirm_header">
    <a href="javascript:history.back();" aria-label="뒤로가기"></a>
    <h1>알림</h1>
</header>

<section class="willow_notification_page">
    <div class="willow_notification_head">
        <p>내 활동과 구독 작가의 새 소식을 모아 보여드려요.</p>
    </div>

    <!--
    알림 기준
    - 구독 중인 작가가 신규 글을 등록한 경우
    - 내가 작성한 글에 새로운 댓글이 등록된 경우
    - 작가 계정에 신규 구독자가 발생한 경우
    - 내가 작성한 글에 좋아요가 발생한 경우
    - 오늘의 주제가 새로 열렸거나 오늘의 주제에 새 참여글이 등록된 경우
    -->

    <?php if (!$is_member) { ?>
    <div class="willow_notification_empty">
        <strong>로그인이 필요합니다.</strong>
        <p>알림은 회원 활동을 기준으로 제공됩니다.</p>
        <a href="<?php echo G5_BBS_URL; ?>/login.php?url=<?php echo urlencode(G5_BBS_URL.'/new.php'); ?>">로그인하기</a>
    </div>
    <?php } else if ($willow_notifications) { ?>
    <div class="willow_notification_list" aria-label="알림 목록">
        <?php foreach ($willow_notifications as $notice) { ?>
        <?php
        $type = isset($notice['type']) ? $notice['type'] : '';
        $type_label = isset($willow_notification_icons[$type]) ? $willow_notification_icons[$type] : '알림';
        ?>
        <a class="willow_notification_item type_<?php echo get_text($type); ?>" href="<?php echo $notice['href']; ?>">
            <span class="willow_notification_badge"><?php echo $type_label; ?></span>
            <div>
                <strong><?php echo $notice['title']; ?></strong>
                <p><?php echo $notice['body']; ?></p>
                <?php if (!empty($notice['date_text'])) { ?><time><?php echo $notice['date_text']; ?></time><?php } ?>
            </div>
        </a>
        <?php } ?>
    </div>
    <?php } else { ?>
    <div class="willow_notification_empty">
        <strong>아직 알림이 없습니다.</strong>
        <p>댓글, 좋아요, 구독, 오늘의 주제 활동이 생기면 이곳에 표시됩니다.</p>
    </div>
    <?php } ?>
</section>
