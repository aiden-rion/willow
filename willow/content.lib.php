<?php
if (!defined('_GNUBOARD_')) exit;

function willow_content_board()
{
    return 'free';
}

function willow_content_table()
{
    global $g5;

    return $g5['write_prefix'].willow_content_board();
}

function willow_category_table()
{
    global $g5;

    $prefix = defined('G5_TABLE_PREFIX') ? G5_TABLE_PREFIX : (isset($g5['table_prefix']) ? $g5['table_prefix'] : 'g5_');

    return $prefix.'willow_category';
}

function willow_banner_table()
{
    global $g5;

    $prefix = defined('G5_TABLE_PREFIX') ? G5_TABLE_PREFIX : (isset($g5['table_prefix']) ? $g5['table_prefix'] : 'g5_');

    return $prefix.'willow_banner';
}

function willow_author_metric_table()
{
    global $g5;

    $prefix = defined('G5_TABLE_PREFIX') ? G5_TABLE_PREFIX : (isset($g5['table_prefix']) ? $g5['table_prefix'] : 'g5_');

    return $prefix.'willow_author_metric';
}

function willow_author_request_table()
{
    global $g5;

    $prefix = defined('G5_TABLE_PREFIX') ? G5_TABLE_PREFIX : (isset($g5['table_prefix']) ? $g5['table_prefix'] : 'g5_');

    return $prefix.'willow_author_request';
}

function willow_report_table()
{
    global $g5;

    $prefix = defined('G5_TABLE_PREFIX') ? G5_TABLE_PREFIX : (isset($g5['table_prefix']) ? $g5['table_prefix'] : 'g5_');

    return $prefix.'willow_report';
}

function willow_report_install()
{
    static $installed = false;

    if ($installed) {
        return;
    }

    $table = willow_report_table();
    sql_query(" create table if not exists `{$table}` (
        wrp_id int unsigned not null auto_increment,
        wrp_target_type varchar(20) not null default '',
        wrp_target_id int unsigned not null default 0,
        wrp_target_title varchar(255) not null default '',
        wrp_author_mb_id varchar(20) not null default '',
        wrp_author_name varchar(100) not null default '',
        wrp_reporter_mb_id varchar(20) not null default '',
        wrp_reporter_name varchar(100) not null default '',
        wrp_content text not null,
        wrp_status varchar(20) not null default 'pending',
        wrp_admin_memo text not null,
        wrp_datetime datetime not null,
        wrp_update_datetime datetime not null,
        primary key (wrp_id),
        key target (wrp_target_type, wrp_target_id),
        key reporter (wrp_reporter_mb_id),
        key status (wrp_status),
        key wrp_datetime (wrp_datetime)
    ) ", false);

    $installed = true;
}

function willow_author_request_install()
{
    static $installed = false;

    if ($installed) {
        return;
    }

    $table = willow_author_request_table();
    sql_query(" create table if not exists `{$table}` (
        war_id int unsigned not null auto_increment,
        mb_id varchar(20) not null default '',
        war_status varchar(20) not null default 'pending',
        war_intro text not null,
        war_is_escapee varchar(20) not null default '',
        war_cert_file varchar(255) not null default '',
        war_profile_image varchar(255) not null default '',
        war_categories varchar(255) not null default '',
        war_bank_name varchar(100) not null default '',
        war_account_holder varchar(100) not null default '',
        war_account_number varchar(100) not null default '',
        war_subscribe_price int unsigned not null default 0,
        war_agree_terms tinyint(1) not null default 0,
        war_agree_privacy tinyint(1) not null default 0,
        war_memo text not null,
        war_admin_memo text not null,
        war_datetime datetime not null,
        war_update_datetime datetime not null,
        war_review_datetime datetime not null default '0000-00-00 00:00:00',
        war_review_mb_id varchar(20) not null default '',
        primary key (war_id),
        key mb_id (mb_id),
        key war_status (war_status),
        key war_datetime (war_datetime)
    ) ", false);

    $installed = true;
}

function willow_author_request_upload_dir()
{
    return G5_DATA_PATH.'/willow_author_request';
}

function willow_author_request_upload_url()
{
    return G5_DATA_URL.'/willow_author_request';
}

function willow_author_request_file_url($filename)
{
    $filename = basename((string) $filename);
    if ($filename === '') {
        return '';
    }

    return willow_author_request_upload_url().'/'.$filename;
}

function willow_author_latest_request($mb_id)
{
    $mb_id = sql_escape_string($mb_id);
    if ($mb_id === '') {
        return array();
    }

    willow_author_request_install();
    $table = willow_author_request_table();

    return sql_fetch(" select * from `{$table}` where mb_id = '{$mb_id}' order by war_id desc limit 1 ", false);
}

function willow_author_is_escapee($member_row)
{
    return !empty($member_row['mb_7']) && $member_row['mb_7'] === 'nk_migrant';
}

function willow_author_cert_badge($member_row)
{
    if (!willow_author_is_escapee($member_row)) {
        return '';
    }

    return '<img class="willow_author_cert_mark" src="'.G5_IMG_URL.'/img_cert_mark.png" alt="탈북이주민 인증">';
}

function willow_author_name_html($member_row, $suffix = '')
{
    $name = !empty($member_row['mb_nick']) ? $member_row['mb_nick'] : (!empty($member_row['mb_name']) ? $member_row['mb_name'] : '윌로우 작가');

    return get_text($name).get_text($suffix).willow_author_cert_badge($member_row);
}

function willow_author_metric_defaults()
{
    return array(
        'wam_recent_days' => 90,
        'wam_like_weight' => 3,
        'wam_comment_weight' => 5,
        'wam_post_weight' => 1,
        'wam_subscriber_weight' => 0,
        'wam_view_weight' => 0,
        'wam_display_limit' => 6,
        'wam_candidate_limit' => 80,
    );
}

