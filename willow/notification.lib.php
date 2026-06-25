<?php
if (!defined('_GNUBOARD_')) exit;

include_once(G5_PATH.'/willow/topic.lib.php');
include_once(G5_PATH.'/willow/content.lib.php');

function willow_subscription_table()
{
    global $g5;

    $prefix = defined('G5_TABLE_PREFIX') ? G5_TABLE_PREFIX : (isset($g5['table_prefix']) ? $g5['table_prefix'] : 'g5_');

    return $prefix.'willow_subscription';
}

function willow_notification_install()
{
    static $installed = false;

    if ($installed) {
        return;
    }

    $table = willow_subscription_table();
    sql_query(" create table if not exists `{$table}` (
        ws_id int unsigned not null auto_increment,
        author_mb_id varchar(20) not null default '',
        subscriber_mb_id varchar(20) not null default '',
        ws_status varchar(20) not null default 'active',
        ws_datetime datetime not null,
        primary key (ws_id),
        unique key author_subscriber (author_mb_id, subscriber_mb_id),
        key subscriber_status (subscriber_mb_id, ws_status),
        key author_status (author_mb_id, ws_status)
    ) ", false);

    $setting_table = willow_notification_setting_table();
    sql_query(" create table if not exists `{$setting_table}` (
        mb_id varchar(20) not null default '',
        setting_key varchar(50) not null default '',
        is_enabled tinyint(1) not null default '1',
        ws_datetime datetime not null,
        primary key (mb_id, setting_key),
        key setting_key (setting_key)
    ) ", false);

    $installed = true;
}

function willow_notification_setting_table()
{
    global $g5;

    $prefix = defined('G5_TABLE_PREFIX') ? G5_TABLE_PREFIX : (isset($g5['table_prefix']) ? $g5['table_prefix'] : 'g5_');

    return $prefix.'willow_notification_setting';
}

function willow_notification_setting_definitions()
{
    return array(
        'subscribed_post' => array(
            'title' => '구독 작가 새 글',
            'description' => '구독 중인 작가가 새 글을 등록하면 알려드려요.',
        ),
        'comment' => array(
            'title' => '내 글 댓글',
            'description' => '내가 작성한 글에 새 댓글이 달리면 알려드려요.',
        ),
        'subscriber' => array(
            'title' => '신규 구독자',
            'description' => '작가 계정에 새 구독자가 생기면 알려드려요.',
        ),
        'like' => array(
            'title' => '좋아요',
            'description' => '내 글에 좋아요가 발생하면 알려드려요.',
        ),
        'topic' => array(
            'title' => '오늘의 주제',
            'description' => '오늘의 주제가 새로 열리면 알려드려요.',
        ),
        'topic_post' => array(
            'title' => '오늘의 주제 참여글',
            'description' => '오늘의 주제에 새 참여글이 올라오면 알려드려요.',
        ),
    );
}

function willow_notification_settings($mb_id)
{
    $settings = array('all' => 1);
    $definitions = willow_notification_setting_definitions();
    foreach ($definitions as $key => $definition) {
        $settings[$key] = 1;
    }

    $mb_id = sql_escape_string($mb_id);
    if ($mb_id === '') {
        return $settings;
    }

    willow_notification_install();

    $table = willow_notification_setting_table();
    $result = sql_query(" select setting_key, is_enabled from `{$table}` where mb_id = '{$mb_id}' ", false);
    if ($result) {
        while ($row = sql_fetch_array($result)) {
            if (array_key_exists($row['setting_key'], $settings)) {
                $settings[$row['setting_key']] = (int) $row['is_enabled'] ? 1 : 0;
            }
        }
    }

    return $settings;
}

function willow_notification_setting_is_enabled($settings, $type)
{
    if (empty($settings['all'])) {
        return false;
    }

    if (isset($settings[$type])) {
        return !empty($settings[$type]);
    }

    return true;
}

