<?php
include_once('./_common.php');
include_once('./topic.lib.php');
include_once('./content.lib.php');

header('Content-Type: application/json; charset=utf-8');

function willow_comment_json($payload)
{
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    willow_comment_json(array('success' => false, 'message' => '잘못된 요청입니다.'));
}

willow_topic_install();

$target_type = isset($_POST['target_type']) ? trim($_POST['target_type']) : '';
$target_id = isset($_POST['target_id']) ? (int) $_POST['target_id'] : 0;
$content = isset($_POST['content']) ? trim(strip_tags($_POST['content'])) : '';

if (!in_array($target_type, array('topic', 'board')) || $target_id < 1) {
    willow_comment_json(array('success' => false, 'message' => '대상을 확인할 수 없습니다.'));
}

if ($content === '') {
    willow_comment_json(array('success' => false, 'message' => '댓글 내용을 입력해주세요.'));
}

$tables = willow_topic_tables();
$count = 0;

if ($target_type === 'topic') {
    $post = sql_fetch(" select wp_id from `{$tables['post']}` where wp_id = '{$target_id}' ");
    if (empty($post['wp_id'])) {
        willow_comment_json(array('success' => false, 'message' => '글을 찾을 수 없습니다.'));
    }
} else {
    $board_table = willow_content_table();
    $post = sql_fetch(" select wr_id from {$board_table} where wr_id = '{$target_id}' and wr_is_comment = 0 ", false);
    if (empty($post['wr_id'])) {
        willow_comment_json(array('success' => false, 'message' => '글을 찾을 수 없습니다.'));
    }
}

$type = sql_escape_string($target_type);
$escaped_content = sql_escape_string($content);
$author = sql_escape_string(willow_interaction_author_name());
$mb_id = isset($member['mb_id']) ? sql_escape_string($member['mb_id']) : '';
$now = G5_TIME_YMDHIS;

sql_query(" insert into `{$tables['comment']}`
    set target_type = '{$type}',
        target_id = '{$target_id}',
        mb_id = '{$mb_id}',
        wc_author = '{$author}',
        wc_content = '{$escaped_content}',
        wc_datetime = '{$now}' ");

if ($target_type === 'topic') {
    sql_query(" update `{$tables['post']}` set wp_comment = wp_comment + 1 where wp_id = '{$target_id}' ");
} else {
    sql_query(" update {$board_table} set wr_comment = wr_comment + 1 where wr_id = '{$target_id}' ");
}

$count = willow_comment_count($target_type, $target_id);
$comment_member = !empty($member['mb_id']) ? $member : array();
willow_comment_json(array(
    'success' => true,
    'count' => number_format($count),
    'comment' => array(
        'author' => get_text(willow_interaction_author_name()),
        'avatar' => willow_member_avatar($comment_member),
        'content' => get_text($content),
        'date' => substr($now, 0, 16),
    ),
));
