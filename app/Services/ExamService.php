<?php

namespace App\Services;

use App\Models\ExamAttempt;
use App\Models\ExamAttemptQuestion;
use App\Models\Question;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamService
{
    public function eligibleQuestionCount(): int
    {
        return $this->eligibleQuestions()->count();
    }

    public function start(array $input, string $guestTokenHash): ExamAttempt
    {
        $mode = $input['mode'];
        $total = $mode === 'full' ? 100 : (int) $input['question_count'];
        $duration = match ($mode) {
            'full' => $input['duration_preset'] === 'esl' ? 110 : 90,
            'practice' => isset($input['practice_duration_minutes']) ? (int) $input['practice_duration_minutes'] : null,
        };

        $pool = $this->eligibleQuestions();

        if ($pool->count() < $total) {
            throw ValidationException::withMessages([
                'question_count' => "This session needs {$total} eligible questions, but only {$pool->count()} are available.",
            ]);
        }

        return DB::transaction(function () use ($mode, $total, $duration, $pool, $guestTokenHash): ExamAttempt {
            $startedAt = now('UTC');
            $attempt = ExamAttempt::create([
                'guest_token_hash' => $guestTokenHash,
                'mode' => $mode,
                'duration_minutes' => $duration,
                'started_at' => $startedAt,
                'expires_at' => $duration === null ? null : $startedAt->copy()->addMinutes($duration),
                'status' => 'in_progress',
                'total_questions' => $total,
            ]);

            foreach ($pool->shuffle()->take($total)->values() as $offset => $question) {
                $correct = $question->options->firstWhere('is_correct', true);
                $displayOptions = $question->options->shuffle()->map(fn ($option) => [
                    'source_index' => (int) $option->source_index,
                    'text' => $option->text,
                ])->values()->all();

                $attemptQuestion = ExamAttemptQuestion::create([
                    'exam_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'position' => $offset + 1,
                    'question_snapshot' => [
                        'source_id' => $question->source_id,
                        'text' => $question->text,
                        'explanation' => $question->explanation,
                        'original_number' => $question->original_number,
                        'source_page' => $question->source_page,
                        'source_type' => $question->source_type,
                    ],
                    'options_snapshot' => $displayOptions,
                    'correct_option_source_index' => (int) $correct->source_index,
                ]);

                $attemptQuestion->answer()->create([
                    'selected_option_source_index' => null,
                    'is_flagged' => false,
                ]);
            }

            return $attempt;
        });
    }

    public function reconcileExpiration(ExamAttempt $attempt): ExamAttempt
    {
        if ($attempt->status === 'in_progress' && $attempt->expires_at && $attempt->expires_at->lessThanOrEqualTo(now('UTC'))) {
            return $this->finalize($attempt->id, true);
        }

        return $attempt;
    }

    /** @return array{state: string, attempt: ExamAttempt} */
    public function saveAnswer(int $attemptId, int $position, array $input): array
    {
        return DB::transaction(function () use ($attemptId, $position, $input): array {
            $attempt = ExamAttempt::query()->whereKey($attemptId)->lockForUpdate()->firstOrFail();

            if ($attempt->status !== 'in_progress') {
                return ['state' => 'finalized', 'attempt' => $attempt];
            }

            if ($attempt->expires_at && $attempt->expires_at->lessThanOrEqualTo(now('UTC'))) {
                return ['state' => 'expired', 'attempt' => $this->finalizeLocked($attempt, true)];
            }

            $attemptQuestion = $attempt->questions()->where('position', $position)->firstOrFail();
            $changes = [];

            if (array_key_exists('selected_option_source_index', $input)) {
                $selected = $input['selected_option_source_index'];
                if ($selected !== null && ! collect($attemptQuestion->options_snapshot)->contains(fn ($option) => (int) $option['source_index'] === (int) $selected)) {
                    throw ValidationException::withMessages(['selected_option_source_index' => 'Choose an option belonging to this question.']);
                }
                $changes['selected_option_source_index'] = $selected;
                $changes['answered_at'] = $selected === null ? null : now('UTC');
            }

            if (array_key_exists('is_flagged', $input)) {
                $changes['is_flagged'] = (bool) $input['is_flagged'];
            }

            if ($changes !== []) {
                $answer = $attemptQuestion->answer()->firstOrCreate(
                    ['exam_attempt_question_id' => $attemptQuestion->id],
                    ['selected_option_source_index' => null, 'is_flagged' => false]
                );
                $answer->fill($changes)->save();
            }

            return ['state' => 'saved', 'attempt' => $attempt->fresh()];
        });
    }

    public function finalize(int $attemptId, bool $expired = false): ExamAttempt
    {
        return DB::transaction(function () use ($attemptId, $expired): ExamAttempt {
            $attempt = ExamAttempt::query()->whereKey($attemptId)->lockForUpdate()->firstOrFail();

            return $this->finalizeLocked($attempt, $expired);
        });
    }

    private function finalizeLocked(ExamAttempt $attempt, bool $expired): ExamAttempt
    {
        if ($attempt->status !== 'in_progress') {
            return $attempt;
        }

        $timedOut = $expired || ($attempt->expires_at && $attempt->expires_at->lessThanOrEqualTo(now('UTC')));
        $attemptQuestions = $attempt->questions()->with('answer')->get();
        $correct = 0;
        $incorrect = 0;
        $unanswered = 0;

        foreach ($attemptQuestions as $attemptQuestion) {
            $selected = $attemptQuestion->answer?->selected_option_source_index;
            if ($selected === null) {
                $unanswered++;
            } elseif ((int) $selected === (int) $attemptQuestion->correct_option_source_index) {
                $correct++;
            } else {
                $incorrect++;
            }
        }

        $submittedAt = $timedOut && $attempt->expires_at ? $attempt->expires_at : now('UTC');
        $attempt->forceFill([
            'status' => $timedOut ? 'expired' : 'submitted',
            'submitted_at' => $submittedAt,
            'correct_count' => $correct,
            'incorrect_count' => $incorrect,
            'unanswered_count' => $unanswered,
            'score_percent' => round(($correct / max($attempt->total_questions, 1)) * 100, 2),
        ])->save();

        return $attempt->fresh();
    }

    private function eligibleQuestions(): Collection
    {
        return Question::eligible()->with('options')->get()
            ->filter(fn (Question $question) => $question->options->count() >= 2 && $question->options->where('is_correct', true)->count() === 1)
            ->values();
    }
}
