(function () {
    document.querySelectorAll('[data-keyword-input]').forEach(function (source) {
        var required = source.required, words = [], composing = false;
        var wrap = document.createElement('div');
        wrap.className = 'willow_keyword_editor';
        var chips = document.createElement('div');
        chips.className = 'willow_keyword_tokens';
        var input = document.createElement('input');
        input.type = 'text';
        input.className = 'willow_keyword_entry';
        input.placeholder = '키워드 입력';
        input.setAttribute('aria-label', '키워드 입력');
        var message = document.createElement('span');
        message.className = 'willow_keyword_feedback';
        message.setAttribute('role', 'status');
        source.after(wrap);
        wrap.append(chips, input, message);
        source.type = 'hidden';
        source.required = false;
        function split(value) { return value.split(/[\s,#]+/).filter(Boolean); }
        function sync() {
            source.value = words.concat(split(input.value)).join(' ');
            input.required = required && words.length === 0;
            source.dispatchEvent(new Event('input', { bubbles: true }));
        }
        function render() {
            chips.replaceChildren();
            words.forEach(function (word, index) {
                var chip = document.createElement('span');
                chip.className = 'willow_keyword_token';
                var label = document.createElement('span');
                label.textContent = '#' + word;
                var remove = document.createElement('button');
                remove.type = 'button';
                remove.setAttribute('aria-label', word + ' 삭제');
                remove.innerHTML = '<i class="fa fa-times" aria-hidden="true"></i>';
                remove.addEventListener('pointerdown', function (event) { event.preventDefault(); });
                remove.addEventListener('click', function (event) {
                    event.preventDefault();
                    words.splice(index, 1);
                    render(); sync(); input.focus();
                });
                chip.append(label, remove);
                chips.append(chip);
            });
        }
        function commit() {
            if (composing) return;
            if (!input.value.trim()) { sync(); return; }
            var next = Array.from(new Set(words.concat(split(input.value))));
            var error = next.length > 5 ? '키워드는 최대 5개까지 입력해주세요.' : next.some(function (word) { return Array.from(word).length > 30; }) ? '키워드는 30자 이내로 입력해주세요.' : '';
            message.textContent = error;
            input.setCustomValidity(error);
            if (error) { sync(); return; }
            words = next;
            input.value = '';
            render(); sync();
        }
        input.addEventListener('compositionstart', function () { composing = true; });
        input.addEventListener('compositionend', function () { composing = false; sync(); });
        input.addEventListener('input', function () {
            input.setCustomValidity(''); message.textContent = '';
            if (!composing && /[\s,]$/.test(input.value)) commit(); else sync();
        });
        input.addEventListener('keydown', function (event) {
            if (composing || event.isComposing || event.keyCode === 229) return;
            if (event.key === 'Enter' || event.key === ',') { event.preventDefault(); commit(); }
        });
        input.addEventListener('blur', commit);
        input.value = source.value;
        commit();
    });
})();
