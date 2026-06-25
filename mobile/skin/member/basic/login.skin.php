<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/willow_auth.css?ver='.G5_CSS_VER.'">', 0);

$return_target = isset($url) ? urldecode($url) : '';
$is_admin_login = $return_target && strpos($return_target, '/adm/') !== false;
if ($is_admin_login) {
?>
<div id="mb_login" class="willow_auth willow_admin_login">
    <header class="willow_auth_header willow_auth_header_center">
        <h1>관리자 로그인</h1>
    </header>

    <main class="willow_auth_body">
        <form name="flogin" class="willow_auth_form" action="<?php echo $login_action_url; ?>" method="post" onsubmit="return flogin_submit(this);">
            <input type="hidden" name="url" value="<?php echo $login_url; ?>">
            <label for="login_id" class="sound_only">아이디</label>
            <input type="text" name="mb_id" id="login_id" placeholder="관리자 아이디" required autocomplete="username">
            <label for="login_pw" class="sound_only">비밀번호</label>
            <input type="password" name="mb_password" id="login_pw" placeholder="비밀번호" required autocomplete="current-password">
            <button type="submit">로그인</button>
        </form>
    </main>
</div>
<script>
function flogin_submit(f) {
    if (typeof jQuery !== 'undefined' && jQuery(document.body).triggerHandler('login_sumit', [f, 'flogin']) === false) {
        return false;
    }
    return true;
}
</script>
<?php
    return;
}

$auth_step = isset($_GET['auth_step']) ? preg_replace('/[^a-z_]/', '', $_GET['auth_step']) : 'bridge';
$allowed_steps = array('bridge', 'phone', 'verify', 'profile');
if (!in_array($auth_step, $allowed_steps, true)) {
    $auth_step = 'bridge';
}

$base_login_url = G5_BBS_URL.'/login.php';
$phone_value = isset($_GET['phone']) ? preg_replace('/[^0-9]/', '', $_GET['phone']) : '';
$display_phone = $phone_value;
if (preg_match('/^(\d{3})(\d{3,4})(\d{4})$/', $phone_value, $phone_matches)) {
    $display_phone = $phone_matches[1].'-'.$phone_matches[2].'-'.$phone_matches[3];
}
$phone_query = $phone_value ? '&phone='.$phone_value : '';
$phone_url = $base_login_url.'?auth_step=phone';
$verify_url = $base_login_url.'?auth_step=verify'.$phone_query;
$profile_url = $base_login_url.'?auth_step=profile'.$phone_query;
$return_url = isset($login_url) && $login_url ? urldecode($login_url) : G5_URL;

