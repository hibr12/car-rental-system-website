<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates Sanctum bearer tokens when present, but allows unauthenticated access.
 * Used on public routes that apply different logic for logged-in management users.
 */
class OptionalSanctumAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken() && ! $request->user()) {
            $accessToken = PersonalAccessToken::findToken($request->bearerToken());

            if ($accessToken && (! $accessToken->expires_at || $accessToken->expires_at->isFuture())) {
                $user = $accessToken->tokenable;
                $request->setUserResolver(static fn () => $user);
                Auth::guard('sanctum')->setUser($user);
            }
        }

        return $next($request);
    }
}
