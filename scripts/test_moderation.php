<?php
// Uses a connection-local shadow table; never changes production moderation or posts.
if (PHP_SAPI !== 'cli') exit;
$_SERVER['SCRIPT_NAME'] = $_SERVER['REQUEST_URI'] = '/adm/check.php';
$_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'willlow.org';
$_SERVER['SERVER_PORT'] = 443;
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require dirname(__DIR__).'/common.php';
require_once G5_PATH.'/willow/content.lib.php';
require_once G5_PATH.'/willow/topic.lib.php';
$table = willow_moderation_table();
sql_query("CREATE TEMPORARY TABLE `{$table}` LIKE `{$table}`");
foreach (array('board', 'topic') as $type) {
    $tables = willow_topic_tables();
    $source = $type === 'board' ? willow_content_table() : $tables['post'];
    $id_column = $type === 'board' ? 'wr_id' : 'wp_id';
    $where = $type === 'board' ? 'wr_is_comment = 0' : '1';
    $row = sql_fetch("SELECT {$id_column} AS id FROM `{$source}` WHERE {$where} LIMIT 1");
    if (empty($row['id'])) throw new Exception('Need an existing '.$type.' post for read-only verification');
    $id = (int) $row['id'];
    sql_query("INSERT INTO `{$table}` VALUES ('{$type}', {$id}, 1, 'test', NOW())");
    if (!willow_post_blocked($type, $id)) throw new Exception('Block lookup failed');
    $visible = willow_visible_sql($type, $id_column);
    $row = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$source}` WHERE {$id_column} = {$id} AND {$visible}");
    if ((int) $row['cnt'] !== 0) throw new Exception('Blocked post leaked');
    $other = $type === 'board' ? 'topic' : 'board';
    if (willow_post_blocked($other, $id)) throw new Exception('Target types collided');
    sql_query("UPDATE `{$table}` SET blocked = 0 WHERE target_type = '{$type}' AND target_id = {$id}");
    $row = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$source}` WHERE {$id_column} = {$id} AND {$visible}");
    if ((int) $row['cnt'] !== 1 || willow_post_blocked($type, $id)) throw new Exception('Restore failed');
    echo $type.": block, isolation, SQL filter, restore PASS\n";
}
willow_get_board_posts(2);
willow_get_topic_search_posts(2, '오늘');
willow_get_mixed_feed_posts(0, 2);
willow_get_author_topic_posts('test', '', 0, 2);
willow_get_topic_posts(1, 2);
echo "Feed queries PASS\n";