function willow_author_metric_install()
{
    static $installed = false;

    if ($installed) {
        return;
    }

    $table = willow_author_metric_table();
    sql_query(" create table if not exists `{$table}` (
        wam_id tinyint unsigned not null default 1,
        wam_recent_days int unsigned not null default 90,
        wam_like_weight decimal(8,2) not null default 3.00,
        wam_comment_weight decimal(8,2) not null default 5.00,
        wam_post_weight decimal(8,2) not null default 1.00,
        wam_subscriber_weight decimal(8,2) not null default 0.00,
        wam_view_weight decimal(8,2) not null default 0.00,
        wam_display_limit int unsigned not null default 6,
        wam_candidate_limit int unsigned not null default 80,
        wam_update_datetime datetime not null,
        primary key (wam_id)
    ) ", false);

    $row = sql_fetch(" select wam_id from `{$table}` where wam_id = '1' ", false);
    if (empty($row['wam_id'])) {
        $defaults = willow_author_metric_defaults();
        sql_query(" insert into `{$table}`
            set wam_id = '1',
                wam_recent_days = '".(int) $defaults['wam_recent_days']."',
                wam_like_weight = '".sql_escape_string($defaults['wam_like_weight'])."',
                wam_comment_weight = '".sql_escape_string($defaults['wam_comment_weight'])."',
                wam_post_weight = '".sql_escape_string($defaults['wam_post_weight'])."',
                wam_subscriber_weight = '".sql_escape_string($defaults['wam_subscriber_weight'])."',
                wam_view_weight = '".sql_escape_string($defaults['wam_view_weight'])."',
                wam_display_limit = '".(int) $defaults['wam_display_limit']."',
                wam_candidate_limit = '".(int) $defaults['wam_candidate_limit']."',
                wam_update_datetime = '".G5_TIME_YMDHIS."' ", false);
    }

    $installed = true;
}

function willow_author_metric_config()
{
    willow_author_metric_install();

    $table = willow_author_metric_table();
    $defaults = willow_author_metric_defaults();
    $row = sql_fetch(" select * from `{$table}` where wam_id = '1' ", false);
    if (!$row) {
        $row = array();
    }

    return array_merge($defaults, $row);
}

function willow_banner_positions()
{
    return array(
        'home' => '메인',
        'post_bottom' => '게시글상세 가장 하단',
        'menu_bottom' => '메뉴페이지 가장 하단',
    );
}

function willow_banner_install()
{
    static $installed = false;

    if ($installed) {
        return;
    }

    $table = willow_banner_table();
    sql_query(" create table if not exists `{$table}` (
        wb_id int unsigned not null auto_increment,
        wb_position varchar(50) not null default 'home',
        wb_title varchar(255) not null default '',
        wb_alt varchar(255) not null default '',
        wb_image varchar(255) not null default '',
        wb_url varchar(255) not null default '',
        wb_new_win tinyint(1) not null default 0,
        wb_sort int not null default 0,
        wb_active tinyint(1) not null default 1,
        wb_datetime datetime not null,
        primary key (wb_id),
        key wb_position (wb_position),
        key wb_sort (wb_sort),
        key wb_active (wb_active)
    ) ", false);

    sql_query(" update `{$table}` set wb_position = 'home' where wb_position in ('home_top', 'home_writer') ", false);

    $installed = true;
}

function willow_banner_upload_dir()
{
    return G5_DATA_PATH.'/willow_banner';
}

function willow_banner_upload_url()
{
    return G5_DATA_URL.'/willow_banner';
}

function willow_banner_image_url($filename)
{
    $filename = trim((string) $filename);

    if ($filename === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $filename)) {
        return $filename;
    }

    if (strpos($filename, 'img/') === 0) {
        $relative = str_replace(array('../', './'), '', $filename);
        $path = G5_PATH.'/'.$relative;
        $query = file_exists($path) ? '?'.filemtime($path) : '';

        return G5_URL.'/'.$relative.$query;
    }

    $filename = basename($filename);
    $path = willow_banner_upload_dir().'/'.$filename;
    $query = file_exists($path) ? '?'.filemtime($path) : '';

    return willow_banner_upload_url().'/'.$filename.$query;
}

function willow_get_banners($position)
{
    $table = willow_banner_table();
    willow_banner_install();

    $position = sql_escape_string($position);
    $items = array();
    $result = sql_query(" select *
        from `{$table}`
        where wb_position = '{$position}' and wb_active = '1' and wb_image <> ''
        order by wb_sort asc, wb_id desc ", false);

    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $row['image_url'] = willow_banner_image_url($row['wb_image']);
            $items[] = $row;
        }
    }

    return $items;
}

function willow_render_banner_area($position, $class = '', $label = '')
{
    static $script_printed = false;

    $banners = willow_get_banners($position);
    if (!$banners) {
        return;
    }

    $positions = willow_banner_positions();
    $label = $label !== '' ? $label : (isset($positions[$position]) ? $positions[$position].' 배너' : '배너');
    $class_attr = trim('willow_writer_banner willow_banner_area '.$class);
    ?>
    <section class="<?php echo $class_attr; ?>" aria-label="<?php echo get_text($label); ?>">
        <div class="willow_writer_banner_track willow_banner_track" tabindex="0">
            <?php foreach ($banners as $banner) { ?>
            <?php
            $href = !empty($banner['wb_url']) ? $banner['wb_url'] : '#';
            $target = !empty($banner['wb_new_win']) ? ' target="_blank" rel="noopener noreferrer"' : '';
            $alt = !empty($banner['wb_alt']) ? $banner['wb_alt'] : (!empty($banner['wb_title']) ? $banner['wb_title'] : $label);
            ?>
            <a class="willow_writer_banner_slide willow_banner_slide" href="<?php echo $href; ?>"<?php echo $target; ?>>
                <img src="<?php echo $banner['image_url']; ?>" alt="<?php echo get_text($alt); ?>">
            </a>
            <?php } ?>
        </div>
        <?php if (count($banners) > 1) { ?>
        <span class="willow_writer_banner_count willow_banner_count" aria-live="polite">01 / <?php echo sprintf('%02d', count($banners)); ?></span>
        <?php } ?>
    </section>
    <?php if (!$script_printed) { $script_printed = true; ?>
    <script>
    (function() {
        document.querySelectorAll('.willow_banner_area').forEach(function(banner) {
            var track = banner.querySelector('.willow_banner_track');
            var count = banner.querySelector('.willow_banner_count');
            var slides = banner.querySelectorAll('.willow_banner_slide');
            if (!track || !count || !slides.length) return;
            var update = function() {
                var index = Math.round(track.scrollLeft / Math.max(1, track.clientWidth));
                count.textContent = String(index + 1).padStart(2, '0') + ' / ' + String(slides.length).padStart(2, '0');
            };
            track.addEventListener('scroll', update, { passive: true });
            update();
        });
    })();
    </script>
    <?php } ?>
    <?php
}

function willow_category_install()
{
    static $installed = false;

    if ($installed) {
        return;
    }

    $table = willow_category_table();
    sql_query(" create table if not exists `{$table}` (
        wc_id int unsigned not null auto_increment,
        wc_label varchar(100) not null default '',
        wc_keyword varchar(100) not null default '',
        wc_sort int not null default 0,
        wc_active tinyint(1) not null default 1,
        wc_datetime datetime not null,
        primary key (wc_id),
        key wc_active (wc_active),
        key wc_sort (wc_sort)
    ) ", false);

    $row = sql_fetch(" select count(*) as cnt from `{$table}` ");
    if (empty($row['cnt'])) {
        $now = G5_TIME_YMDHIS;
        $defaults = array(
            array('전체보기', '', 0),
            array('#정착꿀팁', '정착꿀팁', 10),
            array('#정착상식', '정착상식', 20),
            array('#정신건강', '정신건강', 30),
            array('#범죄사건', '범죄사건', 40),
            array('#종교문화', '종교문화', 50),
            array('#이야기', '이야기', 60),
            array('#사람', '사람', 70),
            array('#민주주의', '민주주의', 80),
            array('#인권', '인권', 90),
        );

        foreach ($defaults as $item) {
            sql_query(" insert into `{$table}`
                set wc_label = '".sql_escape_string($item[0])."',
                    wc_keyword = '".sql_escape_string($item[1])."',
                    wc_sort = '".(int) $item[2]."',
                    wc_active = '1',
                    wc_datetime = '{$now}' ");
        }
    }

    $installed = true;
}

function willow_get_categories($active_only = true)
{
    $table = willow_category_table();
    willow_category_install();

    $where = $active_only ? " where wc_active = '1' " : '';
    $categories = array();
    $result = sql_query(" select * from `{$table}` {$where} order by wc_sort asc, wc_id asc ", false);

    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $label = $row['wc_label'] ? $row['wc_label'] : $row['wc_keyword'];
            $keyword = $row['wc_keyword'] !== '' ? $row['wc_keyword'] : ltrim($label, '#');
            $categories[] = array(
                'id' => (int) $row['wc_id'],
                'label' => get_text($label),
                'keyword' => get_text($keyword),
                'sort' => (int) $row['wc_sort'],
                'active' => (int) $row['wc_active'],
                'href' => G5_BBS_URL.'/search.php?sfl=wr_subject%7C%7Cwr_content&sop=or&stx='.urlencode($keyword),
            );
        }
    }

    return $categories;
}

