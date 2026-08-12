<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaintenanceRequest;
use App\Http\Requests\UpdateMaintenanceRequest;
use App\Http\Resources\MaintenanceResource;
use App\Models\Maintenance;
use App\Models\Vehicle;
use App\Services\VehicleStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MaintenanceController extends Controller
{
    public function __construct(
        private VehicleStatusService $vehicleStatusService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Maintenance::with(['vehicle.branch', 'creator'])->latest();

        if ($user->isBranchManager() || ($user->isStaff() && !$user->isAdmin())) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        $maintenances = $query->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance records retrieved successfully',
            'data' => MaintenanceResource::collection($maintenances),
            'meta' => [
                'current_page' => $maintenances->currentPage(),
                'last_page' => $maintenances->lastPage(),
                'per_page' => $maintenances->perPage(),
                'total' => $maintenances->total(),
            ],
        ]);
    }

    public function store(StoreMaintenanceRequest $request): JsonResponse
    {
        if (!Gate::allows('create', Maintenance::class)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $maintenance = Maintenance::create(array_merge($request->validated(), ['created_by' => $request->user()->id]));

        if ($maintenance->vehicle) {
            $this->vehicleStatusService->transition(
                $maintenance->vehicle,
                Vehicle::STATUS_MAINTENANCE,
                $request->user(),
                'Maintenance scheduled',
                true
            );
        }

        $maintenance->load(['vehicle', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance created successfully',
            'data' => new MaintenanceResource($maintenance),
        ], 201);
    }

    public function show(Maintenance $maintenance): JsonResponse
    {
        $maintenance->load(['vehicle', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance record retrieved successfully',
            'data' => new MaintenanceResource($maintenance),
        ]);
    }

    public function update(UpdateMaintenanceRequest $request, Maintenance $maintenance): JsonResponse
    {
        if (!Gate::allows('update', $maintenance)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $maintenance->update($request->validated());

        if ($maintenance->status === 'completed' && $maintenance->vehicle) {
            $this->vehicleStatusService->transition(
                $maintenance->vehicle,
                Vehicle::STATUS_INSPECTION_REQUIRED,
                $request->user(),
                'Maintenance completed — inspection required',
                true
            );
        }

        $maintenance->load(['vehicle', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance updated successfully',
            'data' => new MaintenanceResource($maintenance),
        ]);
    }

    public function destroy(Maintenance $maintenance): JsonResponse
    {
        if (!Gate::allows('delete', $maintenance)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $maintenance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Maintenance deleted successfully',
        ]);
    }
}
