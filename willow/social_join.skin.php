<?php
if (!defined('_GNUBOARD_')) exit;
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_URL.'/mobile/skin/member/basic/willow_auth.css?v='.filemtime(G5_THEME_PATH.'/mobile/skin/member/basic/willow_auth.css').'">', 0);
$join_session = get_session('ss_willow_social_join');
?>
<div id="mb_login" class="willow_auth willow_auth_profile">
    <header class="willow_auth_header willow_auth_header_center">
        <a class="willow_auth_back" href="<?php echo G5_BBS_URL; ?>/login.php" aria-label="로그인으로 돌아가기"></a>
        <h1>회원가입</h1>
        <a class="willow_auth_close" href="<?php echo G5_URL; ?>/willow/splash.php" aria-label="소개 화면으로"></a>
    </header>
    <main class="willow_auth_body willow_auth_profile_body">
        <form class="willow_profile_form" action="<?php echo G5_SOCIAL_LOGIN_URL; ?>/register_member_update.php" method="post">
            <input type="hidden" name="provider" value="Kakao">
            <input type="hidden" name="willow_join_token" value="<?php echo htmlspecialchars($join_session['token'], ENT_QUOTES, 'UTF-8'); ?>">
            <section class="willow_auth_section">
                <h2>개인정보 등록</h2>
                <label for="willow_join_nick">닉네임</label>
                <input id="willow_join_nick" name="mb_nick" type="text" value="<?php echo htmlspecialchars($user_nick, ENT_QUOTES, 'UTF-8'); ?>" placeholder="닉네임을 입력하세요" autocomplete="nickname" required>
                <label for="willow_join_email">이메일</label>
                <input id="willow_join_email" name="mb_email" type="email" value="<?php echo htmlspecialchars($user_email, ENT_QUOTES, 'UTF-8'); ?>" placeholder="이메일을 입력하세요" autocomplete="email" required>
            </section>
            <section class="willow_auth_section willow_agree_section">
                <h2>약관 동의</h2>
                <label class="willow_agree_all"><input type="checkbox" id="willow_agree_all"><span></span><em>약관 전체동의</em></label>
                <div class="willow_agree_item">
                    <label class="willow_agree_item_label"><input type="checkbox" name="agree" value="1" required><span></span><em><b>[필수]</b> 이용약관 동의</em></label>
                    <a href="<?php echo G5_BBS_URL; ?>/content.php?co_id=provision" target="_blank" rel="noopener" aria-label="이용약관 보기 (새 창)">보기</a>
                </div>
                <div class="willow_agree_item">
                    <label class="willow_agree_item_label"><input type="checkbox" name="agree2" value="1" required><span></span><em><b>[필수]</b> 개인정보 수집·이용 동의</em></label>
                    <a href="<?php echo G5_BBS_URL; ?>/content.php?co_id=privacy" target="_blank" rel="noopener" aria-label="개인정보처리방침 보기 (새 창)">보기</a>
                </div>
            </section>
            <button class="willow_join_submit" type="submit">가입하기</button>
        </form>
    </main>
</div>
<script>
(function () {
    var all = document.getElementById('willow_agree_all');
    var items = Array.from(document.querySelectorAll('.willow_agree_item input'));
    all.addEventListener('change', function () { items.forEach(function (item) { item.checked = all.checked; }); });
    items.forEach(function (item) { item.addEventListener('change', function () { all.checked = items.every(function (entry) { return entry.checked; }); }); });
})();
</script>
