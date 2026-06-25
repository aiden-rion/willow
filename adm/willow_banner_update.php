<?php
$sub_menu = '700300';
require_once './_common.php';
include_once G5_PATH.'/willow/content.lib.php';

check_demo();
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();
willow_banner_install();

$table = willow_banner_table();
$positions = willow_banner_positions();
$upload_dir = willow_banner_upload_dir();
$max_size = 5 * 1024 * 1024;
$allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');
$allowed_mime = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
$action_mode = isset($_POST['action_mode']) && $_POST['action_mode'] === 'add' ? 'add' : 'update';

if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, G5_DIR_PERMISSION, true);
    @chmod($upload_dir, G5_DIR_PERMISSION);
}

function willow_banner_upload_file($field, $upload_dir, $max_size, $allowed_ext, $allowed_mime)
{
    if (empty($_FILES[$field]['name'])) {
        return '';
    }

    if (!empty($_FILES[$field]['error'])) {
        alert('배너 이미지 업로드 중 오류가 발생했습니다. 오류코드: '.$_FILES[$field]['error']);
    }

    if ((int) $_FILES[$field]['size'] > $max_size) {
        alert('배너 이미지는 5MB 이하만 업로드할 수 있습니다.');
    }

    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext, true)) {
        alert('JPG, PNG, GIF, WebP 이미지 파일만 업로드할 수 있습니다.');
    }

    $image_info = @getimagesize($_FILES[$field]['tmp_name']);
    if (!$image_info || empty($image_info['mime']) || !in_array($image_info['mime'], $allowed_mime, true)) {
        alert('정상적인 이미지 파일만 업로드할 수 있습니다.');
    }

    $filename = 'banner_'.date('YmdHis').'_'.bin2hex(random_bytes(4)).'.'.$ext;
    $dest = $upload_dir.'/'.$filename;

    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) {
        alert('배너 이미지 저장에 실패했습니다.');
    }

    @chmod($dest, G5_FILE_PERMISSION);

    return $filename;
}

function willow_banner_upload_new_file($index, $upload_dir, $max_size, $allowed_ext, $allowed_mime)
{
    if (empty($_FILES['new_wb_image']['name'][$index])) {
        return '';
    }

    $file = array(
        'name' => $_FILES['new_wb_image']['name'][$index],
        'type' => $_FILES['new_wb_image']['type'][$index],
        'tmp_name' => $_FILES['new_wb_image']['tmp_name'][$index],
        'error' => $_FILES['new_wb_image']['error'][$index],
        'size' => $_FILES['new_wb_image']['size'][$index],
    );

    $_FILES['_willow_new_banner'] = $file;
    $filename = willow_banner_upload_file('_willow_new_banner', $upload_dir, $max_size, $allowed_ext, $allowed_mime);
    unset($_FILES['_willow_new_banner']);

    return $filename;
}

function willow_banner_delete_uploaded_image($image, $upload_dir)
{
    $image = trim((string) $image);
    if ($image === '' || preg_match('#^https?://#i', $image) || strpos($image, 'img/') === 0) {
        return;
    }

    @unlink($upload_dir.'/'.basename($image));
}

$ids = isset($_POST['wb_id']) ? $_POST['wb_id'] : array();
$positions_post = isset($_POST['wb_position']) ? $_POST['wb_position'] : array();
$sorts = isset($_POST['wb_sort']) ? $_POST['wb_sort'] : array();
$titles = isset($_POST['wb_title']) ? $_POST['wb_title'] : array();
$alts = isset($_POST['wb_alt']) ? $_POST['wb_alt'] : array();
$urls = isset($_POST['wb_url']) ? $_POST['wb_url'] : array();
$active = isset($_POST['wb_active']) ? $_POST['wb_active'] : array();
$new_win = isset($_POST['wb_new_win']) ? $_POST['wb_new_win'] : array();
$delete = isset($_POST['wb_delete']) ? $_POST['wb_delete'] : array();
$image_delete = isset($_POST['wb_image_delete']) ? $_POST['wb_image_delete'] : array();

