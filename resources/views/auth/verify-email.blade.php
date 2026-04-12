@extends('layouts.app')

@section('title', 'Потвърдете имейла')

@section('meta_description', 'Потвърдете имейл адреса си, за да ползвате Questionnaire AI.')

@section('robots', 'noindex, follow')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="page-header">
                <h1 class="h3 mb-1">Потвърдете имейла си</h1>
                <p class="text-muted mb-0">
                    Изпратихме връзка на <strong>{{ auth()->user()->email }}</strong>. Отворете писмото и натиснете бутона за потвърждение, за да продължите.
                </p>
            </div>

            <div class="card p-4">
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                <p class="text-secondary small mb-4">
                    Не виждате писмото? Проверете папката „Спам“. Можете да поискате ново изпращане.
                </p>

                <form method="post" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-envelope me-1"></i> Изпрати отново
                    </button>
                </form>

                <hr class="my-4">

                <form method="post" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-link text-secondary p-0">Изход и друг акаунт</button>
                </form>
            </div>
        </div>
    </div>
@endsection
