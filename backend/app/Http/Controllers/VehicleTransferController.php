<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Maintenance;
use App\Models\Vehicle;
use App\Models\VehicleTransfer;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehicleTransferController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = VehicleTransfer::query()
            ->with(['vehicle.category', 'fromBranch', 'toBranch', 'requester', 'approvedByUser', 'completedByUser', 'rejectedByUser']);

        // Branch-scoped: managers/staff see only transfers involving their branch.
        if (!$user->isAdmin()) {
            $branchId = $user->branch_id;
            $query->where(function ($q) use ($branchId) {
                $q->where('from_branch_id', $branchId)
                    ->orWhere('to_branch_id', $branchId);
            });
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('from_branch_id')) {
            $query->where('from_branch_id', (int) $request->input('from_branch_id'));
        }

        if ($request->filled('to_branch_id')) {
            $query->where('to_branch_id', (int) $request->input('to_branch_id'));
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', (int) $request->input('vehicle_id'));
        }

        if ($request->filled('branch_id')) {
            $branchId = (int) $request->input('branch_id');
            $query->where(function ($q) use ($branchId) {
                $q->where('from_branch_id', $branchId)
                    ->orWhere('to_branch_id', $branchId);
            });
        }

        if ($request->filled('search')) {
            $search = strtolower($request->string('search'));
            $query->where(function ($q) use ($search) {
                $q->where('id', (int) $search)
                    ->orWhereHas('vehicle', function ($q2) use ($search) {
                        $q2->whereRaw('LOWER(brand) LIKE ?', ["%{$search}%"])
                            ->orWhereRaw('LOWER(model) LIKE ?', ["%{$search}%"])
                            ->orWhereRaw('LOWER(registration_number) LIKE ?', ["%{$search}%"]);
                    })
                    ->orWhereHas('fromBranch', function ($q2) use ($search) {
                        $q2->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
                    })
                    ->orWhereHas('toBranch', function ($q2) use ($search) {
                        $q2->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
                    });
            });
        }

        if ($request->filled('start_date') || $request->filled('end_date')) {
            $start = $request->filled('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : null;
            $end = $request->filled('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : null;

            $query->where(function ($q) use ($start, $end) {
                if ($start && !$end) {
                    $q->whereDate('transfer_date', '>=', $start);
                } elseif (!$start && $end) {
                    $q->whereDate('transfer_date', '<=', $end);
                } elseif ($start && $end) {
                    $q->whereBetween('transfer_date', [$start->toDateString(), $end->toDateString()]);
                }
            });
        }

        // Stats are computed for the base scoped query (ignoring filters except branch scoping).
        $scopedBase = clone $query;
        $totalScoped = (clone $scopedBase)->count();

        $counts = [
            'pending' => (clone $scopedBase)->where('status', VehicleTransfer::STATUS_PENDING)->count(),
            'approved' => (clone $scopedBase)->where('status', VehicleTransfer::STATUS_APPROVED)->count(),
            'in_transit' => (clone $scopedBase)->where('status', VehicleTransfer::STATUS_IN_TRANSIT)->count(),
            'completed' => (clone $scopedBase)->where('status', VehicleTransfer::STATUS_COMPLETED)->count(),
            'rejected' => (clone $scopedBase)->where('status', VehicleTransfer::STATUS_REJECTED)->count(),
            'cancelled' => (clone $scopedBase)->where('status', VehicleTransfer::STATUS_CANCELLED)->count(),
        ];

        $perPage = min((int) $request->input('per_page', 15), 50);
        $transfers = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $transfers->items(),
            'meta' => [
                'current_page' => $transfers->currentPage(),
                'last_page' => $transfers->lastPage(),
                'total' => $transfers->total(),
                'stats' => [
                    'total' => $totalScoped,
                    'pending' => $counts['pending'],
                    'approved' => $counts['approved'],
                    'in_transit' => $counts['in_transit'],
                    'completed' => $counts['completed'],
                    'rejected' => $counts['rejected'],
                    'cancelled' => $counts['cancelled'],
                ],
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

        $vehicle = Vehicle::query()->with(['branch'])->findOrFail($data['vehicle_id']);
        $toBranchId = (int) $data['to_branch_id'];
        $fromBranchId = (int) $vehicle->branch_id;
        $transferDate = Carbon::parse($data['transfer_date'])->toDateString();

        // Branch assignment must come from the vehicle.
        if ($vehicle->branch_id === null) {
            return response()->json(['success' => false, 'message' => 'Vehicle must belong to a branch.'], 422);
        }

        // Role constraint: non-admin can only request from their own branch.
        if (!$user->isAdmin() && (int) $vehicle->branch_id !== (int) $user->branch_id) {
            return response()->json(['success' => false, 'message' => 'You can only transfer vehicles from your own branch.'], 403);
        }

        if ($fromBranchId === $toBranchId) {
            return response()->json(['success' => false, 'message' => 'Source and destination branches must be different.'], 422);
        }

        $toBranch = Branch::findOrFail($toBranchId);
        if (!$toBranch->isActive()) {
            return response()->json(['success' => false, 'message' => 'Destination branch is inactive.'], 422);
        }

        // Vehicle state validation.
        if ($vehicle->status !== Vehicle::STATUS_AVAILABLE) {
            return response()->json(['success' => false, 'message' => 'Vehicle cannot be transferred because it is currently not available.'], 422);
        }

        // Maintenance prevents transfer.
        if (Maintenance::query()->active()->where('vehicle_id', $vehicle->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Vehicle is currently under maintenance and cannot be transferred.'], 422);
        }

        // Booking protection: block if there is an upcoming confirmed booking at current branch.
        $hasConfirmedBooking = Booking::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('branch_id', $fromBranchId)
            ->whereIn('status', [
                Booking::STATUS_CONFIRMED,
                Booking::STATUS_READY_FOR_PICKUP,
                Booking::STATUS_ACTIVE,
            ])
            ->where(function ($q) use ($transferDate) {
                $dayStart = Carbon::parse($transferDate)->startOfDay();
                $dayEnd = Carbon::parse($transferDate)->endOfDay();
                $q->whereBetween('pickup_date', [$dayStart, $dayEnd])
                    ->orWhereBetween('return_date', [$dayStart, $dayEnd])
                    ->orWhere(function ($q2) use ($dayStart, $dayEnd) {
                        $q2->where('pickup_date', '<=', $dayStart)
                            ->where('return_date', '>=', $dayEnd);
                    });
            })
            ->exists();

        if ($hasConfirmedBooking) {
            return response()->json(['success' => false, 'message' => 'This vehicle has an upcoming confirmed booking and cannot be transferred.'], 422);
        }

        // Concurrency protection: disallow multiple active transfers.
        $activeTransferStatuses = [
            VehicleTransfer::STATUS_PENDING,
            VehicleTransfer::STATUS_APPROVED,
            VehicleTransfer::STATUS_IN_TRANSIT,
        ];

        $createdTransfer = null;

        try {
            DB::transaction(function () use (&$createdTransfer, $user, $vehicle, $fromBranchId, $toBranchId, $transferDate, $data, $activeTransferStatuses) {
                $vehicleLocked = Vehicle::query()->where('id', $vehicle->id)->lockForUpdate()->first();
                if (!$vehicleLocked) {
                    throw new \RuntimeException('Vehicle not found.');
                }

                $alreadyActive = VehicleTransfer::query()
                    ->where('vehicle_id', $vehicle->id)
                    ->whereIn('status', $activeTransferStatuses)
                    ->lockForUpdate()
                    ->exists();

                if ($alreadyActive) {
                    throw new \RuntimeException('Vehicle already has an active transfer.');
                }

                $transfer = VehicleTransfer::create([
                    'vehicle_id' => $vehicle->id,
                    'from_branch_id' => $fromBranchId,
                    'to_branch_id' => $toBranchId,
                    'requested_by' => $user->id,
                    'transfer_date' => $transferDate,
                    'reason' => $data['reason'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'status' => VehicleTransfer::STATUS_PENDING,
                    'requested_at' => now(),
                ]);

                // Audit: request.
                app(AuditLogService::class)->log(
                    $user,
                    'transfer_requested',
                    'vehicle_transfer',
                    $transfer->id,
                    null,
                    ['status' => VehicleTransfer::STATUS_PENDING],
                    'Transfer request created.',
                    $fromBranchId
                );

                $createdTransfer = $transfer;
            });
        } catch (\RuntimeException $e) {
            $msg = $e->getMessage();
            $code = str_contains($msg, 'active transfer') ? 409 : 422;
            return response()->json(['success' => false, 'message' => $msg], $code);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transfer request created.',
            'data'    => $createdTransfer->load(['vehicle', 'fromBranch', 'toBranch', 'requester']),
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
            'data'    => $transfer->load([
                'vehicle.category',
                'fromBranch',
                'toBranch',
                'requester',
                'approver',
                'rejectedByUser',
                'cancelledByUser',
                'completedByUser',
            ]),
        ]);
    }

    public function approve(Request $request, VehicleTransfer $transfer): JsonResponse
    {
        $user = $request->user();
        if (!in_array($transfer->status, [VehicleTransfer::STATUS_PENDING, 'requested'], true)) {
            return response()->json(['success' => false, 'message' => 'Only pending transfers can be approved.'], 422);
        }

        // Authorization: admin can approve; branch manager can approve if destination matches their branch.
        if (!$user->isAdmin() && (int) $user->branch_id !== (int) $transfer->to_branch_id) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to approve this transfer.'], 403);
        }

        try {
            DB::transaction(function () use ($transfer, $user) {
                $transferLocked = VehicleTransfer::query()->where('id', $transfer->id)->lockForUpdate()->firstOrFail();
                $vehicleLocked = Vehicle::query()->where('id', $transferLocked->vehicle_id)->lockForUpdate()->firstOrFail();

                if (!in_array($transferLocked->status, [VehicleTransfer::STATUS_PENDING, 'requested'], true)) {
                    throw new \RuntimeException('Transfer cannot be approved from its current status.');
                }

                // Re-validate vehicle eligibility.
                if ($vehicleLocked->status !== Vehicle::STATUS_AVAILABLE) {
                    throw new \RuntimeException('Vehicle cannot be transferred because it is currently not available.');
                }

                if (Maintenance::query()->active()->where('vehicle_id', $vehicleLocked->id)->exists()) {
                    throw new \RuntimeException('Vehicle is currently under maintenance and cannot be transferred.');
                }

            // Booking protection re-check: confirmed/ready/active bookings at the source branch
            // overlapping the requested transfer day.
            $transferDate = Carbon::parse($transferLocked->transfer_date)->toDateString();
            $dayStart = Carbon::parse($transferDate)->startOfDay();
            $dayEnd = Carbon::parse($transferDate)->endOfDay();
            $hasConfirmedBooking = Booking::query()
                ->where('vehicle_id', $vehicleLocked->id)
                ->where('branch_id', $transferLocked->from_branch_id)
                ->whereIn('status', [
                    Booking::STATUS_CONFIRMED,
                    Booking::STATUS_READY_FOR_PICKUP,
                    Booking::STATUS_ACTIVE,
                ])
                ->where(function ($q) use ($dayStart, $dayEnd) {
                    $q->whereBetween('pickup_date', [$dayStart, $dayEnd])
                        ->orWhereBetween('return_date', [$dayStart, $dayEnd])
                        ->orWhere(function ($q2) use ($dayStart, $dayEnd) {
                            $q2->where('pickup_date', '<=', $dayStart)
                                ->where('return_date', '>=', $dayEnd);
                        });
                })
                ->exists();

            if ($hasConfirmedBooking) {
                throw new \RuntimeException('This vehicle has an upcoming confirmed booking and cannot be transferred.');
            }

                $activeTransferStatuses = [
                    VehicleTransfer::STATUS_PENDING,
                    VehicleTransfer::STATUS_APPROVED,
                    VehicleTransfer::STATUS_IN_TRANSIT,
                ];

                $alreadyActive = VehicleTransfer::query()
                    ->where('vehicle_id', $vehicleLocked->id)
                    ->where('id', '!=', $transferLocked->id)
                    ->whereIn('status', $activeTransferStatuses)
                    ->lockForUpdate()
                    ->exists();

                if ($alreadyActive) {
                    throw new \RuntimeException('Vehicle already has an active transfer.');
                }

                $transferLocked->update([
                    'status' => VehicleTransfer::STATUS_APPROVED,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]);

                // Mark vehicle unavailable so it disappears from “available for booking” lists.
                $vehicleLocked->update(['status' => Vehicle::STATUS_UNAVAILABLE]);

                app(AuditLogService::class)->log(
                    $user,
                    'transfer_approved',
                    'vehicle_transfer',
                    $transferLocked->id,
                    ['status' => $transferLocked->getOriginal('status')],
                    ['status' => VehicleTransfer::STATUS_APPROVED],
                    'Transfer approved.',
                    $transferLocked->from_branch_id
                );
            });
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transfer approved.',
            'data'    => $transfer->fresh()->load(['vehicle', 'fromBranch', 'toBranch']),
        ]);
    }

    public function reject(Request $request, VehicleTransfer $transfer): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        if (!in_array($transfer->status, [VehicleTransfer::STATUS_PENDING, 'requested'], true)) {
            return response()->json(['success' => false, 'message' => 'Only pending transfers can be rejected.'], 422);
        }

        if (!$user->isAdmin() && (int) $user->branch_id !== (int) $transfer->to_branch_id) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to reject this transfer.'], 403);
        }

        DB::transaction(function () use ($transfer, $user, $data) {
            $transferLocked = VehicleTransfer::query()->where('id', $transfer->id)->lockForUpdate()->firstOrFail();
            if (!in_array($transferLocked->status, [VehicleTransfer::STATUS_PENDING, 'requested'], true)) {
                throw new \RuntimeException('Transfer cannot be rejected from its current status.');
            }

            $transferLocked->update([
                'status' => VehicleTransfer::STATUS_REJECTED,
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => $data['reason'],
            ]);

            app(AuditLogService::class)->log(
                $user,
                'transfer_rejected',
                'vehicle_transfer',
                $transferLocked->id,
                ['status' => $transferLocked->getOriginal('status')],
                ['status' => VehicleTransfer::STATUS_REJECTED],
                $data['reason'],
                $transferLocked->from_branch_id
            );
        });

        return response()->json(['success' => true, 'message' => 'Transfer rejected.', 'data' => $transfer->fresh()]);
    }

    public function complete(Request $request, VehicleTransfer $transfer): JsonResponse
    {
        $user = $request->user();
        if ($transfer->status !== VehicleTransfer::STATUS_IN_TRANSIT) {
            return response()->json(['success' => false, 'message' => 'Transfer cannot be completed from its current status.'], 422);
        }

        // Only destination branch can confirm arrival.
        if (!$user->isAdmin() && (int) $user->branch_id !== (int) $transfer->to_branch_id) {
            return response()->json(['success' => false, 'message' => 'Only the destination branch can confirm arrival.'], 403);
        }

        try {
            DB::transaction(function () use ($transfer, $user) {
                $transferLocked = VehicleTransfer::query()->where('id', $transfer->id)->lockForUpdate()->firstOrFail();
                if ($transferLocked->status !== VehicleTransfer::STATUS_IN_TRANSIT) {
                    throw new \RuntimeException('Transfer cannot be completed from its current status.');
                }

                $vehicleLocked = Vehicle::query()->where('id', $transferLocked->vehicle_id)->lockForUpdate()->firstOrFail();

                if (Maintenance::query()->active()->where('vehicle_id', $vehicleLocked->id)->exists()) {
                    throw new \RuntimeException('Vehicle is currently under maintenance and cannot be completed.');
                }

                $transferLocked->update([
                    'status' => VehicleTransfer::STATUS_COMPLETED,
                    'completed_by' => $user->id,
                    'completed_at' => now(),
                ]);

                $vehicleLocked->update([
                    'branch_id' => $transferLocked->to_branch_id,
                    'status' => Vehicle::STATUS_AVAILABLE,
                ]);

                app(AuditLogService::class)->log(
                    $user,
                    'transfer_completed',
                    'vehicle_transfer',
                    $transferLocked->id,
                    ['status' => $transferLocked->getOriginal('status')],
                    ['status' => VehicleTransfer::STATUS_COMPLETED],
                    'Transfer completed. Vehicle arrived at destination branch.',
                    $transferLocked->to_branch_id
                );
            });
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transfer completed. Vehicle is now at the destination branch.',
            'data'    => $transfer->fresh()->load(['vehicle', 'fromBranch', 'toBranch']),
        ]);
    }

    /**
     * Admin-only: run approve → in-transit → complete in one step.
     * Moves the vehicle from the source branch to the destination branch immediately.
     */
    public function executeNow(Request $request, VehicleTransfer $transfer): JsonResponse
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Only administrators can execute instant transfers.'], 403);
        }

        if (in_array($transfer->status, [
            VehicleTransfer::STATUS_COMPLETED,
            VehicleTransfer::STATUS_REJECTED,
            VehicleTransfer::STATUS_CANCELLED,
        ], true)) {
            return response()->json(['success' => false, 'message' => 'This transfer is already finalized.'], 422);
        }

        try {
            DB::transaction(function () use ($transfer, $user) {
                $transferLocked = VehicleTransfer::query()->where('id', $transfer->id)->lockForUpdate()->firstOrFail();
                $vehicleLocked = Vehicle::query()->where('id', $transferLocked->vehicle_id)->lockForUpdate()->firstOrFail();

                if (in_array($transferLocked->status, [
                    VehicleTransfer::STATUS_COMPLETED,
                    VehicleTransfer::STATUS_REJECTED,
                    VehicleTransfer::STATUS_CANCELLED,
                ], true)) {
                    throw new \RuntimeException('This transfer is already finalized.');
                }

                // Step 1 — Approve if still pending.
                if (in_array($transferLocked->status, [VehicleTransfer::STATUS_PENDING, 'requested'], true)) {
                    if ($vehicleLocked->status !== Vehicle::STATUS_AVAILABLE) {
                        throw new \RuntimeException('Vehicle cannot be transferred because it is currently not available.');
                    }

                    if (Maintenance::query()->active()->where('vehicle_id', $vehicleLocked->id)->exists()) {
                        throw new \RuntimeException('Vehicle is currently under maintenance and cannot be transferred.');
                    }

                    $transferLocked->update([
                        'status' => VehicleTransfer::STATUS_APPROVED,
                        'approved_by' => $user->id,
                        'approved_at' => now(),
                    ]);

                    $vehicleLocked->update(['status' => Vehicle::STATUS_UNAVAILABLE]);
                    $transferLocked->refresh();
                }

                // Step 2 — Mark in transit and move to destination branch.
                if ($transferLocked->status === VehicleTransfer::STATUS_APPROVED) {
                    $transferLocked->update([
                        'status' => VehicleTransfer::STATUS_IN_TRANSIT,
                        'started_by' => $user->id,
                        'in_transit_at' => now(),
                    ]);

                    $vehicleLocked->update([
                        'branch_id' => $transferLocked->to_branch_id,
                        'status' => Vehicle::STATUS_UNAVAILABLE,
                    ]);
                    $transferLocked->refresh();
                }

                // Step 3 — Complete arrival at destination.
                if ($transferLocked->status === VehicleTransfer::STATUS_IN_TRANSIT) {
                    $transferLocked->update([
                        'status' => VehicleTransfer::STATUS_COMPLETED,
                        'completed_by' => $user->id,
                        'completed_at' => now(),
                    ]);

                    $vehicleLocked->update([
                        'branch_id' => $transferLocked->to_branch_id,
                        'status' => Vehicle::STATUS_AVAILABLE,
                    ]);

                    app(AuditLogService::class)->log(
                        $user,
                        'transfer_executed',
                        'vehicle_transfer',
                        $transferLocked->id,
                        null,
                        ['status' => VehicleTransfer::STATUS_COMPLETED],
                        'Admin executed full transfer. Vehicle moved to destination branch.',
                        $transferLocked->to_branch_id
                    );
                }
            });
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $fresh = $transfer->fresh()->load(['vehicle', 'fromBranch', 'toBranch']);

        return response()->json([
            'success' => true,
            'message' => 'Transfer completed. Vehicle has been moved to the destination branch.',
            'data' => $fresh,
        ]);
    }

    public function markInTransit(VehicleTransfer $transfer): JsonResponse
    {
        $user = request()->user();
        if ($transfer->status !== VehicleTransfer::STATUS_APPROVED) {
            return response()->json(['success' => false, 'message' => 'Only approved transfers can be marked as in transit.'], 422);
        }

        // Authorize: admin, or branch manager/staff of the from-branch.
        if (!$user->isAdmin() && (int) $user->branch_id !== (int) $transfer->from_branch_id) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to start this transfer.'], 403);
        }

        try {
            DB::transaction(function () use ($transfer, $user) {
                $transferLocked = VehicleTransfer::query()->where('id', $transfer->id)->lockForUpdate()->firstOrFail();
                if ($transferLocked->status !== VehicleTransfer::STATUS_APPROVED) {
                    throw new \RuntimeException('Transfer cannot be marked as in transit from its current status.');
                }

                $vehicleLocked = Vehicle::query()->where('id', $transferLocked->vehicle_id)->lockForUpdate()->firstOrFail();
                if (in_array($vehicleLocked->status, [Vehicle::STATUS_RENTED, Vehicle::STATUS_MAINTENANCE], true)) {
                    throw new \RuntimeException('Vehicle cannot be transferred because it is currently rented.');
                }

                if (Maintenance::query()->active()->where('vehicle_id', $vehicleLocked->id)->exists()) {
                    throw new \RuntimeException('Vehicle is currently under maintenance and cannot be transferred.');
                }

                $transferDate = Carbon::parse($transferLocked->transfer_date)->toDateString();
                $dayStart = Carbon::parse($transferDate)->startOfDay();
                $dayEnd = Carbon::parse($transferDate)->endOfDay();
                $hasConfirmedBooking = Booking::query()
                    ->where('vehicle_id', $vehicleLocked->id)
                    ->where('branch_id', $transferLocked->from_branch_id)
                    ->whereIn('status', [
                        Booking::STATUS_CONFIRMED,
                        Booking::STATUS_READY_FOR_PICKUP,
                        Booking::STATUS_ACTIVE,
                    ])
                    ->where(function ($q) use ($dayStart, $dayEnd) {
                        $q->whereBetween('pickup_date', [$dayStart, $dayEnd])
                            ->orWhereBetween('return_date', [$dayStart, $dayEnd])
                            ->orWhere(function ($q2) use ($dayStart, $dayEnd) {
                                $q2->where('pickup_date', '<=', $dayStart)
                                    ->where('return_date', '>=', $dayEnd);
                            });
                    })
                    ->exists();

                if ($hasConfirmedBooking) {
                    throw new \RuntimeException('This vehicle has an upcoming confirmed booking and cannot be transferred.');
                }

                $transferLocked->update([
                    'status' => VehicleTransfer::STATUS_IN_TRANSIT,
                    'started_by' => $user->id,
                    'in_transit_at' => now(),
                ]);

                // Vehicle physically leaves the source branch — assign to destination (unavailable until arrival confirmed).
                $vehicleLocked->update([
                    'branch_id' => $transferLocked->to_branch_id,
                    'status' => Vehicle::STATUS_UNAVAILABLE,
                ]);

                app(AuditLogService::class)->log(
                    $user,
                    'transfer_started',
                    'vehicle_transfer',
                    $transferLocked->id,
                    ['status' => $transferLocked->getOriginal('status')],
                    ['status' => VehicleTransfer::STATUS_IN_TRANSIT],
                    'Transfer started (vehicle is in transit).',
                    $transferLocked->from_branch_id
                );
            });
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Transfer marked as in transit.', 'data' => $transfer->fresh()]);
    }

    public function cancel(Request $request, VehicleTransfer $transfer): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if (!in_array($transfer->status, [VehicleTransfer::STATUS_PENDING, VehicleTransfer::STATUS_APPROVED], true)) {
            return response()->json(['success' => false, 'message' => 'Only pending or approved transfers can be cancelled.'], 422);
        }

        // Only admin or from-branch (requesting branch) manager can cancel.
        if (!$user->isAdmin() && (int) $user->branch_id !== (int) $transfer->from_branch_id) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to cancel this transfer.'], 403);
        }

        try {
            DB::transaction(function () use ($transfer, $user, $data) {
                $transferLocked = VehicleTransfer::query()->where('id', $transfer->id)->lockForUpdate()->firstOrFail();
                $vehicleLocked = Vehicle::query()->where('id', $transferLocked->vehicle_id)->lockForUpdate()->firstOrFail();

                if (!in_array($transferLocked->status, [VehicleTransfer::STATUS_PENDING, VehicleTransfer::STATUS_APPROVED], true)) {
                    throw new \RuntimeException('Transfer cannot be cancelled from its current status.');
                }

                $oldStatus = $transferLocked->status;

                $transferLocked->update([
                    'status' => VehicleTransfer::STATUS_CANCELLED,
                    'cancelled_by' => $user->id,
                    'cancelled_at' => now(),
                    'cancellation_reason' => $data['reason'] ?? null,
                ]);

                // Restore vehicle to source branch if transfer had already moved it.
                if ($oldStatus === VehicleTransfer::STATUS_IN_TRANSIT
                    && (int) $vehicleLocked->branch_id === (int) $transferLocked->to_branch_id) {
                    $vehicleLocked->update([
                        'branch_id' => $transferLocked->from_branch_id,
                        'status' => Vehicle::STATUS_AVAILABLE,
                    ]);
                } elseif ($oldStatus === VehicleTransfer::STATUS_APPROVED
                    && $vehicleLocked->status === Vehicle::STATUS_UNAVAILABLE) {
                    $vehicleLocked->update(['status' => Vehicle::STATUS_AVAILABLE]);
                }

                app(AuditLogService::class)->log(
                    $user,
                    'transfer_cancelled',
                    'vehicle_transfer',
                    $transferLocked->id,
                    ['status' => $transferLocked->getOriginal('status')],
                    ['status' => VehicleTransfer::STATUS_CANCELLED],
                    $data['reason'] ?? null,
                    $transferLocked->from_branch_id
                );
            });
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Transfer cancelled.', 'data' => $transfer->fresh()]);
    }

    public function history(Request $request, VehicleTransfer $transfer): JsonResponse
    {
        $user = $request->user();

        if (!$user->isAdmin() && $user->branch_id !== $transfer->from_branch_id && $user->branch_id !== $transfer->to_branch_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $history = VehicleTransfer::query()
            ->with(['fromBranch', 'toBranch', 'requester', 'approvedByUser', 'completedByUser'])
            ->where('vehicle_id', $transfer->vehicle_id)
            ->where('status', VehicleTransfer::STATUS_COMPLETED)
            ->orderByDesc('completed_at')
            ->get();

        return response()->json(['success' => true, 'data' => $history]);
    }
}
