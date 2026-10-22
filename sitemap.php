<?php
include_once __DIR__.'/common.php';
include_once G5_PATH.'/willow/moderation.lib.php';

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex');
$base = 'https://willlow.org';
$board = $g5['write_prefix'].'free';
$topic = G5_TABLE_PREFIX.'willow_topic_post';
$member_table = $g5['member_table'];
$sources = array(
    'board' => array($board, 'wr_id', "wr_is_comment = 0 AND wr_option NOT LIKE '%secret%' AND wr_3 IN ('', 'free', 'public') AND ".willow_visible_sql('board', 'wr_id')),
    'topic' => array($topic, 'wp_id', "wp_access IN ('', 'free', 'public') AND ".willow_visible_sql('topic', 'wp_id')),
    'author' => array($member_table, 'mb_id', "mb_7 = 'nk_migrant' AND mb_level < 10 AND mb_leave_date = '' AND mb_intercept_date = ''"),
);
function willow_sitemap_escape($value) {
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}
$kind = isset($_GET['kind']) && is_string($_GET['kind']) ? $_GET['kind'] : '';
$page = max(1, (int) ($_GET['page'] ?? 1));
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
if (!isset($sources[$kind]) && $kind !== 'static') {
    echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    echo '<sitemap><loc>'.$base.'/sitemap.php?kind=static</loc></sitemap>';
    foreach ($sources as $name => $source) {
        list($table, $id, $where) = $source;
        $count = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$table}` WHERE {$where}");
        for ($i = 1; $i <= ceil($count['cnt'] / 1000); $i++) {
            echo '<sitemap><loc>'.willow_sitemap_escape($base.'/sitemap.php?kind='.$name.'&page='.$i).'</loc></sitemap>';
        }
    }
    echo '</sitemapindex>';
} else {
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    if ($kind === 'static') {
        foreach (array('/', '/bbs/faq.php', '/bbs/board.php?bo_table=notice', '/willow/refund_policy.php') as $path) {
            echo '<url><loc>'.willow_sitemap_escape($base.$path).'</loc></url>';
        }
    } else {
        list($table, $id, $where) = $sources[$kind];
        $offset = ($page - 1) * 1000;
        $result = sql_query("SELECT `{$id}` FROM `{$table}` WHERE {$where} ORDER BY `{$id}` LIMIT {$offset}, 1000");
        while ($row = sql_fetch_array($result)) {
            $path = $kind === 'author' ? '/willow/author.php?author='.rawurlencode($row[$id]) : '/willow/post.php?'.($kind === 'topic' ? 'wp_id=' : 'wr_id=').(int) $row[$id];
            echo '<url><loc>'.willow_sitemap_escape($base.$path).'</loc></url>';
        }
    }
    echo '</urlset>';
}