function willow_notification_save_settings($mb_id, $settings)
{
    $mb_id = sql_escape_string($mb_id);
    if ($mb_id === '') {
        return;
    }

    willow_notification_install();

    $table = willow_notification_setting_table();
    $definitions = willow_notification_setting_definitions();
    $keys = array_merge(array('all'), array_keys($definitions));

    foreach ($keys as $key) {
        $enabled = !empty($settings[$key]) ? 1 : 0;
        $escaped_key = sql_escape_string($key);
        sql_query(" insert into `{$table}`
                set mb_id = '{$mb_id}',
                    setting_key = '{$escaped_key}',
                    is_enabled = '{$enabled}',
                    ws_datetime = '".G5_TIME_YMDHIS."'
            on duplicate key update
                    is_enabled = '{$enabled}',
                    ws_datetime = '".G5_TIME_YMDHIS."' ", false);
    }
}

function willow_notification_datetime($datetime)
{
    if (!$datetime || $datetime === '0000-00-00 00:00:00') {
        return '';
    }

    $time = strtotime($datetime);
    if (!$time) {
        return get_text($datetime);
    }

    if (date('Y-m-d', $time) === G5_TIME_YMD) {
        return date('H:i', $time);
    }

    return date('Y.m.d H:i', $time);
}

function willow_notification_add(&$items, $type, $title, $body, $href, $datetime, $actor = '')
{
    $items[] = array(
        'type' => $type,
        'title' => get_text($title),
        'body' => get_text($body),
        'href' => $href,
        'datetime' => $datetime,
        'date_text' => willow_notification_datetime($datetime),
        'actor' => get_text($actor),
    );
}

function willow_notification_post_meta($target_type, $target_id)
{
    $target_id = (int) $target_id;
    if (!$target_id) {
        return array();
    }

    if ($target_type === 'board') {
        $table = willow_content_table();
        $post = sql_fetch(" select wr_id, mb_id, wr_name, wr_subject, wr_content, wr_datetime from `{$table}` where wr_id = '{$target_id}' and wr_is_comment = 0 ", false);
        if (empty($post['wr_id'])) {
            return array();
        }

        return array(
            'author_mb_id' => $post['mb_id'],
            'author' => $post['wr_name'],
            'title' => $post['wr_subject'],
            'excerpt' => cut_str(trim(preg_replace('/\s+/', ' ', strip_tags($post['wr_content']))), 80, '...'),
            'href' => G5_URL.'/willow/post.php?wr_id='.(int) $post['wr_id'],
            'datetime' => $post['wr_datetime'],
        );
    }

    if ($target_type === 'topic') {
        $tables = willow_topic_tables();
        $post = sql_fetch(" select wp_id, mb_id, wp_author, wp_subject, wp_content, wp_datetime from `{$tables['post']}` where wp_id = '{$target_id}' ", false);
        if (empty($post['wp_id'])) {
            return array();
        }

        return array(
            'author_mb_id' => $post['mb_id'],
            'author' => $post['wp_author'],
            'title' => $post['wp_subject'],
            'excerpt' => cut_str(trim(preg_replace('/\s+/', ' ', strip_tags($post['wp_content']))), 80, '...'),
            'href' => G5_URL.'/willow/post.php?wp_id='.(int) $post['wp_id'],
            'datetime' => $post['wp_datetime'],
        );
    }

    return array();
}

function willow_get_notifications($mb_id, $limit = 50)
{
    global $g5;

    willow_notification_install();
    willow_topic_install();

    $items = array();
    $limit = max(1, (int) $limit);
    $mb_id = sql_escape_string($mb_id);
    if ($mb_id === '') {
        return $items;
    }

    $notification_settings = willow_notification_settings($mb_id);

    $tables = willow_topic_tables();
    $board_table = willow_content_table();
    $subscription_table = willow_subscription_table();

    $comment_sql = "
        (select c.target_type, c.target_id, c.mb_id, c.wc_author as actor, c.wc_content as content, c.wc_datetime as event_datetime
            from `{$tables['comment']}` c
            join `{$board_table}` p on c.target_type = 'board' and c.target_id = p.wr_id and p.wr_is_comment = 0
            where p.mb_id = '{$mb_id}' and c.mb_id <> '{$mb_id}')
        union all
        (select c.target_type, c.target_id, c.mb_id, c.wc_author as actor, c.wc_content as content, c.wc_datetime as event_datetime
            from `{$tables['comment']}` c
            join `{$tables['post']}` p on c.target_type = 'topic' and c.target_id = p.wp_id
            where p.mb_id = '{$mb_id}' and c.mb_id <> '{$mb_id}')
        order by event_datetime desc
        limit 20 ";
    $result = sql_query($comment_sql, false);
    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $post = willow_notification_post_meta($row['target_type'], (int) $row['target_id']);
            if (!$post) {
                continue;
            }
            willow_notification_add($items, 'comment', '내 글에 새 댓글이 달렸어요', ($row['actor'] ? $row['actor'] : '누군가').' · '.$post['title'], $post['href'].'#willow_comments', $row['event_datetime'], $row['actor']);
        }
    }

    $like_sql = "
        (select l.target_type, l.target_id, l.mb_id, m.mb_nick, m.mb_name, l.wl_datetime as event_datetime
            from `{$tables['like']}` l
            join `{$board_table}` p on l.target_type = 'board' and l.target_id = p.wr_id and p.wr_is_comment = 0
            left join {$g5['member_table']} m on m.mb_id = l.mb_id
            where p.mb_id = '{$mb_id}' and l.mb_id <> '{$mb_id}')
        union all
        (select l.target_type, l.target_id, l.mb_id, m.mb_nick, m.mb_name, l.wl_datetime as event_datetime
            from `{$tables['like']}` l
            join `{$tables['post']}` p on l.target_type = 'topic' and l.target_id = p.wp_id
            left join {$g5['member_table']} m on m.mb_id = l.mb_id
            where p.mb_id = '{$mb_id}' and l.mb_id <> '{$mb_id}')
        order by event_datetime desc
        limit 20 ";
    $result = sql_query($like_sql, false);
    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $post = willow_notification_post_meta($row['target_type'], (int) $row['target_id']);
            if (!$post) {
                continue;
            }
            $actor = $row['mb_nick'] ? $row['mb_nick'] : ($row['mb_name'] ? $row['mb_name'] : '누군가');
            willow_notification_add($items, 'like', '내 글에 좋아요가 생겼어요', $actor.' · '.$post['title'], $post['href'], $row['event_datetime'], $actor);
        }
    }

    $result = sql_query(" select s.*, m.mb_nick, m.mb_name
        from `{$subscription_table}` s
        left join {$g5['member_table']} m on m.mb_id = s.subscriber_mb_id
        where s.author_mb_id = '{$mb_id}' and s.ws_status = 'active'
        order by s.ws_datetime desc
        limit 20 ", false);
    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $actor = $row['mb_nick'] ? $row['mb_nick'] : ($row['mb_name'] ? $row['mb_name'] : '새 구독자');
            willow_notification_add($items, 'subscriber', '새 구독자가 생겼어요', $actor.'님이 작가님을 구독했습니다.', G5_URL.'/willow/subscribe.php', $row['ws_datetime'], $actor);
        }
    }

    $result = sql_query(" select s.author_mb_id, s.ws_datetime, m.mb_nick, m.mb_name
        from `{$subscription_table}` s
        left join {$g5['member_table']} m on m.mb_id = s.author_mb_id
        where s.subscriber_mb_id = '{$mb_id}' and s.ws_status = 'active'
        order by s.ws_datetime desc
        limit 30 ", false);
    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $author_id = sql_escape_string($row['author_mb_id']);
            $author = $row['mb_nick'] ? $row['mb_nick'] : ($row['mb_name'] ? $row['mb_name'] : '구독 작가');
            $after = sql_escape_string($row['ws_datetime']);

            $post_result = sql_query(" select 'board' as target_type, wr_id as target_id, wr_subject as subject, wr_datetime as event_datetime
                    from `{$board_table}`
                    where wr_is_comment = 0 and mb_id = '{$author_id}' and wr_datetime >= '{$after}'
                union all
                select 'topic' as target_type, wp_id as target_id, wp_subject as subject, wp_datetime as event_datetime
                    from `{$tables['post']}`
                    where mb_id = '{$author_id}' and wp_datetime >= '{$after}'
                order by event_datetime desc
                limit 10 ", false);

            if ($post_result) {
                while ($post_row = sql_fetch_array($post_result)) {
                    $post = willow_notification_post_meta($post_row['target_type'], (int) $post_row['target_id']);
                    if ($post) {
                        willow_notification_add($items, 'subscribed_post', $author.' 작가의 새 글이 올라왔어요', $post['title'], $post['href'], $post_row['event_datetime'], $author);
                    }
                }
            }
        }
    }

    $topic = willow_get_topic();
    if (!empty($topic['wt_id'])) {
        willow_notification_add($items, 'topic', '오늘의 주제가 열렸어요', $topic['wt_subject'], G5_URL.'/willow/today.php', willow_topic_publish_datetime($topic), 'WILLOW');

        $result = sql_query(" select wp_id, mb_id, wp_author, wp_subject, wp_datetime
            from `{$tables['post']}`
            where wt_id = '".(int) $topic['wt_id']."' and mb_id <> '{$mb_id}'
            order by wp_id desc
            limit 10 ", false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                willow_notification_add($items, 'topic_post', '오늘의 주제에 새 글이 올라왔어요', ($row['wp_author'] ? $row['wp_author'] : '윌로우 회원').' · '.$row['wp_subject'], G5_URL.'/willow/post.php?wp_id='.(int) $row['wp_id'], $row['wp_datetime'], $row['wp_author']);
            }
        }
    }

    usort($items, function ($a, $b) {
        return strcmp($b['datetime'], $a['datetime']);
    });

    $filtered_items = array();
    foreach ($items as $item) {
        if (willow_notification_setting_is_enabled($notification_settings, $item['type'])) {
            $filtered_items[] = $item;
        }
    }

    return array_slice($filtered_items, 0, $limit);
}

function willow_notification_policies()
{
    return array(
        '구독 중인 작가가 신규 글을 등록한 경우',
        '내가 작성한 글에 새로운 댓글이 등록된 경우',
        '작가 계정에 신규 구독자가 발생한 경우',
        '내가 작성한 글에 좋아요가 발생한 경우',
        '오늘의 주제가 새로 열렸거나 오늘의 주제에 새 참여글이 등록된 경우',
    );
}
