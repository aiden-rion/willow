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
$willow_splash_images = array(1, 2, 3, 4, 5, 6, 8);
$willow_splash_count = count($willow_splash_images);

include_once(G5_PATH.'/head.sub.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/willow_splash.css?ver='.filemtime(G5_THEME_PATH.'/css/willow_splash.css').'">', 10);
?>

<main class="willow_splash" data-login-url="<?php echo $login_url; ?>">
    <div class="willow_splash_track">
        <?php foreach ($willow_splash_images as $index => $image_id) { ?>
        <?php $image = sprintf('splash_%03d.png', $image_id); ?>
        <section class="willow_splash_slide" aria-label="WILLOW 소개 <?php echo $index + 1; ?>">
            <img src="<?php echo G5_IMG_URL; ?>/<?php echo $image; ?>" alt="" draggable="false">
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
    var pointerId = null;

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

    function beginDrag(x) {
        dragging = true;
        startX = x;
        currentX = x;
        track.classList.add('is_dragging');
    }

    function moveDrag(x) {
        if (!dragging) return;
        currentX = x;
        var delta = currentX - startX;
        if ((current === 0 && delta > 0) || (current === slides.length - 1 && delta < 0)) delta *= 0.25;
        track.style.transform = 'translate3d(' + (-current * splash.clientWidth + delta) + 'px, 0, 0)';
    }

    function endDrag() {
        if (!dragging) return;
        var deltaX = currentX - startX;
        dragging = false;
        pointerId = null;
        track.classList.remove('is_dragging');
        if (Math.abs(deltaX) > 44) {
            if (deltaX < 0 && current === slides.length - 1) {
                finishSplash();
                return;
            }
            updateSlide(current + (deltaX < 0 ? 1 : -1));
        }
        else updateSlide(current);
    }

    splash.addEventListener('touchstart', function(event) {
        if (window.PointerEvent) return;
        if (!event.touches || !event.touches[0]) return;
        beginDrag(event.touches[0].clientX);
    }, { passive: true });

    splash.addEventListener('touchmove', function(event) {
        if (window.PointerEvent) return;
        if (!dragging || !event.touches || !event.touches[0]) return;
        moveDrag(event.touches[0].clientX);
    }, { passive: true });

    splash.addEventListener('touchend', function() {
        if (window.PointerEvent) return;
        endDrag();
    }, { passive: true });

    splash.addEventListener('pointerdown', function(event) {
        if (event.pointerType === 'mouse' && event.button !== 0) return;
        if (event.target.closest('button, a, input, textarea, select')) return;
        if (event.pointerType === 'mouse') event.preventDefault();
        pointerId = event.pointerId;
        if (splash.setPointerCapture) {
            try {
                splash.setPointerCapture(pointerId);
            } catch (e) {}
        }
        beginDrag(event.clientX);
    });

    splash.addEventListener('pointermove', function(event) {
        if (pointerId !== event.pointerId) return;
        moveDrag(event.clientX);
    });

    splash.addEventListener('pointerup', function(event) {
        if (pointerId !== event.pointerId) return;
        endDrag();
    });

    splash.addEventListener('pointercancel', function(event) {
        if (pointerId !== event.pointerId) return;
        dragging = false;
        pointerId = null;
        track.classList.remove('is_dragging');
        updateSlide(current);
    });

    splash.addEventListener('dragstart', function(event) { event.preventDefault(); });

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
