<?php
include_once './_common.php';
include_once G5_PATH.'/willow/account_check.lib.php';

header('Content-Type: application/json; charset=utf-8');

function willow_account_json($data)
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$is_member) {
    willow_account_json(array('success' => false, 'message' => '로그인 후 이용해주세요.'));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    willow_account_json(array('success' => false, 'message' => '잘못된 요청입니다.'));
}

$is_author = ((int) $member['mb_level'] >= 3) || (!empty($member['mb_2']) && $member['mb_2'] === 'author');
if (!$is_author) {
    willow_account_json(array('success' => false, 'message' => '작가회원만 계좌 인증을 할 수 있습니다.'));
}

$token = isset($_POST['token']) ? trim($_POST['token']) : '';
if ($token === '' || $token !== get_session('ss_token')) {
    willow_account_json(array('success' => false, 'message' => '토큰 정보가 올바르지 않습니다. 페이지를 새로고침 후 다시 시도해주세요.'));
}

$bank_name = isset($_POST['mb_8']) ? trim($_POST['mb_8']) : '';
$account_holder = isset($_POST['mb_9']) ? trim($_POST['mb_9']) : '';
$account_number = isset($_POST['mb_10']) ? trim($_POST['mb_10']) : '';

if (
    $bank_name === trim((string) $member['mb_8']) &&
    $account_holder === trim((string) $member['mb_9']) &&
    preg_replace('/[^0-9]/', '', $account_number) === preg_replace('/[^0-9]/', '', (string) $member['mb_10'])
) {
    willow_account_set_verified($member['mb_id'], $bank_name, $account_holder, $account_number);
    willow_account_json(array('success' => true, 'message' => '기존 인증 계좌정보입니다.', 'unchanged' => true));
}

$result = willow_account_verify($bank_name, $account_holder, $account_number, $member['mb_id']);
if (!empty($result['success'])) {
    willow_account_set_verified($member['mb_id'], $bank_name, $account_holder, $account_number);
}

willow_account_json($result);
