<?php

namespace App\Http\Controllers;

use App\Models\Questionnaire;
use App\Models\QuestionnaireAttempt;
use App\Models\QuestionnaireQuestion;
use App\Services\AttemptScoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuestionnairePlayController extends Controller
{
    public function __construct(
        private readonly AttemptScoringService $scoring
    ) {}

    public function start(Questionnaire $questionnaire): RedirectResponse
    {
        if ($questionnaire->status !== 'completed') {
            return redirect()->route('questionnaires.index')
                ->withErrors(['play' => 'Анкетата още не е завършена за попълване.']);
        }

        $questionnaire->load('sections.questions');
        $questionCount = $this->scoring->countAllQuestions($questionnaire);
        $deadline = null;
        if ($questionnaire->seconds_per_question && $questionCount > 0) {
            $deadline = now()->addSeconds($questionnaire->seconds_per_question * $questionCount);
        }

        $attempt = QuestionnaireAttempt::query()->create([
            'questionnaire_id' => $questionnaire->id,
            'answers' => [],
            'started_at' => now(),
            'deadline_at' => $deadline,
        ]);

        return redirect()->route('questionnaires.play.show', $attempt);
    }

    public function show(QuestionnaireAttempt $questionnaireAttempt): View|RedirectResponse
    {
        if ($questionnaireAttempt->completed_at) {
            return redirect()->route('questionnaires.play.results', $questionnaireAttempt);
        }

        $questionnaireAttempt->load(['questionnaire.sections.questions']);

        return view('questionnaires.play', [
            'attempt' => $questionnaireAttempt,
            'questionnaire' => $questionnaireAttempt->questionnaire,
        ]);
    }

    public function results(QuestionnaireAttempt $questionnaireAttempt): View|RedirectResponse
    {
        if (! $questionnaireAttempt->completed_at) {
            return redirect()->route('questionnaires.play.show', $questionnaireAttempt);
        }

        $questionnaireAttempt->load(['questionnaire.sections.questions']);

        return view('questionnaires.results', [
            'attempt' => $questionnaireAttempt,
            'questionnaire' => $questionnaireAttempt->questionnaire,
        ]);
    }

    public function saveAnswers(Request $request, QuestionnaireAttempt $questionnaireAttempt): RedirectResponse
    {
        if ($questionnaireAttempt->completed_at) {
            return redirect()->route('questionnaires.play.results', $questionnaireAttempt);
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

        $validated = $request->validate($rules);
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

        if ($request->boolean('mark_complete')) {
            return redirect()->route('questionnaires.play.results', $questionnaireAttempt)
                ->with('status', 'Тестът е завършен.');
        }

        return redirect()->route('questionnaires.play.show', $questionnaireAttempt)
            ->with('status', 'Отговорите са записани.');
    }
}
