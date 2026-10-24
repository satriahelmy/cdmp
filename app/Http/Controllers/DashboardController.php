<?php

namespace App\Http\Controllers;

use App\Models\ExamAttempt;
use App\Services\ExamService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ExamService $exams): View
    {
        $guestTokenHash = $this->guestTokenHash($request);
        $activeAttempts = ExamAttempt::query()->where('guest_token_hash', $guestTokenHash)
            ->where('status', 'in_progress')->orderByDesc('started_at')->get();
        foreach ($activeAttempts as $attempt) {
            $exams->reconcileExpiration($attempt);
        }

        $activeAttempt = ExamAttempt::query()->where('guest_token_hash', $guestTokenHash)
            ->where('status', 'in_progress')->orderByDesc('started_at')->first();
        $latestCompleted = ExamAttempt::query()->where('guest_token_hash', $guestTokenHash)
            ->whereIn('status', ['submitted', 'expired'])
            ->orderByDesc('submitted_at')->first();
        $recentAttempts = ExamAttempt::query()->where('guest_token_hash', $guestTokenHash)
            ->orderByDesc('started_at')->limit(4)->get();

        return view('dashboard', compact('activeAttempt', 'latestCompleted', 'recentAttempts'));
    }
}
