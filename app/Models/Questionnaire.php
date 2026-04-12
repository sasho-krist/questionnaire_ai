<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Questionnaire extends Model
{
    protected $fillable = [
        'uuid',
        'user_title',
        'topic_keywords',
        'title_suggestions',
        'chosen_title',
        'status',
        'points_per_correct',
        'seconds_per_question',
    ];

    protected function casts(): array
    {
        return [
            'title_suggestions' => 'array',
            'points_per_correct' => 'decimal:2',
            'seconds_per_question' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Questionnaire $q): void {
            if (empty($q->uuid)) {
                $q->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function sections(): HasMany
    {
        return $this->hasMany(QuestionnaireSection::class)->orderBy('sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuestionnaireAttempt::class);
    }
}