function willow_member_avatar($member_row)
{
    if (!empty($member_row['mb_6'])) {
        return $member_row['mb_6'];
    }

    if (!empty($member_row['mb_id'])) {
        $image_path = G5_DATA_PATH.'/member_image/'.substr($member_row['mb_id'], 0, 2).'/'.get_mb_icon_name($member_row['mb_id']).'.gif';
        if (file_exists($image_path)) {
            $filetime = (defined('G5_USE_MEMBER_IMAGE_FILETIME') && G5_USE_MEMBER_IMAGE_FILETIME) ? '?'.filemtime($image_path) : '';
            return G5_DATA_URL.'/member_image/'.substr($member_row['mb_id'], 0, 2).'/'.get_mb_icon_name($member_row['mb_id']).'.gif'.$filetime;
        }
    }

    return G5_IMG_URL.'/no_profile.gif';
}

function willow_member_tags($member_row)
{
    if (empty($member_row['mb_3'])) {
        return array('인권', '정착꿀팁', '정착상식');
    }

    $tags = array();
    foreach (explode(',', $member_row['mb_3']) as $tag) {
        $tag = trim($tag);
        if ($tag !== '') {
            $tags[] = $tag;
        }
    }

    return $tags ? $tags : array('인권', '정착꿀팁', '정착상식');
}

function willow_format_date($datetime)
{
    if (!$datetime || $datetime === '0000-00-00 00:00:00') {
        return str_replace('-', '.', G5_TIME_YMD);
    }

    return str_replace('-', '.', substr($datetime, 0, 10));
}

function willow_content_excerpt($content, $length = 120)
{
    $content = willow_content_plain_text($content);
    $content = preg_replace('/\s+/', ' ', $content);

    return cut_str(trim($content), $length, '...');
}

function willow_content_plain_text($content)
{
    $content = (string) $content;
    if ($content === '') {
        return '';
    }

    $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $content = str_replace(array('\\r\\n', '\\n', '\\r'), "\n", $content);
    $content = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $content);
    $content = preg_replace('/<\s*\/p\s*>\s*<\s*p[^>]*>/i', "\n\n", $content);
    $content = preg_replace('/<\s*\/p\s*>/i', "\n\n", $content);
    $content = preg_replace('/<\s*p[^>]*>/i', '', $content);
    $content = strip_tags($content);
    $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $content = str_replace("\xc2\xa0", ' ', $content);
    $content = preg_replace("/[ \t]+/", ' ', $content);
    $content = preg_replace("/\n{3,}/", "\n\n", $content);

    return trim($content);
}

function willow_content_paragraphs($content)
{
    $content = willow_content_plain_text($content);
    if ($content === '') {
        return array();
    }

    $paragraphs = preg_split('/\n{2,}/', $content);
    $items = array();
    foreach ($paragraphs as $paragraph) {
        $paragraph = trim(preg_replace('/\s*\n\s*/', "\n", $paragraph));
        if ($paragraph !== '') {
            $items[] = $paragraph;
        }
    }

    return $items;
}

function willow_split_images($value)
{
    $images = array();
    $value = trim((string) $value);

    if ($value === '') {
        return $images;
    }

    $parts = preg_split('/[\r\n|,]+/', $value);
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part !== '') {
            $images[] = $part;
        }
    }

    return array_values(array_unique($images));
}

function willow_first_image($value)
{
    $images = willow_split_images($value);

    return isset($images[0]) ? $images[0] : '';
}

function willow_get_board_attached_images($wr_id)
{
    global $g5;

    $wr_id = (int) $wr_id;
    if (!$wr_id) {
        return array();
    }

    $bo_table = willow_content_board();
    $images = array();
    $result = sql_query(" select bf_file, bf_fileurl, bf_type from {$g5['board_file_table']} where bo_table = '".sql_escape_string($bo_table)."' and wr_id = '{$wr_id}' and bf_file <> '' order by bf_no asc ", false);

    if ($result) {
        while ($row = sql_fetch_array($result)) {
            if ((int) $row['bf_type'] < 1 || (int) $row['bf_type'] > 3) {
                continue;
            }

            if (!empty($row['bf_fileurl'])) {
                $images[] = $row['bf_fileurl'];
            } else {
                $images[] = G5_DATA_URL.'/file/'.$bo_table.'/'.$row['bf_file'];
            }
        }
    }

    return $images;
}

function willow_get_authors($keyword = '', $limit = 3)
{
    global $g5;

    $where = " where mb_leave_date = '' and mb_level < 10 and (mb_level >= 3 or mb_2 = 'author') ";
    if ($keyword !== '') {
        $keyword = sql_escape_string($keyword);
        $where .= " and (mb_nick like '%{$keyword}%' or mb_name like '%{$keyword}%' or mb_profile like '%{$keyword}%' or mb_3 like '%{$keyword}%') ";
    }

    $limit = max(1, (int) $limit);
    $result = sql_query(" select * from {$g5['member_table']} {$where} order by mb_level desc, mb_datetime desc limit {$limit} ", false);
    $authors = array();

    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $authors[] = $row;
        }
    }

    return $authors;
}

function willow_author_card($author)
{
    $name = $author['mb_nick'] ? $author['mb_nick'] : $author['mb_name'];

    return array(
        'id' => $author['mb_id'],
        'name' => get_text($name),
        'name_html' => willow_author_name_html($author),
        'role_name_html' => willow_author_name_html($author, ' 작가'),
        'verified' => willow_author_is_escapee($author),
        'avatar' => willow_member_avatar($author),
        'profile' => get_text($author['mb_profile'] ? $author['mb_profile'] : '윌로우에서 활동하는 작가입니다.'),
        'post_count' => function_exists('willow_author_post_count') ? willow_author_post_count($author['mb_id'], $name) : (int) ($author['mb_5'] ? $author['mb_5'] : 0),
        'subscriber_count' => function_exists('willow_author_subscriber_count') ? willow_author_subscriber_count($author['mb_id']) : (int) ($author['mb_4'] ? $author['mb_4'] : 0),
        'tags' => willow_member_tags($author),
        'href' => G5_URL.'/willow/author.php?author='.urlencode($author['mb_id']),
    );
}

function willow_author_post_count($mb_id, $author_name = '')
{
    $board_table = willow_content_table();
    $board_where = " wr_is_comment = 0 ";
    $topic_where = " 1 ";

    if ($mb_id !== '') {
        $mb_id_sql = sql_escape_string($mb_id);
        $board_where .= " and mb_id = '{$mb_id_sql}' ";
        $topic_where .= " and mb_id = '{$mb_id_sql}' ";
    } else if ($author_name !== '') {
        $author_sql = sql_escape_string($author_name);
        $board_where .= " and wr_name = '{$author_sql}' ";
        $topic_where .= " and wp_author = '{$author_sql}' ";
    } else {
        return 0;
    }

    $board = sql_fetch(" select count(*) as cnt from {$board_table} where {$board_where} ", false);
    $topic_count = 0;
    if (!function_exists('willow_topic_tables')) {
        include_once(G5_PATH.'/willow/topic.lib.php');
    }
    if (function_exists('willow_topic_tables')) {
        $tables = willow_topic_tables();
        willow_topic_install();
        $topic = sql_fetch(" select count(*) as cnt from `{$tables['post']}` where {$topic_where} ", false);
        $topic_count = isset($topic['cnt']) ? (int) $topic['cnt'] : 0;
    }

    return (int) $board['cnt'] + $topic_count;
}

