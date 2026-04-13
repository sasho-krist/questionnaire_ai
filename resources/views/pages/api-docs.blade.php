@extends('layouts.app')

@section('title', 'REST API')

@section('meta_description', 'Документация за REST API на Questionnaire AI: Laravel Sanctum, вход с token, анкети по UUID, попълване на тестове и резултати.')

@section('content')
    <div class="page-header">
        <h1 class="h2 fw-bold text-dark mb-1">REST API</h1>
        <p class="text-secondary mb-0">
            JSON API за интеграции и клиентски приложения. Базов адрес: <code class="user-select-all">{{ url('/api') }}</code>
        </p>
    </div>

    <div class="alert alert-light border shadow-sm mb-4" role="note">
        <strong class="text-body">Удостоверяване:</strong> след вход получавате <strong>Bearer token</strong> (Laravel Sanctum).
        Изпращайте го в заглавие <code>Authorization: Bearer &lt;token&gt;</code> и по желание <code>Accept: application/json</code>.
        Анкетите в пътищата се идентифицират с <strong>UUID</strong> (поле <code>uuid</code>), не с числово <code>id</code>. Секциите и въпросите в API се адресират с <strong>числови</strong> <code>id</code> от отговора на <code>GET /api/questionnaires/{uuid}/build</code>.
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white py-3">
            <h2 class="h6 fw-semibold mb-0"><i class="bi bi-key me-2"></i>Вход и потребител</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Метод</th>
                            <th>Път</th>
                            <th>Описание</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <tr>
                            <td><span class="badge text-bg-success">POST</span></td>
                            <td><code>/api/login</code></td>
                            <td>Тяло JSON: <code>email</code>, <code>password</code>. Отговор: <code>token</code>, <code>token_type</code>, <code>user</code>. Rate limit като уеб входа.</td>
                        </tr>
                        <tr>
                            <td><span class="badge text-bg-secondary">POST</span></td>
                            <td><code>/api/logout</code></td>
                            <td>Изтрива текущия token (изисква Bearer).</td>
                        </tr>
                        <tr>
                            <td><span class="badge text-bg-primary">GET</span></td>
                            <td><code>/api/user</code></td>
                            <td>Текущ потребител (изисква Bearer).</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="small text-secondary mt-3 mb-0">Пример с curl (заменете URL и паролата):</p>
            <pre class="bg-body-secondary bg-opacity-25 border rounded p-3 small mb-0 mt-2 overflow-x-auto"><code>curl -X POST "{{ url('/api/login') }}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"email\":\"you@example.com\",\"password\":\"your-password\"}"</code></pre>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white py-3">
            <h2 class="h6 fw-semibold mb-0"><i class="bi bi-collection me-2"></i>Анкети (създаване и редакция)</h2>
        </div>
        <div class="card-body">
            <p class="small text-secondary">Списъкът <code>GET /api/questionnaires</code> връща само <strong>вашите</strong> анкети. Query: <code>q</code> (търсене по заглавие на анкетата, ключови думи или <strong>заглавие на секция</strong>), <code>status</code> (<code>draft</code>, <code>titles_ready</code>, <code>building</code>, <code>completed</code>).</p>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Метод</th>
                            <th>Път</th>
                            <th>Описание</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <tr>
                            <td><span class="badge text-bg-primary">GET</span></td>
                            <td><code>/api/questionnaires</code></td>
                            <td>Списък (pagination).</td>
                        </tr>
                        <tr>
                            <td><span class="badge text-bg-success">POST</span></td>
                            <td><code>/api/questionnaires</code></td>
                            <td><code>user_title</code>, <code>topic_keywords</code> → AI генерира 5 заглавия.</td>
                        </tr>
                        <tr>
                            <td><span class="badge text-bg-primary">GET</span></td>
                            <td><code>/api/questionnaires/{uuid}</code></td>
                            <td>Обобщение (само собственик).</td>
                        </tr>
                        <tr>
                            <td><span class="badge text-bg-danger">DELETE</span></td>
                            <td><code>/api/questionnaires/{uuid}</code></td>
                            <td>Изтриване (само собственик).</td>
                        </tr>
                        <tr>
                            <td><span class="badge text-bg-secondary">POST</span></td>
                            <td><code>/api/questionnaires/{uuid}/duplicate</code></td>
                            <td>Копие на анкетата.</td>
                        </tr>
                        <tr>
                            <td><span class="badge text-bg-primary">GET</span></td>
                            <td><code>/api/questionnaires/{uuid}/titles</code></td>
                            <td>Предложени заглавия.</td>
                        </tr>
                        <tr>
                            <td><span class="badge text-bg-secondary">POST</span></td>
                            <td><code>/api/questionnaires/{uuid}/select-title</code></td>
                            <td><code>chosen_title</code> (едно от предложените) → генериране на секции и въпроси.</td>
                        </tr>
                        <tr>
                            <td><span class="badge text-bg-primary">GET</span></td>
                            <td><code>/api/questionnaires/{uuid}/build</code></td>
                            <td>Пълни секции и въпроси с верни индекси (редакция).</td>
                        </tr>
                        <tr>
                            <td><span class="badge text-bg-warning text-dark">PATCH</span></td>
                            <td><code>/api/questionnaires/{uuid}/title</code></td>
                            <td>JSON: <code>chosen_title</code> — заглавие на анкетата (като в списъка). Само при <code>building</code> / <code>completed</code>.</td>
                        </tr>
                        <tr>
                            <td><span class="badge text-bg-warning text-dark">PATCH</span></td>
                            <td><code>/api/questionnaires/{uuid}/sections/{sectionId}</code></td>
                            <td>JSON: <code>title</code> — заглавие на секцията. <code>sectionId</code> е числов <code>id</code> от <code>GET …/build</code>.</td>
                        </tr>
                        <tr>
                            <td><span class="badge text-bg-danger">DELETE</span></td>
                            <td><code>/api/questionnaires/{uuid}/sections/{sectionId}</code></td>
                            <td>Изтриване на секцията (и въпросите ѝ). Не се допуска при единствена секция — <code>422</code>.</td>
                        </tr>
                        <tr>
                            <td><span class="badge text-bg-warning text-dark">PATCH</span></td>
                            <td><code>/api/questionnaires/{uuid}/questions/{questionId}</code></td>
                            <td>За множествен избор: <code>body</code>, <code>choice_options</code> (масив от 4 низа), <code>correct_option</code> (0–3). За свободен отговор: само <code>body</code>. <code>questionId</code> — числов <code>id</code> от <code>GET …/build</code>.</td>
                        </tr>
                        <tr>
                            <td><span class="badge text-bg-danger">DELETE</span></td>
                            <td><code>/api/questionnaires/{uuid}/questions/{questionId}</code></td>
                            <td>Изтриване на въпрос.</td>
                        </tr>
                        <tr>
                            <td><span class="badge text-bg-secondary">POST</span></td>
                            <td><code>/api/questionnaires/{uuid}/generate-more</code></td>
                            <td><code>section_id</code> → още въпроси в секцията.</td>
                        </tr>
                        <tr>
                            <td><span class="badge text-bg-secondary">POST</span></td>
                            <td><code>/api/questionnaires/{uuid}/settings</code></td>
                            <td><code>points_per_correct</code>, <code>seconds_per_question</code> (по избор).</td>
                        </tr>
                        <tr>
                            <td><span class="badge text-bg-secondary">POST</span></td>
                            <td><code>/api/questionnaires/{uuid}/finish</code></td>
                            <td>Маркира анкетата като готова за попълване.</td>
                        </tr>
                        <tr>
                            <td><span class="badge text-bg-primary">GET</span></td>
                            <td><code>/api/questionnaires/{uuid}/results</code></td>
                            <td>Обобщени резултати по участници — <strong>само за собственика</strong> на анкетата.</td>
                        </tr>
                        <tr>
                            <td><span class="badge text-bg-primary">GET</span></td>
                            <td><code>/api/questionnaires/{uuid}/export-results</code></td>
                            <td>CSV експорт — <strong>само за собственика</strong>.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white py-3">
            <h2 class="h6 fw-semibold mb-0"><i class="bi bi-play-circle me-2"></i>Попълване и резултат от опит</h2>
        </div>
        <div class="card-body">
            <p class="small text-secondary mb-3">Стартира се опит за <strong>завършена</strong> анкета. Въпросите за попълване <strong>не съдържат</strong> верния отговор; след приключване детайлният преглед показва верни/грешни отговори за вашия опит.</p>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Метод</th>
                            <th>Път</th>
                            <th>Описание</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <tr>
                            <td><span class="badge text-bg-success">POST</span></td>
                            <td><code>/api/questionnaires/{uuid}/attempts</code></td>
                            <td>Създава нов опит; отговор съдържа въпросите без верни отговори.</td>
                        </tr>
                        <tr>
                            <td><span class="badge text-bg-primary">GET</span></td>
                            <td><code>/api/attempts/{attemptUuid}</code></td>
                            <td>Състояние на опит (ако не е завършен).</td>
                        </tr>
                        <tr>
                            <td><span class="badge text-bg-secondary">POST</span></td>
                            <td><code>/api/attempts/{attemptUuid}/answers</code></td>
                            <td>JSON: <code>answers</code> (ключ = id на въпрос), <code>mark_complete</code> (boolean). При <code>true</code> се изчисляват точките.</td>
                        </tr>
                        <tr>
                            <td><span class="badge text-bg-primary">GET</span></td>
                            <td><code>/api/attempts/{attemptUuid}/results</code></td>
                            <td>Резултат и разбивка (след завършен опит).</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-secondary-subtle mb-4">
        <div class="card-body small text-secondary">
            <p class="mb-2"><strong>Конфигурация на сървъра:</strong> за SPA с cookies към същия домейн може да се настрои <code>SANCTUM_STATEFUL_DOMAINS</code> в <code>.env</code>; за мобилни/външни клиенти обикновено се ползва само Bearer token.</p>
            <p class="mb-0">Пълна техническа документация за инсталация и средата: <a href="https://github.com/sasho-krist/questionnaire_ai/blob/main/README.md" class="link-primary" target="_blank" rel="noopener">README в GitHub</a>.</p>
        </div>
    </div>

    <div class="text-center">
        <a href="{{ route('faq') }}" class="btn btn-outline-secondary btn-sm me-2">
            <i class="bi bi-question-circle me-1"></i> ЧЗВ
        </a>
        <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Начало
        </a>
    </div>
@endsection
