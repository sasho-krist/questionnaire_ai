@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.js-password-toggle').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var id = btn.getAttribute('data-password-target');
                        var input = id ? document.getElementById(id) : null;
                        if (!input) {
                            return;
                        }
                        var wasPassword = input.type === 'password';
                        input.type = wasPassword ? 'text' : 'password';
                        var visible = input.type === 'text';
                        var icon = btn.querySelector('i');
                        if (icon) {
                            icon.classList.toggle('bi-eye', !visible);
                            icon.classList.toggle('bi-eye-slash', visible);
                        }
                        btn.setAttribute('aria-label', visible ? 'Скрий паролата' : 'Покажи паролата');
                        btn.setAttribute('title', visible ? 'Скрий паролата' : 'Покажи паролата');
                        btn.setAttribute('aria-pressed', visible ? 'true' : 'false');
                    });
                });
            });
        </script>
    @endpush
@endonce
