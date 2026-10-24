<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExamAttemptQuestion extends Model
{
    protected $fillable = [
        'exam_attempt_id', 'question_id', 'position', 'question_snapshot',
        'options_snapshot', 'correct_option_source_index',
    ];

    protected function casts(): array
    {
        return [
            'question_snapshot' => 'array',
            'options_snapshot' => 'array',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function answer(): HasOne
    {
        return $this->hasOne(ExamAnswer::class);
    }
}
