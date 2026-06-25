<?php
include_once('./_common.php');
include_once('./content.lib.php');

if (!$is_member) {
    alert('로그인 후 이용해주세요.', G5_BBS_URL.'/login.php');
}

check_token();
willow_author_request_install();

$is_author = ((int) $member['mb_level'] >= 3) || (!empty($member['mb_2']) && $member['mb_2'] === 'author');
if ($is_author) {
    alert('이미 작가회원으로 등록되어 있습니다.', G5_URL.'/willow/author_register.php');
}

$latest = willow_author_latest_request($member['mb_id']);
if (!empty($latest['war_status']) && $latest['war_status'] === 'pending') {
    alert('이미 검토 중인 작가등록 요청이 있습니다.', G5_URL.'/willow/author_register.php?step=form');
}

$intro = isset($_POST['intro']) ? trim(strip_tags($_POST['intro'])) : '';
$is_escapee = isset($_POST['is_escapee']) ? trim($_POST['is_escapee']) : '';
$categories = isset($_POST['categories']) && is_array($_POST['categories']) ? $_POST['categories'] : array();
$bank_name = isset($_POST['bank_name']) ? trim(strip_tags($_POST['bank_name'])) : '';
$account_holder = isset($_POST['account_holder']) ? trim(strip_tags($_POST['account_holder'])) : '';
$account_number = isset($_POST['account_number']) ? preg_replace('/[^0-9]/', '', $_POST['account_number']) : '';
$subscribe_price = isset($_POST['subscribe_price']) ? max(0, (int) $_POST['subscribe_price']) : 0;
$agree_terms = isset($_POST['agree_terms']) ? 1 : 0;
$agree_privacy = isset($_POST['agree_privacy']) ? 1 : 0;

if ($intro === '') {
    alert('작가소개를 입력해주세요.');
}

if (!in_array($is_escapee, array('yes', 'no'), true)) {
    alert('작가 유형을 선택해주세요.');
}

if (!$categories) {
    alert('주요 카테고리를 하나 이상 선택해주세요.');
}

if ($bank_name === '' || $account_holder === '' || $account_number === '') {
    alert('정산계좌 정보를 입력해주세요.');
}

if (!$agree_terms || !$agree_privacy || empty($_POST['agree_service'])) {
    alert('필수 동의 항목을 확인해주세요.');
}

$upload_dir = willow_author_request_upload_dir();
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, G5_DIR_PERMISSION, true);
    @chmod($upload_dir, G5_DIR_PERMISSION);
}

function willow_author_request_upload($field, $upload_dir, $required, $image_only = false)
{
    if (empty($_FILES[$field]['name'])) {
        if ($required) {
            alert('필수 첨부파일을 등록해주세요.');
        }
        return '';
    }

    if (!empty($_FILES[$field]['error'])) {
        alert('파일 업로드 중 오류가 발생했습니다. 오류코드: '.$_FILES[$field]['error']);
    }

    if ((int) $_FILES[$field]['size'] > 5 * 1024 * 1024) {
        alert('첨부파일은 5MB 이하만 업로드할 수 있습니다.');
    }

    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    $image_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    $allowed_ext = $image_only ? $image_ext : array_merge($image_ext, array('pdf'));

    if (!in_array($ext, $allowed_ext, true)) {
        alert($image_only ? '이미지 파일만 업로드할 수 있습니다.' : '이미지 또는 PDF 파일만 업로드할 수 있습니다.');
    }

    if (in_array($ext, $image_ext, true)) {
        if (!@getimagesize($_FILES[$field]['tmp_name'])) {
            alert('정상적인 이미지 파일만 업로드할 수 있습니다.');
        }
    } else {
        $fp = @fopen($_FILES[$field]['tmp_name'], 'rb');
        $head = $fp ? fread($fp, 4) : '';
        if ($fp) {
            fclose($fp);
        }
        if ($head !== '%PDF') {
            alert('정상적인 PDF 파일만 업로드할 수 있습니다.');
        }
    }

    $filename = 'author_'.date('YmdHis').'_'.bin2hex(random_bytes(4)).'.'.$ext;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $upload_dir.'/'.$filename)) {
        alert('파일 저장에 실패했습니다.');
    }
    @chmod($upload_dir.'/'.$filename, G5_FILE_PERMISSION);

    return $filename;
}

$profile_image = willow_author_request_upload('profile_image', $upload_dir, false, true);
$cert_file = willow_author_request_upload('cert_file', $upload_dir, $is_escapee === 'yes', false);
$category_text = implode(',', array_map(function($item) {
    return trim(strip_tags($item));
}, $categories));

$table = willow_author_request_table();
$now = G5_TIME_YMDHIS;

sql_query(" insert into `{$table}`
    set mb_id = '".sql_escape_string($member['mb_id'])."',
        war_status = 'pending',
        war_intro = '".sql_escape_string($intro)."',
        war_is_escapee = '".sql_escape_string($is_escapee)."',
        war_cert_file = '".sql_escape_string($cert_file)."',
        war_profile_image = '".sql_escape_string($profile_image)."',
        war_categories = '".sql_escape_string($category_text)."',
        war_bank_name = '".sql_escape_string($bank_name)."',
        war_account_holder = '".sql_escape_string($account_holder)."',
        war_account_number = '".sql_escape_string($account_number)."',
        war_subscribe_price = '{$subscribe_price}',
        war_agree_terms = '{$agree_terms}',
        war_agree_privacy = '{$agree_privacy}',
        war_memo = '',
        war_admin_memo = '',
        war_datetime = '{$now}',
        war_update_datetime = '{$now}' ");

alert('작가등록 요청이 접수되었습니다. 관리자 승인 후 작가회원으로 전환됩니다.', G5_URL.'/willow/author_register.php');
