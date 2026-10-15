(function () {
    document.querySelectorAll('[data-keyword-input]').forEach(function (input) {
        var composing = false;
        function format() {
            if (composing) return;
            var value = input.value;
            var caret = input.selectionStart;
            function hashtags(text) {
                return text.split(/[\s,#]+/).filter(Boolean).map(function (word) {
                    return '#' + word;
                }).join(' ');
            }
            var trailing = /[\s,]$/.test(value) ? ' ' : '';
            var next = hashtags(value) + trailing;
            if (next !== value) {
                var position = hashtags(value.slice(0, caret)).length;
                if (/[\s,]$/.test(value.slice(0, caret))) position++;
                input.value = next;
                input.setSelectionRange(position, position);
            }
        }
        input.addEventListener('compositionstart', function () { composing = true; });
        input.addEventListener('compositionend', function () {
            composing = false;
            format();
            input.dispatchEvent(new Event('input', { bubbles: true }));
        });
        input.addEventListener('input', format);
        input.addEventListener('blur', format);
        format();
    });
})();