if ($auth_step === 'profile' && $phone_value) {
    $existing_mb_id = 'willow_'.substr(md5($phone_value), 0, 12);
    $existing_member = get_member($existing_mb_id);

    if (empty($existing_member['mb_id'])) {
        $escaped_phone = sql_escape_string($phone_value);
        $existing_member = sql_fetch(" select * from {$g5['member_table']} where replace(replace(replace(mb_hp, '-', ''), ' ', ''), '.', '') = '{$escaped_phone}' and mb_leave_date = '' order by mb_datetime asc limit 1 ");
    }

    if (!empty($existing_member['mb_id'])) {
        if ($existing_member['mb_intercept_date'] && $existing_member['mb_intercept_date'] <= date('Ymd', G5_SERVER_TIME)) {
            alert('회원님의 아이디는 접근이 금지되어 있습니다.', G5_BBS_URL.'/login.php');
        }

        $now = G5_TIME_YMDHIS;
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        sql_query(" update {$g5['member_table']} set mb_today_login = '{$now}', mb_login_ip = '".sql_escape_string($ip)."' where mb_id = '".sql_escape_string($existing_member['mb_id'])."' ");

        session_regenerate_id(false);
        set_session('ss_mb_id', $existing_member['mb_id']);
        generate_mb_key($existing_member);
        if (function_exists('update_auth_session_token')) {
            update_auth_session_token($existing_member['mb_datetime']);
        }

        if (!$return_url || strpos($return_url, 'login.php') !== false) {
            $return_url = G5_URL;
        }

        goto_url($return_url);
    }
}
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
        <strong class="willow_auth_brand"><img src="<?php echo G5_IMG_URL; ?>/logo.png" alt="WILLOW"></strong>
        <h2>간편인증을 통해<br>이용이 가능합니다.</h2>
        <p>휴대폰 인증을 통한 간편로그인을 사용합니다</p>

        <form class="willow_auth_form" action="<?php echo $base_login_url; ?>" method="get">
            <input type="hidden" name="auth_step" value="verify">
            <label for="willow_phone" class="sound_only">휴대폰 번호</label>
            <input id="willow_phone" name="phone" type="tel" inputmode="numeric" placeholder="휴대폰 번호를 - 없이 입력하세요" autocomplete="tel" value="<?php echo $phone_value; ?>">
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

        <p class="willow_auth_phone">휴대폰번호 : <?php echo $display_phone; ?></p>

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
                <div class="willow_agree_item">
                    <label class="willow_agree_item_label">
                        <input type="checkbox" required>
                        <span></span>
                        <em><b>[필수]</b> 개인 정보 제공 동의</em>
                    </label>
                    <button type="button" class="willow_terms_open" data-terms="privacy" aria-label="개인 정보 제공 동의 약관 보기">
                        <i aria-hidden="true"></i>
                    </button>
                </div>
                <div class="willow_agree_item">
                    <label class="willow_agree_item_label">
                        <input type="checkbox">
                        <span></span>
                        <em><b>[필수]</b> 선택적 동의 정보 수집·이용 동의</em>
                    </label>
                    <button type="button" class="willow_terms_open" data-terms="optional" aria-label="선택적 동의 정보 수집·이용 동의 약관 보기">
                        <i aria-hidden="true"></i>
                    </button>
                </div>
            </section>

            <button class="willow_join_submit" type="submit">가입하기</button>
        </form>
    </main>
    <div class="willow_terms_modal" role="dialog" aria-modal="true" aria-labelledby="willow_terms_title" hidden>
        <button type="button" class="willow_terms_backdrop" data-terms-close aria-label="약관 닫기"></button>
        <section class="willow_terms_panel">
            <header>
                <h2 id="willow_terms_title">약관</h2>
                <button type="button" class="willow_terms_close" data-terms-close aria-label="닫기"></button>
            </header>
            <div class="willow_terms_content" id="willow_terms_content"></div>
        </section>
    </div>
    <script>
    (function() {
        var section = document.querySelector('.willow_agree_section');
        if (!section) return;

        var allCheckbox = section.querySelector('.willow_agree_all input[type="checkbox"]');
        var itemCheckboxes = Array.prototype.slice.call(section.querySelectorAll('.willow_agree_item input[type="checkbox"]'));
        if (!allCheckbox || !itemCheckboxes.length) return;

        allCheckbox.addEventListener('change', function() {
            itemCheckboxes.forEach(function(checkbox) {
                checkbox.checked = allCheckbox.checked;
            });
        });

        itemCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                allCheckbox.checked = itemCheckboxes.every(function(item) {
                    return item.checked;
                });
            });
        });

        var terms = {
            privacy: {
                title: '개인 정보 제공 동의',
                body: '<p>서비스 이용 및 본인 확인을 위해 휴대폰번호, 닉네임 등 회원가입에 필요한 정보를 수집합니다.</p><p>수집된 정보는 회원 식별, 서비스 제공, 고객 응대 목적으로 이용되며 관련 법령에 따른 보관 기간 이후 파기됩니다.</p>'
            },
            optional: {
                title: '선택적 동의 정보 수집·이용 동의',
                body: '<p>맞춤형 서비스와 알림 제공을 위해 선택 입력 정보 및 서비스 이용 기록을 활용할 수 있습니다.</p><p>선택 동의를 거부해도 기본 서비스 이용은 가능하나 일부 맞춤 기능 제공이 제한될 수 있습니다.</p>'
            }
        };
        var modal = document.querySelector('.willow_terms_modal');
        var title = document.getElementById('willow_terms_title');
        var content = document.getElementById('willow_terms_content');

        section.querySelectorAll('.willow_terms_open').forEach(function(button) {
            button.addEventListener('click', function() {
                var term = terms[button.getAttribute('data-terms')];
                if (!modal || !title || !content || !term) return;

                title.textContent = term.title;
                content.innerHTML = term.body;
                modal.hidden = false;
                document.body.classList.add('willow_terms_opened');
            });
        });

        document.querySelectorAll('[data-terms-close]').forEach(function(button) {
            button.addEventListener('click', function() {
                if (!modal) return;

                modal.hidden = true;
                document.body.classList.remove('willow_terms_opened');
            });
        });
    })();
    </script>
    <?php } ?>
</div>
