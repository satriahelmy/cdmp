<?php

namespace App\Http\Controllers;

use App\Models\ExamAttempt;
use Illuminate\Http\Request;

abstract class Controller
{
    protected function findGuestAttempt(Request $request, int|string $attemptId): ExamAttempt
    {
        return ExamAttempt::query()
            ->where('guest_token_hash', $this->guestTokenHash($request))
            ->findOrFail($attemptId);
    }

    protected function guestTokenHash(Request $request): string
    {
        $guestTokenHash = $request->attributes->get('guest_token_hash');

        abort_unless(
            is_string($guestTokenHash) && preg_match('/\A[a-f0-9]{64}\z/', $guestTokenHash) === 1,
            404
        );

        return $guestTokenHash;
    }
}
