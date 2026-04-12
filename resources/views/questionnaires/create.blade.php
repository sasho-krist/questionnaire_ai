@extends('layouts.app')

@section('title', 'Нова анкета')

@section('content')
    <div class="page-header">
        <h1 class="h2 fw-bold text-dark mb-1">Нова анкета</h1>
        <p class="text-secondary mb-0">Въведете работно заглавие и ключови думи. AI ще предложи 5 различни заглавия.</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body p-4 p-md-5">
                    <form method="post" action="{{ route('questionnaires.store') }}">
                        @csrf
                        <div class="mb-4">
                            <label for="user_title" class="form-label fw-medium">Заглавие</label>
                            <input type="text" name="user_title" id="user_title" value="{{ old('user_title') }}" required maxlength="255"
                                   class="form-control form-control-lg @error('user_title') is-invalid @enderror" placeholder="Напр. Оценка на екипния климат">
                            @error('user_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label for="topic_keywords" class="form-label fw-medium">Ключови думи за темата</label>
                            <textarea name="topic_keywords" id="topic_keywords" rows="4" required maxlength="2000"
                                      class="form-control @error('topic_keywords') is-invalid @enderror"
                                      placeholder="напр. устойчивост, екипна работа, обратна връзка...">{{ old('topic_keywords') }}</textarea>
                            @error('topic_keywords')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="d-grid d-sm-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-lg px-4 shadow-sm">
                                <i class="bi bi-stars me-2"></i>Генерирай заглавия
                            </button>
                            <a href="{{ route('questionnaires.index') }}" class="btn btn-outline-secondary btn-lg">Отказ</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
