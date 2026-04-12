@extends('layouts.app')

@section('title', 'Избор на заглавие')

@section('content')
    <div class="page-header">
        <h1 class="h2 fw-bold text-dark mb-1">Изберете заглавие</h1>
        <p class="text-secondary mb-0">След избор AI генерира 4 секции с по 4 въпроса (16 общо).</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body p-4 p-md-5">
                    <form method="post" action="{{ route('questionnaires.select-title', $questionnaire) }}">
                        @csrf
                        <div class="list-group list-group-flush border rounded-3 overflow-hidden mb-4">
                            @foreach ($titles as $i => $t)
                                <label class="list-group-item list-group-item-action d-flex align-items-start gap-3 py-3 cursor-pointer mb-0">
                                    <input class="form-check-input flex-shrink-0 mt-1" type="radio" name="chosen_title" id="title_{{ $i }}" value="{{ $t }}" @checked(old('chosen_title') === $t) required>
                                    <span class="fw-medium text-dark">{{ $t }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="d-grid d-sm-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-lg px-4 shadow-sm">
                                <i class="bi bi-lightning-charge me-2"></i>Генерирай въпроси
                            </button>
                            <a href="{{ route('questionnaires.index') }}" class="btn btn-outline-secondary btn-lg">Назад</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
