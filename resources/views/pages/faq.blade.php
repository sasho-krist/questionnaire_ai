@extends('layouts.app')

@section('title', 'Често задавани въпроси')

@section('meta_description', 'ЧЗВ за Questionnaire AI: как се създава анкета, OpenAI, точки, времеви лимит, резултати и поверителност.')

@section('content')
    <div class="page-header">
        <h1 class="h2 fw-bold text-dark mb-1">Често задавани въпроси</h1>
        <p class="text-secondary mb-0">Кратки отговори за работа с приложението.</p>
    </div>

    <div class="accordion accordion-flush card shadow-sm" id="faqAccordion">
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="true">
                    Как започвам нова анкета?
                </button>
            </h2>
            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-secondary small">
                    Влезте в акаунта си, отидете на „Нова анкета“, въведете работно заглавие и ключови думи. Системата ще предложи пет заглавия — изберете едно, след което AI генерира секции и въпроси.
                </div>
            </div>
        </div>
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                    Нужен ли е OpenAI API ключ?
                </button>
            </h2>
            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-secondary small">
                    Да — администраторът на инсталацията трябва да зададе <code>AI_API_PUBLIC_KEY</code> в <code>.env</code>. Без ключ генерирането няма да работи.
                </div>
            </div>
        </div>
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                    Как работят точките и таймерът?
                </button>
            </h2>
            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-secondary small">
                    В конструктора задавате точки за верен отговор и по желание секунди на въпрос. При лимит се изчислява общ дедлайн за целия тест според броя въпроси.
                </div>
            </div>
        </div>
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                    Виждат ли се резултатите на другите потребители?
                </button>
            </h2>
            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-secondary small">
                    Обобщената страница с резултати по участници е достъпна за всеки логнат потребител за завършени анкети. Личният детайлен преглед на опит остава чрез линка към опита.
                </div>
            </div>
        </div>
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                    Какви данни се изпращат към OpenAI?
                </button>
            </h2>
            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-secondary small">
                    За генериране се изпращат въведените от вас заглавие и ключови думи, както и контекст за избраните стъпки. Вижте <a href="{{ route('privacy') }}">политиката за поверителност</a>.
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 text-center">
        <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Начало
        </a>
    </div>
@endsection
