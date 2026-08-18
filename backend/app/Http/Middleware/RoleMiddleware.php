<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            Log::warning('RoleMiddleware: No user on request', ['url' => $request->fullUrl()]);
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Insufficient permissions.',
            ], 403);
        }

        $allowedRoles = $roles;

        // super_admin inherits admin-level route access
        if (in_array('admin', $allowedRoles, true) && $user->role === 'super_admin') {
            return $next($request);
        }

        if (!in_array($user->role, $allowedRoles, true)) {
            Log::warning('RoleMiddleware: Role not allowed', [
                'user_role' => $user->role,
                'allowed_roles' => $allowedRoles,
                'url' => $request->fullUrl(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Insufficient permissions.',
            ], 403);
        }

        return $next($request);
    }
}
