<?php

namespace App\Services;

use App\Models\Questionnaire;
use App\Models\QuestionnaireQuestion;

class AttemptScoringService
{
    /**
     * @param  array<int|string, mixed>  $answers
     * @return array{score: float, max_score: float, correct_count: int, total_scored: int}
     */
    public function compute(Questionnaire $questionnaire, array $answers): array
    {
        $pointsPer = (float) $questionnaire->points_per_correct;
        $score = 0.0;
        $maxScore = 0.0;
        $correctCount = 0;
        $totalScored = 0;

        foreach ($questionnaire->sections as $section) {
            foreach ($section->questions as $question) {
                if (! $question instanceof QuestionnaireQuestion || ! $question->isScoredMultipleChoice()) {
                    continue;
                }
                $totalScored++;
                $maxScore += $pointsPer;
                $given = $answers[$question->id] ?? $answers[(string) $question->id] ?? null;
                if ($given === null || $given === '') {
                    continue;
                }
                if ((int) $given === (int) $question->correct_option) {
                    $score += $pointsPer;
                    $correctCount++;
                }
            }
        }

        return [
            'score' => round($score, 2),
            'max_score' => round($maxScore, 2),
            'correct_count' => $correctCount,
            'total_scored' => $totalScored,
        ];
    }

    public function countAllQuestions(Questionnaire $questionnaire): int
    {
        return (int) $questionnaire->sections->sum(fn ($s) => $s->questions->count());
    }
}
