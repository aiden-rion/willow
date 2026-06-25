<?php
include_once('./_common.php');

if ($is_guest) {
    alert('로그인 한 회원만 접근하실 수 있습니다.', G5_BBS_URL.'/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    alert('올바른 방법으로 이용해 주십시오.', G5_URL);
}

$mb_id = isset($_POST['mb_id']) ? trim($_POST['mb_id']) : '';
if (!$mb_id || $member['mb_id'] !== $mb_id) {
    alert('로그인된 회원과 넘어온 정보가 서로 다릅니다.');
}

$auth_code = isset($_POST['auth_code']) ? preg_replace('/[^0-9]/', '', $_POST['auth_code']) : '';
if (strlen($auth_code) !== 6) {
    alert('인증번호 6자리를 입력해 주세요.');
}

$member_hp = isset($member['mb_hp']) ? preg_replace('/[^0-9]/', '', $member['mb_hp']) : '';
if (!$member_hp) {
    alert('회원정보에 등록된 휴대폰번호가 없습니다. 고객센터로 문의해 주세요.');
}

$url = isset($_POST['url']) ? trim($_POST['url']) : 'register_form.php';
while (1) {
    $tmp = preg_replace('/&#[^;]+;/', '', $url);
    if ($tmp == $url) {
        break;
    }
    $url = $tmp;
}

$url = run_replace('member_confirm_next_url', $url);
check_url_host($url, '', G5_URL, true);

if ($url) {
    $url = preg_replace('#^/\\\{1,}#', '/', $url);

    if (preg_match('#^/{3,}#', $url)) {
        $url = preg_replace('#^/{3,}#', '/', $url);
    }

    if (function_exists('safe_filter_url_host')) {
        $url = safe_filter_url_host($url);
    }
}

$confirm_key = 'ss_willow_member_confirm_'.$member['mb_id'];
set_session($confirm_key, G5_SERVER_TIME);

$form_action = htmlspecialchars($url, ENT_QUOTES);
$form_mb_id = htmlspecialchars($member['mb_id'], ENT_QUOTES);
?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<title>휴대폰 인증 확인</title>
</head>
<body>
<form name="fmemberconfirmphone" method="post" action="<?php echo $form_action; ?>">
<input type="hidden" name="mb_id" value="<?php echo $form_mb_id; ?>">
<input type="hidden" name="w" value="u">
</form>
<script>
document.fmemberconfirmphone.submit();
</script>
</body>
</html>
