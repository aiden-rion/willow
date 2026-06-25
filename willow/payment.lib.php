<?php
if (!defined('_GNUBOARD_')) exit;

function willow_payment_default_table()
{
    global $g5;

    $prefix = defined('G5_TABLE_PREFIX') ? G5_TABLE_PREFIX : (isset($g5['table_prefix']) ? $g5['table_prefix'] : 'g5_');

    return $prefix.'willow_payment_default';
}

function willow_payment_card_table()
{
    global $g5;

    return isset($g5['g5_subscription_mb_cardinfo_table']) ? $g5['g5_subscription_mb_cardinfo_table'] : G5_TABLE_PREFIX.'subscription_mb_cardinfo';
}

function willow_payment_install()
{
    static $installed = false;

    if ($installed) {
        return;
    }

    $table = willow_payment_default_table();
    sql_query(" create table if not exists `{$table}` (
        mb_id varchar(100) not null default '',
        ci_id int unsigned not null default 0,
        wp_datetime datetime not null,
        primary key (mb_id),
        key ci_id (ci_id)
    ) ", false);

    $installed = true;
}

function willow_payment_cards($mb_id)
{
    $cards = array();
    $card_table = willow_payment_card_table();
    $result = sql_query(" select *
        from `{$card_table}`
        where card_billkey <> ''
            and mb_id = '".sql_escape_string($mb_id)."'
        order by ci_id desc ", false);

    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $cards[] = $row;
        }
    }

    return $cards;
}

function willow_payment_default_id($mb_id)
{
    willow_payment_install();

    $table = willow_payment_default_table();
    $row = sql_fetch(" select ci_id from `{$table}` where mb_id = '".sql_escape_string($mb_id)."' ", false);

    return isset($row['ci_id']) ? (int) $row['ci_id'] : 0;
}

function willow_payment_set_default($mb_id, $ci_id)
{
    willow_payment_install();

    $table = willow_payment_default_table();
    sql_query(" insert into `{$table}`
            (mb_id, ci_id, wp_datetime)
        values
            ('".sql_escape_string($mb_id)."', '".(int) $ci_id."', '".G5_TIME_YMDHIS."')
        on duplicate key update
            ci_id = values(ci_id),
            wp_datetime = values(wp_datetime) ", false);
}

function willow_payment_default_card($mb_id)
{
    $cards = willow_payment_cards($mb_id);
    if (!$cards) {
        return array();
    }

    $default_id = willow_payment_default_id($mb_id);
    foreach ($cards as $card) {
        if ((int) $card['ci_id'] === $default_id) {
            return $card;
        }
    }

    willow_payment_set_default($mb_id, (int) $cards[0]['ci_id']);

    return $cards[0];
}
