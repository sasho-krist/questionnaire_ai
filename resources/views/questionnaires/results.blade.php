@extends('layouts.app')

@section('title', 'Резултат')

@section('content')
    @php
        $answers = $attempt->answers ?? [];
    @endphp

    <div class="page-header">
        <h1 class="h2 fw-bold text-dark mb-1">Резултат: {{ $questionnaire->chosen_title }}</h1>
        <p class="text-secondary mb-0">Преглед на отговорите и резултата от този опит.</p>
    </div>

    <div class="card border-primary border-2 mb-4">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-md-8">
                    <h2 class="h4 mb-2">
                        <i class="bi bi-trophy me-2 text-warning"></i>
                        Точки: <strong>{{ $attempt->score !== null ? $attempt->score : '—' }}</strong>
                        @if ($attempt->max_score !== null && (float) $attempt->max_score > 0)
                            <span class="text-secondary">/ {{ $attempt->max_score }}</span>
                        @elseif ($attempt->max_score !== null && (float) $attempt->max_score <= 0)
                            <span class="text-secondary small">(няма въпроси със зададен верен отговор за точкуване)</span>
                        @endif
                    </h2>
                    <p class="text-secondary small mb-0">
                        За верен отговор: <strong>{{ $questionnaire->points_per_correct }}</strong> т.
                        @if ($questionnaire->seconds_per_question)
                            · Лимит на въпрос: <strong>{{ $questionnaire->seconds_per_question }}</strong> сек.
                        @else
                            · Без времеви лимит
                        @endif
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <span class="badge text-bg-secondary">Завършен {{ $attempt->completed_at?->format('d.m.Y H:i') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="vstack gap-4">
        @foreach ($questionnaire->sections as $section)
            <div class="card">
                <div class="card-header bg-light py-3">
                    <h2 class="h6 fw-semibold mb-0 text-primary">
                        <i class="bi bi-folder2-open me-2"></i>{{ $section->title }}
                    </h2>
                </div>
                <div class="card-body">
                    @foreach ($section->questions as $question)
                        @php
                            $userRaw = data_get($answers, (string) $question->id) ?? data_get($answers, $question->id);
                        @endphp
                        <div class="mb-4 @if (! $loop->last) pb-4 border-bottom @endif">
                            <p class="fw-medium text-dark mb-2">{{ $question->body }}</p>

                            @if ($question->isScoredMultipleChoice())
                                @php
                                    $userIdx = $userRaw !== null && $userRaw !== '' ? (int) $userRaw : null;
                                    $correctIdx = (int) $question->correct_option;
                                    $isCorrect = $userIdx !== null && $userIdx === $correctIdx;
                                    $userLabel = $userIdx !== null ? ($question->choice_options[$userIdx] ?? '—') : 'няма отговор';
                                    $correctLabel = $question->choice_options[$correctIdx] ?? '';
                                @endphp
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    @if ($userIdx === null)
                                        <span class="badge rounded-pill text-bg-secondary">Без отговор</span>
                                    @elseif ($isCorrect)
                                        <span class="badge rounded-pill text-bg-success">Верен (+{{ $questionnaire->points_per_correct }} т.)</span>
                                    @else
                                        <span class="badge rounded-pill text-bg-danger">Грешен</span>
                                    @endif
                                </div>
                                <ul class="list-unstyled small mb-0">
                                    <li><span class="text-secondary">Вашият избор:</span> {{ $userLabel }}</li>
                                    @if (! $isCorrect)
                                        <li><span class="text-secondary">Верен отговор:</span> <span class="text-success fw-medium">{{ $correctLabel }}</span></li>
                                    @endif
                                </ul>
                            @elseif ($question->hasMultipleChoice())
                                @php
                                    $userIdx = $userRaw !== null && $userRaw !== '' ? (int) $userRaw : null;
                                    $userLabel = $userIdx !== null ? ($question->choice_options[$userIdx] ?? '—') : 'няма отговор';
                                @endphp
                                <p class="small text-secondary mb-0">Вашият избор: {{ $userLabel }}</p>
                            @else
                                <div class="bg-light rounded p-3 small">
                                    <span class="text-secondary">Отговор:</span>
                                    {{ is_string($userRaw) && $userRaw !== '' ? $userRaw : '—' }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4 d-flex flex-wrap gap-2">
        <a href="{{ route('questionnaires.index') }}" class="btn btn-primary">Към анкетите</a>
        <a href="{{ route('questionnaires.play.start', $questionnaire) }}" class="btn btn-outline-primary">Нов опит</a>
    </div>
@endsection
