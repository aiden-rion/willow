<?php
$sub_menu = '700400';
require_once './_common.php';
include_once G5_PATH.'/willow/content.lib.php';
auth_check_menu($auth, $sub_menu, 'r');
$id = (int) ($_GET['war_id'] ?? 0);
$table = willow_author_request_table();
$row = sql_fetch("SELECT war_business_file FROM `{$table}` WHERE war_id = {$id}", false);
$name = basename($row['war_business_file'] ?? '');
$file = willow_author_business_upload_dir().'/'.$name;
if (!$name || !is_file($file) || !is_readable($file)) {
    http_response_code(404);
    exit('첨부파일을 찾을 수 없습니다.');
}
header('Content-Type: application/octet-stream');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
header('Content-Disposition: attachment; filename="business-certificate-'. $id.'.'.pathinfo($name, PATHINFO_EXTENSION).'"');
header('Content-Length: '.filesize($file));
readfile($file);
exit;
