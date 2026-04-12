@extends('layouts.app')

@section('title', 'Анкети')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3 mb-4">
        <div class="page-header mb-0 py-0">
            <h1 class="h2 fw-bold text-dark mb-1">Генерирани анкети</h1>
            <p class="text-secondary mb-0 small">Преглед на всички анкети; редакция и изтриване само на вашите.</p>
        </div>
        <a href="{{ route('questionnaires.create') }}" class="btn btn-primary btn-lg shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Нова анкета
        </a>
    </div>

    @if ($questionnaires->isEmpty())
        <div class="card text-center py-5 px-4">
            <div class="card-body">
                <i class="bi bi-inbox display-4 text-secondary opacity-50"></i>
                <p class="text-secondary mt-3 mb-4">Все още няма анкети.</p>
                <a href="{{ route('questionnaires.create') }}" class="btn btn-primary">Създайте първата</a>
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
                                <h2 class="h6 fw-semibold mb-1 text-dark">{{ $q->chosen_title ?? $q->user_title }}</h2>
                                <div class="d-flex flex-wrap align-items-center gap-2 small text-secondary">
                                    <span>От:</span>
                                    <span class="text-dark">{{ $q->user?->name ?? '—' }}</span>
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
                                    <span class="text-muted">· {{ $q->created_at->diffForHumans() }}</span>
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
                                        <a href="{{ route('questionnaires.play.start', $q) }}" class="btn btn-success btn-sm shadow-sm">
                                            <i class="bi bi-play-fill me-1"></i> Старт
                                        </a>
                                    @endif
                                    @if ($isOwner)
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
