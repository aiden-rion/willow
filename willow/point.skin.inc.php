<?php
if (!defined('_GNUBOARD_')) exit;

add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/willow_content.css?ver='.G5_CSS_VER.'">', 10);

$point_filters = array(
    'all' => '전체',
    'earn' => '적립',
    'use' => '사용',
);
$current_filter = isset($point_filters[$point_type]) ? $point_type : 'all';
$available_point = isset($willow_point_balance['available_amount']) ? (int) $willow_point_balance['available_amount'] : 0;
?>

<main class="willow_point_app">
    <header class="willow_member_confirm_header willow_point_header">
        <a href="<?php echo G5_URL; ?>/willow/menu.php" aria-label="뒤로가기"></a>
        <h1>포인트</h1>
    </header>

    <section class="willow_point_intro">
        <h2>포인트 적립내역을 확인하세요</h2>
        <p>포인트 정산은 10,000포인트 이상 가능합니다.</p>
    </section>

    <section class="willow_point_summary">
        <dl>
            <div>
                <dt>보유포인트</dt>
                <dd><?php echo number_format((int) $member['mb_point']); ?></dd>
            </div>
            <div>
                <dt>정산가능 포인트</dt>
                <dd><?php echo number_format($available_point); ?></dd>
            </div>
        </dl>
    </section>

    <form class="willow_point_filter" method="get" action="<?php echo G5_BBS_URL; ?>/point.php">
        <label for="point_type" class="sound_only">포인트 내역 구분</label>
        <select name="point_type" id="point_type" onchange="this.form.submit()">
            <?php foreach ($point_filters as $filter_key => $filter_label) { ?>
            <option value="<?php echo $filter_key; ?>" <?php echo $current_filter === $filter_key ? 'selected' : ''; ?>><?php echo $filter_label; ?></option>
            <?php } ?>
        </select>
    </form>

    <section class="willow_point_history" aria-label="포인트 내역">
        <?php if ($list) { ?>
            <?php foreach ((array) $list as $row) {
                $is_plus = (int) $row['po_point'] > 0;
                $point_label = $is_plus ? '적립' : '사용';
                $point_class = $is_plus ? 'is_plus' : 'is_minus';
                $rel_text = trim($row['po_rel_table'].' '.$row['po_rel_action']);
            ?>
            <article class="willow_point_card <?php echo $point_class; ?>">
                <div class="willow_point_card_head">
                    <span><?php echo $point_label; ?></span>
                    <time datetime="<?php echo get_text($row['po_datetime']); ?>"><?php echo get_text($row['po_datetime']); ?></time>
                </div>
                <h3><?php echo get_text($row['po_content']); ?></h3>
                <?php if ($rel_text !== '') { ?>
                <p><?php echo get_text($rel_text); ?></p>
                <?php } ?>
                <dl>
                    <div>
                        <dt><?php echo $point_label; ?>포인트</dt>
                        <dd><?php echo ($is_plus ? '+' : '').number_format((int) $row['po_point']); ?></dd>
                    </div>
                    <div>
                        <dt>잔여합계</dt>
                        <dd><?php echo number_format((int) $row['po_mb_point']); ?></dd>
                    </div>
                </dl>
            </article>
            <?php } ?>
        <?php } else { ?>
        <p class="willow_point_empty">포인트 내역이 없습니다.</p>
        <?php } ?>
    </section>

    <div class="willow_point_paging">
        <?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, $_SERVER['SCRIPT_NAME'].'?'.$willow_point_qstr.'&amp;page='); ?>
    </div>

    <?php if (!empty($willow_is_author)) { ?>
    <div class="willow_point_bottom">
        <a href="<?php echo G5_URL; ?>/willow/point_settlement.php">포인트 정산요청</a>
    </div>
    <?php } ?>
</main>
