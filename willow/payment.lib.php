<?php
if (!defined('_GNUBOARD_')) exit;

function willow_payment_default_table()
{
    global $g5;

    $prefix = defined('G5_TABLE_PREFIX') ? G5_TABLE_PREFIX : (isset($g5['table_prefix']) ? $g5['table_prefix'] : 'g5_');

    return $prefix.'willow_payment_default';
}

function willow_payment_card_meta_table()
{
    global $g5;

    $prefix = defined('G5_TABLE_PREFIX') ? G5_TABLE_PREFIX : (isset($g5['table_prefix']) ? $g5['table_prefix'] : 'g5_');

    return $prefix.'willow_payment_card_meta';
}

function willow_payment_card_table()
{
    global $g5;

    return isset($g5['g5_subscription_mb_cardinfo_table']) ? $g5['g5_subscription_mb_cardinfo_table'] : G5_TABLE_PREFIX.'subscription_mb_cardinfo';
}

function willow_payment_install()
{
    static $installed = false;

    if ($installed) {
        return;
    }

    $table = willow_payment_default_table();
    sql_query(" create table if not exists `{$table}` (
        mb_id varchar(100) not null default '',
        ci_id int unsigned not null default 0,
        wp_datetime datetime not null,
        primary key (mb_id),
        key ci_id (ci_id)
    ) ", false);

    $meta_table = willow_payment_card_meta_table();
    sql_query(" create table if not exists `{$meta_table}` (
        ci_id int unsigned not null default 0,
        card_expiry varchar(20) not null default '',
        wp_datetime datetime not null,
        primary key (ci_id)
    ) ", false);

    $installed = true;
}

function willow_payment_cards($mb_id)
{
    $cards = array();
    $card_table = willow_payment_card_table();
    $result = sql_query(" select *
        from `{$card_table}`
        where card_billkey <> ''
            and mb_id = '".sql_escape_string($mb_id)."'
        order by ci_id desc ", false);

    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $cards[] = $row;
        }
    }

    return $cards;
}

function willow_payment_crypto_key()
{
    $base = defined('G5_TOKEN_ENCRYPTION_KEY') ? G5_TOKEN_ENCRYPTION_KEY : '';
    $base .= '|'.(defined('G5_MYSQL_USER') ? G5_MYSQL_USER : '').'|'.(defined('G5_MYSQL_DB') ? G5_MYSQL_DB : '');

    return hash('sha256', $base, true);
}

function willow_payment_is_encrypted_billkey($value)
{
    return is_string($value) && strpos($value, 'enc:v1:') === 0;
}

function willow_payment_encrypt_billkey($billkey)
{
    $billkey = (string) $billkey;
    if ($billkey === '' || willow_payment_is_encrypted_billkey($billkey)) {
        return $billkey;
    }

    if (!function_exists('openssl_encrypt')) {
        return $billkey;
    }

    $iv = openssl_random_pseudo_bytes(16);
    $cipher = openssl_encrypt($billkey, 'AES-256-CBC', willow_payment_crypto_key(), OPENSSL_RAW_DATA, $iv);
    if ($cipher === false) {
        return $billkey;
    }

    return 'enc:v1:'.strtr(base64_encode($iv.$cipher), '+/=', '._-');
}

function willow_payment_decrypt_billkey($billkey)
{
    $billkey = (string) $billkey;
    if ($billkey === '' || !willow_payment_is_encrypted_billkey($billkey)) {
        return $billkey;
    }

    if (!function_exists('openssl_decrypt')) {
        return '';
    }

    $payload = base64_decode(strtr(substr($billkey, 7), '._-', '+/='), true);
    if ($payload === false || strlen($payload) <= 16) {
        return '';
    }

    $iv = substr($payload, 0, 16);
    $cipher = substr($payload, 16);
    $plain = openssl_decrypt($cipher, 'AES-256-CBC', willow_payment_crypto_key(), OPENSSL_RAW_DATA, $iv);

    return $plain === false ? '' : $plain;
}

function willow_payment_card_billkey($card)
{
    if (empty($card['card_billkey'])) {
        return '';
    }

    return willow_payment_decrypt_billkey($card['card_billkey']);
}

