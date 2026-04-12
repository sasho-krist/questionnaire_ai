@extends('layouts.app')

@section('title', 'Конструктор')

@section('content')
    <div class="page-header">
        <h1 class="h2 fw-bold text-dark mb-1">{{ $questionnaire->chosen_title }}</h1>
        <p class="text-secondary mb-0">
            @if ($questionnaire->status === 'completed')
                <span class="badge text-bg-success me-2">Завършена</span>
                Можете да стартирате анкетата от списъка.
            @else
                <span class="badge text-bg-warning text-dark me-2">В изграждане</span>
                Добавете още въпроси по секция или завършете генерирането.
            @endif
        </p>
    </div>

    @if (in_array($questionnaire->status, ['building', 'completed'], true))
        <div class="card mb-4">
            <div class="card-header bg-light fw-semibold">
                <i class="bi bi-gear me-2"></i>Настройки на теста
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('questionnaires.settings', $questionnaire) }}" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-3">
                        <label for="points_per_correct" class="form-label">Точки за верен отговор</label>
                        <input type="number" step="0.01" min="0.01" max="999.99" name="points_per_correct" id="points_per_correct"
                               class="form-control @error('points_per_correct') is-invalid @enderror"
                               value="{{ old('points_per_correct', $questionnaire->points_per_correct) }}" required>
                        @error('points_per_correct')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="seconds_per_question" class="form-label">Секунди на въпрос (лимит)</label>
                        <input type="number" min="1" max="86400" name="seconds_per_question" id="seconds_per_question"
                               class="form-control @error('seconds_per_question') is-invalid @enderror"
                               value="{{ old('seconds_per_question', $questionnaire->seconds_per_question) }}"
                               placeholder="Празно = без лимит">
                        <div class="form-text">Общо време = броят въпроси × секунди (за нови опити).</div>
                        @error('seconds_per_question')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2 me-1"></i>Запази настройките
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="vstack gap-4">
        @foreach ($questionnaire->sections as $section)
            <div class="card">
                <div class="card-header bg-white py-3 border-bottom-0 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h2 class="h5 fw-semibold mb-0 text-primary">
                        <i class="bi bi-folder2-open me-2 opacity-75"></i>{{ $section->title }}
                    </h2>
                    @if ($questionnaire->status === 'building')
                        <form method="post" action="{{ route('questionnaires.generate-more', $questionnaire) }}" class="m-0">
                            @csrf
                            <input type="hidden" name="section_id" value="{{ $section->id }}">
                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-plus-circle me-1"></i> Още въпроси
                            </button>
                        </form>
                    @endif
                </div>
                <div class="card-body pt-0">
                    <ol class="mb-0 ps-3">
                        @foreach ($section->questions as $question)
                            <li class="mb-3 text-secondary">
                                <span class="text-dark">{{ $question->body }}</span>
                                @if ($question->hasMultipleChoice())
                                    <ul class="small mt-2 mb-0 ps-3">
                                        @foreach ($question->choice_options as $i => $opt)
                                            <li class="@if ($question->isScoredMultipleChoice() && (int) $question->correct_option === $i) fw-semibold text-success @endif">
                                                {{ $opt }}
                                                @if ($question->isScoredMultipleChoice() && (int) $question->correct_option === $i)
                                                    <i class="bi bi-check-circle-fill ms-1" title="Верен отговор"></i>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        @endforeach
    </div>

    @if ($questionnaire->status === 'building')
        <div class="card border-primary border-2 mt-4">
            <div class="card-body p-4 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
                <div>
                    <strong class="d-block text-dark">Готови ли сте?</strong>
                    <span class="text-secondary small">След това анкетата може да се стартира за попълване.</span>
                </div>
                <form method="post" action="{{ route('questionnaires.finish', $questionnaire) }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-success btn-lg shadow-sm">
                        <i class="bi bi-check2-circle me-2"></i>Завърши генерирането
                    </button>
                </form>
            </div>
        </div>
    @endif
@endsection
