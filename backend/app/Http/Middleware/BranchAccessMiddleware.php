<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures branch-scoped requests are served only to authorized users.
 *
 * Usage:  ->middleware('branch.access')
 *
 * Admins pass freely.
 * Branch managers / staff are restricted to their assigned branch_id.
 * If a client sends ?branch_id= or branch_id in the body that differs
 * from the authenticated user's branch, the request is denied.
 */
class BranchAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        // Admins and fleet managers have company-wide access — skip scoping
        if ($user->isAdmin() || $user->isFleetManager()) {
            return $next($request);
        }

        // Customers also bypass this middleware (they use resource-ownership checks)
        if ($user->isCustomer()) {
            return $next($request);
        }

        // Branch staff / managers: verify any provided branch_id matches theirs
        $requestedBranchId = $request->route('branch')?->id
            ?? $request->input('branch_id')
            ?? null;

        if ($requestedBranchId !== null && (int) $requestedBranchId !== (int) $user->branch_id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. You do not have permission to access this branch.',
            ], 403);
        }

        return $next($request);
    }
}
