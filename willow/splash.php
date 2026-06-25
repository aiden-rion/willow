<?php
include_once('./_common.php');

$return_url = isset($_GET['url']) ? trim($_GET['url']) : G5_URL;
if ($return_url === '' || preg_match('#^(https?:)?//#i', $return_url)) {
    $return_url = G5_URL;
}

if ($is_member) {
    goto_url($return_url);
}

$login_url = G5_BBS_URL.'/login.php?url='.urlencode($return_url);
$g5['title'] = 'WILLOW';

include_once(G5_PATH.'/head.sub.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/willow_splash.css?ver='.G5_CSS_VER.'">', 10);
?>

<main class="willow_splash" data-login-url="<?php echo $login_url; ?>">
    <div class="willow_splash_track">
        <?php for ($i = 1; $i <= 3; $i++) { ?>
        <?php $image = sprintf('splash_%03d.png', $i); ?>
        <section class="willow_splash_slide" aria-label="WILLOW 소개 <?php echo $i; ?>">
            <img src="<?php echo G5_IMG_URL; ?>/<?php echo $image; ?>" alt="">
        </section>
        <?php } ?>
    </div>

    <div class="willow_splash_controls" aria-label="스플래시 이동">
        <div class="willow_splash_progress" aria-hidden="true">
            <span class="is_active"></span>
            <span></span>
            <span></span>
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
