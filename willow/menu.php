<?php
include_once('./_common.php');
include_once('./content.lib.php');

$g5['title'] = '전체메뉴';
include_once(G5_PATH.'/head.sub.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/willow_mobile.css?ver='.G5_CSS_VER.'">', 10);

$is_logged_in = !empty($member['mb_id']);
$is_author = $is_logged_in && (((int) $member['mb_level'] >= 3) || (!empty($member['mb_2']) && $member['mb_2'] === 'author'));
$member_name = $is_logged_in ? ($member['mb_nick'] ? $member['mb_nick'] : $member['mb_name']) : '방문자';
$member_level = $is_author ? '작가회원' : '일반회원';
$member_suffix = $is_author ? ' 작가님,' : ' 회원님,';
$point = $is_logged_in ? number_format((int) $member['mb_point']) : '0';
$profile_href = $is_logged_in ? G5_BBS_URL.'/member_confirm.php?url=register_form.php' : G5_BBS_URL.'/login.php';
$logout_href = $is_logged_in ? G5_BBS_URL.'/logout.php' : G5_BBS_URL.'/login.php';
$menu_img_url = G5_IMG_URL;
$member_avatar = $is_logged_in ? willow_member_avatar($member) : $menu_img_url.'/profile.png';
?>

<header id="hd" class="willow_shell_header">
    <?php
    $willow_topbar_open = true;
    include G5_THEME_PATH.'/willow_topbar.php';
    ?>
</header>

<main class="willow_menu_page">
    <section class="willow_menu_member_card">
        <div class="willow_menu_member_top">
            <span><?php echo $member_level; ?></span>
            <a href="<?php echo $profile_href; ?>">회원정보수정 <i class="fa fa-angle-right" aria-hidden="true"></i></a>
        </div>
        <div class="willow_menu_profile">
            <img src="<?php echo $member_avatar; ?>" alt="">
            <p><strong><?php echo get_text($member_name); ?></strong><?php echo $is_logged_in ? $member_suffix : '님,'; ?><br>반갑습니다!</p>
        </div>
        <a class="willow_menu_point" href="<?php echo G5_BBS_URL; ?>/point.php">
            <strong>보유포인트</strong>
            <span><?php echo $point; ?> <i class="fa fa-angle-right" aria-hidden="true"></i></span>
        </a>
    </section>

    <nav class="willow_menu_nav" aria-label="전체메뉴">
        <a href="<?php echo G5_URL; ?>">
            <img src="<?php echo $menu_img_url; ?>/ico_menu_home.png" alt="">
            <span>메인으로</span>
        </a>
        <a href="<?php echo $is_logged_in ? G5_URL.'/willow/drafts.php' : G5_BBS_URL.'/login.php'; ?>">
            <img src="<?php echo $menu_img_url; ?>/ico_menu_document.png" alt="">
            <span>임시저장 글</span>
        </a>
        <a href="<?php echo $is_logged_in ? G5_URL.'/willow/my_posts.php' : G5_BBS_URL.'/login.php'; ?>">
            <img src="<?php echo $menu_img_url; ?>/ico_menu_comment.png" alt="">
            <span>내가 쓴 글</span>
        </a>
        <a href="<?php echo $is_logged_in ? G5_URL.'/willow/subscribe.php?mode=my' : G5_BBS_URL.'/login.php'; ?>">
            <img src="<?php echo $menu_img_url; ?>/ico_menu_bell.png" alt="">
            <span>나의 구독</span>
        </a>
        <a href="<?php echo $is_logged_in ? G5_URL.'/willow/payment.php' : G5_BBS_URL.'/login.php'; ?>">
            <img src="<?php echo $menu_img_url; ?>/ico_menu_bookmark.png" alt="">
            <span>결제수단 등록/변경</span>
        </a>
        <?php if ($is_author) { ?>
        <a href="<?php echo G5_URL; ?>/willow/point_settlement.php">
            <img src="<?php echo $menu_img_url; ?>/ico_menu_document.png" alt="">
            <span>수익 정산요청</span>
        </a>
        <?php } ?>
        <a href="<?php echo G5_BBS_URL; ?>/memo.php">
            <img src="<?php echo $menu_img_url; ?>/ico_menu_alert.png" alt="">
            <span>알림설정</span>
        </a>
    </nav>

    <section class="willow_menu_support">
        <h2>고객센터</h2>
        <div>
            <a href="<?php echo get_pretty_url('notice'); ?>">공지사항</a>
            <a href="<?php echo G5_BBS_URL; ?>/faq.php">FAQ</a>
            <a href="<?php echo $logout_href; ?>"><?php echo $is_logged_in ? '로그아웃' : '로그인'; ?></a>
        </div>
    </section>

    <?php willow_render_banner_area('menu_bottom', 'willow_menu_bottom_banner', '메뉴페이지 하단 배너'); ?>
</main>

<?php
include_once(G5_PATH.'/tail.sub.php');
