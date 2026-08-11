<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Vehicle;
use App\Models\VehicleTransfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehicleTransferController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = VehicleTransfer::with(['vehicle.category', 'fromBranch', 'toBranch', 'requester', 'approver']);

        // Branch-scoped: managers/staff see only transfers involving their branch
        if (!$user->isAdmin()) {
            $branchId = $user->branch_id;
            $query->where(function ($q) use ($branchId) {
                $q->where('from_branch_id', $branchId)
                  ->orWhere('to_branch_id', $branchId);
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $transfers = $query->orderByDesc('created_at')->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => $transfers->items(),
            'meta'    => [
                'current_page' => $transfers->currentPage(),
                'last_page'    => $transfers->lastPage(),
                'total'        => $transfers->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'vehicle_id'    => ['required', 'exists:vehicles,id'],
            'to_branch_id'  => ['required', 'exists:branches,id'],
            'transfer_date' => ['required', 'date', 'after_or_equal:today'],
            'reason'        => ['nullable', 'string', 'max:1000'],
            'notes'         => ['nullable', 'string', 'max:1000'],
        ]);

        $vehicle = Vehicle::findOrFail($data['vehicle_id']);

        // Enforce: requester must own the from-branch or be admin
        if (!$user->isAdmin() && $vehicle->branch_id !== $user->branch_id) {
            return response()->json(['success' => false, 'message' => 'You can only transfer vehicles from your own branch.'], 403);
        }

        if ($vehicle->branch_id === (int) $data['to_branch_id']) {
            return response()->json(['success' => false, 'message' => 'Vehicle is already at the destination branch.'], 422);
        }

        if (!in_array($vehicle->status, ['available', 'unavailable'])) {
            return response()->json(['success' => false, 'message' => 'Only available or unavailable vehicles can be transferred.'], 422);
        }

        $transfer = VehicleTransfer::create([
            'vehicle_id'     => $vehicle->id,
            'from_branch_id' => $vehicle->branch_id,
            'to_branch_id'   => $data['to_branch_id'],
            'requested_by'   => $user->id,
            'transfer_date'  => $data['transfer_date'],
            'reason'         => $data['reason'] ?? null,
            'notes'          => $data['notes'] ?? null,
            'status'         => VehicleTransfer::STATUS_REQUESTED,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transfer request created.',
            'data'    => $transfer->load(['vehicle', 'fromBranch', 'toBranch', 'requester']),
        ], 201);
    }

    public function show(Request $request, VehicleTransfer $transfer): JsonResponse
    {
        $user = $request->user();

        if (!$user->isAdmin() && $user->branch_id !== $transfer->from_branch_id && $user->branch_id !== $transfer->to_branch_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => $transfer->load(['vehicle.category', 'fromBranch', 'toBranch', 'requester', 'approver']),
        ]);
    }

    public function approve(Request $request, VehicleTransfer $transfer): JsonResponse
    {
        if ($transfer->status !== VehicleTransfer::STATUS_REQUESTED) {
            return response()->json(['success' => false, 'message' => 'Only requested transfers can be approved.'], 422);
        }

        DB::transaction(function () use ($transfer, $request) {
            $transfer->update([
                'status'      => VehicleTransfer::STATUS_APPROVED,
                'approved_by' => $request->user()->id,
            ]);
            $transfer->vehicle->update(['status' => Vehicle::STATUS_TRANSFERRED]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Transfer approved.',
            'data'    => $transfer->fresh()->load(['vehicle', 'fromBranch', 'toBranch']),
        ]);
    }

    public function reject(Request $request, VehicleTransfer $transfer): JsonResponse
    {
        if ($transfer->status !== VehicleTransfer::STATUS_REQUESTED) {
            return response()->json(['success' => false, 'message' => 'Only requested transfers can be rejected.'], 422);
        }

        $transfer->update([
            'status'      => VehicleTransfer::STATUS_CANCELLED,
            'approved_by' => $request->user()->id,
            'notes'       => $request->input('reason') ?? $transfer->notes,
        ]);

        return response()->json(['success' => true, 'message' => 'Transfer rejected.', 'data' => $transfer->fresh()]);
    }

    public function complete(Request $request, VehicleTransfer $transfer): JsonResponse
    {
        if (!in_array($transfer->status, [VehicleTransfer::STATUS_APPROVED, VehicleTransfer::STATUS_IN_TRANSIT])) {
            return response()->json(['success' => false, 'message' => 'Transfer cannot be completed from its current status.'], 422);
        }

        DB::transaction(function () use ($transfer) {
            $transfer->update(['status' => VehicleTransfer::STATUS_COMPLETED]);
            $transfer->vehicle->update([
                'branch_id' => $transfer->to_branch_id,
                'status'    => Vehicle::STATUS_AVAILABLE,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Transfer completed. Vehicle is now at the destination branch.',
            'data'    => $transfer->fresh()->load(['vehicle', 'fromBranch', 'toBranch']),
        ]);
    }

    public function markInTransit(VehicleTransfer $transfer): JsonResponse
    {
        if ($transfer->status !== VehicleTransfer::STATUS_APPROVED) {
            return response()->json(['success' => false, 'message' => 'Only approved transfers can be marked as in transit.'], 422);
        }

        $transfer->update(['status' => VehicleTransfer::STATUS_IN_TRANSIT]);

        return response()->json(['success' => true, 'message' => 'Transfer marked as in transit.', 'data' => $transfer->fresh()]);
    }
}
