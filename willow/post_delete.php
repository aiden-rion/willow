<?php
include_once('./_common.php');
include_once('./topic.lib.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$is_member) {
    alert('로그인 후 다시 시도해주세요.', G5_URL.'/willow/today.php');
}
if (!get_session('willow_post_token') || !hash_equals(get_session('willow_post_token'), (string) ($_POST['token'] ?? ''))) {
    alert('잘못된 요청입니다.');
}
$tables = willow_topic_tables();
$id = (int) ($_POST['wp_id'] ?? 0);
$owner = sql_escape_string($member['mb_id']);
$post = sql_fetch("select * from `{$tables['post']}` where wp_id = '{$id}' and mb_id = '{$owner}'");
if (empty($post['wp_id'])) alert('본인의 글만 삭제할 수 있습니다.');
sql_query("delete from `{$tables['post']}` where wp_id = '{$id}' and mb_id = '{$owner}'");
$topic_id = (int) $post['wt_id'];
sql_query("update `{$tables['topic']}` set wt_participants = (select count(*) from `{$tables['post']}` where wt_id = '{$topic_id}' and (wp_topic_mode = '' or wp_topic_mode = 'today')) where wt_id = '{$topic_id}'");
goto_url(G5_URL.'/willow/today.php?wt_id='.$topic_id);
