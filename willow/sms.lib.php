<?php
if (!defined('_GNUBOARD_')) exit;

function willow_sms_table()
{
    $prefix = defined('G5_TABLE_PREFIX') ? G5_TABLE_PREFIX : 'g5_';
    return $prefix.'willow_sms_log';
}

function willow_sms_install()
{
    static $installed = false;

    if ($installed) {
        return;
    }

    $table = willow_sms_table();
    sql_query(" create table if not exists `{$table}` (
        wsl_id int unsigned not null auto_increment,
        wsl_provider varchar(30) not null default 'popbill',
        wsl_type varchar(30) not null default 'sms',
        wsl_status varchar(30) not null default '',
        wsl_to varchar(30) not null default '',
        wsl_request_num varchar(80) not null default '',
        wsl_receipt_num varchar(80) not null default '',
        wsl_message varchar(255) not null default '',
        wsl_error_code varchar(30) not null default '',
        wsl_error_message varchar(255) not null default '',
        wsl_dry_run tinyint(1) not null default 0,
        wsl_datetime datetime not null,
        primary key (wsl_id),
        key wsl_to (wsl_to),
        key wsl_datetime (wsl_datetime)
    ) ", false);

    $installed = true;
}

function willow_sms_env($name, $default = '')
{
    $value = getenv($name);
    return ($value === false || $value === '') ? $default : $value;
}

function willow_sms_bool($value, $default = false)
{
    if ($value === null || $value === '') {
        return $default;
    }

    if (is_bool($value)) {
        return $value;
    }

    return in_array(strtolower((string) $value), array('1', 'true', 'yes', 'on', 'y'), true);
}

function willow_popbill_config()
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $local = array();
    $local_file = G5_DATA_PATH.'/popbill.config.php';
    if (is_file($local_file)) {
        $loaded = include $local_file;
        if (is_array($loaded)) {
            $local = $loaded;
        }
    }

    $config = array(
        'link_id' => willow_sms_env('WILLOW_POPBILL_LINK_ID', isset($local['link_id']) ? $local['link_id'] : ''),
        'secret_key' => willow_sms_env('WILLOW_POPBILL_SECRET_KEY', isset($local['secret_key']) ? $local['secret_key'] : ''),
        'corp_num' => preg_replace('/[^0-9]/', '', willow_sms_env('WILLOW_POPBILL_CORP_NUM', isset($local['corp_num']) ? $local['corp_num'] : '')),
        'user_id' => willow_sms_env('WILLOW_POPBILL_USER_ID', isset($local['user_id']) ? $local['user_id'] : ''),
        'sender' => preg_replace('/[^0-9]/', '', willow_sms_env('WILLOW_POPBILL_SENDER', isset($local['sender']) ? $local['sender'] : '')),
        'sender_name' => willow_sms_env('WILLOW_POPBILL_SENDER_NAME', isset($local['sender_name']) ? $local['sender_name'] : 'WILLOW'),
        'is_test' => willow_sms_bool(willow_sms_env('WILLOW_POPBILL_IS_TEST', isset($local['is_test']) ? $local['is_test'] : true), true),
        'dry_run' => willow_sms_bool(willow_sms_env('WILLOW_POPBILL_DRY_RUN', isset($local['dry_run']) ? $local['dry_run'] : ''), false),
        'dev_code' => preg_replace('/[^0-9]/', '', willow_sms_env('WILLOW_SMS_DEV_CODE', isset($local['dev_code']) ? $local['dev_code'] : '123456')),
    );

    if ($config['dev_code'] === '') {
        $config['dev_code'] = '123456';
    }

    return $config;
}

function willow_popbill_config_status()
{
    $config = willow_popbill_config();
    $missing = array();

    foreach (array('link_id', 'secret_key', 'corp_num', 'user_id', 'sender') as $key) {
        if ($config[$key] === '') {
            $missing[] = $key;
        }
    }

    return array(
        'ready' => empty($missing),
        'dry_run' => !empty($config['dry_run']) || !empty($missing),
        'missing' => $missing,
        'is_test' => !empty($config['is_test']),
    );
}

