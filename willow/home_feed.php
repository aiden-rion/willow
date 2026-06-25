<?php
include_once('./_common.php');
include_once('./topic.lib.php');
include_once('./content.lib.php');

header('Content-Type: application/json; charset=utf-8');

$offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 6;
$offset = max(0, $offset);
$limit = min(12, max(1, $limit));

$items = willow_get_personalized_feed($offset, $limit + 1);
$has_more = count($items) > $limit;
if ($has_more) {
    $items = array_slice($items, 0, $limit);
}

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
