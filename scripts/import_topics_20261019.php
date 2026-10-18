<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
chdir(dirname(__DIR__));
$_SERVER['SCRIPT_NAME'] = '/adm/topic_import.php';
$_SERVER['SERVER_PORT'] = 443;
$_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = 'willlow.org';
$_SERVER['REQUEST_URI'] = '/adm/topic_import.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
include './common.php';
$words = json_decode(file_get_contents(__DIR__.'/topics_20261019.json'), true);
if (!is_array($words) || count($words) !== 300) throw new RuntimeException('Expected 300 topics.');
$table = G5_TABLE_PREFIX.'willow_topic';
$start = new DateTimeImmutable('2026-10-19', new DateTimeZone('Asia/Seoul'));
$apply = in_array('--apply', $argv, true);
$pending = array();
foreach ($words as $offset => $word) {
    $day = $start->modify('+'.$offset.' days')->format('Y-m-d');
    $result = sql_query("SELECT wt_subject, wt_active, wt_publish_datetime FROM `{$table}` WHERE wt_date = '{$day}'");
    $rows = array();
    while ($row = sql_fetch_array($result)) $rows[] = $row;
    if ($rows) {
        if (count($rows) !== 1 || $rows[0]['wt_subject'] !== $word || !$rows[0]['wt_active'] || $rows[0]['wt_publish_datetime'] !== $day.' 00:00:00') {
            throw new RuntimeException('Existing schedule conflict: '.$day);
        }
        continue;
    }
    $pending[$day] = $word;
}
echo count($pending)." topics to insert; 2026-10-19 through 2027-08-14\n";
if (!$apply) exit;
sql_query('START TRANSACTION');
try {
    foreach ($pending as $day => $word) {
        $subject = sql_escape_string($word);
        if (!sql_query("INSERT INTO `{$table}` (wt_subject, wt_date, wt_publish_datetime, wt_participants, wt_description, wt_active, wt_datetime)
            VALUES ('{$subject}', '{$day}', '{$day} 00:00:00', 0, '', 1, '".G5_TIME_YMDHIS."')", false)) {
            throw new RuntimeException('Insert failed: '.$day);
        }
    }
    sql_query('COMMIT');
} catch (Throwable $error) {
    sql_query('ROLLBACK');
    throw $error;
}
echo "Import complete.\n";
