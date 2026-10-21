(function () {
    'use strict';
    if (window.willowShareReady) return;
    window.willowShareReady = true;
    var timer;
    function copied() {
        var notice = document.querySelector('.willow_share_status');
        if (!notice) {
            notice = document.createElement('div');
            notice.className = 'willow_share_status';
            notice.setAttribute('role', 'status');
            document.body.appendChild(notice);
        }
        notice.hidden = false;
        notice.textContent = '글 링크가 복사되었습니다.';
        clearTimeout(timer);
        timer = setTimeout(function () { notice.hidden = true; }, 2500);
    }
    function fallback(url) {
        var focused = document.activeElement;
        var input = document.createElement('textarea');
        input.value = url;
        input.readOnly = true;
        input.style.cssText = 'position:fixed;top:0;left:-9999px;font-size:16px';
        document.body.appendChild(input);
        input.select();
        input.setSelectionRange(0, url.length);
        var ok = false;
        try { ok = document.execCommand('copy'); } catch (e) { /* Manual copy below. */ }
        input.remove();
        if (focused) focused.focus({preventScroll: true});
        if (ok) copied();
        else window.prompt('아래 글 링크를 복사해주세요.', url);
    }
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-willow-share]');
        if (!button) return;
        event.preventDefault();
        event.stopPropagation();
        var url = button.getAttribute('data-willow-share');
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(copied).catch(function () { fallback(url); });
        } else fallback(url);
    });
})();
