<?php

namespace App\Http\Controllers;

use App\Services\ExamService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExamResultController extends Controller
{
    public function show(Request $request, string $attempt, ExamService $exams): View|RedirectResponse
    {
        $attempt = $this->findGuestAttempt($request, $attempt);
        $attempt = $exams->reconcileExpiration($attempt);
        if ($attempt->status === 'in_progress') {
            return redirect()->route('exams.show', $attempt);
        }

        $reviews = $attempt->questions()->with('answer')->get()->map(function ($attemptQuestion): array {
            $snapshot = $attemptQuestion->question_snapshot;
            $options = collect($attemptQuestion->options_snapshot)->map(fn ($option) => [
                'source_index' => (int) $option['source_index'],
                'text' => $option['text'],
                'is_selected' => $attemptQuestion->answer?->selected_option_source_index !== null
                    && (int) $attemptQuestion->answer->selected_option_source_index === (int) $option['source_index'],
                'is_correct' => (int) $attemptQuestion->correct_option_source_index === (int) $option['source_index'],
            ])->values();
            $selected = $attemptQuestion->answer?->selected_option_source_index;

            return [
                'position' => (int) $attemptQuestion->position,
                'text' => $snapshot['text'],
                'explanation' => $snapshot['explanation'] ?? null,
                'original_number' => $snapshot['original_number'] ?? null,
                'source_page' => $snapshot['source_page'] ?? null,
                'source_id' => $snapshot['source_id'] ?? null,
                'options' => $options,
                'selected_text' => $selected === null ? null : $options->firstWhere('source_index', (int) $selected)['text'] ?? null,
                'correct_text' => $options->firstWhere('is_correct', true)['text'] ?? null,
                'is_flagged' => (bool) ($attemptQuestion->answer?->is_flagged ?? false),
                'outcome' => $selected === null ? 'unanswered' : ((int) $selected === (int) $attemptQuestion->correct_option_source_index ? 'correct' : 'incorrect'),
            ];
        });

        $timeUsedSeconds = $attempt->submitted_at
            ? max(0, $attempt->submitted_at->getTimestamp() - $attempt->started_at->getTimestamp())
            : 0;

        return view('exams.result', compact('attempt', 'reviews', 'timeUsedSeconds'));
    }
}
