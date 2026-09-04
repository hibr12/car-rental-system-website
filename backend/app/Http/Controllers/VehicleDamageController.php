<?php

namespace App\Http\Controllers;

use App\Http\Resources\VehicleDamageResource;
use App\Models\VehicleDamage;
use App\Services\BranchScopeService;
use App\Services\VehicleDamageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleDamageController extends Controller
{
    public function __construct(
        private VehicleDamageService $damageService,
        private BranchScopeService $branchScope
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = VehicleDamage::with(['vehicle.branch', 'booking', 'reporter'])->latest('reported_at');

        if ($this->branchScope->isBranchScoped($user) && $user->branch_id) {
            $query->whereHas('vehicle', fn ($q) => $q->where('branch_id', $user->branch_id));
        } elseif ($request->filled('branch_id') && $user->hasCompanyWideAccess()) {
            $query->whereHas('vehicle', fn ($q) => $q->where('branch_id', (int) $request->input('branch_id')));
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', (int) $request->input('vehicle_id'));
        }

        if ($request->filled('repair_status')) {
            $query->where('repair_status', $request->input('repair_status'));
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        $records = $query->paginate((int) $request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => VehicleDamageResource::collection($records),
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
            'inspection_id' => ['nullable', 'exists:vehicle_inspections,id'],
            'damage_type' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:2000'],
            'severity' => ['nullable', 'string', 'in:low,medium,high,critical'],
            'location' => ['nullable', 'string', 'max:255'],
            'photos' => ['nullable', 'array'],
            'estimated_repair_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $damage = $this->damageService->create($validated, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Damage record created successfully.',
            'data' => new VehicleDamageResource($damage),
        ], 201);
    }

    public function update(Request $request, VehicleDamage $damage): JsonResponse
    {
        $validated = $request->validate([
            'repair_status' => ['sometimes', 'string', 'in:pending,in_progress,completed,waived'],
            'severity' => ['sometimes', 'string', 'in:low,medium,high,critical'],
            'estimated_repair_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $updated = $this->damageService->update($damage, $validated, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Damage record updated successfully.',
            'data' => new VehicleDamageResource($updated),
        ]);
    }
}
