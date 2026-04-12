<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class QuestionnaireAttempt extends Model
{
    protected $fillable = [
        'uuid',
        'questionnaire_id',
        'answers',
        'started_at',
        'deadline_at',
        'score',
        'max_score',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'started_at' => 'datetime',
            'deadline_at' => 'datetime',
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (QuestionnaireAttempt $a): void {
            if (empty($a->uuid)) {
                $a->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class);
    }

    public function isDeadlinePassed(): bool
    {
        if ($this->deadline_at === null) {
            return false;
        }

        return now()->greaterThan($this->deadline_at);
    }
}
