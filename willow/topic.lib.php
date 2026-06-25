<?php
if (!defined('_GNUBOARD_')) exit;

function willow_topic_tables()
{
    global $g5;

    $prefix = defined('G5_TABLE_PREFIX') ? G5_TABLE_PREFIX : (isset($g5['table_prefix']) ? $g5['table_prefix'] : 'g5_');

    return array(
        'topic' => $prefix.'willow_topic',
        'post' => $prefix.'willow_topic_post',
        'draft' => $prefix.'willow_topic_draft',
        'like' => $prefix.'willow_like',
        'comment' => $prefix.'willow_comment',
    );
}

function willow_topic_install()
{
    static $installed = false;

    if ($installed) {
        return;
    }

    $tables = willow_topic_tables();

    sql_query(" create table if not exists `{$tables['topic']}` (
        wt_id int unsigned not null auto_increment,
        wt_subject varchar(255) not null default '',
        wt_date date not null,
        wt_publish_datetime datetime not null,
        wt_participants int unsigned not null default 0,
        wt_description text not null,
        wt_active tinyint(1) not null default 1,
        wt_datetime datetime not null,
        primary key (wt_id),
        key wt_active (wt_active),
        key wt_date (wt_date),
        key wt_publish_datetime (wt_publish_datetime)
    ) ", false);

    $column = sql_fetch(" show columns from `{$tables['topic']}` like 'wt_publish_datetime' ", false);
    if (empty($column['Field'])) {
        sql_query(" alter table `{$tables['topic']}` add `wt_publish_datetime` datetime not null after `wt_date` ", false);
        sql_query(" alter table `{$tables['topic']}` add key `wt_publish_datetime` (`wt_publish_datetime`) ", false);
    }
    sql_query(" update `{$tables['topic']}`
        set wt_publish_datetime = concat(wt_date, ' 00:00:00')
        where wt_publish_datetime = '0000-00-00 00:00:00'
            or wt_publish_datetime is null ", false);

    sql_query(" create table if not exists `{$tables['post']}` (
        wp_id int unsigned not null auto_increment,
        wt_id int unsigned not null default 0,
        mb_id varchar(20) not null default '',
        wp_author varchar(255) not null default '',
        wp_subject varchar(255) not null default '',
        wp_content text not null,
        wp_image text not null,
        wp_access varchar(20) not null default 'public',
        wp_like int unsigned not null default 0,
        wp_comment int unsigned not null default 0,
        wp_datetime datetime not null,
        primary key (wp_id),
        key wt_id (wt_id),
        key wp_datetime (wp_datetime)
    ) ", false);

    $post_image_column = sql_fetch(" show columns from `{$tables['post']}` like 'wp_image' ", false);
    if (!empty($post_image_column['Type']) && stripos($post_image_column['Type'], 'text') === false) {
        sql_query(" alter table `{$tables['post']}` modify `wp_image` text not null ", false);
    }
    $post_access_column = sql_fetch(" show columns from `{$tables['post']}` like 'wp_access' ", false);
    if (empty($post_access_column['Field'])) {
        sql_query(" alter table `{$tables['post']}` add `wp_access` varchar(20) not null default 'public' after `wp_image` ", false);
    }

    sql_query(" create table if not exists `{$tables['draft']}` (
        wd_id int unsigned not null auto_increment,
        wt_id int unsigned not null default 0,
        mb_id varchar(20) not null default '',
        wd_topic_mode varchar(20) not null default 'today',
        wd_subject varchar(255) not null default '',
        wd_content text not null,
        wd_tags varchar(255) not null default '',
        wd_access varchar(20) not null default 'public',
        wd_images text not null,
        wd_datetime datetime not null,
        wd_update_datetime datetime not null,
        primary key (wd_id),
        key mb_id (mb_id),
        key wt_id (wt_id),
        key wd_update_datetime (wd_update_datetime)
    ) ", false);

    sql_query(" create table if not exists `{$tables['like']}` (
        wl_id int unsigned not null auto_increment,
        target_type varchar(20) not null default '',
        target_id int unsigned not null default 0,
        viewer_key varchar(80) not null default '',
        mb_id varchar(20) not null default '',
        wl_datetime datetime not null,
        primary key (wl_id),
        unique key target_viewer (target_type, target_id, viewer_key),
        key target (target_type, target_id)
    ) ", false);

    sql_query(" create table if not exists `{$tables['comment']}` (
        wc_id int unsigned not null auto_increment,
        target_type varchar(20) not null default '',
        target_id int unsigned not null default 0,
        mb_id varchar(20) not null default '',
        wc_author varchar(255) not null default '',
        wc_content text not null,
        wc_datetime datetime not null,
        primary key (wc_id),
        key target (target_type, target_id),
        key wc_datetime (wc_datetime)
    ) ", false);

    $row = sql_fetch(" select count(*) as cnt from `{$tables['topic']}` ");
    if (empty($row['cnt'])) {
        $now = G5_TIME_YMDHIS;
        sql_query(" insert into `{$tables['topic']}`
            set wt_subject = '나를 잠시 멈추게한 것',
                wt_date = '2026-05-28',
                wt_publish_datetime = '2026-05-28 00:00:00',
                wt_participants = '1280',
                wt_description = '오늘 나를 멈춰 서게 만든 순간을 나눠보세요.',
                wt_active = '1',
                wt_datetime = '{$now}' ");
    }

    $installed = true;
}

function willow_interaction_viewer_key()
{
    global $member;

    if (!empty($member['mb_id'])) {
        return 'm:'.$member['mb_id'];
    }

    if (session_id()) {
        return 's:'.session_id();
    }

    return 'ip:'.(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown');
}

function willow_interaction_author_name()
{
    global $member;

    if (!empty($member['mb_nick'])) {
        return $member['mb_nick'];
    }

    if (!empty($member['mb_name'])) {
        return $member['mb_name'];
    }

    return '윌로우 회원';
}

function willow_has_liked($target_type, $target_id)
{
    global $member;

    $tables = willow_topic_tables();
    willow_topic_install();

    $target_type = sql_escape_string($target_type);
    $target_id = (int) $target_id;
    $viewer_key = sql_escape_string(willow_interaction_viewer_key());
    $where = " target_type = '{$target_type}' and target_id = '{$target_id}' and viewer_key = '{$viewer_key}' ";

    if (!empty($member['mb_id'])) {
        $mb_id = sql_escape_string($member['mb_id']);
        $where = " target_type = '{$target_type}' and target_id = '{$target_id}' and (viewer_key = '{$viewer_key}' or mb_id = '{$mb_id}') ";
    }

    $row = sql_fetch(" select wl_id from `{$tables['like']}` where {$where} limit 1 ");

    return !empty($row['wl_id']);
}

function willow_like_count($target_type, $target_id)
{
    $tables = willow_topic_tables();
    willow_topic_install();

    $target_type = sql_escape_string($target_type);
    $target_id = (int) $target_id;
    $row = sql_fetch(" select count(*) as cnt from `{$tables['like']}` where target_type = '{$target_type}' and target_id = '{$target_id}' ");

    return isset($row['cnt']) ? (int) $row['cnt'] : 0;
}

function willow_comment_count($target_type, $target_id)
{
    $tables = willow_topic_tables();
    willow_topic_install();

    $target_type = sql_escape_string($target_type);
    $target_id = (int) $target_id;
    $row = sql_fetch(" select count(*) as cnt from `{$tables['comment']}` where target_type = '{$target_type}' and target_id = '{$target_id}' ");

    return isset($row['cnt']) ? (int) $row['cnt'] : 0;
}

function willow_get_comments($target_type, $target_id, $limit = 100)
{
    $tables = willow_topic_tables();
    willow_topic_install();

    if (!function_exists('willow_member_avatar')) {
        include_once(G5_PATH.'/willow/content.lib.php');
    }

    $target_type = sql_escape_string($target_type);
    $target_id = (int) $target_id;
    $limit = max(1, (int) $limit);
    $comments = array();
    $result = sql_query(" select * from `{$tables['comment']}` where target_type = '{$target_type}' and target_id = '{$target_id}' order by wc_id asc limit {$limit} ", false);

    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $comment_member = !empty($row['mb_id']) ? get_member($row['mb_id']) : array();
            $comments[] = array(
                'id' => (int) $row['wc_id'],
                'author' => get_text($row['wc_author'] ? $row['wc_author'] : '윌로우 회원'),
                'avatar' => willow_member_avatar($comment_member),
                'content' => get_text($row['wc_content']),
                'date' => $row['wc_datetime'] ? substr($row['wc_datetime'], 0, 16) : '',
            );
        }
    }

    return $comments;
}

function willow_get_topic()
{
    $tables = willow_topic_tables();
    willow_topic_install();

    $now = sql_escape_string(G5_TIME_YMDHIS);
    $topic = sql_fetch(" select * from `{$tables['topic']}`
        where wt_active = '1'
            and wt_publish_datetime <= '{$now}'
        order by wt_publish_datetime desc, wt_date desc, wt_id desc
        limit 1 ", false);

    return $topic;
}

function willow_get_topic_by_id($wt_id)
{
    $tables = willow_topic_tables();
    willow_topic_install();

    $wt_id = (int) $wt_id;
    if ($wt_id < 1) {
        return array();
    }

    $now = sql_escape_string(G5_TIME_YMDHIS);
    return sql_fetch(" select * from `{$tables['topic']}`
        where wt_id = '{$wt_id}'
            and wt_active = '1'
            and wt_publish_datetime <= '{$now}'
        limit 1 ", false);
}

function willow_get_visible_topics($limit = 180)
{
    $tables = willow_topic_tables();
    willow_topic_install();

    $limit = max(1, (int) $limit);
    $now = sql_escape_string(G5_TIME_YMDHIS);
    $topics = array();
    $result = sql_query(" select * from `{$tables['topic']}`
        where wt_active = '1'
            and wt_publish_datetime <= '{$now}'
        order by wt_publish_datetime desc, wt_date desc, wt_id desc
        limit {$limit} ", false);

    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $topics[] = $row;
        }
    }

    return $topics;
}

function willow_topic_archive_months($topics)
{
    $months = array();
    foreach ($topics as $topic) {
        $month_key = substr(willow_topic_publish_datetime($topic), 0, 7);
        if (!$month_key || isset($months[$month_key])) {
            continue;
        }

        $months[$month_key] = str_replace('-', '.', $month_key);
    }

    return $months;
}

function willow_topic_publish_datetime($topic)
{
    if (!empty($topic['wt_publish_datetime']) && $topic['wt_publish_datetime'] !== '0000-00-00 00:00:00') {
        return $topic['wt_publish_datetime'];
    }

    if (!empty($topic['wt_date']) && $topic['wt_date'] !== '0000-00-00') {
        return $topic['wt_date'].' 00:00:00';
    }

    return G5_TIME_YMD.' 00:00:00';
}

function willow_topic_is_visible($topic)
{
    if (empty($topic['wt_id']) || empty($topic['wt_active'])) {
        return false;
    }

    return willow_topic_publish_datetime($topic) <= G5_TIME_YMDHIS;
}

function willow_topic_date($date)
{
    if (!$date || $date === '0000-00-00') {
        return str_replace('-', '.', G5_TIME_YMD);
    }

    return str_replace('-', '.', substr($date, 0, 10));
}

function willow_topic_participants($count)
{
    return number_format((int) $count).'명 참여하고 있어요';
}

function willow_topic_participant_count($wt_id)
{
    $tables = willow_topic_tables();
    willow_topic_install();

    $wt_id = (int) $wt_id;
    $row = sql_fetch(" select count(*) as cnt
        from `{$tables['post']}`
        where wt_id = '{$wt_id}' ");

    return isset($row['cnt']) ? (int) $row['cnt'] : 0;
}

function willow_topic_recent_participants($wt_id, $limit = 3)
{
    $tables = willow_topic_tables();
    willow_topic_install();

    if (!function_exists('willow_member_avatar')) {
        include_once(G5_PATH.'/willow/content.lib.php');
    }

    $wt_id = (int) $wt_id;
    $limit = max(1, (int) $limit);
    $participants = array();
    $seen = array();
    $result = sql_query(" select mb_id, wp_author from `{$tables['post']}` where wt_id = '{$wt_id}' order by wp_id desc limit 30 ");

    while ($row = sql_fetch_array($result)) {
        $key = $row['mb_id'] ? 'm:'.$row['mb_id'] : 'a:'.$row['wp_author'];
        if ($key === 'a:' || isset($seen[$key])) {
            continue;
        }

        $seen[$key] = true;
        $member = $row['mb_id'] ? get_member($row['mb_id']) : array();
        $participants[] = array(
            'name' => get_text($row['wp_author'] ? $row['wp_author'] : (!empty($member['mb_nick']) ? $member['mb_nick'] : '윌로우 회원')),
            'avatar' => willow_member_avatar($member),
        );

        if (count($participants) >= $limit) {
            break;
        }
    }

    return $participants;
}

function willow_topic_write_url($topic)
{
    $wt_id = !empty($topic['wt_id']) ? (int) $topic['wt_id'] : 0;

    return G5_URL.'/willow/write.php?wt_id='.$wt_id;
}

function willow_topic_post_url($post)
{
    return G5_URL.'/willow/post.php?wp_id='.(int) $post['wp_id'];
}

function willow_get_topic_posts($wt_id, $limit = 20)
{
    $tables = willow_topic_tables();
    willow_topic_install();

    $wt_id = (int) $wt_id;
    $limit = max(1, (int) $limit);
    $posts = array();
    $result = sql_query(" select * from `{$tables['post']}` where wt_id = '{$wt_id}' order by wp_id desc limit {$limit} ");

    while ($row = sql_fetch_array($result)) {
        $posts[] = $row;
    }

    return $posts;
}

function willow_topic_post_to_feed($post)
{
    if (!function_exists('willow_member_avatar')) {
        include_once(G5_PATH.'/willow/content.lib.php');
    }

    $member = array();
    if (!empty($post['mb_id'])) {
        $member = get_member($post['mb_id']);
    }

    $author = $post['wp_author'] ? $post['wp_author'] : '윌로우 회원';
    $date = $post['wp_datetime'] ? substr($post['wp_datetime'], 0, 10) : G5_TIME_YMD;

    $access = function_exists('willow_normalize_post_access') ? willow_normalize_post_access(isset($post['wp_access']) ? $post['wp_access'] : '') : (!empty($post['wp_access']) && $post['wp_access'] === 'subscriber' ? 'subscriber' : 'public');

    return array(
        'id' => (int) $post['wp_id'],
        'mb_id' => $post['mb_id'],
        'target_type' => 'topic',
        'author' => get_text($author),
        'date' => str_replace('-', '.', $date),
        'sort_datetime' => $post['wp_datetime'],
        'avatar' => willow_member_avatar($member),
        'body' => function_exists('willow_content_excerpt') ? get_text(willow_content_excerpt($post['wp_content'], 180)) : get_text(cut_str(strip_tags($post['wp_content']), 180, '...')),
        'excerpt' => function_exists('willow_content_excerpt') ? get_text(willow_content_excerpt($post['wp_content'], 92)) : get_text(cut_str(strip_tags($post['wp_content']), 92, '...')),
        'image' => function_exists('willow_first_image') ? willow_first_image($post['wp_image']) : $post['wp_image'],
        'liked' => willow_has_liked('topic', (int) $post['wp_id']),
        'likes' => number_format(willow_like_count('topic', (int) $post['wp_id'])),
        'comments' => number_format(willow_comment_count('topic', (int) $post['wp_id'])),
        'verified' => !empty($member['mb_7']) && $member['mb_7'] === 'nk_migrant',
        'href' => willow_topic_post_url($post),
        'title' => get_text($post['wp_subject']),
        'access' => $access,
        'access_label' => function_exists('willow_post_access_label') ? willow_post_access_label($access) : ($access === 'subscriber' ? '유료' : '무료'),
    );
}
