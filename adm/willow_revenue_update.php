<?php
$sub_menu = '700500';
require_once './_common.php';
include_once G5_PATH.'/willow/revenue.lib.php';

auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$author_share_rate = isset($_POST['author_share_rate']) ? (int) $_POST['author_share_rate'] : 70;
if ($author_share_rate < 0 || $author_share_rate > 100) {
    alert('작가 배분율은 0%부터 100% 사이로 입력해주세요.', './willow_revenue.php');
}

willow_revenue_set_config('author_share_rate', (string) $author_share_rate, $member['mb_id']);

goto_url('./willow_revenue.php');
