<?php
$sub_menu = '700200';
require_once './_common.php';
include_once G5_PATH.'/willow/content.lib.php';

check_demo();
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();
willow_category_install();

$table = willow_category_table();
$ids = isset($_POST['wc_id']) ? $_POST['wc_id'] : array();
$sorts = isset($_POST['wc_sort']) ? $_POST['wc_sort'] : array();
$labels = isset($_POST['wc_label']) ? $_POST['wc_label'] : array();
$keywords = isset($_POST['wc_keyword']) ? $_POST['wc_keyword'] : array();
$actives = isset($_POST['wc_active']) ? $_POST['wc_active'] : array();
$deletes = isset($_POST['wc_delete']) ? $_POST['wc_delete'] : array();

foreach ($ids as $index => $id) {
    $id = (int) $id;
    if (!$id) {
        continue;
    }

    if (!empty($deletes[$id])) {
        sql_query(" delete from `{$table}` where wc_id = '{$id}' ");
        continue;
    }

    $label = isset($labels[$index]) ? trim(strip_tags($labels[$index])) : '';
    $keyword = isset($keywords[$index]) ? trim(strip_tags($keywords[$index])) : '';
    $sort = isset($sorts[$index]) ? (int) $sorts[$index] : 0;
    $active = !empty($actives[$id]) ? 1 : 0;

    if ($label === '' && $keyword === '') {
        continue;
    }

    if ($label === '') {
        $label = '#'.$keyword;
    }

    sql_query(" update `{$table}`
        set wc_label = '".sql_escape_string($label)."',
            wc_keyword = '".sql_escape_string($keyword)."',
            wc_sort = '{$sort}',
            wc_active = '{$active}'
        where wc_id = '{$id}' ");
}

$new_sorts = isset($_POST['new_wc_sort']) ? $_POST['new_wc_sort'] : array();
$new_labels = isset($_POST['new_wc_label']) ? $_POST['new_wc_label'] : array();
$new_keywords = isset($_POST['new_wc_keyword']) ? $_POST['new_wc_keyword'] : array();
$new_actives = isset($_POST['new_wc_active']) ? $_POST['new_wc_active'] : array();
$now = G5_TIME_YMDHIS;

foreach ($new_labels as $index => $label) {
    $label = trim(strip_tags($label));
    $keyword = isset($new_keywords[$index]) ? trim(strip_tags($new_keywords[$index])) : '';
    $sort = isset($new_sorts[$index]) ? (int) $new_sorts[$index] : 0;
    $active = !empty($new_actives[$index]) ? 1 : 0;

    if ($label === '' && $keyword === '') {
        continue;
    }

    if ($label === '') {
        $label = '#'.$keyword;
    }

    sql_query(" insert into `{$table}`
        set wc_label = '".sql_escape_string($label)."',
            wc_keyword = '".sql_escape_string($keyword)."',
            wc_sort = '{$sort}',
            wc_active = '{$active}',
            wc_datetime = '{$now}' ");
}

goto_url('./willow_category.php');
