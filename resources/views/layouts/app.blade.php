<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Questionnaire AI') — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --bs-body-font-family: "DM Sans", system-ui, -apple-system, sans-serif;
            --bs-primary: #4f46e5;
            --bs-primary-rgb: 79, 70, 229;
            --bs-link-color: #4f46e5;
            --bs-link-hover-color: #4338ca;
        }
        .navbar-brand { font-weight: 600; letter-spacing: -0.02em; }
        .page-header {
            border-left: 4px solid var(--bs-primary);
            padding-left: 1rem;
            margin-bottom: 1.75rem;
        }
        .card { border: none; box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.06); }
        .list-group-item-action:hover { background-color: rgba(79, 70, 229, 0.06); }
        .cursor-pointer { cursor: pointer; }
    </style>
    @stack('styles')
</head>
<body class="bg-light d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm" style="background: linear-gradient(135deg, #4f46e5 0%, #6366f1 50%, #7c3aed 100%);">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('questionnaires.index') }}">
                <i class="bi bi-ui-checks-grid"></i>
                <span>Questionnaire AI</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Меню">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto gap-lg-2">
                    <li class="nav-item">
                        <a class="nav-link px-3 rounded-pill {{ request()->routeIs('questionnaires.index') ? 'bg-white bg-opacity-25' : '' }}" href="{{ route('questionnaires.index') }}">
                            <i class="bi bi-collection me-1"></i> Анкети
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 rounded-pill {{ request()->routeIs('questionnaires.create') ? 'bg-white bg-opacity-25' : '' }}" href="{{ route('questionnaires.create') }}">
                            <i class="bi bi-plus-lg me-1"></i> Нова анкета
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="flex-grow-1 py-4">
        <div class="container py-2">
            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Затвори"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger shadow-sm" role="alert">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                        <div>
                            <strong>Има проблем с заявката.</strong>
                            <ul class="mb-0 mt-1 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <footer class="border-top bg-white py-3 mt-auto">
        <div class="container text-center text-muted small">
            {{ config('app.name') }} · AI-генерирани анкети
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    @stack('scripts')
</body>
</html>
