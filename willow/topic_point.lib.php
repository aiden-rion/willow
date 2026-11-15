<?php
if (!defined('_GNUBOARD_')) exit;

include_once G5_PATH.'/willow/content.lib.php';

function willow_award_daily_topic_point($member_row, $topic_mode, $post_id)
{
    if ($topic_mode !== 'today' || (int) $post_id < 1 || empty($member_row['mb_id']) || !willow_author_is_escapee($member_row)) {
        return 0;
    }

    // Serialize duplicate submissions before insert_point checks its relation key.
    $mb_id = sql_escape_string($member_row['mb_id']);
    $day = G5_TIME_YMD;
    $lock = 'willow_topic_point:'.sha1($member_row['mb_id'].':'.$day);
    $acquired = sql_fetch(" select get_lock('{$lock}', 10) as acquired ", false);
    if (empty($acquired['acquired'])) {
        error_log('WILLOW daily topic point lock failed for post '.(int) $post_id);
        return 0;
    }

    try {
        return insert_point($mb_id, 100, '오늘의 주제 작성 보상 (하루 1회)', 'willow_daily_topic', $day, 'write');
    } finally {
        sql_query(" select release_lock('{$lock}') ", false);
    }
}
