@extends('layouts.app')

@section('title', 'Начало')

@section('meta_description', 'Questionnaire AI: AI анкети, REST API с Sanctum, точкуване, резултати и споделяне. Регистрирайте се безплатно.')

@section('content')
    <div class="text-center py-4 py-md-5">
        <h1 class="display-6 fw-bold text-body mb-3">AI тестове и анкети за минути</h1>
        <p class="lead col-lg-8 mx-auto mb-4 landing-lead">
            Опишете темата и ключовите думи — системата генерира заглавия, секции и въпроси с множествен избор,
            настройва точки и време, а след това събира резултатите на участниците на едно място.
        </p>
        <div class="d-flex flex-wrap justify-content-center gap-3 mb-5">
            <a href="{{ route('register') }}" class="btn btn-primary btn-lg px-4 shadow-sm">
                <i class="bi bi-person-plus me-2"></i>Регистрация
            </a>
            <a href="{{ route('login') }}" class="btn btn-outline-primary btn-lg px-4">
                <i class="bi bi-box-arrow-in-right me-2"></i>Вход
            </a>
        </div>
    </div>

    <div class="row g-4 mb-5 justify-content-center">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm p-4">
                <div class="text-primary mb-2"><i class="bi bi-stars fs-2"></i></div>
                <h2 class="h5 fw-semibold text-body">Генериране с AI</h2>
                <p class="small mb-0 text-body-secondary landing-card-text">Заглавия, секции и въпроси на български чрез OpenAI API — по вашите ключови думи.</p>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm p-4">
                <div class="text-primary mb-2"><i class="bi bi-trophy fs-2"></i></div>
                <h2 class="h5 fw-semibold text-body">Точки и таймер</h2>
                <p class="small mb-0 text-body-secondary landing-card-text">Автоматично точкуване при верен отговор и опционален лимит време на въпрос.</p>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm p-4">
                <div class="text-primary mb-2"><i class="bi bi-people fs-2"></i></div>
                <h2 class="h5 fw-semibold text-body">Резултати</h2>
                <p class="small mb-0 text-body-secondary landing-card-text">Преглед на опити, експорт в CSV и споделяне на линк за стартиране на теста.</p>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm p-4">
                <div class="text-primary mb-2"><i class="bi bi-code-slash fs-2"></i></div>
                <h2 class="h5 fw-semibold text-body">REST API</h2>
                <p class="small mb-2 text-body-secondary landing-card-text">
                    JSON интерфейс под <code class="user-select-all small">{{ url('/api') }}</code> с Laravel Sanctum (<strong>Bearer token</strong>).
                    Същите функции като в сайта: анкети, AI генериране, попълване на тестове и резултати — за интеграции и външни приложения.
                </p>
                <p class="small mb-0">
                    <a href="{{ route('api.docs') }}" class="link-primary text-decoration-none fw-medium">
                        Документация и примери <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                </p>
            </div>
        </div>
    </div>

    <div class="text-center small landing-footer-links">
        <a href="{{ route('faq') }}" class="link-secondary text-decoration-none">ЧЗВ</a>
        ·
        <a href="{{ route('api.docs') }}" class="link-secondary text-decoration-none">REST API</a>
        ·
        <a href="{{ route('terms') }}" class="link-secondary">Общи условия</a>
        ·
        <a href="{{ route('privacy') }}" class="link-secondary">Поверителност</a>
    </div>
@endsection
