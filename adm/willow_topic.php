<?php
$sub_menu = '700100';
require_once './_common.php';
include_once G5_PATH.'/willow/topic.lib.php';

auth_check_menu($auth, $sub_menu, 'r');
willow_topic_install();

$g5['title'] = '오늘의 주제';
$tables = willow_topic_tables();
$selected_wt_id = isset($_GET['wt_id']) ? (int) $_GET['wt_id'] : 0;
$mode = isset($_GET['mode']) && $_GET['mode'] === 'new' ? 'new' : '';

$topics = array();
$topic_result = sql_query(" select t.*,
        count(p.wp_id) as post_count,
        max(p.wp_datetime) as last_post_datetime
    from `{$tables['topic']}` t
    left join `{$tables['post']}` p on p.wt_id = t.wt_id
    group by t.wt_id
    order by t.wt_publish_datetime desc, t.wt_date desc, t.wt_id desc ", false);

if ($topic_result) {
    while ($row = sql_fetch_array($topic_result)) {
        $topics[] = $row;
        if (!$selected_wt_id && $mode !== 'new') {
            $selected_wt_id = (int) $row['wt_id'];
        }
    }
}

$selected_topic = array(
    'wt_id' => 0,
    'wt_subject' => '',
    'wt_date' => G5_TIME_YMD,
    'wt_publish_datetime' => G5_TIME_YMD.' 00:00:00',
    'wt_participants' => 0,
    'wt_description' => '',
    'wt_active' => 1,
);

if ($mode !== 'new' && $selected_wt_id) {
    $row = sql_fetch(" select * from `{$tables['topic']}` where wt_id = '{$selected_wt_id}' ", false);
    if (!empty($row['wt_id'])) {
        $selected_topic = $row;
    }
}

$selected_wt_id = (int) $selected_topic['wt_id'];
$topic_posts = array();

if ($selected_wt_id) {
    $post_result = sql_query(" select p.*,
            m.mb_nick,
            m.mb_name,
            (select count(*) from `{$tables['like']}` l where l.target_type = 'topic' and l.target_id = p.wp_id) as like_count,
            (select count(*) from `{$tables['comment']}` c where c.target_type = 'topic' and c.target_id = p.wp_id) as comment_count
        from `{$tables['post']}` p
        left join {$g5['member_table']} m on m.mb_id = p.mb_id
        where p.wt_id = '{$selected_wt_id}'
        order by p.wp_id desc ", false);

    if ($post_result) {
        while ($row = sql_fetch_array($post_result)) {
            $topic_posts[] = $row;
        }
    }
}

require_once './admin.head.php';
?>

<style>
.willow_admin_summary {display:flex;gap:8px;flex-wrap:wrap;margin:0 0 10px}
.willow_admin_summary span {display:inline-block;padding:7px 10px;border-radius:3px;background:#f3f5f8;color:#333}
.willow_admin_subject {font-weight:700}
.willow_admin_excerpt {max-width:520px;color:#555;line-height:1.45}
.willow_admin_empty {padding:28px 0;text-align:center;color:#777}
.willow_admin_active {color:#1976d2;font-weight:700}
.willow_admin_inactive {color:#999}
</style>

<div class="local_desc01 local_desc">
    <p>날짜별 오늘의 주제를 확인하고, 선택한 주제에 작성된 글 목록을 함께 확인할 수 있습니다.</p>
</div>

<div class="btn_fixed_top">
    <a href="./willow_topic.php?mode=new" class="btn btn_01">새 주제 등록</a>
</div>

<section>
    <h2 class="h2_frm">날짜별 주제 목록</h2>
    <div class="tbl_head01 tbl_wrap">
        <table>
            <caption>날짜별 오늘의 주제 목록</caption>
            <thead>
            <tr>
                <th scope="col">번호</th>
                <th scope="col">날짜</th>
                <th scope="col">노출 시작</th>
                <th scope="col">주제</th>
                <th scope="col">상태</th>
                <th scope="col">작성글</th>
                <th scope="col">표시 참여수</th>
                <th scope="col">최근 작성</th>
                <th scope="col">관리</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($topics) { ?>
                <?php foreach ($topics as $topic_row) { ?>
                <?php
                $is_selected = $selected_wt_id && (int) $topic_row['wt_id'] === $selected_wt_id;
                $topic_link = './willow_topic.php?wt_id='.(int) $topic_row['wt_id'];
                ?>
                <tr<?php echo $is_selected ? ' style="background:#fffbe8"' : ''; ?>>
                    <td class="td_num"><?php echo (int) $topic_row['wt_id']; ?></td>
                    <td class="td_datetime"><?php echo get_text($topic_row['wt_date']); ?></td>
                    <td class="td_datetime"><?php echo get_text(willow_topic_publish_datetime($topic_row)); ?></td>
                    <td class="td_left">
                        <a class="willow_admin_subject" href="<?php echo $topic_link; ?>"><?php echo get_text($topic_row['wt_subject']); ?></a>
                    </td>
                    <td class="td_mng">
                        <?php if ((int) $topic_row['wt_active']) { ?>
                        <span class="willow_admin_active">노출</span>
                        <?php } else { ?>
                        <span class="willow_admin_inactive">숨김</span>
                        <?php } ?>
                    </td>
                    <td class="td_num"><?php echo number_format((int) $topic_row['post_count']); ?></td>
                    <td class="td_num"><?php echo number_format((int) $topic_row['wt_participants']); ?></td>
                    <td class="td_datetime"><?php echo $topic_row['last_post_datetime'] ? get_text($topic_row['last_post_datetime']) : '-'; ?></td>
                    <td class="td_mng">
                        <a href="<?php echo $topic_link; ?>" class="btn btn_03">보기</a>
                    </td>
                </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="9" class="willow_admin_empty">등록된 오늘의 주제가 없습니다.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</section>

<form name="fwillowtopic" method="post" action="./willow_topic_update.php">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
    <input type="hidden" name="wt_id" value="<?php echo (int) $selected_topic['wt_id']; ?>">

    <h2 class="h2_frm"><?php echo $selected_topic['wt_id'] ? '선택 주제 수정' : '새 주제 등록'; ?></h2>
    <div class="tbl_frm01 tbl_wrap">
        <table>
            <caption><?php echo $g5['title']; ?></caption>
            <tbody>
            <tr>
                <th scope="row"><label for="wt_subject">오늘의 주제</label></th>
                <td><input type="text" name="wt_subject" id="wt_subject" value="<?php echo get_text($selected_topic['wt_subject']); ?>" required class="required frm_input" size="80" maxlength="255"></td>
            </tr>
            <tr>
                <th scope="row"><label for="wt_date">노출 날짜</label></th>
                <td><input type="date" name="wt_date" id="wt_date" value="<?php echo get_text($selected_topic['wt_date']); ?>" required class="required frm_input"></td>
            </tr>
            <tr>
                <th scope="row">노출 시작일시</th>
                <td>
                    <?php $publish_datetime = willow_topic_publish_datetime($selected_topic); ?>
                    <input type="date" name="wt_publish_date" id="wt_publish_date" value="<?php echo get_text(substr($publish_datetime, 0, 10)); ?>" required class="required frm_input">
                    <input type="time" name="wt_publish_time" id="wt_publish_time" value="<?php echo get_text(substr($publish_datetime, 11, 5)); ?>" required class="required frm_input">
                    <span class="frm_info">설정한 일시 이후부터 메인과 오늘의 주제 페이지에 노출됩니다.</span>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wt_participants">표시 참여자 수</label></th>
                <td>
                    <input type="number" name="wt_participants" id="wt_participants" value="<?php echo (int) $selected_topic['wt_participants']; ?>" min="0" class="frm_input">
                    <?php if ($selected_wt_id) { ?>
                    <span class="frm_info">실제 작성글 수: <?php echo number_format(count($topic_posts)); ?>개</span>
                    <?php } ?>
                </td>
            </tr>
            <tr>
                <th scope="row">노출 상태</th>
                <td>
                    <input type="checkbox" name="wt_active" id="wt_active" value="1" <?php echo !empty($selected_topic['wt_active']) ? 'checked' : ''; ?>>
                    <label for="wt_active">사용</label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wt_description">작성 안내</label></th>
                <td><textarea name="wt_description" id="wt_description" rows="5"><?php echo get_text($selected_topic['wt_description'], 0); ?></textarea></td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="btn_confirm01 btn_confirm">
        <input type="submit" value="<?php echo $selected_topic['wt_id'] ? '수정 저장' : '등록'; ?>" class="btn_submit btn">
    </div>
</form>

<section>
    <h2 class="h2_frm">선택 주제 작성글</h2>
    <?php if ($selected_wt_id) { ?>
    <div class="willow_admin_summary">
        <span>날짜: <?php echo get_text($selected_topic['wt_date']); ?></span>
        <span>노출 시작: <?php echo get_text(willow_topic_publish_datetime($selected_topic)); ?></span>
        <span>주제: <?php echo get_text($selected_topic['wt_subject']); ?></span>
        <span>작성글: <?php echo number_format(count($topic_posts)); ?>개</span>
    </div>
    <?php } ?>
    <div class="tbl_head01 tbl_wrap">
        <table>
            <caption>선택 주제 작성글</caption>
            <thead>
            <tr>
                <th scope="col">글번호</th>
                <th scope="col">작성자</th>
                <th scope="col">제목</th>
                <th scope="col">내용</th>
                <th scope="col">좋아요</th>
                <th scope="col">댓글</th>
                <th scope="col">작성일</th>
                <th scope="col">관리</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($topic_posts) { ?>
                <?php foreach ($topic_posts as $post) { ?>
                <?php
                $author_name = $post['wp_author'] ? $post['wp_author'] : ($post['mb_nick'] ? $post['mb_nick'] : ($post['mb_name'] ? $post['mb_name'] : '윌로우 회원'));
                $excerpt = cut_str(trim(preg_replace('/\s+/', ' ', strip_tags($post['wp_content']))), 90, '...');
                $post_url = G5_URL.'/willow/post.php?wp_id='.(int) $post['wp_id'];
                ?>
                <tr>
                    <td class="td_num"><?php echo (int) $post['wp_id']; ?></td>
                    <td class="td_name"><?php echo get_text($author_name); ?></td>
                    <td class="td_left"><a href="<?php echo $post_url; ?>" target="_blank"><?php echo get_text($post['wp_subject']); ?></a></td>
                    <td class="td_left"><p class="willow_admin_excerpt"><?php echo get_text($excerpt); ?></p></td>
                    <td class="td_num"><?php echo number_format((int) $post['like_count']); ?></td>
                    <td class="td_num"><?php echo number_format((int) $post['comment_count']); ?></td>
                    <td class="td_datetime"><?php echo get_text($post['wp_datetime']); ?></td>
                    <td class="td_mng"><a href="<?php echo $post_url; ?>" target="_blank" class="btn btn_03">보기</a></td>
                </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="8" class="willow_admin_empty"><?php echo $selected_wt_id ? '선택한 주제에 작성된 글이 없습니다.' : '주제를 선택하면 작성글을 확인할 수 있습니다.'; ?></td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</section>

<?php
require_once './admin.tail.php';
