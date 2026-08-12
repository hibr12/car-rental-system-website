<?php

namespace App\Http\Controllers;

use App\Models\VehicleTransfer;
use App\Services\BranchScopeService;
use App\Services\VehicleTransferService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleTransferController extends Controller
{
    public function __construct(
        private VehicleTransferService $transferService,
        private BranchScopeService $branchScope
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = VehicleTransfer::query()
            ->with([
                'vehicle.category',
                'fromBranch',
                'toBranch',
                'requester',
                'approvedByUser',
                'releasedByUser',
                'receivedByUser',
                'completedByUser',
                'rejectedByUser',
            ]);

        if (!$user->isAdmin() && !$user->isFleetManager()) {
            $branchId = $user->branch_id;
            $query->where(function ($q) use ($branchId) {
                $q->where('from_branch_id', $branchId)
                    ->orWhere('to_branch_id', $branchId);
            });
        }

        if ($request->filled('direction') && $user->branch_id) {
            if ($request->string('direction') === 'outgoing') {
                $query->where('from_branch_id', $user->branch_id);
            } elseif ($request->string('direction') === 'incoming') {
                $query->where('to_branch_id', $user->branch_id);
            }
        }

        if ($request->filled('status')) {
            $status = $request->string('status');
            if ($status === 'pending') {
                $query->whereIn('status', [VehicleTransfer::STATUS_PENDING, 'requested']);
            } elseif ($status === 'approved') {
                $query->whereIn('status', [VehicleTransfer::STATUS_APPROVED, VehicleTransfer::STATUS_READY_FOR_RELEASE]);
            } else {
                $query->where('status', $status);
            }
        }

        foreach (['from_branch_id', 'to_branch_id', 'vehicle_id'] as $field) {
            if ($request->filled($field)) {
                if (in_array($field, ['from_branch_id', 'to_branch_id'], true)) {
                    $this->branchScope->assertTransferBranchFilter($user, (int) $request->input($field));
                }
                $query->where($field, (int) $request->input($field));
            }
        }

        if ($request->filled('branch_id')) {
            $branchId = (int) $request->input('branch_id');
            $this->branchScope->assertTransferBranchFilter($user, $branchId);
            $query->where(function ($q) use ($branchId) {
                $q->where('from_branch_id', $branchId)
                    ->orWhere('to_branch_id', $branchId);
            });
        }

        if ($request->filled('search')) {
            $search = strtolower($request->string('search'));
            $query->where(function ($q) use ($search) {
                if (ctype_digit($search)) {
                    $q->orWhere('id', (int) $search);
                }
                $q->orWhereHas('vehicle', function ($q2) use ($search) {
                    $q2->whereRaw('LOWER(brand) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(model) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(registration_number) LIKE ?', ["%{$search}%"]);
                })
                    ->orWhereHas('fromBranch', fn ($q2) => $q2->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]))
                    ->orWhereHas('toBranch', fn ($q2) => $q2->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]))
                    ->orWhereHas('requester', fn ($q2) => $q2->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]));
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

        $scopedBase = clone $query;
        $counts = [
            'pending' => (clone $scopedBase)->whereIn('status', [VehicleTransfer::STATUS_PENDING, 'requested'])->count(),
            'approved' => (clone $scopedBase)->whereIn('status', [VehicleTransfer::STATUS_APPROVED, VehicleTransfer::STATUS_READY_FOR_RELEASE])->count(),
            'ready_for_release' => (clone $scopedBase)->where('status', VehicleTransfer::STATUS_READY_FOR_RELEASE)->count(),
            'in_transit' => (clone $scopedBase)->where('status', VehicleTransfer::STATUS_IN_TRANSIT)->count(),
            'received' => (clone $scopedBase)->whereIn('status', [VehicleTransfer::STATUS_RECEIVED, VehicleTransfer::STATUS_RECEIVED_PENDING_INSPECTION])->count(),
            'completed' => (clone $scopedBase)->where('status', VehicleTransfer::STATUS_COMPLETED)->count(),
            'rejected' => (clone $scopedBase)->where('status', VehicleTransfer::STATUS_REJECTED)->count(),
            'cancelled' => (clone $scopedBase)->where('status', VehicleTransfer::STATUS_CANCELLED)->count(),
            'failed' => (clone $scopedBase)->where('status', VehicleTransfer::STATUS_FAILED)->count(),
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
                'stats' => array_merge(['total' => (clone $scopedBase)->count()], $counts),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'to_branch_id' => ['required', 'exists:branches,id'],
            'transfer_date' => ['required', 'date', 'after_or_equal:today'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $transfer = $this->transferService->createRequest($data, $user);
        } catch (\RuntimeException $e) {
            $msg = $e->getMessage();
            $code = str_contains($msg, 'active transfer') ? 409 : (str_contains($msg, 'own branch') ? 403 : 422);
            return response()->json(['success' => false, 'message' => $msg], $code);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transfer request created.',
            'data' => $transfer,
        ], 201);
    }

    public function show(Request $request, VehicleTransfer $transfer): JsonResponse
    {
        if (!$this->canView($request->user(), $transfer)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $transfer->load([
                'vehicle.category',
                'fromBranch',
                'toBranch',
                'requester',
                'approver',
                'releasedByUser',
                'receivedByUser',
                'rejectedByUser',
                'cancelledByUser',
                'completedByUser',
            ]),
        ]);
    }

    public function approve(Request $request, VehicleTransfer $transfer): JsonResponse
    {
        $data = $request->validate([
            'approval_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $transfer = $this->transferService->approve($transfer, $request->user(), $data['approval_notes'] ?? null);
        } catch (\RuntimeException $e) {
            $code = str_contains($e->getMessage(), 'authorized') ? 403 : 422;
            return response()->json(['success' => false, 'message' => $e->getMessage()], $code);
        }

        return response()->json(['success' => true, 'message' => 'Transfer approved.', 'data' => $transfer]);
    }

    public function reject(Request $request, VehicleTransfer $transfer): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $transfer = $this->transferService->reject($transfer, $request->user(), $data['reason']);
        } catch (\RuntimeException $e) {
            $code = str_contains($e->getMessage(), 'authorized') ? 403 : 422;
            return response()->json(['success' => false, 'message' => $e->getMessage()], $code);
        }

        return response()->json(['success' => true, 'message' => 'Transfer rejected.', 'data' => $transfer]);
    }

    public function cancel(Request $request, VehicleTransfer $transfer): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $transfer = $this->transferService->cancel($transfer, $request->user(), $data['reason'] ?? null);
        } catch (\RuntimeException $e) {
            $code = str_contains($e->getMessage(), 'authorized') ? 403 : 422;
            return response()->json(['success' => false, 'message' => $e->getMessage()], $code);
        }

        return response()->json(['success' => true, 'message' => 'Transfer cancelled.', 'data' => $transfer]);
    }

    public function prepareRelease(Request $request, VehicleTransfer $transfer): JsonResponse
    {
        $data = $request->validate([
            'source_odometer' => ['nullable', 'integer', 'min:0'],
            'source_fuel_level' => ['nullable', 'integer', 'min:0', 'max:100'],
            'source_condition' => ['nullable', 'string', 'max:50'],
            'release_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $transfer = $this->transferService->prepareRelease($transfer, $request->user(), $data);
        } catch (\RuntimeException $e) {
            $code = str_contains($e->getMessage(), 'authorized') ? 403 : 422;
            return response()->json(['success' => false, 'message' => $e->getMessage()], $code);
        }

        return response()->json(['success' => true, 'message' => 'Release preparation saved.', 'data' => $transfer]);
    }

    public function markInTransit(Request $request, VehicleTransfer $transfer): JsonResponse
    {
        $data = $request->validate([
            'source_odometer' => ['nullable', 'integer', 'min:0'],
            'source_fuel_level' => ['nullable', 'integer', 'min:0', 'max:100'],
            'source_condition' => ['nullable', 'string', 'max:50'],
            'release_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $transfer = $this->transferService->release($transfer, $request->user(), $data);
        } catch (\RuntimeException $e) {
            $code = str_contains($e->getMessage(), 'authorized') ? 403 : 422;
            return response()->json(['success' => false, 'message' => $e->getMessage()], $code);
        }

        return response()->json(['success' => true, 'message' => 'Transfer marked as in transit.', 'data' => $transfer]);
    }

    public function receive(Request $request, VehicleTransfer $transfer): JsonResponse
    {
        $data = $request->validate([
            'destination_odometer' => ['nullable', 'integer', 'min:0'],
            'destination_fuel_level' => ['nullable', 'integer', 'min:0', 'max:100'],
            'destination_condition' => ['nullable', 'string', 'max:50'],
            'receiving_notes' => ['nullable', 'string', 'max:1000'],
            'has_damage' => ['nullable', 'boolean'],
            'damage_report' => ['nullable', 'string', 'max:2000'],
            'damage_severity' => ['nullable', 'string', 'max:20'],
            'damage_location' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $transfer = $this->transferService->receive($transfer, $request->user(), $data);
        } catch (\RuntimeException $e) {
            $code = str_contains($e->getMessage(), 'authorized') ? 403 : 422;
            return response()->json(['success' => false, 'message' => $e->getMessage()], $code);
        }

        return response()->json(['success' => true, 'message' => 'Vehicle received.', 'data' => $transfer]);
    }

    public function complete(Request $request, VehicleTransfer $transfer): JsonResponse
    {
        try {
            $transfer = $this->transferService->complete($transfer, $request->user());
        } catch (\RuntimeException $e) {
            $code = str_contains($e->getMessage(), 'authorized') ? 403 : 422;
            return response()->json(['success' => false, 'message' => $e->getMessage()], $code);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transfer completed. Vehicle is now at the destination branch.',
            'data' => $transfer,
        ]);
    }

    public function markFailed(Request $request, VehicleTransfer $transfer): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $transfer = $this->transferService->markFailed($transfer, $request->user(), $data['reason']);
        } catch (\RuntimeException $e) {
            $code = str_contains($e->getMessage(), 'authorized') ? 403 : 422;
            return response()->json(['success' => false, 'message' => $e->getMessage()], $code);
        }

        return response()->json(['success' => true, 'message' => 'Transfer marked as failed.', 'data' => $transfer]);
    }

    public function executeNow(Request $request, VehicleTransfer $transfer): JsonResponse
    {
        try {
            $transfer = $this->transferService->executeNow($transfer, $request->user());
        } catch (\RuntimeException $e) {
            $code = str_contains($e->getMessage(), 'authorized') ? 403 : 422;
            return response()->json(['success' => false, 'message' => $e->getMessage()], $code);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transfer completed. Vehicle has been moved to the destination branch.',
            'data' => $transfer,
        ]);
    }

    public function history(Request $request, VehicleTransfer $transfer): JsonResponse
    {
        if (!$this->canView($request->user(), $transfer)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $history = VehicleTransfer::query()
            ->with(['fromBranch', 'toBranch', 'requester', 'approvedByUser', 'releasedByUser', 'receivedByUser', 'completedByUser'])
            ->where('vehicle_id', $transfer->vehicle_id)
            ->where('status', VehicleTransfer::STATUS_COMPLETED)
            ->orderByDesc('completed_at')
            ->get();

        return response()->json(['success' => true, 'data' => $history]);
    }

    private function canView($user, VehicleTransfer $transfer): bool
    {
        if ($user->isAdmin() || $user->isFleetManager()) {
            return true;
        }

        return (int) $user->branch_id === (int) $transfer->from_branch_id
            || (int) $user->branch_id === (int) $transfer->to_branch_id;
    }
}