function willow_payment_card_expiry($ci_id)
{
    willow_payment_install();

    $meta_table = willow_payment_card_meta_table();
    $row = sql_fetch(" select card_expiry from `{$meta_table}` where ci_id = '".(int) $ci_id."' ", false);

    return isset($row['card_expiry']) ? $row['card_expiry'] : '';
}

function willow_payment_set_card_expiry($ci_id, $card_expiry)
{
    willow_payment_install();

    $meta_table = willow_payment_card_meta_table();
    sql_query(" insert into `{$meta_table}`
            (ci_id, card_expiry, wp_datetime)
        values
            ('".(int) $ci_id."', '".sql_escape_string($card_expiry)."', '".G5_TIME_YMDHIS."')
        on duplicate key update
            card_expiry = values(card_expiry),
            wp_datetime = values(wp_datetime) ", false);
}

function willow_payment_find_card_by_billkey($mb_id, $billkey)
{
    $cards = willow_payment_cards($mb_id);
    foreach ($cards as $card) {
        if (hash_equals(willow_payment_card_billkey($card), (string) $billkey)) {
            return $card;
        }
    }

    return array();
}

function willow_payment_default_id($mb_id)
{
    willow_payment_install();

    $table = willow_payment_default_table();
    $row = sql_fetch(" select ci_id from `{$table}` where mb_id = '".sql_escape_string($mb_id)."' ", false);

    return isset($row['ci_id']) ? (int) $row['ci_id'] : 0;
}

function willow_payment_set_default($mb_id, $ci_id)
{
    willow_payment_install();

    $table = willow_payment_default_table();
    sql_query(" insert into `{$table}`
            (mb_id, ci_id, wp_datetime)
        values
            ('".sql_escape_string($mb_id)."', '".(int) $ci_id."', '".G5_TIME_YMDHIS."')
        on duplicate key update
            ci_id = values(ci_id),
            wp_datetime = values(wp_datetime) ", false);
}

function willow_payment_default_card($mb_id)
{
    $cards = willow_payment_cards($mb_id);
    if (!$cards) {
        return array();
    }

    $default_id = willow_payment_default_id($mb_id);
    foreach ($cards as $card) {
        if ((int) $card['ci_id'] === $default_id) {
            return $card;
        }
    }

    willow_payment_set_default($mb_id, (int) $cards[0]['ci_id']);

    return $cards[0];
}

function willow_toss_client_key()
{
    $key = function_exists('get_subs_option') ? trim((string) get_subs_option('su_tosspayments_api_clientkey')) : '';

    if ($key === '' && function_exists('get_subs_option') && get_subs_option('su_card_test')) {
        $key = 'test_ck_D5GePWvyJnrK0W0k6q8gLzN97Eoq';
    }

    return $key;
}

function willow_toss_secret_key()
{
    $key = function_exists('get_subs_option') ? trim((string) get_subs_option('su_tosspayments_api_secretkey')) : '';

    if ($key === '' && function_exists('get_subs_option') && get_subs_option('su_card_test')) {
        $key = 'test_sk_zXLkKEypNArWmo50nX3lmeaxYG5R';
    }

    return $key;
}

function willow_toss_customer_key($mb_id)
{
    if (function_exists('tosspayments_customerkey_uuidv4')) {
        return tosspayments_customerkey_uuidv4($mb_id);
    }

    $salt = defined('G5_TOKEN_ENCRYPTION_KEY') ? substr(G5_TOKEN_ENCRYPTION_KEY, 0, 9) : '';
    $hash = md5($mb_id.$salt);

    return substr($hash, 0, 8).'_'.substr($hash, 8, 4).'_'.substr($hash, 12, 4).'_'.substr($hash, 16, 4).'_'.substr($hash, 20, 12);
}

function willow_toss_request($url, $payload)
{
    $secret_key = willow_toss_secret_key();
    if ($secret_key === '') {
        return array('success' => false, 'code' => 'EMPTY_TOSS_SECRET_KEY', 'message' => '토스페이먼츠 시크릿키가 설정되어 있지 않습니다.');
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_HTTPHEADER => array(
            'Authorization: Basic '.base64_encode($secret_key.':'),
            'Content-Type: application/json',
        ),
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ));

    $body = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $curl_error) {
        return array('success' => false, 'code' => 'TOSS_CURL_ERROR', 'message' => $curl_error ? $curl_error : '토스페이먼츠 API 호출에 실패했습니다.', 'http_code' => $http_code);
    }

    $json = json_decode($body, true);
    if (!is_array($json)) {
        return array('success' => false, 'code' => 'TOSS_INVALID_RESPONSE', 'message' => '토스페이먼츠 응답을 해석할 수 없습니다.', 'http_code' => $http_code, 'raw' => $body);
    }

    if ($http_code < 200 || $http_code >= 300 || !empty($json['code'])) {
        return array(
            'success' => false,
            'code' => isset($json['code']) ? $json['code'] : 'TOSS_HTTP_'.$http_code,
            'message' => isset($json['message']) ? $json['message'] : '토스페이먼츠 요청에 실패했습니다.',
            'http_code' => $http_code,
            'response' => $json,
        );
    }

    return array('success' => true, 'http_code' => $http_code, 'response' => $json);
}

