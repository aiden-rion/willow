<?php
include_once('./_common.php');
include_once(G5_PATH.'/willow/sms.lib.php');

header('Content-Type: application/json; charset=utf-8');

function willow_member_confirm_json($success, $message, $extra = array())
{
    echo json_encode(array_merge(array(
        'success' => (bool) $success,
        'message' => $message,
    ), $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($is_guest) {
    willow_member_confirm_json(false, '로그인 후 이용해주세요.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    willow_member_confirm_json(false, '올바른 방법으로 이용해 주세요.');
}

$member_hp = isset($member['mb_hp']) ? preg_replace('/[^0-9]/', '', $member['mb_hp']) : '';
if (!preg_match('/^01[0-9]{8,9}$/', $member_hp)) {
    willow_member_confirm_json(false, '회원정보에 등록된 휴대폰번호가 없습니다.');
}

$result = willow_auth_issue_code($member_hp, true);
if (empty($result['success'])) {
    willow_member_confirm_json(false, isset($result['message']) ? $result['message'] : '인증번호 발송에 실패했습니다.');
}

willow_member_confirm_json(true, '인증번호가 발송되었습니다.', array(
    'expires_in' => 240,
    'dry_run' => !empty($result['dry_run']),
));
