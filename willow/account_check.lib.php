<?php
if (!defined('_GNUBOARD_')) exit;

include_once G5_PATH.'/willow/sms.lib.php';

function willow_account_check_table()
{
    $prefix = defined('G5_TABLE_PREFIX') ? G5_TABLE_PREFIX : 'g5_';
    return $prefix.'willow_account_check_log';
}

function willow_account_check_install()
{
    static $installed = false;

    if ($installed) {
        return;
    }

    $table = willow_account_check_table();
    sql_query(" create table if not exists `{$table}` (
        wacl_id int unsigned not null auto_increment,
        mb_id varchar(20) not null default '',
        wacl_status varchar(30) not null default '',
        wacl_bank_name varchar(100) not null default '',
        wacl_bank_code varchar(10) not null default '',
        wacl_account_holder varchar(100) not null default '',
        wacl_account_number varchar(100) not null default '',
        wacl_remote_holder varchar(100) not null default '',
        wacl_result_code varchar(30) not null default '',
        wacl_result_message varchar(255) not null default '',
        wacl_error_message varchar(255) not null default '',
        wacl_datetime datetime not null,
        primary key (wacl_id),
        key mb_id (mb_id),
        key wacl_datetime (wacl_datetime)
    ) ", false);

    $installed = true;
}

function willow_account_bank_codes()
{
    return array(
        '산업은행' => '002',
        '기업은행' => '003',
        'IBK기업은행' => '003',
        '국민은행' => '004',
        'KB국민은행' => '004',
        '수협은행' => '007',
        '농협은행' => '011',
        'NH농협은행' => '011',
        '농협' => '011',
        '우리은행' => '020',
        'SC제일은행' => '023',
        '한국씨티은행' => '027',
        '씨티은행' => '027',
        '대구은행' => '031',
        '부산은행' => '032',
        '광주은행' => '034',
        '제주은행' => '035',
        '전북은행' => '037',
        '경남은행' => '039',
        '새마을금고' => '045',
        '신협' => '048',
        '우체국' => '071',
        '하나은행' => '081',
        'KEB하나은행' => '081',
        '신한은행' => '088',
        '케이뱅크' => '089',
        '카카오뱅크' => '090',
        '토스뱅크' => '092',
        '삼성증권' => '240',
    );
}

function willow_account_normalize_text($value)
{
    $value = trim((string) $value);
    $value = preg_replace('/\s+/u', '', $value);
    return mb_strtolower($value, 'UTF-8');
}

function willow_account_bank_code($bank_name)
{
    $bank_name = trim((string) $bank_name);
    $codes = willow_account_bank_codes();

    if (isset($codes[$bank_name])) {
        return $codes[$bank_name];
    }

    $normalized = willow_account_normalize_text($bank_name);
    foreach ($codes as $name => $code) {
        if (willow_account_normalize_text($name) === $normalized) {
            return $code;
        }
    }

    return '';
}

function willow_account_hash($bank_name, $account_holder, $account_number)
{
    $account_number = preg_replace('/[^0-9]/', '', (string) $account_number);
    return hash('sha256', willow_account_normalize_text($bank_name).'|'.willow_account_normalize_text($account_holder).'|'.$account_number);
}

function willow_account_session_key($mb_id)
{
    return 'ss_willow_account_verified_'.$mb_id;
}

function willow_account_set_verified($mb_id, $bank_name, $account_holder, $account_number)
{
    set_session(willow_account_session_key($mb_id), array(
        'hash' => willow_account_hash($bank_name, $account_holder, $account_number),
        'time' => G5_SERVER_TIME,
    ));
}

function willow_account_is_verified($mb_id, $bank_name, $account_holder, $account_number)
{
    $verified = get_session(willow_account_session_key($mb_id));
    if (!is_array($verified) || empty($verified['hash']) || empty($verified['time'])) {
        return false;
    }

    if ((int) $verified['time'] < G5_SERVER_TIME - 600) {
        return false;
    }

    return hash_equals($verified['hash'], willow_account_hash($bank_name, $account_holder, $account_number));
}

function willow_account_config_status()
{
    $config = willow_popbill_config();
    $missing = array();

    foreach (array('link_id', 'secret_key', 'corp_num', 'user_id') as $key) {
        if ($config[$key] === '') {
            $missing[] = $key;
        }
    }

    return array(
        'ready' => empty($missing),
        'missing' => $missing,
        'is_test' => !empty($config['is_test']),
    );
}

function willow_account_service()
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
    require_once G5_PLUGIN_PATH.'/popbill/src/PopbillAccountCheck.php';

    $config = willow_popbill_config();
    $service = new \Linkhub\Popbill\PopbillAccountCheck($config['link_id'], $config['secret_key']);
    $service->IsTest(!empty($config['is_test']));
    $service->IPRestrictOnOff(false);
    $service->UseLocalTimeYN(true);

    return $service;
}

