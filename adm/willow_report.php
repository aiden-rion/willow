<?php
$sub_menu = '700900';
require_once './_common.php';
include_once G5_PATH.'/willow/content.lib.php';

auth_check_menu($auth, $sub_menu, 'r');
willow_report_install();

$g5['title'] = '신고관리';
$table = willow_report_table();
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
                </td>
                <td><?php echo get_text($report['wrp_author_name']); ?><br><span><?php echo get_text($report['wrp_author_mb_id']); ?></span></td>
                <td><?php echo get_text($report['wrp_reporter_name']); ?><br><span><?php echo get_text($report['wrp_reporter_mb_id']); ?></span></td>
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
            <tr><td colspan="8" class="empty_table">접수된 신고가 없습니다.</td></tr>
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
