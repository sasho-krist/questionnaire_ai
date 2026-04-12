<?php

namespace App\Http\Controllers;

use App\Models\Questionnaire;
use App\Models\QuestionnaireAttempt;
use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireSection;
use App\Services\OpenAiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuestionnaireController extends Controller
{
    public function __construct(
        private readonly OpenAiService $openAi
    ) {}

    public function index(Request $request): View
    {
        $query = Questionnaire::query()->with('user');

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

        $questionnaires = $query->latest()->paginate(15)->withQueryString();

        return view('questionnaires.index', compact('questionnaires'));
    }

    public function duplicate(Questionnaire $questionnaire): RedirectResponse
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

        return redirect()
            ->route($new->status === 'titles_ready' ? 'questionnaires.titles' : 'questionnaires.build', $new)
            ->with('status', 'Създадено е копие на анкетата.');
    }

    public function exportResults(Questionnaire $questionnaire): StreamedResponse|RedirectResponse
    {
        if ($questionnaire->status !== 'completed') {
            return redirect()
                ->route('questionnaires.index')
                ->withErrors(['export' => 'Експорт е възможен само за завършени анкети.']);
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

    /**
     * Редакция, настройки и изтриване — само за създателя на анкетата.
     */
    protected function authorizeOwnedQuestionnaire(Questionnaire $questionnaire): void
    {
        abort_unless(
            $questionnaire->user_id !== null && (int) $questionnaire->user_id === (int) auth()->id(),
            403,
            'Можете да променяте само анкети, които сте създали.'
        );
    }

    /**
     * Обобщени резултати от всички завършили опити — достъпно за всеки логнат потребител.
     */
    public function results(Questionnaire $questionnaire): View|RedirectResponse
    {
        if ($questionnaire->status !== 'completed') {
            return redirect()
                ->route('questionnaires.index')
                ->withErrors(['results' => 'Резултатите по участници са налични, когато анкетата е маркирана като завършена.']);
        }

        $attempts = QuestionnaireAttempt::query()
            ->where('questionnaire_id', $questionnaire->id)
            ->whereNotNull('completed_at')
            ->with('user')
            ->orderByDesc('score')
            ->orderByDesc('completed_at')
            ->get();

        return view('questionnaires.results-overview', compact('questionnaire', 'attempts'));
    }

    public function create(): View
    {
        return view('questionnaires.create');
    }

    public function store(Request $request): RedirectResponse
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
            return back()->withInput()->withErrors(['ai' => $e->getMessage()]);
        }

        $q = Questionnaire::query()->create([
            'user_id' => auth()->id(),
            'user_title' => $validated['user_title'],
            'topic_keywords' => $validated['topic_keywords'],
            'title_suggestions' => $titles,
            'status' => 'titles_ready',
        ]);

        return redirect()->route('questionnaires.titles', $q);
    }

    public function titles(Questionnaire $questionnaire): View|RedirectResponse
    {
        $this->authorizeOwnedQuestionnaire($questionnaire);

        if ($questionnaire->status === 'draft') {
            return redirect()->route('questionnaires.create');
        }
        if (in_array($questionnaire->status, ['building', 'completed'], true) && $questionnaire->chosen_title) {
            return redirect()->route('questionnaires.build', $questionnaire);
        }

        return view('questionnaires.titles', [
            'questionnaire' => $questionnaire,
            'titles' => $questionnaire->title_suggestions ?? [],
        ]);
    }

    public function selectTitle(Request $request, Questionnaire $questionnaire): RedirectResponse
    {
        $this->authorizeOwnedQuestionnaire($questionnaire);

        if ($questionnaire->status !== 'titles_ready') {
            if (in_array($questionnaire->status, ['building', 'completed'], true)) {
                return redirect()->route('questionnaires.build', $questionnaire);
            }

            return redirect()->route('questionnaires.create');
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
            return back()->withErrors(['ai' => $e->getMessage()]);
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

        return redirect()->route('questionnaires.build', $questionnaire);
    }

    public function build(Questionnaire $questionnaire): View|RedirectResponse
    {
        $this->authorizeOwnedQuestionnaire($questionnaire);

        if ($questionnaire->status === 'titles_ready' && ! $questionnaire->sections()->exists()) {
            return redirect()->route('questionnaires.titles', $questionnaire);
        }
        if ($questionnaire->status === 'draft') {
            return redirect()->route('questionnaires.create');
        }

        $questionnaire->load(['sections.questions']);

        return view('questionnaires.build', compact('questionnaire'));
    }

    public function generateMore(Request $request, Questionnaire $questionnaire): RedirectResponse
    {
        $this->authorizeOwnedQuestionnaire($questionnaire);

        if ($questionnaire->status !== 'building') {
            return redirect()->route('questionnaires.build', $questionnaire);
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
            return back()->withErrors(['ai' => $e->getMessage()]);
        }

        $startOrder = (($section->questions()->max('sort_order')) ?? -1) + 1;
        foreach ($newItems as $k => $item) {
            QuestionnaireQuestion::query()->create([
                'section_id' => $section->id,
                'sort_order' => $startOrder + $k,
                'body' => $item['body'],
                'choice_options' => $item['options'],
                'correct_option' => $item['correct_option'],
            ]);
        }

        return redirect()->route('questionnaires.build', $questionnaire);
    }

    public function updateSettings(Request $request, Questionnaire $questionnaire): RedirectResponse
    {
        $this->authorizeOwnedQuestionnaire($questionnaire);

        if (! in_array($questionnaire->status, ['building', 'completed'], true)) {
            return redirect()->route('questionnaires.build', $questionnaire);
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

        return redirect()->route('questionnaires.build', $questionnaire)
            ->with('status', 'Настройките на теста са запазени.');
    }

    public function finish(Questionnaire $questionnaire): RedirectResponse
    {
        $this->authorizeOwnedQuestionnaire($questionnaire);

        if ($questionnaire->status === 'completed') {
            return redirect()->route('questionnaires.index')
                ->with('status', 'Анкетата вече е маркирана като завършена.');
        }

        if ($questionnaire->status !== 'building') {
            return redirect()->route('questionnaires.build', $questionnaire);
        }

        $questionnaire->update(['status' => 'completed']);

        return redirect()->route('questionnaires.index')
            ->with('status', 'Анкетата е завършена и е готова за стартиране.');
    }

    public function destroy(Questionnaire $questionnaire): RedirectResponse
    {
        $this->authorizeOwnedQuestionnaire($questionnaire);

        $questionnaire->delete();

        return redirect()->route('questionnaires.index')
            ->with('status', 'Анкетата е изтрита.');
    }
}
