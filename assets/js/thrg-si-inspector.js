/** Site Inspector screen: inspect and clear-history buttons, share box (copy, download, print, email form) and the monthly email form. */
(function () {
    var inspect = document.querySelector('.si-inspect-form');
    if (inspect) {
        inspect.addEventListener('submit', function () {
            var b = inspect.querySelector('button');
            if (b) { b.disabled = true; b.textContent = 'Inspecting…'; }
        });
    }
    var clear = document.querySelector('.si-clear-form');
    if (clear) {
        clear.addEventListener('submit', function (event) {
            if (!window.confirm(clear.getAttribute('data-confirm'))) { event.preventDefault(); }
        });
    }
})();

(function () {
    var box = document.getElementById('si-report-text'), status = document.getElementById('si-share-status');
    if (!box || !status || !document.getElementById('si-copy')) { return; }
    function say(t) { status.textContent = t; setTimeout(function () { status.textContent = ''; }, 3000); }
    function copyFallback() {
        try {
            say(document.execCommand('copy') ? 'Copied to clipboard' : 'Press Ctrl+C or Cmd+C to copy');
        } catch (error) {
            say('Press Ctrl+C or Cmd+C to copy');
        }
    }
    document.getElementById('si-copy').addEventListener('click', function () {
        box.select();
        var done = function () { say('Copied to clipboard'); };
        if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(box.value).then(done, copyFallback); }
        else { copyFallback(); }
    });
    document.getElementById('si-download').addEventListener('click', function () {
        var a = document.createElement('a'), url = URL.createObjectURL(new Blob([box.value], { type: 'text/plain' }));
        a.href = url; a.download = this.getAttribute('data-filename'); document.body.appendChild(a); a.click();
        setTimeout(function () { URL.revokeObjectURL(url); a.remove(); }, 500); say('Download started');
    });
    document.getElementById('si-print').addEventListener('click', function () { window.print(); });
    var toggle = document.getElementById('si-email-toggle'), form = document.getElementById('si-email');
    if (toggle && form) {
        toggle.addEventListener('click', function () {
            form.hidden = !form.hidden;
            toggle.setAttribute('aria-expanded', form.hidden ? 'false' : 'true');
            if (!form.hidden) { form.querySelector('input[name="recipients[]"]').focus(); }
        });
        var all = document.getElementById('si-email-all'), boxes = form.querySelectorAll('input[name="recipients[]"]');
        all.addEventListener('change', function () { boxes.forEach(function (b) { b.checked = all.checked; }); });
        boxes.forEach(function (b) { b.addEventListener('change', function () { var n = form.querySelectorAll('input[name="recipients[]"]:checked').length; all.checked = n === boxes.length; all.indeterminate = n > 0 && n < boxes.length; }); });
        form.addEventListener('submit', function (e) {
            if (!form.querySelector('input[name="recipients[]"]:checked')) { e.preventDefault(); say('Choose at least one administrator'); return; }
            var b = form.querySelector('button[type="submit"]'); b.disabled = true; b.textContent = 'Sending…';
        });
        if (/[?&]thrg_si_done=mail-/.test(location.search)) { form.hidden = false; toggle.setAttribute('aria-expanded', 'true'); }
    }
})();

(function () {
    var form = document.querySelector('.si-schedule-form');
    if (!form) { return; }
    form.addEventListener('change', function (event) {
        if (event.target.name === 'users[]') {
            form.querySelector('[name="who"][value="chosen"]').checked = true;
        } else if (event.target.name === 'who' && event.target.value === 'all') {
            form.querySelectorAll('[name="users[]"]').forEach(function (box) {
                box.checked = false;
            });
        }
    });
}());
