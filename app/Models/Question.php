<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = [
        'source_id', 'original_number', 'text', 'explanation', 'source_page',
        'source_type', 'review_status', 'needs_key_review',
        'key_verified_against_dmbok', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'needs_key_review' => 'boolean',
            'key_verified_against_dmbok' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('source_index');
    }

    public function scopeEligible(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('review_status', 'ready_source_key')
            ->where('needs_key_review', false)
            ->whereHas('options', fn (Builder $options) => $options->where('is_correct', true));
    }
}