function willow_author_subscriber_count($mb_id)
{
    if ($mb_id === '') {
        return 0;
    }

    if (!function_exists('willow_subscription_table')) {
        include_once(G5_PATH.'/willow/notification.lib.php');
    }

    willow_notification_install();
    $table = willow_subscription_table();
    $row = sql_fetch(" select count(*) as cnt
        from `{$table}`
        where author_mb_id = '".sql_escape_string($mb_id)."'
            and ws_status = 'active' ", false);

    return isset($row['cnt']) ? (int) $row['cnt'] : 0;
}

function willow_author_is_subscribed($author_id)
{
    global $member;

    if (empty($member['mb_id']) || $author_id === '' || $member['mb_id'] === $author_id) {
        return false;
    }

    if (!function_exists('willow_subscription_table')) {
        include_once(G5_PATH.'/willow/notification.lib.php');
    }

    willow_notification_install();
    $table = willow_subscription_table();
    $row = sql_fetch(" select ws_id
        from `{$table}`
        where author_mb_id = '".sql_escape_string($author_id)."'
            and subscriber_mb_id = '".sql_escape_string($member['mb_id'])."'
            and ws_status = 'active'
        limit 1 ", false);

    return !empty($row['ws_id']);
}

function willow_author_recent_subscriber_avatars($author_id, $limit = 3)
{
    global $g5;

    if ($author_id === '') {
        return array();
    }

    if (!function_exists('willow_subscription_table')) {
        include_once(G5_PATH.'/willow/notification.lib.php');
    }

    willow_notification_install();
    $table = willow_subscription_table();
    $limit = max(1, (int) $limit);
    $avatars = array();
    $result = sql_query(" select m.*
        from `{$table}` s
        left join {$g5['member_table']} m on m.mb_id = s.subscriber_mb_id
        where s.author_mb_id = '".sql_escape_string($author_id)."'
            and s.ws_status = 'active'
        order by s.ws_datetime desc
        limit {$limit} ", false);

    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $avatars[] = willow_member_avatar($row);
        }
    }

    return $avatars;
}

function willow_board_post_to_feed($post)
{
    $author = array();
    if (!empty($post['mb_id'])) {
        $author = get_member($post['mb_id']);
    }

    $author_name = $post['wr_name'];
    if (!empty($author['mb_nick'])) {
        $author_name = $author['mb_nick'];
    }

    $images = !empty($post['wr_5']) ? willow_split_images($post['wr_5']) : array();
    $image = isset($images[0]) ? $images[0] : '';

    return array(
        'id' => (int) $post['wr_id'],
        'mb_id' => $post['mb_id'],
        'target_type' => 'board',
        'author' => get_text($author_name ? $author_name : '윌로우 회원'),
        'date' => willow_format_date($post['wr_datetime']),
        'sort_datetime' => $post['wr_datetime'],
        'avatar' => willow_member_avatar($author),
        'raw_content' => $post['wr_content'],
        'body' => get_text(willow_content_excerpt($post['wr_content'], 180)),
        'excerpt' => get_text(willow_content_excerpt($post['wr_content'], 92)),
        'image' => $image,
        'images' => $images,
        'liked' => function_exists('willow_has_liked') ? willow_has_liked('board', (int) $post['wr_id']) : false,
        'likes' => function_exists('willow_like_count') ? number_format(willow_like_count('board', (int) $post['wr_id'])) : number_format((int) $post['wr_good']),
        'comments' => function_exists('willow_comment_count') ? number_format(willow_comment_count('board', (int) $post['wr_id'])) : number_format((int) $post['wr_comment']),
        'views' => (int) $post['wr_hit'],
        'verified' => willow_author_is_escapee($author),
        'actions' => $post['wr_3'] === 'free',
        'href' => G5_URL.'/willow/post.php?wr_id='.(int) $post['wr_id'],
        'title' => get_text($post['wr_subject']),
        'category' => get_text($post['wr_1']),
        'tags' => array_filter(array_map('trim', explode(',', $post['wr_2']))),
        'access' => $post['wr_3'],
    );
}

function willow_get_board_posts($limit = 10, $keyword = '')
{
    $table = willow_content_table();
    $limit = max(1, (int) $limit);
    $where = " where wr_is_comment = 0 ";

    if ($keyword !== '') {
        $keyword = sql_escape_string($keyword);
        $where .= " and (wr_subject like '%{$keyword}%' or wr_content like '%{$keyword}%' or wr_name like '%{$keyword}%' or wr_1 like '%{$keyword}%' or wr_2 like '%{$keyword}%') ";
    }

    $posts = array();
    $result = sql_query(" select * from {$table} {$where} order by wr_datetime desc, wr_id desc limit {$limit} ", false);
    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $posts[] = willow_board_post_to_feed($row);
        }
    }

    return $posts;
}

function willow_get_popular_board_posts($limit = 10)
{
    $table = willow_content_table();
    $limit = max(1, (int) $limit);
    $posts = array();
    $result = sql_query(" select *
        from {$table}
        where wr_is_comment = 0
        order by wr_hit desc, wr_datetime desc, wr_id desc
        limit {$limit} ", false);

    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $posts[] = willow_board_post_to_feed($row);
        }
    }

    return $posts;
}

function willow_add_search_suggestion(&$suggestions, &$seen, $label, $keyword = '', $type = 'keyword')
{
    $label = trim(strip_tags($label));
    $keyword = trim(strip_tags($keyword !== '' ? $keyword : $label));

    if ($label === '' || $keyword === '') {
        return;
    }

    $key = function_exists('mb_strtolower') ? mb_strtolower($keyword, 'UTF-8') : strtolower($keyword);
    if (isset($seen[$key])) {
        return;
    }

    $seen[$key] = true;
    $suggestions[] = array(
        'label' => get_text($label),
        'keyword' => get_text($keyword),
        'type' => $type,
    );
}

function willow_get_search_suggestions($seed_words = array(), $limit = 40)
{
    global $g5;

    $limit = max(1, (int) $limit);
    $suggestions = array();
    $seen = array();

    foreach (willow_get_categories(true) as $category) {
        if ($category['keyword'] === '') {
            continue;
        }
        willow_add_search_suggestion($suggestions, $seen, $category['label'], $category['keyword'], 'keyword');
    }

    foreach ($seed_words as $word) {
        $keyword = ltrim(trim($word), '#');
        if ($keyword === '전체보기') {
            continue;
        }
        willow_add_search_suggestion($suggestions, $seen, $keyword, $keyword, 'keyword');
    }

    foreach (willow_get_authors('', 20) as $author_row) {
        $author = willow_author_card($author_row);
        willow_add_search_suggestion($suggestions, $seen, $author['name'].' 작가', $author['name'], 'author');

        foreach (array_slice($author['tags'], 0, 5) as $tag) {
            willow_add_search_suggestion($suggestions, $seen, $tag, $tag, 'keyword');
        }
    }

    $table = willow_content_table();
    $result = sql_query(" select wr_subject, wr_1, wr_2, wr_name from {$table} where wr_is_comment = 0 order by wr_datetime desc, wr_id desc limit 30 ", false);
    if ($result) {
        while ($row = sql_fetch_array($result)) {
            willow_add_search_suggestion($suggestions, $seen, $row['wr_name'].' 작가', $row['wr_name'], 'author');
            willow_add_search_suggestion($suggestions, $seen, $row['wr_1'], $row['wr_1'], 'keyword');

            foreach (explode(',', $row['wr_2']) as $tag) {
                willow_add_search_suggestion($suggestions, $seen, $tag, $tag, 'keyword');
            }

            willow_add_search_suggestion($suggestions, $seen, $row['wr_subject'], $row['wr_subject'], 'keyword');
        }
    }

    return array_slice($suggestions, 0, $limit);
}

