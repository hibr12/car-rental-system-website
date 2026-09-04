<?php

namespace App\Http\Controllers;

use App\Http\Resources\MaintenanceRequestResource;
use App\Models\MaintenanceRequest;
use App\Services\MaintenanceRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceRequestController extends Controller
{
    public function __construct(
        private MaintenanceRequestService $requestService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = MaintenanceRequest::with(['vehicle', 'branch', 'requester', 'maintenance'])
            ->latest();

        if ($user->isBranchManager() && !$user->isAdmin()) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($user->isFleetManager() && !$user->isAdmin()) {
            // Fleet sees all requests
        } elseif ($request->filled('branch_id') && $user->isAdmin()) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', (int) $request->input('vehicle_id'));
        }

        $records = $query->paginate((int) $request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => MaintenanceRequestResource::collection($records),
            'meta' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isBranchManager() && !$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'priority' => ['nullable', 'string', 'in:low,medium,high,urgent'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $record = $this->requestService->create($validated, $user);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Maintenance request submitted.',
            'data' => new MaintenanceRequestResource($record),
        ], 201);
    }

    public function approve(Request $request, MaintenanceRequest $maintenanceRequest): JsonResponse
    {
        $user = $request->user();

        if (!$user->isFleetManager() && !$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'maintenance_type' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
        ]);

        $record = $this->requestService->approve($maintenanceRequest, $user, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance request approved.',
            'data' => new MaintenanceRequestResource($record),
        ]);
    }

    public function reject(Request $request, MaintenanceRequest $maintenanceRequest): JsonResponse
    {
        $user = $request->user();

        if (!$user->isFleetManager() && !$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $record = $this->requestService->reject($maintenanceRequest, $user, $validated['reason']);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance request rejected.',
            'data' => new MaintenanceRequestResource($record),
        ]);
    }
}
