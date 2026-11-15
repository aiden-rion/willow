<?php
include_once './_common.php';
include_once './notification.lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$is_member) {
    alert('로그인 후 이용해주세요.', G5_BBS_URL.'/login.php');
}
if (!get_session('willow_cancel_token') || !hash_equals(get_session('willow_cancel_token'), (string) ($_POST['token'] ?? ''))) {
    alert('잘못된 요청입니다. 페이지를 새로고침해주세요.');
}
$author_id = trim((string) ($_POST['author'] ?? ''));
if (!willow_cancel_subscription($member['mb_id'], $author_id)) {
    alert('취소할 구독을 찾을 수 없습니다.', G5_URL.'/willow/subscribe.php?mode=my');
}
alert('구독 취소가 접수되었습니다. 결제된 기간까지 이용할 수 있습니다.', G5_URL.'/willow/subscribe.php?mode=my');
