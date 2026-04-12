<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiService
{
    public function generateFiveTitles(string $userTitle, string $keywords): array
    {
        $system = 'You help design questionnaires. Always return valid JSON only.';
        $userPrompt = <<<TXT
The user is creating a questionnaire in Bulgarian.
Working title: {$userTitle}
Topic keywords: {$keywords}

Return a JSON object: {"titles":["...","...","...","...","..."]}
Exactly 5 different, clear questionnaire titles in Bulgarian. Each under 120 characters.
TXT;

        $data = $this->chatJson($system, $userPrompt);
        if (! isset($data['titles']) || ! is_array($data['titles'])) {
            throw new RuntimeException('Невалиден отговор от AI за заглавия.');
        }
        $titles = array_values(array_filter(array_map('trim', $data['titles'])));

        return array_slice($titles, 0, 5);
    }

    /**
     * @return list<array{title: string, questions: list<array{body: string, options: list<string>, correct_option: int}>}>
     */
    public function generateFourSectionsWithQuestions(string $chosenTitle, string $userTitle, string $keywords): array
    {
        $system = 'You help design questionnaires in Bulgarian. Always return valid JSON only. Every multiple-choice question MUST include correct_index: an integer 0, 1, 2, or 3 for the only correct option.';
        $userPrompt = <<<TXT
Final questionnaire title: {$chosenTitle}
Original working title: {$userTitle}
Keywords: {$keywords}

Create exactly 4 sections. Each section has exactly 4 multiple-choice questions (Bulgarian).
Each question object MUST have:
- "body": the question text
- "options": array of exactly 4 answer strings
- "correct_index": integer 0–3 (which option is correct; 0 = first option in "options")

Return JSON:
{"sections":[
  {"title":"Section title BG","questions":[
    {"body":"Question?","options":["A","B","C","D"],"correct_index":0},
    {"body":"...?","options":["...","...","...","..."],"correct_index":2}
  ]},
  ...
]}
TXT;

        $data = $this->chatJson($system, $userPrompt, 0.55);
        if (! isset($data['sections']) || ! is_array($data['sections'])) {
            throw new RuntimeException('Невалиден отговор от AI за секции и въпроси.');
        }

        $sections = [];
        foreach ($data['sections'] as $block) {
            if (! is_array($block) || empty($block['title']) || empty($block['questions']) || ! is_array($block['questions'])) {
                continue;
            }
            $parsedQuestions = [];
            foreach ($block['questions'] as $rawQ) {
                $parsed = $this->parseQuestionStructured($rawQ);
                if ($parsed !== null) {
                    $parsedQuestions[] = $parsed;
                }
            }
            if (count($parsedQuestions) < 4) {
                continue;
            }
            $parsedQuestions = array_slice($parsedQuestions, 0, 4);
            $parsedQuestions = $this->ensureQuestionsHaveCorrectIndices($parsedQuestions);
            $sections[] = [
                'title' => trim((string) $block['title']),
                'questions' => $parsedQuestions,
            ];
        }

        if (count($sections) < 4) {
            throw new RuntimeException('AI не върна 4 валидни секции с по 4 въпроса и опции.');
        }

        return array_slice($sections, 0, 4);
    }

    /**
     * @param  list<array{question: string, options: list<string>, correct_index?: int|null}>  $existingQuestions
     * @return list<array{body: string, options: list<string>, correct_option: int}>
     */
    public function generateMoreQuestionsForSection(
        string $sectionTitle,
        array $existingQuestions,
        string $questionnaireTitle
    ): array {
        $existing = json_encode($existingQuestions, JSON_UNESCAPED_UNICODE);
        $system = 'You help design questionnaires in Bulgarian. Always return valid JSON only. Each question MUST include correct_index 0–3.';
        $userPrompt = <<<TXT
Questionnaire title: {$questionnaireTitle}
Section title: {$sectionTitle}
Existing questions (with options and correct_index): {$existing}

Add exactly 4 NEW multiple-choice questions in Bulgarian (not duplicates).
Each: "body", "options" (4 strings), "correct_index" (0–3).

Return JSON:
{"questions":[
  {"body":"...?","options":["...","...","...","..."],"correct_index":2},
  ...
]}
TXT;

        $data = $this->chatJson($system, $userPrompt, 0.55);
        if (! isset($data['questions']) || ! is_array($data['questions'])) {
            throw new RuntimeException('Невалиден отговор от AI за допълнителни въпроси.');
        }

        $out = [];
        foreach ($data['questions'] as $rawQ) {
            $parsed = $this->parseQuestionStructured($rawQ);
            if ($parsed !== null) {
                $out[] = $parsed;
            }
        }

        if (count($out) < 4) {
            throw new RuntimeException('AI не върна 4 валидни въпроса с опции.');
        }

        $out = array_slice($out, 0, 4);

        return $this->ensureQuestionsHaveCorrectIndices($out);
    }

    /**
     * Винаги пита AI кой индекс е верен за всяка група от 4 въпроса (надеждно записване в БД).
     *
     * @param  list<array{body: string, options: list<string>, correct_option?: int|null}>  $questions
     * @return list<array{body: string, options: list<string>, correct_option: int}>
     */
    private function ensureQuestionsHaveCorrectIndices(array $questions): array
    {
        $indices = $this->fetchCorrectIndicesFromModel($questions);

        foreach ($questions as $i => &$q) {
            if (! isset($indices[$i]) || ! is_int($indices[$i]) || $indices[$i] < 0 || $indices[$i] > 3) {
                throw new RuntimeException('AI не върна валиден индекс на верен отговор за всички въпроси.');
            }
            $q['correct_option'] = $indices[$i];
        }
        unset($q);

        /** @var list<array{body: string, options: list<string>, correct_option: int}> */
        return $questions;
    }

    /**
     * @param  list<array{body: string, options: list<string>, correct_option?: int|null}>  $questions
     * @return list<int>
     */
    private function fetchCorrectIndicesFromModel(array $questions): array
    {
        $batch = [];
        foreach ($questions as $q) {
            $batch[] = [
                'body' => $q['body'],
                'options' => $q['options'],
            ];
        }
        $enc = json_encode($batch, JSON_UNESCAPED_UNICODE);
        $n = count($questions);
        $system = 'You are an expert evaluator. Respond only with valid JSON. Use factual accuracy.';
        $userPrompt = <<<TXT
There are exactly {$n} multiple-choice questions in order (Bulgarian).
For EACH question, decide which single option index is correct: 0 = first option, 1 = second, 2 = third, 3 = fourth.

Questions JSON: {$enc}

Return only: {"correct_indices":[n0,n1,...]} — same length as questions, each n is 0, 1, 2, or 3.
TXT;

        $data = $this->chatJson($system, $userPrompt, 0.2);
        if (! isset($data['correct_indices']) || ! is_array($data['correct_indices'])) {
            throw new RuntimeException('AI не върна correct_indices за верните отговори.');
        }
        $indices = array_values(array_map('intval', $data['correct_indices']));
        if (count($indices) !== count($questions)) {
            throw new RuntimeException('Броят correct_indices не съвпада с броя въпроси.');
        }
        foreach ($indices as $v) {
            if ($v < 0 || $v > 3) {
                throw new RuntimeException('Невалиден индекс в correct_indices.');
            }
        }

        return $indices;
    }

    /**
     * @return array<string, mixed>
     */
    private function chatJson(string $system, string $userMessage, float $temperature = 0.65): array
    {
        $key = config('services.openai.key');
        if (empty($key)) {
            throw new RuntimeException('Липсва AI_API_PUBLIC_KEY в .env файла.');
        }

        $model = (string) config('services.openai.model');

        $response = $this->openAiHttp()
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => $temperature,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI API грешка: '.$response->body());
        }

        $content = $response->json('choices.0.message.content');
        if (! is_string($content) || $content === '') {
            throw new RuntimeException('Празен отговор от AI.');
        }

        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('AI не върна валиден JSON.');
        }

        return $decoded;
    }

    private function openAiHttp(): PendingRequest
    {
        $key = (string) config('services.openai.key');
        $request = Http::withToken($key)->timeout(120);

        if (! config('services.openai.verify_ssl')) {
            $request = $request->withOptions(['verify' => false]);
        }

        return $request;
    }

    /**
     * @return array{body: string, options: list<string>, correct_option: int|null}|null
     */
    private function parseQuestionStructured(mixed $raw): ?array
    {
        if (! is_array($raw)) {
            return null;
        }
        $body = trim((string) ($raw['body'] ?? $raw['question'] ?? $raw['text'] ?? ''));
        $rawOpts = $raw['options'] ?? $raw['choices'] ?? $raw['answers'] ?? [];
        if (! is_array($rawOpts) || $body === '') {
            return null;
        }
        $options = array_values(array_filter(array_map('trim', $rawOpts), fn (mixed $o): bool => is_string($o) && $o !== ''));
        if (count($options) < 4) {
            return null;
        }
        $options = array_slice($options, 0, 4);

        $correct = $this->extractCorrectIndex($raw);

        return [
            'body' => $body,
            'options' => $options,
            'correct_option' => $correct,
        ];
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private function extractCorrectIndex(array $raw): ?int
    {
        $candidates = [
            $raw['correct_index'] ?? null,
            $raw['correct_option'] ?? null,
            $raw['correct_answer_index'] ?? null,
            is_array($raw['correct'] ?? null) ? ($raw['correct']['index'] ?? null) : null,
            $raw['answer_index'] ?? null,
        ];
        foreach ($candidates as $idx) {
            if ($idx === null || $idx === '') {
                continue;
            }
            if (is_string($idx) && is_numeric($idx)) {
                $idx = (int) $idx;
            }
            if (! is_int($idx) && ! is_float($idx)) {
                continue;
            }
            $c = (int) $idx;
            if ($c >= 0 && $c <= 3) {
                return $c;
            }
        }

        return null;
    }
}
