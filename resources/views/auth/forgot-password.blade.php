@extends('layouts.app')

@section('title', 'Забравена парола')

@section('meta_description', 'Възстановяване на парола за Questionnaire AI — въведете имейла си за връзка за нова парола.')

@section('robots', 'noindex, follow')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="page-header">
                <h1 class="h3 mb-1">Забравена парола</h1>
                <p class="text-muted mb-0">Въведете имейла си. Ще изпратим връзка за нова парола.</p>
            </div>

            <div class="card p-4">
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                <form method="post" action="{{ route('password.email') }}" class="vstack gap-3">
                    @csrf
                    <div>
                        <label for="email" class="form-label">Имейл</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}"
                               class="form-control @error('email') is-invalid @enderror" required autofocus autocomplete="username">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Изпрати връзка</button>
                </form>

                <hr class="my-4">
                <p class="text-center text-muted small mb-0">
                    <a href="{{ route('login') }}">Назад към вход</a>
                </p>
            </div>
        </div>
    </div>
@endsection
