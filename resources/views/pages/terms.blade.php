@extends('layouts.app')

@section('title', 'Общи условия')

@section('meta_description', 'Общи условия за ползване на Questionnaire AI: права, отговорности, ограничение на отговорност и контакт.')

@section('content')
    <article class="page-header">
        <h1 class="h2 fw-bold text-dark mb-2">Общи условия</h1>
        <p class="text-secondary small mb-0">Последна актуализация: {{ date('d.m.Y') }}</p>
    </article>

    <div class="card p-4 p-md-5">
        <div class="small text-secondary" style="max-width: 42rem; margin: 0 auto;">
            <h2 class="h5 text-dark mt-0">1. Услугата</h2>
            <p>
                <strong class="text-dark">{{ config('app.name') }}</strong> предоставя онлайн инструмент за създаване и провеждане на анкети и тестове с използване на изкуствен интелект (AI).
                Ползването на услугата означава съгласие с настоящите общи условия и с <a href="{{ route('privacy') }}">политиката за поверителност</a>.
            </p>

            <h2 class="h5 text-dark mt-4">2. Регистрация и акаунт</h2>
            <p>
                Носите отговорност за верността на данните при регистрация и за поверителността на паролата си.
                Можем да ограничим или прекратим достъп при злоупотреба или нарушаване на условията.
            </p>

            <h2 class="h5 text-dark mt-4">3. Съдържание и AI</h2>
            <p>
                Генерираните от AI текстове са автоматични и могат да съдържат неточности.
                Вие носите отговорност за преглед, редакция и законосъобразност на съдържанието, което публикувате или разпространявате чрез приложението.
            </p>

            <h2 class="h5 text-dark mt-4">4. Ограничение на отговорност</h2>
            <p>
                Услугата се предоставя „както е“. В максималната степен, позволена от закона, не носим отговорност за косвени щети, загуба на данни или прекъсване на услугата.
            </p>

            <h2 class="h5 text-dark mt-4">5. Промени</h2>
            <p>
                Можем да актуализираме тези условия. Продължаващото ползване след промяна означава съгласие, освен ако законът изисква друго.
            </p>

            <h2 class="h5 text-dark mt-4">6. Контакт</h2>
            <p class="mb-0">
                Въпроси: <a href="mailto:alexander.krist@gmail.com">alexander.krist@gmail.com</a>
            </p>
        </div>
    </div>

    <div class="mt-4 text-center">
        <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Начало
        </a>
    </div>
@endsection
