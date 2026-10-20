<?php
include_once('./_common.php');
include_once('./topic.lib.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    goto_url(G5_URL.'/willow/today.php');
}

willow_topic_install();

$tables = willow_topic_tables();
$edit_id = isset($_POST['wp_id']) ? (int) $_POST['wp_id'] : 0;
if ($edit_id) {
    $edit_post = sql_fetch("select * from `{$tables['post']}` where wp_id = '{$edit_id}'");
    if (!$is_member || empty($edit_post['mb_id']) || $edit_post['mb_id'] !== $member['mb_id']) alert('본인의 글만 수정할 수 있습니다.');
    if (!get_session('willow_post_token') || !hash_equals(get_session('willow_post_token'), (string) ($_POST['token'] ?? ''))) alert('잘못된 요청입니다.');
    $_POST['wt_id'] = $edit_post['wt_id'];
    $_POST['topic_mode'] = $edit_post['wp_topic_mode'] === 'free' ? 'free' : 'today';
}
$wt_id = isset($_POST['wt_id']) ? (int) $_POST['wt_id'] : 0;
$wd_id = isset($_POST['wd_id']) ? (int) $_POST['wd_id'] : 0;
$topic = sql_fetch(" select * from `{$tables['topic']}` where wt_id = '{$wt_id}' ");

if (empty($topic['wt_id'])) {
    alert('오늘의 주제를 찾을 수 없습니다.', G5_URL.'/willow/today.php');
}

if (!willow_topic_is_visible($topic)) {
    alert('아직 노출되지 않은 오늘의 주제입니다.', G5_URL.'/willow/today.php');
}

$wp_subject = isset($_POST['wp_subject']) ? trim(strip_tags($_POST['wp_subject'])) : '';
$wp_content = isset($_POST['wp_content']) ? trim(strip_tags($_POST['wp_content'])) : '';
$topic_mode = isset($_POST['topic_mode']) && $_POST['topic_mode'] === 'free' ? 'free' : 'today';
$wp_category = '';
$keyword_list = $topic_mode === 'free' ? willow_input_keywords(isset($_POST['wp_tags']) ? $_POST['wp_tags'] : '') : array();
$wp_tags = $keyword_list === false ? '' : implode(',', $keyword_list);
if ($topic_mode === 'free' && !empty($_POST['wp_tags']) && $wp_tags === '') {
    alert('키워드는 각각 30자 이내로 최대 5개까지 입력해주세요.');
}
$can_subscriber_access = !empty($member['mb_7']) && $member['mb_7'] === 'nk_migrant';
$wp_access = $can_subscriber_access && isset($_POST['wp_access']) && $_POST['wp_access'] === 'subscriber' ? 'subscriber' : 'public';
$wp_images = array();
$image_upload_limit = 5 * 1024 * 1024;

if (isset($_POST['existing_images']) && is_array($_POST['existing_images'])) {
    foreach ($_POST['existing_images'] as $image_url) {
        $image_url = trim($image_url);
        if ($image_url === '') {
            continue;
        }
        if (strpos($image_url, G5_DATA_URL.'/willow_draft/') === 0 || strpos($image_url, G5_DATA_URL.'/willow_topic/') === 0) {
            $wp_images[] = $image_url;
        }
        if (count($wp_images) >= 4) {
            break;
        }
    }
}

if (!$wp_subject) {
    alert('제목을 입력해 주세요.');
}

if (!$wp_content) {
    alert('내용을 입력해 주세요.');
}

if (mb_strlen($wp_content, 'UTF-8') > 5000) {
    alert('5000자 이내로 작성해 주세요.');
}

$mb_id = isset($member['mb_id']) ? $member['mb_id'] : '';
$author = !empty($member['mb_nick']) ? $member['mb_nick'] : (!empty($member['mb_name']) ? $member['mb_name'] : '윌로우 회원');
$now = G5_TIME_YMDHIS;

if (isset($_FILES['wp_images']['name']) && is_array($_FILES['wp_images']['name'])) {
    $upload_dir = G5_DATA_PATH.'/willow_topic';
    @mkdir($upload_dir, G5_DIR_PERMISSION);
    @chmod($upload_dir, G5_DIR_PERMISSION);

    foreach ($_FILES['wp_images']['name'] as $idx => $filename) {
        if (count($wp_images) >= 4) {
            break;
        }
        if (!$filename) {
            continue;
        }

        $upload_error = isset($_FILES['wp_images']['error'][$idx]) ? (int) $_FILES['wp_images']['error'][$idx] : UPLOAD_ERR_OK;
        $filesize = isset($_FILES['wp_images']['size'][$idx]) ? (int) $_FILES['wp_images']['size'][$idx] : 0;
        if ($upload_error === UPLOAD_ERR_INI_SIZE || $upload_error === UPLOAD_ERR_FORM_SIZE || $filesize > $image_upload_limit) {
            alert('이미지는 5MB 이하 파일만 첨부할 수 있습니다.');
        }
        if ($upload_error !== UPLOAD_ERR_OK) {
            alert('이미지가 정상적으로 업로드되지 않았습니다. 다시 첨부해 주세요.');
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
            $wp_images[] = G5_DATA_URL.'/willow_topic/'.$save_name;
        }
    }
}

$wp_image = implode('|', $wp_images);

if ($edit_id) {
    sql_query("update `{$tables['post']}` set
        wp_subject = '".sql_escape_string($wp_subject)."',
        wp_content = '".sql_escape_string($wp_content)."',
        wp_image = '".sql_escape_string($wp_image)."',
        wp_access = '".sql_escape_string($wp_access)."',
        wp_tags = '".sql_escape_string($wp_tags)."'
        where wp_id = '{$edit_id}' and mb_id = '".sql_escape_string($mb_id)."'");
    foreach ($keyword_list as $keyword) willow_register_keyword($keyword);
    goto_url(G5_URL.'/willow/post.php?wp_id='.$edit_id);
}

sql_query(" insert into `{$tables['post']}`
    set wt_id = '{$wt_id}',
        mb_id = '".sql_escape_string($mb_id)."',
        wp_author = '".sql_escape_string($author)."',
        wp_subject = '".sql_escape_string($wp_subject)."',
        wp_content = '".sql_escape_string($wp_content)."',
        wp_image = '".sql_escape_string($wp_image)."',
        wp_access = '".sql_escape_string($wp_access)."',
        wp_topic_mode = '".sql_escape_string($topic_mode)."',
        wp_category = '".sql_escape_string($wp_category)."',
        wp_tags = '".sql_escape_string($wp_tags)."',
        wp_datetime = '{$now}' ");
$wp_id = sql_insert_id();
if ($wp_id) foreach ($keyword_list as $keyword) willow_register_keyword($keyword);

if ($topic_mode === 'today') {
    sql_query(" update `{$tables['topic']}` set wt_participants = wt_participants + 1 where wt_id = '{$wt_id}' ");
}

if ($wd_id && !empty($member['mb_id'])) {
    sql_query(" delete from `{$tables['draft']}`
        where wd_id = '{$wd_id}'
            and mb_id = '".sql_escape_string($member['mb_id'])."' ", false);
}

if ($wp_id) {
    goto_url(G5_URL.'/willow/post.php?wp_id='.(int) $wp_id);
}

goto_url(G5_URL.'/willow/today.php');
