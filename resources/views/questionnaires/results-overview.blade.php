@extends('layouts.app')

@section('title', 'Резултати — '.($questionnaire->chosen_title ?? $questionnaire->user_title))

@section('content')
    <div class="page-header">
        <h1 class="h3 mb-1">Резултати по участници</h1>
        <p class="text-secondary mb-0">{{ $questionnaire->chosen_title ?? $questionnaire->user_title }}</p>
    </div>

    <p class="small text-muted mb-4">
        Показват се всички завършили опити. Новите опити записват кой потребител ги е направил; стари опити без акаунт се отбелязват като „Анонимен“.
    </p>

    @if ($attempts->isEmpty())
        <div class="card text-center py-5 px-4">
            <div class="card-body">
                <i class="bi bi-clipboard-data display-4 text-secondary opacity-50"></i>
                <p class="text-secondary mt-3 mb-0">Все още няма завършени опити за тази анкета.</p>
            </div>
        </div>
    @else
        <div class="card overflow-hidden">
            <div class="table-responsive mb-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-4">#</th>
                            <th scope="col">Участник</th>
                            <th scope="col">Резултат</th>
                            <th scope="col">Завършено</th>
                            <th scope="col" class="pe-4 text-end">Детайли</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($attempts as $i => $attempt)
                            <tr>
                                <td class="ps-4 text-muted">{{ $i + 1 }}</td>
                                <td>
                                    @if ($attempt->user)
                                        <span class="fw-medium">{{ $attempt->user->name }}</span>
                                        <span class="d-block small text-muted">{{ $attempt->user->email }}</span>
                                    @else
                                        <span class="text-muted">Анонимен (преди въвеждане на потребители)</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($attempt->max_score !== null && (float) $attempt->max_score > 0)
                                        <span class="fw-semibold">{{ number_format((float) $attempt->score, 2, ',', ' ') }}</span>
                                        <span class="text-muted">/ {{ number_format((float) $attempt->max_score, 2, ',', ' ') }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="small text-secondary">
                                    {{ $attempt->completed_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') ?? '—' }}
                                </td>
                                <td class="pe-4 text-end">
                                    <a href="{{ route('questionnaires.play.results', $attempt) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i> Преглед
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="mt-4">
        <a href="{{ route('questionnaires.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Към анкетите
        </a>
    </div>
@endsection
