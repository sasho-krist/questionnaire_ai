@extends('layouts.app')

@section('title', 'Попълване')

@push('styles')
    <style>
        .quiz-field.quiz-time-expired {
            border: 2px solid var(--bs-danger) !important;
            border-radius: 0.5rem;
            padding: 1rem;
            background-color: rgba(var(--bs-danger-rgb), 0.09);
        }
        .quiz-field.quiz-time-expired .list-group {
            border-color: var(--bs-danger) !important;
        }
        .quiz-field.quiz-time-expired .list-group-item {
            background-color: transparent;
        }
        .quiz-field.quiz-time-expired .form-label {
            color: var(--bs-danger);
        }
    </style>
@endpush

@section('content')
    <div class="page-header">
        <h1 class="h2 fw-bold text-dark mb-1">{{ $questionnaire->chosen_title }}</h1>
        <p class="text-secondary mb-0">Отговорете по желание. Можете да запазите и да продължите по-късно.</p>
    </div>

    @if ($attempt->deadline_at)
        <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2 shadow-sm" role="alert">
            <div>
                <i class="bi bi-hourglass-split me-2"></i>
                <strong>Оставащо време:</strong>
                <span id="quiz-timer" class="font-monospace ms-1">—</span>
            </div>
            <span class="small text-muted">Лимит: {{ $questionnaire->seconds_per_question }} сек./въпрос × {{ $questionnaire->sections->sum(fn ($s) => $s->questions->count()) }} въпроса</span>
        </div>
    @endif

    @php
        $answers = $attempt->answers ?? [];
    @endphp

    <form id="quiz-form" method="post" action="{{ route('questionnaires.play.save', $attempt) }}">
        @csrf
        <div class="vstack gap-4 mb-4">
            @foreach ($questionnaire->sections as $section)
                <div class="card">
                    <div class="card-header bg-primary text-white py-3">
                        <h2 class="h6 fw-semibold mb-0">
                            <i class="bi bi-chat-left-text me-2"></i>{{ $section->title }}
                        </h2>
                    </div>
                    <div class="card-body">
                        @foreach ($section->questions as $question)
                            @php
                                $saved = old('answers.'.$question->id);
                                if ($saved === null) {
                                    $saved = data_get($answers, (string) $question->id);
                                }
                                if ($saved === null) {
                                    $saved = data_get($answers, $question->id);
                                }
                            @endphp
                            <div class="mb-4 quiz-field @if (! $loop->last) pb-4 border-bottom @endif">
                                <p class="form-label fw-medium text-dark mb-2">{{ $question->body }}</p>
                                @if ($question->hasMultipleChoice())
                                    <div class="list-group @error('answers.'.$question->id) is-invalid border border-danger rounded @enderror">
                                        @foreach ($question->choice_options as $i => $label)
                                            <label class="list-group-item list-group-item-action d-flex align-items-start gap-2 mb-0 cursor-pointer" for="q{{ $question->id }}_{{ $i }}">
                                                <input class="form-check-input flex-shrink-0 mt-1 quiz-input" type="radio" name="answers[{{ $question->id }}]" id="q{{ $question->id }}_{{ $i }}" value="{{ $i }}"
                                                    @checked((string) $saved === (string) $i)>
                                                <span class="text-secondary">{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('answers.'.$question->id)
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                @else
                                    <textarea name="answers[{{ $question->id }}]" id="q{{ $question->id }}" rows="3" maxlength="10000"
                                              class="form-control quiz-input @error('answers.'.$question->id) is-invalid @enderror"
                                              placeholder="Вашият отговор...">{{ is_string($saved) ? $saved : '' }}</textarea>
                                    @error('answers.'.$question->id)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card bg-light">
            <div class="card-body p-4 d-flex flex-column flex-sm-row flex-wrap gap-2">
                <button type="submit" name="mark_complete" value="0" id="btn-save-progress" class="btn btn-primary btn-lg shadow-sm">
                    <i class="bi bi-save me-2"></i>Запази отговорите
                </button>
                <button type="submit" name="mark_complete" value="1" id="btn-finish-quiz" class="btn btn-outline-success btn-lg">
                    <i class="bi bi-flag-fill me-2"></i>Завърши теста и виж резултата
                </button>
                <a href="{{ route('questionnaires.index') }}" class="btn btn-outline-secondary btn-lg ms-sm-auto">Към анкетите</a>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('quiz-form');
            if (!form) return;
            form.addEventListener('submit', function () {
                form.querySelectorAll('.quiz-input').forEach(function (input) {
                    input.disabled = false;
                });
            });
            @if ($attempt->deadline_at)
            (function () {
                const deadline = new Date(@json($attempt->deadline_at->toIso8601String())).getTime();
                const el = document.getElementById('quiz-timer');
                const btnSave = document.getElementById('btn-save-progress');
                function fmt(s) {
                    const m = Math.floor(s / 60);
                    const r = s % 60;
                    return m + ':' + String(r).padStart(2, '0');
                }
                let timeExpired = false;
                function expireUi() {
                    if (timeExpired) return;
                    timeExpired = true;
                    if (el) el.textContent = '0:00';
                    if (btnSave) btnSave.disabled = true;
                    form.querySelectorAll('.quiz-input').forEach(function (input) {
                        input.disabled = true;
                    });
                    form.querySelectorAll('.quiz-field').forEach(function (block) {
                        block.classList.add('quiz-time-expired');
                    });
                    form.insertAdjacentHTML('afterbegin', '<div class="alert alert-danger mb-3" role="alert"><strong>Времето изтече.</strong> Отговорите не могат да се променят. Натиснете „Завърши теста и виж резултата“, за да запишете текущия избор.</div>');
                }
                function tick() {
                    const left = Math.max(0, Math.floor((deadline - Date.now()) / 1000));
                    if (el) el.textContent = fmt(left);
                    if (left <= 0) expireUi();
                }
                tick();
                setInterval(tick, 1000);
            })();
            @endif
        })();
    </script>
@endpush