function willow_popbill_service()
{
    static $service = null;

    if ($service !== null) {
        return $service;
    }

    if (!defined('LINKHUB_COMM_MODE')) {
        define('LINKHUB_COMM_MODE', 'CURL');
    }

    require_once G5_PLUGIN_PATH.'/popbill/linkhub/src/Authority.php';
    require_once G5_PLUGIN_PATH.'/popbill/src/PopbillBase.php';
    require_once G5_PLUGIN_PATH.'/popbill/src/PopbillMessaging.php';

    $config = willow_popbill_config();
    $service = new \Linkhub\Popbill\PopbillMessaging($config['link_id'], $config['secret_key']);
    $service->IsTest(!empty($config['is_test']));
    $service->IPRestrictOnOff(false);
    $service->UseLocalTimeYN(true);

    return $service;
}

function willow_sms_log($data)
{
    willow_sms_install();

    $table = willow_sms_table();
    $message = isset($data['message']) ? mb_substr($data['message'], 0, 255, 'UTF-8') : '';
    $error_message = isset($data['error_message']) ? mb_substr($data['error_message'], 0, 255, 'UTF-8') : '';

    sql_query(" insert into `{$table}`
        set wsl_provider = 'popbill',
            wsl_type = '".sql_escape_string(isset($data['type']) ? $data['type'] : 'sms')."',
            wsl_status = '".sql_escape_string(isset($data['status']) ? $data['status'] : '')."',
            wsl_to = '".sql_escape_string(isset($data['to']) ? $data['to'] : '')."',
            wsl_request_num = '".sql_escape_string(isset($data['request_num']) ? $data['request_num'] : '')."',
            wsl_receipt_num = '".sql_escape_string(isset($data['receipt_num']) ? $data['receipt_num'] : '')."',
            wsl_message = '".sql_escape_string($message)."',
            wsl_error_code = '".sql_escape_string(isset($data['error_code']) ? $data['error_code'] : '')."',
            wsl_error_message = '".sql_escape_string($error_message)."',
            wsl_dry_run = '".(!empty($data['dry_run']) ? 1 : 0)."',
            wsl_datetime = '".G5_TIME_YMDHIS."' ", false);
}

function willow_sms_send($to, $message, $receiver_name = '', $request_num = '')
{
    $to = preg_replace('/[^0-9]/', '', $to);
    $message = trim($message);
    $config = willow_popbill_config();
    $status = willow_popbill_config_status();

    if ($to === '' || $message === '') {
        return array('success' => false, 'dry_run' => true, 'message' => '수신번호 또는 메시지가 비어 있습니다.');
    }

    if ($request_num === '') {
        $request_num = 'willow-'.date('YmdHis').'-'.substr(md5($to.microtime(true)), 0, 10);
    }

    if (!empty($status['dry_run'])) {
        willow_sms_log(array(
            'type' => 'auth',
            'status' => 'dry_run',
            'to' => $to,
            'request_num' => $request_num,
            'message' => $message,
            'dry_run' => true,
        ));

        return array('success' => true, 'dry_run' => true, 'receipt_num' => 'DRYRUN-'.$request_num, 'message' => '개발 모드로 SMS 발송을 기록했습니다.');
    }

    try {
        $messages = array(array(
            'snd' => $config['sender'],
            'sndnm' => $config['sender_name'],
            'rcv' => $to,
            'rcvnm' => $receiver_name,
            'msg' => $message,
        ));

        $receipt_num = willow_popbill_service()->SendSMS(
            $config['corp_num'],
            $config['sender'],
            $message,
            $messages,
            null,
            false,
            $config['user_id'],
            $config['sender_name'],
            $request_num
        );

        willow_sms_log(array(
            'type' => 'auth',
            'status' => 'sent',
            'to' => $to,
            'request_num' => $request_num,
            'receipt_num' => $receipt_num,
            'message' => '인증번호 발송',
            'dry_run' => false,
        ));

        return array('success' => true, 'dry_run' => false, 'receipt_num' => $receipt_num, 'message' => 'SMS가 발송되었습니다.');
    } catch (\Exception $e) {
        willow_sms_log(array(
            'type' => 'auth',
            'status' => 'failed',
            'to' => $to,
            'request_num' => $request_num,
            'message' => '인증번호 발송 실패',
            'error_code' => (string) $e->getCode(),
            'error_message' => $e->getMessage(),
            'dry_run' => false,
        ));

        return array('success' => false, 'dry_run' => false, 'message' => $e->getMessage(), 'code' => $e->getCode());
    }
}

function willow_auth_issue_code($phone, $force = false)
{
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (!preg_match('/^01[0-9]{8,9}$/', $phone)) {
        return array('success' => false, 'message' => '휴대폰번호 형식이 올바르지 않습니다.');
    }

    $sent_at = (int) get_session('ss_willow_sms_sent_at');
    $sent_phone = get_session('ss_willow_sms_phone');
    if (!$force && $sent_phone === $phone && $sent_at > 0 && (G5_SERVER_TIME - $sent_at) < 50) {
        return array(
            'success' => true,
            'dry_run' => (bool) get_session('ss_willow_sms_dry_run'),
            'reused' => true,
            'message' => '이미 발송된 인증번호를 사용합니다.',
        );
    }

    $config = willow_popbill_config();
    $status = willow_popbill_config_status();
    $code = !empty($status['dry_run']) ? $config['dev_code'] : (string) random_int(100000, 999999);
    $content = '[WILLOW] 휴대폰 인증번호는 '.$code.'입니다. 4분 이내에 입력해주세요.';
    $result = willow_sms_send($phone, $content, '', 'willow-auth-'.date('YmdHis').'-'.substr(md5($phone), 0, 8));

    if (empty($result['success'])) {
        return $result;
    }

    set_session('ss_willow_sms_phone', $phone);
    set_session('ss_willow_sms_code_hash', password_hash($code, PASSWORD_DEFAULT));
    set_session('ss_willow_sms_expires_at', G5_SERVER_TIME + 240);
    set_session('ss_willow_sms_sent_at', G5_SERVER_TIME);
    set_session('ss_willow_sms_dry_run', !empty($result['dry_run']) ? 1 : 0);

    $result['dev_code'] = !empty($result['dry_run']) ? $code : '';
    return $result;
}

function willow_auth_verify_code($phone, $code)
{
    $phone = preg_replace('/[^0-9]/', '', $phone);
    $code = preg_replace('/[^0-9]/', '', $code);
    $sent_phone = get_session('ss_willow_sms_phone');
    $hash = get_session('ss_willow_sms_code_hash');
    $expires_at = (int) get_session('ss_willow_sms_expires_at');

    if ($phone === '' || $code === '' || $sent_phone !== $phone || !$hash) {
        return false;
    }

    if ($expires_at < G5_SERVER_TIME) {
        return false;
    }

    if (!password_verify($code, $hash)) {
        return false;
    }

    set_session('ss_willow_phone_verified', $phone);
    set_session('ss_willow_phone_verified_at', G5_SERVER_TIME);

    return true;
}

function willow_auth_is_phone_verified($phone)
{
    $phone = preg_replace('/[^0-9]/', '', $phone);
    $verified_phone = get_session('ss_willow_phone_verified');
    $verified_at = (int) get_session('ss_willow_phone_verified_at');

    return $phone !== '' && $verified_phone === $phone && $verified_at > 0 && (G5_SERVER_TIME - $verified_at) <= 600;
}
