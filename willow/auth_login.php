<?php
include_once('./_common.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    goto_url(G5_BBS_URL.'/login.php');
}

$phone = isset($_POST['phone']) ? preg_replace('/[^0-9]/', '', $_POST['phone']) : '';
$nick = isset($_POST['nick']) ? trim(strip_tags($_POST['nick'])) : '';
$return_url = isset($_POST['url']) ? trim($_POST['url']) : G5_URL;

if (!$phone) {
    $phone = '01000009920';
}

if (!$nick) {
    $nick = '윌로우 회원';
}

$mb_id = 'willow_'.substr(md5($phone), 0, 12);
$mb_name = $nick;
$mb_nick = $nick;
$mb_email = $mb_id.'@willow.local';
$now = G5_TIME_YMDHIS;
$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';

$member_row = get_member($mb_id);

if (empty($member_row['mb_id'])) {
    $password = get_encrypt_string(bin2hex(random_bytes(16)));
    $sql = " insert into {$g5['member_table']}
                set mb_id = '".sql_escape_string($mb_id)."',
                    mb_password = '".sql_escape_string($password)."',
                    mb_name = '".sql_escape_string($mb_name)."',
                    mb_nick = '".sql_escape_string($mb_nick)."',
                    mb_nick_date = '".G5_TIME_YMD."',
                    mb_email = '".sql_escape_string($mb_email)."',
                    mb_level = '2',
                    mb_hp = '".sql_escape_string($phone)."',
                    mb_certify = 'hp',
                    mb_today_login = '".$now."',
                    mb_login_ip = '".sql_escape_string($ip)."',
                    mb_datetime = '".$now."',
                    mb_ip = '".sql_escape_string($ip)."',
                    mb_email_certify = '".$now."',
                    mb_open = '1',
                    mb_open_date = '".G5_TIME_YMD."',
                    mb_mailling = '1',
                    mb_mailling_date = '".$now."',
                    mb_sms = '1',
                    mb_sms_date = '".$now."',
                    mb_profile = '',
                    mb_1 = 'willow_phone_auth' ";
    sql_query($sql);
} else {
    $sql = " update {$g5['member_table']}
                set mb_nick = '".sql_escape_string($mb_nick)."',
                    mb_hp = '".sql_escape_string($phone)."',
                    mb_today_login = '".$now."',
                    mb_login_ip = '".sql_escape_string($ip)."'
              where mb_id = '".sql_escape_string($mb_id)."' ";
    sql_query($sql);
}

$mb = get_member($mb_id);
if (empty($mb['mb_id'])) {
    alert('회원 세션을 생성하지 못했습니다. 다시 시도해 주세요.', G5_BBS_URL.'/login.php');
}

session_regenerate_id(false);
set_session('ss_mb_id', $mb['mb_id']);
generate_mb_key($mb);
if (function_exists('update_auth_session_token')) {
    update_auth_session_token($mb['mb_datetime']);
}

if (!$return_url || strpos($return_url, 'login.php') !== false) {
    $return_url = G5_URL;
}

goto_url($return_url);
