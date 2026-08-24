<?php
include_once('./_common.php');
include_once('./topic.lib.php');
include_once('./content.lib.php');

header('Content-Type: application/json; charset=utf-8');

$offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 6;
$seed = isset($_GET['seed']) ? preg_replace('/[^A-Za-z0-9_-]/', '', $_GET['seed']) : '';
$seen_keys = array();
if (!empty($_GET['seen'])) {
    foreach (explode(',', $_GET['seen']) as $seen_key) {
        $seen_key = preg_replace('/[^a-z0-9:_-]/i', '', $seen_key);
        if ($seen_key !== '') {
            $seen_keys[] = $seen_key;
        }
    }
}
$offset = max(0, $offset);
$limit = min(12, max(1, $limit));
$effective_offset = $seen_keys ? 0 : $offset;

$items = willow_get_personalized_feed($effective_offset, $limit + 1, $seed, $seen_keys);
$has_more = count($items) > $limit;
if ($has_more) {
    $items = array_slice($items, 0, $limit);
}
willow_record_feed_impressions($items);

$html = '';
foreach ($items as $item) {
    $html .= willow_render_post_card($item);
}

echo json_encode(array(
    'success' => true,
    'html' => $html,
    'count' => count($items),
    'has_more' => $has_more,
));
