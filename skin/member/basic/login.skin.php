<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/willow_auth.css?ver='.G5_CSS_VER.'">', 0);

$auth_step = isset($_GET['auth_step']) ? preg_replace('/[^a-z_]/', '', $_GET['auth_step']) : 'bridge';
$allowed_steps = array('bridge', 'phone', 'verify', 'profile');
if (!in_array($auth_step, $allowed_steps, true)) {
    $auth_step = 'bridge';
}

$phone_value = isset($_GET['phone']) ? preg_replace('/[^0-9]/', '', $_GET['phone']) : '';
$masked_phone = $phone_value ? preg_replace('/(\d{3})(\d{4})(\d{4})/', '$1-0000-$3', $phone_value) : '010-0000-9920';
$base_login_url = G5_BBS_URL.'/login.php';
$phone_url = $base_login_url.'?auth_step=phone';
$verify_url = $base_login_url.'?auth_step=verify';
$profile_url = $base_login_url.'?auth_step=profile';
$return_url = isset($login_url) && $login_url ? urldecode($login_url) : G5_URL;
?>

<div id="mb_login" class="willow_auth willow_auth_<?php echo $auth_step; ?>">
    <?php if ($auth_step === 'bridge') { ?>
    <header class="willow_auth_header willow_auth_header_center">
        <h1>로그인</h1>
        <a class="willow_auth_close" href="<?php echo G5_URL; ?>" aria-label="닫기">
            <span></span><span></span>
        </a>
    </header>

    <main class="willow_auth_body">
        <h2>로그인 방식을 선택해 주세요.</h2>

        <div class="willow_login_methods" aria-label="로그인 방식">
            <a href="<?php echo $phone_url; ?>" class="willow_login_method">
                <img class="willow_login_icon" src="<?php echo G5_IMG_URL; ?>/ico_login_phone.png" alt="">
                <span>휴대폰 간편인증</span>
            </a>
            <a href="<?php echo $login_url; ?>" class="willow_login_method">
                <img class="willow_login_icon" src="<?php echo G5_IMG_URL; ?>/ico_login_naver.png" alt="">
                <span>네이버</span>
            </a>
            <a href="<?php echo $login_url; ?>" class="willow_login_method">
                <img class="willow_login_icon" src="<?php echo G5_IMG_URL; ?>/ico_login_google.png" alt="">
                <span>Google</span>
            </a>
            <a href="<?php echo $login_url; ?>" class="willow_login_method">
                <img class="willow_login_icon" src="<?php echo G5_IMG_URL; ?>/ico_login_apple.png" alt="">
                <span>Apple</span>
            </a>
        </div>

        <nav class="willow_auth_links" aria-label="회원 도움 메뉴">
            <a href="<?php echo G5_BBS_URL; ?>/register.php">회원가입</a>
            <a href="<?php echo G5_BBS_URL; ?>/password_lost.php">아이디 찾기</a>
            <a href="<?php echo G5_BBS_URL; ?>/password_lost.php">비밀번호 찾기</a>
        </nav>
    </main>
    <?php } ?>

    <?php if ($auth_step === 'phone') { ?>
    <main class="willow_auth_body willow_auth_simple">
        <strong class="willow_auth_brand">WILLOW</strong>
        <h2>간편인증을 통해<br>이용이 가능합니다.</h2>
        <p>휴대폰 인증을 통한 간편로그인을 사용합니다</p>

        <form class="willow_auth_form" action="<?php echo $verify_url; ?>" method="get">
            <input type="hidden" name="auth_step" value="verify">
            <label for="willow_phone" class="sound_only">휴대폰 번호</label>
            <input id="willow_phone" name="phone" type="tel" inputmode="numeric" placeholder="휴대폰 번호를 - 없이 입력하세요" autocomplete="tel">
            <button type="submit">로그인</button>
        </form>
    </main>
    <?php } ?>

    <?php if ($auth_step === 'verify') { ?>
    <header class="willow_auth_header willow_auth_header_center">
        <a class="willow_auth_back" href="<?php echo $phone_url; ?>" aria-label="뒤로가기"></a>
        <h1>인증번호 입력</h1>
    </header>

    <main class="willow_auth_body willow_auth_verify_body">
        <h2>휴대폰으로 전송된<br>인증번호를 입력하세요</h2>
        <a class="willow_auth_resend" href="<?php echo $phone_url; ?>">인증번호 재발송 요청</a>

        <div class="willow_auth_illust" aria-hidden="true">
            <svg viewBox="0 0 80 80">
                <rect x="24" y="15" width="27" height="45" rx="6"></rect>
                <rect x="38" y="27" width="26" height="26" rx="6"></rect>
                <path d="M43 40h.01M48 40h.01M53 40h.01M58 40h.01"></path>
            </svg>
        </div>

        <p class="willow_auth_phone">휴대폰번호 : <?php echo $masked_phone; ?></p>

        <form class="willow_auth_form willow_auth_code_form" action="<?php echo $profile_url; ?>" method="get">
            <input type="hidden" name="auth_step" value="profile">
            <input type="hidden" name="phone" value="<?php echo $phone_value; ?>">
            <label for="willow_code" class="sound_only">인증번호</label>
            <div class="willow_auth_code_wrap">
                <input id="willow_code" name="code" type="text" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" placeholder="인증번호를 입력하세요" autocomplete="one-time-code" required>
                <span id="willow_code_timer">04:00</span>
            </div>
            <button type="submit">인증번호 확인</button>
        </form>

        <p class="willow_auth_notice">* 휴대폰번호를 잘못입력하신 경우 뒤로가기를 통해 휴대폰번호를 다시 입력해주세요</p>
    </main>
    <script>
    (function() {
        var input = document.getElementById('willow_code');
        var timer = document.getElementById('willow_code_timer');
        var form = document.querySelector('.willow_auth_code_form');
        var submit = form ? form.querySelector('button[type="submit"]') : null;
        var remaining = 240;

        if (!input || !timer || !form || !submit) return;

        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        });

        form.addEventListener('submit', function(event) {
            if (remaining <= 0 || input.value.length !== 6) {
                event.preventDefault();
                alert(remaining <= 0 ? '인증 시간이 만료되었습니다. 인증번호를 다시 요청해 주세요.' : '인증번호 6자리를 입력해 주세요.');
            }
        });

        var tick = function() {
            var minutes = Math.floor(remaining / 60);
            var seconds = remaining % 60;
            timer.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');

            if (remaining <= 0) {
                input.disabled = true;
                submit.disabled = true;
                submit.classList.add('is_disabled');
                return;
            }

            remaining -= 1;
            window.setTimeout(tick, 1000);
        };

        tick();
    })();
    </script>
    <?php } ?>

    <?php if ($auth_step === 'profile') { ?>
    <header class="willow_auth_header willow_auth_header_center">
        <a class="willow_auth_back" href="<?php echo $verify_url; ?>" aria-label="뒤로가기"></a>
        <h1>회원가입</h1>
    </header>

    <main class="willow_auth_body willow_auth_profile_body">
        <form class="willow_profile_form" action="<?php echo G5_URL; ?>/willow/auth_login.php" method="post">
            <input type="hidden" name="url" value="<?php echo htmlspecialchars($return_url, ENT_QUOTES); ?>">
            <input type="hidden" name="phone" value="<?php echo $phone_value ? $phone_value : '01000009920'; ?>">
            <section class="willow_auth_section">
                <h2>개인정보 등록</h2>
                <label for="willow_join_phone">휴대폰번호</label>
                <input id="willow_join_phone" type="tel" value="<?php echo $phone_value ? preg_replace('/(\d{3})(\d{4})(\d{4})/', '$1-$2-$3', $phone_value) : ''; ?>" placeholder="010-0000-0000" autocomplete="tel">
                <label for="willow_join_nick">닉네임</label>
                <input id="willow_join_nick" name="nick" type="text" placeholder="닉네임을 입력하세요" autocomplete="nickname" required>
                <p>* 이름 또는 닉네임을 입력하세요</p>
            </section>

            <section class="willow_auth_section willow_agree_section">
                <h2>개인정보수집 이용동의</h2>
                <label class="willow_agree_all">
                    <input type="checkbox">
                    <span></span>
                    <em>약관 전체동의</em>
                </label>
                <label class="willow_agree_item">
                    <input type="checkbox" required>
                    <span></span>
                    <em><b>[필수]</b> 개인 정보 제공 동의</em>
                    <i aria-hidden="true"></i>
                </label>
                <label class="willow_agree_item">
                    <input type="checkbox">
                    <span></span>
                    <em><b>[필수]</b> 선택적 동의 정보 수집·이용 동의</em>
                    <i aria-hidden="true"></i>
                </label>
            </section>

            <button class="willow_join_submit" type="submit">가입하기</button>
        </form>
    </main>
    <?php } ?>
</div>
