<?php
include_once('./_common.php');
include_once('./content.lib.php');
include_once('./topic.lib.php');
include_once('./notification.lib.php');
include_once('./payment.lib.php');

function willow_subscribe_price($value)
{
    $price = (int) preg_replace('/[^0-9]/', '', (string) $value);

    return $price > 0 ? $price : 8800;
}

function willow_subscribe_author_post_count($mb_id)
{
    $mb_id = sql_escape_string($mb_id);
    $board_table = willow_content_table();
    $topic_tables = willow_topic_tables();

    $board = sql_fetch(" select count(*) as cnt from `{$board_table}` where wr_is_comment = 0 and mb_id = '{$mb_id}' ", false);
    $topic = sql_fetch(" select count(*) as cnt from `{$topic_tables['post']}` where mb_id = '{$mb_id}' ", false);

    return (int) $board['cnt'] + (int) $topic['cnt'];
}

function willow_subscribe_author_subscriber_count($mb_id)
{
    willow_notification_install();

    $table = willow_subscription_table();
    $mb_id = sql_escape_string($mb_id);
    $row = sql_fetch(" select count(*) as cnt from `{$table}` where author_mb_id = '{$mb_id}' and ws_status = 'active' ", false);

    return isset($row['cnt']) ? (int) $row['cnt'] : 0;
}

function willow_subscribe_author_row($mb_id = '')
{
    global $g5;

    $where = " where mb_leave_date = '' and mb_level < 10 and (mb_level >= 3 or mb_2 = 'author') ";
    if ($mb_id !== '') {
        $where .= " and mb_id = '".sql_escape_string($mb_id)."' ";
    }

    $row = sql_fetch(" select * from {$g5['member_table']} {$where} order by mb_level desc, mb_datetime desc limit 1 ", false);
    if (empty($row['mb_id'])) {
        return array();
    }

    $name = $row['mb_nick'] ? $row['mb_nick'] : $row['mb_name'];
    $subscriber_count = willow_subscribe_author_subscriber_count($row['mb_id']);

    return array(
        'id' => $row['mb_id'],
        'name' => get_text($name ? $name : '윌로우 작가'),
        'name_html' => willow_author_name_html($row),
        'role_name_html' => willow_author_name_html($row, ' 작가'),
        'verified' => willow_author_is_escapee($row),
        'avatar' => willow_member_avatar($row),
        'profile' => get_text($row['mb_profile'] ? $row['mb_profile'] : '윌로우에서 새로운 이야기를 전하는 작가입니다.'),
        'subscriber_count' => $subscriber_count,
        'post_count' => willow_subscribe_author_post_count($row['mb_id']),
        'price' => willow_subscribe_price($row['mb_1']),
    );
}

function willow_subscribe_user_card()
{
    global $member;

    if (empty($member['mb_id'])) {
        return array();
    }

    return willow_payment_default_card($member['mb_id']);
}

function willow_subscribe_is_active($author_id)
{
    global $member;

    if (empty($member['mb_id']) || $author_id === '') {
        return false;
    }

    willow_notification_install();
    $table = willow_subscription_table();
    $row = sql_fetch(" select ws_id from `{$table}`
        where author_mb_id = '".sql_escape_string($author_id)."'
            and subscriber_mb_id = '".sql_escape_string($member['mb_id'])."'
            and ws_status = 'active'
        limit 1 ", false);

    return !empty($row['ws_id']);
}

function willow_subscribe_my_items()
{
    global $g5, $member;

    if (empty($member['mb_id'])) {
        return array();
    }

    willow_notification_install();
    $table = willow_subscription_table();
    $items = array();
    $result = sql_query(" select s.*, m.mb_nick, m.mb_name, m.mb_profile, m.mb_1, m.mb_4
        from `{$table}` s
        left join {$g5['member_table']} m on m.mb_id = s.author_mb_id
        where s.subscriber_mb_id = '".sql_escape_string($member['mb_id'])."'
            and s.ws_status = 'active'
        order by s.ws_datetime desc ", false);

    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $author = willow_subscribe_author_row($row['author_mb_id']);
            if ($author) {
                $author['subscribed_at'] = $row['ws_datetime'];
                $items[] = $author;
            }
        }
    }

    return $items;
}

