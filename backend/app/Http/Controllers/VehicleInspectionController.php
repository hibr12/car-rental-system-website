<?php

namespace App\Http\Controllers;

use App\Http\Resources\VehicleInspectionResource;
use App\Models\VehicleInspection;
use App\Services\BranchScopeService;
use App\Services\VehicleInspectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleInspectionController extends Controller
{
    public function __construct(
        private VehicleInspectionService $inspectionService,
        private BranchScopeService $branchScope
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = VehicleInspection::with(['vehicle.branch', 'booking', 'inspector'])
            ->latest();

        $requestedBranchId = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;
        $effectiveBranchId = $this->branchScope->resolveBranchFilter($user, $requestedBranchId);

        if ($effectiveBranchId !== null) {
            $query->where('branch_id', $effectiveBranchId);
        } elseif ($requestedBranchId && $user->hasCompanyWideAccess()) {
            $query->where('branch_id', $requestedBranchId);
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', (int) $request->input('vehicle_id'));
        }

        if ($request->filled('inspection_type')) {
            $query->where('inspection_type', $request->input('inspection_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('result')) {
            $query->where('result', $request->input('result'));
        }

        $records = $query->paginate((int) $request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => VehicleInspectionResource::collection($records),
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
        $validated = $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'booking_id' => ['nullable', 'exists:bookings,id'],
            'inspection_type' => ['required', 'string', 'in:pre_rental,post_return,maintenance,periodic,transfer'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'fuel_level' => ['nullable', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $inspection = $this->inspectionService->create($validated, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Inspection created successfully.',
            'data' => new VehicleInspectionResource($inspection),
        ], 201);
    }

    public function show(Request $request, VehicleInspection $inspection): JsonResponse
    {
        $this->branchScope->assertCanAccessBranch($request->user(), $inspection->branch_id);

        $inspection->load(['vehicle.branch', 'booking', 'inspector']);

        return response()->json([
            'success' => true,
            'data' => new VehicleInspectionResource($inspection),
        ]);
    }

    public function complete(Request $request, VehicleInspection $inspection): JsonResponse
    {
        $this->branchScope->assertCanAccessBranch($request->user(), $inspection->branch_id);

        $validated = $request->validate([
            'result' => ['required', 'string', 'in:passed,failed,requires_maintenance'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'mileage_correction' => ['nullable', 'boolean'],
            'fuel_level' => ['nullable', 'integer', 'min:0', 'max:100'],
            'exterior_condition' => ['nullable', 'string', 'max:100'],
            'interior_condition' => ['nullable', 'string', 'max:100'],
            'tires_condition' => ['nullable', 'string', 'max:100'],
            'lights_condition' => ['nullable', 'string', 'max:100'],
            'brakes_condition' => ['nullable', 'string', 'max:100'],
            'engine_indicators' => ['nullable', 'string', 'max:100'],
            'has_damage' => ['nullable', 'boolean'],
            'damage_notes' => ['nullable', 'string', 'max:2000'],
            'photos' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'damage' => ['nullable', 'array'],
            'damage.damage_type' => ['nullable', 'string', 'max:100'],
            'damage.description' => ['nullable', 'string', 'max:2000'],
            'damage.severity' => ['nullable', 'string', 'in:low,medium,high,critical'],
            'damage.location' => ['nullable', 'string', 'max:255'],
            'damage.estimated_repair_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $completed = $this->inspectionService->complete($inspection, $validated, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Inspection completed successfully.',
            'data' => new VehicleInspectionResource($completed),
        ]);
    }
}
