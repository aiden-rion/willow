<?php
include_once('./_common.php');

$return_url = isset($_GET['url']) ? trim($_GET['url']) : G5_URL;
if ($return_url === '' || preg_match('#^(https?:)?//#i', $return_url)) {
    $return_url = G5_URL;
}
if (strpos($return_url, '/bbs/write.php') !== false && strpos($return_url, 'bo_table=free') !== false) {
    $return_url = '/willow/write.php';
}

if ($is_member) {
    goto_url($return_url);
}

$login_url = G5_BBS_URL.'/login.php?url='.urlencode($return_url);
$g5['title'] = 'WILLOW';
$willow_splash_count = 8;

include_once(G5_PATH.'/head.sub.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/willow_splash.css?ver='.G5_CSS_VER.'">', 10);
?>

<?php if (!G5_IS_MOBILE) { ?>
<aside class="willow_splash_desktop_brand" aria-hidden="true">
    <a href="<?php echo G5_URL; ?>" tabindex="-1">
        <img src="<?php echo G5_IMG_URL; ?>/logo.png" alt="">
    </a>
    <strong>이야기를 잇다,<br>willow</strong>
    <p>Where voices take root,<br>and stories become strength.</p>
</aside>
<aside class="willow_splash_desktop_copy" aria-hidden="true">
    <span>WILLOW APP</span>
    <strong>모바일 화면 그대로<br>윌로우를 만나보세요</strong>
    <p>오늘의 주제부터 작가 구독까지, 한 사람의 이야기가 오래 머무는 공간입니다.</p>
    <div class="willow_splash_desktop_preview">
        <img src="<?php echo G5_IMG_URL; ?>/splash_005.png" alt="">
        <img src="<?php echo G5_IMG_URL; ?>/splash_001.png" alt="">
        <img src="<?php echo G5_IMG_URL; ?>/splash_008.png" alt="">
    </div>
</aside>
<?php } ?>

<main class="willow_splash" data-login-url="<?php echo $login_url; ?>">
    <div class="willow_splash_track">
        <?php for ($i = 1; $i <= $willow_splash_count; $i++) { ?>
        <?php $image = sprintf('splash_%03d.png', $i); ?>
        <section class="willow_splash_slide" aria-label="WILLOW 소개 <?php echo $i; ?>">
            <img src="<?php echo G5_IMG_URL; ?>/<?php echo $image; ?>" alt="">
        </section>
        <?php } ?>
    </div>

    <div class="willow_splash_controls" aria-label="스플래시 이동">
        <div class="willow_splash_progress" style="--willow-splash-count: <?php echo (int) $willow_splash_count; ?>;" aria-hidden="true">
            <?php for ($i = 1; $i <= $willow_splash_count; $i++) { ?>
            <span class="<?php echo $i === 1 ? 'is_active' : ''; ?>"></span>
            <?php } ?>
        </div>
        <button type="button" class="willow_splash_skip">건너뛰기</button>
    </div>
</main>

<script>
(function() {
    var splash = document.querySelector('.willow_splash');
    if (!splash) return;
    var track = splash.querySelector('.willow_splash_track');
    var slides = Array.prototype.slice.call(splash.querySelectorAll('.willow_splash_slide'));
    var dots = Array.prototype.slice.call(splash.querySelectorAll('.willow_splash_progress span'));
    var skip = splash.querySelector('.willow_splash_skip');
    var loginUrl = splash.getAttribute('data-login-url') || '<?php echo G5_BBS_URL; ?>/login.php';
    var current = 0;
    var startX = 0;
    var currentX = 0;
    var dragging = false;

    function setSplashSeen() {
        document.cookie = '<?php echo md5('willow_splash_seen'); ?>=' + btoa('1') + '; path=/; max-age=' + (86400 * 30);
        try {
            window.localStorage.setItem('willow_splash_seen', '1');
        } catch (e) {}
    }

    function finishSplash() {
        setSplashSeen();
        window.location.href = loginUrl;
    }

    function updateSlide(index) {
        current = Math.max(0, Math.min(slides.length - 1, index));
        track.style.transform = 'translate3d(' + (-current * 100) + '%, 0, 0)';
        dots.forEach(function(dot, dotIndex) {
            dot.classList.toggle('is_active', dotIndex === current);
        });
        if (skip) {
            skip.textContent = current === slides.length - 1 ? '시작하기' : '건너뛰기';
        }
    }

    if (skip) {
        skip.addEventListener('click', finishSplash);
    }

    splash.addEventListener('touchstart', function(event) {
        if (!event.touches || !event.touches[0]) return;
        dragging = true;
        startX = event.touches[0].clientX;
        currentX = startX;
        track.classList.add('is_dragging');
    }, { passive: true });

    splash.addEventListener('touchmove', function(event) {
        if (!dragging || !event.touches || !event.touches[0]) return;
        currentX = event.touches[0].clientX;
    }, { passive: true });

    splash.addEventListener('touchend', function() {
        if (!dragging) return;
        var deltaX = currentX - startX;
        dragging = false;
        track.classList.remove('is_dragging');
        if (Math.abs(deltaX) > 44) {
            if (deltaX < 0 && current === slides.length - 1) {
                finishSplash();
                return;
            }
            updateSlide(current + (deltaX < 0 ? 1 : -1));
        }
    }, { passive: true });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'ArrowRight') {
            if (current === slides.length - 1) finishSplash();
            else updateSlide(current + 1);
        }
        if (event.key === 'ArrowLeft') {
            updateSlide(current - 1);
        }
        if (event.key === 'Escape') {
            finishSplash();
        }
    });

    updateSlide(0);
})();
</script>

<?php
include_once(G5_PATH.'/tail.sub.php');
