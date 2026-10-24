<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuestIdentity
{
    public function handle(Request $request, Closure $next): Response
    {
        $guestToken = $request->cookie('cdmp_guest_id');

        if (! is_string($guestToken) || preg_match('/\A[a-zA-Z0-9]{64}\z/', $guestToken) !== 1) {
            $guestToken = Str::random(64);

            Cookie::queue(cookie(
                'cdmp_guest_id',
                $guestToken,
                60 * 24 * 365,
                '/',
                config('session.domain'),
                $request->isSecure(),
                true,
                false,
                'lax'
            ));
        }

        $request->attributes->set('guest_token_hash', hash('sha256', $guestToken));

        return $next($request);
    }
}
