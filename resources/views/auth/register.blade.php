@extends('layouts.app')

@section('title', 'Регистрация')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="page-header">
                <h1 class="h3 mb-1">Регистрация</h1>
                <p class="text-muted mb-0">Създайте акаунт, за да създавате и управлявате анкети.</p>
            </div>

            <div class="card p-4">
                <form method="post" action="{{ route('register') }}" class="vstack gap-3">
                    @csrf

                    <div>
                        <label for="name" class="form-label">Име</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}"
                               class="form-control @error('name') is-invalid @enderror" required autofocus autocomplete="name">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="form-label">Имейл</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}"
                               class="form-control @error('email') is-invalid @enderror" required autocomplete="username">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="form-label">Парола</label>
                        <div class="position-relative">
                            <input type="password" name="password" id="password"
                                   class="form-control pe-5 @error('password') is-invalid @enderror" required autocomplete="new-password">
                            <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y py-1 px-2 me-1 text-secondary border-0 shadow-none js-password-toggle z-1"
                                    data-password-target="password"
                                    aria-label="Покажи паролата"
                                    aria-pressed="false"
                                    title="Покажи паролата">
                                <i class="bi bi-eye fs-5" aria-hidden="true"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="form-label">Потвърди парола</label>
                        <div class="position-relative">
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                   class="form-control pe-5" required autocomplete="new-password">
                            <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y py-1 px-2 me-1 text-secondary border-0 shadow-none js-password-toggle z-1"
                                    data-password-target="password_confirmation"
                                    aria-label="Покажи паролата"
                                    aria-pressed="false"
                                    title="Покажи паролата">
                                <i class="bi bi-eye fs-5" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-person-plus me-1"></i> Регистрация
                    </button>
                </form>

                <hr class="my-4">

                <p class="text-center text-muted small mb-0">
                    Вече имате акаунт?
                    <a href="{{ route('login') }}">Вход</a>
                </p>
            </div>
        </div>
    </div>

    @include('auth.partials.password-reveal-script')
@endsection
