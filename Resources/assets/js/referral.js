document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-copy]');
        if (!trigger) return;

        var text = trigger.dataset.copy;
        if (!text) return;

        navigator.clipboard.writeText(text).then(function () {
            var label = trigger.querySelector('span');
            if (label) {
                var original = label.textContent;
                label.textContent = window.translations?.copied || 'Copied!';
                trigger.classList.add('btn-success');

                setTimeout(function () {
                    label.textContent = original;
                    trigger.classList.remove('btn-success');
                }, 1800);
            }
        }).catch(function () {
            var input = document.getElementById('profileReferralLink') || document.getElementById('referralLink');
            if (input) {
                input.select();
                document.execCommand('copy');
            }
        });
    });

    var linkInput = document.getElementById('profileReferralLink') || document.getElementById('referralLink');
    if (linkInput) {
        linkInput.addEventListener('click', function () {
            this.select();
        });
    }
});