function willow_get_recommended_posts($limit = 3)
{
    $table = willow_content_table();
    $limit = max(1, (int) $limit);
    $posts = array();
    $seen_authors = array();
    $recent_from = date('Y-m-d 00:00:00', strtotime('-90 days', G5_SERVER_TIME));
    $queries = array(
        " select * from {$table}
            where wr_is_comment = 0
                and wr_datetime >= '".sql_escape_string($recent_from)."'
            order by wr_hit desc, wr_datetime desc, wr_id desc
            limit ".($limit * 8),
        " select * from {$table}
            where wr_is_comment = 0
            order by wr_hit desc, wr_datetime desc, wr_id desc
            limit ".($limit * 8),
    );

    foreach ($queries as $query) {
        if (count($posts) >= $limit) {
            break;
        }

        $result = sql_query($query, false);
        if (!$result) {
            continue;
        }

        while ($row = sql_fetch_array($result)) {
            $author_key = $row['mb_id'] ? 'm:'.$row['mb_id'] : 'n:'.$row['wr_name'];
            if (isset($seen_authors[$author_key])) {
                continue;
            }

            $seen_authors[$author_key] = true;
            $posts[] = willow_board_post_to_feed($row);

            if (count($posts) >= $limit) {
                break;
            }
        }
    }

    return $posts;
}

