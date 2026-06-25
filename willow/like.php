<?php
include_once('./_common.php');
include_once('./topic.lib.php');
include_once('./content.lib.php');

header('Content-Type: application/json; charset=utf-8');

function willow_like_json($payload)
{
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    willow_like_json(array('success' => false, 'message' => '잘못된 요청입니다.'));
}

willow_topic_install();

$target_type = isset($_POST['target_type']) ? trim($_POST['target_type']) : '';
$target_id = isset($_POST['target_id']) ? (int) $_POST['target_id'] : 0;

if (!in_array($target_type, array('topic', 'board')) || $target_id < 1) {
    willow_like_json(array('success' => false, 'message' => '대상을 확인할 수 없습니다.'));
}

$tables = willow_topic_tables();
$count = 0;

if ($target_type === 'topic') {
    $post = sql_fetch(" select wp_id, wp_like from `{$tables['post']}` where wp_id = '{$target_id}' ");
    if (empty($post['wp_id'])) {
        willow_like_json(array('success' => false, 'message' => '글을 찾을 수 없습니다.'));
    }
} else {
    $board_table = willow_content_table();
    $post = sql_fetch(" select wr_id, wr_good from {$board_table} where wr_id = '{$target_id}' and wr_is_comment = 0 ", false);
    if (empty($post['wr_id'])) {
        willow_like_json(array('success' => false, 'message' => '글을 찾을 수 없습니다.'));
    }
}

$viewer_key = sql_escape_string(willow_interaction_viewer_key());
$mb_id = isset($member['mb_id']) ? sql_escape_string($member['mb_id']) : '';
$type = sql_escape_string($target_type);
$liked_row = sql_fetch(" select wl_id from `{$tables['like']}` where target_type = '{$type}' and target_id = '{$target_id}' and viewer_key = '{$viewer_key}' ");

if (!empty($liked_row['wl_id'])) {
    sql_query(" delete from `{$tables['like']}` where wl_id = '".(int) $liked_row['wl_id']."' ");
    $liked = false;

    if ($target_type === 'topic') {
        sql_query(" update `{$tables['post']}` set wp_like = if(wp_like > 0, wp_like - 1, 0) where wp_id = '{$target_id}' ");
    } else {
        sql_query(" update {$board_table} set wr_good = if(wr_good > 0, wr_good - 1, 0) where wr_id = '{$target_id}' ");
    }
} else {
    sql_query(" insert into `{$tables['like']}`
        set target_type = '{$type}',
            target_id = '{$target_id}',
            viewer_key = '{$viewer_key}',
            mb_id = '{$mb_id}',
            wl_datetime = '".G5_TIME_YMDHIS."' ");
    $liked = true;

    if ($target_type === 'topic') {
        sql_query(" update `{$tables['post']}` set wp_like = wp_like + 1 where wp_id = '{$target_id}' ");
    } else {
        sql_query(" update {$board_table} set wr_good = wr_good + 1 where wr_id = '{$target_id}' ");
    }
}

$count = willow_like_count($target_type, $target_id);
willow_like_json(array(
    'success' => true,
    'liked' => $liked,
    'count' => number_format($count),
));
