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
        $activeTransferStatuses = [
            VehicleTransfer::STATUS_PENDING,
            VehicleTransfer::STATUS_APPROVED,
            VehicleTransfer::STATUS_IN_TRANSIT,
        ];

        $query = Branch::with('manager', 'company')
            ->withCount([
                'vehicles',
                'vehicles as available_vehicles_count' => fn ($q) => $q->where('status', 'available'),
                'bookings',
                'users as staff_count' => function ($q) {
                    $q->whereIn('role', [
                        User::ROLE_BRANCH_STAFF,
                        User::ROLE_BRANCH_MANAGER,
                    ]);
                },
                'vehicleTransfersFrom as outgoing_transfers_count' => fn ($q) => $q->whereIn('status', $activeTransferStatuses),
                'vehicleTransfersTo as incoming_transfers_count' => fn ($q) => $q->whereIn('status', $activeTransferStatuses),
            ]);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        } elseif (!$request->user()?->isAdmin()) {
            // Public/customer requests only see active branches by default
            $query->where('status', 'active');
        }

        $branches = $query->orderBy('name')->get()->map(function (Branch $branch) {
            $branch->pending_transfers_count = VehicleTransfer::query()
                ->where('status', VehicleTransfer::STATUS_PENDING)
                ->where(function ($q) use ($branch) {
                    $q->where('from_branch_id', $branch->id)
                        ->orWhere('to_branch_id', $branch->id);
                })
                ->count();

            return $branch;
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
                }
            }

            $branch->update(collect($data)->except([
                'manager_id', 'create_manager', 'manager_name', 'manager_email', 'manager_password',
            ])->toArray());
        });

        return response()->json([
            'success' => true,
            'message' => 'Branch updated successfully.',
            'data'    => $branch->fresh()->load('manager'),
        ]);
    }

    // ─── Activate / Deactivate ────────────────────────────────────────

    public function activate(Branch $branch): JsonResponse
    {
        $branch->update(['status' => 'active']);
        return response()->json(['success' => true, 'message' => 'Branch activated.', 'data' => $branch->fresh()]);
    }

    public function deactivate(Branch $branch): JsonResponse
    {
        $branch->update(['status' => 'inactive']);
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
        $monthStart = now()->startOfMonth();
        $monthEnd   = now()->endOfMonth();

        $data = [
            'branch'              => $branch->load('manager'),
            'available_vehicles'  => Vehicle::where('branch_id', $branch->id)->where('status', 'available')->count(),
            'rented_vehicles'     => Vehicle::where('branch_id', $branch->id)->where('status', 'rented')->count(),
            'maintenance_vehicles'=> Vehicle::where('branch_id', $branch->id)->where('status', 'maintenance')->count(),
            'total_vehicles'      => Vehicle::where('branch_id', $branch->id)->count(),
            'todays_bookings'     => Booking::where('branch_id', $branch->id)->whereDate('created_at', $today)->count(),
            'active_rentals'      => Booking::where('branch_id', $branch->id)->where('status', 'active')->count(),
            'pending_bookings'    => Booking::where('branch_id', $branch->id)->where('status', 'pending')->count(),
            'monthly_revenue'     => (float) Payment::where('branch_id', $branch->id)
                ->where('status', 'paid')
                ->whereBetween('paid_at', [$monthStart, $monthEnd])
                ->sum('amount'),
            'todays_revenue'      => (float) Payment::where('branch_id', $branch->id)
                ->where('status', 'paid')
                ->whereDate('paid_at', $today)
                ->sum('amount'),
            'total_revenue'       => (float) Payment::where('branch_id', $branch->id)->where('status', 'paid')->sum('amount'),
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
}
