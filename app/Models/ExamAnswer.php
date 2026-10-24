<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAnswer extends Model
{
    protected $fillable = [
        'exam_attempt_question_id', 'selected_option_source_index', 'is_flagged', 'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'selected_option_source_index' => 'integer',
            'is_flagged' => 'boolean',
            'answered_at' => 'immutable_datetime',
        ];
    }

    public function attemptQuestion(): BelongsTo
    {
        return $this->belongsTo(ExamAttemptQuestion::class, 'exam_attempt_question_id');
    }
}
