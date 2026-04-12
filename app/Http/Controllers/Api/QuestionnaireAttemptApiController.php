<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Questionnaire;
use App\Models\QuestionnaireAttempt;
use App\Models\QuestionnaireQuestion;
use App\Services\AttemptScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuestionnaireAttemptApiController extends Controller
{
    public function __construct(
        private readonly AttemptScoringService $scoring
    ) {}

    public function start(Questionnaire $questionnaire): JsonResponse
    {
        if ($questionnaire->status !== 'completed') {
            return response()->json(['message' => 'Анкетата още не е завършена за попълване.'], 409);
        }

        $questionnaire->load(['sections.questions']);
        $questionCount = $this->scoring->countAllQuestions($questionnaire);
        $deadline = null;
        if ($questionnaire->seconds_per_question && $questionCount > 0) {
            $deadline = now()->addSeconds($questionnaire->seconds_per_question * $questionCount);
        }

        $attempt = QuestionnaireAttempt::query()->create([
            'questionnaire_id' => $questionnaire->id,
            'user_id' => auth()->id(),
            'answers' => [],
            'started_at' => now(),
            'deadline_at' => $deadline,
        ]);

        return response()->json(
            $this->playPayload($attempt->fresh(), $questionnaire),
            201
        );
    }

    public function show(QuestionnaireAttempt $questionnaireAttempt): JsonResponse
    {
        $this->authorizeAttempt($questionnaireAttempt);

        if ($questionnaireAttempt->completed_at) {
            return response()->json(['message' => 'Опитът е завършен. Ползвайте GET /api/attempts/{uuid}/results'], 409);
        }

        $questionnaireAttempt->load(['questionnaire.sections.questions']);
        $q = $questionnaireAttempt->questionnaire;

        return response()->json($this->playPayload($questionnaireAttempt, $q));
    }

    public function saveAnswers(Request $request, QuestionnaireAttempt $questionnaireAttempt): JsonResponse
    {
        $this->authorizeAttempt($questionnaireAttempt);

        if ($questionnaireAttempt->completed_at) {
            return response()->json(['message' => 'Опитът вече е завършен.'], 409);
        }

        $questionnaireAttempt->load(['questionnaire.sections.questions']);
        $questionnaire = $questionnaireAttempt->questionnaire;

        $ids = $questionnaire->sections
            ->flatMap(fn ($s) => $s->questions->pluck('id'))
            ->all();

        $rules = [];
        $questionsById = $questionnaire->sections
            ->flatMap(fn ($s) => $s->questions)
            ->keyBy('id');

        foreach ($ids as $id) {
            /** @var QuestionnaireQuestion|null $question */
            $question = $questionsById->get($id);
            if ($question instanceof QuestionnaireQuestion && $question->hasMultipleChoice()) {
                $rules['answers.'.$id] = ['nullable', 'integer', Rule::in([0, 1, 2, 3])];
            } else {
                $rules['answers.'.$id] = ['nullable', 'string', 'max:10000'];
            }
        }

        $validated = $request->validate(array_merge([
            'mark_complete' => ['sometimes', 'boolean'],
        ], $rules));

        $answers = $validated['answers'] ?? [];
        $clean = [];
        foreach ($answers as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $qid = (int) $key;
            $question = $questionsById->get($qid);
            if ($question instanceof QuestionnaireQuestion && $question->hasMultipleChoice()) {
                $clean[$qid] = (int) $value;

                continue;
            }
            if (is_string($value) && trim($value) !== '') {
                $clean[$qid] = trim($value);
            }
        }

        $existing = $questionnaireAttempt->answers ?? [];
        if (! is_array($existing)) {
            $existing = [];
        }
        $merged = [];
        foreach ($existing as $key => $value) {
            $merged[(int) $key] = $value;
        }
        foreach ($clean as $key => $value) {
            $merged[(int) $key] = $value;
        }

        $payload = ['answers' => $merged];

        if ($request->boolean('mark_complete')) {
            $result = $this->scoring->compute($questionnaire, $merged);
            $payload['completed_at'] = now();
            $payload['score'] = $result['score'];
            $payload['max_score'] = $result['max_score'];
        }

        $questionnaireAttempt->update($payload);
        $questionnaireAttempt->refresh();

        if ($request->boolean('mark_complete')) {
            $questionnaire->load(['sections.questions']);

            return response()->json([
                'message' => 'Тестът е завършен.',
                'results' => $this->resultsPayload($questionnaireAttempt, $questionnaire),
            ]);
        }

        $questionnaire->load(['sections.questions']);

        return response()->json([
            'message' => 'Отговорите са записани.',
            'attempt' => $this->attemptMeta($questionnaireAttempt),
            'play' => $this->questionnaireForPlay($questionnaire),
        ]);
    }

    public function results(QuestionnaireAttempt $questionnaireAttempt): JsonResponse
    {
        $this->authorizeAttempt($questionnaireAttempt);

        if (! $questionnaireAttempt->completed_at) {
            return response()->json(['message' => 'Опитът още не е завършен.'], 409);
        }

        $questionnaireAttempt->load(['questionnaire.sections.questions']);
        $questionnaire = $questionnaireAttempt->questionnaire;
        $questionnaire->load(['sections.questions']);

        return response()->json($this->resultsPayload($questionnaireAttempt, $questionnaire));
    }

    protected function authorizeAttempt(QuestionnaireAttempt $attempt): void
    {
        abort_unless(
            $attempt->user_id !== null && (int) $attempt->user_id === (int) auth()->id(),
            403,
            'Нямате достъп до този опит.'
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function playPayload(QuestionnaireAttempt $attempt, Questionnaire $questionnaire): array
    {
        return [
            'attempt' => $this->attemptMeta($attempt),
            'questionnaire' => $this->questionnaireForPlay($questionnaire),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function attemptMeta(QuestionnaireAttempt $attempt): array
    {
        return [
            'uuid' => $attempt->uuid,
            'questionnaire_id' => $attempt->questionnaire_id,
            'answers' => $attempt->answers ?? [],
            'started_at' => $attempt->started_at?->toIso8601String(),
            'deadline_at' => $attempt->deadline_at?->toIso8601String(),
            'completed_at' => $attempt->completed_at?->toIso8601String(),
            'score' => $attempt->score,
            'max_score' => $attempt->max_score,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function questionnaireForPlay(Questionnaire $questionnaire): array
    {
        return [
            'uuid' => $questionnaire->uuid,
            'chosen_title' => $questionnaire->chosen_title,
            'points_per_correct' => $questionnaire->points_per_correct,
            'seconds_per_question' => $questionnaire->seconds_per_question,
            'sections' => $questionnaire->sections->map(fn ($s) => [
                'id' => $s->id,
                'title' => $s->title,
                'questions' => $s->questions->map(function (QuestionnaireQuestion $qq) {
                    $row = [
                        'id' => $qq->id,
                        'body' => $qq->body,
                        'choice_options' => $qq->choice_options,
                    ];
                    if (! $qq->hasMultipleChoice()) {
                        $row['type'] = 'text';
                    } else {
                        $row['type'] = 'multiple_choice';
                    }

                    return $row;
                })->values()->all(),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function resultsPayload(QuestionnaireAttempt $attempt, Questionnaire $questionnaire): array
    {
        $answers = $attempt->answers ?? [];

        $sections = $questionnaire->sections->map(function ($section) use ($answers, $questionnaire) {
            return [
                'id' => $section->id,
                'title' => $section->title,
                'questions' => $section->questions->map(function (QuestionnaireQuestion $question) use ($answers, $questionnaire) {
                    $userRaw = $answers[$question->id] ?? $answers[(string) $question->id] ?? null;
                    $entry = [
                        'id' => $question->id,
                        'body' => $question->body,
                        'user_answer' => $userRaw,
                    ];

                    if ($question->isScoredMultipleChoice()) {
                        $userIdx = $userRaw !== null && $userRaw !== '' ? (int) $userRaw : null;
                        $correctIdx = (int) $question->correct_option;
                        $isCorrect = $userIdx !== null && $userIdx === $correctIdx;
                        $entry['scored'] = true;
                        $entry['is_correct'] = $isCorrect;
                        $entry['correct_option_index'] = $correctIdx;
                        $entry['user_option_index'] = $userIdx;
                        $entry['user_option_label'] = $userIdx !== null
                            ? ($question->choice_options[$userIdx] ?? null)
                            : null;
                        $entry['correct_option_label'] = $question->choice_options[$correctIdx] ?? null;
                        $entry['points_value'] = (float) $questionnaire->points_per_correct;
                    } elseif ($question->hasMultipleChoice()) {
                        $userIdx = $userRaw !== null && $userRaw !== '' ? (int) $userRaw : null;
                        $entry['scored'] = false;
                        $entry['user_option_index'] = $userIdx;
                        $entry['user_option_label'] = $userIdx !== null
                            ? ($question->choice_options[$userIdx] ?? null)
                            : null;
                    } else {
                        $entry['type'] = 'text';
                        $entry['user_text'] = is_string($userRaw) ? $userRaw : null;
                    }

                    return $entry;
                })->values()->all(),
            ];
        })->values()->all();

        return [
            'attempt' => $this->attemptMeta($attempt),
            'questionnaire' => [
                'uuid' => $questionnaire->uuid,
                'chosen_title' => $questionnaire->chosen_title,
                'points_per_correct' => $questionnaire->points_per_correct,
                'seconds_per_question' => $questionnaire->seconds_per_question,
            ],
            'sections' => $sections,
        ];
    }
}
