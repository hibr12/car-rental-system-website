<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Maintenance;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleTransfer;
use App\Services\BranchManagerProvisioningService;
use App\Services\BranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{
    public function __construct(
        private BranchService $branchService,
        private BranchManagerProvisioningService $managerProvisioning
    ) {}

    // ─── List / Show ──────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        // Public/customer screens only need branch directory data. Avoid the
        // operational counts and financial aggregates used by management.
        if (!$request->user() || $request->user()->isCustomer()) {
            $status = $request->filled('status')
                ? (string) $request->input('status')
                : 'active';

            $load = fn () => Branch::query()
                ->select([
                    'id', 'name', 'code', 'address', 'city', 'phone', 'email',
                    'latitude', 'longitude', 'opening_time', 'closing_time', 'status',
                ])
                ->where('status', $status)
                ->orderBy('name')
                ->get();

            // Branch directory fields change rarely and do not contain
            // availability, booking, payment, or other transactional state.
            $branches = in_array($status, ['active', 'inactive'], true)
                ? Cache::remember("branches:directory:{$status}", now()->addMinutes(2), $load)
                : $load();

            return response()->json(['success' => true, 'data' => $branches]);
        }

        $activeTransferStatuses = [
            VehicleTransfer::STATUS_PENDING,
            VehicleTransfer::STATUS_APPROVED,
            VehicleTransfer::STATUS_IN_TRANSIT,
        ];

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $query = Branch::with('manager', 'company')
            ->withCount([
                'vehicles',
                'vehicles as available_vehicles_count' => fn ($q) => $q->where('status', 'available'),
                'vehicles as maintenance_vehicles_count' => fn ($q) => $q->where('status', 'maintenance'),
                'vehicles as rented_vehicles_count' => fn ($q) => $q->where('status', 'rented'),
                'bookings',
                'bookings as active_bookings_count' => fn ($q) => $q->where('status', 'active'),
                'bookings as pending_bookings_count' => fn ($q) => $q->where('status', 'pending'),
                'users as staff_count' => function ($q) {
                    $q->whereIn('role', [
                        User::ROLE_BRANCH_STAFF,
                        User::ROLE_BRANCH_MANAGER,
                    ]);
                },
                'vehicleTransfersFrom as outgoing_transfers_count' => fn ($q) => $q->whereIn('status', $activeTransferStatuses),
                'vehicleTransfersTo as incoming_transfers_count' => fn ($q) => $q->whereIn('status', $activeTransferStatuses),
                'maintenances as maintenance_count' => fn ($q) => $q->whereIn('status', ['scheduled', 'in_progress']),
            ]);

        $query->addSelect([
            'pending_transfers_count' => VehicleTransfer::query()
                ->selectRaw('COUNT(*)')
                ->where('status', VehicleTransfer::STATUS_PENDING)
                ->where(function ($q) {
                    $q->whereColumn('from_branch_id', 'branches.id')
                        ->orWhereColumn('to_branch_id', 'branches.id');
                }),
            'monthly_revenue' => Payment::query()
                ->selectRaw('COALESCE(SUM(amount), 0)')
                ->whereColumn('branch_id', 'branches.id')
                ->where('status', Payment::STATUS_PAID)
                ->whereBetween('paid_at', [$monthStart, $monthEnd]),
            'total_revenue' => Payment::query()
                ->selectRaw('COALESCE(SUM(amount), 0)')
                ->whereColumn('branch_id', 'branches.id')
                ->where('status', Payment::STATUS_PAID),
        ]);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        } elseif (!$request->user()?->isAdmin()) {
            $query->where('status', 'active');
        }

        $branches = $query->orderBy('name')->get()->each(function (Branch $branch) {
            $branch->monthly_revenue = (float) ($branch->monthly_revenue ?? 0);
            $branch->total_revenue = (float) ($branch->total_revenue ?? 0);
        });

        return response()->json(['success' => true, 'data' => $branches]);
    }

    public function show(Branch $branch): JsonResponse
    {
        $branch->load('manager', 'company');

        return response()->json(['success' => true, 'data' => $branch]);
    }

    /**
     * Minimal branch list for transfer destination selection.
     * Exposes only public operational fields — no fleet, staff, or financial data.
     */
    public function transferDestinations(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || (!$user->isAdmin() && !$user->isFleetManager() && !$user->isBranchManager())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $query = Branch::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->select(['id', 'name', 'code', 'city', 'address', 'status']);

        if ($user->isBranchManager() && $user->branch_id) {
            $query->where('id', '!=', $user->branch_id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    // ─── Create ───────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'code'         => ['required', 'string', 'max:20', 'unique:branches,code'],
            'address'      => ['required', 'string'],
            'city'         => ['required', 'string', 'max:100'],
            'phone'        => ['nullable', 'string', 'max:30'],
            'email'        => ['nullable', 'email', 'max:255'],
            'latitude'     => ['nullable', 'numeric'],
            'longitude'    => ['nullable', 'numeric'],
            'opening_time' => ['nullable', 'string'],
            'closing_time' => ['nullable', 'string'],
            'manager_id'   => ['nullable', 'exists:users,id'],
            'create_manager' => ['sometimes', 'boolean'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'manager_email' => ['nullable', 'email', 'max:255'],
            'manager_password' => ['nullable', 'string', 'min:6', 'max:255'],
            'status'       => ['sometimes', 'in:active,inactive'],
        ]);

        $company = Company::first() ?? Company::updateOrCreate(
            ['code' => 'APEX'],
            [
                'name'      => 'Apex Rentals',
                'address'   => 'Addis Ababa, Ethiopia',
                'phone'     => '+251 11 123 4567',
                'email'     => 'info@apexrentals.com',
                'is_active' => true,
            ]
        );

        $branchFields = collect($data)->only([
            'name', 'address', 'city', 'phone', 'email',
            'latitude', 'longitude', 'opening_time', 'closing_time', 'status',
        ])->toArray();

        $branch = Branch::create(array_merge($branchFields, [
            'company_id' => $company->id,
            'code'       => strtoupper($data['code']),
            'status'     => $data['status'] ?? 'active',
        ]));

        if (!empty($data['manager_id'])) {
            $this->managerProvisioning->assignManager(
                $branch,
                User::findOrFail($data['manager_id']),
                $request->user()
            );
        } elseif ($request->boolean('create_manager', true)) {
            $this->managerProvisioning->provision($branch, array_filter([
                'name' => $data['manager_name'] ?? null,
                'email' => $data['manager_email'] ?? null,
                'password' => $data['manager_password'] ?? null,
                'phone' => $data['phone'] ?? null,
            ]), $request->user());
        }

        $this->forgetDirectoryCache();

        // Fire BranchCreated event
        event(new \App\Events\BranchCreated($branch));

        return response()->json([
            'success' => true,
            'message' => 'Branch created successfully.',
            'data'    => $branch->fresh()->load('manager'),
        ], 201);
    }

    // ─── Update ───────────────────────────────────────────────────────

    public function update(Request $request, Branch $branch): JsonResponse
    {
        $data = $request->validate([
            'name'         => ['sometimes', 'string', 'max:255'],
            'code'         => ['sometimes', 'string', 'max:20', 'unique:branches,code,' . $branch->id],
            'address'      => ['sometimes', 'string'],
            'city'         => ['sometimes', 'string', 'max:100'],
            'phone'        => ['nullable', 'string', 'max:30'],
            'email'        => ['nullable', 'email', 'max:255'],
            'latitude'     => ['nullable', 'numeric'],
            'longitude'    => ['nullable', 'numeric'],
            'opening_time' => ['nullable', 'string'],
            'closing_time' => ['nullable', 'string'],
            'manager_id'   => ['nullable', 'exists:users,id'],
            'status'       => ['sometimes', 'in:active,inactive'],
        ]);

        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }

        DB::transaction(function () use ($branch, $data, $request) {
            $changes = [];

            if (array_key_exists('manager_id', $data)) {
                if ($data['manager_id']) {
                    $this->managerProvisioning->assignManager(
                        $branch,
                        User::findOrFail($data['manager_id']),
                        $request->user()
                    );
                } elseif ($branch->manager_id) {
                    User::where('id', $branch->manager_id)
                        ->where('role', User::ROLE_BRANCH_MANAGER)
                        ->update(['role' => User::ROLE_BRANCH_STAFF]);
                    $branch->update(['manager_id' => null]);
                    $changes['manager_id'] = ['old' => $branch->manager_id, 'new' => null];
                }
            }

            $updateData = collect($data)->except([
                'manager_id', 'create_manager', 'manager_name', 'manager_email', 'manager_password',
            ])->toArray();

            foreach ($updateData as $key => $value) {
                if ($branch->getAttribute($key) !== $value) {
                    $changes[$key] = ['old' => $branch->getAttribute($key), 'new' => $value];
                }
            }

            $branch->update($updateData);
        });

        $this->forgetDirectoryCache();

        // Fire BranchUpdated event if there are changes
        if (!empty($changes)) {
            event(new \App\Events\BranchUpdated($branch, $changes));
        }

        return response()->json([
            'success' => true,
            'message' => 'Branch updated successfully.',
            'data'    => $branch->fresh()->load('manager'),
        ]);
    }

    // ─── Activate / Deactivate ────────────────────────────────────────

    public function activate(Branch $branch): JsonResponse
    {
        $oldStatus = $branch->status;
        $branch->update(['status' => 'active']);
        $this->forgetDirectoryCache();
        
        if ($oldStatus !== 'active') {
            event(new \App\Events\BranchUpdated($branch, ['status' => ['old' => $oldStatus, 'new' => 'active']]));
        }
        
        return response()->json(['success' => true, 'message' => 'Branch activated.', 'data' => $branch->fresh()]);
    }

    public function deactivate(Branch $branch): JsonResponse
    {
        $oldStatus = $branch->status;
        $branch->update(['status' => 'inactive']);
        $this->forgetDirectoryCache();
        
        if ($oldStatus !== 'inactive') {
            event(new \App\Events\BranchUpdated($branch, ['status' => ['old' => $oldStatus, 'new' => 'inactive']]));
        }
        
        return response()->json(['success' => true, 'message' => 'Branch deactivated.', 'data' => $branch->fresh()]);
    }

    // ─── Branch-specific sub-resources ───────────────────────────────

    public function vehicles(Branch $branch, Request $request): JsonResponse
    {
        $query = Vehicle::with(['category', 'primaryImage'])
            ->where('branch_id', $branch->id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $vehicles = $query->orderBy('brand')->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $vehicles->items(),
            'meta'    => [
                'current_page' => $vehicles->currentPage(),
                'last_page'    => $vehicles->lastPage(),
                'total'        => $vehicles->total(),
            ],
        ]);
    }

    public function staff(Branch $branch): JsonResponse
    {
        $staff = User::where('branch_id', $branch->id)
            ->whereNotIn('role', [User::ROLE_CUSTOMER])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'role', 'branch_id', 'created_at']);

        return response()->json(['success' => true, 'data' => $staff]);
    }

    public function bookings(Branch $branch, Request $request): JsonResponse
    {
        $query = Booking::with(['user', 'vehicle.category'])
            ->where('branch_id', $branch->id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->orderByDesc('created_at')->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => $bookings->items(),
            'meta'    => [
                'current_page' => $bookings->currentPage(),
                'last_page'    => $bookings->lastPage(),
                'total'        => $bookings->total(),
            ],
        ]);
    }

    public function payments(Branch $branch, Request $request): JsonResponse
    {
        $query = Payment::with(['booking', 'user'])
            ->where('branch_id', $branch->id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->orderByDesc('created_at')->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => $payments->items(),
            'meta'    => [
                'current_page' => $payments->currentPage(),
                'last_page'    => $payments->lastPage(),
                'total'        => $payments->total(),
            ],
        ]);
    }

    /**
     * Branch manager's own dashboard — auto-scoped to their branch.
     */
    public function branchManagerDashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            $branch = $this->branchService->resolveBranch(
                $user,
                $request->input('branch_id') ? (int) $request->input('branch_id') : null
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $this->branchService->dashboardStats($branch),
        ]);
    }

    public function branchCustomers(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isBranchManager() && !$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        try {
            $branch = $this->branchService->resolveBranch(
                $user,
                $request->input('branch_id') ? (int) $request->input('branch_id') : null
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $customers = $this->branchService->branchCustomers($branch, (int) $request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $customers->items(),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    public function dashboard(Branch $branch): JsonResponse
    {
        $today = now()->startOfDay();
        $tomorrow = $today->copy()->addDay();
        $monthStart = now()->startOfMonth();
        $nextMonth = $monthStart->copy()->addMonth();

        $vehicleStats = Vehicle::query()
            ->where('branch_id', $branch->id)
            ->selectRaw(
                'COUNT(*) AS total_vehicles,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS available_vehicles,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS rented_vehicles,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS maintenance_vehicles',
                ['available', 'rented', 'maintenance'],
            )
            ->first();

        $bookingStats = Booking::query()
            ->where('branch_id', $branch->id)
            ->selectRaw(
                'SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END) AS todays_bookings,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS active_rentals,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS pending_bookings',
                [$today, $tomorrow, 'active', 'pending'],
            )
            ->first();

        $paymentStats = Payment::query()
            ->where('branch_id', $branch->id)
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN status = ? AND paid_at >= ? AND paid_at < ? THEN amount ELSE 0 END), 0) AS monthly_revenue,
                 COALESCE(SUM(CASE WHEN status = ? AND paid_at >= ? AND paid_at < ? THEN amount ELSE 0 END), 0) AS todays_revenue,
                 COALESCE(SUM(CASE WHEN status = ? THEN amount ELSE 0 END), 0) AS total_revenue',
                ['paid', $monthStart, $nextMonth, 'paid', $today, $tomorrow, 'paid'],
            )
            ->first();

        $data = [
            'branch'              => $branch->load('manager'),
            'available_vehicles'  => (int) ($vehicleStats->available_vehicles ?? 0),
            'rented_vehicles'     => (int) ($vehicleStats->rented_vehicles ?? 0),
            'maintenance_vehicles'=> (int) ($vehicleStats->maintenance_vehicles ?? 0),
            'total_vehicles'      => (int) ($vehicleStats->total_vehicles ?? 0),
            'todays_bookings'     => (int) ($bookingStats->todays_bookings ?? 0),
            'active_rentals'      => (int) ($bookingStats->active_rentals ?? 0),
            'pending_bookings'    => (int) ($bookingStats->pending_bookings ?? 0),
            'monthly_revenue'     => (float) ($paymentStats->monthly_revenue ?? 0),
            'todays_revenue'      => (float) ($paymentStats->todays_revenue ?? 0),
            'total_revenue'       => (float) ($paymentStats->total_revenue ?? 0),
            'maintenance_count'   => Maintenance::where('branch_id', $branch->id)
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->count(),
            'recent_bookings'     => Booking::with(['user', 'vehicle'])
                ->where('branch_id', $branch->id)
                ->orderByDesc('created_at')
                ->take(5)
                ->get(),
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }

    private function forgetDirectoryCache(): void
    {
        Cache::forget('branches:directory:active');
        Cache::forget('branches:directory:inactive');
    }
}
