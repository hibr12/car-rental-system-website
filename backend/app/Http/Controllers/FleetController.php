<?php

namespace App\Http\Controllers;

use App\Services\FleetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FleetController extends Controller
{
    public function __construct(
        private FleetService $fleetService
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isAdmin() && !$user->isFleetManager() && !$user->isBranchManager()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Insufficient permissions.',
            ], 403);
        }

        $branchId = $request->input('branch_id') ? (int) $request->input('branch_id') : null;

        if ($user->isBranchManager() && !$user->isAdmin()) {
            $branchId = $user->branch_id;
        }

        return response()->json([
            'success' => true,
            'data' => $this->fleetService->dashboardStats($user, $branchId),
        ]);
    }
}
