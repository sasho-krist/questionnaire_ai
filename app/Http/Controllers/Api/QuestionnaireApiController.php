<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Questionnaire;
use App\Models\QuestionnaireAttempt;
use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireSection;
use App\Services\OpenAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuestionnaireApiController extends Controller
{
    public function __construct(
        private readonly OpenAiService $openAi
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Questionnaire::query();

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($sub) use ($like): void {
                $sub->where('user_title', 'like', $like)
                    ->orWhere('chosen_title', 'like', $like)
                    ->orWhere('topic_keywords', 'like', $like);
            });
        }

        $status = $request->string('status')->toString();
        if ($status !== '' && in_array($status, ['draft', 'titles_ready', 'building', 'completed'], true)) {
            $query->where('status', $status);
        }

        $paginator = $query->latest()->paginate(15)->withQueryString();

        return response()->json($paginator->through(fn (Questionnaire $q) => $this->questionnaireSummary($q)));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_title' => ['required', 'string', 'max:255'],
            'topic_keywords' => ['required', 'string', 'max:2000'],
        ], [], [
            'user_title' => 'заглавие',
            'topic_keywords' => 'ключови думи',
        ]);

        try {
            $titles = $this->openAi->generateFiveTitles(
                $validated['user_title'],
                $validated['topic_keywords']
            );
            if (count($titles) < 5) {
                throw new RuntimeException('AI върна по-малко от 5 заглавия. Опитайте отново.');
            }
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $q = Questionnaire::query()->create([
            'user_id' => $request->user()->id,
            'user_title' => $validated['user_title'],
            'topic_keywords' => $validated['topic_keywords'],
            'title_suggestions' => $titles,
            'status' => 'titles_ready',
        ]);

        return response()->json([
            'questionnaire' => $this->questionnaireSummary($q->fresh()),
            'title_suggestions' => $titles,
        ], 201);
    }

    public function show(Questionnaire $questionnaire): JsonResponse
    {
        $this->authorizeOwnedQuestionnaire($questionnaire);

        return response()->json([
            'questionnaire' => $this->questionnaireSummary($questionnaire),
        ]);
    }

    public function titles(Questionnaire $questionnaire): JsonResponse
    {
        $this->authorizeOwnedQuestionnaire($questionnaire);

        if ($questionnaire->status === 'draft') {
            return response()->json(['message' => 'Анкетата е в чернова. Създайте я отначало.'], 409);
        }

        return response()->json([
            'questionnaire' => $this->questionnaireSummary($questionnaire),
            'titles' => $questionnaire->title_suggestions ?? [],
        ]);
    }

    public function selectTitle(Request $request, Questionnaire $questionnaire): JsonResponse
    {
        $this->authorizeOwnedQuestionnaire($questionnaire);

        if ($questionnaire->status !== 'titles_ready') {
            return response()->json(['message' => 'Избор на заглавие е възможен само при статус titles_ready.'], 409);
        }

        $suggestions = $questionnaire->title_suggestions ?? [];
        $validated = $request->validate([
            'chosen_title' => ['required', 'string', 'max:255', Rule::in($suggestions)],
        ], [], [
            'chosen_title' => 'избрано заглавие',
        ]);

        try {
            $blocks = $this->openAi->generateFourSectionsWithQuestions(
                $validated['chosen_title'],
                $questionnaire->user_title,
                $questionnaire->topic_keywords
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        DB::transaction(function () use ($questionnaire, $validated, $blocks): void {
            $sectionIds = $questionnaire->sections()->pluck('id');
            if ($sectionIds->isNotEmpty()) {
                QuestionnaireQuestion::query()->whereIn('section_id', $sectionIds)->delete();
            }
            $questionnaire->sections()->delete();

            $questionnaire->update([
                'chosen_title' => $validated['chosen_title'],
                'status' => 'building',
            ]);

            foreach ($blocks as $i => $block) {
                $section = QuestionnaireSection::query()->create([
                    'questionnaire_id' => $questionnaire->id,
                    'sort_order' => $i,
                    'title' => $block['title'],
                ]);
                foreach ($block['questions'] as $j => $item) {
                    QuestionnaireQuestion::query()->create([
                        'section_id' => $section->id,
                        'sort_order' => $j,
                        'body' => $item['body'],
                        'choice_options' => $item['options'],
                        'correct_option' => $item['correct_option'],
                    ]);
                }
            }
        });

        $questionnaire->load(['sections.questions']);

        return response()->json([
            'questionnaire' => $this->questionnaireBuilder($questionnaire->fresh(['sections.questions'])),
            'message' => 'Генерирани са секции и въпроси.',
        ]);
    }

    public function build(Questionnaire $questionnaire): JsonResponse
    {
        $this->authorizeOwnedQuestionnaire($questionnaire);

        if ($questionnaire->status === 'titles_ready' && ! $questionnaire->sections()->exists()) {
            return response()->json(['message' => 'Първо изберете заглавие.'], 409);
        }
        if ($questionnaire->status === 'draft') {
            return response()->json(['message' => 'Анкетата е в чернова.'], 409);
        }

        $questionnaire->load(['sections.questions']);

        return response()->json([
            'questionnaire' => $this->questionnaireBuilder($questionnaire),
        ]);
    }

    public function generateMore(Request $request, Questionnaire $questionnaire): JsonResponse
    {
        $this->authorizeOwnedQuestionnaire($questionnaire);

        if ($questionnaire->status !== 'building') {
            return response()->json(['message' => 'Генериране на още въпроси е възможно само при статус building.'], 409);
        }

        $validated = $request->validate([
            'section_id' => ['required', 'integer', 'exists:questionnaire_sections,id'],
        ]);

        $section = QuestionnaireSection::query()
            ->where('questionnaire_id', $questionnaire->id)
            ->whereKey($validated['section_id'])
            ->with('questions')
            ->firstOrFail();

        $existing = $section->questions->map(fn (QuestionnaireQuestion $q) => [
            'question' => $q->body,
            'options' => $q->choice_options ?? [],
            'correct_index' => $q->correct_option,
        ])->values()->all();

        try {
            $newItems = $this->openAi->generateMoreQuestionsForSection(
                $section->title,
                $existing,
                (string) $questionnaire->chosen_title
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $startOrder = (int) $section->questions()->max('sort_order') + 1;
        foreach ($newItems as $k => $item) {
            QuestionnaireQuestion::query()->create([
                'section_id' => $section->id,
                'sort_order' => $startOrder + $k,
                'body' => $item['body'],
                'choice_options' => $item['options'],
                'correct_option' => $item['correct_option'],
            ]);
        }

        $questionnaire->load(['sections.questions']);

        return response()->json([
            'questionnaire' => $this->questionnaireBuilder($questionnaire->fresh(['sections.questions'])),
            'message' => 'Добавени са нови въпроси.',
        ]);
    }

    public function updateSettings(Request $request, Questionnaire $questionnaire): JsonResponse
    {
        $this->authorizeOwnedQuestionnaire($questionnaire);

        if (! in_array($questionnaire->status, ['building', 'completed'], true)) {
            return response()->json(['message' => 'Настройките са налични при building или completed.'], 409);
        }

        $validated = $request->validate([
            'points_per_correct' => ['required', 'numeric', 'min:0.01', 'max:999.99'],
            'seconds_per_question' => ['nullable', 'integer', 'min:1', 'max:86400'],
        ], [], [
            'points_per_correct' => 'точки за верен отговор',
            'seconds_per_question' => 'секунди на въпрос',
        ]);

        $questionnaire->update([
            'points_per_correct' => $validated['points_per_correct'],
            'seconds_per_question' => $validated['seconds_per_question'] ?? null,
        ]);

        return response()->json([
            'questionnaire' => $this->questionnaireSummary($questionnaire->fresh()),
            'message' => 'Настройките са запазени.',
        ]);
    }

    public function finish(Questionnaire $questionnaire): JsonResponse
    {
        $this->authorizeOwnedQuestionnaire($questionnaire);

        if ($questionnaire->status === 'completed') {
            return response()->json([
                'questionnaire' => $this->questionnaireSummary($questionnaire),
                'message' => 'Анкетата вече е маркирана като завършена.',
            ]);
        }

        if ($questionnaire->status !== 'building') {
            return response()->json(['message' => 'Можете да завършите само анкета в статус building.'], 409);
        }

        $questionnaire->update(['status' => 'completed']);

        return response()->json([
            'questionnaire' => $this->questionnaireSummary($questionnaire->fresh()),
            'message' => 'Анкетата е завършена и е готова за попълване.',
        ]);
    }

    public function destroy(Questionnaire $questionnaire): JsonResponse
    {
        $this->authorizeOwnedQuestionnaire($questionnaire);
        $questionnaire->delete();

        return response()->json(['message' => 'Анкетата е изтрита.']);
    }

    public function duplicate(Questionnaire $questionnaire): JsonResponse
    {
        $this->authorizeOwnedQuestionnaire($questionnaire);

        $questionnaire->load(['sections.questions']);

        $new = DB::transaction(function () use ($questionnaire): Questionnaire {
            $copy = $questionnaire->replicate([
                'id',
                'uuid',
                'user_id',
                'created_at',
                'updated_at',
            ]);
            $copy->uuid = (string) Str::uuid();
            $copy->user_id = auth()->id();
            $copy->user_title = 'Копие — '.Str::limit($questionnaire->chosen_title ?? $questionnaire->user_title, 240);
            $copy->status = $questionnaire->sections->isEmpty()
                ? $questionnaire->status
                : 'building';
            $copy->save();

            foreach ($questionnaire->sections as $section) {
                $newSection = $copy->sections()->create([
                    'sort_order' => $section->sort_order,
                    'title' => $section->title,
                ]);
                foreach ($section->questions as $question) {
                    $newSection->questions()->create([
                        'sort_order' => $question->sort_order,
                        'body' => $question->body,
                        'choice_options' => $question->choice_options,
                        'correct_option' => $question->correct_option,
                    ]);
                }
            }

            return $copy->refresh();
        });

        $new->load(['sections.questions']);

        return response()->json([
            'questionnaire' => $this->questionnaireBuilder($new),
            'message' => 'Създадено е копие на анкетата.',
        ], 201);
    }

    public function resultsOverview(Questionnaire $questionnaire): JsonResponse
    {
        $this->authorizeOwnedQuestionnaire($questionnaire);

        if ($questionnaire->status !== 'completed') {
            return response()->json(['message' => 'Резултатите са налични, когато анкетата е завършена.'], 409);
        }

        $attempts = QuestionnaireAttempt::query()
            ->where('questionnaire_id', $questionnaire->id)
            ->whereNotNull('completed_at')
            ->with('user')
            ->orderByDesc('score')
            ->orderByDesc('completed_at')
            ->get();

        return response()->json([
            'questionnaire' => $this->questionnaireSummary($questionnaire),
            'attempts' => $attempts->map(fn (QuestionnaireAttempt $a) => [
                'uuid' => $a->uuid,
                'user' => [
                    'id' => $a->user_id,
                    'name' => $a->user?->name ?? 'Анонимен',
                    'email' => $a->user?->email ?? '',
                ],
                'score' => $a->score,
                'max_score' => $a->max_score,
                'completed_at' => $a->completed_at?->toIso8601String(),
            ]),
        ]);
    }

    public function exportResults(Questionnaire $questionnaire): StreamedResponse|JsonResponse
    {
        $this->authorizeOwnedQuestionnaire($questionnaire);

        if ($questionnaire->status !== 'completed') {
            return response()->json(['message' => 'Експорт е възможен само за завършени анкети.'], 409);
        }

        $attempts = QuestionnaireAttempt::query()
            ->where('questionnaire_id', $questionnaire->id)
            ->whereNotNull('completed_at')
            ->with('user')
            ->orderByDesc('completed_at')
            ->get();

        $title = Str::slug($questionnaire->chosen_title ?? $questionnaire->user_title, '-') ?: 'anketa';
        $filename = 'rezultati-'.$title.'-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($attempts): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Участник', 'Имейл', 'Точки', 'Макс', 'Завършен_UTC'], ';');
            foreach ($attempts as $a) {
                fputcsv($out, [
                    $a->user?->name ?? 'Анонимен',
                    $a->user?->email ?? '',
                    $a->score !== null ? (string) $a->score : '',
                    $a->max_score !== null ? (string) $a->max_score : '',
                    $a->completed_at?->toIso8601String() ?? '',
                ], ';');
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function authorizeOwnedQuestionnaire(Questionnaire $questionnaire): void
    {
        abort_unless(
            $questionnaire->user_id !== null && (int) $questionnaire->user_id === (int) auth()->id(),
            403,
            'Можете да променяте само анкети, които сте създали.'
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function questionnaireSummary(Questionnaire $q): array
    {
        return [
            'id' => $q->id,
            'uuid' => $q->uuid,
            'user_title' => $q->user_title,
            'topic_keywords' => $q->topic_keywords,
            'chosen_title' => $q->chosen_title,
            'status' => $q->status,
            'title_suggestions' => $q->title_suggestions,
            'points_per_correct' => $q->points_per_correct,
            'seconds_per_question' => $q->seconds_per_question,
            'created_at' => $q->created_at?->toIso8601String(),
            'updated_at' => $q->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function questionnaireBuilder(Questionnaire $q): array
    {
        $base = $this->questionnaireSummary($q);
        $base['sections'] = $q->sections->map(fn (QuestionnaireSection $s) => [
            'id' => $s->id,
            'sort_order' => $s->sort_order,
            'title' => $s->title,
            'questions' => $s->questions->map(fn (QuestionnaireQuestion $qq) => [
                'id' => $qq->id,
                'sort_order' => $qq->sort_order,
                'body' => $qq->body,
                'choice_options' => $qq->choice_options,
                'correct_option' => $qq->correct_option,
            ])->values()->all(),
        ])->values()->all();

        return $base;
    }
}