if ($action_mode === 'update') {
    foreach ($ids as $index => $id) {
        $id = (int) $id;
        if (!$id) {
            continue;
        }

        $row = sql_fetch(" select * from `{$table}` where wb_id = '{$id}' ", false);
        if (empty($row['wb_id'])) {
            continue;
        }

        if (!empty($delete[$id])) {
            willow_banner_delete_uploaded_image($row['wb_image'], $upload_dir);
            sql_query(" delete from `{$table}` where wb_id = '{$id}' ");
            continue;
        }

        $position = isset($positions_post[$index]) && isset($positions[$positions_post[$index]]) ? $positions_post[$index] : 'home';
        $sort = isset($sorts[$index]) ? (int) $sorts[$index] : 0;
        $title = isset($titles[$index]) ? trim(strip_tags($titles[$index])) : '';
        $alt = isset($alts[$index]) ? trim(strip_tags($alts[$index])) : '';
        $url = isset($urls[$index]) ? trim(strip_tags($urls[$index])) : '';
        $is_active = !empty($active[$id]) ? 1 : 0;
        $is_new_win = !empty($new_win[$id]) ? 1 : 0;
        $image = $row['wb_image'];

        if (!empty($image_delete[$id]) && $image) {
            willow_banner_delete_uploaded_image($image, $upload_dir);
            $image = '';
        }

        $uploaded = willow_banner_upload_file('wb_image_'.$id, $upload_dir, $max_size, $allowed_ext, $allowed_mime);
        if ($uploaded !== '') {
            willow_banner_delete_uploaded_image($image, $upload_dir);
            $image = $uploaded;
        }

        sql_query(" update `{$table}`
            set wb_position = '".sql_escape_string($position)."',
                wb_title = '".sql_escape_string($title)."',
                wb_alt = '".sql_escape_string($alt)."',
                wb_image = '".sql_escape_string($image)."',
                wb_url = '".sql_escape_string($url)."',
                wb_new_win = '{$is_new_win}',
                wb_sort = '{$sort}',
                wb_active = '{$is_active}'
            where wb_id = '{$id}' ");
    }

    goto_url('./willow_banner.php');
}

$new_positions = isset($_POST['new_wb_position']) ? $_POST['new_wb_position'] : array();
$new_sorts = isset($_POST['new_wb_sort']) ? $_POST['new_wb_sort'] : array();
$new_titles = isset($_POST['new_wb_title']) ? $_POST['new_wb_title'] : array();
$new_alts = isset($_POST['new_wb_alt']) ? $_POST['new_wb_alt'] : array();
$new_urls = isset($_POST['new_wb_url']) ? $_POST['new_wb_url'] : array();
$new_active = isset($_POST['new_wb_active']) ? $_POST['new_wb_active'] : array();
$new_new_win = isset($_POST['new_wb_new_win']) ? $_POST['new_wb_new_win'] : array();
$now = G5_TIME_YMDHIS;
$added_count = 0;

foreach ($new_positions as $index => $position) {
    $position = isset($positions[$position]) ? $position : 'home';
    $sort = isset($new_sorts[$index]) ? (int) $new_sorts[$index] : 0;
    $title = isset($new_titles[$index]) ? trim(strip_tags($new_titles[$index])) : '';
    $alt = isset($new_alts[$index]) ? trim(strip_tags($new_alts[$index])) : '';
    $url = isset($new_urls[$index]) ? trim(strip_tags($new_urls[$index])) : '';
    $is_active = !empty($new_active[$index]) ? 1 : 0;
    $is_new_win = !empty($new_new_win[$index]) ? 1 : 0;
    $image = willow_banner_upload_new_file($index, $upload_dir, $max_size, $allowed_ext, $allowed_mime);

    if ($title === '' && $alt === '' && $url === '' && $image === '') {
        continue;
    }

    if ($image === '') {
        alert('새 배너를 추가하려면 이미지를 등록해주세요.');
    }

    sql_query(" insert into `{$table}`
        set wb_position = '".sql_escape_string($position)."',
            wb_title = '".sql_escape_string($title)."',
            wb_alt = '".sql_escape_string($alt)."',
            wb_image = '".sql_escape_string($image)."',
            wb_url = '".sql_escape_string($url)."',
            wb_new_win = '{$is_new_win}',
            wb_sort = '{$sort}',
            wb_active = '{$is_active}',
            wb_datetime = '{$now}' ");
    $added_count++;
}

if ($added_count < 1) {
    alert('추가할 배너 정보를 입력해주세요.', './willow_banner.php');
}

goto_url('./willow_banner.php');
