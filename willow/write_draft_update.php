<?php
include_once('./_common.php');
include_once('./topic.lib.php');

header('Content-Type: application/json; charset=utf-8');

if (!$is_member) {
    echo json_encode(array('success' => false, 'message' => '로그인 후 이용해주세요.'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('success' => false, 'message' => '잘못된 요청입니다.'));
    exit;
}

willow_topic_install();

$tables = willow_topic_tables();
$wd_id = isset($_POST['wd_id']) ? (int) $_POST['wd_id'] : 0;
$wt_id = isset($_POST['wt_id']) ? (int) $_POST['wt_id'] : 0;
$topic = sql_fetch(" select * from `{$tables['topic']}` where wt_id = '{$wt_id}' ", false);

if (empty($topic['wt_id']) || !willow_topic_is_visible($topic)) {
    echo json_encode(array('success' => false, 'message' => '오늘의 주제를 찾을 수 없습니다.'));
    exit;
}

$topic_mode = isset($_POST['topic_mode']) && $_POST['topic_mode'] === 'free' ? 'free' : 'today';
$subject = isset($_POST['wp_subject']) ? trim(strip_tags($_POST['wp_subject'])) : '';
$content = isset($_POST['wp_content']) ? trim(strip_tags($_POST['wp_content'])) : '';
$tags = isset($_POST['wp_tags']) ? trim(strip_tags($_POST['wp_tags'])) : '';
$access = isset($_POST['wp_access']) && $_POST['wp_access'] === 'subscriber' ? 'subscriber' : 'public';
$mb_id = sql_escape_string($member['mb_id']);
$now = G5_TIME_YMDHIS;
$image_upload_limit = 5 * 1024 * 1024;

if ($subject === '' && $content === '' && $tags === '' && empty($_FILES['wp_images']['name'])) {
    echo json_encode(array('success' => false, 'message' => '저장할 내용이 없습니다.'));
    exit;
}

$existing_images = array();
if ($wd_id) {
    $draft = sql_fetch(" select * from `{$tables['draft']}` where wd_id = '{$wd_id}' and mb_id = '{$mb_id}' ", false);
    if (!empty($draft['wd_id']) && !empty($draft['wd_images'])) {
        $existing_images = array_filter(explode('|', $draft['wd_images']));
    } else {
        $wd_id = 0;
    }
}

$images = $existing_images;
if (isset($_FILES['wp_images']['name']) && is_array($_FILES['wp_images']['name'])) {
    $upload_dir = G5_DATA_PATH.'/willow_draft';
    @mkdir($upload_dir, G5_DIR_PERMISSION);
    @chmod($upload_dir, G5_DIR_PERMISSION);

    foreach ($_FILES['wp_images']['name'] as $idx => $filename) {
        if (count($images) >= 4) {
            break;
        }
        if (!$filename) {
            continue;
        }

        $upload_error = isset($_FILES['wp_images']['error'][$idx]) ? (int) $_FILES['wp_images']['error'][$idx] : UPLOAD_ERR_OK;
        $filesize = isset($_FILES['wp_images']['size'][$idx]) ? (int) $_FILES['wp_images']['size'][$idx] : 0;
        if ($upload_error === UPLOAD_ERR_INI_SIZE || $upload_error === UPLOAD_ERR_FORM_SIZE || $filesize > $image_upload_limit) {
            echo json_encode(array('success' => false, 'message' => '이미지는 5MB 이하 파일만 첨부할 수 있습니다.'));
            exit;
        }
        if ($upload_error !== UPLOAD_ERR_OK) {
            echo json_encode(array('success' => false, 'message' => '이미지가 정상적으로 업로드되지 않았습니다. 다시 첨부해 주세요.'));
            exit;
        }
        if (empty($_FILES['wp_images']['tmp_name'][$idx]) || !is_uploaded_file($_FILES['wp_images']['tmp_name'][$idx])) {
            continue;
        }

        $tmp_file = $_FILES['wp_images']['tmp_name'][$idx];
        $image_info = @getimagesize($tmp_file);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!$image_info || !preg_match('/^(jpe?g|gif|png|webp)$/i', $ext)) {
            continue;
        }

        $safe_name = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($filename));
        $save_name = md5(uniqid('', true)).'_'.$safe_name;
        $dest_file = $upload_dir.'/'.$save_name;

        if (move_uploaded_file($tmp_file, $dest_file)) {
            @chmod($dest_file, G5_FILE_PERMISSION);
            $images[] = G5_DATA_URL.'/willow_draft/'.$save_name;
        }
    }
}

$image_value = implode('|', array_slice($images, 0, 4));

if ($wd_id) {
    sql_query(" update `{$tables['draft']}`
        set wt_id = '{$wt_id}',
            wd_topic_mode = '".sql_escape_string($topic_mode)."',
            wd_subject = '".sql_escape_string($subject)."',
            wd_content = '".sql_escape_string($content)."',
            wd_tags = '".sql_escape_string($tags)."',
            wd_access = '".sql_escape_string($access)."',
            wd_images = '".sql_escape_string($image_value)."',
            wd_update_datetime = '{$now}'
        where wd_id = '{$wd_id}' and mb_id = '{$mb_id}' ", false);
} else {
    sql_query(" insert into `{$tables['draft']}`
        set wt_id = '{$wt_id}',
            mb_id = '{$mb_id}',
            wd_topic_mode = '".sql_escape_string($topic_mode)."',
            wd_subject = '".sql_escape_string($subject)."',
            wd_content = '".sql_escape_string($content)."',
            wd_tags = '".sql_escape_string($tags)."',
            wd_access = '".sql_escape_string($access)."',
            wd_images = '".sql_escape_string($image_value)."',
            wd_datetime = '{$now}',
            wd_update_datetime = '{$now}' ", false);
    $wd_id = sql_insert_id();
}

echo json_encode(array(
    'success' => true,
    'message' => '임시저장되었습니다.',
    'draft_id' => (int) $wd_id,
    'saved_at' => substr($now, 0, 16),
    'images' => array_slice($images, 0, 4),
));
exit;
