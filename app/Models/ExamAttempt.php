<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    protected $fillable = [
        'guest_token_hash', 'mode', 'duration_minutes', 'started_at', 'expires_at', 'submitted_at',
        'status', 'total_questions', 'correct_count', 'incorrect_count',
        'unanswered_count', 'score_percent',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'submitted_at' => 'immutable_datetime',
            'score_percent' => 'decimal:2',
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ExamAttemptQuestion::class, 'exam_attempt_id')->orderBy('position');
    }

    public function answers(): HasMany
    {
        return $this->hasManyThrough(
            ExamAnswer::class,
            ExamAttemptQuestion::class,
            'exam_attempt_id',
            'exam_attempt_question_id',
            'id',
            'id'
        );
    }

    public function isFinalized(): bool
    {
        return $this->status !== 'in_progress';
    }
}