function willow_account_log($data)
{
    willow_account_check_install();

    $table = willow_account_check_table();
    $error_message = isset($data['error_message']) ? mb_substr($data['error_message'], 0, 255, 'UTF-8') : '';
    $result_message = isset($data['result_message']) ? mb_substr($data['result_message'], 0, 255, 'UTF-8') : '';

    sql_query(" insert into `{$table}`
        set mb_id = '".sql_escape_string(isset($data['mb_id']) ? $data['mb_id'] : '')."',
            wacl_status = '".sql_escape_string(isset($data['status']) ? $data['status'] : '')."',
            wacl_bank_name = '".sql_escape_string(isset($data['bank_name']) ? $data['bank_name'] : '')."',
            wacl_bank_code = '".sql_escape_string(isset($data['bank_code']) ? $data['bank_code'] : '')."',
            wacl_account_holder = '".sql_escape_string(isset($data['account_holder']) ? $data['account_holder'] : '')."',
            wacl_account_number = '".sql_escape_string(isset($data['account_number']) ? $data['account_number'] : '')."',
            wacl_remote_holder = '".sql_escape_string(isset($data['remote_holder']) ? $data['remote_holder'] : '')."',
            wacl_result_code = '".sql_escape_string(isset($data['result_code']) ? $data['result_code'] : '')."',
            wacl_result_message = '".sql_escape_string($result_message)."',
            wacl_error_message = '".sql_escape_string($error_message)."',
            wacl_datetime = '".G5_TIME_YMDHIS."' ", false);
}

function willow_account_result_success($value)
{
    if ($value === true || $value === 1) {
        return true;
    }

    $value = strtolower(trim((string) $value));
    return in_array($value, array('1', 'true', 'y', 'yes', 'success'), true);
}

function willow_account_verify($bank_name, $account_holder, $account_number, $mb_id = '')
{
    $bank_name = trim(strip_tags((string) $bank_name));
    $account_holder = trim(strip_tags((string) $account_holder));
    $account_number = preg_replace('/[^0-9]/', '', (string) $account_number);
    $bank_code = willow_account_bank_code($bank_name);

    if ($bank_name === '' || $account_holder === '' || $account_number === '') {
        return array('success' => false, 'message' => '정산 계좌정보를 모두 입력해주세요.');
    }

    if ($bank_code === '') {
        return array('success' => false, 'message' => '지원하지 않는 은행입니다. 은행명을 다시 선택해주세요.');
    }

    $status = willow_account_config_status();
    if (!$status['ready']) {
        $message = '팝빌 계좌검증 설정이 완료되지 않았습니다.';
        willow_account_log(array(
            'mb_id' => $mb_id,
            'status' => 'config_error',
            'bank_name' => $bank_name,
            'bank_code' => $bank_code,
            'account_holder' => $account_holder,
            'account_number' => $account_number,
            'error_message' => $message.' missing: '.implode(',', $status['missing']),
        ));

        return array('success' => false, 'message' => $message);
    }

    $config = willow_popbill_config();

    try {
        $result = willow_account_service()->CheckAccountInfo(
            $config['corp_num'],
            $bank_code,
            $account_number,
            $config['user_id']
        );

        $remote_holder = isset($result->accountName) ? trim((string) $result->accountName) : '';
        $result_code = isset($result->resultCode) ? (string) $result->resultCode : '';
        $result_message = isset($result->resultMessage) ? (string) $result->resultMessage : '';
        $api_success = isset($result->result) ? willow_account_result_success($result->result) : ($remote_holder !== '');
        $holder_match = $remote_holder !== '' && willow_account_normalize_text($remote_holder) === willow_account_normalize_text($account_holder);
        $success = $api_success && $holder_match;

        willow_account_log(array(
            'mb_id' => $mb_id,
            'status' => $success ? 'verified' : 'failed',
            'bank_name' => $bank_name,
            'bank_code' => $bank_code,
            'account_holder' => $account_holder,
            'account_number' => $account_number,
            'remote_holder' => $remote_holder,
            'result_code' => $result_code,
            'result_message' => $result_message,
        ));

        if (!$api_success) {
            return array('success' => false, 'message' => $result_message ? $result_message : '계좌정보를 확인할 수 없습니다.');
        }

        if (!$holder_match) {
            return array('success' => false, 'message' => '입력한 예금주명과 실제 예금주명이 일치하지 않습니다.');
        }

        return array(
            'success' => true,
            'message' => '계좌 인증이 완료되었습니다.',
            'account_name' => $remote_holder,
            'bank_code' => $bank_code,
        );
    } catch (\Exception $e) {
        willow_account_log(array(
            'mb_id' => $mb_id,
            'status' => 'error',
            'bank_name' => $bank_name,
            'bank_code' => $bank_code,
            'account_holder' => $account_holder,
            'account_number' => $account_number,
            'error_message' => $e->getMessage(),
        ));

        return array('success' => false, 'message' => $e->getMessage());
    }
}
