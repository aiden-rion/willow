<?php
if (!defined('_GNUBOARD_')) exit;

if (defined('G5_IS_ADMIN')) {
    return;
}

$willow_script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
$willow_allowed_paths = array(
    '/bbs/login.php',
    '/bbs/login_check.php',
    '/bbs/logout.php',
    '/bbs/search.php',
    '/willow/auth_login.php',
    '/willow/menu.php',
    '/willow/splash.php',
);

$willow_is_allowed = in_array($willow_script, $willow_allowed_paths, true)
    || strpos($willow_script, '/adm/') === 0
    || strpos($willow_script, '/install/') === 0;

if (!$is_member && !$willow_is_allowed) {
    if (
        isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) {
        http_response_code(401);
        exit;
    }

    $willow_return_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : G5_URL;
    if (get_cookie('willow_splash_seen') !== '1') {
        goto_url(G5_URL.'/willow/splash.php?url='.urlencode($willow_return_url));
    }

    goto_url(G5_BBS_URL.'/login.php?url='.urlencode($willow_return_url));
}
