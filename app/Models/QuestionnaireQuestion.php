<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionnaireQuestion extends Model
{
    protected $fillable = [
        'section_id',
        'sort_order',
        'body',
        'choice_options',
        'correct_option',
    ];

    protected function casts(): array
    {
        return [
            'choice_options' => 'array',
            'correct_option' => 'integer',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireSection::class, 'section_id');
    }

    public function hasMultipleChoice(): bool
    {
        $opts = $this->choice_options;

        return is_array($opts) && count($opts) === 4;
    }

    public function isScoredMultipleChoice(): bool
    {
        return $this->hasMultipleChoice()
            && $this->correct_option !== null
            && $this->correct_option >= 0
            && $this->correct_option <= 3;
    }
}
