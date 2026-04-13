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

    @if ($questionnaire->status === 'completed')
        @php
            $shareUrl = route('questionnaires.play.start', $questionnaire);
            $qrSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data='.rawurlencode($shareUrl);
        @endphp
        <div class="card mb-4 border-success border-2">
            <div class="card-header bg-success-subtle fw-semibold text-success-emphasis">
                <i class="bi bi-share me-2"></i>Споделяне и QR код
            </div>
            <div class="card-body">
                <p class="small text-secondary mb-3">Изпратете линка на участниците или покажете QR кода на екран/печат.</p>
                <div class="row g-3 align-items-center">
                    <div class="col-md">
                        <label for="share-url" class="form-label small mb-1">Публичен линк за стартиране</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control font-monospace small" id="share-url" readonly value="{{ $shareUrl }}">
                            <button type="button" class="btn btn-outline-primary" id="share-copy-btn" data-copy-target="share-url" title="Копирай">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-auto text-center">
                        <img src="{{ $qrSrc }}" width="180" height="180" class="img-fluid border rounded bg-white p-1" alt="QR код към анкетата" loading="lazy">
                        <div class="small text-muted mt-1">Сканирай за отваряне</div>
                    </div>
                </div>
            </div>
        </div>
        @push('scripts')
            <script>
                document.getElementById('share-copy-btn')?.addEventListener('click', function () {
                    var el = document.getElementById('share-url');
                    if (!el) return;
                    el.select();
                    el.setSelectionRange(0, 99999);
                    navigator.clipboard.writeText(el.value).then(function () {
                        var btn = document.getElementById('share-copy-btn');
                        if (btn) { btn.classList.remove('btn-outline-primary'); btn.classList.add('btn-success'); }
                        setTimeout(function () {
                            if (btn) { btn.classList.add('btn-outline-primary'); btn.classList.remove('btn-success'); }
                        }, 1200);
                    });
                });
            </script>
        @endpush
    @endif

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
                    <div class="flex-grow-1 min-w-0">
                        @if (in_array($questionnaire->status, ['building', 'completed'], true))
                            <form method="post" action="{{ route('questionnaires.section.update', [$questionnaire, $section]) }}" class="row g-2 align-items-end">
                                @csrf
                                @method('PATCH')
                                <div class="col-12 col-lg">
                                    <label class="form-label small text-secondary mb-1" for="section-title-{{ $section->id }}">Заглавие на секцията</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-primary-subtle text-primary border-0"><i class="bi bi-folder2-open"></i></span>
                                        <input type="text" name="section_title_{{ $section->id }}" id="section-title-{{ $section->id }}" class="form-control"
                                               value="{{ old('section_title_'.$section->id, $section->title) }}" maxlength="255" required>
                                        <button type="submit" class="btn btn-primary">Запази</button>
                                    </div>
                                </div>
                            </form>
                        @else
                            <h2 class="h5 fw-semibold mb-0 text-primary">
                                <i class="bi bi-folder2-open me-2 opacity-75"></i>{{ $section->title }}
                            </h2>
                        @endif
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        @if ($questionnaire->status === 'building')
                            <form method="post" action="{{ route('questionnaires.generate-more', $questionnaire) }}" class="m-0">
                                @csrf
                                <input type="hidden" name="section_id" value="{{ $section->id }}">
                                <button type="submit" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-plus-circle me-1"></i> Още въпроси
                                </button>
                            </form>
                        @endif
                        @if (in_array($questionnaire->status, ['building', 'completed'], true))
                            @if ($questionnaire->sections->count() > 1)
                                <form method="post" action="{{ route('questionnaires.section.destroy', [$questionnaire, $section]) }}" class="m-0"
                                      onsubmit="return confirm('Изтриване на цялата секция и всички нейни въпроси?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-trash me-1"></i> Изтрий секцията
                                    </button>
                                </form>
                            @endif
                        @endif
                    </div>
                </div>
                <div class="card-body pt-0">
                    <ol class="mb-0 ps-3">
                        @foreach ($section->questions as $question)
                            <li class="mb-3 text-secondary">
                                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-start">
                                    <div class="flex-grow-1 min-w-0">
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
                                    </div>
                                    @if (in_array($questionnaire->status, ['building', 'completed'], true))
                                        <div class="d-flex flex-shrink-0 gap-1">
                                            @php
                                                $editQuestionPayload = json_encode([
                                                    'body' => $question->body,
                                                    'options' => $question->choice_options ?? [],
                                                    'correct' => $question->correct_option,
                                                    'mc' => $question->hasMultipleChoice(),
                                                    'section_title' => $section->title,
                                                ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                                            @endphp
                                            <button type="button" class="btn btn-sm btn-outline-secondary edit-question-btn"
                                                    data-bs-toggle="modal" data-bs-target="#editQuestionModal"
                                                    data-update-url="{{ route('questionnaires.question.update', [$questionnaire, $question]) }}"
                                                    data-payload-b64="{{ base64_encode($editQuestionPayload) }}">
                                                <i class="bi bi-pencil"></i><span class="d-none d-sm-inline ms-1">Редактирай</span>
                                            </button>
                                            <form method="post" action="{{ route('questionnaires.question.destroy', [$questionnaire, $question]) }}" class="m-0"
                                                  onsubmit="return confirm('Изтриване на този въпрос?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i><span class="d-none d-sm-inline ms-1">Изтрий</span>
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        @endforeach
    </div>

    @if (in_array($questionnaire->status, ['building', 'completed'], true))
        <div class="modal fade" id="editQuestionModal" tabindex="-1" aria-labelledby="editQuestionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <form id="editQuestionForm" method="post" action="">
                        @csrf
                        @method('PATCH')
                        <div class="modal-header">
                            <h2 class="modal-title h5" id="editQuestionModalLabel">Редактиране на въпрос</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Затвори"></button>
                        </div>
                        <div class="modal-body">
                            <div id="edit_q_section_wrap" class="mb-3 pb-2 border-bottom border-secondary border-opacity-25 d-none">
                                <div class="small text-secondary mb-1">Секция</div>
                                <div id="edit_q_section_title" class="fw-semibold text-body"></div>
                            </div>
                            <div class="mb-3">
                                <label for="edit_q_body" class="form-label">Текст на въпроса</label>
                                <textarea name="body" id="edit_q_body" class="form-control" rows="3" required></textarea>
                            </div>
                            <div id="edit_q_mc_wrap" class="d-none">
                                <p class="small text-secondary">Четири варианта на отговор и маркиране на верния (за автоматично точкуване).</p>
                                @for ($i = 0; $i < 4; $i++)
                                    <div class="mb-2">
                                        <label for="edit_q_opt{{ $i }}" class="form-label small">Опция {{ $i + 1 }}</label>
                                        <input type="text" name="choice_options[{{ $i }}]" id="edit_q_opt{{ $i }}" class="form-control form-control-sm" maxlength="2000">
                                    </div>
                                @endfor
                                <div class="mb-0">
                                    <label for="edit_q_correct" class="form-label small">Верен отговор</label>
                                    <select name="correct_option" id="edit_q_correct" class="form-select form-select-sm">
                                        <option value="0">Опция 1</option>
                                        <option value="1">Опция 2</option>
                                        <option value="2">Опция 3</option>
                                        <option value="3">Опция 4</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отказ</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check2 me-1"></i>Запази въпроса
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @push('scripts')
            <script>
                (function () {
                    var LOG = '[questionnaire-edit]';

                    function parsePayloadB64(b64) {
                        var binary = atob(b64);
                        var bytes = new Uint8Array(binary.length);
                        for (var i = 0; i < binary.length; i++) {
                            bytes[i] = binary.charCodeAt(i);
                        }
                        var json = new TextDecoder('utf-8').decode(bytes);
                        return JSON.parse(json);
                    }

                    var modal = document.getElementById('editQuestionModal');
                    if (!modal) {
                        console.warn(LOG, 'modal #editQuestionModal not found');
                        return;
                    }

                    var pendingUrl = null;
                    var pendingB64 = null;

                    /* pointerdown fires before click — Bootstrap often handles click first and opens the modal
                       before a click-capture handler can set pendingUrl/pendingB64 */
                    document.addEventListener('pointerdown', function (e) {
                        if (!e.target || !e.target.closest) return;
                        var btn = e.target.closest('.edit-question-btn');
                        if (!btn) return;
                        pendingUrl = btn.getAttribute('data-update-url');
                        pendingB64 = btn.getAttribute('data-payload-b64');
                        console.log(LOG, 'pointerdown on edit button', {
                            url: pendingUrl ? pendingUrl.slice(0, 80) + (pendingUrl.length > 80 ? '…' : '') : null,
                            b64Length: pendingB64 ? pendingB64.length : 0,
                        });
                    }, true);

                    modal.addEventListener('show.bs.modal', function (event) {
                        var form = document.getElementById('editQuestionForm');
                        var url = pendingUrl;
                        var b64 = pendingB64;

                        if ((!url || !b64) && event.relatedTarget && event.relatedTarget.closest) {
                            var fb = event.relatedTarget.closest('.edit-question-btn');
                            if (fb) {
                                url = fb.getAttribute('data-update-url');
                                b64 = fb.getAttribute('data-payload-b64');
                                console.log(LOG, 'fallback: data from event.relatedTarget');
                            }
                        }

                        console.log(LOG, 'show.bs.modal', {
                            hasForm: !!form,
                            pendingUrl: !!pendingUrl,
                            resolvedUrl: !!url,
                            pendingB64Len: b64 ? b64.length : 0,
                            relatedTarget: event.relatedTarget ? event.relatedTarget.className : null,
                        });

                        if (!form || !url || !b64) {
                            console.warn(LOG, 'missing form/url/payload — modal opened without prefilled pending data', {
                                form: !!form,
                                url: !!url,
                                b64: !!b64,
                            });
                            return;
                        }

                        pendingUrl = null;
                        pendingB64 = null;
                        form.setAttribute('action', url);
                        var p;
                        try {
                            p = parsePayloadB64(b64);
                        } catch (err) {
                            console.error(LOG, 'parsePayloadB64 failed', err);
                            return;
                        }

                        console.log(LOG, 'parsed payload keys', Object.keys(p), 'bodyLen', p.body != null ? String(p.body).length : 0);
                        var sectionWrap = document.getElementById('edit_q_section_wrap');
                        var sectionEl = document.getElementById('edit_q_section_title');
                        if (sectionWrap && sectionEl) {
                            var st = p.section_title;
                            if (st) {
                                sectionEl.textContent = st;
                                sectionWrap.classList.remove('d-none');
                            } else {
                                sectionEl.textContent = '';
                                sectionWrap.classList.add('d-none');
                            }
                        }
                        var bodyEl = document.getElementById('edit_q_body');
                        if (bodyEl) bodyEl.value = p.body != null ? String(p.body) : '';
                        console.log(LOG, 'textarea filled, value length', bodyEl ? bodyEl.value.length : -1);
                        var wrap = document.getElementById('edit_q_mc_wrap');
                        function setMcDisabled(disabled) {
                            for (var k = 0; k < 4; k++) {
                                var opt = document.getElementById('edit_q_opt' + k);
                                if (opt) {
                                    opt.disabled = disabled;
                                    if (disabled) opt.removeAttribute('required');
                                }
                            }
                            var selMc = document.getElementById('edit_q_correct');
                            if (selMc) {
                                selMc.disabled = disabled;
                                if (disabled) selMc.removeAttribute('required');
                            }
                        }
                        if (p.mc && p.options && p.options.length === 4) {
                            wrap.classList.remove('d-none');
                            setMcDisabled(false);
                            for (var i = 0; i < 4; i++) {
                                var inp = document.getElementById('edit_q_opt' + i);
                                if (inp) {
                                    inp.value = p.options[i] != null ? String(p.options[i]) : '';
                                    inp.required = true;
                                }
                            }
                            var sel = document.getElementById('edit_q_correct');
                            if (sel) {
                                sel.value = String(p.correct != null ? p.correct : 0);
                                sel.required = true;
                            }
                        } else {
                            wrap.classList.add('d-none');
                            for (var j = 0; j < 4; j++) {
                                var inp2 = document.getElementById('edit_q_opt' + j);
                                if (inp2) inp2.value = '';
                            }
                            setMcDisabled(true);
                        }
                    });
                })();
            </script>
        @endpush
    @endif

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
