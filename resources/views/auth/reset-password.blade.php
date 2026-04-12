@extends('layouts.app')

@section('title', 'Нова парола')

@section('meta_description', 'Задайте нова парола за вашия акаунт в Questionnaire AI.')

@section('robots', 'noindex, follow')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="page-header">
                <h1 class="h3 mb-1">Нова парола</h1>
                <p class="text-muted mb-0">Изберете нова парола за акаунта си.</p>
            </div>

            <div class="card p-4">
                <form method="post" action="{{ route('password.update') }}" class="vstack gap-3">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div>
                        <label for="email" class="form-label">Имейл</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $email) }}"
                               class="form-control @error('email') is-invalid @enderror" required autocomplete="username">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="form-label">Нова парола</label>
                        <div class="position-relative">
                            <input type="password" name="password" id="password"
                                   class="form-control pe-5 @error('password') is-invalid @enderror" required autocomplete="new-password">
                            <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y py-1 px-2 me-1 text-secondary border-0 shadow-none js-password-toggle z-1"
                                    data-password-target="password" type="button" aria-label="Покажи паролата" title="Покажи паролата">
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
                                    data-password-target="password_confirmation" type="button" aria-label="Покажи паролата" title="Покажи паролата">
                                <i class="bi bi-eye fs-5" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Запази паролата</button>
                </form>
            </div>
        </div>
    </div>

    @include('auth.partials.password-reveal-script')
@endsection
