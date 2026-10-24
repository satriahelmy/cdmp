<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartExamRequest;
use App\Services\ExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function create(ExamService $exams): View
    {
        return view('exams.create', ['eligibleCount' => $exams->eligibleQuestionCount()]);
    }

    public function store(StartExamRequest $request, ExamService $exams): RedirectResponse
    {
        $attempt = $exams->start(
            $request->validated(),
            $this->guestTokenHash($request)
        );

        return redirect()->to(route('exams.show', $attempt).'?position=1');
    }

    public function show(Request $request, string $attempt, ExamService $exams): View|RedirectResponse
    {
        $attempt = $this->findGuestAttempt($request, $attempt);
        $attempt = $exams->reconcileExpiration($attempt);
        if ($attempt->status !== 'in_progress') {
            return redirect()->route('exams.result', $attempt)->with('timeout_notice', $attempt->status === 'expired');
        }

        $questions = $attempt->questions()->with('answer')->get();
        $requestedPosition = filter_var($request->query('position'), FILTER_VALIDATE_INT);
        $position = $requestedPosition && $requestedPosition >= 1 && $requestedPosition <= $attempt->total_questions
            ? $requestedPosition
            : ($questions->first(fn ($question) => $question->answer?->selected_option_source_index === null)?->position ?? $attempt->total_questions);

        $current = $questions->firstWhere('position', $position);
        if (! $current) {
            abort(404);
        }

        $currentQuestion = [
            'position' => (int) $current->position,
            'text' => $current->question_snapshot['text'],
            'options' => collect($current->options_snapshot)->map(fn ($option) => [
                'source_index' => (int) $option['source_index'],
                'text' => $option['text'],
            ])->values()->all(),
            'selected' => $current->answer?->selected_option_source_index,
            'flagged' => (bool) ($current->answer?->is_flagged ?? false),
        ];

        $palette = $questions->map(fn ($question) => [
            'position' => (int) $question->position,
            'answered' => $question->answer?->selected_option_source_index !== null,
            'flagged' => (bool) ($question->answer?->is_flagged ?? false),
        ]);
        $answeredCount = $palette->where('answered', true)->count();
        $flaggedCount = $palette->where('flagged', true)->count();

        return view('exams.show', compact('attempt', 'currentQuestion', 'palette', 'position', 'answeredCount', 'flaggedCount'));
    }

    public function submit(Request $request, string $attempt, ExamService $exams): RedirectResponse
    {
        $attempt = $this->findGuestAttempt($request, $attempt);
        $attempt = $exams->finalize($attempt->id);

        return redirect()->route('exams.result', $attempt)->with('timeout_notice', $attempt->status === 'expired');
    }
}
