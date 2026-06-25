<?php
include_once('./_common.php');
include_once(G5_PATH.'/willow/revenue.lib.php');

if ($is_guest)
    alert_close('회원만 조회하실 수 있습니다.');

$g5['title'] = '포인트';
include_once(G5_PATH.'/head.sub.php');

$list = array();

$point_type = isset($_GET['point_type']) ? preg_replace('/[^a-z]/', '', $_GET['point_type']) : 'all';
if (!in_array($point_type, array('all', 'earn', 'use'), true)) {
    $point_type = 'all';
}

$sql_common = " from {$g5['point_table']} where mb_id = '".escape_trim($member['mb_id'])."' ";
if ($point_type === 'earn') {
    $sql_common .= " and po_point > 0 ";
} else if ($point_type === 'use') {
    $sql_common .= " and po_point < 0 ";
}
$sql_order = " order by po_id desc ";

$sql = " select count(*) as cnt {$sql_common} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) { $page = 1; } // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$sql = " select *
            {$sql_common}
            {$sql_order}
            limit {$from_record}, {$rows} ";

$result = sql_query($sql);

for ($i=0; $row=sql_fetch_array($result); $i++) {
    $list[] = $row;
}

$willow_is_author = willow_revenue_is_author($member);
$willow_point_balance = $willow_is_author ? willow_revenue_author_balance($member['mb_id']) : array();
if (!$willow_point_balance) {
    $willow_point_balance = array(
        'earned_amount' => 0,
        'paid_amount' => 0,
        'requested_amount' => 0,
        'available_amount' => 0,
        'mb_point' => (int) $member['mb_point'],
        'mb_8' => isset($member['mb_8']) ? $member['mb_8'] : '',
        'mb_9' => isset($member['mb_9']) ? $member['mb_9'] : '',
        'mb_10' => isset($member['mb_10']) ? $member['mb_10'] : '',
    );
}
$willow_point_qstr = 'point_type='.$point_type;

include_once($member_skin_path.'/point.skin.php');

include_once(G5_PATH.'/tail.sub.php');
