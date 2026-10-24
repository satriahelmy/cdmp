<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveExamAnswerRequest;
use App\Services\ExamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamAnswerController extends Controller
{
    public function update(SaveExamAnswerRequest $request, string $attempt, int $position, ExamService $exams): JsonResponse
    {
        $attempt = $this->findGuestAttempt($request, $attempt);
        $result = $exams->saveAnswer($attempt->id, $position, $request->validated());
        $savedAttempt = $result['attempt'];

        if ($result['state'] === 'expired') {
            return response()->json([
                'message' => 'The time limit has ended. Your answers have been submitted.',
                'redirect_url' => route('exams.result', $savedAttempt),
            ], 410);
        }

        if ($result['state'] === 'finalized') {
            return response()->json([
                'message' => 'This attempt is already finalized.',
                'redirect_url' => route('exams.result', $savedAttempt),
            ], 409);
        }

        return response()->json([
            'saved' => true,
            'server_now' => now('UTC')->toIso8601String(),
            'expires_at' => $savedAttempt->expires_at?->toIso8601String(),
        ]);
    }
}
