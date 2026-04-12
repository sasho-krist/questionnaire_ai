<div id="cookie-consent-banner" class="position-fixed bottom-0 start-0 end-0 d-none p-3 shadow-lg border-top bg-dark text-white" style="z-index: 1080;" role="dialog" aria-label="Бисквитки">
    <div class="container d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <p class="small mb-0">
            Този сайт използва необходими бисквитки за вход и сесия. Продължавайки, приемате използването им.
            Повече в <a href="{{ route('privacy') }}" class="text-white fw-medium">политиката за поверителност</a>.
        </p>
        <button type="button" class="btn btn-light btn-sm flex-shrink-0" id="cookie-consent-accept">
            Разбирам
        </button>
    </div>
</div>
<script>
    (function () {
        var key = 'cookie_consent_v1';
        try {
            if (localStorage.getItem(key) === '1') return;
        } catch (e) {}
        var el = document.getElementById('cookie-consent-banner');
        if (!el) return;
        el.classList.remove('d-none');
        document.getElementById('cookie-consent-accept').addEventListener('click', function () {
            try { localStorage.setItem(key, '1'); } catch (e) {}
            el.classList.add('d-none');
        });
    })();
</script>
