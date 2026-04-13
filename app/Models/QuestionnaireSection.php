<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionnaireSection extends Model
{
    protected $fillable = [
        'questionnaire_id',
        'sort_order',
        'title',
    ];

    /** @var list<string> */
    protected $touches = ['questionnaire'];

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (QuestionnaireSection $section): void {
            $section->questionnaire?->touch();
        });
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuestionnaireQuestion::class, 'section_id')->orderBy('sort_order');
    }
}
