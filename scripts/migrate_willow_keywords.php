<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
chdir(dirname(__DIR__));
$_SERVER['SCRIPT_NAME'] = '/adm/keyword_migration.php';
include_once('./common.php');
include_once(G5_PATH.'/willow/topic.lib.php');
$table = G5_TABLE_PREFIX.'willow_keyword';
sql_query("CREATE TABLE IF NOT EXISTS `{$table}` (
    keyword varchar(30) NOT NULL,
    PRIMARY KEY (keyword)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$tables = willow_topic_tables();
$result = sql_query("SELECT DISTINCT wp_tags FROM `{$tables['post']}` WHERE wp_tags <> ''");
while ($row = sql_fetch_array($result)) {
    foreach (explode(',', $row['wp_tags']) as $tag) {
        willow_register_keyword(willow_free_keyword($tag));
    }
}
echo "Keyword migration complete.\n";
