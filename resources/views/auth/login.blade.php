@extends('layouts.app')

@section('title', 'Вход')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="page-header">
                <h1 class="h3 mb-1">Вход</h1>
                <p class="text-muted mb-0">Влезте с имейл и парола, за да ползвате приложението.</p>
            </div>

            <div class="card p-4">
                <form method="post" action="{{ route('login') }}" class="vstack gap-3">
                    @csrf

                    <div>
                        <label for="email" class="form-label">Имейл</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}"
                               class="form-control @error('email') is-invalid @enderror" required autofocus autocomplete="username">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="form-label">Парола</label>
                        <input type="password" name="password" id="password"
                               class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label" for="remember">Запомни ме на това устройство</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Вход
                    </button>
                </form>

                <hr class="my-4">

                <p class="text-center text-muted small mb-0">
                    Нямате акаунт?
                    <a href="{{ route('register') }}">Регистрация</a>
                </p>
            </div>
        </div>
    </div>
@endsection
