@extends('layouts.app')

@section('title', 'Анкети')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3 mb-4">
        <div class="page-header mb-0 py-0">
            <h1 class="h2 fw-bold text-body-emphasis mb-1">Генерирани анкети</h1>
            <p class="text-body-secondary mb-0 small">Преглед на всички анкети; редакция и изтриване само на вашите.</p>
        </div>
        <a href="{{ route('questionnaires.create') }}" class="btn btn-primary btn-lg shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Нова анкета
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="get" action="{{ route('questionnaires.index') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label for="q" class="form-label small text-body-secondary mb-1">Търсене</label>
                    <input type="search" name="q" id="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Заглавие или ключови думи…">
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label small text-body-secondary mb-1">Статус</label>
                    <select name="status" id="status" class="form-select form-select-sm">
                        <option value="">Всички</option>
                        <option value="draft" @selected(request('status') === 'draft')>Чернова</option>
                        <option value="titles_ready" @selected(request('status') === 'titles_ready')>Избор на заглавие</option>
                        <option value="building" @selected(request('status') === 'building')>В изграждане</option>
                        <option value="completed" @selected(request('status') === 'completed')>Завършена</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="bi bi-search me-1"></i> Филтрирай
                    </button>
                    <a href="{{ route('questionnaires.index') }}" class="btn btn-outline-secondary btn-sm">Изчисти</a>
                </div>
            </form>
        </div>
    </div>

    @if ($questionnaires->isEmpty())
        <div class="card text-center py-5 px-4">
            <div class="card-body">
                <i class="bi bi-inbox display-4 text-body-secondary opacity-50"></i>
                @if (request()->filled('q') || request()->filled('status'))
                    <p class="text-body-secondary mt-3 mb-4">Няма анкети, които да отговарят на филтрите. Опитайте с други критерии или <a href="{{ route('questionnaires.index') }}">изчистете филтрите</a>.</p>
                @else
                    <p class="text-body-secondary mt-3 mb-4">Все още няма анкети.</p>
                @endif
                <a href="{{ route('questionnaires.create') }}" class="btn btn-primary">Нова анкета</a>
            </div>
        </div>
    @else
        <div class="card overflow-hidden">
            <div class="list-group list-group-flush">
                @foreach ($questionnaires as $q)
                    @php
                        $isOwner = $q->user_id !== null && (int) $q->user_id === (int) auth()->id();
                    @endphp
                    <div class="list-group-item py-4">
                        <div class="row align-items-center g-3">
                            <div class="col-md">
                                <h2 class="h6 fw-semibold mb-1 text-body-emphasis">{{ $q->chosen_title ?? $q->user_title }}</h2>
                                @if ($q->sections->isNotEmpty())
                                    <div class="small text-body mb-1">
                                        <span class="text-muted">Секции:</span>
                                        @foreach ($q->sections as $i => $sec)
                                            @if ($i > 0)<span class="text-muted px-1">·</span>@endif
                                            <span title="{{ $sec->title }}">{{ \Illuminate\Support\Str::limit($sec->title, 72) }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="small text-body-secondary mb-1">
                                    {{ $q->sections_count }} {{ $q->sections_count === 1 ? 'секция' : 'секции' }}
                                    ·
                                    {{ $q->questions_count }} {{ $q->questions_count === 1 ? 'въпрос' : 'въпроса' }}
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-2 small text-body-secondary">
                                    <span>От:</span>
                                    <span class="text-body">{{ $q->user?->name ?? '—' }}</span>
                                    <span class="text-muted">·</span>
                                    <span>Статус:</span>
                                    @if ($q->status === 'completed')
                                        <span class="badge rounded-pill text-bg-success">Завършена</span>
                                    @elseif ($q->status === 'building')
                                        <span class="badge rounded-pill text-bg-warning text-dark">В изграждане</span>
                                    @elseif ($q->status === 'titles_ready')
                                        <span class="badge rounded-pill text-bg-info text-dark">Избор на заглавие</span>
                                    @else
                                        <span class="badge rounded-pill text-bg-secondary">{{ $q->status }}</span>
                                    @endif
                                    <span class="text-muted">·</span>
                                    <span title="{{ $q->created_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}">Създадена {{ $q->created_at->diffForHumans() }}</span>
                                    @if ($q->updated_at && $q->updated_at->greaterThan($q->created_at))
                                        <span class="text-muted">·</span>
                                        <span title="{{ $q->updated_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}">Обновена {{ $q->updated_at->diffForHumans() }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-auto">
                                <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                                    @if ($isOwner && $q->status === 'titles_ready')
                                        <a href="{{ route('questionnaires.titles', $q) }}" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-type me-1"></i> Заглавия
                                        </a>
                                    @endif
                                    @if ($isOwner && in_array($q->status, ['building', 'completed'], true))
                                        <a href="{{ route('questionnaires.build', $q) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-sliders me-1"></i> Конструктор
                                        </a>
                                    @endif
                                    @if ($q->status === 'completed')
                                        <a href="{{ route('questionnaires.results', $q) }}" class="btn btn-outline-dark btn-sm">
                                            <i class="bi bi-clipboard-data me-1"></i> Резултати
                                        </a>
                                    @endif
                                    @if ($q->status === 'completed')
                                        <a href="{{ route('questionnaires.play.start', $q) }}" class="btn btn-success btn-sm shadow-sm">
                                            <i class="bi bi-play-fill me-1"></i> Старт
                                        </a>
                                    @endif
                                    @if ($isOwner)
                                        <form method="post" action="{{ route('questionnaires.duplicate', $q) }}" class="d-inline" onsubmit="return confirm('Да създадете ли копие на тази анкета?');">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-secondary btn-sm">
                                                <i class="bi bi-files me-1"></i> Копие
                                            </button>
                                        </form>
                                        <form method="post" action="{{ route('questionnaires.destroy', $q) }}" class="d-inline" onsubmit="return confirm('Да изтриете ли тази анкета? Това действие е необратимо.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                <i class="bi bi-trash me-1"></i> Изтрий
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $questionnaires->links() }}
        </div>
    @endif
@endsection
