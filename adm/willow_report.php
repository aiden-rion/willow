<?php
$sub_menu = '700900';
require_once './_common.php';
include_once G5_PATH.'/willow/content.lib.php';

auth_check_menu($auth, $sub_menu, 'r');
willow_report_install();
if (!willow_moderation_install()) alert('게시중단 관리 테이블을 생성할 수 없습니다.');

$g5['title'] = '신고관리';
$table = willow_report_table();
$categories = willow_report_categories();
$statuses = array(
    'pending' => '접수',
    'reviewing' => '검토중',
    'done' => '처리완료',
    'rejected' => '반려',
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_demo();
    auth_check_menu($auth, $sub_menu, 'w');
    check_admin_token();

    if (isset($_POST['moderate'])) {
        $parts = explode(':', (string) $_POST['moderate']);
        $report_id = isset($parts[0]) ? (int) $parts[0] : 0;
        $action = isset($parts[1]) ? $parts[1] : '';
        if (!in_array($action, array('block', 'restore'), true)) alert('잘못된 요청입니다.');
        $report = sql_fetch("SELECT * FROM `{$table}` WHERE wrp_id = {$report_id}");
        if (!$report || !in_array($report['wrp_target_type'], array('board', 'topic'), true)) alert('신고 대상을 찾을 수 없습니다.');
        $type = $report['wrp_target_type'];
        $id = (int) $report['wrp_target_id'];
        $blocked = $action === 'block' ? 1 : 0;
        $admin = sql_escape_string($member['mb_id']);
        $moderation_table = willow_moderation_table();
        sql_query("INSERT INTO `{$moderation_table}` (target_type, target_id, blocked, admin_id, updated_at)
            VALUES ('{$type}', {$id}, {$blocked}, '{$admin}', '".G5_TIME_YMDHIS."')
            ON DUPLICATE KEY UPDATE blocked = VALUES(blocked), admin_id = VALUES(admin_id), updated_at = VALUES(updated_at)");
        $log = sql_escape_string("\n[".G5_TIME_YMDHIS."] ".$member['mb_id'].': '.($blocked ? '게시중단' : '게시중단 해제'));
        sql_query("UPDATE `{$table}` SET wrp_admin_memo = CONCAT(COALESCE(wrp_admin_memo, ''), '{$log}'), wrp_update_datetime = '".G5_TIME_YMDHIS."' WHERE wrp_target_type = '{$type}' AND wrp_target_id = {$id}");
        goto_url('./willow_report.php');
    }

    $ids = isset($_POST['wrp_id']) ? $_POST['wrp_id'] : array();
    $status_post = isset($_POST['wrp_status']) ? $_POST['wrp_status'] : array();
    $memo_post = isset($_POST['wrp_admin_memo']) ? $_POST['wrp_admin_memo'] : array();

    foreach ($ids as $id) {
        $id = (int) $id;
        if (!$id) {
            continue;
        }

        $status = isset($status_post[$id]) && isset($statuses[$status_post[$id]]) ? $status_post[$id] : 'pending';
        $memo = isset($memo_post[$id]) ? trim($memo_post[$id]) : '';
        sql_query(" update `{$table}`
            set wrp_status = '".sql_escape_string($status)."',
                wrp_admin_memo = '".sql_escape_string($memo)."',
                wrp_update_datetime = '".G5_TIME_YMDHIS."'
            where wrp_id = '{$id}' ");
    }

    goto_url('./willow_report.php');
}

$sfl = isset($_GET['sfl']) ? $_GET['sfl'] : '';
$stx = isset($_GET['stx']) ? trim($_GET['stx']) : '';
$status = isset($_GET['status']) && isset($statuses[$_GET['status']]) ? $_GET['status'] : '';
$where = " where 1 ";

if ($status !== '') {
    $where .= " and wrp_status = '".sql_escape_string($status)."' ";
}

if ($stx !== '') {
    $stx_sql = sql_escape_string($stx);
    if ($sfl === 'reporter') {
        $where .= " and (wrp_reporter_mb_id like '%{$stx_sql}%' or wrp_reporter_name like '%{$stx_sql}%') ";
    } else if ($sfl === 'author') {
        $where .= " and (wrp_author_mb_id like '%{$stx_sql}%' or wrp_author_name like '%{$stx_sql}%') ";
    } else if ($sfl === 'category') {
        $where .= " and wrp_category like '%{$stx_sql}%' ";
    } else {
        $where .= " and (wrp_target_title like '%{$stx_sql}%' or wrp_content like '%{$stx_sql}%') ";
    }
}

$result = sql_query(" select * from `{$table}` {$where} order by wrp_id desc limit 200 ", false);
$reports = array();
if ($result) {
    while ($row = sql_fetch_array($result)) {
        $reports[] = $row;
    }
}

require_once './admin.head.php';
?>

<form method="get" class="local_sch01 local_sch">
    <label for="status" class="sound_only">상태</label>
    <select name="status" id="status">
        <option value="">전체상태</option>
        <?php foreach ($statuses as $key => $label) { ?>
        <option value="<?php echo $key; ?>" <?php echo get_selected($status, $key); ?>><?php echo $label; ?></option>
        <?php } ?>
    </select>
    <label for="sfl" class="sound_only">검색대상</label>
    <select name="sfl" id="sfl">
        <option value="target" <?php echo get_selected($sfl, 'target'); ?>>신고내용/글제목</option>
        <option value="category" <?php echo get_selected($sfl, 'category'); ?>>카테고리</option>
        <option value="reporter" <?php echo get_selected($sfl, 'reporter'); ?>>신고자</option>
        <option value="author" <?php echo get_selected($sfl, 'author'); ?>>작성자</option>
    </select>
    <input type="text" name="stx" value="<?php echo get_text($stx); ?>" class="frm_input">
    <input type="submit" value="검색" class="btn_submit btn">
</form>

<form method="post" action="./willow_report.php">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
    <div class="tbl_head01 tbl_wrap">
        <table>
            <caption><?php echo $g5['title']; ?></caption>
            <thead>
            <tr>
                <th scope="col">번호</th>
                <th scope="col">대상</th>
                <th scope="col">작성자</th>
                <th scope="col">신고자</th>
                <th scope="col">카테고리</th>
                <th scope="col">신고내용</th>
                <th scope="col">상태</th>
                <th scope="col">관리자 메모</th>
                <th scope="col">접수일</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($reports) { ?>
            <?php foreach ($reports as $report) { ?>
            <?php $target = willow_report_target_info($report['wrp_target_type'], (int) $report['wrp_target_id']); ?>
            <tr>
                <td class="td_num">
                    <?php echo (int) $report['wrp_id']; ?>
                    <input type="hidden" name="wrp_id[]" value="<?php echo (int) $report['wrp_id']; ?>">
                </td>
                <td>
                    <strong><?php echo get_text($report['wrp_target_title']); ?></strong><br>
                    <span><?php echo $report['wrp_target_type'] === 'topic' ? '오늘의 주제 글' : '게시글'; ?> #<?php echo (int) $report['wrp_target_id']; ?></span>
                    <?php if (!empty($target['href'])) { ?>
                    <a href="<?php echo $target['href']; ?>" target="_blank" rel="noopener noreferrer" class="btn btn_03">글보기</a>
                    <?php } ?>
                    <?php $blocked = willow_post_blocked($report['wrp_target_type'], $report['wrp_target_id']); ?>
                    <p><strong><?php echo $blocked ? '게시중단' : '게시중'; ?></strong></p>
                    <button type="submit" name="moderate" value="<?php echo (int) $report['wrp_id']; ?>:<?php echo $blocked ? 'restore' : 'block'; ?>" class="btn btn_02" onclick="return confirm('<?php echo $blocked ? '이 글의 게시중단을 해제할까요?' : '이 글을 게시중단할까요? 목록과 상세페이지에서 숨겨집니다.'; ?>');"><?php echo $blocked ? '게시중단 해제' : '게시중단'; ?></button>
                </td>
                <td><?php echo get_text($report['wrp_author_name']); ?><br><span><?php echo get_text($report['wrp_author_mb_id']); ?></span></td>
                <td><?php echo get_text($report['wrp_reporter_name']); ?><br><span><?php echo get_text($report['wrp_reporter_mb_id']); ?></span></td>
                <td><?php echo get_text(willow_report_category_label($report['wrp_category'])); ?></td>
                <td style="max-width:280px;white-space:normal"><?php echo nl2br(get_text($report['wrp_content'])); ?></td>
                <td>
                    <select name="wrp_status[<?php echo (int) $report['wrp_id']; ?>]">
                        <?php foreach ($statuses as $key => $label) { ?>
                        <option value="<?php echo $key; ?>" <?php echo get_selected($report['wrp_status'], $key); ?>><?php echo $label; ?></option>
                        <?php } ?>
                    </select>
                </td>
                <td><textarea name="wrp_admin_memo[<?php echo (int) $report['wrp_id']; ?>]" rows="3" style="width:180px"><?php echo get_text($report['wrp_admin_memo'], 0); ?></textarea></td>
                <td><?php echo get_text($report['wrp_datetime']); ?></td>
            </tr>
            <?php } ?>
            <?php } else { ?>
            <tr><td colspan="9" class="empty_table">접수된 신고가 없습니다.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="btn_fixed_top">
        <input type="submit" value="저장" class="btn_submit btn">
    </div>
</form>

<?php
require_once './admin.tail.php';