function willow_subscribe_my_detail($author_id)
{
    global $member;

    if (empty($member['mb_id']) || $author_id === '') {
        return array();
    }

    willow_notification_install();
    $table = willow_subscription_table();
    $row = sql_fetch(" select *
        from `{$table}`
        where subscriber_mb_id = '".sql_escape_string($member['mb_id'])."'
            and author_mb_id = '".sql_escape_string($author_id)."'
            and ws_status = 'active'
        limit 1 ", false);

    if (empty($row['ws_id'])) {
        return array();
    }

    $author = willow_subscribe_author_row($row['author_mb_id']);
    if (!$author) {
        return array();
    }

    $author['subscription_id'] = (int) $row['ws_id'];
    $author['subscribed_at'] = $row['ws_datetime'];
    $author['status'] = $row['ws_status'];

    return $author;
}

function willow_subscribe_payment_rows($subscription, $card)
{
    $rows = array();
    if (empty($subscription['subscribed_at'])) {
        return $rows;
    }

    $start = strtotime(substr($subscription['subscribed_at'], 0, 10).' '.substr($subscription['subscribed_at'], 11, 8));
    if (!$start) {
        return $rows;
    }

    $now = G5_SERVER_TIME;
    $cursor = $start;
    $limit = 36;

    while ($cursor <= $now && count($rows) < $limit) {
        $rows[] = array(
            'status' => '정상결제',
            'datetime' => date('Y.m.d H:i', $cursor),
            'product' => $subscription['name'].' 작가 구독료',
            'method' => $card ? '카드 정기결제' : '카드 정기결제',
            'amount' => $subscription['price'],
        );
        $cursor = strtotime('+1 month', $cursor);
    }

    return array_reverse($rows);
}

$mode = isset($_GET['mode']) ? preg_replace('/[^a-z_]/', '', $_GET['mode']) : '';
$step = isset($_GET['step']) ? preg_replace('/[^a-z_]/', '', $_GET['step']) : 'intro';
$author_id = isset($_GET['author']) ? trim($_GET['author']) : '';
$my_subscription_detail = array();
$my_payment_rows = array();

if ($mode === 'my') {
    if (!$is_member) {
        goto_url(G5_BBS_URL.'/login.php?url='.urlencode(G5_URL.'/willow/subscribe.php?mode=my'));
    }
    $my_subscriptions = willow_subscribe_my_items();
    if ($author_id !== '') {
        $my_subscription_detail = willow_subscribe_my_detail($author_id);
        if (!$my_subscription_detail) {
            alert('구독 내역을 찾을 수 없습니다.', G5_URL.'/willow/subscribe.php?mode=my');
        }
        $author = $my_subscription_detail;
    } else {
        $author = array();
    }
} else {
    $author = willow_subscribe_author_row($author_id);
    if (!$author) {
        alert('구독할 작가를 찾을 수 없습니다.', G5_URL);
    }
    $author_id = $author['id'];
}

if ($step !== 'intro' && $mode !== 'my' && !$is_member) {
    goto_url(G5_BBS_URL.'/login.php?url='.urlencode(G5_URL.'/willow/subscribe.php?author='.$author_id.'&step='.$step));
}

$card = $mode === 'my' ? willow_subscribe_user_card() : willow_subscribe_user_card();
$my_payment_rows = $my_subscription_detail ? willow_subscribe_payment_rows($my_subscription_detail, $card) : array();
$is_subscribed = $mode === 'my' ? false : willow_subscribe_is_active($author_id);
$today_text = date('Y.m.d');
$payment_return = G5_URL.'/willow/subscribe.php?author='.urlencode($author_id).'&step=confirm';
$payment_href = G5_URL.'/willow/payment.php'.($mode === 'my' ? '' : '?return='.urlencode($payment_return));
$complete = $step === 'complete';
$confirm = $step === 'confirm';

$g5['title'] = $mode === 'my' ? '나의 구독' : '구독하기';
include_once(G5_PATH.'/head.sub.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/willow_content.css?ver='.G5_CSS_VER.'">', 10);
?>

<main class="willow_subscribe_app <?php echo $confirm || $complete || $mode === 'my' ? 'is_light' : 'is_intro'; ?> <?php echo $my_subscription_detail ? 'is_my_detail' : ''; ?>">
    <header class="willow_subscribe_header">
        <?php if ($mode === 'my') { ?>
        <a class="willow_subscribe_back" href="<?php echo $my_subscription_detail ? G5_URL.'/willow/subscribe.php?mode=my' : G5_URL.'/willow/menu.php'; ?>" aria-label="뒤로가기"><img src="<?php echo G5_IMG_URL; ?>/ico_back.png" alt=""></a>
        <?php } else if ($confirm) { ?>
        <a class="willow_subscribe_back" href="<?php echo G5_URL; ?>/willow/subscribe.php?author=<?php echo urlencode($author_id); ?>" aria-label="뒤로가기"><img src="<?php echo G5_IMG_URL; ?>/ico_back.png" alt=""></a>
        <?php } ?>
        <h1><?php echo $mode === 'my' ? '나의 구독' : '구독하기'; ?></h1>
        <?php if (!$confirm && $mode !== 'my') { ?>
        <a class="willow_subscribe_close" href="<?php echo G5_URL; ?>" aria-label="닫기"></a>
        <?php } ?>
    </header>

    <?php if ($my_subscription_detail) { ?>
    <section class="willow_subscribe_payments">
        <div class="willow_subscribe_my_intro">
            <h2>총 <em><?php echo number_format(count($my_payment_rows)); ?></em>건의 결제내역이 있습니다.</h2>
            <p>구독일 : <?php echo get_text(str_replace('-', '.', substr($my_subscription_detail['subscribed_at'], 0, 10))); ?></p>
        </div>

        <form class="willow_subscribe_filter" method="get" action="<?php echo G5_URL; ?>/willow/subscribe.php">
            <input type="hidden" name="mode" value="my">
            <input type="hidden" name="author" value="<?php echo get_text($my_subscription_detail['id']); ?>">
            <label>
                <span class="sound_only">월 선택</span>
                <select name="month">
                    <option value=""><?php echo get_text(date('Y년 m월')); ?></option>
                </select>
            </label>
            <label>
                <span class="sound_only">작가 선택</span>
                <select name="author_select" onchange="if(this.value){location.href='<?php echo G5_URL; ?>/willow/subscribe.php?mode=my&amp;author='+encodeURIComponent(this.value);}">
                    <?php foreach ($my_subscriptions as $item) { ?>
                    <option value="<?php echo get_text($item['id']); ?>" <?php echo $item['id'] === $my_subscription_detail['id'] ? 'selected' : ''; ?>><?php echo $item['name']; ?> 작가</option>
                    <?php } ?>
                </select>
            </label>
        </form>
        <p class="willow_subscribe_filter_hint">* 해당 월/작가를 선택해주세요</p>

        <h2 class="willow_subscribe_section_title">결제내역</h2>
        <?php if ($my_payment_rows) { ?>
        <div class="willow_subscribe_payment_list">
            <?php foreach ($my_payment_rows as $payment) { ?>
            <article class="willow_subscribe_payment_item">
                <div class="willow_subscribe_payment_head">
                    <span><?php echo get_text($payment['status']); ?></span>
                    <time><?php echo get_text($payment['datetime']); ?></time>
                </div>
                <dl>
                    <div><dt>결제상품명</dt><dd><?php echo get_text($payment['product']); ?></dd></div>
                    <div><dt>결제수단</dt><dd><?php echo get_text($payment['method']); ?></dd></div>
                    <div><dt>결제금액</dt><dd><?php echo number_format($payment['amount']); ?>원</dd></div>
                </dl>
            </article>
            <?php } ?>
        </div>
        <?php } else { ?>
        <div class="willow_subscribe_empty_state">
            <strong>결제내역이 없습니다.</strong>
            <p>구독 결제 후 내역이 이곳에 표시됩니다.</p>
        </div>
        <?php } ?>
        <p class="willow_subscribe_policy_hint">* 카드결제내역은 5년간 보관됩니다.</p>
    </section>
    <?php } else if ($mode === 'my') { ?>
    <section class="willow_subscribe_my">
        <div class="willow_subscribe_my_intro">
            <h2>구독중인 작가 내역을<br>확인해보세요</h2>
            <p>구독 취소 시 현재 구독 기간 종료일까지 이용 가능합니다.</p>
        </div>
        <label class="willow_subscribe_status_select">
            <span class="sound_only">구독 상태</span>
            <select>
                <option>구독중</option>
            </select>
        </label>
        <?php if ($my_subscriptions) { ?>
        <div class="willow_subscribe_my_list">
            <?php foreach ($my_subscriptions as $item) { ?>
            <a class="willow_subscribe_my_item" href="<?php echo G5_URL; ?>/willow/subscribe.php?mode=my&amp;author=<?php echo urlencode($item['id']); ?>">
                <div class="willow_subscribe_my_item_top">
                    <span class="willow_subscribe_badge">구독중</span>
                    <span>구독일 : <?php echo get_text(str_replace('-', '.', substr($item['subscribed_at'], 0, 10))); ?></span>
                    <i aria-hidden="true"></i>
                </div>
                <div class="willow_subscribe_my_author">
                    <img src="<?php echo $item['avatar']; ?>" alt="">
                    <span>
                        <strong><?php echo $item['name']; ?> 작가</strong>
                        <small>구독일 : <?php echo get_text(str_replace('-', '.', substr($item['subscribed_at'], 0, 10))); ?></small>
                    </span>
                </div>
                <dl>
                    <div><dt>구독상품</dt><dd>베이직 구독상품</dd></div>
                    <div><dt>결제수단</dt><dd><?php echo $card ? '카드' : '카드'; ?></dd></div>
                    <div><dt>최근결제일</dt><dd><?php echo get_text(str_replace('-', '.', substr($item['subscribed_at'], 0, 10))); ?></dd></div>
                    <div><dt>구독상태</dt><dd>구독중</dd></div>
                </dl>
                <div class="willow_subscribe_fee">
                    <span>월 구독료 <em>(VAT포함)</em></span>
                    <strong><?php echo number_format($item['price']); ?>원</strong>
                </div>
                <span class="willow_subscribe_cancel">구독취소</span>
            </a>
            <?php } ?>
        </div>
        <?php } else { ?>
        <div class="willow_subscribe_empty_state">
            <strong>구독 중인 작가가 없습니다.</strong>
            <p>관심 있는 작가의 글에서 구독을 시작해보세요.</p>
            <a href="<?php echo G5_URL; ?>">글 둘러보기</a>
        </div>
        <?php } ?>
    </section>
    <?php } else if ($complete) { ?>
    <section class="willow_subscribe_complete">
        <div class="willow_complete_icon" aria-hidden="true"></div>
        <h2>구독등록이<br>완료되었습니다.</h2>
        <p>마이페이지를 통해 구독내역을<br>확인하실 수 있습니다.</p>
    </section>

    <section class="willow_subscribe_info">
        <h2>구독정보</h2>
        <dl>
            <div><dt>구독상품</dt><dd><?php echo $author['name']; ?> 작가 정기구독</dd></div>
            <div><dt>구독료</dt><dd><?php echo number_format($author['price']); ?>원</dd></div>
            <div><dt>구독시작일</dt><dd><?php echo $today_text; ?></dd></div>
            <div><dt>결제방식</dt><dd>카드 정기결제</dd></div>
        </dl>
    </section>

    <nav class="willow_subscribe_bottom is_split" aria-label="구독 완료 메뉴">
        <a href="<?php echo G5_URL; ?>/willow/menu.php">마이페이지</a>
        <a href="<?php echo G5_URL; ?>/willow/subscribe.php?mode=my">나의 구독</a>
    </nav>
    <?php } else if ($confirm) { ?>
    <section class="willow_subscribe_confirm_intro">
        <?php if ($card) { ?>
        <h2>아래의 카드정보로<br>신규구독이 진행됩니다.</h2>
        <p>기본카드 변경 및 카드 신규등록은 마이페이지에서 가능합니다.</p>
        <?php } else { ?>
        <h2>결제수단 등록 후<br>신규구독이 진행됩니다.</h2>
        <p>마이페이지에서 결제수단을 등록하거나 변경할 수 있습니다.</p>
        <?php } ?>
    </section>

    <section class="willow_subscribe_info">
        <h2>구독정보</h2>
        <dl>
            <div><dt>구독상품</dt><dd><?php echo $author['name']; ?> 작가 정기구독</dd></div>
            <div><dt>구독료</dt><dd><?php echo number_format($author['price']); ?>원</dd></div>
            <div><dt>구독시작일</dt><dd><?php echo $today_text; ?></dd></div>
            <div><dt>결제방식</dt><dd>카드 정기결제</dd></div>
        </dl>
    </section>

    <section class="willow_subscribe_card_box <?php echo $card ? '' : 'is_empty'; ?>">
        <?php if ($card) { ?>
        <div class="willow_subscribe_card_headline">
            <span><em>카드</em> 기본카드</span>
            <a href="<?php echo $payment_href; ?>">카드변경 <i class="fa fa-angle-right" aria-hidden="true"></i></a>
        </div>
        <strong><?php echo get_text($card['od_card_name'] ? $card['od_card_name'] : '등록카드'); ?> <?php echo get_text(substr($card['card_mask_number'], -4)); ?></strong>
        <dl>
            <div><dt>카드번호</dt><dd><?php echo get_text($card['card_mask_number']); ?></dd></div>
            <div><dt>소유주명</dt><dd><?php echo get_text($member['mb_name'] ? $member['mb_name'] : $member['mb_nick']); ?></dd></div>
            <div><dt>등록일시</dt><dd><?php echo get_text(substr($card['ci_time'], 0, 16)); ?></dd></div>
            <div><dt>결제동의</dt><dd>완료</dd></div>
        </dl>
        <?php } else { ?>
        <strong>등록된 결제수단이 없습니다.</strong>
        <p>구독을 진행하려면 결제수단을 먼저 등록해주세요.</p>
        <a href="<?php echo $payment_href; ?>">결제수단 등록/변경</a>
        <?php } ?>
    </section>

    <?php if ($card && !$is_subscribed) { ?>
    <form class="willow_subscribe_bottom" method="post" action="<?php echo G5_URL; ?>/willow/subscribe_update.php">
        <input type="hidden" name="author" value="<?php echo get_text($author_id); ?>">
        <button type="submit">구독 등록하기</button>
    </form>
    <?php } else if ($is_subscribed) { ?>
    <nav class="willow_subscribe_bottom">
        <a href="<?php echo G5_URL; ?>/willow/subscribe.php?mode=my">이미 구독 중입니다</a>
    </nav>
    <?php } else { ?>
    <nav class="willow_subscribe_bottom">
        <a href="<?php echo $payment_href; ?>">결제수단 등록/변경</a>
    </nav>
    <?php } ?>
    <?php } else { ?>
    <section class="willow_subscribe_author">
        <img class="willow_subscribe_author_avatar" src="<?php echo $author['avatar']; ?>" alt="<?php echo $author['name']; ?> 프로필">
        <h2><?php echo !empty($author['role_name_html']) ? $author['role_name_html'] : $author['name'].' 작가'; ?></h2>
        <p><?php echo nl2br($author['profile']); ?></p>
        <div class="willow_subscribe_author_stats">
            <span class="willow_subscribe_stack" aria-hidden="true">
                <img src="<?php echo G5_IMG_URL; ?>/no_profile.gif" alt="">
                <img src="<?php echo G5_IMG_URL; ?>/no_profile.gif" alt="">
                <img src="<?php echo G5_IMG_URL; ?>/no_profile.gif" alt="">
            </span>
            <strong><?php echo number_format($author['subscriber_count']); ?>명이 현재 구독중입니다.</strong>
            <strong>현재 등록 글 : <?php echo number_format($author['post_count']); ?>개</strong>
        </div>
        <div class="willow_subscribe_price_card">
            <strong>월 구독료 : <?php echo number_format($author['price']); ?>원</strong>
            <p>구독 시 <?php echo $author['name']; ?> 작가님이 작성하는 새로운 소식들을<br>가장 빠르게 만나보실 수 있습니다.</p>
        </div>
    </section>

    <nav class="willow_subscribe_bottom">
        <?php if ($is_member) { ?>
        <a href="<?php echo G5_URL; ?>/willow/subscribe.php?author=<?php echo urlencode($author_id); ?>&amp;step=confirm"><?php echo $is_subscribed ? '구독중' : '구독하기'; ?></a>
        <?php } else { ?>
        <a href="<?php echo G5_BBS_URL; ?>/login.php?url=<?php echo urlencode(G5_URL.'/willow/subscribe.php?author='.$author_id.'&step=confirm'); ?>">구독하기</a>
        <?php } ?>
    </nav>
    <?php } ?>
</main>

<?php
include_once(G5_PATH.'/tail.sub.php');
