<?php
if (!defined('_GNUBOARD_')) exit;

function willow_moderation_table()
{
    return G5_TABLE_PREFIX.'willow_moderation';
}

function willow_moderation_install()
{
    $table = willow_moderation_table();
    return sql_query("CREATE TABLE IF NOT EXISTS `{$table}` (
        target_type varchar(10) NOT NULL,
        target_id int unsigned NOT NULL,
        blocked tinyint NOT NULL DEFAULT 0,
        admin_id varchar(100) NOT NULL DEFAULT '',
        updated_at datetime NOT NULL,
        PRIMARY KEY (target_type, target_id),
        KEY blocked_targets (target_type, blocked, target_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);
}

// Column expressions are supplied only by application code, never request input.
function willow_visible_sql($type, $column)
{
    $table = willow_moderation_table();
    $type = $type === 'topic' ? 'topic' : 'board';
    return " {$column} NOT IN (SELECT target_id FROM `{$table}` WHERE target_type = '{$type}' AND blocked = 1) ";
}

function willow_post_blocked($type, $id)
{
    $table = willow_moderation_table();
    $type = $type === 'topic' ? 'topic' : 'board';
    $id = (int) $id;
    $row = sql_fetch("SELECT blocked FROM `{$table}` WHERE target_type = '{$type}' AND target_id = {$id}", false);
    return !empty($row['blocked']);
}

function willow_require_visible_post($type, $id)
{
    if ($id && willow_post_blocked($type, $id)) {
        alert('관리자에 의해 게시중단된 글입니다.', G5_URL);
        exit;
    }
}