function willow_personalization_profile()
{
    global $member;

    $profile = array(
        'mb_id' => !empty($member['mb_id']) ? $member['mb_id'] : '',
        'authors' => array(),
        'keywords' => array(),
    );

    if ($profile['mb_id'] === '') {
        return $profile;
    }

    $mb_id = sql_escape_string($profile['mb_id']);

    if (!function_exists('willow_topic_tables')) {
        include_once(G5_PATH.'/willow/topic.lib.php');
    }
    if (function_exists('willow_topic_install')) {
        willow_topic_install();
    }
    $tables = function_exists('willow_topic_tables') ? willow_topic_tables() : array();
    $like_table = !empty($tables['like']) ? $tables['like'] : G5_TABLE_PREFIX.'willow_like';
    $comment_table = !empty($tables['comment']) ? $tables['comment'] : G5_TABLE_PREFIX.'willow_comment';

    if (!function_exists('willow_subscription_table')) {
        include_once(G5_PATH.'/willow/notification.lib.php');
    }
    if (function_exists('willow_notification_install')) {
        willow_notification_install();
        $subscription_table = willow_subscription_table();
        $result = sql_query(" select author_mb_id
            from `{$subscription_table}`
            where subscriber_mb_id = '{$mb_id}' and ws_status = 'active'
            limit 80 ", false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                if ($row['author_mb_id'] !== '') {
                    $profile['authors'][$row['author_mb_id']] = 35;
                }
            }
        }
    }

    $board_table = willow_content_table();
    $liked_board = sql_query(" select b.mb_id, b.wr_name, b.wr_1, b.wr_2
        from `{$like_table}` l
        inner join {$board_table} b on b.wr_id = l.target_id
        where l.target_type = 'board'
            and l.mb_id = '{$mb_id}'
            and b.wr_is_comment = 0
        order by l.wl_datetime desc
        limit 80 ", false);
    if ($liked_board) {
        while ($row = sql_fetch_array($liked_board)) {
            willow_personalization_absorb_board_row($profile, $row, 18);
        }
    }

    $commented_board = sql_query(" select b.mb_id, b.wr_name, b.wr_1, b.wr_2
        from `{$comment_table}` c
        inner join {$board_table} b on b.wr_id = c.target_id
        where c.target_type = 'board'
            and c.mb_id = '{$mb_id}'
            and b.wr_is_comment = 0
        order by c.wc_datetime desc
        limit 80 ", false);
    if ($commented_board) {
        while ($row = sql_fetch_array($commented_board)) {
            willow_personalization_absorb_board_row($profile, $row, 24);
        }
    }

    if (!empty($tables['post'])) {
        $liked_topic = sql_query(" select p.mb_id, p.wp_author, p.wp_subject, t.wt_subject
            from `{$like_table}` l
            inner join `{$tables['post']}` p on p.wp_id = l.target_id
            left join `{$tables['topic']}` t on t.wt_id = p.wt_id
            where l.target_type = 'topic'
                and l.mb_id = '{$mb_id}'
            order by l.wl_datetime desc
            limit 80 ", false);
        if ($liked_topic) {
            while ($row = sql_fetch_array($liked_topic)) {
                willow_personalization_absorb_topic_row($profile, $row, 18);
            }
        }

        $commented_topic = sql_query(" select p.mb_id, p.wp_author, p.wp_subject, t.wt_subject
            from `{$comment_table}` c
            inner join `{$tables['post']}` p on p.wp_id = c.target_id
            left join `{$tables['topic']}` t on t.wt_id = p.wt_id
            where c.target_type = 'topic'
                and c.mb_id = '{$mb_id}'
            order by c.wc_datetime desc
            limit 80 ", false);
        if ($commented_topic) {
            while ($row = sql_fetch_array($commented_topic)) {
                willow_personalization_absorb_topic_row($profile, $row, 24);
            }
        }
    }

    return $profile;
}

function willow_personalization_add_keyword(&$profile, $keyword, $weight)
{
    $keyword = trim(strip_tags((string) $keyword));
    if ($keyword === '') {
        return;
    }

    $key = function_exists('mb_strtolower') ? mb_strtolower($keyword, 'UTF-8') : strtolower($keyword);
    if (!isset($profile['keywords'][$key])) {
        $profile['keywords'][$key] = 0;
    }
    $profile['keywords'][$key] += $weight;
}

function willow_personalization_add_author(&$profile, $mb_id, $author_name, $weight)
{
    $key = $mb_id !== '' ? $mb_id : trim($author_name);
    if ($key === '') {
        return;
    }

    if (!isset($profile['authors'][$key])) {
        $profile['authors'][$key] = 0;
    }
    $profile['authors'][$key] += $weight;
}

function willow_personalization_absorb_board_row(&$profile, $row, $weight)
{
    willow_personalization_add_author($profile, isset($row['mb_id']) ? $row['mb_id'] : '', isset($row['wr_name']) ? $row['wr_name'] : '', $weight);
    willow_personalization_add_keyword($profile, isset($row['wr_1']) ? $row['wr_1'] : '', $weight * 0.7);

    foreach (explode(',', isset($row['wr_2']) ? $row['wr_2'] : '') as $tag) {
        willow_personalization_add_keyword($profile, $tag, $weight * 0.45);
    }
}

function willow_personalization_absorb_topic_row(&$profile, $row, $weight)
{
    willow_personalization_add_author($profile, isset($row['mb_id']) ? $row['mb_id'] : '', isset($row['wp_author']) ? $row['wp_author'] : '', $weight);
    willow_personalization_add_keyword($profile, isset($row['wt_subject']) ? $row['wt_subject'] : '', $weight * 0.5);
    willow_personalization_add_keyword($profile, isset($row['wp_subject']) ? $row['wp_subject'] : '', $weight * 0.35);
}

function willow_personalization_score($item, $profile)
{
    $timestamp = !empty($item['sort_datetime']) ? strtotime($item['sort_datetime']) : 0;
    $age_hours = $timestamp ? max(0, (G5_SERVER_TIME - $timestamp) / 3600) : 720;
    $recency = max(0, 40 - min(40, $age_hours / 6));
    $likes = isset($item['likes_raw']) ? (int) $item['likes_raw'] : (int) str_replace(',', '', isset($item['likes']) ? $item['likes'] : 0);
    $comments = isset($item['comments_raw']) ? (int) $item['comments_raw'] : (int) str_replace(',', '', isset($item['comments']) ? $item['comments'] : 0);
    $views = isset($item['views']) ? (int) $item['views'] : 0;
    $score = $recency + ($likes * 3) + ($comments * 5) + log(max(1, $views + 1), 2);

    $author_keys = array();
    if (!empty($item['mb_id'])) {
        $author_keys[] = $item['mb_id'];
    }
    if (!empty($item['author'])) {
        $author_keys[] = $item['author'];
    }
    foreach ($author_keys as $author_key) {
        if (isset($profile['authors'][$author_key])) {
            $score += $profile['authors'][$author_key];
        }
    }

    $haystack = array();
    foreach (array('title', 'category', 'body', 'excerpt') as $field) {
        if (!empty($item[$field])) {
            $haystack[] = $item[$field];
        }
    }
    if (!empty($item['tags']) && is_array($item['tags'])) {
        $haystack = array_merge($haystack, $item['tags']);
    }
    $haystack = function_exists('mb_strtolower') ? mb_strtolower(implode(' ', $haystack), 'UTF-8') : strtolower(implode(' ', $haystack));
    foreach ($profile['keywords'] as $keyword => $weight) {
        if ($keyword !== '' && strpos($haystack, $keyword) !== false) {
            $score += min(18, $weight);
        }
    }

    if (!empty($item['verified'])) {
        $score += 2;
    }

    return $score;
}

function willow_get_personalized_feed($offset = 0, $limit = 6)
{
    $offset = max(0, (int) $offset);
    $limit = max(1, (int) $limit);
    $fetch_limit = max(80, $offset + $limit + 40);
    $profile = willow_personalization_profile();
    $items = array();
    $seen = array();

    foreach (willow_get_board_posts($fetch_limit) as $item) {
        $key = $item['target_type'].':'.$item['id'];
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $item['likes_raw'] = (int) str_replace(',', '', $item['likes']);
        $item['comments_raw'] = (int) str_replace(',', '', $item['comments']);
        $item['personal_score'] = willow_personalization_score($item, $profile);
        $items[] = $item;
    }

    if (!function_exists('willow_topic_tables')) {
        include_once(G5_PATH.'/willow/topic.lib.php');
    }
    if (function_exists('willow_topic_install')) {
        willow_topic_install();
        $tables = willow_topic_tables();
        $result = sql_query(" select p.*, t.wt_subject
            from `{$tables['post']}` p
            left join `{$tables['topic']}` t on t.wt_id = p.wt_id
            order by p.wp_datetime desc, p.wp_id desc
            limit {$fetch_limit} ", false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $item = willow_topic_post_to_feed($row);
                $key = $item['target_type'].':'.$item['id'];
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $item['category'] = get_text($row['wt_subject']);
                $item['likes_raw'] = isset($row['wp_like']) ? (int) $row['wp_like'] : (int) str_replace(',', '', $item['likes']);
                $item['comments_raw'] = isset($row['wp_comment']) ? (int) $row['wp_comment'] : (int) str_replace(',', '', $item['comments']);
                $item['personal_score'] = willow_personalization_score($item, $profile);
                $items[] = $item;
            }
        }
    }

    usort($items, function($left, $right) {
        if ($left['personal_score'] == $right['personal_score']) {
            return strcmp($right['sort_datetime'], $left['sort_datetime']);
        }

        return $left['personal_score'] < $right['personal_score'] ? 1 : -1;
    });

    $balanced = array();
    $author_seen = array();
    foreach ($items as $item) {
        $author_key = !empty($item['mb_id']) ? $item['mb_id'] : $item['author'];
        if (isset($author_seen[$author_key]) && $author_seen[$author_key] >= 2 && count($balanced) < $offset + $limit) {
            continue;
        }
        if (!isset($author_seen[$author_key])) {
            $author_seen[$author_key] = 0;
        }
        $author_seen[$author_key]++;
        $balanced[] = $item;
    }

    if (count($balanced) < count($items)) {
        foreach ($items as $item) {
            $key = $item['target_type'].':'.$item['id'];
            $exists = false;
            foreach ($balanced as $balanced_item) {
                if ($balanced_item['target_type'].':'.$balanced_item['id'] === $key) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $balanced[] = $item;
            }
        }
    }

    return array_slice($balanced, $offset, $limit);
}

function willow_render_post_card($post)
{
    global $member;

    $is_post_owner = !empty($member['mb_id']) && !empty($post['mb_id']) && $member['mb_id'] === $post['mb_id'];

    ob_start();
    ?>
    <article class="willow_post_card" data-feed-item="<?php echo $post['target_type']; ?>:<?php echo (int) $post['id']; ?>">
        <div class="willow_post_head">
            <img src="<?php echo $post['avatar']; ?>" alt="">
            <div>
                <strong><?php echo $post['author']; ?><?php if (!empty($post['verified'])) { ?><img class="willow_author_cert_mark" src="<?php echo G5_IMG_URL; ?>/img_cert_mark.png" alt="탈북이주민 인증"><?php } ?></strong>
                <span><?php echo $post['date']; ?></span>
            </div>
            <div class="willow_more">
                <button class="willow_more_button" type="button" aria-label="더보기" aria-expanded="false">
                    <span aria-hidden="true"></span>
                </button>
                <div class="willow_more_menu" role="menu">
                    <?php if ($is_post_owner) { ?>
                    <button type="button" role="menuitem">수정하기</button>
                    <button type="button" role="menuitem">삭제하기</button>
                    <?php } else { ?>
                    <button type="button" role="menuitem" class="willow_report_button" data-target-type="<?php echo $post['target_type']; ?>" data-target-id="<?php echo (int) $post['id']; ?>">신고하기</button>
                    <?php } ?>
                </div>
            </div>
        </div>
        <a href="<?php echo $post['href']; ?>">
            <?php if (!empty($post['title'])) { ?>
            <h3><?php echo $post['title']; ?></h3>
            <?php } ?>
            <p><?php echo $post['body']; ?></p>
            <?php if (!empty($post['image'])) { ?>
            <img class="willow_post_image" src="<?php echo $post['image']; ?>" alt="">
            <?php } ?>
        </a>
        <div class="willow_post_meta">
            <button type="button" class="willow_like_button <?php echo !empty($post['liked']) ? 'is_liked' : ''; ?>" data-target-type="<?php echo $post['target_type']; ?>" data-target-id="<?php echo (int) $post['id']; ?>" aria-pressed="<?php echo !empty($post['liked']) ? 'true' : 'false'; ?>">
                <img class="willow_meta_icon" src="<?php echo G5_IMG_URL; ?>/ico_heart<?php echo !empty($post['liked']) ? '_active' : ''; ?>.png" alt="" data-icon-heart data-icon-default="<?php echo G5_IMG_URL; ?>/ico_heart.png" data-icon-active="<?php echo G5_IMG_URL; ?>/ico_heart_active.png"><span data-like-count><?php echo (int) str_replace(',', '', $post['likes']) > 0 ? $post['likes'] : ''; ?></span>
            </button>
            <a class="willow_comment_link" href="<?php echo $post['href']; ?>#willow_comments">
                <img class="willow_meta_icon" src="<?php echo G5_IMG_URL; ?>/ico_rep.png" alt=""><span data-comment-count><?php echo (int) str_replace(',', '', $post['comments']) > 0 ? $post['comments'] : ''; ?></span>
            </a>
            <div class="willow_post_badges">
                <?php if (!empty($post['access']) && $post['access'] !== 'free') { ?>
                <span class="willow_post_badge is_subscribe">구독</span>
                <?php } ?>
                <?php if (!empty($post['category'])) { ?>
                <span class="willow_post_badge"><?php echo get_text($post['category']); ?></span>
                <?php } ?>
            </div>
        </div>
    </article>
    <?php
    return ob_get_clean();
}

function willow_get_recommended_authors($limit = 0, $days = 0)
{
    global $g5;

    $metric_config = willow_author_metric_config();
    $limit = (int) $limit > 0 ? (int) $limit : (int) $metric_config['wam_display_limit'];
    $days = (int) $days > 0 ? (int) $days : (int) $metric_config['wam_recent_days'];
    $limit = max(1, (int) $limit);
    $days = max(1, (int) $days);
    $candidate_limit = max($limit, (int) $metric_config['wam_candidate_limit']);
    $like_weight = (float) $metric_config['wam_like_weight'];
    $comment_weight = (float) $metric_config['wam_comment_weight'];
    $post_weight = (float) $metric_config['wam_post_weight'];
    $subscriber_weight = (float) $metric_config['wam_subscriber_weight'];
    $view_weight = (float) $metric_config['wam_view_weight'];
    $recent_from = date('Y-m-d 00:00:00', strtotime('-'.$days.' days', G5_SERVER_TIME));
    $recent_from_sql = sql_escape_string($recent_from);
    $board_table = willow_content_table();

    if (!function_exists('willow_topic_tables')) {
        include_once(G5_PATH.'/willow/topic.lib.php');
    }
    if (function_exists('willow_topic_install')) {
        willow_topic_install();
    }
    $topic_tables = function_exists('willow_topic_tables') ? willow_topic_tables() : array();
    $like_table = !empty($topic_tables['like']) ? $topic_tables['like'] : G5_TABLE_PREFIX.'willow_like';
    $comment_table = !empty($topic_tables['comment']) ? $topic_tables['comment'] : G5_TABLE_PREFIX.'willow_comment';

    $authors = array();
    $result = sql_query(" select *
        from {$g5['member_table']}
        where mb_leave_date = ''
            and mb_level < 10
            and (mb_level >= 3 or mb_2 = 'author')
        order by mb_level desc, mb_datetime desc
        limit {$candidate_limit} ", false);

    if (!$result) {
        return array();
    }

    while ($member_row = sql_fetch_array($result)) {
        $mb_id = $member_row['mb_id'];
        if ($mb_id === '') {
            continue;
        }

        $mb_id_sql = sql_escape_string($mb_id);
        $board_base = " from {$board_table}
            where wr_is_comment = 0
                and mb_id = '{$mb_id_sql}'
                and wr_datetime >= '{$recent_from_sql}' ";

        $board = sql_fetch(" select count(*) as post_count, coalesce(sum(wr_hit), 0) as views, max(wr_datetime) as recent_datetime {$board_base} ", false);
        $board_likes = sql_fetch(" select count(*) as cnt
            from `{$like_table}` l
            inner join {$board_table} b on b.wr_id = l.target_id
            where l.target_type = 'board'
                and b.wr_is_comment = 0
                and b.mb_id = '{$mb_id_sql}'
                and b.wr_datetime >= '{$recent_from_sql}' ", false);
        $board_comments = sql_fetch(" select count(*) as cnt
            from `{$comment_table}` c
            inner join {$board_table} b on b.wr_id = c.target_id
            where c.target_type = 'board'
                and b.wr_is_comment = 0
                and b.mb_id = '{$mb_id_sql}'
                and b.wr_datetime >= '{$recent_from_sql}' ", false);

        $post_count = isset($board['post_count']) ? (int) $board['post_count'] : 0;
        $views = isset($board['views']) ? (int) $board['views'] : 0;
        $likes = isset($board_likes['cnt']) ? (int) $board_likes['cnt'] : 0;
        $comments = isset($board_comments['cnt']) ? (int) $board_comments['cnt'] : 0;
        $recent_datetime = !empty($board['recent_datetime']) ? $board['recent_datetime'] : '';

        if (!empty($topic_tables['post']) && !empty($topic_tables['like']) && !empty($topic_tables['comment'])) {
            $topic = sql_fetch(" select count(*) as post_count, max(wp_datetime) as recent_datetime
                from `{$topic_tables['post']}`
                where mb_id = '{$mb_id_sql}'
                    and wp_datetime >= '{$recent_from_sql}' ", false);
            $topic_likes = sql_fetch(" select count(*) as cnt
                from `{$topic_tables['like']}` l
                inner join `{$topic_tables['post']}` p on p.wp_id = l.target_id
                where l.target_type = 'topic'
                    and p.mb_id = '{$mb_id_sql}'
                    and p.wp_datetime >= '{$recent_from_sql}' ", false);
            $topic_comments = sql_fetch(" select count(*) as cnt
                from `{$topic_tables['comment']}` c
                inner join `{$topic_tables['post']}` p on p.wp_id = c.target_id
                where c.target_type = 'topic'
                    and p.mb_id = '{$mb_id_sql}'
                    and p.wp_datetime >= '{$recent_from_sql}' ", false);

            $post_count += isset($topic['post_count']) ? (int) $topic['post_count'] : 0;
            $likes += isset($topic_likes['cnt']) ? (int) $topic_likes['cnt'] : 0;
            $comments += isset($topic_comments['cnt']) ? (int) $topic_comments['cnt'] : 0;
            if (!empty($topic['recent_datetime']) && (!$recent_datetime || strcmp($topic['recent_datetime'], $recent_datetime) > 0)) {
                $recent_datetime = $topic['recent_datetime'];
            }
        }

        if ($post_count < 1) {
            continue;
        }

        $name = $member_row['mb_nick'] ? $member_row['mb_nick'] : $member_row['mb_name'];
        $subscriber_count = willow_author_subscriber_count($mb_id);
        $score = ($comments * $comment_weight) + ($likes * $like_weight) + ($post_count * $post_weight) + ($subscriber_count * $subscriber_weight) + ($views * $view_weight);
        $profile = willow_content_plain_text($member_row['mb_profile'] ? $member_row['mb_profile'] : '윌로우에서 활동하는 작가입니다.');

        $authors[] = array(
            'id' => $mb_id,
            'name' => get_text($name ? $name : '윌로우 작가'),
            'name_html' => willow_author_name_html($member_row),
            'role_name_html' => willow_author_name_html($member_row, ' 작가'),
            'verified' => willow_author_is_escapee($member_row),
            'avatar' => willow_member_avatar($member_row),
            'profile' => get_text(cut_str($profile, 54, '...')),
            'score' => $score,
            'likes' => $likes,
            'comments' => $comments,
            'post_count' => $post_count,
            'views' => $views,
            'subscriber_count' => $subscriber_count,
            'recent_datetime' => $recent_datetime,
            'recent_time' => $recent_datetime ? strtotime($recent_datetime) : 0,
            'href' => G5_URL.'/willow/author.php?author='.urlencode($mb_id),
        );
    }

    usort($authors, function($left, $right) {
        if ($left['score'] === $right['score']) {
            if ($left['recent_time'] === $right['recent_time']) {
                return $right['subscriber_count'] <=> $left['subscriber_count'];
            }

            return $right['recent_time'] <=> $left['recent_time'];
        }

        return $right['score'] <=> $left['score'];
    });

    return array_slice($authors, 0, $limit);
}

function willow_get_author_board_posts($mb_id, $author_name = '', $access_group = 'free', $exclude_wr_id = 0, $limit = 3)
{
    $table = willow_content_table();
    $limit = max(1, (int) $limit);
    $exclude_wr_id = (int) $exclude_wr_id;
    $where = " where wr_is_comment = 0 ";

    if ($mb_id !== '') {
        $where .= " and mb_id = '".sql_escape_string($mb_id)."' ";
    } else if ($author_name !== '') {
        $where .= " and wr_name = '".sql_escape_string($author_name)."' ";
    } else {
        return array();
    }

    if ($access_group === 'free') {
        $where .= " and wr_3 = 'free' ";
    } else {
        $where .= " and wr_3 <> 'free' ";
    }

    if ($exclude_wr_id) {
        $where .= " and wr_id <> '{$exclude_wr_id}' ";
    }

    $posts = array();
    $result = sql_query(" select * from {$table} {$where} order by wr_datetime desc, wr_id desc limit {$limit} ", false);
    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $posts[] = willow_board_post_to_feed($row);
        }
    }

    return $posts;
}

function willow_get_author_topic_posts($mb_id, $author_name = '', $exclude_wp_id = 0, $limit = 3)
{
    if (!function_exists('willow_topic_tables')) {
        include_once(G5_PATH.'/willow/topic.lib.php');
    }

    $tables = willow_topic_tables();
    willow_topic_install();

    $limit = max(1, (int) $limit);
    $exclude_wp_id = (int) $exclude_wp_id;
    $where = " where 1 ";

    if ($mb_id !== '') {
        $where .= " and mb_id = '".sql_escape_string($mb_id)."' ";
    } else if ($author_name !== '') {
        $where .= " and wp_author = '".sql_escape_string($author_name)."' ";
    } else {
        return array();
    }

    if ($exclude_wp_id) {
        $where .= " and wp_id <> '{$exclude_wp_id}' ";
    }

    $posts = array();
    $result = sql_query(" select * from `{$tables['post']}` {$where} order by wp_datetime desc, wp_id desc limit {$limit} ", false);
    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $posts[] = willow_topic_post_to_feed($row);
        }
    }

    return $posts;
}

function willow_get_author_other_posts($mb_id, $author_name, $exclude_type, $exclude_id, $limit = 3)
{
    $exclude_wr_id = $exclude_type === 'board' ? (int) $exclude_id : 0;
    $exclude_wp_id = $exclude_type === 'topic' ? (int) $exclude_id : 0;

    $free_posts = willow_get_author_board_posts($mb_id, $author_name, 'free', $exclude_wr_id, $limit);
    $paid_posts = array_merge(
        willow_get_author_board_posts($mb_id, $author_name, 'paid', $exclude_wr_id, $limit),
        willow_get_author_topic_posts($mb_id, $author_name, $exclude_wp_id, $limit)
    );
    $items = array_merge($free_posts, $paid_posts);

    usort($items, function($a, $b) {
        $a_time = !empty($a['sort_datetime']) ? $a['sort_datetime'] : str_replace('.', '-', $a['date']);
        $b_time = !empty($b['sort_datetime']) ? $b['sort_datetime'] : str_replace('.', '-', $b['date']);

        return strcmp($b_time, $a_time);
    });

    return array(
        'free' => array_slice($free_posts, 0, $limit),
        'paid' => array_slice($paid_posts, 0, $limit),
        'items' => array_slice($items, 0, $limit * 2),
    );
}

function willow_get_author_posts($mb_id, $author_name = '', $offset = 0, $limit = 10)
{
    $offset = max(0, (int) $offset);
    $limit = max(1, (int) $limit);
    $fetch_limit = $offset + $limit + 1;
    $items = array_merge(
        willow_get_author_board_posts($mb_id, $author_name, 'free', 0, $fetch_limit),
        willow_get_author_board_posts($mb_id, $author_name, 'paid', 0, $fetch_limit),
        willow_get_author_topic_posts($mb_id, $author_name, 0, $fetch_limit)
    );

    usort($items, function($a, $b) {
        $a_time = !empty($a['sort_datetime']) ? $a['sort_datetime'] : str_replace('.', '-', $a['date']);
        $b_time = !empty($b['sort_datetime']) ? $b['sort_datetime'] : str_replace('.', '-', $b['date']);

        return strcmp($b_time, $a_time);
    });

    return array_slice($items, $offset, $limit);
}

function willow_get_board_post($wr_id)
{
    $table = willow_content_table();
    $wr_id = (int) $wr_id;
    if (!$wr_id) {
        return array();
    }

    $row = sql_fetch(" select * from {$table} where wr_id = '{$wr_id}' and wr_is_comment = 0 ");
    if (empty($row['wr_id'])) {
        return array();
    }

    return willow_board_post_to_feed($row);
}

function willow_report_target_info($target_type, $target_id)
{
    $target_type = $target_type === 'topic' ? 'topic' : 'board';
    $target_id = (int) $target_id;
    if ($target_id < 1) {
        return array();
    }

    if ($target_type === 'board') {
        $table = willow_content_table();
        $row = sql_fetch(" select wr_id, mb_id, wr_name, wr_subject
            from {$table}
            where wr_id = '{$target_id}' and wr_is_comment = 0
            limit 1 ", false);
        if (empty($row['wr_id'])) {
            return array();
        }

        return array(
            'target_type' => 'board',
            'target_id' => (int) $row['wr_id'],
            'title' => get_text($row['wr_subject']),
            'author_mb_id' => $row['mb_id'],
            'author_name' => get_text($row['wr_name']),
            'href' => G5_URL.'/willow/post.php?wr_id='.(int) $row['wr_id'],
        );
    }

    if (!function_exists('willow_topic_tables')) {
        include_once(G5_PATH.'/willow/topic.lib.php');
    }
    $tables = willow_topic_tables();
    willow_topic_install();
    $row = sql_fetch(" select wp_id, mb_id, wp_author, wp_subject
        from `{$tables['post']}`
        where wp_id = '{$target_id}'
        limit 1 ", false);
    if (empty($row['wp_id'])) {
        return array();
    }

    return array(
        'target_type' => 'topic',
        'target_id' => (int) $row['wp_id'],
        'title' => get_text($row['wp_subject']),
        'author_mb_id' => $row['mb_id'],
        'author_name' => get_text($row['wp_author']),
        'href' => G5_URL.'/willow/post.php?wp_id='.(int) $row['wp_id'],
    );
}