function willow_toss_issue_billing_key($auth_key, $customer_key)
{
    return willow_toss_request('https://api.tosspayments.com/v1/billing/authorizations/issue', array(
        'authKey' => $auth_key,
        'customerKey' => $customer_key,
    ));
}

function willow_toss_card_name($card)
{
    if (!is_array($card)) {
        return '등록카드';
    }

    if (!empty($card['cardCompany'])) {
        return $card['cardCompany'];
    }

    if (!empty($card['card']['issuerCode']) && function_exists('get_tosspayments_by_cardcode')) {
        return get_tosspayments_by_cardcode($card['card']['issuerCode']);
    }

    if (!empty($card['issuerCode']) && function_exists('get_tosspayments_by_cardcode')) {
        return get_tosspayments_by_cardcode($card['issuerCode']);
    }

    return !empty($card['issuerCode']) ? $card['issuerCode'] : '등록카드';
}

function willow_toss_card_number($response)
{
    if (!empty($response['cardNumber'])) {
        return $response['cardNumber'];
    }

    if (!empty($response['card']['number'])) {
        return $response['card']['number'];
    }

    return '';
}

function willow_toss_card_expiry($response)
{
    $candidates = array();
    if (isset($response['card']) && is_array($response['card'])) {
        $card = $response['card'];
        $candidates[] = isset($card['expiry']) ? $card['expiry'] : '';
        $candidates[] = isset($card['expiration']) ? $card['expiration'] : '';
        if (!empty($card['expireMonth']) && !empty($card['expireYear'])) {
            $candidates[] = sprintf('%02d/%02d', (int) $card['expireMonth'], (int) substr((string) $card['expireYear'], -2));
        }
        if (!empty($card['expirationMonth']) && !empty($card['expirationYear'])) {
            $candidates[] = sprintf('%02d/%02d', (int) $card['expirationMonth'], (int) substr((string) $card['expirationYear'], -2));
        }
    }

    foreach ($candidates as $candidate) {
        $candidate = trim((string) $candidate);
        if ($candidate !== '') {
            return $candidate;
        }
    }

    return '';
}

function willow_toss_order_id($prefix, $mb_id, $target_id = '')
{
    $base = preg_replace('/[^A-Za-z0-9_-]/', '', $prefix.'_'.$mb_id.'_'.$target_id.'_'.G5_SERVER_TIME.'_'.mt_rand(1000, 9999));

    return substr($base, 0, 64);
}

function willow_toss_bill_card($billing_key, $amount, $order_id, $order_name, $member_row)
{
    return willow_toss_request('https://api.tosspayments.com/v1/billing/'.rawurlencode($billing_key), array(
        'customerKey' => willow_toss_customer_key($member_row['mb_id']),
        'amount' => (int) $amount,
        'orderId' => $order_id,
        'orderName' => $order_name,
        'customerEmail' => isset($member_row['mb_email']) ? $member_row['mb_email'] : '',
        'customerName' => isset($member_row['mb_name']) && $member_row['mb_name'] ? $member_row['mb_name'] : (isset($member_row['mb_nick']) ? $member_row['mb_nick'] : ''),
    ));
}
