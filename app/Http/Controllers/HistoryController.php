<?php

namespace App\Http\Controllers;

use App\Models\ExamAttempt;
use App\Services\ExamService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(Request $request, ExamService $exams): View
    {
        $guestTokenHash = $this->guestTokenHash($request);
        $activeAttempts = ExamAttempt::query()->where('guest_token_hash', $guestTokenHash)
            ->where('status', 'in_progress')->orderByDesc('started_at')->get();
        foreach ($activeAttempts as $attempt) {
            $exams->reconcileExpiration($attempt);
        }

        $attempts = ExamAttempt::query()->where('guest_token_hash', $guestTokenHash)->orderByDesc('started_at')->get();

        return view('history.index', compact('attempts'));
    }
}
