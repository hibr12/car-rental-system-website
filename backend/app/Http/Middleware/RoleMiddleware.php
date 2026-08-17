<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Insufficient permissions.',
            ], 403);
        }

        $allowedRoles = explode(',', $roles);

        // super_admin inherits admin-level route access
        if (in_array('admin', $allowedRoles, true) && $user->role === 'super_admin') {
            return $next($request);
        }

        if (!in_array($user->role, $allowedRoles, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Insufficient permissions.',
            ], 403);
        }

        return $next($request);
    }
}
