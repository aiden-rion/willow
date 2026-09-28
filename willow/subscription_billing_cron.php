<?php
$is_cli = (PHP_SAPI === 'cli');
if ($is_cli) {
    if (!defined('G5_IS_ADMIN')) {
        define('G5_IS_ADMIN', true);
    }
    $_SERVER['REMOTE_ADDR'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
    $_SERVER['HTTP_HOST'] = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $_SERVER['SERVER_NAME'] = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
    $_SERVER['SERVER_PORT'] = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : '80';
    $_SERVER['REQUEST_URI'] = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/willow/subscription_billing_cron.php';
    $_SERVER['SCRIPT_NAME'] = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/willow/subscription_billing_cron.php';
    $_SERVER['PHP_SELF'] = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
}

include_once('./_common.php');
include_once('./subscription_billing.lib.php');

if (!$is_cli && !$is_admin) {
    alert('관리자만 실행할 수 있습니다.', G5_URL);
}

$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
$items = willow_subscription_process_due_billings($limit);

if ($is_cli) {
    echo json_encode(array('count' => count($items), 'items' => $items), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
    exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('count' => count($items), 'items' => $items), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
