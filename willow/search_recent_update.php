<?php
include_once('./_common.php');
include_once('./content.lib.php');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('success' => false, 'message' => '잘못된 요청입니다.'));
    exit;
}

$action = isset($_POST['action']) ? trim($_POST['action']) : '';

if ($action === 'delete') {
    $recent_id = isset($_POST['recent_id']) ? (int) $_POST['recent_id'] : 0;
    willow_delete_recent_search($recent_id);
    echo json_encode(array('success' => true));
    exit;
}

if ($action === 'clear') {
    willow_clear_recent_searches();
    echo json_encode(array('success' => true));
    exit;
}

echo json_encode(array('success' => false, 'message' => '처리할 수 없는 요청입니다.'));
exit;
