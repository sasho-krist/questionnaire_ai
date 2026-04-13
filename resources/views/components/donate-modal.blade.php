@php
    $tag = (string) config('donate.revolut_tag');
    $tag = ltrim($tag, '@');
    $revolutMe = config('donate.revolut_me_url') ?: 'https://revolut.me/'.$tag;
@endphp

<div class="modal fade" id="donateModal" tabindex="-1" aria-labelledby="donateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h2 class="modal-title h5 fw-bold" id="donateModalLabel">
                    <i class="bi bi-heart-fill text-danger me-2"></i>Подкрепа за проекта
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Затвори"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="text-secondary small mb-4">
                    Ако приложението ви е полезно, можете да подкрепите разработката. Плащанията се обработват от
                    <strong>Revolut</strong> — сайтът не записва данни от карта.
                </p>

                <div class="card border-primary border-opacity-25 mb-3">
                    <div class="card-body">
                        <h3 class="h6 fw-semibold mb-2">
                            <i class="bi bi-credit-card-2-front me-1 text-primary"></i>
                            Карта или банкова сметка (през Revolut)
                        </h3>
                        <p class="small text-secondary mb-3">
                            Натиснете бутона по-долу — ще се отвори официалната страница на Revolut, където можете да платите с дебитна/кредитна карта или друг наличен начин според вашия регион.
                        </p>
                        <a href="{{ $revolutMe }}" class="btn btn-primary w-100" target="_blank" rel="noopener noreferrer">
                            <i class="bi bi-box-arrow-up-right me-2"></i>Отвори плащане в Revolut
                        </a>
                    </div>
                </div>

                <div class="card bg-body-secondary bg-opacity-50 border-0 mb-0">
                    <div class="card-body">
                        <h3 class="h6 fw-semibold mb-2">
                            <i class="bi bi-phone me-1"></i>
                            От приложението Revolut към Revolut
                        </h3>
                        <p class="small text-secondary mb-2">
                            Отворете <strong>Revolut</strong> → <strong>Send</strong> / <strong>Изпрати</strong> → потърсете потребителя по таг или изберете от контакти:
                        </p>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <code class="user-select-all px-2 py-1 rounded bg-body border" id="revolut-tag-display">{{ '@'.$tag }}</code>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="copy-revolut-tag" data-copy="{{ '@'.$tag }}">
                                <i class="bi bi-clipboard me-1"></i>Копирай тага
                            </button>
                        </div>
                        <p class="small text-muted mb-0">
                            Таг без @ за търсене: <code class="user-select-all">{{ $tag }}</code>
                        </p>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Затвори</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (function () {
            var btn = document.getElementById('copy-revolut-tag');
            if (!btn) return;
            btn.addEventListener('click', function () {
                var text = btn.getAttribute('data-copy') || '';
                if (!text) return;
                function ok() {
                    var prev = btn.innerHTML;
                    btn.innerHTML = '<i class="bi bi-check2"></i> Копирано';
                    btn.disabled = true;
                    setTimeout(function () {
                        btn.innerHTML = prev;
                        btn.disabled = false;
                    }, 2000);
                }
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(ok).catch(function () {});
                }
            });
        })();
    </script>
@endpush
